<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Setup\Authentication;

class TwoFactorAuthController
{
    public function index(\WHMCS\Http\Message\ServerRequest $request)
    {
        $aInt = new \WHMCS\Admin("Configure Two-Factor Authentication");
        $aInt->setResponseType(\WHMCS\Admin::RESPONSE_HTML_MESSAGE);
        $aInt->title = \AdminLang::trans("twofa.title");
        $aInt->sidebar = "config";
        $aInt->icon = "security";
        $aInt->helplink = "Two-Factor Authentication";
        $aInt->requireAuthConfirmation();
        $twofa = new \WHMCS\TwoFactorAuthentication();
        $securityInterface = new \WHMCS\Module\Security();
        $modules = $securityInterface->getList();
        if (!$modules) {
            $aInt->gracefulExit("Security Module Folder Not Found. Please try reuploading all WHMCS related files.");
        }
        $templateModules = array();
        foreach ($modules as $module) {
            $securityInterface->load($module);
            $configarray = $securityInterface->call("config");
            $templateModules[$module] = array("name" => $module, "active" => $twofa->isModuleEnabled($module), "configData" => $twofa->getModuleSettings($module), "configArray" => $configarray, "friendlyName" => isset($configarray["FriendlyName"]["Value"]) ? $configarray["FriendlyName"]["Value"] : $module, "description" => isset($configarray["ShortDescription"]["Value"]) ? $configarray["ShortDescription"]["Value"] : "");
        }
        $moduleToConfigure = $request->get("module");
        if (!array_key_exists($moduleToConfigure, $templateModules)) {
            $moduleToConfigure = "";
        }
        $output = view("admin.setup.two-factor.index", array("modules" => $templateModules, "moduleToConfigure" => $moduleToConfigure, "globalSettings" => array("forceClients" => $twofa->isForcedClients(), "forceAdmins" => $twofa->isForcedAdmins()), "saveSuccess" => $request->get("saved")));
        $aInt->setBodyContent($output);
        return $aInt->display();
    }
    public function status(\WHMCS\Http\Message\ServerRequest $request)
    {
        $twofa = new \WHMCS\TwoFactorAuthentication();
        $securityInterface = new \WHMCS\Module\Security();
        $modules = $securityInterface->getList();
        $responseData = array();
        foreach ($securityInterface->getList() as $module) {
            $responseData[$module] = $twofa->isModuleEnabled($module);
        }
        return new \WHMCS\Http\Message\JsonResponse($responseData);
    }
    public function saveSettings(\WHMCS\Http\Message\ServerRequest $request)
    {
        $forceClients = $request->request()->get("forceclient");
        $forceAdmins = $request->request()->get("forceadmin");
        $twofa = (new \WHMCS\TwoFactorAuthentication())->setForcedClients($forceClients)->setForcedAdmins($forceAdmins)->save();
        return new \Zend\Diactoros\Response\RedirectResponse(routePathWithQuery("admin-setup-auth-two-factor-index", array(), array("saved" => 1)));
    }
    public function configureModule(\WHMCS\Http\Message\ServerRequest $request)
    {
        $module = $request->attributes()->get("module");
        $securityInterface = new \WHMCS\Module\Security();
        if (!$securityInterface->load($module)) {
            throw new \WHMCS\Exception("Invalid module name.");
        }
        $configuration = $securityInterface->call("config");
        $twofa = new \WHMCS\TwoFactorAuthentication();
        require ROOTDIR . "/includes/modulefunctions.php";
        $settingFields = array();
        foreach ($configuration as $fieldName => $values) {
            if ($values["Type"] != "System") {
                if (!isset($values["FriendlyName"])) {
                    $values["FriendlyName"] = $fieldName;
                }
                $values["Name"] = "settings[" . $fieldName . "]";
                $value = $twofa->getModuleSetting($module, $fieldName);
                if ($values["Type"] == "password") {
                    $values["Value"] = htmlspecialchars(decrypt($value));
                } else {
                    $values["Value"] = htmlspecialchars($value);
                }
                $settingFields[$values["FriendlyName"]] = moduleConfigFieldOutput($values);
            }
        }
        $responseData = array("body" => view("admin.setup.two-factor.configure", array("module" => $module, "configuration" => $configuration, "settingFields" => $settingFields, "isEnabledForClients" => $twofa->isModuleEnabledForClients($module), "isEnabledForAdmins" => $twofa->isModuleEnabledForAdmins($module))));
        return new \WHMCS\Http\Message\JsonResponse($responseData);
    }
    public function saveModule(\WHMCS\Http\Message\ServerRequest $request)
    {
        $module = $request->attributes()->get("module");
        $inputSettings = $request->request()->get("settings");
        $securityInterface = new \WHMCS\Module\Security();
        if (!$securityInterface->load($module)) {
            throw new \WHMCS\Exception("Invalid module name.");
        }
        $twofa = new \WHMCS\TwoFactorAuthentication();
        $configuration = $securityInterface->call("config");
        foreach ($configuration as $fieldName => $values) {
            if ($values["Type"] != "System") {
                $value = $inputSettings[$fieldName];
                if ($values["Type"] == "password") {
                    $value = encrypt($value);
                }
                $twofa->setModuleSetting($module, $fieldName, $value);
            }
        }
        $moduleClientEnabled = $request->request()->get("clientenabled");
        $moduleAdminEnabled = $request->request()->get("adminenabled");
        $twofa->setModuleClientEnablementStatus($module, $moduleClientEnabled)->setModuleAdminEnablementStatus($module, $moduleAdminEnabled)->save();
        return new \WHMCS\Http\Message\JsonResponse(array("dismiss" => true, "successMsgTitle" => "", "successMsg" => \AdminLang::trans("global.changesuccess")));
    }
}

?>