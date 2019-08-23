<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("View Module Debug Log");
$aInt->title = $aInt->lang("system", "moduledebuglog");
$aInt->sidebar = "utilities";
$aInt->icon = "logs";
$aInt->helplink = "Troubleshooting Module Problems";
if ($enable) {
    check_token("WHMCS.admin.default");
    if (isset($CONFIG["ModuleDebugMode"])) {
        update_query("tblconfiguration", array("value" => "on"), array("setting" => "ModuleDebugMode"));
    } else {
        insert_query("tblconfiguration", array("setting" => "ModuleDebugMode", "value" => "on"));
    }
    redir();
}
if ($disable) {
    check_token("WHMCS.admin.default");
    update_query("tblconfiguration", array("value" => ""), array("setting" => "ModuleDebugMode"));
    redir();
}
if ($reset) {
    check_token("WHMCS.admin.default");
    delete_query("tblmodulelog", "id!=''");
    redir();
}
if (!$id) {
    $aInt->sortableTableInit("id");
    $numrows = get_query_val("tblmodulelog", "COUNT(*)", "", "id", "DESC");
    $result = select_query("tblmodulelog", "", "", "id", "DESC", $page * $limit . "," . $limit);
    while ($data = mysql_fetch_array($result)) {
        $id = $data["id"];
        $date = $data["date"];
        $module = $data["module"];
        $action = $data["action"];
        $request = $data["request"];
        $response = $data["response"];
        $arrdata = $data["arrdata"];
        if ($arrdata) {
            $response = $arrdata;
        }
        $date = fromMySQLDate($date, "time");
        $tabledata[] = array("<a href=\"systemmodulelog.php?id=" . $id . "\">" . $date . "</a>", $module, $action, "<textarea rows=\"5\" class=\"form-control\">" . htmlentities($request) . "</textarea>", "<textarea rows=\"5\" class=\"form-control\">" . htmlentities($response) . "</textarea>");
    }
    $content = "<p>" . $aInt->lang("system", "moduledebuglogdesc") . "</p>\n<form method=\"post\" action=\"\">\n<p align=\"center\">";
    if ($CONFIG["ModuleDebugMode"]) {
        $content .= "<input type=\"submit\" name=\"disable\" value=\"" . $aInt->lang("system", "disabledebuglogging") . "\" class=\"btn btn-danger\" />";
    } else {
        $content .= "<input type=\"submit\" name=\"enable\" value=\"" . $aInt->lang("system", "enabledebuglogging") . "\" class=\"btn btn-success\" />";
    }
    $content .= " <input type=\"submit\" name=\"reset\" value=\"" . $aInt->lang("system", "resetdebuglogging") . "\" class=\"btn btn-default\" /></p>\n</form>\n" . $aInt->sortableTable(array(array("", $aInt->lang("fields", "date"), 120), array("", $aInt->lang("fields", "module"), 120), array("", $aInt->lang("fields", "action"), 150), $aInt->lang("fields", "request"), $aInt->lang("fields", "response")), $tabledata);
} else {
    $result = select_query("tblmodulelog", "", array("id" => $id));
    $data = mysql_fetch_array($result);
    $id = $data["id"];
    $date = $data["date"];
    $module = $data["module"];
    $action = $data["action"];
    $request = $data["request"];
    $response = $data["response"];
    $arrdata = $data["arrdata"];
    $date = fromMySQLDate($date, "time");
    $content = $aInt->lang("fields", "date") . ": " . $date . " - " . $aInt->lang("fields", "module") . ": " . $module . " - " . $aInt->lang("fields", "action") . ": " . $action . "<br /><br />\n<b>" . $aInt->lang("fields", "request") . "</b><br />\n<textarea rows=\"10\" style=\"width:100%;\">" . htmlentities($request) . "</textarea><br /><br />\n<b>" . $aInt->lang("fields", "response") . "</b><br />\n<textarea rows=\"20\" style=\"width:100%;\">" . htmlentities($response) . "</textarea><br /><br />";
    if ($arrdata) {
        $content .= "<b>" . $aInt->lang("fields", "interpretedresponse") . "</b><br />\n<textarea rows=\"20\" style=\"width:100%;\">" . htmlentities($arrdata) . "</textarea><br /><br />";
    }
    $content .= "<a href=\"systemmodulelog.php?\">&laquo; Back</a>";
}
$aInt->content = $content;
$aInt->display();

?>