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
$GATEWAYMODULE["chronopayname"] = "chronopay";
$GATEWAYMODULE["chronopayvisiblename"] = "ChronoPay";
$GATEWAYMODULE["chronopaytype"] = "Invoices";
function chronopay_activate()
{
    defineGatewayField("chronopay", "text", "productid", "", "Product ID", "20", "The product ID of a generic product in your ChronoPay Account");
    defineGatewayField("chronopay", "text", "sharedsecret", "", "Shared Secret", "30", "The shared secret is a unique code known only by ChronoPay and the Merchant");
}
function chronopay_link($params)
{
    $operationChecksum = md5(sprintf("%s-%s-%s", $params["productid"], $params["amount"], $params["sharedsecret"]));
    $code = "\n<form action=\"https://payments.chronopay.com/\" method=\"post\">\n<input type=\"hidden\" name=\"sign\" value=\"" . $operationChecksum . "\">\n<input type=\"hidden\" name=\"product_id\" value=\"" . $params["productid"] . "\">\n<input type=\"hidden\" name=\"product_name\" value=\"" . $params["description"] . "\">\n<input type=\"hidden\" name=\"product_price\" value=\"" . $params["amount"] . "\">\n<input type=\"hidden\" name=\"product_price_currency\" value=\"" . $params["currency"] . "\">\n<input type=\"hidden\" name=\"f_name\" value=\"" . $params["clientdetails"]["firstname"] . "\">\n<input type=\"hidden\" name=\"s_name\" value=\"" . $params["clientdetails"]["lastname"] . "\">\n<input type=\"hidden\" name=\"email\" value=\"" . $params["clientdetails"]["email"] . "\">\n<input type=\"hidden\" name=\"street\" value=\"" . $params["clientdetails"]["address1"] . "\">\n<input type=\"hidden\" name=\"city\" value=\"" . $params["clientdetails"]["city"] . "\">\n<input type=\"hidden\" name=\"state\" value=\"" . $params["clientdetails"]["state"] . "\">\n<input type=\"hidden\" name=\"zip\" value=\"" . $params["clientdetails"]["postcode"] . "\">\n<input type=\"hidden\" name=\"country\" value=\"" . $params["clientdetails"]["country"] . "\">\n<input type=\"hidden\" name=\"phone\" value=\"" . $params["clientdetails"]["phonenumber"] . "\">\n<input type=\"hidden\" name=\"cs1\" value=\"" . $params["invoiceid"] . "\">\n<input type=\"hidden\" name=\"cb_url\" value=\"" . $params["systemurl"] . "/modules/gateways/callback/chronopay.php\">\n<input type=\"hidden\" name=\"cb_type\" value=\"P\">\n<input type=\"hidden\" name=\"success_url\" value=\"" . $params["returnurl"] . "&paymentsuccess=true\">\n<input type=\"hidden\" name=\"decline_url\" value=\"" . $params["returnurl"] . "&paymentfailed=true\">\n<input type=\"submit\" value=\"" . $params["langpaynow"] . "\">\n</form> \n";
    return $code;
}

?>