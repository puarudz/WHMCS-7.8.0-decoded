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
$licensing = DI::make("license");
if (defined("DACONFPACKAGEADDONLICENSE")) {
    exit("License Hacking Attempt Detected");
}
define("DACONFPACKAGEADDONLICENSE", $licensing->isActiveAddon("Configurable Package Addon"));
function directadmin_MetaData()
{
    return array("DisplayName" => "DirectAdmin", "APIVersion" => "1.1", "DefaultNonSSLPort" => "2222", "DefaultSSLPort" => "2222", "ListAccountsUniqueIdentifierDisplayName" => "Domain", "ListAccountsUniqueIdentifierField" => "domain", "ListAccountsProductField" => "configoption1");
}
function directadmin_ConfigOptions(array $params)
{
    $resellerSimpleMode = $params["producttype"] == "reselleraccount";
    return array("Package Name" => array("Type" => "text", "Size" => "25", "Loader" => function (array $params) use($resellerSimpleMode) {
        $return = array();
        if ($resellerSimpleMode) {
            $command = "CMD_API_PACKAGES_RESELLER";
        } else {
            $command = "CMD_API_PACKAGES_USER";
        }
        $result = directadmin_req($command, array(), $params);
        if ($result["error"] && $result["details"]) {
            throw new WHMCS\Exception\Module\NotServicable($result["details"]);
        }
        if (isset($result["list"])) {
            foreach ($result["list"] as $package) {
                $return[$package] = ucwords(str_replace("_", " ", $package));
            }
        }
        return $return;
    }, "SimpleMode" => true), "Reseller IP" => array("Type" => "dropdown", "Options" => ",shared,sharedreseller,assign", "SimpleMode" => $resellerSimpleMode), "Dedicated IP" => array("Type" => "yesno", "Description" => "Tick to Auto-Assign Dedicated IP"), "Suspend at Limit" => array("Type" => "yesno", "Description" => "Tick to Auto Suspend Users when reaching Bandwidth Limit"));
}
function directadmin_ClientArea($params)
{
    global $_LANG;
    $host = $params["serverhostname"] ? $params["serverhostname"] : $params["serverip"];
    $form = sprintf("<form action=\"%s://%s:%s/CMD_LOGIN\" method=\"post\" target=\"_blank\">" . "<input type=\"hidden\" name=\"username\" value=\"%s\" />" . "<input type=\"hidden\" name=\"password\" value=\"%s\" />" . "<input type=\"submit\" value=\"%s\" class=\"button\" />" . "</form>", $params["serverhttpprefix"], WHMCS\Input\Sanitize::encode($host), WHMCS\Input\Sanitize::encode($params["serverport"]), WHMCS\Input\Sanitize::encode($params["username"]), WHMCS\Input\Sanitize::encode($params["password"]), $_LANG["directadminlogin"]);
    return $form;
}
function directadmin_AdminLink($params)
{
    $host = $params["serverhostname"] ? $params["serverhostname"] : $params["serverip"];
    $form = sprintf("<form action=\"%s://%s:%s/CMD_LOGIN\" method=\"post\" target=\"_blank\">" . "<input type=\"hidden\" name=\"username\" value=\"%s\" />" . "<input type=\"hidden\" name=\"password\" value=\"%s\" />" . "<input type=\"submit\" value=\"%s\" />" . "</form>", $params["serverhttpprefix"], WHMCS\Input\Sanitize::encode($host), WHMCS\Input\Sanitize::encode($params["serverport"]), WHMCS\Input\Sanitize::encode($params["serverusername"]), WHMCS\Input\Sanitize::encode($params["serverpassword"]), "DirectAdmin");
    return $form;
}
function directadmin_CreateAccount($params)
{
    $fields = array();
    $ip = $params["serverip"];
    if ($params["configoption3"] || DACONFPACKAGEADDONLICENSE && $params["configoption1"] == "Custom" && $params["configoptions"]["Dedicated IP"]) {
        $command = "CMD_API_SHOW_RESELLER_IPS";
        $params["getip"] = true;
        $fields["action"] = "all";
        $results = directadmin_req($command, $fields, $params);
        foreach ($results as $ipaddress => $details) {
            if ($details["status"] == "free") {
                $ip = $ipaddress;
                break;
            }
        }
        $params["model"]->serviceProperties->save(array("dedicatedip" => $ip));
    }
    $params["getip"] = "";
    if (DACONFPACKAGEADDONLICENSE && $params["configoption1"] == "Custom") {
        $command = "CMD_API_ACCOUNT_USER";
        $fields["action"] = "create";
        $fields["add"] = "Submit";
        $fields["username"] = $params["username"];
        $fields["email"] = $params["clientsdetails"]["email"];
        $fields["passwd"] = $params["password"];
        $fields["passwd2"] = $params["password"];
        $fields["domain"] = $params["domain"];
        $fields["ip"] = $ip;
        $fields["notify"] = "no";
        if ($params["configoption4"]) {
            $fields["suspend_at_limit"] = "ON";
        }
        if ($params["configoptions"]["Disk Space"]) {
            $fields["quota"] = $params["configoptions"]["Disk Space"];
        }
        if ($params["configoptions"]["Bandwidth"]) {
            $fields["bandwidth"] = $params["configoptions"]["Bandwidth"];
        }
        if ($params["configoptions"]["FTP Accounts"]) {
            $fields["ftp"] = $params["configoptions"]["FTP Accounts"];
        } else {
            $fields["uftp"] = "ON";
        }
        if ($params["configoptions"]["Email Accounts"]) {
            $fields["nemails"] = $params["configoptions"]["Email Accounts"];
        } else {
            $fields["unemails"] = "ON";
        }
        if ($params["configoptions"]["MySQL Databases"]) {
            $fields["mysql"] = $params["configoptions"]["MySQL Databases"];
        } else {
            $fields["umysql"] = "ON";
        }
        if ($params["configoptions"]["Subdomains"]) {
            $fields["nsubdomains"] = $params["configoptions"]["Subdomains"];
        } else {
            $fields["unsubdomains"] = "ON";
        }
        if ($params["configoptions"]["Parked Domains"]) {
            $fields["domainptr"] = $params["configoptions"]["Parked Domains"];
        } else {
            $fields["udomainptr"] = "ON";
        }
        if ($params["configoptions"]["Addon Domains"]) {
            $fields["vdomains"] = $params["configoptions"]["Addon Domains"];
        } else {
            $fields["uvdomains"] = "ON";
        }
        if ($params["configoptions"]["CGI Access"]) {
            $fields["cgi"] = "ON";
        } else {
            $fields["cgi"] = "OFF";
        }
        if ($params["configoptions"]["Shell Access"]) {
            $fields["ssh"] = "ON";
        } else {
            $fields["ssh"] = "OFF";
        }
        if ($params["configoptions"]["Mailing Lists"]) {
            $fields["nemailml"] = $params["configoptions"]["Mailing Lists"];
        }
        if ($params["configoptions"]["PHP"]) {
            $fields["php"] = "ON";
        } else {
            $fields["php"] = "OFF";
        }
        if ($params["configoptions"]["SSL"]) {
            $fields["ssl"] = "ON";
        } else {
            $fields["ssl"] = "OFF";
        }
        if ($params["configoptions"]["System Info"]) {
            $fields["sysinfo"] = "ON";
        } else {
            $fields["sysinfo"] = "OFF";
        }
        if ($params["configoptions"]["DNS Control"]) {
            $fields["dnscontrol"] = "ON";
        } else {
            $fields["dnscontrol"] = "OFF";
        }
        if ($params["configoptions"]["Cron Jobs"]) {
            $fields["cron"] = "ON";
        } else {
            $fields["cron"] = "OFF";
        }
        if ($params["configoptions"]["Catch All"]) {
            $fields["catchall"] = "ON";
        } else {
            $fields["catchall"] = "OFF";
        }
        if ($params["configoptions"]["Spam Assassin"]) {
            $fields["spam"] = "ON";
        } else {
            $fields["spam"] = "OFF";
        }
        if ($params["configoptions"]["Anon FTP"]) {
            $fields["aftp"] = "ON";
        } else {
            $fields["aftp"] = "OFF";
        }
        if ($params["configoptions"]["Email Forwards"]) {
            if (is_numeric($params["configoptions"]["Email Forwards"])) {
                $fields["nemailf"] = $params["configoptions"]["Email Forwards"];
            } else {
                $fields["unemailf"] = "ON";
            }
        } else {
            $fields["unemailf"] = "OFF";
        }
        if ($params["configoptions"]["Mailing Lists"]) {
            if (is_numeric($params["configoptions"]["Mailing Lists"])) {
                $fields["nemailml"] = $params["configoptions"]["Mailing Lists"];
            } else {
                $fields["nemailml"] = "ON";
            }
        } else {
            $fields["nemailml"] = "OFF";
        }
        if ($params["configoptions"]["Auto Responders"]) {
            if (is_numeric($params["configoptions"]["Auto Responders"])) {
                $fields["nemailr"] = $params["configoptions"]["Auto Responders"];
            } else {
                $fields["unemailr"] = "ON";
            }
        } else {
            $fields["unemailr"] = "OFF";
        }
        $results = directadmin_req($command, $fields, $params);
        if ($results["error"]) {
            $result = $results["details"];
        } else {
            $result = "success";
        }
        return $result;
    }
    if ($params["type"] == "hostingaccount") {
        $fields["action"] = "create";
        $fields["add"] = "Submit";
        $fields["username"] = $params["username"];
        $fields["email"] = $params["clientsdetails"]["email"];
        $fields["passwd"] = $params["password"];
        $fields["passwd2"] = $params["password"];
        $fields["domain"] = $params["domain"];
        $fields["package"] = $params["configoption1"];
        $fields["ip"] = $ip;
        $fields["notify"] = "no";
        $command = "CMD_API_ACCOUNT_USER";
    } else {
        $fields["action"] = "create";
        $fields["add"] = "Submit";
        $fields["username"] = $params["username"];
        $fields["email"] = $params["clientsdetails"]["email"];
        $fields["passwd"] = $params["password"];
        $fields["passwd2"] = $params["password"];
        $fields["domain"] = $params["domain"];
        $fields["package"] = $params["configoption1"];
        if ($params["configoption2"] == "sharedreseller") {
            $fields["ip"] = "sharedreseller";
        } else {
            if ($params["configoption2"] == "assign") {
                $fields["ip"] = "assign";
            } else {
                $fields["ip"] = "shared";
            }
        }
        $fields["notify"] = "no";
        $command = "CMD_ACCOUNT_RESELLER";
    }
    $results = directadmin_req($command, $fields, $params);
    if ($results["error"]) {
        $result = $results["details"];
    } else {
        $result = "success";
    }
    return $result;
}
function directadmin_TerminateAccount($params)
{
    $fields = array();
    $fields["confirmed"] = "Confirm";
    $fields["delete"] = "yes";
    $fields["select0"] = $params["username"];
    $results = directadmin_req("CMD_SELECT_USERS", $fields, $params);
    if ($results["error"]) {
        $result = $results["details"];
    } else {
        $result = "success";
    }
    return $result;
}
function directadmin_SuspendAccount($params)
{
    $fields = array();
    $fields["action"] = "create";
    $fields["add"] = "Submit";
    $fields["user"] = $params["username"];
    $results = directadmin_req("CMD_API_SHOW_USER_CONFIG", $fields, $params);
    if ($results["suspended"] == "yes") {
        $result = "Account is already suspended";
    } else {
        $fields = array();
        $fields["suspend"] = "Suspend/Unsuspend";
        $fields["select0"] = $params["username"];
        $results = directadmin_req("CMD_SELECT_USERS", $fields, $params);
        if ($results["error"]) {
            $result = $results["details"];
        } else {
            $result = "success";
        }
    }
    return $result;
}
function directadmin_UnsuspendAccount($params)
{
    $fields = array();
    $fields["action"] = "create";
    $fields["add"] = "Submit";
    $fields["user"] = $params["username"];
    $results = directadmin_req("CMD_API_SHOW_USER_CONFIG", $fields, $params);
    if ($results["suspended"] == "no") {
        $result = "Account is not suspended";
    } else {
        $fields = array();
        $fields["suspend"] = "Suspend/Unsuspend";
        $fields["select0"] = $params["username"];
        $results = directadmin_req("CMD_SELECT_USERS", $fields, $params);
        if ($results["error"]) {
            $result = $results["details"];
        } else {
            $result = "success";
        }
    }
    return $result;
}
function directadmin_ChangePassword($params)
{
    $fields = array();
    $fields["username"] = $params["username"];
    $fields["passwd"] = $params["password"];
    $fields["passwd2"] = $params["password"];
    $results = directadmin_req("CMD_API_USER_PASSWD", $fields, $params, true);
    if ($results["error"]) {
        $result = $results["details"];
    } else {
        $result = "success";
    }
    return $result;
}
function directadmin_ChangePackage($params)
{
    $fields = array();
    $fields["action"] = "package";
    $fields["user"] = $params["username"];
    $fields["package"] = $params["configoption1"];
    if ($params["type"] == "reselleraccount") {
        $results = directadmin_req("CMD_API_MODIFY_RESELLER", $fields, $params);
    } else {
        $results = directadmin_req("CMD_API_MODIFY_USER", $fields, $params);
    }
    if ($results["error"]) {
        $result = $results["details"];
    } else {
        $result = "success";
    }
    return $result;
}
function directadmin_UsageUpdate($params)
{
    $serverUsername = $params["serverusername"];
    $services = WHMCS\Service\Service::with("product")->where("server", "=", $params["serverid"])->whereIn("domainstatus", array("Active", "Suspended"))->get();
    $addons = WHMCS\Service\Addon::with("productAddon")->where("server", "=", $params["serverid"])->whereIn("status", array("Active", "Suspended"))->get();
    foreach ($services as $service) {
        $username = $service->username;
        $fields = array("user" => $username);
        $command1 = "CMD_API_SHOW_USER_USAGE";
        $command2 = "CMD_API_SHOW_USER_CONFIG";
        $fields1 = $fields;
        $fields2 = $fields;
        $params["serverusername"] = $serverUsername;
        if ($service->product->type == "reselleraccount") {
            $params["serverusername"] = $serverUsername . "|" . $username;
            $command1 = "CMD_API_RESELLER_STATS";
            $fields1["type"] = "usage";
            $command2 = "CMD_API_RESELLER_STATS";
        }
        $results = directadmin_req($command1, $fields1, $params);
        if ($results["error"]) {
            continue;
        }
        $quota = urldecode($results["quota"]);
        $bandwidth = urldecode($results["bandwidth"]);
        $diskUsed = round($quota);
        $bwUsed = round($bandwidth);
        $results = directadmin_req($command2, $fields2, $params);
        if ($results["error"]) {
            continue;
        }
        $quota = urldecode($results["quota"]);
        $bandwidth = urldecode($results["bandwidth"]);
        $diskLimit = $quota == "unlimited" ? "0" : round($quota);
        $bwLimit = $bandwidth == "unlimited" ? "0" : round($bandwidth);
        $service->diskUsage = $diskUsed;
        $service->diskLimit = $diskLimit;
        $service->bandwidthUsage = $bwUsed;
        $service->bandwidthLimit = $bwLimit;
        $service->lastUpdateDate = WHMCS\Carbon::now()->toDateTimeString();
        $service->save();
    }
    foreach ($addons as $addon) {
        $username = $addon->serviceProperties->get("username");
        $fields = array("user" => $username);
        $command1 = "CMD_API_SHOW_USER_USAGE";
        $command2 = "CMD_API_SHOW_USER_CONFIG";
        $fields1 = $fields;
        $fields2 = $fields;
        $params["serverusername"] = $serverUsername;
        if ($addon->productAddon->type == "reselleraccount") {
            $params["serverusername"] = $serverUsername . "|" . $username;
            $command1 = "CMD_API_RESELLER_STATS";
            $fields1["type"] = "usage";
            $command2 = "CMD_API_RESELLER_STATS";
        }
        $results = directadmin_req($command1, $fields1, $params);
        if ($results["error"]) {
            continue;
        }
        $quota = urldecode($results["quota"]);
        $bandwidth = urldecode($results["bandwidth"]);
        $diskUsed = round($quota);
        $bwUsed = round($bandwidth);
        $results = directadmin_req($command2, $fields2, $params);
        if ($results["error"]) {
            continue;
        }
        $quota = urldecode($results["quota"]);
        $bandwidth = urldecode($results["bandwidth"]);
        $diskLimit = $quota == "unlimited" ? "0" : round($quota);
        $bwLimit = $bandwidth == "unlimited" ? "0" : round($bandwidth);
        $addon->serviceProperties->save(array("diskusage" => $diskUsed, "disklimit" => $diskLimit, "bwusage" => $bwUsed, "bwlimit" => $bwLimit, "lastupdate" => WHMCS\Carbon::now()->toDateTimeString()));
    }
}
function directadmin_req($command, $fields, $params, $post = "")
{
    $host = $params["serverhostname"] ? $params["serverhostname"] : $params["serverip"];
    $user = $params["serverusername"];
    $pass = $params["serverpassword"];
    $httpprefix = $params["serverhttpprefix"];
    $port = $params["serverport"];
    $resultsarray = array();
    $fieldstring = "";
    foreach ($fields as $key => $value) {
        $fieldstring .= (string) $key . "=" . urlencode($value) . "&";
    }
    $url = $httpprefix . "://" . $host . ":" . $port . "/" . $command;
    if (!$post) {
        $url .= "?" . $fieldstring;
    }
    $authstr = $user . ":" . $pass;
    $directadminaccterr = "";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_URL, $url);
    if ($post) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fieldstring);
    }
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $curlheaders[0] = "Authorization: Basic " . base64_encode($authstr);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $curlheaders);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $data = curl_exec($ch);
    if (curl_errno($ch)) {
        $resultsarray["error"] = true;
        $resultsarray["details"] = curl_errno($ch) . " - " . curl_error($ch);
        $data = curl_errno($ch) . " - " . curl_error($ch);
    }
    curl_close($ch);
    $arrayReturnPackages = array("CMD_API_PACKAGES_RESELLER", "CMD_API_PACKAGES_USER", "CMD_API_ADDITIONAL_DOMAINS", "CMD_API_SHOW_ALL_USERS", "CMD_API_SHOW_USERS", "CMD_API_SHOW_RESELLERS");
    if (!$resultsarray["error"]) {
        if (strpos($data, "DirectAdmin Login") == true) {
            $resultsarray = array("error" => "1", "details" => "Login Failed");
        } else {
            if (strpos($data, "Your IP is blacklisted") !== false) {
                $resultsarray = array("error" => "1", "details" => "WHMCS Host Server IP is Blacklisted");
            } else {
                if ($params["getip"]) {
                    $data2 = directadmin_unhtmlentities($data);
                    parse_str($data2, $output);
                    foreach ($output as $key => $value) {
                        $key = str_replace("_", ".", urldecode($key));
                        $value = explode("&", urldecode($value));
                        foreach ($value as $temp) {
                            $temp = explode("=", $temp);
                            $resultsarray[urldecode($key)][$temp[0]] = $temp[1];
                        }
                    }
                } else {
                    if (in_array($command, $arrayReturnPackages)) {
                        $data2 = directadmin_unhtmlentities($data);
                        parse_str($data2, $resultsarray);
                    } else {
                        $data = explode("&", $data);
                        foreach ($data as $temp) {
                            $temp = explode("=", $temp);
                            $temp[0] = urldecode($temp[0]);
                            $temp[1] = urldecode($temp[1]);
                            $resultsarray[$temp[0]] = $temp[1];
                        }
                    }
                }
            }
        }
    }
    logModuleCall("directadmin", $command, $url, $data, $resultsarray);
    return $resultsarray;
}
function directadmin_unhtmlentities($string)
{
    return preg_replace_callback("~&#([0-9][0-9])~", function ($match) {
        return chr($match[1]);
    }, $string);
}
function directadmin_TestConnection(array $params)
{
    $response = directadmin_req("CMD_API_SHOW_USERS", array(), $params);
    if (array_key_exists("error", $response) && $response["error"] == "1") {
        return array("error" => $response["details"]);
    }
    return array("success" => true);
}
function directadmin_GenerateCertificateSigningRequest(array $params)
{
    $serverUsername = $params["serverusername"];
    $params["serverusername"] = $serverUsername . "|" . $params["username"];
    $command = "CMD_API_SSL";
    $certificate = $params["certificateInfo"];
    $fields = array("action" => "save", "type" => "create", "request" => "yes", "domain" => $certificate["domain"], "name" => $certificate["domain"], "country" => $certificate["country"], "province" => $certificate["state"], "city" => $certificate["city"], "company" => $certificate["orgname"] ?: "NA", "division" => $certificate["orgunit"], "email" => $certificate["email"], "encryption" => "sha256", "keysize" => "2048", "submit" => "Save");
    $response = directadmin_req($command, $fields, $params, true);
    if (array_key_exists("error", $response) && $response["error"] == "1") {
        throw new Exception("DirectAdmin: CSR Generation Failed: " . $response["details"]);
    }
    return html_entity_decode(urldecode($response["request"]));
}
function directadmin_InstallSsl(array $params)
{
    $serverUsername = $params["serverusername"];
    $params["serverusername"] = $serverUsername . "|" . $params["username"];
    $command = "CMD_API_SSL";
    $fields = array("domain" => $params["domain"]);
    $response = directadmin_req($command, $fields, $params);
    if (array_key_exists("error", $response) && $response["error"] == "1") {
        throw new Exception("DirectAdmin: Private Key Retrieval Failed: " . $response["details"]);
    }
    $key = html_entity_decode(urldecode($response["key"]));
    $fields = array("domain" => $params["certificateDomain"], "action" => "save", "type" => "paste", "certificate" => (string) $params["certificate"] . "\n" . $key . "\n");
    $response = directadmin_req($command, $fields, $params, true);
    if (array_key_exists("error", $response) && $response["error"] == "1") {
        throw new Exception("DirectAdmin: Certificate Installation Failed: " . $response["details"]);
    }
    $fields = array("domain" => $params["certificateDomain"], "action" => "view");
    $response = directadmin_req("CMD_API_ADDITIONAL_DOMAINS", $fields, $params, true);
    if (array_key_exists("error", $response) && $response["error"] == "1") {
        throw new Exception("DirectAdmin: Account Information Retrieval Failed: " . $response["details"]);
    }
    $accountState = $response;
    $fields = array("action" => "modify", "domain" => $params["certificateDomain"], "ssl" => "ON", "php" => $accountState["php"], "cgi" => $accountState["cgi"]);
    if ($accountState["quota"] === "unlimited") {
        $fields["uquota"] = "ON";
    } else {
        $fields["quota"] = $accountState["quota"];
    }
    if ($accountState["bandwidth"] === "unlimited") {
        $fields["ubandwidth"] = "ON";
    } else {
        $fields["bandwidth"] = $accountState["bandwidth"];
    }
    directadmin_req("CMD_API_DOMAIN", $fields, $params, true);
    $fields = array("action" => "private_html", "domain" => $params["certificateDomain"], "val" => "symlink");
    directadmin_req("CMD_API_DOMAIN", $fields, $params, true);
}
function directadmin_GetMxRecords(array $params)
{
    $domain = $params["domain"];
    $serverUsername = $params["serverusername"];
    $params["serverusername"] = $serverUsername . "|" . $params["username"];
    $command = "CMD_API_DNS_CONTROL";
    $fields = array("domain" => $domain);
    $response = directadmin_req($command, $fields, $params);
    if (array_key_exists("error", $response) && $response["error"] == "1") {
        throw new Exception("MX Retrieval Failed: " . $response["details"]);
    }
    list($dnsRecords) = array_keys($response);
    $mxRecords = NULL;
    preg_match_all("/^.*(MX).*\$/m", trim($dnsRecords), $mxRecords);
    $mxRecords = $mxRecords[0];
    $returnedRecords = array();
    foreach ($mxRecords as $key => $mxRecord) {
        $mxRecord = preg_split("/\\s+/", $mxRecord);
        $returnedRecords[] = array("id" => $key, "mx" => $mxRecord[5] . "." . $mxRecord[0], "priority" => $mxRecord[4]);
    }
    return array("mxRecords" => $returnedRecords);
}
function directadmin_DeleteMxRecords(array $params)
{
    $domain = $params["mxDomain"];
    $serverUsername = $params["serverusername"];
    $params["serverusername"] = $serverUsername . "|" . $params["username"];
    $command = "CMD_API_DNS_CONTROL";
    $fields = array();
    $fields["domain"] = $domain;
    $fields["action"] = "select";
    $fields["delete"] = "Delete Selected";
    foreach ($params["mxRecords"] as $mxRecord) {
        $priority = $mxRecord["priority"];
        $mxRecord = str_replace("." . $domain . ".", "", $mxRecord["mx"]);
        $fields["mxrecs0"] = "name=" . $domain . ".&value=" . $priority . "+" . $mxRecord;
        $response = directadmin_req($command, $fields, $params);
        if (array_key_exists("error", $response) && $response["error"] == "1") {
            throw new Exception("MX Deletion Failed: " . $response["details"]);
        }
    }
    $response = directadmin_req($command, $fields, $params);
    if (array_key_exists("error", $response) && $response["error"] == "1") {
        throw new Exception("MX Deletion Failed: " . $response["details"]);
    }
}
function directadmin_AddMxRecords(array $params)
{
    $domain = $params["mxDomain"];
    $serverUsername = $params["serverusername"];
    $params["serverusername"] = $serverUsername . "|" . $params["username"];
    $command = "CMD_API_DNS_CONTROL";
    $fields = array();
    $fields["action"] = "add";
    $fields["name"] = $domain . ".";
    $fields["domain"] = $domain;
    foreach ($params["mxRecords"] as $mxRecord => $priority) {
        $fields["type"] = "MX";
        $fields["value"] = $priority;
        $fields["mx_value"] = str_replace("." . $domain . ".", "", $mxRecord);
        $response = directadmin_req($command, $fields, $params);
        if (array_key_exists("error", $response) && $response["error"] == "1") {
            throw new Exception("MX Creation Failed: " . $response["details"]);
        }
    }
    if (array_key_exists("internal", $params)) {
        $fields = array();
        $fields["action"] = "internal";
        $fields["domain"] = $domain;
        $fields["internal"] = $params["internal"];
        directadmin_req("CMD_API_DNS_MX", $fields, $params);
    }
}
function directadmin_CreateFileWithinDocRoot(array $params)
{
    $basePath = "/public_html";
    $dir = array_key_exists("dir", $params) ? $params["dir"] : "";
    $serverUsername = $params["serverusername"];
    $params["serverusername"] = $serverUsername . "|" . $params["username"];
    if ($dir) {
        $dirParts = explode("/", $dir);
        foreach ($dirParts as $dirPart) {
            $command = "CMD_API_FILE_MANAGER";
            $fields = array("action" => "folder", "path" => $basePath, "name" => $dirPart);
            directadmin_req($command, $fields, $params, true);
            $basePath .= "/" . $dirPart;
        }
    }
    $command = "CMD_API_FILE_MANAGER";
    $fields = array("action" => "file", "path" => $basePath, "name" => $params["filename"], "file" => "Create");
    directadmin_req($command, $fields, $params, true);
    $command = "CMD_API_FILE_MANAGER";
    $fields = array("action" => "edit", "path" => $basePath, "filename" => $params["filename"], "text" => $params["fileContent"]);
    $response = directadmin_req($command, $fields, $params, true);
    if (array_key_exists("error", $response) && $response["error"] == "1") {
        throw new Exception("DirectAdmin: Unable to create DV Auth File: " . $response["details"]);
    }
}
function directadmin_CreateFTPAccount(array $params)
{
    $serverUsername = $params["serverusername"];
    $params["serverusername"] = $serverUsername . "|" . $params["username"];
    $command = "CMD_API_FTP";
    $fields = array("action" => "create", "domain" => $params["domain"], "user" => $params["ftpUsername"], "passwd" => $params["ftpPassword"], "passwd2" => $params["ftpPassword"], "type" => "domain");
    $response = directadmin_req($command, $fields, $params, true);
    if (array_key_exists("error", $response) && $response["error"] == "1") {
        throw new Exception("Unable to Create FTP Account: " . $response["details"]);
    }
}
function directadmin_ListAccounts(array $params)
{
    $commands = array("CMD_API_SHOW_USERS", "CMD_API_SHOW_RESELLERS");
    $accounts = array();
    foreach ($commands as $command) {
        $response = directadmin_req($command, array(), $params, true);
        if (array_key_exists("error", $response) && $response["error"] == "1") {
            if ($response["text"] == "You cannot execute that command") {
                continue;
            }
            return array("error" => "Unable to Obtain Account List - " . $response["details"]);
        }
        if (array_key_exists("list", $response)) {
            foreach ($response["list"] as $user) {
                $command2 = "CMD_API_SHOW_USER_CONFIG";
                $response2 = directadmin_req($command2, array("user" => $user), $params);
                if (array_key_exists("error", $response2) && $response2["error"] == "1") {
                    return array("error" => "Unable to Obtain Account List - " . $response2["details"]);
                }
                $status = WHMCS\Service\Status::ACTIVE;
                if ($response2["suspended"] != "no") {
                    $status = WHMCS\Service\Status::SUSPENDED;
                }
                $account = array("name" => $response2["username"], "email" => $response2["email"], "username" => $response2["username"], "domain" => $response2["domain"], "uniqueIdentifier" => $response2["domain"], "product" => $response2["package"], "primaryip" => $response2["ip"], "created" => WHMCS\Carbon::parse($response2["date_created"])->toDateTimeString(), "status" => $status);
                $accounts[] = $account;
            }
        }
    }
    return array("success" => true, "accounts" => $accounts);
}
function directadmin_GetUserCount(array $params)
{
    $commands = array("CMD_API_SHOW_USERS", "CMD_API_SHOW_RESELLERS");
    $totalCount = 0;
    $ownedAccounts = 0;
    foreach ($commands as $command) {
        $response = directadmin_req($command, array(), $params, true);
        if (array_key_exists("error", $response) && $response["error"] == "1") {
            if ($response["text"] == "You cannot execute that command") {
                continue;
            }
            return array("error" => "Unable to Obtain Account List - " . $response["details"]);
        }
        if (array_key_exists("list", $response)) {
            $totalCount += count($response["list"]);
            $ownedAccounts += count($response["list"]);
            if ($command == "CMD_API_SHOW_RESELLERS") {
                foreach ($response["list"] as $reseller) {
                    $response2 = directadmin_req("CMD_API_SHOW_USERS", array("reseller" => $reseller), $params, true);
                    if (array_key_exists("list", $response2)) {
                        $totalCount += count($response2["list"]);
                    }
                }
            }
        }
    }
    return array("success" => true, "totalAccounts" => $totalCount, "ownedAccounts" => $ownedAccounts);
}
function directadmin_GetRemoteMetaData(array $params)
{
    try {
        $version = "-";
        $loads = array();
        $maxUsers = 0;
        $response = directadmin_req("CMD_API_SYSTEM_INFO", array(), $params, true);
        if (array_key_exists("directadmin", $response)) {
            $version = explode("|", $response["directadmin"]);
            $version = $version[0];
        }
        return array("version" => $version, "load" => $loads, "max_accounts" => $maxUsers);
    } catch (Exception $e) {
        return array("success" => false, "error" => $e->getMessage());
    }
}
function directadmin_RenderRemoteMetaData(array $params)
{
    $remoteData = $params["remoteData"];
    if ($remoteData) {
        $metaData = $remoteData->metaData;
        $version = "Unknown";
        if (array_key_exists("version", $metaData)) {
            $version = $metaData["version"];
        }
        return "DirectAdmin Version: " . $version;
    }
    return "";
}

?>