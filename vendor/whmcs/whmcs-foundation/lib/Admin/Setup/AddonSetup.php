<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Setup;

class AddonSetup
{
    protected $addonId = NULL;
    protected $addon = NULL;
    protected $moduleInterface = NULL;
    protected $mode = NULL;
    protected function setAddonId($addonId = 0)
    {
        $this->addonId = $addonId;
        if ($addonId) {
            $this->getAddon();
        }
    }
    public function getAddonId()
    {
        return $this->addonId;
    }
    protected function getAddon($addonId = 0)
    {
        if (!$addonId) {
            $addonId = $this->addonId;
        }
        if (is_null($this->addon) && $addonId) {
            $this->addon = \WHMCS\Product\Addon::with("moduleConfiguration")->findOrFail($addonId);
            $this->addonId = $this->addon->id;
            $this->mode = null;
        }
        return $this->addon;
    }
    protected function getModuleSetupRequestMode()
    {
        if (!$this->mode) {
            $hasSimpleMode = $this->hasSimpleConfigMode();
            if (!$hasSimpleMode) {
                $mode = "advanced";
            } else {
                $mode = \App::getFromRequest("mode");
                if (!$mode) {
                    $mode = "simple";
                }
            }
            $this->mode = $mode;
        }
        return $this->mode;
    }
    protected function getModuleInterface()
    {
        if (is_null($this->moduleInterface)) {
            $module = \App::getFromRequest("module");
            if (!$module && $this->addon) {
                $module = $this->addon->module;
            }
            if (!$module) {
                return null;
            }
            $this->moduleInterface = new \WHMCS\Module\Server();
            if (!$this->moduleInterface->load($module)) {
                throw new \Exception("Invalid module");
            }
        }
        return $this->moduleInterface;
    }
    protected function hasSimpleConfigMode()
    {
        $moduleInterface = $this->getModuleInterface();
        if ($moduleInterface && $moduleInterface->functionExists("ConfigOptions")) {
            $configArray = $moduleInterface->call("ConfigOptions", array("producttype" => $this->addon->type));
            foreach ($configArray as $values) {
                if (array_key_exists("SimpleMode", $values) && $values["SimpleMode"]) {
                    return true;
                }
            }
        }
        return false;
    }
    protected function getModuleSettingsFields()
    {
        $mode = $this->getModuleSetupRequestMode();
        $moduleInterface = $this->getModuleInterface();
        if (!$moduleInterface || $moduleInterface->isMetaDataValueSet("NoEditModuleSettings") && $moduleInterface->getMetaDataValue("NoEditModuleSettings")) {
            return array();
        }
        $isSimpleModeRequest = false;
        $noServerFound = false;
        $params = array();
        if ($mode == "simple") {
            $isSimpleModeRequest = true;
            $serverId = (int) \App::getFromRequest("server");
            if (!$serverId) {
                $addonId = \App::getFromRequest("id");
                $serverGroup = \App::getFromRequest("servergroup");
                if (!$serverGroup && !\App::isInRequest("servergroup") && $addonId) {
                    $serverGroup = $this->getAddon($addonId)->serverGroupId;
                } else {
                    $serverGroup = 0;
                }
                $serverId = getServerID($moduleInterface->getLoadedModule(), $serverGroup);
                if (!$serverId && $moduleInterface->getMetaDataValue("RequiresServer") !== false) {
                    $noServerFound = true;
                } else {
                    $params = $moduleInterface->getServerParams($serverId);
                }
            }
        }
        $configArray = $moduleInterface->call("ConfigOptions", array("producttype" => $this->addon ? $this->addon->type : "hostingaccount", "isAddon" => true));
        $i = 0;
        $isConfigured = false;
        foreach ($configArray as $key => &$values) {
            $i++;
            if (!array_key_exists("FriendlyName", $values)) {
                $values["FriendlyName"] = $key;
            }
            $values["Name"] = "packageconfigoption[" . $i . "]";
            $variable = "configoption" . $i;
            if (\App::isInRequest($values["Name"])) {
                $values["Value"] = \App::getFromRequest($values["Name"]);
            } else {
                if (!$this->addon) {
                    continue;
                }
                $moduleConfiguration = $this->addon->moduleConfiguration->where("setting_name", $variable)->first();
                $values["Value"] = $moduleConfiguration ? $moduleConfiguration->value : "";
            }
            if ($values["Value"] !== "") {
                $isConfigured = true;
            }
        }
        unset($values);
        $i = 0;
        $fields = array();
        foreach ($configArray as $key => $values) {
            $i++;
            if (!$isConfigured) {
                $values["Value"] = null;
            }
            if ($mode == "advanced" || $mode == "simple" && array_key_exists("SimpleMode", $values) && $values["SimpleMode"]) {
                $dynamicFetchError = null;
                $supportsFetchingValues = false;
                if (in_array($values["Type"], array("text", "dropdown", "radio")) && $isSimpleModeRequest && !empty($values["Loader"])) {
                    if ($noServerFound) {
                        $dynamicFetchError = "No server found so unable to fetch values";
                    } else {
                        $supportsFetchingValues = true;
                        try {
                            $loader = $values["Loader"];
                            $values["Options"] = $loader($params);
                            if ($values["Type"] == "text") {
                                $values["Type"] = "dropdown";
                                if ($values["Value"] && !array_key_exists($values["Value"], $values["Options"])) {
                                    $values["Options"][$values["Value"]] = ucwords($values["Value"]);
                                }
                            }
                        } catch (\WHMCS\Exception\Module\InvalidConfiguration $e) {
                            $dynamicFetchError = \AdminLang::trans("products.serverConfigurationInvalid");
                        } catch (\Exception $e) {
                            $dynamicFetchError = $e->getMessage();
                        }
                    }
                }
                $html = moduleConfigFieldOutput($values);
                if (!is_null($dynamicFetchError)) {
                    $html .= "<i id=\"errorField" . $i . "\" class=\"fas fa-exclamation-triangle icon-warning\" data-toggle=\"tooltip\" data-placement=\"bottom\" title=\"" . $dynamicFetchError . "\"></i>";
                }
                if ($supportsFetchingValues) {
                    $html .= "<i id=\"refreshField" . $i . "\" class=\"fas fa-sync icon-refresh\" data-product-id=\"" . \App::getFromRequest("id") . "\" data-toggle=\"tooltip\" data-placement=\"right\" title=\"" . \AdminLang::trans("products.refreshDynamicInfo") . "\"></i>";
                }
                $fields[$values["FriendlyName"]] = $html;
            }
        }
        return $fields;
    }
    public function getModuleSettings($addonId)
    {
        $this->setAddonId($addonId);
        if (!function_exists("getServerID")) {
            require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "modulefunctions.php";
        }
        $fields = $this->getModuleSettingsFields();
        $i = 1;
        $html = "<tr>";
        foreach ($fields as $friendlyName => $fieldOutput) {
            $i++;
            $html .= "<td class=\"fieldlabel\" width=\"20%\">" . $friendlyName . "</td>" . "<td class=\"fieldarea\">" . $fieldOutput . "</td>";
            if ($i % 2 !== 0) {
                $html .= "</tr><tr>";
            }
        }
        $html .= "</tr>";
        return array("content" => $html, "mode" => $this->mode);
    }
}

?>