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

define('FILENAME', 'config.ini');
define('SECTION_DB', 'database');

//==========================================================================================================================
// Instantiate dependencies
//==========================================================================================================================

$output = array();

try {
    $ini_array = parse_ini_file(FILENAME, true);
    if(!$ini_array){
        throw new Exception('Can not parse ini file. ');
    }
    
    $host = $ini_array[SECTION_DB]['host'];
    $username = $ini_array[SECTION_DB]['username'];
    $password = $ini_array[SECTION_DB]['password'];
    $db = $ini_array[SECTION_DB]['db'];
    $port = $ini_array[SECTION_DB]['port'];

    $mysqli = new mysqli($host, $username, $password, 'information_schema', $port);
    $mysqli_connect_error = mysqli_connect_error();
    if($mysqli_connect_error){
        throw new Exception($mysqli_connect_error);
    }
    
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

try {
    
    $sql = "SELECT  TABLE_SCHEMA,
                    TABLE_NAME,
                    COLUMN_NAME,
                    DATA_TYPE,
                    CHARACTER_MAXIMUM_LENGTH,
                    COLLATION_NAME,
                    COLUMN_TYPE,
                    COLUMN_KEY,
                    EXTRA
        FROM `COLUMNS` WHERE `TABLE_SCHEMA` LIKE ? AND `TABLE_NAME` LIKE ? ";  

    $statement = $mysqli->prepare($sql);  
    if ($statement instanceof mysqli_stmt === false) {   
        throw new Exception('Query error');
    }
    
    $tableName = $_GET['tableName'];
    $statement->bind_param('ss',  $db, $tableName);
    $statement->execute(); 

    $mysqli_result = $statement->get_result();  
    if(!($mysqli_result instanceof mysqli_result)){   
        throw new Exception('Error during retrieval of results. ');
    }   
} catch (Exception $exc) {
    header($_SERVER["SERVER_PROTOCOL"]." 422 Unprocessable Entity"); 
    header('Content-Type: application/json');
    
    $output['error'] = $exc->getMessage();
    echo json_encode($output);
    exit;
}

header('Content-Type: application/json');

$information_schema_entities = $mysqli_result->fetch_all(MYSQLI_ASSOC); 
echo json_encode($information_schema_entities);

