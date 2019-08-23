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
if (!function_exists("recalcRecurringProductPrice")) {
    require ROOTDIR . "/includes/clientfunctions.php";
}
if (!function_exists("saveCustomFields")) {
    require ROOTDIR . "/includes/customfieldfunctions.php";
}
if (!function_exists("getCartConfigOptions")) {
    require ROOTDIR . "/includes/configoptionsfunctions.php";
}
$serviceid = (int) $whmcs->get_req_var("serviceid");
$result = select_query("tblhosting", "id,packageid,billingcycle,promoid,domainstatus", array("id" => $serviceid));
$data = mysql_fetch_array($result);
$serviceid = $data["id"];
if (!$serviceid) {
    $apiresults = array("result" => "error", "message" => "Service ID Not Found");
} else {
    $storedStatus = $data["domainstatus"];
    $status = $whmcs->get_req_var("status");
    $terminationDate = $whmcs->get_req_var("terminationdate");
    $completedDate = NULL;
    $updateqry = array();
    if ($pid) {
        $updateqry["packageid"] = $pid;
    }
    if ($serverid) {
        $updateqry["server"] = $serverid;
    }
    if ($regdate) {
        $updateqry["regdate"] = $regdate;
    }
    if ($nextduedate) {
        $updateqry["nextduedate"] = $nextduedate;
        $updateqry["nextinvoicedate"] = $nextduedate;
    }
    if ($domain) {
        $updateqry["domain"] = $domain;
    }
    if ($firstpaymentamount) {
        $updateqry["firstpaymentamount"] = $firstpaymentamount;
    }
    if ($recurringamount) {
        $updateqry["amount"] = $recurringamount;
    }
    if ($billingcycle) {
        $updateqry["billingcycle"] = $billingcycle;
    }
    if ($status && $status != $storedStatus) {
        switch ($status) {
            case "Terminated":
            case "Cancelled":
                if ((!$terminationDate || $terminationDate == "0000-00-00") && !in_array($storedStatus, array("Terminated", "Cancelled"))) {
                    $terminationDate = date("Y-m-d");
                }
                $completedDate = "0000-00-00";
                break;
            case "Completed":
                $completedDate = WHMCS\Carbon::today()->toDateString();
                $terminationDate = "0000-00-00";
                break;
            default:
                $terminationDate = "0000-00-00";
                $completedDate = "0000-00-00";
        }
        $updateqry["domainstatus"] = $status;
    }
    if ($terminationDate) {
        if (!$status) {
            switch ($storedStatus) {
                case "Terminated":
                case "Cancelled":
                    if ($terminationDate == "0000-00-00") {
                        unset($terminationDate);
                    }
                    break;
                default:
                    $terminationDate = "0000-00-00";
            }
        }
        if ($terminationDate) {
            $updateqry["termination_date"] = $terminationDate;
        }
    }
    if ($completedDate) {
        $updateqry["completed_date"] = $completedDate;
    }
    if ($serviceusername) {
        $updateqry["username"] = $serviceusername;
    }
    if ($servicepassword) {
        $updateqry["password"] = encrypt($servicepassword);
    }
    if ($subscriptionid) {
        $updateqry["subscriptionid"] = $subscriptionid;
    }
    if ($paymentmethod) {
        $updateqry["paymentmethod"] = $paymentmethod;
    }
    if ($promoid) {
        $updateqry["promoid"] = $promoid;
    }
    if ($overideautosuspend && $overideautosuspend != "off") {
        $updateqry["overideautosuspend"] = "1";
    } else {
        if ($overideautosuspend == "off") {
            $updateqry["overideautosuspend"] = "0";
        }
    }
    if ($overidesuspenduntil) {
        $updateqry["overidesuspenduntil"] = $overidesuspenduntil;
    }
    if ($ns1) {
        $updateqry["ns1"] = $ns1;
    }
    if ($ns2) {
        $updateqry["ns2"] = $ns2;
    }
    if ($dedicatedip) {
        $updateqry["dedicatedip"] = $dedicatedip;
    }
    if ($assignedips) {
        $updateqry["assignedips"] = $assignedips;
    }
    if ($notes) {
        $updateqry["notes"] = $notes;
    }
    if ($diskusage) {
        $updateqry["diskusage"] = $diskusage;
    }
    if ($disklimit) {
        $updateqry["disklimit"] = $disklimit;
    }
    if ($bwusage) {
        $updateqry["bwusage"] = $bwusage;
    }
    if ($bwlimit) {
        $updateqry["bwlimit"] = $bwlimit;
    }
    if ($lastupdate) {
        $updateqry["lastupdate"] = $lastupdate;
    }
    if ($suspendreason) {
        $updateqry["suspendreason"] = $suspendreason;
    }
    $unsetAttributes = $whmcs->get_req_var("unset");
    if (is_array($unsetAttributes) && !empty($unsetAttributes)) {
        $allowedVariables = array("domain", "serviceusername", "servicepassword", "subscriptionid", "ns1", "ns2", "dedicatedip", "assignedips", "notes", "suspendreason");
        foreach ($unsetAttributes as $unsetAttribute) {
            if (in_array($unsetAttribute, $allowedVariables)) {
                switch ($unsetAttribute) {
                    case "serviceusername":
                        $unsetAttribute = "username";
                        break;
                    case "servicepassword":
                        $unsetAttribute = "password";
                        break;
                }
                $updateqry[$unsetAttribute] = "";
            }
        }
    }
    if (0 < count($updateqry)) {
        update_query("tblhosting", $updateqry, array("id" => $serviceid));
    }
    if ($customfields) {
        if (!is_array($customfields)) {
            $customfields = base64_decode($customfields);
            $customfields = safe_unserialize($customfields);
        }
        saveCustomFields($serviceid, $customfields, "product", true);
    }
    if ($configoptions) {
        if (!is_array($configoptions)) {
            $configoptions = base64_decode($configoptions);
            $configoptions = safe_unserialize($configoptions);
        }
        foreach ($configoptions as $cid => $vals) {
            if (is_array($vals)) {
                $oid = $vals["optionid"];
                $qty = $vals["qty"];
            } else {
                $oid = $vals;
                $qty = 0;
            }
            if (get_query_val("tblhostingconfigoptions", "COUNT(*)", array("relid" => $serviceid, "configid" => $cid))) {
                update_query("tblhostingconfigoptions", array("optionid" => $oid, "qty" => $qty), array("relid" => $serviceid, "configid" => $cid));
            } else {
                insert_query("tblhostingconfigoptions", array("relid" => $serviceid, "configid" => $cid, "optionid" => $oid, "qty" => $qty));
            }
        }
    }
    if ($autorecalc) {
        if (!$pid) {
            $pid = $data["packageid"];
        }
        if (!$billingcycle) {
            $billingcycle = $data["billingcycle"];
        }
        if (!$promoid) {
            $promoid = $data["promoid"];
        }
        $recurringamount = recalcRecurringProductPrice($serviceid, "", $pid, $billingcycle, "empty", $promoid);
        update_query("tblhosting", array("amount" => $recurringamount), array("id" => $serviceid));
    }
    $apiresults = array("result" => "success", "serviceid" => $serviceid);
}

?>