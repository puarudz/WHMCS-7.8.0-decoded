/*
 * WHMCS Stripe Javascript
 *
 * @copyright Copyright (c) WHMCS Limited 2005-2019
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */
var elementsDiv = null,
    modalInput = false;
function initStripe() {
    var paymentMethod = jQuery('input[name="paymentmethod"]'),
        frm = jQuery('#frmCheckout'),
        newCcForm = jQuery('.frm-credit-card-input'),
        paymentForm = jQuery('#frmPayment'),
        adminCreditCard = jQuery('#frmCreditCardDetails');
    if (paymentMethod.length && !newCcForm.length) {
        var newCcInputs = jQuery('#newCardInfo');

        insertAndMountElementsDivAfterInput(newCcInputs);
        elementsDiv = jQuery('#stripeElements');

        var newOrExisting = jQuery('input[name="ccinfo"]'),
            selectedCard = jQuery('input[name="ccinfo"]:checked'),
            selectedPaymentMethod = jQuery('input[name="paymentmethod"]:checked').val(),
            existingCvv = jQuery('#existingCardInfo');

        if (typeof selectedPaymentMethod == 'undefined') {
            selectedPaymentMethod = jQuery('input[name="paymentmethod"]').val();
        }

        enablePaymentRequestButton();

        if (selectedPaymentMethod === 'stripe') {
            hide_cc_fields();
            enable_stripe();
            if (selectedCard.val() !== 'new') {
                get_existing_token(selectedCard.val());
                elementsDiv.hide();
                frm.off('submit', validateStripe);
                frm.off('submit', validateChangeCard);
                existingCvv.hide();
                frm.on('submit', processExisting);
            }
        }

        paymentMethod.on('ifChecked', function(){
            selectedPaymentMethod = jQuery(this).val();
            if (selectedPaymentMethod === 'stripe') {
                var newOrExistingValue = jQuery('input[name="ccinfo"]:checked').val();
                hide_cc_fields();
                enable_stripe();
                if (newOrExistingValue !== 'new') {
                    get_existing_token(newOrExistingValue);
                    elementsDiv.hide();
                    frm.off('submit', validateStripe);
                    frm.off('submit', validateChangeCard);
                    frm.on('submit', processExisting);
                } else {
                    elementsDiv.show();
                    frm.off('submit', processExisting);
                    if (amount === '000') {
                        frm.off('submit', validateStripe);
                        frm.on('submit', validateChangeCard);
                    } else {
                        frm.on('submit', validateStripe);
                        frm.off('submit', validateChangeCard);
                    }
                }
            } else {
                disable_stripe();
            }
        });
        newOrExisting.on('ifChecked', function() {
            frm.off('submit', validateStripe);
            selectedPaymentMethod = jQuery('input[name="paymentmethod"]:checked').val();
            if (selectedPaymentMethod !== 'stripe') {
                return;
            }
            hide_cc_fields();
            if (jQuery(this).val() === 'new') {
                enable_stripe();
            } else {
                get_existing_token(jQuery(this).val());
                elementsDiv.hide();
            }
        });
    } else if (newCcForm.length) {
        if (jQuery('input[name="type"]:checked').data('gateway') === 'stripe') {
            insertAndMountElementsDivBeforeInput(
                newCcForm.find('div.cc-details')
            );
            elementsDiv = jQuery('#stripeElements');
            hide_cc_fields();
            elementsDiv.hide().removeClass('hidden').show();

            card.addEventListener('change', cardListener);
            cardExpiryElements.addEventListener('change', cardListener);
            cardCvcElements.addEventListener('change', cardListener);
            newCcForm.on('submit', addNewCardClientSide);
        }
        jQuery('input[name="type"]').on('ifChecked', function(){
            if (jQuery(this).data('gateway') === 'stripe') {
                insertAndMountElementsDivBeforeInput(
                    newCcForm.find('div.cc-details')
                );
                elementsDiv = jQuery('#stripeElements');
                hide_cc_fields();
                elementsDiv.hide().removeClass('hidden').show();

                newCcForm.off('submit', addNewCardClientSide);
                newCcForm.on('submit', addNewCardClientSide);
                card.addEventListener('change', cardListener);
                cardExpiryElements.addEventListener('change', cardListener);
                cardCvcElements.addEventListener('change', cardListener);
            } else {
                disable_stripe();
                newCcForm.find('.cc-details').show();
            }
        });
    } else if (paymentForm.length) {
        insertAndMountElementsDivBeforeInput(paymentForm.find('#billingAddressChoice'));
        paymentForm.find('#inputCardCvv').closest('div.form-group').remove();
        paymentForm.off('submit', validateCreditCardInput);
        if (jQuery('input[name="ccinfo"]:checked').val() === 'new') {
            enable_payment_stripe();
        } else {
            get_existing_token(jQuery('input[name="ccinfo"]:checked').val());
            paymentForm.on('submit', processExisting);
        }
        jQuery('input[name="ccinfo"]').on('ifChecked', function(){
            if (jQuery(this).val() === 'new') {
                enable_payment_stripe();
            } else {
                get_existing_token(jQuery(this).val());
                jQuery('#stripeElements').hide();
                paymentForm.off('submit', validateStripe);
                paymentForm.on('submit', processExisting);
                if (card.hasRegisteredListener('change')) {
                    card.removeEventListener('change', cardListener);
                }
            }
        });
        enablePaymentRequestButton();
    } else if (adminCreditCard.length) {
        adminCreditCard.find('#cctype').closest('tr').hide().remove();
        adminCreditCard.find('#inputCardNumber')
            .closest('div')
            .html('<div id="elementCardNumber" class="form-control"></div>');
        adminCreditCard.find('#inputCardExpiry')
            .closest('div')
            .html('<div id="elementCardExpiry" class="form-control"></div>');
        adminCreditCard.find('#cardcvv')
            .closest('div')
            .html('<div id="elementCardCvc" class="form-control"></div>');
        card.mount('#elementCardNumber');
        cardExpiryElements.mount('#elementCardExpiry');
        cardCvcElements.mount('#elementCardCvc');
        card.addEventListener('change', cardListener);
        cardExpiryElements.addEventListener('change', cardListener);
        cardCvcElements.addEventListener('change', cardListener);

        // same as above - Firefox issues
        elementsDiv = jQuery('#elementCardNumber');
        if (jQuery('#containerStorageInputControl')) {
            var modalFooter = jQuery('#modalAjaxFooter'),
                btnSubmit = modalFooter.find('#btnSave');
            btnSubmit.removeAttr('name');
            btnSubmit.off();
            btnSubmit.on('click', validateChangeCard);
            modalInput = true;
        } else {
            adminCreditCard.find('#btnSaveChanges').removeAttr('name');
            adminCreditCard.on('submit', validateChangeCard);
        }
    }
}

