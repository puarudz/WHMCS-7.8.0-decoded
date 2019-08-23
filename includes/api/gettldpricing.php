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
if (!function_exists("getTLDList")) {
    require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "domainfunctions.php";
}
$currencyId = (int) App::getFromRequest("currencyid");
$userId = (int) App::getFromRequest("clientid");
$clientGroupId = 0;
if ($userId) {
    $client = WHMCS\User\Client::find($userId);
    $userId = $client->id;
    $currencyId = $client->currencyId;
    $clientGroupId = $client->groupId;
}
$currency = getCurrency("", $currencyId);
$pricing = array();
$result = WHMCS\Database\Capsule::table("tblpricing")->whereIn("type", array("domainregister", "domaintransfer", "domainrenew"))->where("currency", $currency["id"])->where("tsetupfee", 0)->get();
foreach ($result as $data) {
    $pricing[$data->relid][$data->type] = get_object_vars($data);
}
if ($clientGroupId) {
    $result2 = WHMCS\Database\Capsule::table("tblpricing")->whereIn("type", array("domainregister", "domaintransfer", "domainrenew"))->where("currency", $currency["id"])->where("tsetupfee", $clientGroupId)->get();
    foreach ($result2 as $data) {
        $pricing[$data->relid][$data->type] = get_object_vars($data);
    }
}
$tldIds = array();
$tldGroups = array();
$tldAddons = array();
$result = WHMCS\Database\Capsule::table("tbldomainpricing")->get(array("id", "extension", "dnsmanagement", "emailforwarding", "idprotection", "group"));
foreach ($result as $data) {
    $ext = ltrim($data->extension, ".");
    $tldIds[$ext] = $data->id;
    $tldGroups[$ext] = $data->group != "" && $data->group != "none" ? $data->group : "";
    $tldAddons[$ext] = array("dns" => (bool) $data->dnsmanagement, "email" => (bool) $data->emailforwarding, "idprotect" => (bool) $data->idprotection);
}
$extensions = WHMCS\Domains\Extension::all();
$extensionsByTld = array();
foreach ($extensions as $extension) {
    $tld = ltrim($extension->extension, ".");
    $extensionsByTld[$tld] = $extension;
}
$tldList = array_keys($extensionsByTld);
$periods = array("msetupfee" => 1, "qsetupfee" => 2, "ssetupfee" => 3, "asetupfee" => 4, "bsetupfee" => 5, "monthly" => 6, "quarterly" => 7, "semiannually" => 8, "annually" => 9, "biennially" => 10);
$categories = array();
$result = WHMCS\Database\Capsule::table("tbltlds")->join("tbltld_category_pivot", "tbltld_category_pivot.tld_id", "=", "tbltlds.id")->join("tbltld_categories", "tbltld_categories.id", "=", "tbltld_category_pivot.category_id")->whereIn("tld", $tldList)->get();
foreach ($result as $data) {
    $categories[$data->tld][] = $data->category;
}
$usedTlds = array_keys($categories);
$missedTlds = array_values(array_filter($tldList, function ($key) use($usedTlds) {
    return !in_array($key, $usedTlds);
}));
if ($missedTlds) {
    foreach ($missedTlds as $missedTld) {
        $categories[$missedTld][] = "Other";
    }
}
$apiresults = array("result" => "success", "currency" => $currency);
foreach ($tldList as $tld) {
    $tldId = $tldIds[$tld];
    $apiresults["pricing"][$tld]["categories"] = $categories[$tld];
    $apiresults["pricing"][$tld]["addons"] = $tldAddons[$tld];
    $apiresults["pricing"][$tld]["group"] = $tldGroups[$tld];
    foreach (array("domainregister", "domaintransfer", "domainrenew") as $type) {
        foreach ($pricing[$tldId][$type] as $key => $price) {
            if (array_key_exists($key, $periods) && ($type == "domainregister" && 0 <= $price || $type == "domaintransfer" && 0 < $price || $type == "domainrenew" && 0 < $price)) {
                $apiresults["pricing"][$tld][str_replace("domain", "", $type)][$periods[$key]] = $price;
            }
        }
    }
    if (isset($extensionsByTld[$tld])) {
        $extension = $extensionsByTld[$tld];
        $apiresults["pricing"][$tld]["grace_period"] = NULL;
        if (0 <= $extension->grace_period_fee) {
            $gracePeriodFee = convertCurrency($extension->grace_period_fee, 1, $currency["id"]);
            $apiresults["pricing"][$tld]["grace_period"] = array("days" => 0 <= $extension->grace_period ? $extension->grace_period : $extension->defaultGracePeriod, "price" => new WHMCS\View\Formatter\Price($gracePeriodFee, $currency));
        }
        $apiresults["pricing"][$tld]["redemption_period"] = NULL;
        if (0 <= $extension->redemption_grace_period_fee) {
            $redemptionGracePeriodFee = convertCurrency($extension->redemption_grace_period_fee, 1, $currency["id"]);
            $apiresults["pricing"][$tld]["redemption_period"] = array("days" => 0 <= $extension->redemption_grace_period ? $extension->redemption_grace_period : $extension->defaultRedemptionGracePeriod, "price" => new WHMCS\View\Formatter\Price($redemptionGracePeriodFee, $currency));
        }
    } else {
        continue;
    }
}

?>