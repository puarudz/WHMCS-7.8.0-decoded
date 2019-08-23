<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

require "../../../init.php";
$result = select_query("mod_licensing", "", array("licensekey" => $_POST["licensekey"]));
$data = mysql_fetch_array($result);
$licenseid = $data["id"];
$serviceid = $data["serviceid"];
$addonId = $data["addon_id"];
$validdomain = $data["validdomain"];
$validip = $data["validip"];
$validdirectory = $data["validdirectory"];
$reissues = $data["reissues"];
$status = $data["status"];
if ($addonId) {
    $model = WHMCS\Service\Addon::with("productAddon", "productAddon.moduleConfiguration")->find($addonId);
    $allowdomainconflict = (bool) $model->productAddon->moduleConfiguration()->where("setting_name", "=", "configoption4")->first()->value;
    $allowipconflict = (bool) $model->productAddon->moduleConfiguration()->where("setting_name", "=", "configoption5")->first()->value;
    $allowdirectoryconflict = (bool) $model->productAddon->moduleConfiguration()->where("setting_name", "=", "configoption6")->first()->value;
    $licensing_secretkey = (bool) $model->productAddon->moduleConfiguration()->where("setting_name", "=", "configoption8")->first()->value;
    $licensing_freetrial = (bool) $model->productAddon->moduleConfiguration()->where("setting_name", "=", "configoption9")->first()->value;
    $pid = $model->addonId;
} else {
    $model = WHMCS\Service\Service::with("product")->find($serviceid);
    $allowdomainconflict = $model->product->moduleConfigOption4;
    $allowipconflict = $model->product->moduleConfigOption5;
    $allowdirectoryconflict = $model->product->moduleConfigOption6;
    $licensing_secretkey = $model->product->moduleConfigOption8;
    $licensing_freetrial = $model->product->moduleConfigOption9;
    $pid = $model->packageId;
}
if (!$ip) {
    $ip = $_SERVER["REMOTE_ADDR"];
}
if (!$licenseid) {
    echo "<status>Invalid</status>";
    insert_query("mod_licensinglog", array("licenseid" => $licenseid, "domain" => $_POST["domain"], "ip" => $_POST["ip"], "path" => $_POST["dir"], "message" => "Invalid Key - " . $_POST["licensekey"], "datetime" => "now()"));
    exit;
}
update_query("mod_licensing", array("lastaccess" => "now()"), array("id" => $licenseid));
if ($status == "Expired") {
    echo "<status>Expired</status>";
    licensing_getlicreturndata($licenseid);
    insert_query("mod_licensinglog", array("licenseid" => $licenseid, "domain" => $_POST["domain"], "ip" => $_POST["ip"], "path" => $_POST["dir"], "message" => "License Expired", "datetime" => "now()"));
    exit;
}
if ($status == "Suspended") {
    echo "<status>Suspended</status>";
    licensing_getlicreturndata($licenseid);
    insert_query("mod_licensinglog", array("licenseid" => $licenseid, "domain" => $_POST["domain"], "ip" => $_POST["ip"], "path" => $_POST["dir"], "message" => "License Suspended", "datetime" => "now()"));
    exit;
}
if ($status == "Reissued") {
    if (substr($domain, 0, 4) == "www.") {
        $domain = substr($domain, 4);
    }
    $validdomain = $domain . ",www." . $domain;
    $validip = $ip;
    $validdirectory = $dir;
    update_query("mod_licensing", array("validdomain" => $validdomain, "validip" => $validip, "validdirectory" => $validdirectory, "status" => "Active"), array("id" => $licenseid));
    if (0 < $reissues) {
        insert_query("mod_licensinglog", array("licenseid" => $licenseid, "domain" => $_POST["domain"], "ip" => $_POST["ip"], "path" => $_POST["dir"], "message" => "License Reissued", "datetime" => "now()"));
    }
}
if ($status == "Reissued" || $status == "Active") {
    if ($licensing_freetrial) {
        $trialmatches = array();
        if ($model instanceof WHMCS\Service\Addon) {
            $where = "mod_licensing.id!=" . (int) $licenseid . " AND tblhostingaddons.addonid=" . (int) $pid . " AND mod_licensing.validdomain LIKE '%" . db_escape_string($domain) . "%' AND mod_licensing.validdomain!=''";
            $join = "tblhostingaddons ON tblhostingaddons.id=mod_licensing.addon_id";
        } else {
            $where = "mod_licensing.id!=" . (int) $licenseid . " AND tblhosting.packageid=" . (int) $pid . " AND mod_licensing.validdomain LIKE '%" . db_escape_string($domain) . "%' AND mod_licensing.validdomain!=''";
            $join = "tblhosting ON tblhosting.id=mod_licensing.serviceid";
        }
        $result = select_query("mod_licensing", "mod_licensing.*", $where, "", "", "", $join);
        while ($data = mysql_fetch_array($result)) {
            $triallicenseid = $data["id"];
            $trialvaliddomains = explode(",", $data["validdomain"]);
            if (in_array($domain, $trialvaliddomains)) {
                $trialmatches[] = $triallicenseid;
            }
        }
        if (count($trialmatches)) {
            echo "<status>Suspended</status>";
            licensing_getlicreturndata($licenseid);
            update_query("mod_licensing", array("status" => "Suspended"), array("id" => $licenseid));
            if (!function_exists("ServerSuspendAccount")) {
                require_once ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "modulefunctions.php";
            }
            $suspendReason = "Duplicate Free Trial Use";
            if ($model instanceof WHMCS\Service\Service) {
                ServerSuspendAccount($model->id, $suspendReason);
            } else {
                ServerSuspendAccount($model->serviceId, $suspendReason, $model->id);
            }
            insert_query("mod_licensinglog", array("licenseid" => $licenseid, "domain" => $_POST["domain"], "ip" => $_POST["ip"], "path" => $_POST["dir"], "message" => "License Suspended for Duplicate Trials Use (" . implode(",", $trialmatches) . ")", "datetime" => "now()"));
            exit;
        }
    }
    $result = select_query("mod_licensingbans", "", array("value" => $domain));
    $data = mysql_fetch_array($result);
    $banid = $data["id"];
    $bannotes = $data["notes"];
    if ($banid) {
        echo "<status>Suspended</status>";
        licensing_getlicreturndata($licenseid);
        update_query("mod_licensing", array("status" => "Suspended"), array("id" => $licenseid));
        if (!function_exists("ServerSuspendAccount")) {
            require_once ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "modulefunctions.php";
        }
        $suspendReason = "Banned Domain/IP";
        if ($model instanceof WHMCS\Service\Service) {
            ServerSuspendAccount($model->id, $suspendReason);
        } else {
            ServerSuspendAccount($model->serviceId, $suspendReason, $model->id);
        }
        insert_query("mod_licensinglog", array("licenseid" => $licenseid, "domain" => $_POST["domain"], "ip" => $_POST["ip"], "path" => $_POST["dir"], "message" => "Banned Domain/IP (" . $bannotes . ")", "datetime" => "now()"));
        exit;
    }
}
$validdomains = licensing_explode($validdomain);
$validips = licensing_explode($validip);
$validdirs = licensing_explode($validdirectory);
if (!$allowdomainconflict && !in_array($domain, $validdomains)) {
    echo "<status>Invalid</status>\n<message>Domain Invalid</message>";
    insert_query("mod_licensinglog", array("licenseid" => $licenseid, "domain" => $_POST["domain"], "ip" => $_POST["ip"], "path" => $_POST["dir"], "message" => "Domain Invalid", "datetime" => "now()"));
} else {
    if (!$allowipconflict && !in_array($ip, $validips)) {
        echo "<status>Invalid</status>\n<message>IP Address Invalid</message>";
        insert_query("mod_licensinglog", array("licenseid" => $licenseid, "domain" => $_POST["domain"], "ip" => $_POST["ip"], "path" => $_POST["dir"], "message" => "IP Address Invalid", "datetime" => "now()"));
    } else {
        if (!$allowdirectoryconflict && !in_array($dir, $validdirs)) {
            echo "<status>Invalid</status>\n<message>Directory Invalid</message>";
            insert_query("mod_licensinglog", array("licenseid" => $licenseid, "domain" => $_POST["domain"], "ip" => $_POST["ip"], "path" => $_POST["dir"], "message" => "Directory Invalid", "datetime" => "now()"));
        } else {
            echo "<status>Active</status>";
            licensing_getlicreturndata($licenseid);
        }
    }
}
function licensing_explode($vals)
{
    $vals = explode(",", $vals);
    foreach ($vals as $k => $v) {
        $vals[$k] = trim($v, " \t\n\r");
    }
    return $vals;
}
function licensing_getlicreturndata($licenseid)
{
    global $licensing_secret_key;
    global $licensing_secretkey;
    $result = select_query("mod_licensing", "", array("id" => $licenseid));
    $data = mysql_fetch_array($result);
    $serviceid = $data["serviceid"];
    $addonId = $data["addon_id"];
    $licensekey = $data["licensekey"];
    $validdomain = $data["validdomain"];
    $validip = $data["validip"];
    $validdirectory = $data["validdirectory"];
    $status = $data["status"];
    $validdomain = implode(",", licensing_explode($validdomain));
    $validip = implode(",", licensing_explode($validip));
    $validdirectory = implode(",", licensing_explode($validdirectory));
    if ($addonId) {
        $model = WHMCS\Service\Addon::with("productAddon", "client", "customFieldValues", "customFieldValues.customField")->find($addonId);
        $productid = $model->addonId;
        $productname = $model->productAddon->name;
    } else {
        $model = WHMCS\Service\Service::with("product", "client", "addons", "addons.productAddon", "customFieldValues", "customFieldValues.customField")->find($serviceid);
        $productid = $model->packageId;
        $productname = $model->product->name;
    }
    $nextduedate = $model->nextDueDate;
    $regdate = $model->registrationDate;
    $billingcycle = $model->billingCycle;
    $firstname = $model->client->firstName;
    $lastname = $model->client->lastName;
    $companyname = $model->client->companyName;
    $email = $model->client->email;
    $configoptions = "";
    $addons = "";
    $customfields = "";
    if ($model instanceof WHMCS\Service\Service) {
        $result = full_query("SELECT tblproductconfigoptions.optionname, tblproductconfigoptions.optiontype, tblproductconfigoptionssub.optionname, tblhostingconfigoptions.qty FROM tblhostingconfigoptions INNER JOIN tblproductconfigoptions ON tblproductconfigoptions.id = tblhostingconfigoptions.configid INNER JOIN tblproductconfigoptionssub ON tblproductconfigoptionssub.id = tblhostingconfigoptions.optionid INNER JOIN tblhosting ON tblhosting.id=tblhostingconfigoptions.relid INNER JOIN tblproductconfiglinks ON tblproductconfiglinks.gid=tblproductconfigoptions.gid WHERE tblhostingconfigoptions.relid=" . (int) $serviceid . " AND tblproductconfiglinks.pid=tblhosting.packageid ORDER BY tblproductconfigoptions.`order`,tblproductconfigoptions.id ASC");
        while ($data = mysql_fetch_array($result)) {
            if ($data[1] == "3") {
                if ($data[3]) {
                    $data[2] = "Yes";
                } else {
                    $data[2] = "";
                }
            } else {
                if ($data[1] == "4") {
                    $data[2] = $data[3];
                }
            }
            $configoptions .= $data[0] . "=" . $data[2] . "|";
        }
        $configoptions = substr($configoptions, 0, -1);
        foreach ($model->addons as $addon) {
            $name = $addon->name ?: $addon->productAddon->name;
            $nextduedate = $addon->nextDueDate;
            $addons .= "name=" . $name . ";nextduedate=" . $nextduedate . ";status=" . $addon->status . "|";
        }
        $addons = substr($addons, 0, -1);
    }
    foreach ($model->customFieldValues as $customFieldValue) {
        $customfields .= (string) $customFieldValue->customField->fieldName . "=" . $customFieldValue->value . "|";
    }
    $customfields = substr($customfields, 0, -1);
    $md5hash = isset($_POST["check_token"]) ? md5($licensing_secretkey . $_POST["check_token"]) : "";
    $xmlresp = "\n<registeredname>" . $firstname . " " . $lastname . "</registeredname>\n<companyname>" . $companyname . "</companyname>\n<email>" . $email . "</email>\n<serviceid>" . $serviceid . "</serviceid>\n<productid>" . $productid . "</productid>\n<productname>" . $productname . "</productname>\n<regdate>" . $regdate . "</regdate>\n<nextduedate>" . $nextduedate . "</nextduedate>\n<billingcycle>" . $billingcycle . "</billingcycle>\n<validdomain>" . $validdomain . "</validdomain>\n<validip>" . $validip . "</validip>\n<validdirectory>" . $validdirectory . "</validdirectory>\n<configoptions>" . $configoptions . "</configoptions>\n<customfields>" . $customfields . "</customfields>\n<addons>" . $addons . "</addons>\n<md5hash>" . $md5hash . "</md5hash>";
    echo $xmlresp;
    $hookresults = run_hook("LicensingAddonVerify", array("licenseid" => $licenseid, "serviceid" => $serviceid, "addonid" => $addonId, "xmlresponse" => "<status>Active</status>\n" . $xmlresp));
    foreach ($hookresults as $hookmergefields) {
        foreach ($hookmergefields as $k => $v) {
            echo "<" . $k . ">" . $v . "</" . $k . ">\n";
        }
    }
}

?>