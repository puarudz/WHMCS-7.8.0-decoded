<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require "../../../init.php";
$gatewayModuleName = "tco";
App::load_function("gateway");
App::load_function("invoice");
try {
    $requestHelper = new WHMCS\Module\Gateway\TCO\CallbackRequestHelper(WHMCS\Http\Message\ServerRequest::fromGlobals());
    $gatewayParams = $requestHelper->getGatewayParams();
    $callable = $requestHelper->getCallable();
    $result = call_user_func($callable, $gatewayParams);
} catch (Exception $e) {
    WHMCS\Terminus::getInstance()->doDie($e->getMessage());
}

?>