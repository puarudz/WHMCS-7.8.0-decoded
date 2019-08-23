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
$GATEWAYMODULE["paysonname"] = "payson";
$GATEWAYMODULE["paysonvisiblename"] = "Payson";
$GATEWAYMODULE["paysontype"] = "Invoices";
function payson_activate()
{
    defineGatewayField("payson", "text", "agentid", "", "Agent ID", "15", "");
    defineGatewayField("payson", "text", "email", "", "Seller Email", "50", "");
    defineGatewayField("payson", "text", "key", "", "Key", "20", "");
    defineGatewayField("payson", "yesno", "guaranteeoffered", "", "Offer Payson Guarantee", "", "");
}
function payson_link($params)
{
    $AgentID = $params["agentid"];
    $Key = $params["key"];
    $Description = $params["description"];
    $SellerEmail = $params["email"];
    $BuyerEmail = $params["clientdetails"]["email"];
    $BuyerFirstName = $params["clientdetails"]["firstname"];
    $BuyerLastName = $params["clientdetails"]["lastname"];
    $Cost = str_replace(".", ",", $params["amount"]);
    $CurrencyCode = $params["currency"];
    $ExtraCost = "0";
    $OkUrl = $params["systemurl"] . "/modules/gateways/callback/payson.php";
    $CancelUrl = $params["returnurl"];
    $RefNr = $params["invoiceid"];
    $GuaranteeOffered = $params["guaranteeoffered"] ? "2" : "1";
    $MD5string = $SellerEmail . ":" . $Cost . ":" . $ExtraCost . ":" . $OkUrl . ":" . $GuaranteeOffered . $Key;
    $MD5Hash = md5($MD5string);
    $code = "\n<form action=\"https://www.payson.se/merchant/default.aspx\" method=\"post\">\n<input type=\"hidden\" name=\"BuyerEmail\" value=\"" . $BuyerEmail . "\"> \n<input type=\"hidden\" name=\"AgentID\" value=\"" . $AgentID . "\"> \n<input type=\"hidden\" name=\"Description\" value=\"" . $Description . "\"> \n<input type=\"hidden\" name=\"SellerEmail\" value=\"" . $SellerEmail . "\">\n<input type=\"hidden\" name=\"BuyerFirstName\" value=\"" . $BuyerFirstName . "\">\n<input type=\"hidden\" name=\"BuyerLastName\" value=\"" . $BuyerLastName . "\">\n<input type=\"hidden\" name=\"Cost\" value=\"" . $Cost . "\">\n<input type=\"hidden\" name=\"CurrencyCode\" value=\"" . $CurrencyCode . "\">\n<input type=\"hidden\" name=\"ExtraCost\" value=\"" . $ExtraCost . "\">\n<input type=\"hidden\" name=\"OkUrl\" value=\"" . $OkUrl . "\"> \n<input type=\"hidden\" name=\"CancelUrl\" value=\"" . $CancelUrl . "\"> \n<input type=\"hidden\" name=\"RefNr\" value=\"" . $RefNr . "\"> \n<input type=\"hidden\" name=\"MD5\" value=\"" . $MD5Hash . "\">\n<input type=\"hidden\" name=\"GuaranteeOffered\" value=\"" . $GuaranteeOffered . "\"> \n<input type=\"submit\" value=\"" . $params["langpaynow"] . "\">\n</form>\n";
    return $code;
}

?>