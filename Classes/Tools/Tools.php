<?php

namespace Ameos\AmeosFilemanager\Tools;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class Tools {

	/**
	 * return the image corresponding to the given extension
	 * @param string $type extension of the file
	 * @param string $iconFolder icon to look for the images
	 * @return string
	 */
	public static function getImageIconeTagForType($type,$iconFolder) {
		if(empty($iconFolder)) {
			$iconFolder = 'typo3conf/ext/ameos_filemanager/Resources/Public/Icons/';
		}

		switch ($type) {
			case 'folder':
				if(file_exists($iconFolder.'icon_folder.png')) {
					return '<img src="'.$iconFolder.'icon_folder.png" alt="folder" title="folder" class="icone_file_manager" />';
				}
				else {
					return self::getDefaultIcon($iconFolder);
				}
				break;
			case 'previous_folder':
				if(file_exists($iconFolder.'icon_previous_folder.png')) {
					return '<img src="'.$iconFolder.'icon_previous_folder.png" alt="folder" title="folder" class="icone_file_manager" />';
				}
				else {
					return self::getDefaultIcon($iconFolder);
				}
				break;
			default:
				if(file_exists($iconFolder.'icon_'.$type.'.png')) {
					return '<img src="'.$iconFolder.'icon_'.$type.'.png" alt="file" title="file" class="icone_file_manager" />';
				}
				else {
					return self::getDefaultIcon($iconFolder);
				}
				break;
		}
	}

	/**
	 * return the default icon
	 * @param string $iconFolder icon to look for the images
	 * @return string
	 */
	public static function getDefaultIcon($iconFolder) {
		if(file_exists($iconFolder.'icon_default_file.png')) {
			return '<img src="'.$iconFolder.'icon_default_file.png" alt="file" title="file" class="icone_file_manager" />';
		}
		else {
			return '<img src="typo3conf/ext/ameos_filemanager/Resources/Public/Icons/icon_default_file.png" alt="file" title="file" class="icone_file_manager" />';
		}
	}
	
