<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class Cron
{
    private $incli = false;
    private $debugmode = false;
    private $lasttime = "";
    private $lastmemory = "";
    private $lastaction = "";
    private $log = array();
    private $emaillog = array();
    private $emailsublog = array();
    private $args = array();
    private $doonly = false;
    private $validactions = array();
    private $starttime = "";
    private $sendreport = true;
    public static function init()
    {
        $obj = new Cron();
        $obj->incli = $obj->isRunningInCLI();
        $obj->validactions = $obj->getValidActions();
        $args = $obj->fetchArgs(true);
        if (in_array("debug", $args)) {
            $obj->setDebugMode(true);
        } else {
            $obj->setDebugMode(false);
        }
        if (in_array("skip_report", $args)) {
            $obj->sendreport = false;
        }
        $obj->determineRunMode();
        $obj->starttime = time();
        return $obj;
    }
    public function getValidActions()
    {
        $validactions = array("updaterates" => "Updating Currency Exchange Rates", "updatepricing" => "Updating Product Pricing for Current Exchange Rates", "invoices" => "Generating Invoices", "latefees" => "Applying Late Fees", "ccprocessing" => "Processing Credit Card Charges", "invoicereminders" => "Processing Invoice Reminder Notices", "domainrenewalnotices" => "Processing Domain Renewal Notices", "suspensions" => "Processing Overdue Suspensions", "terminations" => "Processing Overdue Terminations", "fixedtermterminations" => "Performing Automated Fixed Term Service Terminations", "cancelrequests" => "Processing Cancellation Requests", "closetickets" => "Auto Closing Inactive Tickets", "affcommissions" => "Processing Delayed Affiliate Commissions", "affreports" => "Sending Affiliate Reports", "emailmarketing" => "Processing Email Marketer Rules", "ccexpirynotices" => "Sending Credit Card Expiry Reminders", "usagestats" => "Updating Disk & Bandwidth Usage Stats", "overagesbilling" => "Processing Overage Billing Charges", "clientstatussync" => "Performing Client Status Sync", "backups" => "Database Backup", "report" => "Sending Email Report", "domainstatussync" => "Domain Status Synchronisation", "domaintransfersync" => "Domain Transfer Status Synchronisation");
        return $validactions;
    }
    public function isRunningInCLI()
    {
        return Environment\Php::isCli();
    }
    public function fetchArgs($force = false)
    {
        if ($this->args && !$force) {
            return $this->args;
        }
        $this->args = array();
        if ($this->incli) {
            $this->args = $_SERVER["argv"];
        } else {
            foreach ($this->validactions as $action => $name) {
                if (array_key_exists("skip_" . $action, $_REQUEST)) {
                    $this->args[] = "skip_" . $action;
                }
                if (array_key_exists("do_" . $action, $_REQUEST)) {
                    $this->args[] = "do_" . $action;
                }
            }
        }
        return $this->args;
    }
    public function setDebugMode($state = false)
    {
        $this->debugmode = $state ? true : false;
        if ($state) {
            error_reporting(Utility\ErrorManagement::ERROR_LEVEL_ERRORS_VALUE);
        } else {
            error_reporting(Utility\ErrorManagement::ERROR_LEVEL_NONE_VALUE);
        }
    }
    public function determineRunMode()
    {
        foreach ($this->args as $arg) {
            if (substr($arg, 0, 3) == "do_") {
                $this->doonly = true;
                return true;
            }
        }
        return false;
    }
    public function raiseLimits()
    {
        $minimumMemoryLimitSetting = "512M";
        $memoryLimitConfiguredBytes = Environment\Php::convertMemoryLimitToBytes(ini_get("memory_limit"));
        $memoryLimitRequiredBytes = Environment\Php::convertMemoryLimitToBytes($minimumMemoryLimitSetting);
        if (0 < $memoryLimitConfiguredBytes && $memoryLimitConfiguredBytes < $memoryLimitRequiredBytes) {
            @ini_set("memory_limit", $minimumMemoryLimitSetting);
        }
        @ini_set("max_execution_time", 0);
        @set_time_limit(0);
    }
    public function isScheduled($action)
    {
        if (!array_key_exists($action, $this->validactions)) {
            return false;
        }
        $this->emailsublog = array();
        $this->lastaction = $action;
        if ($this->isInDoOnlyMode()) {
            if (in_array("do_" . $action, $this->args)) {
                $this->logAction();
                return true;
            }
            $this->logAction(false, true);
            return false;
        }
        if (in_array("skip_" . $action, $this->args)) {
            $this->logAction(false, true);
            return false;
        }
        $this->logAction();
        return true;
    }
    private function logAction($end = false, $skip = false)
    {
        $action = $this->validactions[$this->lastaction];
        $prefix = "Starting";
        if ($end) {
            $prefix = "Completed";
        }
        if ($skip) {
            $prefix = "Skipping";
        }
        $this->logActivity($prefix . " " . $action);
        return true;
    }
    public function logActivity($msg, $sub = false)
    {
        logActivity("Cron Job: " . $msg);
        if ($sub) {
            $msg = " - " . $msg;
        }
        $this->log($msg);
        return true;
    }
    public function logActivityDebug($msg)
    {
        $this->log($msg, 1);
        return true;
    }
    public function log($msg, $verbose = 0)
    {
        if ($this->debugmode) {
            $time = microtime();
            $memory = $this->getMemUsage();
            $timediff = round($time - $this->lasttime, 2);
            $memdiff = round($memory - $this->lastmemory, 2);
            $msg .= " (Time: " . $timediff . " Memory: " . $memory . ")";
            $this->lasttime = $time;
            $this->lastmemory = $memory;
        }
        if ($this->incli) {
            echo (string) $msg . "\n";
        }
        if (!$verbose) {
            $this->log[] = $msg;
        }
    }
    private function getMemUsage()
    {
        return round(memory_get_peak_usage() / (1024 * 1024), 2);
    }
    public function logmemusage($line)
    {
        $this->log("Memory Usage @ Line " . $line . ": " . $this->getMemUsage());
    }
    public function emailLog($msg)
    {
        $this->emaillog[] = $msg;
        if (count($this->emailsublog)) {
            foreach ($this->emailsublog as $entry) {
                $this->emaillog[] = " - " . $entry;
            }
        }
        $this->emaillog[] = "";
    }
    public function emailLogSub($msg)
    {
        $this->emailsublog[] = $msg;
        $this->logActivity($msg, true);
    }
    public function emailReport()
    {
        if ($this->sendreport) {
            $cronreport = "Cron Job Report for " . date("l jS F Y @ H:i:s", $this->starttime) . "<br /><br />";
            foreach ($this->emaillog as $logentry) {
                $cronreport .= $logentry . "<br />";
            }
            sendAdminNotification("system", "WHMCS Cron Job Activity", $cronreport);
        } else {
            $this->logActivity("Skipped sending email report due to skip_report flag");
        }
    }
    public function isInDoOnlyMode()
    {
        return $this->doonly;
    }
    public static function getCronsPath($fileName)
    {
        $whmcs = \DI::make("app");
        $cronDirectory = $whmcs->getCronDirectory();
        if ($cronDirectory !== ROOTDIR . DIRECTORY_SEPARATOR . "crons") {
            throw new Exception\Fatal("Crons folder not in WHMCS root.");
        }
        $path = realpath($cronDirectory . DIRECTORY_SEPARATOR . $fileName);
        if (!$path) {
            throw new Exception("Unable to locate WHMCS crons folder.");
        }
        return $path;
    }
    public static function getCronPathErrorMessage()
    {
        return "Unable to communicate with the Custom Crons Directory.<br />\nPlease verify the path configured within the configuration.php file.<br />\nFor more information, please see <a href=\"https://docs.whmcs.com/Custom_Crons_Directory\">https://docs.whmcs.com/Custom_Crons_Directory</a>\n";
    }
    public static function getCronRootDirErrorMessage()
    {
        return "This proxy file is only valid when the crons directory is in the default location.<br />\nAs you have customised your crons directory location, you must update your cron commands to use the new path.<br />\nFor more information, please see <a href=\"https://docs.whmcs.com/Custom_Crons_Directory\">https://docs.whmcs.com/Custom_Crons_Directory</a>\n";
    }
    public static function formatOutput($output)
    {
        if (Environment\Php::isCli()) {
            $output = strip_tags(str_replace(array("<br>", "<br />", "<br/>", "<hr>"), array("\n", "\n", "\n", "\n---\n"), $output));
        }
        return $output;
    }
    public static function getLegacyCronMessage()
    {
        $message = "<div style=\"margin:0;padding:15px;border-color:#aa6708;border:1px solid #eee;border-left-width:5px;border-radius:3px;\">\n    <h4 style=\"margin:0 0 10px 0;color:#aa6708;font-size:1.2em;font-weight:500;line-height:1.1;\">\n        Cron Task Configuration\n    </h4>\n    <p style=\"margin:0;line-height:1.4;color:#333;\">\n        This cron file was invoked from a legacy filepath.<br />\n        WHMCS currently provides backwards compatibility for legacy paths so that your scheduled cron tasks will continue to invoke a valid WHMCS cron file.<br />\n        It is recommended however that you update the cron task command on your server at your earliest convenience.<br />\n        For more information, please refer to <a href=\"https://docs.whmcs.com/Cron_Tasks#Legacy_Cron_File_Locations\">\n        https://docs.whmcs.com/Cron_Tasks#Legacy_Cron_File_Locations</a>\n    </p>\n</div>";
        return $message;
    }
    public function setLastDailyCronInvocationTime(Carbon $datetime = NULL)
    {
        (new Cron\Status())->setLastDailyCronInvocationTime($datetime);
    }
    public function getLastDailyCronInvocationTime()
    {
        return (new Cron\Status())->getLastDailyCronInvocationTime();
    }
    public function hasDailyCronRunInLast24Hours()
    {
        return (new Cron\Status())->hasDailyCronRunInLast24Hours();
    }
    public function hasDailyCronEverRun()
    {
        return (new Cron\Status())->hasDailyCronEverRun();
    }
    public function hasCronEverBeenInvoked()
    {
        return (new Cron\Status())->hasCronEverBeenInvoked();
    }
    public static function getDailyCronExecutionHour()
    {
        return Cron\Status::getDailyCronExecutionHour();
    }
    public static function setDailyCronExecutionHour($time = "09")
    {
        Cron\Status::setDailyCronExecutionHour($time);
    }
    public function isOkayToRunDailyCronNow()
    {
        return (new Cron\Status())->isOkayToRunDailyCronNow();
    }
    public function hasCronBeenInvokedIn24Hours()
    {
        return (new Cron\Status())->hasCronBeenInvokedIn24Hours();
    }
    public function getLastCronInvocationTime()
    {
        return (new Cron\Status())->getLastCronInvocationTime();
    }
    public function setCronInvocationTime()
    {
        (new Cron\Status())->setCronInvocationTime();
    }
}

?>