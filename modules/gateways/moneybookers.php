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
function moneybookers_config()
{
    return array("FriendlyName" => array("Type" => "System", "Value" => "Skrill Hosted Payment Solution"), "merchantemail" => array("FriendlyName" => "Merchant Email", "Type" => "text", "Size" => "50", "Description" => "The email address used to identify you to Skrill"), "secretword" => array("FriendlyName" => "Secret Word", "Type" => "text", "Size" => "30", "Description" => "Must match what is set in the Merchant Tools section of your Skrill Account"));
}
function moneybookers_link($params)
{
    global $CONFIG;
    $language = $CONFIG["Language"];
    if ($params["clientdetails"]["language"]) {
        $language = $params["clientdetails"]["language"];
    }
    $languagecode = "EN";
    if ($language == "German") {
        $languagecode = "DE";
    }
    if ($language == "Spanish") {
        $languagecode = "ES";
    }
    if ($language == "French") {
        $languagecode = "FR";
    }
    if ($language == "Turkish") {
        $languagecode = "TR";
    }
    if ($language == "Italian") {
        $languagecode = "IT";
    }
    $code = "<form action=\"https://pay.skrill.com\" method=\"post\">\n<input type=\"hidden\" name=\"pay_to_email\" value=\"" . $params["merchantemail"] . "\">\n<input type=\"hidden\" name=\"pay_from_email\" value=\"" . $params["clientdetails"]["email"] . "\">\n<input type=\"hidden\" name=\"language\" value=\"" . $languagecode . "\">\n<input type=\"hidden\" name=\"amount\" value=\"" . $params["amount"] . "\">\n<input type=\"hidden\" name=\"currency\" value=\"" . $params["currency"] . "\">\n<input type=\"hidden\" name=\"recipient_description\" value=\"" . $CONFIG["CompanyName"] . "\">\n<input type=\"hidden\" name=\"detail1_description\" value=\"" . $params["description"] . "\">\n<input type=\"hidden\" name=\"detail1_text\" value=\"" . $params["invoiceid"] . "\">\n<input type=\"hidden\" name=\"return_url\" value=\"" . $params["returnurl"] . "\">\n<input type=\"hidden\" name=\"cancel_url\" value=\"" . $params["returnurl"] . "&paymentfailed=true\">\n<input type=\"hidden\" name=\"status_url\" value=\"" . $params["systemurl"] . "/modules/gateways/callback/moneybookers.php\">\n<input type=\"hidden\" name=\"transaction_id\" value=\"" . substr($params["invoiceid"] . time(), 0, 100) . "\">\n<input type=\"hidden\" name=\"firstname\" value=\"" . $params["clientdetails"]["firstname"] . "\">\n<input type=\"hidden\" name=\"lastname\" value=\"" . $params["clientdetails"]["lastname"] . "\">\n<input type=\"hidden\" name=\"address\" value=\"" . $params["clientdetails"]["address1"] . "\">\n<input type=\"hidden\" name=\"city\" value=\"" . $params["clientdetails"]["city"] . "\">\n<input type=\"hidden\" name=\"state\" value=\"" . $params["clientdetails"]["state"] . "\">\n<input type=\"hidden\" name=\"postal_code\" value=\"" . $params["clientdetails"]["postcode"] . "\">\n<input type=\"hidden\" name=\"merchant_fields\" value=\"platform,invoice_id\">\n<input type=\"hidden\" name=\"platform\" value=\"21477273\">\n<input type=\"hidden\" name=\"invoice_id\" value=\"" . $params["invoiceid"] . "\">\n<input type=\"submit\" value=\"" . $params["langpaynow"] . "\" class=\"btn btn-default btn-sm\">\n</form>";
    return $code;
}

?>