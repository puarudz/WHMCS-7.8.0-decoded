<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Domains;

class AdditionalFields
{
    protected $fieldsData = array();
    protected $activeTLD = "";
    protected $activeDomain = "";
    protected $activeTLDValues = array();
    protected $resourcePath = NULL;
    protected $countries = NULL;
    protected $placeholderData = NULL;
    protected $domainType = "register";
    public function setDomain($domain)
    {
        $this->activeDomain = $domain;
        $domainparts = explode(".", $domain, 2);
        $this->setTLD($domainparts[1]);
        return $this;
    }
    public function setTLD($tld)
    {
        if (substr($tld, 0, 1) != ".") {
            $tld = "." . $tld;
        }
        $this->activeTLD = $tld;
        $this->loadFieldsData();
        return $this;
    }
    public function getTLD()
    {
        return $this->activeTLD;
    }
    public function getResourcePath()
    {
        return $this->resourcePath;
    }
    protected function fetch($file)
    {
        global $_LANG;
        $additionaldomainfields = array();
        if (file_exists($this->getResourcePath() . $file)) {
            if (is_null($this->countries)) {
                $this->countries = new \WHMCS\Utility\Country();
            }
            $countries = $this->countries->getCountryNameArray();
            require $this->getResourcePath() . $file;
        }
        if (is_array($additionaldomainfields) && array_key_exists($this->getTLD(), $additionaldomainfields)) {
            return $additionaldomainfields[$this->getTLD()];
        }
        return array();
    }
    protected function loadFieldsData()
    {
        $fields = $this->fetch("dist.additionalfields.php");
        $fieldMap = array();
        foreach ($fields as $key => $data) {
            $fieldMap[$data["Name"]] = $key;
        }
        $domainRegistrar = "";
        $idProtection = false;
        try {
            $domain = \WHMCS\Database\Capsule::table("tbldomains")->where("domain", $this->activeDomain)->where("registrar", "!=", "");
            if (\WHMCS\Session::get("uid")) {
                $domain->where("userid", (int) \WHMCS\Session::get("uid"));
            }
            $domain = $domain->first();
            if (!$domain) {
                $extensionData = Extension::where("extension", $this->getTLD())->where("autoreg", "!=", "")->firstOrFail();
                $domainRegistrar = $extensionData->autoRegistrationRegistrar;
            } else {
                $domainRegistrar = $domain->registrar;
                $idProtection = $domain->idprotection;
            }
        } catch (\Exception $e) {
        }
        if ($domainRegistrar) {
            $registrar = new \WHMCS\Module\Registrar();
            if ($registrar->load($domainRegistrar)) {
                $result = $registrar->call("AdditionalDomainFields", array("tld" => substr($this->getTLD(), 1), "idprotection" => $idProtection, "type" => $this->domainType, "fields" => $fields));
                if (is_array($result) && array_key_exists("fields", $result)) {
                    $this->processFieldOverrides($fields, $fieldMap, $result["fields"]);
                }
            }
        }
        $this->processFieldOverrides($fields, $fieldMap, $this->fetch("additionalfields.php"));
        foreach ($fields as $key => $values) {
            if (array_key_exists("Options", $values)) {
                $fields[$key]["Options"] = $this->replacePlaceholders($values["Options"]);
            }
        }
        $this->fieldsData = $fields;
    }
    public function getFields()
    {
        return $this->fieldsData;
    }
    protected function populatePlaceholderData()
    {
        if (is_null($this->placeholderData)) {
            $this->placeholderData = array();
            $countries = $this->countries->getCountryNameArray();
            $this->placeholderData["Countries"] = implode(",", $countries);
            $countryMap = array();
            foreach ($countries as $key => $value) {
                $countryMap[] = str_replace(",", "", $key) . "|" . str_replace(",", "", $value);
            }
            $this->placeholderData["CountryCodeMap"] = implode(",", $countryMap);
        }
    }
    protected function replacePlaceholders($options)
    {
        $placeholders = array("Countries", "CountryCodeMap");
        foreach ($placeholders as $placeholder) {
            $placeholderTag = "{" . $placeholder . "}";
            if (strpos($options, $placeholderTag) !== false) {
                $this->populatePlaceholderData();
                $options = str_replace($placeholderTag, $this->placeholderData[$placeholder], $options);
            }
        }
        return $options;
    }
    protected function getConfigValue($fieldKey, $name)
    {
        return array_key_exists($name, $this->fieldsData[$fieldKey]) ? $this->fieldsData[$fieldKey][$name] : "";
    }
    protected function getFieldName($fieldKey)
    {
        global $_LANG;
        $langvar = $this->getConfigValue($fieldKey, "LangVar");
        $displayname = $this->getConfigValue($fieldKey, "DisplayName");
        if ($langvar && isset($_LANG[$langvar])) {
            return $_LANG[$langvar];
        }
        if ($displayname) {
            return $displayname;
        }
        return $this->getConfigValue($fieldKey, "Name");
    }
    public function setFieldValues($values)
    {
        if (is_array($values)) {
            $this->activeTLDValues = $values;
        }
        return $this;
    }
    protected function getFieldValue($fieldKey)
    {
        $val = array_key_exists($fieldKey, $this->activeTLDValues) ? $this->activeTLDValues[$fieldKey] : "";
        if ($val === "") {
            $name = $this->getConfigValue($fieldKey, "Name");
            $val = array_key_exists($name, $this->activeTLDValues) ? $this->activeTLDValues[$name] : "";
        }
        return $val;
    }
    public function getFieldsForOutput($domainKey = "")
    {
        global $_LANG;
        $domainKey = is_numeric($domainKey) ? "[" . $domainKey . "]" : "";
        $domainfields = array();
        foreach ($this->getFields() as $fieldKey => $values) {
            $type = $this->getConfigValue($fieldKey, "Type");
            $size = $this->getConfigValue($fieldKey, "Size");
            $options = $this->getConfigValue($fieldKey, "Options");
            $required = $this->getConfigValue($fieldKey, "Required");
            $defaultval = $this->getConfigValue($fieldKey, "Default");
            if ($this->getFieldValue($fieldKey) !== "") {
                $defaultval = $this->getFieldValue($fieldKey);
            }
            $input = $this->genFieldHTML("domainfield" . $domainKey . "[" . $fieldKey . "]", $type, $size, $options, $defaultval, $required);
            $desc = $this->getConfigValue($fieldKey, "Description");
            if ($desc) {
                $input .= " " . $desc;
            }
            $domainfields[$this->getFieldName($fieldKey)] = $input;
        }
        return $domainfields;
    }
    protected function genFieldHTML($name, $type, $size, $options, $defaultval, $required)
    {
        if ($type == "dropdown" || $type == "radio") {
            $fieldoptions = array();
            $tmpoptions = explode(",", $options);
            foreach ($tmpoptions as $optionvalue) {
                $opkey = $opvalue = $optionvalue;
                if (strpos($opkey, "|")) {
                    $opkey = explode("|", $opkey, 2);
                    $opvalue = trim($opkey[1]);
                    $opkey = trim($opkey[0]);
                    if (!$opvalue) {
                        $opvalue = $opkey;
                    }
                }
                $fieldoptions[$opkey] = $opvalue;
            }
        }
        $frm = new \WHMCS\Form();
        $input = "";
        if ($type == "text") {
            $input = $frm->text($name, $defaultval, $size, false, "form-control input-250" . ($required ? " input-inline" : ""));
            if ($required) {
                $input .= " *";
            }
        } else {
            if ($type == "dropdown") {
                $input = $frm->dropdown($name, $fieldoptions, $defaultval);
            } else {
                if ($type == "tickbox") {
                    $input = $frm->checkbox($name, "", $defaultval, "on");
                } else {
                    if ($type == "radio") {
                        $input = $frm->radio($name, $fieldoptions, $defaultval);
                    } else {
                        if ($type == "display") {
                            $input = "<p>" . $defaultval . "</p>";
                        }
                    }
                }
            }
        }
        return $input;
    }
    public function getMissingRequiredFields()
    {
        $missingfields = array();
        foreach ($this->getFields() as $fieldKey => $values) {
            if ($this->getConfigValue($fieldKey, "Required") && !$this->getFieldValue($fieldKey)) {
                $missingfields[] = $this->getFieldName($fieldKey);
            }
        }
        return $missingfields;
    }
    public function isMissingRequiredFields()
    {
        return count($this->getMissingRequiredFields()) ? true : false;
    }
    public function getFieldValuesFromDatabase($domainID)
    {
        $values = array();
        $result = select_query("tbldomainsadditionalfields", "name,value", array("domainid" => $domainID));
        while ($data = mysql_fetch_array($result)) {
            $values[$data["name"]] = $data["value"];
        }
        $this->setFieldValues($values);
        return $values;
    }
    public function getAsNameValueArray()
    {
        $fields = array();
        foreach ($this->getFields() as $fieldKey => $values) {
            $name = $this->getConfigValue($fieldKey, "Name");
            $value = $this->getFieldValue($fieldKey);
            $fields[$name] = $value;
        }
        return $fields;
    }
    public function saveToDatabase($domainID, $creating = true)
    {
        $changes = array();
        $userId = 0;
        foreach ($this->getFields() as $fieldKey => $values) {
            $name = $this->getConfigValue($fieldKey, "Name");
            $value = $this->getFieldValue($fieldKey);
            $additionalField = \WHMCS\Domain\AdditionalField::firstOrNew(array("domainid" => $domainID, "name" => $name));
            if (is_null($additionalField->value)) {
                $additionalField->value = "";
            }
            if (is_null($value)) {
                $value = "";
            }
            if ($additionalField->value != $value) {
                $changes[] = (string) $name . " changed from '" . $additionalField->value . "' to '" . $value . "'";
                $additionalField->value = $value;
                $additionalField->save();
            }
            if (!$additionalField->exists) {
                $additionalField->save();
            }
            if (!$userId) {
                $userId = $additionalField->domain->clientId;
            }
        }
        if (count($changes) && !$creating) {
            logActivity("Modified Domain Additional Fields - " . implode(", ", $changes) . " - User ID: " . $userId . " - Domain ID: " . $domainID, $userId);
        }
    }
    protected function processFieldOverrides(array &$fields, array $fieldMap, array $overrideData)
    {
        foreach ($overrideData as $key => $data) {
            if (array_key_exists($data["Name"], $fieldMap)) {
                $storedKey = $fieldMap[$data["Name"]];
                if (array_key_exists("Remove", $data) && $data["Remove"] === true) {
                    unset($fields[$storedKey]);
                } else {
                    $fields[$storedKey] = array_merge($fields[$storedKey], $data);
                }
            } else {
                $fields[] = $data;
            }
        }
    }
    public function setDomainType($type = "register")
    {
        $type = strtolower($type);
        if (!in_array($type, array("register", "transfer"))) {
            $type = "register";
        }
        $this->domainType = $type;
        return $this;
    }
}

?>