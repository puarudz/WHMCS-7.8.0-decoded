<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("View Activity Log");
$aInt->setClientsProfilePresets();
$aInt->valUserID($userid);
$aInt->assertClientBoundary($userid);
ob_start();
echo "\n<form method=\"post\" action=\"clientslog.php?userid=";
echo $userid;
echo "\">\n\n<div class=\"context-btn-container\">\n    <input type=\"submit\" value=\"";
echo $aInt->lang("system", "filterlog");
echo "\" class=\"btn btn-default\" />\n</div>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n    <tr>\n        <td width=\"15%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "date");
echo "</td>\n        <td class=\"fieldarea\">\n            <div class=\"form-group date-picker-prepend-icon\">\n                <label for=\"inputDate\" class=\"field-icon\">\n                    <i class=\"fal fa-calendar-alt\"></i>\n                </label>\n                <input id=\"inputDate\"\n                       type=\"text\"\n                       name=\"date\"\n                       value=\"";
echo App::getFromRequest("date");
echo "\"\n                       class=\"form-control date-picker-single\"\n                />\n            </div>\n        </td>\n        <td width=\"15%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "description");
echo "</td>\n        <td class=\"fieldarea\"><input type=\"text\" name=\"description\" value=\"";
echo $whmcs->get_req_var("description");
echo "\" class=\"form-control input-300\"></td>\n    </tr>\n    <tr>\n        <td width=\"15%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "username");
echo "</td>\n        <td class=\"fieldarea\"><select name=\"username\" class=\"form-control select-inline\">\n            <option value=\"\">Any</option>\n";
$result = select_query("tblactivitylog", "DISTINCT user", "", "user", "ASC");
while ($data = mysql_fetch_array($result)) {
    $user = $data["user"];
    echo "<option";
    if ($user == $whmcs->get_req_var("username")) {
        echo " selected";
    }
    echo ">" . $user . "</option>";
}
echo "            </select></td>\n        <td width=\"15%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "ipaddress");
echo "</td>\n        <td class=\"fieldarea\"><input type=\"text\" name=\"ipaddress\" value=\"";
echo $whmcs->get_req_var("ipaddress");
echo "\" class=\"form-control input-150\"></td>\n    </tr>\n</table>\n\n</form>\n\n<br />\n\n";
$aInt->sortableTableInit("date");
$log = new WHMCS\Log\Activity();
$log->setCriteria(array("userid" => $userid, "date" => $whmcs->get_req_var("date"), "username" => $whmcs->get_req_var("username"), "description" => $whmcs->get_req_var("description"), "ipaddress" => $whmcs->get_req_var("ipaddress")));
$numrows = $log->getTotalCount();
$tabledata = array();
$logs = $log->getLogEntries($whmcs->get_req_var("page"));
foreach ($logs as $entry) {
    $tabledata[] = array($entry["date"], "<div align=\"left\">" . $entry["description"] . "</div>", $entry["username"], $entry["ipaddress"]);
}
echo $aInt->sortableTable(array($aInt->lang("fields", "date"), $aInt->lang("fields", "description"), $aInt->lang("fields", "username"), $aInt->lang("fields", "ipaddress")), $tabledata);
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->display();

?>