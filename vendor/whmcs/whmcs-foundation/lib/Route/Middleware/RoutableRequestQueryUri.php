<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Route\Middleware;

class RoutableRequestQueryUri implements \WHMCS\Route\Contracts\Middleware\StrategyInterface
{
    use Strategy\AssumingMiddlewareTrait;
    public function _process(\WHMCS\Http\Message\ServerRequest $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        return $delegate->process($this->updateUriFromRequestQuery($request));
    }
    protected function updateUriFromRequestQuery(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $uri = $request->getUri();
        $pathParts = explode("/", $uri->getPath());
        $lastPath = array_pop($pathParts);
        if ($lastPath != "index.php" && (!defined("ROUTE_CONVERTED_LEGACY_ENDPOINT") || !constant("ROUTE_CONVERTED_LEGACY_ENDPOINT"))) {
            return $request;
        }
        $routePath = $request->get("rp", "");
        if (!$routePath) {
            return $request;
        }
        if (strpos($routePath, "detect-route-environment") !== false) {
            return $request;
        }
        if (substr($routePath, 0, 1) != "/") {
            $routePath = "/" . $routePath;
        }
        $uri = $uri->withPath($uri->getPath() . $routePath);
        return $request->withUri($uri);
    }
}

?>