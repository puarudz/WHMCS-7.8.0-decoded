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
if (defined("LICENSINGADDONLICENSE")) {
    exit("License Hacking Attempt Detected");
}
if (App::getFromRequest("larefresh")) {
    $licensing->forceRemoteCheck();
}
define("LICENSINGADDONLICENSE", $licensing->isActiveAddon("Licensing Addon"));
function licensing_MetaData()
{
    return array("DisplayName" => "License Software", "APIVersion" => "1.1", "RequiresServer" => false, "addonLicenseRequired" => true, "addonLicenseName" => "Licensing Addon");
}
function licensing_ConfigOptions()
{
    if (!LICENSINGADDONLICENSE) {
        return array("License Required" => array("Type" => "na", "Description" => "You need to purchase the licensing addon from " . "<a href=\"http://go.whmcs.com/94/licensing-addon\" target=\"_blank\">www.whmcs.com/addons/licensing-addon</a> " . "before you can use this functionality. If you just purchased it recently, please " . "<a href=\"configproducts.php?action=edit&id=" . App::getFromRequest("id") . "&tab=3&larefresh=1\">click here</a> " . "to refresh this message"));
    }
    $id = App::getFromRequest("id");
    $addonsCollection = WHMCS\Database\Capsule::table("tbladdons")->orderBy("weight", "asc")->orderBy("name", "asc")->get();
    $supportUpdateAddons = array();
    $supportUpdateAddons[0] = "None";
    foreach ($addonsCollection as $addon) {
        $addonId = $addon->id;
        $addonName = str_replace(",", "&comma;", $addon->name);
        $addonPackages = explode(",", $addon->packages);
        if (in_array($id, $addonPackages)) {
            $supportUpdateAddons[$addonId] = $addonName;
        }
    }
    $configarray = array("Key Length" => array("Type" => "text", "Size" => "10", "Description" => "String Length eg. 10"), "Key Prefix" => array("Type" => "text", "Size" => "20", "Description" => "eg. Leased-"), "Allow Reissue" => array("Type" => "yesno", "Description" => "Tick to allow clients to self-reissue from the client area"), "Allow Domain Conflict" => array("Type" => "yesno", "Description" => "Tick to not validate Domains"), "Allow IP Conflict" => array("Type" => "yesno", "Description" => "Tick to not validate IPs"), "Allow Directory Conflict" => array("Type" => "yesno", "Description" => "Tick to not validate installation path"), "Support/Updates Addon" => array("Type" => "dropdown", "Options" => $supportUpdateAddons), "Secret Key" => array("Type" => "text", "Size" => "20", "Description" => "Used in MD5 Verification"), "Free Trial" => array("Type" => "yesno", "Description" => "Restricts license to one instance per Domain"));
    return $configarray;
}
function licensing_genkey($length, $prefix)
{
    if (!$length) {
        $length = 10;
    }
    $seeds = "abcdef0123456789";
    $key = NULL;
    $seeds_count = strlen($seeds) - 1;
    for ($i = 0; $i < $length; $i++) {
        $key .= $seeds[rand(0, $seeds_count)];
    }
    $licensekey = $prefix . $key;
    $result = select_query("mod_licensing", "COUNT(*)", array("licensekey" => $licensekey));
    $data = mysql_fetch_array($result);
    if ($data[0]) {
        $licensekey = licensing_genkey($length, $prefix);
    }
    return $licensekey;
}
function licensing_CreateAccount($params)
{
    if (!LICENSINGADDONLICENSE) {
        return "Your WHMCS license key is not enabled to use the Licensing Addon yet. Navigate to Addons > Licensing Manager to resolve.";
    }
    $addonId = array_key_exists("addonId", $params) && $params["addonId"] ? $params["addonId"] : 0;
    $existingLicense = licensing_does_license_exist($params);
    if ($existingLicense) {
        return "A license has already been generated for this item";
    }
    $length = $params["configoption1"];
    $prefix = $params["configoption2"];
    $licensekey = licensing_genkey($length, $prefix);
    WHMCS\Database\Capsule::table("mod_licensing")->insert(array("serviceid" => $params["serviceid"], "addon_id" => $addonId, "licensekey" => $licensekey, "validdomain" => "", "validip" => "", "validdirectory" => "", "reissues" => "0", "status" => "Reissued"));
    updateService(array("domain" => $licensekey, "username" => "", "password" => ""));
    $addonid = explode("|", $params["configoption7"]);
    $addonid = $addonid[0];
    if ($addonid) {
        $hostingModel = $params["model"] instanceof WHMCS\Service\Addon ? $params["model"]->service : $params["model"];
        $orderId = $hostingModel->orderId;
        $paymentMethod = $hostingModel->paymentGateway;
        $addon = WHMCS\Product\Addon::find($addonid);
        $pricing = WHMCS\Database\Capsule::table("tblpricing")->where("relid", "=", $addon->id)->where("type", "=", "addon")->where("currency", "=", $params["clientsdetails"]["currency"])->first();
        switch ($addon->billingCycle) {
            case "recurring":
                $serviceBillingCycle = strtolower(str_replace(array("-", " "), "", $hostingModel->billingCycle));
                $setupFeeField = substr($serviceBillingCycle, 0, 1) . "setupfee";
                if (!in_array($serviceBillingCycle, array("free", "freeaccount", "onetime")) && -1 < $pricing->{$serviceBillingCycle}) {
                    $addonSetupFee = $pricing->{$setupFeeField};
                    $addonRecurring = $pricing->{$serviceBillingCycle};
                    $addonBillingCycle = $hostingModel->billingCycle;
                    break;
                }
                switch (true) {
                    case -1 < $pricing->monthly:
                        $addonSetupFee = $pricing->msetupfee;
                        $addonRecurring = $pricing->monthly;
                        $addonBillingCycle = "Monthly";
                        break;
                    case -1 < $pricing->quarterly:
                        $addonSetupFee = $pricing->qsetupfee;
                        $addonRecurring = $pricing->monthly;
                        $addonBillingCycle = "Quarterly";
                        break;
                    case -1 < $pricing->semiannually:
                        $addonSetupFee = $pricing->ssetupfee;
                        $addonRecurring = $pricing->monthly;
                        $addonBillingCycle = "Semi-Annually";
                        break;
                    case -1 < $pricing->annually:
                        $addonSetupFee = $pricing->asetupfee;
                        $addonRecurring = $pricing->annually;
                        $addonBillingCycle = "Annually";
                        break;
                    case -1 < $pricing->biennially:
                        $addonSetupFee = $pricing->bsetupfee;
                        $addonRecurring = $pricing->biennially;
                        $addonBillingCycle = "Biennially";
                        break;
                    case -1 < $pricing->triennially:
                        $addonSetupFee = $pricing->tsetupfee;
                        $addonRecurring = $pricing->triennially;
                        $addonBillingCycle = "Triennially";
                        break;
                    default:
                        $addonSetupFee = 0;
                        $addonRecurring = 0;
                        $addonBillingCycle = "One Time";
                }
                break;
            default:
                $addonSetupFee = $pricing->msetupfee;
                $addonRecurring = $pricing->monthly;
                $addonBillingCycle = $addon->billingCycle;
        }
        $addonTax = $addon->applyTax;
        switch ($addonBillingCycle) {
            case "Monthly":
                $nextDueDate = WHMCS\Carbon::now()->addMonth()->toDateString();
                break;
            case "Quarterly":
                $nextDueDate = WHMCS\Carbon::now()->addMonths(3)->toDateString();
                break;
            case "Semi-Annually":
                $nextDueDate = WHMCS\Carbon::now()->addMonths(6)->toDateString();
                break;
            case "Annually":
                $nextDueDate = WHMCS\Carbon::now()->addMonths(12)->toDateString();
                break;
            case "Biennially":
                $nextDueDate = WHMCS\Carbon::now()->addMonths(24)->toDateString();
                break;
            case "Triennially":
                $nextDueDate = WHMCS\Carbon::now()->addMonths(36)->toDateString();
                break;
            default:
                $nextDueDate = "0000-00-00";
        }
        WHMCS\Database\Capsule::table("tblhostingaddons")->insert(array("orderid" => $orderId, "hostingid" => $params["serviceid"], "addonid" => $addon->id, "userid" => $params["userid"], "setupfee" => $addonSetupFee, "recurring" => $addonRecurring, "billingcycle" => $addonBillingCycle, "tax" => $addonTax, "status" => "Active", "regdate" => WHMCS\Carbon::now()->toDateString(), "nextduedate" => $nextDueDate, "nextinvoicedate" => $nextDueDate, "paymentmethod" => $paymentMethod));
    }
    return "success";
}
function licensing_SuspendAccount($params)
{
    if (!LICENSINGADDONLICENSE) {
        return "Your WHMCS license key is not enabled to use the Licensing Addon yet. Navigate to Addons > Licensing Manager to resolve.";
    }
    $addonId = array_key_exists("addonId", $params) && $params["addonId"] ? $params["addonId"] : 0;
    $existingLicense = licensing_does_license_exist($params);
    if (!$existingLicense) {
        return "No license exists for this item";
    }
    WHMCS\Database\Capsule::table("mod_licensing")->where("serviceid", "=", $params["serviceid"])->where("addon_id", "=", $addonId)->update(array("status" => "Suspended"));
    return "success";
}
function licensing_UnsuspendAccount($params)
{
    if (!LICENSINGADDONLICENSE) {
        return "Your WHMCS license key is not enabled to use the Licensing Addon yet. Navigate to Addons > Licensing Manager to resolve.";
    }
    $addonId = array_key_exists("addonId", $params) && $params["addonId"] ? $params["addonId"] : 0;
    $existingLicense = licensing_does_license_exist($params);
    if (!$existingLicense) {
        return "No license exists for this item";
    }
    WHMCS\Database\Capsule::table("mod_licensing")->where("serviceid", "=", $params["serviceid"])->where("addon_id", "=", $addonId)->update(array("status" => "Active"));
    return "success";
}
function licensing_TerminateAccount($params)
{
    if (!LICENSINGADDONLICENSE) {
        return "Your WHMCS license key is not enabled to use the Licensing Addon yet. Navigate to Addons > Licensing Manager to resolve.";
    }
    $addonId = array_key_exists("addonId", $params) && $params["addonId"] ? $params["addonId"] : 0;
    $existingLicense = licensing_does_license_exist($params);
    if (!$existingLicense) {
        return "No license exists for this item";
    }
    WHMCS\Database\Capsule::table("mod_licensing")->where("serviceid", "=", $params["serviceid"])->where("addon_id", "=", $addonId)->update(array("status" => "Expired"));
    return "success";
}
function licensing_AdminCustomButtonArray()
{
    $buttonarray = array("Reissue License" => "reissue", "Reset Reissues" => "reissuereset", "Revoke License" => "revoke", "Manage" => "manage");
    return $buttonarray;
}
function licensing_ClientAreaCustomButtonArray()
{
    $buttonarray = array("Reissue License" => "reissue");
    return $buttonarray;
}
function licensing_reissue($params)
{
    if (!LICENSINGADDONLICENSE) {
        return "Your WHMCS license key is not enabled to use the Licensing Addon yet. Navigate to Addons > Licensing Manager to resolve.";
    }
    $addonId = array_key_exists("addonId", $params) && $params["addonId"] ? $params["addonId"] : 0;
    $license = licensing_get_license($params);
    if (!$license) {
        return "No license exists for this item";
    }
    if (!$_SESSION["adminid"] && !$params["configoption3"]) {
        return "This license key is not allowed to be reissued";
    }
    if ($license->status != "Active") {
        return "License must be active to be reissued";
    }
    $maxreissues = WHMCS\Database\Capsule::table("tbladdonmodules")->where("module", "=", "licensing")->where("setting", "=", "maxreissues")->value("value");
    if (!$_SESSION["adminid"] && $maxreissues && $maxreissues <= $license->reissues) {
        return "The maximum number of reissues allowed has been reached for this license - please contact support";
    }
    WHMCS\Database\Capsule::table("mod_licensing")->where("id", "=", $license->id)->increment("reissues", 1, array("status" => "Reissued"));
    run_hook("LicensingAddonReissue", array("licenseid" => $license->id, "serviceid" => $params["serviceid"], "addon_id" => $addonId));
    return "success";
}
function licensing_reissuereset($params)
{
    if (!LICENSINGADDONLICENSE) {
        return "Your WHMCS license key is not enabled to use the Licensing Addon yet. Navigate to Addons > Licensing Manager to resolve.";
    }
    $license = licensing_get_license($params);
    if (!$license) {
        return "No license exists for this item";
    }
    WHMCS\Database\Capsule::table("mod_licensing")->where("id", "=", $license->id)->update(array("reissues" => 0));
    return "success";
}
function licensing_revoke($params)
{
    if (!LICENSINGADDONLICENSE) {
        return "Your WHMCS license key is not enabled to use the Licensing Addon yet. Navigate to Addons > Licensing Manager to resolve.";
    }
    $addonId = array_key_exists("addonId", $params) && $params["addonId"] ? $params["addonId"] : 0;
    $existingLicense = licensing_does_license_exist($params);
    if (!$existingLicense) {
        return "No license exists for this item";
    }
    WHMCS\Database\Capsule::table("mod_licensing")->where("serviceid", "=", $params["serviceid"])->where("addon_id", "=", $addonId)->delete();
    updateService(array("domain" => ""));
    return "success";
}
function licensing_manage($params)
{
    $license = licensing_get_license($params);
    if (!$license) {
        return "No license exists for this item";
    }
    return "redirect|addonmodules.php?module=licensing&action=manage&id=" . $license->id;
}
function licensing_valid_input_clean($vals)
{
    $vals = explode(",", $vals);
    foreach ($vals as $k => $v) {
        $vals[$k] = trim($v, " \t\n\r");
    }
    return implode(",", $vals);
}
function licensing_AdminServicesTabFields($params)
{
    global $aInt;
    if (!LICENSINGADDONLICENSE) {
        return array("Error" => "Your WHMCS license key is not enabled to use the Licensing Addon yet. Navigate to Addons > Licensing Manager to resolve.");
    }
    $license = licensing_get_license($params);
    if ($license) {
        $licenseId = $license->id;
        $validdomain = $license->validdomain;
        $validip = $license->validip;
        $validdirectory = $license->validdirectory;
        $reissues = $license->reissues;
        $status = $license->status;
        $lastAccess = $license->lastaccess;
        if ($lastAccess == "0000-00-00 00:00:00") {
            $lastAccess = "Never";
        } else {
            $lastAccess = fromMySQLDate($lastAccess, "time");
        }
        $statusoptions = "<option";
        if ($status == "Reissued") {
            $statusoptions .= " selected";
        }
        $statusoptions .= ">Reissued</option><option";
        if ($status == "Active") {
            $statusoptions .= " selected";
        }
        $statusoptions .= ">Active</option><option";
        if ($status == "Suspended") {
            $statusoptions .= " selected";
        }
        $statusoptions .= ">Suspended</option><option";
        if ($status == "Expired") {
            $statusoptions .= " selected";
        }
        $statusoptions .= ">Expired</option>";
        $licenseLogs = WHMCS\Database\Capsule::table("mod_licensinglog")->where("licenseid", "=", $licenseId)->orderBy("id", "DESC")->limit(10)->offset(0)->get();
        $tableData = array();
        foreach ($licenseLogs as $licenseLog) {
            $tableData[] = array(fromMySQLDate($licenseLog->datetime, true), $licenseLog->domain, $licenseLog->ip, $licenseLog->path, $licenseLog->message);
        }
        $aInt->sortableTableInit("nopagination");
        $recentAccessLog = $aInt->sortableTable(array("Date", "Domain", "IP", "Path", "Result"), $tableData);
        $fieldsArray = array("Valid Domains" => "<textarea name=\"modulefields[0]\" rows=\"2\" class=\"form-control input-600\">" . $validdomain . "</textarea>", "Valid IPs" => "<textarea name=\"modulefields[1]\" rows=\"2\" class=\"form-control input-600\">" . $validip . "</textarea>", "Valid Directory" => "<textarea name=\"modulefields[2]\" rows=\"2\" class=\"form-control input-600\">" . $validdirectory . "</textarea>", "License Status" => "<select name=\"modulefields[3]\" id=\"licensestatus\" class=\"form-control select-inline\">" . $statusoptions . "</select>", "Recent Access Log" => $recentAccessLog, "Number of Reissues" => $reissues, "Last Access" => $lastAccess);
        return $fieldsArray;
    } else {
        return array();
    }
}
function licensing_AdminServicesTabFieldsSave($params)
{
    update_query("mod_licensing", array("validdomain" => licensing_valid_input_clean($_POST["modulefields"][0]), "validip" => licensing_valid_input_clean($_POST["modulefields"][1]), "validdirectory" => licensing_valid_input_clean($_POST["modulefields"][2]), "status" => $_POST["modulefields"][3]), array("serviceid" => $params["serviceid"]));
}
function licensing_ChangePackage($params)
{
    if (!LICENSINGADDONLICENSE) {
        return "Your WHMCS license key is not enabled to use the Licensing Addon yet. Navigate to Addons > Licensing Manager to resolve.";
    }
    $addonid = explode("|", $params["configoption7"]);
    $addonid = $addonid[0];
    if ($addonid) {
        $currentaddon = get_query_val("tblhostingaddons", "id", array("hostingid" => $params["serviceid"], "addonid" => $addonid, "status" => "Active"));
        if (!$currentaddon) {
            $hostingModel = $params["model"] instanceof WHMCS\Service\Addon ? $params["model"]->service : $params["model"];
            $paymentMethod = $hostingModel->paymentGateway;
            $billingCycle = strtolower(str_replace(" ", "", $hostingModel->billingCycle));
            $orderId = WHMCS\Database\Capsule::table("tblupgrades")->where("type", "=", "package")->where("relid", "=", $hostingModel->id)->where("newvalue", "=", (string) $params["packageid"] . "," . $billingCycle)->value("orderid");
            $addon = WHMCS\Product\Addon::find($addonid);
            $pricing = WHMCS\Database\Capsule::table("tblpricing")->where("relid", "=", $addon->id)->where("type", "=", "addon")->where("currency", "=", $params["clientsdetails"]["currency"])->first();
            switch ($addon->billingCycle) {
                case "recurring":
                    $serviceBillingCycle = strtolower(str_replace(array("-", " "), "", $hostingModel->billingCycle));
                    $setupFeeField = substr($serviceBillingCycle, 0, 1) . "setupfee";
                    if (!in_array($serviceBillingCycle, array("free", "freeaccount", "onetime")) && -1 < $pricing->{$serviceBillingCycle}) {
                        $addonSetupFee = $pricing->{$setupFeeField};
                        $addonRecurring = $pricing->{$serviceBillingCycle};
                        $addonBillingCycle = $hostingModel->billingCycle;
                        break;
                    }
                    switch (true) {
                        case -1 < $pricing->monthly:
                            $addonSetupFee = $pricing->msetupfee;
                            $addonRecurring = $pricing->monthly;
                            $addonBillingCycle = "Monthly";
                            break;
                        case -1 < $pricing->quarterly:
                            $addonSetupFee = $pricing->qsetupfee;
                            $addonRecurring = $pricing->monthly;
                            $addonBillingCycle = "Quarterly";
                            break;
                        case -1 < $pricing->semiannually:
                            $addonSetupFee = $pricing->ssetupfee;
                            $addonRecurring = $pricing->monthly;
                            $addonBillingCycle = "Semi-Annually";
                            break;
                        case -1 < $pricing->annually:
                            $addonSetupFee = $pricing->asetupfee;
                            $addonRecurring = $pricing->annually;
                            $addonBillingCycle = "Annually";
                            break;
                        case -1 < $pricing->biennially:
                            $addonSetupFee = $pricing->bsetupfee;
                            $addonRecurring = $pricing->biennially;
                            $addonBillingCycle = "Biennially";
                            break;
                        case -1 < $pricing->triennially:
                            $addonSetupFee = $pricing->tsetupfee;
                            $addonRecurring = $pricing->triennially;
                            $addonBillingCycle = "Triennially";
                            break;
                        default:
                            $addonSetupFee = 0;
                            $addonRecurring = 0;
                            $addonBillingCycle = "One Time";
                    }
                    break;
                default:
                    $addonSetupFee = $pricing->msetupfee;
                    $addonRecurring = $pricing->monthly;
                    $addonBillingCycle = $addon->billingCycle;
            }
            $addonTax = $addon->applyTax;
            switch ($addonBillingCycle) {
                case "Monthly":
                    $nextDueDate = WHMCS\Carbon::now()->addMonth()->toDateString();
                    break;
                case "Quarterly":
                    $nextDueDate = WHMCS\Carbon::now()->addMonths(3)->toDateString();
                    break;
                case "Semi-Annually":
                    $nextDueDate = WHMCS\Carbon::now()->addMonths(6)->toDateString();
                    break;
                case "Annually":
                    $nextDueDate = WHMCS\Carbon::now()->addMonths(12)->toDateString();
                    break;
                case "Biennially":
                    $nextDueDate = WHMCS\Carbon::now()->addMonths(24)->toDateString();
                    break;
                case "Triennially":
                    $nextDueDate = WHMCS\Carbon::now()->addMonths(36)->toDateString();
                    break;
                default:
                    $nextDueDate = "0000-00-00";
            }
            WHMCS\Database\Capsule::table("tblhostingaddons")->insert(array("orderid" => $orderId, "hostingid" => $params["serviceid"], "addonid" => $addon->id, "setupfee" => $addonSetupFee, "recurring" => $addonRecurring, "billingcycle" => $addonBillingCycle, "tax" => $addonTax, "status" => "Active", "regdate" => WHMCS\Carbon::now()->toDateString(), "nextduedate" => $nextDueDate, "nextinvoicedate" => $nextDueDate, "paymentmethod" => $paymentMethod));
        }
    }
    return "success";
}
function licensing_ClientArea($params)
{
    $addonId = array_key_exists("addonId", $params) && $params["addonId"] ? $params["addonId"] : 0;
    $model = $params["model"];
    $licenseData = WHMCS\Database\Capsule::table("mod_licensing")->where("serviceid", "=", $params["serviceid"])->where("addon_id", "=", $addonId)->first();
    $productName = $model instanceof WHMCS\Service\Addon ? $model->productAddon->name : $model->product->name;
    $licenseKey = $licenseData->licensekey;
    $validDomain = $licenseData->validdomain;
    $validIp = $licenseData->validip;
    $validDirectory = $licenseData->validdirectory;
    $status = $licenseData->status;
    if ($model instanceof WHMCS\Service\Addon) {
        $allowReissues = (bool) $model->productAddon->moduleConfiguration()->where("setting_name", "=", "configoption3")->first()->value;
        $allowDomainConflicts = (bool) $model->productAddon->moduleConfiguration()->where("setting_name", "=", "configoption4")->first()->value;
        $allowIpConflicts = (bool) $model->productAddon->moduleConfiguration()->where("setting_name", "=", "configoption5")->first()->value;
        $allowDirectoryConflicts = (bool) $model->productAddon->moduleConfiguration()->where("setting_name", "=", "configoption6")->first()->value;
    } else {
        $allowReissues = (bool) $model->product->moduleConfigOption3;
        $allowDomainConflicts = (bool) $model->product->moduleConfigOption4;
        $allowIpConflicts = (bool) $model->product->moduleConfigOption5;
        $allowDirectoryConflicts = (bool) $model->product->moduleConfigOption6;
    }
    return array("overrideDisplayTitle" => $productName, "overrideBreadcrumb" => array(array("clientarea.php?action=products&module=licensing", Lang::trans("licensingaddon.mylicenses")), array("clientarea.php?action=productdetails#", Lang::trans("licensingaddon.manageLicense"))), "tabOverviewReplacementTemplate" => "managelicense.tpl", "tabOverviewModuleOutputTemplate" => "licenseinfo.tpl", "templateVariables" => array("licensekey" => $licenseKey, "validdomain" => $validDomain, "validip" => $validIp, "validdirectory" => $validDirectory, "status" => $status, "allowreissues" => $allowReissues, "allowDomainConflicts" => $allowDomainConflicts, "allowIpConflicts" => $allowIpConflicts, "allowDirectoryConflicts" => $allowDirectoryConflicts));
}
function licensing_does_license_exist(array $params)
{
    $addonId = array_key_exists("addonId", $params) && $params["addonId"] ? $params["addonId"] : 0;
    return WHMCS\Database\Capsule::table("mod_licensing")->where("serviceid", "=", $params["serviceid"])->where("addon_id", "=", $addonId)->count();
}
function licensing_get_license(array $params)
{
    $addonId = array_key_exists("addonId", $params) && $params["addonId"] ? $params["addonId"] : 0;
    return WHMCS\Database\Capsule::table("mod_licensing")->where("serviceid", "=", $params["serviceid"])->where("addon_id", "=", $addonId)->first();
}

?>