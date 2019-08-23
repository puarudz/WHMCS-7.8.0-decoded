<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\api\contract\v1;

/**
 * Class representing ARBGetSubscriptionRequest
 */
class ARBGetSubscriptionRequest extends ANetApiRequestType
{
    /**
     * @property string $subscriptionId
     */
    private $subscriptionId = null;
    /**
     * @property boolean $includeTransactions
     */
    private $includeTransactions = null;
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
     * Gets as includeTransactions
     *
     * @return boolean
     */
    public function getIncludeTransactions()
    {
        return $this->includeTransactions;
    }
    /**
     * Sets a new includeTransactions
     *
     * @param boolean $includeTransactions
     * @return self
     */
    public function setIncludeTransactions($includeTransactions)
    {
        $this->includeTransactions = $includeTransactions;
        return $this;
    }
}

?>