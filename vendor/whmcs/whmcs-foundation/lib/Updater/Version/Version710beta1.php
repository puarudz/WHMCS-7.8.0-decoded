<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version710beta1 extends IncrementalVersion
{
    protected $updateActions = array("updateCronTasksNextDue", "migrateLookupProviderSettings", "removeLegacyAdminWidgets");
    protected function updateCronTasksNextDue()
    {
        $cronStatus = new \WHMCS\Cron\Status();
        $lastDailyRun = $cronStatus->getLastDailyCronInvocationTime();
        if (empty($lastDailyRun)) {
            $lastDailyRun = $cronStatus->getLastCronInvocationTime();
        }
        if (empty($lastDailyRun)) {
            $runEntry = \WHMCS\Database\Capsule::table("tblactivitylog")->where("description", "like", "%Cron Job: Starting%")->orderBy("id", "desc")->first();
            if ($runEntry) {
                $lastDailyRun = new \WHMCS\Carbon($runEntry->date);
                $cronStatus->setDailyCronExecutionHour($lastDailyRun->format("H"));
                $cronStatus->setLastDailyCronInvocationTime($lastDailyRun);
            } else {
                $lastDailyRun = \WHMCS\Carbon::now();
            }
        }
        $tasks = \WHMCS\Scheduling\Task\AbstractTask::all();
        foreach ($tasks as $task) {
            $taskStatus = $task->getStatus();
            $taskStatus->setLastRuntime($lastDailyRun)->setNextDue($task->anticipatedNextRun($lastDailyRun));
        }
    }
    protected function migrateLookupProviderSettings()
    {
        $existingProviderSettings = \WHMCS\Database\Capsule::table("tblconfiguration")->where("setting", "like", "domainLookup\\_%")->get();
        foreach ($existingProviderSettings as $existingProviderSetting) {
            $settingParts = explode("_", $existingProviderSetting->setting);
            $registrar = $settingParts[1];
            $value = $existingProviderSetting->value;
            if ($registrar != "WhmcsWhois") {
                $registrar = strtolower($registrar);
            }
            $newSetting = \WHMCS\Domains\DomainLookup\Settings::firstOrNew(array("registrar" => $registrar, "setting" => $settingParts[2]));
            if ($value == "") {
                $value = 0;
            } else {
                if ($settingParts[2] == "suggestMaxResultCount" && 100 < (int) $value) {
                    $value = 100;
                }
            }
            $newSetting->value = $value;
            $newSetting->save();
            \WHMCS\Config\Setting::find($existingProviderSetting->setting)->delete();
        }
    }
    protected function removeLegacyAdminWidgets()
    {
        $widgetFilesToRemove = array("activity_log.php", "admin_activity.php", "calendar.php", "client_activity.php", "getting_started.php", "income_forecast.php", "income_overview.php", "my_notes.php", "network_status.php", "open_invoices.php", "orders_overview.php", "system_overview.php", "tickets_overview.php", "todo_list.php", "whmcs_news.php");
        $filePath = ROOTDIR . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . "widgets" . DIRECTORY_SEPARATOR;
        foreach ($widgetFilesToRemove as $filename) {
            $fullPath = $filePath . $filename;
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
        }
    }
}

?>