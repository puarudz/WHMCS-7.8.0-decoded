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
$payMethodId = (int) App::getFromRequest("paymethodid");
$clientId = (int) App::getFromRequest("clientid");
if (!$clientId) {
    $apiresults = array("result" => "error", "message" => "Client ID is Required");
} else {
    if (!$payMethodId) {
        $apiresults = array("result" => "error", "message" => "Pay Method ID is Required");
    } else {
        try {
            $payMethod = WHMCS\Payment\PayMethod\Model::findOrFail($payMethodId);
        } catch (Exception $e) {
            $apiresults = array("result" => "error", "message" => "Invalid Pay Method ID");
            return NULL;
        }
        if ($payMethod->userid != $clientId) {
            $apiresults = array("result" => "error", "message" => "Pay Method does not belong to passed Client ID");
        } else {
            $payment = $payMethod->payment;
            try {
                if ($payment->isRemoteBankAccount() || $payment->isRemoteCreditCard()) {
                    $payment->deleteRemote();
                }
                $payMethod->delete();
            } catch (Exception $e) {
                $apiresults = array("result" => "error", "message" => "Error Deleting Remote Pay Method: " . $e->getMessage());
                return NULL;
            }
            $apiresults = array("result" => "success", "paymethodid" => $payMethodId);
        }
    }
}

?>