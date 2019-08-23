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
if (!$days) {
    $days = 7;
}
if (!$expires) {
    $expires = date("YmdHis", mktime(date("H"), date("i"), date("s"), date("m"), date("d") + $days, date("Y")));
}
$banid = insert_query("tblbannedips", array("ip" => $ip, "reason" => $reason, "expires" => $expires));
$apiresults = array("result" => "success", "banid" => $banid);

?>