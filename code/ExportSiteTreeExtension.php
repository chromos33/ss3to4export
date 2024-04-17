<?php

class ExportSiteTreeExtension extends DataExtension
{
    public function isArchiveOrphaned()
    {
        // Always false for root pages
        if(empty($this->ParentID)) return false;
        
        $cur = $this;
        while($cur != null)
        {
            if($cur->customIsArchived())
            {
                return true;
            }
            $cur = $cur->Parent();
        }
        return false;
    }
    protected function customIsArchived($ID) {
		if($ID) {
			$Page = Versioned::get_latest_version("SiteTree", $ID);
			if(!$Page || $Page->IsDeletedFromStage) {
				return true;
			}
		}
		return false;
	}
}