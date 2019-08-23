$(document).ready(function(){
    $("#replymessage").focus(function () {
        var gotValidResponse = false;
        WHMCS.http.jqClient.post("supporttickets.php", { action: "makingreply", id: ticketid, token: csrfToken },
        function(data){
            gotValidResponse = true;
            if (data.isReplying) {
                $("#replyingAdminMsg").html(data.replyingMsg);
                $("#replyingAdminMsg").removeClass('alert-warning').addClass('alert-info');
                if (!$("#replyingAdminMsg").is(":visible")) {
                    $("#replyingAdminMsg").hide().removeClass('hidden').slideDown();
                }
            } else {
                $("#replyingAdminMsg").slideUp();
            }
        }, "json")
        .always(function() {
            if (!gotValidResponse) {
                $("#replyingAdminMsg").html('Session Expired. Please <a href="javascript:location.reload()" class="alert-link">reload the page</a> before continuing.');
                $("#replyingAdminMsg").removeClass('alert-info').addClass('alert-warning');
                if (!$("#replyingAdminMsg").is(":visible")) {
                    $("#replyingAdminMsg").hide().removeClass('hidden').slideDown();
                }
            } else if ($("#replyingAdminMsg").hasClass('alert-warning')) {
                $("#replyingAdminMsg").slideUp();
            }
        });
        return false;
    });

    $("#frmAddTicketReply").submit(function (e, options) {
        options = options || {};

        var formSubmitButton = $('#btnPostReply'),
            thisElement = jQuery(this),
            swapClass = 'fa-reply';

        formSubmitButton.attr("disabled", "disabled");
        formSubmitButton.find('i').removeClass(swapClass).addClass("fa-spinner fa-spin").end();

        if (options.skipValidation) {
            return true;
        }

        e.preventDefault();

        post_validate_changes_and_submit(
            thisElement,
            formSubmitButton,
            swapClass
        );
    });

    $('#frmTicketOptions').submit(function(e, options) {
        options = options || {};

        var formSubmitButton = $('#btnSaveChanges'),
            thisElement = $(this),
            swapClass = 'fa-save';

        formSubmitButton.attr('disabled', 'disabled');
        formSubmitButton.find('i').removeClass(swapClass).addClass('fa-spinner fa-spin').end();

        if (options.skipValidation) {
            return true;
        }

        e.preventDefault();

        post_validate_changes_and_submit(
            thisElement,
            formSubmitButton,
            swapClass
        );


    });

    $("#frmAddTicketNote").submit(function () {
        $("#btnAddNote").attr("disabled", "disabled");
        $("#btnAddNote i").removeClass("fa-reply").addClass("fa-spinner fa-spin");
    });

    $(window).unload( function () {
        WHMCS.http.jqClient.post("supporttickets.php", { action: "endreply", id: ticketid, token: csrfToken });
    });
    $("#insertpredef, #btnInsertPredefinedReply").click(function () {
        $("#prerepliescontainer, #ticketPredefinedReplies").fadeToggle();
        return false;
    });
    /**
     * The following is in place for custom admin themes to facilitate migration.
     * Deprecated - will be removed in a future version
     */
    $("#addfileupload").click(function () {
        $("#fileuploads").append("<input type=\"file\" name=\"attachments[]\" class=\"form-control top-margin-5\">");
        return false;
    });
    $('.add-file-upload').click(function () {
        var moreId = $(this).data('more-id');
        $('#' + moreId).append("<input type=\"file\" name=\"attachments[]\" class=\"form-control top-margin-5\">");
        return false;
    });
    $('#btnAttachFiles').click(function () {
        $('#ticketReplyAttachments').fadeToggle();
    });
    $('#btnNoteAttachFiles').click(function () {
        $('#ticketNoteAttachments').fadeToggle();
    });
    $('#btnAddBillingEntry').click(function (e) {
        e.preventDefault();
        $('#ticketReplyBillingEntry').fadeToggle();
    });
    $('#btnInsertKbArticle').click(function (e) {
        e.preventDefault();
        window.open('supportticketskbarticle.php','kbartwnd','width=500,height=400,scrollbars=yes');
    });
    $("#ticketstatus").change(function (e, options) {
        var currentStatus = $('#currentStatus'),
            skip = 0;

        options = options || {};

        if (options.skipValidation) {
            skip = 1;
        }

        post_validate_and_change(
            {
                action: "changestatus",
                id: ticketid,
                status: this.options[this.selectedIndex].text,
                currentStatus: currentStatus.val(),
                lastReplyId: $('#lastReplyId').val(),
                currentSubject: $('#currentSubject').val(),
                currentCc: $('#currentCc').val(),
                currentUserId: $('#currentUserId').val(),
                currentDepartmentId: $('#currentdeptid').val(),
                currentFlag: $('#currentflagto').val(),
                currentPriority: $('#currentpriority').val(),
                skip: skip,
                token: csrfToken
            },
            currentStatus,
            this.options[this.selectedIndex].text,
            $(this)
        );
    });
    $("#predefq").keypress(function(e){
        // Stop form submit
        if(e.which === 13){
            return false;
        }
    });
    $("#predefq").keyup(function () {
        var intellisearchlength = $("#predefq").val().length;
        if (intellisearchlength>2) {
        WHMCS.http.jqClient.post("supporttickets.php", { action: "loadpredefinedreplies", predefq: $("#predefq").val(), token: csrfToken },
            function(data){
                $("#prerepliescontent").html(data);
            });
        }
    });

    jQuery("#watch-ticket").click(function() {
        var ticketId = jQuery(this).data('ticket-id'),
            adminId = jQuery(this).data('admin-id'),
            adminFullName = jQuery(this).data('admin-full-name'),
            type = jQuery(this).attr('data-type');

        WHMCS.http.jqClient.post(
            'supporttickets.php', {
                action: 'watcher_update',
                admin_id: adminId,
                ticket_id: ticketId,
                type: type,
                token: csrfToken
            },
            function (data) {
                var self = jQuery("#watch-ticket");
                var adminElementId = 'ticket-watcher-' + adminId;
                var $ticketWatcher = jQuery('#' + adminElementId);

                if (data == 1 && type == 'watch') {
                    jQuery(self).attr('data-type', 'unwatch');
                    jQuery(self).addClass('btn-danger').removeClass('btn-info');
                    jQuery(self).html(unwatch_ticket);

                    // Hide the 'None' watcher.
                    jQuery('#ticket-watcher-0').hide();

                    if ($ticketWatcher.length > 0) {
                        $ticketWatcher.show();
                    } else {
                        jQuery('#ticketWatchers').append('<li id="' + adminElementId + '">' + adminFullName + '<li>');
                    }
                }
                if (data == 1 && type == 'unwatch') {
                    jQuery(self).attr('data-type', 'watch');
                    jQuery(self).addClass('btn-info').removeClass('btn-danger');
                    jQuery(self).html(watch_ticket);

                    $ticketWatcher.hide();

                    // Remove any empty list items.
                    jQuery('#ticketWatchers li:empty').remove();

                    // Display 'None' is nothing is visible under Ticket Watchers.
                    if (jQuery("#ticketWatchers").children(":visible").length === 0) {
                        jQuery('#ticket-watcher-0').removeClass('hidden')
                            .show();
                    }
                }
            }
        );
    });


    jQuery(".sidebar-ticket-ajax").on('change', function(e, options) {
        var self = $(this),
            val = self.data('update-type'),
            currentValue = $('#current' + val),
            skip = 0;

        options = options || {};

        if (options.skipValidation) {
            skip = 1;
        }

        post_validate_and_change(
            {
                action: "viewticket",
                id: ticketid,
                updateticket: val,
                value: self.val(),
                currentValue: currentValue.val(),
                lastReplyId: $('#lastReplyId').val(),
                currentSubject: $('#currentSubject').val(),
                currentCc: $('#currentCc').val(),
                currentUserId: $('#currentUserId').val(),
                currentDepartmentId: $('#currentdeptid').val(),
                currentFlag: $('#currentflagto').val(),
                currentPriority: $('#currentpriority').val(),
                currentStatus: $('#currentStatus').val(),
                skip: skip,
                token: csrfToken
            },
            currentValue,
            self.val(),
            self
        );
    });
});

