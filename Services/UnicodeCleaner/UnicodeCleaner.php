<?php

namespace Services\UnicodeCleaner;

use \Entities\ReinterpretStats\ReinterpretStats;
use \Entities\ReinterpretSettings\ReinterpretSettings;

class UnicodeCleaner {
   
    const PAGING_PAGE_INIT = 1;
    const PAGING_PAGE_SIZE = 2;
    
    const STATS_COUNT_IGNORED = 'amountOfIgnoredCells';
    const STATS_COUNT_CONVERTED = 'amountOfConvertedCells';

    private $db;

    private $table;

    private $fieldPK;

    private $field;

    public function __construct(\mysqli $db){
        $this->db = $db;
    }

    public function setTable($table){
        $this->table = $table;
    }

    public function getTable(){
        return $this->table;
    }

    public function setFieldPK($fieldPK){
        $this->fieldPK = $fieldPK;
    }

    public function getFieldPK(){
        return $this->fieldPK;
    }

    public function setField($field){
        $this->field = $field;
    }

    public function getField(){
        return $this->field;
    }

    public function fetchGarbledFieldValues($pagePointer = 1, ReinterpretSettings $reinterpretSettings, $translation_table = array()) {
     
        $pageSize = self::PAGING_PAGE_SIZE;
                
        // ---------------------------------------------------------------------
        // Step 1) Build the base SELECT query
        // ---------------------------------------------------------------------
        $sql = "
            SELECT
                    t1.". $this->fieldPK .",
                    t1.". $this->field ."
            FROM ". $this->table ." t1
            WHERE 
                true 
        ";
        
        // ---------------------------------------------------------------------
        // Step 2) Narrow down search by the Translation Table. 
        // ---------------------------------------------------------------------
        if($translation_table){
            $i = 0;
            foreach(array_keys($translation_table) as $misinterpretedWord){
                
                $misinterpretedWord = trim($misinterpretedWord, "\x22\x27"); // hex for "'
                
                if($i === 0){
                    $sql .= " AND ";
                } else {
                    $sql .= " OR ";
                }
                
                $sql .=  "t1.". $this->field  ." LIKE '%". $misinterpretedWord ."%' ";
                
                $i++;
            }
        }

        // ---------------------------------------------------------------------
        // Step 3) Define the offset for paging the query. 
        // ---------------------------------------------------------------------
        if($translation_table && $reinterpretSettings->mode !== 'preview'){
            $offset = 0;
        } else {
            $offset = ($pagePointer - 1) * $pageSize;
        }
        $sql .= " LIMIT ?, ? ";
        
        $statement = $this->db->prepare($sql);        
        if ($statement instanceof \mysqli_stmt === false) {
            return null;
        }
        
        $statement->bind_param('dd', $offset, $pageSize);

        $statement->execute();
        $statement->bind_result($id, $field);

        $entities = array();
        while($statement->fetch()){
            $entities[$id] = $field;
        }
        
        return $entities;
    }
    
    public function reinterpret(ReinterpretSettings $reinterpretSettings, array $translation_table = array()) { 

        $pagePointer = self::PAGING_PAGE_INIT;
        $output = $this->reinterpretRecursive($pagePointer, $reinterpretSettings, $translation_table);
        
        return $output;
    }
    
    public function reinterpretRecursive($pagePointer, ReinterpretSettings $reinterpretSettings, array $translation_table = array()){
        
        $output = array();
                
        $garbledFieldValues = $this->fetchGarbledFieldValues($pagePointer, $reinterpretSettings, $translation_table);
        
        if ($pagePointer === self::PAGING_PAGE_INIT && !$garbledFieldValues) {
            throw new \Exception('No misinterpreted data found :-)');
        }
        if(!$garbledFieldValues){
            return $output;
        }
        
        if($translation_table){
            $output = $this->translateCustom($garbledFieldValues, $reinterpretSettings, $translation_table);
        } else {
            $output = $this->iconv($garbledFieldValues, $reinterpretSettings);
        }
        
        $pagePointer++;
        $output = $output + $this->reinterpretRecursive($pagePointer, $reinterpretSettings, $translation_table);        
        return $output;
    }

