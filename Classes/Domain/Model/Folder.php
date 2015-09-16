<?php

namespace Ameos\AmeosFilemanager\Domain\Model;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class Folder extends \TYPO3\CMS\Extbase\Domain\Model\Folder {

	/**
	 * @var string
	 * *@validate NotEmpty
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $description;

	/**
	 * @var string
	 */
	protected $keywords;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Ameos\AmeosFilemanager\Domain\Model\Folder>
	 */
	protected $folders;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Ameos\AmeosFilemanager\Domain\Model\File>
	 */
	protected $files;

	/**
	 * @var \Ameos\AmeosFilemanager\Domain\Model\Folder
	 */
	protected $uidParent;

	/**
	 * @var string
	 */
	protected $feGroupRead;

	/**
	 * @var string
	 */
	protected $feGroupWrite;
	
	/**
	 * @var int
	 */
	protected $crdate;

	/**
	 * @var int
	 */
	protected $tstamp;

	/**
	 * @var integer
	 */
	protected $noReadAccess;

	/**
	 * @var integer
	 */
	protected $noWriteAccess;

	/**
	 * @var \TYPO3\CMS\Extbase\Domain\Model\BackendUser
	 */
	protected $cruserId;

	/**
	 * @var \TYPO3\CMS\Extbase\Domain\Model\FrontendUser
	 */
	protected $feUserId;

	/**
	 * @var array()
	 */
	protected $arrayFeGroupRead;

	/**
	 * @var array()
	 */
	protected $arrayFeGroupWrite;

	/**
	 * @var string
	 */
	protected $identifier;

	/**
     * @return int
     */
	public function getCrdate() {
		return $this->crdate;
	}

	/**
     * @return int
     */
	public function getTstamp() {
		return $this->tstamp;
	}

	/**
     * @return string
     */
	public function getTitle() {
		return $this->title;
	}

	/**
     * @return string
     */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
     * @return string
     */
	public function getDescription() {
		return $this->description;
	}

	/**
     * @return string
     */
	public function getKeywords() {
		return $this->keywords;
	}	

