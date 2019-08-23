<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
$setting = App::getFromRequest("setting");
$isValueInRequest = App::isInRequest("value");
$value = App::getFromRequest("value");
if (!$setting) {
    $apiresults = array("result" => "error", "message" => "Parameter setting is required");
} else {
    $currentValue = WHMCS\Config\Setting::find($setting);
    if (is_null($currentValue)) {
        $apiresults = array("result" => "error", "message" => "Invalid name for parameter setting");
    } else {
        if (!$isValueInRequest) {
            $apiresults = array("result" => "error", "message" => "Parameter value is required");
        } else {
            $apiresults = array();
            $apiresults["result"] = "success";
            if ($value != $currentValue->value) {
                if (!function_exists("logAdminActivity")) {
                    require_once ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "adminfunctions.php";
                }
                WHMCS\Config\Setting::setValue($setting, $value);
                logAdminActivity("Settings Changed. " . $setting . " Updated: '" . $value . "'");
                $apiresults["value_changed"] = true;
            }
        }
    }
}

?>