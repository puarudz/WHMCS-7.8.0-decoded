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
$aInt->title = $aInt->lang("system", "activitylog");
$aInt->sidebar = "utilities";
$aInt->icon = "logs";
ob_start();
echo $aInt->beginAdminTabs(array($aInt->lang("global", "searchfilter")));
echo "\n<form method=\"post\" action=\"systemactivitylog.php\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n    <tr>\n        <td width=\"15%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "date");
echo "</td>\n        <td class=\"fieldarea\">\n            <div class=\"form-group date-picker-prepend-icon\">\n                <label for=\"inputDate\" class=\"field-icon\">\n                    <i class=\"fal fa-calendar-alt\"></i>\n                </label>\n                <input id=\"inputDate\"\n                       type=\"text\"\n                       name=\"date\"\n                       value=\"";
echo App::getFromRequest("date");
echo "\"\n                       class=\"form-control date-picker-single\"\n                />\n            </div>\n        </td>\n        <td width=\"15%\" class=\"fieldlabel\">\n            ";
echo $aInt->lang("fields", "username");
echo "        </td>\n        <td class=\"fieldarea\">\n            <select name=\"username\" class=\"form-control select-inline\">\n                <option value=\"\">";
echo $aInt->lang("global", "any");
echo "</option>";
$query = "SELECT DISTINCT user FROM tblactivitylog ORDER BY user ASC";
$result = full_query($query);
while ($data = mysql_fetch_array($result)) {
    $user = $data["user"];
    echo "<option";
    if ($user == $whmcs->get_req_var("username")) {
        echo " selected";
    }
    echo ">" . $user . "</option>";
}
echo "            </select>\n        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">";
echo $aInt->lang("fields", "description");
echo "</td>\n        <td class=\"fieldarea\">\n            <input type=\"text\" name=\"description\" value=\"";
echo $whmcs->get_req_var("description");
echo "\" class=\"form-control\">\n        </td>\n        <td class=\"fieldlabel\">\n            ";
echo $aInt->lang("fields", "ipaddress");
echo "        </td>\n        <td class=\"fieldarea\">\n            <input type=\"text\" name=\"ipaddress\" value=\"";
echo $whmcs->get_req_var("ipaddress");
echo "\" class=\"form-control input-150\">\n        </td>\n    </tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
echo $aInt->lang("system", "filterlog");
echo "\" class=\"btn btn-default\" />\n</div>\n\n</form>\n\n";
echo $aInt->endAdminTabs();
echo "\n<br />\n\n";
$aInt->sortableTableInit("date");
$log = new WHMCS\Log\Activity();
$log->prune();
$log->setCriteria(array("date" => $whmcs->get_req_var("date"), "username" => $whmcs->get_req_var("username"), "description" => $whmcs->get_req_var("description"), "ipaddress" => $whmcs->get_req_var("ipaddress")));
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