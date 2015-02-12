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

require_once BASE_PATH.'/modules/curate/models/base/CuratedfolderModelBase.php';
require_once BASE_PATH.'/modules/curate/models/dao/CuratedfolderDao.php';

/** PDO model template for the curate module */
class Curate_CuratedfolderModel extends Curate_CuratedfolderModelBase
  {

  /**
   * gets all curatedfolders a user has Read access to
   */
  function getAll($userDao)
    {

    if($userDao == null)
      {
      $userId = -1;
      $admin = false;
      }
    else
      {
      $userId = $userDao->getUserId();
      $admin = $userDao->isAdmin();
      }

    if($admin)
      {
      $sql = $this->database->select();
      }
    else
      {
      $policy = MIDAS_POLICY_READ;
      // if the user has access from any folderpolicyuser
      $subqueryUser = $this->database->select()->setIntegrityCheck(false)->from(
          array('cf' => 'curate_curatedfolder'))
          ->join(array('f' => 'folder'), 'cf.folder_id = f.folder_id', array())
          ->join(array('fpu' => 'folderpolicyuser'), 'fpu.folder_id = f.folder_id', array())
          ->where("fpu.user_id = ? and fpu.policy >= ?", $userId, $policy);
      // or if the user has access from any folderpolicygroup from the user's groups
      // or if the folder is public (has a folderpolicygroup in the Anonymous group)
      $subqueryGroup = $this->database->select()->setIntegrityCheck(false)->from(
          array('cf' => 'curate_curatedfolder'))
          ->join(array('f' => 'folder'), 'cf.folder_id = f.folder_id', array())
          ->join(array('fpg' => 'folderpolicygroup'), 'fpg.folder_id = f.folder_id', array())
          ->where("fpg.group_id = ?", MIDAS_GROUP_ANONYMOUS_KEY)
          ->orWhere('fpg.group_id IN ('.new Zend_Db_Expr(
            $this->database->select()->setIntegrityCheck(false)->from(
                array('u2g' => 'user2group'),
                array('group_id')
            )->where('u2g.user_id = ?', $userId).')'));

      $sql = $this->database->select()->union(array($subqueryUser, $subqueryGroup));
      }
    $rowset = $this->database->fetchAll($this->database->select());

    $all = array();
    foreach($rowset as $row)
      {
      $all[] = $this->initDao('Curatedfolder', $row, 'curate');
      }
    return $all;
    }
  }
