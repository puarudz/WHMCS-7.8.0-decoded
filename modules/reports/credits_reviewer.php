<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

use WHMCS\Carbon;
use WHMCS\Database\Capsule;
use WHMCS\User\Admin;
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
$range = App::getFromRequest('range');
$userId = (int) App::getFromRequest('userid');
$min = App::getFromRequest('min');
$max = App::getFromRequest('max');
$adminId = App::getFromRequest('adminid');
$reportdata["title"] = "Credits Reviewer";
$reportdata["description"] = "This report allows you to review all the credits issued to clients between 2 dates you specify";
$activeAdminUserOptions = [];
foreach (Admin::where('disabled', 0)->get() as $user) {
    $activeAdminUserOptions[] = '<option value="' . $user->id . '"' . ($user->id == $adminId ? ' selected' : '') . '>' . $user->fullName . '</option>';
}
$disabledAdminUserOptions = [];
foreach (Admin::where('disabled', 1)->get() as $user) {
    $disabledAdminUserOptions[] = '<option value="' . $user->id . '"' . ($user->id == $adminId ? ' selected' : '') . '>' . $user->fullName . '</option>';
}
$adminUserOptions = '<option value="0">' . AdminLang::trans('global.-any-') . '</option>' . '<optgroup label="Active Users">' . implode($activeAdminUserOptions);
if (count($disabledAdminUserOptions) > 0) {
    $adminUserOptions .= '<optgroup label="Disabled Users">' . implode($disabledAdminUserOptions);
}
$langRequired = AdminLang::trans('global.required');
$reportdata['headertext'] = '';
if (!$print) {
    $reportdata["headertext"] = <<<HTML
<form method="post" action="reports.php?report={$report}">
    <div class="report-filters-wrapper">
        <div class="inner-container">
            <h3>Filters</h3>
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="form-group">
                        <label for="inputFilterClient">{$aInt->lang('fields', 'client')}</label>
                        {$aInt->clientsDropDown($userId)}
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
                                   class="form-control date-picker-search date-picker-search-100pc"
                                   placeholder="{$langRequired}"
                            />
                        </div>
                    </div>
                </div>
                <div class="col-md-2 col-sm-3">
                    <div class="form-group">
                        <label for="inputFilterMin">Min. Amount</label>
                        <input type="number" name="min" value="{$min}" class="form-control" id="inputFilterMin" step="any" min="0" placeholder="0">
                    </div>
                </div>
                <div class="col-md-2 col-sm-3">
                    <div class="form-group">
                        <label for="inputFilterMax">Max. Amount</label>
                        <input type="number" name="max" value="{$max}" class="form-control" id="inputFilterMax" step="any" min="0" placeholder="Unlimited">
                    </div>
                </div>
                <div class="col-md-2 col-sm-6">
                    <div class="form-group">
                        <label for="inputAdmin">Admin User</label>
                        <select name="adminid" class="form-control">
                            {$adminUserOptions}
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
$reportdata["tableheadings"] = array("Credit ID", "Client ID", "Client Name", "Date", "Description", "Amount", "Admin User");
if ($range) {
    $dateRange = Carbon::parseDateRangeValue($range);
    $dateFrom = $dateRange['from']->toDateTimeString();
    $dateTo = $dateRange['to']->toDateTimeString();
    $query = Capsule::table('tblcredit')->join('tblclients', 'tblclients.id', '=', 'tblcredit.clientid')->whereBetween('tblcredit.date', [$dateFrom, $dateTo]);
    if ($userId) {
        $query->where('clientid', $userId);
    }
    if (App::isInRequest('min') && $min >= 0) {
        $query->where('amount', '>=', $min);
    }
    if ($max && (!$min || $max > $min)) {
        $query->where('amount', '<=', $max);
    }
    if ($adminId) {
        $query->where('admin_id', $adminId);
    }
    $result = $query->orderBy('date')->get(['tblcredit.*', 'tblclients.firstname', 'tblclients.lastname']);
    /** @var stdClass $data */
    foreach ($result as $data) {
        $id = $data->id;
        $userid = $data->clientid;
        $clientname = $data->firstname . " " . $data->lastname;
        $date = fromMySQLDate($data->date);
        $description = $data->description;
        $amount = $data->amount;
        $currency = getCurrency($userid);
        $amount = formatCurrency($amount);
        $adminName = '-';
        if ($data->admin_id) {
            $adminName = getAdminName($data->admin_id);
            if (!trim($adminName)) {
                $adminName = '-';
            }
        }
        $reportdata["tablevalues"][] = array($id, '<a href="clientssummary.php?userid=' . $userid . '">' . $userid . '</a>', '<a href="clientssummary.php?userid=' . $userid . '">' . $clientname . '</a>', $date, nl2br($description), $amount, $adminName);
    }
}
$reportdata["footertext"] = '';

?>