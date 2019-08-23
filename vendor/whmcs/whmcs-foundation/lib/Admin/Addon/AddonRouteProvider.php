<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Addon;

class AddonRouteProvider implements \WHMCS\Route\Contracts\DeferredProviderInterface
{
    use \WHMCS\Route\AdminProviderTrait;
    public function getRoutes()
    {
        $routes = array("/admin/addons" => array("attributes" => array("authentication" => "admin"), array("method" => array("GET", "POST"), "name" => "admin-addons-index", "path" => "", "handle" => array("WHMCS\\Admin\\Addon\\AddonController", "index"), "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("List Addons"));
        })));
        return $routes;
    }
    public function getDeferredRoutePathNameAttribute()
    {
        return "admin-addons-";
    }
}

?>