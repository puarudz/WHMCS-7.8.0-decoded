<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\File\Migration\Processor;

abstract class AbstractMigrationProcessor implements MigrationProcessorInterface
{
    protected $assetType = NULL;
    protected $timeLimit = self::DEFAULT_MIGRATION_TIME_LIMIT_SEC;
    protected $dataSizeLimit = self::DEFAULT_MIGRATION_DATA_SIZE_LIMIT;
    protected $objectsToMigrate = array();
    protected $migratedObjects = array();
    private $migrationProgress = NULL;
    const DEFAULT_MIGRATION_TIME_LIMIT_SEC = 240;
    const DEFAULT_MIGRATION_DATA_SIZE_LIMIT = 1073741824;
    public abstract function setFromProvider(\WHMCS\File\Provider\StorageProviderInterface $fromProvider);
    public abstract function setToProvider(\WHMCS\File\Provider\StorageProviderInterface $toProvider);
    protected abstract function doMigrate();
    public function __construct($assetType)
    {
        $this->assetType = $assetType;
        $this->migrationProgress = \WHMCS\File\Migration\FileAssetMigrationProgress::firstOrNew(array("asset_type" => $this->assetType));
        if (!empty($this->migrationProgress->migratedObjects)) {
            $this->migratedObjects = $this->migrationProgress->migratedObjects;
        }
    }
    public function getTimeLimit()
    {
        return $this->timeLimit;
    }
    public function setTimeLimit($timeLimit)
    {
        $this->timeLimit = $timeLimit;
        return $this;
    }
    public function getDataSizeLimit()
    {
        return $this->dataSizeLimit;
    }
    public function setDataSizeLimit($dataSizeLimit)
    {
        $this->dataSizeLimit = $dataSizeLimit;
        return $this;
    }
    protected function addMigratedObject($objectPath)
    {
        if (!in_array($objectPath, $this->migratedObjects)) {
            $this->migratedObjects[] = $objectPath;
        }
    }
    protected function saveMigrationProgress()
    {
        $this->migratedObjects = array_intersect($this->migratedObjects, $this->objectsToMigrate);
        $this->migrationProgress->migratedObjects = $this->migratedObjects;
        $this->migrationProgress->num_objects_migrated = count($this->migratedObjects);
        $this->migrationProgress->num_objects_total = count($this->objectsToMigrate);
        $this->migrationProgress->save();
    }
    protected function isObjectMigrated($objectPath)
    {
        return in_array($objectPath, $this->migratedObjects);
    }
    private function markMigrationComplete()
    {
        $this->migrationProgress->delete();
        $assetSetting = \WHMCS\File\Configuration\FileAssetSetting::forAssetType($this->assetType)->firstOrFail();
        $assetSetting->storageconfiguration_id = $assetSetting->migratetoconfiguration_id;
        $assetSetting->migratetoconfiguration_id = null;
        $assetSetting->save();
    }
    protected function finalizeMigrationRun()
    {
        if (count(array_diff($this->objectsToMigrate, $this->migratedObjects)) === 0) {
            $this->markMigrationComplete();
            return true;
        }
        $this->saveMigrationProgress();
        return false;
    }
    private function recordMigrationRunFailure($failureReason)
    {
        $this->migrationProgress->num_failures++;
        $this->migrationProgress->last_failure_reason = $failureReason;
        $this->migrationProgress->save();
    }
    private function rescanObjectsToMigrate()
    {
        $this->objectsToMigrate = \WHMCS\File\FileAssetCollection::forAssetType($this->assetType)->toArray();
    }
    public function migrate()
    {
        if (!$this->migrationProgress->active) {
            return false;
        }
        try {
            $this->rescanObjectsToMigrate();
        } catch (\Exception $e) {
            $this->recordMigrationRunFailure($e->getMessage());
            return false;
        }
        try {
            $this->doMigrate();
            $this->migrationProgress->num_failures = 0;
            $this->migrationProgress->last_failure_reason = "";
        } catch (\Exception $e) {
            $this->recordMigrationRunFailure($e->getMessage());
        } finally {
            $rescanSuccessful = true;
            try {
                $this->rescanObjectsToMigrate();
            } catch (\Exception $e) {
                $this->recordMigrationRunFailure($e->getMessage());
                $rescanSuccessful = false;
            }
            $migrationIsComplete = $this->finalizeMigrationRun();
        }
    }
    public function getMigrationProgress()
    {
        return $this->migrationProgress;
    }
}

?>