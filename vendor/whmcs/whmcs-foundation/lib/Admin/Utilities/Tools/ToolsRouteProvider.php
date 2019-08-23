<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Utilities\Tools;

class ToolsRouteProvider implements \WHMCS\Route\Contracts\DeferredProviderInterface
{
    use \WHMCS\Route\AdminProviderTrait;
    public function getRoutes()
    {
        $routes = array("/admin/utilities/tools" => array("attributes" => array("authentication" => "admin"), array("method" => array("POST"), "name" => "admin-utilities-tools-serversync-analyse", "path" => "/serversync/{serverid}", "handle" => array("WHMCS\\Admin\\Utilities\\Tools\\ServerSync\\Controller", "analyse"), "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("WHM Import Script"));
        }), array("method" => array("POST"), "name" => "admin-utilities-tools-serversync-review", "path" => "/serversync/{serverid}/process", "handle" => array("WHMCS\\Admin\\Utilities\\Tools\\ServerSync\\Controller", "process"), "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("WHM Import Script"));
        })));
        return $this->mutateAdminRoutesForCustomDirectory($routes);
    }
    public function getDeferredRoutePathNameAttribute()
    {
        return "admin-utilities-tools-";
    }
    public function registerRoutes(\FastRoute\RouteCollector $routeCollector)
    {
        $this->addRouteGroups($routeCollector, $this->getRoutes());
    }
}

?>