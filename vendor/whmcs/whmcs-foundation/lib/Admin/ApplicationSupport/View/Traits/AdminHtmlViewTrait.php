<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\ApplicationSupport\View\Traits;

trait AdminHtmlViewTrait
{
    use AdminAreaHookTrait;
    use AdminUserContextTrait;
    use BodyContentTrait;
    use JavascriptTrait;
    use NotificationTrait;
    use PageContextTrait;
    use SidebarTrait;
    use TemplatePageTrait;
    public function prepareVariableContent()
    {
        if ($this instanceof NotificationTrait) {
            $this->addJavascript($this->getNotificationJavascript());
            $this->addJquery($this->getNotificationJquery());
        }
        $this->getTemplateVariables()->add($this->getAdminTemplateVariables());
        $this->getTemplateVariables()->add($this->standardTemplateVariables());
        return $this;
    }
    protected function getNonHookTemplateVariables()
    {
        return array_merge(array("_ADMINLANG" => $this->getAdminLanguageVariables()), $this->getSidebarVariables());
    }
    public function getTemplateDirectory()
    {
        if (!$this->templateDirectory) {
            $admin = $this->getAdminUser();
            $this->templateDirectory = $admin->templateThemeName;
        }
        return $this->templateDirectory;
    }
    public function standardTemplateVariables()
    {
        $assetHelper = \DI::make("asset");
        $standardVariables = array("charset" => $this->getCharset(), "filename" => isset($this->filename) ? $this->filename : "", "template" => $this->getTemplateDirectory(), "pagetemplate" => $this->getTemplateName(), "pagetitle" => $this->getTitle(), "helplink" => str_replace(" ", "_", $this->getHelpLink()), "pageicon" => $this->getFavicon(), "csrfToken" => $this->getCsrfToken(), "versionHash" => $this->getVersionHash(), "datepickerformat" => $this->getDateFormat(), "WEB_ROOT" => $assetHelper->getWebRoot(), "BASE_PATH_CSS" => $assetHelper->getCssPath(), "BASE_PATH_JS" => $assetHelper->getJsPath(), "BASE_PATH_FONTS" => $assetHelper->getFontsPath(), "BASE_PATH_IMG" => $assetHelper->getImgPath(), "whmcsBaseUrl" => \WHMCS\Utility\Environment\WebHelper::getBaseUrl(), "jsquerycode" => "", "jscode" => "", "topBarNotification" => "", "sidebar" => "", "minsidebar" => "", "menuticketstatuses" => \WHMCS\Database\Capsule::table("tblticketstatuses")->orderBy("sortorder")->pluck("title"));
        if (traitOf($this, "WHMCS\\Admin\\ApplicationSupport\\View\\Traits\\VersionTrait")) {
            $standardVariables["version"] = $this->getVersion();
            $standardVariables["installedFeatureVersion"] = $this->getFeatureVersion();
        }
        if (traitOf($this, "WHMCS\\Admin\\ApplicationSupport\\View\\Traits\\NotificationTrait")) {
            $standardVariables["clientLimitNotification"] = $this->getClientLimitNotification();
        }
        if (traitOf($this, "WHMCS\\Admin\\ApplicationSupport\\View\\Traits\\JavascriptTrait")) {
            $standardVariables["jquerycode"] = $this->getFormattedJquery();
            $standardVariables["jscode"] = $this->getFormattedJavascript();
        }
        if (traitOf($this, "WHMCS\\Admin\\ApplicationSupport\\View\\Traits\\SidebarTrait")) {
            $standardVariables["sidebar"] = $this->getSidebarName();
            $standardVariables["minsidebar"] = $this->isSidebarMinimized();
        }
        $locales = \AdminLang::getLocales();
        $standardVariables["locales"] = $locales;
        $activeLocale = null;
        foreach ($locales as $locale) {
            if ($locale["language"] == \AdminLang::getName()) {
                $activeLocale = $locale;
                break;
            }
        }
        $carbonObject = new \WHMCS\Carbon();
        $carbonObject->setLocale($activeLocale["languageCode"]);
        $standardVariables["carbon"] = $carbonObject;
        return $standardVariables;
    }
}

?>