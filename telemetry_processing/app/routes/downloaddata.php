<?php
/**
 * downloaddata.php
 *
 * Route that is responsible for downloading, parsing, validating
 * and storing m2m messages in the database
 *
 * @author Adam Machowczyk, Dominik Michalski
 */

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

/**
 * download data button was pressed on homepage
 */
$app->any(
    '/downloaddata',
    function (Request $request, Response $response) use ($app) {
        $settings = $app->getContainer()->get('settings');
        $M2Mcredentials = $settings['m2m_credentials'];

        //data required for most operations
        $connection_parameters = [
            'username' =>  $M2Mcredentials['login'],
            'password' =>  $M2Mcredentials['password'],
            'count' => $M2Mcredentials['count'],
            'deviceMsisdn' => $M2Mcredentials['deviceMsisdn'],
            'countryCode' => $M2Mcredentials['countryCode'],
            'connectionType' => "peekMessages"
        ];

        //first we download the data from a web server
        $returnMsgs = downloadTelemetryData($app, $connection_parameters);

        //next we parse the data from an xml array into a normal array
        $parsedMsgs = parseTelemetryData($app, $returnMsgs);

        //then we validate whether the data is correct or not
        $validatedMsgs = validateTelemetryData($app, $parsedMsgs);

        //next we save the data into the database
        saveTelemetryData($app, $validatedMsgs);

        //next we download the data from the database in order to display it to the user
        $dbMessages = downloadTelemetryDataDB($app);
        
        //if there is a user logged in, record the activity
        if (isset($_SESSION['userId']) && $_SESSION['userId']) {
            $databaseWrapper = $app->getContainer()->get('DatabaseWrapper');
            $db_conf = $app->getContainer()->get('settings');
            $database_connection_settings = $db_conf['pdo_settings'];
            $databaseWrapper->setDatabaseConnectionSettings($database_connection_settings);
            $databaseWrapper->logActivity($_SESSION['userId'], "Downloaded Data", 1);
        }

        //render the page
        return $this->view->render(
            $response,
            'display_download_results.html.twig',
            [
                'css_path' => CSS_PATH,
                'landing_page' => LANDING_PAGE,
                'initial_input_box_value' => null,
                'page_title' => APP_NAME,
                'page_heading_1' => APP_NAME,
                'page_heading_2' => 'Downloading results',
                'telemetry' => $dbMessages,
                'session' => $_SESSION
            ]
        );
    }
);

/**
 * The method creates an instance of the telemetryDataDownloader class to set its parameters and download the data
 *
 * @param $app
 * @param $connection_parameters
 * @return mixed
 */
function downloadTelemetryData($app, $connection_parameters)
{
    $telemetryDataDownloader = $app->getContainer()->get('telemetryDataDownloader');
    $telemetryDataDownloader->setParserParameters($connection_parameters);
    $telemetryDataDownloader->parseTelemetryData();
    $data_parse_result = $telemetryDataDownloader->getResult();

    if ($data_parse_result === false) {
        $data_parse_result = 'not available';
    }

    return $data_parse_result;
}

/**
 * The method creates an instance of the telemetryDataParser class and passes the downloaded data to parse it
 *
 * @param $app
 * @param $unparsedData
 * @return mixed
 */
function parseTelemetryData($app, $unparsedData)
{
    $telemetryDataParser = $app->getContainer()->get('telemetryDataParser');
    $parsedDataArray = [];
    $index = 0;

    foreach ($unparsedData as $dataPiece) {
        //Temporary array to store the data in
        $parsedData = [];

        //suppress display of xml errors, the parser will discard the invalid inputs later
        libxml_use_internal_errors(true);

        //Returned XML string
        $dataString = simplexml_load_string($dataPiece);
        $parsedData['dataString'] = $dataString;

        //Check if the data came from our device
        $parsedData['switchesArray'] = isset($dataString->message->t->ss->s) ? $dataString->message->t->ss->s : '';
        $parsedData['fan'] = isset($dataString->message->t->f) ? $dataString->message->t->f : '';
        $parsedData['temperature'] = isset($dataString->message->t->tp) ? $dataString->message->t->tp->__toString() : '';
        $parsedData['keypad'] = isset($dataString->message->t->tk) ? $dataString->message->t->tk : '';
        $parsedData['unique_id'] = isset($dataString->message->t->id) ? $dataString->message->t->id->__toString() : '';
        $parsedData['src_number'] = isset($dataString->sourcemsisdn) ? $dataString->sourcemsisdn->__toString() : '';
        
        //in case temperature was returned with ',' instead of '.'
        $parsedData['temperature'] = str_replace(',', '.', $parsedData['temperature']);

        //If the data came from another device, discard it
        if ($parsedData['switchesArray'] != '' && count($parsedData['switchesArray']) == 4
            && $parsedData['fan'] != '' && strlen($parsedData['fan']) > 0
            && $parsedData['temperature'] != '' && strlen($parsedData['temperature']) > 0
            && $parsedData['keypad'] != '' && strlen($parsedData['keypad']) > 0
            && $parsedData['unique_id'] != '' && strlen($parsedData['unique_id']) > 0
            && $parsedData['src_number'] != '' && strlen($parsedData['src_number']) > 0
        ) {
            //all data correct
            $telemetryDataParser->setParserParameters($parsedData);
            $telemetryDataParser->parseTelemetryData();
            array_push($parsedDataArray, $telemetryDataParser->getResult());
            $index++;
        }
    }
    return $parsedDataArray;
}

