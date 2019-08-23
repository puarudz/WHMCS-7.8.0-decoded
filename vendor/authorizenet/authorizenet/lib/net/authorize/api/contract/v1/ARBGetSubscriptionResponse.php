<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\api\contract\v1;

/**
 * Class representing ARBGetSubscriptionResponse
 */
class ARBGetSubscriptionResponse extends ANetApiResponseType
{
    /**
     * @property \net\authorize\api\contract\v1\ARBSubscriptionMaskedType $subscription
     */
    private $subscription = null;
    /**
     * Gets as subscription
     *
     * @return \net\authorize\api\contract\v1\ARBSubscriptionMaskedType
     */
    public function getSubscription()
    {
        return $this->subscription;
    }
    /**
     * Sets a new subscription
     *
     * @param \net\authorize\api\contract\v1\ARBSubscriptionMaskedType $subscription
     * @return self
     */
    public function setSubscription(\net\authorize\api\contract\v1\ARBSubscriptionMaskedType $subscription)
    {
        $this->subscription = $subscription;
        return $this;
    }
}

?>