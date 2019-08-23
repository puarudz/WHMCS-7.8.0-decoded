<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class Smarty extends \SmartyBC
{
    public function __construct($admin = false, $policyName = NULL)
    {
        $this->setMbStringMode();
        $whmcs = \App::self();
        $config = \Config::self();
        parent::__construct();
        $this->setCaching(\Smarty::CACHING_OFF);
        $this->setTemplateDir(ROOTDIR . ($admin ? DIRECTORY_SEPARATOR . $whmcs->get_admin_folder_name() : "") . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR);
        $this->setCompileDir($config["templates_compiledir"]);
        $this->registerPlugin("modifier", "sprintf2", array("WHMCS\\Smarty", "sprintf2Modifier"));
        $this->registerPlugin("function", "lang", array("WHMCS\\Smarty", "langFunction"));
        $this->registerFilter("pre", array("WHMCS\\Smarty", "preFilterSmartyTemplateVariableScopeResolution"));
        if (!$policyName) {
            $policyName = "system";
        }
        $policy = \DI::make("WHMCS\\Smarty\\Security\\Policy", array($this, $policyName));
        $this->enableSecurity($policy);
    }
    protected function setMbStringMode()
    {
        self::$_MBSTRING = SMARTY_MBSTRING && function_exists("mb_split");
    }
    public function trigger_error($error_msg, $error_type = E_USER_WARNING)
    {
        if (function_exists("logActivity")) {
            logActivity("Smarty Error: " . $error_msg);
        } else {
            $error_msg = htmlentities($error_msg);
            trigger_error("Smarty error: " . $error_msg, $error_type);
        }
    }
    public function clearAllCaches()
    {
        $this->clearAllCache();
        $this->clearCompiledTemplate();
        $src = "<?php\nheader(\"Location: ../index.php\");";
        $whmcs = Application::getInstance();
        try {
            $compileDir = $this->getCompileDir();
            $file = new File($compileDir . DIRECTORY_SEPARATOR . "index.php");
            $file->create($src);
        } catch (\Exception $e) {
        }
    }
    public static function sprintf2Modifier($string, $arg1, $arg2 = "", $arg3 = "", $arg4 = "")
    {
        return sprintf($string, $arg1, $arg2, $arg3, $arg4);
    }
    public static function langFunction($params)
    {
        $translateKey = null;
        $forceAdmin = false;
        $returnValue = $defaultValue = "";
        foreach ($params as $key => $value) {
            if ($key == "key") {
                $translateKey = $value;
            } else {
                if ($key == "forceAdmin") {
                    $forceAdmin = true;
                } else {
                    if ($key == "defaultValue") {
                        $defaultValue = $value;
                    } else {
                        if (strpos($key, ":") !== 0) {
                            $params[":" . $key] = $value;
                        }
                    }
                }
            }
            unset($params[$key]);
        }
        if (\App::isAdminAreaRequest() || $forceAdmin) {
            $returnValue = \AdminLang::trans($translateKey, $params);
        } else {
            $returnValue = \Lang::trans($translateKey, $params);
        }
        if ($returnValue == $translateKey && $defaultValue) {
            $returnValue = $defaultValue;
        }
        return $returnValue;
    }
    public function fetch($template = NULL, $cache_id = NULL, $compile_id = NULL, $parent = NULL, $display = false, $merge_tpl_vars = true, $no_output_filter = false)
    {
        try {
            return parent::fetch($template, $cache_id, $compile_id, $parent, $display, $merge_tpl_vars, $no_output_filter);
        } catch (\Exception $e) {
            $this->trigger_error($e->getMessage());
        }
    }
    public function setMailMessage(Mail\Message $message)
    {
        $this->unregisterResource("mailMessage");
        $this->registerResource("mailMessage", new Smarty\Resource\MailMessage($message));
    }
    public static function preFilterSmartyTemplateVariableScopeResolution($source, \Smarty_Internal_Template $internal_Template)
    {
        $tags = $internal_Template->smarty->security_policy->disabled_tags;
        if (!is_array($tags) || in_array(Smarty\Security\Policy::TAG_COMPILER_PHP, $tags)) {
            return $source;
        }
        $source = "" . "{php}" . "\$template = \$_smarty_tpl;" . "\n" . "{/php}" . $source;
        return $source;
    }
}

?>