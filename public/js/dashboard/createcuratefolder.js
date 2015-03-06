var midas = midas || {};
midas.curate = midas.curate || {};


$('#createCuratedFolderForm').ajaxForm({
    beforeSubmit: validateCreateCuratedFolder ,
    success: successCreateCuratedFolder
});

function validateCreateCuratedFolder(formData, jqForm, options) {
    'use strict';
    var form = jqForm[0];
    var communityId = form.community.value;
    var folderName = form.name.value;
    if (folderName < 1) {
        midas.createNotice('Folder name cannot be empty', 4000, 'error');
        return false;
    }
    return true;
}

function successCreateCuratedFolder(responseText, statusText, xhr, form) {
    'use strict';
    var response = JSON.parse(responseText);
    var created = response[0];
    var message = response[1];
    if (created) {
        // redirect to dashboard
        window.location = json.global.webroot + '/curate/dashboard';
    } else {
        midas.createNotice(message, 4000, 'error');
    }
}
