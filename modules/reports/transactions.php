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
$reportdata["title"] = "Transactions";
$filterfields = array("id" => "ID", "userid" => "User ID", "clientname" => "Client Name", "currency" => "Currency", "gateway" => "Payment Method", "date" => "Date", "description" => "Description", "invoiceid" => "Invoice ID", "transid" => "Transaction ID", "amountin" => "Amount In", "fees" => "Fees", "amountout" => "Amount Out", "rate" => "Exchange Rate", "refundid" => "Refund ID");
$reportdata["description"] = $reportdata["headertext"] = '';
$incfields = $whmcs->get_req_var('incfields');
$filterfield = $whmcs->get_req_var('filterfield');
$filtertype = $whmcs->get_req_var('filtertype');
$filterq = $whmcs->get_req_var('filterq');
$range = App::getFromRequest('range');
if (!is_array($incfields)) {
    $incfields = array();
}
if (!is_array($filterfield)) {
    $filterfield = array();
}
if (!is_array($filtertype)) {
    $filtertype = array();
}
if (!is_array($filterq)) {
    $filterq = array();
}
if (!$print) {
    $reportdata["description"] = "This report can be used to generate a custom export of transactions by applying up to 5 filters. CSV Export is available via the Tools menu to the right.";
    $reportdata["headertext"] = '<form method="post" action="reports.php?report=' . $report . '">
<table class="form" width="100%" border="0" cellspacing="2" cellpadding="3">
<tr><td width="20%" class="fieldlabel">Fields to Include</td><td class="fieldarea"><table width="100%"><tr>';
    $i = 0;
    foreach ($filterfields as $k => $v) {
        $reportdata["headertext"] .= '<td width="20%"><input type="checkbox" name="incfields[]" value="' . $k . '" id="fd' . $k . '"';
        if (in_array($k, $incfields)) {
            $reportdata["headertext"] .= ' checked';
        }
        $reportdata["headertext"] .= ' /> <label for="fd' . $k . '">' . $v . '</label></td>';
        $i++;
        if ($i % 5 == 0) {
            $reportdata["headertext"] .= '</tr><tr>';
        }
    }
    $reportdata["headertext"] .= '</tr></table></td></tr>';
    for ($i = 1; $i <= 5; $i++) {
        $reportdata["headertext"] .= '<tr><td width="20%" class="fieldlabel">Filter ' . $i . '</td><td class="fieldarea"><select name="filterfield[' . $i . ']"><option value="">None</option>';
        foreach ($filterfields as $k => $v) {
            $reportdata["headertext"] .= '<option value="' . $k . '"';
            if (isset($filterfield[$i]) && $filterfield[$i] == $k) {
                $reportdata["headertext"] .= ' selected';
            }
            $reportdata["headertext"] .= '>' . $v . '</option>';
        }
        $reportdata["headertext"] .= '</select> <select name="filtertype[' . $i . ']"><option>Exact Match</option><option value="like"';
        if (isset($filtertype[$i]) && $filtertype[$i] == "like") {
            $reportdata["headertext"] .= ' selected';
        }
        $reportdata["headertext"] .= '>Containing</option></select> <input type="text" name="filterq[' . $i . ']" size="30" value="' . (isset($filterq[$i]) ? $filterq[$i] : '') . '" /></td></tr>';
    }
    $reportdata["headertext"] .= <<<HTML
        <tr>
            <td width="20%" class="fieldlabel">Date Range</td>
            <td class="fieldarea">
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
            </td>
        </tr>
    </table>
    <p align="center"><input type="submit" value="Filter" /></p>
</form>
HTML;
}
if (count($incfields)) {
    $filters = array();
    foreach ($filterfield as $i => $val) {
        if ($val && array_key_exists($val, $filterfields)) {
            if ($val == 'clientname') {
                $val = "(SELECT CONCAT(firstname,' ',lastname) FROM tblclients WHERE id=tblaccounts.userid)";
            }
            $filters[] = $filtertype[$i] == "like" ? $val . " LIKE '%" . db_escape_string($filterq[$i]) . "%'" : $val . "='" . db_escape_string($filterq[$i]) . "'";
        }
    }
    $fieldlist = array();
    foreach ($incfields as $fieldname) {
        if (array_key_exists($fieldname, $filterfields)) {
            $reportdata["tableheadings"][] = $filterfields[$fieldname];
            if ($fieldname == "clientname") {
                $fieldname = "(SELECT CONCAT(firstname,' ',lastname) FROM tblclients WHERE id=tblaccounts.userid)";
            }
            $fieldlist[] = $fieldname;
        }
    }
    if (in_array('currency', $incfields) && !in_array('userid', $incfields)) {
        $fieldlist[] = 'userid';
    }
    if ($range) {
        $dateRange = Carbon::parseDateRangeValue($range);
        $fromdate = $dateRange['from']->toDateTimeString();
        $todate = $dateRange['to']->toDateTimeString();
        $filters[] = "date>='{$fromdate}' AND date<='{$todate}'";
    }
    $result = select_query("tblaccounts", implode(',', $fieldlist), implode(' AND ', $filters), "date", "ASC");
    while ($data = mysql_fetch_assoc($result)) {
        if (isset($data['currency'])) {
            $currency = getCurrency($data['userid'], $data['currency']);
            $data['currency'] = $currency['code'];
            if (!in_array('userid', $incfields)) {
                unset($data['userid']);
            }
        }
        if (isset($data['gateway'])) {
            $data['gateway'] = $gateways->getDisplayName($data['gateway']);
        }
        $reportdata["tablevalues"][] = $data;
    }
}

?>