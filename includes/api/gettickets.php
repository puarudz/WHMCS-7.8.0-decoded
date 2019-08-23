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
if (!$limitstart) {
    $limitstart = 0;
}
if (!$limitnum) {
    $limitnum = 25;
}
$deptid = (int) App::get_req_var("deptid");
$clientid = (int) App::get_req_var("clientid");
$filters = array("merged_ticket_id=0");
if ($deptid) {
    $filters[] = "did=" . (int) $deptid;
}
if ($clientid) {
    $filters[] = "userid=" . (int) $clientid;
}
if (!empty($email)) {
    $filters[] = "(email='" . db_escape_string($email) . "' OR userid=(SELECT id FROM tblclients WHERE email='" . db_escape_string($email) . "'))";
}
if ($status == "Awaiting Reply") {
    $statusfilters = array();
    $result = select_query("tblticketstatuses", "title", array("showawaiting" => "1"));
    while ($data = mysql_fetch_array($result)) {
        $statusfilters[] = $data[0];
    }
    $filters[] = "tbltickets.status IN (" . db_build_in_array($statusfilters) . ")";
} else {
    if ($status == "All Active Tickets") {
        $statusfilters = array();
        $result = select_query("tblticketstatuses", "title", array("showactive" => "1"));
        while ($data = mysql_fetch_array($result)) {
            $statusfilters[] = $data[0];
        }
        $filters[] = "tbltickets.status IN (" . db_build_in_array($statusfilters) . ")";
    } else {
        if ($status == "My Flagged Tickets") {
            $statusfilters = array();
            $result = select_query("tblticketstatuses", "title", array("showactive" => "1"));
            while ($data = mysql_fetch_array($result)) {
                $statusfilters[] = $data[0];
            }
            $filters[] = "tbltickets.status IN (" . db_build_in_array($statusfilters) . ") AND flag=" . (int) $_SESSION["adminid"];
        } else {
            if ($status) {
                $filters[] = "status='" . db_escape_string($status) . "'";
            }
        }
    }
}
if (isset($subject)) {
    $filters[] = "title LIKE '%" . db_escape_string($subject) . "%'";
}
if (empty($ignore_dept_assignments)) {
    $result = select_query("tbladmins", "supportdepts", array("id" => $_SESSION["adminid"]));
    $data = mysql_fetch_array($result);
    $supportdepts = $data[0];
    $supportdepts = explode(",", $supportdepts);
    $deptids = array();
    foreach ($supportdepts as $id) {
        if (trim($id)) {
            $deptids[] = trim($id);
        }
    }
    if (0 < count($deptids)) {
        $filters[] = "did IN (" . db_build_in_array($deptids) . ")";
    } else {
        $filters[] = "did = 0";
    }
}
$where = implode(" AND ", $filters);
$result = select_query("tbltickets", "COUNT(id)", $where);
$data = mysql_fetch_array($result);
$totalresults = $data[0];
$apiresults = array("result" => "success", "totalresults" => $totalresults, "startnumber" => $limitstart);
$result = select_query("tbltickets", "", $where, "lastreply", "DESC", (string) $limitstart . "," . $limitnum);
$apiresults["numreturned"] = mysql_num_rows($result);
while ($data = mysql_fetch_array($result)) {
    $id = $data["id"];
    $tid = $data["tid"];
    $deptid = $data["did"];
    $userid = $data["userid"];
    $name = $data["name"];
    $email = $data["email"];
    $cc = $data["cc"];
    $c = $data["c"];
    $date = $data["date"];
    $subject = $data["title"];
    $message = $data["message"];
    $status = $data["status"];
    $priority = $data["urgency"];
    $admin = $data["admin"];
    $attachment = $data["attachment"];
    $attachmentsRemoved = (bool) (int) $data["attachments_removed"];
    $lastreply = $data["lastreply"];
    $flag = $data["flag"];
    $service = $data["service"];
    if ($userid) {
        $result2 = select_query("tblclients", "", array("id" => $userid));
        $data = mysql_fetch_array($result2);
        $name = $data["firstname"] . " " . $data["lastname"];
        if ($data["companyname"]) {
            $name .= " (" . $data["companyname"] . ")";
        }
        $email = $data["email"];
    }
    $apiresults["tickets"]["ticket"][] = array("id" => $id, "tid" => $tid, "deptid" => $deptid, "userid" => $userid, "name" => $name, "email" => $email, "cc" => $cc, "c" => $c, "date" => $date, "subject" => $subject, "status" => $status, "priority" => $priority, "admin" => $admin, "attachment" => $attachment, "attachments_removed" => $attachmentsRemoved, "lastreply" => $lastreply, "flag" => $flag, "service" => $service);
}
$responsetype = "xml";

?>