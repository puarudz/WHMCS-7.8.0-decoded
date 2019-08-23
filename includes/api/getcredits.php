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
$result = select_query("tblclients", "id", array("id" => $clientid));
$data = mysql_fetch_array($result);
$clientid = $data["id"];
if (!$clientid) {
    $apiresults = array("result" => "error", "message" => "Client ID Not Found");
} else {
    $credits = array();
    $result = select_query("tblcredit", "id,date,description,amount,relid", array("clientid" => $clientid), "date", "ASC");
    while ($data = mysql_fetch_assoc($result)) {
        $credits[] = $data;
    }
    $apiresults = array("result" => "success", "totalresults" => count($credits), "clientid" => $clientid, "credits" => array("credit" => $credits));
    $responsetype = "xml";
}

?>