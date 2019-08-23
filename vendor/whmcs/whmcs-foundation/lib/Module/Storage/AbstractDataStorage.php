<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Storage;

abstract class AbstractDataStorage
{
    private $moduleName = NULL;
    protected abstract function readDataFromStorage();
    protected abstract function writeDataToStorage(array $allModulesData);
    public function __construct($moduleName)
    {
        $this->setModuleName($moduleName);
    }
    public static function forModule($moduleName)
    {
        return new static($moduleName);
    }
    public function getModuleName()
    {
        return $this->moduleName;
    }
    public function setModuleName($moduleName)
    {
        if (empty($moduleName)) {
            throw new \WHMCS\Exception("Module name cannot be empty: " . $moduleName);
        }
        $this->moduleName = $moduleName;
        return $this;
    }
    private function getModuleData()
    {
        $allModulesData = $this->readDataFromStorage();
        if (!isset($allModulesData[$this->moduleName])) {
            return array();
        }
        return $allModulesData[$this->moduleName];
    }
    private function setModuleData($data)
    {
        $allModulesData = $this->readDataFromStorage();
        $allModulesData[$this->moduleName] = $data;
        $this->writeDataToStorage($allModulesData);
    }
    public function deleteAll()
    {
        $allModulesData = $this->readDataFromStorage();
        unset($allModulesData[$this->moduleName]);
        $this->writeDataToStorage($allModulesData);
    }
    private function validateKey($key)
    {
        if (!is_string($key)) {
            throw new \WHMCS\Exception(sprintf("Key type for \"%s\" module data storage must be a string, \"%s\" was supplied", $this->moduleName, gettype($key)));
        }
        if (trim($key) === "") {
            throw new \WHMCS\Exception(sprintf("Empty key for \"%s\" module data storage", $this->moduleName));
        }
    }
    protected function validateValue($value)
    {
        if (is_object($value) || is_resource($value)) {
            throw new \WHMCS\Exception(sprintf("Invalid value type for \"%s\" module data storage: %s", $this->moduleName, gettype($value)));
        }
    }
    public function getValue($key, $default = NULL, $deleteImmediately = false)
    {
        $this->validateKey($key);
        $moduleData = $this->getModuleData();
        if (isset($moduleData[$key])) {
            $value = $moduleData[$key];
            if ($deleteImmediately) {
                unset($moduleData[$key]);
                $this->setModuleData($moduleData);
            }
        } else {
            $value = $default;
        }
        return $value;
    }
    public function getAndDeleteValue($key, $default = NULL)
    {
        return $this->getValue($key, $default, true);
    }
    public function setValue($key, $value)
    {
        $this->validateKey($key);
        $this->validateValue($value);
        $moduleData = $this->getModuleData();
        if (!is_null($value)) {
            $moduleData[$key] = $value;
        } else {
            unset($moduleData[$key]);
        }
        $this->setModuleData($moduleData);
    }
    public function deleteValue($key)
    {
        $this->setValue($key, null);
    }
}

?>