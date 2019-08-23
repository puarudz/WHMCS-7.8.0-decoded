<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function marketconnect_MetaData()
{
    return array("DisplayName" => "WHMCS MarketConnect", "APIVersion" => "1.1", "RequiresServer" => false, "NoEditModuleSettings" => true, "NoEditPricing" => true, "ConfigurationLink" => "marketconnect.php?manage=:service", "ChangePackageLabel" => "Upgrade", "AutoGenerateUsernameAndPassword" => false);
}
function marketconnect_ConfigOptions()
{
    return array("Service" => array("Type" => "dropdown", "Options" => marketconnect_GetServices()), "Term" => array("Type" => "dropdown", "Options" => "Use Billing Cycle,1,3,6,12,24,36"));
}
function marketconnect_CreateAccount(array $params)
{
    try {
        $term = marketconnect_DetermineTerm($params);
        $status = $params["status"];
        $existingOrder = $params["model"]->serviceProperties->get("Order Number");
        if (!empty($existingOrder) && $status != "Pending") {
            throw new WHMCS\Exception("This service has already been provisioned. Please cancel the existing order before attempting to re-provision it.");
        }
        if (!$existingOrder) {
            $api = new WHMCS\MarketConnect\Api();
            $order = $api->purchase($params["configoption1"], $term);
            $orderNumber = $order["order_number"];
            $params["model"]->serviceProperties->save(array("Order Number" => $orderNumber));
        }
        WHMCS\MarketConnect\Provision::factoryFromModel($params["model"])->provision($params);
        return "success";
    } catch (Exception $e) {
        return $e->getMessage();
    }
}
function marketconnect_TerminateAccount(array $params)
{
    try {
        WHMCS\MarketConnect\Provision::factoryFromModel($params["model"])->cancel();
        $params["model"]->serviceProperties->save(array("Order Number" => ""));
        return "success";
    } catch (Exception $e) {
        return $e->getMessage();
    }
}
function marketconnect_Renew(array $params)
{
    try {
        $orderNumber = marketconnect_GetOrderNumber($params);
        if (!$orderNumber) {
            throw new WHMCS\Exception\Module\NotServicable("You must provision this service before attempting to renew it.");
        }
        WHMCS\MarketConnect\Provision::factoryFromModel($params["model"])->renew($params);
        return "success";
    } catch (Exception $e) {
        return $e->getMessage();
    }
}
function marketconnect_SSLStepOne(array $params)
{
    $productParts = explode("_", $params["configoption1"]);
    if (!in_array($productParts[0], array("rapidssl", "geotrust", "symantec"))) {
        return NULL;
    }
}
function marketconnect_SSLStepTwo(array $params)
{
    $productParts = explode("_", $params["configoption1"]);
    if (!in_array($productParts[0], array("rapidssl", "geotrust", "symantec"))) {
        return array();
    }
    try {
        $orderNumber = marketconnect_GetOrderNumber($params);
        $api = new WHMCS\MarketConnect\Api();
        $data = $api->extra("getapprovalmethods", array("order_number" => $orderNumber, "csr" => $params["csr"]));
        return array("approvalmethods" => $data["approvalmethods"], "approveremails" => $data["approveremails"], "displaydata" => array("Domain Name" => $data["csrData"]["DomainName"], "Organization" => $data["csrData"]["Organization"], "Organization Unit" => $data["csrData"]["OrganizationUnit"], "Locality" => $data["csrData"]["Locality"], "Country" => $data["csrData"]["Country"]));
    } catch (Exception $e) {
        return array("error" => $e->getMessage());
    }
}
function marketconnect_SSLStepThree(array $params)
{
    $productParts = explode("_", $params["configoption1"]);
    if (!in_array($productParts[0], array("rapidssl", "geotrust", "symantec"))) {
        return array();
    }
    try {
        $response = WHMCS\MarketConnect\Provision::factoryFromModel($params["model"])->configure($params);
        return array_merge(array("success" => true), $response);
    } catch (Exception $e) {
        return array("error" => $e->getMessage());
    }
}
function marketconnect_AdminCustomButtonArray(array $params)
{
    return WHMCS\MarketConnect\Provision::factoryFromModel($params["model"])->adminManagementButtons($params);
}
function marketconnect_AdminServicesTabFields(array $params)
{
    return WHMCS\MarketConnect\Provision::factoryFromModel($params["model"])->adminServicesTabOutput($params);
}
function marketconnect_ChangePackage(array $params)
{
    try {
        $orderNumber = marketconnect_GetOrderNumber($params);
        $term = marketconnect_DetermineTerm($params);
        $api = new WHMCS\MarketConnect\Api();
        $order = $api->upgrade($orderNumber, $params["configoption1"], $term);
        $orderNumber = $order["order_number"];
        $params["model"]->serviceProperties->save(array("Order Number" => $orderNumber));
        return "success";
    } catch (Exception $e) {
        return $e->getMessage();
    }
}
function marketconnect_resend(array $params)
{
    try {
        $orderNumber = marketconnect_GetOrderNumber($params);
        if (!$orderNumber) {
            throw new WHMCS\Exception("This order has not yet been provisioned.");
        }
        $sslOrderDetails = marketconnect_GetSslOrderDetails($orderNumber);
        if (!$sslOrderDetails) {
            throw new WHMCS\Exception("SSL configuration profile not found.");
        }
        $sslConfigurationLink = App::getSystemURL() . "configuressl.php?cert=" . md5($sslOrderDetails->id);
        $sslConfigurationLink = "<a href=\"" . $sslConfigurationLink . "\">" . $sslConfigurationLink . "</a>";
        sendMessage("SSL Certificate Configuration Required", $sslOrderDetails->serviceid, array("ssl_configuration_link" => $sslConfigurationLink));
        return array("growl" => array("message" => "Configuration email sent successfully"));
    } catch (Exception $e) {
        return array("growl" => array("type" => "error", "message" => $e->getMessage()));
    }
}
function marketconnect_resendApproverEmail(array $params)
{
    try {
        $orderNumber = marketconnect_GetOrderNumber($params);
        $api = new WHMCS\MarketConnect\Api();
        $api->extra("resendapproveremail", array("order_number" => $orderNumber));
        return "success";
    } catch (Exception $e) {
        return $e->getMessage();
    }
}
function marketconnect_admin_resend_approver_email(array $params)
{
    $response = marketconnect_resendapproveremail($params);
    if ($response == "success") {
        return array("growl" => array("message" => "Approver email re-sent successfully!"));
    }
    return array("growl" => array("type" => "error", "message" => $response));
}
function marketconnect_refreshStatus(array $params)
{
    try {
        $orderNumber = marketconnect_GetOrderNumber($params);
        if (!$orderNumber) {
            throw new WHMCS\Exception();
        }
        $api = new WHMCS\MarketConnect\Api();
        $response = $api->status($orderNumber);
        WHMCS\MarketConnect\OrderInformation::cache($orderNumber, $response);
    } catch (Exception $e) {
        $response = array("status" => "Order not found", "statusDescription" => "Order not found. Please check order number and try again.");
        WHMCS\MarketConnect\OrderInformation::cache($orderNumber, $response);
    }
    $data = marketconnect_adminservicestabfields($params);
    return array("statusOutput" => current($data));
}
function marketconnect_RetrieveCertificate(array $params)
{
    $orderNumber = marketconnect_GetOrderNumber($params);
    $sslOrderDetails = marketconnect_GetSslOrderDetails($orderNumber);
    if (!is_null($sslOrderDetails) && $sslOrderDetails->status == WHMCS\Service\Ssl::STATUS_CANCELLED) {
        throw new WHMCS\Exception("This certificate order has been cancelled.");
    }
    $api = new WHMCS\MarketConnect\Api();
    $certificateData = $api->extra("getcertificate", array("order_number" => $orderNumber));
    return $certificateData["certificate"];
}
function marketconnect_admin_retrieve_certificate(array $params)
{
    try {
        $certificate = marketconnect_retrievecertificate($params);
        return array("body" => "<div class=\"alert alert-success\">\n                <i class=\"fas fa-check fa-fw\"></i> The certificate has been successfully retrieved.\n            </div>\n            <textarea class=\"form-control\" rows=\"15\">" . $certificate . "</textarea>");
    } catch (WHMCS\Exception $e) {
        return array("body" => "<div class=\"alert alert-danger\" role=\"alert\">\n                <i class=\"fas fa-times fa-fw\"></i> " . $e->getMessage() . "\n            </div>");
    }
}
function marketconnect_client_retrieve_certificate(array $params)
{
    $certificate = "";
    $errorMessage = "";
    try {
        $certificate = marketconnect_retrievecertificate($params);
    } catch (WHMCS\Exception $e) {
        $errorMessage = $e->getMessage();
    }
    return array("overrideDisplayTitle" => Lang::trans("ssl.retrieveCertificate"), "appendToBreadcrumb" => array(array("#", Lang::trans("ssl.retrieveCertificate"))), "outputTemplateFile" => "ssl/retrieve", "templateVariables" => array("actionName" => "client_retrieve_certificate", "certificate" => $certificate, "errorMessage" => $errorMessage));
}
function marketconnect_ChangeApproverEmail(array $params, $newApproverEmail)
{
    $approverEmails = array();
    $approverEmailChangeSuccess = false;
    $errorMessage = "";
    $orderNumber = marketconnect_GetOrderNumber($params);
    $sslOrderDetails = marketconnect_GetSslOrderDetails($orderNumber);
    if (!is_null($sslOrderDetails) && $sslOrderDetails->status == WHMCS\Service\Ssl::STATUS_CANCELLED) {
        $errorMessage = "This certificate order has been cancelled.";
    }
    if (!$errorMessage) {
        try {
            if ($newApproverEmail) {
                check_token();
                $api = new WHMCS\MarketConnect\Api();
                $data = $api->extra("changeapproveremail", array("approveremail" => $newApproverEmail, "order_number" => $orderNumber));
                $approverEmailChangeSuccess = true;
                $certificateData = safe_unserialize($sslOrderDetails->configdata);
                $certificateData["approveremail"] = $newApproverEmail;
                $ssl = WHMCS\Service\Ssl::find($sslOrderDetails->id);
                $ssl->configurationData = safe_serialize($certificateData);
                $ssl->save();
            }
        } catch (WHMCS\Exception $e) {
            $errorMessage = $e->getMessage();
        }
        try {
            if (!$approverEmailChangeSuccess) {
                $api = new WHMCS\MarketConnect\Api();
                $data = $api->extra("getapproveremails", array("order_number" => $orderNumber, "domain" => $params["domain"]));
                if (isset($data["approveremails"]) && is_array($data["approveremails"])) {
                    $approverEmails = $data["approveremails"];
                } else {
                    $errorMessage = "An error occurred while attempting to retrieve the approver emails.";
                }
            }
        } catch (WHMCS\Exception $e) {
            $errorMessage = $e->getMessage();
        }
    }
    return array("approverEmails" => $approverEmails, "approverEmailChangeSuccess" => $approverEmailChangeSuccess, "newApproverEmail" => $newApproverEmail, "errorMessage" => $errorMessage);
}
function marketconnect_admin_change_approver_email(array $params)
{
    $newApproverEmail = App::getFromRequest("approver_email");
    $response = marketconnect_changeapproveremail($params, $newApproverEmail);
    $return = array();
    if ($response["errorMessage"]) {
        $return["body"] = "<div class=\"alert alert-danger\" role=\"alert\">" . $response["errorMessage"] . "</div>";
    } else {
        if ($response["approverEmailChangeSuccess"]) {
            $return["dismiss"] = true;
            $return["successMsgTitle"] = "Success!";
            $return["successMsg"] = "Approver email changed successfully";
        } else {
            $emailsList = "";
            foreach ($response["approverEmails"] as $approverEmail) {
                $emailsList .= "<label class=\"radio-inline\">\n    <input type=\"radio\" name=\"approver_email\" value=\"" . $approverEmail . "\">\n    " . $approverEmail . "\n</label>\n<br>";
            }
            if ($emailsList) {
                $userId = $params["userid"];
                $serviceId = $params["serviceid"];
                $addonId = $params["addonId"];
                $emailsList = "<p>Choose an approver email from below to switch to email based validation and update the approver email.</p>\n            <form method=\"post\" action=\"?userid=" . $userId . "&id=" . $serviceId . ($addonId ? "&aid=" . $addonId : "") . "&modop=custom&ac=admin_change_approver_email\">\n                " . generate_token() . "\n                <input type=\"hidden\" name=\"changeemail\" value=\"1\">\n                <blockquote>" . $emailsList . "</blockquote>\n            </form>";
            }
            $return["body"] = $emailsList;
        }
    }
    return $return;
}
function marketconnect_client_change_approver_email(array $params)
{
    $newApproverEmail = App::getFromRequest("approver_email");
    $response = marketconnect_changeapproveremail($params, $newApproverEmail);
    return array("overrideDisplayTitle" => Lang::trans("ssl.changeApproverEmail"), "appendToBreadcrumb" => array(array("#", Lang::trans("ssl.changeApproverEmail"))), "outputTemplateFile" => "ssl/changeapproveremail", "templateVariables" => array_merge($response, array("actionName" => "client_change_approver_email")));
}
function marketconnect_client_reissue_certificate(array $params)
{
    $template = "ssl/reissue";
    $reissueComplete = false;
    $approverEmails = array();
    $errorMessage = "";
    $csr = App::getFromRequest("csr");
    $approverEmail = App::getFromRequest("approver_email");
    $sslOrderDetails = marketconnect_GetSslOrderDetails(marketconnect_GetOrderNumber($params));
    if ($sslOrderDetails && $sslOrderDetails->status == WHMCS\Service\Ssl::STATUS_CANCELLED) {
        $errorMessage = "This certificate order has been cancelled.";
    } else {
        if ($sslOrderDetails) {
            if ($approverEmail && $csr) {
                check_token();
                try {
                    $api = new WHMCS\MarketConnect\Api();
                    $data = $api->extra("reissue", array("order_number" => $sslOrderDetails->remoteid, "csr" => $csr, "approveremail" => $approverEmail));
                    $template = "ssl/reissue-complete";
                } catch (WHMCS\Exception $e) {
                    $errorMessage = $e->getMessage();
                }
            } else {
                if ($csr) {
                    check_token();
                    try {
                        $api = new WHMCS\MarketConnect\Api();
                        $data = $api->extra("getapproveremails", array("order_number" => $sslOrderDetails->remoteid, "csr" => $csr));
                        $template = "ssl/reissue-chooseapprover";
                        $approverEmails = isset($data["approveremails"]) && is_array($data["approveremails"]) ? $data["approveremails"] : array();
                        $csrData = isset($data["csrData"]) && is_array($data["csrData"]) ? $data["csrData"] : array();
                    } catch (WHMCS\Exception $e) {
                        $errorMessage = $e->getMessage();
                    }
                }
            }
        } else {
            $errorMessage = "This certificate is not in a status that allows it to be reissued.";
        }
    }
    return array("overrideDisplayTitle" => Lang::trans("ssl.reissueCertificate"), "appendToBreadcrumb" => array(array("#", Lang::trans("ssl.reissueCertificate"))), "outputTemplateFile" => $template, "templateVariables" => array("actionName" => "client_reissue_certificate", "csr" => $csr, "approverEmails" => $approverEmails, "approverEmail" => $approverEmail, "csrData" => $csrData, "errorMessage" => $errorMessage));
}
function marketconnect_install_certificate(array $params)
{
    try {
        WHMCS\MarketConnect\Provision::factoryFromModel($params["model"])->install();
        return array("growl" => array("message" => "Certificate installed successfully!"), "success" => true);
    } catch (Exception $e) {
        return array("growl" => array("type" => "error", "message" => $e->getMessage()));
    }
}
function marketconnect_GetOrderNumber(array $params)
{
    if (isset($params["customfields"]["Order Number"]) && 0 < strlen($params["customfields"]["Order Number"]) && is_numeric($params["customfields"]["Order Number"])) {
        return $params["customfields"]["Order Number"];
    }
    $orderNumber = $params["model"]->serviceProperties;
    if (is_null($orderNumber)) {
        return "";
    }
    if ($orderNumber->get("Order Number")) {
        return $orderNumber->get("Order Number");
    }
    if (!$orderNumber && $params["model"]->status != "Pending") {
        throw new WHMCS\Exception("You must provision this service before attempting to manage it.");
    }
    return "";
}
function marketconnect_DetermineTerm(array $params)
{
    $term = $params["configoption2"];
    if (is_numeric($term)) {
        return $term;
    }
    $billingCycle = str_replace("-", "", strtolower($params["model"]->billingCycle));
    $terms = array("one time" => "0", "monthly" => "1", "quarterly" => "3", "semiannually" => "6", "annually" => "12", "biennially" => "24", "triennially" => "36", "free account" => "100");
    if (!array_key_exists($billingCycle, $terms)) {
        throw new WHMCS\Exception("Non-recurring billing cycle selected. Unable to convert to valid term.");
    }
    return $terms[$billingCycle];
}
function marketconnect_GetServices()
{
    $services = array();
    try {
        $api = new WHMCS\MarketConnect\Api();
        foreach ($api->services() as $service) {
            foreach ($service["services"] as $subservice) {
                $services[$subservice["id"]] = $subservice["display_name"];
            }
        }
    } catch (Exception $e) {
        $services[] = "Error: " . $e->getMessage();
    }
    return $services;
}
function marketconnect_GetSslOrderDetails($orderNumber)
{
    static $sslOrderDetails = NULL;
    if (!$sslOrderDetails) {
        $sslOrderDetails = WHMCS\Database\Capsule::table("tblsslorders")->where("remoteid", "=", $orderNumber)->where("module", "=", "marketconnect")->first();
    }
    return $sslOrderDetails;
}
function marketconnect_manage_order(array $params)
{
    try {
        $orderNumber = marketconnect_getordernumber($params);
        if (!$orderNumber) {
            throw new WHMCS\Exception("Unable to perform single sign-on.");
        }
        $api = new WHMCS\MarketConnect\Api();
        $response = $api->ssoForOrder($orderNumber);
        return array("jsonResponse" => array("success" => true, "redirect" => "window|" . $response["redirect_url"]));
    } catch (Exception $e) {
        return array("jsonResponse" => array("success" => false, "error" => $e->getMessage()));
    }
}
function marketconnect_admin_sso(array $params)
{
    try {
        $orderNumber = marketconnect_getordernumber($params);
        if (!$orderNumber) {
            throw new WHMCS\Exception("You must provision this service before attempting to login to it.");
        }
        $api = new WHMCS\MarketConnect\Api();
        $response = $api->ssoForOrder($orderNumber);
        return array("redirectUrl" => $response["redirect_url"]);
    } catch (Exception $e) {
        return array("growl" => array("type" => "error", "message" => $e->getMessage()));
    }
}
function marketconnect_ClientAreaAllowedFunctions(array $params)
{
    return WHMCS\MarketConnect\Provision::factoryFromModel($params["model"])->clientAreaAllowedFunctions($params);
}
function marketconnect_ClientArea(array $params)
{
    return WHMCS\MarketConnect\Provision::factoryFromModel($params["model"])->clientAreaOutput($params);
}
function marketconnect_get_configuration_link(array $params)
{
    $metaData = marketconnect_metadata();
    $link = $metaData["ConfigurationLink"];
    $type = WHMCS\MarketConnect\Provision::factoryFromModel($params["model"])->getServiceType();
    switch ($type) {
        case "rapidssl":
        case "geotrust":
        case "symantec":
            $type = "symantec";
            break;
    }
    return str_replace(":service", $type, $link);
}
function marketconnect_update_ftp_details(array $params)
{
    return WHMCS\MarketConnect\Provision::factoryFromModel($params["model"])->updateFtpDetails($params);
}
function marketconnect_resend_configuration_data(array $params)
{
    try {
        $orderNumber = marketconnect_getordernumber($params);
        if (!$orderNumber) {
            throw new WHMCS\Exception("This order has not yet been provisioned.");
        }
        $provision = WHMCS\MarketConnect\Provision::factoryFromModel($params["model"]);
        $params["isSslAutoConfigurationAttempt"] = $provision->isSslProduct();
        $provision->configure($params);
        return array("growl" => array("message" => "Auto-configuration completed successfully!"));
    } catch (Exception $e) {
        return array("growl" => array("type" => "error", "message" => $e->getMessage()));
    }
}
function marketconnect_check_auto_install_panels(array $params)
{
    $supportedParentModules = WHMCS\MarketConnect\Provision::AUTO_INSTALL_PANELS;
    $model = $params["model"];
    $checkIfSupported = false;
    $module = $service = NULL;
    if ($model instanceof WHMCS\Service\Addon) {
        $module = $model->service->product->module;
        $service = $model->service;
        $checkIfSupported = true;
    } else {
        $relatedHostingService = WHMCS\MarketConnect\Provision::findRelatedHostingService($model);
        if ($relatedHostingService) {
            $checkIfSupported = true;
            $module = $relatedHostingService->product->module;
            $service = $relatedHostingService;
        }
    }
    if ($checkIfSupported) {
        return array("supported" => in_array($module, $supportedParentModules), "panel" => WHMCS\Module\Server::factoryFromModel($service)->getDisplayName());
    }
    return array("supported" => false);
}
function marketconnect_generate_csr(array $params)
{
    $supportedParentModules = WHMCS\MarketConnect\Provision::AUTO_INSTALL_PANELS;
    $model = $params["model"];
    $relatedHostingService = NULL;
    if ($model instanceof WHMCS\Service\Service) {
        $relatedHostingService = WHMCS\MarketConnect\Provision::findRelatedHostingService($model);
    }
    if ($model instanceof WHMCS\Service\Addon && in_array($params["model"]->service->product->module, $supportedParentModules) || $relatedHostingService instanceof WHMCS\Service\Service && in_array($relatedHostingService->product->module, $supportedParentModules)) {
        return array("body" => WHMCS\MarketConnect\Provision::factoryFromModel($params["model"])->generateCsr());
    }
    return array("body" => array("csr" => false));
}
function marketconnect_entity_specific_merge_data(array $params)
{
    return WHMCS\MarketConnect\Provision::factoryFromModel($params["model"])->emailMergeData($params);
}

?>