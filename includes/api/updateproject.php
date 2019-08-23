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
if (!App::isInRequest("projectid")) {
    $apiresults = array("result" => "error", "message" => "Project ID Not Set");
} else {
    $projectId = (int) App::getFromRequest("projectid");
    $result = select_query("mod_project", "", array("id" => $projectId));
    $data = mysql_fetch_assoc($result);
    $projectId = $data["id"];
    if (!$projectId) {
        $apiresults = array("result" => "error", "message" => "Project ID Not Found");
    } else {
        $dataUserId = 0;
        if (App::isInRequest("userid")) {
            $dataUserId = get_query_val("tblclients", "id", array("id" => (int) App::getFromRequest("userid")));
            if (!$dataUserId) {
                $apiresults = array("result" => "error", "message" => "Client ID Not Found");
                return NULL;
            }
        }
        $dataAdminId = 0;
        if (App::isInRequest("adminid")) {
            $dataAdminId = get_query_val("tbladmins", "id", array("id" => (int) App::getFromRequest("adminid")));
            if (!$dataAdminId) {
                $apiresults = array("result" => "error", "message" => "Admin ID Not Found");
                return NULL;
            }
        }
        $status_main = "";
        if (App::isInRequest("status")) {
            $status = App::getFromRequest("status");
            $status_get = get_query_val("tbladdonmodules", "value", array("module" => "project_management", "setting" => "statusvalues"));
            $status_get = explode(",", $status_get);
            $status_main = in_array($status, $status_get) ? $status : $status_get[0];
        }
        $title = App::isInRequest("title") ? trim(App::getFromRequest("title")) : "";
        $adminId = $dataAdminId;
        $userId = $dataUserId;
        $ticketIds = App::getFromRequest("ticketids");
        $invoiceIds = App::getFromRequest("invoiceids");
        $notes = App::getFromRequest("notes");
        $status = $status_main;
        $dueDate = App::getFromRequest("duedate");
        $completed = App::isInRequest("completed") ? (int) (bool) App::getFromRequest("completed") : 0;
        $updateQuery = array();
        if ($title) {
            $updateQuery["title"] = $title;
        }
        if ($adminId) {
            $updateQuery["adminid"] = $adminId;
        }
        if ($userId) {
            $updateQuery["userid"] = $userId;
        }
        if ($ticketIds) {
            $updateQuery["ticketids"] = $ticketIds;
        }
        if ($invoiceIds) {
            $updateQuery["invoiceids"] = $invoiceIds;
        }
        if ($notes) {
            $updateQuery["notes"] = $notes;
        }
        if ($status) {
            $updateQuery["status"] = $status;
        }
        if ($dueDate) {
            $updateQuery["duedate"] = $dueDate;
        }
        if (App::isInRequest("completed")) {
            $updateQuery["completed"] = $completed;
        }
        if (0 < count($updateQuery)) {
            $updateQuery["lastmodified"] = "now()";
            update_query("mod_project", $updateQuery, array("id" => $projectId));
        }
        $apiresults = array("result" => "success", "message" => "Project Has Been Updated");
    }
}

?>