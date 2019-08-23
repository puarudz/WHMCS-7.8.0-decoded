<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Domains\DomainLookup\Provider;

abstract class AbstractProvider
{
    protected abstract function getGeneralAvailability($sld, array $tlds);
    protected abstract function getDomainSuggestions(\WHMCS\Domains\Domain $domain, $tldsToInclude);
    public abstract function getSettings();
    public function checkAvailability(\WHMCS\Domains\Domain $domain, $tlds)
    {
        $resultsList = $this->getGeneralAvailability($domain->getIdnSecondLevel(), $tlds);
        if (!$resultsList instanceof \WHMCS\Domains\DomainLookup\ResultsList) {
            throw new \InvalidArgumentException("Return must be an instance of \\WHMCS\\Domains\\DomainLookup\\ResultsList");
        }
        return $resultsList;
    }
    protected function getSpotlightTlds()
    {
        return getSpotlightTlds();
    }
    public function getSuggestions(\WHMCS\Domains\Domain $domain)
    {
        $resultsList = $this->getDomainSuggestions($domain, $this->getTldsForSuggestions());
        if (!$resultsList instanceof \WHMCS\Domains\DomainLookup\ResultsList) {
            throw new \InvalidArgumentException("Return must be an instance of \\WHMCS\\Domains\\DomainLookup\\ResultsList");
        }
        $spotlightDomains = array();
        foreach ($this->getSpotlightTlds() as $tld) {
            $spotlightDomains[] = $domain->getSecondLevel() . $tld;
        }
        $shownElsewhere = array_merge(array($domain->getDomain()), $spotlightDomains);
        $list = $resultsList->toArray();
        foreach ($list as $key => $result) {
            if (in_array($result["domainName"], $shownElsewhere)) {
                $resultsList->offsetUnset($key);
            } else {
                if (!$result["isValidDomain"]) {
                    $resultsList->offsetUnset($key);
                }
            }
        }
        $resultsList->uasort(function (\WHMCS\Domains\DomainLookup\SearchResult $firstResult, \WHMCS\Domains\DomainLookup\SearchResult $secondResult) {
            $scoreA = round($firstResult->getScore(), 3);
            $scoreB = round($secondResult->getScore(), 3);
            if ($scoreA === $scoreB) {
                return 0;
            }
            return $scoreB < $scoreA ? -1 : 1;
        });
        return $resultsList;
    }
    public function getTldsForSuggestions()
    {
        $setting = \WHMCS\Domains\DomainLookup\Settings::ofRegistrar("WhmcsWhois")->whereSetting("suggestTlds")->first();
        if (!$setting) {
            return array();
        }
        $settingTlds = explode(",", $setting->value);
        $qualifiedTlds = getTLDList("register");
        $suggestedTlds = array_intersect($settingTlds, $qualifiedTlds);
        return array_values(array_filter(array_map(function ($tld) {
            return ltrim($tld, ".");
        }, $suggestedTlds)));
    }
    public function checkSubDomain(\WHMCS\Domains\Domain $subDomain)
    {
        if (!\WHMCS\Domains\Domain::isValidDomainName($subDomain->getSecondLevel(), ".com")) {
            throw new \WHMCS\Exception\InvalidDomain("ordererrordomaininvalid");
        }
        $bannedSubDomainPrefixes = explode(",", \WHMCS\Config\Setting::getValue("BannedSubdomainPrefixes"));
        if (in_array($subDomain->getSecondLevel(), $bannedSubDomainPrefixes)) {
            throw new \WHMCS\Exception\InvalidDomain("ordererrorsbudomainbanned");
        }
        if (\WHMCS\Config\Setting::getValue("AllowDomainsTwice")) {
            $subChecks = \WHMCS\Database\Capsule::table("tblhosting")->where("domain", "=", $subDomain->getSecondLevel() . $subDomain->getDotTopLevel())->whereNotIn("domainstatus", array("Terminated", "Cancelled", "Fraud"))->count();
            if ($subChecks) {
                throw new \WHMCS\Exception\InvalidDomain("ordererrorsubdomaintaken");
            }
        }
        $validate = new \WHMCS\Validate();
        run_validate_hook($validate, "CartSubdomainValidation", array("subdomain" => $subDomain->getSecondLevel(), "domain" => $subDomain->getDotTopLevel()));
        if ($validate->hasErrors()) {
            $errors = "";
            foreach ($validate->getErrors() as $error) {
                $errors .= $error . "<br />";
            }
            throw new \WHMCS\Exception\InvalidDomain($errors);
        }
    }
    public function checkOwnDomain(\WHMCS\Domains\Domain $ownDomain)
    {
        if (!\WHMCS\Domains\Domain::isValidDomainName($ownDomain->getSecondLevel(), $ownDomain->getDotTopLevel())) {
            throw new \WHMCS\Exception\InvalidDomain("ordererrordomaininvalid");
        }
        if (!\WHMCS\Domains\Domain::isSupportedTld($ownDomain->getDotTopLevel())) {
            throw new \WHMCS\Exception\InvalidDomain("ordererrordomaininvalid");
        }
        if (\WHMCS\Config\Setting::getValue("AllowDomainsTwice")) {
            $subChecks = \WHMCS\Database\Capsule::table("tblhosting")->where("domain", "=", $ownDomain->getSecondLevel() . $ownDomain->getDotTopLevel())->whereNotIn("domainstatus", array("Terminated", "Cancelled", "Fraud"))->count();
            if ($subChecks) {
                throw new \WHMCS\Exception\InvalidDomain("ordererrordomainalreadyexists");
            }
        }
        $validate = new \WHMCS\Validate();
        run_validate_hook($validate, "ShoppingCartValidateDomain", array("domainoption" => "owndomain", "sld" => $ownDomain->getSecondLevel(), "tld" => $ownDomain->getDotTopLevel()));
        if ($validate->hasErrors()) {
            $errors = "";
            foreach ($validate->getErrors() as $error) {
                $errors .= $error . "<br />";
            }
            throw new \WHMCS\Exception\InvalidDomain($errors);
        }
    }
    public function getProviderName()
    {
        return str_replace("WHMCS\\Domains\\DomainLookup\\Provider\\", "", get_class($this));
    }
}

?>