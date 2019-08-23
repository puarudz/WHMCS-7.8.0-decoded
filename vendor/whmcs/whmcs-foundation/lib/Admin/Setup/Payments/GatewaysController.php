<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Setup\Payments;

class GatewaysController
{
    public function handleOnboardingReturn(\WHMCS\Http\Message\ServerRequest $request)
    {
        $gateway = $request->get("gateway");
        $success = (bool) $request->get("success");
        if ($gateway == "gocardless") {
            $accessToken = $request->get("accessToken");
            $callbackSecret = $request->get("callbackSecret");
            $adminBaseUrl = \App::getSystemURL() . \App::get_admin_folder_name() . "/";
            if ($success) {
                $gatewayInterface = new \WHMCS\Module\Gateway();
                $gatewayInterface->load("gocardless");
                if ($gatewayInterface->isLoadedModuleActive()) {
                    $method = "updateConfiguration";
                    $action = "updated";
                } else {
                    $method = "activate";
                    $action = "activated";
                }
                $gatewayInterface->{$method}(array("accessToken" => $accessToken, "callbackToken" => $callbackSecret));
                return new \Zend\Diactoros\Response\RedirectResponse($adminBaseUrl . "configgateways.php?" . $action . "=gocardless#m_gocardless");
            }
            return new \Zend\Diactoros\Response\RedirectResponse($adminBaseUrl . "configgateways.php?obfailed=1");
        }
        throw new \WHMCS\Exception("You must upgrade to be able to use this payment gateway.");
    }
    public function callAdditionalFunction(\WHMCS\Http\Message\ServerRequest $request)
    {
        $gateway = $request->get("gateway");
        $method = $request->get("method");
        $gatewayInterface = new \WHMCS\Module\Gateway();
        if ($gatewayInterface->load($gateway) && $gatewayInterface->functionExists("admin_area_actions")) {
            $additionalFunctions = $gatewayInterface->call("admin_area_actions");
            foreach ($additionalFunctions as $data) {
                if (!is_array($data)) {
                    throw new \WHMCS\Exception\Module\NotServicable("Invalid Function Return");
                }
                $methodName = $data["actionName"];
                if ($methodName == $method) {
                    return new \WHMCS\Http\Message\JsonResponse($gatewayInterface->call($method, array("gatewayInterface" => $gatewayInterface)));
                }
            }
        }
        throw new \WHMCS\Payment\Exception\InvalidModuleException("Invalid Access Attempt");
    }
}

?>