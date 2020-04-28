<?php

//==========================================================================================================================
// ERROR HANDLING
//==========================================================================================================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

//==========================================================================================================================
// For catching Warnings, and Notices.
//==========================================================================================================================

set_error_handler(function($errno, $errstr, $errfile, $errline) {

    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting, so let it fall
        // through to the standard PHP error handler
        return false;
    }

    switch ($errno) {
        case E_WARNING:
            throw new Exception($errstr);
            return true;
            
            break;
        case E_NOTICE:           
            return false;
            
            break;
        case E_USER_ERROR:
            return false;

            break;
        case E_USER_WARNING:
            return false;

            break;
        case E_RECOVERABLE_ERROR:
            return false;

            break;
        case E_USER_NOTICE:
            return false;

            break;
        default:
            return false;
            break;
    }

    /* Don't execute PHP internal error handler */
    return true;
});

//==========================================================================================================================
// NAMESPACES
//==========================================================================================================================


//==========================================================================================================================
// Autoloader
//==========================================================================================================================
require_once '../Autoloader.php';

//==========================================================================================================================
// Main script
//==========================================================================================================================

$output = array();

try {
    
    $host = $_POST['host'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $db = $_POST['db'];

    $mysqli = new mysqli($host, $username, $password, $db);
    $mysqli_connect_error = mysqli_connect_error();
    if($mysqli_connect_error){
        throw new Exception($mysqli_connect_error);
    }
    
    header('Content-Type: application/json');
    
    $output['success'] = true;
    echo json_encode($output);
    exit;
    
} catch (Exception $exc) {
    header($_SERVER["SERVER_PROTOCOL"]." 422 Unprocessable Entity"); 
    header('Content-Type: application/json');
    
    $output['error'] = $exc->getMessage();
    echo json_encode($output);
    exit;
}