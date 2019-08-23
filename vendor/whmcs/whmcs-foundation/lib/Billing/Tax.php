<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Billing;

class Tax
{
    private $level1Percentage = 0;
    private $level2Percentage = 0;
    private $isInclusive = false;
    private $isCompound = false;
    private $taxBase = 0;
    private $totalBeforeTaxes = 0;
    private $level1TaxTotal = 0;
    private $level2TaxTotal = 0;
    public function __construct()
    {
        $this->reset();
    }
    public function reset()
    {
        $this->setLevel1Percentage(0)->setLevel2Percentage(0)->setIsInclusive(false)->setIsCompound(false);
    }
    protected function validateTaxBase($taxBase)
    {
        if (!is_numeric($taxBase) || is_nan($taxBase)) {
            throw new \WHMCS\Exception\Billing\BillingException("Invalid tax base: " . (string) $taxBase);
        }
    }
    protected function validateTaxPercentage($percentage)
    {
        if (!is_numeric($percentage) || is_nan($percentage) || 100 < $percentage) {
            throw new \WHMCS\Exception\Billing\BillingException("Invalid tax percentage: " . (string) $percentage);
        }
    }
    protected function validateTaxLevelPercentages($level1Percentage, $level2Percentage)
    {
        if (100 < $level1Percentage + $level2Percentage) {
            throw new \WHMCS\Exception\Billing\BillingException("Combined L1 and L2 tax percentage is over 100%");
        }
    }
    public function getLevel1Percentage()
    {
        return $this->level1Percentage;
    }
    public function setLevel1Percentage($level1Percentage)
    {
        $this->validateTaxPercentage($level1Percentage);
        $this->validateTaxLevelPercentages($level1Percentage, $this->level2Percentage);
        $this->level1Percentage = $level1Percentage;
        $this->recalculate();
        return $this;
    }
    public function getLevel2Percentage()
    {
        return $this->level2Percentage;
    }
    public function setLevel2Percentage($level2Percentage)
    {
        $this->validateTaxPercentage($level2Percentage);
        $this->validateTaxLevelPercentages($this->level1Percentage, $level2Percentage);
        $this->level2Percentage = $level2Percentage;
        $this->recalculate();
        return $this;
    }
    public function getIsInclusive()
    {
        return $this->isInclusive;
    }
    public function setIsInclusive($isInclusive)
    {
        $this->isInclusive = $isInclusive;
        $this->recalculate();
        return $this;
    }
    public function getIsCompound()
    {
        return $this->isCompound;
    }
    public function setIsCompound($isCompound)
    {
        $this->isCompound = $isCompound;
        $this->recalculate();
        return $this;
    }
    public function getTaxBase()
    {
        return $this->taxBase;
    }
    public function setTaxBase($taxBase)
    {
        $this->validateTaxBase($taxBase);
        $this->taxBase = $taxBase;
        $this->recalculate();
        return $this;
    }
    protected function recalculate()
    {
        $level1Mult = $this->level1Percentage / 100;
        $level2Mult = $this->level2Percentage / 100;
        if ($this->isInclusive) {
            if ($this->isCompound) {
                $this->level2TaxTotal = format_as_currency($this->taxBase - $this->taxBase / (1 + $level2Mult));
                $preLevel2TaxBase = $this->taxBase - $this->level2TaxTotal;
                $this->level1TaxTotal = format_as_currency($preLevel2TaxBase - $preLevel2TaxBase / (1 + $level1Mult));
            } else {
                $preTaxBase = $this->taxBase / (1 + $level1Mult + $level2Mult);
                $this->level1TaxTotal = format_as_currency($preTaxBase * $level1Mult);
                $this->level2TaxTotal = format_as_currency($preTaxBase * $level2Mult);
            }
            $this->totalBeforeTaxes = $this->taxBase - $this->level1TaxTotal - $this->level2TaxTotal;
        } else {
            $this->totalBeforeTaxes = $this->taxBase;
            $this->level1TaxTotal = format_as_currency($this->totalBeforeTaxes * $level1Mult);
            $level2Base = $this->isCompound ? $this->totalBeforeTaxes + $this->level1TaxTotal : $this->totalBeforeTaxes;
            $this->level2TaxTotal = format_as_currency($level2Base * $level2Mult);
        }
        return $this;
    }
    public function getTotalBeforeTaxes()
    {
        return format_as_currency($this->totalBeforeTaxes);
    }
    public function getLevel1TaxTotal()
    {
        return format_as_currency($this->level1TaxTotal);
    }
    public function getLevel2TaxTotal()
    {
        return format_as_currency($this->level2TaxTotal);
    }
    public function getTotalAfterTaxes()
    {
        $total = $this->totalBeforeTaxes + $this->level1TaxTotal + $this->level2TaxTotal;
        return format_as_currency($total);
    }
}

?>