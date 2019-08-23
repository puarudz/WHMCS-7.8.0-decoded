<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Product;

class ConfigOptions
{
    protected $cache = array();
    protected function getCurrencyID()
    {
        $whmcs = \WHMCS\Application::getInstance();
        return $whmcs->getCurrencyID();
    }
    protected function isCached($productID)
    {
        return isset($this->cache[$productID]) && is_array($this->cache[$productID]);
    }
    protected function getFromCache($productID, $optionLabel)
    {
        if ($this->isCached($productID)) {
            return $this->cache[$productID][$optionLabel];
        }
        return array();
    }
    protected function storeToCache($productID, $optionLabel, $optionsData)
    {
        $this->cache[$productID][$optionLabel] = $optionsData;
        return true;
    }
    protected function loadData($productID)
    {
        $ops = array();
        if (!$this->isCached($productID)) {
            $currencyId = $this->getCurrencyID();
            $info = array();
            $query = "SELECT tblproductconfigoptions.id,tblproductconfigoptions.optionname,tblproductconfigoptions.optiontype,tblproductconfigoptions.qtyminimum,tblproductconfigoptions.qtymaximum,(SELECT CONCAT(msetupfee,'|',qsetupfee,'|',ssetupfee,'|',asetupfee,'|',bsetupfee,'|',tsetupfee,'|',monthly,'|',quarterly,'|',semiannually,'|',annually,'|',biennially,'|',triennially) FROM tblpricing WHERE type='configoptions' AND currency=" . (int) $currencyId . " AND relid=(SELECT id FROM tblproductconfigoptionssub WHERE configid=tblproductconfigoptions.id AND hidden=0 ORDER BY sortorder ASC,id ASC LIMIT 1) ) FROM tblproductconfigoptions INNER JOIN tblproductconfiglinks ON tblproductconfigoptions.gid=tblproductconfiglinks.gid WHERE tblproductconfiglinks.pid=" . (int) $productID . " AND tblproductconfigoptions.hidden=0";
            $result = full_query($query);
            while ($data = mysql_fetch_array($result)) {
                $info[$data[0]] = array("name" => $data["optionname"], "type" => $data["optiontype"], "qtyminimum" => $data["qtyminimum"], "qtymaximum" => $data["qtymaximum"]);
                $ops[$data[0]] = explode("|", $data[5]);
            }
            $this->storeToCache($productID, "info", $info);
            $this->storeToCache($productID, "pricing" . $currencyId, $ops);
        }
        return $ops;
    }
    public function getBasePrice($productID, $billingCycle)
    {
        $cycles = new \WHMCS\Billing\Cycles();
        if ($cycles->isValidSystemBillingCycle($billingCycle)) {
            $this->loadData($productID);
            $optionsInfo = $this->getFromCache($productID, "info");
            $optionsPricing = $this->getFromCache($productID, "pricing" . $this->getCurrencyID());
            $pricingObj = new \WHMCS\Billing\Pricing();
            $cycleindex = array_search($billingCycle, $pricingObj->getDBFields());
            $price = 0;
            foreach ($optionsPricing as $configID => $pricing) {
                if ($optionsInfo[$configID]["type"] == 1 || $optionsInfo[$configID]["type"] == 2) {
                    $price += $pricing[$cycleindex];
                } else {
                    if ($optionsInfo[$configID]["type"] == 3) {
                    } else {
                        if ($optionsInfo[$configID]["type"] == 4) {
                            $minquantity = $optionsInfo[$configID]["qtyminimum"];
                            if (0 < $minquantity) {
                                $price += $minquantity * $pricing[$cycleindex];
                            }
                        }
                    }
                }
            }
            return $price;
        } else {
            return false;
        }
    }
    public function hasConfigOptions($productID)
    {
        $this->loadData($productID);
        $optionsInfo = $this->getFromCache($productID, "info");
        if (0 < count($optionsInfo)) {
            return true;
        }
        return false;
    }
}

?>