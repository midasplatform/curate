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
class Curate_ApiComponent extends AppComponent
  {

  /**
   * Helper function for verifying keys in an input array
   */
  private function _checkKeys($keys, $values)
    {
    foreach($keys as $key)
      {
      if(!array_key_exists($key, $values))
        {
        throw new Exception('Parameter '.$key.' must be set.', 400);
        }
      }
    }

  /**
   * Helper function to get the user from token or session authentication
   */
  private function _getUser($args)
    {
    $authComponent = MidasLoader::loadComponent('Authentication');
    return $authComponent->getUser($args, $this->userSession->Dao);
    }

  /**
   * Enable curation for a folder, calling user requires ADMIN
   * access on folder.
   * @param folder_id id of the folder to enable curation.
   * @return curatedfolder.
   */
  public function enableCuration($args)
    {
    $userDao = $this->_getUser($args);
    if(!$userDao)
      {
      throw new Exception('You must login to enable curation on a folder.', 403);
      }

    $this->_checkKeys(array('folder_id'), $args);
    $folderModel = MidasLoader::loadModel('Folder');
    $folderDao = $folderModel->load($args['folder_id']);
    if(!$folderDao)
      {
      throw new Exception('No folder found with that id.', 404);
      }
    if(!$folderModel->policyCheck($folderDao, $userDao, MIDAS_POLICY_WRITE))
      {
      throw new Exception("Admin permissions required on the folder.", 403);
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
  public function disableCuration($args)
    {
    $userDao = $this->_getUser($args);
    if(!$userDao)
      {
      throw new Exception('You must login to disable curation on a folder.', 403);
      }

    $this->_checkKeys(array('folder_id'), $args);
    $folderModel = MidasLoader::loadModel('Folder');
    $folderDao = $folderModel->load($args['folder_id']);
    if(!$folderDao)
      {
      throw new Exception('No folder found with that id.', 404);
      }
    if(!$folderModel->policyCheck($folderDao, $userDao, MIDAS_POLICY_WRITE))
      {
      throw new Exception("Admin permissions required on the folder.", 403);
      }

    $curatedfolderModel = MidasLoader::loadModel('Curatedfolder', 'curate');
    // return int representation of boolean value
    return $curatedfolderModel->disableFolderCuration($folderDao) ? 1 : 0;
    }


  } // end class
