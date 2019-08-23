<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Apps;

class AppsRouteProvider implements \WHMCS\Route\Contracts\DeferredProviderInterface
{
    use \WHMCS\Route\AdminProviderTrait;
    public function getRoutes()
    {
        $remoteAuthRoutes = array("/admin/apps" => array("attributes" => array("authentication" => "admin", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Apps and Integrations"));
        }), array("method" => array("GET"), "name" => $this->getDeferredRoutePathNameAttribute() . "index", "path" => "", "handle" => array("WHMCS\\Admin\\Apps\\AppsController", "index")), array("method" => array("GET"), "name" => $this->getDeferredRoutePathNameAttribute() . "browse", "path" => "/browse", "handle" => array("WHMCS\\Admin\\Apps\\AppsController", "jumpBrowse")), array("method" => array("GET"), "name" => $this->getDeferredRoutePathNameAttribute() . "browse-category", "path" => "/browse/{category}", "handle" => array("WHMCS\\Admin\\Apps\\AppsController", "jumpBrowse")), array("method" => array("POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "featured", "path" => "/featured", "handle" => array("WHMCS\\Admin\\Apps\\AppsController", "featured")), array("method" => array("GET"), "name" => $this->getDeferredRoutePathNameAttribute() . "active", "path" => "/active", "handle" => array("WHMCS\\Admin\\Apps\\AppsController", "jumpActive")), array("method" => array("POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "active", "path" => "/active", "handle" => array("WHMCS\\Admin\\Apps\\AppsController", "active")), array("method" => array("GET"), "name" => $this->getDeferredRoutePathNameAttribute() . "search", "path" => "/search", "handle" => array("WHMCS\\Admin\\Apps\\AppsController", "jumpSearch")), array("method" => array("POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "search", "path" => "/search", "handle" => array("WHMCS\\Admin\\Apps\\AppsController", "search")), array("method" => array("POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "category", "path" => "/browse/{category}", "handle" => array("WHMCS\\Admin\\Apps\\AppsController", "category")), array("method" => array("POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "info", "path" => "/app/{moduleSlug}", "handle" => array("WHMCS\\Admin\\Apps\\AppsController", "infoModal")), array("method" => array("GET"), "name" => $this->getDeferredRoutePathNameAttribute() . "logo", "path" => "/logo/{moduleSlug}", "handle" => array("WHMCS\\Admin\\Apps\\AppsController", "logo"))));
        return $remoteAuthRoutes;
    }
    public function getDeferredRoutePathNameAttribute()
    {
        return "admin-apps-";
    }
}

?>