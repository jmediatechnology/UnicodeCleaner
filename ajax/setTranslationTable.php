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
// DEPENDENCIES
//==========================================================================================================================

use \Services\UnicodeCleaner\TranslationTableValidator;

//==========================================================================================================================
// Constants
//==========================================================================================================================

define('FILENAME', 'config.ini');
define('FILENAME_TRANSLATION_TABLE', 'translation_table.ini');

//==========================================================================================================================
// Main script
//==========================================================================================================================

$output = array();


try {
    
    if(empty($_POST)){
        throw new Exception('No translation content. ');
    }
    if(!key_exists('translation_table_content', $_POST)){
        throw new Exception('No translation content. ');
    }
    
    $translation_table_content = $_POST['translation_table_content'];
    unset($_POST['translation_table_content']);
    
    $translation_table = parse_ini_string($translation_table_content, true);
    $translationTableValidator = new TranslationTableValidator($translation_table);
    $translationTableValidator->validate();
    
    $bytesWritten = file_put_contents(FILENAME_TRANSLATION_TABLE, $translation_table_content);
    if(!$bytesWritten){
        throw new Exception('Failure to write. ');
    }
    
    if($bytesWritten){
        header('Content-Type: application/json');
        
        $output['success'] = true;
        echo json_encode($output);
        exit;
    }
    
} catch (Exception $exc){
    
    header($_SERVER["SERVER_PROTOCOL"]." 422 Unprocessable Entity"); 
    header('Content-Type: application/json');
    
    $output['error'] = $exc->getMessage();
    echo json_encode($output);
    exit;
}
















































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

