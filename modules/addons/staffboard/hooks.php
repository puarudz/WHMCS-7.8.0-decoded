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
add_hook("AdminHomeWidgets", 1, "widget_staffboard_overview");
function widget_staffboard_overview($vars)
{
    $title = "Staff Noticeboard";
    $lastviews = get_query_val("tbladdonmodules", "value", array("module" => "staffboard", "setting" => "lastviewed"));
    if ($lastviews) {
        $lastviews = safe_unserialize($lastviews);
        $new = false;
    } else {
        $lastviews = array();
        $new = true;
    }
    $lastviewed = $lastviews[$_SESSION["adminid"]];
    $lastviews[$_SESSION["adminid"]] = time();
    if ($new) {
        insert_query("tbladdonmodules", array("module" => "staffboard", "setting" => "lastviewed", "value" => safe_serialize($lastviews)));
    } else {
        update_query("tbladdonmodules", array("value" => safe_serialize($lastviews)), array("module" => "staffboard", "setting" => "lastviewed"));
    }
    $numchanged = get_query_val("mod_staffboard", "COUNT(id)", "date>='" . date("Y-m-d H:i:s", $lastviewed) . "'");
    $content = "\n<style>\n.staffboardchanges {\n    margin: 0 0 5px 0;\n    padding: 8px 25px;\n    font-size: 1.2em;\n    text-align: center;\n}\n.staffboardnotices {\n    max-height: 130px;\n    overflow: auto;\n    border-top: 1px solid #ccc;\n    border-bottom: 1px solid #ccc;\n}\n.staffboardnotices div {\n    padding: 5px 15px;\n    border-bottom: 2px solid #fff;\n}\n.staffboardnotices div.pink {\n    background-color: #F3CBF3;\n}\n.staffboardnotices div.yellow {\n    background-color: #FFFFC1;\n}\n.staffboardnotices div.purple {\n    background-color: #DCD7FE;\n}\n.staffboardnotices div.white {\n    background-color: #FAFAFA;\n}\n.staffboardnotices div.pink {\n    background-color: #F3CBF3;\n}\n.staffboardnotices div.blue {\n    background-color: #A6E3FC;\n}\n.staffboardnotices div.green {\n    background-color: #A5F88B;\n}\n</style>\n<div class=\"staffboardchanges\">There are <strong>" . $numchanged . "</strong> New or Updated Staff Notices Since your Last Visit - <a href=\"addonmodules.php?module=staffboard\">Visit Noticeboard &raquo;</a></div><div class=\"staffboardnotices\">";
    $result = select_query("mod_staffboard", "", "", "date", "DESC");
    while ($data = mysql_fetch_array($result)) {
        $content .= "<div class=\"" . $data["color"] . "\">" . fromMySQLDate($data["date"], 1) . " - " . (100 < strlen($data["note"]) ? substr($data["note"], 0, 100) . "..." : $data["note"]) . "</div>";
    }
    $content .= "</div>";
    return array("title" => $title, "content" => $content, "jquerycode" => NULL);
}

?>