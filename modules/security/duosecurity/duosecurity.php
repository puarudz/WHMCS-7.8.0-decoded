<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function duosecurity_config()
{
    $twofa = new WHMCS\TwoFactorAuthentication();
    $integrationKey = decrypt($twofa->getModuleSetting("duosecurity", "integrationKey"));
    $secretKey = decrypt($twofa->getModuleSetting("duosecurity", "secretKey"));
    $apiHostname = $twofa->getModuleSetting("duosecurity", "apiHostname");
    $extraDescription = "";
    if (!$integrationKey && !$secretKey && !$apiHostname) {
        $extraDescription .= "<div class=\"alert alert-success\" style=\"margin:10px 0;padding:8px 15px;\">New to Duo Security? " . "<a href=\"http://go.whmcs.com/918/duo-security-signup\" target=\"_blank\" class=\"alert-link\">" . "Click here to create an account</a>" . "</div>";
    }
    return array("FriendlyName" => array("Type" => "System", "Value" => "Duo Security"), "ShortDescription" => array("Type" => "System", "Value" => "Get codes via Duo Push, SMS or Phone Callback."), "Description" => array("Type" => "System", "Value" => "Duo Security enables your users to secure their logins using their smartphones. " . "Authentication options include push notifications, passcodes, text messages and/or phone calls." . $extraDescription), "integrationKey" => array("FriendlyName" => "Integration Key", "Type" => "password", "Size" => "25"), "secretKey" => array("FriendlyName" => "Secret Key", "Type" => "password", "Size" => "45"), "apiHostname" => array("FriendlyName" => "API Hostname", "Type" => "text", "Size" => "45"));
}
function duosecurity_activate(array $params)
{
}
function duosecurity_activateverify(array $params)
{
    return array("msg" => "You will be asked to configure your Duo Security Two-Factor Authentication the next time you login.");
}
function duosecurity_challenge(array $params)
{
    $whmcs = App::self();
    $appsecretkey = sha1("Duo" . $whmcs->get_hash());
    $username = $params["user_info"]["username"];
    $email = $params["user_info"]["email"];
    $inAdmin = defined("ADMINAREA");
    $integrationkey = !empty($params["settings"]["integrationKey"]) ? decrypt($params["settings"]["integrationKey"]) : "";
    $secretkey = !empty($params["settings"]["secretKey"]) ? decrypt($params["settings"]["secretKey"]) : "";
    $apihostname = $params["settings"]["apiHostname"];
    $uid = $username . ":" . $email . ":" . $whmcs->get_license_key();
    $sig_request = Duo\Web::signRequest($integrationkey, $secretkey, $appsecretkey, $uid);
    $output = "There is an error with the DuoSecurity module configuration.";
    if (!$integrationkey || !$secretkey || !$apihostname) {
        logActivity(($inAdmin ? "Admin" : "Client") . " Duo Security Login Failed: " . $sig_request);
        $sig_request = NULL;
        $output .= "<br>Please login with your backup code" . ($inAdmin ? " and check the DuoSecurity configuration." : ".");
    }
    if ($sig_request != NULL) {
        $output = "<script src=\"" . ($inAdmin ? "../" : "") . "modules/security/duosecurity/Duo-Web-v2.min.js\"></script>\n<script>\n  Duo.init({\n    \"host\": \"" . $apihostname . "\",\n    \"sig_request\": \"" . $sig_request . "\",\n    \"post_action\": \"dologin.php\"\n  });\n</script>\n<iframe id=\"duo_iframe\" width=\"100%\" height=\"500\" frameborder=\"0\"></iframe>";
    }
    return $output;
}
function duosecurity_verify(array $params)
{
    $whmcs = App::self();
    $appsecretkey = sha1("Duo" . $whmcs->get_hash());
    $integrationkey = !empty($params["settings"]["integrationKey"]) ? decrypt($params["settings"]["integrationKey"]) : "";
    $secretkey = !empty($params["settings"]["secretKey"]) ? decrypt($params["settings"]["secretKey"]) : "";
    if (Duo\Web::verifyResponse($integrationkey, $secretkey, $appsecretkey, $_POST["sig_response"])) {
        return true;
    }
    return false;
}

?>