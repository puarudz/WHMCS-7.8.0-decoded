<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment;

class PaymentController
{
    public function confirm(\WHMCS\Http\Message\ServerRequest $request)
    {
        $token = $request->get("token");
        check_token("WHMCS.default", $token);
        $gatewayName = $request->get("gateway");
        $gateway = new \WHMCS\Module\Gateway();
        if (!$gatewayName) {
            return new \WHMCS\Http\Message\JsonResponse(array("warning" => "Invalid Request"));
        }
        if (!$gateway->load($gatewayName)) {
            return new \WHMCS\Http\Message\JsonResponse(array("warning" => "Module Not Active"));
        }
        $remoteStorageToken = \WHMCS\Session::getAndDelete($gatewayName . "Confirm");
        if (!$remoteStorageToken) {
            return new \WHMCS\Http\Message\JsonResponse(array("warning" => "Invalid Request"));
        }
        if (!$gateway->functionExists("remote_input_confirm")) {
            return new \WHMCS\Http\Message\JsonResponse(array("warning" => "Unsupported Request"));
        }
        $result = $gateway->call("remote_input_confirm", array("remoteStorageToken" => $remoteStorageToken));
        if (array_key_exists("warning", $result) && $result["warning"]) {
            return new \WHMCS\Http\Message\JsonResponse(array("warning" => $result["warning"]));
        }
        $client = \WHMCS\User\Client::find(\WHMCS\Session::get("uid"));
        $payMethod = PayMethod\Adapter\RemoteCreditCard::factoryPayMethod($client, $client->billingContact);
        $payment = $payMethod->payment;
        $payMethod->setGateway($gateway);
        $payment->setCardNumber($result["cardNumber"])->setExpiryDate(\WHMCS\Carbon::createFromCcInput($result["cardExpiry"]))->setRemoteToken($result["remoteStorageToken"])->save();
        $payMethod->save();
        \WHMCS\Session::set("payMethodCreateSuccess", true);
        return new \WHMCS\Http\Message\JsonResponse(array("success" => true, "redirect" => $result["redirect"]));
    }
    public function update(\WHMCS\Http\Message\ServerRequest $request)
    {
        $token = $request->get("token");
        check_token("WHMCS.default", $token);
        $gatewayName = $request->get("gateway");
        $payMethodId = $request->get("pay_method_id");
        $gateway = new \WHMCS\Module\Gateway();
        if (!$gatewayName) {
            return new \WHMCS\Http\Message\JsonResponse(array("warning" => "Invalid Request"));
        }
        if (!$gateway->load($gatewayName)) {
            return new \WHMCS\Http\Message\JsonResponse(array("warning" => "Module Not Active"));
        }
        $remoteStorageToken = \WHMCS\Session::getAndDelete($gatewayName . "Confirm");
        if (!$remoteStorageToken || !$payMethodId) {
            return new \WHMCS\Http\Message\JsonResponse(array("warning" => "Invalid Request"));
        }
        $payMethod = PayMethod\Model::find($payMethodId);
        if ($payMethod->gateway_name != $gatewayName) {
            return new \WHMCS\Http\Message\JsonResponse(array("warning" => "Invalid Request"));
        }
        if (!$gateway->functionExists("remote_input_confirm")) {
            return new \WHMCS\Http\Message\JsonResponse(array("warning" => "Unsupported Request"));
        }
        $result = $gateway->call("remote_input_confirm", array("remoteStorageToken" => $remoteStorageToken));
        if (array_key_exists("warning", $result) && $result["warning"]) {
            return new \WHMCS\Http\Message\JsonResponse(array("warning" => $result["warning"]));
        }
        $payment = $payMethod->payment;
        $payMethod->setGateway($gateway);
        $payment->setCardNumber($result["cardNumber"])->setExpiryDate(\WHMCS\Carbon::createFromCcInput($result["cardExpiry"]))->setRemoteToken($result["remoteStorageToken"])->save();
        $payMethod->save();
        \WHMCS\Session::set("payMethodSaveSuccess", true);
        return new \WHMCS\Http\Message\JsonResponse(array("success" => true, "redirect" => ""));
    }
}

?>