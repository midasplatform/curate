var midas = midas || {};
midas.curate = midas.curate || {};


midas.curate.createNewCuratedFolder = function() {
    if (json.global.logged) {
        console.log('CREATE CURATED FOLDER');
        midas.loadDialog('createCuratedFolder', '/curate/dashboard/create');
        midas.showDialog('Create Curated Folder', false);
   } else {
        console.log('NEED TO BE LOGGED IN');
    }


};

/*function callbackSelect(node) {
    'use strict';
    console.log('callback select');
    console.log(node);
}

function callbackCheckboxes(node) {
    'use strict';
    console.log('callback checkboxes');
    console.log(node);
}*/
midas.curate.adminApprove = function(curatedfolderId) {
    console.log('adminApprove:'+curatedfolderId);
    // TODO call api endpoint
    //  check admin
    //  change state to approved
    //  remove write access if any
    //  add community member read access
    // TODO frontend
    //  change curation state
    //  change action
}
midas.curate.uploaderRequestApproval = function(curatedfolderId) {
    console.log('uploaderRequestApproval:'+curatedfolderId);
    // TODO call api endpoint
    //  check user has write
    //  change state to requested
    //  remove write access for user, replace with user specific read
    // TODO frontend
    //  change curation state
    //  change action
}}


$(function() {
    'use strict';
    /*$('#curateDashboardTableHeaderCheckbox').qtip({
        content: 'Check/Uncheck All'
    });
                        echo "  <td><input type='checkbox' class='treeCheckbox' type='curatedfolder' element='{$this->escape($curatedfolder_id)}' id='curatedfolderCheckbox{$this->escape($curatedfolder_id)}' /></td>";*/
//    midas.browser.enableSelectAll();
//    $('#curateDashboardTable').treeTable({});
    $('#curateDashboardTable').show();
    $('.curatedfolderCheckbox').change(function() {
        // TODO get the checkbox stateg
       console.log("check or uncheck");
    });
});
