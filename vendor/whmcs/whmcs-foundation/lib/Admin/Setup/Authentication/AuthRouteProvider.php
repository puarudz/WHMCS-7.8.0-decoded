<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Setup\Authentication;

class AuthRouteProvider implements \WHMCS\Route\Contracts\DeferredProviderInterface
{
    use \WHMCS\Route\AdminProviderTrait;
    public function getRoutes()
    {
        $authRoutes = array("/admin/setup/auth" => array("attributes" => array("authentication" => "adminConfirmation", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Configure Two-Factor Authentication"));
        }), array("method" => array("GET", "POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "two-factor-index", "path" => "/two-factor", "handle" => array("\\WHMCS\\Admin\\Setup\\Authentication\\TwoFactorAuthController", "index")), array("method" => array("GET"), "name" => $this->getDeferredRoutePathNameAttribute() . "two-factor-status", "path" => "/two-factor/status", "handle" => array("\\WHMCS\\Admin\\Setup\\Authentication\\TwoFactorAuthController", "status")), array("method" => array("POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "two-factor-settings-save", "path" => "/two-factor/save", "handle" => array("\\WHMCS\\Admin\\Setup\\Authentication\\TwoFactorAuthController", "saveSettings")), array("method" => array("POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "two-factor-configure", "path" => "/two-factor/{module}/configure", "handle" => array("\\WHMCS\\Admin\\Setup\\Authentication\\TwoFactorAuthController", "configureModule")), array("method" => array("POST"), "name" => $this->getDeferredRoutePathNameAttribute() . "two-factor-configure-save", "path" => "/two-factor/{module}/configure/save", "handle" => array("\\WHMCS\\Admin\\Setup\\Authentication\\TwoFactorAuthController", "saveModule"))));
        return $authRoutes;
    }
    public function getDeferredRoutePathNameAttribute()
    {
        return "admin-setup-auth-";
    }
}

?>