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
$result = select_query("tblcurrencies", "", "", "id", "ASC");
$apiresults = array("result" => "success", "totalresults" => mysql_num_rows($result));
while ($data = mysql_fetch_array($result)) {
    $id = $data["id"];
    $code = $data["code"];
    $prefix = $data["prefix"];
    $suffix = $data["suffix"];
    $format = $data["format"];
    $rate = $data["rate"];
    $apiresults["currencies"]["currency"][] = array("id" => $id, "code" => $code, "prefix" => $prefix, "suffix" => $suffix, "format" => $format, "rate" => $rate);
}
$responsetype = "xml";

?>