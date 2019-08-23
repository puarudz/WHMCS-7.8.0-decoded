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
$moduleType = $whmcs->getFromRequest("moduleType");
$moduleName = $whmcs->getFromRequest("moduleName");
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
            $configurationParams = $moduleInterface->getConfiguration();
            $paramsToReturn = array();
            if (is_array($configurationParams)) {
                foreach ($configurationParams as $key => $values) {
                    if ($values["Type"] == "System") {
                        if ($key != "FriendlyName") {
                            continue;
                        }
                        $values["Type"] = "text";
                    }
                    $paramsToReturn[] = array("name" => $key, "displayName" => $values["FriendlyName"] ?: $key, "fieldType" => $values["Type"]);
                }
            }
        } catch (WHMCS\Exception\Module\NotImplemented $e) {
            $apiresults = array("result" => "error", "message" => "Get module configuration parameters not supported by module type.");
            return NULL;
        } catch (Exception $e) {
            $apiresults = array("result" => "error", "message" => "An unexpected error occurred: " . $e->getMessage());
            return NULL;
        }
        $apiresults = array("result" => "success", "parameters" => $paramsToReturn);
    }
}

?>