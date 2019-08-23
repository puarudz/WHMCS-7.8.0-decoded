<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\File\Configuration;

class StorageConfiguration extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblstorageconfigurations";
    protected $casts = array("is_local" => "boolean", "last_error" => "array");
    private $settingsErrorLogged = false;
    public static function boot()
    {
        parent::boot();
        self::creating(function (StorageConfiguration $config) {
            if (is_null($config->sort_order)) {
                $highestOrder = StorageConfiguration::query()->orderBy("sort_order", "DESC")->value("sort_order");
                $config->sort_order = (int) $highestOrder + 1;
            }
        });
        self::deleting(function (StorageConfiguration $config) {
            $assetSetting = FileAssetSetting::where("storageconfiguration_id", $config->id)->orWhere("migratetoconfiguration_id", $config->id);
            if ($assetSetting->exists()) {
                throw new \WHMCS\Exception\Storage\StorageException("This storage configuration is in use and cannot be deleted");
            }
        });
        static::addGlobalScope("order", function (\Illuminate\Database\Eloquent\Builder $builder) {
            $builder->orderBy("tblstorageconfigurations.sort_order")->orderBy("tblstorageconfigurations.id");
        });
    }
    public static function newLocal()
    {
        $configuration = new static();
        $configuration->is_local = true;
        return $configuration;
    }
    public static function newRemote()
    {
        $configuration = new static();
        $configuration->is_local = false;
        return $configuration;
    }
    public function getSettingsAttribute()
    {
        $settings = $this->attributes["settings"];
        if (substr($settings, 0, 1) !== "{") {
            $settings = $this->decrypt($settings);
        }
        $settings = json_decode($settings, true);
        if (!is_array($settings) && $this->exists && !$this->settingsErrorLogged) {
            $this->settingsErrorLogged = true;
            logActivity("Encryption hash is missing or damaged. Storage settings could not be decrypted.");
        }
        return $settings;
    }
    public function setSettingsAttribute(array $value)
    {
        $settings = json_encode($value);
        if (!$this->is_local) {
            $settings = $this->encrypt($settings);
        }
        $this->attributes["settings"] = $settings;
    }
    public function createStorageProvider()
    {
        $providerClass = $this->handler;
        if (!class_exists($providerClass)) {
            throw new \WHMCS\Exception\Storage\StorageException("Cannot find storage handler: " . $providerClass);
        }
        if ($providerClass instanceof \WHMCS\File\Provider\StorageProviderInterface) {
            throw new \WHMCS\Exception\Storage\StorageException("Invalid storage handler: " . $providerClass);
        }
        $provider = new $providerClass();
        $provider->applyConfiguration($this->settings ?: array());
        return $provider;
    }
    public function testForDuplicate()
    {
        $otherConfig = static::where("name", $this->name);
        if ($this->id) {
            $otherConfig = $otherConfig->where("id", "!=", $this->id);
        }
        if ($otherConfig->exists()) {
            throw new \WHMCS\Exception\Storage\SameStorageConfigurationExistsException();
        }
        return $this;
    }
    public function assetSettings()
    {
        return $this->hasMany("WHMCS\\File\\Configuration\\FileAssetSetting", "storageconfiguration_id");
    }
    public function assetSettingsMigratedTo()
    {
        return $this->hasMany("WHMCS\\File\\Configuration\\FileAssetSetting", "migratetoconfiguration_id");
    }
    public function scopeLocal(\Illuminate\Database\Eloquent\Builder $builder)
    {
        return $builder->where("is_local", "!=", "0");
    }
}

?>