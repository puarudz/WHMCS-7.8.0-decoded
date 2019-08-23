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
$GATEWAYMODULE["nochexname"] = "nochex";
$GATEWAYMODULE["nochexvisiblename"] = "NoChex";
$GATEWAYMODULE["nochextype"] = "Invoices";
function nochex_activate()
{
    defineGatewayField("nochex", "text", "email", "", "NoChex Merchant ID", "50", "This is the email you have registered with NoChex");
    defineGatewayField("nochex", "yesno", "hide", "", "Hide Details", "0", "Tick to stop customer details being repeated on Nochex payment page");
    defineGatewayField("nochex", "yesno", "testmode", "", "Test Mode", "0", "Tick to enable test transaction mode");
}
function nochex_link($params)
{
    $code = "<form action=\"https://secure.nochex.com/\" method=\"post\">\n<input type=hidden name=merchant_id value=\"" . $params["email"] . "\">\n<input type=hidden name=amount value=\"" . $params["amount"] . "\">\n<input type=hidden name=order_id value=\"" . $params["invoiceid"] . "\">\n<input type=hidden name=description value=\"" . $params["description"] . "\">\n<input type=hidden name=billing_fullname value=\"" . $params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"] . "\">\n<input type=hidden name=billing_address value=\"" . $params["clientdetails"]["address1"] . "\r\n" . $params["clientdetails"]["address2"] . "\r\n" . $params["clientdetails"]["city"] . "\r\n" . $params["clientdetails"]["state"] . "\r\n" . $params["clientdetails"]["country"] . "\">\n<input type=hidden name=billing_postcode value=\"" . $params["clientdetails"]["postcode"] . "\">\n<input type=hidden name=customer_phone_number value=\"" . $params["clientdetails"]["phonenumber"] . "\">\n<input type=hidden name=email_address value=\"" . $params["clientdetails"]["email"] . "\">\n<input type=hidden name=success_url value=\"" . $params["systemurl"] . "/viewinvoice.php?id=" . $params["invoiceid"] . "&paymentsuccess=true\">\n<input type=hidden name=cancel_url value=\"" . $params["systemurl"] . "/viewinvoice.php?id=" . $params["invoiceid"] . "&paymentfailed=true\">\n<input type=hidden name=decline_url value=\"" . $params["systemurl"] . "/viewinvoice.php?id=" . $params["invoiceid"] . "&paymentfailed=true\">\n<input type=hidden name=responderurl value=\"" . $params["systemurl"] . "/modules/gateways/callback/nochex.php\">\n<input type=hidden name=callback_url value=\"" . $params["systemurl"] . "/modules/gateways/callback/nochex.php\">\n";
    if ($params["hide"]) {
        $code .= "<input type=hidden name=hide_billing_details value=\"true\">";
    }
    if ($params["testmode"]) {
        $code .= "<input type=hidden name=test_transaction value=\"100\">\n<input type=hidden name=test_success_url value=\"" . $params["systemurl"] . "/viewinvoice.php?id=" . $params["invoiceid"] . "\">";
    }
    $code .= "\n<input type=\"submit\" value=\"" . $params["langpaynow"] . "\">\n</form>";
    return $code;
}

?>