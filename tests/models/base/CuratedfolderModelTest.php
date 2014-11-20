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
    $disabled = $curatedfolderModel->disableFolderCuration($folderDao);
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

    // disable the folder to leave it in a known state
    $disabled = $curatedfolderModel->disableFolderCuration($folderDao);
    }

  /** testRequestCurationApproval*/
  public function testRequestCurationApproval()
    {
    $curatedfolderModel = MidasLoader::loadModel('Curatedfolder', 'curate');

    // test requesting approval on a null folderDao
    $folderDao = null;
    try
      {
      $curatedfolderDao = $curatedfolderModel->requestCurationApproval($folderDao);
      $this->fail('Should have failed calling requestCurationApproval on null folder');
      }
    catch(Exception $e)
      {
      $this->assertEquals(-1, $e->getCode());
      }

    $folderDao = $this->Folder->load(1000);
    // ensure this folder is not tracked for curation
    $disabled = $curatedfolderModel->disableFolderCuration($folderDao);
    // request curation approval on a folder that isn't tracked under curation
    try
      {
      $curatedfolderDao = $curatedfolderModel->requestCurationApproval($folderDao);
      $this->fail('Should have failed calling requestCurationApproval on untracked folder');
      }
    catch(Exception $e)
      {
      $this->assertEquals(-1, $e->getCode());
      }

    $enabled = $curatedfolderModel->enableFolderCuration($folderDao);

    // empower a user as a curation moderator
    $userModel = MidasLoader::loadModel('User');
    $userDao = $userModel->load(1);
    $curationModeratorModel = MidasLoader::loadModel('Moderator', 'curate');
    $curationModeratorDao = $curationModeratorModel->empowerCurationModerator($userDao);

    $curatedfolderDao = $curatedfolderModel->requestCurationApproval($folderDao);
    $this->assertEquals($curatedfolderDao->getFolderId(), $folderDao->getFolderId());
    $this->assertEquals($curatedfolderDao->getCurationState(), CURATE_STATE_REQUESTED);

    // ensure this folder is not tracked for curation
    $disabled = $curatedfolderModel->disableFolderCuration($folderDao);
    }

  /** testApproveCurationRequest*/
  public function testApproveCurationRequest()
    {
    $curatedfolderModel = MidasLoader::loadModel('Curatedfolder', 'curate');

    // test approving a null folderDao
    $folderDao = null;
    try
      {
      $curatedfolderDao = $curatedfolderModel->approveCurationRequest($folderDao);
      $this->fail('Should have failed calling approveCurationRequest on null folder');
      }
    catch(Exception $e)
      {
      $this->assertEquals(-1, $e->getCode());
      }

    $folderDao = $this->Folder->load(1000);
    // ensure this folder is not tracked for curation
    $disabled = $curatedfolderModel->disableFolderCuration($folderDao);
    // approve a folder that isn't tracked under curation
    try
      {
      $curatedfolderDao = $curatedfolderModel->approveCurationRequest($folderDao);
      $this->fail('Should have failed calling approveCurationRequest on untracked folder');
      }
    catch(Exception $e)
      {
      $this->assertEquals(-1, $e->getCode());
      }

    $enabled = $curatedfolderModel->enableFolderCuration($folderDao);

    // empower a user as a curation moderator
    $userModel = MidasLoader::loadModel('User');
    $userDao = $userModel->load(1);
    $curationModeratorModel = MidasLoader::loadModel('Moderator', 'curate');
    $curationModeratorDao = $curationModeratorModel->empowerCurationModerator($userDao);

    // approve a folder that isn't in the requested state
    try
      {
      $curatedfolderDao = $curatedfolderModel->approveCurationRequest($folderDao);
      $this->fail('Should have failed calling approveCurationRequest on folder not in requested state.');
      }
    catch(Exception $e)
      {
      $this->assertEquals(-1, $e->getCode());
      }

    // set the folder to the requested state
    $curatedfolderDao = $curatedfolderModel->requestCurationApproval($folderDao);

    // now approve it
    $curatedfolderDao = $curatedfolderModel->approveCurationRequest($folderDao);
    $this->assertEquals($curatedfolderDao->getFolderId(), $folderDao->getFolderId());
    $this->assertEquals($curatedfolderDao->getCurationState(), CURATE_STATE_APPROVED);

    // ensure this folder is not tracked for curation
    $disabled = $curatedfolderModel->disableFolderCuration($folderDao);
    }



  }
