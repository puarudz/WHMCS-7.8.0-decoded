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
$GATEWAY = getGatewayVariables("sagepayrepeats");
if (!$GATEWAY["type"]) {
    exit("Module Not Activated");
}
if ($protxsimmode) {
    $url = "https://test.sagepay.com/simulator/VSPDirectCallback.asp";
} else {
    if ($GATEWAY["testmode"]) {
        $url = "https://test.sagepay.com/gateway/service/direct3dcallback.vsp";
    } else {
        $url = "https://live.sagepay.com/gateway/service/direct3dcallback.vsp";
    }
}
$data = "PaRes=" . urlencode($_POST["PaRes"]) . "&MD=" . $_POST["MD"];
$data = sagepayrepeats_formatData($_POST);
$response = sagepayrepeats_requestPost($url, $data);
$baseStatus = $response["Status"];
$transdump = "";
foreach ($response as $key => $value) {
    $transdump .= (string) $key . " => " . $value . "\n";
}
$invoiceid = $_REQUEST["invoiceid"];
$storedInvoiceId = WHMCS\Module\Storage\EncryptedTransientStorage::forModule("sagepayrepeats")->getValue("sagepayrepeatsinvoiceid", NULL);
if (!$invoiceid && !is_null($storedInvoiceId)) {
    $invoiceid = $storedInvoiceId;
}
$invoiceid = checkCbInvoiceID($invoiceid, $GATEWAY["paymentmethod"]);
$invoiceModel = WHMCS\Billing\Invoice::findOrFail($invoiceid);
$userid = $invoiceModel->clientId;
$gatewayid = $invoiceModel->getPayMethodRemoteToken();
$callbacksuccess = false;
$email = "Credit Card Payment Failed";
switch ($response["Status"]) {
    case "OK":
        checkCbTransID($response["VPSTxId"]);
        try {
            $email = "Credit Card Payment Confirmation";
            $callbacksuccess = true;
            $resultStatus = "Successful";
            $invoiceModel->addPayment($invoiceModel->balance, $response["VPSTxId"], 0, "sagepayrepeats", true);
            $gatewayid .= $response["VPSTxId"] . "," . $response["SecurityKey"] . "," . $response["TxAuthNo"];
            $invoiceModel->setPayMethodRemoteToken($gatewayid);
        } catch (Exception $e) {
        }
        break;
    case "NOTAUTHED":
        $resultStatus = "Not Authed";
        break;
    case "REJECTED":
        $resultStatus = "Rejected";
        break;
    case "FAIL":
        $resultStatus = "Failed";
        break;
    default:
        $resultStatus = "Error";
        break;
}
if (!$callbacksuccess) {
    try {
        $invoiceModel->deletePayMethod();
    } catch (Exception $e) {
    }
}
sendMessage($email, $invoiceid);
logTransaction($GATEWAY["paymentmethod"], $transdump, $resultStatus);
callback3DSecureRedirect($invoiceid, $callbacksuccess);

?>