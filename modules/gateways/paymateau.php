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
$GATEWAYMODULE["paymateauname"] = "paymateau";
$GATEWAYMODULE["paymateauvisiblename"] = "Paymate";
$GATEWAYMODULE["paymateautype"] = "Invoices";
function paymateau_activate()
{
    defineGatewayField("paymateau", "text", "mid", "", "Member ID", "20", "");
}
function paymateau_link($params)
{
    $code = "<form action=\"https://www.paymate.com/PayMate/ExpressPayment\" method=\"post\">\n<input type=\"hidden\" name=\"cmd\" value=\"_xclick\">\n<input type=\"hidden\" name=\"mid\" value=\"" . $params["mid"] . "\">\n<input type=\"hidden\" name=\"amt\" value=\"" . $params["amount"] . "\">\n<input type=\"hidden\" name=\"amt_editable\" value=\"N\">\n<input type=\"hidden\" name=\"currency\" value=\"" . $params["currency"] . "\">\n<input type=\"hidden\" name=\"ref\" value=\"" . $params["invoiceid"] . "\">\n<input type=\"hidden\" name=\"return\" value=\"" . $params["systemurl"] . "/modules/gateways/callback/paymate.php\">\n<input type=\"hidden\" name=\"back\" value=\"" . $params["systemurl"] . "/modules/gateways/callback/paymate.php\">\n<input type=\"hidden\" name=\"notify\" value=\"place holder for notify url\">\n<input type=\"hidden\" name=\"popup\" value=\"false\">\n<input type=\"hidden\" name=\"pmt_sender_email\" value=\"" . $params["clientdetails"]["email"] . "\">\n<input type=\"hidden\" name=\"pmt_contact_firstname\" value=\"" . $params["clientdetails"]["firstname"] . "\">\n<input type=\"hidden\" name=\"pmt_contact_surname\" value=\"" . $params["clientdetails"]["lastname"] . "\">\n<input type=\"hidden\" name=\"pmt_contact_phone\" value=\"" . $params["clientdetails"]["phonenumber"] . "\">\n<input type=\"hidden\" name=\"pmt_country\" value=\"" . $params["clientdetails"]["country"] . "\">\n<input type=\"hidden\" name=\"regindi_sub\" value=\"" . $params["clientdetails"]["city"] . "\">\n<input type=\"hidden\" name=\"regindi_address1\" value=\"" . $params["clientdetails"]["address1"] . "\">\n<input type=\"hidden\" name=\"regindi_address2\" value=\"" . $params["clientdetails"]["address2"] . "\">\n<input type=\"hidden\" name=\"regindi_state\" value=\"" . $params["clientdetails"]["state"] . "\">\n<input type=\"hidden\" name=\"regindi_pcode\" value=\"" . $params["clientdetails"]["postcode"] . "\">\n<input type=\"submit\" value=\"" . $params["langpaynow"] . "\">\n</form>";
    return $code;
}

?>