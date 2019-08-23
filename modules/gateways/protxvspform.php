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
function protxvspform_config()
{
    $configArray = array("FriendlyName" => array("Type" => "System", "Value" => "SagePay Form"), "vendorname" => array("FriendlyName" => "Vendor Name", "Type" => "text", "Size" => "25", "Description" => "The Vendor Name assigned to you by Sage Pay"), "xorencryptionpw" => array("FriendlyName" => "Encryption Password", "Type" => "text", "Size" => "25", "Description" => "The AES Encryption Password assigned to you by Sage Pay"), "vendoremail" => array("FriendlyName" => "Vendor Email", "Type" => "text", "Size" => "40", "Description" => "The email address you want Sage Pay to send receipts to (leave blank for none)"), "testmode" => array("FriendlyName" => "Test Mode", "Type" => "yesno"));
    return $configArray;
}
function protxvspform_link(array $params)
{
    $strEncryptionPassword = $params["xorencryptionpw"];
    $strVendorTxCode = date("YmdHis") . $params["invoiceid"];
    $strPost = "VendorTxCode=" . $strVendorTxCode;
    $strPost .= "&Amount=" . number_format($params["amount"], 2);
    $strPost .= "&Currency=" . $params["currency"];
    $strPost .= "&Description=" . $params["description"];
    $strPost .= "&SuccessURL=" . $params["systemurl"] . "/modules/gateways/callback/protxvspform.php?invoiceid=" . $params["invoiceid"];
    $strPost .= "&FailureURL=" . $params["systemurl"] . "/modules/gateways/callback/protxvspform.php?invoiceid=" . $params["invoiceid"];
    $strPost .= "&CustomerName=" . $params["clientdetails"]["fullname"];
    if (!empty($params["vendoremail"])) {
        $strPost .= "&VendorEMail=" . $params["vendoremail"];
    }
    $strPost .= "&BillingSurname=" . $params["clientdetails"]["lastname"];
    $strPost .= "&BillingFirstnames=" . $params["clientdetails"]["firstname"];
    $strPost .= "&BillingAddress1=" . $params["clientdetails"]["address1"];
    $strPost .= "&BillingCity=" . $params["clientdetails"]["city"];
    $strPost .= "&BillingPostCode=" . $params["clientdetails"]["postcode"];
    if (!empty($params["clientdetails"]["state"]) && $params["clientdetails"]["country"] == "US") {
        $strPost .= "&BillingState=" . $params["clientdetails"]["state"];
    }
    $strPost .= "&BillingCountry=" . $params["clientdetails"]["countrycode"];
    $strPost .= "&DeliverySurname=" . $params["clientdetails"]["lastname"];
    $strPost .= "&DeliveryFirstnames=" . $params["clientdetails"]["firstname"];
    $strPost .= "&DeliveryAddress1=" . $params["clientdetails"]["address1"];
    $strPost .= "&DeliveryCity=" . $params["clientdetails"]["city"];
    $strPost .= "&DeliveryPostCode=" . $params["clientdetails"]["postcode"];
    if (!empty($params["clientdetails"]["state"]) && $params["clientdetails"]["country"] == "US") {
        $strPost .= "&DeliveryState=" . $params["clientdetails"]["state"];
    }
    $strPost .= "&DeliveryCountry=" . $params["clientdetails"]["countrycode"];
    $cipher = new phpseclib\Crypt\AES();
    $cipher->setKey($strEncryptionPassword);
    $cipher->setIV($strEncryptionPassword);
    $strCrypt = strtoupper(bin2hex($cipher->encrypt($strPost)));
    $strPurchaseURL = "https://live.sagepay.com/gateway/service/vspform-register.vsp";
    if ($params["testmode"]) {
        $strPurchaseURL = "https://test.sagepay.com/gateway/service/vspform-register.vsp";
    }
    $code = "<form action=\"" . $strPurchaseURL . "\" method=\"post\">\n    <input type=\"hidden\" name=\"VPSProtocol\" value=\"3.00\">\n    <input type=\"hidden\" name=\"TxType\" value=\"PAYMENT\">\n    <input type=\"hidden\" name=\"Vendor\" value=\"" . $params["vendorname"] . "\">\n    <input type=\"hidden\" name=\"Crypt\" value=\"@" . $strCrypt . "\">\n    <input type=\"submit\" value=\"" . $params["langpaynow"] . "\">\n    </form><br />";
    return $code;
}

?>