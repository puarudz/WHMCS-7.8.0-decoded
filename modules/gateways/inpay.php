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
function inpay_config()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "inpay"), "username" => array("FriendlyName" => "Merchant ID", "Type" => "text", "Size" => "20"), "secretkey" => array("FriendlyName" => "Secret ket", "Type" => "text", "Size" => "20"), "testmode" => array("FriendlyName" => "Test Mode", "Type" => "yesno", "Description" => "Use test gateway"));
    return $configarray;
}
function inpay_link($params)
{
    $gatewayusername = $params["username"];
    $gatewaytestmode = $params["testmode"];
    $invoiceid = $params["invoiceid"];
    $description = $params["description"];
    $amount = $params["amount"];
    $currency = $params["currency"];
    $firstname = $params["clientdetails"]["firstname"];
    $lastname = $params["clientdetails"]["lastname"];
    $email = $params["clientdetails"]["email"];
    $address1 = $params["clientdetails"]["address1"];
    $address2 = $params["clientdetails"]["address2"];
    $city = $params["clientdetails"]["city"];
    $state = $params["clientdetails"]["state"];
    $postcode = $params["clientdetails"]["postcode"];
    $country = $params["clientdetails"]["country"];
    $phone = $params["clientdetails"]["phonenumber"];
    $companyname = $params["companyname"];
    $systemurl = $params["systemurl"];
    $currency = $params["currency"];
    $params["checksum"] = calcInpayMd5Key($params);
    $url = "https://secure.inpay.com";
    if ($gatewaytestmode == "on") {
        $url = "https://test-secure.inpay.com";
    }
    $ret_url = $systemurl . "/clientarea.php";
    $pend_url = $systemurl . "/clientarea.php";
    $code = "<form method=\"post\" action=\"" . $url . "\">\n              <input type=\"hidden\" name=\"order_id\" value=\"" . $invoiceid . "\" />\n              <input type=\"hidden\" name=\"merchant_id\" value=\"" . $params["username"] . "\" />\n              <input type=\"hidden\" name=\"amount\" value=\"" . $amount . "\" />\n              <input type=\"hidden\" name=\"currency\" value=\"" . $currency . "\" />\n              <input type=\"hidden\" name=\"order_text\" value=\"" . $description . "\" />\n              <input type=\"hidden\" name=\"flow_layout\" value=\"multi_page\" />\n              <input type=\"hidden\" name=\"return_url\" value=\"" . $ret_url . "\" />\n              <input type=\"hidden\" name=\"pending_url\" value=\"" . $pend_url . "\" />\n              <input type=\"hidden\" name=\"checksum\" value=\"" . $params["checksum"] . "\" />\n              <input type=\"hidden\" name=\"buyer_email\" value=\"" . $email . "\" />\n              <input type=\"hidden\" name=\"buyer_name\" value=\"" . $firstname . " " . $lastname . "\" />\n              <input type=\"hidden\" name=\"buyer_address\" value=\"" . $address1 . "\" />\n              <input type=\"submit\" value=\"Pay with inpay\" />\n              </form>";
    return $code;
}
function calcInpayMd5Key($order)
{
    $sk = $order["secretkey"];
    $q = http_build_query(array("merchant_id" => $order["username"], "order_id" => $order["invoiceid"], "amount" => $order["amount"], "currency" => $order["currency"], "order_text" => $order["description"], "flow_layout" => "multi_page", "secret_key" => $sk), "", "&");
    $md5v = md5($q);
    return $md5v;
}

?>