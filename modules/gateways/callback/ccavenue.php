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
$GATEWAY = getGatewayVariables("ccavenue");
if (!$GATEWAY["type"]) {
    exit("Module Not Activated");
}
$Order_Id = $_POST["Order_Id"];
$WorkingKey = $GATEWAY["workingkey"];
$Amount = $_POST["Amount"];
$AuthDesc = $_POST["AuthDesc"];
$Checksum = $_POST["Checksum"];
$Merchant_Id = $_POST["Merchant_Id"];
$signup = $_POST["Merchant_Param"];
$Checksum = ccavenue_verifyChecksum($Merchant_Id, $Order_Id, $Amount, $AuthDesc, $Checksum, $WorkingKey);
$invoiceid = explode("_", $Order_Id);
$invoiceid = $invoiceid[0];
$invoiceid = checkCbInvoiceID($invoiceid, $GATEWAY["paymentmethod"]);
if ($Checksum == "true" && $AuthDesc == "Y") {
    addInvoicePayment($invoiceid, $Order_Id, "", "", "ccavenue");
    $redirectUrl = "id=" . $invoiceid . "&paymentsuccess=true";
    $transactionStatus = "Successful";
} else {
    $redirectUrl = "id=" . $invoiceid . "&paymentfailed=true";
    $transactionStatus = "Error";
}
logTransaction($GATEWAY["paymentmethod"], $_REQUEST, $transactionStatus);
redirSystemURL($redirectUrl, "viewinvoice.php");
function ccavenue_verifychecksum($MerchantId, $OrderId, $Amount, $AuthDesc, $CheckSum, $WorkingKey)
{
    $str = (string) $MerchantId . "|" . $OrderId . "|" . $Amount . "|" . $AuthDesc . "|" . $WorkingKey;
    $adler = 1;
    $adler = ccavenuecb_adler32($adler, $str);
    if ($adler == $CheckSum) {
        return "true";
    }
    return "false";
}
function ccavenuecb_adler32($adler, $str)
{
    $BASE = 65521;
    $s1 = $adler & 65535;
    $s2 = $adler >> 16 & 65535;
    for ($i = 0; $i < strlen($str); $i++) {
        $s1 = ($s1 + Ord($str[$i])) % $BASE;
        $s2 = ($s2 + $s1) % $BASE;
    }
    return ccavenuecb_leftshift($s2, 16) + $s1;
}
function ccavenuecb_leftshift($str, $num)
{
    $str = DecBin($str);
    for ($i = 0; $i < 64 - strlen($str); $i++) {
        $str = "0" . $str;
    }
    for ($i = 0; $i < $num; $i++) {
        $str = $str . "0";
        $str = substr($str, 1);
    }
    return ccavenuecb_cdec($str);
}
function ccavenuecb_cdec($num)
{
    for ($n = 0; $n < strlen($num); $n++) {
        $temp = $num[$n];
        $dec = $dec + $temp * pow(2, strlen($num) - $n - 1);
    }
    return $dec;
}

?>