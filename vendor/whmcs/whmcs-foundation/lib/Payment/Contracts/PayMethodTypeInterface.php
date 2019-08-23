<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment\Contracts;

interface PayMethodTypeInterface
{
    const TYPE_BANK_ACCOUNT = "BankAccount";
    const TYPE_REMOTE_BANK_ACCOUNT = "RemoteBankAccount";
    const TYPE_CREDITCARD_LOCAL = "CreditCard";
    const TYPE_CREDITCARD_REMOTE_MANAGED = "RemoteCreditCard";
    const TYPE_CREDITCARD_REMOTE_UNMANAGED = "PayToken";
    public function getType($instance);
    public function getTypeDescription($instance);
    public function isManageable();
    public function isCreditCard();
    public function isLocalCreditCard();
    public function isRemoteCreditCard();
    public function isBankAccount();
    public function isRemoteBankAccount();
}

?>