/*!
 * WHMCS Admin Login Javascript Library
 * Copyright 2016 WHMCS Limited
 */

function verticalCenter() {
    var middlePos = jQuery(window).height() - jQuery(".login-container").outerHeight() - 40;
    if (middlePos > 0) {
        jQuery('.login-container').css({
            "margin-top": Math.ceil(middlePos / 2)
        });
    }
}

jQuery(document).ready(function() {
    verticalCenter();
    jQuery(window).resize(function() {
        verticalCenter();
    });

    jQuery(".language-chooser li a").click(function() {
        jQuery("#languageName").html(jQuery(this).html());
        jQuery("#inputLanguage").val(jQuery(this).html());
    });

    var submit = false;

    jQuery('#frmPasswordChange').on('submit', function(e) {
        if (submit) {
            return true;
        }
        e.preventDefault();
        var password = jQuery('#password'),
            confirmPassword = jQuery('#passwordConfirm'),
            button = jQuery(this);
        button.attr('disabled', 'disabled').addClass('disabled');
        if (!password.val()) {
            confirmPassword.tooltip('hide');
            password.attr('data-original-title', 'Required');
            password.tooltip('fixTitle').tooltip('show');
            password.focus();
        } else if (!confirmPassword.val()) {
            password.tooltip('hide');
            confirmPassword.attr('data-original-title', 'Required');
            confirmPassword.tooltip('fixTitle').tooltip('show');
            confirmPassword.focus();
        } else if (password.val() != confirmPassword.val()) {
            password.tooltip('hide');
            confirmPassword.attr('data-original-title', 'Passwords must match');
            confirmPassword.tooltip('fixTitle').tooltip('show');
            confirmPassword.focus();
        } else {
            password.tooltip('hide');
            confirmPassword.tooltip('hide');
            submit = true;
            button.trigger('submit');
        }
        button.removeClass('disabled').removeAttr('disabled');
        return false;
    });
    WHMCS.recaptcha.register();
});
