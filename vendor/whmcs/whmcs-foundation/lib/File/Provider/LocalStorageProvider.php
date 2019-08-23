<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\File\Provider;

class LocalStorageProvider implements StorageProviderInterface, LocalStorageProviderInterface
{
    private $localPath = NULL;
    public static function getShortName()
    {
        return "local";
    }
    public static function getName()
    {
        return "Local Storage";
    }
    public function isLocal()
    {
        return true;
    }
    public function applyConfiguration(array $configSettings)
    {
        $localPath = $configSettings["local_path"];
        if (empty($localPath)) {
            throw new \WHMCS\Exception\Storage\StorageConfigurationException(array("local_path" => "Local path must not be empty"));
        }
        $this->setLocalPath($localPath);
    }
    public function exportConfiguration(\WHMCS\File\Configuration\StorageConfiguration $config = NULL)
    {
        if (!$config) {
            $config = \WHMCS\File\Configuration\StorageConfiguration::newLocal();
        }
        $config->name = $this->getName() . ": " . $this->localPath;
        $config->handler = static::class;
        $config->settings = array("local_path" => $this->localPath);
        return $config;
    }
    public function getConfigurationFields()
    {
        return array(array("Name" => "local_path", "FriendlyName" => \AdminLang::trans("storage.local.local_path"), "Type" => "text"));
    }
    public function getAccessCredentialFieldNames()
    {
        return array();
    }
    public function getLocalPath()
    {
        return $this->localPath;
    }
    public function createFilesystemAdapterForAssetType($assetType, $subPath = "")
    {
        $localAssetPath = $this->localPath;
        if ($subPath) {
            $localAssetPath .= DIRECTORY_SEPARATOR . $subPath;
        }
        return new \League\Flysystem\Adapter\Local($localAssetPath);
    }
    public function setLocalPath($localPath)
    {
        $localPath = rtrim($localPath, DIRECTORY_SEPARATOR);
        $this->localPath = $localPath;
        return $this;
    }
    public function getConfigSummaryText()
    {
        return $this->localPath;
    }
    public function getConfigSummaryHtml()
    {
        $parts = explode(DIRECTORY_SEPARATOR, $this->localPath);
        $maxParts = 1;
        if ($maxParts < count($parts)) {
            return "..." . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, array_slice($parts, 0 - $maxParts, $maxParts));
        }
        return $this->localPath;
    }
    public function getIcon()
    {
        return "far fa-hdd";
    }
    public function testConfiguration()
    {
        if (!is_dir($this->localPath) || !is_writable($this->localPath)) {
            throw new \WHMCS\Exception\Storage\StorageException("Directory must exist and be writable: " . $this->localPath);
        }
        $filesystem = new \WHMCS\File\Filesystem($this->createFilesystemAdapterForAssetType(\WHMCS\File\FileAsset::TYPE_CLIENT_FILES));
        $randomFilename = \Illuminate\Support\Str::random(32);
        $randomString = \Illuminate\Support\Str::random(32);
        try {
            $fileCreated = $filesystem->write($randomFilename, $randomString);
            if ($randomString !== $filesystem->read($randomFilename)) {
                throw new \WHMCS\Exception\Storage\StorageException("Failed to read test file contents");
            }
        } finally {
            if ($fileCreated) {
                $filesystem->delete($randomFilename);
            }
        }
    }
    public function getFieldsLockedInUse()
    {
        return array("local_path");
    }
    public static function getExceptionErrorMessage(\Exception $e)
    {
        $errorMessage = $e->getMessage();
        if (stripos($errorMessage, "lstat failed for") !== false) {
            $errorMessage .= ". This file is missing or inaccessible.";
        }
        return $errorMessage;
    }
}

?>