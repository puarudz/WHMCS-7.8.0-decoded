<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class TokenManager
{
    protected $namespaceSettings = array();
    protected $defaultNamespaceValue = true;
    public static function init(Init $whmcs)
    {
        $obj = new self();
        $namespace_settings = $obj->getStoredNamespaceSettings($whmcs);
        if (count($namespace_settings) < 1) {
            $namespace_settings = $obj->getDefaultNamespaceSettings();
            $obj->setStoredNamespaceSettings($whmcs, $namespace_settings);
        }
        $obj->setNamespaceSettings($namespace_settings);
        return $obj;
    }
    public function __construct()
    {
        return $this;
    }
    public function getToken()
    {
        return array_key_exists("tkval", $_SESSION) ? $_SESSION["tkval"] : null;
    }
    public function setToken($token)
    {
        if (!is_string($token) || empty($token)) {
            throw new \UnexpectedValueException("Token must be a valid value");
        }
        $_SESSION["tkval"] = $token;
        return $token;
    }
    public function conditionallySetToken()
    {
        if (is_null($this->getToken())) {
            $this->setToken(genRandomVal());
        }
        return $this;
    }
    public function generateToken($type = "form")
    {
        $tkval = ($t = $this->getToken()) ? $t : genRandomVal();
        $token = sha1($tkval . session_id() . ":whmcscrsf");
        if ($type == "plain") {
            return $token;
        }
        if ($type == "link") {
            return "&token=" . $token;
        }
        if ($type == "form") {
            return "<input type=\"hidden\" name=\"token\" value=\"" . $token . "\" />";
        }
    }
    public function checkToken($namespace = "WHMCS.default", $token = NULL)
    {
        $strict_check = true;
        $namespace_settings = $this->getNamespaceSettings();
        if (!$namespace_settings["WHMCS.default"]) {
            return true;
        }
        if (array_key_exists($namespace, $namespace_settings)) {
            $strict_check = $namespace_settings[$namespace] ? true : false;
        }
        if (!$strict_check) {
            return true;
        }
        if (is_null($token)) {
            $token = isset($_REQUEST["token"]) ? $_REQUEST["token"] : "";
        }
        if (!$this->isValidToken($token)) {
            $this->handleInvalidToken();
            return false;
        }
        return true;
    }
    public function handleInvalidToken()
    {
        if (defined("CLIENTAREA") && !defined("ADMINAREA")) {
            Session::destroy();
            redir("", "clientarea.php");
        }
        throw new Exception\ProgramExit("Invalid CSRF Protection Token");
    }
    public function isValidToken($token = "")
    {
        $expected = $this->generateToken("plain");
        return $expected == $token ? true : false;
    }
    public function getDefaultNamespaceSettings()
    {
        return array("WHMCS.default" => $this->defaultNamespaceValue, "WHMCS.admin.default" => $this->defaultNamespaceValue, "WHMCS.domainchecker" => false);
    }
    public function getStoredNamespaceSettings($whmcs)
    {
        $serialized_namespace = $whmcs->get_config("token_namespaces");
        $namespace_settings = $serialized_namespace ? safe_unserialize($serialized_namespace) : array();
        if (!is_array($namespace_settings)) {
            $namespace_settings = array();
        }
        return $namespace_settings;
    }
    public function setStoredNamespaceSettings($whmcs, $namespace_settings)
    {
        $serialized_namespace = safe_serialize($namespace_settings);
        return $whmcs->set_config("token_namespaces", $serialized_namespace, $whmcs->getDatabaseObj()->getConnection());
    }
    public function getNamespaceSettings()
    {
        return $this->namespaceSettings;
    }
    public function setNamespaceSettings($namespace_settings)
    {
        if (!is_array($namespace_settings)) {
            throw new \InvalidArgumentException("Namespace settings must be an array");
        }
        $this->namespaceSettings = $namespace_settings;
        return $this;
    }
    public function getNamespaceValue($namespace)
    {
        $settings = $this->getNamespaceSettings();
        if (array_key_exists($namespace, $settings)) {
            return $settings[$namespace] ? true : false;
        }
        return $this->defaultNamespaceValue;
    }
    public function generateAdminConfigurationHTMLRows($aInt)
    {
        $rows = "";
        $ns = $this->getNamespaceSettings();
        $whmcs_defaults = $this->getDefaultNamespaceSettings();
        $stored_default = $ns["WHMCS.default"];
        $system_default_value = $whmcs_defaults["WHMCS.default"];
        unset($ns["WHMCS.default"]);
        $rows = $this->htmlRow($aInt, "WHMCS.default", $stored_default, $system_default_value);
        foreach ($ns as $key => $value) {
            if (strpos($key, "WHMCS.admin.") === 0) {
                continue;
            }
            $system_default_value = array_key_exists($key, $whmcs_defaults) ? $whmcs_defaults[$key] : null;
            $rows .= $this->htmlRow($aInt, $key, $value, $system_default_value, $stored_default);
        }
        return $rows;
    }
    protected function htmlRow($aInt, $key, $value, $whmcs_default = NULL, $show = true)
    {
        $field = "csrftoken";
        $basekey = $field . "." . $key;
        $htmlkey = str_replace(".", "_ns_", $basekey);
        $text = $aInt->lang("general", $htmlkey);
        $textinfo = $aInt->lang("general", $htmlkey . "info");
        if (!$text) {
            $text = $key;
        }
        if (!$textinfo) {
            $textinfo = $key;
        }
        $ondefault = "";
        $offdefault = "";
        $onvalue = "";
        $offvalue = "";
        if ($value) {
            $onvalue = " checked";
        } else {
            $offvalue = " checked";
        }
        if (!is_null($whmcs_default)) {
            if ($whmcs_default) {
                $ondefault = " (" . $aInt->lang("global", "default") . ")";
            } else {
                $offdefault = " (" . $aInt->lang("global", "default") . ")";
            }
        }
        $jsshow = "";
        $jshide = "";
        $row_attr = "";
        if ($key == "WHMCS.default") {
            $jsshow = " onclick=\"\$('." . $field . "').show();\"";
            $jshide = " onclick=\"\$('." . $field . "').hide();\"";
        } else {
            $row_attr = " class=\"" . $field . "\"";
        }
        if (!$show) {
            $row_attr .= "style=\"display:none\"";
        }
        $row = "<tr" . $row_attr . ">" . "<td class=\"fieldlabel\">" . $text . "</td>" . "<td class=\"fieldarea\">" . "<span>" . $textinfo . "</span><br/>" . "<label class=\"checkbox-inline\"><input type=\"radio\" name=\"" . $htmlkey . "\" value=\"on\" " . $jsshow . $onvalue . ">" . $aInt->lang("global", "enabled") . $ondefault . "</label><br/>" . "<label class=\"checkbox-inline\"><input type=\"radio\" name=\"" . $htmlkey . "\" value=\"off\" " . $jshide . $offvalue . ">" . $aInt->lang("global", "disabled") . $offdefault . "</td></tr>" . "\n";
        return $row;
    }
    public function processAdminHTMLSave($whmcs)
    {
        $ns = $this->getNamespaceSettings();
        foreach ($ns as $key => $value) {
            if (strpos($key, "WHMCS.admin.") === 0) {
                continue;
            }
            $ns[$key] = $this->processOneNamespaceRequest($whmcs, $key);
        }
        $this->setNamespaceSettings($ns);
        $this->setStoredNamespaceSettings($whmcs, $ns);
        return $this;
    }
    protected function processOneNamespaceRequest($whmcs, $key)
    {
        $postvar_name = str_replace(".", "_ns_", "csrftoken." . $key);
        $postvar_value = $whmcs->get_req_var($postvar_name);
        return $postvar_value == "on" ? true : false;
    }
}

?>