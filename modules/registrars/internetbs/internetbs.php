<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("API_SERVER_URL", "https://api.internet.bs/");
define("API_TESTSERVER_URL", "https://testapi.internet.bs/");
$internetbs_last_error = NULL;
function internetbs_getLastError()
{
    global $internetbs_last_error;
    return $internetbs_last_error;
}
function internetbs_runCommand($commandUrl, $postData)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $commandUrl);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, "Internet.bs WHMCS module V2.4.1");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    $data = curl_exec($ch);
    global $internetbs_last_error;
    $internetbs_last_error = curl_error($ch);
    curl_close($ch);
    $parsedResult = internetbs_parseResult($data);
    $action = str_replace(array(API_TESTSERVER_URL, API_SERVER_URL), "", $commandUrl);
    logModuleCall("InternetBS", $action, $postData, $data, $parsedResult, array($postData["apikey"], $postData["password"]));
    return $data === false ? false : $parsedResult;
}
function internetbs_getConnectionErrorMessage($message)
{
    return "Cannot connect to server. [" . $message . "]";
}
function internetbs_getConfigArray()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "Internet.bs"), "Username" => array("Type" => "text", "Size" => "50", "Description" => "Enter your Internet.bs ApiKey here"), "Password" => array("Type" => "password", "Size" => "50", "Description" => "Enter your Internet.bs password here"), "TestMode" => array("Type" => "yesno", "Description" => "Check this checkbox if you want to connect to the test server"), "SyncNextDueDate" => array("Type" => "yesno", "Description" => "Tick this box if you want the expiry date sync script to update both expiry and next due dates (cron must be configured). If left unchecked it will only update the domain expiration date."));
    return $configarray;
}
function internetbs_parseResult($data)
{
    $result = array();
    $arr = explode("\n", $data);
    foreach ($arr as $str) {
        list($varName, $value) = explode("=", $str, 2);
        $varName = trim($varName);
        $value = trim($value);
        $result[$varName] = $value;
    }
    return $result;
}
function internetbs_GetNameservers($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domainName = $sld . "." . $tld;
    $apiServerUrl = $testmode == "on" ? API_TESTSERVER_URL : API_SERVER_URL;
    $commandUrl = $apiServerUrl . "Domain/Info";
    $data = array("apikey" => $username, "password" => $password, "domain" => $domainName);
    $result = internetbs_runcommand($commandUrl, $data);
    $errorMessage = internetbs_getlasterror();
    if ($result === false) {
        $values["error"] = internetbs_getconnectionerrormessage($errorMessage);
    } else {
        if ($result["status"] == "FAILURE") {
            $values["error"] = $result["message"];
        } else {
            for ($i = 0; isset($result["nameserver_" . $i]); $i++) {
                $values["ns" . ($i + 1)] = $result["nameserver_" . $i];
            }
        }
    }
    return $values;
}
function internetbs_SaveNameservers($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $nameserver1 = $params["ns1"];
    $nameserver2 = $params["ns2"];
    $nameserver3 = $params["ns3"];
    $nameserver4 = $params["ns4"];
    $nameserver5 = $params["ns5"];
    $nslist = array();
    for ($i = 1; $i <= 5; $i++) {
        if (isset($params["ns" . $i])) {
            if (isset($params["ns" . $i . "_ip"]) && strlen($params["ns" . $i . "_ip"])) {
                $params["ns" . $i] .= " " . $params["ns" . $i . "_ip"];
            }
            array_push($nslist, $params["ns" . $i]);
        }
    }
    $domainName = $sld . "." . $tld;
    $apiServerUrl = $testmode == "on" ? API_TESTSERVER_URL : API_SERVER_URL;
    $commandUrl = $apiServerUrl . "Domain/Update";
    $data = array("apikey" => $username, "password" => $password, "domain" => $domainName, "ns_list" => trim(implode(",", $nslist), ","));
    $result = internetbs_runcommand($commandUrl, $data);
    $errorMessage = internetbs_getlasterror();
    if ($result === false) {
        $values["error"] = internetbs_getconnectionerrormessage($errorMessage);
    } else {
        if ($result["status"] == "FAILURE") {
            $values["error"] = $result["message"];
        }
    }
    return $values;
}
function internetbs_GetRegistrarLock($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domainName = $sld . "." . $tld;
    $apiServerUrl = $testmode == "on" ? API_TESTSERVER_URL : API_SERVER_URL;
    $commandUrl = $apiServerUrl . "Domain/RegistrarLock/Status";
    $data = array("apikey" => $username, "password" => $password, "domain" => $domainName);
    $result = internetbs_runcommand($commandUrl, $data);
    $errorMessage = internetbs_getlasterror();
    if ($result === false) {
        $values["error"] = internetbs_getconnectionerrormessage($errorMessage);
    } else {
        if ($result["status"] == "SUCCESS") {
            if ($result["registrar_lock_status"] == "LOCKED") {
                $lockstatus = "locked";
            } else {
                $lockstatus = "unlocked";
            }
        }
    }
    return strlen($lockstatus) ? $lockstatus : $values;
}
function internetbs_SaveRegistrarLock($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domainName = $sld . "." . $tld;
    $apiServerUrl = $testmode == "on" ? API_TESTSERVER_URL : API_SERVER_URL;
    if ($params["lockenabled"] == "locked") {
        $resourcePath = "Domain/RegistrarLock/Enable";
    } else {
        $resourcePath = "Domain/RegistrarLock/Disable";
    }
    $commandUrl = $apiServerUrl . $resourcePath;
    $data = array("apikey" => $username, "password" => $password, "domain" => $domainName);
    $result = internetbs_runcommand($commandUrl, $data);
    $errorMessage = internetbs_getlasterror();
    if ($result === false) {
        $values["error"] = internetbs_getconnectionerrormessage($errorMessage);
    } else {
        if ($result["status"] == "FAILURE") {
            $values["error"] = $result["message"];
        }
    }
    return $values;
}
function internetbs_GetEmailForwarding($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domainName = $sld . "." . $tld;
    $apiServerUrl = $testmode == "on" ? API_TESTSERVER_URL : API_SERVER_URL;
    $commandUrl = $apiServerUrl . "Domain/EmailForward/List";
    $data = array("apikey" => $username, "password" => $password, "domain" => $domainName);
    $result = internetbs_runcommand($commandUrl, $data);
    $errorMessage = internetbs_getlasterror();
    if ($result === false) {
        $values["error"] = internetbs_getconnectionerrormessage($errorMessage);
    } else {
        if ($result["status"] == "FAILURE") {
            $values["error"] = $result["message"];
        } else {
            $totalRules = $result["total_rules"];
            for ($i = 1; $i <= $totalRules; $i++) {
                list($prefix, $domainName) = explode("@", $result["rule_" . $i . "_source"]);
                $values[$i]["prefix"] = $prefix;
                $values[$i]["forwardto"] = $result["rule_" . $i . "_destination"];
            }
        }
    }
    return $values;
}
function internetbs_SaveEmailForwarding($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domainName = $sld . "." . $tld;
    $apiServerUrl = $testmode == "on" ? API_TESTSERVER_URL : API_SERVER_URL;
    $data = array("apikey" => $username, "password" => $password);
    $errorMessages = "";
    $rules = internetbs_getemailforwarding($params);
    if (is_array($rules)) {
        foreach ($rules as $rule) {
            $source = $rule["prefix"] . "@" . $domainName;
            $source = urlencode($source);
            $cmdData = array("source" => $source);
            $cmdData = array_merge($cmdData, $data);
            $cmd = $apiServerUrl . "Domain/EmailForward/Remove";
            $error = "";
            internetbs_runcommand($cmd, $cmdData);
        }
    }
    foreach ($params["prefix"] as $key => $value) {
        $from = $params["prefix"][$key];
        $to = $params["forwardto"][$key];
        if (trim($to) == "") {
            continue;
        }
        $data["source"] = urlencode($from . "@" . $domainName);
        $data["destination"] = urlencode($to);
        $commandUrl = $apiServerUrl . "Domain/EmailForward/Add";
        $result = internetbs_runcommand($commandUrl, $data);
        $errorMessage = internetbs_getlasterror();
        if ($result === false) {
            $errorMessages .= internetbs_getconnectionerrormessage($errorMessage);
        }
    }
    if (strlen($errorMessages)) {
        $values["error"] = $errorMessages;
    }
    return $values;
}
function internetbs_GetDNS($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domainName = $sld . "." . $tld;
    $apiServerUrl = $testmode == "on" ? API_TESTSERVER_URL : API_SERVER_URL;
    $commandUrl = $apiServerUrl . "Domain/DnsRecord/List";
    $data = array("apikey" => $username, "password" => $password, "domain" => $domainName);
    $result = internetbs_runcommand($commandUrl, $data);
    $errorMessage = internetbs_getlasterror();
    if ($result === false) {
        $values["error"] = internetbs_getconnectionerrormessage($errorMessage);
    } else {
        if (is_array($result)) {
            $keys = array_keys($result);
            $temp = 0;
            foreach ($keys as $key) {
                if (strpos($key, "records_") === 0) {
                    $recNo = substr($key, 8);
                    $recNo = substr($recNo, 0, strpos($recNo, "_"));
                    if ($temp < $recNo) {
                        $temp = $recNo;
                    }
                }
            }
        }
        $hostrecords = array();
        $totalRecords = $temp + 1;
        for ($i = 0; $i < $totalRecords; $i++) {
            $recordType = "";
            if (isset($result["records_" . $i . "_type"])) {
                $recordType = trim($result["records_" . $i . "_type"]);
            }
            if (!in_array(strtolower($recordType), array("a", "mx", "cname", "txt"))) {
                continue;
            }
            if (isset($result["records_" . $i . "_name"])) {
                $recordHostname = $result["records_" . $i . "_name"];
                $recordHostname = strrev(substr(strrev($recordHostname), mb_strlen($domainName, "ASCII") + 1));
            }
            if (isset($result["records_" . $i . "_value"])) {
                $recordAddress = $result["records_" . $i . "_value"];
            }
            if (isset($result["records_" . $i . "_name"])) {
                $hostrecords[] = array("hostname" => $recordHostname, "type" => $recordType, "address" => $recordAddress);
            }
        }
        $commandUrl = $apiServerUrl . "Domain/UrlForward/List";
        $data = array("apikey" => $username, "password" => $password, "domain" => $domainName);
        $result = internetbs_runcommand($commandUrl, $data);
        $errorMessage = internetbs_getlasterror();
        if ($result === false) {
            $values["error"] = internetbs_getconnectionerrormessage($errorMessage);
        } else {
            $totalRecords = (int) $result["total_rules"] + 1;
            for ($i = 0; $i < $totalRecords; $i++) {
                $recordType = "";
                if (isset($result["rule_" . $i . "_isframed"])) {
                    $recordType = trim($result["rule_" . $i . "_isframed"]) == "YES" ? "FRAME" : "URL";
                }
                if (isset($result["rule_" . $i . "_source"])) {
                    $recordHostname = $result["rule_" . $i . "_source"];
                    $recordHostname = strrev(substr(strrev($recordHostname), mb_strlen($domainName, "ASCII") + 1));
                }
                if (isset($result["rule_" . $i . "_destination"])) {
                    $recordAddress = $result["rule_" . $i . "_destination"];
                }
                if (isset($result["rule_" . $i . "_source"])) {
                    $hostrecords[] = array("hostname" => $recordHostname, "type" => $recordType, "address" => $recordAddress);
                }
            }
        }
    }
    return count($hostrecords) ? $hostrecords : $values;
}
function internetbs_SaveDNS($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domainName = $sld . "." . $tld;
    $apiServerUrl = $testmode == "on" ? API_TESTSERVER_URL : API_SERVER_URL;
    $data = array("apikey" => $username, "password" => $password);
    $errorMessages = "";
    $recs = internetbs_getdns($params);
    if (is_array($recs)) {
        foreach ($recs as $r) {
            $source = $r["hostname"] . "." . $domainName;
            $source = trim($source, ". ");
            $type = $r["type"];
            $remParams = array();
            if ($type == "FRAME" || $type == "URL") {
                $cmdPath = "Domain/UrlForward/Remove";
                $remParams["source"] = $source;
            } else {
                $cmdPath = "Domain/DnsRecord/Remove";
                $remParams["FullRecordName"] = $source;
                $remParams["type"] = $type;
            }
            $remParams = array_merge($remParams, $data);
            $cmdPath = $apiServerUrl . $cmdPath;
            internetbs_runcommand($cmdPath, $remParams);
        }
    }
    foreach ($params["dnsrecords"] as $key => $values) {
        $hostname = $values["hostname"];
        $type = $values["type"];
        $address = $values["address"];
        if (trim($hostname) === "" && trim($address) == "") {
            continue;
        }
        if ($hostname != $domainName && strpos($hostname, "." . $domainName) === false) {
            $hostname = $hostname . "." . $domainName;
        }
        $cmdData = array();
        if (!($type == "URL" || $type == "FRAME")) {
            $cmdData["fullrecordname"] = trim($hostname, ". ");
            $cmdData["type"] = $type;
            $cmdData["value"] = $address;
            $commandUrl = $apiServerUrl . "Domain/DnsRecord/Add";
        } else {
            $cmdData["source"] = trim($hostname, ". ");
            $cmdData["isFramed"] = $type == "FRAME" ? "YES" : "NO";
            $cmdData["Destination"] = $address;
            $commandUrl = $apiServerUrl . "Domain/UrlForward/Add";
        }
        $cmdData = array_merge($data, $cmdData);
        $result = internetbs_runcommand($commandUrl, $cmdData);
        $errorMessage = internetbs_getlasterror();
        if ($result === false) {
            $errorMessages .= internetbs_getconnectionerrormessage($errorMessage);
        }
    }
    if (strlen($errorMessages)) {
        $values["error"] = $errorMessages;
    }
    return $values;
}
function internetbs_RegisterDomain($params)
{
    $params = internetbs_get_utf8_parameters($params);
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $regperiod = intval($params["regperiod"]);
    $nameserver1 = $params["ns1"];
    $nameserver2 = $params["ns2"];
    $nameserver3 = $params["ns3"];
    $nameserver4 = $params["ns4"];
    $nameserver5 = $params["ns5"];
    $RegistrantFirstName = $params["firstname"];
    $RegistrantLastName = $params["lastname"];
    $RegistrantCompany = trim($params["companyname"]);
    $RegistrantAddress1 = $params["address1"];
    $RegistrantAddress2 = $params["address2"];
    $RegistrantCity = $params["city"];
    $RegistrantStateProvince = $params["state"];
    $RegistrantPostalCode = $params["postcode"];
    $RegistrantCountry = $params["country"];
    $RegistrantEmailAddress = $params["email"];
    $RegistrantPhone = internetbs_reformatPhone($params["phonenumber"], $params["country"]);
    $AdminFirstName = $params["adminfirstname"];
    $AdminLastName = $params["adminlastname"];
    $AdminCompany = trim($params["admincompanyname"]);
    $AdminAddress1 = $params["adminaddress1"];
    $AdminAddress2 = $params["adminaddress2"];
    $AdminCity = $params["admincity"];
    $AdminStateProvince = $params["adminstate"];
    $AdminPostalCode = $params["adminpostcode"];
    $AdminCountry = $params["admincountry"];
    $AdminEmailAddress = $params["adminemail"];
    $AdminPhone = internetbs_reformatPhone($params["adminphonenumber"], $params["admincountry"]);
    $domainName = $sld . "." . $tld;
    $apiServerUrl = $testmode == "on" ? API_TESTSERVER_URL : API_SERVER_URL;
    $commandUrl = $apiServerUrl . "Domain/Create";
    $nslist = array();
    for ($i = 1; $i <= 5; $i++) {
        if (isset($params["ns" . $i])) {
            array_push($nslist, $params["ns" . $i]);
        }
    }
    $data = array("apikey" => $username, "password" => $password, "domain" => $domainName, "registrant_firstname" => $RegistrantFirstName, "registrant_lastname" => $RegistrantLastName, "registrant_street" => $RegistrantAddress1, "registrant_street2" => $RegistrantAddress2, "registrant_city" => $RegistrantCity, "registrant_countrycode" => $RegistrantCountry, "registrant_postalcode" => $RegistrantPostalCode, "registrant_email" => $RegistrantEmailAddress, "registrant_phonenumber" => $RegistrantPhone, "technical_firstname" => $AdminFirstName, "technical_lastname" => $AdminLastName, "technical_street" => $AdminAddress1, "technical_street2" => $AdminAddress2, "technical_city" => $AdminCity, "technical_countrycode" => $AdminCountry, "technical_postalcode" => $AdminPostalCode, "technical_email" => $AdminEmailAddress, "technical_phonenumber" => $AdminPhone, "admin_firstname" => $AdminFirstName, "admin_lastname" => $AdminLastName, "admin_street" => $AdminAddress1, "admin_street2" => $AdminAddress2, "admin_city" => $AdminCity, "admin_countrycode" => $AdminCountry, "admin_postalcode" => $AdminPostalCode, "admin_email" => $AdminEmailAddress, "admin_phonenumber" => $AdminPhone, "billing_firstname" => $AdminFirstName, "billing_lastname" => $AdminLastName, "billing_street" => $AdminAddress1, "billing_street2" => $AdminAddress2, "billing_city" => $AdminCity, "billing_countrycode" => $AdminCountry, "billing_postalcode" => $AdminPostalCode, "billing_email" => $AdminEmailAddress, "billing_phonenumber" => $AdminPhone);
    if (!empty($RegistrantCompany)) {
        $data["Registrant_Organization"] = $RegistrantCompany;
    }
    if (!empty($AdminCompany)) {
        $data["technical_Organization"] = $AdminCompany;
        $data["admin_Organization"] = $AdminCompany;
        $data["billing_Organization"] = $AdminCompany;
    }
    if (count($nslist)) {
        $data["ns_list"] = trim(implode(",", $nslist), ",");
    }
    if ($params["idprotection"]) {
        $data["privateWhois"] = "FULL";
    }
    $extarr = explode(".", $tld);
    $ext = array_pop($extarr);
    if ($tld == "eu" || $tld == "be" || $ext == "uk") {
        $data["registrant_language"] = isset($params["additionalfields"]["Language"]) ? $params["additionalfields"]["Language"] : "en";
    }
    if ($tld == "eu") {
        $europianLanguages = array("cs", "da", "de", "el", "en", "es", "et", "fi", "fr", "hu", "it", "lt", "lv", "mt", "nl", "pl", "pt", "sk", "sl", "sv", "ro", "bg", "ga");
        if (!in_array($data["registrant_language"], $europianLanguages)) {
            $data["registrant_language"] = "en";
        }
        $europianCountries = array("AT", "AX", "BE", "BG", "CZ", "CY", "DE", "DK", "ES", "EE", "FI", "FR", "GR", "GB", "GF", "GI", "GP", "HU", "IE", "IT", "LT", "LU", "LV", "MT", "MQ", "NL", "PL", "PT", "RE", "RO", "SE", "SK", "SI");
        if (!in_array($RegistrantCountry, $europianCountries)) {
            $RegistrantCountry = "IT";
        }
        $data["registrant_countrycode"] = $RegistrantCountry;
    }
    if ($tld == "be") {
        if (!in_array($data["registrant_language"], array("en", "fr", "nl"))) {
            $data["registrant_language"] = "en";
        }
        if (!in_array($RegistrantCountry, array("AF", "AX", "AL", "DZ", "AS", "AD", "AO", "AI", "AQ", "AG", "AR", "AM", "AW", "AU", "AT", "AZ", "BS", "BH", "BD", "BB", "BY", "BE", "BZ", "BJ", "BM", "BT", "BO", "BA", "BW", "BV", "BR", "IO", "VG", "BN", "BG", "BF", "BI", "KH", "CM", "CA", "CV", "KY", "CF", "TD", "CL", "CN", "CX", "CC", "CO", "KM", "CG", "CK", "CR", "HR", "CU", "CY", "CZ", "CD", "DK", "DJ", "DM", "DO", "TL", "EC", "EG", "SV", "GQ", "ER", "EE", "ET", "FK", "FO", "FM", "FJ", "FI", "FR", "GF", "PF", "TF", "GA", "GM", "GE", "DE", "GH", "GI", "GR", "GL", "GD", "GP", "GU", "GT", "GN", "GW", "GY", "HT", "HM", "HN", "HK", "HU", "IS", "IN", "ID", "IR", "IQ", "IE", "IM", "IL", "IT", "CI", "JM", "JP", "JO", "KZ", "KE", "KI", "KW", "KG", "LA", "LV", "LB", "LS", "LR", "LY", "LI", "LT", "LU", "MO", "MK", "MG", "MW", "MY", "MV", "ML", "MT", "MH", "MQ", "MR", "MU", "YT", "MX", "MD", "MC", "MN", "ME", "MS", "MA", "MZ", "MM", "NA", "NR", "NP", "NL", "AN", "NC", "NZ", "NI", "NE", "NG", "NU", "NF", "KP", "MP", "NO", "OM", "PK", "PW", "PS", "PA", "PG", "PY", "PE", "PH", "PN", "PL", "PT", "PR", "QA", "RE", "RO", "RU", "RW", "SH", "KN", "LC", "PM", "VC", "WS", "SM", "ST", "SA", "SN", "RS", "SC", "SL", "SG", "SK", "SI", "SB", "SO", "ZA", "GS", "KR", "ES", "LK", "SD", "SR", "SJ", "SZ", "SE", "CH", "SY", "TW", "TJ", "TZ", "TH", "TG", "TK", "TO", "TT", "TN", "TR", "TM", "TC", "TV", "VI", "UG", "UA", "AE", "GB", "US", "UM", "UY", "UZ", "VU", "VA", "VE", "VN", "WF", "EH", "YE", "ZM", "ZW"))) {
            $RegistrantCountry = "IT";
        }
        $data["registrant_countrycode"] = $RegistrantCountry;
    }
    if ($tld == "us") {
        $data["registrant_usnexuscategory"] = $params["additionalfields"]["Nexus Category"];
        $usDomainPurpose = trim($params["additionalfields"]["Application Purpose"]);
        if (strtolower($usDomainPurpose) == strtolower("Business use for profit")) {
            $data["registrant_uspurpose"] = "P1";
        } else {
            if (strtolower($usDomainPurpose) == strtolower("Educational purposes")) {
                $data["registrant_uspurpose"] = "P4";
            } else {
                if (strtolower($usDomainPurpose) == strtolower("Personal Use")) {
                    $data["registrant_uspurpose"] = "P3";
                } else {
                    if (strtolower($usDomainPurpose) == strtolower("Government purposes")) {
                        $data["registrant_uspurpose"] = "P5";
                    } else {
                        $data["registrant_uspurpose"] = "P2";
                    }
                }
            }
        }
        $data["registrant_usnexuscategory"] = $params["additionalfields"]["Nexus Category"];
        $data["registrant_usnexuscountry"] = $params["additionalfields"]["Nexus Country"];
    }
    if ($ext == "uk") {
        $legalType = $params["additionalfields"]["Legal Type"];
        $dotUKOrgType = "";
        switch ($legalType) {
            case "Individual":
                $dotUKOrgType = "IND";
                break;
            case "UK Limited Company":
                $dotUKOrgType = "LTD";
                break;
            case "UK Public Limited Company":
                $dotUKOrgType = "PLC";
                break;
            case "UK Partnership":
                $dotUKOrgType = "PTNR";
                break;
            case "UK Limited Liability Partnership":
                $dotUKOrgType = "LLP";
                break;
            case "Sole Trader":
                $dotUKOrgType = "STRA";
                break;
            case "UK Registered Charity":
                $dotUKOrgType = "RCHAR";
                break;
            case "UK Entity (other)":
                $dotUKOrgType = "OTHER";
                break;
            case "Foreign Organization":
                $dotUKOrgType = "FCORP";
                break;
            case "Other foreign organizations":
                $dotUKOrgType = "FOTHER";
                break;
            case "UK Industrial/Provident Registered Company":
                $dotUKOrgType = "IP";
                break;
            case "UK School":
                $dotUKOrgType = "SCH";
                break;
            case "UK Government Body":
                $dotUKOrgType = "GOV";
                break;
            case "UK Corporation by Royal Charter":
                $dotUKOrgType = "CRC";
                break;
            case "UK Statutory Body":
                $dotUKOrgType = "STAT";
                break;
            case "Non-UK Individual":
                $dotUKOrgType = "FIND";
                break;
        }
        if (in_array($dotUKOrgType, array("LTD", "PLC", "LLP", "IP", "SCH", "RCHAR"))) {
            $data["registrant_dotUkOrgNo"] = $params["additionalfields"]["Company ID Number"];
            $data["registrant_dotUKRegistrationNumber"] = $params["additionalfields"]["Company ID Number"];
        }
        $data["registrant_dotUKOrgType"] = isset($params["additionalfields"]["Legal Type"]) ? $dotUKOrgType : "IND";
        if ($data["registrant_dotUKOrgType"] == "IND") {
            $data["registrant_dotUKOptOut"] = "N";
        }
    }
    if ($tld == "asia") {
        $asianCountries = array("AF", "AQ", "AM", "AU", "AZ", "BH", "BD", "BT", "BN", "KH", "CN", "CX", "CC", "CK", "CY", "FJ", "GE", "HM", "HK", "IN", "ID", "IR", "IQ", "IL", "JP", "JO", "KZ", "KI", "KP", "KR", "KW", "KG", "LA", "LB", "MO", "MY", "MV", "MH", "FM", "MN", "MM", "NR", "NP", "NZ", "NU", "NF", "OM", "PK", "PW", "PS", "PG", "PH", "QA", "WS", "SA", "SG", "SB", "LK", "SY", "TW", "TJ", "TH", "TL", "TK", "TO", "TR", "TM", "TV", "AE", "UZ", "VU", "VN", "YE");
        if (!in_array($RegistrantCountry, $asianCountries)) {
            $RegistrantCountry = "BD";
        }
        $data["registrant_countrycode"] = $RegistrantCountry;
        $data["registrant_dotASIACedLocality"] = $RegistrantCountry;
        $data["registrant_dotasiacedentity"] = $params["additionalfields"]["Legal Entity Type"];
        if ($data["registrant_dotasiacedentity"] == "other") {
            $data["registrant_dotasiacedentityother"] = isset($params["additionalfields"]["Other legal entity type"]) ? $params["additionalfields"]["Other legal entity type"] : "otheridentity";
        }
        $data["registrant_dotasiacedidform"] = $params["additionalfields"]["Identification Form"];
        if ($data["registrant_dotasiacedidform"] != "other") {
            $data["registrant_dotASIACedIdNumber"] = $params["additionalfields"]["Identification Number"];
        }
        if ($data["registrant_dotasiacedidform"] == "other") {
            $data["registrant_dotasiacedidformother"] = isset($params["additionalfields"]["Other identification form"]) ? $params["additionalfields"]["Other identification form"] : "otheridentity";
        }
    }
    if ($ext == "fr" || $ext == "re" || $ext == "pm" || $ext == "tf" || $ext == "wf" || $ext == "yt") {
        if ($ext == "fr") {
            $holderType = isset($params["additionalfields"]["Legal Type"]) ? $params["additionalfields"]["Legal Type"] : "individual";
            $data["admin_countrycode"] = "FR";
        } else {
            $holderType = isset($params["additionalfields"]["Legal Type"]) ? $params["additionalfields"]["Legal Type"] : "other";
            $frenchTerritoryCountries = array("GP", "MQ", "GF", "RE", "FR", "PF", "MQ", "YT", "NC", "PM", "WF", "MF", "BL", "TF");
            if (!in_array($data["admin_countrycode"], $frenchTerritoryCountries)) {
                $data["admin_countrycode"] = strtoupper($ext);
            }
        }
        $data["registrant_dotfrcontactentitytype"] = $holderType;
        $data["admin_dotfrcontactentitytype"] = $holderType;
        $addFields = $params["additionalfields"];
        switch (strtolower($holderType)) {
            case "individual":
                $data["registrant_dotfrcontactentitybirthdate"] = $addFields["Birthdate"];
                $data["registrant_dotfrcontactentitybirthplacecountrycode"] = $addFields["Birthplace Country Code"];
                if (!$data["registrant_dotfrcontactentitybirthplacecountrycode"]) {
                    $data["registrant_dotfrcontactentitybirthplacecountrycode"] = $addFields["Birthplace Country"];
                }
                $data["admin_dotfrcontactentitybirthdate"] = $addFields["Birthdate"];
                $data["admin_dotfrcontactentitybirthplacecountrycode"] = $addFields["Birthplace Country Code"];
                if (!$data["admin_dotfrcontactentitybirthplacecountrycode"]) {
                    $data["admin_dotfrcontactentitybirthplacecountrycode"] = $addFields["Birthplace Country"];
                }
                if (strtolower($data["registrant_dotfrcontactentitybirthplacecountrycode"]) == "fr") {
                    $data["registrant_dotFRContactEntityBirthCity"] = $addFields["Birthplace City"];
                    $data["registrant_dotFRContactEntityBirthPlacePostalCode"] = $addFields["Birthplace Postcode"];
                    $data["admin_dotFRContactEntityBirthCity"] = $addFields["Birthplace City"];
                    $data["admin_dotFRContactEntityBirthPlacePostalCode"] = $addFields["Birthplace Postcode"];
                }
                $data["registrant_dotFRContactEntityRestrictedPublication"] = isset($params["additionalfields"]["Restricted Publication"]) ? 1 : 0;
                $data["admin_dotFRContactEntityRestrictedPublication"] = isset($params["additionalfields"]["Restricted Publication"]) ? 1 : 0;
                break;
            case "company":
                $data["registrant_dotFRContactEntitySiren"] = $params["additionalfields"]["Siren"];
                $data["admin_dotFRContactEntitySiren"] = $params["additionalfields"]["Siren"];
                break;
            case "trademark":
                $data["registrant_dotFRContactEntityTradeMark"] = $params["additionalfields"]["Trade Mark"];
                $data["admin_dotFRContactEntityTradeMark"] = $params["additionalfields"]["Trade Mark"];
                break;
            case "association":
                if (isset($params["additionalfields"]["Waldec"])) {
                    $data["registrant_dotFRContactEntityWaldec"] = $params["additionalfields"]["Waldec"];
                    $data["admin_dotFRContactEntityWaldec"] = $params["additionalfields"]["Waldec"];
                } else {
                    $data["registrant_dotfrcontactentitydateofassociation"] = $params["additionalfields"]["Date of Association YYYY-MM-DD"];
                    $data["registrant_dotFRContactEntityDateOfPublication"] = $params["additionalfields"]["Date of Publication YYYY-MM-DD"];
                    $data["registrant_dotfrcontactentityannouceno"] = $params["additionalfields"]["Annouce No"];
                    $data["registrant_dotFRContactEntityPageNo"] = $params["additionalfields"]["Page No"];
                    $data["admin_dotfrcontactentitydateofassociation"] = $params["additionalfields"]["Date of Association YYYY-MM-DD"];
                    $data["admin_dotFRContactEntityDateOfPublication"] = $params["additionalfields"]["Date of Publication YYYY-MM-DD"];
                    $data["admin_dotfrcontactentityannouceno"] = $params["additionalfields"]["Annouce No"];
                    $data["admin_dotFRContactEntityPageNo"] = $params["additionalfields"]["Page No"];
                }
                break;
            case "other":
                $data["registrant_dotFROtherContactEntity"] = $params["additionalfields"]["Other Legal Status"];
                $data["admin_dotFROtherContactEntity"] = $params["additionalfields"]["Other Legal Status"];
                if (isset($params["additionalfields"]["Siren"])) {
                    $data["registrant_dotFRContactEntitySiren"] = $params["additionalfields"]["Siren"];
                    $data["admin_dotFRContactEntitySiren"] = $params["additionalfields"]["Siren"];
                } else {
                    if (isset($params["additionalfields"]["Trade Mark"])) {
                        $data["registrant_dotFRContactEntityTradeMark"] = $params["additionalfields"]["Trade Mark"];
                        $data["admin_dotFRContactEntityTradeMark"] = $params["additionalfields"]["Trade Mark"];
                    }
                }
                break;
        }
        if ($holderType != "individual") {
            $data["registrant_dotFRContactEntityName"] = empty($RegistrantCompany) ? $RegistrantFirstName . " " . $RegistrantLastName : $RegistrantCompany;
            $data["admin_dotFRContactEntityName"] = empty($AdminCompany) ? $AdminFirstName . " " . $AdminLastName : $AdminCompany;
        }
    }
    if ($tld == "tel") {
        $data["telHostingAccount"] = md5($RegistrantLastName . $RegistrantFirstName . time() . rand(0, 99999));
        $data["telHostingPassword"] = "passwd" . rand(0, 99999);
    }
    if ($tld == "it") {
        $EUCountries = array("AT", "BE", "BG", "CZ", "CY", "DE", "DK", "ES", "EE", "FI", "FR", "GR", "GB", "HU", "IE", "IT", "LT", "LU", "LV", "MT", "NL", "PL", "PT", "RO", "SE", "SK", "SI");
        $EntityTypes = array("Italian and foreign natural persons" => 1, "Companies/one man companies" => 2, "Freelance workers/professionals" => 3, "non-profit organizations" => 4, "public organizations" => 5, "other subjects" => 6, "non natural foreigners" => 7);
        $legalEntityType = $params["additionalfields"]["Legal Type"];
        $et = $EntityTypes[$legalEntityType];
        $data["registrant_dotitentitytype"] = $et;
        if (2 <= $et && $et <= 6) {
            $data["registrant_dotitnationality"] = "IT";
            $data["registrant_countrycode"] = "IT";
        } else {
            if ($et == 7) {
                if (!in_array($data["registrant_countrycode"], $EUCountries)) {
                    $data["registrant_countrycode"] = "FR";
                }
                $data["registrant_dotitnationality"] = $data["registrant_countrycode"];
            } else {
                $nationality = internetbs_getCountryCodeByName($params["additionalfields"]["Nationality"]);
                if (!in_array($nationality, $EUCountries) && !in_array($data["registrant_countrycode"], $EUCountries)) {
                    $nationality = "IT";
                }
                $data["registrant_dotitnationality"] = $nationality;
            }
        }
        if (strtoupper($data["registrant_countrycode"]) == "IT") {
            $data["registrant_dotitprovince"] = internetbs_getItProvinceCode($params["additionalfields"]["Province (IT)"]);
        } else {
            $data["registrant_dotitprovince"] = $RegistrantStateProvince;
        }
        if (strtoupper($data["admin_countrycode"]) == "IT") {
            $data["admin_dotitprovince"] = internetbs_getItProvinceCode($params["additionalfields"]["Province (IT)"]);
        } else {
            $data["admin_dotitprovince"] = $AdminStateProvince;
        }
        $data["registrant_dotitregcode"] = $params["additionalfields"]["Tax ID"];
        $data["registrant_dotithidewhois"] = $params["additionalfields"]["Publish Personal Data"] ? false : true;
        $data["admin_dotithidewhois"] = $params["additionalfields"]["Publish Personal Data"] ? false : true;
        $data["registrant_clientip"] = internetbs_getClientIp();
        $data["registrant_dotitterm1"] = "yes";
        $data["registrant_dotitterm2"] = "yes";
        $data["registrant_dotitterm3"] = "yes";
        $data["registrant_dotitterm4"] = "yes";
    }
    if ($tld == "de") {
        $data["admin_role"] = "PERSON";
        $data["technical_role"] = "PERSON";
        $data["zone_role"] = "PERSON";
        if (!empty($RegistrantCompany)) {
            $data["registrant_role"] = "ORG";
        } else {
            $data["registrant_role"] = "PERSON";
        }
        $data["zone_firstname"] = $AdminFirstName;
        $data["zone_lastname"] = $AdminLastName;
        $data["zone_street"] = $AdminAddress1;
        $data["zone_street2"] = $AdminAddress2;
        $data["zone_city"] = $AdminCity;
        $data["zone_countrycode"] = $AdminCountry;
        $data["zone_postalcode"] = $AdminPostalCode;
        $data["zone_email"] = $AdminEmailAddress;
        $data["zone_phonenumber"] = $AdminPhone;
        unset($data["billing_firstname"]);
        unset($data["billing_lastname"]);
        unset($data["billing_street"]);
        unset($data["billing_street2"]);
        unset($data["billing_city"]);
        unset($data["billing_countrycode"]);
        unset($data["billing_postalcode"]);
        unset($data["billing_email"]);
        unset($data["billing_phonenumber"]);
        $data["clientip"] = internetbs_getClientIp();
        $data["tosagree"] = $params["additionalfields"]["Agree to DE Terms"] == "on" ? "YES" : "NO";
    }
    if (isset($params["regperiod"]) && 0 < $regperiod) {
        $data["period"] = $regperiod . "Y";
    }
    $result = internetbs_runcommand($commandUrl, $data);
    $errorMessage = internetbs_getlasterror();
    if ($result === false) {
        $values["error"] = internetbs_getconnectionerrormessage($errorMessage);
    } else {
        if ($result["status"] == "FAILURE") {
            $values["error"] = $result["message"];
        }
    }
    if ($result["product_0_status"] == "FAILURE") {
        if (isset($values["error"])) {
            $values["error"] .= $result["product_0_message"];
        } else {
            $values["error"] = $result["product_0_message"];
        }
    }
    if (($result["status"] == "FAILURE" || $result["product_0_status"] == "FAILURE") && (!isset($values["error"]) || empty($values["error"]))) {
        $values["error"] = "Error: cannot register domain";
    }
    return $values;
}
function internetbs_TransferDomain($params)
{
    $params = internetbs_get_utf8_parameters($params);
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $regperiod = $params["regperiod"];
    $transfersecret = $params["transfersecret"];
    $nameserver1 = $params["ns1"];
    $nameserver2 = $params["ns2"];
    $nameserver3 = $params["ns3"];
    $nameserver4 = $params["ns4"];
    $nameserver5 = $params["ns5"];
    $RegistrantFirstName = $params["firstname"];
    $RegistrantLastName = $params["lastname"];
    $RegistrantCompany = trim($params["companyname"]);
    $RegistrantAddress1 = $params["address1"];
    $RegistrantAddress2 = $params["address2"];
    $RegistrantCity = $params["city"];
    $RegistrantStateProvince = $params["state"];
    $RegistrantPostalCode = $params["postcode"];
    $RegistrantCountry = $params["country"];
    $RegistrantEmailAddress = $params["email"];
    $RegistrantPhone = internetbs_reformatPhone($params["phonenumber"], $params["country"]);
    $AdminFirstName = $params["adminfirstname"];
    $AdminLastName = $params["adminlastname"];
    $AdminAddress1 = $params["adminaddress1"];
    $AdminAddress2 = $params["adminaddress2"];
    $AdminCity = $params["admincity"];
    $AdminCompany = $params["admincompanyname"];
    $AdminStateProvince = $params["adminstate"];
    $AdminPostalCode = $params["adminpostcode"];
    $AdminCountry = $params["admincountry"];
    $AdminEmailAddress = $params["adminemail"];
    $AdminPhone = internetbs_reformatPhone($params["adminphonenumber"], $params["admincountry"]);
    $domainName = $sld . "." . $tld;
    $nslist = array();
    if (isset($params["ns1"])) {
        array_push($nslist, $nameserver1);
    }
    if (isset($params["ns2"])) {
        array_push($nslist, $nameserver2);
    }
    if (isset($params["ns3"])) {
        array_push($nslist, $nameserver3);
    }
    if (isset($params["ns4"])) {
        array_push($nslist, $nameserver4);
    }
    if (isset($params["ns5"])) {
        array_push($nslist, $nameserver5);
    }
    $apiServerUrl = $testmode == "on" ? API_TESTSERVER_URL : API_SERVER_URL;
    $commandUrl = $apiServerUrl . "Domain/Transfer/Initiate";
    $data = array("apikey" => $username, "password" => $password, "domain" => $domainName, "transferAuthInfo" => $transfersecret, "registrant_firstname" => $RegistrantFirstName, "registrant_lastname" => $RegistrantLastName, "registrant_street" => $RegistrantAddress1, "registrant_street2" => $RegistrantAddress2, "registrant_city" => $RegistrantCity, "registrant_countrycode" => $RegistrantCountry, "registrant_postalcode" => $RegistrantPostalCode, "registrant_email" => $RegistrantEmailAddress, "registrant_phonenumber" => $RegistrantPhone, "technical_firstname" => $AdminFirstName, "technical_lastname" => $AdminLastName, "technical_street" => $AdminAddress1, "technical_street2" => $AdminAddress2, "technical_city" => $AdminCity, "technical_countrycode" => $AdminCountry, "technical_postalcode" => $AdminPostalCode, "technical_email" => $AdminEmailAddress, "technical_phonenumber" => $AdminPhone, "admin_firstname" => $AdminFirstName, "admin_lastname" => $AdminLastName, "admin_street" => $AdminAddress1, "admin_street2" => $AdminAddress2, "admin_city" => $AdminCity, "admin_countrycode" => $AdminCountry, "admin_postalcode" => $AdminPostalCode, "admin_email" => $AdminEmailAddress, "admin_phonenumber" => $AdminPhone, "billing_firstname" => $AdminFirstName, "billing_lastname" => $AdminLastName, "billing_street" => $AdminAddress1, "billing_street2" => $AdminAddress2, "billing_city" => $AdminCity, "billing_countrycode" => $AdminCountry, "billing_postalcode" => $AdminPostalCode, "billing_email" => $AdminEmailAddress, "billing_phonenumber" => $AdminPhone);
    if (!empty($RegistrantCompany)) {
        $data["Registrant_Organization"] = $RegistrantCompany;
    }
    if (!empty($AdminCompany)) {
        $data["technical_Organization"] = $AdminCompany;
        $data["admin_Organization"] = $AdminCompany;
        $data["billing_Organization"] = $AdminCompany;
    }
    if (count($nslist)) {
        $data["ns_list"] = implode(",", $nslist);
    }
    $extArray = explode(".", $tld);
    $ext = array_pop($extArray);
    if ($tld == "eu" || $tld == "be" || $tld == "uk") {
        $data["registrant_language"] = isset($params["Language"]) ? $params["Language"] : "en";
    }
    if ($tld == "us") {
        $data["registrant_usnexuscategory"] = $params["additionalfields"]["Nexus Category"];
        $usDomainPurpose = trim($params["additionalfields"]["Application Purpose"]);
        if (strtolower($usDomainPurpose) == strtolower("Business use for profit")) {
            $data["registrant_uspurpose"] = "P1";
        } else {
            if (strtolower($usDomainPurpose) == strtolower("Educational purposes")) {
                $data["registrant_uspurpose"] = "P4";
            } else {
                if (strtolower($usDomainPurpose) == strtolower("Personal Use")) {
                    $data["registrant_uspurpose"] = "P3";
                } else {
                    if (strtolower($usDomainPurpose) == strtolower("Government purposes")) {
                        $data["registrant_uspurpose"] = "P5";
                    } else {
                        $data["registrant_uspurpose"] = "P2";
                    }
                }
            }
        }
        $data["registrant_usnexuscategory"] = $params["additionalfields"]["Nexus Category"];
        $data["registrant_usnexuscountry"] = $params["additionalfields"]["Nexus Country"];
    }
    if ($tld == "asia") {
        $data["registrant_dotASIACedLocality"] = $AdminCountry;
        $data["registrant_dotasiacedentity"] = $params["Legal Entity Type"];
        if ($data["registrant_dotasiacedentity"] == "other") {
            $data["registrant_dotasiacedentityother"] = isset($params["Other legal entity type"]) ? $params["Other legal entity type"] : "otheridentity";
        }
        $data["registrant_dotasiacedidform"] = $params["Identification Form"];
        if ($data["registrant_dotasiacedidform"] != "other") {
            $data["registrant_dotASIACedIdNumber"] = $params["Identification Number"];
        }
        if ($data["registrant_dotasiacedidform"] == "other") {
            $data["registrant_dotasiacedidformother"] = isset($params["Other identification form"]) ? $params["Other identification form"] : "otheridentity";
        }
    }
    if ($ext == "fr" || $ext == "re" || $ext == "pm" || $ext == "tf" || $ext == "wf" || $ext == "yt") {
        if ($ext == "fr") {
            $holderType = isset($params["additionalfields"]["Legal Type"]) ? $params["additionalfields"]["Legal Type"] : "individual";
            $data["admin_countrycode"] = "FR";
        } else {
            $holderType = isset($params["additionalfields"]["Legal Type"]) ? $params["additionalfields"]["Legal Type"] : "other";
            $frenchTerritoryCountries = array("GP", "MQ", "GF", "RE", "FR", "PF", "MQ", "YT", "NC", "PM", "WF", "MF", "BL", "TF");
            if (!in_array($data["admin_countrycode"], $frenchTerritoryCountries)) {
                $data["admin_countrycode"] = strtoupper($ext);
            }
        }
        $data["registrant_dotfrcontactentitytype"] = $holderType;
        $data["admin_dotfrcontactentitytype"] = $holderType;
        $addFields = $params["additionalfields"];
        switch (strtolower($holderType)) {
            case "individual":
                $data["registrant_dotfrcontactentitybirthdate"] = $addFields["Birthdate"];
                $data["registrant_dotfrcontactentitybirthplacecountrycode"] = $addFields["Birthplace Country Code"];
                if (!$data["registrant_dotfrcontactentitybirthplacecountrycode"]) {
                    $data["registrant_dotfrcontactentitybirthplacecountrycode"] = $addFields["Birthplace Country"];
                }
                $data["admin_dotfrcontactentitybirthdate"] = $addFields["Birthdate"];
                $data["admin_dotfrcontactentitybirthplacecountrycode"] = $addFields["Birthplace Country Code"];
                if (!$data["admin_dotfrcontactentitybirthplacecountrycode"]) {
                    $data["admin_dotfrcontactentitybirthplacecountrycode"] = $addFields["Birthplace Country"];
                }
                if (strtolower($data["registrant_dotfrcontactentitybirthplacecountrycode"]) == "fr") {
                    $data["registrant_dotFRContactEntityBirthCity"] = $addFields["Birthplace City"];
                    $data["registrant_dotFRContactEntityBirthPlacePostalCode"] = $addFields["Birthplace Postcode"];
                    $data["admin_dotFRContactEntityBirthCity"] = $addFields["Birthplace City"];
                    $data["admin_dotFRContactEntityBirthPlacePostalCode"] = $addFields["Birthplace Postcode"];
                }
                $data["registrant_dotFRContactEntityRestrictedPublication "] = $addFields["Restricted Publication"] ? 1 : 0;
                $data["admin_dotFRContactEntityRestrictedPublication "] = $addFields["Restricted Publication"] ? 1 : 0;
                break;
            case "company":
                $data["registrant_dotFRContactEntitySiren"] = $params["Siren"];
                $data["admin_dotFRContactEntitySiren"] = $params["Siren"];
                break;
            case "trademark":
                $data["registrant_dotFRContactEntityTradeMark"] = $params["Trade Mark"];
                $data["admin_dotFRContactEntityTradeMark"] = $params["Trade Mark"];
                break;
            case "association":
                if (isset($params["Waldec"])) {
                    $data["registrant_dotFRContactEntityWaldec"] = $params["Waldec"];
                    $data["admin_dotFRContactEntityWaldec"] = $params["Waldec"];
                } else {
                    $data["registrant_dotfrcontactentitydateofassociation"] = $params["Date of Association YYYY-MM-DD"];
                    $data["registrant_dotFRContactEntityDateOfPublication"] = $params["Date of Publication YYYY-MM-DD"];
                    $data["registrant_dotfrcontactentityannouceno"] = $params["Annouce No"];
                    $data["registrant_dotFRContactEntityPageNo"] = $params["Page No"];
                    $data["admin_dotfrcontactentitydateofassociation"] = $params["Date of Association YYYY-MM-DD"];
                    $data["admin_dotFRContactEntityDateOfPublication"] = $params["Date of Publication YYYY-MM-DD"];
                    $data["admin_dotfrcontactentityannouceno"] = $params["Annouce No"];
                    $data["admin_dotFRContactEntityPageNo"] = $params["Page No"];
                }
                break;
            case "other":
                $data["registrant_dotFROtherContactEntity"] = $params["Other Legal Status"];
                $data["admin_dotFROtherContactEntity"] = $params["Other Legal Status"];
                if (isset($params["Siren"])) {
                    $data["registrant_dotFRContactEntitySiren"] = $params["Siren"];
                    $data["admin_dotFRContactEntitySiren"] = $params["Siren"];
                } else {
                    if (isset($params["Trade Mark"])) {
                        $data["registrant_dotFRContactEntityTradeMark"] = $params["Trade Mark"];
                        $data["admin_dotFRContactEntityTradeMark"] = $params["Trade Mark"];
                    }
                }
                break;
        }
        if ($tld == "tel") {
            $data["telHostingAccount"] = md5($RegistrantLastName . $RegistrantFirstName . time() . rand(0, 99999));
            $data["telHostingPassword"] = "passwd" . rand(0, 99999);
        }
        if ($tld == "it") {
            $EUCountries = array("AT", "BE", "BG", "CZ", "CY", "DE", "DK", "ES", "EE", "FI", "FR", "GR", "GB", "HU", "IE", "IT", "LT", "LU", "LV", "MT", "NL", "PL", "PT", "RO", "SE", "SK", "SI");
            $EntityTypes = array("1. Italian and foreign natural persons" => 1, "2. Companies/one man companies" => 2, "3. Freelance workers/professionals" => 3, "4. non-profit organizations" => 4, "5. public organizations" => 5, "6. other subjects" => 6, "7. foreigners who match 2 - 6" => 7);
            $legalEntityType = $params["additionalfields"]["Legal Entity Type"];
            $et = $EntityTypes[$legalEntityType];
            $data["registrant_dotitentitytype"] = $et;
            if (2 <= $et && $et <= 6) {
                $data["registrant_dotitnationality"] = "IT";
                $data["registrant_countrycode"] = "IT";
            } else {
                if ($et == 7) {
                    if (!in_array($data["registrant_countrycode"], $EUCountries)) {
                        $data["registrant_countrycode"] = "FR";
                    }
                    $data["registrant_dotitnationality"] = $data["registrant_countrycode"];
                } else {
                    $nationality = internetbs_getCountryCodeByName($params["additionalfields"]["Nationality"]);
                    if (!in_array($nationality, $EUCountries) && !in_array($data["registrant_countrycode"], $EUCountries)) {
                        $nationality = "IT";
                    }
                    $data["registrant_dotitnationality"] = $nationality;
                }
            }
            if (strtoupper($data["registrant_countrycode"]) == "IT") {
                $data["registrant_dotitprovince"] = internetbs_get2CharDotITProvinceCode($RegistrantStateProvince);
            } else {
                $data["registrant_dotitprovince"] = $RegistrantStateProvince;
            }
            if (strtoupper($data["admin_countrycode"]) == "IT") {
                $data["admin_dotitprovince"] = internetbs_get2CharDotITProvinceCode($AdminStateProvince);
            } else {
                $data["admin_dotitprovince"] = $AdminStateProvince;
            }
            $data["registrant_dotitregcode"] = $params["additionalfields"]["VAT/TAX/Passport/ID Number"];
            $data["registrant_clientip"] = internetbs_getClientIp();
            $data["registrant_dotitterm1"] = "yes";
            $data["registrant_dotitterm2"] = "yes";
            $data["registrant_dotitterm3"] = "yes";
            $data["registrant_dotitterm4"] = "yes";
        }
        if ($holderType != "individual") {
            $data["registrant_dotFRContactEntityName"] = empty($RegistrantCompany) ? $RegistrantFirstName . " " . $RegistrantLastName : $RegistrantCompany;
            $data["admin_dotFRContactEntityName"] = empty($AdminCompany) ? $AdminFirstName . " " . $AdminLastName : $AdminCompany;
        }
    }
    if ($tld == "de") {
        $data["admin_role"] = "PERSON";
        $data["technical_role"] = "PERSON";
        $data["zone_role"] = "PERSON";
        if (!empty($RegistrantCompany)) {
            $data["registrant_role"] = "ORG";
        } else {
            $data["registrant_role"] = "PERSON";
        }
        $data["zone_firstname"] = $AdminFirstName;
        $data["zone_lastname"] = $AdminLastName;
        $data["zone_street"] = $AdminAddress1;
        $data["zone_street2"] = $AdminAddress2;
        $data["zone_city"] = $AdminCity;
        $data["zone_countrycode"] = $AdminCountry;
        $data["zone_postalcode"] = $AdminPostalCode;
        $data["zone_email"] = $AdminEmailAddress;
        $data["zone_phonenumber"] = $AdminPhone;
        unset($data["billing_firstname"]);
        unset($data["billing_lastname"]);
        unset($data["billing_street"]);
        unset($data["billing_street2"]);
        unset($data["billing_city"]);
        unset($data["billing_countrycode"]);
        unset($data["billing_postalcode"]);
        unset($data["billing_email"]);
        unset($data["billing_phonenumber"]);
        $data["clientip"] = internetbs_getClientIp();
        $data["tosagree"] = $params["additionalfields"]["Agree to DE Terms"] == "on" ? "YES" : "NO";
    }
    if ($params["idprotection"]) {
        $data["privateWhois"] = "FULL";
    }
    $result = internetbs_runcommand($commandUrl, $data);
    $errorMessage = internetbs_getlasterror();
    if ($result === false) {
        $values["error"] = internetbs_getconnectionerrormessage($errorMessage);
    } else {
        if ($result["status"] == "FAILURE") {
            $values["error"] = $result["message"];
        }
    }
    if ($result["product_0_status"] == "FAILURE") {
        if (isset($values["error"])) {
            $values["error"] .= $result["product_0_message"];
        } else {
            $values["error"] = $result["product_0_message"];
        }
    }
    if (($result["status"] == "FAILURE" || $result["product_0_status"] == "FAILURE") && (!isset($values["error"]) || empty($values["error"]))) {
        $values["error"] = "Error: cannot start transfer domain";
    }
    return $values;
}
function internetbs_RenewDomain($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $regperiod = intval($params["regperiod"]);
    $domainName = $sld . "." . $tld;
    $apiServerUrl = $testmode == "on" ? API_TESTSERVER_URL : API_SERVER_URL;
    $commandUrl = $apiServerUrl . "Domain/Renew";
    $data = array("apikey" => $username, "password" => $password, "domain" => $domainName);
    if (isset($params["regperiod"]) && 0 < $regperiod) {
        $data["period"] = $regperiod . "Y";
    }
    $result = internetbs_runcommand($commandUrl, $data);
    $errorMessage = internetbs_getlasterror();
    if ($result === false) {
        $values["error"] = internetbs_getconnectionerrormessage($errorMessage);
    } else {
        if ($result["status"] == "FAILURE") {
            $values["error"] = $result["message"];
        }
    }
    return $values;
}
function internetbs_GetContactDetails($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domainName = $sld . "." . $tld;
    $apiServerUrl = $testmode == "on" ? API_TESTSERVER_URL : API_SERVER_URL;
    $commandUrl = $apiServerUrl . "Domain/Info";
    $data = array("apikey" => $username, "password" => $password, "domain" => $domainName);
    $result = internetbs_runcommand($commandUrl, $data);
    $errorMessage = internetbs_getlasterror();
    if ($result === false) {
        $values["error"] = internetbs_getconnectionerrormessage($errorMessage);
    } else {
        if ($result["status"] == "FAILURE") {
            $values["error"] = $result["message"];
        } else {
            $values["Registrant"]["First Name"] = $result["contacts_registrant_firstname"];
            $values["Registrant"]["Last Name"] = $result["contacts_registrant_lastname"];
            $values["Registrant"]["Company"] = $result["contacts_registrant_organization"];
            $values["Registrant"]["Email"] = $result["contacts_registrant_email"];
            $values["Registrant"]["Phone Number"] = $result["contacts_registrant_phonenumber"];
            $values["Registrant"]["Address 1"] = $result["contacts_registrant_street"];
            $values["Registrant"]["Address 2"] = $result["contacts_registrant_street1"];
            $values["Registrant"]["Postcode"] = $result["contacts_registrant_postalcode"];
            $values["Registrant"]["City"] = $result["contacts_registrant_city"];
            $values["Registrant"]["Country"] = $result["contacts_registrant_countrycode"];
            $values["Admin"]["First Name"] = $result["contacts_admin_firstname"];
            $values["Admin"]["Last Name"] = $result["contacts_admin_lastname"];
            $values["Admin"]["Company"] = $result["contacts_admin_organization"];
            $values["Admin"]["Email"] = $result["contacts_admin_email"];
            $values["Admin"]["Phone Number"] = $result["contacts_admin_phonenumber"];
            $values["Admin"]["Address 1"] = $result["contacts_admin_street"];
            $values["Admin"]["Address 2"] = $result["contacts_admin_street1"];
            $values["Admin"]["Postcode"] = $result["contacts_admin_postalcode"];
            $values["Admin"]["City"] = $result["contacts_admin_city"];
            $values["Admin"]["Country"] = $result["contacts_admin_countrycode"];
            if (isset($result["contacts_technical_email"])) {
                $values["Tech"]["First Name"] = $result["contacts_technical_firstname"];
                $values["Tech"]["Last Name"] = $result["contacts_technical_lastname"];
                $values["Tech"]["Company"] = $result["contacts_technical_organization"];
                $values["Tech"]["Email"] = $result["contacts_technical_email"];
                $values["Tech"]["Phone Number"] = $result["contacts_technical_phonenumber"];
                $values["Tech"]["Address 1"] = $result["contacts_technical_street"];
                $values["Tech"]["Address 2"] = $result["contacts_technical_street1"];
                $values["Tech"]["Postcode"] = $result["contacts_technical_postalcode"];
                $values["Tech"]["City"] = $result["contacts_technical_city"];
                $values["Tech"]["Country"] = $result["contacts_technical_countrycode"];
            }
        }
    }
    return $values;
}
function internetbs_SaveContactDetails($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $firstname = $params["contactdetails"]["Registrant"]["First Name"];
    $lastname = $params["contactdetails"]["Registrant"]["Last Name"];
    $company = $params["contactdetails"]["Registrant"]["Company"];
    $email = $params["contactdetails"]["Registrant"]["Email"];
    $phonenumber = internetbs_reformatPhone($params["contactdetails"]["Registrant"]["Phone Number"], $params["contactdetails"]["Registrant"]["Country"]);
    $address1 = $params["contactdetails"]["Registrant"]["Address 1"];
    $address2 = $params["contactdetails"]["Registrant"]["Address 2"];
    $postalcode = $params["contactdetails"]["Registrant"]["Postcode"];
    $city = $params["contactdetails"]["Registrant"]["City"];
    $countrycode = $params["contactdetails"]["Registrant"]["Country"];
    $adminfirstname = $params["contactdetails"]["Admin"]["First Name"];
    $adminlastname = $params["contactdetails"]["Admin"]["Last Name"];
    $adminCompany = $params["contactdetails"]["Admin"]["Company"];
    $adminemail = $params["contactdetails"]["Admin"]["Email"];
    $adminphonenumber = internetbs_reformatPhone($params["contactdetails"]["Admin"]["Phone Number"], $params["contactdetails"]["Admin"]["Country"]);
    $adminaddress1 = $params["contactdetails"]["Admin"]["Address 1"];
    $adminaddress2 = $params["contactdetails"]["Admin"]["Address 2"];
    $adminpostalcode = $params["contactdetails"]["Admin"]["Postcode"];
    $admincity = $params["contactdetails"]["Admin"]["City"];
    $admincountrycode = $params["contactdetails"]["Admin"]["Country"];
    $techfirstname = $params["contactdetails"]["Tech"]["First Name"];
    $techlastname = $params["contactdetails"]["Tech"]["Last Name"];
    $techCompany = $params["contactdetails"]["Tech"]["Company"];
    $techemail = $params["contactdetails"]["Tech"]["Email"];
    $techphonenumber = internetbs_reformatPhone($params["contactdetails"]["Tech"]["Phone Number"], $params["contactdetails"]["Tech"]["Country"]);
    $techaddress1 = $params["contactdetails"]["Tech"]["Address 1"];
    $techaddress2 = $params["contactdetails"]["Tech"]["Address 2"];
    $techpostalcode = $params["contactdetails"]["Tech"]["Postcode"];
    $techcity = $params["contactdetails"]["Tech"]["City"];
    $techcountrycode = $params["contactdetails"]["Tech"]["Country"];
    $clientIp = internetbs_getClientIp();
    $domainName = $sld . "." . $tld;
    $apiServerUrl = $testmode == "on" ? API_TESTSERVER_URL : API_SERVER_URL;
    $commandUrl = $apiServerUrl . "Domain/Update";
    $data = array("apikey" => $username, "password" => $password, "domain" => $domainName, "registrant_firstname" => $firstname, "registrant_lastname" => $lastname, "registrant_organization" => $company, "registrant_street" => $address1, "registrant_street2" => $address2, "registrant_city" => $city, "registrant_countrycode" => $countrycode, "registrant_postalcode" => $postalcode, "registrant_email" => $email, "registrant_phonenumber" => $phonenumber, "registrant_clientip" => $clientIp, "technical_firstname" => $techfirstname, "technical_lastname" => $techlastname, "technical_organization" => $techCompany, "technical_street" => $techaddress1, "technical_street2" => $techaddress2, "technical_city" => $techcity, "technical_countrycode" => $techcountrycode, "technical_postalcode" => $techpostalcode, "technical_email" => $techemail, "technical_phonenumber" => $techphonenumber, "admin_firstname" => $adminfirstname, "admin_lastname" => $adminlastname, "admin_organization" => $adminCompany, "admin_street" => $adminaddress1, "admin_street2" => $adminaddress2, "admin_city" => $admincity, "admin_countrycode" => $admincountrycode, "admin_postalcode" => $adminpostalcode, "admin_email" => $adminemail, "admin_phonenumber" => $adminphonenumber, "billing_firstname" => $adminfirstname, "billing_lastname" => $adminlastname, "admin_organization" => $adminCompany, "billing_street" => $adminaddress1, "billing_street2" => $adminaddress2, "billing_city" => $admincity, "billing_countrycode" => $admincountrycode, "billing_postalcode" => $adminpostalcode, "billing_email" => $adminemail, "billing_phonenumber" => $adminphonenumber);
    $extarr = explode(".", $tld);
    $ext = array_pop($extarr);
    if ("it" == $ext) {
        unset($data["registrant_countrycode"]);
        unset($data["registrant_organization"]);
        unset($data["registrant_countrycode"]);
        unset($data["registrant_country"]);
        unset($data["registrant_dotitentitytype"]);
        unset($data["registrant_dotitnationality"]);
        unset($data["registrant_dotitregcode"]);
    }
    if ($ext == "eu" || $ext == "be") {
        if (!strlen(trim($data["registrant_organization"]))) {
            unset($data["registrant_firstname"]);
            unset($data["registrant_lastname"]);
        }
        unset($data["registrant_organization"]);
    }
    if ($ext == "co.uk" || $ext == "org.uk" || $ext == "me.uk" || $ext == "uk") {
        unset($data["registrant_firstname"]);
        unset($data["registrant_lastname"]);
    }
    if ($ext == "fr" || $ext == "re") {
        unset($data["registrant_firstname"]);
        unset($data["registrant_lastname"]);
        unset($data["registrant_countrycode"]);
        unset($data["registrant_countrycode"]);
        if (!strlen(trim($data["admin_dotfrcontactentitysiren"]))) {
            unset($data["admin_dotfrcontactentitysiren"]);
        }
        if (trim(strtolower($data["admin_dotfrcontactentitytype"])) == "individual") {
            unset($data["admin_countrycode"]);
        }
    }
    $result = internetbs_runcommand($commandUrl, $data);
    $errorMessage = internetbs_getlasterror();
    if ($result === false) {
        $values["error"] = internetbs_getconnectionerrormessage($errorMessage);
    } else {
        if ($result["status"] == "FAILURE") {
            $values["error"] = $result["message"];
        }
    }
    return $values;
}
function internetbs_GetEPPCode($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domainName = $sld . "." . $tld;
    $apiServerUrl = $testmode == "on" ? API_TESTSERVER_URL : API_SERVER_URL;
    $commandUrl = $apiServerUrl . "Domain/Info";
    $data = array("apikey" => $username, "password" => $password, "domain" => $domainName);
    $result = internetbs_runcommand($commandUrl, $data);
    $errorMessage = internetbs_getlasterror();
    if ($result === false) {
        $values["error"] = internetbs_getconnectionerrormessage($errorMessage);
    } else {
        if ($result["status"] == "FAILURE") {
            $values["error"] = $result["message"];
        } else {
            $values["eppcode"] = $result["transferauthinfo"];
        }
    }
    return $values;
}
function internetbs_RegisterNameserver($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $nameserver = $params["nameserver"];
    $ipaddress = $params["ipaddress"];
    $domainName = $sld . "." . $tld;
    if ($nameserver != $domainName && strpos($nameserver, "." . $domainName) === false) {
        $nameserver = $nameserver . "." . $domainName;
    }
    $apiServerUrl = $testmode == "on" ? API_TESTSERVER_URL : API_SERVER_URL;
    $commandUrl = $apiServerUrl . "Domain/Host/Create";
    $data = array("apikey" => $username, "password" => $password, "host" => $nameserver, "ip_list" => $ipaddress);
    $result = internetbs_runcommand($commandUrl, $data);
    $errorMessage = internetbs_getlasterror();
    if ($result === false) {
        $values["error"] = internetbs_getconnectionerrormessage($errorMessage);
    } else {
        if ($result["status"] == "FAILURE") {
            $values["error"] = $result["message"];
        }
    }
    return $values;
}
function internetbs_ModifyNameserver($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $nameserver = $params["nameserver"];
    $currentipaddress = $params["currentipaddress"];
    $newipaddress = $params["newipaddress"];
    $domainName = $sld . "." . $tld;
    if ($nameserver != $domainName && strpos($nameserver, "." . $domainName) === false) {
        $nameserver = $nameserver . "." . $domainName;
    }
    $apiServerUrl = $testmode == "on" ? API_TESTSERVER_URL : API_SERVER_URL;
    $commandUrl = $apiServerUrl . "Domain/Host/Update";
    $data = array("apikey" => $username, "password" => $password, "host" => $nameserver, "ip_list" => $newipaddress);
    $result = internetbs_runcommand($commandUrl, $data);
    $errorMessage = internetbs_getlasterror();
    if ($result === false) {
        $values["error"] = internetbs_getconnectionerrormessage($errorMessage);
    } else {
        if ($result["status"] == "FAILURE") {
            $values["error"] = $result["message"];
        }
    }
    return $values;
}
function internetbs_DeleteNameserver($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $nameserver = $params["nameserver"];
    $domainName = $sld . "." . $tld;
    if ($nameserver != $domainName && strpos($nameserver, "." . $domainName) === false) {
        $nameserver = $nameserver . "." . $domainName;
    }
    $apiServerUrl = $testmode == "on" ? API_TESTSERVER_URL : API_SERVER_URL;
    $commandUrl = $apiServerUrl . "Domain/Host/Delete";
    $data = array("apikey" => $username, "password" => $password, "host" => $nameserver);
    $result = internetbs_runcommand($commandUrl, $data);
    $errorMessage = internetbs_getlasterror();
    if ($result === false) {
        $values["error"] = internetbs_getconnectionerrormessage($errorMessage);
    } else {
        if ($result["status"] == "FAILURE") {
            $values["error"] = $result["message"];
        }
    }
    return $values;
}
function internetbs_mapCountry($countryCode)
{
    $mapc = array("US" => 1, "CA" => 1, "AI" => 1, "AG" => 1, "BB" => 1, "BS" => 1, "VG" => 1, "VI" => 1, "KY" => 1, "BM" => 1, "GD" => 1, "TC" => 1, "MS" => 1, "MP" => 1, "GU" => 1, "LC" => 1, "DM" => 1, "VC" => 1, "PR" => 1, "DO" => 1, "TT" => 1, "KN" => 1, "JM" => 1, "EG" => 20, "MA" => 212, "DZ" => 213, "TN" => 216, "LY" => 218, "GM" => 220, "SN" => 221, "MR" => 222, "ML" => 223, "GN" => 224, "CI" => 225, "BF" => 226, "NE" => 227, "TG" => 228, "BJ" => 229, "MU" => 230, "LR" => 231, "SL" => 232, "GH" => 233, "NG" => 234, "TD" => 235, "CF" => 236, "CM" => 237, "CV" => 238, "ST" => 239, "GQ" => 240, "GA" => 241, "CG" => 242, "CD" => 243, "AO" => 244, "GW" => 245, "IO" => 246, "AC" => 247, "SC" => 248, "SD" => 249, "RW" => 250, "ET" => 251, "SO" => 252, "DJ" => 253, "KE" => 254, "TZ" => 255, "UG" => 256, "BI" => 257, "MZ" => 258, "ZM" => 260, "MG" => 261, "RE" => 262, "ZW" => 263, "NA" => 264, "MW" => 265, "LS" => 266, "BW" => 267, "SZ" => 268, "KM" => 269, "YT" => 269, "ZA" => 27, "SH" => 290, "ER" => 291, "AW" => 297, "FO" => 298, "GL" => 299, "GR" => 30, "NL" => 31, "BE" => 32, "FR" => 33, "ES" => 34, "GI" => 350, "PT" => 351, "LU" => 352, "IE" => 353, "IS" => 354, "AL" => 355, "MT" => 356, "CY" => 357, "FI" => 358, "BG" => 359, "HU" => 36, "LT" => 370, "LV" => 371, "EE" => 372, "MD" => 373, "AM" => 374, "BY" => 375, "AD" => 376, "MC" => 377, "SM" => 378, "VA" => 379, "UA" => 380, "CS" => 381, "YU" => 381, "HR" => 385, "SI" => 386, "BA" => 387, "EU" => 388, "MK" => 389, "IT" => 39, "RO" => 40, "CH" => 41, "CZ" => 420, "SK" => 421, "LI" => 423, "AT" => 43, "GB" => 44, "DK" => 45, "SE" => 46, "NO" => 47, "PL" => 48, "DE" => 49, "FK" => 500, "BZ" => 501, "GT" => 502, "SV" => 503, "HN" => 504, "NI" => 505, "CR" => 506, "PA" => 507, "PM" => 508, "HT" => 509, "PE" => 51, "MX" => 52, "CU" => 53, "AR" => 54, "BR" => 55, "CL" => 56, "CO" => 57, "VE" => 58, "GP" => 590, "BO" => 591, "GY" => 592, "EC" => 593, "GF" => 594, "PY" => 595, "MQ" => 596, "SR" => 597, "UY" => 598, "AN" => 599, "MY" => 60, "AU" => 61, "CC" => 61, "CX" => 61, "ID" => 62, "PH" => 63, "NZ" => 64, "SG" => 65, "TH" => 66, "TL" => 670, "AQ" => 672, "NF" => 672, "BN" => 673, "NR" => 674, "PG" => 675, "TO" => 676, "SB" => 677, "VU" => 678, "FJ" => 679, "PW" => 680, "WF" => 681, "CK" => 682, "NU" => 683, "AS" => 684, "WS" => 685, "KI" => 686, "NC" => 687, "TV" => 688, "PF" => 689, "TK" => 690, "FM" => 691, "MH" => 692, "RU" => 7, "KZ" => 7, "XF" => 800, "XC" => 808, "JP" => 81, "KR" => 82, "VN" => 84, "KP" => 850, "HK" => 852, "MO" => 853, "KH" => 855, "LA" => 856, "CN" => 86, "XS" => 870, "XE" => 871, "XP" => 872, "XI" => 873, "XW" => 874, "XU" => 878, "BD" => 880, "XG" => 881, "XN" => 882, "TW" => 886, "TR" => 90, "IN" => 91, "PK" => 92, "AF" => 93, "LK" => 94, "MM" => 95, "MV" => 960, "LB" => 961, "JO" => 962, "SY" => 963, "IQ" => 964, "KW" => 965, "SA" => 966, "YE" => 967, "OM" => 968, "PS" => 970, "AE" => 971, "IL" => 972, "BH" => 973, "QA" => 974, "BT" => 975, "MN" => 976, "NP" => 977, "XR" => 979, "IR" => 98, "XT" => 991, "TJ" => 992, "TM" => 993, "AZ" => 994, "GE" => 995, "KG" => 996, "UZ" => 998);
    if (isset($mapc[$countryCode])) {
        return $mapc[$countryCode];
    }
    return 1;
}
function internetbs_chekPhone($phoneNumber)
{
    $phoneNumber = str_replace(" ", "", $phoneNumber);
    $phoneNumber = str_replace("\t", "", $phoneNumber);
    if (preg_match("/^\\+[0-9]{1,4}\\.[0-9 ]+\$/i", $phoneNumber)) {
        return true;
    }
    return false;
}
function internetbs_reformatPhone($phoneNumber, $countryCode)
{
    $countryPhoneCode = internetbs_mapcountry($countryCode);
    $plus = 0;
    $country = "";
    $scontrol = trim($phoneNumber);
    $l = strlen($scontrol);
    if ($scontrol[0] == "+") {
        $plus = true;
    }
    $scontrol = preg_replace("#\\D*#si", "", $scontrol);
    if ($plus) {
        $scontrol = "+" . $scontrol;
    }
    if (!$l) {
        return "+" . $countryPhoneCode . ".1111111";
    }
    if (strncmp($scontrol, "00", 2) == 0) {
        $scontrol = "+" . substr($scontrol, 2);
        if (strlen($scontrol) == 1) {
            $scontrol = "1111111";
        }
    }
    $rphone = "+1.1111111";
    if ($scontrol[0] == "+") {
        for ($i = 2; $i < strlen($scontrol); $i++) {
            $first = substr($scontrol, 1, $i - 1);
            if ($first == $countryPhoneCode) {
                $scontrol = "+" . $first . "." . substr($scontrol, $i);
                return $scontrol;
            }
        }
        $scontrol = trim($scontrol, "+");
        $rphone = "+" . $countryPhoneCode . "." . $scontrol;
    } else {
        $rphone = "+" . $countryPhoneCode . "." . $scontrol;
    }
    if (internetbs_chekphone($rphone)) {
        return $rphone;
    }
    return "+1.1111111";
}
function internetbs_get_utf8_parameters($params)
{
    $config = array();
    $result = full_query("SELECT setting, value FROM tblconfiguration;");
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
        $config[strtolower($row["setting"])] = $row["value"];
    }
    if (strtolower($config["charset"]) != "utf-8" && strtolower($config["charset"]) != "utf8") {
        return $params;
    }
    $result = full_query("SELECT orderid FROM tbldomains WHERE id=" . (int) $params["domainid"] . " LIMIT 1;");
    if (!($row = mysql_fetch_array($result, MYSQL_ASSOC))) {
        return $params;
    }
    $result = full_query("SELECT userid,contactid FROM tblorders WHERE id=" . (int) $row["orderid"] . " LIMIT 1;");
    if (!($row = mysql_fetch_array($result, MYSQL_ASSOC))) {
        return $params;
    }
    if ($row["contactid"]) {
        $result = full_query("SELECT firstname, lastname, companyname, email, address1, address2, city, state, postcode, country, phonenumber FROM tblcontacts WHERE id=" . (int) $row["contactid"] . " LIMIT 1;");
        if (!($row = mysql_fetch_array($result, MYSQL_ASSOC))) {
            return $params;
        }
        foreach ($row as $key => $value) {
            $params[$key] = $value;
        }
    } else {
        if ($row["userid"]) {
            $result = full_query("SELECT firstname, lastname, companyname, email, address1, address2, city, state, postcode, country, phonenumber FROM tblclients WHERE id=" . (int) $row["userid"] . " LIMIT 1;");
            if (!($row = mysql_fetch_array($result, MYSQL_ASSOC))) {
                return $params;
            }
            foreach ($row as $key => $value) {
                $params[$key] = $value;
            }
        }
    }
    if ($config["registraradminuseclientdetails"]) {
        $params["adminfirstname"] = $params["firstname"];
        $params["adminlastname"] = $params["lastname"];
        $params["admincompanyname"] = $params["companyname"];
        $params["adminemail"] = $params["email"];
        $params["adminaddress1"] = $params["address1"];
        $params["adminaddress2"] = $params["address2"];
        $params["admincity"] = $params["city"];
        $params["adminstate"] = $params["state"];
        $params["adminpostcode"] = $params["postcode"];
        $params["admincountry"] = $params["country"];
        $params["adminphonenumber"] = $params["phonenumber"];
    } else {
        $params["adminfirstname"] = $config["registraradminfirstname"];
        $params["adminlastname"] = $config["registraradminlastname"];
        $params["admincompanyname"] = $config["registraradmincompanyname"];
        $params["adminemail"] = $config["registraradminemailaddress"];
        $params["adminaddress1"] = $config["registraradminaddress1"];
        $params["adminaddress2"] = $config["registraradminaddress2"];
        $params["admincity"] = $config["registraradmincity"];
        $params["adminstate"] = $config["registraradminstateprovince"];
        $params["adminpostcode"] = $config["registraradminpostalcode"];
        $params["admincountry"] = $config["registraradmincountry"];
        $params["adminphonenumber"] = $config["registraradminphone"];
    }
    return $params;
}
function internetbs_getCountryCodeByName($countryName)
{
    $country = array("AFGHANISTAN" => "AF", "ALAND ISLANDS" => "AX", "ALBANIA" => "AL", "ALGERIA" => "DZ", "AMERICAN SAMOA" => "AS", "ANDORRA" => "AD", "ANGOLA" => "AO", "ANGUILLA" => "AI", "ANTARCTICA" => "AQ", "ANTIGUA AND BARBUDA" => "AG", "ARGENTINA" => "AR", "ARMENIA" => "AM", "ARUBA" => "AW", "AUSTRALIA" => "AU", "AUSTRIA" => "AT", "AZERBAIJAN" => "AZ", "BAHAMAS" => "BS", "BAHRAIN" => "BH", "BANGLADESH" => "BD", "BARBADOS" => "BB", "BELARUS" => "BY", "BELGIUM" => "BE", "BELIZE" => "BZ", "BENIN" => "BJ", "BERMUDA" => "BM", "BHUTAN" => "BT", "BOLIVIA" => "BO", "BOSNIA AND HERZEGOVINA" => "BA", "BOTSWANA" => "BW", "BOUVET ISLAND" => "BV", "BRAZIL" => "BR", "BRITISH INDIAN OCEAN TERRITORY" => "IO", "BRITISH VIRGIN ISLANDS" => "VG", "BRUNEI" => "BN", "BULGARIA" => "BG", "BURKINA FASO" => "BF", "BURUNDI" => "BI", "CAMBODIA" => "KH", "CAMEROON" => "CM", "CANADA" => "CA", "CAPE VERDE" => "CV", "CAYMAN ISLANDS" => "KY", "CENTRAL AFRICAN REPUBLIC" => "CF", "CHAD" => "TD", "CHILE" => "CL", "CHINA" => "CN", "CHRISTMAS ISLAND" => "CX", "COCOS (KEELING) ISLANDS" => "CC", "COLOMBIA" => "CO", "COMOROS" => "KM", "CONGO" => "CG", "COOK ISLANDS" => "CK", "COSTA RICA" => "CR", "CROATIA" => "HR", "CUBA" => "CU", "CYPRUS" => "CY", "CZECH REPUBLIC" => "CZ", "DEMOCRATIC REPUBLIC OF CONGO" => "CD", "DENMARK" => "DK", "DISPUTED TERRITORY" => "XX", "DJIBOUTI" => "DJ", "DOMINICA" => "DM", "DOMINICAN REPUBLIC" => "DO", "EAST TIMOR" => "TL", "ECUADOR" => "EC", "EGYPT" => "EG", "EL SALVADOR" => "SV", "EQUATORIAL GUINEA" => "GQ", "ERITREA" => "ER", "ESTONIA" => "EE", "ETHIOPIA" => "ET", "FALKLAND ISLANDS" => "FK", "FAROE ISLANDS" => "FO", "FEDERATED STATES OF MICRONESIA" => "FM", "FIJI" => "FJ", "FINLAND" => "FI", "FRANCE" => "FR", "FRENCH GUYANA" => "GF", "FRENCH POLYNESIA" => "PF", "FRENCH SOUTHERN TERRITORIES" => "TF", "GABON" => "GA", "GAMBIA" => "GM", "GEORGIA" => "GE", "GERMANY" => "DE", "GHANA" => "GH", "GIBRALTAR" => "GI", "GREECE" => "GR", "GREENLAND" => "GL", "GRENADA" => "GD", "GUADELOUPE" => "GP", "GUAM" => "GU", "GUATEMALA" => "GT", "GUERNSEY" => "GG", "GUINEA" => "GN", "GUINEA-BISSAU" => "GW", "GUYANA" => "GY", "HAITI" => "HT", "HEARD ISLAND AND MCDONALD ISLANDS" => "HM", "HONDURAS" => "HN", "HONG KONG" => "HK", "HUNGARY" => "HU", "ICELAND" => "IS", "INDIA" => "IN", "INDONESIA" => "ID", "IRAN" => "IR", "IRAQ" => "IQ", "IRAQ-SAUDI ARABIA NEUTRAL ZONE" => "XE", "IRELAND" => "IE", "ISRAEL" => "IL", "ISLE OF MAN" => "IM", "ITALY" => "IT", "IVORY COAST" => "CI", "JAMAICA" => "JM", "JAPAN" => "JP", "JERSEY" => "JE", "JORDAN" => "JO", "KAZAKHSTAN" => "KZ", "KENYA" => "KE", "KIRIBATI" => "KI", "KUWAIT" => "KW", "KYRGYZSTAN" => "KG", "LAOS" => "LA", "LATVIA" => "LV", "LEBANON" => "LB", "LESOTHO" => "LS", "LIBERIA" => "LR", "LIBYA" => "LY", "LIECHTENSTEIN" => "LI", "LITHUANIA" => "LT", "LUXEMBOURG" => "LU", "MACAU" => "MO", "MACEDONIA" => "MK", "MADAGASCAR" => "MG", "MALAWI" => "MW", "MALAYSIA" => "MY", "MALDIVES" => "MV", "MALI" => "ML", "MALTA" => "MT", "MARSHALL ISLANDS" => "MH", "MARTINIQUE" => "MQ", "MAURITANIA" => "MR", "MAURITIUS" => "MU", "MAYOTTE" => "YT", "MEXICO" => "MX", "MOLDOVA" => "MD", "MONACO" => "MC", "MONGOLIA" => "MN", "MONTSERRAT" => "MS", "MOROCCO" => "MA", "MOZAMBIQUE" => "MZ", "MYANMAR" => "MM", "NAMIBIA" => "NA", "NAURU" => "NR", "NEPAL" => "NP", "NETHERLANDS" => "NL", "NETHERLANDS ANTILLES" => "AN", "NEW CALEDONIA" => "NC", "NEW ZEALAND" => "NZ", "NICARAGUA" => "NI", "NIGER" => "NE", "NIGERIA" => "NG", "NIUE" => "NU", "NORFOLK ISLAND" => "NF", "NORTH KOREA" => "KP", "NORTHERN MARIANA ISLANDS" => "MP", "NORWAY" => "NO", "OMAN" => "OM", "PAKISTAN" => "PK", "PALAU" => "PW", "PALESTINIAN OCCUPIED TERRITORIES" => "PS", "PANAMA" => "PA", "PAPUA NEW GUINEA" => "PG", "PARAGUAY" => "PY", "PERU" => "PE", "PHILIPPINES" => "PH", "PITCAIRN ISLANDS" => "PN", "POLAND" => "PL", "PORTUGAL" => "PT", "PUERTO RICO" => "PR", "QATAR" => "QA", "REUNION" => "RE", "ROMANIA" => "RO", "RUSSIA" => "RU", "RWANDA" => "RW", "SAINT HELENA AND DEPENDENCIES" => "SH", "SAINT KITTS AND NEVIS" => "KN", "SAINT LUCIA" => "LC", "SAINT PIERRE AND MIQUELON" => "PM", "SAINT VINCENT AND THE GRENADINES" => "VC", "SAMOA" => "WS", "SAN MARINO" => "SM", "SAO TOME AND PRINCIPE" => "ST", "SAUDI ARABIA" => "SA", "SENEGAL" => "SN", "SEYCHELLES" => "SC", "SIERRA LEONE" => "SL", "SINGAPORE" => "SG", "SLOVAKIA" => "SK", "SLOVENIA" => "SI", "SOLOMON ISLANDS" => "SB", "SOMALIA" => "SO", "SOUTH AFRICA" => "ZA", "SOUTH GEORGIA AND SOUTH SANDWICH ISLANDS" => "GS", "SOUTH KOREA" => "KR", "SPAIN" => "ES", "SPRATLY ISLANDS" => "PI", "SRI LANKA" => "LK", "SUDAN" => "SD", "SURINAME" => "SR", "SVALBARD AND JAN MAYEN" => "SJ", "SWAZILAND" => "SZ", "SWEDEN" => "SE", "SWITZERLAND" => "CH", "SYRIA" => "SY", "TAIWAN" => "TW", "TAJIKISTAN" => "TJ", "TANZANIA" => "TZ", "THAILAND" => "TH", "TOGO" => "TG", "TOKELAU" => "TK", "TONGA" => "TO", "TRINIDAD AND TOBAGO" => "TT", "TUNISIA" => "TN", "TURKEY" => "TR", "TURKMENISTAN" => "TM", "TURKS AND CAICOS ISLANDS" => "TC", "TUVALU" => "TV", "UGANDA" => "UG", "UKRAINE" => "UA", "UNITED ARAB EMIRATES" => "AE", "UNITED KINGDOM" => "GB", "UNITED NATIONS NEUTRAL ZONE" => "XD", "UNITED STATES" => "US", "UNITED STATES MINOR OUTLYING ISLANDS" => "UM", "URUGUAY" => "UY", "US VIRGIN ISLANDS" => "VI", "UZBEKISTAN" => "UZ", "VANUATU" => "VU", "VATICAN CITY" => "VA", "VENEZUELA" => "VE", "VIETNAM" => "VN", "WALLIS AND FUTUNA" => "WF", "WESTERN SAHARA" => "EH", "YEMEN" => "YE", "ZAMBIA" => "ZM", "ZIMBABWE" => "ZW", "SERBIA" => "RS", "MONTENEGRO" => "ME", "SAINT MARTIN" => "MF", "SAINT BARTHELEMY" => "BL");
    return $country[$countryName];
}
function internetbs_getClientIp()
{
    return isset($_SERVER["HTTP_X_FORWARDED_FOR"]) ? $_SERVER["HTTP_X_FORWARDED_FOR"] : (isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : NULL);
}
function internetbs_get2CharDotITProvinceCode($province)
{
    $provinceFiltered = trim($province);
    $provinceNamesInPossibleVariants = array("Agrigento" => "AG", "Alessandria" => "AL", "Ancona" => "AN", "Aosta, Aoste (fr)" => "AO", "Aosta, Aoste" => "AO", "Aosta" => "AO", "Aoste" => "AO", "Arezzo" => "AR", "Ascoli Piceno" => "AP", "Ascoli-Piceno" => "AP", "Asti" => "AT", "Avellino" => "AV", "Bari" => "BA", "Barletta-Andria-Trani" => "BT", "Barletta Andria Trani" => "BT", "Belluno" => "BL", "Benevento" => "BN", "Bergamo" => "BG", "Biella" => "BI", "Bologna" => "BO", "Bolzano, Bozen (de)" => "BZ", "Bolzano, Bozen" => "BZ", "Bolzano" => "BZ", "Bozen" => "BZ", "Brescia" => "BS", "Brindisi" => "BR", "Cagliari" => "CA", "Caltanissetta" => "CL", "Campobasso" => "CB", "Carbonia-Iglesias" => "CI", "Carbonia Iglesias" => "CI", "Carbonia" => "CI", "Caserta" => "CE", "Catania" => "CT", "Catanzaro" => "CZ", "Chieti" => "CH", "Como" => "CO", "Cosenza" => "CS", "Cremona" => "CR", "Crotone" => "KR", "Cuneo" => "CN", "Enna" => "EN", "Fermo" => "FM", "Ferrara" => "FE", "Firenze" => "FI", "Foggia" => "FG", "Forli-Cesena" => "FC", "Forli Cesena" => "FC", "Forli" => "FC", "Frosinone" => "FR", "Genova" => "GE", "Gorizia" => "GO", "Grosseto" => "GR", "Imperia" => "IM", "Isernia" => "IS", "La Spezia" => "SP", "L'Aquila" => "AQ", "LAquila" => "AQ", "L-Aquila" => "AQ", "L Aquila" => "AQ", "Latina" => "LT", "Lecce" => "LE", "Lecco" => "LC", "Livorno" => "LI", "Lodi" => "LO", "Lucca" => "LU", "Macerata" => "MC", "Mantova" => "MN", "Massa-Carrara" => "MS", "Massa Carrara" => "MS", "Massa" => "MS", "Matera" => "MT", "Medio Campidano" => "VS", "Medio-Campidano" => "VS", "Medio" => "VS", "Messina" => "ME", "Milano" => "MI", "Modena" => "MO", "Monza e Brianza" => "MB", "Monza-e-Brianza" => "MB", "Monza-Brianza" => "MB", "Monza Brianza" => "MB", "Monza" => "MB", "Napoli" => "NA", "Novara" => "NO", "Nuoro" => "NU", "Ogliastra" => "OG", "Olbia-Tempio" => "OT", "Olbia Tempio" => "OT", "Olbia" => "OT", "Oristano" => "OR", "Padova" => "PD", "Palermo" => "PA", "Parma" => "PR", "Pavia" => "PV", "Perugia" => "PG", "Pesaro e Urbino" => "PU", "Pesaro-e-Urbino" => "PU", "Pesaro-Urbino" => "PU", "Pesaro Urbino" => "PU", "Pesaro" => "PU", "Pescara" => "PE", "Piacenza" => "PC", "Pisa" => "PI", "Pistoia" => "PT", "Pordenone" => "PN", "Potenza" => "PZ", "Prato" => "PO", "Ragusa" => "RG", "Ravenna" => "RA", "Reggio Calabria" => "RC", "Reggio-Calabria" => "RC", "Reggio" => "RC", "Reggio Emilia" => "RE", "Reggio-Emilia" => "RE", "Reggio" => "RE", "Rieti" => "RI", "Rimini" => "RN", "Roma" => "RM", "Rovigo" => "RO", "Salerno" => "SA", "Sassari" => "SS", "Savona" => "SV", "Siena" => "SI", "Siracusa" => "SR", "Sondrio" => "SO", "Taranto" => "TA", "Teramo" => "TE", "Terni" => "TR", "Torino" => "TO", "Trapani" => "TP", "Trento" => "TN", "Treviso" => "TV", "Trieste" => "TS", "Udine" => "UD", "Varese" => "VA", "Venezia" => "VE", "Verbano-Cusio-Ossola" => "VB", "Verbano Cusio Ossola" => "VB", "Verbano" => "VB", "Verbano-Cusio" => "VB", "Verbano-Ossola" => "VB", "Vercelli" => "VC", "Verona" => "VR", "Vibo Valentia" => "VV", "Vibo-Valentia" => "VV", "Vibo" => "VV", "Vicenza" => "VI", "Viterbo" => "VT");
    if (strlen($provinceFiltered) == 2) {
        return strtoupper($provinceFiltered);
    }
    $provinceFiltered = strtolower(preg_replace("/[^a-z]/i", "", $provinceFiltered));
    foreach ($provinceNamesInPossibleVariants as $name => $code) {
        if (strtolower(preg_replace("/[^a-z]/i", "", $name)) == $provinceFiltered) {
            return $code;
        }
    }
    return $province;
}
function internetbs_getItProvinceCode($inputElementValue)
{
    $code = "RM";
    preg_match("/\\[\\s*([a-z]{2})\\s*\\]\$/i", $inputElementValue, $matches);
    if (isset($matches[1])) {
        $code = $matches[1];
    }
    return $code;
}

?>