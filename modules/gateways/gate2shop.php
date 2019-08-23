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
$GATEWAYMODULE["gate2shopname"] = "gate2shop";
$GATEWAYMODULE["gate2shopvisiblename"] = "Gate2Shop";
$GATEWAYMODULE["gate2shoptype"] = "Invoices";
function gate2shop_activate()
{
    defineGatewayField("gate2shop", "text", "MerchantID", "", "MerchantID", "40", "");
    defineGatewayField("gate2shop", "text", "MerchantSiteID", "", "MerchantSiteID", "25", "");
    defineGatewayField("gate2shop", "text", "SecretKey", "", "SecretKey", "90", "");
}
function gate2shop_link($params)
{
    $shipping = 0;
    $discount = 0;
    $total_tax = 0;
    $totalAmount = $params["amount"];
    $sTimestamp = date("Y-m-d.h:i:s");
    $sCheckString = $params["SecretKey"];
    $sCheckString .= $params["MerchantID"];
    $sCheckString .= $params["currency"];
    $sCheckString .= $totalAmount;
    $sCheckString .= $params["description"] . $totalAmount . "1";
    $sCheckString .= $sTimestamp;
    $checksum = md5($sCheckString);
    $sMerchantLocale = "en_US";
    $numberOfItems = 1;
    $code = "<form action=\"https://secure.gate2shop.com/ppp/purchase.do\" method=\"post\">\n<input type=\"hidden\" name=\"encoding\" value=\"utf-8\">\n<input type=\"hidden\" name=\"customField1\" value=\"" . $params["invoiceid"] . "\">\n<input type=\"hidden\" name=\"merchant_id\" value=\"" . $params["MerchantID"] . "\">\n<input type=\"hidden\" name=\"merchant_site_id\" value=\"" . $params["MerchantSiteID"] . "\">\n<input type=\"hidden\" name=\"merchantLocale\" value=\"" . $sMerchantLocale . "\">\n<input type=\"hidden\" name=\"first_name\" value=\"" . $params["clientdetails"]["firstname"] . "\">\n<input type=\"hidden\" name=\"last_name\" value=\"" . $params["clientdetails"]["lastname"] . "\">\n<input type=\"hidden\" name=\"email\" value=\"" . $params["clientdetails"]["email"] . "\">\n<input type=\"hidden\" name=\"address1\" value=\"" . $params["clientdetails"]["address1"] . "\">\n<input type=\"hidden\" name=\"address2\" value=\"" . $params["clientdetails"]["address2"] . "\">\n<input type=\"hidden\" name=\"city\" value=\"" . $params["clientdetails"]["city"] . "\">\n<input type=\"hidden\" name=\"country\" value=\"" . $params["clientdetails"]["country"] . "\">\n<input type=\"hidden\" name=\"zip\" value=\"" . $params["clientdetails"]["postcode"] . "\">\n<input type=\"hidden\" name=\"phone1\" value=\"" . $params["clientdetails"]["phonenumber"] . "\">\n<input type=\"hidden\" name=\"version\" value=\"3.0.0\">\n<input type=\"hidden\" name=\"currency\" value=\"" . $params["currency"] . "\">\n<input type=\"hidden\" name=\"time_stamp\" value=\"" . $sTimestamp . "\">\n<input type=\"hidden\" name=\"item_name_1\" value=\"" . $params["description"] . "\" />\n<input type=\"hidden\" name=\"item_amount_1\" value=\"" . format_as_currency($totalAmount - $shipping) . "\" />\n<input type=\"hidden\" name=\"item_quantity_1\" value=1 />\n<input type=\"hidden\" name=\"numberofitems\" value=\"" . $numberOfItems . "\">\n<input type=\"hidden\" name=\"discount\" value=\"" . $discount . "\">\n<input type=\"hidden\" name=\"total_tax\" value=\"" . $total_tax . "\">\n<input type=\"hidden\" name=\"total_amount\" value=\"" . $totalAmount . "\">\n<input type=\"hidden\" name=\"checksum\" value=\"" . $checksum . "\">\n<input type=\"submit\" value=\"" . $params["langpaynow"] . "\">\n</form>";
    return $code;
}

?>