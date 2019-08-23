<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cart;

class CartServiceProvider extends \WHMCS\Application\Support\ServiceProvider\AbstractServiceProvider implements \WHMCS\Route\Contracts\ProviderInterface
{
    use \WHMCS\Route\ProviderTrait;
    protected function getRoutes()
    {
        return array("/cart/domain" => array(array("name" => "cart-domain-renewals-add", "method" => array("POST"), "path" => "/renew/add", "handle" => array("WHMCS\\Cart\\Controller\\DomainController", "addRenewal")), array("name" => "cart-domain-renewals", "method" => array("GET", "POST"), "path" => "/renew", "handle" => array("WHMCS\\Cart\\Controller\\DomainController", "massRenew")), array("name" => "cart-domain-renew-calculate", "method" => array("GET"), "path" => "/renew/calculate", "handle" => array("WHMCS\\Cart\\Controller\\DomainController", "calcRenewalCartTotals"))));
    }
    public function registerRoutes(\FastRoute\RouteCollector $routeCollector)
    {
        $this->addRouteGroups($routeCollector, $this->getRoutes());
    }
    public function register()
    {
    }
}

?>