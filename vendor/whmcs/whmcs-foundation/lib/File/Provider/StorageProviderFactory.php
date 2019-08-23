<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\File\Provider;

class StorageProviderFactory
{
    private static $providerClasses = array("WHMCS\\File\\Provider\\LocalStorageProvider", "WHMCS\\File\\Provider\\S3StorageProvider");
    public static function getProviderClasses()
    {
        $providers = array();
        foreach (self::$providerClasses as $providerClass) {
            $providers[$providerClass::getShortName()] = $providerClass;
        }
        return $providers;
    }
    public static function createProvider($providerShortName)
    {
        $providers = self::getProviderClasses();
        if (array_key_exists($providerShortName, $providers)) {
            return new $providers[$providerShortName]();
        }
        return null;
    }
    public static function getLocalStoragePathsInUse()
    {
        $storagePaths = array();
        foreach (\WHMCS\File\Configuration\StorageConfiguration::local()->get() as $config) {
            $fileAssetSetting = \WHMCS\File\Configuration\FileAssetSetting::usingConfiguration($config->id)->first();
            if (!$fileAssetSetting) {
                continue;
            }
            $provider = $config->createStorageProvider();
            if ($provider instanceof LocalStorageProviderInterface) {
                $storagePaths[$fileAssetSetting->asset_type] = $provider->getLocalPath();
            }
        }
        return $storagePaths;
    }
}

?>