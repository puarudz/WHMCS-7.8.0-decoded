<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect\Promotion\Service;

class CodeGuard extends AbstractService
{
    protected $name = "codeguard";
    protected $friendlyName = "CodeGuard";
    protected $primaryIcon = "assets/img/marketconnect/codeguard/logo-sml.png";
    protected $primaryLandingPageRouteName = "store-codeguard-index";
    protected $productKeys = array("codeguard_lite", "codeguard_personal", "codeguard_professional", "codeguard_business", "codeguard_businessplus", "codeguard_power", "codeguard_powerplus");
    protected $qualifyingProductTypes = NULL;
    protected $loginPanel = array("label" => "Manage Backups", "icon" => "fa-hdd", "image" => "assets/img/marketconnect/codeguard/hero-image-a.png", "color" => "lime");
    protected $defaultPromotionalContent = array("imagePath" => "assets/img/marketconnect/codeguard/logo-sml.png", "headline" => "Daily Website Backup", "tagline" => "Powered by CodeGuard&trade;", "features" => array("Automatic daily website backup", "Automatic one-click restores", "Malware monitoring & alerting", "WordPress update automation"), "learnMoreRoute" => "store-codeguard-index", "cta" => "Add CodeGuard");
    protected $upsells = array("codeguard_lite" => array("codeguard_personal"), "codeguard_personal" => array("codeguard_professional"), "codeguard_professional" => array("codeguard_business"), "codeguard_business" => array("codeguard_businessplus"), "codeguard_businessplus" => array("codeguard_power"), "codeguard_power" => array("codeguard_powerplus"));
    protected $recommendedUpgradePaths = array("codeguard_lite" => "codeguard_personal", "codeguard_personal" => "codeguard_professional", "codeguard_professional" => "codeguard_business", "codeguard_business" => "codeguard_businessplus", "codeguard_businessplus" => "codeguard_power", "codeguard_power" => "codeguard_powerplus");
    public function __construct()
    {
        $products = \WHMCS\Product\Product::codeguard()->pluck("name", "configoption1");
        foreach ($this->upsells as $upsell) {
            $this->upsellPromoContent[$upsell[0]] = array("imagePath" => $this->primaryIcon, "headline" => "Add More Storage", "tagline" => "Increase backup space to " . self::getDiskSpaceFromName($products[$upsell[0]]), "features" => array("Store more website data", "Retain more backup history"), "learnMoreRoute" => $this->primaryLandingPageRouteName, "cta" => "Upgrade to");
        }
    }
    public static function getDiskSpaceFromName($name)
    {
        preg_match("/[\\d]+GB/i", $name, $diskSpace);
        if (isset($diskSpace[0])) {
            return $diskSpace[0];
        }
        return $name;
    }
    public function getFeaturesForUpgrade($key)
    {
        $standardFeatures = array("Automated Daily Backups" => true, "One-Click Restores" => true, "WordPress Plugin" => true, "WordPress Auto Updates" => true, "File Change Monitoring" => true, "Malware Detection" => true);
        $features = array();
        switch ($key) {
            case "codeguard_lite":
                $features = array("Disk Space" => "1GB");
                break;
            case "codeguard_personal":
                $features = array("Disk Space" => "5GB");
                break;
            case "codeguard_professional":
                $features = array("Disk Space" => "10GB");
                break;
            case "codeguard_business":
                $features = array("Disk Space" => "25GB");
                break;
            case "codeguard_businessplus":
                $features = array("Disk Space" => "50GB");
                break;
            case "codeguard_power":
                $features = array("Disk Space" => "100GB");
                break;
            case "codeguard_powerplus":
                $features = array("Disk Space" => "200GB");
                break;
        }
        return array_merge($features, $standardFeatures);
    }
}

?>