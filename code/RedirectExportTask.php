<?php

class RedirectExportTask extends CliController
{
    private static $allowed_actions = [
        'Export',
    ];
    public function Export()
    {
        ini_set('max_execution_time', 7200);
        $CSVstring = $this->generateCSVHeader();

        $CSVstring .= $this->generateCSVRedirectData();

        $this->outputFile($CSVstring);
    }
    private function generateCSVRedirectData()
    {
        $csv = "";
        Translatable::disable_locale_filter();
        $i = 9999;
        $RedirectExportData = [];
        foreach(SiteTree::get() as $page)
        {
            $Links = [];
            array_push($Links,rtrim($page->Link(), '/'));
            foreach($page->Versions("","Created ASC") as $version)
            {
                $Link = rtrim($version->Link(), '/');
                if(!in_array($Link,$Links))
                {
                    array_push($Links,$Link);
                }
            }

            if(count($Links) > 1)
            {
                foreach($Links as $Redirect)
                {
                    if($Redirect != $page->Link())
                    {
                        $tmpdata = new RedirectExportData();
                        $tmpdata->FromBase = $Redirect;
                        $tmpdata->To = rtrim($page->Link(), '/');
                        $tmpdata->ID = $page->ID;
                        
                        $RedirectExportData[] = $tmpdata;
                        /*
                        $csv.= "RedirectedURL;SilverStripe\RedirectedURLs\Model\RedirectedURL;FromBase;FromBase;".$i.$page->ID.";;;;;".$Redirect."\n";
                        $csv.= "RedirectedURL;SilverStripe\RedirectedURLs\Model\RedirectedURL;To;To;".$i.$page->ID.";;;;;".rtrim($page->Link(), '/')."\n";
                        */
                        $i++;
                    }
                }
            }
        }
        
        $CheckedRedirectExportData = $this->RemoveInfiniteLoops($RedirectExportData);
        foreach($CheckedRedirectExportData as $ExportData)
        {
            $csv .= $ExportData->GetCSV();
        }
        return $csv;
    }
    private function RemoveInfiniteLoops($data)
    {
        $removalkeys = [];
        foreach($data as $key => $entry)
        {
            foreach($data as $comparisonkey => $comparisonentry)
            {
                if($key != $comparisonkey)
                {
                    if($entry->From == $comparisonentry->To && $entry->To == $comparisonentry->From)
                    {
                        $removalkeys[] = $key;
                    }
                }
            }
        }
        foreach($removalkeys as $key)
        {
            unset($data[$key]);
        }
        return $data;
    }
    private function generateCSVHeader()
    {
        //TODO after Fields are done expand to whatever
        $string = "SourceClass;TargetClass;SourceField;TargetField;OriginalID;HasOneTargetClass;HasOneIDs;ManyManyTargetClass;ManyManyIDs;";
        foreach (Translatable::get_allowed_locales() as $locale) {
            $string .= "Value_" . $locale . ";";
        }
        $string = substr($string, 0, strlen($string) - 1);
        $string .= " \n";
        return $string;
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
}
class RedirectExportData
{
    public $FromBase;
    public $To;
    public $ID;

    public function GetCSV()
    {
        if($this->FromBase != $this->To)
        {
            $csv = "";
            $csv.= "RedirectedURL;SilverStripe\RedirectedURLs\Model\RedirectedURL;FromBase;FromBase;".$this->ID.";;;;;".$this->FromBase."\n";
            $csv.= "RedirectedURL;SilverStripe\RedirectedURLs\Model\RedirectedURL;To;To;".$this->ID.";;;;;".$this->To."\n";
            return $csv;
        }
        return "";
    }
}