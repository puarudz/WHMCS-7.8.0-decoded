<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment\Filter;

class Name extends AbstractFilter
{
    private $acceptableName = array();
    public function __construct($name)
    {
        if (!is_array($name)) {
            $name = array($name);
        }
        $this->acceptableName = $name;
    }
    public function filter(\WHMCS\Payment\Adapter\AdapterInterface $adapter)
    {
        $name = $adapter->getName();
        if (in_array($name, $this->acceptableName)) {
            return true;
        }
        return false;
    }
}

?>