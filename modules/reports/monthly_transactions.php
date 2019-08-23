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
$reportdata["title"] = "Monthly Transactions Report for " . $months[(int) $month] . " " . $year;
$reportdata["description"] = "This report provides a summary of daily payments activity for a given month. The Amount Out figure includes both expenditure transactions and refunds.";
$reportdata["currencyselections"] = true;
$reportdata["monthspagination"] = true;
$reportdata["tableheadings"] = array("Date", "Amount In", "Fees", "Amount Out", "Balance");
$reportvalues = array();
$query = "SELECT date_format(date, '%e'),SUM(amountin),SUM(fees),SUM(amountout) FROM tblaccounts INNER JOIN tblclients ON tblclients.id=tblaccounts.userid WHERE date LIKE '" . $year . "-" . $month . "-%' AND tblclients.currency=" . (int) $currencyid . " GROUP BY date_format(date, '%e') ORDER BY date ASC";
$result = full_query($query);
while ($data = mysql_fetch_array($result)) {
    $reportvalues[$data[0]] = array('amountin' => $data[1], 'fees' => $data[2], 'amountout' => $data[3]);
}
$query = "SELECT date_format(date, '%e'),SUM(amountin),SUM(fees),SUM(amountout) FROM tblaccounts WHERE date LIKE '" . $year . "-" . $month . "-%' AND userid='0' AND currency=" . (int) $currencyid . " GROUP BY date_format(date, '%e') ORDER BY date ASC";
$result = full_query($query);
while ($data = mysql_fetch_array($result)) {
    if (!array_key_exists($data[0], $reportvalues)) {
        $reportvalues[$data[0]] = array('amountin' => 0, 'fees' => 0, 'amountout' => 0);
    }
    $reportvalues[$data[0]]['amountin'] += $data[1];
    $reportvalues[$data[0]]['fees'] += $data[2];
    $reportvalues[$data[0]]['amountout'] += $data[3];
}
for ($dayOfTheMonth = 1; $dayOfTheMonth <= 31; $dayOfTheMonth++) {
    $amountin = isset($reportvalues[$dayOfTheMonth]['amountin']) ? $reportvalues[$dayOfTheMonth]['amountin'] : '0';
    $fees = isset($reportvalues[$dayOfTheMonth]['fees']) ? $reportvalues[$dayOfTheMonth]['fees'] : '0';
    $amountout = isset($reportvalues[$dayOfTheMonth]['amountout']) ? $reportvalues[$dayOfTheMonth]['amountout'] : '0';
    $dailybalance = $amountin - $fees - $amountout;
    $overallbalance += $dailybalance;
    $chartdata['rows'][] = array('c' => array(array('v' => $dayOfTheMonth), array('v' => $amountin, 'f' => formatCurrency($amountin)), array('v' => $fees, 'f' => formatCurrency($fees)), array('v' => $amountout, 'f' => formatCurrency($amountout))));
    $amountin = formatCurrency($amountin);
    $fees = formatCurrency($fees);
    $amountout = formatCurrency($amountout);
    $dailybalance = formatCurrency($dailybalance);
    $dayOfTheMonth = str_pad($dayOfTheMonth, 2, "0", STR_PAD_LEFT);
    $reportdata["tablevalues"][] = array(fromMySQLDate("{$year}-{$month}-{$dayOfTheMonth}"), $amountin, $fees, $amountout, $dailybalance);
}
$reportdata["footertext"] = '<p align="center"><strong>Balance: ' . formatCurrency($overallbalance) . '</strong></p>';
$chartdata['cols'][] = array('label' => 'Days Range', 'type' => 'string');
$chartdata['cols'][] = array('label' => 'Amount In', 'type' => 'number');
$chartdata['cols'][] = array('label' => 'Fees', 'type' => 'number');
$chartdata['cols'][] = array('label' => 'Amount Out', 'type' => 'number');
$args['colors'] = '#80D044,#F9D88C,#CC0000';
$reportdata["headertext"] = $chart->drawChart('Area', $chartdata, $args, '450px');

?>