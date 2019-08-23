<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\File\Configuration;

class FileAssetSetting extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblfileassetsettings";
    public function setAssetTypeAttribute($value)
    {
        if (!array_key_exists($value, \WHMCS\File\FileAsset::TYPES)) {
            throw new \WHMCS\Exception\Storage\StorageException("Invalid storage asset type: " . $value);
        }
        $this->attributes["asset_type"] = $value;
    }
    public function configuration()
    {
        return $this->hasOne("WHMCS\\File\\Configuration\\StorageConfiguration", "id", "storageconfiguration_id");
    }
    public function migrateToConfiguration()
    {
        return $this->hasOne("WHMCS\\File\\Configuration\\StorageConfiguration", "id", "migratetoconfiguration_id");
    }
    public function scopeForAssetType(\Illuminate\Database\Eloquent\Builder $query, $assetType)
    {
        if (!array_key_exists($assetType, \WHMCS\File\FileAsset::TYPES)) {
            throw new \WHMCS\Exception\Storage\StorageException("Invalid storage asset type: " . $assetType);
        }
        return $query->where("asset_type", $assetType);
    }
    public function scopeInMigration(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->whereNotNull("migratetoconfiguration_id");
    }
    public function createFilesystemAdapter($subPath = "")
    {
        $storageProvider = $this->configuration->createStorageProvider();
        return $storageProvider->createFilesystemAdapterForAssetType($this->asset_type, $subPath);
    }
    public function scopeUsingConfiguration(\Illuminate\Database\Eloquent\Builder $query, $configurationId)
    {
        return $query->where("storageconfiguration_id", $configurationId)->orWhere("migratetoconfiguration_id", $configurationId);
    }
}

?>