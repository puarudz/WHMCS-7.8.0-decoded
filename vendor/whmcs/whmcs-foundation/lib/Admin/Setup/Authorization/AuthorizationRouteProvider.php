<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Setup\Authorization;

class AuthorizationRouteProvider implements \WHMCS\Route\Contracts\DeferredProviderInterface
{
    use \WHMCS\Route\AdminProviderTrait;
    public function getRoutes()
    {
        $routes = array("/admin/setup/authz" => array("attributes" => array("authentication" => "adminConfirmation", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Manage API Credentials"))->requireCsrfToken();
        }), array("method" => array("GET", "POST"), "name" => "admin-setup-authz-api-manage", "path" => "/api/manage", "handle" => array("WHMCS\\Authentication\\DeviceConfigurationController", "index")), array("method" => array("GET", "POST"), "name" => "admin-setup-authz-api-device-new", "path" => "/api/devices/new", "handle" => array("WHMCS\\Authentication\\DeviceConfigurationController", "createNew"), "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Manage API Credentials"));
        }), array("method" => array("POST"), "name" => "admin-setup-authz-api-devices-generate", "path" => "/api/devices/generate", "handle" => array("WHMCS\\Authentication\\DeviceConfigurationController", "generate")), array("method" => array("GET"), "name" => "admin-setup-authz-api-devices-list", "path" => "/api/devices", "handle" => array("WHMCS\\Authentication\\DeviceConfigurationController", "getDevices")), array("method" => array("POST"), "name" => "admin-setup-authz-api-devices-delete", "path" => "/api/devices/delete[/{id}]", "handle" => array("WHMCS\\Authentication\\DeviceConfigurationController", "delete")), array("method" => array("POST"), "name" => "admin-setup-authz-api-devices-update", "path" => "/api/devices/update[/{id}]", "handle" => array("WHMCS\\Authentication\\DeviceConfigurationController", "update")), array("method" => array("GET", "POST"), "name" => "admin-setup-authz-api-devices-manage", "path" => "/api/devices/manage[/{id}]", "handle" => array("WHMCS\\Authentication\\DeviceConfigurationController", "manage"), "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Manage API Credentials"));
        }), array("method" => array("GET"), "name" => "admin-setup-authz-api-roles-list", "path" => "/api/roles", "handle" => array("WHMCS\\Admin\\Setup\\Authorization\\Api\\RoleController", "listRoles")), array("method" => array("GET"), "name" => "admin-setup-authz-api-roles-select-options", "path" => "/api/roles/select-options", "handle" => array("WHMCS\\Admin\\Setup\\Authorization\\Api\\RoleController", "selectOptions")), array("method" => array("GET", "POST"), "name" => "admin-setup-authz-api-roles-manage", "path" => "/api/roles/manage[/{roleId}]", "handle" => array("WHMCS\\Admin\\Setup\\Authorization\\Api\\RoleController", "manage"), "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Manage API Credentials"));
        }), array("method" => array("POST"), "name" => "admin-setup-authz-api-roles-create", "path" => "/api/roles/create", "handle" => array("WHMCS\\Admin\\Setup\\Authorization\\Api\\RoleController", "create")), array("method" => array("POST"), "name" => "admin-setup-authz-api-roles-delete", "path" => "/api/roles/delete[/{roleId}]", "handle" => array("WHMCS\\Admin\\Setup\\Authorization\\Api\\RoleController", "delete")), array("method" => array("POST"), "name" => "admin-setup-authz-api-roles-update", "path" => "/api/roles/update", "handle" => array("WHMCS\\Admin\\Setup\\Authorization\\Api\\RoleController", "update"))));
        return $routes;
    }
    public function getDeferredRoutePathNameAttribute()
    {
        return "admin-setup-authz-";
    }
}

?>