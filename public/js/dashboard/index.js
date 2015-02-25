var midas = midas || {};
midas.curate = midas.curate || {};


midas.curate.createNewCuratedFolder = function() {
    'use strict';
    if (json.global.logged) {
        console.log('CREATE CURATED FOLDER');
        midas.loadDialog('createCuratedFolder', '/curate/dashboard/create');
        midas.showDialog('Create Curated Folder', false);
    } else {
        console.log('NEED TO BE LOGGED IN');
    }
};

midas.curate.adminApprove = function(folderId) {
    'use strict';
    ajaxWebApi.ajax({
        method: 'midas.curate.approve.curation',
        args: 'folderId=' + folderId,
        success: function(response) {
            if (response.data == 'OK') {
                $('#curatedFolder'+folderId+' .curationState').text('approved');
                $('#curatedFolder'+folderId+' .curationAction').text('');
            } else {
                // TODO handle error
                console.log(response);
            }
        }
    });
}

midas.curate.uploaderRequestApproval = function(folderId) {
    'use strict';
    ajaxWebApi.ajax({
        method: 'midas.curate.request.approval',
        args: 'folderId=' + folderId,
        success: function(response) {
            console.log(response);
            if (response.data == 'OK') {
                $('#curatedFolder'+folderId+' .curationState').text('requested');
                var actionHTML = '<img src="/core/public/images/icons/time.png" style="padding-right: 5px;">wait for approval';
                $('#curatedFolder'+folderId+' .curationAction').html(actionHTML);
            } else {
                // TODO handle error
                console.log(response);
            }
        }
    });
}


$(function() {
    'use strict';
    $('#curateDashboardTable').show();
});
