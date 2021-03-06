<?php
/*=========================================================================
 Midas Server
 Copyright Kitware SAS, 26 rue Louis Guérin, 69100 Villeurbanne, France.
 All rights reserved.
 For more information visit http://www.kitware.com/.

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
        $this->assertEquals($resp->stat, 'fail');
        $this->assertEquals($resp->code, $returnCode);
    }

    /** Authenticate using the default api key */
    private function _loginUsingApiKey($userIndex = 0)
    {
        $usersFile = $this->loadData('User', 'default');
        $userDao = $this->User->load($usersFile[$userIndex]->getKey());

        $userApiModel = MidasLoader::loadModel('Userapi');
        $userApiModel->createDefaultApiKey($userDao);
        $apiKey = $userApiModel->getByAppAndUser('Default', $userDao)->getApikey();

        $this->resetAll();
        $this->params['method'] = 'midas.login';
        $this->params['email'] = $usersFile[$userIndex]->getEmail();
        $this->params['appname'] = 'Default';
        $this->params['apikey'] = $apiKey;
        $this->request->setMethod('POST');

        $resp = $this->_callJsonApi();
        $this->_assertStatusOk($resp);
        $this->assertEquals(strlen($resp->data->token), 32);

        // **IMPORTANT** This will clear any params that were set before this
        // function was called
        $this->resetAll();

        return $resp->data->token;
    }

    /** Authenticate using the default api key */
    private function _loginUsingApiKeyAsAdmin()
    {
        $usersFile = $this->loadData('User', 'default');
        $userDao = $this->User->load(3);
        //$usersFile[0]->getKey());
        //$userDao->setAdmin(1);
        //$this->User->save($userDao);

        $userApiModel = MidasLoader::loadModel('Userapi');
        $userApiModel->createDefaultApiKey($userDao);
        $apiKey = $userApiModel->getByAppAndUser('Default', $userDao)->getApikey();

        $this->resetAll();
        $this->params['method'] = 'midas.login';
        $this->params['email'] = $userDao->getEmail();
        $this->params['appname'] = 'Default';
        $this->params['apikey'] = $apiKey;
        $this->request->setMethod('POST');

        $resp = $this->_callJsonApi();
        $this->_assertStatusOk($resp);
        $this->assertEquals(strlen($resp->data->token), 32);

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
        if (false == $userToken) {
            $userToken = $this->_loginUsingApiKey();
        }

        # without a user
        $this->params['method'] = $apiMethod;
        $this->params['token'] = $userToken;
        $this->request->setMethod($httpMethod);
        $resp = $this->_callJsonApi();
        $this->_assertStatusFailed($resp, 401);

        # with an invalid user
        $this->resetAll();
        $this->params['method'] = $apiMethod;
        $this->params['token'] = $userToken;
        $this->request->setMethod($httpMethod);
        $this->params['user_id'] = -1;
        $resp = $this->_callJsonApi();
        $this->_assertStatusFailed($resp, 401);
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
        $this->assertEquals(1, $resp->data, 'disableCuration should have returned 1 for folder under curation');

        // call disable again, should return false
        $this->resetAll();
        $this->params['method'] = $disableCurationApiMethod;
        $this->params['token'] = $userToken;
        $this->request->setMethod($httpMethod);
        $this->params['folder_id'] = 1000;
        $resp = $this->_callJsonApi();
        $this->_assertStatusOk($resp);
        $this->assertEquals(0, $resp->data, 'disableCuration should have returned 0 for folder not under curation');
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

        $this->resetAll();
        $this->params['method'] = $empowerModeratorApiMethod;
        $this->params['token'] = $adminToken;
        $this->request->setMethod($httpMethod);
        $this->params['user_id'] = 2;
        $resp = $this->_callJsonApi();
        $this->_assertStatusOk($resp);

        // load by user_id and ensure the user is a moderator
        $moderatorModel = MidasLoader::loadModel('Moderator', 'curate');
        $curationModeratorDaos = $moderatorModel->findBy('user_id', 2);
        $this->assertEquals(count($curationModeratorDaos), 1);

        // remove curation power from user
        $userModel = MidasLoader::loadModel('User');
        $userDao = $userModel->load(2);
        $moderatorModel->disempowerCurationModerator($userDao);
    }

    /** test requestCurationApproval */
    public function testRequestCurationApproval()
    {
        $requestCurationApprovalApiMethod = 'midas.curate.request.approval';
        $httpMethod = 'POST';

        // basic validation
        $this->_requireValidSession($requestCurationApprovalApiMethod, $httpMethod);
        $this->_requireValidFolderId($requestCurationApprovalApiMethod, $httpMethod);
        $this->_requireAdminAccess($requestCurationApprovalApiMethod, $httpMethod);

        $userToken = $this->_loginUsingApiKey();

        // ensure the folder is curated and in the CONSTRUCTED state
        $folderModel = MidasLoader::loadModel('Folder');
        $curatedfolderModel = MidasLoader::loadModel('Curatedfolder', 'curate');
        $folderDao = $folderModel->load(1000);
        $loadedCuratedfolderDao = $curatedfolderModel->disableFolderCuration($folderDao);
        $loadedCuratedfolderDao = $curatedfolderModel->enableFolderCuration($folderDao);

        $this->resetAll();
        $this->params['method'] = $requestCurationApprovalApiMethod;
        $this->params['token'] = $userToken;
        $this->request->setMethod($httpMethod);
        $this->params['folder_id'] = 1000;
        $this->params['message'] = 'my message';
        $resp = $this->_callJsonApi();
        $this->_assertStatusOk($resp);

        // ensure it has the correct state
        $loadedCuratedfolderDao = $curatedfolderModel->load($loadedCuratedfolderDao->getCuratedfolderId());
        $this->assertEquals($loadedCuratedfolderDao->getCurationState(), CURATE_STATE_REQUESTED);

        // leave the curated folder in a clean state
        $loadedCuratedfolderDao = $curatedfolderModel->disableFolderCuration($folderDao);
    }

    /** test approveCuration */
    public function testApproveCuration()
    {
        $approveCurationApiMethod = 'midas.curate.approve.curation';
        $httpMethod = 'POST';

        // basic validation
        $this->_requireValidSession($approveCurationApiMethod, $httpMethod);
        $this->_requireValidFolderId($approveCurationApiMethod, $httpMethod);

        // do not track the folder under curation
        $folderModel = MidasLoader::loadModel('Folder');
        $folderDao = $folderModel->load(1000);
        $curatedfolderModel = MidasLoader::loadModel('Curatedfolder', 'curate');
        $loadedCuratedfolderDao = $curatedfolderModel->disableFolderCuration($folderDao);

        $moderatorModel = MidasLoader::loadModel('Moderator', 'curate');
        $nonmoderatorToken = $this->_loginUsingApiKey(1);
        // should fail since user is not a moderator
        $this->resetAll();
        $this->params['method'] = $approveCurationApiMethod;
        $this->params['token'] = $nonmoderatorToken;
        $this->request->setMethod($httpMethod);
        $this->params['folder_id'] = 1000;
        $resp = $this->_callJsonApi();
        $this->_assertStatusFailed($resp, 401);

        // ensure user index 0 is a moderator
        $usersFile = $this->loadData('User', 'default');
        $moderatorDao = $this->User->load($usersFile[0]->getKey());
        $empowered = $moderatorModel->empowerCurationModerator($moderatorDao);
        // use a moderator, should fail because the folder is not under curation
        $moderatorToken = $this->_loginUsingApiKey();
        $this->resetAll();
        $this->params['method'] = $approveCurationApiMethod;
        $this->params['token'] = $moderatorToken;
        $this->request->setMethod($httpMethod);
        $this->params['folder_id'] = 1000;
        $resp = $this->_callJsonApi();
        $this->_assertStatusFailed($resp, 404);

        // use an admin, should fail because the folder is not under curation
        $adminToken = $this->_loginUsingApiKeyAsAdmin();
        $this->resetAll();
        $this->params['method'] = $approveCurationApiMethod;
        $this->params['token'] = $adminToken;
        $this->request->setMethod($httpMethod);
        $this->params['folder_id'] = 1000;
        $resp = $this->_callJsonApi();
        $this->_assertStatusFailed($resp, 404);

        // track the folder under curation
        $loadedCuratedfolderDao = $curatedfolderModel->enableFolderCuration($folderDao);

        // use a moderator, should fail because the folder is not under curation
        $this->resetAll();
        $this->params['method'] = $approveCurationApiMethod;
        $this->params['token'] = $moderatorToken;
        $this->request->setMethod($httpMethod);
        $this->params['folder_id'] = 1000;
        $resp = $this->_callJsonApi();
        $this->_assertStatusFailed($resp, 404);

        // use an admin, should fail because the folder is not under curation
        $this->resetAll();
        $this->params['method'] = $approveCurationApiMethod;
        $this->params['token'] = $adminToken;
        $this->request->setMethod($httpMethod);
        $this->params['folder_id'] = 1000;
        $resp = $this->_callJsonApi();
        $this->_assertStatusFailed($resp, 404);

        // set the curated folder to the requested state
        $loadedCuratedfolderDao = $curatedfolderModel->requestCurationApproval($folderDao);

        // try again with a non-moderator, should still fail
        $this->resetAll();
        $this->params['method'] = $approveCurationApiMethod;
        $this->params['token'] = $nonmoderatorToken;
        $this->request->setMethod($httpMethod);
        $this->params['folder_id'] = 1000;
        $resp = $this->_callJsonApi();
        $this->_assertStatusFailed($resp, 401);

        // try again with moderator, should succeed
        $this->resetAll();
        $this->params['method'] = $approveCurationApiMethod;
        $this->params['token'] = $moderatorToken;
        $this->request->setMethod($httpMethod);
        $this->params['folder_id'] = 1000;
        $this->params['message'] = 'my message';
        $resp = $this->_callJsonApi();
        $this->_assertStatusOk($resp);
        $this->assertEquals($resp->data, 'OK', 'approving curation with a moderator should have succeeded.');
        // check that curation status is correct
        $curatedfolderDaos = $curatedfolderModel->findBy('folder_id', $folderDao->getFolderId());
        $curatedfolderDao = $curatedfolderDaos[0];
        $this->assertEquals(
            $curatedfolderDao->getCurationState(),
            CURATE_STATE_APPROVED,
            'approved folder in incorrect curation state.'
        );

        // reset the folder to requested and ensure sucess with admin
        $loadedCuratedfolderDao = $curatedfolderModel->disableFolderCuration($folderDao);
        $loadedCuratedfolderDao = $curatedfolderModel->enableFolderCuration($folderDao);
        $loadedCuratedfolderDao = $curatedfolderModel->requestCurationApproval($folderDao);
        $this->resetAll();
        $this->params['method'] = $approveCurationApiMethod;
        $this->params['token'] = $adminToken;
        $this->request->setMethod($httpMethod);
        $this->params['folder_id'] = 1000;
        $this->params['message'] = 'my message';
        $resp = $this->_callJsonApi();
        $this->_assertStatusOk($resp);
        $this->assertEquals($resp->data, 'OK', 'approving curation with a admin should have succeeded.');
        // check that curation status is correct
        $curatedfolderDaos = $curatedfolderModel->findBy('folder_id', $folderDao->getFolderId());
        $curatedfolderDao = $curatedfolderDaos[0];
        $this->assertEquals(
            $curatedfolderDao->getCurationState(),
            CURATE_STATE_APPROVED,
            'approved folder in incorrect curation state.'
        );

        // leave the folder in an untracked state regarding curation
        $loadedCuratedfolderDao = $curatedfolderModel->disableFolderCuration($folderDao);
    }

    /** test listAllCuratedFolders */
    public function testListAllCuratedFolders()
    {
        $listAllCuratedFoldersApiMethod = 'midas.curate.list.all.curated.folders';
        $httpMethod = 'GET';

        // create a folder with 1 item that only admin user can see
        $folderModel = MidasLoader::loadModel('Folder');
        $adminFolder = $this->Folder->createFolder('adminFolder', '', -1);
        $curatedfolderModel = MidasLoader::loadModel('Curatedfolder', 'curate');
        $loadedCuratedfolderDao = $curatedfolderModel->enableFolderCuration($adminFolder);

        $itemModel = MidasLoader::loadModel('Item');
        $adminItem = $itemModel->createItem('adminitem', '', $adminFolder);
        $adminSizeBytes = 100;
        $adminItem->setSizebytes($adminSizeBytes);
        $adminDownloadCount = 10;
        $adminItem->setDownload($adminDownloadCount);
        $itemModel->save($adminItem);

        // create a folder with 1 item that anonymous can see
        $anonFolder = $this->Folder->createFolder('anonFolder', '', -1);
        $loadedCuratedfolderDao = $curatedfolderModel->enableFolderCuration($anonFolder);
        $groupModel = MidasLoader::loadModel('Group');
        $anonGroup = $groupModel->load(MIDAS_GROUP_ANONYMOUS_KEY);
        $folderpolicygroupModel = MidasLoader::loadModel('Folderpolicygroup');
        $folderpolicygroupModel->createPolicy($anonGroup, $anonFolder, MIDAS_POLICY_READ);

        $anonItem = $itemModel->createItem('anonitem', '', $anonFolder);
        $anonSizeBytes = 200;
        $anonItem->setSizebytes($anonSizeBytes);
        $anonDownloadCount = 20;
        $anonItem->setDownload($anonDownloadCount);
        $itemModel->save($anonItem);

        // call listAllCuratedFolders with anonymous user
        $this->resetAll();
        $this->params['method'] = $listAllCuratedFoldersApiMethod;
        $this->request->setMethod($httpMethod);
        $resp = $this->_callJsonApi();
        $this->_assertStatusOk($resp);
        $this->assertEquals(
            $resp->data[0]->size,
            $anonSizeBytes,
            'anonymous user should have '.$anonSizeBytes.' size of items under curation.'
        );
        $this->assertEquals(
            $resp->data[0]->download,
            $anonDownloadCount,
            'anonymous user should have '.$anonDownloadCount.' downloads of items under curation.'
        );

        // call listAllCuratedFolders with anonymous user
        $adminToken = $this->_loginUsingApiKeyAsAdmin();
        $this->resetAll();
        $this->params['token'] = $adminToken;
        $this->params['method'] = $listAllCuratedFoldersApiMethod;
        $this->request->setMethod($httpMethod);
        $resp = $this->_callJsonApi();
        $this->_assertStatusOk($resp);

        $anonFolderChecked = false;
        $adminFolderChecked = false;
        foreach ($resp->data as $curatedStats) {
            if ($curatedStats->folder_id === $anonFolder->getFolderId()) {
                $this->assertEquals($curatedStats->size, $anonItem->getSizebytes(), 'Anonymous item incorrect bytes');
                $this->assertEquals(
                    $curatedStats->download,
                    $anonItem->getDownload(),
                    'Anonymous item incorrect download'
                );
                $this->assertEquals(
                    $curatedStats->curation_state,
                    CURATE_STATE_CONSTRUCTION,
                    'Anonymous folder incorrect curation_state'
                );
                $anonFolderChecked = true;
            }
            if ($curatedStats->folder_id === $adminFolder->getFolderId()) {
                $this->assertEquals($curatedStats->size, $adminItem->getSizebytes(), 'Admin item incorrect bytes');
                $this->assertEquals(
                    $curatedStats->download,
                    $adminItem->getDownload(),
                    'Admin item incorrect download'
                );
                $this->assertEquals(
                    $curatedStats->curation_state,
                    CURATE_STATE_CONSTRUCTION,
                    'Admin folder incorrect curation_state'
                );
                $adminFolderChecked = true;
            }
        }
        $this->assertTrue($anonFolderChecked, 'Anonymous folder missing');
        $this->assertTrue($adminFolderChecked, 'Admin folder missing');

        $loadedCuratedfolderDao = $curatedfolderModel->disableFolderCuration($adminFolder);
        $loadedCuratedfolderDao = $curatedfolderModel->disableFolderCuration($anonFolder);
    }
}
