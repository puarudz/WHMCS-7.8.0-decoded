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
$id = (int) $whmcs->get_req_var("id");
$result = select_query("tblhostingaddons", "id,addonid,hostingid,status", array("id" => $id));
$data = mysql_fetch_array($result);
if (!$data["id"]) {
    $apiresults = array("result" => "error", "message" => "Addon ID Not Found");
} else {
    $serviceid = $data["hostingid"];
    $currentstatus = $data["status"];
    $userid = (int) get_query_val("tblhosting", "userid", array("id" => $serviceid));
    $status = $whmcs->get_req_var("status");
    $terminationDate = $whmcs->get_req_var("terminationdate");
    $updateqry = array();
    if ($addonid) {
        $updateqry["addonid"] = $addonid;
    } else {
        $addonid = $data["addonid"];
    }
    if ($name) {
        $updateqry["name"] = $name;
    }
    if ($setupfee) {
        $updateqry["setupfee"] = $setupfee;
    }
    if ($recurring) {
        $updateqry["recurring"] = $recurring;
    }
    if ($billingcycle) {
        $updateqry["billingcycle"] = $billingcycle;
    }
    if ($nextduedate) {
        $updateqry["nextduedate"] = $nextduedate;
    }
    if ($nextinvoicedate) {
        $updateqry["nextinvoicedate"] = $nextinvoicedate;
    }
    if ($notes) {
        $updateqry["notes"] = $notes;
    }
    if ($status && $status != $currentstatus) {
        switch ($status) {
            case "Terminated":
            case "Cancelled":
                if ((!$terminationDate || $terminationDate == "0000-00-00") && !in_array($currentstatus, array("Terminated", "Cancelled"))) {
                    $terminationDate = date("Y-m-d");
                }
                break;
            default:
                $terminationDate = "0000-00-00";
        }
        $updateqry["status"] = $status;
    }
    if ($terminationDate) {
        if (!$status) {
            switch ($currentstatus) {
                case "Terminated":
                case "Cancelled":
                    if ($terminationDate == "0000-00-00") {
                        $terminationDate = date("Y-m-d");
                    }
                    break;
                default:
                    $terminationDate = "0000-00-00";
            }
        }
        $updateqry["termination_date"] = $terminationDate;
    }
    if (0 < count($updateqry)) {
        update_query("tblhostingaddons", $updateqry, array("id" => $id));
        logActivity("Modified Addon - Addon ID: " . $id . " - Service ID: " . $serviceid, $userid);
        if ($currentstatus != "Active" && $status == "Active") {
            run_hook("AddonActivated", array("id" => $id, "userid" => $userid, "serviceid" => $serviceid, "addonid" => $addonid));
        } else {
            if ($currentstatus != "Suspended" && $status == "Suspended") {
                run_hook("AddonSuspended", array("id" => $id, "userid" => $userid, "serviceid" => $serviceid, "addonid" => $addonid));
            } else {
                if ($currentstatus != "Terminated" && $status == "Terminated") {
                    run_hook("AddonTerminated", array("id" => $id, "userid" => $userid, "serviceid" => $serviceid, "addonid" => $addonid));
                } else {
                    if ($currentstatus != "Cancelled" && $status == "Cancelled") {
                        run_hook("AddonCancelled", array("id" => $id, "userid" => $userid, "serviceid" => $serviceid, "addonid" => $addonid));
                    } else {
                        if ($currentstatus != "Fraud" && $status == "Fraud") {
                            run_hook("AddonFraud", array("id" => $id, "userid" => $userid, "serviceid" => $serviceid, "addonid" => $addonid));
                        } else {
                            run_hook("AddonEdit", array("id" => $id, "userid" => $userid, "serviceid" => $serviceid, "addonid" => $addonid));
                        }
                    }
                }
            }
        }
        $apiresults = array("result" => "success", "id" => $id);
    } else {
        $apiresults = array("result" => "error", "id" => $id, "message" => "Nothing to Update");
    }
}

?>