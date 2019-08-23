<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

use WHMCS\Carbon;
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
$reportdata["title"] = "Client Account Register Balance";
$reportdata["description"] = "This report provides a statement of account for individual client accounts.";
$userid = '';
if (App::isInRequest('userid') && App::getFromRequest('userid')) {
    $userid = App::getFromRequest('userid');
}
$range = App::getFromRequest('range');
$reportdata['headertext'] = '';
if (!$print) {
    $reportdata["headertext"] = <<<HTML
<form method="post" action="reports.php?report={$report}">
    <div class="report-filters-wrapper">
        <div class="inner-container">
            <h3>Filters</h3>
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="form-group">
                        <label for="inputFilterClient">{$aInt->lang('fields', 'client')}</label>
                        {$aInt->clientsDropDown($userid)}
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="form-group">
                        <label for="inputFilterDate">{$dateRangeText}</label>
                        <div class="form-group date-picker-prepend-icon">
                            <label for="inputFilterDate" class="field-icon">
                                <i class="fal fa-calendar-alt"></i>
                            </label>
                            <input id="inputFilterDate"
                                   type="text"
                                   name="range"
                                   value="{$range}"
                                   class="form-control date-picker-search"
                                   placeholder="{$optionalText}"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                {$aInt->lang('global', 'apply')}
            </button>
        </div>
    </div>
</form>
HTML;
}
$currency = getCurrency($userid);
$statement = array();
$count = $balance = $totalcredits = $totaldebits = 0;
if ($userid) {
    $result = select_query("tblinvoices", "", "userid='" . db_escape_string($userid) . "' AND status IN ('Unpaid','Paid','Collections')", "date", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $invoiceid = $data["id"];
        $date = $data["date"];
        $total = $data["credit"] + $data["total"];
        $result2 = select_query("tblinvoiceitems", "id", "invoiceid='{$invoiceid}' AND (type='AddFunds' OR type='Invoice')");
        $data = mysql_fetch_array($result2);
        $addfunds = $data[0];
        if (!$addfunds) {
            $statement[str_replace('-', '', $date) . "_" . $count] = array("Invoice", $date, "<a href=\"invoices.php?action=edit&id={$invoiceid}\" target=\"_blank\">#{$invoiceid}</a>", 0, $total);
        }
        $count++;
    }
    $result = select_query("tblaccounts", "", "userid='{$userid}'", "date", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $transid = $data["id"];
        $date = $data["date"];
        $description = $data["description"];
        $amountin = $data["amountin"];
        $amountout = $data["amountout"];
        $invoiceid = $data["invoiceid"];
        $date = substr($date, 0, 10);
        $result2 = select_query("tblinvoiceitems", "type", array("invoiceid" => $invoiceid));
        $data = mysql_fetch_array($result2);
        $itemtype = $data[0];
        if ($itemtype == "AddFunds") {
            $description = "Credit Prefunding";
        } elseif ($itemtype == "Invoice") {
            $description = "Mass Invoice Payment - ";
            $result2 = select_query("tblinvoiceitems", "relid", array("invoiceid" => $invoiceid), "relid", "ASC");
            while ($data = mysql_fetch_array($result2)) {
                $invoiceid = $data[0];
                $description .= "<a href=\"invoices.php?action=edit&id={$invoiceid}\" target=\"_blank\">#{$invoiceid}</a>, ";
            }
            $description = substr($description, 0, -2);
        } else {
            $description = $description;
            if ($invoiceid) {
                $description .= " - <a href=\"invoices.php?action=edit&id={$invoiceid}\" target=\"_blank\">#{$invoiceid}</a>";
            }
        }
        $statement[str_replace('-', '', $date) . "_" . $count] = array("Transaction", $date, $description, $amountin, $amountout);
        $count++;
    }
}
$datefrom = $dateto = '';
if ($range) {
    $dateRange = Carbon::parseDateRangeValue($range);
    $datefrom = $dateRange['from'];
    $dateto = $dateRange['to'];
}
$reportdata["tableheadings"] = array("Type", "Date", "Description", "Credits", "Debits", "Balance");
ksort($statement);
foreach ($statement as $date => $entry) {
    $date = Carbon::createFromFormat('Ymd', substr($date, 0, 8));
    if (!$dateto || $date->lt($dateto)) {
        $totalcredits += $entry[3];
        $totaldebits -= $entry[4];
        $balance += $entry[3] - $entry[4];
    }
    if (!$dateto || $date->gt($datefrom) && $date->lt($dateto)) {
        $reportdata["tablevalues"][] = array($entry[0], fromMySQLDate($entry[1]), $entry[2], formatCurrency($entry[3]), formatCurrency($entry[4]), formatCurrency($balance));
    }
}
$reportdata["tablevalues"][] = array('#efefef', '', '', '<b>Ending Balance</b>', '<b>' . formatCurrency($totalcredits) . '</b>', '<b>' . formatCurrency($totaldebits) . '</b>', '<b>' . formatCurrency($balance) . '</b>');

?>