var replyingAdminMessage = $("#replyingAdminMsg");

function doDeleteReply(id) {
    if (confirm(langdelreplysure)) {
        window.location='supporttickets.php?action=viewticket&id='+ticketid+'&sub=del&idsd='+id+'&token='+csrfToken;
    }
}
function doDeleteTicket() {
    if (confirm(langdelticketsure)) {
        window.location='supporttickets.php?sub=deleteticket&id='+ticketid+'&token='+csrfToken;
    }
}
function doDeleteNote(id) {
    if (confirm(langdelnotesure)) {
        window.location='supporttickets.php?action=viewticket&id='+ticketid+'&sub=delnote&idsd='+id+'&token='+csrfToken;
    }
}
function loadTab(target,type,offset) {
    WHMCS.http.jqClient.post("supporttickets.php", { action: "get" + type, id: ticketid, userid: userid, target: target, offset: offset, token: csrfToken },
    function (data) {
        if ($("#tab" + target + "box #tab_content").length > 0) {
            $("#tab" + target + "box #tab_content").html(data);
        } else {
            $("#tab" + target).html(data);
        }
    });
}
function expandRelServices() {
    $("#relatedservicesexpand").html('<img src="images/loading.gif" align="top" /> '+langloading);
    WHMCS.http.jqClient.post("supporttickets.php", { action: "getallservices", id: ticketid, userid: userid, token: csrfToken },
    function(data){
        $("#relatedservicestbl").append(data);
        $("#relatedservicesexpand").fadeOut();
    });
}
function editTicket(id) {
    $(".editbtns"+id+" input[type=button]").prop('disabled', true);
    $(".editbtns"+id+" img.saveSpinner").show();
    WHMCS.http.jqClient.post("supporttickets.php", { action: "getmsg", ref: id, token: csrfToken })
        .done(function(data){
            $(".editbtns"+id).toggle();
            $("#content"+id+" div.message").hide();
            $("#content"+id+" div.message").after('<textarea rows="15" style="width:99%" id="ticketedit'+id+'">'+langloading+'</textarea>');
            $("#ticketedit"+id).val(data);
        })
        .always(function(){
            $(".editbtns"+id+" img.saveSpinner").hide();
            $(".editbtns"+id+" input[type=button]").removeProp('disabled');
        });
}
function editTicketCancel(id) {
    $("#ticketedit"+id).hide();
    $("#content"+id+" div.message").show();
    $(".editbtns"+id+" input[type=button]").prop('disabled', false);
    $(".editbtns"+id).toggle();
}
function editTicketSave(id) {
    $(".editbtns"+id+" input[type=button]").prop('disabled', true);
    $("#ticketedit"+id).prop('disabled', true);
    $(".editbtns"+id+" img.saveSpinner").show();
    WHMCS.http.jqClient.post("supporttickets.php", { action: "updatereply", ref: id, text: $("#ticketedit"+id).val(), token: csrfToken })
        .done(function(data){
            $("#content"+id+" div.message").html(data);
        })
        .always(function(){
            $(".editbtns"+id+" img.saveSpinner").hide();
            editTicketCancel(id);
        });
}
function quoteTicket(id,ids) {
    $(".tab").removeClass("tabselected");
    $("#tab0").addClass("tabselected");
    $(".tabbox").hide();
    $("#tab0box").show();
    WHMCS.http.jqClient.post("supporttickets.php", { action: "getquotedtext", id: id, ids: ids, token: csrfToken },
    function(data){
        $("#replymessage").val(data+"\n\n"+$("#replymessage").val());
    });
    return false;
}
function selectpredefcat(catid) {
    WHMCS.http.jqClient.post("supporttickets.php", { action: "loadpredefinedreplies", cat: catid, token: csrfToken },
    function(data){
        $("#prerepliescontent").html(data);
    });
}
function selectpredefreply(artid) {
    WHMCS.http.jqClient.post("supporttickets.php", { action: "getpredefinedreply", id: artid, token: csrfToken },
    function(data){
        $("#replymessage").addToReply(data);
    });
    $("#prerepliescontainer, #ticketPredefinedReplies").fadeOut();
}

