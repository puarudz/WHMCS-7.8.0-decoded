<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Domains\DomainLookup;

class SearchResult extends \WHMCS\Domains\Domain
{
    protected $score = 1;
    protected $premiumCostPricing = array();
    protected $status = NULL;
    const STATUS_REGISTERED = "registered";
    const STATUS_NOT_REGISTERED = "available for registration";
    const STATUS_RESERVED = "reserved";
    const STATUS_UNKNOWN = "unknown";
    const STATUS_TLD_NOT_SUPPORTED = "tld not supported";
    public function __construct($sld, $tld)
    {
        $this->setDomainBySecondAndTopLevels($sld, $tld);
        $this->setStatus(static::STATUS_UNKNOWN);
    }
    public static function factoryFromDomain(\WHMCS\Domains\Domain $domain)
    {
        $searchResult = new self($domain->getSecondLevel(), $domain->getTopLevel());
        $searchResult->setIdnSecondLevel($domain->getIdnSecondLevel());
        $searchResult->setGeneralAvailability($domain->isGeneralAvailability());
        $searchResult->setPremiumDomain($domain->isPremiumDomain());
        return $searchResult;
    }
    public function setScore($score)
    {
        $this->score = floatval($score);
        return $this;
    }
    public function getScore()
    {
        return $this->score;
    }
    public function setPremiumCostPricing($pricing = array())
    {
        $this->premiumCostPricing = $pricing;
    }
    public function getPremiumCostPricing()
    {
        return $this->premiumCostPricing;
    }
    public function isAvailableForPurchase()
    {
        if ($this->getStatus() == static::STATUS_REGISTERED) {
            return false;
        }
        return true;
    }
    public function isMatchingLengthRequirements()
    {
        list($DomainMinLengthRestrictions, $DomainMaxLengthRestrictions) = static::getTldDomainLengthRestrictions();
        $sld = $this->getSecondLevel();
        $dottedTld = $this->getTopLevel();
        if ($dottedTld[0] != ".") {
            $dottedTld = "." . $dottedTld;
        }
        if (array_key_exists($dottedTld, $DomainMinLengthRestrictions) && strlen($sld) < $DomainMinLengthRestrictions[$dottedTld] || array_key_exists($dottedTld, $DomainMaxLengthRestrictions) && $DomainMaxLengthRestrictions[$dottedTld] < strlen($sld)) {
            return false;
        }
        return true;
    }
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }
    public function getStatus()
    {
        return $this->status;
    }
    public function getLegacyStatus()
    {
        switch ($this->getStatus()) {
            case static::STATUS_UNKNOWN:
                return "error";
            case static::STATUS_REGISTERED:
            case static::STATUS_RESERVED:
                return "unavailable";
        }
        return "available";
    }
    public function toArray()
    {
        try {
            $pricing = $this->pricing();
            $shortestPeriod = $pricing->shortestPeriod();
            $pricing = $pricing->toArray();
        } catch (\WHMCS\Exception\Domains\Pricing\ContactUs $e) {
            $pricing = "ContactUs";
            $shortestPeriod = 0;
        } catch (\WHMCS\Exception\Domains\Pricing\NoSale $e) {
            $this->setStatus(static::STATUS_RESERVED);
            $pricing = "";
            $shortestPeriod = 0;
        }
        return array("domainName" => $this->getRawDomain(), "idnDomainName" => strtolower($this->getDomain()), "tld" => $this->getTopLevel(), "tldNoDots" => str_replace(".", "", $this->getTopLevel()), "sld" => $this->getSecondLevel(), "idnSld" => strtolower($this->getIdnSecondLevel()), "status" => $this->getStatus(), "legacyStatus" => $this->getLegacyStatus(), "score" => $this->getScore(), "isRegistered" => $this->getStatus() == static::STATUS_REGISTERED, "isAvailable" => $this->getStatus() == static::STATUS_NOT_REGISTERED, "isValidDomain" => self::isValidDomainName($this->getSecondLevel(), $this->getTopLevel()), "pricing" => $pricing, "shortestPeriod" => $shortestPeriod, "group" => $this->group(), "minLength" => $this->getDomainMinimumLength(), "maxLength" => $this->getDomainMaximumLength(), "isPremium" => $this->isPremiumDomain(), "premiumCostPricing" => $this->getPremiumCostPricing());
    }
    public function pricing()
    {
        if (\WHMCS\Config\Setting::getValue("PremiumDomains") && $this->isPremiumDomain()) {
            return $this->calculatePremiumPricing();
        }
        return parent::pricing();
    }
    protected function calculatePremiumPricing()
    {
        try {
            $pricing = parent::pricing();
            $registrarCurrencyId = \WHMCS\Database\Capsule::table("tblcurrencies")->where("code", "=", $this->getPremiumCostPricing()["CurrencyCode"])->value("id");
            if (!$registrarCurrencyId) {
                throw new \WHMCS\Exception("Domain registrar currency not available for conversion");
            }
            $clientCurrency = getCurrency(\WHMCS\Session::get("uid"), \WHMCS\Session::get("currency"));
            $premiumPricing = array();
            foreach ($this->getPremiumCostPricing() as $registerType => &$premiumPrice) {
                if ($registerType == "CurrencyCode") {
                    $premiumPricing["currency"] = $registrarCurrencyId;
                    continue;
                }
                $premiumPricing[$registerType] = convertCurrency($premiumPrice, $registrarCurrencyId, $clientCurrency["id"]);
            }
            $registerTransferKey = "register";
            if (array_key_exists("transfer", $premiumPricing)) {
                $registerTransferKey = "transfer";
            }
            $hookReturns = run_hook("PremiumPriceOverride", array("domainName" => $this->getRawDomain(), "tld" => $this->getTopLevel(), "sld" => $this->getSecondLevel(), $registerTransferKey => $premiumPricing[$registerTransferKey], "renew" => $premiumPricing["renew"]));
            $skipMarkup = false;
            foreach ($hookReturns as $hookReturn) {
                if (array_key_exists("noSale", $hookReturn) && $hookReturn["noSale"] === true) {
                    throw new \WHMCS\Exception\Domains\Pricing\NoSale();
                }
                if (array_key_exists("contactUs", $hookReturn) && $hookReturn["contactUs"] === true) {
                    throw new \WHMCS\Exception\Domains\Pricing\ContactUs();
                }
                if (array_key_exists("register", $hookReturn) && array_key_exists("register", $premiumPricing)) {
                    $premiumPricing["register"] = $hookReturn["register"];
                }
                if (array_key_exists("transfer", $hookReturn) && array_key_exists("transfer", $premiumPricing)) {
                    $premiumPricing["transfer"] = $hookReturn["transfer"];
                }
                if (array_key_exists("renew", $hookReturn) && array_key_exists("renew", $premiumPricing)) {
                    $premiumPricing["renew"] = $hookReturn["renew"];
                }
                if (array_key_exists("skipMarkup", $hookReturn) && $hookReturn["skipMarkup"] === true) {
                    $skipMarkup = true;
                }
            }
            $pricingArray = array();
            foreach ($premiumPricing as $registerType => &$price) {
                if ($registerType == "currency") {
                    $pricingArray[1][$registerType] = $price;
                    continue;
                }
                if (!$skipMarkup) {
                    $price *= 1 + \WHMCS\Domains\Pricing\Premium::markupForCost($price) / 100;
                }
                $pricingArray[1][$registerType] = formatCurrency($price);
            }
            return $pricing->setTldPricing($pricingArray);
        } catch (\WHMCS\Exception\Domains\Pricing\NoSale $e) {
            throw $e;
        } catch (\WHMCS\Exception\Domains\Pricing\ContactUs $e) {
            throw $e;
        } catch (\Exception $e) {
            return parent::pricing();
        }
    }
}

?>