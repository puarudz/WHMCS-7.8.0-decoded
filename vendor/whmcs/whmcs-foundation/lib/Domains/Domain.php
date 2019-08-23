<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Domains;

class Domain
{
    protected $secondLevel = NULL;
    protected $topLevel = NULL;
    protected $idnSecondLevel = NULL;
    protected $generalAvailability = true;
    protected $premiumDomain = false;
    protected static $whois = NULL;
    public function __construct($domain, $tld = NULL)
    {
        if ($tld) {
            $this->setDomainBySecondAndTopLevels($domain, $tld);
        } else {
            $this->setDomain($domain);
        }
    }
    public static function createFromSldAndTld($sld, $tld)
    {
        return new static($sld, $tld);
    }
    protected function setDomain($domain)
    {
        $parts = explode(".", $domain, 2);
        return $this->setDomainBySecondAndTopLevels($parts[0], isset($parts[1]) ? $parts[1] : "");
    }
    protected function setDomainBySecondAndTopLevels($sld, $tld)
    {
        $idnConverter = new Idna();
        $this->setSecondLevel($idnConverter->decode($sld));
        $this->setTopLevel($tld);
        $this->setIdnSecondLevel($idnConverter->encode($this->getSecondLevel()));
        return $this;
    }
    public function setSecondLevel($secondLevel)
    {
        if (strpos($secondLevel, ".") === 0) {
            $secondLevel = substr($secondLevel, 1);
        }
        $this->secondLevel = $secondLevel;
        return $this;
    }
    public function getSecondLevel()
    {
        return $this->secondLevel;
    }
    public function setTopLevel($topLevel)
    {
        $topLevel = ltrim($topLevel, ".");
        $this->topLevel = $topLevel;
        return $this;
    }
    public function getTopLevel()
    {
        return $this->topLevel;
    }
    public function getDotTopLevel()
    {
        return "." . $this->topLevel;
    }
    public function getSLD()
    {
        return $this->secondLevel;
    }
    public function getTLD()
    {
        return $this->topLevel;
    }
    public function getDomain($idnEncoded = true)
    {
        if ($idnEncoded && $this->isIdn()) {
            $sld = $this->getIdnSecondLevel();
        } else {
            $sld = $this->getSecondLevel();
        }
        $tld = $this->getTopLevel();
        if ($sld && $tld) {
            return $sld . "." . $tld;
        }
        return "";
    }
    public function getRawDomain()
    {
        $sld = $this->getSecondLevel();
        $tld = $this->getTopLevel();
        if ($sld && $tld) {
            return $sld . "." . $tld;
        }
        return "";
    }
    public function getLastTLDSegment()
    {
        $tld = $this->getTopLevel();
        $tldparts = explode(".", $tld);
        return $tldparts[count($tldparts) - 1];
    }
    public function getIdnSecondLevel()
    {
        return $this->idnSecondLevel;
    }
    public function isGeneralAvailability()
    {
        return (bool) $this->generalAvailability;
    }
    public function isPremiumDomain()
    {
        return (bool) $this->premiumDomain;
    }
    public function isIdn()
    {
        if ($this->getSecondLevel() == $this->getIdnSecondLevel()) {
            return false;
        }
        return true;
    }
    public function setGeneralAvailability($generalAvailability)
    {
        $this->generalAvailability = (bool) $generalAvailability;
        return $this;
    }
    public function setIdnSecondLevel($idn)
    {
        $this->idnSecondLevel = $idn;
        return $this;
    }
    public function setPremiumDomain($premiumDomain)
    {
        $this->premiumDomain = (bool) $premiumDomain;
        return $this;
    }
    public static function isValidDomainName($sld, $tld)
    {
        if (trim($sld, "-") != $sld) {
            return false;
        }
        $isIdn = false;
        $allowIdnDomains = \WHMCS\Config\Setting::getValue("AllowIDNDomains");
        if ($allowIdnDomains) {
            $idnconv = new Idna();
            $idnconv->encode($sld);
            if ($errorMsg = $idnconv->get_last_error()) {
                $noEncodableCharMsg = "The given string does not contain encodable chars";
                if ($errorMsg != $noEncodableCharMsg) {
                    return false;
                }
                if ($errorMsg == $noEncodableCharMsg) {
                    $isIdn = false;
                } else {
                    $isIdn = true;
                }
            } else {
                $isIdn = true;
            }
        }
        if (!$isIdn && !static::containsValidNonIdnCharacters($sld, $tld)) {
            return false;
        }
        run_hook("DomainValidation", array("sld" => $sld, "tld" => $tld));
        if ($sld === false && $sld !== 0 || !$tld) {
            return false;
        }
        list($DomainMinLengthRestrictions, $DomainMaxLengthRestrictions) = static::getTldDomainLengthRestrictions();
        $dottedTld = $tld;
        if ($tld[0] != ".") {
            $dottedTld = "." . $tld;
        }
        if (array_key_exists($dottedTld, $DomainMinLengthRestrictions) && strlen($sld) < $DomainMinLengthRestrictions[$dottedTld] || array_key_exists($dottedTld, $DomainMaxLengthRestrictions) && $DomainMaxLengthRestrictions[$dottedTld] < strlen($sld)) {
            return false;
        }
        return true;
    }
    protected static function containsValidNonIdnCharacters($sld, $tld)
    {
        $validmaskSld = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-";
        $validmaskTld = "abcdefghijklmnopqrstuvwxyz0123456789-.";
        if (strspn($sld, $validmaskSld) != strlen($sld) || strspn($tld, $validmaskTld) != strlen($tld)) {
            return false;
        }
        return true;
    }
    public static function isSupportedTld($tld)
    {
        $tld = ltrim($tld, ".");
        $dotTld = "." . $tld;
        $existsInDb = \Illuminate\Database\Capsule\Manager::table("tbltlds")->where("tld", $tld)->union(\Illuminate\Database\Capsule\Manager::table("tbldomainpricing")->where("extension", $dotTld))->exists();
        if ($existsInDb) {
            return true;
        }
        if (!static::$whois) {
            static::$whois = new \WHMCS\WHOIS();
        }
        if (static::$whois->canLookup($dotTld)) {
            return true;
        }
        return false;
    }
    protected static function getTldDomainLengthRestrictions()
    {
        $appConfig = \Config::self();
        $DomainMinLengthRestrictions = $appConfig["DomainMinLengthRestrictions"];
        $DomainMaxLengthRestrictions = $appConfig["DomainMaxLengthRestrictions"];
        if (!is_array($DomainMaxLengthRestrictions)) {
            $DomainMaxLengthRestrictions = array();
        }
        if (!is_array($DomainMinLengthRestrictions)) {
            $DomainMinLengthRestrictions = array();
        }
        foreach (static::getCoreTldList() as $ctld) {
            if (!array_key_exists($ctld, $DomainMinLengthRestrictions)) {
                $DomainMinLengthRestrictions[$ctld] = 3;
            }
            if (!array_key_exists($ctld, $DomainMaxLengthRestrictions)) {
                $DomainMaxLengthRestrictions[$ctld] = 63;
            }
        }
        return array(static::normalizeDomainLengthRestrictionArray($DomainMinLengthRestrictions), static::normalizeDomainLengthRestrictionArray($DomainMaxLengthRestrictions));
    }
    protected static function normalizeDomainLengthRestrictionArray($restrictionArray)
    {
        foreach ($restrictionArray as $tld => $restriction) {
            if ($tld[0] != ".") {
                unset($restrictionArray[$tld]);
                $restrictionArray["." . $tld] = $restriction;
            }
        }
        return $restrictionArray;
    }
    protected static function getCoreTldList()
    {
        return array(".com", ".net", ".org", ".info", "biz", ".mobi", ".name", ".asia", ".tel", ".in", ".mn", ".bz", ".cc", ".tv", ".us", ".me", ".co.uk", ".me.uk", ".org.uk", ".net.uk", ".ch", ".li", ".de", ".jp");
    }
    public function alreadyBilledAsAHostingProduct()
    {
        return (bool) \WHMCS\Service\Service::where("domain", $this->getDomain())->whereNotIn("domainstatus", array("Terminated", "Cancelled", "Fraud"))->count();
    }
    public function alreadyBilledAsADomainItem()
    {
        return (bool) \WHMCS\Domain\Domain::where("domain", $this->getDomain())->whereNotIn("status", array("Expired", "Cancelled", "Fraud", "Transferred Away"))->count();
    }
    public function pricing()
    {
        return new DomainPricing($this);
    }
    public function group()
    {
        static $groups = NULL;
        if (is_null($groups)) {
            $groups = \Illuminate\Database\Capsule\Manager::table("tbldomainpricing")->pluck(\Illuminate\Database\Capsule\Manager::raw("LOWER(`group`)"), "extension");
        }
        return isset($groups[$this->getDotTopLevel()]) && $groups[$this->getDotTopLevel()] != "none" ? $groups[$this->getDotTopLevel()] : "";
    }
    public function getDomainMinimumLength()
    {
        $lengthRestrictions = self::getTldDomainLengthRestrictions();
        if (array_key_exists($this->getDotTopLevel(), $lengthRestrictions[0])) {
            return $lengthRestrictions[0][$this->getDotTopLevel()];
        }
        return 0;
    }
    public function getDomainMaximumLength()
    {
        $lengthRestrictions = self::getTldDomainLengthRestrictions();
        if (array_key_exists($this->getDotTopLevel(), $lengthRestrictions[1])) {
            return $lengthRestrictions[1][$this->getDotTopLevel()];
        }
        return 0;
    }
    protected function getPremiumPricing($registrar = NULL, array $type = array())
    {
        $sessionData = \WHMCS\Session::get("Premium");
        if (array_key_exists($this->getDomain(), $sessionData)) {
            unset($sessionData[$this->getDomain()]);
        }
        \WHMCS\Session::set("Premium", $sessionData);
        if (!(bool) (int) \WHMCS\Config\Setting::getValue("PremiumDomains")) {
            throw new \WHMCS\Exception("PremiumDomains not Enabled");
        }
        if (!$this->isPremiumDomain()) {
            throw new \WHMCS\Exception("Not Premium");
        }
        if (!$registrar) {
            $registrar = DomainLookup\Provider::getDomainLookupRegistrar();
        }
        $registrarModule = new \WHMCS\Module\Registrar();
        if (!$registrarModule->load($registrar)) {
            throw new \WHMCS\Exception("No Registrar Configured");
        }
        $pricing = $registrarModule->call("GetPremiumPrice", array("domain" => $this, "sld" => $this->getSecondLevel(), "tld" => $this->getDotTopLevel(), "type" => $type));
        $pricingCurrency = $pricing["CurrencyCode"];
        unset($pricing["CurrencyCode"]);
        foreach ($pricing as $registerType => &$price) {
            $price = convertCurrency($price, \Illuminate\Database\Capsule\Manager::table("tblcurrencies")->where("code", "=", $pricingCurrency)->value("id"), getCurrency(\WHMCS\Session::get("uid"), \WHMCS\Session::get("currency"))["id"]);
        }
        $registerTransferKey = "register";
        if (array_key_exists("transfer", $pricing)) {
            $registerTransferKey = "transfer";
        }
        $hookReturns = run_hook("PremiumPriceOverride", array("domainName" => $this->getRawDomain(), "tld" => $this->getTopLevel(), "sld" => $this->getSecondLevel(), $registerTransferKey => $pricing[$registerTransferKey], "renew" => $pricing["renew"]));
        $skipMarkup = false;
        foreach ($hookReturns as $hookReturn) {
            if (array_key_exists("noSale", $hookReturn) && $hookReturn["noSale"] === true) {
                throw new \WHMCS\Exception\Domains\Pricing\NoSale();
            }
            if (array_key_exists("contactUs", $hookReturn) && $hookReturn["contactUs"] === true) {
                throw new \WHMCS\Exception\Domains\Pricing\ContactUs();
            }
            if (array_key_exists("register", $hookReturn) && array_key_exists("register", $pricing)) {
                $premiumPricing["register"] = $hookReturn["register"];
            }
            if (array_key_exists("transfer", $hookReturn) && array_key_exists("transfer", $pricing)) {
                $premiumPricing["transfer"] = $hookReturn["transfer"];
            }
            if (array_key_exists("renew", $hookReturn) && array_key_exists("renew", $pricing)) {
                $premiumPricing["renew"] = $hookReturn["renew"];
            }
            if (array_key_exists("skipMarkup", $hookReturn) && $hookReturn["skipMarkup"] === true) {
                $skipMarkup = true;
            }
        }
        foreach ($pricing as $type => &$price) {
            if (!$skipMarkup) {
                $price *= 1 + Pricing\Premium::markupForCost($price) / 100;
            }
        }
        return $pricing;
    }
    public function getPremiumRegistrationPrice($registrar = NULL)
    {
        return $this->getPremiumPricing($registrar, array("register"));
    }
    public function getPremiumRenewalPrice($registrar = NULL)
    {
        return $this->getPremiumPricing($registrar, array("renew"));
    }
    protected static function eligibleCountriesForEuTld()
    {
        return array("AT", "BE", "BG", "CZ", "CY", "DE", "DK", "ES", "EE", "FI", "FR", "GR", "GB", "HU", "IE", "IT", "LT", "LU", "LV", "MT", "NL", "PL", "PT", "RO", "SE", "SK", "SI", "AX", "GF", "GI", "GP", "MQ", "RE");
    }
    public static function isValidForEuRegistration($countryCode)
    {
        $eu = self::eligibleCountriesForEuTld();
        return in_array($countryCode, $eu);
    }
}

?>