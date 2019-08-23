<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Configure Support Departments");
$aInt->title = $aInt->lang("supportticketdepts", "supportticketdeptstitle");
$aInt->sidebar = "config";
$aInt->icon = "logs";
$aInt->helplink = "Support Departments";
$aInt->requireAuthConfirmation();
$sub = $whmcs->get_req_var("sub");
$id = (int) $whmcs->get_req_var("id");
$email = $whmcs->get_req_var("email");
$name = $whmcs->get_req_var("name");
$description = $whmcs->get_req_var("description");
$clientsonly = $whmcs->get_req_var("clientsonly");
$piperepliesonly = $whmcs->get_req_var("piperepliesonly");
$noautoresponder = $whmcs->get_req_var("noautoresponder");
$hidden = $whmcs->get_req_var("hidden");
$host = $whmcs->get_req_var("host");
$port = (int) $whmcs->get_req_var("port");
$login = $whmcs->get_req_var("login");
$password = $whmcs->get_req_var("password");
$admins = $whmcs->get_req_var("admins") ?: array();
$feedbackRequest = (int) (bool) App::getFromRequest("feedbackrequest");
if ($sub == "add") {
    check_token("WHMCS.admin.default");
    if ($email == "") {
        infoBox(AdminLang::trans("global.validationerror"), AdminLang::trans("supportticketdepts.emailreqdfordept"));
        $action = "add";
    }
    if ($name == "") {
        infoBox(AdminLang::trans("global.validationerror"), AdminLang::trans("supportticketdepts.namereqdfordept"));
        $action = "add";
    }
    if (0 < WHMCS\User\Admin::whereEmail($email)->whereDisabled(0)->count()) {
        infoBox(AdminLang::trans("global.validationerror"), AdminLang::trans("supportticketdepts.emailCannotBeAdmin"));
        $action = "add";
    }
    if (!$infobox) {
        $result = select_query("tblticketdepartments", "", "", "order", "DESC");
        $data = mysql_fetch_array($result);
        $order = $data["order"];
        $order++;
        $id = insert_query("tblticketdepartments", array("name" => $name, "description" => WHMCS\Input\Sanitize::decode($description), "email" => trim($email), "clientsonly" => $clientsonly, "piperepliesonly" => $piperepliesonly, "noautoresponder" => $noautoresponder, "hidden" => $hidden, "order" => $order, "host" => trim($host), "port" => trim($port), "login" => trim($login), "password" => encrypt(trim(WHMCS\Input\Sanitize::decode($password))), "feedback_request" => $feedbackRequest));
        if (WHMCS\Config\Setting::getValue("EnableTranslations")) {
            WHMCS\Language\DynamicTranslation::saveNewTranslations($id, array("ticket_department.{id}.name", "ticket_department.{id}.description"));
        }
        $result = select_query("tbladmins", "id,supportdepts", array("disabled" => "0"));
        while ($data = mysql_fetch_array($result)) {
            list($deptadminid, $supportdepts) = $data;
            $supportdepts = explode(",", $supportdepts);
            if (in_array($deptadminid, $admins)) {
                if (!in_array($id, $supportdepts)) {
                    $supportdepts[] = $id;
                }
            } else {
                if (in_array($id, $supportdepts)) {
                    $supportdepts = array_diff($supportdepts, array($id));
                }
            }
            update_query("tbladmins", array("supportdepts" => implode(",", $supportdepts)), array("id" => $deptadminid));
        }
        logAdminActivity("Support Department Created: '" . $name . "' - Support Department ID: " . $id);
        redir("createsuccess=1");
    }
}
if ($sub == "save") {
    check_token("WHMCS.admin.default");
    if ($email == "") {
        infoBox(AdminLang::trans("global.validationerror"), AdminLang::trans("supportticketdepts.emailreqdfordept"));
        $action = "edit";
    }
    if ($name == "") {
        infoBox(AdminLang::trans("global.validationerror"), AdminLang::trans("supportticketdepts.namereqdfordept"));
        $action = "edit";
    }
    if (0 < WHMCS\User\Admin::whereEmail($email)->whereDisabled(0)->count()) {
        infoBox(AdminLang::trans("global.validationerror"), AdminLang::trans("supportticketdepts.emailCannotBeAdmin"));
        $action = "edit";
    }
    if (!$infobox) {
        $supportDepartment = WHMCS\Database\Capsule::table("tblticketdepartments")->find($id);
        $saveData = array("name" => $name, "description" => WHMCS\Input\Sanitize::decode($description), "email" => trim($email), "clientsonly" => $clientsonly, "piperepliesonly" => $piperepliesonly, "noautoresponder" => $noautoresponder, "hidden" => $hidden, "host" => trim($host), "port" => trim($port), "login" => trim($login), "feedback_request" => $feedbackRequest);
        $changes = false;
        foreach ($saveData as $save => $data) {
            if ($save == "name") {
                if ($supportDepartment->{$save} != $data) {
                    logAdminActivity("Support Department Modified: " . "Name Changed: '" . $supportDepartment->{$save} . "' to '" . $data . "' - Support Department ID: " . $id);
                }
                continue;
            }
            if (!$changes && $supportDepartment->{$save} != $data) {
                $changes = true;
                break;
            }
        }
        $newPassword = trim($whmcs->get_req_var("password"));
        $originalPassword = decrypt(get_query_val("tblticketdepartments", "password", array("id" => $id)));
        $valueToStore = interpretMaskedPasswordChangeForStorage($newPassword, $originalPassword);
        if ($valueToStore !== false) {
            $saveData["password"] = $valueToStore;
            if ($newPassword != $originalPassword) {
                $changes = true;
            }
        }
        if ($changes) {
            logAdminActivity("Support Department Modified: '" . $name . "' - Configuration Modified - Support Department ID: " . $id);
        }
        update_query("tblticketdepartments", $saveData, array("id" => $id));
        $result = select_query("tbladmins", "id,supportdepts", "");
        while ($data = mysql_fetch_array($result)) {
            list($deptadminid, $supportdepts) = $data;
            $supportdepts = explode(",", $supportdepts);
            if (in_array($deptadminid, $admins)) {
                if (!in_array($id, $supportdepts)) {
                    $supportdepts[] = $id;
                }
            } else {
                if (in_array($id, $supportdepts)) {
                    $supportdepts = array_diff($supportdepts, array($id));
                }
            }
            update_query("tbladmins", array("supportdepts" => implode(",", $supportdepts)), array("id" => $deptadminid));
        }
        $customfieldname = $whmcs->get_req_var("customfieldname") ?: array();
        $customfieldtype = $whmcs->get_req_var("customfieldtype") ?: array();
        $customfielddesc = $whmcs->get_req_var("customfielddesc") ?: array();
        $customfieldoptions = $whmcs->get_req_var("customfieldoptions") ?: array();
        $customfieldregexpr = $whmcs->get_req_var("customfieldregexpr") ?: array();
        $customadminonly = $whmcs->get_req_var("customadminonly") ?: array();
        $customrequired = $whmcs->get_req_var("customrequired") ?: array();
        $customshoworder = $whmcs->get_req_var("customshoworder") ?: array();
        $customsortorder = $whmcs->get_req_var("customsortorder") ?: array();
        if ($customfieldname) {
            foreach ($customfieldname as $fid => $value) {
                $customField = WHMCS\Database\Capsule::table("tblcustomfields")->find($fid);
                if ($customField->fieldname != $value) {
                    logAdminActivity("Support Department Modified: " . "Custom Field Modified: Name Changed: '" . $customField->fieldname . "' to '" . $value . "'" . " - Support Department ID: " . $id);
                }
                if ($customField->fieldtype != $customfieldtype[$fid] || $customField->description != $customfielddesc[$fid] || $customField->fieldoptions != $customfieldoptions[$fid] || $customField->regexpr != $customfieldregexpr[$fid] || $customField->adminonly != $customadminonly[$fid] || $customField->required != $customrequired[$fid] || $customField->showorder != $customshoworder[$fid] || $customField->sortorder != $customsortorder[$fid]) {
                    logAdminActivity("Support Department Modified: Custom Field Modified: '" . $value . "' - Support Department ID: " . $id);
                }
                update_query("tblcustomfields", array("fieldname" => $value, "fieldtype" => $customfieldtype[$fid], "description" => $customfielddesc[$fid], "fieldoptions" => $customfieldoptions[$fid], "regexpr" => WHMCS\Input\Sanitize::decode($customfieldregexpr[$fid]), "adminonly" => $customadminonly[$fid], "required" => $customrequired[$fid], "showorder" => $customshoworder[$fid], "sortorder" => $customsortorder[$fid]), array("id" => $fid));
            }
        }
        $addfieldname = $whmcs->get_req_var("addfieldname");
        $addfieldtype = $whmcs->get_req_var("addfieldtype");
        $addcfdesc = $whmcs->get_req_var("addcfdesc");
        $addfieldoptions = $whmcs->get_req_var("addfieldoptions");
        $addregexpr = $whmcs->get_req_var("addregexpr");
        $addadminonly = $whmcs->get_req_var("addadminonly");
        $addrequired = $whmcs->get_req_var("addrequired");
        $addshoworder = $whmcs->get_req_var("addshoworder");
        $addsortorder = (int) $whmcs->get_req_var("addsortorder");
        if ($addfieldname) {
            $id = insert_query("tblcustomfields", array("type" => "support", "relid" => $id, "fieldname" => $addfieldname, "fieldtype" => $addfieldtype, "description" => $addcfdesc, "fieldoptions" => $addfieldoptions, "regexpr" => WHMCS\Input\Sanitize::decode($addregexpr), "adminonly" => $addadminonly, "required" => $addrequired, "showorder" => $addshoworder, "sortorder" => $addsortorder));
            if (WHMCS\Config\Setting::getValue("EnableTranslations")) {
                WHMCS\Language\DynamicTranslation::saveNewTranslations($id, array("custom_field.{id}.name", "custom_field.{id}.description"));
            }
            logAdminActivity("Support Department Modified: '" . $name . "'" . " - Custom Field Created: '" . $addfieldname . "' - Support Department ID: " . $id);
        }
        redir("savesuccess=1");
    }
}
if ($sub == "delete") {
    check_token("WHMCS.admin.default");
    $result = select_query("tblticketdepartments", "", array("id" => $id));
    $data = mysql_fetch_array($result);
    $order = $data["order"];
    $departmentName = $data["name"];
    update_query("tblticketdepartments", array("order" => "-1"), array("`order`" => $order));
    try {
        WHMCS\Support\Department::findOrFail($id)->delete();
    } catch (Exception $e) {
    }
    logAdminActivity("Support Department Deleted: '" . $departmentName . "' - Support Department ID: " . $id);
    $result = select_query("tblticketdepartments", "min(id) as id", array());
    $data = mysql_fetch_array($result);
    $newdeptid = $data["id"];
    update_query("tbltickets", array("did" => $newdeptid), array("did" => $id));
    WHMCS\CustomField::where("type", "=", "support")->where("relid", "=", $id)->delete();
    redir("delsuccess=1");
}
if ($sub == "deletecustomfield") {
    check_token("WHMCS.admin.default");
    $customField = WHMCS\CustomField::where("type", "=", "support")->where("id", "=", $id)->first();
    if ($customField) {
        $supportDepartment = WHMCS\Database\Capsule::table("tblticketdepartments")->find($customField->relatedId);
        logAdminActivity("Support Department Modified: '" . $supportDepartment->name . "'" . " - Custom Field Deleted: '" . $customField->fieldName . "'" . " - Support Department ID: " . $supportDepartment->id);
        $customField->delete();
    }
    redir("savesuccess=1");
}
if ($sub == "moveup") {
    check_token("WHMCS.admin.default");
    $order = (int) $whmcs->get_req_var("order");
    $result = select_query("tblticketdepartments", "", array("`order`" => $order));
    $data = mysql_fetch_array($result);
    $premid = $data["id"];
    $order1 = $order - 1;
    $otherDepartment = WHMCS\Database\Capsule::table("tblticketdepartments")->where("order", "=", $order1)->first();
    logAdminActivity("Support Department Modified: '" . $data["name"] . "' - Sort Order Increased - Support Department ID: " . $premid);
    logAdminActivity("Support Department Modified: '" . $otherDepartment->name . "'" . " - Sort Order Lowered - Support Department ID: " . $otherDepartment->id);
    update_query("tblticketdepartments", array("order" => $order), array("`order`" => $order1));
    update_query("tblticketdepartments", array("order" => $order1), array("id" => $premid));
    redir();
}
if ($sub == "movedown") {
    check_token("WHMCS.admin.default");
    $order = (int) $whmcs->get_req_var("order");
    $result = select_query("tblticketdepartments", "", array("`order`" => $order));
    $data = mysql_fetch_array($result);
    $premid = $data["id"];
    $order1 = $order + 1;
    $otherDepartment = WHMCS\Database\Capsule::table("tblticketdepartments")->where("order", "=", $order1)->first();
    logAdminActivity("Support Department Modified: '" . $data["name"] . "' - Sort Order Lowered - Support Department ID: " . $premid);
    logAdminActivity("Support Department Modified: '" . $otherDepartment->name . "'" . " - Sort Order Increased - Support Department ID: " . $otherDepartment->id);
    update_query("tblticketdepartments", array("order" => $order), array("`order`" => $order1));
    update_query("tblticketdepartments", array("order" => $order1), array("id" => $premid));
    redir();
}
if (WHMCS\Config\Setting::getValue("EnableTranslations")) {
    WHMCS\Language\DynamicTranslation::whereIn("related_type", array("custom_field.{id}.name", "custom_field.{id}.description"))->where("related_id", "=", 0)->delete();
}
ob_start();
if ($createsuccess) {
    infoBox($aInt->lang("supportticketdepts", "deptaddsuccess"), $aInt->lang("supportticketdepts", "deptaddsuccessdesc"));
}
if ($savesuccess) {
    infoBox($aInt->lang("supportticketdepts", "changessavesuccess"), $aInt->lang("supportticketdepts", "changessavesuccessdesc"));
}
if ($delsuccess) {
    infoBox($aInt->lang("global", "success"), "The selected support department was deleted successfully");
}
echo $infobox;
if ($action == "") {
    $aInt->deleteJSConfirm("doDelete", "supportticketdepts", "delsuredept", "?sub=delete&id=");
    $cronFolder = $whmcs->getCronDirectory();
    echo "\n<p>";
    echo $aInt->lang("supportticketdepts", "supportticketdeptsconfigheredesc");
    echo "</p>\n\n<div class=\"alert alert-warning text-center\">\n    <div class=\"input-group\">\n        <span class=\"input-group-addon\" id=\"emailPipe\">";
    echo $aInt->lang("supportticketdepts", "ticketimportusingef");
    echo "</span>\n        <input type=\"text\" id=\"emailPipe\" value=\" | ";
    echo WHMCS\Environment\Php::getPreferredCliBinary();
    echo " -q ";
    echo $cronFolder;
    echo "/pipe.php\" class=\"form-control\" onfocus=\"this.select()\" onmouseup=\"return false;\" />\n    </div>\n    <strong>";
    echo $aInt->lang("global", "or");
    echo "</strong><br />\n    <div class=\"input-group\">\n        <span class=\"input-group-addon\" id=\"emailPop\">";
    echo $aInt->lang("supportticketdepts", "ticketimportusingpop3imap");
    echo "</span>\n        <input type=\"text\" id=\"emailPop\" value=\"*/5 * * * * ";
    echo WHMCS\Environment\Php::getPreferredCliBinary();
    echo " -q ";
    echo $cronFolder;
    echo "/pop.php\" class=\"form-control\" onfocus=\"this.select()\" onmouseup=\"return false;\" />\n    </div>\n</div>\n\n<p><a href=\"";
    echo $whmcs->getPhpSelf();
    echo "?action=add\" class=\"btn btn-default\"><i class=\"fas fa-plus-square\"></i> ";
    echo $aInt->lang("supportticketdepts", "addnewdept");
    echo "</a></p>\n\n";
    $result = select_query("tblticketdepartments", "", "", "order", "DESC");
    $data = mysql_fetch_array($result);
    $lastorder = $data["order"];
    $aInt->sortableTableInit("nopagination");
    $result = select_query("tblticketdepartments", "", "", "order", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $id = $data["id"];
        $name = $data["name"];
        $description = $data["description"];
        $email = $data["email"];
        $hidden = $data["hidden"];
        $order = $data["order"];
        if ($hidden == "on") {
            $hidden = $aInt->lang("global", "yes");
        } else {
            $hidden = $aInt->lang("global", "no");
        }
        if ($order != "1") {
            $moveup = "<a href=\"?sub=moveup&order=" . $order . generate_token("link") . "\"><img src=\"images/moveup.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("products", "navmoveup") . "\"></a>";
        } else {
            $moveup = "";
        }
        if ($order != $lastorder) {
            $movedown = "<a href=\"?sub=movedown&order=" . $order . generate_token("link") . "\"><img src=\"images/movedown.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("products", "navmovedown") . "\"></a>";
        } else {
            $movedown = "";
        }
        $tabledata[] = array($name, $description, $email, $hidden, $moveup, $movedown, "<a href=\"?action=edit&id=" . $id . "\"><img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "edit") . "\"></a>", "<a href=\"#\" onClick=\"doDelete('" . $id . "');return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\"></a>");
    }
    echo $aInt->sortableTable(array($aInt->lang("supportticketdepts", "deptname"), $aInt->lang("fields", "description"), $aInt->lang("supportticketdepts", "deptemail"), $aInt->lang("global", "hidden"), "", "", "", ""), $tabledata);
} else {
    if ($action == "edit") {
        if (!$infobox) {
            $result = select_query("tblticketdepartments", "", array("id" => $id));
            $data = mysql_fetch_array($result);
            $name = $data["name"];
            $description = $data["description"];
            $email = $data["email"];
            $clientsonly = $data["clientsonly"];
            $piperepliesonly = $data["piperepliesonly"];
            $noautoresponder = $data["noautoresponder"];
            $hidden = $data["hidden"];
            $host = $data["host"];
            $port = $data["port"];
            $login = $data["login"];
            $password = decrypt($data["password"]);
            $feedbackRequest = $data["feedback_request"];
        }
        $aInt->deleteJSConfirm("deleteField", "supportticketdepts", "delsurefielddata", "?sub=deletecustomfield&id=");
        echo "\n<h2>";
        echo $aInt->lang("supportticketdepts", "editdept");
        echo "</h2>\n\n<form method=\"post\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "?sub=save\">\n<input type=\"hidden\" name=\"id\" value=\"";
        echo $id;
        echo "\">\n\n";
        echo $aInt->beginAdminTabs(array("Details", "Custom Fields"), true);
        echo "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr>\n    <td width=\"20%\" class=\"fieldlabel\">\n        ";
        echo $aInt->lang("supportticketdepts", "deptname");
        echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"name\" value=\"";
        echo $name;
        echo "\" class=\"form-control input-inline input-300\">\n        ";
        echo $aInt->getTranslationLink("ticket_department.name", $id);
        echo "    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
        echo $aInt->lang("fields", "description");
        echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"description\" value=\"";
        echo WHMCS\Input\Sanitize::encode($description);
        echo "\" class=\"form-control input-inline input-80percent\">\n        ";
        echo $aInt->getTranslationLink("ticket_department.description", $id);
        echo "    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("supportticketdepts", "deptemail");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"email\" value=\"";
        echo $email;
        echo "\" class=\"form-control input-500\"></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("supportticketdepts", "assignedadmins");
        echo "</td><td class=\"fieldarea\">\n";
        $result = select_query("tbladmins", "", "", "username", "ASC");
        while ($data = mysql_fetch_array($result)) {
            $supportdepts = $data["supportdepts"];
            $supportdepts = explode(",", $supportdepts);
            echo "<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"admins[]\" value=\"" . $data["id"] . "\"";
            if (in_array($id, $supportdepts)) {
                echo " checked";
            }
            echo " /> ";
            if ($data["disabled"] == 1) {
                echo "<span class=\"disabledtext\">";
            }
            echo $data["username"] . " (" . trim($data["firstname"] . " " . $data["lastname"]) . ")";
            if ($data["disabled"] == 1) {
                echo " - " . $aInt->lang("global", "disabled") . "</span>";
            }
            echo "</label><br />";
        }
        echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("supportticketdepts", "clientsonly");
        echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"clientsonly\"";
        if ($clientsonly == "on") {
            echo " checked";
        }
        echo "> ";
        echo $aInt->lang("supportticketdepts", "clientsonlydesc");
        echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("supportticketdepts", "piperepliesonly");
        echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"piperepliesonly\"";
        if ($piperepliesonly == "on") {
            echo " checked";
        }
        echo "> ";
        echo $aInt->lang("supportticketdepts", "ticketsclientareaonlydesc");
        echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("supportticketdepts", "noautoresponder");
        echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"noautoresponder\"";
        if ($noautoresponder == "on") {
            echo " checked";
        }
        echo "> ";
        echo $aInt->lang("supportticketdepts", "noautoresponderdesc");
        echo "</label></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
        echo AdminLang::trans("supportticketdepts.feedbackRequest");
        echo "    </td>\n    <td class=\"fieldarea\">\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"feedbackrequest\"";
        if ($feedbackRequest) {
            echo " checked";
        }
        echo "> ";
        echo AdminLang::trans("supportticketdepts.feedbackRequestDescription");
        echo "        </label>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("global", "hidden");
        echo "?</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"hidden\"";
        if ($hidden == "on") {
            echo " checked";
        }
        echo "> ";
        echo $aInt->lang("supportticketdepts", "hiddendesc");
        echo "</label></td></tr>\n</table>\n<p style=\"text-align:left;\"><b>";
        echo $aInt->lang("supportticketdepts", "pop3importconfigtitle");
        echo "</b> ";
        echo $aInt->lang("supportticketdepts", "pop3importconfigdesc");
        echo "</p>\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"20%\" class=\"fieldlabel\">";
        echo $aInt->lang("fields", "hostname");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"host\" value=\"";
        echo $host;
        echo "\" placeholder=\"mail.example.com\" class=\"form-control input-400\"></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("supportticketdepts", "pop3port");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"port\" value=\"";
        echo $port;
        echo "\" placeholder=\"995\" class=\"form-control input-100\"></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("supportticketdepts", "pop3user");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"login\" value=\"";
        echo $login;
        echo "\" class=\"form-control input-300\"></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("supportticketdepts", "pop3pass");
        echo "</td><td class=\"fieldarea\"><input type=\"password\" name=\"password\" value=\"";
        echo replacePasswordWithMasks($password);
        echo "\" autocomplete=\"off\" class=\"form-control input-300\"></td></tr>\n</table>\n\n";
        echo $aInt->nextAdminTab();
        echo "\n";
        $result = select_query("tblcustomfields", "", array("type" => "support", "relid" => $id), "`sortorder` ASC,`id`", "ASC");
        while ($data = mysql_fetch_array($result)) {
            $fid = $data["id"];
            $fieldname = $data["fieldname"];
            $fieldtype = $data["fieldtype"];
            $description = $data["description"];
            $fieldoptions = $data["fieldoptions"];
            $regexpr = $data["regexpr"];
            $adminonly = $data["adminonly"];
            $required = $data["required"];
            $sortorder = $data["sortorder"];
            echo "<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr>\n    <td width=100 class=\"fieldlabel\">\n        ";
            echo $aInt->lang("customfields", "fieldname");
            echo "    </td>\n    <td class=\"fieldarea\">\n        <table width=\"98%\" cellspacing=\"0\" cellpadding=\"0\">\n            <tr>\n                <td>\n                    <input type=\"text\" name=\"customfieldname[";
            echo $fid;
            echo "]\" value=\"";
            echo $fieldname;
            echo "\" class=\"form-control input-300 input-inline\">\n                    ";
            echo $aInt->getTranslationLink("custom_field.name", $fid, "support");
            echo "                </td>\n                <td align=\"right\">\n                    ";
            echo $aInt->lang("customfields", "order");
            echo "                    <input type=\"text\" name=\"customsortorder[";
            echo $fid;
            echo "]\" value=\"";
            echo $sortorder;
            echo "\" class=\"form-control input-50 input-inline\">\n                </td>\n            </tr>\n        </table>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("customfields", "fieldtype");
            echo "</td><td class=\"fieldarea\"><select name=\"customfieldtype[";
            echo $fid;
            echo "]\" class=\"form-control select-inline\">\n<option value=\"text\"";
            if ($fieldtype == "text") {
                echo " selected";
            }
            echo ">";
            echo $aInt->lang("customfields", "typetextbox");
            echo "</option>\n<option value=\"link\"";
            if ($fieldtype == "link") {
                echo " selected";
            }
            echo ">";
            echo $aInt->lang("customfields", "typelink");
            echo "</option>\n<option value=\"password\"";
            if ($fieldtype == "password") {
                echo " selected";
            }
            echo ">";
            echo $aInt->lang("customfields", "typepassword");
            echo "</option>\n<option value=\"dropdown\"";
            if ($fieldtype == "dropdown") {
                echo " selected";
            }
            echo ">";
            echo $aInt->lang("customfields", "typedropdown");
            echo "</option>\n<option value=\"tickbox\"";
            if ($fieldtype == "tickbox") {
                echo " selected";
            }
            echo ">";
            echo $aInt->lang("customfields", "typetickbox");
            echo "</option>\n<option value=\"textarea\"";
            if ($fieldtype == "textarea") {
                echo " selected";
            }
            echo ">";
            echo $aInt->lang("customfields", "typetextarea");
            echo "</option>\n</select></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
            echo $aInt->lang("fields", "description");
            echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"customfielddesc[";
            echo $fid;
            echo "]\" value=\"";
            echo $description;
            echo "\" class=\"form-control input-500 input-inline\">\n        ";
            echo $aInt->getTranslationLink("custom_field.description", $fid, "support");
            echo "        ";
            echo $aInt->lang("customfields", "descriptioninfo");
            echo "    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("customfields", "validation");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"customfieldregexpr[";
            echo $fid;
            echo "]\" value=\"";
            echo WHMCS\Input\Sanitize::encode($regexpr);
            echo "\" class=\"form-control input-500 input-inline\"> ";
            echo $aInt->lang("customfields", "validationinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("customfields", "selectoptions");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"customfieldoptions[";
            echo $fid;
            echo "]\" value=\"";
            echo $fieldoptions;
            echo "\" class=\"form-control input-500 input-inline\"> ";
            echo $aInt->lang("customfields", "selectoptionsinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\"></td><td class=\"fieldarea\"><table width=\"98%\" cellspacing=\"0\" cellpadding=\"0\"><tr><td><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"customadminonly[";
            echo $fid;
            echo "]\"";
            if ($adminonly == "on") {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("customfields", "adminonly");
            echo "</label> <label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"customrequired[";
            echo $fid;
            echo "]\"";
            if ($required == "on") {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("customfields", "requiredfield");
            echo "</label></td><td align=\"right\"><a href=\"#\" onClick=\"deleteField('";
            echo $fid;
            echo "');return false\">";
            echo $aInt->lang("customfields", "deletefield");
            echo "</a></td></tr></table></td></tr>\n</table><br>\n";
        }
        echo "<b>";
        echo $aInt->lang("customfields", "addfield");
        echo "</b><br><br>\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr>\n    <td width=100 class=\"fieldlabel\">\n        ";
        echo $aInt->lang("customfields", "fieldname");
        echo "    </td>\n    <td class=\"fieldarea\">\n        <table width=\"98%\" cellspacing=\"0\" cellpadding=\"0\">\n            <tr>\n                <td>\n                    <input type=\"text\" name=\"addfieldname\" class=\"form-control input-300 input-inline\">\n                    ";
        echo $aInt->getTranslationLink("custom_field.name", 0, "support");
        echo "                </td>\n                <td align=\"right\">\n                    ";
        echo $aInt->lang("customfields", "order");
        echo "                    <input type=\"text\" name=\"addsortorder\" class=\"form-control input-50 input-inline\" value=\"0\">\n                </td>\n            </tr>\n        </table>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("customfields", "fieldtype");
        echo "</td><td class=\"fieldarea\"><select name=\"addfieldtype\" class=\"form-control select-inline\">\n<option value=\"text\">";
        echo $aInt->lang("customfields", "typetextbox");
        echo "</option>\n<option value=\"link\">";
        echo $aInt->lang("customfields", "typelink");
        echo "</option>\n<option value=\"password\">";
        echo $aInt->lang("customfields", "typepassword");
        echo "</option>\n<option value=\"dropdown\">";
        echo $aInt->lang("customfields", "typedropdown");
        echo "</option>\n<option value=\"tickbox\">";
        echo $aInt->lang("customfields", "typetickbox");
        echo "</option>\n<option value=\"textarea\">";
        echo $aInt->lang("customfields", "typetextarea");
        echo "</option>\n</select></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
        echo $aInt->lang("fields", "description");
        echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"addcfdesc\" class=\"form-control input-500 input-inline\">\n        ";
        echo $aInt->getTranslationLink("custom_field.description", 0, "support");
        echo "        ";
        echo $aInt->lang("customfields", "descriptioninfo");
        echo "    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("customfields", "validation");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"addregexpr\" class=\"form-control input-500 input-inline\"> ";
        echo $aInt->lang("customfields", "validationinfo");
        echo "</td></tr>\n<tr><td class=\"fieldlabel\">Select Options</td><td class=\"fieldarea\"><input type=\"text\" name=\"addfieldoptions\" class=\"form-control input-500 input-inline\"> ";
        echo $aInt->lang("customfields", "selectoptionsinfo");
        echo "</td></tr>\n<tr><td class=\"fieldlabel\"></td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"addadminonly\"> ";
        echo $aInt->lang("customfields", "adminonly");
        echo "</label> <label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"addrequired\"> ";
        echo $aInt->lang("customfields", "requiredfield");
        echo "</label></td></tr>\n</table>\n\n";
        echo $aInt->endAdminTabs();
        echo "\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
        echo $aInt->lang("global", "savechanges");
        echo "\" class=\"btn btn-primary\">\n    <input type=\"button\" value=\"";
        echo $aInt->lang("global", "cancel");
        echo "\" onClick=\"window.location='";
        echo $whmcs->getPhpSelf();
        echo "'\" class=\"btn btn-default\">\n</div>\n\n</form>\n\n";
    }
}
if ($action == "add") {
    if ($port == "") {
        $port = "110";
    }
    echo "\n<h2>";
    echo $aInt->lang("supportticketdepts", "addnewdept");
    echo "</h2>\n\n<form method=\"post\" action=\"";
    echo $whmcs->getPhpSelf();
    echo "?sub=add\" autocomplete=\"off\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr>\n    <td width=\"20%\" class=\"fieldlabel\">";
    echo $aInt->lang("supportticketdepts", "deptname");
    echo "</td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"name\" value=\"";
    echo $name;
    echo "\" class=\"form-control input-300 input-inline\">\n        ";
    echo $aInt->getTranslationLink("ticket_department.name", 0);
    echo "    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
    echo $aInt->lang("fields", "description");
    echo "</td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"description\" value=\"";
    echo $description;
    echo "\" class=\"form-control input-80percent input-inline\">\n        ";
    echo $aInt->getTranslationLink("ticket_department.description", 0);
    echo "    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
    echo $aInt->lang("supportticketdepts", "deptemail");
    echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"email\" value=\"";
    echo $email;
    echo "\" class=\"form-control input-500\"></td></tr>\n<tr><td class=\"fieldlabel\">";
    echo $aInt->lang("supportticketdepts", "assignedadmins");
    echo "</td><td class=\"fieldarea\">\n";
    $result = select_query("tbladmins", "", "", "username", "ASC");
    while ($data = mysql_fetch_array($result)) {
        echo "<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"admins[]\" value=\"" . $data["id"] . "\"";
        echo " /> ";
        if ($data["disabled"] == 1) {
            echo "<span class=\"disabledtext\">";
        }
        echo $data["username"] . " (" . $data["firstname"] . " " . $data["lastname"] . ")";
        if ($data["disabled"] == 1) {
            echo " - " . $aInt->lang("global", "disabled") . "</span>";
        }
        echo "</label><br />";
    }
    echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
    echo $aInt->lang("supportticketdepts", "clientsonly");
    echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"clientsonly\"";
    if ($clientsonly == "on") {
        echo " checked";
    }
    echo "> ";
    echo $aInt->lang("supportticketdepts", "clientsonlydesc");
    echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
    echo $aInt->lang("supportticketdepts", "piperepliesonly");
    echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"piperepliesonly\"";
    if ($piperepliesonly == "on") {
        echo " checked";
    }
    echo "> ";
    echo $aInt->lang("supportticketdepts", "ticketsclientareaonlydesc");
    echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
    echo $aInt->lang("supportticketdepts", "noautoresponder");
    echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"noautoresponder\"";
    if ($noautoresponder == "on") {
        echo " checked";
    }
    echo "> ";
    echo $aInt->lang("supportticketdepts", "noautoresponderdesc");
    echo "</label></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
    echo AdminLang::trans("supportticketdepts.feedbackRequest");
    echo "    </td>\n    <td class=\"fieldarea\">\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"feedbackrequest\"";
    if ($feedbackRequest) {
        echo " checked";
    }
    echo " value=\"1\"> ";
    echo AdminLang::trans("supportticketdepts.feedbackRequestDescription");
    echo "        </label>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
    echo $aInt->lang("global", "hidden");
    echo "?</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"hidden\"";
    if ($hidden == "on") {
        echo " checked";
    }
    echo "> ";
    echo $aInt->lang("supportticketdepts", "hiddendesc");
    echo "</label></td></tr>\n</table>\n<p><b>";
    echo $aInt->lang("supportticketdepts", "pop3importconfigtitle");
    echo "</b> ";
    echo $aInt->lang("supportticketdepts", "pop3importconfigdesc");
    echo "</p>\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"20%\" class=\"fieldlabel\">";
    echo $aInt->lang("fields", "hostname");
    echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"host\" value=\"";
    echo $host;
    echo "\" placeholder=\"mail.example.com\" class=\"form-control input-400 input-inline\"></td></tr>\n<tr><td class=\"fieldlabel\">";
    echo $aInt->lang("supportticketdepts", "pop3port");
    echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"port\" value=\"";
    echo $port;
    echo "\" placeholder=\"995\" class=\"form-control input-100 input-inline\"></td></tr>\n<tr><td class=\"fieldlabel\">";
    echo $aInt->lang("supportticketdepts", "pop3user");
    echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"login\" value=\"";
    echo $login;
    echo "\" class=\"form-control input-300 input-inline\"></td></tr>\n<tr><td class=\"fieldlabel\">";
    echo $aInt->lang("supportticketdepts", "pop3pass");
    echo "</td><td class=\"fieldarea\"><input type=\"password\" name=\"password\" value=\"";
    echo $password;
    echo "\" autocomplete=\"off\" class=\"form-control input-300 input-inline\"></td></tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
    echo $aInt->lang("supportticketdepts", "addnewdept");
    echo "\" class=\"btn btn-primary\">\n    <input type=\"button\" value=\"";
    echo $aInt->lang("global", "cancel");
    echo "\" onClick=\"window.location='";
    echo $whmcs->getPhpSelf();
    echo "'\" class=\"btn btn-default\" />\n</div>\n\n</form>\n\n";
}
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();

?>