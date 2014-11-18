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

require_once BASE_PATH.'/modules/curate/models/base/ModeratorModelBase.php';
require_once BASE_PATH.'/modules/curate/models/dao/ModeratorDao.php';

/** PDO model template for the curate module */
class Curate_ModeratorModel extends Curate_ModeratorModelBase
  {

  /** get All */
  public function getAll()
    {
    $rowset = $this->database->fetchAll($this->database->select()->order(array('moderator_id')));
    $results = array();
    foreach($rowset as $row)
      {
      $results[] = $this->initDao('Moderator', $row, 'curate');
      }
    return $results;
    }

  }
