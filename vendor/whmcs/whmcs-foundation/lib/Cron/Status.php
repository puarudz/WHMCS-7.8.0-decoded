<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron;

class Status
{
    public function setLastDailyCronInvocationTime(\WHMCS\Carbon $datetime = NULL)
    {
        if (!$datetime instanceof \WHMCS\Carbon) {
            $datetime = \WHMCS\Carbon::now();
        }
        \WHMCS\Config\Setting::setValue("lastDailyCronInvocationTime", $datetime->toDateTimeString());
    }
    public function getLastDailyCronInvocationTime()
    {
        $datetime = null;
        $lastDailyTime = \WHMCS\Config\Setting::getValue("lastDailyCronInvocationTime");
        if (!empty($lastDailyTime)) {
            try {
                $datetime = new \WHMCS\Carbon($lastDailyTime);
            } catch (\Exception $e) {
            }
        }
        return $datetime;
    }
    public function hasDailyCronRunInLast24Hours()
    {
        return $this->hasDailyCronRunSince(24);
    }
    public function hasDailyCronRunSince($hours)
    {
        $lastCronInvocationTime = $this->getLastDailyCronInvocationTime();
        if (!empty($lastCronInvocationTime)) {
            $lastCronInvocationTime = new \WHMCS\Carbon($lastCronInvocationTime);
            $minTime = \WHMCS\Carbon::now()->subHours((int) $hours);
            if ($lastCronInvocationTime->gt($minTime)) {
                return true;
            }
        }
        return false;
    }
    public function hasDailyCronEverRun()
    {
        $lastCronInvocationTime = $this->getLastDailyCronInvocationTime();
        return !empty($lastCronInvocationTime);
    }
    public function hasCronEverBeenInvoked()
    {
        return $this->getLastCronInvocationTime();
    }
    public static function getDailyCronExecutionHour()
    {
        $hour = \WHMCS\Config\Setting::getValue("DailyCronExecutionHour");
        $datetime = new \WHMCS\Carbon("January 2, 1970 00:00:00");
        if (!$hour) {
            $datetime->hour("09");
        } else {
            $datetime->hour($hour);
        }
        return $datetime;
    }
    public static function setDailyCronExecutionHour($time = "09")
    {
        try {
            if (is_numeric($time)) {
                $time = (string) $time;
                if (strlen($time) != 2) {
                    $time = "0" . $time;
                }
                $time .= ":00:00";
            }
            $datetime = new \WHMCS\Carbon("January 2, 1970 " . $time);
        } catch (\Exception $e) {
            $datetime = new \WHMCS\Carbon("January 2, 1970 09:00:00");
        }
        \WHMCS\Config\Setting::setValue("DailyCronExecutionHour", $datetime->format("H"));
    }
    public function isOkayToRunDailyCronNow()
    {
        $lastDailyRunTime = $this->getLastDailyCronInvocationTime();
        $now = \WHMCS\Carbon::now();
        $dailyCronHourWindowStart = self::getDailyCronExecutionHour();
        if ($now->format("H") == $dailyCronHourWindowStart->format("H")) {
            if (!$lastDailyRunTime) {
                return true;
            }
            if (!$now->isSameDay($lastDailyRunTime)) {
                return true;
            }
        }
        return false;
    }
    public function hasCronBeenInvokedIn24Hours()
    {
        if ($this->hasDailyCronRunInLast24Hours()) {
            return true;
        }
        $invokeTime = $this->getLastCronInvocationTime();
        if (!empty($invokeTime)) {
            $now = \WHMCS\Carbon::now();
            $minimumDateTimeForNextInvocation = $invokeTime->addDay()->second(0)->subMinute();
            if ($now->lt($minimumDateTimeForNextInvocation)) {
                return true;
            }
        }
        return false;
    }
    public function getLastCronInvocationTime()
    {
        $transientData = \WHMCS\TransientData::getInstance();
        $anyInvocation = $transientData->retrieve("lastCronInvocationTime");
        if ($anyInvocation) {
            try {
                return new \WHMCS\Carbon($anyInvocation);
            } catch (\Exception $e) {
                return null;
            }
        }
        return $this->getLastDailyCronInvocationTime();
    }
    public function setCronInvocationTime()
    {
        $now = \WHMCS\Carbon::now();
        \WHMCS\TransientData::getInstance()->store("lastCronInvocationTime", $now->toDateTimeString(), 48 * 60 * 60);
    }
}

?>