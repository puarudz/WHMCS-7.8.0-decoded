<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\ClientArea;

class UpgradeController
{
    private function renderUpgradePage(\WHMCS\Http\Message\ServerRequest $request, array $extraVars = array())
    {
        $isProduct = $request->request()->get("isproduct");
        $serviceId = $request->request()->get("serviceid");
        if (empty($serviceId)) {
            $redirectPath = \WHMCS\Utility\Environment\WebHelper::getBaseUrl() . "/clientarea.php";
            return new \Zend\Diactoros\Response\RedirectResponse($redirectPath);
        }
        $view = new \WHMCS\ClientArea();
        $view->setTemplate("upgrade-configure");
        $view->addOutputHookFunction("Upgrade");
        $view->setPageTitle(\Lang::trans("upgrade"));
        $view->setDisplayTitle(\Lang::trans("upgrade"));
        $view->addToBreadCrumb("index.php", \Lang::trans("globalsystemname"));
        $view->addToBreadCrumb("clientarea.php", \Lang::trans("clientareatitle"));
        $view->addToBreadCrumb("#", \Lang::trans("upgrade"));
        $view->requireLogin();
        $currency = getCurrency($view->getUserId());
        try {
            if ($isProduct) {
                $service = \WHMCS\Service\Service::userId($view->getUserId())->where("id", $serviceId)->first();
                $module = $service->product->module;
                $marketConnectType = $service->product->serviceKey;
            } else {
                $service = \WHMCS\Service\Addon::userId($view->getUserId())->where("id", $serviceId)->first();
                $module = $service->productAddon->module;
                $marketConnectType = $service->productAddon->serviceKey;
            }
            if (is_null($service)) {
                throw new \WHMCS\Exception("Invalid link followed. Please go back and try again.");
            }
            if ($module != "marketconnect") {
                throw new \WHMCS\Exception("Only MarketConnect services can be upgraded");
            }
            if (!$service->canBeUpgraded()) {
                throw new \WHMCS\Exception("Service not eligible for upgrade");
            }
            if ($service instanceof \WHMCS\Service\Service) {
                $upgradeProducts = \WHMCS\Product\Product::$marketConnectType()->visible()->orderBy("order")->get();
                $currentProductKey = $service->product->productKey;
            } else {
                if ($service instanceof \WHMCS\Service\Addon) {
                    $addonIds = \WHMCS\Config\Module\ModuleConfiguration::with("productAddon")->where("entity_type", "addon")->where("setting_name", "configoption1")->where("value", "LIKE", $marketConnectType . "_%")->get()->pluck("productAddon.id");
                    $upgradeProducts = \WHMCS\Product\Addon::marketConnect()->whereIn("id", $addonIds)->get();
                    $currentProductKey = $service->productAddon->productKey;
                } else {
                    throw new \WHMCS\Exception("Unrecognised service type");
                }
            }
            if (!\WHMCS\MarketConnect\Provision::factoryFromModel($service)->isEligibleForUpgrade()) {
                throw new \WHMCS\Exception("Product not eligible for upgrade");
            }
            $promoHelper = \WHMCS\MarketConnect\MarketConnect::factoryPromotionalHelper($marketConnectType);
            foreach ($upgradeProducts as $key => $product) {
                $product->features = $promoHelper->getFeaturesForUpgrade($product->productKey);
                if (is_null($product->features)) {
                    unset($upgradeProducts[$key]);
                    continue;
                }
                $product->pricing($currency);
                if ($service instanceof \WHMCS\Service\Service) {
                    $product->eligibleForUpgrade = $service->product->displayOrder <= $product->displayOrder;
                } else {
                    if ($service instanceof \WHMCS\Service\Addon) {
                        $product->eligibleForUpgrade = $service->productAddon->weight <= $product->weight;
                    }
                }
            }
            if ((new \WHMCS\Billing\Cycles())->isRecurring($service->billingCycle)) {
                $permittedBillingCycles = (new \WHMCS\Billing\Cycles())->getGreaterCycles($service->billingCycle);
            } else {
                $permittedBillingCycles = null;
            }
            $data = array("isService" => $service instanceof \WHMCS\Service\Service, "isAddon" => $service instanceof \WHMCS\Service\Addon, "upgradeProducts" => $upgradeProducts, "serviceToBeUpgraded" => $service, "recommendedProductKey" => $promoHelper->getRecommendedProductKeyForUpgrade($currentProductKey), "permittedBillingCycles" => $permittedBillingCycles);
        } catch (\Exception $e) {
            $data = array("errorMessage" => $e->getMessage());
        }
        $view->setTemplateVariables(array_merge($data, $extraVars));
        return $view;
    }
    public function index(\WHMCS\Http\Message\ServerRequest $request)
    {
        return $this->renderUpgradePage($request);
    }
    public function addToCart(\WHMCS\Http\Message\ServerRequest $request)
    {
        check_token();
        $isService = $request->request()->get("isservice");
        $serviceId = $request->request()->get("serviceid");
        $productId = $request->request()->get("productid");
        $billingCycle = $request->request()->get("billingcycle");
        if ($isService) {
            $service = \WHMCS\Service\Service::findOrFail($serviceId);
            $currentProduct = $service->product;
            $upgradeProduct = \WHMCS\Product\Product::findOrFail($productId);
        } else {
            $service = \WHMCS\Service\Addon::findOrFail($serviceId);
            $currentProduct = $service->productAddon;
            $upgradeProduct = \WHMCS\Product\Addon::findOrFail($productId);
        }
        if (!$service->canBeUpgraded()) {
            throw new \WHMCS\Exception("Service not eligible for upgrade");
        }
        if (!\WHMCS\MarketConnect\Provision::factoryFromModel($service)->isEligibleForUpgrade()) {
            throw new \WHMCS\Exception("Product not eligible for upgrade");
        }
        if (!$currentProduct->isValidForUpgrade($upgradeProduct)) {
            throw new \WHMCS\Exception("Not a valid upgrade scenario");
        }
        if ($currentProduct->isFree() || $currentProduct->isOneTime()) {
        } else {
            $cyclesHelper = new \WHMCS\Billing\Cycles();
            $monthsAfter = $cyclesHelper->getNumberOfMonths($billingCycle);
            $monthsBefore = $cyclesHelper->getNumberOfMonths($service->billingCycle);
            if ($monthsAfter < $monthsBefore) {
                throw new \WHMCS\Exception("Upgrades may only be performed to the same or greater billing cycle term");
            }
            if ($monthsAfter === $monthsBefore && $currentProduct->id === $upgradeProduct->id) {
                return $this->renderUpgradePage($request, array("errorMessage" => \Lang::trans("upgradeSameProductMustExtendCycle")));
            }
        }
        \WHMCS\OrderForm::addUpgradeToCart($service instanceof \WHMCS\Service\Service ? "service" : "addon", $service->id, $upgradeProduct->id, $billingCycle);
        $redirectPath = \WHMCS\Utility\Environment\WebHelper::getBaseUrl() . "/cart.php?a=view";
        return new \Zend\Diactoros\Response\RedirectResponse($redirectPath);
    }
}

?>