function post_validate_and_change(vars, updateElement, newValue, self)
{
    var gotValidResponse = false,
        responseMsg = '',
        done = false;
    WHMCS.http.jqClient.post(
        "supporttickets.php",
        vars,
        function(data){
            gotValidResponse = true;
            if (typeof data.changes !== 'undefined') {
                if (data.changes === true) {
                    // there have been changes
                    swal({
                            title: changesTitle,
                            text: changes + "\r\n\r\n" + data.changeList,
                            icon: 'info',
                            dangerMode: true,
                            showCancelButton: true,
                            confirmButtonColor: "#DD6B55",
                            confirmButtonText: continueText
                        },
                        function(){
                            replyingAdminMessage.slideUp();
                            self.trigger('change', { 'skipValidation': true });
                        }
                    )
                } else {
                    done = true;
                    updateElement.val(newValue);
                }
            } else {
                // access denied
                responseMsg = 'Access Denied. Please try again.';
            }
        },
        "json"
    )
    .always(function()
        {
            if (!gotValidResponse) {
                responseMsg = 'Session Expired. Please <a href="javascript:location.reload()" class="alert-link">reload the page</a> before continuing.';
            }

            if (responseMsg) {
                replyingAdminMessage.html(responseMsg);
                replyingAdminMessage.removeClass('alert-info').addClass('alert-warning');
                if (!replyingAdminMessage.is(":visible")) {
                    $("#replyingAdminMsg").hide().removeClass('hidden').slideDown();
                }
                $('html, body').animate({
                    scrollTop: replyingAdminMessage.offset().top - 15
                }, 400);
            } else {
                replyingAdminMessage.slideUp();
            }
        }
    );
    return done;
}

