<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Utilities\System;

class SystemRouteProvider implements \WHMCS\Route\Contracts\DeferredProviderInterface
{
    use \WHMCS\Route\AdminProviderTrait;
    public function getRoutes()
    {
        $routes = array("/admin/utilities/system" => array("attributes" => array("authentication" => "admin"), array("method" => array("GET"), "name" => "admin-utilities-system-phpcompat", "path" => "/php-compat", "handle" => array("WHMCS\\Admin\\Utilities\\System\\PhpCompat\\PhpCompatController", "index"), "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("View PHP Info"));
        }), array("method" => array("POST"), "name" => "admin-utilities-system-phpcompat-scan", "path" => "/php-compat/scan", "handle" => array("WHMCS\\Admin\\Utilities\\System\\PhpCompat\\PhpCompatController", "scan"), "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("View PHP Info"))->requireCsrfToken();
        })));
        return $routes;
    }
    public function getDeferredRoutePathNameAttribute()
    {
        return "admin-utilities-system-";
    }
}

?>