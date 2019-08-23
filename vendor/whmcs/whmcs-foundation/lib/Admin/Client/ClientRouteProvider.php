<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Client;

class ClientRouteProvider implements \WHMCS\Route\Contracts\DeferredProviderInterface
{
    use \WHMCS\Route\AdminProviderTrait;
    public function getRoutes()
    {
        $routes = array("/admin/client" => array("attributes" => array("authentication" => "admin"), array("method" => array("POST"), "name" => "admin-client-export", "path" => "/{client_id:\\d+}/export", "handle" => array("WHMCS\\Admin\\Client\\ClientController", "export"), "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->requireCsrfToken()->setRequireAllPermission(array("Client Data Export"));
        }), array("method" => array("GET", "POST"), "name" => "admin-client-consent-history", "path" => "/{client_id:\\d+}/consent/history", "handle" => array("WHMCS\\Admin\\Client\\ProfileController", "consentHistory"), "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAnyPermission(array("Edit Clients Details"));
        }), array("method" => array("GET"), "name" => "admin-client-tickets", "path" => "/{userId:\\d+}/tickets", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("List Support Tickets"));
        }, "authentication" => "admin", "handle" => array("WHMCS\\Admin\\Client\\TicketsController", "tickets")), array("method" => array("POST"), "name" => "admin-client-tickets-close", "path" => "/{userId:\\d+}/tickets/close", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->requireCsrfToken()->setRequireAllPermission(array("List Support Tickets"));
        }, "authentication" => "admin", "handle" => array("WHMCS\\Admin\\Client\\TicketsController", "close")), array("method" => array("POST"), "name" => "admin-client-tickets-delete", "path" => "/{userId:\\d+}/tickets/delete", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->requireCsrfToken()->setRequireAllPermission(array("Delete Ticket"));
        }, "authentication" => "admin", "handle" => array("WHMCS\\Admin\\Client\\TicketsController", "delete")), array("method" => array("POST"), "name" => "admin-client-tickets-merge", "path" => "/{userId:\\d+}/tickets/merge", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->requireCsrfToken()->setRequireAllPermission(array("List Support Tickets"));
        }, "authentication" => "admin", "handle" => array("WHMCS\\Admin\\Client\\TicketsController", "merge")), array("method" => array("GET", "POST"), "name" => "admin-client-paymethods-view", "path" => "/{userId:\\d+}/paymethods/{payMethodId:\\d+}", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Manage Pay Methods"));
        }, "authentication" => "admin", "handle" => array("WHMCS\\Admin\\Client\\PayMethod\\PayMethodController", "viewPayMethod")), array("method" => array("GET", "POST"), "name" => "admin-client-paymethods-new", "path" => "/{userId:\\d+}/paymethods/new/{payMethodType:\\w+}[/{desiredStorage:\\w+}]", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Manage Pay Methods"));
        }, "authentication" => "admin", "handle" => array("WHMCS\\Admin\\Client\\PayMethod\\PayMethodController", "newPayMethodForm")), array("method" => array("POST"), "name" => "admin-client-paymethods-save", "path" => "/{userId:\\d+}/paymethods/save", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->requireCsrfToken()->setRequireAllPermission(array("Manage Pay Methods"));
        }, "authentication" => "admin", "handle" => array("WHMCS\\Admin\\Client\\PayMethod\\PayMethodController", "saveNew")), array("method" => array("POST"), "name" => "admin-client-paymethods-update", "path" => "/{userId:\\d+}/paymethods/update/{payMethodId:\\d+}", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->requireCsrfToken()->setRequireAllPermission(array("Manage Pay Methods"));
        }, "authentication" => "admin", "handle" => array("WHMCS\\Admin\\Client\\PayMethod\\PayMethodController", "updateExisting")), array("method" => array("POST"), "name" => "admin-client-paymethods-delete", "path" => "/{userId:\\d+}/paymethods/delete/{payMethodId:\\d+}", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Manage Pay Methods"));
        }, "authentication" => "admin", "handle" => array("WHMCS\\Admin\\Client\\PayMethod\\PayMethodController", "deleteExisting")), array("method" => array("POST"), "name" => "admin-client-paymethods-delete-confirm", "path" => "/{userId:\\d+}/paymethods/delete/{payMethodId:\\d+}/confirm", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->requireCsrfToken()->setRequireAllPermission(array("Manage Pay Methods"));
        }, "authentication" => "admin", "handle" => array("WHMCS\\Admin\\Client\\PayMethod\\PayMethodController", "doDeleteExisting")), array("method" => array("POST"), "name" => "admin-client-paymethods-html-rows", "path" => "/{userId:\\d+}/paymethods/html/rows", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->requireCsrfToken()->setRequireAllPermission(array("Manage Pay Methods"));
        }, "authentication" => "admin", "handle" => array("WHMCS\\Admin\\Client\\PayMethod\\PayMethodController", "payMethodsHtmlRows")), array("method" => array("POST"), "name" => "admin-client-paymethods-decrypt-cc-data", "path" => "/{userId:\\d+}/paymethods/decrypt/{payMethodId:\\d+}", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->requireCsrfToken()->setRequireAllPermission(array("Decrypt Full Credit Card Number"));
        }, "authentication" => "admin", "handle" => array("WHMCS\\Admin\\Client\\PayMethod\\PayMethodController", "decryptCcData")), array("method" => array("GET, POST"), "name" => "admin-client-profile-contacts", "path" => "/{userId:\\d+}/profile/contacts", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Edit Clients Details"));
        }, "authentication" => "admin", "handle" => array("WHMCS\\Admin\\Client\\ProfileController", "profileContacts")), array("method" => array("POST"), "name" => "admin-client-invoice-capture", "path" => "/{userId:\\d+}/invoice/{invoiceId:\\d+}/capture", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Manage Invoice"));
        }, "authentication" => "admin", "handle" => array("WHMCS\\Admin\\Client\\Invoice\\InvoiceController", "capture")), array("method" => array("POST"), "name" => "admin-client-invoice-capture-confirm", "path" => "/{userId:\\d+}/invoice/{invoiceId:\\d+}/capture/confirm", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Manage Invoice"));
        }, "authentication" => "admin", "handle" => array("WHMCS\\Admin\\Client\\Invoice\\InvoiceController", "doCapture")), array("method" => array("POST"), "name" => "admin-client-payment-remote-confirm", "path" => "/payment/remote/confirm", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->requireCsrfToken()->setRequireAllPermission(array("Update/Delete Stored Credit Card"));
        }, "authentication" => "admin", "handle" => array("WHMCS\\Admin\\Client\\PayMethod\\PayMethodController", "remoteConfirm")), array("method" => array("POST"), "name" => "admin-client-payment-remote-confirm-update", "path" => "/payment/remote/confirm/update", "authorization" => function () {
            return (new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Authorization())->requireCsrfToken()->setRequireAllPermission(array("Update/Delete Stored Credit Card"));
        }, "authentication" => "admin", "handle" => array("WHMCS\\Admin\\Client\\PayMethod\\PayMethodController", "remoteUpdate"))));
        return $routes;
    }
    public function getDeferredRoutePathNameAttribute()
    {
        return "admin-client-";
    }
}

?>