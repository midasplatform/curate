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
//      echo "LOGGED";
      $userDao = $this->userSession->Dao;
      if ($userDao->isAdmin()) {
//        echo "ADMIN";
        $this->view->isAdmin = true;
        $this->view->curatedFolders = $curatedfolderModel->listAllCuratedFolders($userDao, MIDAS_POLICY_ADMIN);
      } else {
//        echo "WEAK";
        $this->view->curatedFolders = $curatedfolderModel->listAllCuratedFolders($userDao, MIDAS_POLICY_WRITE);
      }
    } else {
//      echo "NOTLOGGED";
      $userDao = null;
    }
//    $this->disableLayout();
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
    if ($this->_request->isPost()){// && $form->isValid($this->getRequest()->getPost())) {
        // TODO check user is admin
        //$this->disableLayout();
        $formParams = $this->getRequest()->getPost();
        $name = $formParams['name'];
        $description = $formParams['description'];
        $communityId = $formParams['community'];
        $uploader = $formParams['uploader'];
        //echo $name;
        //echo $communityId;
        $communityModel = MidasLoader::loadModel('Community');
        $community = $communityModel->load($communityId);
        //var_dump($community);
        // TODO check that foldername isn't blank and isn't taken

        // create a top level folder in the community
        $folderModel = MidasLoader::loadModel('Folder');
        $curatedFolder = $folderModel->createFolder($name, $description, $community->getFolder());

        // track the folder for curation
        $curatedfolderModel = MidasLoader::loadModel('Curatedfolder', 'curate');
        $curatedfolderModel->enableFolderCuration($curatedFolder);

        // give the uploader user ADMIN access to the folder
        $userModel = MidasLoader::loadModel('user');
        $user = $userModel->load($uploader);
        $folderpolicyuserModel = MidasLoader::loadModel('Folderpolicyuser');
        $folderpolicyuserModel->createPolicy($user, $curatedFolder, MIDAS_POLICY_WRITE);

        $this->redirect('/community/'.$communityId);




 /*
        //$form = $this->createCuratedFolderForm();
        //echo 'form vali'.$form->isValid($this->getRequest()->getPost());
        //var_dump($this->getRequest()->getPost());
        */
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
