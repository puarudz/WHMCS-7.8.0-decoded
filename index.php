<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("CLIENTAREA", true);
require_once __DIR__ . "/init.php";
$request = WHMCS\Http\Message\ServerRequest::fromGlobals();
$response = DI::make("Frontend\\Dispatcher")->dispatch($request);
$statusCode = $response->getStatusCode();
$statusFamily = substr($statusCode, 0, 1);
if (!in_array($statusFamily, array(2, 3)) && !($response instanceof WHMCS\Http\Message\JsonResponse || $response instanceof Zend\Diactoros\Response\JsonResponse || $response instanceof WHMCS\Admin\ApplicationSupport\View\Html\Smarty\ErrorPage || $response instanceof WHMCS\ClientArea) && $statusCode === 404) {
    gracefulCoreRequiredFileInclude("/includes/clientareafunctions.php");
    $response = new WHMCS\ClientArea();
    $response->setPageTitle("404 - Page Not Found");
    $response->setTemplate("error/page-not-found");
    $response->skipMainBodyContainer();
    $response = $response->withStatus(404);
}
(new Zend\Diactoros\Response\SapiEmitter())->emit($response);
exit;

?>