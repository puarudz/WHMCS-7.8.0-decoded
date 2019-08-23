<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment\PayMethod\Traits;

trait PayMethodFromRequestTrait
{
    private static function getClient(\WHMCS\Http\Message\ServerRequest $request)
    {
        $clientId = (int) $request->getAttribute("userId");
        $client = \WHMCS\User\Client::find($clientId);
        if (!$client) {
            throw new \RuntimeException("Missing client data");
        }
        return $client;
    }
    public static function getBillingContact(\WHMCS\Http\Message\ServerRequest $request, \WHMCS\User\Client $client)
    {
        $billingContactId = $request->request()->get("billingContactId");
        if ($billingContactId === "client") {
            $billingContact = $client;
        } else {
            $billingContact = $client->contacts()->where("id", $billingContactId)->first();
        }
        if (!$billingContact) {
            throw new \RuntimeException("Invalid billing contact id");
        }
        return $billingContact;
    }
    public static function factoryFromRequest(\WHMCS\Http\Message\ServerRequest $request)
    {
        $post = $request->request();
        $client = self::getClient($request);
        $billingContact = self::getBillingContact($request, $client);
        $description = $post->get("description", "");
        $type = $post->get("payMethodType");
        if (in_array($type, array(\WHMCS\Payment\Contracts\PayMethodTypeInterface::TYPE_CREDITCARD_LOCAL, \WHMCS\Payment\Contracts\PayMethodTypeInterface::TYPE_CREDITCARD_REMOTE_MANAGED))) {
            $payment = self::getCardPayment($request, $client, $billingContact, $description);
        } else {
            if (in_array($type, array(\WHMCS\Payment\Contracts\PayMethodTypeInterface::TYPE_BANK_ACCOUNT, \WHMCS\Payment\Contracts\PayMethodTypeInterface::TYPE_REMOTE_BANK_ACCOUNT))) {
                $payment = self::getBankPayment($request, $client, $billingContact, $description);
            } else {
                throw new \RuntimeException("Invalid pay method type");
            }
        }
        return $payment;
    }
    private static function getBankPayment(\WHMCS\Http\Message\ServerRequest $request, \WHMCS\User\Contracts\UserInterface $client, \WHMCS\User\Contracts\ContactInterface $billingContact, $description = "")
    {
        $post = $request->request();
        $gateway = null;
        $existingPayMethod = $post->get("payMethodId", 0);
        if ($existingPayMethod) {
            $payMethod = \WHMCS\Payment\PayMethod\Model::find($existingPayMethod);
            if (!$payMethod || $payMethod->client->id !== $client->id) {
                throw new \RuntimeException("Pay method ID is not associated with client ID");
            }
            $gateway = $payMethod->getGateway();
        } else {
            $storage = $post->get("storage", $post->get("storageGateway", "local"));
            $class = "WHMCS\\Payment\\PayMethod\\Adapter\\BankAccount";
            if ($storage === "local") {
                $resolver = new \WHMCS\Gateways();
                if (!$resolver->isLocalBankAccountGatewayAvailable()) {
                    throw new \RuntimeException("No compatible gateways are active.");
                }
            } else {
                $gateways = (new \WHMCS\Gateways())->getAvailableGatewayInstances(true);
                if (array_key_exists($storage, $gateways)) {
                    $gateway = $gateways[$storage];
                    $class = "WHMCS\\Payment\\PayMethod\\Adapter\\RemoteBankAccount";
                } else {
                    throw new \RuntimeException("Selected gateway is unavailable.");
                }
            }
            $payMethod = $class::factoryPayMethod($client, $billingContact, $description);
        }
        if ($gateway) {
            $payMethod->setGateway($gateway);
            $payMethod->save();
        }
        $payment = $payMethod->payment;
        if ($payMethod->getType() === \WHMCS\Payment\Contracts\PayMethodTypeInterface::TYPE_BANK_ACCOUNT) {
            $payment->setAccountType($post->get("bankaccttype"));
            $payment->setBankName($post->get("bankname"));
            $payment->setAccountHolderName($post->get("bankacctholdername"));
            $payment->setRoutingNumber($post->get("bankroutingnum"));
            $payment->setAccountNumber($post->get("bankacctnum"));
        }
        return $payment;
    }
    private static function getCardPayment(\WHMCS\Http\Message\ServerRequest $request, \WHMCS\User\Contracts\UserInterface $client, \WHMCS\User\Contracts\ContactInterface $billingContact, $description = "")
    {
        include_once ROOTDIR . "/includes/ccfunctions.php";
        $post = $request->request();
        $gateway = null;
        $existingPayMethod = $post->get("payMethodId", 0);
        if ($existingPayMethod) {
            $payMethod = \WHMCS\Payment\PayMethod\Model::find($existingPayMethod);
            if (!$payMethod || $payMethod->client->id !== $client->id) {
                throw new \RuntimeException("Pay method ID is not associated with client ID");
            }
            $gateway = $payMethod->getGateway();
        } else {
            $storage = $post->get("storage", $post->get("storageGateway", "local"));
            $class = "WHMCS\\Payment\\PayMethod\\Adapter\\CreditCard";
            if ($storage === "local") {
                $resolver = new \WHMCS\Gateways();
                if (!$resolver->isLocalCreditCardStorageEnabled(false)) {
                    throw new \RuntimeException("No compatible gateways are active.");
                }
            } else {
                $gateways = (new \WHMCS\Gateways())->getAvailableGatewayInstances(true);
                if (array_key_exists($storage, $gateways)) {
                    $gateway = $gateways[$storage];
                    $class = "WHMCS\\Payment\\PayMethod\\Adapter\\RemoteCreditCard";
                } else {
                    throw new \RuntimeException("Selected gateway is unavailable.");
                }
            }
            $payMethod = $class::factoryPayMethod($client, $billingContact, $description);
        }
        if ($gateway) {
            $payMethod->setGateway($gateway);
            $payMethod->save();
        }
        $expiry = null;
        if ($post->has("ccexpirydate")) {
            try {
                $expiry = \WHMCS\Carbon::createFromCcInput($post->get("ccexpirydate"));
            } catch (\Exception $e) {
            }
        }
        $payment = $payMethod->payment;
        if (!$existingPayMethod) {
            $cardNumber = $post->get("ccnumber", "");
            $cardCvv = $post->get("cardcvv", "");
            $payment->setCardNumber($cardNumber);
            $payment->setCardCvv($cardCvv);
            if (!$expiry) {
                $expiry = \WHMCS\Carbon::fromCreditCard($post->get("ccexpirymonth", "01") . "/" . $post->get("ccexpiryyear", "28"));
            }
        } else {
            if (!$expiry) {
                $defaultMonth = $payment->getExpiryDate() ? $payment->getExpiryDate()->format("m") : "01";
                $defaultYear = $payment->getExpiryDate() ? $payment->getExpiryDate()->format("m") : "28";
                $expiry = \WHMCS\Carbon::fromCreditCard($post->get("ccexpirymonth", $defaultMonth) . $post->get("ccexpiryyear", $defaultYear));
            }
        }
        if ($expiry) {
            $payment->setExpiryDate($expiry);
        }
        $startDate = null;
        $ccStartMonth = $post->get("ccstartmonth");
        $ccStartYear = $post->get("ccstartyear");
        $ccStartDate = $post->get("ccstartdate");
        try {
            if ($ccStartDate) {
                $startDate = \WHMCS\Carbon::createFromCcInput($ccStartDate);
            } else {
                if ($ccStartMonth && $ccStartYear) {
                    $startDate = \WHMCS\Carbon::createFromCcInput($ccStartMonth . $ccStartYear);
                }
            }
        } catch (\Exception $e) {
        }
        if ($startDate) {
            $payment->setStartDate($startDate);
        }
        $issueNumber = $post->get("ccissuenum");
        if ($issueNumber) {
            $payment->setIssueNumber($issueNumber);
        }
        return $payment;
    }
}

?>