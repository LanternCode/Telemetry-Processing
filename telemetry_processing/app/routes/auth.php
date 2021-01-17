<?php
/**
 * auth.php
 *
 * Set of routes used to perform registration, logging in,
 * resetting password and logging out
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
 * login form was open or submitted
 */
$app->any(
    '/login',
    function (Request $request, Response $response) use ($app) {
        //if the user has already logged in, redirect them
        if (isset($_SESSION['userLoggedIn'])) {
            return $this->response->withRedirect('/p2429405/telemetry_processing_public');
        }

        //Establish connection with the database
        $DatabaseWrapper = $app->getContainer()->get('DatabaseWrapper');
        $db_conf = $app->getContainer()->get('settings');
        $database_connection_settings = $db_conf['pdo_settings'];
        $DatabaseWrapper->setDatabaseConnectionSettings($database_connection_settings);

        $data = array(
            'invalid_credentials' => 0,
            'account_barred' => 0,
            'email' => isset($_POST['account--signin--email']) ? trim($_POST['account--signin--email']) : null,
            'password' => isset($_POST['account--signin--password']) ? trim($_POST['account--signin--password']) : null,
            'template' => 'authentication/login.html.twig'
        );

        if (isset($data['email']) && $data['email'] && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $user_data = $DatabaseWrapper->getUserData($data['email']);
            $password_to_compare = isset($user_data['password']) ? $user_data['password'] : 0;

            if ($password_to_compare && password_verify($data['password'], $password_to_compare)) {
                if ($user_data['barred'] == false) {
                    $_SESSION['userLoggedIn'] = 1;
                    $_SESSION['userId'] = $user_data['id'];
                    $_SESSION['userRole'] = $user_data['role'];
                    $_SESSION['userEmail'] = $user_data['email'];
                    $DatabaseWrapper->logActivity($_SESSION['userId'], "Logged in", 1);
                    return header('refresh:0');
                } else {
                    $data['account_barred'] = 1;
                    $DatabaseWrapper->logActivity($user_data['id'], "Barred account login attempted", 0);
                }
            } else {
                $data['invalid_credentials'] = 1;
            }
        } elseif (!empty($_POST)) {
            $data['invalid_credentials'] = 1;
        }

        //render the page
        return $this->view->render(
            $response,
            $data['template'],
            [
                'css_path' => CSS_PATH,
                'landing_page' => LANDING_PAGE,
                'initial_input_box_value' => null,
                'page_title' => APP_NAME,
                'page_heading_1' => APP_NAME,
                'page_heading_2' => 'Downloading results',
                'invalid_credentials' => $data['invalid_credentials'],
                'account_barred' => $data['account_barred'],
                'method' => 'POST',
                'action_1' => 'login',
                'action_2' => 'register',
                'action_3' => 'forgot_password',
                'session' => $_SESSION
            ]
        );
    }
);

/**
 * register form was opened
 */
$app->get(
    '/register',
    function (Request $request, Response $response) use ($app) {

        //if the user has already logged in, redirect them
        if (isset($_SESSION['userLoggedIn'])) {
            return $this->response->withRedirect('/telemetry_processing_public');
        }

        //render the page
        return $this->view->render(
            $response,
            'authentication/register.html.twig',
            [
                'css_path' => CSS_PATH,
                'landing_page' => 'login',
                'initial_input_box_value' => null,
                'page_title' => APP_NAME,
                'page_heading_1' => APP_NAME,
                'page_heading_2' => 'Downloading results',

                'setEmail' => '',
                'emailFormatInvalid' => '',
                'emailTooLong' => '',
                'emailRepeated' => '',
                'setPassword' => '',
                'passwordTooShort' => '',
                'passwordTooLong' => '',
                'setPasswordRepetition' => '',
                'passwordRepetitionNotMatching' => '',
                'session' => $_SESSION
            ]
        );
    }
);

/**
 * register form was submitted
 */
