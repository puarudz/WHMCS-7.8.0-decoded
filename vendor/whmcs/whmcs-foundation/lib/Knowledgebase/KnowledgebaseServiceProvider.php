<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Knowledgebase;

class KnowledgebaseServiceProvider implements \WHMCS\Route\Contracts\ProviderInterface
{
    use \WHMCS\Route\ProviderTrait;
    protected function getRoutes()
    {
        return array("/knowledgebase" => array(array("name" => "knowledgebase-article-view", "method" => array("GET", "POST"), "path" => "/{id:\\d+}[/{slug}.html]", "handle" => array("\\WHMCS\\Knowledgebase\\Controller\\Article", "view")), array("name" => "knowledgebase-category-view", "method" => "GET", "path" => "/{categoryId:\\d+}/{categoryName}", "handle" => array("\\WHMCS\\Knowledgebase\\Controller\\Category", "view")), array("name" => "knowledgebase-tag-view", "method" => "GET", "path" => "/tag/{tag}", "handle" => array("\\WHMCS\\Knowledgebase\\Controller\\Knowledgebase", "viewTag")), array("name" => "knowledgebase-search", "method" => array("GET", "POST"), "path" => "/search[/{search}]", "handle" => array("\\WHMCS\\Knowledgebase\\Controller\\Knowledgebase", "search")), array("name" => "knowledgebase-index", "method" => "GET", "path" => "", "handle" => array("\\WHMCS\\Knowledgebase\\Controller\\Knowledgebase", "index"))));
    }
    public function registerRoutes(\FastRoute\RouteCollector $routeCollector)
    {
        $this->addRouteGroups($routeCollector, $this->getRoutes());
    }
    public function register()
    {
    }
}

?>