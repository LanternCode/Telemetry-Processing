<?php
/**
 * Created by PhpStorm.
 * User: P2429405
 * Date: 25/11/2020
 * Time: 14:00
 */

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->any(
    '/generatetelemetrydata',
    function(Request $request, Response $response) use ($app)
    {
        //we do not receive any data back yet
        //$tainted_parameters = $request->getParsedBody();

        //the method to create new data
        $telemetry_data = generateTelemetryData();

        //append the data to an xml skeleton
        $telemetry_data_xml = appendToXml($telemetry_data);
        //$cleaned_parameters = cleanupParameters($app, $tainted_parameters);

        return $this->view->render($response,
            'display_results.html.twig',
            [
                'css_path' => CSS_PATH,
                'landing_page' => LANDING_PAGE,
                'initial_input_box_value' => null,
                'page_title' => APP_NAME,
                'page_heading_1' => APP_NAME,
                'page_heading_2' => 'Result',
                'switches' => $telemetry_data['switches'],
                'fan' => $telemetry_data['fan'],
                'heater' => $telemetry_data['heater'],
                'keypad' => $telemetry_data['keypad'],
                'tree' => $telemetry_data_xml
            ]);
    }
);

function cleanupParameters($app, array $tainted_parameters): array
{
    $cleaned_parameters = [];
    $validator = $app->getContainer()->get('validator');

    $tainted_calculation_type = $tainted_parameters['conversion'];
    $tainted_temperature = $tainted_parameters['temperature'];
    $tainted_windspeed = $tainted_parameters['windspeed'];

    $cleaned_parameters['cleaned_calculation'] = $validator->validateCalculationType($tainted_calculation_type);
    $cleaned_parameters['cleaned_temperature'] = $validator->validateTemperature($tainted_temperature, $cleaned_parameters['cleaned_calculation']);
    $cleaned_parameters['cleaned_windspeed'] = $validator->validateWindspeed($tainted_windspeed);
    return $cleaned_parameters;
}


function generateTelemetryData()
{
    $telemetry_data = [];
    $number_of_switches = 4;

    //switches
    for($i = 0; $i < $number_of_switches; $i++)
    {
        $random_number = rand(1, 20);
        $telemetry_data['switches'][$i] = $random_number > 10 ? 1 : 0;
    }

    //fan
    $random_number = rand(1, 20);
    $telemetry_data['fan'] = $random_number > 10 ? "forward" : "reverse";

    //heater - generate temperature between -26 and 26 degree celsius
    $random_temperature_number = (rand(0, 52) - 26) . ',' . rand(1, 99);
    $telemetry_data['heater'] = $random_temperature_number;

    //keypad
    $random_keypad_number = rand(0, 9); //keypad holds numbers from 0-9
    $telemetry_data['keypad'] = $random_keypad_number;

    return $telemetry_data;
}

function appendToXml($telemetry_data, $format = 0)
{
    $xmlTree = "";
    if($format == 1) {
        $xmlTree = "<telemetrydata>";

        $xmlTree .= "<telemetrydata_switches>";
        $xmlTree .= "<telemetrydata_switch>" . $telemetry_data['switches'][0] . "</telemetrydata_switch>";
        $xmlTree .= "<telemetrydata_switch>" . $telemetry_data['switches'][1] . "</telemetrydata_switch>";
        $xmlTree .= "<telemetrydata_switch>" . $telemetry_data['switches'][2] . "</telemetrydata_switch>";
        $xmlTree .= "<telemetrydata_switch>" . $telemetry_data['switches'][3] . "</telemetrydata_switch>";
        $xmlTree .= "</telemetrydata_switches>";

        $xmlTree .= "<telemetrydata_fan>" . $telemetry_data['fan'] . "</telemetrydata_fan>";
        $xmlTree .= "<telemetrydata_temperature>" . $telemetry_data['heater'] . "</telemetydata_temperature>";
        $xmlTree .= "<telemetrydata_keypad>" . $telemetry_data['keypad'] . "</telemetrydata_keypad>";

        $xmlTree .= "</telemetrydata>";
    }
    else {
        $xmlTree = "<t>";

        $xmlTree .= "<ss>";
        $xmlTree .= "<s>" . $telemetry_data['switches'][0] . "</s>";
        $xmlTree .= "<s>" . $telemetry_data['switches'][1] . "</s>";
        $xmlTree .= "<s>" . $telemetry_data['switches'][2] . "</s>";
        $xmlTree .= "<s>" . $telemetry_data['switches'][3] . "</s>";
        $xmlTree .= "</ss>";

        $xmlTree .= "<f>" . $telemetry_data['fan'] . "</f>";
        $xmlTree .= "<tp>" . $telemetry_data['heater'] . "</tp>";
        $xmlTree .= "<tk>" . $telemetry_data['keypad'] . "</tk>";

        $xmlTree .= "</t>";
    }

    return $xmlTree;
}