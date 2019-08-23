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
$result = select_query("tblannouncements", "id", array("id" => $announcementid));
$data = mysql_fetch_array($result);
if (!$data["id"]) {
    $apiresults = array("result" => "error", "message" => "Announcement ID Not Found");
    return false;
}
delete_query("tblannouncements", array("id" => $announcementid));
delete_query("tblannouncements", array("parentid" => $announcementid));
$apiresults = array("result" => "success", "announcementid" => $announcementid);

?>