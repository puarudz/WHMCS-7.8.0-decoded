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
$limitStart = (int) App::getFromRequest("limitstart");
$limitNum = (int) App::getFromRequest("limitnum");
if (!is_numeric($limitStart) || !$limitStart) {
    $limitStart = 0;
}
if (!is_numeric($limitnum) || !$limitNum) {
    $limitNum = 25;
}
$userid = (int) App::getFromRequest("userid");
$status = App::getFromRequest("status");
$ordering = strtolower(App::getFromRequest("orderby"));
$orderDirection = strtolower(App::getFromRequest("order"));
if (!$ordering) {
    $ordering = "default";
}
if (!$orderDirection || !in_array($orderDirection, array("asc", "desc"))) {
    $orderDirection = "asc";
}
$invoices = WHMCS\Database\Capsule::table("tblinvoices");
$where = array();
if ($userid) {
    $invoices->where("userid", $userid);
}
if ($status) {
    if ($status == "Overdue") {
        $invoices->where("tblinvoices.status", "Unpaid")->where("duedate", "<", WHMCS\Carbon::today()->toDateString());
    } else {
        $invoices->where("tblinvoices.status", $status);
    }
}
$totalResults = $invoices->count();
$invoices->join("tblclients", "tblclients.id", "=", "tblinvoices.userid");
switch ($ordering) {
    case "id":
    case "date":
    case "duedate":
    case "total":
    case "status":
        $invoices->orderBy("tblinvoices." . $ordering, $orderDirection);
        break;
    case "invoicenumber":
        $invoices->orderBy("invoicenum", $orderDirection)->orderBy("tblinvoices.id", $orderDirection);
        break;
    case "default":
    default:
        $invoices->orderBy("tblinvoices.status", "desc")->orderBy("duedate", $orderDirection);
}
$apiresults = array("result" => "success", "totalresults" => $totalResults, "startnumber" => $limitStart, "numreturned" => $invoices->count(), "invoices" => array());
$invoices->skip($limitStart)->take($limitNum);
$invoices->select(array("tblinvoices.id", "tblinvoices.userid", "tblclients.firstname", "tblclients.lastname", "tblclients.companyname", "tblinvoices.*"));
foreach ($invoices->get() as $invoice) {
    $currency = getCurrency($invoice->userid);
    $data = json_decode(json_encode($invoice), true);
    $data["currencycode"] = $currency["code"];
    $data["currencyprefix"] = $currency["prefix"];
    $data["currencysuffix"] = $currency["suffix"];
    $apiresults["invoices"]["invoice"][] = $data;
}
$responsetype = "xml";

?>