function validateStripe(event) {
    var paymentMethod = jQuery('input[name="paymentmethod"]:checked'),
        frm = elementsDiv.closest('form'),
        displayError = jQuery('.gateway-errors,.assisted-cc-input-feedback').first();

    if (paymentMethod.length && paymentMethod.val() !== 'stripe') {
        return true;
    }
    event.preventDefault();
    // Disable the submit button to prevent repeated clicks:
    frm.find('button[type="submit"],input[type="submit"]')
        .prop('disabled', true)
        .addClass('disabled')
        .find('span').toggleClass('hidden');

    stripe.createPaymentMethod(
        'card',
        card
    ).then(function(result) {
        if (result.error) {
            var error = result.error.message;
            if (error) {
                displayError.html(error);
                if (displayError.hasClass('hidden')) {
                    displayError.removeClass('hidden').show();
                }
                scrollToError();
            }
        } else {
            WHMCS.http.jqClient.jsonPost({
                url: WHMCS.utils.getRouteUrl('/stripe/payment/intent'),
                data: frm.serialize() + '&payment_method_id=' + result.paymentMethod.id,
                success: function(response) {
                    if (response.success) {
                        //payment has been successful already at this point
                        stripeResponseHandler(response.token);
                    } else {
                        stripe.handleCardPayment(
                            response.token,
                            card
                        ).then(function(result) {
                            if (result.error) {
                                var error = result.error.message;
                                if (error) {
                                    displayError.html(error);
                                    if (displayError.hasClass('hidden')) {
                                        displayError.removeClass('hidden').show();
                                    }
                                    scrollToError();
                                }
                            } else {
                                stripeResponseHandler(result.paymentIntent.id);
                            }
                        });

                    }
                },
                warning: function(error) {
                    displayError.html(error);
                    if (displayError.hasClass('hidden')) {
                        displayError.removeClass('hidden').show();
                    }
                    scrollToError();
                },
                fail: function(error) {
                    displayError.html(error);
                    if (displayError.hasClass('hidden')) {
                        displayError.removeClass('hidden').show();
                    }
                    scrollToError();
                }
            });
        }
    });
    // Prevent the form from being submitted:
    return false;
}

