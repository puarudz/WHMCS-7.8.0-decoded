<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect;

class Promotion
{
    const SERVICES = array("symantec" => array("vendorName" => "DigiCert", "vendorSystemName" => "symantec", "serviceTitle" => "SSL Certificates", "tagLine" => "The World's #1 Security Solution", "description" => "Sell SSL's from DigiCert, the world's premier high-assurance digital certificate provider.", "supportsSso" => false, "serviceList" => false), "weebly" => array("vendorName" => "Weebly", "vendorSystemName" => "weebly", "serviceTitle" => "Website Builder", "tagLine" => "The World's Leading Website Builder", "description" => "Make it easier for customers to create a website with Weebly's drag and drop site builder.", "supportsSso" => true, "serviceList" => false, "features" => array("builder", "ecommerce", "forms", "templates", "gallery", "blogging", "video", "seo")), "codeguard" => array("vendorName" => "CodeGuard", "vendorSystemName" => "codeguard", "serviceTitle" => "Website Backup", "tagLine" => "Backup solutions", "description" => "Automated website backup with one-click restores, malware detection and WordPress management.", "supportsSso" => false, "serviceList" => true), "sitelock" => array("vendorName" => "SiteLock", "vendorSystemName" => "sitelock", "serviceTitle" => "Website Security", "tagLine" => "Cloud-based Website Protection & Malware Removal", "description" => "Security and malware scanning, detection and removal plus WAF and CDN services.", "supportsSso" => false, "serviceList" => true), "spamexperts" => array("vendorName" => "SpamExperts", "vendorSystemName" => "spamexperts", "serviceTitle" => "Email Security", "tagLine" => "Business Class Email Filtering & Compliance", "description" => "Offer professional email services including Anti-Spam, Virus Protection and Email Archiving.", "supportsSso" => false, "serviceList" => true));
    const DEFAULT_SETTINGS = array(array("name" => "auto-assign-addons", "label" => "Auto Assign to Addons", "description" => "Automatically assign these products as add-on options to all applicable products", "default" => true), array("name" => "activate-landing-page", "label" => "Landing Page Links", "description" => "Activate navigation link within the client area navigation bar", "default" => true));
    public static function initHooks()
    {
        $hooks = array("ClientAreaSidebars" => "clientAreaSidebars");
        foreach ($hooks as $hook => $function) {
            add_hook($hook, -1, function ($var = NULL) use($function) {
                $response = array();
                foreach (Service::active()->get() as $service) {
                    $response[] = $service->factoryPromoter()->{$function}(func_get_args());
                }
                return implode($response);
            });
        }
        add_hook("ClientAreaProductDetailsOutput", -1, function ($vars) {
            $serviceModel = $vars["service"];
            $logins = array();
            foreach (Service::active()->get() as $service) {
                $loginPanel = $service->factoryPromoter()->productDetailsLogin($serviceModel);
                if ($loginPanel instanceof Promotion\LoginPanel) {
                    $logins[] = $loginPanel->toHtml();
                }
            }
            return implode($logins);
        });
        add_hook("ClientAreaProductDetailsOutput", -2, function ($vars) {
            $serviceModel = $vars["service"];
            $promotions = array();
            foreach (Service::active()->get() as $service) {
                $response = $service->factoryPromoter()->productDetailsOutput($serviceModel);
                if (!empty($response)) {
                    $promotions[] = $response;
                }
            }
            if (0 < count($promotions)) {
                return Promotion::renderPromotionsCarousel($promotions);
            }
        });
        add_hook("ClientAreaHomepagePanels", -1, function ($homePagePanels) {
            $promotions = array();
            foreach (Service::active()->get() as $service) {
                $loginPanel = null;
                $promoter = $service->factoryPromoter();
                if ($promoter->clientHasActiveServices() && $promoter->supportsLogin()) {
                    $loginPanel = $promoter->getLoginPanel();
                }
                if (!is_null($loginPanel)) {
                    $homePagePanels->addChild($loginPanel);
                }
            }
        });
        add_hook("ClientAreaHomepage", -1, function () {
            $promotions = array();
            foreach (Service::active()->get() as $service) {
                $response = $service->factoryPromoter()->clientAreaHomeOutput();
                if (!empty($response)) {
                    $promotions[] = $response;
                }
            }
            if (0 < count($promotions)) {
                return Promotion::renderPromotionsCarousel($promotions);
            }
        });
        add_hook("ShoppingCartViewCartOutput", -1, function () {
            $promotions = Promotion::cartViewPromotion();
            if (0 < count($promotions)) {
                return "<h3 style=\"margin:20px 0;\">" . \Lang::trans("store.recommendedForYou") . "</h3>" . "<div class=\"mc-promos viewcart\">" . implode($promotions) . "</div>";
            }
        });
        add_hook("ShoppingCartCheckoutOutput", -1, function () {
            $promotions = Promotion::cartViewPromotion();
            if (0 < count($promotions)) {
                return "<div class=\"sub-heading\"><span>" . \Lang::trans("store.lastChance") . "</span></div>" . "<div class=\"mc-promos checkout\">" . implode($promotions) . "</div>";
            }
        });
    }
    public static function cartViewPromotion()
    {
        $promotions = array();
        foreach (Service::active()->get() as $service) {
            $promotions[] = $service->factoryPromoter()->cartViewPromotion(func_get_args());
        }
        foreach ($promotions as $key => $value) {
            if (empty($value)) {
                unset($promotions[$key]);
            }
        }
        return $promotions;
    }
    protected static function renderPromotionsCarousel($promotions)
    {
        foreach ($promotions as $key => $value) {
            $promotions[$key] = "<div class=\"item" . ($key == 0 ? " active" : "") . "\">" . $value . "</div>";
        }
        if (count($promotions) == 1) {
            return "<h3 style=\"margin:0 0 20px 0;\">" . \Lang::trans("store.recommendedForYou") . "</h3>" . implode($promotions) . "<br>";
        }
        return "\n<div class=\"pull-right\">\n  <a href=\"#promotions-slider\" role=\"button\" data-slide=\"prev\" style=\"text-decoration:none;\">\n    <span class=\"glyphicon glyphicon-chevron-left\" aria-hidden=\"true\"></span>\n    <span class=\"sr-only\">" . \Lang::trans("tablepagesprevious") . "</span>\n  </a>\n  <a href=\"#promotions-slider\" role=\"button\" data-slide=\"next\">\n    <span class=\"glyphicon glyphicon-chevron-right\" aria-hidden=\"true\"></span>\n    <span class=\"sr-only\">" . \Lang::trans("tablepagesnext") . "</span>\n  </a>\n</div>\n<h3 style=\"margin:0 0 20px 0;\">" . \Lang::trans("store.recommendedForYou") . "</h3>" . "<div id=\"promotions-slider\" class=\"carousel slide\" data-ride=\"carousel\" style=\"margin:0 0 20px 0;\">\n  <div class=\"carousel-inner\" role=\"listbox\">\n    " . implode($promotions) . "\n  </div>\n</div>";
    }
}

?>