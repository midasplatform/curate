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

class ModeratorModelTest extends DatabaseTestCase
{
    /** set up tests*/
    public function setUp()
    {
        $this->setupDatabase(array('default')); //core dataset
        $this->enabledModules = array('curate');
        $this->_models = array('User');
        Zend_Registry::set('modulesEnable', array());
        Zend_Registry::set('notifier', new MIDAS_Notifier(false, null));
        parent::setUp();
    }

    /** testEmpowerCurationModerator */
    public function testEmpowerCurationModerator()
    {
        $curationModeratorModel = MidasLoader::loadModel('Moderator', 'curate');
        $userDao = $this->User->load(1);
        $empowered = $curationModeratorModel->empowerCurationModerator($userDao);

        // empower same user and make sure we aren't creating multiple rows in the table
        $empowered = $curationModeratorModel->empowerCurationModerator($userDao);
        $curationModeratorDaos = $curationModeratorModel->findBy('user_id', $userDao->getUserId());
        $this->assertEquals(count($curationModeratorDaos), 1, 'too many curation moderator rows created');

        $disempowered = $curationModeratorModel->disempowerCurationModerator($userDao);
    }

    public function testIsCurationModerator()
    {
        $curationModeratorModel = MidasLoader::loadModel('Moderator', 'curate');
        $userDao = $this->User->load(1);
        $empowered = $curationModeratorModel->isCurationModerator($userDao);
        $this->assertEquals($empowered, false, 'User should not be a curation moderator');

        $empowered = $curationModeratorModel->empowerCurationModerator($userDao);
        $this->assertEquals(true, $empowered, 'User should have been empowered as a curation moderator');

        // test an admin user
        $adminUserDao = $this->User->load(1);
        $this->assertEquals(
            true,
            $curationModeratorModel->isCurationModerator($userDao),
            'Admin user should be a curation moderator'
        );

        $disempowered = $curationModeratorModel->disempowerCurationModerator($userDao);
        $this->assertEquals(true, $disempowered, 'User should have been disempowered as a curation moderator');
        $this->assertEquals(
            false,
            $curationModeratorModel->isCurationModerator($userDao),
            'User should not be a curation moderator'
        );
    }
}
