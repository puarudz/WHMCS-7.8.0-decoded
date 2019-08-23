<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . "init.php";
$response = new Symfony\Component\HttpFoundation\JsonResponse();
$content = "";
$cacheKey = "OIDC-Discovery-Document";
$cache = new WHMCS\TransientData();
if ($cachedDiscovery = $cache->retrieve($cacheKey)) {
    $content = $cachedDiscovery;
} else {
    $server = DI::make("oauth2_server");
    $content = jsonPrettyPrint($server->getDiscoveryDocument());
    $cache->store($cacheKey, $content, 60);
}
$response->setContent($content);
$response->send();

?>