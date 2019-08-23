<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect\Services;

class Weebly extends AbstractService
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
        $relatedHostingService = null;
        if ($model instanceof \WHMCS\Service\Service) {
            $relatedHostingService = \WHMCS\MarketConnect\Provision::findRelatedHostingService($model);
        }
        $domainName = $model instanceof \WHMCS\Service\Addon ? $model->service->domain : $model->domain;
        $client = $model->client;
        $configure = array("order_number" => $orderNumber, "domain" => $domainName, "companyname" => \WHMCS\Config\Setting::getValue("CompanyName"), "companyurl" => \WHMCS\Config\Setting::getValue("Domain"), "email" => $client->email, "upgrade_url" => fqdnRoutePath("store-weebly-upgrade"));
        $emailRelatedId = $model->id;
        $ftpRequired = true;
        if ($model instanceof \WHMCS\Service\Addon || $relatedHostingService instanceof \WHMCS\Service\Service) {
            $parentModel = $model instanceof \WHMCS\Service\Addon ? $model->service : $relatedHostingService;
            $ftpUsername = $parentModel->username;
            $ftpPassword = decrypt($parentModel->password);
            $ftpPath = "/";
            $configure["ftp_host"] = $domainName;
            $configure["ftp_username"] = $ftpUsername;
            $configure["ftp_password"] = $ftpPassword;
            $configure["ftp_path"] = $ftpPath;
            $configure["server_module"] = $parentModel->product->module;
            $emailRelatedId = $parentModel->id;
            switch ($parentModel->product->module) {
                case "cpanel":
                case "directadmin":
                    $serverInterface = \WHMCS\Module\Server::factoryFromModel($parentModel);
                    $ftpUsername = "weeblya" . $model->id;
                    $ftpPassword = generateFriendlyPassword();
                    $serverInterface->call("CreateFTPAccount", array("ftpUsername" => $ftpUsername, "ftpPassword" => $ftpPassword));
                    $ftpUsername = $ftpUsername . "@" . $domainName;
                    $configure["ftp_username"] = $ftpUsername;
                    $configure["ftp_password"] = $ftpPassword;
                    $ftpRequired = false;
                    break;
                case "plesk":
                    $configure["ftp_path"] = "/httpdocs";
                    break;
            }
        } else {
            $ftpUsername = "";
            $ftpPassword = "";
            $ftpPath = "/";
        }
        $serviceProperties->save(array("FTP Host" => $domainName, "FTP Username" => $ftpUsername, "FTP Password" => array("type" => "password", "value" => $ftpPassword), "FTP Path" => $ftpPath));
        $api = new \WHMCS\MarketConnect\Api();
        $response = $api->configure($configure);
        if (array_key_exists("error", $response)) {
            throw new \WHMCS\Exception($response["error"]);
        }
        $emailTemplate = "Weebly Welcome Email";
        if ($model instanceof \WHMCS\Service\Addon && $model->productAddon->welcomeEmailTemplateId) {
            $emailTemplate = $model->productAddon->welcomeEmailTemplate;
        } else {
            if ($model instanceof \WHMCS\Service\Service && $model->product->welcomeEmailTemplateId) {
                $emailTemplate = $model->product->welcomeEmailTemplate;
            }
        }
        sendMessage($emailTemplate, $emailRelatedId, array("configuration_required" => $ftpRequired));
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
        return array("manage_order", "update_ftp_details");
    }
    public function clientAreaOutput(array $params)
    {
        $orderNumber = marketconnect_GetOrderNumber($params);
        if (!$orderNumber || $params["status"] != "Active") {
            return "";
        }
        $serviceId = $params["serviceid"];
        $addonId = array_key_exists("addonId", $params) ? $params["addonId"] : 0;
        $update = \Lang::trans("marketConnect.weebly.updateFtp");
        if (array_key_exists("get_form", $params) || \App::isInRequest("get_form") && \App::getFromRequest("get_form") == 1) {
            $ftpHost = \Lang::trans("marketConnect.weebly.ftpHost");
            $ftpUsername = \Lang::trans("marketConnect.weebly.ftpUsername");
            $ftpPassword = \Lang::trans("marketConnect.weebly.ftpPassword");
            $ftpPath = \Lang::trans("marketConnect.weebly.ftpPath");
            $update = \Lang::trans("marketConnect.weebly.updateFtp");
            $error = "ModuleError." . $serviceId . "." . $addonId;
            if ($error = \WHMCS\Session::getAndDelete($error)) {
                $error = "<div class=\"alert alert-danger update-feedback\" role=\"alert\">" . $error . "</div>";
            }
            $token = generate_token();
            $output = "<form method=\"POST\" autocomplete=\"off\" id=\"ftpWeebly\">\n    " . $token . "\n    <input type=\"hidden\" name=\"modop\" value=\"custom\" />\n    <input type=\"hidden\" name=\"a\" value=\"update_ftp_details\" />\n    <input type=\"hidden\" name=\"id\" value=\"" . $serviceId . "\" />\n    <input type=\"hidden\" name=\"addonId\" value=\"" . $addonId . "\" />\n    " . $error . "\n    <div class=\"row\">\n        <div class=\"col-md-3 text-right margin-bottom-5\">" . $ftpHost . "</div>\n        <div class=\"col-md-9 text-left margin-bottom-5\">\n            <input type=\"text\" name=\"ftpHost\" class=\"form-control\" placeholder=\"ftp.hostname.com\" />\n        </div>\n        <div class=\"col-md-3 text-right margin-bottom-5\">" . $ftpUsername . "</div>\n        <div class=\"col-md-9 text-left margin-bottom-5\">\n            <input type=\"text\" name=\"ftpUsername\" class=\"form-control\" placeholder=\"user@ftp.hostname.com\" />\n        </div>\n        <div class=\"col-md-3 text-right margin-bottom-5\">" . $ftpPassword . "</div>\n        <div class=\"col-md-9 text-left margin-bottom-5\">\n            <input type=\"password\" name=\"ftpPassword\" class=\"form-control\" placeholder=\"password\" />\n        </div>\n        <div class=\"col-md-3 text-right margin-bottom-5\">" . $ftpPath . "</div>\n        <div class=\"col-md-9 text-left margin-bottom-5\">\n            <input type=\"text\" name=\"ftpPath\" class=\"form-control\" placeholder=\"/\" />\n        </div>\n    </div>\n</form>";
            $outputData = array("body" => $output, "title" => $update);
            $return = new \WHMCS\Http\JsonResponse();
            $return->setData($outputData);
            $return->send();
            \WHMCS\Terminus::getInstance()->doExit();
        }
        $manageText = \Lang::trans("marketConnect.weebly.manage");
        $ftpLink = "clientarea.php?action=productdetails&id=" . $serviceId;
        if ($addonId) {
            $ftpLink .= "&addonId=" . $addonId;
        }
        $upgradeLabel = \Lang::trans("upgrade");
        $upgradeRoute = routePath("upgrade");
        $isProduct = (int) ($addonId == 0);
        $upgradeServiceId = 0 < $addonId ? $addonId : $serviceId;
        $webRoot = \WHMCS\Utility\Environment\WebHelper::getBaseUrl();
        return "<img src=\"" . $webRoot . "/assets/img/marketconnect/weebly/logo.png\" style=\"max-width:300px;\">\n<br><br>\n<form style=\"display:inline;\">\n    <input type=\"hidden\" name=\"modop\" value=\"custom\" />\n    <input type=\"hidden\" name=\"a\" value=\"manage_order\" />\n    <input type=\"hidden\" name=\"id\" value=\"" . $serviceId . "\" />\n    <input type=\"hidden\" name=\"addonId\" value=\"" . $addonId . "\" />\n    <button class=\"btn btn-default btn-service-sso\">\n        <span class=\"loading hidden\">\n            <i class=\"fas fa-spinner fa-spin\"></i>\n        </span>\n        <span class=\"text\">" . $manageText . "</span>\n    </button>\n    <a href=\"" . $ftpLink . "&get_form=1\" class=\"btn btn-default open-modal\" data-btn-submit-id=\"weeblyFtpUpdate\" data-btn-submit-label=\"" . $update . "\">\n        " . $update . "\n    </a>\n    <span class=\"login-feedback\"></span>\n</form>\n<form method=\"post\" action=\"" . $upgradeRoute . "\" style=\"display:inline;\">\n    <input type=\"hidden\" name=\"isproduct\" value=\"" . $isProduct . "\">\n    <input type=\"hidden\" name=\"serviceid\" value=\"" . $upgradeServiceId . "\">\n    <button type=\"submit\" class=\"btn btn-default\">\n        " . $upgradeLabel . "\n    </button>\n</form>";
    }
    public function adminServicesTabOutput(array $params, \WHMCS\MarketConnect\OrderInformation $orderInfo = NULL, array $actionBtns = NULL)
    {
        $orderInfo = \WHMCS\MarketConnect\OrderInformation::factory($params);
        $actionBtns = array(array("icon" => "fa-cog", "label" => "Attempt Configuration", "class" => "btn-default", "moduleCommand" => "resend_configuration_data", "applicableStatuses" => array("Awaiting Configuration")), array("icon" => "fa-sign-in", "label" => "Login to Weebly Site Builder", "class" => "btn-default", "moduleCommand" => "admin_sso", "applicableStatuses" => array("Active")), array("icon" => "fa-upload", "label" => "Update FTP Publishing Credentials", "class" => "btn-default", "moduleCommand" => "update_ftp_details", "applicableStatuses" => array("Active")));
        return parent::adminServicesTabOutput($params, $orderInfo, $actionBtns);
    }
    public function isEligibleForUpgrade()
    {
        return true;
    }
    public function updateFtpDetails(array $params)
    {
        $serviceId = $params["serviceid"];
        $addonId = 0;
        if (array_key_exists("addonId", $params)) {
            $addonId = $params["addonId"];
        }
        $returnKey = "growl";
        $clientArea = false;
        if (defined("CLIENTAREA")) {
            $returnKey = "jsonResponse";
            $clientArea = true;
        }
        try {
            $orderNumber = marketconnect_GetOrderNumber($params);
            if (!$orderNumber) {
                throw new \WHMCS\Exception\Module\NotServicable("You must provision this service before attempting to manage it");
            }
            $model = $params["model"];
            $serviceProperties = $model->serviceProperties;
            $api = new \WHMCS\MarketConnect\Api();
            $ftpHost = $serviceProperties->get("FTP Host");
            $ftpUsername = $serviceProperties->get("FTP Username");
            $ftpPassword = $serviceProperties->get("FTP Password");
            $ftpPath = $serviceProperties->get("FTP Path");
            if ($clientArea) {
                $ftpHost = \App::getFromRequest("ftpHost");
                $ftpUsername = \App::getFromRequest("ftpUsername");
                $ftpPassword = \App::getFromRequest("ftpPassword");
                $ftpPath = \App::getFromRequest("ftpPath");
            }
            if (!$ftpHost) {
                throw new \WHMCS\Exception\Module\NotServicable("FTP Host is required");
            }
            if (!$ftpUsername) {
                throw new \WHMCS\Exception\Module\NotServicable("FTP Username is required");
            }
            if (!$ftpPassword) {
                throw new \WHMCS\Exception\Module\NotServicable("FTP Password is required");
            }
            if (!$ftpPath) {
                throw new \WHMCS\Exception\Module\NotServicable("FTP Path is required. For the base directory, use '/'");
            }
            if ($clientArea) {
                $serviceProperties->save(array("FTP Host" => \App::getFromRequest("ftpHost"), "FTP Username" => \App::getFromRequest("ftpUsername"), "FTP Password" => array("type" => "password", "value" => \App::getFromRequest("ftpPassword")), "FTP Path" => \App::getFromRequest("ftpPath")));
            }
            $response = $api->extra("setftpcredentials", array("order_number" => $orderNumber, "ftp_host" => $ftpHost, "ftp_username" => $ftpUsername, "ftp_password" => $ftpPassword, "ftp_path" => $ftpPath));
            return array($returnKey => array("dismiss" => true, "message" => "FTP credentials updated successfully!"));
        } catch (\Exception $e) {
            $params["get_form"] = true;
            \WHMCS\Session::set("ModuleError." . $serviceId . "." . $addonId, $e->getMessage());
            return array($returnKey => array("type" => "error", "message" => $e->getMessage(), "body" => marketconnect_ClientArea($params)));
        }
    }
}

?>