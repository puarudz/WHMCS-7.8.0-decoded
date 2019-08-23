<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect;

class ServicesFeed
{
    protected $services = NULL;
    public function __construct()
    {
        $services = (new \WHMCS\TransientData())->retrieve("MarketConnectServices");
        if ($services) {
            $services = json_decode($services, true);
        }
        if (is_null($services) || !is_array($services)) {
            try {
                $services = $this->performRemoteFetch();
            } catch (\Exception $e) {
            }
        }
        $this->services = $services;
        $this->convertRecommendedRrpPrices(1);
    }
    protected function performRemoteFetch()
    {
        if (MarketConnect::isAccountConfigured()) {
            $api = new Api();
            $services = $api->services();
            (new \WHMCS\TransientData())->store("MarketConnectServices", json_encode($services), 7 * 24 * 60 * 60);
            return $services;
        }
        throw new \WHMCS\Exception("Account not configured");
    }
    protected function getServicesCache()
    {
        return is_array($this->services) ? $this->services : array();
    }
    protected function isGroupIdInFeed($id)
    {
        foreach ($this->getServicesCache() as $group) {
            if ($group["id"] == $id) {
                return true;
            }
        }
        return false;
    }
    public function getServicesByGroupId($id)
    {
        foreach ($this->getServicesCache() as $group) {
            if ($group["id"] == $id) {
                return collect($group["services"]);
            }
        }
        return collect(array());
    }
    public function getEmulationOfConfiguredProducts($groupSlug)
    {
        if ($groupSlug == "symantec") {
            $groupSlugs = array("rapidssl", "geotrust", "symantec");
        } else {
            $groupSlugs = array($groupSlug);
        }
        $productCollection = new \Illuminate\Support\Collection();
        foreach ($groupSlugs as $slug) {
            if (!$this->isGroupIdInFeed($slug)) {
                try {
                    $this->services = $this->performRemoteFetch();
                } catch (\Exception $e) {
                }
            }
            foreach ($this->getServicesByGroupId($slug) as $listing) {
                $product = new \WHMCS\Product\Product();
                $product->name = $listing["display_name"];
                $product->description = $listing["description"];
                $product->moduleConfigOption1 = $listing["id"];
                $product->isHidden = false;
                $productCollection->push($product);
            }
        }
        return $productCollection;
    }
    public function isNotAvailable()
    {
        return is_null($this->services);
    }
    public function getTerms($productKey = NULL)
    {
        $serviceTerms = array();
        foreach ($this->getServicesCache() as $group) {
            if (isset($group["services"])) {
                foreach ($group["services"] as $serviceData) {
                    $serviceTerms[$serviceData["id"]] = $serviceData["terms"];
                }
            }
        }
        if (is_null($productKey)) {
            return $serviceTerms;
        }
        return isset($serviceTerms[$productKey]) ? $serviceTerms[$productKey] : array();
    }
    public function getPricingMatrix($products)
    {
        $availableTerms = array();
        $terms = array();
        foreach ($products as $product) {
            $termData = collect($this->getTerms($product));
            foreach ($termData->pluck("term") as $term) {
                if (!in_array($term, $availableTerms)) {
                    $availableTerms[] = $term;
                }
            }
            $terms[$product] = $termData;
        }
        sort($availableTerms);
        if (in_array(0, $availableTerms)) {
            unset($availableTerms[0]);
            $availableTerms[] = 0;
        }
        $pricingMatrix = array();
        foreach ($terms as $product => $termData) {
            $data = array();
            foreach ($availableTerms as $term) {
                $data[$term] = array();
            }
            foreach ($termData as $termDataArray) {
                $data[$termDataArray["term"]] = $termDataArray;
            }
            $pricingMatrix[$product] = $data;
        }
        return $pricingMatrix;
    }
    public function getPricing($keyToFetch = "price")
    {
        $pricing = array();
        foreach ($this->getServicesCache() as $group) {
            if (isset($group["services"])) {
                foreach ($group["services"] as $service) {
                    foreach ($service["terms"] as $key => $term) {
                        $pricing[$service["id"]][$key] = $term[$keyToFetch];
                    }
                }
            }
        }
        return $pricing;
    }
    public function getCostPrice($productKey)
    {
        $pricing = $this->getPricing();
        return isset($pricing[$productKey][0]) ? "\$" . $pricing[$productKey][0] : "-";
    }
    public function getRecommendedRetailPrice($productKey)
    {
        $pricing = $this->getPricing("recommendedRrp");
        return isset($pricing[$productKey][0]) ? "\$" . $pricing[$productKey][0] : "-";
    }
    public function convertRecommendedRrpPrices($rate)
    {
        $pricing = array();
        foreach ($this->getServicesCache() as $groupKey => $group) {
            if (isset($group["services"])) {
                foreach ($group["services"] as $serviceKey => $service) {
                    foreach ($service["terms"] as $termKey => $term) {
                        $this->services[$groupKey]["services"][$serviceKey]["terms"][$termKey]["recommendedRrpDefaultCurrency"] = 0 < $rate ? format_as_currency($term["recommendedRrp"] / $rate) : 0;
                    }
                }
            }
        }
    }
    public static function removeCache()
    {
        (new \WHMCS\TransientData())->delete("MarketConnectServices");
    }
}

?>