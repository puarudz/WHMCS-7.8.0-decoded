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
if (!function_exists("acceptOrder")) {
    require ROOTDIR . "/includes/orderfunctions.php";
}
if (!function_exists("getRegistrarConfigOptions")) {
    require ROOTDIR . "/includes/registrarfunctions.php";
}
if (!function_exists("ModuleBuildParams")) {
    require ROOTDIR . "/includes/modulefunctions.php";
}
$orderid = App::getFromRequest("orderid");
$result = select_query("tblorders", "", array("id" => $orderid, "status" => "Pending"));
$data = mysql_fetch_array($result);
$orderid = $data["id"];
if (!$orderid) {
    $apiresults = array("result" => "error", "message" => "Order ID not found or Status not Pending");
} else {
    $ordervars = array();
    if (App::isInRequest("serverid")) {
        $ordervars["api"]["serverid"] = App::getFromRequest("serverid");
    }
    if (App::isInRequest("serviceusername")) {
        $ordervars["api"]["username"] = App::getFromRequest("serviceusername");
    }
    if (App::isInRequest("servicepassword")) {
        $ordervars["api"]["password"] = App::getFromRequest("servicepassword");
    }
    if (App::isInRequest("registrar")) {
        $ordervars["api"]["registrar"] = App::getFromRequest("registrar");
    }
    if (App::isInRequest("sendregistrar")) {
        $ordervars["api"]["sendregistrar"] = App::getFromRequest("sendregistrar");
    }
    if (App::isInRequest("autosetup")) {
        $ordervars["api"]["autosetup"] = App::getFromRequest("autosetup");
    }
    if (App::isInRequest("sendemail")) {
        $ordervars["api"]["sendemail"] = App::getFromRequest("sendemail");
    }
    acceptOrder($orderid, $ordervars);
    $apiresults = array("result" => "success");
}

?>