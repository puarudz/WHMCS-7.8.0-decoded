<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Service\Upgrade;

class Calculator
{
    protected $upgradeEntity = NULL;
    protected $upgradeTarget = NULL;
    protected $upgradeBillingCycle = NULL;
    protected $upgradeOutput = NULL;
    public function setUpgradeTargets($upgradeEntity, $upgradeTarget, $upgradeBillingCycle = NULL)
    {
        if ($upgradeEntity instanceof \WHMCS\Service\Service) {
            $requiredUpgradeObject = "\\WHMCS\\Product\\Product";
        } else {
            if ($upgradeEntity instanceof \WHMCS\Service\Addon) {
                $requiredUpgradeObject = "\\WHMCS\\Product\\Addon";
            } else {
                throw new \InvalidArgumentException("Invalid original model");
            }
        }
        if (!$upgradeTarget instanceof $requiredUpgradeObject) {
            throw new \InvalidArgumentException("Upgrade model must be of type: " . $requiredUpgradeObject);
        }
        $this->upgradeEntity = $upgradeEntity;
        $this->upgradeTarget = $upgradeTarget;
        $this->upgradeBillingCycle = $upgradeBillingCycle;
        return $this;
    }
    public function calculate()
    {
        $billingCycle = $this->upgradeBillingCycle;
        if (!$billingCycle) {
            $billingCycle = $this->upgradeEntity->billingCycle;
        }
        $userId = $this->upgradeEntity->userid;
        $currency = getCurrency($userId);
        $pricing = $this->upgradeTarget->pricing($currency)->byCycle($billingCycle);
        if (is_null($pricing)) {
            throw new \WHMCS\Exception("Invalid billing cycle for upgrade");
        }
        $newSetupFee = $pricing->setup()->toNumeric();
        $newRecurringAmount = $pricing->price()->toNumeric();
        $creditCalc = $this->calculateCredit();
        $amountDueToday = $newRecurringAmount - $creditCalc["creditAmount"];
        if ($amountDueToday < 0) {
            $amountDueToday = 0;
        }
        $upgrade = new Upgrade();
        $upgrade->userId = $userId;
        $upgrade->date = \WHMCS\Carbon::now();
        $upgrade->type = $this->getUpgradeType();
        $upgrade->entityId = $this->upgradeEntity->id;
        $upgrade->originalValue = $this->getUpgradeEntityProductIdValue();
        $upgrade->newValue = $this->upgradeTarget->id;
        $upgrade->newCycle = $billingCycle;
        $upgrade->localisedNewCycle = (new \WHMCS\Billing\Cycles())->translate($billingCycle);
        $upgrade->upgradeAmount = new \WHMCS\View\Formatter\Price($amountDueToday, $currency);
        $upgrade->recurringChange = $newRecurringAmount - $this->upgradeEntity->recurringFee;
        $upgrade->newRecurringAmount = new \WHMCS\View\Formatter\Price($newRecurringAmount, $currency);
        $upgrade->creditAmount = new \WHMCS\View\Formatter\Price($creditCalc["creditAmount"], $currency);
        $upgrade->daysRemaining = $creditCalc["daysRemaining"];
        $upgrade->totalDaysInCycle = $creditCalc["totalDaysInCycle"];
        $upgrade->applyTax = $this->upgradeTarget->applyTax;
        return $upgrade;
    }
    protected function isServiceUpgrade()
    {
        return $this->upgradeEntity instanceof \WHMCS\Service\Service;
    }
    protected function getUpgradeType()
    {
        return $this->isServiceUpgrade() ? Upgrade::TYPE_SERVICE : Upgrade::TYPE_ADDON;
    }
    protected function getUpgradeEntityProductIdValue()
    {
        return $this->isServiceUpgrade() ? $this->upgradeEntity->packageId : $this->upgradeEntity->addonId;
    }
    protected function calculateCredit()
    {
        $nextDueDate = $this->upgradeEntity->nextDueDate;
        $recurringAmount = $this->upgradeEntity->recurringFee;
        $billingCycle = $this->upgradeEntity->billingCycle;
        $daysInCurrentCycle = $this->calculateDaysInCurrentBillingCycle($nextDueDate, $billingCycle);
        if (0 < $daysInCurrentCycle) {
            $dailyRate = $recurringAmount / $daysInCurrentCycle;
        } else {
            $dailyRate = 0;
        }
        $daysRemaining = 0 < $daysInCurrentCycle ? \WHMCS\Carbon::now()->diffInDays(\WHMCS\Carbon::parse($nextDueDate)) : 0;
        $creditAmount = format_as_currency($dailyRate * $daysRemaining);
        return array("totalDaysInCycle" => $daysInCurrentCycle, "daysRemaining" => $daysRemaining, "creditAmount" => $creditAmount);
    }
    public function calculateDaysInCurrentBillingCycle($nextDueDate, $billingCycle)
    {
        if (!(new \WHMCS\Billing\Cycles())->isRecurring($billingCycle)) {
            return 0;
        }
        if (empty($nextDueDate) || $nextDueDate == "0000-00-00") {
            throw new \WHMCS\Exception("Upgrades require products have a valid next due date. Unable to continue.");
        }
        $months = (new \WHMCS\Billing\Cycles())->getNumberOfMonths($billingCycle);
        $nextDueDate = \WHMCS\Carbon::parse($nextDueDate);
        $originalDate = clone $nextDueDate;
        return $nextDueDate->subMonths($months)->diffInDays($originalDate);
    }
}

?>