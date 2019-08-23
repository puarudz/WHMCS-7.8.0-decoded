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
global $newtlds_CurrentVersion;
global $newtlds_isbundled;
global $newtlds_errormessage;
global $newtlds_mysalt;
global $newtlds_DefaultEnvironment;
global $newtlds_ModuleName;
global $newtlds_DBName;
global $newtlds_CronDBName;
$newtlds_CurrentVersion = "1.1";
$newtlds_isbundled = true;
$newtlds_errormessage = "";
$newtlds_mysalt = "sAR2Th4Ste363tUkUw";
$newtlds_DefaultEnvironment = "0";
$newtlds_ModuleName = "newtlds";
$newtlds_DBName = "mod_enomnewtlds";
$newtlds_CronDBName = $newtlds_DBName . "_cron";
function newtlds_config()
{
    global $newtlds_CurrentVersion;
    $configarray = array("name" => "New TLDs", "description" => "Earn commissions offering New TLDs services to your customers.  This addon includes eNom's New TLDs Watchlist and order processing for New TLDs launch phases including: Sunrise, Landrush, and Pre-Registration. Learn more at <a href=\"http://www.enom.com/r/01.aspx\" target=\"_blank\">http://www.enom.com/r/01.aspx</a>", "version" => $newtlds_CurrentVersion, "author" => "eNom", "language" => "english", "fields" => array());
    return $configarray;
}
function newtlds_activate()
{
    global $newtlds_DefaultEnvironment;
    global $newtlds_DBName;
    $sql = newtlds_DB_GetCreateTable();
    $retval = mysql_query($sql);
    if (!$retval) {
        return array("status" => "error", "description" => $LANG["activate_failed1"] . $newtlds_DBName . " : " . mysql_error());
    }
    $companyname = "";
    $domain = "";
    $date = newtlds_Helper_GetDateTime();
    $data = newtlds_DB_GetDefaults();
    $domain = newtlds_Helper_GetWatchlistUrl($data["companyurl"]);
    insert_query($newtlds_DBName, array("enabled" => "1", "configured" => "0", "environment" => $newtlds_DefaultEnvironment, "companyname" => $data["companyname"], "companyurl" => $domain, "supportemail" => $data["supportemail"], "enableddate" => $date));
    newtlds_DB_GetCreateHookTable();
    return array("status" => "success", "description" => $LANG["activate_success1"]);
}
function newtlds_deactivate()
{
    global $newtlds_errormessage;
    global $newtlds_DBName;
    global $newtlds_CronDBName;
    $vars = array();
    $fields = array();
    $fields["statusid"] = "0";
    $data = newtlds_DB_GetWatchlistSettingsLocal();
    $wlenabled = $data["enabled"];
    $wlconfigured = $data["configured"];
    $portalid = $data["portalid"];
    if ($wlenabled && $wlconfigured || 0 < (int) $portalid) {
        $success = newtlds_API_UpdatePortalAccount($vars, $portalid, $fields);
        if (!$success) {
        }
    }
    if (newtlds_DB_TableExists()) {
        $sql = "DROP TABLE `" . $newtlds_DBName . "`;";
        $retval = mysql_query($sql);
    } else {
        $retval = 1;
    }
    if (!$retval) {
        return array("status" => "error", "description" => "Error!  There was a mysql error when attempting to turn off the module - Could not drop table: " . mysql_error());
    }
    if (newtlds_DB_HookTableExists()) {
        $sql = "DROP TABLE `" . $newtlds_CronDBName . "`;";
        $retval = mysql_query($sql);
    }
    return array("status" => "success", "description" => "Success!  The eNom New TLDs Addon has been disabled.");
}
function newtlds_upgrade($vars)
{
    $version = $vars["version"];
    global $newtlds_CurrentVersion;
    if ($version < 1.1) {
    }
    if ($version < 1.2) {
    }
}
function newtlds_clientarea($vars)
{
    global $newtlds_errormessage;
    global $newtlds_mysalt;
    global $newtlds_isbundled;
    $token = "";
    $modulelink = $vars["modulelink"];
    $version = $vars["version"];
    $LANG = $vars["_lang"];
    $pversion = ($newtlds_isbundled ? "bundled" : "nonbundled") . " version " . $version;
    $userid = $_SESSION["uid"];
    if ($userid) {
        $query = mysql_query("SELECT email FROM tblclients WHERE id=" . (int) $userid) or exit("There was a problem with the SQL query: " . mysql_error());
        $data = mysql_fetch_array($query);
        $email = $data[0];
        if (!$email) {
            newtlds_AddError($LANG["noemail"]);
        } else {
            if (!$newtlds_mysalt) {
                newtlds_AddError($LANG["nosalt"]);
            } else {
                $code = hash("sha512", $email . $newtlds_mysalt);
                $password = substr($code, 0, 15);
            }
        }
    } else {
        newtlds_AddError($LANG["notloggedin"]);
    }
    $data = newtlds_DB_GetWatchlistSettingsLocal();
    $wlenabled = $data["enabled"];
    $portalid = $data["portalid"];
    $environment = $data["environment"];
    if (newtlds_Helper_IsNullOrEmptyString($portalid)) {
        $portalid = "0";
    }
    $hasportalaccount = 0 < (int) $portalid;
    $success = true;
    if ($wlenabled && !$hasportalaccount) {
        newtlds_AddError($LANG["noportalacct"]);
        $success = false;
    }
    if ($success && $portalid != "0") {
        $linkarray = array("sitesource" => "whmcs", "embeded" => "1", "ruid" => $data["enomlogin"], "rpw" => $data["enompassword"], "pw" => $password, "portaluserid" => $userid, "email" => $email, "portalid" => $portalid);
        $success = newtlds_API_GetPortalToken($vars, $token, $linkarray);
    } else {
        if ($portalid == "0") {
            newtlds_AddError($LANG["noportalaccount"]);
        } else {
            if (!$success) {
            }
        }
    }
    $varsArray = array("NEWTLDS_HASH" => $code, "WHMCS__EMAIL" => $email, "NEWTLDS_PASSWORD" => $password, "RESELLER_UID" => $data["enomlogin"], "RESELLER_PW" => $data["enompassword"], "NEWTLDS_ENABLED" => $wlenabled, "NEWTLDS_PORTALACCOUNT" => $hasportalaccount, "NEWTLDS_LINK" => $token, "WHMCS_CUSTOMERID" => $userid, "PORTAL_ID" => $portalid, "NEWTLDS_ERRORS" => $newtlds_errormessage, "NEWTLDS_NOPORTALACCT" => $LANG["noportalaccount"], "NEWTLDS_NOTENABLED" => $LANG["headertext"], "NEWTLDS_NOTCONFIGURED" => $LANG["notconfigured"], "NEWTLDS_NOTLOGGEDIN" => $LANG["notloggedin"], "NEWTLDS_URLHOST" => newtlds_Helper_GetWatchlistHost($environment), "NEWTLDS_PLUGINVERSION" => $pversion);
    return array("pagetitle" => $LANG["pagetitle"], "breadcrumb" => array($modulelink => $LANG["pagetitle"]), "templatefile" => "newtlds", "requirelogin" => true, "requiressl" => true, "forcessl" => true, "vars" => $varsArray);
}
function newtlds_NEWTLDS_sidebar($vars)
{
    $modulelink = $vars["modulelink"];
    $LANG = $vars["_lang"];
    $sidebar = "<span class=\"header\"><img src=\"images/icons/addonmodules.png\" class=\"absmiddle\" width=\"16\" height=\"16\" />" . $LANG["intro"] . "</span>\n    <ul class=\"menu\">\n        <li><a href=\"#\">" . $LANG["intro"] . "</a></li>\n        <li><a href=\"#\">Version: " . $vars["version"] . "</a></li>\n    </ul>";
    return $sidebar;
}
function newtlds_DB_GetCreateTable()
{
    global $newtlds_DefaultEnvironment;
    global $newtlds_DBName;
    $sql = "CREATE TABLE IF NOT EXISTS `" . $newtlds_DBName . "` (\n            `id` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,\n            `enabled` INT( 1 ) NOT NULL DEFAULT '0' ,\n            `configured` INT( 1 ) NOT NULL DEFAULT '0' ,\n            `portalid` MEDIUMINT( 18 ) NOT NULL DEFAULT '0' ,\n            `environment` INT( 1 ) NOT NULL DEFAULT '" . $newtlds_DefaultEnvironment . "' ,\n            `enomlogin` VARCHAR( 272 ) NULL ,\n            `enompassword` VARCHAR( 272 ) NULL ,\n            `enableddate` VARCHAR( 272 ) NULL ,\n            `configureddate` VARCHAR( 272 ) NULL,\n            `supportemail` VARCHAR( 387 ) NULL ,\n            `companyname` VARCHAR( 387 ) NULL ,\n            `companyurl` VARCHAR( 387 ) NULL)\n            ENGINE = MYISAM";
    return $sql;
}
function newtlds_DB_GetCreateHookTable()
{
    global $newtlds_CronDBName;
    if (!newtlds_DB_HookTableExists()) {
        full_query("CREATE TABLE IF NOT EXISTS `" . $newtlds_CronDBName . "` (\n            `id` INT( 100 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,\n            `domainname` VARCHAR( 272 ) NOT NULL,\n            `domainnameid` INT ( 10 ) NOT NULL,\n            `emailaddress` VARCHAR( 272 ) NOT NULL,\n            `expdate` VARCHAR( 272 ) NOT NULL,\n            `regdate` VARCHAR( 272 ) NOT NULL,\n            `userid` VARCHAR( 272 ) NOT NULL ,\n            `regprice` VARCHAR( 272 ) NOT NULL ,\n            `renewprice` VARCHAR( 272 ) NOT NULL,\n            `regperiod` INT( 2 ) NOT NULL DEFAULT  '1' ,\n            `provisioned` INT( 1 ) NOT NULL DEFAULT  '0',\n            `provisiondate` VARCHAR( 272 ) NULL )\n             ENGINE = MYISAM;");
        full_query("ALTER TABLE " . $newtlds_CronDBName . "\n              ADD CONSTRAINT UniqueDomainName\n                UNIQUE (domainname);");
    }
    if (!mysql_num_rows(full_query("select * from `tblconfiguration` where setting='newtlds_cronbatchsize';"))) {
        full_query("insert into `tblconfiguration` (Setting, value) VALUES('newtlds_cronbatchsize', '50');");
    }
}
function newtlds_DB_GetDefaults()
{
    $data = array();
    $data["companyname"] = newtlds_DB_GetDefaultcompanyname();
    $data["companyurl"] = newtlds_DB_GetDefaultDomainName();
    $data["supportemail"] = newtlds_DB_GetDefaultSupportEmail();
    return $data;
}
function newtlds_DB_GetWatchlistSettingsLocal()
{
    global $newtlds_DefaultEnvironment;
    global $newtlds_DBName;
    $result = select_query($newtlds_DBName, "enabled,configured,portalid,environment,enomlogin,enompassword,supportemail,companyname,companyurl", array());
    $data = mysql_fetch_array($result);
    if (newtlds_Helper_IsNullOrEmptyString($data["portalid"])) {
        $data["portalid"] = "0";
    }
    if (newtlds_Helper_IsNullOrEmptyString($data["enompassword"])) {
        $data["enompassword"] = "";
    } else {
        $data["enompassword"] = decrypt($data["enompassword"]);
    }
    if (newtlds_Helper_IsNullOrEmptyString($data["enomlogin"])) {
        $data["enomlogin"] = "";
    } else {
        $data["enomlogin"] = decrypt($data["enomlogin"]);
    }
    if (newtlds_Helper_IsNullOrEmptyString($data["companyname"])) {
        $data["companyname"] = "";
    }
    if (newtlds_Helper_IsNullOrEmptyString($data["companyurl"])) {
        $data["companyurl"] = "";
    }
    if (newtlds_Helper_IsNullOrEmptyString($data["environment"])) {
        $data["environment"] = $newtlds_DefaultEnvironment;
    }
    if (newtlds_Helper_IsNullOrEmptyString($data["supportemail"])) {
        $data["supportemail"] = "";
    }
    return $data;
}
function newtlds_DB_GetWatchlistPortalExists()
{
    if (!newtlds_DB_TableExists()) {
        return false;
    }
    $data = newtlds_db_getwatchlistsettingslocal();
    if (!$data) {
        return false;
    }
    return $data["configured"] == 1;
}
function newtlds_DB_TableExists()
{
    if (!mysql_num_rows(full_query("SHOW TABLES LIKE '" . $newtlds_DBName . "'"))) {
        return false;
    }
    return true;
}
function newtlds_DB_HookTableExists()
{
    global $newtlds_CronDBName;
    if (!mysql_num_rows(full_query("SHOW TABLES LIKE '" . $newtlds_CronDBName . "'"))) {
        return false;
    }
    return true;
}
function newtlds_DB_GetWatchlistIsEnabled()
{
    if (!newtlds_db_tableexists()) {
        return false;
    }
    $data = newtlds_db_getwatchlistsettingslocal();
    if (!$data) {
        return false;
    }
    return $data["enabled"] == 1;
}
function newtlds_DB_UpdateDB($vars, $portalid = "0")
{
    global $newtlds_DBName;
    $LANG = $vars["_lang"];
    $companyname = $vars["companyname"];
    $companyurl = $vars["companyurl"];
    $supportemail = $vars["supportemail"];
    $datetime = newtlds_Helper_GetDateTime();
    if (0 < (int) $portalid) {
        update_query($newtlds_DBName, array("configured" => "1", "portalid" => $portalid, "configureddate" => $datetime, "companyname" => $companyname, "companyurl" => $companyurl, "supportemail" => $supportemail), array("id" => "1"));
        return 1;
    }
    update_query($newtlds_DBName, array("configured" => "1", "configureddate" => $datetime, "companyname" => $companyname, "companyurl" => $companyurl, "supportemail" => $supportemail), array("id" => "1"));
    return 2;
}
function newtlds_DB_BootstrapUidPw($enomuid, $enompw)
{
    global $newtlds_DBName;
    $datetime = newtlds_Helper_GetDateTime();
    update_query($newtlds_DBName, array("configureddate" => $datetime, "enomlogin" => $enomuid, "enompassword" => $enompw), array("id" => "1"));
}
function newtlds_DB_GetDefaultcompanyname()
{
    $result = select_query("tblconfiguration", "value", array("setting" => "CompanyName"));
    $data = mysql_fetch_array($result);
    return $data[0];
}
function newtlds_DB_GetDefaultDomainName()
{
    $result = select_query("tblconfiguration", "value", array("setting" => "SystemURL"));
    $data = mysql_fetch_array($result);
    return $data[0];
}
function newtlds_DB_GetDefaultSupportEmail()
{
    $result = select_query("tblconfiguration", "value", array("setting" => "Email"));
    $data = mysql_fetch_array($result);
    return $data[0];
}
function newtlds_Helper_GetDateTime()
{
    $t = microtime(true);
    $micro = sprintf("%06d", ($t - floor($t)) * 1000000);
    $d = new DateTime(date("Y-m-d H:i:s." . $micro, $t));
    return $d->format("Y-m-d H:i:s");
}
function newtlds_Helper_IsNullOrEmptyString($str)
{
    return !isset($str) || trim($str) === "" || strlen($str) == 0;
}
function newtlds_Helper_FormatDomain($domainname)
{
    $website = preg_replace("/^(htt|ht|tt)p\\:?\\/\\//i", "", $domainname);
    if (newtlds_Helper_endsWith($website, "/")) {
        $length = strlen($needle);
        $website = substr($haystack, 0, 0 < $length ? $length - 1 : $length);
    }
    return $website;
}
function newtlds_Helper_startsWith($haystack, $needle)
{
    $length = strlen($needle);
    return substr($haystack, 0, $length) === $needle;
}
function newtlds_Helper_endsWith($haystack, $needle)
{
    $length = strlen($needle);
    if ($length == 0) {
        return true;
    }
    return substr($haystack, 0 - $length) === $needle;
}
function newtlds_AddError($error)
{
    global $newtlds_errormessage;
    if (newtlds_helper_isnulloremptystring($newtlds_errormessage)) {
        $newtlds_errormessage = $error;
    } else {
        $newtlds_errormessage .= "<br />" . $error;
    }
}
function newtlds_Helper_FormatAPICallForEmail($fields, $environment)
{
    $url = "https://" . newtlds_Helper_GetAPIHost($environment) . "/interface.asp?";
    foreach ($fields as $x => $y) {
        $url .= $x . "=" . $y . "&";
    }
    return $url;
}
function newtlds_Helper_GetAPIHost($environment)
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
function newtlds_Helper_GetDocumentationHost($environment)
{
    switch ($environment) {
        case "1":
            $url = "resellertest.enom.com";
            break;
        case "2":
            $url = "enom.staging.local";
            break;
        case "3":
            $url = "enom.build.local";
            break;
        case "4":
            $url = "enom5.enom.com";
            break;
        default:
            $url = "www.enom.com";
            break;
    }
    return $url;
}
function newtlds_Helper_GetWatchlistHost($environment)
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
function newtlds_Helper_Getenvironment($environment)
{
    global $newtlds_DefaultEnvironment;
    if (newtlds_helper_isnulloremptystring($environment)) {
        $data = newtlds_db_getwatchlistsettingslocal();
        $environment = $data["environment"];
    }
    return $environment;
}
function newtlds_Helper_GetWatchlistUrl($domain = "")
{
    global $newtlds_ModuleName;
    if (newtlds_helper_isnulloremptystring($domain)) {
        $data = newtlds_db_getdefaults();
        $domain = $data["companyurl"];
    }
    $domain .= newtlds_helper_endswith($domain, "/") ? "index.php?m=" . $newtlds_ModuleName : "/index.php?" . $newtlds_ModuleName;
    return $domain;
}
function newtlds_API_GetPortalToken($vars, &$token, $fields)
{
    $LANG = $vars["_lang"];
    $postfields = array();
    $postfields["command"] = "PORTAL_GETTOKEN";
    if (is_array($fields)) {
        foreach ($fields as $x => $y) {
            $postfields[$x] = $y;
        }
    }
    $xmldata = newtlds_API_CallEnom($vars, $postfields);
    $success = $xmldata->ErrCount == 0;
    if ($success) {
        $result = "success";
        $token = $xmldata->token;
        return true;
    }
    $result = newtlds_API_HandleErrors($xmldata);
    if (!$result) {
        $result = $LANG["api_unknownerror"];
    }
    newtlds_adderror($result);
    return false;
}
function newtlds_API_HandleErrors($xmldata)
{
    $result = "";
    $errcnt = $xmldata->ErrCount;
    for ($i = 1; $i <= $errcnt; $i++) {
        $result = $xmldata->errors->{"Err" . $i};
        if ($i < $errcnt && 1 < $errcnt) {
            $result .= "<br />";
        }
    }
    return $result;
}
function newtlds_API_CreatePortalAccount($vars, &$portalid, $fields)
{
    $LANG = $vars["_lang"];
    $postfields = array();
    $postfields["command"] = "PORTAL_CREATEPORTAL";
    if (is_array($fields)) {
        foreach ($fields as $x => $y) {
            $postfields[$x] = $y;
        }
    }
    $xmldata = newtlds_API_CallEnom($vars, $postfields);
    $success = $xmldata->ErrCount == 0;
    if ($success) {
        $result = "success";
        $portalid = $xmldata->portalid;
        if (!newtlds_helper_isnulloremptystring($portalid)) {
            return true;
        }
        $portalid = "0";
        return false;
    }
    $result = newtlds_api_handleerrors($xmldata);
    if (!$result) {
        $result = $LANG["api_unknownerror"];
    }
    newtlds_adderror($result);
    $portalid = "0";
    return false;
}
function newtlds_API_UpdatePortalAccount($vars, $portalid, $fields)
{
    $LANG = $vars["_lang"];
    $postfields = array();
    $postfields["command"] = "PORTAL_UPDATEDETAILS";
    $postfields["PortalAccountID"] = $portalid;
    if (is_array($fields)) {
        foreach ($fields as $x => $y) {
            $postfields[$x] = $y;
        }
    }
    $xmldata = newtlds_API_CallEnom($vars, $postfields);
    $success = $xmldata->ErrCount == 0;
    if ($success) {
        return true;
    }
    $result = newtlds_api_handleerrors($xmldata);
    if (!$result) {
        $result = $LANG["api_unknownerror"];
    }
    newtlds_adderror($result);
    return false;
}
function newtlds_API_GetPortalAccount($vars, &$portalid)
{
    $LANG = $vars["_lang"];
    $postfields = array();
    $postfields["command"] = "PORTAL_GETDETAILS";
    if (is_array($fields)) {
        foreach ($fields as $x => $y) {
            $postfields[$x] = $y;
        }
    }
    $xmldata = newtlds_API_CallEnom($vars, $postfields);
    $success = $xmldata->ErrCount == 0;
    if ($success) {
        $result = "success";
        $portalid = $xmldata->tldportaldetails->portalid;
        if (newtlds_helper_isnulloremptystring($portalid)) {
            $portalid = "0";
        }
        return true;
    }
    $result = newtlds_api_handleerrors($xmldata);
    if (!$result) {
        $result = $LANG["api_unknownerror"];
    }
    newtlds_adderror($result);
    $portalid = "0";
    return false;
}
function newtlds_API_CallEnom($vars, $postfields)
{
    global $newtlds_ModuleName;
    global $newtlds_CurrentVersion;
    $LANG = $vars["_lang"];
    $data = newtlds_db_getwatchlistsettingslocal();
    $environment = newtlds_helper_getenvironment($data["environment"]);
    $portalid = $data["portalid"];
    if (!in_array("uid", $postfields)) {
        $enomuid = $data["enomlogin"];
        $postfields["uid"] = $enomuid;
    }
    if (!in_array("pw", $postfields)) {
        $enompw = $data["enompassword"];
        $postfields["pw"] = $enompw;
    }
    if (!in_array("portalid", $postfields) && !newtlds_helper_isnulloremptystring($portalid) && 0 < (int) $portalid) {
        $postfields["portalid"] = $portalid;
    }
    $postfields["ResponseType"] = "XML";
    $postfields["Source"] = "WHMCS";
    $postfields["sourceid"] = "37";
    $postfields["bundled"] = $newtlds_isbundled ? 1 : 0;
    $postfields["pluginversion"] = $newtlds_CurrentVersion;
    $url = "https://" . newtlds_helper_getapihost($environment) . "/interface.asp";
    $data = curlCall($url, $postfields);
    $xmldata = simplexml_load_string($data);
    logModuleCall($newtlds_ModuleName, $postfields["command"], $postfields, $data, $xmldata, array($postfields["pw"], $postfields["rpw"]));
    return $xmldata;
}
function newtlds_output($vars)
{
    global $newtlds_errormessage;
    $newtlds_errormessage = "";
    global $newtlds_isbundled;
    global $newtlds_CurrentVersion;
    $success_message = "";
    $modulelink = $vars["modulelink"];
    $LANG = $vars["_lang"];
    $data = newtlds_db_getwatchlistsettingslocal();
    $companyname = $data["companyname"];
    $companyurl = $data["companyurl"];
    $enomuid = $data["enomlogin"];
    $enompw = $data["enompassword"];
    $portalid = $data["portalid"];
    $supportemail = $data["supportemail"];
    $environment = newtlds_helper_getenvironment($data["environment"]);
    $configured = newtlds_db_getwatchlistportalexists();
    $form_iframe_tab = $configured ? 2 : 1;
    $form_button_text = $configured ? $LANG["form_update"] : $LANG["form_activate"];
    $form_terms_text = $configured ? $LANG["form_terms2"] : $LANG["form_terms1"];
    $documentation_link = $LANG["documentation"];
    $url = newtlds_helper_getdocumentationhost($environment);
    if ($environment != "0") {
        $documentation_link = str_replace("www.enom.com", $url, $documentation_link);
        $form_terms_text = str_replace("www.enom.com", $url, $form_terms_text);
    }
    $create = false;
    $update = false;
    if (newtlds_helper_isnulloremptystring($companyname) || newtlds_helper_isnulloremptystring($companyurl) || newtlds_helper_isnulloremptystring($supportemail)) {
        $data = newtlds_db_getdefaults();
        if (newtlds_helper_isnulloremptystring($companyname)) {
            $companyname = $data["companyname"];
        }
        if (newtlds_helper_isnulloremptystring($companyurl)) {
            $companyurl = newtlds_helper_getwatchlisturl($data["companyurl"]);
        }
        if (newtlds_helper_isnulloremptystring($supportemail)) {
            $supportemail = $data["supportemail"];
        }
    }
    if (isset($_POST["enomuid"])) {
        $enomuid = $_POST["enomuid"];
        $enompw = $_POST["enompw"];
        if ($enompw === "************") {
            $enompw = $data["enompassword"];
        }
        $companyname = $_POST["companyname"];
        $companyurl = $_POST["companyurl"];
        $supportemail = $_POST["supportemail"];
        $success = true;
        if (newtlds_helper_isnulloremptystring($enomuid)) {
            newtlds_adderror($LANG["enomuidrequired"]);
            $success = false;
        }
        if (newtlds_helper_isnulloremptystring($enompw)) {
            newtlds_adderror($LANG["enompwdrequired"]);
            $success = false;
        }
        if (newtlds_helper_isnulloremptystring($companyname)) {
            $companyname = newtlds_db_getdefaultcompanyname();
        }
        if (newtlds_helper_isnulloremptystring($companyurl)) {
            $companyurl = newtlds_helper_getwatchlisturl();
        }
        if (newtlds_helper_isnulloremptystring($supportemail)) {
            $supportemail = newtlds_db_getdefaultsupportemail();
        }
        if ($success) {
            newtlds_db_bootstrapuidpw(encrypt($enomuid), encrypt($enompw));
            $fields = array();
            $fields["companyurl"] = $companyurl;
            $fields["companyname"] = $companyname;
            $fields["supportemailaddress"] = $supportemail;
            $fields["portalType"] = "2";
            $fields["statusid"] = "1";
            if (newtlds_helper_isnulloremptystring($portalid) || (int) $portalid <= 0) {
                $nofields = array();
                $success = newtlds_api_getportalaccount($vars, $portalid, $nofields);
            }
            if ($success) {
                if (newtlds_helper_isnulloremptystring($portalid) || (int) $portalid <= 0) {
                    $create = true;
                    $success = newtlds_api_createportalaccount($vars, $portalid, $fields);
                } else {
                    $update = true;
                    $success = newtlds_api_updateportalaccount($vars, $portalid, $fields);
                }
            } else {
                newtlds_adderror($LANG["api_failedtoget"]);
                $success = false;
            }
            if ($success && ($update || $create)) {
                $mydata = array();
                $mydata["enomLogin"] = encrypt($enomuid);
                $mydata["enomPassword"] = encrypt($enompw);
                $mydata["companyname"] = $companyname;
                $mydata["companyurl"] = $companyurl;
                $mydata["supportemail"] = $supportemail;
                $result = newtlds_db_updatedb($mydata, $portalid);
                if ($result == 1) {
                    $success_message = $LANG["api_setupsuccess"];
                } else {
                    $success_message = $LANG["api_setupsuccess2"];
                }
            } else {
                if ($create || $update) {
                    newtlds_adderror($create ? $LANG["api_failedtocreate"] : $LANG["api_failedtoupdate"]);
                }
            }
        }
    }
    $errormessage = $newtlds_errormessage;
    echo "\n<script type=\"text/javascript\" language=\"JavaScript\">\n    \$(\"#floatbar\").click(function (e) {\n        e.preventDefault();\n        \$(this).find(\".popup\").fadeIn(\"slow\");\n    });\n\n    function InvalidValue(item) {\n        var control = document.getElementById(item);\n        if (control != null)\n        { control.style.backgroundColor = \"#FFE4E1\"; }\n    }\n\n    function RevertForm(item) {\n        var control = document.getElementById(item);\n        if (control != null)\n        { control.style.backgroundColor = \"\"; }\n    }\n    function ReturnFalse(msg) {\n        alert(msg);\n        return false;\n    }\n    function ValidateEmail(strValue) {\n        if (window.echeck(strValue)) {\n            var objRegExp = /(^[a-zA-Z0-9\\-_\\.]([a-zA-Z0-9\\-_\\.]*)@([a-z_\\.]*)([.][a-z]{3})\$)|(^[a-z]([a-z_\\.]*)@([a-z_\\.]*)(\\.[a-z]{3})(\\.[a-z]{2})*\$)/i;\n            return objRegExp.test(strValue);\n        }\n        return false;\n    }\n\n    function ValidateForm() {\n        var email = document.getElementById('supportemail');\n        var enomuid = document.getElementById('enomuid');\n        var enompw = document.getElementById('enompw');\n        var companyurl = document.getElementById('companyurl');\n        var companyname = document.getElementById('companyname');\n        var msg = '';\n\n         if (enomuid.value == \"\") {\n            InvalidValue('enomuid');\n            msg += \"eNom LoginID is required\\n\";\n        } else { RevertForm('enomuid'); }\n\n        if (enompw.value == \"\") {\n            InvalidValue('enompw');\n            msg += \"eNom Password is required\\n\";\n        } else { RevertForm('enompw'); }\n\n       if (email.value == \"\") {\n            InvalidValue('supportemail');\n            msg += \"Support Email Address is required\\n\";\n        } else { RevertForm('supportemail'); }\n\n        if (companyname.value == \"\") {\n            InvalidValue('companyname');\n            msg += \"Company Name is required\\n\";\n        } else { RevertForm('companyname'); }\n\n        if (companyurl.value == \"\") {\n            InvalidValue('companyurl');\n            msg += \"Company Url is required\\n\";\n        } else { RevertForm('companyurl'); }\n\n        if(msg != '')\n            return ReturnFalse(msg);\n\n        return true;\n    }\n\n    function ResetDefault()\n    {\n        var companyurl = document.getElementById('companyurl');\n        companyurl.value = '";
    echo newtlds_helper_getwatchlisturl();
    echo "';\n    }\n\n</script>\n\n<style type=\"text/css\">\n\n\t.tld_wrp {margin-top:10px;font:16px/24px Arial, Verdana, Helvetica;padding:10px;color:#3C3C3C;background-color:#FFF;-webkit-border-radius:5px;border-radius:5px}\n\t.tld_wrp DIV,\n\t.tld_wrp SPAN,\n\t.tld_wrp A,\n\t.tld_wrp IMG,\n\t.tld_wrp STRONG,\n\t.tld_wrp FORM,\n\t.tld_wrp TABLE,\n\t.tld_wrp TR,\n\t.tld_wrp TH,\n\t.tld_wrp TD {font-family:inherit;font-size:inherit;line-height:inherit;margin:0;padding:0;border:0;vertical-align:baseline;background-repeat:no-repeat;-webkit-appearance:none;-moz-appearance:none;appearance:none;-webkit-text-size-adjust:none;-ms-text-size-adjust:none}\n\t.tld_wrp STRONG {font-weight:bold}\n\t.tld_wrp A {text-decoration:none;cursor:pointer;color:#024DD6}\n\t.tld_wrp A:Hover {text-decoration:underline}\n\t.tld_wrp TABLE {border-collapse:collapse;border-spacing:0}\n\t.tld_wrp TH,\n\t.tld_wrp TD {font-weight:normal;vertical-align:top;text-align:left}\n\t.tld_wrp IMG {font-size:0;vertical-align:middle;max-width:100%;height:auto;-ms-interpolation-mode:bicubic}\n\t.tld_wrp INPUT[type=text],\n\t.tld_wrp INPUT[type=password] {-webkit-appearance:none;-moz-appearance:none;appearance:none;-webkit-box-sizing:content-box;-moz-box-sizing:content-box;box-sizing:content-box;margin-bottom:3px;color:#000;display:inline;padding:0;font-weight:normal;vertical-align:baseline;font-family:\"Helvetica Neue\",Helvetica,Arial,sans-serif;font-size:13px;line-height:20px;height:20px;border-style:solid;border-color:#000 #CCC #CCC #000;-webkit-border-radius:2px;border-radius:2px;background-color:#FFF;background-size:100% 100%;margin-bottom:3px;border-width:1px;background-image:-webkit-gradient(linear, left top, left bottom, from(#EEE), to(#FFF));background-image:-webkit-linear-gradient(#EEE 0%, #FFF 100%);background-image:-moz-linear-gradient(#EEE 0%, #FFF 100%);background-image:-ms-linear-gradient(#EEE 0%, #FFF 100%);background-image:-o-linear-gradient(#EEE 0%, #FFF 100%);background-image:linear-gradient(#EEE 0%, #FFF 100%)}\n\n\t.tld_wrp .sError1,\n\t.tld_wrp .sSuccess1 {text-align:left;padding:8px 10px 8px 42px;line-height:18px;font-size:14px;margin:1px 0 15px 0;position:relative;z-index:1;border:1px solid #000000;-moz-border-radius:5px;-webkit-border-radius:5px;border-radius:5px}\n\t.tld_wrp .sError1:Before,\n\t.tld_wrp .sSuccess1:Before {content:\"\";position:absolute;top:4px;left:10px;z-index:2;height:24px;width:24px;background:transparent url('../modules/addons/newtlds/images/ico-info24x.png') no-repeat 0 0}\n\t.tld_wrp .sError1 {border-color:#CC9999;color:#C00;background:#FFEAEA}\n\t.tld_wrp .sSuccess1 {border-color:#A7B983;color:#333;background:#E8FF74}\n\n\t.tld_wrp .clearfix:after {content: \".\"; display: block; height: 0; clear: both; visibility: hidden;}\n\t.tld_wrp .clearfix {display: inline-block;}\n\n</style>\n\n\n<form method=\"post\" action=\"";
    echo $modulelink;
    echo "\">\n\n\t<div class=\"tld_wrp\" style=\"margin:0 auto;padding:10px;max-width:852px;\">\n\n\t\t";
    if (!newtlds_helper_isnulloremptystring($errormessage)) {
        echo "\t\t\t<div class=\"sError1\">\n\t\t\t\t<strong>";
        echo $errormessage;
        echo "</strong>\n\t\t\t</div>\n\t\t";
    }
    echo "\n\t\t";
    if (!newtlds_helper_isnulloremptystring($success_message)) {
        echo "\t\t\t<div class=\"sSuccess1\">\n\t\t\t\t<strong>";
        echo $success_message;
        echo "</strong>\n\t\t\t</div>\n\t\t";
    }
    echo "\n\t\t<div class=\"clearfix\" style=\"display:block;clear:both;border:1px solid #CCC;max-width:850px;font-weight:bold;font-size:14px;background-color:#EEE\">\n\n            <div class=\"row\">\n                <div class=\"col-md-7\">\n                <div style=\"min-height:485px;background-color:#FFF;\">\n\t\t\t\t<div style=\"padding:20px\">\n\n\t\t\t\t\t<table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\">\n\t\t\t\t\t\t<tr>\n\t\t\t\t\t\t\t<td width=\"50%\" style=\"font-size:14px\">\n\t\t\t\t\t\t\t\t<strong>";
    echo $LANG["form_enomloginid"];
    echo "</strong> <span style=\"color:red\">*</span>\n\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t<td width=\"50%\"style=\"text-align:right\">\n\t\t\t\t\t\t\t\t<a href=\"https://www.whmcs.com/members/freeenomaccount.php\" target=\"_blank\"\">";
    echo $LANG["form_getenomaccount"];
    echo "</a>\n\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t<tr>\n\t\t\t\t\t\t\t<td colspan=\"2\" width=\"100%\" style=\"font-size:14px;padding-bottom:10px\">\n\t\t\t\t\t\t\t\t<input type=\"text\" style=\"width:99%\" name=\"enomuid\" id=\"enomuid\" value=\"";
    echo $enomuid;
    echo "\" onfocus=\"RevertForm(this.id);\" />\n\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t<tr>\n\t\t\t\t\t\t\t<td colspan=\"2\" width=\"100%\" style=\"font-size:14px;padding-bottom:10px\">\n\t\t\t\t\t\t\t\t<strong>";
    echo $LANG["form_enompassword"];
    echo "</strong> <span style=\"color:red\">*</span><br />\n\t\t\t\t\t\t\t\t<input type=\"password\" style=\"width:99%\" name=\"enompw\" id=\"enompw\" value=\"";
    if (!newtlds_helper_isnulloremptystring($enompw)) {
        echo "************";
    }
    echo "\" onfocus=\"RevertForm(this.id);\" />\n\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t<tr>\n\t\t\t\t\t\t\t<td colspan=\"2\" width=\"100%\" style=\"font-size:14px;padding-bottom:10px\">\n\t\t\t\t\t\t\t\t<strong>";
    echo $LANG["form_companyname"];
    echo "</strong> <span style=\"color:red\">*</span><br />\n\t\t\t\t\t\t\t\t<input type=\"text\" name=\"companyname\" style=\"width:99%\" id=\"companyname\" value=\"";
    echo $companyname;
    echo "\" onfocus=\"RevertForm(this.id);\" />\n\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t<tr>\n\t\t\t\t\t\t\t<td colspan=\"2\" width=\"100%\" style=\"font-size:14px;padding-bottom:10px\">\n\t\t\t\t\t\t\t\t<strong>";
    echo $LANG["form_supportemail"];
    echo "</strong> <span style=\"color:red\">*</span><br />\n\t\t\t\t\t\t\t\t<input type=\"text\" name=\"supportemail\" style=\"width:99%\" id=\"supportemail\" value=\"";
    echo $supportemail;
    echo "\" onfocus=\"RevertForm(this.id);\" />\n\t\t\t\t\t\t\t\t<div style=\"margin-top:0;font-size:12px;line-height:16px;color:#666\">";
    echo $LANG["form_support_email_desc"];
    echo "</div>\n\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t<tr>\n\t\t\t\t\t\t\t<td colspan=\"2\" width=\"100%\" style=\"font-size:14px;border-bottom:dotted 1px #CCC;padding-bottom:10px\">\n\t\t\t\t\t\t\t\t<strong>";
    echo $LANG["form_companyurl"];
    echo "</strong> <span style=\"color:red\">*</span><br />\n\t\t\t\t\t\t\t\t<input type=\"text\" name=\"companyurl\" style=\"width:99%\" id=\"companyurl\" value=\"";
    echo $companyurl;
    echo "\" onfocus=\"RevertForm(this.id);\" />\n\t\t\t\t\t\t\t\t<div style=\"margin-top:0;font-size:12px;line-height:16px;color:#666;padding-bottom:10px\">\n\t\t\t\t\t\t\t\t\t";
    echo $LANG["form_companyurl_text"];
    echo " <a href=\"javascript:void(0)\" onclick=\"ResetDefault();\">";
    echo $LANG["form_resetdefault"];
    echo "</a>\n\t\t\t\t\t\t\t\t</div>\n\t\t\t\t\t\t\t\t<div>";
    echo $form_terms_text;
    echo "</div>\n\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t<tr>\n\t\t\t\t\t\t\t<td width=\"50%\" valign=\"bottom\" style=\"padding-top:15px\">\n\t\t\t\t\t\t\t\t<input type=\"submit\" value=\"";
    echo $form_button_text;
    echo " &raquo;\" style=\"cursor:pointer;border-style:outset;padding:7px;font-size:1.55em;*font-size:1.3em;font-family:Arial, Helvetica, sans-serif;font-weight:normal;-moz-border-radius:5px;-webkit-border-radius:5px;border-radius:5px;border-width:1px\" onclick=\"return ValidateForm();\" />\n\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t<td width=\"50%\" valign=\"bottom\" style=\"text-align:right;padding-top:15px\">\n\t\t\t\t\t\t\t\t<img src=\"../modules/addons/newtlds/images/enom.gif\" border=\"0\" />\n\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t</tr>\n\t\t\t\t\t\t<!--<tr>\n\t\t\t\t\t\t\t<td colspan=\"2\" width=\"100%\"><p>";
    echo $documentation_link;
    echo "</p></td>\n\t\t\t\t\t\t</tr>-->\n\t\t\t\t\t</table>\n\n\t\t\t\t</div>\n            </div>\n        </div>\n        <div class=\"col-md-5\">\n            <div style=\"min-height:485px\">\n\t\t\t\t<div style=\"padding:20px\">\n\t\t\t\t\t<div style=\"border:1px solid #CCC;background:#FFF;\">\n\t\t\t\t\t\t<iframe frameborder=\"0\" height=\"440px\" width=\"100%\" marginheight=\"0\" marginwidth=\"0\" scrolling=\"yes\" src=\"https://";
    echo $url;
    echo "/whmcs/tld-portal/addon-iframe.aspx?p=";
    echo $form_iframe_tab;
    echo "&version=";
    echo $newtlds_CurrentVersion;
    echo "&bundled=";
    echo $newtlds_isbundled ? "1" : "0";
    echo "\"></iframe>\n\t\t\t\t\t</div>\n\t\t\t\t</div>\n\t\t\t</div>\n            </div>\n        </div>\n\n\t</div>\n\n</div>\n\n</form>\n\n\n\n\n\n";
}

?>