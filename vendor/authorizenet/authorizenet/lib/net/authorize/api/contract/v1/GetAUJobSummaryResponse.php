<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\api\contract\v1;

/**
 * Class representing GetAUJobSummaryResponse
 */
class GetAUJobSummaryResponse extends ANetApiResponseType
{
    /**
     * @property \net\authorize\api\contract\v1\AuResponseType[] $auSummary
     */
    private $auSummary = null;
    /**
     * Adds as auResponse
     *
     * @return self
     * @param \net\authorize\api\contract\v1\AuResponseType $auResponse
     */
    public function addToAuSummary(\net\authorize\api\contract\v1\AuResponseType $auResponse)
    {
        $this->auSummary[] = $auResponse;
        return $this;
    }
    /**
     * isset auSummary
     *
     * @param scalar $index
     * @return boolean
     */
    public function issetAuSummary($index)
    {
        return isset($this->auSummary[$index]);
    }
    /**
     * unset auSummary
     *
     * @param scalar $index
     * @return void
     */
    public function unsetAuSummary($index)
    {
        unset($this->auSummary[$index]);
    }
    /**
     * Gets as auSummary
     *
     * @return \net\authorize\api\contract\v1\AuResponseType[]
     */
    public function getAuSummary()
    {
        return $this->auSummary;
    }
    /**
     * Sets a new auSummary
     *
     * @param \net\authorize\api\contract\v1\AuResponseType[] $auSummary
     * @return self
     */
    public function setAuSummary(array $auSummary)
    {
        $this->auSummary = $auSummary;
        return $this;
    }
}

?>