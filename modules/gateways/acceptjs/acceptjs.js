/*
 * WHMCS Authorize.net Accept.js Javascript
 *
 * @copyright Copyright (c) WHMCS Limited 2005-2019
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */
jQuery(document).ready(function(){
    var paymentMethod = jQuery('input[name="paymentmethod"]'),
        frm = jQuery('#frmCheckout'),
        newCcForm = jQuery('.frm-credit-card-input'),
        paymentForm = jQuery('#frmPayment'),
        adminCreditCard = jQuery('#frmCreditCardDetails');

    if (paymentMethod.length && !newCcForm.length) {
        var newOrExisting = jQuery('input[name="ccinfo"]'),
            selectedPaymentMethod = jQuery('input[name="paymentmethod"]:checked').val();

        if (selectedPaymentMethod === 'acceptjs') {
            enable_acceptjs();
            if (newOrExisting.val() === 'useexisting') {
                frm.off('submit', validateAcceptJs);
            }
        }

        paymentMethod.on('ifChecked change', function(){
            selectedPaymentMethod = jQuery(this).val();
            if (selectedPaymentMethod === 'acceptjs') {
                enable_acceptjs();
                if (newOrExisting.val() === 'useexisting') {
                    frm.off('submit', validateAcceptJs);
                }
            } else {
                disable_acceptjs();
            }
        });

        newOrExisting.on('ifChecked change', function() {
            frm.off('submit', validateAcceptJs);
            if (selectedPaymentMethod === 'acceptjs') {
                if (jQuery(this).val() !== 'useexisting') {
                    frm.on('submit', validateAcceptJs);
                }
            }
        });
    } else if (newCcForm.length) {
        if (jQuery('input[name="type"]:checked').data('gateway') === 'acceptjs') {
            enable_acceptjs_card_input();
        }
        jQuery('input[name="type"]').on('change', function(){
            if (jQuery(this).data('gateway') === 'acceptjs') {
                enable_acceptjs_card_input();
            } else {
                disable_acceptjs_card_input();
            }
        });
    } else if (paymentForm.length) {
        paymentForm.off('submit', validateCreditCardInput);
        if (jQuery('input[name="ccinfo"]:checked').val() === 'new') {
            enable_payment_acceptjs();
        } else {
            paymentForm.find('#inputCardCvv').parents('div.form-group').hide('fast');
        }
        jQuery('input[name="ccinfo"]').on('change', function(){
            if (jQuery(this).val() === 'new') {
                enable_payment_acceptjs();
            } else {
                paymentForm.find('#inputCardCvv').parents('div.form-group').hide('fast');
                paymentForm.off('submit', validatePaymentAcceptJs);
            }
        });
    } else if (adminCreditCard.length) {
        adminCreditCard.find('#cctype').removeAttr('name').parents('tr#rowCardType').hide('fast');
        adminCreditCard.find('#inputCardNumber').removeAttr('name');
        adminCreditCard.find('#inputCardExpiry').removeAttr('name');
        adminCreditCard.find('#cardcvv').removeAttr('name');

        // same as above - Firefox issues
        adminCreditCard.find('#btnSaveChanges').removeAttr('name');

        adminCreditCard.on('submit', validateAdminAcceptJs);
    }
});

function validateAcceptJs(event) {
    var paymentMethod = jQuery('input[name="paymentmethod"]:checked'),
        frm = jQuery('#frmCheckout'),
        newOrExisting = jQuery('input[name="ccinfo"]');
    if (
        paymentMethod.val() !== 'acceptjs'
        || (paymentMethod.val() === 'acceptjs' && newOrExisting.val() === 'useexisting')
    ) {
        return true;
    }
    event.preventDefault();
    // Disable the submit button to prevent repeated clicks:
    frm.find('#btnCompleteOrder').attr('disabled', 'disabled').addClass('disabled');

    var secureData = {},
        authData = {},
        cardData = {};

    // Extract the card number, expiration date, and card code.
    cardData.cardNumber = jQuery("#inputCardNumber").val().replace(/\s/g, '');
    var cardExpiry = jQuery('#inputCardExpiry').payment('cardExpiryVal');
    cardData.month = cardExpiry.month;
    cardData.year = cardExpiry.year;
    cardExpiry = null;
    cardData.cardCode = jQuery("#inputCardCVV").val();
    cardData.zip = jQuery('#inputPostcode').val();
    var firstName = jQuery('#inputFirstName');
    if (firstName.length && firstName.val()) {
        cardData.fullName = firstName.val() + ' ' + jQuery('#inputLastName').val();
    }
    secureData.cardData = cardData;

    authData.clientKey = clientKey;
    authData.apiLoginID = apiLoginId;
    secureData.authData = authData;

    // Pass the card number and expiration date to Accept.js for submission to Authorize.Net.
    Accept.dispatchData(secureData, acceptJsResponseHandler);

    // Prevent the form from being submitted:
    return false;
}

