<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require_once __DIR__ . DIRECTORY_SEPARATOR . "bootstrap.php";
$server = DI::make("oauth2_server");
if (is_null($scope)) {
    $scope = $server->getScopeUtil()->getScopeFromRequest($request);
}
if ($server->verifyResourceRequest($request, $response)) {
    $token = $server->getAccessToken($request);
    $response->setData(array("success" => true, "message" => sprintf("Token '%s' is valid for scope(s) '%s'", $token->accessToken, $token->scope)));
}
Log::debug("oauth/resource", array("request" => array("headers" => $request->server->getHeaders(), "request" => $request->request->all(), "query" => $request->query->all()), "response" => array("body" => $response->getContent())));
$response->send();

?>