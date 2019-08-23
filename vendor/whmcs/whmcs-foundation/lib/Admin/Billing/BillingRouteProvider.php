<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Billing;

class BillingRouteProvider implements \WHMCS\Route\Contracts\DeferredProviderInterface
{
    use \WHMCS\Route\AdminProviderTrait;
    public function getRoutes()
    {
        $routes = array("/admin/billing" => array("attributes" => array("authentication" => "admin"), array("method" => array("GET", "POST"), "name" => "admin-billing-offline-cc-form", "path" => "/offline-cc/invoice/{invoice_id:\\d+}", "handle" => array("WHMCS\\Admin\\Billing\\OfflineCcController", "getForm"), "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Offline Credit Card Processing"));
        }), array("method" => array("POST"), "name" => "admin-billing-offline-cc-decrypt", "path" => "/offline-cc/invoice/{invoice_id:\\d+}/decrypt_card", "handle" => array("WHMCS\\Admin\\Billing\\OfflineCcController", "decryptCardData"), "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->requireCsrfToken()->setRequireAllPermission(array("Offline Credit Card Processing"));
        }), array("method" => array("POST"), "name" => "admin-billing-offline-cc-apply-transaction", "path" => "/offline-cc/invoice/{invoice_id:\\d+}/apply_transaction", "handle" => array("WHMCS\\Admin\\Billing\\OfflineCcController", "applyTransaction"), "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->requireCsrfToken()->setRequireAllPermission(array("Offline Credit Card Processing"));
        })));
        return $routes;
    }
    public function getDeferredRoutePathNameAttribute()
    {
        return "admin-billing-";
    }
}

?>