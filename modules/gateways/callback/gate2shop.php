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
$GATEWAY = getGatewayVariables("gate2shop");
if (!$GATEWAY["type"]) {
    exit("Module Not Activated");
}
$cId = $_REQUEST["customField1"];
$invoiceid = checkCbInvoiceID($cId, $GATEWAY["paymentmethod"]);
if (isset($_REQUEST["TransactionID"])) {
    $trId = $_REQUEST["TransactionID"];
}
if (isset($_REQUEST["ErrCode"])) {
    $errCode = $_REQUEST["ErrCode"];
}
if (isset($_REQUEST["ExErrCode"])) {
    $exErrCode = $_REQUEST["ExErrCode"];
}
if (isset($_REQUEST["Status"])) {
    $status = $_REQUEST["Status"];
}
if (isset($_REQUEST["responsechecksum"])) {
    $responsechecksum = $_REQUEST["responsechecksum"];
}
if (isset($_REQUEST["AuthCode"])) {
    $authCode = $_REQUEST["AuthCode"];
}
if (isset($_REQUEST["Token"])) {
    $token = $_REQUEST["Token"];
}
if (isset($_REQUEST["Reason"])) {
    $reason = $_REQUEST["Reason"];
}
if (isset($_REQUEST["ReasonCode"])) {
    $ReasonCode = $_REQUEST["ReasonCode"];
}
if (isset($_REQUEST["responsechecksum"])) {
    $responseChecksum = $_REQUEST["responsechecksum"];
}
if (isset($_REQUEST["totalAmount"])) {
    $totalAmount = $_REQUEST["totalAmount"];
}
if (isset($_REQUEST["ClientUniqueID"])) {
    $custId = $_REQUEST["ClientUniqueID"];
}
$sCheckString = $GATEWAY["SecretKey"];
$sCheckString .= $trId;
$sCheckString .= $errCode;
$sCheckString .= $exErrCode;
$sCheckString .= $status;
$checksum = md5($sCheckString);
if ($responseChecksum == $checksum) {
    if (isset($_REQUEST["ErrCode"]) && $_REQUEST["ErrCode"] == 0 && isset($_REQUEST["ExErrCode"]) && $_REQUEST["ExErrCode"] == 0 && isset($_REQUEST["Status"]) && $_REQUEST["Status"] == "APPROVED") {
        addInvoicePayment($invoiceid, $trId, "", "", "gate2shop");
        logTransaction($GATEWAY["paymentmethod"], $_REQUEST, "Successful");
        redirSystemURL("id=" . $invoiceid . "&paymentsuccess=true", "viewinvoice.php");
    } else {
        logTransaction($GATEWAY["paymentmethod"], $_REQUEST, "Failed");
    }
} else {
    logTransaction($GATEWAY["paymentmethod"], $_REQUEST, "Checksum Error");
}
redirSystemURL("id=" . $invoiceid . "&paymentfailed=true", "viewinvoice.php");

?>