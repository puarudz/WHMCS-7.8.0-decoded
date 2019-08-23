jQuery(document).ready(function(){
    jQuery("#existingcust").click(function(){
        if (jQuery(this).hasClass('active')!=true) {
            jQuery(".signuptype").removeClass('active');
            jQuery(this).addClass('active');
            jQuery("#signupfrm").fadeToggle('fast',function(){
                jQuery("#securityQuestion").fadeToggle('fast');
                jQuery("#loginfrm").hide().removeClass('hidden').fadeToggle('fast');
                jQuery("#btnCompleteOrder").attr('formnovalidate', true);
                jQuery("#btnUpdateOnly").attr('formnovalidate', true);
            });
            jQuery("#custtype").val("existing");
        }
    });
    jQuery("#newcust").click(function(){
        if (jQuery(this).hasClass('active')!=true) {
            jQuery(".signuptype").removeClass('active');
            jQuery(this).addClass('active');
            jQuery("#loginfrm").fadeToggle('fast',function(){
                jQuery("#securityQuestion").fadeToggle('fast');
                jQuery("#signupfrm").hide().removeClass('hidden').fadeToggle('fast');
                jQuery("#btnCompleteOrder").removeAttr('formnovalidate');
                jQuery("#btnUpdateOnly").removeAttr('formnovalidate');
            });
            jQuery("#custtype").val("new");
        }
    });
    jQuery("#inputDomainContact").on('change', function() {
        if (this.value == "addingnew") {
            jQuery("#domaincontactfields :input")
                .not("#domaincontactaddress2, #domaincontactcompanyname")
                .attr('required', true);
            jQuery("#domaincontactfields").hide().removeClass('hidden').slideDown();
        } else {
            jQuery("#domaincontactfields").slideUp();
            jQuery("#domaincontactfields :input").attr('required', false);
        }
    });

    var existingCards = jQuery('.existing-card'),
        cvvFieldContainer = jQuery('#cvv-field-container'),
        existingCardContainer = jQuery('#existingCardsContainer'),
        newCardInfo = jQuery('#newCardInfo'),
        existingCardInfo = jQuery('#existingCardInfo'),
        newCardOption = jQuery('#new'),
        creditCardInputFields = jQuery('#creditCardInputFields');

    existingCards.on('click', function(event) {
        if (jQuery(this).val() === 'stripe') {
            return;
        }

        newCardInfo.slideUp().find('input').attr('disabled', 'disabled');
        existingCardInfo.slideDown().find('input').removeAttr('disabled');
    });
    newCardOption.on('click', function(event) {
        if (jQuery(this).val() === 'stripe') {
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

function showcats() {
    jQuery("#categories").slideToggle();
}

function selproduct(num) {
    jQuery('#productslider').slider("value", num);
    jQuery(".product").hide();
    jQuery("#product"+num).show();
    jQuery(".sliderlabel").removeClass("selected");
    jQuery("#prodlabel"+num).addClass("selected");
}

function recalctotals(hideLoading) {
    if (typeof hideLoading === 'undefined') {
        hideLoading = true;
    }
    if (!jQuery("#cartLoader").is(":visible")) {
        jQuery("#cartLoader").fadeIn('fast');
    }
    var post = WHMCS.http.jqClient.post("cart.php", 'ajax=1&a=confproduct&calctotal=true&'+jQuery("#orderfrm").serialize());
    post.done(
        function(data) {
            jQuery("#producttotal").html(data);
        }
    );
    if (hideLoading) {
        post.always(
            function() {
                jQuery("#cartLoader").delay(500).fadeOut('slow');
            }
        );
    }
}

function addtocart(gid) {
    jQuery("#loading1").slideDown();
    WHMCS.http.jqClient.post("cart.php", 'ajax=1&a=confproduct&'+jQuery("#orderfrm").serialize(),
    function(data){
        if (data) {
            jQuery("#configproducterror").html(data);
            jQuery("#configproducterror").slideDown();
            jQuery("#loading1").slideUp();
        } else {
            if (gid) window.location='cart.php?gid='+gid;
            else window.location='cart.php?a=confdomains';
        }
    });
}

function updateConfigurableOptions(i, billingCycle) {
    jQuery("#cartLoader").fadeIn('fast');
    var post = WHMCS.http.jqClient.post(
        "cart.php",
        'a=cyclechange&ajax=1&i='+i+'&billingcycle='+billingCycle
    );

    post.done(
        function(data){
            if (data=='') {
                window.location='cart.php?a=view';
            } else {
                jQuery("#prodconfigcontainer").replaceWith(data);
                jQuery("#prodconfigcontainer").slideDown();
                recalctotals(false);
            }
        }
    );

    post.always(
        function() {
            jQuery("#cartLoader").delay(500).fadeOut('slow');
        }

    );
}

function catchEnter(e) {
    if (e) {
        addtocart();
        e.returnValue=false;
    }
}
