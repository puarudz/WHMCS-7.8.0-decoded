<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class TwoFactorAuthentication
{
    protected $settings = array();
    protected $clientmodules = array();
    protected $adminmodules = array();
    protected $adminmodule = "";
    protected $adminsettings = array();
    protected $admininfo = array();
    protected $clientmodule = "";
    protected $clientsettings = array();
    protected $clientinfo = array();
    protected $adminid = "";
    protected $clientid = "";
    public function __construct()
    {
        $this->loadSettings();
    }
    protected function loadSettings()
    {
        $this->settings = safe_unserialize(Config\Setting::getValue("2fasettings"));
        if (!isset($this->settings["modules"])) {
            return false;
        }
        foreach ($this->settings["modules"] as $module => $data) {
            if (!empty($data["clientenabled"])) {
                $this->clientmodules[] = $module;
            }
            if (!empty($data["adminenabled"])) {
                $this->adminmodules[] = $module;
            }
        }
        return true;
    }
    public function getModuleSettings($module)
    {
        return is_array($this->settings["modules"][$module]) ? $this->settings["modules"][$module] : array();
    }
    public function getModuleSetting($module, $name)
    {
        $settings = $this->getModuleSettings($module);
        return isset($settings[$name]) ? $settings[$name] : null;
    }
    public function setModuleSetting($module, $name, $value)
    {
        $this->settings["modules"][$module][$name] = $value;
        return $this;
    }
    public function isModuleEnabled($module)
    {
        return $this->isModuleEnabledForClients($module) || $this->isModuleEnabledForAdmins($module);
    }
    public function isModuleEnabledForClients($module)
    {
        $settings = $this->getModuleSettings($module);
        return (bool) $settings["clientenabled"];
    }
    public function isModuleEnabledForAdmins($module)
    {
        $settings = $this->getModuleSettings($module);
        return (bool) $settings["adminenabled"];
    }
    public function setModuleClientEnablementStatus($module, $status)
    {
        $this->setModuleSetting($module, "clientenabled", (int) (bool) $status);
        return $this;
    }
    public function setModuleAdminEnablementStatus($module, $status)
    {
        $this->setModuleSetting($module, "adminenabled", (int) (bool) $status);
        return $this;
    }
    public function isForced()
    {
        if ($this->clientid) {
            return $this->isForcedClients();
        }
        if ($this->adminid) {
            return $this->isForcedAdmins();
        }
        return false;
    }
    public function isForcedClients()
    {
        return (bool) $this->settings["forceclient"];
    }
    public function isForcedAdmins()
    {
        return (bool) $this->settings["forceadmin"];
    }
    public function setForcedClients($status)
    {
        $this->settings["forceclient"] = (int) (bool) $status;
        return $this;
    }
    public function setForcedAdmins($status)
    {
        $this->settings["forceadmin"] = (int) (bool) $status;
        return $this;
    }
    public function save()
    {
        Config\Setting::setValue("2fasettings", safe_serialize($this->settings));
        return $this;
    }
    public function isActiveClients()
    {
        return count($this->clientmodules) ? true : false;
    }
    public function isActiveAdmins()
    {
        return count($this->adminmodules) ? true : false;
    }
    public function setClientID($id)
    {
        $this->clientid = $id;
        $this->adminid = "";
        return $this->loadClientSettings();
    }
    public function setAdminID($id)
    {
        $this->clientid = "";
        $this->adminid = $id;
        return $this->loadAdminSettings();
    }
    protected function loadClientSettings()
    {
        $data = get_query_vals("tblclients", "id,firstname,lastname,email,authmodule,authdata", array("id" => $this->clientid, "status" => array("sqltype" => "NEQ", "value" => "Closed")));
        if (!$data["id"]) {
            return false;
        }
        $this->clientmodule = $data["authmodule"];
        $this->clientsettings = safe_unserialize($data["authdata"]);
        if (!is_array($this->clientsettings)) {
            $this->clientsettings = array();
        }
        unset($data["authmodule"]);
        unset($data["authdata"]);
        $data["username"] = $data["email"];
        $this->clientinfo = $data;
        return true;
    }
    protected function loadAdminSettings()
    {
        $data = get_query_vals("tbladmins", "id,username,firstname,lastname,email,authmodule,authdata", array("id" => $this->adminid, "disabled" => "0"));
        if (!$data["id"]) {
            return false;
        }
        $this->adminmodule = $data["authmodule"];
        $this->adminsettings = safe_unserialize($data["authdata"]);
        if (!is_array($this->adminsettings)) {
            $this->adminsettings = array();
        }
        unset($data["authmodule"]);
        unset($data["authdata"]);
        $this->admininfo = $data;
        return true;
    }
    public function getAvailableModules()
    {
        if ($this->clientid) {
            return $this->getAvailableClientModules();
        }
        if ($this->adminid) {
            return $this->getAvailableAdminModules();
        }
        return array_unique(array_merge($this->getAvailableClientModules(), $this->getAvailableAdminModules()));
    }
    protected function getAvailableClientModules()
    {
        return $this->clientmodules;
    }
    protected function getAvailableAdminModules()
    {
        return $this->adminmodules;
    }
    public function isEnabled()
    {
        if ($this->clientid) {
            return $this->isEnabledClient();
        }
        if ($this->adminid) {
            return $this->isEnabledAdmin();
        }
        return false;
    }
    protected function isEnabledClient()
    {
        return $this->clientmodule ? true : false;
    }
    protected function isEnabledAdmin()
    {
        return $this->adminmodule ? true : false;
    }
    protected function getModule()
    {
        if ($this->clientid) {
            return $this->clientmodule;
        }
        if ($this->adminid) {
            return $this->adminmodule;
        }
        return false;
    }
    public function moduleCall($function, $module = "", $extraParams = array())
    {
        $mod = new Module\Security();
        $module = $module ? $module : $this->getModule();
        $loaded = $mod->load($module);
        if (!$loaded) {
            return false;
        }
        $params = $this->buildParams($module);
        $params = array_merge($params, $extraParams);
        $result = $mod->call($function, $params);
        return $result;
    }
    protected function buildParams($module)
    {
        $params = array();
        $params["settings"] = $this->settings["modules"][$module];
        $params["user_info"] = $this->clientid ? $this->clientinfo : $this->admininfo;
        $params["user_settings"] = $this->clientid ? $this->clientsettings : $this->adminsettings;
        $params["post_vars"] = $_POST;
        $params["twoFactorAuthentication"] = $this;
        return $params;
    }
    public function activateUser($module, $settings = array())
    {
        $encryptionHash = \App::getApplicationConfig()->cc_encryption_hash;
        if ($this->clientid) {
            $backupCode = sha1($encryptionHash . $this->clientid . time());
            $backupCode = substr($backupCode, 0, 16);
            $settings["backupcode"] = sha1($backupCode);
            update_query("tblclients", array("authmodule" => $module, "authdata" => safe_serialize($settings)), array("id" => $this->clientid));
            return substr($backupCode, 0, 4) . " " . substr($backupCode, 4, 4) . " " . substr($backupCode, 8, 4) . " " . substr($backupCode, 12, 4);
        }
        if ($this->adminid) {
            $backupCode = sha1($encryptionHash . $this->adminid . time());
            $backupCode = substr($backupCode, 0, 16);
            $settings["backupcode"] = sha1($backupCode);
            update_query("tbladmins", array("authmodule" => $module, "authdata" => safe_serialize($settings)), array("id" => $this->adminid));
            return substr($backupCode, 0, 4) . " " . substr($backupCode, 4, 4) . " " . substr($backupCode, 8, 4) . " " . substr($backupCode, 12, 4);
        }
        return false;
    }
    public function disableUser()
    {
        if ($this->clientid) {
            update_query("tblclients", array("authmodule" => "", "authdata" => ""), array("id" => $this->clientid));
            return true;
        }
        if ($this->adminid) {
            update_query("tbladmins", array("authmodule" => "", "authdata" => ""), array("id" => $this->adminid));
            return true;
        }
        return false;
    }
    public function validateAndDisableUser($inputVerifyPassword)
    {
        if (!$this->isEnabled()) {
            throw new Exception("Not enabled");
        }
        $inputVerifyPassword = Input\Sanitize::decode($inputVerifyPassword);
        if ($this->clientid) {
            $databasePassword = get_query_val("tblclients", "password", array("id" => $this->clientid));
            $hasher = new Security\Hash\Password();
            if (!$hasher->verify($inputVerifyPassword, $databasePassword)) {
                throw new Exception("Password incorrect. Please try again.");
            }
        } else {
            if ($this->adminid) {
                $auth = new Auth();
                $auth->getInfobyID($this->adminid);
                if (!$auth->comparePassword($inputVerifyPassword)) {
                    throw new Exception("Password incorrect. Please try again.");
                }
            } else {
                throw new Exception("No user defined");
            }
        }
        $this->disableUser();
        return true;
    }
    public function saveUserSettings($arr)
    {
        if (!is_array($arr)) {
            return false;
        }
        if ($this->clientid) {
            $this->clientsettings = array_merge($this->clientsettings, $arr);
            update_query("tblclients", array("authdata" => safe_serialize($this->clientsettings)), array("id" => $this->clientid));
            return true;
        }
        if ($this->adminid) {
            $this->adminsettings = array_merge($this->adminsettings, $arr);
            update_query("tbladmins", array("authdata" => safe_serialize($this->adminsettings)), array("id" => $this->adminid));
            return true;
        }
        return false;
    }
    public function getUserSetting($var)
    {
        if ($this->clientid) {
            return isset($this->clientsettings[$var]) ? $this->clientsettings[$var] : "";
        }
        if ($this->adminid) {
            return isset($this->adminsettings[$var]) ? $this->adminsettings[$var] : "";
        }
        return false;
    }
    public function verifyBackupCode($code)
    {
        $backupCode = $this->getUserSetting("backupcode");
        if (!$backupCode) {
            return false;
        }
        $code = preg_replace("/[^a-z0-9]/", "", strtolower($code));
        $code = sha1($code);
        return $backupCode == $code;
    }
    public function generateNewBackupCode()
    {
        $encryptionHash = \App::getApplicationConfig()->cc_encryption_hash;
        $uid = $this->clientid ? $this->clientid : $this->adminid;
        $backupCode = sha1($encryptionHash . $uid . time() . rand(10000, 99999));
        $backupCode = substr($backupCode, 0, 16);
        $this->saveUserSettings(array("backupcode" => sha1($backupCode)));
        return substr($backupCode, 0, 4) . " " . substr($backupCode, 4, 4) . " " . substr($backupCode, 8, 4) . " " . substr($backupCode, 12, 4);
    }
}

?>