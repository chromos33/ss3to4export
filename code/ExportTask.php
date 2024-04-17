<?php

class SiteExport extends CliController
{
    private static $allowed_actions = [
        'Export',
    ];
    public function Export()
    {
        ini_set('max_execution_time', 7200);
        if(Member::currentUserID() == 1)
        {
            if(class_exists("Subsite"))
            {
                Subsite::$disable_subsite_filter = true;
            }
            $config = Config::inst()->get("Export", "Configs");
            $CSVstring = $this->generateCSVHeader();
            $ImportConfig = $this->generateCombinedConfig($config);
            $CSVstring .= $this->generateCSVData($ImportConfig);
            $this->outputFile($CSVstring);
        }
        
    }
    private function outputFile($string)
    {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-disposition: attachment; filename=Export.csv');
        header('Content-Length: ' . strlen($string));
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');
        header('Pragma: public');
        
        echo $string;
    }
    private function generateCSVHeader()
    {
        //TODO after Fields are done expand to whatever
        $string = "SourceClass;TargetClass;SourceField;TargetField;OriginalID;HasOneTargetClass;HasOneIDs;ManyManyTargetClass;ManyManyIDs;Value_de_DE";
        /*
        foreach (Translatable::get_allowed_locales() as $locale) {
            $string .= "Value_" . $locale . ";";
        }
        
        $string = substr($string, 0, strlen($string) - 1);
        */
        $string .= " \n";
        return $string;
    }
    //Config Area
    private function isBaseClass($array)
    {
        return array_key_exists("IsBaseClass", $array);
    }
    private function hasBaseClass($array)
    {
        return array_key_exists("BaseClass", $array);
    }
    private function hasOne($array)
    {
        return array_key_exists("hasone", $array) && $array["hasone"] != null;
    }
    private function hasManyMany($array)
    {
        return array_key_exists("manymany", $array) && $array["manymany"] != null;
    }
    private function hasTargetClass($array)
    {
        return array_key_exists("TargetClass", $array);
    }
    private function hasParentLocale($array)
    {
        return array_key_exists("ParentLocale", $array);
    }
    private function hasLocaleField($array)
    {
        return array_key_exists("LocaleField", $array);
    }
    private function cleanLatin1ErrorChars($string)
    {
        $CharMapping = [
            '„' => '&#8222;',
            '“' => '&ldquo;',
            'ß' => '&szlig;',
            '–' => '-',
            '­'  => ''
        ];

        foreach($CharMapping as $Oldchar => $Newchar)
        {
            if($this->containsLatin1ErrorChar($string,$Oldchar))
            {
                $string = $this->replaceLatin1ErrorChar($string,$Oldchar,$Newchar);
            }
        }
        return $string;
    }
    private function containsLatin1ErrorChar($string,$char)
    {
        return strpos($string,$char) !== false;
    }
    private function replaceLatin1ErrorChar($string,$oldchar,$newchar)
    {
        return str_replace($oldchar,$newchar,$string);
    }
    private function generateCombinedConfig($config)
    {
        $CombinedConfig = [];
        $BaseClassConfig = [];
        foreach ($config as $key => $value) {
            $curConf = [];
            //BaseClassConfig
            if ($this->isBaseClass($value)) {
                $BaseClassConfig[$key]["Fields"] = $value["Fields"];
                if($this->hasLocaleField($value))
                {
                    $BaseClassConfig[$key]["LocaleField"] = $value["LocaleField"];
                }
                if ($this->hasOne($value)) {
                    if ($this->hasOne($BaseClassConfig[$key])) {
                        $BaseClassConfig[$key]["hasone"] = array_merge($value["hasone"], $BaseClassConfig[$key]["hasone"]);
                    } else {
                        $BaseClassConfig[$key]["hasone"] = $value["hasone"];
                    }
                }
                if ($this->hasManyMany($value)) {
                    if ($this->hasManyMany($BaseClassConfig[$key])) {
                        $BaseClassConfig[$key]["manymany"] = array_merge($value["manymany"], $BaseClassConfig[$key]["manymany"]);
                    } else {
                        $BaseClassConfig[$key]["manymany"] = $value["manymany"];
                    }
                }
                if (array_key_exists("LocaleField", $value)) {
                    $BaseClassConfig[$key]["LocaleField"] = $value["LocaleField"];
                }
            }
            if ($this->hasBaseClass($value)) {

                if (array_key_exists("IsBaseClass", $value) && array_key_exists($value["BaseClass"], $BaseClassConfig)) {
                    if(array_key_exists("Fields",$value))
                    {
                        $BaseClassConfig[$key]["Fields"] = array_merge($BaseClassConfig[$value["BaseClass"]]["Fields"], $value["Fields"]);
                    }
                    if ($this->hasOne($BaseClassConfig[$value["BaseClass"]]) && $this->hasOne($value)) {
                        $BaseClassConfig[$key]["hasone"] = array_merge($BaseClassConfig[$value["BaseClass"]]["hasone"], $value["hasone"]);
                    } else if ($this->hasOne($value)) {
                        $BaseClassConfig[$key]["hasone"] = $value["hasone"];
                    }
                    
                    if ($this->hasManyMany($BaseClassConfig[$value["BaseClass"]]) && $this->hasManyMany($value)) {
                        $BaseClassConfig[$key]["manymany"] = array_merge($BaseClassConfig[$value["BaseClass"]]["manymany"], $value["manymany"]);
                    } else if ($this->hasManyMany($value)) {
                        $BaseClassConfig[$key]["manymany"] = $value["manymany"];
                    }
                    
                    if(!$this->hasLocaleField($value) && $this->hasLocaleField($BaseClassConfig[$value["BaseClass"]]))
                    {
                        $BaseClassConfig[$key]["LocaleField"] = $BaseClassConfig[$value["BaseClass"]]["LocaleField"];
                    }
                }
                $curConf = $BaseClassConfig[$value["BaseClass"]];
                
                if(array_key_exists("Fields",$value) && is_array($value["Fields"]))
                {
                    $curConf["Fields"] = array_merge($curConf["Fields"], $value["Fields"]);
                }
                if ($this->hasOne($curConf) && $this->hasOne($value)) {
                        $curConf["hasone"] = array_merge($curConf["hasone"], $value["hasone"]);
                } else if ($this->hasOne($value)) {
                    $curConf["hasone"] = $value["hasone"];
                }
                
                if ($this->hasManyMany($curConf) && $this->hasManyMany($value)) {
                    $curConf["manymany"] = array_merge($curConf["manymany"], $value["manymany"]);
                    
                } else if ($this->hasManyMany($value)) {
                    $curConf["manymany"] = $value["manymany"];
                }
            }
            else
            {
                $curConf = $value;
            }
            if ($this->hasTargetClass($value)) {
                $curConf["TargetClass"] = $value["TargetClass"];
            }
            if ($this->hasParentLocale($value)) {
                $curConf["ParentLocale"] = $value["ParentLocale"];
            }
            if (!empty($curConf)) {
                $CombinedConfig[$key] = $curConf;
            }
        }
        return $CombinedConfig;
    }
    //End Config Area
    private function generateCSVData($Importconfig)
    {
        $CSV = "";
        $IgnoreClassWithID = [];
        foreach ($Importconfig as $ObjectClass => $ObjectConfig) {
            //Do not remove filter otherwise i.e. HomePage would be created as Page
            //Translatable::disable_locale_filter();
            $Objects = $ObjectClass::get()->filter("ClassName", $ObjectClass)->Sort("ID ASC");
            foreach ($Objects as $Object) {
                if(method_exists($Object,"isArchiveOrphaned") && $Object->isArchiveOrphaned())
                {
                    continue;
                }
                if ($IgnoreClassWithID[$Object->ClassName] == null || !in_array($Object->ID, $IgnoreClassWithID[$Object->ClassName]["HandledID"])) {
                    /*
                    if(is_subclass_of($ObjectClass,"SiteTree"))
                    {
                        $tmp = new ExportDataRow();
                        $tmp->SourceClass = $Object->ClassName;
                        $tmp->TargetClass = $ObjectConfig["TargetClass"];
                        $tmp->SourceField = "";
                        $tmp->TargetField = "SiteTreeLinkerHelper";
                        $tmp->OriginalID = $Object->ID;
                        $tmpvalue = [$Object->ID];
                        foreach($Object->getTranslatedLocales() as $locale)
                        {
                            $translation = $Object->getTranslations($locale)->first();
                            $tmpvalue[] = $translation->ID;
                        }
                        $tmp->TranslationFields[] = implode(",",$tmpvalue);
                        $tmp->TranslationFields[] = implode(",",$tmpvalue);
                        $CSV .= $tmp->GetCSV();
                    }
                    */
                    foreach ($ObjectConfig["Fields"] as $field) {
                        $tmparray = [];
                        $tmp = new ExportDataRow();
                        $tmp->SourceClass = $Object->ClassName;
                        $tmp->TargetClass = $ObjectConfig["TargetClass"];
                        $tmp->SourceField = $field["Source"];
                        $tmp->TargetField = $field["Target"];
                        $tmp->OriginalID = $Object->ID;
                        $localearray = ["de_DE"];
                        foreach ($localearray as $locale) {
                            if (false && $this->hasLocaleField($ObjectConfig)) {
                                if ($locale == $Object->getField($ObjectConfig["LocaleField"])) {
                                    $insertvalue = $this->cleanLatin1ErrorChars($Object->getField($field["Source"]));
                                    $tmp->TranslationFields[] = $insertvalue;
                                } else {
                                    $availablelocales = $Object->getTranslatedLocales();
                                    if (in_array($locale, $availablelocales)) {
                                        $translation = $Object->getTranslations($locale)->first();
                                        if (!in_array($translation->ID, $IgnoreClassWithID[$Object->ClassName]["HandledID"]))
                                        {
                                            $IgnoreClassWithID[$Object->ClassName]["HandledID"][] = $translation->ID;
                                        }
                                        if ($translation != null) {
                                            $insertvalue = $this->cleanLatin1ErrorChars($translation->getField($field["Source"]));
                                            $tmp->TranslationFields[] = $insertvalue;
                                        } else {
                                            $tmp->TranslationFields[] = "";
                                        }
                                    }
                                    else{
                                        $tmp->TranslationFields[] = "";
                                    }
                                }
                            } else if (false && $this->hasParentLocale($ObjectConfig)) {
                                $parentrelation = $ObjectConfig["ParentLocale"];
                                if ($locale == $Object->$parentrelation()->Locale) {
                                    $tmp->TranslationFields[] = $Object->getField($field["Source"]);
                                } else if(count($tmp->TranslationFields) == 0) {
                                    $tmp->TranslationFields[] = "";
                                }
                            } else {
                                if ($locale == "de_DE") {
                                    $tmp->TranslationFields[] = $Object->getField($field["Source"]);
                                } else if(count($tmp->TranslationFields) == 0) {
                                    $tmp->TranslationFields[] = "";
                                }
                            }
                        }
                        $tmparray[] = $tmp;
                        $CSV .= $tmp->GetCSV();
                    }
                    if($this->hasOne($ObjectConfig))
                    {
                        foreach($ObjectConfig["hasone"] as $hasone)
                        {
                            $tmp = new ExportDataRow();
                            $tmp->SourceClass = $Object->ClassName;
                            $tmp->TargetClass = $ObjectConfig["TargetClass"];
                            $tmp->SourceField = $hasone["Source"];
                            $tmp->TargetField = $hasone["Target"];
                            $tmp->OriginalID = $Object->ID;
                            $tmp->HasOneList[] = $Object->getField($hasone["Source"]);
                            $tmp->OriginalIDs[] = $Object->ID;
                            $tmp->HasOneTargetClass = $hasone["TargetClass"];
                       
                            try{
                                foreach($Object->getTranslatedLocales() as $locale)
                                {
                                    $translation = $Object->getTranslations($locale)->first();
                                    if($translation != null && !in_array($translation->getField($hasone["Source"]),$tmp->HasOneList))
                                    {
                                        array_push($tmp->HasOneList,$translation->getField($hasone["Source"]));
                                        $tmp->OriginalIDs[] = $translation->ID;
                                    }
                                }
                            }catch(Exception $ex)
                            {
                            }   
                            
                            $CSV .= $tmp->GetCSV();
                            
                        }
                    }
                    if($this->hasManyMany($ObjectConfig))
                    {
                        foreach($ObjectConfig["manymany"] as $manymany)
                        {
                            $tmp = new ExportDataRow();
                            $tmp->SourceClass = $Object->ClassName;
                            $tmp->TargetClass = $ObjectConfig["TargetClass"];
                            $tmp->SourceField = $manymany["Source"];
                            $tmp->TargetField = $manymany["Target"];
                            $tmp->OriginalID = $Object->ID;
                            $tmp->ManyManyList = [];
                            //Needs to be extra variable because array output is not string
                            $relationname = $manymany["Source"];
                            foreach($Object->$relationname() as $subobject)
                            {
                                $tmp->ManyManyList[] = $subobject->ID;
                            }
                            $tmp->ManyManyTargetClass = $manymany["TargetClass"];
                            try
                            {
                                foreach($Object->getTranslatedLocales() as $locale)
                                {
                                    $translation = $Object->getTranslations($locale)->first();
                                    if($translation != null)
                                    {
                                        $tmp->ManyManyList[] = $translation->getField($manymany["Source"]);
                                    }
                                }
                            }catch(Exception $ex)
                            {
                                
                            }
                            $CSV .= $tmp->GetCSV();
                        }
                    }
                }
            }
        }
        return $CSV;
    }
}
class ExportDataRow
{
    public $SourceClass = "";
    public $TargetClass = "";
    public $SourceField = "";
    public $TargetField = "";
    public $OriginalID = "";

