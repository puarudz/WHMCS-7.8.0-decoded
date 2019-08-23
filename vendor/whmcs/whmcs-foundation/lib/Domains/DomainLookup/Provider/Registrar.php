<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Domains\DomainLookup\Provider;

class Registrar extends WhmcsWhois
{
    protected $registrarModule = NULL;
    protected function getGeneralAvailability($sld, array $tlds)
    {
        try {
            $domain = new \WHMCS\Domains\Domain($sld);
            $domainSearchResults = $this->getRegistrar()->call("CheckAvailability", array("sld" => $sld, "tlds" => $tlds, "searchTerm" => $domain->getSecondLevel(), "tldsToInclude" => $tlds, "isIdnDomain" => $domain->isIdn(), "punyCodeSearchTerm" => $domain->isIdn() ? $domain->getIdnSecondLevel() : "", "premiumEnabled" => (bool) (int) \WHMCS\Config\Setting::getValue("PremiumDomains")));
            foreach ($domainSearchResults as $key => $domainSearchResult) {
                if ($domainSearchResult->getStatus() == $domainSearchResult::STATUS_TLD_NOT_SUPPORTED) {
                    $unsupportedTld = $domainSearchResult->getDotTopLevel();
                    $tldNotSupportedByEnom = parent::getGeneralAvailability($sld, array($unsupportedTld));
                    $domainSearchResult->setStatus($tldNotSupportedByEnom->offsetGet(0)->getStatus());
                }
            }
            return $domainSearchResults;
        } catch (\Exception $e) {
            return parent::getGeneralAvailability($sld, $tlds);
        }
    }
    protected function getDomainSuggestions(\WHMCS\Domains\Domain $domain, $tldsToInclude)
    {
        try {
            $settings = \WHMCS\Domains\DomainLookup\Settings::ofRegistrar($this->registrarModule->getLoadedModule())->pluck("value", "setting")->toArray();
            return $this->getRegistrar()->call("GetDomainSuggestions", array("searchTerm" => $domain->getSecondLevel(), "tldsToInclude" => $tldsToInclude, "isIdnDomain" => $domain->isIdn(), "punyCodeSearchTerm" => $domain->isIdn() ? $domain->getIdnSecondLevel() : "", "suggestionSettings" => $settings, "premiumEnabled" => (bool) (int) \WHMCS\Config\Setting::getValue("PremiumDomains")));
        } catch (\Exception $e) {
            return parent::getDomainSuggestions($domain, $tldsToInclude);
        }
    }
    public function getTldsForSuggestions()
    {
        if (!function_exists("getTLDList")) {
            include_once ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "domainfunctions.php";
        }
        $tlds = getTLDList("register");
        if (!is_array($tlds) || empty($tlds)) {
            return array();
        }
        $cleanTlds = array_values(array_filter(array_map(function ($tld) {
            return ltrim($tld, ".");
        }, $tlds)));
        return $cleanTlds;
    }
    public function loadRegistrar($moduleName)
    {
        $this->registrarModule = new \WHMCS\Module\Registrar();
        return $this->registrarModule->load($moduleName);
    }
    public function getRegistrar()
    {
        return $this->registrarModule;
    }
    public function getSettings()
    {
        $result = $this->registrarModule->call("DomainSuggestionOptions");
        if (!is_array($result)) {
            return array();
        }
        return $result;
    }
}

?>