<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\File\Migration\Processor;

trait LocalCapableProcessorTrait
{
    private $targetDirMode = NULL;
    public function validateLocalPath($localDir)
    {
        if (is_dir($localDir) && is_writable($localDir)) {
            $this->targetDirMode = stat($localDir)["mode"];
        } else {
            throw new \WHMCS\Exception\Storage\AssetMigrationException(sprintf("%s directory does not exist or is not writable", $localDir));
        }
    }
    public function createDirectoriesForFile($filePath)
    {
        if (is_null($this->targetDirMode)) {
            throw new \WHMCS\Exception\Storage\AssetMigrationException("Invalid target dir mode - must have run validateLocalPath() first");
        }
        $dirPath = dirname($filePath);
        if (is_dir($dirPath)) {
            if (!is_writable($dirPath)) {
                throw new \WHMCS\Exception\Storage\AssetMigrationException(sprintf("%s directory exists but is not writable", $dirPath));
            }
        } else {
            if (!mkdir($dirPath, $this->targetDirMode, true)) {
                throw new \WHMCS\Exception\Storage\AssetMigrationException(sprintf("Cannot create directory: %s", $dirPath));
            }
        }
    }
}

?>