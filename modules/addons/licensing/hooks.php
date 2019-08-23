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
add_hook("DailyCronJob", 0, "hook_licensing_addon_log_prune");
add_hook("AdminIntelliSearch", 0, "hook_licensing_addon_search");
function hook_licensing_addon_log_prune($vars)
{
    $logprune = get_query_val("tbladdonmodules", "value", array("module" => "licensing", "setting" => "logprune"));
    if (is_numeric($logprune)) {
        full_query("DELETE FROM mod_licensinglog WHERE datetime<='" . date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - $logprune, date("Y"))) . "'");
    }
    full_query("DELETE FROM mod_licensing WHERE `serviceid` NOT IN (SELECT id FROM tblhosting)");
    full_query("DELETE FROM mod_licensing WHERE `addon_id` != 0 AND `addon_id` NOT IN (SELECT `id` FROM tblhostingaddons) AND `addon_id` != 0");
    full_query("OPTIMIZE TABLE mod_licensinglog");
}
function hook_licensing_addon_search($vars)
{
    $keyword = $vars["keyword"];
    $matches = array();
    $result = select_query("mod_licensing", "", "licensekey LIKE '%" . db_escape_string($keyword) . "%' OR validdomain LIKE '%" . db_escape_string($keyword) . "%'");
    while ($data = mysql_fetch_array($result)) {
        $serviceid = $data["serviceid"];
        $addonId = $data["addon_id"];
        $licensekey = $data["licensekey"];
        $validdomain = $data["validdomain"];
        $status = $data["status"];
        $validdomain = explode(",", $validdomain);
        $validdomain = $validdomain[0];
        if (!$validdomain) {
            $validdomain = "Not Yet Accessed";
        }
        $uri = "clientsservices.php?id=" . $serviceid;
        if ($addonId) {
            $uri .= "&aid" . $addonId;
        }
        $matches[] = array("link" => $uri, "status" => $status, "title" => $licensekey, "desc" => $validdomain);
    }
    return array("Licenses" => $matches);
}

?>