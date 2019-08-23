<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("System Cleanup Operations");
$aInt->title = $aInt->lang("system", "cleanupoperations");
$aInt->sidebar = "utilities";
$aInt->icon = "cleanup";
$aInt->helplink = "System Utilities#System Cleanup";
$action = App::getFromRequest("action");
$date = App::getFromRequest("date");
ob_start();
if ($action == "pruneclientactivity" && $date) {
    check_token("WHMCS.admin.default");
    $sqldate = toMySQLDate($date);
    $query = "DELETE FROM tblactivitylog WHERE userid>0 AND date<'" . db_escape_string($sqldate) . "'";
    $result = full_query($query);
    infoBox($aInt->lang("system", "cleanupsuccess"), $aInt->lang("system", "deleteactivityinfo") . " " . $date . " (" . mysql_affected_rows() . ")");
    logActivity("Cleanup Operation: Pruned Client Activity Logs from before " . $date);
}
if ($action == "deletemessages" && $date) {
    check_token("WHMCS.admin.default");
    $sqldate = toMySQLDate($date);
    $query = "DELETE FROM tblemails WHERE date<'" . db_escape_string($sqldate) . "'";
    $result = full_query($query);
    infoBox($aInt->lang("system", "cleanupsuccess"), $aInt->lang("system", "deletemessagesinfo") . " " . $date . " (" . mysql_affected_rows() . ")");
    logActivity("Cleanup Operation: Pruned Messages Sent before " . $date);
}
if ($action == "cleargatewaylog") {
    check_token("WHMCS.admin.default");
    $query = "TRUNCATE tblgatewaylog";
    $result = full_query($query);
    infoBox($aInt->lang("system", "cleanupsuccess"), $aInt->lang("system", "deletegatewaylog"));
    logActivity("Cleanup Operation: Gateway Log Emptied");
}
if ($action == "clearmailimportlog") {
    check_token("WHMCS.admin.default");
    $attachments = WHMCS\Database\Capsule::table("tblticketmaillog")->where("attachment", "!=", "")->pluck("attachment");
    try {
        $attachmentStorage = Storage::ticketAttachments();
        foreach ($attachments as $attachmentList) {
            $attachmentSet = explode("|", $attachmentList);
            foreach ($attachmentSet as $attachment) {
                $attachment = trim($attachment);
                if ($attachment) {
                    try {
                        $attachmentStorage->deleteAllowNotPresent($attachment);
                    } catch (Exception $e) {
                    }
                }
            }
        }
    } catch (Exception $e) {
    }
    $query = "TRUNCATE tblticketmaillog";
    $result = full_query($query);
    infoBox($aInt->lang("system", "cleanupsuccess"), $aInt->lang("system", "deleteticketlog"));
    logActivity("Cleanup Operation: Ticket Mail Import Log Emptied");
}
if ($action == "clearwhoislog") {
    check_token("WHMCS.admin.default");
    $query = "TRUNCATE tblwhoislog";
    $result = full_query($query);
    infoBox($aInt->lang("system", "cleanupsuccess"), $aInt->lang("system", "deletewhoislog"));
    logActivity("Cleanup Operation: WHOIS Lookup Log Emptied");
}
if ($action == "emptytemplatecache") {
    check_token("WHMCS.admin.default");
    $smarty = new WHMCS\Smarty();
    $smarty->clearAllCaches();
    infoBox($aInt->lang("system", "cleanupsuccess"), $aInt->lang("system", "deletecacheinfo"));
    logActivity("Cleanup Operation: Template Cache Emptied");
}
if ($action == "deleteattachments" && $date) {
    check_token("WHMCS.admin.default");
    $count = $total = 0;
    $limitHit = false;
    $error = "";
    if (!$date) {
        $error = "Please enter a date to remove attachments.";
    }
    if ($date) {
        if (!function_exists("removeAttachmentsFromClosedTickets")) {
            require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "ticketfunctions.php";
        }
        $date = WHMCS\Carbon::parseDateRangeValue($date);
        $date = $date["from"];
        $data = removeAttachmentsFromClosedTickets($date);
        $count = $data["removed"];
        if ($count === 0 && !empty($data["error"])) {
            $error = $data["error"];
        }
        $limitHit = $data["limitHit"];
        $description = "Cleanup Operation: Automated Prune Ticket Attachments: ";
        $description .= "Removed " . $count . " Attachments from Tickets Closed before ";
        $description .= $date->toAdminDateFormat();
        logAdminActivity($description);
        $title = "system.cleanupsuccess";
        $langString = "system.deleteattachinfo";
        $status = "info";
        if ($limitHit) {
            $langString = "system.deletedAttachmentsLimitHit";
        }
    }
    if ($error) {
        $title = "global.erroroccurred";
        $langString = $error;
        $status = "error";
    }
    infoBox(AdminLang::trans($title), AdminLang::trans($langString, array(":date" => $date->toAdminDateFormat())), $status);
}
$attachmentsStorage = Storage::ticketAttachments();
$ticketAttachments = WHMCS\File\FileAssetCollection::forAssetType(WHMCS\File\FileAsset::TYPE_TICKET_ATTACHMENTS);
$attachmentssize = 0;
if ($attachmentsStorage->isLocalAdapter()) {
    foreach ($ticketAttachments as $file) {
        try {
            $attachmentssize += $attachmentsStorage->getSize($file);
        } catch (Exception $e) {
        }
    }
    if (0 < $attachmentssize) {
        $attachmentssize /= 1024 * 1024;
        $attachmentssize = round($attachmentssize, 2);
    }
}
echo $infobox;
echo "\n<p>";
echo $aInt->lang("system", "cleanupdescription");
echo "</p>\n\n<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\"><tr><td width=\"49%\">\n\n<div class=\"contentbox\">\n<form method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
echo "\"><input type=\"hidden\" name=\"action\" value=\"cleargatewaylog\" />\n<b>";
echo $aInt->lang("system", "emptygwlog");
echo "</b> <input id=\"system-empty-gateway-log\" type=\"submit\" value=\" ";
echo $aInt->lang("global", "go");
echo " &raquo; \" class=\"button btn btn-default\" />\n</form>\n</div>\n\n<br>\n\n<div class=\"contentbox\">\n<form method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
echo "\"><input type=\"hidden\" name=\"action\" value=\"clearmailimportlog\" />\n<b>";
echo $aInt->lang("system", "emptytmlog");
echo "</b> <input id=\"system-empty-ticket-mail-input-log\"  type=\"submit\" value=\" ";
echo $aInt->lang("global", "go");
echo " &raquo; \" class=\"button btn btn-default\" />\n</form>\n</div>\n\n</td><td width=\"2%\"></td><td width=\"49%\">\n\n<div class=\"contentbox\">\n<form method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
echo "\"><input type=\"hidden\" name=\"action\" value=\"clearwhoislog\" />\n<b>";
echo $aInt->lang("system", "emptywllog");
echo "</b> <input id=\"system-empty-whois-lookup-log\"  type=\"submit\" value=\" ";
echo $aInt->lang("global", "go");
echo " &raquo; \" class=\"button btn btn-default\" />\n</form>\n</div>\n\n<br>\n\n<div class=\"contentbox\">\n<form method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
echo "\"><input type=\"hidden\" name=\"action\" value=\"emptytemplatecache\" />\n<b>";
echo $aInt->lang("system", "emptytc");
echo "</b> <input id=\"system-empty-template-cache\"  type=\"submit\" value=\" ";
echo $aInt->lang("global", "go");
echo " &raquo; \" class=\"button btn btn-default\" />\n</form>\n</div>\n\n</td></tr></table>\n\n<br>\n\n<div class=\"contentbox\">\n<form method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
echo "?action=pruneclientactivity\">\n<b>";
echo $aInt->lang("system", "prunecal");
echo "</b><br>\n";
$result = select_query("tblactivitylog", "COUNT(*)", "userid>0");
$data = mysql_fetch_array($result);
$num_rows = $data[0];
echo $aInt->lang("system", "totallogentries") . ": <b>" . $num_rows . "</b>";
echo "<br>\n    <div class=\"form-group date-picker-prepend-icon\">\n        ";
echo AdminLang::trans("system.deleteentriesbefore");
echo ":\n        <label for=\"system-empty-activity-log-date\" class=\"field-icon\">\n            <i class=\"fal fa-calendar-alt\"></i>\n        </label>\n        <input id=\"system-empty-activity-log-date\"\n               type=\"text\"\n               name=\"date\"\n               value=\"\"\n               class=\"form-control input-inline date-picker-single\"\n        />\n    </div>\n    <button id=\"system-empty-activity-log-delete\"  type=\"submit\" class=\"button btn btn-default\">\n        ";
echo $aInt->lang("global", "delete");
echo "    </button>\n</form>\n</div>\n\n<br>\n\n<div class=\"contentbox\">\n<form method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
echo "?action=deletemessages\">\n<b>";
echo $aInt->lang("system", "prunese");
echo "</b><br>\n";
$result = select_query("tblemails", "COUNT(*)", "");
$data = mysql_fetch_array($result);
$num_rows = $data[0];
echo $aInt->lang("system", "totalsavedemails") . ": <b>" . $num_rows . "</b>";
echo "<br>\n    <div class=\"form-group date-picker-prepend-icon\">\n        ";
echo AdminLang::trans("system.deletemailsbefore");
echo ":\n        <label for=\"system-empty-saved-emails-date\" class=\"field-icon\">\n            <i class=\"fal fa-calendar-alt\"></i>\n        </label>\n        <input id=\"system-empty-saved-emails-date\"\n               type=\"text\"\n               name=\"date\"\n               value=\"\"\n               class=\"form-control input-inline date-picker-single\"\n        />\n    </div>\n    <button id=\"system-empty-saved-emails-delete\"  type=\"submit\" class=\"button btn btn-default\">\n        ";
echo $aInt->lang("global", "delete");
echo "    </button>\n</div>\n</form>\n<br>\n\n<div class=\"contentbox\">\n<form method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
echo "?action=deleteattachments\">\n<b>";
echo $aInt->lang("system", "pruneoa");
echo "</b><br>\n";
echo $aInt->lang("system", "nosavedattachments") . ": <b>" . $ticketAttachments->count() . "</b>";
if ($ticketAttachments) {
    echo "<br>" . $aInt->lang("system", "filesizesavedatt") . ": <b>" . $attachmentssize . " " . $aInt->lang("fields", "mb") . "</b>";
}
echo "<br>\n    <div class=\"form-group date-picker-prepend-icon\">\n        ";
echo AdminLang::trans("system.deleteattachbefore");
echo ":\n        <label for=\"system-prune-attachments-before\" class=\"field-icon\">\n            <i class=\"fal fa-calendar-alt\"></i>\n        </label>\n        <input id=\"system-prune-attachments-before\"\n               type=\"text\"\n               name=\"date\"\n               value=\"\"\n               data-drops=\"up\"\n               class=\"form-control input-inline date-picker-single\"\n        />\n    </div>\n    <button id=\"system-empty-atachments-delete\" type=\"submit\" class=\"button btn btn-default\">\n        ";
echo AdminLang::trans("global.delete");
echo "    </button>\n</form>\n</div>\n\n";
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->display();

?>