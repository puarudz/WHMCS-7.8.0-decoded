<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version610alpha1 extends IncrementalVersion
{
    protected $updateActions = array("migrateMaxMindIgnoreCity", "moveAttachmentsProjectsFiles", "detectCronRunForHealthAndUpdates");
    public function __construct(\WHMCS\Version\SemanticVersion $version)
    {
        parent::__construct($version);
        $this->filesToRemove[] = ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "classes" . DIRECTORY_SEPARATOR . "WHMCS" . DIRECTORY_SEPARATOR . "Http" . DIRECTORY_SEPARATOR . "Client";
        $config = \DI::make("config");
        $adminDir = $config::DEFAULT_ADMIN_FOLDER;
        if ($config->customadminpath) {
            $adminDir = $config->customadminpath;
        }
        $this->filesToRemove[] = ROOTDIR . DIRECTORY_SEPARATOR . $adminDir . DIRECTORY_SEPARATOR . "systemupdates.php";
    }
    protected function migrateMaxMindIgnoreCity()
    {
        $maxmindFraudSetting = \WHMCS\Database\Capsule::table("tblfraud")->where("fraud", "maxmind")->where("setting", "Do Not Include City")->count();
        if (0 < $maxmindFraudSetting) {
            \WHMCS\Database\Capsule::table("tblfraud")->where("fraud", "maxmind")->where("setting", "Do Not Include City")->update(array("setting" => "Do Not Validate Address Information"));
        }
        return $this;
    }
    protected function moveAttachmentsProjectsFiles()
    {
        $config = \DI::make("config");
        $attachmentsDir = $config->getAbsoluteAttachmentsPath();
        $badProjectsAttachmentsDir = $attachmentsDir . "projects";
        $goodProjectsAttachmentsDir = $attachmentsDir . DIRECTORY_SEPARATOR . "projects";
        if (!is_dir($badProjectsAttachmentsDir)) {
            return $this;
        }
        if (!is_dir($goodProjectsAttachmentsDir)) {
            mkdir($goodProjectsAttachmentsDir);
        }
        try {
            \WHMCS\Utility\File::recursiveCopy($badProjectsAttachmentsDir, $goodProjectsAttachmentsDir);
        } catch (\Exception $e) {
            \Log::warn($e->getMessage());
        }
        return $this;
    }
    protected function detectCronRunForHealthAndUpdates()
    {
        $cronJobCompletedWithinLast24Hours = \WHMCS\Database\Capsule::table("tblactivitylog")->where("description", "Cron Job: Completed")->whereBetween("date", array(\WHMCS\Carbon::now()->subDay()->format("Y-m-d H:i:s"), \WHMCS\Carbon::now()->format("Y-m-d H:i:s")))->count();
        if (0 < $cronJobCompletedWithinLast24Hours) {
            \WHMCS\Database\Capsule::table("tbltransientdata")->insert(array("name" => "cronComplete", "data" => true, "expires" => \WHMCS\Carbon::now()->addDay()->timestamp));
        }
        return $this;
    }
}

?>