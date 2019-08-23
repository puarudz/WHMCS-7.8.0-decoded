<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Offline Credit Card Processing");
$aInt->title = $aInt->lang("offlineccp", "title");
$aInt->sidebar = "billing";
$aInt->icon = "offlinecc";
$aInt->requiredFiles(array("clientfunctions", "invoicefunctions", "gatewayfunctions", "ccfunctions"));
ob_start();
$aInt->sortableTableInit("duedate", "ASC");
$gatewaysarray = getGatewaysArray();
$query = "SELECT tblinvoices.*,tblclients.firstname,tblclients.lastname,tblclients.companyname,tblclients.groupid FROM tblinvoices INNER JOIN tblclients ON tblclients.id=tblinvoices.userid WHERE paymentmethod='offlinecc' AND tblinvoices.status='Unpaid' ORDER BY ";
if ($orderby == "clientname") {
    $query .= "firstname " . db_escape_string($order) . ", lastname";
} else {
    $query .= db_escape_string($orderby);
}
$query .= " " . db_escape_string($order);
$numresults = full_query($query);
$numrows = mysql_num_rows($numresults);
$query .= " LIMIT " . (int) ($page * $limit) . "," . (int) $limit;
$result = full_query($query);
while ($data = mysql_fetch_array($result)) {
    $id = $data["id"];
    $userid = $data["userid"];
    $date = $data["date"];
    $duedate = $data["duedate"];
    $total = $data["total"];
    $paymentmethod = $data["paymentmethod"];
    $paymentmethod = $gatewaysarray[$paymentmethod];
    $date = fromMySQLDate($date);
    $duedate = fromMySQLDate($duedate);
    $firstname = $data["firstname"];
    $lastname = $data["lastname"];
    $companyname = $data["companyname"];
    $groupid = $data["groupid"];
    $currency = getCurrency($userid);
    $total = formatCurrency($total);
    $openCcDetailsRoute = routePath("admin-billing-offline-cc-form", $id);
    $openCcDetailsLink = "    <a \n        href=\"" . $openCcDetailsRoute . "\"\n        data-modal-title=\"" . $aInt->lang("offlineccp", "title") . "\"\n        data-btn-submit-id=\"\"\n        data-btn-submit-label=\"\"\n        onclick=\"return false;\"\n        class=\"btn btn-default btn-sm open-modal\">\n            View Processing Window\n    </a>";
    $tabledata[] = array("<a href=\"invoices.php?action=edit&id=" . $id . "\">" . $id . "</a>", $aInt->outputClientLink($userid, $firstname, $lastname, $companyname, $groupid), $date, $duedate, $total, $openCcDetailsLink);
}
echo $aInt->sortableTable(array(array("id", $aInt->lang("fields", "id")), array("clientname", $aInt->lang("fields", "clientname")), array("date", $aInt->lang("fields", "invoicedate")), array("duedate", $aInt->lang("fields", "duedate")), array("total", $aInt->lang("fields", "total")), $aInt->lang("supportticketescalations", "actions")), $tabledata);
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->display();

?>