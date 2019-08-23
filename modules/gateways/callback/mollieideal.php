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
$gatewaymodule = "mollieideal";
$GATEWAY = getGatewayVariables($gatewaymodule);
if (!$GATEWAY["type"]) {
    exit("Module Not Activated");
}
$invoiceid = urldecode($_GET["invoiceid"]);
$transid = $_GET["transaction_id"];
$amount = urldecode($_GET["amount"]);
$fee = urldecode($_GET["fee"]);
checkCbTransID($transid);
$transactionStatus = "Unsuccessful";
if (isset($transid)) {
    $iDEAL = new iDEAL_Payment($GATEWAY["partnerid"]);
    $iDEAL->checkPayment($_GET["transaction_id"]);
    if ($iDEAL->getPaidStatus() == true) {
        addInvoicePayment($invoiceid, $transid, $amount, $fee, $gatewaymodule);
        $transactionStatus = "Successful";
    }
}
logTransaction($GATEWAY["paymentmethod"], $_REQUEST, $transactionStatus);

?>