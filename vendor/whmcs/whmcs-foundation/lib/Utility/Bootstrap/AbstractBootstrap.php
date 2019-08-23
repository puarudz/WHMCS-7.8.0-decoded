<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Utility\Bootstrap;

abstract class AbstractBootstrap
{
    protected static function checkBareMinimumRequirements()
    {
    }
    public static function boot()
    {
        static::checkBareMinimumRequirements();
        $container = static::factoryContainer();
        static::facadeDiContainer($container);
        return $container;
    }
    protected static function facadeDiContainer(\WHMCS\Container $container)
    {
        \Illuminate\Support\Facades\Facade::setFacadeApplication($container);
        if (!class_exists("DI")) {
            class_alias("WHMCS\\Application\\Support\\Facades\\Di", "DI");
            $container->instance("di", $container);
        } else {
            if (!defined("TESTDIR_DATA")) {
                throw new \WHMCS\Exception\Fatal("Instance-Of Container cannot be redefined");
            }
            \DI::clearResolvedInstances();
            \DI::swap($container);
        }
    }
    protected static function factoryContainer()
    {
        return new \WHMCS\Container();
    }
    public static function registerServices(\WHMCS\Container $container, array $servicesToRegister)
    {
        foreach ($servicesToRegister as $service) {
            $serviceInstance = $container->register($service);
            if ($serviceInstance instanceof \WHMCS\Route\Contracts\ProviderInterface) {
                $routeCollector = $container->make("Route\\RouteCollector");
                $serviceInstance->registerRoutes($routeCollector);
            }
        }
    }
    protected static function bindSingletons(\WHMCS\Container $container, array $singletonsToBind)
    {
        foreach ($singletonsToBind as $key => $callable) {
            $container->singleton($key, $callable);
        }
    }
    protected static function bindInstances(\WHMCS\Container $container, array $instancesToBind)
    {
        foreach ($instancesToBind as $key => $concrete) {
            $container->instance($key, $concrete);
        }
    }
    protected static function defineClassAliases(array $classAliases)
    {
        foreach ($classAliases as $className => $alias) {
            if (!class_exists($alias)) {
                class_alias($className, $alias);
            } else {
                if (!defined("TESTDIR_DATA")) {
                    throw new \WHMCS\Exception\Fatal(sprintf("Alias \"%s\" cannot be redefined", $alias));
                }
            }
        }
    }
    public static function getSingletons()
    {
        return array();
    }
    public static function getServices()
    {
        return array();
    }
    public static function getAliases()
    {
        return array();
    }
}

?>