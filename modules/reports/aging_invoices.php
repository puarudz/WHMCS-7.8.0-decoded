<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
$reportdata["title"] = "Aging Invoices";
$reportdata["description"] = "A summary of outstanding invoices broken down into the period of which they are overdue";
$reportdata["tableheadings"][] = "Period";
foreach ($currencies as $currencyid => $currencyname) {
    $reportdata["tableheadings"][] = "{$currencyname} Amount";
}
$totals = array();
for ($day = 0; $day < 120; $day += 30) {
    $startdate = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - $day, date("Y")));
    $enddate = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - ($day + 30), date("Y")));
    $rowdata = array();
    $rowdata[] = "{$day} - " . ($day + 30);
    $currencytotals = array();
    $query = "SELECT tblclients.currency,SUM(tblinvoices.total),(SELECT SUM(amountin-amountout) FROM tblaccounts INNER JOIN tblinvoices ON tblinvoices.id=tblaccounts.invoiceid INNER JOIN tblclients t2 ON t2.id=tblinvoices.userid WHERE tblinvoices.duedate<='" . db_make_safe_date($startdate) . "' AND tblinvoices.duedate>='" . db_make_safe_date($enddate) . "' AND tblinvoices.status='Unpaid' AND t2.currency=tblclients.currency) FROM tblinvoices INNER JOIN tblclients ON tblclients.id=tblinvoices.userid WHERE tblinvoices.duedate<='" . db_make_safe_date($startdate) . "' AND tblinvoices.duedate>='" . db_make_safe_date($enddate) . "' AND tblinvoices.status='Unpaid' GROUP BY tblclients.currency";
    $result = full_query($query);
    while ($data = mysql_fetch_array($result)) {
        $currencytotals[$data[0]] = $data[1] - $data[2];
    }
    foreach ($currencies as $currencyid => $currencyname) {
        $currencyamount = $currencytotals[$currencyid];
        if (!$currencyamount) {
            $currencyamount = 0;
        }
        $totals[$currencyid] += $currencyamount;
        $currency = getCurrency('', $currencyid);
        $rowdata[] = formatCurrency($currencyamount);
        if ($currencyid == $defaultcurrencyid) {
            $chartdata['rows'][] = array('c' => array(array('v' => "{$day} - " . ($day + 30)), array('v' => $currencyamount, 'f' => formatCurrency($currencyamount))));
        }
    }
    $reportdata["tablevalues"][] = $rowdata;
}
$startdate = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 120, date("Y")));
$rowdata = array();
$rowdata[] = "120 +";
$currencytotals = array();
$query = "SELECT tblclients.currency,SUM(tblinvoices.total) FROM tblinvoices INNER JOIN tblclients ON tblclients.id=tblinvoices.userid WHERE tblinvoices.duedate<='" . db_make_safe_date($startdate) . "' AND tblinvoices.status='Unpaid' GROUP BY tblclients.currency";
$result = full_query($query);
while ($data = mysql_fetch_array($result)) {
    $currencytotals[$data[0]] = $data[1];
}
foreach ($currencies as $currencyid => $currencyname) {
    $currencyamount = $currencytotals[$currencyid];
    if (!$currencyamount) {
        $currencyamount = 0;
    }
    $totals[$currencyid] += $currencyamount;
    $currency = getCurrency('', $currencyid);
    $rowdata[] = formatCurrency($currencyamount);
}
$reportdata["tablevalues"][] = $rowdata;
$rowdata = array();
$rowdata[] = "<b>Total</b>";
foreach ($currencies as $currencyid => $currencyname) {
    $currencytotal = $totals[$currencyid];
    if (!$currencytotal) {
        $currencytotal = 0;
    }
    $currency = getCurrency('', $currencyid);
    $rowdata[] = "<b>" . formatCurrency($currencytotal) . "</b>";
}
$reportdata["tablevalues"][] = $rowdata;
$chartdata['cols'][] = array('label' => 'Days Range', 'type' => 'string');
$chartdata['cols'][] = array('label' => 'Value', 'type' => 'number');
$args = array();
$args['legendpos'] = 'right';
$reportdata["footertext"] = $chart->drawChart('Pie', $chartdata, $args, '300px');

?>