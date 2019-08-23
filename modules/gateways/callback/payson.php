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
$GATEWAY = getGatewayVariables("payson");
if (!$GATEWAY["type"]) {
    exit("Module Not Activated");
}
$strYourSecretKey = $GATEWAY["key"];
$strOkURL = $_GET["OkURL"];
$strRefNr = $_GET["RefNr"];
$strPaysonRef = $_GET["Paysonref"];
$strTestMD5String = $strOkURL . $strPaysonRef . $strYourSecretKey;
$strMD5Hash = md5($strTestMD5String);
$transactionStatus = "Unsuccessful";
$redirectFile = "clientarea.php";
$redirectUrl = "action=invoices";
if ($strMD5Hash == $_GET["MD5"]) {
    $invoiceid = checkCbInvoiceID($_REQUEST["RefNr"], $GATEWAY["paymentmethod"]);
    addInvoicePayment($invoiceid, $strPaysonRef, "", "", "payson");
    $transactionStatus = "Successful";
    $redirectFile = "viewinvoice.php";
    $redirectUrl = "id=" . $invoiceid . "&paymentsuccess=true";
}
logTransaction($GATEWAY["paymentmethod"], $_REQUEST, "Unsuccessful");
redirSystemURL("action=invoices", $redirectFile);

?>