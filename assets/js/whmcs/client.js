/**
 * WHMCS client module
 *
 * @copyright Copyright (c) WHMCS Limited 2005-2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */
(function(module) {
    if (!WHMCS.hasModule('client')) {
        WHMCS.loadModule('client', module);
    }
})({
registration: function () {
    this.prefillPassword = function (params) {
        params = params || {};
        if (typeof params.hideContainer === 'undefined') {
            var id = (jQuery('#inputSecurityQId').attr('id')) ? '#containerPassword' : '#containerNewUserSecurity';
            params.hideContainer = jQuery(id);
            params.hideInputs = true;
        } else if (typeof params.hideContainer === 'string' && params.hideContainer.length) {
            params.hideContainer = jQuery(params.hideContainer);
        }

        if (typeof params.form === 'undefined') {
            params.form = {
                password: [
                    {id: 'inputNewPassword1'},
                    {id: 'inputNewPassword2'}
                ]
            };
        }

        var prefillFunc = function () {
            var $randomPasswd = WHMCS.utils.simpleRNG();
            for (var i = 0, len = params.form.password.length; i < len; i++) {
                jQuery('#' + params.form.password[i].id)
                    .val($randomPasswd).trigger('keyup');
            }
        };

        if (params.hideInputs) {
            params.hideContainer.slideUp('fast', prefillFunc);
        } else {
            prefillFunc();
        }
    };

    return this;
}});
