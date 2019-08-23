<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment;

class PaymentServiceProvider extends \WHMCS\Application\Support\ServiceProvider\AbstractServiceProvider
{
    public function register()
    {
        \Illuminate\Database\Eloquent\Relations\Relation::morphMap(array(Contracts\PayMethodTypeInterface::TYPE_BANK_ACCOUNT => "WHMCS\\Payment\\PayMethod\\Adapter\\BankAccount", Contracts\PayMethodTypeInterface::TYPE_REMOTE_BANK_ACCOUNT => "WHMCS\\Payment\\PayMethod\\Adapter\\RemoteBankAccount", Contracts\PayMethodTypeInterface::TYPE_CREDITCARD_LOCAL => "WHMCS\\Payment\\PayMethod\\Adapter\\CreditCard", Contracts\PayMethodTypeInterface::TYPE_CREDITCARD_REMOTE_MANAGED => "WHMCS\\Payment\\PayMethod\\Adapter\\RemoteCreditCard", "Client" => "WHMCS\\User\\Client", "Contact" => "WHMCS\\User\\Client\\Contact"));
    }
}

?>