function processExisting(event)
{
    var frm = elementsDiv.closest('form'),
        displayError = jQuery('.gateway-errors,.assisted-cc-input-feedback').first();

    frm.find('.gateway-errors').html('').addClass('hidden');
    event.preventDefault();

    // Disable the submit button to prevent repeated clicks:
    frm.find('button[type="submit"],input[type="submit"]')
        .prop('disabled', true)
        .addClass('disabled')
        .find('span').toggleClass('hidden');;

    WHMCS.http.jqClient.jsonPost({
        url: WHMCS.utils.getRouteUrl('/stripe/payment/intent'),
        data: frm.serialize() + '&payment_method_id=' + existingToken,
        success: function(response) {
            if (response.success) {
                //payment has been successful already at this point
                stripeResponseHandler(response.token);
            } else {
                stripe.handleCardPayment(
                    response.token
                ).then(function(result) {
                    if (result.error) {
                        var error = result.error.message;
                        if (error) {
                            displayError.html(error);
                            if (displayError.hasClass('hidden')) {
                                displayError.removeClass('hidden').show();
                            }
                            scrollToError();
                        }
                    } else {
                        stripeResponseHandler(result.paymentIntent.id);
                    }
                });

            }
        },
        fail: function(error) {
            displayError.html(error);
            if (displayError.hasClass('hidden')) {
                displayError.removeClass('hidden').show();
            }
            scrollToError();
        }
    });
}

function stripeResponseHandler(token) {
    var frm = elementsDiv.closest('form');
    frm.find('.gateway-errors,.assisted-cc-input-feedback').html('').addClass('hidden');
    // Insert the token ID into the form so it gets submitted to the server:
    frm.append(jQuery('<input type="hidden" name="remoteStorageToken">').val(token));
    frm.find('button[type="submit"],input[type="submit"]')
        .find('i.fas,i.far,i.fal,i.fab')
        .removeAttr('class')
        .addClass('fas fa-spinner fa-spin');

    if (!modalInput) {
        elementsDiv.hide();
    }

    // Submit the form:
    frm.off('submit', validateStripe);
    frm.off('submit', validateChangeCard);
    frm.off('submit', addNewCardClientSide);
    frm.off('submit', processExisting);

    frm.append('<input type="submit" id="hiddenSubmit" name="submit" value="Save Changes" style="display:none;">');
    var hiddenButton = jQuery('#hiddenSubmit');
    if (modalInput) {

        var modalFooter = jQuery('#modalAjaxFooter'),
            hiddenButton = modalFooter.find('#btnSave');
        hiddenButton.removeClass('disabled');
        jQuery('#modalAjax .loader').fadeOut();
        hiddenButton.off('click', validateChangeCard);
        hiddenButton.on('click', submitIdAjaxModalClickEvent);
    }
    hiddenButton.click();
}

function hide_cc_fields() {
    var frm = elementsDiv.closest('form'),
        cardInputs = jQuery('#newCardInfo,.cc-details,#existingCardInfo');
    if (cardInputs.is(':visible')) {
        cardInputs.slideUp('fast', function() {
            frm.find('#cctype').removeAttr('name');
            frm.find('#inputCardCvvExisting').removeAttr('name');
            frm.find('#inputCardNumber').removeAttr('name');
            frm.find('#inputCardExpiry').removeAttr('name');
            frm.find('#inputCardCVV').removeAttr('name');
            frm.find('#inputCardCvvExisting').removeAttr('name');
        });
    }
}

