<?php
/*=========================================================================
 MIDAS Server
 Copyright (c) Kitware SAS. 26 rue Louis Guérin. 69100 Villeurbanne, FRANCE
 All rights reserved.
 More information http://www.kitware.com

 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at

         http://www.apache.org/licenses/LICENSE-2.0.txt

 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
=========================================================================*/

require_once BASE_PATH.'/modules/curate/constant/module.php';
/** Base model class template for the curate module */
abstract class Curate_CuratedfolderModelBase extends Curate_AppModel {

  /** Constructor */
  public function __construct() {
    parent::__construct();
    $this->_name = 'curate_curatedfolder';
    $this->_key = 'curatedfolder_id';

    $this->_mainData = array(
      'curatedfolder_id' => array('type' => MIDAS_DATA),
      'folder_id' => array('type' => MIDAS_DATA),
      'curation_state' => array('type' => MIDAS_DATA),
      'creation_date' => array('type' => MIDAS_DATA),
      'folder' =>  array('type' => MIDAS_MANY_TO_ONE,
                       'model' => 'Folder',
                       'parent_column' => 'folder_id',
                       'child_column' => 'folder_id'));
    $this->initialize();
  }

  /** tracks the passed in folder for curation.  If the passed in folder
   * had not yet been tracked for curation, will set curate state to construction.
   * requires ADMIN access to the folder.
   */
  function enableFolderCuration($folderDao) {
    if (is_null($folderDao)) {
      throw new Exception('Non-null folder required to enable curation.', -1);
    }

    // if already under curation return the curatedfolder
    $curatedfolderDaos = $this->findBy('folder_id', $folderDao->getFolderId());
    if (count($curatedfolderDaos) > 0) {
      return $curatedfolderDaos[0];
    }

    $curatedfolderDao = MidasLoader::newDao('CuratedfolderDao', 'curate');
    $curatedfolderDao->setFolderId($folderDao->getFolderId());
    $curatedfolderDao->setCurationState(CURATE_STATE_CONSTRUCTION);
    $this->save($curatedfolderDao);
    return $curatedfolderDao;
  }

  /** removes the passed in folder from being tracked for curation.  If the passed
   * in folder was tracked previously returns true, false otherwise.
   * requires ADMIN access to the folder.
   */
  function disableFolderCuration($folderDao) {
    if (is_null($folderDao)) {
      throw new Exception('Non-null folder required to disable curation.', -1);
    }

    $curatedfolderDaos = $this->findBy('folder_id', $folderDao->getFolderId());
    if (count($curatedfolderDaos) ==  0) {
      // if the folder isn't under curation then there is nothing to do
      return false;
    } else {
      $curatedfolderDao = $curatedfolderDaos[0];
      $this->delete($curatedfolderDao);
      return true;
    }
  }

  /** sends a notifcation email to all curation moderators requesting
   * curation approval for the passed in folder, including the passed in
   * message if it is set.
   * sets the curation status of the folder to requested.
   * returns the curatedfolderDao.
   */
  function requestCurationApproval($folderDao, $message = false) {
    if (is_null($folderDao)) {
      throw new Exception('Non-null folder required to request curation approval.', -1);
    }
    $curatedfolderDaos = $this->findBy('folder_id', $folderDao->getFolderId());
    if (count($curatedfolderDaos) ==  0) {
      throw new Exception('folder must be tracked by curation to request curation approval.', -1);
    } else {
      $curatedfolderDao = $curatedfolderDaos[0];
      $curatedfolderDao->setCurationState(CURATE_STATE_REQUESTED);
      $this->save($curatedfolderDao);

      $moderatorModel = MidasLoader::loadModel('Moderator', 'curate');
      $moderators = $moderatorModel->getAllCurationModerators();

      // notify all in combined list
      $utilityComponent = MidasLoader::loadComponent('Utility');
      $body = "Hello Midas Curation Moderator,\nThe curated folder ".$folderDao->getName()." has been requested for curation approval.\n";
      if (false != $message) {
        $body = $body . $message;
      }
      foreach ($moderators as $userId => $moderator) {
        $utilityComponent->sendEmail($moderator->getEmail(), 'Curation Approval Request', $body);
      }

      return $curatedfolderDao;
    }
  }


  /**
   * approves the curation request on a curated folder that is currently
   * in the REQUESTED state, sets the curation state to APPROVED,
   * sends a notifcation email to all users with direct admin
   * access to the passed in folder from a Folderpolicyuser,
   * including the message in the notification email if the message is set.
   * returns the curatedfolderDao.
   */
  function approveCurationRequest($folderDao, $message = false) {
    if (is_null($folderDao)) {
      throw new Exception('Non-null folder required to approve curation request.', -1);
    }
    $curatedfolderDaos = $this->findBy('folder_id', $folderDao->getFolderId());
    if (count($curatedfolderDaos) ==  0) {
      throw new Exception('folder must be tracked by curation to approve curation request.', -1);
    } else {
      $curatedfolderDao = $curatedfolderDaos[0];
      if ($curatedfolderDao->getCurationState() != CURATE_STATE_REQUESTED) {
        throw new Exception('Curated folder must be in the REQUESTED state before approving curation request.', -1);
      }
      $curatedfolderDao->setCurationState(CURATE_STATE_APPROVED);
      $this->save($curatedfolderDao);

      // get all users that are admin for the folder
      $folderpolicyuserModel = MidasLoader::loadModel('Folderpolicyuser');
      $policyDaos = $folderpolicyuserModel->findBy('folder_id', $folderDao->getFolderId());

      $admins = array();
      foreach ($policyDaos as $policyDao) {
        if ($policyDao->getPolicy() == MIDAS_POLICY_ADMIN) {
          $admins[$policyDao->getUserId()] = $policyDao->getUser();
        }
      }

      // notify all admins
      $utilityComponent = MidasLoader::loadComponent('Utility');
      $body = "Hello Midas Curated Folder Owner,\nThe curated folder ".$folderDao->getName()." has been approved for curation.\n";
      if (false != $message) {
        $body = $body . $message;
      }
      foreach ($admins as $userId => $admin) {
        $utilityComponent->sendEmail($admin->getEmail(), 'Curation Approval', $body);
      }

      return $curatedfolderDao;
    }
  }

  /** gets all curated folders the user has the given policy access on **/
  abstract public function getAllFiltered($userDao, $policy);
  /** Get the total download counts for all items in a folder's subtree,
   * (with no filtered results). */
  abstract public function getFolderDownloadCounts($folder);

  /**
   * lists all of the curated folders that a user has Read access to,
   * including a total count of their item sizes and download counts for
   * full subtrees of the curated folder.
   */
  function listAllCuratedFolders($userDao) {
    $curatedfolderDaos = $this->getAllFiltered($userDao, MIDAS_POLICY_READ);
    $folderStats = array();
    $folderModel = MidasLoader::loadModel('Folder');
    foreach ($curatedfolderDaos as $curatedfolder) {
      $folder = $folderModel->load($curatedfolder->getFolderId());
      $stats = array();
      $stats['size'] = $folderModel->getSize($folder);
      if ($stats['size'] === Null) {
        $stats['size'] = 0;
      }
      $stats['download'] = $this->getFolderDownloadCounts($folder);
      if ($stats['download'] === Null) {
        $stats['download'] = 0;
      }
      $stats['folder_id'] = $folder->getFolderId();
      $stats['name'] = $folder->getName();
      $stats['curation_state'] = $curatedfolder->getCurationState();
      $folderStats[] = $stats;
    }
    return $folderStats;
  }

}
