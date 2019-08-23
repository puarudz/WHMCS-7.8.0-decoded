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
if (!function_exists("updateInvoiceTotal")) {
    require ROOTDIR . "/includes/invoicefunctions.php";
}
$publish = App::get_req_var("publish");
$publishAndSendEmail = App::get_req_var("publishandsendemail");
$invoiceId = (int) App::getFromRequest("invoiceid");
$itemDescription = App::getFromRequest("itemdescription");
$itemAmount = App::getFromRequest("itemamount");
$itemTaxed = App::getFromRequest("itemtaxed");
$newItemDescription = App::getFromRequest("newitemdescription");
$newItemAmount = App::getFromRequest("newitemamount");
$newItemTaxed = App::getFromRequest("newitemtaxed");
$deleteLineIds = App::getFromRequest("deletelineids");
$status = App::getFromRequest("status");
try {
    $invoice = WHMCS\Billing\Invoice::findOrFail($invoiceId);
    $userId = $invoice->clientId;
} catch (Exception $e) {
    $apiresults = array("result" => "error", "message" => "Invoice ID Not Found");
    return NULL;
}
if (($publish || $publishAndSendEmail) && $invoice->status != "Draft") {
    $apiresults = array("result" => "error", "message" => "Invoice must be in Draft status to be published");
} else {
    if ($status && !in_array($status, WHMCS\Invoices::getInvoiceStatusValues())) {
        $apiresults = array("result" => "error", "message" => "Invalid status " . $status);
    } else {
        if ($itemDescription) {
            foreach ($itemDescription as $lineid => $description) {
                if (!array_key_exists($lineid, $itemAmount) || !array_key_exists($lineid, $itemTaxed)) {
                    $apiresults = array("result" => "error", "message" => "Missing Variables: itemdescription, itemamount" . " and itemtaxed are required for each item being changed");
                    return NULL;
                }
                $amount = $itemAmount[$lineid];
                $taxed = $itemTaxed[$lineid];
                $update = array("userid" => $userId, "description" => $description, "amount" => $amount, "taxed" => $taxed, "invoiceid" => $invoiceId);
                WHMCS\Database\Capsule::table("tblinvoiceitems")->where("id", "=", $lineid)->update($update);
            }
        }
        if ($newItemDescription) {
            $inserts = array();
            foreach ($newItemDescription as $k => $v) {
                $description = $v;
                $amount = $newItemAmount[$k];
                $taxed = $newItemTaxed[$k];
                $insert = array("invoiceid" => $invoiceId, "userid" => $userId, "description" => $description, "amount" => $amount, "taxed" => $taxed);
                $inserts[] = $insert;
            }
            if (0 < count($inserts)) {
                WHMCS\Database\Capsule::table("tblinvoiceitems")->insert($inserts);
            }
        }
        if ($deleteLineIds) {
            WHMCS\Database\Capsule::table("tblinvoiceitems")->where("invoiceid", "=", $invoiceId)->whereIn("id", $deleteLineIds)->delete();
        }
        $invoiceNum = App::getFromRequest("invoicenum");
        $date = App::getFromRequest("date");
        $dueDate = App::getFromRequest("duedate");
        $datePaid = App::getFromRequest("datepaid");
        $credit = App::getFromRequest("credit");
        $taxRate = App::getFromRequest("taxrate");
        $taxRate2 = App::getFromRequest("taxrate2");
        $paymentMethod = App::getFromRequest("paymentmethod");
        $notes = App::getFromRequest("notes");
        $changes = false;
        if ($invoiceNum) {
            $changes = true;
            $invoice->invoiceNumber = $invoiceNum;
        }
        if ($date) {
            $changes = true;
            $invoice->dateCreated = $date;
        }
        if ($dueDate) {
            $changes = true;
            $invoice->dateDue = $dueDate;
        }
        if ($datePaid) {
            $changes = true;
            $invoice->datePaid = $datePaid;
        }
        if ($credit) {
            $changes = true;
            $invoice->credit = $credit;
        }
        if ($taxRate) {
            $changes = true;
            $invoice->taxRate1 = $taxRate;
        }
        if ($taxRate2) {
            $changes = true;
            $invoice->taxRate2 = $taxRate2;
        }
        if ($status) {
            $changes = true;
            $invoice->status = $status;
        }
        if ($paymentMethod) {
            $changes = true;
            $invoice->paymentGateway = $paymentMethod;
        }
        if ($notes) {
            $changes = true;
            $invoice->adminNotes = $notes;
        }
        if ($changes) {
            $invoice->save();
        }
        updateInvoiceTotal($invoiceId);
        if ($publish || $publishAndSendEmail) {
            $invoiceArr = array("source" => "api", "user" => WHMCS\Session::get("adminid") ?: "system", "invoiceid" => $invoiceId, "status" => "Unpaid");
            $invoice = WHMCS\Billing\Invoice::find($invoiceid);
            $invoice->status = "Unpaid";
            $invoice->dateCreated = WHMCS\Carbon::now();
            $invoice->save();
            run_hook("InvoiceCreation", $invoiceArr);
            if (!$paymentMethod) {
                $paymentMethod = getClientsPaymentMethod($userId);
            }
            $paymentType = WHMCS\Database\Capsule::table("tblpaymentgateways")->where("setting", "type")->where("gateway", $paymentMethod)->value("value");
            updateInvoiceTotal($invoiceId);
            logActivity("Modified Invoice Options - Invoice ID: " . $invoiceId, $userId);
            if ($publishAndSendEmail) {
                run_hook("InvoiceCreationPreEmail", $invoiceArr);
                $emailName = "Invoice Created";
                if (in_array($paymentType, array("CC", "OfflineCC"))) {
                    $emailName = "Credit Card " . $emailName;
                }
                sendMessage($emailName, $invoiceId);
                run_hook("InvoiceCreated", $invoiceArr);
            }
        }
        $apiresults = array("result" => "success", "invoiceid" => $invoiceId);
    }
}

?>