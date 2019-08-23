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
$result = select_query("tblpaymentgateways", "value", array("gateway" => "payflowpro", "setting" => "processorid"));
$data = mysql_fetch_array($result);
if ($data[0]) {
    function payflowpro_3dsecure($params)
    {
        if ($params["testmode"]) {
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
        $storage = WHMCS\Module\Storage\EncryptedTransientStorage::forModule("payflowpro");
        $storage->setValue("order_data", array("Centinel_OrderId" => $orderid, "Centinel_TransactionId" => $transid));
        if ($errorno == 0) {
            if ($enrolled == "Y") {
                logTransaction($params["paymentmethod"], $xmlarray, "3D Auth");
                $storage->setValue("Centinel_Details", array("cardtype" => $params["cardtype"], "cardnum" => $params["cardnum"], "cardexp" => $params["cardexp"], "cccvv" => $params["cccvv"], "cardstart" => $params["cardstart"], "cardissuenum" => $params["cardissuenum"]));
                $code = "<form method=\"POST\" action=\"" . $acsurl . "\">\n                <input type=hidden name=\"PaReq\" value=\"" . $pareq . "\">\n                <input type=hidden name=\"TermUrl\" value=\"" . $params["systemurl"] . "/modules/gateways/callback/payflowpro.php\">\n                <input type=hidden name=\"MD\" value=\"" . $params["invoiceid"] . "\">\n                <noscript>\n                <center>\n                    <font color=\"red\">\n                        <h2>Processing your Payer Authentication Transaction</h2>\n                        <h3>JavaScript is currently disabled or is not supported by your browser.<br></h3>\n                        <h4>Please click Submit to continue the processing of your transaction.</h4>\n                    </font>\n                <input type=\"submit\" value=\"Submit\">\n                </center>\n                </noscript>\n            </form>";
                return $code;
            }
            $result = payflowpro_capture($params);
            if ($result["status"] == "success") {
                logTransaction($params["paymentmethod"], $result["rawdata"], "Successful");
                addInvoicePayment($params["invoiceid"], $result["transid"], "", "", "payflowpro", "on");
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
function payflowpro_MetaData()
{
    return array("DisplayName" => "PayPal Payflow Pro", "APIVersion" => "1.1");
}
function payflowpro_config()
{
    return array("FriendlyName" => array("Type" => "System", "Value" => "Payflow Pro (PayPal)"), "partner" => array("FriendlyName" => "Partner", "Type" => "text", "Size" => "20"), "vendor" => array("FriendlyName" => "Merchant Login", "Type" => "text", "Size" => "40"), "username" => array("FriendlyName" => "Username", "Type" => "text", "Size" => "40"), "password" => array("FriendlyName" => "Password", "Type" => "text", "Size" => "40"), "processorid" => array("FriendlyName" => "Processor ID", "Type" => "text", "Size" => "20", "Description" => "Cardinal 3D Secure Details"), "merchantid" => array("FriendlyName" => "Merchant ID", "Type" => "text", "Size" => "20"), "transpw" => array("FriendlyName" => "Transaction PW", "Type" => "text", "Size" => "20"), "apiusername" => array("FriendlyName" => "API Username", "Type" => "text", "Size" => "40", "Description" => "API fields only required for refunds"), "apipassword" => array("FriendlyName" => "API Password", "Type" => "text", "Size" => "40"), "apisignature" => array("FriendlyName" => "API Signature", "Type" => "text", "Size" => "40"), "usereftrans" => array("FriendlyName" => "Use Reference Transactions", "Type" => "yesno", "Description" => "Tick to enable reference transactions and not store card details locally"), "payflowmode" => array("FriendlyName" => "PayFlow Mode", "Type" => "yesno", "Description" => "Tick this if you don't use a PayPal merchant account"), "testmode" => array("FriendlyName" => "Test Mode", "Type" => "yesno"));
}
function payflowpro_capture($params, $auth = "")
{
    $submiturl = $params["testmode"] ? "https://pilot-payflowpro.paypal.com/transaction" : "https://payflowpro.paypal.com/transaction";
    $invoicenumber = $params["invoiceid"];
    $amount = $params["amount"];
    $currency = $params["currency"];
    $first_name = $params["clientdetails"]["firstname"];
    $last_name = $params["clientdetails"]["lastname"];
    $cc_type = $params["cardtype"];
    $cc_number = $params["cardnum"];
    $expires = substr($params["cardexp"], 0, 2) . substr($params["cardexp"], 2, 2);
    $ccv2 = $params["cccvv"];
    $address = $params["clientdetails"]["address1"];
    $city = $params["clientdetails"]["city"];
    $state = $params["clientdetails"]["state"];
    $zipcode = $params["clientdetails"]["postcode"];
    $country = $params["clientdetails"]["country"];
    $email = $params["clientdetails"]["email"];
    if ($country == "AF") {
        $country = "IN";
    }
    if ($country == "AO") {
        $country = "ZA";
    }
    if ($country == "BY") {
        $country = "PL";
    }
    if ($country == "DZ") {
        $country = "ZA";
    }
    if ($country == "ET") {
        $country = "ZA";
    }
    if ($country == "GN") {
        $country = "ZA";
    }
    if ($country == "GT") {
        $country = "ZA";
    }
    if ($country == "HT") {
        $country = "ZA";
    }
    if ($country == "IR") {
        $country = "ZA";
    }
    if ($country == "KE") {
        $country = "ZA";
    }
    if ($country == "KH") {
        $country = "ZA";
    }
    if ($country == "KR") {
        $country = "ZA";
    }
    if ($country == "LB") {
        $country = "ZA";
    }
    if ($country == "LK") {
        $country = "ZA";
    }
    if ($country == "NG") {
        $country = "ZA";
    }
    if ($country == "NP") {
        $country = "ZA";
    }
    if ($country == "PK") {
        $country = "IN";
    }
    if ($country == "TD") {
        $country = "ZA";
    }
    $zipcode = preg_replace("/[^A-Z0-9]/", "", strtoupper($zipcode));
    $postfields = array();
    $postfields["PARTNER"] = $params["partner"];
    $postfields["VENDOR"] = $params["vendor"];
    $postfields["USER"] = $params["username"];
    $postfields["PWD"] = $params["password"];
    $postfields["TRXTYPE"] = "S";
    $postfields["TENDER"] = "C";
    $postfields["AMT"] = $amount;
    $postfields["CURRENCY"] = $currency;
    $postfields["CUSTREF"] = $invoicenumber;
    $postfields["INVNUM"] = $invoicenumber;
    if ($params["usereftrans"] && $params["gatewayid"] && !$params["cardnum"]) {
        $postfields["ORIGID"] = $params["gatewayid"];
    } else {
        $postfields["PAYMENTACTION"] = "SALE";
        $postfields["ACCT"] = $cc_number;
        $postfields["EXPDATE"] = $expires;
        $postfields["CARDSTART"] = substr($params["cardstart"], 0, 2) . substr($params["cardstart"], 2, 2);
        $postfields["CARDISSUE"] = $params["cardissuenum"];
        if ($ccv2) {
            $postfields["CVV2"] = $ccv2;
        }
        $postfields["FIRSTNAME"] = $first_name;
        $postfields["LASTNAME"] = $last_name;
        $postfields["STREET"] = $address;
        $postfields["CITY"] = $city;
        $postfields["STATE"] = $state;
        $postfields["ZIP"] = $zipcode;
        $postfields["COUNTRY"] = $country;
        $newreftrans = true;
    }
    $postfields["BUTTONSOURCE"] = "WHMCS_WPP_DP";
    if (is_array($auth)) {
        $postfields["AUTHSTATUS3DS"] = $auth["paresstatus"];
        $postfields["MPIVENDOR3DS"] = "Y";
        $postfields["CAVV"] = $auth["cavv"];
        $postfields["ECI"] = $auth["eciflag"];
        $postfields["XID"] = $auth["xid"];
    }
    $query_string = "";
    foreach ($postfields as $k => $v) {
        if ($k != "CAVV" && $k != "ECI" && $k != "XID") {
            $v = urlencode($v);
        }
        $query_string .= (string) $k . "=" . $v . "&";
    }
    $request_id = md5(date("dmyHis") . $invoicenumber);
    $headers = array();
    $headers[] = "Content-Type: text/namevalue";
    $headers[] = "X-VPS-Timeout: 30";
    $headers[] = "X-VPS-VIT-OS-Name: Linux";
    $headers[] = "X-VPS-VIT-OS-Version: RHEL 4";
    $headers[] = "X-VPS-VIT-Client-Type: PHP/cURL";
    $headers[] = "X-VPS-VIT-Client-Version: 0.01";
    $headers[] = "X-VPS-VIT-Client-Architecture: x86";
    $headers[] = "X-VPS-VIT-Integration-Product: WHMCS";
    $headers[] = "X-VPS-VIT-Integration-Version: 0.01";
    $headers[] = "X-VPS-Request-ID: " . $request_id;
    $user_agent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $submiturl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
    curl_setopt($ch, CURLOPT_POST, 1);
    $result = curl_exec($ch);
    $headers = curl_getinfo($ch);
    if (curl_errno($ch)) {
        $result = "curlerrornum=" . curl_errno($ch) . "&curlerrormessage=" . curl_error($ch);
    }
    curl_close($ch);
    $result = strstr($result, "RESULT");
    $valArray = explode("&", $result);
    foreach ($valArray as $val) {
        $valArray2 = explode("=", $val);
        $pfpro[$valArray2[0]] = $valArray2[1];
    }
    $transid = $pfpro["PPREF"] ? $pfpro["PPREF"] : $pfpro["PNREF"];
    if ($pfpro["RESULT"] == "0") {
        if ($params["usereftrans"]) {
            invoiceSetPayMethodRemoteToken($params["invoiceid"], $pfpro["PNREF"]);
        }
        return array("status" => "success", "transid" => $transid, "rawdata" => $pfpro);
    }
    return array("status" => "declined", "rawdata" => $pfpro, "declineReason" => $pfpro["RESPMSG"]);
}
function payflowpro_refund($params)
{
    if ($params["payflowmode"]) {
        $submiturl = $params["testmode"] ? "https://pilot-payflowpro.paypal.com/transaction" : "https://payflowpro.paypal.com/transaction";
        $postfields = array();
        $postfields["PARTNER"] = $params["partner"];
        $postfields["VENDOR"] = $params["vendor"];
        $postfields["USER"] = $params["username"];
        $postfields["PWD"] = $params["password"];
        $postfields["TRXTYPE"] = "C";
        $postfields["TENDER"] = "C";
        $postfields["ORIGID"] = $params["transid"];
        $postfields["AMT"] = $params["amount"];
        $query_string = "";
        foreach ($postfields as $k => $v) {
            $query_string .= (string) $k . "=" . urlencode($v) . "&";
        }
        $request_id = md5(date("dmyHis") . $params["invoiceid"]);
        $headers = array();
        $headers[] = "Content-Type: text/namevalue";
        $headers[] = "X-VPS-Timeout: 30";
        $headers[] = "X-VPS-VIT-OS-Name: Linux";
        $headers[] = "X-VPS-VIT-OS-Version: RHEL 4";
        $headers[] = "X-VPS-VIT-Client-Type: PHP/cURL";
        $headers[] = "X-VPS-VIT-Client-Version: 0.01";
        $headers[] = "X-VPS-VIT-Client-Architecture: x86";
        $headers[] = "X-VPS-VIT-Integration-Product: WHMCS";
        $headers[] = "X-VPS-VIT-Integration-Version: 0.01";
        $headers[] = "X-VPS-Request-ID: " . $request_id;
        $user_agent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $submiturl);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            $result = "curlerrornum=" . curl_errno($ch) . "&curlerrormessage=" . curl_error($ch);
        }
        curl_close($ch);
        $result = strstr($result, "RESULT");
        $valArray = explode("&", $result);
        foreach ($valArray as $val) {
            $valArray2 = explode("=", $val);
            $pfpro[$valArray2[0]] = $valArray2[1];
        }
        if ($pfpro["RESULT"] == "0") {
            return array("status" => "success", "rawdata" => $pfpro, "transid" => $pfpro["PNREF"]);
        }
        return array("status" => "error", "rawdata" => $pfpro);
    } else {
        $url = $params["sandbox"] ? "https://api-3t.sandbox.paypal.com/nvp" : "https://api-3t.paypal.com/nvp";
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
}

?>