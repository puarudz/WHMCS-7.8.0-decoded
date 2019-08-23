<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Domain;

class DomainRouteProvider implements \WHMCS\Route\Contracts\DeferredProviderInterface
{
    use \WHMCS\Route\AdminProviderTrait;
    public function getRoutes()
    {
        $routes = array("/admin/domains" => array("attributes" => array("authentication" => "admin"), array("method" => array("GET", "POST"), "name" => "admin-domains-index", "path" => "", "handle" => array("WHMCS\\Admin\\Domain\\DomainController", "index"), "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("List Domains"));
        }), array("name" => "admin-domains-ssl-check", "method" => array("POST"), "path" => "/ssl-check", "handle" => array("WHMCS\\Admin\\Domain\\DomainController", "sslCheck"), "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->requireCsrfToken();
        })));
        return $routes;
    }
    public function getDeferredRoutePathNameAttribute()
    {
        return "admin-domains-";
    }
}

?>