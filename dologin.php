<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("CLIENTAREA", true);
require "init.php";
include "includes/clientfunctions.php";
WHMCS\Session::rotate();
$username = trim($whmcs->get_req_var("username"));
$password = WHMCS\Input\Sanitize::decode(trim($whmcs->get_req_var("password")));
$hash = $whmcs->get_req_var("hash");
$goto = $whmcs->get_req_var("goto");
$rememberme = $whmcs->get_req_var("rememberme");
$gotourl = "";
if ($goto) {
    $goto = trim($goto);
    $gotourl = (new WHMCS\Auth())->cleanRedirectUri($goto);
} else {
    if (isset($_SESSION["loginurlredirect"])) {
        $gotourl = $_SESSION["loginurlredirect"];
        if (substr($gotourl, -15) == "&incorrect=true" || substr($gotourl, -15) == "?incorrect=true") {
            $gotourl = substr($gotourl, 0, strlen($gotourl) - 15);
        }
        if (substr($gotourl, -28) == "&incorrect=true&backupcode=1" || substr($gotourl, -28) == "?incorrect=true&backupcode=1" || substr($gotourl, -28) == "&backupcode=1&incorrect=true" || substr($gotourl, -28) == "?backupcode=1&incorrect=true") {
            $gotourl = substr($gotourl, 0, strlen($gotourl) - 28);
        }
        unset($_SESSION["loginurlredirect"]);
    }
}
if (!$gotourl) {
    $gotourl = "clientarea.php";
}
if ($whmcs->get_req_var("newbackupcode")) {
    if (isset($_SESSION["2fafromcart"])) {
        unset($_SESSION["2fafromcart"]);
        redir("a=checkout", "cart.php");
    }
    header("Location: " . $gotourl);
    exit;
}
$loginsuccess = false;
if (WHMCS\Authentication\Client::isInSecondFactorRequestState()) {
    $client = WHMCS\User\Client::findOrNew(WHMCS\Session::get("2faclientid"));
    $authentication = new WHMCS\Authentication\Client($client->email, "");
    if ($authentication->verifySecondFactor()) {
        $authentication->finalizeLogin();
        if ($whmcs->get_req_var("backupcode")) {
            WHMCS\Session::set("2fabackupcodenew", true);
            $gotourl = "clientarea.php?newbackupcode=true";
            header("Location: " . $gotourl);
            exit;
        }
        $loginsuccess = true;
    } else {
        if (strpos($gotourl, "?")) {
            $gotourl .= "&";
        } else {
            $gotourl .= "?";
        }
        $gotourl .= "incorrect=true";
        header("Location: " . $gotourl);
        exit;
    }
}
if (!$loginsuccess) {
    $authentication = new WHMCS\Authentication\Client($username, $password);
    try {
        if ($hash) {
            $email = $whmcs->get_req_var("email");
            $timestamp = $whmcs->get_req_var("timestamp");
            $autoauthkey = "";
            require "configuration.php";
            if ($autoauthkey) {
                $login_uid = $login_cid = "";
                if (!$email || !$timestamp) {
                    exit("Invalid or missing input");
                }
                if ($timestamp < time() - 15 * 60 || time() < $timestamp) {
                    exit("Link expired");
                }
                $hashverify = sha1($email . $timestamp . $autoauthkey);
                if ($hashverify == $hash) {
                    $result = select_query("tblclients", "id,password,language", array("email" => $email, "status" => array("sqltype" => "NEQ", "value" => "Closed")));
                    $data = mysql_fetch_array($result);
                    $login_uid = $data["id"];
                    $login_pwd = $data["password"];
                    $language = $data["language"];
                    if (!$login_uid) {
                        $result = select_query("tblcontacts", "id,userid,password", array("email" => $email, "subaccount" => "1", "password" => array("sqltype" => "NEQ", "value" => "")));
                        $data = mysql_fetch_array($result);
                        $login_cid = $data["id"];
                        $login_uid = $data["userid"];
                        $login_pwd = $data["password"];
                        $result = select_query("tblclients", "id,language", array("id" => $login_uid, "status" => array("sqltype" => "NEQ", "value" => "Closed")));
                        $data = mysql_fetch_array($result);
                        $login_uid = $data["id"];
                        $language = $data["language"];
                    }
                    if ($login_uid) {
                        $fullhost = gethostbyaddr($remote_ip);
                        update_query("tblclients", array("lastlogin" => "now()", "ip" => $remote_ip, "host" => $fullhost), array("id" => $login_uid));
                        $_SESSION["uid"] = $login_uid;
                        if ($login_cid) {
                            $_SESSION["cid"] = $login_cid;
                        }
                        $_SESSION["upw"] = WHMCS\Authentication\Client::generateClientLoginHash($login_uid, $login_cid, $login_pwd);
                        $_SESSION["tkval"] = genRandomVal();
                        if ($language) {
                            $_SESSION["Language"] = $language;
                        }
                        $hookParams = array("userid" => $login_uid);
                        $hookParams["contactid"] = $login_cid ? $login_cid : 0;
                        run_hook("ClientLogin", $hookParams);
                        $loginsuccess = true;
                        if ($goto) {
                            $trustedGotoUrl = (new WHMCS\Auth())->cleanRedirectUri(trim($goto), false);
                            if ($trustedGotoUrl) {
                                $gotourl = $trustedGotoUrl;
                            }
                        }
                    }
                }
            }
        }
        if (!$loginsuccess) {
            $captcha = new WHMCS\Utility\Captcha();
            if ($captcha->isEnabled() && !$authentication->isMasqueradingAdmin()) {
                $validate = new WHMCS\Validate();
                $captcha->validateAppropriateCaptcha(WHMCS\Utility\Captcha::FORM_LOGIN, $validate);
                if ($validate->hasErrors()) {
                    throw new RuntimeException(implode("\n", array($validate->getHTMLErrorOutput())));
                }
            }
            if ($authentication->verifyFirstFactor()) {
                if (!$authentication->needsSecondFactorToFinalize()) {
                    $authentication->finalizeLogin();
                    $loginsuccess = true;
                } else {
                    $authentication->prepareSecondFactor();
                    $loginsuccess = false;
                }
            }
        }
    } catch (RuntimeException $e) {
        WHMCS\Session::set("CaptchaError", $e->getMessage());
        header("Location: " . $gotourl);
        exit;
    } catch (Exception $e) {
        $safeUsername = WHMCS\Input\Sanitize::makeSafeForOutput($username);
        logActivity("'" . $safeUsername . "' attempted to log in, but there was an error: " . $e->getMessage());
        $loginsuccess = false;
    }
}
if (!WHMCS\Authentication\Client::isInSecondFactorRequestState() && !$loginsuccess) {
    if (strpos($gotourl, "?")) {
        $gotourl .= "&incorrect=true";
    } else {
        $gotourl .= "?incorrect=true";
    }
}
if ($loginsuccess) {
    $remoteAuth = DI::make("remoteAuth");
    $remoteAuth->linkRemoteAccounts();
    $clientId = WHMCS\Session::get("uid");
    try {
        WHMCS\User\Client::findOrFail($clientId)->migratePaymentDetailsIfRequired();
    } catch (Exception $e) {
        logActivity("Automatic client payment data migration failed on login: " . $e->getMessage() . " - User ID: " . $clientId);
    }
}
if ($loginsuccess && isset($_SESSION["2fafromcart"])) {
    unset($_SESSION["2fafromcart"]);
    redir("a=checkout", "cart.php");
}
header("Location: " . $gotourl);
exit;

?>