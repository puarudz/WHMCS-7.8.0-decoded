<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

class MyOauth
{
    private $tokendata = "";
    public $twoFactorAuthentication = NULL;
    public function setTokenData($token)
    {
        $this->tokendata = $token;
    }
    public function getData($username)
    {
        $twofa = $this->twoFactorAuthentication;
        $tokendata = $this->tokendata ? $this->tokendata : $twofa->getUserSetting("tokendata");
        return $tokendata;
    }
    public function putData($username, $data)
    {
        $twofa = $this->twoFactorAuthentication;
        $twofa->saveUserSettings(array("tokendata" => $data));
        return true;
    }
    public function getUsers()
    {
        return false;
    }
}
function totp_config()
{
    return array("FriendlyName" => array("Type" => "System", "Value" => "Time Based Tokens"), "ShortDescription" => array("Type" => "System", "Value" => "Get codes from an app like Google Authenticator or Duo."), "Description" => array("Type" => "System", "Value" => "TOTP requires that a user enter a 6 digit code that changes every 30 seconds to complete login. This works with mobile apps such as OATH Token and Google Authenticator."));
}
function totp_activate($params)
{
    $username = $params["user_info"]["username"];
    $tokendata = isset($params["user_settings"]["tokendata"]) ? $params["user_settings"]["tokendata"] : "";
    totp_loadgaclass();
    $gaotp = new MyOauth();
    $gaotp->twoFactorAuthentication = $params["twoFactorAuthentication"];
    $username = implode(":", array(WHMCS\Config\Setting::getValue("CompanyName"), $username));
    $username = App::sanitize("a-z", $username);
    $sessionKey = WHMCS\Session::get("totpKey");
    if ($sessionKey) {
        $sessionKey = decrypt($sessionKey);
    }
    $key = $gaotp->setUser($username, "TOTP", $sessionKey);
    $url = $gaotp->createUrl($username);
    WHMCS\Session::set("totpQrUrl", encrypt($url));
    WHMCS\Session::set("totpKey", encrypt($key));
    return "<h3 style=\"margin-top:0;\">Connect your app</h3>\n<p>Using an authenticator app like <a href=\"https://itunes.apple.com/gb/app/google-authenticator/id388497605\" target=\"_blank\">Google Authenticator</a> or <a href=\"https://itunes.apple.com/gb/app/duo-mobile/id422663827\" target=\"_blank\">Duo</a>, scan the QR code below. Having trouble scanning the code? Enter the code manually: <strong>" . $gaotp->helperhex2b32($gaotp->getKey($username)) . "</strong></p>\n<div align=\"center\">" . (function_exists("imagecreate") ? "<img src=\"" . routePath("account-security-two-factor-qr-code", "totp") . "\" style=\"border: 1px solid #ccc;border-radius: 4px;margin:15px 0;\"/>" : "<em>" . Lang::trans("twoipgdmissing") . "</em>") . "</div>\n<p>Enter the 6-digit code that the application generates to verify and complete setup.</p>\n" . ($params["verifyError"] ? "<div class=\"alert alert-danger\">" . $params["verifyError"] . "</div>" : "") . "\n<div class=\"row\">\n    <div class=\"col-sm-8\">\n        <input type=\"text\" name=\"verifykey\" maxlength=\"6\" style=\"font-size:18px;\" class=\"form-control input-lg\" placeholder=\"Enter authentication code\" autofocus>\n    </div>\n    <div class=\"col-sm-4\">\n        <input type=\"button\" value=\"Submit\" class=\"btn btn-primary btn-block btn-lg\" onclick=\"dialogSubmit()\" />\n    </div>\n</div>\n<br>";
}
function totp_activateverify($params)
{
    $username = $params["user_info"]["username"];
    $tokendata = isset($params["user_settings"]["tokendata"]) ? $params["user_settings"]["tokendata"] : "";
    totp_loadgaclass();
    $gaotp = new MyOauth();
    $gaotp->twoFactorAuthentication = $params["twoFactorAuthentication"];
    $username = implode(":", array(WHMCS\Config\Setting::getValue("CompanyName"), $username));
    $username = App::sanitize("a-z", $username);
    if (!$gaotp->authenticateUser($username, App::getFromRequest("verifykey"))) {
        throw new WHMCS\Exception(Lang::trans("twoipcodemissmatch"));
    }
    WHMCS\Session::delete("totpKey");
    return array("settings" => array("tokendata" => $tokendata));
}
function totp_challenge($params)
{
    return "<form method=\"post\" action=\"dologin.php\">\n            <div align=\"center\">\n            <input type=\"text\" name=\"key\" maxlength=\"6\" class=\"form-control input-lg\" autofocus>\n        <br/>\n            <input id=\"btnLogin\" type=\"submit\" class=\"btn btn-primary btn-block btn-lg\" value=\"" . Lang::trans("loginbutton") . "\">\n            </div>\n</form>";
}
function totp_get_used_otps()
{
    $usedotps = WHMCS\Config\Setting::getValue("TOTPUsedOTPs");
    $usedotps = $usedotps ? safe_unserialize($usedotps) : array();
    if (!is_array($usedotps)) {
        $usedotps = array();
    }
    return $usedotps;
}
function totp_verify($params)
{
    $username = $params["admin_info"]["username"];
    $tokendata = $params["admin_settings"]["tokendata"];
    $key = $params["post_vars"]["key"];
    totp_loadgaclass();
    $gaotp = new MyOauth();
    $gaotp->twoFactorAuthentication = $params["twoFactorAuthentication"];
    $gaotp->setTokenData($tokendata);
    $username = "WHMCS:" . $username;
    $usedotps = totp_get_used_otps();
    $hash = md5($username . $key);
    if (array_key_exists($hash, $usedotps)) {
        return false;
    }
    $ans = false;
    $ans = $gaotp->authenticateUser($username, $key);
    if ($ans) {
        $usedotps[$hash] = time();
        $expiretime = time() - 5 * 60;
        foreach ($usedotps as $k => $time) {
            if ($time < $expiretime) {
                unset($usedotps[$k]);
            } else {
                break;
            }
        }
        WHMCS\Config\Setting::setValue("TOTPUsedOTPs", safe_serialize($usedotps));
    }
    return $ans;
}
function totp_getqrcode()
{
    $totpQrUrl = WHMCS\Session::getAndDelete("totpQrUrl");
    if (empty($totpQrUrl)) {
        exit;
    }
    require_once ROOTDIR . "/modules/security/totp/phpqrcode.php";
    QRcode::png(decrypt($totpQrUrl), false, 6, 6);
}
function totp_loadgaclass()
{
    if (!class_exists("GoogleAuthenticator")) {
        include ROOTDIR . "/modules/security/totp/ga4php.php";
        class MyOauth extends GoogleAuthenticator
        {
            private $tokendata = "";
            public $twoFactorAuthentication = NULL;
            public function setTokenData($token)
            {
                $this->tokendata = $token;
            }
            public function getData($username)
            {
                $twofa = $this->twoFactorAuthentication;
                $tokendata = $this->tokendata ? $this->tokendata : $twofa->getUserSetting("tokendata");
                return $tokendata;
            }
            public function putData($username, $data)
            {
                $twofa = $this->twoFactorAuthentication;
                $twofa->saveUserSettings(array("tokendata" => $data));
                return true;
            }
            public function getUsers()
            {
                return false;
            }
        }
    }
}

?>