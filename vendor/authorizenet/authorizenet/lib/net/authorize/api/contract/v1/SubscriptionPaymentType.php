<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\api\contract\v1;

/**
 * Class representing SubscriptionPaymentType
 *
 *
 * XSD Type: subscriptionPaymentType
 */
class SubscriptionPaymentType
{
    /**
     * @property integer $id
     */
    private $id = null;
    /**
     * @property integer $payNum
     */
    private $payNum = null;
    /**
     * Gets as id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     * Sets a new id
     *
     * @param integer $id
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
    /**
     * Gets as payNum
     *
     * @return integer
     */
    public function getPayNum()
    {
        return $this->payNum;
    }
    /**
     * Sets a new payNum
     *
     * @param integer $payNum
     * @return self
     */
    public function setPayNum($payNum)
    {
        $this->payNum = $payNum;
        return $this;
    }
}

?>