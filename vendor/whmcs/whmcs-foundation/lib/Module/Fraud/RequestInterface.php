<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Fraud;

interface RequestInterface
{
    public function setLicenseKey($licenseKey);
    public function call($data);
}

?>