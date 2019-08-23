jQuery(document).ready(function() {
    jQuery('[data-toggle="tooltip"]').tooltip();
    jQuery('[data-toggle="popover"]').popover();
    jQuery('.inline-editable').editable({
        mode: 'inline',
        params: function(params) {
            params.action = 'savefield';
            params.token = csrfToken;
            return params;
        }
    });
    jQuery('.slide-toggle').bootstrapSwitch();
    jQuery('select.form-control.enhanced').select2({
        theme: 'bootstrap'
    });

    jQuery('body').on('click', '.copy-to-clipboard', WHMCS.ui.clipboard.copy);

    jQuery(".credit-card-type li a").click(function() {
        jQuery("#selectedCard").html(jQuery(this).html());
        jQuery("#cctype").val(jQuery('span.type', this).html());
    });

    jQuery('.paging-dropdown li a,.page-selector').click(function() {
        if (jQuery(this).hasClass('disabled')) {
            return false;
        }
        var form = jQuery('#frmRecordsFound');
        jQuery("#currentPage").html(jQuery(this).data('page'));
        form.find('input[name="page"]')
            .val(jQuery(this).data('page')).end();
        form.submit();
        return false;
    });

    jQuery(".no-results a").click(function(e) {
        e.preventDefault();
        jQuery('#checkboxShowHidden').bootstrapSwitch('state', false);
    });

    jQuery('body').on('click', 'a.autoLinked', function (e) {
        e.preventDefault();

        var child = window.open();
        child.opener = null;
        child.location = $(this).attr('href');
    });

    jQuery('#tblModuleSettings').on('click', '.icon-refresh', function() {
        fetchModuleSettings(jQuery(this).data('product-id'), 'simple');
    });

    jQuery('#mode-switch').click(function() {
        fetchModuleSettings(jQuery(this).data('product-id'), jQuery(this).attr('data-mode'));
    });

    $('body').on('click', '.modal-wizard .modal-submit', function() {
        var modal = $('#modalAjax');
        modal.find('.loader').show();
        modal.find('.modal-submit').prop('disabled', true);

        $('.modal-wizard .wizard-step:hidden :input').attr('disabled', true);

        var form = document.forms.namedItem('frmWizardContent'),
            oData = new FormData(form),
            currentStep = $('.modal-wizard .wizard-step:visible').data('step-number'),
            ccGatewayFormSubmitted = $('#ccGatewayFormSubmitted').val(),
            enomFormSubmitted = $('#enomFormSubmitted').val(),
            oReq = new XMLHttpRequest();

        if ((ccGatewayFormSubmitted && currentStep == 3) || (enomFormSubmitted && currentStep == 5)) {
            wizardStepTransition(false, true);
            fadeoutLoaderAndAllowSubmission(modal);
        } else {

            oReq.open('POST', $('#frmWizardContent').attr('action'), true);

            oReq.send(oData);
            oReq.onload = function () {
                if (oReq.status == 200) {
                    try {
                        var data = JSON.parse(oReq.responseText),
                            doNotShow = $('#btnWizardDoNotShow');
                        if (doNotShow.is(':visible')) {
                            doNotShow.fadeOut('slow', function () {
                                $('#btnWizardSkip').hide().removeClass('hidden').fadeIn('slow');
                            });
                        }

                        if (data.success) {
                            if (data.approveremails) {
                                for (i = 0; i < data.approveremails.length; i++) {
                                    var email = data.approveremails[i];
                                    $('.modal-wizard .cert-approver-emails').append('<label class="radio-inline"><input type="radio" name="approver_email" value="' + email + '"> ' + email + '</label><br>');
                                }
                            } else if (data.fileAuth) {
                                $('.modal-wizard .cert-further-instructions').hide();
                                $('.modal-wizard .cert-file-auth').removeClass('hidden');
                                $('.modal-wizard .cert-file-auth-filename').val(data.fileAuthFilename);
                                $('.modal-wizard .cert-file-auth-contents').val(data.fileAuthContents);
                            } else if (data.refreshMc) {
                                $('#btnMcServiceRefresh').click();
                            }
                            wizardStepTransition(data.skipNextStep, false);
                        } else {
                            wizardError(data.error);
                        }
                    } catch (err) {
                        wizardError('An error occurred while communicating with the server. Please try again.');
                    } finally {
                        fadeoutLoaderAndAllowSubmission(modal);
                    }
                } else {
                    alert('An error occurred while communicating with the server. Please try again.');
                    modal.find('.loader').fadeOut();
                }
            };
        }
    }).on('click', '#btnWizardSkip', function(e) {
        e.preventDefault();
        var currentStep = $('#inputWizardStep').val(),
            skipTwo = false;

        if (currentStep === '2' || currentStep === '4') {
            skipTwo = true;
        }
        wizardStepTransition(skipTwo, true);
    }).on('click', '#btnWizardBack', function(e) {
        e.preventDefault();
        wizardStepBackTransition();
    }).on('click', '#btnWizardDoNotShow', function(e) {
        e.preventDefault();
        WHMCS.http.jqClient.post('wizard.php', 'dismiss=true', function() {
            //Success or no, still hide now
            $('#modalAjax').modal('hide');
        });
    });

    $('#modalAjax').on('hidden.bs.modal', function (e) {
        if ($('#modalAjax').hasClass('modal-wizard')) {
            $('#btnWizardSkip').remove();
            $('#btnWizardBack').remove();
            $('#btnWizardDoNotShow').remove();
        }
    });

    $('#prodsall').click(function () {
        var checkboxes = $('.checkprods');
        checkboxes.filter(':visible').prop('checked', $(this).prop('checked')).end();
        if ($(this).prop('checked')) {
            checkboxes.filter(':hidden').prop('checked', !$(this).prop('checked')).end();
        }
    });
    $('#addonsall').click(function () {
        var checkboxes = $('.checkaddons');
        checkboxes.filter(':visible').prop('checked', $(this).prop('checked')).end();
        if ($(this).prop('checked')) {
            checkboxes.filter(':hidden').prop('checked', !$(this).prop('checked')).end();
        }
    });
    $('#domainsall').click(function () {
        var checkboxes = $('.checkdomains');
        checkboxes.filter(':visible').prop('checked', $(this).prop('checked')).end();
        if ($(this).prop('checked')) {
            checkboxes.filter(':hidden').prop('checked', !$(this).prop('checked')).end();
        }
    });

    jQuery('#addPayment').submit(function (e) {
        e.preventDefault();
        addingPayment = false;
        jQuery('#btnAddPayment').attr('disabled', 'disabled');
        jQuery('#paymentText').hide();
        jQuery('#paymentLoading').removeClass('hidden').show();

        var postData = jQuery(this).serialize().replace('action=edit', 'action=checkTransactionId'),
            post = WHMCS.http.jqClient.post(
            'invoices.php',
            postData + '&ajax=1'
        );

        post.done(function (data) {
            if (data.unique == false) {
                jQuery('#modalDuplicateTransaction').modal('show');
            } else {
                addInvoicePayment();
            }
        });
    });

    $('#modalDuplicateTransaction').on('hidden.bs.modal', function () {
        if (addingPayment === false) {
            jQuery('#paymentLoading').hide('fast', function() {
                jQuery('#paymentText').show('fast');
                jQuery('#btnAddPayment').removeAttr('disabled');
            });
        }
    });

    jQuery(document).on('click', '.feature-highlights-content .btn-action-1, .feature-highlights-content .btn-action-2', function() {
        var linkId = jQuery(this).data('link'),
            linkTitle = jQuery(this).data('link-title');

        WHMCS.http.jqClient.post(
            'whatsnew.php',
            {
                action: "link-click",
                linkId: linkId,
                linkTitle: linkTitle,
                token: csrfToken
            }
        );
    });

    /**
     * Admin Tagging
     */
    if (typeof mentionsFormat !== "undefined") {
        jQuery('#replynote[name="message"],#note[name="note"]').atwho({
            at: "@",
            displayTpl: "<li class=\"mention-list\">${gravatar} ${username} - ${name} (${email})</li>",
            insertTpl: mentionsFormat,
            data: WHMCS.adminUtils.getAdminRouteUrl('/mentions'),
            limit: 5
        });
    }

    jQuery('.search-bar .search-icon').click(function(e) {
        jQuery('.search-bar').find('input:first').focus();
    });
    jQuery('.btn-search-advanced').click(function(e) {
        jQuery(this).closest('.search-bar').find('.advanced-search-options').slideToggle('fast');
    });

    // DataTable data-driven auto object registration
    WHMCS.ui.dataTable.register();

    // Bootstrap Confirmation popup auto object registration
    WHMCS.ui.confirmation.register();

    var mcProductPromos = jQuery("#mcConfigureProductPromos");

    if (mcProductPromos.length) {
        var itemCount = mcProductPromos.find('.item').length;
        mcProductPromos.owlCarousel({
            loop: true,
            margin: 10,
            responsiveClass: true,
            responsive: {
                0: {
                    items: 1
                },
                850: {
                    items: (itemCount < 2 ? itemCount : 2)
                },
                1250: {
                    items: (itemCount < 3 ? itemCount : 3)
                },
                1650: {
                    items: (itemCount < 4 ? itemCount : 4)
                }
            }
        });

        jQuery('#dismissPromos').on('click', function() {
            mcProductPromos.slideUp('fast');
            jQuery(this).hide();
            WHMCS.http.jqClient.post(
                WHMCS.adminUtils.getAdminRouteUrl('/dismiss-marketconnect-promo'),
                {
                    token: csrfToken
                },
                function (data) {
                    //do nothing
                }
            );
        });
    }

    jQuery(document).on('submit', '#frmCreditCardDeleteDetails', function(e) {
        e.preventDefault();
        jQuery('#modalAjax .modal-submit').prop("disabled", true);
        jQuery('#modalAjax .loader').show();
        WHMCS.http.jqClient.post(
            jQuery(this).attr('action'),
            jQuery(this).serialize(),
            function(data) {
                updateAjaxModal(data);
            },
            'json'
        ).fail(function() {
            jQuery('#modalAjax .modal-body').html('An error occurred while communicating with the server. Please try again.');
            jQuery('#modalAjax .loader').fadeOut();
        });
    });

    if (jQuery('.captcha-type').length) {
        jQuery(document).on('change', '.captcha-type', function() {
            var settings = jQuery('.recaptchasetts');
            if (jQuery(this).val() === '') {
                settings.hide();
            } else {
                settings.show();
            }
        });
    }

    if (jQuery('#frmClientSearch').length) {
        jQuery(document).on('change', '.status', function() {
            jQuery('#status').val(jQuery(this).val());
        });
    }

    jQuery('.ssl-state.ssl-sync').each(function () {
        var self = jQuery(this);
        WHMCS.http.jqClient.post(
            WHMCS.adminUtils.getAdminRouteUrl('/domains/ssl-check'),
            {
                'domain': self.data('domain'),
                'userid': self.data('user-id'),
                'token': csrfToken
            },
            function (data) {
                self.replaceWith('<img src="' + data.image + '" data-toggle="tooltip" title="' + data.tooltip + '" class="' + data.class + '">');
                jQuery('[data-toggle="tooltip"]').tooltip();
            }
        );
    });

});
var addingPayment = false;

