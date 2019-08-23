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
function paypalexpress_MetaData()
{
    return array("DisplayName" => "PayPal Express Checkout", "APIVersion" => "1.1");
}
function paypalexpress_config()
{
    global $CONFIG;
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "PayPal Express Checkout"), "apiusername" => array("FriendlyName" => "API Username", "Type" => "text", "Size" => "50", "Description" => ""), "apipassword" => array("FriendlyName" => "API Password", "Type" => "text", "Size" => "30"), "apisignature" => array("FriendlyName" => "API Signature", "Type" => "text", "Size" => "75"), "sandbox" => array("FriendlyName" => "Sandbox", "Type" => "yesno", "Description" => "Tick to enable test mode"));
    return $configarray;
}
function paypalexpress_link($params)
{
    $paypalvars = getGatewayVariables("paypal");
    $params = array_merge($params, $paypalvars);
    $params["returnurl"] = $params["systemurl"] . "/viewinvoice.php?id=" . $params["invoiceid"];
    return paypal_link($params);
}
function paypalexpress_orderformoutput($params)
{
    $storage = WHMCS\Module\Storage\EncryptedTransientStorage::forModule("paypalexpress");
    if (empty($params["isCheckout"])) {
        $storage->deleteAll();
    }
    if ($_POST["paypalcheckout"]) {
        $postfields = array();
        $postfields["PAYMENTREQUEST_0_PAYMENTACTION"] = "Sale";
        $postfields["PAYMENTREQUEST_0_AMT"] = $params["amount"];
        $postfields["PAYMENTREQUEST_0_CURRENCYCODE"] = $params["currency"];
        $postfields["RETURNURL"] = $params["systemurl"] . "/modules/gateways/callback/paypalexpress.php";
        $postfields["CANCELURL"] = $params["systemurl"] . "/cart.php?a=view";
        $results = paypalexpress_api_call($params, "SetExpressCheckout", $postfields);
        $ack = strtoupper($results["ACK"]);
        if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING") {
            logTransaction($params["paymentmethod"], $results, "Token Gen Successful");
            $token = $results["TOKEN"];
            $storage->setValue("token", $token);
            $PAYPAL_URL = $params["sandbox"] ? "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=" : "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
            header("Location: " . $PAYPAL_URL . $token);
            exit;
        }
        logTransaction($params["paymentmethod"], $results, "Token Gen Error");
        return "<p>PayPal Checkout Error. Please Contact Support.</p>";
    }
    $code = "<form action=\"cart.php?a=view\" method=\"post\">\n<input type=\"hidden\" name=\"paypalcheckout\" value=\"1\" />\n<input type=\"image\" name=\"submit\" src=\"https://www.paypal.com/en_US/i/btn/btn_xpressCheckout.gif\" border=\"0\" align=\"top\" alt=\"Check out with PayPal\" />\n</form>";
    return $code;
}
function paypalexpress_orderformcheckout($params)
{
    $storage = WHMCS\Module\Storage\EncryptedTransientStorage::forModule("paypalexpress");
    $orderid = get_query_val("tblorders", "id", array("invoiceid" => $params["invoiceid"]));
    update_query("tblhosting", array("paymentmethod" => "paypal"), array("orderid" => $orderid, "paymentmethod" => "paypalexpress"));
    update_query("tblhostingaddons", array("paymentmethod" => "paypal"), array("orderid" => $orderid, "paymentmethod" => "paypalexpress"));
    update_query("tbldomains", array("paymentmethod" => "paypal"), array("orderid" => $orderid, "paymentmethod" => "paypalexpress"));
    $postfields = array();
    $postfields["TOKEN"] = $storage->getValue("token");
    $postfields["PAYERID"] = $storage->getValue("payerid");
    $postfields["PAYMENTREQUEST_0_PAYMENTACTION"] = "SALE";
    $postfields["PAYMENTREQUEST_0_AMT"] = $params["amount"];
    $postfields["PAYMENTREQUEST_0_CURRENCYCODE"] = $params["currency"];
    $postfields["IPADDRESS"] = $_SERVER["SERVER_NAME"];
    $results = paypalexpress_api_call($params, "DoExpressCheckoutPayment", $postfields);
    $ack = strtoupper($results["ACK"]);
    if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING") {
        $transactionId = $results["PAYMENTINFO_0_TRANSACTIONID"];
        $transactionType = $results["PAYMENTINFO_0_TRANSACTIONTYPE"];
        $paymentType = $results["PAYMENTINFO_0_PAYMENTTYPE"];
        $orderTime = $results["PAYMENTINFO_0_ORDERTIME"];
        $amt = $results["PAYMENTINFO_0_AMT"];
        $currencyCode = $results["PAYMENTINFO_0_CURRENCYCODE"];
        $feeAmt = $results["PAYMENTINFO_0_FEEAMT"];
        $settleAmt = $results["PAYMENTINFO_0_SETTLEAMT"];
        $taxAmt = $results["PAYMENTINFO_0_TAXAMT"];
        $exchangeRate = $results["PAYMENTINFO_0_EXCHANGERATE"];
        $paymentStatus = $results["PAYMENTINFO_0_PAYMENTSTATUS"];
        $paypalCurrencyId = Illuminate\Database\Capsule\Manager::table("tblcurrencies")->where("code", "=", $currencyCode)->value("id");
        if (!$paypalCurrencyId) {
            return array("status" => "Unrecognised Currency", "rawdata" => $results);
        }
        $storage->deleteAll();
        if ($paymentStatus == "Completed") {
            $currency = getCurrency($params["clientdetails"]["id"]);
            if ($paypalCurrencyId != $currency["id"]) {
                $amt = convertCurrency($amt, $paypalCurrencyId, $currency["id"]);
                $feeAmt = convertCurrency($feeAmt, $paypalCurrencyId, $currency["id"]);
                $invoiceTotal = Illuminate\Database\Capsule\Manager::table("tblinvoices")->where("id", "=", $params["invoiceid"])->value("total");
                if ($invoiceTotal < $amt + 1 && $amt - 1 < $invoiceTotal) {
                    $amt = $invoiceTotal;
                }
            }
            return array("status" => "success", "transid" => $transactionId, "amount" => $amt, "fee" => $feeAmt, "rawdata" => $results);
        }
        if ($paymentStatus == "Pending") {
            return array("status" => "payment pending", "rawdata" => $results);
        }
        return array("status" => "invalid status", "rawdata" => $results);
    }
    return array("status" => "error", "rawdata" => $results);
}
function paypalexpress_api_call($params, $methodName, $postfields)
{
    $sBNCode = "WHMCS_ECWizard";
    $version = "64";
    $API_UserName = $params["apiusername"];
    $API_Password = $params["apipassword"];
    $API_Signature = $params["apisignature"];
    $API_Endpoint = $params["sandbox"] ? "https://api-3t.sandbox.paypal.com/nvp" : "https://api-3t.paypal.com/nvp";
    $postfields["METHOD"] = $methodName;
    $postfields["VERSION"] = $version;
    $postfields["PWD"] = $API_Password;
    $postfields["USER"] = $API_UserName;
    $postfields["SIGNATURE"] = $API_Signature;
    $postfields["BUTTONSOURCE"] = $sBNCode;
    $nvpreq = "";
    foreach ($postfields as $k => $v) {
        $nvpreq .= (string) $k . "=" . urlencode($v) . "&";
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
    }
    curl_close($ch);
    return paypalexpress_deformatNVP($response);
}
function paypalexpress_deformatNVP($nvpstr)
{
    $intial = 0;
    $nvpArray = array();
    while (strlen($nvpstr)) {
        $keypos = strpos($nvpstr, "=");
        $valuepos = strpos($nvpstr, "&") ? strpos($nvpstr, "&") : strlen($nvpstr);
        $keyval = substr($nvpstr, $intial, $keypos);
        $valval = substr($nvpstr, $keypos + 1, $valuepos - $keypos - 1);
        $nvpArray[urldecode($keyval)] = urldecode($valval);
        $nvpstr = substr($nvpstr, $valuepos + 1, strlen($nvpstr));
    }
    return $nvpArray;
}

?>