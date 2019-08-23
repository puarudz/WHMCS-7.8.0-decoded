<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace net\authorize\api\contract\v1;

/**
 * Class representing KeyManagementSchemeType
 *
 *
 * XSD Type: KeyManagementScheme
 */
class KeyManagementSchemeType
{
    /**
     * @property \net\authorize\api\contract\v1\KeyManagementSchemeType\DUKPTAType
     * $dUKPT
     */
    private $dUKPT = null;
    /**
     * Gets as dUKPT
     *
     * @return \net\authorize\api\contract\v1\KeyManagementSchemeType\DUKPTAType
     */
    public function getDUKPT()
    {
        return $this->dUKPT;
    }
    /**
     * Sets a new dUKPT
     *
     * @param \net\authorize\api\contract\v1\KeyManagementSchemeType\DUKPTAType $dUKPT
     * @return self
     */
    public function setDUKPT(\net\authorize\api\contract\v1\KeyManagementSchemeType\DUKPTAType $dUKPT)
    {
        $this->dUKPT = $dUKPT;
        return $this;
    }
}

?>