<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Config;

class Template
{
    protected $configFile = NULL;
    protected $name = NULL;
    protected $metaData = array();
    protected $properties = array();
    protected $configDefinitions = array();
    protected $config = array();
    public function __construct(\WHMCS\File $configFile)
    {
        if ($configFile->exists()) {
            $config = \Symfony\Component\Yaml\Yaml::parse($configFile->contents());
            $this->configFile = $configFile;
            $this->name = isset($config["name"]) ? $config["name"] : null;
            $this->metaData = isset($config["meta"]) ? $config["meta"] : array();
            $this->properties = isset($config["properties"]) ? $config["properties"] : array();
            $this->configDefinitions = isset($config["config-definitions"]) ? $config["config-definitions"] : array();
            $this->config = isset($config["config"]) ? $config["config"] : array();
        }
    }
    public function save(\WHMCS\File $saveTo = NULL)
    {
        $yaml = \Symfony\Component\Yaml\Yaml::dump(array("name" => $this->name, "meta" => $this->metaData, "properties" => $this->properties, "config-definitions" => $this->configDefinitions, "config" => $this->config), 4);
        if (is_null($saveTo)) {
            $this->configFile->create($yaml);
        } else {
            $saveTo->create($yaml);
        }
        return $this;
    }
    public function saveTo($path)
    {
        return $this->save(new \WHMCS\File($path));
    }
    public function getProperties()
    {
        return $this->properties;
    }
    public function setProperty($key, $value)
    {
        $this->properties[$key] = $value;
        return $this;
    }
    public function getConfigDefinitions()
    {
        return $this->configDefinitions;
    }
    public function getConfig()
    {
        return $this->config;
    }
    public function setConfig($key, $value)
    {
        if (!array_key_exists($key, $this->configDefinitions)) {
            throw new \WHMCS\Exception("Unknown config key " . $key . ".");
        }
        if (isset($this->configDefinitions[$key]["type"])) {
            switch ($this->configDefinitions[$key]["type"]) {
                case "int":
                case "integer":
                    $value = intval($value);
                    break;
                case "float":
                    $value = floatval($value);
                    break;
                case "bool":
                case "boolean":
                    $value = (bool) $value;
                    break;
                default:
                    $value = trim($value);
            }
        } else {
            $value = trim($value);
        }
        $this->config[$key] = $value;
        return $this;
    }
}

?>