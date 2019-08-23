<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect;

class MarketConnect
{
    public static $services = array("symantec" => "Symantec", "weebly" => "Weebly", "spamexperts" => "SpamExperts", "sitelock" => "Sitelock", "codeguard" => "CodeGuard");
    const PRICING_TERM_FREE = 100;
    public static function getServices()
    {
        return array_keys(self::$services);
    }
    public static function hasActiveServices()
    {
        return 0 < Service::active()->count();
    }
    public static function getActiveServices()
    {
        $services = Service::active()->get();
        $servicesToReturn = array();
        foreach (self::getServices() as $service) {
            if (!is_null($services->where("name", $service)->first())) {
                $servicesToReturn[] = $service;
            }
        }
        return $servicesToReturn;
    }
    public static function isActive($service)
    {
        return !is_null(Service::active()->where("name", $service)->first());
    }
    public static function getProductKeys()
    {
        $services = Service::pluck("product_ids", "name");
        return $services->map(function ($item, $key) {
            return collect(explode(",", $item));
        });
    }
    public static function getProductKeysToServices()
    {
        $productKeys = self::getProductKeys();
        $return = array();
        foreach ($productKeys as $service => $productIds) {
            foreach ($productIds as $productId) {
                $return[$productId] = $service;
            }
        }
        return $return;
    }
    public static function factoryPromotionalHelperByProductKey($productKey)
    {
        $productKeys = self::getProductKeysToServices();
        if (array_key_exists($productKey, $productKeys)) {
            return self::factoryPromotionalHelper($productKeys[$productKey]);
        }
        return null;
    }
    public static function getClassByService($service)
    {
        if (isset(self::$services[$service])) {
            return self::$services[$service];
        }
        throw new \Exception("Invalid service name");
    }
    public static function factoryPromotionalHelper($service)
    {
        $className = "WHMCS\\MarketConnect\\Promotion\\Service\\" . self::getClassByService($service);
        return new $className();
    }
    public static function getMenuItems($loggedIn = false)
    {
        $children = self::getMenuItemsChildren();
        if ($loggedIn && self::isActive("symantec")) {
            if (!empty($children)) {
                $children[] = array("name" => "Website Security Divider", "label" => "-----", "attributes" => array("class" => "nav-divider"), "order" => 2000);
            }
            $children[] = array("name" => "Manage SSL Certificates", "label" => \Lang::trans("navManageSsl"), "uri" => routePath("store-ssl-certificates-manage"), "order" => 2100);
        }
        return $children;
    }
    protected static function getMenuItemsChildren(&$i = 0, $order = 1000)
    {
        $children = array();
        $routeMap = array("symantec" => "ssl-certificates", "weebly" => "websitebuilder", "spamexperts" => "emailservices", "sitelock" => "sitelock", "codeguard" => "codeguard");
        foreach (Service::active()->get() as $service) {
            if ($service->setting("general.activate-landing-page") !== false) {
                $iVar = $i;
                if ($service->productGroup) {
                    $iVar = $service->productGroup->displayOrder;
                }
                $name = $service->name;
                $children[] = array("name" => $name, "label" => \Lang::trans("navMarketConnectService." . $name), "uri" => isset($routeMap[$name]) ? routePath("store-" . $routeMap[$name] . "-index") : "#", "order" => $order + $iVar * 10);
                $i = $iVar + 1;
            }
        }
        return $children;
    }
    public static function getSidebarMenuItems(&$i, $order = 0)
    {
        return self::getMenuItemsChildren($i, $order);
    }
    public function activate($service)
    {
        try {
            $api = new Api();
            $response = $api->activate($service);
        } catch (Exception\AuthError $e) {
            throw new \Exception("Unable to login to the Marketplace. Please check your account and try again.");
        } catch (Exception\AuthNotConfigured $e) {
            throw new \Exception("Before you can activate a service, you must first login or create an account with WHMCS MarketConnect");
        } catch (Exception\ConnectionError $e) {
            throw new \Exception("Unable to connect to the Marketplace. Please try again later.");
        } catch (Exception\GeneralError $e) {
            throw new \Exception("Something went wrong. Please try again later.");
        }
        $productsAndAddons = $this->createProductsFromApiResponse($response["productsCreationParameters"]);
        $productIdNames = $productsAndAddons["products"]->keys()->all();
        $service = Service::activate($service, $productIdNames);
        if (!function_exists("rebuildModuleHookCache")) {
            require_once ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "modulefunctions.php";
        }
        rebuildModuleHookCache();
        return $productsAndAddons;
    }
    public function deactivate($service)
    {
        $service = Service::firstOrNew(array("name" => $service));
        $service->deactivate();
        try {
            $api = new Api();
            $api->deactivate($service->name);
        } catch (\Exception $e) {
        }
    }
    public function createProductsFromApiResponse($products)
    {
        $usdCurrency = \WHMCS\Billing\Currency::where("code", "USD")->first();
        if (is_null($usdCurrency)) {
            $exchangeRates = \WHMCS\Utility\CurrencyExchange::fetchCurrentRates();
            $defaultCurrency = \WHMCS\Billing\Currency::defaultCurrency()->first();
            if (!$exchangeRates->hasCurrencyCode($defaultCurrency->code)) {
                throw new \Exception("We are not able to obtain a USD exchange rate for the default currency in your WHMCS installation. Please add the USD currency and try again.");
            }
            $usdCurrency = new \WHMCS\Billing\Currency();
            $usdCurrency->code = "USD";
            $usdCurrency->rate = $exchangeRates->getUsdExchangeRate($defaultCurrency->code);
        }
        $currencies = \WHMCS\Billing\Currency::all();
        $resultingProducts = new \Illuminate\Support\Collection();
        $resultingAddons = new \Illuminate\Support\Collection();
        foreach ($products as $group) {
            $groupModel = \WHMCS\Product\Group::where("name", $group["name"])->first();
            if (is_null($groupModel)) {
                $groupModel = new \WHMCS\Product\Group();
                $groupModel->name = $group["name"];
                $groupModel->headline = $group["headline"];
                $groupModel->tagline = $group["tagline"];
                $groupModel->isHidden = true;
                $groupModel->displayOrder = \WHMCS\Product\Group::orderBy("order", "desc")->pluck("order")->first() + 1;
                $groupModel->save();
            }
            foreach ($group["products"] as $product) {
                $emailTemplateId = 0;
                if ($product["welcomeEmailName"]) {
                    $emailTemplateId = \WHMCS\Mail\Template::where("name", "=", $product["welcomeEmailName"])->where("language", "=", "")->orWhere("language", "=", null)->pluck("id")->first();
                }
                $newProduct = false;
                $newAddon = false;
                $productModel = \WHMCS\Product\Product::where("servertype", $product["module"])->where("configoption1", $product["moduleConfigOptions"][1])->first();
                if (is_null($productModel)) {
                    $productModel = new \WHMCS\Product\Product();
                    $productModel->type = $product["type"];
                    $productModel->name = $product["name"];
                    $productModel->description = $product["description"];
                    $productModel->welcomeEmailTemplateId = $emailTemplateId;
                    $productModel->paymentType = $product["paymentType"];
                    $productModel->autoSetup = $product["autoSetup"];
                    $productModel->module = $product["module"];
                    foreach ($product["moduleConfigOptions"] as $key => $value) {
                        $keyName = "moduleConfigOption" . $key;
                        $productModel->{$keyName} = $value;
                    }
                    $productModel->displayOrder = $product["displayOrder"];
                    $productModel->applyTax = true;
                    $productModel->isFeatured = (bool) $product["isFeatured"];
                    $groupModel->products()->save($productModel);
                    $resultingProducts->put($product["moduleConfigOptions"][1], $productModel);
                    $newProduct = true;
                } else {
                    $productModel->isHidden = false;
                    $productModel->quantityInStock = 0;
                    $productModel->stockControlEnabled = false;
                    $productModel->displayOrder = $product["displayOrder"];
                    $productModel->save();
                    $resultingProducts->put($productModel->moduleConfigOption1, $productModel);
                }
                $addonModel = \WHMCS\Config\Module\ModuleConfiguration::with("productAddon")->where("entity_type", "addon")->where("setting_name", "configoption1")->where("value", $product["moduleConfigOptions"][1])->get()->where("productAddon.module", $product["module"])->first();
                if (is_null($addonModel)) {
                    $productType = $product["moduleConfigOptions"][1];
                    $productType = explode("_", $productType);
                    $productType = $productType[0];
                    switch ($productType) {
                        case "sitelock":
                            $weighting = 300;
                            break;
                        case "spamexperts":
                            $weighting = 200;
                            break;
                        case "weebly":
                            $weighting = 100;
                            break;
                        case "symantec":
                        case "rapidssl":
                        case "geotrust":
                        default:
                            $weighting = 0;
                    }
                    $addonProducts = \WHMCS\Product\Product::where("id", "!=", 0);
                    foreach ($product["addonLinkCriteria"] as $field => $value) {
                        if (is_array($value)) {
                            $addonProducts->whereIn($field, $value);
                        } else {
                            $addonProducts->where($field, $value);
                        }
                    }
                    $addonProductIds = $addonProducts->pluck("id")->toArray();
                    $addonModel = new \WHMCS\Product\Addon();
                    $addonModel->name = $group["name"] . " - " . $product["name"];
                    $addonModel->description = $product["description"];
                    $addonModel->billingCycle = $product["paymentType"];
                    $addonModel->showOnOrderForm = true;
                    $addonModel->applyTax = true;
                    $addonModel->autoActivate = $product["autoSetup"];
                    $addonModel->welcomeEmailTemplateId = $emailTemplateId;
                    $addonModel->packages = $addonProductIds;
                    $addonModel->type = $product["type"];
                    $addonModel->module = $product["module"];
                    $addonModel->weight = $product["displayOrder"] + $weighting;
                    if (!empty($product["addonLinkCriteria"]) && is_array($product["addonLinkCriteria"])) {
                        $addonModel->autoLinkCriteria = $product["addonLinkCriteria"];
                    }
                    $addonModel->save();
                    $newAddon = true;
                    foreach ($product["moduleConfigOptions"] as $key => $value) {
                        $moduleConfigModel = new \WHMCS\Config\Module\ModuleConfiguration();
                        $moduleConfigModel->entityType = "addon";
                        $moduleConfigModel->settingName = "configoption" . $key;
                        $moduleConfigModel->friendlyName = "";
                        $moduleConfigModel->value = $value;
                        $addonModel->moduleConfiguration()->save($moduleConfigModel);
                    }
                    $resultingAddons->push($addonModel);
                } else {
                    $productAddon = $addonModel->productAddon;
                    $productAddon->showOnOrderForm = true;
                    $addonProducts = \WHMCS\Product\Product::where("id", "!=", 0);
                    foreach ($product["addonLinkCriteria"] as $field => $value) {
                        if (is_array($value)) {
                            $addonProducts->whereIn($field, $value);
                        } else {
                            $addonProducts->where($field, $value);
                        }
                    }
                    $productAddon->packages = $addonProducts->pluck("id")->toArray();
                    $productAddon->save();
                    $resultingAddons->push($productAddon);
                }
                if ($newProduct || $newAddon) {
                    foreach ($currencies as $currency) {
                        $pricingArray = array("type" => "product", "currency" => $currency["id"], "relid" => $productModel->id, "monthly" => "-1", "quarterly" => "-1", "semiannually" => "-1", "annually" => "-1", "biennially" => "-1", "triennially" => "-1");
                        foreach ($product["pricing"] as $cycle => $price) {
                            if ($cycle == "onetime") {
                                $cycle = "monthly";
                            }
                            if (array_key_exists($cycle, $pricingArray)) {
                                $pricingArray[substr($cycle, 0, 1) . "setupfee"] = convertCurrency($price["setup"], null, $currency->id, $usdCurrency->rate);
                                $pricingArray[$cycle] = convertCurrency($price["price"], null, $currency->id, $usdCurrency->rate);
                            }
                        }
                        if ($newProduct) {
                            \WHMCS\Database\Capsule::table("tblpricing")->insert($pricingArray);
                        }
                        if ($newAddon) {
                            $pricingArray["type"] = "addon";
                            $pricingArray["relid"] = $addonModel->id;
                            \WHMCS\Database\Capsule::table("tblpricing")->insert($pricingArray);
                        }
                    }
                }
            }
        }
        return array("products" => $resultingProducts, "addons" => $resultingAddons);
    }
    public static function isAccountConfigured()
    {
        return self::accountEmail() && 0 < strlen(self::getApiBearerToken());
    }
    public static function accountEmail()
    {
        return \WHMCS\Config\Setting::getValue("MarketConnectEmail");
    }
    public static function getApiBearerToken()
    {
        return decrypt(\WHMCS\Config\Setting::getValue("MarketConnectApiToken"));
    }
    public function removeMarketplaceAddons($addons)
    {
        $marketConnectAddonIds = \WHMCS\Product\Addon::where("module", "marketconnect")->pluck("id");
        foreach ($addons as $key => $addonData) {
            if ($marketConnectAddonIds->contains($addonData["id"])) {
                unset($addons[$key]);
            }
        }
        return $addons;
    }
    protected function getAddonsByGroup($addons)
    {
        $marketConnectAddonIds = \WHMCS\Product\Addon::where("module", "marketconnect")->pluck("id");
        $addonsGroupMap = \WHMCS\Config\Module\ModuleConfiguration::where("entity_type", "addon")->whereIn("entity_id", $marketConnectAddonIds)->whereIn("entity_id", $addons->pluck("id"))->where("setting_name", "configoption1")->pluck("value", "entity_id");
        $addonsByGroup = array();
        foreach ($addonsGroupMap as $addonId => $addonKey) {
            $addonKey = explode("_", $addonKey);
            $addonsByGroup[$addonKey[0]][] = $addonId;
        }
        return $addonsByGroup;
    }
    public function getAdminMarketplaceAddonPromo($addons, $billingCycle, $orderItemId)
    {
        $addons = collect($addons);
        $addonsByGroup = $this->getAddonsByGroup($addons);
        $addonPromoHtml = array();
        foreach (Service::active()->get() as $service) {
            $promoter = $service->factoryPromoter();
            $addonPromoHtml[] = $promoter->adminCartConfigureProductAddon($addonsByGroup, $addons, $billingCycle, $orderItemId);
        }
        return $addonPromoHtml;
    }
    public function getMarketplaceConfigureProductAddonPromoHtml($addons, $billingCycle)
    {
        $addons = collect($addons);
        $addonsByGroup = $this->getAddonsByGroup($addons);
        $addonPromoHtml = array();
        foreach (Service::active()->get() as $service) {
            $promoter = $service->factoryPromoter();
            $addonPromoHtml[] = $promoter->cartConfigureProductAddon($addonsByGroup, $addons, $billingCycle);
        }
        return $addonPromoHtml;
    }
}

?>