<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class Pricing
{
    private $data = array();
    private $cycles = array("monthly" => 1, "quarterly" => 3, "semiannually" => 6, "annually" => 12, "biennially" => 24, "triennially" => 36);
    protected $currency = NULL;
    public function loadPricing($type, $relid, $currency = NULL)
    {
        if (is_null($currency)) {
            global $currency;
            if (is_array($currency)) {
                $this->currency = $currency;
            }
        } else {
            if (is_array($currency)) {
                $this->currency = $currency;
            }
        }
        if (is_null($this->currency)) {
            $this->currency = getCurrency();
        }
        $result = select_query("tblpricing", "", array("type" => $type, "currency" => (int) $this->currency["id"], "relid" => (int) $relid));
        $data = mysql_fetch_array($result);
        if (is_array($data)) {
            $this->data = $data;
        } else {
            $this->data = array("monthly" => "-1", "quarterly" => "-1", "semiannually" => "-1", "annually" => "-1", "biennially" => "-1", "triennially" => "-1");
        }
    }
    public function getData($key)
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : "";
    }
    public function getRelID()
    {
        return (int) $this->getData("relid");
    }
    public function getSetup($cycle)
    {
        return $this->getData(substr($cycle, 0, 1) . "setupfee");
    }
    public function getPrice($cycle)
    {
        return $this->getData($cycle);
    }
    public function getAvailableBillingCycles()
    {
        $active_cycles = array();
        foreach ($this->cycles as $cycle => $months) {
            if ($this->getData($cycle) != -1) {
                $active_cycles[] = $cycle;
            }
        }
        return $active_cycles;
    }
    public function hasBillingCyclesAvailable()
    {
        return 0 < count($this->getAvailableBillingCycles()) ? true : false;
    }
    public function getFirstAvailableCycle()
    {
        $cycles = $this->getAvailableBillingCycles();
        return 0 < count($cycles) ? $cycles[0] : "";
    }
    public function getAllCycleOptions()
    {
        $cycles = array();
        foreach ($this->cycles as $cycle => $months) {
            $price = $this->getPrice($cycle);
            if ($price && $price != -1) {
                $cycles[] = $this->getCycleData($cycle, $months);
            }
        }
        return $cycles;
    }
    public function getOneTimePricing()
    {
        $data = $this->getCycleData("monthly");
        $data["cycle"] = "onetime";
        return $data;
    }
    protected function getCycleData($cycle, $months = 0)
    {
        $setupfee = $this->getSetup($cycle);
        $price = $this->getPrice($cycle);
        if (!function_exists("getCartConfigOptions")) {
            require ROOTDIR . "/includes/configoptionsfunctions.php";
        }
        $configoptions = getCartConfigOptions($this->getRelID(), array(), $cycle, "", true);
        if (count($configoptions)) {
            foreach ($configoptions as $option) {
                $setupfee += $option["selectedsetup"];
                $price += $option["selectedrecurring"];
            }
        }
        if (0 < $months) {
            $breakdown = array("monthly" => new View\Formatter\Price($price / $months, $this->currency), "yearly" => 12 <= $months ? new View\Formatter\Price($price / ($months / 12), $this->currency) : null);
        } else {
            $breakdown = array();
        }
        return array("cycle" => $cycle, "setupfee" => new View\Formatter\Price($setupfee, $this->currency), "price" => new View\Formatter\Price($price, $this->currency), "breakdown" => $breakdown);
    }
    public function getAllCycleOptionsIndexedByCycle()
    {
        $cycles = $this->getAllCycleOptions();
        $cyclesToReturn = array();
        foreach ($cycles as $key => $data) {
            $cyclesToReturn[$data["cycle"]] = $data;
        }
        return $cyclesToReturn;
    }
}

?>