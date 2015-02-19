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

/** Base model class template for the curate module */
abstract class Curate_ModeratorModelBase extends Curate_AppModel {
  /** Constructor */
  public function __construct() {
    parent::__construct();
    $this->_name = 'curate_moderator';
    $this->_key = 'moderator_id';
    $this->_mainData = array(
      'moderator_id' => array('type' => MIDAS_DATA),
      'user_id' => array('type' => MIDAS_DATA),
      'creation_date' => array('type' => MIDAS_DATA),
      'user' =>  array('type' => MIDAS_MANY_TO_ONE,
                       'model' => 'User',
                       'parent_column' => 'user_id',
                       'child_column' => 'user_id'));
    $this->initialize();
  }

  /**
   * adds curation moderator ability to a user.
   */
  function empowerCurationModerator($userDao) {
    // if already a moderator, return that moderator rather than creating a new one
    $moderatorDaos = $this->findBy('user_id', $userDao->getUserId());
    if (count($moderatorDaos) > 0) {
      return $moderatorDaos[0];
    }

    $curationModeratorDao = MidasLoader::newDao('ModeratorDao', 'curate');
    $curationModeratorDao->setUserId($userDao->getUserId());
    $this->save($curationModeratorDao);

    return $curationModeratorDao;
  }




}
