<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("View Cancellation Requests");
$aInt->title = $aInt->lang("clients", "cancelrequests");
$aInt->sidebar = "clients";
$aInt->icon = "cancelrequests";
$aInt->helplink = "Cancellation Requests";
$completed = $whmcs->get_req_var("completed");
if ($action == "delete") {
    check_token("WHMCS.admin.default");
    delete_query("tblcancelrequests", array("id" => $id));
    redir();
}
$aInt->deleteJSConfirm("doDelete", "clients", "cancelrequestsdelete", "?action=delete&id=");
ob_start();
echo $aInt->beginAdminTabs(array($aInt->lang("global", "searchfilter")));
echo "\n<form action=\"";
echo $whmcs->getPhpSelf();
echo "\" method=\"get\"><input type=\"hidden\" name=\"filter\" value=\"true\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"15%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "reason");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"reason\" class=\"form-control input-300\" value=\"";
echo $reason;
echo "\" /></td><td width=\"15%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "client");
echo "</td><td class=\"fieldarea\">";
echo $aInt->clientsDropDown($userid, false, "userid", true);
echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "domain");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"domain\" class=\"form-control input-300\" value=\"";
echo $domain;
echo "\" /></td><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "type");
echo "</td><td class=\"fieldarea\"><select name=\"type\" class=\"form-control select-inline\"><option value=\"\">";
echo $aInt->lang("global", "any");
echo "</option><option value=\"Immediate\"";
if ($type == "Immediate") {
    echo " selected";
}
echo ">";
echo $aInt->lang("clients", "cancelrequestimmediate");
echo "</option><option value=\"End of Billing Period\"";
if ($type == "End of Billing Period") {
    echo " selected";
}
echo ">";
echo $aInt->lang("clients", "cancelrequestendofperiod");
echo "</option></select></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("mergefields", "serviceid");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"serviceid\" class=\"form-control input-100\" value=\"";
echo $serviceid;
echo "\" /></td><td class=\"fieldlabel\">&nbsp;</td><td class=\"fieldarea\">&nbsp;</td></tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"Filter\" class=\"btn btn-default\" />\n</div>\n\n</form>\n\n";
echo $aInt->endAdminTabs();
echo "\n<p>\n    <div class=\"btn-group\" role=\"group\">\n        <a href=\"";
echo $_SERVER["PHP_SELF"];
echo "\" class=\"btn btn-default";
if (!$completed) {
    echo " active";
}
echo "\">";
echo $aInt->lang("clients", "cancelrequestsopen");
echo "</a>\n        <a href=\"";
echo $_SERVER["PHP_SELF"];
echo "?completed=true\" class=\"btn btn-default";
if ($completed) {
    echo " active";
}
echo "\">";
echo $aInt->lang("clients", "cancelrequestscompleted");
echo "</a>\n    </div>\n</p>\n\n";
$aInt->sortableTableInit("date", "ASC");
$query = WHMCS\Database\Capsule::table("tblcancelrequests")->join("tblhosting", "tblhosting.id", "=", "tblcancelrequests.relid")->join("tblproducts", "tblproducts.id", "=", "tblhosting.packageid")->join("tblproductgroups", "tblproductgroups.id", "=", "tblproducts.gid")->join("tblclients", "tblclients.id", "=", "tblhosting.userid");
$filter = false;
if ($reason) {
    $query->where("tblcancelrequests.reason", "like", "%" . $reason . "%");
    $filter = true;
}
if ($domain) {
    $query->where("tblhosting.domain", "like", "%" . $domain . "%");
    $filter = true;
}
if ($userid) {
    $query->where("tblhosting.userid", "=", (int) $userid);
    $filter = true;
}
if ($serviceid) {
    $query->where("tblcancelrequests.relid", "=", (int) $serviceid);
    $filter = true;
}
if ($type) {
    $query->where("tblcancelrequests.type", "=", $type);
    $filter = true;
}
if (!$filter) {
    if ($completed) {
        $query->whereIn("tblhosting.domainstatus", array("Cancelled", "Terminated"));
    } else {
        $query->whereNotIn("tblhosting.domainstatus", array("Cancelled", "Terminated"));
    }
}
$numrows = $query->count();
$query->select(array("tblcancelrequests.*", "tblhosting.domain", "tblhosting.nextduedate", "tblproducts.name AS productname", "tblproductgroups.name AS groupname", "tblhosting.id AS productid", "tblhosting.userid", "tblclients.firstname", "tblclients.lastname", "tblclients.companyname", "tblclients.groupid"));
$query->limit($limit);
$offset = 0;
if (1 < $page) {
    $offset = (int) ($page * $limit);
}
$query->offset($offset);
$result = $query->get();
usort($result, function ($first, $second) {
    $firstCompare = $first->nextduedate;
    if (!$firstCompare || $first->type == "Immediate" || $firstCompare == "0000-00-00") {
        $firstCompare = $first->date;
    }
    $secondCompare = $second->nextduedate;
    if (!$secondCompare || $second->type == "Immediate" || $secondCompare == "0000-00-00") {
        $secondCompare = $second->date;
    }
    if ($firstCompare && 10 < strlen($firstCompare)) {
        $firstCompare = substr($firstCompare, 0, 10);
    }
    if ($secondCompare && 10 < strlen($secondCompare)) {
        $secondCompare = substr($secondCompare, 0, 10);
    }
    if (!$firstCompare || $firstCompare == "0000-00-00") {
        $firstCompare = date("Y-m-d");
    }
    if (!$secondCompare || $secondCompare == "0000-00-00") {
        $secondCompare = date("Y-m-d");
    }
    return strcmp($firstCompare, $secondCompare);
});
foreach ($result as $data) {
    $data = (array) $data;
    $id2 = $data["id"];
    $date = $data["date"];
    $relid = $data["relid"];
    $reason = $data["reason"];
    $cancelType = $data["type"];
    $date = fromMySQLDate($date, "time");
    $domain = $data["domain"];
    $productname = $data["productname"];
    $groupname = $data["groupname"];
    $productid = $data["productid"];
    $userid = $data["userid"];
    $firstname = $data["firstname"];
    $lastname = $data["lastname"];
    $companyname = $data["companyname"];
    $groupid = $data["groupid"];
    $nextduedate = $data["nextduedate"];
    $nextduedate = fromMySQLDate($nextduedate);
    $xname = "<a href=\"clientshosting.php?userid=" . $userid . "&id=" . $productid . "\">" . $groupname . " - " . $productname . "</a><br>" . $aInt->outputClientLink($userid, $firstname, $lastname, $companyname, $groupid);
    if ($domain) {
        $xname .= " (" . $domain . ")";
    }
    $cancellationDate = $nextduedate;
    $type = AdminLang::trans("clients.cancelrequestendofperiod");
    if ($cancelType == "Immediate") {
        $type = AdminLang::trans("clients.cancelrequestimmediate");
        $cancellationDate = fromMySQLDate($data["date"]);
    }
    $tabledata[] = array($date, $xname, "<textarea rows=\"3\" class=\"form-control\" readonly>" . $reason . "</textarea>", $type, $cancellationDate, "<a href=\"#\" onClick=\"doDelete('" . $id2 . "');return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\"></a>");
}
echo $aInt->sortableTable(array(AdminLang::trans("fields.date"), AdminLang::trans("fields.product"), AdminLang::trans("fields.reason"), AdminLang::trans("fields.type"), AdminLang::trans("global.cancellationDate"), ""), $tabledata);
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();

?>