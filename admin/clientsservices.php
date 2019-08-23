<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("View Clients Products/Services");
$aInt->requiredFiles(array("clientfunctions", "gatewayfunctions", "modulefunctions", "customfieldfunctions", "configoptionsfunctions", "invoicefunctions", "processinvoices"));
$aInt->setClientsProfilePresets();
$id = (int) $whmcs->get_req_var("id");
$hostingid = (int) $whmcs->get_req_var("hostingid");
$userid = (int) $whmcs->get_req_var("userid");
$aid = $whmcs->get_req_var("aid");
$action = $whmcs->get_req_var("action");
$modop = $whmcs->get_req_var("modop");
$server = $whmcs->get_req_var("server");
if ($whmcs->getFromRequest("productselect")) {
    if (substr($whmcs->getFromRequest("productselect"), 0, 1) == "a") {
        $aid = (int) substr($whmcs->getFromRequest("productselect"), 1);
    } else {
        $id = (int) $whmcs->getFromRequest("productselect");
    }
}
$errors = array();
$jQueryCode = "";
if ($modop) {
    checkPermission("Perform Server Operations");
    define("NO_QUEUE", true);
}
if (!$id && $hostingid) {
    $id = $hostingid;
}
if (!$id && $aid) {
    $addon = WHMCS\Service\Addon::with("service")->find($aid);
    if ($addon) {
        $id = $addon->serviceId;
        if (!$addon->clientId) {
            $addon->clientId = $addon->service->clientId;
            $addon->save();
        }
    }
}
if (!$userid && !$id) {
    $userid = get_query_val("tblclients", "id", "", "id", "ASC", "0,1");
}
if ($userid && !$id) {
    $aInt->valUserID($userid);
    if (!$userid) {
        $aInt->gracefulExit("Invalid User ID");
    }
    $id = get_query_val("tblhosting", "id", array("userid" => $userid), "domain", "ASC", "0,1");
}
if (!$id) {
    $aInt->gracefulExit($aInt->lang("services", "noproductsinfo") . " <a href=\"ordersadd.php?userid=" . $userid . "\">" . $aInt->lang("global", "clickhere") . "</a> " . $aInt->lang("orders", "toplacenew"));
}
$result = select_query("tblhosting", "tblhosting.*,tblproducts.servertype,tblproducts.type, tblproducts.welcomeemail", array("tblhosting.id" => $id), "", "", "", "tblproducts ON tblproducts.id=tblhosting.packageid");
$service_data = mysql_fetch_array($result);
$id = $service_data["id"];
if (!$id) {
    $aInt->gracefulExit("Service ID Not Found");
}
if ($service_data["userid"] != $userid) {
    $userid = $service_data["userid"];
    $aInt->valUserID($userid);
}
$aInt->setClientsProfilePresets($userid);
$aInt->assertClientBoundary($userid);
$producttype = $service_data["type"];
$module = $service_data["servertype"];
$orderid = $service_data["orderid"];
$packageid = $service_data["packageid"];
$server = $service_data["server"];
$regdate = $service_data["regdate"];
$terminationDate = $service_data["termination_date"];
$completedDate = $service_data["completed_date"];
$domain = $service_data["domain"];
$paymentmethod = $service_data["paymentmethod"];
$createServerOptionForNone = false;
$serverModule = new WHMCS\Module\Server();
if ($aid) {
    $serverModule->setAddonId($aid);
} else {
    $serverModule->setServiceId($id);
}
if ($module && !$aid) {
    if ($serverModule->load($module)) {
        if ($serverModule->isMetaDataValueSet("RequiresServer") && !$serverModule->getMetaDataValue("RequiresServer")) {
            $createServerOptionForNone = true;
        }
    } else {
        logActivity("Required Product Module '" . $serverModule->getServiceModule() . "' Missing - Service ID: " . $id, $userid);
    }
}
$gateways = new WHMCS\Gateways();
if (!$paymentmethod || !$gateways->isActiveGateway($paymentmethod)) {
    $paymentmethod = ensurePaymentMethodIsSet($userid, $id, "tblhosting");
}
$firstpaymentamount = $service_data["firstpaymentamount"];
$amount = $service_data["amount"];
$billingcycle = $serviceBillingCycle = $service_data["billingcycle"];
$nextduedate = $service_data["nextduedate"];
$domainstatus = $service_data["domainstatus"];
$username = $service_data["username"];
$password = decrypt($service_data["password"]);
$notes = $service_data["notes"];
$subscriptionid = $service_data["subscriptionid"];
$promoid = $service_data["promoid"];
$suspendreason = $service_data["suspendreason"];
$overideautosuspend = $service_data["overideautosuspend"];
$ns1 = $service_data["ns1"];
$ns2 = $service_data["ns2"];
$dedicatedip = $service_data["dedicatedip"];
$assignedips = $service_data["assignedips"];
$diskusage = $service_data["diskusage"];
$disklimit = $service_data["disklimit"];
$bwusage = $service_data["bwusage"];
$bwlimit = $service_data["bwlimit"];
$lastupdate = $service_data["lastupdate"];
$overidesuspenduntil = $service_data["overidesuspenduntil"];
$welcomeEmail = $service_data["welcomeemail"];
$addonModule = "";
$addonDetails = NULL;
if ($aid && is_numeric($aid)) {
    try {
        $addonDetails = WHMCS\Service\Addon::with("productAddon", "service")->where("id", "=", $aid)->whereIn("userid", array(0, $userid))->firstOrFail();
        if (!$addonDetails->clientId) {
            $addonDetails->clientId = $addonDetails->service->clientId;
            $addonDetails->save();
        }
    } catch (Exception $e) {
        redir("userid=" . $userid . "&id=" . $id);
    }
    $addonModule = $addonDetails->productAddon->module;
}
$frm = new WHMCS\Form();
$adminServicesTabFieldsSaveErrors = NULL;
if ($frm->issubmitted()) {
    checkPermission("Edit Clients Products/Services");
    $packageid = $whmcs->get_req_var("packageid");
    $oldserviceid = $whmcs->get_req_var("oldserviceid");
    $addonid = $whmcs->get_req_var("addonid");
    $name = $whmcs->get_req_var("name");
    $setupfee = $whmcs->get_req_var("setupfee");
    $recurring = $whmcs->get_req_var("recurring");
    $billingcycle = $whmcs->get_req_var("billingcycle");
    $status = $whmcs->get_req_var("domainstatus");
    $regdate = $whmcs->get_req_var("regdate");
    $terminationDate = $whmcs->get_req_var("termination_date");
    $oldnextduedate = $whmcs->get_req_var("oldnextduedate");
    $nextduedate = $whmcs->get_req_var("nextduedate");
    $overidesuspenduntil = $whmcs->get_req_var("overidesuspenduntil");
    $paymentmethod = $whmcs->get_req_var("paymentmethod");
    $tax = $whmcs->get_req_var("tax");
    $promoid = $whmcs->get_req_var("promoid");
    $notes = $whmcs->get_req_var("notes");
    $configoption = $whmcs->get_req_var("configoption");
    $server = $whmcs->get_req_var("server");
    $terminationDateValid = true;
    $queryStr = "userid=" . $userid . "&id=" . $id;
    if (is_string($terminationDate) && trim($terminationDate) == "") {
        $terminationDate = preg_replace("/[MDY]/i", "0", WHMCS\Config\Setting::getValue("DateFormat"));
    }
    if (is_string($overidesuspenduntil) && trim($overidesuspenduntil) == "") {
        $overidesuspenduntil = preg_replace("/[MDY]/i", "0", WHMCS\Config\Setting::getValue("DateFormat"));
    }
    if ($aid) {
        if ($billingcycle == "Free" || $billingcycle == "Free Account") {
            $setupfee = $recurring = 0;
            $nextduedate = fromMySQLDate("0000-00-00");
        }
        if (is_numeric($aid)) {
            $status = $whmcs->get_req_var("status");
            try {
                $addonDetails = WHMCS\Service\Addon::where("id", "=", $aid)->where("userid", "=", $userid)->firstOrFail();
                $queryStr .= "&aid=" . $aid;
            } catch (Exception $e) {
                redir($queryStr);
            }
            $oldStatus = $addonDetails->status;
            $oldAddonId = $addonDetails->addonId;
            if (!in_array(toMySQLDate($terminationDate), array("0000-00-00", "1970-01-01")) && !in_array($status, array("Terminated", "Cancelled")) && !in_array($addonDetails->status, array("Terminated", "Cancelled"))) {
                $terminationDateValid = false;
                $queryStr .= "&terminationdateinvalid=1";
            }
            if (in_array($status, array("Terminated", "Cancelled")) && in_array(toMySQLDate($terminationDate), array("0000-00-00", "1970-01-01"))) {
                $terminationDate = fromMySQLDate(date("Y-m-d"));
            } else {
                if (!in_array($status, array("Terminated", "Cancelled")) && !in_array(toMySQLDate($terminationDate), array("0000-00-00", "1970-01-01"))) {
                    $terminationDate = fromMySQLDate("0000-00-00");
                }
            }
            $changelog = array();
            $forceServerReset = false;
            $newAddon = NULL;
            $newServer = 0;
            if ($id != $addonDetails->serviceId) {
                $changelog[] = "Transferred Addon from Service ID: " . $addonDetails->serviceId . " to Service ID: " . $id;
                $addonDetails->serviceId = $id;
            }
            if ($addonid != $addonDetails->addonId) {
                $addonsCollections = WHMCS\Product\Addon::whereIn("id", array($addonid, $addonDetails->addonId))->get();
                $addonModules = array();
                foreach ($addonsCollections as $addonsCollection) {
                    $addonModules[$addonsCollection->id] = $addonsCollection;
                }
                $oldServerModule = "";
                $newServerModule = "";
                if ($addonDetails->addonId) {
                    $oldServerModule = $addonModules[$addonDetails->addonId]->servertype;
                }
                if ($addonid) {
                    $newServerModule = $addonModules[$addonid]->servertype;
                }
                if ($oldServerModule != $newServerModule) {
                    $forceServerReset = true;
                    $newAddon = $addonModules[$addonid];
                }
                unset($addonModules);
                $changelog[] = "Addon Id changed from " . $addonDetails->addonId . " to " . $addonid;
                $addonDetails->addonId = $addonid;
            }
            if ($addonDetails->name != $name) {
                $changelog[] = "Addon Name changed from " . $addonDetails->name . " to " . $name;
                $addonDetails->name = $name;
            }
            if ($addonDetails->setupFee != $setupfee) {
                $changelog[] = "Setup Fee changed from " . $addonDetails->setupFee . " to " . $setupfee;
                $addonDetails->setupFee = $setupfee;
            }
            if ($addonDetails->recurringFee != $recurring) {
                $changelog[] = "Recurring Fee changed from " . $addonDetails->recurringFee . " to " . $recurring;
                $addonDetails->recurringFee = $recurring;
            }
            if ($addonDetails->billingCycle != $billingcycle) {
                $changelog[] = "Billing Cycle changed from " . $addonDetails->billingCycle . " to " . $billingcycle;
                $addonDetails->billingCycle = $billingcycle;
            }
            if ($addonDetails->status != $status) {
                $changelog[] = "Status changed from " . $addonDetails->status . " to " . $status;
                $addonDetails->status = $status;
            }
            if (fromMySQLDate($addonDetails->registrationDate) != $regdate) {
                $changelog[] = "Registration Date changed from " . fromMySQLDate($addonDetails->registrationDate) . " to " . $regdate;
                $addonDetails->registrationDate = toMySQLDate($regdate);
            }
            if (fromMySQLDate($addonDetails->nextDueDate) != $nextduedate) {
                $changelog[] = "Next Due Date changed from " . fromMySQLDate($addonDetails->nextDueDate) . " to " . $nextduedate;
                $addonDetails->nextDueDate = toMySQLDate($nextduedate);
                $addonDetails->nextInvoiceDate = toMySQLDate($nextduedate);
            }
            if (fromMySQLDate($addonDetails->terminationDate) != $terminationDate) {
                $changelog[] = "Termination Date changed from " . fromMySQLDate($addonDetails->terminationDate) . " to " . $terminationDate;
                $addonDetails->terminationDate = toMySQLDate($terminationDate);
            }
            if ($addonDetails->paymentGateway != $paymentmethod) {
                $changelog[] = "Payment Gateway changed from " . $addonDetails->paymentGateway . " to " . $paymentmethod;
                $addonDetails->paymentGateway = $paymentmethod;
            }
            if ($addonDetails->applyTax != (int) $tax) {
                $taxEnabledDisabled = "Disabled";
                if ($tax) {
                    $taxEnabledDisabled = "Enabled";
                }
                $changelog[] = "Tax " . $taxEnabledDisabled;
                $addonDetails->applyTax = (int) $tax;
            }
            if ($addonDetails->notes != $notes) {
                $changelog[] = "Addon Notes changed";
                $addonDetails->notes = $notes;
            }
            if ($forceServerReset) {
                $server = getServerID($newAddon->module, $newAddon->serverGroupId);
                $changelog[] = "Server Id automatically changed from " . $addonDetails->serverId . " to " . $server;
                $addonDetails->serverId = $server;
            } else {
                if ($addonDetails->serverId != $server) {
                    $changelog[] = "Server Id changed from " . $addonDetails->serverId . " to " . $server;
                    $addonDetails->serverId = $server;
                }
            }
            migrateCustomFieldsBetweenProductsOrAddons($aid, $addonid, $oldAddonId, true, true);
            if ($changelog) {
                $addonDetails->save();
                logActivity("Modified Addon - " . implode(", ", $changelog) . " - User ID: " . $userid . " - Addon ID: " . $aid, $userid);
            }
            $moduleInterface = new WHMCS\Module\Server();
            $moduleInterface->loadByAddonId($aid);
            if ($moduleInterface->functionExists("AdminServicesTabFieldsSave")) {
                $moduleParams = $moduleInterface->buildParams();
                $adminServicesTabFieldsSaveErrors = $moduleInterface->call("AdminServicesTabFieldsSave", $moduleParams);
                if ($adminServicesTabFieldsSaveErrors && !is_array($adminServicesTabFieldsSaveErrors) && $adminServicesTabFieldsSaveErrors != "success") {
                    WHMCS\Session::set("adminServicesTabFieldsSaveErrors", $adminServicesTabFieldsSaveErrors);
                }
            }
            run_hook("AdminClientServicesTabFieldsSave", $_REQUEST);
            if ($oldStatus == "Suspended" && $status == "Active") {
                run_hook("AddonUnsuspended", array("id" => $aid, "userid" => $userid, "serviceid" => $id, "addonid" => $addonid));
            } else {
                if ($oldStatus != "Active" && $status == "Active") {
                    run_hook("AddonActivated", array("id" => $aid, "userid" => $userid, "serviceid" => $id, "addonid" => $addonid));
                } else {
                    if ($oldStatus != "Suspended" && $status == "Suspended") {
                        run_hook("AddonSuspended", array("id" => $aid, "userid" => $userid, "serviceid" => $id, "addonid" => $addonid));
                    } else {
                        if ($oldStatus != "Terminated" && $status == "Terminated") {
                            run_hook("AddonTerminated", array("id" => $aid, "userid" => $userid, "serviceid" => $id, "addonid" => $addonid));
                        } else {
                            if ($oldStatus != "Cancelled" && $status == "Cancelled") {
                                run_hook("AddonCancelled", array("id" => $aid, "userid" => $userid, "serviceid" => $id, "addonid" => $addonid));
                            } else {
                                if ($oldStatus != "Fraud" && $status == "Fraud") {
                                    run_hook("AddonFraud", array("id" => $aid, "userid" => $userid, "serviceid" => $id, "addonid" => $addonid));
                                } else {
                                    run_hook("AddonEdit", array("id" => $aid, "userid" => $userid, "serviceid" => $id, "addonid" => $addonid));
                                }
                            }
                        }
                    }
                }
            }
        } else {
            checkPermission("Add New Order");
            $predefname = "";
            $geninvoice = $whmcs->getFromRequest("geninvoice");
            if ($addonid) {
                $productAddon = WHMCS\Product\Addon::find($addonid);
                $addonid = $productAddon->id;
                $predefname = $productAddon->name;
                $tax = $productAddon->applyTax;
                if ($whmcs->get_req_var("defaultpricing")) {
                    $availableCycleTypes = $productAddon->billingCycle;
                    $currency = getCurrency($userid);
                    $pricing = new WHMCS\Pricing();
                    $pricing->loadPricing("addon", $addonid, $currency);
                    switch ($availableCycleTypes) {
                        case "recurring":
                            $availableCycles = $pricing->getAvailableBillingCycles();
                            $billingcycle = (new WHMCS\Billing\Cycles())->getNormalisedBillingCycle($billingcycle);
                            if (!in_array($billingcycle, $availableCycles)) {
                                $billingcycle = $pricing->getFirstAvailableCycle();
                            }
                            $setupfee = $pricing->getSetup($billingcycle);
                            $recurring = $pricing->getPrice($billingcycle);
                            $billingcycle = (new WHMCS\Billing\Cycles())->getPublicBillingCycle($billingcycle);
                            break;
                        case "free":
                            $billingcycle = WHMCS\Billing\Cycles::DISPLAY_FREE;
                            $setupfee = $recurring = 0;
                            break;
                        case "onetime":
                            $billingCycle = WHMCS\Billing\Cycles::DISPLAY_ONETIME;
                            $setupfee = $pricing->getSetup("monthly");
                            $recurring = $pricing->getPrice("monthly");
                            break;
                        default:
                            $billingcycle = $availableCycleTypes;
                            $setupfee = $pricing->getSetup("monthly");
                            $recurring = $pricing->getPrice("monthly");
                    }
                }
            }
            $status = $whmcs->get_req_var("status");
            $newAddon = new WHMCS\Service\Addon();
            $newAddon->serviceId = $id;
            $newAddon->addonId = $addonid;
            $newAddon->clientId = $userid;
            $newAddon->name = $name;
            $newAddon->setupFee = $setupfee;
            $newAddon->recurringFee = $recurring;
            $newAddon->billingCycle = $billingcycle;
            $newAddon->status = $status;
            $newAddon->registrationDate = toMySQLDate($regdate);
            $newAddon->nextDueDate = toMySQLDate($nextduedate);
            $newAddon->nextInvoiceDate = toMySQLDate($nextduedate);
            $newAddon->terminationDate = in_array($status, array("Terminated", "Cancelled")) ? date("Y-m-d") : "0000-00-00";
            $newAddon->paymentGateway = $paymentmethod;
            $newAddon->applyTax = (int) $tax;
            $newAddon->notes = $notes;
            $newAddon->save();
            $newaddonid = $newAddon->id;
            logActivity("Added New Addon - " . $name . $predefname . " - Addon ID: " . $newaddonid . " - Service ID: " . $id, $userid);
            if ($geninvoice) {
                $invoiceid = createInvoices($userid, "", "", array("addons" => array($newaddonid)));
            }
            run_hook("AddonAdd", array("id" => $newaddonid, "userid" => $userid, "serviceid" => $id, "addonid" => $addonid));
        }
        if ($terminationDateValid) {
            $queryStr .= "&success=true";
        }
        redir($queryStr);
    } else {
        if (toMySQLDate($terminationDate) != "0000-00-00" && !in_array($status, array("Terminated", "Cancelled"))) {
            $oldstatus = $service_data["domainstatus"];
            if (!in_array($oldstatus, array("Terminated", "Cancelled"))) {
                $terminationDateValid = false;
                $queryStr .= "&terminationdateinvalid=1";
            }
        }
    }
    if (!$whmcs->get_req_var("packageid") && !$whmcs->get_req_var("billingcycle")) {
        redir($queryStr);
    }
    $currency = getCurrency($userid);
    run_hook("PreServiceEdit", array("serviceid" => $id));
    run_hook("PreAdminServiceEdit", array("serviceid" => $id));
    $configoptions = getCartConfigOptions($packageid, $configoption, $billingcycle);
    $configoptionsrecurring = 0;
    foreach ($configoptions as $configoption) {
        $configoptionsrecurring += $configoption["selectedrecurring"];
        $result = select_query("tblhostingconfigoptions", "COUNT(*)", array("relid" => $id, "configid" => $configoption["id"]));
        $data = mysql_fetch_array($result);
        if (!$data[0]) {
            insert_query("tblhostingconfigoptions", array("relid" => $id, "configid" => $configoption["id"]));
        }
        update_query("tblhostingconfigoptions", array("optionid" => $configoption["selectedvalue"], "qty" => $configoption["selectedqty"]), array("relid" => $id, "configid" => $configoption["id"]));
    }
    $newamount = $autorecalcrecurringprice ? recalcRecurringProductPrice($id, $userid, $packageid, $billingcycle, $configoptionsrecurring, $promoid) : "-1";
    migrateCustomFieldsBetweenProductsOrAddons($id, $packageid, $service_data["packageid"], true);
    $changelog = array();
    $logchangefields = array("regdate" => "Registration Date", "packageid" => "Product/Service", "server" => "Server", "domain" => "Domain", "dedicatedip" => "Dedicated IP", "paymentmethod" => "Payment Method", "firstpaymentamount" => "First Payment Amount", "amount" => "Recurring Amount", "billingcycle" => "Billing Cycle", "nextduedate" => "Next Due Date", "domainstatus" => "Status", "termination_date" => "Termination Date", "username" => "Username", "password" => "Password", "subscriptionid" => "Subscription ID", "overidesuspenduntil" => "Override Auto Suspend Until Date");
    $forceServerReset = false;
    $newProduct = NULL;
    $newServer = 0;
    foreach ($logchangefields as $fieldname => $displayname) {
        $newval = $whmcs->get_req_var($fieldname);
        $oldval = $service_data[$fieldname];
        if (($fieldname == "nextduedate" || $fieldname == "overidesuspenduntil" || $fieldname == "termination_date") && !$newval) {
            $newval = "0000-00-00";
        } else {
            if ($fieldname == "regdate" || $fieldname == "nextduedate" || $fieldname == "overidesuspenduntil" || $fieldname == "termination_date") {
                $newval = toMySQLDate($newval);
            } else {
                if ($fieldname == "password") {
                    if ($newval != decrypt($oldval)) {
                        $changelog[] = $displayname . " changed";
                    }
                    continue;
                }
                if ($fieldname == "amount" && 0 <= $newamount) {
                    $newval = $newamount;
                } else {
                    if ($fieldname == "packageid" && $newval != $oldval) {
                        $productsCollections = WHMCS\Product\Product::whereIn("id", array($newval, $oldval))->get();
                        $productModules = array();
                        foreach ($productsCollections as $productsCollection) {
                            $productModules[$productsCollection->id] = $productsCollection;
                        }
                        if ($productModules[$newval]->servertype != $productModules[$oldval]->servertype) {
                            $forceServerReset = true;
                            $newProduct = $productModules[$newval];
                        }
                        unset($productModules);
                    } else {
                        if ($fieldname == "server" && $forceServerReset) {
                            $newval = getServerID($newProduct->module, $newProduct->serverGroupId);
                            $newServer = $newval;
                        }
                    }
                }
            }
        }
        if ($newval != $oldval) {
            $changelog[] = $displayname . " changed from " . $oldval . " to " . $newval;
        }
    }
    $updatearr = array();
    $updatefields = array("server", "packageid", "domain", "paymentmethod", "firstpaymentamount", "amount", "billingcycle", "regdate", "nextduedate", "username", "password", "notes", "subscriptionid", "promoid", "overideautosuspend", "overidesuspenduntil", "ns1", "ns2", "domainstatus", "termination_date", "dedicatedip", "assignedips");
    foreach ($updatefields as $fieldname) {
        $newval = $whmcs->get_req_var($fieldname);
        if (in_array($fieldname, array("termination_date", "overidesuspenduntil")) && is_string($newval) && trim($newval) == "") {
            $newval = preg_replace("/[MDY]/i", "0", WHMCS\Config\Setting::getValue("DateFormat"));
        }
        if ($fieldname == "domainstatus" && $newval == "Completed" && $service_data["domainstatus"] != "Completed") {
            $updatearr["completed_date"] = WHMCS\Carbon::today()->toDateString();
        }
        if ($fieldname == "regdate" || $fieldname == "nextduedate" || $fieldname == "overidesuspenduntil" || $fieldname == "termination_date") {
            if ($fieldname == "nextduedate" && in_array($billingcycle, array("Free Account", "One Time"))) {
                $newval = "0000-00-00";
            } else {
                if ($fieldname == "termination_date" && !in_array(toMySQLDate($newval), array("0000-00-00", "1970-01-01")) && !in_array($status, array("Terminated", "Cancelled"))) {
                    $newval = "0000-00-00";
                    $changelog[] = "Termination Date reset to " . $newval;
                } else {
                    if ($fieldname == "termination_date" && in_array(toMySQLDate($newval), array("0000-00-00", "1970-01-01")) && $service_data["termination_date"] == "0000-00-00" && in_array($status, array("Terminated", "Cancelled"))) {
                        $newval = date("Y-m-d");
                        $terminationDate = date("Y-m-d");
                        $updatearr["termination_date"] = date("Y-m-d");
                    } else {
                        if (validateDateInput($newval) || in_array($fieldname, array("overidesuspenduntil", "termination_date")) && (!$newval || in_array(toMySQLDate($newval), array("0000-00-00", "1970-01-01")))) {
                            $newval = toMySQLDate($newval);
                        } else {
                            $errors[] = "The " . $logchangefields[$fieldname] . " you entered is invalid";
                        }
                    }
                }
            }
        } else {
            if ($fieldname == "password") {
                $newval = encrypt($newval);
            } else {
                if ($fieldname == "amount" && 0 <= $newamount) {
                    $newval = $newamount;
                } else {
                    if ($fieldname == "server" && $forceServerReset) {
                        $newval = $newServer;
                    }
                }
            }
        }
        $updatearr[$fieldname] = $newval;
    }
    if (toMySQLDate($whmcs->get_req_var("oldnextduedate")) != $updatearr["nextduedate"]) {
        $updatearr["nextinvoicedate"] = $updatearr["nextduedate"];
    }
    if (count($errors) == 0) {
        if ($updatearr) {
            update_query("tblhosting", $updatearr, array("id" => $id));
        }
        if ($changelog) {
            logActivity("Modified Product/Service - " . implode(", ", $changelog) . " - User ID: " . $userid . " - Service ID: " . $id, $userid);
        }
        $cancelid = WHMCS\Database\Capsule::table("tblcancelrequests")->where("relid", $id)->orderBy("id", "desc")->first();
        if ($autoterminateendcycle) {
            if ($cancelid && $cancelid->type == "Immediate") {
                WHMCS\Database\Capsule::table("tblcancelrequests")->where("id", $cancelid->id)->update(array("reason" => $autoterminatereason, "type" => "End of Billing Period"));
            } else {
                if (!$cancelid) {
                    createCancellationRequest($userid, $id, $autoterminatereason, "End of Billing Period");
                }
            }
        } else {
            if ($cancelid && $cancelid->type == "End of Billing Period") {
                WHMCS\Database\Capsule::table("tblcancelrequests")->where("id", $cancelid->id)->delete($cancelid->id);
                logActivity("Removed Automatic Cancellation for End of Current Cycle - Service ID: " . $id, $userid);
            }
        }
        $module = get_query_val("tblproducts", "servertype", array("id" => $packageid));
        if ($module) {
            $moduleInterface = new WHMCS\Module\Server();
            if ($moduleInterface->loadByServiceID($id) && $moduleInterface->functionExists("AdminServicesTabFieldsSave")) {
                $moduleParams = $moduleInterface->buildParams();
                $adminServicesTabFieldsSaveErrors = $moduleInterface->call("AdminServicesTabFieldsSave", $moduleParams);
                if ($adminServicesTabFieldsSaveErrors && !is_array($adminServicesTabFieldsSaveErrors) && $adminServicesTabFieldsSaveErrors != "success") {
                    WHMCS\Session::set("adminServicesTabFieldsSaveErrors", $adminServicesTabFieldsSaveErrors);
                }
            }
        }
        run_hook("AdminClientServicesTabFieldsSave", $_REQUEST);
        run_hook("AdminServiceEdit", array("userid" => $userid, "serviceid" => $id));
        run_hook("ServiceEdit", array("userid" => $userid, "serviceid" => $id));
        if ($terminationDateValid) {
            $queryStr .= "&success=true";
        }
        redir($queryStr);
    }
}
if ($action == "delete") {
    check_token("WHMCS.admin.default");
    checkPermission("Delete Clients Products/Services");
    run_hook("ServiceDelete", array("userid" => $userid, "serviceid" => $id));
    try {
        $service = WHMCS\Service\Service::with("product", "customFieldValues", "customFieldValues.customField", "addons", "addons.customFieldValues", "addons.customFieldValues.customField")->findOrFail($id);
        if ($service->product->stockControlEnabled) {
            $service->product->quantityInStock++;
            $service->product->save();
        }
        foreach ($service->addons as $serviceAddon) {
            foreach ($serviceAddon->customFieldValues as $customFieldValue) {
                if ($customFieldValue->customField->type == "addon") {
                    $customFieldValue->delete();
                }
            }
            $serviceAddon->delete();
        }
        foreach ($service->customFieldValues as $customFieldValue) {
            if ($customFieldValue->customField->type == "product") {
                $customFieldValue->delete();
            }
        }
        $service->delete();
        delete_query("tblhostingconfigoptions", array("relid" => $id));
        delete_query("tblaffiliatesaccounts", array("relid" => $id));
        logActivity("Deleted Product/Service - User ID: " . $userid . " - Service ID: " . $id, $userid);
    } catch (Exception $e) {
    }
    redir("userid=" . $userid);
}
if ($action == "cancelsubscription") {
    check_token("WHMCS.admin.default");
    try {
        $result = cancelSubscriptionForService($id, $userid);
        WHMCS\Cookie::set("CancelSubscription", $result);
    } catch (Exception $e) {
        switch (get_class($e)) {
            case "WHMCS\\Exception\\Gateways\\SubscriptionCancellationNotSupported":
            case "WHMCS\\Exception\\Gateways\\SubscriptionCancellationFailed":
            case "InvalidArgumentException":
                WHMCS\Cookie::set("CancelSubscription", $e->getMessage());
                break;
            default:
                throw new WHMCS\Exception\ProgramExit($aInt->lang("global", "unexpectedError") . " :" . $e->getMessage());
        }
    }
    redir("userid=" . $userid . "&id=" . $id . "&subcancel=true&ajaxupdate=1");
}
if ($action == "deladdon") {
    check_token("WHMCS.admin.default");
    checkPermission("Delete Clients Products/Services");
    run_hook("AddonDeleted", array("id" => $aid));
    delete_query("tblhostingaddons", array("id" => $aid));
    logActivity("Deleted Addon - User ID: " . $userid . " - Service ID: " . $id . " - Addon ID: " . $aid, $userid);
    redir("userid=" . $userid . "&id=" . $id);
}
ob_start();
$adminbuttonarray = "";
if ($module && !(int) $aid && $serverModule->functionExists("AdminCustomButtonArray")) {
    $moduleParams = $serverModule->buildParams();
    $adminbuttonarray = $serverModule->call("AdminCustomButtonArray", $moduleParams);
}
if ($modop == "create") {
    check_token("WHMCS.admin.default");
    $result = ServerCreateAccount($id, (int) $aid);
    WHMCS\Cookie::set("ModCmdResult", $result);
    $extra = "";
    if ((int) $aid) {
        $extra = "&aid=" . $aid;
    }
    redir("userid=" . $userid . "&id=" . $id . $extra . "&act=create&ajaxupdate=1");
}
if ($modop == "renew") {
    check_token("WHMCS.admin.default");
    $result = ServerRenew($id, (int) $aid);
    WHMCS\Cookie::set("ModCmdResult", $result);
    $extra = "";
    if ((int) $aid) {
        $extra = "&aid=" . $aid;
    }
    redir("userid=" . $userid . "&id=" . $id . $extra . "&act=renew&ajaxupdate=1");
}
if ($modop == "suspend") {
    check_token("WHMCS.admin.default");
    $suspreason = $whmcs->get_req_var("suspreason");
    $suspemail = $whmcs->get_req_var("suspemail");
    $result = ServerSuspendAccount($id, $suspreason, (int) $aid);
    WHMCS\Cookie::set("ModCmdResult", $result);
    if ($result == "success" && $suspemail == "true") {
        $emailTemplate = WHMCS\Mail\Template::where("type", "=", "product")->where("name", "=", "Service Suspension Notification")->get()->first();
        if (!is_null($emailTemplate)) {
            $isDisabled = $emailTemplate->disabled;
            if ($isDisabled) {
                $emailTemplate->disabled = 0;
                $emailTemplate->save();
            }
            sendMessage("Service Suspension Notification", $id);
            if ($isDisabled) {
                $emailTemplate->disabled = $isDisabled;
                $emailTemplate->save();
            }
        }
    }
    $extra = "";
    if ((int) $aid) {
        $extra = "&aid=" . $aid;
    }
    redir("userid=" . $userid . "&id=" . $id . $extra . "&act=suspend&ajaxupdate=1");
}
if ($modop == "unsuspend") {
    check_token("WHMCS.admin.default");
    $sendEmail = $whmcs->get_req_var("unsuspended_email");
    $result = ServerUnsuspendAccount($id, (int) $aid);
    WHMCS\Cookie::set("ModCmdResult", $result);
    if ($result == "success" && $sendEmail == "true") {
        $emailTemplate = WHMCS\Mail\Template::where("type", "=", "product")->where("name", "=", "Service Unsuspension Notification")->get()->first();
        if (!is_null($emailTemplate)) {
            $isDisabled = $emailTemplate->disabled;
            if ($isDisabled) {
                $emailTemplate->disabled = 0;
                $emailTemplate->save();
            }
            sendMessage("Service Unsuspension Notification", $id);
            if ($isDisabled) {
                $emailTemplate->disabled = $isDisabled;
                $emailTemplate->save();
            }
        }
    }
    $extra = "";
    if ((int) $aid) {
        $extra = "&aid=" . $aid;
    }
    redir("userid=" . $userid . "&id=" . $id . $extra . "&act=unsuspend&ajaxupdate=1");
}
if ($modop == "terminate") {
    check_token("WHMCS.admin.default");
    $keepZone = App::getFromRequest("keep_zone") === "true";
    $result = ModuleCallFunction("Terminate", $id, array("keepZone" => $keepZone), $aid);
    WHMCS\Cookie::set("ModCmdResult", $result);
    $extra = "";
    if ((int) $aid) {
        $extra = "&aid=" . $aid;
    }
    redir("userid=" . $userid . "&id=" . $id . $extra . "&act=terminate&ajaxupdate=1");
}
if ($modop == "changepackage") {
    check_token("WHMCS.admin.default");
    $result = ServerChangePackage($id, (int) $aid);
    WHMCS\Cookie::set("ModCmdResult", $result);
    $extra = "";
    if ((int) $aid) {
        $extra = "&aid=" . $aid;
    }
    redir("userid=" . $userid . "&id=" . $id . $extra . "&act=updown&ajaxupdate=1");
}
if ($modop == "changepw") {
    check_token("WHMCS.admin.default");
    $result = ServerChangePassword($id, (int) $aid);
    WHMCS\Cookie::set("ModCmdResult", $result);
    $extra = "";
    if ((int) $aid) {
        $extra = "&aid=" . $aid;
    }
    redir("userid=" . $userid . "&id=" . $id . $extra . "&act=pwchange&ajaxupdate=1");
}
if ($modop == "manageapplinks") {
    check_token("WHMCS.admin.default");
    $moduleInterface = new WHMCS\Module\Server();
    if ((int) $aid) {
        $moduleInterface->loadByAddonId((int) $aid);
    } else {
        $moduleInterface->loadByServiceID($id);
    }
    try {
        $moduleInterface->doSingleApplicationLinkCall($whmcs->get_req_var("command"));
        $success = true;
        $errorMsg = array();
    } catch (Exception $e) {
        $success = false;
        $errorMsg = $e->getMessage();
    }
    $aInt->setBodyContent(array("success" => $success, "errorMsg" => $errorMsg));
    $aInt->output();
    throw new WHMCS\Exception\ProgramExit();
}
if ($modop == "singlesignon") {
    check_token("WHMCS.admin.default");
    $serverId = (int) $server;
    $extra = "";
    if ($addonDetails) {
        $serverId = $addonDetails->serverId;
        $extra = "&aid=" . $aid;
    }
    $allowedRoleIds = WHMCS\Database\Capsule::table("tblserversssoperms")->where("server_id", "=", $serverId)->pluck("role_id");
    if (count($allowedRoleIds) == 0) {
        $allowAccess = true;
    } else {
        $allowAccess = false;
        $adminAuth = new WHMCS\Auth();
        $adminAuth->getInfobyID(WHMCS\Session::get("adminid"));
        $adminRoleId = $adminAuth->getAdminRoleId();
        if (in_array($adminRoleId, $allowedRoleIds)) {
            $allowAccess = true;
        }
    }
    if (!$allowAccess) {
        WHMCS\Cookie::set("ModCmdResult", "You do not have permisson to sign-in to this server. If you feel this message to be an error, please contact the system administrator.");
        redir("userid=" . $userid . "&id=" . $id . $extra . "&act=singlesignon&ajaxupdate=1");
    }
    $redirectUrl = "";
    try {
        $moduleInterface = new WHMCS\Module\Server();
        if ((int) $aid) {
            $moduleInterface->loadByAddonId((int) $aid);
        } else {
            $moduleInterface->loadByServiceID($id);
        }
        $redirectUrl = $moduleInterface->getSingleSignOnUrlForService();
    } catch (WHMCS\Exception\Module\SingleSignOnError $e) {
        WHMCS\Cookie::set("ModCmdResult", $e->getMessage());
        redir("userid=" . $userid . "&id=" . $id . $extra . "&act=singlesignon&ajaxupdate=1");
    } catch (Exception $e) {
        logActivity("Single Sign-On Request Failed with a Fatal Error: " . $e->getMessage(), $userid);
        WHMCS\Cookie::set("ModCmdResult", $aInt->lang("sso", "fatalerror"));
        redir("userid=" . $userid . "&id=" . $id . $extra . "&act=singlesignon&ajaxupdate=1");
    }
    echo "window|" . $redirectUrl;
    throw new WHMCS\Exception\ProgramExit();
}
if ($modop == "custom") {
    check_token("WHMCS.admin.default");
    $ac = $whmcs->getFromRequest("ac");
    $result = ServerCustomFunction($id, $ac, (int) $aid);
    if (isset($result["jsonResponse"])) {
        $result = $result["jsonResponse"];
    }
    if (is_array($result)) {
        if (count($result) == 1 && array_key_exists("error", $result)) {
            $result = $result["error"];
        } else {
            if (count($result) == 1 && array_key_exists("success", $result)) {
            } else {
                $aInt->jsonResponse($result);
            }
        }
    } else {
        if (substr($result, 0, 9) == "redirect|" || substr($result, 0, 7) == "window|") {
            echo $result;
            throw new WHMCS\Exception\ProgramExit();
        }
    }
    WHMCS\Cookie::set("ModCmdResult", $result);
    $extra = "";
    if ((int) $aid) {
        $extra = "&aid=" . $aid;
    }
    redir("userid=" . $userid . "&id=" . $id . $extra . "&act=custom&ajaxupdate=1");
}
if (in_array($whmcs->get_req_var("act"), array("create", "renew", "suspend", "unsuspend", "terminate", "updown", "pwchange", "custom", "singlesignon")) && ($result = WHMCS\Cookie::get("ModCmdResult"))) {
    $result2 = WHMCS\Cookie::get("ModCmdResult", true);
    if ($result2 && is_array($result2) && array_key_exists("success", $result2)) {
        infoBox(AdminLang::trans("services.modulesuccess"), nl2br(WHMCS\Input\Sanitize::makeSafeForOutput($result2["success"])));
    } else {
        if ($result != "success") {
            infoBox($aInt->lang("services", "moduleerror"), WHMCS\Input\Sanitize::makeSafeForOutput($result), "error");
        } else {
            infoBox($aInt->lang("services", "modulesuccess"), $aInt->lang("services", $act . "success"), "success");
        }
    }
    WHMCS\Cookie::delete("ModCmdResult");
}
if ($whmcs->get_req_var("subcancel") && ($result = WHMCS\Cookie::get("CancelSubscription"))) {
    if ($result == 1) {
        infoBox($aInt->lang("global", "success"), $aInt->lang("services", "cancelSubscriptionSuccess"), "success");
    } else {
        if (!$result) {
            $result = $aInt->lang("services", "cancelSubscriptionFailed");
        }
        infoBox($aInt->lang("global", "erroroccurred"), $result, "error");
    }
    WHMCS\Cookie::delete("CancelSubscription");
}
if ($whmcs->get_req_var("success")) {
    infoBox($aInt->lang("global", "changesuccess"), $aInt->lang("global", "changesuccessdesc"), "success");
} else {
    if ($whmcs->get_req_var("terminationdateinvalid")) {
        infoBox($aInt->lang("global", "changesuccess"), $aInt->lang("clients", "terminationdateinvalid"), "success");
    } else {
        if (count($errors)) {
            $errormsg = "";
            foreach ($errors as $error) {
                $errormsg .= $error . "<br />";
            }
            infoBox($aInt->lang("global", "followingerrorsoccurred"), $errormsg, "error");
        }
    }
}
if (!count($errors)) {
    $regdate = fromMySQLDate($regdate);
    $terminationDate = fromMySQLDate($terminationDate);
    $nextduedate = fromMySQLDate($nextduedate);
    $overidesuspenduntil = fromMySQLDate($overidesuspenduntil);
}
if ($disklimit == "0") {
    $disklimit = $aInt->lang("global", "unlimited");
}
if ($bwlimit == "0") {
    $bwlimit = $aInt->lang("global", "unlimited");
}
$currency = getCurrency($userid);
$data = get_query_vals("tblcancelrequests", "id,type,reason", array("relid" => $id), "id", "DESC");
$cancelid = $data["id"];
$canceltype = $data["type"];
$autoterminatereason = $data["reason"];
$autoterminateendcycle = false;
if ($canceltype == "End of Billing Period") {
    $autoterminateendcycle = $cancelid ? true : false;
}
if (!$server) {
    $server = get_query_val("tblservers", "id", array("type" => $module, "active" => "1"));
    if ($server) {
        update_query("tblhosting", array("server" => $server), array("id" => $id));
    }
}
$jscode = "function doDeleteAddon(id) {\nif (confirm(\"" . $aInt->lang("addons", "areYouSureDelete", 1) . "\")) {\nwindow.location='?userid=" . $userid . "&id=" . $id . "&action=deladdon&aid='+id+'" . generate_token("link") . "';\n}}\n\nfunction cancelSubscription() {\n    \$(\"#modalCancelSubscription\").modal(\"hide\");\n\n    \$(\"#subscription\").css(\"filter\",\"alpha(opacity=20)\");\n    \$(\"#subscription\").css(\"-moz-opacity\",\"0.2\");\n    \$(\"#subscription\").css(\"-khtml-opacity\",\"0.2\");\n    \$(\"#subscription\").css(\"opacity\",\"0.2\");\n    var position = \$(\"#subscription\").position();\n\n    \$(\"#subscriptionworking\").css(\"position\",\"absolute\");\n    \$(\"#subscriptionworking\").css(\"top\",position.top);\n    \$(\"#subscriptionworking\").css(\"left\",position.left);\n    \$(\"#subscriptionworking\").css(\"padding\",\"9px 50px 0\");\n    \$(\"#subscriptionworking\").fadeIn();\n\n    var reqstr = \"userid=" . $userid . "&id=" . $id . "&action=cancelsubscription" . generate_token("link") . "\";\n\n    WHMCS.http.jqClient.post(\n        \"clientsservices.php\",\n        reqstr,\n        function(data){\n            if (data.body) {\n                data = data.body;\n                \$(\"#servicecontent\").html(data);\n            }\n        }\n    );\n}\n";
if ($module || $addonModule) {
    $token = generate_token("link");
    $addonRequest = "";
    if ($addonModule) {
        $addonRequest = "&aid=" . $aid;
    }
    $jscode .= "function runModuleCommand(cmd,custom) {\n    \$('#growls').fadeOut('fast').remove();\n    \$('.successbox,.errorbox').slideUp('fast').remove();\n    // Hide the modal that was activated.\n    jQuery(\"[id^=modalModule]\").modal(\"hide\");\n    var commandButtons = jQuery('#modcmdbtns'),\n        commandWorking = jQuery('#modcmdworking');\n\n    commandButtons.css(\"filter\",\"alpha(opacity=20)\");\n    commandButtons.css(\"-moz-opacity\",\"0.2\");\n    commandButtons.css(\"-khtml-opacity\",\"0.2\");\n    commandButtons.css(\"opacity\",\"0.2\");\n    var position = commandButtons.position();\n\n    commandWorking.css(\"position\",\"absolute\");\n    commandWorking.css(\"top\",position.top);\n    commandWorking.css(\"left\",position.left);\n    commandWorking.css(\"padding\",\"9px 50px 0\");\n    commandWorking.fadeIn();\n\n    var reqstr = \"userid=" . $userid . "&id=" . $id . $addonRequest . "&modop=\"+cmd+\"&ajax=1" . $token . "\";\n    if (custom) {\n        reqstr += \"&ac=\"+custom;\n    } else if (cmd == \"suspend\") {\n        reqstr += \"&suspreason=\"+encodeURIComponent(\$(\"#suspreason\").val())+\"&suspemail=\"+\$(\"#suspemail\").is(\":checked\");\n    } else if (cmd == \"unsuspend\") {\n        reqstr += \"&unsuspended_email=\" + jQuery(\"#unsuspended_email\").is(\":checked\");\n    } else if (cmd === \"terminate\" && jQuery('#inputKeepCPanelDnsZone').length !== 0) {\n        reqstr += \"&keep_zone=\" + jQuery(\"#inputKeepCPanelDnsZone\").is(\":checked\");\n    }\n\n    WHMCS.http.jqClient.post(\"clientsservices.php\", reqstr,\n    function(data){\n        if (data.success && data.redirect) {\n            data = data.redirect;\n        }\n        if (data.body) {\n            data = data.body;\n        }\n\n        if (data.substr(0,9)==\"redirect|\") {\n            window.location = data.substr(9);\n        } else if (data.substr(0,7)==\"window|\") {\n            window.open(data.substr(7), '_blank');\n            commandButtons.css(\"filter\",\"alpha(opacity=100)\");\n            commandButtons.css(\"-moz-opacity\",\"1\");\n            commandButtons.css(\"-khtml-opacity\",\"1\");\n            commandButtons.css(\"opacity\",\"1\");\n            commandWorking.fadeOut();\n        } else {\n            \$(\"#servicecontent\").html(data);\n            \$('html, body').animate({\n                scrollTop: \$('.client-tabs').offset().top - 10\n            }, 500);\n        }\n    });\n\n}";
}
$aInt->jscode = $jscode;
echo "<div class=\"context-btn-container\">\n    <div class=\"row\">\n        <div class=\"col-sm-7 text-left\">";
$addonServices = array();
$hostingAddonCollection = WHMCS\Service\Addon::leftJoin("tbladdons", "tbladdons.id", "=", "tblhostingaddons.addonid")->where("tblhostingaddons.userid", $userid)->orderBy("name")->get(array("tblhostingaddons.status", "tblhostingaddons.name as name", "tblhostingaddons.hostingid", "tblhostingaddons.id", "tbladdons.name as addonName"));
foreach ($hostingAddonCollection as $hostingAddon) {
    switch ($hostingAddon->status) {
        case "Pending":
            $color = "#FFFFCC";
            break;
        case "Suspended":
            $color = "#CCFF99";
            break;
        case "Terminated":
        case "Cancelled":
        case "Fraud":
        case "Completed":
            $color = "#FF9999";
            break;
        default:
            $color = "#FFF";
    }
    $addonName = $hostingAddon->addonName;
    if (!$addonName) {
        $addonName = $hostingAddon->name;
    }
    $value = array($color, "- " . $addonName);
    $addonServices[$hostingAddon->serviceId]["a" . $hostingAddon->id] = $value;
}
$allServices = array();
$servicesarr = array();
$result = select_query("tblhosting", "tblhosting.id,tblhosting.domain,tblproducts.name,tblhosting.domainstatus", array("userid" => $userid), "domain", "ASC", "", "tblproducts ON tblhosting.packageid=tblproducts.id");
while ($data = mysql_fetch_array($result)) {
    $servicelist_id = $data["id"];
    $servicelist_product = $data["name"];
    $servicelist_domain = $data["domain"];
    $servicelist_status = $data["domainstatus"];
    if ($servicelist_domain) {
        $servicelist_product .= " - " . $servicelist_domain;
    }
    switch ($servicelist_status) {
        case "Pending":
            $color = "#FFFFCC";
            break;
        case "Suspended":
            $color = "#CCFF99";
            break;
        case "Terminated":
        case "Cancelled":
        case "Fraud":
        case "Completed":
            $color = "#FF9999";
            break;
        default:
            $color = "#FFF";
    }
    $servicesarr[$servicelist_id] = array($color, $servicelist_product);
    $allServices[$servicelist_id] = array($color, $servicelist_product);
    if (array_key_exists($servicelist_id, $addonServices)) {
        foreach ($addonServices[$servicelist_id] as $addonServiceKey => $addonService) {
            $allServices[$addonServiceKey] = $addonService;
        }
    }
}
if ($aid && is_numeric($aid)) {
    $itemToSelect = "a" . $aid;
} else {
    $itemToSelect = $id;
}
$frmsub = new WHMCS\Form("frm2");
echo $frmsub->form("", "", "", "get", true) . $frmsub->hidden("userid", $userid) . $frmsub->dropdown("productselect", $allServices, $itemToSelect, "", "", "", "", "", "form-control selectize-select selectize-float selectize-auto-submit") . $frmsub->submit($aInt->lang("global", "go"), "btn btn-default selectize-float-btn") . $frmsub->close();
echo "</div>";
if (!$aid) {
    $isDomain = str_replace(".", "", $domain) != $domain;
    if ($producttype == "other") {
        $isDomain = false;
    }
    $sslStatus = WHMCS\Domain\Ssl\Status::factory($userid, $domain);
    $html = "<img src=\"%s\"\n               class=\"%s\"\n               data-toggle=\"tooltip\"\n               title=\"%s\"\n               data-domain=\"%s\"\n               data-user-id=\"%s\"\n               >";
    $output = sprintf($html, $sslStatus->getImagePath(), $sslStatus->getClass(), $sslStatus->getTooltipContent(), $domain, $userid);
    echo "<div class=\"col-sm-5\">\n            " . $output . "\n            " . $frm->button("<i class=\"fas fa-arrow-circle-up\"></i> " . $aInt->lang("services", "createupgorder"), "window.open('clientsupgrade.php?id=" . $id . "','','width=750,height=350,scrollbars=yes')", "btn btn-default left-margin-5") . "\n            " . $frm->button("<i class=\"fas fa-random\"></i> " . $aInt->lang("services", "moveservice"), "window.open('clientsmove.php?type=hosting&id=" . $id . "','movewindow','width=500,height=300,top=100,left=100,scrollbars=yes')") . "\n        </div>";
}
echo "</div>\n</div>\n\n<div id=\"modcmdresult\" style=\"display:none;\"></div>\n";
if ($cancelid && !$infobox) {
    infoBox($aInt->lang("services", "cancrequest"), $aInt->lang("services", "cancrequestinfo") . "<br />" . $aInt->lang("fields", "reason") . ": " . $autoterminatereason, "info");
}
echo $infobox;
if ($lastupdate && $lastupdate != "0000-00-00 00:00:00") {
    echo "<div class=\"contentbox\">\n<strong>" . $aInt->lang("services", "diskusage") . ":</strong> " . $diskusage . " " . $aInt->lang("fields", "mb") . ", <strong>" . $aInt->lang("services", "disklimit") . ":</strong> " . $disklimit . " " . $aInt->lang("fields", "mb") . ", ";
    if ($diskusage == $aInt->lang("global", "unlimited") || $disklimit == $aInt->lang("global", "unlimited")) {
    } else {
        echo "<strong>" . round($diskusage / $disklimit * 100, 0) . "% " . $aInt->lang("services", "used") . "</strong> :: ";
    }
    echo "<strong>" . $aInt->lang("services", "bwusage") . ":</strong> " . $bwusage . " " . $aInt->lang("fields", "mb") . ", <strong>" . $aInt->lang("services", "bwlimit") . ":</strong> " . $bwlimit . " " . $aInt->lang("fields", "mb") . ", ";
    if ($bwusage == $aInt->lang("global", "unlimited") || $bwlimit == $aInt->lang("global", "unlimited")) {
    } else {
        echo "<strong>" . round($bwusage / $bwlimit * 100, 0) . "% " . $aInt->lang("services", "used") . "</strong><br>";
    }
    echo "<small>(" . $aInt->lang("services", "lastupdated") . ": " . fromMySQLDate($lastupdate, "time") . ")</small>\n</div>\n<br />\n";
}
echo $frm->form("?userid=" . $userid . "&id=" . $id . ($aid ? "&aid=" . $aid : ""));
if ($aid) {
    if ($aid == "add") {
        checkPermission("Add New Order");
        $managetitle = $aInt->lang("addons", "addnew");
        $setupfee = "0.00";
        $recurring = "0.00";
        $regdate = $nextduedate = getTodaysDate();
        $notes = $customname = "";
        $addonid = 0;
        $status = "Pending";
        $billingcycle = $serviceBillingCycle ? $serviceBillingCycle : "Free Account";
        $tax = "";
        $serversArray = array();
    } else {
        $managetitle = $aInt->lang("addons", "editaddon");
        $aid = $addonDetails->id;
        $id = $addonDetails->serviceId;
        $addonid = $addonDetails->addonId;
        $customname = $addonDetails->name;
        $recurring = $addonDetails->recurringFee;
        $setupfee = $addonDetails->setupFee;
        $billingcycle = $addonDetails->billingCycle;
        $status = $addonDetails->status;
        $regdate = $addonDetails->registrationDate;
        $nextduedate = $addonDetails->nextDueDate;
        $paymentmethod = $addonDetails->paymentGateway;
        $terminationDate = $addonDetails->terminationDate;
        if (!$paymentmethod || !$gateways->isActiveGateway($paymentmethod)) {
            $paymentmethod = ensurePaymentMethodIsSet($userid, $aid, "tblhostingaddons");
        }
        $tax = (int) $addonDetails->applyTax;
        $notes = $addonDetails->notes;
        $server = $addonDetails->serverId;
        $regdate = fromMySQLDate($regdate);
        $nextduedate = fromMySQLDate($nextduedate);
        $terminationDate = fromMySQLDate($terminationDate);
        $moduleInterface = new WHMCS\Module\Server();
        $moduleInterface->loadByAddonId($aid);
        $serversArray = $moduleInterface->getServerListForModule();
        if (!$server && $serversArray) {
            $server = key($serversArray);
            $addonDetails->serverId = $server;
            $addonDetails->save();
        }
    }
    echo "<h2 style=\"margin:15px;\">" . $managetitle . "</h2>";
    $tbl = new WHMCS\Table();
    $tbl->add($aInt->lang("fields", "parentProduct"), $frm->hidden("oldserviceid", $id) . $frm->dropdown("id", $servicesarr, $id, "", "", "", "", "addonServiceId", "form-control"));
    $tbl->add($aInt->lang("fields", "setupfee"), $frm->text("setupfee", $setupfee, "10", false, "form-control input-100"));
    $tbl->add($aInt->lang("fields", "regdate"), $frm->date("regdate", $regdate));
    $tbl->add($aInt->lang("global", "recurring"), $frm->text("recurring", $recurring, "10", false, "form-control input-100 input-inline") . ($aid == "add" ? " " . $frm->checkbox("defaultpricing", $aInt->lang("addons", "usedefault"), true) : ""));
    $predefaddons = WHMCS\Product\Addon::getAddonDropdownValues($addonDetails->addonId);
    $tbl->add($aInt->lang("addons", "predefinedaddon"), $frm->dropdown("addonid", $predefaddons, $addonid, "", "", true));
    $tbl->add($aInt->lang("fields", "billingcycle"), $aInt->cyclesDropDown($billingcycle, "", "Free"));
    $tbl->add($aInt->lang("addons", "customname"), $frm->text("name", $customname, "40", false, "form-control input-80percent"));
    $tbl->add($aInt->lang("fields", "nextduedate"), $aid && is_numeric($aid) && in_array($billingcycle, array("One Time", "Free Account")) ? AdminLang::trans("global.na") : $frm->date("nextduedate", $nextduedate));
    $tbl->add($aInt->lang("fields", "status"), $aInt->productStatusDropDown($status));
    $tbl->add(AdminLang::trans("fields.terminationDate"), $frm->date("termination_date", strpos($terminationDate, "0000") === false ? $terminationDate : ""));
    $tbl->add($aInt->lang("fields", "paymentmethod"), paymentMethodsSelection());
    $tbl->add($aInt->lang("addons", "taxaddon"), $frm->checkbox("tax", "", $tax));
    if ($serversArray) {
        $createAddonServerOptionForNone = false;
        if ($moduleInterface->isMetaDataValueSet("RequiresServer") && !$moduleInterface->getMetaDataValue("RequiresServer")) {
            $createAddonServerOptionForNone = true;
        }
        $tbl->add(AdminLang::trans("fields.server"), $frm->dropdown("server", $serversArray, $server, "", "", $createAddonServerOptionForNone), 1);
    }
    $moduleButtons = array();
    if ($moduleInterface) {
        if ($moduleInterface->functionExists("CreateAccount")) {
            $moduleButtons[] = $frm->button(AdminLang::trans("modulebuttons.create"), "jQuery('#modalModuleCreate').modal('show');");
        }
        if ($moduleInterface->functionExists("Renew")) {
            $moduleButtons[] = $frm->button(AdminLang::trans("modulebuttons.renew"), "jQuery('#modalModuleRenew').modal('show');");
        }
        if ($moduleInterface->functionExists("SuspendAccount")) {
            $moduleButtons[] = $frm->button(AdminLang::trans("modulebuttons.suspend"), "jQuery('#modalModuleSuspend').modal('show');");
        }
        if ($moduleInterface->functionExists("UnsuspendAccount")) {
            $moduleButtons[] = $frm->button(AdminLang::trans("modulebuttons.unsuspend"), "jQuery('#modalModuleUnsuspend').modal('show');");
        }
        if ($moduleInterface->functionExists("TerminateAccount")) {
            $moduleButtons[] = $frm->button(AdminLang::trans("modulebuttons.terminate"), "jQuery('#modalModuleTerminate').modal('show');");
        }
        if ($moduleInterface->functionExists("ChangePackage")) {
            $moduleButtons[] = $frm->button($moduleInterface->getMetaDataValue("ChangePackageLabel") ?: AdminLang::trans("modulebuttons.changepackage"), "jQuery('#modalModuleChangePackage').modal('show');");
        }
        if ($moduleInterface->functionExists("ChangePassword")) {
            $moduleButtons[] = $frm->button(AdminLang::trans("modulebuttons.changepassword"), "runModuleCommand('changepw')");
        }
        if ($moduleInterface->functionExists("ServiceSingleSignOn")) {
            $btnLabel = $moduleInterface->getMetaDataValue("ServiceSingleSignOnLabel");
            if (!$btnLabel) {
                $btnLabel = AdminLang::trans("sso.servicelogin");
            }
            $moduleButtons[] = $frm->button($btnLabel, "runModuleCommand('singlesignon')");
        }
        if ($moduleInterface->isApplicationLinkingEnabled() && $moduleInterface->isApplicationLinkSupported()) {
            $moduleButtons[] = $frm->modalButton(AdminLang::trans("modulebuttons.manageAppLinks"), "modalmanageAppLinks");
        }
        $adminButtonArray = array();
        $moduleParams = array();
        if ($moduleInterface->functionExists("AdminCustomButtonArray")) {
            $moduleParams = $moduleInterface->buildParams();
            $adminButtonArray = $moduleInterface->call("AdminCustomButtonArray", $moduleParams);
        }
        $moduleButtons = buildcustommodulebuttons($moduleButtons, $adminButtonArray);
        if ($moduleButtons) {
            $tbl->add(AdminLang::trans("services.modulecommands"), "<div id=\"modcmdbtns\">" . implode(" ", $moduleButtons) . "</div><div id=\"modcmdworking\" style=\"display:none;text-align:center;\"><img src=\"images/loader.gif\" /> &nbsp; Working...</div>", 1);
        }
        if ($moduleInterface->functionExists("AdminServicesTabFields")) {
            if (!$moduleParams) {
                $moduleParams = $moduleInterface->buildParams();
            }
            $fieldsArray = $moduleInterface->call("AdminServicesTabFields", $moduleParams);
            if ($adminServicesTabFieldsSaveErrors = WHMCS\Session::getAndDelete("adminServicesTabFieldsSaveErrors")) {
                $tbl->add(AdminLang::trans("global.error"), $adminServicesTabFieldsSaveErrors, 1);
            }
            if (is_array($fieldsArray)) {
                foreach ($fieldsArray as $k => $v) {
                    $tbl->add($k, $v, 1);
                }
            }
        }
    }
    if ($addonid) {
        $customFields = getCustomFields("addon", $addonid, $aid, true);
        foreach ($customFields as $customField) {
            $tbl->add($customField["name"], $customField["input"], 1);
        }
    }
    $tbl->add($aInt->lang("fields", "adminnotes"), $frm->textarea("notes", $notes, "4", "100%"), 1);
    echo $tbl->output();
    if ($aid == "add") {
        echo "<p align=\"center\"><input type=\"checkbox\" name=\"geninvoice\" id=\"geninvoice\" checked /> <label for=\"geninvoice\">" . $aInt->lang("addons", "geninvoice") . "</a></p>";
    }
    echo "<div class=\"btn-container\">" . $frm->submit($aInt->lang("global", "savechanges"), "btn btn-primary") . " " . $frm->button($aInt->lang("global", "cancel"), "window.location='?userid=" . $userid . "&id=" . $id . "'") . "</div>";
} else {
    $moduleInterface = new WHMCS\Module\Server();
    $moduleInterface->loadByServiceID($id);
    $moduleParams = $moduleInterface->buildParams();
    $serversarr = $moduleInterface->getServerListForModule();
    $promoarr = array();
    $result = select_query("tblpromotions", "", "", "code", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $promo_id = $data["id"];
        $promo_code = $data["code"];
        $promo_type = $data["type"];
        $promo_recurring = $data["recurring"];
        $promo_value = $data["value"];
        if ($promo_type == "Percentage") {
            $promo_value .= "%";
        } else {
            $promo_value = formatCurrency($promo_value);
        }
        if ($promo_type == "Free Setup") {
            $promo_value = $aInt->lang("promos", "freesetup");
        }
        $promo_recurring = $promo_recurring ? $aInt->lang("status", "recurring") : $aInt->lang("status", "onetime");
        if ($promo_type == "Price Override") {
            $promo_recurring = $aInt->lang("promos", "priceoverride");
        }
        if ($promo_type == "Free Setup") {
            $promo_recurring = "";
        }
        $promoarr[$promo_id] = $promo_code . " - " . $promo_value . " " . $promo_recurring;
    }
    $cancelSubscription = "";
    if ($subscriptionid) {
        $gateway = new WHMCS\Module\Gateway();
        $gateway->load($paymentmethod);
        if ($gateway->functionExists("cancelSubscription")) {
            $cancelSubscription = "<span class=\"input-group-btn\">\n            <button type=\"button\" class=\"btn btn-default\" onclick=\"jQuery('#modalCancelSubscription').modal('show');\" id=\"btnCancel_Subscription\" style=\"margin-left:-3px;\">\n                " . $aInt->lang("services", "cancelSubscription") . "\n            </button>\n        </span>";
        }
    }
    $tbl = new WHMCS\Table();
    $tbl->add($aInt->lang("fields", "ordernum"), $orderid . " - <a href=\"orders.php?action=view&id=" . $orderid . "\">" . $aInt->lang("orders", "vieworder") . "</a>");
    $tbl->add($aInt->lang("fields", "regdate"), $frm->date("regdate", $regdate));
    $tbl->add($aInt->lang("fields", "product"), $frm->hidden("oldpackageid", $packageid) . $frm->dropdown("packageid", $aInt->productDropDown($packageid), "", "submit()", "", "", "", "", "form-control select-inline-long"));
    $tbl->add($aInt->lang("fields", "firstpaymentamount"), $frm->text("firstpaymentamount", $firstpaymentamount, "10", false, "form-control input-100"));
    $tbl->add($aInt->lang("fields", "server"), $frm->dropdown("server", $serversarr, $server, "submit()", "", $createServerOptionForNone));
    $tbl->add($aInt->lang("fields", "recurringamount"), $frm->text("amount", $amount, "10", false, "form-control input-100 input-inline") . " " . $frm->checkbox("autorecalcrecurringprice", $aInt->lang("services", "autorecalc"), $autorecalcdefault ? true : false));
    $tbl->add($producttype == "server" ? $aInt->lang("fields", "hostname") : $aInt->lang("fields", "domain"), "<div class=\"input-group input-300\">\n        <input type=\"text\" name=\"domain\" value=\"" . $domain . "\" class=\"form-control\">\n        <div class=\"input-group-btn\">\n            <button type=\"button\" class=\"btn btn-default dropdown-toggle\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\" style=\"margin-left:-3px;\">\n                <span class=\"caret\"></span>\n            </button>\n            <ul class=\"dropdown-menu dropdown-menu-right\">\n                <li><a href=\"http://" . $domain . "\" target=\"_blank\">www</a></li>\n                <li><a href=\"#\" onclick=\"\$('#frmWhois').submit();return false\">" . $aInt->lang("domains", "whois") . "</a></li>\n                <li><a href=\"http://www.intodns.com/" . $domain . "\" target=\"_blank\">intoDNS</a></li>\n            </ul>\n        </div>\n    </div>");
    $tbl->add($aInt->lang("fields", "nextduedate"), in_array($billingcycle, array("One Time", "Free Account")) ? AdminLang::trans("global.na") : $frm->hidden("oldnextduedate", $nextduedate) . $frm->date("nextduedate", $nextduedate));
    $tbl->add($aInt->lang("fields", "dedicatedip"), $frm->text("dedicatedip", $dedicatedip, "25", false, "form-control input-200"));
    $tbl->add($aInt->lang("fields", "terminationDate"), $frm->date("termination_date", strpos($terminationDate, "0000") === false ? $terminationDate : ""));
    $usernameOutput = $frm->text("username", $username, "20", false, "form-control input-200 input-inline");
    if ($moduleInterface->functionExists("ServiceSingleSignOn")) {
        $btnLabel = $moduleInterface->getMetaDataValue("ServiceSingleSignOnLabel");
        $usernameOutput = "<div class=\"\">" . $usernameOutput;
        $usernameOutput .= sprintf(" <button type=\"button\" onclick=\"runModuleCommand('%s'); return false\" class=\"btn btn-primary\">%s</button>", "singlesignon", $btnLabel ? $btnLabel : $aInt->lang("sso", "servicelogin"));
        $usernameOutput .= "</div>";
    } else {
        if ($moduleInterface->functionExists("LoginLink")) {
            $usernameOutput .= " " . $moduleInterface->call("LoginLink");
        }
    }
    $tbl->add($aInt->lang("fields", "username"), $usernameOutput);
    $tbl->add($aInt->lang("fields", "billingcycle"), $aInt->cyclesDropDown($billingcycle));
    $tbl->add($aInt->lang("fields", "password"), $frm->text("password", $password, "20", false, "form-control input-200"));
    $tbl->add($aInt->lang("fields", "paymentmethod"), paymentMethodsSelection() . " <a href=\"clientsinvoices.php?userid=" . $userid . "&serviceid=" . $id . "\">" . $aInt->lang("invoices", "viewinvoices") . "</a>");
    $statusExtra = "";
    if ($domainstatus == "Suspended") {
        $statusExtra = " (" . AdminLang::trans("services.suspendreason") . ": " . (!$suspendreason ? Lang::trans("suspendreasonoverdue") : $suspendreason) . ")";
    } else {
        if ($domainstatus == "Completed") {
            $statusExtra = $completedDate != "0000-00-00" ? " (" . fromMySQLDate($completedDate) . ")" : "";
        }
    }
    $tbl->add($aInt->lang("fields", "status"), $aInt->productStatusDropDown($domainstatus, false, "domainstatus", "prodstatus") . $statusExtra);
    $tbl->add($aInt->lang("fields", "promocode"), $frm->dropdown("promoid", $promoarr, $promoid, "", "", true) . "<br />(" . $aInt->lang("services", "noaffect") . ")");
    if ($producttype == "server") {
        $tbl->add($aInt->lang("fields", "assignedips"), $frm->textarea("assignedips", $assignedips, "4", "30"), 1);
        $tbl->add($aInt->lang("fields", "nameserver") . " 1", $frm->text("ns1", $ns1, "35", false, "form-control input-500"), 1);
        $tbl->add($aInt->lang("fields", "nameserver") . " 2", $frm->text("ns2", $ns2, "35", false, "form-control input-500"), 1);
    }
    $configoptions = array();
    $configoptions = getCartConfigOptions($packageid, "", $billingcycle, $id);
    if ($configoptions) {
        foreach ($configoptions as $configoption) {
            $optionid = $configoption["id"];
            $optionhidden = $configoption["hidden"];
            $optionname = $optionhidden ? $configoption["optionname"] . " <i>(" . $aInt->lang("global", "hidden") . ")</i>" : $configoption["optionname"];
            $optiontype = $configoption["optiontype"];
            $selectedvalue = $configoption["selectedvalue"];
            $selectedqty = $configoption["selectedqty"];
            if ($optiontype == "1") {
                $inputcode = "<select name=\"configoption[" . $optionid . "]\" class=\"form-control select-inline\">";
                foreach ($configoption["options"] as $option) {
                    $inputcode .= "<option value=\"" . $option["id"] . "\"";
                    if ($option["hidden"]) {
                        $inputcode .= " style='color:#ccc;'";
                    }
                    if ($selectedvalue == $option["id"]) {
                        $inputcode .= " selected";
                    }
                    $inputcode .= ">" . $option["name"] . "</option>";
                }
                $inputcode .= "</select>";
            } else {
                if ($optiontype == "2") {
                    $inputcode = "";
                    foreach ($configoption["options"] as $option) {
                        $inputcode .= "<label class=\"radio-inline\"><input type=\"radio\" name=\"configoption[" . $optionid . "]\" value=\"" . $option["id"] . "\"";
                        if ($selectedvalue == $option["id"]) {
                            $inputcode .= " checked";
                        }
                        if ($option["hidden"]) {
                            $inputcode .= "> <span style='color:#ccc;'>" . $option["name"] . "</span></label><br />";
                        } else {
                            $inputcode .= "> " . $option["name"] . "</label><br />";
                        }
                    }
                } else {
                    if ($optiontype == "3") {
                        $inputcode = "<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"configoption[" . $optionid . "]\" value=\"1\"";
                        if ($selectedqty) {
                            $inputcode .= " checked";
                        }
                        $inputcode .= "> " . $configoption["options"][0]["name"] . "</label>";
                    } else {
                        if ($optiontype == "4") {
                            $inputcode = "<input type=\"text\" name=\"configoption[" . $optionid . "]\" value=\"" . $selectedqty . "\" class=\"form-control input-50 input-inline\"> x " . $configoption["options"][0]["name"];
                        }
                    }
                }
            }
            $tbl->add($optionname, $inputcode, 1);
        }
    }
    if ($module) {
        $modulebtns = array();
        if ($moduleInterface->functionExists("CreateAccount")) {
            $modulebtns[] = $frm->button($aInt->lang("modulebuttons", "create"), "jQuery('#modalModuleCreate').modal('show');");
        }
        if ($moduleInterface->functionExists("Renew")) {
            $modulebtns[] = $frm->button(AdminLang::trans("modulebuttons.renew"), "jQuery('#modalModuleRenew').modal('show');");
        }
        if ($moduleInterface->functionExists("SuspendAccount")) {
            $modulebtns[] = $frm->button($aInt->lang("modulebuttons", "suspend"), "jQuery('#modalModuleSuspend').modal('show');");
        }
        if ($moduleInterface->functionExists("UnsuspendAccount")) {
            $modulebtns[] = $frm->button($aInt->lang("modulebuttons", "unsuspend"), "jQuery('#modalModuleUnsuspend').modal('show');");
        }
        if ($moduleInterface->functionExists("TerminateAccount")) {
            $modulebtns[] = $frm->button($aInt->lang("modulebuttons", "terminate"), "jQuery('#modalModuleTerminate').modal('show');");
        }
        if ($moduleInterface->functionExists("ChangePackage")) {
            $modulebtns[] = $frm->button($moduleInterface->getMetaDataValue("ChangePackageLabel") ?: AdminLang::trans("modulebuttons.changepackage"), "jQuery('#modalModuleChangePackage').modal('show');");
        }
        if ($moduleInterface->functionExists("ChangePassword")) {
            $modulebtns[] = $frm->button($aInt->lang("modulebuttons", "changepassword"), "runModuleCommand('changepw')");
        }
        if ($moduleInterface->isApplicationLinkingEnabled() && $moduleInterface->isApplicationLinkSupported()) {
            $modulebtns[] = $frm->modalButton($aInt->lang("modulebuttons", "manageAppLinks"), "modalmanageAppLinks");
        }
        $modulebtns = buildcustommodulebuttons($modulebtns, $adminbuttonarray);
        $tbl->add($aInt->lang("services", "modulecommands"), "<div id=\"modcmdbtns\">" . implode(" ", $modulebtns) . "</div><div id=\"modcmdworking\" style=\"display:none;text-align:center;\"><img src=\"images/loader.gif\" /> &nbsp; Working...</div>", 1);
        if ($moduleInterface->functionExists("AdminServicesTabFields")) {
            if ($adminServicesTabFieldsSaveErrors = WHMCS\Session::getAndDelete("adminServicesTabFieldsSaveErrors")) {
                $tbl->add(AdminLang::trans("global.error"), $adminServicesTabFieldsSaveErrors, 1);
            }
            $fieldsArray = $moduleInterface->call("AdminServicesTabFields", $moduleParams);
            if ($fieldsArray && is_array($fieldsArray)) {
                foreach ($fieldsArray as $fieldName => $fieldValue) {
                    $tbl->add($fieldName, $fieldValue, 1);
                }
            }
        }
    }
    $hookret = run_hook("AdminClientServicesTabFields", array("id" => $id));
    foreach ($hookret as $hookdat) {
        foreach ($hookdat as $k => $v) {
            $tbl->add($k, $v, 1);
        }
    }
    $addonshtml = "";
    $aInt->sortableTableInit("nopagination");
    $service = new WHMCS\Service($id);
    $addons = $service->getAddons();
    foreach ($addons as $vals) {
        $tabledata[] = array($vals["regdate"], $vals["name"], $vals["pricing"], $vals["status"], $vals["nextduedate"], "<a href=\"?userid=" . $userid . "&id=" . $id . "&aid=" . $vals["id"] . "\"><img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"Edit\"></a>", "<a href=\"#\" onClick=\"doDeleteAddon('" . $vals["id"] . "');return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"Delete\"></a>");
    }
    $addonshtml = $aInt->sortableTable(array($aInt->lang("addons", "regdate"), $aInt->lang("addons", "name"), $aInt->lang("global", "pricing"), $aInt->lang("fields", "status"), $aInt->lang("fields", "nextduedate"), "", ""), $tabledata);
    $tbl->add($aInt->lang("addons", "title"), $addonshtml . "<div style=\"padding:5px 25px;\"><a href=\"clientsservices.php?userid=" . $userid . "&id=" . $id . "&aid=add\"><img src=\"images/icons/add.png\" border=\"0\" align=\"top\" /> " . $aInt->lang("addons", "addnew") . "</a></div>", 1);
    $customfields = getCustomFields("product", $packageid, $id, true);
    foreach ($customfields as $customfield) {
        $tbl->add($customfield["name"], $customfield["input"], 1);
    }
    $tbl->add($aInt->lang("fields", "subscriptionid"), "<div class=\"" . ($cancelSubscription ? "input-group" : "") . " input-500\" id=\"subscription\">\n      " . $frm->text("subscriptionid", $subscriptionid, "25", false, "form-control") . "\n      " . $cancelSubscription . "\n    </div>" . "<div id='subscriptionworking' style='display:none;text-align:center;'><img src='images/loader.gif' />" . "&nbsp; " . $aInt->lang("global", "working") . "</div>", true);
    $suspendValue = strpos($overidesuspenduntil, "0000") === false ? $overidesuspenduntil : "";
    $checkbox = $frm->checkbox("overideautosuspend", $aInt->lang("services", "nosuspenduntil"), $overideautosuspend) . " &nbsp;";
    $tbl->add(AdminLang::trans("services.overrideautosusp"), "<div class=\"form-group date-picker-prepend-icon\">\n    " . $checkbox . "\n    <label for=\"inputOverideSuspendUntil\" class=\"field-icon\">\n        <i class=\"fal fa-calendar-alt\"></i>\n    </label>\n    <input type=\"text\"\n           name=\"overidesuspenduntil\"\n           value=\"" . $suspendValue . "\"\n           class=\"form-control input-inline date-picker-single\"\n           id=\"inputOverideSuspendUntil\"\n    >\n</div>", 1);
    $tbl->add($aInt->lang("services", "endofcycle"), $frm->checkbox("autoterminateendcycle", $aInt->lang("services", "reason"), $autoterminateendcycle) . " " . $frm->text("autoterminatereason", $autoterminatereason, "60", false, "form-control input-inline input-400"), 1);
    $tbl->add($aInt->lang("fields", "adminnotes"), $frm->textarea("notes", $notes, "4", "100%", "form-control"), 1);
    echo $tbl->output();
    echo "\n<div class=\"btn-container\">\n    " . $frm->submit($aInt->lang("global", "savechanges"), "btn btn-primary") . "\n    " . $frm->reset($aInt->lang("global", "cancelchanges")) . "<br />\n    <a href=\"#\" data-toggle=\"modal\" data-target=\"#modalDelete\" style=\"color:#cc0000\"><strong>" . $aInt->lang("global", "delete") . "</strong></a>\n</div>";
    if ($moduleInterface->isApplicationLinkingEnabled() && $moduleInterface->isApplicationLinkSupported()) {
        $message = "<p>" . $aInt->lang("services", "manageAppLinks") . "</p>\n        <p class=\"text-center margin-top-bottom-20\">\n            <button type=\"button\" id=\"manageAppLinks-Create\" name=\"Create\" class=\"manageAppLinks btn btn-default\">\n                " . $aInt->lang("modulebuttons", "create") . "\n            </button>";
        if ($moduleInterface->functionExists("UpdateApplicationLink")) {
            $message .= "\n            <button type=\"button\" id=\"manageAppLinks-Update\" name=\"Update\" class=\"manageAppLinks btn btn-default\">\n                " . $aInt->lang("modulebuttons", "update") . "\n            </button>";
        }
        $message .= "\n            <button type=\"button\" id=\"manageAppLinks-Delete\" name=\"Delete\" class=\"manageAppLinks btn btn-default\">\n                " . $aInt->lang("global", "delete") . "\n            </button>\n        </p>\n        <div id=\"modalAjaxOutput\" class=\"alert alert-info hidden text-center\">\n            <i class=\"fas fa-spinner fa-spin\"></i> Communicating with server. Please wait...\n        </div>\n";
        echo $aInt->modal("manageAppLinks", $aInt->lang("modulebuttons", "manageAppLinks"), $message, array(array("title" => $aInt->lang("global", "cancel"))));
        $jQueryCode .= "\n        jQuery(\".manageAppLinks\").click(function() {\n            jQuery(\"#modalAjaxOutput\").addClass(\"alert-info\").removeClass(\"alert-success\").removeClass(\"alert-danger\").html(\"<i class=\\\"fas fa-spinner fa-spin\\\"></i> Communicating with server. Please wait...\");\n            if (!jQuery(\"#modalAjaxOutput\").is(\":visible\")) {\n                jQuery(\"#modalAjaxOutput\").hide().removeClass(\"hidden\").slideDown();\n            }\n\n            var appLinkAction = jQuery(this).attr(\"name\");\n\n            // Prevent the cancel buttons from\n            // affecting the modal's content.\n            if (appLinkAction !== undefined) {\n                WHMCS.http.jqClient.post(\n                    \"clientsservices.php\",\n                    {\n                        modop: \"manageapplinks\",\n                        command: appLinkAction,\n                        id: \"" . (int) $id . "\",\n                        token: \"" . generate_token("plain") . "\"\n                    },\n                    function(data) {\n                        if (data.success) {\n                            jQuery(\"#modalAjaxOutput\").addClass(\"alert-success\").removeClass(\"alert-info\").removeClass(\"alert-danger\").html(\"Action Completed Successfully!\");\n                        } else {\n                            jQuery(\"#modalAjaxOutput\").addClass(\"alert-danger\").removeClass(\"alert-info\").removeClass(\"alert-success\").html(\"Error: \" + data.errorMsg);\n                        }\n\n                    },\n                    \"json\"\n                );\n            }\n        })\n    ";
    }
}
echo $frm->close() . "\n\n<div class=\"contentbox\">\n<table align=\"center\"><tr><td>\n<strong>" . $aInt->lang("global", "sendmessage") . "</strong>\n</td><td>\n";
$frmsub = new WHMCS\Form("frm3");
echo $frmsub->form("clientsemails.php?userid=" . $userid);
echo $frmsub->hidden("action", "send");
echo $frmsub->hidden("type", "product");
echo $frmsub->hidden("id", $id);
$emailarr = array();
$emailarr[0] = $aInt->lang("emails", "newmessage");
$mailTemplates = WHMCS\Mail\Template::where("type", "=", "product")->where("language", "=", "")->orderBy("name")->get();
foreach ($mailTemplates as $template) {
    $emailarr[$template->id] = $template->custom ? array("#efefef", $template->name) : $template->name;
}
echo $frmsub->dropdown("messageID", $emailarr);
echo $frmsub->submit($aInt->lang("global", "sendmessage"), "btn btn-default btn-sm");
echo $frmsub->close();
echo "</td><td>";
if ($welcomeEmail != 0 && $module != "marketconnect") {
    $frmsub = new WHMCS\Form("frm4");
    echo $frmsub->form("clientsemails.php?userid=" . $userid);
    echo $frmsub->hidden("action", "send");
    echo $frmsub->hidden("type", "product");
    echo $frmsub->hidden("id", $id);
    echo $frmsub->hidden("messageID", $welcomeEmail);
    echo $frmsub->hidden("messagename", "defaultnewacc");
    echo $frmsub->submit($aInt->lang("emails", "senddefaultproductwelcome"), "btn btn-info btn-sm");
    echo $frmsub->close();
}
echo "</td></tr></table>\n</div>\n\n<form method=\"post\" action=\"whois.php\" target=\"_blank\" id=\"frmWhois\">\n<input type=\"hidden\" name=\"domain\" value=\"" . $domain . "\" />\n</form>\n";
$content = ob_get_contents();
ob_end_clean();
$modSuspendMessage = "";
if ($whmcs->get_req_var("ajaxupdate")) {
    $content = preg_replace("/(<form\\W[^>]*\\bmethod=('|\"|)POST('|\"|)\\b[^>]*>)/i", "\\1" . "\n" . generate_token(), $content);
    $aInt->jsonResponse(array("body" => $content));
} else {
    $modSuspendMessage = (string) $aInt->lang("services", "suspendsure") . "<br />\n<div class=\"margin-top-bottom-20 text-center\">\n    " . $aInt->lang("services", "suspensionreason") . ":\n    <input type=\"text\" id=\"suspreason\" class=\"form-control input-inline input-300\" /><br /><br />\n    <label class=\"checkbox-inline\">\n        <input type=\"checkbox\" id=\"suspemail\" />\n        " . $aInt->lang("services", "suspendsendemail") . "\n    </label>\n</div>";
    $unsuspendSure = AdminLang::trans("services.unsuspendsure");
    $unsuspendEmail = AdminLang::trans("automation.sendAutoUnsuspendEmail");
    $modUnsuspendMessage = (string) $unsuspendSure . "<br />\n<div class=\"margin-top-bottom-20 text-center\">\n    <label class=\"checkbox-inline\">\n        <input type=\"checkbox\" id=\"unsuspended_email\" />\n        " . $unsuspendEmail . "\n    </label>\n</div>";
    $content = "<div id=\"servicecontent\">" . $content . "</div>";
    $content .= $aInt->modal("ModuleCreate", $aInt->lang("services", "confirmcommand"), $aInt->lang("services", "createsure"), array(array("title" => $aInt->lang("global", "yes"), "onclick" => "runModuleCommand(\"create\")", "class" => "btn-primary"), array("title" => $aInt->lang("global", "no"))));
    $content .= $aInt->modal("ModuleRenew", AdminLang::trans("services.confirmcommand"), AdminLang::trans("services.renewSure"), array(array("title" => AdminLang::trans("global.yes"), "onclick" => "runModuleCommand(\"renew\")", "class" => "btn-primary"), array("title" => AdminLang::trans("global.no"))));
    $content .= $aInt->modal("ModuleSuspend", $aInt->lang("services", "confirmcommand"), $modSuspendMessage, array(array("title" => $aInt->lang("global", "yes"), "onclick" => "runModuleCommand(\"suspend\")", "class" => "btn-primary"), array("title" => $aInt->lang("global", "no"))));
    $content .= $aInt->modal("ModuleUnsuspend", AdminLang::trans("services.confirmcommand"), $modUnsuspendMessage, array(array("title" => $aInt->lang("global", "yes"), "onclick" => "runModuleCommand(\"unsuspend\")", "class" => "btn-primary"), array("title" => $aInt->lang("global", "no"))));
    $additional = "";
    if ($moduleInterface && $moduleInterface->getLoadedModule() == "cpanel") {
        $keep = AdminLang::trans("services.keepDnsZone") . " " . "(<a href='https://docs.whmcs.com/CPanel/WHM#Keep_DNS_Zone_on_Termination' class='autoLinked'>" . AdminLang::trans("global.learnMore") . "</a>)";
        $additional = "<br>\n<br>\n<label class=\"checkbox-inline\" for=\"inputKeepCPanelDnsZone\">\n    <input type=\"checkbox\" class=\"checkbox-inline\" id=\"inputKeepCPanelDnsZone\">\n    " . $keep . "\n</label>";
    }
    $content .= $aInt->modal("ModuleTerminate", AdminLang::trans("services.confirmcommand"), AdminLang::trans("services.terminatesure") . $additional, array(array("title" => AdminLang::trans("global.yes"), "onclick" => "runModuleCommand(\"terminate\")", "class" => "btn-primary"), array("title" => AdminLang::trans("global.no"))));
    $content .= $aInt->modal("ModuleChangePackage", $aInt->lang("services", "confirmcommand"), $aInt->lang("services", "chgpacksure"), array(array("title" => $aInt->lang("global", "yes"), "onclick" => "runModuleCommand(\"changepackage\")", "class" => "btn-primary"), array("title" => $aInt->lang("global", "no"))));
    $content .= $aInt->modal("Delete", $aInt->lang("services", "deleteproduct"), $aInt->lang("services", "proddeletesure"), array(array("title" => $aInt->lang("global", "yes"), "onclick" => "window.location=\"?userid=" . $userid . "&id=" . $id . "&action=delete" . generate_token("link") . "\"", "class" => "btn-primary"), array("title" => $aInt->lang("global", "no"))));
    $content .= $aInt->modal("CancelSubscription", $aInt->lang("services", "cancelSubscription"), $aInt->lang("services", "cancelSubscriptionSure"), array(array("title" => $aInt->lang("global", "yes"), "onclick" => "cancelSubscription()", "class" => "btn-primary"), array("title" => $aInt->lang("global", "no"))));
}
$aInt->jquerycode = $jQueryCode;
$aInt->content = $content;
$aInt->display();
function buildCustomModuleButtons($modulebtns, $adminbuttonarray)
{
    global $frm;
    global $id;
    global $userid;
    global $aid;
    if ($adminbuttonarray) {
        foreach ($adminbuttonarray as $displayLabel => $options) {
            if (is_array($options)) {
                $href = isset($options["href"]) ? $options["href"] : "?userid=" . $userid . "&id=" . $id;
                if ($aid) {
                    $href .= "&aid=" . $aid;
                }
                if (isset($options["customModuleAction"]) && $options["customModuleAction"]) {
                    $href .= "&modop=custom&ac=" . $options["customModuleAction"] . "&token=" . generate_token("plain");
                }
                $submitLabel = isset($options["submitLabel"]) ? $options["submitLabel"] : "";
                $submitId = isset($options["submitId"]) ? $options["submitId"] : "";
                $modalClass = isset($options["modalClass"]) ? $options["modalClass"] : "";
                $modalSize = isset($options["modalSize"]) ? $options["modalSize"] : "";
                $disabled = isset($options["disabled"]) && $options["disabled"] ? " disabled=\"disabled\"" : "";
                if ($disabled && isset($options["disabledTooltip"]) && $options["disabledTooltip"]) {
                    $disabled .= " data-toggle=\"tooltip\" data-placement=\"bottom\" title=\"" . $options["disabledTooltip"] . "\"";
                }
                if (isset($options["modal"]) && $options["modal"] === true) {
                    $modulebtns[] = "<a href=\"" . $href . "\" class=\"btn btn-default open-modal\" data-modal-title=\"" . $options["modalTitle"] . "\" data-modal-size=\"" . $modalSize . "\" data-modal-class=\"" . $modalClass . "\"" . $disabled . ($submitLabel ? " data-btn-submit-label=\"" . $submitLabel . "\" data-btn-submit-id=\"" . $submitId . "\"" : "") . ">" . $displayLabel . "</a>";
                } else {
                    $modulebtns[] = "<a href=\"" . $href . "\" class=\"btn btn-default" . $options["class"] . "\">" . $displayLabel . "</a>";
                }
            } else {
                $modulebtns[] = $frm->button($displayLabel, "runModuleCommand('custom','" . $options . "')");
            }
        }
    }
    return $modulebtns;
}

?>