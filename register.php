<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("CLIENTAREA", true);
require "init.php";
require "includes/clientfunctions.php";
require "includes/customfieldfunctions.php";
if (isset($_SESSION["uid"])) {
    redir("", "clientarea.php");
}
$captcha = new WHMCS\Utility\Captcha();
$securityquestions = getSecurityQuestions();
$firstname = $whmcs->get_req_var("firstname");
$lastname = $whmcs->get_req_var("lastname");
$companyname = $whmcs->get_req_var("companyname");
$email = $whmcs->get_req_var("email");
$address1 = $whmcs->get_req_var("address1");
$address2 = $whmcs->get_req_var("address2");
$city = $whmcs->get_req_var("city");
$state = $whmcs->get_req_var("state");
$postcode = $whmcs->get_req_var("postcode");
$country = $whmcs->get_req_var("country");
$phonenumber = $whmcs->get_req_var("phonenumber");
$password = $whmcs->get_req_var("password");
$securityqid = $whmcs->get_req_var("securityqid");
$securityqans = $whmcs->get_req_var("securityqans");
$customfield = $whmcs->get_req_var("customfield");
$marketingoptin = $whmcs->get_req_var("marketingoptin");
$taxId = App::getFromRequest(WHMCS\Billing\Tax\Vat::getFieldName());
$remoteAuth = DI::make("remoteAuth");
$remoteAuthData = $remoteAuth->getRegistrationFormData();
if (!App::isInRequest("email") && isset($remoteAuthData["email"])) {
    $email = $remoteAuthData["email"];
}
if (!App::isInRequest("firstname") && isset($remoteAuthData["firstname"])) {
    $firstname = $remoteAuthData["firstname"];
}
if (!App::isInRequest("lastname") && isset($remoteAuthData["lastname"])) {
    $lastname = $remoteAuthData["lastname"];
}
$errormessage = "";
if ($whmcs->get_req_var("register")) {
    check_token();
    $errormessage = checkDetailsareValid("", true);
    if (!$errormessage) {
        if ($remoteAuth->isPrelinkPerformed()) {
            $password = $remoteAuth->generateRandomPassword();
        }
        $phonenumber = App::formatPostedPhoneNumber();
        $userid = addClient($firstname, $lastname, $companyname, $email, $address1, $address2, $city, $state, $postcode, $country, $phonenumber, $password, $securityqid, $securityqans, true, array("tax_id" => $taxId), "", false, $marketingoptin);
        $remoteAuth->linkRemoteAccounts();
        run_hook("ClientAreaRegister", array("userid" => $userid));
        redir("", "clientarea.php");
    }
}
$pagetitle = Lang::trans("clientregistertitle");
$breadcrumbnav = "<a href=\"index.php\">" . Lang::trans("globalsystemname") . "</a> > <a href=\"register.php\">" . Lang::trans("clientregistertitle") . "</a>";
$pageicon = "images/order_big.gif";
$displayTitle = Lang::trans("clientregistertitle");
$tagline = Lang::trans("registerintro");
initialiseClientArea($pagetitle, $displayTitle, $tagline, $pageicon, $breadcrumbnav);
$templatefile = "clientregister";
$smarty->assign("registrationDisabled", (bool) (!WHMCS\Config\Setting::getValue("AllowClientRegister")));
$smarty->assign("noregistration", !WHMCS\Config\Setting::getValue("AllowClientRegister") ? true : false);
$countries = new WHMCS\Utility\Country();
$countriesdropdown = getCountriesDropDown($country);
$smarty->assign("defaultCountry", WHMCS\Config\Setting::getValue("DefaultCountry"));
$smarty->assign("errormessage", $errormessage);
$smarty->assign("clientfirstname", $firstname);
$smarty->assign("clientlastname", $lastname);
$smarty->assign("clientcompanyname", $companyname);
$smarty->assign("clientemail", $email);
$smarty->assign("clientaddress1", $address1);
$smarty->assign("clientaddress2", $address2);
$smarty->assign("clientcity", $city);
$smarty->assign("clientstate", $state);
$smarty->assign("clientpostcode", $postcode);
$smarty->assign("clientcountry", $country);
$smarty->assign("clientcountriesdropdown", $countriesdropdown);
$smarty->assign("clientcountries", $countries->getCountryNameArray());
$smarty->assign("clientphonenumber", $phonenumber);
$smarty->assign("securityquestions", $securityquestions);
$customfields = getCustomFields("client", "", "", "", "on", $customfield);
$smarty->assign("customfields", $customfields);
$smarty->assign("captcha", $captcha);
$smarty->assign("captchaForm", WHMCS\Utility\Captcha::FORM_REGISTRATION);
$smarty->assign("recaptchahtml", clientAreaReCaptchaHTML());
$smarty->assign("capatacha", $captcha);
$smarty->assign("recapatchahtml", clientAreaReCaptchaHTML());
$smarty->assign("accepttos", WHMCS\Config\Setting::getValue("EnableTOSAccept"));
$smarty->assign("tosurl", WHMCS\Config\Setting::getValue("TermsOfService"));
$smarty->assign("uneditablefields", explode(",", WHMCS\Config\Setting::getValue("ClientsProfileUneditableFields")));
$optionalFields = $whmcs->get_config("ClientsProfileOptionalFields");
$smarty->assign("optionalFields", explode(",", $optionalFields));
$smarty->assign("phoneNumberInputStyle", (int) WHMCS\Config\Setting::getValue("PhoneNumberDropdown"));
$smarty->assign("showMarketingEmailOptIn", WHMCS\Config\Setting::getValue("AllowClientsEmailOptOut"));
$smarty->assign("marketingEmailOptInMessage", Lang::trans("emailMarketing.optInMessage") != "emailMarketing.optInMessage" ? Lang::trans("emailMarketing.optInMessage") : WHMCS\Config\Setting::getValue("EmailMarketingOptInMessage"));
$smarty->assign("marketingEmailOptIn", App::isInRequest("marketingoptin") ? (bool) App::getFromRequest("marketingoptin") : (bool) (!WHMCS\Config\Setting::getValue("EmailMarketingRequireOptIn")));
$remoteAuthData = (new WHMCS\Authentication\Remote\Management\Client\ViewHelper())->getTemplateData(WHMCS\Authentication\Remote\Providers\AbstractRemoteAuthProvider::HTML_TARGET_REGISTER);
$smarty->assign($remoteAuthData);
$smarty->assign("showTaxIdField", WHMCS\Billing\Tax\Vat::isUsingNativeField());
Menu::addContext("securityQuestions", WHMCS\User\Client\SecurityQuestion::all());
Menu::primarySidebar("clientRegistration");
Menu::secondarySidebar("clientRegistration");
outputClientArea($templatefile, false, array("ClientAreaPageRegister"));

?>