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
$GATEWAYMODULE["cashuname"] = "cashu";
$GATEWAYMODULE["cashuvisiblename"] = "CashU";
$GATEWAYMODULE["cashutype"] = "Invoices";
$GATEWAYMODULE["cashunotes"] = "You must set the Return URL in your CashU Control Panel to: " . $CONFIG["SystemURL"] . "/modules/gateways/callback/cashu.php";
function cashu_activate()
{
    defineGatewayField("cashu", "text", "merchantid", "", "Merchant ID", "20", "");
    defineGatewayField("cashu", "text", "encryptionkeyword", "", "Encryption Keyword", "20", "");
    defineGatewayField("cashu", "yesno", "demomode", "", "Demo Mode", "", "");
}
function cashu_link($params)
{
    if ($params["cconvert"] == "on") {
        $params["amount"] = number_format($params["amount"] / $params["ccrate"], 2, ".", "");
        $params["currency"] = $params["cccurrency"];
    }
    $token = md5($params["merchantid"] . ":" . $params["amount"] . ":" . strtolower($params["currency"]) . ":" . $params["encryptionkeyword"]);
    $code = "<form action=\"https://www.cashu.com/cgi-bin/pcashu.cgi\" method=\"post\">\n<input type=\"hidden\" name=\"merchant_id\" value=\"" . $params["merchantid"] . "\">\n<input type=\"hidden\" name=\"token\" value=\"" . $token . "\">\n<input type=\"hidden\" name=\"display_text\" value=\"" . $params["description"] . "\">\n<input type=\"hidden\" name=\"currency\" value=\"" . $params["currency"] . "\">\n<input type=\"hidden\" name=\"amount\" value=\"" . $params["amount"] . "\">\n<input type=\"hidden\" name=\"language\" value=\"en\">\n<input type=\"hidden\" name=\"email\" value=\"" . $params["clientdetails"]["email"] . "\">\n<input type=\"hidden\" name=\"session_id\" value=\"" . $params["invoiceid"] . "\">\n<input type=\"hidden\" name=\"txt1\" value=\"" . $params["description"] . "\">";
    if ($params["demomode"] == "on") {
        $code .= "<input type=\"hidden\" name=\"test_mode\" value=\"1\">";
    }
    $code .= "\n<input type=\"submit\" value=\"" . $params["langpaynow"] . "\">\n</form>";
    return $code;
}

?>