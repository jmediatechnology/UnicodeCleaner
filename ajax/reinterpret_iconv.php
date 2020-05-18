<?php

//==========================================================================================================================
// ERROR HANDLING
//==========================================================================================================================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

//==========================================================================================================================
// EXECUTION TIME
//==========================================================================================================================
ini_set('max_execution_time', 0); // 0 = run forever
//
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
            return false;
            break;
                        
            break;
        case E_NOTICE:           
            throw new Exception($errstr);
            return true;
            
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

use \Services\UnicodeCleaner\UnicodeCleaner;
use \Entities\ReinterpretStats\ReinterpretStats;
use \Entities\ReinterpretSettings\ReinterpretSettings;

//==========================================================================================================================
// Constants
//==========================================================================================================================

define('FILENAME', 'config.ini');
define('SECTION_DB', 'database');
define('SECTION_TARGET', 'target');

define('OUTPUT_ERROR', 'Error in displaying data');

//==========================================================================================================================
// Instantiate dependencies
//==========================================================================================================================

$output = array();

try {
    $ini_array = parse_ini_file(FILENAME, true);
    
    // Throw some Exceptions before we actually execute the script. 
    if(!$ini_array){
        throw new Exception('Can not parse ini file. ');
    }
    if(!key_exists(SECTION_DB, $ini_array) || 
        !key_exists('host', $ini_array[SECTION_DB]) || 
        !key_exists('username', $ini_array[SECTION_DB]) || 
        !key_exists('password', $ini_array[SECTION_DB]) || 
        !key_exists('db', $ini_array[SECTION_DB]) 
        ){
        throw new Exception('The connection to the database was not set properly, please set it at the "DB Connection" page. ');
    }
    if(!key_exists(SECTION_TARGET, $ini_array) || 
        !key_exists('target_table', $ini_array[SECTION_TARGET]) || 
        !key_exists('target_column_id', $ini_array[SECTION_TARGET]) || 
        !key_exists('target_column_target', $ini_array[SECTION_TARGET])    
        ){
        throw new Exception('The target is not set properly, please set it at the "Select Table & Column" page. ');
    }
    
    $host = $ini_array[SECTION_DB]['host'];
    $username = $ini_array[SECTION_DB]['username'];
    $password = $ini_array[SECTION_DB]['password'];
    $db = $ini_array[SECTION_DB]['db'];
    $port = $ini_array[SECTION_DB]['port'];

    $mysqli = new mysqli($host, $username, $password, $db, $port);
    $mysqli_connect_error = mysqli_connect_error();
    if($mysqli_connect_error){
        throw new Exception($mysqli_connect_error);
    }
    $mysqli->set_charset($_POST['encoding_for_db_connection']);
    
    $reinterpretSettings = new ReinterpretSettings;
    
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
    $unicodeCleaner = new UnicodeCleaner($mysqli);
    $unicodeCleaner->setTable($ini_array[SECTION_TARGET]['target_table']);
    $unicodeCleaner->setFieldPK($ini_array[SECTION_TARGET]['target_column_id']);
    $unicodeCleaner->setField($ini_array[SECTION_TARGET]['target_column_target']);
        
    $reinterpretSettings->reinterpretStats = new ReinterpretStats;
    $reinterpretSettings->from = $_POST['encoding_from'];
    $reinterpretSettings->to = $_POST['encoding_to'];
    $reinterpretSettings->mode = $_POST['mode'];
    
    $data = $unicodeCleaner->reinterpret($reinterpretSettings);
    
    $amountOfIgnoredCells = $reinterpretSettings->reinterpretStats->countIgnored;
    $amountOfConvertedCells = $reinterpretSettings->reinterpretStats->countConverted;

} catch (Exception $exc) {
    header($_SERVER["SERVER_PROTOCOL"]." 422 Unprocessable Entity"); 
    header('Content-Type: application/json');
    
    $output['error'] = $exc->getMessage();
    echo json_encode($output);
    exit;
}



header('Content-Type: application/json');

$message = 'Converting from ' . $_POST['encoding_from'] . ' to ' . $_POST['encoding_to'] . '<br>';
$messageAmountOfConvertedCells = 'Amount of converted cells: ' . $amountOfConvertedCells . ' cells. <br>';
$messageAmountOfIgnoredCells = 'Amount of ignored cells: ' . $amountOfIgnoredCells . ' cells. <br>';

$output['message'] = $message . $messageAmountOfConvertedCells . $messageAmountOfIgnoredCells;
$output['data'] = $data;

$phpVersion = PHP_MAJOR_VERSION . PHP_MINOR_VERSION;
$phpVersion = (int)$phpVersion;
if($phpVersion < 54){
    $outputJSON = my_json_encode($output);
} else {
    $output = outputArrayCleaner($output);
    $outputJSON = json_encode($output);
}

$message = validateJSON($outputJSON, $messageAmountOfConvertedCells, $messageAmountOfIgnoredCells);

