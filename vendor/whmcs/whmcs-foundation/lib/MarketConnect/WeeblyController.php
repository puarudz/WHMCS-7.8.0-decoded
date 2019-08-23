<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect;

class WeeblyController
{
    public function index(\WHMCS\Http\Message\ServerRequest $request)
    {
        $isAdminPreview = \App::getFromRequest("preview") && \WHMCS\Session::get("adminid");
        if (!$isAdminPreview) {
            $service = Service::where("name", "weebly")->first();
            if (is_null($service) || !$service->status) {
                return new \Zend\Diactoros\Response\RedirectResponse("index.php");
            }
        }
        $ca = new \WHMCS\ClientArea();
        $ca->setPageTitle(\Lang::trans("store.websiteBuilder.title"));
        $ca->addToBreadCrumb("index.php", \Lang::trans("globalsystemname"));
        $ca->addToBreadCrumb(routePath("store"), \Lang::trans("navStore"));
        $ca->addToBreadCrumb(routePath("store-websitebuilder-index"), \Lang::trans("store.websiteBuilder.title"));
        $ca->initPage();
        $all = \WHMCS\Product\Product::weebly()->visible()->orderBy("order")->get();
        $sessionCurrency = \WHMCS\Session::get("currency");
        $currency = getCurrency($ca->getUserId(), $sessionCurrency);
        $ca->assign("activeCurrency", $currency);
        $weeblyPromoHelper = MarketConnect::factoryPromotionalHelper("weebly");
        $enabledCycles = array();
        foreach ($all as $key => $product) {
            $all[$key]->idealFor = $weeblyPromoHelper->getIdealFor($product->productKey);
            $all[$key]->siteFeatures = $weeblyPromoHelper->getSiteFeatures($product->productKey);
            $all[$key]->ecommerceFeatures = $weeblyPromoHelper->getEcommerceFeatures($product->productKey);
            foreach ($product->pricing($currency)->allAvailableCycles() as $price) {
                $cycle = $price->cycle();
                if (!in_array($cycle, $enabledCycles)) {
                    $enabledCycles[] = $cycle;
                }
            }
        }
        $billingCycles = (new \WHMCS\Billing\Cycles())->getRecurringSystemBillingCycles();
        foreach ($billingCycles as $key => $cycle) {
            if (!in_array($cycle, $enabledCycles)) {
                unset($billingCycles[$key]);
            }
        }
        $litePlan = null;
        foreach ($all as $key => $product) {
            if ($product->productKey == "weebly_lite") {
                $litePlan = $product;
                unset($all[$key]);
            }
        }
        $ca->assign("litePlan", $litePlan);
        $ca->assign("products", $all);
        $ca->assign("billingCycles", $billingCycles);
        $ca->assign("inPreview", $isAdminPreview);
        $ca->setTemplate("store/weebly/index");
        $ca->skipMainBodyContainer();
        return $ca;
    }
    public function upgrade(\WHMCS\Http\Message\ServerRequest $request)
    {
        $ca = new \WHMCS\ClientArea();
        $user_id = $request->query()->get("user_id");
        $site = $request->query()->get("site");
        $plan = $request->query()->get("plan");
        $upgrade_type = $request->query()->get("upgrade_type");
        $upgrade_id = $request->query()->get("upgrade_id");
        $plan_ids = $request->query()->get("plan_ids");
        $serviceId = $request->request()->get("serviceid");
        $addonId = $request->request()->get("addonId");
        \WHMCS\Session::set("loginurlredirect", \WHMCS\Input\Sanitize::decode($_SERVER["REQUEST_URI"]));
        $ca->assign("incorrect", $request->query()->get("incorrect", false));
        if ($serviceId && !$addonId) {
            $this->setUpgradeProductKeyByServiceId($serviceId);
        } else {
            if ($serviceId && $addonId) {
                $this->setUpgradeProductKeyByAddonIdAndServiceId($addonId, $serviceId);
            } else {
                if ($plan) {
                    $this->setUpgradeProductKeyByPlan($plan);
                }
            }
        }
        $upgradePlanProductKey = $this->getUpgradeProductKey();
        $upgradeProduct = \WHMCS\Product\Product::marketConnect()->visible()->productKey($upgradePlanProductKey)->first();
        $ca->assign("product", $upgradeProduct);
        $weeblyPromoHelper = MarketConnect::factoryPromotionalHelper("weebly");
        $ca->assign("promo", $weeblyPromoHelper->getUpsellPromotionalContent($upgradePlanProductKey));
        $clientPromoHelper = new Promotion\Helper\Client($ca->getUserId());
        $services = $clientPromoHelper->getServices();
        $ca->assign("weeblyServices", isset($services["weebly"]) ? $services["weebly"] : array());
        $ca->setPageTitle(\Lang::trans("store.websiteBuilder.upgrade.title"));
        $ca->setTemplate("store/weebly/upgrade");
        $ca->skipMainBodyContainer();
        return $ca;
    }
    protected function setUpgradeProductKeyByPlan($plan)
    {
        if (strtolower($plan) == "business") {
            $upgradePlanProductKey = "weebly_business";
        } else {
            $upgradePlanProductKey = "weebly_pro";
        }
        \WHMCS\Session::set("weeblyUpgradeProductKey", $upgradePlanProductKey);
    }
    protected function setUpgradeProductKeyByServiceId($serviceId)
    {
        $currentProductKey = \WHMCS\Service\Service::where("userid", \WHMCS\Session::get("uid"))->where("id", $serviceId)->first()->product->moduleConfigOption1;
        if ($currentProductKey == "weebly_starter") {
            $upgradePlanProductKey = "weebly_pro";
        } else {
            if ($currentProductKey == "weebly_pro") {
                $upgradePlanProductKey = "weebly_business";
            } else {
                \App::redirect("index.php");
            }
        }
        \WHMCS\Session::set("weeblyUpgradeProductKey", $upgradePlanProductKey);
    }
    protected function setUpgradeProductKeyByAddonIdAndServiceId($addonId, $serviceId)
    {
        $upgradePlanProductKey = "";
        $addon = \WHMCS\Service\Addon::userId(\WHMCS\Session::get("uid"))->ofService($serviceId)->where("id", $addonId)->first();
        $currentProductKey = $addon->productAddon->moduleConfiguration()->where("setting_name", "configoption1")->first()->value;
        if ($currentProductKey == "weebly_starter") {
            $upgradePlanProductKey = "weebly_pro";
        } else {
            if ($currentProductKey == "weebly_pro") {
                $upgradePlanProductKey = "weebly_business";
            } else {
                \App::redirect("index.php");
            }
        }
        \WHMCS\Session::set("weeblyUpgradeProductKey", $upgradePlanProductKey);
    }
    protected function getUpgradeProductKey()
    {
        $upgradePlanProductKey = \WHMCS\Session::get("weeblyUpgradeProductKey");
        if (!in_array($upgradePlanProductKey, array("weebly_pro", "weebly_business"))) {
            $upgradePlanProductKey = "weebly_pro";
        }
        return $upgradePlanProductKey;
    }
    public function orderUpgrade(\WHMCS\Http\Message\ServerRequest $request)
    {
        $service = $request->request()->get("service");
        $parts = explode("-", $service, 2);
        $serviceType = isset($parts[0]) ? $parts[0] : null;
        $serviceId = isset($parts[1]) ? $parts[1] : null;
        $upgradePlanProductKey = $this->getUpgradeProductKey();
        if ($serviceType == "addon") {
            $addon = \WHMCS\Service\Addon::find($serviceId);
            $addonModel = \WHMCS\Config\Module\ModuleConfiguration::with("productAddon")->where("entity_type", "addon")->where("setting_name", "configoption1")->where("value", $upgradePlanProductKey)->get()->where("productAddon.module", "marketconnect")->first();
            if (!is_null($addonModel)) {
                $addonModel = $addonModel->productAddon;
                \WHMCS\OrderForm::addUpgradeToCart("addon", $serviceId, $addonModel->id);
            } else {
                throw new Exception("Could not find addon product configured for Weebly upgrade plan: " . $upgradePlanProductKey);
            }
        } else {
            $upgradeProduct = \WHMCS\Product\Product::marketConnect()->visible()->productKey($upgradePlanProductKey)->first();
            \WHMCS\OrderForm::addUpgradeToCart("service", $serviceId, $upgradeProduct->id);
        }
        $redirectPath = \WHMCS\Utility\Environment\WebHelper::getBaseUrl() . "/cart.php?a=view";
        return new \Zend\Diactoros\Response\RedirectResponse($redirectPath);
    }
}

?>