<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\api\contract\v1\TransactionResponseType\ErrorsAType;

/**
 * Class representing ErrorAType
 */
class ErrorAType
{
    /**
     * @property string $errorCode
     */
    private $errorCode = null;
    /**
     * @property string $errorText
     */
    private $errorText = null;
    /**
     * Gets as errorCode
     *
     * @return string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }
    /**
     * Sets a new errorCode
     *
     * @param string $errorCode
     * @return self
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;
        return $this;
    }
    /**
     * Gets as errorText
     *
     * @return string
     */
    public function getErrorText()
    {
        return $this->errorText;
    }
    /**
     * Sets a new errorText
     *
     * @param string $errorText
     * @return self
     */
    public function setErrorText($errorText)
    {
        $this->errorText = $errorText;
        return $this;
    }
}

?>