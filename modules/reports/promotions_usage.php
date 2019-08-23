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
$reportdata["title"] = "Promotions Usage Report";
$reportdata["description"] = "This report shows usage statistics for each promotional code.";
$range = App::getFromRequest('range');
if (!$range) {
    $today = Carbon::today()->endOfDay();
    $lastWeek = Carbon::today()->subDays(6)->startOfDay();
    $range = $lastWeek->toAdminDateFormat() . ' - ' . $today->toAdminDateFormat();
}
$reportdata['headertext'] = '';
if (!$print) {
    $reportdata["headertext"] = <<<EOF
<form method="post" action="reports.php?report={$report}">
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
                                   placeholder="{$optionalText}"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">
                {$aInt->lang('global', 'apply')}
            </button>
        </div>
    </div>
</form>
EOF;
}
$reportdata["tableheadings"] = array("Coupon Code", "Discount Type", "Value", "Recurring", "Notes", "Usage Count", "Total Revenue");
$i = 0;
$dateRange = Carbon::parseDateRangeValue($range);
$datefrom = $dateRange['from']->toDateTimeString();
$dateto = $dateRange['to']->toDateTimeString();
$result = select_query("tblpromotions", "", "", "code", "ASC");
while ($data = mysql_fetch_array($result)) {
    $code = $data["code"];
    $type = $data["type"];
    $value = $data["value"];
    $recurring = $data["recurring"];
    $notes = $data["notes"];
    $rowcount = $rowtotal = 0;
    $reportdata["drilldown"][$i]["tableheadings"] = array("Order ID", "Order Date", "Order Number", "Order Total", "Order Status");
    $result2 = select_query("tblorders", "", "promocode='" . db_escape_string($code) . "' AND date>='" . db_make_safe_human_date($datefrom) . "' AND date<='" . db_make_safe_human_date($dateto) . "'", "id", "ASC");
    while ($data = mysql_fetch_array($result2)) {
        $orderid = $data['id'];
        $ordernum = $data['ordernum'];
        $orderdate = $data['date'];
        $ordertotal = $data['amount'];
        $orderstatus = $data['status'];
        $rowcount++;
        $rowtotal += $ordertotal;
        $reportdata["drilldown"][$i]["tablevalues"][] = array('<a href="orders.php?action=view&id=' . $orderid . '">' . $orderid . '</a>', fromMySQLDate($orderdate), $ordernum, $ordertotal, $orderstatus);
    }
    $reportdata["tablevalues"][$i] = array($code, $type, $value, $recurring, $notes, $rowcount, format_as_currency($rowtotal));
    $i++;
}

?>