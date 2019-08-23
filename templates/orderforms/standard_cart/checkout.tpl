<script>
    // Define state tab index value
    var statesTab = 10;
    // Do not enforce state input client side
    var stateNotRequired = true;
</script>
{include file="orderforms/standard_cart/common.tpl"}
<script type="text/javascript" src="{$BASE_PATH_JS}/StatesDropdown.js"></script>
<script type="text/javascript" src="{$BASE_PATH_JS}/PasswordStrength.js"></script>
<script>
    window.langPasswordStrength = "{$LANG.pwstrength}";
    window.langPasswordWeak = "{$LANG.pwstrengthweak}";
    window.langPasswordModerate = "{$LANG.pwstrengthmoderate}";
    window.langPasswordStrong = "{$LANG.pwstrengthstrong}";
</script>

<div id="order-standard_cart">

    <div class="row">

        <div class="pull-md-right col-md-9">

            <div class="header-lined">
                <h1>{$LANG.orderForm.checkout}</h1>
            </div>

        </div>

        <div class="col-md-3 pull-md-left sidebar hidden-xs hidden-sm">

            {include file="orderforms/standard_cart/sidebar-categories.tpl"}

        </div>

        <div class="col-md-9 pull-md-right">

            {include file="orderforms/standard_cart/sidebar-categories-collapsed.tpl"}

            <div class="already-registered clearfix">
                <div class="pull-right">
                    <button type="button" class="btn btn-info{if $loggedin || !$loggedin && $custtype eq "existing"} hidden{/if}" id="btnAlreadyRegistered">
                        {$LANG.orderForm.alreadyRegistered}
                    </button>
                    <button type="button" class="btn btn-warning{if $loggedin || $custtype neq "existing"} hidden{/if}" id="btnNewUserSignup">
                        {$LANG.orderForm.createAccount}
                    </button>
                </div>
                <p>{$LANG.orderForm.enterPersonalDetails}</p>
            </div>

            {if $errormessage}
                <div class="alert alert-danger checkout-error-feedback" role="alert">
                    <p>{$LANG.orderForm.correctErrors}:</p>
                    <ul>
                        {$errormessage}
                    </ul>
                </div>
                <div class="clearfix"></div>
            {/if}

            <form method="post" action="{$smarty.server.PHP_SELF}?a=checkout" name="orderfrm" id="frmCheckout">
                <input type="hidden" name="submit" value="true" />
                <input type="hidden" name="custtype" id="inputCustType" value="{$custtype}" />

                <div id="containerExistingUserSignin"{if $loggedin || $custtype neq "existing"} class="hidden"{/if}>

                    <div class="sub-heading">
                        <span>{$LANG.orderForm.existingCustomerLogin}</span>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group prepend-icon">
                                <label for="inputLoginEmail" class="field-icon">
                                    <i class="fas fa-envelope"></i>
                                </label>
                                <input type="text" name="loginemail" id="inputLoginEmail" class="field" placeholder="{$LANG.orderForm.emailAddress}">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group prepend-icon">
                                <label for="inputLoginPassword" class="field-icon">
                                    <i class="fas fa-lock"></i>
                                </label>
                                <input type="password" name="loginpassword" id="inputLoginPassword" class="field" placeholder="{$LANG.clientareapassword}">
                            </div>
                        </div>
                    </div>

                    {include file="orderforms/standard_cart/linkedaccounts.tpl" linkContext="checkout-existing"}
                </div>

                <div id="containerNewUserSignup"{if $custtype eq "existing" AND !$loggedin} class="hidden"{/if}>

                    <div{if $loggedin} class="hidden"{/if}>
                        {include file="orderforms/standard_cart/linkedaccounts.tpl" linkContext="checkout-new"}
                    </div>

                    <div class="sub-heading">
                        <span>{$LANG.orderForm.personalInformation}</span>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group prepend-icon">
                                <label for="inputFirstName" class="field-icon">
                                    <i class="fas fa-user"></i>
                                </label>
                                <input type="text" name="firstname" id="inputFirstName" class="field" placeholder="{$LANG.orderForm.firstName}" value="{$clientsdetails.firstname}"{if $loggedin} readonly="readonly"{/if} autofocus>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group prepend-icon">
                                <label for="inputLastName" class="field-icon">
                                    <i class="fas fa-user"></i>
                                </label>
                                <input type="text" name="lastname" id="inputLastName" class="field" placeholder="{$LANG.orderForm.lastName}" value="{$clientsdetails.lastname}"{if $loggedin} readonly="readonly"{/if}>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group prepend-icon">
                                <label for="inputEmail" class="field-icon">
                                    <i class="fas fa-envelope"></i>
                                </label>
                                <input type="email" name="email" id="inputEmail" class="field" placeholder="{$LANG.orderForm.emailAddress}" value="{$clientsdetails.email}"{if $loggedin} readonly="readonly"{/if}>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group prepend-icon">
                                <label for="inputPhone" class="field-icon">
                                    <i class="fas fa-phone"></i>
                                </label>
                                <input type="tel" name="phonenumber" id="inputPhone" class="field" placeholder="{$LANG.orderForm.phoneNumber}" value="{$clientsdetails.phonenumber}"{if $loggedin} readonly="readonly"{/if}>
                            </div>
                        </div>
                    </div>

                    <div class="sub-heading">
                        <span>{$LANG.orderForm.billingAddress}</span>
                    </div>

                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group prepend-icon">
                                <label for="inputCompanyName" class="field-icon">
                                    <i class="fas fa-building"></i>
                                </label>
                                <input type="text" name="companyname" id="inputCompanyName" class="field" placeholder="{$LANG.orderForm.companyName} ({$LANG.orderForm.optional})" value="{$clientsdetails.companyname}"{if $loggedin} readonly="readonly"{/if}>
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group prepend-icon">
                                <label for="inputAddress1" class="field-icon">
                                    <i class="far fa-building"></i>
                                </label>
                                <input type="text" name="address1" id="inputAddress1" class="field" placeholder="{$LANG.orderForm.streetAddress}" value="{$clientsdetails.address1}"{if $loggedin} readonly="readonly"{/if}>
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group prepend-icon">
                                <label for="inputAddress2" class="field-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </label>
                                <input type="text" name="address2" id="inputAddress2" class="field" placeholder="{$LANG.orderForm.streetAddress2}" value="{$clientsdetails.address2}"{if $loggedin} readonly="readonly"{/if}>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group prepend-icon">
                                <label for="inputCity" class="field-icon">
                                    <i class="far fa-building"></i>
                                </label>
                                <input type="text" name="city" id="inputCity" class="field" placeholder="{$LANG.orderForm.city}" value="{$clientsdetails.city}"{if $loggedin} readonly="readonly"{/if}>
                            </div>
                        </div>
                        <div class="col-sm-5">
                            <div class="form-group prepend-icon">
                                <label for="state" class="field-icon" id="inputStateIcon">
                                    <i class="fas fa-map-signs"></i>
                                </label>
                                <label for="stateinput" class="field-icon" id="inputStateIcon">
                                    <i class="fas fa-map-signs"></i>
                                </label>
                                <input type="text" name="state" id="inputState" class="field" placeholder="{$LANG.orderForm.state}" value="{$clientsdetails.state}"{if $loggedin} readonly="readonly"{/if}>
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group prepend-icon">
                                <label for="inputPostcode" class="field-icon">
                                    <i class="fas fa-certificate"></i>
                                </label>
                                <input type="text" name="postcode" id="inputPostcode" class="field" placeholder="{$LANG.orderForm.postcode}" value="{$clientsdetails.postcode}"{if $loggedin} readonly="readonly"{/if}>
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group prepend-icon">
                                <label for="inputCountry" class="field-icon" id="inputCountryIcon">
                                    <i class="fas fa-globe"></i>
                                </label>
                                <select name="country" id="inputCountry" class="field"{if $loggedin} disabled="disabled"{/if}>
                                    {foreach $countries as $countrycode => $countrylabel}
                                        <option value="{$countrycode}"{if (!$country && $countrycode == $defaultcountry) || $countrycode eq $country} selected{/if}>
                                            {$countrylabel}
                                        </option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                        {if $showTaxIdField}
                            <div class="col-sm-12">
                                <div class="form-group prepend-icon">
                                    <label for="inputTaxId" class="field-icon">
                                        <i class="fas fa-building"></i>
                                    </label>
                                    <input type="text" name="tax_id" id="inputTaxId" class="field" placeholder="{lang key=\WHMCS\Billing\Tax\Vat::getLabel()} ({$LANG.orderForm.optional})" value="{$clientsdetails.tax_id}"{if $loggedin} readonly="readonly"{/if}>
                                </div>
                            </div>
                        {/if}
                    </div>

                    {if $customfields}
                        <div class="sub-heading">
                            <span>{$LANG.orderadditionalrequiredinfo}</span>
                        </div>
                        <div class="field-container">
                            <div class="row">
                                {foreach $customfields as $customfield}
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label for="customfield{$customfield.id}">{$customfield.name}</label>
                                            {$customfield.input}
                                            {if $customfield.description}
                                                <span class="field-help-text">
                                                    {$customfield.description}
                                                </span>
                                            {/if}
                                        </div>
                                    </div>
                                {/foreach}
                            </div>
                        </div>
                    {/if}

                </div>

                {if $domainsinorder}

                    <div class="sub-heading">
                        <span>{$LANG.domainregistrantinfo}</span>
                    </div>

                    <p class="small text-muted">{$LANG.orderForm.domainAlternativeContact}</p>

                    <div class="row margin-bottom">
                        <div class="col-sm-6 col-sm-offset-3">
                            <select name="contact" id="inputDomainContact" class="field">
                                <option value="">{$LANG.usedefaultcontact}</option>
                                {foreach $domaincontacts as $domcontact}
                                    <option value="{$domcontact.id}"{if $contact == $domcontact.id} selected{/if}>
                                        {$domcontact.name}
                                    </option>
                                {/foreach}
                                <option value="addingnew"{if $contact == "addingnew"} selected{/if}>
                                    {$LANG.clientareanavaddcontact}...
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="row{if $contact neq "addingnew"} hidden{/if}" id="domainRegistrantInputFields">
                        <div class="col-sm-6">
                            <div class="form-group prepend-icon">
                                <label for="inputDCFirstName" class="field-icon">
                                    <i class="fas fa-user"></i>
                                </label>
                                <input type="text" name="domaincontactfirstname" id="inputDCFirstName" class="field" placeholder="{$LANG.orderForm.firstName}" value="{$domaincontact.firstname}">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group prepend-icon">
                                <label for="inputDCLastName" class="field-icon">
                                    <i class="fas fa-user"></i>
                                </label>
                                <input type="text" name="domaincontactlastname" id="inputDCLastName" class="field" placeholder="{$LANG.orderForm.lastName}" value="{$domaincontact.lastname}">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group prepend-icon">
                                <label for="inputDCEmail" class="field-icon">
                                    <i class="fas fa-envelope"></i>
                                </label>
                                <input type="email" name="domaincontactemail" id="inputDCEmail" class="field" placeholder="{$LANG.orderForm.emailAddress}" value="{$domaincontact.email}">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group prepend-icon">
                                <label for="inputDCPhone" class="field-icon">
                                    <i class="fas fa-phone"></i>
                                </label>
                                <input type="tel" name="domaincontactphonenumber" id="inputDCPhone" class="field" placeholder="{$LANG.orderForm.phoneNumber}" value="{$domaincontact.phonenumber}">
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group prepend-icon">
                                <label for="inputDCCompanyName" class="field-icon">
                                    <i class="fas fa-building"></i>
                                </label>
                                <input type="text" name="domaincontactcompanyname" id="inputDCCompanyName" class="field" placeholder="{$LANG.orderForm.companyName} ({$LANG.orderForm.optional})" value="{$domaincontact.companyname}">
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group prepend-icon">
                                <label for="inputDCAddress1" class="field-icon">
                                    <i class="far fa-building"></i>
                                </label>
                                <input type="text" name="domaincontactaddress1" id="inputDCAddress1" class="field" placeholder="{$LANG.orderForm.streetAddress}" value="{$domaincontact.address1}">
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group prepend-icon">
                                <label for="inputDCAddress2" class="field-icon">
                                    <i class="fas fa-map-marker-alt"></i>
                                </label>
                                <input type="text" name="domaincontactaddress2" id="inputDCAddress2" class="field" placeholder="{$LANG.orderForm.streetAddress2}" value="{$domaincontact.address2}">
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group prepend-icon">
                                <label for="inputDCCity" class="field-icon">
                                    <i class="far fa-building"></i>
                                </label>
                                <input type="text" name="domaincontactcity" id="inputDCCity" class="field" placeholder="{$LANG.orderForm.city}" value="{$domaincontact.city}">
                            </div>
                        </div>
                        <div class="col-sm-5">
                            <div class="form-group prepend-icon">
                                <label for="inputDCState" class="field-icon">
                                    <i class="fas fa-map-signs"></i>
                                </label>
                                <input type="text" name="domaincontactstate" id="inputDCState" class="field" placeholder="{$LANG.orderForm.state}" value="{$domaincontact.state}">
                            </div>
                        </div>
                        <div class="col-sm-3">
                            <div class="form-group prepend-icon">
                                <label for="inputDCPostcode" class="field-icon">
                                    <i class="fas fa-certificate"></i>
                                </label>
                                <input type="text" name="domaincontactpostcode" id="inputDCPostcode" class="field" placeholder="{$LANG.orderForm.postcode}" value="{$domaincontact.postcode}">
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group prepend-icon">
                                <label for="inputDCCountry" class="field-icon" id="inputCountryIcon">
                                    <i class="fas fa-globe"></i>
                                </label>
                                <select name="domaincontactcountry" id="inputDCCountry" class="field">
                                    {foreach $countries as $countrycode => $countrylabel}
                                        <option value="{$countrycode}"{if (!$domaincontact.country && $countrycode == $defaultcountry) || $countrycode eq $domaincontact.country} selected{/if}>
                                            {$countrylabel}
                                        </option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group prepend-icon">
                                <label for="inputDCTaxId" class="field-icon">
                                    <i class="fas fa-building"></i>
                                </label>
                                <input type="text" name="domaincontacttax_id" id="inputDCTaxId" class="field" placeholder="{lang key=\WHMCS\Billing\Tax\Vat::getLabel()} ({$LANG.orderForm.optional})" value="{$domaincontact.tax_id}">
                            </div>
                        </div>
                    </div>

                {/if}

                {if !$loggedin}

                    <div id="containerNewUserSecurity"{if (!$loggedin && $custtype eq "existing") || ($remote_auth_prelinked && !$securityquestions) } class="hidden"{/if}>

                        <div class="sub-heading">
                            <span>{$LANG.orderForm.accountSecurity}</span>
                        </div>

                        <div id="containerPassword" class="row{if $remote_auth_prelinked && $securityquestions} hidden{/if}">
                            <div id="passwdFeedback" style="display: none;" class="alert alert-info text-center col-sm-12"></div>
                            <div class="col-sm-6">
                                <div class="form-group prepend-icon">
                                    <label for="inputNewPassword1" class="field-icon">
                                        <i class="fas fa-lock"></i>
                                    </label>
                                    <input type="password" name="password" id="inputNewPassword1" data-error-threshold="{$pwStrengthErrorThreshold}" data-warning-threshold="{$pwStrengthWarningThreshold}" class="field" placeholder="{$LANG.clientareapassword}"{if $remote_auth_prelinked} value="{$password}"{/if}>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group prepend-icon">
                                    <label for="inputNewPassword2" class="field-icon">
                                        <i class="fas fa-lock"></i>
                                    </label>
                                    <input type="password" name="password2" id="inputNewPassword2" class="field" placeholder="{$LANG.clientareaconfirmpassword}"{if $remote_auth_prelinked} value="{$password}"{/if}>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <button type="button" class="btn btn-default btn-sm generate-password" data-targetfields="inputNewPassword1,inputNewPassword2">
                                    {$LANG.generatePassword.btnLabel}
                                </button>
                            </div>
                            <div class="col-sm-6">
                                <div class="password-strength-meter">
                                    <div class="progress">
                                        <div class="progress-bar progress-bar-success progress-bar-striped" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" id="passwordStrengthMeterBar">
                                        </div>
                                    </div>
                                    <p class="text-center small text-muted" id="passwordStrengthTextLabel">{$LANG.pwstrength}: {$LANG.pwstrengthenter}</p>
                                </div>
                            </div>
                        </div>
                        {if $securityquestions}
                            <div class="row">
                                <div class="col-sm-6">
                                    <select name="securityqid" id="inputSecurityQId" class="field">
                                        <option value="">{$LANG.clientareasecurityquestion}</option>
                                        {foreach $securityquestions as $question}
                                            <option value="{$question.id}"{if $question.id eq $securityqid} selected{/if}>
                                                {$question.question}
                                            </option>
                                        {/foreach}
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group prepend-icon">
                                        <label for="inputSecurityQAns" class="field-icon">
                                            <i class="fas fa-lock"></i>
                                        </label>
                                        <input type="password" name="securityqans" id="inputSecurityQAns" class="field" placeholder="{$LANG.clientareasecurityanswer}">
                                    </div>
                                </div>
                            </div>
                        {/if}

                    </div>

                {/if}

                {foreach $hookOutput as $output}
                    <div>
                        {$output}
                    </div>
                {/foreach}

                <div class="sub-heading">
                    <span>{$LANG.orderForm.paymentDetails}</span>
                </div>

                <div class="alert alert-success text-center large-text" role="alert">
                    {$LANG.ordertotalduetoday}: &nbsp; <strong>{$total}</strong>
                </div>

                {if $canUseCreditOnCheckout}
                    <div id="applyCreditContainer" class="apply-credit-container" data-apply-credit="{$applyCredit}">
                        <p>{lang key='cart.availableCreditBalance' amount=$creditBalance}</p>

                        {if $creditBalance->toNumeric() >= $total->toNumeric()}
                            <label class="radio">
                                <input id="useFullCreditOnCheckout" type="radio" name="applycredit" value="1"{if $applyCredit} checked{/if}>
                                {lang key='cart.applyCreditAmountNoFurtherPayment' amount=$total}
                            </label>
                        {else}
                            <label class="radio">
                                <input id="useCreditOnCheckout" type="radio" name="applycredit" value="1"{if $applyCredit} checked{/if}>
                                {lang key='cart.applyCreditAmount' amount=$creditBalance}
                            </label>
                        {/if}

                        <label class="radio">
                            <input id="skipCreditOnCheckout" type="radio" name="applycredit" value="0"{if !$applyCredit} checked{/if}>
                            {lang key='cart.applyCreditSkip' amount=$creditBalance}
                        </label>
                    </div>
                {/if}
                <div id="paymentGatewaysContainer" class="form-group">
                    <p class="small text-muted">{$LANG.orderForm.preferredPaymentMethod}</p>

                    <div class="text-center">
                        {foreach $gateways as $gateway}
                            <label class="radio-inline">
                                <input type="radio"
                                       name="paymentmethod"
                                       value="{$gateway.sysname}"
                                       data-payment-type="{$gateway.payment_type}"
                                       data-show-local="{$gateway.show_local_cards}"
                                       class="payment-methods{if $gateway.type eq "CC"} is-credit-card{/if}"
                                       {if $selectedgateway eq $gateway.sysname} checked{/if}
                                />
                                {$gateway.name}
                            </label>
                        {/foreach}
                    </div>
                </div>

                <div class="alert alert-danger text-center gateway-errors hidden"></div>

                <div class="clearfix"></div>

                <div class="cc-input-container{if $selectedgatewaytype neq "CC"} hidden{/if}" id="creditCardInputFields">
                    {if $client}
                        <div id="existingCardsContainer" class="existing-cc-grid">
                            {foreach $client->payMethods->validateGateways()->sortByExpiryDate() as $payMethod}
                                {assign "payMethodExpired" 0}
                                {assign "expiryDate" ""}
                                {if $payMethod->isCreditCard()}
                                    {if ($payMethod->payment->isExpired())}
                                        {assign "payMethodExpired" 1}
                                    {/if}

                                    {if $payMethod->payment->getExpiryDate()}
                                        {assign "expiryDate" $payMethod->payment->getExpiryDate()->format('m/Y')}
                                    {/if}
                                {/if}

                                <div class="paymethod-info radio-inline" data-paymethod-id="{$payMethod->id}">
                                    <input
                                        type="radio"
                                        name="ccinfo"
                                        class="existing-card"
                                        {if $payMethodExpired}disabled{/if}
                                        data-payment-type="{$payMethod->getType()}"
                                        data-payment-gateway="{$payMethod->gateway_name}"
                                        data-order-preference="{$payMethod->order_preference}"
                                        value="{$payMethod->id}">
                                </div>

                                <div class="paymethod-info" data-paymethod-id="{$payMethod->id}">
                                    <i class="{$payMethod->getFontAwesomeIcon()}"></i>
                                </div>
                                <div class="paymethod-info" data-paymethod-id="{$payMethod->id}">
                                    {if $payMethod->isCreditCard() || $payMethod->isRemoteBankAccount()}
                                        {$payMethod->payment->getDisplayName()}
                                    {else}
                                        <span class="type">
                                            {$payMethod->payment->getAccountType()}
                                        </span>
                                        {substr($payMethod->payment->getAccountNumber(), -4)}
                                    {/if}
                                </div>
                                <div class="paymethod-info" data-paymethod-id="{$payMethod->id}">
                                    {$payMethod->getDescription()}
                                </div>
                                <div class="paymethod-info" data-paymethod-id="{$payMethod->id}">
                                    {$expiryDate}{if $payMethodExpired}<br><small>{$LANG.clientareaexpired}</small>{/if}
                                </div>
                            {/foreach}
                        </div>
                    {/if}
                    <div class="row cvv-input" id="existingCardInfo">
                        <div class="col-lg-3 col-sm-4">
                            <div class="form-group prepend-icon">
                                <label for="inputCardCVV2" class="field-icon">
                                    <i class="fas fa-barcode"></i>
                                </label>
                                <div class="input-group">
                                    <input type="tel" name="cccvv" id="inputCardCVV2" class="field" placeholder="{$LANG.creditcardcvvnumbershort}" autocomplete="cc-cvc">
                                    <span class="input-group-btn">
                                        <button type="button" class="btn btn-default" data-toggle="popover" data-placement="bottom" data-content="<img src='{$BASE_PATH_IMG}/ccv.gif' width='210' />">
                                            ?
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <ul>
                        <li>
                            <label class="radio-inline">
                                <input type="radio" name="ccinfo" value="new" id="new" {if !$client || $client->payMethods->count() === 0} checked="checked"{/if} />
                                &nbsp;
                                {lang key='creditcardenternewcard'}
                            </label>
                        </li>
                    </ul>

                    <div class="row" id="newCardInfo">
                        <div id="cardNumberContainer" class="col-sm-6 new-card-container">
                            <div class="form-group prepend-icon">
                                <label for="inputCardNumber" class="field-icon">
                                    <i class="fas fa-credit-card"></i>
                                </label>
                                <input type="tel" name="ccnumber" id="inputCardNumber" class="field cc-number-field" placeholder="{$LANG.orderForm.cardNumber}" autocomplete="cc-number">
                            </div>
                        </div>
                        <div class="col-sm-3 new-card-container">
                            <div class="form-group prepend-icon">
                                <label for="inputCardExpiry" class="field-icon">
                                    <i class="fas fa-calendar-alt"></i>
                                </label>
                                <input type="tel" name="ccexpirydate" id="inputCardExpiry" class="field" placeholder="MM / YY{if $showccissuestart} ({$LANG.creditcardcardexpires}){/if}" autocomplete="cc-exp">
                            </div>
                        </div>
                        <div class="col-sm-3" id="cvv-field-container">
                            <div class="form-group prepend-icon">
                                <label for="inputCardCVV" class="field-icon">
                                    <i class="fas fa-barcode"></i>
                                </label>
                                <div class="input-group">
                                    <input type="tel" name="cccvv" id="inputCardCVV" class="field" placeholder="{$LANG.creditcardcvvnumbershort}" autocomplete="cc-cvc">
                                    <span class="input-group-btn">
                                        <button type="button" class="btn btn-default" data-toggle="popover" data-placement="bottom" data-content="<img src='{$BASE_PATH_IMG}/ccv.gif' width='210' />">
                                            ?
                                        </button>
                                    </span>
                                </div>
                            </div>
                        </div>
                        {if $showccissuestart}
                            <div class="col-sm-3 col-sm-offset-6 new-card-container">
                                <div class="form-group prepend-icon">
                                    <label for="inputCardStart" class="field-icon">
                                        <i class="far fa-calendar-check"></i>
                                    </label>
                                    <input type="tel" name="ccstartdate" id="inputCardStart" class="field" placeholder="MM / YY ({$LANG.creditcardcardstart})" autocomplete="cc-exp">
                                </div>
                            </div>
                            <div class="col-sm-3 new-card-container">
                                <div class="form-group prepend-icon">
                                    <label for="inputCardIssue" class="field-icon">
                                        <i class="fas fa-asterisk"></i>
                                    </label>
                                    <input type="tel" name="ccissuenum" id="inputCardIssue" class="field" placeholder="{$LANG.creditcardcardissuenum}">
                                </div>
                            </div>
                        {/if}
                        <div class="form-group new-card-container">
                            <div id="inputDescriptionContainer" class="col-md-6">
                                <div class="prepend-icon">
                                    <label for="inputDescription" class="field-icon">
                                        <i class="fas fa-pencil"></i>
                                    </label>
                                    <input type="text" class="field" id="inputDescription" name="ccdescription" autocomplete="off" value="" placeholder="{$LANG.paymentMethods.descriptionInput} {$LANG.paymentMethodsManage.optional}" />
                                </div>
                            </div>
                            {if $allowClientsToRemoveCards}
                                <div class="col-md-6" style="line-height: 32px;">
                                    <input type="hidden" name="nostore" value="1">
                                    <input type="checkbox" class="toggle-switch-success no-icheck" data-size="mini" checked="checked" name="nostore" id="inputNoStore" value="0" data-on-text="{lang key='yes'}" data-off-text="{lang key='no'}">
                                    <label for="inputNoStore" class="checkbox-inline no-padding">
                                        &nbsp;&nbsp;
                                        {$LANG.creditCardStore}
                                    </label>
                                </div>
                            {/if}
                        </div>
                    </div>
                </div>

                {if $shownotesfield}

                    <div class="sub-heading">
                        <span>{$LANG.orderForm.additionalNotes}</span>
                    </div>

                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group">
                                <textarea name="notes" class="field" rows="4" placeholder="{$LANG.ordernotesdescription}">{$orderNotes}</textarea>
                            </div>
                        </div>
                    </div>

                {/if}

                {if $showMarketingEmailOptIn}
                    <div class="marketing-email-optin">
                        <h4>{lang key='emailMarketing.joinOurMailingList'}</h4>
                        <p>{$marketingEmailOptInMessage}</p>
                        <input type="checkbox" name="marketingoptin" value="1"{if $marketingEmailOptIn} checked{/if} class="no-icheck toggle-switch-success" data-size="small" data-on-text="{lang key='yes'}" data-off-text="{lang key='no'}">
                    </div>
                {/if}

                <div class="text-center">
                    {if $accepttos}
                        <p>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="accepttos" id="accepttos" />
                                &nbsp;
                                {$LANG.ordertosagreement}
                                <a href="{$tosurl}" target="_blank">{$LANG.ordertos}</a>
                            </label>
                        </p>
                    {/if}
                    {if $captcha}
                        <div class="text-center margin-bottom">
                            {include file="$template/includes/captcha.tpl"}
                        </div>
                    {/if}

                    <button type="submit"
                            id="btnCompleteOrder"
                            class="btn btn-primary btn-lg disable-on-click spinner-on-click{if $captcha}{$captcha->getButtonClass($captchaForm)}{/if}"
                            {if $cartitems==0}disabled="disabled"{/if}
                            onclick="this.value='{$LANG.pleasewait}'">
                        {$LANG.completeorder}
                        &nbsp;<i class="fas fa-arrow-circle-right"></i>
                    </button>
                </div>
            </form>

            {if $servedOverSsl}
                <div class="alert alert-warning checkout-security-msg">
                    <i class="fas fa-lock"></i>
                    {$LANG.ordersecure} (<strong>{$ipaddress}</strong>) {$LANG.ordersecure2}
                    <div class="clearfix"></div>
                </div>
            {/if}

        </div>
    </div>
</div>

<script type="text/javascript" src="{$BASE_PATH_JS}/jquery.payment.js"></script>
