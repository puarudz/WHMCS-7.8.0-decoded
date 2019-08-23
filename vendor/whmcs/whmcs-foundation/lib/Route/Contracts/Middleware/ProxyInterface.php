<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Route\Contracts\Middleware;

interface ProxyInterface extends StrategyInterface
{
    public function factoryProxyDriver($handle, \WHMCS\Http\Message\ServerRequest $request);
}

?>