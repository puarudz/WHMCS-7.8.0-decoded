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
$serviceType = $whmcs->getFromRequest("serviceType");
$moduleName = $whmcs->getFromRequest("moduleName");
$moduleAction = $whmcs->getFromRequest("moduleAction");
$since = $whmcs->getFromRequest("since");
$acceptedServiceTypes = array("service", "domain");
if (!in_array($serviceType, $acceptedServiceTypes)) {
    $serviceType = "";
}
$queue = WHMCS\Module\Queue::incomplete();
switch ($serviceType) {
    case "service":
        $queue = $queue->with("service");
        break;
    case "domain":
        $queue = $queue->with("domain");
        break;
    default:
        $queue = $queue->with("service", "domain");
        break;
}
if ($moduleName) {
    $queue = $queue->whereModuleName($moduleName);
}
if ($moduleAction) {
    $queue = $queue->whereModuleAction($moduleName);
}
if ($since) {
    try {
        $since = trim($since);
        if (strlen($since) == 10) {
            $since .= " 00:00:00";
        }
        $since = WHMCS\Carbon::createFromFormat("Y-m-d H:i:s", $since);
        $queue = $queue->where("last_attempt", ">=", $since->toDateTimeString());
    } catch (Exception $e) {
    }
}
$queue = $queue->get();
$apiresults = array("result" => "success", "count" => $queue->count(), "queue" => $queue);
$responsetype = "xml";

?>