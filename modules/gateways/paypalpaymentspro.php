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
$result = select_query("tblpaymentgateways", "value", array("gateway" => "paypalpaymentspro", "setting" => "processorid"));
$data = mysql_fetch_array($result);
if ($data[0]) {
    function paypalpaymentspro_3dsecure($params)
    {
        $storage = WHMCS\Module\Storage\EncryptedTransientStorage::forModule("paypalpaymentspro");
        if ($params["sandbox"]) {
            $mapurl = "https://centineltest.cardinalcommerce.com/maps/txns.asp";
        } else {
            $mapurl = "https://paypal.cardinalcommerce.com/maps/txns.asp";
        }
        $currencyCodes = array("AFA" => "971", "AWG" => "533", "AUD" => "036", "ARS" => "032", "AZN" => "944", "BSD" => "044", "BDT" => "050", "BBD" => "052", "BYR" => "974", "BOB" => "068", "BRL" => "986", "GBP" => "826", "BGN" => "975", "KHR" => "116", "CAD" => "124", "KYD" => "136", "CLP" => "152", "CNY" => "156", "COP" => "170", "CRC" => "188", "HRK" => "191", "CPY" => "196", "CZK" => "203", "DKK" => "208", "DOP" => "214", "XCD" => "951", "EGP" => "818", "ERN" => "232", "EEK" => "233", "EUR" => "978", "GEL" => "981", "GHC" => "288", "GIP" => "292", "GTQ" => "320", "HNL" => "340", "HKD" => "344", "HUF" => "348", "ISK" => "352", "INR" => "356", "IDR" => "360", "ILS" => "376", "JMD" => "388", "JPY" => "392", "KZT" => "368", "KES" => "404", "KWD" => "414", "LVL" => "428", "LBP" => "422", "LTL" => "440", "MOP" => "446", "MKD" => "807", "MGA" => "969", "MYR" => "458", "MTL" => "470", "BAM" => "977", "MUR" => "480", "MXN" => "484", "MZM" => "508", "NPR" => "524", "ANG" => "532", "TWD" => "901", "NZD" => "554", "NIO" => "558", "NGN" => "566", "KPW" => "408", "NOK" => "578", "OMR" => "512", "PKR" => "586", "PYG" => "600", "PEN" => "604", "PHP" => "608", "QAR" => "634", "RON" => "946", "RUB" => "643", "SAR" => "682", "CSD" => "891", "SCR" => "690", "SGD" => "702", "SKK" => "703", "SIT" => "705", "ZAR" => "710", "KRW" => "410", "LKR" => "144", "SRD" => "968", "SEK" => "752", "CHF" => "756", "TZS" => "834", "THB" => "764", "TTD" => "780", "TRY" => "949", "AED" => "784", "USD" => "840", "UGX" => "800", "UAH" => "980", "UYU" => "858", "UZS" => "860", "VEB" => "862", "VND" => "704", "AMK" => "894", "ZWD" => "716");
        $currency = $currencyCodes[$params["currency"]];
        if (paypalpaymentspro_currencyHasNoDecimals($params["currency"])) {
            $params["amount"] = paypalpaymentspro_removeDecimal($params["amount"]);
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
                $code = "<form method=\"POST\" action=\"" . $acsurl . "\">\n                <input type=hidden name=\"PaReq\" value=\"" . $pareq . "\">\n                <input type=hidden name=\"TermUrl\" value=\"" . $params["systemurl"] . "/modules/gateways/callback/paypalpaymentspro.php\">\n                <input type=hidden name=\"MD\" value=\"" . $params["invoiceid"] . "\">\n                <noscript>\n                <center>\n                    <font color=\"red\">\n                        <h2>Processing your Payer Authentication Transaction</h2>\n                        <h3>JavaScript is currently disabled or is not supported by your browser.<br></h3>\n                        <h4>Please click Submit to continue the processing of your transaction.</h4>\n                    </font>\n                <input type=\"submit\" value=\"Submit\">\n                </center>\n                </noscript>\n            </form>";
                return $code;
            }
            $result = paypalpaymentspro_capture($params);
            if ($result["status"] == "success") {
                logTransaction($params["paymentmethod"], $result["rawdata"], "Successful");
                addInvoicePayment($params["invoiceid"], $result["transid"], "", "", "paypalpaymentspro", "on");
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
function paypalpaymentspro_MetaData()
{
    return array("DisplayName" => "PayPal Website Payments Pro", "APIVersion" => "1.1");
}
function paypalpaymentspro_config()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "PayPal Website Payments Pro"), "apiusername" => array("FriendlyName" => "API Username", "Type" => "text", "Size" => "20"), "apipassword" => array("FriendlyName" => "API Password", "Type" => "text", "Size" => "20"), "apisignature" => array("FriendlyName" => "API Signature", "Type" => "text", "Size" => "20"), "processorid" => array("FriendlyName" => "Processor ID", "Type" => "text", "Size" => "20", "Description" => "Cardinal 3D Secure Details"), "merchantid" => array("FriendlyName" => "Merchant ID", "Type" => "text", "Size" => "20"), "transpw" => array("FriendlyName" => "Transaction PW", "Type" => "text", "Size" => "20"), "sandbox" => array("FriendlyName" => "Test Mode", "Type" => "yesno"));
    return $configarray;
}
function paypalpaymentspro_capture($params, $auth = "")
{
    if ($params["sandbox"]) {
        $url = "https://api-3t.sandbox.paypal.com/nvp";
    } else {
        $url = "https://api-3t.paypal.com/nvp";
    }
    $cardtype = $params["cardtype"];
    if ($cardtype == "American Express") {
        $cardtype = "Amex";
    }
    if ($cardtype == "Maestro" || $cardtype == "Solo") {
        $cardtype = "Mastercard";
    }
    if (paypalpaymentspro_currencyHasNoDecimals($params["currency"])) {
        $params["amount"] = paypalpaymentspro_removeDecimal($params["amount"]);
    }
    $paymentvars = array();
    $paymentvars["METHOD"] = "doDirectPayment";
    $paymentvars["BUTTONSOURCE"] = "WHMCS_WPP_DP";
    $paymentvars["VERSION"] = "3.0";
    $paymentvars["PWD"] = $params["apipassword"];
    $paymentvars["USER"] = $params["apiusername"];
    $paymentvars["SIGNATURE"] = $params["apisignature"];
    $paymentvars["PAYMENTACTION"] = "Sale";
    $paymentvars["AMT"] = $params["amount"];
    $paymentvars["CREDITCARDTYPE"] = $cardtype;
    $paymentvars["ACCT"] = $params["cardnum"];
    $paymentvars["EXPDATE"] = substr($params["cardexp"], 0, 2) . "20" . substr($params["cardexp"], 2, 2);
    $paymentvars["CVV2"] = $params["cccvv"];
    if ($params["cardissuenum"]) {
        $paymentvars["ISSUENUMBER"] = $params["cardissuenum"];
    }
    if ($params["cardstart"]) {
        $paymentvars["STARTDATE"] = substr($params["cardstart"], 0, 2) . "20" . substr($params["cardstart"], 2, 2);
    }
    $paymentvars["FIRSTNAME"] = $params["clientdetails"]["firstname"];
    $paymentvars["LASTNAME"] = $params["clientdetails"]["lastname"];
    $paymentvars["STREET"] = $params["clientdetails"]["address1"];
    $paymentvars["CITY"] = $params["clientdetails"]["city"];
    $paymentvars["STATE"] = $params["clientdetails"]["state"];
    $paymentvars["ZIP"] = $params["clientdetails"]["postcode"];
    $paymentvars["COUNTRYCODE"] = $params["clientdetails"]["country"];
    $paymentvars["CURRENCYCODE"] = $params["currency"];
    $paymentvars["INVNUM"] = $params["invoiceid"];
    if (is_array($auth)) {
        $paymentvars["VERSION"] = "59.0";
        $paymentvars["AUTHSTATUS3DS"] = $auth["paresstatus"];
        $paymentvars["MPIVENDOR3DS"] = "Y";
        $paymentvars["CAVV"] = $auth["cavv"];
        $paymentvars["ECI3DS"] = $auth["eciflag"];
        $paymentvars["XID"] = $auth["xid"];
    }
    $response = curlCall($url, $paymentvars);
    $resArray = paypalpaymentspro_deformatNVP($response);
    $ack = strtoupper($resArray["ACK"]);
    if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING") {
        return array("status" => "success", "transid" => $resArray["TRANSACTIONID"], "rawdata" => $resArray);
    }
    return array("status" => "declined", "rawdata" => $resArray, "declineReason" => $resArray["RESPMSG"]);
}
function paypalpaymentspro_refund($params)
{
    if ($params["sandbox"]) {
        $url = "https://api-3t.sandbox.paypal.com/nvp";
    } else {
        $url = "https://api-3t.paypal.com/nvp";
    }
    if (paypalpaymentspro_currencyHasNoDecimals($params["currency"])) {
        $params["amount"] = paypalpaymentspro_removeDecimal($params["amount"]);
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
    return array("status" => "Error", "rawdata" => $resultsarray);
}
function paypalpaymentspro_deformatNVP($nvpstr)
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
function paypalpaymentspro_currencyHasNoDecimals($currencyCode)
{
    $currenciesWithoutDecimals = array("BYR", "BIF", "CLP", "KMF", "DJF", "HUF", "ISK", "JPY", "MGA", "MZN", "PYG", "RWF", "KRW", "VUV");
    return in_array($currencyCode, $currenciesWithoutDecimals);
}
function paypalpaymentspro_removeDecimal($amount)
{
    if (is_numeric($amount)) {
        $amount = round($amount);
    }
    return $amount;
}

?>