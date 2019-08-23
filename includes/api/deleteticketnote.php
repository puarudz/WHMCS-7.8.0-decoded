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
$result = select_query("tblticketnotes", "id", array("id" => $noteid));
$data = mysql_fetch_array($result);
if (!$data["id"]) {
    $apiresults = array("result" => "error", "message" => "Note ID Not Found");
} else {
    delete_query("tblticketnotes", array("id" => $noteid));
    $apiresults = array("result" => "success", "noteid" => $noteid);
}

?>