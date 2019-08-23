<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Application;

class ApplicationServiceProvider extends Support\ServiceProvider\AbstractServiceProvider
{
    public function register()
    {
        $container = $this->app;
        $container->singleton("app", function () use($container) {
            $config = $container->make("config");
            if (!$config->isConfigFileLoaded()) {
                $file = $config->getDefaultApplicationConfigFilename();
                if (!$config->configFileExists($file)) {
                    throw new \WHMCS\Exception\Application\Configuration\FileNotFound("Configuration file '" . $file . "' does not exist.");
                }
                if (!$config->loadConfigFile($file)) {
                    throw new \WHMCS\Exception\Application\Configuration\ParseError("Unable to load configuration file. " . "Please check permissions and contents of the configuration.php file.");
                }
            }
            if (!$config->license) {
                $file = $config->getLoadedFilename();
                throw new \WHMCS\Exception\Application\Configuration\LicenseKeyNotDefined("Configuration file '" . $file . "' does not contain a license key.");
            }
            try {
                $database = $container->make("db");
            } catch (\Exception $e) {
                $dbName = $config["display_errors"] ? " '" . $config->getDatabaseName() . "'" : "";
                $msg = sprintf("Could not connect to the%s database.", $dbName);
                throw new \WHMCS\Exception\Application\Configuration\CannotConnectToDatabase($msg);
            }
            return new \WHMCS\Application($config, $database);
        });
    }
    public static function checkVersion()
    {
        $versionInDb = \WHMCS\Config\Setting::getValue("Version");
        $fileVersion = \WHMCS\Application::FILES_VERSION;
        if ($versionInDb != $fileVersion) {
            $fileVersionSemantic = new \WHMCS\Version\SemanticVersion(\WHMCS\Application::FILES_VERSION);
            try {
                $versionInDbSemantic = new \WHMCS\Version\SemanticVersion($versionInDb);
            } catch (\Exception $e) {
                throw new \WHMCS\Exception\Application\InstallationVersionMisMatch("Version number in database is invalid");
            }
            if (self::isVersionBumpValid($fileVersionSemantic, $versionInDbSemantic)) {
                try {
                    $bumpDestination = \WHMCS\Updater\Version\IncrementalVersion::factory($fileVersionSemantic->getCanonical());
                } catch (\Exception $e) {
                    throw new \WHMCS\Exception\Application\InstallationVersionMisMatch("File version '" . $fileVersionSemantic->getCanonical() . "' could not be created. Core files not synchronized.");
                }
                $bumpDestination->applyUpdate();
            } else {
                throw new \WHMCS\Exception\Application\InstallationVersionMisMatch("Database version '" . $versionInDbSemantic->getCanonical() . "' does not match file version '" . $fileVersionSemantic->getCanonical() . "'");
            }
        }
    }
    public static function isVersionBumpValid(\WHMCS\Version\SemanticVersion $fileVersion, \WHMCS\Version\SemanticVersion $databaseVersion)
    {
        $isNextRevision = \WHMCS\Version\SemanticVersion::isNextRevision($databaseVersion, $fileVersion);
        $isProduction = $databaseVersion->getPreReleaseIdentifier() == \WHMCS\Version\SemanticVersion::DEFAULT_PRERELEASE_IDENTIFIER;
        if ($isNextRevision && $isProduction) {
            return true;
        }
        return false;
    }
}

?>