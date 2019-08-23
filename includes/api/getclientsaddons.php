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
$query = WHMCS\Database\Capsule::table("tblhostingaddons")->distinct()->join("tblhosting", "tblhosting.id", "=", "tblhostingaddons.hostingid")->join("tbladdons", "tbladdons.id", "=", "tblhostingaddons.addonid", "LEFT");
if ($serviceid) {
    if (is_numeric($serviceid)) {
        $query = $query->where("tblhostingaddons.hostingid", "=", $serviceid);
    } else {
        $serviceids = array_map("trim", explode(",", $serviceid));
        $query = $query->whereIn("tblhostingaddons.hostingid", $serviceids);
    }
}
if ($clientid) {
    $query = $query->where("tblhosting.userid", "=", $clientid);
}
if ($addonid) {
    $query = $query->where("tblhostingaddons.addonid", "=", $addonid);
}
$query = $query->orderBy("tblhostingaddons.id", "ASC");
$result = $query->get(array("tblhostingaddons.*", "tblhosting.userid", "tbladdons.name AS addon_name"));
$apiresults = array("result" => "success", "serviceid" => $serviceid, "clientid" => $clientid, "totalresults" => count($result));
foreach ($result as $data) {
    $addonarray = array("id" => $data->id, "userid" => $data->userid, "orderid" => $data->orderid, "serviceid" => $data->hostingid, "addonid" => $data->addonid, "name" => $data->name ?: $data->addon_name, "setupfee" => $data->setupfee, "recurring" => $data->recurring, "billingcycle" => $data->billingcycle, "tax" => $data->tax, "status" => $data->status, "regdate" => $data->regdate, "nextduedate" => $data->nextduedate, "nextinvoicedate" => $data->nextinvoicedate, "paymentmethod" => $data->paymentmethod, "notes" => $data->notes);
    $apiresults["addons"]["addon"][] = $addonarray;
}
$responsetype = "xml";

?>