function enable_stripe() {
    var frm = elementsDiv.closest('form');
    hide_cc_fields();

    elementsDiv.hide().removeClass('hidden').show();
    card.addEventListener('change', cardListener);
    cardExpiryElements.addEventListener('change', cardListener);
    cardCvcElements.addEventListener('change', cardListener);
    if (amount === '000') {
        frm.off('submit', validateStripe);
        frm.on('submit', addNewCardClientSide);
    } else {
        frm.on('submit', validateStripe);
        frm.off('submit', addNewCardClientSide);
    }
    frm.off('submit', processExisting);
}

function disable_stripe() {
    var frm = elementsDiv.closest('form'),
        cardInputs = jQuery('#newCardInfo,.cc-details');

    frm.find('#inputCardCvvExisting').attr('name', 'cccvvexisting');
    frm.find('#inputCardNumber').attr('name', 'ccnumber');
    frm.find('#inputCardExpiry').attr('name', 'ccexpirydate');
    frm.find('#inputCardCVV').attr('name', 'cccvv');
    frm.find('#inputCardCvvExisting').attr('name', 'cccvvexisting');
    frm.find('#cctype').attr('name', 'cctype');

    elementsDiv.hide('fast', function() {
        var firstVisible = jQuery('input[name="ccinfo"]:visible').first();
        if (firstVisible.val() === 'new') {
            cardInputs.show();
        } else {
            firstVisible.click();
        }
    });

    frm.off('submit', validateStripe);
    frm.off('submit', processExisting);
    frm.off('submit', validateChangeCard);
    if (card.hasRegisteredListener('change')) {
        card.removeEventListener('change', cardListener);
    }
    if (cardExpiryElements.hasRegisteredListener('change')) {
        cardExpiryElements.removeEventListener('change', cardListener);
    }
    if (cardCvcElements.hasRegisteredListener('change')) {
        cardCvcElements.removeEventListener('change', cardListener);
    }
}

function enable_payment_stripe() {
    var paymentForm = elementsDiv.closest('form');

    paymentForm.find('#inputCardNumber').closest('div.form-group').remove();
    paymentForm.find('#inputCardExpiry').closest('div.form-group').remove();
    elementsDiv.hide().removeClass('hidden').show();
    card.addEventListener('change', cardListener);
    cardExpiryElements.addEventListener('change', cardListener);
    cardCvcElements.addEventListener('change', cardListener);
    paymentForm.off('submit', processExisting);
    paymentForm.on('submit', validateStripe);
}

