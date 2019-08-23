<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("WHMCS")) {
    header("Location: clientarea.php");
    exit;
}
$smartyvalues["showingLoginPage"] = true;
$_SESSION["loginurlredirect"] = html_entity_decode($_SERVER["REQUEST_URI"]);
if (WHMCS\Session::get("2faverifyc")) {
    $templatefile = "logintwofa";
    if (WHMCS\Session::get("2fabackupcodenew")) {
        $smartyvalues["newbackupcode"] = true;
    } else {
        if ($whmcs->get_req_var("incorrect")) {
            $smartyvalues["incorrect"] = true;
        }
    }
    $twofa = new WHMCS\TwoFactorAuthentication();
    if ($twofa->setClientID(WHMCS\Session::get("2faclientid"))) {
        if (!$twofa->isActiveClients() || !$twofa->isEnabled()) {
            WHMCS\Session::destroy();
            redir();
        }
        if ($whmcs->get_req_var("backupcode")) {
            $smartyvalues["backupcode"] = true;
        } else {
            $challenge = $twofa->moduleCall("challenge");
            if ($challenge) {
                $smartyvalues["challenge"] = $challenge;
            } else {
                $smartyvalues["error"] = "Bad 2 Factor Auth Module. Please contact support.";
            }
        }
    } else {
        $smartyvalues["error"] = "An error occurred. Please try again.";
    }
} else {
    $remoteAuthData = (new WHMCS\Authentication\Remote\Management\Client\ViewHelper())->getTemplateData(WHMCS\Authentication\Remote\Providers\AbstractRemoteAuthProvider::HTML_TARGET_LOGIN);
    foreach ($remoteAuthData as $key => $value) {
        $smartyvalues[$key] = $value;
    }
    $templatefile = "login";
    $smartyvalues["loginpage"] = true;
    $smartyvalues["formaction"] = "dologin.php";
    $smartyvalues["incorrect"] = (bool) $whmcs->get_req_var("incorrect");
    $smartyvalues["ssoredirect"] = (bool) $whmcs->get_req_var("ssoredirect");
    $captcha = new WHMCS\Utility\Captcha();
    $smartyvalues["captcha"] = $captcha;
    $smartyvalues["captchaForm"] = WHMCS\Utility\Captcha::FORM_LOGIN;
    $smartyvalues["invalid"] = WHMCS\Session::getAndDelete("CaptchaError");
}
outputClientArea($templatefile, false, array("ClientAreaPageLogin"));
exit;

?>