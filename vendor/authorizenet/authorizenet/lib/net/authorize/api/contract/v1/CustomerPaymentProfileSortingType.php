<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\api\contract\v1;

/**
 * Class representing CustomerPaymentProfileSortingType
 *
 *
 * XSD Type: CustomerPaymentProfileSorting
 */
class CustomerPaymentProfileSortingType
{
    /**
     * @property string $orderBy
     */
    private $orderBy = null;
    /**
     * @property boolean $orderDescending
     */
    private $orderDescending = null;
    /**
     * Gets as orderBy
     *
     * @return string
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }
    /**
     * Sets a new orderBy
     *
     * @param string $orderBy
     * @return self
     */
    public function setOrderBy($orderBy)
    {
        $this->orderBy = $orderBy;
        return $this;
    }
    /**
     * Gets as orderDescending
     *
     * @return boolean
     */
    public function getOrderDescending()
    {
        return $this->orderDescending;
    }
    /**
     * Sets a new orderDescending
     *
     * @param boolean $orderDescending
     * @return self
     */
    public function setOrderDescending($orderDescending)
    {
        $this->orderDescending = $orderDescending;
        return $this;
    }
}

?>