<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require "../../../init.php";
$whmcs = App::self();
$whmcs->load_function("gateway");
$whmcs->load_function("invoice");
$invoiceId = (int) App::getFromRequest("invoice_id");
$merchantId = App::getFromRequest("merchant_id");
$transactionId = App::getFromRequest("transaction_id");
$status = App::getFromRequest("status");
$md5sig = App::getFromRequest("md5sig");
$recTransactionId = App::getFromRequest("rec_payment_id");
if ($invoiceId) {
    try {
        $invoice = new WHMCS\Invoice($invoiceId);
        $params = $invoice->getGatewayInvoiceParams();
        $payToEmail = App::getFromRequest("pay_to_email");
        $customerEmail = App::getFromRequest("pay_from_email");
        $paymentAmount = App::getFromRequest("mb_amount");
        $paymentCurrency = App::getFromRequest("mb_currency");
        $failedCode = App::getFromRequest("failed_reason_code");
        $amount = App::getFromRequest("amount");
        $currency = App::getFromRequest("currency");
        $md5Secret = strtoupper(md5($params["secretWord"]));
        $validateSig = md5($merchantId . $transactionId . $md5Secret . $paymentAmount . $paymentCurrency . $status);
        if ($status == "-1") {
            $validateSig = md5($merchantId . $transactionId . $md5Secret . $status . $recTransactionId);
        }
        if (strtoupper($validateSig) != $md5sig) {
            throw new WHMCS\Exception\Module\InvalidConfiguration("MD5 Signature Failure");
        }
        $model = $params["clientdetails"]["model"];
        if ($model instanceof WHMCS\User\Client\Contact) {
            $model = $model->client;
        }
        $postFields = array("email" => $params["emailAddress"], "password" => md5($params["apiMqiPassword"]), "action" => "status_trn", "mb_trn_id" => $transactionId);
        $url = "https://www.skrill.com/app/query.pl";
        $rawResponse = curlCall($url, $postFields);
        if (substr($rawResponse, 0, 10) == "CURL Error") {
            throw new WHMCS\Exception\Module\NotServicable($rawResponse);
        }
        $response = explode("\n", $rawResponse);
        $response = $response[1];
        $result = array();
        parse_str($response, $result);
        if ($amount != $result["amount"] || $currency != $result["currency"] || $status != $result["status"] || $invoiceId != $result["invoice_id"] || $payToEmail != $result["pay_to_email"] || $customerEmail != $result["pay_from_email"]) {
            logTransaction("skrill", array_merge($_REQUEST, array("validation_data" => $result)), "Validation Failed");
            throw new WHMCS\Payment\Exception\InvalidModuleException("Invalid Transaction Details");
        }
        $amount = $result["amount"];
        $currency = $result["currency"];
        $status = $result["status"];
        $currencyData = WHMCS\Database\Capsule::table("tblcurrencies")->where("code", $currency)->first();
        if (!$currencyData || $currencyData->id != $params["currencyId"]) {
            throw new WHMCS\Exception\Module\InvalidConfiguration("Unrecognised Currency");
        }
        $currencyId = $currencyData->id;
        switch ($status) {
            case -3:
                paymentReversed("Reverse" . $transactionId, $transactionId, $invoiceId, "skrill");
                logTransaction("skrill", $_REQUEST, "Payment Reversed");
                break;
            case -2:
                logTransaction("skrill", $_REQUEST, "Payment Declined", $params);
                break;
            case -1:
                invoiceDeletePayMethod($params["invoiceid"]);
                logTransaction("skrill", $_REQUEST, "1-Tap Recurring Cancelled", $params);
                break;
            case 2:
                if ($recTransactionId) {
                    $payMethod = $model->payMethods()->where("gateway_name", "skrill")->first();
                    $gatewayId = NULL;
                    if (!$payMethod) {
                        $payMethod = $client->payMethods()->where("gateway_name", "gocardless")->first();
                        if (!$payMethod) {
                            $payMethod = WHMCS\Payment\PayMethod\Adapter\RemoteCreditCard::factoryPayMethod($model, $model, "Skrill Card Payment");
                        }
                        $payMethod->payment->setRemoteToken($recTransactionId);
                        $payMethod->payment->save();
                    }
                    if ($payMethod->payment->getRemoteToken() != $recTransactionId) {
                        $postFields = array("email" => $params["emailAddress"], "password" => md5($params["apiMqiPassword"]), "action" => "cancel_od", "amount" => 0, "trn_id" => $payMethod->payment->getRemoteToken());
                        $url = "https://www.skrill.com/app/query.pl";
                        $rawResponse = curlCall($url, $postFields);
                        logTransaction("skrill", array("response" => $rawResponse, "request" => $postFields), "Cancel Old 1-Tap", $params);
                    }
                    invoiceSetPayMethodRemoteToken($params["invoiceid"], $recTransactionId);
                }
                $clientCurrency = $params["clientdetails"]["currency"];
                if ($currencyId && $clientCurrency != $currencyId) {
                    $amount = convertCurrency($amount, $currencyId, $clientCurrency);
                    $total = array_key_exists("baseamount", $params) ? $params["baseamount"] : $params["amount"];
                    if ($total < $amount + 1 && $amount - 1 < $total) {
                        $amount = $total;
                    }
                }
                addInvoicePayment($params["invoiceid"], $transactionId, $amount, 0, "skrill");
                logTransaction("skrill", $_REQUEST, "Success", $params);
                break;
        }
    } catch (WHMCS\Exception\Module\NotServicable $e) {
        WHMCS\Terminus::getInstance()->doDie($e->getMessage());
    } catch (WHMCS\Exception\Fatal $e) {
        WHMCS\Terminus::getInstance()->doDie("Module Not Activated");
    } catch (WHMCS\Exception\Module\InvalidConfiguration $e) {
        logTransaction("skrill", $_REQUEST, $e->getMessage());
    } catch (WHMCS\Payment\Exception\InvalidModuleException $e) {
        logTransaction("skrill", $_REQUEST, $e->getMessage());
    } catch (Exception $e) {
        logTransaction("skrill", $_REQUEST, "Error");
    }
}

?>