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
if (defined("CPANELCONFPACKAGEADDONLICENSE")) {
    exit("License Hacking Attempt Detected");
}
define("CPANELCONFPACKAGEADDONLICENSE", $licensing->isActiveAddon("Configurable Package Addon"));
include_once __DIR__ . DIRECTORY_SEPARATOR . "lib" . DIRECTORY_SEPARATOR . "Cpanel" . DIRECTORY_SEPARATOR . "ApplicationLink" . DIRECTORY_SEPARATOR . "Server.php";
function cpanel_MetaData()
{
    return array("DisplayName" => "cPanel", "APIVersion" => "1.1", "DefaultNonSSLPort" => "2086", "DefaultSSLPort" => "2087", "ServiceSingleSignOnLabel" => "Login to cPanel", "AdminSingleSignOnLabel" => "Login to WHM", "ApplicationLinkDescription" => "Provides customers with links that utilise" . " Single Sign-On technology to automatically transfer" . " and log your customers into the WHMCS billing &amp; support portal" . " from within the cPanel user interface.", "ListAccountsUniqueIdentifierDisplayName" => "Domain", "ListAccountsUniqueIdentifierField" => "domain", "ListAccountsProductField" => "configoption1");
}
function cpanel_ConfigOptions(array $params)
{
    $resellerSimpleMode = $params["producttype"] == "reselleraccount";
    return array("WHM Package Name" => array("Type" => "text", "Size" => "25", "Loader" => "cpanel_ListPackages", "SimpleMode" => true), "Max FTP Accounts" => array("Type" => "text", "Size" => "5"), "Web Space Quota" => array("Type" => "text", "Size" => "5", "Description" => "MB"), "Max Email Accounts" => array("Type" => "text", "Size" => "5"), "Bandwidth Limit" => array("Type" => "text", "Size" => "5", "Description" => "MB"), "Dedicated IP" => array("Type" => "yesno"), "Shell Access" => array("Type" => "yesno", "Description" => "Tick to grant access"), "Max SQL Databases" => array("Type" => "text", "Size" => "5"), "CGI Access" => array("Type" => "yesno", "Description" => "Tick to grant access"), "Max Subdomains" => array("Type" => "text", "Size" => "5"), "Frontpage Extensions" => array("Type" => "yesno", "Description" => "Tick to grant access"), "Max Parked Domains" => array("Type" => "text", "Size" => "5"), "cPanel Theme" => array("Type" => "text", "Size" => "15"), "Max Addon Domains" => array("Type" => "text", "Size" => "5"), "Limit Reseller by Number" => array("Type" => "text", "Size" => "5", "Description" => "Enter max number of allowed accounts"), "Limit Reseller by Usage" => array("Type" => "yesno", "Description" => "Tick to limit by resource usage"), "Reseller Disk Space" => array("Type" => "text", "Size" => "7", "Description" => "MB", "SimpleMode" => $resellerSimpleMode), "Reseller Bandwidth" => array("Type" => "text", "Size" => "7", "Description" => "MB", "SimpleMode" => $resellerSimpleMode), "Allow DS Overselling" => array("Type" => "yesno", "Description" => "MB"), "Allow BW Overselling" => array("Type" => "yesno", "Description" => "MB"), "Reseller ACL List" => array("Type" => "text", "Size" => "20", "SimpleMode" => $resellerSimpleMode), "Add Prefix to Package" => array("Type" => "yesno", "Description" => "Add username_ to package name"), "Configure Nameservers" => array("Type" => "yesno", "Description" => "Setup Custom ns1/ns2 Nameservers"), "Reseller Ownership" => array("Type" => "yesno", "Description" => "Set the reseller to own their own account"));
}
function cpanel_costrrpl($val)
{
    $val = str_replace("MB", "", $val);
    $val = str_replace("Accounts", "", $val);
    $val = trim($val);
    if ($val == "Yes") {
        $val = true;
    } else {
        if ($val == "No") {
            $val = false;
        } else {
            if ($val == "Unlimited") {
                $val = "unlimited";
            }
        }
    }
    return $val;
}
function cpanel_CreateAccount($params)
{
    $mailinglists = $languageco = "";
    if (CPANELCONFPACKAGEADDONLICENSE) {
        if (isset($params["configoptions"]["Disk Space"])) {
            $params["configoption17"] = cpanel_costrrpl($params["configoptions"]["Disk Space"]);
            $params["configoption3"] = $params["configoption17"];
        }
        if (isset($params["configoptions"]["Bandwidth"])) {
            $params["configoption18"] = cpanel_costrrpl($params["configoptions"]["Bandwidth"]);
            $params["configoption5"] = $params["configoption18"];
        }
        if (isset($params["configoptions"]["FTP Accounts"])) {
            $params["configoption2"] = cpanel_costrrpl($params["configoptions"]["FTP Accounts"]);
        }
        if (isset($params["configoptions"]["Email Accounts"])) {
            $params["configoption4"] = cpanel_costrrpl($params["configoptions"]["Email Accounts"]);
        }
        if (isset($params["configoptions"]["MySQL Databases"])) {
            $params["configoption8"] = cpanel_costrrpl($params["configoptions"]["MySQL Databases"]);
        }
        if (isset($params["configoptions"]["Subdomains"])) {
            $params["configoption10"] = cpanel_costrrpl($params["configoptions"]["Subdomains"]);
        }
        if (isset($params["configoptions"]["Parked Domains"])) {
            $params["configoption12"] = cpanel_costrrpl($params["configoptions"]["Parked Domains"]);
        }
        if (isset($params["configoptions"]["Addon Domains"])) {
            $params["configoption14"] = cpanel_costrrpl($params["configoptions"]["Addon Domains"]);
        }
        if (isset($params["configoptions"]["Dedicated IP"])) {
            $params["configoption6"] = cpanel_costrrpl($params["configoptions"]["Dedicated IP"]);
        }
        if (isset($params["configoptions"]["CGI Access"])) {
            $params["configoption9"] = cpanel_costrrpl($params["configoptions"]["CGI Access"]);
        }
        if (isset($params["configoptions"]["Shell Access"])) {
            $params["configoption7"] = cpanel_costrrpl($params["configoptions"]["Shell Access"]);
        }
        if (isset($params["configoptions"]["FrontPage Extensions"])) {
            $params["configoption11"] = cpanel_costrrpl($params["configoptions"]["FrontPage Extensions"]);
        }
        if (isset($params["configoptions"]["Mailing Lists"])) {
            $mailinglists = cpanel_costrrpl($params["configoptions"]["Mailing Lists"]);
        }
        if (isset($params["configoptions"]["Package Name"])) {
            $params["configoption1"] = $params["configoptions"]["Package Name"];
        }
        if (isset($params["configoptions"]["Language"])) {
            $languageco = $params["configoptions"]["Language"];
        }
    }
    $dedicatedip = $params["configoption6"] ? true : false;
    $cgiaccess = $params["configoption9"] ? true : false;
    $shellaccess = $params["configoption7"] ? true : false;
    $fpextensions = $params["configoption11"] ? true : false;
    try {
        $packages = cpanel_ListPackages($params, false);
    } catch (Exception $e) {
        return $e->getMessage();
    }
    $postfields = array();
    $postfields["username"] = $params["username"];
    $postfields["password"] = $params["password"];
    $postfields["domain"] = $params["domain"];
    $postfields["savepkg"] = 0;
    $packageRequired = true;
    if (isset($params["configoption3"]) && $params["configoption3"] != "") {
        $postfields["quota"] = $params["configoption3"];
        $packageRequired = false;
    }
    if (isset($params["configoption5"]) && $params["configoption5"] != "") {
        $postfields["bwlimit"] = $params["configoption5"];
        $packageRequired = false;
    }
    if ($params["configoption1"] == "") {
        $packageRequired = false;
    }
    if ($dedicatedip) {
        $postfields["ip"] = $dedicatedip;
    }
    if ($cgiaccess) {
        $postfields["cgi"] = $cgiaccess;
    }
    if ($fpextensions) {
        $postfields["frontpage"] = $fpextensions;
    }
    if ($shellaccess) {
        $postfields["hasshell"] = $shellaccess;
    }
    $postfields["contactemail"] = $params["clientsdetails"]["email"];
    if (isset($params["configoption13"]) && $params["configoption13"] != "") {
        $postfields["cpmod"] = $params["configoption13"];
    }
    if (isset($params["configoption2"]) && $params["configoption12"] != "") {
        $postfields["maxftp"] = $params["configoption2"];
    }
    if (isset($params["configoption8"]) && $params["configoption8"] != "") {
        $postfields["maxsql"] = $params["configoption8"];
    }
    if (isset($params["configoption4"]) && $params["configoption4"] != "") {
        $postfields["maxpop"] = $params["configoption4"];
    }
    if (isset($mailinglists) && $mailinglists != "") {
        $postfields["maxlst"] = $mailinglists;
    }
    if (isset($params["configoption10"]) && $params["configoption10"] != "") {
        $postfields["maxsub"] = $params["configoption10"];
    }
    if (isset($params["configoption12"]) && $params["configoption12"] != "") {
        $postfields["maxpark"] = $params["configoption12"];
    }
    if (isset($params["configoption14"]) && $params["configoption14"] != "") {
        $postfields["maxaddon"] = $params["configoption14"];
    }
    if (isset($languageco) && $languageco != "") {
        $postfields["language"] = $languageco;
    }
    try {
        $postfields["plan"] = cpanel_ConfirmPackageName($params["configoption1"], $params["serverusername"], $packages);
    } catch (WHMCS\Exception\Module\NotServicable $e) {
        if ($packageRequired) {
            return $e->getMessage();
        }
        $postfields["plan"] = ($params["configoption22"] ? $params["username"] . "_" : "") . $params["configoption1"];
    } catch (Exception $e) {
        return $e->getMessage();
    }
    $postfields["api.version"] = 1;
    $postfields["reseller"] = 0;
    $output = cpanel_jsonRequest($params, "/json-api/createacct", $postfields);
    if (!is_array($output)) {
        return $output;
    }
    if (array_key_exists("metadata", $output) && $output["metadata"]["result"] == "0") {
        $error = $output["metadata"]["reason"];
        if (!$error) {
            $error = "An unknown error occurred";
        }
        return $error;
    }
    if ($dedicatedip) {
        $newaccountip = $output["data"]["ip"];
        $params["model"]->serviceProperties->save(array("dedicatedip" => $newaccountip));
    }
    if ($params["type"] == "reselleraccount") {
        $makeowner = $params["configoption24"] ? 1 : 0;
        $output = cpanel_jsonRequest($params, "/json-api/setupreseller", array("user" => $params["username"], "makeowner" => $makeowner));
        if (!is_array($output)) {
            return $output;
        }
        if (!$output["result"][0]["status"]) {
            $error = $output["result"][0]["statusmsg"];
            if (!$error) {
                $error = "An unknown error occurred";
            }
            return $error;
        }
        $postVars = "user=" . $params["username"];
        if ($params["configoption16"]) {
            $postVars .= "&enable_resource_limits=1&diskspace_limit=" . urlencode($params["configoption17"]) . "&bandwidth_limit=" . urlencode($params["configoption18"]);
            if ($params["configoption19"]) {
                $postVars .= "&enable_overselling_diskspace=1";
            }
            if ($params["configoption20"]) {
                $postVars .= "&enable_overselling_bandwidth=1";
            }
        }
        if ($params["configoption15"]) {
            $postVars .= "&enable_account_limit=1&account_limit=" . urlencode($params["configoption15"]);
        }
        $output = cpanel_jsonRequest($params, "/json-api/setresellerlimits", $postVars);
        if (!is_array($output)) {
            return $output;
        }
        if (!$output["result"][0]["status"]) {
            $error = $output["result"][0]["statusmsg"];
            if (!$error) {
                $error = "An unknown error occurred";
            }
            return $error;
        }
        $postVars = "reseller=" . $params["username"] . "&acllist=" . urlencode($params["configoption21"]);
        $output = cpanel_jsonRequest($params, "/json-api/setacls", $postVars);
        if (!is_array($output)) {
            return $output;
        }
        if (!$output["result"][0]["status"]) {
            $error = $output["result"][0]["statusmsg"];
            if (!$error) {
                $error = "An unknown error occurred";
            }
            return $error;
        }
        if ($params["configoption23"]) {
            $postVars = "user=" . $params["username"] . "&nameservers=ns1." . $params["domain"] . ",ns2." . $params["domain"];
            $output = cpanel_jsonRequest($params, "/json-api/setresellernameservers", $postVars);
            if (!is_array($output)) {
                return $output;
            }
            if (!$output["result"][0]["status"]) {
                $error = $output["result"][0]["statusmsg"];
                if (!$error) {
                    $error = "An unknown error occurred";
                }
                return $error;
            }
        }
    }
    return "success";
}
function cpanel_SuspendAccount($params)
{
    if (!$params["username"]) {
        return "Cannot perform action without accounts username";
    }
    if ($params["type"] == "reselleraccount") {
        $postVars = "api.version=1&user=" . urlencode($params["username"]) . "&reason=" . urlencode($params["suspendreason"]);
        $output = cpanel_jsonRequest($params, "/json-api/suspendreseller", $postVars);
    } else {
        $postVars = "api.version=1&user=" . urlencode($params["username"]) . "&reason=" . urlencode($params["suspendreason"]);
        $output = cpanel_jsonRequest($params, "/json-api/suspendacct", $postVars);
    }
    if (!is_array($output)) {
        return $output;
    }
    $metadata = isset($output["metadata"]) ? $output["metadata"] : array();
    $resultCode = isset($metadata["result"]) ? $metadata["result"] : 0;
    if ($resultCode == "1") {
        return "success";
    }
    return isset($metadata["reason"]) ? $metadata["reason"] : "An unknown error occurred";
}
function cpanel_UnsuspendAccount($params)
{
    if (!$params["username"]) {
        return "Cannot perform action without accounts username";
    }
    if ($params["type"] == "reselleraccount") {
        $postVars = "api.version=1&user=" . urlencode($params["username"]);
        $output = cpanel_jsonRequest($params, "/json-api/unsuspendreseller", $postVars);
    } else {
        $postVars = "api.version=1&user=" . urlencode($params["username"]);
        $output = cpanel_jsonRequest($params, "/json-api/unsuspendacct", $postVars);
    }
    if (!is_array($output)) {
        return $output;
    }
    $metadata = isset($output["metadata"]) ? $output["metadata"] : array();
    $resultCode = isset($metadata["result"]) ? $metadata["result"] : 0;
    if ($resultCode == "1") {
        return "success";
    }
    return isset($metadata["reason"]) ? $metadata["reason"] : "An unknown error occurred";
}
function cpanel_TerminateAccount($params)
{
    if (!$params["username"]) {
        return "Cannot perform action without accounts username";
    }
    if ($params["type"] == "reselleraccount") {
        $postVars = "reseller=" . $params["username"] . "&terminatereseller=1&verify=I%20understand%20this%20will%20irrevocably%20remove%20all%20the%20accounts%20owned%20by%20the%20reseller%20" . $params["username"];
        $output = cpanel_jsonRequest($params, "/json-api/terminatereseller", $postVars);
        if (!is_array($output)) {
            return $output;
        }
        if (!$output["result"][0]["status"]) {
            $error = $output["result"][0]["statusmsg"];
            if (!$error) {
                $error = "An unknown error occurred";
            }
            return $error;
        }
    } else {
        $request = array("user" => $params["username"], "keepdns" => 0);
        if (array_key_exists("keepZone", $params)) {
            $request["keepdns"] = $params["keepZone"];
        }
        $output = cpanel_jsonRequest($params, "/json-api/removeacct", $request);
        if (!is_array($output)) {
            return $output;
        }
        if (!$output["result"][0]["status"]) {
            $error = $output["result"][0]["statusmsg"];
            if (!$error) {
                $error = "An unknown error occurred";
            }
            return $error;
        }
    }
    return "success";
}
function cpanel_ChangePassword($params)
{
    $postVars = "user=" . $params["username"] . "&pass=" . urlencode($params["password"]);
    $output = cpanel_jsonRequest($params, "/json-api/passwd", $postVars);
    if (!is_array($output)) {
        return $output;
    }
    if (!$output["passwd"][0]["status"]) {
        $error = $output["passwd"][0]["statusmsg"];
        if (!$error) {
            $error = "An unknown error occurred";
        }
        return $error;
    }
    return "success";
}
function cpanel_ChangePackage($params)
{
    if (array_key_exists("Package Name", $params["configoptions"])) {
        $params["configoption1"] = $params["configoptions"]["Package Name"];
    }
    $packages = cpanel_ListPackages($params, false);
    $output = cpanel_jsonRequest($params, "/json-api/listresellers", array("apiversion" => "1"));
    $rusernames = $output["reseller"];
    if ($params["type"] == "reselleraccount") {
        if (!in_array($params["username"], $rusernames)) {
            $makeowner = $params["configoption24"] ? 1 : 0;
            $postVars = "user=" . $params["username"] . "&makeowner=" . $makeowner;
            $output = cpanel_jsonRequest($params, "/json-api/setupreseller", $postVars);
            if (!is_array($output)) {
                return $output;
            }
            if (!$output["result"][0]["status"]) {
                $error = $output["result"][0]["statusmsg"];
                if (!$error) {
                    $error = "An unknown error occurred";
                }
                return $error;
            }
        }
        if ($params["configoption21"]) {
            $postVars = "reseller=" . $params["username"] . "&acllist=" . urlencode($params["configoption21"]);
            $output = cpanel_jsonRequest($params, "/json-api/setacls", $postVars);
            if (!is_array($output)) {
                return $output;
            }
            if (!$output["result"][0]["status"]) {
                $error = $output["result"][0]["statusmsg"];
                if (!$error) {
                    $error = "An unknown error occurred";
                }
                return $error;
            }
        }
        $postVars = "user=" . $params["username"];
        if ($params["configoption16"]) {
            $postVars .= "&enable_resource_limits=1&diskspace_limit=" . urlencode($params["configoption17"]) . "&bandwidth_limit=" . urlencode($params["configoption18"]);
            if ($params["configoption19"]) {
                $postVars .= "&enable_overselling_diskspace=1";
            }
            if ($params["configoption20"]) {
                $postVars .= "&enable_overselling_bandwidth=1";
            }
        } else {
            $postVars .= "&enable_resource_limits=0";
        }
        if ($params["configoption15"]) {
            if ($params["configoption15"] == "unlimited") {
                $postVars .= "&enable_account_limit=1&account_limit=";
            } else {
                $postVars .= "&enable_account_limit=1&account_limit=" . urlencode($params["configoption15"]);
            }
        } else {
            $postVars .= "&enable_account_limit=0&account_limit=";
        }
        $output = cpanel_jsonRequest($params, "/json-api/setresellerlimits", $postVars);
        if (!is_array($output)) {
            return $output;
        }
        if (!$output["result"][0]["status"]) {
            $error = $output["result"][0]["statusmsg"];
            if (!$error) {
                $error = "An unknown error occurred";
            }
            return $error;
        }
    } else {
        if (in_array($params["username"], $rusernames)) {
            $postVars = "user=" . $params["username"];
            $output = cpanel_jsonRequest($params, "/json-api/unsetupreseller", $postVars);
        }
        if ($params["configoption1"] != "Custom") {
            try {
                $plan = cpanel_ConfirmPackageName($params["configoption1"], $params["serverusername"], $packages);
            } catch (Exception $e) {
                return $e->getMessage();
            }
            $postVars = "user=" . $params["username"] . "&pkg=" . urlencode($plan);
            $output = cpanel_jsonRequest($params, "/json-api/changepackage", $postVars);
            if (!is_array($output)) {
                return $output;
            }
            if (!$output["result"][0]["status"]) {
                $error = $output["result"][0]["statusmsg"];
                if (!$error) {
                    $error = "An unknown error occurred";
                }
                return $error;
            }
        }
    }
    if (CPANELCONFPACKAGEADDONLICENSE && count($params["configoptions"])) {
        if (isset($params["configoptions"]["Disk Space"])) {
            $params["configoption3"] = cpanel_costrrpl($params["configoptions"]["Disk Space"]);
            $postVars = "api.version=1&user=" . urlencode($params["username"]) . "&quota=" . urlencode($params["configoption3"]);
            $output = cpanel_jsonRequest($params, "/json-api/editquota", $postVars);
        }
        if (isset($params["configoptions"]["Bandwidth"])) {
            $params["configoption5"] = cpanel_costrrpl($params["configoptions"]["Bandwidth"]);
            $postVars = "api.version=1&user=" . urlencode($params["username"]) . "&bwlimit=" . urlencode($params["configoption5"]);
            $output = cpanel_jsonRequest($params, "/json-api/limitbw", $postVars);
        }
        $postVars = "";
        if (isset($params["configoptions"]["FTP Accounts"])) {
            $params["configoption2"] = cpanel_costrrpl($params["configoptions"]["FTP Accounts"]);
            $postVars .= "MAXFTP=" . $params["configoption2"] . "&";
        }
        if (isset($params["configoptions"]["Email Accounts"])) {
            $params["configoption4"] = cpanel_costrrpl($params["configoptions"]["Email Accounts"]);
            $postVars .= "MAXPOP=" . $params["configoption4"] . "&";
        }
        if (isset($params["configoptions"]["MySQL Databases"])) {
            $params["configoption8"] = cpanel_costrrpl($params["configoptions"]["MySQL Databases"]);
            $postVars .= "MAXSQL=" . $params["configoption8"] . "&";
        }
        if (isset($params["configoptions"]["Subdomains"])) {
            $params["configoption10"] = cpanel_costrrpl($params["configoptions"]["Subdomains"]);
            $postVars .= "MAXSUB=" . $params["configoption10"] . "&";
        }
        if (isset($params["configoptions"]["Parked Domains"])) {
            $params["configoption12"] = cpanel_costrrpl($params["configoptions"]["Parked Domains"]);
            $postVars .= "MAXPARK=" . $params["configoption12"] . "&";
        }
        if (isset($params["configoptions"]["Addon Domains"])) {
            $params["configoption14"] = cpanel_costrrpl($params["configoptions"]["Addon Domains"]);
            $postVars .= "MAXADDON=" . $params["configoption14"] . "&";
        }
        if (isset($params["configoptions"]["CGI Access"])) {
            $params["configoption9"] = cpanel_costrrpl($params["configoptions"]["CGI Access"]);
            $postVars .= "HASCGI=" . $params["configoption9"] . "&";
        }
        if (isset($params["configoptions"]["Shell Access"])) {
            $params["configoption7"] = cpanel_costrrpl($params["configoptions"]["Shell Access"]);
            $postVars .= "shell=" . $params["configoption7"] . "&";
        }
        if ($postVars) {
            $postVars = "user=" . $params["username"] . "&domain=" . $params["domain"] . "&" . $postVars;
            if ($params["configoption13"]) {
                $postVars .= "CPTHEME=" . $params["configoption13"];
            }
            $output = cpanel_jsonRequest($params, "/json-api/modifyacct", $postVars);
        }
        if (isset($params["configoptions"]["Dedicated IP"])) {
            $params["configoption6"] = cpanel_costrrpl($params["configoptions"]["Dedicated IP"]);
            if ($params["configoption6"]) {
                $currentip = "";
                $alreadydedi = false;
                $postVars = "user=" . $params["username"];
                $output = cpanel_jsonRequest($params, "/json-api/accountsummary", $postVars);
                $currentip = $output["acct"][0]["ip"];
                $output = cpanel_jsonRequest($params, "/json-api/listips", array());
                foreach ($output["result"] as $result) {
                    if ($result["ip"] == $currentip && $result["mainaddr"] != "1") {
                        $alreadydedi = true;
                    }
                }
                if (!$alreadydedi) {
                    foreach ($output["result"] as $result) {
                        $active = $result["active"];
                        $dedicated = $result["dedicated"];
                        $ipaddr = $result["ip"];
                        $used = $result["used"];
                        if ($active && $dedicated && !$used) {
                            break;
                        }
                    }
                    $postVars = "user=" . $params["username"] . "&ip=" . $ipaddr;
                    $output = cpanel_jsonRequest($params, "/json-api/setsiteip", $postVars);
                    if ($output["result"][0]["status"]) {
                        $params["model"]->serviceProperties->save(array("dedicatedip" => $ipaddr));
                    }
                }
            }
        }
    }
    return "success";
}
function cpanel_UsageUpdate($params)
{
    $params["overrideTimeout"] = 30;
    try {
        $output = cpanel_jsonRequest($params, "/json-api/listaccts", array());
    } catch (Exception $e) {
        return $e->getMessage();
    }
    $domainData = array();
    $addons = WHMCS\Service\Addon::whereHas("productAddon", function ($query) {
        $query->where("module", "cpanel");
    })->with("productAddon")->where("server", "=", $params["serverid"])->whereIn("status", array("Active", "Suspended"))->get();
    if (is_array($output) && $output["acct"]) {
        foreach ($output["acct"] as $data) {
            $domain = $data["domain"];
            $diskused = $data["diskused"];
            $disklimit = $data["disklimit"];
            $diskused = str_replace("M", "", $diskused);
            $disklimit = str_replace("M", "", $disklimit);
            $domainData[$domain] = array("diskusage" => $diskused, "disklimit" => $disklimit, "lastupdate" => WHMCS\Carbon::now()->toDateTimeString());
        }
    }
    unset($output);
    $output = cpanel_jsonRequest($params, "/json-api/showbw", array());
    if (is_array($output) && !empty($output["bandwidth"][0]["acct"])) {
        foreach ($output["bandwidth"][0]["acct"] as $data) {
            $domain = $data["maindomain"];
            $bwused = $data["totalbytes"];
            $bwlimit = $data["limit"];
            $bwused = $bwused / (1024 * 1024);
            $bwlimit = $bwlimit / (1024 * 1024);
            $domainData[$domain]["bwusage"] = $bwused;
            $domainData[$domain]["bwlimit"] = $bwlimit;
        }
    }
    unset($output);
    foreach ($domainData as $domain => $data) {
        $update = NULL;
        $update = WHMCS\Database\Capsule::table("tblhosting")->where("domain", "=", $domain)->where("server", "=", $params["serverid"])->update($data);
        if (!$update) {
            foreach ($addons as $hostingAddonAccount) {
                $addonDomain = $hostingAddonAccount->serviceProperties->get("domain");
                if ($addonDomain == $domain) {
                    $hostingAddonAccount->serviceProperties->save($data);
                    break;
                }
            }
        }
        unset($domainData[$domain]);
    }
    unset($domainData);
    $data = WHMCS\Database\Capsule::table("tblhosting")->where("server", "=", $params["serverid"])->where("type", "=", "reselleraccount")->whereIn("domainstatus", array("Active", "Suspended"))->join("tblproducts", "tblproducts.id", "=", "tblhosting.packageid")->pluck("domain", "username");
    foreach ($data as $username => $domain) {
        if ($username) {
            $postVars = "reseller=" . $username;
            $output = cpanel_jsonRequest($params, "/json-api/resellerstats", $postVars);
            if (is_array($output) && $output["result"]) {
                $diskUsed = $output["result"]["diskused"];
                $diskLimit = $output["result"]["diskquota"];
                if (!$diskLimit) {
                    $diskLimit = $output["result"]["totaldiskalloc"];
                }
                $bwUsed = $output["result"]["totalbwused"];
                $bwLimit = $output["result"]["bandwidthlimit"];
                if (!$bwLimit) {
                    $bwLimit = $output["result"]["totalbwalloc"];
                }
                WHMCS\Database\Capsule::table("tblhosting")->where("domain", "=", $domain)->where("server", "=", $params["serverid"])->update(array("diskusage" => $diskUsed, "disklimit" => $diskLimit, "bwusage" => $bwUsed, "bwlimit" => $bwLimit, "lastupdate" => WHMCS\Carbon::now()->toDateTimeString()));
            }
        }
        unset($output);
        unset($username);
        unset($domain);
        unset($diskUsed);
        unset($diskLimit);
        unset($bwUsed);
        unset($bwLimit);
    }
    foreach ($addons as $addon) {
        if ($addon->productAddon->type != "reselleraccount") {
            continue;
        }
        $username = $addon->serviceProperties->get("username");
        $postVars = "reseller=" . $username;
        $output = cpanel_jsonRequest($params, "/json-api/resellerstats", $postVars);
        if (is_array($output) && $output["result"]) {
            $diskUsed = $output["result"]["diskused"];
            $diskLimit = $output["result"]["diskquota"];
            if (!$diskLimit) {
                $diskLimit = $output["result"]["totaldiskalloc"];
            }
            if (!$diskLimit) {
                $diskLimit = "Unlimited";
            }
            $bwUsed = $output["result"]["totalbwused"];
            $bwLimit = $output["result"]["bandwidthlimit"];
            if (!$bwLimit) {
                $bwLimit = $output["result"]["totalbwalloc"];
            }
            if (!$bwLimit) {
                $bwLimit = "Unlimited";
            }
            $addon->serviceProperties->save(array("diskusage" => $diskUsed, "disklimit" => $diskLimit, "bwusage" => $bwUsed, "bwlimit" => $bwLimit, "lastupdate" => WHMCS\Carbon::now()->toDateTimeString()));
        }
    }
}
function cpanel_req($params, $request, $notxml = false)
{
    try {
        $requestParts = explode("?", $request, 2);
        list($apiCommand, $requestString) = $requestParts;
        $data = cpanel_curlRequest($params, $apiCommand, $requestString);
    } catch (WHMCS\Exception $e) {
        return $e->getMessage();
    }
    if ($notxml) {
        $results = $data;
    } else {
        if (strpos($data, "Brute Force Protection") == true) {
            $results = "WHM has imposed a Brute Force Protection Block - Contact cPanel for assistance";
        } else {
            if (strpos($data, "<form action=\"/login/\" method=\"POST\">") == true) {
                $results = "Login Failed";
            } else {
                if (strpos($data, "SSL encryption is required") == true) {
                    $results = "SSL Required for Login";
                } else {
                    if (strpos($data, "META HTTP-EQUIV=\"refresh\" CONTENT=") && !$usessl) {
                        $results = "You must enable SSL Mode";
                    } else {
                        if (substr($data, 0, 1) != "<") {
                            $data = substr($data, strpos($data, "<"));
                        }
                        $results = XMLtoARRAY($data);
                        if ($results["CPANELRESULT"]["DATA"]["REASON"] == "Access denied") {
                            $results = "Login Failed";
                        }
                    }
                }
            }
        }
    }
    unset($data);
    return $results;
}
function cpanel_curlRequest($params, $apiCommand, $postVars, $stringsToMask = array())
{
    $whmIP = $params["serverip"];
    $whmHostname = $params["serverhostname"];
    $whmUsername = $params["serverusername"];
    $whmPassword = $params["serverpassword"];
    $whmHttpPrefix = $params["serverhttpprefix"];
    $whmPort = $params["serverport"];
    $whmAccessHash = preg_replace("'(\r|\n)'", "", $params["serveraccesshash"]);
    $whmSSL = $params["serversecure"] ? true : false;
    $curlTimeout = array_key_exists("overrideTimeout", $params) ? $params["overrideTimeout"] : 400;
    if (!$whmIP && !$whmHostname) {
        throw new WHMCS\Exception\Module\InvalidConfiguration("You must provide either an IP or Hostname for the Server");
    }
    if (!$whmUsername) {
        throw new WHMCS\Exception\Module\InvalidConfiguration("WHM Username is missing for the selected server");
    }
    if ($whmAccessHash) {
        $authStr = "WHM " . $whmUsername . ":" . $whmAccessHash;
    } else {
        if ($whmPassword) {
            $authStr = "Basic " . base64_encode($whmUsername . ":" . $whmPassword);
        } else {
            throw new WHMCS\Exception\Module\InvalidConfiguration("You must provide either an API Token (Recommended) or Password for WHM for the selected server");
        }
    }
    if (substr($apiCommand, 0, 1) == "/") {
        $apiCommand = substr($apiCommand, 1);
    }
    $url = $whmHttpPrefix . "://" . ($whmIP ? $whmIP : $whmHostname) . ":" . $whmPort . "/" . $apiCommand;
    if (is_array($postVars)) {
        $requestString = build_query_string($postVars);
    } else {
        if (is_string($postVars)) {
            $requestString = $postVars;
        } else {
            $requestString = "";
        }
    }
    $curlOptions = array("CURLOPT_HTTPHEADER" => array("Authorization: " . $authStr), "CURLOPT_TIMEOUT" => $curlTimeout);
    $ch = curlCall($url, $requestString, $curlOptions, true);
    $data = curl_exec($ch);
    if (curl_errno($ch)) {
        throw new WHMCS\Exception\Module\NotServicable("Connection Error: " . curl_error($ch) . "(" . curl_errno($ch) . ")");
    }
    if (strpos($data, "META HTTP-EQUIV=\"refresh\" CONTENT=") && !$whmSSL) {
        throw new WHMCS\Exception\Module\NotServicable("Please enable SSL Mode for this server and try again.");
    }
    if (!$data) {
        throw new WHMCS\Exception\Module\NotServicable("No response received. Please check connection settings.");
    }
    curl_close($ch);
    $action = str_replace(array("/xml-api/", "/json-api/"), "", $apiCommand);
    logModuleCall("cpanel", $action, $requestString, $data, "", $stringsToMask);
    return $data;
}
function cpanel_jsonRequest($params, $apiCommand, $postVars, $stringsToMask = array())
{
    $data = cpanel_curlrequest($params, $apiCommand, $postVars, $stringsToMask);
    if ($data) {
        $decodedData = json_decode($data, true);
        if (is_null($decodedData) && json_last_error() !== JSON_ERROR_NONE) {
            throw new WHMCS\Exception\Module\NotServicable($data);
        }
        if (isset($decodedData["cpanelresult"]["error"])) {
            throw new WHMCS\Exception\Module\GeneralError($decodedData["cpanelresult"]["error"]);
        }
        return $decodedData;
    }
    throw new WHMCS\Exception\Module\NotServicable("No Response from WHM API");
}
function cpanel_ClientArea($params)
{
    return array("overrideDisplayTitle" => ucfirst($params["domain"]), "tabOverviewReplacementTemplate" => "overview.tpl", "tabOverviewModuleOutputTemplate" => "loginbuttons.tpl");
}
function cpanel_TestConnection($params)
{
    $response = cpanel_jsonrequest($params, "/json-api/version", array());
    if (is_array($response) && array_key_exists("version", $response)) {
        return array("success" => true);
    }
    return array("error" => $response);
}
function cpanel_SingleSignOn($params, $user, $service, $app = "")
{
    if (!$user) {
        return "Username is required for login.";
    }
    $vars = array("api.version" => "1", "user" => $user, "service" => $service);
    if ($app) {
        $vars["app"] = $app;
    }
    try {
        $response = cpanel_jsonrequest($params, "/json-api/create_user_session", $vars);
        $resultCode = isset($response["metadata"]["result"]) ? $response["metadata"]["result"] : 0;
        if ($resultCode == "1") {
            $redirURL = $response["data"]["url"];
            if (!$params["serversecure"]) {
                $secureParts = array("https:", ":2087", ":2083", ":2096");
                $insecureParts = array("http:", ":2086", ":2082", ":2095");
                $redirURL = str_replace($secureParts, $insecureParts, $redirURL);
            }
            return array("success" => true, "redirectTo" => $redirURL);
        }
        if (isset($response["cpanelresult"]["data"]["reason"])) {
            return array("success" => false, "errorMsg" => "cPanel API Response: " . $response["cpanelresult"]["data"]["reason"]);
        }
        if (isset($response["metadata"]["reason"])) {
            return array("success" => false, "errorMsg" => "cPanel API Response: " . $response["metadata"]["reason"]);
        }
    } catch (WHMCS\Exception\Module\InvalidConfiguration $e) {
        return array("success" => false, "errorMsg" => "cPanel API Configuration Problem: " . $e->getMessage());
    } catch (WHMCS\Exception\Module\NotServicable $e) {
        return array("success" => false, "errorMsg" => "cPanel API Unreachable: " . $e->getMessage());
    } catch (WHMCS\Exception $e) {
    }
    return array("success" => false);
}
function cpanel_ServiceSingleSignOn($params)
{
    $user = $params["username"];
    $app = App::get_req_var("app");
    if ($params["producttype"] == "reselleraccount") {
        if ($app) {
            $service = "cpaneld";
        } else {
            $service = "whostmgrd";
        }
    } else {
        $service = "cpaneld";
    }
    return cpanel_singlesignon($params, $user, $service, $app);
}
function cpanel_AdminSingleSignOn($params)
{
    $user = $params["serverusername"];
    $service = "whostmgrd";
    return cpanel_singlesignon($params, $user, $service);
}
function cpanel_ClientAreaAllowedFunctions()
{
    return array("CreateEmailAccount");
}
function cpanel_CreateEmailAccount($params)
{
    $vars = array("cpanel_jsonapi_user" => $params["username"], "cpanel_jsonapi_apiversion" => "2", "cpanel_jsonapi_module" => "Email", "cpanel_jsonapi_func" => "addpop", "domain" => $params["domain"], "email" => App::get_req_var("email_prefix"), "password" => App::get_req_var("email_pw"), "quota" => (int) App::get_req_var("email_quota"));
    try {
        $response = cpanel_jsonrequest($params, "/json-api/cpanel", $vars);
        $resultCode = isset($response["cpanelresult"]["event"]["result"]) ? $response["cpanelresult"]["event"]["result"] : 0;
        if ($resultCode == "1") {
            return array("jsonResponse" => array("success" => true));
        }
    } catch (WHMCS\Exception\Module\GeneralError $e) {
        return array("jsonResponse" => array("success" => false, "errorMsg" => $e->getMessage()));
    } catch (WHMCS\Exception\Module\InvalidConfiguration $e) {
        logActivity("cPanel Client Quick Email Create Failed: API Configuration Problem - " . $e->getMessage());
    } catch (WHMCS\Exception\Module\NotServicable $e) {
        logActivity("cPanel Client Quick Email Create Failed: API Unreachable - " . $e->getMessage());
    } catch (WHMCS\Exception $e) {
        logActivity("cPanel Client Quick Email Create Failed: Unknown Error - " . $e->getMessage());
    }
    return array("jsonResponse" => array("success" => false, "errorMsg" => "An error occurred. Please contact support."));
}
function cpanel__addErrorToList($errorMsg, array &$errors)
{
    if (!$errorMsg) {
        return NULL;
    }
    if (preg_match("/\\s+\\(XID ([a-z\\d]+)\\)\\s+/i", $errorMsg, $matches)) {
        $xidFull = trim($matches[0]);
        $xidCode = $matches[1];
        $cleanMsg = str_replace($xidFull, " ", $errorMsg);
        $errors[$cleanMsg][] = $xidCode;
    } else {
        $errors[$errorMsg] = array();
    }
}
function cpanel__formatErrorList(array $errors)
{
    $ret = array();
    $maxXids = 5;
    foreach ($errors as $errorMsg => $xids) {
        $xidCount = is_array($xids) ? count($xids) : 0;
        if ($xidCount) {
            if ($maxXids < $xidCount) {
                $andMore = " and " . ($xidCount - $maxXids) . " more.";
                $xids = array_slice($xids, 0, $maxXids);
            } else {
                $andMore = "";
            }
            $xidList = " XIDs: " . implode(", ", $xids) . $andMore;
        } else {
            $xidList = "";
        }
        $ret[] = $errorMsg . $xidList;
    }
    return $ret;
}
function cpanel_GetSupportedApplicationLinks()
{
    $appLinksData = file_get_contents(ROOTDIR . "/modules/servers/cpanel/data/application_links.json");
    $appLinks = json_decode($appLinksData, true);
    if (array_key_exists("supportedApplicationLinks", $appLinks)) {
        return $appLinks["supportedApplicationLinks"];
    }
    return array();
}
function cpanel_GetRemovedApplicationLinks()
{
    $appLinksData = file_get_contents(ROOTDIR . "/modules/servers/cpanel/data/application_links.json");
    $appLinks = json_decode($appLinksData, true);
    if (array_key_exists("disabledApplicationLinks", $appLinks)) {
        return $appLinks["disabledApplicationLinks"];
    }
    return array();
}
function cpanel_IsApplicationLinkingSupportedByServer($params)
{
    try {
        $cpanelResponse = cpanel_jsonrequest($params, "/json-api/applist", "api.version=1");
        $resultCode = isset($cpanelResponse["metadata"]["result"]) ? $cpanelResponse["metadata"]["result"] : 0;
        if (!$resultCode) {
            $resultCode = isset($cpanelResponse["cpanelresult"]["data"]["result"]) ? $cpanelResponse["cpanelresult"]["data"]["result"] : 0;
        }
        if (0 < $resultCode) {
            return array("isSupported" => in_array("create_integration_link", $cpanelResponse["data"]["app"]));
        }
        if (isset($cpanelResponse["cpanelresult"]["error"])) {
            $errorMsg = $cpanelResponse["cpanelresult"]["error"];
        } else {
            if (isset($cpanelResponse["metadata"]["reason"])) {
                $errorMsg = $cpanelResponse["metadata"]["reason"];
            } else {
                $errorMsg = "Server response: " . preg_replace("/([\\d\"]),\"/", "\$1, \"", json_encode($cpanelResponse));
            }
        }
    } catch (WHMCS\Exception $e) {
        $errorMsg = $e->getMessage();
    }
    return array("errorMsg" => $errorMsg);
}
function cpanel_CreateApplicationLink($params)
{
    $systemUrl = $params["systemUrl"];
    $tokenEndpoint = $params["tokenEndpoint"];
    $clientCollection = $params["clientCredentialCollection"];
    $appLinks = $params["appLinks"];
    $stringsToMask = array();
    $commands = array();
    foreach ($clientCollection as $client) {
        $secret = $client->decryptedSecret;
        $identifier = $client->identifier;
        $apiData = array("api.version" => 1, "user" => $client->service->username, "group_id" => "whmcs", "label" => "Billing & Support", "order" => "1");
        $commands[] = "command=create_integration_group?" . urlencode(http_build_query($apiData));
        foreach ($appLinks as $scopeName => $appLinkParams) {
            $queryParams = array("scope" => "clientarea:sso " . $scopeName, "module_type" => "server", "module" => "cpanel");
            $fallbackUrl = $appLinkParams["fallback_url"];
            $fallbackUrl .= (strpos($fallbackUrl, "?") ? "&" : "?") . "ssoredirect=1";
            unset($appLinkParams["fallback_url"]);
            $apiData = array("api.version" => 1, "user" => $client->service->username, "subscriber_unique_id" => $identifier, "url" => $systemUrl . $fallbackUrl, "token" => $secret, "autologin_token_url" => $tokenEndpoint . "?" . http_build_query($queryParams));
            $commands[] = "command=create_integration_link?" . urlencode(http_build_query($apiData + $appLinkParams));
            $stringsToMask[] = urlencode(urlencode($secret));
        }
    }
    $cpanelResponse = cpanel_jsonrequest($params, "/json-api/batch", "api.version=1&" . implode("&", $commands), $stringsToMask);
    $errors = array();
    if ($cpanelResponse["metadata"]["result"] == 0) {
        foreach ($cpanelResponse["data"]["result"] as $key => $values) {
            if ($values["metadata"]["result"] == 0) {
                $reasonMsg = isset($values["metadata"]["reason"]) ? $values["metadata"]["reason"] : "";
                cpanel__adderrortolist($reasonMsg, $errors);
            }
        }
    }
    return cpanel__formaterrorlist($errors);
}
function cpanel_DeleteApplicationLink($params)
{
    $clientCollection = $params["clientCredentialCollection"];
    $appLinks = $params["appLinks"];
    $commands = array();
    foreach ($clientCollection as $client) {
        $apiData = array("api.version" => 1, "user" => $client->service->username, "group_id" => "whmcs");
        $commands[] = "command=remove_integration_group?" . urlencode(http_build_query($apiData));
        foreach ($appLinks as $scopeName => $appLinkParams) {
            $apiData = array("api.version" => 1, "user" => $client->service->username, "app" => $appLinkParams["app"]);
            $commands[] = "command=remove_integration_link?" . urlencode(http_build_query($apiData));
        }
    }
    $cpanelResponse = cpanel_jsonrequest($params, "/json-api/batch", "api.version=1&" . implode("&", $commands));
    $errors = array();
    if ($cpanelResponse["metadata"]["result"] == 0) {
        foreach ($cpanelResponse["data"]["result"] as $key => $values) {
            if ($values["metadata"]["result"] == 0) {
                $reasonMsg = isset($values["metadata"]["reason"]) ? $values["metadata"]["reason"] : "";
                cpanel__adderrortolist($reasonMsg, $errors);
            }
        }
    }
    return cpanel__formaterrorlist($errors);
}
function cpanel_ConfirmPackageName($package, $username, array $packages)
{
    switch ($username) {
        case "":
        case "root":
            if (array_key_exists($package, $packages)) {
                return $package;
            }
            break;
        default:
            if (array_key_exists((string) $username . "_" . $package, $packages)) {
                return (string) $username . "_" . $package;
            }
            if (array_key_exists($package, $packages)) {
                return $package;
            }
    }
    throw new WHMCS\Exception\Module\NotServicable("Product attribute Package Name \"" . $package . "\" not found on server");
}
function cpanel_ListPackages(array $params, $removeUsername = true)
{
    $result = cpanel_jsonrequest($params, "/json-api/listpkgs", "");
    if (array_key_exists("cpanelresult", $result) && array_key_exists("error", $result["cpanelresult"])) {
        throw new WHMCS\Exception\Module\NotServicable($result["cpanelresult"]["error"]);
    }
    $return = array();
    if (isset($result["package"])) {
        foreach ($result["package"] as $package) {
            $packageName = $params["serverusername"] == "root" || $removeUsername == false ? $package["name"] : str_replace($params["serverusername"] . "_", "", $package["name"]);
            $return[$packageName] = ucwords($packageName);
        }
    }
    return $return;
}
function cpanel_AutoPopulateServerConfig($params)
{
    $cpanelResponse = cpanel_jsonrequest($params, "/json-api/gethostname", "api.version=1");
    $hostname = $cpanelResponse["data"]["hostname"];
    $name = explode(".", $hostname, 2);
    $name = $name[0];
    $primaryIp = "";
    $cpanelResponse = cpanel_jsonrequest($params, "/json-api/get_shared_ip", "api.version=1");
    if (array_key_exists("ip", $cpanelResponse["data"]) && $cpanelResponse["data"]["ip"]) {
        $primaryIp = trim($cpanelResponse["data"]["ip"]);
    }
    $assignedIps = array();
    $cpanelResponse = cpanel_jsonrequest($params, "/json-api/listips", "api.version=1");
    foreach ($cpanelResponse["data"]["ip"] as $key => $data) {
        if (trim($data["public_ip"])) {
            if (!$primaryIp && $data["mainaddr"]) {
                $primaryIp = $data["public_ip"];
            } else {
                if ($primaryIp != $data["public_ip"]) {
                    $assignedIps[] = $data["public_ip"];
                }
            }
        }
    }
    $cpanelResponse = cpanel_jsonrequest($params, "/json-api/get_nameserver_config", "api.version=1");
    $nameservers = is_array($cpanelResponse["data"]["nameservers"]) ? $cpanelResponse["data"]["nameservers"] : array();
    return array("name" => $name, "hostname" => $hostname, "primaryIp" => $primaryIp, "assignedIps" => $assignedIps, "nameservers" => $nameservers);
}
function cpanel_GenerateCertificateSigningRequest($params)
{
    $certificate = $params["certificateInfo"];
    if (empty($certificate["city"]) || empty($certificate["state"]) || empty($certificate["country"])) {
        throw new WHMCS\Exception("A valid city, state and country are required to generate a Certificate Signing Request. Please set these values in the clients profile and try again.");
    }
    $command = "/json-api/cpanel";
    $postVars = array("keysize" => "2048", "friendly_name" => $certificate["domain"] . time(), "cpanel_jsonapi_user" => strtolower($params["username"]), "cpanel_jsonapi_apiversion" => "3", "cpanel_jsonapi_module" => "SSL", "cpanel_jsonapi_func" => "generate_key");
    $response = cpanel_jsonrequest($params, $command, $postVars);
    if ($response["result"]["errors"]) {
        $error = is_array($response["result"]["errors"]) ? implode(". ", $response["result"]["errors"]) : $response["result"]["errors"];
        throw new WHMCS\Exception("cPanel: Key Generation Failed: " . $error);
    }
    $keyId = $response["result"]["data"]["id"];
    $postVars = array("domains" => $certificate["domain"], "countryName" => $certificate["country"], "stateOrProvinceName" => $certificate["state"], "localityName" => $certificate["city"], "organizationName" => $certificate["orgname"] ?: "N/A", "organizationalUnitName" => $certificate["orgunit"], "emailAddress" => $certificate["email"], "key_id" => $keyId, "cpanel_jsonapi_user" => strtolower($params["username"]), "cpanel_jsonapi_apiversion" => "3", "cpanel_jsonapi_module" => "SSL", "cpanel_jsonapi_func" => "generate_csr");
    $response = cpanel_jsonrequest($params, $command, $postVars);
    if (isset($response["result"]["status"]) && $response["result"]["status"] == 1) {
        $csr = $response["result"]["data"]["text"];
        return $csr;
    }
    $errorMsg = isset($response["result"]["errors"]) ? is_array($response["result"]["errors"]) ? implode(". ", $response["result"]["errors"]) : $response["result"]["errors"] : json_encode($response);
    throw new WHMCS\Exception("cPanel: CSR Generation Failed: " . $errorMsg);
}
function cpanel_GetDocRoot($params)
{
    $command = "/json-api/cpanel";
    $postVars = array("cpanel_jsonapi_user" => strtolower($params["username"]), "cpanel_jsonapi_apiversion" => "2", "cpanel_jsonapi_module" => "DomainLookup", "cpanel_jsonapi_func" => "getdocroot", "domain" => $params["domain"]);
    $response = cpanel_jsonrequest($params, $command, $postVars);
    if (isset($response["cpanelresult"]["error"]) && $response["cpanelresult"]["error"]) {
        throw new WHMCS\Exception("cPanel: Unable to locate docroot: " . json_encode($response));
    }
    return $response["cpanelresult"]["data"][0]["docroot"];
}
function cpanel_CreateFileWithinDocRoot($params)
{
    $command = "/json-api/cpanel";
    $postVars = array("cpanel_jsonapi_user" => strtolower($params["username"]), "cpanel_jsonapi_apiversion" => "2", "cpanel_jsonapi_module" => "DomainLookup", "cpanel_jsonapi_func" => "getdocroot", "domain" => $params["certificateDomain"]);
    $response = cpanel_jsonrequest($params, $command, $postVars);
    if (isset($response["cpanelresult"]["error"]) && $response["cpanelresult"]["error"]) {
        throw new WHMCS\Exception("cPanel: Unable to locate docroot: " . json_encode($response));
    }
    $dir = array_key_exists("dir", $params) ? $params["dir"] : "";
    $basePath = $response["cpanelresult"]["data"][0]["reldocroot"];
    if ($dir) {
        $dirParts = explode("/", $dir);
        foreach ($dirParts as $dirPart) {
            $command = "/json-api/cpanel";
            $postVars = array("cpanel_jsonapi_user" => strtolower($params["username"]), "cpanel_jsonapi_apiversion" => "2", "cpanel_jsonapi_module" => "Fileman", "cpanel_jsonapi_func" => "mkdir", "path" => $basePath, "name" => $dirPart);
            cpanel_jsonrequest($params, $command, $postVars);
            $basePath .= "/" . $dirPart;
        }
    }
    $command = "/json-api/cpanel";
    $postVars = array("cpanel_jsonapi_user" => strtolower($params["username"]), "cpanel_jsonapi_apiversion" => "3", "cpanel_jsonapi_module" => "Fileman", "cpanel_jsonapi_func" => "save_file_content", "dir" => $basePath, "file" => $params["filename"], "from_charset" => "utf-8", "to_charset" => "utf-8", "content" => $params["fileContent"]);
    $response = cpanel_jsonrequest($params, $command, $postVars);
    if (isset($response["cpanelresult"]["error"]) && $response["cpanelresult"]["error"]) {
        throw new WHMCS\Exception("cPanel: Unable to create DV Auth File: " . json_encode($response));
    }
}
function cpanel_InstallSsl($params)
{
    $command = "/json-api/cpanel";
    $postVars = array("certificate" => $params["certificate"], "cpanel_jsonapi_user" => strtolower($params["username"]), "cpanel_jsonapi_apiversion" => "3", "cpanel_jsonapi_module" => "SSL", "cpanel_jsonapi_func" => "fetch_key_and_cabundle_for_certificate");
    $response = cpanel_jsonrequest($params, $command, $postVars);
    if ($response["result"]["status"] == 0) {
        throw new WHMCS\Exception($response["result"]["messages"]);
    }
    $key = $response["data"]["key"];
    $postVars = array("domain" => $params["certificateDomain"], "cert" => $params["certificate"], "key" => $key, "cpanel_jsonapi_user" => strtolower($params["username"]), "cpanel_jsonapi_apiversion" => "3", "cpanel_jsonapi_module" => "SSL", "cpanel_jsonapi_func" => "install_ssl");
    $response = cpanel_jsonrequest($params, $command, $postVars);
    if ($response["result"]["status"] == 0) {
        $error = $response["result"]["messages"] ? is_array($response["result"]["messages"]) ? implode(" ", $response["result"]["messages"]) : $response["result"]["messages"] : $response["result"]["errors"] ? is_array($response["result"]["errors"]) ? implode(" ", $response["result"]["errors"]) : $response["result"]["errors"] : "An unknown error occurred";
        throw new WHMCS\Exception($error);
    }
}
function cpanel_GetMxRecords(array $params)
{
    $domain = $params["mxDomain"];
    $command = "/json-api/cpanel";
    $postVars = array("domain" => $domain, "cpanel_jsonapi_user" => strtolower($params["username"]), "cpanel_jsonapi_apiversion" => "2", "cpanel_jsonapi_module" => "Email", "cpanel_jsonapi_func" => "listmx");
    $response = cpanel_jsonrequest($params, $command, $postVars);
    if (array_key_exists("error", $response["cpanelresult"]) && $response["cpanelresult"]["error"]) {
        $error = is_array($response["cpanelresult"]["error"]) ? implode(". ", $response["cpanelresult"]["error"]) : $response["cpanelresult"]["error"];
        throw new WHMCS\Exception("MX Retrieval Failed: " . $error);
    }
    return array("mxRecords" => $response["cpanelresult"]["data"][0]["entries"], "mxType" => $response["cpanelresult"]["data"][0]["detected"]);
}
function cpanel_DeleteMxRecords(array $params)
{
    $domain = $params["mxDomain"];
    foreach ($params["mxRecords"] as $mxRecord => $priority) {
        $command = "/json-api/cpanel";
        $postVars = array("domain" => $domain, "exchange" => $mxRecord, "preference" => $priority, "cpanel_jsonapi_user" => strtolower($params["username"]), "cpanel_jsonapi_apiversion" => "2", "cpanel_jsonapi_module" => "Email", "cpanel_jsonapi_func" => "delmx");
        $response = cpanel_jsonrequest($params, $command, $postVars);
        if (array_key_exists("error", $response["cpanelresult"]) && $response["cpanelresult"]["error"]) {
            $error = is_array($response["cpanelresult"]["error"]) ? implode(". ", $response["cpanelresult"]["error"]) : $response["cpanelresult"]["error"];
            throw new WHMCS\Exception("Unable to Delete Record: " . $error);
        }
    }
}
function cpanel_AddMxRecords(array $params)
{
    $domain = $params["mxDomain"];
    foreach ($params["mxRecords"] as $mxRecord => $priority) {
        $command = "/json-api/cpanel";
        $postVars = array("alwaysaccept" => $params["alwaysAccept"], "domain" => $domain, "exchange" => $mxRecord, "preference" => $priority, "oldexchange" => "", "oldpreference" => "", "cpanel_jsonapi_user" => strtolower($params["username"]), "cpanel_jsonapi_apiversion" => "2", "cpanel_jsonapi_module" => "Email", "cpanel_jsonapi_func" => "addmx");
        $response = cpanel_jsonrequest($params, $command, $postVars);
        if (array_key_exists("error", $response["cpanelresult"]) && $response["cpanelresult"]["error"]) {
            $error = is_array($response["cpanelresult"]["error"]) ? implode(". ", $response["cpanelresult"]["error"]) : $response["cpanelresult"]["error"];
            throw new WHMCS\Exception("Unable to Add MX Record: " . $error);
        }
    }
}
function cpanel_CreateFTPAccount(array $params)
{
    $command = "/json-api/cpanel";
    $postVars = array("user" => $params["ftpUsername"], "pass" => $params["ftpPassword"], "quota" => 0, "homedir" => "public_html", "cpanel_jsonapi_user" => strtolower($params["username"]), "cpanel_jsonapi_apiversion" => "3", "cpanel_jsonapi_module" => "Ftp", "cpanel_jsonapi_func" => "add_ftp");
    $response = cpanel_jsonrequest($params, $command, $postVars);
    if (array_key_exists("error", $response["cpanelresult"]) && $response["cpanelresult"]["error"]) {
        $error = is_array($response["cpanelresult"]["error"]) ? implode(". ", $response["cpanelresult"]["error"]) : $response["cpanelresult"]["error"];
        throw new WHMCS\Exception("Unable to Create FTP Account: " . $error);
    }
}
function cpanel_GetDns(array $params)
{
    $command = "/json-api/cpanel";
    $postVars = array("cpanel_jsonapi_user" => strtolower($params["username"]), "cpanel_jsonapi_apiversion" => "2", "cpanel_jsonapi_module" => "ZoneEdit", "cpanel_jsonapi_func" => "fetchzone_records", "domain" => $params["domain"]);
    $response = cpanel_jsonrequest($params, $command, $postVars);
    if (array_key_exists("error", $response["cpanelresult"]) && $response["cpanelresult"]["error"]) {
        $error = is_array($response["cpanelresult"]["error"]) ? implode(". ", $response["cpanelresult"]["error"]) : $response["cpanelresult"]["error"];
        throw new WHMCS\Exception("Unable to Get DNS: " . $error);
    }
    if (isset($response["cpanelresult"]["data"]) && is_array($response["cpanelresult"]["data"])) {
        return $response["cpanelresult"]["data"];
    }
    throw new WHMCS\Exception("Unexpected response for Get DNS: " . json_encode($response));
}
function cpanel_SetDnsRecord(array $params)
{
    $command = "/json-api/cpanel";
    $postVars = array("cpanel_jsonapi_user" => strtolower($params["username"]), "cpanel_jsonapi_apiversion" => "2", "cpanel_jsonapi_module" => "ZoneEdit", "cpanel_jsonapi_func" => "edit_zone_record", "domain" => $params["domain"]);
    $dnsRecord = is_array($params["dnsRecord"]) ? $params["dnsRecord"] : array();
    $postVars = array_merge($postVars, $dnsRecord);
    $response = cpanel_jsonrequest($params, $command, $postVars);
    if (array_key_exists("error", $response["cpanelresult"]) && $response["cpanelresult"]["error"]) {
        $error = is_array($response["cpanelresult"]["error"]) ? implode(". ", $response["cpanelresult"]["error"]) : $response["cpanelresult"]["error"];
        throw new WHMCS\Exception("Unable to Modify DNS: " . $error);
    }
    if (isset($response["cpanelresult"]["data"][0]["result"]["status"]) && $response["cpanelresult"]["data"][0]["result"]["status"] == 0) {
        throw new WHMCS\Exception("Unable to Modify DNS: " . $response["cpanelresult"]["data"][0]["result"]["statusmsg"]);
    }
}
function cpanel_ModifyDns(array $params)
{
    $serverDnsRecords = cpanel_getdns($params);
    $biggestLineNumber = 0;
    foreach ($serverDnsRecords as $record) {
        if ($biggestLineNumber < $record["line"]) {
            $biggestLineNumber = $record["line"];
        }
    }
    $newRecordCount = 0;
    $dnsRecordsToProvision = $params["dnsRecordsToProvision"];
    foreach ($dnsRecordsToProvision as $recordToProvision) {
        $recordToUpdate = NULL;
        foreach ($serverDnsRecords as $existingRecord) {
            if ($existingRecord["type"] == $recordToProvision["type"] && $existingRecord["name"] == $recordToProvision["name"]) {
                $recordToUpdate = $existingRecord;
                break;
            }
        }
        if (is_null($recordToUpdate)) {
            $newRecordCount++;
            $recordToUpdate = array("line" => $biggestLineNumber + $newRecordCount, "name" => $recordToProvision["name"], "type" => $recordToProvision["type"]);
        }
        if (in_array($recordToProvision["type"], array("A"))) {
            $recordToUpdate["address"] = $recordToProvision["value"];
        } else {
            if (in_array($recordToProvision["type"], array("CNAME"))) {
                $recordToUpdate["cname"] = $recordToProvision["value"];
            } else {
                if (in_array($recordToProvision["type"], array("TXT", "SRV"))) {
                    $recordToUpdate["txtdata"] = $recordToProvision["value"];
                }
            }
        }
        $params["dnsRecord"] = $recordToUpdate;
        cpanel_setdnsrecord($params);
    }
}
function cpanel_create_api_token(array $params)
{
    $tokenName = "WHMCS" . App::getLicense()->getLicenseKey() . genRandomVal(5);
    $command = "/json-api/api_token_create";
    $postVars = array("api.version" => 1, "token_name" => $tokenName);
    $response = cpanel_jsonrequest($params, $command, $postVars);
    if ($response["metadata"]["result"] == 1) {
        return array("success" => true, "api_token" => $response["data"]["token"]);
    }
    return array("success" => false, "error" => $response["metadata"]["reason"]);
}
function cpanel_request_backup(array $params)
{
    $command = "/json-api/cpanel";
    $postVars = array("arg-0" => $params["dest"], "arg-1" => $params["hostname"], "arg-2" => $params["user"], "arg-3" => $params["pass"], "arg-4" => $params["email"], "arg-5" => $params["port"], "arg-6" => $params["rdir"], "cpanel_jsonapi_user" => strtolower($params["username"]), "cpanel_jsonapi_apiversion" => "1", "cpanel_jsonapi_module" => "Fileman", "cpanel_jsonapi_func" => "fullbackup");
    $response = cpanel_jsonrequest($params, $command, $postVars);
    if (array_key_exists("error", $response["cpanelresult"]) && $response["cpanelresult"]["error"]) {
        $error = is_array($response["cpanelresult"]["error"]) ? implode(". ", $response["cpanelresult"]["error"]) : $response["cpanelresult"]["error"];
        throw new WHMCS\Exception("Unable to Request Backup: " . $error);
    }
}
function cpanel_list_ssh_keys(array $params)
{
    $command = "/json-api/cpanel";
    $postVars = array("pub" => 0, "cpanel_jsonapi_user" => strtolower($params["username"]), "cpanel_jsonapi_apiversion" => "2", "cpanel_jsonapi_module" => "SSH", "cpanel_jsonapi_func" => "listkeys");
    if (array_key_exists("key_name", $params)) {
        $postVars["keys"] = $params["key_name"];
    }
    if (array_key_exists("key_encryption_type", $params) && in_array($params["key_encryption_type"], array("rsa", "dsa"))) {
        $postVars["types"] = $params["key_encryption_type"];
    }
    if (array_key_exists("public_key", $params) && $params["public_key"]) {
        $postVars["pub"] = 1;
    }
    $response = cpanel_jsonrequest($params, $command, $postVars);
    $response = $response["cpanelresult"];
    if (!$response["event"]["result"]) {
        throw new WHMCS\Exception("Unable to Request SSH Key List: " . $response["event"]["reason"]);
    }
    return $response;
}
function cpanel_generate_ssh_key(array $params)
{
    $command = "/json-api/cpanel";
    $bits = 2048;
    if (array_key_exists("bits", $params)) {
        $bits = $params["bits"];
    }
    $postVars = array("cpanel_jsonapi_user" => strtolower($params["username"]), "cpanel_jsonapi_apiversion" => "2", "cpanel_jsonapi_module" => "SSH", "cpanel_jsonapi_func" => "genkey", "name" => $params["key_name"], "bits" => $bits);
    $response = cpanel_jsonrequest($params, $command, $postVars);
    $response = $response["cpanelresult"];
    if (!$response["event"]["result"]) {
        throw new WHMCS\Exception("Unable to Generate SSH Key: " . $response["event"]["reason"]);
    }
}
function cpanel_fetch_ssh_key(array $params)
{
    $command = "/json-api/cpanel";
    $postVars = array("cpanel_jsonapi_user" => strtolower($params["username"]), "cpanel_jsonapi_apiversion" => "2", "cpanel_jsonapi_module" => "SSH", "cpanel_jsonapi_func" => "fetchkey", "name" => $params["key_name"], "pub" => 0);
    if (array_key_exists("public_key", $params) && $params["public_key"]) {
        $postVars["pub"] = 1;
    }
    $response = cpanel_jsonrequest($params, $command, $postVars);
    $response = $response["cpanelresult"];
    if (!$response["event"]["result"]) {
        throw new WHMCS\Exception("Unable to Fetch SSH Key: " . $response["event"]["reason"]);
    }
    $keyData = $response["data"][0];
    $postVars = array("cpanel_jsonapi_user" => strtolower($params["username"]), "cpanel_jsonapi_apiversion" => "2", "cpanel_jsonapi_module" => "SSH", "cpanel_jsonapi_func" => "authkey", "key" => $keyData["name"], "action" => "authorize");
    cpanel_jsonrequest($params, $command, $postVars);
    return $keyData;
}
function cpanel_get_ssh_port(array $params)
{
    $command = "/json-api/cpanel";
    $postVars = array("cpanel_jsonapi_user" => strtolower($params["username"]), "cpanel_jsonapi_apiversion" => "3", "cpanel_jsonapi_module" => "SSH", "cpanel_jsonapi_func" => "get_port");
    $response = cpanel_jsonrequest($params, $command, $postVars);
    $response = $response["result"];
    if (!$response["status"]) {
        throw new WHMCS\Exception("Unable to Fetch SSH Port Number: " . $response["messages"]);
    }
    return $response["data"]["port"];
}
function cpanel_ListAccounts(array $params)
{
    $command = "/json-api/listaccts";
    $postVars = array("want" => "domain,user,plan,ip,unix_startdate,suspended,email,owner");
    $accounts = array();
    try {
        $response = cpanel_jsonrequest($params, $command, $postVars);
        if ($response["status"] == 1) {
            foreach ($response["acct"] as $userAccount) {
                if ($userAccount["owner"] != $params["serverusername"] && $userAccount["owner"] != $userAccount["user"]) {
                    continue;
                }
                $status = WHMCS\Service\Status::ACTIVE;
                if ($userAccount["suspended"]) {
                    $status = WHMCS\Service\Status::SUSPENDED;
                }
                $plan = $userAccount["plan"];
                if ($params["configoption22"]) {
                    $plan = explode("_", $plan);
                    $plan = $plan[1];
                }
                $account = array("name" => $userAccount["user"], "email" => $userAccount["email"], "username" => $userAccount["user"], "domain" => $userAccount["domain"], "uniqueIdentifier" => $userAccount["domain"], "product" => $plan, "primaryip" => $userAccount["ip"], "created" => WHMCS\Carbon::createFromTimestamp($userAccount["unix_startdate"])->toDateTimeString(), "status" => $status);
                $accounts[] = $account;
            }
            return array("success" => true, "accounts" => $accounts);
        } else {
            return array("success" => false, "accounts" => $accounts, "error" => $response["metadata"]["reason"]);
        }
    } catch (Exception $e) {
        return array("success" => false, "accounts" => $accounts, "error" => $e->getMessage());
    }
}
function cpanel_GetUserCount(array $params)
{
    $command = "/json-api/listaccts";
    $postVars = array("want" => "user,owner");
    try {
        $response = cpanel_jsonrequest($params, $command, $postVars);
        if ($response["status"] == 1) {
            $totalCount = count($response["acct"]);
            $ownedAccounts = 0;
            foreach ($response["acct"] as $userAccount) {
                if ($userAccount["owner"] == $params["serverusername"] || $userAccount["owner"] == $userAccount["user"]) {
                    $ownedAccounts++;
                }
            }
            return array("success" => true, "totalAccounts" => $totalCount, "ownedAccounts" => $ownedAccounts);
        }
    } catch (Exception $e) {
        return array("success" => false, "error" => $e->getMessage());
    }
}
function cpanel_GetRemoteMetaData(array $params)
{
    try {
        $apiData = urlencode(http_build_query(array("api.version" => 1)));
        $commands[] = "command=version?" . $apiData;
        $commands[] = "command=systemloadavg?" . $apiData;
        $commands[] = "command=get_maximum_users?" . $apiData;
        $cpanelResponse = cpanel_jsonrequest($params, "/json-api/batch", "api.version=1&" . implode("&", $commands));
        $errors = array();
        if ($cpanelResponse["metadata"]["result"] == 0) {
            foreach ($cpanelResponse["data"]["result"] as $key => $values) {
                if ($values["metadata"]["result"] == 0) {
                    $reasonMsg = "";
                    if (isset($values["metadata"]["reason"])) {
                        $reasonMsg = $values["metadata"]["reason"];
                    }
                    if (substr($reasonMsg, 0, 11) !== "Unknown app") {
                        cpanel__adderrortolist($reasonMsg, $errors);
                    }
                }
            }
        }
        $errors = cpanel__formaterrorlist($errors);
        if (0 < count($errors)) {
            return array("success" => false, "error" => implode(", ", $errors));
        }
        $version = "-";
        $loads = array("fifteen" => "0", "five" => "0", "one" => "0");
        $maxUsers = "0";
        foreach ($cpanelResponse["data"]["result"] as $key => $values) {
            if (!array_key_exists("data", $values)) {
                continue;
            }
            switch ($values["metadata"]["command"]) {
                case "get_maximum_users":
                    $maxUsers = $values["data"]["maximum_users"];
                    break;
                case "systemloadavg":
                    $loads = $values["data"];
                    break;
                case "version":
                    $version = $values["data"]["version"];
                    break;
            }
        }
        return array("version" => $version, "load" => $loads, "max_accounts" => $maxUsers);
    } catch (Exception $e) {
        return array("success" => false, "error" => $e->getMessage());
    }
}
function cpanel_RenderRemoteMetaData(array $params)
{
    $remoteData = $params["remoteData"];
    if ($remoteData) {
        $metaData = $remoteData->metaData;
        $version = "Unknown";
        $loadOne = $loadFive = $loadFifteen = 0;
        $maxAccounts = "Unlimited";
        if (array_key_exists("version", $metaData)) {
            $version = $metaData["version"];
        }
        if (array_key_exists("load", $metaData)) {
            $loadOne = $metaData["load"]["one"];
            $loadFive = $metaData["load"]["five"];
            $loadFifteen = $metaData["load"]["fifteen"];
        }
        if (array_key_exists("max_accounts", $metaData) && 0 < $metaData["max_accounts"]) {
            $maxAccounts = $metaData["max_accounts"];
        }
        return "cPanel Version: " . $version . "<br>\nLoad Averages: " . $loadOne . " " . $loadFive . " " . $loadFifteen . "<br>\nLicense Max # of Accounts: " . $maxAccounts;
    }
    return "";
}

?>