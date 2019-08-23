<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Widget;

use WHMCS\Carbon;
use WHMCS\Module\AbstractWidget;
/**
 * Automation Widget.
 *
 * @copyright Copyright (c) WHMCS Limited 2005-2018
 * @license https://www.whmcs.com/license/ WHMCS Eula
 */
class Automation extends AbstractWidget
{
    protected $title = 'Automation Overview';
    protected $description = 'An overview of system automation.';
    protected $weight = 20;
    protected $cache = true;
    protected $requiredPermission = 'Configure Automation Settings';
    public function getData()
    {
        return localApi('GetAutomationLog', array('startdate' => date("Y-m-d", time() - 7 * 24 * 60 * 60)));
    }
    public function generateOutput($data)
    {
        try {
            $today = Carbon::createFromFormat('Y-m-d H:i:s', $data['currentDatetime'])->toDateString();
            $lastDailyCronInvocationTime = Carbon::createFromFormat('Y-m-d H:i:s', $data['lastDailyCronInvocationTime']);
            if ($lastDailyCronInvocationTime->toDateString() == Carbon::today()->toDateString()) {
                $lastInvokationTime = '<strong>Today</strong> at ' . $lastDailyCronInvocationTime->format('g:i A');
            } elseif ($lastDailyCronInvocationTime->toDateString() == Carbon::yesterday()->toDateString()) {
                $lastInvokationTime = '<strong>Yesterday</strong> at ' . $lastDailyCronInvocationTime->format('g:i A');
            } else {
                $lastInvokationTime = $lastDailyCronInvocationTime->diffForHumans();
            }
            if (Carbon::now()->diffInHours($lastDailyCronInvocationTime) > 24) {
                $lastInvokationTime .= ' <a href="configauto.php" class="label label-danger">Needs Attention</a>';
            }
        } catch (\Exception $e) {
            $lastInvokationTime = '<strong>Never</strong> <a href="configauto.php" class="label label-danger">Needs Attention</a>';
        }
        if (isset($data['statistics'][$today])) {
            $invoicesCreatedToday = (int) $data['statistics'][$today]['CreateInvoices']['invoice.created'];
            $ccCapturesToday = (int) $data['statistics'][$today]['ProcessCreditCardPayments']['captured'];
            $overdueSuspensionsToday = (int) $data['statistics'][$today]['AutoSuspensions']['suspended'];
            $closedTicketsToday = (int) $data['statistics'][$today]['CloseInactiveTickets']['closed'];
            $cancellationsToday = (int) $data['statistics'][$today]['CancellationRequests']['cancellations'];
            $overdueRemindersToday = (int) $data['statistics'][$today]['InvoiceReminders']['unpaid'] + $data['statistics'][$today]['InvoiceReminders']['overdue.first'] + $data['statistics'][$today]['InvoiceReminders']['overdue.second'] + $data['statistics'][$today]['InvoiceReminders']['overdue.third'];
        } else {
            $invoicesCreatedToday = $ccCapturesToday = $overdueSuspensionsToday = $closedTicketsToday = $cancellationsToday = $overdueRemindersToday = 0;
        }
        $graphData = array();
        foreach ($data['statistics'] as $date => $statistics) {
            $graphData['createinvoices'][] = (int) $statistics['CreateInvoices']['invoice.created'];
            $graphData['processcreditcardpayments'][] = (int) $statistics['ProcessCreditCardPayments']['captured'];
            $graphData['suspensions'][] = (int) $statistics['AutoSuspensions']['suspended'];
            $graphData['closetickets'][] = (int) $statistics['CloseInactiveTickets']['closed'];
            $graphData['cancellationrequests'][] = (int) $statistics['CancellationRequests']['cancellations'];
            $graphData['invoicereminders'][] = (int) $statistics['InvoiceReminders']['unpaid'] + $statistics['InvoiceReminders']['overdue.first'] + $statistics['InvoiceReminders']['overdue.second'] + $statistics['InvoiceReminders']['overdue.third'];
        }
        if (!empty($graphData)) {
            $invoicesCreatedString = implode(',', $graphData['createinvoices']);
            $ccCapturesString = implode(',', $graphData['processcreditcardpayments']);
            $overdueSuspensionsString = implode(',', $graphData['suspensions']);
            $closedTicketsString = implode(',', $graphData['closetickets']);
            $cancellationsString = implode(',', $graphData['cancellationrequests']);
            $overdueRemindersString = implode(',', $graphData['invoicereminders']);
        } else {
            $invoicesCreatedString = $ccCapturesString = $overdueSuspensionsString = $closedTicketsString = $cancellationsString = $overdueRemindersString = '';
        }
        return <<<EOF
<div class="row">
    <div class="col-sm-6">
        <div class="mini-chart">
            <a href="automationstatus.php?metric=CreateInvoices">
                <span class="peity-line" data-peity='{ "fill": "rgba(64, 186, 189, 0.2)", "stroke": "rgba(64, 186, 189, 0.7)", "strokeWidth": 2, "width": 120}'>{$invoicesCreatedString}</span>
            </a>
        </div>
        <h4 class="item-title">
            <span class="title-text">Invoices Created</span>
        </h4>
        <p class="item-figure color-blue">{$invoicesCreatedToday}</p>
    </div>
    <div class="col-sm-6">
        <div class="mini-chart">
            <a href="automationstatus.php?metric=ProcessCreditCardPayments">
                <span class="peity-line" data-peity='{ "fill": "rgba(132, 217, 145, 0.2)", "stroke": "rgba(132, 217, 145, 0.7)", "strokeWidth": 2, "width": 120}'>{$ccCapturesString}</span>
            </a>
        </div>
        <h4 class="item-title">
            <span class="title-text">Credit Card Captures</span>
        </h4>
        <p class="item-figure color-green">{$ccCapturesToday}</p>
    </div>
    <div class="col-sm-6">
        <div class="mini-chart">
            <a href="automationstatus.php?metric=AutoSuspensions">
                <span class="peity-line" data-peity='{ "fill": "rgba(248, 161, 63, 0.2)", "stroke": "rgba(248, 161, 63, 0.7)", "strokeWidth": 2, "width": 120}'>{$overdueSuspensionsString}</span>
            </a>
        </div>
        <h4 class="item-title">
            <span class="title-text">Overdue Suspensions</span>
        </h4>
        <p class="item-figure color-orange">{$overdueSuspensionsToday}</p>
    </div>
    <div class="col-sm-6">
        <div class="mini-chart">
            <a href="automationstatus.php?metric=CloseInactiveTickets">
                <span class="peity-line" data-peity='{ "fill": "rgba(234, 83, 149, 0.2)", "stroke": "rgba(234, 83, 149, 0.7)", "strokeWidth": 2, "width": 120}'>{$closedTicketsString}</span>
            </a>
        </div>
        <h4 class="item-title">
            <span class="title-text">Inactive Tickets Closed</span>
        </h4>
        <p class="item-figure color-pink">{$closedTicketsToday}</p>
    </div>
    <div class="col-sm-6">
        <div class="mini-chart">
            <a href="automationstatus.php?metric=InvoiceReminders">
                <span class="peity-line" data-peity='{ "fill": "rgba(30, 30, 30, 0.2)", "stroke": "rgba(30, 30, 30, 0.4)", "strokeWidth": 2, "width": 120}'>{$overdueRemindersString}</span>
            </a>
        </div>
        <h4 class="item-title">
            <span class="title-text">Overdue Reminders</span>
        </h4>
        <p class="item-figure color-grey">{$overdueRemindersToday}</p>
    </div>
    <div class="col-sm-6">
        <div class="mini-chart">
            <a href="automationstatus.php?metric=CancellationRequests">
                <span class="peity-line" data-peity='{ "fill": "rgba(144, 31, 197, 0.2)", "stroke": "rgba(144, 31, 197, 0.7)", "strokeWidth": 2, "width": 120}'>{$cancellationsString}</span>
            </a>
        </div>
        <h4 class="item-title">
            <span class="title-text">Cancellations Processed</span>
        </h4>
        <p class="item-figure color-purple">{$cancellationsToday}</p>
    </div>
    <div class="col-sm-12 text-footer">
        <i class="fas fa-check-circle fa-fw"></i>
        Last Automation Run: {$lastInvokationTime}
    </div>
</div>

<script>
\$(document).ready(function() {
    \$.fn.peity.defaults.line = {
        delimiter: ",",
        fill: "#92d1d2",
        height: 32,
        max: null,
        min: 0,
        stroke: "#40babd",
        strokeWidth: 1,
        width: 100
    }
    \$(".peity-line").peity("line");
});
</script>
EOF;
    }
}

?>