    public function translateCustom($garbledFieldValues, ReinterpretSettings $reinterpretSettings, $translation_table){
        
        if (!$garbledFieldValues) {
            throw new \Exception('No garbledFieldValues given. ');
        }
        
        $output = array();
        
        $amountOfIgnoredCells = 0;
        $amountOfConvertedCells = 0;             
        foreach ($garbledFieldValues as $id => $field) {

            $output[$id] = array(
                'old' => $field,
                'new' => '',
                'error' => '',
            );

            try {       
                $convertedStr = $this->translateByTranslationTable($translation_table, $field);
            } catch (Exception $ex) {
                $output[$id]['error'] = $ex->getMessage();
                $amountOfIgnoredCells++;
                continue;
            }

            $isOutCharset = $this->isOutCharset($convertedStr, $reinterpretSettings->to) ? true : false;
            if (!$isOutCharset || !$convertedStr) {
                $amountOfIgnoredCells++;
                continue;
            }

            if ($isOutCharset && $reinterpretSettings->mode === 'reinterpret') {
                $affected_rows = $this->updateField($id, $convertedStr);
                if($affected_rows){
                    $output[$id]['new'] = $convertedStr;
                    $amountOfConvertedCells += $affected_rows;            
                } else {
                    $amountOfIgnoredCells++;
                }
            }
            
            if ($isOutCharset && $reinterpretSettings->mode === 'preview') {
                $output[$id]['new'] = $convertedStr;
            }
        }
        
        $reinterpretSettings->reinterpretStats->countIgnored += $amountOfIgnoredCells;
        $reinterpretSettings->reinterpretStats->countConverted += $amountOfConvertedCells;

        return $output;
    }

    public function iconv(array $garbledFieldValues, ReinterpretSettings $reinterpretSettings){
        
        if (!$garbledFieldValues) {
            throw new \Exception('No garbledFieldValues given. ');
        }
        if (!function_exists('iconv')) {
            throw new \Exception('The PHP module "iconv" is disabled or not installed on this Server. ');
        }
        
        $output = array();
        
        $amountOfIgnoredCells = 0;
        $amountOfConvertedCells = 0;             
        foreach ($garbledFieldValues as $id => $field) {

            $output[$id] = array(
                'old' => $field,
                'new' => '',
                'error' => '',
            );

            try {
                $convertedStr = iconv($reinterpretSettings->from, $reinterpretSettings->to, $field);
            } catch (\Exception $ex) {
                $output[$id]['error'] = $ex->getMessage();
                $amountOfIgnoredCells++;
                continue;
            }

            $isOutCharset = $this->isOutCharset($convertedStr, $reinterpretSettings->to) ? true : false;
            if (!$isOutCharset || !$convertedStr) {
                $amountOfIgnoredCells++;
                continue;
            }

            if ($isOutCharset && $reinterpretSettings->mode === 'reinterpret') {
                $affected_rows = $this->updateField($id, $convertedStr);
                if($affected_rows){
                    $output[$id]['new'] = $convertedStr;
                    $amountOfConvertedCells += $affected_rows;            
                } else {
                    $amountOfIgnoredCells++;
                }
            }
            
            if ($isOutCharset && $reinterpretSettings->mode === 'preview') {
                $output[$id]['new'] = $convertedStr;
            }
        }
        
        $reinterpretSettings->reinterpretStats->countIgnored += $amountOfIgnoredCells;
        $reinterpretSettings->reinterpretStats->countConverted += $amountOfConvertedCells;
        
        return $output;
    }
    
    private function isOutCharset($str, $outputCharset) {
        return mb_check_encoding($str, $outputCharset);
    }

