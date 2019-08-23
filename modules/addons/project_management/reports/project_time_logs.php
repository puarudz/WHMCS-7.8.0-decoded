<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

use WHMCS\Carbon;
use WHMCS\Database\Capsule;
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
$reportdata["title"] = "Project Management Project Time Logs";
$reportdata["description"] = "This report shows the amount of time logged on a per task basis, per staff member, for a given date range.";
$range = App::getFromRequest('range');
if (!$range) {
    $today = Carbon::today()->endOfDay();
    $lastWeek = Carbon::today()->subDays(6)->startOfDay();
    $range = $lastWeek->toAdminDateFormat() . ' - ' . $today->toAdminDateFormat();
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
<form method="post" action="{$requeststr}">
    <div class="report-filters-wrapper">
        <div class="inner-container">
            <h3>Filters</h3>
            <div class="row">
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
                <div class="col-md-3 col-sm-6">
                    <div class="form-group">
                        <label for="inputFilterStaff">Staff Member</label>
                        <select id="inputFilterStaff" name="adminid" class="form-control">
                            <option value="0">- Any -</option>
                            {$adminDropdown}
                        </select>
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
$reportdata["tableheadings"] = array("Project Name", "Task Name", "Total Time");
$i = 0;
$dateRange = Carbon::parseDateRangeValue($range);
$datefrom = $dateRange['from']->toDateTimeString();
$dateto = $dateRange['to']->toDateString();
$adminquery = $adminid ? " AND adminid='" . (int) $adminid . "'" : '';
$result = select_query("tbladmins", "id,firstname,lastname", "", "firstname", "ASC");
while ($data = mysql_fetch_array($result)) {
    $adminid = $data['id'];
    $adminfirstname = $data['firstname'];
    $adminlastname = $data['lastname'];
    $reportdata["tablevalues"][$i] = array("**<strong>{$adminfirstname} {$adminlastname}</strong>");
    $i++;
    $totalduration = 0;
    $result2 = select_query("mod_projecttimes", "mod_project.id,mod_project.title,mod_projecttasks.task,mod_projecttimes.start,mod_projecttimes.end", "(mod_projecttimes.start>='" . strtotime(toMySQLDate($datefrom)) . "' AND mod_projecttimes.end<='" . strtotime(toMySQLDate($dateto) . ' 23:59:59') . "') AND mod_projecttimes.adminid={$adminid}", "start", "ASC", "", "mod_project ON mod_projecttimes.projectid = mod_project.id INNER JOIN mod_projecttasks ON mod_projecttasks.id = mod_projecttimes.taskid");
    while ($data = mysql_fetch_array($result2)) {
        $projectid = $data['id'];
        $projecttitle = $data['title'];
        $projecttask = $data['task'];
        $time = $data["end"] - $data["start"];
        $totalduration += $time;
        $reportdata["tablevalues"][$i] = array('<a href="addonmodules.php?module=project_management&m=view&projectid=' . $projectid . '">' . $projecttitle . '</a>', $projecttask, project_task_logs_time($time));
        $i++;
    }
    $reportdata["tablevalues"][$i] = array('', '', '<strong>' . project_task_logs_time($totalduration) . '</strong>');
    $i++;
}
function project_task_logs_time($sec, $padHours = false)
{
    if ($sec <= 0) {
        $sec = 0;
    }
    $hms = "";
    $hours = intval(intval($sec) / 3600);
    $hms .= $padHours ? str_pad($hours, 2, "0", STR_PAD_LEFT) . ":" : $hours . ":";
    $minutes = intval($sec / 60 % 60);
    $hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT) . ":";
    $seconds = intval($sec % 60);
    $hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);
    return $hms;
}

?>