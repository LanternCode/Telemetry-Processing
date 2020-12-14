<?php
/**
 * Created by PhpStorm.
 * User: cfi
 * Date: 20/11/15
 * Time: 14:01
 */

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->any(
    '/validatetelemetrydata',
    function(Request $request, Response $response) use ($app)
    {
        //$tainted_data = $request->getParsedBody();
        $tainted_data = [
          1  => [
              'switches' => [
                  0, 0, 1, 0
              ],
              'fan' => 'forward',
              'temperature' => 19.25,
              'keypad' => 6,
          ],
          2 => [
              'switches' => [
                  0, 1, 0, 1
              ],
              'fan' => 'reverse',
              'temperature' => -8.5,
              'keypad' => 0,
          ],
          3 => [
              'switches' => [
                  0, 0, 0, 5
              ],
              'fan' => 'hello',
              'temperature' => 1900,
              'keypad' => -20,
          ]
        ];

        $cleaned_data = cleanupTelemetryData($app, $tainted_data);

        return $this->view->render($response,
            'validation_test.html.twig',
            [
                'css_path' => CSS_PATH,
                'landing_page' => LANDING_PAGE,
                'initial_input_box_value' => null,
                'page_title' => APP_NAME,
                'page_heading_1' => APP_NAME,
                'page_heading_2' => 'Result',

                'telemetry' => $cleaned_data,
            ]);
    });

    function cleanupTelemetryData($app, array $tainted_data): array
    {
        $cleaned_data = [];
        $telemetryDataValidator = $app->getContainer()->get('telemetryDataValidator');

        foreach($tainted_data as $index=>$tainted_response)
        {
            $tainted_switches = $tainted_response['switches'];
            $tainted_fan = $tainted_response['fan'];
            $tainted_temperature = $tainted_response['temperature'];
            $tainted_keypad = $tainted_response['keypad'];

            $cleaned_data[$index]['cleaned_switches'] = $telemetryDataValidator->validateSwitches($tainted_switches);
            $cleaned_data[$index]['cleaned_fan'] = $telemetryDataValidator->validateFan($tainted_fan);
            $cleaned_data[$index]['cleaned_temperature'] = $telemetryDataValidator->validateTemperature($tainted_temperature);
            $cleaned_data[$index]['cleaned_keypad'] = $telemetryDataValidator->validateKeypad($tainted_keypad);
        }

        return $cleaned_data;
    }

