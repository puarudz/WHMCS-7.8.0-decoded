<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect\Promotion\Helper;

class Service
{
    protected $service = NULL;
    public function __construct($service)
    {
        $this->service = $service;
    }
    public function getAddonProducts()
    {
        return $this->service->addons()->marketConnect()->active()->get();
    }
    public function getAddonProductKeys()
    {
        $serviceAddonIds = $this->service->addons()->active()->pluck("addonid");
        $marketConnectAddonIds = \WHMCS\Product\Addon::where("module", "marketconnect")->pluck("id");
        return \WHMCS\Config\Module\ModuleConfiguration::where("entity_type", "addon")->whereIn("entity_id", $marketConnectAddonIds)->whereIn("entity_id", $serviceAddonIds)->where("setting_name", "configoption1")->pluck("value");
    }
    public function getProductAndAddonProductKeys()
    {
        $addonKeys = $this->getAddonProductKeys();
        $productKey = $this->service->product->configoption1;
        if ($productKey) {
            $addonKeys[] = $productKey;
        }
        return $addonKeys;
    }
    public function getActiveAddonByProductKeys($productKeys)
    {
        $serviceAddonIds = $this->service->addons()->where("status", "Active")->pluck("addonid");
        $marketConnectAddonIds = \WHMCS\Product\Addon::where("module", "marketconnect")->pluck("id");
        $entityId = \WHMCS\Config\Module\ModuleConfiguration::where("entity_type", "addon")->whereIn("entity_id", $marketConnectAddonIds)->whereIn("entity_id", $serviceAddonIds)->where("setting_name", "configoption1")->whereIn("value", $productKeys)->pluck("entity_id")->first();
        return $this->service->addons()->where("addonid", $entityId)->first();
    }
}

?>