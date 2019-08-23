<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
$reportdata["title"] = "Server Revenue Forecasts";
$reportdata["description"] = "This report shows income broken down by billing cycle for each of your servers.  It then uses the monthly cost entered for each server to estimate the annual gross profit for each server.";
$reportdata["tableheadings"] = array("Server Income", "Monthly", "Quarterly", "Semi-Annual", "Annual", "Biennial", "Triennial", "Monthly Costs", "Annual Gross Profit");
$currency = getCurrency('', '1');
$query = "SELECT * FROM tblservers WHERE disabled=0 ORDER BY name ASC";
$result = full_query($query);
while ($data = mysql_fetch_array($result)) {
    $id = $data["id"];
    $name = $data["name"];
    $monthlycost = $data["monthlycost"];
    $monthly = $quarterly = $semiannually = $annually = $biennially = $triennially = 0;
    $query2 = "SELECT tblhosting.*,tblhosting.amount/tblcurrencies.rate AS reportamt FROM tblhosting INNER JOIN tblclients ON tblclients.id=tblhosting.userid INNER JOIN tblcurrencies ON tblcurrencies.id=tblclients.currency WHERE server='" . (int) $id . "' AND (domainstatus='Active' OR domainstatus='Suspended') AND billingcycle!='Free Account' AND billingcycle!='One Time'";
    $result2 = full_query($query2);
    while ($data = mysql_fetch_array($result2)) {
        $amount = $data["reportamt"];
        $billingcycle = $data["billingcycle"];
        if ($billingcycle == "Monthly") {
            $monthly += $amount;
        } elseif ($billingcycle == "Quarterly") {
            $quarterly += $amount;
        } elseif ($billingcycle == "Semi-Annually") {
            $semiannually += $amount;
        } elseif ($billingcycle == "Annually") {
            $annually += $amount;
        } elseif ($billingcycle == "Biennially") {
            $biennially += $amount;
        } elseif ($billingcycle == "Triennially") {
            $triennially += $amount;
        }
    }
    $monthly = number_format($monthly, 2, ".", "");
    $quarterly = number_format($quarterly, 2, ".", "");
    $semiannually = number_format($semiannually, 2, ".", "");
    $annually = number_format($annually, 2, ".", "");
    $biennially = number_format($biennially, 2, ".", "");
    $triennially = number_format($triennially, 2, ".", "");
    $totalserverincome = $monthly * 12 + $quarterly * 4 + $semiannually * 2 + $annually + $biennially / 2 + $triennially / 3;
    $totalserverexpenditure = $monthlycost * 12;
    $servertotal = number_format($totalserverincome - $totalserverexpenditure, 2, ".", "");
    $totalincome += $totalserverincome;
    $totalexpenditure += $totalserverexpenditure;
    $totalgrossprofit += $servertotal;
    $reportdata["tablevalues"][] = array("{$name}", formatCurrency($monthly), formatCurrency($quarterly), formatCurrency($semiannually), formatCurrency($annually), formatCurrency($biennially), formatCurrency($triennially), formatCurrency($monthlycost), formatCurrency($servertotal));
}
$totalincome = formatCurrency($totalincome);
$totalexpenditure = formatCurrency($totalexpenditure);
$totalgrossprofit = formatCurrency($totalgrossprofit);
$data["footertext"] = "<B>Total Income:</B> {$totalincome}<br><B>Total Expenses:</B> {$totalexpenditure}</br><B>Gross Profit:</B> {$totalgrossprofit}";

?>