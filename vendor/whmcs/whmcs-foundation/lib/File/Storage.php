<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\File;

final class Storage
{
    private function createFilesystem($assetType, $subPath = "")
    {
        $subPath = ltrim($subPath, DIRECTORY_SEPARATOR);
        $assetSetting = Configuration\FileAssetSetting::forAssetType($assetType)->first();
        if (!$assetSetting) {
            throw new \WHMCS\Exception\Storage\StorageException(sprintf("Cannot find storage setting for asset type \"%s\" (%s)", $assetType, FileAsset::getTypeName($assetType) ?: "unknown"));
        }
        $fileSystem = new Filesystem($assetSetting->createFilesystemAdapter($subPath));
        $fileSystem->setAssetSetting($assetSetting);
        return $fileSystem;
    }
    public function clientFiles()
    {
        return $this->createFilesystem(FileAsset::TYPE_CLIENT_FILES);
    }
    public function downloads()
    {
        return $this->createFilesystem(FileAsset::TYPE_DOWNLOADS);
    }
    public function emailAttachments()
    {
        return $this->createFilesystem(FileAsset::TYPE_EMAIL_ATTACHMENTS);
    }
    public function emailTemplateAttachments()
    {
        return $this->createFilesystem(FileAsset::TYPE_EMAIL_TEMPLATE_ATTACHMENTS);
    }
    public function projectManagementFiles($projectId = NULL)
    {
        $subPath = "";
        if (!is_null($projectId)) {
            $subPath = DIRECTORY_SEPARATOR . (int) $projectId;
        }
        return $this->createFilesystem(FileAsset::TYPE_PM_FILES, $subPath);
    }
    public function ticketAttachments()
    {
        return $this->createFilesystem(FileAsset::TYPE_TICKET_ATTACHMENTS);
    }
}

?>