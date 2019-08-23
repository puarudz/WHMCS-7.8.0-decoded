<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Automation Status");
$aInt->title = "Automation Status";
$aInt->sidebar = "clients";
$aInt->icon = "clients";
$aInt->helplink = "Automation Status";
$date = App::getFromRequest("date");
$nextDayDisabled = false;
if ($date) {
    $date = WHMCS\Carbon::createFromFormat("Y-m-d", $date);
    if ($date->format("m") != date("m")) {
        $dateDisplayLabel = $date->format("l jS F");
    } else {
        $dateDisplayLabel = $date->format("l jS");
    }
} else {
    $date = WHMCS\Carbon::today();
}
if ($date->isToday()) {
    $dateDisplayLabel = "Today";
    $nextDayDisabled = true;
} else {
    if ($date->isYesterday()) {
        $dateDisplayLabel = "Yesterday";
    }
}
$tasks = array("CreateInvoices", "AddLateFees", "ProcessCreditCardPayments", "InvoiceReminders", "CancellationRequests", "AutoSuspensions", "AutoTerminations", "FixedTermTerminations", "DomainRenewalNotices", "DomainTransferSync", "DomainStatusSync", "CloseInactiveTickets", "AffiliateCommissions", "EmailMarketer", "DatabaseBackup", "CheckForWhmcsUpdate", "CurrencyUpdateExchangeRates", "CurrencyUpdateProductPricing", "UpdateServerUsage");
$graphMetric = App::getFromRequest("metric");
$allowedGraphMetrics = array("CreateInvoices" => "Invoices", "AddLateFees" => "Late Fees", "ProcessCreditCardPayments" => "Credit Cards", "InvoiceReminders" => "Invoice Reminders", "CancellationRequests" => "Cancellation Requests", "AutoSuspensions" => "Auto Suspensions", "AutoTerminations" => "Auto Terminations", "DomainRenewalNotices" => "Domain Renewal Notices", "CloseInactiveTickets" => "Close Inactive Tickets");
if (!array_key_exists($graphMetric, $allowedGraphMetrics)) {
    $graphMetric = key($allowedGraphMetrics);
}
$graphPeriod = App::getFromRequest("period");
$allowedGraphPeriods = array("thisweek" => "This Week", "lastweek" => "Last Week", "last30days" => "Last 30 Days", "thismonth" => "This Month", "lastmonth" => "Last Month");
if (!array_key_exists($graphPeriod, $allowedGraphPeriods)) {
    $graphPeriod = key($allowedGraphPeriods);
}
ob_start();
echo "\n<div class=\"graph-filters\">\n\n    <div class=\"btn-group btn-group-sm graph-filter-metric\">\n        <button type=\"button\" class=\"btn btn-default dropdown-toggle\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">\n            Viewing ";
echo $allowedGraphMetrics[$graphMetric];
echo " <span class=\"caret\"></span>\n        </button>\n        <ul class=\"dropdown-menu pull-right\">\n";
foreach ($allowedGraphMetrics as $metric => $displayName) {
    echo "            <li><a href=\"";
    echo $metric;
    echo "\"";
    if ($graphMetric == $metric) {
        echo " class=\"active\"";
    }
    echo ">";
    echo $displayName;
    echo "</a></li>\n";
}
echo "        </ul>\n    </div>\n\n    <div class=\"btn-group btn-group-sm graph-filter-period\">\n        <button type=\"button\" class=\"btn btn-default dropdown-toggle\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">\n            ";
echo $allowedGraphPeriods[$graphPeriod];
echo " <span class=\"caret\"></span>\n        </button>\n        <ul class=\"dropdown-menu pull-right\">\n";
foreach ($allowedGraphPeriods as $period => $displayName) {
    echo "            <li><a href=\"";
    echo $period;
    echo "\"";
    if ($graphPeriod == $period) {
        echo " class=\"active\"";
    }
    echo ">";
    echo $displayName;
    echo "</a></li>\n";
}
echo "        </ul>\n    </div>\n\n</div>\n\n<div id=\"overviewChartContainer\">\n    <canvas id=\"overviewChart\" height=\"270\"></canvas>\n</div>\n\n";
if ($graphPeriod == "thisweek") {
    $startDate = WHMCS\Carbon::today()->subWeek();
    $endDate = WHMCS\Carbon::today();
} else {
    if ($graphPeriod == "lastweek") {
        $startDate = WHMCS\Carbon::today()->subWeeks(2);
        $endDate = WHMCS\Carbon::today()->subWeek(1);
    } else {
        if ($graphPeriod == "last30days") {
            $startDate = WHMCS\Carbon::today()->subDays(30);
            $endDate = WHMCS\Carbon::today();
        } else {
            if ($graphPeriod == "thismonth") {
                $startDate = new WHMCS\Carbon("first day of this month");
                $endDate = WHMCS\Carbon::today();
            } else {
                if ($graphPeriod == "lastmonth") {
                    $startDate = new WHMCS\Carbon("first day of last month");
                    $endDate = (new WHMCS\Carbon("first day of this month"))->subDay();
                }
            }
        }
    }
}
$data = localAPI("GetAutomationLog", array("namespace" => $graphMetric, "startdate" => $startDate->toDateString(), "enddate" => $endDate->toDateString()));
$statistics = $data["statistics"];
$taskName = "\\WHMCS\\Cron\\Task\\" . $graphMetric;
$task = $taskName::firstOfClassOrNew();
$namespaceName = $task->getNamespace();
$successCountIdentifier = $task->getSuccessCountIdentifier();
$graphLabels = array();
$graphData = array();
for ($i = 0; $i < 32; $i++) {
    $graphLabels[] = $startDate->format("jS");
    if (is_array($successCountIdentifier)) {
        $primarySuccessCount = 0;
        foreach ($successCountIdentifier as $identifier) {
            $primarySuccessCount += (int) $statistics[$startDate->toDateString()][$namespaceName][$identifier];
        }
    } else {
        $primarySuccessCount = (int) $statistics[$startDate->toDateString()][$namespaceName][$successCountIdentifier];
    }
    $graphData[] = (int) $primarySuccessCount;
    if ($startDate->toDateString() == $endDate->toDateString()) {
        break;
    }
    $startDate->addDay();
}
echo "\n<script>\n\$(document).ready(function() {\n\n    var canvas = document.getElementById(\"overviewChart\");\n    var parent = document.getElementById('overviewChartContainer');\n\n    canvas.width = parent.offsetWidth;\n    canvas.height = parent.offsetHeight;\n\n    var config = {\n        type: 'line',\n        data: {\n            labels: [\"";
echo implode("\",\"", $graphLabels);
echo "\"],\n            datasets: [{\n                label: \"Success Count\",\n                backgroundColor: 'rgba(255, 205, 86, 0.4)',\n                borderColor: 'rgba(255, 205, 86, 0.8)',\n                data: [\n                    ";
echo implode(",", $graphData);
echo "                ],\n                fill: true,\n            }]\n        },\n        options: {\n            responsive: true,\n            legend: {\n                display: false\n            },\n            scales: {\n                xAxes: [{\n                    display: true,\n                    scaleLabel: {\n                        display: false,\n                        labelString: 'Month'\n                    },\n                }],\n                yAxes: [{\n                    display: true,\n                    scaleLabel: {\n                        display: false,\n                        labelString: 'Count'\n                    },\n                    ticks: {\n                        beginAtZero: true\n                    }\n                }]\n            }\n        }\n    };\n\n    var ctx = document.getElementById(\"overviewChart\").getContext(\"2d\");\n    window.automationStatusChart = new Chart(ctx, config);\n});\n</script>\n\n";
$graphOutput = ob_get_contents();
ob_end_clean();
ob_start();
echo "\n<div class=\"row\">\n    <div class=\"col-lg-12\">\n\n        <div class=\"btn-group day-selector\" role=\"group\">\n            <a href=\"#\" class=\"btn btn-day-nav\" data-date=\"";
$navDate = clone $date;
echo $navDate->subDay()->toDateString();
echo "\">\n                <i class=\"fas fa-chevron-left\"></i>\n            </a>\n            <a href=\"#\" class=\"btn btn-viewing\">\n                ";
echo $dateDisplayLabel;
echo "            </a>\n            <a href=\"#\" class=\"btn btn-day-nav";
if ($nextDayDisabled) {
    echo " disabled";
}
echo "\" data-date=\"";
$navDate = clone $date;
echo $navDate->addDay()->toDateString();
echo "\">\n                <i class=\"fas fa-chevron-right\"></i>\n            </a>\n        </div>\n\n        <h2>Daily Actions</h2>\n\n    </div>\n</div>\n\n<div class=\"row\">\n    <div class=\"col-lg-9\">\n        <div class=\"row widgets-container\">\n\n";
$data = localAPI("GetAutomationLog", array("startdate" => $date->toDateString(), "enddate" => $date->toDateString()));
$statistics = $data["statistics"];
$isDisabledMap = array("AddLateFees" => WHMCS\Config\Setting::getValue("InvoiceLateFeeAmount") == 0, "AutoSuspensions" => !WHMCS\Config\Setting::getValue("AutoSuspension"), "AutoTerminations" => !WHMCS\Config\Setting::getValue("AutoTermination"), "CloseInactiveTickets" => WHMCS\Config\Setting::getValue("CloseInactiveTickets") == 0, "DatabaseBackup" => !WHMCS\Config\Setting::getValue("DailyEmailBackup") || !WHMCS\Config\Setting::getValue("FTPBackupHostname"), "CurrencyUpdateExchangeRates" => !WHMCS\Config\Setting::getValue("CurrencyAutoUpdateExchangeRates"), "CurrencyUpdateProductPricing" => !WHMCS\Config\Setting::getValue("CurrencyAutoUpdateProductPrices"), "DomainStatusSync" => !WHMCS\Config\Setting::getValue("DomainSyncEnabled"), "DomainTransferSync" => !WHMCS\Config\Setting::getValue("DomainSyncEnabled"));
foreach ($tasks as $task) {
    $taskName = "\\WHMCS\\Cron\\Task\\" . $task;
    $task = $taskName::firstOfClassOrNew();
    $namespaceName = $task->getNamespace();
    $decorator = new WHMCS\Cron\Decorator($task);
    $data = $statistics[$date->toDateString()][$namespaceName];
    $isDisabled = array_key_exists($namespaceName, $isDisabledMap) ? $isDisabledMap[$namespaceName] : false;
    echo "<div class=\"col-md-4 col-sm-6\">" . $decorator->render($data, $isDisabled) . "</div>";
}
echo "\n        </div>\n    </div>\n    <div class=\"col-lg-3\">\n\n        <div class=\"calendar-container\">\n\n             <script>\n              \$(document).ready(function(){\n                \$( \"#automation-status-calendar\" ).datepicker({\n                    maxDate: new Date,\n                    dateFormat: 'yy-mm-dd',\n                    defaultDate: '";
echo $date->toDateString();
echo "',\n                    onSelect: function (date) {\n                        \$('#automation-status-calendar').datepicker('setDate', date);\n                        loadAutomationStatsForDate(date);\n                    }\n                });\n              });\n              </script>\n\n            <div id=\"automation-status-calendar\"></div>\n\n        </div>\n\n    </div>\n</div>\n\n";
$statsOutput = ob_get_contents();
ob_end_clean();
$cron = new WHMCS\Cron();
ob_start();
echo "\n<div class=\"automation-status\">\n\n    <div class=\"row home-status-badge-row\">\n        <div class=\"col-sm-4\">\n\n            <div class=\"health-status-block status-badge-";
echo $cron->hasCronBeenInvokedIn24Hours() ? "green" : "pink";
echo " clearfix\">\n                <div class=\"icon\">\n                    ";
if ($cron->hasCronBeenInvokedIn24Hours()) {
    echo "                        <i class=\"fas fa-check\"></i>\n                    ";
} else {
    if ($cron->hasCronEverBeenInvoked()) {
        echo "                        <i class=\"fas fa-exclamation-triangle\"></i>\n                    ";
    } else {
        echo "                        <i class=\"fas fa-times\"></i>\n                    ";
    }
}
echo "                </div>\n                <div class=\"detail\">\n                    <span class=\"count\">\n                        ";
if ($cron->hasCronBeenInvokedIn24Hours()) {
    echo "                            Ok\n                        ";
} else {
    if ($cron->hasCronEverBeenInvoked()) {
        echo "                            Not Ok\n                        ";
    } else {
        echo "                            Not Configured\n                        ";
    }
}
echo "                    </span>\n                    <span class=\"desc\">Automation Status</span>\n                </div>\n            </div>\n\n        </div>\n        <div class=\"col-sm-4\">\n\n            <div class=\"health-status-block status-badge-orange clearfix\">\n                <div class=\"icon\">\n                    <i class=\"fas fa-calendar-alt\"></i>\n                </div>\n                <div class=\"detail\">\n                    <span class=\"count\">";
if ($lastInvocationTime = $cron->getLastCronInvocationTime()) {
    echo $lastInvocationTime->diffForHumans();
} else {
    echo "Never";
}
echo "</span>\n                    <span class=\"desc\">Last Cron Invocation</span>\n                </div>\n            </div>\n\n        </div>\n        <div class=\"col-sm-4\">\n\n            <div class=\"health-status-block status-badge-grey clearfix\">\n                <div class=\"icon\">\n                    <i class=\"far fa-calendar-check\"></i>\n                </div>\n                <div class=\"detail\">\n                    <span class=\"count\">";
$lastDailyCronInvocationTime = $cron->getLastDailyCronInvocationTime();
echo $lastDailyCronInvocationTime instanceof WHMCS\Carbon ? $lastDailyCronInvocationTime->addDay()->diffForHumans(NULL, true) : "N/A";
echo "</span>\n                    <span class=\"desc\">Next Daily Task Run</span>\n                </div>\n            </div>\n\n        </div>\n    </div>\n\n    ";
if ($cron->hasCronBeenInvokedIn24Hours()) {
    echo "    ";
} else {
    if ($cron->hasCronEverBeenInvoked()) {
        echo "        <div class=\"alert alert-warning\">\n            <strong>Warning</strong>\n            No cron run detected within the last 24 hours. Please double check your cron configuration.<br />\n            <small>Last Run: ";
        echo $lastInvocationTime;
        echo "</small>\n        </div>\n    ";
    } else {
        echo "        <div class=\"alert alert-warning\">\n            <strong>Warning</strong>\n            No cron run recorded. Please ensure you have configured the necessary <a href=\"configauto.php\" class=\"alert-link\">cron related settings</a>.\n        </div>\n    ";
    }
}
echo "\n    <div id=\"graphContainer\" class=\"graph-container\">\n        ";
echo $graphOutput;
echo "    </div>\n\n    <div id=\"statsContainer\">\n        ";
echo $statsOutput;
echo "    </div>\n\n</div>\n\n";
$content = ob_get_contents();
ob_end_clean();
$action = App::getFromRequest("action");
if ($action == "stats") {
    $aInt->jsonResponse(array("status" => "1", "body" => $statsOutput));
} else {
    if ($action == "graph") {
        $aInt->jsonResponse(array("status" => "1", "body" => $graphOutput));
    }
}
$aInt->content = $content;
$aInt->display();

?>