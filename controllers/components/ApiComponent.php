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

/** Component for api methods */
class Curate_ApiComponent extends AppComponent {


  /**
   * Helper function for verifying keys in an input array
   */
  private function _checkKeys($keys, $values) {
    foreach ($keys as $key) {
      if (!array_key_exists($key, $values)) {
        throw new Exception('Parameter '.$key.' must be set.', 400);
      }
    }
  }

  /**
   * Helper function to get the user from token or session authentication
   */
  private function _getUser($args) {
    $authComponent = MidasLoader::loadComponent('Authentication');
    return $authComponent->getUser($args, $this->userSession->Dao);
  }

  /**
   * Enable curation for a folder, calling user requires ADMIN
   * access on folder.
   * @param folder_id id of the folder to enable curation.
   * @return curatedfolder.
   */
  public function enableCuration($args) {
    $userDao = $this->_getUser($args);
    if (!$userDao) {
      throw new Exception('You must login to enable curation on a folder.', 401);
    }

    $this->_checkKeys(array('folder_id'), $args);
    $folderModel = MidasLoader::loadModel('Folder');
    $folderDao = $folderModel->load($args['folder_id']);
    if (!$folderDao) {
      throw new Exception('No folder found with that id.', 404);
    }
    if (!$folderModel->policyCheck($folderDao, $userDao, MIDAS_POLICY_WRITE)) {
      throw new Exception("Admin permissions required on the folder.", 401);
    }

    $curatedfolderModel = MidasLoader::loadModel('Curatedfolder', 'curate');
    $curatedfolderDao = $curatedfolderModel->enableFolderCuration($folderDao);
    return $curatedfolderDao;
  }

  /**
   * Disable curation for a folder, calling user requires ADMIN
   * access on folder.
   * @param folder_id id of the folder to disable curation.
   * @return 1 indicating that the folder was tracked for curation
   * before it was disabled, or 0 indicating it wasn't.
   */
  public function disableCuration($args) {
    $userDao = $this->_getUser($args);
    if (!$userDao) {
      throw new Exception('You must login to disable curation on a folder.', 401);
    }

    $this->_checkKeys(array('folder_id'), $args);
    $folderModel = MidasLoader::loadModel('Folder');
    $folderDao = $folderModel->load($args['folder_id']);
    if (!$folderDao) {
      throw new Exception('No folder found with that id.', 404);
    }
    if (!$folderModel->policyCheck($folderDao, $userDao, MIDAS_POLICY_WRITE)) {
      throw new Exception("Admin permissions required on the folder.", 401);
    }

    $curatedfolderModel = MidasLoader::loadModel('Curatedfolder', 'curate');
    // return int representation of boolean value
    return $curatedfolderModel->disableFolderCuration($folderDao) ? 1 : 0;
  }

  /**
   * Give the ability to moderate curated folders to the passed in user,
   * calling user required to be a site admin.
   * @param user_id id of the user to empower.
   * @return "OK" on success
   */
  public function empowerModerator($args) {
    $userDao = $this->_getUser($args);
    if (!$userDao) {
      throw new Exception('You must login to empower a curation moderator.', 401);
    }
    if (!$userDao->getAdmin()) {
      throw new Exception('You must be a site admin to empower a curation moderator.', 401);
    }

    $this->_checkKeys(array('user_id'), $args);
    $userModel = MidasLoader::loadModel('User');
    $empoweredUserDao = $userModel->load($args['user_id']);
    if (!$empoweredUserDao) {
      throw new Exception('No user found with that id.', 404);
    }
    $moderatorModel = MidasLoader::loadModel('Moderator', 'curate');
    $curationModeratorDao = $moderatorModel->empowerCurationModerator($empoweredUserDao);
    return "OK";
  }

  /**
   * Request that a curated folder be approved, will email all site admins and
   * curation moderators, calling user must have Admin access to the folder..
   * @param folder_id id of the folder to request approval for.
   * @param message optional message to include in the email.
   * @return "OK" on success
   */
  public function requestApproval($args) {
    $userDao = $this->_getUser($args);
    if (!$userDao) {
      throw new Exception('You must login to request curation approval for a folder.', 401);
    }

    $this->_checkKeys(array('folder_id'), $args);
    $folderModel = MidasLoader::loadModel('Folder');
    $folderDao = $folderModel->load($args['folder_id']);
    if (!$folderDao) {
      throw new Exception('No folder found with that id.', 404);
    }
    if (!$folderModel->policyCheck($folderDao, $userDao, MIDAS_POLICY_WRITE)) {
      throw new Exception("Admin permissions required on the folder.", 401);
    }

    $curatedfolderModel = MidasLoader::loadModel('Curatedfolder', 'curate');
    if (array_key_exists('message', $args)) {
      $message = $args['message'];
    } else {
      $message = false;
    }
    try {
      $curatedfolderDao = $curatedfolderModel->requestCurationApproval($folderDao, $message);
    } catch (Exception $e) {
      // if we fail for any other reason with a -1, return a 404
      if ($e->getCode() == -1) {
        throw new Exception($e->getMessage(), 404);
      }
    }

    return "OK";
  }

  /**
   * Approve a curation request for a curated folder.  Will email all users
   * with direct Admin access to the folder (not via a group) with a notification,
   * and will set the curated folder to the state of APPROVED; calling user
   * must be a site admin or curation moderator.
   * @param folder_id id of the folder to approve a curation request for.
   * @param message optional message to include in the email.
   * @return "OK" on success
   */
  public function approveCuration($args) {
    $userDao = $this->_getUser($args);
    if (!$userDao) {
      throw new Exception('You must login to approve a curation request for a folder.', 401);
    }

    $this->_checkKeys(array('folder_id'), $args);
    $folderModel = MidasLoader::loadModel('Folder');
    $folderDao = $folderModel->load($args['folder_id']);
    if (!$folderDao) {
      throw new Exception('No folder found with that id.', 404);
    }

    $userDao = $this->_getUser($args);
    if (!$userDao) {
      throw new Exception('You must login to approve a curation request for a folder.', 401);
    }

    // require site admin or curation moderator
    if (!$userDao->getAdmin()) {
      $moderatorModel = MidasLoader::loadModel('Moderator', 'curate');
      $moderatorDao = $moderatorModel->load($userDao->getUserId());
      if (false == $moderatorDao) {
        throw new Exception('You must be a site admin or curation moderator to approve a curation request for a folder.', 401);
      }
    }

    $curatedfolderModel = MidasLoader::loadModel('Curatedfolder', 'curate');
    if (array_key_exists('message', $args)) {
      $message = $args['message'];
    } else {
      $message = false;
    }
    try {
      $curatedfolderDao = $curatedfolderModel->approveCurationRequest($folderDao, $message);
    } catch (Exception $e) {
      // if we fail for any other reason with a -1, return a 404
      if ($e->getCode() == -1) {
        throw new Exception($e->getMessage(), 404);
      }
    }

    return "OK";
  }

  /**
   * List all curated Folders that the passed in user has Read access to,
   * along with the sum of sizes and download counts for all Items in
   * the Folder subtree rooted at the Folder tracked as a Curatedfolder.
   * @return TODO the structure of the return object
   */
  public function listAllCuratedFolders($args) {
    $userDao = $this->_getUser($args);
    $curatedfolderModel = MidasLoader::loadModel('Curatedfolder', 'curate');
    $curatedFolders = $curatedfolderModel->listAllCuratedFolders($userDao);
    return $curatedFolders;
  }




} // end class
