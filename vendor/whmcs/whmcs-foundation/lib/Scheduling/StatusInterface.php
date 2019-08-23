<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Scheduling;

interface StatusInterface
{
    public function isInProgress();
    public function isDueNow();
    public function calculateAndSetNextDue();
    public function setNextDue(\WHMCS\Carbon $nextDue);
    public function setInProgress($state);
    public function getLastRuntime();
    public function setLastRuntime(\WHMCS\Carbon $date);
    public function getNextDue();
}

?>