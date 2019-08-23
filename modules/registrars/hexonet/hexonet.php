<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("HEXONET_MODULE_VERSION", "1.0.60");
function hexonet_getConfigArray()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "HEXONET"), "Description" => array("Type" => "System", "Value" => "Don't have a HEXONET Account yet?" . " Get one here: <a target=\"_blank\" href=\"//go.whmcs.com/1405/hexonet-signup\">www.hexonet.net/sign-up</a>"), "Username" => array("Type" => "text", "Size" => "20", "Description" => "Enter your HEXONET Login ID"), "Password" => array("Type" => "password", "Size" => "20", "Description" => "Enter your HEXONET Password "), "TestMode" => array("Type" => "yesno", "Description" => "Connect to OT&amp;E (Test Environment)"), "ProxyServer" => array("Type" => "text", "Description" => "Optional (HTTP(S) Proxy Server)"), "ConvertIDNs" => array("Type" => "dropdown", "Options" => "API,PHP", "Default" => "API", "Description" => "Use API or PHP function (idn_to_ascii)"), "DNSSEC" => array("Type" => "yesno", "Description" => "Display the DNSSEC Management functionality in the domain details"), "TRANSFERLOCK" => array("Type" => "yesno", "Description" => "Locks automatically a domain after a new registration"));
    if (!function_exists("idn_to_ascii")) {
        $configarray["ConvertIDNs"] = array("Type" => "dropdown", "Options" => "API", "Default" => "API", "Description" => "Use API (PHP function idn_to_ascii not available)");
    }
    return $configarray;
}
function hexonet_CheckAvailability($params)
{
    $label = strtolower($params["searchTerm"]);
    if ($params["isIdnDomain"] && !empty($params["punyCodeSearchTerm"])) {
        $label = $params["punyCodeSearchTerm"];
    }
    $tlds = $params["tldsToInclude"];
    foreach ($tlds as $key => $tld) {
        $tlds[$key] = ltrim($tld, ".");
    }
    $domains = array_map(function ($tld) use($label) {
        return $label . "." . $tld;
    }, $tlds);
    $commandCall = array("COMMAND" => "CheckDomains", "DOMAIN" => $domains, "PREMIUMCHANNELS" => "*");
    $result = hexonet_call($commandCall, hexonet_config($params));
    if ($result["CODE"] == 541) {
        throw new WHMCS\Exception($result["DESCRIPTION"]);
    }
    $results = new WHMCS\Domains\DomainLookup\ResultsList();
    $hexAttributes = array_keys($result["PROPERTY"]);
    $hexFlattenResults = array();
    foreach ($domains as $i => $domain) {
        $domainAttributeData = array();
        foreach ($hexAttributes as $attribute) {
            if (is_array($result["PROPERTY"][$attribute])) {
                $attributeData = $result["PROPERTY"][$attribute];
                $domainAttributeData[$attribute] = $attributeData[$i];
            }
        }
        $domainAttributeData["domainName"] = $domain;
        $hexFlattenResults[$domain] = $domainAttributeData;
    }
    foreach ($hexFlattenResults as $domain => $attributes) {
        $domainStatus = $attributes["DOMAINCHECK"];
        $domainParts = explode(".", $domain, 2);
        $domainSearchResult = new WHMCS\Domains\DomainLookup\SearchResult($domainParts[0], $domainParts[1]);
        $domainStatusCode = substr($domainStatus, 0, 3);
        if ($domainStatusCode == "210") {
            $domainSearchResult->setStatus(WHMCS\Domains\DomainLookup\SearchResult::STATUS_NOT_REGISTERED);
        } else {
            if ($domainStatusCode == "549") {
                $domainSearchResult->setStatus(WHMCS\Domains\DomainLookup\SearchResult::STATUS_TLD_NOT_SUPPORTED);
            } else {
                $domainPrice = $attributes["PRICE"];
                $domainReason = $attributes["REASON"];
                if ($domainStatusCode == "211") {
                    $isReserved = true;
                    $isPremium = true;
                    if (stripos($domainReason, "Reserved") !== false) {
                        $isReserved = true;
                        $isPremium = false;
                    } else {
                        if (stripos($domainReason, "Premium") !== false) {
                            $isPremium = true;
                            $isReserved = false;
                        }
                    }
                    if (stripos($domainStatus, "not available") !== false) {
                        $domainSearchResult->setStatus(WHMCS\Domains\DomainLookup\SearchResult::STATUS_REGISTERED);
                    } else {
                        $domainSearchResult->setStatus(WHMCS\Domains\DomainLookup\SearchResult::STATUS_NOT_REGISTERED);
                    }
                    $priceCurrency = $attributes["CURRENCY"];
                    $domainPremiumClass = $attributes["CLASS"];
                    $domainPremiumChannel = $attributes["PREMIUMCHANNEL"];
                    $domainRenew = hexonet_getRenewPrice($params, $domainPremiumClass, $priceCurrency, ltrim($domainParts[1], "."));
                    if ($domainPremiumClass || $domainPremiumChannel) {
                        if ($params["premiumEnabled"]) {
                            if ($isPremium || $isReserved) {
                                $domainSearchResult->setPremiumDomain(true);
                                $premiumPricing = array("register" => $domainPrice, "renew" => $domainRenew, "CurrencyCode" => $priceCurrency);
                                $domainSearchResult->setPremiumCostPricing($premiumPricing);
                            }
                        } else {
                            $domainSearchResult->setStatus(WHMCS\Domains\DomainLookup\SearchResult::STATUS_RESERVED);
                        }
                    }
                } else {
                    $domainSearchResult->setStatus(WHMCS\Domains\DomainLookup\SearchResult::STATUS_UNKNOWN);
                }
            }
        }
        $results->append($domainSearchResult);
    }
    return $results;
}
function hexonet_GetDomainSuggestions($params)
{
    if (empty($params["suggestionSettings"]["suggestions"])) {
        return new WHMCS\Domains\DomainLookup\ResultsList();
    }
    if ($params["isIdnDomain"]) {
        $label = empty($params["punyCodeSearchTerm"]) ? strtolower($params["searchTerm"]) : strtolower($params["punyCodeSearchTerm"]);
    } else {
        $label = strtolower($params["searchTerm"]);
    }
    $tldslist = $params["tldsToInclude"];
    $zones = array();
    foreach ($tldslist as $tld) {
        if (!preg_match("/\\./", $tld)) {
            $zones[] = $tld;
        }
    }
    $command = array("COMMAND" => "QueryDomainSuggestionList", "KEYWORD" => $label, "ZONE" => $zones, "SOURCE" => "ISPAPI-SUGGESTIONS");
    $suggestions = hexonet_call($command, hexonet_config($params));
    $domains = array();
    if ($suggestions["CODE"] == 200) {
        $domains = $suggestions["PROPERTY"]["DOMAIN"];
    }
    $params["suggestions"] = $domains;
    return hexonet_checkavailability($params);
}
function hexonet_DomainSuggestionOptions()
{
    return array("suggestions" => array("FriendlyName" => AdminLang::trans("general.searchTerm"), "Type" => "yesno", "Description" => AdminLang::trans("global.ticktoenable") . " (" . AdminLang::trans("global.recommended") . ")", "Default" => true));
}
function hexonet_GetPremiumPrice(array $params)
{
    $premiumPricing = array();
    return $premiumPricing;
}
function hexonet_getRenewPrice($params, $class, $cur_id, $tld)
{
    session_start();
    if (empty($class)) {
        $dataRenewPrice = WHMCS\Database\Capsule::table("tbldomainpricing")->join("tblpricing", "tbldomainpricing.id", "=", "tblpricing.relid")->where("tbldomainpricing.extension", "=", "." . $tld)->where("tblpricing.type", "=", "domainrenew")->where("tblpricing.currency", "=", $cur_id)->first();
        if (!empty($dataRenewPrice) && !in_array($dataRenewPrice->msetupfee, array("-1", "0"))) {
            return $dataRenewPrice->msetupfee;
        }
        return false;
    }
    if (!preg_match("/\\:/", $class)) {
        $class = "PRICE_CLASS_DOMAIN_" . $class . "_ANNUAL";
        return hexonet_getUserRelationValue($params, $class);
    }
    $p = preg_split("/\\:/", $class);
    $cl = preg_split("/_/", $p[0]);
    $premiummarkupfix_value = hexonet_getUserRelationValue($params, "PRICE_CLASS_DOMAIN_" . $cl[0] . "_" . $cl[1] . "_*_ANNUAL_MARKUP_FIX");
    $premiummarkupvar_value = hexonet_getUserRelationValue($params, "PRICE_CLASS_DOMAIN_" . $cl[0] . "_" . $cl[1] . "_*_ANNUAL_MARKUP_VAR");
    if ($premiummarkupfix_value && $premiummarkupvar_value) {
        $renewprice = $p[1] * (1 + $premiummarkupvar_value / 100) + $premiummarkupfix_value;
        return $renewprice;
    }
    return false;
}
function hexonet_getUserRelationValue($params, $relationtype)
{
    $relations = hexonet_getUserRelations($params);
    $i = 0;
    foreach ($relations["RELATIONTYPE"] as $relation) {
        if ($relation == $relationtype) {
            return $relations["RELATIONVALUE"][$i];
        }
        $i++;
    }
    return false;
}
function hexonet_getUserRelations($params)
{
    $date = new DateTime();
    if (!isset($_SESSION["HEXONETCACHE"]) || $_SESSION["HEXONETCACHE"]["TIMESTAMP"] + 600 < $date->getTimestamp()) {
        $command["COMMAND"] = "StatusUser";
        $response = hexonet_call($command, hexonet_config($params));
        if ($response["CODE"] == 200) {
            $_SESSION["HEXONETCACHE"] = array("TIMESTAMP" => $date->getTimestamp(), "RELATIONS" => $response["PROPERTY"]);
            return $_SESSION["HEXONETCACHE"]["RELATIONS"];
        }
        return false;
    }
    return $_SESSION["HEXONETCACHE"]["RELATIONS"];
}
function hexonet_ClientAreaCustomButtonArray($params)
{
    $buttonarray = array();
    if ($params["DNSSEC"] == "on") {
        $buttonarray["DNSSEC Management"] = "dnssec";
    }
    return $buttonarray;
}
function hexonet_dnssec($params)
{
    $params = $params["original"];
    $error = false;
    $successful = false;
    $domain = $params["sld"] . "." . $params["tld"];
    if (App::isInRequest("submit") && App::getFromRequest("submit") == 1) {
        check_token();
        $command = array("COMMAND" => "ModifyDomain", "DOMAIN" => $domain, "SECDNS-MAXSIGLIFE" => $_POST["MAXSIGLIFE"]);
        $command["SECDNS-DS"] = array();
        if (isset($_POST["SECDNS-DS"])) {
            foreach ($_POST["SECDNS-DS"] as $dnssecrecord) {
                $everything_empty = true;
                foreach ($dnssecrecord as $attribute) {
                    if (!empty($attribute)) {
                        $everything_empty = false;
                    }
                }
                if (!$everything_empty) {
                    array_push($command["SECDNS-DS"], implode(" ", $dnssecrecord));
                }
            }
        }
        if (empty($command["SECDNS-DS"])) {
            unset($command["SECDNS-DS"]);
            $secdnsds = array();
            $command2 = array("COMMAND" => "StatusDomain", "DOMAIN" => $domain);
            $response = hexonet_call($command2, hexonet_config($params));
            if ($response["CODE"] == 200) {
                $secdnsds = isset($response["PROPERTY"]["SECDNS-DS"]) ? $response["PROPERTY"]["SECDNS-DS"] : array();
            }
            $command["DELSECDNS-DS"] = array();
            foreach ($secdnsds as $item) {
                array_push($command["DELSECDNS-DS"], $item);
            }
        }
        $command["SECDNS-KEY"] = array();
        if (isset($_POST["SECDNS-KEY"])) {
            foreach ($_POST["SECDNS-KEY"] as $dnssecrecord) {
                $everything_empty = true;
                foreach ($dnssecrecord as $attribute) {
                    if (!empty($attribute)) {
                        $everything_empty = false;
                    }
                }
                if (!$everything_empty) {
                    array_push($command["SECDNS-KEY"], implode(" ", $dnssecrecord));
                }
            }
        }
        $response = hexonet_call($command, hexonet_config($params));
        if ($response["CODE"] == 200) {
            $successful = $response["DESCRIPTION"];
        } else {
            $error = $response["DESCRIPTION"];
        }
    }
    $secdnsds = array();
    $secdnskey = array();
    $maxsiglife = "";
    $command = array("COMMAND" => "StatusDomain", "DOMAIN" => $domain);
    $response = hexonet_call($command, hexonet_config($params));
    if ($response["CODE"] == 200) {
        $maxsiglife = isset($response["PROPERTY"]["SECDNS-MAXSIGLIFE"]) ? $response["PROPERTY"]["SECDNS-MAXSIGLIFE"][0] : "";
        $secdnsds = isset($response["PROPERTY"]["SECDNS-DS"]) ? $response["PROPERTY"]["SECDNS-DS"] : array();
        $secdnskey = isset($response["PROPERTY"]["SECDNS-KEY"]) ? $response["PROPERTY"]["SECDNS-KEY"] : array();
        $secdnskeynew = array();
        foreach ($secdnskey as $k) {
            if (!empty($k)) {
                $secdnskeynew[] = $k;
            }
        }
        $secdnskey = $secdnskeynew;
    } else {
        $error = $response["DESCRIPTION"];
    }
    $secdnsds_newformat = array();
    foreach ($secdnsds as $ds) {
        list($keytag, $alg, $digesttype, $digest) = preg_split("/\\s+/", $ds);
        array_push($secdnsds_newformat, array("keytag" => $keytag, "alg" => $alg, "digesttype" => $digesttype, "digest" => $digest));
    }
    $secdnskey_newformat = array();
    foreach ($secdnskey as $key) {
        list($flags, $protocol, $alg, $pubkey) = preg_split("/\\s+/", $key);
        array_push($secdnskey_newformat, array("flags" => $flags, "protocol" => $protocol, "alg" => $alg, "pubkey" => $pubkey));
    }
    return array("templatefile" => "dnssec", "vars" => array("error" => $error, "successful" => $successful, "secdnsds" => $secdnsds_newformat, "secdnskey" => $secdnskey_newformat, "maxsiglife" => $maxsiglife));
}
function hexonet_GetRegistrarLock($params)
{
    $values = array();
    $params = $params["original"];
    $domain = $params["sld"] . "." . $params["tld"];
    $command = array("COMMAND" => "StatusDomain", "DOMAIN" => $domain);
    $response = hexonet_call($command, hexonet_config($params));
    if ($response["CODE"] == 200) {
        if (isset($response["PROPERTY"]["TRANSFERLOCK"])) {
            if ($response["PROPERTY"]["TRANSFERLOCK"][0]) {
                return "locked";
            }
            return "unlocked";
        }
        return "";
    }
    $values["error"] = $response["DESCRIPTION"];
    return $values;
}
function hexonet_SaveRegistrarLock($params)
{
    $values = array();
    $params = $params["original"];
    $domain = $params["sld"] . "." . $params["tld"];
    $command = array("COMMAND" => "ModifyDomain", "DOMAIN" => $domain, "TRANSFERLOCK" => $params["lockenabled"] == "locked" ? "1" : "0");
    $response = hexonet_call($command, hexonet_config($params));
    if ($response["CODE"] != 200) {
        $values["error"] = $response["DESCRIPTION"];
    }
    return $values;
}
function hexonet_GetEPPCode($params)
{
    $values = array();
    $params = $params["original"];
    $domain = $params["sld"] . "." . $params["tld"];
    if ($params["tld"] == "de") {
        $command = array("COMMAND" => "DENIC_CreateAuthInfo1", "DOMAIN" => $domain);
        hexonet_call($command, hexonet_config($params));
    }
    if ($params["tld"] == "eu") {
        $command = array("COMMAND" => "RequestDomainAuthInfo", "DOMAIN" => $domain);
        $response = hexonet_call($command, hexonet_config($params));
    } else {
        $command = array("COMMAND" => "StatusDomain", "DOMAIN" => $domain);
        $response = hexonet_call($command, hexonet_config($params));
    }
    if ($response["CODE"] == 200) {
        if (strlen($response["PROPERTY"]["AUTH"][0])) {
            $values["eppcode"] = htmlspecialchars($response["PROPERTY"]["AUTH"][0]);
        } else {
            $values["error"] = "No AuthInfo code assigned to this domain!";
        }
    } else {
        $values["error"] = $response["DESCRIPTION"];
    }
    return $values;
}
function hexonet_GetNameservers($params)
{
    $values = array();
    $params = $params["original"];
    $domain = $params["sld"] . "." . $params["tld"];
    $command = array("COMMAND" => "StatusDomain", "DOMAIN" => $domain);
    $response = hexonet_call($command, hexonet_config($params));
    if ($response["CODE"] == 200) {
        $i = 1;
        foreach ($response["PROPERTY"]["NAMESERVER"] as $nameserver) {
            $nameserver = htmlspecialchars($nameserver);
            $values["ns" . $i] = $nameserver;
            $i++;
        }
    } else {
        $values["error"] = $response["DESCRIPTION"];
    }
    return $values;
}
function hexonet_SaveNameservers($params)
{
    $values = array();
    $params = $params["original"];
    $domain = $params["sld"] . "." . $params["tld"];
    $command = array("COMMAND" => "ModifyDomain", "DOMAIN" => $domain, "NAMESERVER" => array($params["ns1"], $params["ns2"], $params["ns3"], $params["ns4"], $params["ns5"]), "INTERNALDNS" => 1);
    $response = hexonet_call($command, hexonet_config($params));
    if ($response["CODE"] != 200) {
        $values["error"] = $response["DESCRIPTION"];
    }
    return $values;
}
function hexonet_GetDNS($params)
{
    $values = array();
    $params = $params["original"];
    $dnszone = $params["sld"] . "." . $params["tld"] . ".";
    $domain = $params["sld"] . "." . $params["tld"];
    $command = array("COMMAND" => "ConvertIDN", "DOMAIN" => $domain);
    $response = hexonet_call($command, hexonet_config($params));
    $dnszone_idn = $response["PROPERTY"]["ACE"][0] . ".";
    $command = array("COMMAND" => "QueryDNSZoneRRList", "DNSZONE" => $dnszone, "EXTENDED" => 1);
    $response = hexonet_call($command, hexonet_config($params));
    $hostrecords = array();
    if ($response["CODE"] == 200) {
        $i = 0;
        foreach ($response["PROPERTY"]["RR"] as $rr) {
            $fields = explode(" ", $rr);
            $domain = array_shift($fields);
            $ttl = array_shift($fields);
            $class = array_shift($fields);
            $rrtype = array_shift($fields);
            if ($domain == $dnszone) {
                $domain = "@";
            }
            $domain = str_replace("." . $dnszone_idn, "", $domain);
            if ($rrtype == "A") {
                $hostrecords[$i] = array("hostname" => $domain, "type" => $rrtype, "address" => $fields[0]);
                if (preg_match("/^mxe-host-for-ip-(\\d+)-(\\d+)-(\\d+)-(\\d+)\$/i", $domain, $m)) {
                    unset($hostrecords[$i]);
                    $i--;
                }
                $i++;
            }
            if ($rrtype == "AAAA") {
                $hostrecords[$i] = array("hostname" => $domain, "type" => "AAAA", "address" => $fields[0]);
                $i++;
            }
            if ($rrtype == "TXT") {
                $hostrecords[$i] = array("hostname" => $domain, "type" => $rrtype, "address" => implode(" ", $fields));
                $i++;
            }
            if ($rrtype == "SRV") {
                $priority = array_shift($fields);
                $hostrecords[$i] = array("hostname" => $domain, "type" => $rrtype, "address" => implode(" ", $fields), "priority" => $priority);
                $i++;
            }
            if ($rrtype == "CNAME") {
                $hostrecords[$i] = array("hostname" => $domain, "type" => $rrtype, "address" => $fields[0]);
                $i++;
            }
            if ($rrtype == "X-HTTP") {
                if (preg_match("/^\\//", $fields[0])) {
                    $domain .= array_shift($fields);
                }
                $url_type = array_shift($fields);
                if ($url_type == "REDIRECT") {
                    $url_type = "URL";
                }
                $hostrecords[$i] = array("hostname" => $domain, "type" => $url_type, "address" => implode(" ", $fields));
                $i++;
            }
        }
        $command = array("COMMAND" => "QueryDNSZoneRRList", "DNSZONE" => $dnszone, "SHORT" => 1);
        $response = hexonet_call($command, hexonet_config($params));
        if ($response["CODE"] == 200) {
            foreach ($response["PROPERTY"]["RR"] as $rr) {
                $fields = explode(" ", $rr);
                $domain = array_shift($fields);
                $ttl = array_shift($fields);
                $class = array_shift($fields);
                $rrtype = array_shift($fields);
                if ($rrtype == "MX") {
                    if (preg_match("/^mxe-host-for-ip-(\\d+)-(\\d+)-(\\d+)-(\\d+)(\$|\\.)/i", $fields[1], $m)) {
                        $hostrecords[$i] = array("hostname" => $domain, "type" => "MXE", "address" => $m[1] . "." . $m[2] . "." . $m[3] . "." . $m[4]);
                    } else {
                        $hostrecords[$i] = array("hostname" => $domain, "type" => $rrtype, "address" => $fields[1], "priority" => $fields[0]);
                    }
                    $i++;
                }
            }
        }
    } else {
        $values["error"] = $response["DESCRIPTION"];
    }
    return $hostrecords;
}
function hexonet_SaveDNS($params)
{
    $values = array();
    $params = $params["original"];
    $dnszone = $params["sld"] . "." . $params["tld"] . ".";
    $domain = $params["sld"] . "." . $params["tld"];
    $command = array("COMMAND" => "UpdateDNSZone", "DNSZONE" => $dnszone, "INCSERIAL" => 1, "EXTENDED" => 1, "DELRR" => array("% A", "% AAAA", "% CNAME", "% TXT", "% MX", "% X-HTTP", "% X-SMTP", "% SRV"), "ADDRR" => array());
    $mxe_hosts = array();
    foreach ($params["dnsrecords"] as $key => $values) {
        $hostname = $values["hostname"];
        $type = strtoupper($values["type"]);
        $address = $values["address"];
        $priority = $values["priority"];
        if (strlen($hostname) && strlen($address)) {
            if ($type == "A") {
                $command["ADDRR"][] = (string) $hostname . " " . $type . " " . $address;
            }
            if ($type == "AAAA") {
                $command["ADDRR"][] = (string) $hostname . " " . $type . " " . $address;
            }
            if ($type == "CNAME") {
                $command["ADDRR"][] = (string) $hostname . " " . $type . " " . $address;
            }
            if ($type == "TXT") {
                $command["ADDRR"][] = (string) $hostname . " " . $type . " " . $address;
            }
            if ($type == "SRV") {
                if (empty($priority)) {
                    $priority = 0;
                }
                array_push($command["DELRR"], "% SRV");
                $command["ADDRR"][] = (string) $hostname . " " . $type . " " . $priority . " " . $address;
            }
            if ($type == "MXE") {
                $mxpref = 100;
                if (preg_match("/^([0-9]+) (.*)\$/", $address, $m)) {
                    list(, $mxpref, $address) = $m;
                }
                if (preg_match("/^([0-9]+)\$/", $priority)) {
                    $mxpref = $priority;
                }
                if (preg_match("/^(\\d+)\\.(\\d+)\\.(\\d+)\\.(\\d+)\$/", $address, $m)) {
                    $mxe_host = "mxe-host-for-ip-" . $m[1] . "-" . $m[2] . "-" . $m[3] . "-" . $m[4];
                    $ip = $m[1] . "." . $m[2] . "." . $m[3] . "." . $m[4];
                    $mxe_hosts[$ip] = $mxe_host;
                    $command["ADDRR"][] = (string) $hostname . " MX " . $mxpref . " " . $mxe_host;
                } else {
                    $address = (string) $mxpref . " " . $address;
                    $type = "MX";
                }
            }
            if ($type == "MX") {
                $mxpref = 100;
                if (preg_match("/^([0-9]+) (.*)\$/", $address, $m)) {
                    list(, $mxpref, $address) = $m;
                }
                if (preg_match("/^([0-9]+)\$/", $priority)) {
                    $mxpref = $priority;
                }
                $command["ADDRR"][] = (string) $hostname . " " . $type . " " . $mxpref . " " . $address;
            }
            if ($type == "FRAME") {
                $redirect = "FRAME";
                if (preg_match("/^([^\\/]+)(.*)\$/", $hostname, $m)) {
                    $hostname = $m[1];
                    $redirect = $m[2] . " " . $redirect;
                }
                $command["ADDRR"][] = (string) $hostname . " X-HTTP " . $redirect . " " . $address;
            }
            if ($type == "URL") {
                $redirect = "REDIRECT";
                if (preg_match("/^([^\\/]+)(.*)\$/", $hostname, $m)) {
                    $hostname = $m[1];
                    $redirect = $m[2] . " " . $redirect;
                }
                $command["ADDRR"][] = (string) $hostname . " X-HTTP " . $redirect . " " . $address;
            }
        }
    }
    foreach ($mxe_hosts as $address => $hostname) {
        $command["ADDRR"][] = (string) $hostname . " A " . $address;
    }
    $command2 = array("COMMAND" => "QueryDNSZoneRRList", "DNSZONE" => $dnszone, "EXTENDED" => 1);
    $response = hexonet_call($command2, hexonet_config($params));
    if ($response["CODE"] == 200) {
        foreach ($response["PROPERTY"]["RR"] as $rr) {
            $fields = explode(" ", $rr);
            $domain = array_shift($fields);
            $ttl = array_shift($fields);
            $class = array_shift($fields);
            $rrtype = array_shift($fields);
            if ($rrtype == "X-SMTP") {
                $command["ADDRR"][] = $rr;
                $item = preg_grep("/@ MX [0-9 ]* mx.ispapi.net./i", $command["ADDRR"]);
                if (!empty($item)) {
                    $index_arr = array_keys($item);
                    $index = $index_arr[0];
                    unset($command["ADDRR"][$index]);
                    $command["ADDRR"] = array_values($command["ADDRR"]);
                }
            }
        }
    }
    $response = hexonet_call($command, hexonet_config($params));
    if ($response["CODE"] == 545) {
        $creatednszone_command = array("COMMAND" => "ModifyDomain", "DOMAIN" => $domain, "INTERNALDNS" => 1);
        $creatednszone = hexonet_call($creatednszone_command, hexonet_config($params));
        if ($creatednszone["CODE"] == 200) {
            $response = hexonet_call($command, hexonet_config($params));
        }
    }
    if ($response["CODE"] != 200) {
        $values["error"] = $response["DESCRIPTION"];
    }
    return $values;
}
function hexonet_GetEmailForwarding($params)
{
    $values = array();
    $params = $params["original"];
    $dnszone = $params["sld"] . "." . $params["tld"] . ".";
    $command = array("COMMAND" => "QueryDNSZoneRRList", "DNSZONE" => $dnszone, "SHORT" => 1, "EXTENDED" => 1);
    $response = hexonet_call($command, hexonet_config($params));
    $result = array();
    if ($response["CODE"] == 200) {
        foreach ($response["PROPERTY"]["RR"] as $rr) {
            $fields = explode(" ", $rr);
            $domain = array_shift($fields);
            $ttl = array_shift($fields);
            $class = array_shift($fields);
            $rrtype = array_shift($fields);
            if ($rrtype == "X-SMTP" && $fields[1] == "MAILFORWARD") {
                if (preg_match("/^(.*)\\@\$/", $fields[0], $m)) {
                    $address = $m[1];
                    if (!strlen($address)) {
                        $address = "*";
                    }
                }
                $result[] = array("prefix" => $address, "forwardto" => $fields[2]);
            }
        }
    } else {
        $values["error"] = $response["DESCRIPTION"];
    }
    return $result;
}
function hexonet_SaveEmailForwarding($params)
{
    $values = array();
    $params = $params["original"];
    if (is_array($params["prefix"][0])) {
        $params["prefix"][0] = $params["prefix"][0][0];
    }
    if (is_array($params["forwardto"][0])) {
        $params["forwardto"][0] = $params["forwardto"][0][0];
    }
    foreach ($params["prefix"] as $key => $value) {
        $forwardarray[$key]["prefix"] = $params["prefix"][$key];
        $forwardarray[$key]["forwardto"] = $params["forwardto"][$key];
    }
    $dnszone = $params["sld"] . "." . $params["tld"] . ".";
    $command = array("COMMAND" => "UpdateDNSZone", "DNSZONE" => $dnszone, "INCSERIAL" => 1, "EXTENDED" => 1, "DELRR" => array("@ X-SMTP"), "ADDRR" => array());
    foreach ($params["prefix"] as $key => $value) {
        $prefix = $params["prefix"][$key];
        $target = $params["forwardto"][$key];
        if (strlen($prefix) && strlen($target)) {
            $redirect = "MAILFORWARD";
            if ($prefix == "*") {
                $prefix = "";
            }
            $redirect = $prefix . "@ " . $redirect;
            $command["ADDRR"][] = "@ X-SMTP " . $redirect . " " . $target;
        }
    }
    $response = hexonet_call($command, hexonet_config($params));
    if ($response["CODE"] != 200) {
        $values["error"] = $response["DESCRIPTION"];
    }
    return $values;
}
function hexonet_GetContactDetails($params)
{
    $params = $params["original"];
    $domain = $params["sld"] . "." . $params["tld"];
    $values = array();
    $command = array("COMMAND" => "StatusDomain", "DOMAIN" => $domain);
    $response = hexonet_call($command, hexonet_config($params));
    if ($response["CODE"] == 200) {
        $values["Registrant"] = hexonet_get_contact_info($response["PROPERTY"]["OWNERCONTACT"][0], $params);
        $values["Admin"] = hexonet_get_contact_info($response["PROPERTY"]["ADMINCONTACT"][0], $params);
        $values["Technical"] = hexonet_get_contact_info($response["PROPERTY"]["TECHCONTACT"][0], $params);
        $values["Billing"] = hexonet_get_contact_info($response["PROPERTY"]["BILLINGCONTACT"][0], $params);
    }
    return $values;
}
function hexonet_SaveContactDetails($params)
{
    $values = array();
    $origparams = $params;
    $params = $params["original"];
    $domain = $params["domainObj"];
    $tldSegment = $domain->getLastTLDSegment();
    $additionalDomainFields = hexonet_query_additionalfields($params);
    $newRegistrantDetails = $params["contactdetails"]["Registrant"];
    $contactDetails = hexonet_call(array("COMMAND" => "StatusDomain", "DOMAIN" => $domain->getDomain()), hexonet_config($origparams));
    $currentDetails = hexonet_get_contact_info($contactDetails["PROPERTY"]["OWNERCONTACT"][0], $params);
    $command = array("COMMAND" => "ModifyDomain", "DOMAIN" => $domain->getDomain());
    $map = array("OWNERCONTACT0" => "Registrant", "ADMINCONTACT0" => "Admin", "TECHCONTACT0" => "Technical", "BILLINGCONTACT0" => "Billing");
    $unstrippedparams = $_POST;
    foreach ($map as $ctype => $ptype) {
        if (array_key_exists("First Name", $unstrippedparams["contactdetails"][$ptype])) {
            $p = $unstrippedparams["contactdetails"][$ptype];
        } else {
            $p = $params["contactdetails"][$ptype];
        }
        $options = ENT_QUOTES | ENT_XML1;
        $command[$ctype] = array("FIRSTNAME" => html_entity_decode($p["First Name"], $options, "UTF-8"), "LASTNAME" => html_entity_decode($p["Last Name"], $options, "UTF-8"), "ORGANIZATION" => html_entity_decode($p["Company Name"], $options, "UTF-8"), "STREET" => html_entity_decode($p["Address"], $options, "UTF-8"), "CITY" => html_entity_decode($p["City"], $options, "UTF-8"), "STATE" => html_entity_decode($p["State"], $options, "UTF-8"), "ZIP" => html_entity_decode($p["Postcode"], $options, "UTF-8"), "COUNTRY" => html_entity_decode($p["Country"], $options, "UTF-8"), "PHONE" => html_entity_decode($p["Phone"], $options, "UTF-8"), "FAX" => html_entity_decode($p["Fax"], $options, "UTF-8"), "EMAIL" => html_entity_decode($p["Email"], $options, "UTF-8"));
        if (strlen($p["Address 2"])) {
            $command[$ctype]["STREET"] .= " , " . html_entity_decode($p["Address 2"], $options, "UTF-8");
        }
    }
    if ($tldSegment == "it") {
        if ($newRegistrantDetails["First Name"] . " " . $newRegistrantDetails["Last Name"] != $currentDetails["First Name"] . " " . $currentDetails["Last Name"] && empty($newRegistrantDetails["Company Name"])) {
            $commandOverride = array("COMMAND" => "TradeDomain", "DOMAIN" => $domain->getDomain());
        } else {
            if ($newRegistrantDetails["Company Name"] != $currentDetails["Company Name"]) {
                $commandOverride = array("COMMAND" => "TradeDomain", "DOMAIN" => $domain->getDomain());
            } else {
                if ($newRegistrantDetails["Country"] != $currentDetails["Country"]) {
                    $commandOverride = array("COMMAND" => "TradeDomain", "DOMAIN" => $domain->getDomain());
                } else {
                    $commandOverride = array("COMMAND" => "ModifyDomain", "DOMAIN" => $domain->getDomain());
                }
            }
        }
        if (!($additionalDomainFields["Accept Section 3 of .IT registrar contract"] || $additionalDomainFields["Accept Section 5 of .IT registrar contract"] || $additionalDomainFields["Accept Section 6 of .IT registrar contract"] || $additionalDomainFields["Accept Section 7 of .IT registrar contract"])) {
            return array("error" => "You must accept Agreement Sections 3, 5, 6 and 7.");
        }
        $command = array_merge($command, $commandOverride);
    }
    if (in_array($tldSegment, array("ch", "li", "se", "sg"))) {
        if ($newRegistrantDetails["First Name"] . " " . $newRegistrantDetails["Last Name"] != $currentDetails["First Name"] . " " . $currentDetails["Last Name"]) {
            $commandOverride = array("COMMAND" => "TradeDomain", "DOMAIN" => $domain->getDomain());
        } else {
            if ($newRegistrantDetails["Company Name"] != $currentDetails["Company Name"]) {
                $commandOverride = array("COMMAND" => "TradeDomain", "DOMAIN" => $domain->getDomain());
            } else {
                $commandOverride = array("COMMAND" => "ModifyDomain", "DOMAIN" => $domain->getDomain());
            }
        }
        $command = array_merge($command, $commandOverride);
    }
    if ($tldSegment == "se") {
        if (!empty($additionalDomainFields["Identification Number"])) {
            $commandOverride["X-NICSE-IDNUMBER"] = $additionalDomainFields["Identification Number"];
        }
        $command = array_merge($command, $commandOverride);
    }
    if ($tldSegment == "ca") {
        unset($command["X-CA-LEGALTYPE"]);
        if (!empty($additionalDomainFields["Legal Type"])) {
            $legalType = $additionalDomainFields["Legal Type"];
            switch ($legalType) {
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
                case "Canadian Trade Union":
                    $legalType = "TRD";
                    break;
                case "Canadian Political Party":
                    $legalType = "PLT";
                    break;
                case "Canadian Library Archive or Museum":
                    $legalType = "LAM";
                    break;
                case "Trust established in Canada":
                    $legalType = "TRS";
                    break;
                case "Aboriginal Peoples":
                    $legalType = "ABO";
                    break;
                case "Legal Representative of a Canadian Citizen":
                    $legalType = "LGR";
                    break;
                case "Official mark registered in Canada":
                    $legalType = "OMK";
                    break;
            }
            $commandOverride["X-CA-LEGALTYPE"] = $legalType;
        }
        unset($command["OWNERCONTACT0X-CA-DISCLOSE"]);
        if (!empty($additionalDomainFields["WHOIS Opt-out"])) {
            $commandOverride["OWNERCONTACT0X-CA-DISCLOSE"] = 1;
        }
        if (!empty($additionalDomainFields["CIRA Agreement"])) {
            $commandOverride["X-CA-ACCEPT-AGREEMENT-VERSION"] = "2.0";
            $command = array_merge($command, $commandOverride);
        } else {
            return array("error" => "You have to accept the CIRA Agreement.");
        }
    }
    $response = hexonet_call($command, hexonet_config($origparams));
    if ($response["CODE"] != 200) {
        $values["error"] = $response["DESCRIPTION"];
    }
    if ($tldSegment == "se" && $command["COMMAND"] == "TradeDomain") {
        $values["pending"] = true;
        $values["pendingData"] = array("message" => "domains.changePendingFormRequired", "replacement" => array(":form" => "<a href=\"https://www.domainform.net/form/se/search?view=ownerchange\" target=\"_blank\">domainform.net</a>"));
    }
    return $values;
}
function hexonet_RegisterNameserver($params)
{
    $values = array();
    $params = $params["original"];
    $nameserver = $params["nameserver"];
    $command = array("COMMAND" => "AddNameserver", "NAMESERVER" => $nameserver, "IPADDRESS0" => $params["ipaddress"]);
    $response = hexonet_call($command, hexonet_config($params));
    if ($response["CODE"] != 200) {
        $values["error"] = $response["DESCRIPTION"];
    }
    return $values;
}
function hexonet_ModifyNameserver($params)
{
    $values = array();
    $params = $params["original"];
    $nameserver = $params["nameserver"];
    $command = array("COMMAND" => "ModifyNameserver", "NAMESERVER" => $nameserver, "DELIPADDRESS0" => $params["currentipaddress"], "ADDIPADDRESS0" => $params["newipaddress"]);
    $response = hexonet_call($command, hexonet_config($params));
    if ($response["CODE"] != 200) {
        $values["error"] = $response["DESCRIPTION"];
    }
    return $values;
}
function hexonet_DeleteNameserver($params)
{
    $values = array();
    $params = $params["original"];
    $nameserver = $params["nameserver"];
    $command = array("COMMAND" => "DeleteNameserver", "NAMESERVER" => $nameserver);
    $response = hexonet_call($command, hexonet_config($params));
    if ($response["CODE"] != 200) {
        $values["error"] = $response["DESCRIPTION"];
    }
    return $values;
}
function hexonet_IDProtectToggle($params)
{
    $values = array();
    $params = $params["original"];
    $domain = $params["domainObj"];
    $command = array("COMMAND" => "ModifyDomain", "DOMAIN" => $domain->getDomain(), "X-ACCEPT-WHOISTRUSTEE-TAC" => $params["protectenable"] ? "1" : "0");
    if ($domain->getLastTLDSegment() == "ca") {
        $apiParams = array("COMMAND" => "StatusDomain", "DOMAIN" => $domain->getDomain());
        $apiReturn = hexonet_call($apiParams, hexonet_config($params));
        if (isset($apiReturn["PROPERTY"]["X-CA-LEGALTYPE"])) {
            $caLegalType = $apiReturn["PROPERTY"]["X-CA-LEGALTYPE"];
        }
        $protectableCaLegalTypes = array("CCT", "RES", "ABO", "LGT");
        if (in_array($caLegalType, $protectableCaLegalTypes)) {
            $whoisPrivate = "1";
            $commandOverrides = array("X-ACCEPT-WHOISTRUSTEE-TAC" => $whoisPrivate);
            $command = array_merge($command, $commandOverrides);
        } else {
            return array("error" => "LegalType " . $caLegalType[0] . " is not allowed for WHOIS Trustee. Domain ID: " . $params["domainid"]);
        }
    }
    $response = hexonet_call($command, hexonet_config($params));
    if ($response["CODE"] != 200) {
        $values["error"] = $response["DESCRIPTION"];
    }
    return $values;
}
function hexonet_RegisterDomain($params)
{
    $values = array();
    $origparams = $params;
    $premiumDomainsEnabled = (bool) $params["premiumEnabled"];
    $premiumDomainsCost = $params["premiumCost"];
    $params = $params["original"];
    $domain = $origparams["domainObj"];
    $tldSegment = $domain->getLastTLDSegment();
    $registrant = array("FIRSTNAME" => $params["firstname"], "LASTNAME" => $params["lastname"], "ORGANIZATION" => $params["companyname"], "STREET" => $params["address1"], "CITY" => $params["city"], "STATE" => $params["state"], "ZIP" => $params["postcode"], "COUNTRY" => $params["country"], "PHONE" => $params["phonenumber"], "EMAIL" => $params["email"]);
    if (strlen($params["address2"])) {
        $registrant["STREET"] .= " , " . $params["address2"];
    }
    $admin = array("FIRSTNAME" => $params["adminfirstname"], "LASTNAME" => $params["adminlastname"], "ORGANIZATION" => $params["admincompanyname"], "STREET" => $params["adminaddress1"], "CITY" => $params["admincity"], "STATE" => $params["adminstate"], "ZIP" => $params["adminpostcode"], "COUNTRY" => $params["admincountry"], "PHONE" => $params["adminphonenumber"], "EMAIL" => $params["adminemail"]);
    if (strlen($params["adminaddress2"])) {
        $admin["STREET"] .= " , " . $params["adminaddress2"];
    }
    $command = array("COMMAND" => "AddDomain", "DOMAIN" => $domain->getDomain(), "PERIOD" => $params["regperiod"], "NAMESERVER0" => $params["ns1"], "NAMESERVER1" => $params["ns2"], "NAMESERVER2" => $params["ns3"], "NAMESERVER3" => $params["ns4"], "OWNERCONTACT0" => $registrant, "ADMINCONTACT0" => $admin, "TECHCONTACT0" => $admin, "BILLINGCONTACT0" => $admin);
    if ($tldSegment == "it") {
        if (!empty($params["additionalfields"]["Accept Section 3 of .IT registrar contract"])) {
            $command["X-IT-ACCEPT-LIABILITY-TAC"] = 1;
        }
        if (!empty($params["additionalfields"]["Accept Section 5 of .IT registrar contract"])) {
            $command["X-IT-ACCEPT-REGISTRATION-TAC"] = 1;
        }
        if (!empty($params["additionalfields"]["Accept Section 6 of .IT registrar contract"])) {
            $command["X-IT-ACCEPT-DIFFUSION-AND-ACCESSIBILITY-TAC"] = 1;
        }
        if (!empty($params["additionalfields"]["Accept Section 7 of .IT registrar contract"])) {
            $command["X-IT-ACCEPT-EXPLICIT-TAC"] = 1;
        }
        $command["X-IT-PIN"] = $params["additionalfields"]["Tax ID"];
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
        $command["X-IT-REGISTRANT-ENTITY-TYPE"] = $entityNumber;
    } else {
        if ($tldSegment == "ca") {
            $legalType = $params["additionalfields"]["Legal Type"];
            switch ($legalType) {
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
                case "Canadian Trade Union":
                    $legalType = "TRD";
                    break;
                case "Canadian Political Party":
                    $legalType = "PLT";
                    break;
                case "Canadian Library Archive or Museum":
                    $legalType = "LAM";
                    break;
                case "Trust established in Canada":
                    $legalType = "TRS";
                    break;
                case "Aboriginal Peoples":
                    $legalType = "ABO";
                    break;
                case "Legal Representative of a Canadian Citizen":
                    $legalType = "LGR";
                    break;
                case "Official mark registered in Canada":
                    $legalType = "OMK";
                    break;
            }
            $command["X-CA-LEGALTYPE"] = $legalType;
            if (!empty($params["additionalfields"]["CIRA Agreement"])) {
                $command["X-CA-ACCEPT-AGREEMENT-VERSION"] = "2.0";
            }
            if (!empty($params["additionalfields"]["WHOIS Opt-out"])) {
                $command["OWNERCONTACT0X-CA-DISCLOSE"] = 1;
            }
        } else {
            if ($tldSegment == "swiss") {
                $command["COMMAND"] = "AddDomainApplication";
                $command["CLASS"] = "GOLIVE";
                $command["X-CORE-INTENDED-USE"] = $params["additionalfields"]["Core Intended Use"];
                $command["X-SWISS-REGISTRANT-ENTERPRISE-ID"] = $params["additionalfields"]["Registrant Enterprise ID"];
                unset($command["INTERNALDNS"]);
                unset($command["X-ACCEPT-WHOISTRUSTEE-TAC"]);
            } else {
                if ($tldSegment == "nu") {
                    $command["X-REGISTRANT-IDNUMBER"] = $params["additionalfields"]["Identification Number"];
                    if (!empty($params["additionalfields"]["VAT Number"])) {
                        $command["X-VATID"] = $params["additionalfields"]["VAT Number"];
                    }
                } else {
                    if ($tldSegment == "vote" || $tldSegment == "voto") {
                        $command["X-VOTE-ACCEPT-HIGHLY-REGULATED-TAC"] = $params["additionalfields"]["Agreement"];
                    } else {
                        if ($tldSegment == "ro") {
                            if (!empty($params["additionalfields"]["Registration Number"])) {
                                $command["X-REGISTRANT-IDNUMBER"] = $params["additionalfields"]["Registration Number"];
                            }
                            if (!empty($params["additionalfields"]["CNPFiscalCode"])) {
                                $command["X-REGISTRANT-VATID"] = $params["additionalfields"]["CNPFiscalCode"];
                            }
                        } else {
                            if ($tldSegment == "sg") {
                                $command["X-SG-RCBID"] = $params["additionalfields"]["RCB Singapore ID"];
                                $registrantType = $params["additionalfields"]["Registrant Type"];
                                $registrantTypeValue = 0;
                                if ($registrantType == "Individual" && $params["country"] == "SG" || $registrantType == "Organization" && $params["admincountry"] == "SG") {
                                    $registrantTypeValue = 1;
                                }
                                $command["X-SG-ACCEPT-TRUSTEE-TAC"] = $registrantTypeValue;
                                if ($registrantType == "Individual" && $params["admincountry"] == "SG") {
                                    $command["X-ADMIN-IDNUMBER"] = $params["additionalfields"]["RCB Singapore ID"];
                                }
                            } else {
                                if ($tldSegment == "se") {
                                    $command["X-NICSE-IDNUMBER"] = $params["additionalfields"]["Identification Number"];
                                    if (!empty($params["additionalfields"]["VAT"])) {
                                        $command["X-NICSE-VATID"] = $params["additionalfields"]["VAT"];
                                    }
                                } else {
                                    if ($tldSegment == "aero") {
                                        $command["X-AERO-ENS-AUTH-ID"] = $params["additionalfields"][".AERO ID"];
                                        $command["X-AERO-ENS-AUTH-KEY"] = $params["additionalfields"][".AERO Key"];
                                    } else {
                                        if ($tldSegment == "travel") {
                                            if (!empty($params["additionalfields"][".TRAVEL Usage Agreement"])) {
                                                $command["X-TRAVEL-INDUSTRY"] = 1;
                                            }
                                            $command["X-TRAVEL-UIN"] = $params["additionalfields"][".TRAVEL UIN Code"];
                                        } else {
                                            if ($tldSegment == "us") {
                                                $nexusCategory = $params["additionalfields"]["Nexus Category"];
                                                $nexusCountry = $params["additionalfields"]["Nexus Country"];
                                                $appPurpose = $params["additionalfields"]["Application Purpose"];
                                                switch ($appPurpose) {
                                                    case "Business use for profit":
                                                        $appPurpose = "P1";
                                                        break;
                                                    case "Non-profit business":
                                                    case "Club":
                                                    case "Association":
                                                    case "Religious Organization":
                                                        $appPurpose = "P2";
                                                        break;
                                                    case "Personal Use":
                                                        $appPurpose = "P3";
                                                        break;
                                                    case "Educational purposes":
                                                        $appPurpose = "P4";
                                                        break;
                                                    case "Government purposes":
                                                        $appPurpose = "P5";
                                                        break;
                                                }
                                                $command["X-US-NEXUS-APPPURPOSE"] = $appPurpose;
                                                switch ($nexusCategory) {
                                                    case "C11":
                                                    case "C12":
                                                    case "C21":
                                                        $command["X-US-NEXUS-CATEGORY"] = $nexusCategory;
                                                        break;
                                                    case "C31":
                                                    case "C32":
                                                        $command["X-US-NEXUS-CATEGORY"] = $nexusCategory;
                                                        $command["X-US-NEXUS-VALIDATOR"] = $nexusCountry;
                                                        break;
                                                }
                                            } else {
                                                if ($tldSegment == "berlin") {
                                                    if ($params["state"] != "Berlin" || $params["adminstate"] != "Berlin") {
                                                        $command["X-BERLIN-ACCEPT-TRUSTEE-TAC"] = 1;
                                                    }
                                                } else {
                                                    if ($tldSegment == "ruhr") {
                                                        $command["X-RUHR-ACCEPT-TRUSTEE-TAC"] = 1;
                                                    } else {
                                                        if ($tldSegment == "hamburg") {
                                                            if ($params["state"] != "Hamburg" || $params["adminstate"] != "Hamburg") {
                                                                $command["X-HAMBURG-ACCEPT-TRUSTEE-TAC"] = 1;
                                                            }
                                                        } else {
                                                            if ($tldSegment == "bayern") {
                                                                if ($params["state"] != "Bayern" || $params["adminstate"] != "Bayern") {
                                                                    $command["X-BAYERN-ACCEPT-TRUSTEE-TAC"] = 1;
                                                                }
                                                            } else {
                                                                if ($tldSegment == "jp") {
                                                                    if ($params["country"] != "JP" || $params["admincountry"] != "JP") {
                                                                        $command["X-JP-ACCEPT-TRUSTEE-TAC"] = 1;
                                                                    }
                                                                } else {
                                                                    if ($tldSegment == "de") {
                                                                        if ($params["country"] != "DE" || $params["admincountry"] != "DE") {
                                                                            $command["X-DE-ACCEPT-TRUSTEE-TAC"] = 1;
                                                                        }
                                                                    } else {
                                                                        if ($tldSegment == "eu") {
                                                                            if (!WHMCS\Domains\Domain::isValidForEuRegistration($params["country"]) && !WHMCS\Domains\Domain::isValidForEuRegistration($params["admincountry"])) {
                                                                                $command["X-EU-ACCEPT-TRUSTEE-TAC"] = 1;
                                                                            }
                                                                        } else {
                                                                            if ($tldSegment == "jobs") {
                                                                                $command["X-JOBS-COMPANYURL"] = $params["additionalfields"]["Website"];
                                                                            } else {
                                                                                if ($tldSegment == "pro") {
                                                                                    $command["X-PRO-PROFESSION"] = $params["additionalfields"]["Profession"];
                                                                                    $command["X-PRO-AUTHORITY"] = $params["additionalfields"]["Authority"];
                                                                                    $command["X-PRO-AUTHORITYWEBSITE"] = $params["additionalfields"]["Authority Website"];
                                                                                    $command["X-PRO-LICENSENUMBER"] = $params["additionalfields"]["License Number"];
                                                                                } else {
                                                                                    if (in_array($tldSegment, array("fr", "re", "pm", "tf", "wf", "yt"))) {
                                                                                        if ($params["country"] != "FR" || $params["admincountry"] != "FR") {
                                                                                            $command["X-FR-ACCEPT-TRUSTEE-TAC"] = 1;
                                                                                        }
                                                                                        $command["X-FR-REGISTRANT-BIRTH-DATE"] = $params["additionalfields"]["Birthdate"];
                                                                                        $command["X-FR-REGISTRANT-BIRTH-PLACE"] = $params["additionalfields"]["Birthplace City"] . ", " . $params["additionalfields"]["Birthplace Country"];
                                                                                        if (!empty($params["additionalfields"]["Vat Number"])) {
                                                                                            $command["X-FR-REGISTRANT-LEGAL-ID"] = $params["additionalfields"]["VAT Number"];
                                                                                        } else {
                                                                                            if (!empty($params["additionalfields"]["SIRET Number"])) {
                                                                                                $command["X-FR-REGISTRANT-LEGAL-ID"] = $params["additionalfields"]["SIRET Number"];
                                                                                            }
                                                                                        }
                                                                                        $command["X-FR-REGISTRANT-TRADEMARK-NUMBER"] = $params["additionalfields"]["Trademark Number"];
                                                                                        $command["X-FR-REGISTRANT-DUNS-NUMBER"] = $params["additionalfields"]["DUNS Number"];
                                                                                    } else {
                                                                                        if ($tldSegment == "hk") {
                                                                                            if (!empty($params["additionalfields"]["Organizations Document Number"])) {
                                                                                                $command["X-HK-REGISTRANT-DOCUMENT-NUMBER"] = $params["additionalfields"]["Organizations Document Number"];
                                                                                            } else {
                                                                                                if (!empty($params["additionalfields"]["Individuals Document Number"])) {
                                                                                                    $command["X-HK-REGISTRANT-DOCUMENT-NUMBER"] = $params["additionalfields"]["Individuals Document Number"];
                                                                                                }
                                                                                            }
                                                                                            if (!empty($params["additionalfields"]["Organizations Issuing Country"])) {
                                                                                                $command["X-HK-REGISTRANT-DOCUMENT-ORIGIN-COUNTRY"] = $params["additionalfields"]["Organizations Issuing Country"];
                                                                                            } else {
                                                                                                if (!empty($params["additionalfields"]["Individuals Issuing Country"])) {
                                                                                                    $command["X-HK-REGISTRANT-DOCUMENT-ORIGIN-COUNTRY"] = $params["additionalfields"]["Individuals Issuing Country"];
                                                                                                }
                                                                                            }
                                                                                            if ($params["additionalfields"]["Registrant Type"] == "ind") {
                                                                                                $command["X-HK-ACCEPT-INDIVIDUAL-REGISTRATION-TAC"] = 1;
                                                                                            }
                                                                                            $registrantType = $params["additionalfields"]["Registrant Type"];
                                                                                            switch ($registrantType) {
                                                                                                case "ind":
                                                                                                    $registrantDocType = $params["additionalfields"]["Individuals Supporting Documentation"];
                                                                                                    break;
                                                                                                case "org":
                                                                                                    $registrantDocType = $params["additionalfields"]["Organizations Supporting Documentation"];
                                                                                                    break;
                                                                                            }
                                                                                            $command["X-HK-REGISTRANT-DOCUMENT-TYPE"] = $registrantDocType;
                                                                                        } else {
                                                                                            if ($tldSegment == "fi") {
                                                                                                $command["X-FI-ACCEPT-REGISTRATION-TAC"] = 1;
                                                                                            } else {
                                                                                                if ($tldSegment == "quebec") {
                                                                                                    $command["X-CORE-INTENDED-USE"] = $params["additionalfields"]["Intended Use"];
                                                                                                } else {
                                                                                                    if ($tldSegment == "es") {
                                                                                                        $command["X-ES-REGISTRANT-IDENTIFICACION"] = $params["additionalfields"]["ID Form Number"];
                                                                                                        $command["X-ES-REGISTRANT-FORM-JURIDICA"] = $params["additionalfields"]["Legal Form"];
                                                                                                    } else {
                                                                                                        if ($tldSegment == "au") {
                                                                                                            $command["X-AU-REGISTRANT-ID-NUMBER"] = $params["additionalfields"]["Registrant ID"];
                                                                                                            $command["X-AU-REGISTRANT-ID-TYPE"] = $params["additionalfields"]["Registrant ID Type"];
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
                            }
                        }
                    }
                }
            }
        }
    }
    if ($origparams["TRANSFERLOCK"]) {
        $command["TRANSFERLOCK"] = 1;
    }
    if ($params["dnsmanagement"]) {
        $command["INTERNALDNS"] = 1;
    }
    if ($params["idprotection"]) {
        $command["X-ACCEPT-WHOISTRUSTEE-TAC"] = 1;
    }
    if ($premiumDomainsEnabled && !empty($premiumDomainsCost)) {
        $c = array("COMMAND" => "CheckDomains", "DOMAIN" => array($domain->getDomain()), "PREMIUMCHANNELS" => "*");
        $check = hexonet_call($c, hexonet_config($origparams));
        if ($check["CODE"] == 200) {
            $registrar_premium_domain_price = $check["PROPERTY"]["PRICE"][0];
            $registrar_premium_domain_class = empty($check["PROPERTY"]["CLASS"][0]) ? "AFTERMARKET_PURCHASE_" . $check["PROPERTY"]["PREMIUMCHANNEL"][0] : $check["PROPERTY"]["CLASS"][0];
            $registrar_premium_domain_currency = $check["PROPERTY"]["CURRENCY"][0];
            if ($premiumDomainsCost == $registrar_premium_domain_price) {
                $command["COMMAND"] = "AddDomainApplication";
                $command["CLASS"] = $registrar_premium_domain_class;
                $command["PRICE"] = $premiumDomainsCost;
                $command["CURRENCY"] = $registrar_premium_domain_currency;
                unset($command["INTERNALDNS"]);
            }
        }
    }
    $response = hexonet_call($command, hexonet_config($origparams));
    if ($response["CODE"] != 200) {
        $values["error"] = $response["DESCRIPTION"];
    }
    if ($tldSegment == "swiss" && $response["CODE"] == 200) {
        $application_id = $response["PROPERTY"]["APPLICATION"][0];
        $appResponse = "APPLICATION ID <strong>" . $application_id . "</strong> SUCCESSFULLY SUBMITTED." . " STATUS IS PENDING UNTIL THE SWISS REGISTRATION PROCESS IS COMPLETED";
        if (!empty($application_id)) {
            $addDomainNote = "### DO NOT DELETE BELOW THIS LINE ### \n.SWISS ApplicationID: " . $application_id . "\n";
            WHMCS\Database\Capsule::table("tbldomains")->where("id", "=", $params["domainid"])->update(array("additionalnotes" => $addDomainNote));
        }
        $values["pending"] = true;
        $values["pendingMessage"] = $appResponse;
    }
    return $values;
}
function hexonet_TransferDomain($params)
{
    $values = array();
    $origparams = $params;
    $tldSegment = $origparams["domainObj"]->getLastTLDSegment();
    $params = $params["original"];
    $domain = $params["sld"] . "." . $params["tld"];
    $registrant = array("FIRSTNAME" => $params["firstname"], "LASTNAME" => $params["lastname"], "ORGANIZATION" => $params["companyname"], "STREET" => $params["address1"], "CITY" => $params["city"], "STATE" => $params["state"], "ZIP" => $params["postcode"], "COUNTRY" => $params["country"], "PHONE" => $params["phonenumber"], "EMAIL" => $params["email"]);
    if (strlen($params["address2"])) {
        $registrant["STREET"] .= " , " . $params["address2"];
    }
    $admin = array("FIRSTNAME" => $params["adminfirstname"], "LASTNAME" => $params["adminlastname"], "ORGANIZATION" => $params["admincompanyname"], "STREET" => $params["adminaddress1"], "CITY" => $params["admincity"], "STATE" => $params["adminstate"], "ZIP" => $params["adminpostcode"], "COUNTRY" => $params["admincountry"], "PHONE" => $params["adminphonenumber"], "EMAIL" => $params["adminemail"]);
    if (strlen($params["adminaddress2"])) {
        $admin["STREET"] .= " , " . $params["adminaddress2"];
    }
    $command = array("COMMAND" => "TransferDomain", "DOMAIN" => $domain, "PERIOD" => $params["regperiod"], "NAMESERVER0" => $params["ns1"], "NAMESERVER1" => $params["ns2"], "NAMESERVER2" => $params["ns3"], "NAMESERVER3" => $params["ns4"], "OWNERCONTACT0" => $registrant, "ADMINCONTACT0" => $admin, "TECHCONTACT0" => $admin, "BILLINGCONTACT0" => $admin, "AUTH" => $origparams["transfersecret"]);
    $tldsToExclude = array("nu", "dk", "ca", "us", "pt", "no", "se");
    if (in_array($tldSegment, $tldsToExclude)) {
        unset($command["OWNERCONTACT0"]);
        unset($command["ADMINCONTACT0"]);
        unset($command["TECHCONTACT0"]);
        unset($command["BILLINGCONTACT0"]);
    }
    if ($tldSegment == "fr") {
        unset($command["OWNERCONTACT0"]);
        unset($command["BILLINGCONTACT0"]);
    }
    if ($tldSegment == "no" || $tldSegment == "nu") {
        $command["PERIOD"] = 0;
    }
    if (preg_match("/\\.[a-z]{3,}\$/i", $domain)) {
        unset($command["OWNERCONTACT0"]);
        unset($command["ADMINCONTACT0"]);
        unset($command["TECHCONTACT0"]);
        unset($command["BILLINGCONTACT0"]);
    }
    $response = hexonet_call($command, hexonet_config($origparams));
    if (preg_match("/USERTRANSFER/", $response["DESCRIPTION"])) {
        $command["ACTION"] = "USERTRANSFER";
        $response = hexonet_call($command, hexonet_config($origparams));
    }
    if ($response["CODE"] != 200) {
        $values["error"] = $response["DESCRIPTION"];
    }
    return $values;
}
function hexonet_RenewDomain($params)
{
    $values = array();
    $params = $params["original"];
    $domain = $params["sld"] . "." . $params["tld"];
    $command = array("COMMAND" => "RenewDomain", "DOMAIN" => $domain, "PERIOD" => $params["regperiod"]);
    $response = hexonet_call($command, hexonet_config($params));
    if ($response["CODE"] == 510) {
        $command["COMMAND"] = "PayDomainRenewal";
        $response = hexonet_call($command, hexonet_config($params));
    }
    if ($response["CODE"] != 200) {
        $values["error"] = $response["DESCRIPTION"];
    }
    return $values;
}
function hexonet_ReleaseDomain($params)
{
    $values = array();
    $params = $params["original"];
    $domain = $params["sld"] . "." . $params["tld"];
    $target = $params["transfertag"];
    $command = array("COMMAND" => "PushDomain", "DOMAIN" => $domain, "TARGET" => $target);
    $response = hexonet_call($command, hexonet_config($params));
    if ($response["CODE"] != 200) {
        $values["error"] = $response["DESCRIPTION"];
    }
    return $values;
}
function hexonet_RequestDelete($params)
{
    $values = array();
    $params = $params["original"];
    $domain = $params["sld"] . "." . $params["tld"];
    $command = array("COMMAND" => "DeleteDomain", "DOMAIN" => $domain);
    $response = hexonet_call($command, hexonet_config($params));
    if ($response["CODE"] != 200) {
        $values["error"] = $response["DESCRIPTION"];
    }
    return $values;
}
function hexonet_TransferSync($params)
{
    $values = array();
    $domain = $params["sld"] . "." . $params["tld"];
    $command = array("COMMAND" => "StatusDomain", "DOMAIN" => $domain);
    $response = hexonet_call($command, hexonet_config($params));
    if ($response["CODE"] == 200) {
        $values["completed"] = true;
        if ($response["PROPERTY"]["PAIDUNTILDATE"][0] < $response["PROPERTY"]["FAILUREDATE"][0]) {
            $cleanPaidUntilDate = trim($response["PROPERTY"]["PAIDUNTILDATE"][0]);
            $paiduntildate = preg_replace("/ .*/", "", $cleanPaidUntilDate);
            $values["expirydate"] = $paiduntildate;
        } else {
            $cleanAccountingDate = trim($response["PROPERTY"]["ACCOUNTINGDATE"][0]);
            $accountingdate = preg_replace("/ .*/", "", $cleanAccountingDate);
            $values["expirydate"] = $accountingdate;
        }
        if ($params["idprotection"] == "1" || $params["idprotection"] == "on") {
            $command = array("COMMAND" => "ModifyDomain", "DOMAIN" => $domain, "X-ACCEPT-WHOISTRUSTEE-TAC" => "1");
            hexonet_call($command, hexonet_config($params));
        }
    } else {
        if ($response["CODE"] == 545 || $response["CODE"] == 531) {
            $command = array("COMMAND" => "StatusDomainTransfer", "DOMAIN" => $domain);
            $response = hexonet_call($command, hexonet_config($params));
            if ($response["CODE"] == 545 || $response["CODE"] == 531) {
                $values["failed"] = true;
                $values["reason"] = "Transfer Failed";
                $loglist_command = array("COMMAND" => "QueryObjectLogList", "OBJECTCLASS" => "DOMAIN", "OBJECTID" => $domain, "ORDERBY" => "LOGDATEDESC", "LIMIT" => 1);
                $loglist_response = hexonet_call($loglist_command, hexonet_config($params));
                if (isset($loglist_response["PROPERTY"]["LOGINDEX"])) {
                    foreach ($loglist_response["PROPERTY"]["LOGINDEX"] as $index => $logindex) {
                        $type = $loglist_response["PROPERTY"]["OPERATIONTYPE"][$index];
                        $status = $loglist_response["PROPERTY"]["OPERATIONSTATUS"][$index];
                        if ($type == "INBOUND_TRANSFER" && $status == "FAILED") {
                            $logstatus_command = array("COMMAND" => "StatusObjectLog", "LOGINDEX" => $logindex);
                            $logstatus_response = hexonet_call($logstatus_command, hexonet_config($params));
                            if ($logstatus_response["CODE"] == 200) {
                                $values["reason"] = implode("\n", $logstatus_response["PROPERTY"]["OPERATIONINFO"]);
                            }
                        }
                    }
                }
            }
        } else {
            $values["error"] = $response["DESCRIPTION"];
        }
    }
    return $values;
}
function hexonet_Sync($params)
{
    $values = array();
    $domain = $params["sld"] . "." . $params["tld"];
    if ($params["tld"] == "swiss") {
        $domains = WHMCS\Database\Capsule::table("tbldomains")->where("registrar", "=", "hexonet")->where("status", "=", "pending")->where("domain", "like", "%." . $params["tld"])->get(array("additionalnotes"));
        foreach ($domains as $swissDomain) {
            preg_match("/\\.SWISS ApplicationID: (.+?)(?:\$|\\n)/i", $swissDomain->additionalnotes, $appId);
            if (!empty($appId[1])) {
                $command = array("COMMAND" => "StatusDomainApplication", "APPLICATION" => $appId[1]);
                $appResults = hexonet_call($command, hexonet_config($params));
                if ($appResults["CODE"] == 200) {
                    $domainStatus = $appResults["PROPERTY"]["STATUS"][0];
                    if ($domainStatus == "SUCCESSFUL") {
                        $values["active"] = true;
                    } else {
                        if ($domainStatus == "FAILED") {
                            $values["cancelled"] = true;
                        }
                    }
                }
            }
        }
    } else {
        $command = array("COMMAND" => "StatusDomain", "DOMAIN" => $domain);
        $response = hexonet_call($command, hexonet_config($params));
        if ($response["CODE"] == 200) {
            $status = $response["PROPERTY"]["STATUS"][0];
            if (preg_match("/ACTIVE/i", $status)) {
                $values["active"] = true;
            } else {
                if (preg_match("/DELETE/i", $status)) {
                    $cleanExpiry = trim($response["PROPERTY"]["EXPIRATIONDATE"][0]);
                    $expiryDate = preg_replace("/ .*/", "", $cleanExpiry);
                    $values["expirydate"] = $expiryDate;
                }
            }
            if ($response["PROPERTY"]["PAIDUNTILDATE"][0] < $response["PROPERTY"]["FAILUREDATE"][0]) {
                $cleanPaidUntil = trim($response["PROPERTY"]["PAIDUNTILDATE"][0]);
                $paiduntildate = preg_replace("/ .*/", "", $cleanPaidUntil);
                $values["expirydate"] = $paiduntildate;
            } else {
                $cleanAccountingDate = trim($response["PROPERTY"]["ACCOUNTINGDATE"][0]);
                $accountingdate = preg_replace("/ .*/", "", $cleanAccountingDate);
                $values["expirydate"] = $accountingdate;
            }
        } else {
            if (in_array($response["CODE"], array(531, 545))) {
                $values["transferredAway"] = true;
            } else {
                $values["error"] = $response["DESCRIPTION"];
            }
        }
    }
    return $values;
}
function hexonet_get_contact_info($contact, &$params)
{
    if (isset($params["_contact_hash"][$contact])) {
        return $params["_contact_hash"][$contact];
    }
    $domain = $params["sld"] . "." . $params["tld"];
    $values = array();
    $command = array("COMMAND" => "StatusContact", "CONTACT" => $contact);
    $response = hexonet_call($command, hexonet_config($params));
    if (1 || $response["CODE"] == 200) {
        $values["First Name"] = $response["PROPERTY"]["FIRSTNAME"][0];
        $values["Last Name"] = $response["PROPERTY"]["LASTNAME"][0];
        $values["Company Name"] = $response["PROPERTY"]["ORGANIZATION"][0];
        $values["Address"] = $response["PROPERTY"]["STREET"][0];
        $values["Address 2"] = $response["PROPERTY"]["STREET"][1];
        $values["City"] = $response["PROPERTY"]["CITY"][0];
        $values["State"] = $response["PROPERTY"]["STATE"][0];
        $values["Postcode"] = $response["PROPERTY"]["ZIP"][0];
        $values["Country"] = $response["PROPERTY"]["COUNTRY"][0];
        $values["Phone"] = $response["PROPERTY"]["PHONE"][0];
        $values["Fax"] = $response["PROPERTY"]["FAX"][0];
        $values["Email"] = $response["PROPERTY"]["EMAIL"][0];
        if (count($response["PROPERTY"]["STREET"]) < 2 && preg_match("/^(.*) , (.*)/", $response["PROPERTY"]["STREET"][0], $m)) {
            list(, $values["Address"], $values["Address 2"]) = $m;
        }
        if (preg_match("/[.]ca\$/i", $domain) && isset($response["PROPERTY"]["X-CA-LEGALTYPE"])) {
            if (preg_match("/^(CCT|RES|ABO|LGR)\$/i", $response["PROPERTY"]["X-CA-LEGALTYPE"][0])) {
            } else {
                if (!isset($response["PROPERTY"]["ORGANIZATION"]) || !$response["PROPERTY"]["ORGANIZATION"][0]) {
                    $response["PROPERTY"]["ORGANIZATION"] = $response["PROPERTY"]["NAME"];
                }
            }
        }
    }
    $params["_contact_hash"][$contact] = $values;
    return $values;
}
function hexonet_query_additionalfields(&$params)
{
    $additionalDomainFields = WHMCS\Database\Capsule::table("tbldomainsadditionalfields")->where("domainid", "=", $params["domainid"])->pluck("value", "name");
    return $additionalDomainFields;
}
function hexonet_config($params)
{
    $config = array();
    $config["registrar"] = $params["registrar"];
    $config["entity"] = "54cd";
    $config["url"] = "https://coreapi.1api.net/api/call.cgi";
    $config["idns"] = $params["ConvertIDNs"];
    if ($params["TestMode"] == 1 || $params["TestMode"] == "on") {
        $config["entity"] = "1234";
    }
    if (strlen($params["ProxyServer"])) {
        $config["proxy"] = $params["ProxyServer"];
    }
    $config["login"] = $params["Username"];
    $config["password"] = $params["Password"];
    return $config;
}
function hexonet_call($command, $config)
{
    return hexonet_parse_response(hexonet_call_raw($command, $config));
}
function hexonet_call_raw($command, $config)
{
    $args = array();
    $url = $config["url"];
    if (isset($config["login"])) {
        $args["s_login"] = $config["login"];
    }
    if (isset($config["password"])) {
        $args["s_pw"] = html_entity_decode($config["password"], ENT_QUOTES);
    }
    if (isset($config["user"])) {
        $args["s_user"] = $config["user"];
    }
    if (isset($config["entity"])) {
        $args["s_entity"] = $config["entity"];
    }
    $args["s_command"] = hexonet_encode_command($command);
    if (1) {
        $new_command = array();
        foreach (explode("\n", $args["s_command"]) as $line) {
            if (preg_match("/^([^\\=]+)\\=(.*)/", $line, $m)) {
                $new_command[strtoupper(trim($m[1]))] = trim($m[2]);
            }
        }
        if (strtoupper($new_command["COMMAND"]) != "CONVERTIDN") {
            $replace = array();
            $domains = array();
            foreach ($new_command as $k => $v) {
                if (preg_match("/^(DOMAIN|NAMESERVER|DNSZONE)([0-9]*)\$/i", $k) && preg_match("/[^a-z0-9\\.\\- ]/i", $v)) {
                    $replace[] = $k;
                    $domains[] = $v;
                }
            }
            if (count($replace)) {
                if ($config["idns"] == "PHP") {
                    foreach ($replace as $index => $k) {
                        $new_command[$k] = hexonet_to_punycode($new_command[$k]);
                    }
                } else {
                    $r = hexonet_call(array("COMMAND" => "ConvertIDN", "DOMAIN" => $domains), $config);
                    if ($r["CODE"] == 200 && isset($r["PROPERTY"]["ACE"])) {
                        foreach ($replace as $index => $k) {
                            $new_command[$k] = $r["PROPERTY"]["ACE"][$index];
                        }
                        $args["s_command"] = hexonet_encode_command($new_command);
                    }
                }
            }
        }
    }
    $config["curl"] = curl_init($url);
    if ($config["curl"] === false) {
        return "[RESPONSE]\nCODE=423\nAPI access error: curl_init failed\nEOF\n";
    }
    $postfields = array();
    foreach ($args as $key => $value) {
        $postfields[] = urlencode($key) . "=" . urlencode($value);
    }
    $postfields = implode("&", $postfields);
    curl_setopt($config["curl"], CURLOPT_POST, 1);
    curl_setopt($config["curl"], CURLOPT_POSTFIELDS, $postfields);
    curl_setopt($config["curl"], CURLOPT_HEADER, 0);
    curl_setopt($config["curl"], CURLOPT_RETURNTRANSFER, 1);
    if (strlen($config["proxy"])) {
        curl_setopt($config["curl"], CURLOPT_PROXY, $config["proxy"]);
    }
    curl_setopt($config["curl"], CURLOPT_USERAGENT, "HEXONET/" . HEXONET_MODULE_VERSION . " WHMCS/" . App::getVersion()->getMajor() . "." . App::getVersion()->getMinor());
    curl_setopt($config["curl"], CURLOPT_REFERER, App::getSystemURL(true));
    $response = curl_exec($config["curl"]);
    logModuleCall($config["registrar"], $command["COMMAND"], $args["s_command"], $response);
    return $response;
}
function hexonet_to_punycode($domain)
{
    if (!strlen($domain)) {
        return $domain;
    }
    if (preg_match("/^[a-z0-9\\.\\-]+\$/i", $domain)) {
        return $domain;
    }
    if (function_exists("idn_to_ascii")) {
        $punycode = idn_to_ascii($domain);
        if (strlen($punycode)) {
            return $punycode;
        }
    }
    return $domain;
}
function hexonet_encode_command($commandarray)
{
    if (!is_array($commandarray)) {
        return $commandarray;
    }
    $command = "";
    foreach ($commandarray as $k => $v) {
        if (is_array($v)) {
            $v = hexonet_encode_command($v);
            $l = explode("\n", trim($v));
            foreach ($l as $line) {
                $command .= (string) $k . $line . "\n";
            }
        } else {
            $v = preg_replace("/\r|\n/", "", $v);
            $command .= (string) $k . "=" . $v . "\n";
        }
    }
    return $command;
}
function hexonet_parse_response($response)
{
    if (is_array($response)) {
        return $response;
    }
    $hash = array("PROPERTY" => array(), "CODE" => "423", "DESCRIPTION" => "Empty response from API");
    if (!$response) {
        return $hash;
    }
    $rlist = explode("\n", $response);
    foreach ($rlist as $item) {
        if (preg_match("/^([^\\=]*[^\t\\= ])[\t ]*=[\t ]*(.*)\$/", $item, $m)) {
            list(, $attr, $value) = $m;
            $value = preg_replace("/[\t ]*\$/", "", $value);
            if (preg_match("/^property\\[([^\\]]*)\\]/i", $attr, $m)) {
                $prop = strtoupper($m[1]);
                $prop = preg_replace("/\\s/", "", $prop);
                if (in_array($prop, array_keys($hash["PROPERTY"]))) {
                    array_push($hash["PROPERTY"][$prop], $value);
                } else {
                    $hash["PROPERTY"][$prop] = array($value);
                }
            } else {
                $hash[strtoupper($attr)] = $value;
            }
        }
    }
    if (!$hash["CODE"] || !$hash["DESCRIPTION"]) {
        $hash = array("PROPERTY" => array(), "CODE" => "423", "DESCRIPTION" => "Invalid response from API");
    }
    return $hash;
}

?>