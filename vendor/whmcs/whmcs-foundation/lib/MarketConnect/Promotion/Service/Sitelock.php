<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect\Promotion\Service;

class Sitelock extends AbstractService
{
    protected $name = "sitelock";
    protected $friendlyName = "Sitelock";
    protected $primaryIcon = "assets/img/marketconnect/sitelock/logo.png";
    protected $primaryLandingPageRouteName = "store-sitelock-index";
    protected $productKeys = array("sitelock_lite", "sitelock_find", "sitelock_fix", "sitelock_defend", "sitelock_emergency");
    protected $qualifyingProductTypes = NULL;
    protected $loginPanel = array("label" => "Manage Your Security", "icon" => "fa-bug", "image" => "assets/img/marketconnect/sitelock/logo-sml.png", "color" => "pomegranate");
    protected $settings = array(array("name" => "include-sitelock-lite-by-default", "label" => "Include SiteLock Lite by Default", "description" => "Automatically pre-select SiteLock Lite by default for new orders of all applicable products", "default" => true));
    protected $planFeatures = array("sitelock_lite" => array("Daily Malware Scanning" => true, "Number of Pages" => 5, "Daily Blacklist Monitoring" => true, "SiteLock Risk Score" => true), "sitelock_find" => array("Daily Malware Scanning" => true, "Number of Pages" => 25, "Daily Blacklist Monitoring" => true, "SiteLock Risk Score" => true, "Website Application Scan" => "One Time", "SQL Injection Scan" => "One Time", "Cross Site (XSS) Scan" => "One Time", "Sitelock&trade; Trust Seal" => true), "sitelock_fix" => array("Daily Malware Scanning" => true, "Number of Pages" => 500, "Daily Blacklist Monitoring" => true, "SiteLock Risk Score" => true, "Website Application Scan" => "Daily", "SQL Injection Scan" => "Daily", "Cross Site (XSS) Scan" => "Daily", "Sitelock&trade; Trust Seal" => true, "Daily SMART Scans" => true, "Automatic Malware Removal" => true, "TrueShield Protection" => true, "Wordpress Scan" => true, "Spam Blacklist Monitoring" => true), "sitelock_defend" => array("Daily Malware Scanning" => true, "Number of Pages" => 500, "Daily Blacklist Monitoring" => true, "SiteLock Risk Score" => true, "Website Application Scan" => "Daily", "SQL Injection Scan" => "Daily", "Cross Site (XSS) Scan" => "Daily", "Sitelock&trade; Trust Seal" => true, "Daily SMART Scans" => true, "Automatic Malware Removal" => true, "TrueShield Protection" => true, "Wordpress Scan" => true, "Spam Blacklist Monitoring" => true, "Web Application Firewall" => true, "Global CDN" => true, "Content Acceleration" => true));
    protected $recommendedUpgradePaths = array("sitelock_lite" => "sitelock_find", "sitelock_find" => "sitelock_fix", "sitelock_fix" => "sitelock_defend");
    protected $upsells = array("sitelock_lite" => array("sitelock_find", "sitelock_fix", "sitelock_defend"), "sitelock_find" => array("sitelock_fix", "sitelock_defend"), "sitelock_fix" => array("sitelock_defend"));
    protected $upsellPromoContent = array("sitelock_find" => array("imagePath" => "assets/img/marketconnect/sitelock/logo.png", "headline" => "Upgrade to SiteLock Find", "tagline" => "Stop more malware and vulnerabilities.", "features" => array("Scans up to 25 Pages", "SQL Injection & Cross-Site (XSS) Scan", "Website Application Scan", "WordPress Scan"), "learnMoreRoute" => "store-sitelock-index", "cta" => "Upgrade to"), "sitelock_fix" => array("imagePath" => "assets/img/marketconnect/sitelock/logo.png", "headline" => "Upgrade to SiteLock Fix", "tagline" => "Removes malicious code automatically.", "features" => array("Scans up to 500 Pages", "Daily Vulnerability Scans", "Automatic Malware Removal", "Daily SMART Scans"), "learnMoreRoute" => "store-sitelock-index", "cta" => "Upgrade to"), "sitelock_defend" => array("imagePath" => "assets/img/marketconnect/sitelock/logo.png", "headline" => "Upgrade to SiteLock Defend", "tagline" => "Improves website speed with CDN", "features" => array("Find, Fix, and prevent threats", "Global CDN for increased performance", "Scans up to 500 Pages", "Automatic Malware Removal"), "learnMoreRoute" => "store-sitelock-index", "cta" => "Upgrade to"));
    protected $defaultPromotionalContent = array("imagePath" => "assets/img/marketconnect/sitelock/logo.png", "headline" => "Secure your website with SiteLock", "tagline" => "Protect yourself against hackers and malware with SiteLock's industry leading protection.", "features" => array("Daily Malware Scanning", "Daily Blacklist Monitoring", "SiteLock Risk Score", "SiteLock Trust Seal"), "learnMoreRoute" => "store-sitelock-index", "cta" => "Buy", "ctaRoute" => "store-sitelock-index");
    protected $promotionalContent = array("sitelock_lite" => array("imagePath" => "assets/img/marketconnect/icons/sitelock.png", "headline" => "Daily Website Scanning", "tagline" => "Security protection by SiteLock&trade;", "features" => array("Try completely free up to 5 pages", "Daily Malware Scanning", "Daily Blacklist Monitoring", "Sitelock Trust Seal"), "learnMoreRoute" => "store-sitelock-index", "cta" => "Try SiteLock", "ctaRoute" => "store-sitelock-index"));
    public function getPlanFeatures($key)
    {
        return isset($this->planFeatures[$key]) ? $this->planFeatures[$key] : array();
    }
    public function getFeaturesForUpgrade($key)
    {
        if ($key == "sitelock_emergency") {
            return null;
        }
        return $this->getPlanFeatures($key);
    }
    protected function getAddonToSelectByDefault()
    {
        if ($this->getModel()->setting("general.include-sitelock-lite-by-default")) {
            $litePlan = \WHMCS\Config\Module\ModuleConfiguration::with("productAddon")->where("entity_type", "addon")->where("setting_name", "configoption1")->where("value", "sitelock_lite")->get()->where("productAddon.module", "marketconnect")->first();
            return $litePlan->productAddon->id;
        }
        return null;
    }
    protected function getExcludedFromNewPurchaseAddonIds()
    {
        $emergencyPlan = \WHMCS\Config\Module\ModuleConfiguration::with("productAddon")->where("entity_type", "addon")->where("setting_name", "configoption1")->where("value", "sitelock_emergency")->get()->where("productAddon.module", "marketconnect")->first();
        return array($emergencyPlan->productAddon->id);
    }
}

?>