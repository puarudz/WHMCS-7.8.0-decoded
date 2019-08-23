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
function protx_config()
{
    $configArray = array("FriendlyName" => array("Type" => "System", "Value" => "SagePay"), "vendorid" => array("FriendlyName" => "Vendor ID", "Type" => "text", "Size" => "20", "Description" => "Main Account Vendor ID used for First Payment"), "recurringvendorid" => array("FriendlyName" => "Vendor ID", "Type" => "text", "Size" => "20", "Description" => "Vendor ID of Continuous Authority Merchant Account used for Recurring Payments"), "testmode" => array("FriendlyName" => "Test Mode", "Type" => "yesno"));
    return $configArray;
}
function protx_3dsecure(array $params)
{
    $whmcs = DI::make("app");
    $TargetURL = "https://live.sagepay.com/gateway/service/vspdirect-register.vsp";
    if ($params["testmode"] == "on") {
        $TargetURL = "https://test.sagepay.com/gateway/service/vspdirect-register.vsp";
    }
    $data = array();
    $data["VPSProtocol"] = "3.00";
    $data["TxType"] = "PAYMENT";
    $data["Vendor"] = $params["vendorid"];
    $data["VendorTxCode"] = date("YmdHis") . $params["invoiceid"];
    $data["Amount"] = $params["amount"];
    $data["Currency"] = $params["currency"];
    $data["Description"] = $params["companyname"] . " - Invoice #" . $params["invoiceid"];
    $cardType = protx_getcardtype($params["cardtype"]);
    $data["CardHolder"] = $params["clientdetails"]["fullname"];
    $data["CardType"] = $cardType;
    $data["CardNumber"] = $params["cardnum"];
    $data["ExpiryDate"] = $params["cardexp"];
    if (!empty($params["cccvv"])) {
        $data["CV2"] = $params["cccvv"];
    }
    $data["BillingSurname"] = $params["clientdetails"]["lastname"];
    $data["BillingFirstnames"] = $params["clientdetails"]["firstname"];
    $data["BillingAddress1"] = $params["clientdetails"]["address1"];
    $data["BillingAddress2"] = $params["clientdetails"]["address2"];
    $data["BillingCity"] = $params["clientdetails"]["city"];
    if ($params["clientdetails"]["country"] == "US") {
        $data["BillingState"] = $params["clientdetails"]["state"];
    }
    $data["BillingPostCode"] = $params["clientdetails"]["postcode"];
    $data["BillingCountry"] = $params["clientdetails"]["country"];
    $data["BillingPhone"] = $params["clientdetails"]["phonenumber"];
    $data["DeliverySurname"] = $params["clientdetails"]["lastname"];
    $data["DeliveryFirstnames"] = $params["clientdetails"]["firstname"];
    $data["DeliveryAddress1"] = $params["clientdetails"]["address1"];
    $data["DeliveryAddress2"] = $params["clientdetails"]["address2"];
    $data["DeliveryCity"] = $params["clientdetails"]["city"];
    if ($params["clientdetails"]["country"] == "US") {
        $data["DeliveryState"] = $params["clientdetails"]["state"];
    }
    $data["DeliveryPostCode"] = $params["clientdetails"]["postcode"];
    $data["DeliveryCountry"] = $params["clientdetails"]["country"];
    $data["DeliveryPhone"] = $params["clientdetails"]["phonenumber"];
    $data["CustomerEMail"] = $params["clientdetails"]["email"];
    $ipAddress = $whmcs->getRemoteIp();
    if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
        $data["ClientIPAddress"] = $ipAddress;
    }
    $data = protx_formatData($data);
    $response = protx_requestPost($TargetURL, $data);
    $baseStatus = $response["Status"];
    switch ($baseStatus) {
        case "3DAUTH":
            logTransaction($params["paymentmethod"], $response, "3D Auth Required");
            WHMCS\Session::set("protxinvoiceid", $params["invoiceid"]);
            $termUrl = $params["systemurl"] . "/modules/gateways/callback/protxthreedsecure.php?invoiceid=" . $params["invoiceid"];
            $code = "<form method=\"post\" action=\"" . $response["ACSURL"] . "\" name=\"paymentfrm\">\n    <input type=\"hidden\" name=\"PaReq\" value=\"" . $response["PAReq"] . "\">\n    <input type=\"hidden\" name=\"TermUrl\" value=\"" . $termUrl . "\">\n    <input type=\"hidden\" name=\"MD\" value=\"" . $response["MD"] . "\">\n    <noscript>\n        <div class=\"errorbox\">\n            <strong>\n                JavaScript is currently disabled or is not supported by your browser.\n            </strong>\n            <br />\n            Please click the continue button to proceed with the processing of your transaction.\n        </div>\n        <p align=\"center\">\n            <input type=\"submit\" value=\"Continue >>\" />\n        </p>\n    </noscript>\n</form>";
            return $code;
        case "OK":
            addInvoicePayment($params["invoiceid"], $response["VPSTxId"], "", "", "protx", "on");
            logTransaction($params["paymentmethod"], $response, "Successful");
            sendMessage("Credit Card Payment Confirmation", $params["invoiceid"]);
            $result = "success";
            return $result;
        case "NOTAUTHED":
            $resultText = "Not Authorised";
            break;
        case "REJECTED":
            $resultText = "Rejected";
            break;
        case "FAIL":
            $resultText = "Failed";
            break;
        default:
            $resultText = "Error";
            break;
    }
    logTransaction($params["paymentmethod"], $response, $resultText);
    sendMessage("Credit Card Payment Failed", $params["invoiceid"]);
    $result = "declined";
    return $result;
}
function protx_capture(array $params)
{
    $whmcs = DI::make("app");
    $TargetURL = "https://live.sagepay.com/gateway/service/vspdirect-register.vsp";
    if ($params["testmode"] == "on") {
        $TargetURL = "https://test.sagepay.com/gateway/service/vspdirect-register.vsp";
    }
    $data = array();
    $data["VPSProtocol"] = "3.00";
    $data["TxType"] = "PAYMENT";
    $data["Vendor"] = $params["recurringvendorid"];
    $data["VendorTxCode"] = date("YmdHis") . $params["invoiceid"];
    $data["Amount"] = $params["amount"];
    $data["Currency"] = $params["currency"];
    $data["Description"] = $params["companyname"] . " - Invoice #" . $params["invoiceid"];
    $cardType = protx_getcardtype($params["cardtype"]);
    $data["CardHolder"] = $params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"];
    $data["CardType"] = $cardType;
    $data["CardNumber"] = $params["cardnum"];
    $data["ExpiryDate"] = $params["cardexp"];
    if (!empty($params["cccvv"])) {
        $data["CV2"] = $params["cccvv"];
    }
    $data["BillingSurname"] = $params["clientdetails"]["lastname"];
    $data["BillingFirstnames"] = $params["clientdetails"]["firstname"];
    $data["BillingAddress1"] = $params["clientdetails"]["address1"];
    $data["BillingAddress2"] = $params["clientdetails"]["address2"];
    $data["BillingCity"] = $params["clientdetails"]["city"];
    if ($params["clientdetails"]["country"] == "US") {
        $data["BillingState"] = $params["clientdetails"]["state"];
    }
    $data["BillingPostCode"] = $params["clientdetails"]["postcode"];
    $data["BillingCountry"] = $params["clientdetails"]["country"];
    $data["BillingPhone"] = $params["clientdetails"]["phonenumber"];
    $data["DeliverySurname"] = $params["clientdetails"]["lastname"];
    $data["DeliveryFirstnames"] = $params["clientdetails"]["firstname"];
    $data["DeliveryAddress1"] = $params["clientdetails"]["address1"];
    $data["DeliveryAddress2"] = $params["clientdetails"]["address2"];
    $data["DeliveryCity"] = $params["clientdetails"]["city"];
    if ($params["clientdetails"]["country"] == "US") {
        $data["DeliveryState"] = $params["clientdetails"]["state"];
    }
    $data["DeliveryPostCode"] = $params["clientdetails"]["postcode"];
    $data["DeliveryCountry"] = $params["clientdetails"]["country"];
    $data["DeliveryPhone"] = $params["clientdetails"]["phonenumber"];
    $data["CustomerEMail"] = $params["clientdetails"]["email"];
    $ipAddress = $whmcs->getRemoteIp();
    if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
        $data["ClientIPAddress"] = $ipAddress;
    }
    $data["ApplyAVSCV2"] = "2";
    $data["Apply3DSecure"] = "2";
    switch ($params["cardtype"]) {
        case "American Express":
        case "Laser":
            $data["AccountType"] = "E";
            break;
        case "Maestro":
            $data["AccountType"] = "M";
            break;
        default:
            $data["AccountType"] = "C";
    }
    $data = protx_formatData($data);
    $response = protx_requestPost($TargetURL, $data);
    $baseStatus = $response["Status"];
    $result = array();
    switch ($baseStatus) {
        case "OK":
            $result["status"] = "success";
            $result["transid"] = $response["VPSTxId"];
            break;
        case "NOTAUTHED":
            $result["status"] = "Not Authorised";
            break;
        case "REJECTED":
            $result["status"] = "Rejected";
            break;
        case "FAIL":
            $result["status"] = "Failed";
            break;
        default:
            $result["status"] = "Error";
            break;
    }
    $result["rawdata"] = $response;
    $result["fee"] = 0;
    if ($params["cardtype"] == "Maestro") {
        invoiceDeletePayMethod($params["invoiceid"]);
    }
    return $result;
}
function protx_requestPost($url, $data)
{
    set_time_limit(60);
    $output = array();
    $curlSession = curl_init();
    curl_setopt($curlSession, CURLOPT_URL, $url);
    curl_setopt($curlSession, CURLOPT_HEADER, 0);
    curl_setopt($curlSession, CURLOPT_POST, 1);
    curl_setopt($curlSession, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curlSession, CURLOPT_TIMEOUT, 60);
    curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curlSession, CURLOPT_SSL_VERIFYHOST, 1);
    $response = explode(chr(10), curl_exec($curlSession));
    if (curl_error($curlSession)) {
        $output["Status"] = "FAIL";
        $output["StatusDetail"] = curl_error($curlSession);
    }
    curl_close($curlSession);
    for ($i = 0; $i < count($response); $i++) {
        $splitAt = strpos($response[$i], "=");
        $output[trim(substr($response[$i], 0, $splitAt))] = trim(substr($response[$i], $splitAt + 1));
    }
    return $output;
}
function protx_formatData(array $data)
{
    $output = "";
    foreach ($data as $key => $value) {
        $output .= "&" . $key . "=" . urlencode($value);
    }
    $output = substr($output, 1);
    return $output;
}
function protx_getcardtype($cardType)
{
    switch ($cardType) {
        case "EnRoute":
        case "Visa":
            $cardType = "VISA";
            break;
        case "MasterCard":
            $cardType = "MC";
            break;
        case "American Express":
            $cardType = "AMEX";
            break;
        case "Diners Club":
        case "Discover":
            $cardType = "DC";
            break;
        case "JCB":
            $cardType = "JCB";
            break;
        case "Visa Debit":
            $cardType = "DELTA";
            break;
        case "Maestro":
            $cardType = "MAESTRO";
            break;
        case "Visa Electron":
            $cardType = "UKE";
            break;
        case "Laser":
            $cardType = "LASER";
            break;
    }
    return $cardType;
}

?>