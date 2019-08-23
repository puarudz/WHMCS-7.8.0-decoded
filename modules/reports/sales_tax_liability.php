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
$reportdata["title"] = "Sales Tax Liability";
$reportdata["description"] = "This report shows sales tax liability for the selected period";
$reportdata["currencyselections"] = true;
$range = App::getFromRequest('range');
if (!$range) {
    $today = Carbon::today()->endOfDay();
    $lastWeek = Carbon::today()->subDays(6)->startOfDay();
    $range = $lastWeek->toAdminDateFormat() . ' - ' . $today->toAdminDateFormat();
}
$currencyID = (int) $currencyid;
$reportdata['headertext'] = '';
if (!$print) {
    $reportdata['headertext'] = <<<HTML
<form method="post" action="reports.php?report={$report}">
    <div class="report-filters-wrapper">
        <div class="inner-container">
            <h3>Filters</h3>
            <div class="row">
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
                            />
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                {$aInt->lang('reports', 'generateReport')}
            </button>
        </div>
    </div>
</form>
HTML;
}
if ($calculate) {
    $dateRange = Carbon::parseDateRangeValue($range);
    $queryStartDate = $dateRange['from']->toDateTimeString();
    $queryEndDate = $dateRange['to']->toDateString();
    $query = <<<QUERY
SELECT COUNT(*), SUM(total), SUM(tblinvoices.credit), SUM(tax), SUM(tax2)
FROM tblinvoices
INNER JOIN tblclients ON tblclients.id = tblinvoices.userid
WHERE datepaid >= '{$queryStartDate}'
    AND datepaid <= '{$queryEndDate} 23:59:59'
    AND tblinvoices.status = 'Paid'
    AND currency = {$currencyID}
    AND (SELECT count(tblinvoiceitems.id)
        FROM tblinvoiceitems
        WHERE invoiceid = tblinvoices.id
            AND (type = 'AddFunds' OR type = 'Invoice')
        ) = 0;
QUERY;
    $result = full_query($query);
    $data = mysql_fetch_array($result);
    $numinvoices = $data[0];
    $total = $data[1] + $data[2];
    $tax = $data[3];
    $tax2 = $data[4];
    if (!$total) {
        $total = "0.00";
    }
    if (!$tax) {
        $tax = "0.00";
    }
    if (!$tax2) {
        $tax2 = "0.00";
    }
    $reportdata["headertext"] .= "<br>{$numinvoices} Invoices Found<br><B>Total Invoiced:</B> " . formatCurrency($total) . " &nbsp; <B>Tax Level 1 Liability:</B> " . formatCurrency($tax) . " &nbsp; <B>Tax Level 2 Liability:</B> " . formatCurrency($tax2);
}
$reportdata["headertext"] .= "</center>";
$reportdata["tableheadings"] = array($aInt->lang('fields', 'invoiceid'), $aInt->lang('fields', 'clientname'), $aInt->lang('fields', 'invoicedate'), $aInt->lang('fields', 'datepaid'), $aInt->lang('fields', 'subtotal'), $aInt->lang('fields', 'tax'), $aInt->lang('fields', 'credit'), $aInt->lang('fields', 'total'));
$query = <<<QUERY
SELECT tblinvoices.*, tblclients.firstname, tblclients.lastname
FROM tblinvoices
INNER JOIN tblclients ON tblclients.id = tblinvoices.userid
WHERE datepaid >= '{$queryStartDate}'
    AND datepaid <= '{$queryEndDate} 23:59:59'
    AND tblinvoices.status = 'Paid'
    AND currency = {$currencyID}
    AND (SELECT count(tblinvoiceitems.id)
        FROM tblinvoiceitems
        WHERE invoiceid = tblinvoices.id
            AND (type = 'AddFunds' OR type = 'Invoice')
        ) = 0
ORDER BY date ASC;
QUERY;
$result = full_query($query);
while ($data = mysql_fetch_array($result)) {
    $id = $data["id"];
    $userid = $data["userid"];
    $client = $data["firstname"] . " " . $data["lastname"];
    $date = fromMySQLDate($data["date"]);
    $datepaid = fromMySQLDate($data["datepaid"]);
    $currency = getCurrency($userid);
    $subtotal = $data["subtotal"];
    $credit = $data["credit"];
    $tax = $data["tax"] + $data["tax2"];
    $total = $data["total"] + $credit;
    $reportdata["tablevalues"][] = array("{$id}", "{$client}", "{$date}", "{$datepaid}", "{$subtotal}", "{$tax}", "{$credit}", "{$total}");
}
$data["footertext"] = "This report excludes invoices that affect a clients credit balance " . "since this income will be counted and reported when it is applied to invoices for products/services.";

?>