<?php

/**
 * Created by PhpStorm.
 * User: slim
 * Date: 13/10/17
 * Time: 12:33
 */

ini_set('display_errors', 'On');
ini_set('html_errors', 'On');

define('DIRSEP', DIRECTORY_SEPARATOR);

$app_url = dirname($_SERVER['SCRIPT_NAME']);
$css_path = $app_url . '/css/styles.css';
$wsdl = 'https://m2mconnect.ee.co.uk/orange-soap/services/MessageServiceByCountry?wsdl';
define('LANDING_PAGE', $_SERVER['SCRIPT_NAME']);
define('CSS_PATH', $css_path);
define('APP_NAME', 'Telemetry Processing');
define('WSDL', $wsdl);

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
            ]
        ],
        'pdo_settings' => [
            'rdbms' => 'mysql',
            'host' => 'mysql.tech.dmu.ac.uk',
            'db_name' => 'p2429405db',
            'port' => '3306',
            'user_name' => 'p2429405_web',
            'user_password' => 'poulT=10',
            'charset' => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'options' => [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => true,
            ],
        ],
        'm2m_credentials' => [
            'login' => '20_2429405',
            'password' => 'SmoothSail101',
            'count' => "100",
            'deviceMsisdn' => "+447817814149",
            'countryCode' => "44"
        ],
    ]
];

return $settings;
