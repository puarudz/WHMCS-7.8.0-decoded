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
if (!$limitstart) {
    $limitstart = 0;
}
if (!$limitnum) {
    $limitnum = 25;
}
$result = select_query("`tblcancelrequests", "COUNT(*)", NULL);
$data = mysql_fetch_array($result);
$totalresults = $data[0];
$query = "SELECT * FROM tblcancelrequests LIMIT " . (int) $limitstart . "," . (int) $limitnum;
$result2 = full_query($query);
$apiresults = array("result" => "success", "totalresults" => $totalresults, "startnumber" => $limitstart, "numreturned" => mysql_num_rows($result2), "packages" => array());
while ($data = mysql_fetch_assoc($result2)) {
    $apiresults["packages"]["package"][] = $data;
}
$responsetype = "xml";

?>