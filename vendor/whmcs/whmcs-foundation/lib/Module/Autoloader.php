<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module;

class Autoloader
{
    protected $baseModulePath = NULL;
    protected $moduleTypes = array("Addon" => "addons", "Fraud" => "fraud", "Gateway" => "gateways", "Notification" => "notifications", "Registrar" => "registrars", "Report" => "reports", "Security" => "security", "Server" => "servers", "Social" => "social", "Support" => "support", "Widget" => "widgets");
    public static function register(Autoloader $loader = NULL)
    {
        if (!$loader) {
            $loader = new static();
        }
        spl_autoload_register(array($loader, "moduleClassLoader"));
    }
    public static function unregister(Autoloader $loader = NULL)
    {
        if (!$loader) {
            foreach (spl_autoload_functions() as $loader) {
                $className = get_class();
                if ($loader[0] instanceof $className) {
                    spl_autoload_unregister(array($loader[0], $loader[1]));
                }
            }
        } else {
            spl_autoload_unregister(array($loader, "moduleClassLoader"));
        }
    }
    public function moduleClassLoader($className)
    {
        $className = trim($className, "\\");
        if (strpos($className, "WHMCS\\Module") !== 0) {
            return false;
        }
        if (class_exists($className, false)) {
            return false;
        }
        if (substr($className, 0, strlen("WHMCS\\Module\\Widget\\")) == "WHMCS\\Module\\Widget\\") {
            $filename = str_replace("WHMCS\\Module\\Widget\\", "", $className);
            \WHMCS\Utility\SafeInclude::file(ROOTDIR . "/modules/widgets/" . $filename . ".php", function ($errorMessage) use($filename) {
                logActivity("Admin homepage widget " . $filename . " failed to load. " . $errorMessage);
            });
            return true;
        }
        try {
            $parts = $this->getClassParts($className);
        } catch (\WHMCS\Exception $e) {
            return false;
        }
        if (!array_key_exists($parts["moduleType"], $this->getModuleTypes())) {
            return false;
        }
        $includePaths = $this->getModuleIncludePaths($parts["moduleType"], $parts["moduleName"], $parts["relativeClassParts"]);
        foreach ($includePaths as $path) {
            if (file_exists($path)) {
                include_once $path;
                return true;
            }
        }
        return false;
    }
    public function getBaseModulePath()
    {
        return ROOTDIR . $this->baseModulePath;
    }
    public function getModuleTypes()
    {
        return $this->moduleTypes;
    }
    public function getClassParts($className)
    {
        $parts = explode("\\", $className, 5);
        if (5 <= count($parts)) {
            return array("moduleType" => $parts[2], "moduleName" => $parts[3], "relativeClassParts" => explode("\\", $parts[4]));
        }
        throw new \WHMCS\Exception();
    }
    protected function splitAtUppercase($string)
    {
        $split = preg_split("/(?=[A-Z])/", $string);
        foreach ($split as $k => $v) {
            if (!trim($v)) {
                unset($split[$k]);
            }
        }
        return $split;
    }
    public function getModuleDirectoryNames($moduleName)
    {
        $parts = $this->splitAtUppercase($moduleName);
        return array_unique(array($moduleName, strtolower($moduleName), implode("_", array_map("strtolower", $parts)), implode("_", $parts)));
    }
    public function getModuleIncludePaths($moduleType, $moduleName, $relativeClassParts)
    {
        $moduleDirectoryNames = $this->getModuleDirectoryNames($moduleName);
        $paths = array();
        foreach ($moduleDirectoryNames as $moduleDirName) {
            $paths[] = $this->getBaseModulePath() . DIRECTORY_SEPARATOR . $this->moduleTypes[$moduleType] . DIRECTORY_SEPARATOR . $moduleDirName . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $relativeClassParts) . ".php";
        }
        return $paths;
    }
}

?>