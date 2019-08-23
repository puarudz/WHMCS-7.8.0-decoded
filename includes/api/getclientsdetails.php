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
if (!function_exists("getClientsDetails")) {
    require ROOTDIR . "/includes/clientfunctions.php";
}
$where = array();
if ($clientid) {
    $where["id"] = $clientid;
} else {
    if ($email) {
        $where["email"] = $email;
    } else {
        $apiresults = array("result" => "error", "message" => "Either clientid Or email Is Required");
        return NULL;
    }
}
$client = WHMCS\Database\Capsule::table("tblclients");
if ($clientid) {
    $client->where("id", $clientid);
} else {
    if ($email) {
        $client->where("email", $email);
    } else {
        $apiresults = array("result" => "error", "message" => "Either clientid Or email Is Required");
        return NULL;
    }
}
if ($client->count() === 0) {
    $apiresults = array("result" => "error", "message" => "Client Not Found");
} else {
    $clientid = $client->value("id");
    $clientsdetails = getClientsDetails($clientid);
    unset($clientsdetails["model"]);
    $currency_result = full_query("SELECT code FROM tblcurrencies WHERE id=" . (int) $clientsdetails["currency"]);
    $currency = mysql_fetch_assoc($currency_result);
    $clientsdetails["currency_code"] = $currency["code"];
    $apiresults = array_merge(array("result" => "success"), $clientsdetails);
    if ($clientsdetails["cctype"]) {
        $apiresults["warning"] = "Credit Card related parameters are now deprecated " . "and have been removed. Use GetPayMethods instead.";
    }
    unset($clientsdetails["cctype"]);
    unset($clientsdetails["cclastfour"]);
    unset($clientsdetails["gatewayid"]);
    $userRequestedResponseType = is_object($request) ? $request->getResponseFormat() : NULL;
    if (is_null($userRequestedResponseType) || WHMCS\Api\ApplicationSupport\Http\ResponseFactory::isTypeHighlyStructured($userRequestedResponseType)) {
        $apiresults["client"] = $clientsdetails;
        if ($stats || $userRequestedResponseType == WHMCS\Api\ApplicationSupport\Http\ResponseFactory::RESPONSE_FORMAT_XML) {
            $apiresults["stats"] = getClientsStats($clientid);
        }
    }
}

?>