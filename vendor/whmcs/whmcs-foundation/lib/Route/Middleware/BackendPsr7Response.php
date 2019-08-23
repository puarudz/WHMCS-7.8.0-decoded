<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Route\Middleware;

class BackendPsr7Response implements \WHMCS\Route\Contracts\Middleware\StrategyInterface
{
    use Strategy\SharingMiddlewareTrait;
    public function process(\Psr\Http\Message\ServerRequestInterface $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        try {
            $response = $this->_process($request, $delegate);
        } catch (\WHMCS\Exception\HttpCodeException $e) {
            $factory = \DI::make("Route\\ResponseType");
            $response = $factory->factoryFromException($request, $e);
        }
        return $response;
    }
    public function _process(\WHMCS\Http\Message\ServerRequest $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        $response = $delegate->process($request);
        if (!$response instanceof \Psr\Http\Message\ResponseInterface) {
            if (is_object($response)) {
                $type = get_class($response);
            } else {
                $type = gettype($response);
            }
            throw new \WHMCS\Exception\HttpCodeException("Invalid internal middleware response " . $type, 500);
        }
        $statusCode = $response->getStatusCode();
        $statusFamily = substr($statusCode, 0, 1);
        if (!in_array($statusFamily, array(2, 3)) && !(string) $response->getBody()) {
            throw new \WHMCS\Exception\HttpCodeException("", $statusCode);
        }
        return $response;
    }
}

?>