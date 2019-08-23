<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Service;

class ServiceRouteProvider implements \WHMCS\Route\Contracts\DeferredProviderInterface
{
    use \WHMCS\Route\AdminProviderTrait;
    public function getRoutes()
    {
        $routes = array("/admin/services" => array("attributes" => array("authentication" => "admin"), array("method" => array("GET", "POST"), "name" => "admin-services-index", "path" => "", "handle" => array("WHMCS\\Admin\\Service\\ServiceController", "index"), "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("List Services"));
        }), array("method" => array("GET", "POST"), "name" => "admin-services-shared", "path" => "/shared", "handle" => array("WHMCS\\Admin\\Service\\ServiceController", "shared"), "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("List Services"));
        }), array("method" => array("GET", "POST"), "name" => "admin-services-reseller", "path" => "/reseller", "handle" => array("WHMCS\\Admin\\Service\\ServiceController", "reseller"), "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("List Services"));
        }), array("method" => array("GET", "POST"), "name" => "admin-services-server", "path" => "/server", "handle" => array("WHMCS\\Admin\\Service\\ServiceController", "server"), "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("List Services"));
        }), array("method" => array("GET", "POST"), "name" => "admin-services-other", "path" => "/other", "handle" => array("WHMCS\\Admin\\Service\\ServiceController", "other"), "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("List Services"));
        })));
        return $routes;
    }
    public function getDeferredRoutePathNameAttribute()
    {
        return "admin-services-";
    }
}

?>