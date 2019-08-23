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
$fetchStatus = App::getFromRequest("fetchStatus");
$servers = array();
$result = select_query("tblservers", "", array("disabled" => "0"), "name", "ASC");
while ($data = mysql_fetch_array($result)) {
    $id = $data["id"];
    $name = $data["name"];
    $hostname = $data["hostname"];
    $ipaddress = $data["ipaddress"];
    $maxaccounts = $data["maxaccounts"];
    $statusaddress = $data["statusaddress"];
    $active = $data["active"];
    $numaccounts = get_query_val("tblhosting", "COUNT(id)", "server='" . (int) $id . "' AND (domainstatus='Active' OR domainstatus='Suspended')");
    $percentuse = @round($numaccounts / $maxaccounts * 100, 0);
    $http = $serverload = $uptime = "";
    if ($fetchStatus) {
        $http = @fsockopen($ipaddress, 80, $errno, $errstr, 5);
        if ($statusaddress) {
            if (strpos($statusaddress, "index.php") === false) {
                if (substr($statusaddress, -1, 1) != "/") {
                    $statusaddress .= "/";
                }
                $statusaddress .= "index.php";
            }
            $filecontents = curlCall($statusaddress, false, array(CURLOPT_TIMEOUT => 5));
            $serverload = WHMCS\Input\Sanitize::encode(preg_match("/<load>(.*?)<\\/load>/i", $filecontents, $matches) ? $matches[1] : "-");
            $uptime = WHMCS\Input\Sanitize::encode(preg_match("/<uptime>(.*?)<\\/uptime>/i", $filecontents, $matches) ? $matches[1] : "-");
        }
    }
    $servers[] = array("id" => $id, "name" => $name, "hostname" => $hostname, "ipaddress" => $ipaddress, "active" => (bool) $active, "activeServices" => $numaccounts, "maxAllowedServices" => $maxaccounts, "percentUsed" => $percentuse, "status" => array("http" => (bool) $http, "load" => $serverload, "uptime" => $uptime));
}
$apiresults = array("result" => "success", "servers" => $servers, "fetchStatus" => $fetchStatus);

?>