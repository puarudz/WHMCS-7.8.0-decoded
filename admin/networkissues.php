<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Manage Network Issues");
$aInt->title = $aInt->lang("networkissues", "title");
$aInt->sidebar = "support";
$aInt->icon = "networkissues";
$upd = fromMySQLDate(date("Y-m-d H:i:s"), true);
if ($action == "save") {
    check_token("WHMCS.admin.default");
    if (!$startdate) {
        $startdate = $upd;
    }
    $errormessage = "";
    if (!$title) {
        $errormessage = "<li>" . $aInt->lang("networkIssues", "missingTitle") . "</li>";
    }
    if (!$type) {
        $errormessage = "<li>" . $aInt->lang("networkIssues", "missingType") . "</li>";
    }
    if ($type == "Server" && !$server) {
        $errormessage = "<li>" . $aInt->lang("networkIssues", "missingServer") . "</li>";
    }
    if (($type == "Service" || $type == "Other") && !$affecting) {
        $errormessage = "<li>" . $aInt->lang("networkIssues", "missingAffecting") . "</li>";
    }
    if ($type != "Server") {
        $server = 0;
    }
    if (!$startdate) {
        $errormessage = "<li>" . $aInt->lang("networkIssues", "missingStartDate") . "</li>";
    }
    if (!$description) {
        $errormessage = "<li>" . $aInt->lang("networkIssues", "missingDescription") . "</li>";
    }
    if ($errormessage) {
        $action = "manage";
    } else {
        $startdate = toMySQLDate($startdate);
        if ($enddate) {
            $enddate = toMySQLDate($enddate);
        } else {
            $enddate = "NULL";
        }
        $updatearray = array("startdate" => $startdate, "enddate" => $enddate, "title" => $title, "description" => WHMCS\Input\Sanitize::decode($description), "type" => $type, "server" => $server, "affecting" => $affecting, "priority" => $priority, "status" => $status, "lastupdate" => "now()");
        if ($id) {
            update_query("tblnetworkissues", $updatearray, array("id" => $id));
            run_hook("NetworkIssueEdit", array_merge(array("id" => $id), $updatearray));
            if ($status == "Resolved") {
                run_hook("NetworkIssueClose", array("id" => $id));
            }
        } else {
            $nwid = insert_query("tblnetworkissues", $updatearray);
            run_hook("NetworkIssueAdd", array_merge(array("id" => $nwid), $updatearray));
        }
        redir();
    }
}
if ($action == "close") {
    check_token("WHMCS.admin.default");
    update_query("tblnetworkissues", array("status" => "Resolved", "enddate" => "now()"), array("id" => $id));
    run_hook("NetworkIssueClose", array("id" => $id));
    redir("view=resolved");
}
if ($action == "reopen") {
    check_token("WHMCS.admin.default");
    update_query("tblnetworkissues", array("status" => "In Progress", "enddate" => "NULL"), array("id" => $id));
    run_hook("NetworkIssueReopen", array("id" => $id));
    redir();
}
if ($action == "delete") {
    check_token("WHMCS.admin.default");
    run_hook("NetworkIssueDelete", array("id" => $id));
    delete_query("tblnetworkissues", array("id" => $id));
    redir();
}
$t_query = "SHOW COLUMNS FROM tblnetworkissues LIKE 'type'";
$t_result = full_query($t_query);
if (0 < mysql_num_rows($t_result)) {
    $t_row = mysql_fetch_row($t_result);
    $type_options = explode("','", preg_replace("/(enum|set)\\('(.+?)'\\)/", "\\2", $t_row[1]));
}
$p_query = "SHOW COLUMNS FROM tblnetworkissues LIKE 'priority'";
$p_result = full_query($p_query);
if (0 < mysql_num_rows($p_result)) {
    $p_row = mysql_fetch_row($p_result);
    $priority_options = explode("','", preg_replace("/(enum|set)\\('(.+?)'\\)/", "\\2", $p_row[1]));
}
$s_query = "SHOW COLUMNS FROM tblnetworkissues LIKE 'status'";
$s_result = full_query($s_query);
if (0 < mysql_num_rows($s_result)) {
    $s_row = mysql_fetch_row($s_result);
    $status_options = explode("','", preg_replace("/(enum|set)\\('(.+?)'\\)/", "\\2", $s_row[1]));
}
$server_query = "SELECT id, name FROM tblservers";
$server_result = full_query($server_query);
ob_start();
if ($action == "") {
    if ($view == "scheduled") {
        $pagetitle = "Scheduled";
        $where = array("status" => "Scheduled");
    } else {
        if ($view == "resolved") {
            $pagetitle = "Resolved";
            $where = array("status" => "Resolved");
        } else {
            $pagetitle = "Open";
            $where = "status!='Resolved' AND status!='Scheduled'";
        }
    }
    $result = select_query("tblnetworkissues", "*,(select name from tblservers where id = tblnetworkissues.server) as server", $where, "lastupdate", "DESC");
    $aInt->deleteJSConfirm("doDelete", "networkissues", "deletesure", "?action=delete&id=");
    echo "\n<p><strong>";
    echo $aInt->lang("fields", "options");
    echo ":</strong>\n    <a id=\"openNetworkIssues\"      href=\"networkissues.php\">";
    echo $aInt->lang("networkissues", "open");
    echo "</a> |\n    <a id=\"scheduledNetworkIssues\" href=\"networkissues.php?view=scheduled\">";
    echo $aInt->lang("networkissues", "scheduled");
    echo "</a> |\n    <a id=\"resolvedNetworkIssues\"  href=\"networkissues.php?view=resolved\">";
    echo $aInt->lang("networkissues", "resolved");
    echo "</a> |\n    <a id=\"createNewNetworkIssue\"  href=\"networkissues.php?action=manage\"><img src=\"images/icons/add.png\" border=\"0\" align=\"absmiddle\" /> ";
    echo $aInt->lang("networkissues", "addnew");
    echo "</a></p>\n\n<h2>";
    echo $pagetitle;
    echo " Issues</h2>\n\n";
    $aInt->sortableTableInit("nopagination");
    if (mysql_num_rows($result)) {
        while ($open_row = mysql_fetch_assoc($result)) {
            $enddate = $open_row["enddate"];
            if ($enddate) {
                $enddate = fromMySQLDate($enddate, true);
            } else {
                $enddate = $aInt->lang("networkIssues", "none");
            }
            if ($open_row["server"]) {
                $open_row["type"] .= " (" . $open_row["server"] . ")";
            }
            if ($open_row["status"] == "Resolved") {
                $actions = "<a href=\"" . $_SERVER["PHP_SELF"] . "?action=reopen&id=" . $open_row["id"] . generate_token("link") . "\">" . $aInt->lang("networkIssues", "reopen") . "</a>";
            } else {
                $actions = "<a href=\"" . $_SERVER["PHP_SELF"] . "?action=close&id=" . $open_row["id"] . generate_token("link") . "\">" . $aInt->lang("networkIssues", "close") . "</a>";
            }
            $tabledata[] = array("<a href=\"" . $_SERVER["PHP_SELF"] . "?action=manage&id=" . $open_row["id"] . "\">" . $open_row["title"] . "</a>", $open_row["type"], $open_row["priority"], $open_row["status"], fromMySQLDate($open_row["startdate"], true), $enddate, $actions, "<a href=\"#\" onClick=\"doDelete('" . $open_row["id"] . "');return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"Delete\"></a>");
        }
    }
    echo $aInt->sortableTable(array($aInt->lang("networkIssues", "titleTitle"), $aInt->lang("networkIssues", "type"), $aInt->lang("networkIssues", "priority"), $aInt->lang("networkIssues", "status"), $aInt->lang("networkIssues", "startDate"), $aInt->lang("networkIssues", "endDate"), " ", ""), $tabledata);
} else {
    if ($action == "manage") {
        if ($errormessage) {
            infoBox($aInt->lang("networkIssues", "validationFailed"), $errormessage, "error");
            echo $infobox;
        }
        echo "<form id=\"manageNetworkIssueForm\" method=\"post\" action=\"" . $_SERVER["PHP_SELF"] . "?action=save\">";
        if ($id) {
            $pagetitle = $aInt->lang("networkIssues", "modifyExisting");
            $result = select_query("tblnetworkissues", "", array("id" => $id));
            $data = mysql_fetch_array($result);
            $title = $data["title"];
            $startdate = $data["startdate"];
            $enddate = $data["enddate"];
            $description = $data["description"];
            $type = $data["type"];
            $affecting = $data["affecting"];
            $server = $data["server"];
            $priority = $data["priority"];
            $status = $data["status"];
            $lastupdate = $data["lastupdate"];
            $startts = $startdate ? MySQL2Timestamp($startdate) : "";
            $endts = $enddate ? MySQL2Timestamp($enddate) : "";
            $startdate = fromMySQLDate($startdate, true);
            if ($enddate) {
                $enddate = fromMySQLDate($enddate, true);
            }
            echo "<input type=\"hidden\" name=\"id\" value=\"" . $id . "\" />";
        } else {
            $pagetitle = $aInt->lang("networkIssues", "createNewIssue");
            if (!$startdate) {
                $startdate = $upd;
                $startts = $startdate ? MySQL2Timestamp($startdate) : "";
            }
            if (!$type) {
                $type = "Server";
            }
        }
        $jquerycode = "\$(\"#affectingtype\").change( function() {\n    affectingtype = \$(\"option:selected\", this).val();\n    if (affectingtype==\"Server\") {\n        \$(\"#affectingserver\").css(\"display\",\"\");\n        \$(\"#affectingother\").css(\"display\",\"none\");\n    } else {\n        \$(\"#affectingserver\").css(\"display\",\"none\");\n        \$(\"#affectingother\").css(\"display\",\"\");\n    }\n});";
        echo "<h2>" . $pagetitle . "</h2>";
        echo "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr>\n    <td width=\"15%\" class=\"fieldlabel\">";
        echo $aInt->lang("networkIssues", "fieldTitle");
        echo "</td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"title\" class=\"form-control input-400\" value=\"";
        echo $title;
        echo "\" />\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">Type</td><td class=\"fieldarea\"><select name=\"type\" id=\"affectingtype\" class=\"form-control select-inline\">";
        foreach ($type_options as $row => $value) {
            if ($value == $type) {
                echo "<option value=\"" . $value . "\" selected>" . $aInt->lang("networkIssues", "type" . str_replace(" ", "", $value)) . "</option>";
            } else {
                echo "<option value=\"" . $value . "\">" . $aInt->lang("networkIssues", "type" . str_replace(" ", "", $value)) . "</option>";
            }
        }
        echo "</select></td></tr>\n<tr id=\"affectingserver\"";
        if ($type != "Server") {
            echo "style=\"display:none;\"";
        }
        echo ">\n    <td class=\"fieldlabel\">";
        echo $aInt->lang("networkIssues", "fieldServer");
        echo "</td>\n    <td class=\"fieldarea\">\n        <select name=\"server\" class=\"form-control select-inline\">\n            <option value=\"0\">";
        echo AdminLang::trans("global.none");
        echo "</option>\n            ";
        while ($server_options = mysql_fetch_assoc($server_result)) {
            $selected = "";
            if ($server_options["id"] == $server) {
                $selected = " selected=\"selected\"";
            }
            echo "<option value=\"" . $server_options["id"] . "\"" . $selected . ">" . (string) $server_options["name"] . "</option>";
        }
        echo "        </select>\n    </td>\n</tr>\n<tr id=\"affectingother\"";
        if ($type == "Server") {
            echo "style=\"display:none;\"";
        }
        echo ">\n    <td class=\"fieldlabel\">";
        echo $aInt->lang("networkIssues", "fieldOther");
        echo "</td>\n    <td class=\"fieldarea\"><input type=\"text\" name=\"affecting\" size=\"50\" value=\"";
        echo $affecting;
        echo "\" /></td></tr>\n<tr><td class=\"fieldlabel\">Priority</td><td class=\"fieldarea\"><select name=\"priority\" class=\"form-control select-inline\">";
        foreach ($priority_options as $row => $value) {
            echo "<option value=\"" . $value . "\"";
            if ($value == $priority) {
                echo " selected";
            }
            echo ">" . $aInt->lang("networkIssues", "priority" . str_replace(" ", "", $value)) . "</option>";
        }
        echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">Status</td><td class=\"fieldarea\"><select name=\"status\" class=\"form-control select-inline\">";
        foreach ($status_options as $row => $value) {
            echo "<option value=\"" . $value . "\"";
            if ($value == $status) {
                echo " selected";
            }
            echo ">" . $aInt->lang("networkIssues", "status" . str_replace(" ", "", $value)) . "</option>";
        }
        echo "</select></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
        echo $aInt->lang("networkIssues", "startDate");
        echo "    </td>\n    <td class=\"fieldarea\">\n        <div class=\"form-group date-picker-prepend-icon\">\n            <label for=\"startdate\" class=\"field-icon\">\n                <i class=\"fal fa-calendar-alt\"></i>\n            </label>\n            <input id=\"startdate\"\n                   type=\"text\"\n                   name=\"startdate\"\n                   value=\"";
        echo $startdate;
        echo "\"\n                   class=\"form-control date-picker-single time future\"\n            />\n        </div>\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
        echo $aInt->lang("networkIssues", "endDate");
        echo "    </td>\n    <td class=\"fieldarea\">\n        <div class=\"form-group date-picker-prepend-icon\">\n            <label for=\"enddate\" class=\"field-icon\">\n                <i class=\"fal fa-calendar-alt\"></i>\n            </label>\n            <input id=\"enddate\"\n                   type=\"text\"\n                   name=\"enddate\"\n                   value=\"";
        echo $enddate;
        echo "\"\n                   class=\"form-control date-picker-single time future\"\n            />\n        </div>\n    </td>\n</tr>\n</table>\n\n<p><strong>";
        echo $aInt->lang("fields", "description");
        echo "</strong></p>\n\n<textarea name=\"description\" id=\"message\" rows=20 style=\"width:100%\" class=\"tinymce\">\n    ";
        echo WHMCS\Input\Sanitize::makeSafeForOutput($description);
        echo "</textarea>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" name=\"submit\" value=\"";
        echo $aInt->lang("global", "savechanges");
        echo "\" class=\"btn btn-primary\" />\n</div>\n\n</form>\n\n";
        $aInt->richTextEditor();
    }
}
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();

?>