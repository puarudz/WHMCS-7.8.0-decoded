<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\api\contract\v1;

/**
 * Class representing MobileDeviceRegistrationRequest
 */
class MobileDeviceRegistrationRequest extends ANetApiRequestType
{
    /**
     * @property \net\authorize\api\contract\v1\MobileDeviceType $mobileDevice
     */
    private $mobileDevice = null;
    /**
     * Gets as mobileDevice
     *
     * @return \net\authorize\api\contract\v1\MobileDeviceType
     */
    public function getMobileDevice()
    {
        return $this->mobileDevice;
    }
    /**
     * Sets a new mobileDevice
     *
     * @param \net\authorize\api\contract\v1\MobileDeviceType $mobileDevice
     * @return self
     */
    public function setMobileDevice(\net\authorize\api\contract\v1\MobileDeviceType $mobileDevice)
    {
        $this->mobileDevice = $mobileDevice;
        return $this;
    }
}

?>