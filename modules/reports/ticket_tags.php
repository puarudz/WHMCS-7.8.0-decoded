<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

use WHMCS\Carbon;
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
$reportdata["title"] = "Ticket Tags Overview";
$reportdata["description"] = "This report provides an overview of ticket tags assigned to tickets for a given date range";
$range = App::getFromRequest('range');
if (!$range) {
    $today = Carbon::today()->endOfDay();
    $lastWeek = Carbon::today()->subMonth()->startOfDay();
    $range = $lastWeek->toAdminDateFormat() . ' - ' . $today->toAdminDateFormat();
}
$dateRange = Carbon::parseDateRangeValue($range);
$startdate = $dateRange['from']->toAdminDateFormat();
$enddate = $dateRange['to']->toAdminDateFormat();
$reportdata['headertext'] = '';
if (!$print) {
    $reportdata['headertext'] = <<<HTML
<form method="post" action="reports.php?report={$report}&currencyid={$currencyid}&calculate=true">
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
$reportdata["tableheadings"][] = "Tag";
$reportdata["tableheadings"][] = "Count";
$result = full_query("SELECT `tag`, COUNT(*) AS `count` FROM `tbltickettags` INNER JOIN tbltickets ON tbltickets.id=tbltickettags.ticketid WHERE tbltickets.date>='" . db_make_safe_human_date($fromdate) . " 00:00:00' AND tbltickets.date<='" . db_make_safe_human_date($todate) . " 23:59:59' GROUP BY tbltickettags.tag ORDER BY `count` DESC");
while ($data = mysql_fetch_array($result)) {
    $tag = $data[0];
    $count = $data[1];
    $reportdata["tablevalues"][] = array($tag, $count);
    $chartdata['rows'][] = array('c' => array(array('v' => $tag), array('v' => (int) $count, 'f' => $count)));
}
$chartdata['cols'][] = array('label' => 'Tag', 'type' => 'string');
$chartdata['cols'][] = array('label' => 'Count', 'type' => 'number');
$args = array();
$args['legendpos'] = 'right';
$reportdata["headertext"] .= $chart->drawChart('Pie', $chartdata, $args, '300px');

?>