<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect\Promotion\Service;

class Weebly extends AbstractService
{
    protected $name = "weebly";
    protected $friendlyName = "Weebly";
    protected $primaryIcon = "assets/img/marketconnect/weebly/logo.png";
    protected $primaryLandingPageRouteName = "store-websitebuilder-index";
    protected $promoteToNewClients = true;
    protected $productKeys = array("weebly_lite", "weebly_starter", "weebly_pro", "weebly_business");
    protected $qualifyingProductTypes = NULL;
    protected $upsells = array("weebly_lite" => array("weebly_starter", "weebly_pro", "weebly_business"), "weebly_starter" => array("weebly_pro", "weebly_business"), "weebly_pro" => array("weebly_business"));
    protected $loginPanel = array("label" => "Build Your Website", "icon" => "fa-desktop", "image" => "assets/img/marketconnect/weebly/dragdropeditor.png", "color" => "blue");
    protected $upsellPromoContent = array("weebly_starter" => array("imagePath" => "assets/img/marketconnect/weebly/logo.png", "headline" => "Upgrade to Weebly Starter", "tagline" => "To unlock the full power of Weebly", "features" => array("Ideal for personal websites and blogs", "Create Unlimited Pages", "No Weebly Ads", "Basic eCommerce Functionality"), "learnMoreRoute" => "store-websitebuilder-index", "cta" => "Upgrade to"), "weebly_pro" => array("imagePath" => "assets/img/marketconnect/weebly/logo.png", "headline" => "Upgrade to Weebly Pro", "tagline" => "For even more power and flexibility", "features" => array("Up to 25 eCommerce Products", "Rich HD Video & Audio Content", "Password Protected Pages", "Powerful Site Search"), "learnMoreRoute" => "store-websitebuilder-index", "cta" => "Upgrade to"), "weebly_business" => array("imagePath" => "assets/img/marketconnect/weebly/logo.png", "headline" => "Upgrade to Weebly Business", "tagline" => "Ideal for eCommerce and SMBs", "features" => array("Sell unlimited eCommerce Products", "More powerful eCommerce features", "Coupons & Tax Calculation", "0% Weebly Transaction fees"), "learnMoreRoute" => "store-websitebuilder-index", "cta" => "Upgrade to"));
    protected $idealFor = array("weebly_lite" => "Starting Out", "weebly_starter" => "Personal Use", "weebly_pro" => "Groups + Organizations", "weebly_business" => "Businesses + Stores");
    protected $siteFeatures = array("weebly_lite" => array("Drag & Drop Builder", "1 Page"), "weebly_starter" => array("ddBuilder" => "Drag & Drop Builder", "pages" => "Unlimited Pages", "noAds" => "No Weebly Ads"), "weebly_pro" => array("ddBuilder" => "Drag & Drop Builder", "pages" => "Unlimited Pages", "noAds" => "No Weebly Ads", "search" => "Site Search", "passwords" => "Password Protection", "backgrounds" => "Video Backgrounds", "hdVideo" => "HD Video & Audio", "memberCount" => "Up to 100 Members"), "weebly_business" => array("ddBuilder" => "Drag & Drop Builder", "pages" => "Unlimited Pages", "noAds" => "No Weebly Ads", "search" => "Site Search", "passwords" => "Password Protection", "backgrounds" => "Video Backgrounds", "hdVideo" => "HD Video & Audio", "memberCount" => "Up to 100 Members", "registration" => "Membership Registration"));
    protected $ecommerceFeatures = array("weebly_starter" => array("3pcFee" => "3% Weebly Transaction Fees", "tenProducts" => "Up to 10 Products", "checkoutOnWeebly" => "Checkout on Weebly.com"), "weebly_pro" => array("3pcFee" => "3% Weebly Transaction Fees", "twentyFiveProducts" => "Up to 25 Products", "checkoutOnWeebly" => "Checkout on Weebly.com"), "weebly_business" => array("0pcFee" => "0% Weebly Transaction Fees", "unlimitedProducts" => "Unlimited Products", "checkoutDomain" => "Checkout on your domain", "inventory" => "Inventory Management", "coupons" => "Coupons", "tax" => "Tax Calculator"));
    protected $defaultPromotionalContent = array("imagePath" => "assets/img/marketconnect/icons/weebly.png", "headline" => "Drag & drop site builder", "tagline" => "Powered by Weebly&trade;", "features" => array("Powerful drag and drop website builder", "No coding knowledge needed", "Over 100 pre-made themes", "Drag and drop editor"), "learnMoreRoute" => "store-websitebuilder-index", "cta" => "Add Weebly");
    protected $recommendedUpgradePaths = array("weebly_lite" => "weebly_starter", "weebly_starter" => "weebly_pro", "weebly_pro" => "weebly_business");
    public function getIdealFor($key)
    {
        return isset($this->idealFor[$key]) ? $this->idealFor[$key] : "";
    }
    public function getSiteFeatures($key)
    {
        return isset($this->siteFeatures[$key]) ? $this->siteFeatures[$key] : array();
    }
    public function getEcommerceFeatures($key)
    {
        return isset($this->ecommerceFeatures[$key]) ? $this->ecommerceFeatures[$key] : array();
    }
    public function getFeaturesForUpgrade($key)
    {
        $features = array();
        foreach ($this->getSiteFeatures($key) as $feature) {
            $features[$feature] = true;
        }
        foreach ($this->getEcommerceFeatures($key) as $feature) {
            $features[$feature] = true;
        }
        return $features;
    }
}

?>