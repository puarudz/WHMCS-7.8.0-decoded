<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function yubico_class_load()
{
    require_once __DIR__ . "/lib/Auth/Otp.php";
}
function yubico_config()
{
    return array("FriendlyName" => array("Type" => "System", "Value" => "Yubico"), "ShortDescription" => array("Type" => "System", "Value" => "Generate codes using a YubiKey hardware device."), "Description" => array("Type" => "System", "Value" => "Yubico is a hardware based solution which requires each of your users to use a YubiKey to authenticate and complete the login process."), "clientid" => array("FriendlyName" => "Client ID", "Type" => "text", "Size" => "10", "Description" => "Setup Your YubiKey if you haven't already @ https://upgrade.yubico.com/getapikey/"), "secretkey" => array("FriendlyName" => "Secret Key", "Type" => "text", "Size" => "50", "Description" => ""));
}
function yubico_activate($params)
{
    yubico_class_load();
    $apiID = (int) $params["settings"]["clientid"];
    $signatureKey = $params["settings"]["secretkey"];
    $verifyError = isset($params["verifyError"]) ? $params["verifyError"] : "";
    return "<p>To associate your Yubico Key with your account, simply click into the text field below and press the button on your Yubico Key USB Device.  The system will detect and validate your key upon submission to the next step.</p>\n" . ($verifyError ? "<div class=\"alert alert-danger\">" . $verifyError . "</div>" : "") . "\n<table>\n<tr><td width=\"100\">Yubico Key</td><td><input type=\"password\" name=\"yubicoprefix\" size=\"50\" id=\"yubicoprefix\" class=\"form-control\" placeholder=\"Click Here & Activate Yubico Key\" autofocus></td></tr>\n</table>\n<br />\n<p align=\"center\"><input type=\"submit\" value=\"Activate &raquo;\" class=\"btn btn-primary\" /></p>";
}
function yubico_activateverify($params)
{
    yubico_class_load();
    $apiID = (int) $params["settings"]["clientid"];
    $signatureKey = $params["settings"]["secretkey"];
    $otp = isset($params["post_vars"]["yubicoprefix"]) ? $params["post_vars"]["yubicoprefix"] : "";
    $invalid = false;
    $otp = trim(trim($otp, "\""));
    $optToLog = substr($otp, 0, 3) . str_repeat("*", strlen($otp) - 6) . substr($otp, -3);
    $token = new Yubico\Auth\Otp($apiID, $signatureKey);
    try {
        $verifySuccess = $token->verify($otp, true, false, NULL, 20);
        logModuleCall("yubico", "activate", array("otp" => $optToLog), $token->getLastResponse());
    } catch (Exception $e) {
        $verifySuccess = false;
    }
    if (!$verifySuccess) {
        throw new WHMCS\Exception("The YubiKey value entered could not be validated successfully. Please try again.");
    }
    $otp = substr($otp, 0, 12);
    return array("msg" => "Yubico Key Detected & Saved Successfully!", "settings" => array("yubicoprefix" => sha1($otp)));
}
function yubico_challenge($params)
{
    $output = "<form method=\"post\" action=\"dologin.php\">\n        <div align=\"center\">\n            <input type=\"password\" name=\"otp\" class=\"form-control\" placeholder=\"Yubico Key\" autofocus>\n        <br/>\n            <input id=\"login\" type=\"submit\" class=\"btn btn-primary btn-block btn-lg\" value=\"" . Lang::trans("loginbutton") . "\" />\n        </div>\n</form>";
    logModuleCall("yubico", "challenge", "", "");
    return $output;
}
function yubico_verify($params)
{
    yubico_class_load();
    $apiID = (int) $params["settings"]["clientid"];
    $signatureKey = $params["settings"]["secretkey"];
    $yubicoprefix = $params["user_settings"]["yubicoprefix"];
    $otp = $params["post_vars"]["otp"];
    $otp = trim(trim($otp, "\""));
    $optToLog = substr($otp, 0, 3) . str_repeat("*", strlen($otp) - 6) . substr($otp, -3);
    $token = new Yubico\Auth\Otp($apiID, $signatureKey);
    if ($token->verify($otp, true, false, NULL, 20)) {
        logModuleCall("yubico", "verify", array("otp" => $optToLog), $token->getLastResponse());
        if (sha1(substr($otp, 0, 12)) == $yubicoprefix) {
            return true;
        }
        return false;
    }
    logModuleCall("yubico", "verify", array("otp" => $optToLog), $token->getLastResponse());
    return false;
}

?>