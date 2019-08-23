<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require_once "lib/Plesk/Loader.php";
function plesk_MetaData()
{
    return array("DisplayName" => "Plesk", "APIVersion" => "1.1", "ListAccountsUniqueIdentifierDisplayName" => "Domain", "ListAccountsUniqueIdentifierField" => "domain", "ListAccountsProductField" => "configoption1");
}
function plesk_ConfigOptions(array $params)
{
    require_once "lib/Plesk/Translate.php";
    $translator = new Plesk_Translate();
    $resellerSimpleMode = $params["producttype"] == "reselleraccount";
    $configarray = array("servicePlanName" => array("FriendlyName" => $translator->translate("CONFIG_SERVICE_PLAN_NAME"), "Type" => "text", "Size" => "25", "Loader" => function (array $params) {
        $return = array();
        Plesk_Loader::init($params);
        $packages = Plesk_Registry::getInstance()->manager->getServicePlans();
        $return[""] = "None";
        foreach ($packages as $package) {
            $return[$package] = $package;
        }
        return $return;
    }, "SimpleMode" => true), "resellerPlanName" => array("FriendlyName" => $translator->translate("CONFIG_RESELLER_PLAN_NAME"), "Type" => "text", "Size" => "25", "Loader" => function (array $params) {
        $return = array();
        Plesk_Loader::init($params);
        $packages = Plesk_Registry::getInstance()->manager->getResellerPlans();
        $return[""] = "None";
        foreach ($packages as $package) {
            $return[$package] = $package;
        }
        return $return;
    }, "SimpleMode" => $resellerSimpleMode), "ipAdresses" => array("FriendlyName" => $translator->translate("CONFIG_WHICH_IP_ADDRESSES"), "Type" => "dropdown", "Options" => "IPv4 shared; IPv6 none,IPv4 dedicated; IPv6 none,IPv4 none; IPv6 shared,IPv4 none; IPv6 dedicated,IPv4 shared; IPv6 shared,IPv4 shared; IPv6 dedicated,IPv4 dedicated; IPv6 shared,IPv4 dedicated; IPv6 dedicated", "Default" => "IPv4 shared; IPv6 none", "Description" => "", "SimpleMode" => true), "powerUser" => array("FriendlyName" => $translator->translate("CONFIG_POWER_USER_MODE"), "Type" => "yesno", "Description" => $translator->translate("CONFIG_POWER_USER_MODE_DESCRIPTION")));
    return $configarray;
}
function plesk_AdminLink($params)
{
    $address = $params["serverhostname"] ? $params["serverhostname"] : $params["serverip"];
    $port = $params["serveraccesshash"] ? $params["serveraccesshash"] : "8443";
    $secure = $params["serversecure"] ? "https" : "http";
    if (empty($address)) {
        return "";
    }
    $form = sprintf("<form action=\"%s://%s:%s/login_up.php3\" method=\"post\" target=\"_blank\">" . "<input type=\"hidden\" name=\"login_name\" value=\"%s\" />" . "<input type=\"hidden\" name=\"passwd\" value=\"%s\" />" . "<input type=\"submit\" value=\"%s\">" . "</form>", $secure, WHMCS\Input\Sanitize::encode($address), WHMCS\Input\Sanitize::encode($port), WHMCS\Input\Sanitize::encode($params["serverusername"]), WHMCS\Input\Sanitize::encode($params["serverpassword"]), "Login to panel");
    return $form;
}
function plesk_ClientArea($params)
{
    try {
        Plesk_Loader::init($params);
        return Plesk_Registry::getInstance()->manager->getClientAreaForm($params);
    } catch (Exception $e) {
        return Plesk_Registry::getInstance()->translator->translate("ERROR_COMMON_MESSAGE", array("CODE" => $e->getCode(), "MESSAGE" => $e->getMessage()));
    }
}
function plesk_CreateAccount($params)
{
    try {
        Plesk_Loader::init($params);
        $translator = Plesk_Registry::getInstance()->translator;
        if ("" == $params["clientsdetails"]["firstname"] && "" == $params["clientsdetails"]["lastname"]) {
            return $translator->translate("ERROR_ACCOUNT_VALIDATION_EMPTY_FIRST_OR_LASTNAME");
        }
        if ("" == $params["username"]) {
            return $translator->translate("ERROR_ACCOUNT_VALIDATION_EMPTY_USERNAME");
        }
        Plesk_Registry::getInstance()->manager->createTableForAccountStorage();
        $account = WHMCS\Database\Capsule::table("mod_pleskaccounts")->where("userid", $params["clientsdetails"]["userid"])->where("usertype", $params["type"])->first();
        $panelExternalId = is_null($account) ? "" : $account->panelexternalid;
        $params["clientsdetails"]["panelExternalId"] = $panelExternalId;
        $accountId = NULL;
        try {
            $accountInfo = Plesk_Registry::getInstance()->manager->getAccountInfo($params, $panelExternalId);
            if (isset($accountInfo["id"])) {
                $accountId = $accountInfo["id"];
            }
        } catch (Exception $e) {
            if (Plesk_Api::ERROR_OBJECT_NOT_FOUND != $e->getCode()) {
                throw $e;
            }
        }
        if (!is_null($accountId) && Plesk_Object_Customer::TYPE_RESELLER == $params["type"]) {
            return $translator->translate("ERROR_RESELLER_ACCOUNT_IS_ALREADY_EXISTS", array("EMAIL" => $params["clientsdetails"]["email"]));
        }
        $params = array_merge($params, Plesk_Registry::getInstance()->manager->getIps($params));
        if (is_null($accountId)) {
            try {
                $accountId = Plesk_Registry::getInstance()->manager->addAccount($params);
            } catch (Exception $e) {
                if (Plesk_Api::ERROR_OPERATION_FAILED == $e->getCode()) {
                    return $translator->translate("ERROR_ACCOUNT_CREATE_COMMON_MESSAGE");
                }
                throw $e;
            }
        }
        Plesk_Registry::getInstance()->manager->addIpToIpPool($accountId, $params);
        if ("" == $panelExternalId && "" != ($possibleExternalId = Plesk_Registry::getInstance()->manager->getCustomerExternalId($params))) {
            WHMCS\Database\Capsule::table("mod_pleskaccounts")->insert(array("userid" => $params["clientsdetails"]["userid"], "usertype" => $params["type"], "panelexternalid" => $possibleExternalId));
        }
        if (!is_null($accountId) && Plesk_Object_Customer::TYPE_RESELLER == $params["type"]) {
            return "success";
        }
        $params["ownerId"] = $accountId;
        Plesk_Registry::getInstance()->manager->addWebspace($params);
        if (!empty($params["configoptions"])) {
            Plesk_Registry::getInstance()->manager->processAddons($params);
        }
        return "success";
    } catch (Exception $e) {
        return Plesk_Registry::getInstance()->translator->translate("ERROR_COMMON_MESSAGE", array("CODE" => $e->getCode(), "MESSAGE" => $e->getMessage()));
    }
}
function plesk_SuspendAccount($params)
{
    try {
        Plesk_Loader::init($params);
        $params["status"] = "root" != $params["serverusername"] && "admin" != $params["serverusername"] ? Plesk_Object_Customer::STATUS_SUSPENDED_BY_RESELLER : Plesk_Object_Customer::STATUS_SUSPENDED_BY_ADMIN;
        switch ($params["type"]) {
            case Plesk_Object_Customer::TYPE_CLIENT:
                Plesk_Registry::getInstance()->manager->setWebspaceStatus($params);
                break;
            case Plesk_Object_Customer::TYPE_RESELLER:
                Plesk_Registry::getInstance()->manager->setResellerStatus($params);
                break;
        }
        return "success";
    } catch (Exception $e) {
        return Plesk_Registry::getInstance()->translator->translate("ERROR_COMMON_MESSAGE", array("CODE" => $e->getCode(), "MESSAGE" => $e->getMessage()));
    }
}
function plesk_UnsuspendAccount($params)
{
    try {
        Plesk_Loader::init($params);
        switch ($params["type"]) {
            case Plesk_Object_Customer::TYPE_CLIENT:
                $params["status"] = Plesk_Object_Webspace::STATUS_ACTIVE;
                Plesk_Registry::getInstance()->manager->setWebspaceStatus($params);
                break;
            case Plesk_Object_Customer::TYPE_RESELLER:
                $params["status"] = Plesk_Object_Customer::STATUS_ACTIVE;
                Plesk_Registry::getInstance()->manager->setResellerStatus($params);
                break;
        }
        return "success";
    } catch (Exception $e) {
        return Plesk_Registry::getInstance()->translator->translate("ERROR_COMMON_MESSAGE", array("CODE" => $e->getCode(), "MESSAGE" => $e->getMessage()));
    }
}
function plesk_TerminateAccount($params)
{
    try {
        Plesk_Loader::init($params);
        switch ($params["type"]) {
            case Plesk_Object_Customer::TYPE_CLIENT:
                Plesk_Registry::getInstance()->manager->deleteWebspace($params);
                break;
            case Plesk_Object_Customer::TYPE_RESELLER:
                Plesk_Registry::getInstance()->manager->deleteReseller($params);
                break;
        }
        return "success";
    } catch (Exception $e) {
        return Plesk_Registry::getInstance()->translator->translate("ERROR_COMMON_MESSAGE", array("CODE" => $e->getCode(), "MESSAGE" => $e->getMessage()));
    }
}
function plesk_ChangePassword($params)
{
    try {
        Plesk_Loader::init($params);
        Plesk_Registry::getInstance()->manager->setAccountPassword($params);
        if (Plesk_Object_Customer::TYPE_RESELLER == $params["type"]) {
            return "success";
        }
        Plesk_Registry::getInstance()->manager->setWebspacePassword($params);
        return "success";
    } catch (Exception $e) {
        return Plesk_Registry::getInstance()->translator->translate("ERROR_COMMON_MESSAGE", array("CODE" => $e->getCode(), "MESSAGE" => $e->getMessage()));
    }
}
function plesk_AdminServicesTabFields($params)
{
    try {
        Plesk_Loader::init($params);
        $translator = Plesk_Registry::getInstance()->translator;
        $accountInfo = Plesk_Registry::getInstance()->manager->getAccountInfo($params);
        if (!isset($accountInfo["login"])) {
            return array();
        }
        if ($accountInfo["login"] == $params["username"]) {
            return array("" => $translator->translate("FIELD_CHANGE_PASSWORD_MAIN_PACKAGE_DESCR"));
        }
        return array("" => $translator->translate("FIELD_CHANGE_PASSWORD_ADDITIONAL_PACKAGE_DESCR", array("PACKAGE" => $params["domain"])));
    } catch (Exception $e) {
        return Plesk_Registry::getInstance()->translator->translate("ERROR_COMMON_MESSAGE", array("CODE" => $e->getCode(), "MESSAGE" => $e->getMessage()));
    }
}
function plesk_ChangePackage($params)
{
    try {
        Plesk_Loader::init($params);
        $params = array_merge($params, Plesk_Registry::getInstance()->manager->getIps($params));
        Plesk_Registry::getInstance()->manager->switchSubscription($params);
        if (Plesk_Object_Customer::TYPE_RESELLER == $params["type"]) {
            return "success";
        }
        Plesk_Registry::getInstance()->manager->processAddons($params);
        Plesk_Registry::getInstance()->manager->changeSubscriptionIp($params);
        return "success";
    } catch (Exception $e) {
        return Plesk_Registry::getInstance()->translator->translate("ERROR_COMMON_MESSAGE", array("CODE" => $e->getCode(), "MESSAGE" => $e->getMessage()));
    }
}
function plesk_UsageUpdate($params)
{
    $services = WHMCS\Service\Service::where("server", "=", $params["serverid"])->whereIn("domainstatus", array("Active", "Suspended"))->get();
    $addons = WHMCS\Service\Addon::whereHas("customFieldValues.customField", function ($query) {
        $query->where("fieldname", "Domain");
    })->with("customFieldValues", "customFieldValues.customField")->where("server", "=", $params["serverid"])->whereIn("status", array("Active", "Suspended"))->get();
    $domains = array();
    $resellerUsernames = array();
    $resellerAccountsUsage = array();
    $domainToModel = array();
    foreach ($services as $service) {
        if ($service->product->type == "reselleraccount") {
            $resellerUsernames["service"][] = $service->username;
            $resellerToModel[$service->username] = $service;
        } else {
            if ($service->domain) {
                $domains[] = $service->domain;
                $domainToModel[$service->domain] = $service;
            }
        }
    }
    foreach ($addons as $addon) {
        if ($addon->productAddon->type == "reselleraccount") {
            $resellerUsernames["addon"][] = $addon->username;
            $resellerToModel[$addon->username] = $addon;
            continue;
        }
        foreach ($addon->customFieldValues as $customFieldValue) {
            if (!$customFieldValue->customField) {
                continue;
            }
            if ($customFieldValue->value) {
                $domains[] = $customFieldValue->value;
                $domainToModel[$customFieldValue->value] = $addon;
            }
            break;
        }
    }
    if (!empty($resellerUsernames) && !empty($resellerUsernames["service"])) {
        $params["usernames"] = $resellerUsernames["service"];
        try {
            Plesk_Loader::init($params);
            $resellerServiceUsage = Plesk_Registry::getInstance()->manager->getResellersUsage($params);
        } catch (Exception $e) {
            return Plesk_Registry::getInstance()->translator->translate("ERROR_COMMON_MESSAGE", array("CODE" => $e->getCode(), "MESSAGE" => $e->getMessage()));
        }
        $resellerAccountsUsage = $resellerServiceUsage;
    }
    if (!empty($resellerUsernames) && !empty($resellerUsernames["addon"])) {
        $params["usernames"] = $resellerUsernames["addon"];
        try {
            Plesk_Loader::init($params);
            $resellerAddonUsage = Plesk_Registry::getInstance()->manager->getResellersUsage($params);
        } catch (Exception $e) {
            return Plesk_Registry::getInstance()->translator->translate("ERROR_COMMON_MESSAGE", array("CODE" => $e->getCode(), "MESSAGE" => $e->getMessage()));
        }
        $resellerAccountsUsage = array_merge($resellerAccountsUsage, $resellerAddonUsage);
    }
    if (!empty($resellerAccountsUsage)) {
        foreach ($resellerAccountsUsage as $username => $usage) {
            $domainModel = $resellerToModel[$username];
            if ($domainModel) {
                $domainModel->serviceProperties->save(array("diskusage" => $usage["diskusage"], "disklimit" => $usage["disklimit"], "bwusage" => $usage["bwusage"], "bwlimit" => $usage["bwlimit"], "lastupdate" => WHMCS\Carbon::now()->toDateTimeString()));
            }
        }
    }
    if (!empty($domains)) {
        $params["domains"] = $domains;
        try {
            Plesk_Loader::init($params);
            $domainsUsage = Plesk_Registry::getInstance()->manager->getWebspacesUsage($params);
        } catch (Exception $e) {
            return Plesk_Registry::getInstance()->translator->translate("ERROR_COMMON_MESSAGE", array("CODE" => $e->getCode(), "MESSAGE" => $e->getMessage()));
        }
        foreach ($domainsUsage as $domainName => $usage) {
            $domainModel = $domainToModel[$domainName];
            if ($domainModel) {
                $domainModel->serviceProperties->save(array("diskusage" => $usage["diskusage"], "disklimit" => $usage["disklimit"], "bwusage" => $usage["bwusage"], "bwlimit" => $usage["bwlimit"], "lastupdate" => WHMCS\Carbon::now()->toDateTimeString()));
            }
        }
    }
    return "success";
}
function plesk_TestConnection($params)
{
    try {
        Plesk_Loader::init($params);
        $translator = Plesk_Registry::getInstance()->translator;
        return array("success" => true);
    } catch (Exception $e) {
        return array("error" => Plesk_Registry::getInstance()->translator->translate("ERROR_COMMON_MESSAGE", array("CODE" => $e->getCode(), "MESSAGE" => $e->getMessage())));
    }
}
function plesk_GenerateCertificateSigningRequest(array $params)
{
    try {
        Plesk_Loader::init($params);
        $result = Plesk_Registry::getInstance()->manager->generateCSR($params);
        return array("csr" => $result->certificate->generate->result->csr->__toString(), "key" => $result->certificate->generate->result->pvt->__toString(), "saveData" => true);
    } catch (Exception $e) {
        return Plesk_Registry::getInstance()->translator->translate("ERROR_COMMON_MESSAGE", array("CODE" => $e->getCode(), "MESSAGE" => $e->getMessage()));
    }
}
function plesk_InstallSsl(array $params)
{
    try {
        Plesk_Loader::init($params);
        Plesk_Registry::getInstance()->manager->installSsl($params);
        return "success";
    } catch (Exception $e) {
        return Plesk_Registry::getInstance()->translator->translate("ERROR_COMMON_MESSAGE", array("CODE" => $e->getCode(), "MESSAGE" => $e->getMessage()));
    }
}
function plesk_GetMxRecords(array $params)
{
    try {
        Plesk_Loader::init($params);
        return Plesk_Registry::getInstance()->manager->getMxRecords($params);
    } catch (Exception $e) {
        throw new Exception("MX Retrieval Failed: " . Plesk_Registry::getInstance()->translator->translate("ERROR_COMMON_MESSAGE", array("CODE" => $e->getCode(), "MESSAGE" => $e->getMessage())));
    }
}
function plesk_DeleteMxRecords(array $params)
{
    try {
        Plesk_Loader::init($params);
        Plesk_Registry::getInstance()->manager->deleteMxRecords($params);
    } catch (Exception $e) {
        throw new Exception("Unable to Delete MX Record: " . Plesk_Registry::getInstance()->translator->translate("ERROR_COMMON_MESSAGE", array("CODE" => $e->getCode(), "MESSAGE" => $e->getMessage())));
    }
}
function plesk_AddMxRecords(array $params)
{
    try {
        Plesk_Loader::init($params);
        Plesk_Registry::getInstance()->manager->addMxRecords($params);
    } catch (Exception $e) {
        throw new Exception("MX Creation Failed: " . Plesk_Registry::getInstance()->translator->translate("ERROR_COMMON_MESSAGE", array("CODE" => $e->getCode(), "MESSAGE" => $e->getMessage())));
    }
}
function plesk_CreateFileWithinDocRoot(array $params)
{
    $ftpConnection = false;
    if (function_exists("ftp_ssl_connect")) {
        $ftpConnection = @ftp_ssl_connect($params["serverhostname"]);
    }
    if (!$ftpConnection) {
        $ftpConnection = @ftp_connect($params["serverhostname"]);
    }
    if (!$ftpConnection) {
        throw new Exception("Plesk: Unable to create DV Auth File: FTP Connection Failed");
    }
    $ftpLogin = @ftp_login($ftpConnection, $params["username"], $params["password"]);
    if (!$ftpLogin) {
        throw new Exception("Plesk: Unable to create DV Auth File: FTP Login Failed");
    }
    $tempFile = tempnam(sys_get_temp_dir(), "plesk");
    if (!$tempFile) {
        throw new Exception("Plesk: Unable to create DV Auth File: Unable to Create Temp File");
    }
    $file = fopen($tempFile, "w+");
    if (!fwrite($file, $params["fileContent"])) {
        throw new Exception("Plesk: Unable to create DV Auth File: Unable to Write to Temp File");
    }
    fclose($file);
    ftp_chdir($ftpConnection, "httpdocs");
    $dir = array_key_exists("dir", $params) ? $params["dir"] : "";
    if ($dir) {
        $dirParts = explode("/", $dir);
        foreach ($dirParts as $dirPart) {
            if (!@ftp_chdir($ftpConnection, $dirPart)) {
                ftp_mkdir($ftpConnection, $dirPart);
                ftp_chdir($ftpConnection, $dirPart);
            }
        }
    }
    $upload = ftp_put($ftpConnection, $params["filename"], $tempFile, FTP_ASCII);
    if (!$upload) {
        ftp_pasv($ftpConnection, true);
        $upload = ftp_put($ftpConnection, $params["filename"], $tempFile, FTP_ASCII);
    }
    ftp_close($ftpConnection);
    if (!$upload) {
        throw new Exception("Plesk: Unable to create DV Auth File: Unable to Upload File: " . json_encode(error_get_last()));
    }
}
function plesk_ListAccounts(array $params)
{
    try {
        Plesk_Loader::init($params);
        return array("success" => true, "accounts" => Plesk_Registry::getInstance()->manager->listAccounts($params));
    } catch (Exception $e) {
        return array("error" => Plesk_Registry::getInstance()->translator->translate("ERROR_COMMON_MESSAGE", array("CODE" => $e->getCode(), "MESSAGE" => $e->getMessage())));
    }
}
function plesk_GetUserCount(array $params)
{
    try {
        $totalCount = $ownedAccounts = 0;
        Plesk_Loader::init($params);
        $mainAccountId = 0;
        try {
            $mainAccount = Plesk_Registry::getInstance()->manager->getResellerByLogin(array("username" => $params["serverusername"]));
            $mainAccountId = $mainAccount["id"];
        } catch (Exception $e) {
        }
        $customers = Plesk_Registry::getInstance()->manager->getCustomers(array());
        foreach ($customers as $customer) {
            $customerData = (array) $customer->data->gen_info;
            if (array_key_exists("owner-login", $customerData) && $customerData["owner-login"] == $params["serverusername"]) {
                $totalCount += 1;
                $ownedAccounts += 1;
            } else {
                if (array_key_exists("owner-id", $customerData) && $customerData["owner-id"] == $mainAccountId) {
                    $totalCount += 1;
                    $ownedAccounts += 1;
                }
            }
        }
        try {
            $resellers = Plesk_Registry::getInstance()->manager->getResellers(array());
            foreach ($resellers as $reseller) {
                $reseller = (array) $reseller;
                $resellerId = $reseller["id"];
                if ($resellerId != $mainAccountId) {
                    $totalCount += count($resellers);
                    $ownedAccounts += count($resellers);
                    $resellerCustomers = Plesk_Registry::getInstance()->manager->getCustomersByOwner(array("ownerId" => $resellerId));
                    $totalCount += count($resellerCustomers);
                }
            }
        } catch (Exception $e) {
            throw $e;
        }
        return array("success" => true, "totalAccounts" => $totalCount, "ownedAccounts" => $ownedAccounts);
    } catch (Exception $e) {
        return array("error" => Plesk_Registry::getInstance()->translator->translate("ERROR_COMMON_MESSAGE", array("CODE" => $e->getCode(), "MESSAGE" => $e->getMessage())));
    }
}
function plesk_GetRemoteMetaData(array $params)
{
    try {
        $version = "-";
        $loads = array("fifteen" => "0", "five" => "0", "one" => "0");
        $maxUsers = "0";
        Plesk_Loader::init($params);
        $serverInformation = Plesk_Registry::getInstance()->manager->getServerData(array());
        if (isset($serverInformation->stat->version)) {
            $version = (string) $serverInformation->stat->version->plesk_version;
        }
        if (isset($serverInformation->stat->load_avg)) {
            $loads = array("fifteen" => (int) $serverInformation->stat->load_avg->l15 / 100, "five" => (int) $serverInformation->stat->load_avg->l5 / 100, "one" => (int) $serverInformation->stat->load_avg->l1 / 100);
        }
        if (isset($serverInformation->key)) {
            $licenseInfo = array();
            foreach ($serverInformation->key->property as $data) {
                $data = (array) $data;
                $licenseInfo[$data["name"]] = $data["value"];
            }
            if (array_key_exists("lim_cl", $licenseInfo)) {
                $maxUsers = $licenseInfo["lim_cl"];
            }
        }
        return array("version" => $version, "load" => $loads, "max_accounts" => $maxUsers);
    } catch (Exception $e) {
        return array("error" => Plesk_Registry::getInstance()->translator->translate("ERROR_COMMON_MESSAGE", array("CODE" => $e->getCode(), "MESSAGE" => $e->getMessage())));
    }
}
function plesk_RenderRemoteMetaData(array $params)
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
        return "Plesk Version: " . $version . "<br>\nLoad Averages: " . $loadOne . " " . $loadFive . " " . $loadFifteen . "<br>\nLicense Max # of Accounts: " . $maxAccounts;
    }
    return "";
}

?>