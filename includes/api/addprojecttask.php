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
if (!function_exists("getClientsDetails")) {
    require ROOTDIR . "/includes/clientfunctions.php";
}
if (!function_exists("saveCustomFields")) {
    require ROOTDIR . "/includes/customfieldfunctions.php";
}
if (!isset($_REQUEST["projectid"])) {
    $apiresults = array("result" => "error", "message" => "Project ID Not Set");
} else {
    if (isset($_REQUEST["projectid"])) {
        $result = select_query("mod_project", "", array("id" => (int) $projectid));
        $data = mysql_fetch_assoc($result);
        $projectid = $data["id"];
        if (!$projectid) {
            $apiresults = array("result" => "error", "message" => "Project ID Not Found");
            return NULL;
        }
    }
    if (isset($_REQUEST["userid"])) {
        $result_userid = select_query("tblclients", "id", array("id" => $_REQUEST["userid"]));
        $data_userid = mysql_fetch_array($result_userid);
        if (!$data_userid["id"]) {
            $apiresults = array("result" => "error", "message" => "Client ID Not Found");
            return NULL;
        }
    }
    if (isset($_REQUEST["adminid"])) {
        $result_adminid = select_query("tbladmins", "id", array("id" => $_REQUEST["adminid"]));
        $data_adminid = mysql_fetch_array($result_adminid);
        if (!$data_adminid["id"]) {
            $apiresults = array("result" => "error", "message" => "Admin ID Not Found");
            return NULL;
        }
    }
    if (!isset($_REQUEST["task"])) {
        $apiresults = array("result" => "error", "message" => "A task description must be specified");
    } else {
        $ordervalue = get_query_val("mod_projecttasks", "`order`", array("projectid" => $projectid), "order", "DESC");
        $projectid = $_REQUEST["projectid"];
        $adminid = isset($_REQUEST["adminid"]) ? $data_adminid["id"] : 0;
        $task = $_REQUEST["task"];
        $notes = $_REQUEST["notes"];
        $duedate = $_REQUEST["duedate"];
        $completed = isset($_REQUEST["completed"]) ? 1 : 0;
        $billed = isset($_REQUEST["billed"]) ? 1 : 0;
        $created = "now()";
        $ordervalue++;
        $apply = insert_query("mod_projecttasks", array("projectid" => $projectid, "adminid" => $adminid, "task" => $task, "notes" => $notes, "completed" => $completed, "created" => $created, "duedate" => $duedate, "billed" => $billed, "order" => $ordervalue));
        $apiresults = array("result" => "success", "message" => "Task has been added");
    }
}

?>