function enablePaymentRequestButton() {
    if (paymentRequestButtonEnabled) {
        var frm = elementsDiv.closest('form'),
            paymentRequest = stripe.paymentRequest({
            country: 'US',
            currency: paymentRequestCurrency.toLowerCase(),
            total: {
                label: paymentRequestDescription,
                amount: paymentRequestAmountDue
            },
            requestPayerName: true,
            requestPayerEmail: true,
        }),
            displayError = jQuery('.gateway-errors,.assisted-cc-input-feedback').first();
        var prButton = elements.create('paymentRequestButton', {
            paymentRequest: paymentRequest,
        });

        paymentRequest.canMakePayment().then(function(result) {
            if (result) {
                if (result.applePay) {
                    //we know it's applepay
                } else {
                    //we know it's a browser based card option
                }
                if (jQuery('#paymentRequestButton').length === 0) {
                    elementsDiv.prepend(
                        '<div class="row"><div class="col-md-4 col-md-offset-4">' +
                        '<div id="paymentRequestButton"></div>' +
                        '</div></div>'
                    );
                }
                prButton.mount('#paymentRequestButton');
            }
        });

        paymentRequest.on('paymentmethod', function(ev) {
            var paymentMethodId = ev.paymentMethod.id,
                paymentIntentId = null,
                event = ev,
                frm = elementsDiv.closest('form');
            frm.find('.gateway-errors,.assisted-cc-input-feedback').html('').addClass('hidden');
            frm.find('button[type="submit"],input[type="submit"]')
                .addClass('disabled')
                .prop('disabled', true)
                .find('i.fas,i.far,i.fal,i.fab')
                .removeAttr('class')
                .addClass('fas fa-spinner fa-spin');
            WHMCS.http.jqClient.jsonPost({
                url: WHMCS.utils.getRouteUrl('/stripe/payment/intent'),
                data: frm.serialize() + '&payment_method_id=' + paymentMethodId,
                success: function(response) {
                    paymentIntentId = response.token;
                    if (response.success) {
                        event.complete('success');
                        stripeResponseHandler(response.token);
                    } else {
                        // Let Stripe.js handle the rest of the payment flow.
                        stripe.handleCardPayment(paymentIntentId).then(function(result) {
                            if (result.error) {
                                var error = result.error.message;
                                if (error) {
                                    displayError.html(error);
                                    if (displayError.hasClass('hidden')) {
                                        displayError.removeClass('hidden').show();
                                    }
                                    scrollToError();
                                }
                            } else {
                                stripeResponseHandler(result.paymentIntent.id);
                            }
                        });
                    }
                },
                warning: function(error) {
                    displayError.html(error);
                    if (displayError.hasClass('hidden')) {
                        displayError.removeClass('hidden').show();
                    }
                    scrollToError();
                },
                fail: function(error) {
                    displayError.html(error);
                    if (displayError.hasClass('hidden')) {
                        displayError.removeClass('hidden').show();
                    }
                    scrollToError();
                }
            });
        });
    }
}

function scrollToError() {
    var frm = elementsDiv.closest('form'),
        displayError = jQuery('.gateway-errors,.assisted-cc-input-feedback').first();
    frm.find('button[type="submit"],input[type="submit"]')
        .prop('disabled', false)
        .removeClass('disabled')
        .find('i.fas,i.far,i.fal,i.fab')
        .removeAttr('class')
        .addClass('fas fa-arrow-circle-right')
        .find('span').toggleClass('hidden');

    if (displayError.length) {
        jQuery('html, body').animate(
            {
                scrollTop: displayError.offset().top - 50
            },
            500
        );
    }
}

function insertAndMountElementsDivAfterInput(input) {
    elementsDiv = jQuery('#stripeElements');
    if (!elementsDiv.length) {
        input.after(stripe_cc_html(input));
        var stripeCvvWhere = jQuery('#stripeCvcWhere');
        if (stripeCvvWhere.length) {
            jQuery('#cvvWhereLink').clone().appendTo(stripeCvvWhere);
            // Default catch for all other popovers
            jQuery('[data-toggle="popover"]').popover({
                html: true
            });
        }
        elementsDiv = jQuery('#stripeElements');
        card.mount('#stripeCreditCard');
        cardExpiryElements.mount('#stripeExpiryDate');
        cardCvcElements.mount('#stripeCvc');
    }
}

function insertAndMountElementsDivBeforeInput(input) {
    elementsDiv = jQuery('#stripeElements');
    if (!elementsDiv.length) {
        input.before(stripe_cc_html(input));
        var stripeCvvWhere = jQuery('#stripeCvcWhere');
        if (stripeCvvWhere.length) {
            jQuery('#cvvWhereLink').clone().appendTo(stripeCvvWhere);
            // Default catch for all other popovers
            jQuery('[data-toggle="popover"]').popover({
                html: true
            });
        }
        elementsDiv = jQuery('#stripeElements');
        card.mount('#stripeCreditCard');
        cardExpiryElements.mount('#stripeExpiryDate');
        cardCvcElements.mount('#stripeCvc');
    }

}

