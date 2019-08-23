<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("View Gateway Log");
$aInt->title = $aInt->lang("gatewaytranslog", "gatewaytranslogtitle");
$aInt->sidebar = "billing";
$aInt->icon = "logs";
ob_start();
echo $aInt->beginAdminTabs(array($aInt->lang("global", "searchfilter")));
$range = App::getFromRequest("range");
if (!$range) {
    $today = WHMCS\Carbon::today();
    $from = $today->copy()->subMonths(3)->toAdminDateFormat();
    $range = $from . " - " . $today->toAdminDateFormat();
}
$filterresult = App::getFromRequest("filterresult");
$filtergateway = App::getFromRequest("filtergateway");
$filterdebugdata = App::getFromRequest("filterdebugdata");
echo "\n<form method=\"post\" action=\"gatewaylog.php\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n    <tr>\n        <td width=\"15%\" class=\"fieldlabel\">\n            ";
echo AdminLang::trans("fields.daterange");
echo "        </td>\n        <td class=\"fieldarea\">\n            <div class=\"form-group date-picker-prepend-icon\">\n                <label for=\"inputRange\" class=\"field-icon\">\n                    <i class=\"fal fa-calendar-alt\"></i>\n                </label>\n                <input id=\"inputRange\"\n                       type=\"text\"\n                       name=\"range\"\n                       value=\"";
echo $range;
echo "\"\n                       class=\"form-control date-picker-search\"\n                />\n            </div>\n        </td>\n        <td width=\"15%\" class=\"fieldlabel\">\n            ";
echo AdminLang::trans("gatewaytranslog.gateway");
echo "        </td>\n        <td class=\"fieldarea\">\n            <select name=\"filtergateway\" class=\"form-control select-inline\">\n                <option value=\"\">";
echo AdminLang::trans("global.any");
echo "</option>\n                ";
$query = "SELECT DISTINCT gateway FROM tblgatewaylog ORDER BY gateway ASC";
$result = full_query($query);
while ($data = mysql_fetch_array($result)) {
    $gateway = $data["gateway"];
    echo "<option";
    if ($gateway == $filtergateway) {
        echo " selected";
    }
    echo ">" . $gateway . "</option>";
}
echo "            </select>\n        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">\n            ";
echo AdminLang::trans("gatewaytranslog.debugdata");
echo "        </td>\n        <td class=\"fieldarea\">\n            <input type=\"text\"\n                   name=\"filterdebugdata\"\n                   class=\"form-control input-300\"\n                   value=\"";
echo $filterdebugdata;
echo "\"\n            >\n        </td><td class=\"fieldlabel\">\n            ";
echo AdminLang::trans("fields.result");
echo "        </td>\n        <td class=\"fieldarea\">\n            <select name=\"filterresult\" class=\"form-control select-inline\">\n                <option value=\"\">";
echo AdminLang::trans("global.any");
echo "</option>\n                ";
$query = "SELECT DISTINCT result FROM tblgatewaylog ORDER BY result ASC";
$result = full_query($query);
while ($data = mysql_fetch_array($result)) {
    $resultval = $data["result"];
    echo "<option";
    if ($resultval == $filterresult) {
        echo " selected";
    }
    echo ">" . $resultval . "</option>";
}
echo "            </select>\n        </td>\n    </tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
echo $aInt->lang("gatewaytranslog", "filter");
echo "\" class=\"btn btn-default\">\n</div>\n\n</form>\n\n";
echo $aInt->endAdminTabs();
echo "\n<br />\n\n";
$aInt->sortableTableInit("id", "DESC");
$where = array();
if ($filterdebugdata) {
    $where[] = "data LIKE '%" . db_escape_string(WHMCS\Input\Sanitize::decode($filterdebugdata)) . "%'";
}
$range = WHMCS\Carbon::parseDateRangeValue($range);
$startDate = $range["from"];
$endDate = $range["to"];
if ($startDate) {
    $where[] = "date>='" . $startDate->toDateTimeString() . "'";
}
if ($endDate) {
    $where[] = "date<='" . $endDate->toDateTimeString() . "'";
}
if ($filtergateway) {
    $where[] = "gateway='" . db_escape_string($filtergateway) . "'";
}
if ($filterresult) {
    $where[] = "result='" . db_escape_string($filterresult) . "'";
}
if (App::isInRequest("history")) {
    $historyId = (int) App::getFromRequest("history");
    if ($historyId) {
        $where[] = "transaction_history_id = '" . db_escape_string($historyId) . "'";
    }
}
$result = select_query("tblgatewaylog", "COUNT(*)", implode(" AND ", $where), "id", "DESC");
$data = mysql_fetch_array($result);
$numrows = $data[0];
$result = select_query("tblgatewaylog", "", implode(" AND ", $where), "id", "DESC", $page * $limit . "," . $limit);
while ($data = mysql_fetch_array($result)) {
    $id = $data["id"];
    $date = $data["date"];
    $gateway = WHMCS\Input\Sanitize::makeSafeForOutput($data["gateway"]);
    $data2 = WHMCS\Input\Sanitize::makeSafeForOutput($data["data"]);
    $res = WHMCS\Input\Sanitize::makeSafeForOutput($data["result"]);
    $date = fromMySQLDate($date, "time");
    $tabledata[] = array("<div class=\"text-center\">" . $date . "</div>", "<div class=\"text-center\">" . $gateway . "</div>", "<textarea rows=\"6\" class=\"form-control\">" . $data2 . "</textarea>", "<div class=\"text-center\"><strong>" . $res . "</strong></div>");
}
echo $aInt->sortableTable(array($aInt->lang("fields", "date"), $aInt->lang("gatewaytranslog", "gateway"), $aInt->lang("gatewaytranslog", "debugdata"), $aInt->lang("fields", "result")), $tabledata);
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->display();

?>