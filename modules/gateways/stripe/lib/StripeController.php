<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Gateway\Stripe;

class StripeController
{
    public function intent(\WHMCS\Http\Message\ServerRequest $request)
    {
        $token = $request->get("token");
        check_token("WHMCS.default", $token);
        $paymentMethodId = $request->get("payment_method_id");
        $gateway = new \WHMCS\Module\Gateway();
        if (!$gateway->load("stripe")) {
            return new \WHMCS\Http\Message\JsonResponse(array("warning" => "Module Not Active"));
        }
        stripe_start_stripe($gateway->getParams());
        $invoiceId = $request->get("invoiceid");
        $stripeCustomer = null;
        $client = null;
        $method = null;
        if ($paymentMethodId) {
            try {
                $method = \Stripe\PaymentMethod::retrieve($paymentMethodId);
                if ($method->customer) {
                    $stripeCustomer = \Stripe\Customer::retrieve($method->customer);
                }
            } catch (\Exception $e) {
            }
        }
        if (!$stripeCustomer && $invoiceId) {
            $invoice = \WHMCS\Billing\Invoice::with("client")->find($invoiceId);
            $sessionUser = \WHMCS\Session::get("uid");
            if ($sessionUser != $invoice->clientId) {
                throw new \InvalidArgumentException("Invalid Access Attempt");
            }
            $client = $invoice->client;
        }
        $clientId = null;
        $errorMessage = null;
        if (!$client) {
            $clientId = \WHMCS\Session::get("uid");
        }
        if (!$clientId) {
            if (!function_exists("validateClientLogin")) {
                require_once ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "clientfunctions.php";
            }
            $newOrExisting = \App::getFromRequest("custtype");
            if ($newOrExisting === "existing") {
                $loginemail = \App::getFromRequest("loginemail");
                $loginpw = \WHMCS\Input\Sanitize::decode(\App::getFromRequest("loginpw"));
                if (!$loginpw) {
                    $loginpw = \WHMCS\Input\Sanitize::decode(\App::getFromRequest("loginpassword"));
                }
                if (!validateClientLogin($loginemail, $loginpw)) {
                    $errorMessage = \Lang::trans("loginincorrect");
                }
                $clientId = (int) \WHMCS\Session::get("uid");
            } else {
                $whmcs = \App::self();
                $errorMessage = checkDetailsareValid("", true, true, false);
            }
        }
        if ($clientId) {
            $client = \WHMCS\User\Client::find($clientId);
            if (\App::isInRequest("billingcontact")) {
                $billingContactId = \App::getFromRequest("billingcontact");
                if ($billingContactId === "new") {
                    if (!function_exists("checkDetailsareValid")) {
                        require_once ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "clientfunctions.php";
                    }
                    $errorMessage = checkDetailsareValid($clientId, false, false, false, false);
                }
            }
        }
        if ($errorMessage) {
            $response = array("warning" => $errorMessage);
            return new \WHMCS\Http\Message\JsonResponse($response);
        }
        if ($client && !$stripeCustomer) {
            $payMethod = $client->payMethods()->where("gateway_name", "stripe")->first();
            $gatewayId = "";
            if ($payMethod) {
                $gatewayId = $payMethod->payment->getRemoteToken();
            }
            if ($client->billingContactId) {
                $client = $client->billingContact;
            }
            if ($gatewayId) {
                $jsonCheck = json_decode(\WHMCS\Input\Sanitize::decode($gatewayId), true);
                if (is_array($jsonCheck) && array_key_exists("customer", $jsonCheck)) {
                    $stripeCustomer = \Stripe\Customer::retrieve($jsonCheck["customer"]);
                    if (!$paymentMethodId) {
                        $paymentMethodId = $jsonCheck["method"];
                    }
                } else {
                    if (substr($gatewayId, 0, 3) == "cus") {
                        $stripeCustomer = \Stripe\Customer::retrieve($gatewayId);
                    }
                }
            }
            $clientId = $client->id;
            if ($client instanceof \WHMCS\User\Client\Contact) {
                $clientId = $client->clientId;
            }
            try {
                $method = \Stripe\PaymentMethod::retrieve($paymentMethodId);
            } catch (\Exception $e) {
                return new \WHMCS\Http\Message\JsonResponse(array("warning" => $e->getMessage()));
            }
        }
        if (!$stripeCustomer && $client) {
            $stripeCustomer = \Stripe\Customer::create(array("description" => "Customer for " . $client->fullName . " (" . $client->email . ")", "email" => $client->email, "metadata" => array("id" => $clientId, "fullName" => $client->fullName, "email" => $client->email)));
        } else {
            if (!$stripeCustomer && !$client) {
                $name = \App::getFromRequest("firstname") . " " . \App::getFromRequest("lastname");
                $email = \App::getFromRequest("email");
                if (!trim($name) || !$email) {
                    $response = array("warning" => "Name and Email are required to pay with this gateway");
                    return new \WHMCS\Http\Message\JsonResponse($response);
                }
                $stripeCustomer = \Stripe\Customer::create(array("description" => "Customer for " . $name . " (" . $client->email . ")", "email" => $email, "metadata" => array("fullName" => $name, "email" => $email)));
            }
        }
        if (!$method->customer) {
            try {
                $method = $method->attach(array("customer" => $stripeCustomer->id));
                $method->save();
            } catch (\Exception $e) {
                $response = array("warning" => $e->getMessage());
                return new \WHMCS\Http\Message\JsonResponse($response);
            }
        }
        $methodId = $method->id;
        if (substr($methodId, 0, 4) !== "card") {
            if ($client) {
                \Stripe\PaymentMethod::update($method->id, array("billing_details" => array("email" => $client->email, "name" => $client->fullName, "address" => array("line1" => _stripe_formatValue($client->address1), "line2" => _stripe_formatValue($client->address2), "city" => _stripe_formatValue($client->city), "state" => _stripe_formatValue($client->state), "country" => _stripe_formatValue($client->country), "postal_code" => _stripe_formatValue($client->postcode))), "metadata" => array("id" => $clientId, "fullName" => $client->fullName, "email" => $client->email)));
            } else {
                $method = \Stripe\PaymentMethod::update($method->id, array("billing_details" => array("email" => $email, "name" => $name, "address" => array("line1" => _stripe_formatValue(\App::getFromRequest("address1")), "line2" => _stripe_formatValue(\App::getFromRequest("address2")), "city" => _stripe_formatValue(\App::getFromRequest("city")), "state" => _stripe_formatValue(\App::getFromRequest("state")), "country" => _stripe_formatValue(\App::getFromRequest("country")), "postal_code" => _stripe_formatValue(\App::getFromRequest("postcode")))), "metadata" => array("fullName" => $name, "email" => $email)));
            }
        }
        try {
            $intentsData = \WHMCS\Session::get("StripeIntentsData");
            if (!is_array($intentsData)) {
                throw new \InvalidArgumentException("Invalid or Missing Payment Information - Please Reload and Try Again");
            }
            $intentsData["confirmation_method"] = "automatic";
            $intentsData["confirm"] = true;
            $intentsData["customer"] = $stripeCustomer->id;
            $intentsData["payment_method"] = $method->id;
            $intentsData["save_payment_method"] = true;
            $intent = \Stripe\PaymentIntent::create($intentsData);
        } catch (\Exception $e) {
            return new \WHMCS\Http\Message\JsonResponse(array("warning" => $e->getMessage()));
        }
        \WHMCS\Session::set("StripePaymentMethod", $method->id);
        if (in_array($intent->status, array("requires_source_action", "requires_action", "requires_capture"))) {
            $response = array("requires_action" => true, "success" => false, "token" => $intent->client_secret);
        } else {
            if ($intent->status == "succeeded") {
                $response = array("success" => true, "requires_action" => false, "token" => $intent->id);
            } else {
                $response = array("warning" => "Invalid PaymentIntent status");
            }
        }
        return new \WHMCS\Http\Message\JsonResponse($response);
    }
    public function setupIntent(\WHMCS\Http\Message\ServerRequest $request)
    {
        $token = $request->get("token");
        check_token("WHMCS.default", $token);
        $userId = \WHMCS\Session::get("uid");
        if (!$userId) {
            return new \WHMCS\Http\Message\JsonResponse(array("warning" => "Login session not found"));
        }
        $gateway = new \WHMCS\Module\Gateway();
        if (!$gateway->load("stripe")) {
            return new \WHMCS\Http\Message\JsonResponse(array("warning" => "Module Not Active"));
        }
        stripe_start_stripe($gateway->getParams());
        $setupIntent = \Stripe\SetupIntent::create();
        return new \WHMCS\Http\Message\JsonResponse(array("success" => true, "setup_intent" => $setupIntent->client_secret));
    }
    public function add(\WHMCS\Http\Message\ServerRequest $request)
    {
        $token = $request->get("token");
        check_token("WHMCS.default", $token);
        return $this->addProcess($request, true);
    }
    public function adminAdd(\WHMCS\Http\Message\ServerRequest $request)
    {
        return $this->addProcess($request);
    }
    protected function addProcess(\WHMCS\Http\Message\ServerRequest $request, $sessionUserId = false)
    {
        $paymentMethodId = $request->get("payment_method_id");
        $userId = (int) $request->get("user_id");
        if ($sessionUserId) {
            $userId = \WHMCS\Session::get("uid");
        }
        if (!$userId) {
            $error = "User Id not found in request params";
            if ($sessionUserId) {
                $error = "Login session not found";
            }
            return new \WHMCS\Http\Message\JsonResponse(array("warning" => $error));
        }
        $gateway = new \WHMCS\Module\Gateway();
        if (!$gateway->load("stripe")) {
            return new \WHMCS\Http\Message\JsonResponse(array("warning" => "Module Not Active"));
        }
        stripe_start_stripe($gateway->getParams());
        try {
            $client = \WHMCS\User\Client::findOrFail($userId);
            $existingMethod = stripe_findFirstCustomerToken($client);
            $stripeCustomer = null;
            $gatewayId = $client->paymentGatewayToken;
            if ($client->billingContactId) {
                $client = $client->billingContact;
            }
            if ($gatewayId) {
                $jsonCheck = json_decode(\WHMCS\Input\Sanitize::decode($gatewayId), true);
                if (is_array($jsonCheck) && array_key_exists("customer", $jsonCheck)) {
                    $stripeCustomer = \Stripe\Customer::retrieve($jsonCheck["customer"]);
                } else {
                    if (substr($gatewayId, 0, 3) == "cus") {
                        $stripeCustomer = \Stripe\Customer::retrieve($gatewayId);
                    }
                }
            }
            if (!$stripeCustomer && $existingMethod && is_array($existingMethod) && array_key_exists("customer", $existingMethod)) {
                $stripeCustomer = \Stripe\Customer::retrieve($existingMethod["customer"]);
            }
            if (!$stripeCustomer) {
                $stripeCustomer = \Stripe\Customer::create(array("description" => "Customer for " . $client->fullName . " (" . $client->email . ")", "email" => $client->email, "metadata" => array("id" => $userId, "fullName" => $client->fullName, "email" => $client->email)));
            }
            $method = \Stripe\PaymentMethod::retrieve($paymentMethodId);
            if (!$method->customer) {
                $method->attach(array("customer" => $stripeCustomer->id));
            }
            \Stripe\PaymentMethod::update($method->id, array("billing_details" => array("email" => $client->email, "name" => $client->fullName, "address" => array("line1" => _stripe_formatValue($client->address1), "line2" => _stripe_formatValue($client->address2), "city" => _stripe_formatValue($client->city), "state" => _stripe_formatValue($client->state), "country" => _stripe_formatValue($client->country), "postal_code" => _stripe_formatValue($client->postcode))), "metadata" => array("id" => $userId, "fullName" => $client->fullName, "email" => $client->email)));
            \WHMCS\Session::set("StripePaymentMethod", $method->id);
            $response = array("success" => true, "requires_action" => false, "token" => $method->id);
        } catch (\Exception $e) {
            $response = array("warning" => $e->getMessage());
        }
        return new \WHMCS\Http\Message\JsonResponse($response);
    }
    public function get(\WHMCS\Http\Message\ServerRequest $request)
    {
        $token = $request->get("token");
        check_token("WHMCS.default", $token);
        $gateway = new \WHMCS\Module\Gateway();
        if (!$gateway->load("stripe")) {
            return new \WHMCS\Http\Message\JsonResponse(array("warning" => "Module Not Active"));
        }
        try {
            $payMethodId = $request->get("paymethod_id", 0);
            if (!$payMethodId) {
                throw new \InvalidArgumentException("Invalid Request: Missing Payment ID");
            }
            $userId = (int) \WHMCS\Session::get("uid");
            if (!$userId) {
                throw new \InvalidArgumentException("Invalid Request: Logged In User Required");
            }
            $payMethod = \WHMCS\Payment\PayMethod\Model::find($payMethodId);
            if (!$payMethod) {
                throw new \InvalidArgumentException("Invalid Request: Invalid Payment ID");
            }
            if ($payMethod->userid != $userId) {
                throw new \InvalidArgumentException("Invalid Access Attempt");
            }
            if ($payMethod->gateway_name != "stripe") {
                throw new \InvalidArgumentException("Invalid PayMethod for Gateway");
            }
            if (!$payMethod->payment instanceof \WHMCS\Payment\PayMethod\Adapter\RemoteCreditCard) {
                throw new \InvalidArgumentException("Invalid PayMethod for Gateway");
            }
            $remoteToken = stripe_parseGatewayToken($payMethod->payment->getRemoteToken());
            if (count($remoteToken) < 2) {
                throw new \InvalidArgumentException("Invalid Remote Token");
            }
            return new \WHMCS\Http\Message\JsonResponse(array("success" => true, "token" => $remoteToken["method"]));
        } catch (\Exception $e) {
            return new \WHMCS\Http\Message\JsonResponse(array("warning" => $e->getMessage()));
        }
    }
}

?>