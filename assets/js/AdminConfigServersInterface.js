$(document).ready(function(){
    $("#inputOverridePort").click(function() {
        if ($("#inputOverridePort").prop("checked")) {
            $("#inputPort").prop("disabled", false);
        } else {
            $("#inputPort").prop("disabled", true);
            if ($("#inputSecure").prop("checked")) {
                $("#inputPort").val(defaultSSLPort);
            } else {
                $("#inputPort").val(defaultNonSSLPort);
            }
        }
    });

    $("#inputSecure").click(function() {
        if (!$("#inputOverridePort").prop("checked")) {
            if ($("#inputSecure").prop("checked")) {
                $("#inputPort").val(defaultSSLPort);
            } else {
                $("#inputPort").val(defaultNonSSLPort);
            }
        }
    });
    $("#preAddForm").find("input,textarea,select").on("keyup change", function(){
        var copyValue = $(this).val();
        var targetField = $(this).data("related-id");
        if (targetField == "inputHostname") {
            var cleanedvalue = copyValue.split(".").join("");
            if ($.isNumeric(cleanedvalue)) {
                targetField = "inputPrimaryIp";
                $("#inputHostname").val("");
            }
        }
        $("#" + targetField).val(copyValue);
    });

    $("#inputServerType,#addType").change(function() {
        WHMCS.http.jqClient.post(
            "configservers.php",
            'token=' + csrfToken + '&action=getmoduleinfo&type=' + $(this).val(),
            function(data) {
                $(".connection-test-result").fadeOut();
                if (data.cantestconnection=="1") {
                    connectionTestSupported = true;
                    $("#connectionTestBtn").fadeIn();
                    $("#newTestConn").show();
                    $("#newContAny").show();
                    $("#newCont").hide();
                } else {
                    $("#connectionTestBtn").fadeOut();
                    $("#newContAny").hide();
                    connectionTestSupported = false;
                    $("#newTestConn").hide();
                    $("#newCont").removeClass("hidden").show();
                }
                if (data.supportsadminsso=="1") {
                    $("#containerAccessControl").fadeIn();
                } else {
                    $("#containerAccessControl").fadeOut();
                }
                defaultSSLPort = data.defaultsslport;
                defaultNonSSLPort = data.defaultnonsslport;
                if (!defaultSSLPort && !defaultNonSSLPort) {
                    $("#trPort").fadeOut();
                } else {
                    $("#trPort").fadeIn();
                }
                if (!$("#inputOverridePort").prop("checked")) {
                    if ($("#inputSecure").prop("checked")) {
                        $("#inputPort").val(defaultSSLPort);
                    } else {
                        $("#inputPort").val(defaultNonSSLPort);
                    }
                }
                var accessHash = $("#serverHash"),
                    apiToken = $("#apiToken");
                if (typeof data.apiTokens !== "undefined" && data.apiTokens === true) {
                    var currentAccessHash = accessHash.val();
                    if (accessHash.hasClass('hidden') === false && (!currentAccessHash || currentAccessHash.indexOf("\n") < 0)) {
                        apiToken.removeClass('hidden').prop('disabled', false);
                        apiToken.val(currentAccessHash);
                        $("#newToken").removeClass('hidden').prop('disabled', false)
                            .val(currentAccessHash);
                        accessHash.addClass('hidden').prop('disabled', true);
                        $("#newHash").addClass('hidden').prop('disabled', true);
                        $("span.access-hash").hide();
                        $("span.api-key").removeClass('hidden').show();
                    }
                } else {
                    if (accessHash.hasClass('hidden')) {
                        var currentApiToken = apiToken.val();
                        accessHash.removeClass('hidden').prop('disabled', false);
                        accessHash.text(currentApiToken);
                        $("#newHash").removeClass('hidden').prop('disabled', false)
                            .text(currentApiToken);
                        apiToken.addClass('hidden').prop('disabled', true);
                        $("#newToken").addClass('hidden').prop('disabled', true);
                        $("span.api-key").hide();
                        $("span.access-hash").removeClass('hidden').show();
                    }
                }
            },
            "json"
        );
    });
    $("#addType").change();

    $("#connectionTestBtn").click(function() {
        $(".alert.connection-test-result").removeClass("alert-success")
            .removeClass("alert-danger").addClass("alert-grey")
            .html($("#newServerWizardConnecting").html())
            .hide().removeClass("hidden").fadeIn();
        WHMCS.http.jqClient.jsonPost({
            url: "configservers.php",
            data: $("#frmServerConfig").serialize() + '&action=testconnection',
            success: function(data) {
                if (data.success) {
                    // growl success
                    var values = data.autoPopulateValues,
                        inputName = $("#inputName"),
                        inputHostname = $("#inputHostname"),
                        inputPrimaryIp = $("#inputPrimaryIp"),
                        inputAssignedIps = $("#assignedIps");
                    if (values.name && inputName.val() == "") {
                        inputName.val(values.name);
                    }
                    if (values.hostname && inputHostname.val() == "") {
                        inputHostname.val(values.hostname);
                    }
                    if (values.primaryIp && inputPrimaryIp.val() == "") {
                        inputPrimaryIp.val(values.primaryIp);
                    }
                    $.each(values.nameservers, function(index, value) {
                        index = index + 1;
                        var input = $("input[name='nameserver" + index + "']");
                        if (input.val() == "") {
                            input.val(value);
                        }
                    });
                    $(".alert.connection-test-result").removeClass("alert-grey").addClass("alert-success")
                        .html($("#newServerWizardSuccess").html());
                    $("#newServerWizardSuccess").removeClass("hidden").show();
                    $("#newContAny").click();
                } else {
                    $(".alert.connection-test-result").removeClass("alert-grey").addClass("alert-danger")
                        .html(data.errorMsg);
                }
            },
            always: function() {
                $("#newContAny").removeAttr("disabled");
            }
        });
    });
    $("#newTestConn").click(function(e) {
        $("#connectionTestBtn").click();
    });
    $("#newCont,#newContAny").click(function(e) {
        if (!$("#frmServerConfig").is(":visible")) {
            $("#preAddForm").slideUp("fast");
            $("#frmServerConfig").hide()
                .removeClass("hidden")
                .slideDown("fast");
        }
    });
    $("#newServerWizardBanner a").click(function(e) {
        e.preventDefault();
        $("#preAddForm").hide();
        $("#newServerWizardBanner").hide();
        $("#frmServerConfig").removeClass("hidden").show();
    });

    $("#serveradd").click(function () {
        $("#serverslist option:selected").appendTo("#selectedservers");
        return false;
    });
    $("#serverrem").click(function () {
        $("#selectedservers option:selected").appendTo("#serverslist");
        return false;
    });

    $('#btnRefreshAllData').on('click', function (e) {
        e.preventDefault();
        if ($(this).hasClass('disabled')) {
            return;
        }
        var serverRows = $('.refresh-server-item').not('.disabled');
        refreshingAccounts = serverRows.length;
        if (refreshingAccounts === 0) {
            return;
        }
        $(this).addClass('disabled').prop('disabled', true).find('i').addClass('fa-spin');
        serverRows.each(function(index) {
            $(this).click();
        });
    });

    $('.refresh-server-item').on('click', function (e) {
        e.preventDefault();
        if ($(this).hasClass('disabled')) {
            return;
        }
        var serverId = $(this).data('server-id'),
            faTag = $(this).find('i');
        if (typeof serverId === "undefined" || serverId === 0) {
            return;
        }
        faTag.addClass('fa-spin')
            .closest('.btn')
            .prop('disabled', true)
            .addClass('disabled');
        /**
         * Ajax Call
         */
        WHMCS.http.jqClient.jsonPost({
            url: WHMCS.adminUtils.getAdminRouteUrl('/setup/servers/meta/refresh'),
            data: {
                id: serverId,
                token: csrfToken
            },
            success: function(response) {
                faTag.closest('tr')
                    .find('.remote-meta-data')
                    .html(response.metaData);
                faTag.closest('tr')
                    .find('.server-usage-count')
                    .html(response.numAccounts);
            },
            error: function(error) {
                jQuery.growl.warning(
            {
                        title: error.title,
                        message: error.message
                    }
                );
            },
            always: function () {
                faTag.removeClass('fa-spin')
                    .closest('.btn')
                    .prop('disabled', false)
                    .removeClass('disabled');
                refreshingAccounts--;
                if (refreshingAccounts === 0) {
                    $('#btnRefreshAllData')
                        .prop('disabled', false)
                        .removeClass('disabled').find('i').removeClass('fa-spin');
                }
            }
        });
    });
    
    $('.force-meta-refresh').each(function (index) {
        $(this).click();
    }) ;
});

var refreshingAccounts = 0;

function hideAccessControl() {
    $(".trAccessControl").fadeOut();
}
function showAccessControl() {
    $(".trAccessControl").fadeIn();
}
