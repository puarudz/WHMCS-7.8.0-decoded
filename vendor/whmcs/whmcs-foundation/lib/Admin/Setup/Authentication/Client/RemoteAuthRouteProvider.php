<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Setup\Authentication\Client;

class RemoteAuthRouteProvider implements \WHMCS\Route\Contracts\DeferredProviderInterface
{
    use \WHMCS\Route\AdminProviderTrait;
    public function getRoutes()
    {
        $remoteAuthRoutes = array("/admin/setup/authn" => array("attributes" => array("authentication" => "adminConfirmation", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Configure Sign-In Integration"))->requireCsrfToken();
        }), array("method" => array("GET", "POST"), "name" => "admin-setup-authn-view", "path" => "/view", "handle" => array("\\WHMCS\\Admin\\Setup\\Authentication\\Client\\RemoteProviderController", "viewProviderSettings")), array("method" => array("POST"), "name" => "admin-setup-authn-deactivate", "path" => "/deactivate", "handle" => array("\\WHMCS\\Admin\\Setup\\Authentication\\Client\\RemoteProviderController", "deactivate")), array("method" => array("POST"), "name" => "admin-setup-authn-activate", "path" => "/activate", "handle" => array("\\WHMCS\\Admin\\Setup\\Authentication\\Client\\RemoteProviderController", "activate")), array("method" => array("POST"), "name" => "admin-setup-authn-delete_account_link", "path" => "/delete_account_link", "handle" => array("\\WHMCS\\Admin\\Setup\\Authentication\\Client\\RemoteProviderController", "deleteAccountLink"), "authentication" => "admin", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAnyPermission(array("Edit Clients Details"))->requireCsrfToken();
        })));
        return $remoteAuthRoutes;
    }
    public function getDeferredRoutePathNameAttribute()
    {
        return "admin-setup-authn-";
    }
}

?>