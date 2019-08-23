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
$type = strtolower(App::getFromRequest("type"));
$description = App::getFromRequest("description");
$default = (int) App::getFromRequest("set_as_default");
if (!$clientId) {
    $apiresults = array("result" => "error", "message" => "Client ID Is Required");
} else {
    try {
        $client = WHMCS\User\Client::findOrFail($clientId);
    } catch (Exception $e) {
        $apiresults = array("result" => "error", "message" => "Invalid Client ID");
        return NULL;
    }
    if (!$type) {
        $type = strtolower(WHMCS\Payment\PayMethod\Model::TYPE_CREDITCARD_LOCAL);
    }
    if (in_array($type, array(strtolower(WHMCS\Payment\PayMethod\Model::TYPE_BANK_ACCOUNT), strtolower(WHMCS\Payment\PayMethod\Model::TYPE_CREDITCARD_LOCAL), strtolower(WHMCS\Payment\PayMethod\Model::TYPE_CREDITCARD_REMOTE_MANAGED)))) {
        $apiresults = array("result" => "error", "message" => "Invalid Pay Method Type. " . "Type should be one of '" . WHMCS\Payment\PayMethod\Model::TYPE_BANK_ACCOUNT . "'," . " '" . WHMCS\Payment\PayMethod\Model::TYPE_CREDITCARD_LOCAL . "'," . " or '" . WHMCS\Payment\PayMethod\Model::TYPE_CREDITCARD_REMOTE_MANAGED . "'");
    } else {
        $gateway = App::getFromRequest("gateway_module_name");
        if (!$gateway && $type == strtolower(WHMCS\Payment\PayMethod\Model::TYPE_REMOTE_BANK_ACCOUNT)) {
            $apiresults = array("result" => "error", "message" => "Gateway is Required for RemoteCreditCard type");
        } else {
            if ($gateway) {
                $gatewayInterface = new WHMCS\Module\Gateway();
                if (!$gatewayInterface->load($gateway)) {
                    $gateways = $gatewayInterface->getActiveGateways();
                    $apiresults = array("result" => "error", "message" => "Invalid Gateway Module Name. Must be one of: " . implode(", ", $gateways));
                    return NULL;
                }
                $workFlowType = $gatewayInterface->getWorkflowType();
            }
            $billingContact = $client->billingContact;
            if (!$billingContact) {
                $billingContact = $client;
            }
            if (in_array($type, array(strtolower(WHMCS\Payment\PayMethod\Model::TYPE_CREDITCARD_LOCAL), strtolower(WHMCS\Payment\PayMethod\Model::TYPE_CREDITCARD_REMOTE_MANAGED)))) {
                if (!$workFlowType) {
                    $workFlowType = WHMCS\Module\Gateway::WORKFLOW_MERCHANT;
                }
                $cardNumber = App::getFromRequest("card_number");
                $expiryDate = App::getFromRequest("card_expiry");
                $startDate = App::getFromRequest("card_start");
                $issueNumber = App::getFromRequest("card_issue_number");
                if (!$cardNumber) {
                    $apiresults = array("result" => "error", "message" => "Card Number is required for '" . WHMCS\Payment\PayMethod\Model::TYPE_CREDITCARD_LOCAL . "'," . " or '" . WHMCS\Payment\PayMethod\Model::TYPE_CREDITCARD_REMOTE_MANAGED . "' type");
                    return NULL;
                }
                if (!$expiryDate) {
                    $apiresults = array("result" => "error", "message" => "Expiry Date is required for '" . WHMCS\Payment\PayMethod\Model::TYPE_CREDITCARD_LOCAL . "'," . " or '" . WHMCS\Payment\PayMethod\Model::TYPE_CREDITCARD_REMOTE_MANAGED . "' type");
                    return NULL;
                }
                try {
                    $expiryDate = WHMCS\Carbon::createFromCcInput($expiryDate);
                } catch (Exception $e) {
                    $apiresults = array("result" => "error", "message" => "Expiry Date is invalid");
                    return NULL;
                }
                if ($startDate) {
                    try {
                        $startDate = WHMCS\Carbon::createFromCcInput($startDate);
                    } catch (Exception $e) {
                        $apiresults = array("result" => "error", "message" => "Start Date is invalid");
                        return NULL;
                    }
                }
                if ($issueNumber && !is_numeric($issueNumber)) {
                    $apiresults = array("result" => "error", "message" => "Issue Number is invalid");
                    return NULL;
                }
                switch ($workFlowType) {
                    case WHMCS\Module\Gateway::WORKFLOW_TOKEN:
                        $payMethod = WHMCS\Payment\PayMethod\Adapter\RemoteCreditCard::factoryPayMethod($client, $billingContact, $description);
                        $payMethod->setGateway($gatewayInterface);
                        if ($default) {
                            $payMethod->setAsDefaultPayMethod();
                        }
                        $payMethod->save();
                        $newPayment = $payMethod->payment;
                        $newPayment->setCardNumber($cardNumber);
                        $newPayment->setExpiryDate($expiryDate);
                        if ($startDate) {
                            $newPayment->setStartDate($startDate);
                        }
                        if ($issueNumber) {
                            $newPayment->setIssueNumber($issueNumber);
                        }
                        try {
                            $newPayment->createRemote()->save();
                        } catch (Exception $e) {
                            $apiresults = array("result" => "error", "message" => "Error Creating Remote Token: " . $e->getMessage());
                            return NULL;
                        }
                        break;
                    case WHMCS\Module\Gateway::WORKFLOW_MERCHANT:
                        $payMethod = WHMCS\Payment\PayMethod\Adapter\CreditCard::factoryPayMethod($client, $billingContact, $description);
                        if ($default) {
                            $payMethod->setAsDefaultPayMethod();
                        }
                        $payMethod->save();
                        $newPayment = $payMethod->payment;
                        $newPayment->setCardNumber($cardNumber);
                        $newPayment->setExpiryDate($expiryDate);
                        if ($startDate) {
                            $newPayment->setStartDate($startDate);
                        }
                        if ($issueNumber) {
                            $newPayment->setIssueNumber($issueNumber);
                        }
                        $newPayment->save();
                        break;
                    case WHMCS\Module\Gateway::WORKFLOW_ASSISTED:
                    case WHMCS\Module\Gateway::WORKFLOW_NOLOCALCARDINPUT:
                    case WHMCS\Module\Gateway::WORKFLOW_REMOTE:
                    default:
                        $apiresults = array("result" => "error", "message" => "Unsupported Gateway Type for Storage");
                        return NULL;
                }
            } else {
                $bankName = App::getFromRequest("bank_name");
                $acctType = App::getFromRequest("bank_account_type");
                $bankCode = App::getFromRequest("bank_code");
                $bankAccountNumber = App::getFromRequest("bank_account");
                $payMethod = WHMCS\Payment\PayMethod\Adapter\BankAccount::factoryPayMethod($client, $billingContact, $description);
                if ($default) {
                    $payMethod->setAsDefaultPayMethod();
                }
                $payMethod->save();
                $newPayment = $payMethod->payment;
                try {
                    $newPayment->setAccountType($acctType)->setAccountHolderName($billingContact->firstName . " " . $billingContact->lastName)->setBankName($bankName)->setRoutingNumber($bankCode)->setAccountNumber($bankAccountNumber)->validateRequiredValuesPreSave()->save();
                } catch (Exception $e) {
                    $apiresults = array("result" => "error", "message" => $e->getMessage());
                    return NULL;
                }
            }
            $apiresults = array("result" => "success", "clientid" => $client->id, "paymethodid" => $payMethod->id);
        }
    }
}

?>