<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("CLIENTAREA", true);
require "init.php";
$pagetitle = $_LANG["serverstatustitle"];
$breadcrumbnav = "<a href=\"index.php\">" . $_LANG["globalsystemname"] . "</a> > <a href=\"serverstatus.php\">" . $_LANG["serverstatustitle"] . "</a>";
$templatefile = "serverstatus";
$pageicon = "images/status_big.gif";
$displayTitle = Lang::trans("networkstatustitle");
$tagline = Lang::trans("networkstatussubtitle");
initialiseClientArea($pagetitle, $displayTitle, $tagline, $pageicon, $breadcrumbnav);
if ($CONFIG["NetworkIssuesRequireLogin"] && !isset($_SESSION["uid"])) {
    $goto = "serverstatus";
    require "login.php";
}
WHMCS\Session::release();
$servers = array();
$result = select_query("tblservers", "", "disabled=0 AND statusaddress!=''", "name", "ASC");
while ($data = mysql_fetch_array($result)) {
    $name = $data["name"];
    $ipaddress = $data["ipaddress"];
    $statusaddress = $data["statusaddress"];
    if (substr($statusaddress, -1, 1) != "/") {
        $statusaddress .= "/";
    }
    if (substr($statusaddress, -9, 9) != "index.php") {
        $statusaddress .= "index.php";
    }
    $servers[] = array("name" => $name, "ipaddress" => $ipaddress, "statusaddr" => $statusaddress, "phpinfourl" => $statusaddress . "?action=phpinfo", "serverload" => $serverload, "uptime" => $uptime, "phpver" => $phpver, "mysqlver" => $mysqlver, "zendver" => $zendver);
}
$smarty->assign("servers", $servers);
$smarty->register_function("get_port_status", "getPortStatus");
if ($whmcs->get_req_var("getstats")) {
    $num = $whmcs->get_req_var("num");
    $statusaddress = $servers[$num]["statusaddr"];
    if (strpos($statusaddress, "index.php") === false) {
        if (substr($statusaddress, -1, 1) != "/") {
            $statusaddress .= "/";
        }
        $statusaddress .= "index.php";
    }
    $filecontents = curlCall($statusaddress, "");
    preg_match("/\\<load\\>(.*?)\\<\\/load\\>/", $filecontents, $serverload);
    preg_match("/\\<uptime\\>(.*?)\\<\\/uptime\\>/", $filecontents, $uptime);
    preg_match("/\\<phpver\\>(.*?)\\<\\/phpver\\>/", $filecontents, $phpver);
    preg_match("/\\<mysqlver\\>(.*?)\\<\\/mysqlver\\>/", $filecontents, $mysqlver);
    preg_match("/\\<zendver\\>(.*?)\\<\\/zendver\\>/", $filecontents, $zendver);
    $serverload = $serverload[1];
    $uptime = $uptime[1];
    $phpver = $phpver[1];
    $mysqlver = $mysqlver[1];
    $zendver = $zendver[1];
    if (!$serverload) {
        $serverload = $_LANG["serverstatusnotavailable"];
    }
    if (!$uptime) {
        $uptime = $_LANG["serverstatusnotavailable"];
    }
    echo json_encode(array("load" => WHMCS\Input\Sanitize::encode($serverload), "uptime" => WHMCS\Input\Sanitize::encode($uptime), "phpver" => WHMCS\Input\Sanitize::encode($phpver), "mysqlver" => WHMCS\Input\Sanitize::encode($mysqlver), "zendver" => WHMCS\Input\Sanitize::encode($zendver)));
    exit;
}
if ($whmcs->get_req_var("ping")) {
    $num = (int) $whmcs->get_req_var("num");
    $port = (int) $whmcs->get_req_var("port");
    if (is_array($servers[$num])) {
        $res = @fsockopen($servers[$num]["ipaddress"], $port, $errno, $errstr, 5);
        echo "<img src=\"" . DI::make("asset")->getImgPath() . "/status" . ($res ? "ok" : "failed") . ".gif\" alt=\"" . $_LANG["serverstatus" . ($res ? "on" : "off") . "line"] . "\" width=\"16\" height=\"16\" />";
        if ($res) {
            fclose($res);
        }
    }
    exit;
}
include "networkissues.php";
Menu::addContext("networkIssueStatusCounts", $issueStatusCounts);
Menu::primarySidebar("networkIssueList");
Menu::secondarySidebar("networkIssueList");
outputClientArea($templatefile, false, array("ClientAreaPageServerStatus"));
function getPortStatus($params, &$smarty)
{
    global $servers;
    $num = $params["num"];
    $res = @fsockopen($servers[$num]["ipaddress"], $params["port"], $errno, $errstr, 5);
    $status = "<img src=\"" . DI::make("asset")->getImgPath() . "/status" . ($res ? "ok" : "failed") . ".gif\" alt=\"" . $_LANG["serverstatus" . ($res ? "on" : "off") . "line"] . "\" width=\"16\" height=\"16\" />";
    return $status;
}

?>