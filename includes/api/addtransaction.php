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
if (!function_exists("addTransaction")) {
    require ROOTDIR . "/includes/invoicefunctions.php";
}
$whmcs = App::self();
$allowDuplicateTransId = (bool) App::getFromRequest("allowduplicatetransid");
if ($invoiceid) {
    $result = select_query("tblinvoices", "id,userid", array("id" => (int) $_POST["invoiceid"]));
    $invoiceData = mysql_fetch_array($result);
    $invoiceid = $invoiceData["id"];
    if (!$invoiceid) {
        $apiresults = array("result" => "error", "message" => "Invoice ID Not Found");
        return NULL;
    }
    if (!$userid) {
        $userid = $invoiceData["userid"];
    }
}
if ($userid) {
    $result = select_query("tblclients", "id,currency", array("id" => $userid));
    $clientData = mysql_fetch_array($result);
    if (!$clientData["id"]) {
        $apiresults = array("result" => "error", "message" => "Client ID Not Found");
        return NULL;
    }
    if (!$currencyid) {
        $currencyid = $clientData["currency"];
    }
}
if ($userid && $invoiceid && $invoiceData["userid"] != $userid) {
    $apiresults = array("result" => "error", "message" => "User ID does not own the given Invoice ID");
} else {
    if ($currencyid) {
        if (!WHMCS\Billing\Currency::find($currencyid)) {
            $apiresults = array("result" => "error", "message" => "Currency ID Not Found");
            return NULL;
        }
        if ($userid && $currencyid != $clientData["currency"]) {
            $apiresults = array("result" => "error", "message" => "Currency ID does not match Client currency");
            return NULL;
        }
    } else {
        if (!$userid && !$invoiceid) {
            $apiresults = array("result" => "error", "message" => "A Currency ID is required for non-customer related transactions");
            return NULL;
        }
    }
    if (!$paymentmethod) {
        $apiresults = array("result" => "error", "message" => "Payment Method is required");
    } else {
        if ($transid && !$allowDuplicateTransId && !isUniqueTransactionID($transid, $paymentmethod)) {
            $apiresults = array("result" => "error", "message" => "Transaction ID must be Unique");
        } else {
            $date = $whmcs->get_req_var("date");
            if (empty($date)) {
                $date = fromMySQLDate(date("Y-m-d H:i:s"));
            }
            addTransaction($userid, $currencyid, $description, $amountin, $fees, $amountout, $paymentmethod, $transid, $invoiceid, $date, "", $rate);
            if ($userid && $credit && (!$invoiceid || $invoiceid == 0)) {
                if ($transid) {
                    $description .= " (Trans ID: " . $transid . ")";
                }
                insert_query("tblcredit", array("clientid" => $userid, "date" => toMySQLDate($date), "description" => $description, "amount" => $amountin));
                update_query("tblclients", array("credit" => "+=" . $amountin), array("id" => (int) $userid));
            }
            if (0 < $invoiceid) {
                $totalPaid = get_query_val("tblaccounts", "SUM(amountin)-SUM(amountout)", array("invoiceid" => $invoiceid));
                $invoiceData = get_query_vals("tblinvoices", "status, total", array("id" => $invoiceid));
                $balance = $invoiceData["total"] - $totalPaid;
                if ($balance <= 0 && $invoiceData["status"] == "Unpaid") {
                    processPaidInvoice($invoiceid, "", $date);
                }
            }
            $apiresults = array("result" => "success");
        }
    }
}

?>