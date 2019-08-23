<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Utilities\Tools\ServerSync;

class Process
{
    public static function import(SyncItem $syncItem, \WHMCS\Module\Server $moduleInterface, $serverId, array $additional = array())
    {
        if (!function_exists("getClientsPaymentMethod")) {
            require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "clientfunctions.php";
        }
        $clientWelcomeEmail = "";
        $passwordReset = "";
        $serviceWelcomeEmail = "";
        $nextDueDate = "";
        $billingCycle = "";
        if (array_key_exists("clientWelcomeEmail", $additional)) {
            $clientWelcomeEmail = $additional["clientWelcomeEmail"];
        }
        if (array_key_exists("passwordReset", $additional)) {
            $passwordReset = $additional["passwordReset"];
        }
        if (array_key_exists("serviceWelcomeEmail", $additional)) {
            $serviceWelcomeEmail = $additional["serviceWelcomeEmail"];
        }
        if (array_key_exists("nextDueDate", $additional)) {
            $nextDueDate = $additional["nextDueDate"];
        }
        if (array_key_exists("billingCycle", $additional)) {
            $billingCycle = $additional["billingCycle"];
        }
        $defaultPaymentMethod = getClientsPaymentMethod(0);
        if (!$defaultPaymentMethod) {
            throw new \WHMCS\Exception\Module\InvalidConfiguration("There are no active payment gateways. A gateway is required for import.");
        }
        $client = \WHMCS\User\Client::where("email", $syncItem->getEmail())->first();
        if (!$client) {
            $currency = getCurrency();
            $client = new \WHMCS\User\Client();
            $client->firstName = "Name";
            $client->lastName = "Placeholder";
            $client->email = $syncItem->getEmail();
            if (empty($client->email)) {
                $client->email = $client->generateUniquePlaceholderEmail();
            }
            $client->notes = "Auto-created client from Server Sync Tool";
            $client->dateCreated = \WHMCS\Carbon::now();
            $client->currencyId = $currency["id"];
            $client->status = "Active";
            $client->save();
            $msg = "Server Sync: Created Client - User ID: " . $client->id;
            logActivity($msg, $client->id);
            if ($clientWelcomeEmail) {
                sendMessage($clientWelcomeEmail, $client->id);
            }
        }
        $productField = $syncItem->getProductField();
        $product = \WHMCS\Product\Product::where("servertype", $moduleInterface->getLoadedModule())->where($productField, $syncItem->getProduct())->first();
        $productExisted = true;
        if (!$product) {
            $serverSyncProductGroupName = "Server Sync Tool Auto-Created Products";
            $productGroup = \WHMCS\Product\Group::where("name", $serverSyncProductGroupName)->first();
            if (!$productGroup) {
                $productGroup = new \WHMCS\Product\Group();
                $productGroup->name = $serverSyncProductGroupName;
                $productGroup->isHidden = true;
                $productGroup->disabledPaymentGateways = array();
                $productGroup->displayOrder = 0;
                $productGroup->headline = "";
                $productGroup->tagline = "";
                $productGroup->orderFormTemplate = "";
                $productGroup->save();
                $msg = "Server Sync: Created " . $serverSyncProductGroupName . " Product Group";
                logActivity($msg);
            }
            $productName = $syncItem->getProduct();
            $productName = str_replace(array($moduleInterface->getParam("serverusername") . "_", $moduleInterface->getParam("serverusername")), "", $productName);
            $moduleRequiresServer = $moduleInterface->getMetaDataValue("RequiresServer") !== false;
            $product = new \WHMCS\Product\Product();
            $product->productGroupId = $productGroup->id;
            $product->name = $productName;
            $product->isHidden = true;
            $product->stockControlEnabled = true;
            $product->quantityInStock = 0;
            $product->module = $moduleInterface->getLoadedModule();
            $product->{$productField} = $syncItem->getProduct();
            $product->type = $moduleRequiresServer ? "hostingaccount" : "other";
            $product->description = "Auto-created product from Server Sync Tool";
            $product->showDomainOptions = $moduleRequiresServer;
            $product->welcomeEmailTemplateId = 0;
            $product->proRataBilling = false;
            $product->proRataChargeDayOfCurrentMonth = 0;
            $product->proRataChargeNextMonthAfterDay = 0;
            $product->paymentType = "free";
            $product->allowMultipleQuantities = false;
            $product->freeSubDomains = array();
            $product->autoSetup = "";
            $product->freeDomain = "";
            $product->freeDomainPaymentTerms = array();
            $product->freeDomainTlds = array();
            $product->recurringCycleLimit = 0;
            $product->daysAfterSignUpUntilAutoTermination = 0;
            $product->autoTerminationEmailTemplateId = 0;
            $product->allowConfigOptionUpgradeDowngrade = false;
            $product->upgradeEmailTemplateId = 0;
            $product->enableOverageBillingAndUnits = array();
            $product->overageDiskLimit = 0;
            $product->overageBandwidthLimit = 0;
            $product->overageDiskPrice = 0;
            $product->overageBandwidthPrice = 0;
            $product->applyTax = false;
            $product->affiliatePayoutOnceOnly = false;
            $product->affiliatePaymentType = "";
            $product->affiliatePaymentAmount = 0;
            $product->displayOrder = 0;
            $product->isFeatured = false;
            $product->save();
            $msg = "Server Sync: Created Product Name \"" . $productName . "\" - Product ID: " . $product->id;
            logActivity($msg);
            $productExisted = false;
        }
        $service = new \WHMCS\Service\Service();
        $service->domain = $syncItem->getDomain();
        $service->username = $syncItem->getUsername();
        $service->registrationDate = $syncItem->getCreated();
        $service->packageId = $product->id;
        $service->serverId = $serverId;
        $service->domainStatus = $syncItem->getStatus();
        $service->clientId = $client->id;
        $service->orderId = 0;
        $service->paymentGateway = getClientsPaymentMethod($client->id);
        $service->firstPaymentAmount = 0;
        $service->recurringAmount = 0;
        $service->billingCycle = "Free Account";
        $service->nextDueDate = "0000-00-00";
        $service->nextInvoiceDate = "0000-00-00";
        $service->terminationDate = "0000-00-00";
        $service->completedDate = "0000-00-00";
        $service->password = "";
        $service->notes = "Auto-created service from Server Sync Tool";
        $service->subscriptionId = "";
        $service->promotionId = 0;
        $service->suspendReason = "";
        $service->overrideAutoSuspend = false;
        $service->dedicatedIp = $syncItem->getPrimaryIp();
        $service->assignedIps = "";
        $service->ns1 = "";
        $service->ns2 = "";
        $service->diskUsage = 0;
        $service->diskLimit = 0;
        $service->bandwidthUsage = 0;
        $service->bandwidthLimit = 0;
        $service->save();
        if ($billingCycle && $productExisted) {
            $service->billingCycle = $billingCycle;
            $service->recurringAmount = recalcRecurringProductPrice($service->id, $client->id, $product->id, $billingCycle);
            $service->save();
        }
        if ($nextDueDate && $productExisted) {
            $dates = \WHMCS\Carbon::parseDateRangeValue($nextDueDate);
            $nextDueDate = $dates["from"];
            $service->nextDueDate = $service->nextInvoiceDate = $nextDueDate;
            $service->save();
        }
        if ($passwordReset) {
            $newPassword = \WHMCS\Module\Server::generateRandomPassword();
            $service->password = encrypt($newPassword);
            $service->save();
            $thisModuleInterface = \WHMCS\Module\Server::factoryFromModel($service);
            $thisModuleInterface->call("ChangePassword");
        }
        if ($serviceWelcomeEmail && $product->welcomeEmailTemplateId) {
            sendMessage("defaultnewacc", $service->id);
        }
        $uniqueIdField = $moduleInterface->getMetaDataValue("ListAccountsUniqueIdentifierField");
        if ($uniqueIdField == "username") {
            $service->username = $syncItem->getUniqueIdentifier();
        } else {
            if (substr($uniqueIdField, 0, 12) == "customfield.") {
                $customFieldName = substr($uniqueIdField, 12);
                $service->serviceProperties->save(array($customFieldName => $syncItem->getUniqueIdentifier()));
            } else {
                if ($uniqueIdField == "domain" || !$uniqueIdField) {
                    $service->domain = $syncItem->getUniqueIdentifier();
                } else {
                    throw new \WHMCS\Exception("Unsupported unique identifier field provided by module: \"" . $uniqueIdField . "\"");
                }
            }
        }
        $msg = "Server Sync: Created Product/Service - User ID: " . $client->id . " - Service ID: " . $service->id;
        logActivity($msg, $client->id);
    }
    public static function sync(SyncItem $syncItem, ServiceItem $serviceItem, $serverId)
    {
        $service = $serviceItem->getService();
        $changes = array();
        $registrationDate = $service->registrationDate;
        if ($registrationDate instanceof \WHMCS\Carbon) {
            $registrationDate = $registrationDate->toDateString();
        }
        if ($syncItem->getCreated() != $registrationDate) {
            $previousDate = fromMySQLDate($registrationDate);
            $newDate = fromMySQLDate($syncItem->getCreated());
            $service->registrationDate = $syncItem->getCreated();
            $changes[] = "Registration Date changed from " . $previousDate . " to " . $newDate;
        }
        if ($syncItem->getProduct() != $serviceItem->getProduct()) {
            $currentProductId = $service->packageId;
            $currentServiceModule = $service->product->module;
            $newProduct = \WHMCS\Database\Capsule::table("tblproducts")->where("servertype", $currentServiceModule)->where($syncItem->getProductField(), $syncItem->getProduct())->first();
            $service->packageId = $newProduct->id;
            $changes[] = "Product/Service changed from " . $currentProductId . " to " . $newProduct->id;
        }
        if ($serverId != $service->serverId) {
            $previousServer = $service->serverId;
            $service->serverId = $serverId;
            $changes[] = "Server changed from " . $previousServer . " to " . $service->serverId;
        }
        if ($syncItem->getPrimaryIp() != $service->dedicatedIp) {
            $previousDedicatedIp = $service->dedicatedIp;
            $service->dedicatedIp = $syncItem->getPrimaryIp();
            $changes[] = "Dedicated IP changed from " . $previousDedicatedIp . " to " . $service->dedicatedIp;
        }
        if ($syncItem->getUsername() != $service->username) {
            $previousUsername = $service->username;
            $service->username = $syncItem->getUsername();
            $changes[] = "Username changed from " . $previousUsername . " to " . $service->username;
        }
        if ($syncItem->getStatus() != $service->domainStatus) {
            $previousStatus = $service->domain;
            $service->domainStatus = $syncItem->getStatus();
            $changes[] = "Status changed from " . $previousStatus . " to " . $service->domainStatus;
        }
        if (count($changes)) {
            $service->save();
            $userId = $service->clientId;
            $id = $service->id;
            $changes = implode(", ", $changes);
            logActivity("Server Sync: " . "Modified Product/Service - " . $changes . " - User ID: " . $userId . " - Service ID: " . $id, $userId);
        }
    }
    public static function terminate(\WHMCS\Service\Service $service)
    {
        $previousStatus = $service->domain;
        $service->domainStatus = \WHMCS\Service\Status::TERMINATED;
        $service->terminationDate = \WHMCS\Carbon::today()->toDateTimeString();
        $service->save();
        $userId = $service->clientId;
        $id = $service->id;
        $change = "Status changed from " . $previousStatus . " to " . $service->domainStatus;
        logActivity("Server Sync: " . "Modified Product/Service - " . $change . " - User ID: " . $userId . " - Service ID: " . $id, $userId);
    }
}

?>