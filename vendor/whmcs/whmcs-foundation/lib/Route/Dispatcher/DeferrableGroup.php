<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Route\Dispatcher;

class DeferrableGroup extends \FastRoute\Dispatcher\GroupCountBased
{
    protected $collector = NULL;
    protected $loadedDeferredGroup = array();
    public function __construct(\FastRoute\RouteCollector $data)
    {
        $this->collector = $data;
        parent::__construct($data->getData());
    }
    public function dispatch($httpMethod, $uri)
    {
        list($this->staticRouteMap, $this->variableRouteData) = $this->collector->getData();
        $route = parent::dispatch($httpMethod, $uri);
        if ($route[0] == static::NOT_FOUND) {
            $deferredRoute = $this->deferredDispatch($httpMethod, $uri);
            if ($deferredRoute[0] == static::FOUND) {
                $handle = $deferredRoute[1];
                if ($handle instanceof \WHMCS\Route\Contracts\ProviderInterface) {
                    $handle->registerRoutes($this->collector);
                    list($this->staticRouteMap, $this->variableRouteData) = $this->collector->getData();
                    $route = $this->dispatch($httpMethod, $uri);
                }
            }
        }
        return $route;
    }
    public function deferredDispatch($httpMethod, $uri)
    {
        $uriSegments = explode("/", $uri);
        while (!empty($uriSegments)) {
            $uri = implode("/", $uriSegments);
            if (!in_array($uri, $this->loadedDeferredGroup)) {
                try {
                    $route = parent::dispatch($httpMethod, "/DEFERRED_GROUP" . $uri);
                    if ($route[0] == static::FOUND) {
                        $this->loadedDeferredGroup[] = $uri;
                        array_pop($uriSegments);
                        $this->loadedDeferredGroup[] = implode("/", $uriSegments);
                        return $route;
                    }
                } catch (\Exception $e) {
                }
            }
            array_pop($uriSegments);
        }
        return array(static::NOT_FOUND);
    }
}

?>