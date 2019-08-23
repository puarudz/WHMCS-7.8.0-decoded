<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class Addon
{
    private $id = 0;
    private $userId = 0;
    private $data = array();
    private $moduleParams = array();
    private $moduleResults = array();
    public function __construct($addonId = NULL, $userId = NULL)
    {
        if (!function_exists("checkContactPermission")) {
            require_once ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "clientfunctions.php";
        }
        if ($addonId) {
            $this->setAddonId($addonId, $userId);
        }
    }
    public function setAddonId($addonId, $userId = 0)
    {
        $this->id = $addonId;
        $this->userId = $userId;
        $this->data = array();
        $this->moduleParams = array();
        $this->moduleResults = array();
        return $this->getAddonData();
    }
    public function getAddonData()
    {
        $addon = Service\Addon::with("productAddon")->where("id", $this->id);
        if ($this->userId) {
            $addon = $addon->where("userid", $this->userId);
        }
        $addon = $addon->first();
        if ($addon) {
            $data = $addon->toArray();
            $data["name"] = $data["name"] ?: $addon->productAddon->name;
            $this->data = $data;
            return true;
        }
        return false;
    }
    public function getData($key1, $key2 = "")
    {
        if ($key2) {
            return isset($this->data[$key1][$key2]) ? $this->data[$key1][$key2] : "";
        }
        return isset($this->data[$key1]) ? $this->data[$key1] : "";
    }
    public function getModule()
    {
        return \App::sanitize("0-9a-z_-", $this->getData("product_addon", "module"));
    }
    public function getId()
    {
        return (int) $this->getData("id");
    }
    public function hasFunction($function)
    {
        $moduleInterface = new Module\Server();
        $moduleName = $this->getModule();
        if (!$moduleName) {
            $this->moduleResults = array("error" => "Addon not assigned to a module");
            return false;
        }
        $loaded = $moduleInterface->load($moduleName);
        if (!$loaded) {
            $this->moduleResults = array("error" => "Product Addon module not found");
            return false;
        }
        return $moduleInterface->functionExists($function);
    }
    public function moduleCall($function, $vars = array())
    {
        $moduleInterface = new Module\Server();
        $moduleName = $this->getModule();
        if (!$moduleName) {
            $this->moduleResults = array("error" => "Addon not assigned to a module");
            return false;
        }
        $loaded = $moduleInterface->load($moduleName);
        if (!$loaded) {
            $this->moduleResults = array("error" => "Addon module not found");
            return false;
        }
        $moduleInterface->setAddonId($this->getId());
        switch ($function) {
            case "CreateAccount":
                $hookFunction = "Create";
                break;
            case "SuspendAccount":
                $hookFunction = "Suspend";
                break;
            case "TerminateAccount":
                $hookFunction = "Terminate";
                break;
            case "UnsuspendAccount":
                $hookFunction = "Unsuspend";
                break;
            default:
                $hookFunction = $function;
        }
        $builtParams = array_merge($moduleInterface->getParams(), $vars);
        $hookResults = run_hook("PreModule" . $hookFunction, array("params" => $vars));
        try {
            if (processHookResults($moduleName, $function, $hookResults)) {
                return true;
            }
        } catch (Exception $e) {
            $this->moduleResults = array("error" => $e->getMessage());
            return false;
        }
        $results = $moduleInterface->call($function, $builtParams);
        $hookVars = array("params" => $builtParams, "results" => $results, "functionExists" => $results !== Module\Server::FUNCTIONDOESNTEXIST, "functionSuccessful" => is_array($results) && empty($results["error"]) || is_object($results));
        $successOrFail = "";
        if (!$hookVars["functionSuccessful"] && $hookResults["functionExists"]) {
            $successOrFail = "Failed";
        }
        $hookResults = run_hook("AfterModule" . $hookFunction . $successOrFail, $hookVars);
        try {
            if (processHookResults($moduleName, $function, $hookResults)) {
                return true;
            }
        } catch (Exception $e) {
            $this->moduleResults = array("error" => $e->getMessage());
            return false;
        }
        if ($results == false) {
            $this->moduleResults = array("error" => "Function not found");
            return false;
        }
        if (is_array($results)) {
            $results = array("data" => $results);
        } else {
            $results = $results == "success" || !$results ? array() : array("error" => $results, "data" => $results);
        }
        $this->moduleResults = $results;
        return isset($results["error"]) && $results["error"] ? false : true;
    }
    public function getModuleReturn($var = "")
    {
        if (!$var) {
            return $this->moduleResults;
        }
        return isset($this->moduleResults[$var]) ? $this->moduleResults[$var] : "";
    }
    public function getLastError()
    {
        return $this->getModuleReturn("error");
    }
}

?>