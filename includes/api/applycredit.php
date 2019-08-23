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
if (!function_exists("applyCredit")) {
    require ROOTDIR . "/includes/invoicefunctions.php";
}
$data = get_query_vals("tblinvoices", "id,userid,credit,total,status", array("id" => $invoiceid));
$invoiceid = $data["id"];
if (!$invoiceid) {
    $apiresults = array("result" => "error", "message" => "Invoice ID Not Found");
} else {
    $userid = $data["userid"];
    $credit = $data["credit"];
    $total = $data["total"];
    $status = $data["status"];
    $amountpaid = get_query_val("tblaccounts", "SUM(amountin)-SUM(amountout)", array("invoiceid" => $invoiceid));
    $balance = round($total - $amountpaid, 2);
    $amount = $amount == "full" ? $balance : round($amount, 2);
    $totalcredit = get_query_val("tblclients", "credit", array("id" => $userid));
    if ($status != "Unpaid") {
        $apiresults = array("result" => "error", "message" => "Invoice Not in Unpaid Status");
    } else {
        if ($totalcredit < $amount) {
            $apiresults = array("result" => "error", "message" => "Amount exceeds customer credit balance");
        } else {
            if ($balance < $amount) {
                $apiresults = array("result" => "error", "message" => "Amount Exceeds Invoice Balance");
            } else {
                if ($amount == "0.00") {
                    $apiresults = array("result" => "error", "message" => "Credit Amount to apply must be greater than zero");
                } else {
                    $appliedamount = min($amount, $totalcredit);
                    applyCredit($invoiceid, $userid, $appliedamount, $noemail);
                    $apiresults = array("result" => "success", "invoiceid" => $invoiceid, "amount" => $appliedamount, "invoicepaid" => get_query_val("tblinvoices", "status", array("id" => $invoiceid)) == "Paid" ? "true" : "false");
                }
            }
        }
    }
}

?>