function acceptJsResponseHandler(response) {
    var frm = jQuery('#frmCheckout');
    if (response.messages.resultCode === "Error") {
        var errors = '';
        for (var i = 0; i < response.messages.message.length; i++) {
            errors += response.messages.message[i].text + "\n";
        }
        frm.find('.gateway-errors').text(errors).removeClass('hidden');
        scrollToError();
        frm.find('#btnCompleteOrder').removeAttr('disabled').removeClass('disabled');
    } else {
        frm.find('.gateway-errors').text('').addClass('hidden');
        // Insert the token ID into the form so it gets submitted to the server:
        frm.append(jQuery('<input type="hidden" name="dataDescriptor">').val(response.opaqueData.dataDescriptor));
        frm.append(jQuery('<input type="hidden" name="dataValue">').val(response.opaqueData.dataValue));

        // Submit the form:
        frm.off('submit', validateAcceptJs);
        frm.find('#btnCompleteOrder').removeAttr('disabled').removeClass('disabled')
            .click().addClass('disabled').attr('disabled', 'disabled');
    }
}

function enable_acceptjs() {
    var frm = jQuery('#frmCheckout'),
        cardTypeInput = frm.find('#cardType');

    frm.find('#cctype').removeAttr('name');
    frm.find('#inputCardNumber').removeAttr('name');
    frm.find('#inputCardExpiry').removeAttr('name');
    frm.find('#inputCardCVV').removeAttr('name');

    if (cardTypeInput.length) {
        frm.find('#cardType').parents('div.col-sm-6').slideUp('fast', function() {
            frm.find('#inputCardNumber').parents('div.col-sm-6').toggleClass('col-sm-6 col-sm-12');
        });
    } else {
        //legacy template
        frm.find('#cctype').parents('div.new-card-info').slideUp('fast');
        frm.find('#cctype').parents('tr.newccinfo').slideUp('fast');
    }

    frm.on('submit', validateAcceptJs);
}

function disable_acceptjs() {
    var frm = jQuery('#frmCheckout'),
        cardTypeInput = frm.find('#cardType');

    frm.find('#inputCardNumber').attr('name', 'ccnumber');
    frm.find('#inputCardExpiry').attr('name', 'ccexpirydate');
    frm.find('#inputCardCVV').attr('name', 'cccvv');
    frm.find('#cctype').attr('name', 'cctype');

    if (cardTypeInput.length) {
        frm.find('#inputCardNumber').parents('div.col-sm-12').toggleClass('col-sm-6 col-sm-12');
        frm.find('#cardType').parents('div.col-sm-6').slideDown('fast');
    } else {
        //legacy template
        frm.find('#cctype').parents('div.new-card-info').slideDown('fast');
        frm.find('#cctype').parents('tr.newccinfo').slideDown('fast');
    }

    frm.off('submit', validateAcceptJs);
}

function validateNewCcAcceptJs(event) {
    event.preventDefault();
    jQuery('#btnSubmitNewCard').attr('disabled', 'disabled').addClass('disabled');

    var secureData = {},
        authData = {},
        cardData = {};

    // Extract the card number, expiration date, and card code.
    cardData.cardNumber = jQuery("#inputCardNumber").val().replace(/\s/g, '');
    var cardExpiry = jQuery('#inputCardExpiry').payment('cardExpiryVal');
    cardData.month = cardExpiry.month;
    cardData.year = cardExpiry.year;
    cardExpiry = null;
    cardData.cardCode = jQuery("#inputCardCvc").val();

    secureData.cardData = cardData;

    authData.clientKey = clientKey;
    authData.apiLoginID = apiLoginId;
    secureData.authData = authData;

    Accept.dispatchData(secureData, acceptJsNewCcResponseHandler);
    return false;
}

