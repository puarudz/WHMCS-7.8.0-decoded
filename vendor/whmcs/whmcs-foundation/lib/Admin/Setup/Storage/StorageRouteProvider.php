<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Setup\Storage;

class StorageRouteProvider implements \WHMCS\Route\Contracts\DeferredProviderInterface
{
    use \WHMCS\Route\AdminProviderTrait;
    public function getRoutes()
    {
        $storageRoutes = array("/admin/setup/storage" => array("attributes" => array("authentication" => "adminConfirmation", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Manage Storage Settings"));
        }), array("method" => array("GET", "POST"), "name" => "admin-setup-storage-index", "path" => "/index[/{action}]", "handle" => array("WHMCS\\Admin\\Setup\\Storage\\StorageController", "index")), array("method" => array("GET", "POST"), "name" => "admin-setup-storage-edit-configuration", "path" => "/config/{id}/edit", "handle" => array("WHMCS\\Admin\\Setup\\Storage\\StorageController", "editConfiguration")), array("method" => array("POST"), "name" => "admin-setup-storage-save-configuration", "path" => "/config/{id}/save", "handle" => array("WHMCS\\Admin\\Setup\\Storage\\StorageController", "saveConfiguration"), "authorization" => function (\WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization $authz) {
            return $authz->requireCsrfToken();
        }), array("method" => array("POST"), "name" => "admin-setup-storage-duplicate-configuration", "path" => "/config/{id}/duplicate", "handle" => array("WHMCS\\Admin\\Setup\\Storage\\StorageController", "duplicateConfiguration")), array("method" => array("POST"), "name" => "admin-setup-storage-test-configuration", "path" => "/config/{id}/test", "handle" => array("WHMCS\\Admin\\Setup\\Storage\\StorageController", "testConfiguration"), "authorization" => function (\WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization $authz) {
            return $authz->requireCsrfToken();
        }), array("method" => array("POST"), "name" => "admin-setup-storage-delete-configuration", "path" => "/config/{id}/delete", "handle" => array("WHMCS\\Admin\\Setup\\Storage\\StorageController", "deleteConfiguration"), "authorization" => function (\WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization $authz) {
            return $authz->requireCsrfToken();
        }), array("method" => array("POST"), "name" => "admin-setup-storage-dismiss-error", "path" => "/config/{id}/dismiss_error", "handle" => array("WHMCS\\Admin\\Setup\\Storage\\StorageController", "dismissError"), "authorization" => function (\WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization $authz) {
            return $authz->requireCsrfToken();
        }), array("method" => array("POST"), "name" => "admin-setup-storage-migration-start", "path" => "/migration/{asset_type}/start", "handle" => array("WHMCS\\Admin\\Setup\\Storage\\StorageController", "startMigration"), "authorization" => function (\WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization $authz) {
            return $authz->requireCsrfToken();
        }), array("method" => array("POST"), "name" => "admin-setup-storage-migration-switch", "path" => "/migration/{asset_type}/switch", "handle" => array("WHMCS\\Admin\\Setup\\Storage\\StorageController", "switchAssetStorage"), "authorization" => function (\WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization $authz) {
            return $authz->requireCsrfToken();
        }), array("method" => array("POST"), "name" => "admin-setup-storage-migration-cancel", "path" => "/migration/{asset_type}/cancel", "handle" => array("WHMCS\\Admin\\Setup\\Storage\\StorageController", "cancelMigration"), "authorization" => function (\WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization $authz) {
            return $authz->requireCsrfToken();
        })));
        return $storageRoutes;
    }
    public function getDeferredRoutePathNameAttribute()
    {
        return "admin-setup-storage-";
    }
}

?>