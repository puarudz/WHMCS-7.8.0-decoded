<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment\Contracts;

interface PayMethodAdapterInterface extends \WHMCS\User\Contracts\ContactAwareInterface, PayMethodTypeInterface, SensitiveDataInterface
{
    public function payMethod();
    public static function factoryPayMethod(\WHMCS\User\Contracts\UserInterface $client, \WHMCS\User\Contracts\ContactInterface $billingContact, $description);
    public function getDisplayName();
}

?>