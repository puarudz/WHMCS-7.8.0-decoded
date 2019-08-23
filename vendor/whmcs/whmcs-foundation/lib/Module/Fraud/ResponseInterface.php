<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Fraud;

interface ResponseInterface
{
    public function __construct($jsonData, $httpCode);
    public function isSuccessful();
    public function getHttpCode();
    public function isEmpty();
    public function get($key);
    public function toArray();
    public function toJson();
}

?>