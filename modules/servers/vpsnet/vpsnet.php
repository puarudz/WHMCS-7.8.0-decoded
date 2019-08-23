<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function vpsnet_MetaData()
{
    return array("DisplayName" => "VPS.net", "APIVersion" => "1.0");
}
function vpsnet_ConfigOptions($params)
{
    if (!mysql_num_rows(full_query("SHOW TABLES LIKE 'mod_vpsnet'"))) {
        $query = "CREATE TABLE `mod_vpsnet` (`relid` INTEGER UNSIGNED NOT NULL,`addon_id` INTEGER UNSIGNED NOT NULL DEFAULT '0', `setting` VARCHAR(45) NOT NULL,`value` VARCHAR(45) NOT NULL,PRIMARY KEY (`relid`,`setting`));";
        full_query($query);
    }
    $query = "set @query = if ((select count(*) from information_schema.columns where table_schema=database() and table_name='mod_vpsnet' and column_name='addon_id') = 0, 'ALTER TABLE `mod_vpsnet` ADD COLUMN `addon_id` INT(10) unsigned NOT NULL DEFAULT \\'0\\' AFTER 'relid';', 'DO 0;');\nprepare statement from @query;\nexecute statement;\ndeallocate prepare statement;";
    full_query($query);
    $creds = vpsnet_GetCredentials();
    if (!$creds["id"]) {
        return array("Error" => array("Type" => "x", "Description" => "No VPS.Net Server Config found in Setup > Servers"));
    }
    $verifyauth = vpsnet_call($params, "", "", "GET", "profile");
    if ($verifyauth["success"] != "1") {
        foreach ($verifyauth["errors"] as $errormsg) {
            $verifyautherror .= $errormsg;
        }
        if ($verifyautherror) {
            return array("Error" => array("Type" => "x", "Description" => $verifyautherror));
        }
        return array("Error" => array("Type" => "FailedAuth", "Description" => "Unable to authenticate with Username and Access Hash. Please check Server Config found in Setup > Servers"));
    } else {
        $resources = vpsnet_call($params, $action, $id, $reqtype = "", $type = "available_clouds");
        $cloudtemplate = ",";
        foreach ($resources["response"] as $resource) {
            $cloudid = $resource["cloud"]["id"];
            $cloudlabel = $resource["cloud"]["label"];
            foreach ($resource["cloud"]["system_templates"] as $system_template) {
                $templateid = $system_template["id"];
                $templatelabel = $system_template["label"];
                $cloudtemplate .= (string) $cloudid . "+" . $templateid . "|" . $cloudlabel . ":" . $templatelabel . ",";
            }
        }
        $cloudtemplate = substr($cloudtemplate, 0, -1);
        $configarray = array("Number of Nodes" => array("Type" => "text", "Size" => "5"), "Cloud/Template" => array("Type" => "dropdown", "Options" => $cloudtemplate), "Enable Backups" => array("Type" => "yesno", "Description" => "Tick to enable backups"), "Rsync Backups" => array("Type" => "yesno", "Description" => "Tick to enable"), "R1Soft Backups" => array("Type" => "yesno", "Description" => "Tick to enable"), "" => array("Type" => "x", "Description" => ""));
        return $configarray;
    }
}
function vpsnet_CreateAccount($params)
{
    $creds = vpsnet_GetCredentials();
    $initialNodes = intval($params["configoption1"]);
    $cloudtemplate = explode("|", $params["configoption2"]);
    $cloudtemplate = $cloudtemplate[0];
    $enablebackups = $params["configoption3"];
    $controlpanel = $controlpanel[0];
    if ($params["configoptions"]["Nodes"]) {
        $initialNodes = intval($params["configoptions"]["Nodes"]);
    }
    if ($params["configoptions"]["Cloud Template"]) {
        $cloudtemplate = $params["configoptions"]["Cloud Template"];
    }
    if ($params["configoptions"]["Enable Backups"]) {
        $enablebackups = $params["configoptions"]["Enable Backups"];
    }
    if ($params["configoptions"]["Rsync Backups"]) {
        $rsyncbackups = $params["configoptions"]["Rsync Backups"];
    }
    if ($params["configoptions"]["R1Soft Backups"]) {
        $r1softbackups = $params["configoptions"]["R1Soft Backups"];
    }
    $cloudtemplate = explode("+", $cloudtemplate);
    list($cloud, $template) = $cloudtemplate;
    $enablebackups = $enablebackups == "on" || $enablebackups == "Yes" ? "true" : "false";
    $rsyncbackups = $rsyncbackups == "on" || $rsyncbackups == "Yes" ? "true" : "false";
    $r1softbackups = $r1softbackups == "on" || $r1softbackups == "Yes" ? "true" : "false";
    $label = $params["customfields"]["VPS Label"] ? $params["customfields"]["VPS Label"] : $params["clientsdetails"]["lastname"] . $params["serviceid"] . "_VPS";
    $postfields = array();
    $postfields["label"] = $label;
    $postfields["fqdn"] = $params["domain"];
    $postfields["system_template_id"] = $template;
    $postfields["cloud_id"] = $cloud;
    $postfields["backups_enabled"] = $enablebackups;
    $postfields["rsync_backups_enabled"] = $rsyncbackups;
    $postfields["r1_soft_backups_enabled"] = $r1softbackups;
    $postfields["slices_required"] = $initialNodes;
    $result = vpsnet_call($params, "", "", "", "virtual_machines", array("virtual_machine" => $postfields));
    if ($result["success"] == "1") {
        $netid = $result["response"]["virtual_machine"]["id"];
        $password = $result["response"]["virtual_machine"]["password"];
        $ip = $result["response"]["virtual_machine"]["primary_ip_address"]["ip_address"]["ip_address"];
        $params["model"]->serviceProperties->save(array("dedicatedip" => $ip, "password" => $password));
        insert_query("mod_vpsnet", array("relid" => $params["serviceid"], "addon_id" => (int) $params["addonId"], "setting" => "netid", "value" => $netid));
        insert_query("mod_vpsnet", array("relid" => $params["serviceid"], "addon_id" => (int) $params["addonId"], "setting" => "slices", "value" => $initialNodes));
        return "success";
    }
    if (is_array($result)) {
        $errors = $result["errors"]["errors"];
        $errlist = " - ";
        foreach ($errors as $error) {
            $errlist .= $error[0] . " " . $error[1] . "<br />";
        }
    }
    return "Failed to create VM" . $errlist;
}
function vpsnet_SuspendAccount($params)
{
    $netid = get_query_val("mod_vpsnet", "value", array("relid" => $params["serviceid"], "addon_id" => (int) $params["addonId"], "setting" => "netid"));
    if (!$netid) {
        return "No VM Found";
    }
    $rtn = vpsnet_call($params, "power_off", $netid);
    if ($rtn["success"]) {
        return "success";
    }
    return $rtn["errors"];
}
function vpsnet_UnsuspendAccount($params)
{
    $netid = get_query_val("mod_vpsnet", "value", array("relid" => $params["serviceid"], "addon_id" => (int) $params["addonId"], "setting" => "netid"));
    if (!$netid) {
        return "No VM Found";
    }
    $rtn = vpsnet_call($params, "power_on", $netid);
    if ($rtn["success"]) {
        return "success";
    }
    return $rtn["errors"];
}
function vpsnet_TerminateAccount($params)
{
    $netid = get_query_val("mod_vpsnet", "value", array("relid" => $params["serviceid"], "addon_id" => (int) $params["addonId"], "setting" => "netid"));
    if (!$netid) {
        return "No VM Found";
    }
    $rtn = vpsnet_call($params, "", $netid, "DELETE");
    if ($rtn["success"]) {
        delete_query("mod_vpsnet", array("relid" => $params["serviceid"], "addon_id" => (int) $params["addonId"]));
    } else {
        return $rtn["errors"];
    }
}
function vpsnet_ChangePackage($params)
{
    $creds = vpsnet_GetCredentials();
    $result = select_query("mod_vpsnet", "", array("relid" => $params["serviceid"], "addon_id" => (int) $params["addonId"]));
    while ($data = mysql_fetch_assoc($result)) {
        ${$data["setting"]} = $data["value"];
    }
    $netid = get_query_val("mod_vpsnet", "value", array("relid" => $params["serviceid"], "addon_id" => (int) $params["addonId"], "setting" => "netid"));
    if (!$netid) {
        return "No VM Found";
    }
    $result = vpsnet_call($params, "power_on", $netid);
    if (!$result["success"]) {
    }
    $initialNodes = intval($params["configoption1"]);
    $cloudtemplate = explode("|", $params["configoption2"]);
    $cloudtemplate = $cloudtemplate[0];
    $enablebackups = $params["configoption3"];
    $controlpanel = $controlpanel[0];
    if ($params["configoptions"]["Nodes"]) {
        $initialNodes = intval($params["configoptions"]["Nodes"]);
    }
    if ($params["configoptions"]["Cloud Template"]) {
        $cloudtemplate = $params["configoptions"]["Cloud Template"];
    }
    if ($params["configoptions"]["Enable Backups"]) {
        $enablebackups = $params["configoptions"]["Enable Backups"];
    }
    if ($params["configoptions"]["Rsync Backups"]) {
        $rsyncbackups = $params["configoptions"]["Rsync Backups"];
    }
    if ($params["configoptions"]["R1Soft Backups"]) {
        $r1softbackups = $params["configoptions"]["R1Soft Backups"];
    }
    $cloudtemplate = explode("+", $cloudtemplate);
    list($cloud, $template) = $cloudtemplate;
    $enablebackups = $enablebackups == "on" || $enablebackups == "Yes" ? "true" : "false";
    $rsyncbackups = $rsyncbackups == "on" || $rsyncbackups == "Yes" ? "true" : "false";
    $r1softbackups = $r1softbackups == "on" || $r1softbackups == "Yes" ? "true" : "false";
    $postfields = array();
    $postfields["id"] = $netid;
    $postfields["system_template_id"] = $template;
    $postfields["cloud_id"] = $cloud;
    $postfields["backups_enabled"] = $enablebackups;
    $postfields["rsync_backups_enabled"] = $rsyncbackups;
    $postfields["r1_soft_backups_enabled"] = $r1softbackups;
    $postfields["slices_required"] = $initialNodes;
    $result = vpsnet_call($params, "", $netid, "PUT", "virtual_machines", array("virtual_machine" => $postfields));
    if ($result["success"]) {
        update_query("mod_vpsnet", array("value" => $netid), array("relid" => $params["serviceid"], "addon_id" => (int) $params["addonId"], "setting" => "netid"));
        update_query("mod_vpsnet", array("value" => $initialNodes), array("relid" => $params["serviceid"], "addon_id" => (int) $params["addonId"], "setting" => "slices"));
        return "success";
    }
    if (is_array($result)) {
        $errors = $result["errors"]["errors"];
        $errlist = " - ";
        foreach ($errors as $error) {
            $errlist .= $error[0] . " " . $error[1] . "<br />";
        }
    }
    return "Failed to update VM" . $errlist;
}
function vpsnet_AdminCustomButtonArray()
{
    $buttonarray = array("Manage Backups" => "managebackups");
    return $buttonarray;
}
function vpsnet_poweron($params)
{
    $netid = get_query_val("mod_vpsnet", "value", array("relid" => $params["serviceid"], "addon_id" => (int) $params["addonId"], "setting" => "netid"));
    if (!$netid) {
        return "No VM Found";
    }
    $rtn = vpsnet_call($params, "power_on", $netid);
    if ($rtn["success"]) {
        return "VPS Queued for Power On";
    }
    return $rtn["errors"];
}
function vpsnet_poweroff($params)
{
    $netid = get_query_val("mod_vpsnet", "value", array("relid" => $params["serviceid"], "addon_id" => (int) $params["addonId"], "setting" => "netid"));
    if (!$netid) {
        return "No VM Found";
    }
    $rtn = vpsnet_call($params, "power_off", $netid);
    if ($rtn["success"]) {
        return "VPS Queued for Power Off";
    }
    return $rtn["errors"];
}
function vpsnet_reboot($params)
{
    $netid = get_query_val("mod_vpsnet", "value", array("relid" => $params["serviceid"], "addon_id" => (int) $params["addonId"], "setting" => "netid"));
    if (!$netid) {
        return "No VM Found";
    }
    $rtn = vpsnet_call($params, "reboot", $netid);
    if ($rtn["success"]) {
        return "VPS Queued for Reboot";
    }
    return $rtn["errors"];
}
function vpsnet_shutdown($params)
{
    $netid = get_query_val("mod_vpsnet", "value", array("relid" => $params["serviceid"], "addon_id" => (int) $params["addonId"], "setting" => "netid"));
    if (!$netid) {
        return "No VM Found";
    }
    $rtn = vpsnet_call($params, "shutdown", $netid);
    if ($rtn["success"]) {
        return "success";
    }
    return $rtn["errors"];
}
function vpsnet_rebuild($params)
{
    $netid = get_query_val("mod_vpsnet", "value", array("relid" => $params["serviceid"], "addon_id" => (int) $params["addonId"], "setting" => "netid"));
    if (!$netid) {
        return "No VM Found";
    }
    $rtn = vpsnet_call($params, "rebuild_network", $netid);
    if ($rtn["success"]) {
        return "VPS Queued for Rebuild";
    }
    return $rtn["errors"];
}
function vpsnet_recover($params)
{
    $netid = get_query_val("mod_vpsnet", "value", array("relid" => $params["serviceid"], "addon_id" => (int) $params["addonId"], "setting" => "netid"));
    if (!$netid) {
        return "No VM Found";
    }
    $rtn = vpsnet_call($params, "power_on", $netid, "", "", array("mode" => "recovery"));
    if ($rtn["success"]) {
        return "VPS Queued for Power On and Recovery";
    }
    return $rtn["errors"];
}
function vpsnet_reinstall($params)
{
    $netid = get_query_val("mod_vpsnet", "value", array("relid" => $params["serviceid"], "addon_id" => (int) $params["addonId"], "setting" => "netid"));
    if (!$netid) {
        return "No VM Found";
    }
    $rtn = vpsnet_call($params, "reinstall", $netid);
    if ($rtn["success"]) {
        return "VPS Queued for Power On and Recovery";
    }
    return $rtn["errors"];
}
function vpsnet_snapshotbackup($params)
{
    $netid = get_query_val("mod_vpsnet", "value", array("relid" => $params["serviceid"], "addon_id" => (int) $params["addonId"], "setting" => "netid"));
    if (!$netid) {
        return "No VM Found";
    }
    $rtn = vpsnet_call($params, "backups", $netid);
    if ($rtn["success"]) {
        if (defined("CLIENTAREA")) {
        } else {
            redir("userid=" . $_REQUEST["userid"] . "&id=" . $_REQUEST["id"] . "&addonid=" . (int) $params["addonId"] . "&managebackups=1", "clientshosting.php");
        }
    } else {
        return $rtn["errors"];
    }
}
function vpsnet_restorebackup($params)
{
    $netid = get_query_val("mod_vpsnet", "value", array("relid" => $params["serviceid"], "addon_id" => (int) $params["addonId"], "setting" => "netid"));
    if (!$netid) {
        return "No VM Found";
    }
    $rtn = vpsnet_call($params, "backups/" . (int) $_REQUEST["bid"] . "/restore", $netid);
    if ($rtn["success"]) {
        return "success";
    }
    return "VPS Must be Powered Off First to Restore a Backup";
}
function vpsnet_deletebackup($params)
{
    $netid = get_query_val("mod_vpsnet", "value", array("relid" => $params["serviceid"], "addon_id" => (int) $params["addonId"], "setting" => "netid"));
    if (!$netid) {
        return "No VM Found";
    }
    $rtn = vpsnet_call($params, "backups/" . (int) $_REQUEST["bid"], $netid, "DELETE");
    if ($rtn["success"]) {
        if (defined("CLIENTAREA")) {
        } else {
            redir("userid=" . $_REQUEST["userid"] . "&id=" . $_REQUEST["id"] . "&addonid=" . (int) $params["addonId"] . "&managebackups=1", "clientshosting.php");
        }
    } else {
        return $rtn["errors"];
    }
}
function vpsnet_managebackups($params)
{
    return "redirect|clientshosting.php?userid=" . $_REQUEST["userid"] . "&id=" . $_REQUEST["id"] . "&addonid=" . (int) $params["addonId"] . "&managebackups=1";
}
function vpsnet_AdminServicesTabFields($params)
{
    global $_LANG;
    $netid = get_query_val("mod_vpsnet", "value", array("relid" => $params["serviceid"], "addon_id" => (int) $params["addonId"], "setting" => "netid"));
    if (!$netid) {
        return false;
    }
    $vpsinfo = "<style>\n#vpsnetcont {\n    margin: 10px;\n    padding: 10px;\n    background-color: #fff;\n    -moz-border-radius: 10px;\n    -webkit-border-radius: 10px;\n    -o-border-radius: 10px;\n    border-radius: 10px;\n}\n#vpsnetcont table {\n    width: 100%;\n}\n#vpsnetcont table tr th {\n    padding: 4px;\n    background-color: #1A4D80;\n    color: #fff;\n    font-weight: bold;\n    text-align: center;\n    -moz-border-radius: 3px;\n    -webkit-border-radius: 3px;\n    -o-border-radius: 3px;\n    border-radius: 3px;\n}\n#vpsnetcont table tr td {\n    padding: 4px;\n    border-bottom: 1px solid #efefef;\n}\n#vpsnetcont table tr td.fieldlabel {\n    width: 175px;\n    text-align: right;\n    font-weight: bold;\n    background-color: #efefef;\n}\n#vpsnetcont .tools {\n    padding: 10px 0 0 15px;\n}\n</style>\n";
    if ($_REQUEST["bwgraphs"]) {
        $rtn = vpsnet_call($params, "network_graph", $netid, "GET", "virtual_machines", "period=hourly");
        $data = $rtn["response"];
        $datatable = array();
        $datatable[] = "[\"Time\",\"Upload\",\"Download\"]";
        foreach ($data as $d) {
            $datatable[] = "[\"" . date("Y-m-d H:i", strtotime($d["created_at"])) . "\"," . round($d["data_received"] / (1024 * 1024), 2) . "," . round($d["data_sent"] / (1024 * 1024), 2) . "]";
        }
        $vpsinfo .= "<script type=\"text/javascript\" src=\"https://www.google.com/jsapi\"></script>\n    <script type=\"text/javascript\">\n      google.load(\"visualization\", \"1\", {packages:[\"corechart\"]});\n      google.setOnLoadCallback(drawChart);\n      function drawChart() {\n        var data = google.visualization.arrayToDataTable([\n          " . implode(",", $datatable) . "\n        ]);\n\n        var options = {\n          title: \"Network Usage - Hourly\",\n          hAxis: {title: \"Time Period\"},\n          vAxis: {title: \"Bandwidth (GB)\"},\n          legend: {position: \"in\"}\n        };\n\n        var chart = new google.visualization.AreaChart(document.getElementById(\"bwchart\"));\n        chart.draw(data, options);\n      }\n    </script>\n    <div id=\"vpsnetcont\">\n    <div id=\"bwchart\" style=\"width: 100%; height: 400px;\"></div>\n    </div>";
        return array("Bandwidth Graphs" => $vpsinfo);
    } else {
        if ($_REQUEST["managebackups"]) {
            $rtn = vpsnet_call($params, "backups", $netid, "GET");
            $vpsinfo .= "<div id=\"vpsnetcont\">\nThe list below shows all the backups for your virtual machine, along with the last time each of these backups was run.<br /><br />\n<table cellspacing=\"1\">\n<tr><th>Type</th><th>State</th><th>Date/Time</th><th>Size</th><th>Restore</th><th>Delete</th></tr>";
            foreach ($rtn["response"] as $backup) {
                $lastupdated = $backup["updated_at"];
                $lastupdated = strtotime($lastupdated);
                $lastupdated = date("F dS, Y H:i", $lastupdated);
                $vpsinfo .= "\n<tr><td>" . ucfirst($backup["backup_type"]) . "</td><td>" . ($backup["built"] ? "Completed" : "Pending") . "</td><td>" . $lastupdated . "</td><td>" . ($backup["built"] ? round($backup["backup_size"] / 1024, 1) . " MB" : "Not built yet") . "</td><td><a href=\"" . $_SERVER["PHP_SELF"] . "?userid=" . $_REQUEST["userid"] . "&id=" . $_REQUEST["id"] . "&addonid=" . (int) $params["addonId"] . "&modop=custom&ac=restorebackup&bid=" . $backup["id"] . "\" onclick=\"if (confirm('Are you sure you wish to restore this backup?')) { return true; } return false;\"><img src=\"../modules/servers/vpsnet/img/backup.png\" align=\"absmiddle\" /></a></td><td><a href=\"" . $_SERVER["PHP_SELF"] . "?userid=" . $_REQUEST["userid"] . "&id=" . $_REQUEST["id"] . "&addonid=" . (int) $params["addonId"] . "&modop=custom&ac=deletebackup&bid=" . $backup["id"] . "\" onclick=\"if (confirm('Are you sure you wish to delete this backup?')) { return true; } return false;\"><img src=\"../modules/servers/vpsnet/img/deletebackup.png\" align=\"absmiddle\" /></a></td></tr>";
            }
            $vpsinfo .= "\n</table>\n<div class=\"tools\">\n<a href=\"" . $_SERVER["PHP_SELF"] . "?userid=" . $_REQUEST["userid"] . "&id=" . $_REQUEST["id"] . "&addonid=" . $_REQUEST["addonid"] . "&modop=custom&ac=snapshotbackup\" onclick=\"if (confirm('Are you sure you want to create a new snapshot?')) { return true; } return false;\"><img src=\"../modules/servers/vpsnet/img/backup.png\" align=\"absmiddle\" /> Create a new Snapshot</a>&nbsp;&nbsp;\n<a href=\"" . $_SERVER["PHP_SELF"] . "?userid=" . $_REQUEST["userid"] . "&id=" . $_REQUEST["id"] . "&addonid=" . $_REQUEST["addonid"] . "&rsyncbackups=1\"><img src=\"../modules/servers/vpsnet/img/restore.png\" align=\"absmiddle\" /> Rsync Backups</a>\n</div>\n</div>";
            return array("Manage Backups" => $vpsinfo);
        } else {
            if ($_REQUEST["rsyncbackups"]) {
                $rtn = vpsnet_call($params, "backups/rsync_backup", $netid, "GET");
                $data = $rtn["response"];
                $vpsinfo .= "<div id=\"vpsnetcont\">\n<table cellspacing=\"1\">\n<tr><td class=\"fieldlabel\">Username</td><td>" . $data["username"] . "</td></tr>\n<tr><td class=\"fieldlabel\">Password</td><td>" . $data["password"] . "</td></tr>\n<tr><td class=\"fieldlabel\">Quota</td><td>" . $data["quota"] . "</td></tr>\n</table>\n<div class=\"tools\">\n<a href=\"" . $_SERVER["PHP_SELF"] . "?userid=" . $_REQUEST["userid"] . "&id=" . $_REQUEST["id"] . "&addonid=" . $_REQUEST["addonid"] . "&modop=custom&ac=snapshotbackup\" onclick=\"if (confirm('Are you sure you want to create a new snapshot?')) { return true; } return false;\"><img src=\"../modules/servers/vpsnet/img/backup.png\" align=\"absmiddle\" /> Create a new Snapshot</a>&nbsp;&nbsp;\n<a href=\"" . $_SERVER["PHP_SELF"] . "?userid=" . $_REQUEST["userid"] . "&id=" . $_REQUEST["id"] . "&addonid=" . $_REQUEST["addonid"] . "&rsyncbackups=1\"><img src=\"../modules/servers/vpsnet/img/backup.png\" align=\"absmiddle\" /> Rsync Backups</a>\n</div>\n</div>";
                return array("Rsync Backups Info" => $vpsinfo);
            }
            $rtn = vpsnet_call($params, "", $netid, "GET");
            if (!$rtn["success"]) {
                return false;
            }
            $data = $rtn["response"]["virtual_machine"];
            $running = $data["running"];
            $pending = $data["power_action_pending"];
            $runningstatus = $running ? "<img src=\"../modules/servers/vpsnet/img/running.png\" align=\"absmiddle\" /> " . $_LANG["vpsnetrunning"] : "<img src=\"../modules/servers/vpsnet/img/notrunning.png\" align=\"absmiddle\" /> " . $_LANG["vpsnetnotrunning"];
            if ($pending) {
                $runningstatus = "<img src=\"../modules/servers/vpsnet/img/notrunning.png\" align=\"absmiddle\" /> " . $_LANG["vpsnetpowercycling"];
            }
            $bwused = $data["bandwidth_used"];
            $bwused = $bwused / 1024 / 1024;
            $bwused = round($bwused, 2) . "MB";
            $cloudid = $data["cloud_id"];
            $templateid = $data["system_template_id"];
            $clouddata = vpsnet_call($params, "", $cloudid, 1, "clouds");
            $cloudname = $clouddata["response"]["label"];
            $vpsinfo .= "<div id=\"vpsnetcont\">\n<table cellspacing=\"1\">\n<tr><td class=\"fieldlabel\">Hostname</td><td>" . $data["hostname"] . "</td><td class=\"fieldlabel\">Domain Name</td><td>" . $data["domain_name"] . "</td></tr>\n<tr><td class=\"fieldlabel\">Nodes</td><td>" . $data["slices_count"] . "</td><td class=\"fieldlabel\">Cloud</td><td>" . $cloudname . "</td></tr>\n<tr><td class=\"fieldlabel\">Initial Root Password</td><td>" . $data["password"] . "</td><td class=\"fieldlabel\">Backups Enabled</td><td>" . ($data["backups_enabled"] ? "<img src=\"../modules/servers/vpsnet/img/tick.png\" align=\"absmiddle\" /> Yes" : "<img src=\"../modules/servers/vpsnet/img/cross.png\" align=\"absmiddle\" /> No") . "</td></tr>\n<tr><td class=\"fieldlabel\">Status</td><td>" . $runningstatus . "</td><td class=\"fieldlabel\">IP Address</td><td>" . $data["primary_ip_address"]["ip_address"]["ip_address"] . "</td></tr>\n<tr><td class=\"fieldlabel\">Monthly Bandwidth Used</td><td>" . $bwused . "</td><td class=\"fieldlabel\">Deployed Storage</td><td>" . $data["deployed_disk_size"] . "</td></tr>\n<tr><td class=\"fieldlabel\">Template</td><td>" . $templatelabel . "</td><td class=\"fieldlabel\">Licenses</td><td>None</td></tr>\n</table>\n<div class=\"tools\">";
            if ($data["power_action_pending"]) {
                $vpsinfo .= "<img src=\"../modules/servers/vpsnet/img/running.png\" align=\"absmiddle\" /> This VPS is currently running a task. Power Management Options Not Available Until Complete.";
            } else {
                if ($running) {
                    $vpsinfo .= "\n<a href=\"" . $_SERVER["PHP_SELF"] . "?userid=" . $_REQUEST["userid"] . "&id=" . $_REQUEST["id"] . "&addonid=" . $_REQUEST["addonid"] . "&modop=custom&ac=shutdown\" onclick=\"if (confirm('Are you sure you wish to shutdown this VPS?')) { return true; } return false;\"><img src=\"../modules/servers/vpsnet/img/shutdown.png\" align=\"absmiddle\" /> Shutdown</a>&nbsp;&nbsp;\n<a href=\"" . $_SERVER["PHP_SELF"] . "?userid=" . $_REQUEST["userid"] . "&id=" . $_REQUEST["id"] . "&addonid=" . $_REQUEST["addonid"] . "&modop=custom&ac=poweroff\" onclick=\"if (confirm('Are you sure you wish to force power off this VPS?')) { return true; } return false;\"><img src=\"../modules/servers/vpsnet/img/poweroff.png\" align=\"absmiddle\" /> Force Power Off</a>&nbsp;&nbsp;\n<a href=\"" . $_SERVER["PHP_SELF"] . "?userid=" . $_REQUEST["userid"] . "&id=" . $_REQUEST["id"] . "&addonid=" . $_REQUEST["addonid"] . "&modop=custom&ac=reboot\" onclick=\"if (confirm('Are you sure you wish to reboot this VPS?')) { return true; } return false;\"><img src=\"../modules/servers/vpsnet/img/reboot.png\" align=\"absmiddle\" /> Graceful Reboot</a>&nbsp;&nbsp;\n<a href=\"" . $_SERVER["PHP_SELF"] . "?userid=" . $_REQUEST["userid"] . "&id=" . $_REQUEST["id"] . "&addonid=" . $_REQUEST["addonid"] . "&modop=custom&ac=recover\" onclick=\"if (confirm('Are you sure you wish to reboot this VPS in recovery mode? Please note: in recovery mode the login is (root) and the password is (recovery).')) { return true; } return false;\"><img src=\"../modules/servers/vpsnet/img/recovery.png\" align=\"absmiddle\" /> Reboot in Recovery</a>&nbsp;&nbsp;\n<a href=\"" . $_SERVER["PHP_SELF"] . "?userid=" . $_REQUEST["userid"] . "&id=" . $_REQUEST["id"] . "&addonid=" . $_REQUEST["addonid"] . "&modop=custom&ac=rebuild\" onclick=\"if (confirm('Are you sure you want to rebuilt network for this VPS? Your virtual machine will be rebooted and the network interfaces configuration file on this virtual machine will be regenerated.')) { return true; } return false;\"><img src=\"../modules/servers/vpsnet/img/restart.png\" align=\"absmiddle\" /> Rebuild Network</a>\n";
                } else {
                    $vpsinfo .= "\n<a href=\"" . $_SERVER["PHP_SELF"] . "?userid=" . $_REQUEST["userid"] . "&id=" . $_REQUEST["id"] . "&addonid=" . $_REQUEST["addonid"] . "&modop=custom&ac=poweron\" onclick=\"if (confirm('Are you sure you wish to start this VPS?')) { return true; } return false;\"><img src=\"../modules/servers/vpsnet/img/startup.png\" align=\"absmiddle\" /> Startup</a>&nbsp;&nbsp;\n<a href=\"" . $_SERVER["PHP_SELF"] . "?userid=" . $_REQUEST["userid"] . "&id=" . $_REQUEST["id"] . "&addonid=" . $_REQUEST["addonid"] . "&modop=custom&ac=recover\" onclick=\"if (confirm('Are you sure you wish to start this VPS in recovery mode? Please note: in recovery mode the login is (root) and the password is (recovery).')) { return true; } return false;\"><img src=\"../modules/servers/vpsnet/img/recovery.png\" align=\"absmiddle\" /> Startup in Recovery</a>&nbsp;&nbsp;\n<a href=\"" . $_SERVER["PHP_SELF"] . "?userid=" . $_REQUEST["userid"] . "&id=" . $_REQUEST["id"] . "&addonid=" . $_REQUEST["addonid"] . "&modop=custom&ac=rebuild\" onclick=\"if (confirm('Are you sure you want to rebuilt network for this VPS? Your virtual machine will be rebooted and the network interfaces configuration file on this virtual machine will be regenerated.')) { return true; } return false;\"><img src=\"../modules/servers/vpsnet/img/restart.png\" align=\"absmiddle\" /> Rebuild Network</a>\n<a href=\"" . $_SERVER["PHP_SELF"] . "?userid=" . $_REQUEST["userid"] . "&id=" . $_REQUEST["id"] . "&addonid=" . $_REQUEST["addonid"] . "&modop=terminate\" onclick=\"if (confirm('Are you sure you wish to delete this VPS? Please note: recovery is only possible for up to 12 hours after deletion, and only your last 3 deleted VPS's will be available for recovery.')) { return true; } return false;\"><img src=\"../modules/servers/vpsnet/img/delete.png\" align=\"absmiddle\" /> Delete VPS</a>&nbsp;&nbsp;\n<a href=\"" . $_SERVER["PHP_SELF"] . "?userid=" . $_REQUEST["userid"] . "&id=" . $_REQUEST["id"] . "&addonid=" . $_REQUEST["addonid"] . "&modop=custom&ac=reinstall\" onclick=\"if (confirm('Are you sure you want to re-install this VPS?')) { return true; } return false;\"><img src=\"../modules/servers/vpsnet/img/restart.png\" align=\"absmiddle\" /> Re-install VPS</a>\n";
                }
            }
            $vpsinfo .= "\n</div>\n</div>\n\n\n";
            return array("VPS Overview" => $vpsinfo);
        }
    }
}
function vpsnet_ClientAreaAllowedFunctions()
{
    return array("cpugraphs", "networkgraphs");
}
function vpsnet_cpugraphs($params)
{
    global $_LANG;
    $pagearray = array("templatefile" => "cpugraphs", "breadcrumb" => " > <a href=\"" . $_SERVER["PHP_SELF"] . "#\">" . $_LANG["vpsnetcpugraphs"] . "</a>", "vars" => array("serviceid" => $params["serviceid"], "addonid" => (int) $params["addonId"]));
    return $pagearray;
}
function vpsnet_networkgraphs($params)
{
    global $_LANG;
    $pagearray = array("templatefile" => "networkgraphs", "breadcrumb" => " > <a href=\"" . $_SERVER["PHP_SELF"] . "#\">" . $_LANG["vpsnetnetworkgraphs"] . "</a>", "vars" => array("serviceid" => $params["serviceid"], "addonid" => (int) $params["addonId"]));
    return $pagearray;
}
function vpsnet_call($params, $action, $id, $reqtype = "", $type = "virtual_machines", $data = "", $nojsonencode = "")
{
    $creds = vpsnet_GetCredentials();
    $url = "https://api.vps.net/" . $type;
    if ($id) {
        $url .= "/" . $id;
    }
    if ($action) {
        $url .= "/" . $action;
    }
    $url .= ".api10json";
    if ($reqtype == "GET" && $data && !is_array($data)) {
        $url .= "?" . $data;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Accept: application/json"));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERPWD, $creds["username"] . ":" . $creds["accesshash"]);
    if ($reqtype == "GET") {
        curl_setopt($ch, CURLOPT_HTTPGET, 1);
    } else {
        if ($reqtype == "DELETE") {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        } else {
            if ($reqtype == "PUT") {
                curl_setopt($ch, CURLOPT_PUT, true);
                if (!is_null($data)) {
                    if ($nojsonencode) {
                        curl_setopt($ch, CURLOPT_INFILE, $data);
                        curl_setopt($ch, CURLOPT_INFILESIZE, strlen($data));
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array());
                    } else {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    }
                }
            } else {
                curl_setopt($ch, CURLOPT_POST, 1);
                if (!is_null($data)) {
                    if ($nojsonencode) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, array());
                    } else {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    }
                }
            }
        }
    }
    $rtn = array();
    $rtn["response_body"] = curl_exec($ch);
    $rtn["info"] = curl_getinfo($ch);
    $rtn["success"] = "0";
    if ($rtn["info"]["content_type"] == "application/json; charset=utf-8") {
        if ($rtn["info"]["http_code"] == 200) {
            $rtn["response"] = json_decode($rtn["response_body"], true);
            $rtn["success"] = "1";
        } else {
            if ($rtn["info"]["http_code"] == 422) {
                $rtn["errors"] = json_decode($rtn["response_body"], true);
            } else {
                if ($rtn["info"]["http_code"] == 406) {
                    $rtn["errors"] = array("Login Failed");
                } else {
                    $rtn["errors"] = json_decode($rtn["response_body"], true);
                }
            }
        }
    }
    if (curl_error($ch)) {
        $rtn["errors"] = array("Curl Error: " . curl_errno($ch) . " - " . curl_error($ch));
    }
    curl_close($ch);
    logModuleCall("vpsnet", $action, $url . " - " . json_encode($data), $rtn, $rtn["response"]);
    return $rtn;
}
function vpsnet_GetCredentials()
{
    return get_query_vals("tblservers", "id,username,accesshash", array("type" => "vpsnet"));
}
function vpsnet_ClientArea($params)
{
    $netid = get_query_val("mod_vpsnet", "value", array("relid" => $params["serviceid"], "addon_id" => (int) $params["addonId"], "setting" => "netid"));
    if (!$netid) {
        return false;
    }
    $tplvars = array();
    $tplvars["allowmanagement"] = true;
    global $whmcs;
    if ($whmcs->get_req_var("managebackups")) {
        $tplvars["managebackups"] = true;
        $rtn = vpsnet_call($params, "backups", $netid, "GET");
        $backups = array();
        foreach ($rtn["response"] as $backup) {
            $type = ucfirst($backup["backup_type"]);
            $state = $backup["built"] ? "Completed" : "Pending";
            $size = $backup["built"] ? round($backup["backup_size"] / 1024, 1) . " MB" : "Not built yet";
            $lastupdated = $backup["updated_at"];
            $lastupdated = strtotime($lastupdated);
            $lastupdated = date("F dS, Y H:i", $lastupdated);
            $backups[] = array("type" => $type, "state" => $state, "size" => $size, "lastupdated" => $lastupdated);
        }
        $tplvars["backups"] = $backups;
    } else {
        if ($whmcs->get_req_var("rsyncbackups")) {
            $tplvars["rsyncbackups"] = true;
            $rtn = vpsnet_call($params, "backups/rsync_backup", $netid, "GET");
            $data = $rtn["response"];
            $tplvars["rsync"] = $data;
        }
    }
    $rtn = vpsnet_call($params, "", $netid, "GET");
    if ($rtn["success"]) {
        $data = $rtn["response"]["virtual_machine"];
        foreach ($data as $k => $v) {
            $tplvars[$k] = $v;
        }
        $running = $data["running"];
        $pending = $data["power_action_pending"];
        $runningstatus = $running ? "<img src=\"./modules/servers/vpsnet/img/running.png\" align=\"absmiddle\" /> " . $_LANG["vpsnetrunning"] : "<img src=\"./modules/servers/vpsnet/img/notrunning.png\" align=\"absmiddle\" /> " . $_LANG["vpsnetnotrunning"];
        if ($pending) {
            $runningstatus = "<img src=\"./modules/servers/vpsnet/img/notrunning.png\" align=\"absmiddle\" /> " . $_LANG["vpsnetpowercycling"];
        }
        $tplvars["runningstatus"] = $runningstatus;
        $bwused = $data["bandwidth_used"];
        $bwused = $bwused / 1024 / 1024;
        $bwused = round($bwused, 2) . "MB";
        $cloudid = $data["cloud_id"];
        $templateid = $data["system_template_id"];
        $clouddata = vpsnet_call($params, "", $cloudid, "GET", "clouds");
        foreach ($clouddata["response"]["system_templates"] as $templatearr) {
            $availtemplates[$templatearr["id"]] = $templatearr["label"];
        }
        $templatelabel = $availtemplates[$templateid];
        $tplvars["templatelabel"] = $templatelabel;
        $cloudname = $clouddata["response"]["label"];
        $tplvars["cloudname"] = $cloudname;
    }
    return array("vars" => array("vpsnet" => $tplvars));
}

?>