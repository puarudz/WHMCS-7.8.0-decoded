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
$reportdata["title"] = "New Customers";
$reportdata["description"] = "This report shows the total number of new customers, orders and complete orders and compares each of these to the previous year on the graph.";
$reportdata["tableheadings"] = array("Month", "New Signups", "Orders Placed", "Orders Completed");
for ($rawmonth = 1; $rawmonth <= 12; $rawmonth++) {
    $year2 = $year - 1;
    $month = str_pad($rawmonth, 2, 0, STR_PAD_LEFT);
    $newsignups = get_query_val("tblclients", "COUNT(*)", "datecreated LIKE '{$year}-{$month}-%'");
    $totalorders = get_query_val("tblorders", "COUNT(*)", "date LIKE '{$year}-{$month}-%'");
    $completedorders = get_query_val("tblorders", "COUNT(*)", "date LIKE '{$year}-{$month}-%' AND status='Active'");
    $newsignups2 = get_query_val("tblclients", "COUNT(*)", "datecreated LIKE '{$year2}-{$month}-%'");
    $totalorders2 = get_query_val("tblorders", "COUNT(*)", "date LIKE '{$year2}-{$month}-%'");
    $completedorders2 = get_query_val("tblorders", "COUNT(*)", "date LIKE '{$year2}-{$month}-%' AND status='Active'");
    $reportdata["tablevalues"][] = array($months[$rawmonth] . ' ' . $year, $newsignups, $totalorders, $completedorders);
    if (!$show || $show == "signups") {
        $chartdata['rows'][] = array('c' => array(array('v' => $months[$rawmonth]), array('v' => (int) $newsignups), array('v' => (int) $newsignups2)));
    }
    if ($show == "orders") {
        $chartdata['rows'][] = array('c' => array(array('v' => $months[$rawmonth]), array('v' => (int) $totalorders), array('v' => (int) $totalorders2)));
    }
    if ($show == "orderscompleted") {
        $chartdata['rows'][] = array('c' => array(array('v' => $months[$rawmonth]), array('v' => (int) $completedorders), array('v' => (int) $completedorders2)));
    }
}
$chartdata['cols'][] = array('label' => 'Month', 'type' => 'string');
$chartdata['cols'][] = array('label' => $year, 'type' => 'number');
$chartdata['cols'][] = array('label' => $year2, 'type' => 'number');
$args = array();
if (!$show || $show == "signups") {
    $args['title'] = 'New Signups';
    $args['colors'] = '#3366CC,#888888';
}
if ($show == "orders") {
    $args['title'] = 'Orders Placed';
    $args['colors'] = '#DC3912,#888888';
}
if ($show == "orderscompleted") {
    $args['title'] = 'Orders Completed';
    $args['colors'] = '#FF9900,#888888';
}
$args['legendpos'] = 'right';
$reportdata["headertext"] = $chart->drawChart('Area', $chartdata, $args, '400px') . '<p align="center">' . '<a href="reports.php' . $requeststr . '&show=signups">New Signups</a>' . ' | <a href="reports.php' . $requeststr . '&show=orders">Orders Placed</a>' . ' | <a href="reports.php' . $requeststr . '&show=orderscompleted">Orders Completed</a>' . '</p>';
$reportdata["yearspagination"] = true;

?>