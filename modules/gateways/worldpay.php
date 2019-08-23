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
function worldpay_config()
{
    global $CONFIG;
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "WorldPay"), "installationid" => array("FriendlyName" => "Installation ID", "Type" => "text", "Size" => "20", "Description" => "Enter your WorldPay Installation ID"), "prpassword" => array("FriendlyName" => "Payment Response Password", "Type" => "text", "Size" => "20", "Description" => "Enter your WorldPay Payment Response Password used in Callback Validations (Optional)"), "testmode" => array("FriendlyName" => "Test Mode", "Type" => "yesno"));
    return $configarray;
}
function worldpay_link($params)
{
    $testMode = $params["testmode"] == "on" ? "-test" : "";
    $formUrl = "https://secure" . $testMode . ".worldpay.com/wcc/purchase";
    $address = $params["clientdetails"]["address1"];
    if ($params["clientdetails"]["address2"]) {
        $address .= "\n" . $params["clientdetails"]["address2"];
    }
    $address .= "\n" . $params["clientdetails"]["city"];
    $address .= "\n" . $params["clientdetails"]["state"];
    $code = "<form action=\"" . $formUrl . "\" method=\"post\">\n<input type=\"hidden\" name=\"instId\" value=\"" . $params["installationid"] . "\">\n<input type=\"hidden\" name=\"cartId\" value=\"" . $params["invoiceid"] . "\">\n<input type=\"hidden\" name=\"desc\" value=\"" . $params["description"] . "\">\n<input type=\"hidden\" name=\"amount\" value=\"" . $params["amount"] . "\">\n<input type=\"hidden\" name=\"currency\" value=\"" . $params["currency"] . "\">\n<input type=\"hidden\" name=\"name\" value=\"" . $params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"] . "\">\n<input type=\"hidden\" name=\"email\" value=\"" . $params["clientdetails"]["email"] . "\">\n<input type=\"hidden\" name=\"address\" value=\"" . $address . "\">\n<input type=\"hidden\" name=\"postcode\" value=\"" . $params["clientdetails"]["postcode"] . "\">\n<input type=\"hidden\" name=\"country\" value=\"" . $params["clientdetails"]["country"] . "\">\n<input type=\"hidden\" name=\"tel\" value=\"" . $params["clientdetails"]["phonenumber"] . "\">";
    if ($params["testmode"] == "on") {
        $code .= "\n<input type=\"hidden\" name=\"testMode\" value=\"100\">";
    }
    if ($params["authmode"] == "on") {
        $code .= "\n<input type=\"hidden\" name=\"authMode\" value=\"E\">";
    }
    $code .= "\n<INPUT TYPE=\"hidden\" NAME=\"MC_callback\" VALUE=\"" . $params["systemurl"] . "/modules/gateways/callback/worldpay.php\">\n<input type=\"submit\" value=\"" . $params["langpaynow"] . "\">\n</form>";
    return $code;
}

?>