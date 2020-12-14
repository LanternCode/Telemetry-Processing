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
    '/downloaddata',
    function(Request $request, Response $response) use ($app)
    {

        return $this->view->render($response,
            'display_download_results.html.twig',
            [
                'css_path' => CSS_PATH,
                'landing_page' => LANDING_PAGE,
                'initial_input_box_value' => null,
                'page_title' => APP_NAME,
                'page_heading_1' => APP_NAME,
                'page_heading_2' => 'Downloading results',

            ]);
    }
);



