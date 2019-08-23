<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Application\Support\Facades;

class Facade extends \Illuminate\Support\Facades\Facade
{
    public static function self()
    {
        return static::getFacadeRoot();
    }
}

?>