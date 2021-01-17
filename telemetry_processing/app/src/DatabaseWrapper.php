<?php
/**
 * DatabaseWrapper.php
 *
 * Wrapper class for accessing the database and performing all db activities.
 *
 *
 * @author Dominik Michalski and Adam Machowczyk
 *
 */

namespace TelemetryProcessing;

use PDO;
use PDOException;
use DateTime;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class DatabaseWrapper
{
    private $database_connection_settings;
    private $db_handle;
    private $sql_queries;
    private $prepared_statement;
    private $errors;
    private $log;

    public function __construct()
    {
        $this->database_connection_settings = null;
        $this->db_handle = null;
        $this->sql_queries = null;
        $this->prepared_statement = null;
        $this->errors = [];
    }

    public function __destruct()
    {
    }

    public function getVars()
    {
        $vars = [
            $this->database_connection_settings,
            $this->db_handle,
            $this->sql_queries,
            $this->prepared_statement,
            $this->errors,
            $this->log
        ];

        return $vars;
    }

    public function setDatabaseConnectionSettings($database_connection_settings)
    {
        $this->database_connection_settings = $database_connection_settings;
    }
    /**
     * Function that creates a PDO object
     *
     * @return string - Only when error encountered
     *
     */
    public function makeDatabaseConnection()
    {
        $pdo_error = '';

        $database_settings = $this->database_connection_settings;
        $host_name = $database_settings['rdbms'] . ':host=' . $database_settings['host'];
        $port_number = ';port=' . '3306';
        $user_database = ';dbname=' . $database_settings['db_name'];
        $host_details = $host_name . $port_number . $user_database;
        $user_name = $database_settings['user_name'];
        $user_password = $database_settings['user_password'];
        $pdo_attributes = $database_settings['options'];

        try {
            $pdo_handle = new PDO($host_details, $user_name, $user_password, $pdo_attributes);
            $this->db_handle = $pdo_handle;
        } catch (PDOException $exception_object) {
            trigger_error('error connecting to database');
            $this->log->error('Error occurred when attempting to connect to database.');
            $pdo_error = 'error connecting to database';
        }

        return $pdo_error;
    }

    /**
     * @param $query_string
     * @param null $params
     *
     * Function checks if a sql query is correct
     *
     * @return mixed
     */
    private function safeQuery($query_string, $params = null)
    {
        $this->errors['db_error'] = false;
        $query_parameters = $params;

        try {
            $this->prepared_statement = $this->db_handle->prepare($query_string);
            $execute_result = $this->prepared_statement->execute($query_parameters);
            $this->errors['execute-OK'] = $execute_result;
        } catch (PDOException $exception_object) {
            $error_message = 'PDO Exception caught. ';
            $error_message .= 'Error with the database access.' . "\n";
            $error_message .= 'SQL query: ' . $query_string . "\n";
            $error_message .= 'Error: ' . var_dump($this->prepared_statement->errorInfo(), true) . "\n";

            $this->log->error('Error occurred when attempting to execute query: ' . $query_string . $query_parameters);

            $this->errors['db_error'] = true;
            $this->errors['sql_error'] = $error_message;
        }
        return $this->errors['db_error'];
    }

    public function countRows()
    {
        $num_rows = $this->prepared_statement->rowCount();
        return $num_rows;
    }

    public function safeFetchRow()
    {
        $record_set = $this->prepared_statement->fetch(PDO::FETCH_NUM);
        return $record_set;
    }

    public function safeFetchArray()
    {
        $row = $this->prepared_statement->fetchAll();
        $this->prepared_statement->closeCursor();
        return $row;
    }
    
    /**
     * Get all messages that are stored in the DB
     *
     *
     * @return mixed
     */
    public function getMessages()
    {
        $messages = '';
        $this->makeDatabaseConnection();

        $query_string = 'SELECT * FROM messages';

        $this->safeQuery($query_string);
        if ($this->countRows() > 0) {
            $messages = $this->safeFetchArray();
        }

        return $messages;
    }

    /**
     * Insert a message into the database
     *
     * @param $switch_01
     * @param $switch_02
     * @param $switch_03
     * @param $switch_04
     * @param $heater
     * @param $keypad
     * @param $fan
     * @param $id
     *
     *
     * @param $number
     * @return mixed
     */
    public function addMessage($switch_01, $switch_02, $switch_03, $switch_04, $heater, $keypad, $fan, $id, $number)
    {
        $fan = "'" . $fan . "'";
        $id = "'" . $id . "'";
        $this->makeDatabaseConnection();
        $query_string = 'INSERT INTO messages (switch_01, switch_02, switch_03, switch_04, heater, keypad, fan,
        unique_message, src_number)
        VALUES (' . $switch_01 . ', ' . $switch_02 . ', ' . $switch_03 . ', ' . $switch_04 . ', 
        ' . $heater . ', ' . $keypad . ', ' . $fan . ', ' . $id . ', ' . $number . ')';
        $this->safeQuery($query_string);
    }
    
    /**
     * Check if message is unique
     *
     * @param $id
     *
     * @return boolean
     */
    public function isMessageUnique($id)
    {
        $this->makeDatabaseConnection();

        $sql = "SELECT * FROM messages WHERE unique_message = '$id'";

        $this->safeQuery($sql);

        if ($this->countRows() > 0) {
            return false;
        } else {
            return true;
        }
    }
    
    /**
     * Checks whether this email address already exists in the database
     *
     * @param $email
     *
     * @return bool
     */
    public function isEmailUnique($email)
    {
        $this->makeDatabaseConnection();

        $sql = "SELECT email FROM users WHERE email = '$email'";
        $this->safeQuery($sql);

        if ($this->countRows() > 0) {
            return 0;
        } else {
            return 1;
        }
    }
    
    /**
     * Add a new user to the database
     *
     * @param $email
     * @param $password
     *
     */
    public function registerNewUser($email, $password)
    {
        $this->makeDatabaseConnection();
        $sql = "INSERT INTO users
		( email, password, role )
		VALUES
		( '$email', '$password', 'user')";

        $this->safeQuery($sql);
    }

    /**
     * When user requests a password reset, the key is inserted into the database
     *
     * @param $email
     *
     * @return string
     */
    public function insertPasswordUpdateLink($email)
    {
        $this->makeDatabaseConnection();
        $keyToInsert = $this->getToken(255);

        $sql = "UPDATE users SET passwordResetKey = '$keyToInsert' WHERE email = '$email'";
        $this->safeQuery($sql);

        return $keyToInsert;
    }

    /**
     * Generates random string with given length
     *
     * Used to generate reset password token and unique ID for the message
     *
     * @param $length
     *
     * @return string
     */
    public function getToken($length = 255)
    {
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet .= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet .= "0123456789";
        $codeALength = strlen($codeAlphabet) - 1;

        for ($i = 0; $i < $length; ++$i) {
            $token .= $codeAlphabet[rand(0, $codeALength)];
        }

        return $token;
    }
    
    /**
     * Send email to the user when they ask for password reset
     *
     *
     * @param $email
     * @param $resetKey
     *
     */
    public function sendPasswordChangeEmail($email, $resetKey)
    {
        $resetLink = 'http://php.tech.dmu.ac.uk:6789/p2429405/telemetry_processing_public/reset_password?qs=' . $resetKey;
        $subject = "Reset Password : Telemetry Processing";
        $headers = array(
            'From: No reply',
            'Reply-To: noreply@TelemetryProcessing.net',
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=ISO-8859-1'
        );
        $txt = "It appears that someone has requested to change the password assigned to this
				email <br /> on the TelemetryProcessing web application. If it was you, press the
				URL located below.<br /><br />Reset Password: <a href='$resetLink' target='_blank'>
				$resetLink</a><br /><br /> If you did not ask for a password reset, simply ignore this email.";

        mail($email, $subject, $txt, implode("\r\n", $headers));
    }

    /**
     * Updates the old password with a new password
     *
     *
     * @param $password
     * @param $userId
     *
     */
    public function updateUserPassword($password, $userId)
    {
        $this->makeDatabaseConnection();
        $newPass = password_hash($password, PASSWORD_BCRYPT);
        $sql = "UPDATE users SET passwordResetKey = NULL, password = '$newPass' WHERE id = '$userId'";
        $this->safeQuery($sql);
    }

    /**
     * checks whether the password reset key exists and who it belongs to
     *
     *
     * @param $key
     *
     * @return int
     */
    public function validatePasswordResetString($key)
    {
        $this->makeDatabaseConnection();
        $userId = null;

        $key = '"' . $key . '"';
        $sql = 'SELECT id FROM users WHERE passwordresetkey = ' . $key;

        $this->safeQuery($sql);

        if ($this->countRows() > 0) {
            $userId = $this->safeFetchRow();
        }

        if ($userId !== null) {
            return $userId[0];
        } else {
            return 0;
        }
    }
    
    /**
     * Get details of a given user
     *
     * @param $email
     *
     * @return int
     */
    public function getUserData($email)
    {
        $this->makeDatabaseConnection();
        $userData = null;

        $sql = "SELECT id, password, role, barred, email FROM users WHERE email = '$email'";
        $this->safeQuery($sql);

        if ($this->countRows() > 0) {
            $userData = $this->safeFetchArray()[0];
        }

        if ($userData !== null) {
            return $userData;
        } else {
            return 0;
        }
    }
    
    /**
     * Get all users from the database
     *
     * @return mixed
     */
    public function getAllUsers()
    {
        $this->makeDatabaseConnection();
        $userData = null;

        $sql = "SELECT id, email, createdate, role, barred FROM users";
        $this->safeQuery($sql);

        if ($this->countRows() > 0) {
            $userData = $this->safeFetchArray();
        }

        if ($userData !== null) {
            return $userData;
        } else {
            return 0;
        }
    }

    /**
     * Bar an account
     *
     * @param $userId
     *
     */
    public function barAccount($userId)
    {
        $this->makeDatabaseConnection();

        $sql = "UPDATE users SET barred = 1 WHERE id = '$userId'";
        $this->safeQuery($sql);
    }

    /**
     * Change a bar status to false
     *
     * @param $userId
     *
     */
    //changes bar status to false
    public function freeAccount($userId)
    {
        $this->makeDatabaseConnection();

        $sql = "UPDATE users SET barred = 0 WHERE id = '$userId'";
        $this->safeQuery($sql);
    }

    /**
     * Make a normal account an admin account
     *
     * @param $userId
     *
     */
    public function promoteAccount($userId)
    {
        $this->makeDatabaseConnection();

        $sql = "UPDATE users SET role = 'admin' WHERE id = $userId";
        $this->safeQuery($sql);
    }

    /**
     * Make an admin account an admin account
     *
     * @param $userId
     *
     */
    public function demoteAccount($userId)
    {
        $this->makeDatabaseConnection();

        $sql = "UPDATE users SET role = 'user' WHERE id = $userId";
        $this->safeQuery($sql);
    }

    /**
     * Retrieve user password
     *
     * @param $userId
     *
     * @return mixed
     */

    public function getUserPasswordById($userId)
    {
        $this->makeDatabaseConnection();
        $password = "";

        $sql = "SELECT password FROM users WHERE id = '$userId'";
        $this->safeQuery($sql);

        if ($this->countRows() > 0) {
            $password = $this->safeFetchRow();
        } else {
            return null;
        }

        return $password[0];
    }

    /**
     * Delete account
     *
     * @param $userId
     *
     */
    public function deleteAccount($userId)
    {
        $this->makeDatabaseConnection();

        $sql = "DELETE FROM users WHERE id = '$userId'";
        $this->safeQuery($sql);
    }

    /**
     * Log and store activity into the database
     *
     * @param $userId
     * @param $activity
     * @param $result
     *
     * @return mixed
     */
    public function logActivity($userId, $activity, $result)
    {
        $this->makeDatabaseConnection();

        $sql = "INSERT INTO activity (userId, activity, result)VALUES($userId, '$activity', $result)";


        $this->safeQuery($sql);
    }

    /**
     * Get all activity logs for given user
     *
     *
     * @return mixed
     */
    public function getUserLogs($userId)
    {
        $this->makeDatabaseConnection();
        $logs = [];

        $sql = "SELECT * FROM activity WHERE userId = $userId";
        $this->safeQuery($sql);

        if ($this->countRows() > 0) {
            $logs = $this->safeFetchArray();
        }

        if ($logs !== null) {
            return $logs;
        } else {
            return 0;
        }
    }
}
