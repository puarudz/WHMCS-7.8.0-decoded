<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function onlinenic_getConfigArray()
{
    $query = "CREATE TABLE IF NOT EXISTS `mod_onlinenic` (`id` int(10) NOT NULL auto_increment,`domain` VARCHAR(255) NOT NULL,`lockstatus` BOOL NOT NULL DEFAULT '0',PRIMARY KEY  (`id`),KEY `domainid` (`domain`))";
    $result = full_query($query);
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "OnlineNIC"), "Username" => array("Type" => "text", "Size" => "20", "Description" => "Onlinenic ID"), "Password" => array("Type" => "password", "Size" => "20", "Description" => "Password"), "TestMode" => array("Type" => "yesno"), "SyncNextDueDate" => array("Type" => "yesno", "Description", "Tick this box if you want the expiry date sync script to update the expiry and next due dates (cron must be configured)"));
    return $configarray;
}
function onlinenic_GetNameservers($params)
{
    $username = $params["Username"];
    $password = md5($params["Password"]);
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domain = $sld . "." . $tld;
    if ($testmode) {
        $username = 135610;
        $password = md5("654123");
    }
    $values = onlinenic_Login($fp, $username, $password, $testmode);
    if ($values["error"]) {
        return $values;
    }
    $domain_type = onlinenic_getDomainType($tld, $sld);
    $clTrid = substr(md5($domain), 0, 10) . mt_rand(1000000000, 9999999999.0);
    $checksum = md5($username . $password . $clTrid . "getdomaininfo");
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n            <epp>\n            <command>\n            <getdomaininfo>\n                <clID>" . $username . "</clID>\n                <domain>" . $domain . "</domain>\n                <domain:type>" . $domain_type . "</domain:type>\n                <options>\n                <version>1.0</version>\n                <lang>en</lang>\n                </options>\n                </getdomaininfo>\n                <clTRID>" . $clTrid . "</clTRID>\n                <chksum>" . $checksum . "</chksum>\n            </command>\n            </epp>";
    $result = onlinenic_sendCommand($fp, $xml);
    if (!$result) {
        return array("error" => "Domain not found");
    }
    $resultcode = onlinenic_getResultCode($result);
    onlinenic_Logout($fp, $username, $password);
    if ($resultcode != "1000") {
        $msg = onlinenic_GetValue($result, "<msg>", "</msg>");
        $error = onlinenic_GetValue($result, "<value>", "</value>");
        $error = $msg . " - " . $error;
        $errormsg = onlinenic_getResultText($resultcode);
        $values["error"] = (string) $resultcode . " - " . $errormsg . ": " . $error;
    } else {
        $nameserver1 = onlinenic_GetValue($result, "<dns1>", "</dns1>");
        $nameserver2 = onlinenic_GetValue($result, "<dns2>", "</dns2>");
        $values["ns1"] = trim($nameserver1);
        $values["ns2"] = trim($nameserver2);
        $values["ns3"] = trim($nameserver3);
        $values["ns4"] = trim($nameserver4);
        $values["ns5"] = trim($nameserver5);
    }
    return $values;
}
function onlinenic_SaveNameservers($params)
{
    $username = $params["Username"];
    $password = md5($params["Password"]);
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    if ($testmode) {
        $username = 135610;
        $password = md5("654123");
    }
    $domain = $sld . "." . $tld;
    $values = onlinenic_Login($fp, $username, $password, $testmode);
    if ($values["error"]) {
        return $values;
    }
    $domain_type = onlinenic_getDomainType($tld, $sld);
    $dns1 = $params["ns1"];
    $dns2 = $params["ns2"];
    $dns3 = $params["ns3"];
    $dns4 = $params["ns4"];
    $dns5 = $params["ns5"];
    $clTrid = substr(md5($domain), 0, 10) . mt_rand(1000000000, 9999999999.0);
    $checksum = md5($username . $password . $clTrid . "upddomain" . $domain_type . $domain . $dns1 . $dns2);
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n            <epp>\n                    <command>\n                            <update>\n                                    <domain:update>\n                                            <domain:type>" . $domain_type . "</domain:type>\n                                            <domain:name>" . $domain . "</domain:name>\n                                            <domain:rep>\n                                                    <domain:ns1>" . $dns1 . "</domain:ns1>\n                                                    <domain:ns2>" . $dns2 . "</domain:ns2>\n                                                    <domain:ns3>" . $dns3 . "</domain:ns3>\n                                                    <domain:ns4>" . $dns4 . "</domain:ns4>\n                                                    <domain:ns5>" . $dns5 . "</domain:ns5>\n                                            </domain:rep>\n                                    </domain:update>\n                            </update>\n                            <clTRID>" . $clTrid . "</clTRID>\n                            <chksum>" . $checksum . "</chksum>\n                    </command>\n            </epp>";
    $result = onlinenic_sendCommand($fp, $xml);
    $resultcode = onlinenic_getResultCode($result);
    onlinenic_Logout($fp, $username, $password);
    if ($resultcode != "1000") {
        $errormsg = onlinenic_getResultText($resultcode);
        $msg = onlinenic_GetValue($result, "<msg>", "</msg>");
        $error = onlinenic_GetValue($result, "<value>", "</value>");
        $error = $msg . " - " . $error;
        $values["error"] = (string) $resultcode . " - " . $errormsg . ": " . $error;
    }
    return $values;
}
function onlinenic_GetRegistrarLock($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domainname = (string) $sld . "." . $tld;
    if ($testmode) {
        $username = 135610;
        $password = md5("654123");
    }
    $queryresult = select_query("mod_onlinenic", "lockstatus", "domain='" . $domainname . "'");
    $data = mysql_fetch_array($queryresult);
    $lock = (bool) $data["lockstatus"];
    if ($lock) {
        $lockstatus = "locked";
    } else {
        $lockstatus = "unlocked";
    }
    return $lockstatus;
}
function onlinenic_SaveRegistrarLock($params)
{
    $username = $params["Username"];
    $password = md5($params["Password"]);
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    if ($params["lockenabled"] == "locked") {
        $locked = true;
    } else {
        $locked = false;
    }
    if ($testmode) {
        $username = 135610;
        $password = md5("654123");
    }
    $domain = $sld . "." . $tld;
    $values = onlinenic_Login($fp, $username, $password, $testmode);
    if ($values["error"]) {
        return $values;
    }
    $domain_type = onlinenic_getDomainType($tld, $sld);
    $clTrid = rand();
    $checksum = md5($username . $password . $clTrid . "upddomain" . $domain_type . $domain);
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n                    <epp>\n                        <command>\n                            <update>\n                            <domain:update>\n                                <domain:type>" . $domain_type . "</domain:type>\n                                <domain:name>" . $domain . "</domain:name>";
    if ($locked) {
        $xml .= "\n                             <domain:add>\n                                    <domain:status s=\"clientTransferProhibited\"/>\n                                </domain:add>";
    } else {
        $xml .= "\n                             <domain:rem>\n                                    <domain:status s=\"clientTransferProhibited\"/>\n                                </domain:rem>";
    }
    $xml .= "\n                             </domain:update>\n                            </update>\n                            <clTRID>" . $clTrid . "</clTRID>\n                            <chksum>" . $checksum . "</chksum>\n                        </command>\n                    </epp>";
    $result = onlinenic_sendCommand($fp, $xml);
    onlinenic_Logout($fp, $username, $password);
    $resultcode = onlinenic_getResultCode($result);
    if ($resultcode != "1000") {
        $errormsg = onlinenic_getResultText($resultcode);
        $msg = onlinenic_GetValue($result, "<msg>", "</msg>");
        $error = onlinenic_GetValue($result, "<value>", "</value>");
        $error = $msg . " - " . $error;
        $values["error"] = (string) $resultcode . " - " . $errormsg . ": " . $error;
    } else {
        $queryresult = select_query("mod_onlinenic", "*", "domain='" . $domain . "'");
        $check = mysql_num_rows($queryresult);
        if ($check != "0") {
            $result = update_query("mod_onlinenic", array("lockstatus" => $locked), array("domain" => $domain));
        } else {
            $result = insert_query("mod_onlinenic", array("lockstatus" => $locked, "domain" => $domain));
        }
    }
    return $values;
}
function onlinenicX_GetDNS($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    if ($testmode) {
        $username = 135610;
        $password = md5("654123");
    }
    $hostrecords = array();
    $hostrecords[] = array("hostname" => "ns1", "type" => "A", "address" => "192.168.0.1");
    $hostrecords[] = array("hostname" => "ns2", "type" => "A", "address" => "192.168.0.2");
    return $hostrecords;
}
function onlinenicX_SaveDNS($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    if ($testmode) {
        $username = 135610;
        $password = md5("654123");
    }
    foreach ($params["dnsrecords"] as $key => $values) {
        $hostname = $values["hostname"];
        $type = $values["type"];
        $address = $values["address"];
    }
    $values["error"] = $Enom->Values["Err1"];
    return $values;
}
function onlinenic_RegisterDomain($params)
{
    $username = $params["Username"];
    $password = md5($params["Password"]);
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domain = $sld . "." . $tld;
    if ($testmode) {
        $username = 135610;
        $password = md5("654123");
    }
    $values = onlinenic_Login($fp, $username, $password, $testmode);
    if ($values["error"]) {
        return $values;
    }
    $domain_type = onlinenic_getDomainType($tld, $sld);
    $year = $params["regperiod"];
    $dns1 = $params["ns1"];
    $dns2 = $params["ns2"];
    $dns3 = $params["ns3"];
    $dns4 = $params["ns4"];
    $dns5 = $params["ns5"];
    $RegistrantFirstName = $params["firstname"];
    $RegistrantLastName = $params["lastname"];
    $RegistrantCompany = $params["companyname"];
    $RegistrantAddress1 = $params["address1"];
    $RegistrantAddress2 = $params["address2"];
    $RegistrantCity = $params["city"];
    $RegistrantStateProvince = $params["state"];
    $RegistrantPostalCode = $params["postcode"];
    $RegistrantCountry = $params["country"];
    $RegistrantEmailAddress = $params["email"];
    $RegistrantPhone = $params["fullphonenumber"];
    $values = onlinenic_RegisterContact($fp, $username, $password, $domain_type, $RegistrantFirstName, $RegistrantLastName, $RegistrantCompany, $RegistrantAddress1, $RegistrantAddress2, $RegistrantCity, $RegistrantStateProvince, $RegistrantCountry, $RegistrantPostalCode, $RegistrantPhone, $RegistrantPhone, $RegistrantEmailAddress);
    if ($values["error"]) {
        return $values;
    }
    $registrant = $values["contactid"];
    $AdminFirstName = $params["adminfirstname"];
    $AdminLastName = $params["adminlastname"];
    $AdminCompany = $params["companyname"];
    $AdminAddress1 = $params["adminaddress1"];
    $AdminAddress2 = $params["adminaddress2"];
    $AdminCity = $params["admincity"];
    $AdminStateProvince = $params["adminstate"];
    $AdminPostalCode = $params["adminpostcode"];
    $AdminCountry = $params["admincountry"];
    $AdminEmailAddress = $params["adminemail"];
    $AdminPhone = $params["adminfullphonenumber"];
    $values = onlinenic_RegisterContact($fp, $username, $password, $domain_type, $AdminFirstName, $AdminLastName, $AdminCompany, $AdminAddress1, $AdminAddress2, $AdminCity, $AdminStateProvince, $AdminCountry, $AdminPostalCode, $AdminPhone, $AdminPhone, $AdminEmailAddress);
    if ($values["error"]) {
        return $values;
    }
    $admin = $values["contactid"];
    $tech = $admin;
    $billing = $admin;
    $clTrid = substr(md5($domain), 0, 10) . mt_rand(1000000000, 9999999999.0);
    $password1 = onlinenic_genpw();
    if ($tld == "eu" || $tld == "cc") {
        $checksum = md5($username . $password . $clTrid . "crtdomain" . $domain_type . $domain . $year . $dns1 . $dns2 . $registrant . $password1);
    } else {
        if ($tld == "asia") {
            $checksum = md5($username . $password . $clTrid . "crtdomain" . $domain_type . $domain . $year . $dns1 . $dns2 . $registrant . $admin . $tech . $billing . $password1);
        } else {
            if ($tld == "tv") {
                $checksum = md5($username . $password . $clTrid . "crtdomain" . $domain_type . $domain . $year . $dns1 . $dns2 . $registrant . $tech . $password1);
            } else {
                $checksum = md5($username . $password . $clTrid . "crtdomain" . $domain_type . $domain . $year . $dns1 . $dns2 . $registrant . $admin . $tech . $billing . $password1);
            }
        }
    }
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n            <epp>\n                    <command>\n                            <create>\n                                    <domain:create>\n                                            <domain:type>" . $domain_type . "</domain:type>\n                                            <domain:name>" . $domain . "</domain:name>\n                                            <domain:period>" . $year . "</domain:period>\n                                            <domain:ns1>" . $dns1 . "</domain:ns1>\n                                            <domain:ns2>" . $dns2 . "</domain:ns2>\n                                            <domain:ns3>" . $dns3 . "</domain:ns3>\n                                            <domain:ns4>" . $dns4 . "</domain:ns4>\n                                            <domain:ns5>" . $dns5 . "</domain:ns5>\n                                            <domain:registrant>" . $registrant . "</domain:registrant>\n                                            <domain:contact type=\"admin\">" . $admin . "</domain:contact>\n                                            <domain:contact type=\"tech\">" . $tech . "</domain:contact>\n                                            <domain:contact type=\"billing\">" . $billing . "</domain:contact>\n                                            <domain:authInfo type=\"pw\">" . $password1 . "</domain:authInfo>\n                                    </domain:create>\n                            </create>\n                            <clTRID>" . $clTrid . "</clTRID>\n                            <chksum>" . $checksum . "</chksum>\n                    </command>\n            </epp>";
    $result = onlinenic_sendCommand($fp, $xml);
    $resultcode = onlinenic_getResultCode($result);
    onlinenic_Logout($fp, $username, $password);
    if ($resultcode != "1000") {
        $errormsg = onlinenic_getResultText($resultcode);
        $msg = onlinenic_GetValue($result, "<msg>", "</msg>");
        $error = onlinenic_GetValue($result, "<value>", "</value>");
        $error = $msg . " - " . $error;
        $values["error"] = (string) $resultcode . " - " . $errormsg . ": " . $error;
        return $values;
    }
}
function onlinenic_FormatPhone($telephone, $country)
{
    $countries = new WHMCS\Utility\Country();
    $prefix = $countries->getCallingCode($country);
    $telephone = preg_replace("/[^0-9]/", "", $telephone);
    if ($telephone == "") {
        return "+" . $prefix . ".0000000";
    }
    $StartsWith001 = strcmp(substr($telephone, 0, 3), "001") == 0;
    $StartsWith011 = strcmp(substr($telephone, 0, 3), "011") == 0;
    $StartsWithPrefix = strcmp(substr($telephone, 0, strlen($prefix)), $prefix) == 0;
    if ($StartsWith001 || $StartsWith011) {
        $telephone = substr($telephone, 3, strlen($telephone) - 3);
    }
    if ($StartsWithPrefix) {
        $telephone = substr($telephone, strlen($prefix), strlen($telephone) - strlen($prefix));
    }
    return "+" . $prefix . "." . $telephone;
}
function onlinenic_RegisterContact($fp, $username, $password, $domain_type, $firstname, $lastname, $companyname, $address1, $address2, $city, $province, $country, $postalcode, $telephone, $fax, $email)
{
    $fullname = (string) $firstname . " " . $lastname;
    if (trim($companyname) == "") {
        $companyname = "None";
    }
    $fax = onlinenic_formatphone($fax, $country);
    $password1 = onlinenic_genpw();
    $clTrid = substr(md5($domain), 0, 10) . mt_rand(1000000000, 9999999999.0);
    $checksum = md5($username . $password . $clTrid . "crtcontact" . $fullname . $companyname . $email);
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n<epp>\n        <command>\n                <create>\n                        <contact:create>\n                                <contact:domaintype>" . $domain_type . "</contact:domaintype>\n                                <contact:ascii>\n                                        <contact:name>" . $fullname . "</contact:name>\n                                        <contact:org>" . $companyname . "</contact:org>\n                                        <contact:addr>\n                                                <contact:street1>" . $address1 . "</contact:street1>\n";
    if ($address2 != "") {
        $xml .= "<contact:street2>" . $address2 . "</contact:street2>\n";
    }
    $xml .= "                                                <contact:city>" . $city . "</contact:city>\n                                                <contact:sp>" . $province . "</contact:sp>\n                                                <contact:pc>" . $postalcode . "</contact:pc>\n                                                <contact:cc>" . $country . "</contact:cc>\n                                        </contact:addr>\n                                </contact:ascii>\n                                <contact:voice>" . $telephone . "</contact:voice>\n                                <contact:fax>" . $fax . "</contact:fax>\n                                <contact:email>" . $email . "</contact:email>\n                                <contact:pw>" . $password1 . "</contact:pw>\n                        </contact:create>\n</create>\n";
    $xml .= "               <clTRID>" . $clTrid . "</clTRID>\n                <chksum>" . $checksum . "</chksum>\n        </command>\n</epp>";
    $result = onlinenic_sendCommand($fp, $xml);
    $resultcode = onlinenic_getResultCode($result);
    if ($resultcode != "1000") {
        $errormsg = onlinenic_getResultText($resultcode);
        $msg = onlinenic_GetValue($result, "<msg>", "</msg>");
        $error = onlinenic_GetValue($result, "<value>", "</value>");
        $error = $msg . " - " . $error;
        $values["error"] = (string) $resultcode . " - " . $errormsg . ": " . $error;
    }
    $values["contactid"] = onlinenic_GetValue($result, "<contact:id>", "</contact:id>");
    return $values;
}
function onlinenic_TransferDomain($params)
{
    $username = $params["Username"];
    $password = md5($params["Password"]);
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domain = $sld . "." . $tld;
    if ($testmode) {
        $username = 135610;
        $password = md5("654123");
    }
    $values = onlinenic_Login($fp, $username, $password, $testmode);
    if ($values["error"]) {
        return $values;
    }
    $domain_type = onlinenic_getDomainType($tld, $sld);
    $password1 = onlinenic_genpw();
    $clTrid = substr(md5($domain), 0, 10) . mt_rand(1000000000, 9999999999.0);
    $checksum = md5($username . $password . $clTrid . "transferdomain" . $domain_type . $domain);
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n            <epp>\n            <command>\n            <transfer>\n            <domain:transfer>\n            <domain:name>" . $domain . "</domain:name>\n            <domain:type>" . $domain_type . "</domain:type>\n            <domain:pw>" . $password1 . "</domain:pw>\n            </domain:transfer>\n            </transfer>\n            <unspec/>\n            <clTRID>" . $clTrid . "</clTRID>\n            <chksum>" . $checksum . "</chksum>\n            </command>\n            </epp>";
    $result = onlinenic_sendCommand($fp, $xml);
    $resultcode = onlinenic_getResultCode($result);
    onlinenic_Logout($fp, $username, $password);
    if ($resultcode != "1000" && $resultcode != "1001") {
        $errormsg = onlinenic_getResultText($resultcode);
        $msg = onlinenic_GetValue($result, "<msg>", "</msg>");
        $error = onlinenic_GetValue($result, "<value>", "</value>");
        $error = $msg . " - " . $error;
        $values["error"] = (string) $resultcode . " - " . $errormsg . ": " . $error;
        return $values;
    }
}
function onlinenic_RenewDomain($params)
{
    $username = $params["Username"];
    $password = md5($params["Password"]);
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domain = $sld . "." . $tld;
    if ($testmode) {
        $username = 135610;
        $password = md5("654123");
    }
    $year = $params["regperiod"];
    $values = onlinenic_Login($fp, $username, $password, $testmode);
    if ($values["error"]) {
        return $values;
    }
    $domain_type = onlinenic_getDomainType($tld, $sld);
    $clTrid = substr(md5($domain), 0, 10) . mt_rand(1000000000, 9999999999.0);
    $checksum = md5($username . $password . $clTrid . "renewdomain" . $domain_type . $domain . $year);
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n            <epp>\n                    <command>\n                            <renew>\n                                    <domain:renew>\n                                            <domain:type>" . $domain_type . "</domain:type>\n                                            <domain:name>" . $domain . "</domain:name>\n                                            <domain:period>" . $year . "</domain:period>\n                                    </domain:renew>\n                            </renew>\n                            <clTRID>" . $clTrid . "</clTRID>\n                            <chksum>" . $checksum . "</chksum>\n                    </command>\n            </epp>";
    $result = onlinenic_sendCommand($fp, $xml);
    $resultcode = onlinenic_getResultCode($result);
    onlinenic_Logout($fp, $username, $password);
    if ($resultcode != "1000") {
        $errormsg = onlinenic_getResultText($resultcode);
        $msg = onlinenic_GetValue($result, "<msg>", "</msg>");
        $error = onlinenic_GetValue($result, "<value>", "</value>");
        $error = $msg . " - " . $error;
        $values["error"] = (string) $resultcode . " - " . $errormsg . ": " . $error;
        return $values;
    }
}
function onlinenic_GetContactDetails($params)
{
    $username = $params["Username"];
    $password = md5($params["Password"]);
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domain = $sld . "." . $tld;
    if ($testmode) {
        $username = 135610;
        $password = md5("654123");
    }
    $values = onlinenic_Login($fp, $username, $password, $testmode);
    if ($values["error"]) {
        return $values;
    }
    $domain_type = onlinenic_getDomainType($tld, $sld);
    $clTrid = substr(md5($domain), 0, 10) . mt_rand(1000000000, 9999999999.0);
    $checksum = md5($username . $password . $clTrid . "getdomaininfo");
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n        <epp>\n        <command>\n        <getdomaininfo>\n        <clID>" . $username . "</clID>\n        <domain>" . $domain . "</domain>\n        <domain:type>" . $domain_type . "</domain:type>\n        <options>\n        <version>1.0</version>\n        <lang>en</lang>\n        </options>\n        </getdomaininfo>\n        <clTRID>" . $clTrid . "</clTRID>\n        <chksum>" . $checksum . "</chksum>\n        </command>\n        </epp>";
    $result = onlinenic_sendCommand($fp, $xml);
    $resultcode = onlinenic_getResultCode($result);
    onlinenic_Logout($fp, $username, $password);
    if ($resultcode != "1000") {
        $errormsg = onlinenic_getResultText($resultcode);
        $msg = onlinenic_GetValue($result, "<msg>", "</msg>");
        $error = onlinenic_GetValue($result, "<value>", "</value>");
        $error = $msg . " - " . $error;
        $values["error"] = (string) $resultcode . " - " . $errormsg . ": " . $error;
    } else {
        $name = onlinenic_GetValue($result, "<r_name>", "</r_name>");
        $company = onlinenic_GetValue($result, "<r_org>", "</r_org>");
        $address = onlinenic_GetValue($result, "<r_addr>", "</r_addr>");
        $city = onlinenic_GetValue($result, "<r_city>", "</r_city>");
        $state = onlinenic_GetValue($result, "<r_sp>", "</r_sp>");
        $postcode = onlinenic_GetValue($result, "<r_pc>", "</r_pc>");
        $country = onlinenic_GetValue($result, "<r_cc>", "</r_cc>");
        $tel = onlinenic_GetValue($result, "<r_phone>", "</r_phone>");
        $fax = onlinenic_GetValue($result, "<r_fax>", "</r_fax>");
        $email = onlinenic_GetValue($result, "<r_email>", "</r_email>");
        $values["Registrant"]["Full Name"] = $name;
        $values["Registrant"]["Company Name"] = $company;
        $values["Registrant"]["Address"] = $address;
        $values["Registrant"]["City"] = $city;
        $values["Registrant"]["State"] = $state;
        $values["Registrant"]["Postcode"] = $postcode;
        $values["Registrant"]["Country"] = $country;
        $values["Registrant"]["Phone Number"] = $tel;
        $values["Registrant"]["Fax Number"] = $fax;
        $values["Registrant"]["Email"] = $email;
        $name = onlinenic_GetValue($result, "<a_name>", "</a_name>");
        $company = onlinenic_GetValue($result, "<a_org>", "</a_org>");
        $address = onlinenic_GetValue($result, "<a_addr>", "</a_addr>");
        $city = onlinenic_GetValue($result, "<a_city>", "</a_city>");
        $state = onlinenic_GetValue($result, "<a_sp>", "</a_sp>");
        $postcode = onlinenic_GetValue($result, "<a_pc>", "</a_pc>");
        $country = onlinenic_GetValue($result, "<a_cc>", "</a_cc>");
        $tel = onlinenic_GetValue($result, "<a_phone>", "</a_phone>");
        $fax = onlinenic_GetValue($result, "<a_fax>", "</a_fax>");
        $email = onlinenic_GetValue($result, "<a_email>", "</a_email>");
        $values["Admin"]["Full Name"] = $name;
        $values["Admin"]["Company Name"] = $company;
        $values["Admin"]["Address"] = $address;
        $values["Admin"]["City"] = $city;
        $values["Admin"]["State"] = $state;
        $values["Admin"]["Postcode"] = $postcode;
        $values["Admin"]["Country"] = $country;
        $values["Admin"]["Phone Number"] = $tel;
        $values["Admin"]["Fax Number"] = $fax;
        $values["Admin"]["Email"] = $email;
        $name = onlinenic_GetValue($result, "<t_name>", "</t_name>");
        $company = onlinenic_GetValue($result, "<t_org>", "</t_org>");
        $address = onlinenic_GetValue($result, "<t_addr>", "</t_addr>");
        $city = onlinenic_GetValue($result, "<t_city>", "</t_city>");
        $state = onlinenic_GetValue($result, "<t_sp>", "</t_sp>");
        $postcode = onlinenic_GetValue($result, "<t_pc>", "</t_pc>");
        $country = onlinenic_GetValue($result, "<t_cc>", "</t_cc>");
        $tel = onlinenic_GetValue($result, "<t_phone>", "</t_phone>");
        $fax = onlinenic_GetValue($result, "<t_fax>", "</t_fax>");
        $email = onlinenic_GetValue($result, "<t_email>", "</t_email>");
        $values["Tech"]["Full Name"] = $name;
        $values["Tech"]["Company Name"] = $company;
        $values["Tech"]["Address"] = $address;
        $values["Tech"]["City"] = $city;
        $values["Tech"]["State"] = $state;
        $values["Tech"]["Postcode"] = $postcode;
        $values["Tech"]["Country"] = $country;
        $values["Tech"]["Phone Number"] = $tel;
        $values["Tech"]["Fax Number"] = $fax;
        $values["Tech"]["Email"] = $email;
    }
    return $values;
}
function onlinenic_SaveContactDetails($params)
{
    $username = $params["Username"];
    $password = md5($params["Password"]);
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domain = $sld . "." . $tld;
    if ($testmode) {
        $username = 135610;
        $password = md5("654123");
    }
    $values = onlinenic_Login($fp, $username, $password, $testmode);
    if ($values["error"]) {
        return $values;
    }
    $domain_type = onlinenic_getDomainType($tld, $sld);
    $clTrid = substr(md5($domain), 0, 10) . mt_rand(1000000000, 9999999999.0);
    $checksum = md5($username . $password . $clTrid . "getdomaininfo");
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n    <epp>\n        <command>\n        <getdomaininfo>\n        <clID>" . $username . "</clID>\n        <domain>" . $domain . "</domain>\n        <domain:type>" . $domain_type . "</domain:type>\n        <options>\n        <version>1.0</version>\n        <lang>en</lang>\n        </options>\n        </getdomaininfo>\n        <clTRID>" . $clTrid . "</clTRID>\n        <chksum>" . $checksum . "</chksum>\n        </command>\n    </epp>";
    $result = onlinenic_sendCommand($fp, $xml);
    $resultcode = onlinenic_getResultCode($result);
    if ($resultcode != "1000") {
        $errormsg = onlinenic_getResultText($resultcode);
        $msg = onlinenic_GetValue($result, "<msg>", "</msg>");
        $error = onlinenic_GetValue($result, "<value>", "</value>");
        $error = $msg . " - " . $error;
        $values["error"] = (string) $resultcode . " - " . $errormsg . ": " . $error;
    } else {
        $password1 = onlinenic_GetValue($result, "<pwd>", "</pwd>");
    }
    $contact_type = "4";
    $name = "";
    $company = $params["contactdetails"]["Registrant"]["Company Name"];
    $address = $params["contactdetails"]["Registrant"]["Address"];
    $city = $params["contactdetails"]["Registrant"]["City"];
    $state = $params["contactdetails"]["Registrant"]["State"];
    $postcode = $params["contactdetails"]["Registrant"]["Postcode"];
    $country = $params["contactdetails"]["Registrant"]["Country"];
    $tel = $params["contactdetails"]["Registrant"]["Phone Number"];
    $fax = $params["contactdetails"]["Registrant"]["Fax Number"];
    $email = $params["contactdetails"]["Registrant"]["Email"];
    $password1 = onlinenic_genpw();
    $clTrid = substr(md5($domain), 0, 10) . mt_rand(1000000000, 9999999999.0);
    $checksum = md5($username . $password . $clTrid . "updcontact" . $domain_type . $domain . $contact_type . $name . $company . $email);
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n    <epp>\n        <command>\n        <update>\n            <contact:update>\n                <contact:domaintype>" . $domain_type . "</contact:domaintype>\n                <contact:domain>" . $domain . "</contact:domain>\n                <contact:contacttype>" . $contact_type . "</contact:contacttype>\n                <contact:ascii>\n                <contact:name>" . $name . "</contact:name>\n                <contact:org>" . $company . "</contact:org>\n                <contact:addr>\n                <contact:street1>" . $address . "</contact:street1>\n                <contact:city>" . $city . "</contact:city>\n                <contact:sp>" . $state . "</contact:sp>\n                <contact:pc>" . $postcode . "</contact:pc>\n                <contact:cc>" . $country . "</contact:cc>\n                </contact:addr>\n                </contact:ascii>\n                <contact:voice>" . $tel . "</contact:voice>\n                <contact:fax>" . $fax . "</contact:fax>\n                <contact:email>" . $email . "</contact:email>\n                <contact:pw>" . $password1 . "</contact:pw>\n            </contact:update>\n        </update>\n        <clTRID>" . $clTrid . "</clTRID>\n        <chksum>" . $checksum . "</chksum>\n        </command>\n    </epp>";
    $result = onlinenic_sendCommand($fp, $xml);
    $resultcode = onlinenic_getResultCode($result);
    if ($resultcode != "1000") {
        $errormsg = onlinenic_getResultText($resultcode);
        $msg = onlinenic_GetValue($result, "<msg>", "</msg>");
        $error = onlinenic_GetValue($result, "<value>", "</value>");
        $error = $msg . " - " . $error;
        $values["error"] = (string) $resultcode . " - " . $errormsg . ": " . $error;
        return $values;
    }
    $contact_type = "1";
    $name = $params["contactdetails"]["Admin"]["Full Name"];
    $company = $params["contactdetails"]["Admin"]["Company Name"];
    $address = $params["contactdetails"]["Admin"]["Address"];
    $city = $params["contactdetails"]["Admin"]["City"];
    $state = $params["contactdetails"]["Admin"]["State"];
    $postcode = $params["contactdetails"]["Admin"]["Postcode"];
    $country = $params["contactdetails"]["Admin"]["Country"];
    $tel = $params["contactdetails"]["Admin"]["Phone Number"];
    $fax = $params["contactdetails"]["Admin"]["Fax Number"];
    $email = $params["contactdetails"]["Admin"]["Email"];
    $clTrid = substr(md5($domain), 0, 10) . mt_rand(1000000000, 9999999999.0);
    $checksum = md5($username . $password . $clTrid . "updcontact" . $domain_type . $domain . $contact_type . $name . $company . $email);
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n    <epp>\n        <command>\n        <update>\n            <contact:update>\n                <contact:domaintype>" . $domain_type . "</contact:domaintype>\n                <contact:domain>" . $domain . "</contact:domain>\n                <contact:contacttype>" . $contact_type . "</contact:contacttype>\n                <contact:ascii>\n                <contact:name>" . $name . "</contact:name>\n                <contact:org>" . $company . "</contact:org>\n                <contact:addr>\n                <contact:street1>" . $address . "</contact:street1>\n                <contact:city>" . $city . "</contact:city>\n                <contact:sp>" . $state . "</contact:sp>\n                <contact:pc>" . $postcode . "</contact:pc>\n                <contact:cc>" . $country . "</contact:cc>\n                </contact:addr>\n                </contact:ascii>\n                <contact:voice>" . $tel . "</contact:voice>\n                <contact:fax>" . $fax . "</contact:fax>\n                <contact:email>" . $email . "</contact:email>\n                <contact:pw>" . $password1 . "</contact:pw>\n            </contact:update>\n        </update>\n        <clTRID>" . $clTrid . "</clTRID>\n        <chksum>" . $checksum . "</chksum>\n        </command>\n    </epp>";
    $result = onlinenic_sendCommand($fp, $xml);
    $resultcode = onlinenic_getResultCode($result);
    if ($resultcode != "1000") {
        $errormsg = onlinenic_getResultText($resultcode);
        $msg = onlinenic_GetValue($result, "<msg>", "</msg>");
        $error = onlinenic_GetValue($result, "<value>", "</value>");
        $error = $msg . " - " . $error;
        $values["error"] = (string) $resultcode . " - " . $errormsg . ": " . $error;
        return $values;
    }
    $contact_type = "2";
    $name = $params["contactdetails"]["Tech"]["Full Name"];
    $company = $params["contactdetails"]["Tech"]["Company Name"];
    $address = $params["contactdetails"]["Tech"]["Address"];
    $city = $params["contactdetails"]["Tech"]["City"];
    $state = $params["contactdetails"]["Tech"]["State"];
    $postcode = $params["contactdetails"]["Tech"]["Postcode"];
    $country = $params["contactdetails"]["Tech"]["Country"];
    $tel = $params["contactdetails"]["Tech"]["Phone Number"];
    $fax = $params["contactdetails"]["Tech"]["Fax Number"];
    $email = $params["contactdetails"]["Tech"]["Email"];
    $clTrid = substr(md5($domain), 0, 10) . mt_rand(1000000000, 9999999999.0);
    $checksum = md5($username . $password . $clTrid . "updcontact" . $domain_type . $domain . $contact_type . $name . $company . $email);
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n    <epp>\n        <command>\n        <update>\n            <contact:update>\n                <contact:domaintype>" . $domain_type . "</contact:domaintype>\n                <contact:domain>" . $domain . "</contact:domain>\n                <contact:contacttype>" . $contact_type . "</contact:contacttype>\n                <contact:ascii>\n                <contact:name>" . $name . "</contact:name>\n                <contact:org>" . $company . "</contact:org>\n                <contact:addr>\n                <contact:street1>" . $address . "</contact:street1>\n                <contact:city>" . $city . "</contact:city>\n                <contact:sp>" . $state . "</contact:sp>\n                <contact:pc>" . $postcode . "</contact:pc>\n                <contact:cc>" . $country . "</contact:cc>\n                </contact:addr>\n                </contact:ascii>\n                <contact:voice>" . $tel . "</contact:voice>\n                <contact:fax>" . $fax . "</contact:fax>\n                <contact:email>" . $email . "</contact:email>\n                <contact:pw>" . $password1 . "</contact:pw>\n            </contact:update>\n        </update>\n        <clTRID>" . $clTrid . "</clTRID>\n        <chksum>" . $checksum . "</chksum>\n        </command>\n    </epp>";
    $result = onlinenic_sendCommand($fp, $xml);
    $resultcode = onlinenic_getResultCode($result);
    onlinenic_Logout($fp, $username, $password);
    if ($resultcode != "1000") {
        $errormsg = onlinenic_getResultText($resultcode);
        $msg = onlinenic_GetValue($result, "<msg>", "</msg>");
        $error = onlinenic_GetValue($result, "<value>", "</value>");
        $error = $msg . " - " . $error;
        $values["error"] = (string) $resultcode . " - " . $errormsg . ": " . $error;
        return $values;
    }
    return $values;
}
function onlinenic_RegisterNameserver($params)
{
    $username = $params["Username"];
    $password = md5($params["Password"]);
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $nameserver = $params["nameserver"];
    $ipaddress = $params["ipaddress"];
    if ($testmode) {
        $username = 135610;
        $password = md5("654123");
    }
    $clTrid = substr(md5($domain), 0, 10) . mt_rand(1000000000, 9999999999.0);
    $domain = $sld . "." . $tld;
    $domain_type = onlinenic_getDomainType($tld, $sld);
    $checksum = md5($username . $password . $clTrid . "crthost" . $domain_type . $nameserver . $ipaddress);
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n            <epp xmlns=\"urn:iana:xml:ns:epp-1.0\"\n            xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n            xsi:schemaLocation=\"urn:iana:xml:ns:epp-1.0 epp-1.0.xsd\">\n                <command>\n                <create>\n                    <host:create xmlns:host=\"urn:iana:xml:ns:host-1.0\"\n                    xsi:schemaLocation=\"urn:iana:xml:ns:host-1.0 host-1.0.xsd\">\n                        <host:domaintype>" . $domain_type . "</host:domaintype>\n                        <host:name>" . $nameserver . "</host:name>\n                        <host:addr ip=\"v4\">" . $ipaddress . "</host:addr>\n                    </host:create>\n                    </create>\n                <unspec/>\n                <clTRID>" . $clTrid . "</clTRID>\n                <chksum>" . $checksum . "</chksum>\n                </command>\n            </epp>";
    $values = onlinenic_Login($fp, $username, $password, $testmode);
    if ($values["error"]) {
        return $values;
    }
    $result = onlinenic_sendCommand($fp, $xml);
    $resultcode = onlinenic_getResultCode($result);
    onlinenic_Logout($fp, $username, $password);
    if ($resultcode != "1000") {
        $errormsg = onlinenic_getResultText($resultcode);
        $msg = onlinenic_GetValue($result, "<msg>", "</msg>");
        $error = onlinenic_GetValue($result, "<value>", "</value>");
        $error = $msg . " - " . $error;
        $values["error"] = (string) $resultcode . " - " . $errormsg . ": " . $error;
    }
    return $values;
}
function onlinenic_ModifyNameserver($params)
{
    $username = $params["Username"];
    $password = md5($params["Password"]);
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $nameserver = $params["nameserver"];
    $currentipaddress = $params["currentipaddress"];
    $newipaddress = $params["newipaddress"];
    if ($testmode) {
        $username = 135610;
        $password = md5("654123");
    }
    $clTrid = substr(md5($domain), 0, 10) . mt_rand(1000000000, 9999999999.0);
    $domain = $sld . "." . $tld;
    $domain_type = onlinenic_getDomainType($tld, $sld);
    $checksum = md5($username . $password . $clTrid . "updhost" . $domain_type . $nameserver . $newipaddress . $currentipaddress);
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n            <epp xmlns=\"urn:iana:xml:ns:epp-1.0\"\n            xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n            xsi:schemaLocation=\"urn:iana:xml:ns:epp-1.0 epp-1.0.xsd\">\n                <command>\n                <update>\n                    <host:update xmlns:host=\"urn:iana:xml:ns:host-1.0\"\n                    xsi:schemaLocation=\"urn:iana:xml:ns:host-1.0 host-1.0.xsd\">\n                        <host:domaintype>" . $domain_type . "</host:domaintype>\n                        <host:name>" . $nameserver . "</host:name>\n                        <host:add>\n                            <host:addr ip=\"v4\">" . $newipaddress . "</host:addr>\n                            </host:add>\n                            <host:rem>\n                            <host:addr ip=\"v4\">" . $currentipaddress . "</host:addr>\n                            </host:rem>\n                    </host:update>\n                    </update>\n                <unspec/>\n                <clTRID>" . $clTrid . "</clTRID>\n                <chksum>" . $checksum . "</chksum>\n                </command>\n            </epp>";
    $values = onlinenic_Login($fp, $username, $password, $testmode);
    if ($values["error"]) {
        return $values;
    }
    $result = onlinenic_sendCommand($fp, $xml);
    $resultcode = onlinenic_getResultCode($result);
    onlinenic_Logout($fp, $username, $password);
    if ($resultcode != "1000") {
        $errormsg = onlinenic_getResultText($resultcode);
        $msg = onlinenic_GetValue($result, "<msg>", "</msg>");
        $error = onlinenic_GetValue($result, "<value>", "</value>");
        $error = $msg . " - " . $error;
        $values["error"] = (string) $resultcode . " - " . $errormsg . ": " . $error;
    }
    return $values;
}
function onlinenic_DeleteNameserver($params)
{
    $username = $params["Username"];
    $password = md5($params["Password"]);
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $nameserver = $params["nameserver"];
    if ($testmode) {
        $username = 135610;
        $password = md5("654123");
    }
    $clTrid = substr(md5($domain), 0, 10) . mt_rand(1000000000, 9999999999.0);
    $domain = $sld . "." . $tld;
    $domain_type = onlinenic_getDomainType($tld, $sld);
    $checksum = md5($username . $password . $clTrid . "delhost" . $domain_type . $nameserver . $ipaddress);
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n            <epp xmlns=\"urn:iana:xml:ns:epp-1.0\"\n            xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n            xsi:schemaLocation=\"urn:iana:xml:ns:epp-1.0 epp-1.0.xsd\">\n                <command>\n                    <delete>\n                    <host:delete xmlns:host=\"urn:iana:xml:ns:host-1.0\"\n                    xsi:schemaLocation=\"urn:iana:xml:ns:host-1.0 host-1.0.xsd\">\n                        <host:domaintype>" . $domain_type . "</host:domaintype>\n                        <host:name>" . $nameserver . "</host:name>\n                    </host:delete>\n                    </delete>\n                <unspec/>\n                <clTRID>" . $clTrid . "</clTRID>\n                <chksum>" . $checksum . "</chksum>\n                </command>\n            </epp>";
    $values = onlinenic_Login($fp, $username, $password, $testmode);
    if ($values["error"]) {
        return $values;
    }
    $result = onlinenic_sendCommand($fp, $xml);
    $resultcode = onlinenic_getResultCode($result);
    onlinenic_Logout($fp, $username, $password);
    if ($resultcode != "1000") {
        $errormsg = onlinenic_getResultText($resultcode);
        $msg = onlinenic_GetValue($result, "<msg>", "</msg>");
        $error = onlinenic_GetValue($result, "<value>", "</value>");
        $error = $msg . " - " . $error;
        $values["error"] = (string) $resultcode . " - " . $errormsg . ": " . $error;
    }
    return $values;
}
function onlinenic_GetExpirationDate($fp, $username, $password, $domainname, $domainext)
{
    $domain = $domainname . "." . $domainext;
    $domain_type = onlinenic_getDomainType($domainext, $domainname);
    $clTrid = rand();
    $checksum = md5($username . $password . $clTrid . "getdomaininfo");
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n            <epp>\n                <command>\n                    <getdomaininfo>\n                        <clID>" . $username . "</clID>\n                        <domain>" . $domain . "</domain>\n                        <domain:type>" . $domain_type . "</domain:type>\n                        <options>\n                            <version>1.0</version>\n                            <lang>en</lang>\n                        </options>\n                    </getdomaininfo>\n                    <clTRID>" . $clTrid . "</clTRID>\n                    <chksum>" . $checksum . "</chksum>\n                </command>\n            </epp>";
    $result = onlinenic_sendCommand($fp, $xml);
    $resultcode = onlinenic_getResultCode($result);
    if ($resultcode != "1000") {
        $errormsg = onlinenic_getResultText($resultcode);
        $msg = onlinenic_GetValue($result, "<msg>", "</msg>");
        $error = onlinenic_GetValue($result, "<value>", "</value>");
        $error = $msg . " - " . $error;
        $values["error"] = (string) $resultcode . " - " . $errormsg . ": " . $error;
    } else {
        $values["expirydate"] = onlinenic_GetValue($result, "<expdate>", "</expdate>");
    }
    return $values;
}
function onlinenic_Logout($fp, $username, $password)
{
    $clTrid = substr(md5($domain), 0, 10) . mt_rand(1000000000, 9999999999.0);
    $checksum = md5($username . $password . $clTrid . "logout");
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n        <epp>\n        <command>\n        <logout/>\n        <unspec/>\n        <clTRID>" . $clTrid . "</clTRID>\n        <chksum>" . $checksum . "</chksum>\n        </command>\n        </epp>";
    $result = onlinenic_sendCommand($fp, $xml);
    $resultcode = onlinenic_getResultCode($result);
    if ($resultcode != "1500") {
        $errormsg = onlinenic_getResultText($resultcode);
        $msg = onlinenic_GetValue($result, "<msg>", "</msg>");
        $error = onlinenic_GetValue($result, "<value>", "</value>");
        $error = $msg . " - " . $error;
        $values["error"] = (string) $resultcode . " - " . $errormsg . ": " . $error;
    }
    return $values;
}
function onlinenic_GetValue($msg, $str1, $str2)
{
    $start_pos = strpos($msg, $str1);
    $stop_post = strpos($msg, $str2);
    $start_pos += strlen($str1);
    return substr($msg, $start_pos, $stop_post - $start_pos);
}
function onlinenic_getResultCode($result)
{
    $start_pos = strpos($result, "<result code=\"");
    return substr($result, $start_pos + 14, 4);
}
function onlinenic_Login(&$fp, $username, $password, $testmode)
{
    $server = "www.onlinenic.com";
    $port = 20001;
    if ($testmode) {
        $server = "218.5.81.149";
    }
    if (!($fp = fsockopen($server, $port, $errno, $errstr, 90))) {
        $values["error"] = "Connection Failed - " . $errno . " - " . $errstr;
        return $values;
    }
    $i = 0;
    while (!feof($fp)) {
        $i++;
        $line = fgets($fp, 2);
        $result .= $line;
        if (preg_match("|</epp>\$|i", $result)) {
            break;
        }
        if (5000 < $i) {
            break;
        }
    }
    if (preg_match("|</greeting></epp>\$|i", $result)) {
        $clTrid = substr(md5($domain), 0, 10) . mt_rand(1000000000, 9999999999.0);
        $checksum = md5($username . $password . $clTrid . "login");
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n<epp>\n        <command>\n                <creds>\n                        <clID>" . $username . "</clID>\n                        <options>\n                                <version>1.0</version>\n                                <lang>en</lang>\n                        </options>\n                </creds>\n                <clTRID>" . $clTrid . "</clTRID>\n                <login>\n                        <chksum>" . $checksum . "</chksum>\n                </login>\n        </command>\n</epp>";
        $result = onlinenic_sendCommand($fp, $xml, $username, $password);
        $resultcode = onlinenic_getresultcode($result);
        if ($resultcode != "1000") {
            $errormsg = onlinenic_getResultText($resultcode);
            $msg = onlinenic_getvalue($result, "<msg>", "</msg>");
            $error = onlinenic_getvalue($result, "<value>", "</value>");
            $error = $msg . " - " . $error;
            $values["error"] = (string) $resultcode . " - " . $errormsg . ": " . $error;
        }
        return $values;
    }
    $values["error"] = "An Error Occurred with Connection";
    return $values;
}
function onlinenic_getDomainType($tld, $sld)
{
    $tld = strtolower(trim($tld, " ."));
    if ($tld == "cc") {
        $idn = new WHMCS\Domains\Idna();
        if ($idn->encode($sld) == $sld) {
            return 600;
        }
        return 610;
    }
    $mapping = array("com" => "0", "net" => "0", "org" => "807", "cn" => "220", "biz" => "800", "info" => "805", "us" => "806", "in" => "808", "mobi" => "903", "eu" => "902", "asia" => "905", "me" => "906", "name" => "804", "tel" => "907", "tv" => "400", "tw" => "302", "uk" => "901", "co" => "908", "xxx" => "930", "pw" => "940", "bar" => "942", "host" => "943", "ink" => "944", "press" => "945", "rest" => "946", "website" => "947", "xyz" => "948", "wiki" => "949", "club" => "740", "bike" => "2001", "clothing" => "2002", "guru" => "2003", "holdings" => "2004", "plumbing" => "2005", "singles" => "2006", "ventures" => "2007", "camera" => "2008", "equipment" => "2009", "estate" => "2010", "gallery" => "2011", "graphics" => "2012", "lighting" => "2013", "photography" => "2014", "construction" => "2015", "contractors" => "2016", "directory" => "2017", "kitchen" => "2018", "land" => "2019", "technology" => "2020", "today" => "2021", "diamonds" => "2022", "enterprises" => "2023", "tips" => "2024", "voyage" => "2025", "careers" => "2026", "photos" => "2027", "recipes" => "2028", "shoes" => "2029", "cab" => "2030", "company" => "2031", "domains" => "2032", "limo" => "2033", "academy" => "2034", "center" => "2035", "computer" => "2036", "management" => "2037", "systems" => "2038", "builders" => "2039", "email" => "2040", "solutions" => "2041", "support" => "2042", "training" => "2043", "camp" => "2044", "education" => "2045", "glass" => "2046", "institute" => "2047", "repair" => "2048", "coffee" => "2049", "florist" => "2050", "house" => "2051", "international" => "2052", "solar" => "2053", "marketing" => "2054", "viajes" => "2055", "farm" => "2056", "codes" => "2057", "cheap" => "2058", "zone" => "2059", "agency" => "2060", "bargains" => "2061", "boutique" => "2062", "cool" => "2063", "watch" => "2064", "works" => "2065", "expert" => "2066", "foundation" => "2067", "exposed" => "2068", "villas" => "2069", "flights" => "2070", "rentals" => "2071", "cruises" => "2072", "vacations" => "2073", "condos" => "2074", "properties" => "2075", "maison" => "2076", "tienda" => "2077", "dating" => "2078", "events" => "2079", "partners" => "2080", "productions" => "2081", "community" => "2082", "catering" => "2083", "cards" => "2084", "cleaning" => "2085", "tools" => "2086", "industries" => "2087", "parts" => "2088", "supplies" => "2089", "supply" => "2090", "report" => "2091", "vision" => "2092", "fish" => "2093", "services" => "2094", "capital" => "2095", "engineering" => "2096", "exchange" => "2097", "gripe" => "2098", "associates" => "2099", "lease" => "2100", "media" => "2101", "pictures" => "2102", "reisen" => "2103", "toys" => "2104", "university" => "2105", "town" => "2106", "wtf" => "2107", "fail" => "2108", "financial" => "2109", "limited" => "2110", "care" => "2111", "clinic" => "2112", "surgery" => "2113", "dental" => "2114", "tax" => "2115", "cash" => "2116", "fund" => "2117", "investments" => "2118", "furniture" => "2119", "discount" => "2120", "fitness" => "2121", "schule" => "2122", "sexy" => "2500", "tattoo" => "2501", "link" => "2502", "guitars" => "2503", "gift" => "2504", "pics" => "2505", "photo" => "2506", "christmas" => "2507", "blackfriday" => "2508", "hiphop" => "2509", "juegos" => "2510", "audio" => "2511", "click" => "2512", "hosting" => "2513", "property" => "2514", "top" => "770");
    if (isset($mapping[$tld])) {
        return $mapping[$tld];
    }
    return "0";
}
function onlinenic_getResultText($resultCode)
{
    switch ($resultCode) {
        case "1000":
            return "Command completed successfully";
        case "1300":
            return "Command completed successfully; no messages";
        case "1500":
            return "Command completed successfully; ending session";
        case "1700":
            return "Command completed successfully; not in lib";
        case "2001":
            return "Command syntax error";
        case "2002":
            return "Command use error";
        case "2003":
            return "Required Parameter missing";
        case "2004":
            return "Parameter value range err";
        case "2005":
            return "Parameter value syntax error";
        case "2104":
            return "Billing fail; Not enough funds ?";
        case "2201":
            return "Authorization error";
        case "2302":
            return "Domain is currently with OnlineNIC";
        case "2303":
            return "Object does not exist";
        case "2304":
            return "Object status prohibits operation";
        case "2305":
            return "Object association prohibits operation";
        case "2306":
            return "Parameter value policy error";
        case "2400":
            return "Command fail";
        case "2500":
            return "Command failed;server ending session";
        case "2501":
            return "Timeout;server ending session";
        case "5000":
            return "Something error in netware";
        case "5500":
            return "Did not login";
        case "6000":
            return "Checksum error";
    }
    return "No response from OnlineNIC";
}
function onlinenic_sendCommand($fp, $command, $username = "", $password = "")
{
    fputs($fp, $command);
    $i = 0;
    while (!feof($fp)) {
        $i++;
        $line = fgets($fp, 2);
        $result .= $line;
        if (preg_match("|</epp>\$|i", $result)) {
            break;
        }
        if (5000 < $i) {
            break;
        }
    }
    $xmlinput = XMLtoArray($command);
    $xmlinput = array_keys($xmlinput["EPP"]["COMMAND"]);
    $xmlinput = $xmlinput[2];
    logModuleCall("onlinenic", $xmlinput, $command, $result, "", array($username, $password));
    return $result;
}
function onlinenic_genpw()
{
    $pw = "";
    $length = 3;
    $seeds = "0123456789";
    $seeds_count = strlen($seeds) - 1;
    for ($i = 0; $i < $length; $i++) {
        $pw .= $seeds[rand(0, $seeds_count)];
    }
    $seeds = "abcdefghijklmnopqrstuvwxyz";
    $seeds_count = strlen($seeds) - 1;
    for ($i = 0; $i < $length; $i++) {
        $pw .= $seeds[rand(0, $seeds_count)];
    }
    $seeds = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    $seeds_count = strlen($seeds) - 1;
    for ($i = 0; $i < $length; $i++) {
        $pw .= $seeds[rand(0, $seeds_count)];
    }
    $seeds = "!#\$%()*+,-./=?@[]^";
    $seeds_count = strlen($seeds) - 1;
    for ($i = 0; $i < $length; $i++) {
        $pw .= $seeds[rand(0, $seeds_count)];
    }
    return $pw;
}
function onlinenic_Sync($params)
{
    $username = $params["Username"];
    $password = md5($params["Password"]);
    $testmode = $params["TestMode"];
    if ($testmode) {
        $username = 135610;
        $password = md5("654123");
    }
    $values = onlinenic_login($fp, $username, $password, $testmode);
    if ($values["error"]) {
        return $values;
    }
    $values = onlinenic_getexpirationdate($fp, $username, $password, $params["sld"], $params["tld"]);
    if ($values["error"]) {
        return $values;
    }
    $expirydate = strtotime($values["expirydate"]);
    $expirydate = date("Y-m-d", $expirydate);
    return array("active" => true, "expirydate" => $expirydate);
}

?>