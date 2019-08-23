<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
if (!function_exists("getCustomFields")) {
    require ROOTDIR . "/includes/customfieldfunctions.php";
}
if (!function_exists("getCartConfigOptions")) {
    require ROOTDIR . "/includes/configoptionsfunctions.php";
}
$where = array();
if ($clientid) {
    $where["tbldomains.userid"] = $clientid;
}
if ($domainid) {
    $where["tbldomains.id"] = $domainid;
}
if ($domain) {
    $where["tbldomains.domain"] = $domain;
}
$result = select_query("tbldomains", "COUNT(*)", $where);
$data = mysql_fetch_array($result);
$totalresults = $data[0];
$limitstart = (int) $limitstart;
$limitnum = (int) $limitnum;
if (!$limitnum) {
    $limitnum = 25;
}
$result = select_query("tbldomains", "tbldomains.*,(SELECT tblpaymentgateways.value FROM tblpaymentgateways WHERE tblpaymentgateways.gateway=tbldomains.paymentmethod AND tblpaymentgateways.setting='name' LIMIT 1) AS paymentmethodname", $where, "tbldomains`.`id", "ASC", (string) $limitstart . "," . $limitnum);
$apiresults = array("result" => "success", "clientid" => $clientid, "domainid" => $domainid, "totalresults" => $totalresults, "startnumber" => $limitstart, "numreturned" => mysql_num_rows($result));
if (!$totalresults) {
    $apiresults["domains"] = "";
}
while ($data = mysql_fetch_array($result)) {
    $id = $data["id"];
    $userid = $data["userid"];
    $orderid = $data["orderid"];
    $type = $data["type"];
    $registrationdate = $data["registrationdate"];
    $domain = $data["domain"];
    $firstpaymentamount = $data["firstpaymentamount"];
    $recurringamount = $data["recurringamount"];
    $registrar = $data["registrar"];
    $registrationperiod = $data["registrationperiod"];
    $expirydate = $data["expirydate"];
    $nextduedate = $data["nextduedate"];
    $status = $data["status"];
    $subscriptionid = $data["subscriptionid"];
    $promoid = $data["promoid"];
    $additionalnotes = $data["additionalnotes"];
    $paymentmethod = $data["paymentmethod"];
    $paymentmethodname = $data["paymentmethodname"];
    $dnsmanagement = $data["dnsmanagement"];
    $emailforwarding = $data["emailforwarding"];
    $idprotection = $data["idprotection"];
    $donotrenew = $data["donotrenew"];
    $nameservers = array();
    if ($getnameservers) {
        if (!function_exists("RegGetNameservers")) {
            require ROOTDIR . "/includes/registrarfunctions.php";
        }
        $domainparts = explode(".", $domain, 2);
        $params = array();
        $params["domainid"] = $id;
        list($params["sld"], $params["tld"]) = $domainparts;
        $params["regperiod"] = $registrationperiod;
        $params["registrar"] = $registrar;
        $nameservers = RegGetNameservers($params);
        $nameservers["nameservers"] = true;
    }
    $apiresults["domains"]["domain"][] = array_merge(array("id" => $id, "userid" => $userid, "orderid" => $orderid, "regtype" => $type, "domainname" => $domain, "registrar" => $registrar, "regperiod" => $registrationperiod, "firstpaymentamount" => $firstpaymentamount, "recurringamount" => $recurringamount, "paymentmethod" => $paymentmethod, "paymentmethodname" => $paymentmethodname, "regdate" => $registrationdate, "expirydate" => $expirydate, "nextduedate" => $nextduedate, "status" => $status, "subscriptionid" => $subscriptionid, "promoid" => $promoid, "dnsmanagement" => $dnsmanagement, "emailforwarding" => $emailforwarding, "idprotection" => $idprotection, "donotrenew" => $donotrenew, "notes" => $additionalnotes), $nameservers);
}
$responsetype = "xml";

?>