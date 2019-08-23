<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require_once "init.php";
$controller = new WHMCS\ClientArea\PasswordResetController();
$request = WHMCS\Http\Message\ServerRequest::fromGlobals();
$response = NULL;
if ($_SERVER["REQUEST_METHOD"] === "POST" && $request->has("email")) {
    $response = $controller->validateEmail($request);
}
if (!$response) {
    $response = $controller->emailPrompt($request);
}
(new Zend\Diactoros\Response\SapiEmitter())->emit($response);

?>