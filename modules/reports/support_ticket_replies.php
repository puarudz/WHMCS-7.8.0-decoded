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
$reportdata['title'] = "Support Ticket Replies for " . $currentmonth . " " . $currentyear;
$reportdata['description'] = "This report shows a breakdown of support tickets dealt with per admin for a given month";
$reportdata['monthspagination'] = true;
$reportdata['tableheadings'][] = "Admin";
for ($day = 1; $day <= 31; $day++) {
    $reportdata['tableheadings'][] = $day;
}
$reportvalues = array();
$query = "SELECT admin, date_format(date, '%e'), COUNT(tid) AS totalreplies, COUNT(DISTINCT tid) AS totaltickets FROM tblticketreplies WHERE date LIKE '" . $year . "-" . $month . "-%' AND admin!='' GROUP BY admin, date_format(date, '%e') ORDER BY admin ASC, date ASC";
$result = full_query($query);
while ($data = mysql_fetch_array($result)) {
    $adminname = $data[0];
    $day = $data[1];
    $reportvalues[$adminname][$day] = array("totalreplies" => $data[2], "totaltickets" => $data[3]);
}
$rc = 0;
foreach ($reportvalues as $adminname => $values) {
    $reportdata['tablevalues'][$rc][] = "**{$adminname}";
    $rc++;
    $reportdata['tablevalues'][$rc][] = "Tickets";
    $reportdata['tablevalues'][$rc + 1][] = "Replies";
    for ($day = 1; $day <= 31; $day++) {
        $reportdata['tablevalues'][$rc][] = isset($reportvalues[$adminname][$day]['totaltickets']) ? $reportvalues[$adminname][$day]['totaltickets'] : '';
        $reportdata['tablevalues'][$rc + 1][] = isset($reportvalues[$adminname][$day]['totalreplies']) ? $reportvalues[$adminname][$day]['totalreplies'] : '';
    }
    $rc += 2;
}

?>