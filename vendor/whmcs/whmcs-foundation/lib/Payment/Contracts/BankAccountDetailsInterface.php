<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment\Contracts;

interface BankAccountDetailsInterface
{
    public function getRoutingNumber();
    public function setRoutingNumber($value);
    public function getAccountNumber();
    public function setAccountNumber($value);
    public function getBankName();
    public function setBankName($value);
    public function getAccountType();
    public function setAccountType($value);
    public function getAccountHolderName();
    public function setAccountHolderName($value);
}

?>