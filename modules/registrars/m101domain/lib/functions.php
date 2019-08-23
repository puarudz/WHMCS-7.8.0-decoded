<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function m101domain_getConfigArray()
{
    if (!class_exists("\\DOMDocument")) {
        return array("error" => array("Description" => "Error: The DOM PHP Extension needs to be installed to use 101domain"));
    }
    $configuration = array("FriendlyName" => array("Type" => "System", "Value" => "101 Domain"), "Username" => array("Type" => "text", "Size" => "20", "Description" => "Enter your username here"), "Password" => array("Type" => "password", "Size" => "20", "Description" => "Enter your password here"), "ApiUrl" => array("Type" => "text", "Size" => "50", "Description" => "Customize the API URL here if required", "Default" => "https://api.101domain.com/epp.xml"), "DisableValidation" => array("Type" => "yesno", "Description" => "Check to disable SSL Validation if you are receiving SSL related errors"));
    return $configuration;
}
function m101domain_GetNameservers($params)
{
    $tld = $params["tld"];
    $sld = $params["sld"];
    try {
        $domain = m101domain_eppdomaininfo($params, $sld . "." . $tld);
        $values = array();
        $x = 1;
        foreach ($domain->ns as $ns) {
            if (empty($ns)) {
                continue;
            }
            $values["ns" . $x] = $ns;
            $x++;
        }
        return $values;
    } catch (M101Domain\Exception\Error $e) {
        $values = array("error" => $e->getMessage());
        return $values;
    }
}
function m101domain_SaveNameservers($params)
{
    try {
        $new_nameservers = array();
        for ($x = 1; $x <= 13; $x++) {
            $passedNameserver = !empty($params["ns" . $x]) ? $params["ns" . $x] : NULL;
            if ($passedNameserver) {
                $passedNameserver = strtoupper($passedNameserver);
                $new_nameservers[$passedNameserver] = $passedNameserver;
            }
        }
        if (count($new_nameservers) < 2) {
            throw new M101Domain\Exception\Error("At least 2 nameservers are required");
        }
        $tld = $params["tld"];
        $sld = $params["sld"];
        $domain = m101domain_eppdomaininfo($params, $sld . "." . $tld);
        $to_remove = array();
        $to_add = array();
        foreach ($domain->ns as $ns) {
            $ns = strtoupper($ns);
            if (!isset($new_nameservers[$ns])) {
                $to_remove[] = $ns;
            }
        }
        foreach ($new_nameservers as $ns) {
            if (!in_array($domain->ns, $ns)) {
                $to_add[] = $ns;
            }
        }
        $xml = "<epp xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns=\"urn:ietf:params:xml:ns:epp-1.0\"\n     xsi:schemaLocation=\"urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd\">\n     <command>\n      <update>\n       <domain:update xmlns:domain=\"urn:ietf:params:xml:ns:domain-1.0\"\n        xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n        xsi:schemaLocation=\"urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd\">\n        <domain:name>" . htmlspecialchars($sld . "." . $tld) . "</domain:name>\n        <domain:add>\n         ";
        if (count($to_add)) {
            $xml .= "<domain:ns>";
            foreach ($to_add as $ns) {
                $xml .= "<domain:hostObj>" . htmlspecialchars($ns) . "</domain:hostObj>";
            }
            $xml .= "</domain:ns>";
        }
        $xml .= "</domain:add><domain:rem>";
        if (count($to_remove)) {
            $xml .= "<domain:ns>";
            foreach ($to_remove as $ns) {
                $xml .= "<domain:hostObj>" . htmlspecialchars($ns) . "</domain:hostObj>";
            }
            $xml .= "</domain:ns>";
        }
        $xml .= "</domain:rem><domain:chg /></domain:update></update></command></epp>";
        m101domain_command($params, $xml, "m101domain_SaveNameservers");
        unset(M101Domain\Cache::$cache[strtolower($domain->name)]);
        $values = array();
        return $values;
    } catch (M101Domain\Exception\Error $e) {
        $values = array("error" => $e->getMessage());
        return $values;
    }
}
function m101domain_GetRegistrarLock($params)
{
    $tld = $params["tld"];
    $sld = $params["sld"];
    try {
        $domain = m101domain_eppdomaininfo($params, $sld . "." . $tld);
        return $domain->isLocked() ? "locked" : "unlocked";
    } catch (M101Domain\Exception\Error $e) {
        $values = array("error" => $e->getMessage());
        return $values;
    }
}
function m101domain_SaveRegistrarLock($params)
{
    $tld = $params["tld"];
    $sld = $params["sld"];
    $domain = m101domain_eppdomaininfo($params, $sld . "." . $tld);
    $is_locked = $domain->isLocked();
    $xml = "<epp xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns=\"urn:ietf:params:xml:ns:epp-1.0\"\n xsi:schemaLocation=\"urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd\">\n <command>\n  <update>\n   <domain:update xmlns:domain=\"urn:ietf:params:xml:ns:domain-1.0\"\n    xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n    xsi:schemaLocation=\"urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd\">\n    <domain:name>" . htmlspecialchars($sld . "." . $tld) . "</domain:name>\n    <domain:add>\n     " . (!$is_locked ? "<domain:status s=\"clientTransferProhibited\" />" : "") . "\n    </domain:add>\n    <domain:rem>\n     " . ($is_locked ? "<domain:status s=\"clientTransferProhibited\" />" : "") . "\n    </domain:rem>\n    <domain:chg />\n   </domain:update>\n  </update>\n </command>\n</epp>";
    try {
        m101domain_command($params, $xml, "SaveRegistrarLock");
        unset(M101Domain\Cache::$cache[strtolower($domain->name)]);
        $values = array();
        return $values;
    } catch (M101Domain\Exception\Error $e) {
        $values = array("error" => $e->getMessage());
        return $values;
    }
}
function m101domain_GetDNS($params)
{
    $tld = $params["tld"];
    $sld = $params["sld"];
    $name = strtoupper($sld . "." . $tld);
    try {
        $rrs = m101domain_eppdnsinfo($params, $name);
        $rtn_rr = array();
        foreach ($rrs as &$rr) {
            if ($rr["type"] == "A" || $rr["type"] == "MX" || $rr["type"] == "TXT" || $rr["type"] == "CNAME" || $rr["type"] == "AAAA") {
                $rr["hostname"] = preg_replace("/(^|\\.)" . preg_quote($name) . "\\.?\$/", "", $rr["hostname"]);
                $rtn_rr[] = $rr;
            }
        }
        return $rtn_rr;
    } catch (M101Domain\Exception\Error $e) {
        return array("error" => $e->getMessage());
    }
}
function m101domain_SaveDNS($params)
{
    $tld = $params["tld"];
    $sld = $params["sld"];
    $records = array();
    $name = $sld . "." . $tld;
    $aRecord = array();
    foreach ($params["dnsrecords"] as &$values) {
        if ($values["type"] == "MXE") {
            $values["type"] = "MX";
            $aRecord = array("hostname" => "mailserver", "type" => "A", "address" => $values["address"]);
            $values["address"] = "mailserver." . $name . ".";
        }
    }
    if ($aRecord) {
        $params["dnsrecords"][] = $aRecord;
    }
    foreach ($params["dnsrecords"] as &$values) {
        $hostname = $values["hostname"];
        $type = $values["type"];
        $address = $values["address"];
        if (empty($address)) {
            continue;
        }
        $rr_name = strtoupper(ltrim($hostname . "." . $name . ".", "."));
        $rr = "<dns:rr><dns:name>" . htmlspecialchars($rr_name) . "</dns:name><dns:ttl>86400</dns:ttl>";
        switch ($type) {
            case "AAAA":
            case "CNAME":
            case "TXT":
            case "NS":
            case "A":
                $rr .= "<dns:" . $type . ">" . htmlspecialchars($address) . "</dns:" . $type . ">";
                break;
            case "MX":
                $rr .= "<dns:MX><dns:priority>" . htmlspecialchars($values["priority"]) . "</dns:priority>" . "<dns:addr>" . htmlspecialchars($address) . "</dns:addr></dns:MX>";
                break;
        }
        $rr .= "</dns:rr>";
        $records[] = $rr;
    }
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n        <epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd\">\n         <command>\n          <update>\n           <dns:update xmlns:dns=\"epp.101domain.com:dns-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n            xsi:schemaLocation=\"epp.101domain.com:dns-1.0 dns-1.0.xsd\">\n             <dns:name>" . htmlspecialchars($name) . "</dns:name>\n             <dns:chg>" . join($records, "") . "</dns:chg>\n           </dns:update>\n          </update>\n         </command>\n        </epp>";
    try {
        m101domain_command($params, $xml, "DNS Update");
        return array();
    } catch (M101Domain\Exception\Error $e) {
        if ($e->getCode() == 2303) {
            return array();
        }
        return array("error" => $e->getMessage());
    }
}
function m101domain_RegisterDomain($params)
{
    try {
        $tld = $params["tld"];
        $sld = $params["sld"];
        $domain_name = $sld . "." . $tld;
        list($registrant_handle, $admin_handle, $tech_handle, $bill_handle) = m101domain_contacthandles($params);
        $check_contacts = m101domain_eppCheckContact($params, array($registrant_handle, $admin_handle, $tech_handle, $bill_handle));
        $registrant = new M101Domain\Contact();
        $registrant->handle = $registrant_handle;
        $registrant->first_name = $params["firstname"];
        $registrant->last_name = $params["lastname"];
        $registrant->address1 = $params["address1"];
        $registrant->address2 = $params["address2"];
        $registrant->city = $params["city"];
        $registrant->state = $params["state"];
        $registrant->postal = $params["postcode"];
        $registrant->country = $params["country"];
        $registrant->phone = $params["phonenumber"];
        $registrant->company = $params["companyname"];
        $registrant->email = $params["email"];
        if ($check_contacts[$registrant->handle]) {
            m101domain_eppRegisterContact($params, $registrant);
        } else {
            m101domain_eppUpdateContact($params, $registrant);
        }
        $admin = new M101Domain\Contact();
        $admin->handle = $admin_handle;
        $admin->first_name = $params["adminfirstname"];
        $admin->last_name = $params["adminlastname"];
        $admin->address1 = $params["adminaddress1"];
        $admin->address2 = $params["adminaddress2"];
        $admin->city = $params["admincity"];
        $admin->state = $params["adminstate"];
        $admin->postal = $params["adminpostcode"];
        $admin->country = $params["admincountry"];
        $admin->phone = $params["adminphonenumber"];
        $admin->company = $params["admincompanyname"];
        $admin->email = $params["adminemail"];
        if ($check_contacts[$admin->handle]) {
            m101domain_eppRegisterContact($params, $admin);
        } else {
            m101domain_eppUpdateContact($params, $admin);
        }
        $tech = clone $admin;
        $tech->handle = $tech_handle;
        if ($check_contacts[$tech->handle]) {
            m101domain_eppRegisterContact($params, $tech);
        } else {
            m101domain_eppUpdateContact($params, $tech);
        }
        $billing = clone $admin;
        $billing->handle = $bill_handle;
        if ($check_contacts[$billing->handle]) {
            m101domain_eppRegisterContact($params, $billing);
        } else {
            m101domain_eppUpdateContact($params, $billing);
        }
        unset(M101Domain\Cache::$cache[strtolower($domain_name)]);
        $xml = "\n        <epp xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns=\"urn:ietf:params:xml:ns:epp-1.0\"\n         xsi:schemaLocation=\"urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd\">\n         <command>\n          <create>\n           <domain:create xmlns:domain=\"urn:ietf:params:xml:ns:domain-1.0\"\n            xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n            xsi:schemaLocation=\"urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd\">\n            <domain:name>" . htmlspecialchars($domain_name) . "</domain:name>\n            <domain:period unit=\"y\">" . htmlspecialchars($params["regperiod"]) . "</domain:period>";
        $nameservers = array();
        for ($x = 1; $x <= 13; $x++) {
            if (!empty($params["ns" . $x])) {
                $nameservers[] = $params["ns" . $x];
            }
        }
        if (count($nameservers)) {
            $xml .= "<domain:ns>";
            foreach ($nameservers as &$ns) {
                $xml .= "<domain:hostObj>" . htmlspecialchars($ns) . "</domain:hostObj>";
            }
            $xml .= "</domain:ns>";
        }
        $ext_xml = m101domain_eppdomaincreateext($params, $registrant_handle);
        $ext_xml = $ext_xml ? "<extension>" . $ext_xml . "</extension>" : "";
        $xml .= "\n                <domain:registrant>" . htmlspecialchars($registrant->handle) . "</domain:registrant>\n                <domain:contact type=\"admin\">" . htmlspecialchars($admin->handle) . "</domain:contact>\n                <domain:contact type=\"tech\">" . htmlspecialchars($tech->handle) . "</domain:contact>\n                <domain:contact type=\"billing\">" . htmlspecialchars($billing->handle) . "</domain:contact>\n               </domain:create>\n              </create>" . $ext_xml . "\n             </command>\n            </epp>";
        $create_res = m101domain_command($params, $xml, "Register Domain");
        if ($create_res->error_code == 1001) {
            return array("error" => "Domain was submitted for registration and is pending approval");
        }
        return array("error" => "");
    } catch (M101Domain\Exception\Error $e) {
        $values = array("error" => $e->getMessage());
        return $values;
    }
}
function m101domain_eppDomainCreateExt(array &$params, $registrant)
{
    M101Domain\DomainFields::init();
    $xml = "";
    $tld = strtolower("." . ltrim($params["tld"], "."));
    $fields = !empty(M101Domain\DomainFields::$m101domain_fieldconfig[$tld]) ? M101Domain\DomainFields::$m101domain_fieldconfig[$tld] : array();
    if (!empty($fields)) {
        $first = reset($fields);
        if ($first["_ext_type"] == "simple") {
            list($pfx, $nn) = explode(":", $first["_ext_node"]);
            $envelope_begin = sprintf("<%s:%s xmlns:%s=\"%s\">", $pfx, $nn, $pfx, $first["_ext_namespace"]);
            $envelope_end = sprintf("</%s:%s>", $pfx, $nn);
            $xml = $envelope_begin;
            foreach ($fields as &$field) {
                Log::debug("epp reg", array($field, $params));
                $val = htmlspecialchars(m101domain_addFieldGetVal($field, $params));
                $xml .= sprintf("<%s:%s>%s</%s:%s>", $pfx, $field["_ext_field"], $val, $pfx, $field["_ext_field"]);
            }
            $xml .= $envelope_end;
            return $xml;
        } else {
            if ($first["_ext_type"] == "hk") {
                $pfx = "hk";
                $nn = "create";
                $ns = "epp.101domain.com:hk-ext-1.0";
                $xml = sprintf("<%s:%s xmlns:%s=\"%s\">", $pfx, $nn, $pfx, $ns);
                $vars = array();
                foreach ($fields as &$field) {
                    $vars[$field["_ext_name"]] = m101domain_addFieldGetVal($field, $params);
                }
                $xml .= sprintf("<%s:surname>%s</%s:surname>", $pfx, htmlspecialchars($registrant->last_name), $pfx);
                if ($vars["org_type"] == "org") {
                    $org_node = "organization";
                    $org_fields = array("org_doc_type" => "documentationType", "org_chinese" => "chineseOrg", "org_doc_country" => "issuingCountry", "org_doc_number" => "documentNumber", "org_industry_type" => "industryType");
                } else {
                    $org_node = "individual";
                    $org_fields = array("ind_doc_type" => "documentationType", "ind_doc_country" => "issuingCountry", "ind_doc_number" => "documentNumber", "ind_under18" => "under18");
                }
                $xml .= sprintf("<%s:%s>", $pfx, $org_node);
                foreach ($org_fields as $var_name => $node_name) {
                    $xml .= sprintf("<%s:%s>%s</%s:%s>", $pfx, $node_name, $vars[$var_name], $pfx, $node_name);
                }
                $xml .= sprintf("</%s:%s>", $pfx, $org_node);
                $xml .= sprintf("</%s:%s>", $pfx, $nn);
                return $xml;
            } else {
                if ($first["_ext_type"] == "ru") {
                    $pfx = "ru";
                    $nn = "create";
                    $ns = "epp.101domain.com:ru-ext-1.0";
                    $xml = sprintf("<%s:%s xmlns:%s=\"%s\">", $pfx, $nn, $pfx, $ns);
                    $vars = array();
                    foreach ($fields as &$field) {
                        $vars[$field["_ext_name"]] = m101domain_addFieldGetVal($field, $params);
                    }
                    if ($vars["org_type"] == "ORG") {
                        $org_node = "organization";
                        $org_fields = array("taxpayerNumber" => "taxpayerNumber", "taxpayerNumber2" => "taxpayerNumber");
                    } else {
                        $org_node = "individual";
                        $org_fields = array("birthday" => "birthday", "passportNumber" => "passportNumber", "passportIssuer" => "passportIssuer", "passportIssueDate" => "passportIssueDate", "whoisPP" => "whoisPP");
                    }
                    $xml .= sprintf("<%s:%s>", $pfx, $org_node);
                    foreach ($org_fields as $var_name => $node_name) {
                        $xml .= sprintf("<%s:%s>%s</%s:%s>", $pfx, $node_name, $vars[$var_name], $pfx, $node_name);
                    }
                    $xml .= sprintf("</%s:%s>", $pfx, $org_node);
                    $xml .= sprintf("</%s:%s>", $pfx, $nn);
                    return $xml;
                } else {
                    if ($first["_ext_type"] == "travel") {
                        $pfx = "travel";
                        $nn = "create";
                        $uri = "epp.101domain.com:travel-ext-1.0";
                        $envelope_begin = sprintf("<%s:%s xmlns:%s=\"%s\">", $pfx, $nn, $pfx, $uri);
                        $envelope_end = sprintf("</%s:%s>", $pfx, $nn);
                        $xml = $envelope_begin;
                        $vars = array();
                        foreach ($fields as &$field) {
                            $vars[$field["_ext_field"]] = m101domain_addFieldGetVal($field, $params);
                        }
                        if ($vars["trustee"] == "TRUST") {
                            unset($vars["uin"]);
                            $vars["agreeTrusteeTAC"] = $vars["agreeTrusteeTAC"] ? "true" : "false";
                            $vars["agreeTravelTAC"] = $vars["agreeTravelTAC"] ? "true" : "false";
                        } else {
                            unset($vars["agreeTrusteeTAC"]);
                            unset($vars["agreeTravelTAC"]);
                        }
                        unset($vars["trustee"]);
                        foreach ($vars as $var_name => $value) {
                            $val = htmlspecialchars($value);
                            $xml .= sprintf("<%s:%s>%s</%s:%s>", $pfx, $var_name, $val, $pfx, $var_name);
                        }
                        $xml .= $envelope_end;
                        return $xml;
                    } else {
                        if ($first["_ext_type"] == "cn") {
                            $pfx = "cn";
                            $nn = "create";
                            $ns = "epp.101domain.com:cn-ext-1.0";
                            $xml = sprintf("<%s:%s xmlns:%s=\"%s\">", $pfx, $nn, $pfx, $ns);
                            $vars = array();
                            foreach ($fields as &$field) {
                                $vars[$field["_ext_field"]] = m101domain_addFieldGetVal($field, $params);
                            }
                            if ($vars["org_type"] == "trustee") {
                                return "<trustee:create xmlns:trustee=\"epp.101domain.com:trustee-ext-1.0\"><trustee:acceptTrusteeTAC>" . (!empty($vars["trusteeTAC"]) ? "true" : "false") . "</trustee:acceptTrusteeTAC></trustee:create>";
                            }
                            if ($vars["org_type"] == "org") {
                                $org_node = "organization";
                                $org_fields = array("industry" => "industry", "documentType" => "documentType", "organizationNumber" => "organizationNumber", "managerIDType" => "managerIDType", "managerIDNumber" => "managerIDNumber");
                            } else {
                                $org_node = "individual";
                                $org_fields = array("idNumber" => "idNumber", "idType" => "idType");
                            }
                            $xml .= sprintf("<%s:%s>", $pfx, $org_node);
                            foreach ($org_fields as $var_name => $node_name) {
                                $xml .= sprintf("<%s:%s>%s</%s:%s>", $pfx, $node_name, $vars[$var_name], $pfx, $node_name);
                            }
                            $xml .= sprintf("</%s:%s>", $pfx, $org_node);
                            $xml .= sprintf("</%s:%s>", $pfx, $nn);
                            return $xml;
                        }
                    }
                }
            }
        }
    }
    return $xml;
}
function m101domain_addFieldGetVal(array &$field, array &$params)
{
    $val = !empty($params["additionalfields"][$field["Name"]]) ? $params["additionalfields"][$field["Name"]] : "";
    if (isset($field["_ext_bool_true"])) {
        $val = $field["_ext_bool_true"] == $val ? "true" : "false";
    }
    if (is_array($field["_ext_transform"]) && !empty($field["_ext_transform"][$val])) {
        $val = $field["_ext_transform"][$val];
    }
    return $val;
}
function m101domain_TransferDomain($params)
{
    try {
        $tld = $params["tld"];
        $sld = $params["sld"];
        $domain_name = $sld . "." . $tld;
        $transfersecret = $params["transfersecret"];
        list($registrant_handle, $admin_handle, $tech_handle, $bill_handle) = m101domain_contacthandles($params);
        $check_contacts = m101domain_eppCheckContact($params, array($registrant_handle, $admin_handle, $tech_handle, $bill_handle));
        $registrant = new M101Domain\Contact();
        $registrant->handle = $registrant_handle;
        $registrant->first_name = $params["firstname"];
        $registrant->last_name = $params["lastname"];
        $registrant->address1 = $params["address1"];
        $registrant->address2 = $params["address2"];
        $registrant->city = $params["city"];
        $registrant->state = $params["state"];
        $registrant->postal = $params["postcode"];
        $registrant->country = $params["country"];
        $registrant->phone = $params["phonenumber"];
        $registrant->company = $params["companyname"];
        $registrant->email = $params["email"];
        if ($check_contacts[$registrant->handle]) {
            m101domain_eppRegisterContact($params, $registrant);
        } else {
            m101domain_eppUpdateContact($params, $registrant);
        }
        $admin = new M101Domain\Contact();
        $admin->handle = $admin_handle;
        $admin->first_name = $params["adminfirstname"];
        $admin->last_name = $params["adminlastname"];
        $admin->address1 = $params["adminaddress1"];
        $admin->address2 = $params["adminaddress2"];
        $admin->city = $params["admincity"];
        $admin->state = $params["adminstate"];
        $admin->postal = $params["adminpostcode"];
        $admin->country = $params["admincountry"];
        $admin->phone = $params["adminphonenumber"];
        $admin->company = $params["admincompanyname"];
        $admin->email = $params["adminemail"];
        if ($check_contacts[$admin->handle]) {
            m101domain_eppRegisterContact($params, $admin);
        } else {
            m101domain_eppUpdateContact($params, $admin);
        }
        $tech = clone $admin;
        $tech->handle = $tech_handle;
        if ($check_contacts[$tech->handle]) {
            m101domain_eppRegisterContact($params, $tech);
        } else {
            m101domain_eppUpdateContact($params, $tech);
        }
        $billing = clone $admin;
        $billing->handle = $bill_handle;
        if ($check_contacts[$billing->handle]) {
            m101domain_eppRegisterContact($params, $billing);
        } else {
            m101domain_eppUpdateContact($params, $billing);
        }
        $xml = "\n        <epp xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns=\"urn:ietf:params:xml:ns:epp-1.0\"\n         xsi:schemaLocation=\"urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd\">\n         <command>\n          <transfer op=\"request\">\n           <domain:transfer xmlns:domain=\"urn:ietf:params:xml:ns:domain-1.0\"\n            xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n            xsi:schemaLocation=\"urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd\">\n            <domain:name>" . htmlspecialchars($domain_name) . "</domain:name>\n            <domain:period unit=\"y\">" . htmlspecialchars($params["regperiod"]) . "</domain:period>\n            <domain:authInfo><domain:pw>" . htmlspecialchars($transfersecret) . "</domain:pw></domain:authInfo>\n           </domain:transfer>\n          </transfer>\n          <extension>\n           <trn:transfer xmlns:trn=\"epp.101domain.com:transfer-ext-1.0\">";
        $xml .= "<trn:ns>";
        for ($x = 1; $x <= 13; $x++) {
            if (!empty($params["ns" . $x])) {
                $xml .= "<trn:hostObj>" . htmlspecialchars($params["ns" . $x]) . "</trn:hostObj>";
            }
        }
        $xml .= "</trn:ns>";
        $xml .= "\n             <trn:registrant>" . htmlspecialchars($registrant->handle) . "</trn:registrant>\n             <trn:contact type=\"admin\">" . htmlspecialchars($admin->handle) . "</trn:contact>\n             <trn:contact type=\"tech\">" . htmlspecialchars($tech->handle) . "</trn:contact>\n             <trn:contact type=\"billing\">" . htmlspecialchars($billing->handle) . "</trn:contact>\n           </trn:transfer>\n          </extension>\n         </command>\n        </epp>";
        $nameservers = array();
        for ($x = 1; $x <= 13; $x++) {
            if (!empty($params["ns" . $x])) {
                $nameservers[] = $params["ns" . $x];
            }
        }
        m101domain_command($params, $xml, "Register Domain");
        return array("error" => "");
    } catch (M101Domain\Exception\Error $e) {
        $values = array("error" => $e->getMessage());
        return $values;
    }
}
function m101domain_RenewDomain($params)
{
    $tld = $params["tld"];
    $sld = $params["sld"];
    $registrationPeriod = $params["regperiod"];
    try {
        $domain = m101domain_eppdomaininfo($params, $sld . "." . $tld);
        $values = array();
        if (m101domain_eppdomainrenew($params, $domain, $registrationPeriod)) {
        }
        return $values;
    } catch (M101Domain\Exception\Error $e) {
        $values = array("error" => $e->getMessage());
        return $values;
    }
}
function m101domain_GetContactDetails($params)
{
    $tld = $params["tld"];
    $sld = $params["sld"];
    try {
        $domain_info = m101domain_eppdomaininfo($params, $sld . "." . $tld);
        $contacts = array_merge(array("registrant" => $domain_info->registrant), $domain_info->contacts);
        $values = array();
        foreach ($contacts as $name => $handle) {
            $contact = m101domain_eppContactInfo($params, $handle);
            foreach ($contact as $n => $v) {
                if (!isset(M101Domain\Contact::$labels[$n])) {
                    continue;
                }
                $values[$name][M101Domain\Contact::$labels[$n]] = $v;
            }
        }
        return $values;
    } catch (M101Domain\Exception\Error $e) {
        return array("error" => $e->getMessage());
    }
}
function m101domain_SaveContactDetails($params)
{
    try {
        list($registrant_handle, $admin_handle, $tech_handle, $bill_handle) = m101domain_contacthandles($params);
        $handles = array("registrant" => $registrant_handle, "admin" => $admin_handle, "tech" => $tech_handle, "billing" => $bill_handle);
        foreach ($params["contactdetails"] as $contact_type => $data) {
            if (isset($handles[$contact_type])) {
                $contact = new M101Domain\Contact();
                $contact->handle = $handles[$contact_type];
                foreach ($data as $field_name => $value) {
                    $vn = array_search($field_name, M101Domain\Contact::$labels);
                    if ($vn) {
                        $contact->{$vn} = $value;
                    }
                }
                m101domain_eppUpdateContact($params, $contact);
            }
        }
        return array();
    } catch (M101Domain\Exception\Error $e) {
        return array("error" => $e->getMessage());
    }
}
function m101domain_GetEPPCode($params)
{
    $tld = $params["tld"];
    $sld = $params["sld"];
    try {
        $domain = m101domain_eppdomaininfo($params, $sld . "." . $tld);
        return array("eppcode" => $domain->key);
    } catch (M101Domain\Exception\Error $e) {
        $values = array("error" => $e->getMessage());
        return $values;
    }
}
function m101domain_RegisterNameserver($params)
{
    $nameserver = $params["nameserver"];
    $ipaddress = $params["ipaddress"];
    $type = "v4";
    if (preg_match("/^\\s*((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)(\\.(25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)(\\.(25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)(\\.(25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)(\\.(25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)(\\.(25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)(\\.(25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)(\\.(25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)){3}))|:)))(%.+)?\\s*\$/", $ipaddress)) {
        $type = "v6";
    }
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n<epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n xsi:schemaLocation=\"urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd\">\n <command>\n  <create>\n   <host:create xmlns:host=\"urn:ietf:params:xml:ns:host-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n    xsi:schemaLocation=\"urn:ietf:params:xml:ns:host-1.0 host-1.0.xsd\">\n    <host:name>" . htmlspecialchars($nameserver) . "</host:name>\n    <host:addr ip=\"" . $type . "\">" . htmlspecialchars($ipaddress) . "</host:addr>\n   </host:create>\n  </create>\n </command>\n</epp>";
    try {
        m101domain_command($params, $xml, "Register Nameserver");
        return array("error" => "");
    } catch (M101Domain\Exception\Error $e) {
        $values = array("error" => $e->getMessage());
        return $values;
    }
}
function m101domain_ModifyNameserver($params)
{
    $nameserver = $params["nameserver"];
    $currentIpaddress = $params["currentipaddress"];
    $newIpaddress = $params["newipaddress"];
    $old_type = "v4";
    if (preg_match("/^\\s*((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)(\\.(25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)(\\.(25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)(\\.(25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)(\\.(25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)(\\.(25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)(\\.(25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)(\\.(25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)){3}))|:)))(%.+)?\\s*\$/", $currentIpaddress)) {
        $old_type = "v6";
    }
    $new_type = "v4";
    if (preg_match("/^\\s*((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)(\\.(25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)(\\.(25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)(\\.(25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)(\\.(25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)(\\.(25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)(\\.(25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)(\\.(25[0-5]|2[0-4]\\d|1\\d\\d|[1-9]?\\d)){3}))|:)))(%.+)?\\s*\$/", $newIpaddress)) {
        $new_type = "v6";
    }
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n    <epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n     xsi:schemaLocation=\"urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd\">\n     <command>\n      <update>\n       <host:update xmlns:host=\"urn:ietf:params:xml:ns:host-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n        xsi:schemaLocation=\"urn:ietf:params:xml:ns:host-1.0 host-1.0.xsd\">\n        <host:name>" . htmlspecialchars($nameserver) . "</host:name>\n        <host:add>\n         <host:addr ip=\"" . $old_type . "\">" . htmlspecialchars($newIpaddress) . "</host:addr>\n        </host:add>\n        <host:rem>\n         <host:addr ip=\"" . $new_type . "\">" . htmlspecialchars($currentIpaddress) . "</host:addr>\n        </host:rem>\n       </host:update>\n      </update>\n     </command>\n    </epp>";
    try {
        m101domain_command($params, $xml, "Update Nameserver");
        return array("error" => "");
    } catch (M101Domain\Exception\Error $e) {
        $values = array("error" => $e->getMessage());
        return $values;
    }
}
function m101domain_DeleteNameserver($params)
{
    $nameserver = $params["nameserver"];
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\"?>\n<epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n xsi:schemaLocation=\"urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd\">\n <command>\n  <delete>\n   <host:delete xmlns:host=\"urn:ietf:params:xml:ns:host-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n    xsi:schemaLocation=\"urn:ietf:params:xml:ns:host-1.0 host-1.0.xsd\">\n    <host:name>" . htmlspecialchars($nameserver) . "</host:name>\n   </host:delete>\n  </delete>\n </command>\n</epp>";
    try {
        m101domain_command($params, $xml, "Delete Nameserver");
        return array("error" => "");
    } catch (M101Domain\Exception\Error $e) {
        $values = array("error" => $e->getMessage());
        return $values;
    }
}
function m101domain_Sync($params)
{
    $sld = $params["sld"];
    $tld = $params["tld"];
    try {
        $info = m101domain_eppdomaininfo($params, $sld . "." . $tld);
        $values = array("expirydate" => date("Y-m-d", $info->ex_date), "active" => true, "expired" => false);
        if (in_array("pendingCreate", $info->status) || in_array("pendingTransfer", $info->status)) {
            $values["active"] = false;
            $values["expired"] = false;
            unset($values["expirydate"]);
        } else {
            if (in_array("pendingDelete", $info->status) || in_array("redemptionPeriod", $info->status)) {
                $values["active"] = false;
                $values["expired"] = true;
            }
        }
        return $values;
    } catch (M101Domain\Exception\Error $e) {
        $values = array();
        if ($e->getCode() == 2303) {
            $values["active"] = false;
            $values["expired"] = true;
            $values["expirydate"] = "0000-00-00";
            return $values;
        }
        $values = array("error" => $e->getMessage());
        return $values;
    }
}
function m101domain_TransferSync($params)
{
    $sld = $params["sld"];
    $tld = $params["tld"];
    try {
        $values = array("completed" => false, "failed" => false);
        $info = m101domain_eppdomaininfo($params, $sld . "." . $tld);
        if (in_array("pendingCreate", $info->status) || in_array("pendingTransfer", $info->status)) {
        } else {
            if (in_array("pendingDelete", $info->status) || in_array("redemptionPeriod", $info->status)) {
                $values["failed"] = true;
                $values["reason"] = "pendingDelete";
            } else {
                if ($info->ex_date) {
                    $values["completed"] = true;
                    $values["expirydate"] = date("Y-m-d", $info->ex_date);
                }
            }
        }
        return $values;
    } catch (M101Domain\Exception\Error $e) {
        if ($e->getCode() == 2303) {
            $values["failed"] = true;
            $values["reason"] = $e->getMessage();
            return $values;
        }
        $values = array("error" => $e->getMessage());
        return $values;
    }
}
function m101domain_eppRegisterContact($params, M101Domain\Contact $contact)
{
    $contact->normalize();
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n        <epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n         xsi:schemaLocation=\"urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd\">\n         <command>\n          <create>\n           <contact:create xmlns:contact=\"urn:ietf:params:xml:ns:contact-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n            xsi:schemaLocation=\"urn:ietf:params:xml:ns:contact-1.0 contact-1.0.xsd\">\n            <contact:id>" . htmlspecialchars($contact->handle) . "</contact:id>\n            <contact:postalInfo type=\"int\">\n             <contact:name>" . htmlspecialchars($contact->first_name . " " . $contact->last_name) . "</contact:name>\n             <contact:org>" . htmlspecialchars($contact->company ? $contact->company : $contact->first_name . " " . $contact->last_name) . "</contact:org>\n             <contact:addr>\n              <contact:street>" . htmlspecialchars($contact->address1) . "</contact:street>\n              " . ($contact->address2 ? "<contact:street>" . htmlspecialchars($contact->address2) . "</contact:street>" : "") . "\n              " . ($contact->address3 ? "<contact:street>" . htmlspecialchars($contact->address3) . "</contact:street>" : "") . "\n              <contact:city>" . htmlspecialchars($contact->city) . "</contact:city>\n              " . ($contact->state ? "<contact:sp>" . htmlspecialchars($contact->state) . "</contact:sp>" : "") . "\n              " . ($contact->postal ? "<contact:pc>" . htmlspecialchars($contact->postal) . "</contact:pc>" : "") . "\n              <contact:cc>" . htmlspecialchars($contact->country) . "</contact:cc>\n             </contact:addr>\n            </contact:postalInfo>\n            <contact:voice>" . htmlspecialchars($contact->phone) . "</contact:voice>\n            " . ($contact->fax ? "<contact:fax>" . htmlspecialchars($contact->fax) . "</contact:fax>" : "") . "\n            <contact:email>" . htmlspecialchars($contact->email) . "</contact:email>\n           </contact:create>\n          </create>\n         </command>\n        </epp>";
    m101domain_command($params, $xml, "Contact Create");
}
function m101domain_eppUpdateContact($params, M101Domain\Contact $contact)
{
    $contact->normalize();
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n        <epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n         xsi:schemaLocation=\"urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd\">\n         <command>\n          <update>\n           <contact:update xmlns:contact=\"urn:ietf:params:xml:ns:contact-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n            xsi:schemaLocation=\"urn:ietf:params:xml:ns:contact-1.0 contact-1.0.xsd\">\n            <contact:id>" . htmlspecialchars($contact->handle) . "</contact:id>\n            <contact:chg>\n            <contact:postalInfo type=\"int\">\n             <contact:name>" . htmlspecialchars($contact->first_name . " " . $contact->last_name) . "</contact:name>\n             <contact:org>" . htmlspecialchars($contact->company ? $contact->company : $contact->first_name . " " . $contact->last_name) . "</contact:org>\n             <contact:addr>\n              <contact:street>" . htmlspecialchars($contact->address1) . "</contact:street>\n              " . ($contact->address2 ? "<contact:street>" . htmlspecialchars($contact->address2) . "</contact:street>" : "") . "\n              " . ($contact->address3 ? "<contact:street>" . htmlspecialchars($contact->address3) . "</contact:street>" : "") . "\n              <contact:city>" . htmlspecialchars($contact->city) . "</contact:city>\n              " . ($contact->state ? "<contact:sp>" . htmlspecialchars($contact->state) . "</contact:sp>" : "") . "\n              " . ($contact->postal ? "<contact:pc>" . htmlspecialchars($contact->postal) . "</contact:pc>" : "") . "\n              <contact:cc>" . htmlspecialchars($contact->country) . "</contact:cc>\n             </contact:addr>\n            </contact:postalInfo>\n            <contact:voice>" . htmlspecialchars($contact->phone) . "</contact:voice>\n            " . ($contact->fax ? "<contact:fax>" . htmlspecialchars($contact->fax) . "</contact:fax>" : "") . "\n            <contact:email>" . htmlspecialchars($contact->email) . "</contact:email>\n            </contact:chg>\n           </contact:update>\n          </update>\n         </command>\n        </epp>";
    m101domain_command($params, $xml, "Contact Create");
}
function m101domain_eppCheckContact($params, array $handles)
{
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n        <epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd\">\n         <command>\n          <check>\n           <contact:check xmlns:contact=\"urn:ietf:params:xml:ns:contact-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n            xsi:schemaLocation=\"urn:ietf:params:xml:ns:contact-1.0 contact-1.0.xsd\">";
    foreach ($handles as $id) {
        $xml .= "<contact:id>" . htmlspecialchars($id) . "</contact:id>";
    }
    $xml .= "\n           </contact:check>\n          </check>\n         </command>\n        </epp>";
    $res = m101domain_command($params, $xml, "Contact Check");
    $avail = array();
    $node = $res->resData->firstChild;
    while ($node) {
        if ($node->localName == "chkData" && $node->namespaceURI == "urn:ietf:params:xml:ns:contact-1.0") {
            $cd = $node->firstChild;
            while ($cd) {
                if ($cd->localName == "cd" && $cd->namespaceURI == $node->namespaceURI && ($id = m101domain_FindNode($cd, "id", $cd->namespaceURI))) {
                    $a = $id->getAttribute("avail") ? true : false;
                    $id->normalize();
                    $avail[$id->firstChild->nodeValue] = $a;
                }
                $cd = $cd->nextSibling;
            }
        }
        $node = $node->nextSibling;
    }
    return $avail;
}
function m101domain_connect($params)
{
    global $m101domain_session_id;
    if ($m101domain_session_id) {
        return true;
    }
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n xsi:schemaLocation=\"urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd\">\n <command>\n  <login>\n   <clID>" . htmlspecialchars($params["Username"]) . "</clID>\n   <pw>" . htmlspecialchars($params["Password"]) . "</pw>\n   <options>\n    <version>1.0</version>\n    <lang>EN</lang>\n   </options>\n   <svcs>\n    <objURI>urn:ietf:params:xml:ns:contact-1.0</objURI>\n    <objURI>urn:ietf:params:xml:ns:domain-1.0</objURI>\n    <objURI>urn:ietf:params:xml:ns:host-1.0</objURI>\n   </svcs>\n  </login>\n </command>\n</epp>";
    $result = m101domain_command($params, $xml);
    if (1000 <= $result->error_code && $result->error_code <= 1999) {
        return true;
    }
    throw new M101Domain\Exception\Error(sprintf("%s (%d) %s", $result->error_code, $result->error_message, join($result->reason, ", ")));
}
function m101domain_command($params, $xml, $command = "")
{
    global $m101domain_session_id;
    $xml = preg_replace("/\\s+/", " ", $xml);
    $res = new M101Domain\Result();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $params["DisableValidation"] ? 0 : 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $params["DisableValidation"] ? 0 : 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
    curl_setopt($ch, CURLOPT_TIMEOUT, 600);
    if (!$m101domain_session_id) {
        $url = $params["ApiUrl"] . "?clID=" . urlencode($params["Username"]) . "&pw=" . urlencode($params["Password"]) . "&whmcs=1";
    } else {
        $url = $params["ApiUrl"];
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
    if ($m101domain_session_id) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: EPPSESSION=" . $m101domain_session_id, "Expect:"));
    } else {
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Expect:"));
    }
    $result = curl_exec($ch);
    logModuleCall("m101domain", $command, $xml, $result, "", array());
    if ($result === false) {
        $res->error_message = "Failed to connect: " . curl_error($ch);
        $res->error_code = 2400;
        throw new M101Domain\Exception\Error(sprintf("%s (%d) %s", $res->error_message, $res->error_code, join($res->reason, ", ")), $res->error_code);
    }
    if (400 <= ($code = curl_getinfo($ch, CURLINFO_HTTP_CODE)) && $code <= 599) {
        $res->error_code = 2001;
        $res->error_message = "Unexpected HTTP Error code " . $code;
        throw new M101Domain\Exception\Error(sprintf("%s (%d) %s", $res->error_message, $res->error_code, join($res->reason, ", ")), $res->error_code);
    }
    $resultParts = explode("\r\n\r\n", $result, 2);
    list($headers, $body) = $resultParts;
    preg_match("/Set-Cookie:(.+)/i", $headers, $matches);
    $cookies = !empty($matches[1]) ? $matches[1] : NULL;
    if ($cookies) {
        $matches = NULL;
        preg_match("/EPPSESSION=([^;]*)/i", $cookies, $matches);
        if (!empty($matches[1])) {
            $_session_id = trim($matches[1]);
            if (!empty($_session_id)) {
                $m101domain_session_id = $_session_id;
            }
        }
    }
    $res->xml = $xml = new DOMDocument();
    $xml->preserveWhiteSpace = false;
    if (!$xml->loadXML($body)) {
        $res->error_code = 2001;
        $res->error_message = "Failed to parse XML";
        throw new M101Domain\Exception\Error(sprintf("%s (%d) %s", $res->error_message, $res->error_code, join($res->reason, ", ")), $res->error_code);
    }
    $root = $xml->documentElement;
    if (!($response = m101domain_FindNode($root, "response", $root->namespaceURI))) {
        $res->error_code = 2001;
        $res->error_message = "Response Node not in document";
        throw new M101Domain\Exception\Error(sprintf("%s (%d) %s", $res->error_message, $res->error_code, join($res->reason, ", ")), $res->error_code);
    }
    if (!($result = m101domain_FindNode($response, "result", $root->namespaceURI))) {
        $res->error_code = 2001;
        $res->error_message = "Result node not in document";
        throw new M101Domain\Exception\Error(sprintf("%s (%d) %s", $res->error_message, $res->error_code, join($res->reason, ", ")), $res->error_code);
    }
    $res->error_code = (int) $result->getAttribute("code");
    $node = $result->firstChild;
    while ($node) {
        if ($node->localName == "msg" && $node->namespaceURI == $root->namespaceURI) {
            $node->normalize();
            $res->error_message = trim($node->firstChild->nodeValue);
        } else {
            if ($node->localName == "value" && $node->namespaceURI == $root->namespaceURI && ($text = m101domain_FindNode($node, "text", $root->namespaceURI))) {
                $text->normalize();
                $res->reason[] = $text->firstChild->nodeValue;
            }
        }
        $node = $node->nextSibling;
    }
    $res->resData = m101domain_FindNode($response, "resData", $root->namespaceURI);
    $res->extension = m101domain_FindNode($response, "extension", $root->namespaceURI);
    if (2000 <= $res->error_code) {
        throw new M101Domain\Exception\Error(sprintf("%s (%d) %s", $res->error_message, $res->error_code, join($res->reason, ", ")), $res->error_code);
    }
    return $res;
}
function m101domain_FindNode(DOMElement $parent, $name, $namespace_uri = NULL)
{
    $node = $parent->firstChild;
    while ($node) {
        if ($node->localName == $name && (!$namespace_uri || $node->namespaceURI == $namespace_uri)) {
            return $node;
        }
        $node = $node->nextSibling;
    }
}
function m101Domain_createPassword($length)
{
    $number = false;
    $new_k = "";
    for ($x = 0; $x < $length; $x++) {
        $v = rand(1, 24 + 24 + 10);
        if ($v <= 24) {
            $new_k .= chr(ord("a") + $v - 1);
        } else {
            if (24 < $v && $v <= 48) {
                $new_k .= chr(ord("A") + $v - 25);
            } else {
                if (48 < $v) {
                    $new_k .= $v - 48;
                    $number = true;
                }
            }
        }
    }
    if (!$number) {
        $new_k .= rand(0, 10) . rand(0, 10);
    }
    return $new_k;
}
function m101domain_eppcontactinfo($params, $handle)
{
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n        <epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd\">\n         <command>\n          <info>\n           <contact:info xmlns:contact=\"urn:ietf:params:xml:ns:contact-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n            xsi:schemaLocation=\"urn:ietf:params:xml:ns:contact-1.0 contact-1.0.xsd\">\n             <contact:id>" . htmlspecialchars($handle) . "</contact:id>\n           </contact:info>\n          </info>\n         </command>\n        </epp>";
    $ret = m101domain_command($params, $xml, "Contact Info");
    $contact = new M101Domain\Contact();
    if ($chkData = m101domain_findnode($ret->resData, "infData", "urn:ietf:params:xml:ns:contact-1.0")) {
        $node = $chkData->firstChild;
        while ($node) {
            if ($node instanceof DOMElement && $node->namespaceURI == "urn:ietf:params:xml:ns:contact-1.0") {
                switch ($node->localName) {
                    case "id":
                        $node->normalize();
                        $contact->handle = $node->firstChild->nodeValue;
                        break;
                    case "voice":
                        $node->normalize();
                        $contact->phone = $node->firstChild->nodeValue;
                        break;
                    case "fax":
                        $node->normalize();
                        $contact->fax = $node->firstChild->nodeValue;
                        break;
                    case "email":
                        $node->normalize();
                        $contact->email = $node->firstChild->nodeValue;
                        break;
                    case "postalInfo":
                        if ($node->getAttribute("type") == "int") {
                            $postal = $node->firstChild;
                            while ($postal) {
                                if ($postal instanceof DOMElement && $postal->namespaceURI == "urn:ietf:params:xml:ns:contact-1.0") {
                                    switch ($postal->localName) {
                                        case "name":
                                            $postal->normalize();
                                            $spl = explode(" ", trim($postal->firstChild->nodeValue));
                                            $contact->last_name = array_pop($spl);
                                            $contact->first_name = join($spl, " ");
                                            break;
                                        case "org":
                                            $postal->normalize();
                                            $contact->company = $postal->firstChild->nodeValue;
                                            break;
                                        case "addr":
                                            $street = 1;
                                            $addr = $postal->firstChild;
                                            while ($addr) {
                                                if ($addr instanceof DOMElement && $addr->namespaceURI == "urn:ietf:params:xml:ns:contact-1.0") {
                                                    switch ($addr->localName) {
                                                        case "street":
                                                            $addr->normalize();
                                                            $vn = "address" . $street;
                                                            $contact->{$vn} = $addr->firstChild->nodeValue;
                                                            $street++;
                                                            break;
                                                        case "city":
                                                            $addr->normalize();
                                                            $contact->city = $addr->firstChild->nodeValue;
                                                            break;
                                                        case "sp":
                                                            $addr->normalize();
                                                            $contact->state = $addr->firstChild->nodeValue;
                                                            break;
                                                        case "pc":
                                                            $addr->normalize();
                                                            $contact->postal = $addr->firstChild->nodeValue;
                                                            break;
                                                        case "cc":
                                                            $addr->normalize();
                                                            $contact->country = $addr->firstChild->nodeValue;
                                                            break;
                                                    }
                                                }
                                                $addr = $addr->nextSibling;
                                            }
                                            break;
                                    }
                                }
                                $postal = $postal->nextSibling;
                            }
                        }
                        break;
                }
            }
            $node = $node->nextSibling;
        }
        return $contact;
    }
    throw new M101Domain\Exception\Error("Failed to find {urn:ietf:params:xml:ns:contact-1.0}infData");
}
function m101domain_eppdomaininfo($params, $name)
{
    $name = strtolower($name);
    if (isset(M101Domain\Cache::$cache[$name])) {
        return clone M101Domain\Cache::$cache[$name];
    }
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n        <epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd\">\n         <command>\n          <info>\n           <domain:info xmlns:domain=\"urn:ietf:params:xml:ns:domain-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n            xsi:schemaLocation=\"urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd\">\n             <domain:name>" . htmlspecialchars($name) . "</domain:name>\n           </domain:info>\n          </info>\n         </command>\n        </epp>";
    $ret = m101domain_command($params, $xml, "Domain Info");
    $domain = new M101Domain\Domain();
    if ($inf_data = m101domain_findnode($ret->resData, "infData", "urn:ietf:params:xml:ns:domain-1.0")) {
        $node = $inf_data->firstChild;
        while ($node) {
            if ($node instanceof DOMElement && $node->namespaceURI == "urn:ietf:params:xml:ns:domain-1.0") {
                switch ($node->localName) {
                    case "name":
                        $node->normalize();
                        $domain->name = $node->firstChild->nodeValue;
                        break;
                    case "status":
                        $node->normalize();
                        $domain->status[] = $node->getAttribute("s");
                        break;
                    case "registrant":
                        $node->normalize();
                        $domain->registrant = $node->firstChild->nodeValue;
                        break;
                    case "contact":
                        $node->normalize();
                        $domain->contacts[$node->getAttribute("type")] = $node->firstChild->nodeValue;
                        break;
                    case "ns":
                        $ns = $node->firstChild;
                        while ($ns) {
                            if ($ns instanceof DOMElement && $ns->namespaceURI == "urn:ietf:params:xml:ns:domain-1.0") {
                                if ($ns->localName == "hostObj") {
                                    $ns->normalize();
                                    $domain->ns[] = $ns->firstChild->nodeValue;
                                } else {
                                    if ($ns->localName == "hostAttr") {
                                        $_name = m101domain_findnode($ns, "hostName", $ns->namespaceURI);
                                        if ($_name) {
                                            $_name->normalize();
                                            $domain->ns[] = $_name->firstChild->nodeValue;
                                        }
                                    }
                                }
                            }
                            $ns = $ns->nextSibling;
                        }
                        break;
                    case "host":
                        break;
                    case "crDate":
                        $node->normalize();
                        $domain->cr_date = strtotime($node->firstChild->nodeValue);
                        break;
                    case "upDate":
                        $node->normalize();
                        $domain->up_date = strtotime($node->firstChild->nodeValue);
                        break;
                    case "exDate":
                        $node->normalize();
                        $domain->ex_date = strtotime($node->firstChild->nodeValue);
                        break;
                    case "authInfo":
                        if ($pw = m101domain_findnode($node, "pw", $node->namespaceURI)) {
                            $pw->normalize();
                            $domain->key = $pw->firstChild->nodeValue;
                        }
                        break;
                }
            }
            $node = $node->nextSibling;
        }
    }
    M101Domain\Cache::$cache[strtolower($domain->name)] = $domain;
    return $domain;
}
function m101domain_eppdnsinfo($params, $name)
{
    $name = strtolower($name);
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n        <epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd\">\n         <command>\n          <info>\n           <dns:info xmlns:dns=\"epp.101domain.com:dns-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n            xsi:schemaLocation=\"epp.101domain.com:dns-1.0 dns-1.0.xsd\">\n             <dns:name>" . htmlspecialchars($name) . "</dns:name>\n           </dns:info>\n          </info>\n         </command>\n        </epp>";
    try {
        $ret = m101domain_command($params, $xml, "Domain Info");
    } catch (M101Domain\Exception\Error $e) {
        if ($e->getCode() == 2303) {
            return array();
        }
        throw $e;
    }
    $hostRecords = array();
    $name = "";
    if ($inf_data = m101domain_findnode($ret->resData, "infData", "epp.101domain.com:dns-1.0")) {
        $node = $inf_data->firstChild;
        while ($node) {
            if ($node instanceof DOMElement && $node->namespaceURI == "epp.101domain.com:dns-1.0") {
                $node->normalize();
                switch ($node->localName) {
                    case "name":
                        $name = strtoupper(rtrim($node->firstChild->nodeValue, ".")) . ".";
                        break;
                    case "rr":
                        $host_record = array();
                        $child = $node->firstChild;
                        while ($child) {
                            if ($child instanceof DOMElement && $child->namespaceURI == "epp.101domain.com:dns-1.0") {
                                $child->normalize();
                                switch ($child->localName) {
                                    case "name":
                                        $host_record["hostname"] = $child->firstChild->nodeValue;
                                        break;
                                    case "ttl":
                                        break;
                                    case "AAAA":
                                    case "CNAME":
                                    case "NS":
                                    case "TXT":
                                    case "A":
                                        $host_record["type"] = $child->localName;
                                        $host_record["address"] = $child->firstChild->nodeValue;
                                        break;
                                    case "MX":
                                        $host_record["type"] = "MX";
                                        if ($inf_data = m101domain_findnode($child, "priority", "epp.101domain.com:dns-1.0")) {
                                            $inf_data->normalize();
                                            $host_record["priority"] = $inf_data->firstChild->nodeValue;
                                        }
                                        if ($inf_data = m101domain_findnode($child, "addr", "epp.101domain.com:dns-1.0")) {
                                            $inf_data->normalize();
                                            $host_record["address"] = $inf_data->firstChild->nodeValue;
                                        }
                                        break;
                                    case "SRV":
                                        break;
                                }
                            }
                            $child = $child->nextSibling;
                        }
                        $host_record["hostname"] = str_replace($name . ".", "", $host_record["hostname"]);
                        $hostRecords[] = $host_record;
                        break;
                }
            }
            $node = $node->nextSibling;
        }
    }
    return $hostRecords;
}
function m101domain_eppdomainrenew($params, M101Domain\Domain $domain, $period)
{
    $cur_tz = date_default_timezone_get();
    date_default_timezone_set("UTC");
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n        <epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd\">\n         <command>\n          <renew>\n           <domain:renew xmlns:domain=\"urn:ietf:params:xml:ns:domain-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n            xsi:schemaLocation=\"urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd\">\n             <domain:name>" . htmlspecialchars($domain->name) . "</domain:name>\n             <domain:curExpDate>" . date("Y-m-d", $domain->ex_date) . "</domain:curExpDate>\n             <domain:period unit=\"y\">" . htmlspecialchars($period) . "</domain:period>\n           </domain:renew>\n          </renew>\n         </command>\n        </epp>";
    date_default_timezone_set($cur_tz);
    unset(M101Domain\Cache::$cache[strtolower($domain->name)]);
    $ret = m101domain_command($params, $xml, "Domain Renew");
    if ($inf_data = m101domain_findnode($ret->resData, "resData", "urn:ietf:params:xml:ns:domain-1.0")) {
        $node = $inf_data->firstChild;
        while ($node) {
            if ($node instanceof DOMElement && $node->namespaceURI == "urn:ietf:params:xml:ns:domain-1.0") {
                if ($node->localName == "name") {
                    $node->normalize();
                    if (strtolower($node->firstChild->nodeValue) != strtolower($domain->name)) {
                        throw new M101Domain\Exception\Error("Domain doesn't match the domain provided");
                    }
                } else {
                    if ($node->localName == "exDate") {
                        $node->normalize();
                        $domain->ex_date = strtotime($node->firstChild->nodeValue);
                    }
                }
            }
            $node = $node->nextSibling;
        }
    }
    return $ret->error_code == 1000;
}
function m101domain_contacthandles($params)
{
    $registrant_handle = substr(strtolower(sprintf("WHR%s-%s", $params["Username"], $params["domainid"])), 0, 20);
    $admin_handle = substr(strtolower(sprintf("WHA%s-%s", $params["Username"], $params["domainid"])), 0, 20);
    $tech_handle = substr(strtolower(sprintf("WHT%s-%s", $params["Username"], $params["domainid"])), 0, 20);
    $bill_handle = substr(strtolower(sprintf("WHB%s-%s", $params["Username"], $params["domainid"])), 0, 20);
    return array($registrant_handle, $admin_handle, $tech_handle, $bill_handle);
}
function m101domain_epphostinfo($params, $name)
{
    $name = strtolower($name);
    $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n        <epp xmlns=\"urn:ietf:params:xml:ns:epp-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"urn:ietf:params:xml:ns:epp-1.0 epp-1.0.xsd\">\n         <command>\n          <info>\n           <host:info xmlns:host=\"urn:ietf:params:xml:ns:host-1.0\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"\n            xsi:schemaLocation=\"urn:ietf:params:xml:ns:host-1.0 host-1.0.xsd\">\n             <host:name>" . htmlspecialchars($name) . "</host:name>\n           </host:info>\n          </info>\n         </command>\n        </epp>";
    $ret = m101domain_command($params, $xml, "Host Info");
    $host = new M101Domain\Host();
    if ($inf_data = m101domain_findnode($ret->resData, "resData", "urn:ietf:params:xml:ns:host-1.0")) {
        $node = $inf_data->firstChild;
        while ($node) {
            if ($node instanceof DOMElement && $node->namespaceURI == "urn:ietf:params:xml:ns:host-1.0") {
                if ($node->localName == "name") {
                    $node->normalize();
                    if (strtolower($host->name = $node->firstChild->nodeValue) != strtolower($name)) {
                        throw new M101Domain\Exception\Error("Domain doesn't match the domain provided");
                    }
                    $host->name = $node->firstChild->nodeValue;
                } else {
                    if ($node->localName == "crDate") {
                        $node->normalize();
                        $host->cr_date = strtotime($node->firstChild->nodeValue);
                    } else {
                        if ($node->localName == "crID") {
                            $node->normalize();
                            $host->cr_id = $node->firstChild->nodeValue;
                        } else {
                            if ($node->localName == "crID") {
                                $node->normalize();
                                $host->cr_id = $node->firstChild->nodeValue;
                            }
                        }
                    }
                }
            }
            $node = $node->nextSibling;
        }
    }
}

?>