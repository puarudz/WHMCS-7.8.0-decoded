<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Configure Admin Roles");
$aInt->title = $aInt->lang("setup", "adminroles");
$aInt->sidebar = "config";
$aInt->icon = "adminroles";
$aInt->helplink = "Administrator Roles";
$aInt->requireAuthConfirmation();
$aInt->requiredFiles(array("reportfunctions"));
$chart = new WHMCSChart();
$jsCode = $jQueryCode = "";
$id = App::getFromRequest("id");
$action = App::getFromRequest("action");
$widgetList = $reportList = array();
if ($action == "save" || $action == "edit") {
    function load_admin_home_widgets()
    {
        global $hooks;
        if (!is_array($hooks)) {
            if (defined("HOOKSLOGGING")) {
                logActivity(sprintf("Hooks Debug: Hook File: the hooks list has been mutated to %s", ucfirst(gettype($hooks))));
            }
            $hooks = array();
        }
        $hook_name = "AdminHomeWidgets";
        $args = array("adminid" => $_SESSION["adminid"], "loading" => "<img src=\"images/loading.gif\" align=\"absmiddle\" /> " . AdminLang::trans("global.loading"));
        if (!array_key_exists($hook_name, $hooks)) {
            return array();
        }
        reset($hooks[$hook_name]);
        $results = array();
        foreach ($hooks[$hook_name] as $hook) {
            $widgetName = substr($hook["hook_function"], 7);
            if (function_exists($hook["hook_function"])) {
                $res = call_user_func($hook["hook_function"], $args);
                if ($res) {
                    $results[$widgetName] = $res["title"];
                }
            }
        }
        return $results;
    }
    $hooksDir = ROOTDIR . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . "widgets" . DIRECTORY_SEPARATOR;
    if (is_dir($hooksDir)) {
        $dh = opendir($hooksDir);
        while (false !== ($hookFile = readdir($dh))) {
            if (is_file($hooksDir . $hookFile) && $hookFile != "index.php") {
                $extension = explode(".", $hookFile);
                $extension = end($extension);
                if ($extension == "php") {
                    include $hooksDir . $hookFile;
                }
            }
        }
        closedir($dh);
    }
    $widgetList = load_admin_home_widgets();
    asort($widgetList);
    $reportList = getReportsList();
}
if ($action == "addrole") {
    check_token("WHMCS.admin.default");
    if (defined("DEMO_MODE")) {
        redir("demo=1");
    }
    $adminrole = insert_query("tbladminroles", array("name" => $name));
    logAdminActivity("Admin Role Created: " . $name);
    redir("action=edit&id=" . $adminrole);
}
if ($action == "duplicaterole") {
    check_token("WHMCS.admin.default");
    if (defined("DEMO_MODE")) {
        redir("demo=1");
    }
    $newName = App::getFromRequest("newname");
    $existingGroup = App::getFromRequest("existinggroup");
    $data = Illuminate\Database\Capsule\Manager::table("tbladminroles")->find($existingGroup);
    $name = $data->name;
    $widgets = $data->widgets;
    $reports = $data->reports;
    $systemEmails = $data->systememails;
    $accountEmails = $data->accountemails;
    $supportEmails = $data->supportemails;
    $roleId = Illuminate\Database\Capsule\Manager::table("tbladminroles")->insertGetId(array("name" => $newName, "widgets" => $widgets, "reports" => $reports, "systememails" => $systemEmails, "accountemails" => $accountEmails, "supportemails" => $supportEmails));
    $permissionsToCopy = Illuminate\Database\Capsule\Manager::table("tbladminperms")->where("roleid", "=", $existingGroup)->get();
    $insertPermissions = array();
    foreach ($permissionsToCopy as $permissionToCopy) {
        $insertPermissions[] = array("roleid" => $roleId, "permid" => $permissionToCopy->permid);
    }
    if ($insertPermissions) {
        Illuminate\Database\Capsule\Manager::table("tbladminperms")->insert($insertPermissions);
    }
    logAdminActivity("Admin Role Duplicated: " . $name . " to " . $newName);
    redir("action=edit&id=" . $roleId);
}
if ($action == "save") {
    check_token("WHMCS.admin.default");
    if (defined("DEMO_MODE")) {
        redir("demo=1");
    }
    $changes = array();
    $id = (int) $whmcs->get_req_var("id");
    $name = (string) $whmcs->get_req_var("name");
    $widget = $whmcs->get_req_var("widget") ?: array();
    $report = $whmcs->getFromRequest("report") ?: array();
    $reportRestrictions = $whmcs->getFromRequest("restrictReports");
    if ($reportRestrictions == "none") {
        $report = array_keys($reportList);
    }
    $systemEmails = (int) $whmcs->get_req_var("systememails");
    $accountEmails = (int) $whmcs->get_req_var("accountemails");
    $supportEmails = (int) $whmcs->get_req_var("supportemails");
    $adminPermissions = $whmcs->get_req_var("adminperms") ?: array();
    $adminRole = Illuminate\Database\Capsule\Manager::table("tbladminroles")->find($id);
    if ($name != $adminRole->name) {
        $changes[] = "Name changed from " . $adminRole->name . " to " . $name . ".";
    }
    if ($systemEmails != $adminRole->systememails) {
        if ($systemEmails) {
            $changes[] = "System Level Email Notifications Enabled";
        } else {
            $changes[] = "System Level Email Notifications Disabled";
        }
    }
    if ($accountEmails != $adminRole->accountemails) {
        if ($accountEmails) {
            $changes[] = "Account Level Email Notifications Enabled";
        } else {
            $changes[] = "Account Level Email Notifications Disabled";
        }
    }
    if ($supportEmails != $adminRole->supportemails) {
        if ($supportEmails) {
            $changes[] = "Support Email Notifications Enabled";
        } else {
            $changes[] = "Support Email Notifications Disabled";
        }
    }
    $currentWidgets = explode(",", $adminRole->widgets);
    $newWidgets = $removedWidgets = array();
    foreach ($widget as $savingWidget) {
        if (!in_array($savingWidget, $currentWidgets)) {
            $newWidgets[] = $widgetList[$savingWidget];
        }
    }
    foreach ($currentWidgets as $currentWidget) {
        if (!in_array($currentWidget, $widget)) {
            $removedWidgets[] = $widgetList[$currentWidget];
        }
    }
    if (array_filter($newWidgets)) {
        $changes[] = "Widgets Added: " . implode(", ", $newWidgets);
    }
    if (array_filter($removedWidgets)) {
        $changes[] = "Widgets Removed: " . implode(", ", $removedWidgets);
    }
    $currentDeniedReports = explode(",", $adminRole->reports);
    $currentReports = array_filter(array_keys($reportList), function ($var) use($currentDeniedReports) {
        return !in_array($var, $currentDeniedReports);
    });
    $newReports = $removedReports = array();
    foreach ($report as $savingReport) {
        if (!in_array($savingReport, $currentReports)) {
            $newReports[] = $reportList[$savingReport];
        }
    }
    foreach ($currentReports as $currentReport) {
        if (!in_array($currentReport, $report)) {
            $removedReports[] = $reportList[$currentReport];
        }
    }
    if (array_filter($newReports)) {
        $changes[] = "Reports Access Added: " . implode(", ", $newReports);
    }
    if (array_filter($removedReports)) {
        $changes[] = "Reports Access Removed: " . implode(", ", $removedReports);
    }
    $reportsToSave = array_filter(array_keys($reportList), function ($var) use($report) {
        return !in_array($var, $report);
    });
    $rolePermissions = Illuminate\Database\Capsule\Manager::table("tbladminperms")->where("roleid", "=", $id)->get(array("permid"));
    $permissions = $newPermissions = $removedPermissions = $permissionList = $inserts = array();
    foreach ($rolePermissions as $rolePermission) {
        $permissions[] = $rolePermission->permid;
    }
    Illuminate\Database\Capsule\Manager::table("tbladminroles")->where("id", "=", $id)->update(array("name" => $name, "widgets" => implode(",", $widget), "reports" => implode(",", $reportsToSave), "systememails" => $systemEmails, "accountemails" => $accountEmails, "supportemails" => $supportEmails));
    delete_query("tbladminperms", array("roleid" => $id));
    foreach ($adminPermissions as $k => $v) {
        $permissionList[] = $k;
        if (!in_array($k, $permissions)) {
            $newPermissions[] = AdminLang::trans("permissions." . $k);
        }
        $inserts[] = array("roleid" => $id, "permid" => $k);
    }
    if ($permissionList) {
        Illuminate\Database\Capsule\Manager::table("tbladminperms")->insert($inserts);
    }
    foreach ($permissions as $permission) {
        if (!in_array($permission, $permissionList)) {
            $removedPermissions[] = AdminLang::trans("permissions." . $permission);
        }
    }
    if (array_filter($newPermissions)) {
        $changes[] = "Added Permissions: " . implode(", ", $newPermissions);
    }
    if (array_filter($removedPermissions)) {
        $changes[] = "Removed Permissions: " . implode(", ", $removedPermissions);
    }
    if ($changes) {
        logAdminActivity("Admin Role Group Modified: '" . $adminRole->name . "' - " . implode(". ", $changes));
    }
    redir("saved=true");
}
if ($action == "delete") {
    check_token("WHMCS.admin.default");
    if (defined("DEMO_MODE")) {
        redir("demo=1");
    }
    $id = $whmcs->get_req_var("id");
    $adminRole = Illuminate\Database\Capsule\Manager::table("tbladminroles")->find($id);
    $admincount = get_query_val("tbladmins", "COUNT(id)", array("roleid" => $id));
    if ($admincount) {
        redir();
    }
    delete_query("tbladminroles", array("id" => $id));
    delete_query("tbladminperms", array("roleid" => $id));
    logAdminActivity("Admin Role Deleted: " . $adminRole->name);
    redir("deleted=true");
}
ob_start();
if (!$action) {
    $infobox = "";
    if (defined("DEMO_MODE")) {
        infoBox("Demo Mode", "Actions on this page are unavailable while in demo mode. Changes will not be saved.");
    }
    if ($saved) {
        infoBox($aInt->lang("global", "changesuccess"), $aInt->lang("global", "changesuccessdesc"));
    }
    if ($deleted) {
        infoBox($aInt->lang("adminroles", "deletesuccess"), $aInt->lang("adminroles", "deletesuccessinfo"));
    }
    echo $infobox;
    $aInt->deleteJSConfirm("doDelete", "adminroles", "suredelete", $_SERVER["PHP_SELF"] . "?action=delete&id=");
    echo "\n<p>";
    echo $aInt->lang("adminroles", "description");
    echo "</p>\n\n<p>\n    <div class=\"btn-group\" role=\"group\">\n        <a href=\"configadminroles.php?action=add\" class=\"btn btn-default\"><i class=\"fas fa-plus\"></i> ";
    echo $aInt->lang("adminroles", "addnew");
    echo "</a>\n        <a href=\"configadminroles.php?action=duplicate\" class=\"btn btn-default\"><i class=\"fas fa-plus-square\"></i> ";
    echo $aInt->lang("adminroles", "duplicate");
    echo "</a>\n    </div>\n</p>\n\n";
    $aInt->sortableTableInit("nopagination");
    $result = select_query("tbladminroles", "", "", "name", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $deletejs = 3 < $data["id"] ? "doDelete('" . $data["id"] . "')" : "alert('" . $aInt->lang("adminroles", "nodeldefault", 1) . "')";
        $assigned = array();
        $result2 = select_query("tbladmins", "id,username,disabled", array("roleid" => $data["id"]), "username", "ASC");
        while ($data2 = mysql_fetch_array($result2)) {
            $assigned[] = "<a href=\"configadmins.php?action=manage&id=" . $data2["id"] . "\"" . ($data2["disabled"] ? " style=\"color:#ccc;\"" : "") . ">" . $data2["username"] . "</a>";
        }
        if (count($assigned)) {
            $deletejs = "alert('" . $aInt->lang("adminroles", "nodelinuse", 1) . "')";
        } else {
            $assigned[] = $aInt->lang("global", "none");
        }
        $tabledata[] = array($data["name"], implode(", ", $assigned), "<a href=\"?action=edit&id=" . $data["id"] . "\"><img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "edit") . "\"></a>", "<a href=\"#\" onClick=\"" . $deletejs . "\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\"></a>");
    }
    echo $aInt->sortableTable(array($aInt->lang("fields", "groupname"), $aInt->lang("supportticketdepts", "assignedadmins"), "", ""), $tabledata);
} else {
    if ($action == "add") {
        $infobox = "";
        if (defined("DEMO_MODE")) {
            infoBox("Demo Mode", "Actions on this page are unavailable while in demo mode. Changes will not be saved.");
        }
        echo $infobox;
        echo "\n<p><strong>";
        echo $aInt->lang("adminroles", "addnew");
        echo "</strong></p>\n<form method=\"post\" action=\"";
        echo $_SERVER["PHP_SELF"];
        echo "?action=addrole\">\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"15%\" class=\"fieldlabel\">";
        echo $aInt->lang("fields", "name");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"name\" value=\"";
        echo $name;
        echo "\" class=\"form-control input-400\"></td></tr>\n</table>\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
        echo $aInt->lang("global", "continue");
        echo " &raquo;\" class=\"button btn btn-primary\" />\n</div>\n</form>\n\n";
    } else {
        if ($action == "duplicate") {
            $infobox = "";
            if (defined("DEMO_MODE")) {
                infoBox("Demo Mode", "Actions on this page are unavailable while in demo mode. Changes will not be saved.");
            }
            echo $infobox;
            echo "\n<p><strong>";
            echo $aInt->lang("adminroles", "duplicate");
            echo "</strong></p>\n<form method=\"post\" action=\"";
            echo $_SERVER["PHP_SELF"];
            echo "?action=duplicaterole\">\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"15%\" class=\"fieldlabel\">";
            echo $aInt->lang("adminroles", "existinggroupname");
            echo "</td><td class=\"fieldarea\"><select name=\"existinggroup\" class=\"form-control select-inline\">";
            $result = select_query("tbladminroles", "", "", "name", "ASC");
            while ($data = mysql_fetch_array($result)) {
                echo "<option value=\"" . $data["id"] . "\">" . $data["name"] . "</option>";
            }
            echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("adminroles", "newgroupname");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"newname\" value=\"";
            echo $name;
            echo "\" class=\"form-control input-400\"></td></tr>\n</table>\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
            echo $aInt->lang("global", "continue");
            echo " &raquo;\" class=\"button btn btn-default\" />\n</div>\n</form>\n\n";
        } else {
            if ($action == "edit") {
                if (!$id) {
                    redir();
                }
                $result = select_query("tbladminroles", "", array("id" => $id));
                $data = mysql_fetch_array($result);
                $name = $data["name"];
                $widgets = $data["widgets"];
                $systememails = $data["systememails"];
                $accountemails = $data["accountemails"];
                $supportemails = $data["supportemails"];
                $reports = $data["reports"];
                $widgets = array_filter(explode(",", $widgets));
                $reports = array_filter(explode(",", $reports));
                $adminpermsarray = getAdminPermsArray();
                $totalpermissions = count($adminpermsarray);
                $totalpermissionspercolumn = round($totalpermissions / 3);
                $infobox = "";
                if (defined("DEMO_MODE")) {
                    infoBox("Demo Mode", "Actions on this page are unavailable while in demo mode. Changes will not be saved.");
                }
                echo $infobox;
                echo "<script type=\"text/javascript\">\nfunction zCheckAll(oForm) {\n    oForm.find(':checkbox').prop('checked', true);\n}\nfunction zUncheckAll(oForm) {\n    oForm.find(':checkbox').prop('checked', false);\n}\n</script>\n<form method=\"post\" action=\"";
                echo $_SERVER["PHP_SELF"];
                echo "?action=save&id=";
                echo $id;
                echo "\" name=\"frmperms\">\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"15%\" class=\"fieldlabel\">";
                echo $aInt->lang("fields", "name");
                echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"name\" value=\"";
                echo $name;
                echo "\" class=\"form-control input-400\"></td></tr>\n<tr><td class=\"fieldlabel\">";
                echo $aInt->lang("adminroles", "permissions");
                echo "</td><td class=\"fieldarea\">\n    <div class=\"row\" id=\"rowPermissions\">\n        <div class=\"col-md-4\">\n            ";
                $rowcount = 0;
                $colcount = 0;
                foreach ($adminpermsarray as $k => $v) {
                    echo "<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"adminperms[" . $k . "]\"";
                    $result = select_query("tbladminperms", "COUNT(*)", array("roleid" => $id, "permid" => $k));
                    $data = mysql_fetch_array($result);
                    if ($data[0]) {
                        echo " checked";
                    }
                    echo "> " . $aInt->lang("permissions", $k) . "</label><br>";
                    $rowcount++;
                    if ($rowcount == $totalpermissionspercolumn) {
                        if ($colcount < 2) {
                            echo "</div><div class=\"col-md-4\">";
                        }
                        $rowcount = 0;
                        $colcount++;
                    }
                }
                echo "        </div>\n    </div>\n<div align=\"right\"><a href=\"#\" onClick=\"zCheckAll(\$('#rowPermissions'));return false\">";
                echo $aInt->lang("adminroles", "checkall");
                echo "</a> | <a href=\"#\" onClick=\"zUncheckAll(\$('#rowPermissions'));return false\">";
                echo $aInt->lang("adminroles", "uncheckall");
                echo "</a></div></td></tr>\n";
                if ($widgetList) {
                    echo "<tr><td class=\"fieldlabel\">";
                    echo $aInt->lang("adminroles", "widgets");
                    echo "</td><td class=\"fieldarea\">\n\n<div class=\"row\">\n    <div class=\"col-md-4 col-sm-6\">\n";
                    $totalportlets = ceil(count($widgetList) / 3);
                    $i = 1;
                    foreach ($widgetList as $k => $v) {
                        echo "<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"widget[]\" value=\"" . $k . "\"";
                        if (in_array($k, $widgets)) {
                            echo " checked";
                        }
                        echo " /> " . $v . "</label><br />";
                        if ($totalportlets <= $i) {
                            echo "</div><div class=\"col-md-4 col-sm-6\">";
                            $i = 1;
                        } else {
                            $i++;
                        }
                    }
                    echo "    </div>\n</div>\n\n</td></tr>\n";
                }
                if ($reportList) {
                    $doNotRestrict = $doRestrict = "";
                    if (!$reports) {
                        $doNotRestrict = "checked=\"checked\" ";
                    } else {
                        $doRestrict = "checked=\"checked\" ";
                    }
                    $jQueryCode = "jQuery('input[name=\"restrictReports\"]').on('change', function() {\n    if (jQuery(this).val() == 'none') {\n        jQuery('#reportListRow').fadeOut('fast');\n    } else {\n        jQuery('#reportListRow').hide().removeClass('hidden').fadeIn('fast');\n    }\n});";
                    echo "<tr>\n    <td class=\"fieldlabel\">";
                    echo AdminLang::trans("adminroles.reports");
                    echo "</td>\n    <td class=\"fieldarea\">\n\n        <label class=\"radio-inline\">\n            <input type=\"radio\" name=\"restrictReports\" value=\"none\" ";
                    echo $doNotRestrict;
                    echo "/>\n            ";
                    echo AdminLang::trans("adminroles.doNotRestrictReports");
                    echo "        </label>\n        <label class=\"radio-inline\">\n            <input type=\"radio\" name=\"restrictReports\" value=\"restrict\" ";
                    echo $doRestrict;
                    echo "/>\n            ";
                    echo AdminLang::trans("adminroles.restrictReports");
                    echo "        </label>\n\n        <div class=\"";
                    echo $reports ? "" : " hidden";
                    echo "\" id=\"reportListRow\">\n            <div class=\"inset-whitebg-container\">\n                <div class=\"row\">\n                    ";
                    foreach ($reportList as $k => $v) {
                        echo "<div class=\"col-md-4 col-sm-6\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"report[]\" value=\"" . $k . "\"";
                        if (!in_array($k, $reports)) {
                            echo " checked";
                        }
                        echo " /> " . $v . "</label></div>";
                    }
                    echo "                </div>\n            </div>\n            <div align=\"right\"><a href=\"#\" onClick=\"zCheckAll(\$('#reportListRow'));return false\">";
                    echo AdminLang::trans("adminroles.checkall");
                    echo "</a> | <a href=\"#\" onClick=\"zUncheckAll(\$('#reportListRow'));return false\">";
                    echo AdminLang::trans("adminroles.uncheckall");
                    echo "</a></div>\n        </div>\n    </td>\n</tr>\n";
                }
                echo "<tr><td class=\"fieldlabel\">";
                echo $aInt->lang("adminroles", "emailmessages");
                echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"systememails\" value=\"1\"";
                if ($systememails) {
                    echo " checked";
                }
                echo "> ";
                echo $aInt->lang("adminroles", "systememails");
                echo "</label><br /><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"accountemails\" value=\"1\"";
                if ($accountemails) {
                    echo " checked";
                }
                echo "> ";
                echo $aInt->lang("adminroles", "accountemails");
                echo "</label><br /><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"supportemails\" value=\"1\"";
                if ($supportemails) {
                    echo " checked";
                }
                echo "> ";
                echo $aInt->lang("adminroles", "supportemails");
                echo "</label></td></tr>\n</table>\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
                echo $aInt->lang("global", "savechanges");
                echo "\" class=\"btn btn-primary\" />\n    <input type=\"button\" value=\"";
                echo $aInt->lang("global", "cancelchanges");
                echo "\" class=\"btn btn-default\" onclick=\"window.location='configadminroles.php'\" />\n</div>\n</form>\n";
            }
        }
    }
}
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jscode = $jsCode;
$aInt->jquerycode = $jQueryCode;
$aInt->display();

?>