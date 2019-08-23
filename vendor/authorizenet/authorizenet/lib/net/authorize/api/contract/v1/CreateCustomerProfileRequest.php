<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\api\contract\v1;

/**
 * Class representing CreateCustomerProfileRequest
 */
class CreateCustomerProfileRequest extends ANetApiRequestType
{
    /**
     * @property \net\authorize\api\contract\v1\CustomerProfileType $profile
     */
    private $profile = null;
    /**
     * @property string $validationMode
     */
    private $validationMode = null;
    /**
     * Gets as profile
     *
     * @return \net\authorize\api\contract\v1\CustomerProfileType
     */
    public function getProfile()
    {
        return $this->profile;
    }
    /**
     * Sets a new profile
     *
     * @param \net\authorize\api\contract\v1\CustomerProfileType $profile
     * @return self
     */
    public function setProfile(\net\authorize\api\contract\v1\CustomerProfileType $profile)
    {
        $this->profile = $profile;
        return $this;
    }
    /**
     * Gets as validationMode
     *
     * @return string
     */
    public function getValidationMode()
    {
        return $this->validationMode;
    }
    /**
     * Sets a new validationMode
     *
     * @param string $validationMode
     * @return self
     */
    public function setValidationMode($validationMode)
    {
        $this->validationMode = $validationMode;
        return $this;
    }
}

?>