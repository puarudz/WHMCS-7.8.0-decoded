<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Utility;

class Country
{
    protected $countries = array();
    protected $countriesPath = NULL;
    public function __construct($countriesPath = "")
    {
        if (!empty($countriesPath)) {
            $this->countriesPath = $countriesPath;
        }
        $this->load();
    }
    protected function load()
    {
        $path = $this->countriesPath . "dist.countries.json";
        $overridePath = $this->countriesPath . "countries.json";
        $countries = array_merge($this->loadFile($path), $this->loadFile($overridePath));
        foreach ($countries as $code => $data) {
            if (!$data) {
                unset($countries[$code]);
            }
        }
        $this->countries = $countries;
    }
    protected function loadFile($path)
    {
        $countries = array();
        if (file_exists($path)) {
            $countries = file_get_contents($path);
            $countries = json_decode($countries, true);
            if (!is_array($countries)) {
                logActivity("Unable to load Countries File: " . $path);
                $countries = array();
            }
        }
        return $countries;
    }
    public function getCountries()
    {
        return $this->countries;
    }
    public function getCountryNameArray()
    {
        $countries = array();
        foreach ($this->getCountries() as $code => $data) {
            $countries[$code] = $data["name"];
        }
        return $countries;
    }
    public function getCountryNamesOnly()
    {
        $countries = array();
        foreach ($this->getCountries() as $data) {
            $countries[$data["name"]] = $data["name"];
        }
        return $countries;
    }
    public function getCallingCode($countryCode)
    {
        $countries = $this->getCountries();
        if (array_key_exists($countryCode, $countries)) {
            return $countries[$countryCode]["callingCode"];
        }
        return 0;
    }
    public function getName($countryCode)
    {
        $countries = $this->getCountries();
        if (array_key_exists($countryCode, $countries)) {
            return $countries[$countryCode]["name"];
        }
        return $countryCode;
    }
    public function isValidCountryCode($countryCode)
    {
        return isset($this->countries[$countryCode]);
    }
    public function isValidCountryName($countryName)
    {
        return in_array($countryName, $this->getCountryNamesOnly());
    }
}

?>