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
$reportdata['title'] = "Annual Income Report for " . $currentyear;
$reportdata['description'] = "This report shows the income received broken down by month converted to the base currency using rates at the time of the transaction";
$reportdata['yearspagination'] = true;
$currency = getCurrency(0, 1);
$reportdata['tableheadings'] = array("Month", "Amount In", "Fees", "Amount Out", "Balance");
$reportvalues = array();
$query = "SELECT date_format(date,'%m'), date_format(date,'%Y'), SUM(amountin/rate), SUM(fees/rate), SUM(amountout/rate) FROM tblaccounts WHERE date>='" . ($currentyear - 2) . "-01-01' GROUP BY date_format(date,'%M %Y') ORDER BY date ASC";
$result = full_query($query);
while ($data = mysql_fetch_array($result)) {
    $month = (int) $data[0];
    $year = (int) $data[1];
    $amountin = $data[2];
    $fees = $data[3];
    $amountout = $data[4];
    $monthlybalance = $amountin - $fees - $amountout;
    $reportvalues[$year][$month] = array($amountin, $fees, $amountout, $monthlybalance);
}
foreach ($months as $k => $monthName) {
    if ($monthName) {
        $amountin = $reportvalues[$currentyear][$k][0];
        $fees = $reportvalues[$currentyear][$k][1];
        $amountout = $reportvalues[$currentyear][$k][2];
        $monthlybalance = $reportvalues[$currentyear][$k][3];
        $reportdata['tablevalues'][] = array($monthName . ' ' . $currentyear, formatCurrency($amountin), formatCurrency($fees), formatCurrency($amountout), formatCurrency($monthlybalance));
        $overallbalance += $monthlybalance;
    }
}
$reportdata['footertext'] = '<p align="center"><strong>Balance: ' . formatCurrency($overallbalance) . '</strong></p>';
$chartdata['cols'][] = array('label' => 'Days Range', 'type' => 'string');
$chartdata['cols'][] = array('label' => $currentyear - 2, 'type' => 'number');
$chartdata['cols'][] = array('label' => $currentyear - 1, 'type' => 'number');
$chartdata['cols'][] = array('label' => $currentyear, 'type' => 'number');
for ($i = 1; $i <= 12; $i++) {
    $chartdata['rows'][] = array('c' => array(array('v' => $months[$i]), array('v' => $reportvalues[$currentyear - 2][$i][3], 'f' => formatCurrency($reportvalues[$currentyear - 2][$i][3])->toFull()), array('v' => $reportvalues[$currentyear - 1][$i][3], 'f' => formatCurrency($reportvalues[$currentyear - 1][$i][3])->toFull()), array('v' => $reportvalues[$currentyear][$i][3], 'f' => formatCurrency($reportvalues[$currentyear][$i][3])->toFull())));
}
$args = array();
$args['colors'] = '#3070CF,#F9D88C,#cb4c30';
$args['chartarea'] = '80,20,90%,350';
$reportdata['headertext'] = $chart->drawChart('Column', $chartdata, $args, '400px');

?>