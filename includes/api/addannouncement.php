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
$title = WHMCS\Input\Sanitize::decode($title);
$announcement = WHMCS\Input\Sanitize::decode($announcement);
$isPublished = $published ? "1" : "0";
$id = insert_query("tblannouncements", array("date" => $date, "title" => $title, "announcement" => $announcement, "published" => $isPublished));
run_hook("AnnouncementAdd", array("announcementid" => $id, "date" => $date, "title" => $title, "announcement" => $announcement, "published" => $isPublished));
$apiresults = array("result" => "success", "announcementid" => $id);

?>