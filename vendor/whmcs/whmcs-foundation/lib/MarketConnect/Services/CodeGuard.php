<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect\Services;

class CodeGuard extends AbstractService
{
    public function provision($model, array $params = NULL)
    {
        $this->configure($model, $params);
    }
    private function getPanelSpecificConfigurationSettings(array $configure, $panel, \WHMCS\Module\Server $serverInterface, $domainName)
    {
        $excludeRules = array();
        switch ($panel) {
            case "cpanel":
                if ($configure["use_sftp"]) {
                    $docRoot = $serverInterface->call("GetDocRoot", array("domain" => $domainName));
                    $homeDir = dirname($docRoot);
                } else {
                    $homeDir = "";
                }
                $excludeRules = array($homeDir . "/www/*");
                break;
            case "directadmin":
                if ($configure["use_sftp"]) {
                    $username = str_replace(array("/", "\\", ".", ""), "", $configure["username"]);
                    $excludeRules = array("*/" . $username . "/public_html/*");
                } else {
                    $excludeRules = array("/public_html/*");
                }
                break;
        }
        if (!empty($excludeRules)) {
            $configure["exclude_rules"] = json_encode($excludeRules);
        }
        return $configure;
    }
    public function configure($model, array $params = NULL)
    {
        $serviceProperties = $model->serviceProperties;
        $orderNumber = $serviceProperties->get("Order Number");
        if (!$orderNumber) {
            throw new \WHMCS\Exception\Module\NotServicable("You must provision this service before attempting to configure it");
        }
        $emailRelatedId = $model->id;
        $relatedHostingService = null;
        if ($model instanceof \WHMCS\Service\Service) {
            $relatedHostingService = \WHMCS\MarketConnect\Provision::findRelatedHostingService($model);
        }
        $domainName = $model instanceof \WHMCS\Service\Addon ? $model->service->domain : $model->domain;
        $client = $model->client;
        $configure = array("order_number" => $orderNumber, "domain" => $domainName, "reseller_company_name" => \WHMCS\Config\Setting::getValue("CompanyName"), "reseller_whmcs_url" => \App::getSystemURL(), "reseller_support_email" => \WHMCS\Config\Setting::getValue("Email"), "customer_name" => $client->fullName, "customer_email" => $client->email, "username" => $params["username"], "password" => $params["password"], "use_sftp" => true);
        $connectionHostname = $configure["domain"];
        if (array_key_exists("service", $params) && count(0 < $params["service"])) {
            $serviceParams = $params["service"];
            if (array_key_exists("serverip", $serviceParams) && $serviceParams["serverip"]) {
                $connectionHostname = $serviceParams["serverip"];
            } else {
                if (array_key_exists("serverhostname", $serviceParams) && $serviceParams["serverhostname"]) {
                    $connectionHostname = $serviceParams["serverhostname"];
                }
            }
        }
        $testConnectionConfiguration = array("order_number" => $configure["order_number"], "domain" => $connectionHostname, "username" => $configure["username"]);
        $configure["connection_hostname"] = $connectionHostname;
        $api = new \WHMCS\MarketConnect\Api();
        if ($model instanceof \WHMCS\Service\Addon || $relatedHostingService instanceof \WHMCS\Service\Service) {
            $parentModel = $model instanceof \WHMCS\Service\Addon ? $model->service : $relatedHostingService;
            $serverInterface = \WHMCS\Module\Server::factoryFromModel($parentModel);
            $key = null;
            $keyData = null;
            if ($serverInterface->functionExists("list_ssh_keys")) {
                $callParams = array("key_name" => "code_guard" . $orderNumber);
                try {
                    $returnedKeys = $serverInterface->call("list_ssh_keys", $callParams);
                    $key = $returnedKeys[0]["name"];
                } catch (\Exception $e) {
                }
            }
            if (is_null($key) && $serverInterface->functionExists("generate_ssh_key")) {
                $callParams = array("key_name" => "code_guard" . $orderNumber, "bits" => "2048");
                try {
                    $serverInterface->call("generate_ssh_key", $callParams);
                    $key = "code_guard" . $orderNumber;
                } catch (\Exception $e) {
                    $key = null;
                }
            }
            if ($key && $serverInterface->functionExists("fetch_ssh_key")) {
                $callParams = array("key_name" => $key);
                $keyData = $serverInterface->call("fetch_ssh_key", $callParams);
            }
            if ($keyData) {
                $sshPort = 22;
                if ($serverInterface->functionExists("get_ssh_port")) {
                    $sshPort = $serverInterface->call("get_ssh_port");
                }
                $configure["ssh_key"] = $keyData["key"];
                if ($sshPort != 22) {
                    $configure["connection_port"] = $sshPort;
                }
                $testConnectionConfiguration["ssh_key"] = $configure["ssh_key"];
                if (array_key_exists("connection_port", $configure)) {
                    $testConnectionConfiguration["connection_port"] = $configure["connection_port"];
                }
                try {
                    $response = $api->testCodeGuardWebsiteConnection($testConnectionConfiguration);
                    if ($response["useSftp"] !== true) {
                        $keyData = null;
                    }
                } catch (\Exception $e) {
                    $keyData = null;
                }
            }
            if (is_null($keyData)) {
                try {
                    $configure["use_sftp"] = false;
                    switch ($parentModel->product->module) {
                        case "cpanel":
                        case "directadmin":
                            $ftpUsername = "codeguarda" . $model->id;
                            $ftpPassword = generateFriendlyPassword();
                            $serverInterface->call("CreateFTPAccount", array("ftpUsername" => $ftpUsername, "ftpPassword" => $ftpPassword));
                            $ftpUsername = $ftpUsername . "@" . $domainName;
                            $configure["username"] = $ftpUsername;
                            $configure["password"] = $ftpPassword;
                            break;
                        case "plesk":
                            $configure["username"] = $parentModel->username;
                            $configure["password"] = decrypt($parentModel->password);
                            break;
                    }
                } catch (\Exception $e) {
                }
            }
            $configure = $this->getPanelSpecificConfigurationSettings($configure, $parentModel->product->module, $serverInterface, $domainName);
        }
        $response = $api->configure($configure);
        $manualConfigCompletionRequired = $response["data"]["manualBackupConfigurationRequired"];
        $emailTemplate = "CodeGuard Welcome Email";
        if ($model instanceof \WHMCS\Service\Addon && $model->productAddon->welcomeEmailTemplateId) {
            $emailTemplate = $model->productAddon->welcomeEmailTemplate;
        } else {
            if ($model instanceof \WHMCS\Service\Service && $model->product->welcomeEmailTemplateId) {
                $emailTemplate = $model->product->welcomeEmailTemplate;
            }
        }
        sendMessage($emailTemplate, $emailRelatedId, $this->emailMergeData($params, array("configuration_required" => $manualConfigCompletionRequired)));
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
    public function clientAreaAllowedFunctions(array $params)
    {
        $orderNumber = marketconnect_GetOrderNumber($params);
        if (!$orderNumber || $params["status"] != "Active") {
            return array();
        }
        return array("manage_order");
    }
    public function clientAreaOutput(array $params)
    {
        $orderNumber = marketconnect_GetOrderNumber($params);
        if (!$orderNumber || $params["status"] != "Active") {
            return "";
        }
        $serviceId = $params["serviceid"];
        $addonId = array_key_exists("addonId", $params) ? $params["addonId"] : 0;
        $manageText = \Lang::trans("marketConnect.codeguard.manage");
        $ftpLink = "clientarea.php?action=productdetails&id=" . $serviceId;
        if ($addonId) {
            $ftpLink .= "&addonId=" . $addonId;
        }
        $upgradeLabel = \Lang::trans("upgrade");
        $upgradeRoute = routePath("upgrade");
        $isProduct = (int) ($addonId == 0);
        $upgradeServiceId = 0 < $addonId ? $addonId : $serviceId;
        $webRoot = \WHMCS\Utility\Environment\WebHelper::getBaseUrl();
        return "<img src=\"" . $webRoot . "/assets/img/marketconnect/codeguard/logo.png\" style=\"max-width:300px;\">\n<br><br>\n<form style=\"display:inline;\">\n    <input type=\"hidden\" name=\"modop\" value=\"custom\" />\n    <input type=\"hidden\" name=\"a\" value=\"manage_order\" />\n    <input type=\"hidden\" name=\"id\" value=\"" . $serviceId . "\" />\n    <input type=\"hidden\" name=\"addonId\" value=\"" . $addonId . "\" />\n    <button class=\"btn btn-default btn-service-sso\">\n        <span class=\"loading hidden\">\n            <i class=\"fas fa-spinner fa-spin\"></i>\n        </span>\n        <span class=\"text\">" . $manageText . "</span>\n    </button>\n    <span class=\"login-feedback\"></span>\n</form>\n<form method=\"post\" action=\"" . $upgradeRoute . "\" style=\"display:inline;\">\n    <input type=\"hidden\" name=\"isproduct\" value=\"" . $isProduct . "\">\n    <input type=\"hidden\" name=\"serviceid\" value=\"" . $upgradeServiceId . "\">\n    <button type=\"submit\" class=\"btn btn-default\">\n        " . $upgradeLabel . "\n    </button>\n</form>";
    }
    public function adminServicesTabOutput(array $params, \WHMCS\MarketConnect\OrderInformation $orderInformation = NULL, array $actionButtons = NULL)
    {
        $orderInfo = \WHMCS\MarketConnect\OrderInformation::factory($params);
        $actionBtns = array(array("icon" => "fa-cog", "label" => "Attempt Configuration", "class" => "btn-default", "moduleCommand" => "resend_configuration_data", "applicableStatuses" => array("Awaiting Configuration")), array("icon" => "fa-sign-in", "label" => "Login to CodeGuard Control Panel", "class" => "btn-default", "moduleCommand" => "admin_sso", "applicableStatuses" => array("Active")));
        return parent::adminServicesTabOutput($params, $orderInfo, $actionBtns);
    }
    public function emailMergeData(array $params, array $preCalculatedMergeData = array())
    {
        $configurationRequired = true;
        if (array_key_exists("configuration_required", $preCalculatedMergeData)) {
            $configurationRequired = $preCalculatedMergeData["configuration_required"];
        }
        return array("configuration_required" => $configurationRequired);
    }
    public function isEligibleForUpgrade()
    {
        return true;
    }
}

?>