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
$GATEWAYMODULE["worldpayinvisiblename"] = "worldpayinvisible";
$GATEWAYMODULE["worldpayinvisiblevisiblename"] = "WorldPay Invisible";
$GATEWAYMODULE["worldpayinvisibletype"] = "CC";
function worldpayinvisible_activate()
{
    defineGatewayField("worldpayinvisible", "text", "installationid", "", "Installation ID", "20", "Enter your WorldPay Installation ID");
    defineGatewayField("worldpayinvisible", "text", "authpw", "", "Auth Password", "20", "Enter your WorldPay Authorisation Password here");
    defineGatewayField("worldpayinvisible", "yesno", "preauth", "", "PreAuth Mode", "", "");
    defineGatewayField("worldpayinvisible", "yesno", "testmode", "", "Test Mode", "", "");
}
function worldpayinvisible_capture($params)
{
    $gatewayurl = "https://secure.worldpay.com/wcc/itransaction";
    $query_string = "instId=" . $params["installationid"];
    $query_string .= "&cartId=" . $params["invoiceid"];
    if ($params["testmode"] == "on") {
        $query_string .= "&testMode=100";
    }
    $query_string .= "&currency=" . $params["currency"];
    $query_string .= "&desc=" . $params["description"];
    $query_string .= "&amount=" . $params["amount"];
    $query_string .= "&authPW=" . $params["authpw"];
    $query_string .= "&cardNo=" . $params["cardnum"];
    $query_string .= "&cardExpMonth=" . substr($params["cardexp"], 0, 2);
    $query_string .= "&cardExpYear=20" . substr($params["cardexp"], 2, 2);
    if ($params["cccvv"]) {
        $query_string .= "&cardCVV=" . $params["cccvv"];
    }
    if ($params["cardissuenum"]) {
        $query_string .= "&cardIssueNo=" . $params["cardissuenum"];
    }
    if ($params["cardstart"]) {
        $query_string .= "&cardStartMonth=" . substr($params["cardstart"], 0, 2);
        $query_string .= "&cardStartYear=20" . substr($params["cardstart"], 2, 2);
    }
    $query_string .= "&cardName=" . $params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"];
    $query_string .= "&address=" . $params["clientdetails"]["address1"] . "&#10;" . $params["clientdetails"]["city"] . "&#10;" . $params["clientdetails"]["state"];
    $query_string .= "&country=" . $params["clientdetails"]["country"];
    $query_string .= "&postcode=" . $params["clientdetails"]["postcode"];
    $query_string .= "&tel=" . $params["clientdetails"]["phonenumber"];
    $query_string .= "&email=" . $params["clientdetails"]["email"];
    $query_string .= "&literalResult=" . urlencode("<wpdisplay item=rawAuthCode>,<wpdisplay item=transId default='-1'>,<wpdisplay item=rawAuthMessage>,<wpdisplay item=countryMatch>,<wpdisplay item=AVS>,<wpdisplay item=wafMerchMessage>");
    if ($params["preauth"]) {
        $query_string .= "&authMode=A";
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
    curl_setopt($ch, CURLOPT_URL, $gatewayurl);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $gatewayresult = curl_exec($ch);
    if (curl_errno($ch)) {
        $gatewayresult = "CURL Error: " . curl_error($ch);
    }
    curl_close($ch);
    if (substr($gatewayresult, 0, 1) == "A") {
        $returndata = explode(",", $gatewayresult);
        return array("status" => "success", "transid" => $returndata[1], "rawdata" => $gatewayresult);
    }
    return array("status" => "declined", "rawdata" => $gatewayresult);
}
function worldpayinvisible_refund($params)
{
    $gatewayurl = "https://secure.worldpay.com/wcc/authorise";
    $query_string = "authPW=" . $params["authpw"];
    $query_string .= "&instId=" . $params["installationid"];
    $query_string .= "&cartId=Refund" . $params["invoiceid"];
    $query_string .= "&op=refund-full";
    $query_string .= "&transId=" . $params["transid"];
    $query_string .= "&currency=" . $params["currency"];
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
    curl_setopt($ch, CURLOPT_URL, $gatewayurl);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $gatewayresult = curl_exec($ch);
    if (curl_errno($ch)) {
        $gatewayresult = "CURL Error: " . curl_error($ch);
    }
    curl_close($ch);
    if (substr($gatewayresult, 0, 1) == "A") {
        $returndata = explode(",", $gatewayresult);
        return array("status" => "success", "transid" => $returndata[1], "rawdata" => $gatewayresult);
    }
    return array("status" => "error", "rawdata" => $gatewayresult);
}

?>