    /**
     * Manual translation of misinterpretations from UTF-8 to ISO-8859-1.
     * 
     * @param string$str
     * @return string
     */
    public function translateMisinterpretations($str) {

        $needles = array(
            'â‚¬','â€š','Æ’','â€ž','â€¦','â€¡','Ë†','â€°','Å','â€¹','Å’','Å½',
            'â€˜','â€™','â€œ','â€¢','â€“','â€”','Ëœ','â„¢','Å¡','â€º',
            'Å“',
            
            'Å¾','Å¸',
            
            'Â','Â¡','Â¢','Â£','Â¤','Â¥','Â¦','Â§','Â¨','Â©','Âª','Â«',
            'Â¬','Â­','Â®','Â¯', 'Â°','Â±','Â²','Â³','Â´','Âµ', 'Â¶','Â·','Â¸','Â¹',
            'Âº','Â»','Â¼','Â½','Â¾','Â¿',
            
            'Ã€','Ã‚','Ãƒ','Ã„','Ã…','Ã†','Ã‡',
            'Ãˆ','Ã‰','Ã‹','ÃŽ','Ã‘','Ã’','Ã“','Ã”','Ã•','Ã–',
            'Ã—','Ã˜','Ã™','Ã›','Ãž','ÃŸ','Ã¡','Ã¢','Ã£','Ã¤',
            'Ã¥','Ã¦','Ã§','Ã¨','Ã©','Ãª','Ã«','Ã¬','Ã­','Ã®','Ã¯','Ã°','Ã±','Ã²',
            'Ã³','Ã´','Ãµ','Ã¶','Ã·','Ã¸','Ã¹','Ãº','Ã»','Ã¼','Ã½','Ã¾','Ã¿',
        );
        $replace = array(
            '€','‚','ƒ','„','…','‡','ˆ','‰','Š','‹','Œ','Ž','‘','’','“',
            '•','–','—','˜','™','š','›','œ','ž','Ÿ','',
            
            '¡','¢','£','¤','¥','¦','§',
            '¨','©','ª','«','¬','­','®','¯','°',
            
            '±','²','³','´','µ','¶','·','¸','¹',
            'º','»','¼','½','¾','¿',
            
            'À','Â','Ã','Ä','Å','Æ','Ç','È','É','Ë',
            'Î','Ñ','Ò','Ó','Ô','Õ','Ö','×','Ø','Ù','Û',
            'Þ','ß','á','â','ã','ä','å','æ','ç','è','é','ê','ë','ì','í','î','ï',
            'ð','ñ','ò','ó','ô','õ','ö','÷','ø','ù','ú','û','ü','ý','þ','ÿ'
        );

        foreach($needles as $k => $needle){
            if(mb_strpos($str,$needle) !== FALSE){
                $str = str_replace($needles[$k], $replace[$k], $str);
            }
        }

        return $str;
    }

    public function translateByTranslationTable($translation_table, $str){
        
        foreach($translation_table as $misinterpretedWord => $replace){
            
            $misinterpretedWord = trim($misinterpretedWord, "\x22\x27"); // hex for "'
                        
            if(strpos($str,$misinterpretedWord) !== FALSE){
                $str = str_replace($misinterpretedWord, $replace, $str);
            }
        }

        return $str;
    }

    public function updateField($id, $fieldValue){

        $fieldValue = $this->db->real_escape_string($fieldValue);
        $id = $this->db->real_escape_string($id);

        $sql = "UPDATE ".$this->table  ." as t1
                SET t1.". $this->field ." = '". $fieldValue ."' 
                WHERE t1.". $this->fieldPK ." = ". $id  ."
                LIMIT 1
        ";

        $statement = $this->db->prepare($sql);
        if($statement instanceof \mysqli_stmt === false){
            return null;
        }

        $status = $statement->execute();
        if($status === false){
            return $status;
        }

        return $statement->affected_rows;
    }

}
