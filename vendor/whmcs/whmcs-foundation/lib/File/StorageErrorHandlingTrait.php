<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\File;

trait StorageErrorHandlingTrait
{
    private $assetSetting = NULL;
    public function getAssetSetting()
    {
        return $this->assetSetting;
    }
    public function setAssetSetting($assetSetting)
    {
        $this->assetSetting = $assetSetting;
        return $this;
    }
    private function wrapStorageCall($methodName, array $args)
    {
        try {
            return parent::$methodName(...$args);
        } catch (\Exception $e) {
            if ($this->assetSetting) {
                try {
                    if (class_exists($this->assetSetting->configuration->handler)) {
                        $handlerClass = $this->assetSetting->configuration->handler;
                        $exceptionErrorMessage = $handlerClass::getExceptionErrorMessage($e);
                    } else {
                        $exceptionErrorMessage = $e->getMessage();
                    }
                    $assetTypeName = ucwords(FileAsset::getTypeName($this->assetSetting->asset_type) ?: $this->assetSetting->asset_type);
                    $configuration = $this->assetSetting->configuration;
                    $configuration->last_error = array("message" => $assetTypeName . ": " . $exceptionErrorMessage, "timestamp" => \WHMCS\Carbon::now()->toDateTimeString());
                    $configuration->save();
                } catch (\Exception $e) {
                }
            }
            throw $e;
        }
    }
    public function has($path)
    {
        return $this->wrapStorageCall("has", func_get_args());
    }
    public function read($path)
    {
        return $this->wrapStorageCall("read", func_get_args());
    }
    public function readStream($path)
    {
        return $this->wrapStorageCall("readStream", func_get_args());
    }
    public function listContents($directory = "", $recursive = false)
    {
        return $this->wrapStorageCall("listContents", func_get_args());
    }
    public function getMetadata($path)
    {
        return $this->wrapStorageCall("getMetadata", func_get_args());
    }
    public function getSize($path)
    {
        return $this->wrapStorageCall("getSize", func_get_args());
    }
    public function getMimetype($path)
    {
        return $this->wrapStorageCall("getMimetype", func_get_args());
    }
    public function getTimestamp($path)
    {
        return $this->wrapStorageCall("getTimestamp", func_get_args());
    }
    public function getVisibility($path)
    {
        return $this->wrapStorageCall("getVisibility", func_get_args());
    }
    public function write($path, $contents, array $config = array())
    {
        return $this->wrapStorageCall("write", func_get_args());
    }
    public function writeStream($path, $resource, array $config = array())
    {
        return $this->wrapStorageCall("writeStream", func_get_args());
    }
    public function update($path, $contents, array $config = array())
    {
        return $this->wrapStorageCall("update", func_get_args());
    }
    public function updateStream($path, $resource, array $config = array())
    {
        return $this->wrapStorageCall("updateStream", func_get_args());
    }
    public function rename($path, $newpath)
    {
        return $this->wrapStorageCall("rename", func_get_args());
    }
    public function copy($path, $newpath)
    {
        return $this->wrapStorageCall("copy", func_get_args());
    }
    public function delete($path)
    {
        return $this->wrapStorageCall("delete", func_get_args());
    }
    public function deleteDir($dirname)
    {
        return $this->wrapStorageCall("deleteDir", func_get_args());
    }
    public function createDir($dirname, array $config = array())
    {
        return $this->wrapStorageCall("createDir", func_get_args());
    }
    public function setVisibility($path, $visibility)
    {
        return $this->wrapStorageCall("setVisibility", func_get_args());
    }
    public function put($path, $contents, array $config = array())
    {
        return $this->wrapStorageCall("put", func_get_args());
    }
    public function putStream($path, $resource, array $config = array())
    {
        return $this->wrapStorageCall("putStream", func_get_args());
    }
    public function readAndDelete($path)
    {
        return $this->wrapStorageCall("readAndDelete", func_get_args());
    }
    public function get($path, \League\Flysystem\Handler $handler = NULL)
    {
        return $this->wrapStorageCall("get", func_get_args());
    }
}

?>