<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

use WHMCS\Carbon;
use WHMCS\Database\Capsule;
use WHMCS\Input\Sanitize;
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
if (!function_exists('getRegistrarsDropdownMenu')) {
    require ROOTDIR . '/includes/registrarfunctions.php';
}
$reportdata["title"] = $aInt->lang('reports', 'domainRenewalEmailsTitle');
$userID = App::getFromRequest('userid');
$domain = App::getFromRequest('domain');
$range = App::getFromRequest('range');
$registrar = App::getFromRequest('registrar');
$print = App::getFromRequest('print');
/**
 * Replace the "None" string with the "Any" string
 */
$registrarList = str_replace([$aInt->lang('global', 'none')], [$aInt->lang('global', 'any')], getRegistrarsDropdownMenu($registrar));
$registrarList = str_replace(' select-inline', '', $registrarList);
$reportdata["description"] = $aInt->lang('reports', 'domainRenewalEmailsDescription');
$reportHeader = '';
if (!$print) {
    $reportHeader = <<<REPORT_HEADER
<form method="post" action="reports.php?report={$report}">
    <div class="report-filters-wrapper">
        <div class="inner-container">
            <h3>Filters</h3>
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="form-group">
                        <label for="inputFilterClient">{$aInt->lang('fields', 'client')}</label>
                        {$aInt->clientsDropDown($userID, '', 'userid', true)}
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="form-group">
                        <label for="inputFilterRegistrar">{$aInt->lang('fields', 'registrar')}</label>
                        {$registrarList}
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="form-group">
                        <label for="inputFilterDomain">{$aInt->lang('fields', 'domain')}</label>
                        <input type="text" name="domain" value="{$domain}" class="form-control" id="inputFilterDomain" placeholder="{$optionalText}">
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
                                   data-opens="left"
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
REPORT_HEADER;
}
$reportdata["headertext"] = $reportHeader;
$reportdata["tableheadings"] = array($aInt->lang('fields', 'client'), $aInt->lang('fields', 'domain'), $aInt->lang('fields', 'dateSent'), $aInt->lang('domains', 'reminder'), $aInt->lang('emails', 'recipients'), $aInt->lang('domains', 'sent'));
$typeMap = array(1 => $aInt->lang('domains', 'firstReminder'), 2 => $aInt->lang('domains', 'secondReminder'), 3 => $aInt->lang('domains', 'thirdReminder'), 4 => $aInt->lang('domains', 'fourthReminder'), 5 => $aInt->lang('domains', 'fifthReminder'));
# Report Footer Text - this gets displayed below the report table of data
$data["footertext"] = "";
$query = Capsule::table('tbldomainreminders')->join('tbldomains', 'tbldomains.id', '=', 'tbldomainreminders.domain_id')->join('tblclients', 'tblclients.id', '=', 'tbldomains.userid')->select(['tbldomainreminders.id AS reminder_id', 'tbldomainreminders.date', 'tbldomainreminders.type', 'tbldomainreminders.days_before_expiry', 'tbldomainreminders.recipients', 'tblclients.firstname', 'tblclients.lastname', 'tblclients.companyname', 'tbldomains.domain'])->orderBy('reminder_id', 'desc');
$where = array();
if ($userID) {
    $query->where('tblclients.id', (int) $userID);
}
if ($domain) {
    $query->where('tbldomains.domain', Sanitize::encode($domain));
}
if ($range) {
    $dateRange = Carbon::parseDateRangeValue($range);
    $dateFrom = $dateRange['from']->toDateTimeString();
    $dateTo = $dateRange['to']->toDateTimeString();
    $query->whereBetween('tbldomainreminders.date', [$dateFrom, $dateTo]);
}
if ($registrar) {
    $query->where('tbldomains.registrar', $registrar);
}
foreach ($query->get() as $data) {
    $data = (array) $data;
    $companyName = '';
    if ($data['companyname']) {
        $companyName = ' ' . $data['companyname'];
    }
    $client = sprintf('%s %s%s', $data['firstname'], $data['lastname'], $companyName);
    $domain = $data['domain'];
    $date = $data['date'];
    $type = $typeMap[$data['type']];
    $recipients = $data['recipients'];
    $days_before_expiry = sprintf($aInt->lang('domains', 'beforeExpiry'), $data['days_before_expiry']);
    if ($data['days_before_expiry'] < 0) {
        $days_before_expiry = sprintf($aInt->lang('domains', 'afterExpiry'), $data['days_before_expiry'] * -1);
    }
    $reportdata["tablevalues"][] = array($client, $domain, $date, $type, $recipients, $days_before_expiry);
}

?>