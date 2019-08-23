<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Utility\Bootstrap;

class OauthServer extends Application
{
    public static function boot(\WHMCS\Config\RuntimeStorage $preBootInstances = NULL)
    {
        parent::boot($preBootInstances);
        \Di::make("app");
    }
}

?>