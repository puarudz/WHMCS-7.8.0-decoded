<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Route\Middleware;

class RoutableAdminRequestUri implements \WHMCS\Route\Contracts\Middleware\StrategyInterface
{
    use Strategy\AssumingMiddlewareTrait;
    const ATTRIBUTE_ADMIN_REQUEST = "isAdminRouteRequest";
    public function _process(\WHMCS\Http\Message\ServerRequest $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        $path = $request->getUri()->getPath();
        if ($this->isAdminLegacyEndpoint($path) && !$this->isAdminRoutePath($path)) {
            $adminBasePath = \WHMCS\Admin\AdminServiceProvider::getAdminRouteBase();
            if (strpos($path, "/admin") === 0) {
                $path = $adminBasePath . substr($path, 6);
                $request = $request->withUri($request->getUri()->withPath($path));
            }
        }
        if ($this->isAdminLegacyEndpoint($path) || $this->isAdminRoutePath($path)) {
            if ($path == \WHMCS\Admin\AdminServiceProvider::getAdminRouteBase()) {
                $path .= "/";
                $request = $request->withUri($request->getUri()->withPath($path));
            }
            $isAdminRequest = true;
            \WHMCS\Utility\Bootstrap\AbstractBootstrap::registerServices(\DI::make("di"), array("\\WHMCS\\Admin\\AdminServiceProvider"));
        } else {
            $isAdminRequest = false;
        }
        $request = $request->withAttribute(static::ATTRIBUTE_ADMIN_REQUEST, $isAdminRequest);
        return $delegate->process($request);
    }
    public function isAdminLegacyEndpoint($path)
    {
        if (defined("ROUTE_CONVERTED_LEGACY_ENDPOINT") && constant("ROUTE_CONVERTED_LEGACY_ENDPOINT") && strpos($path, "/admin") === 0) {
            return true;
        }
        return false;
    }
    public function isAdminRoutePath($path)
    {
        $adminDirectoryName = \WHMCS\Admin\AdminServiceProvider::getAdminRouteBase();
        $testPath = preg_replace("#^" . $adminDirectoryName . "#", "", $path);
        if ($testPath == $path) {
            return false;
        }
        if (!$testPath || substr($testPath, 0, 1) == "/") {
            return true;
        }
        return false;
    }
}

?>