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
function paypal_config()
{
    return array("FriendlyName" => array("Type" => "System", "Value" => "PayPal"), "UsageNotes" => array("Type" => "System", "Value" => "Please ensure Instant Payment Notification (IPN) is enabled under Selling Preferences within your PayPal account. Paypal requires the Notification URL to be set to a valid URL, we recommend setting it to: " . App::getSystemUrl()), "email" => array("FriendlyName" => "PayPal Email", "Type" => "text", "Size" => "40"), "forceonetime" => array("FriendlyName" => "Force One Time Payments", "Type" => "yesno", "Description" => "Never show the subscription payment button"), "forcesubscriptions" => array("FriendlyName" => "Force Subscriptions", "Type" => "yesno", "Description" => "Hide the one time payment button when a subscription can be created"), "requireshipping" => array("FriendlyName" => "Require Shipping Address", "Type" => "yesno", "Description" => "Tick this box to request a shipping address from a user on PayPal's site"), "overrideaddress" => array("FriendlyName" => "Client Address Matching", "Type" => "yesno", "Description" => "Tick this box to force using client profile information entered into WHMCS at PayPal"), "apiusername" => array("FriendlyName" => "API Username", "Type" => "text", "Size" => "40", "Description" => "API Details are required for Refunds and Subscription cancellations"), "apipassword" => array("FriendlyName" => "API Password", "Type" => "text", "Size" => "40"), "apisignature" => array("FriendlyName" => "API Signature", "Type" => "text", "Size" => "70"), "sandbox" => array("FriendlyName" => "Sandbox Mode", "Type" => "yesno", "Description" => "Tick to use PayPal’s Virtual Sandbox Test Environment - requires a separate Sandbox Test Account"));
}
function paypal_link($params)
{
    if (isset($params["sandbox"]) && $params["sandbox"]) {
        $url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
    } else {
        $url = "https://www.paypal.com/cgi-bin/webscr";
    }
    $invoiceid = $params["invoiceid"];
    $paypalemails = $params["email"];
    $paypalemails = explode(",", $paypalemails);
    $paypalemail = trim($paypalemails[0]);
    $upgrade = false;
    $recurrings = getRecurringBillingValues($invoiceid);
    if (!$recurrings) {
        try {
            $recurrings = getUpgradeRecurringValues($invoiceid);
            if ($recurrings) {
                $upgrade = true;
            }
        } catch (Exception $e) {
        }
    }
    $primaryserviceid = $recurrings["primaryserviceid"];
    if ($upgrade) {
        $primaryserviceid = "U" . $primaryserviceid;
    }
    $firstpaymentamount = $recurrings["firstpaymentamount"];
    $firstcycleperiod = $recurrings["firstcycleperiod"];
    $firstcycleunits = strtoupper(substr($recurrings["firstcycleunits"], 0, 1));
    $recurringamount = $recurrings["recurringamount"];
    $recurringcycleperiod = $recurrings["recurringcycleperiod"];
    $recurringcycleunits = strtoupper(substr($recurrings["recurringcycleunits"], 0, 1));
    if (paypal_currencyHasNoDecimals($params["currency"])) {
        $firstpaymentamount = paypal_removeDecimal($firstpaymentamount);
        $recurringamount = paypal_removeDecimal($recurringamount);
        $params["amount"] = paypal_removeDecimal($params["amount"]);
    }
    if ($params["clientdetails"]["country"] == "US" || $params["clientdetails"]["country"] == "CA") {
        $phonenumber = preg_replace("/[^0-9]/", "", $params["clientdetails"]["phonenumber"]);
        $phone1 = substr($phonenumber, 0, 3);
        $phone2 = substr($phonenumber, 3, 3);
        $phone3 = substr($phonenumber, 6);
    } else {
        $phone1 = $params["clientdetails"]["phonecc"];
        $phone2 = $params["clientdetails"]["phonenumber"];
    }
    $subnotpossible = false;
    if (!$recurrings) {
        $subnotpossible = true;
    }
    if ($recurrings["overdue"]) {
        $subnotpossible = true;
    }
    if (isset($params["forceonetime"]) && $params["forceonetime"]) {
        $subnotpossible = true;
    }
    if ($recurringamount <= 0) {
        $subnotpossible = true;
    }
    if (90 < $firstcycleperiod && $firstcycleunits == "D") {
        $subnotpossible = true;
    }
    if (24 < $firstcycleperiod && $firstcycleunits == "M") {
        $subnotpossible = true;
    }
    if (5 < $firstcycleperiod && $firstcycleunits == "Y") {
        $subnotpossible = true;
    }
    $code = "<table style=\"border-collapse: separate; border-spacing: 15px 0;\"><tr>";
    if (!$subnotpossible) {
        $code .= "<td><form action=\"" . $url . "\" method=\"post\" name=\"paymentfrm\">\n<input type=\"hidden\" name=\"cmd\" value=\"_xclick-subscriptions\">\n<input type=\"hidden\" name=\"business\" value=\"" . $paypalemail . "\">\n<input type=\"hidden\" name=\"item_name\" value=\"" . $params["description"] . "\">\n<input type=\"hidden\" name=\"no_shipping\" value=\"" . ($params["requireshipping"] ? "2" : "1") . "\">\n<input type=\"hidden\" name=\"address_override\" value=\"" . ($params["overrideaddress"] ? "1" : "0") . "\">\n<input type=\"hidden\" name=\"first_name\" value=\"" . $params["clientdetails"]["firstname"] . "\">\n<input type=\"hidden\" name=\"last_name\" value=\"" . $params["clientdetails"]["lastname"] . "\">\n<input type=\"hidden\" name=\"email\" value=\"" . $params["clientdetails"]["email"] . "\">\n<input type=\"hidden\" name=\"address1\" value=\"" . $params["clientdetails"]["address1"] . "\">\n<input type=\"hidden\" name=\"city\" value=\"" . $params["clientdetails"]["city"] . "\">\n<input type=\"hidden\" name=\"state\" value=\"" . $params["clientdetails"]["state"] . "\">\n<input type=\"hidden\" name=\"zip\" value=\"" . $params["clientdetails"]["postcode"] . "\">\n<input type=\"hidden\" name=\"country\" value=\"" . $params["clientdetails"]["country"] . "\">\n<input type=\"hidden\" name=\"night_phone_a\" value=\"" . $phone1 . "\">\n<input type=\"hidden\" name=\"night_phone_b\" value=\"" . $phone2 . "\">";
        if (isset($phone3)) {
            $code .= "<input type=\"hidden\" name=\"night_phone_c\" value=\"" . $phone3 . "\">";
        }
        $code .= "<input type=\"hidden\" name=\"no_note\" value=\"1\">\n<input type=\"hidden\" name=\"currency_code\" value=\"" . $params["currency"] . "\">\n<input type=\"hidden\" name=\"bn\" value=\"WHMCS_ST\">";
        if ($firstpaymentamount) {
            $code .= "\n<input type=\"hidden\" name=\"a1\" value=\"" . $firstpaymentamount . "\">\n<input type=\"hidden\" name=\"p1\" value=\"" . $firstcycleperiod . "\">\n<input type=\"hidden\" name=\"t1\" value=\"" . $firstcycleunits . "\">";
        }
        $code .= "\n<input type=\"hidden\" name=\"a3\" value=\"" . $recurringamount . "\">\n<input type=\"hidden\" name=\"p3\" value=\"" . $recurringcycleperiod . "\">\n<input type=\"hidden\" name=\"t3\" value=\"" . $recurringcycleunits . "\">\n<input type=\"hidden\" name=\"src\" value=\"1\">\n<input type=\"hidden\" name=\"sra\" value=\"1\">\n<input type=\"hidden\" name=\"charset\" value=\"" . WHMCS\Config\Setting::getValue("Charset") . "\">\n<input type=\"hidden\" name=\"custom\" value=\"" . $primaryserviceid . "\">\n<input type=\"hidden\" name=\"return\" value=\"" . $params["returnurl"] . "&paymentsuccess=true\">\n<input type=\"hidden\" name=\"cancel_return\" value=\"" . $params["returnurl"] . "&paymentfailed=true\">\n<input type=\"hidden\" name=\"notify_url\" value=\"" . $params["systemurl"] . "modules/gateways/callback/paypal.php\">\n<input type=\"hidden\" name=\"rm\" value=\"2\">\n<input type=\"image\" src=\"https://www.paypal.com/en_US/i/btn/x-click-but20.gif\" border=\"0\" name=\"submit\" alt=\"Subscribe with PayPal for Automatic Payments\">\n</form></td>";
    }
    if (!$subnotpossible && $params["forcesubscriptions"] && !$params["forceonetime"]) {
    } else {
        $code .= "<td><form action=\"" . $url . "\" method=\"post\">\n<input type=\"hidden\" name=\"cmd\" value=\"_xclick\">\n<input type=\"hidden\" name=\"business\" value=\"" . $paypalemail . "\">";
        if (isset($params["style"]) && $params["style"]) {
            $code .= "<input type=\"hidden\" name=\"page_style\" value=\"" . $params["style"] . "\">";
        }
        $code .= "<input type=\"hidden\" name=\"item_name\" value=\"" . $params["description"] . "\">\n<input type=\"hidden\" name=\"amount\" value=\"" . $params["amount"] . "\">\n<input type=\"hidden\" name=\"tax\" value=\"0.00\">\n<input type=\"hidden\" name=\"no_note\" value=\"1\">\n<input type=\"hidden\" name=\"no_shipping\" value=\"" . (isset($params["requireshipping"]) && $params["requireshipping"] ? "2" : "1") . "\">\n<input type=\"hidden\" name=\"address_override\" value=\"" . (isset($params["overrideaddress"]) && $params["overrideaddress"] ? "1" : "0") . "\">\n<input type=\"hidden\" name=\"first_name\" value=\"" . $params["clientdetails"]["firstname"] . "\">\n<input type=\"hidden\" name=\"last_name\" value=\"" . $params["clientdetails"]["lastname"] . "\">\n<input type=\"hidden\" name=\"email\" value=\"" . $params["clientdetails"]["email"] . "\">\n<input type=\"hidden\" name=\"address1\" value=\"" . $params["clientdetails"]["address1"] . "\">\n<input type=\"hidden\" name=\"city\" value=\"" . $params["clientdetails"]["city"] . "\">\n<input type=\"hidden\" name=\"state\" value=\"" . $params["clientdetails"]["state"] . "\">\n<input type=\"hidden\" name=\"zip\" value=\"" . $params["clientdetails"]["postcode"] . "\">\n<input type=\"hidden\" name=\"country\" value=\"" . $params["clientdetails"]["country"] . "\">\n<input type=\"hidden\" name=\"night_phone_a\" value=\"" . $phone1 . "\">\n<input type=\"hidden\" name=\"night_phone_b\" value=\"" . $phone2 . "\">";
        if (isset($phone3)) {
            $code .= "<input type=\"hidden\" name=\"night_phone_c\" value=\"" . $phone3 . "\">";
        }
        $code .= "<input type=\"hidden\" name=\"charset\" value=\"" . WHMCS\Config\Setting::getValue("Charset") . "\">\n<input type=\"hidden\" name=\"currency_code\" value=\"" . $params["currency"] . "\">\n<input type=\"hidden\" name=\"custom\" value=\"" . $params["invoiceid"] . "\">\n<input type=\"hidden\" name=\"return\" value=\"" . $params["returnurl"] . "&paymentsuccess=true\">\n<input type=\"hidden\" name=\"cancel_return\" value=\"" . $params["returnurl"] . "&paymentfailed=true\">\n<input type=\"hidden\" name=\"notify_url\" value=\"" . $params["systemurl"] . "modules/gateways/callback/paypal.php\">\n<input type=\"hidden\" name=\"bn\" value=\"WHMCS_ST\">\n<input type=\"hidden\" name=\"rm\" value=\"2\">\n<input type=\"image\" src=\"https://www.paypal.com/en_US/i/btn/x-click-but03.gif\" border=\"0\" name=\"submit\" alt=\"Make a one time payment with PayPal\">\n</form></td>";
    }
    $code .= "</tr></table>";
    return $code;
}
function paypal_refund($params)
{
    if ($params["sandbox"]) {
        $url = "https://api-3t.sandbox.paypal.com/nvp";
    } else {
        $url = "https://api-3t.paypal.com/nvp";
    }
    if (paypal_currencyHasNoDecimals($params["currency"])) {
        $params["amount"] = paypal_removeDecimal($params["amount"]);
    }
    $postfields = array();
    $postfields["VERSION"] = "3.0";
    $postfields["METHOD"] = "RefundTransaction";
    $postfields["BUTTONSOURCE"] = "WHMCS_WPP_DP";
    $postfields["USER"] = $params["apiusername"];
    $postfields["PWD"] = $params["apipassword"];
    $postfields["SIGNATURE"] = $params["apisignature"];
    $postfields["TRANSACTIONID"] = $params["transid"];
    $postfields["REFUNDTYPE"] = "Partial";
    $postfields["AMT"] = $params["amount"];
    $postfields["CURRENCYCODE"] = $params["currency"];
    $result = curlCall($url, $postfields);
    $resultsarray2 = explode("&", $result);
    foreach ($resultsarray2 as $line) {
        $line = explode("=", $line);
        $resultsarray[$line[0]] = urldecode($line[1]);
    }
    if (strtoupper($resultsarray["ACK"]) == "SUCCESS") {
        return array("status" => "success", "rawdata" => $resultsarray, "transid" => $resultsarray["REFUNDTRANSACTIONID"], "fees" => $resultsarray["FEEREFUNDAMT"]);
    }
    return array("status" => "error", "rawdata" => $resultsarray);
}
function paypal_removeDecimal($amount)
{
    if (is_numeric($amount)) {
        $amount = round($amount);
    }
    return $amount;
}
function paypal_cancelSubscription(array $params)
{
    if (!$params["apiusername"] || !$params["apipassword"] || !$params["apisignature"]) {
        return array("status" => "error", "rawdata" => "PayPal API Details not configured to allow Subscription cancellation");
    }
    $resultsArray = array();
    if ($params["sandbox"]) {
        $url = "https://api-3t.sandbox.paypal.com/nvp";
    } else {
        $url = "https://api-3t.paypal.com/nvp";
    }
    $postFields = array();
    $postFields["BUTTONSOURCE"] = "WHMCS_WPP_DP";
    $postFields["USER"] = $params["apiusername"];
    $postFields["PWD"] = $params["apipassword"];
    $postFields["SIGNATURE"] = $params["apisignature"];
    $postFields["VERSION"] = "3.0";
    $postFields["METHOD"] = "ManageRecurringPaymentsProfileStatus";
    $postFields["PROFILEID"] = $params["subscriptionID"];
    $postFields["ACTION"] = "Cancel";
    $postFields["NOTE"] = "Automatic Subscription Cancellation";
    $result = curlCall($url, $postFields);
    parse_str($result, $resultsArray);
    $resultsArray["PROFILEID"] = $params["subscriptionID"];
    if (strtoupper($resultsArray["ACK"]) == "SUCCESS" && $resultsArray["PROFILEID"]) {
        return array("status" => "success", "rawdata" => $resultsArray);
    }
    return array("status" => "error", "rawdata" => $resultsArray);
}
function paypal_currencyHasNoDecimals($currencyCode)
{
    $currenciesWithoutDecimals = array("BYR", "BIF", "CLP", "KMF", "DJF", "HUF", "ISK", "JPY", "MGA", "MZN", "PYG", "RWF", "KRW", "VUV");
    if (in_array($currencyCode, $currenciesWithoutDecimals)) {
        return true;
    }
    return false;
}

?>