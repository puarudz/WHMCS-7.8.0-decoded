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
$userid = (int) App::getFromRequest("userid");
$notes = (string) App::getFromRequest("notes");
$sticky = (int) (bool) App::getFromRequest("sticky");
$userid = get_query_val("tblclients", "id", array("id" => $userid));
if (!$userid) {
    $apiresults = array("result" => "error", "message" => "Client ID not found");
} else {
    if (!$notes) {
        $apiresults = array("result" => "error", "message" => "Notes can not be empty");
    } else {
        $sticky = $sticky ? 1 : 0;
        $noteid = insert_query("tblnotes", array("userid" => $userid, "adminid" => $_SESSION["adminid"], "created" => "now()", "modified" => "now()", "note" => $notes, "sticky" => $sticky));
        $apiresults = array("result" => "success", "noteid" => $noteid);
    }
}

?>