<?php

namespace Ameos\AmeosFilemanager\Slots;

use Ameos\AmeosFilemanager\Tools\Tools;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Slot {

	/**
	 * Call after folder rename in filelist
	 * Rename the correct folder in the database
	 * @param Folder $folder 
	 * @param string $newName
	 * @return void
	 */
	public function postFolderRename($folder,$newName) {
		$folderRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')->get('Ameos\AmeosFilemanager\Domain\Repository\FolderRepository');
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery("uid", "tx_ameosfilemanager_domain_model_folder", "tx_ameosfilemanager_domain_model_folder.identifier like '".$folder->getIdentifier()."'" );
		if(($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== FALSE) {
			if(Tools::getFolderPathFromUid($row['uid']).'/' == $folder->getIdentifier()) {
				$folderRepository->requestUpdate($row['uid'], array("title"=>$newName));
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
	}

	/**
	 * Call after folder addition in filelist
	 * Add the correct folder in the database
	 * @param Folder $folder
	 * @return void
	 */
	public function postFolderAdd($folder) {
		if($folder->getParentFolder() && $folder->getParentFolder()->getName() != '') {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery("uid", "tx_ameosfilemanager_domain_model_folder", "tx_ameosfilemanager_domain_model_folder.identifier like '".$folder->getParentFolder()->getIdentifier()."'" );
			if(($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== FALSE) { 
				if(Tools::getFolderPathFromUid($row['uid']).'/' == $folder->getParentFolder()->getIdentifier()) {
					$insertArray = array(
					    "tstamp" => time(),
					    "crdate" => time(),
					    "cruser_id" => 1,
					    "title" => $folder->getName(),
					    "uid_parent" => $row['uid'],
					    "identifier" => $folder->getIdentifier()
					);
					$folderRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')->get('Ameos\AmeosFilemanager\Domain\Repository\FolderRepository');
					$folderRepository->requestInsert($insertArray);
				}
			} else {			
				$this->postFolderAdd($folder->getParentFolder());
				$this->postFolderAdd($folder);
			}
		} else {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery("uid", "tx_ameosfilemanager_domain_model_folder", "tx_ameosfilemanager_domain_model_folder.identifier like '".$folder->getIdentifier()."'" );
			if(($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) === FALSE) { 
				$insertArray = array(
					"tstamp" => time(),
					"crdate" => time(),
					"cruser_id" => 1,
					"title" => $folder->getName(),
					"uid_parent" => 0,
					"identifier" => $folder->getIdentifier()
				);
				$folderRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')->get('Ameos\AmeosFilemanager\Domain\Repository\FolderRepository');
				$folderRepository->requestInsert($insertArray);
			}
		}
	}

	/**
	 * Call after folder move in filelist
	 * Move the correct folder in the database
	 * @param Folder $folder 
	 * @param Folder $targetFolder 
	 * @param string $newName
	 * @return void
	 */
	public function postFolderMove($folder, $targetFolder, $newName) {
		$folderRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')->get('Ameos\AmeosFilemanager\Domain\Repository\FolderRepository');
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery("uid", "tx_ameosfilemanager_domain_model_folder", "tx_ameosfilemanager_domain_model_folder.identifier like '".$targetFolder->getIdentifier()."'" );
		if(($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== FALSE) {
			if(Tools::getFolderPathFromUid($row['uid']).'/' == $targetFolder->getIdentifier()) {
				$uid_parent = $row['uid'];
			}
		}
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery("uid", "tx_ameosfilemanager_domain_model_folder", "tx_ameosfilemanager_domain_model_folder.identifier like '".$folder->getIdentifier()."'" );
		if(($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== FALSE) {
			if(Tools::getFolderPathFromUid($row['uid']).'/' == $folder->getIdentifier()) {
				$folderRepository->requestUpdate($row['uid'], array("uid_parent"=>$uid_parent,"title"=>$newName));
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
	}

	/**
	 * Call after folder copy in filelist
	 * Copy the correct folder in the database
	 * @param Folder $folder 
	 * @param Folder $targetFolder 
	 * @param string $newName
	 * @return void
	 */
	public function postFolderCopy($folder, $targetFolder, $newName) {
		$folderRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')->get('Ameos\AmeosFilemanager\Domain\Repository\FolderRepository');
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery("uid", "tx_ameosfilemanager_domain_model_folder", "tx_ameosfilemanager_domain_model_folder.identifier like '".$targetFolder->getIdentifier()."'" );
		if(($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== FALSE) {
			if(Tools::getFolderPathFromUid($row['uid']).'/' == $targetFolder->getIdentifier()) {
				$uid_parent = $row['uid'];
			}
		}
		self::setDatabaseForFolder($targetFolder->getSubfolder($newName),$uid_parent);
	}
	
	public function setDatabaseForFolder($folder,$uidParent=0) {
		$folderRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')->get('Ameos\AmeosFilemanager\Domain\Repository\FolderRepository');
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery("uid", "tx_ameosfilemanager_domain_model_folder", "tx_ameosfilemanager_domain_model_folder.identifier like '".$folder->getIdentifier()."'" );
		$exist = false;
		if(($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== FALSE) {
			// Si il n'existe on ne fait rien
			if(Tools::getFolderPathFromUid($row['uid']).'/' == $folder->getIdentifier()) {
				$exist = true;
			}
		}
		if(!$exist) {
			$afmFolder = GeneralUtility::makeInstance('Tx_AmeosFileManager_Domain_Model_Folder');
			$afmFolder->setTitle($folder->getName());
			$afmFolder->setPid(0);
			$afmFolder->setCruser($GLOBALS["BE_USER"]->user["uid"]);
			$afmFolder->setUidParent($uidParent);
			$folderRepository->add($afmFolder);
			GeneralUtility::makeInstance('Tx_Extbase_Persistence_Manager')->persistAll();
			$uid = $afmFolder->getUid();
		}

		foreach ($folder->getFiles() as $file) {
			$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				'sys_file_metadata', 
				'sys_file_metadata.file = '.$file->getUid(), 
				array('folder_uid' => $uid), 
				$no_quote_fields=FALSE
			);
		}

		foreach ($folder->getSubfolders() as $subFolder) {
			self::setDatabaseForFolder($subFolder,$uid);
		}

	}

	/**
	 * Call after folder delete in filelist
	 * Delete the correct folder in the database
	 * @param Folder $folder
	 * @return void
	 */
	public function postFolderDelete($folder) {
		$folderRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')->get('Ameos\AmeosFilemanager\Domain\Repository\FolderRepository');
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery("uid", "tx_ameosfilemanager_domain_model_folder", "tx_ameosfilemanager_domain_model_folder.identifier like '".$folder->getIdentifier()."'" );
		if(($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== FALSE) {
			if(Tools::getFolderPathFromUid($row['uid']).'/' == $folder->getIdentifier())
			{
				$folderRepository->requestDelete($row['uid']);
				break;
			}
		}
	}

	/**
	 * Call after file addition in filelist
	 * Add the file to the correct folder in the database
	 * @param File $file 
	 * @param Folder $targetFolder
	 * @return void
	 */
	public function postFileAdd($file, $targetFolder) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery("uid", "tx_ameosfilemanager_domain_model_folder", "tx_ameosfilemanager_domain_model_folder.identifier like '".$targetFolder->getIdentifier()."'" );
		if(($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== FALSE) {
			if(Tools::getFolderPathFromUid($row['uid']).'/' == $targetFolder->getIdentifier()) {
				$meta = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
					"*", 
					"sys_file_metadata",
					"sys_file_metadata.file = ".$file->getUid()
				);
				if(isset($meta['uid'])) {
					$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
						'sys_file_metadata', 
						'sys_file_metadata.file = '.$file->getUid(), 
						array("folder_uid" => $row['uid']), 
						$no_quote_fields=FALSE
					);
				}
				else {
					$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery(
						'sys_file_metadata', 
						array("file" => $file->getUid(), "folder_uid" => $row['uid'], 'tstamp' => time(), 'crdate'=>time()), 
						$no_quote_fields=FALSE
					);
				}
			}
		}
	}

	/**
	 * Call after file copy in filelist
	 * Copy the file to the correct folder in the database
	 * @param File $file 
	 * @param Folder $targetFolder
	 * @return void
	 */
	public function postFileCopy($file, $targetFolder) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery("uid", "tx_ameosfilemanager_domain_model_folder", "tx_ameosfilemanager_domain_model_folder.identifier like '".$targetFolder->getIdentifier()."'" );
		if(($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== FALSE) {
			if(Tools::getFolderPathFromUid($row['uid']).'/' == $targetFolder->getIdentifier()) {
				$newFile = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
					"uid", 
					"sys_file",
					"sys_file.identifier = '".$targetFolder->getIdentifier().$file->getName()."'"
				);
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					'sys_file_metadata', 
					'sys_file_metadata.file = '.$newFile['uid'], 
					array("folder_uid" => $row['uid']), 
					$no_quote_fields=FALSE
				);
			}
		}
	}

	/**
	 * Call after file move in filelist
	 * Move the file to the correct folder in the database
	 * @param File $file 
	 * @param Folder $targetFolder
	 * @return void
	 */
	public function postFileMove($file, $targetFolder) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery("uid", "tx_ameosfilemanager_domain_model_folder", "tx_ameosfilemanager_domain_model_folder.identifier like '".$targetFolder->getIdentifier()."'" );
		if(($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) !== FALSE) {
			if(Tools::getFolderPathFromUid($row['uid']).'/' == $targetFolder->getIdentifier()) {
				$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					'sys_file_metadata', 
					'sys_file_metadata.file = '.$file->getUid(), 
					array("folder_uid" => $row['uid']), 
					$no_quote_fields=FALSE
				);
				break;	
			}
		}
	}
}
