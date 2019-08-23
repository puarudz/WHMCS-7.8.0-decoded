<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
global $newtlds__hook_message;
global $newtlds__hook_processed;
global $newtlds__hook_defaultgateway;
global $newtlds__hook_BatchSize;
global $newtlds__hook_DomainsToUpdate;
global $newtlds__hook_DefaultEnvironment;
global $newtlds__ModuleName;
global $newtlds__DBName;
global $newtlds__CronDBName;
$newtlds__hook_message = "";
$newtlds__hook_processed = 0;
$newtlds__hook_defaultgateway = "";
$newtlds__hook_BatchSize = "50";
$newtlds__hook_DomainsToUpdate = array();
$newtlds__hook_DefaultEnvironment = "0";
$newtlds__ModuleName = "newtlds";
$newtlds__DBName = "mod_enomnewtlds";
$newtlds__CronDBName = $newtlds__DBName . "_cron";
add_hook("DailyCronJob", 1, "newtlds__hook_cronjob");
add_hook("ClientAreaHomepage", 1, "newtlds_hook_clientareahomeoutput");
add_hook("ClientAreaPrimaryNavbar", -1, function (WHMCS\View\Menu\Item $primaryNavbar) {
    $menu = $primaryNavbar->getChild("Domains");
    if (is_null($menu)) {
        return false;
    }
    $menu->addChild("New TLDs", array("label" => "Preregister New TLDs", "uri" => "index.php?m=newtlds", "order" => 55));
});
function newtlds__hook_cronjob()
{
    global $newtlds__hook_message;
    global $newtlds__hook_BatchSize;
    global $newtlds__hook_processed;
    global $newtlds__DBName;
    global $newtlds__hook_defaultgateway;
    $newtlds__hook_message = "";
    newtlds__hook_Helper_Log("Starting New TLD Watchlist Cron Job");
    newtlds__hook_DB_GetCreateHookTable();
    newtlds__hook_DB_GetBatchSize();
    $data = newtlds__hook_DB_GetWatchlistSettingsLocal();
    $newtlds__hook_defaultgateway = newtlds__hook_DB_GetSystemDefaultGateway();
    $portalid = $data["portalid"];
    $enomuid = $data["enomlogin"];
    $enompw = $data["enompassword"];
    $enabled = $data["enabled"];
    $configured = $data["configured"];
    $companyname = $data["companyname"];
    $companyurl = $data["companyurl"];
    $supportemail = $data["supportemail"];
    $environment = newtlds__hook_Helper_Getenvironment($data["environment"]);
    $batches = 1;
    $newtlds__hook_processed = 0;
    if (!$enabled || !$configured) {
        newtlds__hook_Helper_Log("Module is not configured.");
    } else {
        newtlds__hook_Helper_Log2("******* Settings ******");
        newtlds__hook_Helper_Log2("Enabled = " . $enabled);
        newtlds__hook_Helper_Log2("Configured = " . $configured);
        newtlds__hook_Helper_Log2("Portal ID = " . $portalid);
        newtlds__hook_Helper_Log2("Enom Login = " . $enomuid);
        newtlds__hook_Helper_Log2("Company Name = " . $companyname);
        newtlds__hook_Helper_Log2("Company URL = " . $companyurl);
        newtlds__hook_Helper_Log2("Batch Size = " . $newtlds__hook_BatchSize);
        newtlds__hook_Helper_Log2("<br /><br />");
        $fields = array();
        $fields["portalid"] = $portalid;
        $fields["uid"] = $enomuid;
        $fields["pw"] = $enompw;
        $fields["recordcount"] = $newtlds__hook_BatchSize;
        newtlds__hook_Helper_Log("Calling eNom API To get the awarded domains");
        $xmldata = newtlds__hook_API_GetAwardedDomains($fields);
        $success = $xmldata->ErrCount == 0;
        if ($success) {
            $returnedCount = $xmldata->Domains->DomainCount;
            $totalCount = $xmldata->Domains->TotalDomainCount;
            newtlds__hook_Helper_Log("Got " . $returnedCount . " domains returned, " . $totalCount . " total domains to process");
            if (0 < (int) $returnedCount && 0 < (int) $totalCount) {
                if ((int) $returnedCount < (int) $totalCount) {
                    $batches = ceil((int) $totalCount / (int) $returnedCount);
                }
                newtlds__hook_Helper_Log2("batches = " . $batches);
                newtlds__hook_Helper_Log("Batch size is " . $newtlds__hook_BatchSize . " and with " . $totalCount . " domains returned, there are a total of " . $batches . " batches to process");
                newtlds__hook_ProcessDomains($xmldata, $returnedCount, $totalCount, 1);
                newtlds__hook_DumpProcessedDomains();
                for ($i = 1; $i <= $batches; $i++) {
                    newtlds__hook_Helper_Log2("Processing a batch - #" . $i . "/" . $batches);
                    newtlds__hook_ProcessBatch($data, $returnedCount, $totalCount, $i + 1);
                    newtlds__hook_DumpProcessedDomains();
                }
            }
        } else {
            newtlds__hook_Helper_Log("API ERRORS!");
            $errcnt = $xmldata->ErrCount;
            for ($i = 1; $i <= $errcnt; $i++) {
                $err = $xmldata->errors->{"Err" . $i};
                if ($i < $errcnt) {
                    $result .= $err . "<br />";
                }
                newtlds__hook_Helper_Log("Error " . $i . " = " . $err);
            }
            if (!$result) {
                newtlds__hook_Helper_Log("UNKNOWN ERROR");
            }
        }
        newtlds__hook_DumpProcessedDomains();
        newtlds__hook_Helper_Log("Processed " . $newtlds__hook_processed . " domains");
        if (!newtlds__hook_Helper_IsNullOrEmptyString($supportemail)) {
            newtlds__hook_Helper_SendEmail($supportemail, "Watchlist CronJob Results final", $newtlds__hook_message);
        }
    }
}
function newtlds__hook_ProcessBatch($data, $returnedCount, $totalCount, $batch)
{
    global $newtlds__hook_message;
    global $newtlds__hook_BatchSize;
    global $newtlds__hook_processed;
    $portalid = $data["portalid"];
    $enomuid = $data["enomlogin"];
    $enompw = $data["enompassword"];
    $enabled = $data["enabled"];
    $configured = $data["configured"];
    $companyname = $data["companyname"];
    $companyurl = $data["companyurl"];
    $environment = newtlds__hook_Helper_Getenvironment($data["environment"]);
    $fields = array();
    $fields["portalid"] = $portalid;
    $fields["uid"] = $enomuid;
    $fields["pw"] = $enompw;
    $fields["recordcount"] = $newtlds__hook_BatchSize;
    newtlds__hook_Helper_Log("Calling eNom API To get the awarded domains");
    $xmldata = newtlds__hook_API_GetAwardedDomains($fields);
    newtlds__hook_ProcessDomains($xmldata, $returnedCount, $totalCount, $batch);
}
function newtlds__hook_ProcessDomains($xmldata, $returnedCount, $totalCount, $batch)
{
    global $newtlds__hook_message;
    global $newtlds__hook_BatchSize;
    global $newtlds__hook_processed;
    $processed = 0;
    newtlds__hook_Helper_Log("Starting Batch " . $batch);
    foreach ($xmldata->Domains->Domain as $details) {
        $processed++;
        $newtlds__hook_processed++;
        $domain = $details->DomainName;
        $email = $details->EmailAddress;
        $expdate = $details->ExpirationDate;
        $regdate = $details->RegisterDate;
        $domainnameid = $details->PortalDomainId;
        $userid = $details->ForeignLoginId;
        $regperiod = $details->RegistrationPeriod;
        $provisioned = $details->ResellerProvisioned;
        $dtregdate = new DateTime($regdate);
        $dtexpdate = new DateTime($expdate);
        $currency = getCurrency($userid);
        $from = get_query_val("tblcurrencies", "id", array("code" => "USD"));
        $to = $currency["id"];
        if (!$from) {
            $from = "1";
        }
        $convert = $to != $from;
        $regprice = $convert ? convertCurrency($details->RegisterPrice, $from, $to) : $details->RegisterPrice;
        $renewprice = $convert ? convertCurrency($details->RenewPrice, $from, $to) : $details->RenewPrice;
        $gateway = newtlds__hook_DB_GetUserDefaultGateway($userid);
        $fields = array("userid" => $userid, "type" => "Register", "registrationdate" => $dtregdate->format("Y-m-d"), "domain" => $domain, "firstpaymentamount" => $regprice, "recurringamount" => $renewprice, "registrar" => "enom", "registrationperiod" => $regperiod, "expirydate" => $dtexpdate->format("Y-m-d"), "nextduedate" => $dtexpdate->format("Y-m-d"), "nextinvoicedate" => $dtexpdate->format("Y-m-d"), "status" => "Active", "paymentmethod" => $gateway, "dnid" => $domainnameid, "provisioned" => $provisioned, "email" => $email);
        $result = newtlds__hook_DB_InsertDomain($fields);
        if ($result) {
            newtlds__hook_AddDomainToUpdateList($domainnameid);
            newtlds__hook_DB_InsertIntoCronTable($fields);
        }
    }
    newtlds__hook_Helper_Log("Finished with Batch " . $batch . " - Processed " . $processed . " domains.");
}
function newtlds__hook_DumpProcessedDomains()
{
    global $newtlds__hook_DomainsToUpdate;
    if (0 < count($newtlds__hook_DomainsToUpdate)) {
        $domains = implode(",", $newtlds__hook_DomainsToUpdate);
        $fields = array();
        newtlds__hook_Helper_Log("Calling eNom API To Update the awarded domains statuses");
        $xmldata = newtlds__hook_API_SetAwardedDomains($fields);
        $success = $xmldata->ErrCount == 0;
        if ($success) {
            newtlds__hook_DB_UpdateProvisionedCronTable($domains);
        }
    }
    $newtlds__hook_DomainsToUpdate = array();
}
function newtlds__hook_DB_GetSystemDefaultGateway()
{
    $result = full_query("SELECT g.gateway FROM `tblpaymentgateways` g inner join `tblpaymentgateways` gg on g.gateway=gg.gateway where gg.setting='visible' and gg.value='on' ORDER BY 'order' ASC LIMIT 0,1");
    $data = mysql_fetch_array($result);
    return $data[0];
}
function newtlds__hook_DB_GetUserDefaultGateway($userid)
{
    global $newtlds__hook_defaultgateway;
    $result = select_query("tblclients", "defaultgateway", array("id" => $userid));
    $data = mysql_fetch_array($result);
    if ($data[0]) {
        return $data[0];
    }
    return $newtlds__hook_defaultgateway;
}
function newtlds__hook_DB_GetCreateHookTable()
{
    global $newtlds__CronDBName;
    if (!newtlds__hook_DB_TableExists()) {
        full_query("CREATE TABLE IF NOT EXISTS `" . $newtlds__CronDBName . "` (\n            `id` INT( 100 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,\n            `domainname` VARCHAR( 272 ) NOT NULL,\n            `domainnameid` INT ( 10 ) NOT NULL,\n            `emailaddress` VARCHAR( 272 ) NOT NULL,\n            `expdate` VARCHAR( 272 ) NOT NULL,\n            `regdate` VARCHAR( 272 ) NOT NULL,\n            `userid` VARCHAR( 272 ) NOT NULL ,\n            `regprice` VARCHAR( 272 ) NOT NULL ,\n            `renewprice` VARCHAR( 272 ) NOT NULL,\n            `regperiod` INT( 2 ) NOT NULL DEFAULT  '1' ,\n            `provisioned` INT( 1 ) NOT NULL DEFAULT  '0',\n            `provisiondate` VARCHAR( 272 ) NULL )\n             ENGINE = MYISAM;");
        full_query("ALTER TABLE " . $newtlds__CronDBName . "\n              ADD CONSTRAINT UniqueDomainName\n                UNIQUE (domainname);");
    }
    if (!mysql_num_rows(full_query("select * from `tblconfiguration` where setting='newtlds__cronbatchsize';"))) {
        full_query("insert into `tblconfiguration` (Setting, value) VALUES('newtlds__cronbatchsize', '50');");
    }
}
function newtlds__hook_DB_TableExists()
{
    global $newtlds__CronDBName;
    if (!mysql_num_rows(full_query("SHOW TABLES LIKE '" . $newtlds__CronDBName . "'"))) {
        return false;
    }
    return true;
}
function newtlds__hook_DB_GetWatchlistSettingsLocal()
{
    global $newtlds__hook_DefaultEnvironment;
    global $newtlds__DBName;
    $result = select_query($newtlds__DBName, "enabled,configured,portalid,environment,enomlogin,enompassword,companyname,companyurl,supportemail", array());
    $data = mysql_fetch_array($result);
    if (newtlds__hook_Helper_IsNullOrEmptyString($data["portalid"])) {
        $data["portalid"] = "0";
    }
    if (newtlds__hook_Helper_IsNullOrEmptyString($data["enompassword"])) {
        $data["enompassword"] = "";
    } else {
        $data["enompassword"] = decrypt($data["enompassword"]);
    }
    if (newtlds__hook_Helper_IsNullOrEmptyString($data["enomlogin"])) {
        $data["enomlogin"] = "";
    } else {
        $data["enomlogin"] = decrypt($data["enomlogin"]);
    }
    if (newtlds__hook_Helper_IsNullOrEmptyString($data["companyname"])) {
        $data["companyname"] = "";
    }
    if (newtlds__hook_Helper_IsNullOrEmptyString($data["companyurl"])) {
        $data["companyurl"] = "";
    }
    if (newtlds__hook_Helper_IsNullOrEmptyString($data["environment"])) {
        $data["environment"] = $newtlds__hook_DefaultEnvironment;
    }
    if (newtlds__hook_Helper_IsNullOrEmptyString($data["supportemail"])) {
        $data["supportemail"] = "";
    }
    return $data;
}
function newtlds__hook_DB_InsertDomain($domain)
{
    $domainname = $domain["domain"];
    if (is_array($domain) && !newtlds__hook_Helper_IsNullOrEmptyString($domainname)) {
        if (!newtlds__hook_DB_DomainExists($domainname)) {
            $values = array("userid" => $domain["userid"], "type" => "Register", "registrationdate" => $domain["registrationdate"], "domain" => $domain["domain"], "firstpaymentamount" => $domain["firstpaymentamount"], "recurringamount" => $domain["recurringamount"], "registrar" => "enom", "registrationperiod" => $domain["registrationperiod"], "expirydate" => $domain["expirydate"], "nextduedate" => $domain["nextduedate"], "nextinvoicedate" => $domain["nextinvoicedate"], "status" => "Active", "paymentmethod" => $domain["paymentmethod"]);
            $result = insert_query("tbldomains", $values);
        } else {
            newtlds__hook_Helper_Log("Domain name " . $domainname . " already exists in the table, cannot and should not re-insert!");
            $result = true;
        }
    } else {
        newtlds__hook_Helper_Log("Domain name is null, empty, blank or domain Object is not an array!");
        $result = false;
    }
    return $result;
}
function newtlds__hook_DB_GetBatchSize()
{
    global $newtlds__hook_BatchSize;
    $result = select_query("tblconfiguration", "value", array("setting" => "newtlds__cronbatchsize"));
    $data = mysql_fetch_array($result);
    if (!$data) {
        $newtlds__hook_BatchSize = "25";
    } else {
        $newtlds__hook_BatchSize = $data[0];
    }
    newtlds__hook_Helper_Log2("Setting batch size = " . $newtlds__hook_BatchSize);
    return $newtlds__hook_BatchSize;
}
function newtlds__hook_DB_InsertIntoCronTable($values)
{
    global $newtlds__CronDBName;
    $sql = "replace into " . $newtlds__CronDBName . "\n                        (domainname, domainnameid, emailaddress, expdate, regdate, userid, regprice, renewprice, regperiod, provisioned)\n                Values (\n                '" . $values["domain"] . "',\n                '" . $values["dnid"] . "',\n                '" . $values["email"] . "',\n                '" . $values["expirydate"] . "',\n                '" . $values["registrationdate"] . "',\n                '" . $values["userid"] . "',\n                '" . $values["firstpaymentamount"] . "',\n                '" . $values["recurringamount"] . "',\n                '" . $values["registrationperiod"] . "',\n                '" . $values["provisioned"] . "' )";
    $result = full_query($sql);
    return $result;
}
function newtlds__hook_DB_UpdateProvisionedCronTable($domains)
{
    global $newtlds__CronDBName;
    $result = "0";
    if ($domains != "") {
        $time = newtlds__hook_Helper_GetDateTime();
        $domainnameids = $sql = "update " . $newtlds__CronDBName . " set provisioned='1', provisiondate='" . $time . "' where domainnameid in (" . $domains . ");";
        $result = full_query($sql);
    }
    return $result;
}
function newtlds__hook_DB_DomainExists($domain)
{
    if (!mysql_num_rows(full_query("select domain from tbldomains where domain='" . $domain . "'"))) {
        return false;
    }
    return true;
}
function newtlds__hook_Helper_Log($String)
{
    newtlds__hook_AddMessage($String);
    logActivity($String);
}
function newtlds__hook_Helper_Log2($String)
{
    newtlds__hook_AddMessage($String);
}
function newtlds__hook_Helper_GetDateTime()
{
    $t = microtime(true);
    $micro = sprintf("%06d", ($t - floor($t)) * 1000000);
    $d = new DateTime(date("Y-m-d H:i:s." . $micro, $t));
    return $d->format("Y-m-d H:i:s");
}
function newtlds__hook_Helper_IsNullOrEmptyString($str)
{
    return !isset($str) || trim($str) === "" || strlen($str) == 0;
}
function newtlds__hook_Helper_startsWith($haystack, $needle)
{
    $length = strlen($needle);
    return substr($haystack, 0, $length) === $needle;
}
function newtlds__hook_Helper_endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }
    return substr($haystack, 0 - $length) === $needle;
}
function newtlds__hook_Helper_FormatAPICallForEmail($fields, $environment)
{
    $url = "https://" . newtlds__hook_Helper_GetAPIHost($environment) . "/interface.asp?";
    foreach ($fields as $x => $y) {
        $url .= $x . "=" . $y . "&";
    }
    return $url;
}
function newtlds__hook_Helper_GetAPIHost($environment)
{
    switch ($environment) {
        case "1":
            $url = "resellertest.enom.com";
            break;
        case "2":
            $url = "api.staging.local";
            break;
        case "3":
            $url = "api.build.local";
            break;
        case "4":
            $url = "reseller-sb.enom.com";
            break;
        default:
            $url = "reseller.enom.com";
            break;
    }
    return $url;
}
function newtlds__hook_Helper_GetWatchlistHost($environment)
{
    switch ($environment) {
        case "1":
            $url = "resellertest.tldportal.com";
            break;
        case "2":
            $url = "tldportal.staging.local";
            break;
        case "3":
            $url = "tldportal.build.local";
            break;
        case "4":
            $url = "preprod.tldportal.com";
            break;
        default:
            $url = "tldportal.com";
            break;
    }
    return $url;
}
function newtlds__hook_Helper_Getenvironment($environment)
{
    global $newtlds__hook_DefaultEnvironment;
    if (newtlds__hook_helper_isnulloremptystring($environment)) {
        $data = newtlds__hook_db_getwatchlistsettingslocal();
        $environment = $data["environment"];
    }
    return $environment;
}
function newtlds__hook_Helper_SendEmail($to, $subject, $message)
{
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=iso-8859-1" . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    $message = wordwrap($message, 70);
}
function newtlds__hook_AddDomainToUpdateList($domain)
{
    global $newtlds__hook_DomainsToUpdate;
    $newtlds__hook_DomainsToUpdate[] = $domain;
}
function newtlds__hook_AddError($error)
{
    global $newtlds__hook_errormessage;
    if (newtlds__hook_helper_isnulloremptystring($newtlds__hook_errormessage)) {
        $newtlds__hook_errormessage = $error;
    } else {
        $newtlds__hook_errormessage .= "<br />" . $error;
    }
    newtlds__hook_helper_log("ERROR!! - " . $error);
}
function newtlds__hook_AddMessage($message)
{
    global $newtlds__hook_message;
    if (newtlds__hook_helper_isnulloremptystring($newtlds__hook_message)) {
        $newtlds__hook_message = $message;
    } else {
        $newtlds__hook_message .= "<br />" . $message;
    }
}
function newtlds__hook_API_GetAwardedDomains($fields)
{
    global $newtlds__hook_errormessage;
    $postfields = array();
    $postfields["command"] = "PORTAL_GETAWARDEDDOMAINS";
    if (is_array($fields)) {
        foreach ($fields as $x => $y) {
            $postfields[$x] = $y;
        }
    }
    $xmldata = newtlds__hook_API_CallEnom($postfields);
    return $xmldata;
}
function newtlds__hook_API_SetAwardedDomains($fields)
{
    global $newtlds__hook_errormessage;
    global $newtlds__hook_DomainsToUpdate;
    $postfields = array();
    $postfields["domainlist"] = implode(",", $newtlds__hook_DomainsToUpdate);
    $postfields["command"] = "PORTAL_UPDATEAWARDEDDOMAINS";
    if (is_array($fields)) {
        foreach ($fields as $x => $y) {
            $postfields[$x] = $y;
        }
    }
    $xmldata = newtlds__hook_API_CallEnom($postfields);
    return $xmldata;
}
function newtlds__hook_API_CallEnom($postfields)
{
    global $newtlds__hook_errormessage;
    global $newtlds__ModuleName;
    $data = newtlds__hook_db_getwatchlistsettingslocal();
    $environment = newtlds__hook_helper_getenvironment($data["environment"]);
    $portalid = $data["portalid"];
    if (!in_array("uid", $postfields)) {
        $enomuid = $data["enomlogin"];
        $postfields["uid"] = $enomuid;
    }
    if (!in_array("pw", $postfields)) {
        $enompw = $data["enompassword"];
        $postfields["pw"] = $enompw;
    }
    if (!in_array("portalid", $postfields) && !newtlds__hook_helper_isnulloremptystring($portalid) && 0 < (int) $portalid) {
        $postfields["portalid"] = $portalid;
    }
    $postfields["ResponseType"] = "XML";
    $postfields["Source"] = "WHMCS";
    $postfields["sourceid"] = "37";
    $url = "https://" . newtlds__hook_helper_getapihost($environment) . "/interface.asp";
    $data = curlCall($url, $postfields);
    $call = newtlds__hook_helper_formatapicallforemail($postfields, $environment);
    $apiData = $call . "<br /><br /><br />" . htmlentities($data, ENT_COMPAT | ENT_HTML401, "UTF-8");
    newtlds__hook_helper_log2("API DATA = " . $apiData);
    $xmldata = simplexml_load_string($data);
    logModuleCall($newtlds__ModuleName, $postfields["command"], $postfields, $data, $xmldata, array($postfields["pw"]));
    return $xmldata;
}
function newtlds_hook_clientareahomeoutput()
{
    return "<style>\n.new-tlds-home-banner {\n    margin: 5px 0 20px 0;\n    padding: 14px;\n    background:#0064CD;\n    background:-moz-linear-gradient(top, #0064CD 0%, #207ce5 100%);\n    background:-webkit-linear-gradient(top, #0064CD 0%,#207ce5 100%);\n    background:-ms-linear-gradient(top, #0064CD 0%,#207ce5 100%);\n    background:linear-gradient(to bottom, #0064CD 0%,#207ce5 100%);\n    font-family:\"Open Sans\",Trebuchet MS, Trebuchet MS, sans-serif;\n    font-size:1.2em;\n    text-align:center;\n    color: #fff;\n    border-radius:5px;\n    zoom:1;\n}\n.new-tlds-home-banner a {\n    color:#FFD20A;\n}\n</style>\n<div class=\"new-tlds-home-banner\">\n    The next generation of domains is coming! Take advantage of New TLD opportunities.\n    <a href=\"index.php?m=newtlds\">Learn More &raquo;</a>\n</div>";
}

?>