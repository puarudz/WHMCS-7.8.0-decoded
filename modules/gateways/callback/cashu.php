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
$GATEWAY = getGatewayVariables("cashu");
if (!$GATEWAY["type"]) {
    exit("Module Not Activated");
}
$amount = $_REQUEST["amount"];
$currency = $_REQUEST["currency"];
$trn_id = $_REQUEST["trn_id"];
$session_id = (int) $_REQUEST["session_id"];
$verificationString = $_REQUEST["verificationString"];
$invoiceid = checkCbInvoiceID($session_id, $GATEWAY["paymentmethod"]);
$verstr = array(strtolower($GATEWAY["merchantid"]), strtolower($trn_id), $GATEWAY["encryptionkeyword"]);
$verstr = implode(":", $verstr);
$verstr = sha1($verstr);
if ($verstr == $verificationString) {
    if (isset($GATEWAY["convertto"]) && 0 < strlen($GATEWAY["convertto"])) {
        $invoiceArr = array("id" => $invoiceid);
        $result = select_query("tblinvoices", "userid,total", $invoiceArr);
        $data = mysql_fetch_array($result);
        $total = $data["total"];
        $currencyArr = getCurrency($data["userid"]);
        $amount = convertCurrency($amount, $GATEWAY["convertto"], $currencyArr["id"]);
        $roundAmt = round($amount, 1);
        $roundTotal = round($total, 1);
        if ($roundAmt == $roundTotal) {
            $amount = $total;
        }
    }
    addInvoicePayment($invoiceid, $trn_id, $amount, "0", "cashu");
    $transactionStatus = "Successful";
    $success = true;
} else {
    $transactionStatus = "Invalid Hash";
    $success = false;
}
logTransaction($GATEWAY["paymentmethod"], $_REQUEST, $transactionStatus);
callback3DSecureRedirect($invoiceid, $success);

?>