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
$where = array();
if (App::isInRequest("catid")) {
    $where["catid"] = (int) App::getFromRequest("catid");
}
$result = select_query("tblticketpredefinedreplies", "COUNT(id)", $where);
$data = mysql_fetch_array($result);
$totalresults = $data[0];
$apiresults = array("result" => "success", "totalresults" => $totalresults);
$result = select_query("tblticketpredefinedreplies", "name,reply", $where, "name", "ASC");
while ($data = mysql_fetch_assoc($result)) {
    $apiresults["predefinedreplies"]["predefinedreply"][] = array("name" => $data["name"], "reply" => $data["reply"]);
}
$responsetype = "xml";

?>