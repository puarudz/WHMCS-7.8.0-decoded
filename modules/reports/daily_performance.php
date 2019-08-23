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
$reportdata["title"] = "Daily Performance for " . $months[(int) $month] . " " . $year;
$reportdata["description"] = "This report shows a daily activity summary for a given month.";
$reportdata["monthspagination"] = true;
$reportdata["tableheadings"] = array("Date", "Completed Orders", "New Invoices", "Paid Invoices", "Opened Tickets", "Ticket Replies", "Cancellation Requests");
$reportvalues = array();
$query = "SELECT date_format(date, '%e'),COUNT(id) FROM tblorders WHERE date LIKE '" . $year . "-" . $month . "-%' AND status='Active' GROUP BY date_format(date, '%e') ORDER BY date ASC";
$result = full_query($query);
while ($data = mysql_fetch_array($result)) {
    $reportvalues['orders_active'][$data[0]] = $data[1];
}
$query = "SELECT date_format(date, '%e'),COUNT(id) FROM tblinvoices WHERE date LIKE '" . $year . "-" . $month . "-%' GROUP BY date_format(date, '%e') ORDER BY date ASC";
$result = full_query($query);
while ($data = mysql_fetch_array($result)) {
    $reportvalues['invoices_new'][$data[0]] = $data[1];
}
$query = "SELECT date_format(datepaid, '%e'),COUNT(id) FROM tblinvoices WHERE datepaid LIKE '" . $year . "-" . $month . "-%' GROUP BY date_format(datepaid, '%e') ORDER BY datepaid ASC";
$result = full_query($query);
while ($data = mysql_fetch_array($result)) {
    $reportvalues['invoices_paid'][$data[0]] = $data[1];
}
$query = "SELECT date_format(date, '%e'),COUNT(id) FROM tbltickets WHERE date LIKE '" . $year . "-" . $month . "-%' GROUP BY date_format(date, '%e') ORDER BY date ASC";
$result = full_query($query);
while ($data = mysql_fetch_array($result)) {
    $reportvalues['tickets_new'][$data[0]] = $data[1];
}
$query = "SELECT date_format(date, '%e'),COUNT(id) FROM tblticketreplies WHERE date LIKE '" . $year . "-" . $month . "-%' AND admin!='' GROUP BY date_format(date, '%e') ORDER BY date ASC";
$result = full_query($query);
while ($data = mysql_fetch_array($result)) {
    $reportvalues['tickets_staff_replies'][$data[0]] = $data[1];
}
$query = "SELECT date_format(date, '%e'),COUNT(id) FROM tblcancelrequests WHERE date LIKE '" . $year . "-" . $month . "-%' GROUP BY date_format(date, '%e') ORDER BY date ASC";
$result = full_query($query);
while ($data = mysql_fetch_array($result)) {
    $reportvalues['cancellations_new'][$data[0]] = $data[1];
}
for ($day = 1; $day <= 31; $day++) {
    $date = date("Y-m-d", mktime(0, 0, 0, $month, $day, $year));
    $daytext = date("l", mktime(0, 0, 0, $month, $day, $year));
    $neworders = isset($reportvalues['orders_active'][$day]) ? $reportvalues['orders_active'][$day] : '0';
    $newinvoices = isset($reportvalues['invoices_new'][$day]) ? $reportvalues['invoices_new'][$day] : '0';
    $paidinvoices = isset($reportvalues['invoices_paid'][$day]) ? $reportvalues['invoices_paid'][$day] : '0';
    $newtickets = isset($reportvalues['tickets_new'][$day]) ? $reportvalues['tickets_new'][$day] : '0';
    $ticketreplies = isset($reportvalues['tickets_staff_replies'][$day]) ? $reportvalues['tickets_staff_replies'][$day] : '0';
    $cancellations = isset($reportvalues['cancellations_new'][$day]) ? $reportvalues['cancellations_new'][$day] : '0';
    $reportdata["tablevalues"][] = array($daytext . ' ' . fromMySQLDate($date), $neworders, $newinvoices, $paidinvoices, $newtickets, $ticketreplies, $cancellations);
    $chartdata['rows'][] = array('c' => array(array('v' => fromMySQLDate($date)), array('v' => (int) $neworders), array('v' => (int) $newinvoices), array('v' => (int) $paidinvoices), array('v' => (int) $newtickets), array('v' => (int) $ticketreplies), array('v' => (int) $cancellations)));
}
$chartdata['cols'][] = array('label' => 'Day', 'type' => 'string');
$chartdata['cols'][] = array('label' => 'Completed Orders', 'type' => 'number');
$chartdata['cols'][] = array('label' => 'New Invoices', 'type' => 'number');
$chartdata['cols'][] = array('label' => 'Paid Invoices', 'type' => 'number');
$chartdata['cols'][] = array('label' => 'Opened Tickets', 'type' => 'number');
$chartdata['cols'][] = array('label' => 'Ticket Replies', 'type' => 'number');
$chartdata['cols'][] = array('label' => 'Cancellation Requests', 'type' => 'number');
$args = array();
$args['legendpos'] = 'right';
$reportdata["headertext"] = $chart->drawChart('Area', $chartdata, $args, '400px');

?>