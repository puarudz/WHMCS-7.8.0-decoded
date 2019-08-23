<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Api;

class ApiServiceProvider extends \WHMCS\Application\Support\ServiceProvider\AbstractServiceProvider implements \WHMCS\Route\Contracts\ProviderInterface
{
    use \WHMCS\Route\ProviderTrait;
    public function register()
    {
    }
    public function getRoutes()
    {
        return array("/api/v1" => array("attributes" => array("authentication" => "api", "authorization" => "api"), array("method" => array("GET", "POST"), "name" => "api-v1-action", "path" => "/{action}", "handle" => array("WHMCS\\Api\\ApplicationSupport\\Route\\Middleware\\HandleProcessor", "process"))), "/includes" => array("attributes" => array("authentication" => "api", "authorization" => "api"), array("method" => array("GET", "POST"), "name" => "api-legacy", "path" => "/api.php", "handle" => array("WHMCS\\Api\\ApplicationSupport\\Route\\Middleware\\HandleProcessor", "process"))));
    }
    public function registerRoutes(\FastRoute\RouteCollector $routeCollector)
    {
        $this->addRouteGroups($routeCollector, $this->getRoutes());
    }
}

?>