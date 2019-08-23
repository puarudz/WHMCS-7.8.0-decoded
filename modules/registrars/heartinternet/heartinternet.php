<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!function_exists("heartinternet_ClientArea")) {
    function heartinternet_ClientArea($params)
    {
        $cltrid = md5(date("YmdHis"));
        $values = array();
        $infoxml = "<?xml version=\"1.0\"?>\n<epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:ext-domain=\"http://www.heartinternet.co.uk/whapi/ext-domain-1.2\" xmlns:domain=\"urn:ietf:params:xml:ns:domain-1.0\">\n<command>\n<info>\n<domain:info>\n<domain:name>" . $params["sld"] . "." . $params["tld"] . "</domain:name>\n</domain:info>\n</info>\n<extension>\n<ext-domain:preAuthenticate/>\n</extension>\n<clTRID>" . $cltrid . "</clTRID>\n</command>\n</epp>";
        $xmldata = heartinternetreg_curlcall($infoxml, "on", $params);
        if (!is_array($xmldata)) {
            return array("error" => $xmldata);
        }
        if (trim($xmldata["epp"]["response"]["result"]["attr"]["code"]) != "1000") {
            $result = "";
        } else {
            $url = $xmldata["epp"]["response"]["resData"]["ext-domain:redirectURL"]["value"];
            $result = "<a href=\"" . $url . "\" target=\"_blank\">Login to Control Panel</a>";
        }
        return $result;
    }
}
function heartinternet_getConfigArray()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "Heart Internet"), "Username" => array("Type" => "text", "Size" => "25", "Description" => "Your Domain Reseller API Username as given at https://customer.heartinternet.co.uk/manage/api"), "Password" => array("Type" => "text", "Size" => "25", "Description" => "Your Domain Reseller API Password as given at https://customer.heartinternet.co.uk/manage/api"), "TestMode" => array("Type" => "yesno", "Description" => "Tick to enable test mode"));
    return $configarray;
}
function heartinternet_RegisterDomain($params)
{
    $cltrid = md5(date("YmdHis"));
    $registrantid = heartinternetreg_createContact($params);
    $values = array();
    if (is_array($registrantid)) {
        $values["error"] .= "Failed to create contact" . safe_serialize($registrantid);
    }
    $domainxml = "<?xml version=\"1.0\"?>\n<epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:ext-domain=\"http://www.heartinternet.co.uk/whapi/ext-domain-1.2\" xmlns:domain=\"urn:ietf:params:xml:ns:domain-1.0\">\n<command>\n<create>\n<domain:create>\n<domain:name>" . $params["sld"] . "." . $params["tld"] . "</domain:name>\n<domain:period unit=\"y\">" . $params["regperiod"] . "</domain:period>\n<domain:registrant>" . $registrantid . "</domain:registrant>\n<domain:authInfo>\n<domain:ext>\n<ext-domain:null/>\n</domain:ext>\n</domain:authInfo>\n</domain:create>\n</create>\n<extension>\n<ext-domain:createExtension>";
    if ($params["idprotection"]) {
        $domainxml .= "<ext-domain:privacy/>";
    }
    $domainxml .= "<ext-domain:registrationMechanism>credits</ext-domain:registrationMechanism>\n<ext-domain:registrationMechanism>basket</ext-domain:registrationMechanism>\n</ext-domain:createExtension>\n</extension>\n<clTRID>" . $cltrid . "</clTRID>\n</command>\n</epp>";
    $xmldata = heartinternetreg_curlcall($domainxml, "off", $params);
    if (trim($xmldata["EPP"]["RESPONSE"]["RESULT"]["MSG"]) != "Command completed successfully") {
        $values["error"] .= $xmldata["EPP"]["RESPONSE"]["RESULT"]["MSG"];
    }
    return $values;
}
function heartinternet_TransferDomain($params)
{
    $cltrid = md5(date("YmdHis"));
    $registrantid = heartinternetreg_createContact($params);
    $values = array();
    if (is_array($registrantid)) {
        $values["error"] .= "Failed to create contact" . safe_serialize($registrantid);
    }
    $transferxml = "<?xml version=\"1.0\"?>\n<epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:domain=\"urn:ietf:params:xml:ns:domain-1.0\" xmlns:ext-domain=\"http://www.heartinternet.co.uk/whapi/ext-domain-1.2\">\n<command>\n<transfer op=\"request\">\n<domain:transfer>\n<domain:name>" . $params["sld"] . "." . $params["tld"] . "</domain:name>\n<domain:authInfo>\n<domain:ext>\n<ext-domain:null/>\n</domain:ext>\n</domain:authInfo>\n</domain:transfer>\n</transfer>\n<extension>\n<ext-domain:transferExtension>\n<ext-domain:registrant>" . $registrantid . "</ext-domain:registrant>\n<ext-domain:keepNameservers/>\n</ext-domain:transferExtension>\n</extension>\n<clTRID>" . $cltrid . "</clTRID>\n</command>\n</epp>";
    $xmldata = heartinternetreg_curlcall($transferxml, "on", $params);
    if (trim($xmldata["epp"]["response"]["result"]["attr"]["code"]) != "1001") {
        $values["error"] .= $xmldata["epp"]["response"]["result"]["msg"]["value"];
    }
    return $values;
}
function heartinternet_RenewDomain($params)
{
    $regperiod = $params["regperiod"];
    $cltrid = md5(date("YmdHis"));
    $values = array();
    $current_expiry_date = mysql_fetch_assoc(select_query("tbldomains", "expirydate", array("domain" => $params["sld"] . "." . $params["tld"])));
    $current_expiry_date = $current_expiry_date["expirydate"];
    $cltrid = md5(date("YmdHis") . date("sHdmiY"));
    $renewxml = "<?xml version=\"1.0\"?>\n<epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:domain=\"urn:ietf:params:xml:ns:domain-1.0\" xmlns:ext-domain=\"http://www.heartinternet.co.uk/whapi/ext-domain-1.2\">\n<command>\n<renew>\n<domain:renew>\n<domain:name>" . $params["sld"] . "." . $params["tld"] . "</domain:name>\n<domain:curExpDate>" . $current_expiry_date . "</domain:curExpDate>\n<domain:period unit=\"y\">" . $regperiod . "</domain:period>\n</domain:renew>\n</renew>\n<extension>\n<ext-domain:renewExtension>\n<ext-domain:registrationMechanism>credits</ext-domain:registrationMechanism>\n<ext-domain:registrationMechanism>basket</ext-domain:registrationMechanism>\n</ext-domain:renewExtension>\n</extension>\n<clTRID>" . $cltrid . "</clTRID>\n</command>\n</epp>";
    $xmldata = heartinternetreg_curlcall($renewxml, "on", $params);
    if (trim($xmldata["epp"]["response"]["result"]["attr"]["code"]) != "1000") {
        $values["error"] .= $xmldata["epp"]["response"]["result"]["msg"]["value"];
    }
    return $values;
}
function heartinternet_GetNameservers($params)
{
    $cltrid = md5(date("YmdHis"));
    $values = array();
    $infoxml = "<?xml version=\"1.0\"?>\n<epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:ext-domain=\"http://www.heartinternet.co.uk/whapi/ext-domain-1.2\" xmlns:domain=\"urn:ietf:params:xml:ns:domain-1.0\">\n<command>\n<info>\n<domain:info>\n<domain:name>" . $params["sld"] . "." . $params["tld"] . "</domain:name>\n</domain:info>\n</info>\n<extension>\n<ext-domain:info xmlns:ext-domain=\"http://www.heartinternet.co.uk/whapi/ext-domain-1.2\"/>\n</extension>\n<clTRID>" . $cltrid . "</clTRID>\n</command>\n</epp>";
    $xmldata = heartinternetreg_curlcall($infoxml, "on", $params);
    if (!is_array($xmldata)) {
        return array("error" => $xmldata);
    }
    if (trim($xmldata["epp"]["response"]["result"]["attr"]["code"]) != "1000") {
        $values["error"] .= $xmldata["epp"]["response"]["result"]["msg"]["value"];
    } else {
        $values["ns1"] = $xmldata["epp"]["response"]["resData"]["domain:infData"]["domain:ns"]["domain:hostAttr"][0]["domain:hostName"]["value"];
        $values["ns2"] = $xmldata["epp"]["response"]["resData"]["domain:infData"]["domain:ns"]["domain:hostAttr"][1]["domain:hostName"]["value"];
        $values["ns3"] = $xmldata["epp"]["response"]["resData"]["domain:infData"]["domain:ns"]["domain:hostAttr"][2]["domain:hostName"]["value"];
        $values["ns4"] = $xmldata["epp"]["response"]["resData"]["domain:infData"]["domain:ns"]["domain:hostAttr"][3]["domain:hostName"]["value"];
        $values["ns5"] = $xmldata["epp"]["response"]["resData"]["domain:infData"]["domain:ns"]["domain:hostAttr"][4]["domain:hostName"]["value"];
    }
    return $values;
}
function heartinternet_SaveNameservers($params)
{
    $cltrid = md5(date("YmdHis"));
    $values = array();
    $heartns = heartinternet_getnameservers($params);
    $addns = $removens = array();
    for ($i = 1; $i <= 5; $i++) {
        if (!in_array($params["ns" . $i], $heartns)) {
            $addns[] = $params["ns" . $i];
        }
    }
    foreach ($heartns as $v) {
        if (!in_array($v, $params)) {
            $removens[] = $v;
        }
    }
    $addnsxml = $removensxml = "";
    if (count($addns)) {
        $addnsxml = "<domain:add><domain:ns>";
        foreach ($addns as $ns) {
            $addnsxml .= "<domain:hostAttr><domain:hostName>" . $ns . "</domain:hostName></domain:hostAttr>";
        }
        $addnsxml .= "</domain:ns></domain:add>";
    }
    if (count($removens)) {
        $removensxml = "<domain:rem><domain:ns>";
        foreach ($removens as $ns) {
            $removensxml .= "<domain:hostAttr><domain:hostName>" . $ns . "</domain:hostName></domain:hostAttr>";
        }
        $removensxml .= "</domain:ns></domain:rem>";
    }
    if ($addnsxml) {
        $updatexml = "<?xml version=\"1.0\"?>\n<epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:ext-domain=\"http://www.heartinternet.co.uk/whapi/ext-domain-1.3\" xmlns:domain=\"urn:ietf:params:xml:ns:domain-1.0\">\n<command>\n<update>\n<domain:update>\n<domain:name>" . $params["sld"] . "." . $params["tld"] . "</domain:name>\n" . $addnsxml . "\n</domain:update>\n</update>\n<clTRID>" . $cltrid . "</clTRID>\n</command>\n</epp>";
        $xmldata = heartinternetreg_curlcall($updatexml, "on", $params);
        if (trim($xmldata["epp"]["response"]["result"]["attr"]["code"]) != "1000") {
            $values["error"] .= $xmldata["epp"]["response"]["result"]["msg"]["value"];
        }
    }
    if ($removensxml) {
        $updatexml = "<?xml version=\"1.0\"?>\n<epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:ext-domain=\"http://www.heartinternet.co.uk/whapi/ext-domain-1.3\" xmlns:domain=\"urn:ietf:params:xml:ns:domain-1.0\">\n<command>\n<update>\n<domain:update>\n<domain:name>" . $params["sld"] . "." . $params["tld"] . "</domain:name>\n" . $removensxml . "\n</domain:update>\n</update>\n<clTRID>" . $cltrid . "</clTRID>\n</command>\n</epp>";
        $xmldata = heartinternetreg_curlcall($updatexml, "on", $params);
        if (trim($xmldata["epp"]["response"]["result"]["attr"]["code"]) != "1000") {
            $values["error"] .= $xmldata["epp"]["response"]["result"]["msg"]["value"];
        }
    }
    return $values;
}
function heartinternet_Sync($params)
{
    $cltrid = md5(date("YmdHis") . rand(1000, 9999));
    $infoxml = "<?xml version=\"1.0\"?>\n<epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:ext-domain=\"http://www.heartinternet.co.uk/whapi/ext-domain-1.2\" xmlns:domain=\"urn:ietf:params:xml:ns:domain-1.0\">\n<command>\n<info>\n<domain:info>\n<domain:name>" . $params["domain"] . "</domain:name>\n</domain:info>\n</info>\n<extension>\n<ext-domain:info xmlns:ext-domain=\"http://www.heartinternet.co.uk/whapi/ext-domain-1.2\"/>\n</extension>\n<clTRID>" . $cltrid . "</clTRID>\n</command>\n</epp>";
    $xmldata = heartinternetreg_curlcall($infoxml, "on", $params);
    $rtn = array();
    if (trim($xmldata["epp"]["response"]["result"]["attr"]["code"]) != "1000") {
        $rtn["error"] = $xmldata["epp"]["response"]["result"]["msg"]["value"];
    } else {
        $expirydate = $xmldata["epp"]["response"]["resData"]["domain:infData"]["domain:exDate"]["value"];
        if (trim($expirydate)) {
            $expirydate = substr($expirydate, 0, 10);
            $rtn["active"] = true;
            $rtn["expirydate"] = $expirydate;
        }
    }
    return $rtn;
}
function heartinternetreg_curlcall($xml, $verbose = "off", $params)
{
    if (!class_exists("HeartInternetReg_API")) {
        require ROOTDIR . "/modules/registrars/heartinternet/heartinternet.class.php";
    }
    $hi_api = new HeartInternetReg_API();
    if ($params["TestMode"] == "on") {
        $hi_api->connect(true);
    } else {
        $hi_api->connect();
    }
    $objects = array("urn:ietf:params:xml:ns:contact-1.0", "urn:ietf:params:xml:ns:domain-1.0", "http://www.heartinternet.co.uk/whapi/null-1.1");
    $extensions = array("http://www.heartinternet.co.uk/whapi/ext-domain-1.2", "http://www.heartinternet.co.uk/whapi/ext-contact-1.0", "http://www.heartinternet.co.uk/whapi/ext-host-1.0", "http://www.heartinternet.co.uk/whapi/ext-null-1.0", "http://www.heartinternet.co.uk/whapi/ext-whapi-1.0");
    try {
        $hi_api->logIn($params["Username"], $params["Password"], $objects, $extensions);
    } catch (Exception $e) {
        return "Caught exception: " . $e->getMessage();
    }
    $data = $hi_api->sendMessage($xml, true);
    logModuleCall("heartinternet", $action, $xml, $data, "", array($params["Username"], $params["Password"]));
    if ($verbose == "on") {
        return heartinternetreg_xml2array($data);
    }
    return XMLtoArray($data);
}
function heartinternetreg_createContact($params)
{
    $countries = new WHMCS\Utility\Country();
    $cltrid = md5(date("YmdHis"));
    $xml = "<?xml version=\"1.0\"?><epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:ext-contact=\"http://www.heartinternet.co.uk/whapi/ext-contact-1.0\" xmlns:contact=\"urn:ietf:params:xml:ns:contact-1.0\">\n<command>\n<create>\n<contact:create>\n<contact:id>IGNORED</contact:id>\n<contact:postalInfo type=\"loc\">\n<contact:name>" . $params["firstname"] . " " . $params["lastname"] . "</contact:name>\n<contact:addr>\n<contact:street>" . $params["address1"] . "</contact:street>\n<contact:city>" . $params["city"] . "</contact:city>\n<contact:sp>" . $params["state"] . "</contact:sp>\n<contact:pc>" . $params["postcode"] . "</contact:pc>\n<contact:cc>" . $params["country"] . "</contact:cc>\n</contact:addr>\n</contact:postalInfo>\n<contact:voice>+" . $params["phonecc"] . "." . preg_replace("/[^0-9]/", "", $params["phonenumber"]) . "</contact:voice>\n<contact:email>" . $params["email"] . "</contact:email>\n<contact:authInfo>\n<contact:ext>\n<ext-contact:null/>\n</contact:ext>\n</contact:authInfo>\n</contact:create>\n</create>\n<extension>\n  <ext-contact:createExtension>\n    <ext-contact:person>\n      <ext-contact:salutation gender=\"male\">Mr</ext-contact:salutation>\n      <ext-contact:surname>" . $params["lastname"] . "</ext-contact:surname>\n      <ext-contact:otherNames>" . $params["firstname"] . "</ext-contact:otherNames>\n      <ext-contact:dateOfBirth>1980-12-20</ext-contact:dateOfBirth>\n    </ext-contact:person>\n    <ext-contact:telephone type=\"mobile\">+" . $params["phonecc"] . "." . preg_replace("/[^0-9]/", "", $params["phonenumber"]) . "</ext-contact:telephone>\n  </ext-contact:createExtension>\n</extension>\n<clTRID>" . $cltrid . "</clTRID>\n</command>\n</epp>";
    $xmldata = heartinternetreg_curlcall($xml, "off", $params);
    if (trim($xmldata["EPP"]["RESPONSE"]["RESULT"]["MSG"]) == "Command completed successfully") {
        $registrantid = trim($xmldata["EPP"]["RESPONSE"]["RESDATA"]["CONTACT:CREDATA"]["CONTACT:ID"]);
    } else {
        $registrantid = $xmldata["error"];
    }
    return $registrantid;
}
function heartinternetreg_xml2array($contents, $get_attributes = 1)
{
    if (!$contents) {
        return array();
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

?>