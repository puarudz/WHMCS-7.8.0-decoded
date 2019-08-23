<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Configure Banned Emails");
$aInt->title = $aInt->lang("bans", "emailtitle");
$aInt->sidebar = "config";
$aInt->icon = "configbans";
$aInt->helplink = "Security/Ban Control";
$aInt->requireAuthConfirmation();
if ($email) {
    check_token("WHMCS.admin.default");
    insert_query("tblbannedemails", array("domain" => $email));
    logAdminActivity("Banned Email Domain Added: '" . $email . "'");
    redir("success=true");
}
if ($action == "delete") {
    check_token("WHMCS.admin.default");
    $id = (int) $whmcs->get_req_var("id");
    $record = Illuminate\Database\Capsule\Manager::table("tblbannedemails")->find($id, array("domain"));
    delete_query("tblbannedemails", array("id" => $id));
    logAdminActivity("Banned Email Domain Removed: '" . $record->domain . "'");
    redir("delete=true");
}
ob_start();
if ($success) {
    infoBox($aInt->lang("bans", "emailaddsuccess"), $aInt->lang("bans", "emailaddsuccessinfo"));
}
if ($delete) {
    infoBox($aInt->lang("bans", "emaildelsuccess"), $aInt->lang("bans", "emaildelsuccessinfo"));
}
echo $infobox;
$aInt->deleteJSConfirm("doDelete", "bans", "emaildelsure", "?action=delete&id=");
echo $aInt->beginAdminTabs(array($aInt->lang("global", "add")), true);
echo "\n<form method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
echo "\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"15%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "email");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"email\" size=\"50\"> (";
echo $aInt->lang("bans", "onlydomain");
echo ")</td></tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
echo $aInt->lang("bans", "addbannedemail");
echo "\" class=\"btn btn-primary\" />\n</div>\n\n</form>\n\n";
echo $aInt->endAdminTabs();
echo "\n<br>\n\n";
$aInt->sortableTableInit("nopagination");
$result = select_query("tblbannedemails", "", "", "domain", "ASC");
while ($data = mysql_fetch_array($result)) {
    $id = $data["id"];
    $domain = $data["domain"];
    $count = $data["count"];
    $tabledata[] = array($domain, $count, "<a href=\"#\" onClick=\"doDelete('" . $id . "');return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\"></a>");
}
echo $aInt->sortableTable(array($aInt->lang("bans", "emaildomain"), $aInt->lang("bans", "usagecount"), ""), $tabledata);
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();

?>