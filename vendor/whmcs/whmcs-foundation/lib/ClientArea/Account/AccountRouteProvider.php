<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\ClientArea\Account;

class AccountRouteProvider implements \WHMCS\Route\Contracts\DeferredProviderInterface
{
    use \WHMCS\Route\ProviderTrait;
    public function getRoutes()
    {
        $helpRoutes = array("/account" => array("attributes" => array("authorization" => function () {
            return new \WHMCS\Security\Middleware\Authorization();
        }), array("method" => array("GET"), "name" => $this->getDeferredRoutePathNameAttribute() . "index", "path" => "", "handle" => array("WHMCS\\ClientArea\\Account\\AccountController", "index")), array("method" => array("POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "security-two-factor-enable", "path" => "/security/two-factor/enable", "handle" => array("WHMCS\\Authentication\\TwoFactor\\TwoFactorController", "enable")), array("method" => array("GET", "POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "security-two-factor-enable-configure", "path" => "/security/two-factor/enable/configure", "handle" => array("WHMCS\\Authentication\\TwoFactor\\TwoFactorController", "configure"), "authorization" => function (\WHMCS\Security\Middleware\Authorization $authz) {
            return $authz->requireCsrfToken();
        }), array("method" => array("GET"), "name" => $this->getDeferredRoutePathNameAttribute() . "security-two-factor-qr-code", "path" => "/security/two-factor/qr/{module}", "handle" => array("WHMCS\\Authentication\\TwoFactor\\TwoFactorController", "qrCode")), array("method" => array("POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "security-two-factor-enable-verify", "path" => "/security/two-factor/enable/verify", "handle" => array("WHMCS\\Authentication\\TwoFactor\\TwoFactorController", "verify"), "authorization" => function (\WHMCS\Security\Middleware\Authorization $authz) {
            return $authz->requireCsrfToken();
        }), array("method" => array("POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "security-two-factor-disable", "path" => "/security/two-factor/disable", "handle" => array("WHMCS\\Authentication\\TwoFactor\\TwoFactorController", "disable")), array("method" => array("POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "security-two-factor-disable-confirm", "path" => "/security/two-factor/disable/confirm", "handle" => array("WHMCS\\Authentication\\TwoFactor\\TwoFactorController", "disableConfirm"), "authorization" => function (\WHMCS\Security\Middleware\Authorization $authz) {
            return $authz->requireCsrfToken();
        }), array("method" => array("GET"), "name" => $this->getDeferredRoutePathNameAttribute() . "paymentmethods", "path" => "/paymentmethods", "handle" => array("WHMCS\\ClientArea\\Account\\PaymentMethodsController", "index")), array("method" => array("GET"), "name" => $this->getDeferredRoutePathNameAttribute() . "paymentmethods-add", "path" => "/paymentmethods/add", "handle" => array("WHMCS\\ClientArea\\Account\\PaymentMethodsController", "add")), array("method" => array("POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "paymentmethods-add", "path" => "/paymentmethods/add", "handle" => array("WHMCS\\ClientArea\\Account\\PaymentMethodsController", "create")), array("method" => array("POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "paymentmethods-inittoken", "path" => "/paymentmethods/inittoken", "handle" => array("WHMCS\\ClientArea\\Account\\PaymentMethodsController", "initToken")), array("method" => array("GET"), "name" => $this->getDeferredRoutePathNameAttribute() . "paymentmethods-view", "path" => "/paymentmethods/{id:\\d+}", "handle" => array("WHMCS\\ClientArea\\Account\\PaymentMethodsController", "manage")), array("method" => array("POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "paymentmethods-setdefault", "path" => "/paymentmethods/{id:\\d+}/default", "handle" => array("WHMCS\\ClientArea\\Account\\PaymentMethodsController", "setDefault")), array("method" => array("POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "paymentmethods-delete", "path" => "/paymentmethods/{id:\\d+}/delete", "handle" => array("WHMCS\\ClientArea\\Account\\PaymentMethodsController", "delete")), array("method" => array("POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "paymentmethods-save", "path" => "/paymentmethods/{id:\\d+}", "handle" => array("WHMCS\\ClientArea\\Account\\PaymentMethodsController", "save")), array("method" => array("GET"), "name" => $this->getDeferredRoutePathNameAttribute() . "paymentmethods-billing-contacts", "path" => "/paymentmethods-billing-contacts[/{id:\\d+}]", "handle" => array("WHMCS\\ClientArea\\Account\\PaymentMethodsController", "getBillingContacts")), array("method" => array("POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "paymentmethods-billing-contacts-create", "path" => "/paymentmethods-billing-contacts/create", "handle" => array("WHMCS\\ClientArea\\Account\\PaymentMethodsController", "createBillingContact")), array("method" => array("POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "paymentmethods-get-remote-input", "path" => "/paymentmethods/remote-input", "handle" => array("WHMCS\\ClientArea\\Account\\PaymentMethodsController", "remoteInput"))));
        return $helpRoutes;
    }
    public function getDeferredRoutePathNameAttribute()
    {
        return "account-";
    }
    public function registerRoutes(\FastRoute\RouteCollector $routeCollector)
    {
        $this->addRouteGroups($routeCollector, $this->getRoutes());
    }
}

?>