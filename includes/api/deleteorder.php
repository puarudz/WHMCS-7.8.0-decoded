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
if (!function_exists("getRegistrarConfigOptions")) {
    require ROOTDIR . "/includes/registrarfunctions.php";
}
if (!function_exists("ModuleBuildParams")) {
    require ROOTDIR . "/includes/modulefunctions.php";
}
if (!function_exists("deleteOrder")) {
    require ROOTDIR . "/includes/orderfunctions.php";
}
$result = select_query("tblorders", "", array("id" => (int) $orderid));
$data = mysql_fetch_array($result);
$orderid = $data["id"];
if (!$orderid) {
    $apiresults = array("result" => "error", "message" => "Order ID not found");
} else {
    if (canOrderBeDeleted($orderid)) {
        deleteOrder($orderid);
        $apiresults = array("result" => "success");
    } else {
        $apiresults = array("result" => "error", "message" => "The order status must be in Cancelled or Fraud to be deleted");
    }
}

?>