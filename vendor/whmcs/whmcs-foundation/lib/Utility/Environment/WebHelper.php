<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Utility\Environment;

class WebHelper
{
    public static function getBaseUrl($root = ROOTDIR, $scriptName = NULL)
    {
        static $cache = array();
        $serverScriptName = isset($_SERVER["SCRIPT_NAME"]) && !is_null($_SERVER["SCRIPT_NAME"]) ? $_SERVER["SCRIPT_NAME"] : "";
        $scriptName = is_null($scriptName) ? $serverScriptName : $scriptName;
        $root_cache_key = $root;
        if (isset($cache[$root_cache_key][$scriptName])) {
            return $cache[$root_cache_key][$scriptName];
        }
        $root = str_replace("\\", "/", $root);
        $segments = explode("/", trim($root, "/"));
        $segments = array_reverse($segments);
        $index = 0;
        $last = count($segments);
        $baseUrl = "";
        $found = true;
        while ($found && $index < $last) {
            $segment = $segments[$index];
            $baseUrl = "/" . $segment . $baseUrl;
            $index++;
            $found = strpos($scriptName, $baseUrl . "/") !== false;
        }
        $baseUrlSegments = explode("/", trim($baseUrl, "/"));
        array_shift($baseUrlSegments);
        $adminDir = \WHMCS\Config\Application::DEFAULT_ADMIN_FOLDER;
        $config = \DI::make("config");
        if (!empty($config->customadminpath)) {
            $adminDir = $config->customadminpath;
        }
        if (isset($baseUrlSegments[0]) && $baseUrlSegments[0] == $adminDir) {
            array_shift($baseUrlSegments);
        }
        $baseUrl = "/" . implode("/", $baseUrlSegments);
        if ($baseUrl == "/") {
            $baseUrl = "";
        }
        $cache[$root_cache_key][$scriptName] = $baseUrl;
        return $baseUrl;
    }
    public static function getAdminBaseUrl($root = ROOTDIR, $scriptName = NULL)
    {
        $basePath = static::getBaseUrl($root, $scriptName);
        $adminBase = \WHMCS\Admin\AdminServiceProvider::getAdminRouteBase();
        return $basePath . $adminBase;
    }
}

?>