<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("My Account", false);
$aInt->title = $aInt->lang("global", "myaccount");
$aInt->sidebar = "config";
$aInt->icon = "home";
$aInt->helplink = "My Account";
$aInt->requiredFiles(array("ticketfunctions"));
$action = $whmcs->get_req_var("action");
$errormessage = "";
$twofa = new WHMCS\TwoFactorAuthentication();
$twofa->setAdminID($aInt->getAdminID());
$file = new WHMCS\File\Directory($whmcs->get_admin_folder_name() . DIRECTORY_SEPARATOR . "templates");
$adminTemplates = $file->getSubdirectories();
if ($action == "save") {
    check_token("WHMCS.admin.default");
    if (defined("DEMO_MODE")) {
        redir("demo=1");
    }
    $newPassword = $whmcs->get_req_var("password");
    $newPassword = $newPassword ? trim($newPassword) : "";
    $passwordRetype = $whmcs->get_req_var("password2");
    $passwordRetype = $passwordRetype ? trim($passwordRetype) : "";
    $template = $whmcs->getFromRequest("template");
    $language = $whmcs->getFromRequest("language");
    $firstname = $whmcs->getFromRequest("firstname");
    $lastname = $whmcs->getFromRequest("lastname");
    $email = $whmcs->getFromRequest("email");
    $signature = $whmcs->getFromRequest("signature");
    $notes = $whmcs->getFromRequest("notes");
    $ticketnotify = $whmcs->getFromRequest("ticketnotify");
    if (!$auth instanceof WHMCS\Auth) {
        $auth = new WHMCS\Auth();
    }
    $currentPasswd = $whmcs->get_req_var("currentPasswd");
    $auth->getInfobyID($aInt->getAdminID());
    if ($auth->comparePassword($currentPasswd)) {
        if ($newPassword != $passwordRetype) {
            $errormessage = $aInt->lang("administrators", "pwmatcherror");
            $action = "edit";
        } else {
            if (WHMCS\Database\Capsule::table("tblticketdepartments")->where("email", "=", $email)->count()) {
                $errormessage = AdminLang::trans("administrators.emailCannotBeSupport");
                $action = "edit";
            } else {
                $currentDetails = WHMCS\User\Admin::find($aInt->getAdminID());
                if (!in_array($template, $adminTemplates)) {
                    $template = $adminTemplates[0];
                }
                $language = WHMCS\Language\AdminLanguage::getValidLanguageName($language);
                if ($email != $currentDetails->email) {
                    $currentDetails->email = $email;
                }
                $currentDetails->firstName = $firstname;
                $currentDetails->lastName = $lastname;
                $currentDetails->signature = $signature;
                $currentDetails->notes = $notes;
                $currentDetails->template = $template;
                $currentDetails->language = $language;
                $currentDetails->receivesTicketNotifications = $ticketnotify ?: array();
                $currentDetails->passwordResetKey = "";
                $currentDetails->passwordResetData = "";
                $currentDetails->passwordResetExpiry = "0000-00-00 00:00:00";
                if ($currentDetails->validate()) {
                    $currentDetails->save();
                    if ($newPassword) {
                        $auth->getInfobyID($aInt->getAdminID());
                        if ($auth->generateNewPasswordHashAndStore($newPassword)) {
                            $auth->generateNewPasswordHashAndStoreForApi(md5($newPassword));
                            $auth->setSessionVars();
                        }
                    }
                    WHMCS\Session::delete("adminlang");
                    logActivity("Administrator Account Modified (" . $firstname . " " . $lastname . ")");
                    redir("success=true");
                } else {
                    $errormessage = implode("<br>", $currentDetails->errors()->all());
                }
            }
        }
    } else {
        $errormessage = $aInt->lang("administrators", "currentPassError");
    }
}
WHMCS\Session::release();
$result = select_query("tbladmins", "tbladmins.*,tbladminroles.name,tbladminroles.supportemails", array("tbladmins.id" => $aInt->getAdminID()), "", "", "", "tbladminroles ON tbladminroles.id=tbladmins.roleid");
$data = mysql_fetch_array($result);
$supportEmailsEnabled = (bool) $data["supportemails"];
if (!$errormessage) {
    $firstname = $data["firstname"];
    $lastname = $data["lastname"];
    $email = $data["email"];
    $signature = $data["signature"];
    $notes = $data["notes"];
    $template = $data["template"];
    $language = $data["language"];
    $ticketnotifications = $data["ticketnotifications"];
    $ticketnotify = explode(",", $ticketnotifications);
} else {
    if (!is_array($ticketnotify)) {
        $ticketnotify = array();
    }
}
$username = $data["username"];
$adminrole = $data["name"];
$language = WHMCS\Language\AdminLanguage::getValidLanguageName($language);
ob_start();
$infobox = "";
if (defined("DEMO_MODE")) {
    infoBox("Demo Mode", "Actions on this page are unavailable while in demo mode. Changes will not be saved.");
}
if ($whmcs->get_req_var("success")) {
    infoBox($aInt->lang("administrators", "changesuccess"), $aInt->lang("administrators", "changesuccessinfo2"));
}
if (!empty($errormessage)) {
    infoBox($aInt->lang("global", "validationerror"), $errormessage, "error");
}
echo $infobox;
echo "\n<form method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
echo "?action=save\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"20%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "username");
echo "</td><td class=\"fieldarea\"><b>";
echo $username;
echo "</b></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("administrators", "role");
echo "</td><td class=\"fieldarea\"><strong>";
echo $adminrole;
echo "</strong></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "firstname");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"firstname\" class=\"form-control input-250\" value=\"";
echo $firstname;
echo "\"></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "lastname");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"lastname\" class=\"form-control input-250\" value=\"";
echo $lastname;
echo "\"></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "email");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"email\" class=\"form-control input-400\" value=\"";
echo $email;
echo "\"></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("administrators", "ticketnotifications");
echo "</td><td class=\"fieldarea\">\n";
if (!$supportEmailsEnabled) {
    echo "<div class=\"alert alert-warning top-margin-10 bottom-margin-10\"><i class=\"fas fa-exclamation-triangle\"></i> &nbsp; " . $aInt->lang("administrators", "ticketNotificationsUnavailable") . "</div>";
}
echo "<div class=\"row\">\n    <div class=\"col-sm-10 col-sm-offset-1\">\n        <div class=\"row\">";
$nodepartments = true;
$supportdepts = getAdminDepartmentAssignments();
foreach ($supportdepts as $deptid) {
    $deptname = get_query_val("tblticketdepartments", "name", array("id" => $deptid));
    if ($deptname) {
        echo "<div class=\"col-sm-6\">\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"ticketnotify[]\" value=\"" . $deptid . "\"" . (in_array($deptid, $ticketnotify) ? " checked" : "") . ($supportEmailsEnabled ? "" : " disabled") . " />\n                " . $deptname . "\n            </label>\n        </div>";
        $nodepartments = false;
    }
}
if ($nodepartments) {
    echo "<div class=\"col-xs-12\">" . $aInt->lang("administrators", "nosupportdeptsassigned") . "</div>";
}
echo "</div>\n    </div>\n</div></div>\n</td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("administrators", "supportsig");
echo "</td><td class=\"fieldarea\"><textarea name=\"signature\" rows=\"4\" class=\"form-control\">";
echo $signature;
echo "</textarea></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("global", "mynotes");
echo "</td><td class=\"fieldarea\"><textarea name=\"notes\" rows=\"4\" class=\"form-control\">";
echo $notes;
echo "</textarea></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "template");
echo "</td><td class=\"fieldarea\"><select name=\"template\" class=\"form-control select-inline\">";
foreach ($adminTemplates as $temp) {
    echo "<option value=\"" . $temp . "\"";
    if ($temp == $template) {
        echo " selected";
    }
    echo ">" . ucfirst($temp) . "</option>";
}
echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("global", "language");
echo "</td><td class=\"fieldarea\"><select name=\"language\" class=\"form-control select-inline\">";
foreach (WHMCS\Language\AdminLanguage::getLanguages() as $lang) {
    echo "<option value=\"" . $lang . "\"";
    if ($lang == $language) {
        echo " selected=\"selected\"";
    }
    echo ">" . ucfirst($lang) . "</option>";
}
echo "</select></td></tr>\n";
if ($twofa->isActiveAdmins()) {
    echo "<tr>\n    <td class=\"fieldlabel\">" . $aInt->lang("twofa", "title") . "</td>\n    <td class=\"fieldarea\">\n        <input type=\"checkbox\"" . ($twofa->isEnabled() ? " checked" : "") . " class=\"twofa-toggle-switch\" /> &nbsp;";
    echo "<a href=\"" . routePath("admin-account-security-two-factor-disable") . "\" class=\"open-modal twofa-config-link disable" . ($twofa->isEnabled() ? "" : " hidden") . "\" data-modal-title=\"" . $aInt->lang("twofa", "disable", 1) . "\" data-modal-class=\"twofa-setup\">" . $aInt->lang("twofa", "disableclickhere") . "</a>";
    echo "<a href=\"" . routePath("admin-account-security-two-factor-enable") . "\" class=\"open-modal twofa-config-link enable" . ($twofa->isEnabled() ? " hidden" : "") . "\" data-modal-title=\"" . $aInt->lang("twofa", "enable", 1) . "\" data-modal-class=\"twofa-setup\">" . $aInt->lang("twofa", "enableclickhere") . "</a>";
    echo "</td>\n</tr>";
}
echo "</table>\n\n<p>";
echo $aInt->lang("administrators", "entertochange");
echo "</p>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"20%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "password");
echo "</td><td class=\"fieldarea\"><input type=\"password\" name=\"password\" class=\"form-control input-250\" autocomplete=\"off\"></td></tr>\n<tr><td class=\"fieldlabel\" >";
echo $aInt->lang("fields", "confpassword");
echo "</td><td class=\"fieldarea\"><input type=\"password\" name=\"password2\" class=\"form-control input-250\" autocomplete=\"off\"></td></tr>\n</table>\n\n<p>\n    ";
echo $aInt->lang("administrators", "confirmAdminPasswd");
echo "</p>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n    <tr>\n        <td width=\"20%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "confpassword");
echo "</td>\n        <td class=\"fieldarea\">\n            <input type=\"password\" name=\"currentPasswd\" class=\"form-control input-250\" autocomplete=\"off\" required>\n        </td>\n    </tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
echo $aInt->lang("global", "savechanges");
echo "\" class=\"btn btn-primary\">\n    <input type=\"reset\" value=\"";
echo $aInt->lang("global", "cancelchanges");
echo "\" class=\"btn btn-default\" />\n</div>\n\n</form>\n\n";
$aInt->jquerycode = "\njQuery(\".twofa-toggle-switch\").bootstrapSwitch(\n    {\n        \"size\": \"mini\",\n        \"onColor\": \"success\",\n        \"onSwitchChange\": function(event, state)\n        {\n            \$(\".twofa-config-link:visible\").click();\n        }\n    }\n);";
if ($whmcs->get_req_var("2faenforce")) {
    $aInt->jquerycode .= "\$(\".twofa-config-link.enable\").attr(\"href\", \"" . routePathWithQuery("admin-account-security-two-factor-enable", array(), array("enforce" => true)) . "\").click();";
}
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->display();

?>