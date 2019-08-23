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
$GATEWAY = getGatewayVariables("quantumvault");
if (!$GATEWAY["type"]) {
    exit("Module Not Activated");
}
$invoiceid = checkCbInvoiceID($_REQUEST["ID"], $GATEWAY["paymentmethod"]);
$transid = $_REQUEST["transID"];
$transresult = $_REQUEST["trans_result"];
$amount = $_REQUEST["amount"];
$md5_hash = $_REQUEST["md5_hash"];
$vaultid = $_REQUEST["cust_id"];
checkCbTransID($transid);
$ourhash = md5($GATEWAY["md5hash"] . $GATEWAY["loginid"] . $transid . $amount);
if ($ourhash != $md5_hash) {
    logTransaction($GATEWAY["paymentmethod"], $_REQUEST, "MD5 Hash Failure");
    echo "Hash Failure. Please Contact Support.";
    exit;
}
if ($GATEWAY["convertto"]) {
    $result = select_query("tblinvoices", "userid,total", array("id" => $invoiceid));
    $data = mysql_fetch_array($result);
    $userid = $data["userid"];
    $total = $data["total"];
    $currency = getCurrency($userid);
    $amount = convertCurrency($amount, $GATEWAY["convertto"], $currency["id"]);
    if ($total < $amount + 1 && $amount - 1 < $total) {
        $amount = $total;
    }
}
if ($transresult == "APPROVED") {
    invoiceSetPayMethodRemoteToken($invoiceid, $vaultid);
    addInvoicePayment($invoiceid, $transid, $amount, "", "quantumvault", "on");
    logTransaction($GATEWAY["paymentmethod"], $_REQUEST, "Approved");
    sendMessage("Credit Card Payment Confirmation", $invoiceid);
    callback3DSecureRedirect($invoiceid, true);
}
logTransaction($GATEWAY["paymentmethod"], $_REQUEST, "Declined");
sendMessage("Credit Card Payment Failed", $invoiceid);
callback3DSecureRedirect($invoiceid, false);

?>