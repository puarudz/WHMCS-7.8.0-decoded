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
$projectid = (int) $_REQUEST["projectid"];
$taskid = (int) $_REQUEST["taskid"];
if (!$projectid) {
    $apiresults = array("result" => "error", "message" => "Project ID is Required");
} else {
    if (!$taskid) {
        $apiresults = array("result" => "error", "message" => "Task ID is Required");
    } else {
        $result = select_query("mod_project", "", array("id" => (int) $projectid));
        $data = mysql_fetch_assoc($result);
        $projectid = $data["id"];
        if (!$projectid) {
            $apiresults = array("result" => "error", "message" => "Project ID Not Found");
        } else {
            $result_taskid = select_query("mod_projecttasks", "id", array("id" => $_REQUEST["taskid"]));
            $data_taskid = mysql_fetch_array($result_taskid);
            if (!$data_taskid["id"]) {
                $apiresults = array("result" => "error", "message" => "Task ID Not Found");
            } else {
                delete_query("mod_projecttasks", array("id" => $taskid, "projectid" => $projectid));
                $apiresults = array("result" => "success", "message" => "Task has been deleted");
            }
        }
    }
}

?>