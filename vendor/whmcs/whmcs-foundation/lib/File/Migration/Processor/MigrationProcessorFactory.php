<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\File\Migration\Processor;

final class MigrationProcessorFactory
{
    private static $processorTypeMap = array("local-local" => "WHMCS\\File\\Migration\\Processor\\LocalToLocalMigrationProcessor", "local-s3" => "WHMCS\\File\\Migration\\Processor\\LocalToS3MigrationProcessor", "s3-local" => "WHMCS\\File\\Migration\\Processor\\S3ToLocalMigrationProcessor", "s3-s3" => "WHMCS\\File\\Migration\\Processor\\S3ToS3MigrationProcessor");
    private static function getProcessorClass(\WHMCS\File\Provider\StorageProviderInterface $fromProvider, \WHMCS\File\Provider\StorageProviderInterface $toProvider)
    {
        $migrationTypeKey = $fromProvider->getShortName() . "-" . $toProvider->getShortName();
        return array_key_exists($migrationTypeKey, static::$processorTypeMap) ? static::$processorTypeMap[$migrationTypeKey] : null;
    }
    public static function canMigrateFileAsset(\WHMCS\File\Provider\StorageProviderInterface $fromProvider, \WHMCS\File\Provider\StorageProviderInterface $toProvider)
    {
        return !is_null(static::getProcessorClass($fromProvider, $toProvider));
    }
    public static function createForFileAsset(\WHMCS\File\Configuration\FileAssetSetting $assetSetting, $timeLimitSec = AbstractMigrationProcessor::DEFAULT_MIGRATION_TIME_LIMIT_SEC, $dataSizeLimit = AbstractMigrationProcessor::DEFAULT_MIGRATION_DATA_SIZE_LIMIT)
    {
        if (!\WHMCS\File\FileAsset::validType($assetSetting->asset_type)) {
            throw new \WHMCS\Exception\Storage\AssetMigrationException("Invalid asset type: " . $assetSetting->asset_type);
        }
        if (!\WHMCS\File\FileAsset::canMigrate($assetSetting->asset_type)) {
            throw new \WHMCS\Exception\Storage\AssetMigrationException("Migration not supported for " . \WHMCS\File\FileAsset::getTypeName($assetSetting->asset_type));
        }
        if (!$assetSetting->migrateToConfiguration) {
            throw new \WHMCS\Exception\Storage\AssetMigrationException("Migration not in progress for " . \WHMCS\File\FileAsset::getTypeName($assetSetting->asset_type));
        }
        if ($assetSetting->configuration->id === $assetSetting->migrateToConfiguration->id) {
            throw new \WHMCS\Exception\Storage\AssetMigrationException("Cannot migrate " . \WHMCS\File\FileAsset::getTypeName($assetSetting->asset_type) . " to the same location");
        }
        $fromProvider = $assetSetting->configuration->createStorageProvider();
        $toProvider = $assetSetting->migrateToConfiguration->createStorageProvider();
        $processorClass = static::getProcessorClass($fromProvider, $toProvider);
        if (is_null($processorClass)) {
            throw new \WHMCS\Exception\Storage\UnsupportedMigrationPathException(sprintf("Migration from %s to %s is not supported", $fromProvider->getName(), $toProvider->getName()));
        }
        $migrationProcessor = new $processorClass($assetSetting->asset_type);
        if (!$migrationProcessor instanceof MigrationProcessorInterface) {
            throw new \WHMCS\Exception\Storage\AssetMigrationException(sprintf("Invalid migration processor for migrating %s to %s", $fromProvider->getName(), $toProvider->getName()));
        }
        $migrationProcessor->setFromProvider($fromProvider)->setToProvider($toProvider)->setTimeLimit($timeLimitSec)->setDataSizeLimit($dataSizeLimit);
        return $migrationProcessor;
    }
}

?>