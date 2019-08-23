<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Apps\App\Utility;

class AppHelper
{
    protected $excludedFromActiveApps = array("servers.marketconnect");
    public function isExcludedFromActiveList($appKey)
    {
        return in_array($appKey, $this->excludedFromActiveApps);
    }
    public function getNonModuleActivationForms($moduleType, $moduleName)
    {
        switch ($moduleType) {
            case "marketconnect":
                return array((new \WHMCS\View\Form())->setUriPrefixAdminBaseUrl("marketconnect.php")->setMethod(\WHMCS\View\Form::METHOD_GET)->setParameters(array("activate" => $moduleName))->setSubmitLabel(\AdminLang::trans("global.activate")));
            case "signin":
                return array((new \WHMCS\View\Form())->setUriByRoutePath("admin-setup-authn-view")->setMethod(\WHMCS\View\Form::METHOD_GET)->setParameters(array("rp" => "/admin/setup/authn/view", "activate" => $moduleName))->setSubmitLabel(\AdminLang::trans("global.activate")));
            default:
                throw new \WHMCS\Exception\Module\NotImplemented();
        }
    }
    public function getNonModuleManagementForms($moduleType, $moduleName)
    {
        switch ($moduleType) {
            case "marketconnect":
                return array((new \WHMCS\View\Form())->setUriPrefixAdminBaseUrl("marketconnect.php")->setMethod(\WHMCS\View\Form::METHOD_GET)->setParameters(array("manage" => $moduleName))->setSubmitLabel(\AdminLang::trans("global.manage")));
            case "signin":
                return array((new \WHMCS\View\Form())->setUriByRoutePath("admin-setup-authn-view")->setMethod(\WHMCS\View\Form::METHOD_GET)->setParameters(array("rp" => "/admin/setup/authn/view"))->setSubmitLabel(\AdminLang::trans("global.manage")));
            default:
                throw new \WHMCS\Exception\Module\NotImplemented();
        }
    }
    public function isNonModuleActive($moduleType, $moduleName)
    {
        switch ($moduleType) {
            case "marketconnect":
                return (bool) \WHMCS\MarketConnect\Service::where("name", $moduleName)->first()->status;
            case "signin":
                $appMap = array("google" => \WHMCS\Authentication\Remote\Providers\Google\GoogleSignin::NAME, "facebook" => \WHMCS\Authentication\Remote\Providers\Facebook\FacebookSignin::NAME, "twitter" => \WHMCS\Authentication\Remote\Providers\Twitter\TwitterOauth::NAME);
                if (array_key_exists($moduleName, $appMap)) {
                    $appName = $appMap[$moduleName];
                } else {
                    $appName = $appMap[$moduleName];
                }
                $enabledProviders = (new \WHMCS\Authentication\Remote\RemoteAuth())->getEnabledProviders();
                return (bool) array_key_exists($appName, $enabledProviders);
        }
        return false;
    }
}

?>