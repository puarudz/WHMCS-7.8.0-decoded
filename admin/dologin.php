<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
WHMCS\Session::rotate();
$username = trim($whmcs->getFromRequest("username"));
$password = trim($whmcs->getFromRequest("password"));
$redirectUri = $whmcs->getFromRequest("redirect");
$isBackupCodeRequest = (bool) $whmcs->getFromRequest("backupcode");
$backupCode = $whmcs->getFromRequest("code");
$requestedLanguage = $whmcs->getFromRequest("language");
$rememberMe = (bool) $whmcs->get_req_var("rememberme");
$auth = new WHMCS\Auth();
$twofa = new WHMCS\TwoFactorAuthentication();
$loginSuccess = false;
$twoFactorCompleted = false;
if ($twofa->isActiveAdmins() && WHMCS\Session::exists("2faverify")) {
    $twofa->setAdminID(WHMCS\Session::get("2faadminid"));
    if (WHMCS\Session::get("2fabackupcodenew")) {
        WHMCS\Session::delete("2fabackupcodenew");
        WHMCS\Session::delete("2faverify");
        WHMCS\Session::delete("2faadminid");
        WHMCS\Session::delete("2farememberme");
        $auth->redirectPostLogin($redirectUri);
    }
    if ($isBackupCodeRequest) {
        $success = $twofa->verifyBackupCode($backupCode);
    } else {
        $success = $twofa->moduleCall("verify");
    }
    if ($success) {
        $auth->getInfobyID(WHMCS\Session::get("2faadminid"));
        $rememberMe = (bool) WHMCS\Session::get("2farememberme");
        $loginSuccess = true;
        $twoFactorCompleted = true;
    } else {
        $auth->redirect($redirectUri, ($isBackupCodeRequest ? "backupcode=1&" : "") . "incorrect=1");
    }
} else {
    if (!$username || !$password) {
        $auth->failedLogin();
        $auth->redirect($redirectUri, "incorrect=1");
    }
    $captcha = new WHMCS\Utility\Captcha();
    if ($captcha->isEnabled() && $captcha->isEnabledForForm(WHMCS\Utility\Captcha::FORM_LOGIN)) {
        try {
            $validate = new WHMCS\Validate();
            $captcha->validateAppropriateCaptcha(WHMCS\Utility\Captcha::FORM_LOGIN, $validate);
            if ($validate->hasErrors()) {
                throw new Exception($validate->getErrors()[0]);
            }
        } catch (Exception $e) {
            WHMCS\Session::set("LoginCaptcha", $e->getMessage());
            $auth->redirect($redirectUri, "invalid=1");
        }
    }
    if ($auth->getInfobyUsername($username) && $auth->comparePassword($password)) {
        $loginSuccess = true;
    }
}
if ($loginSuccess) {
    if ($requestedLanguage) {
        WHMCS\Session::set("adminlang", $requestedLanguage);
    }
    try {
        if ($auth->isAdminPWHashSet()) {
            $hasher = new WHMCS\Security\Hash\Password();
            if ($hasher->needsRehash($auth->getAdminPWHash())) {
                $auth->generateNewPasswordHashAndStore($password);
            }
        } else {
            if ($auth->generateNewPasswordHashAndStore($password)) {
                $auth->generateNewPasswordHashAndStoreForApi(md5($password));
            }
        }
    } catch (Exception $e) {
        logActivity("Failed to validate password rehash: " . $e->getMessage());
    }
    if (!$twoFactorCompleted && $twofa->isActiveAdmins() && $auth->isTwoFactor()) {
        WHMCS\Session::set("2faverify", true);
        WHMCS\Session::set("2faadminid", $auth->getAdminID());
        WHMCS\Session::set("2farememberme", $rememberMe);
        $auth->redirect($redirectUri);
    }
    $auth->setSessionVars();
    if ($rememberMe) {
        $auth->setRememberMeCookie();
    } else {
        $auth->unsetRememberMeCookie();
    }
    $auth->processLogin();
    if ($isBackupCodeRequest) {
        WHMCS\Session::set("2fabackupcodenew", true);
        $auth->redirect($redirectUri, "newbackupcode=1");
    }
    if (WHMCS\Session::exists("2faverify")) {
        WHMCS\Session::delete("2faverify");
        WHMCS\Session::delete("2faadminid");
        WHMCS\Session::delete("2farememberme");
    }
    $auth->redirectPostLogin($redirectUri);
}
$auth->failedLogin();
$auth->redirect($redirectUri, "incorrect=1");

?>