function updateServerGroups(requiredModule) {
    var optionServerTypes = '';
    var doShowOption = false;

    $('#inputServerGroup').find('option:not([value=0])').each(function() {
        optionServerTypes = $(this).attr('data-server-types');

        if (requiredModule) {
            doShowOption = (optionServerTypes.indexOf(',' + requiredModule + ',') > -1);
        } else {
            doShowOption = true;
        }

        if (doShowOption) {
            $(this).attr('disabled', false);
        } else {
            $(this).attr('disabled', true);

            if ($(this).is(':selected')) {
                $('#inputServerGroup').val('0');
            }
        }
    });
}

function fetchModuleSettings(productId, mode) {
    var gotValidResponse = false;
    var dataResponse = '';
    var switchLink = $('#mode-switch');
    var module = $('#inputModule').val();

    if (module === "") {
        $('#tblModuleSettings').find('tr').not(':first').remove();
        $('#noModuleSelectedRow').removeClass('hidden');
        $('#tblModuleAutomationSettings').find('input[type=radio]').attr('disabled', true);
        return;
    }

    mode = mode || 'simple';
    if (mode != 'simple' && mode != 'advanced') {
        mode = 'simple';
    }
    requestedMode = mode;
    $('#tblModuleSettings').addClass('module-settings-loading');
    $('#tblModuleAutomationSettings').addClass('module-settings-loading');
    $('#serverReturnedError').addClass('hidden');
    $('#moduleSettingsLoader').show();
    switchLink.attr('data-product-id', productId);
    WHMCS.http.jqClient.post(window.location.pathname, {
        'action': 'module-settings',
        'module': module,
        'servergroup': $('#inputServerGroup').val(),
        'id': productId,
        'mode': mode
    },
    function(data) {
        gotValidResponse = true;
        $('#tblModuleSettings').removeClass('module-settings-loading');
        $('#tblModuleAutomationSettings').removeClass('module-settings-loading');
        $('#tblModuleSettings tr').not(':first').remove();
        switchLink.addClass('hidden');
        if (module && data.error) {
            $('#serverReturnedErrorText').html(data.error);
            $('#serverReturnedError').removeClass('hidden');
        }
        if (module && data.content) {
            $('#noModuleSelectedRow').addClass('hidden');
            $('#tblModuleSettings').append(data.content);
            $('#tblModuleAutomationSettings').find('input[type=radio]').removeAttr('disabled');
            if (data.mode == 'simple') {
                switchLink.attr('data-mode', 'advanced').find('a').find('span').addClass('hidden').parent().find('.text-advanced').removeClass('hidden');
                switchLink.removeClass('hidden');
            } else {
                if (data.mode == 'advanced' && requestedMode == 'advanced') {
                    switchLink.attr('data-mode', 'simple').find('a').find('span').addClass('hidden').parent().find('.text-simple').removeClass('hidden');
                    switchLink.removeClass('hidden');
                } else {
                    switchLink.addClass('hidden');
                }
            }
        } else {
            $('#noModuleSelectedRow').removeClass('hidden');
            $('#tblModuleAutomationSettings').find('input[type=radio]').attr('disabled', true);
        }
        $('#moduleSettingsLoader').fadeOut();
        jQuery('[data-toggle="tooltip"]').tooltip();
    }, "json")
    .always(function() {
        updateServerGroups(gotValidResponse ? module : '');

        if (!gotValidResponse) {
            // non json response, likely session expired
        }
    });
    return dataResponse;
}

