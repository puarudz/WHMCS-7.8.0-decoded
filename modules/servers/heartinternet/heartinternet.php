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
        global $_LANG;
        $cltrid = md5(date("YmdHis"));
        $userloginxml = "<?xml version=\"1.0\"?>\n<epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:package=\"http://www.heartinternet.co.uk/whapi/package-1.4\">\n  <command>\n    <info>\n      <package:info>\n        <package:id>" . $params["domain"] . "</package:id>\n      </package:info>\n    </info>\n    <extension>\n      <ext-package:preAuthenticate xmlns:ext-package=\"http://www.heartinternet.co.uk/whapi/ext-package-1.1\"/>\n    </extension>\n    <clTRID>" . $cltrid . "</clTRID>\n  </command>\n</epp>";
        $xmldata = heartinternet_curlcall($userloginxml, "on", $params);
        if (!is_array($xmldata)) {
            return $xmldata;
        }
        if (trim($xmldata["epp"]["response"]["result"]["attr"]["code"]) != "1000") {
            $result = "";
        } else {
            $url = $xmldata["epp"]["response"]["resData"]["ext-package:redirectURL"]["value"];
            $result = "<a href=\"" . $url . "\" target=\"_blank\">" . $_LANG["heartinternetlogin"] . "</a>";
        }
        return $result;
    }
}
function heartinternet_MetaData()
{
    return array("DisplayName" => "Heart Internet", "APIVersion" => "1.0");
}
function heartinternet_ConfigOptions()
{
    $configarray = array("Test Mode" => array("Type" => "yesno", "Description" => "Tick to enable test mode"), "Package ID" => array("Type" => "text", "Size" => "25"));
    return $configarray;
}
function heartinternet_CreateAccount($params)
{
    $cltrid = md5(date("YmdHis"));
    $createxml = "<?xml version=\"1.0\"?>\n<epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:package=\"http://www.heartinternet.co.uk/whapi/package-1.4\">\n  <command>\n    <create>\n      <package:create>\n        <package:domainName>" . $params["domain"] . "</package:domainName>\n        <package:emailAddress name=\"" . $params["clientsdetails"]["firstname"] . " " . $params["clientsdetails"]["lastname"] . "\">" . $params["clientsdetails"]["email"] . "</package:emailAddress>\n        <package:type>" . $params["configoption2"] . "</package:type>\n      </package:create>\n    </create>\n    <clTRID>" . $cltrid . "</clTRID>\n  </command>\n</epp>";
    $xmldata = heartinternet_curlcall($createxml, "on", $params);
    if (!is_array($xmldata)) {
        return $xmldata;
    }
    if (trim($xmldata["epp"]["response"]["result"]["attr"]["code"]) != "1000") {
        $result = $xmldata["epp"]["response"]["result"]["msg"]["value"];
    } else {
        $username = $xmldata["epp"]["response"]["resData"]["package:creData"]["package:id"]["value"];
        $password = $xmldata["epp"]["response"]["resData"]["package:creData"]["package:passwordSet"]["package:password"][0]["value"];
        $params["model"]->serviceProperties->save(array("username" => $username, "password" => $password));
        $result = "success";
    }
    return $result;
}
function heartinternet_TerminateAccount($params)
{
    $cltrid = md5(date("YmdHis"));
    $deletexml = "<?xml version=\"1.0\"?>\n<epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:package=\"http://www.heartinternet.co.uk/whapi/package-1.4\">\n  <command>\n    <delete>\n      <package:delete>\n        <package:id>" . $params["username"] . "</package:id>\n      </package:delete>\n    </delete>\n    <clTRID>" . $cltrid . "</clTRID>\n  </command>\n</epp>";
    $xmldata = heartinternet_curlcall($deletexml, "on", $params);
    if (!is_array($xmldata)) {
        return $xmldata;
    }
    if (trim($xmldata["epp"]["response"]["result"]["attr"]["code"]) != "1000") {
        $result = $xmldata["epp"]["response"]["result"]["msg"]["value"];
    } else {
        $result = "success";
    }
    return $result;
}
function heartinternet_SuspendAccount($params)
{
    $cltrid = md5(date("YmdHis"));
    $suspendxml = "<?xml version=\"1.0\"?>\n<epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:package=\"http://www.heartinternet.co.uk/whapi/package-1.4\">\n  <command>\n    <update>\n      <package:update>\n        <package:id>" . $params["username"] . "</package:id>\n        <package:add>\n          <package:status s=\"inactive\"/>\n        </package:add>\n      </package:update>\n    </update>\n    <clTRID>" . $cltrid . "</clTRID>\n  </command>\n</epp>";
    $xmldata = heartinternet_curlcall($suspendxml, "on", $params);
    if (!is_array($xmldata)) {
        return $xmldata;
    }
    if (trim($xmldata["epp"]["response"]["result"]["attr"]["code"]) != "1000") {
        $result = $xmldata["epp"]["response"]["result"]["msg"]["value"];
    } else {
        $result = "success";
    }
    return $result;
}
function heartinternet_UnsuspendAccount($params)
{
    $cltrid = md5(date("YmdHis"));
    $unsuspendxml = "<?xml version=\"1.0\"?>\n<epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:package=\"http://www.heartinternet.co.uk/whapi/package-1.4\">\n  <command>\n    <update>\n      <package:update>\n        <package:id>" . $params["username"] . "</package:id>\n        <package:rem>\n          <package:status s=\"inactive\"/>\n        </package:rem>\n      </package:update>\n    </update>\n    <clTRID>" . $cltrid . "</clTRID>\n  </command>\n</epp>";
    $xmldata = heartinternet_curlcall($unsuspendxml, "on", $params);
    if (!is_array($xmldata)) {
        return $xmldata;
    }
    if (trim($xmldata["epp"]["response"]["result"]["attr"]["code"]) != "1000") {
        $result = $xmldata["epp"]["response"]["result"]["msg"]["value"];
    } else {
        $result = "success";
    }
    return $result;
}
function heartinternet_ChangePassword($params)
{
    $cltrid = md5(date("YmdHis"));
    $chgpwdxml = "<?xml version=\"1.0\"?>\n<epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:package=\"http://www.heartinternet.co.uk/whapi/package-1.4\">\n  <command>\n    <update>\n      <package:update>\n        <package:id>" . $params["username"] . "</package:id>\n        <package:chg>\n          <package:password type=\"control-panel\"/>\n        </package:chg>\n      </package:update>\n    </update>\n    <clTRID>" . $cltrid . "</clTRID>\n  </command>\n</epp>";
    $xmldata = heartinternet_curlcall($chgpwdxml, "on", $params);
    if (!is_array($xmldata)) {
        return $xmldata;
    }
    if (trim($xmldata["epp"]["response"]["result"]["attr"]["code"]) != "1000") {
        $result = $xmldata["epp"]["response"]["result"]["msg"]["value"];
    } else {
        $oldpassword = $params["password"];
        $newpassword = $xmldata["epp"]["response"]["resData"]["package:password"]["value"];
        if ($newpassword != $oldpassword) {
            $params["model"]->serviceProperties->save(array("password" => $newpassword));
        }
        $result = "success";
    }
    return $result;
}
function heartinternet_ChangePackage($params)
{
    $cltrid = md5(date("YmdHis"));
    $chgpkgxml = "<?xml version=\"1.0\"?>\n<epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:package=\"http://www.heartinternet.co.uk/whapi/package-1.4\">\n  <command>\n    <update>\n      <package:update>\n        <package:id>" . $params["username"] . "</package:id>\n        <package:chg>\n          <package:type>" . $params["configoption2"] . "</package:type>\n        </package:chg>\n      </package:update>\n    </update>\n    <clTRID>" . $cltrid . "</clTRID>\n  </command>\n</epp>";
    $xmldata = heartinternet_curlcall($chgpkgxml, "on", $params);
    if (!is_array($xmldata)) {
        return $xmldata;
    }
    if (trim($xmldata["epp"]["response"]["result"]["attr"]["code"]) != "1000") {
        $result = $xmldata["epp"]["response"]["result"]["msg"]["value"];
    } else {
        $result = "success";
    }
    return $result;
}
function heartinternet_LoginLink($params)
{
    return heartinternet_ClientArea($params);
}
function heartinternet_curlcall($xml, $verbose = "off", $params)
{
    if (!class_exists("HeartInternet_API")) {
        require ROOTDIR . "/modules/servers/heartinternet/heartinternet.class.php";
    }
    $hi_api = new HeartInternet_API();
    if ($params["configoption1"] == "on") {
        $hi_api->connect(true);
    } else {
        $hi_api->connect();
    }
    $objects = array("http://www.heartinternet.co.uk/whapi/null-1.1", "http://www.heartinternet.co.uk/whapi/package-1.4");
    $extensions = array("http://www.heartinternet.co.uk/whapi/ext-package-1.1", "http://www.heartinternet.co.uk/whapi/ext-whapi-1.0");
    try {
        $hi_api->logIn($params["serverusername"], $params["serverpassword"], $objects, $extensions);
    } catch (Exception $e) {
        logModuleCall("heartinternet", $params["action"], $xml, $e->getMessage());
        return "Caught exception: " . $e->getMessage();
    }
    $data = $hi_api->sendMessage($xml, true);
    $retxml = $verbose == "on" ? heartinternet_xml2array($data) : XMLtoArray($data);
    logModuleCall("heartinternet", $params["action"], $xml, $data, $retxml);
    return $retxml;
}
function heartinternet_xml2array($contents, $get_attributes = 1)
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