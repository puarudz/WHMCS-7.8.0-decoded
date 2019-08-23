<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
$statuses = array("New" => array("count" => 0, "overdue" => 0), "Pending" => array("count" => 0, "overdue" => 0), "In Progress" => array("count" => 0, "overdue" => 0), "Completed" => array("count" => 0, "overdue" => 0), "Postponed" => array("count" => 0, "overdue" => 0));
$todo_result = full_query("SELECT status, COUNT(*) AS count FROM tbltodolist GROUP BY status");
while ($todo = mysql_fetch_assoc($todo_result)) {
    $statuses[$todo["status"]]["count"] = $todo["count"];
}
$todo_over_due_result = full_query("SELECT status, COUNT(*) AS count FROM tbltodolist WHERE DATE(duedate) <= CURDATE() GROUP BY status");
while ($todo_over_due = mysql_fetch_assoc($todo_over_due_result)) {
    $statuses[$todo_over_due["status"]]["overdue"] = $todo_over_due["count"];
}
$apiresults = array("result" => "success", "totalresults" => 5);
foreach ($statuses as $key => $status) {
    $apiresults["todoitemstatuses"]["status"][] = array("type" => $key, "count" => $status["count"], "overdue" => $status["overdue"]);
}
$responsetype = "xml";

?>