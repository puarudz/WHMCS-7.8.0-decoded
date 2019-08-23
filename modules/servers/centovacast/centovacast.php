<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require_once dirname(__FILE__) . "/class_APIClient.php";
define("CC_TXT_MAXCLIENTS", "Max listeners");
define("CC_TXT_MAXBITRATE", "Max bit rate");
define("CC_TXT_XFERLIMIT", "Data transfer limit");
define("CC_TXT_DISKQUOTA", "Disk quota");
define("CC_TXT_MAXBW", "Max bandwidth");
define("CC_TXT_MAXACCT", "Max accounts");
define("CC_TXT_MOUNTLIMIT", "Mount point limit");
function centovacast_MetaData()
{
    return array("DisplayName" => "Centova Cast", "APIVersion" => "1.0");
}
function centovacast_ConfigOptions()
{
    $configarray = array("Account template name" => array("Type" => "text", "Size" => "20", "Description" => "<br />(create this in Centova Cast)"), "Max listeners" => array("Type" => "text", "Size" => "5", "Description" => "(simultaneous)<br />(blank to use template setting)"), "Max bit rate" => array("Type" => "dropdown", "Options" => ",8,16,20,24,32,40,48,56,64,80,96,112,128,160,192,224,256,320", "Description" => "kbps<br />(blank to use template setting)"), "Data transfer limit" => array("Type" => "text", "Size" => "5", "Description" => "MB/month<br />(blank to use template setting)"), "Disk quota" => array("Type" => "text", "Size" => "5", "Description" => "MB<br />(blank to use template setting)"), "Start server" => array("Type" => "dropdown", "Options" => "no,yes", "Description" => "<br>(only used if source is disabled)"), "Mount point limit" => array("Type" => "text", "Size" => "5", "Description" => "<br />(blank to use template setting)"), "Port 80 proxy" => array("Type" => "dropdown", "Options" => ",Enabled,Disabled", "Description" => "<br />(blank to use template setting)"), "AutoDJ support" => array("Type" => "dropdown", "Options" => ",Enabled,Disabled", "Description" => "<br />(blank to use template setting)"), "Max accounts" => array("Type" => "text", "Size" => "5", "Description" => "(resellers only)<br />(blank to use template setting)"), "Max bandwidth" => array("Type" => "text", "Size" => "5", "Description" => "kbps (resellers only)<br />(blank to use template setting)"));
    return $configarray;
}
function centovacast_QueryOneRow()
{
    $args = func_get_args();
    if (!count($args)) {
        return false;
    }
    $query = array_shift($args);
    foreach ($args as $k => $arg) {
        $args[$k] = mysql_real_escape_string($arg);
    }
    if (count($args)) {
        $query = vsprintf($query, $args);
    }
    $rsh = full_query($query);
    if (!$rsh) {
        return false;
    }
    $row = mysql_fetch_assoc($rsh);
    if (!is_array($row)) {
        return false;
    }
    return $row;
}
function centovacast_QueryAllRows()
{
    $args = func_get_args();
    if (!count($args)) {
        return false;
    }
    $query = array_shift($args);
    foreach ($args as $k => $arg) {
        $args[$k] = mysql_real_escape_string($arg);
    }
    if (count($args)) {
        $query = vsprintf($query, $args);
    }
    $rsh = full_query($query);
    if (!$rsh) {
        return false;
    }
    $rows = array();
    $row = mysql_fetch_assoc($rsh);
    while ($row) {
        $rows[] = $row;
        $row = mysql_fetch_assoc($rsh);
    }
    return $rows;
}
function centovacast_GetCCURL($params, &$error)
{
    $error = false;
    $ccurl = $params["serverhostname"];
    if (!preg_match("#^https?://#", $ccurl)) {
        $error = "Invalid 'Hostname' setting in WHMCS configuration for Centova Cast.  Per the documentation the 'Hostname' field must contain the complete URL to Centova Cast, not just a hostname.";
        return false;
    }
    return $params["serverhostname"];
}
function centovacast_GetServerCredentials($params, $serverapi = false)
{
    $serverusername = $params["serverusername"];
    $serverpassword = $params["serverpassword"];
    if ($serverusername != "admin" || $serverapi) {
        $serverpassword = $serverusername . "|" . $serverpassword;
    }
    return array($serverusername, $serverpassword);
}
function centovacast_GetAPIArgs($params, &$arguments)
{
    $packageid = $params["packageid"];
    $templatename = $params["configoption1"];
    $maxlisteners = $params["configoption2"];
    $maxbitrate = $params["configoption3"];
    $xferquota = $params["configoption4"];
    $diskquota = $params["configoption5"];
    $autostart = $params["configoption6"];
    $mountlimit = $params["configoption7"];
    $webproxy = $params["configoption8"];
    $autodj = $params["configoption9"];
    $maxaccounts = $params["configoption10"];
    $maxbw = $params["configoption11"];
    if (!strlen($templatename)) {
        return "Missing account template name in WHMCS package configuration for package " . $packageid . "; check your WHMCS package configuration.";
    }
    $arguments["template"] = $templatename;
    if (strlen($maxlisteners)) {
        $arguments["maxclients"] = $maxlisteners;
    }
    if (strlen($maxbitrate)) {
        $arguments["maxbitrate"] = $maxbitrate;
    }
    if (strlen($xferquota)) {
        $arguments["transferlimit"] = $xferquota;
    }
    if (strlen($diskquota)) {
        $arguments["diskquota"] = $diskquota;
    }
    if (strlen($autostart)) {
        $arguments["autostart"] = $autostart == "yes" ? 1 : 0;
    }
    if (strlen($mountlimit)) {
        $arguments["mountlimit"] = max(1, (int) $mountlimit);
    }
    if (strlen($webproxy)) {
        $arguments["allowproxy"] = strtolower($webproxy[0]) == "d" ? 0 : 1;
    }
    if (strlen($autodj)) {
        $arguments["usesource"] = strtolower($webproxy[0]) == "d" ? 1 : 2;
    }
    if (strlen($maxaccounts)) {
        $arguments["resellerusers"] = $maxaccounts;
    }
    if (strlen($maxbw)) {
        $arguments["resellerbandwidth"] = $maxbw;
    }
    $addonmap = array(CC_TXT_MAXCLIENTS => "maxclients", CC_TXT_MAXBITRATE => "maxbitrate", CC_TXT_XFERLIMIT => "transferlimit", CC_TXT_DISKQUOTA => "diskquota", CC_TXT_MAXBW => "resellerbandwidth", CC_TXT_MAXACCT => "resellerusers", CC_TXT_MOUNTLIMIT => "mountlimit");
    if (is_array($params["configoptions"])) {
        foreach ($params["configoptions"] as $caption => $value) {
            if (strlen($value) && isset($addonmap[$caption])) {
                $optionname = $addonmap[$caption];
                $value = preg_replace("/[^0-9]/", "", $value);
                $arguments[$optionname] = $value;
            }
        }
    }
    return true;
}
function centovacast_CreateAccount($params)
{
    $serverip = $params["serverip"];
    list($serverusername, $serverpassword) = centovacast_getservercredentials($params);
    $username = $params["username"];
    $password = $params["password"];
    $model = array_key_exists("model", $params) ? $params["model"] : NULL;
    $updateFields = array();
    if (!strlen($username) || is_numeric($username)) {
        $params["username"] = $username = createServerUsername($params["clientsdetails"]["companyname"] . $params["clientsdetails"]["firstname"]);
        $updateFields["username"] = $username;
    }
    if (!strlen($password)) {
        $params["password"] = $password = generateFriendlyPassword(8);
        $updateFields["password"] = $password;
    }
    if ($updateFields) {
        $params["model"]->serviceProperties->save($updateFields);
    }
    if (false === ($ccurl = centovacast_getccurl($params, $urlerror))) {
        return $urlerror;
    }
    $clientsdetails = $params["clientsdetails"];
    $arguments = array("hostname" => "auto", "ipaddress" => "auto", "port" => "auto", "username" => $username, "adminpassword" => $password, "sourcepassword" => $password . "dj", "email" => $clientsdetails["email"], "title" => sprintf("%s Stream", strlen($clientsdetails["companyname"]) ? $clientsdetails["companyname"] : $clientsdetails["lastname"]), "organization" => $clientsdetails["companyname"], "introfile" => "", "fallbackfile" => "", "autorebuildlist" => 1);
    $error = centovacast_getapiargs($params, $arguments);
    if (is_string($error)) {
        return $error;
    }
    $system = new CCSystemAPIClient($ccurl);
    if ($_REQUEST["ccmoduledebug"]) {
        $system->debug = true;
    }
    $system->call("provision", $serverpassword, $arguments);
    logModuleCall("centovacast", "create", $system->raw_request, $system->raw_response, NULL, array($serverpassword));
    if ($system->success) {
        $account = $system->data["account"];
        $account["sourcepassword"] = $arguments["sourcepassword"];
        $modelToUse = $serviceModel;
        $addon = false;
        if ($addonModel) {
            $modelToUse = $addonModel;
            $addon = true;
        }
        $id = $modelToUse->id;
        if ($id) {
            $packageId = $addon ? $modelToUse->addonId : $modelToUse->packageId;
            if ($packageId) {
                $customFields = $addon ? $modelToUse->productAddon->customFields : $modelToUse->product->customFields;
                foreach ($customFields as $customField) {
                    $fieldName = $customField->fieldName;
                    $fieldId = $customField->id;
                    if (array_key_exists($fieldName, $account)) {
                        $value = $account[$fieldName];
                        $customFieldValue = $modelToUse->customFieldValues()->firstOrNew(array("fieldid" => $fieldId, "relid" => $id));
                        $customFieldValue->value = $value;
                        $customFieldValue->save();
                    }
                }
            }
        }
    }
    return $system->success ? "success" : $system->error;
}
function centovacast_ChangePackage($params)
{
    list($serverusername, $serverpassword) = centovacast_getservercredentials($params, true);
    $username = $params["username"];
    $password = $params["password"];
    if (false === ($ccurl = centovacast_getccurl($params, $urlerror))) {
        return $urlerror;
    }
    $server = new CCServerAPIClient($ccurl);
    $arguments = array();
    $server->call("getaccount", $username, $serverpassword, $arguments);
    if (!$server->success) {
        return $server->error;
    }
    if (!is_array($server->data) || !count($server->data)) {
        return "Error fetching account information from Centova Cast";
    }
    $account = $server->data["account"];
    if (!is_array($account) || !isset($account["username"])) {
        return "Account does not exist in Centova Cast";
    }
    $error = centovacast_getapiargs($params, $account);
    if (is_string($error)) {
        return $error;
    }
    unset($account["template"]);
    $server->call("reconfigure", $username, $serverpassword, $account);
    logModuleCall("centovacast", "changepackage", $server->raw_request, $server->raw_response, NULL, array($serverpassword));
    return $server->success ? "success" : $server->error;
}
function centovacast_TerminateAccount($params)
{
    list($serverusername, $serverpassword) = centovacast_getservercredentials($params);
    $username = $params["username"];
    if (false === ($ccurl = centovacast_getccurl($params, $urlerror))) {
        return $urlerror;
    }
    $system = new CCSystemAPIClient($ccurl);
    $arguments = array("username" => $username);
    $system->call("terminate", $serverpassword, $arguments);
    logModuleCall("centovacast", "terminate", $system->raw_request, $system->raw_response, NULL, array($serverpassword));
    return $system->success ? "success" : $system->error;
}
function centovacast_SuspendAccount($params)
{
    list($serverusername, $serverpassword) = centovacast_getservercredentials($params);
    $username = $params["username"];
    if (false === ($ccurl = centovacast_getccurl($params, $urlerror))) {
        return $urlerror;
    }
    $system = new CCSystemAPIClient($ccurl);
    $arguments = array("username" => $username, "status" => "disabled");
    $system->call("setstatus", $serverpassword, $arguments);
    logModuleCall("centovacast", "suspend", $system->raw_request, $system->raw_response, NULL, array($serverpassword));
    return $system->success ? "success" : $system->error;
}
function centovacast_UnsuspendAccount($params)
{
    list($serverusername, $serverpassword) = centovacast_getservercredentials($params);
    $username = $params["username"];
    if (false === ($ccurl = centovacast_getccurl($params, $urlerror))) {
        return $urlerror;
    }
    $system = new CCSystemAPIClient($ccurl);
    $arguments = array("username" => $username, "status" => "enabled");
    $system->call("setstatus", $serverpassword, $arguments);
    logModuleCall("centovacast", "unsuspend", $system->raw_request, $system->raw_response, NULL, array($serverpassword));
    return $system->success ? "success" : $system->error;
}
function centovacast_ChangePassword($params)
{
    list($serverusername, $serverpassword) = centovacast_getservercredentials($params, true);
    $username = $params["username"];
    $password = $params["password"];
    if (false === ($ccurl = centovacast_getccurl($params, $urlerror))) {
        return $urlerror;
    }
    $server = new CCServerAPIClient($ccurl);
    $arguments = array();
    $server->call("getaccount", $username, $serverpassword, $arguments);
    if (!$server->success) {
        return $server->error;
    }
    if (!is_array($server->data) || !count($server->data)) {
        return "Error fetching account information from Centova Cast";
    }
    $account = $server->data["account"];
    if (!is_array($account) || !isset($account["username"])) {
        return "Account does not exist in Centova Cast";
    }
    $account["adminpassword"] = $password;
    $server->call("reconfigure", $username, $serverpassword, $account);
    logModuleCall("centovacast", "changepassword", $server->raw_request, $server->raw_response, NULL, array($serverpassword));
    return $server->success ? "success" : $server->error;
}
function centovacast_AdminCustomButtonArray()
{
    return array("Start Stream" => "StartStream", "Stop Stream" => "StopStream", "Restart Stream" => "RestartStream");
}
function centovacast_ClientArea($params)
{
    if (false === ($ccurl = centovacast_getccurl($params, $urlerror))) {
        return $urlerror;
    }
    $whmcs = WHMCS\Application::getInstance();
    $username = $params["username"];
    $password = $params["password"];
    if (substr($ccurl, -1) != "/") {
        $ccurl .= "/";
    }
    $loginurl = $ccurl . "login/index.php";
    $time = time();
    $authtoken = sha1($username . $password . $time);
    $form = sprintf("<form method=\"post\" action=\"%s\" target=\"_blank\">" . "<input type=\"hidden\" name=\"username\" value=\"%s\" />" . "<input type=\"hidden\" name=\"password\" value=\"%s\" />" . "<input type=\"submit\" name=\"login\" value=\"%s\" />" . "</form>", WHMCS\Input\Sanitize::encode($loginurl), WHMCS\Input\Sanitize::encode($username), WHMCS\Input\Sanitize::encode($password), $whmcs->get_lang("centovacastlogin"));
    $fn = dirname(__FILE__) . "/client_area.html";
    if (file_exists($fn)) {
        if ($_SERVER["HTTPS"] == "on") {
            $ccurl = preg_replace("/^http:/", "https:", $ccurl);
        }
        $details = preg_replace("/<!--[\\s\\S]*?-->/", "", str_replace(array("[CCURL]", "[USERNAME]", "[TIME]", "[AUTH]"), array($ccurl, preg_replace("/[^a-z0-9_]+/i", "", $username), $time, $authtoken), file_get_contents($fn)));
    } else {
        $details = "";
    }
    return $form . $details;
}
function centovacast_AdminLink($params)
{
    $query = "SELECT hostname FROM tblservers WHERE tblservers.ipaddress=\"%s\" AND tblservers.username=\"%s\" AND tblservers.type=\"centovacast\" LIMIT 1";
    $res = centovacast_queryonerow($query, $params["serverip"], $params["serverusername"]);
    if (!$res["hostname"]) {
        return "";
    }
    $params["serverhostname"] = $res["hostname"];
    $serverusername = $params["serverusername"];
    $serverpassword = $params["serverpassword"];
    if (false === ($ccurl = centovacast_getccurl($params, $urlerror))) {
        return $urlerror;
    }
    if (substr($ccurl, -1) != "/") {
        $ccurl .= "/";
    }
    $ccurl .= "login/index.php";
    return sprintf("<form method=\"post\" action=\"%s\" target=\"_blank\">" . "<input type=\"hidden\" name=\"username\" value=\"%s\" />" . "<input type=\"hidden\" name=\"password\" value=\"%s\" />" . "<input type=\"submit\" name=\"login\" value=\"%s\" />" . "</form>", WHMCS\Input\Sanitize::makeSafeForOutput($ccurl), WHMCS\Input\Sanitize::makeSafeForOutput($serverusername), WHMCS\Input\Sanitize::makeSafeForOutput($serverpassword), "Log in to Centova Cast");
}
function centovacast_SetState($params, $newstate)
{
    if (!in_array($newstate, array("start", "stop", "restart"))) {
        return "Invalid state";
    }
    list($serverusername, $serverpassword) = centovacast_getservercredentials($params, true);
    $username = $params["username"];
    if (false === ($ccurl = centovacast_getccurl($params, $urlerror))) {
        return $urlerror;
    }
    $server = new CCServerAPIClient($ccurl);
    $arguments = array();
    $server->call($newstate, $username, $serverpassword, $arguments);
    logModuleCall("centovacast", "setstate", $server->raw_request, $server->raw_response, NULL, array($serverpassword));
    return $server->success ? "success" : $server->error;
}
function centovacast_StartStream($params)
{
    return centovacast_setstate($params, "start");
}
function centovacast_StopStream($params)
{
    return centovacast_setstate($params, "stop");
}
function centovacast_RestartStream($params)
{
    return centovacast_setstate($params, "restart");
}
function centovacast_UsageUpdate($params)
{
    list($serverusername, $serverpassword) = centovacast_getservercredentials($params);
    if (false === ($ccurl = centovacast_getccurl($params, $urlerror))) {
        return $urlerror;
    }
    $system = new CCSystemAPIClient($ccurl);
    if ($_REQUEST["ccmoduledebug"]) {
        $system->debug = true;
    }
    $arguments = array();
    $system->call("usage", $serverpassword, $arguments);
    logModuleCall("centovacast", "usageupdate", $system->raw_request, $system->raw_response, NULL, array($serverpassword));
    if (!$system->success) {
        return $system->error;
    }
    if (!is_array($system->data) || !count($system->data)) {
        return "Error fetching account information from Centova Cast";
    }
    $accounts = $system->data["row"];
    if (!is_array($accounts) || !count($accounts)) {
        return "No accounts in Centova Cast";
    }
    $serverid = $params["serverid"];
    $hostingAccounts = WHMCS\Service\Service::where("server", "=", $serverid)->whereIn("domainstatus", array("Active", "Suspended"))->get();
    $hostingAddonAccounts = WHMCS\Service\Addon::where("server", "=", $serverid)->whereIn("status", array("Active", "Suspended"))->get();
    foreach ($accounts as $k => $values) {
        $addonAccount = NULL;
        $updateData = array("diskusage" => $values["diskusage"], "disklimit" => max(0, $values["diskquota"]), "bwusage" => $values["transferusage"], "bwlimit" => max(0, $values["transferlimit"]), "lastupdate" => WHMCS\Carbon::now()->toDateTimeString());
        $model = $hostingAccounts->where("username", $values["username"])->first();
        if (!$model) {
            foreach ($hostingAddonAccounts as $hostingAddonAccount) {
                $username = $hostingAddonAccount->serviceProperties->get("username");
                if ($username == $values["username"]) {
                    $model = $hostingAddonAccount;
                    break;
                }
            }
            if (!$addonAccount) {
                break;
            }
        }
        $model->serviceProperties->save($updateData);
    }
    return "success";
}

?>