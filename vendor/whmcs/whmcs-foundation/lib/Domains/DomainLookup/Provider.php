<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Domains\DomainLookup;

class Provider
{
    protected static $providerNames = array("BasicWhois" => "\\WHMCS\\Domains\\DomainLookup\\Provider\\BasicWhois", "WhmcsWhois" => "\\WHMCS\\Domains\\DomainLookup\\Provider\\WhmcsWhois", "WhmcsDomains" => "WHMCS\\Domains\\DomainLookup\\Provider\\WhmcsDomains", "Registrar" => "\\WHMCS\\Domains\\DomainLookup\\Provider\\Registrar");
    public static function factory($providerName = "", $registrar = "")
    {
        if (empty($providerName)) {
            $providerName = static::getDomainLookupProvider();
        }
        $providerClassMap = static::getAvailableProviders();
        $className = $providerClassMap[$providerName];
        $provider = new $className();
        if (!$provider instanceof Provider\AbstractProvider) {
            throw new \WHMCS\Exception\Information("Domain lookup provider '" . $providerName . "' must implement " . "WHMCS\\Domains\\DomainLookup\\Provider\\AbstractProvider");
        }
        if ($provider instanceof Provider\Registrar) {
            if (empty($registrar)) {
                $registrar = static::getDomainLookupRegistrar();
            }
            if (!$provider->loadRegistrar($registrar)) {
                $provider = static::factory("WhmcsWhois");
            }
        }
        return $provider;
    }
    public static function getDomainLookupProvider()
    {
        $providerName = \WHMCS\Config\Setting::getValue("domainLookupProvider");
        if (is_null($providerName)) {
            $providerName = "WhmcsDomains";
            \WHMCS\Config\Setting::setValue("domainLookupProvider", $providerName);
        }
        return $providerName;
    }
    public static function getDomainLookupRegistrar()
    {
        return \WHMCS\Config\Setting::getValue("domainLookupRegistrar");
    }
    public static function getAvailableProviders()
    {
        return static::$providerNames;
    }
    public static function getAvailableRegistrarProviders()
    {
        $registrarModules = new \WHMCS\Module\Registrar();
        $registrars = $registrarModules->getList();
        $returnedRegistrars = array();
        foreach ($registrars as $registrar) {
            $registrarModules->load($registrar);
            if ($registrarModules->functionExists("CheckAvailability") || $registrarModules->functionExists("GetDomainSuggestions")) {
                $returnedRegistrars[$registrar] = array("checks" => $registrarModules->functionExists("CheckAvailability"), "suggestions" => $registrarModules->functionExists("GetDomainSuggestions"), "logo" => $registrarModules->getLogoFilename(), "name" => $registrarModules->getDisplayName(), "suggestionSettings" => $registrarModules->functionExists("DomainSuggestionOptions") ? $registrarModules->call("DomainSuggestionOptions") : array());
            }
        }
        return $returnedRegistrars;
    }
}

?>