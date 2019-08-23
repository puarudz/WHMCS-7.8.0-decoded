<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Configure Order Statuses");
$aInt->title = $aInt->lang("setup", "orderstatuses");
$aInt->sidebar = "config";
$aInt->icon = "clients";
$aInt->helplink = "Order Statuses";
if ($action == "save") {
    check_token("WHMCS.admin.default");
    $id = (int) $whmcs->get_req_var("id");
    $title = $whmcs->get_req_var("title");
    $color = $whmcs->get_req_var("color");
    $showpending = $whmcs->get_req_var("showpending");
    $showactive = $whmcs->get_req_var("showactive");
    $showcancelled = $whmcs->get_req_var("showcancelled");
    $sortorder = (int) $whmcs->get_req_var("sortorder");
    if ($id) {
        $orderStatus = Illuminate\Database\Capsule\Manager::table("tblorderstatuses")->find($id);
        update_query("tblorderstatuses", array("title" => $title, "color" => $color, "showpending" => $showpending, "showactive" => $showactive, "showcancelled" => $showcancelled, "sortorder" => $sortorder), array("id" => $id));
        if ($title != $orderStatus->title) {
            logAdminActivity("Order Status Name Changed: " . $orderStatus->title . " to " . $title);
        }
        if ($color != $orderStatus->color || $showpending != $orderStatus->showpending || $showactive != $orderStatus->showactive || $showcancelled != $orderStatus->showcancelled || $sortorder != $orderStatus->sortorder) {
            logAdminActivity("Order Status Modified: " . $title);
        }
        redir("update=true");
    } else {
        insert_query("tblorderstatuses", array("title" => $title, "color" => $color, "showpending" => $showpending, "showactive" => $showactive, "showcancelled" => $showcancelled, "sortorder" => $sortorder));
        logAdminActivity("Order Status Created: " . $title);
        redir("added=true");
    }
}
if ($action == "delete") {
    check_token("WHMCS.admin.default");
    if (4 < $id) {
        $title = get_query_val("tblorderstatuses", "title", array("id" => $id));
        update_query("tblorders", array("status" => "Cancelled"), array("status" => $title));
        delete_query("tblorderstatuses", array("id" => $id));
        logAdminActivity("Order Status Removed: " . $title);
        redir("delete=true");
    } else {
        redir();
    }
}
ob_start();
if ($added) {
    infoBox($aInt->lang("orderstatusconfig", "addtitle"), $aInt->lang("orderstatusconfig", "adddesc"));
}
if ($update) {
    infoBox($aInt->lang("orderstatusconfig", "edittitle"), $aInt->lang("orderstatusconfig", "editdesc"));
}
if ($delete) {
    infoBox($aInt->lang("orderstatusconfig", "deltitle"), $aInt->lang("orderstatusconfig", "deldesc"));
}
echo $infobox;
$aInt->deleteJSConfirm("doDelete", "orderstatusconfig", "delsure", "?action=delete&id=");
echo "\n<p>";
echo $aInt->lang("orderstatusconfig", "pagedesc");
echo "</p>\n\n<p><a href=\"";
echo $whmcs->getPhpSelf();
echo "\" class=\"btn btn-default\"><i class=\"fas fa-plus-square\"></i> ";
echo $aInt->lang("global", "addnew");
echo "</a></p>\n\n";
$aInt->sortableTableInit("nopagination");
$result = select_query("tblorderstatuses", "", "", "sortorder", "ASC");
while ($data = mysql_fetch_assoc($result)) {
    $statusid = $data["id"];
    $title = $data["title"];
    $color = $data["color"];
    $showpending = $data["showpending"];
    $showactive = $data["showactive"];
    $showcancelled = $data["showcancelled"];
    $sortorder = $data["sortorder"];
    $showpending = $showpending ? "<img src=\"images/icons/tick.png\">" : "<img src=\"images/icons/disabled.png\">";
    $showactive = $showactive ? "<img src=\"images/icons/tick.png\">" : "<img src=\"images/icons/disabled.png\">";
    $showcancelled = $showcancelled ? "<img src=\"images/icons/tick.png\">" : "<img src=\"images/icons/disabled.png\">";
    if (4 < $statusid) {
        $delete = "<a href=\"#\" onClick=\"doDelete('" . $statusid . "');return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\"></a>";
    } else {
        $delete = "";
    }
    $tabledata[] = array("<span style=\"font-weight:bold;color:" . $color . "\">" . $title . "</span>", $showpending, $showactive, $showcancelled, $sortorder, "<a href=\"" . $_SERVER["PHP_SELF"] . "?action=edit&id=" . $statusid . "\"><img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "edit") . "\"></a>", $delete);
}
echo $aInt->sortableTable(array($aInt->lang("fields", "title"), $aInt->lang("orderstatusconfig", "includeinpending"), $aInt->lang("orderstatusconfig", "includeinactive"), $aInt->lang("orderstatusconfig", "includeincancelled"), $aInt->lang("products", "sortorder"), "", ""), $tabledata);
echo WHMCS\View\Asset::jsInclude("jquery.miniColors.js") . WHMCS\View\Asset::cssInclude("jquery.miniColors.css");
$jquerycode = "\$(\".colorpicker\").miniColors();";
echo "\n<h2>";
if ($action == "edit") {
    $data = get_query_vals("tblorderstatuses", "", array("id" => $id));
    extract($data);
    echo $aInt->lang("orderstatusconfig", "edit");
} else {
    $title = $showpending = $showactive = $showcancelled = "";
    $color = "#000000";
    echo $aInt->lang("orderstatusconfig", "addnew");
}
echo "</h2>\n\n<form method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
echo "?action=save&id=";
echo $id;
echo "\">\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"25%\" class=\"fieldlabel\">";
echo $aInt->lang("clientsummary", "filetitle");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"title\" size=\"30\" value=\"";
echo $title;
echo "\"";
if ($id && $id <= 4) {
    echo " readonly=\"true\"";
}
echo " /></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("orderstatusconfig", "color");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"color\" size=\"10\" value=\"";
echo $color;
echo "\" class=\"colorpicker\" /></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("orderstatusconfig", "includeinpending");
echo "</td><td class=\"fieldarea\"><input type=\"checkbox\" name=\"showpending\" value=\"1\"";
if ($showpending) {
    echo " checked";
}
echo " /></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("orderstatusconfig", "includeinactive");
echo "</td><td class=\"fieldarea\"><input type=\"checkbox\" name=\"showactive\" value=\"1\"";
if ($showactive) {
    echo " checked";
}
echo " /></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("orderstatusconfig", "includeincancelled");
echo "</td><td class=\"fieldarea\"><input type=\"checkbox\" name=\"showcancelled\" value=\"1\"";
if ($showcancelled) {
    echo " checked";
}
echo " /></td></tr>\n<tr><td width=\"25%\" class=\"fieldlabel\">";
echo $aInt->lang("products", "sortorder");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"sortorder\" size=\"10\" value=\"";
echo $sortorder;
echo "\" /></td></tr>\n</table>\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
echo $aInt->lang("global", "savechanges");
echo "\" class=\"btn btn-primary\" />\n</div>\n</form>\n\n";
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();

?>