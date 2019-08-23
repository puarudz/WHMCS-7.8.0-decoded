<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
if (!$whmcs instanceof WHMCS\Init) {
    exit("Failed to initialize application.");
}
$licenseerror = strtolower($whmcs->get_req_var("status"));
if (empty($licenseerror)) {
    $licenseerror = strtolower($whmcs->get_req_var("licenseerror"));
}
$validLicenseErrorTypes = array("invalid", "pending", "suspended", "expired", "version", "noconnection", "error", "change");
$licenseCheckError = WHMCS\Session::getAndDelete("licenseCheckError");
if ($licenseCheckError) {
    $licenseerror = "error";
}
if (!in_array($licenseerror, $validLicenseErrorTypes)) {
    $licenseerror = $validLicenseErrorTypes[0];
}
$match = "";
$id = "";
$roleid = "";
$remote_ip = WHMCS\Utility\Environment\CurrentUser::getIP();
$performLicenseKeyUpdate = $whmcs->get_req_var("updatekey") === "true";
$licenseChangeResult = "";
if ($performLicenseKeyUpdate && defined("DEMO_MODE")) {
    $performLicenseKeyUpdate = false;
    $licenseChangeResult = "demoMode";
}
if ($performLicenseKeyUpdate) {
    $authAdmin = new WHMCS\Auth();
    if ($authAdmin->getInfobyUsername($username) && $authAdmin->comparePassword($password)) {
        $roleid = get_query_val("tbladmins", "roleid", array("id" => $authAdmin->getAdminID()));
        $result = select_query("tbladminperms", "COUNT(*)", array("roleid" => $roleid, "permid" => "64"));
        $data = mysql_fetch_array($result);
        $match = $data[0];
        $newlicensekey = trim($newlicensekey);
        $licenseKeyPattern = "/^[a-zA-Z0-9-]+\$/";
        if (!$newlicensekey) {
            $licenseChangeResult = "keyempty";
        } else {
            if (preg_match($licenseKeyPattern, $newlicensekey) !== 1) {
                $licenseChangeResult = "keyinvalid";
            } else {
                if (!$match) {
                    $licenseChangeResult = "nopermission";
                } else {
                    if (is_writable("../configuration.php")) {
                        $newConfigurationContent = getconfigurationfilecontentwithnewlicensekey($newlicensekey);
                        $fp = fopen("../configuration.php", "w");
                        fwrite($fp, $newConfigurationContent);
                        fclose($fp);
                        update_query("tblconfiguration", array("value" => ""), array("setting" => "License"));
                        redir("", "index.php");
                    }
                }
            }
        }
    } else {
        $authAdmin->failedLogin();
        $licenseChangeResult = "loginfailed";
    }
}
$changeError = "";
if ($licenseChangeResult) {
    switch ($licenseChangeResult) {
        case "loginfailed":
            $changeError = "Login Details Incorrect";
            break;
        case "keyinvalid":
            $changeError = "You did not enter a valid license key";
            break;
        case "keyempty":
            $changeError = "You did not enter a new license key";
            break;
        case "nopermission":
            $changeError = "You do not have permission to make this change";
            break;
        case "demoMode":
            $changeError = "Actions on this page are unavailable while in demo mode. Changes will not be saved.";
            break;
    }
}
if ($licenseerror == "change" && !is_writable("../configuration.php")) {
    $changeError = "The current permissions for configuration.php will prevent successful update of the license key. Please ensure that your configuration file is writable by the web server process.";
}
$templatevars["errorMsg"] = $changeError;
$templatevars["licenseError"] = $licenseerror;
$templatevars["licenseCheckError"] = $licenseCheckError;
$assetHelper = DI::make("asset");
$templatevars["WEB_ROOT"] = $assetHelper->getWebRoot();
$templatevars["BASE_PATH_CSS"] = $assetHelper->getCssPath();
$templatevars["BASE_PATH_JS"] = $assetHelper->getJsPath();
$templatevars["BASE_PATH_FONTS"] = $assetHelper->getFontsPath();
$templatevars["BASE_PATH_IMG"] = $assetHelper->getImgPath();
$smarty = new WHMCS\Smarty(true);
foreach ($templatevars as $key => $value) {
    $smarty->assign($key, $value);
}
echo $smarty->fetch("licenseerror.tpl");
function getConfigurationFileContentWithNewLicenseKey($key)
{
    $newline = "\n";
    $attachments_dir = "";
    $downloads_dir = "";
    $customadminpath = "";
    $db_host = "";
    $db_username = "";
    $db_password = "";
    $db_name = "";
    $cc_encryption_hash = "";
    $templates_compiledir = "";
    $mysql_charset = "";
    $api_access_key = "";
    $autoauthkey = "";
    $display_errors = false;
    $error_reporting = 0;
    include ROOTDIR . "/configuration.php";
    $output = sprintf("<?php%s" . "\$license = '%s';%s" . "\$db_host = '%s';%s" . "\$db_username = '%s';%s" . "\$db_password = '%s';%s" . "\$db_name = '%s';%s" . "\$cc_encryption_hash = '%s';%s" . "\$templates_compiledir = '%s';%s", $newline, $key, $newline, $db_host, $newline, $db_username, $newline, $db_password, $newline, $db_name, $newline, $cc_encryption_hash, $newline, $templates_compiledir, $newline);
    if ($mysql_charset) {
        $output .= sprintf("\$mysql_charset = '%s';%s", $mysql_charset, $newline);
    }
    if ($attachments_dir) {
        $output .= sprintf("\$attachments_dir = '%s';%s", $attachments_dir, $newline);
    }
    if ($downloads_dir) {
        $output .= sprintf("\$downloads_dir = '%s';%s", $downloads_dir, $newline);
    }
    if ($customadminpath) {
        $output .= sprintf("\$customadminpath = '%s';%s", $customadminpath, $newline);
    }
    if ($api_access_key) {
        $output .= sprintf("\$api_access_key = '%s';%s", $api_access_key, $newline);
    }
    if ($autoauthkey) {
        $output .= sprintf("\$autoauthkey = '%s';%s", $autoauthkey, $newline);
    }
    if ($display_errors) {
        $output .= sprintf("\$display_errors = %s;%s", "true", $newline);
    }
    if ($error_reporting) {
        $output .= sprintf("\$error_reporting = %s;%s", $error_reporting, $newline);
    }
    return $output;
}

?>