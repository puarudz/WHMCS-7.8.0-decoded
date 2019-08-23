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
if ($_POST["userid"]) {
    $result = select_query("tblclients", "", array("id" => $_POST["userid"]));
} else {
    $result = select_query("tblclients", "", array("email" => $_POST["email"]));
}
$data = mysql_fetch_array($result);
if ($data["id"]) {
    $password = $data["password"];
    $apiresults = array("result" => "success", "password" => $password);
} else {
    $apiresults = array("result" => "error", "message" => "Client ID Not Found");
}

?>