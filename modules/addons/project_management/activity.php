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
echo $headeroutput;
if (!project_management_checkperm("View Recent Activity")) {
    echo "<p>You do not have permission to view recent activity.</p>";
    return false;
}
$aInt->sortableTableInit("duedate", "ASC");
$tabledata = array();
$where = array();
if ($_REQUEST["projectid"]) {
    $where["projectid"] = (int) $_REQUEST["projectid"];
}
$result = select_query("mod_projectlog", "COUNT(*)", $where);
$data = mysql_fetch_array($result);
$numrows = $data[0];
$result = select_query("mod_projectlog", "mod_projectlog.*,(SELECT CONCAT(firstname,' ',lastname) FROM tbladmins WHERE tbladmins.id=mod_projectlog.adminid) AS admin,(SELECT title FROM mod_project WHERE mod_project.id=mod_projectlog.projectid) AS projectname, (SELECT adminid FROM mod_project WHERE mod_project.id=mod_projectlog.projectid) as assignedadminid", $where, "id", "DESC", $page * $limit . "," . $limit);
while ($data = mysql_fetch_array($result)) {
    $date = $data["date"];
    $projectid = $data["projectid"];
    $projectname = project_management_check_viewproject($projectid) ? "<a href=\"" . $modulelink . "&m=view&projectid=" . $projectid . "\">" . $data["projectname"] . "</a>" : $data["projectname"];
    $msg = $data["msg"];
    $admin = $data["admin"];
    $date = fromMySQLDate($date, true);
    $tabledata[] = array($date, $projectname, $msg, $admin);
}
echo "\n<div class=\"pm-addon\">\n    <ul class=\"nav nav-tabs pm-tabs\" role=\"tablist\">\n        <li>\n            <a href=\"addonmodules.php?module=project_management\">\n                <i class=\"fas fa-cube fa-fw\"></i>\n                " . $vars["_lang"]["projects"] . "\n            </a>\n        </li>\n        <li>\n            <a href=\"addonmodules.php?module=project_management&view=tasks\">\n                <i class=\"far fa-check-circle fa-fw\"></i>\n                " . $vars["_lang"]["tasks"] . "\n            </a>\n        </li>\n        <li>\n            <a href=\"addonmodules.php?module=project_management&m=reports\">\n                <i class=\"fas fa-chart-area fa-fw\"></i>\n                " . $vars["_lang"]["viewreports"] . "\n            </a>\n        </li>\n        <li class=\"active\">\n            <a href=\"addonmodules.php?module=project_management&m=activity\">\n                <i class=\"far fa-file-alt fa-fw\"></i>\n                " . $vars["_lang"]["recentactivity"] . "\n            </a>\n        </li>\n    </ul>\n\n    <div class=\"tab-content\">\n        <div role=\"tabpanel\" class=\"tab-pane active\" id=\"home\">\n            <div class=\"project-tab-padding\">\n                " . $aInt->sortableTable(array("Date", "Project", "Log Entry", "Admin User"), $tabledata) . "\n            </div>\n        </div>\n    </div>\n</div>";

?>