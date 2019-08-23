<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Billing;

class Cycles
{
    protected $nonRecurringCycles = NULL;
    protected $recurringCycles = array("monthly" => "Monthly", "quarterly" => "Quarterly", "semiannually" => "Semi-Annually", "annually" => "Annually", "biennially" => "Biennially", "triennially" => "Triennially");
    protected $monthsToCyclesMap = NULL;
    const CYCLE_FREE = "free";
    const CYCLE_ONETIME = "onetime";
    const DISPLAY_FREE = "Free Account";
    const DISPLAY_ONETIME = "One Time";
    public function getSystemBillingCycles($excludeNonRecurring = false)
    {
        if ($excludeNonRecurring) {
            $allCycles = $this->getRecurringCycles();
        } else {
            $allCycles = array_merge($this->nonRecurringCycles, $this->getRecurringCycles());
        }
        $cycles = array();
        foreach ($allCycles as $k => $v) {
            $cycles[] = $k;
        }
        return $cycles;
    }
    public function getRecurringSystemBillingCycles()
    {
        return $this->getSystemBillingCycles(true);
    }
    public function isValidSystemBillingCycle($cycle)
    {
        return in_array($cycle, $this->getSystemBillingCycles());
    }
    public function isValidPublicBillingCycle($cycle)
    {
        return in_array($cycle, $this->getPublicBillingCycles());
    }
    public function getPublicBillingCycles()
    {
        $allCycles = array_merge($this->nonRecurringCycles, $this->getRecurringCycles());
        $cycles = array();
        foreach ($allCycles as $k => $v) {
            $cycles[] = $v;
        }
        return $cycles;
    }
    public function getRecurringCycles()
    {
        return $this->recurringCycles;
    }
    public function getPublicBillingCycle($cycle)
    {
        $allCycles = array_merge($this->nonRecurringCycles, $this->getRecurringCycles());
        return array_key_exists($cycle, $allCycles) ? $allCycles[$cycle] : "";
    }
    public function getNormalisedBillingCycle($cycle)
    {
        $cycle = strtolower($cycle);
        $cycle = preg_replace("/[^a-z]/i", "", $cycle);
        if ($cycle == "freeaccount") {
            $cycle = "free";
        }
        return $this->isValidSystemBillingCycle($cycle) ? $cycle : "";
    }
    public function getNameByMonths($months)
    {
        return isset($this->monthsToCyclesMap[$months]) ? $this->monthsToCyclesMap[$months] : "";
    }
    public function getNumberOfMonths($cycle)
    {
        $cycles = array_flip($this->monthsToCyclesMap);
        if (array_key_exists($cycle, $cycles)) {
            return $cycles[$cycle];
        }
        $normalisedCycle = $this->getNormalisedBillingCycle($cycle);
        $cycle = $this->getPublicBillingCycle($normalisedCycle);
        if (array_key_exists($cycle, $cycles)) {
            return $cycles[$cycle];
        }
        throw new \WHMCS\Exception("Invalid billing cycle provided");
    }
    public function isRecurring($cycle)
    {
        $recurringCycles = $this->getRecurringCycles();
        if (in_array($cycle, $recurringCycles) || array_key_exists($cycle, $recurringCycles)) {
            return true;
        }
        return false;
    }
    public function translate($cycle)
    {
        return \Lang::trans("orderpaymentterm" . $this->getNormalisedBillingCycle($cycle));
    }
    public function getGreaterCycles($cycle)
    {
        $currentCycleMonths = $this->getNumberOfMonths($cycle);
        $cyclesToReturn = array();
        foreach ($this->monthsToCyclesMap as $numMonths => $displayLabel) {
            if ($currentCycleMonths <= $numMonths && $numMonths != 100) {
                $cyclesToReturn[] = $this->getNormalisedBillingCycle($displayLabel);
            }
        }
        return $cyclesToReturn;
    }
}

?>