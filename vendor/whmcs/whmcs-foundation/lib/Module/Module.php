<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module;

class Module
{
    protected $classMap = array("addons" => "Addon", "fraud" => "Fraud", "gateways" => "Gateway", "notifications" => "Notification", "registrars" => "Registrar", "security" => "Security", "servers" => "Server");
    private static $hookLoads = NULL;
    protected $cacheActiveModules = NULL;
    public function getClassMap()
    {
        return $this->classMap;
    }
    public function getAllClasses()
    {
        $classMap = $this->classMap;
        foreach ($classMap as $key => $value) {
            $classMap[$key] = $this->getClassByModuleType($key);
        }
        return $classMap;
    }
    public function getClassByModuleType($type)
    {
        if (!array_key_exists($type, $this->classMap)) {
            throw new \WHMCS\Exception("Invalid module type requested: " . $type);
        }
        $className = "\\WHMCS\\Module\\" . $this->classMap[$type];
        return new $className();
    }
    public static function sluggify($moduleType, $moduleName)
    {
        return strtolower(implode(".", array($moduleType, $moduleName)));
    }
    public static function defineHooks()
    {
        foreach (self::$hookLoads as $moduleType => $settingName) {
            $moduleHooks = array_filter(explode(",", \WHMCS\Config\Setting::getValue($settingName)));
            foreach ($moduleHooks as $moduleHook) {
                $moduleHook = ROOTDIR . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . $moduleType . DIRECTORY_SEPARATOR . $moduleHook . DIRECTORY_SEPARATOR . "hooks.php";
                if (file_exists($moduleHook)) {
                    hook_log("", "Attempting to load hook file: %s", $moduleHook);
                    $hookLoaded = \WHMCS\Utility\SafeInclude::file($moduleHook, function ($errorMessage) {
                        hook_log("", $errorMessage);
                    });
                    if ($hookLoaded) {
                        hook_log("", "Hook File Loaded: %s", $moduleHook);
                    }
                }
            }
        }
    }
}

?>