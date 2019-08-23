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
$PMRoleID = get_query_val("tbladmins", "roleid", array("id" => $_SESSION["adminid"]));
if (!$vars["masteradmin" . $PMRoleID]) {
    echo $headeroutput . "\n<h2>Access Denied</h2>\n<p>You must be granted Master Admin User status in the Project Management Addon Configuration area within <strong><a href=\"configaddonmods.php#project_management\">Setup > Addon Modules</a></strong> before you are allowed to access this page.</p>";
    return false;
}
if ($_POST["save"]) {
    check_token("WHMCS.admin.default");
    delete_query("tbladdonmodules", array("module" => "project_management", "setting" => "hourlyrate"));
    insert_query("tbladdonmodules", array("module" => "project_management", "setting" => "hourlyrate", "value" => format_as_currency($_POST["hourlyrate"])));
    delete_query("tbladdonmodules", array("module" => "project_management", "setting" => "statusvalues"));
    insert_query("tbladdonmodules", array("module" => "project_management", "setting" => "statusvalues", "value" => $_POST["statusvalues"]));
    delete_query("tbladdonmodules", array("module" => "project_management", "setting" => "completedstatuses"));
    insert_query("tbladdonmodules", array("module" => "project_management", "setting" => "completedstatuses", "value" => implode(",", $_POST["completestatus"])));
    delete_query("tbladdonmodules", array("module" => "project_management", "setting" => "perms"));
    insert_query("tbladdonmodules", array("module" => "project_management", "setting" => "perms", "value" => safe_serialize($_POST["perms"])));
    delete_query("tbladdonmodules", array("module" => "project_management", "setting" => "clientenable"));
    insert_query("tbladdonmodules", array("module" => "project_management", "setting" => "clientenable", "value" => $_POST["clientenable"]));
    delete_query("tbladdonmodules", array("module" => "project_management", "setting" => "clientfeatures"));
    insert_query("tbladdonmodules", array("module" => "project_management", "setting" => "clientfeatures", "value" => implode(",", $_POST["clfeat"])));
    redir("module=project_management&m=settings");
}
$adminroles = array();
$result = select_query("tbladminroles", "", "", "name", "ASC");
while ($data = mysql_fetch_array($result)) {
    $adminroles[$data["id"]] = $data["name"];
}
$permissions = project_management_permslist();
echo $headeroutput . "\n\n<form method=\"post\" action=\"" . $modulelink . "\">\n<input type=\"hidden\" name=\"save\" value=\"1\" />";
echo "\n\n<div class=\"pm-addon\">\n    <ul class=\"nav nav-tabs pm-tabs\" role=\"tablist\">\n        <li id=\"tabGeneral\" role=\"presentation\" class=\"active\">\n            <a href=\"#general\" aria-controls=\"general\" role=\"tab\" data-toggle=\"tab\">\n                <i class=\"fas fa-cog fa-fw\"></i>\n                General\n            </a>\n        </li>\n        <li id=\"tabClientarea\" role=\"presentation\">\n            <a href=\"#clientarea\" aria-controls=\"clientarea\" role=\"tab\" data-toggle=\"tab\">\n                <i class=\"fas fa-user fa-fw\"></i>\n                Client Area\n            </a>\n        </li>\n        <li id=\"tabPermissions\" role=\"presentation\">\n            <a href=\"#permissions\" aria-controls=\"permissions\" role=\"tab\" data-toggle=\"tab\">\n                <i class=\"fas fa-user fa-fw\"></i>\n                Permissions\n            </a>\n        </li>\n    </ul>\n\n    <div class=\"tab-content\">\n        <div role=\"tabpanel\" class=\"tab-pane active\" id=\"general\">\n            <div class=\"project-tab-padding\">\n                \n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"200\" class=\"fieldlabel\">Default Hourly Rate</td><td class=\"fieldarea\">\n    <input type=\"text\" name=\"hourlyrate\" size=\"15\" value=\"";
echo $vars["hourlyrate"];
echo "\" class=\"form-control input-150\">\n    Enter the standard hourly rate you charge for use in time based billing (can be overriden at the time of invoice generation)\n</td></tr>\n<tr><td class=\"fieldlabel\">Project Statuses</td><td class=\"fieldarea\">\n    <input type=\"text\" name=\"statusvalues\" size=\"90\" value=\"";
echo $vars["statusvalues"];
echo "\" class=\"form-control\">\n    Enter a comma separated list of the statuses you want to setup for projects\n</td></tr>\n<tr><td width=\"200\" class=\"fieldlabel\">Completed Statuses</td><td class=\"fieldarea\">\n    <blockquote>\n    ";
$statuses = explode(",", $vars["statusvalues"]);
$completestatuses = explode(",", $vars["completedstatuses"]);
foreach ($statuses as $status) {
    echo "<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"completestatus[]\" value=\"" . $status . "\"" . (in_array($status, $completestatuses) ? " checked" : "") . " /> " . current(explode("|", $status)) . "</label><br>";
}
echo "    </blockquote>\n    Choose the statuses above that should be treated as closed/completed</td></tr>\n</table>\n\n<br>\n\n<p align=\"center\">\n    <input type=\"submit\" value=\"";
echo AdminLang::trans("global.savechanges");
echo "\" class=\"btn btn-primary\">\n    <input type=\"reset\" value=\"";
echo AdminLang::trans("global.cancelchanges");
echo "\" class=\"btn btn-default\">\n</p>\n\n\n            </div>\n        </div>\n        <div role=\"tabpanel\" class=\"tab-pane\" id=\"clientarea\">\n            <div class=\"project-tab-padding\">\n                \n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"200\" class=\"fieldlabel\">Enable/Disable</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"clientenable\" value=\"1\"";
if ($vars["clientenable"]) {
    echo " checked";
}
echo " /> Tick to enable Client Area Project Access</label></td></tr>\n<tr><td class=\"fieldlabel\">Allow Access To</td><td class=\"fieldarea\">\n    <blockquote>\n        <label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"clfeat[]\" value=\"tasks\"";
$clfeat = explode(",", $vars["clientfeatures"]);
if (in_array("tasks", $clfeat)) {
    echo " checked";
}
echo " /> View Project Tasks</label><br>\n        <label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"clfeat[]\" value=\"time\"";
if (in_array("time", $clfeat)) {
    echo " checked";
}
echo " /> View Task Time Logs</label><br>\n        <label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"clfeat[]\" value=\"addtasks\"";
if (in_array("addtasks", $clfeat)) {
    echo " checked";
}
echo " /> Add New Tasks</label><br>\n        <label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"clfeat[]\" value=\"staff\"";
if (in_array("staff", $clfeat)) {
    echo " checked";
}
echo " /> View Assigned Staff Member</label><br>\n        <label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"clfeat[]\" value=\"files\"";
if (in_array("files", $clfeat)) {
    echo " checked";
}
echo " /> View/Upload Files</label>\n    </blockquote>\n</td></tr>\n</table>\n\n<br>\n\n<p align=\"center\">\n    <input type=\"submit\" value=\"";
echo AdminLang::trans("global.savechanges");
echo "\" class=\"btn btn-primary\">\n    <input type=\"reset\" value=\"";
echo AdminLang::trans("global.cancelchanges");
echo "\" class=\"btn btn-default\">\n</p>\n\n\n            </div>\n        </div>\n        <div role=\"tabpanel\" class=\"tab-pane\" id=\"permissions\">\n            <div class=\"project-tab-padding\">\n                \n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr style=\"text-align:center;\"><td></td>";
foreach ($adminroles as $aid => $aname) {
    echo "<td>" . $aname . "</td>";
}
echo "</tr>\n";
foreach ($permissions as $permid => $permname) {
    echo "<tr><td width=\"200\" class=\"fieldlabel\">" . $permname . "</td>";
    foreach ($adminroles as $aid => $aname) {
        echo "<td class=\"fieldarea\" style=\"text-align:center;\"><input type=\"checkbox\" name=\"perms[" . $permid . "][" . $aid . "]\" value=\"1\"";
        if ($perms[$permid][$aid]) {
            echo " checked";
        }
        echo " /></td>";
    }
    echo "</tr>";
}
echo "</table>\n\n<br>\n\n<p align=\"center\">\n    <input type=\"submit\" value=\"";
echo AdminLang::trans("global.savechanges");
echo "\" class=\"btn btn-primary\">\n    <input type=\"reset\" value=\"";
echo AdminLang::trans("global.cancelchanges");
echo "\" class=\"btn btn-default\">\n</p>\n\n\n            </div>\n        </div>\n    </div>\n</div>\n\n</form>\n\n";

?>