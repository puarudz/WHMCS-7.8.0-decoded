<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\api\contract\v1;

/**
 * Class representing GetBatchStatisticsRequest
 */
class GetBatchStatisticsRequest extends ANetApiRequestType
{
    /**
     * @property string $batchId
     */
    private $batchId = null;
    /**
     * Gets as batchId
     *
     * @return string
     */
    public function getBatchId()
    {
        return $this->batchId;
    }
    /**
     * Sets a new batchId
     *
     * @param string $batchId
     * @return self
     */
    public function setBatchId($batchId)
    {
        $this->batchId = $batchId;
        return $this;
    }
}

?>