$app->post(
    '/register',
    function (Request $request, Response $response) use ($app) {

        //Establish connection with the database
        $DatabaseWrapper = $app->getContainer()->get('DatabaseWrapper');
        $db_conf = $app->getContainer()->get('settings');
        $database_connection_settings = $db_conf['pdo_settings'];
        $DatabaseWrapper->setDatabaseConnectionSettings($database_connection_settings);

        $data = [];

        //Fetch data send by the user
        $email          = isset($_POST['register--email']) ? trim($_POST['register--email']) : 0;
        $password       = isset($_POST['register--password']) ? trim($_POST['register--password']) : 0;
        $password_rep   = isset($_POST['register--password__repetition']) ?
            trim($_POST['register--password__repetition']) : 0;

        //Validate all data sent by the user
        $data['emailFormatInvalid'] = filter_var($email, FILTER_VALIDATE_EMAIL) ? 0 : "Enter your email!";
        $data['emailTooLong']       = strlen($email) > 50 ? "Email can't be longer than 50 characters." : 0;
        $data['emailRepeated']      = $DatabaseWrapper->isEmailUnique($email) ? 0 : "An 
        account with this email already exists.";

        $data['passwordTooShort']   = strlen($password) > 3 ? 0 : "Password must be at least 4-characters long!";
        $data['passwordTooLong']    = strlen($password) > 25 ? "Password can't be longer than 25 characters." : 0;

        $data['passwordRepetitionNotMatching'] = $password == $password_rep ? 0 : "Entered passwords aren't the same!";

        //Check if all the data was validated
        if ($data['emailFormatInvalid'] || $data['emailTooLong'] || $data['emailRepeated'] ||
            $data['passwordTooShort'] || $data['passwordTooLong'] ||
            $data['passwordRepetitionNotMatching']) {
            //Wrap error messages is HTML tags to style error messages in CSS later
            $dataKeys = array_keys($data);
            for ($i = 0; $i < count($dataKeys); ++$i) {
                if (!$data[$dataKeys[$i]]) {
                    $data[$dataKeys[$i]] = "";
                } else {
                    $addInFront = "<h4 class='registration--error'>";
                    $addInFront .= $data[$dataKeys[$i]];
                    $addInFront .= "</h4>";
                    $data[$dataKeys[$i]] = $addInFront;
                }
            }

            //Pre-set values earlier submitted by the user in the form
            $data['setEmail']               = ($data['emailFormatInvalid'] || $data['emailTooLong']
                || $data['emailRepeated'] ) ? "" : $email;
            $data['setPassword']            = ($data['passwordTooShort'] || $data['passwordTooLong']) ? "" : $password;
            $data['setPasswordRepetition']  = ($data['setPassword'] &&
                !$data['passwordRepetitionNotMatching'] ) ? $password_rep : "";
            $data['template'] = 'authentication/register.html.twig';

            //render the page
            return $this->view->render(
                $response,
                $data['template'],
                [
                    'css_path' => CSS_PATH,
                    'landing_page' => LANDING_PAGE,
                    'initial_input_box_value' => null,
                    'page_title' => APP_NAME,
                    'page_heading_1' => APP_NAME,
                    'page_heading_2' => 'Downloading results',

                    'emailFormatInvalid' => $data['emailFormatInvalid'],
                    'emailTooLong' => $data['emailTooLong'],
                    'emailRepeated' => $data['emailRepeated'],
                    'passwordTooShort' => $data['passwordTooShort'],
                    'passwordTooLong' => $data['passwordTooLong'],
                    'passwordRepetitionNotMatching' => $data['passwordRepetitionNotMatching'],

                    'setEmail' => $data['setEmail'],
                    'setPassword' => $data['setPassword'],
                    'setPasswordRepetition' => $data['setPasswordRepetition'],
                    'session' => $_SESSION
                ]
            );
        } else {
            //All fields pass validation
            $data['template'] = 'authentication/login.html.twig';
            $data['userHasRegistered'] = 1;
            $password = password_hash($password, PASSWORD_BCRYPT);
            $DatabaseWrapper->registerNewUser($email, $password);

            //render the page
            return $this->view->render(
                $response,
                $data['template'],
                [
                    'css_path' => CSS_PATH,
                    'landing_page' => LANDING_PAGE,
                    'initial_input_box_value' => null,
                    'page_title' => APP_NAME,
                    'page_heading_1' => APP_NAME,
                    'page_heading_2' => 'Downloading results',

                    'userHasRegistered' => $data['userHasRegistered'],
                    'session' => $_SESSION
                ]
            );
        }
    }
);