	/**
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Ameos\AmeosFilemanager\Domain\Model\Folder>
	 */
	public function getFolders() {
		$folderRepository = GeneralUtility::makeInstance('Ameos\AmeosFilemanager\Domain\Repository\FolderRepository');
		$folders = $folderRepository->getSubFolderFromFolder($this->getUid());
		return $folders;
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Ameos\AmeosFilemanager\Domain\Model\File>
	 */
	public function getFiles() {
		return $this->files;
	}

	/**
	 * @return \Ameos\AmeosFilemanager\Domain\Model\Folder
	 */
	public function getParent($rootFolderUid = null) {
		if($this->getUid() && $rootFolderUid == $this->getUid()) {
			return false;
		}
		else {
			return $this->uidParent;	
		}
	}
	
	/**
     * @return array
     */
	public function getArrayFeGroupRead() {
		$res=array();
		if($this->feGroupRead) {
			foreach (explode(',', $this->feGroupRead) as $feGroup) {
				$res[] = $feGroup;
			}
		}
		return $res;
	}

	/**
     * @return array
     */
	public function getArrayFeGroupWrite() {
		$res=array();
		if($this->feGroupWrite) {
			foreach (explode(',',$this->feGroupWrite) as $feGroup) {
				$res[] = $feGroup;
			}
		}
		return $res;
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Domain\Model\BackendUser
	 */
	public function getCruser() {
		return $this->cruserId;
	}

	/**
	 * @return \TYPO3\CMS\Extbase\Domain\Model\FrontendUser
	 */
	public function getFeUser() {
        return $this->feUserId;
    }

    /**
	 * @return boolean
	 */
	public function getNoReadAccess() {
		return $this->noReadAccess;
	}

	/**
	 * @return boolean
	 */
	public function getNoWriteAccess() {
		return $this->noWriteAccess;
	}

    /**
     * @return string
     */
    public function getGedPath() {
    	if($parent = $this->getParent()) {
			return $parent->getGedPath().'/'.$this->title;
		}
		return '/'.$this->title;
    }

    /**
     * @return string
     */
    public function getOwnerUsername() {
        return $this->getFeUser() ? $this->getFeUser()->getUsername() : '';
    }

    /**
     * @return string
     */
    public function getRecursiveSubFolders() {
    	$res='';
        if($folders = $this->folders) {
        	foreach ($folders as $folder) {
        		$res .= $folder->getRecursiveSubFolders();
        	}
        }
        $res .= $this->getUid().',';

        return $res;
    }

    /**
     * @return string
     */
    public function getSubFolders() {
    	return substr($this->getRecursiveSubFolders(), 0,-1);
    }

	/**
	 * Setter for title
	 *
	 * @param string $title
	 * @return void
	 */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
	 * Setter for description
	 *
	 * @param string $description
	 * @return void
	 */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
	 * Setter for keywords
	 *
	 * @param string $keywords
	 * @return void
	 */
	public function setKeywords($keywords) {
		$this->keywords = $keywords;
	}

	/**
	 * Setter for identifier
	 *
	 * @param string $identifier
	 * @return void
	 */
	public function setIdentifier($identifier) {
		$this->identifier = $identifier;
	}

	/**
	 * Setter for feUserId
	 *
	 * @param \TYPO3\CMS\Extbase\Domain\Model\FrontendUser $feUserId
	 * @return void
	 */
	public function setFeUser($feUserId) {
        $this->feUserId = $feUserId;
    }

    /**
	 * Setter for cruserId
	 *
	 * @param \TYPO3\CMS\Extbase\Domain\Model\BackendUser $cruserId
	 * @return void
	 */
	public function setCruser($cruserId) {
		$this->cruserId = $cruserId;
	}

	/**
	 * Setter for noReadAccess
	 *
	 * @param boolean $noReadAccess
	 * @return void
	 */
	public function setNoReadAccess($noReadAccess) {
		$this->noReadAccess = $noReadAccess;
	}

	/**
	 * Setter for noWriteAccess
	 *
	 * @param boolean $noWriteAccess
	 * @return void
	 */
	public function setNoWriteAccess($noWriteAccess) {
		$this->noWriteAccess = $noWriteAccess;
	}

	/**
	 * Setter for uidParent
	 *
	 * @param \Ameos\AmeosFilemanager\Domain\Model\Folder $uidParent
	 * @return void
	 */
	public function setUidParent($uidParent) {
		$this->uidParent = $uidParent;
	}

    /**
	 * Setter for arrayFeGroupRead
	 *
	 * @param array $arrayFeGroupRead
	 * @return void
	 */
    public function setArrayFeGroupRead($arrayFeGroupRead) {
		$arrayFeGroupRead = is_array($arrayFeGroupRead) ?  implode(',', $arrayFeGroupRead) : $arrayFeGroupRead;
		$this->feGroupRead = $arrayFeGroupRead;
	}

	/**
	 * Setter for arrayFeGroupWrite
	 *
	 * @param array $arrayFeGroupWrite
	 * @return void
	 */
	public function setArrayFeGroupWrite($arrayFeGroupWrite) {
		$arrayFeGroupWrite = is_array($arrayFeGroupWrite) ?  implode(',', $arrayFeGroupWrite) : $arrayFeGroupWrite;
		$this->feGroupWrite = $arrayFeGroupWrite;
	}

	public function getFileNumber() {
		$folderRepository = GeneralUtility::makeInstance('Ameos\AmeosFilemanager\Domain\Repository\FolderRepository');
		return $folderRepository->countFilesForFolder($this->getUid());		
	}

	public function getFolderNumber() {
		$folderRepository = GeneralUtility::makeInstance('Ameos\AmeosFilemanager\Domain\Repository\FolderRepository');
		return $folderRepository->countFoldersForFolder($this->getUid());		
	}

	public function hasFolder($folderName, $uid=null) {
		foreach ($this->getFolders() as $child) {
			if($child->getTitle() == $folderName && $child->getUid() != $uid){
				return true;
			}
		}
	}


    public function getCategories() {
        $extbaseObjectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Object\ObjectManager');
        $repo = $extbaseObjectManager->get('TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository');

        $uidsCat = $this->getCategoriesUids();

        if(!empty($uidsCat)) {
            $categories = \Ameos\AmeosFilemanager\Tools\Tools::getByUids($repo,$uidsCat);
            return $categories;
        }
        else {
            return;
        }
    }

    public function getCategoriesUids() {
        $test = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            'uid_local',
            'sys_category_record_mm',
            'tablenames like "tx_ameosfilemanager_domain_model_folder" AND fieldname like "cats" AND uid_foreign = ' . $this->getUid(),
            '',
            'sorting_foreign'
        );

        $uidsCat = array_map(function ($e) {
            return $e['uid_local'];
        }, $test);

        return $uidsCat;
    }

    public function setCategories($categories) {
        if(is_array($categories)) {
            $GLOBALS['TYPO3_DB']->exec_DELETEquery(
                'sys_category_record_mm',
                'tablenames like "tx_ameosfilemanager_domain_model_folder" AND fieldname like "cats" AND uid_foreign = ' . $this->getUid()
            );
            $i = 1;
            foreach($categories as $category) {
                $fields_values = array(
                    "uid_local" => $category,
                    "uid_foreign" => $this->getUid(),
                    "tablenames" => "tx_ameosfilemanager_domain_model_folder",
                    "fieldname" => "cats",
                    "sorting_foreign" => $i,
                );
                $GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_category_record_mm', $fields_values, $no_quote_fields=FALSE);
                $i++;
            }
        }
    }

    public function isChildOf($uidFolder) {
    	if($this->getUid() == $uidFolder){
    		return true;
    	}
    	else if($this->getParent()){
    		return $this->getParent()->isChildOf($uidFolder);
    	}
    	else{
    		return false;
    	}
    }

    public function getIsEmpty(){
    	return ( $this->getFileNumber() == 0 && $this->getFolderNumber() == 0 );
    }
}
