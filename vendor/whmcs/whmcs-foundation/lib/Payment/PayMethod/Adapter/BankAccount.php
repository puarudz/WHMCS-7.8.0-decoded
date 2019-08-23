<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment\PayMethod\Adapter;

class BankAccount extends BankAccountModel implements \WHMCS\Payment\Contracts\BankAccountDetailsInterface
{
    use \WHMCS\Payment\PayMethod\Traits\BankAccountDetailsTrait;
}

?>