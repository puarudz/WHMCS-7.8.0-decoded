<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Route\Contracts;

interface MapInterface
{
    public function mapRoute($route);
    public function getMappedRoute($key);
    public function getMappedAttributeName();
}

?>