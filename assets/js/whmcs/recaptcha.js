/**
 * reCaptcha module
 *
 * @copyright Copyright (c) WHMCS Limited 2005-2018
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */
var recaptchaLoadComplete = false;

(function(module) {
    if (!WHMCS.hasModule('recaptcha')) {
        WHMCS.loadModule('recaptcha', module);
    }
})(
    function () {

        this.register = function () {
            if (recaptchaLoadComplete) {
                return;
            }
            var postLoad = [];
            var recaptchaForms = jQuery(".btn-recaptcha").parents('form');
            recaptchaForms.each(function (i, el){
                if (typeof recaptchaSiteKey === 'undefined') {
                    console.log('Recaptcha site key not defined');
                    return;
                }
                var frm = jQuery(el);
                var btnRecaptcha = frm.find(".btn-recaptcha");
                var isInvisible = btnRecaptcha.hasClass('btn-recaptcha-invisible'),
                    required = (typeof requiredText !== 'undefined') ? requiredText : 'Required';

                // if no recaptcha element, make one
                var recaptchaContent = frm.find("#divDynamicRecaptcha .g-recaptcha"),
                    recaptchaElement = frm.find('.recaptcha-container'),
                    appendElement = frm;

                if (recaptchaElement.length) {
                    appendElement = recaptchaElement;
                }
                if (!recaptchaContent.length) {
                    appendElement.append('<div id="divDynamicRecaptcha" class="g-recaptcha" data-toggle="tooltip" data-placement="bottom" data-trigger="manual" title="' + required + '"></div>');
                    recaptchaContent = appendElement.find("#divDynamicRecaptcha");
                }
                // propagate invisible recaptcha if necessary
                if (isInvisible) {
                    if (recaptchaContent.data('size') !== 'invisible') {
                        recaptchaContent.attr('data-size', 'invisible');
                    }
                } else {
                    recaptchaContent.hide()
                }

                // ensure site key is available to grecaptcha
                recaptchaContent.attr('data-sitekey', recaptchaSiteKey);


                // alter form to work around JS behavior on .submit() when there
                // there is an input with the name 'submit'
                var btnSubmit = frm.find("input[name='submit']");
                if (btnSubmit.length) {
                    var action = frm.prop('action');
                    frm.prop('action', action + '&submit=1');
                    btnSubmit.remove();
                }

                // make callback for grecaptcha to invoke after
                // injecting token & make it known via data-callback
                var funcName = 'recaptchaCallback' + i;
                window[funcName] = function () {
                    if (isInvisible) {
                        frm.submit();
                    }
                };
                recaptchaContent.attr('data-callback', funcName);

                // alter submit button to integrate invisible recaptcha
                // otherwise setup a callback to twiddle UI after grecaptcha
                // has inject DOM
                if (isInvisible) {
                    btnRecaptcha.on('click', function (event) {
                        if (!grecaptcha.getResponse().trim()) {
                            event.preventDefault();
                            grecaptcha.execute();
                        }
                    });
                } else {
                    postLoad.push(function () {
                        recaptchaContent.slideDown('fast', function() {
                            // just in case there's a delay in DOM; rare
                            recaptchaContent.find(':first').addClass('center-block');
                        });
                    });
                    postLoad.push(function() {
                        recaptchaContent.find(':first').addClass('center-block');
                    });
                }
            });

            // fetch/invoke the grecaptcha lib
            if (recaptchaForms.length) {
                var gUrl = "https://www.google.com/recaptcha/api.js";
                jQuery.getScript(gUrl, function () {
                    for(var i = postLoad.length -1; i >= 0 ; i--){
                        postLoad[i]();
                    }
                });
            }
            recaptchaLoadComplete = true;
        };

        return this;
    });
