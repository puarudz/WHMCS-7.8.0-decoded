<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("View Clients Summary", false);
$aInt->requiredFiles(array("clientfunctions", "processinvoices", "invoicefunctions", "gatewayfunctions", "affiliatefunctions", "modulefunctions"));
$aInt->setClientsProfilePresets();
$userId = (int) App::getFromRequest("userid");
try {
    $client = WHMCS\User\Client::findOrFail($userId);
    $userId = $client->id;
} catch (Exception $e) {
    $aInt->gracefulExit(AdminLang::trans("clients.invalidclientid"));
}
$client->migratePaymentDetailsIfRequired();
$whmcs = WHMCS\Application::getInstance();
if ($return) {
    unset($_SESSION["uid"]);
}
$aInt->assertClientBoundary($userid);
if ($action == "resendVerificationEmail") {
    check_token("WHMCS.admin.default");
    $client->sendEmailAddressVerification();
    WHMCS\Terminus::getInstance()->doExit();
} else {
    if ($action == "massaction") {
        check_token("WHMCS.admin.default");
        $queryStr = "userid=" . $userid . "&massaction=true";
        $serviceDetails = array("userid" => $userid, "serviceid" => "");
        $addonDetails = array("userid" => $userid, "id" => "", "serviceid" => "", "addonid" => "");
        $domainDetails = array("userid" => $userid, "domainid" => "");
        if ($inv) {
            checkPermission("Generate Due Invoices");
            $specificitems = array("products" => $selproducts, "addons" => $seladdons, "domains" => $seldomains);
            createInvoices($userid, "", "", $specificitems);
            $queryStr .= "&invoicecount=" . $invoicecount;
        }
        if ($del) {
            if ($selproducts) {
                checkPermission("Delete Clients Products/Services");
                foreach ($selproducts as $pid) {
                    $hosting = $client->services->find((int) $pid);
                    if ($hosting) {
                        $serviceDetails["serviceid"] = $hosting->id;
                        run_hook("ServiceDelete", $serviceDetails);
                        $hosting->delete();
                        $activityMessage = "Deleted Product/Service - User ID: " . $userId;
                        $activityMessage .= " - Service ID: " . $hosting->id;
                        logActivity($activityMessage, $userId);
                    }
                }
            }
            if ($seladdons) {
                checkPermission("Delete Clients Products/Services");
                foreach ($seladdons as $aid) {
                    $addon = WHMCS\Service\Addon::find((int) $aid);
                    $addonUserId = $addon->service->clientId;
                    if ($addonUserId == $userId) {
                        run_hook("AddonDeleted", array("id" => $addon->id));
                        $addon->delete();
                        logActivity("Deleted Addon ID: " . $addon->id . " - User ID: " . $userId, $userId);
                    }
                }
            }
            if ($seldomains) {
                checkPermission("Delete Clients Domains");
                foreach ($seldomains as $did) {
                    $domain = $client->domains->find((int) $did);
                    if ($domain) {
                        $domainDetails["domainid"] = $domain->id;
                        run_hook("DomainDelete", $domainDetails);
                        $domain->delete();
                        logActivity("Deleted Domain ID: " . $did . " - User ID: " . $userId, $userId);
                    }
                }
            }
            $queryStr .= "&deletesuccess=true";
        }
        if ($massupdate || $masscreate || $masssuspend || $massunsuspend || $massterminate || $masschangepackage || $masschangepw) {
            if ($paymentmethod) {
                $paymentmethod = get_query_val("tblpaymentgateways", "gateway", array("gateway" => $paymentmethod));
            }
            if ($proratabill) {
                checkPermission("Edit Clients Products/Services");
                $targetnextduedate = toMySQLDate($nextduedate);
                foreach ($selproducts as $serviceid) {
                    $data = get_query_vals("tblhosting", "packageid,domain,nextduedate,billingcycle,amount,paymentmethod", array("id" => $serviceid));
                    $existingpid = $data["packageid"];
                    $domain = $data["domain"];
                    $existingnextduedate = $data["nextduedate"];
                    $billingcycle = $data["billingcycle"];
                    $price = $data["amount"];
                    if (!$paymentmethod) {
                        $paymentmethod = $data["paymentmethod"];
                    }
                    if ($recurringamount) {
                        $price = $recurringamount;
                    }
                    $totaldays = getBillingCycleDays($billingcycle);
                    $timediff = WHMCS\Carbon::createFromFormat("Y-m-d", $targetnextduedate)->diffInDays(WHMCS\Carbon::createFromFormat("Y-m-d", $existingnextduedate));
                    $percent = $timediff / $totaldays;
                    $amountdue = format_as_currency($price * $percent);
                    $invdata = getInvoiceProductDetails($serviceid, $existingpid, "", "", $billingcycle, $domain, $userid);
                    $description = $invdata["description"] . " (" . fromMySQLDate($existingnextduedate) . " - " . $nextduedate . ")";
                    $tax = $invdata["tax"];
                    insert_query("tblinvoiceitems", array("userid" => $userid, "type" => "ProrataProduct" . $targetnextduedate, "relid" => $serviceid, "description" => $description, "amount" => $amountdue, "taxed" => $tax, "duedate" => "now()", "paymentmethod" => $paymentmethod));
                }
                foreach ($seladdons as $aid) {
                    $data = get_query_vals("tblhostingaddons", "hostingid,addonid,name,nextduedate,billingcycle,recurring,paymentmethod", array("id" => $aid));
                    $serviceid = $data["hostingid"];
                    $addonid = $data["addonid"];
                    $name = $data["name"];
                    $existingnextduedate = $data["nextduedate"];
                    $billingcycle = $data["billingcycle"];
                    $price = $data["recurring"];
                    if (!$paymentmethod) {
                        $paymentmethod = $data["paymentmethod"];
                    }
                    $domain = get_query_val("tblhosting", "domain", array("id" => $serviceid));
                    if ($recurringamount) {
                        $price = $recurringamount;
                    }
                    $totaldays = getBillingCycleDays($billingcycle);
                    $timediff = WHMCS\Carbon::createFromFormat("Y-m-d", $targetnextduedate)->diffInDays(WHMCS\Carbon::createFromFormat("Y-m-d", $existingnextduedate));
                    $percent = $timediff / $totaldays;
                    $amountdue = format_as_currency($price * $percent);
                    if ($domain) {
                        $domain = "(" . $domain . ") ";
                    }
                    $description = $_LANG["orderaddon"] . " " . $domain . "- ";
                    if ($name) {
                        $description .= $name;
                    } else {
                        $description .= get_query_val("tbladdons", "name", array("id" => $addonid));
                    }
                    $description .= " (" . fromMySQLDate($existingnextduedate) . " - " . $nextduedate . ")";
                    $tax = $invdata["tax"];
                    insert_query("tblinvoiceitems", array("userid" => $userid, "type" => "ProrataAddon" . $targetnextduedate, "relid" => $aid, "description" => $description, "amount" => $amountdue, "taxed" => $tax, "duedate" => "now()", "paymentmethod" => $paymentmethod));
                }
                createInvoices($userid);
            }
            $updateqry = array();
            if ($firstpaymentamount) {
                $updateqry["firstpaymentamount"] = $firstpaymentamount;
            }
            if ($recurringamount) {
                $updateqry["amount"] = $recurringamount;
            }
            if ($nextduedate && !$proratabill) {
                $updateqry["nextinvoicedate"] = toMySQLDate($nextduedate);
                $updateqry["nextduedate"] = $updateqry["nextinvoicedate"];
            }
            if ($billingcycle) {
                $updateqry["billingcycle"] = $billingcycle;
            }
            if ($paymentmethod) {
                $updateqry["paymentmethod"] = $paymentmethod;
            }
            if ($status) {
                $updateqry["domainstatus"] = $status;
            }
            if ($overideautosuspend) {
                $updateqry["overideautosuspend"] = "1";
                $updateqry["overidesuspenduntil"] = toMySQLDate($overidesuspenduntil);
            }
            if ($selproducts && count($updateqry)) {
                checkPermission("Edit Clients Products/Services");
                foreach ($selproducts as $pid) {
                    run_hook("PreServiceEdit", array("serviceid" => $pid));
                    update_query("tblhosting", $updateqry, array("id" => $pid));
                    $serviceDetails["serviceid"] = $pid;
                    run_hook("ServiceEdit", $serviceDetails);
                    run_hook("AdminServiceEdit", $serviceDetails);
                }
                logActivity("Mass Updated Products IDs: " . implode(",", $selproducts) . " - User ID: " . $userid, $userid);
            }
            unset($updateqry["amount"]);
            unset($updateqry["domainstatus"]);
            unset($updateqry["overideautosuspend"]);
            unset($updateqry["overidesuspenduntil"]);
            if ($status) {
                $updateqry["status"] = $status;
            }
            if ($seladdons) {
                $addonHook = "AddonEdit";
                unset($updateqry["firstpaymentamount"]);
                if ($recurringamount) {
                    $updateqry["recurring"] = $recurringamount;
                }
                if (count($updateqry)) {
                    checkPermission("Edit Clients Products/Services");
                    foreach ($seladdons as $aid) {
                        $addonData = get_query_vals("tblhostingaddons", "addonid, hostingid, status", array("id" => $aid));
                        $currentStatus = $addonData["status"];
                        if ($status && $currentStatus != $status) {
                            if ($currentStatus == "Suspended" && $status == "Active") {
                                $addonHook = "AddonUnsuspended";
                            } else {
                                if ($currentStatus != "Active" && $status == "Active") {
                                    $addonHook = "AddonActivated";
                                } else {
                                    if ($currentStatus != "Suspended" && $status == "Suspended") {
                                        $addonHook = "AddonSuspended";
                                    } else {
                                        if ($currentStatus != "Terminated" && $status == "Terminated") {
                                            $addonHook = "AddonTerminated";
                                        } else {
                                            if ($currentStatus != "Cancelled" && $status == "Cancelled") {
                                                $addonHook = "AddonCancelled";
                                            } else {
                                                if ($currentStatus != "Fraud" && $status == "Fraud") {
                                                    $addonHook = "AddonFraud";
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        $definedAddonID = $addonData["addonid"];
                        $addonServiceID = $addonData["hostingid"];
                        $addonDetails["addonid"] = $definedAddonID;
                        $addonDetails["id"] = $aid;
                        $addonDetails["serviceid"] = $addonServiceID;
                        update_query("tblhostingaddons", $updateqry, array("id" => $aid));
                        run_hook($addonHook, $addonDetails);
                    }
                    logActivity("Mass Updated Addons IDs: " . implode(",", $seladdons) . " - User ID: " . $userid, $userid);
                }
            }
            if ($seldomains) {
                unset($updateqry["recurring"]);
                unset($updateqry["billingcycle"]);
                if ($firstpaymentamount) {
                    $updateqry["firstpaymentamount"] = $firstpaymentamount;
                }
                if ($recurringamount) {
                    $updateqry["recurringamount"] = $recurringamount;
                }
                if ($billingcycle == "Annually") {
                    $updateqry["registrationperiod"] = "1";
                }
                if ($billingcycle == "Biennially") {
                    $updateqry["registrationperiod"] = "2";
                }
                if ($billingcycle == "Triennially") {
                    $updateqry["registrationperiod"] = "3";
                }
                if ($status == "Suspended" || $status == "Terminated") {
                    $updateqry["status"] = "Expired";
                }
                if (count($updateqry)) {
                    checkPermission("Edit Clients Domains");
                    foreach ($seldomains as $did) {
                        $domainDetails["domainid"] = $did;
                        run_hook("DomainEdit", $domainDetails);
                        update_query("tbldomains", $updateqry, array("id" => $did));
                    }
                    logActivity("Mass Updated Domains IDs: " . implode(",", $seldomains) . " - User ID: " . $userid, $userid);
                }
            }
            $moduleresults = array();
            if ($masscreate) {
                checkPermission("Perform Server Operations");
                foreach ($selproducts as $serviceid) {
                    $modresult = ServerCreateAccount($serviceid);
                    if ($modresult != "success") {
                        $moduleresults[] = "Service ID " . $serviceid . ": " . $modresult;
                    } else {
                        $moduleresults[] = "Service ID " . $serviceid . ": " . $aInt->lang("services", "createsuccess");
                    }
                }
                foreach ($seladdons as $addonUniqueId) {
                    $moduleAutomation = WHMCS\Service\Automation\AddonAutomation::factory($addonUniqueId);
                    if (!$moduleAutomation->runAction("CreateAccount")) {
                        $moduleresults[] = "Addon ID: " . $addonUniqueId . ": " . $moduleAutomation->getError();
                    } else {
                        $moduleresults[] = "Addon ID: " . $addonUniqueId . ": " . AdminLang::trans("services.createsuccess");
                    }
                }
            }
            if ($masssuspend) {
                checkPermission("Perform Server Operations");
                foreach ($selproducts as $serviceid) {
                    $modresult = ServerSuspendAccount($serviceid);
                    if ($modresult != "success") {
                        $moduleresults[] = "Service ID " . $serviceid . ": " . $modresult;
                    } else {
                        $moduleresults[] = "Service ID " . $serviceid . ": " . $aInt->lang("services", "suspendsuccess");
                    }
                }
                foreach ($seladdons as $addonUniqueId) {
                    $moduleAutomation = WHMCS\Service\Automation\AddonAutomation::factory($addonUniqueId);
                    if (!$moduleAutomation->runAction("SuspendAccount")) {
                        $moduleresults[] = "Addon ID: " . $addonUniqueId . ": " . $moduleAutomation->getError();
                    } else {
                        $moduleresults[] = "Addon ID: " . $addonUniqueId . ": " . AdminLang::trans("services.suspendsuccess");
                    }
                }
            }
            if ($massunsuspend) {
                checkPermission("Perform Server Operations");
                foreach ($selproducts as $serviceid) {
                    $modresult = ServerUnsuspendAccount($serviceid);
                    if ($modresult != "success") {
                        $moduleresults[] = "Service ID " . $serviceid . ": " . $modresult;
                    } else {
                        $moduleresults[] = "Service ID " . $serviceid . ": " . $aInt->lang("services", "unsuspendsuccess");
                    }
                }
                foreach ($seladdons as $addonUniqueId) {
                    $moduleAutomation = WHMCS\Service\Automation\AddonAutomation::factory($addonUniqueId);
                    if (!$moduleAutomation->runAction("UnsuspendAccount")) {
                        $moduleresults[] = "Addon ID: " . $addonUniqueId . ": " . $moduleAutomation->getError();
                    } else {
                        $moduleresults[] = "Addon ID: " . $addonUniqueId . ": " . AdminLang::trans("services.unsuspendsuccess");
                    }
                }
            }
            if ($massterminate) {
                checkPermission("Perform Server Operations");
                foreach ($selproducts as $serviceid) {
                    $modresult = ServerTerminateAccount($serviceid);
                    if ($modresult != "success") {
                        $moduleresults[] = "Service ID " . $serviceid . ": " . $modresult;
                    } else {
                        $moduleresults[] = "Service ID " . $serviceid . ": " . $aInt->lang("services", "terminatesuccess");
                    }
                }
                foreach ($seladdons as $addonUniqueId) {
                    $moduleAutomation = WHMCS\Service\Automation\AddonAutomation::factory($addonUniqueId);
                    if (!$moduleAutomation->runAction("TerminateAccount")) {
                        $moduleresults[] = "Addon ID: " . $addonUniqueId . ": " . $moduleAutomation->getError();
                    } else {
                        $moduleresults[] = "Addon ID: " . $addonUniqueId . ": " . AdminLang::trans("services.terminatesuccess");
                    }
                }
            }
            if ($masschangepackage) {
                checkPermission("Perform Server Operations");
                foreach ($selproducts as $serviceid) {
                    $modresult = ServerChangePackage($serviceid);
                    if ($modresult != "success") {
                        $moduleresults[] = "Service ID " . $serviceid . ": " . $modresult;
                    } else {
                        $moduleresults[] = "Service ID " . $serviceid . ": " . $aInt->lang("services", "updownsuccess");
                    }
                }
                foreach ($seladdons as $addonUniqueId) {
                    $moduleAutomation = WHMCS\Service\Automation\AddonAutomation::factory($addonUniqueId);
                    if (!$moduleAutomation->runAction("ChangePackage")) {
                        $moduleresults[] = "Addon ID: " . $addonUniqueId . ": " . $moduleAutomation->getError();
                    } else {
                        $moduleresults[] = "Addon ID: " . $addonUniqueId . ": " . AdminLang::trans("services.updownsuccess");
                    }
                }
            }
            if ($masschangepw) {
                checkPermission("Perform Server Operations");
                foreach ($selproducts as $serviceid) {
                    $modresult = ServerChangePassword($serviceid);
                    if ($modresult != "success") {
                        $moduleresults[] = "Service ID " . $serviceid . ": " . $modresult;
                    } else {
                        $moduleresults[] = "Service ID " . $serviceid . ": " . $aInt->lang("services", "pwchangesuccess");
                    }
                }
                foreach ($seladdons as $addonUniqueId) {
                    $moduleAutomation = WHMCS\Service\Automation\AddonAutomation::factory($addonUniqueId);
                    if (!$moduleAutomation->runAction("ChangePassword")) {
                        $moduleresults[] = "Addon ID: " . $addonUniqueId . ": " . $moduleAutomation->getError();
                    } else {
                        $moduleresults[] = "Addon ID: " . $addonUniqueId . ": " . AdminLang::trans("services.pwchangesuccess");
                    }
                }
            }
            WHMCS\Cookie::set("moduleresults", $moduleresults);
            $queryStr .= "&massupdatecomplete=true";
        }
        redir($queryStr);
    }
}
if ($action == "uploadfile") {
    check_token("WHMCS.admin.default");
    checkPermission("Manage Clients Files");
    foreach (WHMCS\File\Upload::getUploadedFiles("uploadfile") as $uploadedFile) {
        try {
            $filename = $uploadedFile->storeAsClientFile();
        } catch (Exception $e) {
            $aInt->gracefulExit("Could not save file: " . $e->getMessage());
        }
        if (!$title) {
            $title = $uploadedFile->getCleanName();
        }
        $params = array("userid" => $userid, "title" => $title, "filename" => $filename, "adminonly" => $adminonly);
        run_hook("AdminClientFileUpload", array_merge($params, array("origfilename" => $uploadedFile->getCleanName())));
        insert_query("tblclientsfiles", array_merge($params, array("dateadded" => "now()")));
        logActivity("Added Client File - Title: " . $title . " - User ID: " . $userid, $userid);
    }
    redir("userid=" . $userid);
}
if ($action == "deletefile") {
    check_token("WHMCS.admin.default");
    checkPermission("Manage Clients Files");
    $id = (int) $whmcs->get_req_var("id");
    $result = select_query("tblclientsfiles", "", array("id" => $id, "userid" => $userId));
    $data = mysql_fetch_array($result);
    $id = $data["id"];
    if (!$id) {
        $aInt->gracefulExit("Invalid File to Delete");
    }
    $title = $data["title"];
    $filename = $data["filename"];
    try {
        Storage::clientFiles()->deleteAllowNotPresent($filename);
    } catch (Exception $e) {
        $aInt->gracefulExit("Could not delete file: " . htmlentities($e->getMessage()));
    }
    delete_query("tblclientsfiles", array("id" => $id, "userid" => $userId));
    logActivity("Deleted Client File - Title: " . $title . " - User ID: " . $userid, $userid);
    redir("userid=" . $userid);
}
if ($action == "closeclient") {
    check_token("WHMCS.admin.default");
    checkPermission("Edit Clients Details");
    checkPermission("Edit Clients Products/Services");
    checkPermission("Edit Clients Domains");
    checkPermission("Manage Invoice");
    closeClient($userid);
    redir("userid=" . $userid);
}
if ($action == "deleteclient") {
    check_token("WHMCS.admin.default");
    checkPermission("Delete Client");
    run_hook("ClientDelete", array("userid" => $userid));
    deleteClient($userid);
    redir("", "clients.php");
}
if ($action == "savenotes") {
    check_token("WHMCS.admin.default");
    checkPermission("Edit Clients Details");
    update_query("tblclients", array("notes" => $adminnotes), array("id" => $userid));
    logActivity("Client Summary Notes Updated - User ID: " . $userid, $userid);
    redir("userid=" . $userid);
}
if ($action == "addfunds") {
    check_token("WHMCS.admin.default");
    checkPermission("Create Invoice");
    $addfundsamt = round($addfundsamt, 2);
    if (0 < $addfundsamt) {
        $invoiceid = createInvoices($userid);
        $paymentmethod = getClientsPaymentMethod($userid);
        insert_query("tblinvoiceitems", array("userid" => $userid, "type" => "AddFunds", "relid" => "", "description" => $_LANG["addfunds"], "amount" => $addfundsamt, "taxed" => "0", "duedate" => "now()", "paymentmethod" => $paymentmethod));
        $invoiceid = createInvoices($userid, "", true);
        redir("userid=" . $userid . "&addfunds=true&invoiceid=" . $invoiceid);
    } else {
        redir("userid=" . $userid);
    }
}
if ($generateinvoices) {
    check_token("WHMCS.admin.default");
    checkPermission("Generate Due Invoices");
    $invoiceid = createInvoices($userid, $noemails);
    $_SESSION["adminclientgeninvoicescount"] = $invoicecount;
    redir("userid=" . $userid . "&geninvoices=true");
}
if ($activateaffiliate) {
    check_token("WHMCS.admin.default");
    affiliateActivate($userid);
    redir("userid=" . $userid . "&affactivated=true");
}
if ($resetpw) {
    check_token("WHMCS.admin.default");
    sendMessage("Automated Password Reset", $userid);
    redir("userid=" . $userid . "&pwreset=true");
}
if ($whmcs->get_req_var("csajaxtoggle")) {
    check_token("WHMCS.admin.default");
    if (!checkPermission("Edit Clients Details", true)) {
        throw new WHMCS\Exception\ProgramExit("Permission Denied");
    }
    switch ($whmcs->get_req_var("csajaxtoggle")) {
        case "autocc":
            $fieldName = "disableautocc";
            break;
        case "taxstatus":
            $fieldName = "taxexempt";
            break;
        case "overduenotices":
            $fieldName = "overideduenotices";
            break;
        case "latefees":
            $fieldName = "latefeeoveride";
            break;
        case "splitinvoices":
            $fieldName = "separateinvoices";
            break;
        default:
            throw new WHMCS\Exception\ProgramExit("Invalid Toggle Value");
    }
    $csajaxtoggleval = get_query_val("tblclients", $fieldName, array("id" => $userid));
    if ($csajaxtoggleval == "1") {
        update_query("tblclients", array($fieldName => 0), array("id" => $userid));
        if ($fieldName == "taxexempt") {
            echo "<strong class=\"textred\">" . $aInt->lang("global", "no") . "</strong>";
        } else {
            echo "<strong class=\"textgreen\">" . $aInt->lang("global", "yes") . "</strong>";
        }
    } else {
        update_query("tblclients", array($fieldName => 1), array("id" => $userid));
        if ($fieldName == "taxexempt") {
            echo "<strong class=\"textgreen\">" . $aInt->lang("global", "yes") . "</strong>";
        } else {
            echo "<strong class=\"textred\">" . $aInt->lang("global", "no") . "</strong>";
        }
    }
    exit;
}
WHMCS\Session::release();
$legacyClient = new WHMCS\Client($client);
$clientsdetails = $legacyClient->getDetails();
$currency = getCurrency($userid);
$aInt->deleteJSConfirm("deleteFile", "clientsummary", "filedeletesure", "?userid=" . $userid . "&action=deletefile&id=");
$jscode = "function closeClient() {\nif (confirm(\"" . $aInt->lang("clients", "closesure") . "\")) {\nwindow.location='?userid=" . $userid . "&action=closeclient" . generate_token("link") . "';\n}}\nfunction deleteClient() {\nif (confirm(\"" . $aInt->lang("clients", "deletesure") . "\")) {\nwindow.location='?userid=" . $userid . "&action=deleteclient" . generate_token("link") . "';\n}}";
$jquerycode = "\$(\"#addfile\").click(function () {\n    \$(\"#addfileform\").slideToggle();\n    return false;\n});\n\$(\".csajaxtoggle\").click(function () {\n    var csturl = \"clientssummary.php?userid=" . $userid . "&csajaxtoggle=\"+\$(this).attr(\"id\")+\"" . generate_token("link") . "\";\n    var cstelm = \"#\"+\$(this).attr(\"id\");\n    WHMCS.http.jqClient.get(csturl, function(data){\n         \$(cstelm).html(data);\n    });\n});\n";
ob_start();
if ($geninvoices) {
    infoBox($aInt->lang("invoices", "gencomplete"), (int) $_SESSION["adminclientgeninvoicescount"] . " Invoices Created");
}
if ($addfunds) {
    infoBox($aInt->lang("clientsummary", "createaddfunds"), $aInt->lang("clientsummary", "createaddfundssuccess") . " - <a href=\"invoices.php?action=edit&id=" . (int) $invoiceid . "\">" . $aInt->lang("fields", "invoicenum") . $invoiceid . "</a>");
}
if ($pwreset) {
    infoBox($aInt->lang("clients", "resetsendpassword"), $aInt->lang("clients", "passwordsuccess"));
}
if ($affactivated) {
    infoBox($aInt->lang("clientsummary", "activateaffiliate"), $aInt->lang("clientsummary", "affiliateactivatesuccess"));
}
$massaction = $whmcs->get_req_var("massaction");
if ($massaction) {
    $deletesuccess = $whmcs->get_req_var("deletesuccess");
    $invoicecount = $whmcs->get_req_var("invoicecount");
    $massupdatecomplete = $whmcs->get_req_var("massupdatecomplete");
    if ($deletesuccess) {
        infoBox($aInt->lang("global", "success"), $aInt->lang("clientsummary", "deletesuccess"));
    } else {
        if (0 < strlen(trim($invoicecount))) {
            infoBox($aInt->lang("invoices", "gencomplete"), $invoicecount . " Invoices Created");
        } else {
            if ($massupdatecomplete) {
                $moduleresults = WHMCS\Cookie::get("moduleresults", true);
                WHMCS\Cookie::delete("moduleresults");
                infoBox($aInt->lang("clientsummary", "massupdcomplete"), $aInt->lang("clientsummary", "modifysuccess") . "<br />" . implode("<br />", $moduleresults));
            }
        }
    }
}
echo $infobox;
$clientstats = getClientsStats($userid, $legacyClient->getClientModel());
$clientsdetails["status"] = $aInt->lang("status", strtolower($clientsdetails["status"]));
$clientsdetails["autocc"] = $clientsdetails["disableautocc"] ? $aInt->lang("global", "no") : $aInt->lang("global", "yes");
$clientsdetails["taxstatus"] = $clientsdetails["taxexempt"] ? $aInt->lang("global", "yes") : $aInt->lang("global", "no");
$clientsdetails["overduenotices"] = $clientsdetails["overideduenotices"] ? $aInt->lang("global", "no") : $aInt->lang("global", "yes");
$clientsdetails["latefees"] = $clientsdetails["latefeeoveride"] ? $aInt->lang("global", "no") : $aInt->lang("global", "yes");
$clientsdetails["splitinvoices"] = $clientsdetails["separateinvoices"] ? $aInt->lang("global", "yes") : $aInt->lang("global", "no");
$verifyEmailAddressEnabled = WHMCS\Config\Setting::getValue("EnableEmailVerification");
$emailVerificationPending = false;
$isEmailAddressVerified = $client->isEmailAddressVerified();
if ($verifyEmailAddressEnabled && !$isEmailAddressVerified) {
    $emailVerificationPending = true;
    $jquerycode .= "\n        jQuery('#btnResendVerificationEmail').click(function() {\n            WHMCS.http.jqClient.post('" . $whmcs->getPhpSelf() . "',\n                {\n                    'token': '" . generate_token("plain") . "',\n                    'action': 'resendVerificationEmail',\n                    'userid': '" . $userid . "'\n                }).done(function(data) {\n                    jQuery('#btnResendVerificationEmail').prop('disabled', true).text('" . $aInt->lang("global", "emailSent") . "');\n                });\n        });\n    ";
}
$templatevars["emailVerificationEnabled"] = $verifyEmailAddressEnabled;
$templatevars["emailVerificationPending"] = $emailVerificationPending;
$templatevars["emailVerified"] = $isEmailAddressVerified;
$templatevars["showTaxIdField"] = WHMCS\Billing\Tax\Vat::isUsingNativeField();
$clientsdetails["phonenumber"] = $clientsdetails["telephoneNumber"];
$templatevars["clientsdetails"] = $clientsdetails;
$countries = new WHMCS\Utility\Country();
$templatevars["clientsdetails"]["countrylong"] = $countries->getName($clientsdetails["country"]);
$result = select_query("tblcontacts", "", array("userid" => $userid));
$contacts = array();
while ($data = mysql_fetch_array($result)) {
    $contacts[] = array("id" => $data["id"], "firstname" => $data["firstname"], "lastname" => $data["lastname"], "email" => $data["email"]);
}
$templatevars["contacts"] = $contacts;
$groupname = $groupcolour = "";
if ($clientsdetails["groupid"]) {
    $result = select_query("tblclientgroups", "", array("id" => $clientsdetails["groupid"]));
    $data = mysql_fetch_array($result);
    $groupname = $data["groupname"];
    $groupcolour = $data["groupcolour"];
}
if (!$groupname) {
    $groupname = $aInt->lang("global", "none");
}
$templatevars["clientgroup"] = array("name" => $groupname, "colour" => $groupcolour);
$result = select_query("tblclients", "", array("id" => $userid));
$data = mysql_fetch_array($result);
$datecreated = $data["datecreated"];
$templatevars["signupdate"] = fromMySQLDate($datecreated);
if ($datecreated == "0000-00-00") {
    $clientfor = "Unknown";
} else {
    $todaysdate = date("Ymd");
    $datecreated = strtotime($datecreated);
    $todaysdate = strtotime($todaysdate);
    $days = round(($datecreated - $todaysdate) / 86400);
    $clientfor = ceil($days / 30 * -1);
    if ($clientfor <= 0) {
        $clientfor = 0;
    }
    $clientfor .= " " . $aInt->lang("billableitems", "months");
}
$templatevars["clientfor"] = $clientfor;
if ($clientsdetails["lastlogin"]) {
    $templatevars["lastlogin"] = $clientsdetails["lastlogin"];
} else {
    $templatevars["lastlogin"] = $aInt->lang("global", "none");
}
$templatevars["stats"] = $clientstats;
$templatevars["paymethodsSummary"] = (new WHMCS\Admin\Client\PayMethod\ViewHelper($aInt))->clientProfileSummaryHtml($client);
$result = select_query("tblemails", "", array("userid" => $userid), "id", "DESC", "0,5");
$lastfivemail = array();
while ($data = mysql_fetch_array($result)) {
    $lastfivemail[] = array("id" => (int) $data["id"], "date" => WHMCS\Input\Sanitize::makeSafeForOutput(fromMySQLDate($data["date"], "time")), "subject" => $data["subject"] ? WHMCS\Input\Sanitize::makeSafeForOutput($data["subject"]) : $aInt->lang("emails", "nosubject"));
}
$templatevars["lastfivemail"] = $lastfivemail;
$result = select_query("tblaffiliates", "", array("clientid" => $userid));
$data = mysql_fetch_array($result);
$affid = $data["id"];
$templatevars["affiliateid"] = $affid;
if ($affid) {
    $templatevars["afflink"] = "<a href=\"affiliates.php?action=edit&id=" . $affid . "\">" . $aInt->lang("clientsummary", "viewaffiliate") . "</a><br /><br />";
} else {
    $templatevars["afflink"] = "<a href=\"clientssummary.php?userid=" . $userid . "&activateaffiliate=true\">" . $aInt->lang("clientsummary", "activateaffiliate") . "</a><br /><br />";
}
$templatevars["messages"] = "<select name=\"messageID\" class=\"form-control select-inline\"><option value=\"0\">" . $aInt->lang("global", "newmessage") . "</option>";
$mailTemplates = WHMCS\Mail\Template::where("type", "=", "general")->where("disabled", 0)->where("language", "=", "")->where("name", "!=", "Password Reset Validation")->orderBy("name")->get();
foreach ($mailTemplates as $template) {
    $templatevars["messages"] .= "<option value=\"" . $template->id . "\"";
    if ($template->custom) {
        $templatevars["messages"] .= " style=\"background-color:#efefef\"";
    }
    $templatevars["messages"] .= ">" . $template->name . "</option>";
}
$templatevars["messages"] .= "</select>";
$recordsfound = "";
$itemStatuses = array("Pending" => $aInt->lang("status", "pending"), "Pending Registration" => $aInt->lang("status", "pendingregistration"), "Pending Transfer" => $aInt->lang("status", "pendingtransfer"), "Active" => $aInt->lang("status", "active"), "Completed" => AdminLang::trans("status.completed"), "Suspended" => $aInt->lang("status", "suspended"), "Terminated" => $aInt->lang("status", "terminated"), "Cancelled" => $aInt->lang("status", "cancelled"), "Grace" => AdminLang::trans("status.grace"), "Redemption" => AdminLang::trans("status.redemption"), "Expired" => $aInt->lang("status", "expired"), "Transferred Away" => AdminLang::trans("status.transferredaway"), "Fraud" => $aInt->lang("status", "fraud"));
$templatevars["itemstatuses"] = $itemStatuses;
$jscode .= "function applyStatusFilter() {\n    var statusFiltersCount = jQuery(\"input[name='statusfilter[]']\").length;\n    var statusFilters = {};\n    var allChecked = true;\n\n    jQuery(\"input[name='statusfilter[]']:checkbox\").each(function(){\n        var checked = jQuery(this).is(':checked');\n        statusFilters[jQuery(this).val()] = checked;\n\n        if (!checked) {\n            allChecked = false;\n        }\n    });\n\n    var filterableTables = jQuery('.filterable');\n    var statusCells = filterableTables.find('td.status');\n\n    statusCells.parent('tr').slideDown('fast');\n\n    statusCells.each(function (index) {\n        if (!statusFilters[jQuery(this).attr('data-filter-value')]) {\n            jQuery(this).parent('tr').slideUp('fast', function() {\n                /**\n                 * When hiding a status, deselect all selected items\n                 */\n                jQuery(this).find('input.checkprods').prop('checked', false).end();\n            });\n        }\n    });\n    if(typeof(Storage) !== \"undefined\") {\n        localStorage.setItem(\"whmcs_clientsummary_status_filter\", JSON.stringify(statusFilters));\n    }\n    if (allChecked) {\n        jQuery('#statusfiltercheckall').prop('checked', true);\n        checkAllStatusFilter();\n        jQuery('#btnStatusEnabled')\n            .find('span.on').hide().end()\n            .find('span.off').show().end()\n            .removeClass('btn-success')\n    } else {\n        uncheckCheckAllStatusFilter();\n        jQuery('#btnStatusEnabled')\n            .find('span.off').hide().end()\n            .find('span.on').show().end()\n            .addClass('btn-success');\n    }\n}\nfunction checkAllStatusFilter() {\n    \$(\"#statusfilter\").find(\"input:checkbox\").attr(\"checked\", \$(\"#statusfiltercheckall\").prop(\"checked\"));\n}\nfunction uncheckCheckAllStatusFilter() {\n    \$(\"#statusfiltercheckall\").attr(\"checked\", false);\n}\nfunction toggleStatusFilter() {\n    \$(\"#statusfilter\").fadeToggle();\n}";
$jquerycode .= "jQuery('#statusfiltercheckall').change(function() {\n    jQuery('#statusfilter').find(\"input:checkbox\").prop('checked', jQuery(this).prop('checked'));\n});\n\nif(typeof(Storage) !== \"undefined\") {\n    var statusFilters = jQuery(\"input[name='statusfilter[]']\");\n    var allChecked = true;\n    savedFilter = localStorage.getItem(\"whmcs_clientsummary_status_filter\");\n    if (savedFilter && typeof(savedFilter) !== \"undefined\") {\n        try {\n            savedFilter = JSON.parse(savedFilter);\n\n            jQuery(statusFilters).each(function () {\n                var status = jQuery(this).val();\n\n                /*\n                 * Do not invalidate filter when a new checkbox is added, but assume new checkboxes\n                 * are checked.\n                 */\n                if (savedFilter.hasOwnProperty(status) && !savedFilter[status]) {\n                    jQuery(this).prop('checked', false);\n                    allChecked = false;\n                }\n            });\n        } catch (e) {\n        }\n        if (allChecked) {\n            checkAllStatusFilter();\n        } else {\n            uncheckCheckAllStatusFilter();\n        }\n    }\n    applyStatusFilter();\n}";
$productsummary = array();
$result = select_query("tblhosting", "tblhosting.*,tblproducts.name", "userid=" . (int) $userid, "tblhosting`.`id", "DESC", "", "tblproducts ON tblproducts.id=tblhosting.packageid");
while ($data = mysql_fetch_array($result)) {
    $id = $data["id"];
    $regdate = $data["regdate"];
    $domain = $data["domain"];
    $dpackage = $data["name"];
    $dpaymentmethod = $data["paymentmethod"];
    $amount = formatCurrency($data["amount"]);
    $billingcycle = $data["billingcycle"];
    $nextduedate = $data["nextduedate"];
    $status = $data["domainstatus"];
    $regdate = fromMySQLDate($regdate);
    $nextduedate = fromMySQLDate($nextduedate);
    if ($billingcycle == "One Time" || $billingcycle == "Free Account") {
        $nextduedate = "-";
        $amount = formatCurrency($data["firstpaymentamount"]);
    }
    if ($domain == "") {
        $domain = "(" . $aInt->lang("addons", "nodomain") . ")";
    }
    $billingcycle = $aInt->lang("billingcycles", str_replace(array("-", "account", " "), "", strtolower($billingcycle)));
    $translatedStatus = $aInt->lang("status", strtolower($status));
    $productsummary[] = array("id" => $id, "idshort" => ltrim($id, "0"), "regdate" => $regdate, "domain" => $domain, "dpackage" => $dpackage, "dpaymentmethod" => $dpaymentmethod, "amount" => $amount, "dbillingcycle" => $billingcycle, "nextduedate" => $nextduedate, "domainstatus" => $translatedStatus, "domainoriginalstatus" => $status);
}
$templatevars["productsummary"] = $productsummary;
$predefinedaddons = array();
$result = select_query("tbladdons", "", "");
while ($data = mysql_fetch_array($result)) {
    $addon_id = $data["id"];
    $addon_name = $data["name"];
    $predefinedaddons[$addon_id] = $addon_name;
}
$result = select_query("tblhostingaddons", "tblhostingaddons.*,tblhostingaddons.id AS aid,tblhostingaddons.name AS addonname,tblhosting.id AS hostingid,tblhosting.domain,tblproducts.name", "tblhosting.userid=" . (int) $userid, "tblhosting`.`id", "DESC", "", "tblhosting ON tblhosting.id=tblhostingaddons.hostingid INNER JOIN tblproducts ON tblproducts.id=tblhosting.packageid");
$addonsummary = array();
while ($data = mysql_fetch_array($result)) {
    $id = $data["aid"];
    $hostingid = $data["hostingid"];
    $addonid = $data["addonid"];
    $regdate = $data["regdate"];
    $domain = $data["domain"];
    $addonname = $data["addonname"];
    $dpackage = $data["name"];
    $dpaymentmethod = $data["paymentmethod"];
    $amount = formatCurrency($data["recurring"]);
    $billingcycle = $data["billingcycle"];
    $nextduedate = $data["nextduedate"];
    if (!$addonname) {
        $addonname = $predefinedaddons[$addonid];
    }
    $regdate = fromMySQLDate($regdate);
    $nextduedate = fromMySQLDate($nextduedate);
    if ($dbillingcycle == "One Time" || $dbillingcycle == "Free Account") {
        $nextduedate = "-";
    }
    $status = $data["status"];
    if (!$domain) {
        $domain = "(" . $aInt->lang("addons", "nodomain") . ")";
    }
    $billingcycle = $aInt->lang("billingcycles", str_replace(array("-", "account", " "), "", strtolower($billingcycle)));
    $translatedStatus = $aInt->lang("status", strtolower($status));
    $addonsummary[] = array("id" => $id, "idshort" => ltrim($id, "0"), "hostingid" => $hostingid, "serviceid" => $hostingid, "regdate" => $regdate, "domain" => $domain, "addonname" => $addonname, "dpackage" => $dpackage, "dpaymentmethod" => $dpaymentmethod, "amount" => $amount, "dbillingcycle" => $billingcycle, "nextduedate" => $nextduedate, "status" => $translatedStatus, "originalstatus" => $status);
}
$templatevars["addonsummary"] = $addonsummary;
$domainsummary = array();
$result = select_query("tbldomains", "", "userid=" . (int) $userid, "id", "DESC");
while ($data = mysql_fetch_array($result)) {
    $id = $data["id"];
    $domain = $data["domain"];
    $registrar = ucfirst($data["registrar"]);
    $registrationdate = $data["registrationdate"];
    $nextduedate = $data["nextduedate"];
    $expirydate = $data["expirydate"];
    $status = $data["status"];
    $registrationdate = fromMySQLDate($registrationdate);
    $nextduedate = fromMySQLDate($nextduedate);
    $expirydate = fromMySQLDate($expirydate);
    $translatedStatus = $aInt->lang("status", strtolower(str_replace(" ", "", $status)));
    $domainsummary[] = array("id" => $id, "idshort" => ltrim($id, "0"), "domain" => $domain, "registrar" => $registrar, "registrationdate" => $registrationdate, "nextduedate" => $nextduedate, "expirydate" => $expirydate, "status" => $translatedStatus, "originalstatus" => $status);
}
$templatevars["domainsummary"] = $domainsummary;
$where = array();
$where["validuntil"] = array("sqltype" => ">", "value" => date("Ymd"));
$where["userid"] = $userid;
$result = select_query("tblquotes", "", $where);
$quotes = array();
while ($data = mysql_fetch_assoc($result)) {
    $id = $data["id"];
    $subject = $data["subject"];
    $datecreated = $data["datecreated"];
    $validuntil = $data["validuntil"];
    $stage = $data["stage"];
    $total = formatCurrency($data["total"]);
    $datecreated = fromMySQLDate($datecreated);
    $validuntil = fromMySQLDate($validuntil);
    $quotes[] = array("id" => $id, "idshort" => ltrim($id, "0"), "datecreated" => $datecreated, "subject" => $subject, "stage" => $stage, "total" => $total, "validuntil" => $validuntil);
}
$templatevars["quotes"] = $quotes;
$result = select_query("tblclientsfiles", "", array("userid" => $userid), "title", "ASC");
while ($data = mysql_fetch_array($result)) {
    $id = $data["id"];
    $title = $data["title"];
    $adminonly = $data["adminonly"];
    $dateadded = $data["dateadded"];
    $dateadded = fromMySQLDate($dateadded);
    $files[] = array("id" => $id, "title" => $title, "adminonly" => $adminonly, "date" => $dateadded);
}
$templatevars["files"] = $files;
$paymentmethoddropdown = paymentMethodsSelection("- " . $aInt->lang("global", "nochange") . " -");
$templatevars["paymentmethoddropdown"] = $paymentmethoddropdown;
$markup = new WHMCS\View\Markup\Markup();
$templatevars["notes"] = array();
$result = select_query("tblnotes", "tblnotes.*,(SELECT CONCAT(firstname,' ',lastname) FROM tbladmins WHERE tbladmins.id=tblnotes.adminid) AS adminuser", array("userid" => $userid, "sticky" => "1"), "modified", "DESC");
while ($data = mysql_fetch_assoc($result)) {
    $markupFormat = $markup->determineMarkupEditor("client_note", "", $data["modified"]);
    $data["note"] = $markup->transform($data["note"], $markupFormat);
    $data["created"] = fromMySQLDate($data["created"], 1);
    $data["modified"] = fromMySQLDate($data["modified"], 1);
    $templatevars["notes"][] = $data;
}
$addons_html = run_hook("AdminAreaClientSummaryPage", array("userid" => $userid));
$templatevars["addons_html"] = $addons_html;
$tmplinks = run_hook("AdminAreaClientSummaryActionLinks", array("userid" => $userid));
$actionlinks = array();
foreach ($tmplinks as $tmplinks2) {
    foreach ($tmplinks2 as $tmplinks3) {
        $actionlinks[] = $tmplinks3;
    }
}
$templatevars["customactionlinks"] = $actionlinks;
$templatevars["tokenvar"] = generate_token("link");
$templatevars["csrfToken"] = generate_token("plain");
$aInt->templatevars = $templatevars;
$aInt->populateStandardAdminSmartyVariables();
if ($whmcs->get_req_var("updatestatusfilter")) {
    echo $aInt->autoAddTokensToForms($aInt->getTemplate("clientssummary"));
    exit;
}
echo $aInt->getTemplate("clientssummary");
echo $aInt->modal("GenerateInvoices", $aInt->lang("invoices", "geninvoices"), $aInt->lang("invoices", "geninvoicessendemails"), array(array("title" => $aInt->lang("global", "yes"), "onclick" => "window.location=\"?userid=" . $userid . "&generateinvoices=true" . generate_token("link") . "\"", "class" => "btn-primary"), array("title" => $aInt->lang("global", "no"), "onclick" => "window.location=\"?userid=" . $userid . "&generateinvoices=true&noemails=true" . generate_token("link") . "\"")));
echo $aInt->modal("AddFunds", $aInt->lang("clientsummary", "createaddfunds"), $aInt->lang("clientsummary", "createaddfundsdesc") . "<br />" . "<div class=\"margin-top-bottom-20 text-center\">" . $aInt->lang("fields", "amount") . ": <input type=\"text\" id=\"addfundsamt\" value=\"" . $CONFIG["AddFundsMinimum"] . "\" class=\"form-control input-inline input-100\" /></div>", array(array("title" => $aInt->lang("global", "submit"), "onclick" => "window.location=\"?userid=" . $userid . "&action=addfunds" . generate_token("link") . "&addfundsamt=\" + jQuery(\"#addfundsamt\").val()", "class" => "btn-primary"), array("title" => $aInt->lang("global", "cancel"))));
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();

?>