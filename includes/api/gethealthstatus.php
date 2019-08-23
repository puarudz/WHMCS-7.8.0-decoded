<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
$healthChecks = new WHMCS\View\Admin\HealthCheck\HealthCheckRepository();
$keyChecks = $healthChecks->keyChecks();
$nonKeyChecks = $healthChecks->nonKeyChecks();
$allChecks = $keyChecks->merge($nonKeyChecks);
$healthChecks = $allChecks->reduce(function ($results = 0, WHMCS\View\Admin\HealthCheck\HealthCheckResult $result) {
    $results = is_null($results) ? array() : $results;
    switch ($result->getSeverityLevel()) {
        case PSR\Log\LogLevel::INFO:
        case PSR\Log\LogLevel::NOTICE:
            $results["success"][] = $result->toArray();
            break;
        case PSR\Log\LogLevel::WARNING:
            $results["warning"][] = $result->toArray();
            break;
        case PSR\Log\LogLevel::ERROR:
        case PSR\Log\LogLevel::CRITICAL:
        case PSR\Log\LogLevel::ALERT:
        case PSR\Log\LogLevel::EMERGENCY:
            $results["danger"][] = $result->toArray();
            break;
    }
    return $results;
});
$apiresults = array("result" => "success", "checks" => array("success" => $healthChecks["success"], "warning" => $healthChecks["warning"], "danger" => $healthChecks["danger"]));

?>