function acceptJsNewCcResponseHandler(response) {
    var ccForm = jQuery('.frm-credit-card-input');
    if (response.messages.resultCode === "Error") { // Problem!
        var errors = '';
        for (var i = 0; i < response.messages.message.length; i++) {
            errors += response.messages.message[i].text + "\n";
        }
        // Show the errors on the form:
        ccForm.find('.gateway-errors,.assisted-cc-input-feedback').text(errors).removeClass('hidden');
        scrollToError();
        jQuery('#btnSubmitNewCard').removeAttr('disabled').removeClass('disabled'); // Re-enable submission

    } else { // Token was created!
        ccForm.find('.gateway-errors,.assisted-cc-input-feedback').text('').addClass('hidden');
        // Insert the token ID into the form so it gets submitted to the server:
        ccForm.append(jQuery('<input type="hidden" name="dataDescriptor">').val(response.opaqueData.dataDescriptor));
        ccForm.append(jQuery('<input type="hidden" name="dataValue">').val(response.opaqueData.dataValue));

        // Submit the form:
        ccForm.off('submit', validateNewCcAcceptJs);

        // Firefox will be unable to re-enable and click original submit button, so we will inject another one
        ccForm.append('<input type="submit" id="hiddenSubmit" name="submit" value="Save Changes" style="display: none">');

        jQuery('#hiddenSubmit').click();
    }
}

function enable_payment_acceptjs() {
    var paymentForm = jQuery('#frmPayment');
    paymentForm.find('#cctype').removeAttr('name').parents('div.form-group').remove();
    paymentForm.find('#inputCardNumber').removeAttr('name').payment('formatCardNumber');
    paymentForm.find('#inputCardExpiry').removeAttr('name');
    paymentForm.find('#inputCardExpiryYear').removeAttr('name');
    paymentForm.find('#inputCardCvv').removeAttr('name').payment('formatCardCVC')
        .parents('div.form-group').show('fast');
    paymentForm.on('submit', validatePaymentAcceptJs);
}

function validatePaymentAcceptJs(event) {
    event.preventDefault();
    jQuery('#btnSubmit').attr('disabled', 'disabled').addClass('disabled');
    var secureData = {},
        authData = {},
        cardData = {};

    // Extract the card number, expiration date, and card code.
    cardData.cardNumber = jQuery("#inputCardNumber").val().replace(/\s/g, '');
    cardData.month = jQuery('#inputCardExpiry').val();
    cardData.year = jQuery('#inputCardExpiryYear').val();
    cardData.cardCode = jQuery("#inputCardCvv").val();
    secureData.cardData = cardData;

    authData.clientKey = clientKey;
    authData.apiLoginID = apiLoginId;
    secureData.authData = authData;

    Accept.dispatchData(secureData, acceptJsPaymentResponseHandler);
    return false;
}

function acceptJsPaymentResponseHandler(response) {
    var paymentForm = jQuery('#frmPayment');
    if (response.messages.resultCode === "Error") { // Problem!
        var errors = '';
        for (var i = 0; i < response.messages.message.length; i++) {
            errors += response.messages.message[i].text + "\n";
        }
        // Show the errors on the form:
        paymentForm.find('.gateway-errors').text(errors).removeClass('hidden');
        scrollToError();
        jQuery('#btnSubmit').removeAttr('disabled').removeClass('disabled')
            .find('span').toggleClass('hidden'); // Re-enable submission

    } else { // Token was created!
        paymentForm.find('.gateway-errors').text('').addClass('hidden');
        // Insert the token ID into the form so it gets submitted to the server:
        paymentForm.append(jQuery('<input type="hidden" name="dataDescriptor">').val(response.opaqueData.dataDescriptor));
        paymentForm.append(jQuery('<input type="hidden" name="dataValue">').val(response.opaqueData.dataValue));

        // Submit the form:
        paymentForm.off('submit', validatePaymentAcceptJs);
        paymentForm.find('#btnSubmit').removeAttr('disabled').removeClass('disabled')
            .click().addClass('disabled').attr('disabled', 'disabled');
    }
}

