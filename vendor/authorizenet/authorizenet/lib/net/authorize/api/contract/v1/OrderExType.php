<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\api\contract\v1;

/**
 * Class representing OrderExType
 *
 *
 * XSD Type: orderExType
 */
class OrderExType extends OrderType
{
    /**
     * @property string $purchaseOrderNumber
     */
    private $purchaseOrderNumber = null;
    /**
     * Gets as purchaseOrderNumber
     *
     * @return string
     */
    public function getPurchaseOrderNumber()
    {
        return $this->purchaseOrderNumber;
    }
    /**
     * Sets a new purchaseOrderNumber
     *
     * @param string $purchaseOrderNumber
     * @return self
     */
    public function setPurchaseOrderNumber($purchaseOrderNumber)
    {
        $this->purchaseOrderNumber = $purchaseOrderNumber;
        return $this;
    }
}

?>