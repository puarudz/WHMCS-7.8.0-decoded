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
if (!function_exists("getAdminPermsArray")) {
    require ROOTDIR . "/includes/adminfunctions.php";
}
$iphone = $whmcs->get_req_var("iphone");
$windows8app = $whmcs->get_req_var("windows8app");
$android = $whmcs->get_req_var("android");
$deptId = $whmcs->get_req_var("deptid");
$admin = WHMCS\User\Admin::find((int) WHMCS\Session::get("adminid"));
if (is_null($admin)) {
    $apiresults = array("result" => "error", "message" => "You must be authenticated as an admin user to perform this action");
} else {
    $apiresults = array("result" => "success", "adminid" => $admin->id, "name" => $admin->firstName . " " . $admin->lastName, "notes" => $admin->notes, "signature" => $admin->signature);
    $adminPermissionsArray = getAdminPermsArray();
    $adminPermissions = Illuminate\Database\Capsule\Manager::table("tbladminperms")->where("roleid", "=", $admin->roleId)->get();
    $apiresults["allowedpermissions"] = "";
    foreach ($adminPermissions as $adminPermission) {
        $apiresults["allowedpermissions"] .= $adminPermissionsArray[$adminPermission->permid] . ",";
    }
    $apiresults["departments"] .= $admin->supportDepts;
    $apiresults["allowedpermissions"] = substr($apiresults["allowedpermissions"], 0, -1);
    if ($iphone) {
        if (defined("IPHONELICENSE")) {
            exit("License Hacking Attempt Detected");
        }
        global $licensing;
        define("IPHONELICENSE", $licensing->isActiveAddon("iPhone App"));
        $apiresults["iphone"] = IPHONELICENSE;
    }
    if ($windows8app) {
        if (defined("WINDOWS8APPLICENSE")) {
            exit("License Hacking Attempt Detected");
        }
        global $licensing;
        define("WINDOWS8APPLICENSE", $licensing->isActiveAddon("Windows 8 App"));
        $apiresults["windows8app"] = WINDOWS8APPLICENSE;
    }
    if ($android) {
        if (defined("ANDROIDLICENSE")) {
            exit("License Hacking Attempt Detected");
        }
        if (!function_exists("getGatewaysArray")) {
            require ROOTDIR . "/includes/gatewayfunctions.php";
        }
        global $licensing;
        define("ANDROIDLICENSE", $licensing->isActiveAddon("Android App"));
        $apiresults["android"] = ANDROIDLICENSE;
        $statuses = array();
        $ticketStatuses = Illuminate\Database\Capsule\Manager::table("tblticketstatuses")->orderBy("sortorder")->get();
        foreach ($ticketStatuses as $ticketStatus) {
            $statuses[$ticketStatus->title] = 0;
        }
        $ticketStatuses = Illuminate\Database\Capsule\Manager::table("tbltickets")->selectRaw("status, COUNT(*) AS count")->groupBy("status")->get();
        if ($deptId) {
            $ticketStatuses = Illuminate\Database\Capsule\Manager::table("tbltickets")->selectRaw("status, COUNT(*) AS count")->where("did", "=", (int) $deptId)->groupBy("status")->get();
        }
        foreach ($ticketStatuses as $ticketStatus) {
            $statuses[$ticketStatus->status] = $ticketStatus->count;
        }
        foreach ($statuses as $status => $ticketCount) {
            $apiresults["supportstatuses"]["status"][] = array("title" => $status, "count" => $ticketCount);
        }
        $departments = array();
        $dept = Illuminate\Database\Capsule\Manager::table("tblticketdepartments")->get(array("id", "name"));
        foreach ($dept as $department) {
            $departments[$department->id] = $department->name;
        }
        foreach ($departments as $departmentId => $departmentName) {
            $apiresults["supportdepartments"]["department"] = array("id" => $departmentId, "name" => $departmentName, "count" => Illuminate\Database\Capsule\Manager::table("tbltickets")->where("did", "=", $departmentId)->count("id"));
        }
        $paymentMethods = getGatewaysArray();
        foreach ($paymentMethods as $module => $name) {
            $apiresults["paymentmethods"]["paymentmethod"][] = array("module" => $module, "displayname" => $name);
        }
    }
    $apiresults["requesttime"] = date("Y-m-d H:i:s");
}

?>