<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
$clientId = App::getFromRequest("clientid");
$payMethodId = App::getFromRequest("paymethodid");
$type = strtolower(App::getFromRequest("type"));
if (!$clientId) {
    $apiresults = array("result" => "error", "message" => "Client ID Is Required");
} else {
    if ($type && !in_array($type, array(strtolower(WHMCS\Payment\PayMethod\Model::TYPE_BANK_ACCOUNT), strtolower(WHMCS\Payment\PayMethod\Model::TYPE_CREDITCARD_LOCAL)))) {
        $apiresults = array("result" => "error", "message" => "Invalid Pay Method Type. Should be 'BankAccount' or 'CreditCard'");
    } else {
        try {
            $client = WHMCS\User\Client::with("payMethods")->findOrFail($clientId);
            if ($payMethodId) {
                $payMethods = $client->payMethods()->where("id", $payMethodId)->get();
            } else {
                if ($type) {
                    $types = array(WHMCS\Payment\PayMethod\Model::TYPE_CREDITCARD_LOCAL, WHMCS\Payment\PayMethod\Model::TYPE_CREDITCARD_REMOTE_MANAGED, WHMCS\Payment\PayMethod\Model::TYPE_CREDITCARD_REMOTE_UNMANAGED);
                    if ($type == strtolower(WHMCS\Payment\PayMethod\Model::TYPE_BANK_ACCOUNT)) {
                        $types = array(WHMCS\Payment\PayMethod\Model::TYPE_BANK_ACCOUNT, WHMCS\Payment\PayMethod\Model::TYPE_REMOTE_BANK_ACCOUNT);
                    }
                    $payMethods = $client->payMethods()->whereIn("payment_type", $types)->get();
                } else {
                    $payMethods = $client->payMethods;
                }
            }
            $payMethodResponse = array();
            foreach ($payMethods as $payMethod) {
                $payment = $payMethod->payment;
                if (!$payment->getSensitiveData()) {
                    $payMethod->delete();
                    continue;
                }
                $response = array("id" => $payMethod->id, "type" => $payMethod->payment_type, "description" => $payMethod->description, "gateway_name" => $payMethod->gateway_name, "contact_type" => $payMethod->contact_type, "contact_id" => $payMethod->contact_id);
                if ($payment instanceof WHMCS\Payment\PayMethod\Adapter\CreditCardModel) {
                    $remoteToken = "";
                    if ($payment->isRemoteCreditCard()) {
                        $remoteToken = $payment->getRemoteToken();
                    }
                    $startDate = "";
                    if ($payment->getStartDate()) {
                        $startDate = $payment->getStartDate()->toCreditCard();
                    }
                    $expiryDate = "";
                    if ($payment->getExpiryDate()) {
                        $expiryDate = $payment->getExpiryDate()->toCreditCard();
                    }
                    $response = array_merge($response, array("card_last_four" => $payment->getLastFour(), "expiry_date" => $expiryDate, "start_date" => $startDate, "issue_number" => $payment->getIssueNumber(), "card_type" => $payment->getCardType(), "remote_token" => $remoteToken));
                } else {
                    $remoteToken = "";
                    if ($payment->isRemoteBankAccount()) {
                        $remoteToken = $payment->getRemoteToken();
                    }
                    $response = array_merge($response, array("bank_name" => $payment->getName(), "remote_token" => $payment->getRemoteToken()));
                }
                $response["last_updated"] = $payMethod->updated_at->toAdminDateTimeFormat();
                $payMethodResponse[] = $response;
            }
            $apiresults = array("result" => "success", "clientid" => $clientId, "paymethods" => $payMethodResponse);
        } catch (Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $apiresults = array("result" => "error", "message" => "Client Not Found");
            return NULL;
        } catch (Exception $e) {
            $apiresults = array("result" => "error", "message" => $e->getMessage());
            return NULL;
        }
    }
}

?>