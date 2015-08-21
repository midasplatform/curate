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

require_once BASE_PATH.'/modules/curate/models/base/CuratedfolderModelBase.php';
require_once BASE_PATH.'/modules/curate/models/dao/CuratedfolderDao.php';

/** PDO model template for the curate module */
class Curate_CuratedfolderModel extends Curate_CuratedfolderModelBase
{
    /**
     * gets all curatedfolders a user has access to with the given policy.
     */
    public function getAllFiltered($userDao, $policy)
    {
        if ($userDao == null) {
            $userId = -1;
            $admin = false;
        } else {
            $userId = $userDao->getUserId();
            $admin = $userDao->isAdmin();
        }

        if ($admin) {
            $sql = $this->database->select();
        } else {
            $sql = $this->database->select()->from('curate_curatedfolder')
                //$selectFolderpolicyuser = $this->database->select()->from('curate_curatedfolder')
                ->join(
                    'folder',
                    'folder.folder_id = curate_curatedfolder.folder_id',
                    array()
                )
                ->join(
                    'folderpolicyuser',
                    'folderpolicyuser.folder_id = folder.folder_id',
                    array()
                )
                ->where('folderpolicyuser.user_id = ?', $userId)
                ->where('folderpolicyuser.policy >= ?', $policy);
            /*      $selectFolderpolicygroup = $this->database->select()->from('curate_curatedfolder')
                            ->join('folder',
                                   'folder.folder_id = curate_curatedfolder.folder_id',
                                    array())
                            ->join('folderpolicygroup',
                                   'folderpolicygroup.folder_id = folder.folder_id',
                                    array())
                            ->where('folderpolicygroup.policy >= ?', $policy)
                            ->where("folderpolicygroup.group_id = ?", MIDAS_GROUP_ANONYMOUS_KEY)
                            ->orWhere('folderpolicygroup.group_id IN ('.new Zend_Db_Expr(
                                $this->database->select()->setIntegrityCheck(false)->from(
                                       array('u2g' => 'user2group'),
                                       array('group_id')
                                )->where('u2g.user_id = ?', $userId).')'));
             */
            //$sql = $this->database->select()->union(array($selectFolderpolicyuser, $selectFolderpolicygroup));
            //$sql = $this->database->select($selectFolderpolicyuser);
        }
        $rowset = $this->database->fetchAll($sql);
        $all = array();
        foreach ($rowset as $row) {
            $all[] = $this->initDao('Curatedfolder', $row, 'curate');
        }

        return $all;
    }

    /**
     * Get the total download counts for all items in a folder's subtree,
     * (with no filtered results).
     *
     * @param FolderDao $folder
     *
     * @return string
     * @throws Zend_Exception
     */
    public function getFolderDownloadCounts($folder)
    {
        if (!$folder instanceof FolderDao) {
            throw new Zend_Exception('Input should be a FolderDao');
        }
        $folders = $this->database->select()->setIntegrityCheck(false)->from(
            array('f' => 'folder'),
            array('folder_id')
        )->where(
            'left_index > ?',
            $folder->getLeftIndex()
        )->where('right_index < ?', $folder->getRightIndex());

        $sql = $this->database->select()->distinct()->setIntegrityCheck(false)->from(array('i' => 'item'))->join(
            array('i2f' => 'item2folder'),
            '( '.$this->database->getDB()->quoteInto('i2f.folder_id IN (?)', $folders).'
             OR i2f.folder_id = '.$folder->getKey().'
             )
             AND i2f.item_id = i.item_id',
            array()
        );

        $sql = $this->database->select()->setIntegrityCheck(false)->from(
            array('i' => $sql),
            array('sum' => 'sum(i.download)')
        );
        $row = $this->database->fetchRow($sql);

        return $row['sum'];
    }
}
