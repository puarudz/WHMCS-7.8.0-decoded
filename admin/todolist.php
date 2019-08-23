<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("To-Do List");
$aInt->title = AdminLang::trans("todolist.todolisttitle");
$aInt->sidebar = "utilities";
$aInt->icon = "todolist";
if ($action == "delete") {
    check_token("WHMCS.admin.default");
    delete_query("tbltodolist", array("id" => $id));
    redir();
}
if ($action == "add") {
    check_token("WHMCS.admin.default");
    $table = "tbltodolist";
    $array = array("date" => toMySQLDate($date), "title" => $title, "description" => $description, "admin" => $admin, "status" => $status, "duedate" => toMySQLDate($duedate));
    insert_query($table, $array);
    redir();
}
if ($action == "save") {
    check_token("WHMCS.admin.default");
    $table = "tbltodolist";
    $array = array("date" => toMySQLDate($date), "title" => $title, "description" => $description, "admin" => $admin, "status" => $status, "duedate" => toMySQLDate($duedate));
    $where = array("id" => $id);
    update_query($table, $array, $where);
    redir();
}
if ($mass_assign) {
    check_token("WHMCS.admin.default");
    foreach ($selids as $id) {
        update_query("tbltodolist", array("admin" => $_SESSION["adminid"]), array("id" => $id));
    }
    redir();
}
if ($mass_inprogress) {
    check_token("WHMCS.admin.default");
    foreach ($selids as $id) {
        update_query("tbltodolist", array("status" => "In Progress"), array("id" => $id));
    }
    redir();
}
if ($mass_completed) {
    check_token("WHMCS.admin.default");
    foreach ($selids as $id) {
        update_query("tbltodolist", array("status" => "Completed"), array("id" => $id));
    }
    redir();
}
if ($mass_postponed) {
    check_token("WHMCS.admin.default");
    foreach ($selids as $id) {
        update_query("tbltodolist", array("status" => "Postponed"), array("id" => $id));
    }
    redir();
}
if ($mass_delete) {
    check_token("WHMCS.admin.default");
    foreach ($selids as $id) {
        delete_query("tbltodolist", array("id" => $id));
    }
    redir();
}
ob_start();
if ($action == "") {
    $aInt->deleteJSConfirm("doDelete", "todolist", "delsuretodoitem", "?action=delete&id=");
    echo $aInt->beginAdminTabs(array(AdminLang::trans("global.searchfilter"), AdminLang::trans("todolist.additem")));
    $status = App::getFromRequest("status");
    $date = App::getFromRequest("date");
    $duedate = App::getFromRequest("duedate");
    $title = App::getFromRequest("title");
    $description = App::getFromRequest("description");
    $admim = App::getFromRequest("admin");
    echo "\n<form method=\"post\" action=\"todolist.php\"><input type=\"hidden\" name=\"filter\" value=\"true\">\n    <table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n        <tr>\n            <td width=\"15%\" class=\"fieldlabel\">\n                ";
    echo AdminLang::trans("fields.daterange");
    echo "            </td>\n            <td class=\"fieldarea\">\n                <div class=\"form-group date-picker-prepend-icon\">\n                    <label for=\"inputDate\" class=\"field-icon\">\n                        <i class=\"fal fa-calendar-alt\"></i>\n                    </label>\n                    <input id=\"inputDate\"\n                           type=\"text\"\n                           name=\"date\"\n                           value=\"";
    echo $date;
    echo "\"\n                           class=\"form-control date-picker-search\"\n                           data-opens=\"left\"\n                    />\n                </div>\n            </td>\n            <td width=\"15%\" class=\"fieldlabel\">\n                ";
    echo AdminLang::trans("fields.duedate");
    echo "            </td>\n            <td class=\"fieldarea\">\n                <div class=\"form-group date-picker-prepend-icon\">\n                    <label for=\"inputDueDate\" class=\"field-icon\">\n                        <i class=\"fal fa-calendar-alt\"></i>\n                    </label>\n                    <input id=\"inputDueDate\"\n                           type=\"text\"\n                           name=\"duedate\"\n                           value=\"";
    echo $duedate;
    echo "\"\n                           class=\"form-control date-picker-search\"\n                    />\n                </div>\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                ";
    echo AdminLang::trans("fields.title");
    echo "            </td>\n            <td class=\"fieldarea\">\n                <input type=\"text\"\n                       name=\"title\"\n                       class=\"form-control input-400\"\n                       value=\"";
    echo $title;
    echo "\"\n                >\n            </td>\n            <td class=\"fieldlabel\">\n                ";
    echo AdminLang::trans("fields.admin");
    echo "            </td>\n            <td class=\"fieldarea\">\n                <select name=\"admin\" class=\"form-control select-inline\">\n                    <option value=\"\">\n                        ";
    echo AdminLang::trans("global.any");
    echo "                    </option>\n                    ";
    $admins = WHMCS\Database\Capsule::table("tbladmins")->pluck("username", "id");
    foreach ($admins as $adminId => $adminUsername) {
        $selected = "";
        if ($adminId == $admin) {
            $selected = " selected=\"selected\"";
        }
        echo "<option value=\"" . $adminId . "\"" . $selected . ">" . $adminUsername . "</option>";
    }
    echo "                </select>\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                ";
    echo AdminLang::trans("fields.description");
    echo "            </td>\n            <td class=\"fieldarea\">\n                <input type=\"text\"\n                       name=\"description\"\n                       class=\"form-control input-400\"\n                       value=\"";
    echo $description;
    echo "\"\n                >\n            </td>\n            <td class=\"fieldlabel\">\n                ";
    echo AdminLang::trans("fields.status");
    echo "            </td>\n            <td class=\"fieldarea\">\n                <select name=\"status\" class=\"form-control select-inline\">\n                    <option";
    echo $status == "Incomplete" ? " selected=\"selected\"" : "";
    echo ">\n                        ";
    echo AdminLang::trans("todolist.incomplete");
    echo "                    </option>\n                    <option";
    echo $status == "New" ? " selected=\"selected\"" : "";
    echo ">\n                        ";
    echo AdminLang::trans("domains.new");
    echo "                    </option>\n                    <option";
    echo $status == "Pending" ? " selected=\"selected\"" : "";
    echo ">\n                        ";
    echo AdminLang::trans("status.pending");
    echo "                    </option>\n                    <option";
    echo $status == "In Progress" ? " selected=\"selected\"" : "";
    echo ">\n                        ";
    echo AdminLang::trans("todolist.inProgress");
    echo "                    </option>\n                    <option";
    echo $status == "Completed" ? " selected=\"selected\"" : "";
    echo ">\n                        ";
    echo AdminLang::trans("todolist.completed");
    echo "                    </option>\n                    <option";
    echo $status == "Postponed" ? " selected=\"selected\"" : "";
    echo ">\n                        ";
    echo AdminLang::trans("todolist.postponed");
    echo "                    </option>\n                </select>\n            </td>\n        </tr>\n    </table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
    echo AdminLang::trans("global.searchfilter");
    echo "\" class=\"btn btn-primary\" />\n</div>\n\n</form>\n\n";
    echo $aInt->nextAdminTab();
    echo "\n<form method=\"post\" action=\"";
    echo $whmcs->getPhpSelf();
    echo "?action=add\">\n\n    <table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n        <tr>\n            <td width=\"15%\" class=\"fieldlabel\">\n                ";
    echo AdminLang::trans("fields.date");
    echo "            </td>\n            <td class=\"fieldarea\">\n                <div class=\"form-group date-picker-prepend-icon\">\n                    <label for=\"inputDate\" class=\"field-icon\">\n                        <i class=\"fal fa-calendar-alt\"></i>\n                    </label>\n                    <input id=\"inputDate\"\n                           type=\"text\"\n                           name=\"date\"\n                           value=\"";
    echo getTodaysDate();
    echo "\"\n                           class=\"form-control date-picker-single\"\n                    />\n                </div>\n            </td>\n            <td width=\"15%\"\n                class=\"fieldlabel\">";
    echo AdminLang::trans("fields.duedate");
    echo "            </td>\n            <td class=\"fieldarea\">\n                <div class=\"form-group date-picker-prepend-icon\">\n                    <label for=\"inputDueDate\" class=\"field-icon\">\n                        <i class=\"fal fa-calendar-alt\"></i>\n                    </label>\n                    <input id=\"inputDueDate\"\n                           type=\"text\"\n                           name=\"duedate\"\n                           value=\"\"\n                           class=\"form-control date-picker-single future\"\n                    />\n                </div>\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                ";
    echo AdminLang::trans("fields.title");
    echo "            </td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"title\" class=\"form-control input-400\">\n            </td>\n            <td class=\"fieldlabel\">\n                ";
    echo AdminLang::trans("fields.admin");
    echo "            </td>\n            <td class=\"fieldarea\">\n                <select name=\"admin\" class=\"form-control select-inline\">\n                    <option value=\"\">\n                        ";
    echo AdminLang::trans("global.none");
    echo "                    </option>\n                    ";
    $admins = WHMCS\Database\Capsule::table("tbladmins")->where("disabled", 0)->pluck("username", "id");
    foreach ($admins as $adminId => $adminUsername) {
        echo "<option value=\"" . $adminId . "\">" . $adminUsername . "</option>";
    }
    echo "                </select>\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">\n                ";
    echo AdminLang::trans("fields.description");
    echo "            </td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"description\" class=\"form-control input-400\">\n            </td>\n            <td class=\"fieldlabel\">\n                ";
    echo AdminLang::trans("fields.status");
    echo "            </td>\n            <td class=\"fieldarea\">\n                <select name=\"status\" class=\"form-control select-inline\">\n                    <option>";
    echo AdminLang::trans("domains.new");
    echo "                    <option>";
    echo AdminLang::trans("status.pending");
    echo "                    <option>";
    echo AdminLang::trans("todolist.inProgress");
    echo "                    <option>";
    echo AdminLang::trans("todolist.completed");
    echo "                    <option>";
    echo AdminLang::trans("todolist.postponed");
    echo "                </select>\n            </td>\n        </tr>\n    </table>\n\n    <div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
    echo AdminLang::trans("todolist.addtodoitem");
    echo "\" class=\"btn btn-primary\">\n</div>\n\n</form>\n\n";
    echo $aInt->endAdminTabs();
    echo "\n<br />\n\n";
    $aInt->sortableTableInit("duedate", "ASC");
    unset($where);
    $query = WHMCS\Database\Capsule::table("tbltodolist");
    $where = array();
    if ($status == "Incomplete" || $status == "") {
        $query->where("status", "!=", "Completed");
    } else {
        $query->where("status", "!=", $status);
    }
    if ($date) {
        $date = WHMCS\Carbon::parseDateRangeValue($date);
        $startDate = $date["from"];
        $endDate = $date["to"];
        $query->whereBetween("date", array($startDate->toDateTimeString(), $endDate->toDateTimeString()));
    }
    if ($duedate) {
        $duedate = WHMCS\Carbon::parseDateRangeValue($duedate);
        $startDate = $duedate["from"];
        $endDate = $duedate["to"];
        $query->whereBetween("duedate", array($startDate->toDateTimeString(), $endDate->toDateTimeString()));
    }
    if ($title) {
        $query->where("title", "like", "%" . $title . "%");
    }
    if ($description) {
        $query->where("description", "like", "%" . $description . "%");
    }
    if ($admin) {
        $query->where("admin", $admin);
    }
    $numrows = $query->count();
    $AdminsArray = array();
    $query->limit($limit);
    $query->offset($page * $limit);
    foreach ($query->get() as $data) {
        $data = (array) $data;
        $i++;
        $id = $data["id"];
        $date = $data["date"];
        $title = $data["title"];
        $description = $data["description"];
        $adminid = $data["admin"];
        $status = $data["status"];
        $duedate = $data["duedate"];
        $date = fromMySQLDate($date);
        if ($duedate == "0000-00-00") {
            $duedate = "-";
        } else {
            $duedate = fromMySQLDate($duedate);
        }
        if (80 < strlen($description)) {
            $description = substr($description, 0, 80) . "...";
        }
        if ($adminid) {
            if (isset($AdminsArray[$adminid])) {
                $admin = $AdminsArray[$adminid];
            } else {
                $result2 = select_query("tbladmins", "firstname,lastname", array("id" => $adminid));
                $data = mysql_fetch_array($result2);
                $admin = $data["firstname"] . " " . $data["lastname"];
                $AdminsArray[$adminid] = $admin;
            }
        } else {
            $admin = "";
        }
        $tabledata[] = array("<input type=\"checkbox\" name=\"selids[]\" value=\"" . $id . "\" class=\"checkall\">", $date, $title, $description, $admin, $status, $duedate, "<a href=\"?action=edit&id=" . $id . "\"><img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "edit") . "\"></a>", "<a href=\"#\" onClick=\"doDelete('" . $id . "');return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\"></a>");
    }
    $tableformurl = $_SERVER["PHP_SELF"];
    $tableformbuttons = "<input type=\"submit\" value=\"" . AdminLang::trans("todolist.assign") . "\" class=\"btn btn-default\" name=\"mass_assign\"> <input type=\"submit\" value=\"" . AdminLang::trans("todolist.setProgress") . "\" class=\"btn btn-default\" name=\"mass_inprogress\"> <input type=\"submit\" value=\"" . AdminLang::trans("todolist.setComplete") . "\" class=\"btn btn-success\" name=\"mass_completed\"> <input type=\"submit\" value=\"" . AdminLang::trans("todolist.setPostponed") . "\" class=\"btn btn-default\" name=\"mass_postponed\"> <input type=\"submit\" value=\"" . AdminLang::trans("global.delete") . "\" class=\"btn btn-danger\" name=\"mass_delete\">";
    echo $aInt->sortableTable(array("checkall", array("date", "Date"), array("title", "Title"), array("description", "Description"), array("admin", "Admin"), array("status", "Status"), array("duedate", "Due Date"), "", ""), $tabledata, $tableformurl, $tableformbuttons);
} else {
    if ($action == "edit") {
        $table = "tbltodolist";
        $fields = "";
        $where = array("id" => $id);
        $result = select_query($table, $fields, $where);
        $data = mysql_fetch_array($result);
        $date = $data["date"];
        $title = $data["title"];
        $description = $data["description"];
        $admin = $data["admin"];
        $status = $data["status"];
        $duedate = $data["duedate"];
        $date = fromMySQLDate($date);
        $duedate = fromMySQLDate($duedate);
        echo "\n<p><b>";
        echo AdminLang::trans("todolist.edittodoitem");
        echo "</b></p>\n\n<form method=\"post\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "?action=save&id=";
        echo $id;
        echo "\" name=\"calendarfrm\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr>\n    <td width=\"15%\" class=\"fieldlabel\">\n        ";
        echo AdminLang::trans("fields.date");
        echo "    </td>\n    <td class=\"fieldarea\">\n        <div class=\"form-group date-picker-prepend-icon\">\n            <label for=\"inputDate\" class=\"field-icon\">\n                <i class=\"fal fa-calendar-alt\"></i>\n            </label>\n            <input id=\"inputDate\"\n                   type=\"text\"\n                   name=\"date\"\n                   value=\"";
        echo $date;
        echo "\"\n                   class=\"form-control date-picker-single\"\n            />\n        </div>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
        echo AdminLang::trans("fields.title");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"title\" size=50 value=\"";
        echo $title;
        echo "\"></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo AdminLang::trans("fields.description");
        echo "</td><td class=\"fieldarea\"><textarea name=\"description\" cols=100 rows=8>";
        echo $description;
        echo "</textarea></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo AdminLang::trans("fields.admin");
        echo "</td><td class=\"fieldarea\"><select name=\"admin\" class=\"form-control select-inline\"><option value=\"\">";
        echo AdminLang::trans("global.none");
        $result2 = select_query("tbladmins", "id,firstname,lastname,disabled", "", "username", "ASC");
        while ($data2 = mysql_fetch_array($result2)) {
            $admin_id = $data2["id"];
            $admin_name = $data2["firstname"] . " " . $data2["lastname"];
            $admin_disabled = $data2["disabled"];
            echo "<option value=\"" . $admin_id . "\"";
            if ($admin_id == $admin) {
                echo " selected";
            }
            echo ">" . $admin_name . ($admin_disabled ? " (" . AdminLang::trans("global.disabled") . ")" : "") . "</option>";
        }
        echo "</select></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
        echo AdminLang::trans("fields.duedate");
        echo "    </td>\n    <td class=\"fieldarea\">\n        <div class=\"form-group date-picker-prepend-icon\">\n            <label for=\"inputDueDate\" class=\"field-icon\">\n                <i class=\"fal fa-calendar-alt\"></i>\n            </label>\n            <input id=\"inputDueDate\"\n                   type=\"text\"\n                   name=\"date\"\n                   value=\"";
        echo $duedate;
        echo "\"\n                   class=\"form-control date-picker-single future\"\n            />\n        </div>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
        echo AdminLang::trans("fields.status");
        echo "</td><td class=\"fieldarea\"><select name=\"status\" class=\"form-control select-inline\"><option";
        if ($status == "Incomplete") {
            echo " selected";
        }
        echo ">";
        echo AdminLang::trans("todolist.incomplete");
        echo "<option";
        if ($status == "New") {
            echo " selected";
        }
        echo ">";
        echo AdminLang::trans("domains.new");
        echo "<option";
        if ($status == "Pending") {
            echo " selected";
        }
        echo ">";
        echo AdminLang::trans("status.pending");
        echo "<option";
        if ($status == "In Progress") {
            echo " selected";
        }
        echo ">";
        echo AdminLang::trans("todolist.inProgress");
        echo "<option";
        if ($status == "Completed") {
            echo " selected";
        }
        echo ">";
        echo AdminLang::trans("todolist.completed");
        echo "<option";
        if ($status == "Postponed") {
            echo " selected";
        }
        echo ">";
        echo AdminLang::trans("todolist.postponed");
        echo "</select></td></tr>\n</table>\n\n<p align=\"center\"><input type=\"submit\" value=\"";
        echo AdminLang::trans("global.savechanges");
        echo "\" class=\"btn btn-primary\"> <input type=\"button\" value=\"";
        echo AdminLang::trans("global.cancelchanges");
        echo "\" class=\"btn btn-default\" onclick=\"history.go(-1)\" /></p>\n\n</form>\n\n";
    }
}
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();

?>