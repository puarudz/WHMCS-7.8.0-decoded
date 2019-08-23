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
$GATEWAYMODULE["usaepayname"] = "usaepay";
$GATEWAYMODULE["usaepayvisiblename"] = "USA ePay";
$GATEWAYMODULE["usaepaytype"] = "CC";
function usaepay_activate()
{
    defineGatewayField("usaepay", "text", "key", "", "Key", "40", "");
    defineGatewayField("usaepay", "text", "pin", "", "PIN", "40", "An optional PIN string (only enter if you assigned a PIN in USA ePay merchant console)");
    defineGatewayField("usaepay", "yesno", "testmode", "", "Test Mode", "", "Check to enable test mode");
}
function _usaepay_calculate_UMhash($params, $postfields)
{
    $algo = "sha1";
    $seed = strtoupper(Illuminate\Support\Str::random(16));
    $hashBaseParts = array($postfields["UMcommand"], $params["pin"], $postfields["UMamount"], $postfields["UMinvoice"], $seed);
    $hashBase = implode(":", $hashBaseParts);
    $hash = hash($algo, $hashBase);
    return "s/" . $seed . "/" . $hash . "/n";
}
function usaepay_capture($params)
{
    global $remote_ip;
    $url = "https://www.usaepay.com/gate";
    if ($params["testmode"] == "on") {
        $url = "https://sandbox.usaepay.com/gate";
    }
    $postfields = array();
    $postfields["UMcommand"] = "cc:sale";
    $postfields["UMkey"] = $params["key"];
    $postfields["UMignoreDuplicate"] = "yes";
    $postfields["UMcard"] = $params["cardnum"];
    $postfields["UMexpir"] = $params["cardexp"];
    $postfields["UMamount"] = $params["amount"];
    $postfields["UMinvoice"] = $params["invoiceid"];
    $postfields["UMname"] = $params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"];
    $postfields["UMstreet"] = $params["clientdetails"]["address1"];
    $postfields["UMzip"] = $params["clientdetails"]["postcode"];
    $postfields["UMcvv2"] = $params["cccvv"];
    $postfields["UMip"] = $remote_ip;
    if (isset($params["pin"]) && 0 < strlen($params["pin"])) {
        $postfields["UMhash"] = _usaepay_calculate_umhash($params, $postfields);
    }
    $query_string = "";
    foreach ($postfields as $k => $v) {
        $query_string .= (string) $k . "=" . urlencode($v) . "&";
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($ch);
    if (curl_error($ch)) {
        $result = "CURL Error: " . curl_error($ch);
    }
    curl_close($ch);
    $tmp = explode("\n", $result);
    $result = $tmp[count($tmp) - 1];
    parse_str($result, $tmp);
    if ($tmp["UMresult"] == "A") {
        return array("status" => "success", "transid" => $tmp["UMrefNum"], "rawdata" => $tmp);
    }
    if ($tmp["UMresult"] == "E") {
        return array("status" => "error", "rawdata" => $tmp);
    }
    return array("status" => "declined", "rawdata" => $tmp);
}

?>