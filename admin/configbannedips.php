<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("View Banned IPs");
$aInt->title = $aInt->lang("bans", "iptitle");
$aInt->sidebar = "config";
$aInt->icon = "configbans";
$aInt->helplink = "Security/Ban Control";
$aInt->requireAuthConfirmation();
if ($whmcs->get_req_var("ip")) {
    check_token("WHMCS.admin.default");
    if (defined("DEMO_MODE")) {
        redir("demo=1");
    }
    checkPermission("Add Banned IP");
    $expires = $year . $month . $day . $hour . $minutes . "00";
    insert_query("tblbannedips", array("ip" => $ip, "reason" => $reason, "expires" => $expires));
    logAdminActivity("IP Ban Added: " . $ip . " (Expires: " . $year . "-" . $month . "-" . $day . " " . $hour . ":" . $minutes . ")");
    redir("success=true");
}
if ($whmcs->get_req_var("delete")) {
    check_token("WHMCS.admin.default");
    if (defined("DEMO_MODE")) {
        redir("demo=1");
    }
    checkPermission("Unban Banned IP");
    $id = (int) $whmcs->get_req_var("id");
    $record = Illuminate\Database\Capsule\Manager::table("tblbannedips")->find($id, array("ip"));
    delete_query("tblbannedips", array("id" => $id));
    logAdminActivity("IP Ban Removed: " . $record->ip);
    redir("deleted=true");
}
ob_start();
$infobox = "";
if (defined("DEMO_MODE")) {
    infoBox("Demo Mode", "Actions on this page are unavailable while in demo mode. Changes will not be saved.");
}
if ($whmcs->get_req_var("success")) {
    infoBox($aInt->lang("bans", "ipaddsuccess"), $aInt->lang("bans", "ipaddsuccessinfo"));
}
if ($whmcs->get_req_var("deleted")) {
    infoBox($aInt->lang("bans", "ipdelsuccess"), $aInt->lang("bans", "ipdelsuccessinfo"));
}
echo $infobox;
$aInt->deleteJSConfirm("doDelete", "bans", "ipdelsure", $_SERVER["PHP_SELF"] . "?delete=true&id=");
echo $aInt->beginAdminTabs(array($aInt->lang("global", "add"), $aInt->lang("global", "searchfilter")), true);
echo "\n<form method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
$new_ban_time = mktime(date("H"), date("i"), date("s"), date("m"), date("d") + 7, date("Y"));
echo "\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "ipaddress");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"ip\" size=\"20\"></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("bans", "banreason");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"reason\" size=\"90\"></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("bans", "banexpires");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"day\" size=\"3\" maxlength=\"2\" value=\"";
echo date("d", $new_ban_time);
echo "\">/<input type=\"text\" name=\"month\" size=\"3\" maxlength=\"2\" value=\"";
echo date("m", $new_ban_time);
echo "\">/<input type=\"text\" name=\"year\" size=\"6\" maxlength=\"4\" value=\"";
echo date("Y", $new_ban_time);
echo "\"> <input type=\"text\" name=\"hour\" size=\"3\" maxlength=\"2\" value=\"";
echo date("H", $new_ban_time);
echo "\">:<input type=\"text\" name=\"minutes\" size=\"3\" maxlength=\"2\" value=\"";
echo date("i", $new_ban_time);
echo "\"> (";
echo $aInt->lang("bans", "format");
echo ")</td></tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
echo $aInt->lang("bans", "addbannedip");
echo "\" name=\"postreply\" class=\"btn btn-primary\">\n</div>\n\n</form>\n\n";
echo $aInt->nextAdminTab();
echo "\n<div class=\"text-center\">\n    <form method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
echo "\" class=\"form-inline\">\n        Filter for\n        <select name=\"filterfor\" class=\"form-control select-inline\">\n            <option";
if ($filterfor == "IP Address") {
    echo " selected";
}
echo ">";
echo $aInt->lang("fields", "ipaddress");
echo "</option>\n            <option";
if ($filterfor == "Ban Reason") {
    echo " selected";
}
echo ">";
echo $aInt->lang("bans", "banreason");
echo "</option>\n        </select>\n        matching\n        <input type=\"text\" name=\"filtertext\" value=\"";
echo $filtertext;
echo "\" class=\"form-control\" />\n        <input type=\"submit\" value=\"";
echo $aInt->lang("global", "search");
echo "\" name=\"postreply\" class=\"btn btn-default\" />\n    </div>\n</form>\n\n";
echo $aInt->endAdminTabs();
echo "\n<br>\n\n";
$aInt->sortableTableInit("nopagination");
$where = array();
if ($filterfor = $whmcs->get_req_var("filterfor")) {
    $filtertext = $whmcs->get_req_var("filtertext");
    if ($filterfor == "IP Address") {
        $where = array("ip" => $filtertext);
    } else {
        $where = array("reason" => array("sqltype" => "LIKE", "value" => $filtertext));
    }
}
$result = select_query("tblbannedips", "", $where, "id", "DESC");
while ($data = mysql_fetch_array($result)) {
    $id = $data["id"];
    $ip = $data["ip"];
    $reason = $data["reason"];
    $expires = $data["expires"];
    $expires = fromMySQLDate($expires, "time");
    $tabledata[] = array(WHMCS\Utility\GeoIp::getLookupHtmlAnchor($ip), $reason, $expires, "<a href=\"#\" onClick=\"doDelete('" . $id . "');return false\">" . "<img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\"></a>");
}
echo $aInt->sortableTable(array($aInt->lang("fields", "ipaddress"), $aInt->lang("bans", "banreason"), $aInt->lang("bans", "banexpires"), ""), $tabledata);
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->display();

?>