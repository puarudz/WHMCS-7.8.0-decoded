<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Configure Security Questions");
$aInt->title = $aInt->lang("setup", "securityqs");
$aInt->sidebar = "config";
$aInt->icon = "securityquestions";
$aInt->helplink = "Security Questions";
$id = (int) $whmcs->get_req_var("id");
$action = $whmcs->get_req_var("action");
if ($action == "savequestion") {
    check_token("WHMCS.admin.default");
    $addquestion = $whmcs->get_req_var("addquestion");
    if ($id) {
        update_query("tbladminsecurityquestions", array("question" => encrypt($addquestion)), array("id" => $id));
        logAdminActivity("Security Question Modified - Security Question ID: " . $id);
        redir("update=true");
    } else {
        $id = insert_query("tbladminsecurityquestions", array("question" => encrypt($addquestion)));
        logAdminActivity("Security Question Created - Security Question ID: " . $id);
        redir("added=true");
    }
}
if ($action == "delete") {
    check_token("WHMCS.admin.default");
    $result = select_query("tblclients", "", array("securityqid" => $id));
    $numaccounts = mysql_num_rows($result);
    if (0 < $numaccounts) {
        redir("deleteerror=true");
    } else {
        delete_query("tbladminsecurityquestions", array("id" => $id));
        logAdminActivity("Security Question Deleted - Security Question ID: " . $id);
        redir("deletesuccess=true");
    }
}
ob_start();
if ($deletesuccess) {
    infoBox($aInt->lang("securityquestionconfig", "delsuccess"), $aInt->lang("securityquestionconfig", "delsuccessinfo"));
}
if ($deleteerror) {
    infoBox($aInt->lang("securityquestionconfig", "error"), $aInt->lang("securityquestionconfig", "errorinfo"));
}
if ($added) {
    infoBox($aInt->lang("securityquestionconfig", "addsuccess"), $aInt->lang("securityquestionconfig", "changesuccessinfo"));
}
if ($update) {
    infoBox($aInt->lang("securityquestionconfig", "changesuccess"), $aInt->lang("securityquestionconfig", "changesuccessinfo"));
}
echo $infobox;
$aInt->deleteJSConfirm("doDelete", "securityquestionconfig", "delsuresecurityquestion", "?action=delete&id=");
echo "\n<h2>";
echo $aInt->lang("securityquestionconfig", "questions");
echo "</h2>\n\n";
$aInt->sortableTableInit("nopagination");
$result = select_query("tbladminsecurityquestions", "", "");
while ($data = mysql_fetch_assoc($result)) {
    $count = select_query("tblclients", "count(securityqid) as cnt", array("securityqid" => $data["id"]));
    $count_data = mysql_fetch_assoc($count);
    $cnt = is_null($count_data["cnt"]) ? "0" : $count_data["cnt"];
    $tabledata[] = array(decrypt($data["question"]), $cnt, "<a href=\"" . $_SERVER["PHP_SELF"] . "?action=edit&id=" . $data["id"] . "\"><img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "edit") . "\"></a>", "<a href=\"#\" onClick=\"doDelete('" . $data["id"] . "');return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\"></a>");
}
echo $aInt->sortableTable(array($aInt->lang("securityquestionconfig", "question"), $aInt->lang("securityquestionconfig", "uses"), "", ""), $tabledata);
echo "\n<h2>";
if ($action == "edit") {
    $result = select_query("tbladminsecurityquestions", "", array("id" => $id));
    $data = mysql_fetch_array($result);
    $question = decrypt($data["question"]);
    echo $aInt->lang("securityquestionconfig", "edit");
} else {
    echo $aInt->lang("securityquestionconfig", "add");
}
echo "</h2>\n\n<form method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
echo "?action=savequestion&id=";
echo $id;
echo "\">\n    <table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n        <tr>\n            <td width=\"25%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "securityquestion");
echo "</td>\n            <td class=\"fieldarea\"><input type=\"text\" name=\"addquestion\" value=\"";
echo $question;
echo "\" class=\"form-control\" /></td>\n        </tr>\n    </table>\n    <div class=\"btn-container\">\n        <input type=\"submit\" value=\"";
echo $aInt->lang("global", "savechanges");
echo "\" class=\"btn btn-primary\" />\n    </div>\n</form>\n\n";
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();

?>