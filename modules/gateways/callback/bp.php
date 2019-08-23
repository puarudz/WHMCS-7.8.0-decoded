<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require "../../../init.php";
App::load_function("clientarea");
App::load_function("gateway");
App::load_function("invoice");
$jsonResponse = file_get_contents("php://input");
$response = WHMCS\Filter\Json::safeDecode($jsonResponse, true);
$params = getGatewayVariables("bp");
if (is_null($response) && json_last_error() !== JSON_ERROR_NONE) {
    logTransaction($params["paymentmethod"], $jsonResponse, "Invalid Response");
    header("HTTP/1.0 406 Not Acceptable");
    WHMCS\Terminus::getInstance()->doExit();
}
$bitPayInvoiceId = $response["id"];
$bitPay = new WHMCS\Module\Gateway\BP\BitPay($params);
$bitPayClient = $bitPay->getConnectionClient();
$bitPayClient->setToken((new Bitpay\Token())->setToken($params["apiKey"])->setFacade("merchant"));
try {
    $bitPayInvoice = $bitPayClient->getInvoice($bitPayInvoiceId);
} catch (Exception $e) {
    logTransaction($params["paymentmethod"], $response, "Invalid Invoice");
    header("HTTP/1.0 406 Not Acceptable");
    WHMCS\Terminus::getInstance()->doExit();
}
$invoiceId = (int) $bitPayInvoice->getOrderId();
if (!$invoiceId) {
    $transientData = WHMCS\TransientData::getInstance()->retrieveByData($bitPayInvoiceId);
    $invoiceId = (int) str_replace("BitPay", "", $transientData);
}
try {
    $invoice = WHMCS\Billing\Invoice::with("client", "client.currencyrel")->findOrFail($invoiceId);
} catch (Exception $e) {
    logTransaction($params["paymentmethod"], array_merge($response, array("whmcs_invoice_id" => $invoiceId)), "Invoice ID Not Found");
    header("HTTP/1.1 200 OK");
    header("Status: 200 OK");
    WHMCS\Terminus::getInstance()->doExit();
}
checkCbTransID($bitPayInvoiceId);
$params = getGatewayVariables("bp", $invoiceId);
$paymentStatus = $bitPayInvoice->getStatus();
switch ($paymentStatus) {
    case "complete":
    case "confirmed":
        $invoiceCurrency = $bitPayInvoice->getCurrency()->getCode();
        try {
            $currency = WHMCS\Billing\Currency::where("code", $invoiceCurrency)->firstOrFail();
        } catch (Exception $e) {
            logTransaction($params["paymentmethod"], $response, "Unrecognised Currency", $params);
            header("HTTP/1.0 406 Not Acceptable");
            WHMCS\Terminus::getInstance()->doExit();
        }
        $amount = $bitPayInvoice->getPrice();
        $amount = WHMCS\Billing\Invoice\Helper::convertCurrency($amount, $currency, $invoice);
        $invoice->addPayment($amount, $bitPayInvoiceId, 0, $params["paymentmethod"]);
        logTransaction($params["paymentmethod"], $response, "Success", $params);
        WHMCS\TransientData::getInstance()->delete("BitPay" . $invoiceId);
        break;
}
header("HTTP/1.1 200 OK");
header("Status: 200 OK");

?>