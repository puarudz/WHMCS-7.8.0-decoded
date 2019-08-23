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
if (!function_exists("getAdminName")) {
    require ROOTDIR . "/includes/adminfunctions.php";
}
if (!function_exists("affiliateActivate")) {
    require ROOTDIR . "/includes/affiliatefunctions.php";
}
$result = select_query("tblclients", "id", array("id" => $userid));
$data = mysql_fetch_array($result);
$userid = $data["id"];
if (!$userid) {
    $apiresults = array("result" => "error", "message" => "Client ID not found");
} else {
    affiliateActivate($userid);
    $apiresults = array("result" => "success");
}

?>