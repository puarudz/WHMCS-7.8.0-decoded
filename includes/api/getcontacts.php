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
if (!$limitstart) {
    $limitstart = 0;
}
if (!$limitnum) {
    $limitnum = 25;
}
$where = array();
if ($userid) {
    $where["userid"] = $userid;
}
if ($firstname) {
    $where["firstname"] = $firstname;
}
if ($lastname) {
    $where["lastname"] = $lastname;
}
if ($companyname) {
    $where["companyname"] = $companyname;
}
if ($email) {
    $where["email"] = $email;
}
if ($address1) {
    $where["address1"] = $address1;
}
if ($address2) {
    $where["address2"] = $address2;
}
if ($city) {
    $where["city"] = $city;
}
if ($state) {
    $where["state"] = $state;
}
if ($postcode) {
    $where["postcode"] = $postcode;
}
if ($country) {
    $where["country"] = $country;
}
if ($phonenumber) {
    $where["phonenumber"] = $phonenumber;
}
if ($subaccount) {
    $where["subaccount"] = "1";
}
$result = select_query("tblcontacts", "COUNT(*)", $where);
$data = mysql_fetch_array($result);
$totalresults = $data[0];
$result = select_query("tblcontacts", "", $where, "id", "ASC", (string) $limitstart . "," . $limitnum);
$apiresults = array("result" => "success", "totalresults" => $totalresults, "startnumber" => $limitstart, "numreturned" => mysql_num_rows($result));
while ($data = mysql_fetch_assoc($result)) {
    $apiresults["contacts"]["contact"][] = $data;
}
$responsetype = "xml";

?>