<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Route;

trait ProviderTrait
{
    public function getRoutes()
    {
        return array();
    }
    public function addRoute(\FastRoute\RouteCollector $routeCollector, array $route, $group = "")
    {
        $path = $route["path"];
        $routeCollector->addRoute($route["method"], $path, $route["handle"]);
        if (isset($route["name"])) {
            if ($group) {
                $path = $group . $path;
            }
            $route["canonicalPath"] = $path;
            $this->getUriMap()->mapRoute($route);
        } else {
            if (isset($route["handle"]) && $route["handle"] instanceof Contracts\DeferredProviderInterface) {
                $handle = $route["handle"];
                $this->getUriMap()->mapRoute(array("name" => $handle->getDeferredRoutePathNameAttribute(), "canonicalPath" => $handle));
            }
        }
        if (isset($route["authentication"])) {
            $this->getAuthenticationMap()->mapRoute($route);
        }
        if (isset($route["authorization"])) {
            $this->getAuthorizationMap()->mapRoute($route);
        }
        if (isset($route["responseType"])) {
            $this->getResponseTypeMap()->mapRoute($route);
        }
    }
    public function getUriMap()
    {
        return \DI::make("Route\\UriPath");
    }
    public function getAuthenticationMap()
    {
        return \DI::make("Route\\Authentication");
    }
    public function getAuthorizationMap()
    {
        return \DI::make("Route\\Authorization");
    }
    public function getResponseTypeMap()
    {
        return \DI::make("Route\\ResponseType");
    }
    public function applyGroupLevelAttributes($routes)
    {
        if (is_array($routes) && !empty($routes["attributes"])) {
            $groupAttributes = $routes["attributes"];
            unset($routes["attributes"]);
            array_walk($routes, function (&$routeDefinition) use($groupAttributes) {
                if (is_array($routeDefinition) && isset($routeDefinition["handle"]) && is_callable($routeDefinition["handle"])) {
                    if (isset($groupAttributes["authorization"]) && isset($routeDefinition["authorization"]) && is_callable($routeDefinition["authorization"])) {
                        $groupFunc = $groupAttributes["authorization"];
                        $routeFunc = $routeDefinition["authorization"];
                        $wrapperFunc = function () use($groupFunc, $routeFunc) {
                            if (is_callable($groupFunc)) {
                                $groupBaseInstance = $groupFunc();
                            } else {
                                $groupBaseInstance = $groupFunc;
                            }
                            return $routeFunc($groupBaseInstance);
                        };
                        $routeDefinition["authorization"] = $wrapperFunc;
                    }
                    $routeDefinition = array_merge($groupAttributes, $routeDefinition);
                }
            });
        }
        return $routes;
    }
    public function addRouteGroups(\FastRoute\RouteCollector $routeCollector, array $routeGroup = array())
    {
        foreach ($routeGroup as $group => $routes) {
            if ($routes instanceof Contracts\DeferredProviderInterface) {
                $this->addDeferredRouteGroup($routeCollector, $routes, $group);
            } else {
                if ($routes instanceof Contracts\ProviderInterface) {
                    $routes->registerRoutes($routeCollector);
                } else {
                    if ($group) {
                        $routes = $this->applyGroupLevelAttributes($routes);
                        $routeCollector->addGroup($group, function (\FastRoute\RouteCollector $routeCollector) use($routes, $group) {
                            foreach ($routes as $route) {
                                $this->addRoute($routeCollector, $route, $group);
                            }
                        });
                    } else {
                        foreach ($routes as $route) {
                            $this->addRoute($routeCollector, $route);
                        }
                    }
                }
            }
        }
    }
    public function addDeferredRouteGroup(\FastRoute\RouteCollector $routeCollector, Contracts\ProviderInterface $provider, $group)
    {
        $this->addRoute($routeCollector, array("path" => "/DEFERRED_GROUP" . $group . "[/{stub_wildcard}]", "handle" => $provider, "method" => array("GET", "POST", "PUT", "DELETE")), $group);
    }
    public function registerRoutes(\FastRoute\RouteCollector $routeCollector)
    {
        $this->addRouteGroups($routeCollector, $this->getRoutes());
    }
}

?>