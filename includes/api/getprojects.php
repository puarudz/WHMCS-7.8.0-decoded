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
$query = WHMCS\Database\Capsule::table("mod_project");
if ($userid) {
    $query = $query->where("userid", "=", $userid);
}
if ($title) {
    $query = $query->where("title", "like", $title);
}
if ($ticketids) {
    $query = $query->where("ticketids", "like", $ticketids);
}
if ($invoiceids) {
    $query = $query->where("invoiceids", "like", $invoiceids);
}
if ($notes) {
    $query = $query->where("notes", "like", $notes);
}
if (isset($_REQUEST["adminid"])) {
    $query = $query->where("adminid", "=", $_REQUEST["adminid"]);
}
if ($status) {
    $query = $query->where("status", "like", $status);
}
if ($created) {
    $query = $query->where("created", "like", $created);
}
if ($duedate) {
    $query = $query->where("duedate", "like", $duedate);
}
if ($completed) {
    $query = $query->where("completed", "like", $completed);
}
if ($lastmodified) {
    $query = $query->where("lastmodified", "like", $lastmodified);
}
$totalresults = $query->count();
$result = $query->orderBy("id", "ASC")->skip($limitstart)->limit($limitnum)->get();
$apiresults = array("result" => "success", "totalresults" => $totalresults, "startnumber" => $limitstart, "numreturned" => count($result), "projects" => array());
foreach ($result as $row) {
    $apiresults["projects"][] = (array) $row;
}
$responsetype = "xml";

?>