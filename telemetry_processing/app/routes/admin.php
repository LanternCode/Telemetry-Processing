<?php
/**
 * admin.php
 *
 * Set of routes for admin activities
 *
 * @author Adam Machowczyk, Dominik Michalski
 *
 */

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

//establish the session
if (!isset($_SESSION)) {
    session_start();
}

/**
 * Admin dashboard was opened
 */
$app->any(
    '/admin',
    function (Request $request, Response $response) use ($app) {
        //user must be logged in and be an admin to access admin dashboard
        if (!isset($_SESSION['userLoggedIn']) || !isset($_SESSION['userRole']) || $_SESSION['userRole'] != "admin") {
            return $this->response->withRedirect('/telemetry_processing_public/sessionExpired');
        }

        //Establish connection with the database
        $DatabaseWrapper = $app->getContainer()->get('DatabaseWrapper');
        $db_conf = $app->getContainer()->get('settings');
        $database_connection_settings = $db_conf['pdo_settings'];
        $DatabaseWrapper->setDatabaseConnectionSettings($database_connection_settings);

        //render the page
        return $this->view->render(
            $response,
            "settings/adminDashboard.html.twig",
            [
                'css_path' => CSS_PATH,
                'landing_page' => LANDING_PAGE,
                'initial_input_box_value' => null,
                'page_title' => APP_NAME,
                'page_heading_1' => APP_NAME,
                'page_heading_2' => 'Account Settings',
                'session' => $_SESSION
            ]
        );
    }
);

/**
 * user management button in admin dashboard was pressed
 */
$app->any(
    '/admin/userManagement',
    function (Request $request, Response $response) use ($app) {
        //user must be logged in and be an admin to access user management
        if (!isset($_SESSION['userLoggedIn']) || !isset($_SESSION['userRole']) || $_SESSION['userRole'] != "admin") {
            return $this->response->withRedirect('/telemetry_processing_public/sessionExpired');
        }

        //Establish connection with the database
        $DatabaseWrapper = $app->getContainer()->get('DatabaseWrapper');
        $db_conf = $app->getContainer()->get('settings');
        $database_connection_settings = $db_conf['pdo_settings'];
        $DatabaseWrapper->setDatabaseConnectionSettings($database_connection_settings);

        //get the list of users to display
        $userList = $DatabaseWrapper->GetAllUsers();

        //render the page
        return $this->view->render(
            $response,
            "settings/userManagement.html.twig",
            [
                'css_path' => CSS_PATH,
                'landing_page' => LANDING_PAGE,
                'initial_input_box_value' => null,
                'page_title' => APP_NAME,
                'page_heading_1' => APP_NAME,
                'page_heading_2' => 'Account Settings',
                'users' => $userList,
                'adminId' => $_SESSION['userId'],
                'session' => $_SESSION
            ]
        );
    }
);

/**
 * bar account in user management was pressed
 */
$app->get('/admin/barAccount', function (Request $request, Response $response) use ($app) {
    //user must be logged in and be admin to be able to bar account
    if (!isset($_SESSION['userLoggedIn']) || !isset($_SESSION['userRole']) || $_SESSION['userRole'] !== "admin") {
        return $this->response->withRedirect('/telemetry_processing_public/sessionExpired');
    }

    //Establish connection with the database
    $DatabaseWrapper = $app->getContainer()->get('DatabaseWrapper');
    $db_conf = $app->getContainer()->get('settings');
    $database_connection_settings = $db_conf['pdo_settings'];
    $DatabaseWrapper->setDatabaseConnectionSettings($database_connection_settings);

    //get the user id from the url string
    $userIdToBar = isset($_GET['accId']) ? trim($_GET['accId']) : 0;
    $barSuccessful = true;

    //bar account
    if ($userIdToBar > 0 && $userIdToBar != $_SESSION['userId']) {
        $DatabaseWrapper->barAccount($userIdToBar);
    } else {
        $barSuccessful = false;
    }

    $DatabaseWrapper->logActivity($_SESSION['userId'], "Barred User: " . $userIdToBar, $barSuccessful);

    //render the page
    return $this->view->render(
        $response,
        'settings/accountBarred.html.twig',
        [
            'css_path' => CSS_PATH,
            'method' => 'post',
            'action' => '',
            'page_title' => APP_NAME,
            'page_heading_1' => APP_NAME,
            'page_heading_2' => '',
            'bar_status' => $barSuccessful,
            'session' => $_SESSION
        ]
    );
});

