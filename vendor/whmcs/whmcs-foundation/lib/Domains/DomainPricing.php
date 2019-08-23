<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Domains;

class DomainPricing
{
    protected $domain = NULL;
    protected $tldPricing = NULL;
    public function __construct(Domain $domain, $formatCurrency = true)
    {
        if (!function_exists("getTLDPriceList")) {
            require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "domainfunctions.php";
        }
        $this->setDomain($domain);
        $pricing = getTLDPriceList($domain->getDotTopLevel(), $formatCurrency);
        $this->setTldPricing($pricing ?: array());
    }
    public function setDomain($domain)
    {
        $this->domain = $domain;
        return $this;
    }
    public function getDomain()
    {
        return $this->domain;
    }
    public function setTldPricing(array $tldPricing)
    {
        $this->tldPricing = $tldPricing;
        return $this;
    }
    public function getTldPricing()
    {
        return $this->tldPricing;
    }
    public function hasPricing($type = "register", $specificPeriod = 0)
    {
        $pricing = $this->getTldPricing();
        if (count($pricing) < 1) {
            return false;
        }
        foreach ($pricing as $period => $year) {
            if (!empty($year[$type])) {
                $amount = $year[$type];
                $amount = preg_replace("/[^\\d]/", "", $amount);
                if (-1 != (int) $amount) {
                    if ($specificPeriod) {
                        if ($specificPeriod == $period) {
                            return true;
                        }
                    } else {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    public function allPricing()
    {
        return $this->getTldPricing();
    }
    public function forPeriod($period)
    {
        $pricing = $this->getTldPricing();
        if (0 < count($pricing) && isset($pricing[$period]) && 0 < count($pricing[$period])) {
            return $pricing[$period];
        }
        return array();
    }
    public function forRegistrationPeriod($period)
    {
        if ($this->hasPricing("register", $period)) {
            $periodPricing = $this->forPeriod($period);
            return $periodPricing["register"];
        }
        return "";
    }
    public function shortestPeriod()
    {
        $corePricing = $this->allPricing();
        $periods = array_keys($corePricing);
        if (0 < count($periods)) {
            sort($periods);
            $period = $periods[0];
            return array("period" => $period) + $corePricing[$period];
        }
        return array();
    }
    public function longestPeriod()
    {
        $corePricing = $this->allPricing();
        $periods = array_keys($corePricing);
        if (0 < count($periods)) {
            sort($periods);
            $period = $periods[count($periods) - 1];
            return array("period" => $period) + $corePricing[$period];
        }
        return array();
    }
    public function toArray()
    {
        return $this->allPricing();
    }
}

?>