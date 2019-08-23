<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect;

class Service extends \WHMCS\Model\AbstractModel
{
    protected $table = "tblmarketconnect_services";
    protected $booleans = array("status");
    protected $casts = array("settings" => "array");
    protected $commaSeparated = array("productIds");
    protected $fillable = array("name");
    protected $appends = array("productGroup");
    public $timestamps = false;
    public function scopeActive(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where("status", 1);
    }
    public static function activate($serviceName, array $productIdNames = NULL)
    {
        $service = self::firstOrNew(array("name" => $serviceName));
        $service->status = true;
        if (is_array($productIdNames) && !empty($productIdNames)) {
            $service->productIds = array_unique(array_merge($service->productIds, $productIdNames));
        }
        if (!$service->id) {
            $generalSettingDefaults = array();
            foreach ($service->getSettingDefinitions() as $setting) {
                $generalSettingDefaults[$setting["name"]] = $setting["default"];
            }
            $service->settings = array("promotion" => array("client-home" => true, "product-details" => true, "product-list" => true, "cart-view" => true, "cart-checkout" => true), "general" => $generalSettingDefaults);
        }
        $service->save();
        return $service;
    }
    public function deactivate()
    {
        foreach (\WHMCS\Product\Product::marketConnect()->whereIn("configoption1", $this->productIds)->get() as $product) {
            $product->isHidden = true;
            $product->quantityInStock = 0;
            $product->stockControlEnabled = true;
            $product->save();
        }
        foreach (\WHMCS\Config\Module\ModuleConfiguration::with("productAddon")->where("entity_type", "addon")->where("setting_name", "configoption1")->whereIn("value", $this->productIds)->get() as $addonModuleConfig) {
            $productAddon = $addonModuleConfig->productAddon;
            if (!is_null($productAddon)) {
                $productAddon->showOnOrderForm = false;
                $productAddon->save();
            }
        }
        $this->status = false;
        $this->save();
        return $this;
    }
    public function setting($key)
    {
        $settings = $this->settings;
        $parts = explode(".", $key);
        foreach ($parts as $part) {
            $settings = isset($settings[$part]) ? $settings[$part] : null;
        }
        return $settings;
    }
    public function factoryPromoter()
    {
        $key = strtolower($this->name);
        $className = "WHMCS\\MarketConnect\\Promotion\\Service\\" . MarketConnect::getClassByService($key);
        return new $className();
    }
    public function getProductGroupAttribute()
    {
        static $productGroups = array();
        if (!array_key_exists($this->id, $productGroups) || is_null($productGroups[$this->id])) {
            $productIds = $this->productIds;
            $productGroups[$this->id] = \WHMCS\Product\Group::whereHas("products", function (\Illuminate\Database\Eloquent\Builder $query) use($productIds) {
                $query->where("servertype", "marketconnect")->whereIn("configoption1", $productIds);
            })->first();
        }
        return $productGroups[$this->id];
    }
    public static function getAutoAssignableAddons()
    {
        $mcServices = self::active()->get()->filter(function ($mcService) {
            return $mcService->setting("general.auto-assign-addons");
        });
        $addons = array();
        foreach ($mcServices as $mcService) {
            $addonModuleConfigs = \WHMCS\Config\Module\ModuleConfiguration::with("productAddon")->where("entity_type", "addon")->where("setting_name", "configoption1")->whereIn("value", $mcService->productIds)->get();
            foreach ($addonModuleConfigs as $addonModuleConfig) {
                if ($addonModuleConfig->productAddon) {
                    $addons[$addonModuleConfig->productAddon->id] = $addonModuleConfig->productAddon;
                }
            }
        }
        return $addons;
    }
    public function getSettingDefinitions()
    {
        $serviceSpecificSettings = $this->factoryPromoter()->getSettings();
        return array_merge(Promotion::DEFAULT_SETTINGS, $serviceSpecificSettings);
    }
}

?>