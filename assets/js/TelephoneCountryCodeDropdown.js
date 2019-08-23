/**
 * WHMCS Telephone Country Code Dropdown
 *
 * Using https://github.com/jackocnr/intl-tel-input
 *
 * @copyright Copyright (c) WHMCS Limited 2005-2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

jQuery(document).ready(function() {
    if (jQuery('body').data('phone-cc-input')) {
        var phoneInput = jQuery('input[name^="phone"], input[name$="phone"]').not('input[type="hidden"]');
        if (phoneInput.length) {
            var countryInput = jQuery('[name^="country"], [name$="country"]'),
                initialCountry = 'us';
            if (countryInput.length) {
                initialCountry = countryInput.val().toLowerCase();
                if (initialCountry === 'um') {
                    initialCountry = 'us';
                }
            }

            phoneInput.each(function(){
                var thisInput = jQuery(this),
                    inputName = thisInput.attr('name');
                jQuery(this).before(
                    '<input id="populatedCountryCode' + inputName + '" type="hidden" name="country-calling-code-' + inputName + '" value="" />'
                );
                thisInput.intlTelInput({
                    preferredCountries: [initialCountry, "us", "gb"].filter(function(value, index, self) {
                        return self.indexOf(value) === index;
                    }),
                    initialCountry: initialCountry,
                    autoPlaceholder: 'polite', //always show the helper placeholder
                    separateDialCode: true
                });

                thisInput.on('countrychange', function (e, countryData) {
                    jQuery('#populatedCountryCode' + inputName).val(countryData.dialCode);
                    if (jQuery(this).val() === '+' + countryData.dialCode) {
                        jQuery(this).val('');
                    }
                });
                thisInput.on('blur keydown', function (e) {
                    if (e.type === 'blur' || (e.type === 'keydown' && e.keyCode === 13)) {
                        var number = jQuery(this).intlTelInput("getNumber"),
                            countryData = jQuery(this).intlTelInput("getSelectedCountryData"),
                            countryPrefix = '+' + countryData.dialCode;

                        if (number.indexOf(countryPrefix) === 0 && (number.match(/\+/g) || []).length > 1) {
                            number = number.substr(countryPrefix.length);
                        }
                        jQuery(this).intlTelInput("setNumber", number);
                    }
                });
                jQuery('#populatedCountryCode' + inputName).val(thisInput.intlTelInput('getSelectedCountryData').dialCode);

                countryInput.on('change', function() {
                    if (thisInput.val() === '') {
                        var country = jQuery(this).val().toLowerCase();
                        if (country === 'um') {
                            country = 'us';
                        }
                        phoneInput.intlTelInput('setCountry', country);
                    }
                });

                // this must be .attr (not .data) in order for it to be found by [data-initial-value] selector
                thisInput.attr('data-initial-value', $(thisInput).val());

                thisInput.parents('form').find('input[type=reset]').each(function() {
                    var resetButton = this;
                    var form = $(resetButton).parents('form');

                    if (!$(resetButton).data('phone-handler')) {
                        $(resetButton).data('phone-handler', true);

                        $(resetButton).click(function(e) {
                            e.stopPropagation();

                            $(form).trigger('reset');

                            $(form).find('input[data-initial-value]').each(function() {
                                var inputToReset = this;

                                $(inputToReset).val(
                                    $(inputToReset).attr('data-initial-value')
                                );
                            });

                            return false;
                        });
                    }
                });
            });

            /**
             * In places where a form icon is present, hide it.
             * Where the input has a class of field, remove that and add form-control in place.
             */
            phoneInput.parents('div.form-group').find('.field-icon').addClass('hidden').end();
            phoneInput.removeClass('field').addClass('form-control');
        }

        var registrarPhoneInput = jQuery('input[name$="][Phone Number]"], input[name$="][Phone]"]').not('input[type="hidden"]');
        if (registrarPhoneInput.length) {
            jQuery.each(registrarPhoneInput, function(index, input) {
                var thisInput = jQuery(this),
                    inputName = thisInput.attr('name');
                inputName = inputName.replace('contactdetails[', '').replace('][Phone Number]', '').replace('][Phone]', '');

                var countryInput = jQuery('[name$="' + inputName + '][Country]"]'),
                    initialCountry = countryInput.val().toLowerCase();
                if (initialCountry === 'um') {
                    initialCountry = 'us';
                }

                thisInput.before('<input id="populated' + inputName + 'CountryCode" type="hidden" name="contactdetails[' + inputName + '][Phone Country Code]" value="" />');
                thisInput.intlTelInput({
                    preferredCountries: [initialCountry, "us", "gb"].filter(function(value, index, self) {
                        return self.indexOf(value) === index;
                    }),
                    initialCountry: initialCountry,
                    autoPlaceholder: 'polite', //always show the helper placeholder
                    separateDialCode: true
                });

                thisInput.on('countrychange', function (e, countryData) {
                    jQuery('#populated' + inputName + 'CountryCode').val(countryData.dialCode);
                    if (jQuery(this).val() === '+' + countryData.dialCode) {
                        jQuery(this).val('');
                    }
                });
                thisInput.on('blur keydown', function (e) {
                    if (e.type === 'blur' || (e.type === 'keydown' && e.keyCode === 13)) {
                        var number = jQuery(this).intlTelInput("getNumber"),
                            countryData = jQuery(this).intlTelInput("getSelectedCountryData"),
                            countryPrefix = '+' + countryData.dialCode;

                        if (number.indexOf(countryPrefix) === 0 && (number.match(/\+/g) || []).length > 1) {
                            number = number.substr(countryPrefix.length);
                        }
                        jQuery(this).intlTelInput("setNumber", number);
                    }
                });
                jQuery('#populated' + inputName + 'CountryCode').val(thisInput.intlTelInput('getSelectedCountryData').dialCode);

                countryInput.on('blur', function() {
                    if (thisInput.val() === '') {
                        var country = jQuery(this).val().toLowerCase();
                        if (country === 'um') {
                            country = 'us';
                        }
                        thisInput.intlTelInput('setCountry', country);
                    }
                });

            });
        }
    }
});
