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
if ($quoteid) {
    $where["id"] = $quoteid;
}
if ($userid) {
    $where["userid"] = $userid;
}
if ($subject) {
    $where["subject"] = $subject;
}
if ($stage) {
    $where["stage"] = $stage;
}
if ($datecreated) {
    $where["datecreated"] = $datecreated;
}
if ($lastmodified) {
    $where["lastmodified"] = $lastmodified;
}
if ($validuntil) {
    $where["validuntil"] = $validuntil;
}
$totalResults = get_query_val("tblquotes", "COUNT(*)", $where);
$quotes = array();
$result = select_query("tblquotes", "", $where, "id", "DESC", (int) $limitstart . "," . (int) $limitnum);
while ($data = mysql_fetch_assoc($result)) {
    $result2 = select_query("tblquoteitems", "id,description,quantity,unitprice,discount,taxable", array("quoteid" => $data["id"]));
    while ($itemdata = mysql_fetch_assoc($result2)) {
        $data["items"]["item"][] = $itemdata;
    }
    $quotes[] = $data;
}
$apiresults = array("result" => "success", "totalresults" => $totalResults, "startnumber" => $limitstart, "numreturned" => mysql_num_rows($result), "quotes" => array("quote" => $quotes));
$responsetype = "xml";

?>