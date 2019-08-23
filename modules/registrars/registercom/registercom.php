<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function registercom_getConfigArray()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "Register.com"), "applicationGuid" => array("Type" => "text", "Size" => "20", "Description" => "This is the unique key assigned by RxPortalExpress"), "TestMode" => array("Type" => "yesno"));
    return $configarray;
}
function registercom_GetNameservers($params)
{
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domain = $sld . "." . $tld;
    $xml = "<serviceRequest>\n<command>domainGet</command>\n<client>\n<applicationGuid>" . $params["applicationGuid"] . "</applicationGuid>\n<clientRef>" . md5(date("YmdHis")) . "</clientRef>\n</client>\n<request>\n<page>1</page>\n<domains>\n<domainName>" . $domain . "</domainName>\n</domains>\n</request>\n</serviceRequest>";
    $data = registercom_curlCall($xml, $params);
    $data = $data["serviceResponse"]["response"]["domainGet"]["domain"]["nameServers"]["nameServer"];
    $values["ns1"] = $data[0]["nsName"]["value"];
    $values["ns2"] = $data[1]["nsName"]["value"];
    if (empty($values["ns1"]) && empty($values["ns2"])) {
        $values["error"] = "Could not retrieve nameservers for the domain " . $domain;
    }
    return $values;
}
function registercom_SaveNameservers($params)
{
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domain = $sld . "." . $tld;
    $nameserver1 = $params["ns1"];
    $nameserver2 = $params["ns2"];
    $product_id = registercom_GetProductIdByDomain($domain, $params);
    $xml_modify = "<serviceRequest>\n<command>domainModify</command>\n<client>\n<applicationGuid>" . $params["applicationGuid"] . "</applicationGuid>\n<clientRef>" . md5(date("YmdHis")) . "</clientRef>\n</client>\n<request>\n<productId>" . $product_id . "</productId>\n<nameservers>\n<nameserver>\n<nsType>Primary</nsType>\n<nsName>" . $nameserver1 . "</nsName>\n</nameserver>\n<nameserver>\n<nsType>Secondary</nsType>\n<nsName>" . $nameserver2 . "</nsName>\n</nameserver>\n</nameservers>\n</request>\n</serviceRequest>";
    $data = registercom_curlCall($xml_modify, $params);
    $data = $data["serviceResponse"]["status"]["statusCode"]["value"];
    if ($data != "1000") {
        $values["error"] = "The requested nameserver changes were NOT accepted by the registrar for the domain " . $domain;
    }
    return $values;
}
function registercom_GetRegistrarLock($params)
{
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domain = $sld . "." . $tld;
    $xml = "<serviceRequest>\n<command>domainGet</command>\n<client>\n<applicationGuid>" . $params["applicationGuid"] . "</applicationGuid>\n<clientRef>" . md5(date("YmdHis")) . "</clientRef>\n</client>\n<request>\n<page>1</page>\n<domains>\n<domainName>" . $domain . "</domainName>\n</domains>\n</request>\n</serviceRequest>";
    $data = registercom_curlCall($xml, $params);
    $lock = $data["serviceResponse"]["response"]["domainGet"]["domain"]["domainInfo"]["registrarLock"]["value"];
    if ($lock == "On") {
        $lockstatus = "locked";
    } else {
        $lockstatus = "unlocked";
    }
    return $lockstatus;
}
function registercom_SaveRegistrarLock($params)
{
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domain = $sld . "." . $tld;
    $product_id = registercom_GetProductIdByDomain($domain, $params);
    if ($params["lockenabled"] == "locked") {
        $lockstatus = "True";
    } else {
        $lockstatus = "False";
    }
    $xml_lock = "<serviceRequest>\n<command>domainLock</command>\n<client>\n<applicationGuid>" . $params["applicationGuid"] . "</applicationGuid>\n<clientRef>" . md5(date("YmdHis")) . "</clientRef>\n</client>\n<request>\n<productId>" . $product_id . "</productId>\n<registrarLock>" . $lockstatus . "</registrarLock>\n</request>\n</serviceRequest>";
    $data = registercom_curlCall($xml_lock, $params);
    $data = $data["serviceResponse"]["status"]["statusCode"]["value"];
    if ($data != "1000") {
        $values["error"] = "Could not update Registrar Lock Status for the domain " . $domain;
    }
    return $values;
}
function registercom_RegisterDomain($params)
{
    $countries = new WHMCS\Utility\Country();
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domain = $sld . "." . $tld;
    $regperiod = $params["regperiod"];
    $nameserver1 = $params["ns1"];
    $nameserver2 = $params["ns2"];
    $RegistrantFirstName = $params["firstname"];
    $RegistrantLastName = $params["lastname"];
    $RegistrantAddress1 = $params["address1"];
    $RegistrantAddress2 = $params["address2"];
    $RegistrantCity = $params["city"];
    $RegistrantPostalCode = $params["postcode"];
    $RegistrantCountry = $params["country"];
    $RegistrantStateProvince = $params["state"];
    $RegistrantEmailAddress = $params["email"];
    $RegistrantPhone = $params["fullphonenumber"];
    $AdminPhone = $params["adminfullphonenumber"];
    $AdminFirstName = $params["adminfirstname"];
    $AdminLastName = $params["adminlastname"];
    $AdminAddress1 = $params["adminaddress1"];
    $AdminAddress2 = $params["adminaddress2"];
    $AdminCity = $params["admincity"];
    $AdminStateProvince = $params["adminstate"];
    $AdminPostalCode = $params["adminpostcode"];
    $AdminCountry = $params["admincountry"];
    $AdminEmailAddress = $params["adminemail"];
    $xml_adduser = "<serviceRequest>\n<command>userAdd</command>\n<client>\n<applicationGuid>" . $params["applicationGuid"] . "</applicationGuid>\n<clientRef>" . md5(date("YmdHis")) . "</clientRef>\n</client>\n<request>\n<userId>" . $RegistrantEmailAddress . "</userId>\n<userAccountName>" . $RegistrantFirstName . " " . $RegistrantLastName . "</userAccountName>\n<contacts>\n<contact>\n<firstName>" . $RegistrantFirstName . "</firstName>\n<lastName>" . $RegistrantLastName . "</lastName>\n<emailAddress>" . $RegistrantEmailAddress . "</emailAddress>\n<telephoneNumber>" . $RegistrantPhone . "</telephoneNumber>\n<addressLine1>" . $RegistrantAddress1 . "</addressLine1>\n<addressLine2>" . $RegistrantAddress2 . "</addressLine2>\n<city>" . $RegistrantCity . "</city>";
    if ($RegistrantCountry == "US") {
        $xml_adduser .= "<state>" . $RegistrantStateProvince . "</state>";
    } else {
        $xml_adduser .= "<province>" . $RegistrantStateProvince . "</province>";
    }
    $xml_adduser .= "<postalCode>" . $RegistrantPostalCode . "</postalCode>\n<countryCode>" . $RegistrantCountry . "</countryCode>\n<contactType>Registration</contactType>\n</contact>";
    if ($AdminEmailAddress != $RegistrantEmailAddress) {
        $xml_adduser .= "<contact>\n<firstName>" . $AdminFirstName . "</firstName>\n<lastName>" . $AdminLastName . "</lastName>\n<emailAddress>" . $AdminEmailAddress . "</emailAddress>\n<telephoneNumber>" . $AdminPhone . "</telephoneNumber>\n<addressLine1>" . $AdminAddress1 . "</addressLine1>\n<addressLine2>" . $AdminAddress2 . "</addressLine2>\n<city>" . $AdminCity . "</city>";
        if ($AdminCountry == "US") {
            $xml_adduser .= "<state>" . $AdminStateProvince . "</state>";
        } else {
            $xml_adduser .= "<province>" . $AdminStateProvince . "</province>";
        }
        $xml_adduser .= "<postalCode>" . $AdminPostalCode . "</postalCode>\n<countryCode>" . $AdminCountry . "</countryCode>\n<contactType>Administration</contactType>\n</contact>";
    }
    $xml_adduser .= "\n</contacts>\n</request>\n</serviceRequest>";
    $data = registercom_curlCall($xml_adduser, $params);
    if ($data["serviceResponse"]["status"]["statusCode"]["value"] != "1000" && $data["serviceResponse"]["status"]["statusCode"]["value"] != "1005") {
        $values["error"] = "Could not add or update user account " . $AdminFirstName . " " . $AdminLastName . " with Registrar for the domainAdd request of " . $domain;
        return $values;
    }
    $xml_adddomain = "<serviceRequest>\n<command>domainAdd</command>\n<client>\n<applicationGuid>" . $params["applicationGuid"] . "</applicationGuid>\n<clientRef>" . md5(date("YmdHis")) . "</clientRef>\n</client>\n<request>\n<userId>" . $AdminEmailAddress . "</userId>\n<domainName>" . $domain . "</domainName>\n<term>" . $regperiod . "</term>\n<contacts>";
    $xml_adddomain .= "<contact>\n<title>Mr.</title>\n<firstName>" . $RegistrantFirstName . "</firstName>\n<lastName>" . $RegistrantLastName . "</lastName>\n<emailAddress>" . $RegistrantEmailAddress . "</emailAddress>\n<telephoneNumber>" . $RegistrantPhone . "</telephoneNumber>\n<addressLine1>" . $RegistrantAddress1 . "</addressLine1>\n<addressLine2>" . $RegistrantAddress2 . "</addressLine2>\n<city>" . $RegistrantCity . "</city>";
    if ($RegistrantCountry == "US") {
        $xml_adddomain .= "<state>" . $RegistrantStateProvince . "</state>";
    } else {
        $xml_adddomain .= "<province>" . $RegistrantStateProvince . "</province>";
    }
    $xml_adddomain .= "<postalCode>" . $RegistrantPostalCode . "</postalCode>\n<countryCode>" . $RegistrantCountry . "</countryCode>\n<contactType>Registration</contactType>\n</contact>";
    $xml_adddomain .= "<contact>\n<title>Mr.</title>\n<firstName>" . $AdminFirstName . "</firstName>\n<lastName>" . $AdminLastName . "</lastName>\n<emailAddress>" . $AdminEmailAddress . "</emailAddress>\n<telephoneNumber>" . $AdminPhone . "</telephoneNumber>\n<addressLine1>" . $AdminAddress1 . "</addressLine1>\n<addressLine2>" . $AdminAddress2 . "</addressLine2>\n<city>" . $AdminCity . "</city>";
    if ($AdminCountry == "US") {
        $xml_adddomain .= "<state>" . $AdminStateProvince . "</state>";
    } else {
        $xml_adddomain .= "<province>" . $AdminStateProvince . "</province>";
    }
    $xml_adddomain .= "<postalCode>" . $AdminPostalCode . "</postalCode>\n<countryCode>" . $AdminCountry . "</countryCode>\n<contactType>Administration</contactType>\n</contact>";
    $xml_adddomain .= "</contacts>\n</request>\n</serviceRequest>";
    $data = registercom_curlCall($xml_adddomain, $params);
    $domProductId = $data["serviceResponse"]["response"]["productId"]["value"];
    $data = $data["serviceResponse"]["status"]["statusCode"]["value"];
    if ($data != "1000") {
        $values["error"] = "Failed to register the domain " . $domain;
        return $values;
    }
    $domain_product_id = registercom_GetProductIdByDomain($domain, $params);
    if ($domProductId != $domain_product_id) {
        $values["error"] = "Failed to register the domain " . $domain;
        return $values;
    }
}
function registercom_TransferDomain($params)
{
    $countries = new WHMCS\Utility\Country();
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domain = $sld . "." . $tld;
    $transfersecret = $params["transfersecret"];
    $nameserver1 = $params["ns1"];
    $nameserver2 = $params["ns2"];
    $RegistrantFirstName = $params["firstname"];
    $RegistrantLastName = $params["lastname"];
    $RegistrantAddress1 = $params["address1"];
    $RegistrantAddress2 = $params["address2"];
    $RegistrantCity = $params["city"];
    $RegistrantPostalCode = $params["postcode"];
    $RegistrantCountry = $params["country"];
    $RegistrantStateProvince = $params["state"];
    $RegistrantEmailAddress = $params["email"];
    $RegistrantPhone = $params["fullphonenumber"];
    $AdminPhone = $params["adminfullphonenumber"];
    $AdminFirstName = $params["adminfirstname"];
    $AdminLastName = $params["adminlastname"];
    $AdminAddress1 = $params["adminaddress1"];
    $AdminAddress2 = $params["adminaddress2"];
    $AdminCity = $params["admincity"];
    $AdminStateProvince = $params["adminstate"];
    $AdminPostalCode = $params["adminpostcode"];
    $AdminCountry = $params["admincountry"];
    $AdminEmailAddress = $params["adminemail"];
    $xml_adduser = "<serviceRequest>\n<command>userAdd</command>\n<client>\n<applicationGuid>" . $params["applicationGuid"] . "</applicationGuid>\n<clientRef>" . md5(date("YmdHis")) . "</clientRef>\n</client>\n<request>\n<userId>" . $RegistrantEmailAddress . "</userId>\n<userAccountName>" . $RegistrantFirstName . " " . $RegistrantLastName . "</userAccountName>\n<contacts>\n<contact>\n<firstName>" . $RegistrantFirstName . "</firstName>\n<lastName>" . $RegistrantLastName . "</lastName>\n<emailAddress>" . $RegistrantEmailAddress . "</emailAddress>\n<telephoneNumber>" . $RegistrantPhone . "</telephoneNumber>\n<addressLine1>" . $RegistrantAddress1 . "</addressLine1>\n<addressLine2>" . $RegistrantAddress2 . "</addressLine2>\n<city>" . $RegistrantCity . "</city>";
    if ($RegistrantCountry == "US") {
        $xml_adduser .= "<state>" . $RegistrantStateProvince . "</state>";
    } else {
        $xml_adduser .= "<province>" . $RegistrantStateProvince . "</province>";
    }
    $xml_adduser .= "<postalCode>" . $RegistrantPostalCode . "</postalCode>\n<countryCode>" . $RegistrantCountry . "</countryCode>\n<contactType>Registration</contactType>\n</contact>";
    if ($AdminEmailAddress != $RegistrantEmailAddress) {
        $xml_adduser .= "<contact>\n<firstName>" . $AdminFirstName . "</firstName>\n<lastName>" . $AdminLastName . "</lastName>\n<emailAddress>" . $AdminEmailAddress . "</emailAddress>\n<telephoneNumber>" . $AdminPhone . "</telephoneNumber>\n<addressLine1>" . $AdminAddress1 . "</addressLine1>\n<addressLine2>" . $AdminAddress2 . "</addressLine2>\n<city>" . $AdminCity . "</city>";
        if ($AdminCountry == "US") {
            $xml_adduser .= "<state>" . $AdminStateProvince . "</state>";
        } else {
            $xml_adduser .= "<province>" . $AdminStateProvince . "</province>";
        }
        $xml_adduser .= "<postalCode>" . $AdminPostalCode . "</postalCode>\n<countryCode>" . $AdminCountry . "</countryCode>\n<contactType>Administration</contactType>\n</contact>";
    }
    $xml_adduser .= "\n</contacts>\n</request>\n</serviceRequest>";
    $data = registercom_curlCall($xml_adduser, $params);
    if ($data["serviceResponse"]["status"]["statusCode"]["value"] != "1000" && $data["serviceResponse"]["status"]["statusCode"]["value"] != "1005") {
        $values["error"] = "Could not add or update user account " . $AdminFirstName . " " . $AdminLastName . " with Registrar for the domainTransferIn request of " . $domain;
        return $values;
    }
    $xml_adddomain = "<serviceRequest>\n<command>domainTransferIn</command>\n<client>\n<applicationGuid>" . $params["applicationGuid"] . "</applicationGuid>\n<clientRef>" . md5(date("YmdHis")) . "</clientRef>\n</client>\n<request>\n<userId>" . $AdminEmailAddress . "</userId>\n<domainName>" . $domain . "</domainName>\n<authCode><![CDATA[" . $transfersecret . "]]></authCode>\n<contacts>";
    $xml_adddomain .= "<contact>\n<title>Mr.</title>\n<firstName>" . $RegistrantFirstName . "</firstName>\n<lastName>" . $RegistrantLastName . "</lastName>\n<emailAddress>" . $RegistrantEmailAddress . "</emailAddress>\n<telephoneNumber>" . $RegistrantPhone . "</telephoneNumber>\n<addressLine1>" . $RegistrantAddress1 . "</addressLine1>\n<addressLine2>" . $RegistrantAddress2 . "</addressLine2>\n<city>" . $RegistrantCity . "</city>";
    if ($RegistrantCountry == "US") {
        $xml_adddomain .= "<state>" . $RegistrantStateProvince . "</state>";
    } else {
        $xml_adddomain .= "<province>" . $params["state"] . "</province>";
    }
    $xml_adddomain .= "<postalCode>" . $RegistrantPostalCode . "</postalCode>\n<countryCode>" . $RegistrantCountry . "</countryCode>\n<contactType>Registration</contactType>\n</contact>";
    $xml_adddomain .= "<contact>\n<title>Mr.</title>\n<firstName>" . $AdminFirstName . "</firstName>\n<lastName>" . $AdminLastName . "</lastName>\n<emailAddress>" . $AdminEmailAddress . "</emailAddress>\n<telephoneNumber>" . $AdminPhone . "</telephoneNumber>\n<addressLine1>" . $AdminAddress1 . "</addressLine1>\n<addressLine2>" . $AdminAddress2 . "</addressLine2>\n<city>" . $AdminCity . "</city>";
    if ($AdminCountry == "US") {
        $xml_adddomain .= "<state>" . $AdminStateProvince . "</state>";
    } else {
        $xml_adddomain .= "<province>" . $AdminStateProvince . "</province>";
    }
    $xml_adddomain .= "<postalCode>" . $AdminPostalCode . "</postalCode>\n<countryCode>" . $AdminCountry . "</countryCode>\n<contactType>Administration</contactType>\n</contact>";
    $xml_adddomain .= "</contacts>\n</request>\n</serviceRequest>";
    $data = registercom_curlCall($xml_adddomain, $params);
    print_r($data);
    $domProductId = $data["serviceResponse"]["response"]["productId"]["value"];
    $data = $data["serviceResponse"]["status"]["statusCode"]["value"];
    if ($data != "1000") {
        $values["error"] = "Failed to transfer the domain " . $domain;
        return $values;
    }
    $domain_product_id = registercom_GetProductIdByDomain($domain, $params);
    if ($domProductId != $domain_product_id) {
        $values["error"] = "Failed to transfer the domain " . $domain;
        return $values;
    }
}
function registercom_RenewDomain($params)
{
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domain = $sld . "." . $tld;
    $regperiod = $params["regperiod"];
    $product_id = registercom_GetProductIdByDomain($domain, $params);
    $xml_domainrenew = "<serviceRequest>\n<command>domainRenew</command>\n<client>\n<applicationGuid>" . $params["applicationGuid"] . "</applicationGuid>\n<clientRef>" . md5(date("YmdHis")) . "</clientRef>\n</client>\n<request>\n<productId>" . $product_id . "</productId>\n<term>" . $regperiod . "</term>\n</request>\n</serviceRequest>";
    $data = registercom_curlCall($xml_domainrenew, $params);
    $domProductId = $data["serviceResponse"]["response"]["productId"]["value"];
    $data = $data["serviceResponse"]["status"]["statusCode"]["value"];
    if ($data != "1000") {
        $values["error"] = "Failed to renew the domain " . $domain;
        return $values;
    }
    $domain_product_id = registercom_GetProductIdByDomain($domain, $params);
    if ($domProductId != $domain_product_id) {
        $values["error"] = "Failed to renew the domain " . $domain;
        return $values;
    }
}
function registercom_GetContactDetails($params)
{
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domain = $sld . "." . $tld;
    $xml = "<serviceRequest>\n<command>domainGet</command>\n<client>\n<applicationGuid>" . $params["applicationGuid"] . "</applicationGuid>\n<clientRef>" . md5(date("YmdHis")) . "</clientRef>\n</client>\n<request>\n<page>1</page>\n<domains>\n<domainName>" . $domain . "</domainName>\n</domains>\n</request>\n</serviceRequest>";
    $data = registercom_curlCall($xml, $params);
    $data = $data["serviceResponse"]["response"]["domainGet"]["domain"]["contacts"]["contact"];
    $values["Registrant"]["First Name"] = $data[1]["firstName"]["value"];
    $values["Registrant"]["Last Name"] = $data[1]["lastName"]["value"];
    $values["Admin"]["First Name"] = $data[0]["firstName"]["value"];
    $values["Admin"]["Last Name"] = $data[0]["lastName"]["value"];
    return $values;
}
function registercom_SaveContactDetails($params)
{
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domain = $sld . "." . $tld;
    $currentc_xml = "<serviceRequest>\n<command>domainGet</command>\n<client>\n<applicationGuid>" . $params["applicationGuid"] . "</applicationGuid>\n<clientRef>" . md5(date("YmdHis")) . "</clientRef>\n</client>\n<request>\n<page>1</page>\n<domains>\n<domainName>" . $domain . "</domainName>\n</domains>\n</request>\n</serviceRequest>";
    $curcontacts = registercom_curlCall($currentc_xml, $params);
    $product_id = $curcontacts["serviceResponse"]["response"]["domainGet"]["domain"]["domainInfo"]["productId"]["value"];
    $curcontacts = $curcontacts["serviceResponse"]["response"]["domainGet"]["domain"]["contacts"]["contact"];
    $firstname = $params["contactdetails"]["Registrant"]["First Name"];
    $lastname = $params["contactdetails"]["Registrant"]["Last Name"];
    $adminfirstname = $params["contactdetails"]["Admin"]["First Name"];
    $adminlastname = $params["contactdetails"]["Admin"]["Last Name"];
    $xml_modify = "<serviceRequest>\n<command>domainModify</command>\n<client>\n<applicationGuid>" . $params["applicationGuid"] . "</applicationGuid>\n<clientRef>" . md5(date("YmdHis")) . "</clientRef>\n</client>\n<request>\n<productId>" . $product_id . "</productId>\n<contacts>\n<contact>\n<title>Mr.</title>\n<firstName>" . $adminfirstname . "</firstName>\n<lastName>" . $adminlastname . "</lastName>\n<emailAddress>" . $curcontacts[0]["emailAddress"]["value"] . "</emailAddress>\n<telephoneNumber>" . $curcontacts[0]["telephoneNumber"]["value"] . "</telephoneNumber>\n<addressLine1>" . $curcontacts[0]["addressLine1"]["value"] . "</addressLine1>\n<addressLine2>" . $curcontacts[0]["addressLine2"]["value"] . "</addressLine2>\n<city>" . $curcontacts[0]["city"]["value"] . "</city>";
    if ($curcontacts[0]["countryCode"]["value"] == "US") {
        $xml_modify .= "<state>" . $curcontacts[0]["state"]["value"] . "</state>";
    } else {
        $xml_modify .= "<province>" . $curcontacts[0]["province"]["value"] . "</province>";
    }
    $xml_modify .= "<postalCode>" . $curcontacts[0]["postalCode"]["value"] . "</postalCode>\n<countryCode>" . $curcontacts[0]["countryCode"]["value"] . "</countryCode>\n<contactType>Administration</contactType>\n</contact>\n<contact>\n<title>Mr.</title>\n<firstName>" . $firstname . "</firstName>\n<lastName>" . $lastname . "</lastName>\n<emailAddress>" . $curcontacts[1]["emailAddress"]["value"] . "</emailAddress>\n<telephoneNumber>" . $curcontacts[1]["telephoneNumber"]["value"] . "</telephoneNumber>\n<addressLine1>" . $curcontacts[1]["addressLine1"]["value"] . "</addressLine1>\n<addressLine2>" . $curcontacts[1]["addressLine2"]["value"] . "</addressLine2>\n<city>" . $curcontacts[1]["city"]["value"] . "</city>";
    if ($curcontacts[1]["countryCode"]["value"] == "US") {
        $xml_modify .= "<state>" . $curcontacts[1]["state"]["value"] . "</state>";
    } else {
        $xml_modify .= "<province>" . $curcontacts[1]["province"]["value"] . "</province>";
    }
    $xml_modify .= "<postalCode>" . $curcontacts[1]["postalCode"]["value"] . "</postalCode>\n<countryCode>" . $curcontacts[1]["countryCode"]["value"] . "</countryCode>\n<contactType>Registration</contactType>\n</contact>\n</contacts>\n</request>\n</serviceRequest>";
    $data = registercom_curlCall($xml_modify, $params);
    $data = $data["serviceResponse"]["status"]["statusCode"]["value"];
    if ($data != "1000") {
        $values["error"] = "The requested domain contact changes were NOT accepted by the registrar for the domain " . $domain;
    }
    return $values;
}
function registercom_xml2array($contents, $get_attributes = 1)
{
    if (!$contents) {
        return array();
    }
    $content = trim($content);
    if (strpos($content, "<?xml") === 0 && ($prologCloseStart = strpos($content, "?>"))) {
        $content = substr($content, $prologCloseStart + 2);
    }
    if (!function_exists("xml_parser_create")) {
        return array();
    }
    $parser = xml_parser_create();
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parse_into_struct($parser, $contents, $xml_values);
    xml_parser_free($parser);
    if (!$xml_values) {
        return NULL;
    }
    $xml_array = array();
    $parents = array();
    $opened_tags = array();
    $arr = array();
    $current =& $xml_array;
    foreach ($xml_values as $data) {
        unset($attributes);
        unset($value);
        extract($data);
        $result = "";
        if ($get_attributes) {
            $result = array();
            if (isset($value)) {
                $result["value"] = $value;
            }
            if (isset($attributes)) {
                foreach ($attributes as $attr => $val) {
                    if ($get_attributes == 1) {
                        $result["attr"][$attr] = $val;
                    }
                }
            }
        } else {
            if (isset($value)) {
                $result = $value;
            }
        }
        if ($type == "open") {
            $parent[$level - 1] =& $current;
            if (!is_array($current) || !in_array($tag, array_keys($current))) {
                $current[$tag] = $result;
                $current =& $current[$tag];
            } else {
                if (isset($current[$tag][0])) {
                    array_push($current[$tag], $result);
                } else {
                    $current[$tag] = array($current[$tag], $result);
                }
                $last = count($current[$tag]) - 1;
                $current =& $current[$tag][$last];
            }
        } else {
            if ($type == "complete") {
                if (!isset($current[$tag])) {
                    $current[$tag] = $result;
                } else {
                    if (is_array($current[$tag]) && $get_attributes == 0 || isset($current[$tag][0]) && is_array($current[$tag][0]) && $get_attributes == 1) {
                        array_push($current[$tag], $result);
                    } else {
                        $current[$tag] = array($current[$tag], $result);
                    }
                }
            } else {
                if ($type == "close") {
                    $current =& $parent[$level - 1];
                }
            }
        }
    }
    return $xml_array;
}
function registercom_curlCall($xml, $params)
{
    if ($params["TestMode"]) {
        $url = "https://staging-services.rxportalexpress.com/V1.0/";
    } else {
        $url = "https://services.rxportalexpress.com/V1.0/";
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $xml_data = curl_exec($ch);
    curl_close($ch);
    $tempxml = XMLtoArray($xml);
    $command = $tempxml["SERVICEREQUEST"]["COMMAND"];
    logModuleCall("registercom", $command, $xml, $xml_data);
    return registercom_xml2array($xml_data);
}
function registercom_GetProductIdByDomain($domain, $params)
{
    $xml = "<serviceRequest>\n<command>domainGet</command>\n<client>\n<applicationGuid>" . $params["applicationGuid"] . "</applicationGuid>\n<clientRef>" . md5(date("YmdHis")) . "</clientRef>\n</client>\n<request>\n<page>1</page>\n<domains>\n<domainName>" . $domain . "</domainName>\n</domains>\n</request>\n</serviceRequest>";
    $data = registercom_curlcall($xml, $params);
    return $data["serviceResponse"]["response"]["domainGet"]["domain"]["domainInfo"]["productId"]["value"];
}

?>