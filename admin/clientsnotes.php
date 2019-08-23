<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
if ($action == "edit" || $action == "parseMarkdown") {
    $reqperm = "Add/Edit Client Notes";
} else {
    $reqperm = "View Clients Notes";
}
$aInt = new WHMCS\Admin($reqperm);
$aInt->setClientsProfilePresets();
if ($action == "parseMarkdown") {
    $markup = new WHMCS\View\Markup\Markup();
    $content = App::get_req_var("content");
    $aInt->setBodyContent(array("body" => "<div class=\"markdown-content\">" . $markup->transform($content, "markdown") . "</div>"));
    $aInt->output();
    WHMCS\Terminus::getInstance()->doExit();
}
$userId = $aInt->valUserID($whmcs->get_req_var("userid"));
$id = (int) $id;
$aInt->assertClientBoundary($userId);
$aInt->addMarkdownEditor("clientNote", "client_note_" . md5($userId . WHMCS\Session::get("adminid")), "note");
if ($sub == "add") {
    check_token("WHMCS.admin.default");
    checkPermission("Add/Edit Client Notes");
    $note = App::getFromRequest("note");
    $mentionedAdminIds = WHMCS\Mentions\Mentions::getIdsForMentions($note);
    insert_query("tblnotes", array("userid" => $userId, "adminid" => $_SESSION["adminid"], "created" => "now()", "modified" => "now()", "note" => $note, "sticky" => $sticky));
    if ($mentionedAdminIds) {
        WHMCS\Mentions\Mentions::sendNotification("note", $userId, $note, $mentionedAdminIds);
    }
    logActivity("Added Note - User ID: " . $userId, $userId);
    redir("userid=" . $userId);
} else {
    if ($sub == "save") {
        check_token("WHMCS.admin.default");
        checkPermission("Add/Edit Client Notes");
        $noteUserId = (int) get_query_val("tblnotes", "userid", array("id" => $id));
        if ($noteUserId == $userId) {
            update_query("tblnotes", array("note" => $note, "sticky" => $sticky, "modified" => "now()"), array("id" => $id));
            logActivity("Updated Note - User ID: " . $userId . " - ID: " . $id, $userId);
        }
        redir("userid=" . $userId);
    } else {
        if ($sub == "delete") {
            check_token("WHMCS.admin.default");
            checkPermission("Delete Client Notes");
            $noteUserId = (int) get_query_val("tblnotes", "userid", array("id" => $id));
            if ($noteUserId == $userId) {
                delete_query("tblnotes", array("id" => $id, "userid" => $userId));
                logActivity("Deleted Note - User ID: " . $userId . " - ID: " . $id, $userId);
            }
            redir("userid=" . $userId);
        }
    }
}
$aInt->deleteJSConfirm("doDelete", "clients", "deletenote", "clientsnotes.php?userid=" . $userId . "&sub=delete&id=");
ob_start();
$aInt->sortableTableInit("created", "ASC");
$result = select_query("tblnotes", "COUNT(*)", array("userid" => $userId), "created", "ASC", "", "tbladmins ON tbladmins.id=tblnotes.adminid");
$data = mysql_fetch_array($result);
$numrows = $data[0];
$markup = new WHMCS\View\Markup\Markup();
$result = select_query("tblnotes", "tblnotes.*,(SELECT CONCAT(firstname,' ',lastname) FROM tbladmins WHERE tbladmins.id=tblnotes.adminid) AS adminuser", array("userid" => $userId), "modified", "DESC");
while ($data = mysql_fetch_array($result)) {
    $noteid = $data["id"];
    $created = $data["created"];
    $modified = $data["modified"];
    $note = $data["note"];
    $admin = $data["adminuser"];
    if (!$admin) {
        $admin = "Admin Deleted";
    }
    $markupFormat = $markup->determineMarkupEditor("client_note", "", $modified);
    $note = $markup->transform($note, $markupFormat);
    $mentions = WHMCS\Mentions\Mentions::getMentionReplacements($note);
    if (0 < count($mentions)) {
        $note = str_replace($mentions["find"], $mentions["replace"], $note);
    }
    $created = fromMySQLDate($created, "time");
    $modified = fromMySQLDate($modified, "time");
    $importantnote = $data["sticky"] ? "high" : "low";
    $tabledata[] = array($created, $note, $admin, $modified, "<img src=\"images/" . $importantnote . "priority.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("clientsummary", "importantnote") . "\">", "<a href=\"?userid=" . $userId . "&action=edit&id=" . $noteid . "\"><img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "edit") . "\"></a>", "<a href=\"#\" onClick=\"doDelete('" . $noteid . "');return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\"></a>");
}
echo $aInt->sortableTable(array($aInt->lang("fields", "created"), $aInt->lang("fields", "note"), $aInt->lang("fields", "admin"), $aInt->lang("fields", "lastmodified"), "", "", ""), $tabledata);
echo "\n<br>\n\n";
if ($action == "edit") {
    $notesdata = get_query_vals("tblnotes", "note, sticky", array("userid" => $userId, "id" => $id));
    $note = $notesdata["note"];
    $importantnote = $notesdata["sticky"] ? " checked" : "";
    echo "<form method=\"post\" action=\"";
    echo $whmcs->getPhpSelf();
    echo "?userid=";
    echo $userId;
    echo "&sub=save&id=";
    echo $id;
    echo "\" data-no-clear=\"false\">\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldarea\">\n        <textarea id=\"note\" name=\"note\" rows=\"6\" class=\"form-control\">";
    echo $note;
    echo "</textarea>\n    </td>\n    <td align=\"center\" width=\"150\">\n    <input type=\"submit\" value=\"";
    echo $aInt->lang("global", "savechanges");
    echo "\" class=\"btn btn-primary\"><br />\n    <div class=\"text-left top-margin-5\">\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" class=\"checkbox\" name=\"sticky\" value=\"1\"";
    echo $importantnote;
    echo " />\n            ";
    echo $aInt->lang("clientsummary", "stickynotescheck");
    echo "        </label>\n    </div>\n</td></tr>\n</table>\n</form>\n";
} else {
    echo "<form method=\"post\" action=\"";
    echo $whmcs->getPhpSelf();
    echo "?userid=";
    echo $userId;
    echo "&sub=add\" data-no-clear=\"false\">\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldarea\"><textarea id=\"note\" name=\"note\" rows=\"6\" class=\"form-control\"></textarea></td><td width=\"150\" class=\"text-center\">\n    <input type=\"submit\" value=\"";
    echo $aInt->lang("global", "addnew");
    echo "\" class=\"btn btn-primary\" /><br />\n    <div class=\"text-left top-margin-5\">\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" class=\"checkbox\" name=\"sticky\" value=\"1\" />\n            ";
    echo $aInt->lang("clientsummary", "stickynotescheck");
    echo "        </label>\n    </div>\n</td></tr>\n</table>\n</form>\n";
}
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();

?>