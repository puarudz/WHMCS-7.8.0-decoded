<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\api\contract\v1;

/**
 * Class representing ARBUpdateSubscriptionRequest
 */
class ARBUpdateSubscriptionRequest extends ANetApiRequestType
{
    /**
     * @property string $subscriptionId
     */
    private $subscriptionId = null;
    /**
     * @property \net\authorize\api\contract\v1\ARBSubscriptionType $subscription
     */
    private $subscription = null;
    /**
     * Gets as subscriptionId
     *
     * @return string
     */
    public function getSubscriptionId()
    {
        return $this->subscriptionId;
    }
    /**
     * Sets a new subscriptionId
     *
     * @param string $subscriptionId
     * @return self
     */
    public function setSubscriptionId($subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;
        return $this;
    }
    /**
     * Gets as subscription
     *
     * @return \net\authorize\api\contract\v1\ARBSubscriptionType
     */
    public function getSubscription()
    {
        return $this->subscription;
    }
    /**
     * Sets a new subscription
     *
     * @param \net\authorize\api\contract\v1\ARBSubscriptionType $subscription
     * @return self
     */
    public function setSubscription(\net\authorize\api\contract\v1\ARBSubscriptionType $subscription)
    {
        $this->subscription = $subscription;
        return $this;
    }
}

?>