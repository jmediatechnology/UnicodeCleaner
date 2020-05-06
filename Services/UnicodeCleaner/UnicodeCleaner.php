<?php

namespace Services\UnicodeCleaner;

class UnicodeCleaner {

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

    public function fetchGarbledFieldValues($translation_table = array()) {
        
        // -- t1.". $this->field  ." LIKE '%Ã%' COLLATE utf8_bin  OR t1.". $this->field ." LIKE '%Ã%' COLLATE utf8_bin 
        $sql = "
            SELECT
                    t1.". $this->fieldPK .",
                    t1.". $this->field ."
            FROM ". $this->table ." t1
            WHERE 
                true 
        ";
        
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

        $statement = $this->db->prepare($sql);        
        if ($statement instanceof \mysqli_stmt === false) {
            return null;
        }

        $statement->execute();
        $statement->bind_result($id, $field);

        $entities = array();
        while($statement->fetch()){
            $entities[$id] = $field;
        }

        return $entities;
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
