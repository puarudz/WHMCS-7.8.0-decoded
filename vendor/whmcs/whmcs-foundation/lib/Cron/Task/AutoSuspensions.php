<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Task;

class AutoSuspensions extends \WHMCS\Scheduling\Task\AbstractTask
{
    public $description = "Processing Overdue Suspensions";
    protected $defaultPriority = 1580;
    protected $defaultFrequency = 1440;
    protected $defaultDescription = "Process Overdue Suspensions";
    protected $defaultName = "Overdue Suspensions";
    protected $systemName = "AutoSuspensions";
    protected $outputs = array("suspended" => array("defaultValue" => 0, "identifier" => "unpaid", "name" => "Overdue Suspended"), "manual" => array("defaultValue" => 0, "identifier" => "manual", "name" => "Manual Suspension Required"));
    protected $icon = "fas fa-bell";
    protected $successCountIdentifier = "suspended";
    protected $failureCountIdentifier = "manual";
    protected $successKeyword = "Suspended";
    public function __invoke()
    {
        $successfulSuspensions = 0;
        $manualSuspensionRequired = 0;
        if (!\WHMCS\Config\Setting::getValue("AutoSuspension")) {
            $this->output("suspended")->write($successfulSuspensions);
            $this->output("manual")->write($manualSuspensionRequired);
            return true;
        }
        update_query("tblhosting", array("overideautosuspend" => ""), "overideautosuspend='1' AND overidesuspenduntil<'" . date("Y-m-d") . "' AND overidesuspenduntil!='0000-00-00'");
        $clientGroups = \WHMCS\Database\Capsule::table("tblclientgroups")->pluck("susptermexempt", "id");
        $clients = array();
        $i = 0;
        $suspenddate = date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - \WHMCS\Config\Setting::getValue("AutoSuspensionDays"), date("Y")));
        $query3 = "SELECT * FROM tblhosting" . " WHERE domainstatus = 'Active'" . " AND billingcycle != 'Free Account'" . " AND billingcycle != 'Free'" . " AND billingcycle != 'One Time'" . " AND overideautosuspend != '1'" . " AND nextduedate <= '" . $suspenddate . "'" . " ORDER BY domain ASC";
        $result3 = full_query($query3);
        while ($data = mysql_fetch_array($result3)) {
            $id = $data["id"];
            $userid = $data["userid"];
            $domain = $data["domain"];
            $packageid = $data["packageid"];
            $nextDueDate = $data["nextduedate"];
            if (!array_key_exists($userid, $clients)) {
                $client = \WHMCS\Database\Capsule::table("tblclients")->where("id", $userid)->first(array("firstname", "lastname", "groupid"));
                if (!$client) {
                    continue;
                }
                $clients[$userid] = array("firstname" => $client->firstname, "lastname" => $client->lastname, "groupid" => $client->groupid);
            }
            $firstname = $clients[$userid]["firstname"];
            $lastname = $clients[$userid]["lastname"];
            $groupid = $clients[$userid]["groupid"];
            $result2 = select_query("tblproducts", "tblproducts.name, tblproducts.servertype, tblhosting.nextduedate", array("tblproducts.id" => $packageid, "tblhosting.id" => $id), "", "", "", "tblhosting on tblproducts.id = tblhosting.packageid");
            $data2 = mysql_fetch_array($result2);
            $prodname = $data2["name"];
            $module = $data2["servertype"];
            $nextDueDate2 = $data2["nextduedate"];
            $susptermexempt = 0;
            if ($groupid) {
                $susptermexempt = $clientGroups[$groupid];
            }
            if ($susptermexempt) {
                continue;
            }
            $serverresult = "No Module";
            logActivity("Cron Job: Suspending Service - Service ID: " . $id);
            if ($module) {
                if ($nextDueDate != $nextDueDate2) {
                    continue;
                }
                $serverresult = ServerSuspendAccount($id);
            }
            if ($domain) {
                $domain = " - " . $domain;
            }
            $loginfo = sprintf("%s%s - %s %s (Service ID: %s - User ID: %s)", $prodname, $domain, $firstname, $lastname, $id, $userid);
            if ($serverresult == "success") {
                sendMessage("Service Suspension Notification", $id);
                $msg = "SUCCESS: " . $loginfo;
                $successfulSuspensions++;
                $i++;
            } else {
                $msg = sprintf("ERROR: Manual Suspension Required - %s - %s", $serverresult, $loginfo);
                $manualSuspensionRequired++;
            }
            logActivity("Cron Job: " . $msg);
        }
        $addons = \WHMCS\Service\Addon::whereHas("service", function ($query) {
            $query->where("overideautosuspend", "!=", 1);
        })->with("client", "productAddon", "service", "service.product")->where("status", "=", "Active")->whereNotIn("billingcycle", array("Free", "Free Account", "One Time", "onetime"))->where("nextduedate", "<=", $suspenddate)->get();
        foreach ($addons as $addon) {
            if (!$addon->service) {
                continue;
            }
            $suspendTerminateExempt = 0;
            if ($addon->client->groupId) {
                $suspendTerminateExempt = $clientGroups[$addon->client->groupId];
            }
            if ($suspendTerminateExempt) {
                continue;
            }
            $id = $addon->id;
            $serviceId = $addon->serviceId;
            $addonId = $addon->addonId;
            $name = $addon->name;
            $userId = $addon->clientId;
            $domain = $addon->service->domain;
            $firstName = $addon->client->firstName;
            $lastName = $addon->client->lastName;
            if (!$name && $addonId) {
                $name = $addon->productAddon->name;
            }
            $noModule = true;
            $automationResult = false;
            $automation = null;
            if ($addon->productAddon->module) {
                $automation = \WHMCS\Service\Automation\AddonAutomation::factory($addon);
                $automationResult = $automation->runAction("SuspendAccount", "");
                $noModule = false;
            } else {
                $addon->status = "Suspended";
                $addon->save();
            }
            if ($noModule || $automationResult) {
                $logInfo = sprintf("%s - %s %s (Service ID: %d - Addon ID: %d)", $name, $firstName, $lastName, $serviceId, $id);
                $msg = "SUCCESS: " . $logInfo;
                logActivity("Cron Job: " . $msg);
                $successfulSuspensions++;
                if (!$noModule) {
                    run_hook("AddonSuspended", array("id" => $id, "userid" => $userId, "serviceid" => $serviceId, "addonid" => $addonId));
                }
                if ($addonId && $addon->productAddon->suspendProduct) {
                    $productName = $addon->service->product->name;
                    $module = $addon->service->product->module;
                    $serverResult = "No Module";
                    logActivity("Cron Job: Suspending Parent Service - Service ID: " . $serviceId);
                    if ($module) {
                        $serverResult = ServerSuspendAccount($serviceId, "Parent Service Suspended due to Overdue Addon");
                    }
                    if ($domain) {
                        $domain = " - " . $domain;
                    }
                    $logInfo = sprintf("%s %s - %s%s (Service ID: %d - User ID: %d)", $firstName, $lastName, $productName, $domain, $serviceId, $userId);
                    if ($serverResult == "success") {
                        sendMessage("Service Suspension Notification", $serviceId);
                        $msg = "SUCCESS: " . $logInfo;
                        $successfulSuspensions++;
                    } else {
                        $msg = sprintf("ERROR: Manual Parent Service Suspension Required - %s - %s", $serverResult, $logInfo);
                        $manualSuspensionRequired++;
                    }
                    logActivity("Cron Job: " . $msg);
                }
            }
            $i++;
        }
        $this->output("suspended")->write($successfulSuspensions);
        $this->output("manual")->write($manualSuspensionRequired);
        return $this;
    }
}

?>