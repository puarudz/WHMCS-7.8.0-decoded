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
if (!project_management_checkperm("View Reports")) {
    echo "<p>You do not have permission to view reports.</p>";
    return false;
}
$pmReportsPath = array("modules", "addons", "project_management", "reports");
$reports = new WHMCS\File\Directory(implode(DIRECTORY_SEPARATOR, $pmReportsPath));
$reportFiles = $reports->listFiles();
echo "\n<div class=\"pm-addon\">\n    <ul class=\"nav nav-tabs pm-tabs\" role=\"tablist\">\n        <li>\n            <a href=\"addonmodules.php?module=project_management\">\n                <i class=\"fas fa-cube fa-fw\"></i>\n                " . $vars["_lang"]["projects"] . "\n            </a>\n        </li>\n        <li>\n            <a href=\"addonmodules.php?module=project_management&view=tasks\">\n                <i class=\"far fa-check-circle fa-fw\"></i>\n                " . $vars["_lang"]["tasks"] . "\n            </a>\n        </li>\n        <li class=\"active\">\n            <a href=\"addonmodules.php?module=project_management&m=reports\">\n                <i class=\"fas fa-chart-area fa-fw\"></i>\n                " . $vars["_lang"]["viewreports"] . "\n            </a>\n        </li>\n        <li>\n            <a href=\"addonmodules.php?module=project_management&m=activity\">\n                <i class=\"far fa-file-alt fa-fw\"></i>\n                " . $vars["_lang"]["recentactivity"] . "\n            </a>\n        </li>\n    </ul>\n\n    <div class=\"tab-content\">\n        <div role=\"tabpanel\" class=\"tab-pane active\" id=\"home\">\n            <div class=\"project-tab-padding\">\n                ";
$chart = new WHMCS\Chart();
$chartData = array("cols" => array(array("label" => "Project", "type" => "string"), array("label" => "Completed Tasks", "type" => "number"), array("label" => "Incomplete Tasks", "type" => "number")), "rows" => array());
$statuses = get_query_val("tbladdonmodules", "value", array("module" => "project_management", "setting" => "completedstatuses"));
$statuses = explode(",", $statuses);
$result = select_query("mod_project", "id,title", "status NOT IN (" . db_build_in_array($statuses) . ")");
while ($data = mysql_fetch_array($result)) {
    $projectid = $data["id"];
    $title = $data["title"];
    $incompletetasks = get_query_val("mod_projecttasks", "COUNT(id)", array("projectid" => $projectid, "completed" => "0"));
    $completedtasks = get_query_val("mod_projecttasks", "COUNT(id)", array("projectid" => $projectid, "completed" => "1"));
    $chartData["rows"][] = array("c" => array(array("v" => $title), array("v" => $completedtasks, "f" => $completedtasks), array("v" => $incompletetasks, "f" => $incompletetasks)));
}
$args = array("title" => "Task Status per Project", "legendpos" => "right", "colors" => "#77CC56,#999", "stacked" => true);
echo $chart->drawChart("Column", $chartData, $args, "600px", "100%");
echo "<hr>\n<h2>Available Reports</h2>\n";
foreach ($reportFiles as $reportName) {
    $reportName = str_replace(".php", "", $reportName);
    $displayName = titleCase(str_replace("_", " ", $reportName));
    echo "<a href=\"reports.php?moduletype=addons&modulename=project_management&subdir=reports&report=" . $reportName . "\" target=\"_blank\" class=\"btn btn-default\">" . $displayName . "</a> ";
}
echo "\n            </div>\n        </div>\n    </div>\n</div>";

?>