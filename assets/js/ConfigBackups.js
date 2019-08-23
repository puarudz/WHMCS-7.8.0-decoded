jQuery(document).ready(function() {
    var backupsContainer = jQuery('.database-backups');

    backupsContainer.find('.activate').on('click', function() {
        var self = jQuery(this),
            form = self.parent('form'),
            type = self.data('type'),
            request = form.serialize();

        self.prop('disabled', true).addClass('disabled');

        request += '&action=save&activate=1&type=' + type + '&token=' + csrfToken;
        WHMCS.http.jqClient.post(
            window.location.href,
            request,
            function(data) {
                if (data.success === true) {
                    jQuery.growl.notice(
                        {
                            title: data.successMessageTitle,
                            message: data.successMessage
                        }
                    );
                    form.find('.save, .deactivate-start').removeClass('hidden');
                    self.addClass('hidden');
                    jQuery('#' + type + 'Label').toggleClass('label-default label-success').text(data.activeText);
                } else {
                    jQuery.growl.error(
                        {
                            title: data.errorMessageTitle,
                            message: data.errorMessage
                        }
                    );
                }
            },
            'json'
        ).always(function() {
            self.prop('disabled', false).removeClass('disabled');
        });
    });

    backupsContainer.find('.save').on('click', function() {
        var self = jQuery(this),
            form = self.parent('form'),
            type = self.data('type'),
            request = form.serialize();


        self.prop('disabled', true).addClass('disabled');

        request += '&action=save&type=' + type + '&token=' + csrfToken;
        WHMCS.http.jqClient.post(
            window.location.href,
            request,
            function(data) {
                if (data.success === true) {
                    jQuery.growl.notice(
                        {
                            title: data.successMessageTitle,
                            message: data.successMessage
                        }
                    );
                } else {
                    jQuery.growl.error(
                        {
                            title: data.errorMessageTitle,
                            message: data.errorMessage
                        }
                    );
                }
            },
            'json'
        ).always(function() {
            self.prop('disabled', false).removeClass('disabled');
        });
    });

    backupsContainer.find('.test').on('click', function() {
        var self = jQuery(this),
            form = self.parent('form'),
            type = self.data('type'),
            request = form.serialize();

        self.prop('disabled', true).addClass('disabled');
        jQuery('#' + type + 'Container').removeClass('hidden');
        request += '&action=test&type=' + type + '&token=' + csrfToken;
        jQuery('#' + type + 'Test').hide()
            .removeClass('hidden alert-success alert-danger')
            .addClass('alert-default')
            .find('.extra-text').addClass('hidden').text('').end()
            .find('.default-text').removeClass('hidden').end()
            .slideDown('fast');
        WHMCS.http.jqClient.post(
            window.location.href,
            request,
            function(data) {
                if (data.success === true) {
                    jQuery('#' + type + 'Test')
                        .addClass('alert-success')
                        .removeClass('alert-default alert-danger')
                        .find('.default-text').addClass('hidden').end()
                        .find('.extra-text').text(data.successMessage).removeClass('hidden').end()
                        .delay(3000).slideUp('slow');
                    form.find('.activate').prop('disabled', false).removeClass('disabled');
                } else {
                    jQuery('#' + type + 'Test')
                        .addClass('alert-danger')
                        .removeClass('alert-default alert-success')
                        .find('.default-text').addClass('hidden').end()
                        .find('.extra-text').text(data.errorMessageTitle + ': ' + data.errorMessage).removeClass('hidden').end()
                        .delay(3000).slideUp('slow');
                }
            },
            'json'
        ).always(function() {
            self.prop('disabled', false).removeClass('disabled');
            jQuery('#' + type + 'Container').addClass('hidden');
        });

    });

    backupsContainer.find('.deactivate-start').on('click', function() {
        var self = jQuery(this),
            form = self.parent('form'),
            type = self.data('type'),
            modal = jQuery('#modalConfirmDeactivate');


        jQuery('#confirmDeactivateYes').data('type', type);
        modal.modal('show');
    });

    jQuery('#modalConfirmDeactivate').find('.deactivate').on('click', function() {
        var self = jQuery(this),
            modal = jQuery('#modalConfirmDeactivate'),
            form = modal.parent('form'),
            type = self.data('type'),
            request = 'action=deactivate&type=' + type + '&token=' + csrfToken,
            mainForm = jQuery('.deactivate-start[data-type="' + type + '"]').parent('form');

        self.prop('disabled', true).addClass('disabled');

        WHMCS.http.jqClient.post(
            window.location.href,
            request,
            function(data) {
                if (data.success === true) {
                    jQuery.growl.notice(
                        {
                            title: data.successMessageTitle,
                            message: data.successMessage
                        }
                    );
                    mainForm.find('.save, .deactivate-start').addClass('hidden');
                    mainForm.find('.activate').removeClass('hidden').prop('disabled', true);
                    if (type === 'email') {
                        mainForm.find('.activate').prop('disabled', false);
                    }
                    jQuery('#' + type + 'Label').toggleClass('label-default label-success').text(data.inactiveText);
                } else {
                    jQuery.growl.error(
                        {
                            title: data.errorMessageTitle,
                            message: data.errorMessage
                        }
                    );
                }
            },
            'json'
        ).always(function() {
            self.prop('disabled', false).removeClass('disabled');
            modal.modal('hide');
        });
    });

    backupsContainer.find('#inputDestination').on('change', function() {
        var destinationData = jQuery('#destinationData'),
            value = jQuery(this).val();

        if (value !== 'homedir' && destinationData.hasClass('hidden')) {
            destinationData.hide().removeClass('hidden').slideDown('fast');
        } else if (value === 'homedir' && !(destinationData.hasClass('hidden'))) {
            destinationData.slideUp('fast').addClass('hidden');
        }
    });
});
