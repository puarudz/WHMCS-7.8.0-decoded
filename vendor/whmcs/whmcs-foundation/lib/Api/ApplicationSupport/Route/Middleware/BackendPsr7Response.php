<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Api\ApplicationSupport\Route\Middleware;

class BackendPsr7Response implements \WHMCS\Route\Contracts\Middleware\StrategyInterface
{
    use \WHMCS\Route\Middleware\Strategy\DelegatingMiddlewareTrait;
    public function _process(\WHMCS\Http\Message\ServerRequest $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        $response = $delegate->process($request);
        if (!$response instanceof \Psr\Http\Message\ResponseInterface) {
            $code = 0;
            $msg = "Invalid response : ";
            if (is_string($response) || is_array($response)) {
                $msg .= (string) $response;
            } else {
                if (is_object($response)) {
                    if (method_exists($response, "__toString")) {
                        $msg = (string) $response;
                    } else {
                        $msg .= get_class($response);
                    }
                    if ($response instanceof \Exception) {
                        $code = $response->getCode();
                    }
                } else {
                    $msg .= "Unknown";
                }
            }
            throw new \RuntimeException($msg, $code);
        }
        return $response;
    }
    public function process(\Psr\Http\Message\ServerRequestInterface $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        $response = null;
        $responseData = array();
        $statusCode = 200;
        try {
            $response = $this->_process($request, $delegate);
        } catch (\WHMCS\Exception\Api\AuthException $e) {
            $responseData = array("result" => "error", "message" => $e->getMessage());
            $statusCode = $e->getCode();
        } catch (\WHMCS\Exception\Api\InvalidResponseType $e) {
            $responseData = array("result" => "error", "message" => $e->getMessage());
        } catch (\Exception $e) {
            $responseData = array("result" => "error", "message" => $e->getMessage());
            $code = $e->getCode();
            if ($code < 600 && 99 < $code) {
                $statusCode = $code;
            }
        } finally {
            if (!$response instanceof \Psr\Http\Message\ResponseInterface) {
                $response = \WHMCS\Api\ApplicationSupport\Http\ResponseFactory::factory($request, $responseData, $statusCode);
            }
        }
    }
}

?>