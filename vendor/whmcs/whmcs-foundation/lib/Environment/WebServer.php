<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Environment;

final class WebServer
{
    private static $phpInfoOutput = "";
    private static $serverFamily = "";
    private static $serverVersion = "";
    const SERVER_NAME_APACHE = "Apache";
    const HTACCESS_FILE = ".htaccess";
    private static function getPhpInfo()
    {
        if (static::$phpInfoOutput) {
            return static::$phpInfoOutput;
        }
        ob_start();
        phpinfo();
        $phpInfo = ob_get_clean();
        $phpInfo = preg_replace("/.*<body\\s*>/is", "", $phpInfo);
        $phpInfo = preg_replace("/<\\/body>.*/is", "", $phpInfo);
        $phpInfo = preg_replace("/<br\\s*(\\/)?>/i", "\n", $phpInfo);
        $phpInfo = strip_tags($phpInfo);
        static::$phpInfoOutput = trim($phpInfo);
        return static::$phpInfoOutput;
    }
    private static function parseServerInfo()
    {
        if (isset($_SERVER["SERVER_SOFTWARE"])) {
            $serverSoftware = $_SERVER["SERVER_SOFTWARE"];
            if (preg_match("/^([^\\s]+)\\/([a-z\\d\\.]+)/i", $serverSoftware, $matches)) {
                list(, static::$serverFamily, static::$serverVersion) = $matches;
            } else {
                if (preg_match("/^([^\\s]+)\\s/i", $serverSoftware, $matches)) {
                    $serverFamily = $matches[1];
                    $serverVersion = "Unknown";
                    $aliasedServerFamilies = array("centos webpanel: protected by mod security" => "Apache");
                    $serverSoftwareKey = trim(strtolower($serverSoftware));
                    if (array_key_exists($serverSoftwareKey, $aliasedServerFamilies)) {
                        $serverFamily = $aliasedServerFamilies[$serverSoftwareKey];
                        if (empty($serverFamily)) {
                            $serverFamily = "Other";
                        }
                        $serverVersion = $serverSoftware;
                    }
                    static::$serverFamily = $serverFamily;
                    static::$serverVersion = $serverVersion;
                } else {
                    $knownServers = array("Apache", "LiteSpeed", "lighttpd", "Microsoft-IIS", "nginx");
                    static::$serverFamily = "";
                    foreach ($knownServers as $knownServer) {
                        if (stripos($serverSoftware, $knownServer) !== false) {
                            static::$serverFamily = $knownServer;
                            static::$serverVersion = "Unknown";
                        }
                    }
                    if (!static::$serverFamily) {
                        static::$serverFamily = "Other";
                        static::$serverVersion = $serverSoftware;
                    }
                }
            }
            return true;
        }
        return false;
    }
    public static function getServerFamily()
    {
        if (!static::$serverFamily) {
            static::parseServerInfo();
        }
        return static::$serverFamily;
    }
    public static function getServerVersion()
    {
        if (!static::$serverVersion) {
            static::parseServerInfo();
        }
        return static::$serverVersion;
    }
    public static function getControlPanelInfo()
    {
        $cpFlags = array("/usr/local/cpanel" => array("family" => "cPanel", "versionFile" => "/usr/local/cpanel/version"), "/usr/local/psa" => array("family" => "Plesk", "versionFile" => "/usr/local/psa/version"), "/usr/local/directadmin" => array("family" => "DirectAdmin", "versionFile" => "/usr/local/directadmin/custombuild/versions.txt", "versionFileRegex" => "/^[\\s]*directadmin:([^:\\r\\n]+):[\\s]*\$/im"));
        $panelFamily = "Unknown";
        $panelVersion = "";
        foreach ($cpFlags as $flagFile => $cpInfo) {
            if (file_exists($flagFile)) {
                $panelVersionFileContent = @file_get_contents($cpInfo["versionFile"]);
                if (isset($cpInfo["versionFileRegex"])) {
                    if (preg_match($cpInfo["versionFileRegex"], $panelVersionFileContent, $matches)) {
                        $panelVersion = $matches[1];
                    }
                } else {
                    $panelVersion = $panelVersionFileContent;
                }
                $panelVersion = trim(preg_replace("/[^A-Z\\d\\.\\- ]+/i", " ", $panelVersion));
                $panelFamily = $cpInfo["family"];
                break;
            }
        }
        return array("family" => $panelFamily, "version" => $panelVersion);
    }
    public static function isApache()
    {
        return 0 == strcasecmp(static::getServerFamily(), static::SERVER_NAME_APACHE);
    }
    public static function hasModRewrite()
    {
        $controlPanelInfo = static::getControlPanelInfo();
        if (strcasecmp("cpanel", $controlPanelInfo["family"]) === 0) {
            return true;
        }
        return stripos(static::getPhpInfo(), "mod_rewrite") !== false;
    }
    public static function hasRootHtaccess()
    {
        return (bool) file_exists(ROOTDIR . DIRECTORY_SEPARATOR . static::HTACCESS_FILE);
    }
    public static function hasAdminHtaccess()
    {
        $adminPath = \DI::make("config")->customadminpath;
        if (!$adminPath) {
            $adminPath = \WHMCS\Config\Application::DEFAULT_ADMIN_FOLDER;
        }
        return (bool) file_exists(ROOTDIR . DIRECTORY_SEPARATOR . $adminPath . DIRECTORY_SEPARATOR . static::HTACCESS_FILE);
    }
}

?>