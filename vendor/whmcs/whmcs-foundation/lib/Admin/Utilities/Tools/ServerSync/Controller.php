<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Utilities\Tools\ServerSync;

class Controller
{
    public function analyse(\WHMCS\Http\Message\ServerRequest $request)
    {
        $serverId = $request->attributes()->get("serverid");
        $server = \WHMCS\Product\Server::findOrFail($serverId);
        $moduleInterface = $server->getModuleInterface();
        if (!$moduleInterface->functionExists("ListAccounts")) {
            throw new \WHMCS\Exception\Module\NotServicable("Server does not support account sync");
        }
        $uniqueIdDisplayName = $moduleInterface->getMetaDataValue("ListAccountsUniqueIdentifierDisplayName");
        $uniqueIdField = $moduleInterface->getMetaDataValue("ListAccountsUniqueIdentifierField");
        $productField = $moduleInterface->getMetaDataValue("ListAccountsProductField");
        $response = $moduleInterface->call("ListAccounts", $moduleInterface->getServerParams($server));
        $error = "";
        $syncItems = array();
        $services = null;
        if (!$response["success"] && array_key_exists("error", $response) && $response["error"]) {
            $error = \AdminLang::trans("utilities.serverSync.unableToConnect") . ": " . $response["error"];
        }
        if (!$error) {
            $services = $server->services;
            foreach ($response["accounts"] as $values) {
                $syncItems[] = new SyncItem($values, $uniqueIdField, $services, $productField);
            }
        }
        $clientWelcomeEmails = \WHMCS\Mail\Template::master()->where("type", "general")->orderBy("name")->pluck("name");
        $templateData = array("error" => $error, "server" => $server, "syncItems" => $syncItems, "syncedServiceIds" => array(), "services" => $services, "sync" => $request->attributes()->get("sync", array()), "terminate" => $request->attributes()->get("terminate", array()), "import" => $request->attributes()->get("import", array()), "uniqueIdDisplayName" => $uniqueIdDisplayName ? $uniqueIdDisplayName : "Domain", "clientWelcomeEmails" => $clientWelcomeEmails);
        $body = view("admin.utilities.tools.serversync.analyse", $templateData);
        $view = new \WHMCS\Admin\ApplicationSupport\View\Html\Smarty\BodyContentWrapper();
        $view->setTitle(\AdminLang::trans("utilities.serverSync.title"))->setSidebarName("utilities")->setHelpLink("Server Sync")->setFavicon("refresh")->setBodyContent($body);
        return $view;
    }
    public function process(\WHMCS\Http\Message\ServerRequest $request)
    {
        $serverRequest = $request->request();
        $serverId = $request->attributes()->get("serverid");
        $sync = $serverRequest->get("sync");
        $import = $serverRequest->get("import");
        $terminate = $serverRequest->get("terminate");
        $clientWelcomeEmail = $serverRequest->get("client_welcome");
        $clientWelcomeEmailTemplate = "";
        if ($clientWelcomeEmail) {
            $clientWelcomeEmailTemplate = $serverRequest->get("client_welcome_email");
        }
        $passwordReset = $serverRequest->get("password_reset");
        $serviceWelcomeEmail = $serverRequest->get("service_welcome");
        $setBilling = $serverRequest->get("set_billing");
        $nextDueDate = "";
        $billingCycle = "";
        if ($setBilling) {
            $nextDueDate = $serverRequest->get("next_due_date");
            $billingCycle = $serverRequest->get("billing_cycle");
        }
        $additional = array("clientWelcomeEmail" => $clientWelcomeEmailTemplate, "passwordReset" => $passwordReset, "serviceWelcomeEmail" => $serviceWelcomeEmail, "nextDueDate" => $nextDueDate, "billingCycle" => $billingCycle);
        $server = \WHMCS\Product\Server::findOrFail($serverId);
        $moduleInterface = $server->getModuleInterface();
        if (!$moduleInterface->functionExists("ListAccounts")) {
            throw new \WHMCS\Exception\Module\NotServicable("Server does not support account sync");
        }
        $services = $server->services;
        $uniqueIdDisplayName = $moduleInterface->getMetaDataValue("ListAccountsUniqueIdentifierDisplayName");
        $uniqueIdField = $moduleInterface->getMetaDataValue("ListAccountsUniqueIdentifierField");
        $productField = $moduleInterface->getMetaDataValue("ListAccountsProductField");
        $response = $moduleInterface->call("ListAccounts", $moduleInterface->getServerParams($server));
        $imported = array();
        $synced = array();
        $terminated = array();
        $syncItems = array();
        foreach ($response["accounts"] as $values) {
            $syncItems[] = new SyncItem($values, $uniqueIdField, $services, $productField);
        }
        $importErrors = array();
        foreach ($syncItems as $syncItem) {
            $uniqueId = $syncItem->getUniqueIdentifier();
            if (in_array($uniqueId, $import)) {
                try {
                    Process::import($syncItem, $moduleInterface, $serverId, $additional);
                    $imported[] = $uniqueId;
                } catch (\Exception $e) {
                    $importErrors[] = $e->getMessage();
                }
                continue;
            }
            $syncServices = $syncItem->getServices();
            foreach ($syncServices as $syncService) {
                $syncValue = (string) $uniqueId . "||" . $syncService->getId();
                if (in_array($syncValue, $sync)) {
                    try {
                        Process::sync($syncItem, $syncService, $serverId);
                        $synced[] = $syncItem;
                    } catch (\WHMCS\Exception\Module\InvalidConfiguration $e) {
                        $importErrors[] = $e->getMessage();
                        break 2;
                    } catch (\Exception $e) {
                        $importErrors[] = $e->getMessage();
                    }
                    continue 2;
                }
            }
        }
        foreach ($terminate as $serviceId) {
            $service = $services->where("id", $serviceId)->first();
            Process::terminate($service);
            $terminated[] = $serviceId;
        }
        $templateData = array("server" => $server, "syncItems" => $syncItems, "services" => $services, "import" => $import, "sync" => $sync, "terminate" => $terminate, "imported" => $imported, "synced" => $synced, "terminated" => $terminated, "hasErrors" => 0 < count($importErrors), "errors" => $importErrors);
        $body = view("admin.utilities.tools.serversync.summary", $templateData);
        $view = new \WHMCS\Admin\ApplicationSupport\View\Html\Smarty\BodyContentWrapper();
        $view->setTitle(\AdminLang::trans("utilities.serverSync.title"))->setSidebarName("utilities")->setHelpLink("Server Sync")->setFavicon("refresh")->setBodyContent($body);
        return $view;
    }
}

?>