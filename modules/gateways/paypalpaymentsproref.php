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
$result = select_query("tblpaymentgateways", "value", array("gateway" => "paypalpaymentsproref", "setting" => "processorid"));
$data = mysql_fetch_array($result);
if ($data[0]) {
    function paypalpaymentsproref_3dsecure($params)
    {
        $storage = WHMCS\Module\Storage\EncryptedTransientStorage::forModule("paypalpaymentsproref");
        if ($params["sandbox"]) {
            $mapurl = "https://centineltest.cardinalcommerce.com/maps/txns.asp";
        } else {
            $mapurl = "https://paypal.cardinalcommerce.com/maps/txns.asp";
        }
        $currency = "";
        if ($params["currency"] == "USD") {
            $currency = "840";
        }
        if ($params["currency"] == "GBP") {
            $currency = "826";
        }
        if ($params["currency"] == "EUR") {
            $currency = "978";
        }
        if ($params["currency"] == "CAD") {
            $currency = "124";
        }
        $postfields = array();
        $postfields["MsgType"] = "cmpi_lookup";
        $postfields["Version"] = "1.7";
        $postfields["ProcessorId"] = $params["processorid"];
        $postfields["MerchantId"] = $params["merchantid"];
        $postfields["TransactionPwd"] = $params["transpw"];
        $postfields["UserAgent"] = $_SERVER["HTTP_USER_AGENT"];
        $postfields["BrowserHeader"] = $_SERVER["HTTP_ACCEPT"];
        $postfields["TransactionType"] = "C";
        $postfields["Amount"] = $params["amount"] * 100;
        $postfields["ShippingAmount"] = "0";
        $postfields["TaxAmount"] = "0";
        $postfields["CurrencyCode"] = $currency;
        $postfields["OrderNumber"] = $params["invoiceid"];
        $postfields["OrderDescription"] = $params["description"];
        $postfields["EMail"] = $params["clientdetails"]["email"];
        $postfields["BillingFirstName"] = $params["clientdetails"]["firstname"];
        $postfields["BillingLastName"] = $params["clientdetails"]["lastname"];
        $postfields["BillingAddress1"] = $params["clientdetails"]["address1"];
        $postfields["BillingAddress2"] = $params["clientdetails"]["address2"];
        $postfields["BillingCity"] = $params["clientdetails"]["city"];
        $postfields["BillingState"] = $params["clientdetails"]["state"];
        $postfields["BillingPostalCode"] = $params["clientdetails"]["postcode"];
        $postfields["BillingCountryCode"] = $params["clientdetails"]["country"];
        $postfields["BillingPhone"] = $params["clientdetails"]["phonenumber"];
        $postfields["ShippingFirstName"] = $params["clientdetails"]["firstname"];
        $postfields["ShippingLastName"] = $params["clientdetails"]["lastname"];
        $postfields["ShippingAddress1"] = $params["clientdetails"]["address1"];
        $postfields["ShippingAddress2"] = $params["clientdetails"]["address2"];
        $postfields["ShippingCity"] = $params["clientdetails"]["city"];
        $postfields["ShippingState"] = $params["clientdetails"]["state"];
        $postfields["ShippingPostalCode"] = $params["clientdetails"]["postcode"];
        $postfields["ShippingCountryCode"] = $params["clientdetails"]["country"];
        $postfields["ShippingPhone"] = $params["clientdetails"]["phonenumber"];
        $postfields["CardNumber"] = $params["cardnum"];
        $postfields["CardExpMonth"] = substr($params["cardexp"], 0, 2);
        $postfields["CardExpYear"] = "20" . substr($params["cardexp"], 2, 2);
        $queryString = "<CardinalMPI>\n";
        foreach ($postfields as $name => $value) {
            $queryString .= "<" . $name . ">" . $value . "</" . $name . ">\n";
        }
        $queryString .= "</CardinalMPI>";
        $data = "cmpi_msg=" . urlencode($queryString);
        $response = curlCall($mapurl, $data);
        $xmlarray = XMLtoArray($response);
        $xmlarray = $xmlarray["CARDINALMPI"];
        $errorno = $xmlarray["ERRORNO"];
        $enrolled = $xmlarray["ENROLLED"];
        $eciflag = $xmlarray["ECIFLAG"];
        $transid = $xmlarray["TRANSACTIONID"];
        $acsurl = $xmlarray["ACSURL"];
        $pareq = $xmlarray["PAYLOAD"];
        $orderid = $xmlarray["ORDERID"];
        $storage->setValue("order_data", array("Centinel_OrderId" => $orderid, "Centinel_TransactionId" => $transid));
        if ($errorno == 0) {
            if ($enrolled == "Y") {
                logTransaction($params["paymentmethod"], $xmlarray, "3D Auth");
                $storage->setValue("Centinel_Details", array("cardtype" => $params["cardtype"], "cardnum" => $params["cardnum"], "cardexp" => $params["cardexp"], "cccvv" => $params["cccvv"], "cardstart" => $params["cardstart"], "cardissuenum" => $params["cardissuenum"]));
                $code = "<form method=\"POST\" action=\"" . $acsurl . "\">\n                <input type=hidden name=\"PaReq\" value=\"" . $pareq . "\">\n                <input type=hidden name=\"TermUrl\" value=\"" . $params["systemurl"] . "/modules/gateways/callback/paypalpaymentsproref.php\">\n                <input type=hidden name=\"MD\" value=\"" . $params["invoiceid"] . "\">\n                <noscript>\n                <center>\n                    <font color=\"red\">\n                        <h2>Processing your Payer Authentication Transaction</h2>\n                        <h3>JavaScript is currently disabled or is not supported by your browser.<br></h3>\n                        <h4>Please click Submit to continue the processing of your transaction.</h4>\n                    </font>\n                <input type=\"submit\" value=\"Submit\">\n                </center>\n                </noscript>\n            </form>";
                return $code;
            }
            $result = paypalpaymentsproref_capture($params);
            if ($result["status"] == "success") {
                logTransaction($params["paymentmethod"], $result["rawdata"], "Successful");
                addInvoicePayment($params["invoiceid"], $result["transid"], "", "", "paypalpaymentsproref", "on");
                sendMessage("Credit Card Payment Confirmation", $params["invoiceid"]);
                redir("id=" . $params["invoiceid"] . "&paymentsuccess=true", "viewinvoice.php");
            } else {
                logTransaction($params["paymentmethod"], $result["rawdata"], "Failed");
            }
        } else {
            logTransaction($params["paymentmethod"], $xmlarray, "No 3D Auth");
        }
        return "declined";
    }
}
function paypalpaymentsproref_MetaData()
{
    return array("DisplayName" => "PayPal Pro Reference Payments", "APIVersion" => "1.1");
}
function paypalpaymentsproref_config()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "PayPal Pro Reference Payments"), "apiusername" => array("FriendlyName" => "API Username", "Type" => "text", "Size" => "30"), "apipassword" => array("FriendlyName" => "API Password", "Type" => "text", "Size" => "30"), "apisignature" => array("FriendlyName" => "API Signature", "Type" => "text", "Size" => "30"), "processorid" => array("FriendlyName" => "Processor ID", "Type" => "text", "Size" => "20", "Description" => "Cardinal 3D Secure Details"), "merchantid" => array("FriendlyName" => "Merchant ID", "Type" => "text", "Size" => "20"), "transpw" => array("FriendlyName" => "Transaction PW", "Type" => "text", "Size" => "20"), "sandbox" => array("FriendlyName" => "Sandbox", "Type" => "yesno"));
    return $configarray;
}
function paypalpaymentsproref_capture($params)
{
    global $remote_ip;
    if ($params["sandbox"]) {
        $url = "https://api-3t.sandbox.paypal.com/nvp";
    } else {
        $url = "https://api-3t.paypal.com/nvp";
    }
    $cardtype = $params["cardtype"];
    if ($cardtype == "American Express") {
        $cardtype = "Amex";
    }
    $paymentvars = array();
    $paymentvars["VERSION"] = "3.0";
    $paymentvars["PAYMENTACTION"] = "Sale";
    $paymentvars["IPADDRESS"] = $remote_ip;
    $paymentvars["BUTTONSOURCE"] = "WHMCS_WPP_DP";
    $paymentvars["PWD"] = $params["apipassword"];
    $paymentvars["USER"] = $params["apiusername"];
    $paymentvars["SIGNATURE"] = $params["apisignature"];
    $paymentvars["AMT"] = $params["amount"];
    $paymentvars["FIRSTNAME"] = $params["clientdetails"]["firstname"];
    $paymentvars["LASTNAME"] = $params["clientdetails"]["lastname"];
    $paymentvars["STREET"] = $params["clientdetails"]["address1"];
    $paymentvars["CITY"] = $params["clientdetails"]["city"];
    $paymentvars["STATE"] = $params["clientdetails"]["state"];
    $paymentvars["ZIP"] = $params["clientdetails"]["postcode"];
    $paymentvars["COUNTRYCODE"] = $params["clientdetails"]["country"];
    $paymentvars["CURRENCYCODE"] = $params["currency"];
    $paymentvars["INVNUM"] = $params["invoiceid"];
    if (!$params["cardnum"] && $params["gatewayid"]) {
        $paymentvars["METHOD"] = "DoReferenceTransaction";
        $paymentvars["REFERENCEID"] = $params["gatewayid"];
    } else {
        $paymentvars["METHOD"] = "doDirectPayment";
        $paymentvars["CREDITCARDTYPE"] = $cardtype;
        $paymentvars["ACCT"] = $params["cardnum"];
        $paymentvars["EXPDATE"] = substr($params["cardexp"], 0, 2) . "20" . substr($params["cardexp"], 2, 2);
        $paymentvars["CVV2"] = $params["cccvv"];
        $paymentvars["ISSUENUMBER"] = $params["cardissuenum"];
        $paymentvars["STARTDATE"] = substr($params["cardstart"], 0, 2) . "20" . substr($params["cardstart"], 2, 2);
    }
    $fieldstring = "";
    foreach ($paymentvars as $key => $value) {
        $fieldstring .= (string) $key . "=" . urlencode($value) . "&";
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldstring);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        $resArray["Curl Error Number"] = curl_errno($ch);
        $resArray["Curl Error Description"] = curl_error($ch);
    } else {
        $resArray = paypalpaymentsproref_deformatNVP($response);
    }
    curl_close($ch);
    $debugreport = "";
    foreach ($resArray as $key => $value) {
        $debugreport .= (string) $key . " => " . $value . "\n";
    }
    $ack = strtoupper($resArray["ACK"]);
    if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING" || $resArray["PAYMENTSTATUS"] == "Completed") {
        invoiceSetPayMethodRemoteToken($params["invoiceid"], $resArray["TRANSACTIONID"]);
        return array("status" => "success", "transid" => $resArray["TRANSACTIONID"], "rawdata" => $resArray);
    }
    return array("status" => "error", "rawdata" => $resArray);
}
function paypalpaymentsproref_refund($params)
{
    if ($params["sandbox"]) {
        $url = "https://api-3t.sandbox.paypal.com/nvp";
    } else {
        $url = "https://api-3t.paypal.com/nvp";
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
        return array("status" => "success", "rawdata" => $resultsarray, "transid" => $resultsarray["REFUNDTRANSACTIONID"]);
    }
    return array("status" => "Error", "rawdata" => $resultsarray);
}
function paypalpaymentsproref_deformatNVP($nvpstr)
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
function paypalpaymentsproref_credit_card_input(array $params = array())
{
    return "";
}

?>