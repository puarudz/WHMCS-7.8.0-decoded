<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("CLIENTAREA", true);
require "init.php";
require "includes/gatewayfunctions.php";
require "includes/invoicefunctions.php";
require "includes/clientfunctions.php";
require "includes/adminfunctions.php";
$id = $invoiceid = (int) $whmcs->get_req_var("id");
$breadcrumbnav = "<a href=\"index.php\">" . $whmcs->get_lang("globalsystemname") . "</a> > <a href=\"clientarea.php\">" . $whmcs->get_lang("clientareatitle") . "</a> > <a href=\"clientarea.php?action=invoices\">" . $_LANG["invoices"] . "</a> > <a href=\"viewinvoice.php?id=" . $invoiceid . "\">" . $_LANG["invoicenumber"] . $invoiceid . "</a>";
$existingLanguage = NULL;
if (isset($_SESSION["adminid"]) && $whmcs->get_req_var("view_as_client")) {
    $userId = WHMCS\Invoice::getUserIdByInvoiceId($invoiceid);
    if ($userId) {
        $existingLanguage = getUsersLang($userId);
    }
}
initialiseClientArea($whmcs->get_lang("invoicestitle") . $invoiceid, "", "", "", $breadcrumbnav);
if (!isset($_SESSION["uid"]) && !isset($_SESSION["adminid"])) {
    $goto = "viewinvoice";
    require "login.php";
    exit;
}
$invoice = new WHMCS\Invoice();
$invoiceexists = true;
try {
    $invoice->setID($invoiceid);
} catch (Exception $e) {
    $invoiceexists = false;
}
$allowedaccess = isset($_SESSION["adminid"]) ? checkPermission("Manage Invoice", true) : $invoice->isAllowed();
if (!$invoiceexists || !$allowedaccess) {
    $smarty->assign("error", "on");
    $smarty->assign("invalidInvoiceIdRequested", true);
    outputClientArea("viewinvoice", true);
    exit;
}
$smarty->assign("invalidInvoiceIdRequested", false);
checkContactPermission("invoices");
if ($invoice->getData("status") == "Paid" && isset($_SESSION["orderdetails"]) && $_SESSION["orderdetails"]["InvoiceID"] == $invoiceid && !$_SESSION["orderdetails"]["paymentcomplete"]) {
    $_SESSION["orderdetails"]["paymentcomplete"] = true;
    redir("a=complete", "cart.php");
}
$gateway = $whmcs->get_req_var("gateway");
if ($gateway) {
    check_token();
    $gateways = new WHMCS\Gateways();
    $validgateways = $gateways->getAvailableGateways($invoiceid);
    if (array_key_exists($gateway, $validgateways)) {
        update_query("tblinvoices", array("paymentmethod" => $gateway), array("id" => $invoiceid));
        run_hook("InvoiceChangeGateway", array("invoiceid" => $invoiceid, "paymentmethod" => $gateway));
    }
    redir("id=" . $invoiceid);
}
$creditbal = get_query_val("tblclients", "credit", array("id" => $invoice->getData("userid")));
if ($invoice->getData("status") == "Unpaid" && 0 < $creditbal && !$invoice->isAddFundsInvoice()) {
    $balance = $invoice->getData("balance");
    $creditamount = $whmcs->get_req_var("creditamount");
    if ($whmcs->get_req_var("applycredit") && 0 < $creditamount) {
        check_token();
        if ($creditbal < $creditamount) {
            echo $_LANG["invoiceaddcreditovercredit"];
            exit;
        }
        if ($balance < $creditamount) {
            echo $_LANG["invoiceaddcreditoverbalance"];
            exit;
        }
        applyCredit($invoiceid, $invoice->getData("userid"), $creditamount);
        redir("id=" . $invoiceid);
    }
    $smartyvalues["manualapplycredit"] = true;
    $clientCurrency = getCurrency($invoice->getData("userid"));
    $smartyvalues["totalcredit"] = formatCurrency($creditbal, $clientCurrency["id"]) . generate_token("form");
    if (!$creditamount) {
        $creditamount = $balance <= $creditbal ? $balance : $creditbal;
    }
    $smartyvalues["creditamount"] = $creditamount;
}
$outputvars = $invoice->getOutput();
$smartyvalues = array_merge($smartyvalues, $outputvars);
$invoiceitems = $invoice->getLineItems();
$smartyvalues["invoiceitems"] = $invoiceitems;
$transactions = $invoice->getTransactions();
$smartyvalues["transactions"] = $transactions;
$paymentbutton = $invoice->getData("status") == "Unpaid" && 0 < $invoice->getData("balance") ? $invoice->getPaymentLink() : "";
$smartyvalues["paymentbutton"] = $paymentbutton;
$smartyvalues["paymentSuccess"] = (bool) $whmcs->get_req_var("paymentsuccess");
$smartyvalues["paymentFailed"] = (bool) $whmcs->get_req_var("paymentfailed");
$smartyvalues["pendingReview"] = (bool) $whmcs->get_req_var("pendingreview");
$smartyvalues["offlineReview"] = (bool) $whmcs->get_req_var("offlinepaid");
$smartyvalues["offlinepaid"] = (bool) $whmcs->get_req_var("offlinepaid");
$smartyvalues["paymentSuccessAwaitingNotification"] = $invoice->showPaymentSuccessAwaitingNotificationMsg($smartyvalues["paymentSuccess"]);
if ($whmcs->get_config("AllowCustomerChangeInvoiceGateway")) {
    $smartyvalues["allowchangegateway"] = true;
    $gateways = new WHMCS\Gateways();
    $availablegateways = $gateways->getAvailableGateways($invoiceid);
    $frm = new WHMCS\Form();
    $gatewaydropdown = generate_token("form") . $frm->dropdown("gateway", $availablegateways, $invoice->getData("paymentmodule"), "submit()");
    $smartyvalues["gatewaydropdown"] = $gatewaydropdown;
} else {
    $smartyvalues["allowchangegateway"] = false;
}
$smartyvalues["taxIdLabel"] = Lang::trans(WHMCS\Billing\Tax\Vat::getLabel());
outputClientArea("viewinvoice", true, array("ClientAreaPageViewInvoice"));
if ($existingLanguage) {
    swapLang($existingLanguage);
}

?>