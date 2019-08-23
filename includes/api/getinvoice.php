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
$invoiceid = (int) App::getFromRequest("invoiceid");
$result = select_query("tblinvoices", "", array("id" => $invoiceid));
$data = mysql_fetch_array($result);
$invoiceid = $data["id"];
if (!$invoiceid) {
    $apiresults = array("status" => "error", "message" => "Invoice ID Not Found");
} else {
    $userid = $data["userid"];
    $invoicenum = $data["invoicenum"];
    $date = $data["date"];
    $duedate = $data["duedate"];
    $datepaid = $data["datepaid"];
    $lastCaptureAttempt = $data["last_capture_attempt"];
    $subtotal = $data["subtotal"];
    $credit = $data["credit"];
    $tax = $data["tax"];
    $tax2 = $data["tax2"];
    $total = $data["total"];
    $taxrate = $data["taxrate"];
    $taxrate2 = $data["taxrate2"];
    $status = $data["status"];
    $paymentmethod = $data["paymentmethod"];
    $notes = $data["notes"];
    $result = select_query("tblaccounts", "SUM(amountin)-SUM(amountout)", array("invoiceid" => $invoiceid));
    $data = mysql_fetch_array($result);
    $amountpaid = $data[0];
    $balance = $total - $amountpaid;
    $balance = format_as_currency($balance);
    $gatewaytype = get_query_val("tblpaymentgateways", "value", array("gateway" => $paymentmethod, "setting" => "type"));
    $ccgateway = $gatewaytype == "CC" || $gatewaytype == "OfflineCC" ? true : false;
    $apiresults = array("result" => "success", "invoiceid" => $invoiceid, "invoicenum" => $invoicenum, "userid" => $userid, "date" => $date, "duedate" => $duedate, "datepaid" => $datepaid, "lastcaptureattempt" => $lastCaptureAttempt, "subtotal" => $subtotal, "credit" => $credit, "tax" => $tax, "tax2" => $tax2, "total" => $total, "balance" => $balance, "taxrate" => $taxrate, "taxrate2" => $taxrate2, "status" => $status, "paymentmethod" => $paymentmethod, "notes" => $notes, "ccgateway" => $ccgateway);
    $result = select_query("tblinvoiceitems", "", array("invoiceid" => $invoiceid));
    while ($data = mysql_fetch_array($result)) {
        $apiresults["items"]["item"][] = array("id" => $data["id"], "type" => $data["type"], "relid" => $data["relid"], "description" => $data["description"], "amount" => $data["amount"], "taxed" => $data["taxed"]);
    }
    $apiresults["transactions"] = array();
    $result = select_query("tblaccounts", "", array("invoiceid" => $invoiceid));
    while ($data = mysql_fetch_assoc($result)) {
        $apiresults["transactions"]["transaction"][] = $data;
    }
    if (empty($apiresults["transactions"])) {
        $apiresults["transactions"] = "";
    }
    $responsetype = "xml";
}

?>