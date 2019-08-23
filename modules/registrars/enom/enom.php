<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function enom_getConfigArray()
{
    $configArray = array("Description" => array("Type" => "System", "Value" => "Don't have an Enom Account yet? Get one here:" . " <a href=\"http://go.whmcs.com/82/enom\" target=\"_blank\">www.whmcs.com/partners/enom</a>"), "Username" => array("Type" => "text", "Size" => "20", "Description" => "Enter your Enom Reseller Account Username here"), "Password" => array("FriendlyName" => "API Token", "Type" => "password", "Size" => "35", "Description" => "Don't have an API Token?" . " <a href=\"https://www.enom.com/apitokens\" target=\"_blank\">Create one now</a>"), "TestMode" => array("FriendlyName" => "Enable Test Mode", "Type" => "yesno"), "DisableIRTP" => array("FriendlyName" => "Disable IRTP", "Type" => "yesno", "Description" => "Disable IRTP for the eNom Module." . " This will remove all notices in WHMCS relating to verification of contact information."), "DefaultNameservers" => array("FriendlyName" => "Use Default Nameservers", "Type" => "yesno", "Description" => "Tick this box to use the default Enom nameservers for new domain registrations"));
    return $configArray;
}
function enom_CheckAvailability($params)
{
    $sld = $params["sld"];
    $tlds = $params["tlds"];
    foreach ($tlds as $key => $tld) {
        $tlds[$key] = ltrim($tld, ".");
    }
    $domains = array_map(function ($tld) use($sld) {
        return $sld . "." . $tld;
    }, $tlds);
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $results = new WHMCS\Domains\DomainLookup\ResultsList();
    $maxDomainsInRequest = 30;
    foreach (array_chunk($domains, $maxDomainsInRequest) as $domainChunk) {
        $Enom->NewRequest();
        $Enom->AddParam("uid", $params["Username"]);
        $Enom->AddParam("pw", $params["Password"]);
        $Enom->AddParam("DomainList", implode(",", $domainChunk));
        $Enom->AddParam("command", "check");
        $Enom->AddParam("IncludePrice", true);
        $Enom->DoTransaction($params);
        if (0 < $Enom->Values["ErrCount"]) {
            throw new Exception($Enom->Values["Err1"]);
        }
        $premiumPricing = $premiumDomains = array();
        if ($params["premiumEnabled"]) {
            for ($i = 1; $i <= $Enom->Values["DomainCount"]; $i++) {
                if ($Enom->Values["IsPremiumName" . $i] == "true") {
                    $premiumDomains[] = $Enom->Values["Domain" . $i];
                    $premiumPricing[$Enom->Values["Domain" . $i]] = array("register" => $Enom->Values["PremiumPrice" . $i], "renew" => $Enom->Values["RenewalPrice" . $i], "transfer" => $Enom->Values["TransferPrice" . $i]);
                }
            }
        }
        $domainCount = $Enom->Values["DomainCount"];
        for ($i = 1; $i <= $domainCount; $i++) {
            $domain = $Enom->Values["Domain" . $i];
            $rrpCode = $Enom->Values["RRPCode" . $i];
            $parts = explode(".", $domain, 2);
            $domainSearchResult = new WHMCS\Domains\DomainLookup\SearchResult($parts[0], $parts[1]);
            if ($rrpCode == 210) {
                $status = $domainSearchResult::STATUS_NOT_REGISTERED;
            } else {
                if ($rrpCode == 827) {
                    $status = $domainSearchResult::STATUS_TLD_NOT_SUPPORTED;
                } else {
                    $status = $domainSearchResult::STATUS_REGISTERED;
                }
            }
            $domainSearchResult->setStatus($status);
            if ($params["premiumEnabled"] && $Enom->Values["IsPremiumName" . $i] == "true" && array_key_exists($Enom->Values["Domain" . $i], $premiumPricing)) {
                $domainSearchResult->setPremiumDomain(true);
                $pricing = $premiumPricing[$Enom->Values["Domain" . $i]];
                $pricing["CurrencyCode"] = "USD";
                $domainSearchResult->setPremiumCostPricing($pricing);
            } else {
                if (!$params["premiumEnabled"] && $Enom->Values["IsPremiumName" . $i] == "true") {
                    $domainSearchResult->setStatus($domainSearchResult::STATUS_RESERVED);
                }
            }
            $results[] = $domainSearchResult;
        }
    }
    return $results;
}
function enom_GetDomainSuggestions($params)
{
    $numberOfSuggestions = $params["suggestionSettings"]["suggestMaxResultCount"];
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->NewRequest();
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("SearchTerm", $params["punyCodeSearchTerm"] ?: $params["searchTerm"]);
    $Enom->AddParam("OnlyTldList", implode(",", $params["tldsToInclude"]));
    $Enom->AddParam("ExclueTldList", "");
    $Enom->AddParam("MaxResults", $numberOfSuggestions);
    $Enom->AddParam("SpinType", "0");
    $Enom->AddParam("Adult", $params["suggestionSettings"]["suggestAdultDomains"] ? "True" : "False");
    $Enom->AddParam("AllGA", $params["suggestionSettings"]["suggestOnlyGeneralAvailability"] ? "True" : "False");
    $Enom->AddParam("command", "GetNameSuggestions");
    $Enom->AddParam("IncludePrice", true);
    $Enom->DoTransaction($params);
    if (0 < $Enom->Values["ErrCount"]) {
        throw new Exception($Enom->Values["Err1"]);
    }
    $premiumDomains = array();
    if ($params["premiumEnabled"]) {
        $domainsToCheck = array();
        for ($i = 1; $i <= $Enom->Values["DomainSuggestionCount"]; $i++) {
            $domainsToCheck[] = $Enom->Values["sld" . $i] . "." . $Enom->Values["tld" . $i];
        }
        $maxDomainsInRequest = 30;
        $EnomPremium = new WHMCS\Module\Registrar\Enom\ApiInterface();
        foreach (array_chunk($domainsToCheck, $maxDomainsInRequest) as $domainChunk) {
            $EnomPremium->NewRequest();
            $EnomPremium->AddParam("uid", $params["Username"]);
            $EnomPremium->AddParam("pw", $params["Password"]);
            $EnomPremium->AddParam("DomainList", implode(",", $domainChunk));
            $EnomPremium->AddParam("command", "check");
            $EnomPremium->AddParam("IncludePrice", true);
            $EnomPremium->DoTransaction($params);
            if (0 < $EnomPremium->Values["ErrCount"]) {
                throw new Exception($EnomPremium->Values["Err1"]);
            }
            if ($params["premiumEnabled"]) {
                for ($i = 1; $i <= $EnomPremium->Values["DomainCount"]; $i++) {
                    if ($EnomPremium->Values["IsPremiumName" . $i] == "true") {
                        $domainNameKey = strtolower($EnomPremium->Values["Domain" . $i]);
                        $premiumDomains[$domainNameKey]["pricing"] = array("register" => $EnomPremium->Values["PremiumPrice" . $i], "renew" => $EnomPremium->Values["RenewalPrice" . $i], "transfer" => $EnomPremium->Values["TransferPrice" . $i], "CurrencyCode" => "USD");
                    }
                }
            }
        }
    }
    $results = new WHMCS\Domains\DomainLookup\ResultsList();
    for ($i = 1; $i <= $numberOfSuggestions; $i++) {
        if (!isset($Enom->Values["sld" . $i])) {
            continue;
        }
        $sld = strtolower($Enom->Values["sld" . $i]);
        $tld = $Enom->Values["tld" . $i];
        $score = $Enom->Values["score" . $i];
        $idn = $Enom->Values["idn" . $i] == "true";
        $premium = array_key_exists((string) $sld . "." . $tld, $premiumDomains);
        if ($params["isIdnDomain"] && $idn == "false") {
            continue;
        }
        $domainSearchResult = new WHMCS\Domains\DomainLookup\SearchResult($sld, $tld);
        $domainSearchResult->setScore($score);
        $domainSearchResult->setStatus($domainSearchResult::STATUS_NOT_REGISTERED);
        if ($params["premiumEnabled"] && $premiumDomains && $premium) {
            $domainSearchResult->setPremiumDomain(true);
            $domainSearchResult->setPremiumCostPricing($premiumDomains[(string) $sld . "." . $tld]["pricing"]);
            unset($premiumDomains[(string) $sld . "." . $tld]);
        } else {
            if (!$params["premiumEnabled"] && $premium) {
                $domainSearchResult->setStatus($domainSearchResult::STATUS_RESERVED);
            }
        }
        $results[] = $domainSearchResult;
    }
    return $results;
}
function enom_GetNameservers($params)
{
    $params = injectDomainObjectIfNecessary($params);
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->NewRequest();
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("tld", $params["tld"]);
    $Enom->AddParam("sld", $params["sld"]);
    $Enom->AddParam("command", "getdns");
    $Enom->DoTransaction($params);
    $values = array();
    for ($i = 1; $i <= $Enom->Values["NSCount"]; $i++) {
        $values["ns" . $i] = isset($Enom->Values["DNS" . $i]) ? $Enom->Values["DNS" . $i] : "";
    }
    if ($Enom->Values["Err1"]) {
        $values["error"] = $Enom->Values["Err1"];
    }
    return $values;
}
function enom_SaveNameservers($params)
{
    $params = injectDomainObjectIfNecessary($params);
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("tld", $params["tld"]);
    $Enom->AddParam("sld", $params["sld"]);
    $Enom->AddParam("NS1", $params["ns1"]);
    $Enom->AddParam("NS2", $params["ns2"]);
    $Enom->AddParam("NS3", $params["ns3"]);
    $Enom->AddParam("NS4", $params["ns4"]);
    $Enom->AddParam("NS5", $params["ns5"]);
    $Enom->AddParam("command", "modifyns");
    $Enom->DoTransaction($params);
    $values["error"] = $Enom->Values["Err1"];
    return $values;
}
function enom_GetRegistrarLock($params)
{
    $params = injectDomainObjectIfNecessary($params);
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->NewRequest();
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("tld", $params["tld"]);
    $Enom->AddParam("sld", $params["sld"]);
    $Enom->AddParam("command", "getreglock");
    $Enom->DoTransaction($params);
    if ($Enom->Values["ErrCount"] == "0") {
        $lock = $Enom->Values["RegLock"];
        if ($Enom->Values["IsLockable"] == "True") {
            if ($lock == "1") {
                $lockstatus = "locked";
            } else {
                $lockstatus = "unlocked";
            }
        }
        return $lockstatus;
    }
}
function enom_SaveRegistrarLock($params)
{
    $params = injectDomainObjectIfNecessary($params);
    if ($params["lockenabled"] == "locked") {
        $lockstatus = "0";
    } else {
        $lockstatus = "1";
    }
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->NewRequest();
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("tld", $params["tld"]);
    $Enom->AddParam("sld", $params["sld"]);
    $Enom->AddParam("UnlockRegistrar", $lockstatus);
    $Enom->AddParam("command", "setreglock");
    $Enom->DoTransaction($params);
    if ($Enom->Values["ErrCount"] != "0") {
        $values["error"] = $Enom->Values["Err1"];
    }
    return $values;
}
function enom_GetEmailForwarding($params)
{
    $params = injectDomainObjectIfNecessary($params);
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("tld", $params["tld"]);
    $Enom->AddParam("sld", $params["sld"]);
    $Enom->AddParam("command", "getforwarding");
    $Enom->DoTransaction($params);
    $counter = 1;
    while ($counter <= 100) {
        if ($Enom->Values["Username" . $counter]) {
            $values[$counter]["prefix"] = $Enom->Values["Username" . $counter];
            $values[$counter]["forwardto"] = $Enom->Values["ForwardTo" . $counter];
        }
        $counter += 1;
    }
    return $values;
}
function _enom_EnableService($params, $service)
{
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("tld", $params["tld"]);
    $Enom->AddParam("sld", $params["sld"]);
    $Enom->AddParam("command", "serviceselect");
    $Enom->AddParam("service", $service);
    $Enom->DoTransaction($params);
    return $Enom->Values["ErrCount"] == 0;
}
function enom_SaveEmailForwarding($params)
{
    if (!_enom_enableservice($params, "emailset")) {
        return array("error" => "Could not enable mail forwarding service");
    }
    $params = injectDomainObjectIfNecessary($params);
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("tld", $params["tld"]);
    $Enom->AddParam("sld", $params["sld"]);
    $apiFieldIndex = 1;
    foreach ($params["prefix"] as $key => $value) {
        $Enom->AddParam("Address" . $apiFieldIndex, $params["prefix"][$key]);
        $Enom->AddParam("ForwardTo" . $apiFieldIndex, $params["forwardto"][$key]);
        $apiFieldIndex++;
    }
    $Enom->AddParam("command", "forwarding");
    $Enom->DoTransaction($params);
    $values["error"] = $Enom->Values["Err1"];
    return $values;
}
function enom_GetDNS($params)
{
    $params = injectDomainObjectIfNecessary($params);
    $hostRecords = array();
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("tld", $params["tld"]);
    $Enom->AddParam("sld", $params["sld"]);
    $Enom->AddParam("command", "gethosts");
    $Enom->AddParam("ResponseType", "XML");
    $xmlData = $Enom->DoTransaction($params, false);
    $arrayData = XMLtoArray($xmlData);
    if ($xmlData) {
        foreach ($arrayData["INTERFACE-RESPONSE"] as $k => $values) {
            if (preg_match("/^HOST[\\d]*\$/i", $k) && is_array($values)) {
                $hostRecords[] = array("hostname" => $values["NAME"], "type" => $values["TYPE"], "address" => $values["ADDRESS"], "priority" => $values["MXPREF"]);
            }
        }
    }
    return $hostRecords;
}
function enom_SaveDNS($params)
{
    $params = injectDomainObjectIfNecessary($params);
    foreach ($params["dnsrecords"] as $key => $values) {
        if ($values && $values["address"]) {
            $key++;
            $newvalues["HostName" . $key] = $values["hostname"];
            $newvalues["RecordType" . $key] = $values["type"];
            $newvalues["Address" . $key] = $values["address"];
            if ($values["type"] == "MX") {
                $newvalues["MXPref" . $key] = $values["priority"];
            }
        }
    }
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("tld", $params["tld"]);
    $Enom->AddParam("sld", $params["sld"]);
    foreach ($newvalues as $key => $value) {
        $Enom->AddParam($key, $value);
    }
    $Enom->AddParam("command", "sethosts");
    $Enom->DoTransaction($params);
    $values["error"] = $Enom->Values["Err1"];
    return $values;
}
function enom_RegisterDomain($params)
{
    $params = injectDomainObjectIfNecessary($params);
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->NewRequest();
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("tld", $params["tld"]);
    $Enom->AddParam("sld", $params["sld"]);
    $Enom->AddParam("numyears", $params["regperiod"]);
    $Enom->AddParam("IgnoreNSFail", "Yes");
    $Enom->AddParam("EmailNotify", "1");
    if ($params["premiumEnabled"] && $params["premiumCost"]) {
        $Enom->AddParam("CustomerSuppliedPrice", $params["premiumCost"]);
    }
    if ($params["DefaultNameservers"]) {
        $Enom->AddParam("UseDNS", "default");
    } else {
        $Enom->AddParam("NS1", $params["ns1"]);
        $Enom->AddParam("NS2", $params["ns2"]);
        $Enom->AddParam("NS3", $params["ns3"]);
        $Enom->AddParam("NS4", $params["ns4"]);
        $Enom->AddParam("NS5", $params["ns5"]);
    }
    enom_set_initial_contact_data($Enom, $params);
    try {
        enom_populate_additional_fields($Enom, $params);
    } catch (Exception $e) {
        return array("error" => $e->getMessage());
    }
    $Enom->AddParam("command", "purchase");
    $Enom->DoTransaction($params);
    $values["error"] = $Enom->Values["Err1"];
    if (!$values["error"] && $Enom->Values["RRPCode"] != "200") {
        $values["error"] = $Enom->Values["RRPText"];
    }
    if ($params["idprotection"] && !$values["error"]) {
        $Enom->NewRequest();
        $Enom->AddParam("uid", $params["Username"]);
        $Enom->AddParam("pw", $params["Password"]);
        $Enom->AddParam("ProductType", "IDProtect");
        $Enom->AddParam("TLD", $params["tld"]);
        $Enom->AddParam("SLD", $params["sld"]);
        $Enom->AddParam("Quantity", $params["regperiod"]);
        $Enom->AddParam("ClearItems", "yes");
        $Enom->AddParam("command", "AddToCart");
        $Enom->DoTransaction($params);
        $Enom->NewRequest();
        $Enom->AddParam("uid", $params["Username"]);
        $Enom->AddParam("pw", $params["Password"]);
        $Enom->AddParam("command", "InsertNewOrder");
        $Enom->DoTransaction($params);
    }
    return $values;
}
function enom_TransferDomain(array $params)
{
    $params = injectDomainObjectIfNecessary($params);
    if ($params["companyname"]) {
        $jobtitle = "Director";
    }
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->NewRequest();
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("DomainCount", "1");
    $Enom->AddParam("OrderType", "Autoverification");
    $Enom->AddParam("TLD1", $params["tld"]);
    $Enom->AddParam("SLD1", $params["sld"]);
    $Enom->AddParam("AuthInfo1", $params["transfersecret"]);
    $Enom->AddParam("UseContacts", "0");
    $Enom->AddParam("Lock", "1");
    $Enom->AddParam("EmailNotify", "1");
    if ($params["premiumEnabled"] && $params["premiumCost"]) {
        $Enom->AddParam("CustomerSuppliedPrice", $params["premiumCost"]);
    }
    enom_set_initial_contact_data($Enom, $params);
    try {
        enom_populate_additional_fields($Enom, $params);
    } catch (Exception $e) {
        return array("error" => $e->getMessage());
    }
    $Enom->AddParam("command", "TP_CreateOrder");
    $Enom->DoTransaction($params);
    $values["error"] = $Enom->Values["Err1"];
    return $values;
}
function enom_RenewDomain($params)
{
    $values = array();
    $params = injectDomainObjectIfNecessary($params);
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->NewRequest();
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("sld", $params["sld"]);
    $Enom->AddParam("tld", $params["tld"]);
    $Enom->AddParam("command", "GetDomainInfo");
    $Enom->DoTransaction($params);
    if (0 < $Enom->Values["ErrCount"]) {
        return array("error" => "An Error Occurred When Getting The Domain Status");
    }
    $RegistrationStatus = $Enom->Values["registrationstatus"];
    $Enom->NewRequest();
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    if (stripos($RegistrationStatus, "Registered") !== false) {
        $Enom->AddParam("tld", $params["tld"]);
        $Enom->AddParam("sld", $params["sld"]);
        $Enom->AddParam("NumYears", $params["regperiod"]);
        $Enom->AddParam("command", "extend");
    } else {
        if (stripos($RegistrationStatus, "Expired") !== false) {
            $Enom->AddParam("DomainName", $params["sld"] . "." . $params["tld"]);
            $Enom->AddParam("NumYears", $params["regperiod"]);
            $Enom->AddParam("command", "UpdateExpiredDomains");
        } else {
            $Enom->AddParam("tld", $params["tld"]);
            $Enom->AddParam("sld", $params["sld"]);
            $Enom->AddParam("command", "Extend_RGP");
        }
    }
    if ($params["premiumEnabled"] && $params["premiumCost"]) {
        $Enom->AddParam("CustomerSuppliedPrice", $params["premiumCost"]);
    }
    $Enom->DoTransaction($params);
    if (0 < $Enom->Values["ErrCount"]) {
        $values["error"] = $Enom->Values["Err1"];
    } else {
        if ($params["idprotection"]) {
            $Enom->NewRequest();
            $Enom->AddParam("uid", $params["Username"]);
            $Enom->AddParam("pw", $params["Password"]);
            $Enom->AddParam("ProductType", "IDProtectRenewal");
            $Enom->AddParam("TLD", $params["tld"]);
            $Enom->AddParam("SLD", $params["sld"]);
            $Enom->AddParam("Quantity", $params["regperiod"]);
            $Enom->AddParam("ClearItems", "yes");
            $Enom->AddParam("command", "AddToCart");
            $Enom->DoTransaction($params);
            $Enom->NewRequest();
            $Enom->AddParam("uid", $params["Username"]);
            $Enom->AddParam("pw", $params["Password"]);
            $Enom->AddParam("command", "InsertNewOrder");
            $Enom->DoTransaction($params);
        }
    }
    return $values;
}
function enom_GetContactDetails($params)
{
    $params = injectDomainObjectIfNecessary($params);
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->NewRequest();
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("tld", $params["tld"]);
    $Enom->AddParam("sld", $params["sld"]);
    $Enom->AddParam("command", "getcontacts");
    $Enom->DoTransaction($params);
    $contacttypes = array("Registrant", "Admin", "Tech");
    for ($i = 0; $i <= 2; $i++) {
        if (isset($Enom->Values["RegistrantUpdatable"]) && ($Enom->Values["RegistrantUpdatable"] == "False" || $Enom->Values["RegistrantUpdatable"] === false) && $contacttypes[$i] == "Registrant") {
            continue;
        }
        $values[$contacttypes[$i]]["First Name"] = $Enom->Values[$contacttypes[$i] . "FirstName"];
        $values[$contacttypes[$i]]["Last Name"] = $Enom->Values[$contacttypes[$i] . "LastName"];
        $values[$contacttypes[$i]]["Organisation Name"] = $Enom->Values[$contacttypes[$i] . "OrganizationName"];
        $values[$contacttypes[$i]]["Job Title"] = $Enom->Values[$contacttypes[$i] . "JobTitle"];
        $values[$contacttypes[$i]]["Email"] = $Enom->Values[$contacttypes[$i] . "EmailAddress"];
        $values[$contacttypes[$i]]["Address 1"] = $Enom->Values[$contacttypes[$i] . "Address1"];
        $values[$contacttypes[$i]]["Address 2"] = $Enom->Values[$contacttypes[$i] . "Address2"];
        $values[$contacttypes[$i]]["City"] = $Enom->Values[$contacttypes[$i] . "City"];
        $values[$contacttypes[$i]]["State"] = $Enom->Values[$contacttypes[$i] . "StateProvince"];
        $values[$contacttypes[$i]]["Postcode"] = $Enom->Values[$contacttypes[$i] . "PostalCode"];
        $values[$contacttypes[$i]]["Country"] = $Enom->Values[$contacttypes[$i] . "Country"];
        $values[$contacttypes[$i]]["Phone"] = $Enom->Values[$contacttypes[$i] . "Phone"];
        $values[$contacttypes[$i]]["Fax"] = $Enom->Values[$contacttypes[$i] . "Fax"];
    }
    return $values;
}
function enom_GetRegistrantContactEmailAddress(array $params)
{
    $params = injectDomainObjectIfNecessary($params);
    $eNom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $eNom->NewRequest();
    $eNom->AddParam("uid", $params["Username"]);
    $eNom->AddParam("pw", $params["Password"]);
    $eNom->AddParam("tld", $params["tld"]);
    $eNom->AddParam("sld", $params["sld"]);
    $eNom->AddParam("command", "GetContacts");
    $eNom->DoTransaction($params);
    $values = array();
    $values["registrantEmail"] = $eNom->Values["RegistrantEmailAddress"];
    if (0 < $eNom->Values["ErrCount"]) {
        for ($i = 1; $i <= $eNom->Values["ErrCount"]; $i++) {
            $values["error"] .= $eNom->Values["Err" . $i] . ". ";
        }
    }
    return $values;
}
function enom_SaveContactDetails($params)
{
    $params = injectDomainObjectIfNecessary($params);
    $countries = new WHMCS\Utility\Country();
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->NewRequest();
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("tld", $params["tld"]);
    $Enom->AddParam("sld", $params["sld"]);
    $params = enom_NormalizeContactDetails($params);
    $existingContactDetails = enom_getcontactdetails($params);
    $contacttypes = array("Registrant", "Admin", "Tech");
    $registrantFieldsChanged = false;
    for ($i = 0; $i <= 2; $i++) {
        $phonenumber = $params["contactdetails"][$contacttypes[$i]]["Phone"];
        $country = $params["contactdetails"][$contacttypes[$i]]["Country"];
        $phoneprefix = $countries->getCallingCode($country);
        if (substr($phonenumber, 0, 1) != "+" && $phoneprefix) {
            $params["contactdetails"][$contacttypes[$i]]["Phone"] = "+" . $phoneprefix . "." . $phonenumber;
        }
        $Enom->AddParam($contacttypes[$i] . "Fax", $params["contactdetails"][$contacttypes[$i]]["Fax"]);
        $Enom->AddParam($contacttypes[$i] . "Phone", $params["contactdetails"][$contacttypes[$i]]["Phone"]);
        $Enom->AddParam($contacttypes[$i] . "Country", $params["contactdetails"][$contacttypes[$i]]["Country"]);
        $Enom->AddParam($contacttypes[$i] . "PostalCode", $params["contactdetails"][$contacttypes[$i]]["Postcode"]);
        $Enom->AddParam($contacttypes[$i] . "StateProvince", $params["contactdetails"][$contacttypes[$i]]["State"]);
        if ($params["contactdetails"][$contacttypes[$i]]["Country"] == "US") {
            $Enom->AddParam($contacttypes[$i] . "StateProvinceChoice", "S");
        } else {
            $Enom->AddParam($contacttypes[$i] . "StateProvinceChoice", "P");
        }
        $Enom->AddParam($contacttypes[$i] . "City", $params["contactdetails"][$contacttypes[$i]]["City"]);
        $Enom->AddParam($contacttypes[$i] . "EmailAddress", $params["contactdetails"][$contacttypes[$i]]["Email"]);
        $Enom->AddParam($contacttypes[$i] . "Address2", $params["contactdetails"][$contacttypes[$i]]["Address 2"]);
        $Enom->AddParam($contacttypes[$i] . "Address1", $params["contactdetails"][$contacttypes[$i]]["Address 1"]);
        $Enom->AddParam($contacttypes[$i] . "JobTitle", $params["contactdetails"][$contacttypes[$i]]["Job Title"]);
        $Enom->AddParam($contacttypes[$i] . "LastName", $params["contactdetails"][$contacttypes[$i]]["Last Name"]);
        $Enom->AddParam($contacttypes[$i] . "FirstName", $params["contactdetails"][$contacttypes[$i]]["First Name"]);
        $Enom->AddParam($contacttypes[$i] . "OrganizationName", $params["contactdetails"][$contacttypes[$i]]["Organisation Name"]);
        $registrantLastName = $params["contactdetails"]["Registrant"]["Last Name"];
        $registrantFirstName = $params["contactdetails"]["Registrant"]["First Name"];
        $registrantCompanyName = $params["contactdetails"]["Registrant"]["Organisation Name"];
        if ($existingContactDetails["Registrant"]["Last Name"] != $registrantLastName || $existingContactDetails["Registrant"]["First Name"] != $registrantFirstName || $existingContactDetails["Registrant"]["Organisation Name"] != $registrantCompanyName) {
            $registrantFieldsChanged = true;
        }
    }
    try {
        enom_populate_additional_fields($Enom, $params);
    } catch (Exception $e) {
        return array("error" => $e->getMessage());
    }
    $values = array();
    if (array_key_exists("irtpOptOut", $params) && $registrantFieldsChanged) {
        $Enom->AddParam("IRTPOptOut", (bool) (int) $params["irtpOptOut"]);
        $optOutReason = $params["irtpOptOutReason"];
        if ($optOutReason) {
            $Enom->AddParam("IRTPOptOutReason", $optOutReason);
        }
        switch (strtolower($params["language"])) {
            case "italian":
                $language = "IT";
                break;
            case "french":
                $language = "FR";
                break;
            case "portuguese-br":
            case "portuguese-pt":
                $language = "PT";
                break;
            case "spanish":
                $language = "ES";
                break;
            case "german":
                $language = "DE";
                break;
            default:
                $language = "EN";
        }
        $Enom->AddParam("IRTPEmailLanguageCode", $language);
        $values["pending"] = true;
    }
    $Enom->AddParam("command", "contacts");
    $Enom->DoTransaction($params);
    $errorMsgs = array();
    $errorCount = $Enom->Values["ErrCount"];
    for ($i = 1; $i <= $errorCount; $i++) {
        $errorMsgs[] = trim($Enom->Values["Err" . $i]);
    }
    $values["error"] = implode(", ", $errorMsgs);
    return $values;
}
function enom_GetEPPCode($params)
{
    $params = injectDomainObjectIfNecessary($params);
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->NewRequest();
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("tld", $params["tld"]);
    $Enom->AddParam("sld", $params["sld"]);
    $Enom->AddParam("command", "SynchAuthInfo");
    $Enom->AddParam("EmailEPP", "True");
    $Enom->AddParam("RunSynchAutoInfo", "True");
    $Enom->DoTransaction($params);
    $values["error"] = $Enom->Values["Err1"];
    return $values;
}
function enom_RegisterNameserver($params)
{
    $params = injectDomainObjectIfNecessary($params);
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->NewRequest();
    $Enom->AddParam("command", "RegisterNameServer");
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("Add", "true");
    $Enom->AddParam("NSName", $params["nameserver"]);
    $Enom->AddParam("IP", $params["ipaddress"]);
    $Enom->DoTransaction($params);
    if ($Enom->Values["Err1"]) {
        $error = $Enom->Values["Err1"];
    }
    if ($Enom->Values["ResponseString1"]) {
        $error = $Enom->Values["ResponseString1"];
    }
    $values["error"] = $error;
    return $values;
}
function enom_ModifyNameserver($params)
{
    $params = injectDomainObjectIfNecessary($params);
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->NewRequest();
    $Enom->AddParam("command", "UpdateNameServer");
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("Add", "true");
    $Enom->AddParam("NS", $params["nameserver"]);
    $Enom->AddParam("OldIP", $params["currentipaddress"]);
    $Enom->AddParam("NewIP", $params["newipaddress"]);
    $Enom->DoTransaction($params);
    if ($Enom->Values["Err1"]) {
        $error = $Enom->Values["Err1"];
    }
    if ($Enom->Values["ResponseString1"]) {
        $error = $Enom->Values["ResponseString1"];
    }
    $values["error"] = $error;
    return $values;
}
function enom_DeleteNameserver($params)
{
    $params = injectDomainObjectIfNecessary($params);
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->NewRequest();
    $Enom->AddParam("command", "DeleteNameServer");
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("Add", "true");
    $Enom->AddParam("NS", $params["nameserver"]);
    $Enom->DoTransaction($params);
    if ($Enom->Values["Err1"]) {
        $error = $Enom->Values["Err1"];
    }
    if ($Enom->Values["ResponseString1"]) {
        $error = $Enom->Values["ResponseString1"];
    }
    $values["error"] = $error;
    return $values;
}
function enom_AdminCustomButtonArray($params)
{
    $buttonarray = array();
    if ($params["regtype"] == "Transfer") {
        $buttonarray["Resend Transfer Approval Email"] = "resendtransferapproval";
        $buttonarray["Cancel Domain Transfer"] = "canceldomaintransfer";
    }
    return $buttonarray;
}
function enom_resendtransferapproval($params)
{
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->NewRequest();
    $Enom->AddParam("command", "TP_CancelOrder");
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("sld", $params["sld"]);
    $Enom->AddParam("tld", $params["tld"]);
    $Enom->DoTransaction($params);
    if ($Enom->Values["Err1"] || $Enom->Values["ResponseString1"]) {
        $values["error"] = $Enom->Values["Err1"];
    } else {
        $values["message"] = "Successfully resent the transfer approval email";
    }
    return $values;
}
function enom_getorderid($params)
{
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->NewRequest();
    $Enom->AddParam("command", "StatusDomain");
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("sld", $params["sld"]);
    $Enom->AddParam("tld", $params["tld"]);
    $Enom->AddParam("OrderType", "Transfer");
    $Enom->DoTransaction($params, true);
    if ($Enom->Values["Err1"] || !$Enom->Values["OrderID"]) {
        $errmsg = "Unable to Find Domain Order";
        if ($Enom->Values["Err1"]) {
            $errmsg .= " - " . $Enom->Values["Err1"];
        }
        return $errmsg;
    }
    return $Enom->Values["OrderID"];
}
function enom_canceldomaintransfer($params)
{
    $orderid = enom_getorderid($params);
    if (!is_numeric($orderid)) {
        $values["error"] = $orderid;
        return $values;
    }
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->NewRequest();
    $Enom->AddParam("command", "TP_CancelOrder");
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("TransferOrderID", $orderid);
    $Enom->DoTransaction($params);
    if ($Enom->Values["Err1"] || $Enom->Values["ResponseString1"]) {
        $values["error"] = $Enom->Values["Err1"];
    } else {
        $values["message"] = "Successfully cancelled the domain transfer";
    }
    return $values;
}
function enom_Sync($params)
{
    $params = injectDomainObjectIfNecessary($params);
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->NewRequest();
    $Enom->AddParam("command", "GetDomainExp");
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("sld", $params["sld"]);
    $Enom->AddParam("tld", $params["tld"]);
    $Enom->DoTransaction($params);
    $values = array();
    if ($Enom->Values["Err1"] || $Enom->Values["ResponseString1"]) {
        $values["error"] = $Enom->Values["Err1"];
        if ($values["error"] == "Domain name not found") {
            $values["transferredAway"] = true;
            unset($values["error"]);
        }
    } else {
        $expirydate = $Enom->Values["ExpirationDate"];
        if ($expirydate) {
            $expirydate = explode(" ", $expirydate);
            $expirydate = explode("/", $expirydate[0]);
            list($month, $day, $year) = $expirydate;
            $expirydate = $year . "-" . str_pad($month, 2, "0", STR_PAD_LEFT) . "-" . str_pad($day, 2, "0", STR_PAD_LEFT);
            if (trim($year)) {
                $values["status"] = "Active";
            }
            $values["expirydate"] = $expirydate;
        }
    }
    return $values;
}
function enom_TransferSync($params)
{
    $params = injectDomainObjectIfNecessary($params);
    $cancelledstatusids = array("2", "4", "6", "7", "8", "15", "16", "17", "18", "19", "20", "21", "22", "23", "24", "25", "26", "27", "30", "31", "32", "33", "34", "36", "37", "45");
    $pendingstatusids = array("0", "1", "3", "9", "10", "11", "12", "13", "14", "28", "29", "35");
    $values = array();
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->NewRequest();
    $Enom->AddParam("command", "TP_GetDetailsByDomain");
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("sld", $params["sld"]);
    $Enom->AddParam("tld", $params["tld"]);
    $Enom->DoTransaction($params);
    if ($Enom->Values["Err1"] || $Enom->Values["ResponseString1"]) {
        $values["error"] = $Enom->Values["Err1"];
    } else {
        $count = $Enom->Values["ordercount"];
        $statusid = $Enom->Values["statusid" . $count];
        $statusdesc = $Enom->Values["statusdesc" . $count];
        if ($statusid == "5") {
            if ($params["idprotection"]) {
                $Enom->NewRequest();
                $Enom->AddParam("uid", $params["Username"]);
                $Enom->AddParam("pw", $params["Password"]);
                $Enom->AddParam("ProductType", "IDProtect");
                $Enom->AddParam("TLD", $params["tld"]);
                $Enom->AddParam("SLD", $params["sld"]);
                $Enom->AddParam("Quantity", $params["regperiod"]);
                $Enom->AddParam("ClearItems", "yes");
                $Enom->AddParam("command", "AddToCart");
                $Enom->DoTransaction($params);
                $Enom->NewRequest();
                $Enom->AddParam("uid", $params["Username"]);
                $Enom->AddParam("pw", $params["Password"]);
                $Enom->AddParam("command", "InsertNewOrder");
                $Enom->DoTransaction($params);
            }
            $values["completed"] = true;
        } else {
            if (in_array($statusid, $cancelledstatusids)) {
                $values["failed"] = true;
                $values["reason"] = $statusdesc;
            } else {
                if (in_array($statusid, $pendingstatusids)) {
                    $values["pendingtransfer"] = true;
                    $values["reason"] = $statusdesc;
                }
            }
        }
    }
    return $values;
}
function enom_GetPremiumPrice(array $params)
{
    $premiumPricing = array();
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->NewRequest();
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("SLD", $params["sld"]);
    $Enom->AddParam("TLD", ltrim($params["tld"], "."));
    $Enom->AddParam("command", "check");
    $Enom->AddParam("IncludePrice", true);
    $Enom->DoTransaction($params);
    if (0 < $Enom->Values["ErrCount"]) {
        throw new Exception($Enom->Values["Err1"]);
    }
    if ($Enom->Values["IsPremiumName"] == "true") {
        $premiumPricing = array("register" => $Enom->Values["PremiumPrice"], "renew" => $Enom->Values["RenewalPrice"], "transfer" => $Enom->Values["TransferPrice"], "CurrencyCode" => "USD");
    }
    $premiumPricing["CurrencyCode"] = "USD";
    return $premiumPricing;
}
function enom_NormalizeContactDetails($params)
{
    $contacttypes = array("Registrant", "Admin", "Tech");
    if (array_key_exists("country", $params) && $params["country"] == "NL") {
        $modifyKeys = array("fullstate", "state", "statecode", "adminfullstate", "adminstate");
        foreach ($modifyKeys as $key) {
            $params[$key] = str_replace("-", "", $params[$key]);
        }
    }
    if (array_key_exists("country", $params) && $params["country"] == "CA") {
        $params["postcode"] = preg_replace("/\\s/", "", $params["postcode"]);
    }
    if (array_key_exists("admincountry", $params) && $params["admincountry"] == "CA") {
        $params["adminpostcode"] = preg_replace("/\\s/", "", $params["adminpostcode"]);
    }
    for ($i = 0; $i <= 2; $i++) {
        if (array_key_exists($contacttypes[$i], $params["contactdetails"]) && array_key_exists("Country", $params["contactdetails"][$contacttypes[$i]])) {
            $country = $params["contactdetails"][$contacttypes[$i]]["Country"];
            if ($country == "CA") {
                $params["contactdetails"][$contacttypes[$i]]["Postcode"] = preg_replace("/\\s/", "", $params["contactdetails"][$contacttypes[$i]]["Postcode"]);
            }
        }
    }
    return $params;
}
function enom_DomainSuggestionOptions()
{
    return array("suggestMaxResultCount" => array("FriendlyName" => AdminLang::trans("general.maxsuggestions"), "Type" => "dropdown", "Options" => array(10 => "10", 25 => "25", 50 => "50", 75 => "75", 100 => "100 (" . AdminLang::trans("global.recommended") . ")"), "Default" => "100", "Description" => ""), "suggestOnlyGeneralAvailability" => array("FriendlyName" => AdminLang::trans("general.onlygtlds"), "Type" => "yesno", "Description" => AdminLang::trans("global.ticktoenable") . " (" . AdminLang::trans("global.recommended") . ")", "Default" => true), "suggestAdultDomains" => array("FriendlyName" => AdminLang::trans("general.suggestadultdomains"), "Type" => "yesno", "Description" => AdminLang::trans("global.ticktoenable")));
}
function enom_IDProtectToggle(array $params)
{
    $params = injectDomainObjectIfNecessary($params);
    $Enom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $Enom->NewRequest();
    $Enom->AddParam("uid", $params["Username"]);
    $Enom->AddParam("pw", $params["Password"]);
    $Enom->AddParam("tld", $params["tld"]);
    $Enom->AddParam("sld", $params["sld"]);
    $Enom->AddParam("command", "GetWPPSInfo");
    $Enom->DoTransaction($params);
    $idProtectionAllowed = $Enom->Values["WPPSAllowed"];
    $idProtectionSubscription = $Enom->Values["WPPSExists"];
    $idProtectionEnabled = $Enom->Values["WPPSEnabled"];
    $enableIdProtect = $params["protectenable"];
    if (!$idProtectionAllowed) {
        return array("error" => "ID Protection is not available for this domain");
    }
    if ($idProtectionSubscription && $idProtectionEnabled && $enableIdProtect) {
        return array("error" => "ID Protection is already enabled on this domain");
    }
    if ($idProtectionSubscription) {
        $Enom->NewRequest();
        $Enom->AddParam("uid", $params["Username"]);
        $Enom->AddParam("pw", $params["Password"]);
        $Enom->AddParam("tld", $params["tld"]);
        $Enom->AddParam("sld", $params["sld"]);
        $Enom->AddParam("Service", "WPPS");
        $Enom->AddParam("command", $enableIdProtect ? "ENABLESERVICES" : "DISABLESERVICES");
        $Enom->DoTransaction($params);
        $errorMsgs = array();
        $errorCount = $Enom->Values["ErrCount"];
        for ($i = 1; $i <= $errorCount; $i++) {
            $errorMsgs[] = trim($Enom->Values["Err" . $i]);
        }
        if (0 < count($errorMsgs)) {
            return array("error" => implode(", ", $errorMsgs));
        }
    }
    if (!$idProtectionSubscription) {
        if (!$enableIdProtect) {
            return array("error" => "ID Protection is not enabled on this domain");
        }
        $Enom->NewRequest();
        $Enom->AddParam("uid", $params["Username"]);
        $Enom->AddParam("pw", $params["Password"]);
        $Enom->AddParam("tld", $params["tld"]);
        $Enom->AddParam("sld", $params["sld"]);
        $Enom->AddParam("Service", "WPPS");
        $Enom->AddParam("command", "PURCHASESERVICES");
        $Enom->AddParam("NumYears", $params["regperiod"]);
        $Enom->DoTransaction($params);
        $errorMsgs = array();
        $errorCount = $Enom->Values["ErrCount"];
        for ($i = 1; $i <= $errorCount; $i++) {
            $errorMsgs[] = trim($Enom->Values["Err" . $i]);
        }
        if (0 < count($errorMsgs)) {
            return array("error" => implode(", ", $errorMsgs));
        }
    }
    return array("success" => true);
}
function enom_GetDomainInformation(array $params)
{
    $params = injectDomainObjectIfNecessary($params);
    $eNom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $eNom->NewRequest();
    $eNom->AddParam("uid", $params["Username"]);
    $eNom->AddParam("pw", $params["Password"]);
    $eNom->AddParam("tld", $params["tld"]);
    $eNom->AddParam("sld", $params["sld"]);
    $eNom->AddParam("command", "GetDomainInfo");
    $eNom->AddParam("ResponseType", "XML");
    $eNom->DoTransaction($params, false);
    $arrayData = $eNom->parseXMLResponseToArray();
    $arrayData = $arrayData["interface-response"];
    $irtpDisabled = (bool) $params["DisableIRTP"];
    if ($errorCount = $arrayData["ErrCount"]["value"]) {
        $errorMessages = array();
        for ($i = 1; $i <= $errorCount; $i++) {
            $errorMessages[] = trim($arrayData["errors"]["Err" . $i]["value"]);
        }
        if (0 < count($errorMessages)) {
            throw new WHMCS\Exception\Module\NotServicable(implode(", ", $errorMessages));
        }
    }
    $nameservers = array();
    $irtpOptOutStatus = $irtpTransferLock = $irtpTransferLockExpiryDate = $contactChangeExpiryDate = NULL;
    $contactChangePending = $contactChangePendingSuspension = $icannTld = false;
    $emailAddress = "";
    $arrayData = $arrayData["GetDomainInfo"];
    foreach ($arrayData["services"]["entry"] as $service) {
        if ($service["attr"]["name"] === "dnsserver" && $service["configuration"]["dns"]) {
            $nsCounter = 1;
            foreach ($service["configuration"]["dns"] as $nameserver) {
                $ns = $nameserver;
                if (is_array($nameserver)) {
                    $ns = $nameserver["value"];
                }
                $nameservers["ns" . $nsCounter] = $ns;
                $nsCounter++;
            }
        }
        if (!$irtpDisabled) {
            if ($service["attr"]["name"] === "irtpsettings") {
                $irtpOptOutStatus = $service["irtpsetting"]["optout"]["value"] === "True";
                $irtpTransferLock = $service["irtpsetting"]["transferlock"]["value"] === "True";
                $icannTld = $service["irtpsetting"]["icanncompliant"]["value"] === "True";
                if ($icannTld && $irtpTransferLock) {
                    $irtpTransferLockExpiryDate = WHMCS\Carbon::createFromTimestampUTC($service["irtpsetting"]["transferlockexpdate"]["attr"]["epoch"]);
                }
            }
            if ($service["attr"]["name"] === "raasettings" && array_key_exists("raasetting", $service)) {
                $contactChangePending = (bool) count($service["raasetting"]);
                $contactChangeExpiryDate = WHMCS\Carbon::createFromFormat("Y-m-d\\TH:i:s.u", $service["raasetting"]["statusexpdate"]["value"]);
                $contactChangePendingSuspension = (bool) (int) $service["raasetting"]["pendingsuspension"]["value"];
                $emailAddress = $service["raasetting"]["emailaddress"]["value"];
            }
        }
    }
    return (new WHMCS\Domain\Registrar\Domain())->setDomain($arrayData["domainname"]["value"])->setNameservers($nameservers)->setRegistrationStatus(enom_normalise_status($arrayData["status"]["registrationstatus"]["value"]))->setIrtpOptOutStatus($irtpOptOutStatus)->setIrtpTransferLock($irtpTransferLock)->setTransferLockExpiryDate($irtpTransferLockExpiryDate)->setDomainContactChangePending($contactChangePending)->setPendingSuspension($contactChangePendingSuspension)->setDomainContactChangeExpiryDate($contactChangeExpiryDate)->setRegistrantEmailAddress($emailAddress)->setIsIrtpEnabled($icannTld)->setIrtpVerificationTriggerFields(array("Registrant" => array("First Name", "Last Name", "Organisation Name", "Email")));
}
function enom_ResendIRTPVerificationEmail(array $params)
{
    $params = injectDomainObjectIfNecessary($params);
    $eNom = new WHMCS\Module\Registrar\Enom\ApiInterface();
    $eNom->NewRequest();
    $eNom->AddParam("uid", $params["Username"]);
    $eNom->AddParam("pw", $params["Password"]);
    $eNom->AddParam("DomainName", $params["domainname"]);
    $eNom->AddParam("command", "RAA_ResendNotification");
    $eNom->DoTransaction($params);
    $errorMessages = array();
    $errorCount = $eNom->Values["ErrCount"];
    for ($i = 1; $i <= $errorCount; $i++) {
        $errorMessages[] = trim($eNom->Values["Err" . $i]);
    }
    if (0 < count($errorMessages)) {
        return array("error" => implode(", ", $errorMessages));
    }
    return array("success" => true);
}
function enom_normalise_status($status)
{
    switch ($status) {
        case "Expired":
            return WHMCS\Domain\Registrar\Domain::STATUS_EXPIRED;
    }
    return WHMCS\Domain\Registrar\Domain::STATUS_ACTIVE;
}
function enom_set_initial_contact_data(WHMCS\Module\Registrar\Enom\ApiInterface $Enom, array $params)
{
    $jobTitle = "";
    if ($params["companyname"]) {
        $jobTitle = "Director";
    }
    $params = enom_normalizecontactdetails($params);
    $Enom->AddParam("RegistrantFirstName", $params["firstname"]);
    $Enom->AddParam("RegistrantLastName", $params["lastname"]);
    $Enom->AddParam("RegistrantOrganizationName", $params["companyname"]);
    $Enom->AddParam("RegistrantJobTitle", $jobTitle);
    $Enom->AddParam("RegistrantAddress1", $params["address1"]);
    $Enom->AddParam("RegistrantAddress2", $params["address2"]);
    $Enom->AddParam("RegistrantCity", $params["city"]);
    $Enom->AddParam("RegistrantStateProvince", $params["state"]);
    $Enom->AddParam("RegistrantPostalCode", $params["postcode"]);
    $Enom->AddParam("RegistrantCountry", $params["country"]);
    $Enom->AddParam("RegistrantEmailAddress", $params["email"]);
    $Enom->AddParam("RegistrantPhone", $params["fullphonenumber"]);
    if ($params["country"] == "US") {
        $Enom->AddParam("RegistrantStateProvinceChoice", "S");
    } else {
        $Enom->AddParam("RegistrantStateProvinceChoice", "P");
    }
    $contactTypes = array("Admin", "Tech", "AuxBilling");
    foreach ($contactTypes as $contactType) {
        $Enom->AddParam($contactType . "FirstName", $params["adminfirstname"]);
        $Enom->AddParam($contactType . "LastName", $params["adminlastname"]);
        $Enom->AddParam($contactType . "OrganizationName", $params["admincompanyname"]);
        $Enom->AddParam($contactType . "JobTitle", $jobTitle);
        $Enom->AddParam($contactType . "Address1", $params["adminaddress1"]);
        $Enom->AddParam($contactType . "Address2", $params["adminaddress2"]);
        $Enom->AddParam($contactType . "City", $params["admincity"]);
        $Enom->AddParam($contactType . "StateProvince", $params["adminstate"]);
        $Enom->AddParam($contactType . "PostalCode", $params["adminpostcode"]);
        $Enom->AddParam($contactType . "Country", $params["admincountry"]);
        $Enom->AddParam($contactType . "EmailAddress", $params["adminemail"]);
        $Enom->AddParam($contactType . "Phone", $params["adminfullphonenumber"]);
    }
}
function enom_populate_additional_fields(WHMCS\Module\Registrar\Enom\ApiInterface $Enom, array $params)
{
    if ($params["domainObj"]->getLastTLDSegment() == "us") {
        $nexus = $params["additionalfields"]["Nexus Category"];
        $countryCode = $params["additionalfields"]["Nexus Country"];
        $purpose = $params["additionalfields"]["Application Purpose"];
        switch ($purpose) {
            case "Business use for profit":
                $purpose = "P1";
                break;
            case "Non-profit business":
            case "Club":
            case "Association":
            case "Religious Organization":
                $purpose = "P2";
                break;
            case "Personal Use":
            case "Educational purposes":
                $purpose = "P4";
                break;
            case "Government purposes":
                $purpose = "P5";
                break;
            default:
                $purpose = "P3";
                break;
        }
        switch ($nexus) {
            case "C11":
            case "C12":
            case "C21":
                $Enom->AddParam("us_nexus", $nexus);
                break;
            case "C31":
            case "C32":
                $Enom->AddParam("us_nexus", $nexus);
                $Enom->AddParam("global_cc_us", $countryCode);
                break;
        }
        $Enom->AddParam("us_purpose", $purpose);
    } else {
        if ($params["domainObj"]->getLastTLDSegment() == "uk") {
            switch ($params["additionalfields"]["Legal Type"]) {
                case "UK Limited Company":
                    $ukLegalType = "LTD";
                    break;
                case "UK Public Limited Company":
                    $ukLegalType = "PLC";
                    break;
                case "UK Partnership":
                    $ukLegalType = "PTNR";
                    break;
                case "UK Limited Liability Partnership":
                    $ukLegalType = "LLP";
                    break;
                case "Sole Trader":
                    $ukLegalType = "STRA";
                    break;
                case "UK Registered Charity":
                    $ukLegalType = "RCHAR";
                    break;
                case "UK Industrial/Provident Registered Company":
                    $ukLegalType = "IP";
                    break;
                case "UK School":
                    $ukLegalType = "SCH";
                    break;
                case "UK Government Body":
                    $ukLegalType = "GOV";
                    break;
                case "UK Corporation by Royal Charter":
                    $ukLegalType = "CRC";
                    break;
                case "UK Statutory Body":
                    $ukLegalType = "STAT";
                    break;
                case "Non-UK Individual":
                    $ukLegalType = "FIND";
                    break;
                case "Foreign Organization":
                    $ukLegalType = "FCORP";
                    break;
                case "Other foreign organizations":
                    $ukLegalType = "FOTHER";
                    break;
                default:
                    $ukLegalType = "IND";
            }
            $ukRegOptOut = "n";
            if ($params["additionalfields"]["WHOIS Opt-out"] && $ukLegalType == "IND") {
                $ukRegOptOut = "y";
            }
            $Enom->AddParam("uk_legal_type", $ukLegalType);
            $Enom->AddParam("uk_reg_co_no", strtoupper($params["additionalfields"]["Company ID Number"]));
            $Enom->AddParam("registered_for", $params["additionalfields"]["Registrant Name"]);
            $Enom->AddParam("uk_reg_opt_out", $ukRegOptOut);
        } else {
            if ($params["domainObj"]->getLastTLDSegment() == "ca") {
                switch ($params["additionalfields"]["Legal Type"]) {
                    case "Corporation":
                        $legalType = "CCO";
                        break;
                    case "Canadian Citizen":
                        $legalType = "CCT";
                        break;
                    case "Permanent Resident of Canada":
                        $legalType = "RES";
                        break;
                    case "Government":
                        $legalType = "GOV";
                        break;
                    case "Canadian Educational Institution":
                        $legalType = "EDU";
                        break;
                    case "Canadian Unincorporated Association":
                        $legalType = "ASS";
                        break;
                    case "Canadian Hospital":
                        $legalType = "HOP";
                        break;
                    case "Partnership Registered in Canada":
                        $legalType = "PRT";
                        break;
                    case "Trade-mark registered in Canada":
                        $legalType = "TDM";
                        break;
                    default:
                        $legalType = "CCO";
                }
                $whoisOptOut = "FULL";
                if ($params["additionalfields"]["WHOIS Opt-out"] && in_array($legalType, array("CCT", "RES"))) {
                    $whoisOptOut = "PRIVATE";
                }
                $ciraAgreement = "N";
                if ($params["additionalfields"]["CIRA Agreement"]) {
                    $ciraAgreement = "Y";
                }
                $Enom->AddParam("cira_legal_type", $legalType);
                $Enom->AddParam("cira_whois_display", $whoisOptOut);
                $Enom->AddParam("cira_language", "en");
                $Enom->AddParam("cira_agreement_version", "2.0");
                $Enom->AddParam("cira_agreement_value", $ciraAgreement);
                if ($ciraAgreement == "N") {
                    throw new WHMCS\Exception\Module\NotServicable("The CIRA Agreement must be agreed to by the customer" . " before the domain can be registered");
                }
            } else {
                if ($params["domainObj"]->getLastTLDSegment() == "eu") {
                    $Enom->AddParam("eu_whoispolicy", "I AGREE");
                    $Enom->AddParam("eu_agreedelete", "YES");
                    $Enom->AddParam("eu_adr_lang", "EN");
                } else {
                    if ($params["domainObj"]->getLastTLDSegment() == "it") {
                        if ($params["additionalfields"]["Publish Personal Data"] == "on") {
                            $Enom->AddParam("it_consentforpublishing", "1");
                        } else {
                            $Enom->AddParam("it_consentforpublishing", "0");
                        }
                        if ($params["additionalfields"]["Accept Section 3 of .IT registrar contract"] == "on") {
                            $Enom->AddParam("it_sect3_liability", "1");
                        } else {
                            $Enom->AddParam("it_sect3_liability", "0");
                        }
                        if ($params["additionalfields"]["Accept Section 5 of .IT registrar contract"] == "on") {
                            $Enom->AddParam("it_explicit_acceptance", "1");
                        } else {
                            $Enom->AddParam("it_explicit_acceptance", "0");
                        }
                        if ($params["additionalfields"]["Accept Section 6 of .IT registrar contract"] == "on") {
                            $Enom->AddParam("it_datafordiffusion", "1");
                        } else {
                            $Enom->AddParam("it_datafordiffusion", "0");
                        }
                        if ($params["additionalfields"]["Accept Section 7 of .IT registrar contract"] == "on") {
                            $Enom->AddParam("it_personal_data_for_reg", "1");
                        } else {
                            $Enom->AddParam("it_personal_data_for_reg", "0");
                        }
                        $fieldName = "Consent for Dissemination and Accessibility via the Internet";
                        $Enom->AddParam("it_datafordiffusion", $params["additionalfields"][$fieldName] ? "1" : "0");
                        $Enom->AddParam("it_agreedelete", "YES");
                        $Enom->AddParam("it_pin", $params["additionalfields"]["Tax ID"]);
                        $entityType = $params["additionalfields"]["Legal Type"];
                        switch ($entityType) {
                            case "Italian and foreign natural persons":
                                $entityNumber = 1;
                                break;
                            case "Companies/one man companies":
                                $entityNumber = 2;
                                break;
                            case "Freelance workers/professionals":
                                $entityNumber = 3;
                                break;
                            case "non-profit organizations":
                                $entityNumber = 4;
                                break;
                            case "public organizations":
                                $entityNumber = 5;
                                break;
                            case "other subjects":
                                $entityNumber = 6;
                                break;
                            case "non natural foreigners":
                                $entityNumber = 7;
                                break;
                            default:
                                $entityNumber = $params["companyname"] ? "2" : "1";
                        }
                        $Enom->AddParam("it_entity_type", $entityNumber);
                    } else {
                        if ($params["domainObj"]->getLastTLDSegment() == "de") {
                            $Enom->AddParam("confirmaddress", "DE");
                            $Enom->AddParam("de_agreedelete", "YES");
                        } else {
                            if ($params["domainObj"]->getLastTLDSegment() == "nl") {
                                $Enom->AddParam("nl_agreedelete", "YES");
                            } else {
                                if ($params["domainObj"]->getLastTLDSegment() == "fm") {
                                    $Enom->AddParam("fm_agreedelete", "YES");
                                } else {
                                    if ($params["domainObj"]->getLastTLDSegment() == "be") {
                                        $Enom->AddParam("be_agreedelete", "YES");
                                    } else {
                                        if ($params["domainObj"]->getLastTLDSegment() == "nz") {
                                            $Enom->AddParam("co.nz_agreedelete", "YES");
                                        } else {
                                            if ($params["domainObj"]->getLastTLDSegment() == "tel") {
                                                $telregoptout = "NO";
                                                if ($params["additionalfields"]["Registrant Type"] == "Legal Person") {
                                                    $regtype = "legal_person";
                                                } else {
                                                    $regtype = "natural_person";
                                                    if ($params["additionalfields"]["WHOIS Opt-out"]) {
                                                        $telregoptout = "YES";
                                                    }
                                                }
                                                $telpw = "";
                                                $length = 10;
                                                $seeds = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVYWXYZ";
                                                $seeds_count = strlen($seeds) - 1;
                                                for ($i = 0; $i < $length; $i++) {
                                                    $telpw .= $seeds[rand(0, $seeds_count)];
                                                }
                                                if (is_numeric(substr($telpw, 0, 1))) {
                                                    $telpw = "a" . $telpw;
                                                }
                                                $Enom->AddParam("tel_whoistype", $regtype);
                                                $Enom->AddParam("tel_publishwhois", $telregoptout);
                                                $Enom->AddParam("tel_username", strtolower($params["firstname"] . $params["lastname"] . $params["domainid"]));
                                                $Enom->AddParam("tel_password", $telpw);
                                                $Enom->AddParam("tel_emailaddress", $params["email"]);
                                            } else {
                                                if ($params["domainObj"]->getLastTLDSegment() == "pro") {
                                                    $Enom->AddParam("pro_profession", $params["additionalfields"]["Profession"]);
                                                } else {
                                                    if ($params["domainObj"]->getLastTLDSegment() == "es") {
                                                        $params["additionalfields"]["ID Form Type"] = explode("|", $params["additionalfields"]["ID Form Type"]);
                                                        $idType = $params["additionalfields"]["ID Form Type"][0];
                                                        switch ($idType) {
                                                            case "DNI":
                                                            case "NIF":
                                                            case "Tax Identification Number":
                                                            case "Tax Identification Code":
                                                                $idType = 3;
                                                                break;
                                                            case "NIE":
                                                            case "Foreigner Identification Number":
                                                                $idType = 1;
                                                                break;
                                                            default:
                                                                $idType = 0;
                                                        }
                                                        if (!empty($params["additionalfields"]["Legal Form"])) {
                                                            $params["additionalfields"]["Legal Form"] = explode("|", $params["additionalfields"]["Legal Form"]);
                                                            $legalForm = $params["additionalfields"]["Legal Form"][0];
                                                            if (!is_int($legalForm)) {
                                                                switch ($legalForm) {
                                                                    case "Economic Interest Group":
                                                                        $legalForm = 39;
                                                                        break;
                                                                    case "Association":
                                                                        $legalForm = 47;
                                                                        break;
                                                                    case "Sports Association":
                                                                        $legalForm = 59;
                                                                        break;
                                                                    case "Professional Association":
                                                                        $legalForm = 68;
                                                                        break;
                                                                    case "Savings Bank":
                                                                        $legalForm = 124;
                                                                        break;
                                                                    case "Community Property":
                                                                        $legalForm = 150;
                                                                        break;
                                                                    case "Community of Owners":
                                                                        $legalForm = 152;
                                                                        break;
                                                                    case "Order or Religious Institution":
                                                                        $legalForm = 164;
                                                                        break;
                                                                    case "Consulate":
                                                                        $legalForm = 181;
                                                                        break;
                                                                    case "Public Law Association":
                                                                        $legalForm = 197;
                                                                        break;
                                                                    case "Embassy":
                                                                        $legalForm = 203;
                                                                        break;
                                                                    case "Local Authority":
                                                                        $legalForm = 229;
                                                                        break;
                                                                    case "Sports Federation":
                                                                        $legalForm = 269;
                                                                        break;
                                                                    case "Foundation":
                                                                        $legalForm = 286;
                                                                        break;
                                                                    case "Mutual Insurance Company":
                                                                        $legalForm = 365;
                                                                        break;
                                                                    case "Regional Government Body":
                                                                        $legalForm = 434;
                                                                        break;
                                                                    case "Central Government Body":
                                                                        $legalForm = 436;
                                                                        break;
                                                                    case "Political Party":
                                                                        $legalForm = 439;
                                                                        break;
                                                                    case "Trade Union":
                                                                        $legalForm = 476;
                                                                        break;
                                                                    case "Farm Partnership":
                                                                        $legalForm = 510;
                                                                        break;
                                                                    case "Public Limited Company":
                                                                        $legalForm = 524;
                                                                        break;
                                                                    case "Civil Society":
                                                                        $legalForm = 554;
                                                                        break;
                                                                    case "General Partnership":
                                                                        $legalForm = 560;
                                                                        break;
                                                                    case "General and Limited Partnership":
                                                                        $legalForm = 562;
                                                                        break;
                                                                    case "Cooperative":
                                                                        $legalForm = 566;
                                                                        break;
                                                                    case "Worker-owned Company":
                                                                        $legalForm = 608;
                                                                        break;
                                                                    case "Limited Company":
                                                                        $legalForm = 612;
                                                                        break;
                                                                    case "Spanish Office":
                                                                        $legalForm = 713;
                                                                        break;
                                                                    case "Temporary Alliance of Enterprises":
                                                                        $legalForm = 717;
                                                                        break;
                                                                    case "Worker-owned Limited Company":
                                                                        $legalForm = 744;
                                                                        break;
                                                                    case "Regional Public Entity":
                                                                        $legalForm = 745;
                                                                        break;
                                                                    case "National Public Entity":
                                                                        $legalForm = 746;
                                                                        break;
                                                                    case "Local Public Entity":
                                                                        $legalForm = 747;
                                                                        break;
                                                                    case "Others":
                                                                        $legalForm = 877;
                                                                        break;
                                                                    case "Designation of Origin Supervisory Council":
                                                                        $legalForm = 878;
                                                                        break;
                                                                    case "Entity Managing Natural Areas":
                                                                        $legalForm = 879;
                                                                        break;
                                                                    default:
                                                                        $legalForm = 1;
                                                                }
                                                            }
                                                        } else {
                                                            $legalForm = 1;
                                                        }
                                                        $Enom->AddParam("es_legalform", $legalForm);
                                                        if ($legalForm == 1) {
                                                            $Enom->AddParam("es_accepttac", true);
                                                        }
                                                        $Enom->AddParam("es_registrantidtype", $idType);
                                                        $Enom->AddParam("es_registrantid", $params["additionalfields"]["ID Form Number"]);
                                                        $Enom->AddParam("es_adminidtype", $idType);
                                                        $Enom->AddParam("es_adminid", $params["additionalfields"]["ID Form Number"]);
                                                    } else {
                                                        if ($params["domainObj"]->getLastTLDSegment() == "au") {
                                                            $idtype = $params["additionalfields"]["Registrant ID Type"];
                                                            if ($idtype == "Business Registration Number") {
                                                                $idtype = "RBN";
                                                            }
                                                            $idnumber = $params["additionalfields"]["Eligibility ID"] ? $params["additionalfields"]["Eligibility ID"] : $params["additionalfields"]["Registrant ID"];
                                                            $Enom->AddParam("au_registrantidtype", $idtype);
                                                            $Enom->AddParam("au_registrantid", $params["additionalfields"]["Registrant ID"]);
                                                        } else {
                                                            if ($params["domainObj"]->getLastTLDSegment() == "sg") {
                                                                $idnumber = $params["additionalfields"]["RCB Singapore ID"];
                                                                $Enom->AddParam("sg_rcbid", $idnumber);
                                                            } else {
                                                                if ($params["domainObj"]->getLastTLDSegment() == "fr") {
                                                                    $additional = $params["additionalfields"];
                                                                    $Enom->AddParam("fr_legaltype", $additional["Legal Type"]);
                                                                    if ($params["countrycode"] == "FR") {
                                                                        $Enom->AddParam("fr_registrantbirthplace", (string) $additional["Birthplace Postcode"] . ", " . $additional["Birthplace City"]);
                                                                        $Enom->AddParam("fr_registrantbirthdate", $additional["Birthdate"]);
                                                                    } else {
                                                                        $Enom->AddParam("fr_registrantbirthplace", $params["countrycode"]);
                                                                        $Enom->AddParam("fr_registrantbirthdate", $additional["Birthdate"]);
                                                                    }
                                                                    if ($additional["Legal Type"] == "Company") {
                                                                        if (!empty($additional["SIRET Number"])) {
                                                                            $Enom->AddParam("fr_registrantlegalid", $additional["SIRET Number"]);
                                                                        }
                                                                        if (!empty($additional["DUNS Number"])) {
                                                                            $Enom->AddParam("fr_registrantdunsnumber", $additional["DUNS Number"]);
                                                                        }
                                                                    }
                                                                } else {
                                                                    if ($params["domainObj"]->getLastTLDSegment() == "nu") {
                                                                        $Enom->AddParam("iis_orgno", $params["additionalfields"]["Identification Number"]);
                                                                        if (!empty($params["additionalfields"]["VAT Number"])) {
                                                                            $Enom->AddParam("iis_vatno", $params["additionalfields"]["VAT Number"]);
                                                                        }
                                                                    } else {
                                                                        if ($params["domainObj"]->getLastTLDSegment() == "quebec") {
                                                                            $intendedUse = $params["additionalfields"]["Intended Use"];
                                                                            $intendedUseTruncated = substr($intendedUse, 0, 2048);
                                                                            $Enom->AddParam("core_intendeduse", $intendedUseTruncated);
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

?>