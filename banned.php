<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("CLIENTAREA", true);
require "init.php";
$pagetitle = $_LANG["bannedtitle"];
$breadcrumbnav = "<a href=\"index.php\">" . $_LANG["globalsystemname"] . "</a> > <a href=\"banned.php\">" . $_LANG["bannedtitle"] . "</a>";
$pageicon = "";
$displayTitle = Lang::trans("accessdenied");
$tagline = "";
initialiseClientArea($pagetitle, $displayTitle, $tagline, $pageicon, $breadcrumbnav);
$remote_ip = WHMCS\Utility\Environment\CurrentUser::getIP();
$ip = explode(".", $remote_ip);
$ip = db_escape_numarray($ip);
$remote_ip1 = $ip[0] . "." . $ip[1] . "." . $ip[2] . ".*";
$remote_ip2 = $ip[0] . "." . $ip[1] . ".*.*";
$data = get_query_vals("tblbannedips", "", "ip='" . db_escape_string($remote_ip) . "' OR ip='" . db_escape_string($remote_ip1) . "' OR ip='" . db_escape_string($remote_ip2) . "'", "id", "DESC");
$id = $data["id"];
$reason = $data["reason"];
$expires = fromMySQLDate($data["expires"], true, true);
if (!$id) {
    redir("", "index.php");
}
$smartyvalues["ip"] = htmlspecialchars($remote_ip);
$smartyvalues["reason"] = $reason;
$smartyvalues["expires"] = $expires;
$templatefile = "banned";
outputClientArea($templatefile, false, array("ClientAreaPageBanned"));

?>