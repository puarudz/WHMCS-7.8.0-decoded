<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module;

class LegacyModuleCleanup
{
    const JOB_UPDATER_ADMIN_NOTIFICATION = "updater.legacyModuleCleanup.adminNotification";
    private function isModuleUsed($moduleName, $moduleType)
    {
        $queries = array();
        switch ($moduleType) {
            case AbstractModule::TYPE_ADMIN:
                return true;
            case AbstractModule::TYPE_ADDON:
                $queries[] = \WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", $moduleName);
                break;
            case AbstractModule::TYPE_FRAUD:
                $queries[] = \WHMCS\Database\Capsule::table("tblfraud")->where("fraud", $moduleName)->where("setting", "Enable")->where("value", "!=", "");
                break;
            case AbstractModule::TYPE_GATEWAY:
                $queries[] = \WHMCS\Database\Capsule::table("tblpaymentgateways")->where("gateway", $moduleName);
                break;
            case AbstractModule::TYPE_NOTIFICATION:
                $queries[] = \WHMCS\Notification\Provider::where("name", $moduleName)->active();
                break;
            case AbstractModule::TYPE_REGISTRAR:
                $queries[] = \WHMCS\Database\Capsule::table("tblregistrars")->where("registrar", $moduleName);
                break;
            case AbstractModule::TYPE_REPORT:
                return true;
            case AbstractModule::TYPE_SECURITY:
                $twofa = new \WHMCS\TwoFactorAuthentication();
                if ($twofa->isModuleEnabled($moduleName)) {
                    return true;
                }
                $queries[] = \WHMCS\Database\Capsule::table("tblclients")->where("authmodule", $moduleName);
                break;
            case AbstractModule::TYPE_SERVER:
                $queries[] = \WHMCS\Database\Capsule::table("tblservers")->where("type", $moduleName);
                $queries[] = \WHMCS\Database\Capsule::table("tblproducts")->where("servertype", $moduleName);
                break;
            case AbstractModule::TYPE_SOCIAL:
                return $moduleName === "twitter";
            case AbstractModule::TYPE_SUPPORT:
                return \WHMCS\Config\Setting::getValue("SupportModule") === $moduleName;
            case AbstractModule::TYPE_WIDGET:
                return false;
            default:
                break;
        }
        if (!empty($queries)) {
            foreach ($queries as $query) {
                if (0 < (bool) $query->count()) {
                    return true;
                }
            }
            return false;
        } else {
            throw new \WHMCS\Exception("Unknown module type: " . $moduleType);
        }
    }
    private function getModuleAssets($moduleName, $moduleType)
    {
        if ($moduleName === "callback" && $moduleType === AbstractModule::TYPE_GATEWAY) {
            return array();
        }
        $moduleAssetPaths = array();
        $directoryPath = ROOTDIR . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . $moduleType . DIRECTORY_SEPARATOR . $moduleName;
        if (is_dir($directoryPath)) {
            $moduleAssetPaths[] = $directoryPath;
        }
        $filePath = $directoryPath . ".php";
        if (is_file($filePath)) {
            $moduleAssetPaths[] = $filePath;
        }
        if ($moduleType === AbstractModule::TYPE_GATEWAY) {
            $callbackPath = ROOTDIR . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . $moduleType . DIRECTORY_SEPARATOR . "callback" . DIRECTORY_SEPARATOR . $moduleName . ".php";
            if (is_file($callbackPath)) {
                $moduleAssetPaths[] = $callbackPath;
            }
        }
        return $moduleAssetPaths;
    }
    private function isModulePresent($moduleName, $moduleType)
    {
        if (!in_array($moduleType, AbstractModule::ALL_TYPES)) {
            throw new \WHMCS\Exception("Unknown module type: " . $moduleType);
        }
        return $this->getModuleAssets($moduleName, $moduleType);
    }
    public function removeModule($moduleName, $moduleType)
    {
        if ($moduleType === AbstractModule::TYPE_GATEWAY && $moduleName === "callback") {
            throw new \WHMCS\Exception("Cannot remove a gateway \"module\" named \"callback\"");
        }
        $moduleAssets = $this->getModuleAssets($moduleName, $moduleType);
        if (empty($moduleAssets)) {
            throw new \WHMCS\Exception("Invalid module name or type");
        }
        foreach ($moduleAssets as $asset) {
            if (is_dir($asset)) {
                \WHMCS\Utility\File::recursiveDelete($asset, array(), true);
            } else {
                if (is_file($asset) && !@unlink($asset)) {
                    throw new \WHMCS\Exception("Failed to delete module asset: " . $asset);
                }
            }
        }
    }
    public function removeModulesIfInstalledAndUnused(array $modules)
    {
        $modulesInUse = array();
        $modulesUnusedAndDeleted = array();
        $modulesFailedDeletion = array();
        foreach ($modules as $moduleType => $moduleNames) {
            foreach ($moduleNames as $moduleName) {
                try {
                    if ($this->isModulePresent($moduleName, $moduleType)) {
                        if ($this->isModuleUsed($moduleName, $moduleType)) {
                            $modulesInUse[] = $moduleName;
                        } else {
                            $this->removeModule($moduleName, $moduleType);
                            $modulesUnusedAndDeleted[] = $moduleName;
                        }
                    }
                } catch (\Exception $e) {
                    $modulesFailedDeletion[] = $moduleName . " (" . $e->getMessage() . ")";
                }
            }
        }
        $adminNotificationMessageLines = array();
        if (!empty($modulesUnusedAndDeleted)) {
            logActivity("Unused legacy modules were deleted: " . implode(", ", $modulesUnusedAndDeleted));
        }
        if (!empty($modulesInUse)) {
            $adminNotificationMessageLines[] = "One or more legacy modules were found to be in use and were not attempted to be deleted: " . implode(", ", $modulesInUse);
        }
        if (!empty($modulesFailedDeletion)) {
            $adminNotificationMessageLines[] = "One or more legacy modules could not be deleted: " . implode(", ", $modulesFailedDeletion);
        }
        if (!empty($adminNotificationMessageLines)) {
            foreach ($adminNotificationMessageLines as $logLine) {
                logActivity($logLine);
            }
            $to = "system";
            $subject = "Legacy WHMCS Module Cleanup Results";
            $message = "<p>A recent WHMCS update failed to remove legacy modules. Legacy modules are modules that are no longer maintained or distributed and are typically for older and often discontinued services. ";
            $message .= "The presence of these modules in your WHMCS installation can cause negative behaviour when it comes to upgrading PHP due to ionCube incompatibility and therefore we recommend removing them if they are no longer in use.</p>";
            $message .= implode("<br>", $adminNotificationMessageLines);
            $message .= "<p>To learn more, visit <a href=\"https://docs.whmcs.com/Legacy_Module_Removal\">https://docs.whmcs.com/Legacy_Module_Removal</a></p>";
            $queue = new \WHMCS\Scheduling\Jobs\Queue();
            $queue->add(self::JOB_UPDATER_ADMIN_NOTIFICATION, "WHMCS\\Mail\\Job\\AdminNotification", "send", array($to, $subject, $message), 0, true);
        }
    }
}

?>