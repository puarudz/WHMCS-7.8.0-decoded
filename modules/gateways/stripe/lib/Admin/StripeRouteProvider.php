<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Gateway\Stripe\Admin;

class StripeRouteProvider implements \WHMCS\Route\Contracts\DeferredProviderInterface
{
    use \WHMCS\Route\AdminProviderTrait;
    protected function getRoutes()
    {
        return array("/admin/stripe" => array(array("name" => $this->getDeferredRoutePathNameAttribute() . "payment-method-add", "method" => array("POST"), "path" => "/payment/admin/add", "authentication" => "admin", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->requireCsrfToken();
        }, "handle" => array("WHMCS\\Module\\Gateway\\Stripe\\StripeController", "adminAdd"))));
    }
    public function getDeferredRoutePathNameAttribute()
    {
        return "admin-stripe-";
    }
}

?>