<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Domains\DomainLookup;

class ResultsList extends \ArrayObject
{
    public function toArray()
    {
        $result = array();
        foreach ($this->getArrayCopy() as $key => $data) {
            $result[$key] = $data->toArray();
        }
        return $result;
    }
}

?>