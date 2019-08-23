<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require dirname(__DIR__) . "/init.php";
$aInt = new WHMCS\Admin("Manage MarketConnect");
$aInt->title = AdminLang::trans("setup.marketconnect");
$aInt->requireAuthConfirmation();
$request = WHMCS\Http\Message\ServerRequest::fromGlobals();
$adminController = new WHMCS\MarketConnect\AdminController();
$aInt->setBodyContent($adminController->dispatch($request));
$aInt->display();

?>