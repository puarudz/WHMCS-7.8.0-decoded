<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require_once "init.php";
$rss = new WHMCS\Announcement\Rss();
$request = Zend\Diactoros\ServerRequestFactory::fromGlobals();
$response = $rss->toXml($request);
(new Zend\Diactoros\Response\SapiEmitter())->emit($response);

?>