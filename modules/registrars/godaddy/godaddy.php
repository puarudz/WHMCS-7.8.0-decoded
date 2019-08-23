<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function godaddy_MetaData()
{
    return array("DisplayName" => "GoDaddy");
}
function godaddy_getConfigArray()
{
    $configArray = array("apiKey" => array("FriendlyName" => "API Key", "Type" => "text", "Size" => "40", "Description" => "Enter your GoDaddy API Key here"), "apiSecret" => array("FriendlyName" => "API Secret", "Type" => "password", "Size" => "20", "Description" => "Enter your GoDaddy API Secret Key here"), "TestMode" => array("FriendlyName" => "Enable Test Mode", "Type" => "yesno"));
    return $configArray;
}
function godaddy_RegisterDomain(array $params)
{
    try {
        $shopperId = godaddy_find_shopper_id($params);
    } catch (Exception $e) {
        return array("error" => $e->getMessage());
    }
    $goDaddyClient = WHMCS\Module\Registrar\GoDaddy\Client::factory($params["apiKey"], $params["apiSecret"], $params["TestMode"]);
    $agreeArgs = "tlds=" . $params["tld"];
    $agreeArgs .= "&privacy=" . $params["idprotection"];
    $agreeArgs .= "&forTransfer=false";
    $agreementKeys = array();
    try {
        $agreementData = $goDaddyClient->get("domains/agreements?" . $agreeArgs, array("headers" => array("X-Market-Id" => "en-US")));
    } catch (Exception $e) {
        return array("error" => $e->getMessage());
    }
    $agreementData = json_decode((string) $agreementData, true);
    foreach ($agreementData as $agreementDatum) {
        $agreementKeys[] = $agreementDatum["agreementKey"];
    }
    $contacts = array();
    foreach (array("Admin", "Billing", "Registrant", "Tech") as $contactType) {
        $prefix = "admin";
        if ($contactType == "Registrant") {
            $prefix = "";
        }
        $contacts["contact" . $contactType] = array("addressMailing" => array("address1" => $params[$prefix . "address1"], "address2" => $params[$prefix . "address2"], "city" => $params[$prefix . "city"], "country" => $params[$prefix . "country"], "postalCode" => $params[$prefix . "postcode"], "state" => $params[$prefix . "state"]), "email" => $params[$prefix . "email"], "nameFirst" => $params[$prefix . "firstname"], "nameLast" => $params[$prefix . "lastname"], "phone" => $params[$prefix . "fullphonenumber"]);
    }
    $nameservers = array();
    for ($i = 1; $i <= 5; $i++) {
        if ($params["ns" . $i]) {
            $nameservers[] = $params["ns" . $i];
        }
    }
    $ip = $params["original"]["model"]->lastLoginIp;
    if (!$ip) {
        $ip = App::getRemoteIp();
    }
    $params = array_merge(array("domain" => $params["domainname"], "nameServers" => $nameservers, "period" => (int) $params["regperiod"], "privacy" => $params["idprotection"], "renewAuto" => false, "consent" => array("agreedAt" => WHMCS\Carbon::now()->format(WHMCS\Module\Registrar\GoDaddy\Client::DATE_FORMAT), "agreedBy" => $ip, "agreementKeys" => $agreementKeys)), $contacts, godaddy_additional_fields($params));
    try {
        $response = $goDaddyClient->post("domains/purchase", array("headers" => array("X-Shopper-Id" => $shopperId), "json" => $params));
        $response = json_decode($response, true);
        logModuleCall("godaddy", "register", $params, $response);
    } catch (Exception $e) {
        logModuleCall("godaddy", "register", $params, $e->getMessage());
        return array("error" => $e->getMessage());
    }
}
function godaddy_RenewDomain(array $params)
{
    try {
        $shopperId = godaddy_find_shopper_id($params);
    } catch (Exception $e) {
        return array("error" => $e->getMessage());
    }
    $goDaddyClient = WHMCS\Module\Registrar\GoDaddy\Client::factory($params["apiKey"], $params["apiSecret"], $params["TestMode"]);
    try {
        $response = $goDaddyClient->post("domains/" . $params["domainname"] . "/renew", array("headers" => array("X-Shopper-Id" => $shopperId), "json" => array("period" => (int) $params["regperiod"])));
        $response = json_decode($response, true);
        logModuleCall("godaddy", "renew", array("url" => "domains/" . $params["domainname"] . "/renew", "period" => (int) $params["regperiod"]), $response);
    } catch (Exception $e) {
        logModuleCall("godaddy", "renew", array("url" => "domains/" . $params["domainname"] . "/renew", "period" => (int) $params["regperiod"]), $e->getMessage());
        return array("error" => $e->getMessage());
    }
    return array("success" => true);
}
function godaddy_TransferDomain(array $params)
{
    try {
        $shopperId = godaddy_find_shopper_id($params);
    } catch (Exception $e) {
        return array("error" => $e->getMessage());
    }
    $goDaddyClient = WHMCS\Module\Registrar\GoDaddy\Client::factory($params["apiKey"], $params["apiSecret"], $params["TestMode"]);
    $agreeArgs = "tlds=" . $params["tld"];
    $agreeArgs .= "&privacy=" . $params["idprotection"];
    $agreeArgs .= "&forTransfer=true";
    $agreementKeys = array();
    try {
        $agreementData = $goDaddyClient->get("domains/agreements?" . $agreeArgs, array("headers" => array("X-Market-Id" => "en-US")));
    } catch (Exception $e) {
        return array("error" => $e->getMessage());
    }
    $agreementData = json_decode((string) $agreementData, true);
    foreach ($agreementData as $agreementDatum) {
        $agreementKeys[] = $agreementDatum["agreementKey"];
    }
    $contacts = array();
    foreach (array("Admin", "Billing", "Registrant", "Tech") as $contactType) {
        $prefix = "admin";
        if ($contactType == "Registrant") {
            $prefix = "";
        }
        $contacts["contact" . $contactType] = array("addressMailing" => array("address1" => $params[$prefix . "address1"], "address2" => $params[$prefix . "address2"], "city" => $params[$prefix . "city"], "country" => $params[$prefix . "country"], "postalCode" => $params[$prefix . "postcode"], "state" => $params[$prefix . "state"]), "email" => $params[$prefix . "email"], "nameFirst" => $params[$prefix . "firstname"], "nameLast" => $params[$prefix . "lastname"], "phone" => $params[$prefix . "fullphonenumber"]);
    }
    $nameservers = array();
    for ($i = 1; $i <= 5; $i++) {
        if ($params["ns" . $i]) {
            $nameservers[] = $params["ns" . $i];
        }
    }
    $ip = $params["original"]["model"]->lastLoginIp;
    if (!$ip) {
        $ip = App::getRemoteIp();
    }
    $params = array_merge(array("authCode" => $params["transfersecret"], "domain" => $params["domainname"], "nameServers" => $nameservers, "period" => (int) $params["regperiod"], "privacy" => $params["idprotection"], "renewAuto" => false, "consent" => array("agreedAt" => WHMCS\Carbon::now()->format(WHMCS\Module\Registrar\GoDaddy\Client::DATE_FORMAT), "agreedBy" => $ip, "agreementKeys" => $agreementKeys)), $contacts, godaddy_additional_fields($params));
    try {
        $response = $goDaddyClient->post("domains/" . $params["domainname"] . "/transfer", array("headers" => array("X-Shopper-Id" => $shopperId), "json" => $params));
        $response = json_decode($response, true);
        logModuleCall("godaddy", "transfer", $params, $response);
    } catch (Exception $e) {
        logModuleCall("godaddy", "transfer", $params, $e->getMessage());
        return array("error" => $e->getMessage());
    }
}
function godaddy_SaveRegistrarLock(array $params)
{
    try {
        $shopperId = godaddy_find_shopper_id($params);
    } catch (Exception $e) {
        return array("error" => $e->getMessage());
    }
    $goDaddyClient = WHMCS\Module\Registrar\GoDaddy\Client::factory($params["apiKey"], $params["apiSecret"], $params["TestMode"]);
    try {
        $response = $goDaddyClient->patch("domains/" . $params["domainname"], array("headers" => array("X-Shopper-Id" => $shopperId), "json" => array("locked" => $params["lockenabled"] == "locked")));
        $response = json_decode($response, true);
        logModuleCall("godaddy", "getNameservers", $params, $response);
    } catch (Exception $e) {
        logModuleCall("godaddy", "getNameservers", $params, $e->getMessage());
        return array("error" => $e->getMessage());
    }
}
function godaddy_SaveNameservers(array $params)
{
    try {
        $shopperId = godaddy_find_shopper_id($params);
    } catch (Exception $e) {
        return array("error" => $e->getMessage());
    }
    $goDaddyClient = WHMCS\Module\Registrar\GoDaddy\Client::factory($params["apiKey"], $params["apiSecret"], $params["TestMode"]);
    $nameServers = array();
    for ($x = 1; $x < 5; $x++) {
        if ($params["ns" . $x]) {
            $nameServers[] = $params["ns" . $x];
        }
    }
    try {
        $body = array("nameServers" => $nameServers);
        $response = $goDaddyClient->patch("domains/" . $params["domainname"], array("headers" => array("X-Shopper-Id" => $shopperId), "json" => $body));
        $response = json_decode($response, true);
        logModuleCall("godaddy", "setNameservers", $body, $response, $response, array($response["authCode"]));
    } catch (Exception $e) {
        logModuleCall("godaddy", "setNameservers", $body, $e->getMessage());
        return array("error" => $e->getMessage());
    }
}
function godaddy_GetDomainInformation(array $params)
{
    try {
        $shopperId = godaddy_find_shopper_id($params);
    } catch (Exception $e) {
        return array("error" => $e->getMessage());
    }
    $goDaddyClient = WHMCS\Module\Registrar\GoDaddy\Client::factory($params["apiKey"], $params["apiSecret"], $params["TestMode"]);
    $icannTld = $contactChangePendingSuspension = $irtpTransferLock = false;
    $contactChangePending = false;
    $irtpTransferLockExpiryDate = $contactChangeExpiryDate = NULL;
    $nameServers = array();
    try {
        $response = $goDaddyClient->get("domains/" . $params["domainname"], array("headers" => array("X-Shopper-Id" => $shopperId)));
        $response = json_decode($response, true);
        $x = 1;
        foreach ($response["nameServers"] as $nameServer) {
            $nameServers["ns" . $x] = $nameServer;
            $x++;
        }
        if ($response["transferAwayEligibleAt"]) {
            $icannTld = $irtpTransferLock = true;
            $irtpTransferLockExpiryDate = WHMCS\Carbon::createFromFormat(WHMCS\Module\Registrar\GoDaddy\Client::DATE_FORMAT, $response["transferAwayEligibleAt"]);
        }
        if ($response["status"] == "PENDING_UPDATE") {
            $contactChangePending = true;
        }
        logModuleCall("godaddy", "getDomainInfo", "domains/" . $params["domainname"], $response, $response, array($response["authCode"]));
    } catch (Exception $e) {
        logModuleCall("godaddy", "getDomainInfo", "domains/" . $params["domainname"], $e->getMessage());
        return array("error" => $e->getMessage());
    }
    $domainInfo = (new WHMCS\Domain\Registrar\Domain())->setDomain($response["domain"])->setNameservers($nameServers)->setRegistrationStatus(godaddy_normalise_status($response["status"]))->setIrtpOptOutStatus(false)->setIrtpTransferLock($irtpTransferLock)->setTransferLock($response["locked"] == 1)->setDomainContactChangePending($contactChangePending)->setPendingSuspension($contactChangePendingSuspension)->setRegistrantEmailAddress($response["contactRegistrant"]["email"])->setIsIrtpEnabled($icannTld)->setIrtpVerificationTriggerFields(array("Registrant" => array("First Name", "Last Name", "Organisation Name", "Email")))->setIdProtectionStatus($response["privacy"]);
    if (!is_null($irtpTransferLockExpiryDate)) {
        $domainInfo->setIrtpTransferLockExpiryDate($irtpTransferLockExpiryDate)->setTransferLockExpiryDate($irtpTransferLockExpiryDate);
    }
    if (!is_null($contactChangeExpiryDate)) {
        $domainInfo->setDomainContactChangeExpiryDate($contactChangeExpiryDate);
    }
    return $domainInfo;
}
function godaddy_ResendIRTPVerificationEmail(array $params)
{
    try {
        $shopperId = godaddy_find_shopper_id($params);
    } catch (Exception $e) {
        return array("error" => $e->getMessage());
    }
    $goDaddyClient = WHMCS\Module\Registrar\GoDaddy\Client::factory($params["apiKey"], $params["apiSecret"], $params["TestMode"]);
    try {
        $response = $goDaddyClient->post("domains/" . $params["domainname"] . "/verifyRegistrantEmail", array("headers" => array("X-Shopper-Id" => $shopperId)));
        $response = json_decode($response, true);
        logModuleCall("godaddy", "resendVerification", $params, $response);
    } catch (Exception $e) {
        logModuleCall("godaddy", "resendVerification", $params, $e->getMessage());
        return array("error" => $e->getMessage());
    }
    return array("success" => true);
}
function godaddy_GetContactDetails(array $params)
{
    try {
        $shopperId = godaddy_find_shopper_id($params);
    } catch (Exception $e) {
        return array("error" => $e->getMessage());
    }
    $goDaddyClient = WHMCS\Module\Registrar\GoDaddy\Client::factory($params["apiKey"], $params["apiSecret"], $params["TestMode"]);
    $contactTypes = array("Registrant", "Admin", "Tech");
    $values = array();
    try {
        $response = $goDaddyClient->get("domains/" . $params["domainname"], array("headers" => array("X-Shopper-Id" => $shopperId)));
        $response = json_decode($response, true);
        foreach ($contactTypes as $contactType) {
            $type = "contact" . $contactType;
            $address = $response[$type]["addressMailing"];
            $values[$contactType]["First Name"] = $response[$type]["nameFirst"];
            $values[$contactType]["Last Name"] = $response[$type]["nameLast"];
            $values[$contactType]["Organisation Name"] = $response[$type]["organization"];
            $values[$contactType]["Job Title"] = $response[$type]["jobTitle"];
            $values[$contactType]["Email"] = $response[$type]["email"];
            $values[$contactType]["Address 1"] = $address["address1"];
            $values[$contactType]["Address 2"] = $address["address2"];
            $values[$contactType]["City"] = $address["city"];
            $values[$contactType]["State"] = $address["state"];
            $values[$contactType]["Postcode"] = $address["postalCode"];
            $values[$contactType]["Country"] = $address["country"];
            $values[$contactType]["Phone"] = $response[$type]["phone"];
            $values[$contactType]["Fax"] = $response[$type]["fax"];
        }
        logModuleCall("godaddy", "getContacts", "domains/" . $params["domainname"], $response, $response, array($response["authCode"]));
    } catch (Exception $e) {
        logModuleCall("godaddy", "getContacts", "domains/" . $params["domainname"], $e->getMessage());
        return array("error" => $e->getMessage());
    }
    return $values;
}
function godaddy_SaveContactDetails(array $params)
{
    try {
        $shopperId = godaddy_find_shopper_id($params);
    } catch (Exception $e) {
        return array("error" => $e->getMessage());
    }
    $goDaddyClient = WHMCS\Module\Registrar\GoDaddy\Client::factory($params["apiKey"], $params["apiSecret"], $params["TestMode"]);
    $contactTypes = array("Registrant", "Admin", "Tech");
    $contacts = array();
    $contactDetails = $params["contactdetails"];
    foreach ($contactTypes as $contactType) {
        $contacts["contact" . $contactType] = array("addressMailing" => array("address1" => $contactDetails[$contactType]["Address 1"], "address2" => $contactDetails[$contactType]["Address 2"], "city" => $contactDetails[$contactType]["City"], "country" => $contactDetails[$contactType]["Country"], "postalCode" => $contactDetails[$contactType]["Postcode"], "state" => $contactDetails[$contactType]["State"]), "email" => $contactDetails[$contactType]["Email"], "jobTitle" => $contactDetails[$contactType]["Job Title"], "nameFirst" => $contactDetails[$contactType]["First Name"], "nameLast" => $contactDetails[$contactType]["Last Name"], "organization" => $contactDetails[$contactType]["Organisation Name"], "phone" => $contactDetails[$contactType]["Phone"], "fax" => $contactDetails[$contactType]["Fax"]);
    }
    try {
        $response = $goDaddyClient->patch("domains/" . $params["domainname"] . "/contacts", array("headers" => array("X-Shopper-Id" => $shopperId), "json" => $contacts));
        $response = json_decode($response, true);
        logModuleCall("godaddy", "setContacts", $contacts, $response);
    } catch (Exception $e) {
        logModuleCall("godaddy", "setContacts", $contacts, $e->getMessage());
        return array("error" => $e->getMessage());
    }
}
function godaddy_GetEPPCode(array $params)
{
    try {
        $shopperId = godaddy_find_shopper_id($params);
    } catch (Exception $e) {
        return array("error" => $e->getMessage());
    }
    $goDaddyClient = WHMCS\Module\Registrar\GoDaddy\Client::factory($params["apiKey"], $params["apiSecret"], $params["TestMode"]);
    try {
        $response = $goDaddyClient->get("domains/" . $params["domainname"], array("headers" => array("X-Shopper-Id" => $shopperId)));
        $response = json_decode($response, true);
        $eppCode = $response["authCode"];
        logModuleCall("godaddy", "getEppCode", "domains/" . $params["domainname"], $response, $response, array($response["authCode"]));
        return array("eppcode" => $eppCode);
    } catch (Exception $e) {
        logModuleCall("godaddy", "getEppCode", "domains/" . $params["domainname"], $e->getMessage());
        return array("error" => $e->getMessage());
    }
}
function godaddy_IDProtectToggle(array $params)
{
    try {
        $shopperId = godaddy_find_shopper_id($params);
    } catch (Exception $e) {
        return array("error" => $e->getMessage());
    }
    $goDaddyClient = WHMCS\Module\Registrar\GoDaddy\Client::factory($params["apiKey"], $params["apiSecret"], $params["TestMode"]);
    $domainInformation = godaddy_getdomaininformation($params);
    if (is_array($domainInformation)) {
        return $domainInformation;
    }
    $idProtectionEnabled = $domainInformation->getIdProtectionStatus();
    $enableIdProtect = $params["protectenable"];
    if ($idProtectionEnabled && $enableIdProtect) {
        return array("error" => "ID Protection is already enabled on this domain");
    }
    if ($enableIdProtect) {
        $agreeArgs = "tlds=" . $params["tld"];
        $agreeArgs .= "&privacy=true";
        $agreeArgs .= "&forTransfer=false";
        $agreementKeys = array();
        try {
            $agreementData = $goDaddyClient->get("domains/agreements?" . $agreeArgs, array("headers" => array("X-Market-Id" => "en-US")));
        } catch (Exception $e) {
            return array("error" => $e->getMessage());
        }
        $agreementData = json_decode((string) $agreementData, true);
        foreach ($agreementData as $agreementDatum) {
            $agreementKeys[] = $agreementDatum["agreementKey"];
        }
        $body = array("consent" => array("agreedAt" => WHMCS\Carbon::now()->format(WHMCS\Module\Registrar\GoDaddy\Client::DATE_FORMAT), "agreedBy" => App::getRemoteIp(), "agreementKeys" => $agreementKeys));
        try {
            $goDaddyClient->post("/v1/domains/" . $params["domainname"] . " /privacy/purchase", array("json" => $body));
            logModuleCall("godaddy", "enableIdProtect", $body, array());
        } catch (Exception $e) {
            logModuleCall("godaddy", "enableIdProtect", $body, $e->getMessage());
            return array("error" => $e->getMessage());
        }
    } else {
        try {
            $goDaddyClient->delete("domains/" . $params["domainname"] . "/privacy", array("headers" => array("X-Shopper-Id" => $shopperId)));
        } catch (Exception $e) {
            logModuleCall("godaddy", "disableIdProtect", "domains/" . $params["domainname"] . "/privacy", $e->getMessage());
            return array("error" => $e->getMessage());
        }
    }
    return array("success" => true);
}
function godaddy_Sync(array $params)
{
    $values = array();
    $domainInformation = godaddy_getdomaininformation($params);
    if (is_array($domainInformation)) {
        $message = "Domain " . $params["domainname"] . " not found for shopper";
        if (stristr($domainInformation["error"], $message)) {
            $values["transferredAway"] = true;
        } else {
            return $domainInformation;
        }
    } else {
        $values["expirydate"] = $domainInformation->getExpiryDate()->toDateString();
    }
    return $values;
}
function godaddy_TransferSync(array $params)
{
    $values = array();
    $domainInformation = godaddy_getdomaininformation($params);
    if (is_array($domainInformation)) {
        return $domainInformation;
    }
    switch ($domainInformation->getRegistrationStatus()) {
        case WHMCS\Domain\Registrar\Domain::STATUS_INACTIVE:
            $values["pendingtransfer"] = true;
            break;
        case WHMCS\Domain\Registrar\Domain::STATUS_EXPIRED:
            $values["failed"] = true;
            break;
        case WHMCS\Domain\Registrar\Domain::STATUS_ACTIVE:
        default:
            $values["completed"] = true;
            $values["expirydate"] = $domainInformation->getExpiryDate()->toDateString();
    }
    return $values;
}
function godaddy_AdditionalDomainFields(array $params)
{
    $values = array();
    $transientKey = "goDaddy" . ucfirst($params["tld"]);
    $fields = WHMCS\TransientData::getInstance()->retrieve($transientKey);
    if ($fields) {
        $fields = json_decode($fields, true);
        if ($fields && is_array($fields)) {
            return $fields;
        }
    }
    $goDaddyClient = WHMCS\Module\Registrar\GoDaddy\Client::factory($params["apiKey"], $params["apiSecret"], $params["TestMode"]);
    $agreeArgs = "tlds=" . $params["tld"];
    $agreeArgs .= "&privacy=" . $params["idprotection"];
    $agreeArgs .= "&forTransfer=" . ($params["type"] == "transfer");
    $agreementKeys = array();
    try {
        $agreementData = $goDaddyClient->get("domains/agreements?" . $agreeArgs, array("headers" => array("X-Market-Id" => "en-US")));
        $agreementData = json_decode((string) $agreementData, true);
        $fields = array();
        foreach ($agreementData as $agreementDatum) {
            $agreementKeys[] = $agreementDatum["agreementKey"];
            $url = $agreementDatum["url"];
            $fields[] = array("Name" => $agreementDatum["agreementKey"], "DisplayName" => $agreementDatum["title"], "LangVar" => $params["tld"] . $agreementDatum["agreementKey"], "Type" => "tickbox", "Required" => true, "Description" => "<a href=\"" . $url . "\" class='autoLinked'>" . $url . "</a>");
        }
        $values["fields"] = $fields;
        WHMCS\TransientData::getInstance()->store($transientKey, json_encode($fields), 86400 * 30);
    } catch (Exception $e) {
        return array("error" => $e->getMessage());
    }
    return $values;
}
function godaddy_find_shopper_id(array $params)
{
    if (!array_key_exists("userid", $params)) {
        $params["userid"] = WHMCS\Database\Capsule::table("tbldomains")->where("id", $params["domainid"])->value("userid");
    }
    $shopperId = WHMCS\Module\Registrar\GoDaddy\Shopper::findShopperId($params["userid"], $params);
    if (!$shopperId) {
        $shopperId = WHMCS\Module\Registrar\GoDaddy\Shopper::create($params);
    }
    return $shopperId;
}
function godaddy_normalise_status($status)
{
    switch ($status) {
        case "AWAITING":
        case "PENDING":
        case "RESERVED":
        case "REVERTED":
        case "UNLOCKED":
        case "UNPARKED":
        case "UPDATED":
            return WHMCS\Domain\Registrar\Domain::STATUS_INACTIVE;
        case "CANCELLED":
        case "EXPIRED":
        case "FAILED":
            return WHMCS\Domain\Registrar\Domain::STATUS_EXPIRED;
        case "CONFISCATED":
        case "DISABLED":
        case "EXCLUDED":
        case "HELD":
        case "LOCKED":
        case "PARKED":
        case "SUSPENDED":
        case "TRANSFERRED":
            return WHMCS\Domain\Registrar\Domain::STATUS_SUSPENDED;
    }
    return WHMCS\Domain\Registrar\Domain::STATUS_ACTIVE;
}
function godaddy_generate_random_password()
{
    $lowercase = 4;
    $uppercase = 4;
    $numbers = 4;
    return (new WHMCS\Utility\Random())->string($lowercase, $uppercase, $numbers, 0);
}
function godaddy_additional_fields(array $params)
{
    $fields = array();
    $additionalFields = $params["additionalfields"];
    switch ($params["domainObj"]->getTopLevel()) {
        case "es":
            $entityType = $additionalFields["Entity Type"];
            switch ($entityType) {
                case "39":
                    $entityType = "ECONOMIC_INTEREST_GROUP";
                    break;
                case "47":
                    $entityType = "ASSOCIATION";
                    break;
                case "59":
                    $entityType = "SPORTS";
                    break;
                case "68":
                    $entityType = "PROFESSIONAL";
                    break;
                case "124":
                    $entityType = "BANK_SAVINGS";
                    break;
                case "150":
                    $entityType = "COMMUNITY_PROPERTY";
                    break;
                case "152":
                    $entityType = "COMMUNITY_OF_OWNERS";
                    break;
                case "164":
                    $entityType = "INSTITUTION_RELIGIOUS";
                    break;
                case "181":
                    $entityType = "CONSULATE";
                    break;
                case "197":
                    $entityType = "ASSOCIATION_LAW";
                    break;
                case "203":
                    $entityType = "EMBASSY";
                    break;
                case "229":
                    $entityType = "LOCAL_AUTHORITY";
                    break;
                case "269":
                    $entityType = "FEDERATION_SPORT";
                    break;
                case "286":
                    $entityType = "FOUNDATION";
                    break;
                case "365":
                    $entityType = "INSURANCE";
                    break;
                case "434":
                    $entityType = "GOVERNMENT_REGIONAL";
                    break;
                case "436":
                    $entityType = "GOVERNMENT_CENTRAL";
                    break;
                case "439":
                    $entityType = "POLITICAL_PARTY";
                    break;
                case "476":
                    $entityType = "UNION_TRADE";
                    break;
                case "510":
                    $entityType = "PARTNERSHIP_FARM";
                    break;
                case "524":
                    $entityType = "COMPANY_LIMITED_PUBLIC";
                    break;
                case "554":
                    $entityType = "CIVIL_SOCIETY";
                    break;
                case "560":
                    $entityType = "PARTNERSHIP_GENERAL";
                    break;
                case "562":
                    $entityType = "PARTNERSHIP_GENERAL_LIMITED";
                    break;
                case "566":
                    $entityType = "COOPERATIVE";
                    break;
                case "608":
                    $entityType = "COMPANY_WORKER_OWNED";
                    break;
                case "612":
                    $entityType = "COMPANY_LIMITED";
                    break;
                case "713":
                    $entityType = "SPANISH_OFFICE";
                    break;
                case "717":
                    $entityType = "ALLIANCE_TEMPORARY";
                    break;
                case "744":
                    $entityType = "COMPANY_LIMITED_WORKER_OWNED";
                    break;
                case "745":
                    $entityType = "ENTITY_REGIONAL";
                    break;
                case "746":
                    $entityType = "ENTITY_NATIONAL";
                    break;
                case "747":
                    $entityType = "ENTITY_LOCAL";
                    break;
                case "877":
                    $entityType = "OTHERS";
                    break;
                case "878":
                    $entityType = "COUNCIL_SUPERVISORY";
                    break;
                case "879":
                    $entityType = "ENTITY_MANAGING_AREAS";
                    break;
                case "1|Individual":
                default:
                    $entityType = "INDIVIDUAL";
                    break;
            }
        case "com.es":
        case "nom.es":
        case "org.es":
            $fields["entityType"] = $additionalFields["Entity Type"];
            $fields["identificationType"] = $additionalFields["ID Form Type"];
            $fields["identificationNumber"] = $additionalFields["ID Form Number"];
            break;
        case "nyc":
            $entityType = "INDIVIDUAL";
            if ($params["companyname"]) {
                $entityType = "ORGANIZATION";
            }
            $fields["entityType"] = $entityType;
            break;
        case "sg":
            $fields["adminPersonalId"] = $additionalFields["Admin Personal ID"];
            $fields["identificationNumber"] = $additionalFields["RCB Singapore ID"];
            break;
        case "us":
            $intent = $additionalFields["Nexus Category"];
            switch ($intent) {
                case "C12":
                    $intent = "PERMANENT_RESIDENT";
                    break;
                case "C21":
                    $intent = "INCORPORATED";
                    break;
                case "C31":
                    $intent = "FOREIGN_BUSINESS";
                    break;
                case "C32":
                    $intent = "FOREIGN_OFFICE";
                    break;
                case "C11":
                default:
                    $intent = "CITIZEN";
                    break;
            }
        case "ca":
            switch ($additionalFields["Legal Type"]) {
                case "Canadian Citizen":
                    $entityType = "CITIZEN";
                    break;
                case "Permanent Resident of Canada":
                    $entityType = "RESIDENT_PERMANENT";
                    break;
                case "Government":
                    $entityType = "GOVERNMENT";
                    break;
                case "Canadian Educational Institution":
                    $entityType = "EDUCATIONAL";
                    break;
                case "Canadian Unincorporated Association":
                    $entityType = "ASSOCIATION";
                    break;
                case "Canadian Hospital":
                    $entityType = "HOSPITAL";
                    break;
                case "Partnership Registered in Canada":
                    $entityType = "PARTNERSHIP";
                    break;
                case "Trade-mark registered in Canada":
                    $entityType = "MARK_TRADE";
                    break;
                case "Canadian Trade Union":
                    $entityType = "UNION";
                    break;
                case "Canadian Political Party":
                    $entityType = "POLITICAL_PARTY";
                    break;
                case "Canadian Library Archive or Museum":
                    $entityType = "LIBRARY_ARCHIVE_MUSEUM";
                    break;
                case "Trust established in Canada":
                    $entityType = "TRUST";
                    break;
                case "Aboriginal Peoples":
                    $entityType = "ABORIGINAL";
                    break;
                case "Legal Representative of a Canadian Citizen":
                    $entityType = "LEGAL_REPRESENTATIVE";
                    break;
                case "Official mark registered in Canada":
                    $entityType = "MARK_REGISTERED";
                    break;
                case "Corporation":
                default:
                    $entityType = "CORPORATION";
                    break;
            }
        case "eu":
            $fields["entityType"] = $additionalFields["Entity Type"];
            break;
    }
    $fields["entityType"] = $entityType;
    $idType = $additionalFields["ID Form Type"];
    switch ($idType) {
        case "Tax Identification Number":
            $idType = "CITIZEN";
            break;
        case "Tax Identification Code":
            $idType = "COMPANY";
            break;
        case "Foreigner Identification Number":
        case "Other Identification":
        default:
            $idType = "OTHER";
    }
    $fields["identificationType"] = $idType;
    $fields["identificationNumber"] = $additionalFields["ID Form Number"];
    break;
}

?>