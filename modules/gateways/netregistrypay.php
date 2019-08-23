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
$GATEWAYMODULE = array("netregistrypayname" => "netregistrypay", "netregistrypayvisiblename" => "Netregistry Pay", "netregistrypaytype" => "CC");
function netregistrypay_activate()
{
    defineGatewayField("netregistrypay", "text", "merchantid", "", "Merchant ID (MID) number", "20", "");
    defineGatewayField("netregistrypay", "text", "externalpassword", "", "External access password", "20", "");
}
function netregistrypay_capture($params)
{
    $gatewayusername = $params["merchantid"];
    $gatewaypassword = $params["externalpassword"];
    $invoiceid = $params["invoiceid"];
    $amount = $params["amount"];
    $currency = $params["currency"];
    $firstname = $params["clientdetails"]["firstname"];
    $lastname = $params["clientdetails"]["lastname"];
    $email = $params["clientdetails"]["email"];
    $address1 = $params["clientdetails"]["address1"];
    $address2 = $params["clientdetails"]["address2"];
    $city = $params["clientdetails"]["city"];
    $state = $params["clientdetails"]["state"];
    $postcode = $params["clientdetails"]["postcode"];
    $country = $params["clientdetails"]["country"];
    $phone = $params["clientdetails"]["phone"];
    $cardtype = $params["cardtype"];
    $cardnumber = $params["cardnum"];
    $cardexpiry = $params["cardexp"];
    $cardstart = $params["cardstart"];
    $cardissuenum = $params["cardissuenum"];
    $txnref = "Unknown";
    $params = array("COMMAND" => "purchase", "LOGIN" => $gatewayusername . "/" . $gatewaypassword, "AMOUNT" => number_format($amount, 2, ".", ""), "CCNUM" => $cardnumber, "CCEXP" => substr($cardexpiry, 0, 2) . "/" . substr($cardexpiry, 2, 3), "COMMENT" => $firstname . $lastname . " WHMCS Invoice ID:" . $invoiceid);
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, "https://paygate.ssllock.net/external2.pl");
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($curl);
    if (isset($result)) {
        $exploded_result = explode("\n", $result);
        if ($exploded_result[0] == "approved") {
            $success = true;
            foreach ($exploded_result as $result) {
                if (strpos($result, "txn_ref") !== false) {
                    $txnref = substr($result, strpos($result, "=") + 1, strlen($result));
                }
            }
        } else {
            if ($exploded_result[0] == "declined") {
                $declined = true;
            }
        }
    }
    if (isset($success) && $success) {
        return array("status" => "success", "transid" => $txnref, "rawdata" => $result);
    }
    if (isset($declined) && $declined) {
        return array("status" => "declined", "rawdata" => $result);
    }
    return array("status" => "error", "rawdata" => $result);
}

?>