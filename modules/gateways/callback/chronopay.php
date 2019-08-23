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
$GATEWAY = getGatewayVariables("chronopay");
if (!$GATEWAY["type"]) {
    exit("Module Not Activated");
}
$postedSign = $whmcs->get_req_var("sign");
$builtSign = md5($GATEWAY["sharedsecret"] . $whmcs->get_req_var("customer_id") . $whmcs->get_req_var("transaction_id") . $whmcs->get_req_var("transaction_type") . $whmcs->get_req_var("total"));
$transactionStatus = "Invalid";
if (strcasecmp($_POST["transaction_type"], "Purchase") == 0) {
    $transactionStatus = "Signature Error";
    if ($postedSign == $builtSign) {
        $transid = $_POST["transaction_id"];
        $invoiceid = $_POST["cs1"];
        $amount = $_POST["total"];
        $invoiceid = checkCbInvoiceID($invoiceid, $GATEWAY["paymentmethod"]);
        addInvoicePayment($invoiceid, $transid, "", "", "chronopay");
        $transactionStatus = "Successful";
    }
}
logTransaction($GATEWAY["paymentmethod"], $_REQUEST, $transactionStatus);
header("HTTP/1.1 200 OK");
exit("");

?>