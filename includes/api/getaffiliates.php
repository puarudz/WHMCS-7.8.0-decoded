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
$where = array();
if ($userid) {
    $where["clientid"] = (int) $userid;
}
if ($visitors) {
    $where["visitors"] = (int) $visitors;
}
if ($paytype) {
    $where["paytype"] = array("sqltype" => "LIKE", "value" => $paytype);
}
if ($payamount) {
    $where["payamount"] = array("sqltype" => "LIKE", "value" => $payamount);
}
if ($onetime) {
    $where["onetime"] = (int) $onetime;
}
if ($balance) {
    $where["balance"] = array("sqltype" => "LIKE", "value" => $balance);
}
if ($withdrawn) {
    $where["withdrawn"] = array("sqltype" => "LIKE", "value" => $withdrawn);
}
if ($userid) {
    $result_user = select_query("tblaffiliates", "clientid", array("clientid" => $userid));
    $data_user = mysql_fetch_array($result_user);
    $userid = $data_user["clientid"];
    if (!$userid) {
        $apiresults = array("result" => "error", "message" => "Client ID not found");
        return NULL;
    }
}
$result = select_query("tblaffiliates", "COUNT(*)", $where);
$data = mysql_fetch_array($result);
$totalresults = $data[0];
$result2 = select_query("tblaffiliates", "", $where, "id", "ASC", (int) $limitstart . "," . (int) $limitnum);
$apiresults = array("result" => "success", "totalresults" => $totalresults, "startnumber" => $limitstart, "numreturned" => mysql_num_rows($result2), "affiliates" => array());
while ($data3 = mysql_fetch_assoc($result2)) {
    $apiresults["affiliates"]["affiliate"][] = $data3;
}
$responsetype = "xml";

?>