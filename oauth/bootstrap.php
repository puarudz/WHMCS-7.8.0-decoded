<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

ini_set("eaccelerator.enable", 0);
ini_set("eaccelerator.optimizer", 0);
if (!defined("ROOTDIR")) {
    define("ROOTDIR", dirname(__DIR__));
}
if (!defined("WHMCS")) {
    define("WHMCS", true);
}
if (!defined("WHMCS_OAUTH")) {
    define("WHMCS_OAUTH", true);
}
if (file_exists(ROOTDIR . DIRECTORY_SEPARATOR . "c3.php")) {
    include ROOTDIR . DIRECTORY_SEPARATOR . "c3.php";
}
require_once ROOTDIR . "/vendor/autoload.php";
$errMgmt = WHMCS\Utility\ErrorManagement::boot();
$errMgmt::disableIniDisplayErrors();
$errMgmt::setErrorReportingLevel(error_reporting());
require_once ROOTDIR . "/includes/functions.php";
require_once ROOTDIR . "/includes/dbfunctions.php";
$runtimeStorage = new WHMCS\Config\RuntimeStorage();
$runtimeStorage->errorManagement = $errMgmt;
WHMCS\Utility\Bootstrap\OauthServer::boot($runtimeStorage);
$errMgmt->loadApplicationHandlers()->loadDeferredHandlers();
Log::debug("Installer bootstrapped");
WHMCS\Security\Environment::setHttpProxyHeader(DI::make("config")->outbound_http_proxy);
$request = OAuth2\HttpFoundationBridge\Request::createFromGlobals();
$response = new OAuth2\HttpFoundationBridge\Response();
$response->prepare($request);

?>