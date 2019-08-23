<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function affordabledomains_splitPhoneNumber($phoneNumber)
{
    $phoneNumber = trim($phoneNumber);
    if (preg_match("/^([0-9]+)\\s+([0-9]+)\\s+([0-9]+)\$/", $phoneNumber, $matches)) {
        return array($matches[1], $matches[2], $matches[3]);
    }
    if (preg_match("/^([0-9]+)\\s+([0-9]+)\$/", $phoneNumber, $matches)) {
        return array(64, $matches[1], $matches[2]);
    }
    if (preg_match("/^(03)([0-9]+)/", $phoneNumber, $matches)) {
        return array(64, $matches[1], $matches[2]);
    }
    if (preg_match("/^(04)([0-9]+)/", $phoneNumber, $matches)) {
        return array(64, $matches[1], $matches[2]);
    }
    if (preg_match("/^(06)([0-9]+)/", $phoneNumber, $matches)) {
        return array(64, $matches[1], $matches[2]);
    }
    if (preg_match("/^(07)([0-9]+)/", $phoneNumber, $matches)) {
        return array(64, $matches[1], $matches[2]);
    }
    if (preg_match("/^(09)([0-9]+)/", $phoneNumber, $matches)) {
        return array(64, $matches[1], $matches[2]);
    }
    if (preg_match("/^(021)([0-9]+)/", $phoneNumber, $matches)) {
        return array(64, $matches[1], $matches[2]);
    }
    if (preg_match("/^(027)([0-9]+)/", $phoneNumber, $matches)) {
        return array(64, $matches[1], $matches[2]);
    }
    if (preg_match("/^(029)([0-9]+)/", $phoneNumber, $matches)) {
        return array(64, $matches[1], $matches[2]);
    }
    if (preg_match("/^\\+([0-9]+)\\.([0-9])([0-9]+)/", $phoneNumber, $matches)) {
        return array($matches[1], $matches[2], $matches[3]);
    }
    return array("", "", $phoneNumber);
}
function affordabledomains_getConfigArray()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "Affordable Domains"), "Username" => array("Type" => "text", "Size" => "20", "Description" => "Enter your AffordableDomains.co.nz Username here"), "Password" => array("Type" => "password", "Size" => "20", "Description" => "Enter your Account Password here"), "TestMode" => array("Type" => "yesno"));
    return $configarray;
}
function affordabledomains_GetNameservers($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $loginStatus = affordabledomains_login($params);
    if ($loginStatus == "success") {
        $whoisReturn = affordabledomains_whoisNzDomain($params);
        $values["ns1"] = $whoisReturn["ServerFQDN1"];
        $values["ns2"] = $whoisReturn["ServerFQDN2"];
        $values["ns3"] = $whoisReturn["ServerFQDN3"];
        $values["ns4"] = $whoisReturn["ServerFQDN4"];
        $values["ns5"] = $whoisReturn["ServerFQDN5"];
    } else {
        $values["error"] = $loginStatus;
    }
    return $values;
}
function affordabledomains_SaveNameservers($params)
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
    $loginStatus = affordabledomains_login($params);
    if ($loginStatus == "success") {
        $nsSaveReturn = affordabledomains_nsSaveNzDomain($params);
        $nsSaveReturnArr = explode("|", $nsSaveReturn);
        foreach ($nsSaveReturnArr as $nsSaveReturnVal) {
            if ($nsSaveReturnVal != "") {
                $nsSaveStatus = explode(":", $nsSaveReturnVal);
                if (trim($nsSaveStatus[0]) != "success") {
                    $values["error"] = $nsSaveStatus[1];
                }
            }
        }
    } else {
        $values["error"] = $loginStatus;
    }
    return $values;
}
function affordabledomains_RegisterDomain($params)
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
    $loginStatus = affordabledomains_login($params);
    if ($loginStatus == "success") {
        $registrantPhAr = affordabledomains_splitphonenumber($RegistrantPhone);
        $adminPhAr = affordabledomains_splitphonenumber($AdminPhone);
        if ($registrantPhAr[0] == "") {
            $values["error"] = "Registrant phone number is in incorrect format.  Please edit the phone number in your profile to be in \"country areacode localnumber\" format (eg \"64 9 1234567\")";
        } else {
            if ($adminPhAr[0] == "") {
                $values["error"] = "Admin phone number is in incorrect format.  Please edit the phone number in your profile to be in \"country areacode localnumber\" format (eg \"64 9 1234567\")";
            } else {
                list($params["phonecountrycode"], $params["phoneareacode"], $params["phonelocalcode"]) = $registrantPhAr;
                list($params["adminphonecountrycode"], $params["adminphoneareacode"], $params["adminphonelocalcode"]) = $adminPhAr;
                $regReturn = affordabledomains_registerNzDomain($params);
                $regReturnArr = explode("|", $regReturn);
                foreach ($regReturnArr as $regReturnVal) {
                    if ($regReturnVal != "") {
                        $regStatus = explode(":", $regReturnVal);
                        if (trim($regStatus[0]) != "success") {
                            $values["error"] = $regStatus[1];
                        }
                    }
                }
            }
        }
    } else {
        $values["error"] = $loginStatus;
    }
    return $values;
}
function affordabledomains_TransferDomain($params)
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
    $loginStatus = affordabledomains_login($params);
    if ($loginStatus == "success") {
        $registrantPhAr = affordabledomains_splitphonenumber($RegistrantPhone);
        if ($registrantPhAr[0] == "") {
            $values["error"] = "Registrant phone number is in incorrect format.  Please edit the phone number in your profile to be in \"country areacode localnumber\" format (eg \"64 9 1234567\")";
        } else {
            list($params["phonecountrycode"], $params["phoneareacode"], $params["phonelocalcode"]) = $registrantPhAr;
        }
        $transReturn = affordabledomains_transNzDomain($params);
        $transReturnArr = explode("|", $transReturn);
        foreach ($transReturnArr as $transReturnVal) {
            if ($transReturnVal != "") {
                $transStatus = explode(":", $transReturnVal);
                if (trim($transStatus[0]) != "success") {
                    $values["error"] = $transStatus[1];
                }
            }
        }
    } else {
        $values["error"] = $loginStatus;
    }
    return $values;
}
function affordabledomains_RenewDomain($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $regperiod = $params["regperiod"];
    $loginStatus = affordabledomains_login($params);
    if ($loginStatus == "success") {
        $renewReturn = affordabledomains_renewNzDomain($params);
        $renewReturnArr = explode("|", $renewReturn);
        foreach ($renewReturnArr as $renewReturnVal) {
            if ($renewReturnVal != "") {
                $renewStatus = explode(":", $renewReturnVal);
                if (trim($renewStatus[0]) != "success") {
                    $values["error"] = $renewStatus[1];
                }
            }
        }
    } else {
        $values["error"] = $loginStatus;
    }
    return $values;
}
function affordabledomains_GetContactDetails($params)
{
    $username = $params["Username"];
    $password = $params["Password"];
    $testmode = $params["TestMode"];
    $tld = $params["tld"];
    $sld = $params["sld"];
    $loginStatus = affordabledomains_login($params);
    if ($loginStatus == "success") {
        $whoisReturn = affordabledomains_whoisNzDomain($params);
        if ($whoisReturn["RegistrantName"] != "") {
            list($firstname, $lastname) = explode(" ", $whoisReturn["RegistrantName"], 2);
            $values["Registrant"]["First Name"] = $firstname;
            $values["Registrant"]["Last Name"] = $lastname;
            $values["Registrant"]["Email"] = $whoisReturn["RegistrantEmail"];
            $values["Registrant"]["Address1"] = $whoisReturn["RegistrantAddress1"];
            $values["Registrant"]["Address2"] = $whoisReturn["RegistrantAddress2"];
            $values["Registrant"]["City"] = $whoisReturn["RegistrantCity"];
            $values["Registrant"]["Province"] = $whoisReturn["RegistrantProvince"];
            $values["Registrant"]["PostalCode"] = $whoisReturn["RegistrantPostalCode"];
            $regphoneno = "";
            $regfaxno = "";
            if ($whoisReturn["RegistrantPhoneLocalNumber"] != "") {
                $regphoneno = $whoisReturn["RegistrantPhoneCountryCode"] . " " . $whoisReturn["RegistrantPhoneAreaCode"] . " " . $whoisReturn["RegistrantPhoneLocalNumber"];
            }
            $values["Registrant"]["Phone"] = $regphoneno;
            if ($whoisReturn["RegistrantFaxLocalNumber"] != "") {
                $regfaxno = $whoisReturn["RegistrantFaxCountryCode"] . " " . $whoisReturn["RegistrantFaxAreaCode"] . " " . $whoisReturn["RegistrantFaxLocalNumber"];
            }
            $values["Registrant"]["Fax"] = $regfaxno;
        }
        if ($whoisReturn["AdminName"] != "") {
            list($adminfirstname, $adminlastname) = explode(" ", $whoisReturn["AdminName"], 2);
            $values["Admin"]["First Name"] = $adminfirstname;
            $values["Admin"]["Last Name"] = $adminlastname;
            $values["Admin"]["Email"] = $whoisReturn["AdminEmail"];
            $values["Admin"]["Address1"] = $whoisReturn["AdminAddress1"];
            $values["Admin"]["Address2"] = $whoisReturn["AdminAddress2"];
            $values["Admin"]["City"] = $whoisReturn["AdminCity"];
            $values["Admin"]["Province"] = $whoisReturn["AdminProvince"];
            $values["Admin"]["PostalCode"] = $whoisReturn["AdminPostalCode"];
            $adminphoneno = "";
            $adminfaxno = "";
            if ($whoisReturn["AdminPhoneLocalNumber"] != "") {
                $adminphoneno = $whoisReturn["AdminPhoneCountryCode"] . " " . $whoisReturn["AdminPhoneAreaCode"] . " " . $whoisReturn["AdminPhoneLocalNumber"];
            }
            $values["Admin"]["Phone"] = $adminphoneno;
            if ($whoisReturn["AdminFaxLocalNumber"] != "") {
                $adminfaxno = $whoisReturn["AdminFaxCountryCode"] . " " . $whoisReturn["AdminFaxAreaCode"] . " " . $whoisReturn["AdminFaxLocalNumber"];
            }
            $values["Admin"]["Fax"] = $adminfaxno;
        }
        if ($whoisReturn["TechnicalName"] != "") {
            list($techfirstname, $techlastname) = explode(" ", $whoisReturn["TechnicalName"], 2);
            $values["Tech"]["First Name"] = $techfirstname;
            $values["Tech"]["Last Name"] = $techlastname;
            $values["Tech"]["Email"] = $whoisReturn["TechnicalEmail"];
            $values["Tech"]["Address1"] = $whoisReturn["TechnicalAddress1"];
            $values["Tech"]["Address2"] = $whoisReturn["TechnicalAddress2"];
            $values["Tech"]["City"] = $whoisReturn["TechnicalCity"];
            $values["Tech"]["Province"] = $whoisReturn["TechnicalProvince"];
            $values["Tech"]["PostalCode"] = $whoisReturn["TechnicalPostalCode"];
            $techphoneno = "";
            $techfaxno = "";
            if ($whoisReturn["TechnicalPhoneLocalNumber"] != "") {
                $techphoneno = $whoisReturn["TechnicalPhoneCountryCode"] . " " . $whoisReturn["TechnicalPhoneAreaCode"] . " " . $whoisReturn["TechnicalPhoneLocalNumber"];
            }
            $values["Tech"]["Phone"] = $techphoneno;
            if ($whoisReturn["TechnicalFaxLocalNumber"] != "") {
                $techfaxno = $whoisReturn["TechnicalFaxCountryCode"] . " " . $whoisReturn["TechnicalFaxAreaCode"] . " " . $whoisReturn["TechnicalFaxLocalNumber"];
            }
            $values["Tech"]["Fax"] = $techfaxno;
        }
    } else {
        $values["error"] = $loginStatus;
    }
    return $values;
}
function affordabledomains_SaveContactDetails($params)
{
    $whoisReturn = affordabledomains_whoisNzDomain($params);
    $myparams["username"] = $params["Username"];
    $myparams["password"] = $params["Password"];
    $myparams["testmode"] = $params["TestMode"];
    $myparams["tld"] = $params["tld"];
    $myparams["sld"] = $params["sld"];
    $myparams["regfirstname"] = $params["contactdetails"]["Registrant"]["First Name"];
    $myparams["reglastname"] = $params["contactdetails"]["Registrant"]["Last Name"];
    $myparams["regemail"] = $params["contactdetails"]["Registrant"]["Email"];
    $myparams["regaddress1"] = $params["contactdetails"]["Registrant"]["Address1"];
    if (!$myparams["regaddress1"]) {
        $myparams["regaddress1"] = $params["contactdetails"]["Registrant"]["Address 1"];
    }
    $myparams["regaddress2"] = $params["contactdetails"]["Registrant"]["Address2"];
    if (!$myparams["regaddress2"]) {
        $myparams["regaddress2"] = $params["contactdetails"]["Registrant"]["Address 2"];
    }
    $myparams["regcity"] = $params["contactdetails"]["Registrant"]["City"];
    $myparams["regprovince"] = $params["contactdetails"]["Registrant"]["Province"];
    if (!$myparams["regprovince"]) {
        $myparams["regprovince"] = $params["contactdetails"]["Registrant"]["Region"];
    }
    $myparams["regpostalcode"] = $params["contactdetails"]["Registrant"]["PostalCode"];
    if (!$myparams["regpostalcode"]) {
        $myparams["regpostalcode"] = $params["contactdetails"]["Registrant"]["Postcode"];
    }
    $myparams["regcountry"] = $whoisReturn["RegistrantCountryCode"];
    $myparams["adminfirstname"] = $params["contactdetails"]["Admin"]["First Name"];
    $myparams["adminlastname"] = $params["contactdetails"]["Admin"]["Last Name"];
    $myparams["adminemail"] = $params["contactdetails"]["Admin"]["Email"];
    $myparams["adminaddress1"] = $params["contactdetails"]["Admin"]["Address1"];
    if (!$myparams["adminaddress1"]) {
        $myparams["adminaddress1"] = $params["contactdetails"]["Admin"]["Address 1"];
    }
    $myparams["adminaddress2"] = $params["contactdetails"]["Admin"]["Address2"];
    if (!$myparams["adminaddress2"]) {
        $myparams["adminaddress2"] = $params["contactdetails"]["Admin"]["Address 2"];
    }
    $myparams["admincity"] = $params["contactdetails"]["Admin"]["City"];
    $myparams["adminprovince"] = $params["contactdetails"]["Admin"]["Province"];
    if (!$myparams["adminprovince"]) {
        $myparams["adminprovince"] = $params["contactdetails"]["Admin"]["Region"];
    }
    $myparams["adminpostalcode"] = $params["contactdetails"]["Admin"]["PostalCode"];
    if (!$myparams["adminpostalcode"]) {
        $myparams["adminpostalcode"] = $params["contactdetails"]["Admin"]["Postcode"];
    }
    $myparams["admincountry"] = $whoisReturn["AdminCountryCode"];
    $myparams["techfirstname"] = $params["contactdetails"]["Tech"]["First Name"];
    $myparams["techlastname"] = $params["contactdetails"]["Tech"]["Last Name"];
    $myparams["techemail"] = $params["contactdetails"]["Tech"]["Email"];
    $myparams["techaddress1"] = $params["contactdetails"]["Tech"]["Address1"];
    if (!$myparams["techaddress1"]) {
        $myparams["techaddress1"] = $params["contactdetails"]["Tech"]["Address 1"];
    }
    $myparams["techaddress2"] = $params["contactdetails"]["Tech"]["Address2"];
    if (!$myparams["techaddress2"]) {
        $myparams["techaddress2"] = $params["contactdetails"]["Tech"]["Address 2"];
    }
    $myparams["techcity"] = $params["contactdetails"]["Tech"]["City"];
    $myparams["techprovince"] = $params["contactdetails"]["Tech"]["Province"];
    if (!$myparams["techprovince"]) {
        $myparams["techprovince"] = $params["contactdetails"]["Tech"]["Region"];
    }
    $myparams["techpostalcode"] = $params["contactdetails"]["Tech"]["PostalCode"];
    if (!$myparams["techpostalcode"]) {
        $myparams["techpostalcode"] = $params["contactdetails"]["Tech"]["Postcode"];
    }
    $myparams["techcountry"] = $whoisReturn["TechnicalCountryCode"];
    $loginStatus = affordabledomains_login($params);
    if ($loginStatus == "success") {
        $registrantPhAr = affordabledomains_splitphonenumber($params["contactdetails"]["Registrant"]["Phone"]);
        $adminPhAr = affordabledomains_splitphonenumber($params["contactdetails"]["Admin"]["Phone"]);
        $techPhAr = affordabledomains_splitphonenumber($params["contactdetails"]["Tech"]["Phone"]);
        $registrantFxAr = affordabledomains_splitphonenumber($params["contactdetails"]["Registrant"]["Fax"]);
        $adminFxAr = affordabledomains_splitphonenumber($params["contactdetails"]["Admin"]["Fax"]);
        $techFxAr = affordabledomains_splitphonenumber($params["contactdetails"]["Tech"]["Fax"]);
        list($myparams["phonecountrycode"], $myparams["phoneareacode"], $myparams["phonelocalcode"]) = $registrantPhAr;
        list($myparams["adminphonecountrycode"], $myparams["adminphoneareacode"], $myparams["adminphonelocalcode"]) = $adminPhAr;
        list($myparams["techphonecountrycode"], $myparams["techphoneareacode"], $myparams["techphonelocalcode"]) = $techPhAr;
        if ($params["contactdetails"]["Registrant"]["Fax"] != "") {
            list($myparams["faxcountrycode"], $myparams["faxareacode"], $myparams["faxlocalcode"]) = $registrantFxAr;
        }
        if ($params["contactdetails"]["Admin"]["Fax"] != "") {
            list($myparams["adminfaxcountrycode"], $myparams["adminfaxareacode"], $myparams["adminfaxlocalcode"]) = $adminFxAr;
        }
        if ($params["contactdetails"]["Tech"]["Fax"] != "") {
            list($myparams["techfaxcountrycode"], $myparams["techfaxareacode"], $myparams["techfaxlocalcode"]) = $techFxAr;
        }
        $contactReturn = affordabledomains_contactSaveNzDomain($myparams);
        $contactReturnArr = explode("|", $contactReturn);
        foreach ($contactReturnArr as $contactReturnVal) {
            if ($contactReturnVal != "") {
                $contactStatus = explode(":", $contactReturnVal);
                if (trim($contactStatus[0]) != "success") {
                    $values["error"] = $contactStatus[1];
                }
            }
        }
    } else {
        $values["error"] = $loginStatus;
    }
    return $values;
}
function affordabledomains_GetEPPCode($params)
{
    $loginStatus = affordabledomains_login($params);
    if ($loginStatus == "success") {
        $eppReturn = affordabledomains_getUdaiNzDomain($params);
        $eppReturnArr = explode("|", $eppReturn);
        foreach ($eppReturnArr as $eppReturnVal) {
            if ($eppReturnVal != "") {
                $eppStatus = explode(":", $eppReturnVal);
                if (trim($eppStatus[0]) == "unsuccess") {
                    $values["error"] = $eppStatus[1];
                } else {
                    $values["eppcode"] = substr($eppStatus[1], strpos($eppStatus[1], "["), strpos($eppStatus[1], "]"));
                }
            }
        }
    } else {
        $values["error"] = $loginStatus;
    }
    return $values;
}
function affordabledomains_login($params)
{
    if ($params["TestMode"] == "on") {
        $url = "http://dev.affordabledomains.co.nz/testlab/api/whmcs/login.php";
    } else {
        $url = "http://www.affordabledomains.co.nz/api/whmcs/login.php";
    }
    $postfields["txtLoginUname"] = $params["Username"];
    $postfields["txtLoginPwd"] = $params["Password"];
    $postfields["txtIP"] = $_SERVER["SERVER_ADDR"];
    $loginResult = affordabledomains_connect_server($url, $postfields);
    return $loginResult;
}
function affordabledomains_connect_server($url, $postfields)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 100);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
    $data = curl_exec($ch);
    curl_close($ch);
    $action = $url;
    $action = str_replace("http://dev.affordabledomains.co.nz/testlab/api/whmcs/", "", $action);
    $action = str_replace("http://www.affordabledomains.co.nz/api/whmcs/", "", $action);
    $action = str_replace(".php", "", $action);
    logModuleCall("affordabledomains", $action, $postfields, $data, "", array($postfields["txtLoginUname"], $postfields["txtLoginPwd"]));
    return $data;
}
function affordabledomains_registerNzDomain($params)
{
    $tld = explode(".", $params["tld"]);
    if ($tld[count($tld) - 1] == "nz") {
        if ($params["TestMode"] == "on") {
            $url = "http://dev.affordabledomains.co.nz/testlab/api/whmcs/registerNzDomain.php";
        } else {
            $url = "http://www.affordabledomains.co.nz/api/whmcs/registerNzDomain.php";
        }
    } else {
        if ($params["TestMode"] == "on") {
            $url = "http://dev.affordabledomains.co.nz/testlab/api/whmcs/registerGlobalDomain.php";
        } else {
            $url = "http://www.affordabledomains.co.nz/api/whmcs/registerGlobalDomain.php";
        }
    }
    $postfields["txtLoginUname"] = $params["Username"];
    $postfields["txtLoginPwd"] = $params["Password"];
    $postfields["txtFName"] = $params["firstname"];
    $postfields["txtLName"] = $params["lastname"];
    $postfields["txtAdd1"] = $params["address1"];
    $postfields["txtAdd2"] = $params["address2"];
    $postfields["txtCity"] = $params["city"];
    $postfields["txtProv"] = $params["state"];
    $postfields["txtPostal"] = $params["postcode"];
    $postfields["cboCountry"] = $params["country"];
    $postfields["txtEmail"] = $params["email"];
    $postfields["txtPh1"] = $params["phonecountrycode"];
    $postfields["txtPh2"] = $params["phoneareacode"];
    $postfields["txtPh3"] = $params["phonelocalcode"];
    $postfields["txtfax1"] = "";
    $postfields["txtfax2"] = "";
    $postfields["txtfax3"] = "";
    $postfields["billFName"] = $params["adminfirstname"];
    $postfields["billLName"] = $params["adminlastname"];
    $postfields["billAdd1"] = $params["adminaddress1"];
    $postfields["billAdd2"] = $params["adminaddress2"];
    $postfields["billCity"] = $params["admincity"];
    $postfields["billProv"] = $params["adminstate"];
    $postfields["billPostal"] = $params["adminpostcode"];
    $postfields["cboBillCountry"] = $params["admincountry"];
    $postfields["billEmail"] = $params["adminemail"];
    $postfields["billPh1"] = $params["adminphonecountrycode"];
    $postfields["billPh2"] = $params["adminphoneareacode"];
    $postfields["billPh3"] = $params["adminphonelocalcode"];
    $postfields["billFax1"] = "";
    $postfields["billFax2"] = "";
    $postfields["billFax3"] = "";
    $postfields["techFName"] = $params["firstname"];
    $postfields["techLName"] = $params["lastname"];
    $postfields["techAdd1"] = $params["address1"];
    $postfields["techAdd2"] = $params["address2"];
    $postfields["techCity"] = $params["city"];
    $postfields["techProv"] = $params["state"];
    $postfields["techPostal"] = $params["postcode"];
    $postfields["cboTechCountry"] = $params["country"];
    $postfields["techEmail"] = $params["email"];
    $postfields["techPh1"] = $params["phonecountrycode"];
    $postfields["techPh2"] = $params["phoneareacode"];
    $postfields["techPh3"] = $params["phonelocalcode"];
    $postfields["techFax1"] = "";
    $postfields["techFax2"] = "";
    $postfields["techFax3"] = "";
    $postfields["txthostname1"] = $params["ns1"];
    $postfields["txthostname2"] = $params["ns2"];
    $postfields["txthostname3"] = $params["ns3"];
    $postfields["txthostname4"] = $params["ns4"];
    $postfields["txtip1"] = "";
    $postfields["txtip2"] = "";
    $postfields["txtip3"] = "";
    $postfields["txtip4"] = "";
    $postfields["txtv6ip1"] = "";
    $postfields["txtv6ip2"] = "";
    $postfields["txtv6ip3"] = "";
    $postfields["txtv6ip4"] = "";
    $postfields["txtdomainname"] = $params["sld"] . "." . $params["tld"];
    $postfields["txtdomainext"] = $params["tld"];
    if ($tld[count($tld) - 1] == "nz") {
        $postfields["cboRenew"] = $params["regperiod"] * 12;
    } else {
        $postfields["cboRenew"] = $params["regperiod"];
    }
    $registerResult = affordabledomains_connect_server($url, $postfields);
    return $registerResult;
}
function affordabledomains_whoisNzDomain($params)
{
    $tld = explode(".", $params["tld"]);
    if ($tld[count($tld) - 1] == "nz") {
        if ($params["TestMode"] == "on") {
            $url = "http://dev.affordabledomains.co.nz/testlab/api/whmcs/whoisNzDomain.php";
        } else {
            $url = "http://www.affordabledomains.co.nz/api/whmcs/whoisNzDomain.php";
        }
    } else {
        if ($params["TestMode"] == "on") {
            $url = "http://dev.affordabledomains.co.nz/testlab/api/whmcs/whoisGlobalDomain.php";
        } else {
            $url = "http://www.affordabledomains.co.nz/api/whmcs/whoisGlobalDomain.php";
        }
    }
    $postfields["txtLoginUname"] = $params["Username"];
    $postfields["txtLoginPwd"] = $params["Password"];
    $postfields["txtdomainname"] = $params["sld"] . "." . $params["tld"];
    $postfields["txtdomainext"] = $params["tld"];
    $whoisResult = affordabledomains_connect_server($url, $postfields);
    $xml = new SimpleXMLElement($whoisResult);
    $resultDomain = $xml->xpath("/DomainsResponse/Domain");
    if (0 < count($resultDomain)) {
        $whoisVal["ServerDomainName"] = $resultDomain[0]["DomainName"];
        $whoisVal["ServerDomainStatus"] = $resultDomain[0]["Status"];
    }
    $resultRegistrant = $xml->xpath("/DomainsResponse/Domain/RegistrantContact");
    if (0 < count($resultRegistrant)) {
        $whoisVal["RegistrantName"] = $resultRegistrant[0]["Name"];
        $whoisVal["RegistrantEmail"] = $resultRegistrant[0]["Email"];
    }
    $resultRegistrantPostalAddress = $xml->xpath("/DomainsResponse/Domain/RegistrantContact/PostalAddress");
    if (0 < count($resultRegistrantPostalAddress)) {
        $whoisVal["RegistrantAddress1"] = $resultRegistrantPostalAddress[0]["Address1"];
        $whoisVal["RegistrantAddress2"] = $resultRegistrantPostalAddress[0]["Address2"];
        $whoisVal["RegistrantCity"] = $resultRegistrantPostalAddress[0]["City"];
        $whoisVal["RegistrantCountryCode"] = $resultRegistrantPostalAddress[0]["CountryCode"];
        $whoisVal["RegistrantPostalCode"] = $resultRegistrantPostalAddress[0]["PostalCode"];
        $whoisVal["RegistrantProvince"] = $resultRegistrantPostalAddress[0]["Province"];
    }
    $resultRegistrantPhone = $xml->xpath("/DomainsResponse/Domain/RegistrantContact/Phone");
    if (0 < count($resultRegistrantPhone)) {
        $whoisVal["RegistrantPhoneAreaCode"] = $resultRegistrantPhone[0]["AreaCode"];
        $whoisVal["RegistrantPhoneCountryCode"] = $resultRegistrantPhone[0]["CountryCode"];
        $whoisVal["RegistrantPhoneLocalNumber"] = $resultRegistrantPhone[0]["LocalNumber"];
    }
    $resultRegistrantFax = $xml->xpath("/DomainsResponse/Domain/RegistrantContact/Fax");
    if (0 < count($resultRegistrantFax)) {
        $whoisVal["RegistrantFaxAreaCode"] = $resultRegistrantFax[0]["AreaCode"];
        $whoisVal["RegistrantFaxCountryCode"] = $resultRegistrantFax[0]["CountryCode"];
        $whoisVal["RegistrantFaxLocalNumber"] = $resultRegistrantFax[0]["LocalNumber"];
    }
    $resultAdmin = $xml->xpath("/DomainsResponse/Domain/AdminContact");
    if (0 < count($resultAdmin)) {
        $whoisVal["AdminName"] = $resultAdmin[0]["Name"];
        $whoisVal["AdminEmail"] = $resultAdmin[0]["Email"];
    }
    $resultAdminPostalAddress = $xml->xpath("/DomainsResponse/Domain/AdminContact/PostalAddress");
    if (0 < count($resultAdminPostalAddress)) {
        $whoisVal["AdminAddress1"] = $resultAdminPostalAddress[0]["Address1"];
        $whoisVal["AdminAddress2"] = $resultAdminPostalAddress[0]["Address2"];
        $whoisVal["AdminCity"] = $resultAdminPostalAddress[0]["City"];
        $whoisVal["AdminCountryCode"] = $resultAdminPostalAddress[0]["CountryCode"];
        $whoisVal["AdminPostalCode"] = $resultAdminPostalAddress[0]["PostalCode"];
        $whoisVal["AdminProvince"] = $resultAdminPostalAddress[0]["Province"];
    }
    $resultAdminPhone = $xml->xpath("/DomainsResponse/Domain/AdminContact/Phone");
    if (0 < count($resultAdminPhone)) {
        $whoisVal["AdminPhoneAreaCode"] = $resultAdminPhone[0]["AreaCode"];
        $whoisVal["AdminPhoneCountryCode"] = $resultAdminPhone[0]["CountryCode"];
        $whoisVal["AdminPhoneLocalNumber"] = $resultAdminPhone[0]["LocalNumber"];
    }
    $AdminRegistrarFax = $xml->xpath("/DomainsResponse/Domain/AdminContact/Fax");
    if (0 < count($AdminRegistrarFax)) {
        $whoisVal["AdminFaxAreaCode"] = $AdminRegistrarFax[0]["AreaCode"];
        $whoisVal["AdminFaxCountryCode"] = $AdminRegistrarFax[0]["CountryCode"];
        $whoisVal["AdminFaxLocalNumber"] = $AdminRegistrarFax[0]["LocalNumber"];
    }
    $resultTechnical = $xml->xpath("/DomainsResponse/Domain/TechnicalContact");
    if (0 < count($resultTechnical)) {
        $whoisVal["TechnicalName"] = $resultTechnical[0]["Name"];
        $whoisVal["TechnicalEmail"] = $resultTechnical[0]["Email"];
    }
    $resultTechnicalPostalAddress = $xml->xpath("/DomainsResponse/Domain/TechnicalContact/PostalAddress");
    if (0 < count($resultTechnicalPostalAddress)) {
        $whoisVal["TechnicalAddress1"] = $resultTechnicalPostalAddress[0]["Address1"];
        $whoisVal["TechnicalAddress2"] = $resultTechnicalPostalAddress[0]["Address2"];
        $whoisVal["TechnicalCity"] = $resultTechnicalPostalAddress[0]["City"];
        $whoisVal["TechnicalCountryCode"] = $resultTechnicalPostalAddress[0]["CountryCode"];
        $whoisVal["TechnicalPostalCode"] = $resultTechnicalPostalAddress[0]["PostalCode"];
        $whoisVal["TechnicalProvince"] = $resultAdminPostalAddress[0]["Province"];
    }
    $resultTechnicalPhone = $xml->xpath("/DomainsResponse/Domain/TechnicalContact/Phone");
    if (0 < count($resultTechnicalPhone)) {
        $whoisVal["TechnicalPhoneAreaCode"] = $resultTechnicalPhone[0]["AreaCode"];
        $whoisVal["TechnicalPhoneCountryCode"] = $resultTechnicalPhone[0]["CountryCode"];
        $whoisVal["TechnicalPhoneLocalNumber"] = $resultTechnicalPhone[0]["LocalNumber"];
    }
    $TechnicalRegistrarFax = $xml->xpath("/DomainsResponse/Domain/TechnicalContact/Fax");
    if (0 < count($TechnicalRegistrarFax)) {
        $whoisVal["TechnicalFaxAreaCode"] = $TechnicalRegistrarFax[0]["AreaCode"];
        $whoisVal["TechnicalFaxCountryCode"] = $TechnicalRegistrarFax[0]["CountryCode"];
        $whoisVal["TechnicalFaxLocalNumber"] = $TechnicalRegistrarFax[0]["LocalNumber"];
    }
    $resultServer = $xml->xpath("/DomainsResponse/Domain/NameServers/Server");
    if (0 < count($resultServer)) {
        $whoisVal["ServerFQDN1"] = $resultServer[0]["FQDN"];
        $whoisVal["ServerIP4Addr1"] = $resultServer[0]["IP4Addr"];
        $whoisVal["ServerFQDN2"] = $resultServer[1]["FQDN"];
        $whoisVal["ServerIP4Addr2"] = $resultServer[1]["IP4Addr"];
        $whoisVal["ServerFQDN3"] = $resultServer[2]["FQDN"];
        $whoisVal["ServerIP4Addr3"] = $resultServer[2]["IP4Addr"];
        $whoisVal["ServerFQDN4"] = $resultServer[3]["FQDN"];
        $whoisVal["ServerIP4Addr4"] = $resultServer[3]["IP4Addr"];
    }
    return $whoisVal;
}
function affordabledomains_nsSaveNzDomain($params)
{
    $tld = explode(".", $params["tld"]);
    if ($tld[count($tld) - 1] == "nz") {
        if ($params["TestMode"] == "on") {
            $url = "http://dev.affordabledomains.co.nz/testlab/api/whmcs/nsSaveNzDomain.php";
        } else {
            $url = "http://www.affordabledomains.co.nz/api/whmcs/nsSaveNzDomain.php";
        }
    } else {
        if ($params["TestMode"] == "on") {
            $url = "http://dev.affordabledomains.co.nz/testlab/api/whmcs/nsSaveGlobalDomain.php";
        } else {
            $url = "http://www.affordabledomains.co.nz/api/whmcs/nsSaveGlobalDomain.php";
        }
    }
    $postfields["txtLoginUname"] = $params["Username"];
    $postfields["txtLoginPwd"] = $params["Password"];
    $postfields["txtdomainname"] = $params["sld"] . "." . $params["tld"];
    $postfields["txtdomainext"] = $params["tld"];
    $postfields["txthostname1"] = $params["ns1"];
    $postfields["txthostname2"] = $params["ns2"];
    $postfields["txthostname3"] = $params["ns3"];
    $postfields["txthostname4"] = $params["ns4"];
    $postfields["txtip1"] = "";
    $postfields["txtip2"] = "";
    $postfields["txtip3"] = "";
    $postfields["txtip4"] = "";
    $postfields["txtv6ip1"] = "";
    $postfields["txtv6ip2"] = "";
    $postfields["txtv6ip3"] = "";
    $postfields["txtv6ip4"] = "";
    $nsSaveResult = affordabledomains_connect_server($url, $postfields);
}
function affordabledomains_contactSaveNzDomain($myparams)
{
    $tld = explode(".", $myparams["tld"]);
    if ($tld[count($tld) - 1] == "nz") {
        if ($myparams["testmode"] == "on") {
            $url = "http://dev.affordabledomains.co.nz/testlab/api/whmcs/contactSaveNzDomain.php";
        } else {
            $url = "http://www.affordabledomains.co.nz/api/whmcs/contactSaveNzDomain.php";
        }
    } else {
        if ($myparams["testmode"] == "on") {
            $url = "http://dev.affordabledomains.co.nz/testlab/api/whmcs/contactSaveGlobalDomain.php";
        } else {
            $url = "http://www.affordabledomains.co.nz/api/whmcs/contactSaveGlobalDomain.php";
        }
    }
    $phonenumber = $myparams["fullphonenumber"];
    $postfields["txtLoginUname"] = $myparams["username"];
    $postfields["txtLoginPwd"] = $myparams["password"];
    $postfields["txtdomainname"] = $myparams["sld"] . "." . $myparams["tld"];
    $postfields["txtdomainext"] = $myparams["tld"];
    $postfields["txtFName"] = $myparams["regfirstname"];
    $postfields["txtLName"] = $myparams["reglastname"];
    $postfields["txtAdd1"] = $myparams["regaddress1"];
    $postfields["txtAdd2"] = $myparams["regaddress2"];
    $postfields["txtCity"] = $myparams["regcity"];
    $postfields["txtProv"] = $myparams["regprovince"];
    $postfields["txtPostal"] = $myparams["regpostalcode"];
    $postfields["cboCountry"] = $myparams["regcountry"];
    $postfields["txtEmail"] = $myparams["regemail"];
    $postfields["txtPh1"] = $myparams["phonecountrycode"];
    $postfields["txtPh2"] = $myparams["phoneareacode"];
    $postfields["txtPh3"] = $myparams["phonelocalcode"];
    $postfields["txtfax1"] = $myparams["faxcountrycode"];
    $postfields["txtfax2"] = $myparams["faxareacode"];
    $postfields["txtfax3"] = $myparams["faxlocalcode"];
    $postfields["billFName"] = $myparams["adminfirstname"];
    $postfields["billLName"] = $myparams["adminlastname"];
    $postfields["billAdd1"] = $myparams["adminaddress1"];
    $postfields["billAdd2"] = $myparams["adminaddress2"];
    $postfields["billCity"] = $myparams["admincity"];
    $postfields["billProv"] = $myparams["adminprovince"];
    $postfields["billPostal"] = $myparams["adminpostalcode"];
    $postfields["cboBillCountry"] = $myparams["admincountry"];
    $postfields["billEmail"] = $myparams["adminemail"];
    $postfields["billPh1"] = $myparams["adminphonecountrycode"];
    $postfields["billPh2"] = $myparams["adminphoneareacode"];
    $postfields["billPh3"] = $myparams["adminphonelocalcode"];
    $postfields["billFax1"] = $myparams["adminfaxcountrycode"];
    $postfields["billFax2"] = $myparams["adminfaxareacode"];
    $postfields["billFax3"] = $myparams["adminfaxlocalcode"];
    $postfields["techFName"] = $myparams["techfirstname"];
    $postfields["techLName"] = $myparams["techlastname"];
    $postfields["techAdd1"] = $myparams["techaddress1"];
    $postfields["techAdd2"] = $myparams["techaddress2"];
    $postfields["techCity"] = $myparams["techcity"];
    $postfields["techProv"] = $myparams["techprovince"];
    $postfields["techPostal"] = $myparams["techpostalcode"];
    $postfields["cboTechCountry"] = $myparams["techcountry"];
    $postfields["techEmail"] = $myparams["techemail"];
    $postfields["techPh1"] = $myparams["techphonecountrycode"];
    $postfields["techPh2"] = $myparams["techphoneareacode"];
    $postfields["techPh3"] = $myparams["techphonelocalcode"];
    $postfields["techFax1"] = $myparams["techfaxcountrycode"];
    $postfields["techFax2"] = $myparams["techfaxareacode"];
    $postfields["techFax3"] = $myparams["techfaxlocalcode"];
    $contactSaveResult = affordabledomains_connect_server($url, $postfields);
    return $contactSaveResult;
}
function affordabledomains_renewNzDomain($params)
{
    $tld = explode(".", $params["tld"]);
    if ($tld[count($tld) - 1] == "nz") {
        if ($params["TestMode"] == "on") {
            $url = "http://dev.affordabledomains.co.nz/testlab/api/whmcs/renewNzDomain.php";
        } else {
            $url = "http://www.affordabledomains.co.nz/api/whmcs/renewNzDomain.php";
        }
    } else {
        if ($params["TestMode"] == "on") {
            $url = "http://dev.affordabledomains.co.nz/testlab/api/whmcs/renewGlobalDomain.php";
        } else {
            $url = "http://www.affordabledomains.co.nz/api/whmcs/renewGlobalDomain.php";
        }
    }
    $postfields["txtLoginUname"] = $params["Username"];
    $postfields["txtLoginPwd"] = $params["Password"];
    $postfields["txtdomainname"] = $params["sld"] . "." . $params["tld"];
    $postfields["txtdomainext"] = $params["tld"];
    if ($tld[count($tld) - 1] == "nz") {
        $postfields["cboRenew"] = $params["regperiod"] * 12;
    } else {
        $postfields["cboRenew"] = $params["regperiod"];
    }
    $renewResult = affordabledomains_connect_server($url, $postfields);
    return $renewResult;
}
function affordabledomains_transNzDomain($params)
{
    $tld = explode(".", $params["tld"]);
    if ($tld[count($tld) - 1] == "nz") {
        if ($params["TestMode"] == "on") {
            $url = "http://dev.affordabledomains.co.nz/testlab/api/whmcs/transNzDomain.php";
        } else {
            $url = "http://www.affordabledomains.co.nz/api/whmcs/transNzDomain.php";
        }
    } else {
        if ($params["TestMode"] == "on") {
            $url = "http://dev.affordabledomains.co.nz/testlab/api/whmcs/transGlobalDomain.php";
        } else {
            $url = "http://www.affordabledomains.co.nz/api/whmcs/transGlobalDomain.php";
        }
    }
    $postfields["txtLoginUname"] = $params["Username"];
    $postfields["txtLoginPwd"] = $params["Password"];
    $postfields["txtdomainname"] = $params["sld"] . "." . $params["tld"];
    $postfields["txtdomainext"] = $params["tld"];
    $postfields["txtUDAI"] = $params["transfersecret"];
    $postfields["txtLoginUname"] = $params["Username"];
    $postfields["txtLoginPwd"] = $params["Password"];
    $postfields["txtFName"] = $params["firstname"];
    $postfields["txtLName"] = $params["lastname"];
    $postfields["txtCompany"] = $params["companyname"];
    $postfields["txtAdd1"] = $params["address1"];
    $postfields["txtAdd2"] = $params["address2"];
    $postfields["txtCity"] = $params["city"];
    $postfields["txtProv"] = $params["state"];
    $postfields["txtPostal"] = $params["postcode"];
    $postfields["cboCountry"] = $params["country"];
    $postfields["txtEmail"] = $params["email"];
    $postfields["txtPh1"] = $params["phonecountrycode"];
    $postfields["txtPh2"] = $params["phoneareacode"];
    $postfields["txtPh3"] = $params["phonelocalcode"];
    $postfields["txtfax1"] = "";
    $postfields["txtfax2"] = "";
    $postfields["txtfax3"] = "";
    $postfields["txthostname1"] = $params["ns1"];
    $postfields["txthostname2"] = $params["ns2"];
    $postfields["txthostname3"] = $params["ns3"];
    $postfields["txthostname4"] = $params["ns4"];
    $postfields["txtip1"] = "";
    $postfields["txtip2"] = "";
    $postfields["txtip3"] = "";
    $postfields["txtip4"] = "";
    $postfields["txtv6ip1"] = "";
    $postfields["txtv6ip2"] = "";
    $postfields["txtv6ip3"] = "";
    $postfields["txtv6ip4"] = "";
    $transResult = affordabledomains_connect_server($url, $postfields);
    return $transResult;
}
function affordabledomains_getUdaiNzDomain($params)
{
    $tld = explode(".", $params["tld"]);
    if ($tld[count($tld) - 1] == "nz") {
        if ($params["TestMode"] == "on") {
            $url = "http://dev.affordabledomains.co.nz/testlab/api/whmcs/getUdaiNzDomain.php";
        } else {
            $url = "http://www.affordabledomains.co.nz/api/whmcs/getUdaiNzDomain.php";
        }
    } else {
        if ($params["TestMode"] == "on") {
            $url = "http://dev.affordabledomains.co.nz/testlab/api/whmcs/getUdaiGlobalDomain.php";
        } else {
            $url = "http://www.affordabledomains.co.nz/api/whmcs/getUdaiGlobalDomain.php";
        }
    }
    $postfields["txtLoginUname"] = $params["Username"];
    $postfields["txtLoginPwd"] = $params["Password"];
    $postfields["txtdomainname"] = $params["sld"] . "." . $params["tld"];
    $postfields["txtdomainext"] = $params["tld"];
    $getUdaiResult = affordabledomains_connect_server($url, $postfields);
    return $getUdaiResult;
}

?>