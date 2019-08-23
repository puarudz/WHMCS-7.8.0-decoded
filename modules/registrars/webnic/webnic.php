<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function webnic_getConfigArray()
{
    $configarray = array("Source" => array("Type" => "text", "Size" => "20", "Description" => ""), "Password" => array("Type" => "password", "Size" => "20", "Description" => ""), "TestMode" => array("Type" => "yesno"));
    return $configarray;
}
function webnic_GetNameservers($params)
{
    $url = "pn_whois.jsp";
    $postfields = array();
    $postfields["source"] = $params["Source"];
    $postfields["domain"] = $params["sld"] . "." . $params["tld"];
    $rtype = "Get Nameservers";
    $results = webnic_call($url, $rtype, $postfields, $params);
    if ($results[0] == 0) {
        foreach ($results as $row) {
            $row = explode("\t", $row);
            $arr[$row[0]] = $row[1];
        }
        return array("ns1" => $arr["ns1"], "ns2" => $arr["ns2"], "ns3" => $arr["ns3"], "ns4" => $arr["ns4"], "ns5" => $arr["ns5"]);
    } else {
        if ($results[1]) {
            return array("error" => $results[1]);
        }
        if ($results[0]) {
            return array("error" => $results[0]);
        }
    }
}
function webnic_SaveNameservers($params)
{
    $otime = date("Y-m-d H:i:s");
    $ochecksum = md5($params["Source"] . $otime . md5($params["Password"]));
    $url = "pn_dns.jsp";
    $postfields = array();
    $postfields["source"] = $params["Source"];
    $postfields["otime"] = $otime;
    $postfields["ochecksum"] = $ochecksum;
    $postfields["domain"] = $params["sld"] . "." . $params["tld"];
    $postfields["ns1"] = $params["ns1"];
    $postfields["ns2"] = $params["ns2"];
    $postfields["ns3"] = $params["ns3"];
    $postfields["ns4"] = $params["ns4"];
    $postfields["ns5"] = $params["ns5"];
    $postfields["nsip1"] = gethostbyname($params["ns1"]);
    $postfields["nsip2"] = gethostbyname($params["ns2"]);
    $postfields["nsip3"] = gethostbyname($params["ns3"]);
    $postfields["nsip4"] = gethostbyname($params["ns4"]);
    $postfields["nsip5"] = gethostbyname($params["ns5"]);
    $rtype = "Save Nameservers";
    $results = webnic_call($url, $rtype, $postfields, $params);
}
function webnic_GetRegistrarLock($params)
{
    $url = "pn_whois.jsp";
    $postfields = array();
    $postfields["source"] = $params["Source"];
    $postfields["domain"] = $params["sld"] . "." . $params["tld"];
    $rtype = "Get Registrar Lock";
    $results = webnic_call($url, $rtype, $postfields, $params);
    if ($results[0] == 0) {
        foreach ($results as $row) {
            $row = explode("\t", $row);
            $arr[$row[0]] = $row[1];
        }
        if ($arr["status"] == "A") {
            $lockstatus = "unlocked";
        } else {
            $lockstatus = "locked";
        }
        return $lockstatus;
    }
}
function webnic_SaveRegistrarLock($params)
{
    $otime = date("Y-m-d H:i:s");
    $ochecksum = md5($params["Source"] . $otime . md5($params["Password"]));
    $url = "pn_protect.jsp";
    $postfields = array();
    $postfields["source"] = $params["Source"];
    $postfields["otime"] = $otime;
    $postfields["ochecksum"] = $ochecksum;
    $postfields["domainname"] = $params["sld"] . "." . $params["tld"];
    if ($params["lockenabled"] == "locked") {
        $postfields["status"] = "L";
    } else {
        $postfields["status"] = "A";
    }
    $rtype = "Save Registrar Lock";
    $results = webnic_call($url, $rtype, $postfields, $params);
    if (substr($results[0], 0, 1) == 0) {
    } else {
        return array("error" => $results[0]);
    }
}
function webnic_RegisterDomain($params)
{
    $otime = date("Y-m-d H:i:s");
    $ochecksum = md5($params["Source"] . $otime . md5($params["Password"]));
    $username = preg_replace("/[^a-zA-Z]/", "", $params["sld"] . $params["tld"]);
    $username = substr($username, 0, 8) . rand(100, 999);
    $password = substr(md5($params["domainid"]), 0, 10);
    if (!$params["companyname"]) {
        $params["companyname"] = "-";
    }
    $url = "pn_newreg.jsp";
    $postfields = array();
    $postfields["source"] = $params["Source"];
    $postfields["otime"] = $otime;
    $postfields["ochecksum"] = $ochecksum;
    $postfields["domainname"] = $params["sld"] . "." . $params["tld"];
    $postfields["encoding"] = "iso8859-1";
    $postfields["term"] = $params["regperiod"];
    $postfields["ns1"] = $params["ns1"];
    $postfields["ns2"] = $params["ns2"];
    if ($params["ns3"]) {
        $postfields["ns3"] = $params["ns3"];
    }
    if ($params["ns4"]) {
        $postfields["ns4"] = $params["ns4"];
    }
    if ($params["ns5"]) {
        $postfields["ns5"] = $params["ns5"];
    }
    $postfields["ns1ip"] = gethostbyname($params["ns1"]);
    $postfields["ns2ip"] = gethostbyname($params["ns2"]);
    if ($params["ns3"]) {
        $postfields["ns3ip"] = gethostbyname($params["ns3"]);
    }
    if ($params["ns4"]) {
        $postfields["ns4ip"] = gethostbyname($params["ns4"]);
    }
    if ($params["ns5"]) {
        $postfields["ns5ip"] = gethostbyname($params["ns5"]);
    }
    $postfields["reg_company"] = $params["companyname"];
    $postfields["reg_fname"] = $params["firstname"];
    $postfields["reg_lname"] = $params["lastname"];
    $postfields["reg_addr1"] = $params["address1"];
    $postfields["reg_addr2"] = $params["address2"];
    $postfields["reg_state"] = $params["state"];
    $postfields["reg_city"] = $params["city"];
    $postfields["reg_postcode"] = $params["postcode"];
    $postfields["reg_telephone"] = $params["fullphonenumber"];
    $postfields["reg_country"] = $params["country"];
    $postfields["reg_email"] = $params["email"];
    $postfields["flag_adm"] = 1;
    $postfields["flag_tec"] = 1;
    $postfields["flag_bil"] = 1;
    $postfields["username"] = $username;
    $postfields["password"] = $password;
    $postfields["newuser"] = "new";
    $postfields["bil_contact_type"] = $params["companyname"] ? (int) "0" : "1";
    $postfields["tec_contact_type"] = $postfields["bil_contact_type"];
    $postfields["adm_contact_type"] = $postfields["tec_contact_type"];
    $postfields["reg_contact_type"] = $postfields["adm_contact_type"];
    $postfields["custom_reg1"] = $params["additionalfields"]["Identity or Registration Number"];
    $postfields["custom_reg2"] = $params["additionalfields"]["Organization Type"];
    $postfields["custom_reg3"] = $params["additionalfields"]["Registrant Type"];
    if (preg_match("/us\$/i", $params["tld"])) {
        $nexus = $params["additionalfields"]["Nexus Category"];
        $countrycode = $params["additionalfields"]["Nexus Country"];
        $purpose = $params["additionalfields"]["Application Purpose"];
        if ($purpose == "Business use for profit") {
            $purpose = "P1";
            $custom_reg2 = "OTA000005";
        } else {
            if ($purpose == "Non-profit business") {
                $purpose = "P2";
                $custom_reg2 = "OTA000025";
            } else {
                if ($purpose == "Club") {
                    $purpose = "P2";
                    $custom_reg2 = "OTA000032";
                } else {
                    if ($purpose == "Association") {
                        $purpose = "P2";
                        $custom_reg2 = "OTA000032";
                    } else {
                        if ($purpose == "Religious Organization") {
                            $purpose = "P2";
                            $custom_reg2 = "OTA000032";
                        } else {
                            if ($purpose == "Personal Use") {
                                $purpose = "P3";
                                $custom_reg2 = "OTA000032";
                            } else {
                                if ($purpose == "Educational purposes") {
                                    $purpose = "P4";
                                    $custom_reg2 = "OTA000006";
                                } else {
                                    if ($purpose == "Government purposes") {
                                        $purpose = "P5";
                                        $custom_reg2 = "OTA000032";
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $postfields["purpose"] = $purpose;
        $postfields["nexus"] = $nexus;
        $postfields["custom_reg3"] = $nexus;
    }
    if (preg_match("/sg\$/i", $params["tld"])) {
        if ($params["additionalfields"]["Registrant Type"] == "Individual") {
            $regtype = "2";
        } else {
            $regtype = "1";
        }
        $postfields["custom_reg1"] = $params["additionalfields"]["RCB Singapore ID"];
        $postfields["custom_adm1"] = $params["additionalfields"]["RCB Singapore ID"];
        $postfields["proxy"] = 0;
    }
    if (preg_match("/my\$/i", $params["tld"])) {
        $individual = $params["companyname"] == "-" ? "I" : "O";
        $postfields["ctxtype"] = $individual;
    }
    if ($params["companyname"] == "-") {
        if (preg_match("/sg\$/i", $params["tld"])) {
            $postfields["bil_contact_type"] = (int) "0";
            $postfields["tec_contact_type"] = $postfields["bil_contact_type"];
            $postfields["adm_contact_type"] = $postfields["tec_contact_type"];
            $postfields["reg_contact_type"] = $postfields["adm_contact_type"];
            $postfields["custom_reg1"] = $params["additionalfields"]["RCB Singapore ID"];
            $postfields["custom_adm1"] = $params["additionalfields"]["RCB Singapore ID"];
            $postfields["custom_tec1"] = $params["additionalfields"]["RCB Singapore ID"];
            $postfields["custom_bil1"] = $params["additionalfields"]["RCB Singapore ID"];
        }
        $postfields["custom_bil3"] = $params["additionalfields"]["Date of Birth"];
        $params["additionalfields"]["Date of Birth"] = $postfields["custom_bil3"];
        $postfields["custom_tec3"] = $params["additionalfields"]["Date of Birth"];
        $postfields["custom_adm3"] = $postfields["custom_tec3"];
        $postfields["custom_reg3"] = $postfields["custom_adm3"];
        $params["companyname"] = "-";
    } else {
        $postfields["custom_reg1"] = $params["additionalfields"]["RCB Singapore ID"];
        $postfields["custom_adm1"] = $params["additionalfields"]["RCB Singapore ID"];
        $postfields["custom_tec1"] = $params["additionalfields"]["RCB Singapore ID"];
        $postfields["custom_bil1"] = $params["additionalfields"]["RCB Singapore ID"];
        $postfields["bil_contact_type"] = "1";
        $postfields["tec_contact_type"] = $postfields["bil_contact_type"];
        $postfields["adm_contact_type"] = $postfields["tec_contact_type"];
        $postfields["reg_contact_type"] = $postfields["adm_contact_type"];
        $usorgtype = $params["additionalfields"]["Organization Type"];
        if ($usorgtype == "Permanent resident of U.S") {
            $usorgtype = "C12";
        } else {
            if ($usorgtype == "Entity with office in US") {
                $usorgtype = "C32";
            } else {
                if ($usorgtype == "Entity that regularly engages in lawful activities") {
                    $usorgtype = "C31";
                } else {
                    if ($usorgtype == "Citizen of U.S") {
                        $usorgtype = "C11";
                    } else {
                        if ($usorgtype == "Incorporated within one of the U.S. states") {
                            $usorgtype = "C21";
                        }
                    }
                }
            }
        }
        if ($usorgtype == "") {
            $usorgtype = "C31";
        }
        $postfields["custom_bil3"] = $usorgtype;
        $params["additionalfields"]["Date of Birth"] = $postfields["custom_bil3"];
        $postfields["custom_tec3"] = $params["additionalfields"]["Date of Birth"];
        $postfields["custom_adm3"] = $postfields["custom_tec3"];
        $postfields["custom_reg3"] = $postfields["custom_adm3"];
    }
    if (preg_match("/(asia|tw)\$/i", $params["tld"])) {
        $reg1value = trim($params["additionalfields"]["Identity Number"]);
        $legaltype = strtolower($params["additionalfields"]["Legal Type"]);
        $reg2value = "";
        switch ($legaltype) {
            case "corporation":
                $reg2value = "OTA000003";
                break;
            case "partnership":
                $reg2value = "OTA000005";
                break;
            case "politicalParty":
                $reg2value = "OTA000025";
                break;
            case "institution":
                $reg2value = "OTA000006";
                break;
            case "society":
                $reg2value = "OTA000012";
                break;
            default:
                $reg2value = "OTA000032";
                break;
        }
        $postfields["custom_bil1"] = $reg1value;
        $postfields["custom_tec1"] = $postfields["custom_bil1"];
        $postfields["custom_adm1"] = $postfields["custom_tec1"];
        $postfields["custom_reg1"] = $postfields["custom_adm1"];
        $postfields["custom_bil2"] = $reg2value;
        $postfields["custom_tec2"] = $postfields["custom_bil2"];
        $postfields["custom_adm2"] = $postfields["custom_tec2"];
        $postfields["custom_reg2"] = $postfields["custom_adm2"];
        foreach (array("custom_reg3", "custom_adm3", "custom_tec3", "custom_bil3") as $removefield) {
            unset($postfields[$removefield]);
        }
        if (preg_match("/asia\$/i", $params["tld"]) && !webnic_isCountryInAsia($postfields["reg_country"])) {
            $postfields["proxy"] = 1;
        }
    }
    $rtype = "Register Domain";
    $results = webnic_call($url, $rtype, $postfields, $params);
    if (substr($results[0], 0, 1) == 0) {
        if ($params["idprotection"]) {
            $otime = date("Y-m-d H:i:s");
            $ochecksum = md5($params["Source"] . $otime . md5($params["Password"]));
            $url = "pn_whoisprivacy.jsp";
            $postfields = array();
            $postfields["source"] = $params["Source"];
            $postfields["otime"] = $otime;
            $postfields["ochecksum"] = $ochecksum;
            $postfields["domainname"] = $params["sld"] . "." . $params["tld"];
            $results = webnic_call($url, $rtype, $postfields, $params);
            if (substr($results[0], 0, 1) == 0) {
            } else {
                return array("error" => $results[0]);
            }
        }
        return array("success" => "complete");
    }
    return array("error" => $results[0]);
}
function webnic_TransferDomain($params)
{
    $otime = date("Y-m-d H:i:s");
    $ochecksum = md5($params["Source"] . $otime . md5($params["Password"]));
    $username = preg_replace("/[^a-zA-Z]/", "", $params["sld"] . $params["tld"]);
    $username = substr($username, 0, 8) . rand(100, 999);
    $password = substr(md5($params["domainid"]), 0, 10);
    $url = "pn_newtransfer.jsp";
    $postfields = array();
    $postfields["source"] = $params["Source"];
    $postfields["otime"] = $otime;
    $postfields["ochecksum"] = $ochecksum;
    $postfields["domainname"] = $params["sld"] . "." . $params["tld"];
    $postfields["term"] = $params["regperiod"];
    $postfields["authinfo"] = $params["transfersecret"];
    $postfields["userstatus"] = "NEW";
    $postfields["username"] = $username;
    $postfields["password"] = $password;
    $postfields["password2"] = $password;
    $postfields["reg_company"] = $params["companyname"];
    $postfields["reg_fname"] = $params["firstname"];
    $postfields["reg_lname"] = $params["lastname"];
    $postfields["reg_addr1"] = $params["address1"];
    $postfields["reg_addr2"] = $params["address2"];
    $postfields["reg_state"] = $params["state"];
    $postfields["reg_city"] = $params["city"];
    $postfields["reg_postcode"] = $params["postcode"];
    $postfields["reg_telephone"] = $params["fullphonenumber"];
    $postfields["reg_country"] = $params["country"];
    $postfields["reg_email"] = $params["email"];
    $postfields["bil_company"] = $params["companyname"];
    $postfields["bil_fname"] = $params["firstname"];
    $postfields["bil_lname"] = $params["lastname"];
    $postfields["bil_addr1"] = $params["address1"];
    $postfields["bil_addr2"] = $params["address2"];
    $postfields["bil_state"] = $params["state"];
    $postfields["bil_city"] = $params["city"];
    $postfields["bil_postcode"] = $params["postcode"];
    $postfields["bil_telephone"] = $params["fullphonenumber"];
    $postfields["bil_country"] = $params["country"];
    $postfields["bil_email"] = $params["email"];
    $postfields["adm_company"] = $params["companyname"];
    $postfields["adm_fname"] = $params["firstname"];
    $postfields["adm_lname"] = $params["lastname"];
    $postfields["adm_addr1"] = $params["address1"];
    $postfields["adm_addr2"] = $params["address2"];
    $postfields["adm_state"] = $params["state"];
    $postfields["adm_city"] = $params["city"];
    $postfields["adm_postcode"] = $params["postcode"];
    $postfields["adm_telephone"] = $params["fullphonenumber"];
    $postfields["adm_country"] = $params["country"];
    $postfields["adm_email"] = $params["email"];
    $postfields["tec_company"] = $params["companyname"];
    $postfields["tec_fname"] = $params["firstname"];
    $postfields["tec_lname"] = $params["lastname"];
    $postfields["tec_addr1"] = $params["address1"];
    $postfields["tec_addr2"] = $params["address2"];
    $postfields["tec_state"] = $params["state"];
    $postfields["tec_city"] = $params["city"];
    $postfields["tec_postcode"] = $params["postcode"];
    $postfields["tec_telephone"] = $params["fullphonenumber"];
    $postfields["tec_country"] = $params["country"];
    $postfields["tec_email"] = $params["email"];
    $postfields["bil_contact_type"] = $params["companyname"] ? (int) "0" : "1";
    $postfields["tec_contact_type"] = $postfields["bil_contact_type"];
    $postfields["adm_contact_type"] = $postfields["tec_contact_type"];
    $postfields["reg_contact_type"] = $postfields["adm_contact_type"];
    if (preg_match("/us\$/i", $params["tld"])) {
        $nexus = $params["additionalfields"]["Nexus Category"];
        $countrycode = $params["additionalfields"]["Nexus Country"];
        $purpose = $params["additionalfields"]["Application Purpose"];
        if ($purpose == "Business use for profit") {
            $purpose = "P1";
            $custom_reg2 = "OTA000005";
        } else {
            if ($purpose == "Non-profit business") {
                $purpose = "P2";
                $custom_reg2 = "OTA000025";
            } else {
                if ($purpose == "Club") {
                    $purpose = "P2";
                    $custom_reg2 = "OTA000032";
                } else {
                    if ($purpose == "Association") {
                        $purpose = "P2";
                        $custom_reg2 = "OTA000032";
                    } else {
                        if ($purpose == "Religious Organization") {
                            $purpose = "P2";
                            $custom_reg2 = "OTA000032";
                        } else {
                            if ($purpose == "Personal Use") {
                                $purpose = "P3";
                                $custom_reg2 = "OTA000032";
                            } else {
                                if ($purpose == "Educational purposes") {
                                    $purpose = "P4";
                                    $custom_reg2 = "OTA000006";
                                } else {
                                    if ($purpose == "Government purposes") {
                                        $purpose = "P5";
                                        $custom_reg2 = "OTA000032";
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $postfields["purpose"] = $purpose;
        $postfields["nexus"] = $nexus;
        $postfields["custom_reg3"] = $nexus;
    }
    if (preg_match("/sg\$/i", $params["tld"])) {
        if ($params["additionalfields"]["Registrant Type"] == "Individual") {
            $regtype = "2";
        } else {
            $regtype = "1";
        }
        $postfields["custom_reg1"] = $params["additionalfields"]["RCB Singapore ID"];
        $postfields["custom_adm1"] = $params["additionalfields"]["RCB Singapore ID"];
        $postfields["proxy"] = 0;
    }
    if (preg_match("/my\$/i", $params["tld"])) {
        $individual = $params["companyname"] == "-" ? "I" : "O";
        $postfields["ctxtype"] = $individual;
    }
    $rtype = "Transfer Domain";
    $results = webnic_call($url, $rtype, $postfields, $params);
    if (substr($results[0], 0, 1) == 0) {
        return array("success" => "complete");
    }
    return array("error" => $results[0]);
}
function webnic_RenewDomain($params)
{
    $otime = date("Y-m-d H:i:s");
    $ochecksum = md5($params["Source"] . $otime . md5($params["Password"]));
    $url = "pn_renew.jsp";
    $postfields = array();
    $postfields["source"] = $params["Source"];
    $postfields["otime"] = $otime;
    $postfields["ochecksum"] = $ochecksum;
    $postfields["domainname"] = $params["sld"] . "." . $params["tld"];
    $postfields["term"] = $params["regperiod"];
    if (preg_match("/asia\$/i", $params["tld"])) {
        $postfields["proxy"] = 1;
    }
    $rtype = "Renew Domain";
    $results = webnic_call($url, $rtype, $postfields, $params);
    if (substr($results[0], 0, 1) == 0) {
        return array("success" => "complete");
    }
    return array("error" => $results[0]);
}
function webnic_GetContactDetails($params)
{
    $url = "pn_whois.jsp";
    $postfields = array();
    $postfields["source"] = $params["Source"];
    $postfields["domain"] = $params["sld"] . "." . $params["tld"];
    $rtype = "Get Contact Details";
    $results = webnic_call($url, $rtype, $postfields, $params);
    if ($results[0] == 0) {
        foreach ($results as $row) {
            $row = explode("\t", $row);
            $arr[$row[0]] = $row[1];
        }
        return array("Registrant" => array("First Name" => $arr["registrant first name"], "Last Name" => $arr["registrant last name"], "Company Name" => $arr["registrant"], "Address 1" => $arr["registrant address 1"], "Address 2" => $arr["registrant address 2"], "City" => $arr["registrant city"], "State" => $arr["registrant state"], "Country" => $arr["registrant country"], "ZIP Code" => $arr["registrant zip"], "Phone Number" => $arr["registrant phone"], "Fax Number" => $arr["registrant fax"], "Email Address" => $arr["registrant email"]), "Admin" => array("First Name" => $arr["admin first name"], "Last Name" => $arr["admin last name"], "Company Name" => $arr["admin company"], "Address 1" => $arr["admin address 1"], "Address 2" => $arr["admin address 2"], "City" => $arr["admin city"], "State" => $arr["admin state"], "Country" => $arr["admin country"], "ZIP Code" => $arr["admin zip"], "Phone Number" => $arr["admin phone"], "Fax Number" => $arr["admin fax"], "Email Address" => $arr["admin email"]), "Technical" => array("First Name" => $arr["technical first name"], "Last Name" => $arr["technical last name"], "Company Name" => $arr["technical company"], "Address 1" => $arr["technical address 1"], "Address 2" => $arr["technical address 2"], "City" => $arr["technical city"], "State" => $arr["technical state"], "Country" => $arr["technical country"], "ZIP Code" => $arr["technical zip"], "Phone Number" => $arr["technical phone"], "Fax Number" => $arr["technical fax"], "Email Address" => $arr["technical email"]), "Billing" => array("First Name" => $arr["billing first name"], "Last Name" => $arr["billing last name"], "Company Name" => $arr["billing company"], "Address 1" => $arr["billing address 1"], "Address 2" => $arr["billing address 2"], "City" => $arr["billing city"], "State" => $arr["billing state"], "Country" => $arr["billing country"], "ZIP Code" => $arr["billing zip"], "Phone Number" => $arr["billing phone"], "Fax Number" => $arr["billing fax"], "Email Address" => $arr["billing email"]));
    } else {
        return array("error" => $results[1]);
    }
}
function webnic_SaveContactDetails($params)
{
    $otime = date("Y-m-d H:i:s");
    $ochecksum = md5($params["Source"] . $otime . md5($params["Password"]));
    $url = "pn_newmod.jsp";
    $postfields = array();
    $postfields["source"] = $params["Source"];
    $postfields["otime"] = $otime;
    $postfields["ochecksum"] = $ochecksum;
    $postfields["domainname"] = $params["sld"] . "." . $params["tld"];
    $postfields["reg_company"] = $params["contactdetails"]["Registrant"]["Company Name"];
    $postfields["reg_fname"] = $params["contactdetails"]["Registrant"]["First Name"];
    $postfields["reg_lname"] = $params["contactdetails"]["Registrant"]["Last Name"];
    $postfields["reg_addr1"] = $params["contactdetails"]["Registrant"]["Address 1"];
    $postfields["reg_addr2"] = $params["contactdetails"]["Registrant"]["Address 2"];
    $postfields["reg_state"] = $params["contactdetails"]["Registrant"]["State"];
    $postfields["reg_city"] = $params["contactdetails"]["Registrant"]["City"];
    $postfields["reg_postcode"] = $params["contactdetails"]["Registrant"]["ZIP Code"];
    $postfields["reg_telephone"] = $params["contactdetails"]["Registrant"]["Phone Number"];
    $postfields["reg_fax"] = $params["contactdetails"]["Registrant"]["Fax Number"];
    $postfields["reg_country"] = $params["contactdetails"]["Registrant"]["Country"];
    $postfields["reg_email"] = $params["contactdetails"]["Registrant"]["Email Address"];
    $postfields["adm_company"] = $params["contactdetails"]["Admin"]["Company Name"];
    $postfields["adm_fname"] = $params["contactdetails"]["Admin"]["First Name"];
    $postfields["adm_lname"] = $params["contactdetails"]["Admin"]["Last Name"];
    $postfields["adm_addr1"] = $params["contactdetails"]["Admin"]["Address 1"];
    $postfields["adm_addr2"] = $params["contactdetails"]["Admin"]["Address 2"];
    $postfields["adm_state"] = $params["contactdetails"]["Admin"]["State"];
    $postfields["adm_city"] = $params["contactdetails"]["Admin"]["City"];
    $postfields["adm_postcode"] = $params["contactdetails"]["Admin"]["ZIP Code"];
    $postfields["adm_telephone"] = $params["contactdetails"]["Admin"]["Phone Number"];
    $postfields["adm_fax"] = $params["contactdetails"]["Admin"]["Fax Number"];
    $postfields["adm_country"] = $params["contactdetails"]["Admin"]["Country"];
    $postfields["adm_email"] = $params["contactdetails"]["Admin"]["Email Address"];
    $postfields["tec_company"] = $params["contactdetails"]["Technical"]["Company Name"];
    $postfields["tec_fname"] = $params["contactdetails"]["Technical"]["First Name"];
    $postfields["tec_lname"] = $params["contactdetails"]["Technical"]["Last Name"];
    $postfields["tec_addr1"] = $params["contactdetails"]["Technical"]["Address 1"];
    $postfields["tec_addr2"] = $params["contactdetails"]["Technical"]["Address 2"];
    $postfields["tec_state"] = $params["contactdetails"]["Technical"]["State"];
    $postfields["tec_city"] = $params["contactdetails"]["Technical"]["City"];
    $postfields["tec_postcode"] = $params["contactdetails"]["Technical"]["ZIP Code"];
    $postfields["tec_telephone"] = $params["contactdetails"]["Technical"]["Phone Number"];
    $postfields["tec_fax"] = $params["contactdetails"]["Technical"]["Fax Number"];
    $postfields["tec_country"] = $params["contactdetails"]["Technical"]["Country"];
    $postfields["tec_email"] = $params["contactdetails"]["Technical"]["Email Address"];
    $postfields["bil_company"] = $params["contactdetails"]["Billing"]["Company Name"];
    $postfields["bil_fname"] = $params["contactdetails"]["Billing"]["First Name"];
    $postfields["bil_lname"] = $params["contactdetails"]["Billing"]["Last Name"];
    $postfields["bil_addr1"] = $params["contactdetails"]["Billing"]["Address 1"];
    $postfields["bil_addr2"] = $params["contactdetails"]["Billing"]["Address 2"];
    $postfields["bil_state"] = $params["contactdetails"]["Billing"]["State"];
    $postfields["bil_city"] = $params["contactdetails"]["Billing"]["City"];
    $postfields["bil_postcode"] = $params["contactdetails"]["Billing"]["ZIP Code"];
    $postfields["bil_telephone"] = $params["contactdetails"]["Billing"]["Phone Number"];
    $postfields["bil_fax"] = $params["contactdetails"]["Billing"]["Fax Number"];
    $postfields["bil_country"] = $params["contactdetails"]["Billing"]["Country"];
    $postfields["bil_email"] = $params["contactdetails"]["Billing"]["Email Address"];
    if (preg_match("/us\$/i", $params["tld"])) {
        $nexus = $params["additionalfields"]["Nexus Category"];
        $countrycode = $params["additionalfields"]["Nexus Country"];
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
        $postfields["purpose"] = $purpose;
        $postfields["nexus"] = $nexus;
        $usorgtype = $params["additionalfields"]["Organization Type"];
        if ($usorgtype == "Permanent resident of U.S") {
            $usorgtype = "C12";
        } else {
            if ($usorgtype == "Entity with office in US") {
                $usorgtype = "C32";
            } else {
                if ($usorgtype == "Entity that regularly engages in lawful activities") {
                    $usorgtype = "C31";
                } else {
                    if ($usorgtype == "Citizen of U.S") {
                        $usorgtype = "C11";
                    } else {
                        if ($usorgtype == "Incorporated within one of the U.S. states") {
                            $usorgtype = "C21";
                        }
                    }
                }
            }
        }
        $individual = $params["companyname"] == "-" ? "I" : "O";
        if ($individual == "O") {
            $postfields["custom_bil3"] = $usorgtype;
            $postfields["custom_tec3"] = $postfields["custom_bil3"];
            $postfields["custom_adm3"] = $postfields["custom_tec3"];
            $postfields["custom_reg3"] = $postfields["custom_adm3"];
        }
    }
    if (preg_match("/sg\$/i", $params["tld"])) {
        if ($params["additionalfields"]["Registrant Type"] == "Individual") {
            $regtype = "2";
        } else {
            $regtype = "1";
        }
        $postfields["custom_reg1"] = $params["additionalfields"]["RCB Singapore ID"];
        $postfields["custom_reg2"] = $usorgtype;
        $postfields["custom_adm1"] = $params["additionalfields"]["RCB Singapore ID"];
        $postfields["custom_adm2"] = $usorgtype;
        $postfields["custom_tec2"] = $usorgtype;
        $postfields["custom_bil2"] = $usorgtype;
        $postfields["proxy"] = 0;
    }
    if (preg_match("/hk\$/i", $params["tld"])) {
        $individual = $params["companyname"] == "-" ? "I" : "O";
        $postfields["ctxtype"] = $individual;
        if ($individual == "I") {
            $postfields["custom_bil3"] = $params["additionalfields"]["Date of Birth"];
            $params["additionalfields"]["Date of Birth"] = $postfields["custom_bil3"];
            $postfields["custom_tec3"] = $params["additionalfields"]["Date of Birth"];
            $postfields["custom_adm3"] = $postfields["custom_tec3"];
            $postfields["custom_reg3"] = $postfields["custom_adm3"];
        }
    }
    if (preg_match("/my\$/i", $params["tld"])) {
        $individual = $params["companyname"] == "-" ? "I" : "O";
        $postfields["ctxtype"] = $individual;
        if ($individual == "I") {
            $postfields["custom_bil3"] = $params["additionalfields"]["Date of Birth"];
            $params["additionalfields"]["Date of Birth"] = $postfields["custom_bil3"];
            $postfields["custom_tec3"] = $params["additionalfields"]["Date of Birth"];
            $postfields["custom_adm3"] = $postfields["custom_tec3"];
            $postfields["custom_reg3"] = $postfields["custom_adm3"];
        }
    }
    $rtype = "Save Contact Details";
    $results = webnic_call($url, $rtype, $postfields, $params);
    if (substr($results[0], 0, 1) == 0) {
    } else {
        return array("error" => $results[0]);
    }
}
function webnic_IDProtectToggle($params)
{
    $otime = date("Y-m-d H:i:s");
    $ochecksum = md5($params["Source"] . $otime . md5($params["Password"]));
    $url = "pn_whoisprivacyacti.jsp";
    $postfields = array();
    $postfields["source"] = $params["Source"];
    $postfields["otime"] = $otime;
    $postfields["ochecksum"] = $ochecksum;
    $postfields["domain"] = $params["sld"] . "." . $params["tld"];
    $postfields["Action"] = $params["protectenable"] ? "act" : "deact";
    $results = webnic_call($url, $postfields, $params);
}
function webnic_call($url, $rtype, $postfields, $params)
{
    if ($params["TestMode"]) {
        $url = "https://ote.webnic.cc/jsp/" . $url;
    } else {
        $url = "https://my.webnic.cc/jsp/" . $url;
    }
    $query_string = "";
    foreach ($postfields as $k => $v) {
        $query_string .= (string) $k . "=" . urlencode($v) . "&";
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 50);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
    $res_data = curl_exec($ch);
    if (curl_errno($ch)) {
        $res_data = "CURL Error: " . curl_errno($ch) . " - " . curl_error($ch);
    }
    curl_close($ch);
    $res_data = trim($res_data);
    $res_array = explode("\n", $res_data);
    logModuleCall("webnic", $rtype, $query_string, $res_data, $res_array, array($params["Source"]));
    return $res_array;
}
function webnic_isCountryInAsia($countrycode)
{
    $asiacountrycodes = array("af", "am", "az", "bh", "bd", "bt", "bn", "kh", "cn", "cx", "cc", "io", "ge", "hk", "in", "id", "ir", "iq", "il", "jp", "jo", "kz", "kp", "kr", "kw", "kg", "la", "lb", "mo", "my", "mv", "mn", "mm", "np", "om", "pk", "ph", "qa", "sa", "sg", "lk", "sy", "tw", "tj", "th", "tr", "tm", "ae", "uz", "vn", "ye");
    return in_array(strtolower($countrycode), $asiacountrycodes);
}

?>