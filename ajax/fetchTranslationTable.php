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
// Autoloader
//==========================================================================================================================
chdir('../');
require_once 'Autoloader.php';


//==========================================================================================================================
// Constants
//==========================================================================================================================

define('FILENAME_TRANSLATION_TABLE', 'translation_table.ini');

//==========================================================================================================================
// Instantiate dependencies
//==========================================================================================================================

$output = array();

try {

    if(!file_exists(FILENAME_TRANSLATION_TABLE)){
        throw new Exception('No translation table ini file found. ');
    }
    $content = file(FILENAME_TRANSLATION_TABLE);
    
} catch (Exception $exc) {
    header($_SERVER["SERVER_PROTOCOL"]." 422 Unprocessable Entity"); 
    header('Content-Type: application/json');
    
    $output['error'] = $exc->getMessage();
    echo json_encode($output);
    exit;
}

//==========================================================================================================================
// Main script
//==========================================================================================================================



header('Content-Type: application/json');

echo json_encode($content);