/**
 * free account button in user management was pressed
 */
$app->get('/admin/freeAccount', function (Request $request, Response $response) use ($app) {
    //user must be logged in and be admin to be able to free account
    if (!isset($_SESSION['userLoggedIn']) || !isset($_SESSION['userRole']) || $_SESSION['userRole'] !== "admin") {
        return $this->response->withRedirect('/telemetry_processing_public/sessionExpired');
    }

    //Establish connection with the database
    $DatabaseWrapper = $app->getContainer()->get('DatabaseWrapper');
    $db_conf = $app->getContainer()->get('settings');
    $database_connection_settings = $db_conf['pdo_settings'];
    $DatabaseWrapper->setDatabaseConnectionSettings($database_connection_settings);

    //get the user id from the url string
    $userIdToFree = isset($_GET['accId']) ? trim($_GET['accId']) : 0;
    $freeSuccessful = true;

    //free account
    if ($userIdToFree > 0) {
        $DatabaseWrapper->freeAccount($userIdToFree);
    } else {
        $freeSuccessful = false;
    }

    $DatabaseWrapper->logActivity($_SESSION['userId'], "Free User: " . $userIdToFree, $freeSuccessful);

    //render the page
    return $this->view->render(
        $response,
        'settings/accountFreed.html.twig',
        [
            'css_path' => CSS_PATH,
            'method' => 'post',
            'action' => '',
            'page_title' => APP_NAME,
            'page_heading_1' => APP_NAME,
            'page_heading_2' => '',
            'free_status' => $freeSuccessful,
            'session' => $_SESSION
        ]
    );
});

/**
 * delete account operation was performed in settings
 */
$app->post('/deleteAccount', function (Request $request, Response $response) use ($app) {
    //user must be logged in to be able to delete account
    if (!isset($_SESSION['userLoggedIn'])) {
        return $this->response->withRedirect('/telemetry_processing_public/sessionExpired');
    }

    //Establish connection with the database
    $DatabaseWrapper = $app->getContainer()->get('DatabaseWrapper');
    $db_conf = $app->getContainer()->get('settings');
    $database_connection_settings = $db_conf['pdo_settings'];
    $DatabaseWrapper->setDatabaseConnectionSettings($database_connection_settings);

    //get the user id from the session
    $userIdToDelete = isset($_SESSION['userId']) ? trim($_SESSION['userId']) : 0;
    $deleteSuccessful = true;

    //make sure the user checked the checkbox
    $checkboxChecked = isset($_POST['deleteAccount']);

    //delete account
    if ($userIdToDelete > 0 && $checkboxChecked) {
        $DatabaseWrapper->deleteAccount($userIdToDelete);
        $_SESSION['userId'] = null;
        session_unset();
        session_destroy();
    } else {
        $deleteSuccessful = false;
    }

    $DatabaseWrapper->logActivity($userIdToDelete, "Delete Account", $deleteSuccessful);

    //render the page
    return $this->view->render(
        $response,
        'settings/accountDeleted.html.twig',
        [
            'css_path' => CSS_PATH,
            'method' => 'post',
            'action' => '',
            'page_title' => APP_NAME,
            'page_heading_1' => APP_NAME,
            'page_heading_2' => '',
            'delete_status' => $deleteSuccessful,
            'session' => $_SESSION
        ]
    );
});

/**
 * promote to admin button was pressed in user management
 */
