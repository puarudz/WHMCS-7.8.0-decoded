<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version700release1 extends IncrementalVersion
{
    protected $updateActions = array("mutateDailyCronConfigurations");
    public function mutateDailyCronConfigurations()
    {
        $transientData = \WHMCS\TransientData::getInstance();
        $lastCronInvocationTime = $transientData->retrieve("lastCronInvocationTime");
        $cron = new \WHMCS\Cron();
        if (!$lastCronInvocationTime) {
            $runEntry = \WHMCS\Database\Capsule::table("tblactivitylog")->where("description", "like", "%Cron Job: Starting%")->orderBy("id", "desc")->first();
            if ($runEntry) {
                $lastRun = new \WHMCS\Carbon($runEntry->date);
                \WHMCS\Cron::setDailyCronExecutionHour($lastRun->format("H"));
                $cron->setLastDailyCronInvocationTime($lastRun);
            }
            return $this;
        }
        $lastRun = new \WHMCS\Carbon($lastCronInvocationTime);
        \WHMCS\Cron::setDailyCronExecutionHour($lastRun->format("H"));
        $cron->setLastDailyCronInvocationTime($lastRun);
        return $this;
    }
}

?>