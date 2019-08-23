<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function enomssl_MetaData()
{
    return array("DisplayName" => "eNom SSL Certificates", "APIVersion" => "1.0", "RequiresServer" => false, "AutoGenerateUsernameAndPassword" => false);
}
function enomssl_ConfigOptions(array $params)
{
    if (WHMCS\Mail\Template::where("name", "=", "SSL Certificate Configuration Required")->count() == 0) {
        $template = new WHMCS\Mail\Template();
        $template->type = "product";
        $template->name = "SSL Certificate Configuration Required";
        $template->subject = "SSL Certificate Configuration Required";
        $template->message = "<p>Dear {\$client_name},</p><p>Thank you for your order for an SSL Certificate. Before you can use your certificate, it requires configuration which can be done at the URL below.</p><p>{\$ssl_configuration_link}</p><p>Instructions are provided throughout the process but if you experience any problems or have any questions, please open a ticket for assistance.</p><p>{\$signature}</p>";
        $template->disabled = false;
        $template->custom = false;
        $template->plaintext = false;
        $template->save();
    }
    $enomusername = $enompassword = $testmode = "";
    if ($params["isAddon"]) {
        $addon = WHMCS\Product\Addon::with("moduleConfiguration")->find(App::getFromRequest("id"));
        if ($addon) {
            foreach ($addon->moduleConfiguration as $moduleConfiguration) {
                switch ($moduleConfiguration->settingName) {
                    case "configoption1":
                        $enomusername = $moduleConfiguration->value;
                        break;
                    case "configoption2":
                        $enompassword = $moduleConfiguration->value;
                        break;
                    case "configoption5":
                        $testmode = $moduleConfiguration->value;
                        break;
                }
            }
        }
    } else {
        $product = WHMCS\Product\Product::find(App::getFromRequest("id"));
        if ($product) {
            $enomusername = $product->moduleConfigOption1;
            $enompassword = $product->moduleConfigOption2;
            $testmode = $product->moduleConfigOption5;
        }
    }
    if ($enomusername && $enompassword) {
        $postfields = array();
        $postfields["uid"] = $enomusername;
        $postfields["pw"] = $enompassword;
        $postfields["command"] = "GetCerts";
        $postfields["ResponseType"] = "XML";
        $result = enomssl_call($postfields, $testmode);
        $certtypelist = "";
        foreach ($result["INTERFACE-RESPONSE"]["GETCERTS"]["CERTS"] as $cert => $details) {
            $certcode = $details["PRODCODE"];
            if ($certcode) {
                $certcode = str_replace("-", " ", $certcode);
                $certcode = titleCase($certcode);
                $certtypelist .= $certcode . ",";
            }
        }
        $certtypelist = substr($certtypelist, 0, -1);
        if (!$certtypelist) {
            $certtypelist = "certificate-rapidssl-rapidssl,certificate-geotrust-quickssl,certificate-geotrust-quickssl-premium,certificate-geotrust-truebizid,certificate-geotrust-truebizid-ev,certificate-geotrust-truebizid-wildcard,certificate-verisign-secure-site,certificate-verisign-secure-site-pro,certificate-verisign-secure-site-ev,certificate-verisign-secure-site-pro-ev,certificate-comodo-essential,certificate-comodo-premium-wildcard,certificate-comodo-essential-wildcard,certificate-comodo-ev,certificate-comodo-ev-sgc,certificate-comodo-ucc-dv-1yr-additional-domain,certificate-comodo-ucc-dv-2yr-additional-domain,certificate-comodo-ucc-dv-3yr-additional-domain,certificate-comodo-ucc-ov,certificate-comodo-ucc-ov-1yr-additional-domain,certificate-comodo-ucc-ov-2yr-additional-domain,certificate-comodo-ucc-ov-3yr-additional-domain";
        }
    } else {
        $certtypelist = "Please Enter your Username and Password";
    }
    $configarray = array("Username" => array("Type" => "text", "Size" => "25"), "Password" => array("Type" => "password", "Size" => "25"), "Certificate Type" => array("Type" => "dropdown", "Options" => $certtypelist), "Years" => array("Type" => "dropdown", "Options" => "1,2,3,4,5,6,7,8,9,10"), "Test Mode" => array("Type" => "yesno"));
    return $configarray;
}
function enomssl_CreateAccount($params)
{
    $sslOrder = WHMCS\Service\Ssl::where("serviceid", $params["serviceid"])->where("addon_id", $params["addonId"])->where("module", "enomssl")->first();
    if ($sslOrder) {
        return "An SSL Order already exists for this order";
    }
    $postfields = array();
    $postfields["uid"] = $params["configoption1"];
    $postfields["pw"] = $params["configoption2"];
    $postfields["EmptyCart"] = "On";
    $postfields["command"] = "DeleteFromCart";
    $postfields["ResponseType"] = "XML";
    $clearResult = enomssl_call($postfields, $params["configoption5"]);
    if (!is_array($clearResult) && substr($clearResult, 0, 4) == "CURL") {
        return $clearResult;
    }
    $error = $clearResult["INTERFACE-RESPONSE"]["ERRORS"]["ERR1"];
    if ($error && strpos($error, "There are no items in the cart to be deleted") === false) {
        return $error;
    }
    $certtype = $params["configoptions"]["Certificate Type"] ? $params["configoptions"]["Certificate Type"] : $params["configoption3"];
    $certyears = $params["configoptions"]["Years"] ? $params["configoptions"]["Years"] : $params["configoption4"];
    $certtype = str_replace(" ", "-", strtolower($certtype));
    $postfields = array();
    $postfields["uid"] = $params["configoption1"];
    $postfields["pw"] = $params["configoption2"];
    $postfields["ProductType"] = $certtype;
    $postfields["Quantity"] = $certyears;
    $postfields["ClearItems"] = "yes";
    $postfields["command"] = "AddToCart";
    $postfields["ResponseType"] = "XML";
    $result = enomssl_call($postfields, $params["configoption5"]);
    if (!is_array($result) && substr($result, 0, 4) == "CURL") {
        return $result;
    }
    $error = $result["INTERFACE-RESPONSE"]["ERRORS"]["ERR1"];
    if ($error) {
        return $error;
    }
    $postfields = array();
    $postfields["uid"] = $params["configoption1"];
    $postfields["pw"] = $params["configoption2"];
    $postfields["command"] = "InsertNewOrder";
    $postfields["ResponseType"] = "XML";
    $result = enomssl_call($postfields, $params["configoption5"]);
    $error = $result["INTERFACE-RESPONSE"]["ERRORS"]["ERR1"];
    if ($error) {
        return $error;
    }
    $orderid = $result["INTERFACE-RESPONSE"]["ORDERID"];
    $sslOrder = WHMCS\Service\Ssl::firstOrNew(array("userid" => $params["clientsdetails"]["userid"], "serviceid" => $params["serviceid"], "addon_id" => $params["addonId"], "module" => "enomssl"));
    $sslOrder->remoteId = $orderid;
    $sslOrder->certificateType = $certtype;
    $sslOrder->status = WHMCS\Service\Ssl::STATUS_AWAITING_CONFIGURATION;
    $sslOrder->save();
    sendMessage("SSL Certificate Configuration Required", $params["serviceid"], array("ssl_configuration_link" => $sslOrder->getConfigurationUrl()));
    return "success";
}
function enomssl_TerminateAccount($params)
{
    $sslOrder = WHMCS\Service\Ssl::where("serviceid", $params["serviceid"])->where("addon_id", $params["addonId"])->where("status", WHMCS\Service\Ssl::STATUS_AWAITING_CONFIGURATION)->where("module", "enomssl")->first();
    if (!$sslOrder) {
        return "SSL Either not Provisioned or Not Awaiting Configuration so unable to cancel";
    }
    $sslOrder->status = WHMCS\Service\Ssl::STATUS_CANCELLED;
    $sslOrder->save();
    return "success";
}
function enomssl_AdminCustomButtonArray()
{
    $buttonarray = array("Resend Configuration Email" => "resend");
    return $buttonarray;
}
function enomssl_resend($params)
{
    $sslOrder = WHMCS\Service\Ssl::where("serviceid", $params["serviceid"])->where("addon_id", $params["addonId"])->where("status", WHMCS\Service\Ssl::STATUS_AWAITING_CONFIGURATION)->where("module", "enomssl")->first();
    if (!$sslOrder) {
        return "No SSL Order exists for this product";
    }
    sendMessage("SSL Certificate Configuration Required", $params["serviceid"], array("ssl_configuration_link" => $sslOrder->getConfigurationUrl()));
    return "success";
}
function enomssl_ClientArea($params)
{
    global $_LANG;
    $sslOrder = WHMCS\Service\Ssl::with("service", "addon")->where("serviceid", $params["serviceid"])->where("addon_id", $params["addonId"])->where("module", "enomssl")->first();
    $output = "";
    if ($sslOrder) {
        $id = $sslOrder->id;
        $provisiondate = $sslOrder->addonId ? $sslOrder->addon->registrationDate : $sslOrder->service->registrationDate;
        $status = $sslOrder->status;
        $provisiondate = fromMySQLDate($provisiondate);
        $status .= " - <a href=\"configuressl.php?cert=" . md5($id) . "\">" . $_LANG["sslconfigurenow"] . "</a>";
        $output = "<div align=\"left\">\n<table width=\"100%\">\n<tr><td width=\"150\" class=\"fieldlabel\">" . $_LANG["sslprovisioningdate"] . ":</td><td>" . $provisiondate . "</td></tr>\n<tr><td class=\"fieldlabel\">" . $_LANG["sslstatus"] . ":</td><td>" . $status . "</td></tr>\n</table>\n</div>";
    }
    return $output;
}
function enomssl_AdminServicesTabFields($params)
{
    $sslOrder = WHMCS\Service\Ssl::where("serviceid", $params["serviceid"])->where("addon_id", $params["addonId"])->where("module", "enomssl")->first();
    if (!$sslOrder) {
        $remoteid = "-";
        $status = "Not Yet Provisioned";
    } else {
        $remoteid = $sslOrder->remoteId;
        $status = $sslOrder->status;
    }
    $fieldsarray = array("Enom Order ID" => $remoteid, "SSL Configuration Status" => $status);
    return $fieldsarray;
}
function enomssl_SSLStepOne($params)
{
    $sslOrder = $params["sslOrder"];
    $orderid = $params["remoteid"];
    $values = array();
    if (!$_SESSION["enomsslcert"][$orderid]["id"]) {
        $postfields = array();
        $postfields["uid"] = $params["configoption1"];
        $postfields["pw"] = $params["configoption2"];
        $postfields["command"] = "CertGetCerts";
        $postfields["ResponseType"] = "XML";
        $result = enomssl_call($postfields, $params["configoption5"]);
        $values["error"] = $result["INTERFACE-RESPONSE"]["ERRORS"]["ERR1"];
        if ($values["error"]) {
            return $values;
        }
        $cert_allowconfig = false;
        foreach ($result["INTERFACE-RESPONSE"]["CERTGETCERTS"]["CERTS"] as $certificate) {
            $temp_cert_id = $certificate["CERTID"];
            $temp_cert_name = $certificate["PRODDESC"];
            $temp_cert_status = $certificate["CERTSTATUS"];
            $temp_cert_orderid = $certificate["ORDERID"];
            $temp_cert_orderdate = $certificate["ORDERDATE"];
            $temp_cert_validityperiod = $certificate["VALIDITYPERIOD"];
            if ($temp_cert_orderid == $orderid) {
                $cert_id = $temp_cert_id;
                $cert_name = $temp_cert_name;
                $cert_orderid = $temp_cert_orderid;
                $cert_orderdate = $temp_cert_orderdate;
                $cert_validityperiod = $temp_cert_validityperiod;
                if ($temp_cert_status == "Awaiting Configuration" || $temp_cert_status == "Rejected by Customer") {
                    $cert_allowconfig = true;
                }
            }
        }
        if (!$cert_allowconfig) {
            $sslOrder->status = WHMCS\Service\Ssl::STATUS_COMPLETED;
        } else {
            $sslOrder->status = WHMCS\Service\Ssl::STATUS_AWAITING_CONFIGURATION;
        }
        $sslOrder->save();
        $_SESSION["enomsslcert"][$orderid]["id"] = $cert_id;
    } else {
        $cert_id = $_SESSION["enomsslcert"][$orderid]["id"];
    }
    $postfields = array();
    $postfields["uid"] = $params["configoption1"];
    $postfields["pw"] = $params["configoption2"];
    $postfields["CertID"] = $cert_id;
    $postfields["command"] = "CertGetCertDetail";
    $postfields["ResponseType"] = "XML";
    $result = enomssl_call($postfields, $params["configoption5"]);
    $values["error"] = $result["INTERFACE-RESPONSE"]["ERRORS"]["ERR1"];
    if ($values["error"]) {
        return $values;
    }
    $values["displaydata"]["Domain"] = $result["INTERFACE-RESPONSE"]["CERTGETCERTDETAIL"]["DOMAINNAME"];
    $values["displaydata"]["Validity Period"] = $result["INTERFACE-RESPONSE"]["CERTGETCERTDETAIL"]["VALIDITYPERIOD"] . " Months";
    $values["displaydata"]["Expiration Date"] = $result["INTERFACE-RESPONSE"]["CERTGETCERTDETAIL"]["EXPIRATIONDATE"];
    $certtype = $params["configoptions"]["Certificate Type"] ? $params["configoptions"]["Certificate Type"] : $params["configoption3"];
    $certtype = str_replace(" ", "-", strtolower($certtype));
    if (stristr($certtype, "-ucc-")) {
        $values["additionalfields"]["Additional Certificate Configuration"] = array("ucc_domains" => array("FriendlyName" => "Domain Name List", "Type" => "textarea", "Rows" => "4", "Description" => "A list of Domains for this Certificate. One per line. Include the CSR Domain.", "Required" => true), "ucc_emails" => array("FriendlyName" => "Approval Emails List", "Type" => "textarea", "Rows" => "4", "Description" => "A list of Approval Emails for this Certificate. One per line for each domain.", "Required" => true));
    }
    return $values;
}
function enomssl_SSLStepTwo($params)
{
    $orderid = $params["remoteid"];
    $cert_id = $_SESSION["enomsslcert"][$orderid]["id"];
    $webservertype = $params["servertype"];
    $csr = $params["csr"];
    $firstname = $params["firstname"];
    $lastname = $params["lastname"];
    $organisationname = $params["orgname"];
    $jobtitle = $params["jobtitle"];
    $emailaddress = $params["email"];
    $address1 = $params["address1"];
    $address2 = $params["address2"];
    $city = $params["city"];
    $state = $params["state"];
    $postcode = $params["postcode"];
    $country = $params["country"];
    $phonenumber = $params["phonenumber"];
    $faxnumber = $params["faxnumber"];
    $certificateType = $params["configoptions"]["Certificate Type"] ? $params["configoptions"]["Certificate Type"] : $params["configoption3"];
    $certificateType = str_replace(" ", "-", strtolower($certificateType));
    $values = array();
    $postfields = array();
    $postfields["uid"] = $params["configoption1"];
    $postfields["pw"] = $params["configoption2"];
    $postfields["CertID"] = $cert_id;
    $postfields["WebServerType"] = $webservertype;
    $postfields["CSR"] = $csr;
    $contacttypes = array("Admin", "Tech", "Billing");
    foreach ($contacttypes as $contacttype) {
        $postfields[$contacttype . "FName"] = $firstname;
        $postfields[$contacttype . "LName"] = $lastname;
        $postfields[$contacttype . "OrgName"] = $organisationname;
        $postfields[$contacttype . "JobTitle"] = $jobtitle;
        $postfields[$contacttype . "Address1"] = $address1;
        $postfields[$contacttype . "Address2"] = $address2;
        $postfields[$contacttype . "City"] = $city;
        if ($country == "US") {
            $postfields[$contacttype . "State"] = $state;
        } else {
            $postfields[$contacttype . "Province"] = $state;
        }
        $postfields[$contacttype . "PostalCode"] = $postcode;
        $postfields[$contacttype . "Country"] = $country;
        $postfields[$contacttype . "Phone"] = $phonenumber;
        $postfields[$contacttype . "Fax"] = $faxnumber;
        $postfields[$contacttype . "EmailAddress"] = $emailaddress;
    }
    $showApprovalEmails = true;
    if (stristr($certificateType, "-ucc-")) {
        $count = 0;
        $uccDomainEmails = explode("\r\n", $params["fields"]["ucc_emails"]);
        foreach (explode("\r\n", $params["fields"]["ucc_domains"]) as $key => $domain) {
            $count++;
            $postfields["UCCDomainList" . $count] = $domain;
            $postfields["UCCEmailList" . $count] = $uccDomainEmails[$key];
        }
        $postfields["DomainListNumber"] = $count;
        $showApprovalEmails = false;
    }
    $postfields["command"] = "CertConfigureCert";
    $postfields["ResponseType"] = "XML";
    $result = enomssl_call($postfields, $params["configoption5"]);
    $values["error"] = $result["INTERFACE-RESPONSE"]["ERRORS"]["ERR1"];
    if ($values["error"]) {
        return $values;
    }
    $approveremailsarray = array();
    if ($showApprovalEmails) {
        foreach ($result["INTERFACE-RESPONSE"]["CERTCONFIGURECERT"] as $k => $v) {
            if (substr($k, 0, 8) == "APPROVER") {
                $approver = trim($v["APPROVEREMAIL"]);
                if ($approver) {
                    $approveremailsarray[] = $approver;
                }
            }
        }
    }
    $values["approveremails"] = $approveremailsarray;
    $postfields = array();
    $postfields["uid"] = $params["configoption1"];
    $postfields["pw"] = $params["configoption2"];
    $postfields["CertID"] = $cert_id;
    $postfields["command"] = "CertGetCertDetail";
    $postfields["ResponseType"] = "XML";
    $result = enomssl_call($postfields, $params["configoption5"]);
    $values["error"] = $result["INTERFACE-RESPONSE"]["ERRORS"]["ERR1"];
    if ($values["error"]) {
        return $values;
    }
    $values["displaydata"]["Domain"] = $result["INTERFACE-RESPONSE"]["CERTGETCERTDETAIL"]["DOMAINNAME"];
    $values["displaydata"]["Validity Period"] = $result["INTERFACE-RESPONSE"]["CERTGETCERTDETAIL"]["VALIDITYPERIOD"] . " Months";
    $values["displaydata"]["Expiration Date"] = $result["INTERFACE-RESPONSE"]["CERTGETCERTDETAIL"]["EXPIRATIONDATE"];
    $params["model"]->serviceProperties->save(array("domain" => $values["displaydata"]["Domain"]));
    $postfields = array();
    $postfields["uid"] = $params["configoption1"];
    $postfields["pw"] = $params["configoption2"];
    $postfields["CertID"] = $cert_id;
    $postfields["CSR"] = $csr;
    $postfields["command"] = "CertParseCSR";
    $postfields["ResponseType"] = "XML";
    $result = enomssl_call($postfields, $params["configoption5"]);
    $values["error"] = $result["INTERFACE-RESPONSE"]["ERRORS"]["ERR1"];
    if ($values["error"]) {
        return $values;
    }
    $values["displaydata"]["Organization"] = $result["INTERFACE-RESPONSE"]["CERTPARSECSR"]["ORGANIZATION"];
    $values["displaydata"]["Organization Unit"] = $result["INTERFACE-RESPONSE"]["CERTPARSECSR"]["ORGANIZATIONUNIT"];
    $values["displaydata"]["Email"] = $result["INTERFACE-RESPONSE"]["CERTPARSECSR"]["EMAIL"];
    $values["displaydata"]["Locality"] = $result["INTERFACE-RESPONSE"]["CERTPARSECSR"]["LOCALITY"];
    $values["displaydata"]["State"] = $result["INTERFACE-RESPONSE"]["CERTPARSECSR"]["STATE"];
    $values["displaydata"]["Country"] = $result["INTERFACE-RESPONSE"]["CERTPARSECSR"]["COUNTRY"];
    return $values;
}
function enomssl_SSLStepThree($params)
{
    $orderid = $params["remoteid"];
    $cert_id = $_SESSION["enomsslcert"][$orderid]["id"];
    $webservertype = $params["servertype"];
    $csr = $params["csr"];
    $firstname = $params["firstname"];
    $lastname = $params["lastname"];
    $organisationname = $params["organisationname"];
    $jobtitle = $params["jobtitle"];
    $emailaddress = $params["email"];
    $address1 = $params["address1"];
    $address2 = $params["address2"];
    $city = $params["city"];
    $state = $params["state"];
    $postcode = $params["postcode"];
    $country = $params["country"];
    $phonenumber = $params["phonenumber"];
    $faxnumber = $params["faxnumber"];
    $uccDomains = isset($params["fields"]["ucc_domains"]) ? $params["fields"]["ucc_domains"] : "";
    $uccEmails = isset($params["fields"]["ucc_emails"]) ? $params["fields"]["ucc_emails"] : "";
    $approveremail = $params["approveremail"];
    $cert_id = $_SESSION["enomsslcert"][$orderid]["id"];
    $postfields = array();
    $postfields["uid"] = $params["configoption1"];
    $postfields["pw"] = $params["configoption2"];
    $postfields["CertID"] = $cert_id;
    $postfields["CSR"] = $csr;
    $postfields["command"] = "CertParseCSR";
    $postfields["ResponseType"] = "XML";
    $result = enomssl_call($postfields, $params["configoption5"]);
    $csr_organization = $result["INTERFACE-RESPONSE"]["CERTPARSECSR"]["ORGANIZATION"];
    $csr_organizationunit = $result["INTERFACE-RESPONSE"]["CERTPARSECSR"]["ORGANIZATIONUNIT"];
    $csr_email = $result["INTERFACE-RESPONSE"]["CERTPARSECSR"]["EMAIL"];
    $csr_locality = $result["INTERFACE-RESPONSE"]["CERTPARSECSR"]["LOCALITY"];
    $csr_state = $result["INTERFACE-RESPONSE"]["CERTPARSECSR"]["STATE"];
    $csr_country = $result["INTERFACE-RESPONSE"]["CERTPARSECSR"]["COUNTRY"];
    $postfields = array();
    $postfields["uid"] = $params["configoption1"];
    $postfields["pw"] = $params["configoption2"];
    if ($params["approveremail"]) {
        $postfields["ApproverEmail"] = $params["approveremail"];
    }
    $postfields["CertID"] = $cert_id;
    $postfields["CSRAddress1"] = $address1;
    $postfields["CSRPostalCode"] = $postcode;
    if ($csr_organization) {
        $postfields["CSROrganization"] = $csr_organization;
    }
    if ($csr_organizationunit) {
        $postfields["CSROrganizationUnit"] = $csr_organizationunit;
    }
    if ($csr_locality) {
        $postfields["CSRLocality"] = $csr_locality;
    }
    if ($csr_state) {
        $postfields["CSRStateProvince"] = $csr_state;
    }
    if ($csr_country) {
        $postfields["CSRCountry"] = $csr_country;
    }
    if ($uccDomains) {
        $count = 0;
        $uccEmails = explode("\r\n", $uccEmails);
        foreach (explode("\r\n", $uccDomains) as $key => $domain) {
            $count++;
            $postfields["UCCDomainList" . $count] = $domain;
            $postfields["UCCEmailList" . $count] = $uccEmails[$key];
        }
        $postfields["DomainListNumber"] = $count;
    }
    $postfields["command"] = "CertPurchaseCert";
    $postfields["ResponseType"] = "XML";
    $result = enomssl_call($postfields, $params["configoption5"]);
    $values["error"] = $result["INTERFACE-RESPONSE"]["ERRORS"]["ERR1"];
    if ($values["error"]) {
        return $values;
    }
    unset($_SESSION["enomsslcert"]);
    return $values;
}
function enomssl_call($fields, $testmode = "")
{
    $url = $testmode ? "resellertest.enom.com" : "reseller.enom.com";
    $query_string = "";
    $whmcsVersion = App::getVersion();
    $fields["Engine"] = "WHMCS" . $whmcsVersion->getMajor() . "." . $whmcsVersion->getMinor();
    foreach ($fields as $k => $v) {
        $query_string .= (string) $k . "=" . urlencode($v) . "&";
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://" . $url . "/interface.asp");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 100);
    $data = curl_exec($ch);
    if (curl_error($ch)) {
        return "CURL Error: " . curl_errno($ch) . " - " . curl_error($ch);
    }
    curl_close($ch);
    $result = XMLtoARRAY($data);
    logModuleCall("enomssl", $fields["command"], $fields, $result, "", array($fields["uid"], $fields["pw"]));
    return $result;
}

?>