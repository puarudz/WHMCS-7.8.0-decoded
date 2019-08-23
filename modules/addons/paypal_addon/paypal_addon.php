<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function paypal_addon_config()
{
    $configarray = array("name" => "PayPal Transaction Lookup", "version" => "2.0.1", "author" => "WHMCS", "description" => "This addon shows your PayPal account balance on the admin homepage & allows you to search PayPal Transactions without needing to login to PayPal", "fields" => array("username" => array("FriendlyName" => "API Username", "Type" => "text", "Size" => "30"), "password" => array("FriendlyName" => "API Password", "Type" => "password", "Size" => "30"), "signature" => array("FriendlyName" => "API Signature", "Type" => "password", "Size" => "50")));
    $baltitle = "Show Balance";
    $result = select_query("tbladminroles", "", "", "name", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $configarray["fields"]["showbalance" . $data["id"]] = array("FriendlyName" => $baltitle, "Type" => "yesno", "Description" => "Display PayPal Balance on Homepage for <strong>" . $data["name"] . "</strong> users");
        $baltitle = "";
    }
    return $configarray;
}
function paypal_addon_output($vars)
{
    global $aInt;
    $modulelink = $vars["modulelink"];
    $url = "https://api-3t.paypal.com/nvp";
    $transid = trim($_REQUEST["transid"]);
    $email = trim($_REQUEST["email"]);
    $receiptid = trim($_REQUEST["receiptid"]);
    $search = trim($_REQUEST["search"]);
    $range = App::getFromRequest("range");
    if (!$range) {
        $today = WHMCS\Carbon::today();
        $lastMonth = $today->copy()->subDays(29)->toAdminDateFormat();
        $range = $lastMonth . " - " . $today->toAdminDateFormat();
    }
    echo "<form method=\"post\" action=\"" . $modulelink . "\">\n<input type=\"hidden\" name=\"search\" value=\"true\" />\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n    <tr>\n        <td width=\"20%\" class=\"fieldlabel\">Transaction ID</td>\n        <td class=\"fieldarea\">\n            <input type=\"text\" name=\"transid\" class=\"form-control input-225\" value=\"" . $transid . "\" />\n        </td>\n    </tr>\n    <tr>\n        <td width=\"20%\" class=\"fieldlabel\">" . AdminLang::trans("fields.daterange") . "</td>\n        <td class=\"fieldarea\">\n            <div class=\"form-group date-picker-prepend-icon\">\n                <label for=\"inputRange\" class=\"field-icon\">\n                    <i class=\"fal fa-calendar-alt\"></i>\n                </label>\n                <input id=\"inputRange\"\n                       type=\"text\"\n                       name=\"range\"\n                       value=\"" . $range . "\"\n                       class=\"form-control date-picker-search\"\n                />\n            </div>\n        </td>\n    </tr>\n    <tr>\n        <td width=\"20%\" class=\"fieldlabel\">Email</td>\n        <td class=\"fieldarea\">\n            <input type=\"text\" name=\"email\" class=\"form-control input-225\" value=\"" . $email . "\" />\n        </td>\n    </tr>\n    <tr>\n        <td width=\"20%\" class=\"fieldlabel\">Receipt ID</td>\n        <td class=\"fieldarea\">\n            <input type=\"text\" name=\"receiptid\" class=\"form-control input-225\" value=\"" . $receiptid . "\" />\n        </td>\n    </tr>\n</table>\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"Search\" class=\"btn btn-primary\" />\n</div>\n</form>";
    if (!$search) {
        return false;
    }
    if ($transid) {
        $postfields = $resultsarray = array();
        $postfields["USER"] = $vars["username"];
        $postfields["PWD"] = $vars["password"];
        $postfields["SIGNATURE"] = $vars["signature"];
        $postfields["METHOD"] = "GetTransactionDetails";
        $postfields["TRANSACTIONID"] = $transid;
        $postfields["VERSION"] = "3.0";
        $result = curlCall($url, $postfields);
        $resultsarray2 = explode("&", $result);
        foreach ($resultsarray2 as $line) {
            $line = explode("=", $line);
            $resultsarray[$line[0]] = urldecode($line[1]);
        }
        $errormessage = $resultsarray["L_LONGMESSAGE0"];
        $payerstatus = $resultsarray["PAYERSTATUS"];
        $countrycode = $resultsarray["COUNTRYCODE"];
        $invoiceid = $resultsarray["INVNUM"];
        $timestamp = $resultsarray["TIMESTAMP"];
        $firstname = $resultsarray["FIRSTNAME"];
        $lastname = $resultsarray["LASTNAME"];
        $email = $resultsarray["EMAIL"];
        $transactionid = $resultsarray["TRANSACTIONID"];
        $transactiontype = $resultsarray["TRANSACTIONTYPE"];
        $paymenttype = $resultsarray["PAYMENTTYPE"];
        $ordertime = $resultsarray["ORDERTIME"];
        $amount = $resultsarray["AMT"];
        $fee = $resultsarray["FEEAMT"];
        $paymentstatus = $resultsarray["PAYMENTSTATUS"];
        $description = $resultsarray["L_NAME0"];
        $currencycode = $resultsarray["L_CURRENCYCODE0"];
        $exchrate = $resultsarray["EXCHANGERATE"];
        $settleamt = $resultsarray["SETTLEAMT"];
        if ($errormessage) {
            echo "<p><b>PayPal API Error Message</b></p><p>" . $errormessage . "</p>";
        } else {
            echo "<p><b>PayPal Transaction Details</b></p>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"20%\" class=\"fieldlabel\">Transaction ID</td><td class=\"fieldarea\">" . $transactionid . "</td></tr>\n<tr><td class=\"fieldlabel\">Date/Time</td><td class=\"fieldarea\">" . fromMySQLDate($ordertime, true) . "</td></tr>\n<tr><td class=\"fieldlabel\">Transaction Type</td><td class=\"fieldarea\">" . $transactiontype . "</td></tr>\n<tr><td class=\"fieldlabel\">Payment Type</td><td class=\"fieldarea\">" . $paymenttype . "</td></tr>\n<tr><td class=\"fieldlabel\">Name</td><td class=\"fieldarea\">" . $firstname . " " . $lastname . "</td></tr>\n<tr><td class=\"fieldlabel\">Email</td><td class=\"fieldarea\">" . $email . "</td></tr>\n<tr><td class=\"fieldlabel\">Description</td><td class=\"fieldarea\">" . $description . "</td></tr>\n<tr><td class=\"fieldlabel\">Amount</td><td class=\"fieldarea\">" . $amount . "</td></tr>\n<tr><td class=\"fieldlabel\">PayPal Fee</td><td class=\"fieldarea\">" . $fee . "</td></tr>\n<tr><td class=\"fieldlabel\">Currency</td><td class=\"fieldarea\">" . $currencycode . "</td></tr>";
            if ($exchrate) {
                echo "\n<tr><td class=\"fieldlabel\">Exchange Rate</td><td class=\"fieldarea\">" . $exchrate . " (" . $settleamt . ")</td></tr>";
            }
            echo "\n<tr><td class=\"fieldlabel\">Payer Status</td><td class=\"fieldarea\">" . ucfirst($payerstatus) . "</td></tr>\n<tr><td class=\"fieldlabel\">PayPal Status</td><td class=\"fieldarea\">" . $paymentstatus . "</td></tr>\n</table>";
            if (!$invoiceid) {
                $invoiceid = explode("#", $description);
                $invoiceid = (int) $invoiceid[1];
            }
            $result = select_query("tblinvoices", "tblinvoices.id,tblinvoices.status,tblinvoices.userid,tblclients.firstname,tblclients.lastname", array("tblinvoices.id" => $invoiceid), "", "", "", "tblclients ON tblclients.id=tblinvoices.userid");
            $data = mysql_fetch_array($result);
            $whmcs_invoiceid = $data["id"];
            $whmcs_status = $data["status"];
            $whmcs_userid = $data["userid"];
            $whmcs_firstname = $data["firstname"];
            $whmcs_lastname = $data["lastname"];
            if (!$whmcs_invoiceid) {
                $whmcs_status = "No Matching Invoice Found";
            }
            echo "<p><b>WHMCS Invoice Lookup</b></p>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"20%\" class=\"fieldlabel\">Invoice ID</td><td class=\"fieldarea\"><a href=\"invoices.php?action=edit&id=" . $whmcs_invoiceid . "\">" . $whmcs_invoiceid . "</a></td></tr>\n<tr><td class=\"fieldlabel\">Invoice Status</td><td class=\"fieldarea\">" . $whmcs_status . "</td></tr>\n<tr><td class=\"fieldlabel\">Client Name</td><td class=\"fieldarea\"><a href=\"clientssummary.php?userid=" . $whmcs_userid . "\">" . $whmcs_firstname . " " . $whmcs_lastname . "</a></td></tr>\n</table>";
            $result = select_query("tblaccounts", "", array("transid" => $transactionid));
            $data = mysql_fetch_array($result);
            $whmcstransid = $data["id"];
            $date = $data["date"];
            $invoiceid = $data["invoiceid"];
            $amountin = $data["amountin"];
            $fees = $data["fees"];
            $date = $date ? fromMySQLDate($date) : "";
            if ($invoiceid) {
                $status = get_query_val("tblinvoices", "status", array("id" => $invoiceid));
                $invoiceid = "<a href=\"invoices.php?action=edit&id=" . $invoiceid . "\">" . $invoiceid . "</a>";
            } else {
                $invoiceid = "No Matching Invoice Found";
                $status = "Transaction not applied to an Invoice";
            }
            if ($whmcstransid) {
                $whmcstransid = "<a href=\"transactions.php?action=edit&id=" . $whmcstransid . "\">" . $transactionid . "</a>";
            } else {
                $whmcstransid = "No Matching Transaction Found";
            }
            echo "<p><b>WHMCS Transaction Lookup</b></p>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"20%\" class=\"fieldlabel\">Date</td><td class=\"fieldarea\">" . $date . "</td></tr>\n<tr><td class=\"fieldlabel\">Transaction ID</td><td class=\"fieldarea\">" . $whmcstransid . "</td></tr>\n<tr><td class=\"fieldlabel\">Amount</td><td class=\"fieldarea\">" . $amountin . "</td></tr>\n<tr><td class=\"fieldlabel\">Invoice ID</td><td class=\"fieldarea\">" . $invoiceid . "</td></tr>\n<tr><td class=\"fieldlabel\">Invoice Status</td><td class=\"fieldarea\">" . $status . "</td></tr>\n</table>";
        }
    } else {
        if ($startdate) {
            $startdate = date("c", strtotime(toMySQLDate($startdate))) . "<br>";
            $enddate = date("c", strtotime(toMySQLDate($enddate))) . "<br>";
            $postfields = $resultsarray = array();
            $postfields["USER"] = $vars["username"];
            $postfields["PWD"] = $vars["password"];
            $postfields["SIGNATURE"] = $vars["signature"];
            $postfields["METHOD"] = "TransactionSearch";
            if ($startdate) {
                $postfields["STARTDATE"] = $startdate;
            }
            if ($enddate) {
                $postfields["ENDDATE"] = $enddate;
            }
            if ($email) {
                $postfields["EMAIL"] = $email;
            }
            if ($receiptid) {
                $postfields["RECEIPTID"] = $receiptid;
            }
            $postfields["VERSION"] = "51.0";
            $result = curlCall($url, $postfields);
            $resultsarray2 = explode("&", $result);
            foreach ($resultsarray2 as $line) {
                $line = explode("=", $line);
                $resultsarray[$line[0]] = urldecode($line[1]);
            }
            if (!empty($resultsarray["L_ERRORCODE0"]) && $resultsarray["L_ERRORCODE0"] != "11002") {
                echo "<p><b>PayPal API Error Message</b></p><p>" . $resultsarray["L_SEVERITYCODE0"] . " Code: " . $resultsarray["L_ERRORCODE0"] . " - " . $resultsarray["L_SHORTMESSAGE0"] . " - " . $resultsarray["L_LONGMESSAGE0"] . "</p>";
            } else {
                if ($resultsarray["L_ERRORCODE0"] == "11002") {
                    global $infobox;
                    infoBox("Search Results Truncated", "There were more than 100 matching transactions for the selected criteria. Please make your search parameters more specific to see all results");
                    echo $infobox;
                }
                $aInt->sortableTableInit("nopagination");
                for ($i = 0; $i < 100; $i++) {
                    if ($resultsarray["L_TYPE" . $i] == "Payment" && !empty($resultsarray["L_EMAIL" . $i])) {
                        $data = get_query_vals("tblaccounts", "tblclients.id AS userid, tblclients.firstname,tblclients.lastname,tblclients.companyname," . "tblaccounts.invoiceid,tblinvoices.total,tblinvoices.status", array("transid" => $resultsarray["L_TRANSACTIONID" . $i]), "", "", "", " tblclients ON tblclients.id = tblaccounts.userid" . " LEFT JOIN tblinvoices ON tblinvoices.id = tblaccounts.invoiceid");
                        $tabledata[] = $testarray = array("clientname" => $data["userid"] ? $data["companyname"] ? "<a href=\"clientssummary.php?userid=" . $data["userid"] . "\">" . $data["firstname"] . " " . $data["lastname"] . " (" . $data["companyname"] . ")</a>" : "<a href=\"clientssummary.php?userid=" . $data["userid"] . "\">" . $data["firstname"] . " " . $data["lastname"] . "</a>" : "Trans ID Not Found in WHMCS", "transid" => "<a href=\"addonmodules.php?module=paypal_addon&search=1&transid=" . $resultsarray["L_TRANSACTIONID" . $i] . "\">" . $resultsarray["L_TRANSACTIONID" . $i] . "<a/>", "datetime" => fromMySQLDate($resultsarray["L_TIMESTAMP" . $i], true), "name" => $resultsarray["L_NAME" . $i], "email" => $resultsarray["L_EMAIL" . $i], "amt" => $resultsarray["L_NETAMT" . $i], "fee" => $resultsarray["L_FEEAMT" . $i], "curcode" => $resultsarray["L_CURRENCYCODE" . $i], "status" => $resultsarray["L_STATUS" . $i], "invoiceid" => $data["invoiceid"] ? "<a href=\"invoices.php?action=edit&id=" . $data["invoiceid"] . "\">" . $data["invoiceid"] . "</a>" : "-", "invoiceamt" => $data["invoiceid"] ? $data["total"] : "-", "invoicestatus" => $data["invoiceid"] ? $data["status"] : "-");
                    }
                }
                echo $aInt->sortableTable(array("Client Name", "Transaction ID", "Date/Time", " Payer Name", "Payer Email", "Amount", "Fee", "Currency Code", "Transaction Status", "Invoice ID", "Invoice Amount", "Invoice Status"), $tabledata);
            }
        } else {
            global $infobox;
            infoBox("Start Date Required", "You must enter a start and end date to search between");
            echo $infobox;
        }
    }
}

?>