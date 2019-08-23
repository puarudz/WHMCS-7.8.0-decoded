<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Apps\App;

class Model
{
    protected $moduleInterface = NULL;
    protected $moduleType = NULL;
    protected $moduleName = NULL;
    protected $metaData = NULL;
    protected $managementObj = NULL;
    const MISSING_TAGLINE = "No description available.";
    const MISSING_ICON_PATH = "assets/img/module/missing_icon.png";
    public static function factoryFromModule($moduleInterface, $moduleName)
    {
        $app = new self();
        $app->moduleInterface = $moduleInterface;
        $app->moduleType = $moduleInterface->getType();
        $app->moduleName = $moduleName;
        $app->metaData = \WHMCS\Apps\Meta\MetaData::factoryFromModule($moduleInterface, $moduleName);
        return $app;
    }
    public static function factoryFromRemoteFeed($metaData)
    {
        $app = new self();
        $app->metaData = \WHMCS\Apps\Meta\MetaData::factoryFromRemoteFeed($metaData);
        return $app;
    }
    public function getKey()
    {
        return \WHMCS\Module\Module::sluggify($this->getModuleType(), $this->getModuleName());
    }
    protected function getModuleInterface()
    {
        return $this->moduleInterface;
    }
    public function isActive()
    {
        if ($this->getModuleInterface() instanceof \WHMCS\Module\AbstractModule) {
            return $this->getModuleInterface()->isActive($this->moduleName);
        }
        return (new Utility\AppHelper())->isNonModuleActive($this->getModuleType(), $this->getModuleName());
    }
    public function isInstalledLocally()
    {
        return !is_null($this->getModuleInterface());
    }
    public function getActivationForms()
    {
        try {
            if ($this->getModuleInterface() instanceof \WHMCS\Module\AbstractModule) {
                return $this->getModuleInterface()->getAdminActivationForms($this->getModuleName());
            }
            return (new Utility\AppHelper())->getNonModuleActivationForms($this->getModuleType(), $this->getModuleName());
        } catch (\WHMCS\Exception\Module\NotImplemented $e) {
        }
        return array();
    }
    public function getManagementForms()
    {
        try {
            if ($this->getModuleInterface() instanceof \WHMCS\Module\AbstractModule) {
                return $this->getModuleInterface()->getAdminManagementForms($this->getModuleName());
            }
            return (new Utility\AppHelper())->getNonModuleManagementForms($this->getModuleType(), $this->getModuleName());
        } catch (\WHMCS\Exception\Module\NotImplemented $e) {
        }
        return array();
    }
    public function getModuleType()
    {
        $type = $this->moduleType;
        if (!$type) {
            $type = $this->getType();
        }
        return $type;
    }
    public function getModuleName()
    {
        $name = $this->getName();
        if (!$name) {
            $name = $this->moduleName;
        }
        return $name;
    }
    public function getType()
    {
        return str_replace("whmcs-", "", $this->metaData->getType());
    }
    public function getName()
    {
        return $this->metaData->getName();
    }
    public function hasVersion()
    {
        return $this->getVersion();
    }
    public function getVersion()
    {
        return $this->metaData->getVersion();
    }
    public function hasLogo()
    {
        return !(is_null($this->getLogoFilename()) && is_null($this->getLogoRemoteUri()));
    }
    public function getLogoFilename()
    {
        $logoFilename = $this->metaData->getLogoFilename();
        if (!is_null($logoFilename)) {
            $logoFilename = $this->moduleInterface->getModuleDirectory($this->moduleName) . DIRECTORY_SEPARATOR . $logoFilename;
        }
        if (is_null($logoFilename)) {
            $logoFilename = $this->metaData->getLogoAssetFilename();
            if (!is_null($logoFilename)) {
                $logoFilename = ROOTDIR . "/assets/img/" . $logoFilename;
            }
        }
        return $logoFilename;
    }
    public function getLogoRemoteUri()
    {
        return $this->metaData->getLogoRemoteUri();
    }
    public function getLogoContent()
    {
        $logoRemoteUri = $this->getLogoRemoteUri();
        $logoFilename = $this->getLogoFilename();
        if ($logoRemoteUri) {
            return curlCall($logoRemoteUri, "");
        }
        if (file_exists($logoFilename)) {
            $iconPath = $logoFilename;
        } else {
            $iconPath = ROOTDIR . DIRECTORY_SEPARATOR . self::MISSING_ICON_PATH;
        }
        return file_get_contents($iconPath);
    }
    public function getDisplayName()
    {
        $name = $this->metaData->getDisplayName();
        if (is_null($name)) {
            $name = titleCase(str_replace("_", " ", $this->moduleName));
        }
        $name = str_replace("[tm]", "&trade;", $name);
        return $name;
    }
    public function getCategory()
    {
        return $this->metaData->getCategory();
    }
    public function getCategoryModel()
    {
        $categorySlug = $this->getCategory();
        return (new \WHMCS\Apps\Category\Collection())->getCategoryBySlug($categorySlug);
    }
    public function getCategoryDisplayName()
    {
        $model = $this->getCategoryModel();
        return $model ? $model->getDisplayName() : "-";
    }
    public function getTagline()
    {
        $tagline = $this->metaData->getTagline();
        if (is_null($tagline)) {
            $tagline = self::MISSING_TAGLINE;
        }
        return $tagline;
    }
    public function getLongDescription()
    {
        return \WHMCS\View\Markup\Markdown\Markdown::defaultTransform(\WHMCS\Input\Sanitize::makeSafeForOutput($this->metaData->getLongDescription()));
    }
    public function hasFeatures()
    {
        $features = (array) $this->metaData->getFeatures();
        return 0 < count($features);
    }
    public function getFeatures()
    {
        $features = (array) $this->metaData->getFeatures();
        foreach ($features as $key => $feature) {
            $features[$key] = $feature;
        }
        return $features;
    }
    public function requiresPurchase()
    {
        return $this->getPurchaseUrl();
    }
    public function hasPurchaseFreeTrial()
    {
        return $this->getPurchaseFreeTrialDays();
    }
    public function getPurchaseFreeTrialDays()
    {
        return $this->metaData->getPurchaseFreeTrialDays();
    }
    public function getPurchasePrice()
    {
        return $this->metaData->getPurchasePrice();
    }
    public function getPurchaseCurrency()
    {
        return $this->metaData->getPurchaseCurrency();
    }
    public function getPurchaseCurrencySymbol()
    {
        switch ($this->getPurchaseCurrency()) {
            case "USD":
                return "\$";
            case "GBP":
                return "£";
        }
        return "";
    }
    public function getPurchaseTerm()
    {
        return $this->metaData->getPurchaseTerm();
    }
    public function getPurchaseUrl()
    {
        return $this->metaData->getPurchaseUrl();
    }
    public function requiresLicense()
    {
        if ($this->requiresPurchase() && $this->getModuleInterface()) {
            $this->getModuleInterface()->load($this->moduleName);
            if ($this->getModuleInterface()->getMetaDataValue("addonLicenseRequired")) {
                return true;
            }
        }
        return false;
    }
    public function isLicensed()
    {
        if ($this->requiresLicense()) {
            $licensing = \DI::make("license");
            return $licensing->isActiveAddon($this->getModuleInterface()->getMetaDataValue("addonLicenseName"));
        }
        return false;
    }
    public function isFeatured()
    {
        $allFeaturedAppKeys = (new \WHMCS\Apps\Category\Collection())->getAllFeaturedKeys();
        return in_array($this->getKey(), $allFeaturedAppKeys);
    }
    public function isPopular()
    {
        return (bool) $this->metaData->isPopular();
    }
    public function isUpdated()
    {
        return (bool) $this->metaData->isUpdated();
    }
    protected function addGaTracking($url)
    {
        if ($url) {
            $parts = parse_url($url);
            $host = array_key_exists("host", $parts) ? $parts["host"] : "";
            if (strpos($host, ".whmcs.com") !== false) {
                $gaString = "utm_source=in-product&utm_medium=apps";
                if (strpos($url, "?") !== false) {
                    $url .= "&" . $gaString;
                } else {
                    $url .= "?" . $gaString;
                }
            }
        }
        return $url;
    }
    public function getAuthors()
    {
        $authors = $this->metaData->getAuthors();
        $authorsToReturn = array();
        foreach ($authors as $author) {
            $url = \WHMCS\Input\Sanitize::makeSafeForOutput($this->addGaTracking($author["homepage"]));
            $authorsToReturn[] = "<a href=\"" . $url . "\" target=\"_blank\">" . \WHMCS\Input\Sanitize::makeSafeForOutput($author["name"]) . "</a>";
        }
        if (empty($authorsToReturn)) {
            $authorsToReturn[] = "<em>Unknown</em>";
        }
        return $authorsToReturn;
    }
    public function getHomepageUrl()
    {
        return $this->addGaTracking($this->metaData->getHomepageUrl());
    }
    public function getSupportEmail()
    {
        return $this->metaData->getSupportEmail();
    }
    public function getSupportUrl()
    {
        return $this->addGaTracking($this->metaData->getSupportUrl());
    }
    public function getDocumentationUrl()
    {
        return $this->addGaTracking($this->metaData->getDocumentationUrl());
    }
    public function getLearnMoreUrl()
    {
        return $this->addGaTracking($this->metaData->getLearnMoreUrl());
    }
    public function getMarketplaceUrl()
    {
        return $this->addGaTracking($this->metaData->getMarketplaceUrl());
    }
    public function getKeywords()
    {
        $keywords = $this->metaData->getKeywords();
        return is_array($keywords) ? $keywords : array();
    }
    public function isVisible()
    {
        return !(bool) $this->metaData->isHidden();
    }
    public function isHidden()
    {
        return (bool) $this->metaData->isHidden();
    }
}

?>