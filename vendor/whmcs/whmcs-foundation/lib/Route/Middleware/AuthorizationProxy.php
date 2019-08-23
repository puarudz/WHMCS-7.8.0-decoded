<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Route\Middleware;

class AuthorizationProxy extends AbstractProxyMiddleware
{
    public function getMappedAttributeName()
    {
        return "authorization";
    }
    public function factoryProxyDriver($handle, \WHMCS\Http\Message\ServerRequest $request = NULL)
    {
        if ($handle == "api") {
            $driver = new \WHMCS\Api\ApplicationSupport\Route\Middleware\Authorization();
        } else {
            if (is_callable($handle)) {
                $driver = $handle();
            } else {
                throw new \RuntimeException("Invalid authorization middleware not supported" . $request->getUri()->getPath());
            }
        }
        return $driver;
    }
}

?>