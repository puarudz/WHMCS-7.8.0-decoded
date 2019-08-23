<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Account;

class AccountRouteProvider implements \WHMCS\Route\Contracts\DeferredProviderInterface
{
    use \WHMCS\Route\AdminProviderTrait;
    public function getRoutes()
    {
        $routes = array("/admin/account" => array("attributes" => array("authentication" => "admin", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("My Account"));
        }), array("method" => array("POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "security-two-factor-enable", "path" => "/security/two-factor/enable", "handle" => array("WHMCS\\Admin\\Account\\TwoFactorController", "enable")), array("method" => array("GET", "POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "security-two-factor-enable-configure", "path" => "/security/two-factor/enable/configure", "handle" => array("WHMCS\\Admin\\Account\\TwoFactorController", "configure"), "authorization" => function (\WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization $authz) {
            return $authz->setRequireAllPermission(array("My Account"))->requireCsrfToken();
        }), array("method" => array("GET"), "name" => $this->getDeferredRoutePathNameAttribute() . "security-two-factor-qr-code", "path" => "/security/two-factor/qr/{module}", "handle" => array("WHMCS\\Admin\\Account\\TwoFactorController", "qrCode")), array("method" => array("POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "security-two-factor-enable-verify", "path" => "/security/two-factor/enable/verify", "handle" => array("WHMCS\\Admin\\Account\\TwoFactorController", "verify"), "authorization" => function (\WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization $authz) {
            return $authz->setRequireAllPermission(array("My Account"))->requireCsrfToken();
        }), array("method" => array("POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "security-two-factor-disable", "path" => "/security/two-factor/disable", "handle" => array("WHMCS\\Admin\\Account\\TwoFactorController", "disable")), array("method" => array("POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "security-two-factor-disable-confirm", "path" => "/security/two-factor/disable/confirm", "handle" => array("WHMCS\\Admin\\Account\\TwoFactorController", "disableConfirm"), "authorization" => function (\WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization $authz) {
            return $authz->setRequireAllPermission(array("My Account"))->requireCsrfToken();
        })));
        return $routes;
    }
    public function getDeferredRoutePathNameAttribute()
    {
        return "admin-account-";
    }
    public function registerRoutes(\FastRoute\RouteCollector $routeCollector)
    {
        $this->addRouteGroups($routeCollector, $this->getRoutes());
    }
}

?>