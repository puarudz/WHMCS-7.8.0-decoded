<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\api\contract\v1;

/**
 * Class representing GetAUJobDetailsResponse
 */
class GetAUJobDetailsResponse extends ANetApiResponseType
{
    /**
     * @property integer $totalNumInResultSet
     */
    private $totalNumInResultSet = null;
    /**
     * @property \net\authorize\api\contract\v1\ListOfAUDetailsType $auDetails
     */
    private $auDetails = null;
    /**
     * Gets as totalNumInResultSet
     *
     * @return integer
     */
    public function getTotalNumInResultSet()
    {
        return $this->totalNumInResultSet;
    }
    /**
     * Sets a new totalNumInResultSet
     *
     * @param integer $totalNumInResultSet
     * @return self
     */
    public function setTotalNumInResultSet($totalNumInResultSet)
    {
        $this->totalNumInResultSet = $totalNumInResultSet;
        return $this;
    }
    /**
     * Gets as auDetails
     *
     * @return \net\authorize\api\contract\v1\ListOfAUDetailsType
     */
    public function getAuDetails()
    {
        return $this->auDetails;
    }
    /**
     * Sets a new auDetails
     *
     * @param \net\authorize\api\contract\v1\ListOfAUDetailsType $auDetails
     * @return self
     */
    public function setAuDetails(\net\authorize\api\contract\v1\ListOfAUDetailsType $auDetails)
    {
        $this->auDetails = $auDetails;
        return $this;
    }
}

?>