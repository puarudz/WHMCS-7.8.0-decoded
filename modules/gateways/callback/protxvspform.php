<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require "../../../init.php";
$whmcs->load_function("gateway");
$whmcs->load_function("invoice");
$GATEWAY = getGatewayVariables("protxvspform");
if (!$GATEWAY["type"]) {
    exit("Module Not Activated");
}
$strEncryptionPassword = $GATEWAY["xorencryptionpw"];
$strCrypt = $whmcs->get_req_var("crypt");
$cipher = new phpseclib\Crypt\AES();
$cipher->setKey($GATEWAY["xorencryptionpw"]);
$cipher->setIV($GATEWAY["xorencryptionpw"]);
$strDecoded = $cipher->decrypt(protxvspform_hex2bin(substr($strCrypt, 1)));
$values = getTokenX($strDecoded);
$strStatus = $values["Status"];
$strVendorTxCode = $values["VendorTxCode"];
$strVPSTxId = $values["VPSTxId"];
$invoiceId = (int) substr($strVendorTxCode, 14);
$invoiceId = checkCbInvoiceID($invoiceId, $GATEWAY["paymentmethod"]);
$transactionStatus = "Error";
$redirectUrl = "id=" . $invoiceId . "&paymentfailed=true";
if ($strStatus == "OK") {
    addInvoicePayment($invoiceId, $strVPSTxId, "", "", "protxvspform");
    $transactionStatus = "Successful";
    $redirectUrl = "id=" . $invoiceId . "&paymentsuccess=true";
}
logTransaction($GATEWAY["paymentmethod"], $values, $transactionStatus);
redirSystemURL($redirectUrl, "viewinvoice.php");
function getTokenX($thisString)
{
    $tokens = array("Status", "StatusDetail", "VendorTxCode", "VPSTxId", "TxAuthNo", "Amount", "AVSCV2", "AddressResult", "PostCodeResult", "CV2Result", "GiftAid", "3DSecureStatus", "CAVV", "CardType", "Last4Digits", "DeclineCode", "ExpiryDate", "BankAuthCode");
    $output = array();
    $resultArray = array();
    for ($i = count($tokens) - 1; 0 <= $i; $i--) {
        $start = strpos($thisString, $tokens[$i]);
        if ($start !== false) {
            $resultArray[$i]->start = $start;
            $resultArray[$i]->token = $tokens[$i];
        }
    }
    sort($resultArray);
    for ($i = 0; $i < count($resultArray); $i++) {
        $valueStart = $resultArray[$i]->start + strlen($resultArray[$i]->token) + 1;
        if ($i == count($resultArray) - 1) {
            $output[$resultArray[$i]->token] = substr($thisString, $valueStart);
        } else {
            $valueLength = $resultArray[$i + 1]->start - $resultArray[$i]->start - strlen($resultArray[$i]->token) - 2;
            $output[$resultArray[$i]->token] = substr($thisString, $valueStart, $valueLength);
        }
    }
    return $output;
}
function protxvspform_hex2bin($hexInput)
{
    if (function_exists("hex2bin")) {
        return hex2bin($hexInput);
    }
    $len = strlen($hexInput);
    if ($len % 2 != 0) {
        return false;
    }
    if (strspn($hexInput, "0123456789abcdefABCDEF") != $len) {
        return false;
    }
    $output = "";
    $i = 0;
    while ($i < $len) {
        $output .= pack("H*", substr($hexInput, $i, 2));
        $i += 2;
    }
    return $output;
}

?>