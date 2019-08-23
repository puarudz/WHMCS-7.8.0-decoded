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
function offlinecc_config()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "Offline Credit Card"), "RemoteStorage" => true);
    return $configarray;
}
function offlinecc_capture($params)
{
    return false;
}

?>