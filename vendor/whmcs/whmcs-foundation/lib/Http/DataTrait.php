<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Http;

trait DataTrait
{
    protected $rawData = array();
    public function getRawData()
    {
        return $this->rawData;
    }
    public function setRawData($rawData)
    {
        $this->rawData = $rawData;
        return $this;
    }
}

?>