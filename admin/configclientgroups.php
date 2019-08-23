<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Configure Client Groups");
$aInt->title = $aInt->lang("clientgroups", "title");
$aInt->sidebar = "config";
$aInt->icon = "clients";
$aInt->helplink = "Client Groups";
if ($action == "savegroup") {
    check_token("WHMCS.admin.default");
    $id = insert_query("tblclientgroups", array("groupname" => $groupname, "groupcolour" => $groupcolour, "discountpercent" => $discountpercent, "susptermexempt" => $susptermexempt, "separateinvoices" => $separateinvoices));
    logAdminActivity("Client Group Created: " . $groupname . " - Client Group ID: " . $id);
    redir("added=true");
}
if ($action == "updategroup") {
    check_token("WHMCS.admin.default");
    $changes = array();
    $group = Illuminate\Database\Capsule\Manager::table("tblclientgroups")->find($groupid);
    update_query("tblclientgroups", array("groupname" => $groupname, "groupcolour" => $groupcolour, "discountpercent" => $discountpercent, "susptermexempt" => $susptermexempt, "separateinvoices" => $separateinvoices), array("id" => $groupid));
    if ($discountpercent != $group->discountpercent) {
        $changes[] = "Discount Percentage Changed from '" . $group->discountpercent . "' to '" . $discountpercent . "'";
    }
    if ($susptermexempt != $group->susptermexempt) {
        if ($susptermexempt) {
            $changes[] = "Suspend/Termination Exemption Enabled";
        } else {
            $changes[] = "Suspend/Termination Exemption Disabled";
        }
    }
    if ($separateinvoices != $group->separateinvoices) {
        if ($separateinvoices) {
            $changes[] = "Separate Invoices Enabled";
        } else {
            $changes[] = "Separate Invoices Disabled";
        }
    }
    if ($changes) {
        $changes = " - " . implode(". ", $changes);
    } else {
        $changes = "";
    }
    logAdminActivity("Client Group Modified: " . $groupname . $changes . " - Client Group ID: " . $groupid);
    redir("update=true");
}
if ($action == "delete") {
    check_token("WHMCS.admin.default");
    $result = select_query("tblclients", "", array("groupid" => $id));
    $numaccounts = mysql_num_rows($result);
    if (0 < $numaccounts) {
        redir("deleteerror=true");
    } else {
        $groupName = Illuminate\Database\Capsule\Manager::table("tblclientgroups")->find($id, array("groupname"))->groupname;
        delete_query("tblclientgroups", array("id" => $id));
        foreach (array("domainregister", "domaintransfer", "domainrenew") as $type) {
            delete_query("tblpricing", array("type" => $type, "tsetupfee" => $id));
        }
        logAdminActivity("Client Group Deleted: " . $groupName . " - Client Group ID: " . $id);
        redir("deletesuccess=true");
    }
}
if ($action == "edit") {
    $result = select_query("tblclientgroups", "", array("id" => $id));
    $data = mysql_fetch_assoc($result);
    foreach ($data as $name => $value) {
        ${$name} = $value;
    }
}
ob_start();
if ($added) {
    infoBox($aInt->lang("clientgroups", "addsuccess"), $aInt->lang("clientgroups", "addsuccessinfo"));
}
if ($update) {
    infoBox($aInt->lang("clientgroups", "editsuccess"), $aInt->lang("clientgroups", "editsuccessinfo"));
}
if ($deletesuccess) {
    infoBox($aInt->lang("clientgroups", "delsuccess"), $aInt->lang("clientgroups", "delsuccessinfo"));
}
if ($deleteerror) {
    infoBox($aInt->lang("global", "erroroccurred"), $aInt->lang("clientgroups", "delerrorinfo"));
}
echo $infobox;
$jscode = "function doDelete(id) {\nif (confirm(\"" . $aInt->lang("clientgroups", "delsure") . "\")) {\nwindow.location='" . $_SERVER["PHP_SELF"] . "?action=delete&id='+id+'" . generate_token("link") . "';\n}}";
echo "\n<p>";
echo $aInt->lang("clientgroups", "info");
echo "</p>\n\n";
$aInt->sortableTableInit("nopagination");
$result = select_query("tblclientgroups", "", "");
while ($data = mysql_fetch_assoc($result)) {
    $suspterm = $data["susptermexempt"] == "on" ? $aInt->lang("global", "yes") : $aInt->lang("global", "no");
    $separateinv = $data["separateinvoices"] == "on" ? $aInt->lang("global", "yes") : $aInt->lang("global", "no");
    $groupcol = $data["groupcolour"] ? "<div style=\"width:75px;background-color:" . $data["groupcolour"] . "\">" . $aInt->lang("clientgroups", "sample") . "</div>" : "";
    $tabledata[] = array($data["groupname"], $groupcol, $data["discountpercent"], $suspterm, $separateinv, "<a href=\"" . $_SERVER["PHP_SELF"] . "?action=edit&id=" . $data["id"] . "\"><img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "edit") . "\"></a>", "<a href=\"#\" onClick=\"doDelete('" . $data["id"] . "');return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\"></a>");
}
echo $aInt->sortableTable(array($aInt->lang("clientgroups", "groupname"), $aInt->lang("clientgroups", "groupcolour"), $aInt->lang("clientgroups", "perdiscount"), $aInt->lang("clientgroups", "susptermexempt"), $aInt->lang("clients", "separateinvoices"), "", ""), $tabledata);
$setaction = $action == "edit" ? "updategroup" : "savegroup";
echo WHMCS\View\Asset::jsInclude("jquery.miniColors.js") . WHMCS\View\Asset::cssInclude("jquery.miniColors.css");
echo "\n";
$jquerycode = "\$(\".colorpicker\").miniColors();";
echo "\n<h2>";
if ($action == "edit") {
    echo $aInt->lang("global", "edit");
} else {
    echo $aInt->lang("global", "add");
}
echo " ";
echo $aInt->lang("clientgroups", "clientgroup");
echo "</h2>\n\n<form method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
echo "?action=";
echo $setaction;
echo "\">\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"25%\" class=\"fieldlabel\">";
echo $aInt->lang("clientgroups", "groupname");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"groupname\" value=\"";
echo $groupname;
echo "\" class=\"form-control input-400\" /></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("clientgroups", "groupcolour");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"groupcolour\" value=\"";
echo $groupcolour;
echo "\" class=\"form-control input-100 input-inline colorpicker\" /></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("clientgroups", "grpdispercent");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"discountpercent\" value=\"";
echo $discountpercent;
echo "\" placeholder=\"0\" class=\"form-control input-100\" /></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("clientgroups", "exemptsusterm");
echo "</td><td class=\"fieldarea\"><input type=\"checkbox\" name=\"susptermexempt\"";
if ($susptermexempt) {
    echo "checked";
}
echo " /></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("clients", "separateinvoicesdesc");
echo "</td><td class=\"fieldarea\"><input type=\"checkbox\" name=\"separateinvoices\"";
if ($separateinvoices) {
    echo "checked";
}
echo " /></td></tr>\n<input type=\"hidden\" name=\"groupid\" value=\"";
echo $id;
echo "\" />\n</table>\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
echo $aInt->lang("global", "savechanges");
echo "\" class=\"btn btn-primary\" />\n</div>\n</form>\n\n";
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();

?>