<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Stripe\Error;

class SignatureVerification extends Base
{
    public function __construct($message, $sigHeader, $httpBody = null)
    {
        parent::__construct($message, null, $httpBody, null, null);
        $this->sigHeader = $sigHeader;
    }
    public function getSigHeader()
    {
        return $this->sigHeader;
    }
}

?>