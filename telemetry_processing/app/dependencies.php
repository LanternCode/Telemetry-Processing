<?php
// Register component on container
$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig(
        $container['settings']['view']['template_path'],
        $container['settings']['view']['twig'],
        [
            'debug' => true // This line should enable debug mode
        ]
    );

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));

    return $view;
};

$container['DatabaseWrapper'] = function ($container) {
    $dbWrapper = new \telemetryProcessing\DatabaseWrapper();
    return $dbWrapper;
};

$container['telemetryDataValidator'] = function ($container) {
    $telemetryDataValidator = new \telemetryProcessing\TelemetryDataValidator();
    return $telemetryDataValidator;
};

$container['telemetryDataDownloader'] = function ($container) {
    $telemetryDataDownloader = new \telemetryProcessing\TelemetryDataDownloader();
    return $telemetryDataDownloader;
};

$container['telemetryDataParser'] = function ($container) {
    $telemetryDataParser = new \telemetryProcessing\TelemetryDataParser();
    return $telemetryDataParser;
};
