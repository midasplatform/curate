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

require_once BASE_PATH.'/modules/api/library/APIEnabledNotification.php';

/** Notification manager for the curate module */
class Curate_Notification extends ApiEnabled_Notification
{
    public $moduleName = 'curate';
    public $_moduleComponents = array('Api');

    /** Init notification process */
    public function init()
    {
        $fc = Zend_Controller_Front::getInstance();
        $this->moduleWebroot = $fc->getBaseUrl().'/modules/'.$this->moduleName;
        $this->coreWebroot = $fc->getBaseUrl().'/core';
        $this->enableWebAPI($this->moduleName);
        $this->addcallback('CALLBACK_CORE_GET_LEFT_LINKS', 'getLeftLink');
        $this->addcallback('CALLBACK_CORE_FOLDER_DELETED', 'folderDeleted');
    }

    /** adds a left link to overall Midas layout for Curation Dashboard */
    public function getLeftLink()
    {
        $fc = Zend_Controller_Front::getInstance();
        $baseURL = $fc->getBaseUrl();
        $moduleWebroot = $baseURL.'/'.$this->moduleName;

        return array(
            'Curation Dashboard' => array(
                $moduleWebroot.'/dashboard',
                $baseURL.'/core/public/images/icons/ok.png',
            ),
        );
    }

    /** remove any curated folders if the folder is deleted */
    public function folderDeleted($args)
    {
        $folder = $args['folder'];
        $curatedfolderModel = MidasLoader::loadModel('Curatedfolder', 'curate');
        $curatedfolderDaos = $curatedfolderModel->findBy('folder_id', $folder->getFolderId());
        if (count($curatedfolderDaos) > 0) {
            $curatedfolderModel->delete($curatedfolderDaos[0]);
        }
    }
}
