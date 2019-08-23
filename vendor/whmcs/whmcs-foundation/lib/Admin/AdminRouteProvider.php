<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin;

class AdminRouteProvider implements \WHMCS\Route\Contracts\ProviderInterface
{
    use \WHMCS\Route\AdminProviderTrait;
    public function getRoutes()
    {
        $adminRoutes = array("/admin/account" => new Account\AccountRouteProvider(), "/admin/apps" => new Apps\AppsRouteProvider(), "/admin/setup/notifications" => new Setup\Notifications\NotificationsRouteProvider(), "/admin/setup/general/uripathmgmt" => array(array("method" => array("GET", "POST"), "name" => "dev-test", "path" => "/view", "handle" => array("\\WHMCS\\Admin\\Setup\\General\\UriManagement\\ConfigurationController", "view"))), "/admin/setup/payments" => array(array("method" => array("POST"), "name" => "admin-setup-payments-deletelocalcards", "path" => "/deletelocalcards", "handle" => array("WHMCS\\Admin\\Client\\PayMethod\\PayMethodController", "clearLocalCardPayMethods"), "authentication" => "admin", "authorization" => function () {
            return (new ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Configure General Settings"))->requireCsrfToken();
        })), "/admin/setup/payments/gateways" => new Setup\Payments\GatewaysRouteProvider(), "/admin/setup/payments/tax" => new Setup\Payments\TaxRouteProvider(), "/admin/setup/servers" => new Server\ServerRouteProvider(), "/admin/setup/storage" => new Setup\Storage\StorageRouteProvider(), "/admin/setup/auth" => new Setup\Authentication\AuthRouteProvider(), "/admin/setup/authn" => new Setup\Authentication\Client\RemoteAuthRouteProvider(), "/admin/setup/authz" => new Setup\Authorization\AuthorizationRouteProvider(), "/admin/setup" => array(array("method" => array("GET"), "name" => "admin-setup-index", "path" => "", "authentication" => "admin", "handle" => array("\\WHMCS\\Admin\\Setup\\OverviewController", "index"))), "/admin/services" => new Service\ServiceRouteProvider(), "/admin/addons" => new Addon\AddonRouteProvider(), "/admin/domains" => new Domain\DomainRouteProvider(), "/admin/utilities/system" => new Utilities\System\SystemRouteProvider(), "/admin/utilities/tools" => new Utilities\Tools\ToolsRouteProvider(), "/admin/help" => new Help\HelpRouteProvider(), "/admin/search" => array(array("method" => array("GET", "POST"), "name" => "admin-search-client", "path" => "/client", "handle" => array("\\WHMCS\\Admin\\Search\\Controller\\ClientController", "searchRequest"), "authentication" => "admin", "authorization" => function () {
            return (new ApplicationSupport\Route\Middleware\Authorization())->setRequireAnyPermission(array("Add/Edit Client Notes", "Add New Order", "Edit Clients Details", "Edit Transaction", "List Invoices", "List Support Tickets", "List Transactions", "Manage Billable Items", "Manage Quotes", "Open New Ticket", "View Activity Log", "View Billable Items", "View Clients Domains", "View Clients Notes", "View Clients Products/Services", "View Clients Summary", "View Email Message Log", "View Orders", "View Reports", "View Support Ticket"));
        }), array("method" => array("GET", "POST"), "name" => "admin-search-client-contacts", "path" => "/{clientId:\\d+}/contacts", "handle" => array("WHMCS\\Admin\\Search\\Controller\\ContactController", "searchRequest"), "authentication" => "admin", "authorization" => function () {
            return (new ApplicationSupport\Route\Middleware\Authorization())->setRequireAnyPermission(array("Add/Edit Client Notes", "Add New Order", "Edit Clients Details", "Edit Transaction", "List Invoices", "List Support Tickets", "List Transactions", "Manage Billable Items", "Manage Quotes", "Open New Ticket", "View Activity Log", "View Billable Items", "View Clients Domains", "View Clients Notes", "View Clients Products/Services", "View Clients Summary", "View Email Message Log", "View Orders", "View Reports", "View Support Ticket"));
        }), array("method" => array("POST"), "name" => "admin-search-intellisearch", "path" => "/intellisearch", "handle" => array("WHMCS\\Admin\\Search\\Controller\\IntelligentSearchController", "searchRequest"), "authentication" => "admin", "authorization" => function () {
            return (new ApplicationSupport\Route\Middleware\Authorization())->requireCsrfToken();
        }), array("method" => array("POST"), "name" => "admin-search-intellisearch-settings-autosearch", "path" => "/intellisearch/settings/autosearch", "handle" => array("WHMCS\\Admin\\Search\\Controller\\IntelligentSearchController", "setAutoSearch"), "authentication" => "admin", "authorization" => function () {
            return (new ApplicationSupport\Route\Middleware\Authorization())->requireCsrfToken();
        })), "/admin/billing" => new Billing\BillingRouteProvider(), "/admin/client" => new Client\ClientRouteProvider(), "/admin" => array(array("method" => array("POST"), "name" => "admin-notes-save", "path" => "/profile/notes", "authentication" => "admin", "authorization" => function () {
            return (new ApplicationSupport\Route\Middleware\Authorization())->requireCsrfToken();
        }, "handle" => array("WHMCS\\Admin\\Controller\\HomepageController", "saveNotes")), array("method" => array("GET", "POST"), "name" => "admin-widget-refresh", "path" => "/widget/refresh", "authentication" => "admin", "authorization" => function () {
            return (new ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Main Homepage"));
        }, "handle" => array("WHMCS\\Admin\\Controller\\HomepageController", "refreshWidget")), array("method" => array("POST"), "name" => "admin-widget-order", "path" => "/widget/order", "authentication" => "admin", "authorization" => function () {
            return (new ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Main Homepage"))->requireCsrfToken();
        }, "handle" => array("WHMCS\\Admin\\Controller\\HomepageController", "orderWidgets")), array("method" => array("GET", "POST"), "name" => "admin-widget-display-toggle", "path" => "/widget/display/toggle/{widget:\\w+}", "authentication" => "admin", "authorization" => function () {
            return (new ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Main Homepage"));
        }, "handle" => array("WHMCS\\Admin\\Controller\\HomepageController", "toggleWidgetDisplay")), array("method" => array("GET"), "name" => "admin-license-required", "path" => "/license-required", "authentication" => "admin", "handle" => array("WHMCS\\Admin\\Utilities\\Assent\\Controller\\LicenseController", "licensedRequired")), array("method" => array("POST"), "name" => "admin-license-update-key", "path" => "/license-update-key", "authentication" => "admin", "authorization" => function () {
            return (new ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Configure General Settings"))->requireCsrfToken();
        }, "handle" => array("WHMCS\\Admin\\Utilities\\Assent\\Controller\\LicenseController", "updateLicenseKey")), array("method" => array("GET"), "name" => "admin-eula-required", "path" => "/eula-required", "authentication" => "admin", "handle" => array("WHMCS\\Admin\\Utilities\\Assent\\Controller\\EulaController", "eulaAcceptanceRequired")), array("method" => array("POST"), "name" => "admin-eula-accept", "path" => "/eula-accept", "authentication" => "admin", "authorization" => function () {
            return (new ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Configure General Settings"))->requireCsrfToken();
        }, "handle" => array("WHMCS\\Admin\\Utilities\\Assent\\Controller\\EulaController", "acceptEula")), array("method" => array("GET", "POST"), "name" => "admin-login", "path" => "/login[.php]", "handle" => array("\\WHMCS\\Admin\\Controller\\LoginController", "viewLoginForm")), array("method" => array("GET", "POST"), "name" => "admin-homepage", "path" => "/[index.php]", "authorization" => function () {
            return (new ApplicationSupport\Route\Middleware\Authorization())->setRequireAnyPermission(array("Main Homepage", "Support Center Overview"));
        }, "authentication" => "admin", "handle" => array("\\WHMCS\\Admin\\Controller\\HomepageController", "index")), array("method" => array("GET"), "name" => "admin-mentions", "path" => "/mentions", "authentication" => "admin", "authorization" => function () {
            return (new ApplicationSupport\Route\Middleware\Authorization())->setRequireAnyPermission(array("View Support Ticket", "Add/Edit Client Notes"));
        }, "handle" => array("\\WHMCS\\Admin\\Controller\\HomepageController", "mentions")), array("method" => array("POST"), "name" => "admin-marketing-consent-convert", "path" => "/marketing/convert", "authentication" => "admin", "authorization" => function () {
            return (new ApplicationSupport\Route\Middleware\Authorization())->setRequireAllPermission(array("Mass Mail"));
        }, "handle" => array("\\WHMCS\\Admin\\Controller\\HomepageController", "marketingConversion")), array("method" => array("POST"), "name" => "admin-tld-mass-configuration", "path" => "/tld/mass-configuration", "authentication" => "admin", "authorization" => function () {
            return (new ApplicationSupport\Route\Middleware\Authorization())->requireCsrfToken()->setRequireAllPermission(array("Configure Domain Pricing"));
        }, "handle" => array("WHMCS\\Admin\\Setup\\Tld\\TldController", "massConfiguration")), array("method" => array("POST"), "name" => "admin-dismiss-global-warning", "path" => "/dismiss-global-warning", "authentication" => "admin", "authorization" => function () {
            return (new ApplicationSupport\Route\Middleware\Authorization())->requireCsrfToken();
        }, "handle" => array("WHMCS\\Admin\\Controller\\GlobalWarningController", "dismiss")), array("method" => array("POST"), "name" => "admin-dismiss-marketconnect-promotions", "path" => "/dismiss-marketconnect-promo", "authentication" => "admin", "authorization" => function () {
            return (new ApplicationSupport\Route\Middleware\Authorization())->requireCsrfToken();
        }, "handle" => array("WHMCS\\Admin\\Controller\\HomepageController", "dismissMarketConnectProductPromo"))), "/admin/stripe" => new \WHMCS\Module\Gateway\Stripe\Admin\StripeRouteProvider());
        return $adminRoutes;
    }
}

?>