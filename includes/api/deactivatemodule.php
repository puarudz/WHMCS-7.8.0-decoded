<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
$moduleType = App::getFromRequest("moduleType");
$moduleName = App::getFromRequest("moduleName");
$newGateway = App::getFromRequest("newGateway");
$supportedModuleTypes = array("gateway", "registrar", "addon", "fraud");
if (!in_array($moduleType, $supportedModuleTypes)) {
    $apiresults = array("result" => "error", "message" => "Invalid module type provided. Supported module types include: " . implode(", ", $supportedModuleTypes));
} else {
    $moduleClassName = "\\WHMCS\\Module\\" . ucfirst($moduleType);
    $moduleInterface = new $moduleClassName();
    if (!in_array($moduleName, $moduleInterface->getList())) {
        $apiresults = array("result" => "error", "message" => "Invalid module name provided.");
    } else {
        $moduleInterface->load($moduleName);
        try {
            $parameters = array("newGateway" => $newGateway);
            $moduleInterface->deactivate($parameters);
        } catch (WHMCS\Exception\Module\NotImplemented $e) {
            $apiresults = array("result" => "error", "message" => "Module deactivation not supported by module type.");
            return NULL;
        } catch (WHMCS\Exception\Module\NotActivated $e) {
            $apiresults = array("result" => "error", "message" => "Failed to deactivate: " . $e->getMessage());
            return NULL;
        } catch (Exception $e) {
            $apiresults = array("result" => "error", "message" => "An unexpected error occurred: " . $e->getMessage());
            return NULL;
        }
        $apiresults = array("result" => "success");
    }
}

?>