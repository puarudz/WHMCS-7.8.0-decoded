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
$credentialId = (int) $whmcs->getFromRequest("credentialId");
$client = WHMCS\ApplicationLink\Client::find($credentialId);
if (is_null($client)) {
    $apiresults = array("result" => "error", "message" => "Invalid Credential ID provided.");
} else {
    $client->delete();
    $apiresults = array("result" => "success", "credentialId" => $credentialId);
}

?>