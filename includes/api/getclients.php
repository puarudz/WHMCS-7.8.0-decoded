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
$whmcs = App::self();
$limitStart = (int) $whmcs->get_req_var("limitstart");
$limitNum = (int) $whmcs->get_req_var("limitnum");
$sorting = strtoupper($whmcs->get_req_var("sorting"));
$search = $whmcs->get_req_var("search");
if (!$limitStart) {
    $limitStart = 0;
}
if (!$limitNum || $limitNum == 0) {
    $limitNum = 25;
}
if (!in_array($sorting, array("ASC", "DESC"))) {
    $sorting = "ASC";
}
$search = mysql_real_escape_string($search);
if (0 < strlen(trim($search))) {
    $whereStmt = "WHERE email LIKE '" . $search . "%' OR firstname LIKE '" . $search . "%' " . "OR lastname LIKE '" . $search . "%' OR companyname LIKE '" . $search . "%'" . "OR CONCAT(firstname, ' ', lastname) LIKE '" . $search . "%'";
} else {
    $whereStmt = "";
}
$sql = "SELECT SQL_CALC_FOUND_ROWS id, firstname, lastname, companyname, email, groupid, datecreated, status\n        FROM tblclients\n        " . $whereStmt . "\n        ORDER BY lastname " . $sorting . ", firstname " . $sorting . ", companyname " . $sorting . "\n        LIMIT " . (int) $limitStart . ", " . (int) $limitNum;
$result = full_query($sql);
$resultCount = full_query("SELECT FOUND_ROWS()");
$data = mysql_fetch_array($resultCount);
$totalResults = $data[0];
$apiresults = array("result" => "success", "totalresults" => $totalResults, "startnumber" => $limitStart, "numreturned" => mysql_num_rows($result));
while ($data = mysql_fetch_array($result)) {
    $id = $data["id"];
    $firstName = $data["firstname"];
    $lastName = $data["lastname"];
    $companyName = $data["companyname"];
    $email = $data["email"];
    $groupID = $data["groupid"];
    $dateCreated = $data["datecreated"];
    $status = $data["status"];
    $apiresults["clients"]["client"][] = array("id" => $id, "firstname" => $firstName, "lastname" => $lastName, "companyname" => $companyName, "email" => $email, "datecreated" => $dateCreated, "groupid" => $groupID, "status" => $status);
}
$responsetype = "xml";

?>