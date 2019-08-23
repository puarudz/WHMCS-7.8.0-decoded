<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Scheduling\Task;

abstract class RegisterTrait
{
    protected $outputInstances = array();
    public function output($key)
    {
        $namespaceKey = $this->getNamespace() . "." . $key;
        if (empty($this->outputInstances[$key])) {
            $outputKeys = $this->getOutputKeys();
            $friendlyName = isset($outputKeys[$key]["name"]) ? $outputKeys[$key]["name"] : $key;
            $defaultValue = isset($outputKeys[$key]["defaultValue"]) ? $outputKeys[$key]["defaultValue"] : 0;
            $output = new \WHMCS\Log\Register();
            $output->setNamespace($namespaceKey);
            $output->setName($friendlyName);
            $output->setNamespaceId($this->id);
            $output->setValue($defaultValue);
            $this->outputInstances[$key] = $output;
        }
        return $this->outputInstances[$key];
    }
    public function getNamespace()
    {
        if (method_exists($this, "getSystemName")) {
            return $this->getSystemName();
        }
        $classname = static::class;
        $namespaces = explode("\\", $classname);
        return array_pop($namespaces);
    }
    public function getLatestOutputs(array $outputKeys = array())
    {
        if (empty($outputKeys)) {
            $namespaceKeys = array_keys($this->getOutputKeys());
        } else {
            $namespaceKeys = $outputKeys;
        }
        $namespace = $this->getNamespace();
        $applyNamespace = function ($value) use($namespace) {
            return $namespace . "." . $value;
        };
        $namespaces = array_map($applyNamespace, $namespaceKeys);
        return (new \WHMCS\Log\Register())->latestByNamespaces($namespaces, $this->id);
    }
    public function getOutputsSince(\WHMCS\Carbon $since, array $outputKeys = array())
    {
        if (empty($outputKeys)) {
            $namespaceKeys = array_keys($this->getOutputKeys());
        } else {
            $namespaceKeys = $outputKeys;
        }
        $namespace = $this->getNamespace();
        $applyNamespace = function ($value) use($namespace) {
            return $namespace . "." . $value;
        };
        $namespaces = array_map($applyNamespace, $namespaceKeys);
        return (new \WHMCS\Log\Register())->sinceByNamespace($since, $namespaces, $this->id);
    }
    public abstract function getOutputKeys();
}

?>