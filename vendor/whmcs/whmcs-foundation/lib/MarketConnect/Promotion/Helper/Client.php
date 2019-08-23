<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect\Promotion\Helper;

class Client
{
    protected $clientId = NULL;
    public function __construct($clientId)
    {
        $this->clientId = $clientId;
    }
    public function getProductTypes()
    {
        $productIds = \WHMCS\Service\Service::where("userid", $this->clientId)->active()->pluck("packageid");
        return \WHMCS\Product\Product::whereIn("id", $productIds)->pluck("type")->unique();
    }
    public function hasProductTypes(array $types)
    {
        $productTypes = $this->getProductTypes();
        foreach ($types as $type) {
            if ($productTypes->contains($type)) {
                return true;
            }
        }
        return false;
    }
    public function getProductProductKeys()
    {
        $serviceProductIds = \WHMCS\Service\Service::where("userid", $this->clientId)->where("domainstatus", "Active")->pluck("packageid");
        return \WHMCS\Product\Product::where("servertype", "marketconnect")->whereIn("id", $serviceProductIds)->pluck("configoption1");
    }
    public function getAddonProductKeys()
    {
        $serviceIds = \WHMCS\Service\Service::where("userid", $this->clientId)->where("domainstatus", "Active")->pluck("id");
        $serviceAddonIds = \WHMCS\Service\Addon::where("userid", $this->clientId)->whereIn("hostingid", $serviceIds)->where("status", "Active")->pluck("addonid");
        $marketConnectAddonIds = \WHMCS\Product\Addon::where("module", "marketconnect")->pluck("id");
        return \WHMCS\Config\Module\ModuleConfiguration::where("entity_type", "addon")->whereIn("entity_id", $marketConnectAddonIds)->whereIn("entity_id", $serviceAddonIds)->where("setting_name", "configoption1")->pluck("value");
    }
    public function getProductAndAddonProductKeys()
    {
        return $this->getProductProductKeys()->merge($this->getAddonProductKeys());
    }
    public function getProductsAndAddons()
    {
        $client = \WHMCS\User\Client::find($this->clientId);
        return $client->services()->marketConnect()->active()->get()->merge($client->addons()->marketConnect()->active()->get());
    }
    public function getServices($serviceName = "")
    {
        $productConfigOptions = array();
        foreach (\WHMCS\MarketConnect\MarketConnect::getServices() as $service) {
            $promo = \WHMCS\MarketConnect\MarketConnect::factoryPromotionalHelper($service);
            if ($promo->supportsLogin()) {
                $productConfigOptions[] = $service . "_%";
            }
        }
        $services = \WHMCS\Service\Service::with(array("product", "addons" => function ($query) {
            $query->whereIn("status", array("Active"));
        }, "addons.productAddon" => function ($query) {
            $query->where("module", "=", "marketconnect");
        }, "addons.productAddon.moduleConfiguration" => function ($query) use($productConfigOptions) {
            $query->where("setting_name", "=", "configoption1")->where(function ($query) use($productConfigOptions) {
                $first = true;
                foreach ($productConfigOptions as $configOption) {
                    if ($first) {
                        $query->where("value", "like", $configOption);
                        $first = false;
                        continue;
                    }
                    $query->orWhere("value", "like", $configOption);
                }
            });
        }))->where("userid", "=", $this->clientId)->where("domainstatus", "=", "Active")->get();
        $accounts = array();
        foreach ($services as $serviceModel) {
            if ($serviceModel->product()->first()->module == "marketconnect") {
                $type = explode("_", $serviceModel->product()->first()->moduleConfigOption1);
                $accounts[$type[0]][] = array("type" => "service", "id" => $serviceModel->id, "domain" => $serviceModel->domain);
            }
            foreach ($serviceModel->addons as $addon) {
                foreach ($addon->productAddon->moduleConfiguration as $moduleConfiguration) {
                    $type = explode("_", $moduleConfiguration->value);
                    $accounts[$type[0]][] = array("type" => "addon", "id" => $addon->id, "domain" => $serviceModel->domain);
                }
            }
        }
        return isset($accounts[$serviceName]) ? $accounts[$serviceName] : array();
    }
}

?>