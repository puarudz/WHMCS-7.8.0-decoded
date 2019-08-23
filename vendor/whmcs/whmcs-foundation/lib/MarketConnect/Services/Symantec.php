<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect\Services;

class Symantec extends AbstractService
{
    public function provision($model, array $params = NULL)
    {
        $userId = $model->clientId;
        $serviceId = 0;
        $addonId = 0;
        $certificateType = "";
        if ($model instanceof \WHMCS\Service\Service) {
            $serviceId = $model->id;
            $addonId = 0;
            $certificateType = $model->product->name;
        } else {
            if ($model instanceof \WHMCS\Service\Addon) {
                $serviceId = $model->service->id;
                $addonId = $model->id;
                $certificateType = $model->productAddon->name;
            }
        }
        $sslOrder = \WHMCS\Service\Ssl::firstOrCreate(array("userid" => $userId, "serviceid" => $serviceId, "addon_id" => $addonId, "module" => "marketconnect"));
        $serviceProperties = $model->serviceProperties;
        $orderNumber = $serviceProperties->get("Order Number");
        if (!$orderNumber) {
            throw new \WHMCS\Exception\Module\NotServicable("You must provision this service before attempting to manage it.");
        }
        $sslOrder->remoteId = $orderNumber;
        $sslOrder->certificateType = $certificateType;
        $sslOrder->status = \WHMCS\Service\Ssl::STATUS_AWAITING_CONFIGURATION;
        $sslOrder->save();
        $relatedHostingService = null;
        if ($model instanceof \WHMCS\Service\Service) {
            $relatedHostingService = \WHMCS\MarketConnect\Provision::findRelatedHostingService($model);
        }
        if ($model instanceof \WHMCS\Service\Addon || $relatedHostingService instanceof \WHMCS\Service\Service) {
            $parentModel = $model instanceof \WHMCS\Service\Addon ? $model->service : $relatedHostingService;
            switch ($parentModel->product->module) {
                case "cpanel":
                case "directadmin":
                case "plesk":
                    $this->configure($model, array(), $relatedHostingService);
                    return NULL;
            }
        }
        $sslConfigurationLink = \App::getSystemURL() . "configuressl.php?cert=" . md5($sslOrder->id);
        $sslConfigurationLink = "<a href=\"" . $sslConfigurationLink . "\">" . $sslConfigurationLink . "</a>";
        $emailTemplate = "SSL Certificate Configuration Required";
        if ($model instanceof \WHMCS\Service\Addon && $model->productAddon->welcomeEmailTemplateId) {
            $emailTemplate = $model->productAddon->welcomeEmailTemplate;
        } else {
            if ($model instanceof \WHMCS\Service\Service && $model->product->welcomeEmailTemplateId) {
                $emailTemplate = $model->product->welcomeEmailTemplate;
            }
        }
        sendMessage($emailTemplate, $serviceId, array("ssl_configuration_link" => $sslConfigurationLink));
    }
    public function configure($model, array $params = NULL, \WHMCS\Service\Service $relatedHostingService = NULL)
    {
        try {
            $serviceProperties = $model->serviceProperties;
            $orderNumber = $serviceProperties->get("Order Number");
            $domain = $serviceProperties->get("domain");
            $api = new \WHMCS\MarketConnect\Api();
            if ($model instanceof \WHMCS\Service\Addon || $relatedHostingService instanceof \WHMCS\Service\Service) {
                $parentModel = $relatedHostingService instanceof \WHMCS\Service\Service ? $relatedHostingService : $model->service;
                $fileAuthFilename = null;
                $fileAuthContents = null;
                $certificateType = "";
                $domain = $initialDomain = $serviceProperties->get("Certificate Domain") ?: $parentModel->domain;
                if ($relatedHostingService instanceof \WHMCS\Service\Service) {
                    $certificateType = $model->product->moduleConfigOption1;
                } else {
                    foreach ($model->productAddon->moduleConfiguration as $moduleConfiguration) {
                        if ($moduleConfiguration->settingName == "configoption1") {
                            $certificateType = $moduleConfiguration->value;
                            break;
                        }
                    }
                }
                if ($certificateType && stristr($certificateType, "wildcard") && substr($domain, 0, 1) != "*") {
                    $domain = substr($domain, 0, 4) == "www." ? substr($domain, 4) : $domain;
                    $domain = "*." . $domain;
                }
                switch ($parentModel->product->module) {
                    case "cpanel":
                    case "directadmin":
                    case "plesk":
                        $serverInterface = \WHMCS\Module\Server::factoryFromModel($parentModel);
                        $csrData = $this->generateCsr($model, $serverInterface, $params);
                        $csr = $csrData["csr"];
                        $certificateInfo = $csrData["certificateInfo"];
                        $initialDomain = $csrData["initialDomain"];
                        try {
                            $configuration = array("order_number" => $orderNumber, "csr" => $csr, "servertype" => $parentModel->product->module, "domain" => $domain, "admin" => array("title" => "Mr", "firstname" => $serviceProperties->get("Certificate Contact First Name") ?: $model->client->firstName, "lastname" => $serviceProperties->get("Certificate Contact Last Name") ?: $model->client->lastName, "email" => $serviceProperties->get("Certificate Contact Email") ?: $model->client->email, "phone" => $serviceProperties->get("Certificate Contact Phone") ?: $model->client->phoneNumber), "tech" => array("title" => "Mr", "firstname" => $serviceProperties->get("Certificate Contact First Name") ?: $model->client->firstName, "lastname" => $serviceProperties->get("Certificate Contact Last Name") ?: $model->client->lastName, "email" => $serviceProperties->get("Certificate Contact Email") ?: $model->client->email, "phone" => $serviceProperties->get("Certificate Contact Phone") ?: $model->client->phoneNumber), "billing" => array("title" => "Mr", "firstname" => $serviceProperties->get("Certificate Contact First Name") ?: $model->client->firstName, "lastname" => $serviceProperties->get("Certificate Contact Last Name") ?: $model->client->lastName, "email" => $serviceProperties->get("Certificate Contact Email") ?: $model->client->email, "phone" => $serviceProperties->get("Certificate Contact Phone") ?: $model->client->phoneNumber), "org" => array("name" => $serviceProperties->get("Certificate Organisation Name") ?: $model->client->companyName, "address1" => $serviceProperties->get("Certificate Address 1") ?: $model->client->address1, "address2" => $serviceProperties->get("Certificate Address 2") ?: $model->client->address2, "city" => $serviceProperties->get("Certificate City") ?: $model->client->city, "state" => $serviceProperties->get("Certificate State") ?: $model->client->state, "postcode" => $serviceProperties->get("Certificate Post/Zip Code") ?: $model->client->postcode, "country" => $serviceProperties->get("Certificate Country") ?: $model->client->country, "phone" => $serviceProperties->get("Certificate Phone") ?: $model->client->phoneNumber), "callback_url" => fqdnRoutePath("store-ssl-callback"), "fileauth" => true, "server_module" => $parentModel->product->module);
                            $promoHelper = new \WHMCS\MarketConnect\Promotion\Service\Symantec();
                            $certificateTypes = $promoHelper->getSslTypes();
                            if (!in_array($certificateType, $certificateTypes["dv"]) && $certificateType != "rapidssl_wildcard") {
                                $configuration["fileauth"] = false;
                                $configuration["approveremail"] = "webmaster@" . $initialDomain;
                            }
                            if ($certificateType == "rapidssl_wildcard") {
                                $configuration["approveremail"] = "webmaster@" . $initialDomain;
                            }
                            if (array_key_exists("configdata", $params)) {
                                switch ($params["configdata"]["approvalmethod"]) {
                                    case "file":
                                        $configuration["fileauth"] = true;
                                        break;
                                    default:
                                        $configuration["fileauth"] = false;
                                        $configuration["approveremail"] = "webmaster@" . $initialDomain;
                                        if (array_key_exists("approveremail", $params["configdata"]) && $params["configdata"]["approveremail"]) {
                                            $configuration["approveremail"] = $params["configdata"]["approveremail"];
                                        }
                                }
                            }
                            $api = new \WHMCS\MarketConnect\Api();
                            $response = $api->configure($configuration);
                            if (array_key_exists("error", $response) && $response["error"]) {
                                throw new \WHMCS\Exception("SSL Configuration Failed: " . $response["error]"]);
                            }
                            if (isset($response["data"]["competitiveUpgradeFreeMonths"])) {
                                $this->applyCompetitiveUpgradeFreeMonths($model, $response["data"]["competitiveUpgradeFreeMonths"]);
                            }
                            if ($configuration["fileauth"]) {
                                $this->fileAuthUpload($serverInterface, $initialDomain, $response);
                            }
                        } catch (\Exception $e) {
                            throw $e;
                        }
                        unset($configuration["callback_url"]);
                        $where = array("userid" => $model->clientId, "serviceid" => $parentModel->id, "addon_id" => $model->id, "module" => "marketconnect");
                        $certificateType = $model instanceof \WHMCS\Service\Addon ? $model->productAddon->name : $model->product->name;
                        if ($relatedHostingService instanceof \WHMCS\Service\Service) {
                            $where = array("userid" => $model->clientId, "serviceid" => $model->id, "addon_id" => 0, "module" => "marketconnect");
                        }
                        \WHMCS\Database\Capsule::table("tblsslorders")->updateOrInsert($where, array("remoteid" => $orderNumber, "certtype" => $certificateType, "status" => \WHMCS\Service\Ssl::STATUS_AWAITING_ISSUANCE, "configdata" => safe_serialize($configuration)));
                        return array();
                }
            } else {
                if ($model instanceof \WHMCS\Service\Service) {
                    $certificateType = $model->product->moduleConfigOption1;
                    if ($certificateType && stristr($certificateType, "wildcard") && substr($domain, 0, 1) != "*") {
                        $domain = substr($domain, 0, 4) == "www." ? substr($domain, 4) : $domain;
                        $domain = "*." . $domain;
                    }
                }
            }
            if (array_key_exists("isSslAutoConfigurationAttempt", $params) && $params["isSslAutoConfigurationAttempt"]) {
                throw new \WHMCS\MarketConnect\Exception\GeneralError("Unable to automatically configure SSL Certificate." . " Server module must be one of cPanel, Plesk or DirectAdmin" . " for auto-configuration to be possible.");
            }
            if (array_key_exists("configdata", $params) && array_key_exists("approveremail", $params["configdata"])) {
                $approverEmail = $params["configdata"]["approveremail"];
            } else {
                if (array_key_exists("approveremail", $params)) {
                    $approverEmail = $params["approveremail"];
                } else {
                    $approverEmail = "webmaster@" . preg_replace("/(\\*\\.|www\\.)(.*)/", "\\2", $domain);
                }
            }
            $response = $api->configure(array("order_number" => $orderNumber, "csr" => $params["configdata"]["csr"], "servertype" => $this->getMarketplaceServerType($params["configdata"]["servertype"]), "domain" => isset($params["configdata"]["domain"]) ? $params["configdata"]["domain"] : $domain, "admin" => array("title" => $params["configdata"]["jobtitle"], "firstname" => $params["configdata"]["firstname"], "lastname" => $params["configdata"]["lastname"], "email" => $params["configdata"]["email"], "phone" => $params["configdata"]["phonenumber"]), "tech" => array("title" => $params["configdata"]["jobtitle"], "firstname" => $params["configdata"]["firstname"], "lastname" => $params["configdata"]["lastname"], "email" => $params["configdata"]["email"], "phone" => $params["configdata"]["phonenumber"]), "billing" => array("title" => $params["configdata"]["jobtitle"], "firstname" => $params["configdata"]["firstname"], "lastname" => $params["configdata"]["lastname"], "email" => $params["configdata"]["email"], "phone" => $params["configdata"]["phonenumber"]), "org" => array("name" => $params["configdata"]["orgname"], "address1" => $params["configdata"]["address1"], "address2" => $params["configdata"]["address2"], "city" => $params["configdata"]["city"], "state" => $params["configdata"]["state"], "postcode" => $params["configdata"]["postcode"], "country" => $params["configdata"]["country"], "phone" => $params["configdata"]["phonenumber"]), "fileauth" => isset($params["configdata"]["approvalmethod"]) && $params["configdata"]["approvalmethod"] == "file", "callback_url" => fqdnRoutePath("store-ssl-callback"), "approveremail" => $approverEmail));
            if (array_key_exists("error", $response) && $response["error"]) {
                throw new \WHMCS\Exception($response["error"]);
            }
            if (isset($response["data"]["competitiveUpgradeFreeMonths"])) {
                $this->applyCompetitiveUpgradeFreeMonths($model, $response["data"]["competitiveUpgradeFreeMonths"]);
            }
            if (isset($response["data"]["fileAuthFilename"]) && isset($response["data"]["fileAuthContents"])) {
                return array("fileAuth" => true, "fileAuthFilename" => $response["data"]["fileAuthPath"] . "/" . $response["data"]["fileAuthFilename"], "fileAuthContents" => $response["data"]["fileAuthContents"]);
            }
            return array("fileAuth" => false);
        } catch (\Exception $e) {
            throw $e;
        }
    }
    protected function applyCompetitiveUpgradeFreeMonths($model, $freeMonths)
    {
        if ($model->nextDueDate instanceof \WHMCS\Carbon) {
            $model->nextDueDate->addMonths($freeMonths);
        } else {
            $model->nextDueDate = \WHMCS\Carbon::createFromFormat("Y-m-d", $model->nextDueDate)->addMonths($freeMonths)->toDateString();
        }
        $model->nextInvoiceDate = $model->nextDueDate;
        $model->save();
    }
    public function cancel($model)
    {
        try {
            $orderNumber = $model->serviceProperties->get("Order Number");
            if (!$orderNumber) {
                throw new \WHMCS\Exception("No SSL Order exists for this product");
            }
            $api = new \WHMCS\MarketConnect\Api();
            $response = $api->cancel($orderNumber);
            if ($response["success"] == true) {
                \WHMCS\Database\Capsule::table("tblsslorders")->where("remoteid", "=", $orderNumber)->update(array("status" => "Cancelled"));
                return NULL;
            }
            throw new \WHMCS\Exception("Cancellation Failed");
        } catch (\Exception $e) {
            throw $e;
        }
    }
    public function install($model)
    {
        try {
            $serviceProperties = $model->serviceProperties;
            $orderNumber = $serviceProperties->get("Order Number");
            if (!$orderNumber) {
                throw new \WHMCS\Exception("No SSL Order exists for this product");
            }
            $relatedHostingService = null;
            if ($model instanceof \WHMCS\Service\Service) {
                $relatedHostingService = \WHMCS\MarketConnect\Provision::findRelatedHostingService($model);
            }
            if ($model instanceof \WHMCS\Service\Addon || $relatedHostingService instanceof \WHMCS\Service\Service) {
                $parentModel = $model instanceof \WHMCS\Service\Addon ? $model->service : $relatedHostingService;
                switch ($parentModel->product->module) {
                    case "cpanel":
                    case "directadmin":
                    case "plesk":
                        $api = new \WHMCS\MarketConnect\Api();
                        $certificateData = $api->extra("getcertificate", array("order_number" => $orderNumber));
                        if (array_key_exists("error", $certificateData)) {
                            throw new \WHMCS\Exception($certificateData["error"]);
                        }
                        $serverInterface = \WHMCS\Module\Server::factoryFromModel($parentModel);
                        $serverInterface->call("InstallSsl", array("certificateDomain" => $serviceProperties->get("Certificate Domain") ?: $parentModel->domain, "certificate" => $certificateData["certificate"], "csr" => $serviceProperties->get("Certificate Signing Request"), "key" => $serviceProperties->get("Certificate Private Key")));
                        $serviceId = $model->id;
                        $addonId = 0;
                        if ($model instanceof \WHMCS\Service\Addon) {
                            $serviceId = $model->service->id;
                            $addonId = $model->id;
                        }
                        $sslOrder = \WHMCS\Service\Ssl::firstOrCreate(array("userid" => $model->clientId, "serviceid" => $serviceId, "addon_id" => $addonId, "module" => "marketconnect"));
                        $sslOrder->status = "Completed";
                        $sslOrder->save();
                        break;
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
    public function clientAreaAllowedFunctions(array $params)
    {
        if ($params["status"] != "Active") {
            return array();
        }
        return array("client_change_approver_email", "client_reissue_certificate", "client_retrieve_certificate");
    }
    public function clientAreaOutput(array $params)
    {
        try {
            $orderNumber = marketconnect_GetOrderNumber($params);
            $sslOrderDetails = marketconnect_GetSslOrderDetails($orderNumber);
        } catch (\WHMCS\Exception $e) {
            $sslOrderDetails = null;
        }
        if ($sslOrderDetails) {
            $model = $params["model"];
            $provisionDate = $model->registrationDate;
            $provisionDate = in_array($provisionDate, array("0000-00-00", "1970-01-01")) ? "-" : fromMySQLDate($provisionDate);
            $status = $sslOrderDetails->status;
            if ($status == \WHMCS\Service\Ssl::STATUS_AWAITING_CONFIGURATION) {
                $status .= " <a class=\"btn btn-default autoLinked\" href=\"configuressl.php?cert=" . md5($sslOrderDetails->id) . "\">" . \Lang::trans("sslconfigurenow") . "</a>";
            }
            $sslProvisionDate = \Lang::trans("sslprovisioningdate");
            $sslStatus = \Lang::trans("sslstatus");
            $certificateData = safe_unserialize($sslOrderDetails->configdata);
            $approverEmailData = "";
            if (array_key_exists("approveremail", $certificateData) && $certificateData["approveremail"]) {
                $approverEmail = \Lang::trans("sslcertapproveremail");
                $approverEmailData = "<div class=\"row\">\n    <div class=\"col-md-4\">" . $approverEmail . "</div>\n    <div class=\"col-md-8\">" . $certificateData["approveremail"] . "</div>\n</div>";
            }
            return "<div align=\"left\">\n    <div class=\"row\">\n        <div class=\"col-md-4\">" . $sslProvisionDate . "</div>\n        <div class=\"col-md-8\">" . $provisionDate . "</div>\n    </div>\n    <div class=\"row\">\n        <div class=\"col-md-4\">" . $sslStatus . "</div>\n        <div class=\"col-md-8\">" . $status . "</div>\n    </div>\n    " . $approverEmailData . "\n</div>";
        }
        return "";
    }
    public function renew($model, array $params = NULL)
    {
        $orderNumber = marketconnect_GetOrderNumber($params);
        $term = marketconnect_DetermineTerm($params);
        $api = new \WHMCS\MarketConnect\Api();
        $response = $api->renew($orderNumber, $term, fqdnRoutePath("store-ssl-callback"));
        $model->serviceProperties->save(array("Order Number" => $response["order_number"]));
        $serviceProperties = $model->serviceProperties;
        $serviceId = $model->id;
        $addonId = 0;
        $status = "Completed";
        $relatedHostingService = null;
        if ($model instanceof \WHMCS\Service\Service) {
            $relatedHostingService = \WHMCS\MarketConnect\Provision::findRelatedHostingService($model);
        }
        if ($model instanceof \WHMCS\Service\Addon || $relatedHostingService instanceof \WHMCS\Service\Service) {
            $serviceId = $model->id;
            $addonId = 0;
            if ($model instanceof \WHMCS\Service\Addon) {
                $module = $model->service->product->module;
                $serviceId = $model->serviceId;
                $addonId = $model->id;
                $certType = $model->productAddon->name;
            } else {
                $certType = $model->product->name;
                $module = $model->product->module;
            }
            if (in_array($module, \WHMCS\MarketConnect\Provision::AUTO_INSTALL_PANELS)) {
                $domain = $serviceProperties->get("Certificate Domain");
                if (!$domain && $model instanceof \WHMCS\Service\Addon) {
                    $domain = $model->service->domain;
                } else {
                    if (!$domain) {
                        $domain = $model->domain;
                    }
                }
                $parentModel = $relatedHostingService instanceof \WHMCS\Service\Service ? $relatedHostingService : $model->service;
                $serverInterface = \WHMCS\Module\Server::factoryFromModel($parentModel);
                $this->fileAuthUpload($serverInterface, $domain, $response);
                $status = \WHMCS\Service\Ssl::STATUS_AWAITING_ISSUANCE;
            }
        } else {
            $certType = $model->product->name;
        }
        \WHMCS\Database\Capsule::table("tblsslorders")->updateOrInsert(array("userid" => $model->clientId, "serviceid" => $serviceId, "addon_id" => $addonId, "module" => "marketconnect"), array("remoteid" => $serviceProperties->get("Order Number"), "certtype" => $certType, "status" => $status));
        return "success";
    }
    protected function getMarketplaceServerType($serverType)
    {
        if ($serverType == "1031") {
            return "cpanel";
        }
        if ($serverType == "1030") {
            return "plesk";
        }
        if ($serverType == "1013" || $serverType == "1014") {
            return "iis";
        }
        $validWebServerTypes = array("cpanel", "plesk", "apache2", "apacheopenssl", "apacheapachessl", "iis");
        if (in_array($serverType, $validWebServerTypes)) {
            return $serverType;
        }
        return "other";
    }
    public function generateCsr($model, \WHMCS\Module\Server $serverInterface, array $params = NULL)
    {
        $serviceProperties = $model->serviceProperties;
        $domain = $serviceProperties->get("Certificate Domain");
        if (!$domain && $model instanceof \WHMCS\Service\Addon) {
            $domain = $model->service->domain;
        } else {
            if (!$domain) {
                $domain = $model->domain;
            }
        }
        $initialDomain = $domain;
        if (!is_null($params) && array_key_exists("configdata", $params)) {
            $csr = $params["configdata"]["csr"];
            $certificateInfo = array("domain" => $params["configdata"]["domain"], "country" => $params["configdata"]["country"], "state" => $params["configdata"]["state"], "city" => $params["configdata"]["city"], "orgname" => $params["configdata"]["orgname"], "orgunit" => "Technical", "email" => $params["configdata"]["email"]);
        } else {
            $certificateType = "";
            if ($model instanceof \WHMCS\Service\Addon) {
                foreach ($model->productAddon->moduleConfiguration as $moduleConfiguration) {
                    if ($moduleConfiguration->settingName == "configoption1") {
                        $certificateType = $moduleConfiguration->value;
                        break;
                    }
                }
            } else {
                $certificateType = $model->product->moduleConfigOption1;
            }
            if ($certificateType && stristr($certificateType, "wildcard") && substr($domain, 0, 1) != "*") {
                $domain = substr($domain, 0, 4) == "www." ? substr($domain, 4) : $domain;
                $domain = "*." . $domain;
            }
            $certificateInfo = array("domain" => $domain, "country" => $serviceProperties->get("Certificate Country") ?: $model->client->country, "state" => $serviceProperties->get("Certificate State") ?: $model->client->state, "city" => $serviceProperties->get("Certificate City") ?: $model->client->city, "orgname" => $serviceProperties->get("Certificate Organisation Name") ?: $model->client->companyName ?: "N/A", "orgunit" => $serviceProperties->get("Certificate Organisation Unit") ?: "Technical", "email" => $serviceProperties->get("Certificate Email Address") ?: $model->client->email);
            $csr = $serverInterface->call("GenerateCertificateSigningRequest", array("certificateInfo" => $certificateInfo));
            if (is_array($csr)) {
                $save = $csr["saveData"];
                $key = $csr["key"];
                $csr = $csr["csr"];
                if ($save) {
                    $serviceProperties->save(array("Certificate Private Key" => array("type" => "textarea", "value" => $key), "Certificate Signing Request" => array("type" => "textarea", "value" => $csr)));
                }
            }
        }
        return array("csr" => $csr, "certificateInfo" => $certificateInfo, "initialDomain" => $initialDomain);
    }
    public function adminServicesTabOutput(array $params, \WHMCS\MarketConnect\OrderInformation $orderInfo = NULL, array $actionBtns = NULL)
    {
        $orderInfo = \WHMCS\MarketConnect\OrderInformation::factory($params);
        $actionBtns = array(array("icon" => "far fa-envelope", "label" => "Email Client Link to Configure", "class" => "btn-default", "moduleCommand" => "resend", "applicableStatuses" => array("Awaiting Configuration")), array("icon" => "fa-cog", "label" => "Manually Configure Certificate", "class" => "btn-default", "href" => "wizard.php?wizard=ConfigureSsl&serviceid=" . $params["serviceid"] . "&addonid=" . $params["addonId"], "modal" => array("title" => "Configure Certificate", "class" => "modal-wizard", "size" => "modal-lg", "submitLabel" => "Next", "submitId" => ""), "applicableStatuses" => array("Awaiting Configuration")), array("icon" => "fa-cogs", "label" => "Attempt Automatic Configuration", "class" => "btn-default", "moduleCommand" => "resend_configuration_data", "applicableStatuses" => array("Awaiting Configuration")), array("icon" => "fa-upload", "label" => "Change Approver Email", "class" => "btn-default", "moduleCommand" => "admin_change_approver_email", "modal" => array("title" => "Change Approver Email", "submitLabel" => "Submit", "submitId" => "btnChangeApproverEmailSubmit"), "applicableStatuses" => array("Configuration Submitted")), array("icon" => "fa-envelope", "label" => "Re-send Approver Email", "class" => "btn-default", "moduleCommand" => "admin_resend_approver_email", "applicableStatuses" => array("Configuration Submitted")), array("icon" => "fa-download", "label" => "Retrieve Certificate", "class" => "btn-default", "moduleCommand" => "admin_retrieve_certificate", "modal" => array("title" => "Retrieve Certificate"), "applicableStatuses" => array("Configuration Submitted")), array("icon" => "fa-exchange", "label" => "Attempt Certificate Auto-Installation", "class" => "btn-default", "moduleCommand" => "install_certificate", "applicableStatuses" => array("Configuration Submitted")));
        return parent::adminServicesTabOutput($params, $orderInfo, $actionBtns);
    }
    protected function fileAuthUpload(\WHMCS\Module\Server $serverInterface, $initialDomain, array $response)
    {
        $fileAuthPath = isset($response["data"]["fileAuthPath"]) ? $response["data"]["fileAuthPath"] : null;
        $fileAuthFilename = isset($response["data"]["fileAuthFilename"]) ? $response["data"]["fileAuthFilename"] : null;
        $fileAuthContents = isset($response["data"]["fileAuthContents"]) ? $response["data"]["fileAuthContents"] : null;
        if (!$fileAuthPath || !$fileAuthFilename || !$fileAuthContents) {
            throw new \WHMCS\Exception("File authentication parameters malformed. Auto verification not possible." . (string) $fileAuthPath . "|" . $fileAuthFilename . "|" . $fileAuthContents);
        }
        $serverInterface->call("CreateFileWithinDocRoot", array("certificateDomain" => $initialDomain, "dir" => $fileAuthPath, "filename" => $fileAuthFilename, "fileContent" => $fileAuthContents));
    }
    public function isEligibleForUpgrade()
    {
        return false;
    }
    public function isSslProduct()
    {
        return true;
    }
}

?>