<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Apps\Meta;

class MetaData
{
    protected $localMetaData = NULL;
    protected $remoteMetaData = NULL;
    public static function factoryFromModule($moduleInterface, $moduleName)
    {
        $metaData = new self();
        try {
            $metaData->localMetaData = Sources\LocalFile::build($moduleInterface->getAppMetaDataFilePath($moduleName));
        } catch (\Exception $e) {
        }
        try {
            $metaData->remoteMetaData = (new Sources\RemoteFeed())->getAppByModuleName($moduleInterface->getType(), $moduleName);
        } catch (\Exception $e) {
        }
        return $metaData;
    }
    public static function factoryFromRemoteFeed($feed)
    {
        $metaData = new self();
        $metaData->remoteMetaData = (new Sources\RemoteFeed())->parseJson($feed);
        return $metaData;
    }
    protected function get($method, $allowLocal = true)
    {
        $result = null;
        if (method_exists($this->remoteMetaData, $method)) {
            $result = $this->remoteMetaData->{$method}();
        }
        if (is_null($result) && $allowLocal && method_exists($this->localMetaData, $method)) {
            $result = $this->localMetaData->{$method}();
        }
        return $result;
    }
    public function getType()
    {
        return $this->get("getType");
    }
    public function getName()
    {
        return $this->get("getName");
    }
    public function getVersion()
    {
        return $this->get("getVersion");
    }
    public function getLicense()
    {
        return $this->get("getLicense");
    }
    public function getCategory()
    {
        return $this->get("getCategory");
    }
    public function getDisplayName()
    {
        return $this->get("getDisplayName");
    }
    public function getTagline()
    {
        return $this->get("getTagline");
    }
    public function getShortDescription()
    {
        return $this->get("getShortDescription");
    }
    public function getLongDescription()
    {
        return $this->get("getLongDescription");
    }
    public function getFeatures()
    {
        return $this->get("getFeatures");
    }
    public function getLogoFilename()
    {
        return $this->get("getLogoFilename");
    }
    public function getLogoBase64()
    {
        return $this->get("getLogoBase64");
    }
    public function getLogoAssetFilename()
    {
        return $this->get("getLogoAssetFilename", false);
    }
    public function getLogoRemoteUri()
    {
        return $this->get("getLogoRemoteUri", false);
    }
    public function getMarketplaceUrl()
    {
        return $this->get("getMarketplaceUrl");
    }
    public function getAuthors()
    {
        return $this->get("getAuthors");
    }
    public function getHomepageUrl()
    {
        return $this->get("getHomepageUrl");
    }
    public function getLearnMoreUrl()
    {
        return $this->get("getLearnMoreUrl");
    }
    public function getSupportEmail()
    {
        return $this->get("getSupportEmail");
    }
    public function getSupportUrl()
    {
        return $this->get("getSupportUrl");
    }
    public function getDocumentationUrl()
    {
        return $this->get("getDocumentationUrl");
    }
    public function getPurchaseFreeTrialDays()
    {
        return $this->get("getPurchaseFreeTrialDays", false);
    }
    public function getPurchasePrice()
    {
        return $this->get("getPurchasePrice", false);
    }
    public function getPurchaseCurrency()
    {
        return $this->get("getPurchaseCurrency", false);
    }
    public function getPurchaseTerm()
    {
        return $this->get("getPurchaseTerm", false);
    }
    public function getPurchaseUrl()
    {
        return $this->get("getPurchaseUrl", false);
    }
    public function isFeatured()
    {
        return (bool) $this->get("isFeatured", false);
    }
    public function isPopular()
    {
        return (bool) $this->get("isPopular", false);
    }
    public function isUpdated()
    {
        return (bool) $this->get("isUpdated", false);
    }
    public function getKeywords()
    {
        return $this->get("getKeywords", false);
    }
    public function getWeighting()
    {
        return (int) $this->get("getWeighting", false);
    }
    public function isHidden()
    {
        return (bool) $this->get("isHidden", false);
    }
}

?>