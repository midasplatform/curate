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

/** test Curatedfolder model */
require_once BASE_PATH.'/modules/curate/constant/module.php';

class CuratedfolderModelTest extends DatabaseTestCase
{
    /** set up tests */
    public function setUp()
    {
        $this->setupDatabase(array('default')); //core dataset
        $this->enabledModules = array('curate');
        $this->_models = array('Folder');
        Zend_Registry::set('modulesEnable', array());
        Zend_Registry::set('notifier', new MIDAS_Notifier(false, null));
        parent::setUp();
    }

    /** testEnableAndDisableFolderCuration */
    public function testEnableAndDisableFolderCuration()
    {
        $curatedfolderModel = MidasLoader::loadModel('Curatedfolder', 'curate');

        // test curating null folderDao
        $folderDao = null;
        try {
            $curatedfolderDao = $curatedfolderModel->enableFolderCuration($folderDao);
            $this->fail('Should have failed calling enableFolderCuration on null folder');
        } catch (Exception $e) {
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
        try {
            $curatedfolderDao = $curatedfolderModel->disableFolderCuration($folderDao);
            $this->fail('Should have failed calling disableFolderCuration on null folder');
        } catch (Exception $e) {
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

    /** testRequestCurationApproval */
    public function testRequestCurationApproval()
    {
        $curatedfolderModel = MidasLoader::loadModel('Curatedfolder', 'curate');

        // test requesting approval on a null folderDao
        $folderDao = null;
        try {
            $curatedfolderDao = $curatedfolderModel->requestCurationApproval($folderDao);
            $this->fail('Should have failed calling requestCurationApproval on null folder');
        } catch (Exception $e) {
            $this->assertEquals(-1, $e->getCode());
        }

        $folderDao = $this->Folder->load(1000);
        // ensure this folder is not tracked for curation
        $disabled = $curatedfolderModel->disableFolderCuration($folderDao);
        // request curation approval on a folder that isn't tracked under curation
        try {
            $curatedfolderDao = $curatedfolderModel->requestCurationApproval($folderDao);
            $this->fail('Should have failed calling requestCurationApproval on untracked folder');
        } catch (Exception $e) {
            $this->assertEquals(-1, $e->getCode());
        }

        $enabled = $curatedfolderModel->enableFolderCuration($folderDao);

        // empower a user as a curation moderator
        $userModel = MidasLoader::loadModel('User');
        $userDao = $userModel->load(1);
        $curationModeratorModel = MidasLoader::loadModel('Moderator', 'curate');
        $empowered = $curationModeratorModel->empowerCurationModerator($userDao);

        $curatedfolderDao = $curatedfolderModel->requestCurationApproval($folderDao);
        $this->assertEquals($curatedfolderDao->getFolderId(), $folderDao->getFolderId());
        $this->assertEquals($curatedfolderDao->getCurationState(), CURATE_STATE_REQUESTED);

        // ensure this folder is not tracked for curation
        $disabled = $curatedfolderModel->disableFolderCuration($folderDao);
    }

    /** testApproveCurationRequest */
    public function testApproveCurationRequest()
    {
        $curatedfolderModel = MidasLoader::loadModel('Curatedfolder', 'curate');

        // test approving a null folderDao
        $folderDao = null;
        try {
            $curatedfolderDao = $curatedfolderModel->approveCurationRequest($folderDao);
            $this->fail('Should have failed calling approveCurationRequest on null folder');
        } catch (Exception $e) {
            $this->assertEquals(-1, $e->getCode());
        }

        $folderDao = $this->Folder->load(1000);
        // ensure this folder is not tracked for curation
        $disabled = $curatedfolderModel->disableFolderCuration($folderDao);
        // approve a folder that isn't tracked under curation
        try {
            $curatedfolderDao = $curatedfolderModel->approveCurationRequest($folderDao);
            $this->fail('Should have failed calling approveCurationRequest on untracked folder');
        } catch (Exception $e) {
            $this->assertEquals(-1, $e->getCode());
        }

        $enabled = $curatedfolderModel->enableFolderCuration($folderDao);

        // empower a user as a curation moderator
        $userModel = MidasLoader::loadModel('User');
        $userDao = $userModel->load(1);
        $curationModeratorModel = MidasLoader::loadModel('Moderator', 'curate');
        $empowered = $curationModeratorModel->empowerCurationModerator($userDao);

        // approve a folder that isn't in the requested state
        try {
            $curatedfolderDao = $curatedfolderModel->approveCurationRequest($folderDao);
            $this->fail('Should have failed calling approveCurationRequest on folder not in requested state.');
        } catch (Exception $e) {
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

    /** _trackUserReadFolders */
    public function _trackUserReadFolders($folder, $userReadFolders, $policies)
    {
        foreach ($policies as $userInd => $policy) {
            if ($policy) {
                $userReadFolders[$userInd][] = $folder;
            }
        }

        return $userReadFolders;
    }

    /** _ensureReadPolicy */
    public function _ensureReadPolicy($folder, $users, $policies)
    {
        foreach ($policies as $ind => $policy) {
            $this->assertEquals($policies[$ind], $this->Folder->policyCheck($folder, $users[$ind], MIDAS_POLICY_READ));
        }
    }

    public function _createItemWithStats(
        $itemName,
        $folder,
        $sizeBytes,
        $downloadCount,
        $expectedFolderStats,
        $rootCuratedFolder
    ) {
        $itemModel = MidasLoader::loadModel('Item');
        $item = $itemModel->createItem($itemName, $itemName, $folder);
        $item->setSizebytes($sizeBytes);
        $item->setDownload($downloadCount);
        $itemModel->save($item);
        $rootFolderKey = $rootCuratedFolder->getFolderId();
        if (!array_key_exists($rootFolderKey, $expectedFolderStats)) {
            $expectedFolderStats[$rootFolderKey] = array('size' => 0, 'download' => 0);
        }
        $expectedFolderStats[$rootFolderKey]['size'] += $sizeBytes;
        $expectedFolderStats[$rootFolderKey]['download'] += $downloadCount;

        return $expectedFolderStats;
    }

    /** testListAllCuratedFolders */
    public function testListAllCuratedFolders()
    {
        $userModel = MidasLoader::loadModel('User');
        // first user is empty, for anonymous access
        $users = array();
        $users[] = null;
        foreach (range(1, 3) as $userId) {
            $users[] = $userModel->load($userId);
        }

        $userReadFolders = array();
        foreach (range(0, 3) as $userInd) {
            $userReadFolders[] = array();
        }

        // folder 1 is read for user 3
        $folder1 = $this->Folder->createFolder('curateFolder1', '', -1);
        $policies = array(false, false, false, true);
        $userReadFolders = $this->_trackUserReadFolders($folder1, $userReadFolders, $policies);
        $this->_ensureReadPolicy($folder1, $users, $policies);

        // create items and subfolders with [size, download]
        // e.g. folder 1: item11 [1000, 1]
        // track this mapping to root folder_id, i.e. the top level folder that all stats
        // should accumulate up to
        $expectedFolderStats = array();
        $expectedFolderStats = $this->_createItemWithStats('item11', $folder1, 1000, 1, $expectedFolderStats, $folder1);

        // folder 2 is read for user 3
        // folder 2 is read for user 1 by folderpolicyuser
        $folder2 = $this->Folder->createFolder('curateFolder2', '', -1);
        $policies = array(false, true, false, true);
        $userReadFolders = $this->_trackUserReadFolders($folder2, $userReadFolders, $policies);
        $folderpolicyuserModel = MidasLoader::loadModel('Folderpolicyuser');
        $folderpolicyuserModel->createPolicy($users[1], $folder2, MIDAS_POLICY_READ);
        $this->_ensureReadPolicy($folder2, $users, $policies);

        // subfolder21
        $folder21 = $this->Folder->createFolder('subfolder21', '', $folder2->getFolderId());
        // item211: [2000, 10]
        $expectedFolderStats = $this->_createItemWithStats(
            'item211',
            $folder21,
            2000,
            10,
            $expectedFolderStats,
            $folder2
        );
        // subfolder22
        $folder22 = $this->Folder->createFolder('subfolder22', '', $folder2->getFolderId());
        // item221: [3000, 20]
        $expectedFolderStats = $this->_createItemWithStats(
            'item221',
            $folder22,
            3000,
            20,
            $expectedFolderStats,
            $folder2
        );

        // folder 3 is read for user 3
        // folder 3 is read for user 2 by folderpolicygroup
        $folder3 = $this->Folder->createFolder('curateFolder3', '', -1);
        $policies = array(false, false, true, true);
        $userReadFolders = $this->_trackUserReadFolders($folder3, $userReadFolders, $policies);
        // create a group tied to a community
        $communityModel = MidasLoader::loadModel('Community');
        $community = $communityModel->load(2000);
        $groupModel = MidasLoader::loadModel('Group');
        $group = $groupModel->createGroup($community, 'folder3readgroup');
        $folderpolicygroupModel = MidasLoader::loadModel('Folderpolicygroup');
        $groupModel->addUser($group, $users[2]);
        $folderpolicygroupModel->createPolicy($group, $folder3, MIDAS_POLICY_READ);
        $this->_ensureReadPolicy($folder3, $users, $policies);

        // item31: [2000, 2]
        // item32: [2000, 3]
        $expectedFolderStats = $this->_createItemWithStats('item31', $folder3, 2000, 2, $expectedFolderStats, $folder3);
        $expectedFolderStats = $this->_createItemWithStats('item32', $folder3, 2000, 3, $expectedFolderStats, $folder3);
        // subfolder31
        $folder31 = $this->Folder->createFolder('subfolder31', '', $folder3->getFolderId());
        // item311: [2000, 10]
        $expectedFolderStats = $this->_createItemWithStats(
            'item311',
            $folder31,
            2000,
            10,
            $expectedFolderStats,
            $folder3
        );
        // item312: [7000, 7]
        $expectedFolderStats = $this->_createItemWithStats(
            'item312',
            $folder31,
            7000,
            7,
            $expectedFolderStats,
            $folder3
        );
        // subfolder32
        $folder32 = $this->Folder->createFolder('subfolder32', '', $folder3->getFolderId());
        // item321: [2000, 200]
        $expectedFolderStats = $this->_createItemWithStats(
            'item321',
            $folder32,
            2000,
            200,
            $expectedFolderStats,
            $folder3
        );
        // subfolder321
        $folder321 = $this->Folder->createFolder('subfolder321', '', $folder32->getFolderId());
        // item3211: [4700, 40]
        $expectedFolderStats = $this->_createItemWithStats(
            'item3211',
            $folder321,
            4700,
            40,
            $expectedFolderStats,
            $folder3
        );

        // folder 4 is read for all users because of anonymous group
        $folder4 = $this->Folder->createFolder('curateFolder4', '', -1);
        $policies = array(true, true, true, true);
        $userReadFolders = $this->_trackUserReadFolders($folder4, $userReadFolders, $policies);
        $anonGroup = $groupModel->load(MIDAS_GROUP_ANONYMOUS_KEY);
        $folderpolicygroupModel->createPolicy($anonGroup, $folder4, MIDAS_POLICY_READ);
        $this->_ensureReadPolicy($folder4, $users, $policies);

        // folder 4 [0, 0]
        $expectedFolderStats[$folder4->getFolderId()] = array('size' => 0, 'download' => 0);

        // enable all folders for curation
        $folders = array($folder1, $folder2, $folder3, $folder4);
        $curatedfolderModel = MidasLoader::loadModel('Curatedfolder', 'curate');
        foreach ($folders as $folder) {
            $enabled = $curatedfolderModel->enableFolderCuration($folder);
        }

        // check that each user sees the correct curation folders
        foreach ($users as $userInd => $user) {
            $expectedFoldersForUser = $userReadFolders[$userInd];
            $curatedFolders = $curatedfolderModel->listAllCuratedFolders($user);
            $this->assertEquals(
                count($expectedFoldersForUser),
                count($curatedFolders),
                'Expected folders and curated folders differ in count'
            );
            foreach ($expectedFoldersForUser as $expectedFolder) {
                $found = false;
                foreach ($curatedFolders as $curatedFolder) {
                    if ($expectedFolder->getFolderId() == $curatedFolder['folder_id']) {
                        $found = true;
                        // check that stats are correct
                        $this->assertEquals(
                            $expectedFolderStats[$curatedFolder['folder_id']]['download'],
                            $curatedFolder['download'],
                            'Did not find expected download value for curated folder with folder_id '.$curatedFolder['folder_id']
                        );
                        $this->assertEquals(
                            $expectedFolderStats[$curatedFolder['folder_id']]['size'],
                            $curatedFolder['size'],
                            'Did not find expected size value for curated folder with folder_id '.$curatedFolder['folder_id']
                        );
                        $this->assertEquals(
                            $curatedFolder['curation_state'],
                            CURATE_STATE_CONSTRUCTION,
                            'Did not find expected curation_state for curated folder with folder_id '.$curatedFolder['folder_id']
                        );
                    }
                }
                $this->assertTrue(
                    $found,
                    'Did not find a match in the curated folders for expected folder with id '.$curatedFolder['folder_id']
                );
            }
        }

        // disable all folders for curation
        foreach ($folders as $folder) {
            $enabled = $curatedfolderModel->disableFolderCuration($folder);
        }
    }
}
