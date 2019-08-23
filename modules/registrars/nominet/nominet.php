<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require ROOTDIR . "/modules/registrars/nominet/class.Nominet.php";
function nominet_getConfigArray()
{
    $configarray = array("Description" => array("Type" => "System", "Value" => "The Official UK Domain Registry Module"), "Username" => array("Type" => "text", "Size" => "25", "Description" => ""), "Password" => array("Type" => "password", "Size" => "25", "Description" => ""), "TestMode" => array("Type" => "yesno"), "AllowClientTAGChange" => array("Type" => "yesno", "Description" => "Tick to allow clients to change TAGs on domains"), "DeleteOnTransfer" => array("Type" => "yesno", "Description" => "Tick this box if you want the domain to be" . " deleted entirely on RELEASE. Not Recommended - This option may be removed" . " in a future release."));
    return $configarray;
}
function nominet_GetNameservers($params)
{
    $nominet = WHMCS_Nominet::init($params);
    if ($nominet->connectAndLogin()) {
        $xml = "  <command>\n            <info>\n              <domain:info\n                xmlns:domain=\"urn:ietf:params:xml:ns:domain-1.0\">\n                <domain:name hosts=\"all\">" . $nominet->escapeParam($nominet->getDomain()) . "</domain:name>\n                </domain:info>\n            </info>\n            <clTRID>ABC-12345</clTRID>\n         </command>\n       </epp>";
        $success = $nominet->call($xml);
        if ($success) {
            if ($nominet->isErrorCode()) {
                return array("error" => $nominet->getErrorDesc());
            }
            $x = 1;
            $values = array();
            $xmldata = $nominet->getResponseArray();
            foreach ($xmldata["EPP"]["RESPONSE"]["RESDATA"]["DOMAIN:INFDATA"]["DOMAIN:NS"]["DOMAIN:HOSTOBJ"] as $discard => $nsdata) {
                $values["ns" . $x] = $nsdata;
                $x++;
            }
            return $values;
        } else {
            return array("error" => $nominet->getLastError());
        }
    } else {
        return array("error" => $nominet->getLastError());
    }
}
function nominet_SaveNameservers($params)
{
    $nominet = WHMCS_Nominet::init($params);
    if ($nominet->connectAndLogin()) {
        $removeNS = nominet_getnameservers($params);
        if (0 < count($removeNS)) {
            $removeXML = "\n                            <domain:rem>\n                                   <domain:ns>\n                        ";
            foreach ($removeNS as $rm) {
                $removeXML .= "<domain:hostObj>" . $nominet->escapeParam($rm) . "</domain:hostObj>\n                                ";
            }
            $removeXML .= " </domain:ns>\n                                      </domain:rem>\n                        ";
        } else {
            $removeXML = "";
        }
        $ns = array();
        $ns[1] = $params["ns1"];
        $ns[2] = $params["ns2"];
        $xml = "  <command>\n                    <update>\n                    <domain:update\n                    xmlns:domain=\"urn:ietf:params:xml:ns:domain-1.0\"\n                    xsi:schemaLocation=\"urn:ietf:params:xml:ns:domain-1.0\n                    domain-1.0.xsd\">\n                      <domain:name>" . $nominet->escapeParam($nominet->getDomain()) . "</domain:name>\n                      <domain:add>\n                        <domain:ns>\n                          <domain:hostObj>" . $nominet->escapeParam($params["ns1"]) . "</domain:hostObj>\n                          <domain:hostObj>" . $nominet->escapeParam($params["ns2"]) . "</domain:hostObj>\n               ";
        if ($params["ns3"]) {
            $ns[3] = $params["ns3"];
            $xml .= "<domain:hostObj>" . $nominet->escapeParam($params["ns3"]) . "</domain:hostObj>\n                    ";
        }
        if ($params["ns4"]) {
            $ns[4] = $params["ns4"];
            $xml .= "<domain:hostObj>" . $nominet->escapeParam($params["ns4"]) . "</domain:hostObj>\n                    ";
        }
        if ($params["ns5"]) {
            $ns[5] = $params["ns5"];
            $xml .= "<domain:hostObj>" . $nominet->escapeParam($params["ns5"]) . "</domain:hostObj>\n                    ";
        }
        $xml .= "</domain:ns>\n                </domain:add>" . $removeXML . "\n               </domain:update>\n             </update>\n           <clTRID>ABC-12345</clTRID>\n         </command>\n        </epp>";
        nominet_createHost($nominet, $ns);
        $success = $nominet->call($xml);
        if ($success) {
            if ($nominet->isErrorCode()) {
                return array("error" => $nominet->getErrorDesc());
            }
            $x = 1;
            $values = array();
            $xmldata = $nominet->getResponseArray();
            foreach ($xmldata["EPP"]["RESPONSE"]["RESDATA"]["DOMAIN:INFDATA"]["DOMAIN:NS"]["DOMAIN:HOSTOBJ"] as $discard => $nsdata) {
                $values["ns" . $x] = $nsdata;
                $x++;
            }
            return $values;
        } else {
            return array("error" => $nominet->getLastError());
        }
    } else {
        return array("error" => $nominet->getLastError());
    }
}
function nominet_getLegalTypeID($LegalType)
{
    $LegalTypeID = "";
    if ($LegalType == "Individual") {
        $LegalTypeID = "IND";
    } else {
        if ($LegalType == "UK Limited Company") {
            $LegalTypeID = "LTD";
        } else {
            if ($LegalType == "UK Public Limited Company") {
                $LegalTypeID = "PLC";
            } else {
                if ($LegalType == "UK Partnership") {
                    $LegalTypeID = "PTNR";
                } else {
                    if ($LegalType == "Sole Trader") {
                        $LegalTypeID = "STRA";
                    } else {
                        if ($LegalType == "UK Limited Liability Partnership") {
                            $LegalTypeID = "LLP";
                        } else {
                            if ($LegalType == "UK Industrial/Provident Registered Company") {
                                $LegalTypeID = "IP";
                            } else {
                                if ($LegalType == "UK School") {
                                    $LegalTypeID = "SCH";
                                } else {
                                    if ($LegalType == "UK Registered Charity") {
                                        $LegalTypeID = "RCHAR";
                                    } else {
                                        if ($LegalType == "UK Government Body") {
                                            $LegalTypeID = "GOV";
                                        } else {
                                            if ($LegalType == "UK Corporation by Royal Charter") {
                                                $LegalTypeID = "CRC";
                                            } else {
                                                if ($LegalType == "UK Statutory Body") {
                                                    $LegalTypeID = "STAT";
                                                } else {
                                                    if ($LegalType == "UK Entity (other)") {
                                                        $LegalTypeID = "OTHER";
                                                    } else {
                                                        if (in_array($LegalType, array("Non-UK Individual (representing self)", "Non-UK Individual"))) {
                                                            $LegalTypeID = "FIND";
                                                        } else {
                                                            if ($LegalType == "Foreign Organization") {
                                                                $LegalTypeID = "FCORP";
                                                            } else {
                                                                if ($LegalType == "Other foreign organizations") {
                                                                    $LegalTypeID = "FOTHER";
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
    return $LegalTypeID;
}
function nominet_RegisterDomain($params)
{
    $nominet = WHMCS_Nominet::init($params);
    if ($nominet->connectAndLogin()) {
        $RegistrantName = $params["additionalfields"]["Registrant Name"];
        if (!$RegistrantName) {
            $RegistrantName = $params["additionalfields"]["Company Name"];
        }
        if (!trim($RegistrantName)) {
            return array("error" => "Registrant Name is missing. Please check the contact fields on the domains tab.");
        }
        $LegalType = $params["additionalfields"]["Legal Type"];
        $CompanyIDNumber = $params["additionalfields"]["Company ID Number"];
        $WhoisOptOut = $params["additionalfields"]["WHOIS Opt-out"];
        $LegalTypeID = nominet_getlegaltypeid($LegalType);
        if (!$LegalTypeID) {
            return array("error" => "Legal Type is missing. Please check field on domains tab");
        }
        if ($LegalTypeID != "IND") {
            $WhoisOptOut = "";
        }
        $contactID = nominet_createContact($nominet, $params);
        if (is_array($contactID)) {
            return $contactID;
        }
        $ns = array();
        $ns[1] = $params["ns1"];
        $ns[2] = $params["ns2"];
        $xml = "\n            <command>\n              <create>\n                <domain:create\n                 xmlns:domain=\"urn:ietf:params:xml:ns:domain-1.0\"\n                 xsi:schemaLocation=\"urn:ietf:params:xml:ns:domain-1.0\n                 domain-1.0.xsd\">\n                   <domain:name>" . $nominet->escapeParam($nominet->getDomain()) . "</domain:name>\n                   <domain:period unit=\"y\">" . $params["regperiod"] . "</domain:period>\n                     <domain:ns>\n                      <domain:hostObj>" . $nominet->escapeParam($ns[1]) . "</domain:hostObj>\n                      <domain:hostObj>" . $nominet->escapeParam($ns[2]) . "</domain:hostObj>\n                     ";
        if ($params["ns3"]) {
            $ns[3] = $params["ns3"];
            $xml .= "<domain:hostObj>" . $nominet->escapeParam($params["ns3"]) . "</domain:hostObj>\n                                            ";
        }
        if ($params["ns4"]) {
            $ns[4] = $params["ns4"];
            $xml .= "<domain:hostObj>" . $nominet->escapeParam($params["ns4"]) . "</domain:hostObj>\n                                            ";
        }
        if ($params["ns5"]) {
            $ns[5] = $params["ns5"];
            $xml .= "<domain:hostObj>" . $nominet->escapeParam($params["ns5"]) . "</domain:hostObj>\n                                            ";
        }
        $xml .= " </domain:ns>\n                     <domain:registrant>" . $nominet->escapeParam($contactID) . "</domain:registrant>\n                     <domain:authInfo>\n                       <domain:pw></domain:pw>\n                     </domain:authInfo>\n                  </domain:create>\n               </create>\n            <clTRID>ABC-12345</clTRID>\n          </command>\n        </epp>\n            ";
        nominet_createHost($nominet, $ns);
        $success = $nominet->call($xml);
        if ($success) {
            if ($nominet->isErrorCode()) {
                return array("error" => $nominet->getErrorDesc());
            }
        } else {
            return array("error" => $nominet->getLastError());
        }
    } else {
        return array("error" => $nominet->getLastError());
    }
}
function nominet_TransferDomain(array $params)
{
    $nominet = WHMCS_Nominet::init($params);
    if ($nominet->connectAndLogin()) {
        $xml = "<command>\n            <info>\n              <domain:info\n                xmlns:domain=\"urn:ietf:params:xml:ns:domain-1.0\">\n                <domain:name hosts=\"all\">" . $nominet->escapeParam($nominet->getDomain()) . "</domain:name>\n                </domain:info>\n            </info>\n            <clTRID>ABC-12345</clTRID>\n         </command>\n       </epp>";
        $success = $nominet->call($xml);
        if ($success) {
            if ($nominet->isErrorCode()) {
                return array("success" => true);
            }
            return array("error" => "Domain already exists at domain registrar");
        }
        return array("error" => $nominet->getLastError());
    }
    return array("error" => $nominet->getLastError());
}
function nominet_RenewDomain($params)
{
    $nominet = WHMCS_Nominet::init($params);
    if ($nominet->connectAndLogin()) {
        $expiry = get_query_val("tbldomains", "expirydate", array("id" => $params["domainid"]));
        $xml = "  <command>\n                <renew>\n\t\t  <domain:renew\n\t\t  xmlns:domain=\"urn:ietf:params:xml:ns:domain-1.0\"\n\t\t  xsi:schemaLocation=\"urn:ietf:params:xml:ns:domain-1.0\n\t\t  domain-1.0.xsd\">\n                    <domain:name>" . $nominet->escapeParam($nominet->getDomain()) . "</domain:name>\n                    <domain:curExpDate>" . $nominet->escapeParam($expiry) . "</domain:curExpDate>\n                    <domain:period unit=\"y\">" . $nominet->escapeParam($params["regperiod"]) . "</domain:period>\n                  </domain:renew>\n                </renew>\n         <clTRID>ABC-12345</clTRID>\n       </command>\n     </epp>";
        $success = $nominet->call($xml);
        if ($success) {
            if ($nominet->isErrorCode()) {
                return array("error" => $nominet->getErrorDesc());
            }
            return array();
        }
        return array("error" => $nominet->getLastError());
    }
    return array("error" => $nominet->getLastError());
}
function nominet_GetContactDetails($params)
{
    $nominet = WHMCS_Nominet::init($params);
    if ($nominet->connectAndLogin()) {
        $xml = "  <command>\n            <info>\n              <domain:info\n                xmlns:domain=\"urn:ietf:params:xml:ns:domain-1.0\">\n                <domain:name hosts=\"all\">" . $nominet->escapeParam($nominet->getDomain()) . "</domain:name>\n                </domain:info>\n            </info>\n            <clTRID>ABC-12345</clTRID>\n         </command>\n       </epp>";
        $success = $nominet->call($xml);
        if ($success) {
            if ($nominet->isErrorCode()) {
                return array("error" => $nominet->getErrorDesc());
            }
            $xmldata = $nominet->getResponseArray();
            $contactID = $xmldata["EPP"]["RESPONSE"]["RESDATA"]["DOMAIN:INFDATA"]["DOMAIN:REGISTRANT"];
            $xml = "  <command>\n                        <info>\n                        <contact:info xmlns:contact=\"urn:ietf:params:xml:ns:contact-1.0\"\n                          xsi:schemaLocation=\"urn:ietf:params:xml:ns:contact-1.0\n                          contact-1.0.xsd\">\n                            <contact:id>" . $nominet->escapeParam($contactID) . "</contact:id>\n                          </contact:info>\n                        </info>\n                      <clTRID>ABC-12345</clTRID>\n                    </command>\n                  </epp>";
            $success = $nominet->call($xml);
            if ($success) {
                if ($nominet->isErrorCode()) {
                    return array("error" => $nominet->getErrorDesc());
                }
                $xmldata = $nominet->getResponseArray();
                $values = array();
                $values["Registrant"]["Contact Name"] = $xmldata["EPP"]["RESPONSE"]["RESDATA"]["CONTACT:INFDATA"]["CONTACT:POSTALINFO"]["CONTACT:NAME"];
                $streetData = $xmldata["EPP"]["RESPONSE"]["RESDATA"]["CONTACT:INFDATA"]["CONTACT:POSTALINFO"]["CONTACT:ADDR"]["CONTACT:STREET"];
                if (!is_array($streetData)) {
                    $streetData = array($streetData);
                }
                for ($i = 0; $i <= 2; $i++) {
                    $values["Registrant"]["Street " . ($i + 1)] = isset($streetData[$i]) ? $streetData[$i] : "";
                }
                $values["Registrant"]["City"] = $xmldata["EPP"]["RESPONSE"]["RESDATA"]["CONTACT:INFDATA"]["CONTACT:POSTALINFO"]["CONTACT:ADDR"]["CONTACT:CITY"];
                $values["Registrant"]["County"] = $xmldata["EPP"]["RESPONSE"]["RESDATA"]["CONTACT:INFDATA"]["CONTACT:POSTALINFO"]["CONTACT:ADDR"]["CONTACT:SP"];
                $values["Registrant"]["Postcode"] = $xmldata["EPP"]["RESPONSE"]["RESDATA"]["CONTACT:INFDATA"]["CONTACT:POSTALINFO"]["CONTACT:ADDR"]["CONTACT:PC"];
                $values["Registrant"]["Country"] = $xmldata["EPP"]["RESPONSE"]["RESDATA"]["CONTACT:INFDATA"]["CONTACT:POSTALINFO"]["CONTACT:ADDR"]["CONTACT:CC"];
                $values["Registrant"]["Phone Number"] = $xmldata["EPP"]["RESPONSE"]["RESDATA"]["CONTACT:INFDATA"]["CONTACT:VOICE"];
                $values["Registrant"]["Email Address"] = $xmldata["EPP"]["RESPONSE"]["RESDATA"]["CONTACT:INFDATA"]["CONTACT:EMAIL"];
                return $values;
            }
            return array("error" => $nominet->getLastError());
        }
        return array("error" => $nominet->getLastError());
    }
    return array("error" => $nominet->getLastError());
}
function nominet_SaveContactDetails($params)
{
    $nominet = WHMCS_Nominet::init($params);
    if ($nominet->connectAndLogin()) {
        $xml = "  <command>\n            <info>\n              <domain:info\n                xmlns:domain=\"urn:ietf:params:xml:ns:domain-1.0\">\n                <domain:name hosts=\"all\">" . $nominet->escapeParam($nominet->getDomain()) . "</domain:name>\n                </domain:info>\n            </info>\n            <clTRID>ABC-12345</clTRID>\n         </command>\n       </epp>";
        $success = $nominet->call($xml);
        if ($success) {
            if ($nominet->isErrorCode()) {
                return array("error" => $nominet->getErrorDesc());
            }
            $xmldata = $nominet->getResponseArray();
            $contactID = $xmldata["EPP"]["RESPONSE"]["RESDATA"]["DOMAIN:INFDATA"]["DOMAIN:REGISTRANT"];
            $xml = "  <command>\n                        <update>\n                          <contact:update\n                          xmlns:contact=\"urn:ietf:params:xml:ns:contact-1.0\"\n                          xsi:schemaLocation=\"urn:ietf:params:xml:ns:contact-1.0\n                          contact-1.0.xsd\">\n                          <contact:id>" . $contactID . "</contact:id>\n                            <contact:chg>\n                              <contact:postalInfo type=\"loc\">\n                              <contact:name>" . $nominet->escapeParam($params["contactdetails"]["Registrant"]["Contact Name"]) . "</contact:name>\n                              <contact:addr>";
            if ($params["contactdetails"]["Registrant"]["Street 1"]) {
                $xml .= "\n                                <contact:street>" . $nominet->escapeParam($params["contactdetails"]["Registrant"]["Street 1"]) . "</contact:street>";
            }
            if ($params["contactdetails"]["Registrant"]["Street 2"]) {
                $xml .= "\n                                <contact:street>" . $nominet->escapeParam($params["contactdetails"]["Registrant"]["Street 2"]) . "</contact:street>";
            }
            if ($params["contactdetails"]["Registrant"]["Street 3"]) {
                $xml .= "\n                                <contact:street>" . $nominet->escapeParam($params["contactdetails"]["Registrant"]["Street 3"]) . "</contact:street>";
            }
            $xml .= "\n                                <contact:city>" . $nominet->escapeParam($params["contactdetails"]["Registrant"]["City"]) . "</contact:city>\n                                <contact:sp>" . $nominet->escapeParam($params["contactdetails"]["Registrant"]["County"]) . "</contact:sp>\n                                <contact:pc>" . $nominet->escapeParam(strtoupper($params["contactdetails"]["Registrant"]["Postcode"])) . "</contact:pc>\n                                <contact:cc>" . $nominet->escapeParam($params["contactdetails"]["Registrant"]["Country"]) . "</contact:cc>\n                               </contact:addr>\n                              </contact:postalInfo>\n                            <contact:voice>" . $nominet->escapeParam($params["contactdetails"]["Registrant"]["Phone Number"]) . "</contact:voice>\n                            <contact:email>" . $nominet->escapeParam($params["contactdetails"]["Registrant"]["Email Address"]) . "</contact:email>\n                            </contact:chg>\n                          </contact:update>\n                         </update>\n                         <clTRID>ABC-12345</clTRID>\n                       </command>\n                     </epp>";
            $success = $nominet->call($xml);
            if ($success) {
                if ($nominet->isErrorCode()) {
                    return array("error" => $nominet->getErrorDesc());
                }
                return array();
            }
        } else {
            return array("error" => $nominet->getLastError());
        }
    } else {
        return array("error" => $nominet->getLastError());
    }
}
function nominet_ReleaseDomain($params)
{
    $nominet = WHMCS_Nominet::init($params);
    if ($nominet->connectAndLogin()) {
        $transfertag = $params["transfertag"];
        $xml = "  <command>\n\t        <update>\n\t\t<r:release\n\t\txmlns:r=\"http://www.nominet.org.uk/epp/xml/std-release-1.0\"\n\t\txsi:schemaLocation=\"http://www.nominet.org.uk/epp/xml/std-release-1.0\n\t\tstd-release-1.0.xsd\">\n\t\t<r:domainName>" . $nominet->escapeParam($nominet->getDomain()) . "</r:domainName>\n\t\t<r:registrarTag>" . $nominet->escapeParam($transfertag) . "</r:registrarTag>\n\t\t</r:release>\n\t\t</update>\n               <clTRID>ABC-12345</clTRID>\n              </command>\n            </epp>";
        $success = $nominet->call($xml);
        if ($success) {
            if ($nominet->isErrorCode()) {
                return array("error" => $nominet->getErrorDesc());
            }
            if ($nominet->getResultCode() == 1000 && $params["DeleteOnTransfer"]) {
                delete_query("tbldomains", array("id" => $params["domainid"]));
                return array("deleted" => true);
            }
        } else {
            return array("error" => $nominet->getLastError());
        }
    } else {
        return array("error" => $nominet->getLastError());
    }
}
function nominet_TransferSync(array $params)
{
    return nominet_Sync($params, "Transfer");
}
function nominet_Sync(array $params, $type = "Active")
{
    $nominet = WHMCS_Nominet::init($params);
    if ($nominet->connectAndLogin()) {
        $xml = "  <command>\n                <info>\n\t\t<domain:info\n\t\txmlns:domain=\"urn:ietf:params:xml:ns:domain-1.0\">\n                  <domain:name hosts = \"all\">" . $nominet->escapeParam($nominet->getDomain()) . "</domain:name>\n                </domain:info>\n                </info>\n                <clTRID>ABC-12345</clTRID>\n              </command>\n            </epp>";
        $success = $nominet->call($xml);
        if ($success) {
            if ($nominet->getResultCode() == 2201 && $type == "Active") {
                $return = array();
                if ($params["DeleteOnTransfer"]) {
                    delete_query("tbldomains", array("id" => $params["domainid"]));
                    $return["error"] = "Domain Deleted per Nominet Module Configuration";
                } else {
                    $return["cancelled"] = true;
                }
                return $return;
            }
            if ($nominet->isErrorCode()) {
                return array("error" => $nominet->getErrorDesc());
            }
            $xmldata = $nominet->getResponseArray();
            $expirydate = trim($xmldata["EPP"]["RESPONSE"]["RESDATA"]["DOMAIN:INFDATA"]["DOMAIN:EXDATE"]);
            $expirydate = substr($expirydate, 0, 10);
            if ($expirydate) {
                $rtn = array();
                $rtn["expirydate"] = $expirydate;
                if (date("Ymd") <= str_replace("-", "", $expirydate)) {
                    $rtn["active"] = true;
                } else {
                    $rtn["expired"] = true;
                }
                return $rtn;
            }
        } else {
            return array("error" => $nominet->getLastError());
        }
    } else {
        return array("error" => $nominet->getLastError());
    }
}
function nominet_createContact($nominet, $params)
{
    $RegistrantName = $params["additionalfields"]["Registrant Name"];
    $LegalType = $params["additionalfields"]["Legal Type"];
    $CompanyIDNumber = $params["additionalfields"]["Company ID Number"];
    $WhoisOptOut = $params["additionalfields"]["WHOIS Opt-out"];
    $TradingName = $params["additionalfields"]["Trading Name"];
    $LegalTypeID = nominet_getlegaltypeid($LegalType);
    if ($LegalTypeID != "IND") {
        $WhoisOptOut = "";
    }
    $WhoisOptOut = $WhoisOptOut ? "Y" : "N";
    if ($LegalTypeID == "IND") {
        $RegistrantOrgName = "";
    } else {
        $RegistrantOrgName = $RegistrantName;
        $RegistrantName = $params["firstname"] . " " . $params["lastname"];
    }
    $street = $params["address1"];
    $street2 = trim($params["address2"]);
    $street2code = empty($street2) ? "" : "<contact:street>" . $nominet->escapeParam($street2) . "</contact:street>";
    $city = $params["city"];
    $county = $params["state"];
    $postcode = $params["postcode"];
    $country = $params["country"];
    $phonenumber = $params["fullphonenumber"];
    $email = $params["email"];
    $contactID = "WHMCS" . $params["domainid"] . rand(1000, 9999);
    if ($RegistrantOrgName) {
        $RegistrantOrgName = "<contact:org>" . $nominet->escapeParam($RegistrantOrgName) . "</contact:org>";
    }
    $xml = "  <command>\n\t     <create>\n\t     <contact:create\n\t\t     xmlns:contact=\"urn:ietf:params:xml:ns:contact-1.0\"\n\t\t     xsi:schemaLocation=\"urn:ietf:params:xml:ns:contact-1.0\n\t\t     contact-1.0.xsd\">\n\t\t\t     <contact:id>" . $nominet->escapeParam($contactID) . "</contact:id>\n\t\t\t     <contact:postalInfo type=\"loc\">\n                 <contact:name>" . $nominet->escapeParam($RegistrantName) . "</contact:name>\n                 " . $RegistrantOrgName . "\n                 <contact:addr>\n\t\t\t\t <contact:street>" . $nominet->escapeParam($street) . "</contact:street>\n                " . $street2code . "\n\t\t\t\t <contact:city>" . $nominet->escapeParam($city) . "</contact:city>\n\t\t\t\t <contact:sp>" . $nominet->escapeParam($county) . "</contact:sp>\n\t\t\t\t <contact:pc>" . $nominet->escapeParam($postcode) . "</contact:pc>\n\t\t\t\t<contact:cc>" . $nominet->escapeParam($country) . "</contact:cc>\n\t\t\t\t     </contact:addr>\n\t\t\t     </contact:postalInfo>\n\t\t\t\t     <contact:voice>" . $nominet->escapeParam($phonenumber) . "</contact:voice>\n\t\t\t\t     <contact:email>" . $nominet->escapeParam($email) . "</contact:email>\n\t\t\t\t     <contact:authInfo>\n\t\t\t\t <contact:pw>" . $nominet->escapeParam(substr(sha1(time()), 0, 15)) . "</contact:pw>\n\t\t\t\t </contact:authInfo>\n\t\t\t     </contact:create>\n\t\t\t   </create>\n<extension>\n<contact-ext:create\nxmlns:contact-ext=\"http://www.nominet.org.uk/epp/xml/contact-nom-ext-1.0\"\nxsi:schemaLocation=\"http://www.nominet.org.uk/epp/xml/contact-nom-ext-1.0 contact-nom-ext-1.0.xsd\">\n";
    if ($TradingName) {
        $xml .= "<contact-ext:trad-name>" . $nominet->escapeParam($TradingName) . "</contact-ext:trad-name>\n";
    }
    $xml .= "<contact-ext:type>" . $nominet->escapeParam($LegalTypeID) . "</contact-ext:type>\n";
    if (isset($CompanyIDNumber) && 0 < strlen($CompanyIDNumber)) {
        $xml .= "<contact-ext:co-no>" . $nominet->escapeParam($CompanyIDNumber) . "</contact-ext:co-no>\n";
    }
    $xml .= "<contact-ext:opt-out>" . $nominet->escapeParam($WhoisOptOut) . "</contact-ext:opt-out>\n</contact-ext:create>\n</extension>\n\t\t\t<clTRID>ABC-12345</clTRID>\n\t\t   </command>\n\t\t </epp>\n\t";
    $success = $nominet->call($xml);
    if ($success) {
        if ($nominet->isErrorCode()) {
            if ($nominet->getResultCode() == 2302) {
                $params["contactCreateCount"]++;
                if (10 < $params["contactCreateCount"]) {
                    return array("error" => "Failed to create contact. Please contact support.");
                }
                return nominet_createContact($nominet, $params);
            }
            return array("error" => $nominet->getErrorDesc());
        }
        $xmldata = $nominet->getResponseArray();
        return $xmldata["EPP"]["RESPONSE"]["RESDATA"]["CONTACT:CREDATA"]["CONTACT:ID"];
    }
    return array("error" => $nominet->getLastError());
}
function nominet_createHost($nominet, $ns = array())
{
    foreach ($ns as $server) {
        $xml = "  <command>\n\t        <create>\n\t\t  <host:create xmlns:host=\"urn:ietf:params:xml:ns:host-1.0\"\n\t\t  xsi:schemaLocation=\"urn:ietf:params:xml:ns:host-1.0\n\t\t  host-1.0.xsd\">\n\t\t  ";
        $xml .= "<host:name>" . $nominet->escapeParam($server) . "</host:name>\n\t\t  </host:create>\n\t\t</create>\n              <clTRID>ABC-12345</clTRID>\n\t    </command>\n\t  </epp>\n\t  ";
        $result = $nominet->call($xml);
    }
}

?>