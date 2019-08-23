<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Application\Support\ServiceProvider;

abstract class AbstractServiceProvider
{
    protected $app = NULL;
    public function __construct(\WHMCS\Container $app)
    {
        $this->app = $app;
    }
    public abstract function register();
}

?>