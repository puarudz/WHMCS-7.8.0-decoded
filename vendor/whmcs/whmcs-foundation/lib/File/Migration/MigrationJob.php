<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\File\Migration;

class MigrationJob implements \WHMCS\Scheduling\Contract\JobInterface
{
    use \WHMCS\Scheduling\Jobs\JobTrait;
    const JOB_NAME = "storage.asset.migrations";
    public static function queue()
    {
        \WHMCS\Scheduling\Jobs\Queue::addOrUpdate(static::JOB_NAME, static::class, "performAssetMigrations", array());
    }
    public static function dequeue()
    {
        \WHMCS\Scheduling\Jobs\Queue::remove(static::JOB_NAME);
    }
    public function performAssetMigrations()
    {
        $migratingAssets = \WHMCS\File\Configuration\FileAssetSetting::inMigration()->get();
        $numAssetTypesToMigrate = $migratingAssets->count();
        foreach ($migratingAssets as $assetSetting) {
            try {
                $migrationProcessor = Processor\MigrationProcessorFactory::createForFileAsset($assetSetting, Processor\AbstractMigrationProcessor::DEFAULT_MIGRATION_TIME_LIMIT_SEC / $numAssetTypesToMigrate, Processor\AbstractMigrationProcessor::DEFAULT_MIGRATION_DATA_SIZE_LIMIT / $numAssetTypesToMigrate);
                $migrationProcessor->migrate();
            } catch (\WHMCS\Exception\Storage\UnsupportedMigrationPathException $e) {
                $assetSetting->migratetoconfiguration_id = null;
                $assetSetting->save();
                logActivity("Storage migration cancelled: " . $e->getMessage());
            } catch (\Exception $e) {
                logActivity("Storage migration failed: " . $e->getMessage());
            }
        }
        if (\WHMCS\File\Configuration\FileAssetSetting::inMigration()->first()) {
            self::queue();
        } else {
            self::dequeue();
        }
    }
}

?>