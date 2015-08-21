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

/** Base model class template for the curate module */
abstract class Curate_ModeratorModelBase extends Curate_AppModel
{
    /** Constructor */
    public function __construct()
    {
        parent::__construct();
        $this->_name = 'curate_moderator';
        $this->_key = 'moderator_id';
        $this->_mainData = array(
            'moderator_id' => array('type' => MIDAS_DATA),
            'user_id' => array('type' => MIDAS_DATA),
            'creation_date' => array('type' => MIDAS_DATA),
            'user' => array(
                'type' => MIDAS_MANY_TO_ONE,
                'model' => 'User',
                'parent_column' => 'user_id',
                'child_column' => 'user_id',
            ),
        );
        $this->initialize();
    }

    /**
     * boolean check to see if a given user is a curation moderator.
     *
     * @return bool indicating if the user has curation moderator powers
     */
    public function isCurationModerator($userDao)
    {
        if (!$userDao) {
            return false;
        }
        if ($userDao->getAdmin()) {
            return true;
        }
        $moderatorDaos = $this->findBy('user_id', $userDao->getUserId());
        if (count($moderatorDaos) > 0) {
            return true;
        }
    }

    /**
     * gets all users authorized as curation moderators.
     *
     * @return array of user_id to UserDao objects
     */
    public function getAllCurationModerators()
    {
        // get all users that are admin
        $userModel = MidasLoader::loadModel('User');
        $adminDaos = $userModel->findBy('admin', '1');

        $moderators = array();
        foreach ($adminDaos as $admin) {
            $moderators[$admin->getUserId()] = $admin;
        }

        // combine with all admins with those that are moderators
        $curationModeratorDaos = $this->getAll();
        foreach ($curationModeratorDaos as $curationModerator) {
            $moderatorUser = $curationModerator->getUser();
            $moderators[$moderatorUser->getUserId()] = $moderatorUser;
        }

        return $moderators;
    }

    /**
     * adds curation moderator ability to a user.
     *
     * @return bool indicating success of empowerment
     */
    public function empowerCurationModerator($userDao)
    {
        if (!$userDao) {
            return false;
        }
        if (!$this->isCurationModerator($userDao)) {
            $curationModeratorDao = MidasLoader::newDao('ModeratorDao', 'curate');
            $curationModeratorDao->setUserId($userDao->getUserId());
            $this->save($curationModeratorDao);
        }

        return true;
    }

    /**
     * removes curation moderator ability from a user.
     *
     * @return bool indicating success of disempowerment
     */
    public function disempowerCurationModerator($userDao)
    {
        if (!$userDao) {
            // something that wasn't a user is already disempowered
            return true;
        }
        if ($userDao->getAdmin()) {
            // cannot disempower admin
            return false;
        }
        if ($this->isCurationModerator($userDao)) {
            $moderatorDaos = $this->findBy('user_id', $userDao->getUserId());
            $moderatorDao = $moderatorDaos[0];
            $this->delete($moderatorDao);
        }

        return true;
    }
}
