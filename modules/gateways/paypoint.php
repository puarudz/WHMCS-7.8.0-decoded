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
function paypoint_config()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "PayPoint.net (SecPay)"), "merchantid" => array("FriendlyName" => "Merchant ID", "Type" => "text", "Size" => "20"), "remotepw" => array("FriendlyName" => "Remote Password", "Type" => "text", "Size" => "30"), "digestkey" => array("FriendlyName" => "Digest Key", "Type" => "text", "Size" => "40"), "testmode" => array("FriendlyName" => "Test Mode", "Type" => "yesno"));
    return $configarray;
}
function paypoint_link($params)
{
    $transid = $params["invoiceid"] . "-" . date("Ymdhis");
    $digest = md5($transid . $params["amount"] . $params["remotepw"]);
    $code = "<form method=\"post\" action=\"https://www.secpay.com/java-bin/ValCard\">\n<input type=\"hidden\" name=\"merchant\" value=\"" . $params["merchantid"] . "\" />\n<input type=\"hidden\" name=\"trans_id\" value=\"" . $transid . "\" />\n<input type=\"hidden\" name=\"amount\" value=\"" . $params["amount"] . "\" />\n<input type=\"hidden\" name=\"currency\" value=\"" . $params["currency"] . "\" />\n<input type=\"hidden\" name=\"repeat\" value=\"true\" />\n<input type=\"hidden\" name=\"callback\" value=\"" . $params["systemurl"] . "/modules/gateways/callback/paypoint.php\" />\n<input type=\"hidden\" name=\"options\" value=\"cb_post=true,md_flds=trans_id:amount:callback\">\n<input type=\"hidden\" name=\"digest\" value=\"" . $digest . "\" />";
    if ($params["testmode"]) {
        $code .= "<input type=\"hidden\" name=\"test_status\" value=\"true\">";
    }
    $code .= "<input type=\"submit\" value=\"" . $params["langpaynow"] . "\">\n</form>";
    return $code;
}

?>