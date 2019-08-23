<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Configure Administrators", false);
$aInt->title = $aInt->lang("administrators", "title");
$aInt->sidebar = "config";
$aInt->icon = "admins";
$aInt->helplink = "Administrators";
$aInt->requireAuthConfirmation();
$validate = new WHMCS\Validate();
$file = new WHMCS\File\Directory($whmcs->get_admin_folder_name() . DIRECTORY_SEPARATOR . "templates");
$adminTemplates = $file->getSubdirectories();
$adminRolesResult = WHMCS\Database\Capsule::table("tbladminroles")->get(array("id", "name"));
$adminRoles = array();
foreach ($adminRolesResult as $adminRoleResult) {
    $adminRoles[$adminRoleResult->id] = $adminRoleResult->name;
}
if ($action == "save") {
    check_token("WHMCS.admin.default");
    if (defined("DEMO_MODE")) {
        redir("demo=1");
    }
    $id = (int) App::getFromRequest("id");
    $email = App::getFromRequest("email");
    $username = App::getFromRequest("username");
    $userProvidedPassword = $whmcs->get_req_var("password");
    $email = trim($email);
    $username = trim($username);
    $userProvidedPassword = trim($userProvidedPassword);
    $validate->validate("required", "firstname", array("administrators", "namerequired"));
    if ($validate->validate("required", "email", array("administrators", "emailerror")) && $validate->validate("email", "email", array("administrators", "emailinvalid")) && WHMCS\Database\Capsule::table("tblticketdepartments")->where("email", "=", $email)->count()) {
        $validate->addError(array("administrators", "emailCannotBeSupport"));
    }
    try {
        (new WHMCS\User\Admin())->validateUsername($username, $id);
    } catch (WHMCS\Exception\Validation\InvalidLength $e) {
        $validate->addError(array("administrators", "usernameLength"));
    } catch (WHMCS\Exception\Validation\InvalidFirstCharacter $e) {
        $validate->addError(array("administrators", "usernameFirstCharacterLetterRequired"));
    } catch (WHMCS\Exception\Validation\InvalidCharacters $e) {
        $validate->addError(array("administrators", "usernameCharacters"));
    } catch (WHMCS\Exception\Validation\DuplicateValue $e) {
        $validate->addError(array("administrators", "userexists"));
    }
    if (!$id && $validate->validate("required", "password", array("administrators", "pwerror"))) {
        $validate->validate("match_value", "password", array("administrators", "pwmatcherror"), "password2");
    }
    if ($validate->hasErrors()) {
        $action = "manage";
    } else {
        if (empty($deptids)) {
            $deptids = array();
        }
        if (empty($ticketnotify)) {
            $ticketnotify = array();
        }
        $supportdepts = implode(",", $deptids);
        $ticketnotify = implode(",", $ticketnotify);
        $disabled = $disabled == "on" ? 1 : 0;
        if (!in_array($template, $adminTemplates)) {
            $template = $adminTemplates[0];
        }
        $language = WHMCS\Language\AdminLanguage::getValidLanguageName($language);
        $adminDetails = array("roleid" => $roleid, "username" => $username, "firstname" => $firstname, "lastname" => $lastname, "email" => $email, "signature" => $signature, "disabled" => $disabled, "notes" => $notes, "template" => $template, "language" => $language, "supportdepts" => $supportdepts, "ticketnotifications" => $ticketnotify);
        if ($id) {
            $changes = array();
            $admin = WHMCS\User\Admin::find($id);
            if ($admin->roleId != $adminDetails["roleid"]) {
                $changes[] = "Role changed from '" . $adminRoles[$admin->roleId] . "'" . " to '" . $adminRoles[$adminDetails["roleid"]] . "'";
            }
            if ($admin->username != $adminDetails["username"]) {
                $changes[] = "Username changed from '" . $admin->username . "' to '" . $adminDetails["username"] . "'";
            }
            if ($admin->firstName != $adminDetails["firstname"]) {
                $changes[] = "First Name changed from '" . $admin->firstName . "' to '" . $adminDetails["firstname"] . "'";
            }
            if ($admin->lastName != $adminDetails["lastname"]) {
                $changes[] = "Last Name changed from '" . $admin->lastName . "' to '" . $adminDetails["lastname"] . "'";
            }
            if ($admin->email != $adminDetails["email"]) {
                $changes[] = "Email changed from '" . $admin->email . "' to '" . $adminDetails["email"] . "'";
            }
            if ($admin->disabled != $adminDetails["disabled"]) {
                if ($admin->disabled) {
                    $changes[] = "Admin User Enabled";
                } else {
                    $changes[] = "Admin User Disabled";
                }
            }
            if ($admin->signature != $adminDetails["signature"]) {
                $changes[] = "Signature changed";
            }
            if ($admin->notes != $adminDetails["notes"]) {
                $changes[] = "Notes changed";
            }
            if ($admin->template != $adminDetails["template"]) {
                $changes[] = "Template changed from '" . $admin->template . "' to '" . $adminDetails["template"] . "'";
            }
            if ($admin->language != $adminDetails["language"]) {
                $changes[] = "Language changed from '" . $admin->language . "' to '" . $adminDetails["language"] . "'";
            }
            $ticketDepartmentResults = WHMCS\Database\Capsule::table("tblticketdepartments")->get(array("id", "name"));
            $ticketDepartments = array();
            foreach ($ticketDepartmentResults as $ticketDepartmentResult) {
                $ticketDepartments[$ticketDepartmentResult->id] = $ticketDepartmentResult->name;
            }
            $newSupportDepartments = explode(",", $adminDetails["supportdepts"]);
            if ($admin->supportDepartmentIds != $newSupportDepartments) {
                $added = $removed = array();
                foreach ($newSupportDepartments as $newSupportDepartment) {
                    if (!in_array($newSupportDepartment, $admin->supportDepartmentIds)) {
                        $added[] = $ticketDepartments[$newSupportDepartment];
                    }
                }
                foreach ($admin->supportDepartmentIds as $existingSupportDepartment) {
                    if (!in_array($existingSupportDepartment, $newSupportDepartments)) {
                        $removed[] = $ticketDepartments[$existingSupportDepartment];
                    }
                }
                if (array_filter($added)) {
                    $changes[] = "Added Support Departments: " . implode(", ", $added);
                }
                if (array_filter($removed)) {
                    $changes[] = "Removed Support Departments: " . implode(", ", $removed);
                }
            }
            $newNotificationDepartments = explode(",", $adminDetails["ticketnotifications"]);
            if ($admin->receivesTicketNotifications != $newNotificationDepartments) {
                $added = $removed = array();
                foreach ($newNotificationDepartments as $newNotificationDepartment) {
                    if (!in_array($newNotificationDepartment, $admin->receivesTicketNotifications)) {
                        $added[] = $ticketDepartments[$newNotificationDepartment];
                    }
                }
                foreach ($admin->receivesTicketNotifications as $existingNotificationDepartment) {
                    if (!in_array($existingNotificationDepartment, $newNotificationDepartments)) {
                        $removed[] = $ticketDepartments[$existingNotificationDepartment];
                    }
                }
                if (array_filter($added)) {
                    $changes[] = "Added Support Departments Notification: " . implode(", ", $added);
                }
                if (array_filter($removed)) {
                    $changes[] = "Removed Support Departments Notification: " . implode(", ", $removed);
                }
            }
            $adminToUpdate = new WHMCS\Auth();
            $adminToUpdate->getInfobyID($id, NULL, false);
            if ($adminToUpdate->getAdminID() && $userProvidedPassword && ($userProvidedPassword = trim($userProvidedPassword))) {
                if ($adminToUpdate->generateNewPasswordHashAndStore($userProvidedPassword)) {
                    $adminToUpdate->generateNewPasswordHashAndStoreForApi(md5($userProvidedPassword));
                    if ($id == WHMCS\Session::get("adminid")) {
                        $adminToUpdate->setSessionVars();
                    }
                    $adminDetails["password_reset_key"] = "";
                    $adminDetails["password_reset_data"] = "";
                    $adminDetails["password_reset_expiry"] = "0000-00-00 00:00:00";
                    $changes[] = "Password Changed";
                } else {
                    logActivity(sprintf("Failed to update password hash for admin %s.", $adminDetails["username"]));
                }
            }
            $adminDetails["updated_at"] = WHMCS\Carbon::now()->toDateTimeString();
            $adminDetails["password_reset_key"] = "";
            $adminDetails["password_reset_data"] = "";
            $adminDetails["password_reset_expiry"] = "0000-00-00 00:00:00";
            update_query("tbladmins", $adminDetails, array("id" => $id));
            if ($changes) {
                logAdminActivity("Admin User '" . $adminDetails["username"] . "' modified. Changes: " . implode(". ", $changes));
            }
            redir("saved=true");
        } else {
            $adminDetails["password"] = phpseclib\Crypt\Random::string(21);
            $adminDetails["password_reset_data"] = "";
            $adminDetails["password_reset_key"] = $adminDetails["password_reset_data"];
            $adminDetails["password_reset_expiry"] = "0000-00-00 00:00:00";
            $adminDetails["updated_at"] = WHMCS\Carbon::now()->toDateTimeString();
            $adminDetails["created_at"] = $adminDetails["updated_at"];
            insert_query("tbladmins", $adminDetails);
            $newAdmin = new WHMCS\Auth();
            $newAdmin->getInfobyUsername($adminDetails["username"], NULL, false);
            $userProvidedPassword = trim($userProvidedPassword);
            if ($newAdmin->getAdminID() && $userProvidedPassword && $newAdmin->generateNewPasswordHashAndStore($userProvidedPassword)) {
                $newAdmin->generateNewPasswordHashAndStoreForApi(md5($userProvidedPassword));
            } else {
                logActivity(sprintf("Failed to assign password hash for new admin %s." . " Account will stay locked until properly reset.", $adminDetails["username"]));
            }
            WHMCS\Admin::dismissFeatureHighlightsUntilUpdateForAdmin($newAdmin->getAdminID());
            logAdminActivity("Admin User '" . $adminDetails["username"] . "' with role " . $adminRoles[$adminDetails["roleid"]] . " created");
            redir("added=true");
        }
        exit;
    }
}
if ($action == "delete") {
    check_token("WHMCS.admin.default");
    if (defined("DEMO_MODE")) {
        redir("demo=1");
    }
    $id = (int) $whmcs->get_req_var("id");
    $adminName = WHMCS\User\Admin::find($id)->username;
    delete_query("tbladmins", array("id" => $id));
    logAdminActivity("Admin User '" . $adminName . "' deleted");
    redir("deleted=true");
}
ob_start();
if ($action == "") {
    $infobox = "";
    if (defined("DEMO_MODE")) {
        infoBox("Demo Mode", "Actions on this page are unavailable while in demo mode. Changes will not be saved.");
    }
    if ($saved) {
        infoBox($aInt->lang("administrators", "changesuccess"), $aInt->lang("administrators", "changesuccessinfo"));
    } else {
        if ($added) {
            infoBox($aInt->lang("administrators", "addsuccess"), $aInt->lang("administrators", "addsuccessinfo"));
        } else {
            if ($deleted) {
                infoBox($aInt->lang("administrators", "deletesuccess"), $aInt->lang("administrators", "deletesuccessinfo"));
            }
        }
    }
    echo $infobox;
    $data = get_query_vals("tbladmins", "COUNT(id),id", array("roleid" => "1"));
    $numrows = $data[0];
    $onlyadminid = $numrows == "1" ? $data["id"] : 0;
    $jscode = "function doDelete(id) {\n    if(id != " . $onlyadminid . "){\n        if (confirm(\"" . $aInt->lang("administrators", "deletesure", 1) . "\")) {\n        window.location='" . $_SERVER["PHP_SELF"] . "?action=delete&id='+id+'" . generate_token("link") . "';\n        }\n    } else alert(\"" . $aInt->lang("administrators", "deleteonlyadmin", 1) . "\");\n    }";
    echo "<p>";
    echo $aInt->lang("administrators", "description");
    echo "</p>\n\n<p><a href=\"configadmins.php?action=manage\" class=\"btn btn-default\"><i class=\"fas fa-user-plus\"></i> ";
    echo $aInt->lang("administrators", "addnew");
    echo "</a></p>\n\n";
    echo "<h2>" . $aInt->lang("administrators", "active") . " </h2>";
    $aInt->sortableTableInit("nopagination");
    $result = select_query("tbladmins", "", array("disabled" => "0"), "firstname` ASC,`lastname", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $departments = $deptnames = array();
        $supportdepts = db_build_in_array(explode(",", $data["supportdepts"]));
        if ($supportdepts) {
            $resultdeptids = select_query("tblticketdepartments", "name", "id IN (" . $supportdepts . ")");
            while ($data_resultdeptids = mysql_fetch_array($resultdeptids)) {
                $deptnames[] = $data_resultdeptids[0];
            }
        }
        if (!count($deptnames)) {
            $deptnames[] = $aInt->lang("global", "none");
        }
        $tabledata[] = array($data["firstname"] . " " . $data["lastname"], "<a href=\"mailto:" . $data["email"] . "\">" . $data["email"] . "</a>", $data["username"], $adminRoles[$data["roleid"]], implode(", ", $deptnames), "<a href=\"?action=manage&id=" . $data["id"] . "\"><img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"Edit\"></a>", "<a href=\"#\" onClick=\"doDelete('" . $data["id"] . "')\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"Delete\"></a>");
    }
    echo $aInt->sortableTable(array($aInt->lang("fields", "name"), $aInt->lang("fields", "email"), $aInt->lang("fields", "username"), $aInt->lang("administrators", "adminrole"), $aInt->lang("administrators", "assigneddepts"), "", ""), $tabledata);
    echo "<h2>" . $aInt->lang("administrators", "inactive") . " </h2>";
    $tabledata = array();
    $result = select_query("tbladmins", "", array("disabled" => "1"), "firstname` ASC,`lastname", "ASC");
    $spacesInUsernames = false;
    while ($data = mysql_fetch_array($result)) {
        $departments = $deptnames = array();
        $supportdepts = db_build_in_array(explode(",", $data["supportdepts"]));
        if ($supportdepts) {
            $resultdeptids = select_query("tblticketdepartments", "name", "id IN (" . $supportdepts . ")");
            while ($data_resultdeptids = mysql_fetch_array($resultdeptids)) {
                $deptnames[] = $data_resultdeptids[0];
            }
        }
        if (!count($deptnames)) {
            $deptnames[] = $aInt->lang("global", "none");
        }
        if (!$spacesInUsernames && strpos($data["username"], " ") !== false) {
            $spacesInUsernames = true;
        }
        $tabledata[] = array($data["firstname"] . " " . $data["lastname"], "<a href=\"mailto:" . $data["email"] . "\">" . $data["email"] . "</a>", $data["username"], $adminRoles[$data["roleid"]], implode(", ", $deptnames), "<a href=\"?action=manage&id=" . $data["id"] . "\"><img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"Edit\"></a>", "<a href=\"#\" onClick=\"doDelete('" . $data["id"] . "')\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"Delete\"></a>");
    }
    WHMCS\Config\Setting::setValue("AdminUserNamesWithSpaces", $spacesInUsernames);
    echo $aInt->sortableTable(array($aInt->lang("fields", "name"), $aInt->lang("fields", "email"), $aInt->lang("fields", "username"), $aInt->lang("administrators", "adminrole"), $aInt->lang("administrators", "assigneddepts"), "", ""), $tabledata);
} else {
    if ($action == "manage") {
        if ($id) {
            $result = select_query("tbladmins", "", array("id" => $id));
            $data = mysql_fetch_array($result);
            $supportdepts = $data["supportdepts"];
            $ticketnotifications = $data["ticketnotifications"];
            $supportdepts = explode(",", $supportdepts);
            $ticketnotify = explode(",", $ticketnotifications);
            if (!$validate->hasErrors()) {
                $roleid = $data["roleid"];
                $firstname = $data["firstname"];
                $lastname = $data["lastname"];
                $email = $data["email"];
                $username = $data["username"];
                $signature = $data["signature"];
                $notes = $data["notes"];
                $template = $data["template"];
                $language = $data["language"];
                $disabled = $data["disabled"];
            }
            $numrows = get_query_vals("tbladmins", "COUNT(id)", array("roleid" => "1"));
            $onlyadmin = $numrows == "1" && $roleid == "1" ? true : false;
            $managetitle = $aInt->lang("administrators", "editadmin");
        } else {
            $supportdepts = $ticketnotify = array();
            $managetitle = $aInt->lang("administrators", "addadmin");
        }
        $language = WHMCS\Language\AdminLanguage::getValidLanguageName($language);
        $infobox = "";
        if (defined("DEMO_MODE")) {
            infoBox("Demo Mode", "Actions on this page are unavailable while in demo mode. Changes will not be saved.");
        }
        echo $infobox;
        echo "<p><b>" . $managetitle . "</b></p>";
        if ($validate->hasErrors()) {
            infoBox($aInt->lang("global", "validationerror"), $validate->getHTMLErrorOutput(), "error");
            echo $infobox;
        }
        echo "\n<form method=\"post\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "?action=save&id=";
        echo $id;
        echo "\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"20%\" class=\"fieldlabel\">";
        echo $aInt->lang("administrators", "role");
        echo "</td><td class=\"fieldarea\"><select name=\"roleid\" class=\"form-control select-inline\"";
        if ($onlyadmin) {
            echo " disabled";
        }
        echo ">";
        foreach ($adminRoles as $adminRoleId => $adminRoleName) {
            echo "<option value=\"" . $adminRoleId . "\"";
            if ($roleid == $adminRoleId) {
                echo " selected";
            }
            echo ">" . $adminRoleName . "</option>";
        }
        echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "firstname");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"firstname\" value=\"";
        echo $firstname;
        echo "\" class=\"form-control input-300\"></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "lastname");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"lastname\" value=\"";
        echo $lastname;
        echo "\" class=\"form-control input-300\"></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "email");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"email\" value=\"";
        echo $email;
        echo "\" class=\"form-control input-400\"></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "username");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"username\" autocomplete=\"off\" value=\"";
        echo $username;
        echo "\" class=\"form-control input-250\"></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "password");
        echo "</td><td class=\"fieldarea\"><input type=\"password\" name=\"password\" autocomplete=\"off\" class=\"form-control input-250\">";
        if ($id) {
            echo " (" . $aInt->lang("administrators", "entertochange") . ")";
        }
        echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "confpassword");
        echo "</td><td class=\"fieldarea\"><input type=\"password\" name=\"password2\" autocomplete=\"off\" class=\"form-control input-250\"></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("administrators", "assigneddepts");
        echo "</td><td class=\"fieldarea\">\n<div class=\"row\">\n";
        $nodepartments = true;
        $result = select_query("tblticketdepartments", "", "", "order", "ASC");
        while ($data = mysql_fetch_array($result)) {
            $deptid = $data["id"];
            $deptname = $data["name"];
            echo "<div class=\"col-md-6\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"deptids[]\" value=\"" . $deptid . "\"";
            if (in_array($deptid, $supportdepts)) {
                echo " checked";
            }
            echo "> " . $deptname . "</label> <label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"ticketnotify[]\" value=\"" . $deptid . "\"";
            if (in_array($deptid, $ticketnotify)) {
                echo " checked";
            }
            echo "> Enable Ticket Notifications</label></div>";
            $nodepartments = false;
        }
        if ($nodepartments) {
            echo "<div class=\"col-xs-12\">" . $aInt->lang("administrators", "nosupportdepts") . "</div>";
        }
        echo "</div>\n</td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("administrators", "supportsig");
        echo "</td><td class=\"fieldarea\"><textarea name=\"signature\" class=\"form-control\" rows=\"4\">";
        echo $signature;
        echo "</textarea></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("administrators", "privatenotes");
        echo "</td><td class=\"fieldarea\"><textarea name=\"notes\" class=\"form-control\" rows=\"4\">";
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
        echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "disable");
        echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"disabled\"";
        if ($disabled == 1) {
            echo " checked";
        }
        if ($onlyadmin || $id == $_SESSION["adminid"]) {
            echo " disabled";
        }
        echo " /> ";
        echo $aInt->lang("administrators", "disableinfo");
        echo "</label></td></tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
        echo $aInt->lang("global", "savechanges");
        echo "\" class=\"btn btn-primary\">\n    <input type=\"button\" value=\"";
        echo $aInt->lang("global", "cancelchanges");
        echo "\" class=\"btn btn-default\" onclick=\"window.location='configadmins.php'\" />\n</div>\n\n</form>\n\n";
    }
}
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jscode = $jscode;
$aInt->display();

?>