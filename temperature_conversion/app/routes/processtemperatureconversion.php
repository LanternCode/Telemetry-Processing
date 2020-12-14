<?php
/**
 * Created by PhpStorm.
 * User: cfi
 * Date: 20/11/15
 * Time: 14:01
 */

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->post(
    '/processtemperatureconversion',
    function(Request $request, Response $response) use ($app)
    {
        $tainted_parameters = $request->getParsedBody();

        $cleaned_parameters = cleanupParameters($app, $tainted_parameters);
        $calculation_result = performCalculation($app, $cleaned_parameters);
//        $conversion_calculation_text = CONV_CALC[$cleaned_parameters['cleaned_calculation']];

        return $this->view->render($response,
            'display_result.html.twig',
            [
                'css_path' => CSS_PATH,
                'landing_page' => LANDING_PAGE,
                'initial_input_box_value' => null,
                'page_title' => APP_NAME,
                'page_heading_1' => APP_NAME,
                'page_heading_2' => 'Result',

                'temperature' => $cleaned_parameters['cleaned_temperature'],
                'calculation' => $cleaned_parameters['cleaned_calculation'],
                'windspped' => $cleaned_parameters['cleaned_windspeed'],
                'conversion_type_text' => CONV_CALC[$cleaned_parameters['cleaned_calculation']],
                'result' => $calculation_result,
            ]);
    });

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


function performCalculation($app, $cleaned_parameters)
{
    $tempconv_model = $app->getContainer()->get('tempConvModel');

    $calculation_type = $cleaned_parameters['cleaned_calculation'];

    $tempconv_model->setConversionParameters($cleaned_parameters);

    $tempconv_model->performTemperatureConversion();

    $temperature_conversion_result = $tempconv_model->getResult();

    if ($temperature_conversion_result === false)
    {
        $temperature_conversion_result = 'not available';
    }

    return $temperature_conversion_result;
}

