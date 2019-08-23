<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require_once dirname(__DIR__) . "/init.php";
App::redirectToRoutePath("admin-setup-payments-tax-index");

?>