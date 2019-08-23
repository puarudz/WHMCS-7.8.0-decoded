<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\api\contract\v1;

/**
 * Class representing SecurePaymentContainerRequest
 */
class SecurePaymentContainerRequest extends ANetApiRequestType
{
    /**
     * @property \net\authorize\api\contract\v1\WebCheckOutDataType $data
     */
    private $data = null;
    /**
     * Gets as data
     *
     * @return \net\authorize\api\contract\v1\WebCheckOutDataType
     */
    public function getData()
    {
        return $this->data;
    }
    /**
     * Sets a new data
     *
     * @param \net\authorize\api\contract\v1\WebCheckOutDataType $data
     * @return self
     */
    public function setData(\net\authorize\api\contract\v1\WebCheckOutDataType $data)
    {
        $this->data = $data;
        return $this;
    }
}

?>