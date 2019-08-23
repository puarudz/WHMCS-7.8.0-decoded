<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Payment;

class PaymentRouteProvider extends \WHMCS\Application\Support\ServiceProvider\AbstractServiceProvider implements \WHMCS\Route\Contracts\ProviderInterface
{
    use \WHMCS\Route\ProviderTrait;
    protected function getRoutes()
    {
        return array("/payment" => array(array("name" => "payment-remote-confirm", "method" => array("POST"), "path" => "/remote/confirm", "handle" => array("WHMCS\\Payment\\PaymentController", "confirm")), array("name" => "payment-remote-confirm", "method" => array("POST"), "path" => "/remote/confirm/update", "handle" => array("WHMCS\\Payment\\PaymentController", "update"))));
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