function wizardCall(action, request, handler) {
    var requestString = 'wizard=' + $('input[name="wizard"]').val()
        + '&step=' + $('input[name="step"]').val()
        + '&token=' + $('input[name="token"]').val()
        + '&action=' + action
        + '&' + request;

    WHMCS.http.jqClient.post('wizard.php', requestString, handler);
}

function wizardError(errorMsg) {
    $('.modal-wizard .wizard-content').css('overflow', 'hidden');

    WHMCS.ui.effects.errorShake($('.info-alert:visible:first').html(errorMsg).addClass('alert-danger'));

}

function wizardStepTransition(skipNextStep, skip) {
    var currentStepNumber = $('.modal-wizard .wizard-step:visible').data('step-number');
    if (skipNextStep) {
        increment = 2;
    } else {
        increment = 1;
    }
    var lastStep = $('.modal-wizard .wizard-step:visible');
    var nextStepNumber = currentStepNumber + increment;
    if ($('#wizardStep' + nextStepNumber).length) {
        $('#wizardStep' + currentStepNumber).fadeOut('', function() {
            var newClass = 'completed';
            if (skip) {
                newClass = 'skipped';
                $('#wizardStepLabel' + currentStepNumber + ' i').removeClass('fa-check-circle').addClass('fa-minus-circle');
            } else {
                lastStep.find('.signup-frm').hide();
                lastStep.find('.signup-frm-success').removeClass('hidden');

                if (currentStepNumber == 3) {
                    lastStep.find('.signup-frm-success')
                        .append('<input type="hidden" id="ccGatewayFormSubmitted" name="ccGatewayFormSubmitted" value="1" />');
                } else if (currentStepNumber == 5) {
                    lastStep.find('.signup-frm-success')
                        .append('<input type="hidden" id="enomFormSubmitted" name="enomFormSubmitted" value="1" />');
                }

            }

            if (nextStepNumber > 0) {
                // Show the BACK button.
                if (!$('#btnWizardBack').is(':visible')) {
                    $('#btnWizardBack').hide().removeClass('hidden').fadeIn('slow');
                }
            } else {
                $('#btnWizardBack').fadeOut('slow');
                $('#btnWizardDoNotShow').fadeIn('slow');
                $('#btnWizardSkip').fadeOut('slow');
            }
            $('#wizardStepLabel' + currentStepNumber).removeClass('current').addClass(newClass);
            $('.modal-wizard .wizard-step:visible :input').attr('disabled', true);
            $('#wizardStep' + nextStepNumber + ' :input').removeAttr('disabled');
            $('#wizardStep' + nextStepNumber).fadeIn();
            $('#inputWizardStep').val(nextStepNumber);
            $('#wizardStepLabel' + nextStepNumber).addClass('current');
        });
        if (!$('#wizardStep' + (nextStepNumber + 1)).length) {
            $('#btnWizardSkip').fadeOut('slow');
            $('#btnWizardBack').fadeOut('slow');
            $('.modal-submit').html('Finish');
        }
    } else {
        // end of steps
        $('#modalAjax').modal('hide');
    }
}

