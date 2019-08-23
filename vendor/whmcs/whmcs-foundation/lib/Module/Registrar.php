<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module;

class Registrar extends AbstractModule
{
    protected $type = self::TYPE_REGISTRAR;
    protected $domainID = 0;
    protected $function = NULL;
    private $builtParams = array();
    private $settings = array();
    public function __construct()
    {
        if (!function_exists("injectDomainObjectIfNecessary")) {
            include_once ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "registrarfunctions.php";
        }
    }
    public function getActiveModules()
    {
        return \WHMCS\Database\Capsule::table("tblregistrars")->distinct("registrar")->orderBy("registrar")->pluck("registrar");
    }
    public function getDisplayName()
    {
        $DisplayName = $this->getMetaDataValue("DisplayName");
        if (!$DisplayName) {
            $configData = $this->call("getConfigArray");
            if (isset($configData["FriendlyName"]["Value"])) {
                $DisplayName = $configData["FriendlyName"]["Value"];
            } else {
                $DisplayName = ucfirst($this->getLoadedModule());
            }
        }
        return \WHMCS\Input\Sanitize::makeSafeForOutput($DisplayName);
    }
    public function clearSettings()
    {
        if (array_key_exists($this->getLoadedModule(), $this->settings)) {
            unset($this->settings[$this->getLoadedModule()]);
        }
        return $this;
    }
    public function getSettings()
    {
        $settings = $this->settings;
        if (!array_key_exists($this->getLoadedModule(), $settings)) {
            $settings[$this->getLoadedModule()] = array();
            $dbSettings = \WHMCS\Database\Capsule::table("tblregistrars")->select("setting", "value")->where("registrar", $this->getLoadedModule())->get();
            foreach ($dbSettings as $dbSetting) {
                $settings[$this->getLoadedModule()][$dbSetting->setting] = decrypt($dbSetting->value);
            }
        }
        $this->settings = $settings;
        return $settings[$this->getLoadedModule()];
    }
    public function setDomainID($domainID)
    {
        $this->domainID = $domainID;
    }
    protected function getDomainID()
    {
        return (int) $this->domainID;
    }
    public function buildParams()
    {
        if (!$this->builtParams) {
            $params = $this->getSettings();
            if ($this->domainID) {
                try {
                    $domain = \WHMCS\Domain\Domain::with(array("order", "extra"))->findOrFail($this->domainID);
                    $domainObj = new \WHMCS\Domains\Domain($domain->domain);
                    $params["domainObj"] = $domainObj;
                    $params["domainid"] = $domain->id;
                    $params["domainname"] = $domain->domain;
                    $params["sld"] = $domainObj->getSecondLevel();
                    $params["tld"] = $domainObj->getTopLevel();
                    $params["regtype"] = $domain->type;
                    $params["regperiod"] = $domain->registrationPeriod;
                    $params["registrar"] = $domain->registrarModuleName;
                    $params["dnsmanagement"] = $domain->hasDnsManagement;
                    $params["emailforwarding"] = $domain->hasEmailForwarding;
                    $params["idprotection"] = $domain->hasIdProtection;
                    $params["premiumEnabled"] = (bool) (int) \WHMCS\Config\Setting::getValue("PremiumDomains");
                    $params["userid"] = $domain->clientId;
                    $this->buildFunctionSpecificParams($domain, $params);
                } catch (\Exception $e) {
                    throw $e;
                }
            }
            $this->builtParams = $params;
        }
        return $this->builtParams;
    }
    public function call($function, array $additionalParams = array())
    {
        $noDomainIdRequirement = array("getConfigArray", "CheckAvailability", "GetDomainSuggestions", "DomainSuggestionOptions", "AdditionalDomainFields");
        if (!in_array($function, $noDomainIdRequirement) && !$this->getDomainID()) {
            return array("error" => "Domain ID is required");
        }
        try {
            $this->function = $function;
            $params = $this->buildParams();
            $hookResults = run_hook("PreRegistrar" . $function, array("params" => $params));
            if (processHookResults($this->getLoadedModule(), $function, $hookResults)) {
                return true;
            }
        } catch (\Exception $e) {
            return array("error" => $e->getMessage());
        }
        if (is_array($additionalParams)) {
            $params = array_merge($params, $additionalParams);
        }
        $originalDetails = $params;
        if (!array_key_exists("original", $params)) {
            $params = foreignChrReplace($params);
            $params["original"] = $originalDetails;
        }
        if (!isset($params["domainObj"]) || !is_object($params["domainObj"])) {
            $params["domainObj"] = new \WHMCS\Domains\Domain(sprintf("%s.%s", $params["sld"], $params["tld"]));
        }
        return parent::call($function, $params);
    }
    public function isActivated()
    {
        return (bool) RegistrarSetting::registrar($this->getLoadedModule())->first();
    }
    public function activate(array $parameters = array())
    {
        $this->deactivate();
        $registrarSetting = new RegistrarSetting();
        $registrarSetting->registrar = $this->getLoadedModule();
        $registrarSetting->setting = "Username";
        $registrarSetting->value = "";
        $registrarSetting->save();
        $moduleSettings = $this->call("getConfigArray");
        $settingsToSave = array("Username" => "");
        foreach ($moduleSettings as $key => $values) {
            if ($values["Type"] == "yesno" && !empty($values["Default"]) && $values["Default"] !== "off" && $values["Default"] !== "disabled") {
                $settingsToSave[$key] = $values["Default"];
            }
        }
        $logChanges = false;
        if (0 < count($parameters)) {
            foreach ($parameters as $key => $value) {
                if (array_key_exists($key, $moduleSettings)) {
                    $settingsToSave[$key] = $value;
                    $logChanges = true;
                }
            }
        }
        logAdminActivity("Registrar Activated: '" . $this->getDisplayName() . "'");
        $this->saveSettings($settingsToSave, $logChanges);
        return $this;
    }
    public function deactivate(array $parameters = array())
    {
        RegistrarSetting::registrar($this->getLoadedModule())->delete();
        $this->clearSettings();
        return $this;
    }
    public function saveSettings(array $newSettings = array(), $logChanges = true)
    {
        $moduleName = $this->getLoadedModule();
        $moduleSettings = $this->call("getConfigArray");
        $previousSettings = $this->getSettings();
        $settingsToSave = array();
        $changes = array();
        foreach ($moduleSettings as $key => $values) {
            if ($values["Type"] == "System") {
                continue;
            }
            if (isset($newSettings[$key])) {
                $settingsToSave[$key] = $newSettings[$key];
            } else {
                if ($values["Type"] == "yesno") {
                    $settingsToSave[$key] = "";
                } else {
                    if (isset($values["Default"])) {
                        $settingsToSave[$key] = $values["Default"];
                    }
                }
            }
            if ($values["Type"] == "password" && isset($newSettings[$key]) && isset($previousSettings[$key])) {
                $updatedPassword = interpretMaskedPasswordChangeForStorage($newSettings[$key], $previousSettings[$key]);
                if ($updatedPassword === false) {
                    $settingsToSave[$key] = $previousSettings[$key];
                } else {
                    $changes[] = "'" . $key . "' value modified";
                }
            }
            if ($values["Type"] == "yesno") {
                if (!empty($settingsToSave[$key]) && $settingsToSave[$key] !== "off" && $settingsToSave[$key] !== "disabled") {
                    $settingsToSave[$key] = "on";
                } else {
                    $settingsToSave[$key] = "";
                }
                if (empty($previousSettings[$key])) {
                    $previousSettings[$key] = "";
                }
                if ($previousSettings[$key] != $settingsToSave[$key]) {
                    $newSetting = $settingsToSave[$key] ?: "off";
                    $oldSetting = $previousSettings[$key] ?: "off";
                    $changes[] = "'" . $key . "' changed from '" . $oldSetting . "' to '" . $newSetting . "'";
                }
            } else {
                if (empty($settingsToSave[$key])) {
                    $settingsToSave[$key] = "";
                }
                if (empty($previousSettings[$key])) {
                    $previousSettings[$key] = "";
                }
                if ($values["Type"] != "password") {
                    if (!$previousSettings[$key] && $settingsToSave[$key]) {
                        $changes[] = "'" . $key . "' set to '" . $settingsToSave[$key] . "'";
                    } else {
                        if ($previousSettings[$key] != $settingsToSave[$key]) {
                            $changes[] = "'" . $key . "' changed from '" . $previousSettings[$key] . "' to '" . $settingsToSave[$key] . "'";
                        }
                    }
                }
            }
        }
        foreach ($settingsToSave as $setting => $value) {
            $model = RegistrarSetting::registrar($moduleName)->setting($setting)->first();
            if ($model) {
                $model->value = $value;
            } else {
                $model = new RegistrarSetting();
                $model->registrar = $moduleName;
                $model->setting = $setting;
                $model->value = \WHMCS\Input\Sanitize::decode(trim($value));
            }
            $model->save();
        }
        if ($changes && $logChanges) {
            logAdminActivity("Domain Registrar Modified: '" . $this->getDisplayName() . "' - " . implode(". ", $changes) . ".");
        }
        unset($this->settings[$this->getLoadedModule()]);
        return $this;
    }
    public function getConfiguration()
    {
        return $this->call("getConfigArray");
    }
    public function updateConfiguration(array $parameters = array())
    {
        if (!$this->isActivated()) {
            throw new \WHMCS\Exception\Module\NotActivated("Module not active");
        }
        $moduleSettings = $this->call("getConfigArray");
        $settingsToSave = array();
        $logChanges = false;
        if (0 < count($parameters)) {
            foreach ($parameters as $key => $value) {
                if (array_key_exists($key, $moduleSettings)) {
                    $settingsToSave[$key] = $value;
                    $logChanges = true;
                }
            }
        }
        if (0 < count($settingsToSave)) {
            $this->saveSettings($settingsToSave, $logChanges);
        }
    }
    protected function buildFunctionSpecificParams(\WHMCS\Domain\Domain $domain, array &$params)
    {
        $premiumEnabled = (bool) (int) \WHMCS\Config\Setting::getValue("PremiumDomains");
        if (in_array($this->function, array("RegisterDomain", "TransferDomain", "SaveContactDetails"))) {
            if (!function_exists("getClientsDetails")) {
                require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "clientfunctions.php";
            }
            $userId = $domain->clientId;
            $contactId = 0;
            if ($domain->order) {
                $contactId = $domain->order->contactId;
            }
            $clientsDetails = getClientsDetails($userId, $contactId);
            $clientsDetails["state"] = $clientsDetails["statecode"];
            $clientsDetails["fullphonenumber"] = $clientsDetails["phonenumberformatted"];
            $clientsDetails["phone-cc"] = $clientsDetails["phonecc"];
            if ($premiumEnabled) {
                $registrarCostPrice = json_decode($domain->extra()->whereName("registrarCostPrice")->value("value"), true);
                if ($registrarCostPrice && is_numeric($registrarCostPrice)) {
                    $params["premiumCost"] = $registrarCostPrice;
                } else {
                    if ($registrarCostPrice && is_array($registrarCostPrice) && array_key_exists("price", $registrarCostPrice)) {
                        $params["premiumCost"] = $registrarCostPrice["price"];
                    }
                }
            }
            if (\WHMCS\Config\Setting::getValue("RegistrarAdminUseClientDetails") == "on") {
                $params["adminfirstname"] = $clientsDetails["firstname"];
                $params["adminlastname"] = $clientsDetails["lastname"];
                $params["admincompanyname"] = $clientsDetails["companyname"];
                $params["adminemail"] = $clientsDetails["email"];
                $params["adminaddress1"] = $clientsDetails["address1"];
                $params["adminaddress2"] = $clientsDetails["address2"];
                $params["admincity"] = $clientsDetails["city"];
                $params["adminfullstate"] = $clientsDetails["fullstate"];
                $params["adminstate"] = $clientsDetails["state"];
                $params["adminpostcode"] = $clientsDetails["postcode"];
                $params["admincountry"] = $clientsDetails["country"];
                $params["adminphonenumber"] = $clientsDetails["phonenumber"];
                $params["adminphonecc"] = $clientsDetails["phonecc"];
                $params["adminfullphonenumber"] = $clientsDetails["phonenumberformatted"];
            } else {
                $params["adminfirstname"] = \WHMCS\Config\Setting::getValue("RegistrarAdminFirstName");
                $params["adminlastname"] = \WHMCS\Config\Setting::getValue("RegistrarAdminLastName");
                $params["admincompanyname"] = \WHMCS\Config\Setting::getValue("RegistrarAdminCompanyName");
                $params["adminemail"] = \WHMCS\Config\Setting::getValue("RegistrarAdminEmailAddress");
                $params["adminaddress1"] = \WHMCS\Config\Setting::getValue("RegistrarAdminAddress1");
                $params["adminaddress2"] = \WHMCS\Config\Setting::getValue("RegistrarAdminAddress2");
                $params["admincity"] = \WHMCS\Config\Setting::getValue("RegistrarAdminCity");
                $params["adminfullstate"] = \WHMCS\Config\Setting::getValue("RegistrarAdminStateProvince");
                $params["adminstate"] = convertStateToCode(\WHMCS\Config\Setting::getValue("RegistrarAdminStateProvince"), \WHMCS\Config\Setting::getValue("RegistrarAdminCountry"));
                if ($params["tld"] == "ca" || substr($params["tld"], -3) == ".ca") {
                    $params["adminstate"] = convertToCiraCode($params["adminstate"]);
                }
                $params["adminpostcode"] = \WHMCS\Config\Setting::getValue("RegistrarAdminPostalCode");
                $params["admincountry"] = \WHMCS\Config\Setting::getValue("RegistrarAdminCountry");
                $phoneDetails = \WHMCS\Client::formatPhoneNumber(array("phonenumber" => \WHMCS\Config\Setting::getValue("RegistrarAdminPhone"), "countrycode" => \WHMCS\Config\Setting::getValue("RegistrarAdminCountry")));
                $params["adminphonenumber"] = $phoneDetails["phonenumber"];
                $params["adminfullphonenumber"] = $phoneDetails["phonenumberformatted"];
                $params["adminphonecc"] = $phoneDetails["phonecc"];
            }
            $nameservers = "";
            if ($domain->order) {
                $nameservers = $domain->order->nameservers;
            }
            $hostingAccount = \WHMCS\Service\Service::where("domain", $domain->domain)->first();
            if ($hostingAccount && $hostingAccount->serverId) {
                $serverData = \WHMCS\Database\Capsule::table("tblservers")->find($hostingAccount->serverId);
                if ($serverData) {
                    for ($i = 1; $i <= 5; $i++) {
                        $nameserver = "nameserver" . $i;
                        $params["ns" . $i] = trim($serverData->{$nameserver});
                    }
                }
            } else {
                if ($nameservers && $nameservers != ",") {
                    $nameservers = explode(",", $nameservers);
                    for ($i = 1; $i <= 5; $i++) {
                        $params["ns" . $i] = trim($nameservers[$i - 1]);
                    }
                } else {
                    for ($i = 1; $i <= 5; $i++) {
                        $params["ns" . $i] = trim(\WHMCS\Config\Setting::getValue("DefaultNameserver" . $i));
                    }
                }
            }
            $params["additionalfields"] = (new \WHMCS\Domains\AdditionalFields())->setDomainType($domain->type)->getFieldValuesFromDatabase($domain->id);
            $params = array_merge($params, $clientsDetails);
            $originalDetails = $params;
            $params = foreignChrReplace($params);
            $params["original"] = $originalDetails;
            if ($this->function == "TransferDomain") {
                $transferSecret = array($domain->domain => "");
                if ($domain->order && $domain->order->transferSecret) {
                    $transferSecret = safe_unserialize($domain->order->transferSecret);
                }
                $params["eppcode"] = $transferSecret[$domain->domain];
                $params["transfersecret"] = $params["eppcode"];
            }
        } else {
            if ($this->function == "RenewDomain" && $premiumEnabled && $domain->isPremium) {
                $params["premiumCost"] = $domain->extra()->whereName("registrarRenewalCostPrice")->value("value");
            }
        }
    }
    public function load($module, $globalVariable = NULL)
    {
        $this->builtParams = array();
        return parent::load($module);
    }
    public function getAdminActivationForms($moduleName)
    {
        return array((new \WHMCS\View\Form())->setUriPrefixAdminBaseUrl("configregistrars.php")->setMethod(\WHMCS\View\Form::METHOD_POST)->setParameters(array("token" => generate_token("plain"), "action" => "activate", "module" => $moduleName))->setSubmitLabel(\AdminLang::trans("global.activate")));
    }
    public function getAdminManagementForms($moduleName)
    {
        return array((new \WHMCS\View\Form())->setUriPrefixAdminBaseUrl("configregistrars.php#" . $moduleName)->setMethod(\WHMCS\View\Form::METHOD_GET)->setSubmitLabel(\AdminLang::trans("global.manage")));
    }
}

?>