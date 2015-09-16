<?php

namespace Ameos\AmeosFilemanager\ViewHelpers;

class BreadcrumbViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

    /**
     * Renders line for folder or file
     *
     * @param Folder $folder 
     * @param int $startFolder 
     * @return string 
     */
    public function render($folder=null,$startFolder = null) {
    	if($folder != null) {
            return $this->getBreadcrumb($folder,$startFolder);
    	}
    }

    public function getBreadcrumb($folder,$startFolder) {
    	$typolink["parameter"] = intval($GLOBALS['TSFE']->id);
        $typolink["additionalParams"] = '&tx_ameos_filemanager[folder]='.$folder->getUid();
        $url = $GLOBALS['TSFE']->cObj->typolink($folder->getTitle(),$typolink);
    	if($folder->getParent() && $folder->getUid() != $startFolder) {
    		return  $this->getBreadcrumb($folder->getParent(),$startFolder)  .'-->' . $url;
    	}
    	else {
    		return $url;
    	}
    }   
}