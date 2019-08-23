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
$reportdata["title"] = "Sales by Product for " . $months[(int) $month] . " " . $year;
$reportdata["description"] = "This report gives a breakdown of the number of units sold of each product per month";
$reportdata["currencyselections"] = true;
$total = 0;
$datefilter = $year . '-' . $month . '%';
$reportdata["tableheadings"] = array("Product Name", "Units Sold", "Value");
$result = select_query("tblproducts", "tblproducts.id,tblproducts.name,tblproductgroups.name AS groupname", "", "tblproductgroups`.`order` ASC,`tblproducts`.`order` ASC,`name", "ASC", "", "tblproductgroups ON tblproducts.gid=tblproductgroups.id");
while ($data = mysql_fetch_array($result)) {
    $pid = $data["id"];
    $group = $data["groupname"];
    $prodname = $data["name"];
    if ($group != $prevgroup) {
        $reportdata["tablevalues"][] = array("**<b>{$group}</b>");
    }
    $result2 = select_query("tblhosting", "COUNT(*),SUM(tblhosting.firstpaymentamount)", "tblhosting.packageid='{$pid}' AND tblhosting.domainstatus='Active' AND tblhosting.regdate LIKE '" . $datefilter . "' AND tblclients.currency='{$currencyid}'", "", "", "", "tblclients ON tblclients.id=tblhosting.userid");
    $data = mysql_fetch_array($result2);
    $number = $data[0];
    $amount = $data[1];
    $total += $amount;
    $amount = formatCurrency($amount);
    $reportdata["tablevalues"][] = array($prodname, $number, $amount);
    $prevgroup = $group;
}
$reportdata["tablevalues"][] = array("**<b>Addons</b>");
$result = select_query("tbladdons", "", "", "name", "ASC");
while ($data = mysql_fetch_array($result)) {
    $pid = $data["id"];
    $prodname = $data["name"];
    $result2 = select_query("tblhostingaddons", "COUNT(*),SUM(tblhostingaddons.setupfee+tblhostingaddons.recurring)", "tblhostingaddons.addonid='{$pid}' AND tblhostingaddons.status='Active' AND tblhostingaddons.regdate LIKE '{$datefilter}' AND tblclients.currency='{$currencyid}'", "", "", "", "tblhosting ON tblhosting.id=tblhostingaddons.hostingid INNER JOIN tblclients ON tblclients.id=tblhosting.userid");
    $data = mysql_fetch_array($result2);
    $number = $data[0];
    $amount = $data[1];
    $total += $amount;
    $amount = formatCurrency($amount);
    $reportdata["tablevalues"][] = array($prodname, $number, $amount);
    $prevgroup = $group;
}
$total = formatCurrency($total);
$reportdata["footertext"] = '<p align="center"><strong>Total: ' . $total . '</strong></p>';
$reportdata["monthspagination"] = true;

?>