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
if ($code) {
    $where["code"] = (string) $code;
} else {
    if ($id) {
        $where["id"] = (int) $id;
    }
}
$result = select_query("tblpromotions", "", $where, "code", "ASC");
$apiresults = array("result" => "success", "totalresults" => mysql_num_rows($result));
while ($data = mysql_fetch_assoc($result)) {
    $apiresults["promotions"]["promotion"][] = $data;
}
$responsetype = "xml";

?>