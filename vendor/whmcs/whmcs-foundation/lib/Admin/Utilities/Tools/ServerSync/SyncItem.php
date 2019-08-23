<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Utilities\Tools\ServerSync;

class SyncItem
{
    protected $moduleValues = array();
    protected $uniqueIdField = NULL;
    protected $productField = NULL;
    protected $services = NULL;
    public function __construct(array $moduleValues, $uniqueIdField, $servicesCollection, $productField)
    {
        $this->moduleValues = $moduleValues;
        $this->uniqueIdField = $uniqueIdField;
        $this->productField = $productField;
        $this->services = $this->determineServiceMatches($uniqueIdField, $servicesCollection);
    }
    protected function determineServiceMatches($uniqueIdField, $servicesCollection)
    {
        $uniqueIdentifier = $this->getUniqueIdentifier();
        if ($uniqueIdField == "username") {
            return $servicesCollection->where("username", $uniqueIdentifier);
        }
        if (substr($uniqueIdField, 0, 12) == "customfield.") {
            $customFieldName = substr($uniqueIdField, 12);
            $serviceProductIds = $servicesCollection->pluck("packageid");
            $customFields = \WHMCS\CustomField::where("type", "product")->where("fieldname", $customFieldName)->whereIn("relid", $serviceProductIds)->get();
            $customFields2 = \WHMCS\CustomField::where("type", "product")->where("fieldname", "LIKE", $customFieldName . "|%")->whereIn("relid", $serviceProductIds)->get();
            $customFields = $customFields->merge($customFields2);
            $serviceIdsToInclude = array();
            if (0 < $customFields->count()) {
                $serviceIds = $servicesCollection->pluck("id");
                foreach ($customFields as $field) {
                    $customFieldId = $field->id;
                    $matchingServiceIds = \WHMCS\CustomField\CustomFieldValue::where("fieldid", $customFieldId)->where("value", $uniqueIdentifier)->pluck("relid");
                    foreach ($matchingServiceIds as $id) {
                        $serviceIdsToInclude[] = $id;
                    }
                }
            }
            return $servicesCollection->whereIn("id", $serviceIdsToInclude);
        } else {
            if ($uniqueIdField == "domain" || !$uniqueIdField) {
                return $servicesCollection->where("domain", $uniqueIdentifier);
            }
            throw new \WHMCS\Exception("Unsupported unique identifier field provided by module: \"" . $uniqueIdField . "\"");
        }
    }
    public function getUniqueIdentifier()
    {
        return $this->moduleValues["uniqueIdentifier"];
    }
    public function getName()
    {
        return $this->moduleValues["name"];
    }
    public function getPrimaryIp()
    {
        return $this->moduleValues["primaryip"];
    }
    public function getProduct()
    {
        return $this->moduleValues["product"];
    }
    public function getStatus()
    {
        return $this->moduleValues["status"];
    }
    public function getDomain()
    {
        return (string) $this->moduleValues["domain"];
    }
    public function getUsername()
    {
        return $this->moduleValues["username"];
    }
    public function getEmail()
    {
        return $this->moduleValues["email"];
    }
    public function getProductField()
    {
        return $this->productField;
    }
    public function getCreated()
    {
        return \WHMCS\Carbon::createFromFormat("Y-m-d H:i:s", $this->moduleValues["created"])->format("Y-m-d");
    }
    public function getServices()
    {
        $return = array();
        foreach ($this->services as $service) {
            $return[] = new ServiceItem($service, $this, $this->uniqueIdField, $this->productField);
        }
        return $return;
    }
    public function getServicesCount()
    {
        return count($this->services);
    }
    public function hasMatches()
    {
        return 0 < $this->getServicesCount();
    }
    public function hasMultipleMatches()
    {
        return 1 < $this->getServicesCount();
    }
    public function hasExactMatch()
    {
        foreach ($this->getServices() as $service) {
            if ($service->hasUniqueIdMatch() && $service->hasPrimaryIpMatch() && $service->hasProductMatch() && $service->hasUsernameMatch() && $service->hasStatusMatch() && $service->hasCreatedMatch()) {
                return true;
            }
        }
        return false;
    }
    public function getMatches()
    {
        $rankedMatches = array();
        foreach ($this->getServices() as $service) {
            $rank = 0;
            if ($service->hasUniqueIdMatch()) {
                $rank++;
            }
            if ($service->hasPrimaryIpMatch()) {
                $rank++;
            }
            if ($service->hasProductMatch()) {
                $rank++;
            }
            if ($service->hasUsernameMatch()) {
                $rank++;
            }
            if ($service->hasStatusMatch()) {
                $rank++;
            }
            if ($service->hasCreatedMatch()) {
                $rank++;
            }
            if (in_array($service->getStatus(), array(\WHMCS\Service\Status::CANCELLED, \WHMCS\Service\Status::TERMINATED))) {
                $rank = 0;
            }
            $rankedMatches[$rank][] = $service;
        }
        krsort($rankedMatches, SORT_NUMERIC);
        return collect($rankedMatches)->flatten();
    }
}

?>