function stripe_cc_html(input)
{
    var frm = input.closest('form')[0],
        html = '';

    if (frm.id === 'frmCheckout') {
        html = '<div id="stripeElements" class="form-group hidden">' +
            '<div class="stripe-cards-inputs col-md-8 col-md-offset-2">' +
            '<div class="row">' +
            '<div class="col-md-6">' +
            '<label for="stripeCreditCard">' + lang.creditCardInput + '</label>' +
            '<div id="stripeCreditCard" class="form-control"></div>' +
            '<div id="stripeCardType"></div>' +
            '</div><div class="col-md-3">' +
            '<label for="stripeExpiryDate">' + lang.creditCardExpiry + '</label>' +
            '<div id="stripeExpiryDate" class="form-control"></div>' +
            '</div><div class="col-md-3">' +
            '<label for="stripeCvc">' + lang.creditCardCvc + '</label>' +
            '<div id="stripeCvc" class="form-control"></div>' +
            '</div>' +
            '</div>' + //row
            '</div>' + //stripe-card-inputs
            '</div>' + //#stripeElements
            '<div class="clearfix"></div>';
    } else {
        elementsClass = '';

        html = '<div id="stripeElements" class="hidden">' +
            '<div class="form-group cc-billing-address">' +
            '<label for="stripeCreditCard" class="col-sm-4 control-label">' +
            lang.creditCardInput + '</label>' +
            '<div class="col-sm-7">' +
            '<div id="stripeCreditCard" class="form-control" aria-describedby="cc-type"></div>' +
            '<div id="stripeCardType"></div>' +
            '</div>' + //col-sm-6
            '<div class="col-sm-4"></div>' +
            '</div>' + //form-group
            '<div class="form-group cc-billing-address">' +
            '<label for="stripeExpiryDate" class="col-sm-4 control-label">' +
            lang.creditCardExpiry + '</label>' +
            '<div class="col-sm-2">' +
            '<div id="stripeExpiryDate" class="form-control"></div>' +
            '</div>' + //col-sm-6
            '<div class="col-sm-6"></div>' +
            '</div>' + //form-group
            '<div class="form-group cc-billing-address">' +
            '<label for="stripeCvc" class="col-sm-4 control-label">' +
            lang.creditCardCvc + '</label>' +
            '<div class="col-sm-2">' +
            '<div id="stripeCvc" class="form-control"></div>' +
            '</div>' + //col-sm-2
            '<div class="col-sm-4">' +
            '<div id="stripeCvcWhere"></div>' +
            '</div>' + //col-sm-4
            '</div>' + //form-group
            '</div>' + //row
            '</div>' + //#stripeElements
            '<div class="clearfix"></div>';
    }
    return html;
}

function cardListener(event) {
    var displayError = jQuery('.gateway-errors,.assisted-cc-input-feedback').first(),
        error = '';
    if (typeof event.error !== "undefined") {
        error = event.error.message;

        if (error) {
            displayError.html(error);
            if (displayError.hasClass('hidden')) {
                displayError.removeClass('hidden').show();
            }
            scrollToError();
        }
    } else {
        displayError.hide().addClass('hidden').html('');
    }
    if (typeof event.brand !== 'undefined') {
        // var cardType = jQuery('#stripeCardType');
        // if (cardType.length && event.brand === 'unknown') {
        //     cardType.html('');
        // } else if (cardType.length && event.brand !== 'unknown') {
        //     cardType.html(event.brand.toUpperCase());
        // }
    }
}

function addNewCardClientSide(event)
{
    var frm = elementsDiv.closest('form'),
        displayError = jQuery('.gateway-errors,.assisted-cc-input-feedback').first();
    event.preventDefault();
    // Disable the submit button to prevent repeated clicks:
    frm.find('button[type="submit"],input[type="submit"]')
        .prop('disabled', true)
        .addClass('disabled')
        .find('span').toggleClass('hidden');


    // We need to submit first to our endpoint to start a SetupIntent
    WHMCS.http.jqClient.jsonPost({
        url: WHMCS.utils.getRouteUrl('/stripe/setup/intent'),
        data: frm.serialize(),
        success: function(response) {
            if (response.success) {
                stripe.handleCardSetup(
                    response.setup_intent,
                    card
                ).then(function(result) {
                    if (result.error) {
                        displayError.html(result.error);
                        if (displayError.hasClass('hidden')) {
                            displayError.removeClass('hidden').show();
                        }
                        scrollToError();
                    } else {
                        stripeResponseHandler(result.setupIntent.id);
                    }
                });
            }
        },
        warning: function(error) {
            displayError.html(error);
            if (displayError.hasClass('hidden')) {
                displayError.removeClass('hidden').show();
            }
            scrollToError();
        },
        fail: function(error) {
            displayError.html(error);
            if (displayError.hasClass('hidden')) {
                displayError.removeClass('hidden').show();
            }
            scrollToError();
        }
    });
}

