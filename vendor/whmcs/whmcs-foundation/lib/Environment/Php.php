<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Environment;

class Php
{
    protected static $myUid = NULL;
    protected static $versionSupport = array("5.3" => array("active" => "14 Aug 2013", "security" => "14 Aug 2014"), "5.4" => array("active" => "14 Sep 2014", "security" => "14 Sep 2015"), "5.5" => array("active" => "10 Jul 2015", "security" => "10 Jul 2016"), "5.6" => array("active" => "31 Dec 2016", "security" => "31 Dec 2018"), "7.0" => array("active" => "03 Dec 2017", "security" => "03 Dec 2018"), "7.1" => array("active" => "01 Dec 2018", "security" => "01 Dec 2019"), "7.2" => array("active" => "30 Nov 2019", "security" => "30 Nov 2020"), "7.3" => array("active" => "06 Dec 2020", "security" => "06 Dec 2021"));
    public static function functionEnabled($function)
    {
        $disabledFunctions = preg_split("/\\s*\\,\\s*/", trim(ini_get("disable_functions")));
        return (bool) ($function !== "" && !in_array(strtolower($function), $disabledFunctions));
    }
    public static function getIniSetting($setting)
    {
        return ini_get($setting);
    }
    public static function isIniSettingEnabled($setting)
    {
        return (bool) self::getIniSetting($setting);
    }
    public static function isFunctionAvailable($function)
    {
        return function_exists($function) && self::functionEnabled($function);
    }
    public static function isModuleActive($module)
    {
        return extension_loaded($module);
    }
    public static function isCli()
    {
        switch (php_sapi_name()) {
            case "cli":
            case "cli-server":
                return true;
        }
        if (!isset($_SERVER["SERVER_NAME"]) && !isset($_SERVER["HTTP_HOST"])) {
            return true;
        }
        return false;
    }
    public static function getUserRunningPhp()
    {
        if (!is_null(static::$myUid)) {
            return static::$myUid;
        }
        $tempFilename = tempnam(\App::getApplicationConfig()->templates_compiledir, "tmp");
        touch($tempFilename);
        static::$myUid = fileowner($tempFilename);
        unlink($tempFilename);
        return static::$myUid;
    }
    public static function hasValidTimezone()
    {
        $tz = ini_get("date.timezone");
        $tzOld = date_default_timezone_get();
        if ($tz) {
            $tzValid = date_default_timezone_set($tz) ? true : false;
            if ($tzOld) {
                date_default_timezone_set($tzOld);
            }
        } else {
            $tzValid = false;
        }
        return $tzValid;
    }
    public static function hasExtension($extension)
    {
        return extension_loaded($extension);
    }
    public static function isSessionAutoStartEnabled()
    {
        return (bool) ini_get("session.auto_start");
    }
    public static function isSessionSavePathWritable()
    {
        return is_writable(ini_get("session.save_path"));
    }
    public static function isSupportedByWhmcs($version = PHP_VERSION)
    {
        return version_compare($version, "5.6.0", ">=");
    }
    public static function hasActivePhpSupport($majorMinor)
    {
        return \WHMCS\Carbon::createFromFormat("d M Y", static::$versionSupport[$majorMinor]["active"])->isFuture();
    }
    public static function hasSecurityPhpSupport($majorMinor)
    {
        return \WHMCS\Carbon::createFromFormat("d M Y", static::$versionSupport[$majorMinor]["security"])->isFuture();
    }
    public static function convertMemoryLimitToBytes($memoryLimit)
    {
        if (is_int($memoryLimit) || is_float($memoryLimit)) {
            return $memoryLimit;
        }
        $memoryLimit = trim($memoryLimit);
        $memoryLimitModifier = $memoryLimit[strlen($memoryLimit) - 1];
        $memoryLimitNumeric = (int) $memoryLimit;
        switch ($memoryLimitModifier) {
            case "G":
                $memoryLimitNumeric *= 1024;
            case "M":
                $memoryLimitNumeric *= 1024;
            case "K":
                $memoryLimitNumeric *= 1024;
        }
        return $memoryLimitNumeric;
    }
    public static function getPhpMemoryLimitInBytes()
    {
        return static::convertMemoryLimitToBytes(ini_get("memory_limit"));
    }
    public static function hasErrorLevelEnabled($errorLevels, $checkLevel)
    {
        return (bool) ($errorLevels & $checkLevel);
    }
    public static function getVersion()
    {
        return PHP_VERSION;
    }
    public static function getLoadedExtensions()
    {
        return get_loaded_extensions();
    }
    public static function getPreferredCliBinary()
    {
        try {
            if (static::isFunctionAvailable("php_ini_loaded_file")) {
                $iniPath = php_ini_loaded_file();
                if (strpos($iniPath, "/opt/cpanel/") === 0) {
                    $phpBinary = preg_replace("/etc\\/php.ini\$/", "usr/bin/php", $iniPath);
                    if (file_exists($phpBinary)) {
                        return $phpBinary;
                    }
                }
            }
        } catch (\Error $e) {
        } catch (\Exception $e) {
        }
        $potentialPhpBinaries = array("/usr/bin/php-cli", "/usr/local/bin/php-cli", "/usr/bin/php", "/usr/local/bin/php");
        foreach ($potentialPhpBinaries as $phpBinary) {
            if (file_exists($phpBinary)) {
                return $phpBinary;
            }
        }
        return "php";
    }
}

?>