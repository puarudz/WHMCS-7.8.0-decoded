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
require ROOTDIR . "/includes/clientfunctions.php";
$reportdata["title"] = "Project Management Summary";
$reportdata["description"] = "This report shows a summary of all projects with times logged betwen";
$range = App::getFromRequest('range');
if (!$range) {
    $today = Carbon::today()->endOfDay();
    $lastWeek = Carbon::today()->subDays(6)->startOfDay();
    $range = $lastWeek->toAdminDateFormat() . ' - ' . $today->toAdminDateFormat();
}
$statusdropdown = '<select name="status" class="form-control"><option value="">- Any -</option>';
$statuses = get_query_val("tbladdonmodules", "value", ["module" => "project_management", "setting" => "statusvalues"]);
$statuses = explode(",", $statuses);
foreach ($statuses as $statusx) {
    $statusx = explode("|", $statusx, 2);
    $statusdropdown .= '<option';
    if ($statusx[0] == $status) {
        $statusdropdown .= ' selected';
    }
    $statusdropdown .= '>' . $statusx[0] . '</option>';
}
$statusdropdown .= '</select>';
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
                        <label for="inputFilterStaff">Status</label>
                        {$statusdropdown}
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
$reportdata["tableheadings"] = array("ID", "Created", "Project Title", "Assigned Staff", "Associated Client", "Due Date", "Total Invoiced", "Total Paid", "Total Time", "Status");
$totalprojectstime = $i = 0;
$adminquery = $adminid ? " AND adminid='" . (int) $adminid . "'" : '';
$statusquery = $status ? " AND status='" . db_escape_string($status) . "'" : '';
$dateRange = Carbon::parseDateRangeValue($range);
$fromdate = $dateRange['from']->toDateTimeString();
$todate = $dateRange['to']->toDateTimeString();
$result = select_query("mod_project", "", "duedate>='{$fromdate}' AND duedate<='{$todate}'" . $adminquery . $statusquery);
while ($data = mysql_fetch_array($result)) {
    $totaltaskstime = 0;
    $projectid = $data["id"];
    $projectname = $data["title"];
    $adminid = $data["adminid"];
    $userid = $data["userid"];
    $created = $data["created"];
    $duedate = $data["duedate"];
    $ticketids = $data["ticketids"];
    $projectstatus = $data["status"];
    $created = fromMySQLDate($created);
    $duedate = fromMySQLDate($duedate);
    $admin = $adminid ? getAdminName($adminid) : 'None';
    if ($userid) {
        $clientsdetails = getClientsDetails($userid);
        $client = '<a href="clientssummary.php?userid=' . $clientsdetails['userid'] . '">' . $clientsdetails['firstname'] . ' ' . $clientsdetails['lastname'];
        if ($clientsdetails['companyname']) {
            $client .= ' (' . $clientsdetails['companyname'] . ')';
        }
        $client .= '</a>';
        $currency = getCurrency();
    } else {
        $client = 'None';
    }
    $ticketinvoicelinks = array();
    foreach ($ticketids as $i => $ticketnum) {
        if ($ticketnum) {
            $ticketnum = get_query_val("tbltickets", "tid", array("tid" => $ticketnum));
            $ticketinvoicelinks[] = "description LIKE '%Ticket #{$ticketnum}%'";
        }
    }
    $ticketinvoicesquery = !empty($ticketinvoicelinks) ? "(\".implode(' AND ',{$ticketinvoicelinks}).\") OR " : '';
    $totalinvoiced = get_query_val("tblinvoices", "SUM(subtotal+tax+tax2)", "id IN (SELECT invoiceid FROM tblinvoiceitems WHERE description LIKE '%Project #{$projectid}%' OR {$ticketinvoicesquery} (type='Project' AND relid='{$projectid}'))");
    $totalinvoiced = $userid ? formatCurrency($totalinvoiced) : format_as_currency($totalinvoiced);
    $totalpaid = get_query_val("tblinvoices", "SUM(subtotal+tax+tax2)", "id IN (SELECT invoiceid FROM tblinvoiceitems WHERE description LIKE '%Project #{$projectid}%' OR {$ticketinvoicesquery} (type='Project' AND relid='{$projectid}')) AND status='Paid'");
    $totalpaid = $userid ? formatCurrency($totalpaid) : format_as_currency($totalpaid);
    $reportdata["drilldown"][$i]["tableheadings"] = array("Task Name", "Start Time", "Stop Time", "Duration", "Task Status");
    $timerresult = select_query("mod_projecttimes", "mod_projecttimes.start,mod_projecttimes.end,mod_projecttasks.task,mod_projecttasks.completed", array("mod_projecttimes.projectid" => $projectid), "", "", "", "mod_projecttasks ON mod_projecttimes.taskid = mod_projecttasks.id");
    while ($data2 = mysql_fetch_assoc($timerresult)) {
        $rowcount = $rowtotal = 0;
        $taskid = $data2['id'];
        $task = $data2['task'];
        $taskadminid = $data2['adminid'];
        $timerstart = $data2['start'];
        $timerend = $data2['end'];
        $duration = $timerend ? $timerend - $timerstart : 0;
        $taskadmin = getAdminName($taskadminid);
        $starttime = date("d/m/Y H:i:s ", $timerstart);
        $stoptime = date("d/m/Y H:i:s ", $timerend);
        $taskstatus = $data2['completed'] ? "Completed" : "Open";
        $totalprojectstime += $duration;
        $totaltaskstime += $duration;
        $rowcount++;
        $rowtotal += $ordertotal;
        $reportdata["drilldown"][$i]["tablevalues"][] = array($task, $starttime, $stoptime, project_management_sec2hms($duration), $taskstatus);
    }
    $reportdata["tablevalues"][$i] = array('<a href="addonmodules.php?module=project_management&m=view&projectid=' . $projectid . '">' . $projectid . '</a>', $created, $projectname, $admin, $client, $duedate, $totalinvoiced, $totalpaid, project_management_sec2hms($totaltaskstime), $projectstatus);
    $i++;
}
$reportdata["footertext"] = "Total Time effort across {$i} projects: " . project_management_sec2hms($totalprojectstime);
function project_management_sec2hms($sec, $padHours = false)
{
    if ($sec <= 0) {
        $sec = 0;
    }
    $hms = "";
    $hours = intval(intval($sec) / 3600);
    $hms .= $padHours ? str_pad($hours, 2, "0", STR_PAD_LEFT) . ":" : $hours . ":";
    $minutes = intval($sec / 60 % 60);
    $hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT);
    return $hms;
}

?>