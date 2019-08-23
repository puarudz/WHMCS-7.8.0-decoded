<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function checkDomain($domain)
{
    global $domainparts;
    if (preg_match("/^[a-z0-9][a-z0-9\\-]+[a-z0-9](\\.[a-z]{2,4})+\$/i", $domain)) {
        $domainparts = explode(".", $domain, 2);
        return true;
    }
    return false;
}
function getRegistrarsDropdownMenu($registrar, $name = "registrar")
{
    $code = "<select name=\"" . $name . "\" class=\"form-control select-inline\" id=\"registrarsDropDown\">" . "<option value=\"\">" . AdminLang::trans("global.none") . "</option>";
    foreach ((new WHMCS\Module\Registrar())->getActiveModules() as $module) {
        $code .= "<option value=\"" . $module . "\"";
        if ($registrar == $module) {
            $code .= " selected";
        }
        $code .= ">" . ucfirst($module) . "</option>";
    }
    $code .= "</select>";
    return $code;
}
function loadRegistrarModule($registrar)
{
    if (function_exists($registrar . "_getConfigArray")) {
        return true;
    }
    $module = new WHMCS\Module\Registrar();
    return $module->load($registrar);
}
function RegCallFunction($params, $function, $noarr = false)
{
    $registrar = $params["registrar"];
    $hookResults = run_hook("PreRegistrar" . $function, array("params" => $params));
    try {
        if (processHookResults($registrar, $function, $hookResults)) {
            return array();
        }
    } catch (Exception $e) {
        return array("error" => $e->getMessage());
    }
    $functionExists = $functionSuccessful = false;
    $module = new WHMCS\Module\Registrar();
    $module->setDomainID($params["domainid"]);
    $module->load($registrar);
    $queueFunctions = array("IDProtectToggle", "RegisterDomain", "RenewDomain", "TransferDomain");
    if ($module->functionExists($function)) {
        $functionExists = true;
        $values = $module->call($function, $params);
        if (!is_array($values) && !$noarr) {
            $values = array();
        }
        if (empty($values["error"])) {
            if (in_array($function, $queueFunctions)) {
                WHMCS\Module\Queue::resolve("domain", $params["domainid"], $registrar, $function);
            }
            $functionSuccessful = true;
        } else {
            if (in_array($function, $queueFunctions)) {
                WHMCS\Module\Queue::add("domain", $params["domainid"], $registrar, $function, $values["error"]);
            }
        }
    } else {
        $values = array("na" => true);
    }
    $vars = array("params" => $params, "results" => $values, "functionExists" => $functionExists, "functionSuccessful" => $functionSuccessful);
    $hookResults = run_hook("AfterRegistrar" . $function, $vars);
    try {
        if (processHookResults($registrar, $function, $hookResults)) {
            return array();
        }
    } catch (Exception $e) {
        return array("error" => $e->getMessage());
    }
    return $values;
}
function getRegistrarConfigOptions($registrar)
{
    $module = new WHMCS\Module\Registrar();
    $module->load($registrar);
    return $module->getSettings();
}
function RegGetNameservers($params)
{
    return regcallfunction($params, "GetNameservers");
}
function RegSaveNameservers($params)
{
    for ($i = 1; $i <= 5; $i++) {
        $params["ns" . $i] = trim($params["ns" . $i]);
    }
    $values = regcallfunction($params, "SaveNameservers");
    if (!$values) {
        return false;
    }
    $userid = get_query_val("tbldomains", "userid", array("id" => $params["domainid"]));
    if ($values["error"]) {
        logActivity("Domain Registrar Command: Save Nameservers - Failed: " . $values["error"] . " - Domain ID: " . $params["domainid"], $userid);
    } else {
        logActivity("Domain Registrar Command: Save Nameservers - Successful", $userid);
    }
    return $values;
}
function RegGetRegistrarLock($params)
{
    $values = regcallfunction($params, "GetRegistrarLock", 1);
    if (is_array($values)) {
        return "";
    }
    return $values;
}
function RegSaveRegistrarLock($params)
{
    $values = regcallfunction($params, "SaveRegistrarLock");
    if (!$values) {
        return false;
    }
    $userid = get_query_val("tbldomains", "userid", array("id" => $params["domainid"]));
    if ($values["error"]) {
        logActivity("Domain Registrar Command: Toggle Registrar Lock - Failed: " . $values["error"] . " - Domain ID: " . $params["domainid"], $userid);
    } else {
        logActivity("Domain Registrar Command: Toggle Registrar Lock - Successful", $userid);
    }
    return $values;
}
function RegGetURLForwarding($params)
{
    return regcallfunction($params, "GetURLForwarding");
}
function RegSaveURLForwarding($params)
{
    return regcallfunction($params, "SaveURLForwarding");
}
function RegGetEmailForwarding($params)
{
    return regcallfunction($params, "GetEmailForwarding");
}
function RegSaveEmailForwarding($params)
{
    return regcallfunction($params, "SaveEmailForwarding");
}
function RegGetDNS($params)
{
    return regcallfunction($params, "GetDNS");
}
function RegSaveDNS($params)
{
    return regcallfunction($params, "SaveDNS");
}
function RegRenewDomain($params)
{
    $domainid = $params["domainid"];
    $result = select_query("tbldomains", "", array("id" => $domainid));
    $data = mysql_fetch_array($result);
    $userid = $data["userid"];
    $domain = $data["domain"];
    $orderid = $data["orderid"];
    $registrar = $data["registrar"];
    $registrationperiod = $data["registrationperiod"];
    $dnsmanagement = $data["dnsmanagement"] ? true : false;
    $emailforwarding = $data["emailforwarding"] ? true : false;
    $idprotection = $data["idprotection"] ? true : false;
    $domainObj = new WHMCS\Domains\Domain($domain);
    $params["registrar"] = $registrar;
    $params["sld"] = $domainObj->getSLD();
    $params["tld"] = $domainObj->getTLD();
    $params["regperiod"] = $registrationperiod;
    $params["dnsmanagement"] = $dnsmanagement;
    $params["emailforwarding"] = $emailforwarding;
    $params["idprotection"] = $idprotection;
    $params["isInGracePeriod"] = $data["status"] == WHMCS\Domain\Status::GRACE;
    $params["isInRedemptionGracePeriod"] = $data["status"] == WHMCS\Domain\Status::REDEMPTION;
    $params["premiumEnabled"] = (bool) (int) WHMCS\Config\Setting::getValue("PremiumDomains");
    if ($params["premiumEnabled"] && $data["is_premium"]) {
        $params["premiumCost"] = WHMCS\Domain\Extra::whereDomainId($domainid)->whereName("registrarRenewalCostPrice")->value("value");
    }
    $params["domainObj"] = $domainObj;
    $values = regcallfunction($params, "RenewDomain");
    if (!is_array($values)) {
        return false;
    }
    if ($values["na"]) {
        return array("error" => "Registrar Function Not Supported");
    }
    if ($values["error"]) {
        logActivity("Domain Renewal Failed - Domain ID: " . $domainid . " - Domain: " . $domain . " - Error: " . $values["error"], $userid);
        run_hook("AfterRegistrarRenewalFailed", array("params" => $params, "error" => $values["error"]));
    } else {
        $expiryInfo = WHMCS\Database\Capsule::table("tbldomains")->where("id", "=", $domainid)->first(array("expirydate", "registrationperiod"));
        $expirydate = $expiryInfo->expirydate;
        $registrationperiod = $expiryInfo->registrationperiod;
        $year = substr($expirydate, 0, 4);
        $month = substr($expirydate, 5, 2);
        $day = substr($expirydate, 8, 2);
        if (strpos($expirydate, "0000-00-00") === false) {
            $newExpiryDate = WHMCS\Carbon::createFromDate($year, $month, $day);
        } else {
            $newExpiryDate = WHMCS\Carbon::create();
        }
        $newExpiryDate = $newExpiryDate->addYears($registrationperiod)->format("Y-m-d");
        $update = array("expirydate" => $newExpiryDate, "status" => "Active", "reminders" => "");
        WHMCS\Database\Capsule::table("tbldomains")->where("id", "=", $domainid)->update($update);
        logActivity("Domain Renewed Successfully - Domain ID: " . $domainid . " - Domain: " . $domain, $userid);
        run_hook("AfterRegistrarRenewal", array("params" => $params));
    }
    return $values;
}
function RegRegisterDomain($paramvars)
{
    global $CONFIG;
    $domainid = $paramvars["domainid"];
    $result = select_query("tbldomains", "", array("id" => $domainid));
    $data = mysql_fetch_array($result);
    $userid = $data["userid"];
    $domain = $data["domain"];
    $orderid = $data["orderid"];
    $registrar = $data["registrar"];
    $registrationperiod = $data["registrationperiod"];
    $dnsmanagement = $data["dnsmanagement"] ? true : false;
    $emailforwarding = $data["emailforwarding"] ? true : false;
    $idprotection = $data["idprotection"] ? true : false;
    $result = select_query("tblorders", "contactid", array("id" => $orderid));
    $data = mysql_fetch_array($result);
    $contactid = $data["contactid"];
    if (!function_exists("getClientsDetails")) {
        require dirname(__FILE__) . "/clientfunctions.php";
    }
    $clientsdetails = getClientsDetails($userid, $contactid);
    $clientsdetails["state"] = $clientsdetails["statecode"];
    $clientsdetails["fullphonenumber"] = $clientsdetails["phonenumberformatted"];
    $clientsdetails["phone-cc"] = $clientsdetails["phonecc"];
    global $params;
    $params = array_merge($paramvars, $clientsdetails);
    $domainObj = new WHMCS\Domains\Domain($domain);
    $params["registrar"] = $registrar;
    $params["sld"] = $domainObj->getSLD();
    $params["tld"] = $domainObj->getTLD();
    $params["regperiod"] = $registrationperiod;
    $params["dnsmanagement"] = $dnsmanagement;
    $params["emailforwarding"] = $emailforwarding;
    $params["idprotection"] = $idprotection;
    $params["premiumEnabled"] = (bool) (int) WHMCS\Config\Setting::getValue("PremiumDomains");
    if ($params["premiumEnabled"]) {
        $registrarCostPrice = json_decode(WHMCS\Domain\Extra::whereDomainId($domainid)->whereName("registrarCostPrice")->value("value"), true);
        if ($registrarCostPrice && is_numeric($registrarCostPrice)) {
            $params["premiumCost"] = $registrarCostPrice;
        } else {
            if ($registrarCostPrice && is_array($registrarCostPrice) && array_key_exists("price", $registrarCostPrice)) {
                $params["premiumCost"] = $registrarCostPrice["price"];
            }
        }
    }
    if ($CONFIG["RegistrarAdminUseClientDetails"] == "on") {
        $params["adminfirstname"] = $clientsdetails["firstname"];
        $params["adminlastname"] = $clientsdetails["lastname"];
        $params["admincompanyname"] = $clientsdetails["companyname"];
        $params["adminemail"] = $clientsdetails["email"];
        $params["adminaddress1"] = $clientsdetails["address1"];
        $params["adminaddress2"] = $clientsdetails["address2"];
        $params["admincity"] = $clientsdetails["city"];
        $params["adminfullstate"] = $clientsdetails["fullstate"];
        $params["adminstate"] = $clientsdetails["state"];
        $params["adminpostcode"] = $clientsdetails["postcode"];
        $params["admincountry"] = $clientsdetails["country"];
        $params["adminphonenumber"] = $clientsdetails["phonenumber"];
        $params["adminphonecc"] = $clientsdetails["phonecc"];
        $params["adminfullphonenumber"] = $clientsdetails["phonenumberformatted"];
    } else {
        $params["adminfirstname"] = $CONFIG["RegistrarAdminFirstName"];
        $params["adminlastname"] = $CONFIG["RegistrarAdminLastName"];
        $params["admincompanyname"] = $CONFIG["RegistrarAdminCompanyName"];
        $params["adminemail"] = $CONFIG["RegistrarAdminEmailAddress"];
        $params["adminaddress1"] = $CONFIG["RegistrarAdminAddress1"];
        $params["adminaddress2"] = $CONFIG["RegistrarAdminAddress2"];
        $params["admincity"] = $CONFIG["RegistrarAdminCity"];
        $params["adminfullstate"] = $CONFIG["RegistrarAdminStateProvince"];
        $params["adminstate"] = convertStateToCode($CONFIG["RegistrarAdminStateProvince"], $CONFIG["RegistrarAdminCountry"]);
        $params["adminpostcode"] = $CONFIG["RegistrarAdminPostalCode"];
        $params["admincountry"] = $CONFIG["RegistrarAdminCountry"];
        $phoneDetails = WHMCS\Client::formatPhoneNumber(array("phonenumber" => $CONFIG["RegistrarAdminPhone"], "countrycode" => $CONFIG["RegistrarAdminCountry"]));
        $params["adminphonenumber"] = $phoneDetails["phonenumber"];
        $params["adminfullphonenumber"] = $phoneDetails["phonenumberformatted"];
        $params["adminphonecc"] = $phoneDetails["phonecc"];
    }
    if ($params["tld"] == "ca" || substr($params["tld"], -3) == ".ca") {
        $params["adminstate"] = convertToCiraCode($params["adminstate"]);
    }
    if (!$params["ns1"] && !$params["ns2"]) {
        $result = select_query("tblorders", "nameservers", array("id" => $orderid));
        $data = mysql_fetch_array($result);
        $nameservers = $data["nameservers"];
        $result = select_query("tblhosting", "server", array("domain" => $domain));
        $data = mysql_fetch_array($result);
        $server = $data["server"];
        if ($server) {
            $result = select_query("tblservers", "", array("id" => $server));
            $data = mysql_fetch_array($result);
            for ($i = 1; $i <= 5; $i++) {
                $params["ns" . $i] = trim($data["nameserver" . $i]);
            }
        } else {
            if ($nameservers && $nameservers != ",") {
                $nameservers = explode(",", $nameservers);
                for ($i = 1; $i <= 5; $i++) {
                    $params["ns" . $i] = trim($nameservers[$i - 1]);
                }
            } else {
                for ($i = 1; $i <= 5; $i++) {
                    $params["ns" . $i] = trim($CONFIG["DefaultNameserver" . $i]);
                }
            }
        }
    } else {
        for ($i = 1; $i <= 5; $i++) {
            $params["ns" . $i] = trim($params["ns" . $i]);
        }
    }
    $additflds = new WHMCS\Domains\AdditionalFields();
    $params["additionalfields"] = $additflds->getFieldValuesFromDatabase($domainid);
    $originaldetails = $params;
    if (!array_key_exists("original", $params)) {
        $params = foreignChrReplace($params);
        $params["original"] = $originaldetails;
    }
    $params["domainObj"] = $domainObj;
    run_hook("PreDomainRegister", array("domain" => $domain));
    $values = regcallfunction($params, "RegisterDomain");
    if (!is_array($values)) {
        return false;
    }
    if ($values["na"]) {
        logActivity("Domain Registration Not Supported by Module - Domain ID: " . $domainid . " - Domain: " . $domain);
        return array("error" => "Registrar Function Not Supported");
    }
    if ($values["error"]) {
        logActivity("Domain Registration Failed - Domain ID: " . $domainid . " - Domain: " . $domain . " - Error: " . $values["error"], $userid);
        run_hook("AfterRegistrarRegistrationFailed", array("params" => $params, "error" => $values["error"]));
    } else {
        if ($values["pending"]) {
            update_query("tbldomains", array("status" => "Pending"), array("id" => $domainid));
            logActivity("Domain Pending Registration Successful - Domain ID: " . $domainid . " - Domain: " . $domain, $userid);
        } else {
            $expirydate = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d"), date("Y") + $registrationperiod));
            update_query("tbldomains", array("registrationdate" => date("Ymd"), "expirydate" => $expirydate, "status" => "Active"), array("id" => $domainid));
            logActivity("Domain Registered Successfully - Domain ID: " . $domainid . " - Domain: " . $domain, $userid);
        }
        run_hook("AfterRegistrarRegistration", array("params" => $params));
    }
    return $values;
}
function RegTransferDomain($paramvars)
{
    global $CONFIG;
    $domainid = $paramvars["domainid"];
    $passedepp = $paramvars["transfersecret"];
    $result = select_query("tbldomains", "", array("id" => $domainid));
    $data = mysql_fetch_array($result);
    $userid = $data["userid"];
    $domain = $data["domain"];
    $orderid = $data["orderid"];
    $registrar = $data["registrar"];
    $registrationperiod = $data["registrationperiod"];
    $dnsmanagement = $data["dnsmanagement"] ? true : false;
    $emailforwarding = $data["emailforwarding"] ? true : false;
    $idprotection = $data["idprotection"] ? true : false;
    $result = select_query("tblorders", "contactid,nameservers,transfersecret", array("id" => $orderid));
    $data = mysql_fetch_array($result);
    $contactid = $data["contactid"];
    $nameservers = $data["nameservers"];
    $transfersecret = $data["transfersecret"];
    if (!function_exists("getClientsDetails")) {
        require dirname(__FILE__) . "/clientfunctions.php";
    }
    $clientsdetails = getClientsDetails($userid, $contactid);
    $clientsdetails["state"] = $clientsdetails["statecode"];
    $clientsdetails["fullphonenumber"] = $clientsdetails["phonenumberformatted"];
    global $params;
    $params = array_merge($paramvars, $clientsdetails);
    $domainObj = new WHMCS\Domains\Domain($domain);
    $params["registrar"] = $registrar;
    $params["sld"] = $domainObj->getSLD();
    $params["tld"] = $domainObj->getTLD();
    $params["regperiod"] = $registrationperiod;
    $params["dnsmanagement"] = $dnsmanagement;
    $params["emailforwarding"] = $emailforwarding;
    $params["idprotection"] = $idprotection;
    $params["premiumEnabled"] = (bool) (int) WHMCS\Config\Setting::getValue("PremiumDomains");
    if ($params["premiumEnabled"]) {
        $registrarCostPrice = WHMCS\Domain\Extra::whereDomainId($domainid)->whereName("registrarCostPrice")->value("value");
        if ($registrarCostPrice) {
            $params["premiumCost"] = (double) $registrarCostPrice;
        }
    }
    if ($CONFIG["RegistrarAdminUseClientDetails"] == "on") {
        $params["adminfirstname"] = $clientsdetails["firstname"];
        $params["adminlastname"] = $clientsdetails["lastname"];
        $params["admincompanyname"] = $clientsdetails["companyname"];
        $params["adminemail"] = $clientsdetails["email"];
        $params["adminaddress1"] = $clientsdetails["address1"];
        $params["adminaddress2"] = $clientsdetails["address2"];
        $params["admincity"] = $clientsdetails["city"];
        $params["adminfullstate"] = $clientsdetails["fullstate"];
        $params["adminstate"] = $clientsdetails["state"];
        $params["adminpostcode"] = $clientsdetails["postcode"];
        $params["admincountry"] = $clientsdetails["country"];
        $params["adminphonenumber"] = $clientsdetails["phonenumber"];
        $params["adminfullphonenumber"] = $clientsdetails["phonenumberformatted"];
    } else {
        $params["adminfirstname"] = $CONFIG["RegistrarAdminFirstName"];
        $params["adminlastname"] = $CONFIG["RegistrarAdminLastName"];
        $params["admincompanyname"] = $CONFIG["RegistrarAdminCompanyName"];
        $params["adminemail"] = $CONFIG["RegistrarAdminEmailAddress"];
        $params["adminaddress1"] = $CONFIG["RegistrarAdminAddress1"];
        $params["adminaddress2"] = $CONFIG["RegistrarAdminAddress2"];
        $params["admincity"] = $CONFIG["RegistrarAdminCity"];
        $params["adminfullstate"] = $CONFIG["RegistrarAdminStateProvince"];
        $params["adminstate"] = convertStateToCode($CONFIG["RegistrarAdminStateProvince"], $CONFIG["RegistrarAdminCountry"]);
        $params["adminpostcode"] = $CONFIG["RegistrarAdminPostalCode"];
        $params["admincountry"] = $CONFIG["RegistrarAdminCountry"];
        $phoneDetails = WHMCS\Client::formatPhoneNumber(array("phonenumber" => $CONFIG["RegistrarAdminPhone"], "countrycode" => $CONFIG["RegistrarAdminCountry"]));
        $params["adminphonenumber"] = $phoneDetails["phonenumber"];
        $params["adminfullphonenumber"] = $phoneDetails["phonenumberformatted"];
        $params["adminphonecc"] = $phoneDetails["phonecc"];
    }
    if ($params["tld"] == "ca" || substr($params["tld"], -3) == ".ca") {
        $params["adminstate"] = convertToCiraCode($params["adminstate"]);
    }
    if (!$params["ns1"] && !$params["ns2"]) {
        $result = select_query("tblorders", "nameservers", array("id" => $orderid));
        $data = mysql_fetch_array($result);
        $nameservers = $data["nameservers"];
        $result = select_query("tblhosting", "server", array("domain" => $domain));
        $data = mysql_fetch_array($result);
        $server = $data["server"];
        if ($server) {
            $result = select_query("tblservers", "", array("id" => $server));
            $data = mysql_fetch_array($result);
            for ($i = 1; $i <= 5; $i++) {
                $params["ns" . $i] = trim($data["nameserver" . $i]);
            }
        } else {
            if ($nameservers && $nameservers != ",") {
                $nameservers = explode(",", $nameservers);
                for ($i = 1; $i <= 5; $i++) {
                    $params["ns" . $i] = trim($nameservers[$i - 1]);
                }
            } else {
                for ($i = 1; $i <= 5; $i++) {
                    $params["ns" . $i] = trim($CONFIG["DefaultNameserver" . $i]);
                }
            }
        }
    } else {
        for ($i = 1; $i <= 5; $i++) {
            $params["ns" . $i] = trim($params["ns" . $i]);
        }
    }
    $additflds = new WHMCS\Domains\AdditionalFields();
    $params["additionalfields"] = $additflds->setDomain("transfer")->getFieldValuesFromDatabase($domainid);
    $originaldetails = $params;
    if (!array_key_exists("original", $params)) {
        $params = foreignChrReplace($params);
        $params["original"] = $originaldetails;
    }
    if (!$params["transfersecret"]) {
        $transfersecret = $transfersecret ? safe_unserialize($transfersecret) : array();
        $params["eppcode"] = $transfersecret[$domain];
        $params["transfersecret"] = $params["eppcode"];
    } else {
        $params["eppcode"] = $passedepp;
        $params["transfersecret"] = $params["eppcode"];
    }
    $params["domainObj"] = $domainObj;
    run_hook("PreDomainTransfer", array("domain" => $domain));
    $values = regcallfunction($params, "TransferDomain");
    if (!is_array($values)) {
        return false;
    }
    if ($values["na"]) {
        logActivity("Domain Transfer Not Supported by Module - Domain ID: " . $domainid . " - Domain: " . $domain);
        return array("error" => "Registrar Function Not Supported");
    }
    if ($values["error"]) {
        logActivity("Domain Transfer Failed - Domain ID: " . $domainid . " - Domain: " . $domain . " - Error: " . $values["error"], $userid);
        run_hook("AfterRegistrarTransferFailed", array("params" => $params, "error" => $values["error"]));
    } else {
        update_query("tbldomains", array("status" => "Pending Transfer"), array("id" => $domainid));
        $array = array("date" => "now()", "title" => "Domain Pending Transfer", "description" => "Check the transfer status of the domain " . $params["sld"] . "." . $params["tld"] . "", "admin" => "", "status" => "In Progress", "duedate" => date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") + 5, date("Y"))));
        insert_query("tbltodolist", $array);
        logActivity("Domain Transfer Initiated Successfully - Domain ID: " . $domainid . " - Domain: " . $domain, $userid);
        run_hook("AfterRegistrarTransfer", array("params" => $params));
    }
    return $values;
}
function RegGetContactDetails($params)
{
    return regcallfunction($params, "GetContactDetails");
}
function RegSaveContactDetails($params)
{
    $domainObj = new WHMCS\Domains\Domain($params["sld"] . "." . $params["tld"]);
    $domainid = get_query_val("tbldomains", "id", array("domain" => $domainObj->getDomain()));
    $additflds = new WHMCS\Domains\AdditionalFields();
    $params["additionalfields"] = $additflds->getFieldValuesFromDatabase($domainid);
    $originaldetails = $params;
    if (!array_key_exists("original", $params)) {
        $params = foreignChrReplace($params);
        $params["original"] = $originaldetails;
    }
    $params["domainObj"] = $domainObj;
    $values = regcallfunction($params, "SaveContactDetails");
    if (!$values) {
        return false;
    }
    $result = select_query("tbldomains", "userid", array("id" => $params["domainid"]));
    $data = mysql_fetch_array($result);
    $userid = $data[0];
    if ($values["error"]) {
        logActivity("Domain Registrar Command: Update Contact Details - Failed: " . $values["error"] . " - Domain ID: " . $params["domainid"], $userid);
    } else {
        logActivity("Domain Registrar Command: Update Contact Details - Successful", $userid);
    }
    return $values;
}
function RegGetEPPCode($params)
{
    $values = regcallfunction($params, "GetEPPCode");
    if (!$values) {
        return false;
    }
    if ($values["eppcode"]) {
        $values["eppcode"] = htmlentities($values["eppcode"]);
    }
    return $values;
}
function RegRequestDelete($params)
{
    $values = regcallfunction($params, "RequestDelete");
    if (!$values) {
        return false;
    }
    if (!$values["error"]) {
        update_query("tbldomains", array("status" => "Cancelled"), array("id" => $params["domainid"]));
    }
    return $values;
}
function RegReleaseDomain($params)
{
    $values = regcallfunction($params, "ReleaseDomain");
    if (isset($values["na"]) && $values["na"] === true) {
        return $values;
    }
    if (!isset($values["error"]) || !$values["error"]) {
        WHMCS\Database\Capsule::table("tbldomains")->where("id", $params["domainid"])->update(array("status" => "Transferred Away"));
    }
    return $values;
}
function RegRegisterNameserver($params)
{
    return regcallfunction($params, "RegisterNameserver");
}
function RegModifyNameserver($params)
{
    return regcallfunction($params, "ModifyNameserver");
}
function RegDeleteNameserver($params)
{
    return regcallfunction($params, "DeleteNameserver");
}
function RegIDProtectToggle($params)
{
    if (!array_key_exists("protectenable", $params)) {
        $domainid = $params["domainid"];
        $result = select_query("tbldomains", "idprotection", array("id" => $domainid));
        $data = mysql_fetch_assoc($result);
        $idprotection = $data["idprotection"] ? true : false;
        $params["protectenable"] = $idprotection;
    }
    return regcallfunction($params, "IDProtectToggle");
}
function RegGetDefaultNameservers($params, $domain)
{
    global $CONFIG;
    $serverid = get_query_val("tblhosting", "server", array("domain" => $domain));
    if ($serverid) {
        $result = select_query("tblservers", "", array("id" => $serverid));
        $data = mysql_fetch_array($result);
        for ($i = 1; $i <= 5; $i++) {
            $params["ns" . $i] = trim($data["nameserver" . $i]);
        }
    } else {
        for ($i = 1; $i <= 5; $i++) {
            $params["ns" . $i] = trim($CONFIG["DefaultNameserver" . $i]);
        }
    }
    return $params;
}
function RegGetRegistrantContactEmailAddress(array $params)
{
    $values = regcallfunction($params, "GetRegistrantContactEmailAddress");
    if (isset($values["registrantEmail"])) {
        return array("registrantEmail" => $values["registrantEmail"]);
    }
    return array();
}
function RegCustomFunction($params, $func_name)
{
    return regcallfunction($params, $func_name);
}
function RebuildRegistrarModuleHookCache()
{
    $hooksarray = array();
    $registrar = new WHMCS\Module\Registrar();
    foreach ($registrar->getList() as $module) {
        if (is_file(ROOTDIR . "/modules/registrars/" . $module . "/hooks.php") && get_query_val("tblregistrars", "COUNT(*)", array("registrar" => $module))) {
            $hooksarray[] = $module;
        }
    }
    $whmcs = WHMCS\Application::getInstance();
    $whmcs->set_config("RegistrarModuleHooks", implode(",", $hooksarray));
}
function injectDomainObjectIfNecessary($params)
{
    if (!isset($params["domainObj"]) || !is_object($params["domainObj"])) {
        $params["domainObj"] = new WHMCS\Domains\Domain(sprintf("%s.%s", $params["sld"], $params["tld"]));
    }
    return $params;
}
function convertToCiraCode($code)
{
    if ($code == "YT") {
        $code = "YK";
    }
    return $code;
}

?>