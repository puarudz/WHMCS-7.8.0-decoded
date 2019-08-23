<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace Stripe\Error\OAuth;

class OAuthBase extends \Stripe\Error\Base
{
    public function __construct($code, $description, $httpStatus = null, $httpBody = null, $jsonBody = null, $httpHeaders = null)
    {
        parent::__construct($description, $httpStatus, $httpBody, $jsonBody, $httpHeaders);
        $this->errorCode = $code;
    }
    public function getErrorCode()
    {
        return $this->errorCode;
    }
}

?>