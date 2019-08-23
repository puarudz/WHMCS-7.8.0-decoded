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
function ippay_config()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "IP.Pay"), "terminalid" => array("FriendlyName" => "Terminal ID", "Type" => "text", "Size" => "25", "Description" => "Your Terminal ID assigned by IPpay"), "testmode" => array("FriendlyName" => "Test Mode", "Type" => "yesno"));
    return $configarray;
}
function ippay_capture($params)
{
    global $remote_ip;
    $url = $params["testmode"] ? "https://testgtwy.ippay.com/ippay" : "https://gtwy.ippay.com/ippay";
    $transid = $params["invoiceid"] . date("YmdHis");
    $transid = substr($transid, 0, 18);
    $transid = str_pad($transid, 18, "0", STR_PAD_LEFT);
    $xmldata = "<JetPay>\n    <TransactionType>SALE</TransactionType>\n    <TerminalID>" . $params["terminalid"] . "</TerminalID>\n    <TransactionID>" . $transid . "</TransactionID>\n    <CardNum>" . $params["cardnum"] . "</CardNum>\n    <CardExpMonth>" . substr($params["cardexp"], 0, 2) . "</CardExpMonth>\n    <CardExpYear>" . substr($params["cardexp"], 2, 2) . "</CardExpYear>";
    if ($params["cccvv"]) {
        $xmldata .= "<CVV2>" . $params["cccvv"] . "</CVV2>";
    }
    if ($params["cardissuenum"]) {
        $xmldata .= "<Issue>" . $params["cardissuenum"] . "</Issue>";
    }
    if ($params["cardstart"]) {
        $xmldata .= "<CardStartMonth>" . substr($params["cardstart"], 0, 2) . "</CardStartMonth>\n<CardStartYear>" . substr($params["cardstart"], 0, 2) . "</CardStartYear>";
    }
    $xmldata .= "<TotalAmount>" . $params["amount"] * 100 . "</TotalAmount>\n    <CardName>" . $params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"] . "</CardName>\n    <BillingAddress>" . $params["clientdetails"]["address1"] . "</BillingAddress>\n    <BillingCity>" . $params["clientdetails"]["city"] . "</BillingCity>\n    <BillingStateProv>" . $params["clientdetails"]["state"] . "</BillingStateProv>\n    <BillingPostalCode>" . $params["clientdetails"]["postcode"] . "</BillingPostalCode>\n    <BillingPhone>" . $params["clientdetails"]["phonenumber"] . "</BillingPhone>\n    <Email>" . $params["clientdetails"]["email"] . "</Email>\n    <UserIPAddress>" . $remote_ip . "</UserIPAddress>\n    <Origin>RECURRING</Origin>\n    <UDField1>" . $params["invoiceid"] . "</UDField1>\n    </JetPay>";
    $response = curlCall($url, $xmldata);
    $response = XMLtoArray($response);
    $response = $response["JETPAYRESPONSE"];
    if ($response["ACTIONCODE"] == "000") {
        return array("status" => "success", "transid" => $response["TRANSACTIONID"], "rawdata" => $response);
    }
    return array("status" => "declined", "rawdata" => $response);
}
function ippay_refund($params)
{
    global $remote_ip;
    $url = $params["testmode"] ? "https://testgtwy.ippay.com/ippay" : "https://gtwy.ippay.com/ippay";
    $transid = $params["invoiceid"] . date("YmdHis");
    $transid = substr($transid, 0, 18);
    $transid = str_pad($transid, 18, "0", STR_PAD_LEFT);
    $xmldata = "<JetPay>\n    <TransactionType>CREDIT</TransactionType>\n    <TerminalID>" . $params["terminalid"] . "</TerminalID>\n    <TransactionID>" . $transid . "</TransactionID>\n    <CardNum>" . $params["cardnum"] . "</CardNum>\n    <CardExpMonth>" . substr($params["cardexp"], 0, 2) . "</CardExpMonth>\n    <CardExpYear>" . substr($params["cardexp"], 2, 2) . "</CardExpYear>\n    <TotalAmount>" . $params["amount"] * 100 . "</TotalAmount>\n    </JetPay>";
    $response = curlCall($url, $xmldata);
    $response = XMLtoArray($response);
    $response = $response["JETPAYRESPONSE"];
    if ($response["ACTIONCODE"] == "000") {
        return array("status" => "success", "transid" => $response["TRANSACTIONID"], "rawdata" => $response);
    }
    return array("status" => "error", "rawdata" => $response);
}

?>