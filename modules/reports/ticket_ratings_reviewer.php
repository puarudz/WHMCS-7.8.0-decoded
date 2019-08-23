<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

use WHMCS\Carbon;
use WHMCS\View\Markup\Markup;
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
$rating = App::getFromRequest('range');
if (!$rating) {
    $rating = '1';
}
$range = App::getFromRequest('range');
if (!$range) {
    $today = Carbon::today()->endOfDay();
    $lastWeek = Carbon::today()->subDays(6)->startOfDay();
    $range = $lastWeek->toAdminDateFormat() . ' - ' . $today->toAdminDateFormat();
}
$dateRange = Carbon::parseDateRangeValue($range);
$startdate = $dateRange['from']->toAdminDateFormat();
$enddate = $dateRange['to']->toAdminDateFormat();
$rsel[$rating] = ' selected="selected"';
$markup = new Markup();
$query = "SELECT tblticketreplies.*,tbltickets.tid AS ticketid FROM tblticketreplies INNER JOIN tbltickets ON tbltickets.id=tblticketreplies.tid WHERE tblticketreplies.admin!='' AND tblticketreplies.rating='" . (int) $rating . "' AND tblticketreplies.date BETWEEN '" . db_make_safe_human_date($startdate) . "' AND '" . db_make_safe_human_date($enddate) . "' ORDER BY date DESC";
$result = full_query($query);
$num_rows = mysql_num_rows($result);
$reportdata["title"] = "Support Ticket Ratings Reviewer";
$reportdata["description"] = "This report is showing all {$num_rows} ticket replies rated {$rating} between {$startdate} & {$enddate} for review";
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
                        <label for="inputFilterRating">Rating</label>
                        <select id="inputFilterRating" name="rating" class="form-control">
                            <option{$rsel[1]}>1</option>
                            <option{$rsel[2]}>2</option>
                            <option{$rsel[3]}>3</option>
                            <option{$rsel[4]}>4</option>
                            <option{$rsel[5]}>5</option>
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
$reportdata["tableheadings"] = array("Ticket #", "Date", "Message", "Admin", "Rating");
while ($data = mysql_fetch_array($result)) {
    $tid = $data["tid"];
    $ticketid = $data["ticketid"];
    $date = $data["date"];
    $message = $data["message"];
    $admin = $data["admin"];
    $rating = $data["rating"];
    $editor = $data["editor"];
    $date = fromMySQLDate($date, true);
    $markupFormat = $markup->determineMarkupEditor('ticket_reply', $editor);
    $message = $markup->transform($message, $markupFormat);
    $reportdata["tablevalues"][] = array('<a href="supporttickets.php?action=viewticket&id=' . $tid . '">' . $ticketid . '</a>', $date, '<div align="left">' . $message . '</div>', $admin, $rating);
}

?>