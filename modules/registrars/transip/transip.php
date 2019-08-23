<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require_once dirname(__FILE__) . "/Transip/DomainService.php";
function transip_getContactTypeMapping()
{
    return array(Transip_WhoisContact::TYPE_REGISTRANT => "Registrant contact", Transip_WhoisContact::TYPE_ADMINISTRATIVE => "Administrative contact", Transip_WhoisContact::TYPE_TECHNICAL => "Technical contact");
}
function transip_getContactFieldMapping()
{
    return array("firstName" => "First name", "middleName" => "Middle name", "lastName" => "Last name", "companyName" => "Company name", "companyKvk" => "Company KvK number", "companyType" => "Company type", "street" => "Street", "number" => "Number", "postalCode" => "Postal code", "city" => "City", "phoneNumber" => "Phone number", "faxNumber" => "Fax number", "email" => "Email address", "country" => "Country code");
}
function transip_getConfigArray()
{
    $configArray = array("Endpoint" => array("Type" => "dropdown", "Options" => "api.transip.nl,api.transip.be,api.transip.eu"), "Login" => array("Type" => "text", "Size" => "32", "Description" => "Your TransIP login name"), "PrivateKey" => array("Type" => "textarea", "Description" => "Private key (downloaded from your Controlpanel)"), "ReadOnlyMode" => array("Type" => "yesno", "Description" => "Don't allow any changes, use for testing"));
    if (!class_exists("SoapClient")) {
        $configArray["Description"] = array("Type" => "System", "Value" => "This module requires the PHP SOAP extension which is not currently compiled into your PHP build.");
    }
    return $configArray;
}
function transip_initialize($params)
{
    Transip_ApiSettings::$login = $params["Login"];
    Transip_ApiSettings::$privateKey = $params["PrivateKey"];
    Transip_ApiSettings::$mode = $params["ReadOnlyMode"] ? "readonly" : "readwrite";
    Transip_ApiSettings::$endpoint = $params["Endpoint"];
}
function transip_splitAddress($address)
{
    $address = trim($address);
    try {
        return transip_convertAddress($address);
    } catch (Exception $e) {
        if (preg_match("/[0-9]/Usi", $address, $matches)) {
            if ($address == (string) intval($address)) {
                return array("Postbus", trim($address));
            }
            $address = preg_replace("/([^0-9]*)([0-9]+)([^\\s]*)(.*)/si", "\$1\$4 \$2\$3", $address);
            $address = trim($address);
            try {
                return transip_convertAddress($address);
            } catch (Exception $e) {
                return array("FakeAddress", "1");
            }
        } else {
            return array($address, "1");
        }
    }
}
function transip_convertAddress($address)
{
    $maxLoop = 10;
    $address = preg_replace("/(.*\\s)([0-9]+)(\\s*)-(\\s*)([0-9]+)(.*)/si", "\$1\$2-\$5\$6", $address);
    $address = preg_replace("/(.*\\s)([0-9]+)\\s([0-9]+)\\s*hg(.*)/Usi", "\$1\$2-\$3hg\$4", $address);
    $address = preg_replace("/(.*\\s)([0-9]+)(\\s*)(kamer|k)(\\s*)([0-9]+)(.*)/si", "\$1\$2-k\$6\$7", $address);
    if (preg_match("/^([^0-9\\s]+)([0-9]+)\$/", $address, $matches)) {
        return array($matches[1], $matches[2]);
    }
    while (!preg_match("/(.*)[^0-9][0-9]+?[^\\s]*\$/Usi", $address, $matches) && $maxLoop) {
        $parts = explode(" ", $address);
        if (count($parts) < 3) {
            throw new Exception("Could not convert address '" . $address . "'");
        }
        $lastEl = array_pop($parts);
        $parts[count($parts) - 1] .= $lastEl;
        $address = implode(" ", $parts);
        $maxLoop--;
    }
    if (!preg_match("/(.*)[^0-9][0-9]+?[^\\s]*\$/Usi", $address, $matches) && $maxLoop == 0) {
        throw new Exception("Hit limit of 10 rounds while trying to convert address line '" . $address . "'");
    }
    $street = $matches[1];
    $number = trim(substr($address, strlen($street)));
    return array($street, $number);
}
function transip_getContactsForRegisterAndTransfer($data)
{
    $address = transip_splitaddress($data["address1"]);
    $reg = new Transip_WhoisContact();
    $reg->type = "registrant";
    $reg->firstName = $data["firstname"];
    $reg->lastName = $data["lastname"];
    $reg->companyName = $data["companyname"];
    $reg->postalCode = $data["postcode"];
    $reg->city = $data["city"];
    list($reg->street, $reg->number) = $address;
    $reg->country = $data["country"];
    $reg->phoneNumber = $data["phonenumber"];
    $reg->email = $data["email"];
    $adminAddress = transip_splitaddress($data["adminaddress1"]);
    $admin = new Transip_WhoisContact();
    $admin->type = "administrative";
    $admin->firstName = $data["adminfirstname"];
    $admin->lastName = $data["adminlastname"];
    $admin->companyName = $data["admincompanyname"];
    $admin->postalCode = $data["adminpostcode"];
    $admin->city = $data["admincity"];
    list($admin->street, $admin->number) = $adminAddress;
    $admin->country = $data["admincountry"];
    $admin->phoneNumber = $data["adminphonenumber"];
    $admin->email = $data["adminemail"];
    $techAddress = transip_splitaddress($data["adminaddress1"]);
    $tech = new Transip_WhoisContact();
    $tech->type = "technical";
    $tech->firstName = $data["adminfirstname"];
    $tech->lastName = $data["adminlastname"];
    $tech->companyName = $data["admincompanyname"];
    $tech->postalCode = $data["adminpostcode"];
    $tech->city = $data["admincity"];
    list($tech->street, $tech->number) = $techAddress;
    $tech->country = $data["admincountry"];
    $tech->phoneNumber = $data["adminphonenumber"];
    $tech->email = $data["adminemail"];
    return array($reg, $admin, $tech);
}
function transip_whoisContactsToContactDetails($contacts)
{
    $contactTypeMapping = transip_getcontacttypemapping();
    $contactFieldMapping = transip_getcontactfieldmapping();
    $contactDetails = array();
    foreach ($contacts as $contact) {
        if (!isset($contactTypeMapping[$contact->type])) {
            continue;
        }
        $whmcsType = $contactTypeMapping[$contact->type];
        if (isset($contactDetails[$whmcsType])) {
            continue;
        }
        $details = array();
        foreach ($contactFieldMapping as $transipField => $whmcsField) {
            $details[$whmcsField] = $contact->{$transipField};
        }
        $contactDetails[$whmcsType] = $details;
    }
    return $contactDetails;
}
function transip_contactDetailsToWhoisContacts($contactDetails)
{
    $contactTypeMapping = transip_getcontacttypemapping();
    $contactFieldMapping = transip_getcontactfieldmapping();
    $contacts = array();
    foreach ($contactTypeMapping as $transipType => $whmcsType) {
        if (isset($contactDetails[$whmcsType])) {
            $contact = new Transip_WhoisContact();
            $contact->type = $transipType;
            foreach ($contactFieldMapping as $transipField => $whmcsField) {
                if (isset($contactDetails[$whmcsType][$whmcsField])) {
                    $contact->{$transipField} = $contactDetails[$whmcsType][$whmcsField];
                }
            }
            $contacts[] = $contact;
        }
    }
    return $contacts;
}
function transip_GetNameservers($params)
{
    transip_initialize($params);
    $domain = NULL;
    try {
        $domainName = $params["sld"] . "." . $params["tld"];
        $domain = Transip_DomainService::getInfo($domainName);
        $nameservers = array();
        foreach ($domain->nameservers as $index => $nameserver) {
            $nameservers["ns" . ($index + 1)] = $nameserver->hostname;
        }
        logModuleCall("transip", "GetNameservers", $domainName, $domain, NULL);
        return $nameservers;
    } catch (Exception $exception) {
        logModuleCall("transip", "GetNameservers", $domainName, $domain);
        return array("error" => $exception->getMessage());
    }
}
function transip_SaveNameservers($params)
{
    transip_initialize($params);
    try {
        $domainName = $params["sld"] . "." . $params["tld"];
        $nameservers = array();
        foreach ($params as $key => $value) {
            if (preg_match("/^ns([0-9]+)\$/", $key, $matches) && !empty($value)) {
                $nameservers[$matches[1] - 1] = new Transip_Nameserver($value);
            }
        }
        Transip_DomainService::setNameservers($domainName, $nameservers);
        logModuleCall("transip", "SaveNameservers", array($domainName, $nameservers), NULL);
        return array();
    } catch (Exception $exception) {
        return array("error" => $exception->getMessage());
    }
}
function transip_GetRegistrarLock($params)
{
    transip_initialize($params);
    try {
        $domainName = $params["sld"] . "." . $params["tld"];
        $isLocked = Transip_DomainService::getIsLocked($domainName);
        $result = $isLocked ? "locked" : "unlocked";
        logModuleCall("transip", "GetRegistrarLock", $domainName, $isLocked, $result);
        return $result;
    } catch (Exception $exception) {
        return array("error" => $exception->getMessage());
    }
}
function transip_SaveRegistrarLock($params)
{
    transip_initialize($params);
    try {
        $domainName = $params["sld"] . "." . $params["tld"];
        if ($params["lockenabled"] == "locked") {
            $response = Transip_DomainService::setLock($domainName);
            logModuleCall("transip", "SaveRegistrarLock", array("setLock()", $domainName), $response);
        } else {
            $response = Transip_DomainService::unsetLock($domainName);
            logModuleCall("transip", "SaveRegistrarLock", array("unsetLock()", $domainName), $response);
        }
        return array();
    } catch (Exception $exception) {
        return array("error" => $exception->getMessage());
    }
}
function transip_GetDNS($params)
{
    transip_initialize($params);
    try {
        $domainName = $params["sld"] . "." . $params["tld"];
        $domain = Transip_DomainService::getInfo($domainName);
        $records = array();
        foreach ($domain->dnsEntries as $dnsEntry) {
            $records[] = array("hostname" => $dnsEntry->name, "type" => $dnsEntry->type, "address" => $dnsEntry->content);
        }
        logModuleCall("transip", "GetDNS", $domainName, (array) $domain, $records);
        return $records;
    } catch (Exception $exception) {
        return array("error" => $exception->getMessage());
    }
}
function transip_SaveDNS($params)
{
    transip_initialize($params);
    try {
        $domainName = $params["sld"] . "." . $params["tld"];
        $dnsEntries = array();
        foreach ($params["dnsrecords"] as $dnsRecord) {
            if (empty($dnsRecord["hostname"]) || empty($dnsRecord["address"])) {
                continue;
            }
            $dnsEntries[] = new Transip_DnsEntry($dnsRecord["hostname"], 86400, $dnsRecord["type"], $dnsRecord["address"]);
        }
        $response = Transip_DomainService::setDnsEntries($domainName, $dnsEntries);
        logModuleCall("transip", "SaveDNS", array($domainName, $dnsEntries), $response);
        return array();
    } catch (Exception $exception) {
        return array("error" => $exception->getMessage());
    }
}
function transip_RegisterDomain($params)
{
    transip_initialize($params);
    try {
        $domain = new Transip_Domain($params["sld"] . "." . $params["tld"]);
        $domain->nameservers = array();
        foreach ($params as $key => $value) {
            if (preg_match("/^ns([0-9]+)\$/", $key, $matches) && !empty($value)) {
                $domain->nameservers[$matches[1] - 1] = new Transip_Nameserver($value);
            }
        }
        $domain->contacts = transip_getcontactsforregisterandtransfer($params);
        $response = Transip_DomainService::register($domain);
        logModuleCall("transip", "RegisterDomain", $domain, "");
        return array();
    } catch (Exception $exception) {
        return array("error" => $exception->getMessage());
    }
}
function transip_TransferDomain($params)
{
    transip_initialize($params);
    try {
        $domain = new Transip_Domain($params["sld"] . "." . $params["tld"]);
        $domain->nameservers = array();
        foreach ($params as $key => $value) {
            if (preg_match("/^ns([0-9]+)\$/", $key, $matches) && !empty($value)) {
                $domain->nameservers[$matches[1] - 1] = new Transip_Nameserver($value);
            }
        }
        $domain->contacts = transip_getcontactsforregisterandtransfer($params);
        $authCode = $params["transfersecret"];
        logModuleCall("transip", "TransferDomain", array($domain, $authCode), "");
        if (preg_match("/\\.(\\w+)\$/", $domain->name, $matches)) {
            switch ($matches[1]) {
                case "be":
                case "eu":
                case "nl":
                    Transip_DomainService::transferWithoutOwnerChange($domain, $authCode);
                    break;
                default:
                    Transip_DomainService::transferWithOwnerChange($domain, $authCode);
            }
        }
        return array();
    } catch (Exception $exception) {
        return array("error" => $exception->getMessage());
    }
}
function transip_RenewDomain($params)
{
    return array();
}
function transip_GetContactDetails($params)
{
    transip_initialize($params);
    try {
        $domainName = $params["sld"] . "." . $params["tld"];
        $domain = Transip_DomainService::getInfo($domainName);
        $result = transip_whoiscontactstocontactdetails($domain->contacts);
        logModuleCall("transip", "GetContactDetails", $domainName, (array) $domain, $result);
        return $result;
    } catch (Exception $exception) {
        return array("error" => $exception->getMessage());
    }
}
function transip_SaveContactDetails($params)
{
    transip_initialize($params);
    try {
        $domainName = $params["sld"] . "." . $params["tld"];
        $contacts = transip_contactdetailstowhoiscontacts($params["contactdetails"]);
        logModuleCall("transip", "SaveContactDetails", array($domainName, $contacts), "");
        Transip_DomainService::setContacts($domainName, $contacts);
        return array();
    } catch (Exception $exception) {
        return array("error" => $exception->getMessage());
    }
}
function transip_GetEPPCode($params)
{
    transip_initialize($params);
    try {
        $domainName = $params["sld"] . "." . $params["tld"];
        $authCode = array("eppcode" => Transip_DomainService::getAuthCode($domainName));
        logModuleCall("transip", "GetEPPCode", $domainName, $authCode);
        return $authCode;
    } catch (Exception $exception) {
        return array("error" => $exception->getMessage());
    }
}

?>