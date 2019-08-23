<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\api\contract\v1;

/**
 * Class representing GetHostedPaymentPageResponse
 */
class GetHostedPaymentPageResponse extends ANetApiResponseType
{
    /**
     * @property string $token
     */
    private $token = null;
    /**
     * Gets as token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }
    /**
     * Sets a new token
     *
     * @param string $token
     * @return self
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }
}

?>