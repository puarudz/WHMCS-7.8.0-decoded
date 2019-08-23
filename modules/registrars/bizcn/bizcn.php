<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

$globalMessage = "";
$globalServerUrl = "";
$globalParams = "";
function bizcn_getConfigArray()
{
    $configarray = array("Username" => array("Type" => "text", "Size" => "20", "Description" => "Enter your username here"), "Password" => array("Type" => "password", "Size" => "20", "Description" => "Enter your password here"), "TestMode" => array("Type" => "yesno"));
    return $configarray;
}
function bizcn_GetNameservers($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $_params = array("username" => $username, "password" => md5($password), "testmode" => $testmode, "module" => "getdomaindns", "domainname" => $sld . "." . $tld);
    $result = com_71_call($_params, $testmode);
    if (empty($result)) {
        $error = "return is null";
        $values["error"] = $error;
        return $values;
    }
    if (substr($result, 0, 3) == 200) {
        $_result = explode("\r\n", $result);
        list(, $nameserver1, $nameserver2, $nameserver3, $nameserver4, $nameserver5) = $_result;
        $values["ns1"] = $nameserver1;
        $values["ns2"] = $nameserver2;
        $values["ns3"] = $nameserver3;
        $values["ns4"] = $nameserver4;
        $values["ns5"] = $nameserver5;
        return $values;
    }
    $error = $result;
    $values["error"] = $error;
    return $values;
}
function bizcn_SaveNameservers($params)
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
    $dnsHost = "";
    if (!empty($nameserver1)) {
        $dnsHost .= "ANDdns_hostEQU" . $nameserver1;
    }
    if (!empty($nameserver2)) {
        $dnsHost .= "ANDdns_hostEQU" . $nameserver2;
    }
    if (!empty($nameserver3)) {
        $dnsHost .= "ANDdns_hostEQU" . $nameserver3;
    }
    if (!empty($nameserver4)) {
        $dnsHost .= "ANDdns_hostEQU" . $nameserver4;
    }
    if (!empty($nameserver5)) {
        $dnsHost .= "ANDdns_hostEQU" . $nameserver5;
    }
    $dnsHost = substr($dnsHost, strlen("ANDdns_hostEQU"));
    $_params = array("username" => $username, "password" => md5($password), "testmode" => $testmode, "module" => "moddomaindns", "domainname" => $sld . "." . $tld, "dns_host" => $dnsHost);
    $result = com_71_call($_params, $testmode);
    if (empty($result)) {
        $error = "return is null";
        $values["error"] = $error;
        return $values;
    }
    if (substr($result, 0, 3) == 200) {
        return true;
    }
    $error = $result;
    $values["error"] = $error;
    return $values;
}
function bizcn_GetRegistrarLock($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $_params = array("username" => $username, "password" => md5($password), "testmode" => $testmode, "module" => "getdomainlock", "domainname" => $sld . "." . $tld);
    $result = com_71_call($_params, $testmode);
    if (empty($result)) {
        return false;
    }
    if (substr($result, 0, 3) == 200) {
        $_result = explode("\r\n", $result);
        $_lock = trim(substr($_result[1], 5));
        if ($_lock == "true") {
            $lock = "1";
        } else {
            $lock = "0";
        }
        if ($lock == "1") {
            $lockstatus = "locked";
        } else {
            $lockstatus = "unlocked";
        }
        return $lockstatus;
    }
    return false;
}
function bizcn_SaveRegistrarLock($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    if ($params["lockenabled"] == "locked") {
        $lockstatus = "lockdomain";
    } else {
        $lockstatus = "unlockdomain";
    }
    $domainname = $sld . "." . $tld;
    $_params = array("username" => $username, "password" => md5($password), "testmode" => $testmode, "module" => $lockstatus, "domainname" => $domainname);
    $result = com_71_call($_params, $testmode);
    if (empty($result)) {
        $error = "return is null";
    }
    if (substr($result, 0, 3) == 200) {
        return true;
    }
    $error = $result;
    $values["error"] = $error;
    return $values;
}
function bizcn_GetEmailForwarding($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    foreach ($result as $value) {
        $values[$counter]["prefix"] = $value["prefix"];
        $values[$counter]["forwardto"] = $value["forwardto"];
    }
    return $values;
}
function bizcn_SaveEmailForwarding($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    foreach ($params["prefix"] as $key => $value) {
        $forwardarray[$key]["prefix"] = $params["prefix"][$key];
        $forwardarray[$key]["forwardto"] = $params["forwardto"][$key];
    }
}
function bizcn_GetDNS($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $_params = array("username" => $username, "password" => md5($password), "testmode" => $testmode, "module" => "getdnsrecord", "domainname" => $sld . "." . $tld);
    $result = com_71_call($_params, $testmode);
    if (empty($result)) {
        return false;
    }
    if (substr($result, 0, 3) == 200) {
        $_result = explode("\r\n", $result);
        $hostrecords = array();
        foreach ($_result as $_key => $_value) {
            $_tmpNS = explode("|", $_value);
            if (0 < $_key) {
                $hostrecords[] = array("hostname" => $_tmpNS[0], "type" => $_tmpNS[1], "address" => $_tmpNS[2]);
            }
        }
        return $hostrecords;
    } else {
        return false;
    }
}
function bizcn_SaveDNS($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $dnsData = $params["dnsrecords"];
    $tmpDnsData = $dnsData;
    $trimDnsData = array();
    $tmpResult = bizcn_getdns($params);
    if ($tmpResult === false) {
        $error = "get dns is error";
        $values["error"] = $error;
        return $values;
    }
    $hostrecords = $tmpResult;
    if (!is_array($hostrecords) && !empty($hostrecords)) {
        $hostrecords = (array) $hostrecords;
    }
    $i = 0;
    if (0 < count($hostrecords)) {
        foreach ($hostrecords as $_key => $_value) {
            $isExistHostname = false;
            foreach ($dnsData as $_key1 => $_value1) {
                if ($oHostname == $_value1["hostname"] && $oType == $_value1["type"] && $oAddress == $_value1["address"]) {
                    $dnsData[$_key1]["func"] = "NULL";
                    $isExistHostname = true;
                    $trimDnsData[] = $dnsData[$_key1];
                    unset($tmpDnsData[$_key1]);
                    continue;
                }
                if ($oHostname == $_value1["hostname"] && $oType == $_value1["type"]) {
                    $dnsData[$_key1]["func"] = "MOD";
                    $dnsData[$_key1]["oldAddress"] = $oAddress;
                    $isExistHostname = true;
                    unset($tmpDnsData[$_key1]);
                    $trimDnsData[] = $dnsData[$_key1];
                    continue;
                }
                if ($isExistHostname === true) {
                    continue;
                }
            }
            if ($isExistHostname == false) {
                $hostrecords[$_key]["func"] = "DEL";
                $trimDnsData[] = $hostrecords[$_key];
            }
        }
    }
    if (0 < count($tmpDnsData)) {
        foreach ($tmpDnsData as $_key2 => $_value2) {
            $tmpDnsData[$_key2]["func"] = "ADD";
            $trimDnsData[] = $tmpDnsData[$_key2];
        }
    }
    if (0 < count($trimDnsData)) {
        foreach ($trimDnsData as $_key3 => $_value3) {
            $func = $_value3["func"];
            if ($func == "NULL") {
                continue;
            }
            if ($func == "ADD") {
                $_params = array("username" => $username, "password" => md5($password), "module" => "adddnsrecord", "domainname" => $sld . "." . $tld, "resolvetype" => $_value3["type"], "resolvehost" => $_value3["hostname"], "resolvevalue" => $_value3["address"], "mxlevel" => 10);
                $result = com_71_call($_params, $testmode);
                if (empty($result)) {
                    $error = "return is null";
                    $values["error"] = $error;
                    return $values;
                }
                if (substr($result, 0, 3) == 200) {
                } else {
                    $error = $result;
                    if (empty($error)) {
                        $error = "add unknow error";
                    }
                    $values["error"] = $error;
                    return $values;
                }
            }
            if ($func == "MOD") {
                $_params = array("username" => $username, "password" => md5($password), "module" => "moddnsrecord", "domainname" => $sld . "." . $tld, "resolvetype" => $_value3["type"], "resolvehost" => $_value3["hostname"], "resolveoldvalue" => $_value3["oldAddress"], "resolvevalue" => $_value3["address"], "mxlevel" => 10);
                $result = com_71_call($_params, $testmode);
                if (empty($result)) {
                    $error = "return is null";
                    $values["error"] = $error;
                    return $values;
                }
                if (substr($result, 0, 3) == 200) {
                } else {
                    $error = $result;
                    if (empty($error)) {
                        $error = "mod unknow error";
                    }
                    $values["error"] = $error;
                    return $values;
                }
            }
            if ($func == "DEL") {
                $_params = array("username" => $username, "password" => md5($password), "module" => "deldnsrecord", "domainname" => $sld . "." . $tld, "resolvetype" => $_value3["type"], "resolvehost" => $_value3["hostname"], "resolveoldvalue" => $_value3["address"], "mxlevel" => 10);
                $result = com_71_call($_params, $testmode);
                if (empty($result)) {
                    $error = "return is null";
                    $values["error"] = $error;
                    return $values;
                }
                if (substr($result, 0, 3) == 200) {
                } else {
                    $error = $result;
                    if (empty($error)) {
                        $error = "del unknow error";
                    }
                    $values["error"] = $error;
                    return $values;
                }
            }
        }
    }
    return true;
}
function bizcn_RegisterDomain($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $regperiod = $params["regperiod"];
    $nameserver1 = $params["ns1"];
    $nameserver2 = $params["ns2"];
    $nameserver3 = $params["ns3"];
    $nameserver4 = $params["ns4"];
    $nameserver5 = $params["ns5"];
    $RegistrantFirstName = $params["firstname"];
    $RegistrantLastName = $params["lastname"];
    $RegistrantCompanyName = $params["companyname"];
    if (empty($RegistrantCompanyName)) {
        $RegistrantCompanyName = $RegistrantFirstName . " " . $RegistrantLastName;
    }
    $RegistrantAddress1 = $params["address1"];
    $RegistrantAddress2 = $params["address2"];
    $RegistrantCity = $params["city"];
    $RegistrantStateProvince = $params["state"];
    $RegistrantPostalCode = $params["postcode"];
    $RegistrantCountry = $params["country"];
    $RegistrantEmailAddress = $params["email"];
    $RegistrantPhone = $params["phonenumber"];
    $AdminFirstName = $params["adminfirstname"];
    $AdminLastName = $params["adminlastname"];
    $AdminAddress1 = $params["adminaddress1"];
    $AdminAddress2 = $params["adminaddress2"];
    $AdminCity = $params["admincity"];
    $AdminStateProvince = $params["adminstate"];
    $AdminPostalCode = $params["adminpostcode"];
    $AdminCountry = $params["admincountry"];
    $AdminEmailAddress = $params["adminemail"];
    $AdminPhone = $params["adminphonenumber"];
    $DnsIp1 = $params["dns_ip1"];
    $DnsIp2 = $params["dns_ip2"];
    $_params = array("username" => $username, "password" => md5($password), "module" => "adddomain", "domainname" => $sld . "." . $tld, "term" => $regperiod, "dns_host1" => $nameserver1, "dns_host2" => $nameserver2, "dom_org" => $RegistrantCompanyName, "dom_fn" => $RegistrantFirstName, "dom_ln" => $RegistrantLastName, "dom_adr1" => $RegistrantAddress1, "dom_adr2" => $RegistrantAddress2, "dom_ct" => $RegistrantCity, "dom_st" => $RegistrantStateProvince, "dom_co" => $RegistrantCountry, "dom_ph" => $RegistrantPhone, "dom_pc" => $RegistrantPostalCode, "dom_em" => $RegistrantEmailAddress, "admi_fn" => $AdminFirstName, "admi_ln" => $AdminLastName, "admi_adr1" => $AdminAddress1, "admi_adr2" => $AdminAddress2, "admi_ct" => $AdminCity, "admi_st" => $AdminStateProvince, "admi_co" => $AdminCountry, "admi_ph" => $AdminPhone, "admi_pc" => $AdminPostalCode, "admi_em" => $AdminEmailAddress, "tech_fn" => $AdminFirstName, "tech_ln" => $AdminLastName, "tech_adr1" => $AdminAddress1, "tech_adr2" => $AdminAddress2, "tech_ct" => $AdminCity, "tech_st" => $AdminStateProvince, "tech_co" => $AdminCountry, "tech_ph" => $AdminPhone, "tech_pc" => $AdminPostalCode, "tech_em" => $AdminEmailAddress, "bill_fn" => $AdminFirstName, "bill_ln" => $AdminLastName, "bill_adr1" => $AdminAddress1, "bill_adr2" => $AdminAddress2, "bill_ct" => $AdminCity, "bill_st" => $AdminStateProvince, "bill_co" => $AdminCountry, "bill_ph" => $AdminPhone, "bill_pc" => $AdminPostalCode, "bill_em" => $AdminEmailAddress, "dns_ip1" => $DnsIp1, "dns_ip2" => $DnsIp2);
    $result = com_71_call($_params, $testmode);
    if (empty($result)) {
        return false;
    }
    if (substr($result, 0, 3) == 200) {
        return true;
    }
    $error = $result;
    $values["error"] = $error;
    return $values;
}
function bizcn_TransferDomain($params)
{
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
    $RegistrantAddress1 = $params["address1"];
    $RegistrantAddress2 = $params["address2"];
    $RegistrantCity = $params["city"];
    $RegistrantStateProvince = $params["state"];
    $RegistrantPostalCode = $params["postcode"];
    $RegistrantCountry = $params["country"];
    $RegistrantEmailAddress = $params["email"];
    $RegistrantPhone = $params["phonenumber"];
    $AdminFirstName = $params["adminfirstname"];
    $AdminLastName = $params["adminlastname"];
    $AdminAddress1 = $params["adminaddress1"];
    $AdminAddress2 = $params["adminaddress2"];
    $AdminCity = $params["admincity"];
    $AdminStateProvince = $params["adminstate"];
    $AdminPostalCode = $params["adminpostcode"];
    $AdminCountry = $params["admincountry"];
    $AdminEmailAddress = $params["adminemail"];
    $AdminPhone = $params["adminphonenumber"];
    $_params = array("username" => $username, "password" => md5($password), "testmode" => $testmode, "tld" => $tld, "sld" => $sld, "regperiod" => $regperiod, "transfersecret" => $transfersecret, "nameserver1" => $nameserver1, "nameserver2" => $nameserver2, "nameserver3" => $nameserver3, "nameserver4" => $nameserver4, "nameserver5" => $nameserver5, "RegistrantFirstName" => $RegistrantFirstName, "RegistrantLastName" => $RegistrantLastName, "RegistrantAddress1" => $RegistrantAddress1, "RegistrantAddress2" => $RegistrantAddress2, "RegistrantCity" => $RegistrantCity, "RegistrantStateProvince" => $RegistrantStateProvince, "RegistrantPostalCode" => $RegistrantPostalCode, "RegistrantCountry" => $RegistrantCountry, "RegistrantEmailAddress" => $RegistrantEmailAddress, "RegistrantPhone" => $RegistrantPhone, "AdminFirstName" => $AdminFirstName, "AdminLastName" => $AdminLastName, "AdminAddress1" => $AdminAddress1, "AdminAddress2" => $AdminAddress2, "AdminCity" => $AdminCity, "AdminStateProvince" => $AdminStateProvince, "AdminPostalCode" => $AdminPostalCode, "AdminCountry" => $AdminCountry, "AdminEmailAddress" => $AdminEmailAddress, "AdminPhone" => $AdminPhone);
    $result = com_71_call($_params, $testmode);
    if (empty($result)) {
        return false;
    }
    if (substr($result, 0, 3) == 200) {
        return true;
    }
    $error = $result;
    $values["error"] = $error;
    return $values;
}
function bizcn_RenewDomain($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $regperiod = $params["regperiod"];
    $_params = array("username" => $username, "password" => md5($password), "module" => "renewdomain", "domain" => $sld . "." . $tld, "term" => $regperiod);
    $result = com_71_call($_params, $testmode);
    if (substr($result, 0, 3) == 200) {
        return true;
    }
    $error = $result;
    $values["error"] = $error;
    return $values;
}
function bizcn_GetContactDetails($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $_params = array("username" => $username, "password" => md5($password), "testmode" => $testmode, "module" => "getcontactdetails", "domainname" => $sld . "." . $tld);
    $result = com_71_call($_params, $testmode);
    if (empty($result)) {
        $values["error"] = "return is null";
        return $values;
    }
    if (substr($result, 0, 3) == 200) {
        $_result = explode("\r\n", $result);
        if (0 < count($_result)) {
            for ($i = 1; $i < count($_result); $i++) {
                $arr_split = explode(":", $_result[$i]);
                $arr_results[$arr_split[0]] = $arr_split[1];
            }
        }
    } else {
        $values["error"] = $result;
    }
    $values["Registrant"]["First Name"] = $arr_results["dom_fn"];
    $values["Registrant"]["Last Name"] = $arr_results["dom_ln"];
    $values["Registrant"]["Organization"] = $arr_results["dom_org"];
    $values["Registrant"]["Address1"] = $arr_results["dom_adr1"];
    $values["Registrant"]["Address2"] = $arr_results["dom_adr2"];
    $values["Registrant"]["City"] = $arr_results["dom_ct"];
    $values["Registrant"]["State Province"] = $arr_results["dom_st"];
    $values["Registrant"]["Country"] = $arr_results["dom_co"];
    $values["Registrant"]["Phone"] = $arr_results["dom_ph"];
    $values["Registrant"]["Email Address"] = $arr_results["dom_em"];
    $values["Registrant"]["Postcode"] = $arr_results["dom_pc"];
    $values["Admin"]["First Name"] = $arr_results["admi_fn"];
    $values["Admin"]["Last Name"] = $arr_results["admi_ln"];
    $values["Admin"]["Address1"] = $arr_results["admi_adr1"];
    $values["Admin"]["Address2"] = $arr_results["admi_adr2"];
    $values["Admin"]["City"] = $arr_results["admi_ct"];
    $values["Admin"]["State Province"] = $arr_results["admi_st"];
    $values["Admin"]["Country"] = $arr_results["admi_co"];
    $values["Admin"]["Phone"] = $arr_results["admi_ph"];
    $values["Admin"]["Email Address"] = $arr_results["admi_em"];
    $values["Admin"]["Postcode"] = $arr_results["admi_pc"];
    $values["Tech"]["First Name"] = $arr_results["tech_fn"];
    $values["Tech"]["Last Name"] = $arr_results["tech_ln"];
    $values["Tech"]["Address1"] = $arr_results["tech_adr1"];
    $values["Tech"]["Address2"] = $arr_results["tech_adr2"];
    $values["Tech"]["City"] = $arr_results["tech_ct"];
    $values["Tech"]["State Province"] = $arr_results["tech_st"];
    $values["Tech"]["Country"] = $arr_results["tech_co"];
    $values["Tech"]["Phone"] = $arr_results["tech_ph"];
    $values["Tech"]["Email Address"] = $arr_results["tech_em"];
    $values["Tech"]["Postcode"] = $arr_results["tech_pc"];
    $values["Bill"]["First Name"] = $arr_results["bill_fn"];
    $values["Bill"]["Last Name"] = $arr_results["bill_ln"];
    $values["Bill"]["Address1"] = $arr_results["bill_adr1"];
    $values["Bill"]["Address2"] = $arr_results["bill_adr2"];
    $values["Bill"]["City"] = $arr_results["bill_ct"];
    $values["Bill"]["State Province"] = $arr_results["bill_st"];
    $values["Bill"]["Country"] = $arr_results["bill_co"];
    $values["Bill"]["Phone"] = $arr_results["bill_ph"];
    $values["Bill"]["Email Address"] = $arr_results["bill_em"];
    $values["Bill"]["Postcode"] = $arr_results["bill_pc"];
    return $values;
}
function bizcn_SaveContactDetails($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $dom_fn = $params["contactdetails"]["Registrant"]["First Name"];
    $dom_ln = $params["contactdetails"]["Registrant"]["Last Name"];
    $dom_org = $params["contactdetails"]["Registrant"]["Organization"];
    $dom_adr1 = $params["contactdetails"]["Registrant"]["Address1"];
    $dom_adr2 = $params["contactdetails"]["Registrant"]["Address2"];
    $dom_ct = $params["contactdetails"]["Registrant"]["City"];
    $dom_st = $params["contactdetails"]["Registrant"]["State Province"];
    $dom_co = $params["contactdetails"]["Registrant"]["Country"];
    $dom_ph = $params["contactdetails"]["Registrant"]["Phone"];
    $dom_em = $params["contactdetails"]["Registrant"]["Email Address"];
    $dom_pc = $params["contactdetails"]["Registrant"]["Postcode"];
    $admi_fn = $params["contactdetails"]["Admin"]["First Name"];
    $admi_ln = $params["contactdetails"]["Admin"]["Last Name"];
    $admi_adr1 = $params["contactdetails"]["Admin"]["Address1"];
    $admi_adr2 = $params["contactdetails"]["Admin"]["Address2"];
    $admi_ct = $params["contactdetails"]["Admin"]["City"];
    $admi_st = $params["contactdetails"]["Admin"]["State Province"];
    $admi_co = $params["contactdetails"]["Admin"]["Country"];
    $admi_ph = $params["contactdetails"]["Admin"]["Phone"];
    $admi_em = $params["contactdetails"]["Admin"]["Email Address"];
    $admi_pc = $params["contactdetails"]["Admin"]["Postcode"];
    $tech_fn = $params["contactdetails"]["Tech"]["First Name"];
    $tech_ln = $params["contactdetails"]["Tech"]["Last Name"];
    $tech_adr1 = $params["contactdetails"]["Tech"]["Address1"];
    $tech_adr2 = $params["contactdetails"]["Tech"]["Address2"];
    $tech_ct = $params["contactdetails"]["Tech"]["City"];
    $tech_st = $params["contactdetails"]["Tech"]["State Province"];
    $tech_co = $params["contactdetails"]["Tech"]["Country"];
    $tech_ph = $params["contactdetails"]["Tech"]["Phone"];
    $tech_em = $params["contactdetails"]["Tech"]["Email Address"];
    $tech_pc = $params["contactdetails"]["Tech"]["Postcode"];
    $bill_fn = $params["contactdetails"]["Bill"]["First Name"];
    $bill_ln = $params["contactdetails"]["Bill"]["Last Name"];
    $bill_adr1 = $params["contactdetails"]["Bill"]["Address1"];
    $bill_adr2 = $params["contactdetails"]["Bill"]["Address2"];
    $bill_ct = $params["contactdetails"]["Bill"]["City"];
    $bill_st = $params["contactdetails"]["Bill"]["State Province"];
    $bill_co = $params["contactdetails"]["Bill"]["Country"];
    $bill_ph = $params["contactdetails"]["Bill"]["Phone"];
    $bill_em = $params["contactdetails"]["Bill"]["Email Address"];
    $bill_pc = $params["contactdetails"]["Bill"]["Postcode"];
    if (empty($bill_fn)) {
        $bill_fn = $admi_fn;
    }
    if (empty($bill_ln)) {
        $bill_ln = $bill_fn;
    }
    $domainname = $sld . "." . $tld;
    $_params = array("username" => $username, "password" => md5($password), "testmode" => $testmode, "module" => "savecontactdetails", "domainname" => $domainname, "dom_org" => $dom_org, "dom_fn" => $dom_fn, "dom_ln" => $dom_ln, "dom_adr1" => $dom_adr1, "dom_adr2" => $dom_adr2, "dom_ct" => $dom_ct, "dom_st" => $dom_st, "dom_co" => $dom_co, "dom_ph" => $dom_ph, "dom_pc" => $dom_pc, "dom_em" => $dom_em, "admi_fn" => $admi_fn, "admi_ln" => $admi_ln, "admi_adr1" => $admi_adr1, "admi_adr2" => $admi_adr2, "admi_ct" => $admi_ct, "admi_st" => $admi_st, "admi_co" => $admi_co, "admi_ph" => $admi_ph, "admi_pc" => $admi_pc, "admi_em" => $admi_em, "tech_fn" => $tech_fn, "tech_ln" => $tech_ln, "tech_adr1" => $tech_adr1, "tech_adr2" => $tech_adr2, "tech_ct" => $tech_ct, "tech_st" => $tech_st, "tech_co" => $tech_co, "tech_ph" => $tech_ph, "tech_pc" => $tech_pc, "tech_em" => $tech_em, "bill_fn" => $bill_fn, "bill_ln" => $bill_ln, "bill_adr1" => $bill_adr1, "bill_adr2" => $bill_adr2, "bill_ct" => $bill_ct, "bill_st" => $bill_st, "bill_co" => $bill_co, "bill_ph" => $bill_ph, "bill_pc" => $bill_pc, "bill_em" => $bill_em);
    $result = com_71_call($_params, $testmode);
    if (empty($result)) {
        $values["error"] = "return is null";
        return $values;
    }
    if (substr($result, 0, 3) == 200) {
        return true;
    }
    $error = $result;
    $values["error"] = $result;
    return $values;
}
function bizcn_GetEPPCode($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $values["eppcode"] = $eppcode;
    $values["error"] = $error;
    return $values;
}
function bizcn_RegisterNameserver($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $nameserver = $params["nameserver"];
    $ipaddress = $params["ipaddress"];
    $_params = array("username" => $username, "password" => md5($password), "testmode" => $testmode, "tld" => $tld, "sld" => $sld, "module" => "createnameserver", "hostname" => $nameserver, "ip" => $ipaddress);
    $result = com_71_call($_params, $testmode);
    if (empty($result)) {
        $error = "return is null";
        $values["error"] = $error;
        return $values;
    }
    if (substr($result, 0, 3) == 200) {
        return true;
    }
    $error = $result;
    $values["error"] = $error;
    return $values;
}
function bizcn_ModifyNameserver($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $nameserver = $params["nameserver"];
    $currentipaddress = $params["currentipaddress"];
    $newipaddress = $params["newipaddress"];
    $_params = array("username" => $username, "password" => md5($password), "testmode" => $testmode, "tld" => $tld, "sld" => $sld, "module" => "modnameserver", "hostname" => $nameserver, "oldip" => $currentipaddress, "newip" => $newipaddress);
    $result = com_71_call($_params, $testmode);
    if (empty($result)) {
        $error = "return is null";
        $values["error"] = $error;
        return $values;
    }
    if (substr($result, 0, 3) == 200) {
        return true;
    }
    $error = $result;
    $values["error"] = $error;
    return $values;
}
function bizcn_DeleteNameserver($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $nameserver = $params["nameserver"];
    $_params = array("username" => $username, "password" => md5($password), "testmode" => $testmode, "tld" => $tld, "sld" => $sld, "module" => "delnameserver", "hostname" => $nameserver);
    $result = com_71_call($_params, $testmode);
    if (empty($result)) {
        $error = "return is null";
        $values["error"] = $error;
        return $values;
    }
    if (substr($result, 0, 3) == 200) {
        return true;
    }
    $error = $result;
    $values["error"] = $error;
    return $values;
}
function com_71_call($params, $testModel = false)
{
    define("COM_71_API_TEST", "https://test.api.71.com/webrrpdomain");
    define("COM_71_API", "https://api.71.com/webrrpdomain");
    global $globalServerUrl;
    global $globalMessage;
    global $globalParams;
    if ($testModel == true) {
        $postUrl = COM_71_API_TEST;
    } else {
        $postUrl = COM_71_API;
    }
    $globalServerUrl = $postUrl;
    $sendParams = http_build_query($params, "", "&");
    $sendParams = str_replace("EQU", "=", $sendParams);
    $sendParams = str_replace("AND", "&", $sendParams);
    $globalParams = $sendParams;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $postUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $sendParams);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_NOBODY, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    $ret = curl_exec($ch);
    $globalMessage = $ret;
    logModuleCall("bizcn", $params["module"], $params, $ret, "", array($params["username"], $params["password"]));
    return $ret;
}

?>