<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Route;

class RouteServiceProvider extends \WHMCS\Application\Support\ServiceProvider\AbstractServiceProvider
{
    use ProviderTrait;
    public function register()
    {
        $container = $this->app;
        $container->singleton("Frontend\\Dispatcher", function () use($container) {
            return new \Middlewares\Utils\Dispatcher(array(new Middleware\RoutableRequestQueryUri(), new Middleware\RoutableRequestUri(), new Middleware\RoutableAdminRequestUri(), new Middleware\RoutableClientModuleRequest(), new Middleware\RoutableApiRequestUri(false), new Middleware\RoutePathMatch(), new Middleware\BackendDispatch()));
        });
        $container->singleton("Backend\\Dispatcher\\Api", function () use($container) {
            return new \Middlewares\Utils\Dispatcher(array(new \WHMCS\Api\ApplicationSupport\Route\Middleware\ApiLog(), new \WHMCS\Api\ApplicationSupport\Route\Middleware\BackendPsr7Response(), new \WHMCS\Api\ApplicationSupport\Route\Middleware\SystemAccessControl(), new \WHMCS\Api\ApplicationSupport\Route\Middleware\ActionFilter(), $container->make("Route\\Authentication"), $container->make("Route\\Authorization"), new \WHMCS\Api\ApplicationSupport\Route\Middleware\ActionResponseFormat(), new \WHMCS\Api\ApplicationSupport\Route\Middleware\HandleProcessor()));
        });
        $container->singleton("Backend\\Dispatcher\\Admin", function () use($container) {
            return new \Middlewares\Utils\Dispatcher(array(new Middleware\BackendPsr7Response(), new \WHMCS\Admin\ApplicationSupport\Route\Middleware\DirectoryValidation(), $container->make("Route\\Authentication"), $container->make("Route\\Authorization"), new \WHMCS\Admin\ApplicationSupport\Route\Middleware\Assent(), new Middleware\HandleProcessor()));
        });
        $container->singleton("Backend\\Dispatcher\\Client", function () use($container) {
            return new \Middlewares\Utils\Dispatcher(array($container->make("Route\\Authorization"), new Middleware\HandleProcessor()));
        });
        $container->singleton("Route\\Router", function () use($container) {
            $router = new \Middlewares\FastRoute($this->app->make("Route\\Dispatch"));
            $router->resolver($container);
            return $router;
        });
        $container->singleton("Route\\RouteCollector", function () {
            $parser = new \FastRoute\RouteParser\Std();
            $generator = new \FastRoute\DataGenerator\GroupCountBased();
            return new \FastRoute\RouteCollector($parser, $generator);
        });
        $container->singleton("Route\\Dispatch", function () use($container) {
            $routeCollector = $container->make("Route\\RouteCollector");
            $this->addRouteGroups($routeCollector, $this->standardRoutes());
            return new Dispatcher\DeferrableGroup($routeCollector);
        });
        $container->singleton("Route\\UriPath", function () {
            return new UriPath();
        });
        $container->singleton("Route\\Authorization", function () {
            return new Middleware\AuthorizationProxy();
        });
        $container->singleton("Route\\ResponseType", function () {
            return new \WHMCS\Http\Message\ResponseFactory();
        });
        $container->singleton("Route\\Authentication", function () {
            return new Middleware\AuthenticationProxy();
        });
    }
    protected function standardRoutes()
    {
        return array("/resources/test" => array(array("method" => array("GET", "POST"), "path" => "/detect-route-environment", "handle" => function ($request) {
            $controller = new \WHMCS\Admin\Setup\General\UriManagement\ConfigurationController(\WHMCS\Admin\Setup\General\UriManagement\ConfigurationController::PATH_COMPARISON_TEST);
            return $controller->detectRouteEnvironment($request);
        }), array("method" => array("GET", "POST"), "path" => "/index.php[/detect-route-environment]", "handle" => function ($request) {
            $controller = new \WHMCS\Admin\Setup\General\UriManagement\ConfigurationController(\WHMCS\Admin\Setup\General\UriManagement\ConfigurationController::PATH_COMPARISON_TEST);
            return $controller->detectRouteEnvironment($request);
        })), "/admin" => new \WHMCS\Admin\AdminRouteProvider(), "" => array(array("method" => array("GET", "POST"), "path" => "/detect-route-environment", "handle" => function ($request) {
            $controller = new \WHMCS\Admin\Setup\General\UriManagement\ConfigurationController(\WHMCS\Admin\Setup\General\UriManagement\ConfigurationController::PATH_COMPARISON_INDEX);
            return $controller->detectRouteEnvironment($request);
        }), array("method" => array("GET", "POST"), "path" => "/index.php/detect-route-environment", "handle" => function ($request) {
            $controller = new \WHMCS\Admin\Setup\General\UriManagement\ConfigurationController(\WHMCS\Admin\Setup\General\UriManagement\ConfigurationController::PATH_COMPARISON_INDEX);
            return $controller->detectRouteEnvironment($request);
        }), array("name" => "route-not-defined", "path" => "/route-not-defined", "method" => array("GET", "POST"), "handle" => function (\Psr\Http\Message\ServerRequestInterface $request) {
            $response = new \WHMCS\ClientArea();
            $response->setPageTitle("404 - Unknown Route Path");
            $response->setTemplate("error/unknown-routepath");
            $referrer = "";
            if (!empty($request->getServerParams()["HTTP_REFERER"])) {
                $referrer = $request->getServerParams()["HTTP_REFERER"];
            }
            $response->assign("referrer", $referrer);
            return $response->withStatus(404);
        }), array("name" => "clientarea-homepage", "method" => array("GET", "POST"), "path" => "/", "handle" => array("\\WHMCS\\ClientArea\\ClientAreaController", "homePage")), array("name" => "clientarea-index", "method" => array("GET", "POST"), "path" => "/index.php", "handle" => array("\\WHMCS\\ClientArea\\ClientAreaController", "homePage"))));
    }
}

?>