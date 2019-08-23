<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\File;

class FileAssetCollection extends \Illuminate\Support\Collection
{
    public static function forAssetType($assetType)
    {
        switch ($assetType) {
            case FileAsset::TYPE_CLIENT_FILES:
                $items = \WHMCS\Database\Capsule::table("tblclientsfiles")->where("filename", "!=", "")->pluck("filename");
                break;
            case FileAsset::TYPE_DOWNLOADS:
                $items = \WHMCS\Database\Capsule::table("tbldownloads")->pluck("location");
                break;
            case FileAsset::TYPE_EMAIL_ATTACHMENTS:
                throw new \WHMCS\Exception\Storage\StorageException("Email attachment files cannot be listed");
            case FileAsset::TYPE_EMAIL_TEMPLATE_ATTACHMENTS:
                $templateAttachmentLists = \WHMCS\Database\Capsule::table("tblemailtemplates")->where("attachments", "!=", "")->pluck("attachments");
                $items = array();
                foreach ($templateAttachmentLists as $list) {
                    $items = array_merge($items, explode(",", $list));
                }
                break;
            case FileAsset::TYPE_PM_FILES:
                $items = array();
                if (\WHMCS\Database\Capsule::schema()->hasTable("mod_project_management_files")) {
                    $pmFiles = \WHMCS\Database\Capsule::table("mod_project_management_files")->get(array("project_id", "filename"));
                    foreach ($pmFiles as $pmFile) {
                        $items[] = $pmFile->project_id . DIRECTORY_SEPARATOR . $pmFile->filename;
                    }
                }
                break;
            case FileAsset::TYPE_TICKET_ATTACHMENTS:
                $attachmentsInDb = \WHMCS\Database\Capsule::table("tbltickets")->where("attachment", "!=", "")->where("attachments_removed", 0)->union(\WHMCS\Database\Capsule::table("tblticketreplies")->where("attachment", "!=", "")->select("attachment")->where("attachments_removed", 0))->union(\WHMCS\Database\Capsule::table("tblticketnotes")->where("attachments", "!=", "")->select("attachments")->where("attachments_removed", 0))->pluck("attachment");
                $items = array();
                foreach ($attachmentsInDb as $attachment) {
                    $attachments = explode("|", $attachment);
                    foreach ($attachments as $a) {
                        $a = trim($a);
                        if ($a) {
                            array_push($items, $a);
                        }
                    }
                }
                break;
            default:
                throw new \WHMCS\Exception\Storage\StorageException("Unknown asset type: " . $assetType);
        }
        return new static($items);
    }
}

?>