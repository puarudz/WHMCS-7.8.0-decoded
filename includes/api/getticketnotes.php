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
$notes = array();
$result = select_query("tblticketnotes", "id,admin,date,message,attachments,attachments_removed", array("ticketid" => $ticketid), "date", "ASC");
while ($data = mysql_fetch_assoc($result)) {
    $data["attachments_removed"] = (bool) (int) $data["attachments_removed"];
    $notes[] = $data;
}
$apiresults = array("result" => "success", "totalresults" => count($notes), "notes" => array("note" => $notes));
$responsetype = "xml";

?>