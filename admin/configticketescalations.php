<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Configure Escalation Rules");
$aInt->title = $aInt->lang("supportticketescalations", "supportticketescalationstitle");
$aInt->sidebar = "config";
$aInt->icon = "todolist";
$aInt->helplink = "Support Ticket Escalations";
$action = $whmcs->get_req_var("action");
$id = (int) $whmcs->get_req_var("id");
if ($action == "markdown") {
    $body = "<div class=\"row\">\n    <div class=\"col-md-6\">\n        <h4>Emphasis</h4>\n        <pre>\n**<strong>bold</strong>**\n*<em>italics</em>*\n~~<strike>strikethrough</strike>~~</pre>\n\n        <h4>Headers</h4>\n        <pre class=\"markdown-content\">\n<h1 style=\"margin:0;\"># Big header</h1>\n<h2 style=\"margin:0;\">## Medium header</h2>\n<h3 style=\"margin:0;\">### Small header</h3>\n<h4 style=\"margin:0;\">#### Tiny header</h4>\n</pre>\n\n        <h4>Lists</h4>\n        <pre>\n* Generic list item\n* Generic list item\n* Generic list item\n\n1. Numbered list item\n2. Numbered list item\n3. Numbered list item</pre>\n    </div>\n    <div class=\"col-md-6\">\n        <h4>Links</h4>\n        <pre>[Text to display](http://www.example.com)</pre>\n\n        <h4>Quotes</h4>\n        <pre>\n> This is a quote.\n> It can span multiple lines!</pre>\n\n        <h4>Tables</h4>\n        <pre>\n| Column 1 | Column 2 | Column 3 |\n| -------- | -------- | -------- |\n| John     | Doe      | Male     |\n| Mary     | Smith    | Female   |\n</pre>\n<em>Or without aligning the columns...</em><br /><br />\n<pre>\n| Column 1 | Column 2 | Column 3 |\n| -------- | -------- | -------- |\n| John | Doe | Male |\n| Mary | Smith | Female |</pre>\n    </div>\n</div>\n\n        <h4>Displaying code</h4>\n        <pre>\n`var example = \"hello!\";`\n</pre>\n<em>Or spanning multiple lines...</em><br /><br />\n<pre>\n```\nvar example = \"hello!\";\nalert(example);\n```</pre>";
    $aInt->setBodyContent(array("body" => $body));
    $aInt->output();
    WHMCS\Terminus::getInstance()->doExit();
}
if ($action == "parseMarkdown") {
    $markup = new WHMCS\View\Markup\Markup();
    $content = App::get_req_var("content");
    $aInt->setBodyContent(array("body" => "<div class=\"markdown-content\">" . $markup->transform($content, "markdown") . "</div>"));
    $aInt->output();
    WHMCS\Terminus::getInstance()->doExit();
}
if ($action == "save") {
    check_token("WHMCS.admin.default");
    $name = $whmcs->get_req_var("name");
    $departments = $whmcs->get_req_var("departments") ?: array();
    $statuses = $whmcs->get_req_var("statuses") ?: array();
    $priorities = $whmcs->get_req_var("priorities") ?: array();
    $timeelapsed = $whmcs->get_req_var("timeelapsed");
    $newdepartment = (int) $whmcs->get_req_var("newdepartment");
    $newstatus = $whmcs->get_req_var("newstatus");
    $newpriority = $whmcs->get_req_var("newpriority");
    $flagto = (int) $whmcs->get_req_var("flagto");
    $notify = $whmcs->get_req_var("notify") ?: array();
    $addreply = $whmcs->get_req_var("addreply");
    if (is_array($departments)) {
        $departments = implode(",", $departments);
    }
    if (is_array($statuses)) {
        $statuses = implode(",", $statuses);
    }
    if (is_array($priorities)) {
        $priorities = implode(",", $priorities);
    }
    if (is_array($notify)) {
        $notify = implode(",", $notify);
    }
    if ($id) {
        $ticketEscalation = WHMCS\Database\Capsule::table("tblticketescalations")->find($id);
        if ($ticketEscalation->name != $name) {
            logAdminActivity("Ticket Escalation Modified: Name Changed: " . "'" . $ticketEscalation->name . "' to '" . $name . "' - Escalation ID: " . $id);
        }
        if ($ticketEscalation->departments != $departments || $ticketEscalation->statuses != $statuses || $ticketEscalation->priorities != $priorities || $ticketEscalation->timeelapsed != $timeelapsed || $ticketEscalation->newdepartment != $newdepartment || $ticketEscalation->newstatus != $newstatus || $ticketEscalation->newpriority != $newpriority || $ticketEscalation->flagto != $flagto || $ticketEscalation->notify != $notify || $ticketEscalation->addreply != $addreply) {
            logAdminActivity("Ticket Escalation Modified: '" . $name . "' - Escalation ID: " . $id);
        }
        update_query("tblticketescalations", array("name" => $name, "departments" => $departments, "statuses" => $statuses, "priorities" => $priorities, "timeelapsed" => $timeelapsed, "newdepartment" => $newdepartment, "newstatus" => $newstatus, "newpriority" => $newpriority, "flagto" => $flagto, "notify" => $notify, "addreply" => $addreply, "editor" => "markdown"), array("id" => $id));
        redir("saved=true");
    } else {
        $id = insert_query("tblticketescalations", array("name" => $name, "departments" => $departments, "statuses" => $statuses, "priorities" => $priorities, "timeelapsed" => $timeelapsed, "newdepartment" => $newdepartment, "newstatus" => $newstatus, "newpriority" => $newpriority, "flagto" => $flagto, "notify" => $notify, "addreply" => $addreply, "editor" => "markdown"));
        logAdminActivity("Ticket Escalation Created: '" . $name . "' - Escalation ID: " . $id);
        redir("added=true");
    }
}
if ($action == "delete") {
    check_token("WHMCS.admin.default");
    $ticketEscalation = WHMCS\Database\Capsule::table("tblticketescalations")->find($id);
    logAdminActivity("Ticket Escalation Deleted: '" . $ticketEscalation->name . "' - Escalation ID: " . $id);
    delete_query("tblticketescalations", array("id" => $id));
    redir("deleted=true");
}
ob_start();
if ($action == "") {
    if ($added) {
        infoBox($aInt->lang("supportticketescalations", "ruleaddsuccess"), $aInt->lang("supportticketescalations", "ruleaddsuccessdesc"));
    }
    if ($saved) {
        infoBox($aInt->lang("supportticketescalations", "ruleeditsuccess"), $aInt->lang("supportticketescalations", "ruleeditsuccessdesc"));
    }
    if ($deleted) {
        infoBox($aInt->lang("supportticketescalations", "ruledelsuccess"), $aInt->lang("supportticketescalations", "ruledelsuccessdesc"));
    }
    echo $infobox;
    $aInt->deleteJSConfirm("doDelete", "supportticketescalations", "delsureescalationrule", "?action=delete&id=");
    echo "\n<p>";
    echo $aInt->lang("supportticketescalations", "escalationrulesinfo");
    echo "</p>\n\n<div class=\"alert alert-warning text-center\">\n    <div class=\"input-group\">\n        <span class=\"input-group-addon\" id=\"cronPhp\">";
    echo $aInt->lang("supportticketescalations", "croncommandreq");
    echo "</span>\n        <input type=\"text\" id=\"inputCronPhp\" value=\"";
    echo WHMCS\Environment\Php::getPreferredCliBinary();
    echo " -q ";
    echo $whmcs->getCronDirectory();
    echo "/cron.php do --TicketEscalations\" class=\"form-control\" />\n    </div>\n</div>\n\n<p><a href=\"";
    echo $_SERVER["PHP_SELF"];
    echo "?action=manage\" class=\"btn btn-default\"><i class=\"fas fa-plus-square\"></i> ";
    echo $aInt->lang("supportticketescalations", "addnewrule");
    echo "</a></p>\n\n";
    $aInt->sortableTableInit("nopagination");
    $result = select_query("tblticketescalations", "", "", "name", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $id = $data["id"];
        $name = $data["name"];
        $tabledata[] = array($name, "<a href=\"" . $_SERVER["PHP_SELF"] . "?action=manage&id=" . $id . "\"><img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "edit") . "\"></a>", "<a href=\"#\" onClick=\"doDelete('" . $id . "');return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\"></a>");
    }
    echo $aInt->sortableTable(array($aInt->lang("addons", "name"), "", ""), $tabledata);
} else {
    if ($action == "manage") {
        if ($id) {
            $edittitle = "Edit Rule";
            $result = select_query("tblticketescalations", "", array("id" => $id));
            $data = mysql_fetch_array($result);
            $id = $data["id"];
            $name = $data["name"];
            $departments = $data["departments"];
            $statuses = $data["statuses"];
            $priorities = $data["priorities"];
            $timeelapsed = $data["timeelapsed"];
            $newdepartment = $data["newdepartment"];
            $newstatus = $data["newstatus"];
            $newpriority = $data["newpriority"];
            $flagto = $data["flagto"];
            $notify = $data["notify"];
            $addreply = $data["addreply"];
            $departments = explode(",", $departments);
            $statuses = explode(",", $statuses);
            $priorities = explode(",", $priorities);
            $notify = explode(",", $notify);
            $aInt->addMarkdownEditor("escalationReplyMDE", "escalation_reply_" . md5($id . WHMCS\Session::get("adminid")), "addreply");
        } else {
            $edittitle = "Add New Rule";
            $departments = $statuses = $priorities = $notify = array();
            $aInt->addMarkdownEditor("escalationReplyMDE", "escalation_reply_" . md5("new" . WHMCS\Session::get("adminid")), "addreply");
        }
        echo "<h2>" . $edittitle . "</h2>";
        echo "\n<form method=\"post\" action=\"";
        echo $_SERVER["PHP_SELF"];
        echo "?action=save\">\n<input type=\"hidden\" name=\"id\" value=\"";
        echo $id;
        echo "\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"20%\" class=\"fieldlabel\">";
        echo $aInt->lang("addons", "name");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"name\" value=\"";
        echo $name;
        echo "\" class=\"form-control input-500\"></td></tr>\n</table>\n\n<p><b>";
        echo $aInt->lang("supportticketescalations", "conditions");
        echo "</b></p>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"20%\" class=\"fieldlabel\">";
        echo $aInt->lang("supportticketescalations", "departments");
        echo "</td><td class=\"fieldarea\"><select name=\"departments[]\" size=\"4\" multiple=\"true\" class=\"form-control select-inline\">";
        $result = select_query("tblticketdepartments", "", "", "name", "ASC");
        while ($data = mysql_fetch_array($result)) {
            $departmentid = $data["id"];
            $departmentname = $data["name"];
            echo "<option value=\"" . $departmentid . "\"";
            if (in_array($departmentid, $departments)) {
                echo " selected";
            }
            echo ">" . $departmentname . "</option>";
        }
        echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("supportticketescalations", "statuses");
        echo "</td><td class=\"fieldarea\"><select name=\"statuses[]\" size=\"4\" multiple=\"true\" class=\"form-control select-inline\">\n";
        $result = select_query("tblticketstatuses", "", "", "sortorder", "ASC");
        while ($data = mysql_fetch_assoc($result)) {
            $title = $data["title"];
            echo "<option";
            if (in_array($title, $statuses)) {
                echo " selected";
            }
            echo ">" . $title . "</option>";
        }
        echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">Priorities</td><td class=\"fieldarea\"><select name=\"priorities[]\" size=\"3\" multiple=\"true\" class=\"form-control select-inline\">\n<option value=\"Low\"";
        if (in_array("Low", $priorities)) {
            echo " selected";
        }
        echo ">";
        echo $aInt->lang("supportticketescalations", "prioritylow");
        echo "</option>\n<option value=\"Medium\"";
        if (in_array("Medium", $priorities)) {
            echo " selected";
        }
        echo ">";
        echo $aInt->lang("supportticketescalations", "prioritymedium");
        echo "</option>\n<option value=\"High\"";
        if (in_array("High", $priorities)) {
            echo " selected";
        }
        echo ">";
        echo $aInt->lang("supportticketescalations", "priorityhigh");
        echo "</option>\n</select></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("supportticketescalations", "timeelapsed");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"timeelapsed\" value=\"";
        echo $timeelapsed;
        echo "\" class=\"form-control input-100 input-inline\"> ";
        echo $aInt->lang("supportticketescalations", "minsincelastreply");
        echo "</td></tr>\n</table>\n\n<p><b>";
        echo $aInt->lang("supportticketescalations", "actions");
        echo "</b></p>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"20%\" class=\"fieldlabel\">";
        echo $aInt->lang("supportticketescalations", "department");
        echo "</td><td class=\"fieldarea\"><select name=\"newdepartment\" class=\"form-control select-inline\"><option value=\"\">- ";
        echo $aInt->lang("supportticketescalations", "nochange");
        echo " -</option>";
        $result = select_query("tblticketdepartments", "", "", "name", "ASC");
        while ($data = mysql_fetch_array($result)) {
            $departmentid = $data["id"];
            $departmentname = $data["name"];
            echo "<option value=\"" . $departmentid . "\"";
            if ($newdepartment == $departmentid) {
                echo " selected";
            }
            echo ">" . $departmentname . "</option>";
        }
        echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "status");
        echo "</td><td class=\"fieldarea\"><select name=\"newstatus\" class=\"form-control select-inline\"><option value=\"\">- ";
        echo $aInt->lang("supportticketescalations", "nochange");
        echo " -</option>\n";
        $result = select_query("tblticketstatuses", "", "", "sortorder", "ASC");
        while ($data = mysql_fetch_assoc($result)) {
            $title = $data["title"];
            echo "<option";
            if ($title == $newstatus) {
                echo " selected";
            }
            echo ">" . $title . "</option>";
        }
        echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("supportticketescalations", "priority");
        echo "</td><td class=\"fieldarea\"><select name=\"newpriority\" class=\"form-control select-inline\"><option value=\"\">- ";
        echo $aInt->lang("supportticketescalations", "nochange");
        echo " -</option>\n<option";
        if ($newpriority == "Low") {
            echo " selected";
        }
        echo ">";
        echo $aInt->lang("supportticketescalations", "prioritylow");
        echo "</option>\n<option";
        if ($newpriority == "Medium") {
            echo " selected";
        }
        echo ">";
        echo $aInt->lang("supportticketescalations", "prioritymedium");
        echo "</option>\n<option";
        if ($newpriority == "High") {
            echo " selected";
        }
        echo ">";
        echo $aInt->lang("supportticketescalations", "priorityhigh");
        echo "</option>\n</select></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("supportticketescalations", "flagto");
        echo "</td><td class=\"fieldarea\"><select name=\"flagto\" class=\"form-control select-inline\"><option value=\"\">- ";
        echo $aInt->lang("supportticketescalations", "nochange");
        echo " -</option>";
        $result = select_query("tbladmins", "", "", "username", "ASC");
        while ($data = mysql_fetch_array($result)) {
            $flag_adminid = $data["id"];
            $flag_adminusername = $data["username"];
            echo "<option value=\"" . $flag_adminid . "\"";
            if ($flag_adminid == $flagto) {
                echo " selected";
            }
            echo ">" . $flag_adminusername . "</option>";
        }
        echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("supportticketescalations", "notifyadmins");
        echo "</td><td class=\"fieldarea\">\n<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"notify[]\" value=\"all\"";
        if (in_array("all", $notify)) {
            echo " checked";
        }
        echo " /> ";
        echo $aInt->lang("supportticketescalations", "notifyadminsdesc");
        echo "</label>\n<div style=\"padding:5px;\">";
        echo $aInt->lang("supportticketescalations", "alsonotify");
        echo ":</div>\n";
        $result = select_query("tbladmins", "", "", "username", "ASC");
        while ($data = mysql_fetch_array($result)) {
            echo "<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"notify[]\" value=\"" . $data["id"] . "\"";
            if (in_array($data["id"], $notify)) {
                echo " checked";
            }
            echo " /> ";
            if ($data["disabled"] == 1) {
                echo "<span class=\"disabledtext\">";
            }
            echo $data["username"] . " (" . $data["firstname"] . " " . $data["lastname"] . ")";
            if ($data["disabled"] == 1) {
                echo " - " . $aInt->lang("global", "disabled") . "</span> ";
            }
            echo "</label>";
        }
        echo "</td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
        echo $aInt->lang("support", "addreply");
        echo "    </td>\n    <td class=\"fieldarea\">\n        <textarea id=\"addreply\" name=\"addreply\" rows=\"15\" class=\"form-control\">";
        echo $addreply;
        echo "</textarea>\n    </td>\n</tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
        echo $aInt->lang("global", "savechanges");
        echo "\" class=\"btn btn-primary\" />\n    <input type=\"button\" value=\"";
        echo $aInt->lang("global", "cancelchanges");
        echo "\" onClick=\"window.location='";
        echo $whmcs->getPhpSelf();
        echo "'\" class=\"btn btn-default\" />\n</div>\n\n</form>\n\n";
    }
}
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();

?>