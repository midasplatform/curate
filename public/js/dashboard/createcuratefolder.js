// Midas Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.curate = midas.curate || {};

$('#createCuratedFolderForm').ajaxForm({
    beforeSubmit: validateCreateCuratedFolder,
    success: successCreateCuratedFolder
});

function validateCreateCuratedFolder(formData, jqForm, options) {
    'use strict';
    var form = jqForm[0],
        communityId = form.community.value,
        folderName = form.name.value,
        uploaderId = form.uploader.value;
    if (!$.isNumeric(communityId)) {
        midas.createNotice('Invalid Community, please select again.', 4000, 'error');
        return false;
    }
    if (folderName < 1) {
        midas.createNotice('Folder name cannot be empty', 4000, 'error');
        return false;
    }
    if (!$.isNumeric(uploaderId)) {
        midas.createNotice('Invalid Uploading User, please select again.', 4000, 'error');
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

$(function () {
    'use strict';
    // community

    $('#community_search').focus(function () {
        if ($('#community').val() == 'init') {
            $('#community').val($('#community_search').val());
            $('#community_search').val('');
        }
    });

    $('#community_search').focusout(function () {
        if ($('#community_search').val() == '') {
            $('#community_search').val($('#community').val());
            $('#community').val('init');
        }
    });

    $.widget("custom.communitycatcomplete", $.ui.autocomplete, {
        _renderMenu: function (ul, items) {
            var self = this,
                currentCategory = "Communities";
            ul.append('<li class="search-category">' + currentCategory + "</li>");
            $.each(items, function (index, item) {
                if (item.category === currentCategory) {
                    self._renderItemData(ul, item);
                }
            });
        }
    });

    var communitycache = {},
        lastShareXhr;

    $("#community_search").communitycatcomplete({
        minLength: 2,
        delay: 10,
        source: function (request, response) {
            'use strict';
            var term = request.term;
            if (term in communitycache) {
                response(communitycache[term]);
                return;
            }
            $("#communitysearchloading").show();

            lastShareXhr = $.getJSON($('.webroot').val() + "/search/live?communitySearch=",
                request, function (data, status, xhr) {
                    $("#communitysearchloading").hide();
                    var filteredData = [];
                    $.each(data, function (ind, resource) {
                        if (resource.category === "Communities") {
                            filteredData.push(resource);
                        }
                    });
                    communitycache[term] = filteredData;
                    if (xhr === lastShareXhr) {
                        response(filteredData);
                    }
                });
        }, // end source
        select: function (event, ui) {
            'use strict';
            $('#community_search').val(ui.item.value);
            $('#community').val(ui.item.communityid);
        } // end select
    });

    // uploader user

    $('#uploader_search').focus(function () {
        if ($('#uploader').val() == 'init') {
            $('#uploader').val($('#uploader_search').val());
            $('#uploader_search').val('');
        }
    });

    $('#uploader_search').focusout(function () {
        if ($('#uploader_search').val() == '') {
            $('#uploader_search').val($('#uploader').val());
            $('#uploader').val('init');
        }
    });

    $.widget("custom.usercatcomplete", $.ui.autocomplete, {
        _renderMenu: function (ul, items) {
            var self = this,
                currentCategory = "";
            $.each(items, function (index, item) {
                if (item.category != currentCategory) {
                    ul.append('<li class="search-category">' + item.category + "</li>");
                    currentCategory = item.category;
                }
                self._renderItemData(ul, item);
            });
        }
    });

    var usercache = {},
        lastShareXhr;

    $("#uploader_search").usercatcomplete({
        minLength: 2,
        delay: 10,
        source: function (request, response) {
            'use strict';
            var term = request.term;
            if (term in usercache) {
                response(usercache[term]);
                return;
            }
            $("#uploadersearchloading").show();

            lastShareXhr = $.getJSON($('.webroot').val() + "/search/live?userSearch=true&allowEmail",
                request, function (data, status, xhr) {
                    $("#uploadersearchloading").hide();
                    usercache[term] = data;
                    if (xhr === lastShareXhr) {
                        response(data);
                    }
                });
        }, // end source
        select: function (event, ui) {
            'use strict';
            $('#uploader_search').val(ui.item.value);
            $('#uploader').val(ui.item.userid);
        } // end select
    });
});
