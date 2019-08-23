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
require "../protx.php";
$GATEWAY = $params = getGatewayVariables("protx");
if (!$GATEWAY["type"]) {
    exit("Module Not Activated");
}
$url = "https://live.sagepay.com/gateway/service/direct3dcallback.vsp";
if ($params["testmode"] == "on") {
    $url = "https://test.sagepay.com/gateway/service/direct3dcallback.vsp";
}
$data = protx_formatData($_POST);
$response = protx_requestPost($url, $data);
$baseStatus = $response["Status"];
$invoiceId = (int) $whmcs->get_req_var("invoiceid");
if (!$invoiceId && WHMCS\Session::get("protxinvoiceid")) {
    $invoiceId = (int) WHMCS\Session::getAndDelete("protxinvoiceid");
}
checkCbInvoiceID($invoiceId, "protx");
$response["Invoice ID"] = $invoiceId;
if ($params["cardtype"] == "Maestro") {
    invoiceDeletePayMethod($invoiceid);
}
$callbackSuccess = false;
$email = "Credit Card Payment Failed";
switch ($response["Status"]) {
    case "OK":
        addInvoicePayment($invoiceId, $response["VPSTxId"], "", "", "protx", "on");
        $resultText = "Successful";
        $email = "Credit Card Payment Confirmation";
        $callbackSuccess = true;
        break;
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
}
logTransaction($GATEWAY["paymentmethod"], $response, $resultText);
sendMessage($email, $invoiceId);
callback3DSecureRedirect($invoiceId, $callbackSuccess);

?>