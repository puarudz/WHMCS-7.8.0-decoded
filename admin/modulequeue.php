<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("View Module Queue");
$aInt->title = AdminLang::trans("queue.title");
$aInt->sidebar = "utilities";
$aInt->icon = "logs";
$aInt->helplink = "Module Queue";
$action = App::getFromRequest("action");
if ($action == "retry") {
    check_token("WHMCS.admin.default");
    $entryId = (int) App::getFromRequest("id");
    $hasPerformModuleOperationsPermission = $aInt->hasPermission("Perform Server Operations");
    $hasPerformRegistrarOperationsPermission = $aInt->hasPermission("Perform Registrar Operations");
    $entry = NULL;
    $response = array();
    try {
        $entry = WHMCS\Module\Queue::with("service", "addon", "domain")->findOrFail($entryId);
        switch ($entry->serviceType) {
            case "domain":
                if (!$hasPerformRegistrarOperationsPermission) {
                    throw new Exception(AdminLang::trans("permissions.nopermission"));
                }
                $entity = new WHMCS\Domains();
                $entity->getDomainsDatabyID($entry->serviceId);
                if ($entry->moduleAction == "RegisterDomain") {
                    run_hook("PreDomainRegister", array("domain" => $entity->getData("domain")));
                }
                break;
            case "addon":
                if (!$hasPerformModuleOperationsPermission) {
                    throw new Exception(AdminLang::trans("permissions.nopermission"));
                }
                $entity = new WHMCS\Addon();
                $entity->setAddonId($entry->serviceId);
                break;
            default:
                if (!$hasPerformModuleOperationsPermission) {
                    throw new Exception(AdminLang::trans("permissions.nopermission"));
                }
                $entity = new WHMCS\Service();
                $entity->setServiceID($entry->serviceId);
        }
        $entity->moduleCall($entry->moduleAction);
        $call = $entity->getModuleReturn();
        $entry->lastAttempt = WHMCS\Carbon::now();
        if (is_array($call) && array_key_exists("error", $call)) {
            $entry->lastAttemptError = $call["error"];
            $entry->numRetries++;
            $response = array("error" => true, "entryId" => $entryId, "errorMessage" => $call["error"], "message" => AdminLang::trans("queue.retryResponse", array(":error" => $call["error"])), "lastAttempt" => $entry->lastAttempt->diffForHumans());
        } else {
            switch ($entry->serviceType) {
                case "addon":
                    $entryToUpdate = $entry->addon;
                    $statusField = "status";
                    break;
                case "domain":
                    $entryToUpdate = $entry->domain;
                    break;
                default:
                    $entryToUpdate = $entry->service;
                    $statusField = "domainStatus";
            }
            switch ($entry->moduleAction) {
                case "RegisterDomain":
                    $entryToUpdate->status = "Active";
                    break;
                case "TransferDomain":
                    $entryToUpdate->status = "Pending Transfer";
                    break;
                case "CreateAccount":
                case "UnsuspendAccount":
                    $entryToUpdate->{$statusField} = "Active";
                    break;
                case "TerminateAccount":
                    $entryToUpdate->{$statusField} = "Terminated";
                    break;
                case "SuspendAccount":
                    $entryToUpdate->{$statusField} = "Suspended";
                    break;
            }
            $entryToUpdate->save();
            $entry->completed = 1;
            $response = array("entryId" => $entryId, "completed" => true);
        }
        $entry->save();
    } catch (Exception $e) {
        $response = array("error" => true, "entryId" => $entryId, "message" => $e->getMessage());
    }
    $aInt->jsonResponse($response);
}
if ($action == "resolve") {
    check_token("WHMCS.admin.default");
    $entryId = (int) App::getFromRequest("id");
    try {
        $entry = WHMCS\Module\Queue::findOrFail($entryId);
        $entry->completed = 1;
        $entry->save();
        $response = array("entryId" => $entryId, "completed" => true, "message" => AdminLang::trans("queue.markedResolved"));
    } catch (Exception $e) {
        $response = array("error" => true, "entryId" => $entryId, "message" => $e->getMessage());
    }
    $aInt->jsonResponse($response);
}
$whmcs = App::self();
$queueData = localAPI("GetModuleQueue", array("serviceType" => $whmcs->getFromRequest("serviceType"), "moduleName" => $whmcs->getFromRequest("moduleName"), "moduleAction" => $whmcs->getFromRequest("moduleAction"), "since" => $whmcs->getFromRequest("since")));
$queue = $queueData["queue"];
$queueCount = $queueData["count"];
$clients = $products = $modules = $addons = array();
$clientIds = $productIds = $addonIds = array();
foreach ($queue as $entry) {
    switch ($entry->serviceType) {
        case "domain":
            $clientIds[] = $entry->domain->clientId;
            break;
        case "addon":
            $clientIds[] = $entry->addon->clientId;
            $addonIds[] = $entry->addon->addonId;
            break;
        default:
            $clientIds[] = $entry->service->clientId;
            $productIds[] = $entry->service->packageId;
    }
}
if ($clientIds) {
    foreach (WHMCS\User\Client::whereIn("id", $clientIds)->get() as $client) {
        $clients[$client->id] = $client->toArray();
    }
}
if ($productIds) {
    foreach (WHMCS\Product\Product::whereIn("id", $productIds)->get() as $product) {
        $products[$product->id] = $product->toArray();
    }
}
if ($addonIds) {
    foreach (WHMCS\Product\Addon::whereIn("id", $addonIds)->get() as $addon) {
        $addons[$addon->id] = $addon->toArray();
    }
}
$modules["domain"] = array();
$modules["service"] = array();
$registrars = new WHMCS\Module\Registrar();
foreach ($registrars->getList() as $registrar) {
    $registrars->load($registrar);
    $modules["domain"][$registrars->getLoadedModule()] = $registrars->getDisplayName();
}
unset($registrars);
$serviceModules = new WHMCS\Module\Server();
foreach ($serviceModules->getList() as $serviceModule) {
    $serviceModules->load($serviceModule);
    $modules["service"][$serviceModules->getLoadedModule()] = $serviceModules->getDisplayName();
}
unset($serviceModules);
ob_start();
echo "\n<div class=\"alert alert-info\">\n    ";
echo AdminLang::trans($queueCount == 1 ? "queue.numberItem" : "queue.numberItems", array(":count" => $queueCount));
if ($queueCount) {
    echo "    <div class=\"pull-right\">\n        <button type=\"button\" class=\"btn btn-default retry-all\">\n            <i class=\"fas fa-fw fa-sync\"></i> ";
    echo AdminLang::trans("queue.retryAll");
    echo "        </button>\n    </div>\n    ";
}
echo "</div>\n\n<div class=\"module-queue-header\">\n    <div class=\"row\">\n        <div class=\"col-sm-3\">\n            ";
echo AdminLang::trans("queue.clientService");
echo "        </div>\n        <div class=\"col-sm-2\">\n            ";
echo AdminLang::trans("queue.moduleAction");
echo "        </div>\n        <div class=\"col-sm-7\">\n            ";
echo AdminLang::trans("queue.failureReason");
echo "        </div>\n    </div>\n</div>\n<div class=\"module-queue\">\n    ";
foreach ($queue as $entry) {
    switch ($entry->serviceType) {
        case "domain":
            $client = $clients[$entry->domain->clientId];
            $product = "<a class=\"autoLinked\" href=\"clientsdomains.php?id=" . $entry->serviceId . "\">" . AdminLang::trans("fields.domain") . "</a>: <a class=\"autoLinked\" href=\"http://" . $entry->domain->domain . "\">" . $entry->domain->domain . "</a>";
            $moduleName = $modules["domain"][$entry->domain->registrarModuleName];
            break;
        case "addon":
            $client = $clients[$entry->addon->clientId];
            $thisAddon = $addons[$entry->addon->addonId];
            $product = "<a class=\"autoLinked\" href=\"clientsservices.php?userid=" . $entry->addon->clientId . "&aid=" . $entry->serviceId . "&id=" . $entry->addon->service->id . "\">" . $thisAddon["name"] . "</a>";
            if ($entry->addon->service->domain) {
                $product .= " - <a class=\"autoLinked\" href=\"http://" . $entry->addon->service->domain . "\">" . $entry->addon->service->domain . "</a>";
            }
            $moduleName = $modules["service"][$thisAddon["module"]];
            break;
        default:
            $thisProduct = $products[$entry->service->packageId];
            $client = $clients[$entry->service->clientId];
            $product = "<a class=\"autoLinked\" href=\"clientsservices.php?id=" . $entry->serviceId . "\">" . $thisProduct["name"] . "</a> - <a class=\"autoLinked\" href=\"http://" . $entry->service->domain . "\">" . $entry->service->domain . "</a>";
            $moduleName = $modules["service"][$thisProduct["servertype"]];
    }
    if (!$moduleName) {
        $moduleName = AdminLang::trans("global.unknown");
    }
    $clientName = $client["fullName"];
    if ($client["companyname"]) {
        $clientName .= " (" . $client["companyname"] . ")";
    }
    $client = sprintf("<a href=\"clientssummary.php?userid=%d\">%s</a>", $client["id"], $clientName);
    echo "        <div id=\"entry-";
    echo $entry->id;
    echo "\" class=\"entry\">\n            <div class=\"row\">\n                <div class=\"col-sm-3\">\n                    ";
    echo $client;
    echo "<br />\n                    <small>";
    echo $product;
    echo "</small>\n                </div>\n                <div class=\"col-md-2\">\n                    ";
    echo $moduleName;
    echo " / ";
    echo $entry->moduleAction;
    echo "                </div>\n                <div class=\"col-sm-7\">\n                    <div class=\"btn-group pull-right action-buttons\" role=\"group\">\n                        <button id=\"btn-retry-";
    echo $entry->id;
    echo "\" type=\"button\" class=\"btn btn-default btn-sm retry\" data-entry-id=\"";
    echo $entry->id;
    echo "\">\n                            <i class=\"fas fa-fw fa-sync\"></i> ";
    echo AdminLang::trans("global.retry");
    echo "                        </button>\n                        <button type=\"button\" class=\"btn btn-success btn-sm resolve\" data-entry-id=\"";
    echo $entry->id;
    echo "\">\n                            <i class=\"fas fa-fw fa-check\"></i> ";
    echo AdminLang::trans("queue.markResolved");
    echo "                        </button>\n                    </div>\n                    <span id=\"last-error-";
    echo $entry->id;
    echo "\">";
    echo $entry->lastAttemptError;
    echo "</span>\n                    <br>\n                    <small class=\"last-attempt\">\n                        ";
    echo AdminLang::trans("queue.lastAttempt");
    echo ": <span>";
    echo $entry->lastAttempt->diffForHumans();
    echo "</span>\n                    </small>\n                </div>\n            </div>\n            <div id=\"processing-entry-";
    echo $entry->id;
    echo "\" class=\"row hidden\">\n                <div class=\"col-sm-12 messages\">\n                    <div class=\"processing\">\n                        <i class=\"fas fa-fw fa-spinner fa-spin\"></i>\n                        <span>";
    echo AdminLang::trans("queue.communicating");
    echo "</span>\n                    </div>\n                    <div class=\"queued\">\n                        <i class=\"fas fa-fw fa-pause\"></i>\n                        <span>";
    echo AdminLang::trans("queue.queued");
    echo "</span>\n                    </div>\n                    <div class=\"success\">\n                        <i class=\"fas fa-fw fa-check\"></i>\n                        <span>";
    echo AdminLang::trans("queue.retrySuccess");
    echo "</span>\n                    </div>\n                    <div class=\"error alert alert-danger\">\n                        <i class=\"fas fa-fw fa-times\"></i>\n                        <span></span>\n                    </div>\n                </div>\n            </div>\n        </div>\n    ";
}
echo "    ";
if (!$queueCount) {
    echo "        <div class=\"entry empty-entry\">\n            <div class=\"row\">\n                <div class=\"col-md-12 text-center\">\n                    ";
    echo AdminLang::trans("queue.noItems");
    echo "                </div>\n            </div>\n        </div>\n    ";
}
echo "</div>\n\n";
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->display();

?>