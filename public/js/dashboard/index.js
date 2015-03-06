var midas = midas || {};
midas.curate = midas.curate || {};


midas.curate.createNewCuratedFolder = function() {
    'use strict';
    if (json.global.logged) {
        midas.loadDialog('createCuratedFolder', '/curate/dashboard/create');
        midas.showDialog('Create Curated Folder', false);
    } else {
        midas.createNotice('You need to be logged in.', 4000, 'error');
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
                midas.createNotice(response.data, 4000, 'error');
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
            if (response.data == 'OK') {
                $('#curatedFolder'+folderId+' .curationState').text('requested');
                var actionHTML = '<img src="/core/public/images/icons/time.png" style="padding-right: 5px;">wait for approval';
                $('#curatedFolder'+folderId+' .curationAction').html(actionHTML);
            } else {
                midas.createNotice(response.data, 4000, 'error');
            }
        }
    });
}


$(function() {
    'use strict';
    $('#curateDashboardTable').show();
});