/**
 * logout button was pressed
 */
$app->any(
    '/logout',
    function (Request $request, Response $response) use ($app) {
        //delete the session
        session_unset();
        session_destroy();

        //redirect to homepage after successful logout
        return $this->response->withRedirect('/p2429405/telemetry_processing_public');
    }
);

/**
 * session of the user has expired
 */
$app->any(
    '/sessionExpired',
    function (Request $request, Response $response) use ($app) {
        $data = array(
            'sessionExpired' => 1
        );

        //delete the session
        session_unset();
        session_destroy();

        //render the page
        return $this->view->render(
            $response,
            'homepage.html.twig',
            [
                'css_path' => CSS_PATH,
                'landing_page' => LANDING_PAGE,
                'initial_input_box_value' => null,
                'page_title' => APP_NAME,
                'page_heading_1' => APP_NAME,
                'page_heading_2' => 'Downloading results',
                'sessionExpires' => $data['sessionExpired'],
                'session' => $_SESSION
            ]
        );
    }
);

/**
 * forgot password form was opened or submitted
 */
$app->any(
    '/forgot_password',
    function (Request $request, Response $response) use ($app) {
        //if the user has already logged in, redirect them
        if (isset($_SESSION['userLoggedIn'])) {
            return $this->response->withRedirect('/p2429405/telemetry_processing_public');
        }

        //Establish connection with the database
        $DatabaseWrapper = $app->getContainer()->get('DatabaseWrapper');
        $db_conf = $app->getContainer()->get('settings');
        $database_connection_settings = $db_conf['pdo_settings'];
        $DatabaseWrapper->setDatabaseConnectionSettings($database_connection_settings);

        //fetch data submitted by the user
        $data = array(
            'emailEntered' => ((isset($_POST['email']) ? trim($_POST['email']) : "") == "" ? 0 : 1),
            'email' => isset($_POST['email']) &&
            filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) ? trim($_POST['email']) : 0,
            'formSubmitted' => $_POST ? 1 : 0,
            'actionNotification' => '',
            'resetLink' => ''
        );

        //validate the data
        if ($data['emailEntered']) {
            if ($data['email']) {
                if (!$DatabaseWrapper->isEmailUnique($data['email'])) {
                    if ($resetKey = $DatabaseWrapper->insertPasswordUpdateLink($data['email'])) {
                        $DatabaseWrapper->sendPasswordChangeEmail($data['email'], $resetKey);
                        //$data['resetLink'] = 'http://php.tech.dmu.ac.uk:6789/p2429405/telemetry_processing_public/
                        //reset_password?qs=' . $resetKey;
                        $data['actionNotification'] = "<span class='universal--successMessage'>
                        Success! Check your inbox to reset your password.</span>";
                    } else {
                        $data['actionNotification'] = "Error: Something went wrong. Please try again.";
                    }
                } else {
                    $data['actionNotification'] = "Error: There is no account bound to this email.
                    <br />If you think this is a mistake, contact us immediately.";
                }
            } else {
                $data['actionNotification'] = "Error: Please enter a correct email address.";
            }
        } elseif ($data['formSubmitted']) {
            $data['actionNotification'] = "Error: In order to reset your password,
             you have to submit your email address first.";
        }

        //render the page
        return $this->view->render(
            $response,
            'authentication/forgot_password.html.twig',
            [
                'css_path' => CSS_PATH,
                'landing_page' => 'login',
                'initial_input_box_value' => null,
                'page_title' => APP_NAME,
                'page_heading_1' => APP_NAME,
                'page_heading_2' => 'Downloading results',
                'action_1' => 'forgot_password',
                'method' => 'POST',
                'action_notification' => $data['actionNotification'],
                'reset_link' => $data['resetLink'],
                'session' => $_SESSION
            ]
        );
    }
);

