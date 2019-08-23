<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Product\Pricing;

class Price
{
    protected $price = NULL;
    public function __construct($price)
    {
        $this->price = $price;
        if (!isset($price["breakdown"]) && !is_null($price["price"])) {
            $this->price["breakdown"] = array();
            if ($this->isYearly()) {
                $yearlyPrice = $price["price"]->toNumeric() / (int) $this->cycleInYears();
                $this->price["breakdown"]["yearly"] = new \WHMCS\View\Formatter\Price($yearlyPrice, $this->price()->getCurrency());
            } else {
                $cycleMonths = $this->cycleInMonths();
                if ($cycleMonths < 1) {
                    $cycleMonths = 1;
                }
                $yearlyPrice = $price["price"]->toNumeric() / (int) $cycleMonths;
                $this->price["breakdown"]["monthly"] = new \WHMCS\View\Formatter\Price($yearlyPrice, $this->price()->getCurrency());
            }
        }
    }
    public function cycle()
    {
        return $this->price["cycle"];
    }
    public function isFree()
    {
        return $this->cycle() == "free";
    }
    public function isOneTime()
    {
        return $this->cycle() == "onetime";
    }
    public function isRecurring()
    {
        return in_array($this->cycle(), (new \WHMCS\Billing\Cycles())->getRecurringSystemBillingCycles());
    }
    public function setup()
    {
        return $this->price["setupfee"];
    }
    public function price()
    {
        return $this->price["price"];
    }
    public function breakdown()
    {
        return $this->price["breakdown"];
    }
    public function toPrefixedString()
    {
        $priceString = "";
        $price = $this->price();
        if (!is_null($price)) {
            $priceString .= $price->toPrefixed();
            if ($this->isRecurring()) {
                $priceString .= "/" . $this->getShortCycle();
            }
        }
        $setup = $this->setup();
        if (!is_null($setup) && 0 < $setup->toNumeric()) {
            $priceString .= " + " . $setup->toPrefixed() . " " . \Lang::trans("ordersetupfee");
        }
        return $priceString;
    }
    public function toSuffixedString()
    {
        $priceString = "";
        $price = $this->price();
        if (!is_null($price)) {
            $priceString .= $price->toSuffixed();
            if ($this->isRecurring()) {
                $priceString .= "/" . $this->getShortCycle();
            }
        }
        $setup = $this->setup();
        if (!is_null($setup) && 0 < $setup->toNumeric()) {
            $priceString .= " + " . $setup->toSuffixed() . " " . \Lang::trans("ordersetupfee");
        }
        return $priceString;
    }
    public function toFullString()
    {
        $priceString = "";
        if ($this->isFree()) {
            return \Lang::trans("orderfree");
        }
        $price = $this->price();
        if (!is_null($price)) {
            $priceString .= $price->toFull();
            if ($this->isRecurring()) {
                $priceString .= "/" . $this->getShortCycle();
            } else {
                if ($this->isOneTime()) {
                    $priceString .= " " . \Lang::trans("orderpaymenttermonetime");
                }
            }
        }
        $setup = $this->setup();
        if (!is_null($setup) && 0 < $setup->toNumeric()) {
            $priceString .= " + " . $setup->toFull() . " " . \Lang::trans("ordersetupfee");
        }
        return $priceString;
    }
    public function getShortCycle()
    {
        switch ($this->cycle()) {
            case "monthly":
                return \Lang::trans("pricingCycleShort.monthly");
            case "quarterly":
                return \Lang::trans("pricingCycleShort.quarterly");
            case "semiannually":
                return \Lang::trans("pricingCycleShort.semiannually");
            case "annually":
                return \Lang::trans("pricingCycleShort.annually");
            case "biennially":
                return \Lang::trans("pricingCycleShort.biennially");
            case "triennially":
                return \Lang::trans("pricingCycleShort.triennially");
        }
    }
    public function isYearly()
    {
        return in_array($this->cycle(), array("annually", "biennially", "triennially"));
    }
    public function cycleInYears()
    {
        switch ($this->cycle()) {
            case "annually":
                return \Lang::trans("pricingCycleLong.annually");
            case "biennially":
                return \Lang::trans("pricingCycleLong.biennially");
            case "triennially":
                return \Lang::trans("pricingCycleLong.triennially");
        }
    }
    public function yearlyPrice()
    {
        return $this->breakdown()["yearly"]->toFull() . "/" . \Lang::trans("pricingCycleShort.annually");
    }
    public function cycleInMonths()
    {
        switch ($this->cycle()) {
            case "monthly":
                return \Lang::trans("pricingCycleLong.monthly");
            case "quarterly":
                return \Lang::trans("pricingCycleLong.quarterly");
            case "semiannually":
                return \Lang::trans("pricingCycleLong.semiannually");
        }
    }
    public function monthlyPrice()
    {
        return $this->breakdown()["monthly"]->toFull() . "/" . \Lang::trans("pricingCycleShort.monthly");
    }
    public function breakdownPrice()
    {
        if ($this->isYearly()) {
            return $this->yearlyPrice();
        }
        return $this->monthlyPrice();
    }
    public function breakdownPriceNumeric()
    {
        if ($this->isYearly()) {
            return (double) $this->breakdown()["yearly"]->toNumeric();
        }
        return (double) $this->breakdown()["monthly"]->toNumeric();
    }
}

?>