<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
if ($action == "edit") {
    $reqperm = "Edit Transaction";
} else {
    $reqperm = "List Transactions";
}
$aInt = new WHMCS\Admin($reqperm);
$aInt->title = $aInt->lang("transactions", "title");
$aInt->sidebar = "billing";
$aInt->icon = "transactions";
$aInt->requiredFiles(array("gatewayfunctions", "invoicefunctions"));
$jquerycode = "";
if ($action == "add") {
    check_token("WHMCS.admin.default");
    checkPermission("Add Transaction");
    $paymentMethod = $whmcs->get_req_var("paymentmethod");
    $transactionID = $whmcs->get_req_var("transid");
    $amountIn = $whmcs->get_req_var("amountin");
    $fees = $whmcs->get_req_var("fees");
    $date = $whmcs->get_req_var("date");
    $amountOut = $whmcs->get_req_var("amountout");
    $description = $whmcs->get_req_var("description");
    $addCredit = $whmcs->get_req_var("addcredit");
    $currency = $whmcs->get_req_var("currency");
    $client = $whmcs->get_req_var("client");
    $cleanedInvoiceIDs = array();
    $userInputInvoiceIDs = trim($whmcs->get_req_var("invoiceids"));
    if ($userInputInvoiceIDs) {
        $userInputInvoiceIDs = explode(",", $userInputInvoiceIDs);
        foreach ($userInputInvoiceIDs as $tmpInvID) {
            $tmpInvID = trim($tmpInvID);
            if (is_numeric($tmpInvID)) {
                $cleanedInvoiceIDs[] = (int) $tmpInvID;
            }
        }
    }
    $validationError = false;
    $validationErrorDescription = array();
    if ($client) {
        $currency = 0;
    }
    if ($amountIn < 0) {
        $validationError = true;
        $validationErrorDescription[] = $aInt->lang("transactions", "amountInLessThanZero") . PHP_EOL;
    }
    if ($amountOut < 0) {
        $validationError = true;
        $validationErrorDescription[] = $aInt->lang("transactions", "amountOutLessThanZero") . PHP_EOL;
    }
    if (count($cleanedInvoiceIDs) == 0 && !$description) {
        $validationError = true;
        $validationErrorDescription[] = $aInt->lang("transactions", "invoiceIdOrDescriptionRequired") . PHP_EOL;
    }
    if ((!$amountOut || $amountOut == 0) && (!$amountIn || $amountIn == 0) && (!$fees || $fees == 0)) {
        $validationError = true;
        $validationErrorDescription[] = $aInt->lang("transactions", "amountInOutOrFeeRequired") . PHP_EOL;
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
    if ($addCredit && 0 < count($cleanedInvoiceIDs)) {
        $validationError = true;
        $validationErrorDescription[] = $aInt->lang("transactions", "invoiceIDAndCreditInvalid") . PHP_EOL;
    }
    if ($transactionID && !isUniqueTransactionID($transactionID, $paymentMethod)) {
        $validationError = true;
        $validationErrorDescription[] = $aInt->lang("transactions", "requireUniqueTransaction") . PHP_EOL;
    }
    if ($validationError) {
        WHMCS\Cookie::set("ValidationError", array("invoiceid" => implode(",", $userInputInvoiceIDs), "transid" => $transactionID, "amountin" => $amountIn, "fees" => $fees, "paymentmethod" => $paymentMethod, "date" => $date, "amountout" => $amountOut, "description" => $description, "addcredit" => $addCredit, "validationError" => $validationErrorDescription, "userid" => $client, "currency" => $currency));
        redir(array("validation" => true, "tab" => 1));
    }
    if (count($cleanedInvoiceIDs) <= 1) {
        $invoiceid = count($cleanedInvoiceIDs) ? $cleanedInvoiceIDs[0] : "";
        if ($transid && !isUniqueTransactionID($transactionID, $paymentMethod)) {
            WHMCS\Cookie::set("DuplicateTransaction", array("invoiceid" => $invoiceid, "transid" => $transactionID, "amountin" => $amountIn, "fees" => $fees, "paymentmethod" => $paymentMethod, "date" => $date, "amountout" => $amountOut, "description" => $description, "addcredit" => $addCredit, "userid" => $client, "currency" => $currency));
            redir(array("duplicate" => true, "tab" => 1));
        }
        addTransaction($client, $currency, $description, $amountIn, $fees, $amountOut, $paymentMethod, $transactionID, $invoiceid, $date);
        if ($client && $addCredit && (!is_int($invoiceid) || $invoiceid == 0)) {
            if ($transactionID) {
                $description .= " (" . $aInt->lang("transactions", "transid") . ": " . $transactionID . ")";
            }
            insert_query("tblcredit", array("clientid" => $client, "date" => toMySQLDate($date), "description" => $description, "amount" => $amountIn));
            update_query("tblclients", array("credit" => "+=" . $amountIn), array("id" => (int) $client));
        }
        if (is_int($invoiceid)) {
            $totalPaid = get_query_val("tblaccounts", "SUM(amountin)-SUM(amountout)", array("invoiceid" => $invoiceid));
            $invoiceData = get_query_vals("tblinvoices", "status, total", array("id" => $invoiceid));
            $balance = $invoiceData["total"] - $totalPaid;
            if ($balance <= 0 && $invoiceData["status"] == "Unpaid") {
                processPaidInvoice($invoiceid, "", $date);
            }
        }
    } else {
        if (1 < count($cleanedInvoiceIDs)) {
            $query = select_query("tblinvoices", "SUM(total)", array("id" => array("sqltype" => "IN", "values" => $cleanedInvoiceIDs)));
            $data = mysql_fetch_assoc($query);
            $invoicestotal = $data[0];
            $totalleft = $amountIn;
            $fees = round($fees / count($invoices), 2);
            foreach ($cleanedInvoiceIDs as $invoiceid) {
                if (0 < $totalleft) {
                    $result = select_query("tblinvoices", "total", array("id" => $invoiceid));
                    $data = mysql_fetch_array($result);
                    $invoicetotal = $data[0];
                    $result2 = select_query("tblaccounts", "SUM(amountin)", array("invoiceid" => $invoiceid));
                    $data = mysql_fetch_array($result2);
                    $totalin = $data[0];
                    $paymentdue = $invoicetotal - $totalin;
                    if ($paymentdue < $totalleft) {
                        addInvoicePayment($invoiceid, $transactionID, $paymentdue, $fees, $paymentMethod, "", $date);
                        $totalleft -= $paymentdue;
                    } else {
                        addInvoicePayment($invoiceid, $transactionID, $totalleft, $fees, $paymentMethod, "", $date);
                        $totalleft = 0;
                    }
                }
            }
            if ($totalleft) {
                addInvoicePayment($invoiceid, $transactionID, $totalleft, $fees, $paymentMethod, "", $date);
            }
        }
    }
    redir("added=true");
}
if ($action == "save") {
    check_token("WHMCS.admin.default");
    checkPermission("Edit Transaction");
    if ($client) {
        $currency = 0;
    }
    $date = toMySQLDate($date);
    $values = array("userid" => $client, "date" => $date, "description" => $description, "amountin" => $amountin, "fees" => $fees, "amountout" => $amountout, "gateway" => $paymentmethod, "transid" => $transid, "invoiceid" => $invoiceid, "currency" => $currency);
    update_query("tblaccounts", $values, array("id" => $id));
    logActivity("Modified Transaction - Transaction ID: " . $id, $client);
    redir("saved=true");
}
if ($action == "delete") {
    check_token("WHMCS.admin.default");
    checkPermission("Delete Transaction");
    $transaction = WHMCS\Billing\Payment\Transaction::find($id);
    $userId = $transaction->clientId;
    $transaction->delete();
    logActivity("Deleted Transaction - Transaction ID: " . $id, $userId);
    redir("deleted=true");
}
ob_start();
if (!$action) {
    if ($added) {
        infoBox($aInt->lang("transactions", "transactionadded"), $aInt->lang("transactions", "transactionaddedinfo"));
    }
    if ($saved) {
        infoBox($aInt->lang("transactions", "transactionupdated"), $aInt->lang("transactions", "transactionupdatedinfo"));
    }
    if ($deleted) {
        infoBox($aInt->lang("transactions", "transactiondeleted"), $aInt->lang("transactions", "transactiondeletedinfo"));
    }
    if ($duplicate || $validation) {
        if ($duplicate) {
            infobox($aInt->lang("transactions", "duplicate"), $aInt->lang("transactions", "requireUniqueTransaction"), "error");
            $cookieName = "DuplicateTransaction";
        } else {
            $cookieName = "ValidationError";
        }
        $repopulateData = WHMCS\Cookie::get($cookieName, true);
        $invoiceid = $repopulateData["invoiceid"] ? WHMCS\Input\Sanitize::makeSafeForOutput($repopulateData["invoiceid"]) : "";
        $transid = WHMCS\Input\Sanitize::makeSafeForOutput($repopulateData["transid"]);
        $amountin = $repopulateData["amountin"] ? WHMCS\Input\Sanitize::makeSafeForOutput($repopulateData["amountin"]) : "0.00";
        $fees = $repopulateData["fees"] ? WHMCS\Input\Sanitize::makeSafeForOutput($repopulateData["fees"]) : "0.00";
        $paymentmethod = WHMCS\Input\Sanitize::makeSafeForOutput($repopulateData["paymentmethod"]);
        $date2 = WHMCS\Input\Sanitize::makeSafeForOutput($repopulateData["date"]);
        $amountout = $repopulateData["amountout"] ? WHMCS\Input\Sanitize::makeSafeForOutput($repopulateData["amountout"]) : "0.00";
        $description = WHMCS\Input\Sanitize::makeSafeForOutput($repopulateData["description"]);
        $addcredit = $repopulateData["addcredit"] ? " CHECKED" : "";
        $userid = $repopulateData["userid"] ? WHMCS\Input\Sanitize::makeSafeForOutput($repopulateData["userid"]) : "";
        $currency = $repopulateData["currency"] ? WHMCS\Input\Sanitize::makeSafeForOutput($repopulateData["currency"]) : "";
        if ($validation) {
            $errorMessage = "";
            foreach ($repopulateData["validationError"] as $validationError) {
                $errorMessage .= WHMCS\Input\Sanitize::makeSafeForOutput($validationError) . "<br />";
            }
            if ($errorMessage) {
                infobox($aInt->lang("global", "validationerror"), $errorMessage, "error");
            }
        }
        WHMCS\Cookie::delete($cookieName);
    }
    echo $infobox;
    $aInt->deleteJSConfirm("doDelete", "transactions", "deletesure", "?action=delete&id=");
    echo $aInt->beginAdminTabs(array($aInt->lang("global", "searchfilter"), $aInt->lang("transactions", "add")));
    $range = App::getFromRequest("range");
    if (!$range) {
        $today = WHMCS\Carbon::today();
        $lastMonth = $today->copy()->subDays(29)->toAdminDateFormat();
        $range = $lastMonth . " - " . $today->toAdminDateFormat();
    }
    $show = App::getFromRequest("show");
    echo "\n<form method=\"post\" action=\"transactions.php\"><input type=\"hidden\" name=\"filter\" value=\"true\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n    <tr>\n        <td width=\"15%\" class=\"fieldlabel\">\n            ";
    echo AdminLang::trans("transactions.show");
    echo "        </td>\n        <td class=\"fieldarea\">\n            <select name=\"show\" class=\"form-control select-inline\">\n                <option value=\"\">\n                    ";
    echo AdminLang::trans("transactions.allactivity");
    echo "                </option>\n                <option value=\"received\"";
    echo $show == "received" ? " selected=\"selected\"" : "";
    echo ">\n                    ";
    echo AdminLang::trans("transactions.preceived");
    echo "                </option>\n                <option value=\"sent\"";
    echo $show == "sent" ? " selected=\"selected\"" : "";
    echo ">\n                    ";
    echo AdminLang::trans("transactions.psent");
    echo "                </option>\n            </select>\n        </td>\n        <td width=\"15%\" class=\"fieldlabel\">\n            ";
    echo AdminLang::trans("fields.daterange");
    echo "        </td>\n        <td class=\"fieldarea\">\n            <div class=\"form-group date-picker-prepend-icon\">\n                <label for=\"inputRange\" class=\"field-icon\">\n                    <i class=\"fal fa-calendar-alt\"></i>\n                </label>\n                <input id=\"inputRange\"\n                       type=\"text\"\n                       name=\"range\"\n                       value=\"";
    echo $range;
    echo "\"\n                       class=\"form-control date-picker-search\"\n                />\n            </div>\n        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\" width=\"15%\">\n            ";
    echo AdminLang::trans("fields.description");
    echo "        </td>\n        <td class=\"fieldarea\">\n            <input type=\"text\"\n                   name=\"filterdescription\"\n                   class=\"form-control input-300\"\n                   value=\"";
    echo $filterdescription;
    echo "\"\n            >\n        </td>\n        <td class=\"fieldlabel\">\n            ";
    echo AdminLang::trans("fields.amount");
    echo "        </td>\n        <td class=\"fieldarea\">\n            <input type=\"text\" name=\"amount\" class=\"form-control input-100\" value=\"";
    echo $amount;
    echo "\">\n        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">\n            ";
    echo AdminLang::trans("fields.transid");
    echo "        </td>\n        <td class=\"fieldarea\">\n            <input type=\"text\"\n                   name=\"filtertransid\"\n                   class=\"form-control input-300\"\n                   value=\"";
    echo $filtertransid;
    echo "\"\n            >\n        </td>\n        <td class=\"fieldlabel\">\n            ";
    echo AdminLang::trans("fields.paymentmethod");
    echo "        </td>\n        <td class=\"fieldarea\">\n            ";
    echo paymentMethodsSelection(AdminLang::trans("global.any"));
    echo "        </td>\n    </tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
    echo $aInt->lang("global", "searchfilter");
    echo "\" class=\"btn btn-default\" />\n</div>\n\n</form>\n\n";
    echo $aInt->nextAdminTab();
    echo "\n";
    $date2 = getTodaysDate();
    echo "<form method=\"post\" action=\"";
    echo $whmcs->getPhpSelf();
    echo "?action=add\" name=\"calendarfrm\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr>\n    <td width=\"15%\" class=\"fieldlabel\">\n        ";
    echo $aInt->lang("fields", "date");
    echo "    </td>\n    <td class=\"fieldarea\">\n        <div class=\"form-group date-picker-prepend-icon\">\n            <label for=\"inputDate\" class=\"field-icon\">\n                <i class=\"fal fa-calendar-alt\"></i>\n            </label>\n            <input id=\"inputDate\"\n                   type=\"text\"\n                   name=\"date\"\n                   value=\"";
    echo $date2;
    echo "\"\n                   class=\"form-control date-picker-single\"\n            />\n        </div>\n    </td>\n    <td width=\"15%\" class=\"fieldlabel\">\n        ";
    echo $aInt->lang("currencies", "currency");
    echo "    </td>\n    <td class=\"fieldarea\">\n        <select name=\"currency\" class=\"form-control select-inline\">";
    $result = select_query("tblcurrencies", "", "", "code", "ASC");
    while ($data = mysql_fetch_array($result)) {
        echo "<option value=\"" . $data["id"] . "\"";
        if (!$currency && $data["default"] || $currency && $currency == $data["id"]) {
            echo " selected";
        }
        echo ">" . $data["code"] . "</option>";
    }
    echo "</select> (";
    echo $aInt->lang("transactions", "nonclientonly");
    echo ")</td></tr>\n<tr>\n    <td width=\"15%\" class=\"fieldlabel\">";
    echo $aInt->lang("transactions", "relclient");
    echo "</td>\n    <td class=\"fieldarea\">";
    echo $aInt->clientsDropDown($userid, false, "client", true);
    echo "</td>\n    <td class=\"fieldlabel\">";
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
    echo "</td>\n    <td class=\"fieldarea\"><input type=\"text\" name=\"transid\" class=\"form-control input-300\" value=\"";
    echo $transid;
    echo "\"></td>\n    <td class=\"fieldlabel\">";
    echo $aInt->lang("transactions", "amountout");
    echo "</td>\n    <td class=\"fieldarea\"><input type=\"text\" name=\"amountout\" class=\"form-control input-100\" value=\"";
    echo $amountout;
    echo "\"></td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
    echo $aInt->lang("transactions", "invoiceids");
    echo "</td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"invoiceids\" class=\"form-control input-150 input-inline\" value=\"";
    echo $invoiceid;
    echo "\">\n        ";
    echo $aInt->lang("transactions", "commaseparated");
    echo "    </td>\n    <td class=\"fieldlabel\">";
    echo $aInt->lang("fields", "credit");
    echo "</td>\n    <td class=\"fieldarea\">\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"addcredit\"";
    echo $addcredit;
    echo ">\n            ";
    echo $aInt->lang("invoices", "refundtypecredit");
    echo "        </label>\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
    echo $aInt->lang("fields", "paymentmethod");
    echo "</td>\n    <td class=\"fieldarea\">";
    echo paymentMethodsSelection($aInt->lang("global", "none"));
    echo "</td>\n    <td class=\"fieldlabel\"></td>\n    <td class=\"fieldarea\"></td>\n</tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
    echo $aInt->lang("transactions", "add");
    echo "\" class=\"btn btn-default\" />\n</div>\n\n</form>\n\n";
    echo $aInt->endAdminTabs();
    echo "\n<br />\n\n";
    $aInt->sortableTableInit("date", "DESC");
    $query = "";
    $where = array();
    if ($show == "received") {
        $where[] = "tblaccounts.amountin>0";
    } else {
        if ($show == "sent") {
            $where[] = "tblaccounts.amountout>0";
        }
    }
    if ($amount) {
        $where[] = "(tblaccounts.amountin='" . db_escape_string($amount) . "' OR tblaccounts.amountout='" . db_escape_string($amount) . "')";
    }
    $range = WHMCS\Carbon::parseDateRangeValue($range);
    $startDate = $range["from"];
    $endDate = $range["to"];
    if ($startDate) {
        $where[] = "tblaccounts.date>='" . $startDate->toDateTimeString() . "'";
    }
    if ($endDate) {
        $where[] = "tblaccounts.date<='" . $endDate->toDateTimeString() . "'";
    }
    if ($filtertransid) {
        $where[] = "tblaccounts.transid='" . db_escape_string($filtertransid) . "'";
    }
    if ($paymentmethod) {
        $where[] = "tblaccounts.gateway='" . db_escape_string($paymentmethod) . "'";
    }
    if ($filterdescription) {
        $where[] = "tblaccounts.description LIKE '%" . db_escape_string($filterdescription) . "%'";
    }
    if (count($where)) {
        $query .= " WHERE " . implode(" AND ", $where);
    }
    $totals = array();
    $fullquery = "SELECT tblclients.currency,SUM(amountin),SUM(fees),SUM(amountout),SUM(amountin-fees-amountout) FROM tblaccounts,tblclients " . ($query ? $query . " AND" : "WHERE") . " tblclients.id=tblaccounts.userid GROUP BY tblclients.currency";
    $result = full_query($fullquery);
    while ($data = mysql_fetch_array($result)) {
        $currency = $data["currency"];
        list(, $totalin, $totalfees, $totalout, $total) = $data;
        $totals[$currency] = array("in" => $totalin, "fees" => $totalfees, "out" => $totalout, "total" => $total);
    }
    $fullquery = "SELECT currency,SUM(amountin),SUM(fees),SUM(amountout),SUM(amountin-fees-amountout) FROM tblaccounts " . ($query ? $query . " AND" : "WHERE") . " userid=0 GROUP BY currency";
    $result = full_query($fullquery);
    while ($data = mysql_fetch_array($result)) {
        $currency = $data["currency"];
        list(, $totalin, $totalfees, $totalout, $total) = $data;
        $totals[$currency]["in"] += $totalin;
        $totals[$currency]["fees"] += $totalfees;
        $totals[$currency]["out"] += $totalout;
        $totals[$currency]["total"] += $total;
    }
    $gatewaysarray = getGatewaysArray();
    $query .= " ORDER BY tblaccounts.date DESC,tblaccounts.id DESC";
    $result = full_query("SELECT COUNT(*) FROM tblaccounts" . $query);
    $data = mysql_fetch_array($result);
    $numrows = $data[0];
    $query = "SELECT tblaccounts.*,tblclients.firstname,tblclients.lastname,tblclients.companyname,tblclients.groupid,tblclients.currency AS currencyid FROM tblaccounts LEFT JOIN tblclients ON tblclients.id=tblaccounts.userid" . $query . " LIMIT " . (int) ($page * $limit) . "," . (int) $limit;
    $result = full_query($query);
    while ($data = mysql_fetch_array($result)) {
        $id = $data["id"];
        $userid = $data["userid"];
        $currency = $data["currency"];
        $date = $data["date"];
        $date = fromMySQLDate($date);
        $description = $data["description"];
        $amountin = $data["amountin"];
        $fees = $data["fees"];
        $amountout = $data["amountout"];
        $gateway = $data["gateway"];
        $transid = $data["transid"];
        $invoiceid = $data["invoiceid"];
        $firstname = $data["firstname"];
        $lastname = $data["lastname"];
        $companyname = $data["companyname"];
        $groupid = $data["groupid"];
        $currencyid = $data["currencyid"];
        $clientlink = $userid ? $aInt->outputClientLink($userid, $firstname, $lastname, $companyname, $groupid) : "-";
        $currency = $userid ? getCurrency("", $currencyid) : getCurrency("", $currency);
        $amountin = formatCurrency($amountin);
        $fees = formatCurrency($fees);
        $amountout = formatCurrency($amountout);
        if ($invoiceid != "0") {
            $description .= " (<a href=\"invoices.php?action=edit&id=" . $invoiceid . "\">#" . $invoiceid . "</a>)";
        }
        if ($transid != "") {
            $description .= "<br>Trans ID: " . $transid;
        }
        $gateway = $gatewaysarray[$gateway];
        $tabledata[] = array($clientlink, $date, $gateway, $description, $amountin, $fees, $amountout, "<a href=\"?action=edit&id=" . $id . "\"><img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "edit") . "\"></a>", "<a href=\"#\" onClick=\"doDelete('" . $id . "');return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\"></a>");
    }
    echo $aInt->sortableTable(array($aInt->lang("fields", "clientname"), $aInt->lang("fields", "date"), $aInt->lang("fields", "paymentmethod"), $aInt->lang("fields", "description"), $aInt->lang("transactions", "amountin"), $aInt->lang("transactions", "fees"), $aInt->lang("transactions", "amountout"), "", ""), $tabledata, $tableformurl, $tableformbuttons);
    if (checkPermission("View Income Totals", true)) {
        echo "\n<table cellspacing=\"1\" cellpadding=\"5\" bgcolor=\"#cccccc\" width=\"600\" align=\"center\">\n<tr bgcolor=\"#f4f4f4\" style=\"text-align:center;font-weight:bold;\"><td></td><td>";
        echo $aInt->lang("transactions", "totalincome");
        echo "</td><td>";
        echo $aInt->lang("transactions", "totalfees");
        echo "</td><td>";
        echo $aInt->lang("transactions", "totalexpenditure");
        echo "</td><td>";
        echo $aInt->lang("transactions", "totalbalance");
        echo "</td></tr>\n";
        foreach ($totals as $currency => $values) {
            $currency = getCurrency("", $currency);
            echo "<tr bgcolor=\"#ffffff\" style=\"text-align:center;\"><td bgcolor=\"#f4f4f4\"><b>" . $currency["code"] . "</b></td><td>" . formatCurrency($values["in"]) . "</td><td>" . formatCurrency($values["fees"]) . "</td><td>" . formatCurrency($values["out"]) . "</td><td bgcolor=\"#f4f4f4\"><b>" . formatCurrency($values["total"]) . "</b></td></tr>";
        }
        if (!count($totals)) {
            echo "<tr bgcolor=\"#ffffff\" style=\"text-align:center;\"><td colspan=\"5\">" . $aInt->lang("transactions", "nototals") . "</td></tr>";
        }
        echo "</table>\n\n";
    }
} else {
    if ($action == "edit") {
        $result = select_query("tblaccounts", "", array("id" => $id));
        $data = mysql_fetch_array($result);
        $id = $data["id"];
        $userid = $data["userid"];
        $date = $data["date"];
        $date = fromMySQLDate($date);
        $description = $data["description"];
        $amountin = $data["amountin"];
        $fees = $data["fees"];
        $amountout = $data["amountout"];
        $paymentmethod = $data["gateway"];
        $transid = $data["transid"];
        $invoiceid = $data["invoiceid"];
        $currency = $data["currency"];
        if (!$id) {
            $aInt->gracefulExit($aInt->lang("transactions", "notfound"));
        }
        echo "\n<h2>";
        echo $aInt->lang("transactions", "edit");
        echo "</h2>\n\n<form method=\"post\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "?action=save&id=";
        echo $id;
        echo "\" name=\"calendarfrm\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n    <tr>\n        <td width=\"15%\" class=\"fieldlabel\">";
        echo $aInt->lang("fields", "date");
        echo "</td>\n        <td class=\"fieldarea\">\n            <div class=\"form-group date-picker-prepend-icon\">\n                <label for=\"inputDate\" class=\"field-icon\">\n                    <i class=\"fal fa-calendar-alt\"></i>\n                </label>\n                <input id=\"inputDate\"\n                       type=\"text\"\n                       name=\"date\"\n                       value=\"";
        echo $date;
        echo "\"\n                       class=\"form-control date-picker-single\"\n                />\n            </div>\n        </td>\n        <td class=\"fieldlabel\">";
        echo $aInt->lang("currencies", "currency");
        echo "</td>\n        <td class=\"fieldarea\">";
        if ($userid) {
            echo "---";
        } else {
            echo "            <select name=\"currency\" class=\"form-control select-inline\">";
            $currencies = WHMCS\Database\Capsule::table("tblcurrencies")->orderBy("code", "asc")->get();
            foreach ($currencies as $dropdownCurrencyData) {
                echo "<option value=\"" . $dropdownCurrencyData->id . "\"";
                if (!$currency && $dropdownCurrencyData->default || $currency && $currency == $dropdownCurrencyData->id) {
                    echo " selected";
                }
                echo ">" . $dropdownCurrencyData->code . "</option>";
            }
            echo "</select> ";
            echo "(" . $aInt->lang("transactions", "nonclientonly") . ")";
        }
        echo "        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">";
        echo $aInt->lang("transactions", "relclient");
        echo "</td>\n        <td class=\"fieldarea\">";
        echo $aInt->clientsDropDown($userid, false, "client", true);
        echo "</td>\n        <td class=\"fieldlabel\">";
        echo $aInt->lang("transactions", "amountin");
        echo "</td>\n        <td class=\"fieldarea\"><input type=\"text\" name=\"amountin\" class=\"form-control input-100\" value=\"";
        echo $amountin;
        echo "\"></td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "description");
        echo "</td>\n        <td class=\"fieldarea\"><input type=\"text\" name=\"description\" class=\"form-control input-300\" value=\"";
        echo $description;
        echo "\"></td>\n        <td class=\"fieldlabel\">";
        echo $aInt->lang("transactions", "fees");
        echo "</td>\n        <td class=\"fieldarea\"><input type=\"text\" name=\"fees\" class=\"form-control input-100\" value=\"";
        echo $fees;
        echo "\"></td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "transid");
        echo "</td>\n        <td class=\"fieldarea\"><input type=\"text\" name=\"transid\" class=\"form-control input-300\" value=\"";
        echo $transid;
        echo "\"></td>\n        <td class=\"fieldlabel\">";
        echo $aInt->lang("transactions", "amountout");
        echo "</td>\n        <td class=\"fieldarea\"><input type=\"text\" name=\"amountout\" class=\"form-control input-100\" value=\"";
        echo $amountout;
        echo "\"></td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "invoiceid");
        echo "</td>\n        <td class=\"fieldarea\"><input type=\"text\" name=\"invoiceid\" class=\"form-control input-150\" value=\"";
        echo $invoiceid;
        echo "\"></td>\n        <td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "paymentmethod");
        echo "</td>\n        <td class=\"fieldarea\">";
        echo paymentMethodsSelection($aInt->lang("global", "none"));
        echo "</td>\n    </tr>\n</table>\n\n<p align=\"center\"><input type=\"submit\" value=\"";
        echo $aInt->lang("global", "savechanges");
        echo "\" class=\"button btn btn-default\" /></p>\n\n</form>\n\n";
    }
}
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();

?>