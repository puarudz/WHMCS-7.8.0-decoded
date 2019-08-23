<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require "../../../init.php";
App::load_function("gateway");
App::load_function("invoice");
$gatewayParams = getGatewayVariables("gocardless");
if (!$gatewayParams["type"]) {
    WHMCS\Terminus::getInstance()->doDie("Module Not Activated");
}
if (App::isInRequest("redirect_flow_id")) {
    $flowId = App::getFromRequest("redirect_flow_id");
    $invoiceData = WHMCS\TransientData::getInstance()->retrieveByData($flowId);
    if (!$invoiceData) {
        WHMCS\Terminus::getInstance()->doDie("Invalid Access Attempt");
    }
    $sessionId = "SESSION_" . $invoiceData;
    $invoiceDataParts = explode("_", $invoiceData);
    list($userId, $invoiceId) = $invoiceDataParts;
    $gatewayParams = getGatewayVariables("gocardless", $invoiceId);
    $client = WHMCS\Module\Gateway\GoCardless\Client::factory($gatewayParams["accessToken"]);
    $postParams = array("data" => array("session_token" => $sessionId));
    try {
        $response = json_decode($client->post("redirect_flows/" . $flowId . "/actions/complete", array("json" => $postParams)));
        logTransaction($gatewayParams["paymentmethod"], $response, "Mandate Setup Redirect Flow");
        $mandate = $response->redirect_flows->links->mandate;
        $successUrl = $response->redirect_flows->confirmation_url;
        $customerBankAccount = $response->redirect_flows->links->customer_bank_account;
        $clientModel = WHMCS\User\Client::find($userId);
        $gatewayInstance = new WHMCS\Module\Gateway();
        if ($clientModel && $gatewayInstance->load($gatewayParams["paymentmethod"])) {
            $billingContact = $clientModel;
            $bankAccountData = json_decode($client->get("/customer_bank_accounts/" . $customerBankAccount));
            $accountNumberLastTwo = str_pad($bankAccountData->customer_bank_accounts->account_number_ending, 8, "x", STR_PAD_LEFT);
            $accountBankName = $bankAccountData->customer_bank_accounts->bank_name;
            $accountHolderName = $bankAccountData->customer_bank_accounts->account_holder_name;
            $payMethod = WHMCS\Payment\PayMethod\Adapter\RemoteBankAccount::factoryPayMethod($clientModel, $billingContact);
            $payMethod->setGateway($gatewayInstance)->save();
            $payment = $payMethod->payment;
            $payment->setRemoteToken($mandate)->setName($accountBankName)->setAccountNumber($accountNumberLastTwo)->validateRequiredValuesPreSave()->save();
        }
        $client->put("mandates/" . $mandate, array("json" => array("mandates" => array("metadata" => array("client_id" => (string) (string) $gatewayParams["clientdetails"]["userid"])))));
        $response = json_decode($client->get("mandates/" . $mandate));
        $nextChargeDate = $response->mandates->next_possible_charge_date;
        $nextChargeDateCarbon = WHMCS\Carbon::createFromFormat("Y-m-d", $nextChargeDate);
        $nextDueDate = explode(" ", $gatewayParams["dueDate"]);
        $nextDueDate = WHMCS\Carbon::createFromFormat("Y-m-d", $nextDueDate[0]);
        if ($nextDueDate < $nextChargeDateCarbon) {
            $nextDueDate = $nextChargeDateCarbon;
        }
        $details = (string) $gatewayParams["amount"] . "|" . $gatewayParams["currencyId"];
        if (array_key_exists("basecurrencyamount", $gatewayParams)) {
            $details = (string) $gatewayParams["basecurrencyamount"] . "|" . $gatewayParams["baseCurrencyId"];
        }
        $postParams = array("amount" => str_replace(".", "", $gatewayParams["amount"]), "currency" => $gatewayParams["currency"], "charge_date" => $nextDueDate->format("Y-m-d"), "description" => $gatewayParams["description"], "metadata" => array("client_id" => (string) (string) $gatewayParams["clientdetails"]["userid"], "invoice_id" => (string) (string) $gatewayParams["invoiceid"], "invoice_details" => $details), "links" => array("mandate" => $mandate));
        $response = json_decode($client->post("payments", array("json" => array("payments" => $postParams))));
        $invoiceModel = WHMCS\Billing\Invoice::findOrFail($invoiceId);
        $invoiceModel->status = "Payment Pending";
        $invoiceModel->save();
        $history = WHMCS\Billing\Payment\Transaction\History::firstOrNew(array("invoice_id" => $gatewayParams["invoiceid"], "gateway" => $gatewayParams["paymentmethod"], "transaction_id" => $response->payments->id));
        $history->remoteStatus = $response->payments->status;
        $history->description = $gatewayParams["description"];
        $history->completed = false;
        $history->additionalInformation = json_decode(json_encode($response->payments), true);
        $history->save();
        logTransaction($gatewayParams["paymentmethod"], $response, "Payment Pending", array("history_id" => $history->id));
        $file = "clientarea.php";
        $vars = "";
        if ($invoiceId) {
            $file = "viewinvoice.php";
            $vars = "id=" . $invoiceId;
        }
        redirSystemURL($vars, $file);
    } catch (Exception $e) {
        logTransaction($gatewayParams["paymentmethod"], array("data" => $_REQUEST, "error" => $e->getMessage()), "Error", $gatewayParams);
        WHMCS\Terminus::getInstance()->doDie($e->getMessage());
    }
}
$response = WHMCS\Http\Message\ServerRequest::fromGlobals();
$responseBody = $response->getBody()->getContents();
$parsedBody = json_decode($responseBody, true);
$checkHeader = $response->getHeader("Webhook-Signature-Whmcs");
$headers = $response->getHeaders();
if ($checkHeader) {
    $checkHeader = $checkHeader[0];
}
$verificationHash = sha1($gatewayParams["callbackToken"] . $responseBody);
if (!hash_equals($checkHeader, $verificationHash)) {
    logTransaction($gatewayParams["paymentmethod"], $responseBody . "\r\nVerification Hash: " . $checkHeader, "Verification Failed", $gatewayParams);
    header("Status: 498 Token Invalid");
    WHMCS\Terminus::getInstance()->doExit();
}
if ($parsedBody) {
    $resources = WHMCS\Module\Gateway\GoCardless\Resources::RESOURCES;
    foreach ($parsedBody["events"] as $event) {
        if (array_key_exists($event["resource_type"], $resources)) {
            $class = $resources[$event["resource_type"]];
            $interface = new $class($gatewayParams);
            if (method_exists($interface, $event["action"])) {
                $method = $event["action"];
                try {
                    $interface->{$method}($event);
                    logTransaction($gatewayParams["paymentmethod"], $event, ucwords((string) $event["resource_type"] . " " . $event["action"]), $gatewayParams);
                } catch (Exception $e) {
                    logTransaction($gatewayParams["paymentmethod"], array_merge($event, array("error_message" => $e->getMessage())), "Invalid Request", $gatewayParams);
                }
                continue;
            }
            if (method_exists($interface, "defaultAction")) {
                try {
                    $interface->defaultAction($event);
                } catch (Exception $e) {
                    logTransaction($gatewayParams["paymentmethod"], array_merge($event, array("error_message" => $e->getMessage())), "Invalid Request", $gatewayParams);
                }
                continue;
            }
        }
        logTransaction($gatewayParams["paymentmethod"], $event, "Notification Only", $gatewayParams);
    }
    WHMCS\Terminus::getInstance()->doExit();
}
logTransaction($gatewayParams["paymentmethod"], $responseBody, "Invalid Request", $gatewayParams);

?>