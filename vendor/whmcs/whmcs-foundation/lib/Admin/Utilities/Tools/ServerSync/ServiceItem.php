<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Utilities\Tools\ServerSync;

class ServiceItem
{
    protected $service = NULL;
    protected $syncItem = NULL;
    protected $uniqueIdField = NULL;
    protected $productField = NULL;
    public function __construct(\WHMCS\Service\Service $service, SyncItem $syncItem, $uniqueIdField, $productField)
    {
        $this->service = $service;
        $this->syncItem = $syncItem;
        $this->uniqueIdField = $uniqueIdField;
        $this->productField = $productField;
    }
    public function getService()
    {
        return $this->service;
    }
    protected function syncItem()
    {
        return $this->syncItem;
    }
    public function getId()
    {
        return $this->getService()->id;
    }
    public function getUniqueIdentifier()
    {
        $uniqueIdField = $this->uniqueIdField;
        if ($uniqueIdField == "username") {
            return $this->getService()->username;
        }
        if (substr($uniqueIdField, 0, 12) == "customfield.") {
            $customFieldName = substr($uniqueIdField, 12);
            return $this->getService()->serviceProperties->get($customFieldName);
        }
        if ($uniqueIdField == "domain" || !$uniqueIdField) {
            return $this->getService()->domain;
        }
        throw new \WHMCS\Exception("Unsupported unique identifier field provided by module: \"" . $uniqueIdField . "\"");
    }
    public function getName()
    {
        return $this->getService()->domain;
    }
    public function getPrimaryIp()
    {
        $ip = $this->getService()->dedicatedip;
        if (!$ip) {
            $ip = $this->getService()->serverModel->ipAddress;
        }
        return $ip;
    }
    public function getProduct()
    {
        $product = $this->getService()->product;
        $productField = $this->productField;
        if (!empty($productField)) {
            return $product->{$productField};
        }
        return $product->name;
    }
    public function getStatus()
    {
        return $this->getService()->status;
    }
    public function getUsername()
    {
        return $this->getService()->username;
    }
    public function getCreated()
    {
        return $this->getService()->registrationDate->format("Y-m-d");
    }
    public function hasUniqueIdMatch()
    {
        return $this->getUniqueIdentifier() == $this->syncItem()->getUniqueIdentifier();
    }
    public function hasPrimaryIpMatch()
    {
        return $this->getPrimaryIp() == $this->syncItem()->getPrimaryIp();
    }
    public function hasProductMatch()
    {
        return $this->getProduct() == $this->syncItem()->getProduct();
    }
    public function hasUsernameMatch()
    {
        return $this->getUsername() == $this->syncItem()->getUsername();
    }
    public function hasStatusMatch()
    {
        return $this->getStatus() == $this->syncItem()->getStatus();
    }
    public function hasCreatedMatch()
    {
        return $this->getCreated() == $this->syncItem()->getCreated();
    }
    public function getProductField()
    {
        return $this->productField;
    }
    public function isTerminated()
    {
        return in_array($this->getStatus(), array(\WHMCS\Service\Status::CANCELLED, \WHMCS\Service\Status::TERMINATED));
    }
}

?>