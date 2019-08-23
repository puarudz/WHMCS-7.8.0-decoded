<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Setup\Payments;

class GatewaysRouteProvider implements \WHMCS\Route\Contracts\DeferredProviderInterface
{
    use \WHMCS\Route\AdminProviderTrait;
    public function getRoutes()
    {
        $routes = array("/admin/setup/payments/gateways" => array("attributes" => array("authentication" => "admin", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Configure Payment Gateways"));
        }), array("method" => array("GET"), "name" => "admin-setup-payments-gateways-onboarding-return", "path" => "/onboarding/return", "handle" => array("WHMCS\\Admin\\Setup\\Payments\\GatewaysController", "handleOnboardingReturn")), array("method" => array("POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "action", "path" => "/{gateway:\\w+}/action/{method:\\w+}", "handle" => array("WHMCS\\Admin\\Setup\\Payments\\GatewaysController", "callAdditionalFunction"))));
        return $routes;
    }
    public function getDeferredRoutePathNameAttribute()
    {
        return "admin-setup-payments-gateways-";
    }
}

?>