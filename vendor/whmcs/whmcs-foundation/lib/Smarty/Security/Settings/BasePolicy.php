<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Smarty\Security\Settings;

class BasePolicy
{
    protected $phpFunctions = NULL;
    protected $phpModifiers = NULL;
    protected $allowedModifiers = NULL;
    protected $disabledModifiers = NULL;
    protected $allowedTags = NULL;
    protected $disabledTags = NULL;
    protected $staticClasses = NULL;
    protected $trustedStaticMethods = NULL;
    protected $trustedStaticProperties = NULL;
    protected $disabledSpecialSmartyVars = NULL;
    protected $streams = NULL;
    protected $allowSuperGlobals = NULL;
    protected $allowConstants = NULL;
    protected $secureDir = NULL;
    protected $trustedDir = NULL;
    public function __construct(array $data)
    {
        $defaults = $this->getDefaultPolicySettings();
        foreach ($defaults as $key => $value) {
            if (array_key_exists($key, $data)) {
                $value = $data[$key];
            }
            $method = "set" . studly_case($key);
            $this->{$method}($value);
        }
    }
    protected function getDefaultPolicySettings()
    {
        return array("php_functions" => array(), "php_modifiers" => array(), "allowed_modifiers" => array(), "disabled_modifiers" => array(), "allowed_tags" => array(), "disabled_tags" => array("private_php"), "static_classes" => array(), "trusted_static_methods" => array(), "trusted_static_properties" => array(), "disabled_special_smarty_vars" => array(), "streams" => array(), "allow_super_globals" => true, "allow_constants" => true, "secure_dir" => array(ROOTDIR), "trusted_dir" => array());
    }
    public function getPhpFunctions()
    {
        return $this->phpFunctions;
    }
    public function setphpFunctions($phpFunctions)
    {
        $this->phpFunctions = $phpFunctions;
        return $this;
    }
    public function getPhpModifiers()
    {
        return $this->phpModifiers;
    }
    public function setPhpModifiers($phpModifiers)
    {
        $this->phpModifiers = $phpModifiers;
        return $this;
    }
    public function getAllowedModifiers()
    {
        return $this->allowedModifiers;
    }
    public function setAllowedModifiers($allowedModifiers)
    {
        $this->allowedModifiers = $allowedModifiers;
        return $this;
    }
    public function getDisabledModifiers()
    {
        return $this->disabledModifiers;
    }
    public function setDisabledModifiers($disabledModifiers)
    {
        $this->disabledModifiers = $disabledModifiers;
        return $this;
    }
    public function getAllowedTags()
    {
        return $this->allowedTags;
    }
    public function setAllowedTags($allowedTags)
    {
        $this->allowedTags = $allowedTags;
        return $this;
    }
    public function getDisabledTags()
    {
        return $this->disabledTags;
    }
    public function setDisabledTags($disabledTags)
    {
        $this->disabledTags = $disabledTags;
        return $this;
    }
    public function getStaticClasses()
    {
        return $this->staticClasses;
    }
    public function setStaticClasses($staticClasses)
    {
        $this->staticClasses = $staticClasses;
        return $this;
    }
    public function getTrustedStaticMethods()
    {
        return $this->trustedStaticMethods;
    }
    public function setTrustedStaticMethods($staticMethods)
    {
        $this->trustedStaticMethods = $staticMethods;
        return $this;
    }
    public function getTrustedStaticProperties()
    {
        return $this->trustedStaticProperties;
    }
    public function setTrustedStaticProperties($trustedStaticProperties)
    {
        $this->trustedStaticProperties = $trustedStaticProperties;
        return $this;
    }
    public function getDisabledSpecialSmartyVars()
    {
        return $this->disabledSpecialSmartyVars;
    }
    public function setDisabledSpecialSmartyVars($disabledSpecialSmartyVars)
    {
        $this->disabledSpecialSmartyVars = $disabledSpecialSmartyVars;
        return $this;
    }
    public function getStreams()
    {
        return $this->streams;
    }
    public function setStreams($streams)
    {
        $this->streams = $streams;
        return $this;
    }
    public function isAllowSuperGlobals()
    {
        return $this->allowSuperGlobals;
    }
    public function setAllowSuperGlobals($allowSuperGlobals)
    {
        $this->allowSuperGlobals = $allowSuperGlobals;
        return $this;
    }
    public function getSecureDir()
    {
        return $this->secureDir;
    }
    public function setSecureDir($secureDir)
    {
        $this->secureDir = $secureDir;
        return $this;
    }
    public function getTrustedDir()
    {
        return $this->trustedDir;
    }
    public function setTrustedDir($trustedDir)
    {
        $this->trustedDir = $trustedDir;
        return $this;
    }
    public function isAllowConstants()
    {
        return $this->allowConstants;
    }
    public function setAllowConstants($allowConstants)
    {
        $this->allowConstants = $allowConstants;
        return $this;
    }
    public function hasPhpTagCompiler()
    {
        return !in_array(\WHMCS\Smarty\Security\Policy::TAG_COMPILER_PHP, (array) $this->getDisabledTags());
    }
}

?>