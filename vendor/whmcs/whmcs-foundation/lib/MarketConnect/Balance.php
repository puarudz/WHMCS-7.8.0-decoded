<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect;

class Balance
{
    protected $balance = NULL;
    protected $updatedAt = NULL;
    protected $cacheTimeout = 1;
    public function loadFromCache()
    {
        $balance = \WHMCS\Config\Setting::getValue("MarketConnectBalance");
        $cacheData = json_decode($balance, true);
        if (!is_null($cacheData) && is_array($cacheData)) {
            $this->balance = $cacheData["balance"];
            $this->updatedAt = \Carbon\Carbon::parse($cacheData["updated"]);
        }
        return $this;
    }
    public function setBalance($balance)
    {
        $this->balance = $balance;
        $this->updatedAt = \Carbon\Carbon::now();
        return $this;
    }
    public function getBalance()
    {
        return is_null($this->balance) ? "0.00" : $this->balance;
    }
    public function isLastUpdatedSet()
    {
        return !is_null($this->updatedAt);
    }
    public function getLastUpdated()
    {
        return $this->updatedAt;
    }
    public function getLastUpdatedDiff()
    {
        if (!$this->isLastUpdatedSet()) {
            return "Never";
        }
        return $this->getLastUpdated()->diffForHumans();
    }
    public function setCacheTimeout($hours)
    {
        $this->cacheTimeout = $hours;
        return $this;
    }
    public function isExpired()
    {
        $lastUpdated = $this->getLastUpdated();
        if (is_null($lastUpdated) || !$lastUpdated instanceof \Carbon\Carbon) {
            return true;
        }
        return $this->cacheTimeout * 60 < $lastUpdated->diffInMinutes(\Carbon\Carbon::now());
    }
    public function updateViaApi()
    {
        $balance = (new Api())->balance();
        $this->setBalance($balance["balance"]);
        return $this;
    }
    public function updateViaApiIfExpired()
    {
        if ($this->isExpired()) {
            $this->updateViaApi()->saveToCache();
        }
        return $this;
    }
    public function saveToCache()
    {
        $data = array("balance" => $this->getBalance(), "updated" => $this->getLastUpdated()->toDateTimeString());
        \WHMCS\Config\Setting::setValue("MarketConnectBalance", json_encode($data));
        return $this;
    }
}

?>