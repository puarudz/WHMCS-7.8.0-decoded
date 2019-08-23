<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Authentication\Remote;

class ServiceProvider extends \WHMCS\Application\Support\ServiceProvider\AbstractServiceProvider implements \WHMCS\Route\Contracts\ProviderInterface
{
    use \WHMCS\Route\ProviderTrait;
    public function register()
    {
    }
    public function registerRoutes(\FastRoute\RouteCollector $routeCollector)
    {
        $this->addRouteGroups($routeCollector, $this->getClientAreaManagementRoutes());
        $this->addRouteGroups($routeCollector, $this->getProviderRoutes());
    }
    private function getClientAreaManagementRoutes()
    {
        return array("/auth/manage/client" => array(array("name" => "auth-manage-client-delete", "method" => "POST", "path" => "/delete/[{authnid:\\d+}]", "handle" => array("WHMCS\\Authentication\\Remote\\Management\\Client\\Controller", "delete")), array("name" => "auth-manage-client-links", "method" => "GET", "path" => "/links", "handle" => array("WHMCS\\Authentication\\Remote\\Management\\Client\\Controller", "getLinks"))));
    }
    private function getProviderRoutes()
    {
        return array("/auth/provider/google_signin" => array(array("name" => "auth-provider-google_signin-finalize", "method" => "POST", "path" => "/finalize", "handle" => array("WHMCS\\Authentication\\Remote\\Providers\\Google\\GoogleSignin", "finalizeSignin"))), "/auth/provider/facebook_signin" => array(array("name" => "auth-provider-facebook_signin-finalize", "method" => "POST", "path" => "/finalize", "handle" => array("WHMCS\\Authentication\\Remote\\Providers\\Facebook\\FacebookSignin", "finalizeSignin"))), "/auth/provider/twitter_oauth" => array(array("name" => "auth-provider-twitter_oauth-authorize", "method" => "POST", "path" => "/authorize", "handle" => array("WHMCS\\Authentication\\Remote\\Providers\\Twitter\\TwitterOauth", "authorizeSignin")), array("name" => "auth-provider-twitter_oauth-callback", "method" => "GET", "path" => "/callback", "handle" => array("WHMCS\\Authentication\\Remote\\Providers\\Twitter\\TwitterOauth", "signinCallback"))));
    }
}

?>