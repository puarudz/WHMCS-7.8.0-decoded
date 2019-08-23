<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment\Contracts;

interface SensitiveDataInterface
{
    public function getEncryptionKey();
    public function wipeSensitiveData();
    public function getSensitiveDataAttributeName();
    public function getSensitiveProperty($property);
    public function setSensitiveProperty($property, $value);
    public function getSensitiveData();
}

?>