function wizardStepBackTransition() {
    var currentStepNumber = $('.modal-wizard .wizard-step:visible').data('step-number');
    var previousStepNumber = parseInt(currentStepNumber) - 1;

    $('#wizardStep' + currentStepNumber).fadeOut('', function() {
        if (previousStepNumber < 1) {
            $('#btnWizardBack').fadeOut('slow');
            $('#btnWizardDoNotShow').fadeIn('slow');
            $('#btnWizardSkip').addClass('hidden');
        }

        $('.modal-wizard .wizard-step:visible :input').attr('disabled', true);
        $('#wizardStep' + previousStepNumber + ' :input').removeAttr('disabled');
        $('#wizardStep' + previousStepNumber).fadeIn();
        $('#inputWizardStep').val(previousStepNumber);
        $('#wizardStepLabel' + previousStepNumber).addClass('current');
        $('#wizardStepLabel' + currentStepNumber).removeClass('current');
    });
}

function fadeoutLoaderAndAllowSubmission(modal) {
    modal.find('.loader').fadeOut();
    modal.find('.modal-submit').removeProp('disabled');
}

function openSetupWizard() {
    $('#modalFooterLeft').html('<a href="#" id="btnWizardSkip" class="btn btn-link pull-left hidden">Skip Step</a>' +
        '<a href="#" id="btnWizardDoNotShow" class="btn btn-link pull-left">Do not show this again</a>' +
        '</div>');
    $('#modalAjaxSubmit').before('<a href="#" id="btnWizardBack" class="btn btn-default hidden">Back</a>');
    openModal('wizard.php?wizard=GettingStarted', '', 'Getting Started Wizard', 'modal-lg', 'modal-wizard modal-setup-wizard', 'Next', '', true);
}

function addInvoicePayment() {
    addingPayment = true;
    jQuery('#modalDuplicateTransaction').modal('hide');
    WHMCS.http.jqClient.post(
        'invoices.php',
        jQuery('#addPayment').serialize() + '&ajax=1',
        function (data) {
            if (data.redirectUri) {
                window.location = data.redirectUri;
            }
        }
    );
}

function cancelAddPayment() {
    jQuery('#paymentLoading').fadeOut('fast', function() {
        jQuery('#paymentText').fadeIn('fast');
        jQuery('#btnAddPayment').removeAttr('disabled');
    });
    jQuery('#modalDuplicateTransaction').modal('hide');
}

function openFeatureHighlights() {
    openModal('whatsnew.php?modal=1', '', 'What\'s new in Version ...', '', 'modal-feature-highlights', '', '', true);
}
