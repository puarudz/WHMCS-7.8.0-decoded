<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Stripe\Error;

class InvalidRequest extends Base
{
    public function __construct($message, $stripeParam, $httpStatus = null, $httpBody = null, $jsonBody = null, $httpHeaders = null)
    {
        parent::__construct($message, $httpStatus, $httpBody, $jsonBody, $httpHeaders);
        $this->stripeParam = $stripeParam;
    }
    public function getStripeParam()
    {
        return $this->stripeParam;
    }
}

?>