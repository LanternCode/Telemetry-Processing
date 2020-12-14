<?php
/**
 * homepageform.php
 *
 *
 */

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->get('/home', function(Request $request, Response $response)
{
    return $this->view->render($response,
        'homepage.html.twig',
        [
            'css_path' => CSS_PATH,
            'method' => 'post',
            'action' => 'downloaddata',
            'page_title' => APP_NAME,
            'page_heading_1' => APP_NAME,
            'page_heading_2' => 'Press a button to view and manage telemetry data'
        ]);
})->setName('homepage');