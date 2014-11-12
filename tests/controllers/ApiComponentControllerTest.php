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

/** Tests the functionality of the web API methods */
require_once BASE_PATH.'/modules/curate/constant/module.php';
class ApiControllerTest extends ControllerTestCase
  {
  /** set up tests */
  public function setUp()
    {
    $db = Zend_Registry::get('dbAdapter');
    $configDatabase = Zend_Registry::get('configDatabase');
    $this->setupDatabase(array('default')); //core dataset
    $this->enabledModules = array('api', 'curate');
    $this->_models = array('User', 'Folder');
    $this->_daos = array('User', 'Folder');
    parent::setUp();
    }

  /** Invoke the JSON web API */
  private function _callJsonApi($sessionUser = null)
    {
    $this->dispatchUrI($this->webroot.'api/json', $sessionUser);
    return json_decode($this->getBody());
    }

  /** Make sure we got a good response from a web API call */
  private function _assertStatusOk($resp)
    {
    $this->assertNotEquals($resp, false);
    $this->assertEquals($resp->message, '');
    $this->assertEquals($resp->stat, 'ok');
    $this->assertEquals($resp->code, 0);
    $this->assertTrue(isset($resp->data));
    }

  /** Test to see that the response is bad (for testing exceptional cases) */
  private function _assertStatusFailed($resp, $returnCode = -1)
    {
    $this->assertEquals($resp->stat, "fail");
    $this->assertEquals($resp->code, $returnCode);
    }

  /** Authenticate using the default api key */
  private function _loginUsingApiKey()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());

    $userApiModel = MidasLoader::loadModel('Userapi');
    $userApiModel->createDefaultApiKey($userDao);
    $apiKey = $userApiModel->getByAppAndUser('Default', $userDao)->getApikey();

    $this->params['method'] = 'midas.login';
    $this->params['email'] = $usersFile[0]->getEmail();
    $this->params['appname'] = 'Default';
    $this->params['apikey'] = $apiKey;
    $this->request->setMethod('POST');

    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEquals(strlen($resp->data->token), 40);

    // **IMPORTANT** This will clear any params that were set before this
    // function was called
    $this->resetAll();
    return $resp->data->token;
    }

  /** Authenticate using the default api key */
  private function _loginUsingApiKeyAsAdmin()
    {
    $usersFile = $this->loadData('User', 'default');
    $userDao = $this->User->load($usersFile[0]->getKey());
    $userDao->setAdmin(1);
    $this->User->save($userDao);

    $userApiModel = MidasLoader::loadModel('Userapi');
    $userApiModel->createDefaultApiKey($userDao);
    $apiKey = $userApiModel->getByAppAndUser('Default', $userDao)->getApikey();

    $this->resetAll(); 
    $this->params['method'] = 'midas.login';
    $this->params['email'] = $usersFile[0]->getEmail();
    $this->params['appname'] = 'Default';
    $this->params['apikey'] = $apiKey;
    $this->request->setMethod('POST');

    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEquals(strlen($resp->data->token), 40);

    // **IMPORTANT** This will clear any params that were set before this
    // function was called
    $this->resetAll();
    return $resp->data->token;
    }

  private function _requireValidSession($apiMethod, $httpMethod)
    {
    $this->resetAll();
    $this->params['method'] = $apiMethod;
    $this->request->setMethod($httpMethod);
    $resp = $this->_callJsonApi();
    $this->_assertStatusFailed($resp, 401);
    }

  private function _requireValidFolderId($apiMethod, $httpMethod)
    {
    $this->resetAll();
    $userToken = $this->_loginUsingApiKey();

    # without a folder
    $this->params['method'] = $apiMethod;
    $this->params['token'] = $userToken;
    $this->request->setMethod($httpMethod);
    $resp = $this->_callJsonApi();
    $this->_assertStatusFailed($resp, 400);

    # with an invalid folder
    $this->resetAll();
    $this->params['method'] = $apiMethod;
    $this->params['token'] = $userToken;
    $this->request->setMethod($httpMethod);
    $this->params['folder_id'] = -1;
    $resp = $this->_callJsonApi();
    $this->_assertStatusFailed($resp, 404);
    }

  private function _requireValidUserId($apiMethod, $httpMethod, $userToken = false)
    {
    $this->resetAll();
    if(false == $userToken)
      {
      $userToken = $this->_loginUsingApiKey();
      }

    # without a user
    $this->params['method'] = $apiMethod;
    $this->params['token'] = $userToken;
    $this->request->setMethod($httpMethod);
    $resp = $this->_callJsonApi();
    $this->_assertStatusFailed($resp, 400);

    # with an invalid user
    $this->resetAll();
    $this->params['method'] = $apiMethod;
    $this->params['token'] = $userToken;
    $this->request->setMethod($httpMethod);
    $this->params['user_id'] = -1;
    $resp = $this->_callJsonApi();
    $this->_assertStatusFailed($resp, 404);
    }

  private function _requireFolderAdminAccess($apiMethod, $httpMethod)
    {
    $this->resetAll();
    $userToken = $this->params['token'] = $this->_loginUsingApiKey();

    $this->params['method'] = $apiMethod;
    $this->params['token'] = $userToken;
    $this->request->setMethod($httpMethod);
    $this->params['folder_id'] = 1;
    $resp = $this->_callJsonApi();
    $this->_assertStatusFailed($resp, 401);
    }

  private function _requireAdminAccess($apiMethod, $httpMethod)
    {
    $this->resetAll();
    $userToken = $this->params['token'] = $this->_loginUsingApiKey();

    $this->params['method'] = $apiMethod;
    $this->params['token'] = $userToken;
    $this->request->setMethod($httpMethod);
    $this->params['folder_id'] = 1;
    $resp = $this->_callJsonApi();
    $this->_assertStatusFailed($resp, 401);
    }

  /** test enableAndDisableCuration */
  public function testEnableCuration()
    {
    $enableCurationApiMethod = 'midas.curate.enable.curation';
    $httpMethod = 'POST';

    // basic validation on enableCuration 
    $this->_requireValidSession($enableCurationApiMethod, $httpMethod);
    $this->_requireValidFolderId($enableCurationApiMethod, $httpMethod);
    $this->_requireFolderAdminAccess($enableCurationApiMethod, $httpMethod);
     
    $this->resetAll();
    $userToken = $this->_loginUsingApiKey(); 
    // folder is tracked under curation and curation status is construction
    $this->resetAll();
    $this->params['method'] = $enableCurationApiMethod;
    $this->params['token'] = $userToken;
    $this->request->setMethod($httpMethod);
    $this->params['folder_id'] = 1000;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $curatedfolderObj = $resp->data;
    $curatedfolderId = $curatedfolderObj->curatedfolder_id;

    // load the dao based on the returned id
    $curatedfolderModel = MidasLoader::loadModel('Curatedfolder', 'curate');
    $loadedCuratedfolderDao = $curatedfolderModel->load($curatedfolderId);
    // ensure it has the correct folder and state
    $this->assertEquals($loadedCuratedfolderDao->getFolderId(), 1000);
    $this->assertEquals($loadedCuratedfolderDao->getCurationState(), CURATE_STATE_CONSTRUCTION);

    // call the API again and ensure that no additional row is created
    $this->resetAll();
    $this->params['method'] = $enableCurationApiMethod;
    $this->params['token'] = $userToken;
    $this->request->setMethod($httpMethod);
    $this->params['folder_id'] = 1000;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $curatedfolderObj = $resp->data;
    $curatedfolderIdTwo = $curatedfolderObj->curatedfolder_id;
    // ensure it has the correct curatedfolderId, folder and state
    $this->assertEquals($curatedfolderId, $curatedfolderIdTwo);
    $loadedCuratedfolderDao = $curatedfolderModel->load($curatedfolderIdTwo);
    $this->assertEquals($loadedCuratedfolderDao->getFolderId(), 1000);
    $this->assertEquals($loadedCuratedfolderDao->getCurationState(), CURATE_STATE_CONSTRUCTION);

    $disableCurationApiMethod = 'midas.curate.disable.curation';

    // basic validation on disableCuration 
    $this->_requireValidSession($disableCurationApiMethod, $httpMethod);
    $this->_requireValidFolderId($disableCurationApiMethod, $httpMethod);
    $this->_requireFolderAdminAccess($disableCurationApiMethod, $httpMethod);

    // disable curation on the folder
    $this->resetAll();
    $this->params['method'] = $disableCurationApiMethod;
    $this->params['token'] = $userToken;
    $this->request->setMethod($httpMethod);
    $this->params['folder_id'] = 1000;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEquals(1, $resp->data, "disableCuration should have returned 1 for folder under curation");

    // call disable again, should return false
    $this->resetAll();
    $this->params['method'] = $disableCurationApiMethod;
    $this->params['token'] = $userToken;
    $this->request->setMethod($httpMethod);
    $this->params['folder_id'] = 1000;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);
    $this->assertEquals(0, $resp->data, "disableCuration should have returned 0 for folder not under curation");

    }

  /** test empowerModerator */
  public function testEmpowerModerator()
    {
    $empowerModeratorApiMethod = 'midas.curate.empower.moderator';
    $httpMethod = 'POST';

    // basic validation
    $this->_requireValidSession($empowerModeratorApiMethod, $httpMethod);
    $this->_requireAdminAccess($empowerModeratorApiMethod, $httpMethod);
    $adminToken = $this->_loginUsingApiKeyAsAdmin();
    $this->_requireValidUserId($empowerModeratorApiMethod, $httpMethod, $adminToken);

    $this->resetAll();
    $this->params['method'] = $empowerModeratorApiMethod;
    $this->params['token'] = $adminToken;
    $this->request->setMethod($httpMethod);
    $this->params['user_id'] = 1;
    $resp = $this->_callJsonApi();
    $this->_assertStatusOk($resp);

    // load by user_id and ensure the user is a moderator
    $moderatorModel = MidasLoader::loadModel('Moderator', 'curate');
    $curationModeratorDaos = $moderatorModel->findBy('user_id', 1);
    $this->assertNotEquals(count($curationModeratorDaos), 0);

    // TODO check that there is only one DAO created


    }
 }
