<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Help;

class HelpRouteProvider implements \WHMCS\Route\Contracts\DeferredProviderInterface
{
    use \WHMCS\Route\AdminProviderTrait;
    public function getRoutes()
    {
        $helpRoutes = array("/admin/help" => array("attributes" => array("authentication" => "admin", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Main Homepage"));
        }), array("method" => array("GET"), "name" => "admin-help-license", "path" => "/license", "handle" => array("\\WHMCS\\Admin\\Help\\HelpController", "viewLicense")), array("method" => array("POST"), "name" => "admin-help-license-check", "path" => "/license/check", "handle" => array("\\WHMCS\\Admin\\Help\\HelpController", "forceLicenseCheck"), "authorization" => function (\WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization $authz) {
            return $authz->requireCsrfToken();
        }), array("method" => array("POST"), "name" => "admin-help-license-upgrade-data", "path" => "/license/upgrade/data", "handle" => array("\\WHMCS\\Admin\\Help\\HelpController", "fetchLicenseUpgradeData"), "authorization" => function (\WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization $authz) {
            return $authz->requireCsrfToken();
        }), array("method" => array("POST"), "name" => "admin-help-license-upgrade-send", "path" => "/license/upgrade/send", "handle" => array("\\WHMCS\\Admin\\Help\\HelpController", "sendLicenseUpgradeRequest"), "authorization" => function (\WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization $authz) {
            return $authz->requireCsrfToken();
        })));
        return $helpRoutes;
    }
    public function getDeferredRoutePathNameAttribute()
    {
        return "admin-help-";
    }
}

?>