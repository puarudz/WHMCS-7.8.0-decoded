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
if (!function_exists("closeClient")) {
    require ROOTDIR . "/includes/clientfunctions.php";
}
$result = select_query("tblclients", "id", array("id" => $clientid));
$data = mysql_fetch_array($result);
if (!$data["id"]) {
    $apiresults = array("result" => "error", "message" => "Client ID Not Found");
} else {
    closeClient($_REQUEST["clientid"]);
    $apiresults = array("result" => "success", "clientid" => $_REQUEST["clientid"]);
}

?>