$app->get('/admin/promote', function (Request $request, Response $response) use ($app) {
    //user must be logged in and be admin to be able to promote someone
    if (!isset($_SESSION['userLoggedIn']) || !isset($_SESSION['userRole']) || $_SESSION['userRole'] !== "admin") {
        return $this->response->withRedirect('/telemetry_processing_public/sessionExpired');
    }

    //Establish connection with the database
    $DatabaseWrapper = $app->getContainer()->get('DatabaseWrapper');
    $db_conf = $app->getContainer()->get('settings');
    $database_connection_settings = $db_conf['pdo_settings'];
    $DatabaseWrapper->setDatabaseConnectionSettings($database_connection_settings);

    //get the user id from the url string
    $userIdToPromote = isset($_GET['accId']) ? trim($_GET['accId']) : 0;
    $promoteSuccessful = true;

    //promote account
    if ($userIdToPromote > 0) {
        $DatabaseWrapper->promoteAccount($userIdToPromote);
    } else {
        $promoteSuccessful = false;
    }

    $DatabaseWrapper->logActivity($_SESSION['userId'], "Promoted to admin: " . $userIdToPromote, $promoteSuccessful);

    //render the page
    return $this->view->render(
        $response,
        'settings/accountPromoted.html.twig',
        [
            'css_path' => CSS_PATH,
            'method' => 'post',
            'action' => '',
            'page_title' => APP_NAME,
            'page_heading_1' => APP_NAME,
            'page_heading_2' => '',
            'promote_status' => $promoteSuccessful,
            'session' => $_SESSION
        ]
    );
});

/**
 * demote admin to user was pressed in user management
 */
$app->get('/admin/demote', function (Request $request, Response $response) use ($app) {
    //user must be logged in and be admin to be able to demote another admin
    if (!isset($_SESSION['userLoggedIn']) || !isset($_SESSION['userRole']) || $_SESSION['userRole'] !== "admin") {
        return $this->response->withRedirect('/telemetry_processing_public/sessionExpired');
    }

    //Establish connection with the database
    $DatabaseWrapper = $app->getContainer()->get('DatabaseWrapper');
    $db_conf = $app->getContainer()->get('settings');
    $database_connection_settings = $db_conf['pdo_settings'];
    $DatabaseWrapper->setDatabaseConnectionSettings($database_connection_settings);

    //get the user id from the url string
    $userIdToDemote = isset($_GET['accId']) ? trim($_GET['accId']) : 0;
    $demoteSuccessful = true;

    //demote admin
    if ($userIdToDemote > 0  && $userIdToDemote != $_SESSION['userId']) {
        $DatabaseWrapper->demoteAccount($userIdToDemote);
    } else {
        $demoteSuccessful = false;
    }

    $DatabaseWrapper->logActivity($_SESSION['userId'], "Demoted admin: " . $userIdToDemote, $demoteSuccessful);

    //render the page
    return $this->view->render(
        $response,
        'settings/accountDemoted.html.twig',
        [
            'css_path' => CSS_PATH,
            'method' => 'post',
            'action' => '',
            'page_title' => APP_NAME,
            'page_heading_1' => APP_NAME,
            'page_heading_2' => '',
            'demote_status' => $demoteSuccessful,
            'session' => $_SESSION
        ]
    );
});

/**
 * view user logs
 */
$app->get('/admin/viewLogs', function (Request $request, Response $response) use ($app) {
    //user must be logged in and be admin to be able to view logs
    if (!isset($_SESSION['userLoggedIn']) || !isset($_SESSION['userRole']) || $_SESSION['userRole'] !== "admin") {
        return $this->response->withRedirect('/telemetry_processing_public/sessionExpired');
    }

    //Establish connection with the database
    $DatabaseWrapper = $app->getContainer()->get('DatabaseWrapper');
    $db_conf = $app->getContainer()->get('settings');
    $database_connection_settings = $db_conf['pdo_settings'];
    $DatabaseWrapper->setDatabaseConnectionSettings($database_connection_settings);

    //get the user id from the url string
    $userId = isset($_GET['accId']) ? trim($_GET['accId']) : 0;
    $logReadSuccessful = true;

    //fetch the logs
    $userLogs = [];
    if ($userId > 0) {
        $userLogs = $DatabaseWrapper->getUserLogs($userId);
    } else {
        $logReadSuccessful = false;
    }

    $DatabaseWrapper->logActivity($_SESSION['userId'], "Accessed logs: " . $userId, $logReadSuccessful);

    //render the page
    return $this->view->render(
        $response,
        'settings/userLogs.html.twig',
        [
            'css_path' => CSS_PATH,
            'method' => 'post',
            'action' => '',
            'page_title' => APP_NAME,
            'page_heading_1' => APP_NAME,
            'page_heading_2' => '',
            'logs' => $userLogs,
            'logCount' => count($userLogs),
            'session' => $_SESSION
        ]
    );
});