function validateAdminAcceptJs(event) {
    var adminCreditCard = jQuery('#frmCreditCardDetails');
    event.preventDefault();
    adminCreditCard.find('#btnSaveChanges').attr('disabled', 'disabled').addClass('disabled');

    var secureData = {},
        authData = {},
        cardData = {};

    // Extract the card number, expiration date, and card code.
    cardData.cardNumber = jQuery("#inputCardNumber").val().replace(/\s/g, '');
    var cardExpiry = ccForm.find('#inputCardExpiry').payment('cardExpiryVal');
    cardData.month = cardExpiry.month;
    cardData.year = cardExpiry.year;
    cardExpiry = null;
    cardData.cardCode = jQuery("#cardcvv").val();
    secureData.cardData = cardData;

    authData.clientKey = clientKey;
    authData.apiLoginID = apiLoginId;
    secureData.authData = authData;

    Accept.dispatchData(secureData, acceptJsAdminResponseHandler);
    return false;
}

function acceptJsAdminResponseHandler(response) {
    var adminCreditCard = jQuery('#frmCreditCardDetails');
    if (response.messages.resultCode === "Error") { // Problem!
        var errors = '';
        for (var i = 0; i < response.messages.message.length; i++) {
            errors += response.messages.message[i].text + "\n";
        }
        // Show the errors on the form:
        adminCreditCard.find('.gateway-errors').text(errors).removeClass('hidden');
        scrollToError();
        adminCreditCard.find('#btnSaveChanges').removeAttr('disabled').removeClass('disabled'); // Re-enable submission
    } else {
        adminCreditCard.find('.gateway-errors').text('').addClass('hidden');
        // Insert the token ID into the form so it gets submitted to the server:
        adminCreditCard.append(jQuery('<input type="hidden" name="dataDescriptor">').val(response.opaqueData.dataDescriptor));
        adminCreditCard.append(jQuery('<input type="hidden" name="dataValue">').val(response.opaqueData.dataValue));


        adminCreditCard.off('submit', validateAdminAcceptJs);

        // Firefox will be unable to re-enable and click original submit button, so we will inject another one
        adminCreditCard.append('<input type="submit" id="hiddenSubmit" name="submit" value="Save Changes" style="display: none">');

        jQuery('#hiddenSubmit').click();
    }
}

function scrollToError() {
    jQuery('html, body').animate(
        {
            scrollTop: jQuery('.gateway-errors,.assisted-cc-input-feedback').offset().top - 50
        },
        500
    );
}

function enable_acceptjs_card_input() {
    var ccForm = jQuery('.frm-credit-card-input');
    ccForm.find('#inputCardNumber').removeAttr('name').payment('formatCardNumber');
    ccForm.find('#inputCardExpiry').removeAttr('name').payment('formatCardExpiry');
    ccForm.find('#inputCardCvc').removeAttr('name').payment('formatCardCVC');

    // get the original submit button out of the way as we need another name='submit' field to click
    // due to Firefox issues
    ccForm.find('button[type="submit"]').removeAttr('name');

    ccForm.on('submit', validateNewCcAcceptJs);
}

function disable_acceptjs_card_input() {
    var ccForm = jQuery('.frm-credit-card-input');
    ccForm.find('#inputCardNumber').attr('name', 'ccnumber').payment('formatCardNumber');
    ccForm.find('#inputCardExpiry').removeAttr('name', 'ccexpiry').payment('formatCardExpiry');
    ccForm.find('#inputCardCvc').removeAttr('name', 'cardcvv').payment('formatCardCVC');

    // get the original submit button out of the way as we need another name='submit' field to click
    // due to Firefox issues
    ccForm.find('button[type="submit"]').attr('name', 'submit');

    ccForm.off('submit', validateNewCcAcceptJs);
}
