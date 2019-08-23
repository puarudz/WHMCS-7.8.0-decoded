<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\api\contract\v1;

/**
 * Class representing UpdateHeldTransactionRequest
 */
class UpdateHeldTransactionRequest extends ANetApiRequestType
{
    /**
     * @property \net\authorize\api\contract\v1\HeldTransactionRequestType
     * $heldTransactionRequest
     */
    private $heldTransactionRequest = null;
    /**
     * Gets as heldTransactionRequest
     *
     * @return \net\authorize\api\contract\v1\HeldTransactionRequestType
     */
    public function getHeldTransactionRequest()
    {
        return $this->heldTransactionRequest;
    }
    /**
     * Sets a new heldTransactionRequest
     *
     * @param \net\authorize\api\contract\v1\HeldTransactionRequestType
     * $heldTransactionRequest
     * @return self
     */
    public function setHeldTransactionRequest(\net\authorize\api\contract\v1\HeldTransactionRequestType $heldTransactionRequest)
    {
        $this->heldTransactionRequest = $heldTransactionRequest;
        return $this;
    }
}

?>