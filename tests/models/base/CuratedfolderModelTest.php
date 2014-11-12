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

/** test Curatedfolder model*/
require_once BASE_PATH.'/modules/curate/constant/module.php';
class CuratedfolderModelTest extends DatabaseTestCase
  {
  /** set up tests*/
  public function setUp()
    {
    $this->setupDatabase(array('default')); //core dataset
    $this->enabledModules = array('curate');
    $this->_models = array('Folder');
    Zend_Registry::set('modulesEnable', array());
    Zend_Registry::set('notifier', new MIDAS_Notifier(false, null));
    parent::setUp();
    }

  /** testEnableAndDisableFolderCuration*/
  public function testEnableAndDisableFolderCuration()
    {
    $curatedfolderModel = MidasLoader::loadModel('Curatedfolder', 'curate');

    // test curating null folderDao
    $folderDao = null;
    try
      {
      $curatedfolderDao = $curatedfolderModel->enableFolderCuration($folderDao);
      $this->fail('Should have failed calling enableFolderCuration on null folder');
      }
    catch(Exception $e)
      {
      $this->assertEquals(-1, $e->getCode());
      }

    // curate a folder and ensure it is tracked for curation
    $folderDao = $this->Folder->load(1000);
    $curatedfolderDao = $curatedfolderModel->enableFolderCuration($folderDao);

    $this->assertEquals($curatedfolderDao->getFolderId(), $folderDao->getFolderId());
    $this->assertEquals($curatedfolderDao->getCurationState(), CURATE_STATE_CONSTRUCTION);

    // save the id, when enabling curation again on this folder should have the same id
    // since it should not create a new row
    $curatedfolderId = $curatedfolderDao->getCuratedfolderId();

    $curatedfolderDao = $curatedfolderModel->enableFolderCuration($folderDao);
    $this->assertEquals($curatedfolderDao->getCuratedfolderId(), $curatedfolderId);

    // test disabling curation on a null folderDao
    $folderDao = null;
    try
      {
      $curatedfolderDao = $curatedfolderModel->disableFolderCuration($folderDao);
      $this->fail('Should have failed calling disableFolderCuration on null folder');
      }
    catch(Exception $e)
      {
      $this->assertEquals(-1, $e->getCode());
      }

    // disable a curated folder
    $folderDao = $this->Folder->load(1000);
    $disabled = $curatedfolderModel->disableFolderCuration($folderDao);
    $this->assertEquals($disabled, true);

    // disable a non-tracked folder, should return false
    $disabled = $curatedfolderModel->disableFolderCuration($folderDao);
    $this->assertEquals($disabled, false);

    // ensure that we can re-enable curation
    $curatedfolderDao = $curatedfolderModel->enableFolderCuration($folderDao);

    $this->assertEquals($curatedfolderDao->getFolderId(), $folderDao->getFolderId());
    $this->assertEquals($curatedfolderDao->getCurationState(), CURATE_STATE_CONSTRUCTION);
    }






/*
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
      $curatedfolderDao = curatedfolderDaos[0];
      $this->delete($curatedfolderDao);
      return true;
      } 
    }*/


  }
