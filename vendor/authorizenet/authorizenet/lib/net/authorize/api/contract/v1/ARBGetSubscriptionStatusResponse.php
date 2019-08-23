<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\api\contract\v1;

/**
 * Class representing ARBGetSubscriptionStatusResponse
 */
class ARBGetSubscriptionStatusResponse extends ANetApiResponseType
{
    /**
     * @property string $status
     */
    private $status = null;
    /**
     * Gets as status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }
    /**
     * Sets a new status
     *
     * @param string $status
     * @return self
     */
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }
}

?>