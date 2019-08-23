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
$result = select_query("tbltickets", "", array("id" => $ticketid));
$data = mysql_fetch_array($result);
$ticketid = $data["id"];
if (!$ticketid) {
    $apiresults = array("result" => "error", "message" => "Ticket ID not found");
} else {
    if (!function_exists("deleteTicket")) {
        require ROOTDIR . "/includes/ticketfunctions.php";
    }
    deleteTicket($ticketid);
    $apiresults = array("result" => "success");
}

?>