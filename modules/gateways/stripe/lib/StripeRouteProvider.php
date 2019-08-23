<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Gateway\Stripe;

class StripeRouteProvider extends \WHMCS\Application\Support\ServiceProvider\AbstractServiceProvider implements \WHMCS\Route\Contracts\ProviderInterface
{
    use \WHMCS\Route\ProviderTrait;
    protected function getRoutes()
    {
        return array("/stripe" => array(array("name" => "stripe-payment-intent", "method" => array("POST"), "path" => "/payment/intent", "handle" => array("WHMCS\\Module\\Gateway\\Stripe\\StripeController", "intent")), array("name" => "stripe-payment-method-add", "method" => array("POST"), "path" => "/payment/add", "handle" => array("WHMCS\\Module\\Gateway\\Stripe\\StripeController", "add")), array("name" => "stripe-setup-intent", "method" => array("POST"), "path" => "/setup/intent", "handle" => array("WHMCS\\Module\\Gateway\\Stripe\\StripeController", "setupIntent")), array("name" => "stripe-payment-method-get", "method" => array("POST"), "path" => "/payment/get", "handle" => array("WHMCS\\Module\\Gateway\\Stripe\\StripeController", "get"))));
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