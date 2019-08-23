<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Gateway\GoCardless\Resources;

class Mandates extends AbstractResource
{
    const STATUSES = array("pending_customer_approval" => "Pending Customer Approval", "pending_submission" => "Pending Submission", "submitted" => "Submitted", "active" => "Active", "failed" => "Failed", "cancelled" => "Cancelled", "expired" => "Expired");
    public function cancelled(array $event)
    {
        $mandateId = $event["links"]["mandate"];
        $client = $this->getClientFromMandate($mandateId);
        if ($client) {
            $payMethod = $client->payMethods()->where("gateway_name", "gocardless")->first();
            if ($payMethod && $payMethod->payment->isRemoteBankAccount() && $payMethod->payment->getRemoteToken() == $mandateId) {
                $payMethod->delete();
            }
        }
    }
    public function created(array $event)
    {
        $mandateId = $event["links"]["mandate"];
        $client = $this->getClientFromMandate($mandateId);
        if (!$client) {
            logTransaction($this->params["paymentmethod"], $event, "No Client Found for Mandate", $this->params);
        } else {
            $payMethod = $client->payMethods()->where("gateway_name", "gocardless")->first();
            if (!$payMethod) {
                $payMethod = \WHMCS\Payment\PayMethod\Adapter\RemoteBankAccount::factoryPayMethod($client, $client);
            }
            $payMethod->payment->setRemoteToken($mandateId);
            $payMethod->payment->save();
            try {
                $this->client->put("mandates/" . $mandateId, array("json" => array("mandates" => array("metadata" => array("client_id" => (string) (string) $client->id)))));
            } catch (\Exception $e) {
            }
        }
    }
    public function failed(array $event)
    {
        $this->cancelled($event);
    }
    public function reinstated(array $event)
    {
        $mandateId = $event["links"]["mandate"];
        $client = $this->getClientFromMandate($mandateId);
        try {
            $payMethod = $client->payMethods()->where("gateway_name", "gocardless")->first();
            if (!$payMethod) {
                $payMethod = \WHMCS\Payment\PayMethod\Adapter\RemoteBankAccount::factoryPayMethod($client, $client);
            }
            $payMethod->payment->setRemoteToken($mandateId);
            $payMethod->payment->save();
        } catch (\Exception $e) {
        }
    }
    public function replaced(array $event)
    {
        $mandateId = $event["links"]["mandate"];
        $client = $this->getClientFromMandate($mandateId);
        if ($client) {
            $newMandateId = $event["links"]["new_mandate"];
            $payMethod = $client->payMethods()->where("gateway_name", "gocardless")->first();
            if (!$payMethod) {
                $payMethod = \WHMCS\Payment\PayMethod\Adapter\RemoteBankAccount::factoryPayMethod($client, $client);
            }
            $payMethod->payment->setRemoteToken($newMandateId);
            $payMethod->payment->save();
        }
    }
    public function defaultAction(array $event)
    {
        logTransaction($this->params["paymentmethod"], $event, "Mandate Notification", $this->params);
    }
    protected function getClientFromMandate($mandateId)
    {
        $client = null;
        try {
            $response = json_decode($this->client->get("mandates/" . $mandateId), true);
            if (array_key_exists("metadata", $response) && array_key_exists("client_id", $response["metadata"]) && $response["metadata"]["client_id"]) {
                $clientId = $response["mandates"]["metadata"]["client_id"];
                $client = \WHMCS\User\Client::find($clientId);
            }
            if (!$client) {
                $customerId = $response["links"]["customer"];
                $response = json_decode($this->client->get("customers/" . $customerId), true);
                $email = $response["customers"]["email"];
                $client = \WHMCS\User\Client::where("email", $email)->first();
            }
        } catch (\Exception $e) {
            return $client;
        }
        return $client;
    }
}

?>