echo $message;
    
exit;


//==========================================================================================================================
// FUNCTIONS
//==========================================================================================================================


function validateJSON($outputJSON, $messageAmountOfConvertedCells, $messageAmountOfIgnoredCells){
    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            // No json errors
            $message = $outputJSON;
        break;
        case JSON_ERROR_DEPTH:
            $output = array();
            $output['message'] = $messageAmountOfConvertedCells . $messageAmountOfIgnoredCells . '<br> '. OUTPUT_ERROR . ': Maximum stack depth exceeded';
            $message = json_encode($output);
        break;
        case JSON_ERROR_STATE_MISMATCH:
            $output = array();
            $output['message'] = $messageAmountOfConvertedCells . $messageAmountOfIgnoredCells . '<br> '. OUTPUT_ERROR . ': Underflow or the modes mismatch';
            $message = json_encode($output);
        break;
        case JSON_ERROR_CTRL_CHAR:
            $output = array();
            $output['message'] = $messageAmountOfConvertedCells . $messageAmountOfIgnoredCells . '<br> '. OUTPUT_ERROR . ': Unexpected control character found';
            $message = json_encode($output);
        break;
        case JSON_ERROR_SYNTAX:
            $output = array();
            $output['message'] = $messageAmountOfConvertedCells . $messageAmountOfIgnoredCells . '<br> '. OUTPUT_ERROR . ': Syntax error, malformed JSON';
            $message = json_encode($output);
        break;
        case JSON_ERROR_UTF8:
            $output = array();
            $output['message'] = $messageAmountOfConvertedCells . $messageAmountOfIgnoredCells . '<br> '. OUTPUT_ERROR . ': Malformed UTF-8 characters, possibly incorrectly encoded';
            $message = json_encode($output);
        break;
        default:
            $output = array();
            $output['message'] = $messageAmountOfConvertedCells . $messageAmountOfIgnoredCells . '<br> '. OUTPUT_ERROR . ': Unknown error';
            $message = json_encode($output);
        break;
    }
    
    return $message;
}

function hasGarbledChars($str) {

    // @link: https://www.i18nqa.com/debug/utf8-debug.html
    $misinterpretedChars = array(
        'â‚¬','â€š','Æ’','â€ž','â€¦','â€','â€¡','Ë†','â€°','Å','â€¹','Å’','Å½',
        'â€˜','â€™','â€œ','â€','â€¢','â€“','â€”','Ëœ','â„¢','Å¡','â€º','Å“',
        'Å¾','Å¸','Â','Â¡','Â¢','Â£','Â¤','Â¥','Â¦','Â§','Â¨','Â©','Âª','Â«',
        'Â¬','Â­','Â®','Â¯', 'Â°','Â±','Â²','Â³','Â´','Âµ', 'Â¶','Â·','Â¸','Â¹',
        'Âº','Â»','Â¼','Â½','Â¾','Â¿','Ã€','Ã','Ã‚','Ãƒ','Ã„','Ã…','Ã†','Ã‡',
        'Ãˆ','Ã‰','ÃŠ','Ã‹','ÃŒ','Ã','ÃŽ','Ã','Ã','Ã‘','Ã’','Ã“','Ã”','Ã•','Ã–',
        'Ã—','Ã˜','Ã™','Ãš','Ã›','Ãœ','Ã','Ãž','ÃŸ','Ã','Ã¡','Ã¢','Ã£','Ã¤','Ã¥',
        'Ã¦','Ã§','Ã¨','Ã©','Ãª','Ã«','Ã¬','Ã­','Ã®','Ã¯','Ã°','Ã±','Ã²','Ã³',
        'Ã´','Ãµ','Ã¶','Ã·','Ã¸','Ã¹','Ãº','Ã»','Ã¼','Ã½','Ã¾','Ã¿',

        'â?¬','â„®','â€',
    );

    foreach($misinterpretedChars as $misinterpretated){
        if(strpos($str,$misinterpretated) !== FALSE){
            return true;
        }
    }

    return false;
}


/**
 * Clean non-UTF8 chars for output
 * 
 * @link https://stackoverflow.com/a/46305914/1280520
 * 
 * @param type $arr
 * @return type
 */
function outputArrayCleaner($arr){
    array_walk_recursive($arr, function (&$item, $key) { $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8'); });
    return $arr;
}

/**
 * @link https://www.php.net/manual/en/function.json-encode.php#105789
 * 
 * @param type $arr
 * @return type
 */
function my_json_encode($arr){
    //convmap since 0x80 char codes so it takes all multibyte codes (above ASCII 127). So such characters are being "hidden" from normal json_encoding
    array_walk_recursive($arr, function (&$item, $key) { if (is_string($item)) $item = mb_encode_numericentity($item, array (0x80, 0xffff, 0, 0xffff), 'UTF-8'); });
    return mb_decode_numericentity(json_encode($arr), array (0x80, 0xffff, 0, 0xffff), 'UTF-8');
}