/**
 * reset password form was opened or submitted
 */
$app->any(
    '/reset_password',
    function (Request $request, Response $response) use ($app) {

        //if the user has already logged in, redirect them
        if (isset($_SESSION['userLoggedIn'])) {
            return $this->response->withRedirect('/p2429405/telemetry_processing_public');
        }

        //Establish connection with the database
        $DatabaseWrapper = $app->getContainer()->get('DatabaseWrapper');
        $db_conf = $app->getContainer()->get('settings');
        $database_connection_settings = $db_conf['pdo_settings'];
        $DatabaseWrapper->setDatabaseConnectionSettings($database_connection_settings);

        $data = array(
            'key' => isset($_GET['qs']) ?  trim($_GET['qs']) : 0,
            'formSubmitted' => $_POST ? 1 : 0,
            'template' => 'authentication/reset_password.html.twig'
        );

        //form was submitted
        if (isset($_POST) && isset($_SESSION['user'])) {
            $password = isset($_POST['newPassword']) ? trim($_POST['newPassword']) : null;
            $repeatedPassword = isset($_POST['newPasswordRepeated']) ? trim($_POST['newPasswordRepeated']) : null;

            //Form filled correctly
            if ($password && strlen($password) > 3 && $repeatedPassword && $password == $repeatedPassword) {
                //reset the password
                $DatabaseWrapper->updateUserPassword($password, $_SESSION['user']);
                $DatabaseWrapper->logActivity($_SESSION['user'], "Password was reset", 1);

                //unset the user session so they can log in again using the new password
                $_SESSION['user'] = "";
                session_unset();

                //change the template to a success message
                $data['template'] = 'authentication/reset_password_success.html.twig';
            } elseif ($data['formSubmitted']) {
                //Form filled incorrectly - generate error message
                $data['errorMessage'] = "";

                if (!$password) {
                    $data['errorMessage'] = "Error: New Password field is required.";
                } elseif (strlen($password) < 4) {
                    $data['errorMessage'] = "Error: Password must be at least 4 characters in length.";
                } elseif (!$repeatedPassword) {
                    $data['errorMessage'] = "Error: Repeat New Password field is required.";
                } elseif ($password != $repeatedPassword) {
                    $data['errorMessage'] = "Error: Entered passwords were different. Please try again.";
                }
                
                $data['errorMessage'] .= "<br />";
            }
        } elseif (strlen($data['key']) == 255 && !isset($_SESSION['user'])) {
            //form opened from the link received by email

            //check if the reset key exists, if so, find the user who it belongs to
            $userId = $DatabaseWrapper->validatePasswordResetString($data['key']);

            if ($userId) {
                $_SESSION['user'] = $userId;
            } else {
                return $this->response->withRedirect('/p2429405/telemetry_processing_public');
            }
        } else {
            //no key was given or key was invalid or user data missing
            return $this->response->withRedirect('/p2429405/telemetry_processing_public');
        }

        //render the page
        return $this->view->render(
            $response,
            $data['template'],
            [
                'css_path' => CSS_PATH,
                'landing_page' => LANDING_PAGE,
                'initial_input_box_value' => null,
                'page_title' => APP_NAME,
                'page_heading_1' => APP_NAME,
                'page_heading_2' => 'Downloading results',
                'method' => 'POST',
                'action_1' => 'reset_password',

                'errorMessage' => isset($data['errorMessage']) ? $data['errorMessage'] : null,
                'session' => $_SESSION
            ]
        );
    }
);
