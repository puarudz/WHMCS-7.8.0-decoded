<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect\Promotion;

class PromotionContentWrapper
{
    protected $serviceName = NULL;
    protected $productKey = NULL;
    protected $upsell = NULL;
    protected $data = NULL;
    public function __construct($serviceName, $productKey, $promoData, $isUpsell = false)
    {
        $this->validatePromoData($promoData);
        $this->serviceName = $serviceName;
        $this->productKey = $productKey;
        $this->data = $promoData;
        $this->upsell = $isUpsell;
    }
    public function validatePromoData($promoData)
    {
        if (!array_key_exists("imagePath", $promoData) || !array_key_exists("headline", $promoData) || !array_key_exists("tagline", $promoData) || !array_key_exists("features", $promoData) || !is_array($promoData["features"]) || count($promoData["features"]) == 0 || !array_key_exists("learnMoreRoute", $promoData) || !array_key_exists("cta", $promoData)) {
            throw new \WHMCS\Exception("Required promotion data missing.");
        }
    }
    public function getServiceName()
    {
        return $this->serviceName;
    }
    public function getId()
    {
        return ($this->upsell ? "upsell" : "promo") . "-" . $this->productKey;
    }
    public function canShowPromo()
    {
        return !is_null($this->data);
    }
    public function getTemplate()
    {
        return isset($this->data["template"]) ? $this->data["template"] : "";
    }
    public function getClass()
    {
        return implode(" ", array($this->getServiceName(), $this->getId()));
    }
    public function getImagePath()
    {
        $path = "";
        if (!empty($this->data["imagePath"])) {
            $path = $this->data["imagePath"];
            if (substr($path, 0, 1) !== "/" || substr($path, 0, 4) !== "http") {
                $path = \WHMCS\Utility\Environment\WebHelper::getBaseUrl() . "/" . $path;
            }
        }
        return $path;
    }
    protected function getText($key)
    {
        $languageKey = $this->getLanguageKey($key);
        $string = isset($this->data[$key]) ? $this->data[$key] : "";
        return $this->langStringOrFallback($languageKey, $string);
    }
    protected function getLanguageKey($key)
    {
        return "store." . $this->getServiceName() . "." . ($this->upsell ? "upsell" : "promo") . "." . $this->productKey . "." . $key;
    }
    protected function langStringOrFallback($key, $fallbackText)
    {
        if (\Lang::trans($key) != $key) {
            return \Lang::trans($key);
        }
        return $fallbackText;
    }
    public function getHeadline()
    {
        return $this->getText("headline");
    }
    public function getTagline()
    {
        return $this->getText("tagline");
    }
    public function getDescription()
    {
        return $this->getText("description");
    }
    public function hasFeatures()
    {
        return isset($this->data["features"]) && 0 < count($this->data["features"]);
    }
    public function getFeatures()
    {
        $features = isset($this->data["features"]) && is_array($this->data["features"]) ? $this->data["features"] : array();
        foreach ($features as $key => $feature) {
            $languageKey = $this->getLanguageKey("feature" . ($key + 1));
            $features[$key] = $this->langStringOrFallback($languageKey, $feature);
        }
        return $features;
    }
    public function getLearnMoreRoute()
    {
        return isset($this->data["learnMoreRoute"]) ? $this->data["learnMoreRoute"] : "";
    }
    public function getCta()
    {
        return $this->getText("cta");
    }
}

?>