	/**
	 * check if user has read permission to the folder
	 * @param array $user current user
	 * @param \Ameos\AmeosFilemanager\Domain\Model\Folde $folder
	 * @param array $arguments array of other arguments for hooks
	 * @return boolean
	 */
	public static function userHasFolderReadAccess($user, $folder, $arguments = null) {
		$rootFolder = $arguments['folderRoot'] ? $arguments['folderRoot'] : null;
        if(!$folder instanceof \Ameos\AmeosFilemanager\Domain\Model\Folder || !$folder->isChildOf($rootFolder)){
            return false;
        }
		// Hooks to forbid read permission to a file if necessary
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Tx_AmeosFilemanager_Tools_Tools']['userHasFolderReadAccess'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Tx_AmeosFilemanager_Tools_Tools']['userHasFolderReadAccess'] as $classData) {
				$hookObject = GeneralUtility::getUserObj($classData);
				if(method_exists($hookObject, 'userHasNotFolderReadAccess') && $hookObject->userHasNotFolderReadAccess($user, folder, $arguments)) {
					return false;
				}
			}
		}
		if($user) {
			if( $folder->getNoReadAccess() && (!is_object($folder->getFeUser()) || $folder->getFeUser()->getUid() != $user['uid'])) {
				return false;
			}
		}
		$folderRepository = GeneralUtility::makeInstance('Ameos\AmeosFilemanager\Domain\Repository\FolderRepository');
		if($exist = $folderRepository->findByUid($folder->getUid())) {
			return true;
		}
		return false;
	}

	/**
	 * check if user has read permission to the file
	 * @param array $user current user
	 * @param \Ameos\AmeosFilemanager\Domain\Model\File $file
	 * @param array $arguments array of other arguments for hooks
	 * @return boolean
	 */
	public static function userHasFileReadAccess($user, $file, $arguments = null) {
		$rootFolder = $arguments['folderRoot'] ? $arguments['folderRoot'] : null;
		// Hooks to forbid read permission to a file if necessary
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Tx_AmeosFilemanager_Tools_Tools']['userHasFileReadAccess'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Tx_AmeosFilemanager_Tools_Tools']['userHasFileReadAccess'] as $classData) {
				$hookObject = GeneralUtility::getUserObj($classData);
				// Don't forget to test $user in your hook
				if(method_exists($hookObject, 'userHasNotFileReadAccess') && $hookObject->userHasNotFileReadAccess($user, $file, $arguments)) {
					return false;
				}
			}
		}
		if($user) {
			if($file->getNoReadAccess() && (!is_object($file->getFeUser()) || $file->getFeUser()->getUid() != $user['uid'])) {
				return false;
			}
		}
		if($file->getArrayFeGroupRead()) {
			$fileRepository = GeneralUtility::makeInstance('Ameos\AmeosFilemanager\Domain\Repository\FileRepository');
			if($exist = $fileRepository->findByUid($file->getUid())) {
				return true;
			}
		}
		else{
			return self::userHasFolderReadAccess($user,$file->getParentFolder(),$arguments);
		}
		return false;
	}


	/**
	 * check if user has write permission to the folder
	 * @param array $user current user
	 * @param \Ameos\AmeosFilemanager\Domain\Model\Folde $folder
	 * @param array $arguments array of other arguments for hooks
	 * @return boolean
	 */
	public static function userHasFolderWriteAccess($user, $folder, $arguments = null) {
		$rootFolder = $arguments['folderRoot'] ? $arguments['folderRoot'] : null;
		if(!$folder instanceof \Ameos\AmeosFilemanager\Domain\Model\Folder && !$folder->isChildOf($rootFolder)){
            return false;
        }
		// Hooks to forbid read permission to a file if necessary
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Tx_AmeosFilemanager_Tools_Tools']['userHasFolderWriteAccess'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Tx_AmeosFilemanager_Tools_Tools']['userHasFolderWriteAccess'] as $classData) {
				$hookObject = GeneralUtility::getUserObj($classData);
				// Don't forget to test $user in your hook
				if(method_exists($hookObject, 'userHasNotFolderWriteAccess') && $hookObject->userHasNotFolderWriteAccess($user, $folder, $arguments)) {
					return false;
				}
			}
		}
		if($user) {
			if($folder->getNoWriteAccess() && (!is_object($folder->getFeUser()) || $folder->getFeUser()->getUid() != $user['uid'])) {
				return false;
			}
		}
		$folderRepository = GeneralUtility::makeInstance('Ameos\AmeosFilemanager\Domain\Repository\FolderRepository');
		if($exist = $folderRepository->findByUid($folder->getUid(),$writeMode = true)) {
			return true;
		}
		return false;
	}


	/**
	 * check if user has write permission to the file
	 * @param array $user current user
	 * @param \Ameos\AmeosFilemanager\Domain\Model\File $file
	 * @param array $arguments array of other arguments for hooks
	 * @return boolean
	 */
	public static function userHasFileWriteAccess($user, $file, $arguments = null) {
		$rootFolder = $arguments['$folderRoot'] ? $arguments['$folderRoot'] : null;
		// Hooks to forbid write permission to a file if necessary
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Tx_AmeosFilemanager_Tools_Tools']['userHasFileWriteAccess'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['Tx_AmeosFilemanager_Tools_Tools']['userHasFileWriteAccess'] as $classData) {
				$hookObject = GeneralUtility::getUserObj($classData);
				// Don't forget to test $user in your hook
				if(method_exists($hookObject, 'userHasNotFileWriteAccess') && $hookObject->userHasNotFileWriteAccess($user, $file, $arguments)) {
					return false;
				}
			}
		}
		if($user) {
			if($file->getNoWriteAccess() && (!is_object($file->getFeUser()) || $file->getFeUser()->getUid() != $user['uid'])) {
				return false;
			}
		}
		if($file->getArrayFeGroupWrite()) {
			$fileRepository = GeneralUtility::makeInstance('Ameos\AmeosFilemanager\Domain\Repository\FileRepository');
			if($exist = $fileRepository->findByUid($file->getUid(),$writeMode = true)) {
				return true;
			}
		}
		else {
			return self::userHasFolderWriteAccess($user,$file->getParentFolder(),$arguments);
		}
		return false;
	}

	/**
	 * download the file and log the download in the DB
	 * @param integer $uidFile uid of the file
	 * @return void
	 */
	public static function downloadFile($uidFile,$folderRoot=null) {
		$fileRepository = GeneralUtility::makeInstance('Ameos\AmeosFilemanager\Domain\Repository\FileRepository');
		$file = $fileRepository->findByUid($uidFile);
		$user = ($GLOBALS['TSFE']->fe_user->user);

        // We check if the user has access to the file.
        if(Tools::userHasFileReadAccess($user, $file, array("folderRoot" => $folderRoot)))
		{
			if($file) {
				$filename = urldecode($file->getPublicUrl());
			}

			if (file_exists($filename)) {
				// We register who downloaded the file and when
				$filedownloadRepository = GeneralUtility::makeInstance('Ameos\AmeosFilemanager\Domain\Repository\FiledownloadRepository');
				$filedownload = GeneralUtility::makeInstance('Ameos\AmeosFilemanager\Domain\Model\Filedownload');
				$filedownload->setFile($file);
				$filedownload->setUserDownload($user['uid']);
				$filedownloadRepository->add($filedownload);
				$persitenceManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager');
				$persitenceManager->persistAll();

			    // Download of the file
			    header('Content-Description: File Transfer');
			    header('Content-Type: application/octet-stream');
			    header('Content-Disposition: attachment; filename='.basename($filename));
			    header('Expires: 0');
			    header('Cache-Control: must-revalidate');
			    header('Pragma: public');
			    header('Content-Length: ' . filesize($filename));
			    ob_clean();
			    flush();
			    readfile($filename);
			    exit;
			}
		}
		else {
			header('HTTP/1.1 403 Forbidden');
			$message = $GLOBALS["TSFE"]->tmpl->setup["plugin."]["tx_ameosfilemanager."]["settings."]["forbidden"] ?: "Access denied";
			exit($message);
		}
	}

	/**
	 * return objects of $repo where uid in $uids
	 * @param Repository $repo
	 * @param array $uids
	 * @return object
	 */
	public static function getByUids($repo,$uids) {
		if(!is_array($uids)) {
			$uids = explode(',', $uids);
		}
		$query = $repo->createQuery();
		$query->matching($query->in('uid', $uids));
		return $query->execute();
	}

	/**
	 * return folder parent
	 * @param integer $uid uid of the child folder
	 * @return string
	 */
	public static function getFolderPathFromUid($uid) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
			'uid_parent, title',
			'tx_ameosfilemanager_domain_model_folder',
			'tx_ameosfilemanager_domain_model_folder.uid = '.$uid
		);
		if($res['uid_parent'] != '' && $res['uid_parent'] != 0) {
			return self::getFolderPathFromUid($res['uid_parent']).'/'.$res['title'];
		}
		return '/'.$res['title'];
	}

	public static function parseFolderForNewElements($storage,$folderIdentifier,$folderName) {
		$slot = GeneralUtility::makeInstance('Ameos\AmeosFilemanager\Slots\Slot');
		$falFolder = GeneralUtility::makeInstance('TYPO3\CMS\Core\Resource\Folder',$storage,$folderIdentifier,$folderName);
		$subfolders = $falFolder->getSubfolders();
		foreach ($subfolders as $folder) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery("uid", "tx_ameosfilemanager_domain_model_folder", "tx_ameosfilemanager_domain_model_folder.title like '".$folder->getName()."'" );
			$exist = false;
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				// Si il n'existe on ne fait rien
				if(Tools::getFolderPathFromUid($row['uid']).'/' == $folder->getIdentifier()) {
					$exist = true;
					$uid = $row['uid'];
					break;
				}
			}
			if(!$exist) {
				$slot->postFolderAdd($folder);
			}
		}
		
		$files = $falFolder->getFiles();
		foreach ($files as $file) {
			$slot->postFileAdd($file,$falFolder);
		}
	}
}
