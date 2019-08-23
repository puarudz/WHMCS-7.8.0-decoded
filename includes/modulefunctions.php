<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function getModuleType($id)
{
    $result = select_query("tblservers", "type", array("id" => $id));
    $data = mysql_fetch_array($result);
    $type = $data["type"];
    return $type;
}
function ModuleBuildParams($serviceID, $addonId = 0)
{
    $server = new WHMCS\Module\Server();
    if ($addonId) {
        if (!$server->loadByAddonId($addonId)) {
            logActivity("Required Product Module '" . $server->getAddonModule() . "' Missing");
        }
    } else {
        if (!$server->loadByServiceID($serviceID)) {
            logActivity("Required Product Module '" . $server->getServiceModule() . "' Missing");
        }
    }
    return $server->buildParams();
}
function ModuleCallFunction($function, $serviceID, $extraParams = array(), $addonId = 0)
{
    $server = new WHMCS\Module\Server();
    if ($addonId && !$server->loadByAddonId($addonId)) {
        if (!$server->getAddonModule()) {
            return "success";
        }
        logActivity("Required Product Module '" . $server->getAddonModule() . "' Missing");
        return "Module Not Found";
    }
    if (!$addonId && !$server->loadByServiceID($serviceID)) {
        if (!$server->getServiceModule()) {
            return "success";
        }
        logActivity("Required Product Module '" . $server->getServiceModule() . "' Missing");
        return "Module Not Found";
    }
    $params = $server->buildParams();
    if (is_array($extraParams)) {
        $params = array_merge($params, $extraParams);
    }
    $serviceid = (int) $params["serviceid"];
    $userid = (int) $params["userid"];
    $hookresults = run_hook("PreModule" . $function, array("params" => $params));
    $hookabort = false;
    foreach ($hookresults as $hookvals) {
        foreach ($hookvals as $k => $v) {
            if ($k == "abortcmd" && $v === true) {
                $hookabort = true;
                $result = "Function Aborted by Action Hook Code";
            }
        }
        if (!$hookabort) {
            $params = array_replace_recursive($params, $hookvals);
        }
    }
    $entityType = "service";
    $entityId = $serviceID;
    $entityModule = $server->getServiceModule();
    $serviceOrAddon = "Service ID: " . $serviceID;
    $extraSaveData = array();
    if ($addonId) {
        $entityType = "addon";
        $entityId = $addonId;
        $entityModule = $server->getAddonModule();
        $serviceOrAddon = "Addon ID: " . $addonId;
    }
    $logname = $function;
    if ($logname == "ChangePackage") {
        $logname = "Change Package";
    } else {
        if ($logname == "ChangePassword") {
            $logname = "Change Password";
        }
    }
    if (!$hookabort) {
        $modfuncname = in_array($function, array("Create", "Suspend", "Unsuspend", "Terminate")) ? $function . "Account" : $function;
        if ($server->functionExists($modfuncname)) {
            try {
                $result = $server->call($modfuncname, $params);
            } catch (Exception $e) {
                $result = $e->getMessage();
            }
            if ($result == "success") {
                $extra_log_info = $suspendReason = "";
                if ($function == "Suspend") {
                    $suspendReason = "";
                    if (isset($params["suspendreason"]) && $params["suspendreason"] != "Overdue on Payment") {
                        $suspendReason = $params["suspendreason"];
                    }
                    if ($suspendReason) {
                        $extra_log_info = " - Reason: " . $suspendReason;
                    }
                }
                logActivity("Module " . $logname . " Successful" . $extra_log_info . " - " . $serviceOrAddon, $userid);
                $updatearray = array();
                if ($function == "Create") {
                    $updatearray = array("domainstatus" => "Active", "termination_date" => "0000-00-00");
                    if ($entityType == "addon") {
                        $updatearray = array("status" => "Active", "termination_date" => "0000-00-00");
                    }
                } else {
                    if ($function == "Suspend") {
                        $updatearray = array("domainstatus" => "Suspended", "suspendreason" => $suspendReason);
                        if ($entityType == "addon") {
                            $updatearray = array("status" => "Suspended");
                            $extraSaveData["suspend_reason"] = $suspendReason;
                        }
                    } else {
                        if ($function == "Unsuspend") {
                            $updatearray = array("domainstatus" => "Active", "suspendreason" => "", "termination_date" => "0000-00-00");
                            if ($entityType == "addon") {
                                $updatearray = array("status" => "Active", "termination_date" => "0000-00-00");
                                $extraSaveData["suspend_reason"] = "";
                            }
                        } else {
                            if ($function == "Terminate") {
                                if ($entityType == "addon") {
                                    $updatearray = array("status" => "Terminated");
                                    if (in_array(WHMCS\Database\Capsule::table("tblhostingaddons")->where("id", "=", $entityId)->value("termination_date"), array("0000-00-00", "1970-01-01"))) {
                                        $updatearray["termination_date"] = date("Y-m-d");
                                    }
                                } else {
                                    $updatearray = array("domainstatus" => "Terminated");
                                    if (in_array(WHMCS\Database\Capsule::table("tblhosting")->where("id", "=", $serviceid)->value("termination_date"), array("0000-00-00", "1970-01-01"))) {
                                        $updatearray["termination_date"] = date("Y-m-d");
                                    }
                                    $addons = WHMCS\Service\Addon::where("hostingid", "=", $serviceid)->whereIn("status", array("Active", "Suspended"))->get();
                                    foreach ($addons as $addon) {
                                        if ($addon->productAddon->module) {
                                            WHMCS\Service\Automation\AddonAutomation::factory($addon)->runAction("TerminateAccount");
                                        } else {
                                            $addon->status = "Terminated";
                                            $addon->terminationDate = WHMCS\Carbon::now()->toDateString();
                                            $addon->save();
                                            run_hook("AddonTerminated", array("id" => $addon->id, "userid" => $addon->clientId, "serviceid" => $addon->serviceId, "addonid" => $addon->addonId));
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if (0 < count($updatearray)) {
                    $table = $entityType == "addon" ? "tblhostingaddons" : "tblhosting";
                    update_query($table, $updatearray, array("id" => $entityId));
                    if ($extraSaveData) {
                    }
                }
                if ($server->isApplicationLinkSupported() && $server->isApplicationLinkingEnabled()) {
                    try {
                        $errors = $server->doSingleApplicationLinkCall("Create");
                        if (is_array($errors) && 0 < count($errors)) {
                            logActivity("Application Link Provisioning returned the following warnings: " . implode(", ", $errors));
                        }
                    } catch (WHMCS\Exception $e) {
                        logActivity("Application Link Provisioning Failed: " . $e->getMessage() . " - " . $serviceOrAddon);
                    }
                }
                run_hook("AfterModule" . $function, array("params" => $params));
                WHMCS\Module\Queue::resolve($entityType, $entityId, $entityModule, $modfuncname);
                return "success";
            }
            WHMCS\Module\Queue::add($entityType, $entityId, $entityModule, $modfuncname, $result);
            run_hook("AfterModule" . $function . "Failed", array("failureResponseMessage" => $result, "params" => $params));
        } else {
            $result = "Function Not Supported by Module";
            if ($function == "Renew") {
                return $result;
            }
        }
    }
    logActivity("Module " . $logname . " Failed - " . $serviceOrAddon . " - Error: " . $result, $userid);
    return $result;
}
function ServerCreateAccount($serviceID, $addonId = 0)
{
    $params = modulebuildparams($serviceID, $addonId);
    $updateInfo = array();
    if (array_key_exists("service", $params)) {
        if (!$params["username"]) {
            $params["username"] = $params["service"]["username"];
            $updateInfo["username"] = $params["username"];
        }
        if (!$params["domain"]) {
            $params["domain"] = $params["service"]["domain"];
            $updateInfo["domain"] = $params["domain"];
        }
    }
    if (!$params["username"]) {
        $usernamegenhook = run_hook("OverrideModuleUsernameGeneration", $params);
        $username = "";
        if (count($usernamegenhook)) {
            foreach ($usernamegenhook as $usernameval) {
                if (is_string($usernameval)) {
                    $username = $usernameval;
                }
            }
        }
        if (!$username) {
            $username = createServerUsername($params["domain"]);
        }
        $updateInfo["username"] = $username;
    }
    if (!$params["password"]) {
        $updateInfo["password"] = encrypt(WHMCS\Module\Server::generateRandomPassword());
    }
    $moduleMetaData = array();
    if (function_exists($params["moduletype"] . "_MetaData")) {
        $moduleMetaData = call_user_func($params["moduletype"] . "_MetaData");
    }
    if (!array_key_exists("AutoGenerateUsernameAndPassword", $moduleMetaData) || $moduleMetaData["AutoGenerateUsernameAndPassword"] !== false) {
        if ($updateInfo && !$addonId) {
            update_query("tblhosting", $updateInfo, array("id" => $serviceID));
        } else {
            if ($updateInfo) {
                if (array_key_exists("password", $updateInfo)) {
                    $updateInfo["password"] = decrypt($updateInfo["password"]);
                }
                $params["model"]->serviceProperties->save($updateInfo);
            }
        }
    }
    try {
        return modulecallfunction("Create", $serviceID, array(), $addonId);
    } catch (Exception $e) {
        return $e->getMessage();
    }
}
function ServerSuspendAccount($serviceID, $suspendreason = "", $addonId = 0)
{
    $extraParams = array("suspendreason" => $suspendreason ? $suspendreason : "Overdue on Payment");
    return modulecallfunction("Suspend", $serviceID, $extraParams, $addonId);
}
function ServerUnsuspendAccount($serviceID, $addonId = 0)
{
    return modulecallfunction("Unsuspend", $serviceID, array(), $addonId);
}
function ServerTerminateAccount($serviceID, $addonId = 0)
{
    return modulecallfunction("Terminate", $serviceID, array(), $addonId);
}
function ServerRenew($serviceID, $addonId = 0)
{
    $result = modulecallfunction("Renew", $serviceID, array(), $addonId);
    if ($result == "Function Not Supported by Module") {
        $result = "notsupported";
    }
    return $result;
}
function ServerChangePassword($serviceID, $addonId = 0)
{
    return modulecallfunction("ChangePassword", $serviceID, array(), $addonId);
}
function ServerLoginLink($serviceID, $addonId = 0)
{
    $server = new WHMCS\Module\Server();
    if ($addonId) {
        $server->loadByAddonId($addonId);
    } else {
        $server->loadByServiceID($serviceID);
    }
    if ($server->functionExists("LoginLink")) {
        return $server->call("LoginLink");
    }
    return "";
}
function ServerChangePackage($serviceID, $addonId = 0)
{
    return modulecallfunction("ChangePackage", $serviceID, array(), $addonId);
}
function ServerCustomFunction($serviceID, $func_name, $addonId = 0)
{
    $server = new WHMCS\Module\Server();
    if ($addonId) {
        $server->loadByAddonId($addonId);
    } else {
        $server->loadByServiceID($serviceID);
    }
    return $server->call($func_name, array());
}
function ServerClientArea($serviceID, $addonId = 0)
{
    $server = new WHMCS\Module\Server();
    if ($addonId) {
        $server->loadByAddonId($addonId);
    } else {
        $server->loadByServiceID($serviceID);
    }
    if ($server->functionExists("ClientArea")) {
        return $server->call("ClientArea");
    }
    return "";
}
function ServerUsageUpdate()
{
    $servers = WHMCS\Product\Server::where("disabled", "0")->orderBy("name", "ASC")->get();
    $updatedServerIds = array();
    foreach ($servers as $serverModel) {
        $server = new WHMCS\Module\Server();
        $server->load($serverModel->type);
        if ($server->functionExists("UsageUpdate")) {
            $updatedServerIds[] = $serverModel->id;
            $response = $server->call("UsageUpdate", $server->getServerParams($serverModel));
            if ($response && !in_array($response, array("success", WHMCS\Module\Server::FUNCTIONDOESNTEXIST))) {
                logActivity("Server Usage Update Failed: " . $response . " - Server ID: " . $serverModel->id);
            }
        }
    }
    return $updatedServerIds;
}
function createServerUsername($domain)
{
    global $CONFIG;
    if (!$domain && !$CONFIG["GenerateRandomUsername"]) {
        return "";
    }
    if (!$CONFIG["GenerateRandomUsername"]) {
        $domain = strtolower($domain);
        $username = preg_replace("/[^a-z]/", "", $domain);
        $username = substr($username, 0, 8);
        $result = select_query("tblhosting", "COUNT(*)", array("username" => $username));
        $data = mysql_fetch_array($result);
        $username_exists = $data[0];
        $suffix = 0;
        while (0 < $username_exists) {
            $suffix++;
            $trimlength = 8 - strlen($suffix);
            $username = substr($username, 0, $trimlength) . $suffix;
            $result = select_query("tblhosting", "COUNT(*)", array("username" => $username));
            $data = mysql_fetch_array($result);
            $username_exists = $data[0];
        }
    } else {
        $lowercase = "abcdefghijklmnopqrstuvwxyz";
        $str = "";
        $seeds_count = strlen($lowercase) - 1;
        for ($i = 0; $i < 8; $i++) {
            $str .= $lowercase[rand(0, $seeds_count)];
        }
        $username = "";
        for ($i = 0; $i < 8; $i++) {
            $randomnum = rand(0, strlen($str) - 1);
            $username .= $str[$randomnum];
            $str = substr($str, 0, $randomnum) . substr($str, $randomnum + 1);
        }
        $result = select_query("tblhosting", "COUNT(*)", array("username" => $username));
        $data = mysql_fetch_array($result);
        $username_exists = $data[0];
        while (0 < $username_exists) {
            $username = "";
            $str = "";
            for ($i = 0; $i < 8; $i++) {
                $str .= $lowercase[rand(0, $seeds_count)];
            }
            for ($i = 0; $i < 8; $i++) {
                $randomnum = rand(0, strlen($str) - 1);
                $username .= $str[$randomnum];
                $str = substr($str, 0, $randomnum) . substr($str, $randomnum + 1);
            }
            $result = select_query("tblhosting", "COUNT(*)", array("username" => $username));
            $data = mysql_fetch_array($result);
            $username_exists = $data[0];
        }
    }
    return $username;
}
function createServerPassword()
{
    return WHMCS\Module\Server::generateRandomPassword();
}
function getServerID($servertype, $servergroup)
{
    $serverid = 0;
    $hostingCountsQuery = "(SELECT COUNT(id) FROM tblhosting WHERE tblhosting.server=tblservers.id AND domainstatus IN ('Active', 'Suspended'))";
    $hostingCountsQuery .= "+ (SELECT COUNT(id) FROM tblhostingaddons WHERE tblhostingaddons.server=tblservers.id AND status IN ('Active', 'Suspended'))";
    if ($servertype && !$servergroup) {
        $result = select_query("tblservers", "id,maxaccounts,(" . $hostingCountsQuery . ") AS usagecount", array("type" => $servertype, "active" => "1", "disabled" => "0"));
        $data = mysql_fetch_array($result);
        $serverid = $data["id"];
        $maxaccounts = $data["maxaccounts"];
        $usagecount = $data["usagecount"];
        if ($serverid && $maxaccounts <= $usagecount) {
            $result = full_query("SELECT id,((" . $hostingCountsQuery . ")/maxaccounts) AS percentusage FROM tblservers WHERE type='" . $servertype . "' AND id!='" . $serverid . "' AND disabled=0 ORDER BY percentusage ASC");
            $data = mysql_fetch_array($result);
            if ($data["id"]) {
                $serverid = $data["id"];
                update_query("tblservers", array("active" => ""), array("type" => $servertype));
                update_query("tblservers", array("active" => "1"), array("type" => $servertype, "id" => $serverid));
            }
        }
    } else {
        if ($servertype) {
            $result = select_query("tblservergroups", "filltype", array("id" => $servergroup));
            $data = mysql_fetch_array($result);
            $filltype = $data["filltype"];
            $serverslist = array();
            $result = select_query("tblservergroupsrel", "serverid", array("groupid" => $servergroup));
            while ($data = mysql_fetch_array($result)) {
                $serverslist[] = $data["serverid"];
            }
            if ($filltype == 1) {
                $result = full_query("SELECT id,((" . $hostingCountsQuery . ")/maxaccounts) AS percentusage FROM tblservers WHERE id IN (" . db_build_in_array($serverslist) . ") AND disabled=0 ORDER BY percentusage ASC");
                $data = mysql_fetch_array($result);
                $serverid = $data["id"];
            } else {
                if ($filltype == 2) {
                    $result = select_query("tblservers", "id,maxaccounts,(" . $hostingCountsQuery . ") AS usagecount", "id IN (" . db_build_in_array($serverslist) . ") AND active='1' AND disabled=0");
                    $data = mysql_fetch_array($result);
                    $serverid = $data["id"];
                    $maxaccounts = $data["maxaccounts"];
                    $usagecount = $data["usagecount"];
                    if ($serverid && $maxaccounts <= $usagecount) {
                        $result = full_query("SELECT id,((" . $hostingCountsQuery . ")/maxaccounts) AS percentusage FROM tblservers WHERE id IN (" . db_build_in_array($serverslist) . ") AND disabled=0 AND id!=" . (int) $serverid . " ORDER BY percentusage ASC");
                        $data = mysql_fetch_array($result);
                        if ($data["id"]) {
                            $serverid = $data["id"];
                            update_query("tblservers", array("active" => ""), array("type" => $servertype));
                            update_query("tblservers", array("active" => "1"), array("type" => $servertype, "id" => $serverid));
                        }
                    }
                }
            }
        }
    }
    return $serverid;
}
function rebuildModuleHookCache()
{
    $hooksarray = array();
    $inUseProvisioningModules = WHMCS\Product\Product::distinct("servertype")->pluck("servertype")->all();
    $inUseProvisioningModules = array_merge(WHMCS\Product\Addon::distinct("module")->pluck("module")->all(), $inUseProvisioningModules);
    $server = new WHMCS\Module\Server();
    foreach ($server->getList() as $module) {
        if (in_array($module, $inUseProvisioningModules) && is_file(ROOTDIR . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . "servers" . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . "hooks.php")) {
            $hooksarray[] = $module;
        }
    }
    WHMCS\Config\Setting::setValue("ModuleHooks", implode(",", $hooksarray));
}
function rebuildAddonHookCache()
{
    $hooksarray = array();
    $inUseAddonModules = WHMCS\Database\Capsule::table("tbladdonmodules")->distinct()->pluck("module");
    foreach ($inUseAddonModules as $module) {
        if (is_file(ROOTDIR . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . "addons" . DIRECTORY_SEPARATOR . $module . DIRECTORY_SEPARATOR . "hooks.php")) {
            $hooksarray[] = $module;
        }
    }
    WHMCS\Config\Setting::setValue("AddonModulesHooks", implode(",", $hooksarray));
}
function moduleConfigFieldOutput($values)
{
    if (is_null($values["Value"])) {
        $values["Value"] = isset($values["Default"]) ? $values["Default"] : "";
    }
    if (empty($values["Size"])) {
        $values["Size"] = 40;
    }
    $inputClass = "input-";
    switch (true) {
        case $values["Size"] <= 10:
            $inputClass .= "100";
            break;
        case $values["Size"] <= 20:
            $inputClass .= "200";
            break;
        case $values["Size"] <= 30:
            $inputClass .= "300";
            break;
        default:
            $inputClass .= "400";
            break;
    }
    switch ($values["Type"]) {
        case "text":
            $code = "<input type=\"text\" name=\"" . $values["Name"] . "\" class=\"form-control input-inline " . $inputClass . "\" value=\"" . WHMCS\Input\Sanitize::encode($values["Value"]) . "\"" . (isset($values["Placeholder"]) ? " placeholder=\"" . $values["Placeholder"] . "\"" : "") . (!empty($values["Disabled"]) ? " disabled" : "") . (!empty($values["ReadOnly"]) ? " readonly=\"readonly\"" : "") . " />";
            if (isset($values["Description"])) {
                $code .= " " . $values["Description"];
            }
            break;
        case "password":
            $code = "<input type=\"password\" autocomplete=\"off\" name=\"" . $values["Name"] . "\" class=\"form-control input-inline " . $inputClass . "\" value=\"" . replacePasswordWithMasks($values["Value"]) . "\"" . (!empty($values["ReadOnly"]) ? " readonly=\"readonly\"" : "") . " />";
            if (isset($values["Description"])) {
                $code .= " " . $values["Description"];
            }
            break;
        case "yesno":
            $code = "<label class=\"checkbox-inline\"><input type=\"hidden\" name=\"" . $values["Name"] . "\" value=\"\">" . "<input type=\"checkbox\" name=\"" . $values["Name"] . "\"";
            if (!empty($values["Value"])) {
                $code .= " checked=\"checked\"";
            }
            $code .= " /> " . (isset($values["Description"]) ? $values["Description"] : "&nbsp") . "</label>";
            break;
        case "dropdown":
            $code = "<select name=\"" . $values["Name"];
            if (isset($values["Multiple"])) {
                $size = isset($values["Size"]) && is_numeric($values["Size"]) ? $values["Size"] : 3;
                $code .= "[]\" multiple=\"true\" size=\"" . $size . "\"";
                $selectedKeys = json_decode($values["Value"]);
            } else {
                $code .= "\"";
                $selectedKeys = array($values["Value"]);
            }
            $code .= " class=\"form-control select-inline\"" . (!empty($values["ReadOnly"]) ? " readonly=\"readonly\"" : "") . ">";
            $dropdownOptions = $values["Options"];
            if (is_array($dropdownOptions)) {
                foreach ($dropdownOptions as $key => $value) {
                    $code .= "<option value=\"" . $key . "\"";
                    if (in_array($key, $selectedKeys)) {
                        $code .= " selected=\"selected\"";
                    }
                    $code .= ">" . $value . "</option>";
                }
            } else {
                $dropdownOptions = explode(",", $dropdownOptions);
                foreach ($dropdownOptions as $value) {
                    $code .= "<option value=\"" . $value . "\"";
                    if (in_array($value, $selectedKeys)) {
                        $code .= " selected=\"selected\"";
                    }
                    $code .= ">" . $value . "</option>";
                }
            }
            $code .= "</select>";
            if (isset($values["Description"])) {
                $code .= " " . $values["Description"];
            }
            break;
        case "radio":
            $code = "";
            if (isset($values["Description"])) {
                $code .= $values["Description"] . "<br />";
            }
            $options = $values["Options"];
            if (!is_array($options)) {
                $options = explode(",", $options);
            }
            if (!isset($values["Value"])) {
                $values["Value"] = $options[0];
            }
            foreach ($options as $value) {
                $code .= "<label class=\"radio-inline\"><input type=\"radio\" name=\"" . $values["Name"] . "\" value=\"" . $value . "\"";
                if ($values["Value"] == $value) {
                    $code .= " checked=\"checked\"";
                }
                $code .= " /> " . $value . "</label><br />";
            }
            break;
        case "textarea":
            $cols = isset($values["Cols"]) ? $values["Cols"] : "60";
            $rows = isset($values["Rows"]) ? $values["Rows"] : "5";
            $code = "<textarea class=\"form-control\" name=\"" . $values["Name"] . "\" cols=\"" . $cols . "\" rows=\"" . $rows . "\"" . (!empty($values["ReadOnly"]) ? " readonly=\"readonly\"" : "") . ">" . WHMCS\Input\Sanitize::encode($values["Value"]) . "</textarea>";
            if (isset($values["Description"])) {
                $code .= $values["Description"];
            }
            break;
        default:
            $code = $values["Description"];
    }
    return $code;
}

?>