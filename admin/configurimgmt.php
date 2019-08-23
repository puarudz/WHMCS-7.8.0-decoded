<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Configure General Settings");
$aInt->title = "URI Path Management";
$aInt->sidebar = "config";
$aInt->icon = "autosettings";
$aInt->helplink = "URI Path Management";
$response = "";
$action = App::get_req_var("action");
$request = WHMCS\Http\Message\ServerRequest::fromGlobals();
$action = $request->get("action", "view");
$configurationController = new WHMCS\Admin\Setup\General\UriManagement\ConfigurationController();
if ($action == "toggle") {
    check_token("WHMCS.admin.default");
    $response = $configurationController->updateUriManagementSetting($request);
} else {
    if ($action == "updateUriPathMode") {
        check_token("WHMCS.admin.default");
        $response = $configurationController->setEnvironmentMode($request);
    } else {
        if ($action == "synchronize") {
            check_token("WHMCS.admin.default");
            $response = $configurationController->synchronizeRules($request);
        } else {
            if ($action == "remoteDetectEnvironmentModeAndSet") {
                check_token("WHMCS.admin.default");
                $request = $request->withAttribute("setDetected", true);
                $response = $configurationController->remoteDetectEnvironmentMode($request);
            } else {
                if ($action == "applyBestConfiguration") {
                    check_token("WHMCS.admin.default");
                    $response = $configurationController->applyBestConfiguration($request);
                } else {
                    if ($action == "modal-view") {
                        $request = $request->withAttribute("modal-view", true);
                        $response = $configurationController->view($request);
                    } else {
                        $response = $configurationController->view($request);
                    }
                }
            }
        }
    }
}
(new Zend\Diactoros\Response\SapiEmitter())->emit($response);
exit;

?>