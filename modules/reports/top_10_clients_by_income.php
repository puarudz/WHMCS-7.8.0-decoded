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
$reportdata["title"] = "Top 10 Clients by Income";
$reportdata["description"] = "This report shows the 10 clients with the highest net income according to the transactions entered in WHMCS.";
$reportdata["tableheadings"] = array("Client ID", "Client Name", "Total Amount In", "Total Fees", "Total Amount Out", "Balance");
$query = "SELECT tblclients.id,tblclients.firstname, tblclients.lastname, SUM(tblaccounts.amountin/tblaccounts.rate), SUM(tblaccounts.fees/tblaccounts.rate), SUM(tblaccounts.amountout/tblaccounts.rate), SUM((tblaccounts.amountin/tblaccounts.rate)-(tblaccounts.fees/tblaccounts.rate)-(tblaccounts.amountout/tblaccounts.rate)) AS balance, tblaccounts.rate FROM tblaccounts INNER JOIN tblclients ON tblclients.id = tblaccounts.userid GROUP BY userid ORDER BY balance DESC LIMIT 0,10";
$result = full_query($query);
while ($data = mysql_fetch_array($result)) {
    $userid = $data[0];
    $currency = getCurrency();
    $rate = $data['rate'] == "1.00000" ? '' : '*';
    $clientlink = '<a href="clientssummary.php?userid=' . $data[0] . '">';
    $reportdata["tablevalues"][] = array($clientlink . $data[0] . '</a>', $clientlink . $data[1] . ' ' . $data[2] . '</a>', formatCurrency($data[3]) . " {$rate}", formatCurrency($data[4]) . " {$rate}", formatCurrency($data[5]) . " {$rate}", formatCurrency($data[6]) . " {$rate}");
    $chartdata['rows'][] = array('c' => array(array('v' => $data[1] . ' ' . $data[2]), array('v' => round($data[6], 2), 'f' => formatCurrency($data[6]))));
}
$reportdata["footertext"] = "<p>* denotes converted to default currency</p>";
$chartdata['cols'][] = array('label' => 'Client', 'type' => 'string');
$chartdata['cols'][] = array('label' => 'Balance', 'type' => 'number');
$args = array();
$args['legendpos'] = 'right';
$reportdata["headertext"] = $chart->drawChart('Pie', $chartdata, $args, '300px');

?>