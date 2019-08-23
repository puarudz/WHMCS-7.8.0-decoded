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
function mwarrior_config()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "Merchant Warrior"), "merchantUUID" => array("FriendlyName" => "Merchant UUID", "Type" => "text", "Size" => "20"), "apiKey" => array("FriendlyName" => "API Key", "Type" => "text", "Size" => "20"), "apiPassphrase" => array("FriendlyName" => "API Passphrase", "Type" => "text", "Size" => "20"), "testmode" => array("FriendlyName" => "Test Mode", "Type" => "yesno", "Description" => "Tick this to send requests to the test environment."));
    return $configarray;
}
function mwarrior_capture($params)
{
    if (isset($params["testmode"]) && $params["testmode"]) {
        $endpointTarget = "base";
    } else {
        $endpointTarget = "api";
    }
    $endpoint = "https://" . $endpointTarget . ".merchantwarrior.com/post/";
    $postData["method"] = "processCard";
    $postData["merchantUUID"] = $params["merchantUUID"];
    $postData["apiKey"] = $params["apiKey"];
    $postData["transactionProduct"] = $params["invoiceid"];
    $postData["transactionAmount"] = $params["amount"];
    $postData["transactionCurrency"] = "AUD";
    $postData["customerName"] = $params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"];
    $postData["customerEmail"] = $params["clientdetails"]["email"];
    $postData["customerCountry"] = $params["clientdetails"]["country"];
    $postData["customerState"] = $params["clientdetails"]["state"];
    $postData["customerCity"] = $params["clientdetails"]["city"];
    $postData["customerPostCode"] = $params["clientdetails"]["postcode"];
    $postData["customerPhone"] = $params["clientdetails"]["phonenumber"];
    $postData["customerIP"] = $_SERVER["REMOTE_ADDR"];
    $address = $params["clientdetails"]["address1"];
    if (isset($params["clientdetails"]["address2"]) && strlen($params["clientdetails"]["address2"])) {
        $address .= ", " . $params["clientdetails"]["address2"];
    }
    $postData["customerAddress"] = $address;
    $postData["paymentCardName"] = $postData["customerName"];
    $postData["paymentCardNumber"] = $params["cardnum"];
    $postData["paymentCardExpiry"] = $params["cardexp"];
    $postData["paymentCardCSC"] = $params["cccvv"];
    $postData["hash"] = md5(strtolower($params["apiPassphrase"] . $params["merchantUUID"] . $postData["transactionAmount"] . $postData["transactionCurrency"]));
    try {
        list($xmlObj, $xml) = mwarrior_sendRequest($endpoint, $postData);
        $status = (int) $xml["responseCode"] === 0 ? "success" : "declined";
    } catch (Exception $e) {
        $status = "error";
    }
    $results = array("status" => $status, "transID" => isset($xml["transactionID"]) ? $xml["transactionID"] : NULL, "transAmount" => $params["amount"], "endpoint" => $endpoint, "xml" => $xmlObj instanceof SimpleXMLElement ? $xmlObj->asXML() : NULL);
    if ($results["status"] == "success") {
        $tarnsID = (string) $results["transID"] . "|" . $params["amount"];
        return array("status" => "success", "transid" => $transID, "rawdata" => $results);
    }
    if ($results["status"] == "declined") {
        return array("status" => "declined", "rawdata" => $results);
    }
    return array("status" => "error", "rawdata" => $results);
}
function mwarrior_refund($params)
{
    list($transID, $origAmount) = explode("|", $params["transid"]);
    if (isset($params["testmode"]) && $params["testmode"]) {
        $endpointTarget = "base";
    } else {
        $endpointTarget = "api";
    }
    $endpoint = "https://" . $endpointTarget . ".merchantwarrior.com/post/";
    $postData["method"] = "refundCard";
    $postData["merchantUUID"] = $params["merchantUUID"];
    $postData["apiKey"] = $params["apiKey"];
    $postData["transactionAmount"] = number_format($origAmount, 2, ".", "");
    $postData["transactionCurrency"] = "AUD";
    $postData["transactionID"] = $transID;
    $postData["refundAmount"] = number_format($params["amount"], 2, ".", "");
    $postData["hash"] = md5(strtolower($params["apiPassphrase"] . $params["merchantUUID"] . $postData["transactionAmount"] . $postData["transactionCurrency"]));
    try {
        list($xmlObj, $xml) = mwarrior_sendRequest($endpoint, $postData);
        $status = (int) $xml["responseCode"] === 0 ? "success" : "declined";
    } catch (Exception $e) {
        $status = "error";
    }
    $results = array("status" => $status, "transID" => isset($xml["transactionID"]) ? $xml["transactionID"] : NULL, "endpoint" => $endpoint, "xml" => $xmlObj instanceof SimpleXMLElement ? $xmlObj->asXML() : NULL);
    if ($results["status"] == "success") {
        return array("status" => "success", "transid" => $results["transID"], "rawdata" => $results);
    }
    if ($results["status"] == "declined") {
        return array("status" => "declined", "rawdata" => $results);
    }
    return array("status" => "error", "rawdata" => $results);
}
function mwarrior_sendRequest($url, $postData)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_TIMEOUT, 60);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
    curl_setopt($curl, CURLOPT_FORBID_REUSE, true);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($postData, "", "&"));
    $response = curl_exec($curl);
    $error = curl_error($curl);
    if (isset($error) && strlen($error)) {
        throw new Exception("CURL Error: " . $error);
    }
    if (!isset($response) || strlen($response) < 1) {
        throw new Exception("API response was empty");
    }
    $xmlObj = simplexml_load_string($response);
    $xml = (array) $xmlObj;
    if (!isset($xml["responseCode"]) || strlen($xml["responseCode"]) < 1) {
        throw new Exception("API Response did not contain a valid responseCode");
    }
    return array($xmlObj, $xml);
}

?>