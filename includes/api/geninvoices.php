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
if (!function_exists("createInvoices")) {
    require ROOTDIR . "/includes/processinvoices.php";
}
if (!function_exists("getClientsDetails")) {
    require ROOTDIR . "/includes/clientfunctions.php";
}
if (!function_exists("updateInvoiceTotal")) {
    require ROOTDIR . "/includes/invoicefunctions.php";
}
if (!function_exists("getGatewaysArray")) {
    require ROOTDIR . "/includes/gatewayfunctions.php";
}
if (!function_exists("getRegistrarConfigOptions")) {
    require ROOTDIR . "/includes/registrarfunctions.php";
}
if (!function_exists("ModuleBuildParams")) {
    require ROOTDIR . "/includes/modulefunctions.php";
}
if ($clientid) {
    $clientid = get_query_val("tblclients", "id", array("id" => $clientid));
    if (!$clientid) {
        $apiresults = array("result" => "error", "message" => "Client ID Not Found");
        return NULL;
    }
}
global $invoicecount;
$invoicecount = 0;
if (is_array($serviceids) || is_array($addonids) || is_array($domainids)) {
    $specificitems = array("products" => $serviceids, "addons" => $addonids, "domains" => $domainids);
    $invoiceid = createInvoices($clientid, $noemails, "", $specificitems);
} else {
    $invoiceid = createInvoices($clientid, $noemails);
}
$apiresults = array("result" => "success", "numcreated" => $invoicecount);
if ($clientid) {
    $apiresults["latestinvoiceid"] = $invoiceid;
}

?>