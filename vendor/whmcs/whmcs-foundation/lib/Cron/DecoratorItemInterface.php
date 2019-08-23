<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron;

interface DecoratorItemInterface
{
    public function getIcon();
    public function getName();
    public function getSuccessCountIdentifier();
    public function getFailureCountIdentifier();
    public function getSuccessKeyword();
    public function getFailureKeyword();
    public function getFailureUrl();
    public function isBooleanStatusItem();
}

?>