<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

global $server_ip;
$server_ip = isset($_SERVER["SERVER_ADDR"]) ? $_SERVER["SERVER_ADDR"] : $_SERVER["LOCAL_ADDR"];
function opensrs_getConfigArray()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "OpenSRS"), "Username" => array("Type" => "text", "Size" => "20", "Description" => "Enter your Reseller Account Username here"), "PrivateKey" => array("Type" => "text", "Size" => "80", "Description" => "Enter your Private Key here"), "TestMode" => array("Type" => "yesno"));
    if (!class_exists("SoapClient")) {
        $configarray["Description"] = array("Type" => "System", "Value" => "This module requires the PHP SOAP extension which is not currently compiled into your PHP build.");
    }
    return $configarray;
}
function opensrs_GetNameservers($params, openSRS_base $O = NULL, $moduleName = "opensrs")
{
    if (is_null($O) || !$O instanceof openSRS_base) {
        try {
            $O = opensrs_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
        } catch (Exception $e) {
            return array("error" => $e->getMessage());
        }
    }
    global $server_ip;
    $cmd = array("action" => "get", "object" => "domain", "registrant_ip" => $server_ip, "attributes" => array("domain" => strtolower($params["sld"] . "." . $params["tld"]), "type" => "nameservers"));
    $result = $O->send_cmd($cmd);
    logModuleCall($moduleName, $cmd["action"] . " " . $cmd["object"], $cmd, $result);
    if ($result["is_success"] != "1") {
        $values["error"] = $result["response_text"];
        if (!$values["error"]) {
            $values["error"] = "API Connection Failure. Please open ports 55443 and 55000 in your servers firewall.";
        }
    } else {
        $nameserver1 = $result["attributes"]["nameserver_list"][0]["name"];
        $nameserver2 = $result["attributes"]["nameserver_list"][1]["name"];
        $nameserver3 = $result["attributes"]["nameserver_list"][2]["name"];
        $nameserver4 = $result["attributes"]["nameserver_list"][3]["name"];
        $nameserver5 = $result["attributes"]["nameserver_list"][4]["name"];
        $values["ns1"] = $nameserver1;
        $values["ns2"] = $nameserver2;
        $values["ns3"] = $nameserver3;
        $values["ns4"] = $nameserver4;
        $values["ns5"] = $nameserver5;
    }
    return $values;
}
function opensrs_SaveNameservers($params, openSRS_base $O = NULL, $moduleName = "opensrs")
{
    if (is_null($O) || !$O instanceof openSRS_base) {
        try {
            $O = opensrs_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
        } catch (Exception $e) {
            return array("error" => $e->getMessage());
        }
    }
    global $server_ip;
    $nameserverslist[] = $params["ns1"];
    $nameserverslist[] = $params["ns2"];
    if ($params["ns3"]) {
        $nameserverslist[] = $params["ns3"];
    }
    if ($params["ns4"]) {
        $nameserverslist[] = $params["ns4"];
    }
    if ($params["ns5"]) {
        $nameserverslist[] = $params["ns5"];
    }
    $cmd = array("action" => "advanced_update_nameservers", "object" => "domain", "registrant_ip" => $server_ip, "attributes" => array("domain" => strtolower($params["sld"] . "." . $params["tld"]), "op_type" => "assign", "assign_ns" => $nameserverslist));
    $result = $O->send_cmd($cmd);
    logModuleCall($moduleName, $cmd["action"] . " " . $cmd["object"], $cmd, $result);
    if ($result["is_success"] != "1") {
        $values["error"] = $result["response_text"];
        if (!$values["error"]) {
            $values["error"] = "API Connection Failure. Please open ports 55443 and 55000 in your servers firewall.";
        }
    }
    return $values;
}
function opensrs_GetRegistrarLock($params, openSRS_base $O = NULL, $moduleName = "opensrs")
{
    if (is_null($O) || !$O instanceof openSRS_base) {
        try {
            $O = opensrs_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
        } catch (Exception $e) {
            return array("error" => $e->getMessage());
        }
    }
    global $server_ip;
    $cmd = array("action" => "get", "object" => "domain", "registrant_ip" => $server_ip, "attributes" => array("domain" => strtolower($params["sld"] . "." . $params["tld"]), "type" => "status"));
    $result = $O->send_cmd($cmd);
    logModuleCall($moduleName, $cmd["action"] . " " . $cmd["object"], $cmd, $result);
    $lockstate = $result["attributes"]["lock_state"];
    if ($lockstate == "1") {
        $lockstate = "locked";
    } else {
        $lockstate = "unlocked";
    }
    return $lockstate;
}
function opensrs_SaveRegistrarLock($params, openSRS_base $O = NULL, $moduleName = "opensrs")
{
    if (is_null($O) || !$O instanceof openSRS_base) {
        try {
            $O = opensrs_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
        } catch (Exception $e) {
            return array("error" => $e->getMessage());
        }
    }
    if ($params["lockenabled"] == "locked") {
        $lockstate = "1";
    } else {
        $lockstate = "0";
    }
    $cmd = array("action" => "modify", "object" => "domain", "attributes" => array("domain" => strtolower($params["sld"] . "." . $params["tld"]), "data" => "status", "lock_state" => $lockstate));
    $result = $O->send_cmd($cmd);
    logModuleCall($moduleName, $cmd["action"] . " " . $cmd["object"], $cmd, $result);
}
function opensrs_RegisterDomain($params, openSRS_base $O = NULL, $moduleName = "opensrs")
{
    global $server_ip;
    $legaltype = $params["additionalfields"]["Legal Type"];
    $regname = $params["additionalfields"]["Registrant Name"];
    $trademarknum = $params["additionalfields"]["Trademark Number"];
    if ($trademarknum) {
        $isatrademark = "1";
    } else {
        $isatrademark = "0";
    }
    if (preg_match("/uk\$/i", $params["tld"])) {
        if ($params["additionalfields"]["Legal Type"] == "UK Limited Company") {
            $legaltype = "LTD";
        } else {
            if ($params["additionalfields"]["Legal Type"] == "UK Public Limited Company") {
                $legaltype = "PLC";
            } else {
                if ($params["additionalfields"]["Legal Type"] == "UK Partnership") {
                    $legaltype = "PTNR";
                } else {
                    if ($params["additionalfields"]["Legal Type"] == "UK Limited Liability Partnership") {
                        $legaltype = "LLP";
                    } else {
                        if ($params["additionalfields"]["Legal Type"] == "Sole Trader") {
                            $legaltype = "STRA";
                        } else {
                            if ($params["additionalfields"]["Legal Type"] == "UK Registered Charity") {
                                $legaltype = "RCHAR";
                            } else {
                                if ($params["additionalfields"]["Legal Type"] == "UK Industrial/Provident Registered Company") {
                                    $legaltype = "IP";
                                } else {
                                    if ($params["additionalfields"]["Legal Type"] == "UK School") {
                                        $legaltype = "SCH";
                                    } else {
                                        if ($params["additionalfields"]["Legal Type"] == "UK Government Body") {
                                            $legaltype = "GOV";
                                        } else {
                                            if ($params["additionalfields"]["Legal Type"] == "UK Corporation by Royal Charter") {
                                                $legaltype = "CRC";
                                            } else {
                                                if ($params["additionalfields"]["Legal Type"] == "UK Statutory Body") {
                                                    $legaltype = "STAT";
                                                } else {
                                                    if ($params["additionalfields"]["Legal Type"] == "Non-UK Individual") {
                                                        $legaltype = "FIND";
                                                    } else {
                                                        $legaltype = "IND";
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
    } else {
        if (preg_match("/ca\$/i", $params["tld"])) {
            if ($legaltype == "Corporation") {
                $legaltype = "CCO";
            } else {
                if ($legaltype == "Canadian Citizen") {
                    $legaltype = "CCT";
                } else {
                    if ($legaltype == "Government") {
                        $legaltype = "GOV";
                    } else {
                        if ($legaltype == "Canadian Educational Institution") {
                            $legaltype = "EDU";
                        } else {
                            if ($legaltype == "Canadian Unincorporated Association") {
                                $legaltype = "ASS";
                            } else {
                                if ($legaltype == "Canadian Hospital") {
                                    $legaltype = "HOP";
                                } else {
                                    if ($legaltype == "Partnership Registered in Canada") {
                                        $legaltype = "PRT";
                                    } else {
                                        if ($legaltype == "Trade-mark registered in Canada") {
                                            $legaltype = "TDM";
                                        } else {
                                            $legaltype = "CCT";
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } else {
            if (preg_match("/de\$/i", $params["tld"])) {
                $params["admincountry"] = "DE";
            }
        }
    }
    if (is_null($O) || !$O instanceof openSRS_base) {
        try {
            $O = opensrs_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
        } catch (Exception $e) {
            return array("error" => $e->getMessage());
        }
    }
    $domain = strtolower($params["sld"] . "." . $params["tld"]);
    $f_whois_privacy = $params["idprotection"] ? "1" : "0";
    if (!$params["companyname"]) {
        $params["companyname"] = "None";
    }
    if (!$params["admincompanyname"]) {
        $params["admincompanyname"] = "None";
    }
    $nameserverslist = array();
    $nameserverslist[] = array("sortorder" => "1", "name" => $params["ns1"]);
    $nameserverslist[] = array("sortorder" => "2", "name" => $params["ns2"]);
    if ($params["ns3"]) {
        $nameserverslist[] = array("sortorder" => "3", "name" => $params["ns3"]);
    }
    if ($params["ns4"]) {
        $nameserverslist[] = array("sortorder" => "4", "name" => $params["ns4"]);
    }
    if ($params["ns5"]) {
        $nameserverslist[] = array("sortorder" => "5", "name" => $params["ns5"]);
    }
    if (!mysql_num_rows(full_query("SHOW TABLES LIKE 'mod_opensrs'"))) {
        $query = "CREATE TABLE `mod_opensrs` (`domain` TEXT NOT NULL ,`username` TEXT NOT NULL ,`password` TEXT NOT NULL)";
        $result = full_query($query);
    }
    $opensrsusername = opensrs_getusername($params["sld"] . "." . $params["tld"]);
    $opensrspassword = substr(sha1($params["domainid"] . mt_rand(1000000, 9999999)), 0, 10);
    $attributes = array("f_lock_domain" => "1", "domain" => $domain, "period" => $params["regperiod"], "reg_type" => "new", "reg_username" => $opensrsusername, "reg_password" => $opensrspassword, "custom_tech_contact" => "1", "legal_type" => $legaltype, "isa_trademark" => $isatrademark, "lang_pref" => "EN", "link_domains" => "0", "custom_nameservers" => "1", "f_whois_privacy" => $f_whois_privacy, "nameserver_list" => $nameserverslist, "contact_set" => array("admin" => array("first_name" => $params["adminfirstname"], "state" => $params["adminstate"], "country" => $params["admincountry"], "address1" => $params["adminaddress1"], "address2" => $params["adminaddress2"], "last_name" => $params["adminlastname"], "address3" => "", "city" => $params["admincity"], "fax" => $params["additionalfields"]["Fax Number"], "postal_code" => $params["adminpostcode"], "email" => $params["adminemail"], "phone" => $params["adminfullphonenumber"], "org_name" => $params["admincompanyname"], "lang_pref" => "EN"), "billing" => array("first_name" => $params["adminfirstname"], "state" => $params["adminstate"], "country" => $params["admincountry"], "address1" => $params["adminaddress1"], "address2" => $params["adminaddress2"], "last_name" => $params["adminlastname"], "address3" => "", "city" => $params["admincity"], "fax" => $params["additionalfields"]["Fax Number"], "postal_code" => $params["adminpostcode"], "email" => $params["adminemail"], "phone" => $params["adminfullphonenumber"], "org_name" => $params["admincompanyname"], "lang_pref" => "EN"), "tech" => array("first_name" => $params["adminfirstname"], "state" => $params["adminstate"], "country" => $params["admincountry"], "address1" => $params["adminaddress1"], "address2" => $params["adminaddress2"], "last_name" => $params["adminlastname"], "address3" => "", "city" => $params["admincity"], "fax" => $params["additionalfields"]["Fax Number"], "postal_code" => $params["adminpostcode"], "email" => $params["adminemail"], "phone" => $params["adminfullphonenumber"], "org_name" => $params["admincompanyname"], "lang_pref" => "EN"), "owner" => array("first_name" => $params["firstname"], "state" => $params["state"], "country" => $params["country"], "address1" => $params["address1"], "address2" => $params["address2"], "last_name" => $params["lastname"], "address3" => "", "city" => $params["city"], "fax" => $params["additionalfields"]["Fax Number"], "postal_code" => $params["postcode"], "email" => $params["email"], "phone" => $params["fullphonenumber"], "org_name" => $params["companyname"], "lang_pref" => "EN")));
    if (preg_match("/au\$/i", $params["tld"])) {
        $eligibilityType = $params["additionalfields"]["Eligibility ID Type"];
        switch ($eligibilityType) {
            case "Australian Company Number (ACN)":
                $eligibilityType = "ACN";
                break;
            case "ACT Business Number":
            case "Australian Business Number (ABN)":
                $eligibilityType = "ABN";
                break;
            case "NSW Business Number":
                $eligibilityType = "NSW BN";
                break;
            case "NT Business Number":
                $eligibilityType = "NT BN";
                break;
            case "QLD Business Number":
                $eligibilityType = "QLD BN";
                break;
            case "SA Business Number":
                $eligibilityType = "SA BN";
                break;
            case "TAS Business Number":
                $eligibilityType = "TAS BN";
                break;
            case "VIC Business Number":
                $eligibilityType = "VIC BN";
                break;
            case "WA Business Number":
                $eligibilityType = "WA BN";
                break;
            case "Trademark (TM)":
                $eligibilityType = "TM";
                break;
            default:
                $eligibilityType = "OTHER";
        }
        $attributes["tld_data"]["au_registrant_info"]["eligibility_id"] = $params["additionalfields"]["Eligibility ID"];
        $attributes["tld_data"]["au_registrant_info"]["eligibility_id_type"] = $eligibilityType;
        $attributes["tld_data"]["au_registrant_info"]["eligibility_name"] = $params["additionalfields"]["Eligibility Name"];
        $attributes["tld_data"]["au_registrant_info"]["eligibility_type"] = $params["additionalfields"]["Eligibility Type"];
        $attributes["tld_data"]["au_registrant_info"]["registrant_name"] = $params["additionalfields"]["Registrant Name"];
    } else {
        if (preg_match("/us\$/i", $params["tld"])) {
            $purpose = $params["additionalfields"]["Application Purpose"];
            if ($purpose == "Business use for profit") {
                $purpose = "P1";
            } else {
                if ($purpose == "Non-profit business") {
                    $purpose = "P2";
                } else {
                    if ($purpose == "Club") {
                        $purpose = "P2";
                    } else {
                        if ($purpose == "Association") {
                            $purpose = "P2";
                        } else {
                            if ($purpose == "Religious Organization") {
                                $purpose = "P2";
                            } else {
                                if ($purpose == "Personal Use") {
                                    $purpose = "P3";
                                } else {
                                    if ($purpose == "Educational purposes") {
                                        $purpose = "P4";
                                    } else {
                                        if ($purpose == "Government purposes") {
                                            $purpose = "P5";
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
            $attributes["tld_data"] = array("nexus" => array("category" => $params["additionalfields"]["Nexus Category"], "app_purpose" => $purpose));
        } else {
            if (preg_match("/it\$/i", $params["tld"])) {
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
                $attributes["tld_data"] = array("it_registrant_info" => array("nationality_code" => $params["country"], "reg_code" => $params["additionalfields"]["Tax ID"], "entity_type" => $entityNumber));
            } else {
                if (preg_match("/pro\$/i", $params["tld"])) {
                    $attributes["tld_data"] = array("professional_data" => array("profession" => $params["additionalfields"]["Profession"], "license_number" => $params["additionalfields"]["License Number"], "authority" => $params["additionalfields"]["Authority"], "authority_website" => $params["additionalfields"]["Authority Website"]));
                } else {
                    if (preg_match("/fr\$/i", $params["tld"])) {
                        $frArr = array();
                        if ($params["additionalfields"]["Legal Type"] == "Individual") {
                            if (empty($params["additionalfields"]["Birthplace Country"])) {
                                $birthCountry = strtoupper($params["country"]);
                            } else {
                                $birthCountry = strtoupper($params["additionalfields"]["Birthplace Country"]);
                            }
                            $frArr["registrant_type"] = "individual";
                            $frArr["country_of_birth"] = $birthCountry;
                            $frArr["date_of_birth"] = $params["additionalfields"]["Birthdate"];
                            if ($birthCountry == "FR") {
                                $frArr["place_of_birth"] = $params["additionalfields"]["Birthplace City"];
                                $frArr["postal_code_of_birth"] = $params["additionalfields"]["Birthplace Postcode"];
                            }
                        } else {
                            $frArr["registrant_type"] = "organization";
                            $frArr["registrant_vat_id"] = $params["additionalfields"]["VAT Number"];
                            $frArr["siren_siret"] = $params["additionalfields"]["SIRET Number"];
                            $frArr["trademark_number"] = $params["additionalfields"]["Trademark Number"];
                        }
                        $attributes["tld_data"]["registrant_extra_info"] = $frArr;
                    }
                }
            }
        }
    }
    $domain = strtolower($params["sld"] . "." . $params["tld"]);
    $cmd = array("action" => "SW_REGISTER", "object" => "DOMAIN", "registrant_ip" => $server_ip, "attributes" => $attributes);
    $result = $O->send_cmd($cmd);
    logModuleCall($moduleName, "Register Domain", $attributes, $result, "", array($opensrsusername, $opensrspassword));
    if ($result["is_success"] != "1") {
        $values["error"] = $result["response_text"] . " - " . $result["attributes"]["error"];
        if (!$values["error"]) {
            $values["error"] = "API Connection Failure. Please open ports 55443 and 55000 in your servers firewall.";
        }
    } else {
        delete_query("mod_opensrs", array("domain" => $domain));
        insert_query("mod_opensrs", array("domain" => $domain, "username" => $opensrsusername, "password" => $opensrspassword));
        $cmd = array("action" => "process_pending", "object" => "domain", "registrant_ip" => $server_ip, "attributes" => array("owner_address" => $result["attributes"]["admin_email"], "order_id" => $result["attributes"]["id"]));
        $result = $O->send_cmd($cmd);
        logModuleCall($moduleName, "Process Pending Domain", $cmd, $result);
    }
    if ($result["is_success"] != "1") {
        $values["error"] = $result["response_text"] . " - " . $result["attributes"]["error"];
        if (!$values["error"]) {
            $values["error"] = "API Connection Failure. Please open ports 55443 and 55000 in your servers firewall.";
        }
    }
    return $values;
}
function opensrs_TransferDomain($params, openSRS_base $O = NULL, $moduleName = "opensrs")
{
    global $server_ip;
    $params = injectDomainObjectIfNecessary($params);
    if (is_null($O) || !$O instanceof openSRS_base) {
        try {
            $O = opensrs_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
        } catch (Exception $e) {
            return array("error" => $e->getMessage());
        }
    }
    $domain = strtolower($params["sld"] . "." . $params["tld"]);
    $f_whois_privacy = $params["idprotection"] ? "1" : "0";
    if (!$params["companyname"]) {
        $params["companyname"] = "None";
    }
    if (!$params["admincompanyname"]) {
        $params["admincompanyname"] = "None";
    }
    $nameserverslist = array();
    $nameserverslist[] = array("sortorder" => "1", "name" => $params["ns1"]);
    $nameserverslist[] = array("sortorder" => "2", "name" => $params["ns2"]);
    if ($params["ns3"]) {
        $nameserverslist[] = array("sortorder" => "3", "name" => $params["ns3"]);
    }
    if ($params["ns4"]) {
        $nameserverslist[] = array("sortorder" => "4", "name" => $params["ns4"]);
    }
    if ($params["ns5"]) {
        $nameserverslist[] = array("sortorder" => "5", "name" => $params["ns5"]);
    }
    $opensrsusername = opensrs_getusername($params["sld"] . "." . $params["tld"]);
    $opensrspassword = substr(sha1($params["domainid"] . mt_rand(1000000, 9999999)), 0, 10);
    if (preg_match("/au\$/i", $params["tld"])) {
        $params["regperiod"] = "0";
    }
    if ($params["domainObj"]->getLastTLDSegment() == "es") {
        $params["regperiod"] = "0";
    }
    $domain = strtolower($params["sld"] . "." . $params["tld"]);
    $cmd = array("action" => "SW_REGISTER", "object" => "DOMAIN", "registrant_ip" => $server_ip, "attributes" => array("f_lock_domain" => "1", "domain" => $domain, "period" => $params["regperiod"], "reg_type" => "transfer", "reg_username" => $opensrsusername, "reg_password" => $opensrspassword, "custom_tech_contact" => "0", "link_domains" => "0", "custom_nameservers" => "1", "nameserver_list" => $nameserverslist, "f_whois_privacy" => $f_whois_privacy, "contact_set" => array("admin" => array("first_name" => $params["adminfirstname"], "state" => $params["adminstate"], "country" => $params["admincountry"], "address1" => $params["adminaddress1"], "address2" => $params["adminaddress2"], "last_name" => $params["adminlastname"], "address3" => "", "city" => $params["admincity"], "fax" => $params["additionalfields"]["Fax Number"], "postal_code" => $params["adminpostcode"], "email" => $params["adminemail"], "phone" => $params["adminfullphonenumber"], "org_name" => $params["admincompanyname"]), "billing" => array("first_name" => $params["adminfirstname"], "state" => $params["adminstate"], "country" => $params["admincountry"], "address1" => $params["adminaddress1"], "address2" => $params["adminaddress2"], "last_name" => $params["adminlastname"], "address3" => "", "city" => $params["admincity"], "fax" => $params["additionalfields"]["Fax Number"], "postal_code" => $params["adminpostcode"], "email" => $params["adminemail"], "phone" => $params["adminfullphonenumber"], "org_name" => $params["admincompanyname"]), "tech" => array("first_name" => $params["adminfirstname"], "state" => $params["adminstate"], "country" => $params["admincountry"], "address1" => $params["adminaddress1"], "address2" => $params["adminaddress2"], "last_name" => $params["adminlastname"], "address3" => "", "city" => $params["admincity"], "fax" => $params["additionalfields"]["Fax Number"], "postal_code" => $params["adminpostcode"], "email" => $params["adminemail"], "phone" => $params["adminfullphonenumber"], "org_name" => $params["admincompanyname"]), "owner" => array("first_name" => $params["firstname"], "state" => $params["state"], "country" => $params["country"], "address1" => $params["address1"], "address2" => $params["address2"], "last_name" => $params["lastname"], "address3" => "", "city" => $params["city"], "fax" => $params["additionalfields"]["Fax Number"], "postal_code" => $params["postcode"], "email" => $params["email"], "phone" => $params["fullphonenumber"], "org_name" => $params["companyname"]))));
    if (preg_match("/au\$/i", $params["tld"]) || preg_match("/de\$/i", $params["tld"]) || preg_match("/be\$/i", $params["tld"]) || preg_match("/eu\$/i", $params["tld"]) || preg_match("/it\$/i", $params["tld"])) {
        $cmd["attributes"]["owner_confirm_address"] = $params["email"];
    }
    $result = $O->send_cmd($cmd);
    logModuleCall($moduleName, "Transfer Domain", $cmd, $result, "", array($opensrsusername, $opensrspassword));
    if ($result["is_success"] != "1") {
        $values["error"] = $result["response_text"] . " - " . $result["attributes"]["error"];
        if (!$values["error"]) {
            $values["error"] = "API Connection Failure. Please open ports 55443 and 55000 in your servers firewall.";
        }
    } else {
        delete_query("mod_opensrs", array("domain" => $domain));
        insert_query("mod_opensrs", array("domain" => $domain, "username" => $opensrsusername, "password" => $opensrspassword));
        $cmd = array("action" => "process_pending", "object" => "domain", "registrant_ip" => $server_ip, "attributes" => array("owner_address" => $result["attributes"]["admin_email"], "order_id" => $result["attributes"]["id"]));
        $result = $O->send_cmd($cmd);
        logModuleCall($moduleName, "Process Pending Domain Transfer", $cmd, $result);
    }
    return $values;
}
function opensrs_RenewDomain($params, openSRS_base $O = NULL, $moduleName = "opensrs")
{
    if (is_null($O) || !$O instanceof openSRS_base) {
        try {
            $O = opensrs_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
        } catch (Exception $e) {
            return array("error" => $e->getMessage());
        }
    }
    $domain = strtolower($params["sld"] . "." . $params["tld"]);
    $result = select_query("tbldomains", "expirydate", array("id" => $params["domainid"]));
    $data = mysql_fetch_array($result);
    $expirydate = $data["expirydate"];
    $expiryyear = substr($expirydate, 0, 4);
    $cmd = array("action" => "renew", "object" => "DOMAIN", "attributes" => array("auto_renew" => "0", "currentexpirationyear" => $expiryyear, "handle" => "process", "domain" => $domain, "period" => $params["regperiod"]));
    $result = $O->send_cmd($cmd);
    logModuleCall($moduleName, "Renew Domain", $cmd, $result);
    if ($result["is_success"] != "1") {
        $values["error"] = $result["response_text"];
        if (!$values["error"]) {
            $values["error"] = "API Connection Failure. Please open ports 55443 and 55000 in your servers firewall.";
        }
    }
    return $values;
}
function opensrs_GetContactDetails($params, openSRS_base $O = NULL, $moduleName = "opensrs")
{
    if (is_null($O) || !$O instanceof openSRS_base) {
        try {
            $O = opensrs_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
        } catch (Exception $e) {
            return array("error" => $e->getMessage());
        }
    }
    $domain = strtolower($params["sld"] . "." . $params["tld"]);
    $cmd = array("action" => "GET_DOMAINS_CONTACTS", "object" => "DOMAIN", "attributes" => array("domain_list" => array($domain)));
    $result = $O->send_cmd($cmd);
    logModuleCall($moduleName, "Get Contact Details", $cmd, $result);
    if ($result["is_success"] != "1") {
        $values["error"] = $result["response_text"];
        if (!$values["error"]) {
            $values["error"] = "API Connection Failure. Please open ports 55443 and 55000 in your servers firewall.";
        }
    }
    $ownerdata = $result["attributes"][$domain]["contact_set"]["owner"];
    $admindata = $result["attributes"][$domain]["contact_set"]["admin"];
    $billingdata = $result["attributes"][$domain]["contact_set"]["billing"];
    $techdata = $result["attributes"][$domain]["contact_set"]["tech"];
    $values["Owner"]["First Name"] = $ownerdata["first_name"];
    $values["Owner"]["Last Name"] = $ownerdata["last_name"];
    $values["Owner"]["Organisation Name"] = $ownerdata["org_name"];
    $values["Owner"]["Email"] = $ownerdata["email"];
    $values["Owner"]["Address 1"] = $ownerdata["address1"];
    $values["Owner"]["Address 2"] = $ownerdata["address2"];
    $values["Owner"]["City"] = $ownerdata["city"];
    $values["Owner"]["State"] = $ownerdata["state"];
    $values["Owner"]["Postcode"] = $ownerdata["postal_code"];
    $values["Owner"]["Country"] = $ownerdata["country"];
    $values["Owner"]["Phone"] = $ownerdata["phone"];
    $values["Owner"]["Fax"] = $ownerdata["fax"];
    $values["Admin"]["First Name"] = $admindata["first_name"];
    $values["Admin"]["Last Name"] = $admindata["last_name"];
    $values["Admin"]["Organisation Name"] = $admindata["org_name"];
    $values["Admin"]["Email"] = $admindata["email"];
    $values["Admin"]["Address 1"] = $admindata["address1"];
    $values["Admin"]["Address 2"] = $admindata["address2"];
    $values["Admin"]["City"] = $admindata["city"];
    $values["Admin"]["State"] = $admindata["state"];
    $values["Admin"]["Postcode"] = $admindata["postal_code"];
    $values["Admin"]["Country"] = $admindata["country"];
    $values["Admin"]["Phone"] = $admindata["phone"];
    $values["Admin"]["Fax"] = $admindata["fax"];
    if (!preg_match("/ca\$/i", $params["tld"])) {
        $values["Billing"]["First Name"] = $billingdata["first_name"];
        $values["Billing"]["Last Name"] = $billingdata["last_name"];
        $values["Billing"]["Organisation Name"] = $billingdata["org_name"];
        $values["Billing"]["Email"] = $billingdata["email"];
        $values["Billing"]["Address 1"] = $billingdata["address1"];
        $values["Billing"]["Address 2"] = $billingdata["address2"];
        $values["Billing"]["City"] = $billingdata["city"];
        $values["Billing"]["State"] = $billingdata["state"];
        $values["Billing"]["Postcode"] = $billingdata["postal_code"];
        $values["Billing"]["Country"] = $billingdata["country"];
        $values["Billing"]["Phone"] = $billingdata["phone"];
        $values["Billing"]["Fax"] = $billingdata["fax"];
    }
    $values["Technical"]["First Name"] = $techdata["first_name"];
    $values["Technical"]["Last Name"] = $techdata["last_name"];
    $values["Technical"]["Organisation Name"] = $techdata["org_name"];
    $values["Technical"]["Email"] = $techdata["email"];
    $values["Technical"]["Address 1"] = $techdata["address1"];
    $values["Technical"]["Address 2"] = $techdata["address2"];
    $values["Technical"]["City"] = $techdata["city"];
    $values["Technical"]["State"] = $techdata["state"];
    $values["Technical"]["Postcode"] = $techdata["postal_code"];
    $values["Technical"]["Country"] = $techdata["country"];
    $values["Technical"]["Phone"] = $techdata["phone"];
    $values["Technical"]["Fax"] = $techdata["fax"];
    return $values;
}
function opensrs_SaveContactDetails($params, openSRS_base $O = NULL, $moduleName = "opensrs")
{
    $getFormattedPhoneNumber = function ($details, $contactType) {
        $selectedContactDetails = $details["contactdetails"][$contactType];
        $formattedPhoneNumber = $selectedContactDetails["Phone"];
        if (empty($selectedContactDetails["phone-normalised"]) && !preg_match("/^\\+[\\d]+\\.[\\d]+\$/", $formattedPhoneNumber)) {
            $formattedPhoneNumber = preg_replace("/[^\\d]+/", "", $formattedPhoneNumber);
            if (!empty($selectedContactDetails["Phone Country Code"])) {
                $countryCode = $selectedContactDetails["Phone Country Code"];
            } else {
                $countryCode = (new WHMCS\Utility\Country())->getCallingCode($selectedContactDetails["Country"]);
            }
            if (!empty($countryCode)) {
                $formattedPhoneNumber = "+" . $countryCode . "." . $formattedPhoneNumber;
            }
        }
        return $formattedPhoneNumber;
    };
    if (is_null($O) || !$O instanceof openSRS_base) {
        try {
            $O = opensrs_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
        } catch (Exception $e) {
            return array("error" => $e->getMessage());
        }
    }
    global $server_ip;
    $domain = strtolower($params["sld"] . "." . $params["tld"]);
    $cmd = array("object" => "domain", "action" => "modify", "registrant_ip" => $server_ip, "attributes" => array("domain" => strtolower($params["sld"] . "." . $params["tld"]), "data" => "contact_info", "affect_domains" => "0", "lang_pref" => "EN", "report_email" => $params["Owner"]["Email"], "contact_set" => array("owner" => array("first_name" => $params["contactdetails"]["Owner"]["First Name"], "state" => convertStateToCode($params["contactdetails"]["Owner"]["State"], $params["contactdetails"]["Owner"]["Country"]), "country" => $params["contactdetails"]["Owner"]["Country"], "address1" => $params["contactdetails"]["Owner"]["Address 1"], "address2" => $params["contactdetails"]["Owner"]["Address 2"], "last_name" => $params["contactdetails"]["Owner"]["Last Name"], "address3" => "", "city" => $params["contactdetails"]["Owner"]["City"], "fax" => $params["contactdetails"]["Owner"]["Fax"], "postal_code" => $params["contactdetails"]["Owner"]["Postcode"], "email" => $params["contactdetails"]["Owner"]["Email"], "phone" => $getFormattedPhoneNumber($params, "Owner"), "org_name" => $params["contactdetails"]["Owner"]["Organisation Name"], "lang_pref" => "EN"), "admin" => array("first_name" => $params["contactdetails"]["Admin"]["First Name"], "state" => convertStateToCode($params["contactdetails"]["Admin"]["State"], $params["contactdetails"]["Admin"]["Country"]), "country" => $params["contactdetails"]["Admin"]["Country"], "address1" => $params["contactdetails"]["Admin"]["Address 1"], "address2" => $params["contactdetails"]["Admin"]["Address 2"], "last_name" => $params["contactdetails"]["Admin"]["Last Name"], "address3" => "", "city" => $params["contactdetails"]["Admin"]["City"], "fax" => $params["contactdetails"]["Admin"]["Fax"], "postal_code" => $params["contactdetails"]["Admin"]["Postcode"], "email" => $params["contactdetails"]["Admin"]["Email"], "phone" => $getFormattedPhoneNumber($params, "Admin"), "org_name" => $params["contactdetails"]["Admin"]["Organisation Name"], "lang_pref" => "EN"), "tech" => array("first_name" => $params["contactdetails"]["Technical"]["First Name"], "state" => convertStateToCode($params["contactdetails"]["Technical"]["State"], $params["contactdetails"]["Technical"]["Country"]), "country" => $params["contactdetails"]["Technical"]["Country"], "address1" => $params["contactdetails"]["Technical"]["Address 1"], "address2" => $params["contactdetails"]["Technical"]["Address 2"], "last_name" => $params["contactdetails"]["Technical"]["Last Name"], "address3" => "", "city" => $params["contactdetails"]["Technical"]["City"], "fax" => $params["contactdetails"]["Technical"]["Fax"], "postal_code" => $params["contactdetails"]["Technical"]["Postcode"], "email" => $params["contactdetails"]["Technical"]["Email"], "phone" => $getFormattedPhoneNumber($params, "Technical"), "org_name" => $params["contactdetails"]["Technical"]["Organisation Name"], "lang_pref" => "EN"))));
    if (!($params["tld"] == "ca" || substr($params["tld"], -3) == ".ca")) {
        $cmd["attributes"]["contact_set"]["billing"] = array("first_name" => $params["contactdetails"]["Billing"]["First Name"], "state" => convertStateToCode($params["contactdetails"]["Billing"]["State"], $params["contactdetails"]["Billing"]["Country"]), "country" => $params["contactdetails"]["Billing"]["Country"], "address1" => $params["contactdetails"]["Billing"]["Address 1"], "address2" => $params["contactdetails"]["Billing"]["Address 2"], "last_name" => $params["contactdetails"]["Billing"]["Last Name"], "address3" => "", "city" => $params["contactdetails"]["Billing"]["City"], "fax" => $params["contactdetails"]["Billing"]["Fax"], "postal_code" => $params["contactdetails"]["Billing"]["Postcode"], "email" => $params["contactdetails"]["Billing"]["Email"], "phone" => $getFormattedPhoneNumber($params, "Billing"), "org_name" => $params["contactdetails"]["Billing"]["Organisation Name"], "lang_pref" => "EN");
    } else {
        $cmd["attributes"]["contact_set"]["owner"]["state"] = convertToCiraCode($cmd["attributes"]["contact_set"]["owner"]["state"]);
        $cmd["attributes"]["contact_set"]["admin"]["state"] = convertToCiraCode($cmd["attributes"]["contact_set"]["admin"]["state"]);
        $cmd["attributes"]["contact_set"]["tech"]["state"] = convertToCiraCode($cmd["attributes"]["contact_set"]["tech"]["state"]);
    }
    $result = $O->send_cmd($cmd);
    logModuleCall($moduleName, "Save Contact Details (Modify Domain)", $cmd, $result, "", array(opensrs_getusername($domain), opensrs_getpassword($params["domainid"], $domain)));
    if ($result["is_success"] != "1") {
        $values["error"] = $result["response_text"] . " - " . $result["attributes"]["details"][$domain]["response_text"];
        if (!$values["error"]) {
            $values["error"] = "API Connection Failure. Please open ports 55443 and 55000 in your servers firewall.";
        }
    }
    return $values;
}
function opensrs_GetEPPCode($params, openSRS_base $O = NULL, $moduleName = "opensrs")
{
    if (is_null($O) || !$O instanceof openSRS_base) {
        try {
            $O = opensrs_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
        } catch (Exception $e) {
            return array("error" => $e->getMessage());
        }
    }
    global $server_ip;
    $cmd = array("action" => "get", "object" => "domain", "registrant_ip" => $server_ip, "attributes" => array("domain" => strtolower($params["sld"] . "." . $params["tld"]), "type" => "domain_auth_info"));
    $result = $O->send_cmd($cmd);
    logModuleCall($moduleName, "Get EPP Code (Get Domain)", $cmd, $result);
    if ($result["is_success"] != "1") {
        $values["error"] = $result["response_text"];
        if (!$values["error"]) {
            $values["error"] = "API Connection Failure. Please open ports 55443 and 55000 in your servers firewall.";
        }
    } else {
        $epp = $result["attributes"]["domain_auth_info"];
        $values["eppcode"] = $epp;
    }
    return $values;
}
function opensrs_getusername($domain)
{
    $result = select_query("mod_opensrs", "username", array("domain" => $domain));
    $data = mysql_fetch_array($result);
    $username = $data["username"];
    if ($username) {
        return $username;
    }
    $username = preg_replace("/[^a-zA-Z]/", "", $domain);
    $username = substr($username, 0, 8);
    return $username;
}
function opensrs_getpassword($domainid, $domain)
{
    $result = select_query("mod_opensrs", "password", array("domain" => $domain));
    $data = mysql_fetch_array($result);
    $password = trim($data["password"]);
    if ($password) {
        return $password;
    }
    $password = md5(ltrim($domainid, "0"));
    $password = substr($password, 0, 10);
    return $password;
}
function opensrs_RegisterNameserver($params, openSRS_base $O = NULL, $moduleName = "opensrs")
{
    if (is_null($O) || !$O instanceof openSRS_base) {
        try {
            $O = opensrs_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
        } catch (Exception $e) {
            return array("error" => $e->getMessage());
        }
    }
    $domain = strtolower($params["sld"] . "." . $params["tld"]);
    $cmd = array("action" => "create", "object" => "nameserver", "attributes" => array("domain" => $domain, "name" => $params["nameserver"], "ipaddress" => $params["ipaddress"]));
    $result = $O->send_cmd($cmd);
    logModuleCall($moduleName, "Register NS (Create NS)", $cmd, $result);
    if ($result["is_success"] != "1") {
        $values["error"] = $result["response_text"];
        if (!$values["error"]) {
            $values["error"] = "API Connection Failure. Please open ports 55443 and 55000 in your servers firewall.";
        }
    }
    return $values;
}
function opensrs_DeleteNameserver($params, openSRS_base $O = NULL, $moduleName = "opensrs")
{
    if (is_null($O) || !$O instanceof openSRS_base) {
        try {
            $O = opensrs_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
        } catch (Exception $e) {
            return array("error" => $e->getMessage());
        }
    }
    $domain = strtolower($params["sld"] . "." . $params["tld"]);
    $cmd = array("action" => "delete", "object" => "nameserver", "attributes" => array("domain" => $domain, "name" => $params["nameserver"], "ipaddress" => $params["ipaddress"]));
    $result = $O->send_cmd($cmd);
    logModuleCall($moduleName, "Delete NS (Delete NS)", $cmd, $result);
    if ($result["is_success"] != "1") {
        $values["error"] = $result["response_text"];
        if (!$values["error"]) {
            $values["error"] = "API Connection Failure. Please open ports 55443 and 55000 in your servers firewall.";
        }
    }
    return $values;
}
function opensrs_ModifyNameserver($params, openSRS_base $O = NULL, $moduleName = "opensrs")
{
    if (is_null($O) || !$O instanceof openSRS_base) {
        try {
            $O = opensrs_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
        } catch (Exception $e) {
            return array("error" => $e->getMessage());
        }
    }
    $domain = strtolower($params["sld"] . "." . $params["tld"]);
    $cmd = array("action" => "modify", "object" => "nameserver", "attributes" => array("domain" => $domain, "name" => $params["nameserver"], "new_name" => $params["nameserver"], "ipaddress" => $params["newipaddress"]));
    $result = $O->send_cmd($cmd);
    logModuleCall($moduleName, "Modify NS (Modify NS)", $cmd, $result);
    if ($result["is_success"] != "1") {
        $values["error"] = $result["response_text"];
        if (!$values["error"]) {
            $values["error"] = "API Connection Failure. Please open ports 55443 and 55000 in your servers firewall.";
        }
    }
    return $values;
}
function opensrs_AdminDomainsTabFields($params)
{
    $domain = $params["sld"] . "." . $params["tld"];
    $data = get_query_vals("mod_opensrs", "username,password", array("domain" => $domain));
    $username = $data["username"];
    $password = $data["password"];
    $fieldsarray = array("OpenSRS Username" => "<input type=\"text\" name=\"modulefields[0]\" size=\"30\" value=\"" . $username . "\" />", "OpenSRS Password" => "<input type=\"text\" name=\"modulefields[1]\" size=\"30\" value=\"" . $password . "\" />");
    return $fieldsarray;
}
function opensrs_AdminDomainsTabFieldsSave($params)
{
    $domain = $params["sld"] . "." . $params["tld"];
    update_query("mod_opensrs", array("username" => $_POST["modulefields"][0], "password" => $_POST["modulefields"][1]), array("domain" => $domain));
}
function opensrs_Sync($params, openSRS_base $O = NULL, $moduleName = "opensrs")
{
    if (is_null($O) || !$O instanceof openSRS_base) {
        try {
            $O = opensrs_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
        } catch (Exception $e) {
            return array("error" => $e->getMessage());
        }
    }
    $server_ip = isset($_SERVER["SERVER_ADDR"]) ? $_SERVER["SERVER_ADDR"] : $_SERVER["LOCAL_ADDR"];
    $domainid = $params["domainid"];
    $domain = strtolower($params["domain"]);
    $username = opensrs_getusername($domain);
    $password = opensrs_getpassword($domainid, $domain);
    $error = "";
    $cmd = array("action" => "get", "object" => "domain", "registrant_ip" => $server_ip, "attributes" => array("domain" => strtolower($params["sld"] . "." . $params["tld"]), "type" => "all_info"));
    $result = $O->send_cmd($cmd);
    logModuleCall($moduleName . "sync", "Get Domain Info", $cmd, $result, "", array($username, $password));
    if ($result["is_success"] != "1") {
        return array("error" => $result["response_text"]);
    }
    $expirydate = $result["attributes"]["expiredate"];
    $expirydate = explode(" ", $expirydate);
    $expirydate = $expirydate[0];
    $rtn = array();
    $rtn["active"] = true;
    $rtn["expirydate"] = $expirydate;
    return $rtn;
}
function opensrs_TransferSync($params, openSRS_base $O = NULL, $moduleName = "opensrs")
{
    if (is_null($O) || !$O instanceof openSRS_base) {
        try {
            $O = opensrs_Connect($params["Username"], $params["PrivateKey"], $params["TestMode"]);
        } catch (Exception $e) {
            return array("error" => $e->getMessage());
        }
    }
    $server_ip = isset($_SERVER["SERVER_ADDR"]) ? $_SERVER["SERVER_ADDR"] : $_SERVER["LOCAL_ADDR"];
    $domainid = $params["domainid"];
    $domain = strtolower($params["domain"]);
    $username = opensrs_getusername($domain);
    $password = opensrs_getpassword($domainid, $domain);
    $error = "";
    $cmd = array("action" => "get", "object" => "domain", "registrant_ip" => $server_ip, "attributes" => array("domain" => strtolower($params["sld"] . "." . $params["tld"]), "type" => "all_info"));
    $result = $O->send_cmd($cmd);
    logModuleCall($moduleName . "sync", "Get Domain Info", $cmd, $result, "", array($username, $password));
    if ($result["is_success"] != "1") {
        return array("error" => $result["response_text"]);
    }
    $expirydate = $result["attributes"]["expiredate"];
    $expirydate = explode(" ", $expirydate);
    $expirydate = $expirydate[0];
    $rtn = array();
    $rtn["active"] = true;
    $rtn["expirydate"] = $expirydate;
    return $rtn;
}
function opensrs_Connect($username, $privateKey, $testMode = false)
{
    $mode = "live";
    if ($testMode) {
        $mode = "test";
    }
    require_once dirname(__FILE__) . "/openSRS_base.php";
    if (!class_exists("PEAR")) {
        $error = "OpenSRS Class Files Missing. Visit <a href=\"https://docs.whmcs.com/" . "OpenSRS#Additional_Registrar_Module_Files_Requirement\" target=\"_blank\">" . "https://docs.whmcs.com/OpenSRS#Additional_Registrar_Module_Files_Requirement</a> to resolve";
        throw new Exception($error);
    }
    return new openSRS_base($mode, "XCP", $username, $privateKey);
}

?>