function post_validate_changes_and_submit(form, submitButton, swapClass)
{
    var gotValidResponse = false,
        allowPost = false,
        responseMsg = '';

    WHMCS.http.jqClient.post(
        'supporttickets.php',
        {
            action: 'validatereply',
            id: ticketid,
            status: $("#ticketstatus").val(),
            lastReplyId: $('#lastReplyId').val(),
            currentSubject: $('#currentSubject').val(),
            currentCc: $('#currentCc').val(),
            currentUserId: $('#currentUserId').val(),
            currentDepartmentId: $('#currentdeptid').val(),
            currentFlag: $('#currentflagto').val(),
            currentPriority: $('#currentpriority').val(),
            currentStatus: $('#currentStatus').val(),
            token: csrfToken
        },
        function(data){
            gotValidResponse = true;
            if (data.valid) {
                if (data.changes) {
                    // there have been ticket changes
                    allowPost = false;
                    swal({
                        title: changesTitle,
                        text: changes + "\r\n\r\n" + data.changeList,
                        icon: 'info',
                        dangerMode: true,
                        showCancelButton: true,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: continueText
                    },
                        function(){
                            replyingAdminMessage.slideUp();
                            form.attr('data-no-clear', 'false');
                            form.trigger('submit', { 'skipValidation': true });
                        }
                    )
                } else {
                    allowPost = true;
                }
            } else {
                // access denied
                responseMsg = 'Access Denied. Please try again.';
            }
        }, "json")
    .always(function() {
        if (!gotValidResponse) {
            responseMsg = 'Session Expired. Please <a href="javascript:location.reload()" class="alert-link">reload the page</a> before continuing.';
        }

        if (responseMsg) {
            allowPost = false;
            replyingAdminMessage.html(responseMsg);
            replyingAdminMessage.removeClass('alert-info').addClass('alert-warning');
            if (!replyingAdminMessage.is(':visible')) {
                $("#replyingAdminMsg").hide().removeClass('hidden').slideDown();
            }
            $('html, body').animate({
                scrollTop: replyingAdminMessage.offset().top - 15
            }, 400);
        }

        if (allowPost) {
            replyingAdminMessage.slideUp();
            form.attr('data-no-clear', 'false');
            form.trigger('submit', { 'skipValidation': true });
        } else {
            submitButton.removeAttr('disabled');
            submitButton.find('i').removeClass('fa-spinner fa-spin').addClass(swapClass).end();
        }
    });
}
