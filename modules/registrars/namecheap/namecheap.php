<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function namecheap_getConfigArray()
{
    $configarray = array("Username" => array("Type" => "text", "Size" => "20", "Description" => "Enter your username here."), "Password" => array("Type" => "text", "Size" => "20", "Description" => "Enter your API key here. " . "To get your api key, go to Manage Profile section in Namecheap.com," . " then click API access link on the left hand side. C/p the key here. DON'T include your password."), "PromotionCode" => array("Type" => "text", "Size" => "20", "Description" => "Enter your promotional (coupon) code."), "SandboxUsername" => array("Type" => "text", "Size" => "20", "Description" => "Enter your sandbox username here. (This will be used only if you set the test mode on.)"), "SandboxPassword" => array("Type" => "text", "Size" => "20", "Description" => "Enter your sandbox API key here. (This will be used only if you set the test mode on.)"), "TestMode" => array("Type" => "yesno"));
    return $configarray;
}
function namecheap_GetNameservers($params)
{
    require_once dirname(__FILE__) . "/namecheapapi.php";
    $testmode = (bool) $params["TestMode"];
    $username = $testmode ? $params["SandboxUsername"] : $params["Username"];
    $password = $testmode ? $params["SandboxPassword"] : $params["Password"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    if (!in_array(get_query_val("tbldomains", "status", array("id" => $params["domainid"])), array("Active", "Expired"))) {
        return array("error" => "Unable to obtain Nameservers for an unregistered domain");
    }
    $response = "";
    $result = $request_params = $values = array();
    $oIDNA = new NamecheapRegistrarIDNA($sld, $tld);
    $sld = $oIDNA->getEncodedSld();
    try {
        $request_params = array("SLD" => $sld, "TLD" => $tld);
        $api = new NamecheapRegistrarApi($username, $password, $testmode);
        $response = $api->request("namecheap.domains.dns.getList", $request_params);
        $result = $api->parseResponse($response);
        $ns = $result["DomainDNSGetListResult"]["Nameserver"];
        if (!isset($ns[0])) {
            $ns = array($ns);
        }
        list($values["ns1"], $values["ns2"], $values["ns3"], $values["ns4"], $values["ns5"]) = $ns;
    } catch (Exception $e) {
        $values["error"] = "An error occurred: " . $e->getMessage();
        logModuleCall("namecheap", "GetNameservers", array("command" => "namecheap.domains.dns.getList") + $request_params, $response, $result, array($password));
    }
    return $values;
}
function namecheap_SaveNameservers($params)
{
    require_once dirname(__FILE__) . "/namecheapapi.php";
    $testmode = (bool) $params["TestMode"];
    $username = $testmode ? $params["SandboxUsername"] : $params["Username"];
    $password = $testmode ? $params["SandboxPassword"] : $params["Password"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $oIDNA = new NamecheapRegistrarIDNA($sld, $tld);
    $sld = $oIDNA->getEncodedSld();
    $response = "";
    $result = $request_params = $values = array();
    $defaultNs = true;
    $defaultNsServers = array("dns1.registrar-servers.com", "dns2.registrar-servers.com", "dns3.registrar-servers.com", "dns4.registrar-servers.com", "dns5.registrar-servers.com");
    $nameservers = array($params["ns1"], $params["ns2"], $params["ns3"], $params["ns4"], $params["ns5"]);
    foreach ($nameservers as $k => $v) {
        if (!$v) {
            unset($nameservers[$k]);
            continue;
        }
        if (!in_array($v, $defaultNsServers)) {
            $defaultNs = false;
        }
    }
    try {
        $request_params = array("SLD" => $sld, "TLD" => $tld);
        $api = new NamecheapRegistrarApi($username, $password, $testmode);
        if (false === $defaultNs) {
            $request_params["Nameservers"] = implode(",", $nameservers);
            $response = $api->request("namecheap.domains.dns.setCustom", $request_params);
        } else {
            $response = $api->request("namecheap.domains.dns.setDefault", $request_params);
        }
        $result = $api->parseResponse($response);
    } catch (Exception $e) {
        $values["error"] = "An error occurred: " . $e->getMessage();
        logModuleCall("namecheap", "SetNameservers", array("command" => "namecheap.domains.dns.setCustom") + $request_params, $response, $result, array($password));
    }
    return $values;
}
function namecheap_GetRegistrarLock($params)
{
    require_once dirname(__FILE__) . "/namecheapapi.php";
    $testmode = (bool) $params["TestMode"];
    $username = $testmode ? $params["SandboxUsername"] : $params["Username"];
    $password = $testmode ? $params["SandboxPassword"] : $params["Password"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $oIDNA = new NamecheapRegistrarIDNA($sld, $tld);
    $sld = $oIDNA->getEncodedSld();
    $response = "";
    $result = $request_params = $values = array();
    try {
        $request_params = array("DomainName" => $sld . "." . $tld);
        $api = new NamecheapRegistrarApi($username, $password, $testmode);
        $response = $api->request("namecheap.domains.getRegistrarLock", $request_params);
        $result = $api->parseResponse($response);
        $lockstatus = "true" == $result["DomainGetRegistrarLockResult"]["@attributes"]["RegistrarLockStatus"];
        return $lockstatus ? "locked" : "unlocked";
    } catch (Exception $e) {
        $values["error"] = "An error occurred: " . $e->getMessage();
        logModuleCall("namecheap", "GetRegistrarLock", array("command" => "namecheap.domains.getRegistrarLock") + $request_params, $response, $result, array($password));
    }
    return $values;
}
function namecheap_SaveRegistrarLock($params)
{
    require_once dirname(__FILE__) . "/namecheapapi.php";
    $testmode = (bool) $params["TestMode"];
    $username = $testmode ? $params["SandboxUsername"] : $params["Username"];
    $password = $testmode ? $params["SandboxPassword"] : $params["Password"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $oIDNA = new NamecheapRegistrarIDNA($sld, $tld);
    $sld = $oIDNA->getEncodedSld();
    $response = "";
    $result = $request_params = $values = array();
    try {
        $request_params = array("DomainName" => $sld . "." . $tld, "LockAction" => "locked" == $params["lockenabled"] ? "lock" : "unlock");
        $api = new NamecheapRegistrarApi($username, $password, $testmode);
        $response = $api->request("namecheap.domains.setRegistrarLock", $request_params);
        $result = $api->parseResponse($response);
    } catch (Exception $e) {
        $rl_unable_domains = array("ca", "cm", "co.uk", "org.uk", "me.uk", "de", "eu", "ws", "uk");
        $msg = $e->getMessage();
        $values["error"] = "An error occurred: " . $msg;
        if ("[3031510] Failed to get Registrar Lock Status" == $msg && in_array(strtolower($tld), $rl_unable_domains)) {
            $values["error"] = "Registrar lock is not applicable for <strong>" . $tld . "</strong> domains.";
        }
        logModuleCall("namecheap", "SaveRegistrarLock", array("command" => "namecheap.domains.setRegistrarLock") + $request_params, $response, $result, array($password));
    }
    return $values;
}
function namecheap_GetEmailForwarding($params)
{
    require_once dirname(__FILE__) . "/namecheapapi.php";
    $testmode = (bool) $params["TestMode"];
    $username = $testmode ? $params["SandboxUsername"] : $params["Username"];
    $password = $testmode ? $params["SandboxPassword"] : $params["Password"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $oIDNA = new NamecheapRegistrarIDNA($sld, $tld);
    $sld = $oIDNA->getEncodedSld();
    $response = "";
    $result = $request_params = $values = array();
    try {
        $request_params = array("DomainName" => $sld . "." . $tld);
        $api = new NamecheapRegistrarApi($username, $password, $testmode);
        $response = $api->request("namecheap.domains.dns.getEmailForwarding", $request_params);
        $result = $api->parseResponse($response);
        $forward = $result["DomainDNSGetEmailForwardingResult"]["Forward"];
        if (!isset($forward[0])) {
            $forward = array($forward);
        }
        $values = array();
        foreach ($forward as $v) {
            $values[] = array("prefix" => $v["@attributes"]["mailbox"], "forwardto" => $v["@value"]);
        }
    } catch (Exception $e) {
        $values["error"] = "An error occurred: " . $e->getMessage();
        logModuleCall("namecheap", "GetEmailForwarding", array("command" => "namecheap.domains.dns.getEmailForwarding") + $request_params, $response, $result, array($password));
    }
    return $values;
}
function namecheap_SaveEmailForwarding($params)
{
    require_once dirname(__FILE__) . "/namecheapapi.php";
    $testmode = (bool) $params["TestMode"];
    $username = $testmode ? $params["SandboxUsername"] : $params["Username"];
    $password = $testmode ? $params["SandboxPassword"] : $params["Password"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $oIDNA = new NamecheapRegistrarIDNA($sld, $tld);
    $sld = $oIDNA->getEncodedSld();
    $response = "";
    $result = $request_params = $values = array();
    try {
        $request_params = array("DomainName" => $sld . "." . $tld);
        foreach ($params["prefix"] as $k => $v) {
            if (!empty($params["prefix"][$k]) && !empty($params["forwardto"][$k])) {
                $request_params["MailBox" . ($k + 1)] = $params["prefix"][$k];
                $request_params["ForwardTo" . ($k + 1)] = $params["forwardto"][$k];
            }
        }
        $api = new NamecheapRegistrarApi($username, $password, $testmode);
        $response = $api->request("namecheap.domains.dns.setEmailForwarding", $request_params);
        $result = $api->parseResponse($response);
    } catch (Exception $e) {
        $values["error"] = "An error occurred: " . $e->getMessage();
        logModuleCall("namecheap", "SaveEmailForwarding", array("command" => "namecheap.domains.dns.setEmailForwarding") + $request_params, $response, $result, array($password));
    }
    return $values;
}
function namecheap_GetDNS($params)
{
    require_once dirname(__FILE__) . "/namecheapapi.php";
    $testmode = (bool) $params["TestMode"];
    $username = $testmode ? $params["SandboxUsername"] : $params["Username"];
    $password = $testmode ? $params["SandboxPassword"] : $params["Password"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $oIDNA = new NamecheapRegistrarIDNA($sld, $tld);
    $sld = $oIDNA->getEncodedSld();
    $response = "";
    $result = $request_params = $values = array();
    try {
        $request_params = array("SLD" => $sld, "TLD" => $tld);
        $api = new NamecheapRegistrarApi($username, $password, $testmode);
        $response = $api->request("namecheap.domains.dns.getHosts", $request_params);
        $result = $api->parseResponse($response);
        $host = $result["DomainDNSGetHostsResult"]["host"];
        if (!isset($host[0])) {
            $host = array($host);
        }
        foreach ($host as $v) {
            $values[] = array("hostname" => $v["@attributes"]["Name"], "type" => $v["@attributes"]["Type"], "address" => $v["@attributes"]["Address"]);
        }
    } catch (Exception $e) {
        $values["error"] = "An error occurred: " . $e->getMessage();
        logModuleCall("namecheap", "GetDNS", array("command" => "namecheap.domains.dns.getHosts") + $request_params, $response, $result, array($password));
    }
    return $values;
}
function namecheap_SaveDNS($params)
{
    require_once dirname(__FILE__) . "/namecheapapi.php";
    $testmode = (bool) $params["TestMode"];
    $username = $testmode ? $params["SandboxUsername"] : $params["Username"];
    $password = $testmode ? $params["SandboxPassword"] : $params["Password"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $oIDNA = new NamecheapRegistrarIDNA($sld, $tld);
    $sld = $oIDNA->getEncodedSld();
    $response = "";
    $result = $request_params = $values = array();
    try {
        $request_params = array("SLD" => $sld, "TLD" => $tld);
        foreach ($params["dnsrecords"] as $k => $v) {
            if (!empty($v["hostname"]) && !empty($v["type"]) && !empty($v["address"])) {
                $request_params["HostName" . ($k + 1)] = $v["hostname"];
                $request_params["RecordType" . ($k + 1)] = $v["type"];
                $request_params["Address" . ($k + 1)] = $v["address"];
                if ($v["type"] == "MX") {
                    $request_params["EmailType"] = "MX";
                }
                if ($v["type"] == "MXE") {
                    $request_params["EmailType"] = "MXE";
                }
            }
        }
        $api = new NamecheapRegistrarApi($username, $password, $testmode);
        $response = $api->request("namecheap.domains.dns.setHosts", $request_params);
        $result = $api->parseResponse($response);
        if (isset($result["DomainDNSSetHostsResult"]["Warnings"]["Warning"])) {
            $message = "Saving DNS warning<br />" . "-----------------------------------------------------------------------------------------<br />" . $result["DomainDNSSetHostsResult"]["Warnings"]["Warning"]["@value"] . "<br />" . "-----------------------------------------------------------------------------------------<br />" . "Domain: " . $tld . "." . $sld . "<br />" . "<pre>" . print_r($params["dnsrecords"]) . "</pre>";
            sendAdminNotification("system", "WHMCS Namecheap Domain Registrar Module", $message);
        }
    } catch (Exception $e) {
        $values["error"] = "An error occurred: " . $e->getMessage();
        logModuleCall("namecheap", "SaveDNS", array("command" => "namecheap.domains.dns.setHosts") + $request_params, $response, $result, array($password));
    }
    return $values;
}
function namecheap_RegisterDomain($params)
{
    require_once dirname(__FILE__) . "/namecheapapi.php";
    $testmode = (bool) $params["TestMode"];
    $username = $testmode ? $params["SandboxUsername"] : $params["Username"];
    $password = $testmode ? $params["SandboxPassword"] : $params["Password"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $oIDNA = new NamecheapRegistrarIDNA($sld, $tld);
    $sld = $oIDNA->getEncodedSld();
    $response = "";
    $result = $request_params = $values = array();
    $nameservers = array($params["ns1"], $params["ns2"], $params["ns3"], $params["ns4"], $params["ns5"]);
    foreach ($nameservers as $k => $v) {
        if (!$v) {
            unset($nameservers[$k]);
        }
    }
    try {
        if ("ca" == strtolower($tld)) {
            $params["adminpostcode"] = strtoupper(str_replace(" ", "", $params["adminpostcode"]));
            $params["postcode"] = strtoupper(str_replace(" ", "", $params["postcode"]));
            if ("CA" == $params["admincountry"]) {
                $params["adminpostcode"] = substr($params["adminpostcode"], 0, 3) . " " . substr($params["adminpostcode"], 3);
                $params["postcode"] = substr($params["postcode"], 0, 3) . " " . substr($params["postcode"], 3);
            }
            if ("US" == $params["admincountry"] && 5 < strlen($params["adminpostcode"])) {
                $params["adminpostcode"] = substr($params["adminpostcode"], 0, 5) . "-" . substr($params["adminpostcode"], 5);
            }
        }
        $registrant = array("RegistrantFirstName" => $params["firstname"], "RegistrantLastName" => $params["lastname"], "RegistrantOrganizationName" => $params["companyname"], "RegistrantAddress1" => $params["address1"], "RegistrantAddress2" => $params["address2"], "RegistrantCity" => $params["city"], "RegistrantStateProvince" => $params["state"], "RegistrantPostalCode" => $params["postcode"], "RegistrantCountry" => $params["country"], "RegistrantPhone" => $params["fullphonenumber"], "RegistrantEmailAddress" => $params["email"]);
        $registrantAdmin = array("FirstName" => $params["adminfirstname"], "LastName" => $params["adminlastname"], "OrganizationName" => $params["admincompanyname"], "Address1" => $params["adminaddress1"], "Address2" => $params["adminaddress2"], "City" => $params["admincity"], "StateProvince" => $params["adminstate"], "PostalCode" => $params["adminpostcode"], "Country" => $params["admincountry"], "Phone" => $params["adminfullphonenumber"], "EmailAddress" => $params["adminemail"]);
        $aux = $tech = $admin = array();
        foreach ($registrantAdmin as $k => $v) {
            $admin["Admin" . $k] = $v;
            $tech["Tech" . $k] = $v;
            $aux["AuxBilling" . $k] = $v;
        }
        $request_params = array("DomainName" => $sld . "." . $tld, "Years" => $params["regperiod"], "Nameservers" => implode(",", $nameservers));
        if ($oIDNA->sldWasEncoded()) {
            $request_params["IdnCode"] = $oIDNA->getIdnCode(empty($params["additionalfields"]["idnCode"]) ? "" : $params["additionalfields"]["idnCode"]);
        }
        $request_params += $registrant + $admin + $tech + $aux;
        if (!empty($params["PromotionCode"])) {
            $request_params["PromotionCode"] = $params["PromotionCode"];
        }
        $wg_ex = array("bz", "ca", "cn", "co.uk", "de", "eu", "in", "me.uk", "mobi", "nu", "org.uk", "us", "ws", "uk");
        if ($params["idprotection"] && !in_array(strtolower($tld), $wg_ex)) {
            $request_params["AddFreeWhoisguard"] = "yes";
            $request_params["WGEnabled"] = "yes";
        }
        if ("eu" == strtolower($tld)) {
            $request_params["EUAgreeWhoisPolicy"] = "YES";
            $request_params["EUAgreeDeletePolicy"] = "YES";
            $langs = array("BG" => "Bulgaria", "CS" => "Czech", "DS" => "Danish", "NL" => "Dutch", "EN" => "English", "ET" => "Estonian", "FI" => "Finnish", "FR" => "French", "DE" => "German", "EL" => "Greek", "HL" => "Hungarian", "IT" => "Italian", "LV" => "Latvian", "LI" => "Lithuanian", "MT" => "Maltese", "PL" => "Polish", "PT" => "Portuguese", "RO" => "Romania", "SK" => "Slovak", "SL" => "Slovenian", "ES" => "Spanish", "SV" => "Swedish");
            foreach ($langs as $k => $v) {
                if ($v == $params["additionalfields"]["Language for Address Used"]) {
                    $request_params["EUAdrLang"] = $k;
                    break;
                }
            }
        } else {
            if ("us" == strtolower($tld)) {
                $request_params["RegistrantNexus"] = $params["additionalfields"]["Nexus Category"];
                $request_params["RegistrantNexusCountry"] = $params["additionalfields"]["Nexus Country"];
                switch ($params["additionalfields"]["Application Purpose"]) {
                    case "Business use for profit":
                        $request_params["RegistrantPurpose"] = "P1";
                        break;
                    case "Non-profit business":
                    case "Club":
                    case "Association":
                    case "Religious Organization":
                        $request_params["RegistrantPurpose"] = "P2";
                        break;
                    case "Educational purposes":
                        $request_params["RegistrantPurpose"] = "P4";
                        break;
                    case "Government purposes":
                        $request_params["RegistrantPurpose"] = "P5";
                        break;
                    case "Personal Use":
                    default:
                        $request_params["RegistrantPurpose"] = "P3";
                        break;
                }
            } else {
                if ("ca" == strtolower($tld)) {
                    $request_params["CIRAWhoisDisplay"] = "on" == $params["additionalfields"]["WHOIS Opt-out"] ? "Private" : "Full";
                    $request_params["CIRAAgreementVersion"] = "2.0";
                    $request_params["CIRAAgreementValue"] = "on" == $params["additionalfields"]["CIRA Agreement"] ? "Y" : "";
                    $request_params["CIRALanguage"] = "en";
                    if (!empty($params["additionalfields"]["jobTitle"])) {
                        $jobTitle = $params["additionalfields"]["jobTitle"];
                    } else {
                        if (!empty($params["additionalfields"]["Job Title"])) {
                            $jobTitle = $params["additionalfields"]["Job Title"];
                        } else {
                            $jobTitle = "Director";
                        }
                    }
                    $request_params["RegistrantJobTitle"] = $jobTitle;
                    $request_params["AdminJobTitle"] = $jobTitle;
                    $request_params["TechJobTitle"] = $jobTitle;
                    $request_params["AuxBillingJobTitle"] = $jobTitle;
                    switch ($params["additionalfields"]["Legal Type"]) {
                        case "Corporation":
                            $request_params["CIRALegalType"] = "CCO";
                            break;
                        case "Permanent Resident of Canada":
                            $request_params["CIRALegalType"] = "RES";
                            break;
                        case "Government":
                            $request_params["CIRALegalType"] = "GOV";
                            break;
                        case "Canadian Educational Institution":
                            $request_params["CIRALegalType"] = "EDU";
                            break;
                        case "Canadian Unincorporated Association":
                            $request_params["CIRALegalType"] = "ASS";
                            break;
                        case "Canadian Hospital":
                            $request_params["CIRALegalType"] = "HOP";
                            break;
                        case "Partnership Registered in Canada":
                            $request_params["CIRALegalType"] = "PRT";
                            break;
                        case "Trade-mark registered in Canada":
                            $request_params["CIRALegalType"] = "TDM";
                            break;
                        case "Canadian Trade Union":
                            $request_params["CIRALegalType"] = "TRD";
                            break;
                        case "Canadian Political Party":
                            $request_params["CIRALegalType"] = "PLT";
                            break;
                        case "Canadian Library Archive or Museum":
                            $request_params["CIRALegalType"] = "LAM";
                            break;
                        case "Trust established in Canada":
                            $request_params["CIRALegalType"] = "TRS";
                            break;
                        case "Aboriginal Peoples":
                            $request_params["CIRALegalType"] = "ABO";
                            break;
                        case "Legal Representative of a Canadian Citizen":
                            $request_params["CIRALegalType"] = "LGR";
                            break;
                        case "Official mark registered in Canada":
                            $request_params["CIRALegalType"] = "OMK";
                            break;
                        case "Canadian Citizen":
                        default:
                            $request_params["CIRALegalType"] = "CCT";
                            break;
                    }
                } else {
                    if ("co.uk" == strtolower($tld) || "org.uk" == strtolower($tld) || "me.uk" == strtolower($tld) || "uk" == strtolower($tld)) {
                        $key = strtoupper(str_replace(".", "", $tld));
                        $request_params[$key . "CompanyID"] = $params["additionalfields"]["Company ID Number"];
                        $request_params[$key . "Registeredfor"] = $params["additionalfields"]["Registrant Name"];
                        switch ($params["additionalfields"]["Legal Type"]) {
                            case "UK Limited Company":
                                $request_params[$key . "LegalType"] = "LTD";
                                break;
                            case "UK Public Limited Company":
                                $request_params[$key . "LegalType"] = "PLC";
                                break;
                            case "UK Partnership":
                                $request_params[$key . "LegalType"] = "PTNR";
                                break;
                            case "UK Limited Liability Partnership":
                                $request_params[$key . "LegalType"] = "LLP";
                                break;
                            case "Sole Trader":
                                $request_params[$key . "LegalType"] = "STRA";
                                break;
                            case "UK Registered Charity":
                                $request_params[$key . "LegalType"] = "RCHAR";
                                break;
                            case "UK Entity (other)":
                                $request_params[$key . "LegalType"] = "OTHER";
                                break;
                            case "Foreign Organization":
                                $request_params[$key . "LegalType"] = "FCORP";
                                break;
                            case "Other foreign organizations":
                                $request_params[$key . "LegalType"] = "FOTHER";
                                break;
                            case "UK Industrial/Provident Registered Company":
                                $request_params[$key . "LegalType"] = "IP";
                                break;
                            case "UK School":
                                $request_params[$key . "LegalType"] = "SCH";
                                break;
                            case "UK Government Body":
                                $request_params[$key . "LegalType"] = "GOV";
                                break;
                            case "UK Corporation by Royal Charter":
                                $request_params[$key . "LegalType"] = "CRC";
                                break;
                            case "UK Statutory Body":
                                $request_params[$key . "LegalType"] = "STAT";
                                break;
                            case "Non-UK Individual":
                                $request_params[$key . "LegalType"] = "FIND";
                                break;
                            case "Individual":
                            default:
                                $request_params[$key . "LegalType"] = "IND";
                                break;
                        }
                    } else {
                        if ("de" == strtolower($tld)) {
                            $request_params["DEConfirmAddress"] = "DE";
                            $request_params["DEAgreeDelete"] = "Yes";
                        } else {
                            if ("asia" == strtolower($tld)) {
                                $request_params["ASIACCLocality"] = $params["additionalfields"]["Locality"];
                                $request_params["ASIALegalEntityType"] = $params["additionalfields"]["Legal Type"];
                                $request_params["ASIAIdentForm"] = $params["additionalfields"]["Identity Form"];
                                $request_params["ASIAIdentNumber"] = $params["additionalfields"]["Identity Number"];
                            } else {
                                if ("sg" == strtolower($tld)) {
                                    $request_params["SGRCBID"] = $params["additionalfields"]["RCB Singapore ID"];
                                } else {
                                    if ("com.sg" == strtolower($tld)) {
                                        $request_params["COMSGRCBID"] = $params["additionalfields"]["RCB Singapore ID"];
                                    } else {
                                        if ("com.au" == strtolower($tld) || "net.au" == strtolower($tld) || "org.au" == strtolower($tld)) {
                                            $key_prefix = strtoupper(str_replace(".", "", $tld));
                                            $request_params[$key_prefix . "RegistrantId"] = $params["additionalfields"]["Registrant ID"];
                                            if ("Business Registration Number" == $params["additionalfields"]["Registrant ID Type"]) {
                                                $params["additionalfields"]["Registrant ID Type"] = "RBN";
                                            }
                                            $request_params[$key_prefix . "RegistrantIdType"] = $params["additionalfields"]["Registrant ID Type"];
                                            if (!empty($params["additionalfields"]["jobTitle"])) {
                                                $jobTitle = $params["additionalfields"]["jobTitle"];
                                            } else {
                                                if (!empty($params["additionalfields"]["Job Title"])) {
                                                    $jobTitle = $params["additionalfields"]["Job Title"];
                                                } else {
                                                    $jobTitle = "Director";
                                                }
                                            }
                                            $request_params["RegistrantJobTitle"] = $jobTitle;
                                            $request_params["AdminJobTitle"] = $jobTitle;
                                            $request_params["TechJobTitle"] = $jobTitle;
                                            $request_params["AuxBillingJobTitle"] = $jobTitle;
                                        } else {
                                            if ("es" == strtolower($tld) || "com.es" == strtolower($tld) || "nom.es" == strtolower($tld) || "org.es" == strtolower($tld)) {
                                                $key_prefix = strtoupper(str_replace(".", "", $tld));
                                                $request_params[$key_prefix . "RegistrantId"] = $params["additionalfields"]["ID Form Number"];
                                            } else {
                                                if ("fr" == strtolower($tld)) {
                                                    if (!empty($params["additionalfields"]["Legal Type"])) {
                                                        $request_params["FRLegalType"] = $params["additionalfields"]["Legal Type"];
                                                    }
                                                    if (!empty($params["additionalfields"]["Date of Birth"])) {
                                                        $request_params["FRRegistrantBirthDate"] = $params["additionalfields"]["Date of Birth"];
                                                    }
                                                    if (!empty($params["additionalfields"]["Place of Birth"])) {
                                                        $request_params["FRRegistrantBirthPlace"] = $params["additionalfields"]["Place of Birth"];
                                                    }
                                                    if (!empty($params["additionalfields"]["Legal Id"])) {
                                                        $request_params["FRRegistrantLegalId"] = $params["additionalfields"]["Legal Id"];
                                                    }
                                                    if (!empty($params["additionalfields"]["Trade Number"])) {
                                                        $request_params["FRRegistrantTradeNumber"] = $params["additionalfields"]["Trade Number"];
                                                    }
                                                    if (!empty($params["additionalfields"]["Duns Number"])) {
                                                        $request_params["FRRegistrantDunsNumber"] = $params["additionalfields"]["Duns Number"];
                                                    }
                                                    if (!empty($params["additionalfields"]["Local Id"])) {
                                                        $request_params["FRRegistrantLocalId"] = $params["additionalfields"]["Local Id"];
                                                    }
                                                    if (!empty($params["additionalfields"]["Journal Date of Declaration"])) {
                                                        $request_params["FRRegistrantJoDateDec"] = $params["additionalfields"]["Journal Date of Declaration"];
                                                    }
                                                    if (!empty($params["additionalfields"]["Journal Date of Publication"])) {
                                                        $request_params["FRRegistrantJoDatePub"] = $params["additionalfields"]["Journal Date of Publication"];
                                                    }
                                                    if (!empty($params["additionalfields"]["Journal Number"])) {
                                                        $request_params["FRRegistrantJoNumber"] = $params["additionalfields"]["Journal Number"];
                                                    }
                                                    if (!empty($params["additionalfields"]["Journal Page"])) {
                                                        $request_params["FRRegistrantJoPage"] = $params["additionalfields"]["Journal Page"];
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
        $api = new NamecheapRegistrarApi($username, $password, $testmode);
        $response = $api->request("namecheap.domains.create", $request_params);
        $result = $api->parseResponse($response);
        if (isset($result["DomainCreateResult"]["warnings"]["Warning"])) {
            $message = "Registering Domain warning<br />" . "-----------------------------------------------------------------------------------------<br />" . $result["DomainCreateResult"]["warnings"]["Warning"]["@value"] . "<br />" . "-----------------------------------------------------------------------------------------<br />" . "Domain: " . $tld . "." . $sld . "<br />" . "Nameservers: " . implode(",", $nameservers);
            sendAdminNotification("system", "WHMCS Namecheap Domain Registrar Module", $message);
        }
    } catch (Exception $e) {
        $values["error"] = "An error occurred: " . $e->getMessage();
        logModuleCall("namecheap", "RegisterDomain", array("command" => "namecheap.domains.create") + $request_params, $response, $result, array($password));
    }
    return $values;
}
function namecheap_TransferDomain($params)
{
    require_once dirname(__FILE__) . "/namecheapapi.php";
    $testmode = (bool) $params["TestMode"];
    $username = $testmode ? $params["SandboxUsername"] : $params["Username"];
    $password = $testmode ? $params["SandboxPassword"] : $params["Password"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $oIDNA = new NamecheapRegistrarIDNA($sld, $tld);
    $sld = $oIDNA->getEncodedSld();
    $response = "";
    $result = $request_params = $values = array();
    try {
        $request_params = array("DomainName" => $sld . "." . $tld, "Years" => $params["regperiod"], "EPPCode" => $params["transfersecret"]);
        if (!empty($params["PromotionCode"])) {
            $request_params["PromotionCode"] = $params["PromotionCode"];
        }
        $wg_ex = array("bz", "ca", "cn", "co.uk", "de", "eu", "in", "me.uk", "mobi", "nu", "org.uk", "us", "ws");
        if ($params["idprotection"] && !in_array(strtolower($tld), $wg_ex)) {
            $request_params["AddFreeWhoisguard"] = "yes";
            $request_params["WGEnabled"] = "yes";
        }
        $api = new NamecheapRegistrarApi($username, $password, $testmode);
        $response = $api->request("namecheap.domains.transfer.create", $request_params);
        $result = $api->parseResponse($response);
    } catch (Exception $e) {
        $values["error"] = "An error occurred: " . $e->getMessage();
        logModuleCall("namecheap", "TransferDomain", array("command" => "namecheap.domains.transfer.create") + $request_params, $response, $result, array($password));
    }
    return $values;
}
function namecheap_RenewDomain($params)
{
    require_once dirname(__FILE__) . "/namecheapapi.php";
    $testmode = (bool) $params["TestMode"];
    $username = $testmode ? $params["SandboxUsername"] : $params["Username"];
    $password = $testmode ? $params["SandboxPassword"] : $params["Password"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $oIDNA = new NamecheapRegistrarIDNA($sld, $tld);
    $sld = $oIDNA->getEncodedSld();
    $response = "";
    $result = $request_params = $values = array();
    $exCode = 0;
    try {
        $request_params = array("DomainName" => $sld . "." . $tld, "Years" => $params["regperiod"]);
        if (!empty($params["PromotionCode"])) {
            $request_params["PromotionCode"] = $params["PromotionCode"];
        }
        $api = new NamecheapRegistrarApi($username, $password, $testmode);
        $response = $api->request("namecheap.domains.renew", $request_params);
        $result = $api->parseResponse($response);
        $values["status"] = "Domain Renewed";
    } catch (Exception $e) {
        $exCode = $e->getCode();
        $values["error"] = "An error occurred: " . $e->getMessage();
        logModuleCall("namecheap", "RenewDomain", array("command" => "namecheap.domains.renew") + $request_params, $response, $result, array($password));
    }
    if ($exCode != 2020166) {
        return $values;
    }
    try {
        unset($values["error"]);
        unset($request_params["Years"]);
        $api = new NamecheapRegistrarApi($username, $password, $testmode);
        $response = $api->request("namecheap.domains.reactivate", $request_params);
        $result = $api->parseResponse($response);
        $values["status"] = "Domain Reactivated";
    } catch (Exception $e) {
        $values["error"] = "An error occurred: " . $e->getMessage();
        logModuleCall("namecheap", "ReactivateDomain", array("command" => "namecheap.domains.reactivate") + $request_params, $response, $result, array($password));
    }
    return $values;
}
function namecheap_GetContactDetails($params)
{
    require_once dirname(__FILE__) . "/namecheapapi.php";
    $testmode = (bool) $params["TestMode"];
    $username = $testmode ? $params["SandboxUsername"] : $params["Username"];
    $password = $testmode ? $params["SandboxPassword"] : $params["Password"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $oIDNA = new NamecheapRegistrarIDNA($sld, $tld);
    $sld = $oIDNA->getEncodedSld();
    $response = "";
    $result = $request_params = $values = array();
    try {
        $request_params = array("DomainName" => $sld . "." . $tld);
        $api = new NamecheapRegistrarApi($username, $password, $testmode);
        $response = $api->request("namecheap.domains.getContacts", $request_params);
        $result = $api->parseResponse($response);
        $values = array();
        foreach ($result["DomainContactsResult"] as $k => $v) {
            if (!in_array($k, array("Registrant", "Admin", "Tech", "AuxBilling"))) {
                continue;
            }
            $values[$k]["First Name"] = $v["FirstName"];
            $values[$k]["Last Name"] = $v["LastName"];
            $values[$k]["Organization Name"] = $v["OrganizationName"];
            $values[$k]["Address"] = $v["Address1"];
            $values[$k]["Address1"] = $v["Address2"];
            $values[$k]["City"] = $v["City"];
            $values[$k]["State"] = $v["StateProvince"];
            $values[$k]["Postcode"] = $v["PostalCode"];
            $values[$k]["Country"] = $v["Country"];
            $values[$k]["Phone"] = $v["Phone"];
            $values[$k]["Fax"] = $v["Fax"];
            $values[$k]["Email"] = $v["EmailAddress"];
        }
    } catch (Exception $e) {
        $values["error"] = "An error occurred: " . $e->getMessage();
        logModuleCall("namecheap", "GetContactDetails", array("command" => "namecheap.domains.getContacts") + $request_params, $response, $result, array($password));
    }
    return $values;
}
function namecheap_SaveContactDetails($params)
{
    require_once dirname(__FILE__) . "/namecheapapi.php";
    $testmode = (bool) $params["TestMode"];
    $username = $testmode ? $params["SandboxUsername"] : $params["Username"];
    $password = $testmode ? $params["SandboxPassword"] : $params["Password"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $oIDNA = new NamecheapRegistrarIDNA($sld, $tld);
    $sld = $oIDNA->getEncodedSld();
    $response = "";
    $result = $request_params = $values = array();
    try {
        $request_params = array("DomainName" => $sld . "." . $tld);
        foreach ($params["contactdetails"] as $k => $v) {
            if (in_array($k, array("Registrant", "Admin", "Tech", "AuxBilling"))) {
                $request_params[$k . "FirstName"] = $v["First Name"];
                $request_params[$k . "LastName"] = $v["Last Name"];
                $request_params[$k . "OrganizationName"] = $v["Organization Name"];
                $request_params[$k . "Address1"] = $v["Address"];
                $request_params[$k . "Address2"] = $v["Address1"];
                $request_params[$k . "City"] = $v["City"];
                $request_params[$k . "StateProvince"] = $v["State"];
                $request_params[$k . "PostalCode"] = $v["Postcode"];
                $request_params[$k . "Country"] = $v["Country"];
                $request_params[$k . "Phone"] = $v["Phone"];
                $request_params[$k . "Fax"] = !empty($v["Fax"]) ? $v["Fax"] : $v["Phone"];
                $request_params[$k . "EmailAddress"] = $v["Email"];
            }
        }
        $api = new NamecheapRegistrarApi($username, $password, $testmode);
        $response = $api->request("namecheap.domains.setContacts", $request_params);
        $result = $api->parseResponse($response);
        if (isset($result["DomainSetContactResult"]["Warnings"]["Warning"])) {
            $message = "Saving Contact Details warning<br />" . "-----------------------------------------------------------------------------------------<br />" . $result["DomainSetContactResult"]["Warnings"]["Warning"]["@value"] . "<br /" . "-----------------------------------------------------------------------------------------<br />" . "Domain: " . $sld . "." . $tld;
            sendAdminNotification("system", "WHMCS Namecheap Domain Registrar Module", $message);
        }
    } catch (Exception $e) {
        $values["error"] = "An error occurred: " . $e->getMessage();
        logModuleCall("namecheap", "SaveContactDetails", array("command" => "namecheap.domains.setContacts") + $request_params, $response, $result, array($password));
    }
    return $values;
}
function namecheap_RegisterNameserver($params)
{
    require_once dirname(__FILE__) . "/namecheapapi.php";
    $testmode = (bool) $params["TestMode"];
    $username = $testmode ? $params["SandboxUsername"] : $params["Username"];
    $password = $testmode ? $params["SandboxPassword"] : $params["Password"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $oIDNA = new NamecheapRegistrarIDNA($sld, $tld);
    $sld = $oIDNA->getEncodedSld();
    $response = "";
    $result = $request_params = $values = array();
    try {
        $request_params = array("SLD" => $sld, "TLD" => $tld, "Nameserver" => $params["nameserver"], "IP" => $params["ipaddress"]);
        $api = new NamecheapRegistrarApi($username, $password, $testmode);
        $response = $api->request("namecheap.domains.ns.create", $request_params);
        $result = $api->parseResponse($response);
    } catch (Exception $e) {
        $values["error"] = "An error occurred: " . $e->getMessage();
        logModuleCall("namecheap", "RegisterNameserver", array("command" => "namecheap.domains.ns.create") + $request_params, $response, $result, array($password));
    }
    return $values;
}
function namecheap_ModifyNameserver($params)
{
    require_once dirname(__FILE__) . "/namecheapapi.php";
    $testmode = (bool) $params["TestMode"];
    $username = $testmode ? $params["SandboxUsername"] : $params["Username"];
    $password = $testmode ? $params["SandboxPassword"] : $params["Password"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $oIDNA = new NamecheapRegistrarIDNA($sld, $tld);
    $sld = $oIDNA->getEncodedSld();
    $response = "";
    $result = $request_params = $values = array();
    try {
        $request_params = array("SLD" => $sld, "TLD" => $tld, "Nameserver" => $params["nameserver"], "IP" => $params["newipaddress"], "OldIP" => $params["currentipaddress"]);
        $api = new NamecheapRegistrarApi($username, $password, $testmode);
        $response = $api->request("namecheap.domains.ns.update", $request_params);
        $result = $api->parseResponse($response);
    } catch (Exception $e) {
        $values["error"] = "An error occurred: " . $e->getMessage();
        logModuleCall("namecheap", "ModifyNameserver", array("command" => "namecheap.domains.ns.update") + $request_params, $response, $result, array($password));
    }
    return $values;
}
function namecheap_DeleteNameserver($params)
{
    require_once dirname(__FILE__) . "/namecheapapi.php";
    $testmode = (bool) $params["TestMode"];
    $username = $testmode ? $params["SandboxUsername"] : $params["Username"];
    $password = $testmode ? $params["SandboxPassword"] : $params["Password"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $oIDNA = new NamecheapRegistrarIDNA($sld, $tld);
    $sld = $oIDNA->getEncodedSld();
    $response = "";
    $result = $request_params = $values = array();
    try {
        $request_params = array("SLD" => $sld, "TLD" => $tld, "Nameserver" => $params["nameserver"]);
        $api = new NamecheapRegistrarApi($username, $password, $testmode);
        $response = $api->request("namecheap.domains.ns.delete", $request_params);
        $result = $api->parseResponse($response);
    } catch (Exception $e) {
        $values["error"] = "An error occurred: " . $e->getMessage();
        logModuleCall("namecheap", "DeleteNameserver", array("command" => "namecheap.domains.ns.delete") + $request_params, $response, $result, array($password));
    }
    return $values;
}
function namecheap_Sync($params)
{
    require_once dirname(__FILE__) . "/namecheapapi.php";
    $testmode = (bool) $params["TestMode"];
    $username = $testmode ? $params["SandboxUsername"] : $params["Username"];
    $password = $testmode ? $params["SandboxPassword"] : $params["Password"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $oIDNA = new NamecheapRegistrarIDNA($sld, $tld);
    $sld = $oIDNA->getEncodedSld();
    $values = array();
    try {
        $request_params = array("ListType" => "ALL", "Page" => 1, "PageSize" => 10, "SortBy" => "NAME", "SearchTerm" => (string) $sld . "." . $tld);
        if (!empty($params["PromotionCode"])) {
            $request_params["PromotionCode"] = $params["PromotionCode"];
        }
        $api = new NamecheapRegistrarApi($username, $password, $testmode);
        $response = $api->request("namecheap.domains.getList", $request_params);
        $result = $api->parseResponse($response);
        $domains = $api->parseResultSyncHelper($result["DomainGetListResult"]["Domain"], "Name");
    } catch (Exception $e) {
        $values["error"] = $e->getMessage();
        return $values;
    }
    if (empty($domains[(string) $sld . "." . $tld])) {
        $values["error"] = "Domain not found";
        return $values;
    }
    $values["expired"] = "true" === strtolower($domains[(string) $sld . "." . $tld]["IsExpired"]);
    $values["expirydate"] = date("Y-m-d", strtotime($domains[(string) $sld . "." . $tld]["Expires"]));
    return $values;
}
function namecheap_TransferSync($params)
{
    require_once dirname(__FILE__) . "/namecheapapi.php";
    $testmode = (bool) $params["TestMode"];
    $username = $testmode ? $params["SandboxUsername"] : $params["Username"];
    $password = $testmode ? $params["SandboxPassword"] : $params["Password"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $oIDNA = new NamecheapRegistrarIDNA($sld, $tld);
    $sld = $oIDNA->getEncodedSld();
    try {
        $request_params = array("ListType" => "ALL", "Page" => 1, "PageSize" => 10, "SortBy" => "DOMAINNAME", "SearchTerm" => (string) $sld . "." . $tld);
        if (!empty($params["PromotionCode"])) {
            $request_params["PromotionCode"] = $params["PromotionCode"];
        }
        $api = new NamecheapRegistrarApi($username, $password, $testmode);
        $response = $api->request("namecheap.domains.transfer.getList", $request_params);
        $result = $api->parseResponse($response);
        $domains = $api->parseResultSyncHelper($result["TransferGetListResult"]["Transfer"], "DomainName");
    } catch (Exception $e) {
        $values["error"] = $e->getMessage();
        return $values;
    }
    if (empty($domains[(string) $sld . "." . $tld])) {
        $values["error"] = "Domain not found";
        return $values;
    }
    if ("completed" === strtolower($domains[(string) $sld . "." . $tld]["Status"])) {
        $values["completed"] = true;
    } else {
        $values["error"] = $domains[(string) $sld . "." . $tld]["StatusDescription"];
    }
    return $values;
}

?>