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
$currusername = get_query_val("tbladmins", "username", array("id" => $_SESSION["adminid"]));
$result = full_query("SELECT DISTINCT adminusername FROM tbladminlog WHERE lastvisit>='" . date("Y-m-d H:i:s", mktime(date("H"), date("i") - 15, date("s"), date("m"), date("d"), date("Y"))) . "' AND adminusername!='" . db_escape_string($currusername) . "' AND logouttime='0000-00-00' ORDER BY lastvisit ASC");
$apiresults = array("result" => "success", "totalresults" => mysql_num_rows($result) + 1);
$apiresults["staffonline"]["staff"][] = array("adminusername" => $currusername, "logintime" => date("Y-m-d H:i:s"), "ipaddress" => $remote_ip, "lastvisit" => date("Y-m-d H:i:s"));
while ($data = mysql_fetch_assoc($result)) {
    $username = $data["adminusername"];
    $result2 = select_query("tbladminlog", "adminusername,logintime,ipaddress,lastvisit", "lastvisit>='" . date("Y-m-d H:i:s", mktime(date("H"), date("i") - 15, date("s"), date("m"), date("d"), date("Y"))) . "' AND adminusername='" . db_escape_string($username) . "'", "lastvisit", "ASC", "0,1");
    $apiresults["staffonline"]["staff"][] = mysql_fetch_assoc($result2);
}
$responsetype = "xml";

?>