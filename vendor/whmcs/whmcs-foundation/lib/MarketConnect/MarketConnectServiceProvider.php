<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect;

class MarketConnectServiceProvider extends \WHMCS\Application\Support\ServiceProvider\AbstractServiceProvider implements \WHMCS\Route\Contracts\ProviderInterface
{
    use \WHMCS\Route\ProviderTrait;
    public function register()
    {
    }
    protected function getRoutes()
    {
        return array("/store" => array(array("name" => "store-ssl-certificates-dv", "path" => "/ssl-certificates/dv", "handle" => array("\\WHMCS\\MarketConnect\\SslController", "viewDv"), "method" => array("GET", "POST")), array("name" => "store-ssl-certificates-ov", "path" => "/ssl-certificates/ov", "handle" => array("\\WHMCS\\MarketConnect\\SslController", "viewOv"), "method" => array("GET", "POST")), array("name" => "store-ssl-certificates-ev", "path" => "/ssl-certificates/ev", "handle" => array("\\WHMCS\\MarketConnect\\SslController", "viewEv"), "method" => array("GET", "POST")), array("name" => "store-ssl-certificates-wildcard", "path" => "/ssl-certificates/wildcard", "handle" => array("\\WHMCS\\MarketConnect\\SslController", "viewWildcard"), "method" => array("GET", "POST")), array("name" => "store-ssl-certificates-competitiveupgrade", "path" => "/ssl-certificates/switch", "handle" => array("\\WHMCS\\MarketConnect\\SslController", "competitiveUpgrade"), "method" => array("GET")), array("name" => "store-ssl-certificates-competitiveupgrade-validate", "path" => "/ssl-certificates/switch/validate", "handle" => array("\\WHMCS\\MarketConnect\\SslController", "validateCompetitiveUpgrade"), "method" => array("POST")), array("name" => "store-ssl-certificates-manage", "path" => "/ssl-certificates/manage", "handle" => array("\\WHMCS\\MarketConnect\\SslController", "manage"), "method" => array("GET")), array("name" => "store-ssl-certificates-resend-approver-email", "path" => "/ssl-certificates/resend-approver-email", "handle" => array("\\WHMCS\\MarketConnect\\SslController", "resendApproverEmail"), "method" => array("POST")), array("name" => "store-ssl-certificates-index", "path" => "/ssl-certificates", "handle" => array("\\WHMCS\\MarketConnect\\SslController", "index"), "method" => array("GET", "POST")), array("name" => "store-websitebuilder-index", "path" => "/website-builder", "handle" => array("\\WHMCS\\MarketConnect\\WeeblyController", "index"), "method" => array("GET", "POST")), array("name" => "store-weebly-upgrade", "path" => "/weebly/upgrade", "handle" => array("\\WHMCS\\MarketConnect\\WeeblyController", "upgrade"), "method" => array("GET", "POST")), array("name" => "store-weebly-upgrade-order", "path" => "/weebly/upgrade/order", "handle" => array("\\WHMCS\\MarketConnect\\WeeblyController", "orderUpgrade"), "method" => array("POST")), array("name" => "store-emailservices-index", "path" => "/email-services", "handle" => array("\\WHMCS\\MarketConnect\\SpamExpertsController", "index"), "method" => array("GET", "POST")), array("name" => "store-sitelock-index", "path" => "/sitelock", "handle" => array("\\WHMCS\\MarketConnect\\SitelockController", "index"), "method" => array("GET", "POST")), array("name" => "store-order-addtocart", "path" => "/order/add", "handle" => array("\\WHMCS\\MarketConnect\\StoreController", "addToCart"), "method" => array("POST")), array("name" => "store-order-login", "path" => "/order/login", "handle" => array("\\WHMCS\\MarketConnect\\StoreController", "login"), "method" => array("GET", "POST")), array("name" => "store-order-validate", "path" => "/order/validate", "handle" => array("\\WHMCS\\MarketConnect\\StoreController", "validate"), "method" => array("POST")), array("name" => "store-order", "path" => "/order", "handle" => array("\\WHMCS\\MarketConnect\\StoreController", "order"), "method" => array("POST", "GET")), array("name" => "store-ssl-callback", "path" => "/callback/ssl", "handle" => array("\\WHMCS\\MarketConnect\\SslController", "handleSslCallback"), "method" => array("POST")), array("name" => "store-codeguard-index", "path" => "/codeguard", "handle" => array("WHMCS\\MarketConnect\\CodeGuardController", "index"), "method" => array("GET", "POST")), array("name" => "store", "path" => "", "handle" => function () {
            return new \Zend\Diactoros\Response\RedirectResponse(\WHMCS\Utility\Environment\WebHelper::getBaseUrl() . "/cart.php");
        }, "method" => array("GET"))));
    }
    public function registerRoutes(\FastRoute\RouteCollector $routeCollector)
    {
        $this->addRouteGroups($routeCollector, $this->getRoutes());
    }
}

?>