    public $OriginalIDs = [];
    public $TranslationFields = [];

    public $HasOneList = [];
    public $HasOneTargetClass = "";

    public $ManyManyList = [];
    public $ManyManyTargetClass = "";

    
    public function GetCSV($test = false)
    {
        
        if($test)
        {
            echo "test";
            Debug::Dump($this);die;
        }
        //"SourceClass;TargetClass;SourceField;TargetField;OriginalID;HasOneTargetClass;HasOneIDs;ManyManyTargetClass;ManyManyIDs;";
        $string = "";
        $string .= $this->SourceClass . ";";
        $string .= $this->TargetClass . ";";
        $string .= $this->SourceField . ";";
        $string .= $this->TargetField . ";";
        $string .= $this->OriginalID . ";";
        $string .= $this->HasOneTargetClass . ";";
        $hasonevalues = false;
        foreach($this->HasOneList as $ID)
        {
            if($ID != 0)
            {
                $hasonevalues = true;
                $string.= $ID.",";
            }
        }
        if($hasonevalues)
        {
            $string = substr($string, 0, strlen($string) - 1).";";
        }
        else{
            $string .=";";
        }
        $string .= $this->ManyManyTargetClass . ";";
        $manymanyvalues = false;
        foreach($this->ManyManyList as $ID)
        {
            if($ID != 0)
            {
                $manymanyvalues = true;
                $string.= $ID.",";
            }
        }
        if($manymanyvalues)
        {
            $string = substr($string, 0, strlen($string) - 1).";";
        }
        else{
            $string .=";";
        }
        
        $hasvalues = false;
        foreach ($this->TranslationFields as $value) {
            if ($this->cleanString($value) != "") {
                $hasvalues = true;
            }
            $string .= '"'.$this->cleanString($value) . '";';
        }
        if ($hasvalues || $hasonevalues || $manymanyvalues) {
            return substr($string, 0, strlen($string) - 1) . " \n";
        }
        return "";
    }
    private function cleanString($string)
    {
        $string = str_replace(";", "[semikolon]", $string);
        $string = str_replace(["\r\n", "\n\r", "\n", "\r"], "[linebreak]", $string);
        $string = str_replace('"', "[quotes]", $string);
        return $string;
    }
}
