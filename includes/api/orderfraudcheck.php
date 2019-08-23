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
if (!function_exists("getClientsDetails")) {
    require ROOTDIR . "/includes/clientfunctions.php";
}
$orderId = App::getFromRequest("orderid");
$order = new WHMCS\Order();
$order->setID($orderId);
$fraudModule = $order->getActiveFraudModule();
$orderId = $order->getData("id");
if (!$orderId) {
    $apiresults = array("result" => "error", "message" => "Order ID Not Found");
    return false;
}
if (!$fraudModule) {
    $apiresults = array("result" => "error", "message" => "No Active Fraud Module");
    return false;
}
$userId = $order->getData("userid");
$ipAddress = $order->getData("ipaddress");
$invoiceId = $order->getData("invoiceid");
if (App::isInRequest("ipaddress")) {
    $ipAddress = App::getFromRequest("ipaddress");
}
$results = $fraudResults = "";
$fraud = new WHMCS\Module\Fraud();
if ($fraud->load($fraudModule)) {
    $results = $fraud->doFraudCheck($orderId, $userId, $ipAddress);
    $fraudResults = $fraud->processResultsForDisplay($orderId, $results["fraudoutput"]);
}
if (!is_array($results)) {
    $results = array();
}
$error = $results["error"];
if ($results["userinput"]) {
    $status = "User Input Required";
} else {
    if ($results["error"]) {
        $status = "Fail";
        WHMCS\Database\Capsule::table("tblorders")->where("id", "=", $orderId)->update(array("status" => "Fraud"));
        WHMCS\Database\Capsule::table("tblhosting")->where("orderid", "=", $orderId)->where("domainstatus", "=", "Pending")->update(array("domainstatus" => "Fraud"));
        WHMCS\Database\Capsule::table("tblhostingaddons")->where("orderid", "=", $orderId)->where("status", "=", "Pending")->update(array("status" => "Fraud"));
        WHMCS\Database\Capsule::table("tbldomains")->where("orderid", "=", $orderId)->where("status", "=", "Pending")->update(array("status" => "Fraud"));
        WHMCS\Database\Capsule::table("tblinvoices")->where("id", "=", $invoiceId)->where("status", "=", "Unpaid")->update(array("status" => "Cancelled"));
    } else {
        $status = "Pass";
        WHMCS\Database\Capsule::table("tblorders")->where("id", "=", $orderId)->update(array("status" => "Pending"));
        WHMCS\Database\Capsule::table("tblhosting")->where("orderid", "=", $orderId)->where("domainstatus", "=", "Fraud")->update(array("domainstatus" => "Pending"));
        WHMCS\Database\Capsule::table("tblhostingaddons")->where("orderid", "=", $orderId)->where("status", "=", "Fraud")->update(array("status" => "Pending"));
        WHMCS\Database\Capsule::table("tbldomains")->where("orderid", "=", $orderId)->where("status", "=", "Fraud")->update(array("status" => "Pending"));
        WHMCS\Database\Capsule::table("tblinvoices")->where("id", "=", $invoiceId)->where("status", "=", "Cancelled")->update(array("status" => "Unpaid"));
    }
}
$apiresults = array("result" => "success", "status" => $status, "module" => $fraudModule, "results" => safe_serialize($fraudResults));
$responsetype = "xml";

?>