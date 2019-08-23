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
if (!function_exists("RegGetContactDetails")) {
    require ROOTDIR . "/includes/registrarfunctions.php";
}
$result = select_query("tbldomains", "id,domain,registrar,registrationperiod", array("id" => $domainid));
$data = mysql_fetch_array($result);
$domainid = $data[0];
if (!$domainid) {
    $apiresults = array("result" => "error", "message" => "Domain ID Not Found");
    return false;
}
$domain = $data["domain"];
$registrar = $data["registrar"];
$regperiod = $data["registrationperiod"];
$domainparts = explode(".", $domain, 2);
$params = array();
$params["domainid"] = $domainid;
list($params["sld"], $params["tld"]) = $domainparts;
$params["regperiod"] = $regperiod;
$params["registrar"] = $registrar;
$values = RegGetContactDetails($params);
if ($values["error"]) {
    $apiresults = array("result" => "error", "message" => "Registrar Error Message", "error" => $values["error"]);
    return false;
}
foreach ($values as $type => $value) {
    if (is_array($value)) {
        foreach ($value as $type2 => $value2) {
            if (is_array($value2)) {
                foreach ($value2 as $type3 => $value3) {
                    $passback[str_replace(" ", "_", $type)][str_replace(" ", "_", $type2)][str_replace(" ", "_", $type3)] = $value3;
                }
            } else {
                $passback[str_replace(" ", "_", $type)][str_replace(" ", "_", $type2)] = $value2;
            }
        }
    } else {
        $passback[str_replace(" ", "_", $type)] = $value;
    }
}
$responsetype = "xml";
$apiresults = array_merge(array("result" => "success"), $passback);

?>