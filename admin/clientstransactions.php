<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
if ($action == "add") {
    $reqperm = "Add Transaction";
} else {
    if ($action == "edit") {
        $reqperm = "Edit Transaction";
    } else {
        $reqperm = "List Transactions";
    }
}
$aInt = new WHMCS\Admin($reqperm);
$aInt->requiredFiles(array("gatewayfunctions", "invoicefunctions"));
$aInt->setClientsProfilePresets();
$error = $whmcs->get_req_var("error");
$userid = $aInt->valUserID($whmcs->get_req_var("userid"));
$aInt->assertClientBoundary($userid);
if ($sub == "add") {
    check_token("WHMCS.admin.default");
    $paymentMethod = $whmcs->get_req_var("paymentmethod");
    $invoiceID = $whmcs->get_req_var("invoiceid");
    $transactionID = $whmcs->get_req_var("transid");
    $amountIn = $whmcs->get_req_var("amountin");
    $fees = $whmcs->get_req_var("fees");
    $date = $whmcs->get_req_var("date");
    $amountOut = $whmcs->get_req_var("amountout");
    $description = $whmcs->get_req_var("description");
    $addCredit = $whmcs->get_req_var("addcredit");
    $validationError = false;
    $validationErrorDescription = array();
    if ($amountIn < 0) {
        $validationError = true;
        $validationErrorDescription[] = $aInt->lang("transactions", "amountInLessThanZero") . PHP_EOL;
    }
    if ($amountOut < 0) {
        $validationError = true;
        $validationErrorDescription[] = $aInt->lang("transactions", "amountOutLessThanZero") . PHP_EOL;
    }
    if (!$invoiceID && !$description) {
        $validationError = true;
        $validationErrorDescription[] = $aInt->lang("transactions", "invoiceIdOrDescriptionRequired") . PHP_EOL;
    }
    if ((!$amountOut || $amountOut == 0) && (!$amountIn || $amountIn == 0) && (!$fees || $fees == 0)) {
        $validationError = true;
        $validationErrorDescription[] = $aInt->lang("transactions", "amountInOutOrFeeRequired") . PHP_EOL;
    }
    $validate = new WHMCS\Validate();
    $invalidFormatLangKey = array("transactions", "amountOrFeeInvalidFormat");
    if ($amountIn && !$validate->validate("decimal", "amountin", $invalidFormatLangKey) || $amountOut && !$validate->validate("decimal", "amountout", $invalidFormatLangKey) || $fees && !$validate->validate("decimal", "fees", $invalidFormatLangKey)) {
        $validationError = true;
        $validationErrorDescription[] = implode(PHP_EOL, array_unique($validate->getErrors())) . PHP_EOL;
    }
    if ($amountIn && $fees && $amountIn < $fees) {
        $validationError = true;
        $validationErrorDescription[] = $aInt->lang("transactions", "feeMustBeLessThanAmountIn") . PHP_EOL;
    }
    if ($amountIn && $fees && $fees < 0) {
        $validationError = true;
        $validationErrorDescription[] = $aInt->lang("transactions", "amountInFeeMustBePositive") . PHP_EOL;
    }
    if (0 < $amountIn && 0 < $amountOut) {
        $validationError = true;
        $validationErrorDescription[] = $aInt->lang("transactions", "amountInFeeMustBePositive") . PHP_EOL;
    }
    if ($addCredit && 0 < $amountOut) {
        $validationError = true;
        $validationErrorDescription[] = $aInt->lang("transactions", "amountOutCannotBeUsedWithAddCredit") . PHP_EOL;
    }
    if ($addCredit && $invoiceID) {
        $validationError = true;
        $validationErrorDescription[] = $aInt->lang("transactions", "invoiceIDAndCreditInvalid") . PHP_EOL;
    }
    if ($transactionID && !isUniqueTransactionID($transactionID, $paymentMethod)) {
        $validationError = true;
        $validationErrorDescription[] = $aInt->lang("transactions", "requireUniqueTransaction") . PHP_EOL;
    }
    if ($validationError) {
        WHMCS\Cookie::set("ValidationError", array("invoiceid" => $invoiceID, "transid" => $transactionID, "amountin" => $amountIn, "fees" => $fees, "paymentmethod" => $paymentMethod, "date" => $date, "amountout" => $amountOut, "description" => $description, "addcredit" => $addCredit, "validationError" => $validationErrorDescription));
        redir(array("userid" => $userid, "error" => "validation", "action" => "add"));
    }
    if ($invoiceID) {
        $transactionUserID = get_query_val("tblinvoices", "userid", array("id" => $invoiceID));
        if (!$transactionUserID) {
            redir("error=invalidinvid");
        } else {
            if ($transactionUserID != $userid) {
                redir("error=wronguser");
            }
        }
        addInvoicePayment($invoiceID, $transactionID, $amountIn, $fees, $paymentMethod, "", $date);
    } else {
        addTransaction($userid, 0, $description, $amountIn, $fees, $amountOut, $paymentMethod, $transactionID, $invoiceID, $date);
    }
    if ($addCredit) {
        if ($transactionID) {
            $description .= " (Trans ID: " . $transactionID . ")";
        }
        insert_query("tblcredit", array("clientid" => $userid, "date" => toMySQLDate($date), "description" => $description, "amount" => $amountIn));
        update_query("tblclients", array("credit" => "+=" . $amountIn), array("id" => (int) $userid));
    }
    redir("userid=" . $userid);
}
if ($sub == "save") {
    check_token("WHMCS.admin.default");
    update_query("tblaccounts", array("gateway" => $paymentmethod, "date" => toMySQLDate($date), "description" => $description, "amountin" => $amountin, "fees" => $fees, "amountout" => $amountout, "transid" => $transid, "invoiceid" => $invoiceid), array("id" => $id));
    logActivity("Modified Transaction (User ID: " . $userid . " - Transaction ID: " . $id . ")", $userid);
    redir("userid=" . $userid);
}
if ($sub == "delete") {
    check_token("WHMCS.admin.default");
    checkPermission("Delete Transaction");
    $ide = (int) $whmcs->get_req_var("ide");
    $transaction = WHMCS\User\Client::find($userid)->transactions->find($ide);
    if ($transaction) {
        $transaction->delete();
        logActivity("Deleted Transaction (ID: " . $ide . " - User ID: " . $userid . ")", $userid);
    }
    redir("userid=" . $userid);
}
ob_start();
if ($action == "") {
    $aInt->deleteJSConfirm("doDelete", "transactions", "deletesure", "clientstransactions.php?userid=" . $userid . "&sub=delete&ide=");
    $currency = getCurrency($userid);
    if ($error == "invalidinvid") {
        infoBox($aInt->lang("invoices", "checkInvoiceID"), $aInt->lang("invoices", "invalidInvoiceID"), "error");
    } else {
        if ($error == "wronguser") {
            infoBox($aInt->lang("invoices", "checkInvoiceID"), $aInt->lang("invoices", "wrongUser"), "error");
        }
    }
    echo $infobox;
    $result = select_query("tblaccounts", "SUM(amountin),SUM(fees),SUM(amountout),SUM(amountin-fees-amountout)", array("userid" => $userid));
    $data = mysql_fetch_array($result);
    echo "\n<div class=\"context-btn-container\">\n    <a href=\"";
    echo $whmcs->getPhpSelf();
    echo "?userid=";
    echo $userid;
    echo "&action=add\" class=\"btn btn-primary\"><i class=\"fas fa-plus\"></i> ";
    echo $aInt->lang("transactions", "addnew");
    echo "</a>\n</div>\n\n<div class=\"stat-blocks\">\n    <div class=\"row\">\n        <div class=\"col-xs-6 col-sm-3\">\n            <div class=\"stat\">\n                <strong class=\"truncate\">";
    echo formatCurrency($data[0])->toPrefixed();
    echo "</strong>\n                <p class=\"truncate\">";
    echo AdminLang::trans("transactions.totalin");
    echo "</p>\n            </div>\n        </div>\n        <div class=\"col-xs-6 col-sm-3\">\n            <div class=\"stat\">\n                <strong class=\"truncate\">";
    echo formatCurrency($data[1])->toPrefixed();
    echo "</strong>\n                <p class=\"truncate\">";
    echo AdminLang::trans("transactions.totalfees");
    echo "</p>\n            </div>\n        </div>\n        <div class=\"col-xs-6 col-sm-3\">\n            <div class=\"stat\">\n                <strong class=\"truncate\">";
    echo formatCurrency($data[2])->toPrefixed();
    echo "</strong>\n                <p class=\"truncate\">";
    echo AdminLang::trans("transactions.totalout");
    echo "</p>\n            </div>\n        </div>\n        <div class=\"col-xs-6 col-sm-3\">\n            <div class=\"stat\">\n                <strong class=\"truncate\">";
    echo formatCurrency($data[3])->toPrefixed();
    echo "</strong>\n                <p class=\"truncate\">";
    echo AdminLang::trans("fields.balance");
    echo "</p>\n            </div>\n        </div>\n    </div>\n</div>\n\n";
    $aInt->sortableTableInit("date", "DESC");
    $result = select_query("tblaccounts", "COUNT(*)", array("userid" => $userid));
    $data = mysql_fetch_array($result);
    $numrows = $data[0];
    $result = select_query("tblaccounts", "", array("userid" => $userid), $orderby, $order, $page * $limit . "," . $limit);
    while ($data = mysql_fetch_array($result)) {
        $ide = $data["id"];
        $date = $data["date"];
        $date = fromMySQLDate($date);
        $gateway = $data["gateway"];
        $description = $data["description"];
        $amountin = $data["amountin"];
        $fees = $data["fees"];
        $amountout = $data["amountout"];
        $transid = $data["transid"];
        $invoiceid = $data["invoiceid"];
        $totalin = $totalin + $amountin;
        $totalout = $totalout + $amountout;
        $totalfees = $totalfees + $fees;
        $amountin = formatCurrency($amountin);
        $fees = formatCurrency($fees);
        $amountout = formatCurrency($amountout);
        if ($invoiceid != "0") {
            $description .= " (<a href=\"invoices.php?action=edit&id=" . $invoiceid . "\">#" . $invoiceid . "</a>)";
        }
        if ($transid != "") {
            $description .= " - Trans ID: " . $transid;
        }
        $result2 = select_query("tblpaymentgateways", "", array("gateway" => $gateway, "setting" => "name"));
        $data = mysql_fetch_array($result2);
        $gateway = $data["value"];
        $tabledata[] = array($date, $gateway, $description, $amountin, $fees, $amountout, "<a href=\"?userid=" . $userid . "&action=edit&id=" . $ide . "\"><img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"Edit\"></a>", "<a href=\"#\" onClick=\"doDelete('" . $ide . "');return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"Delete\"></a>");
    }
    echo $aInt->sortableTable(array(array("date", $aInt->lang("fields", "date")), array("gateway", $aInt->lang("fields", "paymentmethod")), array("description", $aInt->lang("fields", "description")), array("amountin", $aInt->lang("transactions", "amountin")), array("fees", $aInt->lang("transactions", "fees")), array("amountout", $aInt->lang("transactions", "amountout")), "", ""), $tabledata);
} else {
    if ($action == "add") {
        $date2 = getTodaysDate();
        if ($error == "validation") {
            $repopulateData = WHMCS\Cookie::get("ValidationError", true);
            $errorMessage = "";
            foreach ($repopulateData["validationError"] as $validationError) {
                $errorMessage .= WHMCS\Input\Sanitize::makeSafeForOutput($validationError) . "<br />";
            }
            if ($errorMessage) {
                infobox($aInt->lang("global", "validationerror"), $errorMessage, "error");
            }
            $invoiceid = $repopulateData["invoiceid"] ? WHMCS\Input\Sanitize::makeSafeForOutput($repopulateData["invoiceid"]) : "";
            $transid = WHMCS\Input\Sanitize::makeSafeForOutput($repopulateData["transid"]);
            $amountin = $repopulateData["amountin"] ? WHMCS\Input\Sanitize::makeSafeForOutput($repopulateData["amountin"]) : "0.00";
            $fees = $repopulateData["fees"] ? WHMCS\Input\Sanitize::makeSafeForOutput($repopulateData["fees"]) : "0.00";
            $paymentmethod = WHMCS\Input\Sanitize::makeSafeForOutput($repopulateData["paymentmethod"]);
            $date2 = WHMCS\Input\Sanitize::makeSafeForOutput($repopulateData["date"]);
            $amountout = $repopulateData["amountout"] ? WHMCS\Input\Sanitize::makeSafeForOutput($repopulateData["amountout"]) : "0.00";
            $description = WHMCS\Input\Sanitize::makeSafeForOutput($repopulateData["description"]);
            $addcredit = $repopulateData["addcredit"] ? " CHECKED" : "";
            WHMCS\Cookie::delete("ValidationError");
        }
        echo $infobox;
        echo "\n<p><b>";
        echo $aInt->lang("transactions", "addnew");
        echo "</b></p>\n\n<form method=\"post\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "?userid=";
        echo $userid;
        echo "&sub=add\" name=\"calendarfrm\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr>\n    <td width=\"15%\" class=\"fieldlabel\">";
        echo $aInt->lang("fields", "date");
        echo "</td>\n    <td class=\"fieldarea\">\n        <div class=\"form-group date-picker-prepend-icon\">\n            <label for=\"inputDate\" class=\"field-icon\">\n                <i class=\"fal fa-calendar-alt\"></i>\n            </label>\n            <input id=\"inputDate\"\n                   type=\"text\"\n                   name=\"date\"\n                   value=\"";
        echo $date2;
        echo "\"\n                   class=\"form-control date-picker-single\"\n            />\n        </div>\n    </td>\n    <td class=\"fieldlabel\" width=\"15%\">";
        echo $aInt->lang("transactions", "amountin");
        echo "</td>\n    <td class=\"fieldarea\"><input type=\"text\" name=\"amountin\" class=\"form-control input-100\" value=\"";
        echo $amountin;
        echo "\"></td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "description");
        echo "</td>\n    <td class=\"fieldarea\"><input type=\"text\" name=\"description\" class=\"form-control input-300\" value=\"";
        echo $description;
        echo "\"></td>\n    <td class=\"fieldlabel\">";
        echo $aInt->lang("transactions", "fees");
        echo "</td>\n    <td class=\"fieldarea\"><input type=\"text\" name=\"fees\" class=\"form-control input-100\" value=\"";
        echo $fees;
        echo "\"></td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "transid");
        echo "</td>\n    <td class=\"fieldarea\"><input type=\"text\" name=\"transid\" class=\"form-control input-250\" value=\"";
        echo $transid;
        echo "\"></td>\n    <td class=\"fieldlabel\">";
        echo $aInt->lang("transactions", "amountout");
        echo "</td>\n    <td class=\"fieldarea\"><input type=\"text\" name=\"amountout\" class=\"form-control input-100\" value=\"";
        echo $amountout;
        echo "\"></td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "invoiceid");
        echo "</td>\n    <td class=\"fieldarea\"><input type=\"text\" name=\"invoiceid\" class=\"form-control input-150\" value=\"";
        echo $invoiceid;
        echo "\"></td>\n    <td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "credit");
        echo "</td>\n    <td class=\"fieldarea\">\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"addcredit\"";
        echo $addcredit;
        echo ">\n            ";
        echo $aInt->lang("invoices", "refundtypecredit");
        echo "        </label>\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "paymentmethod");
        echo "</td>\n    <td class=\"fieldarea\">";
        echo paymentMethodsSelection($aInt->lang("global", "none"));
        echo "</td>\n    <td class=\"fieldlabel\"></td><td class=\"fieldarea\"></td></tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
        echo $aInt->lang("transactions", "add");
        echo "\" class=\"button btn btn-default\">\n</div>\n\n</form>\n\n";
    } else {
        if ($action == "edit") {
            $result = select_query("tblaccounts", "", array("id" => $id));
            $data = mysql_fetch_array($result);
            $id = $data["id"];
            $date = $data["date"];
            $date = fromMySQLDate($date);
            $description = $data["description"];
            $amountin = $data["amountin"];
            $fees = $data["fees"];
            $amountout = $data["amountout"];
            $paymentmethod = $data["gateway"];
            $transid = $data["transid"];
            $invoiceid = $data["invoiceid"];
            echo "\n<p><b>";
            echo $aInt->lang("transactions", "edit");
            echo "</b></p>\n\n<form method=\"post\" action=\"";
            echo $whmcs->getPhpSelf();
            echo "?userid=";
            echo $userid;
            echo "&sub=save&id=";
            echo $id;
            echo "\" name=\"calendarfrm\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr>\n    <td width=\"15%\" class=\"fieldlabel\">\n        ";
            echo $aInt->lang("fields", "date");
            echo "    </td>\n    <td class=\"fieldarea\">\n        <div class=\"form-group date-picker-prepend-icon\">\n            <label for=\"inputDate\" class=\"field-icon\">\n                <i class=\"fal fa-calendar-alt\"></i>\n            </label>\n            <input id=\"inputDate\"\n                   type=\"text\"\n                   name=\"date\"\n                   value=\"";
            echo $date;
            echo "\"\n                   class=\"form-control date-picker-single future\"\n            />\n        </div>\n    </td>\n    <td width=\"15%\" class=\"fieldlabel\" width=110>\n        ";
            echo $aInt->lang("fields", "transid");
            echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"transid\" size=20 value=\"";
            echo $transid;
            echo "\" class=\"form-control input-250\" />\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
            echo $aInt->lang("fields", "paymentmethod");
            echo "    </td>\n    <td class=\"fieldarea\">\n        ";
            echo paymentMethodsSelection($aInt->lang("global", "none"));
            echo "    </td>\n    <td class=\"fieldlabel\">\n        ";
            echo $aInt->lang("transactions", "amountin");
            echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"amountin\" size=10 value=\"";
            echo $amountin;
            echo "\" class=\"form-control input-100\" />\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
            echo $aInt->lang("fields", "description");
            echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"description\" size=50 value=\"";
            echo $description;
            echo "\" class=\"form-control input-300\" />\n    </td>\n    <td class=\"fieldlabel\">\n        ";
            echo $aInt->lang("transactions", "fees");
            echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"fees\" size=10 value=\"";
            echo $fees;
            echo "\" class=\"form-control input-100\" />\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
            echo $aInt->lang("fields", "invoiceid");
            echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"invoiceid\" size=8 value=\"";
            echo $invoiceid;
            echo "\" class=\"form-control input-100\" />\n    </td>\n    <td class=\"fieldlabel\">\n        ";
            echo $aInt->lang("transactions", "amountout");
            echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"amountout\" size=10 value=\"";
            echo $amountout;
            echo "\" class=\"form-control input-100\" />\n    </td>\n</tr>\n</table>\n\n<p align=\"center\"><input type=\"submit\" value=\"";
            echo $aInt->lang("global", "savechanges");
            echo "\" class=\"button btn btn-default\"></p>\n\n</form>\n\n";
        }
    }
}
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();

?>