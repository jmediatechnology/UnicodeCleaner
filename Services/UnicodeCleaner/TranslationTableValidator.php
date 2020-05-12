<?php

namespace Services\UnicodeCleaner;


class TranslationTableValidator {

    private $TTArray;
    
    private $TTString;
    
    public function setTTArray($TTArray) {
        $this->TTArray = $TTArray;
    }

    public function setTTString($TTString) {
        $this->TTString = $TTString;
    }

    public function validateTTString(){
        if(!$this->TTString){
            throw new \Exception('Translation table is empty. ');
        }
        
        $TTstring = preg_split('/\r\n|\r|\n/', $this->TTString);
        $TTstringSearchList = array_map(function($v){
            return strstr($v, '=', true);
        }, $TTstring);
        
        $TTstringSearchCounter = array_count_values($TTstringSearchList);
        
        $reject = false;
        $str = '';
        foreach($TTstringSearchCounter as $search => $count){
            if($count > 1){
                $reject = true;
                $str .= '<br>Search pattern: ' . $search . ' is specified more than one time, this is not allowed. ';
            }
        }
        
        if($reject){
            throw new \Exception('Double records error: ' . $str);
        }
        
        return true;
    }  

    public function validateTTArray(){
        if(!$this->TTArray){
            throw new \Exception('Translation table is empty. ');
        }
        
        $searchPatternList = array_keys($this->TTArray);
        $searchPatternList = array_map(function($v){
            return trim($v, "\x22\x27"); // hex for "'
        }, $searchPatternList);
        $replacementList = array_values($this->TTArray);
        
        $intersect = array_intersect_assoc($searchPatternList, $replacementList);  
        
        if($intersect){
            $str = '';
            foreach($intersect as $searchPattern => $replacement){
                $str .= '<br>Search pattern: ' . $searchPatternList[$searchPattern] . ' , replacement: ' . $replacement;
            }
            
            throw new \Exception('Senseless search-replacement pattern found: ' . $str);
        }
        
        return true;
    }

}
