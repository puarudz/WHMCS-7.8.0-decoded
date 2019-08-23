<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Route\Middleware;

class RoutableRequestUri implements \WHMCS\Route\Contracts\Middleware\StrategyInterface
{
    use Strategy\AssumingMiddlewareTrait;
    public function _process(\WHMCS\Http\Message\ServerRequest $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        return $delegate->process($this->updateUriFromServerScriptName($request));
    }
    public function updateUriFromServerScriptName(\Psr\Http\Message\ServerRequestInterface $request)
    {
        $uri = $request->getUri();
        $path = (string) $uri->getPath();
        $path = preg_replace("/\\/+/", "/", $path);
        $serverParams = $request->getServerParams();
        if (is_array($serverParams) && isset($serverParams["SCRIPT_NAME"])) {
            $serverScriptName = $serverParams["SCRIPT_NAME"];
        } else {
            $serverScriptName = null;
        }
        $baseUrl = \WHMCS\Utility\Environment\WebHelper::getBaseUrl(ROOTDIR, $serverScriptName);
        $baseInUrlPattern = "#^" . preg_quote($baseUrl . "/") . "#";
        if ($path !== $serverScriptName && strpos($path, "detect-route-environment") === false) {
            $scriptLessPath = preg_replace("#^" . preg_quote($serverScriptName) . "#", "", $path);
            if (is_null($serverScriptName) || $scriptLessPath !== $path) {
                $path = $scriptLessPath;
            } else {
                if (1 < strlen($path) && substr($path, -1) == "/") {
                    $path = substr($path, 0, -1);
                }
                if ($path === $baseUrl) {
                    $path = "/";
                } else {
                    if ($path !== $baseUrl && preg_match($baseInUrlPattern, $path)) {
                        $path = preg_replace("#^" . preg_quote($baseUrl) . "#", "", $path);
                    }
                }
            }
        } else {
            if (1 < strlen($path) && substr($path, -1) == "/") {
                $path = substr($path, 0, -1);
            }
            if ($path === $baseUrl) {
                $path = "/";
            } else {
                if ($path !== $baseUrl && preg_match($baseInUrlPattern, $path)) {
                    $path = preg_replace("#^" . preg_quote($baseUrl) . "#", "", $path);
                }
            }
        }
        if (1 < strlen($path) && substr($path, -1) == "/") {
            $path = substr($path, 0, -1);
        }
        $uri = $uri->withPath($path);
        return $request->withUri($uri);
    }
}

?>