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
if (!function_exists("captureCCPayment")) {
    require ROOTDIR . "/includes/ccfunctions.php";
}
if (!function_exists("getClientsDetails")) {
    require ROOTDIR . "/includes/clientfunctions.php";
}
if (!function_exists("processPaidInvoice")) {
    require ROOTDIR . "/includes/invoicefunctions.php";
}
$result = select_query("tblinvoices", "id", array("id" => $invoiceid, "status" => "Unpaid"));
$data = mysql_fetch_array($result);
$invoiceid = $data["id"];
if (!$invoiceid) {
    $apiresults = array("result" => "error", "message" => "Invoice Not Found or Not Unpaid");
} else {
    $result = captureCCPayment($invoiceid, $cvv);
    if ($result) {
        $apiresults = array("result" => "success");
    } else {
        $apiresults = array("result" => "error", "message" => "Payment Attempt Failed");
    }
}

?>