<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Api\ApplicationSupport\Route\Middleware;

class ApiLog implements \WHMCS\Route\Contracts\Middleware\StrategyInterface
{
    use \WHMCS\Route\Middleware\Strategy\AssumingMiddlewareTrait;
    public function _process(\WHMCS\Http\Message\ServerRequest $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        $response = $delegate->process($request);
        $loggableRequest = \DI::make("runtimeStorage")->apiRequest;
        if (!$loggableRequest) {
            $loggableRequest = $request;
        }
        $logger = \DI::make("ApiLog");
        $logger->info($loggableRequest->getAction(), array("request" => $loggableRequest, "response" => $response));
        return $response;
    }
}

?>