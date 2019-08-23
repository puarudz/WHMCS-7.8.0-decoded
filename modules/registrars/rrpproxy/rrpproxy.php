<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function rrpproxy_getConfigArray()
{
    $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "RRPProxy"), "Username" => array("Type" => "text", "Size" => "20", "Description" => ""), "Password" => array("Type" => "password", "Size" => "20", "Description" => ""), "TestMode" => array("Type" => "yesno"));
    return $configarray;
}
function rrpproxy_call($params, $request)
{
    $url = "https://api.rrpproxy.net/api/call.cgi?";
    if ($params["TestMode"]) {
        $url = "https://api-ote.rrpproxy.net/api/call.cgi?";
    }
    if (is_array($request)) {
        $query_string = "";
        foreach ($request as $k => $v) {
            $query_string .= $k . "=" . urlencode($v) . "&";
        }
    } else {
        $query_string = $request;
    }
    $url .= "s_login=" . urlencode($params["Username"]) . "&s_pw=" . urlencode($params["Password"]) . "&" . $query_string;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 100);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $retval = curl_exec($ch);
    if (curl_errno($ch)) {
        $retval = "CURL Error: " . curl_errno($ch) . " - " . curl_error($ch);
    }
    curl_close($ch);
    $lines = explode("\n", $retval);
    foreach ($lines as $tempvalue) {
        $tempvalue = explode("=", $tempvalue);
        $result[trim($tempvalue[0])] = trim($tempvalue[1]);
    }
    if (substr($retval, 0, 4) == "CURL") {
        $result["code"] = "999";
        $result["description"] = $retval;
    } else {
        if (!$retval) {
            $result["code"] = "998";
            $result["description"] = "An unhandled exception occurred";
        }
    }
    $action = explode("&", $query_string);
    $action = $action[0];
    $action = str_replace("command=", "", $action);
    logModuleCall("rrpproxy", $action, $url, $retval, $result, array($params["Username"], $params["Password"]));
    return $result;
}
function rrpproxy_GetNameservers($params)
{
    $domain = $params["sld"] . "." . $params["tld"];
    $result = rrpproxy_call($params, "command=StatusDomain&domain=" . $domain);
    if ($result["code"] == "200") {
        $values["ns1"] = $result["property[nameserver][0]"];
        $values["ns2"] = $result["property[nameserver][1]"];
        $values["ns3"] = $result["property[nameserver][2]"];
        $values["ns4"] = $result["property[nameserver][3]"];
        $values["ns5"] = $result["property[nameserver][4]"];
    } else {
        $values["error"] = $result["description"];
    }
    return $values;
}
function rrpproxy_SaveNameservers($params)
{
    $domain = $params["sld"] . "." . $params["tld"];
    $result = rrpproxy_call($params, "command=ModifyDomain&domain=" . $domain . "&nameserver0=" . $params["ns1"] . "&nameserver1=" . $params["ns2"] . "&nameserver2=" . $params["ns3"] . "&nameserver3=" . $params["ns4"] . "&nameserver4=" . $params["ns5"]);
    if ($result["code"] != "200") {
        $values["error"] = $result["description"];
    }
    return $values;
}
function rrpproxy_RegisterDomain($params)
{
    $result = rrpproxy_call($params, "command=AddContact&firstname=" . urlencode($params["firstname"]) . "&lastname=" . urlencode($params["lastname"]) . "&street=" . urlencode($params["address1"]) . "&city=" . urlencode($params["city"]) . "&zip=" . urlencode($params["postcode"]) . "&country=" . urlencode($params["country"]) . "&phone=" . urlencode($params["phonenumber"]) . "&email=" . urlencode($params["email"]) . "&organization=" . urlencode($params["companyname"]) . "&state=" . urlencode($params["state"]));
    if ($result["code"] == "200") {
        $contactid = $result["property[contact][0]"];
        $result = rrpproxy_call($params, "command=AddContact&firstname=" . urlencode($params["firstname"]) . "&lastname=" . urlencode($params["lastname"] . "Admin") . "&street=" . urlencode($params["address1"]) . "&city=" . urlencode($params["city"]) . "&zip=" . urlencode($params["postcode"]) . "&country=" . urlencode($params["country"]) . "&phone=" . urlencode($params["phonenumber"]) . "&email=" . urlencode($params["email"]) . "&organization=" . urlencode($params["companyname"]) . "&state=" . urlencode($params["state"]));
        if ($result["code"] == "200") {
            $admincontactid = $result["property[contact][0]"];
            $domain = $params["sld"] . "." . $params["tld"];
            $postfields = array();
            $postfields["command"] = "AddDomain";
            $postfields["domain"] = $domain;
            $postfields["period"] = $params["regperiod"];
            $postfields["ownercontact0"] = $contactid;
            $postfields["admincontact0"] = $contactid;
            $postfields["techcontact0"] = $contactid;
            $postfields["billingcontact0"] = $contactid;
            $postfields["nameserver0"] = $params["ns1"];
            $postfields["nameserver1"] = $params["ns2"];
            if ($params["ns3"]) {
                $postfields["nameserver2"] = $params["ns3"];
            }
            if ($params["ns4"]) {
                $postfields["nameserver3"] = $params["ns4"];
            }
            if ($params["ns5"]) {
                $postfields["nameserver4"] = $params["ns5"];
            }
            if (preg_match("/ca\$/i", $params["tld"])) {
                $legaltype = $params["additionalfields"]["Legal Type"];
                if ($legaltype == "Corporation") {
                    $legaltype = "CCO";
                }
                if ($legaltype == "Canadian Citizen") {
                    $legaltype = "CCT";
                }
                if ($legaltype == "Permanent Resident of Canada") {
                    $legaltype = "RES";
                }
                if ($legaltype == "Government") {
                    $legaltype = "GOV";
                }
                if ($legaltype == "Canadian Educational Institution") {
                    $legaltype = "EDU";
                }
                if ($legaltype == "Canadian Unincorporated Association") {
                    $legaltype = "ASS";
                }
                if ($legaltype == "Canadian Hospital") {
                    $legaltype = "HOP";
                }
                if ($legaltype == "Partnership Registered in Canada") {
                    $legaltype = "PRT";
                }
                if ($legaltype == "Trade-mark registered in Canada") {
                    $legaltype = "TDM";
                }
                if ($legaltype == "Canadian Trade Union") {
                    $legaltype = "TRD";
                }
                if ($legaltype == "Canadian Political Party") {
                    $legaltype = "PLT";
                }
                if ($legaltype == "Canadian Library Archive or Museum") {
                    $legaltype = "LAM";
                }
                if ($legaltype == "Trust established in Canada") {
                    $legaltype = "TRS";
                }
                if ($legaltype == "Aboriginal Peoples") {
                    $legaltype = "ABO";
                }
                if ($legaltype == "Legal Representative of a Canadian Citizen") {
                    $legaltype = "LGR";
                }
                if ($legaltype == "Official mark registered in Canada") {
                    $legaltype = "OMK";
                }
                $postfields["X-CA-LEGAL-TYPE"] = $legaltype;
                $postfields["X-CA-TRADEMARK:"] = "0";
            }
            if (preg_match("/tel\$/i", $params["tld"])) {
                $legaltype = $params["additionalfields"]["Legal Type"];
                if ($legaltype == "Legal Person") {
                    $legaltype = "Legal";
                } else {
                    $legaltype = "Natural";
                }
                $whoisoptout = $params["additionalfields"]["WHOIS Opt-out"];
                $whoisoptout = $whoisoptout ? "1" : "0";
                $postfields["X-TEL-PUBLISH-WHOIS"] = $whoisoptout;
                $postfields["X-TEL-WHOISTYPE"] = $legaltype;
            }
            if (preg_match("/it\$/i", $params["tld"])) {
                $personaldata = $params["additionalfields"]["Publish Personal Data"];
                if (!empty($personaldata)) {
                    $postfields["X-IT-CONSENTFORPUBLISHING"] = "1";
                } else {
                    $postfields["X-IT-CONSENTFORPUBLISHING"] = "0";
                }
                $acceptsec3 = $params["additionalfields"]["Accept Section 3 of .IT registrar contract"];
                if (!empty($acceptsec3)) {
                    $postfields["X-IT-SECT3-LIABILITY"] = "1";
                } else {
                    $postfields["X-IT-SECT3-LIABILITY"] = "0";
                }
                $acceptsec5 = $params["additionalfields"]["Accept Section 5 of .IT registrar contract"];
                if (!empty($acceptsec5)) {
                    $postfields["X-IT-SECT5-PERSONAL-DATA-FOR-REGISTRATION"] = "1";
                } else {
                    $postfields["X-IT-SECT5-PERSONAL-DATA-FOR-REGISTRATION"] = "0";
                }
                $acceptsec6 = $params["additionalfields"]["Accept Section 6 of .IT registrar contract"];
                if (!empty($acceptsec6)) {
                    $postfields["X-IT-SECT6-PERSONAL-DATA-FOR-DIFFUSION"] = "1";
                } else {
                    $postfields["X-IT-SECT6-PERSONAL-DATA-FOR-DIFFUSION"] = "0";
                }
                $acceptsec7 = $params["additionalfields"]["Accept Section 7 of .IT registrar contract"];
                if (!empty($acceptsec6)) {
                    $postfields["X-IT-SECT7-EXPLICIT-ACCEPTANCE"] = "1";
                } else {
                    $postfields["X-IT-SECT7-EXPLICIT-ACCEPTANCE"] = "0";
                }
                $taxid = $params["additionalfields"]["Tax ID"];
                $postfields["X-IT-PIN"] = $taxid;
                $legaltype = $params["additionalfields"]["Legal Type"];
                if ($legaltype == "Italian and foreign natural persons") {
                    $legaltype = "1";
                }
                if ($legaltype == "Companies/one man companies") {
                    $legaltype = "2";
                }
                if ($legaltype == "Freelance workers/professionals") {
                    $legaltype = "3";
                }
                if ($legaltype == "non-profit organizations") {
                    $legaltype = "4";
                }
                if ($legaltype == "public organizations") {
                    $legaltype = "5";
                }
                if ($legaltype == "other subjects") {
                    $legaltype = "6";
                }
                if ($legaltype == "non natural foreigners") {
                    $legaltype = "7";
                }
                $postfields["X-IT-ENTITY-TYPE"] = $legaltype;
            }
            if (preg_match("/us\$/i", $params["tld"])) {
                $nexuspurpose = $params["additionalfields"]["Application Purpose"];
                if ($nexuspurpose == "Business use for profit") {
                    $nexuspurpose = "P1";
                }
                if ($nexuspurpose == "Non-profit business") {
                    $nexuspurpose = "P2";
                }
                if ($nexuspurpose == "Club,Association") {
                    $nexuspurpose = "P2";
                }
                if ($nexuspurpose == "Religious Organization") {
                    $nexuspurpose = "P2";
                }
                if ($nexuspurpose == "Personal Use") {
                    $nexuspurpose = "P3";
                }
                if ($nexuspurpose == "Educational purposes") {
                    $nexuspurpose = "P4";
                }
                if ($nexuspurpose == "Government purposes") {
                    $nexuspurpose = "P5";
                }
                $postfields["X-US-NEXUS-APPPURPOSE"] = $nexuspurpose;
                $postfields["X-US-NEXUS-CATEGORY"] = $params["additionalfields"]["Nexus Category"];
            }
            if (preg_match("/es\$/i", $params["tld"])) {
                $postfields["X-ES-ACCEPT-SPECIAL-TAC"] = "0";
            }
            if (preg_match("/de\$/i", $params["tld"])) {
                $postfields["X-DE-ACCEPT-TRUSTEE-TAC"] = "0";
            }
            if (preg_match("/es\$/i", $params["tld"])) {
                $idformtype = $params["additionalfields"]["ID Form Type"];
                if ($idformtype == "Other Identification") {
                    $idformtype = "0";
                }
                if ($idformtype == "Tax Identification Number") {
                    $idformtype = "1";
                }
                if ($idformtype == "Tax Identification Code") {
                    $idformtype = "2";
                }
                if ($idformtype == "Foreigner Identification Number") {
                    $idformtype = "3";
                }
                $postfields["X-ES-REGISTRANT-TIPO-IDENTIFICACION"] = $idformtype;
                $postfields["X-ES-REGISTRANT-IDENTIFICACION "] = $params["additionalfields"]["ID Form Number"];
            }
            if (preg_match("/eu\$/i", $params["tld"])) {
                $postfields["X-EU-ACCEPT-TRUSTEE-TAC"] = "0";
            }
            $result = rrpproxy_call($params, $postfields);
            if ($result["code"] != "200") {
                $values["error"] = $result["description"];
            }
            return $values;
        }
        $values["error"] = $result["description"];
        return $values;
    }
    $values["error"] = $result["description"];
    return $values;
}
function rrpproxy_TransferDomain($params)
{
    $domain = $params["sld"] . "." . $params["tld"];
    $result = rrpproxy_call($params, "command=TransferDomain&domain=" . $domain . "&auth=" . $params["transfersecret"] . "&action=REQUEST");
    if ($result["code"] != "200") {
        $values["error"] = $result["description"];
    }
    return $values;
}
function rrpproxy_RenewDomain($params)
{
    $domain = $params["sld"] . "." . $params["tld"];
    $result = select_query("tbldomains", "expirydate", array("id" => $params["domainid"]));
    $data = mysql_fetch_array($result);
    $expirydate = $data["expirydate"];
    $expirydate = explode("-", $expirydate);
    $expyear = $expirydate[0];
    $result = rrpproxy_call($params, "command=RenewDomain&domain=" . $domain . "&period=" . $params["regperiod"] . "&expiration=" . $expyear);
    if ($result["code"] != "200") {
        $values["error"] = $result["description"];
    }
    return $values;
}
function rrpproxy_GetContactDetails($params)
{
    $domain = $params["sld"] . "." . $params["tld"];
    $result = rrpproxy_call($params, "command=StatusDomain&domain=" . $domain);
    $ownercontact = $result["property[owner contact][0]"];
    $admincontact = $result["property[admin contact][0]"];
    $result = rrpproxy_call($params, "command=StatusContact&contact=" . $ownercontact);
    if ($result["code"] == "200") {
        $values["Owner"] = array("First Name" => $result["property[first name][0]"], "Last Name" => $result["property[last name][0]"], "Organisation" => $result["property[organization][0]"], "Street" => $result["property[street][0]"], "City" => $result["property[city][0]"], "State" => $result["property[state][0]"], "Zip" => $result["property[zip][0]"], "Country" => $result["property[country][0]"], "Phone" => $result["property[phone][0]"], "Fax" => $result["property[fax][0]"], "Email" => $result["property[email][0]"]);
    } else {
        $values["error"] = $result["description"];
    }
    $result = rrpproxy_call($params, "command=StatusContact&contact=" . $admincontact);
    if ($result["code"] == "200") {
        $values["Admin"] = array("First Name" => $result["property[first name][0]"], "Last Name" => $result["property[last name][0]"], "Organisation" => $result["property[organization][0]"], "Street" => $result["property[street][0]"], "City" => $result["property[city][0]"], "State" => $result["property[state][0]"], "Zip" => $result["property[zip][0]"], "Country" => $result["property[country][0]"], "Phone" => $result["property[phone][0]"], "Fax" => $result["property[fax][0]"], "Email" => $result["property[email][0]"]);
    } else {
        $values["error"] = $result["description"];
    }
    return $values;
}
function rrpproxy_SaveContactDetails($params)
{
    $domain = $params["sld"] . "." . $params["tld"];
    $result = rrpproxy_call($params, "command=StatusDomain&domain=" . $domain);
    $ownercontact = $result["property[owner contact][0]"];
    $admincontact = $result["property[admin contact][0]"];
    $result = rrpproxy_call($params, "command=ModifyContact&contact=" . $ownercontact . "&firstname=" . $params["contactdetails"]["Owner"]["First Name"] . "&lastname=" . $params["contactdetails"]["Owner"]["Last Name"] . "&organization=" . $params["contactdetails"]["Owner"]["Organisation"] . "&street=" . urlencode($params["contactdetails"]["Owner"]["Street"]) . "&city=" . urlencode($params["contactdetails"]["Owner"]["City"]) . "&state=" . urlencode($params["contactdetails"]["Owner"]["State"]) . "&zip=" . urlencode($params["contactdetails"]["Owner"]["Zip"]) . "&country=" . $params["contactdetails"]["Owner"]["Country"] . "&phone=" . urlencode($params["contactdetails"]["Owner"]["Phone"]) . "&fax=" . urlencode($params["contactdetails"]["Owner"]["Fax"]) . "&email=" . urlencode($params["contactdetails"]["Owner"]["Email"]));
    $result = rrpproxy_call($params, "command=ModifyContact&contact=" . $admincontact . "&firstname=" . $params["contactdetails"]["Admin"]["First Name"] . "&lastname=" . $params["contactdetails"]["Admin"]["Last Name"] . "&organization=" . $params["contactdetails"]["Admin"]["Organisation"] . "&street=" . urlencode($params["contactdetails"]["Admin"]["Street"]) . "&city=" . urlencode($params["contactdetails"]["Admin"]["City"]) . "&state=" . urlencode($params["contactdetails"]["Admin"]["State"]) . "&zip=" . urlencode($params["contactdetails"]["Admin"]["Zip"]) . "&country=" . $params["contactdetails"]["Admin"]["Country"] . "&phone=" . urlencode($params["contactdetails"]["Admin"]["Phone"]) . "&fax=" . urlencode($params["contactdetails"]["Admin"]["Fax"]) . "&email=" . urlencode($params["contactdetails"]["Admin"]["Email"]));
    if ($result["code"] != "200") {
        $values["error"] = $result["description"];
    }
    return $values;
}
function rrpproxy_GetEPPCode($params)
{
    $domain = $params["sld"] . "." . $params["tld"];
    $result = rrpproxy_call($params, "command=StatusDomain&domain=" . $domain);
    if ($result["code"] == "200") {
        $values["eppcode"] = $result["property[auth][0]"];
    } else {
        $values["error"] = $result["description"];
    }
    return $values;
}

?>