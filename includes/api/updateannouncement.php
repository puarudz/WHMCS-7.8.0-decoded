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
$title = WHMCS\Input\Sanitize::decode($title);
$announcement = WHMCS\Input\Sanitize::decode($announcement);
$update = array();
if (0 < strlen(trim($date))) {
    $update["date"] = $date;
}
if (0 < strlen(trim($title))) {
    $update["title"] = $title;
}
if (0 < strlen(trim($announcement))) {
    $update["announcement"] = $announcement;
}
if (0 < strlen(trim($published))) {
    $update["published"] = $published;
}
$where = array("id" => $announcementid);
update_query("tblannouncements", $update, $where);
run_hook("AnnouncementEdit", array("announcementid" => $announcementid, "date" => $date, "title" => $title, "announcement" => $announcement, "published" => $published));
$apiresults = array("result" => "success", "announcementid" => $announcementid);

?>