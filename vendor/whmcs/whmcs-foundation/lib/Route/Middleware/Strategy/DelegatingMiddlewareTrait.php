<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Route\Middleware\Strategy;

abstract class DelegatingMiddlewareTrait
{
    public abstract function _process(\WHMCS\Http\Message\ServerRequest $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate);
    public function process(\Psr\Http\Message\ServerRequestInterface $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        $result = $this->_process($request, $delegate);
        if ($result instanceof \Psr\Http\Message\ResponseInterface || $result instanceof \WHMCS\Exception\HttpCodeException) {
            $response = $result;
        } else {
            $response = $delegate->process($result);
        }
        return $response;
    }
}

?>