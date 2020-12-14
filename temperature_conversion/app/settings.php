<?php
/**
 * Created by PhpStorm.
 * User: slim
 * Date: 13/10/17
 * Time: 12:33
 */

ini_set('display_errors', 'On');
ini_set('html_errors', 'On');
ini_set('xdebug.trace_output_name', 'temp_conversion.%t');
ini_set('xdebug.trace_format', '1');

define('DIRSEP', DIRECTORY_SEPARATOR);

$app_url = dirname($_SERVER['SCRIPT_NAME']);
$css_path = $app_url . '/css/styles.css';

define('CSS_PATH', $css_path);
define('APP_NAME', 'Temperature Calculations');
define('LANDING_PAGE', $_SERVER['SCRIPT_NAME']);
define('LOWEST_CENTIGRADE_TEMPERATURE', -273.15 );
define('LOWEST_FAHRENHEIT_TEMPERATURE', -459.67 );

$conversion_calculation = [
    'null' => 'Select:',
    'ctof' => 'Centigrade to Fahrenheit',
    'ftoc' => 'Fahrenheit to Centigrade',
    'cchill' => 'Calculate Windchill in Centigrade',
    'fchill' => 'Calculate Windchill in Fahrenheit',
];
define ('CONV_CALC', $conversion_calculation);

$wsdl = 'https://webservices.daehosting.com/services/TemperatureConversions.wso?WSDL';
define ('WSDL', $wsdl);

$settings = [
    "settings" => [
        'displayErrorDetails' => true,
        'addContentLengthHeader' => false,
        'mode' => 'development',
        'debug' => true,
        'class_path' => __DIR__ . '/src/',
        'view' => [
            'template_path' => __DIR__ . '/templates/',
            'twig' => [
                'cache' => false,
                'auto_reload' => true,
            ]],
    ],
];

return $settings;
