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
function paymentexpress_MetaData()
{
    return array("DisplayName" => "Payment Express", "APIVersion" => "1.1");
}
function paymentexpress_config()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "Payment Express"), "pxpayuserid" => array("FriendlyName" => "User ID", "Type" => "text", "Size" => "20", "Description" => "Your account's user ID"), "pxpaykey" => array("FriendlyName" => "Post Password", "Type" => "text", "Size" => "70", "Description" => "Your account's 64 character key"));
    return $configarray;
}
function paymentexpress_link($params)
{
    $url = "https://sec.paymentexpress.com/pxpay/pxaccess.aspx";
    $xml = "<GenerateRequest>\n<PxPayUserId>" . $params["pxpayuserid"] . "</PxPayUserId>\n<PxPayKey>" . $params["pxpaykey"] . "</PxPayKey>\n<AmountInput>" . $params["amount"] . "</AmountInput>\n<CurrencyInput>" . $params["currency"] . "</CurrencyInput>\n<MerchantReference>" . $params["description"] . "</MerchantReference>\n<EmailAddress>" . $params["clientdetails"]["email"] . "</EmailAddress>\n<TxnData1>" . $params["invoiceid"] . "</TxnData1>\n<TxnType>Purchase</TxnType>\n<TxnId>" . substr(time() . $params["invoiceid"], 0, 16) . "</TxnId>\n<BillingId></BillingId>\n<EnableAddBillCard>0</EnableAddBillCard>\n<UrlSuccess>" . $params["systemurl"] . "/modules/gateways/callback/paymentexpress.php</UrlSuccess>\n<UrlFail>" . $params["systemurl"] . "/clientarea.php</UrlFail>\n</GenerateRequest>";
    $data = curlCall($url, $xml);
    $xmlresponse = XMLtoArray($data);
    $uri = $xmlresponse["REQUEST"]["URI"];
    $code = "<form method=\"post\" action=\"" . $uri . "\"><input type=\"submit\" value=\"" . $params["langpaynow"] . "\"></form>";
    return $code;
}

?>