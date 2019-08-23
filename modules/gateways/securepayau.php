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
$GATEWAYMODULE["securepayauname"] = "securepayau";
$GATEWAYMODULE["securepayauvisiblename"] = "SecurePay AU";
$GATEWAYMODULE["securepayautype"] = "CC";
function securepayau_activate()
{
    defineGatewayField("securepayau", "text", "merchantid", "", "Merchant ID", "20", "");
    defineGatewayField("securepayau", "text", "transpassword", "", "Transaction Password", "20", "");
    defineGatewayField("securepayau", "yesno", "testmode", "", "Test Mode", "", "");
}
function securepayau_capture($params)
{
    $request_type = "payment";
    $payment_type = "0";
    $merchant_id = $params["merchantid"];
    $transaction_password = $params["transpassword"];
    $payment_amount = $params["amount"] * 100;
    $payment_reference = $params["invoiceid"];
    $card_holder = $params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"];
    $card_number = $params["cardnum"];
    $card_cvv = $params["cccvv"];
    $card_expiry_month = substr($params["cardexp"], 0, 2);
    $card_expiry_year = substr($params["cardexp"], 2, 2);
    $currency = $params["currency"];
    if ($params["testmode"]) {
        $host = "test.api.securepay.com.au/xmlapi/payment";
    } else {
        $host = "api.securepay.com.au/xmlapi/payment";
    }
    $timestamp = securepayau_getGMTtimestamp();
    $vars = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" . "<SecurePayMessage>" . "<MessageInfo>" . "<messageID>8af793f9af34bea0cf40f5fb5c630c</messageID>" . "<messageTimestamp>" . urlencode($timestamp) . "</messageTimestamp>" . "<timeoutValue>60</timeoutValue>" . "<apiVersion>xml-4.2</apiVersion>" . "</MessageInfo>" . "<MerchantInfo>" . "<merchantID>" . urlencode($merchant_id) . "</merchantID>" . "<password>" . urlencode($transaction_password) . "</password>" . "</MerchantInfo>" . "<RequestType>" . urlencode($request_type) . "</RequestType>" . "<Payment>" . "<TxnList count=\"1\">" . "<Txn ID=\"1\">" . "<txnType>" . urlencode($payment_type) . "</txnType>" . "<txnSource>23</txnSource>" . "<amount>" . $payment_amount . "</amount>" . "<purchaseOrderNo>" . urlencode($payment_reference) . "</purchaseOrderNo>" . "<currency>" . urlencode($currency) . "</currency>" . "<preauthID>" . urlencode($preauthid) . "</preauthID>" . "<txnID>" . urlencode($txnid) . "</txnID>" . "<CreditCardInfo>" . "<cardNumber>" . urlencode($card_number) . "</cardNumber>" . "<cvv>" . urlencode($card_cvv) . "</cvv>" . "<expiryDate>" . urlencode($card_expiry_month) . "/" . urlencode($card_expiry_year) . "</expiryDate>" . "</CreditCardInfo>" . "<DirectEntryInfo>" . "<bsbNumber>" . urlencode($bsb_number) . "</bsbNumber>" . "<accountNumber>" . urlencode($account_number) . "</accountNumber>" . "<accountName>" . urlencode($account_name) . "</accountName>" . "</DirectEntryInfo>" . "</Txn>" . "</TxnList>" . "</Payment>" . "</SecurePayMessage>";
    $gatewayurl = "https://" . $host;
    $response = curlCall($gatewayurl, $vars);
    $xmlres = array();
    $xmlres = securepayau_makeXMLTree($response);
    $messageID = trim($xmlres[SecurePayMessage][MessageInfo][messageID]);
    $messageTimestamp = trim($xmlres[SecurePayMessage][MessageInfo][messageTimestamp]);
    $apiVersion = trim($xmlres[SecurePayMessage][MessageInfo][apiVersion]);
    $RequestType = trim($xmlres[SecurePayMessage][RequestType]);
    $merchantID = trim($xmlres[SecurePayMessage][MerchantInfo][merchantID]);
    $statusCode = trim($xmlres[SecurePayMessage][Status][statusCode]);
    $statusDescription = trim($xmlres[SecurePayMessage][Status][statusDescription]);
    $txnType = trim($xmlres[SecurePayMessage][Payment][TxnList][Txn][txnType]);
    $txnSource = trim($xmlres[SecurePayMessage][Payment][TxnList][Txn][txnSource]);
    $amount = trim($xmlres[SecurePayMessage][Payment][TxnList][Txn][amount]);
    $currency = trim($xmlres[SecurePayMessage][Payment][TxnList][Txn][currency]);
    $purchaseOrderNo = trim($xmlres[SecurePayMessage][Payment][TxnList][Txn][purchaseOrderNo]);
    $approved = trim($xmlres[SecurePayMessage][Payment][TxnList][Txn][approved]);
    $responseCode = trim($xmlres[SecurePayMessage][Payment][TxnList][Txn][responseCode]);
    $responseText = trim($xmlres[SecurePayMessage][Payment][TxnList][Txn][responseText]);
    $settlementDate = trim($xmlres[SecurePayMessage][Payment][TxnList][Txn][settlementDate]);
    $txnID = trim($xmlres[SecurePayMessage][Payment][TxnList][Txn][txnID]);
    $preauthID = trim($xmlres[SecurePayMessage][Payment][TxnList][Txn][preauthID]);
    $pan = trim($xmlres[SecurePayMessage][Payment][TxnList][Txn][CreditCardInfo][pan]);
    $expiryDate = trim($xmlres[SecurePayMessage][Payment][TxnList][Txn][CreditCardInfo][expiryDate]);
    $cardType = trim($xmlres[SecurePayMessage][Payment][TxnList][Txn][CreditCardInfo][cardType]);
    $cardDescription = trim($xmlres[SecurePayMessage][Payment][TxnList][Txn][CreditCardInfo][cardDescription]);
    $bsbNumber = trim($xmlres[SecurePayMessage][Payment][TxnList][Txn][DirectEntryInfo][bsbNumber]);
    $accountNumber = trim($xmlres[SecurePayMessage][Payment][TxnList][Txn][DirectEntryInfo][accountNumber]);
    $accountName = trim($xmlres[SecurePayMessage][Payment][TxnList][Txn][DirectEntryInfo][accountName]);
    $transreport = print_r($xmlres, true);
    if ($responseCode == "00" || $responseCode == "08" || $responseCode == "77") {
        return array("status" => "success", "transid" => $txnID, "rawdata" => $transreport);
    }
    return array("status" => "declined", "rawdata" => $transreport);
}
function securepayau_getGMTtimeStamp()
{
    $stamp = date("YmdGis") . "000+1000";
    return $stamp;
}
function securepayau_openSocket($host, $query)
{
    $url = "https://" . $host;
    $data = curlCall($url, $query);
    return $data;
}
function securepayau_makeXMLTree($rawxml)
{
    include_once ROOTDIR . "/includes/functions.php";
    $options = array(XML_OPTION_CASE_FOLDING => 0, XML_OPTION_SKIP_WHITE => 1);
    return ParseXmlToArray($rawxml, $options);
}

?>