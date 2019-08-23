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
$reportdata["title"] = "Ticket Feedback Scores";
$reportdata["description"] = "This report provides a summary of scores received on a per staff member basis for a given date range";
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
$reportdata["tableheadings"][] = "Staff Name";
for ($rating = 1; $rating <= 10; $rating++) {
    $reportdata["tableheadings"][] = $rating;
}
$reportdata["tableheadings"][] = "Total Ratings";
$reportdata["tableheadings"][] = "Average Rating";
$dateRange = Carbon::parseDateRangeValue($range);
$fromdate = $dateRange['from']->toDateTimeString();
$todate = $dateRange['to']->endOfDay()->toDateTimeString();
$adminnames = $ratingstats = array();
$query = Capsule::table('tblticketfeedback')->select([Capsule::raw('CONCAT_WS(\' \', `firstname`, `lastname`) as adminname'), 'adminid', 'rating', Capsule::raw('count(rating) as counts')])->where('adminid', '>', 0)->where('datetime', '>=', $fromdate)->where('datetime', '<=', $todate)->join('tbladmins', 'tbladmins.id', '=', 'tblticketfeedback.adminid')->groupBy(['rating', 'adminid']);
foreach ($query->get() as $data) {
    $adminname = $data->adminname;
    $adminid = $data->adminid;
    $rating = $data->rating;
    $count = $data->counts;
    $adminnames[$adminid] = $adminname;
    $ratingstats[$adminid][$rating] = $count;
}
foreach ($adminnames as $adminid => $adminname) {
    $rowtotal = $rowcount = 0;
    $row = array();
    $row[] = '<a href="' . $_SERVER['PHP_SELF'] . '?' . (isset($_REQUEST['module']) ? 'module=' . $_REQUEST['module'] . '&' : '') . 'report=ticket_feedback_comments&' . (isset($_REQUEST['module']) ? 'module=' . $_REQUEST['module'] . '&' : '') . 'staffid=' . $adminid . '">' . $adminname . '</a>';
    for ($rating = 1; $rating <= 10; $rating++) {
        $count = $ratingstats[$adminid][$rating];
        $row[] = $count;
        $rowcount += $count;
        $rowtotal += $count * $rating;
    }
    $average = round($rowtotal / $rowcount, 2);
    $row[] = $rowcount;
    $row[] = $average;
    $reportdata["tablevalues"][] = $row;
    $chartdata['rows'][] = array('c' => array(array('v' => $adminname), array('v' => $average, 'f' => $average)));
}
$chartdata['cols'][] = array('label' => 'Staff Name', 'type' => 'string');
$chartdata['cols'][] = array('label' => 'Average Rating', 'type' => 'number');
$args = array();
$args['colors'] = '#F9D88C,#3070CF';
$args['minyvalue'] = '0';
$args['maxyvalue'] = '10';
$args['gridlinescount'] = '11';
$args['minorgridlinescount'] = '3';
$args['ylabel'] = 'Average Rating';
$args['xlabel'] = 'Staff Name';
$args['legendpos'] = 'none';
$reportdata["headertext"] .= $chart->drawChart('Column', $chartdata, $args, '500px');

?>