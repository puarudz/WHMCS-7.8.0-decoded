<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Scheduling\Contract;

interface JobInterface
{
    public function jobName($name);
    public function jobClassName($className);
    public function jobMethodName($methodName);
    public function jobMethodArguments($arguments);
    public function jobAvailableAt(\WHMCS\Carbon $date);
    public function jobDigestHash($hash);
}

?>