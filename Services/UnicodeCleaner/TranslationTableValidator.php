<?php

namespace Services\UnicodeCleaner;


class TranslationTableValidator {

    private $translationTable;

    public function __construct(array $translation_table){    
        $this->translationTable = $translation_table;
    }
    
    public function validate(){
        if(!$this->translationTable){
            throw new \Exception('Translation table is empty. ');
        }
        
        $searchPatternList = array_keys($this->translationTable);
        $searchPatternList = array_map(function($v){
            return trim($v, "\x22\x27"); // hex for "'
        }, $searchPatternList);
        $replacementList = array_values($this->translationTable);
        
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
