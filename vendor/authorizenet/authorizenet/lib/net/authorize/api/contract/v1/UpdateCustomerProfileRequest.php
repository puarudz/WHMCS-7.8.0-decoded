<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\api\contract\v1;

/**
 * Class representing UpdateCustomerProfileRequest
 */
class UpdateCustomerProfileRequest extends ANetApiRequestType
{
    /**
     * @property \net\authorize\api\contract\v1\CustomerProfileExType $profile
     */
    private $profile = null;
    /**
     * Gets as profile
     *
     * @return \net\authorize\api\contract\v1\CustomerProfileExType
     */
    public function getProfile()
    {
        return $this->profile;
    }
    /**
     * Sets a new profile
     *
     * @param \net\authorize\api\contract\v1\CustomerProfileExType $profile
     * @return self
     */
    public function setProfile(\net\authorize\api\contract\v1\CustomerProfileExType $profile)
    {
        $this->profile = $profile;
        return $this;
    }
}

?>