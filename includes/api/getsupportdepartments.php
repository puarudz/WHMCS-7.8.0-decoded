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
$activestatuses = $awaitingreplystatuses = array();
$result = select_query("tblticketstatuses", "title,showactive,showawaiting", "");
while ($data = mysql_fetch_array($result)) {
    if ($data["showactive"]) {
        $activestatuses[] = $data[0];
    }
    if ($data["showawaiting"]) {
        $awaitingreplystatuses[] = $data[0];
    }
}
$deptfilter = "";
if (!$ignore_dept_assignments) {
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
    if (count($deptids)) {
        $deptfilter = "WHERE tblticketdepartments.id IN (" . db_build_in_array($deptids) . ") ";
    }
}
$result = full_query("SELECT id,name,(SELECT COUNT(id) FROM tbltickets WHERE merged_ticket_id = 0 AND did=tblticketdepartments.id AND status IN (" . db_build_in_array($awaitingreplystatuses) . ")) AS awaitingreply,(SELECT COUNT(id) FROM tbltickets WHERE merged_ticket_id = 0 AND did=tblticketdepartments.id AND status IN (" . db_build_in_array($activestatuses) . ")) AS opentickets FROM tblticketdepartments " . $deptfilter . "ORDER BY name ASC");
$apiresults = array("result" => "success", "totalresults" => mysql_num_rows($result));
while ($data = mysql_fetch_array($result)) {
    $apiresults["departments"]["department"][] = array("id" => $data["id"], "name" => $data["name"], "awaitingreply" => $data["awaitingreply"], "opentickets" => $data["opentickets"]);
}
$responsetype = "xml";

?>