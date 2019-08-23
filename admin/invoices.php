<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$whmcs = App::self();
$action = $whmcs->get_req_var("action");
$warning = $whmcs->get_req_var("warning");
if ($action == "edit" || $action == "invtooltip") {
    $reqperm = "Manage Invoice";
} else {
    if ($action == "createinvoice") {
        $reqperm = "Create Invoice";
    } else {
        $reqperm = "List Invoices";
    }
}
$aInt = new WHMCS\Admin($reqperm);
$aInt->requiredFiles(array("clientfunctions", "invoicefunctions", "gatewayfunctions", "processinvoices", "ccfunctions"));
$invoiceModel = NULL;
if ($action == "edit") {
    $invoice = new WHMCS\Invoice($whmcs->get_req_var("id"));
    $invoiceModel = $invoice->getModel();
    $pageicon = "invoicesedit";
    if ($invoice->isProformaInvoice()) {
        $pagetitle = AdminLang::trans("fields.proformaInvoiceNum") . $invoice->getData("invoicenum");
    } else {
        $pagetitle = AdminLang::trans("fields.invoicenum") . $invoice->getData("invoicenum");
    }
} else {
    $pageicon = "invoices";
    $pagetitle = $aInt->lang("invoices", "title");
}
$aInt->title = $pagetitle;
$aInt->sidebar = "billing";
$aInt->icon = $pageicon;
$invoiceid = (int) $whmcs->get_req_var("invoiceid");
$status = $whmcs->get_req_var("status");
if (!in_array($status, array_merge(WHMCS\Invoices::getInvoiceStatusValues(), array("Overdue")))) {
    $status = "";
}
if ($action == "invtooltip") {
    check_token("WHMCS.admin.default");
    echo "<table bgcolor=\"#cccccc\" cellspacing=\"1\" cellpadding=\"3\"><tr bgcolor=\"#efefef\" style=\"text-align:center;font-weight:bold;\"><td>" . $aInt->lang("fields", "description") . "</td><td>" . $aInt->lang("fields", "amount") . "</td></tr>";
    $currency = getCurrency($userid);
    $result = select_query("tblinvoiceitems", "", array("invoiceid" => $id), "id", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $lineid = $data["id"];
        echo "<tr bgcolor=\"#ffffff\"><td width=\"275\">" . nl2br($data["description"]) . "</td><td width=\"100\" style=\"text-align:right;\">" . formatCurrency($data["amount"]) . "</td></tr>";
    }
    $data = get_query_vals("tblinvoices", "subtotal,credit,tax,tax2,taxrate,taxrate2,total", array("id" => $id), "id", "ASC");
    echo "<tr bgcolor=\"#efefef\" style=\"text-align:right;font-weight:bold;\"><td>" . $aInt->lang("fields", "subtotal") . "&nbsp;</td><td>" . formatCurrency($data["subtotal"]) . "</td></tr>";
    if ($CONFIG["TaxEnabled"]) {
        if (0 < $data["tax"]) {
            echo "<tr bgcolor=\"#efefef\" style=\"text-align:right;font-weight:bold;\"><td>" . $data["taxrate"] . "% " . $aInt->lang("fields", "tax") . "&nbsp;</td><td>" . formatCurrency($data["tax"]) . "</td></tr>";
        }
        if (0 < $data["tax2"]) {
            echo "<tr bgcolor=\"#efefef\" style=\"text-align:right;font-weight:bold;\"><td>" . $data["taxrate2"] . "% " . $aInt->lang("fields", "tax") . "&nbsp;</td><td>" . formatCurrency($data["tax2"]) . "</td></tr>";
        }
    }
    echo "<tr bgcolor=\"#efefef\" style=\"text-align:right;font-weight:bold;\"><td>" . $aInt->lang("fields", "credit") . "&nbsp;</td><td>" . formatCurrency($data["credit"]) . "</td></tr>";
    echo "<tr bgcolor=\"#efefef\" style=\"text-align:right;font-weight:bold;\"><td>" . $aInt->lang("fields", "totaldue") . "&nbsp;</td><td>" . formatCurrency($data["total"]) . "</td></tr>";
    echo "</table>";
    exit;
}
if ($action == "createinvoice") {
    check_token("WHMCS.admin.default");
    if (!checkActiveGateway()) {
        $aInt->gracefulExit($aInt->lang("gateways", "nonesetup"));
    }
    $gateway = getClientsPaymentMethod($userid);
    $invoice = WHMCS\Billing\Invoice::newInvoice($userid, $gateway);
    $invoice->save();
    $invoiceid = $invoice->id;
    logActivity("Created Manual Invoice - Invoice ID: " . $invoiceid, $userid);
    $invoiceArr = array("source" => "adminarea", "user" => WHMCS\Session::get("adminid"), "invoiceid" => $invoiceid, "status" => "Draft");
    run_hook("InvoiceCreation", $invoiceArr);
    run_hook("InvoiceCreationAdminArea", $invoiceArr);
    redir("action=edit&id=" . $invoiceid);
}
if ($action == "checkTransactionId") {
    check_token("WHMCS.admin.default");
    $transactionId = $whmcs->get_req_var("transid");
    $paymentMethod = $whmcs->get_req_var("paymentmethod");
    $output = array("unique" => $transactionId && !isUniqueTransactionID($transactionId, $paymentMethod) ? false : true);
    $aInt->jsonResponse($output);
}
$filters = new WHMCS\Filter();
if ($whmcs->get_req_var("markpaid")) {
    check_token("WHMCS.admin.default");
    checkPermission("Manage Invoice");
    $failedInvoices = array();
    $invoiceCount = 0;
    foreach ($selectedinvoices as $invid) {
        if (get_query_val("tblinvoices", "status", array("id" => $invid)) == "Paid") {
            continue;
        }
        $paymentMethod = get_query_val("tblinvoices", "paymentmethod", array("id" => $invid));
        if (addInvoicePayment($invid, "", "", "", $paymentMethod) === false) {
            $failedInvoices[] = $invid;
        }
        $invoiceCount++;
    }
    if (0 < count($selectedinvoices)) {
        $failedInvoices["successfulInvoicesCount"] = $invoiceCount - count($failedInvoices);
        WHMCS\Cookie::set("FailedMarkPaidInvoices", $failedInvoices);
    }
    $filters->redir();
}
if ($whmcs->get_req_var("markunpaid")) {
    check_token("WHMCS.admin.default");
    checkPermission("Manage Invoice");
    foreach ($selectedinvoices as $invid) {
        $invoice = WHMCS\Billing\Invoice::find($invid);
        $invoice->status = "Unpaid";
        $invoice->save();
        logActivity("Reactivated Invoice - Invoice ID: " . $invid, $invoice->clientId);
        run_hook("InvoiceUnpaid", array("invoiceid" => $invid));
    }
    $filters->redir();
}
if ($whmcs->get_req_var("markcancelled")) {
    check_token("WHMCS.admin.default");
    checkPermission("Manage Invoice");
    foreach ($selectedinvoices as $invid) {
        $invoice = WHMCS\Billing\Invoice::find($invid);
        $invoice->status = "Cancelled";
        $invoice->save();
        logActivity("Cancelled Invoice - Invoice ID: " . $invid, $invoice->clientId);
        run_hook("InvoiceCancelled", array("invoiceid" => $invid));
    }
    $filters->redir();
}
if ($whmcs->get_req_var("duplicateinvoice")) {
    check_token("WHMCS.admin.default");
    foreach ($selectedinvoices as $invid) {
        $invoices = new WHMCS\Invoices();
        $invoices->duplicate($invid);
    }
    $filters->redir();
}
if ($whmcs->get_req_var("massdelete")) {
    check_token("WHMCS.admin.default");
    checkPermission("Delete Invoice");
    foreach ($selectedinvoices as $invid) {
        $invoice = WHMCS\Billing\Invoice::find($invid);
        $userId = $invoice->clientId;
        $invoice->delete();
        logActivity("Deleted Invoice - Invoice ID: " . $invid, $userId);
    }
    $filters->redir();
}
if ($whmcs->get_req_var("paymentreminder")) {
    check_token("WHMCS.admin.default");
    foreach ($selectedinvoices as $invid) {
        $invoice = WHMCS\Billing\Invoice::find($invid);
        sendMessage("Invoice Payment Reminder", $invid);
        logActivity("Invoice Payment Reminder Sent - Invoice ID: " . $invid, $invoice->clientId);
    }
    $filters->redir();
}
if ($whmcs->get_req_var("delete")) {
    check_token("WHMCS.admin.default");
    checkPermission("Delete Invoice");
    $invoiceID = (int) $whmcs->get_req_var("invoiceid");
    if ($whmcs->get_req_var("returnCredit")) {
        removeCreditOnInvoiceDelete($invoiceID);
    }
    $invoice = WHMCS\Billing\Invoice::find($invoiceID);
    $userId = $invoice->clientId;
    $invoice->delete();
    logActivity("Deleted Invoice - Invoice ID: " . $invoiceID, $userId);
    $filters->redir();
}
ob_start();
if ($action == "") {
    $name = "invoices";
    $orderby = "duedate";
    $sort = "DESC";
    $pageObj = new WHMCS\Pagination($name, $orderby, $sort);
    $pageObj->digestCookieData();
    $tbl = new WHMCS\ListTable($pageObj, 0, $aInt);
    $tbl->setColumns(array("checkall", array("id", $aInt->lang("fields", "invoicenum")), array("clientname", $aInt->lang("fields", "clientname")), array("date", $aInt->lang("fields", "invoicedate")), array("duedate", $aInt->lang("fields", "duedate")), array("last_capture_attempt", AdminLang::trans("fields.lastCaptureAttempt"), "150"), array("total", $aInt->lang("fields", "total")), array("paymentmethod", $aInt->lang("fields", "paymentmethod")), array("status", $aInt->lang("fields", "status")), "", ""));
    $invoicesModel = new WHMCS\Invoices($pageObj);
    if (checkPermission("View Income Totals", true)) {
        $invoicetotals = $invoicesModel->getInvoiceTotals();
        if (count($invoicetotals)) {
            echo "<div class=\"contentbox\" style=\"font-size:18px;\">";
            foreach ($invoicetotals as $vals) {
                echo "<b>" . $vals["currencycode"] . "</b> " . $aInt->lang("status", "paid") . ": <span class=\"textgreen\"><b>" . $vals["paid"] . "</b></span> " . $aInt->lang("status", "unpaid") . ": <span class=\"textred\"><b>" . $vals["unpaid"] . "</b></span> " . $aInt->lang("status", "overdue") . ": <span class=\"textblack\"><b>" . $vals["overdue"] . "</b></span><br />";
            }
            echo "</div><br />";
        }
    }
    echo $aInt->beginAdminTabs(array($aInt->lang("global", "searchfilter")));
    $clientid = $filters->get("clientid");
    $invoicenum = $filters->get("invoicenum");
    $status = $filters->get("status");
    echo "\n<!-- Filter -->\n<form action=\"";
    echo $whmcs->getPhpSelf();
    echo "\" method=\"post\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n    <tr>\n        <td width=\"15%\" class=\"fieldlabel\">\n            ";
    echo AdminLang::trans("fields.clientname");
    echo "        </td>\n        <td class=\"fieldarea\">\n            ";
    echo $aInt->clientSearchDropdown("clientname", $clientname = $filters->get("clientname"), array(), "", "name");
    echo "        </td>\n        <td width=\"15%\" class=\"fieldlabel\">\n            ";
    echo AdminLang::trans("fields.invoicedate");
    echo "        </td>\n        <td class=\"fieldarea\">\n            <div class=\"form-group date-picker-prepend-icon\">\n                <label for=\"inputInvoiceDate\" class=\"field-icon\">\n                    <i class=\"fal fa-calendar-alt\"></i>\n                </label>\n                <input id=\"inputInvoiceDate\"\n                       type=\"text\"\n                       name=\"invoicedate\"\n                       value=\"";
    echo $invoicedate = $filters->get("invoicedate");
    echo "\"\n                       class=\"form-control date-picker-search\"\n                       data-opens=\"left\"\n                />\n            </div>\n        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">\n            ";
    echo AdminLang::trans("fields.lineitem");
    echo "        </td>\n        <td class=\"fieldarea\">\n            <input type=\"text\"\n                   name=\"lineitem\"\n                   class=\"form-control input-300\"\n                   value=\"";
    echo $lineitem = $filters->get("lineitem");
    echo "\"\n            >\n        </td>\n        <td width=\"15%\" class=\"fieldlabel\">\n            ";
    echo AdminLang::trans("fields.duedate");
    echo "        </td>\n        <td class=\"fieldarea\">\n            <div class=\"form-group date-picker-prepend-icon\">\n                <label for=\"inputDueDate\" class=\"field-icon\">\n                    <i class=\"fal fa-calendar-alt\"></i>\n                </label>\n                <input id=\"inputDueDate\"\n                       type=\"text\"\n                       name=\"duedate\"\n                       value=\"";
    echo $duedate = $filters->get("duedate");
    echo "\"\n                       class=\"form-control date-picker-search\"\n                       data-opens=\"left\"\n                />\n            </div>\n        </td>\n    </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                ";
    echo AdminLang::trans("fields.paymentmethod");
    echo "            </td>\n            <td class=\"fieldarea\">\n                ";
    $paymentmethod = $filters->get("paymentmethod");
    echo paymentMethodsSelection(AdminLang::trans("global.any"));
    echo "            </td>\n            <td width=\"15%\" class=\"fieldlabel\">\n                ";
    echo AdminLang::trans("fields.datepaid");
    echo "            </td>\n            <td class=\"fieldarea\">\n                <div class=\"form-group date-picker-prepend-icon\">\n                    <label for=\"inputDatePaid\" class=\"field-icon\">\n                        <i class=\"fal fa-calendar-alt\"></i>\n                    </label>\n                    <input id=\"inputDatePaid\"\n                           type=\"text\"\n                           name=\"datepaid\"\n                           value=\"";
    echo $datepaid = $filters->get("datepaid");
    echo "\"\n                           class=\"form-control date-picker-search\"\n                           data-opens=\"left\"\n                    />\n                </div>\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                ";
    echo AdminLang::trans("fields.status");
    echo "            </td>\n            <td class=\"fieldarea\">\n                <select name=\"status\" class=\"form-control select-inline\">\n                    <option value=\"\">\n                        ";
    echo AdminLang::trans("global.any");
    echo "                    </option>\n                    <option value=\"Draft\"";
    echo $status == "Draft" ? " selected=\"selected\"" : "";
    echo ">\n                        ";
    echo AdminLang::trans("status.draft");
    echo "                    </option>\n                    <option value=\"Unpaid\"";
    echo $status == "Unpaid" ? " selected=\"selected\"" : "";
    echo ">\n                        ";
    echo AdminLang::trans("status.unpaid");
    echo "                    </option>\n                    <option value=\"Overdue\"";
    echo $status == "Overdue" ? " selected=\"selected\"" : "";
    echo ">\n                        ";
    echo AdminLang::trans("status.overdue");
    echo "                    </option>\n                    <option value=\"Paid\"";
    echo $status == "Paid" ? " selected=\"selected\"" : "";
    echo ">\n                        ";
    echo AdminLang::trans("status.paid");
    echo "                    </option>\n                    <option value=\"Cancelled\"";
    echo $status == "Cancelled" ? " selected=\"selected\"" : "";
    echo ">\n                        ";
    echo AdminLang::trans("status.cancelled");
    echo "                    </option>\n                    <option value=\"Refunded\"";
    echo $status == "Refunded" ? " selected=\"selected\"" : "";
    echo ">\n                        ";
    echo AdminLang::trans("status.refunded");
    echo "                    </option>\n                    <option value=\"Collections\"";
    echo $status == "Collections" ? " selected=\"selected\"" : "";
    echo ">\n                        ";
    echo AdminLang::trans("status.collections");
    echo "                    </option>\n                    <option value=\"Payment Pending\"";
    echo $status == "Payment Pending" ? " selected=\"selected\"" : "";
    echo ">\n                        ";
    echo AdminLang::trans("status.paymentpending");
    echo "                    </option>\n                </select>\n            </td>\n            <td class=\"fieldlabel\">\n                ";
    echo AdminLang::trans("fields.lastCaptureAttempt");
    echo "            </td>\n            <td class=\"fieldarea\">\n                <div class=\"form-group date-picker-prepend-icon\">\n                    <label for=\"inputLastCaptureAttempt\" class=\"field-icon\">\n                        <i class=\"fal fa-calendar-alt\"></i>\n                    </label>\n                    <input id=\"inputLastCaptureAttempt\"\n                           type=\"text\"\n                           name=\"last_capture_attempt\"\n                           value=\"";
    echo $lastCaptureAttempt = $filters->get("last_capture_attempt");
    echo "\"\n                           class=\"form-control date-picker-search\"\n                           data-opens=\"left\"\n                    />\n                </div>\n            </td>\n        </tr>\n    <tr>\n        <td class=\"fieldlabel\">\n            ";
    echo AdminLang::trans("fields.totaldue");
    echo "        </td>\n        <td class=\"fieldarea\">\n            ";
    echo AdminLang::trans("filters.from");
    echo "            <input type=\"number\"\n                   name=\"totalfrom\"\n                   class=\"form-control input-100 input-inline\"\n                   value=\"";
    echo $totalfrom = $filters->get("totalfrom");
    echo "\"\n                   step=\"0.01\"\n            >\n            ";
    echo AdminLang::trans("filters.to");
    echo "            <input type=\"number\"\n                   name=\"totalto\"\n                   class=\"form-control input-100 input-inline\"\n                   value=\"";
    echo $totalto = $filters->get("totalto");
    echo "\"\n                   step=\"0.01\"\n            >\n        </td>\n        <td colspan=\"2\" class=\"fieldlabel\"></td>\n    </tr>\n    <tr></tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
    echo $aInt->lang("global", "search");
    echo "\" class=\"btn btn-default\" />\n</div>\n\n</form>\n\n";
    echo $aInt->endAdminTabs();
    echo "\n<br />\n\n";
    $failedInvoices = WHMCS\Input\Sanitize::makeSafeForOutput(WHMCS\Cookie::get("FailedMarkPaidInvoices", true));
    if (isset($failedInvoices["successfulInvoicesCount"])) {
        $successfulInvoicesCount = (int) $failedInvoices["successfulInvoicesCount"];
        unset($failedInvoices["successfulInvoicesCount"]);
    } else {
        $successfulInvoicesCount = 0;
    }
    WHMCS\Cookie::delete("FailedMarkPaidInvoices");
    if (0 < $successfulInvoicesCount || 0 < count($failedInvoices)) {
        $description = sprintf($aInt->lang("invoices", "markPaidSuccess"), $successfulInvoicesCount);
        if (0 < count($failedInvoices)) {
            $failedInvoicesString = (string) implode(", ", $failedInvoices);
            $description .= "<br />" . sprintf($aInt->lang("invoices", "markPaidError"), $failedInvoicesString);
            $description .= "<br />" . $aInt->lang("invoices", "markPaidErrorInfo") . " <a href=\"https://docs.whmcs.com/Clients:Invoices_Tab#Mark_Paid\" target=\"_blank\">" . $aInt->lang("global", "findoutmore") . "</a>";
        }
        $infoBoxTitle = $aInt->lang("global", "successWithErrors");
        $infoBoxType = "info";
        if (count($failedInvoices) == 0) {
            $infoBoxTitle = $aInt->lang("global", "success");
            $infoBoxType = "success";
        }
        if ($successfulInvoicesCount == 0) {
            $infoBoxTitle = $aInt->lang("global", "erroroccurred");
            $infoBoxType = "error";
        }
        infoBox($infoBoxTitle, $description, $infoBoxType);
        echo $infobox;
    }
    echo WHMCS\View\Asset::jsInclude("jquerytt.js");
    $selectors = "input[name='markpaid'],input[name='markunpaid'],input[name='markcancelled'],";
    $selectors .= "input[name='duplicateinvoice'],input[name='paymentreminder'],input[name='massdelete']";
    $jqueryCode = "\$(\".invtooltip\").tooltip({cssClass:\"invoicetooltip\"});\n\n\$(\"" . $selectors . "\").on('click', function( event ) {\n    var selectedItems = \$(\"input[name='selectedinvoices[]']\");\n    var name = \$(this).attr('name');\n    switch(name) {\n        case 'markpaid':\n            var langConfirm = '" . $aInt->lang("invoices", "markpaidconfirm", "1") . "';\n            break;\n        case 'markunpaid':\n            var langConfirm = '" . $aInt->lang("invoices", "markunpaidconfirm", "1") . "';\n            break;\n        case 'markcancelled':\n            var langConfirm = '" . $aInt->lang("invoices", "markcancelledconfirm", "1") . "';\n            break;\n        case 'duplicateinvoice':\n            var langConfirm = '" . $aInt->lang("invoices", "duplicateinvoiceconfirm", "1") . "';\n            break;\n        case 'paymentreminder':\n            var langConfirm = '" . $aInt->lang("invoices", "sendreminderconfirm", "1") . "';\n            break;\n        case 'massdelete':\n            var langConfirm = '" . $aInt->lang("invoices", "massdeleteconfirm", "1") . "';\n            break;\n    }\n    if (selectedItems.filter(':checked').length == 0) {\n        event.preventDefault();\n        alert('" . $aInt->lang("global", "pleaseSelectForMassAction") . "');\n    } else {\n        if (!confirm(langConfirm)) {\n            event.preventDefault();\n        }\n    }\n});";
    $aInt->jquerycode = $jqueryCode;
    $filters->store();
    $criteria = array("clientid" => $clientid, "clientname" => $clientname, "invoicenum" => $invoicenum, "lineitem" => $lineitem, "paymentmethod" => $paymentmethod, "invoicedate" => $invoicedate, "duedate" => $duedate, "datepaid" => $datepaid, "last_capture_attempt" => $lastCaptureAttempt, "totalfrom" => $totalfrom, "totalto" => $totalto, "status" => $status);
    $invoicesModel->execute($criteria);
    $numresults = $pageObj->getNumResults();
    if ($filters->isActive() && $numresults == 1) {
        $invoice = $pageObj->getOne();
        redir("action=edit&id=" . $invoice["id"], "invoices.php");
    } else {
        $invoicelist = $pageObj->getData();
        foreach ($invoicelist as $invoice) {
            $linkopen = "<a href=\"invoices.php?action=edit&id=" . $invoice["id"] . "\">";
            $linkclose = "</a>";
            $token = generate_token("link");
            $credit = $invoice["credit"];
            $payments = WHMCS\Database\Capsule::table("tblaccounts")->where("invoiceid", "=", $invoice["id"])->count("id");
            $deleteLink = "<a href=\"#\" onClick=\"doDelete('" . $invoice["id"] . "');return false\">\n    <img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\">\n</a>";
            if (0 < $credit && 0 < $payments) {
                $deleteLink = "<a href=\"#\" onclick=\"openModal('ExistingCreditAndPayments', " . $invoice["id"] . ")\">\n    <img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\">\n</a>";
            } else {
                if (0 < $credit && $payments == 0) {
                    $deleteLink = "<a href=\"#\" onclick=\"openModal('ExistingCredit', " . $invoice["id"] . ")\">\n    <img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\">\n</a>";
                } else {
                    if ($credit == 0 && 0 < $payments) {
                        $deleteLink = "<a href=\"#\" onclick=\"openModal('ExistingPayments', " . $invoice["id"] . ")\">\n    <img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\">\n</a>";
                    }
                }
            }
            $tbl->addRow(array(array("trAttributes" => array("class" => "text-center"), "output" => "<input type='checkbox' name='selectedinvoices[]' value='" . $invoice["id"] . "' class='checkall'>"), $linkopen . $invoice["invoicenum"] . $linkclose, $invoice["clientname"], $invoice["date"], $invoice["duedate"], $invoice["lastCaptureAttempt"], "<a href='invoices.php?action=invtooltip&id=" . $invoice["id"] . "&userid=" . $invoice["userid"] . $token . "'" . " class='invtooltip' lang=''>" . $invoice["totalformatted"] . "</a>", $invoice["paymentmethod"], $invoice["statusformatted"], (string) $linkopen . "<img src='images/edit.gif' width='16' height='16' border='0' alt='Edit'>" . $linkclose, $deleteLink));
        }
        $mpButton = $aInt->lang("invoices", "markpaid");
        $mupButton = $aInt->lang("invoices", "markunpaid");
        $mcButton = $aInt->lang("invoices", "markcancelled");
        $diButton = $aInt->lang("invoices", "duplicateinvoice");
        $srButton = $aInt->lang("invoices", "sendreminder");
        $delButton = $aInt->lang("global", "delete");
        $massActionButtons = "<input type=\"submit\" value=\"" . $mpButton . "\" class=\"btn btn-success\" name=\"markpaid\" />\n <input type=\"submit\" value=\"" . $mupButton . "\" class=\"btn btn-default\" name=\"markunpaid\" />\n <input type=\"submit\" value=\"" . $mcButton . "\" class=\"btn btn-default\" name=\"markcancelled\" />\n <input type=\"submit\" value=\"" . $diButton . "\" class=\"btn btn-default\" name=\"duplicateinvoice\" />\n <input type=\"submit\" value=\"" . $srButton . "\" class=\"btn btn-default\" name=\"paymentreminder\" />\n <input type=\"submit\" value=\"" . $delButton . "\" class=\"btn btn-danger\" name=\"massdelete\" />";
        $tbl->setMassActionBtns($massActionButtons);
        echo $tbl->output();
        unset($clientlist);
        unset($invoicesModel);
        echo $aInt->modal("ExistingCreditAndPayments", $aInt->lang("invoices", "existingCreditTitle"), $aInt->lang("invoices", "existingCredit"), array(array("title" => $aInt->lang("invoices", "existingCreditReturn"), "onclick" => "\$(\"#existingPaymentsReturnCredit\").modal(\"show\")"), array("title" => $aInt->lang("invoices", "existingCreditDiscard"), "onclick" => "\$(\"#existingPaymentsDiscardCredit\").modal(\"show\");"), array("title" => $aInt->lang("global", "cancel"))));
        echo $aInt->modal("ExistingPaymentsReturnCredit", $aInt->lang("invoices", "existingPaymentsTitle"), $aInt->lang("invoices", "existingPayments"), array(array("title" => $aInt->lang("invoices", "existingPaymentsOrphan"), "onclick" => "doDeleteCall(\"returnCredit\");"), array("title" => $aInt->lang("global", "no"))));
        echo $aInt->modal("ExistingPaymentsDiscardCredit", $aInt->lang("invoices", "existingPaymentsTitle"), $aInt->lang("invoices", "existingPayments"), array(array("title" => $aInt->lang("invoices", "existingPaymentsOrphan"), "onclick" => "doDeleteCall()"), array("title" => $aInt->lang("global", "no"))));
        echo $aInt->modal("ExistingCredit", $aInt->lang("invoices", "existingCreditTitle"), $aInt->lang("invoices", "existingCredit"), array(array("title" => $aInt->lang("invoices", "existingCreditReturn"), "onclick" => "doDeleteCall(\"returnCredit\")"), array("title" => $aInt->lang("invoices", "existingCreditDiscard"), "onclick" => "doDeleteCall()"), array("title" => $aInt->lang("global", "cancel"))));
        echo $aInt->modal("ExistingPayments", $aInt->lang("invoices", "existingPaymentsTitle"), $aInt->lang("invoices", "existingPayments"), array(array("title" => $aInt->lang("invoices", "existingPaymentsOrphan"), "onclick" => "doDeleteCall()"), array("title" => $aInt->lang("global", "no"))));
        $jscode = "var invoice = 0;\nfunction openModal(displayModal, invoiceID)\n{\n    /**\n     * Store the invoiceID in the global JS variable\n     */\n    invoice = invoiceID;\n    \$('#modal' + displayModal).modal('show');\n}\n\nfunction doDeleteCall(credit)\n{\n    if (credit == 'returnCredit') {\n        doDeleteReturnCredit(invoice);\n    } else {\n        doDelete(invoice);\n    }\n}";
        echo $aInt->modalWithConfirmation("doDelete", $aInt->lang("invoices", "delete"), $whmcs->getPhpSelf() . "?status=" . $status . "&delete=true&invoiceid=");
        echo $aInt->modalWithConfirmation("doDeleteReturnCredit", $aInt->lang("invoices", "delete"), $whmcs->getPhpSelf() . "?status=" . $status . "&delete=true&returnCredit=true&invoiceid=");
    }
} else {
    if ($action == "edit") {
        $saveoptions = $whmcs->get_req_var("saveoptions");
        $save = $whmcs->get_req_var("save");
        $sub = $whmcs->get_req_var("sub");
        $addcredit = $whmcs->get_req_var("addcredit");
        $removecredit = $whmcs->get_req_var("removecredit");
        $creditapply = $whmcs->get_req_var("creditapply");
        $creditremove = $whmcs->get_req_var("creditremove");
        $tplname = $whmcs->get_req_var("tplname");
        $error = $whmcs->get_req_var("error");
        $refundattempted = $whmcs->get_req_var("refundattempted");
        $publishInvoice = $whmcs->get_req_var("publishInvoice");
        $publishAndSendEmail = $whmcs->get_req_var("inputPublishAndSendEmail");
        $userid = $invoice->getData("userid");
        $oldpaymentmethod = $invoice->getData("paymentmethod");
        $aInt->assertClientBoundary($userid);
        if ($saveoptions) {
            check_token("WHMCS.admin.default");
            update_query("tblinvoices", array("date" => toMySQLDate($invoicedate), "duedate" => toMySQLDate($datedue), "paymentmethod" => $paymentmethod, "invoicenum" => $invoicenum, "taxrate" => $taxrate, "taxrate2" => $taxrate2, "status" => $status), array("id" => $id));
            updateInvoiceTotal($id);
            if ($oldpaymentmethod != $paymentmethod) {
                run_hook("InvoiceChangeGateway", array("invoiceid" => $id, "paymentmethod" => $paymentmethod));
            }
            logActivity("Modified Invoice Options - Invoice ID: " . $id, $userid);
            redir("action=edit&id=" . $id);
        }
        if ($save == "notes") {
            check_token("WHMCS.admin.default");
            update_query("tblinvoices", array("notes" => $notes), array("id" => $id));
            logActivity("Modified Invoice Notes - Invoice ID: " . $id, $userid);
            redir("action=edit&id=" . $id);
        }
        if ($sub == "statuscancelled") {
            check_token("WHMCS.admin.default");
            $set = array("status" => "Cancelled", "datepaid" => "0000-00-00 00:00:00");
            $where = array("id" => $id);
            update_query("tblinvoices", $set, $where);
            logActivity("Cancelled Invoice - Invoice ID: " . $id, $userid);
            run_hook("InvoiceCancelled", array("invoiceid" => $id));
            redir("action=edit&id=" . $id);
        }
        if ($sub == "statusunpaid") {
            check_token("WHMCS.admin.default");
            $tab = $whmcs->get_req_var("tab");
            $set = array("status" => "Unpaid", "datepaid" => "0000-00-00 00:00:00");
            $where = array("id" => $id);
            update_query("tblinvoices", $set, $where);
            logActivity("Reactivated Invoice - Invoice ID: " . $id, $userid);
            run_hook("InvoiceUnpaid", array("invoiceid" => $id));
            if ($tab) {
                $tab = "&tab=" . $tab;
            }
            redir("action=edit&id=" . $id . $tab);
        }
        if ($sub == "zeroPaid") {
            check_token("WHMCS.admin.default");
            $id = $whmcs->get_req_var("id");
            $invoiceStatus = $invoice->getData("status");
            $invoiceBalance = $invoice->getData("balance");
            if ($invoiceStatus == "Unpaid" && (int) $invoiceBalance <= 0) {
                processPaidInvoice($id, true);
            }
            redir("action=edit&id=" . $id);
        }
        if ($sub == "markpaid") {
            check_token("WHMCS.admin.default");
            checkPermission("Add Transaction");
            $id = $whmcs->get_req_var("id");
            $transactionID = $whmcs->get_req_var("transid");
            $amount = $whmcs->get_req_var("amount");
            $fees = $whmcs->get_req_var("fees");
            $paymentMethod = $whmcs->get_req_var("paymentmethod");
            $sendConfirmation = $whmcs->get_req_var("sendconfirmation");
            $date = $whmcs->get_req_var("date");
            $validationError = false;
            $validationErrorDescription = array();
            if ($amount < 0) {
                $validationError = true;
                $validationErrorDescription[] = $aInt->lang("transactions", "amountInLessThanZero") . PHP_EOL;
            }
            if ((!$amount || $amount == 0) && (!$fees || $fees == 0)) {
                $validationError = true;
                $validationErrorDescription[] = $aInt->lang("transactions", "amountOrFeeRequired") . PHP_EOL;
            }
            $validate = new WHMCS\Validate();
            $invalidFormatLangKey = array("transactions", "amountOrFeeInvalidFormat");
            if ($amount && !$validate->validate("decimal", "amount", $invalidFormatLangKey) || $fees && !$validate->validate("decimal", "fees", $invalidFormatLangKey)) {
                $validationError = true;
                $validationErrorDescription[] = implode(PHP_EOL, array_unique($validate->getErrors())) . PHP_EOL;
            }
            if ($amount && $fees && $amount < $fees) {
                $validationError = true;
                $validationErrorDescription[] = $aInt->lang("transactions", "feeMustBeLessThanAmountIn") . PHP_EOL;
            }
            if ($amount && $fees && $fees < 0) {
                $validationError = true;
                $validationErrorDescription[] = $aInt->lang("transactions", "amountInFeeMustBePositive") . PHP_EOL;
            }
            $validationURL = "";
            if (!$validationError) {
                if ($sendConfirmation == "on") {
                    $sendConfirmation = "";
                } else {
                    $sendConfirmation = "on";
                }
                addInvoicePayment($id, $transactionID, $amount, $fees, $paymentMethod, $sendConfirmation, $date);
            } else {
                WHMCS\Cookie::set("ValidationError", array("validationError" => $validationErrorDescription, "submission" => array("transid" => $transactionID, "amount" => $amount, "fees" => $fees, "paymentmethod" => $paymentMethod, "sendconfirmation" => $sendConfirmation, "date" => $date)));
                $validationURL = "&error=validation&tab=2";
            }
            if (App::getFromRequest("ajax")) {
                $aInt->jsonResponse(array("redirectUri" => "invoices.php?action=edit&id=" . $id . $validationURL));
            } else {
                redir("action=edit&id=" . $id . $validationURL);
            }
        }
        if ($sub == "save") {
            check_token("WHMCS.admin.default");
            $taxed = $whmcs->get_req_var("taxed");
            if ($taxed == "") {
                $taxed = array();
            }
            if ($description) {
                foreach ($description as $lineId => $desc) {
                    $updateAmount = isset($amount[$lineId]) ? $amount[$lineId] : NULL;
                    $updateTaxed = isset($taxed[$lineId]) ? $taxed[$lineId] : NULL;
                    update_query("tblinvoiceitems", array("description" => $desc, "amount" => $updateAmount, "taxed" => $updateTaxed), array("id" => $lineId));
                }
            }
            if ($adddescription) {
                insert_query("tblinvoiceitems", array("invoiceid" => $id, "userid" => $userid, "description" => $adddescription, "amount" => $addamount, "taxed" => $addtaxed));
            }
            if ($selaction == "delete" && is_array($itemids)) {
                foreach ($itemids as $itemid) {
                    delete_query("tblinvoiceitems", array("id" => $itemid));
                }
            }
            if ($selaction == "split" && is_array($itemids)) {
                $result = select_query("tblinvoices", "userid,date,duedate,taxrate,taxrate2,paymentmethod", array("id" => $id));
                $data = mysql_fetch_array($result);
                list($userid, $date, $duedate, $taxrate, $taxrate2, $paymentmethod) = $data;
                $result = select_query("tblinvoiceitems", "COUNT(*)", array("invoiceid" => $id));
                $data = mysql_fetch_array($result);
                $totalitemscount = $data[0];
                if (count($itemids) < $totalitemscount) {
                    $invoice = WHMCS\Billing\Invoice::newInvoice($userid, $gateway, $taxrate, $taxrate2);
                    $invoice->status = "Unpaid";
                    $invoice->save();
                    $invoiceid = $invoice->id;
                    foreach ($itemids as $itemid) {
                        update_query("tblinvoiceitems", array("invoiceid" => $invoiceid), array("id" => $itemid));
                    }
                    updateInvoiceTotal($invoiceid);
                    updateInvoiceTotal($id);
                    logActivity("Split Invoice - Invoice ID: " . $id . " to Invoice ID: " . $invoiceid, $userid);
                    $invoiceArr = array("source" => "adminarea", "user" => WHMCS\Session::get("adminid"), "invoiceid" => $invoiceid, "status" => "Unpaid");
                    run_hook("InvoiceCreation", $invoiceArr);
                    run_hook("InvoiceCreationAdminArea", $invoiceArr);
                    run_hook("InvoiceSplit", array("originalinvoiceid" => $id, "newinvoiceid" => $invoiceid));
                    redir("action=edit&id=" . $invoiceid);
                }
            }
            updateInvoiceTotal($id);
            $result = select_query("tblinvoices", "userid", array("id" => $id));
            $data = mysql_fetch_array($result);
            $userid = $data[0];
            logActivity("Modified Invoice - Invoice ID: " . $id, $userid);
            redir("action=edit&id=" . $id);
        }
        if ($addcredit != "0.00" && $addcredit) {
            check_token("WHMCS.admin.default");
            $result2 = select_query("tblinvoices", "userid,subtotal,credit,total", array("id" => $id));
            $data = mysql_fetch_array($result2);
            $userid = $data["userid"];
            $subtotal = $data["subtotal"];
            $credit = $data["credit"];
            $total = $data["total"];
            $result2 = select_query("tblaccounts", "SUM(amountin)-SUM(amountout)", array("invoiceid" => $id));
            $data = mysql_fetch_array($result2);
            $amountpaid = $data[0];
            $balance = $total - $amountpaid;
            if ($CONFIG["TaxType"] == "Inclusive") {
                $subtotal = $total;
            }
            $addcredit = round($addcredit, 2);
            $balance = round($balance, 2);
            $result2 = select_query("tblclients", "credit", array("id" => $userid));
            $data = mysql_fetch_array($result2);
            $totalcredit = $data["credit"];
            if ($totalcredit < $addcredit) {
                redir("action=edit&id=" . $id . "&creditapply=exceedbalance");
            } else {
                if ($balance < $addcredit) {
                    redir("action=edit&id=" . $id . "&creditapply=exceedtotal");
                } else {
                    applyCredit($id, $userid, $addcredit);
                    $currency = getCurrency($userid);
                    redir("action=edit&id=" . $id . "&creditapply=success&amt=" . $addcredit);
                }
            }
        }
        if ($removecredit != "0.00" && $removecredit != "") {
            check_token("WHMCS.admin.default");
            $result2 = select_query("tblinvoices", "userid,subtotal,credit,total,status", array("id" => $id));
            $data = mysql_fetch_array($result2);
            $userid = $data["userid"];
            $subtotal = $data["subtotal"];
            $credit = $data["credit"];
            $total = $data["total"];
            $status = $data["status"];
            if ($credit < $removecredit) {
                redir("action=edit&id=" . $id . "&creditremove=exceedtotal");
            } else {
                update_query("tblinvoices", array("credit" => "-=" . $removecredit), array("id" => (int) $id));
                updateInvoiceTotal($id);
                update_query("tblclients", array("credit" => "+=" . $removecredit), array("id" => (int) $userid));
                insert_query("tblcredit", array("clientid" => $userid, "date" => "now()", "description" => "Credit Removed from Invoice #" . $id, "amount" => $removecredit));
                logActivity("Credit Removed - Amount: " . $removecredit . " - Invoice ID: " . $id, $userid);
                if ($status == "Paid") {
                    update_query("tblinvoices", array("status" => "Refunded"), array("id" => $id));
                }
                redir("action=edit&id=" . $id . "&creditremove=success&amt=" . $removecredit);
            }
        }
        if ($sub == "delete") {
            check_token("WHMCS.admin.default");
            delete_query("tblinvoiceitems", array("id" => $iid));
            updateInvoiceTotal($id);
            redir("action=edit&id=" . $id);
        }
        $gatewaysarray = getGatewaysArray();
        $data = (array) WHMCS\Database\Capsule::table("tblinvoices")->join("tblclients", "tblclients.id", "=", "tblinvoices.userid")->join("tblpaymentgateways", "tblpaymentgateways.gateway", "=", "tblinvoices.paymentmethod")->where("tblinvoices.id", $id)->where("tblpaymentgateways.setting", "=", "type")->first(array("tblinvoices.*", "tblclients.firstname", "tblclients.lastname", "tblclients.companyname", "tblclients.groupid", "tblclients.state", "tblclients.country", "tblpaymentgateways.value"));
        $paymentmethod = $data["paymentmethod"];
        $type = $data["value"];
        loadGatewayModule($paymentmethod);
        $initiatevscapture = false;
        if (function_exists($paymentmethod . "_initiatepayment")) {
            $initiatevscapture = true;
        }
        if ($publishInvoice) {
            check_token("WHMCS.admin.default");
            $invoice = WHMCS\Billing\Invoice::find($id);
            $invoice->status = "Unpaid";
            $invoice->dateCreated = WHMCS\Carbon::now();
            $invoice->save();
            $invoiceArr = array("source" => "adminarea", "user" => WHMCS\Session::get("adminid") ? WHMCS\Session::get("adminid") : "system", "invoiceid" => $id, "status" => "Unpaid");
            run_hook("InvoiceCreation", $invoiceArr);
            $paymentMethod = getClientsPaymentMethod($userid);
            $paymentType = WHMCS\Database\Capsule::table("tblpaymentgateways")->where("setting", "type")->where("gateway", $paymentMethod)->value("value");
            updateInvoiceTotal($id);
            logActivity("Modified Invoice Options - Invoice ID: " . $id, $userid);
            if ($publishAndSendEmail) {
                run_hook("InvoiceCreationPreEmail", $invoiceArr);
                $emailName = ($paymentType == "CC" || $paymentType == "OfflineCC" ? "Credit Card " : "") . "Invoice Created";
                sendMessage($emailName, $id);
            }
            redir("action=edit&id=" . $id);
        }
        if ($tplname) {
            check_token("WHMCS.admin.default");
            sendMessage($tplname, $id, "", true);
        }
        if ($type == "CC") {
            WHMCS\Session::start();
            $captureStatus = (bool) (int) App::getFromRequest("payment");
            if (App::isInRequest("payment")) {
                $stringPrefix = "capture";
                if ($initiatevscapture) {
                    $stringPrefix = "initiatepayment";
                }
                $infoBoxTitle = "invoices." . $stringPrefix . "successful";
                $infoBoxDescription = "invoices." . $stringPrefix . "successfulmsg";
                $infoBoxType = "success";
                if (!$captureStatus) {
                    $infoBoxTitle = "invoices." . $stringPrefix . "error";
                    $infoBoxDescription = "invoices." . $stringPrefix . "errormsg";
                    $infoBoxType = "error";
                }
                infoBox(AdminLang::trans($infoBoxTitle), AdminLang::trans($infoBoxDescription), $infoBoxType);
            }
        }
        $transid = App::getFromRequest("transid");
        if ($sub == "refund" && $transid) {
            check_token("WHMCS.admin.default");
            checkPermission("Refund Invoice Payments");
            logActivity("Admin Initiated Refund - Invoice ID: " . $id . " - Transaction ID: " . $transid);
            $amount = App::getFromRequest("amount");
            $sendemail = App::getFromRequest("sendemail");
            $refundtransid = App::getFromRequest("refundtransid");
            $refundtype = App::getFromRequest("refundtype");
            $reverse = (bool) (int) App::getFromRequest("reverse");
            $sendtogateway = $addascredit = false;
            if ($refundtype == "sendtogateway") {
                $sendtogateway = true;
            } else {
                if ($refundtype == "addascredit") {
                    $addascredit = true;
                }
            }
            $result = refundInvoicePayment($transid, $amount, $sendtogateway, $addascredit, $sendemail, $refundtransid, $reverse);
            $queryStr = "";
            if ($warning == "removeCredit") {
                $queryStr = "&transid=" . $transid . "&warning=" . $warning . "&invoiceCredit=" . $invoiceCredit;
            }
            redir("action=edit&id=" . $id . "&refundattempted=1" . $queryStr . "&refund_result_msg=" . $result);
        }
        if ($sub == "deletetrans") {
            check_token("WHMCS.admin.default");
            checkPermission("Delete Transaction");
            $ide = (int) App::getFromRequest("ide");
            $transaction = WHMCS\Billing\Payment\Transaction::find($ide);
            $userId = $transaction->clientId;
            $transaction->delete();
            logActivity("Deleted Transaction - Transaction ID: " . $ide, $userId);
            redir("action=edit&id=" . $id);
        }
        $jscode = "function showrefundtransid() {\n    var refundtype = \$(\"#refundtype\").val();\n    if (refundtype != \"\") {\n        \$(\"#refundtransid\").slideUp();\n    } else {\n        \$(\"#refundtransid\").slideDown();\n    }\n}";
        if ($refundattempted) {
            $refundResultMsg = App::getFromRequest("refund_result_msg");
            if ($refundResultMsg == "manual") {
                if ($warning == "removeCredit") {
                    removeOverpaymentCredit($userid, $transid, $invoiceCredit);
                }
                infoBox($aInt->lang("invoices", "refundsuccess"), $aInt->lang("invoices", "refundmanualsuccessmsg"));
            } else {
                if ($refundResultMsg == "amounterror") {
                    infoBox($aInt->lang("invoices", "refundfailed"), $aInt->lang("invoices", "refundamounterrormsg"));
                } else {
                    if ($refundResultMsg == "success") {
                        if ($warning == "removeCredit") {
                            removeOverpaymentCredit($userid, $transid, $invoiceCredit);
                        }
                        infoBox($aInt->lang("invoices", "refundsuccess"), $aInt->lang("invoices", "refundsuccessmsg"));
                    } else {
                        if ($refundResultMsg == "creditsuccess") {
                            if ($warning == "removeCredit") {
                                removeOverpaymentCredit($userid, $transid, $invoiceCredit);
                            }
                            infoBox($aInt->lang("invoices", "refundsuccess"), $aInt->lang("invoices", "refundcreditmsg"));
                        } else {
                            infoBox($aInt->lang("invoices", "refundfailed"), $aInt->lang("invoices", "refundfailedmsg"));
                        }
                    }
                }
            }
        }
        if ($creditapply == "exceedbalance") {
            infoBox($aInt->lang("global", "erroroccurred"), $aInt->lang("invoices", "exceedBalance"), "error");
        }
        if ($creditapply == "exceedtotal") {
            infoBox($aInt->lang("global", "erroroccurred"), $aInt->lang("invoices", "exceedTotal"), "error");
        }
        if ($creditapply == "success") {
            $clientCurrency = getCurrency($userid);
            infoBox($aInt->lang("global", "success"), sprintf($aInt->lang("invoices", "creditApplySuccess"), formatCurrency($amt, $clientCurrency["id"])), "success");
        }
        if ($creditremove == "exceedtotal") {
            infoBox($aInt->lang("global", "erroroccurred"), $aInt->lang("invoices", "exceedTotalRemove"), "error");
        }
        if ($creditremove == "success") {
            $clientCurrency = getCurrency($userid);
            infoBox($aInt->lang("global", "success"), sprintf($aInt->lang("invoices", "creditRemoveSuccess"), formatCurrency($amt, $clientCurrency["id"])), "success");
        }
        $failedData = array();
        if ($error == "validation") {
            $repopulateData = WHMCS\Cookie::get("ValidationError", true);
            $errorMessage = "";
            foreach ($repopulateData["validationError"] as $validationError) {
                $errorMessage .= WHMCS\Input\Sanitize::makeSafeForOutput($validationError) . "<br />";
            }
            if ($errorMessage) {
                infobox($aInt->lang("global", "validationerror"), $errorMessage, "error");
            }
            $failedData = $repopulateData["submission"];
            WHMCS\Cookie::delete("ValidationError");
        }
        echo $infobox;
        $id = $data["id"];
        $invoicenum = $data["invoicenum"];
        $date = $data["date"];
        $duedate = $data["duedate"];
        $datepaid = $data["datepaid"];
        $subtotal = $data["subtotal"];
        $credit = $data["credit"];
        $tax = $data["tax"];
        $tax2 = $data["tax2"];
        $total = $data["total"];
        $taxrate = $data["taxrate"];
        $taxrate2 = $data["taxrate2"];
        $status = $data["status"];
        $paymentmethod = $data["paymentmethod"];
        $payMethodId = $data["paymethodid"];
        $notes = $data["notes"];
        $userid = $data["userid"];
        $firstname = $data["firstname"];
        $lastname = $data["lastname"];
        $companyname = $data["companyname"];
        $groupid = $data["groupid"];
        $clientstate = $data["state"];
        $clientcountry = $data["country"];
        $date = fromMySQLDate($date);
        $duedate = fromMySQLDate($duedate);
        $datepaid = fromMySQLDate($datepaid, "time");
        $lastCaptureAttempt = $invoice->getData("last_capture_attempt");
        $payMethod = NULL;
        if ($payMethodId) {
            $payMethod = WHMCS\Payment\PayMethod\Model::find($payMethodId);
        }
        if (!$id) {
            $aInt->gracefulExit("Invoice ID Not Found");
        }
        $currency = getCurrency($userid);
        $result = select_query("tblaccounts", "COUNT(id),SUM(amountin)-SUM(amountout)", array("invoiceid" => $id));
        $data = mysql_fetch_array($result);
        list($transcount, $amountpaid) = $data;
        $balance = $total - $amountpaid;
        $balance = $rawbalance = sprintf("%01.2f", $balance);
        if ($status == "Unpaid") {
            $paymentmethodfriendly = $gatewaysarray[$paymentmethod];
        } else {
            if ($transcount == 0) {
                $paymentmethodfriendly = $aInt->lang("invoices", "notransapplied");
            } else {
                $paymentmethodfriendly = $gatewaysarray[$paymentmethod];
            }
        }
        if (0 < $credit) {
            if ($total == 0) {
                $paymentmethodfriendly = $aInt->lang("invoices", "fullypaidcredit");
            } else {
                $paymentmethodfriendly .= " + " . $aInt->lang("invoices", "partialcredit");
            }
        }
        $initiatevscapture = function_exists($paymentmethod . "_initiatepayment") ? true : false;
        $paymentGateways = new WHMCS\Module\Gateway();
        if ($paymentGateways->load($paymentmethod)) {
            $gatewayParams = getGatewayVariables($paymentmethod, $id);
            if (App::isInRequest("cancelpayment") && $paymentGateways->functionExists("cancel_payment")) {
                $historyId = (int) App::getFromRequest("cancelpayment");
                if ($historyId) {
                    $payment = WHMCS\Billing\Payment\Transaction\History::find($historyId);
                    if ($payment && $payment->invoiceId == $id) {
                        $gatewayParams["history"] = $payment;
                        $gatewayParams["cancelTransactionId"] = $payment->transactionId;
                        $response = $paymentGateways->call("cancel_payment", $gatewayParams);
                        if ($response && is_array($response)) {
                            echo WHMCS\View\Helper::alert($response["msg"], $response["type"]);
                            logTransaction($gatewayParams["paymentmethod"], $response["rawdata"], $response["status"]);
                        }
                        unset($gatewayParams["cancelTransactionId"]);
                        unset($gatewayParams["history"]);
                    }
                }
            }
            if ($paymentGateways->functionExists("adminstatusmsg")) {
                $response = $paymentGateways->call("adminstatusmsg", array_merge(array("invoiceid" => $id, "userid" => $userid, "date" => $date, "duedate" => $duedate, "datepaid" => $datepaid, "subtotal" => $subtotal, "tax" => $tax, "tax2" => $tax2, "total" => $total, "status" => $status), $gatewayParams));
                if ($response && is_array($response) && array_key_exists("msg", $response)) {
                    infoBox($response["title"], $response["msg"], $response["type"]);
                    echo $infobox;
                } else {
                    if ($response && is_array($response) && array_key_exists("alert", $response)) {
                        echo WHMCS\View\Helper::alert($response["alertText"], $response["type"]);
                    }
                }
            }
        }
        if ($status == "Draft") {
            echo WHMCS\View\Helper::alert(AdminLang::trans("invoices.draftInvoiceNotice"), "info");
        }
        $aInt->deleteJSConfirm("doDelete", "invoices", "deletelineitem", "?action=edit&id=" . $id . "&sub=delete&iid=");
        $aInt->deleteJSConfirm("doDeleteTransaction", "invoices", "deletetransaction", "?action=edit&id=" . $id . "&sub=deletetrans&ide=");
        run_hook("ViewInvoiceDetailsPage", array("invoiceid" => $id));
        $clientInvoiceLink = WHMCS\Utility\Environment\WebHelper::getBaseUrl() . "/viewinvoice.php?id=" . $id . "&view_as_client=1";
        echo "\n<div class=\"pull-right\">\n    <div class=\"btn-group btn-group-sm\" role=\"group\">\n        <button id=\"viewInvoiceAsClientButton\" type=\"button\" class=\"btn btn-default\" onclick=\"window.open('";
        echo $clientInvoiceLink;
        echo "','clientInvoice','')\">\n            <i class=\"fas fa-clipboard\"></i> ";
        echo $aInt->lang("invoices", "viewAsClient");
        echo "        </button>\n        <button id=\"printableVersionButton\" type=\"button\" class=\"btn btn-default\" onclick=\"window.open('../dl.php?type=i&amp;id=";
        echo $id;
        echo "&amp;viewpdf=1','pdfinv','')\"><i class=\"fas fa-print\"></i> ";
        echo $aInt->lang("invoices", "viewpdf");
        echo "</button>\n        <a href=\"../dl.php?type=i&amp;id=";
        echo $id;
        echo "\" class=\"btn btn-default\"><i class=\"fas fa-download\"></i> ";
        echo $aInt->lang("invoices", "downloadpdf");
        echo "</a>\n    </div>\n</div>\n\n<br />\n\n";
        echo $aInt->beginAdminTabs(array($aInt->lang("invoices", "summary"), $aInt->lang("invoices", "addpayment"), $aInt->lang("invoices", "options"), $aInt->lang("fields", "credit"), $aInt->lang("invoices", "refund"), $aInt->lang("fields", "notes")), true);
        if ($status == "Draft") {
            echo "<div class=\"context-btn-container\">\n    <form method=\"post\" action=\"invoices.php?action=edit&id=";
            echo $id;
            echo "\">\n        <input type=\"hidden\" name=\"publishInvoice\" value=\"1\">\n        <input type=\"submit\" id=\"inputPublish\" name=\"inputPublish\" value=\"";
            echo $aInt->lang("invoices", "publish");
            echo "\" class=\"btn btn-primary\">\n        <input type=\"submit\" id=\"inputPublishAndSendEmail\" name=\"inputPublishAndSendEmail\" value=\"";
            echo $aInt->lang("invoices", "publishAndSendEmail");
            echo "\" class=\"btn btn-warning\" />\n    </form>\n</div>\n";
        }
        echo "\n<table width=100%><tr><td width=50%>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"35%\" class=\"fieldlabel\">";
        echo $aInt->lang("fields", "clientname");
        echo "</td><td class=\"fieldarea\">";
        echo $aInt->outputClientLink($userid, $firstname, $lastname, $companyname, $groupid);
        echo " (<a href=\"clientsinvoices.php?userid=";
        echo $userid;
        echo "\">";
        echo $aInt->lang("invoices", "viewinvoices");
        echo "</a>)</td></tr>\n";
        if ($invoicenum) {
            echo "<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("fields", "invoicenum");
            echo "</td><td class=\"fieldarea\">";
            echo $invoicenum;
            echo "</td></tr>";
        }
        echo "<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "invoicedate");
        echo "</td><td class=\"fieldarea\">";
        echo $date;
        echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "duedate");
        echo "</td><td class=\"fieldarea\">";
        echo $duedate;
        echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "totaldue");
        echo "</td><td class=\"fieldarea\">";
        echo formatCurrency($credit + $total);
        echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "balance");
        echo "</td><td class=\"fieldarea\"><b>";
        if (0 < $rawbalance) {
            echo "<font color=#cc0000>" . formatCurrency($balance) . "</font>";
        } else {
            echo "<font color=#99cc00>" . formatCurrency($balance) . "</font>";
        }
        echo "</b></td></tr>\n</table>\n\n</td><td align=center width=50%>\n";
        if ($status == "Draft") {
            echo "    <span class=\"textgrey\" style=\"font-family:Arial;font-size:20px;font-weight:bold;text-transform:uppercase\">";
            echo $aInt->lang("status", "draft");
            echo "</span>\n";
        } else {
            if ($status == "Unpaid") {
                echo "    <span class=\"textred\" style=\"font-family:Arial;font-size:20px;font-weight:bold;text-transform:uppercase\">";
                echo $aInt->lang("status", "unpaid");
                echo "</span>\n    ";
                if ($type == "CC") {
                    echo "<br />" . AdminLang::trans("fields.lastCaptureAttempt") . ": <b>" . ($lastCaptureAttempt != "0000-00-00 00:00:00" ? fromMySQLDate($lastCaptureAttempt, true) : AdminLang::trans("global.none")) . "</b>";
                }
            } else {
                if ($status == "Paid") {
                    echo "    <span class=\"textgreen\" style=\"font-family:Arial;font-size:20px;font-weight:bold;text-transform:uppercase\">";
                    echo $aInt->lang("status", "paid");
                    echo "</span>\n    <br><b>";
                    echo $datepaid;
                    echo "</b>\n";
                } else {
                    if ($status == "Cancelled") {
                        echo "    <span class=\"textgrey\" style=\"font-family:Arial;font-size:20px;font-weight:bold;text-transform:uppercase\">";
                        echo $aInt->lang("status", "cancelled");
                        echo "</span>\n";
                    } else {
                        if ($status == "Refunded") {
                            echo "    <span class=\"textblue\" style=\"font-family:Arial;font-size:20px;font-weight:bold;text-transform:uppercase\">";
                            echo $aInt->lang("status", "refunded");
                            echo "</span>\n";
                        } else {
                            if ($status == "Collections") {
                                echo "    <span class=\"textgold\" style=\"font-family:Arial;font-size:20px;font-weight:bold;text-transform:uppercase\">";
                                echo $aInt->lang("status", "collections");
                                echo "</span>\n";
                            } else {
                                if ($status == "Payment Pending") {
                                    echo "    <span class=\"textgreen\" style=\"font-family:Arial;font-size:20px;font-weight:bold;text-transform:uppercase\">";
                                    echo AdminLang::trans("status.paymentpending");
                                    echo "</span>\n";
                                }
                            }
                        }
                    }
                }
            }
        }
        echo "            <br>\n            ";
        echo AdminLang::trans("fields.paymentmethod");
        echo ":\n            <strong>";
        echo $paymentmethodfriendly;
        echo "</strong>\n            ";
        if ($payMethod) {
            echo " - " . $payMethod->payment->getDisplayName();
        }
        echo "<br /><img src=\"images/spacer.gif\" width=\"1\" height=\"10\" /><br />\n<form method=\"post\" action=\"invoices.php?action=edit&id=";
        echo $id;
        echo "\" class=\"bottom-margin-5\">\n<select name=\"tplname\" class=\"form-control select-inline\">";
        $emailtplsarray = array();
        $invoiceMailTemplates = WHMCS\Mail\Template::where("type", "=", "invoice")->where("language", "=", "")->get();
        foreach ($invoiceMailTemplates as $template) {
            $emailtplsarray[$template->name] = $template->id;
        }
        $emailtplsoutput = array("Invoice Created", "Credit Card Invoice Created", "Invoice Payment Reminder", "First Invoice Overdue Notice", "Second Invoice Overdue Notice", "Third Invoice Overdue Notice", "Credit Card Payment Due", "Credit Card Payment Failed", "Invoice Payment Confirmation", "Credit Card Payment Confirmation", "Invoice Refund Confirmation");
        if ($status == "Paid") {
            $emailtplsoutput = array_merge(array("Invoice Payment Confirmation", "Credit Card Payment Confirmation"), $emailtplsoutput);
        }
        if ($status == "Refunded") {
            $emailtplsoutput = array_merge(array("Invoice Refund Confirmation"), $emailtplsoutput);
        }
        foreach ($emailtplsoutput as $tplname) {
            if (array_key_exists($tplname, $emailtplsarray)) {
                echo "<option>" . $tplname . "</option>";
                unset($emailtplsarray[$tplname]);
            }
        }
        foreach ($emailtplsarray as $tplname => $k) {
            echo "<option>" . $tplname . "</option>";
        }
        echo "    </select>\n    ";
        $captureButtonText = AdminLang::trans("invoices.attemptcapture");
        $captureDisabled = "";
        if ($initiatevscapture) {
            $captureButtonText = AdminLang::trans("invoices.initiatepayment");
        }
        if (in_array($status, array("Paid", "Cancelled")) || !function_exists($paymentmethod . "_capture") || $paymentmethod === "offlinecc") {
            $captureDisabled = " disabled=\"disabled\"";
        }
        $hasPayMethods = false;
        try {
            if ($invoiceModel instanceof WHMCS\Billing\Invoice) {
                $hasPayMethods = 0 < $invoiceModel->client->payMethods->count();
            }
        } catch (Exception $e) {
        }
        echo "    <input type=\"submit\" value=\"";
        echo $aInt->lang("global", "sendemail");
        echo "\" class=\"btn btn-default\"";
        if ($status == "Draft") {
            echo " disabled";
        }
        echo " />\n</form>\n<a href=\"";
        echo routePath("admin-client-invoice-capture", $userid, $id);
        echo "\"\n   class=\"btn btn-success open-modal\"";
        echo $captureDisabled;
        echo "   id=\"btnShowAttemptCaptureDialog\"\n   data-btn-submit-id=\"btnAttemptCapture\"\n   data-btn-submit-label=\"";
        echo $captureButtonText;
        echo "\"\n   data-modal-title=\"";
        echo $captureButtonText;
        echo "\"\n>\n    ";
        echo $captureButtonText;
        echo "</a>\n<input type=\"button\" value=\"";
        echo $aInt->lang("invoices", "markcancelled");
        echo "\" class=\"button btn btn-default\" onClick=\"window.location='";
        echo $whmcs->getPhpSelf();
        echo "?action=edit&id=";
        echo $id;
        echo "&sub=statuscancelled";
        echo generate_token("link");
        echo "';\"";
        if ($status == "Cancelled") {
            echo " disabled";
        }
        echo " />\n";
        $invoiceStatus = $invoice->getData("status");
        $invoiceBalance = $invoice->getData("balance");
        if ($invoiceStatus == "Unpaid" && (int) $invoiceBalance <= 0) {
            echo "        <button type=\"button\" value=\"";
            echo AdminLang::trans("invoices.markpaid");
            echo "\" onClick=\"window.location='";
            echo $whmcs->getPhpSelf();
            echo "?action=edit&id=";
            echo $id;
            echo "&sub=zeroPaid";
            echo generate_token("link");
            echo "';\" class=\"button btn btn-info\" data-toggle=\"tooltip\" data-placement=\"left\" title=\"";
            echo AdminLang::trans("invoices.zeroPaid");
            echo "\">\n            ";
            echo AdminLang::trans("invoices.markpaid");
            echo "        </button>\n        ";
        } else {
            echo "        <input type=\"button\" value=\"";
            echo AdminLang::trans("invoices.markunpaid");
            echo "\" onClick=\"window.location='";
            echo $whmcs->getPhpSelf();
            echo "?action=edit&id=";
            echo $id;
            echo "&sub=statusunpaid";
            echo generate_token("link");
            echo "';\" class=\"button btn btn-default\"";
            if ($status == "Unpaid") {
                echo " disabled";
            }
            echo " />\n        ";
        }
        echo "\n";
        $addons_html = run_hook("AdminInvoicesControlsOutput", array("invoiceid" => $id, "userid" => $userid, "subtotal" => $subtotal, "tax" => $tax, "tax2" => $tax2, "credit" => $credit, "total" => $total, "balance" => $balance, "taxrate" => $taxrate, "taxrate2" => $taxrate2, "paymentmethod" => $paymentmethod));
        foreach ($addons_html as $output) {
            echo $output;
        }
        echo "\n</td></tr></table>\n\n";
        echo $aInt->nextAdminTab();
        if ($status != "Cancelled" && $status != "Draft") {
            $duplicateTransactionModal = $aInt->modal("DuplicateTransaction", AdminLang::trans("transactions.duplicateTransaction"), AdminLang::trans("transactions.forceDuplicateTransaction"), array(array("title" => AdminLang::trans("global.continue"), "onclick" => "addInvoicePayment();return false;", "class" => "btn-danger"), array("title" => AdminLang::trans("global.cancel"), "onclick" => "cancelAddPayment();return false;")));
            echo "    <form method=\"post\" id=\"addPayment\" action=\"";
            echo $whmcs->getPhpSelf();
            echo "\">\n    <input type=\"hidden\" name=\"action\" value=\"edit\">\n    <input type=\"hidden\" name=\"id\" value=\"";
            echo $id;
            echo "\" id=\"invoiceId\">\n    <input type=\"hidden\" name=\"sub\" value=\"markpaid\">\n\n    ";
            if (0 < $total && $rawbalance <= 0) {
                infoBox($aInt->lang("invoices", "paidstatuscredit"), $aInt->lang("invoices", "paidstatuscreditdesc"));
                echo $infobox;
            }
            if ($failedData) {
                $paymentmethod = $failedData["paymentmethod"];
            }
            $paymentMethodDropDown = paymentMethodsSelection($aInt->lang("global", "none"));
            $addPaymentDate = $failedData ? $failedData["date"] : getTodaysDate();
            $addPaymentBalance = $failedData ? $failedData["amount"] : $rawbalance;
            $addPaymentFees = $failedData ? $failedData["fees"] : "0.00";
            $addPaymentTransId = $failedData ? $failedData["transid"] : "";
            $addPaymentSendConfirmationChecked = !$failedData || $failedData["sendconfirmation"] ? " checked " : "";
            echo "    <table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n        <tr>\n            <td width=\"20%\" class=\"fieldlabel\">\n                " . $aInt->lang("fields", "date") . "\n            </td>\n            <td class=\"fieldarea\">\n                <div class=\"form-group date-picker-prepend-icon\">\n                    <label for=\"inputDate\" class=\"field-icon\">\n                        <i class=\"fal fa-calendar-alt\"></i>\n                    </label>\n                    <input id=\"inputDate\"\n                           type=\"text\"\n                           name=\"date\"\n                           value=\"" . $addPaymentDate . "\"\n                           class=\"form-control date-picker-single\"\n                    />\n                </div>\n            </td>\n            <td width=\"20%\" class=\"fieldlabel\">\n                " . $aInt->lang("fields", "amount") . "\n            </td>\n            <td class=\"fieldarea\">\n                <div class=\"row\">\n                    <div class=\"col-xs-9 col-md-5\">\n                        <input type=\"text\" name=\"amount\" value=\"" . $addPaymentBalance . "\" class=\"form-control\">\n                    </div>\n                </div>\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                " . $aInt->lang("fields", "paymentmethod") . "\n            </td>\n            <td class=\"fieldarea\">\n                " . $paymentMethodDropDown . "\n            </td>\n            <td class=\"fieldlabel\">\n                " . $aInt->lang("fields", "fees") . "\n            </td>\n            <td class=\"fieldarea\">\n                <div class=\"row\">\n                    <div class=\"col-xs-9 col-md-5\">\n                        <input type=\"text\" name=\"fees\" value=\"" . $addPaymentFees . "\" class=\"form-control\">\n                    </div>\n                </div>\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                " . $aInt->lang("fields", "transid") . "\n            </td>\n            <td class=\"fieldarea\">\n                <div class=\"row\">\n                    <div class=\"col-xs-9\">\n                        <input type=\"text\" name=\"transid\" value=\"" . $addPaymentTransId . "\" class=\"form-control\">\n                    </div>\n                </div>\n            </td>\n            <td class=\"fieldlabel\">\n                " . $aInt->lang("global", "sendemail") . "\n            </td>\n            <td class=\"fieldarea\">\n                <label class=\"checkbox-inline\">\n                    <input type=\"checkbox\" name=\"sendconfirmation\" " . $addPaymentSendConfirmationChecked . " >\n                    " . $aInt->lang("invoices", "ticksendconfirmation") . "\n                </label>\n            </td>\n        </tr>\n    </table>\n    <div class=\"btn-container\">\n        <button id=\"btnAddPayment\" type=\"submit\" class=\"btn btn-primary\">\n            <span id=\"paymentText\">\n                " . $aInt->lang("invoices", "addpayment") . "\n            </span>\n            <span id=\"paymentLoading\" class=\"hidden\">\n                <i class=\"fas fa-spinner fa-spin\"></i> " . $aInt->lang("global", "loading") . "\n            </span>\n        </button>\n    </div>\n    </form>";
        } else {
            $phpSelf = $whmcs->getPhpSelf();
            $token = generate_token("link");
            if ($status == "Draft") {
                $publishText = $aInt->lang("invoices", "publish");
                $publishLink = "<a href=\"" . $phpSelf . "?action=edit&id=" . $id . "&tab=1\">\n    " . $publishText . "\n</a>";
                infoBox($aInt->lang("invoices", "invoiceIsDraft"), sprintf($aInt->lang("invoices", "invoiceIsCancelledDescription"), $publishLink));
            } else {
                $markUnpaid = $aInt->lang("invoices", "markunpaid");
                $markPaidLink = "<a href=\"" . $phpSelf . "?action=edit&id=" . $id . "&sub=statusunpaid&tab=1" . $token . "\">\n    " . $markUnpaid . "\n</a>";
                infoBox($aInt->lang("invoices", "invoiceIsCancelled"), sprintf($aInt->lang("invoices", "invoiceIsCancelledDescription"), $markPaidLink));
            }
            echo $infobox;
        }
        echo $aInt->nextAdminTab();
        echo "\n<form method=\"post\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "\">\n<input type=\"hidden\" name=\"action\" value=\"edit\">\n<input type=\"hidden\" name=\"saveoptions\" value=\"true\">\n<input type=\"hidden\" name=\"id\" value=\"";
        echo $id;
        echo "\" id=\"invoiceId\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr>\n    <td width=\"20%\" class=\"fieldlabel\">\n        ";
        echo $aInt->lang("fields", "invoicedate");
        echo "    </td>\n    <td class=\"fieldarea\">\n        <div class=\"form-group date-picker-prepend-icon\">\n            <label for=\"inputInvoiceDate\" class=\"field-icon\">\n                <i class=\"fal fa-calendar-alt\"></i>\n            </label>\n            <input id=\"inputInvoiceDate\"\n                   type=\"text\"\n                   name=\"invoicedate\"\n                   value=\"";
        echo $date;
        echo "\"\n                   class=\"form-control date-picker-single\"\n            />\n        </div>\n    </td>\n    <td width=\"20%\" class=\"fieldlabel\">\n        ";
        echo $aInt->lang("fields", "duedate");
        echo "    </td>\n    <td class=\"fieldarea\">\n        <div class=\"form-group date-picker-prepend-icon\">\n            <label for=\"inputDateDue\" class=\"field-icon\">\n                <i class=\"fal fa-calendar-alt\"></i>\n            </label>\n            <input id=\"inputDateDue\"\n                   type=\"text\"\n                   name=\"datedue\"\n                   value=\"";
        echo $duedate;
        echo "\"\n                   class=\"form-control date-picker-single future\"\n            />\n        </div>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "paymentmethod");
        echo "</td><td class=\"fieldarea\">";
        echo paymentMethodsSelection();
        echo "</td><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "taxrate");
        echo "</td><td class=\"fieldarea\"><div class=\"form-inline\">\n    <div class=\"input-group\">\n        <div class=\"input-group-addon\">1</div>\n        <input type=\"text\" name=\"taxrate\" value=\"";
        echo $taxrate;
        echo "\" class=\"form-control\" style=\"width:80px;\">\n        <div class=\"input-group-addon\">%</div>\n    </div>\n\n    <div class=\"input-group\">\n        <div class=\"input-group-addon\">2</div>\n        <input type=\"text\" name=\"taxrate2\" value=\"";
        echo $taxrate2;
        echo "\" class=\"form-control\" style=\"width:80px;\">\n        <div class=\"input-group-addon\">%</div>\n    </div>\n</div></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "invoicenum");
        echo "</td><td class=\"fieldarea\"><div class=\"row\"><div class=\"col-xs-9\"><input type=\"text\" name=\"invoicenum\" value=\"";
        echo $invoicenum;
        echo "\" class=\"form-control\"></div></div></td><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "status");
        echo "</td><td class=\"fieldarea\"><select name=\"status\" class=\"form-control select-inline\">\n";
        foreach (WHMCS\Invoices::getInvoiceStatusValues() as $invoiceStatusOption) {
            $isSelected = $status == $invoiceStatusOption;
            echo "<option value=\"" . $invoiceStatusOption . "\"" . ($isSelected ? " selected" : "") . ">" . $aInt->lang("status", strtolower(str_replace(" ", "", $invoiceStatusOption))) . "</option>";
        }
        echo "</select></td></tr>\n</table>\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
        echo $aInt->lang("global", "savechanges");
        echo "\" class=\"button btn btn-primary\">\n</div>\n</form>\n\n";
        echo $aInt->nextAdminTab();
        echo "\n";
        $totalcredit = get_query_val("tblclients", "credit", array("id" => $userid));
        echo "<table width=75% align=\"center\">\n<tr><td width=50% align=\"center\"><b>";
        echo $aInt->lang("invoices", "addcredit");
        echo "</b></td><td align=center><b>";
        echo $aInt->lang("invoices", "removecredit");
        echo "</b></td></tr>\n<tr><td align=center><font color=#377D0D>";
        echo formatCurrency($totalcredit);
        echo " ";
        echo $aInt->lang("invoices", "creditavailable");
        echo "</font></td><td align=center><font color=#cc0000>";
        echo formatCurrency($credit);
        echo " ";
        echo $aInt->lang("invoices", "creditavailable");
        echo "</font></td></tr>\n<tr><td align=center><form method=\"post\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "\"><input type=\"hidden\" name=\"action\" value=\"edit\"><input type=\"hidden\" name=\"id\" value=\"";
        echo $id;
        echo "\"><input type=\"text\" name=\"addcredit\" value=\"";
        echo $balance <= $totalcredit ? $balance : $totalcredit;
        echo "\" class=\"form-control input-100 input-inline\"";
        if ($totalcredit == "0.00") {
            echo " disabled";
        }
        echo "> <input type=\"submit\" value=\"";
        echo $aInt->lang("global", "go");
        echo "\" class=\"btn";
        if ($totalcredit == "0.00") {
            echo " disabled";
        }
        echo "\"";
        if ($totalcredit == "0.00") {
            echo " disabled";
        }
        echo "></form></td><td align=center><form method=\"post\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "\"><input type=\"hidden\" name=\"action\" value=\"edit\"><input type=\"hidden\" name=\"id\" value=\"";
        echo $id;
        echo "\"><input type=\"text\" name=\"removecredit\" value=\"0.00\" class=\"form-control input-100 input-inline\"";
        if ($credit == "0.00") {
            echo " disabled";
        }
        echo "> <input type=\"submit\" value=\"";
        echo $aInt->lang("global", "go");
        echo "\" class=\"btn";
        if ($credit == "0.00") {
            echo " disabled";
        }
        echo "\"";
        if ($credit == "0.00") {
            echo " disabled";
        }
        echo "></form></td></tr>\n</table>\n</form>\n\n";
        echo $aInt->nextAdminTab();
        echo "\n";
        $numtrans = get_query_vals("tblaccounts", "COUNT(id)", array("invoiceid" => $id, "amountin" => array("sqltype" => ">", "value" => "0")), "date` ASC,`id", "ASC");
        $notransactions = $numtrans[0] == "0" ? true : false;
        echo "<form method=\"post\" id=\"transactions\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "\">\n<input type=\"hidden\" name=\"action\" value=\"edit\">\n<input type=\"hidden\" name=\"id\" value=\"";
        echo $id;
        echo "\">\n<input type=\"hidden\" name=\"sub\" value=\"refund\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"20%\" class=\"fieldlabel\">";
        echo $aInt->lang("invoices", "transactions");
        echo "</td><td class=\"fieldarea\"><select id=\"transid\" name=\"transid\" class=\"form-control select-inline\">";
        $result = select_query("tblaccounts", "", array("invoiceid" => $id, "amountin" => array("sqltype" => ">", "value" => "0")), "date` ASC,`id", "ASC");
        $transArr = array();
        while ($data = mysql_fetch_array($result)) {
            $trans_id = $data["id"];
            $trans_date = $data["date"];
            $trans_amountin = $data["amountin"];
            $transArr[$trans_id] = $trans_amountin;
            $trans_transid = $data["transid"];
            $trans_date = fromMySQLDate($trans_date);
            $trans_amountin = formatCurrency($trans_amountin);
            echo "<option value=\"" . $trans_id . "\">" . $trans_date . " | " . $trans_transid . " | " . $trans_amountin . "</option>";
            $transInvoice = $data;
        }
        if ($notransactions) {
            echo "<option value=\"\">" . $aInt->lang("invoices", "notransactions") . "</option>";
        }
        echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "amount");
        echo "</td><td class=\"fieldarea\"><div class=\"row\"><div class=\"col-xs-3 col-md-2\"><input type=\"text\" name=\"amount\" id=\"amount\" class=\"form-control\" placeholder=\"0.00\" /></div><div class=\"col-xs-9 form-control-static\">Leave blank for full refund</div></div></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("invoices", "refundtype");
        echo "</td><td class=\"fieldarea\"><select name=\"refundtype\" id=\"refundtype\" class=\"form-control select-inline\" onchange=\"showrefundtransid();return false\"><option value=\"sendtogateway\">";
        echo $aInt->lang("invoices", "refundtypegateway");
        echo "</option><option value=\"\" type=\"\">";
        echo $aInt->lang("invoices", "refundtypemanual");
        echo "</option><option value=\"addascredit\">";
        echo $aInt->lang("invoices", "refundtypecredit");
        echo "</option></select></td></tr>\n<tr id=\"refundtransid\" style=\"display:none;\" ><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "transid");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"refundtransid\" size=\"25\" /></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
        echo AdminLang::trans("invoices.reverse");
        echo "    </td>\n    <td class=\"fieldarea\">\n        <label class=\"checkbox-inline\">\n            <input type=\"hidden\" name=\"reverse\" value=\"0\" />\n            <input type=\"checkbox\" name=\"reverse\" value=\"1\" /> ";
        echo AdminLang::trans("invoices.reverseDescription");
        echo "        </label>\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
        echo $aInt->lang("global", "sendemail");
        echo "    </td>\n    <td class=\"fieldarea\">\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"sendemail\" checked> ";
        echo $aInt->lang("invoices", "ticksendconfirmation");
        echo "        </label>\n    </td>\n</tr>\n";
        if (isset($transInvoice["invoiceid"])) {
            $invoiceCredit = WHMCS\Database\Capsule::table("tblcredit")->where("relid", $transInvoice["invoiceid"])->sum("amount");
            if (0 < $invoiceCredit) {
                $creditGiven = true;
                echo "<tbody id='creditArea'>\n";
                $labelText = $aInt->lang("invoices", "invoiceCreditResult") . formatCurrency($invoiceCredit) . ". " . $aInt->lang("invoices", "currentCreditBalance") . formatCurrency($totalcredit) . ".";
                echo "<tr><td class=\"fieldlabel\"><font color=\"#cc0000\">WARNING</font></td><td class=\"fieldarea\">" . $labelText . "</td></tr>" . "\n";
                if ($totalcredit < $invoiceCredit) {
                    $labelText = $aInt->lang("invoices", "cannotRemoveCredit");
                    $checkboxText = "<strong>" . $aInt->lang("invoices", "cannotRemoveCreditAck") . "</strong>";
                } else {
                    $labelText = $aInt->lang("invoices", "creditCanBeRemoved");
                    $radioButtons = array("removeCredit" => "<strong>" . $aInt->lang("invoices", "removeCreditFirst") . "</strong>", "leaveCredit" => "<strong>" . $aInt->lang("invoices", "leaveCreditUntouched") . "</strong>");
                }
                echo "<tr><td class=\"fieldlabel\"></td><td class=\"fieldarea\">" . $labelText . "</td></tr>" . "\n";
                if (isset($checkboxText)) {
                    echo "<tr><td class=\"fieldlabel\"></td>";
                    echo "<td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" id=\"warning\" name=\"warning\" value=\"leaveCredit\" onclick=\"selectRefundChoice(this);\">" . $checkboxText . "</label></td>";
                    echo "</tr>\n";
                } else {
                    if (is_array($radioButtons)) {
                        foreach ($radioButtons as $key => $button) {
                            echo "<tr><td class=\"fieldlabel\"></td>";
                            echo "<td class=\"fieldarea\"><label class=\"radio-inline\"><input type=\"radio\" id=\"warning_" . $key . "\" name=\"warning\" value=\"" . $key . "\" onclick=\"selectRefundChoice(this);\">" . $button . "</label></td>";
                            echo "</tr>\n";
                        }
                    }
                }
                echo "<input type=\"hidden\" name=\"invoiceCredit\" id=\"invoiceCredit\" value=\"" . $invoiceCredit . "\">" . "\n";
                echo "</tbody>\n";
            }
        }
        if (!isset($invoiceCredit) || !is_numeric($invoiceCredit)) {
            $invoiceCredit = 0;
        }
        $transAmountObjectTxt = "";
        foreach ($transArr as $k => $v) {
            $transAmountObjectTxt .= "       transAmountObj._" . $k . " = " . $v . ";\n";
        }
        $aInt->jquerycode .= "\$(\"#transactions\").submit(function(e) {" . "\n" . "   var credit = " . $invoiceCredit . ";" . "\n" . "   var choice = \$(\"input[id^=warning]:checked\", \"#transactions\").val();" . "\n" . "   if (credit > 0 && choice != \"leaveCredit\") {" . "\n" . "       var amount = \$(\"#amount\").val();" . "\n" . "       amount = amount.replace(/^\\s*/, \"\").replace(/\\s*\$/, \"\");" . "\n" . "       " . "\n" . "       // Grab the amount from the combobox choice." . "\n" . "       var selectedId = \"_\" + \$(\"#transid\").find(\"option:selected\").val();" . "\n" . "       var transAmountObj = new Object();" . "\n" . $transAmountObjectTxt . "\n" . "       var transAmount = transAmountObj[selectedId];" . "\n" . "       " . "\n" . "       if (amount === \"\") {" . "\n" . "           // Field was left blank." . "\n" . "           // Return the entire amount." . "\n" . "           amount = transAmount;" . "\n" . "       }" . "\n" . "       " . "\n" . "       var removeCreditAmount;" . "\n" . "       if (amount < credit) {" . "\n" . "           // Only remove some of the credit." . "\n" . "           removeCreditAmount = amount;" . "\n" . "       } else if (amount >= credit) {" . "\n" . "           // Remove all credit." . "\n" . "           removeCreditAmount = credit;" . "\n" . "       } else {" . "\n" . "           // We do not have numbers." . "\n" . "           return;" . "\n" . "       }" . "\n" . "       " . "\n" . "       // Update the hidden credit field." . "\n" . "       \$(\"#invoiceCredit\").val(removeCreditAmount);" . "\n" . "   }" . "\n" . "});\n";
        $aInt->jquerycode .= "\n    jQuery(\"#addPayment\").submit(function(event) {\n        // Only allow the first submission.\n        if (jQuery(this).data(\"alreadySent\") === true) {\n            event.preventDefault();\n        } else {\n            jQuery(this).data(\"alreadySent\", true);\n        }\n    });";
        echo "</table>\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
        echo $aInt->lang("invoices", "refund");
        echo "\" class=\"btn btn-default\" id=\"refundBtn\"";
        if ($notransactions || $creditGiven) {
            echo " disabled";
        }
        echo ">\n</div>\n</form>\n\n";
        echo $aInt->nextAdminTab();
        echo "\n<form method=\"post\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "?save=notes\">\n<input type=\"hidden\" name=\"action\" value=\"edit\">\n<input type=\"hidden\" name=\"id\" value=\"";
        echo $id;
        echo "\">\n<textarea rows=4 style=\"width:100%\" name=\"notes\" class=\"form-control\">";
        echo $notes;
        echo "</textarea>\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
        echo $aInt->lang("global", "savechanges");
        echo "\" class=\"btn btn-primary\">\n</div>\n</form>\n\n";
        echo $aInt->endAdminTabs();
        echo "\n<script language=\"JavaScript\">\nfunction selectRefundChoice(selection)\n{\n    if (selection.checked) {\n        // A choice was made.\n        // Enable the refund button.\n        \$(\"#refundBtn\").removeAttr(\"disabled\");\n    } else {\n        // Checkbox was unchecked.\n        // Disable the refund button.\n        \$(\"#refundBtn\").prop(\"disabled\", \"disabled\");\n    }\n}\n</script>\n\n<h2>";
        echo $aInt->lang("invoices", "items");
        echo "</h2>\n<form method=\"post\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "\">\n<input type=\"hidden\" name=\"action\" value=\"edit\">\n<input type=\"hidden\" name=\"id\" value=\"";
        echo $id;
        echo "\">\n<input type=\"hidden\" name=\"userid\" value=\"";
        echo $userid;
        echo "\">\n<input type=\"hidden\" name=\"sub\" value=\"save\">\n\n<div class=\"tablebg\">\n<table class=\"datatable\" width=\"100%\" border=\"0\" cellspacing=\"1\" cellpadding=\"3\">\n<tr><th width=\"20\"></th><th>";
        echo $aInt->lang("fields", "description");
        echo "</th><th width=\"120\">";
        echo $aInt->lang("fields", "amount");
        echo "</th><th width=\"70\">";
        echo $aInt->lang("fields", "taxed");
        echo "</th><th width=\"20\"></th></tr>\n";
        $result = select_query("tblinvoiceitems", "", array("invoiceid" => $id), "id", "ASC");
        while ($data = mysql_fetch_array($result)) {
            $lineid = $data["id"];
            $description = $data["description"];
            $linecount = explode("\n", $description);
            $linecount = count($linecount);
            echo "<tr><td width=\"20\" align=\"center\"><input type=\"checkbox\" name=\"itemids[]\" value=\"" . $lineid . "\" /></td><td><textarea name=\"description[" . $lineid . "]\" rows=\"" . $linecount . "\" class=\"form-control\">" . $description . "</textarea></td><td align=center nowrap><input type=\"text\" name=\"amount[" . $lineid . "]\" value=\"" . $data["amount"] . "\" style=\"text-align:center\" class=\"form-control\"></td><td align=center><input type=\"checkbox\" name=\"taxed[" . $lineid . "]\" value=\"1\"";
            if ($data["taxed"] == "1") {
                echo " checked";
            }
            echo "><td width=\"20\" align=\"center\"><a href=\"#\" onClick=\"doDelete('" . $lineid . "');return false\"><img src=\"images/delete.gif\" border=\"0\"></a></td></tr>";
        }
        echo "<tr><td width=\"20\"></td><td><textarea name=\"adddescription\" rows=\"1\" class=\"form-control\"></textarea></td><td align=center><input type=\"text\" name=\"addamount\" style=\"text-align:center\" class=\"form-control\"></td><td align=center><input type=\"checkbox\" name=\"addtaxed\" value=\"1\"" . ($CONFIG["TaxEnabled"] && $CONFIG["TaxCustomInvoices"] ? " checked" : "") . "></td><td>&nbsp;</td></tr>";
        echo "<tr><td colspan=\"2\" style=\"text-align:right;background-color:#efefef;\"><div align=\"left\" style=\"width:60%;float:left;\"><select name=\"selaction\" onchange=\"this.form.submit()\"><option>- ";
        echo $aInt->lang("global", "withselected");
        echo " -</option><option value=\"split\">";
        echo $aInt->lang("invoices", "split");
        echo "</option><option value=\"delete\">";
        echo $aInt->lang("global", "delete");
        echo "</option></select></div><div style=\"width:25%;float:right;line-height:22px;\"><strong>";
        echo $aInt->lang("fields", "subtotal");
        echo ":</strong>&nbsp;</div></td><td style=\"background-color:#efefef;text-align:center;\"><strong>";
        echo formatCurrency($subtotal);
        echo "</strong></td><td style=\"background-color:#efefef;\">&nbsp;</td><td style=\"background-color:#efefef;\">&nbsp;</td></tr>\n";
        if ($CONFIG["TaxEnabled"] == "on") {
            if ($taxrate != "0.00") {
                echo "<tr><td colspan=\"2\" style=\"text-align:right;background-color:#efefef;\">";
                echo $taxrate;
                echo "% ";
                $taxdata = getTaxRate(1, $clientstate, $clientcountry);
                echo $taxdata["name"] ? $taxdata["name"] : $aInt->lang("invoices", "taxdue");
                echo ":&nbsp;</td><td style=\"background-color:#efefef;text-align:center;\">";
                echo formatCurrency($tax);
                echo "</td><td style=\"background-color:#efefef;\">&nbsp;</td><td style=\"background-color:#efefef;\">&nbsp;</td></tr>";
            }
            if ($taxrate2 != "0.00") {
                echo "<tr><td colspan=\"2\" style=\"text-align:right;background-color:#efefef;\">";
                echo $taxrate2;
                echo "% ";
                $taxdata = getTaxRate(2, $clientstate, $clientcountry);
                echo $taxdata["name"] ? $taxdata["name"] : $aInt->lang("invoices", "taxdue");
                echo ":&nbsp;</td><td style=\"background-color:#efefef;text-align:center;\">";
                echo formatCurrency($tax2);
                echo "</td><td style=\"background-color:#efefef;\">&nbsp;</td><td style=\"background-color:#efefef;\">&nbsp;</td></tr>";
            }
        }
        echo "<tr><td colspan=\"2\" style=\"text-align:right;background-color:#efefef;\">";
        echo $aInt->lang("fields", "credit");
        echo ":&nbsp;</td><td style=\"background-color:#efefef;text-align:center;\">";
        echo formatCurrency($credit);
        echo "</td><td style=\"background-color:#efefef;\">&nbsp;</td><td style=\"background-color:#efefef;\">&nbsp;</td></tr>\n<tr><th colspan=\"2\" style=\"text-align:right;\">";
        echo $aInt->lang("fields", "totaldue");
        echo ":&nbsp;</th><th>";
        echo formatCurrency($total);
        echo "</th><th></th><th></th></tr>\n</table>\n</div>\n<p align=center><input type=\"submit\" value=\"";
        echo $aInt->lang("global", "savechanges");
        echo "\" class=\"btn btn-primary\" /> <input type=\"reset\" value=\"";
        echo $aInt->lang("global", "cancelchanges");
        echo "\" class=\"button btn btn-default\" /></p>\n</form>\n\n<h2>";
        echo $aInt->lang("invoices", "transactions");
        echo "</h2>\n\n";
        $aInt->sortableTableInit("nopagination");
        $paymentGateways = new WHMCS\Gateways();
        $transactions = array();
        $paymentTransactions = WHMCS\Billing\Payment\Transaction::where("invoiceid", "=", (int) $id)->orderBy("date")->orderBy("id")->get();
        foreach ($paymentTransactions as $transaction) {
            $paymentmethod = "";
            if ($transaction->paymentGateway) {
                $paymentmethod = $paymentGateways->getDisplayName($transaction->paymentGateway);
            }
            if (!$paymentmethod) {
                $paymentmethod = "-";
            }
            $transactions[(string) $transaction->date][] = array(fromMySQLDate($transaction->date, 1), $paymentmethod, $transaction->transactionId, formatCurrency($transaction->amountin - $transaction->amountout), formatCurrency($transaction->fees), "<a href=\"#\" onClick=\"doDeleteTransaction('" . $transaction->id . "');return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"Delete\"></a>");
        }
        $creditTransactions = WHMCS\Database\Capsule::table("tblcredit")->where("description", "LIKE", "%Invoice #" . (int) $id)->get();
        foreach ($creditTransactions as $transaction) {
            if (0 < $transaction->amount) {
                if (strpos($transaction->description, "Overpayment") !== false || strpos($transaction->description, "Mass Invoice Payment Credit") !== false) {
                    continue;
                }
                $creditMsg = AdminLang::trans("invoices.creditRemoved");
            } else {
                $creditMsg = AdminLang::trans("invoices.creditApplied");
            }
            $transactions[$transaction->date . " 25:59:59"][] = array(fromMySQLDate($transaction->date), $creditMsg, "-", formatCurrency($transaction->amount * -1), "-", "");
        }
        ksort($transactions);
        foreach ($transactions as $date => $trans) {
            foreach ($trans as $transaction) {
                $tabledata[] = $transaction;
            }
        }
        echo $aInt->sortableTable(array($aInt->lang("fields", "date"), $aInt->lang("fields", "paymentmethod"), $aInt->lang("fields", "transid"), $aInt->lang("fields", "amount"), $aInt->lang("fields", "fees"), ""), $tabledata);
        $log = WHMCS\Billing\Payment\Transaction\History::where("invoice_id", $id)->get();
        echo "<h2>" . AdminLang::trans("invoices.transactionsHistory") . "</h2>";
        $tableData = array();
        foreach ($log as $transactionHistory) {
            $tableData[] = array($transactionHistory->updatedAt->toAdminDateTimeFormat(), $paymentGateways->getDisplayName($transactionHistory->gateway), "<a href=\"gatewaylog.php?history=" . $transactionHistory->id . "\">" . $transactionHistory->transactionId . "</a>", $transactionHistory->remoteStatus, $transactionHistory->description);
        }
        echo $aInt->sortableTable(array(AdminLang::trans("fields.date"), AdminLang::trans("fields.paymentmethod"), AdminLang::trans("fields.transid"), AdminLang::trans("fields.status"), AdminLang::trans("fields.description")), $tableData);
    }
}
if (!empty($duplicateTransactionModal)) {
    echo $duplicateTransactionModal;
}
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jscode = $jscode;
$aInt->display();

?>