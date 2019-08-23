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
$domains = new WHMCS\Domains();
$domainparts = $domains->splitAndCleanDomainInput($domain);
$isValid = $domains->checkDomainisValid($domainparts);
if ($isValid) {
    $whois = new WHMCS\WHOIS();
    if ($whois->canLookup($domainparts["tld"])) {
        $result = $whois->lookup($domainparts);
        $userRequestedResponseType = is_object($request) ? $request->getResponseFormat() : NULL;
        if (is_null($userRequestedResponseType) || WHMCS\Api\ApplicationSupport\Http\ResponseFactory::isTypeHighlyStructured($userRequestedResponseType)) {
            $whois = $result["whois"];
        } else {
            $whois = urlencode($result["whois"]);
        }
        if (function_exists("mb_convert_encoding") && $userRequestedResponseType == WHMCS\Api\ApplicationSupport\Http\ResponseFactory::RESPONSE_FORMAT_JSON) {
            $whois = mb_convert_encoding($whois, "UTF-8", mb_detect_encoding($whois));
        }
        $result["whois"] = $whois;
        $apiresults = array("result" => "success", "status" => $result["result"], "whois" => $result["whois"]);
    } else {
        $apiresults = array("result" => "error", "message" => "The given TLD is not supported for WHOIS lookups");
        return false;
    }
} else {
    $apiresults = array("result" => "error", "message" => "Domain not valid");
    return false;
}

?>