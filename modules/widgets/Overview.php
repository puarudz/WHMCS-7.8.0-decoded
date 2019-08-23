<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Widget;

use AdminLang;
use App;
use WHMCS\Carbon;
use WHMCS\Database\Capsule;
use WHMCS\Module\AbstractWidget;
use WHMCS\Order\Order;
/**
 * System Overview Widget.
 *
 * @copyright Copyright (c) WHMCS Limited 2005-2018
 * @license https://www.whmcs.com/license/ WHMCS Eula
 */
class Overview extends AbstractWidget
{
    protected $title = 'System Overview';
    protected $description = 'An overview of orders and income.';
    protected $columns = 2;
    protected $weight = 10;
    protected $requiredPermission = 'View Income Totals';
    public function getData()
    {
        $today = Carbon::today()->startOfDay()->toDateString();
        $month = Carbon::today()->subMonth()->startOfDay()->toDateString();
        $year = Carbon::today()->subYear()->startOfMonth()->startOfDay()->toDateString();
        $dates = ['today' => '%k', 'month' => '%e %M', 'year' => '%M %Y'];
        $orderData = $completeOrderData = array();
        foreach ($dates as $dateType => $format) {
            $data = Order::where('date', '>', ${$dateType})->select([Capsule::raw('date_format(date, \'' . $format . '\') as format_date'), Capsule::raw('COUNT(id) as id_count'), 'status'])->groupBy('format_date', 'status')->get();
            $xAxisData = $data->pluck('format_date');
            $todayData = $completedData = [];
            foreach ($xAxisData as $xAxisDatum) {
                $todayData[$xAxisDatum] = $data->where('format_date', $xAxisDatum)->sum('id_count');
                $completedData[$xAxisDatum] = $data->where('format_date', $xAxisDatum)->where('status', 'Active')->sum('id_count');
            }
            $orderData[$dateType] = $todayData;
            $completeOrderData[$dateType] = $completedData;
        }
        $incomeData = array();
        $result = full_query("SELECT date_format(date, '%k'), SUM(amountin/rate), SUM(amountout/rate) FROM tblaccounts WHERE date>'{$today}' GROUP BY date_format(date, '%k')");
        while ($data = mysql_fetch_array($result)) {
            $incomeData['today'][$data[0]] = $data[1];
        }
        $result = full_query("SELECT date_format(date, '%e %M'), SUM(amountin/rate), SUM(amountout/rate) FROM tblaccounts WHERE date>'{$month}' GROUP BY date_format(date, '%e %M')");
        while ($data = mysql_fetch_array($result)) {
            $incomeData['month'][$data[0]] = $data[1];
        }
        $result = full_query("SELECT date_format(date, '%M %Y'), SUM(amountin/rate), SUM(amountout/rate) FROM tblaccounts WHERE date>'{$year}' GROUP BY date_format(date, '%M %Y')");
        while ($data = mysql_fetch_array($result)) {
            $incomeData['year'][$data[0]] = $data[1];
        }
        return array('orders' => array('new' => $orderData, 'complete' => $completeOrderData), 'revenue' => array('income' => $incomeData));
    }
    public function generateOutput($data)
    {
        $viewPeriod = App::getFromRequest('viewperiod');
        if (!in_array($viewPeriod, array('today', 'month', 'year'))) {
            $viewPeriod = 'today';
        }
        $orderData = isset($data['orders']['new'][$viewPeriod]) ? $data['orders']['new'][$viewPeriod] : [];
        $completedOrderData = [];
        if (isset($data['orders']['complete'][$viewPeriod])) {
            $completedOrderData = $data['orders']['complete'][$viewPeriod];
        }
        $incomeData = isset($data['revenue']['income'][$viewPeriod]) ? $data['revenue']['income'][$viewPeriod] : [];
        $graphLabels = $graphData = $graphData2 = $graphData3 = array();
        if ($viewPeriod == 'today') {
            for ($i = 0; $i <= date("H"); $i++) {
                $graphLabels[] = date("ga", mktime($i, date("i"), date("s"), date("m"), date("d"), date("Y")));
                $graphData[] = isset($orderData[$i]) ? $orderData[$i] : 0;
                $graphData2[] = isset($incomeData[$i]) ? $incomeData[$i] : 0;
                $graphData3[] = isset($completedOrderData[$i]) ? $completedOrderData[$i] : 0;
            }
        } elseif ($viewPeriod == 'month') {
            for ($i = 0; $i < 30; $i++) {
                $time = mktime(0, 0, 0, date("m"), date("d") - $i, date("Y"));
                $graphLabels[] = date("jS", $time);
                $graphData[] = isset($orderData[date("j F", $time)]) ? $orderData[date("j F", $time)] : 0;
                $graphData2[] = isset($incomeData[date("j F", $time)]) ? $incomeData[date("j F", $time)] : 0;
                $graphData3[] = isset($completedOrderData[date("j F", $time)]) ? $completedOrderData[date("j F", $time)] : 0;
            }
            $graphLabels = array_reverse($graphLabels);
            $graphData = array_reverse($graphData);
            $graphData2 = array_reverse($graphData2);
            $graphData3 = array_reverse($graphData3);
        } elseif ($viewPeriod == 'year') {
            for ($i = 0; $i < 12; $i++) {
                $time = mktime(0, 0, 0, date("m") - $i, 1, date("Y"));
                $graphLabels[] = date("F y", $time);
                $graphData[] = isset($orderData[date("F Y", $time)]) ? $orderData[date("F Y", $time)] : 0;
                $graphData2[] = isset($incomeData[date("F Y", $time)]) ? $incomeData[date("F Y", $time)] : 0;
                $graphData3[] = isset($completedOrderData[date("F Y", $time)]) ? $completedOrderData[date("F Y", $time)] : 0;
            }
            $graphLabels = array_reverse($graphLabels);
            $graphData = array_reverse($graphData);
            $graphData2 = array_reverse($graphData2);
            $graphData3 = array_reverse($graphData3);
        }
        $graphLabels = '"' . implode('","', $graphLabels) . '"';
        $graphData = implode(',', $graphData);
        $graphData2 = implode(',', $graphData2);
        $graphData3 = implode(',', $graphData3);
        $activeToday = $viewPeriod == 'today' ? ' active' : '';
        $activeThisMonth = $viewPeriod == 'month' ? ' active' : '';
        $activeThisYear = $viewPeriod == 'year' ? ' active' : '';
        $langToday = AdminLang::trans('billing.incometoday');
        $langActiveThisMonth = AdminLang::trans('billing.incomethismonth');
        $langActiveThisYear = AdminLang::trans('billing.incomethisyear');
        return <<<EOF
<div style="padding:20px;">
    <div class="btn-group btn-group-sm btn-period-chooser" role="group" aria-label="...">
        <button type="button" class="btn btn-default{$activeToday}" data-period="today">{$langToday}</button>
        <button type="button" class="btn btn-default{$activeThisMonth}" data-period="month">{$langActiveThisMonth}</button>
        <button type="button" class="btn btn-default{$activeThisYear}" data-period="year">{$langActiveThisYear}</button>
    </div>
</div>

<div style="width:100%;height:317px;overflow:hidden">
    <div id="myChartParent">
        <canvas id="myChart" height="277"></canvas>
    </div>
</div>

<script>

\$(document).ready(function() {
    var chartObject = null;
    var windowResizeTimeoutId = null;

    \$('.btn-period-chooser button').click(function() {
        \$('.btn-period-chooser button').removeClass('active');
        \$(this).addClass('active');
        refreshWidget('Overview', 'viewperiod=' + \$(this).data('period'));
    });

    \$(window).resize(function() {
        if (windowResizeTimeoutId) {
            clearTimeout(windowResizeTimeoutId);
            windowResizeTimeoutId = null;
        }

        windowResizeTimeoutId = setTimeout(function() {
            if (typeof chartObject === 'object') {
                chartObject.resize(false);
            }
        }, 250);
    });

    var lineData = {
        labels: [{$graphLabels}],
        datasets: [
            {
                label: "New Orders",
                backgroundColor: "rgba(220,220,220,0.5)",
                borderColor: "rgba(220,220,220,1)",
                pointBackgroundColor: "rgba(220,220,220,1)",
                pointBorderColor: "#fff",
                yAxisID: "y-axis-0",
                data: [{$graphData}],
                type: 'line',
                lineTension: 0
            },
            {
                label: "Activated Orders",
                backgroundColor: "rgba(51,92,249,0.5)",
                borderColor: "rgba(51,92,249,1)",
                pointBackgroundColor: "rgba(51,92,249,1)",
                pointBorderColor: "#fff",
                yAxisID: "y-axis-0",
                data: [{$graphData3}]
            },
            {
                label: "Income",
                backgroundColor: "rgba(93,197,96,0.5)",
                borderColor: "rgba(93,197,96,1)",
                pointBackgroundColor: "rgba(93,197,96,1)",
                pointBorderColor: "#fff",
                yAxisID: "y-axis-1",
                data: [{$graphData2}],
                type: 'line'
            }
        ]
    };

    var canvas = document.getElementById("myChart");
    var parent = document.getElementById('myChartParent');

    canvas.width = parent.offsetWidth;
    canvas.height = parent.offsetHeight;

    var ctx = \$("#myChart");
    var chartObject = new Chart(ctx, {
        type: 'bar',
        data: lineData,
        options: {
            responsive: false,
            maintainAspectRatio: false,
            responsiveAnimationDuration: 500,
            scales: {
                yAxes: [
                    {
                        position: "left",
                        "id": "y-axis-0",
                        scaleLabel: {
                            display: true,
                            labelString: 'Orders'
                        },
                        ticks: {
                            beginAtZero: true
                        }
                    },
                    {
                        position: "right",
                        "id": "y-axis-1",
                        scaleLabel: {
                            display: true,
                            labelString: 'Income'
                        },
                        ticks: {
                            beginAtZero: true
                        }
                    }
                ]
            }
        }
    });
});
</script>
EOF;
    }
}

?>