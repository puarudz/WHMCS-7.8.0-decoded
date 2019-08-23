<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Console\Helper;

class DailyCronHelper implements \Symfony\Component\Console\Helper\HelperInterface
{
    protected $helperSet = NULL;
    protected $report = NULL;
    protected $io = NULL;
    protected $isDailyCronInvocation = NULL;
    protected $status = NULL;
    public function __construct(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output, \WHMCS\Cron\Status $status)
    {
        $this->io = new \WHMCS\Cron\Console\Style\TaskStyle($input, $output);
        $this->status = $status;
        $this->isDailyCronInvocation = $this->calculateIfIsDailyCronInvocation();
    }
    public function setHelperSet(\Symfony\Component\Console\Helper\HelperSet $helperSet = NULL)
    {
        $this->helperSet = $helperSet;
    }
    public function getHelperSet()
    {
        return $this->helperSet;
    }
    public function getName()
    {
        return "daily-cron";
    }
    public function calculateIfIsDailyCronInvocation()
    {
        if ($this->status->isOkayToRunDailyCronNow()) {
            return true;
        }
        if ($this->io->hasForceOption()) {
            return true;
        }
        return false;
    }
    public function isDailyCronInvocation()
    {
        return $this->isDailyCronInvocation;
    }
    public function getReport()
    {
        if (!$this->report) {
            $dailyCronHelper = $this;
            add_hook("PostAutomationTask", 0, function ($task, $completed) use($dailyCronHelper) {
                $dailyCronHelper->hookRegisterTaskCompletion($task, $completed);
            });
            $this->report = new \WHMCS\Log\Register\DailyCronReport();
        }
        return $this->report;
    }
    public function sendDailyCronDigest()
    {
        $sendReport = true;
        $reason = "";
        if ($this->io->getInput()->hasOption("email-report") && !$this->io->getInput()->getOption("email-report")) {
            $sendReport = false;
            $reason = " per command options";
        }
        $hookResults = run_hook("DailyCronJobPreEmail", array());
        foreach ($hookResults as $result) {
            if ($result == true) {
                $sendReport = false;
                $reason = " per result of DailyCronJobPreEmail hook";
            }
        }
        if ($this->io->isDebug()) {
            $this->io->text(sprintf("%s Daily Cron Digest email%s", $sendReport ? "Sending" : "Not sending", $reason));
        }
        if ($sendReport) {
            sendAdminNotification("system", "WHMCS Cron Job Activity", $this->getReport()->toHtmlDigest(), 0, false);
        }
    }
    public function isDailyCronRunningOnTime()
    {
        $dailyCronHour = $this->status->getDailyCronExecutionHour();
        $dailyCronHourPassedToday = $dailyCronHour->format("H") < \WHMCS\Carbon::now()->format("H");
        $lastDailyCronRun = $this->status->getLastDailyCronInvocationTime();
        $lastDailyCronWasRunToday = !is_null($lastDailyCronRun) ? $lastDailyCronRun->isToday() : false;
        if ($dailyCronHourPassedToday && !$lastDailyCronWasRunToday) {
            return false;
        }
        if (!$this->status->hasDailyCronRunSince(32)) {
            return false;
        }
        return true;
    }
    public function sendDailyNotificationDailyCronNotExecuting()
    {
        if (!$this->status->hasDailyCronEverRun()) {
            return $this;
        }
        $dailyCronHour = $this->status->getDailyCronExecutionHour();
        $hasBeenNotifiedToday = false;
        $lastNotification = \WHMCS\TransientData::getInstance()->retrieve("lastNotificationDailyCronOutOfSync");
        if ($lastNotification) {
            $lastNotification = new \WHMCS\Carbon($lastNotification);
            $hasBeenNotifiedToday = $lastNotification->isToday();
        }
        if (!$hasBeenNotifiedToday) {
            $outOfSyncCronMessage = "Your WHMCS is configured to perform the Daily System Cron during the hour of %hour%.\n However, the Daily System Cron did not execute within that hour as expected.\n<br/><br/>\nThis may be due to the scheduled time specified in your web hosting control\n panel's cron entry.  Please ensure your web hosting control panel executes the\n WHMCS System Cron (cron.php) at least once during the hour of %hour%.\n<br/><br/>\nIf you have confirmed that setting, and you continue to receive this message,\n then please refer to the <a href=\"https://docs.whmcs.com/Crons\" target=\"_blank\">\nWHMCS Cron documentation</a> to ensure you have itemized the appropriate command\n and any additional options.\n<br/><br/>\nPlease contact <a href=\"https://whmcs.com/support\" target=\"_blank\">WHMCS Support\n</a> if you require further assistance.";
            $outOfSyncCronMessage = str_replace("%hour%", $dailyCronHour->format("g a"), $outOfSyncCronMessage);
            $outOfSyncCronMessage = str_replace("\n", "", $outOfSyncCronMessage);
            sendAdminNotification("system", "WHMCS Daily System Cron Attention Needed", $outOfSyncCronMessage);
            \WHMCS\TransientData::getInstance()->store("lastNotificationDailyCronOutOfSync", \WHMCS\Carbon::now()->toDateTimeString(), 1460);
        }
        return $this;
    }
    public function hookRegisterTaskCompletion($task, $completed = true)
    {
        $report = $this->getReport();
        if ($completed) {
            $report->completed($task);
        } else {
            $report->notCompleted($task);
        }
    }
    public function startDailyCron()
    {
        $this->status->setLastDailyCronInvocationTime();
        logActivity("Cron Job: Starting Daily Automation Tasks");
        \WHMCS\TransientData::getInstance()->delete("cronComplete");
        $this->getReport()->start();
        run_hook("PreCronJob", array());
    }
    public function endDailyCron()
    {
        \WHMCS\Billing\Tax\Vat::resetNumbers();
        \WHMCS\TransientData::getInstance()->store("cronComplete", "true", 86400);
        logActivity("Cron Job: Completed Daily Automation Tasks");
        $this->getReport()->finish();
        $this->sendDailyCronDigest();
        run_hook("DailyCronJob", array());
    }
}

?>