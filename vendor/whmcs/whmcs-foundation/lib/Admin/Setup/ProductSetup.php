<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Setup;

class ProductSetup
{
    protected $product = NULL;
    protected $moduleInterface = NULL;
    protected $mode = NULL;
    protected function getProduct($productId)
    {
        if (is_null($this->product)) {
            $this->product = \WHMCS\Product\Product::findOrFail($productId);
            $this->mode = null;
        }
        return $this->product;
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
            $module = \App::isInRequest("module") ? \App::getFromRequest("module") : $this->product->module;
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
        if ($moduleInterface->functionExists("ConfigOptions")) {
            $configArray = $moduleInterface->call("ConfigOptions", array("producttype" => $this->product->type));
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
        if ($moduleInterface->isMetaDataValueSet("NoEditModuleSettings") && $moduleInterface->getMetaDataValue("NoEditModuleSettings")) {
            return array();
        }
        $isSimpleModeRequest = false;
        $noServerFound = false;
        $params = array();
        if ($mode == "simple") {
            $isSimpleModeRequest = true;
            $serverId = (int) \App::getFromRequest("server");
            if (!$serverId) {
                $serverId = getServerID($moduleInterface->getLoadedModule(), \App::isInRequest("servergroup") ? \App::getFromRequest("servergroup") : $this->getProduct(\App::getFromRequest("id"))->serverGroupId);
                if (!$serverId && $moduleInterface->getMetaDataValue("RequiresServer") !== false) {
                    $noServerFound = true;
                } else {
                    $params = $moduleInterface->getServerParams($serverId);
                }
            }
        }
        $moduleInterface = $this->getModuleInterface();
        $configArray = $moduleInterface->call("ConfigOptions", array("producttype" => $this->product->type, "isAddon" => false));
        $i = 0;
        $isConfigured = false;
        foreach ($configArray as $key => &$values) {
            $i++;
            if (!array_key_exists("FriendlyName", $values)) {
                $values["FriendlyName"] = $key;
            }
            $values["Name"] = "packageconfigoption[" . $i . "]";
            $variable = "moduleConfigOption" . $i;
            $values["Value"] = \App::isInRequest($values["Name"]) ? \App::getFromRequest($values["Name"]) : $this->product->{$variable};
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
    public function getModuleSettings($productId)
    {
        $whmcs = \App::self();
        $product = $this->getProduct($productId);
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
    public static function formatSubDomainValuesToEnsureLeadingDotAndUnique(array $subDomains = array())
    {
        array_walk($subDomains, function (&$value, $key) {
            if ($value && substr($value, 0, 1) != ".") {
                $value = "." . $value;
            }
        });
        return array_unique($subDomains);
    }
}

?>