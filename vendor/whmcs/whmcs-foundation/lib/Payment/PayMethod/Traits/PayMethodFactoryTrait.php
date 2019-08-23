<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment\PayMethod\Traits;

trait PayMethodFactoryTrait
{
    public static function factoryPayMethod(\WHMCS\User\Contracts\UserInterface $client, \WHMCS\User\Contracts\ContactInterface $billingContact = NULL, $description = "")
    {
        $payment = new static();
        $payment->save();
        return $payment->newPayMethod($client, $billingContact, $description);
    }
    public function newPayMethod(\WHMCS\User\Contracts\UserInterface $client, \WHMCS\User\Contracts\ContactInterface $billingContact = NULL, $description = "")
    {
        $payMethod = new \WHMCS\Payment\PayMethod\Model();
        $payMethod->description = $description;
        $payMethod->order_preference = \WHMCS\Payment\PayMethod\Model::totalPayMethodsOnFile($client);
        if (!$billingContact) {
            $billingContact = $client->defaultBillingContact;
        }
        $payMethod->save();
        $payMethod->contact()->associate($billingContact);
        $payMethod->client()->associate($client);
        $payMethod->payment()->associate($this);
        $this->pay_method_id = $payMethod->id;
        $payMethod->push();
        return $payMethod;
    }
}

?>