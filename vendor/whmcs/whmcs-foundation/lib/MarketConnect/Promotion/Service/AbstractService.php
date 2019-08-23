<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect\Promotion\Service;

abstract class AbstractService
{
    protected $name = NULL;
    protected $friendlyName = NULL;
    protected $productKeys = array();
    protected $qualifyingProductTypes = array();
    protected $settings = array();
    protected $upsells = array();
    protected $defaultPromotionalContent = array();
    protected $promotionalContent = array();
    protected $upsellPromoContent = array();
    protected $loginPanel = NULL;
    protected $supportsUpgrades = true;
    protected $promoteToNewClients = false;
    protected $noPromotionStatuses = array("Cancelled", "Terminated", "Fraud");
    public function getProductKeys()
    {
        return $this->productKeys;
    }
    public function getName()
    {
        return $this->name;
    }
    public function getModel()
    {
        $className = get_class($this);
        $className = substr($className, strrpos($className, "\\") + 1);
        return \WHMCS\MarketConnect\Service::where("name", $className)->first();
    }
    public function getSettings()
    {
        return (array) $this->settings;
    }
    public function supportsUpgrades()
    {
        return (bool) $this->supportsUpgrades;
    }
    public function collectionContains($collection, $contains)
    {
        foreach ($contains as $containedItem) {
            if ($collection->contains($containedItem)) {
                return true;
            }
        }
        return false;
    }
    public function getBestUpsell($productKey)
    {
        if (array_key_exists($productKey, $this->upsells)) {
            $upsells = $this->upsells[$productKey];
            foreach ($upsells as $upsellProductKey) {
                $product = \WHMCS\Product\Product::productKey($upsellProductKey)->visible()->first();
                if (!is_null($product)) {
                    return $product;
                }
            }
        }
        return null;
    }
    public function getPromotionalContent($promotionalKey)
    {
        if (isset($this->promotionalContent[$promotionalKey])) {
            $promotionalContent = $this->promotionalContent[$promotionalKey];
        } else {
            $promotionalContent = $this->defaultPromotionalContent;
        }
        return new \WHMCS\MarketConnect\Promotion\PromotionContentWrapper($this->name, $promotionalKey, $promotionalContent);
    }
    public function getUpsellPromotionalContent($promotionalKey)
    {
        if (isset($this->upsellPromoContent[$promotionalKey])) {
            $promotionalContent = $this->upsellPromoContent[$promotionalKey];
            return new \WHMCS\MarketConnect\Promotion\PromotionContentWrapper($this->name, $promotionalKey, $promotionalContent, true);
        }
        return null;
    }
    public function getRecommendedProductKeyForUpgrade($productKey)
    {
        return array_key_exists($productKey, $this->recommendedUpgradePaths) ? $this->recommendedUpgradePaths[$productKey] : null;
    }
    protected function getAddonArray(array $groupedAddons, $addons, $billingCycle)
    {
        $addonsArray = array();
        $excludedAddonIds = $this->getExcludedFromNewPurchaseAddonIds();
        $firstCycle = null;
        foreach ($groupedAddons as $addonId) {
            $addonInfo = $addons->where("id", $addonId);
            if (!is_null($addonInfo) && !in_array($addonId, $excludedAddonIds)) {
                $addonInfo = $addonInfo->first();
                if (defined("CLIENTAREA") && $addonInfo->hidden) {
                    continue;
                }
                $name = $addonInfo["name"];
                $name = explode("-", $name, 2);
                $name = $name[1];
                $addonInfo["name"] = $name;
                if (isset($addonInfo["billingCycles"][$billingCycle])) {
                    $cycle = $billingCycle;
                    $pricing = $addonInfo["billingCycles"][$billingCycle];
                } else {
                    $cycle = $addonInfo["minCycle"];
                    $pricing = $addonInfo["minPrice"];
                }
                $pricing["cycle"] = $cycle;
                if (is_null($pricing["setup"])) {
                    $pricing["setup"] = new \WHMCS\View\Formatter\Price(0);
                }
                if (is_null($pricing["price"])) {
                    $pricing["price"] = new \WHMCS\View\Formatter\Price(0);
                }
                $price = new \WHMCS\Product\Pricing\Price($pricing);
                if (is_null($firstCycle)) {
                    $firstCycle = $cycle;
                }
                $addonsArray[$addonId] = array("addon" => $addonInfo, "price" => $price, "firstCycle" => $firstCycle);
            }
        }
        return $addonsArray;
    }
    public function cartViewPromotion()
    {
        return $this->cartPromo("cart-view");
    }
    public function cartCheckoutPromotion()
    {
        return $this->cartPromo("cart-checkout");
    }
    protected function cartPromo($callingLocation)
    {
        $service = $this->getModel();
        if (is_null($service) || !$service->setting("promotion." . $callingLocation)) {
            return "";
        }
        $cart = new \WHMCS\MarketConnect\Promotion\Helper\Cart();
        if (!$cart->hasProductTypes($this->qualifyingProductTypes)) {
            return "";
        }
        if ($cart->hasMarketConnectProductKeys($this->productKeys)) {
            foreach ($cart->getMarketConnectProductKeys() as $productKey) {
                $upsellProduct = $this->getBestUpsell($productKey);
                if (!is_null($upsellProduct)) {
                    $promotion = $this->getUpsellPromotionalContent($upsellProduct->productKey);
                    if (!is_null($promotion)) {
                        return new \WHMCS\MarketConnect\Promotion\CartPromotion($promotion, $upsellProduct);
                    }
                }
            }
            return "";
        } else {
            $product = $this->getPromotedProduct();
            if (!is_null($product)) {
                $promotion = $this->getPromotionalContent($product->productKey);
                if (!is_null($promotion)) {
                    return new \WHMCS\MarketConnect\Promotion\CartPromotion($promotion, $product);
                }
            }
        }
    }
    public function clientHasActiveServices()
    {
        $productKeys = (new \WHMCS\MarketConnect\Promotion\Helper\Client(\WHMCS\Session::get("uid")))->getProductAndAddonProductKeys();
        return $this->collectionContains($productKeys, $this->productKeys);
    }
    public function supportsLogin()
    {
        return !is_null($this->loginPanel);
    }
    public function getLoginPanel()
    {
        $services = (new \WHMCS\MarketConnect\Promotion\Helper\Client(\WHMCS\Session::get("uid")))->getServices($this->name);
        return (new \WHMCS\MarketConnect\Promotion\LoginPanel())->setName(ucfirst($this->name) . "Login")->setLabel($this->loginPanel["label"])->setIcon($this->loginPanel["icon"])->setColor($this->loginPanel["color"])->setImage($this->loginPanel["image"])->setPoweredBy($this->name)->setServices($services);
    }
    public function getPromotedProduct()
    {
        return \WHMCS\Product\Product::marketConnectProducts($this->productKeys)->visible()->orderBy("order")->first();
    }
    public function clientAreaHomeOutput()
    {
        $service = $this->getModel();
        if (is_null($service) || !$service->setting("promotion.client-home")) {
            return NULL;
        }
        $client = new \WHMCS\MarketConnect\Promotion\Helper\Client(\WHMCS\Session::get("uid"));
        foreach ($client->getProductsAndAddons() as $service) {
            $productKey = $service->isService() ? $service->product->productKey : $service->productAddon->productKey;
            $upsellProduct = $this->getBestUpsell($productKey);
            if (!is_null($upsellProduct)) {
                $promotion = $this->getUpsellPromotionalContent($upsellProduct->productKey);
                if (!is_null($promotion)) {
                    return new \WHMCS\MarketConnect\Promotion\UpsellPromotion($promotion, $upsellProduct, $service);
                }
            }
        }
        if ($client->hasProductTypes($this->qualifyingProductTypes) || count($client->getProductTypes()) == 0 && $this->promoteToNewClients) {
            if ($this->collectionContains($client->getProductAndAddonProductKeys(), $this->productKeys)) {
                return NULL;
            }
            $promoProduct = $this->getPromotedProduct();
            if (is_null($promoProduct)) {
                return NULL;
            }
            $promotion = $this->getPromotionalContent($promoProduct->productKey);
            if (!is_null($promotion)) {
                return new \WHMCS\MarketConnect\Promotion\Promotion($promotion, $promoProduct);
            }
        } else {
            return NULL;
        }
    }
    public function clientAreaSidebars()
    {
        $primarySidebar = \Menu::primarySidebar();
        $secondarySidebar = \Menu::secondarySidebar();
        if (is_null($secondarySidebar->getChild("My Services Actions")) && is_null($primarySidebar->getChild("Service Details Actions"))) {
            return NULL;
        }
        $service = $this->getModel();
        if (is_null($service) || !$service->setting("promotion.product-list")) {
            return NULL;
        }
        if (!is_null($primarySidebar->getChild("Service Details Actions"))) {
            $serviceId = \App::getFromRequest("id");
            $service = \WHMCS\Service\Service::find($serviceId);
            $serviceHelper = new \WHMCS\MarketConnect\Promotion\Helper\Service($service);
            $addons = $serviceHelper->getProductAndAddonProductKeys();
            if ($this->collectionContains($addons, $this->productKeys)) {
                return NULL;
            }
        }
        $secondarySidebar->addChild(ucfirst($this->name) . " Sidebar Promo", array("name" => $this->name . " Sidebar Promo", "label" => \Lang::trans("store." . $this->name . ".promo.sidebar.title"), "order" => 100, "icon" => "", "attributes" => array("class" => "mc-panel-promo panel-promo-" . $this->name), "bodyHtml" => "<div class=\"text-center\">\n    <a href=\"" . routePath($this->primaryLandingPageRouteName) . "\" style=\"font-weight: 300;\">\n        <img src=\"" . \WHMCS\Utility\Environment\WebHelper::getBaseUrl() . "/" . $this->primaryIcon . "\">\n        <span>" . \Lang::trans("store." . $this->name . ".promo.sidebar.body") . "</span>\n    </a>\n</div>", "footerHtml" => "<i class=\"fas fa-arrow-right fa-fw\"></i> <a href=\"" . routePath($this->primaryLandingPageRouteName) . "\">" . \Lang::trans("learnmore") . "</a>"));
    }
    public function productDetailsLogin(\WHMCS\Service\Service $serviceModel)
    {
        if (in_array($serviceModel->status, $this->noPromotionStatuses)) {
            return false;
        }
        if ($this->supportsLogin()) {
            $currentServiceId = $serviceModel->id;
            if (in_array($currentService->product->configoption1, $this->productKeys)) {
                return $this->getLoginPanel()->setServices(array(array("type" => "service", "id" => $currentServiceId)));
            }
            $serviceInterface = new \WHMCS\MarketConnect\Promotion\Helper\Service($serviceModel);
            if ($this->collectionContains($serviceInterface->getAddonProductKeys(), $this->productKeys)) {
                $addon = $serviceInterface->getActiveAddonByProductKeys($this->productKeys);
                return $this->getLoginPanel()->setServices(array(array("type" => "addon", "id" => $addon->id)));
            }
        }
    }
    public function productDetailsOutput(\WHMCS\Service\Service $serviceModel)
    {
        if (in_array($serviceModel->status, $this->noPromotionStatuses)) {
            return NULL;
        }
        if (!in_array($serviceModel->product->type, $this->qualifyingProductTypes)) {
            return NULL;
        }
        $service = $this->getModel();
        if (is_null($service) || !$service->setting("promotion.product-details")) {
            return NULL;
        }
        $serviceInterface = new \WHMCS\MarketConnect\Promotion\Helper\Service($serviceModel);
        foreach ($serviceInterface->getAddonProducts() as $addon) {
            $productKey = $addon->productAddon->productKey;
            $upsellProduct = $this->getBestUpsell($productKey);
            if (!is_null($upsellProduct)) {
                $promotion = $this->getUpsellPromotionalContent($upsellProduct->productKey);
                if (!is_null($promotion)) {
                    return new \WHMCS\MarketConnect\Promotion\UpsellPromotion($promotion, $upsellProduct, $addon);
                }
            }
        }
        $serviceProductKeys = $serviceInterface->getProductAndAddonProductKeys();
        if ($this->collectionContains($serviceProductKeys, $this->productKeys)) {
            return NULL;
        }
        $promoProduct = $this->getPromotedProduct();
        if (is_null($promoProduct)) {
            return NULL;
        }
        $promotion = $this->getPromotionalContent($promoProduct->productKey);
        if (!is_null($promotion)) {
            return new \WHMCS\MarketConnect\Promotion\Promotion($promotion, $promoProduct, $serviceModel);
        }
    }
    public function adminCartConfigureProductAddon($addonsByGroup, $addons, $billingCycle, $orderItemId)
    {
        $defaultSelectedAddonId = $this->getAddonToSelectByDefault();
        $addonOptions = array();
        foreach ($this->getProductKeyPrefixes() as $type) {
            if (0 < count($addonsByGroup[$type])) {
                $addonsArray = $this->getAddonArray($addonsByGroup[$type], $addons, $billingCycle);
                foreach ($addonsArray as $addonId => $addonData) {
                    $addonInfo = $addonData["addon"];
                    $addonOptions[] = "<label class=\"radio-inline\"><input type=\"radio\" onchange=\"updatesummary(); return false;\" name=\"addons_radio[" . $orderItemId . "][" . $this->name . "]\" value=\"" . $addonId . "\" class=\"addon-selector\"" . ($defaultSelectedAddonId && $defaultSelectedAddonId == $addonId ? " checked" : "") . "> " . $addonInfo["name"] . " - " . $addonData["price"]->toFullString() . "</label>";
                }
            }
        }
        if ($addonOptions) {
            array_unshift($addonOptions, "<strong>" . $this->friendlyName . "</strong>", "<label class=\"radio-inline\"><input type=\"radio\" onchange=\"updatesummary(); return false;\" name=\"addons_radio[" . $orderItemId . "][" . $this->name . "]\" class=\"addon-selector\" value=\"\" checked> " . \Lang::trans("none") . "</label>");
        }
        return $addonOptions;
    }
    public function getProductKeyPrefixes()
    {
        $prefixes = array();
        foreach ($this->productKeys as $productKey) {
            $parts = explode("_", $productKey, 2);
            $prefixes[] = $parts[0];
        }
        return array_values(array_unique($prefixes));
    }
    public function cartConfigureProductAddon($addonsByGroup, $addons, $billingCycle)
    {
        $firstCycle = null;
        $defaultSelectedAddonId = $this->getAddonToSelectByDefault();
        $addonOptions = array();
        foreach ($this->getProductKeyPrefixes() as $type) {
            $addonsArray = array();
            if (isset($addonsByGroup[$type]) && 0 < count($addonsByGroup[$type])) {
                $addonsArray = $this->getAddonArray($addonsByGroup[$type], $addons, $billingCycle);
            }
            foreach ($addonsArray as $addonId => $addonData) {
                $addonInfo = $addonData["addon"];
                if (is_null($firstCycle)) {
                    $firstCycle = $addonData["firstCycle"];
                }
                $addonOptions[] = "<label class=\"radio-inline\"><input type=\"radio\" name=\"addons_radio[" . $this->name . "]\" value=\"" . $addonId . "\" class=\"addon-selector\"" . ($addonInfo["status"] || $defaultSelectedAddonId && $defaultSelectedAddonId == $addonId ? " checked" : "") . "> &nbsp; " . $addonInfo["name"] . "<span class=\"pull-right\">" . $addonData["price"]->toFullString() . "</span></label>";
            }
        }
        if (0 < count($addonOptions)) {
            return $this->renderCartConfigureProductAddon($addonOptions, $firstCycle);
        }
    }
    protected function getAddonToSelectByDefault()
    {
        return null;
    }
    protected function getExcludedFromNewPurchaseAddonIds()
    {
        return array();
    }
    protected function renderCartConfigureProductAddon($addonOptions, $firstCycle)
    {
        return "<div class=\"addon-promo-container addon-promo-container-" . $this->name . "\">\n            <div class=\"description\">\n                <div class=\"logo\">\n                    <img src=\"" . $this->primaryIcon . "\" width=\"80\">\n                </div>\n                <h3>" . \Lang::trans("store." . $this->name . ".cartTitle") . "</h3>\n                <p>" . \Lang::trans("store." . $this->name . ".cartShortDescription") . "<br><a href=\"" . routePath($this->primaryLandingPageRouteName) . "\" target=\"_blank\">" . \Lang::trans("learnmore") . "...</a></p>\n            </div>\n            <div class=\"clearfix\"></div>\n            <div class=\"pull-right\"><strong>" . \Lang::trans("orderpaymentterm" . $firstCycle) . "</strong></div>\n            <label class=\"radio-inline\"><input type=\"radio\" name=\"addons_radio[" . $this->name . "]\" class=\"addon-selector\" checked> &nbsp; " . \Lang::trans("none") . "<span class=\"pull-right\">-</span></label><br>\n            " . implode("<br>", $addonOptions) . "\n        </div>";
    }
}

?>