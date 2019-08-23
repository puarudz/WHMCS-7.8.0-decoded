<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Manage Quotes");
$aInt->requiredFiles(array("clientfunctions", "invoicefunctions"));
$aInt->setClientsProfilePresets();
$userId = $aInt->valUserID($whmcs->get_req_var("userid"));
$aInt->assertClientBoundary($userid);
if ($delete == "true") {
    check_token("WHMCS.admin.default");
    checkPermission("Manage Quotes");
    $quoteId = (int) $whmcs->get_req_var("quoteid");
    $quote = WHMCS\User\Client::find($userId)->quotes->find($quoteId);
    if ($quote) {
        $quote->delete();
        logActivity("Deleted Quote (ID: " . $quote->id . " - User ID: " . $userId . ")", $userId);
    }
    redir("userid=" . $userId);
}
ob_start();
$aInt->deleteJSConfirm("doDelete", "quotes", "deletesure", "?userid=" . $userid . "&delete=true&quoteid=");
echo "\n<div class=\"context-btn-container\">\n    <button type=\"button\" class=\"btn btn-primary\" onClick=\"window.location='quotes.php?action=manage&userid=";
echo $userid;
echo "'\">\n        <i class=\"fas fa-plus\"></i>\n        ";
echo $aInt->lang("quotes", "createnew");
echo "    </button>\n</div>\n\n";
$currency = getCurrency($userid);
$aInt->sortableTableInit("id", "DESC");
$result = select_query("tblquotes", "COUNT(*)", array("userid" => $userid));
$data = mysql_fetch_array($result);
$numrows = $data[0];
$result = select_query("tblquotes", "", array("userid" => $userid), $orderby, $order, $page * $limit . "," . $limit);
while ($data = mysql_fetch_assoc($result)) {
    $id = $data["id"];
    $subject = $data["subject"];
    $validuntil = $data["validuntil"];
    $datecreated = $data["datecreated"];
    $stage = $aInt->lang("status", str_replace(" ", "", strtolower($data["stage"])));
    $total = $data["total"];
    $validuntil = fromMySQLDate($validuntil);
    $datecreated = fromMySQLDate($datecreated);
    $total = formatCurrency($total);
    $tabledata[] = array("<a href=\"quotes.php?action=manage&id=" . $id . "\">" . $id . "</a>", $subject, $datecreated, $validuntil, $total, $stage, "<a href=\"quotes.php?action=manage&id=" . $id . "\"><img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "edit") . "\"></a>", "<a href=\"#\" onClick=\"doDelete('" . $id . "');return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\"></a>");
}
echo $aInt->sortableTable(array(array("id", $aInt->lang("quotes", "quotenum")), array("subject", $aInt->lang("quotes", "subject")), array("datecreated", $aInt->lang("quotes", "createdate")), array("validuntil", $aInt->lang("quotes", "validuntil")), array("total", $aInt->lang("fields", "total")), array("stage", $aInt->lang("quotes", "stage")), "", ""), $tabledata);
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();

?>