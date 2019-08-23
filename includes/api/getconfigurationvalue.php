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
$setting = App::get_req_var("setting");
if (!$setting) {
    $apiresults = array("result" => "error", "message" => "Parameter setting is required");
} else {
    $currentValue = WHMCS\Config\Setting::find($setting);
    if (is_null($currentValue)) {
        $apiresults = array("result" => "error", "message" => "Invalid name for parameter setting");
    } else {
        $apiresults = array("result" => "success", "setting" => $setting, "value" => $currentValue->value);
    }
}

?>