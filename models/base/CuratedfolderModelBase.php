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
abstract class Curate_CuratedfolderModelBase extends Curate_AppModel
  {
  /** Constructor */
  public function __construct()
    {
    parent::__construct();
    $this->_name = 'curate_curatedfolder';
    $this->_key = 'curatedfolder_id';

    $this->_mainData = array(
      'curatedfolder_id' => array('type' => MIDAS_DATA),
      'folder_id' => array('type' => MIDAS_DATA),
      'curation_state' => array('type' => MIDAS_DATA),
      'creation_date' => array('type' => MIDAS_DATA));
/*,
      'folder' =>  array('type' => MIDAS_ONE_TO_ONE,
                       'model' => 'Folder',
                       'parent_column' => 'folder_id',
                       'child_column' => 'folder_id')*/
    $this->initialize();
    }

  /** tracks the passed in folder for curation.  If the passed in folder
   * had not yet been tracked for curation, will set curate state to construction.
   * requires ADMIN access to the folder.
   */
  function enableFolderCuration($folderDao)
    {
    if(is_null($folderDao))
      {
      throw new Exception('Non-null folder required to enable curation.', -1);
      }

    // if already under curation return the curatedfolder
    $curatedfolderDaos = $this->findBy('folder_id', $folderDao->getFolderId());
    if(count($curatedfolderDaos) > 0)
      {
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
  function disableFolderCuration($folderDao)
    {
    if(is_null($folderDao))
      {
      throw new Exception('Non-null folder required to disable curation.', -1);
      }

    $curatedfolderDaos = $this->findBy('folder_id', $folderDao->getFolderId());
    if(count($curatedfolderDaos) ==  0)
      {
      // if the folder isn't under curation then there is nothing to do
      return false;
      }
    else
      {
      $curatedfolderDao = $curatedfolderDaos[0];
      $this->delete($curatedfolderDao);
      return true;
      }
    }


  }
