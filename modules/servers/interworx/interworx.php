<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function interworx_MetaData()
{
    return array("DisplayName" => "InterWorx", "APIVersion" => "1.0", "DefaultNonSSLPort" => "2080", "DefaultSSLPort" => "2443");
}
function interworx_ConfigOptions()
{
    $configarray = array("Package Name" => array("Type" => "text", "Size" => "25"), "Theme" => array("Type" => "text", "Size" => "25"), "Disk & BW Overselling" => array("Type" => "yesno", "Description" => "If reseller, tick to allow"));
    return $configarray;
}
function interworx_ClientArea($params)
{
    global $_LANG;
    $serverhost = $params["serverhostname"] ?: $params["serverip"];
    if ($params["type"] == "reselleraccount") {
        $form = sprintf("<form action=\"%s://%s:%s/nodeworx/index.php?action=login\" method=\"post\" target=\"_blank\">" . "<input type=\"hidden\" name=\"email\" value=\"%s\" />" . "<input type=\"hidden\" name=\"password\" value=\"%s\" />" . "<input type=\"submit\" value=\"%s\" class=\"button\" />" . "</form>", $params["serverhttpprefix"], WHMCS\Input\Sanitize::encode($serverhost), $params["serverport"], WHMCS\Input\Sanitize::encode($params["username"]), WHMCS\Input\Sanitize::encode($params["password"]), $_LANG["nodeworxlogin"]);
    } else {
        $form = sprintf("<form action=\"%s://%s:%s/siteworx/index.php?action=login\" method=\"post\" target=\"_blank\">" . "<input type=\"hidden\" name=\"email\" value=\"%s\" />" . "<input type=\"hidden\" name=\"password\" value=\"%s\" />" . "<input type=\"hidden\" name=\"domain\" value=\"%s\" />" . "<input type=\"submit\" value=\"%s\" class=\"button\" />" . "</form>", $params["serverhttpprefix"], WHMCS\Input\Sanitize::encode($serverhost), $params["serverport"], WHMCS\Input\Sanitize::encode($params["clientsdetails"]["email"]), WHMCS\Input\Sanitize::encode($params["password"]), WHMCS\Input\Sanitize::encode($params["domain"]), $_LANG["siteworxlogin"]);
    }
    return $form;
}
function interworx_AdminLink($params)
{
    $serverhost = $params["serverhostname"] ?: $params["serverip"];
    $form = sprintf("<form action=\"%s://%s:%s/nodeworx/\" method=\"post\" target=\"_blank\">" . "<input type=\"submit\" value=\"%s\" />" . "</form>", $params["serverhttpprefix"], WHMCS\Input\Sanitize::encode($serverhost), $params["serverport"], "InterWorx Panel");
    return $form;
}
function interworx__getSoapClient($params)
{
    if ($params["serverhostname"]) {
        $serverhost = $params["serverhostname"];
        $soapParams = array();
    } else {
        $serverhost = $params["serverip"];
        $opts = array("ssl" => array("verify_peer" => false, "verify_peer_name" => false));
        $soapParams = array("stream_context" => stream_context_create($opts));
    }
    $wsdl = $params["serverhttpprefix"] . "://" . $serverhost . ":" . $params["serverport"] . "/nodeworx/soap?wsdl";
    return new SoapClient($wsdl, $soapParams);
}
function interworx_CreateAccount($params)
{
    $key = $params["serveraccesshash"];
    $api_controller = "/nodeworx/siteworx";
    $input = array();
    if ($params["configoptions"]["Dedicated IP"]) {
        $action = "listDedicatedFreeIps";
        $client = interworx__getsoapclient($params);
        $result = $client->route($key, $api_controller, $action, $input);
        logModuleCall("interworx", $action, $input, $result);
        if ($result["status"]) {
            return $result["status"] . " - " . $result["payload"];
        }
    } else {
        $action = "listFreeIps";
        $client = interworx__getsoapclient($params);
        $result = $client->route($key, $api_controller, $action, $input);
        logModuleCall("interworx", $action, $input, $result);
        if ($result["status"]) {
            return $result["status"] . " - " . $result["payload"];
        }
    }
    $ipaddress = $result["payload"][0][0];
    if ($params["type"] == "reselleraccount") {
        $overselling = $params["configoption3"] ? "1" : "0";
        $api_controller = "/nodeworx/reseller";
        $action = "add";
        $input = array("nickname" => strtolower($params["clientsdetails"]["firstname"] . $params["clientsdetails"]["lastname"]), "email" => $params["clientsdetails"]["email"], "password" => $params["password"], "confirm_password" => $params["password"], "language" => "en-us", "theme" => $params["configoption2"], "billing_day" => "1", "status" => "active", "packagetemplate" => $params["configoption1"], "RSL_OPT_OVERSELL_STORAGE" => $overselling, "RSL_OPT_OVERSELL_BANDWIDTH" => $overselling, "ips" => $ipaddress, "database_servers" => "localhost");
        $params["model"]->serviceProperties->save(array("username" => $params["clientsdetails"]["email"]));
    } else {
        $action = "add";
        $input = array("domainname" => $params["domain"], "ipaddress" => $ipaddress, "uniqname" => $params["username"], "nickname" => strtolower($params["clientsdetails"]["firstname"] . $params["clientsdetails"]["lastname"]), "email" => $params["clientsdetails"]["email"], "password" => $params["password"], "confirm_password" => $params["password"], "language" => "en-us", "theme" => $params["configoption2"], "packagetemplate" => $params["configoption1"]);
    }
    $client = interworx__getsoapclient($params);
    $result = $client->route($key, $api_controller, $action, $input);
    logModuleCall("interworx", $action, $input, $result);
    if ($result["status"]) {
        return $result["status"] . " - " . $result["payload"];
    }
    return "success";
}
function interworx_TerminateAccount($params)
{
    $key = $params["serveraccesshash"];
    if ($params["type"] == "reselleraccount") {
        $resellers = interworx_GetResellers($params);
        $email = $params["clientsdetails"]["email"];
        $resellerid = $resellers[$email];
        if (!$resellerid) {
            return "Reseller ID Not Found";
        }
        $api_controller = "/nodeworx/reseller";
        $action = "delete";
        $input = array("reseller_id" => $resellerid);
    } else {
        $api_controller = "/nodeworx/siteworx";
        $action = "delete";
        $input = array("domain" => $params["domain"], "confirm_action" => "1");
    }
    $client = interworx__getsoapclient($params);
    $result = $client->route($key, $api_controller, $action, $input);
    logModuleCall("interworx", $action, $input, $result);
    if ($result["status"]) {
        return $result["status"] . " - " . $result["payload"];
    }
    return "success";
}
function interworx_UsageUpdate($params)
{
    $key = $params["serveraccesshash"];
    $api_controller = "/nodeworx/siteworx";
    $action = "listBandwidthAndStorageInMB";
    $input = array();
    $client = interworx__getsoapclient($params);
    $result = $client->route($key, $api_controller, $action, $input);
    logModuleCall("interworx", $action, $input, $result);
    $domainsdata = $result["payload"];
    $services = WHMCS\Service\Service::whereIn("domainstatus", array("Active", "Suspended"))->get();
    $addons = WHMCS\Service\Addon::whereHas("customFieldValues.customField", function ($query) {
        $query->where("fieldname", "Domain");
    })->with("customFieldValues", "customFieldValues.customField")->whereIn("status", array("Active", "Suspended"))->get();
    foreach ($domainsdata as $data) {
        $domain = $data["domain"];
        $bandwidth_used = $data["bandwidth_used"];
        $bandwidth = $data["bandwidth"];
        $storage_used = $data["storage_used"];
        $storage = $data["storage"];
        $model = $services->where("domain", $domain)->first();
        if (!$model) {
            foreach ($addons as $searchingAddon) {
                foreach ($searchingAddon->customFieldValues as $customFieldValue) {
                    if (!$customFieldValue->customField) {
                        continue;
                    }
                    if ($domain == $customFieldValue->value) {
                        $model = $searchingAddon;
                        break 2;
                    }
                }
            }
        }
        if ($model) {
            $model->serviceProperties->save(array("diskusage" => $storage_used, "disklimit" => $storage, "bwusage" => $bandwidth_used, "bwlimit" => $bandwidth, "lastupdate" => WHMCS\Carbon::now()->toDateTimeString()));
        }
    }
}
function interworx_SuspendAccount($params)
{
    $key = $params["serveraccesshash"];
    if ($params["type"] == "reselleraccount") {
        $resellers = interworx_GetResellers($params);
        $email = $params["clientsdetails"]["email"];
        $resellerid = $resellers[$email];
        if (!$resellerid) {
            return "Reseller ID Not Found";
        }
        $api_controller = "/nodeworx/reseller";
        $action = "edit";
        $input = array("reseller_id" => $resellerid, "status" => "inactive");
    } else {
        $api_controller = "/nodeworx/siteworx";
        $action = "edit";
        $input = array("domain" => $params["domain"], "status" => "0");
    }
    $client = interworx__getsoapclient($params);
    $result = $client->route($key, $api_controller, $action, $input);
    logModuleCall("interworx", $action, $input, $result);
    if ($result["status"]) {
        return $result["status"] . " - " . $result["payload"];
    }
    return "success";
}
function interworx_UnsuspendAccount($params)
{
    $key = $params["serveraccesshash"];
    if ($params["type"] == "reselleraccount") {
        $resellers = interworx_GetResellers($params);
        $email = $params["clientsdetails"]["email"];
        $resellerid = $resellers[$email];
        if (!$resellerid) {
            return "Reseller ID Not Found";
        }
        $api_controller = "/nodeworx/reseller";
        $action = "edit";
        $input = array("reseller_id" => $resellerid, "status" => "active");
    } else {
        $api_controller = "/nodeworx/siteworx";
        $action = "edit";
        $input = array("domain" => $params["domain"], "status" => "1");
    }
    $client = interworx__getsoapclient($params);
    $result = $client->route($key, $api_controller, $action, $input);
    logModuleCall("interworx", $action, $input, $result);
    if ($result["status"]) {
        return $result["status"] . " - " . $result["payload"];
    }
    return "success";
}
function interworx_ChangePassword($params)
{
    $key = $params["serveraccesshash"];
    if ($params["type"] == "reselleraccount") {
        $resellers = interworx_GetResellers($params);
        $email = $params["clientsdetails"]["email"];
        $resellerid = $resellers[$email];
        if (!$resellerid) {
            return "Reseller ID Not Found";
        }
        $api_controller = "/nodeworx/reseller";
        $action = "edit";
        $input = array("reseller_id" => $resellerid, "password" => $params["password"], "confirm_password" => $params["password"]);
    } else {
        $api_controller = "/nodeworx/siteworx";
        $action = "edit";
        $input = array("domain" => $params["domain"], "password" => $params["password"], "confirm_password" => $params["password"]);
    }
    $client = interworx__getsoapclient($params);
    $result = $client->route($key, $api_controller, $action, $input);
    logModuleCall("interworx", $action, $input, $result);
    if ($result["status"]) {
        return $result["status"] . " - " . $result["payload"];
    }
    return "success";
}
function interworx_ChangePackage($params)
{
    $key = $params["serveraccesshash"];
    if ($params["type"] == "reselleraccount") {
        $resellers = interworx_GetResellers($params);
        $email = $params["clientsdetails"]["email"];
        $resellerid = $resellers[$email];
        if (!$resellerid) {
            return "Reseller ID Not Found";
        }
        $overselling = $params["configoption3"] ? "1" : "0";
        $api_controller = "/nodeworx/reseller";
        $action = "edit";
        $input = array("reseller_id" => $resellerid, "package_template" => $params["configoption1"], "RSL_OPT_OVERSELL_STORAGE" => $overselling, "RSL_OPT_OVERSELL_BANDWIDTH" => $overselling);
    } else {
        $api_controller = "/nodeworx/siteworx";
        $action = "edit";
        $input = array("domain" => $params["domain"], "package_template" => $params["configoption1"]);
    }
    $client = interworx__getsoapclient($params);
    $result = $client->route($key, $api_controller, $action, $input);
    logModuleCall("interworx", $action, $input, $result);
    if ($result["status"]) {
        return $result["status"] . " - " . $result["payload"];
    }
    return "success";
}
function interworx_GetResellers($params)
{
    $key = $params["serveraccesshash"];
    $api_controller = "/nodeworx/reseller";
    $action = "listIds";
    $input = array();
    $client = interworx__getsoapclient($params);
    $result = $client->route($key, $api_controller, $action, $input);
    logModuleCall("interworx", $action, $input, $result);
    $resellers = array();
    foreach ($result["payload"] as $reseller) {
        list($resellerid, $reselleremail) = $reseller;
        $reselleremail = explode("(", $reselleremail, 2);
        $reselleremail = $reselleremail[1];
        $reselleremail = substr($reselleremail, 0, -1);
        $resellers[$reselleremail] = $resellerid;
    }
    return $resellers;
}

?>