function validateChangeCard(event)
{
    var frm = elementsDiv.closest('form'),
        displayError = jQuery('.gateway-errors,.assisted-cc-input-feedback').first();
    event.preventDefault();
    // Disable the submit button to prevent repeated clicks:
    frm.find('button[type="submit"],input[type="submit"]')
        .prop('disabled', true)
        .addClass('disabled')
        .find('span').toggleClass('hidden');

    stripe.createPaymentMethod(
        'card',
        card
    ).then(function(result) {
        if (result.error) {
            var error = result.error.message;
            if (error) {
                displayError.html(error);
                if (displayError.hasClass('hidden')) {
                    displayError.removeClass('hidden').show();
                }
                scrollToError();
            }
        } else {
            if (modalInput) {
                var btnSubmit = jQuery('#btnSave');
                btnSubmit.addClass('disabled');
                jQuery('#modalAjax .loader').show();
            }
            if (typeof WHMCS.utils !== 'undefined') {
                var url = WHMCS.utils.getRouteUrl('/stripe/payment/add');
            } else {
                var url = WHMCS.adminUtils.getAdminRouteUrl('/stripe/payment/admin/add');
            }
            WHMCS.http.jqClient.jsonPost({
                url: url,
                data: frm.serialize()
                    + '&payment_method_id=' + result.paymentMethod.id,
                success: function(response) {
                    if (response.success) {
                        //payment has been successful already at this point
                        stripeResponseHandler(response.token);
                    }
                },
                warning: function(error) {
                    displayError.html(error);
                    if (displayError.hasClass('hidden')) {
                        displayError.removeClass('hidden').show();
                    }
                    scrollToError();
                },
                fail: function(error) {
                    displayError.html(error);
                    if (displayError.hasClass('hidden')) {
                        displayError.removeClass('hidden').show();
                    }
                    scrollToError();
                },
                always: function() {
                    if (modalInput) {
                        btnSubmit.removeClass('disabled');
                        jQuery('#modalAjax .loader').fadeOut();
                    }
                }
            });
        }
    });
    // Prevent the form from being submitted:
    return false;
}

function get_existing_token(tokenId)
{
    if (typeof tokenId === 'undefined') {
        var input = jQuery('input[name="ccinfo"]:visible:first');
        input.iCheck('check');
        tokenId = input.val();
        if (tokenId === 'new') {
            return;
        }
    }
    var displayError = jQuery('.gateway-errors,.assisted-cc-input-feedback').first();
    WHMCS.http.jqClient.jsonPost({
        url: WHMCS.utils.getRouteUrl('/stripe/payment/get'),
        data: 'paymethod_id=' + tokenId + '&token=' + csrfToken,
        success: function(response) {
            existingToken = response.token;
        },
        warning: function(error) {
            displayError.html(error);
            if (displayError.hasClass('hidden')) {
                displayError.removeClass('hidden').show();
            }
            scrollToError();
            reset_input_to_new();
        },
        fail: function(error) {
            displayError.html(error);
            if (displayError.hasClass('hidden')) {
                displayError.removeClass('hidden').show();
            }
            scrollToError();
            reset_input_to_new();
        }
    });
}

function reset_input_to_new()
{
    jQuery('input[name="ccinfo"][value="new"]').iCheck('check');
    if (jQuery('#existingCardInfo').is(':visible')) {
        jQuery('#existingCardInfo').hide();
    }

    setTimeout(function() {
        jQuery('.gateway-errors,.assisted-cc-input-feedback').hide().addClass('hidden');
    }, 4000);
}
