<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Server;

class ServerRouteProvider implements \WHMCS\Route\Contracts\DeferredProviderInterface
{
    use \WHMCS\Route\AdminProviderTrait;
    public function getRoutes()
    {
        $routes = array("/admin/setup/servers" => array("attributes" => array("authentication" => "admin"), array("method" => array("POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "meta-refresh", "path" => "/meta/refresh", "handle" => array("WHMCS\\Admin\\Server\\ServerController", "refreshRemoteData"), "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Configure Servers"));
        })));
        return $routes;
    }
    public function getDeferredRoutePathNameAttribute()
    {
        return "admin-setup-servers-";
    }
}

?>