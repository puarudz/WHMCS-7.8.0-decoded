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
$sendInvoice = App::get_req_var("sendinvoice");
$paymentMethod = App::get_req_var("paymentmethod");
if (!$paymentMethod) {
    $paymentMethod = NULL;
}
$status = App::get_req_var("status");
$createAsDraft = (bool) App::get_req_var("draft");
$invoiceStatuses = WHMCS\Invoices::getInvoiceStatusValues();
$defaultStatus = "Unpaid";
$doprocesspaid = false;
$result = select_query("tblclients", "id", array("id" => $_POST["userid"]));
$data = mysql_fetch_array($result);
if (!$data["id"]) {
    $apiresults = array("result" => "error", "message" => "Client ID Not Found");
} else {
    if ($createAsDraft && $sendInvoice) {
        $apiresults = array("result" => "error", "message" => "Cannot create and send a draft invoice in a single API request. Please create and send separately.");
    } else {
        $taxrate = $taxrate2 = NULL;
        if (App::isInRequest("taxrate")) {
            $taxrate2 = 0;
            $taxrate = App::getFromRequest("taxrate");
            if (App::isInRequest("taxrate2")) {
                $taxrate2 = App::getFromRequest("taxrate2");
            }
        }
        if ($createAsDraft) {
            $status = "Draft";
        } else {
            if (!in_array($status, $invoiceStatuses)) {
                $status = $defaultStatus;
            }
        }
        $dateCreated = App::getFromRequest("date");
        if ($dateCreated) {
            try {
                $format = "Y-m-d";
                if (!stristr($dateCreated, "-")) {
                    $format = "Ymd";
                }
                $dateCreated = WHMCS\Carbon::createFromFormat($format, $dateCreated);
            } catch (Exception $e) {
                $dateCreated = NULL;
            }
        }
        $dueDate = App::getFromRequest("duedate");
        if ($dueDate) {
            try {
                $format = "Y-m-d";
                if (!stristr($dueDate, "-")) {
                    $format = "Ymd";
                }
                $dueDate = WHMCS\Carbon::createFromFormat($format, $dueDate);
            } catch (Exception $e) {
                $dueDate = NULL;
            }
        }
        $invoice = WHMCS\Billing\Invoice::newInvoice(App::getFromRequest("userid"), $paymentMethod, $taxrate, $taxrate2);
        if ($dateCreated) {
            $invoice->dateCreated = $dateCreated;
        }
        if ($dueDate) {
            $invoice->dateDue = $dueDate;
        }
        if ($status != $invoice->status) {
            $invoice->status = $status;
        }
        $invoice->adminNotes = App::getFromRequest("notes");
        $invoice->save();
        $invoiceid = $invoice->id;
        $invoiceArr = array("source" => "api", "user" => WHMCS\Session::get("adminid"), "invoiceid" => $invoiceid, "status" => $status);
        foreach ($_POST as $k => $v) {
            if (substr($k, 0, 10) == "itemamount") {
                $counter = substr($k, 10);
                $description = $_POST["itemdescription" . $counter];
                $amount = $_POST["itemamount" . $counter];
                $taxed = $_POST["itemtaxed" . $counter];
                if ($description) {
                    insert_query("tblinvoiceitems", array("invoiceid" => $invoiceid, "userid" => $userid, "description" => $description, "amount" => $amount, "taxed" => $taxed));
                }
            }
        }
        updateInvoiceTotal($invoiceid);
        run_hook("InvoiceCreation", $invoiceArr);
        if (isset($autoapplycredit) && $autoapplycredit) {
            $result = select_query("tblclients", "credit", array("id" => $userid));
            $data = mysql_fetch_array($result);
            $credit = $data["credit"];
            $result = select_query("tblinvoices", "total", array("id" => $invoiceid));
            $data = mysql_fetch_array($result);
            $total = $data["total"];
            if (0 < $credit) {
                if ($total <= $credit) {
                    $creditleft = $credit - $total;
                    $credit = $total;
                    $doprocesspaid = true;
                } else {
                    $creditleft = 0;
                }
                logActivity("Credit Automatically Applied at Invoice Creation - Invoice ID: " . $invoiceid . " - Amount: " . $credit, $userid);
                update_query("tblclients", array("credit" => $creditleft), array("id" => $userid));
                update_query("tblinvoices", array("credit" => $credit), array("id" => $invoiceid));
                insert_query("tblcredit", array("clientid" => $userid, "date" => "now()", "description" => "Credit Applied to Invoice #" . $invoiceid, "amount" => $credit * -1));
                updateInvoiceTotal($invoiceid);
            }
        }
        if ($sendInvoice) {
            run_hook("InvoiceCreationPreEmail", $invoiceArr);
            $where = array("gateway" => $paymentMethod, "setting" => "type");
            $result = select_query("tblpaymentgateways", "value", $where);
            $data = mysql_fetch_array($result);
            $paymentType = $data["value"];
            $emailTemplate = $paymentType == "CC" || $paymentType == "OfflineCC" ? "Credit Card Invoice Created" : "Invoice Created";
            $template = WHMCS\Mail\Template::where("name", $emailTemplate)->get()->first();
            sendMessage($template, $invoiceid);
        }
        if ($status != "Draft") {
            run_hook("InvoiceCreated", $invoiceArr);
        }
        if ($doprocesspaid) {
            processPaidInvoice($invoiceid);
        }
        $apiresults = array("result" => "success", "invoiceid" => $invoiceid, "status" => $status);
    }
}

?>