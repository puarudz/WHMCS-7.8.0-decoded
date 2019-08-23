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
$GATEWAY = getGatewayVariables("nochex");
if (!$GATEWAY["type"]) {
    exit("Module Not Activated");
}
if (!isset($_POST)) {
    $_POST =& $HTTP_POST_VARS;
}
foreach ($_POST as $key => $value) {
    $values[] = $key . "=" . urlencode($value);
}
$work_string = @implode("&", $values);
$url = "https://www.nochex.com/nochex.dll/apc/apc";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDSIZE, 0);
curl_setopt($ch, CURLOPT_POSTFIELDS, $work_string);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
$output = curl_exec($ch);
curl_close($ch);
$response = preg_replace("'Content-type: text/plain'si", "", $output);
$transactionStatus = "Invalid";
if ($response == "AUTHORISED") {
    $invoiceid = checkCbInvoiceID($_POST["order_id"], $GATEWAY["paymentmethod"]);
    addInvoicePayment($invoiceid, $_POST["transaction_id"], "", "", "nochex");
    $transactionStatus = "Successful";
}
logTransaction($GATEWAY["paymentmethod"], $_REQUEST, $transactionStatus);

?>