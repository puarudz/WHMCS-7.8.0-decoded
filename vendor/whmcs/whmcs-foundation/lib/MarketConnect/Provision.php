<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect;

class Provision
{
    protected $controllerMap = array("rapidssl" => "WHMCS\\MarketConnect\\Services\\Symantec", "geotrust" => "WHMCS\\MarketConnect\\Services\\Symantec", "symantec" => "WHMCS\\MarketConnect\\Services\\Symantec", "spamexperts" => "WHMCS\\MarketConnect\\Services\\SpamExperts", "weebly" => "WHMCS\\MarketConnect\\Services\\Weebly", "sitelock" => "WHMCS\\MarketConnect\\Services\\Sitelock", "codeguard" => "WHMCS\\MarketConnect\\Services\\CodeGuard");
    protected $model = NULL;
    const AUTO_INSTALL_PANELS = array("cpanel", "directadmin", "plesk");
    public static function factoryFromModel($model)
    {
        $provision = new self();
        $provision->setModel($model);
        return $provision;
    }
    public function setModel($model)
    {
        $this->model = $model;
    }
    protected function getServiceIdentifier()
    {
        if ($this->model instanceof \WHMCS\Service\Service) {
            return $this->model->product->moduleConfigOption1;
        }
        if ($this->model instanceof \WHMCS\Service\Addon) {
            $moduleConfiguration = $this->model->productAddon->moduleConfiguration;
            foreach ($moduleConfiguration as $moduleConfigureValue) {
                if ($moduleConfigureValue->settingName == "configoption1") {
                    return $moduleConfigureValue->value;
                }
            }
        } else {
            if ($this->model instanceof \WHMCS\Product\Product) {
                return $this->model->moduleConfigOption1;
            }
            if ($this->model instanceof \WHMCS\Product\Addon) {
                $moduleConfiguration = $this->model->moduleConfiguration;
                foreach ($moduleConfiguration as $moduleConfigureValue) {
                    if ($moduleConfigureValue->settingName == "configoption1") {
                        return $moduleConfigureValue->value;
                    }
                }
            }
        }
        return "";
    }
    protected function getServiceIdentifierPrefix()
    {
        $serviceId = $this->getServiceIdentifier();
        $serviceParts = explode("_", $serviceId, 2);
        return $serviceParts[0];
    }
    protected function getServiceController()
    {
        $serviceIdentifierPrefix = $this->getServiceIdentifierPrefix();
        if (!array_key_exists($serviceIdentifierPrefix, $this->controllerMap)) {
            throw new \WHMCS\Exception("Unrecognised service \"" . $serviceIdentifierPrefix . "\". Please ensure you are running the latest version of WHMCS.");
        }
        $className = $this->controllerMap[$serviceIdentifierPrefix];
        return new $className($this->model);
    }
    public function provision(array $params)
    {
        return $this->getServiceController()->provision($this->model, $params);
    }
    public function configure(array $params)
    {
        return $this->getServiceController()->configure($this->model, $params);
    }
    public function cancel()
    {
        return $this->getServiceController()->cancel($this->model);
    }
    public function install()
    {
        return $this->getServiceController()->install($this->model);
    }
    public function renew(array $params)
    {
        return $this->getServiceController()->renew($this->model, $params);
    }
    public function adminManagementButtons($params)
    {
        return $this->getServiceController()->adminManagementButtons($params);
    }
    public function adminServicesTabOutput($params)
    {
        return $this->getServiceController()->adminServicesTabOutput($params);
    }
    public function clientAreaAllowedFunctions($params)
    {
        return $this->getServiceController()->clientAreaAllowedFunctions($params);
    }
    public function clientAreaOutput($params)
    {
        return $this->getServiceController()->clientAreaOutput($params);
    }
    public function isEligibleForUpgrade()
    {
        return $this->getServiceController()->isEligibleForUpgrade();
    }
    public function getServiceType()
    {
        return $this->getServiceIdentifierPrefix();
    }
    public function generateCsr()
    {
        if (in_array($this->getServiceType(), array("symantec", "rapidssl", "geotrust")) && $this->model instanceof \WHMCS\Service\Addon) {
            return $this->getServiceController()->generateCsr($this->model, \WHMCS\Module\Server::factoryFromModel($this->model->service));
        }
        return array();
    }
    public static function findRelatedHostingService(\WHMCS\Service\Service $model)
    {
        $domainCheck = array();
        $domainCheck[] = $model->domain;
        if (substr($model->domain, 0, 4) == "www.") {
            $domainCheck[] = substr($model->domain, 4);
        } else {
            $domainCheck[] = "www." . $model->domain;
        }
        return \WHMCS\Service\Service::whereHas("product", function ($query) {
            $query->whereIn("servertype", self::AUTO_INSTALL_PANELS);
        })->with("product")->whereIn("domain", $domainCheck)->where("id", "!=", $model->id)->where("userid", "=", $model->clientId)->where("domainstatus", "=", "Active")->first();
    }
    public function updateFtpDetails(array $params)
    {
        if (in_array($this->getServiceType(), array("weebly", "codeguard"))) {
            return $this->getServiceController()->updateFtpDetails($params);
        }
        return array();
    }
    public function emailMergeData(array $params)
    {
        return $this->getServiceController()->emailMergeData($params);
    }
    public function isSslProduct()
    {
        return $this->getServiceController()->isSslProduct();
    }
}

?>