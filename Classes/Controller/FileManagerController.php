<?php

namespace Ameos\AmeosFilemanager\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Ameos\AmeosFilemanager\Tools\Tools;

class FileManagerController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * @var \Ameos\AmeosFilemanager\Domain\Repository\FolderRepository
	 */
	protected $folderRepository;

	/**
	 * @var \Ameos\AmeosFilemanager\Domain\Repository\FileRepository
	 */
	protected $fileRepository;

	/**
	 * @var \Ameos\AmeosFilemanager\Domain\Repository\FiledownloadRepository
	 */
	protected $filedownloadRepository;

	/**
	 * @var \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserGroupRepository
	 * @inject
	 */
	protected $feGroupRepository;

	/**
	 * @var \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository
	 */
	protected $feUserRepository;

	/**
	 * @var \TYPO3\CMS\Extbase\Domain\Repository\BackendUserRepository
	 */
	protected $beUserRepository;

	/**
	 * @var \TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository
	 */
	protected $categoryRepository;

	/**
	 * Dependency injection of the file Repository
	 * @param \Ameos\AmeosFilemanager\Domain\Repository\FolderRepository $folderRepository
 	 * @return void
	 */
	public function injectFolderRepository(\Ameos\AmeosFilemanager\Domain\Repository\FolderRepository $folderRepository) {$this->folderRepository = $folderRepository;}

	/**
	 * Dependency injection of the file Repository
	 * @param \Ameos\AmeosFilemanager\Domain\Repository\FileRepository $fileRepository
 	 * @return void
	 */
	public function injectFileRepository(\Ameos\AmeosFilemanager\Domain\Repository\FileRepository $fileRepository) {$this->fileRepository = $fileRepository;}

	/**
	 * Dependency injection of the filedownload Repository
	 * @param \Ameos\AmeosFilemanager\Domain\Repository\FiledownloadRepository $filedownloadRepository
 	 * @return void
	 */
	public function injectFiledownloadRepository(\Ameos\AmeosFilemanager\Domain\Repository\FiledownloadRepository $filedownloadRepository) {$this->filedownloadRepository = $filedownloadRepository;}

	/**
	 * Dependency injection of the file Repository
	 * @param \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserGroupRepository $feGroupRepository
 	 * @return void
	 */
	public function injectFrontendUserGroupRepository(\TYPO3\CMS\Extbase\Domain\Repository\FrontendUserGroupRepository $feGroupRepository) {$this->feGroupRepository = $feGroupRepository;}
	
	/**
	 * Dependency injection of the file Repository
	 * @param \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository $feUserRepository
 	 * @return void
	 */
	public function injectFrontendUserRepository(\TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository $feUserRepository) {$this->feUserRepository = $feUserRepository;}
	
	/**
	 * Dependency injection of the file Repository
	 * @param \TYPO3\CMS\Extbase\Domain\Repository\BackendUserRepository $beUserRepository
 	 * @return void
	 */
	public function injectBackendUserRepository(\TYPO3\CMS\Extbase\Domain\Repository\BackendUserRepository $beUserRepository) {$this->beUserRepository = $beUserRepository;}
	
	/**
	 * Dependency injection of the category Repository
	 * @param \TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository $categoryRepository
 	 * @return void
	 */
	public function injectCategoryRepository(\TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository  $categoryRepository) {$this->categoryRepository = $categoryRepository;}


	/**
	 * Initialization of all actions.
	 * Check if the plugin is correctly configured and set the basic variables.
	 *
	 * @return void
	 */
	public function initializeAction() {
		$this->user = ($GLOBALS['TSFE']->fe_user->user);

		if($this->settings['startFolder'] != '') {
			$this->startFolder = $this->settings['startFolder'];
		}
		else {
			throw new Exception("The root folder was not configured. Please add it in plugin configuration.");
		}
		// Setting feUser Repository
		if($this->settings['stockageGroupPid']!='') {
		  	$querySettings = $this->feGroupRepository->createQuery()->getQuerySettings();
		  	$querySettings->setStoragePageIds(array($this->settings['stockageGroupPid']));
		  	$this->feGroupRepository->setDefaultQuerySettings($querySettings);
		}
		else {
			throw new Exception("The user folder was not configured. Please add it in plugin configuration.");
		}
		// Setting storage folder, return error if not set or not found.
		if($this->settings['storage']) {
			$this->storageUid = $this->settings['storage'];
		}
		else {
			throw new Exception("The storage folder was not configured. Please add it in plugin configuration.");
		}
		$storageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
		$this->storage = $storageRepository->findByUid($this->storageUid);
		if($this->storage == null) {
			throw new Exception("Storage folder not found. Please check configuration");
		}
		// Setting list of usergroups to send to form actions
		if($this->settings['authorizedGroups']) {
			$this->authorizedGroups = $this->settings['authorizedGroups'];
		}
		// Setting list of categories to send to form actions
		if($this->settings['authorizedCategories']) {
			$this->authorizedCategories = $this->settings['authorizedCategories'];
		}
	}

	/**
	 * Download file if file uid is set
	 * Display the files/folders of the current folder otherwise
	 *
	 * @return void
	 */
	public function indexAction() {
		$args = GeneralUtility::_GET('tx_ameos_filemanager');
		if($args['file']) {
			Tools::downloadFile($args['file'],$this->settings['startFolder']);
		}
		$startFolder = $args['folder'] ?: $this->settings['startFolder'];
		$folder = $this->folderRepository->findByUid($startFolder);
		if(!$folder->isChildOf($this->startFolder)) {
			return LocalizationUtility::translate('accessDenied', 'ameos_filemanager');
		}
		if($this->settings["parseFolderInFE"]) {
            Tools::parseFolderForNewElements($this->storage,$folder->getGedPath(),$folder->getTitle());
		}
		$this->settings['columnsTable'] = explode(',', $this->settings['columnsTable']);
		$this->settings['actionDetail'] = explode(',', $this->settings['actionDetail']);
		$this->view->assign('settings', $this->settings);
		$this->view->assign('folder',$folder);
		$this->view->assign('files',$this->fileRepository->findFilesForFolder($startFolder));
	}

	/**
	 * File form
	 *
	 * @return void
	 */
	public function formFileAction() {
		$args = GeneralUtility::_GET('tx_ameos_filemanager');
		$folder = $args['folder'] ?: $this->settings['startFolder'];
		$editFileUid = $args['newFile'];
		if($editFileUid != '' && $newFile = $this->fileRepository->findByUid($editFileUid)) {
			if(!Tools::userHasFileWriteAccess($this->user, $newFile, array('folderRoot' => $this->settings['startFolder']))) {
				return LocalizationUtility::translate('accessDenied', 'ameos_filemanager');
			}
			$metaDataRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository');
			$meta = $metaDataRepository->findByFileUid($newFile->getUid());

			$fileArgs = array();
			$fileArgs['title'] = $meta['title'];
			$fileArgs['arrayFeGroupRead'] = explode(',', $meta['fe_group_read']);
			$fileArgs['arrayFeGroupWrite'] = explode(',', $meta['fe_group_write']);
			$fileArgs['description'] = $meta['description'];
			$fileArgs['keywords'] = $meta['keywords'];
			$fileArgs['noReadAccess'] = $meta['no_read_access'];
			$fileArgs['noWriteAccess'] = $meta['no_write_access'];

			$this->view->assign('properties',$fileArgs);
			$this->view->assign('file',$newFile);
			$this->view->assign('parentFolder',$newFile->getParentFolder()->getUid());
			$this->view->assign('uidFile',$newFile->getUid());
		}
		else {
			if(!Tools::userHasFolderWriteAccess($this->user, $this->folderRepository->findByUid($folder))) {
				return LocalizationUtility::translate('accessDenied', 'ameos_filemanager');
			}
			$this->view->assign('parentFolder',$folder);
		}
		// Setting userGroup list
		if($this->authorizedGroups!='') {
			$feGroup = Tools::getByUids($this->feGroupRepository,$this->authorizedGroups)->toArray();
			if(\TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->authorizedGroups,-2)) {
				$temp = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Domain\\Model\\FrontendUserGroup');
				$temp->_setProperty('uid',-2);
				$temp->setTitle(LocalizationUtility::translate('LLL:EXT:lang/locallang_general.xlf:LGL.any_login',null));
				$feGroup[] = $temp;	
			}
		}
		else {
			$feGroup = $this->feGroupRepository->findAll()->toArray();
			$temp = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Domain\\Model\\FrontendUserGroup');
			$temp->_setProperty('uid',-2);
			$temp->setTitle(LocalizationUtility::translate('LLL:EXT:lang/locallang_general.xlf:LGL.any_login',null));
			$feGroup[] = $temp;
		}
		// Setting category list
		if($this->authorizedCategories != '') {
			$categorieUids = explode(',', $this->authorizedCategories);
			$categories = Tools::getByUids($this->categoryRepository,$this->authorizedCategories);
		}
		else {
			$categories = $this->categoryRepository->findAll();
		}
		// if errors, display them.
		if($args['errors']) {
			$this->view->assign('errors',$args['errors']);
			$this->view->assign('properties',$args['properties']);
		}
		$this->view->assign('feGroup',$feGroup);
		$this->view->assign('categories',$categories);
	}

	/**
	 * Creates or update a file then redirect to the parent directory
	 *
	 * @return void
	 */
	public function createFileAction() {
			$fileArgs = $this->request->getArguments();
	        $folder = $this->folderRepository->findByUid($fileArgs['uidParent']);
	       	
			$storageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
			$storage = $storageRepository->findByUid($this->storageUid);
			$properties = array();
			$errors = array();
			//if an uid is sent, we update an existing file, if not we create a new one.
	        if($fileArgs['uidFile'] != '') {
	        	$fileModel = $this->fileRepository->findByUid($fileArgs['uidFile']);
	        	if(!Tools::userHasFileWriteAccess($this->user, $fileModel,array('folderRoot' => $this->settings['startFolder']))) {
	        		return LocalizationUtility::translate('accessDenied', 'ameos_filemanager');
	        	}
				if($fileArgs['file']['tmp_name'] != '') {
					if (file_exists($storage->getConfiguration()['basePath'].$fileModel->getParentFolder()->getGedPath().'/'.$fileArgs['file']['name'])) {
						$errors['file'] = LocalizationUtility::translate('fileAlreadyExist', 'ameos_filemanager');	
					}
					else if (!move_uploaded_file($fileArgs['file']['tmp_name'], $storage->getConfiguration()['basePath'].$folder->getGedPath().'/'.$fileArgs['file']['name'])) {
					    $errors['file'] = LocalizationUtility::translate('fileUploadError', 'ameos_filemanager');
					}
					else {
						$someFileIdentifier = $folder->getGedPath().'/'.$fileArgs['file']['name']; 
						$storage->replaceFile($fileModel->getOriginalResource(),'fileadmin/'.$someFileIdentifier);
						$storage->renameFile($fileModel->getOriginalResource(), $fileArgs['file']['name']);	
					}
				}
	        }
	        else {
	        	if(!Tools::userHasFolderWriteAccess($this->user, $folder,array('folderRoot' => $this->settings['startFolder']))) {
					return LocalizationUtility::translate('accessDenied', 'ameos_filemanager');
	        	}
	        	$properties['fe_user_id'] = $GLOBALS['TSFE']->fe_user->user['uid'];
	        	if($fileArgs['file']['tmp_name'] == '') {
                    $errors['file'] = LocalizationUtility::translate('fileNotUploaded', 'ameos_filemanager');
	        	}
                else if (file_exists($storage->getConfiguration()['basePath'].$folder->getGedPath().'/'.$fileArgs['file']['name'])) {
                    $errors['file'] = LocalizationUtility::translate('fileAlreadyExist', 'ameos_filemanager');
                }
                else if (!move_uploaded_file($fileArgs['file']['tmp_name'], $storage->getConfiguration()['basePath'].$folder->getGedPath().'/'.$fileArgs['file']['name'])) {
                    $errors['file'] = LocalizationUtility::translate('fileUploadError', 'ameos_filemanager');
                }
                $someFileIdentifier = $folder->getGedPath().'/'.$fileArgs['file']['name'];
                $fileObj = $storage->getFile($someFileIdentifier);
            }

            // If errors, redirect to form with array erros.
            if(!empty($errors)) {
                $resultUri = $this->uriBuilder
                    ->reset()
                    ->setCreateAbsoluteUri(true)
                    ->setArguments(array('tx_ameos_filemanager' => array('newFile' => $fileArgs['uidFile'], 'errors' => $errors,'folder' => $fileArgs['uidParent'], 'properties' => $fileArgs)))
                    ->uriFor('formFile');

                $this->redirectToUri($resultUri);
            }
            else {
                $persitenceManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager');
                $persitenceManager->persistAll();
                if(!isset($fileModel)) {
                    $fileModel = $this->fileRepository->findByUid($fileObj->getUid());
                }
            }

        // Setting metadatas
        if($fileArgs['title']) {
        	$properties['title'] = $fileArgs['title'];
        }
        if($fileArgs['arrayFeGroupRead']) {
        	$properties['fe_group_read'] = implode(',', $fileArgs['arrayFeGroupRead']);
        }
        if($fileArgs['arrayFeGroupWrite']) {
       		$properties['fe_group_write'] = implode(',', $fileArgs['arrayFeGroupWrite']);
        }
        if($fileArgs['description']) {
        	$properties['description'] = $fileArgs['description'];
        }
        if($fileArgs['keywords']) {
        	$properties['keywords'] = $fileArgs['keywords'];
        }
        if($fileArgs['noReadAccess']) {
            $properties['no_read_access'] = $fileArgs['noReadAccess'];
        }
	    else {
	        $properties['no_read_access'] = 0;
	    }
        if($fileArgs['noWriteAccess']) {
            $properties['no_write_access'] = $fileArgs['noWriteAccess'];
        }
    	else {
        	$properties['no_write_access'] = 0;
    	}
        if($fileArgs['categories']){
            $properties['categories'] = count($fileArgs['categories']);
            $fileModel->setCategories($fileArgs['categories']);
        }
        if($fileArgs['uidFile'] != '') {
            $metaDataRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository');
				$metaDataRepository->update($fileModel->getUid(),$properties);
				if($fileArgs['categories']) {
		            $fileModel->setCategories($fileArgs['categories']);
				}
        }
        else {
	    	$properties['folder_uid'] = $folder->getUid();
	    	$metaDataRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Index\\MetaDataRepository');
	    	$metaDataRepository->update($fileObj->getUid(),$properties);
	    	$persitenceManager = GeneralUtility::makeInstance('TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager');
            $persitenceManager->persistAll();
            
            if(!isset($fileModel)) {
                $fileModel = $this->fileRepository->findByUid($fileObj->getUid());
            }
            if($fileArgs['categories']) {
	            $fileModel->setCategories($fileArgs['categories']);
	        }
        }
        
        $resultUri = $this->uriBuilder
        ->reset()
        ->setCreateAbsoluteUri(true)
        ->setArguments(array('tx_ameos_filemanager' => array('folder' => $folder)))
        ->uriFor('index');
        
		$this->redirectToUri($resultUri);
	}

	/**
	 * Folder form
	 *
	 * @return void
	 */
	public function formFolderAction() {
		$args = GeneralUtility::_GET('tx_ameos_filemanager');
		$editFolderUid = $args['newFolder'];
		
		// We are editing a folder
		if($editFolderUid != '') {
			if($newFolder = $this->folderRepository->findByUid($editFolderUid,$writeRight=true)) {
				$this->view->assign('folder',$newFolder);
				$this->view->assign('parentFolder',$newFolder->getParent()->getUid());
			}
			else {
				return LocalizationUtility::translate('accessDenied', 'ameos_filemanager');
			}
		}
		// We are creating a folder
		else {
			$folderUid = $args['folder'] ?: $this->settings['startFolder'];
			if($folderParent = $this->folderRepository->findByUid($folderUid ,$writeRight=true)) {
				$this->view->assign('parentFolder',$folderParent->getUid());
			}
			else {
				return LocalizationUtility::translate('accessDenied', 'ameos_filemanager');
			}
		}

		if($this->authorizedCategories != '') {
            $categorieUids = explode(',', $this->authorizedCategories);
            $categories = Tools::getByUids($this->categoryRepository,$this->authorizedCategories);
        }
        else {
            $categories = $this->categoryRepository->findAll();
        }

        if($this->authorizedGroups!='') {
			$feGroup = Tools::getByUids($this->feGroupRepository,$this->authorizedGroups)->toArray();
			if(\TYPO3\CMS\Core\Utility\GeneralUtility::inList($this->authorizedGroups,-2)) {
				$temp = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Domain\\Model\\FrontendUserGroup');
				$temp->_setProperty('uid',-2);
				$temp->setTitle(LocalizationUtility::translate('LLL:EXT:lang/locallang_general.xlf:LGL.any_login',null));
				$feGroup[] = $temp;	
			}
		}
		else {
			$feGroup = $this->feGroupRepository->findAll()->toArray();
			$temp = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Domain\\Model\\FrontendUserGroup');
			$temp->_setProperty('uid',-2);
			$temp->setTitle(LocalizationUtility::translate('LLL:EXT:lang/locallang_general.xlf:LGL.any_login',null));
			$feGroup[] = $temp;
		}

		if($args['errors']) {
			$this->view->assign('errors',$args['errors']);
			$this->view->assign('folder',$args['currentState']);
			$this->view->assign('parentFolder',$args['currentState']['uidParent']);
		}
		
        $this->view->assign('categories',$categories);
		$this->view->assign('feGroup',$feGroup);
		$this->view->assign('returnFolder',$args['returnFolder']);

	}

	/**
	 * Creates or update a folder then redirect to the parent directory
	 *
	 * @return void
	 */
	public function createFolderAction() {
			$fileArgs = $this->request->getArguments();
			$parent = $this->folderRepository->findByUid($fileArgs['uidParent']);
			$errors = array();

			// No uid so we are in create mode
			if($fileArgs['uidFolder'] == '') {
				if(!Tools::userHasFolderWriteAccess($this->user, $parent,array('folderRoot' => $this->settings['startFolder']))) {
					return LocalizationUtility::translate('accessDenied', 'ameos_filemanager');
				}
				$newFolder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Ameos\\AmeosFilemanager\\Domain\\Model\\Folder');
				$newFolder->setFeUser($GLOBALS['TSFE']->fe_user->user['uid']);
				// Needed if an error is detected.
				$fileArgs['feUser'] = $GLOBALS['TSFE']->fe_user->user['uid'];
				if($parent->hasFolder($newFolder->getTitle())) {
					$errors['title'] = "Folder already exists";
				}
			}
			// edit mode
			else {
				$exFolderQuery = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow("*", "tx_ameosfilemanager_domain_model_folder", "tx_ameosfilemanager_domain_model_folder.uid = ".$fileArgs['uidFolder'] );
	        	
	        	//checking if user had the right to update this folder BEFORE the edition.
	        	$newFolder = $this->folderRepository->findByUid($fileArgs['uidFolder']);
	        	if(!Tools::userHasFolderWriteAccess($this->user, $newFolder,array('folderRoot' => $this->settings['startFolder']))) {
					return LocalizationUtility::translate('accessDenied', 'ameos_filemanager');
				}
				if($parent->hasFolder($newFolder->getTitle(),$newFolder->getUid())) {
					$errors['title'] = "Folder already exists";
				}
			}
			
			if(empty($fileArgs['title'])) {
				$errors['title'] = 'Folder title cannot be empty';
			}

			if(!empty($errors)) {
				$resultUri = $this->uriBuilder
		        ->reset()
		        ->setCreateAbsoluteUri(true)
		        ->setArguments(array('tx_ameos_filemanager' => array('newFolder' => $fileArgs['uidFile'], 'errors' => $errors,'folder' => $fileArgs['uidParent'], 'currentState' => $fileArgs)))
		        ->uriFor('formFolder');
		        
				$this->redirectToUri($resultUri);
			}

			$storageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
			$storage = $storageRepository->findByUid($this->storageUid);
			$localDriver = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\Driver\\LocalDriver');

			// Editing folder
			$newFolder->setTitle($localDriver->sanitizeFileName($fileArgs['title']));
			$newFolder->setDescription($fileArgs['description']);
			$newFolder->setKeywords($fileArgs['keywords']);
			$newFolder->setNoReadAccess($fileArgs['noReadAccess']);
			$newFolder->setNoWriteAccess($fileArgs['noWriteAccess']);
			$newFolder->setArrayFeGroupRead($fileArgs['arrayFeGroupRead']);
			$newFolder->setArrayFeGroupWrite($fileArgs['arrayFeGroupWrite']);
            $newFolder->setCategories($fileArgs['categories']);
			$newFolder->setUidParent($parent);

			$this->folderRepository->add($newFolder);

			if($fileArgs['uidFolder'] != '') {
				$storageFolder = $storage->getFolder($newFolder->getParent()->getGedPath().'/'.$exFolderQuery['title'].'/');
				$storageFolder->rename($newFolder->getTitle());

				if($fileArgs['returnFolder'] != '') {
					$resultUri = $this->uriBuilder
			       ->setCreateAbsoluteUri(true)
			       ->setArguments(array('tx_ameos_filemanager' => array('folder' => $fileArgs['returnFolder'])))
			       ->buildFrontendUri();
			   	}
			   	else {
			      	$resultUri = $this->uriBuilder
			       ->setCreateAbsoluteUri(true)
			       ->setArguments(array('tx_ameos_filemanager' => array('folder' => $fileArgs['uidFolder'])))
			       ->buildFrontendUri();
			   	}

	        }
	        else {
				$storageFolder = $storage->getFolder($parent->getGedPath().'/');
				$storageFolder->createFolder($newFolder->getTitle());
				
		       if($fileArgs['returnFolder'] != '') {
					$resultUri = $this->uriBuilder
				       ->setCreateAbsoluteUri(true)
				       ->setArguments(array('tx_ameos_filemanager' => array('folder' => $fileArgs['returnFolder'])))
				       ->buildFrontendUri();
			   	}
			   	else {
			      	$resultUri = $this->uriBuilder
				       ->setCreateAbsoluteUri(true)
				       ->setArguments(array('tx_ameos_filemanager' => array('folder' => $parent->getUid())))
				       ->buildFrontendUri();
			   	}
	        }
	        
			$this->redirectToUri($resultUri);
	}

	/**
	 * Set the research arguments and send them to the list action
	 *
	 * @return void
	 */
	public function searchAction() {
		$arguments = array();
		if($this->request->hasArgument('keyword')) {
			if ($this->request->getArgument('keyword') !== '') {
				$arguments['keyword'] = $this->request->getArgument('keyword');
			}
		}
        if(GeneralUtility::_POST('tx_ameosfilemanager_fe_filemanager_search')['keyword'] && GeneralUtility::_POST('tx_ameosfilemanager_fe_filemanager_search')['keyword'] != '') {
            $arguments['keyword'] = GeneralUtility::_POST('tx_ameosfilemanager_fe_filemanager_search')['keyword'];
        }
		$resultUri = $this->uriBuilder
		->reset()
		->setCreateAbsoluteUri(true)
		->setArguments(array('tx_ameos_filemanager' => $arguments))
     	->uriFor('list');

		$this->redirectToUri($resultUri);
	}

	/**
	 * Display a list of files matching the given arguments
	 *
	 * @return void
	 */
	public function listAction() {
		$args = (array)GeneralUtility::_GET('tx_ameos_filemanager');
		$t = $this->fileRepository->findBySearchCriterias($args);
		$this->view->assign('files', $t);
		$this->view->assign('value', $args);
		$this->settings['columnsTable'] = explode(',', $this->settings['columnsTable']);
		$this->settings['actionDetail'] = explode(',', $this->settings['actionDetail']);
		$this->view->assign('settings', $this->settings);
	}

	/**
	 * Details of a file
	 *
	 * @return void
	 */
	public function detailAction() {
		$args = GeneralUtility::_GET('tx_ameos_filemanager');
		if($args['file'] && $file = $this->fileRepository->findByUid($args['file'])) {
			$this->view->assign('file', $file);
		}
		else {
			return LocalizationUtility::translate('fileNotFound', 'ameos_filemanager');
		}
		$this->view->assign('settings', $this->settings);
	}


	/**
	 * Delete the folder given in arguments
	 *
	 * @return void
	 */
	public function deleteFolderAction() {
		if($this->request->hasArgument('folder')) {
			$folder = $this->folderRepository->findByUid($this->request->getArgument('folder'));
			if($folder && Tools::userHasFolderWriteAccess($this->user, $folder, array('folderRoot' => $this->startFolder))){
				if($folder->getIdentifier()){
					$ebFolder = $this->storage->getFolder($folder->getIdentifier());
					$this->storage->deleteFolder($ebFolder);
					$this->folderRepository->remove($folder);
				}
			}
		}
	}

	/**
	 * Delete the file given in arguments
	 *
	 * @return void
	 */
	public function deleteFileAction() {
		if($this->request->hasArgument('file')) {
			$file = $this->fileRepository->findByUid($this->request->getArgument('file'));
			if($file && Tools::userHasFileWriteAccess($this->user, $file, array('folderRoot' => $this->startFolder))){
				$this->storage->deleteFile($file->getOriginalResource());
			}
		}
	}
}

