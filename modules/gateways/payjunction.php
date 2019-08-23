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
$GATEWAYMODULE["payjunctionname"] = "payjunction";
$GATEWAYMODULE["payjunctionvisiblename"] = "Pay Junction";
$GATEWAYMODULE["payjunctiontype"] = "CC";
function payjunction_activate()
{
    defineGatewayField("payjunction", "text", "dc_logon", "", "Logon", "20", "The username identifying your account");
    defineGatewayField("payjunction", "text", "dc_password", "", "Password", "20", "The password for your account");
}
function payjunction_capture($params)
{
    $url = "https://payjunction.com/quick_link";
    $fields["dc_logon"] = $params["dc_logon"];
    $fields["dc_password"] = $params["dc_password"];
    $fields["dc_first_name"] = $params["clientdetails"]["firstname"];
    $fields["dc_last_name"] = $params["clientdetails"]["lastname"];
    $fields["dc_address"] = $params["clientdetails"]["address1"];
    $fields["dc_city"] = $params["clientdetails"]["city"];
    $fields["dc_state"] = $params["clientdetails"]["state"];
    $fields["dc_zipcode"] = $params["clientdetails"]["postcode"];
    $fields["dc_country"] = $params["clientdetails"]["country"];
    $fields["dc_number"] = $params["cardnum"];
    $fields["dc_expiration_month"] = substr($params["cardexp"], 0, 2);
    $fields["dc_expiration_year"] = substr($params["cardexp"], 2, 2);
    $fields["dc_verification_number"] = $params["cccvv"];
    $fields["dc_transaction_amount"] = $params["amount"];
    $fields["dc_notes"] = $params["description"];
    $fields["dc_transaction_type"] = "AUTHORIZATION_CAPTURE";
    $fields["dc_test"] = "No";
    $fields["dc_version"] = "1.2";
    $query_string = "";
    foreach ($fields as $k => $v) {
        $query_string .= (string) $k . "=" . urlencode($v) . "&";
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $gatewayresult = curl_exec($ch);
    if (curl_errno($ch)) {
        $gatewayresult = "CurlError=" . curl_error($ch);
    }
    curl_close($ch);
    $content = explode(chr(28), $gatewayresult);
    foreach ($content as $key_value) {
        list($key, $value) = explode("=", $key_value);
        $response[$key] = $value;
    }
    $transid = isset($response["dc_transaction_id"]) ? $response["dc_transaction_id"] : "";
    if (strcmp($response["dc_response_code"], "00") == 0 || strcmp($response["dc_response_code"], "85") == 0) {
        return array("status" => "success", "transid" => $transid, "rawdata" => $response);
    }
    return array("status" => "declined", "rawdata" => $response);
}
function payjunction_refund($params)
{
    $url = "https://payjunction.com/quick_link";
    $fields["dc_logon"] = $params["dc_logon"];
    $fields["dc_password"] = $params["dc_password"];
    $fields["dc_first_name"] = $params["clientdetails"]["firstname"];
    $fields["dc_last_name"] = $params["clientdetails"]["lastname"];
    $fields["dc_address"] = $params["clientdetails"]["address1"];
    $fields["dc_city"] = $params["clientdetails"]["city"];
    $fields["dc_state"] = $params["clientdetails"]["state"];
    $fields["dc_zipcode"] = $params["clientdetails"]["postcode"];
    $fields["dc_country"] = $params["clientdetails"]["country"];
    $fields["dc_number"] = $params["cardnum"];
    $fields["dc_expiration_month"] = substr($params["cardexp"], 0, 2);
    $fields["dc_expiration_year"] = substr($params["cardexp"], 2, 2);
    $fields["dc_transaction_amount"] = $params["amount"];
    $fields["dc_notes"] = $params["description"];
    $fields["dc_transaction_type"] = "CREDIT";
    $fields["dc_version"] = "1.2";
    $query_string = "";
    foreach ($fields as $k => $v) {
        $query_string .= (string) $k . "=" . urlencode($v) . "&";
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $gatewayresult = curl_exec($ch);
    if (curl_errno($ch)) {
        $gatewayresult = "CurlError=" . curl_error($ch);
    }
    curl_close($ch);
    $content = explode(chr(28), $gatewayresult);
    foreach ($content as $key_value) {
        list($key, $value) = explode("=", $key_value);
        $response[$key] = $value;
    }
    $debugoutput = "";
    foreach ($response as $k => $v) {
        $debugoutput .= (string) $k . " => " . $v . "\n";
    }
    $transid = isset($response["dc_transaction_id"]) ? $response["dc_transaction_id"] : "";
    if (strcmp($response["dc_response_code"], "00") == 0 || strcmp($response["dc_response_code"], "85") == 0) {
        refundInvoicePayment($params["invoiceid"], $transid);
        $transactionStatus = "Successful";
        $result = "success";
    } else {
        $transactionStatus = "Declined";
        $result = "declined";
    }
    logTransaction($params["paymentmethod"], $debugoutput, $transactionStatus);
    return $result;
}

?>