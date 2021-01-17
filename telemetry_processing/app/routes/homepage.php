<?php
/**
 * homepage.php
 *
 * Main route/entry point of the application
 *
 * @author Adam Machowczyk, Dominik Michalski
 */

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

//establish the session
if (!isset($_SESSION)) {
    session_start();
}

/**
 * homepage is opened
 */
$app->get('/', function (Request $request, Response $response) {
    //render the homepage
    return $this->view->render(
        $response,
        'homepage.html.twig',
        [
            'css_path' => CSS_PATH,
            'method' => 'post',
            'action' => 'downloaddata',
            'page_title' => APP_NAME,
            'page_heading_1' => APP_NAME,
            'page_heading_2' => 'Press a button to view and manage telemetry data',
            'session' => $_SESSION
        ]
    );
})->setName('homepage');

/**
 * settings button in the navbar was pressed or form submitted
 */
$app->any(
    '/settings',
    function (Request $request, Response $response) use ($app) {
        //user must be logged in to access account settings
        if (!isset($_SESSION['userLoggedIn'])) {
            return $this->response->withRedirect('/telemetry_processing_public/sessionExpired');
        }

        //Establish connection with the database
        $DatabaseWrapper = $app->getContainer()->get('DatabaseWrapper');
        $db_conf = $app->getContainer()->get('settings');
        $database_connection_settings = $db_conf['pdo_settings'];
        $DatabaseWrapper->setDatabaseConnectionSettings($database_connection_settings);

        //set the default template
        $template = "settings/settings.html.twig";
        $errorMessage = "";

        //user submitted the change password form
        if (!empty($_POST) && isset($_SESSION['userId'])) {
            //fetch the form details
            $oldPassword = isset($_POST['oldPassword']) ? trim($_POST['oldPassword']) : null;
            $password = isset($_POST['newPassword']) ? trim($_POST['newPassword']) : null;
            $repeatedPassword = isset($_POST['newPasswordRepeated']) ? trim($_POST['newPasswordRepeated']) : null;
            $password_to_compare = $DatabaseWrapper->getUserPasswordById($_SESSION['userId']);

            //Form filled correctly
            if ($password && strlen($password) > 3
                && $repeatedPassword && $password == $repeatedPassword
                && $oldPassword && password_verify($oldPassword, $password_to_compare)
            ) {
                //reset the password
                $DatabaseWrapper->updateUserPassword($password, $_SESSION['userId']);
                $DatabaseWrapper->logActivity($_SESSION['userId'], "Password updated in settings", 1);

                //unset the user session so they can log in again using the new password
                $_SESSION['userId'] = "";
                session_unset();

                //change the template to a success message
                $template = 'authentication/reset_password_success.html.twig';
            } else {
                //Form filled incorrectly - generate error message

                if (!$password) {
                    $errorMessage = "Error: New Password field is required.";
                } elseif (strlen($password) < 4) {
                    $errorMessage = "Error: Password must be at least 4 characters in length.";
                } elseif (!$repeatedPassword) {
                    $errorMessage = "Error: Repeat New Password field is required.";
                } elseif ($password != $repeatedPassword) {
                    $errorMessage = "Error: Entered passwords were different. Please try again.";
                } elseif (!$oldPassword) {
                    $errorMessage = "Error: Current password field is required.";
                } elseif (!password_verify($oldPassword, $password_to_compare)) {
                    $errorMessage = "Error: Your current password does not match the entered password.";
                }
                
                $errorMessage .= "<br />";
            }
        } elseif (!empty($_POST)) {
            return $this->response->withRedirect('/telemetry_processing_public/logout');
        }

        //render the page
        return $this->view->render(
            $response,
            $template,
            [
                'css_path' => CSS_PATH,
                'landing_page' => LANDING_PAGE,
                'initial_input_box_value' => null,
                'page_title' => APP_NAME,
                'page_heading_1' => APP_NAME,
                'page_heading_2' => 'Account Settings',
                'method_1' => 'POST',
                'action_1' => 'settings',
                'method_2' => 'POST',
                'action_2' => 'deleteAccount',
                'userRole' => $_SESSION['userRole'],
                'errorMessage' => $errorMessage,
                'session' => $_SESSION
            ]
        );
    }
);
