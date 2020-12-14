<?php
/**
 * homepageform.php
 *
 *
 */

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->get('/', function(Request $request, Response $response)
{
    return $this->view->render($response,
        'homepageform.html.twig',
        [
            'css_path' => CSS_PATH,
            'method' => 'post',

            'action_2' => 'generatetelemetrydata',
            'page_title' => APP_NAME,
            'page_heading_1' => APP_NAME,
            'page_heading_2' => 'Type in or generate random parameters of circuit board to be sent to the M2M server'
        ]);
})->setName('homepageform');