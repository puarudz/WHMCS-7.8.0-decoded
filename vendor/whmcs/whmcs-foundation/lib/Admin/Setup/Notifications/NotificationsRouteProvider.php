<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Setup\Notifications;

class NotificationsRouteProvider implements \WHMCS\Route\Contracts\DeferredProviderInterface
{
    use \WHMCS\Route\AdminProviderTrait;
    public function getRoutes()
    {
        $routes = array("/admin/setup/notifications" => array("attributes" => array("authentication" => "admin", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Manage Notifications"))->requireCsrfToken();
        }), array("method" => array("GET", "POST"), "name" => "admin-setup-notifications-overview", "path" => "/overview", "handle" => array("WHMCS\\Admin\\Setup\\Notifications\\NotificationsController", "index")), array("method" => array("GET"), "name" => "admin-setup-notifications-list", "path" => "/list", "handle" => array("WHMCS\\Admin\\Setup\\Notifications\\NotificationsController", "listNotifications")), array("method" => array("POST"), "name" => "admin-setup-notifications-rule-create", "path" => "/rule", "handle" => array("WHMCS\\Admin\\Setup\\Notifications\\NotificationsController", "manageRule"), "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Manage Notifications"));
        }), array("method" => array("POST"), "name" => "admin-setup-notifications-rule-delete", "path" => "/rule/delete", "handle" => array("WHMCS\\Admin\\Setup\\Notifications\\NotificationsController", "deleteRule")), array("method" => array("POST"), "name" => "admin-setup-notifications-rule-duplicate", "path" => "/rule/duplicate/{rule_id}", "handle" => array("WHMCS\\Admin\\Setup\\Notifications\\NotificationsController", "duplicateRule"), "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Manage Notifications"));
        }), array("method" => array("POST"), "name" => "admin-setup-notifications-rule-status", "path" => "/rule/status", "handle" => array("WHMCS\\Admin\\Setup\\Notifications\\NotificationsController", "setRuleStatus")), array("method" => array("POST"), "name" => "admin-setup-notifications-rule-save", "path" => "/rule/save", "handle" => array("WHMCS\\Admin\\Setup\\Notifications\\NotificationsController", "saveRule")), array("method" => array("POST"), "name" => "admin-setup-notifications-rule-edit", "path" => "/rule/{rule_id}", "handle" => array("WHMCS\\Admin\\Setup\\Notifications\\NotificationsController", "manageRule"), "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Manage Notifications"));
        }), array("method" => array("POST"), "name" => "admin-setup-notifications-provider-dynamic-field", "path" => "/provider/field", "handle" => array("WHMCS\\Admin\\Setup\\Notifications\\NotificationsController", "getDynamicField")), array("method" => array("POST"), "name" => "admin-setup-notifications-provider-disable", "path" => "/provider/disable", "handle" => array("WHMCS\\Admin\\Setup\\Notifications\\NotificationsController", "disableProvider")), array("method" => array("POST"), "name" => "admin-setup-notifications-provider", "path" => "/provider/{provider}", "handle" => array("WHMCS\\Admin\\Setup\\Notifications\\NotificationsController", "manageProvider"), "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Manage Notifications"));
        }), array("method" => array("POST"), "name" => "admin-setup-notifications-provider-save", "path" => "/provider/{provider}/save", "handle" => array("WHMCS\\Admin\\Setup\\Notifications\\NotificationsController", "saveProvider")), array("method" => array("GET"), "name" => "admin-setup-notifications-providers-status", "path" => "/providers/status", "handle" => array("WHMCS\\Admin\\Setup\\Notifications\\NotificationsController", "getProvidersStatus"))));
        return $routes;
    }
    public function getDeferredRoutePathNameAttribute()
    {
        return "admin-setup-notifications-";
    }
}

?>