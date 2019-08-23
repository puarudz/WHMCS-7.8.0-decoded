<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

use WHMCS\Billing\Invoice;
use WHMCS\Payment\PayMethod\Adapter\BankAccount;
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
if (!function_exists('getClientDefaultBankDetails')) {
    require ROOTDIR . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'clientfunctions.php';
}
$reportdata["title"] = "Direct Debit Processing";
$reportdata["description"] = "This report displays all Unpaid invoices assigned to the Direct Debit payment method and the associated bank account details stored for their owners ready for processing";
$reportdata["tableheadings"] = array("Invoice ID", "Client Name", "Invoice Date", "Due Date", "Subtotal", "Tax", "Credit", "Total", "Bank Name", "Bank Account Type", "Bank Code", "Bank Account Number");
$defaultBankDetailsPerUser = [];
$query = "SELECT tblinvoices.*,tblclients.firstname,tblclients.lastname FROM tblinvoices INNER JOIN tblclients ON tblclients.id=tblinvoices.userid WHERE tblinvoices.paymentmethod='directdebit' AND tblinvoices.status='Unpaid' ORDER BY duedate ASC";
$result = full_query($query);
while ($data = mysql_fetch_array($result)) {
    $id = $data["id"];
    $userid = $data["userid"];
    $client = $data["firstname"] . " " . $data["lastname"];
    $date = $data["date"];
    $duedate = $data["duedate"];
    $subtotal = $data["subtotal"];
    $credit = $data["credit"];
    $tax = $data["tax"] + $data["tax2"];
    $total = $data["total"];
    $invoice = Invoice::find($id);
    if ($invoice && $invoice->payMethod && $invoice->payMethod->payment->isBankAccount()) {
        /** @var BankAccount $payment */
        $payment = $invoice->payMethod->payment;
        $bankDetails["bankname"] = $payment->getBankName();
        $bankDetails["banktype"] = $payment->getAccountType();
        $bankDetails["bankcode"] = $payment->getRoutingNumber();
        $bankDetails["bankacct"] = $payment->getAccountNumber();
    } else {
        if (!isset($defaultBankDetailsPerUser[$userid])) {
            $defaultBankDetailsPerUser[$userid] = getClientDefaultBankDetails($userid);
        }
        $bankDetails = $defaultBankDetailsPerUser[$userid];
    }
    $bankname = $bankDetails["bankname"];
    $banktype = $bankDetails["banktype"];
    $bankcode = $bankDetails["bankcode"];
    $bankacct = $bankDetails["bankacct"];
    $currency = getCurrency($userid);
    $date = fromMySQLDate($date);
    $duedate = fromMySQLDate($duedate);
    $subtotal = formatCurrency($subtotal);
    $credit = formatCurrency($credit);
    $tax = formatCurrency($tax);
    $total = formatCurrency($total);
    $reportdata["tablevalues"][] = array('<a href="invoices.php?action=edit&id=' . $id . '">' . $id . '</a>', $client, $date, $duedate, $subtotal, $tax, $credit, $total, $bankname, $banktype, $bankcode, $bankacct);
}
$reportdata["footertext"] = "";

?>