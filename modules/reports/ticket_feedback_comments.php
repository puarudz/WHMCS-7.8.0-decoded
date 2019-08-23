<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

use WHMCS\Carbon;
use WHMCS\Database\Capsule;
use WHMCS\Utility\GeoIp;
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
$reportdata["title"] = "Ticket Feedback Comments";
$reportdata["description"] = "This report allows you to review feedback comments submitted by customers.";
$staffid = App::getFromRequest('staffid');
$range = App::getFromRequest('range');
if (!$range) {
    $today = Carbon::today()->endOfDay();
    $lastWeek = Carbon::today()->subDays(6)->startOfDay();
    $range = $lastWeek->toAdminDateFormat() . ' - ' . $today->toAdminDateFormat();
}
$module = App::getFromRequest('module');
$moduleString = '';
if ($module) {
    $moduleString = 'module=' . $module . '&';
}
if (!$fromdate) {
    $fromdate = fromMySQLDate(date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 7, date("Y"))));
}
if (!$todate) {
    $todate = getTodaysDate();
}
$admins = Capsule::table('tbladmins')->orderBy('firstname')->pluck(Capsule::raw('CONCAT_WS(\' \', tbladmins.firstname, tbladmins.lastname) as name'), 'id');
$adminDropdown = '';
foreach ($admins as $adminId => $adminName) {
    $selected = '';
    if ($adminId == $staffid) {
        $selected = ' selected="selected"';
    }
    $adminDropdown .= "<option value=\"{$adminId}\"{$selected}>{$adminName}</option>";
}
$reportdata['headertext'] = '';
if (!$print) {
    $reportdata['headertext'] = <<<HTML
<form method="post" action="?{$moduleString}report={$report}&currencyid={$currencyid}&calculate=true">
    <div class="report-filters-wrapper">
        <div class="inner-container">
            <h3>Filters</h3>
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="form-group">
                        <label for="inputFilterStaff">Staff Name</label>
                        <select id="inputFilterStaff" name="staffid" class="form-control">
                            <option value="0">- Any -</option>
                            {$adminDropdown}
                        </select>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="form-group">
                        <label for="inputFilterDate">{$dateRangeText}</label>
                        <div class="form-group date-picker-prepend-icon">
                            <label for="inputFilterDate" class="field-icon">
                                <i class="fal fa-calendar-alt"></i>
                            </label>
                            <input id="inputFilterDate"
                                   type="text"
                                   name="range"
                                   value="{$range}"
                                   class="form-control date-picker-search"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                {$aInt->lang('reports', 'generateReport')}
            </button>
        </div>
    </div>
</form>
HTML;
}
$reportdata["tableheadings"][] = "Ticket ID";
$reportdata["tableheadings"][] = "Staff Name";
$reportdata["tableheadings"][] = "Subject";
$reportdata["tableheadings"][] = "Feedback Left";
$reportdata["tableheadings"][] = "Rating";
$reportdata["tableheadings"][] = "Comments";
$reportdata["tableheadings"][] = "IP Address";
$dateRange = Carbon::parseDateRangeValue($range);
$fromdate = $dateRange['from']->toDateTimeString();
$todate = $dateRange['to']->toDateString();
$result = select_query("tblticketfeedback", "tblticketfeedback.*,(SELECT CONCAT(firstname,' ',lastname) FROM tbladmins WHERE tbladmins.id=tblticketfeedback.adminid) AS adminname,(SELECT CONCAT(tid,'|||',title) FROM tbltickets WHERE tbltickets.id=tblticketfeedback.ticketid) AS ticketinfo", "datetime>='" . db_make_safe_human_date($fromdate) . "' AND datetime<='" . db_make_safe_human_date($todate) . " 23:59:59'" . ($staffid ? " AND adminid=" . (int) $staffid : ""), "datetime", "ASC");
while ($data = mysql_fetch_array($result)) {
    $id = $data['id'];
    $ticketid = $data['ticketid'];
    $ticketinfo = $data['ticketinfo'];
    $adminid = $data['adminid'];
    $adminname = $data['adminname'];
    $rating = $data['rating'];
    $comments = $data['comments'];
    $datetime = $data['datetime'];
    $ip = $data['ip'];
    if ($adminid == 0) {
        $adminname = 'Generic Feedback';
    } elseif (!trim($adminname)) {
        $adminname = 'Deleted Admin';
    }
    if (!trim($comments)) {
        $comments = 'No Comments Left';
    }
    $datetime = fromMySQLDate($datetime, 1);
    $ticketinfo = explode('|||', $ticketinfo);
    $tickettid = $ticketinfo[0];
    $subject = $ticketinfo[1];
    if (!$tickettid) {
        $tickettid = 'Not Found';
    }
    $reportdata["tablevalues"][] = ['<a href="supporttickets.php?action=viewticket&id=' . $ticketid . '" target="_blank">' . $tickettid . '</a>', $adminname, $subject, $datetime, $rating, nl2br($comments), GeoIp::getLookupHtmlAnchor($ip)];
}

?>