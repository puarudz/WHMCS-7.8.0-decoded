<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module;

class Security extends AbstractModule
{
    protected $type = self::TYPE_SECURITY;
    public function getActiveModules()
    {
        return (new \WHMCS\TwoFactorAuthentication())->getAvailableModules();
    }
    public function getAdminActivationForms($moduleName)
    {
        return array((new \WHMCS\View\Form())->setUriPrefixAdminBaseUrl("configtwofa.php")->setMethod(\WHMCS\View\Form::METHOD_GET)->setParameters(array("module" => $moduleName))->setSubmitLabel(\AdminLang::trans("global.activate")));
    }
    public function getAdminManagementForms($moduleName)
    {
        return array((new \WHMCS\View\Form())->setUriPrefixAdminBaseUrl("configtwofa.php")->setMethod(\WHMCS\View\Form::METHOD_GET)->setParameters(array("module" => $moduleName))->setSubmitLabel(\AdminLang::trans("global.manage")));
    }
}

?>