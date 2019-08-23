<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\ApplicationSupport\Route\Middleware;

class Assent implements \WHMCS\Route\Contracts\Middleware\StrategyInterface
{
    use \WHMCS\Route\Middleware\Strategy\DelegatingMiddlewareTrait;
    public function _process(\WHMCS\Http\Message\ServerRequest $request, \Interop\Http\ServerMiddleware\DelegateInterface $delegate)
    {
        $license = \DI::make("license");
        $eula = new \WHMCS\Utility\Eula();
        if ($request->getAttribute("authenticatedUser")) {
            if (!$eula->isEulaAccepted()) {
                $controller = new \WHMCS\Admin\Utilities\Assent\Controller\EulaController();
                if ($request->has("eulaAccepted") && $request->get("eulaAccepted")) {
                    return $controller->acceptEula($request);
                }
                return $controller->eulaAcceptanceRequired($request);
            }
            if ($license->isUnlicensed()) {
                $controller = new \WHMCS\Admin\Utilities\Assent\Controller\LicenseController();
                if ($request->has("license_key")) {
                    return $controller->updateLicenseKey($request);
                }
                return $controller->licensedRequired($request);
            }
        }
        return $request;
    }
}

?>