/**
 * The method creates an instance of the telemetryDataValidator class and passes parsed data to it
 *
 * @param $app
 * @param array $tainted_data
 * @return mixed
 */
function validateTelemetryData($app, array $tainted_data): array
{
    $cleaned_data = [];
    $telemetryDataValidator = $app->getContainer()->get('telemetryDataValidator');

    foreach ($tainted_data as $index => $tainted_response) {
        //save the data to temp variables to manage it easier
        $tainted_switches = $tainted_response['switches'];
        $tainted_fan = $tainted_response['fan'];
        $tainted_temperature = $tainted_response['temperature'];
        $tainted_keypad = $tainted_response['keypad'];

        //validate each piece of data one by one
        $cleaned_data[$index]['cleaned_switches'] = $telemetryDataValidator->validateSwitches($tainted_switches);
        $cleaned_data[$index]['cleaned_fan'] = $telemetryDataValidator->validateFan($tainted_fan);
        $cleaned_data[$index]['cleaned_temperature'] = $telemetryDataValidator->validateTemperature(
            $tainted_temperature
        );
        $cleaned_data[$index]['cleaned_keypad'] = $telemetryDataValidator->validateKeypad($tainted_keypad);
        $cleaned_data[$index]['unique_id'] = $tainted_response['unique_id'];
        $cleaned_data[$index]['src_number'] = $tainted_response['src_number'];
    }
    return $cleaned_data;
}

/**
 * The method saves the validated data into the database if it was not already saved there
 *
 * @param $app
 * @param $validated_data
 * @return mixed
 */
function saveTelemetryData($app, $validated_data)
{
    $databaseWrapper = $app->getContainer()->get('DatabaseWrapper');
    $db_conf = $app->getContainer()->get('settings');
    $database_connection_settings = $db_conf['pdo_settings'];
    //$database_connection_settings = $db_conf['pdo_test_settings'];
    $databaseWrapper->setDatabaseConnectionSettings($database_connection_settings);

    for ($i = 0; $i < count($validated_data); $i++) {
        $switch_01 = $validated_data[$i]['cleaned_switches'][0];
        $switch_02 = $validated_data[$i]['cleaned_switches'][1];
        $switch_03 = $validated_data[$i]['cleaned_switches'][2];
        $switch_04 = $validated_data[$i]['cleaned_switches'][3];
        $fan = $validated_data[$i]['cleaned_fan'];
        $temp = $validated_data[$i]['cleaned_temperature'];
        $keypad = $validated_data[$i]['cleaned_keypad'];
        $id = $validated_data[$i]['unique_id'];
        $number = $validated_data[$i]['src_number'];
        
        if ($databaseWrapper->isMessageUnique($id)) {
            $databaseWrapper->addMessage(
                $switch_01,
                $switch_02,
                $switch_03,
                $switch_04,
                $temp,
                $keypad,
                $fan,
                $id,
                $number
            );
        }
    }
    $dbMessages = $databaseWrapper->getMessages();
}

/**
 * The method downloads the messages stored in the databse to show them to the user later
 *
 * @param $app
 * @return mixed
 */
function downloadTelemetryDataDB($app)
{
    $databaseWrapper = $app->getContainer()->get('DatabaseWrapper');
    $db_conf = $app->getContainer()->get('settings');
    $database_connection_settings = $db_conf['pdo_settings'];
    //$database_connection_settings = $db_conf['pdo_test_settings'];
    $databaseWrapper->setDatabaseConnectionSettings($database_connection_settings);
    $dbMessages = $databaseWrapper->getMessages();
    return $dbMessages;
}
