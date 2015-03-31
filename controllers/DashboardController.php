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

/** DashboardController */
class Curate_DashboardController extends Curate_AppController {

  /** Init Controller */
  public function init() {
      $this->view->activemenu = 'curate/dashboard';
  }

  /** index action */
  public function indexAction() {
    $this->view->header = $this->t("Curation Dashboard");
    $curatedfolderModel = MidasLoader::loadModel('Curatedfolder', 'curate');
    $this->view->isAdmin = false;
    if ($this->logged) {
      $userDao = $this->userSession->Dao;
      if ($userDao->isAdmin()) {
        $this->view->isAdmin = true;
        $this->view->curatedFolders = $curatedfolderModel->listAllCuratedFolders($userDao, MIDAS_POLICY_ADMIN);
      } else {
        $this->view->curatedFolders = $curatedfolderModel->listAllCuratedFolders($userDao, MIDAS_POLICY_READ);
      }
    } else {
      $userDao = null;
    }
  }




  /** create create curated folder form */
  public function createCuratedFolderForm($displayCommunities = array(), $displayUsers = array()) {
    $form = new Zend_Form();
    $form->setAction('dashboard/create')->setMethod('post');

    $communitySelect = new Zend_Form_Element_Select('community');
    $communitySelect->addMultiOptions($displayCommunities);

    $name = new Zend_Form_Element_Text('name');
    $name->setRequired(true)->addValidator('NotEmpty', true);

    $description = new Zend_Form_Element_Textarea('description');

    $uploader = new Zend_Form_Element_Select('uploader');
    $uploader->addMultiOptions($displayUsers);

    $submit = new  Zend_Form_Element_Submit('submit');
    $submit->setLabel($this->t("Create"));

    $form->addElements(array($communitySelect, $name, $description, $uploader, $submit));

    return $form;
  }

  /** create a curated folder (ajax) */
  public function createAction() {
    $this->requireAdminPrivileges();
    if ($this->_request->isPost()) {
        $this->disableLayout();
        $this->disableView();

        $formParams = $this->getRequest()->getPost();
        $name = $formParams['name'];
        $description = $formParams['description'];
        $communityId = $formParams['community'];
        $uploader = $formParams['uploader'];

        $communityModel = MidasLoader::loadModel('Community');
        $community = $communityModel->load($communityId);
        $folderModel = MidasLoader::loadModel('Folder');
        if ($folderModel->getFolderExists($name, $community->getFolder())) {
            echo JsonComponent::encode(array(false, $this->t('A Folder with that name already exists in the selected Community')));
            return;
        }

        // create a top level folder in the community
        $curatedFolder = $folderModel->createFolder($name, $description, $community->getFolder());

        // track the folder for curation
        $curatedfolderModel = MidasLoader::loadModel('Curatedfolder', 'curate');
        $curatedfolderModel->enableFolderCuration($curatedFolder);

        // give the uploader user ADMIN access to the folder
        $userModel = MidasLoader::loadModel('user');
        $user = $userModel->load($uploader);
        $folderpolicyuserModel = MidasLoader::loadModel('Folderpolicyuser');
        $folderpolicyuserModel->createPolicy($user, $curatedFolder, MIDAS_POLICY_WRITE);

        // notify the uploader user that the curated folder has been created
        $utilityComponent = MidasLoader::loadComponent('Utility');
        // TODO NOTIFY
        // TODO update message
        // probably set host and origin
        $body = "Dear ".$user->getFirstname()." ".$user->getLastname().",\nA curated folder has been created for you to upload a dataset into at qidw.rsna.org.  The curated folder ".$curatedFolder->getName()." can be found at http://qidw.rsna.org/folder/".$curatedFolder->getFolderId()." .  You will need to log in to view it.\n\nThanks,\nqidw.rsna.org admin\n";
        $utilityComponent->sendEmail($user->getEmail(), 'Curated Folder Created', $body);

        echo JsonComponent::encode(array(true, $this->t('Folder successfully created')));
        return;
    } else {
      $communityModel = MidasLoader::loadModel('Community');
      $communities = $communityModel->getAll();
      $displayCommunities = array();
      foreach ($communities as $community) {
        $displayCommunities[$community->getCommunityId()] = $community->getName();
      }

      $userModel = MidasLoader::loadModel('User');
      $users = $userModel->getAll();
      $displayUsers = array();
      $userOrgs = array();
      foreach ($users as $user) {
        $displayUsers[$user->getUserId()] = $user->getFirstname() .' '. $user->getLastname();
        $userOrgs[$user->getUserId()] = $user->getCompany();
      }
      // TODO how to put userOrgs in json and get it out on the page
      $form = $this->createCuratedFolderForm($displayCommunities, $displayUsers);
      $this->disableLayout();
      $this->view->form = $this->getFormAsArray($form);
      $this->view->json['userOrgs'] = $userOrgs;
    }

  }

}
