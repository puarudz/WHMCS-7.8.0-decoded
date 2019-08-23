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
$parameters = App::getFromRequest("parameters");
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
            if (!is_array($parameters)) {
                $parameters = array();
            }
            $moduleInterface->activate($parameters);
        } catch (WHMCS\Exception\Module\NotImplemented $e) {
            $apiresults = array("result" => "error", "message" => "Module activation not supported by module type.");
            return NULL;
        } catch (WHMCS\Exception\Module\NotActivated $e) {
            $apiresults = array("result" => "error", "message" => "Failed to activate: " . $e->getMessage());
            return NULL;
        } catch (Exception $e) {
            $apiresults = array("result" => "error", "message" => "An unexpected error occurred: " . $e->getMessage());
            return NULL;
        }
        $apiresults = array("result" => "success");
    }
}

?>