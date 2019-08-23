<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect\Services;

class Sitelock extends AbstractService
{
    public function provision($model, array $params = NULL)
    {
        $this->configure($model, $params);
    }
    public function configure($model, array $params = NULL)
    {
        $serviceProperties = $model->serviceProperties;
        $orderNumber = $serviceProperties->get("Order Number");
        if (!$orderNumber) {
            throw new \WHMCS\Exception\Module\NotServicable("You must provision this service before attempting to configure it");
        }
        $domainName = "";
        $parentModel = null;
        if ($model instanceof \WHMCS\Service\Addon) {
            $parentModel = $model->service;
            $domainName = $parentModel->domain;
            $emailRelatedId = $parentModel->id;
        } else {
            if ($model instanceof \WHMCS\Service\Service) {
                $parentModel = \WHMCS\MarketConnect\Provision::findRelatedHostingService($model);
                if (is_null($parentModel)) {
                    $domainName = $model->domain;
                } else {
                    $domainName = $parentModel->domain;
                }
                $emailRelatedId = $model->id;
            }
        }
        if (!$domainName) {
            throw new \WHMCS\Exception\Module\NotServicable("A domain name is required for configuration");
        }
        $configure = array("order_number" => $orderNumber, "domain" => $domainName, "domain_email" => $model->client->email, "customer_name" => $model->client->fullName, "customer_email" => $model->client->email);
        $api = new \WHMCS\MarketConnect\Api();
        $response = $api->configure($configure);
        $ftpRequired = false;
        $ftpAutoProvisioned = false;
        if ($response["data"]["requiresFtp"]) {
            $ftpRequired = true;
            $ftpAutoProvisioned = $this->provisionFtp($model, $parentModel);
        }
        $dnsRequired = false;
        $dnsAutoProvisioned = false;
        $dnsRecordEmailOutput = "";
        if ($response["data"]["requiresDns"]) {
            $dnsRequired = true;
            $dnsRecordsToProvision = isset($response["data"]["dnsRecords"]) ? $response["data"]["dnsRecords"] : null;
            if (is_array($dnsRecordsToProvision)) {
                $dnsRecordEmailOutput = array();
                foreach ($dnsRecordsToProvision as $record) {
                    $dnsRecordEmailOutput[] = "Type: " . $record["type"] . "<br>" . PHP_EOL . "Name: " . $record["name"] . "<br>" . PHP_EOL . "Value: " . $record["value"] . "<br>" . PHP_EOL;
                }
                $dnsRecordEmailOutput = str_repeat("-", 60) . implode(str_repeat("-", 60), $dnsRecordEmailOutput) . str_repeat("-", 60);
                $dnsAutoProvisioned = $this->provisionDns($model, $parentModel, $dnsRecordsToProvision);
            }
        }
        $emailTemplate = "SiteLock Welcome Email";
        if ($model instanceof \WHMCS\Service\Addon && $model->productAddon->welcomeEmailTemplateId) {
            $emailTemplate = $model->productAddon->welcomeEmailTemplate;
        } else {
            if ($model instanceof \WHMCS\Service\Service && $model->product->welcomeEmailTemplateId) {
                $emailTemplate = $model->product->welcomeEmailTemplate;
            }
        }
        sendMessage($emailTemplate, $emailRelatedId, array("sitelock_requires_ftp" => $ftpRequired, "sitelock_ftp_auto_provisioned" => $ftpAutoProvisioned, "sitelock_requires_dns" => $dnsRequired, "sitelock_dns_auto_provisioned" => $dnsAutoProvisioned, "sitelock_dns_host_record_info" => $dnsRecordEmailOutput));
    }
    protected function provisionFtp($model, $parentModel)
    {
        if (is_null($parentModel)) {
            return false;
        }
        $ftpHost = $parentModel->domain;
        $ftpPath = "/";
        switch ($parentModel->product->module) {
            case "cpanel":
            case "directadmin":
                $serverInterface = \WHMCS\Module\Server::factoryFromModel($parentModel);
                $ftpUsername = "sitelock" . $model->id;
                $ftpPassword = (new \WHMCS\Utility\Random())->string(4, 4, 2, 2);
                $serverInterface->call("CreateFTPAccount", array("ftpUsername" => $ftpUsername, "ftpPassword" => $ftpPassword));
                $ftpUsername = $ftpUsername . "@" . $parentModel->domain;
                break;
            case "plesk":
                $ftpUsername = $parentModel->username;
                $ftpPassword = decrypt($parentModel->password);
                $ftpPath = "/httpdocs";
                break;
            default:
                return false;
        }
        $model->serviceProperties->save(array("FTP Host" => $ftpHost, "FTP Username" => $ftpUsername, "FTP Password" => array("type" => "password", "value" => $ftpPassword), "FTP Path" => $ftpPath));
        $api = new \WHMCS\MarketConnect\Api();
        $api->extra("setftpcredentials", array("order_number" => $model->serviceProperties->get("Order Number"), "ftp_host" => $ftpHost, "ftp_username" => $ftpUsername, "ftp_password" => $ftpPassword, "ftp_path" => $ftpPath));
        return true;
    }
    protected function provisionDns($model, $parentModel, $dnsRecordsToProvision)
    {
        if (is_null($parentModel)) {
            return false;
        }
        switch ($parentModel->product->module) {
            case "cpanel":
                $serverInterface = \WHMCS\Module\Server::factoryFromModel($parentModel);
                try {
                    $serverInterface->call("ModifyDns", array("dnsRecordsToProvision" => $dnsRecordsToProvision));
                    return true;
                } catch (\Exception $e) {
                    return false;
                }
                break;
            case "directadmin":
            case "plesk":
            default:
                return false;
        }
    }
    public function cancel($model)
    {
        $serviceProperties = $model->serviceProperties;
        $orderNumber = $serviceProperties->get("Order Number");
        if (!$orderNumber) {
            throw new \WHMCS\Exception\Module\NotServicable("You must provision this service before attempting to manage it");
        }
        $api = new \WHMCS\MarketConnect\Api();
        $response = $api->cancel($orderNumber);
        if (array_key_exists("error", $response)) {
            throw new \WHMCS\Exception($response["error"]);
        }
    }
    public function clientAreaAllowedFunctions($params)
    {
        if ($params["status"] != "Active") {
            return array();
        }
        return array("manage_order");
    }
    public function clientAreaOutput(array $params)
    {
        $orderNumber = marketconnect_GetOrderNumber($params);
        if (!$orderNumber) {
            return "";
        }
        $serviceId = $params["serviceid"];
        $addonId = array_key_exists("addonId", $params) ? $params["addonId"] : 0;
        $manageText = \Lang::trans("store.sitelock.manageService");
        $webRoot = \WHMCS\Utility\Environment\WebHelper::getBaseUrl();
        $upgradeLabel = \Lang::trans("upgrade");
        $upgradeRoute = routePath("upgrade");
        $isProduct = (int) ($addonId == 0);
        $upgradeServiceId = 0 < $addonId ? $addonId : $serviceId;
        return "<img src=\"" . $webRoot . "/assets/img/marketconnect/sitelock/logo.png\" style=\"max-width:300px;\">\n<br><br>\n<form style=\"display:inline;\">\n    <input type=\"hidden\" name=\"modop\" value=\"custom\" />\n    <input type=\"hidden\" name=\"a\" value=\"manage_order\" />\n    <input type=\"hidden\" name=\"id\" value=\"" . $serviceId . "\" />\n    <input type=\"hidden\" name=\"addonId\" value=\"" . $addonId . "\" />\n    <button class=\"btn btn-default btn-service-sso\">\n        <span class=\"loading hidden\">\n            <i class=\"fas fa-spinner fa-spin\"></i>\n        </span>\n        <span class=\"text\">" . $manageText . "</span>\n    </button>\n    <span class=\"login-feedback\"></span>\n</form>\n<form method=\"post\" action=\"" . $upgradeRoute . "\" style=\"display:inline;\">\n    <input type=\"hidden\" name=\"isproduct\" value=\"" . $isProduct . "\">\n    <input type=\"hidden\" name=\"serviceid\" value=\"" . $upgradeServiceId . "\">\n    <button type=\"submit\" class=\"btn btn-default\">\n        " . $upgradeLabel . "\n    </button>\n</form>";
    }
    public function adminServicesTabOutput(array $params, \WHMCS\MarketConnect\OrderInformation $orderInformation = NULL, array $actionButtons = NULL)
    {
        $orderInfo = \WHMCS\MarketConnect\OrderInformation::factory($params);
        $actionBtns = array(array("icon" => "fa-cog", "label" => "Attempt Configuration", "class" => "btn-default", "moduleCommand" => "resend_configuration_data", "applicableStatuses" => array("Awaiting Configuration")), array("icon" => "fa-sign-in", "label" => "Login to SiteLock Control Panel", "class" => "btn-default", "moduleCommand" => "admin_sso", "applicableStatuses" => array("Active")), array("icon" => "fa-upload", "label" => "Update FTP Access Credentials", "class" => "btn-default", "moduleCommand" => "update_ftp_details", "applicableStatuses" => array("Active")));
        return parent::adminServicesTabOutput($params, $orderInfo, $actionBtns);
    }
    public function isEligibleForUpgrade()
    {
        return true;
    }
}

?>