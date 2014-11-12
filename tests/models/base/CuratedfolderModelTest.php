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

  /** testSaveAndLoad*/
  public function testEnableFolderCuration()
    {
    $curatedfolderModel = MidasLoader::loadModel('Curatedfolder', 'curate');

    // test null folderDao
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
    }
  }
