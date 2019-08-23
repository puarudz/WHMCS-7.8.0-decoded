<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . "bootstrap.php";
$server = DI::make("oauth2_sso");
$response = $server->handleSingleSignOnRequest($request, $response);
Log::debug("oauth/singlesignon", array("request" => array("headers" => $request->server->getHeaders(), "request" => $request->request->all(), "query" => $request->query->all()), "response" => array("body" => $response->getContent())));
$response->send();

?>