jQuery(document).ready(function() {
    var existingCards = jQuery('.existing-card'),
        cvvFieldContainer = jQuery('#cvv-field-container'),
        existingCardContainer = jQuery('#existingCardsContainer'),
        newCardInfo = jQuery('#newCardInfo'),
        existingCardInfo = jQuery('#existingCardInfo'),
        newCardOption = jQuery('#new'),
        creditCardInputFields = jQuery('#creditCardInputFields');

    existingCards.on('click', function(event) {
        if (jQuery('.payment-methods:checked').val() === 'stripe') {
            return;
        }

        newCardInfo.slideUp().find('input').attr('disabled', 'disabled');
        existingCardInfo.slideDown().find('input').removeAttr('disabled');
    });
    newCardOption.on('click', function(event) {
        if (jQuery('.payment-methods:checked').val() === 'stripe') {
            return;
        }

        newCardInfo.slideDown().find('input').removeAttr('disabled');
        existingCardInfo.slideUp().find('input').attr('disabled', 'disabled');
    });

    if (!existingCards.length) {
        existingCardInfo.slideUp().find('input').attr('disabled', 'disabled');
    }

    jQuery(".payment-methods").on('click', function(event) {
        if (jQuery(this).hasClass('is-credit-card')) {
            var gatewayPaymentType = jQuery(this).data('payment-type'),
                gatewayModule = jQuery(this).val(),
                showLocal = jQuery(this).data('show-local'),
                relevantMethods = [];

            existingCards.each(function(index) {
                var paymentType = jQuery(this).data('payment-type'),
                    paymentModule = jQuery(this).data('payment-gateway'),
                    payMethodId = jQuery(this).val();

                var paymentTypeMatch = (paymentType === gatewayPaymentType);

                var paymentModuleMatch = false;
                if (gatewayPaymentType === 'RemoteCreditCard') {
                    // only show remote credit cards that belong to the selected gateway
                    paymentModuleMatch = (paymentModule === gatewayModule);
                } else if (gatewayPaymentType === 'CreditCard') {
                    // any local credit card can be used with any credit card gateway
                    paymentModuleMatch = true;
                }

                if (showLocal && paymentType === 'CreditCard') {
                    paymentTypeMatch = true;
                    paymentModuleMatch = true;
                }

                var payMethodElements = jQuery('[data-paymethod-id="' + payMethodId + '"]');

                if (paymentTypeMatch && paymentModuleMatch) {
                    jQuery(payMethodElements).show();
                    relevantMethods.push(this);
                } else {
                    jQuery(payMethodElements).hide();
                }
            });

            var enabledRelevantMethods = relevantMethods.filter(function (item) {
                return ! jQuery(item).attr('disabled');
            });

            if (enabledRelevantMethods.length > 0) {
                var defaultId = null;
                jQuery.each(enabledRelevantMethods, function(index, value) {
                    var jQueryElement = jQuery(value),
                        order = parseInt(jQueryElement.data('order-preference'), 10);
                    if ((defaultId === null) || (order < defaultId)) {
                        defaultId = jQueryElement.val();
                    }
                });
                if (defaultId === null) {
                    defaultId = 'new';
                }

                jQuery.each(enabledRelevantMethods, function(index, value) {
                    var jQueryElement = jQuery(value);
                    if (jQueryElement.val() === defaultId) {
                        jQueryElement.iCheck('check');
                        return false;
                    }
                });
                existingCardContainer.show();
                existingCardInfo.removeClass('hidden').show().find('input').removeAttr('disabled');
            } else {
                jQuery(newCardOption).trigger('click');
                existingCardContainer.hide();
                existingCardInfo.hide().find('input').attr('disabled', 'disabled');
            }

            if (!creditCardInputFields.is(":visible")) {
                creditCardInputFields.hide().removeClass('hidden').slideDown();
            }
        } else {
            creditCardInputFields.slideUp();
        }
    });

    // make sure relevant payment methods are displayed for the pre-selected gateway
    jQuery(".payment-methods:checked").trigger('click');

    jQuery('.cc-input-container .paymethod-info').click(function() {
        var payMethodId = $(this).data('paymethod-id');
        var input = jQuery('input[name="ccinfo"][value=' + payMethodId + ']:not(:disabled)');

        if (input.length > 0) {
            input.trigger('click');
        }
    });
});

function chooseDomainReg(type) {
    jQuery(".domain-option").hide();
    jQuery("#domopt-" + type).hide().removeClass('hidden').fadeIn();
}

function removeItem(type,num) {
    var response = confirm(removeItemText);
    if (response) {
        window.location = 'cart.php?a=remove&r=' + type + '&i=' + num;
    }
}

function showPromoInput() {
    jQuery("#promoAddText").fadeOut('slow', function() {
        jQuery("#promoInput").hide().removeClass('hidden').fadeIn('slow');
    });
}

function showLogin() {
    jQuery("#inputCustType").val('existing');
    jQuery("#signupContainer").fadeOut();
    jQuery("#btnCompleteOrder").attr('formnovalidate', true);
    jQuery("#loginContainer").hide().removeClass('hidden').fadeIn();
}

function showSignup() {
    jQuery("#inputCustType").val('new');
    jQuery("#loginContainer").fadeOut();
    jQuery("#signupContainer").fadeIn();
    jQuery("#btnCompleteOrder").removeAttr('formnovalidate');
}

function domainContactChange() {
    if (jQuery("#inputDomainContact").val() == "addingnew") {
        jQuery("#domainContactContainer").hide().removeClass('hidden').slideDown();
    } else {
        jQuery("#domainContactContainer").slideUp();
    }
}
