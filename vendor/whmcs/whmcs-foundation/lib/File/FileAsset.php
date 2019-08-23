<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\File;

class FileAsset
{
    const TYPE_CLIENT_FILES = "client_files";
    const TYPE_DOWNLOADS = "downloads";
    const TYPE_EMAIL_ATTACHMENTS = "email_attachments";
    const TYPE_EMAIL_TEMPLATE_ATTACHMENTS = "email_template_attachments";
    const TYPE_PM_FILES = "pm_files";
    const TYPE_TICKET_ATTACHMENTS = "ticket_attachments";
    const TYPES = NULL;
    const NO_MIGRATION_TYPES = NULL;
    public static function canMigrate($assetType)
    {
        return !in_array($assetType, self::NO_MIGRATION_TYPES);
    }
    public static function validType($assetType)
    {
        return (bool) array_key_exists($assetType, self::TYPES);
    }
    public static function getTypeName($assetType)
    {
        return self::validType($assetType) ? self::TYPES[$assetType] : null;
    }
}

?>