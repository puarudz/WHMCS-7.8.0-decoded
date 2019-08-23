<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Route;

class UriPath implements Contracts\MapInterface
{
    use HandleMapTrait;
    private $baseUri = "";
    protected $deferredRoutes = array();
    const MODE_REWRITE = "rewrite";
    const MODE_ACCEPTPATHINFO = "acceptpathinfo";
    const MODE_BASIC = "basic";
    const MODE_UNKNOWN = "unknown";
    const SETTING_MODE = "RouteUriPathMode";
    public function __construct($baseUri = "")
    {
        if (empty($baseUri)) {
            $this->baseUri = \WHMCS\Utility\Environment\WebHelper::getBaseUrl();
        } else {
            $this->baseUri = $baseUri;
        }
    }
    public function mapRoute($route)
    {
        $attributeName = $this->getMappedAttributeName();
        if (!isset($route["name"]) || !isset($route[$attributeName])) {
            return $this;
        }
        $this->routes[$route["name"]] = $route[$attributeName];
        return $this;
    }
    public function getMappedAttributeName()
    {
        return "canonicalPath";
    }
    public function getSafeNonRoutablePath()
    {
        $uri = new \Zend\Diactoros\Uri();
        $routePath = "/route-not-defined";
        $base = $this->baseUri;
        if (substr($base, -1) == "/") {
            $base = substr($base, -1);
        }
        $mode = $this->getMode();
        if ($mode == self::MODE_REWRITE) {
            $uri = $uri->withPath($base . $routePath);
        } else {
            if ($mode == self::MODE_ACCEPTPATHINFO) {
                $uri = $uri->withPath($base . "/index.php" . $routePath);
            } else {
                $uri = $uri->withPath($base . "/index.php")->withQuery("rp=" . $routePath);
            }
        }
        return $uri;
    }
    public function getRawPath($routeName, $routeVars)
    {
        $routePath = $this->buildRoutePath($routeName, $routeVars);
        if (substr($routePath, 0, 1) != "/") {
            $routePath = "/" . $routePath;
        }
        return $routePath;
    }
    /*
    ERROR in processing the function: Unknown opcode 164 at line 1
       em Class33.method_30()
       em Class33.method_31()
       em Class33.method_26()
       em Class33.method_27()
       em Class33.method_13()
       em Class35.method_3(Class3 class3_0, Class25 class25_0, String string_7, Boolean bool_1)
    */
    public function getRouteCollector()
    {
        return \DI::make("Route\\RouteCollector");
    }
    protected function getDeferredRoutePath($routeName)
    {
        $route = null;
        try {
            $matches = array();
            foreach ($this->routes as $key => $value) {
                if (strpos($routeName, $key) === 0) {
                    $matches[$key] = $value;
                }
            }
            if (empty($matches)) {
                return $route;
            }
            $collector = $this->getRouteCollector();
            foreach ($matches as $key => $value) {
                if ($value instanceof Contracts\DeferredProviderInterface) {
                    unset($this->routes[$key]);
                    $value->registerRoutes($collector);
                    $route = $this->getMappedRoute($routeName);
                    if (!is_null($route)) {
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            logActivity("Failed to expand route " . $routeName);
        }
        return $route;
    }
    protected function buildRoutePath($routeName, $routeVars)
    {
        $requireVariablePattern = "#{[^}]*}#";
        $optionalPathPattern = "#(\\[(.*)\\])#";
        $routePath = $this->getMappedRoute($routeName);
        if (!$routePath) {
            $routePath = $this->getDeferredRoutePath($routeName);
        }
        if (is_null($routePath)) {
            throw new \RuntimeException("Invalid route link name: " . $routeName);
        }
        $routePathParts = explode("[", $routePath, 2);
        if (preg_match_all($requireVariablePattern, $routePathParts[0], $requiredInterpolations)) {
            $requiredCount = count($requiredInterpolations[0]);
            $providedCount = count($routeVars);
            if ($providedCount < $requiredCount) {
                throw new \RuntimeException("URI route path '" . $routeName . "' requires " . $requiredCount . " " . "interpolation variables, " . $providedCount . " provided.");
            }
        }
        $tmpRouteVars = $routeVars;
        $interpolatePlaceHolder = function () use(&$tmpRouteVars) {
            if (count($tmpRouteVars)) {
                $value = array_shift($tmpRouteVars);
                if ($value && !is_numeric($value)) {
                    return $this->getModRewriteFriendlyString($value);
                }
                return (string) $value;
            }
            return "";
        };
        $link = preg_replace_callback($requireVariablePattern, $interpolatePlaceHolder, $routePath);
        if (preg_match($optionalPathPattern, $routePath, $matches)) {
            if (preg_match($requireVariablePattern, $matches[2])) {
                $tmpRouteVars = array();
                $emptyInterpolatedOption = preg_replace_callback($requireVariablePattern, $interpolatePlaceHolder, $matches[2]);
                $invalidOption = "[" . $emptyInterpolatedOption . "]";
                preg_match($optionalPathPattern, $link, $interpolatedMatches);
                if ($interpolatedMatches[1] == $invalidOption) {
                    $link = preg_replace($optionalPathPattern, "", $link);
                } else {
                    $link = preg_replace($optionalPathPattern, "\\2", $link);
                }
            } else {
                $link = str_replace($matches[0], "", $link);
            }
        }
        return $link;
    }
    public static function getAllKnownModes()
    {
        return array(static::MODE_REWRITE, static::MODE_ACCEPTPATHINFO, static::MODE_BASIC);
    }
    public function setMode($mode)
    {
        if ($mode && !in_array($mode, static::getAllKnownModes())) {
            throw new \RuntimeException(sprintf("Invalid \"mode\" value \"%s\"", $mode));
        }
        \WHMCS\Config\Setting::setValue(static::SETTING_MODE, $mode);
    }
    public function getMode()
    {
        $mode = \WHMCS\Config\Setting::getValue(static::SETTING_MODE);
        if (in_array($mode, self::getAllKnownModes())) {
            return $mode;
        }
        return self::MODE_UNKNOWN;
    }
    protected function getModRewriteFriendlyString($string)
    {
        $wasEmpty = $string === "";
        $string = str_replace("#", "sharp", $string);
        $string = str_replace("&quot;", "", $string);
        $string = str_replace("/", "or", $string);
        $string = str_replace("&amp;", "and", $string);
        $string = str_replace("&", "and", $string);
        $string = str_replace("+", "plus", $string);
        $string = str_replace("=", "equals", $string);
        $string = str_replace("@", "at", $string);
        $string = str_replace(" ", "-", $string);
        $string = preg_replace("/[^\\w\\-\\.]/u", "", $string);
        if ($string === "" && !$wasEmpty) {
            $string = "-";
        }
        return $string;
    }
}

?>