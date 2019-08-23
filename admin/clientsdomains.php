<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("View Clients Domains", false);
$aInt->requiredFiles(array("clientfunctions", "domainfunctions", "gatewayfunctions", "registrarfunctions"));
$aInt->setClientsProfilePresets();
$id = (int) App::getFromRequest("id");
$domainid = (int) App::getFromRequest("domainid");
$userid = (int) App::getFromRequest("userid");
$action = App::getFromRequest("action");
$domain = App::getFromRequest("domain");
if (!$id && $domainid) {
    $id = $domainid;
}
if (!$userid && !$id) {
    $userid = get_query_val("tblclients", "id", "", "id", "ASC", "0,1");
}
if ($userid && !$id) {
    $aInt->valUserID($userid);
    $id = get_query_val("tbldomains", "id", array("userid" => $userid), "domain", "ASC", "0,1");
}
if (!$id) {
    $aInt->gracefulExit($aInt->lang("domains", "nodomainsinfo") . " <a href=\"ordersadd.php?userid=" . $userid . "\">" . $aInt->lang("global", "clickhere") . "</a> " . $aInt->lang("orders", "toplacenew"));
}
$domains = new WHMCS\Domains();
$domain_data = $domains->getDomainsDatabyID($id);
$id = $did = $domainid = $domain_data["id"];
if ($userid != $domain_data["userid"]) {
    $userid = $domain_data["userid"];
    $aInt->valUserID($userid);
}
$aInt->setClientsProfilePresets($userid);
$aInt->assertClientBoundary($userid);
if (!$id) {
    $aInt->gracefulExit(AdminLang::trans("domains.domainidnotfound"));
}
$currency = getCurrency($userid);
if ($action == "delete") {
    check_token("WHMCS.admin.default");
    checkPermission("Delete Clients Domains");
    run_hook("DomainDelete", array("userid" => $userid, "domainid" => $id));
    delete_query("tbldomains", array("id" => $id));
    logActivity("Deleted Domain - User ID: " . $userid . " - Domain ID: " . $id, $userid);
    redir("userid=" . $userid);
}
$addonsPricing = WHMCS\Database\Capsule::table("tblpricing")->where("type", "domainaddons")->where("currency", $currency["id"])->where("relid", 0)->first(array("msetupfee", "qsetupfee", "ssetupfee"));
$domaindnsmanagementprice = $addonsPricing->msetupfee * $domain_data["registrationperiod"];
$domainemailforwardingprice = $addonsPricing->qsetupfee * $domain_data["registrationperiod"];
$domainidprotectionprice = $addonsPricing->ssetupfee * $domain_data["registrationperiod"];
if ($action == "savedomain" && $domain) {
    check_token("WHMCS.admin.default");
    checkPermission("Edit Clients Domains");
    $conf = "success";
    $regperiod = (int) App::getFromRequest("regperiod");
    $recurringamount = App::getFromRequest("recurringamount");
    if ($domain_data["is_premium"]) {
        $regperiod = $domain_data["registrationperiod"];
    }
    $domaindnsmanagementprice = $addonsPricing->msetupfee * $regperiod;
    $domainemailforwardingprice = $addonsPricing->qsetupfee * $regperiod;
    $domainidprotectionprice = $addonsPricing->ssetupfee * $regperiod;
    $olddnsmanagement = $domain_data["dnsmanagement"];
    $oldemailforwarding = $domain_data["emailforwarding"];
    $oldidprotection = $domain_data["idprotection"];
    $olddonotrenew = $domain_data["donotrenew"];
    $dnsmanagement = (int) (bool) App::getFromRequest("dnsmanagement");
    $emailforwarding = (int) (bool) App::getFromRequest("emailforwarding");
    $idprotection = (int) (bool) App::getFromRequest("idprotection");
    $idProtectionInRequest = App::isInRequest("idprotection");
    $donotrenew = (int) (bool) App::getFromRequest("donotrenew");
    $promoid = (int) App::getFromRequest("promoid");
    $oldlockstatus = App::getFromRequest("oldlockstatus");
    $lockstatus = App::getFromRequest("lockstatus");
    $newlockstatus = $lockstatus ? "locked" : "unlocked";
    $autorecalc = App::getFromRequest("autorecalc");
    $regdate = App::getFromRequest("regdate");
    $registrar = App::getFromRequest("registrar");
    $changelog = array();
    $logChangeFields = array("registrationdate" => "Registration Date", "domain" => "Domain Name", "firstpaymentamount" => "First Payment Amount", "recurringamount" => "Recurring Amount", "registrar" => "Registrar", "registrationperiod" => "Registration Period", "expirydate" => "Expiry Date", "subscriptionid" => "Subscription Id", "status" => "Status", "nextduedate" => "Next Due Date", "additionalnotes" => "Notes", "paymentmethod" => "Payment Method", "dnsmanagement" => "DNS Management", "emailforwarding" => "Email Forwarding", "idprotection" => "ID Protection", "donotrenew" => "Do Not Renew", "promoid" => "Promotion Code");
    if ($olddnsmanagement) {
        if (!$dnsmanagement) {
            $recurringamount -= $domaindnsmanagementprice;
            $conf = "removeddns";
        }
    } else {
        if ($dnsmanagement) {
            $recurringamount += $domaindnsmanagementprice;
            $conf = "addeddns";
        }
    }
    if ($oldemailforwarding) {
        if (!$emailforwarding) {
            $recurringamount -= $domainemailforwardingprice;
            $conf = "removedemailforward";
        }
    } else {
        if ($emailforwarding) {
            $recurringamount += $domainemailforwardingprice;
            $conf = "addedemailforward";
        }
    }
    if ($idProtectionInRequest) {
        if ($oldidprotection) {
            if (!$idprotection) {
                $recurringamount -= $domainidprotectionprice;
                $conf = "removedidprotect";
            }
        } else {
            if ($idprotection) {
                $recurringamount += $domainidprotectionprice;
                $conf = "addedidprotect";
            }
        }
    }
    if ($autorecalc) {
        $domainparts = explode(".", $domain, 2);
        if ($domain_data["is_premium"]) {
            $recurringamount = (double) WHMCS\Domain\Extra::whereDomainId($domain_data["id"])->whereName("registrarRenewalCostPrice")->value("value");
            $recurringamount = convertCurrency($recurringamount["price"], $recurringamount["currency"], $currency["id"]);
            $hookReturns = run_hook("PremiumPriceRecalculationOverride", array("domainName" => $domain, "tld" => $domainparts[1], "sld" => $domainparts[0], "renew" => $recurringamount));
            $skipMarkup = false;
            foreach ($hookReturns as $hookReturn) {
                if (array_key_exists("renew", $hookReturn)) {
                    $recurringamount = $hookReturn["renew"];
                }
                if (array_key_exists("skipMarkup", $hookReturn) && $hookReturn["skipMarkup"] === true) {
                    $skipMarkup = true;
                }
            }
            if (!$skipMarkup) {
                $recurringamount *= 1 + WHMCS\Domains\Pricing\Premium::markupForCost($recurringamount) / 100;
            }
        } else {
            $temppricelist = getTLDPriceList("." . $domainparts[1], "", true, $userid);
            $recurringamount = $temppricelist[$regperiod]["renew"];
        }
        if ($dnsmanagement) {
            $recurringamount += $domaindnsmanagementprice;
        }
        if ($emailforwarding) {
            $recurringamount += $domainemailforwardingprice;
        }
        if ($idProtectionInRequest && $idprotection || !$idProtectionInRequest && $oldidprotection) {
            $recurringamount += $domainidprotectionprice;
        }
        if ($promoid) {
            $recurringamount -= recalcPromoAmount("D." . $domainparts[1], $userid, $id, $regperiod . "Years", $recurringamount, $promoid);
        }
    }
    $changes = array();
    foreach ($logChangeFields as $fieldName => $displayName) {
        $newValue = ${$fieldName};
        if ($fieldName == "registrationdate") {
            $newValue = $regdate;
        }
        if ($fieldName == "registrationperiod") {
            $newValue = $regperiod;
        }
        $oldValue = $domain_data[$fieldName];
        if (in_array($fieldName, array("dnsmanagement", "emailforwarding", "idprotection", "donotrenew")) && $newValue != $oldValue) {
            if ($newValue && !$oldValue) {
                $changelog[] = (string) $displayName . " Enabled";
                if ($fieldName == "donotrenew") {
                    disableAutoRenew($id);
                }
            } else {
                if (!$newValue && $oldValue) {
                    $changelog[] = (string) $displayName . " Disabled";
                }
            }
            $changes[$fieldName] = $newValue;
            continue;
        }
        if (in_array($fieldName, array("promoid", "additionalnotes")) && $newValue != $oldValue) {
            $changelog[] = (string) $displayName . " Changed";
            $changes[$fieldName] = $newValue;
        }
        if (in_array($fieldName, array("registrationdate", "expirydate", "nextduedate"))) {
            $newValue = toMySQLDate($newValue);
        }
        if ($newValue != $oldValue) {
            $changelog[] = (string) $displayName . " changed from '" . $oldValue . "' to '" . $newValue . "'";
            $changes[$fieldName] = $newValue;
            if ($fieldName == "nextduedate") {
                $changes["nextinvoicedate"] = $newValue;
            }
            if ($fieldName == "expirydate") {
                $changes["reminders"] = "";
            }
        }
    }
    if (0 < count($changes)) {
        WHMCS\Database\Capsule::table("tbldomains")->where("id", $id)->update($changes);
        logActivity("Modified Domain - " . implode(", ", $changelog) . " - User ID: " . $userid . " - Domain ID: " . $id, $userid);
    }
    if (isset($domainfield) && is_array($domainfield)) {
        $additflds = new WHMCS\Domains\AdditionalFields();
        $additflds->setDomain($domain)->setDomainType($domain_data["type"])->setFieldValues($domainfield)->saveToDatabase($id, false);
    }
    loadRegistrarModule($registrar);
    if (function_exists($registrar . "_AdminDomainsTabFieldsSave")) {
        $domainparts = explode(".", $domain, 2);
        $params = array();
        $params["domainid"] = $id;
        list($params["sld"], $params["tld"]) = $domainparts;
        $params["regperiod"] = $regperiod;
        $params["registrar"] = $registrar;
        $fieldsarray = call_user_func($registrar . "_AdminDomainsTabFieldsSave", $params);
    }
    run_hook("AdminClientDomainsTabFieldsSave", $_REQUEST);
    run_hook("DomainEdit", array("userid" => $userid, "domainid" => $id));
    $domainsavetemp = array("ns1" => $ns1, "ns2" => $ns2, "ns3" => $ns3, "ns4" => $ns4, "ns5" => $ns5, "oldns1" => $oldns1, "oldns2" => $oldns2, "oldns3" => $oldns3, "oldns4" => $oldns4, "oldns5" => $oldns5, "defaultns" => $defaultns, "newlockstatus" => $newlockstatus, "oldlockstatus" => $oldlockstatus, "oldidprotection" => $oldidprotection, "idprotection" => $idProtectionInRequest ? $idprotection : $oldidprotection);
    WHMCS\Session::set("domainsavetemp", $domainsavetemp);
    redir("userid=" . $userid . "&id=" . $id . "&conf=" . $conf);
}
ob_start();
$did = $domain_data["id"];
$orderid = $domain_data["orderid"];
$ordertype = $domain_data["type"];
$domain = $domain_data["domain"];
$paymentmethod = $domain_data["paymentmethod"];
$gateways = new WHMCS\Gateways();
if (!$paymentmethod || !$gateways->isActiveGateway($paymentmethod)) {
    $paymentmethod = ensurePaymentMethodIsSet($userid, $id, "tbldomains");
}
$firstpaymentamount = $domain_data["firstpaymentamount"];
$recurringamount = $domain_data["recurringamount"];
$registrar = $domain_data["registrar"];
$regtype = $domain_data["type"];
$expirydate = $domain_data["expirydate"];
$nextduedate = $domain_data["nextduedate"];
$subscriptionid = $domain_data["subscriptionid"];
$promoid = $domain_data["promoid"];
$registrationdate = $domain_data["registrationdate"];
$registrationperiod = $domain_data["registrationperiod"];
$domainstatus = $domain_data["status"];
$additionalnotes = $domain_data["additionalnotes"];
$dnsmanagement = $domain_data["dnsmanagement"];
$emailforwarding = $domain_data["emailforwarding"];
$idprotection = $domain_data["idprotection"];
$donotrenew = $domain_data["donotrenew"];
$isPremium = $domain_data["is_premium"];
$expirydate = fromMySQLDate($expirydate);
$nextduedate = fromMySQLDate($nextduedate);
$regdate = fromMySQLDate($registrationdate);
$token = generate_token("link");
$modalHtml = $aInt->modal("Renew", $aInt->lang("domains", "renewdomain"), $aInt->lang("domains", "renewdomainq"), array(array("title" => $aInt->lang("global", "yes"), "onclick" => "window.location=\"?userid=" . $userid . "&id=" . $id . "&regaction=renew" . $token . "\"", "class" => "btn-primary"), array("title" => $aInt->lang("global", "no"))));
$modalHtml .= $aInt->modal("GetEPP", $aInt->lang("domains", "requestepp"), $aInt->lang("domains", "requesteppq"), array(array("title" => $aInt->lang("global", "yes"), "onclick" => "window.location=\"?userid=" . $userid . "&id=" . $id . "&regaction=eppcode" . $token . "\"", "class" => "btn-primary"), array("title" => $aInt->lang("global", "no"))));
$modalHtml .= $aInt->modal("RequestDelete", $aInt->lang("domains", "requestdel"), $aInt->lang("domains", "requestdelq"), array(array("title" => $aInt->lang("global", "yes"), "onclick" => "window.location=\"?userid=" . $userid . "&id=" . $id . "&regaction=reqdelete" . $token . "\"", "class" => "btn-primary"), array("title" => $aInt->lang("global", "no"))));
$modalHtml .= $aInt->modal("Delete", $aInt->lang("domains", "delete"), $aInt->lang("domains", "deleteq"), array(array("title" => $aInt->lang("global", "yes"), "onclick" => "window.location=\"?userid=" . $userid . "&id=" . $id . "&action=delete" . $token . "\"", "class" => "btn-primary"), array("title" => $aInt->lang("global", "no"))));
$modalHtml .= $aInt->modal("ReleaseDomain", $aInt->lang("domains", "releasedomain"), $aInt->lang("domains", "releasedomainq") . "<div class=\"margin-top-bottom-20\"><table width=\"80%\" align=\"center\"><tr><td>" . $aInt->lang("domains", "transfertag") . ":</td><td>" . "<input type=\"text\" id=\"transtag\" class=\"form-control\" />" . "</td></tr></table></div>", array(array("title" => $aInt->lang("global", "submit"), "onclick" => "window.location=\"?userid=" . $userid . "&id=" . $id . "&regaction=release&transtag=\" + jQuery(\"#transtag\").val() + \"" . $token . "\"", "class" => "btn-primary"), array("title" => $aInt->lang("global", "cancel"))));
$domainsavetemp = WHMCS\Session::get("domainsavetemp");
WHMCS\Session::delete("domainsavetemp");
if ($conf && $domainsavetemp) {
    $ns1 = $domainsavetemp["ns1"];
    $ns2 = $domainsavetemp["ns2"];
    $ns3 = $domainsavetemp["ns3"];
    $ns4 = $domainsavetemp["ns4"];
    $ns5 = $domainsavetemp["ns5"];
    $oldns1 = $domainsavetemp["oldns1"];
    $oldns2 = $domainsavetemp["oldns2"];
    $oldns3 = $domainsavetemp["oldns3"];
    $oldns4 = $domainsavetemp["oldns4"];
    $oldns5 = $domainsavetemp["oldns5"];
    $defaultns = $domainsavetemp["defaultns"];
    $newlockstatus = $domainsavetemp["newlockstatus"];
    $oldlockstatus = $domainsavetemp["oldlockstatus"];
    $oldidprotect = $domainsavetemp["oldidprotection"];
    $idprotect = $domainsavetemp["idprotection"];
} else {
    $ns1 = "";
    $ns2 = "";
    $ns3 = "";
    $ns4 = "";
    $ns5 = "";
    $oldns1 = "";
    $oldns2 = "";
    $oldns3 = "";
    $oldns4 = "";
    $oldns5 = "";
    $defaultns = "";
    $newlockstatus = "";
    $oldlockstatus = "";
    $oldidprotect = "";
    $idprotect = "";
}
switch ($conf) {
    case "success":
        infoBox($aInt->lang("global", "changesuccess"), $aInt->lang("global", "changesuccessdesc"), "success");
        break;
    case "addeddns":
        infoBox($aInt->lang("global", "changesuccess"), $aInt->lang("domains", "dnsmanagementadded"), "success");
        break;
    case "addedemailforward":
        infoBox($aInt->lang("global", "changesuccess"), $aInt->lang("domains", "emailforwardingadded"), "success");
        break;
    case "addedidprotect":
        infoBox($aInt->lang("global", "changesuccess"), $aInt->lang("domains", "idprotectionadded"), "success");
        break;
    case "removeddns":
        infoBox($aInt->lang("global", "changesuccess"), $aInt->lang("domains", "dnsmanagementremoved"), "success");
        break;
    case "removedemailforward":
        infoBox($aInt->lang("global", "changesuccess"), $aInt->lang("domains", "emailforwardingremoved"), "success");
        break;
    case "removedidprotect":
        infoBox($aInt->lang("global", "changesuccess"), $aInt->lang("domains", "idprotectionremoved"), "success");
        break;
    case "domainreleasedanddeleted":
        $successMessage = WHMCS\Session::getAndDelete("DomainReleaseInfo");
        infoBox(AdminLang::trans("domains.releasesuccess"), $successMessage, "success");
        break;
}
WHMCS\Session::release();
$domainregistraractions = checkPermission("Perform Registrar Operations", true) && $domains->getModule() ? true : false;
if ($domainregistraractions) {
    $domainparts = explode(".", $domain, 2);
    $params = array();
    $params["domainid"] = $id;
    list($params["sld"], $params["tld"]) = $domainparts;
    $params["regperiod"] = $registrationperiod;
    $params["registrar"] = $registrar;
    $params["regtype"] = $regtype;
    $adminbuttonarray = "";
    loadRegistrarModule($registrar);
    if (function_exists($registrar . "_AdminCustomButtonArray")) {
        $adminbuttonarray = call_user_func($registrar . "_AdminCustomButtonArray", $params);
    }
    if ($oldns1 != $ns1 || $oldns2 != $ns2 || $oldns3 != $ns3 || $oldns4 != $ns4 || $oldns5 != $ns5 || $defaultns) {
        $nameservers = $defaultns ? $domains->getDefaultNameservers() : array("ns1" => $ns1, "ns2" => $ns2, "ns3" => $ns3, "ns4" => $ns4, "ns5" => $ns5);
        $success = $domains->moduleCall("SaveNameservers", $nameservers);
        if (!$success) {
            infoBox($aInt->lang("domains", "nschangefail"), $domains->getLastError(), "error");
        } else {
            infoBox($aInt->lang("domains", "nschangesuccess"), $aInt->lang("domains", "nschangeinfo"), "success");
        }
    }
    if (!$oldlockstatus) {
        $oldlockstatus = $newlockstatus;
    }
    if ($newlockstatus != $oldlockstatus) {
        $params["lockenabled"] = $newlockstatus;
        $values = RegSaveRegistrarLock($params);
        if ($values["error"]) {
            infoBox($aInt->lang("domains", "reglockfailed"), $values["error"], "error");
        } else {
            infoBox($aInt->lang("domains", "reglocksuccess"), $aInt->lang("domains", "reglockinfo"), "success");
        }
    }
    if ($regaction = App::getFromRequest("regaction")) {
        check_token("WHMCS.admin.default");
        define("NO_QUEUE", true);
    }
    if ($regaction == "renew") {
        $values = RegRenewDomain($params);
        WHMCS\Cookie::set("DomRenewRes", $values);
        redir("userid=" . $userid . "&id=" . $id . "&conf=renew");
    }
    if ($regaction == "eppcode") {
        $values = RegGetEPPCode($params);
        if ($values["error"]) {
            infoBox($aInt->lang("domains", "eppfailed"), $values["error"], "error");
        } else {
            if ($values["eppcode"]) {
                infoBox($aInt->lang("domains", "epprequest"), $_LANG["domaingeteppcodeis"] . " " . $values["eppcode"], "success");
            } else {
                infoBox($aInt->lang("domains", "epprequest"), $_LANG["domaingeteppcodeemailconfirmation"], "success");
            }
        }
    }
    if ($regaction == "reqdelete") {
        $values = RegRequestDelete($params);
        if ($values["error"]) {
            infoBox($aInt->lang("domains", "deletefailed"), $values["error"], "error");
        } else {
            infoBox($aInt->lang("domains", "deletesuccess"), $aInt->lang("domains", "deleteinfo"), "success");
        }
    }
    if ($regaction == "release") {
        $params["transfertag"] = $transtag;
        $values = RegReleaseDomain($params);
        if (array_key_exists("deleted", $values) && $values["deleted"]) {
            $successMessage = AdminLang::trans("domains.releasedAndDeleted", array(":domain" => $domain, ":tag" => $transtag));
            WHMCS\Session::setAndRelease("DomainReleaseInfo", $successMessage);
            App::redirect(App::getPhpSelf(), array("userid" => $userid, "conf" => "domainreleasedanddeleted"));
        }
        $successmessage = str_replace("%s", $transtag, $aInt->lang("domains", "releaseinfo"));
        if ($values["error"]) {
            infoBox(AdminLang::trans("domains.releasefailed"), $values["error"], "error");
        } else {
            infoBox(AdminLang::trans("domains.releasesuccess"), $successmessage, "success");
            WHMCS\Database\Capsule::table("tbldomains")->where("id", $domainid)->update(array("status" => WHMCS\Domain\Status::TRANSFERRED_AWAY));
            $domainstatus = WHMCS\Domain\Status::TRANSFERRED_AWAY;
            $domain_data["status"] = WHMCS\Domain\Status::TRANSFERRED_AWAY;
        }
    }
    if ($regaction == "idtoggle") {
        $params["protectenable"] = !(bool) (int) $domain_data["idprotection"];
        $values = RegIDProtectToggle($params);
        if ($values["error"]) {
            infoBox(AdminLang::trans("domains.idprotectfailed"), $values["error"], "error");
        } else {
            $idprotection = !(bool) (int) $domain_data["idprotection"];
            $recurringamount = $domain_data["recurringamount"] - $domainidprotectionprice;
            if ($idprotection) {
                $recurringamount = $domain_data["recurringamount"] + $domainidprotectionprice;
            }
            $updateArray = array("idprotection" => $idprotection, "recurringamount" => $recurringamount);
            WHMCS\Database\Capsule::table("tbldomains")->where("id", $domain_data["id"])->update($updateArray);
            infoBox(AdminLang::trans("domains.idprotectsuccess"), AdminLang::trans("domains.idprotectinfo"), "success");
        }
    }
    if ($regaction == "resendirtpemail" && $domains->hasFunction("ResendIRTPVerificationEmail")) {
        $success = $domains->moduleCall("ResendIRTPVerificationEmail");
        if ($success) {
            infoBox(AdminLang::trans("domains.resendNotification"), AdminLang::trans("domains.resendNotificationSuccess"), "success");
        } else {
            if ($values["error"]) {
                infoBox(AdminLang::trans("domains.resendNotification"), $values["error"], "error");
            }
        }
    }
    if ($regaction == "custom") {
        $values = RegCustomFunction($params, $ac);
        if ($values["error"]) {
            infoBox($aInt->lang("domains", "registrarerror"), $values["error"], "error");
        } else {
            if (!$values["message"]) {
                $values["message"] = $aInt->lang("domains", "changesuccess");
            }
            infoBox($aInt->lang("domains", "changesuccess"), $values["message"], "success");
        }
    }
    if ($conf == "renew") {
        $values = WHMCS\Cookie::get("DomRenewRes", 1);
        if ($values["error"]) {
            infoBox($aInt->lang("domains", "renewfailed"), $values["error"], "error");
        } else {
            $successmessage = str_replace("%s", $registrationperiod, $aInt->lang("domains", "renewinfo"));
            infoBox($aInt->lang("domains", "renewsuccess"), $successmessage, "success");
        }
    }
    $nsvalues = array();
    $lockstatus = NULL;
    $showResendIRTPVerificationEmail = false;
    $alerts = array();
    try {
        $domainInformation = $domains->getDomainInformation();
        $nsvalues = $domainInformation->getNameservers();
        $registrarLockStatus = $domainInformation->getTransferLock();
        if (!is_null($registrarLockStatus)) {
            $lockstatus = "unlocked";
            if ($registrarLockStatus === true) {
                $lockstatus = "locked";
            }
        }
        if ($domainInformation->isIrtpEnabled() && $domainInformation->isContactChangePending()) {
            $title = AdminLang::trans("domains.contactChangePending");
            $description = "domains.contactsChanged";
            if ($domainInformation->getPendingSuspension()) {
                $title = AdminLang::trans("domains.verificationRequired");
                $description = "domains.newRegistration";
            }
            $parameters = array();
            if ($domainInformation->getDomainContactChangeExpiryDate()) {
                $description .= "Date";
                $parameters = array(":date" => $domainInformation->getDomainContactChangeExpiryDate()->toAdminDateFormat());
            }
            $description = AdminLang::trans($description, $parameters);
            $alerts[] = WHMCS\View\Helper::alert("<strong>" . $title . "</strong><br>" . $description);
            $showResendIRTPVerificationEmail = true;
        }
        if ($domainInformation->isIrtpEnabled() && $domainInformation->getIrtpTransferLock()) {
            $title = AdminLang::trans("domains.irtpLockEnabled");
            $description = AdminLang::trans("domains.irtpLockDescription");
            if ($domainInformation->getIrtpTransferLockExpiryDate()) {
                $description = AdminLang::trans("domains.irtpLockDescriptionDate", array(":date" => $domainInformation->getIrtpTransferLockExpiryDate()->toAdminDateFormat()));
            }
            $alerts[] = WHMCS\View\Helper::alert("<strong>" . $title . "</strong><br>" . $description);
        }
    } catch (Exception $e) {
        if (!$infobox) {
            infoBox(AdminLang::trans("domains.registrarerror"), $e->getMessage(), "error");
        }
    }
}
if ($showResendIRTPVerificationEmail && $domains->hasFunction("ResendIRTPVerificationEmail")) {
    $modalHtml .= $aInt->modal("ResendIRTPVerificationEmail", AdminLang::trans("domains.resendNotification"), AdminLang::trans("domains.resendNotificationQuestion"), array(array("title" => AdminLang::trans("global.submit"), "onclick" => "window.location=\"?userid=" . $userid . "&id=" . $id . "&regaction=resendirtpemail" . $token . "\"", "class" => "btn-primary"), array("title" => AdminLang::trans("global.cancel"))));
}
$idProtectTitle = "domains.enableIdProtection";
$idProtectQuestion = "domains.enableIdProtectionQuestion";
if ($idprotection) {
    $idProtectTitle = "domains.disableIdProtection";
    $idProtectQuestion = "domains.disableIdProtectionQuestion";
}
$modalHtml .= $aInt->modal("IdProtectToggle", AdminLang::trans($idProtectTitle), AdminLang::trans($idProtectQuestion), array(array("title" => AdminLang::trans("global.yes"), "onclick" => "window.location=\"?userid=" . $userid . "&id=" . $id . "&regaction=idtoggle" . $token . "\"", "class" => "btn-primary"), array("title" => AdminLang::trans("global.no"))));
echo "\n<div class=\"context-btn-container\">\n    <div class=\"row\">\n        <div class=\"col-sm-7 text-left\">\n            <form action=\"";
echo $whmcs->getPhpSelf();
echo "\" method=\"get\">\n                <input type=\"hidden\" name=\"userid\" value=\"";
echo $userid;
echo "\">\n                ";
echo $aInt->lang("clientsummary", "domains");
echo ":\n                <select name=\"id\" onChange=\"submit()\" class=\"form-control select-inline\">\n";
$result = select_query("tbldomains", "", array("userid" => $userid), "domain", "ASC");
while ($data = mysql_fetch_array($result)) {
    $domainlistid = $data["id"];
    $domainlistname = $data["domain"];
    $domainliststatus = $data["status"];
    echo "<option value=\"" . $domainlistid . "\"";
    if ($domainlistid == $id) {
        echo " selected";
    }
    if ($domainliststatus == "Pending") {
        echo " style=\"background-color:#ffffcc;\"";
    } else {
        if (in_array($domainliststatus, array("Expired", "Cancelled", "Fraud", "Transferred Away"))) {
            echo " style=\"background-color:#ff9999;\"";
        }
    }
    echo ">" . $domainlistname . "</option>";
}
echo "                </select>\n                <noscript>\n                    <input type=\"submit\" value=\"";
echo $aInt->lang("global", "go");
echo "\" class=\"btn btn-success btn-sm\" />\n                </noscript>\n            </form>\n        </div>\n        <div class=\"col-sm-5\">\n            ";
$sslStatus = WHMCS\Domain\Ssl\Status::factory($userid, $domain);
$html = "<img src=\"%s\"\n                           class=\"%s\"\n                           data-toggle=\"tooltip\"\n                           title=\"%s\"\n                           data-domain=\"%s\"\n                           data-user-id=\"%s\"\n                           >";
echo sprintf($html, $sslStatus->getImagePath(), $sslStatus->getClass(), $sslStatus->getTooltipContent(), $domain, $userid);
echo "            <button type=\"button\" onclick=\"window.open('clientsmove.php?type=domain&id=";
echo $id;
echo "','movewindow','width=500,height=200,top=100,left=100');return false\" class=\"btn btn-default left-margin-5\">\n                <i class=\"fas fa-random\"></i>\n                ";
echo $aInt->lang("services", "moveservice");
echo "            </button>\n        </div>\n    </div>\n</div>\n\n";
if ($infobox) {
    echo $infobox;
}
if ($alerts) {
    echo implode($alerts);
}
$premiumLabel = $renewalCostInfo = "";
$registrationPeriodInput = "<input type=\"text\" name=\"regperiod\" value=\"" . $registrationperiod . "\" class=\"form-control input-50";
$registrationPeriodInputEnd = "\" /> " . AdminLang::trans("domains.years");
if ($isPremium) {
    $extraData = WHMCS\Domain\Extra::whereDomainId($domain_data["id"])->pluck("value", "name");
    $renewalCost = convertCurrency($extraData["registrarRenewalCostPrice"], $extraData["registrarCurrency"], $currency["id"]);
    $premiumLabel = " <span class=\"label label-danger\">" . AdminLang::trans("domains.premiumDomain") . "</span>";
    $registrationPeriodInput = "<div data-toggle=\"tooltip\" data-placement=\"left\" data-trigger=\"hover\" title=\"" . AdminLang::trans("domains.periodPremiumDomains") . "\">" . $registrationPeriodInput . " disabled\" disabled=\"disabled" . $registrationPeriodInputEnd . "</div>";
    $renewalCostInfo = "<span class=\"badge\">" . AdminLang::trans("domains.premiumRenewalCost") . ": " . formatCurrency((double) $renewalCost, true)->toPrefixed() . "</span>";
} else {
    $registrationPeriodInput .= $registrationPeriodInputEnd;
}
echo "\n<form method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
echo "?action=savedomain&userid=";
echo $userid;
echo "&id=";
echo $id;
echo "\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr>\n    <td class=\"fieldlabel\">";
echo $aInt->lang("fields", "ordernum");
echo "</td>\n    <td class=\"fieldarea\">";
echo $orderid;
echo " - <a href=\"orders.php?action=view&id=";
echo $orderid;
echo "\">";
echo $aInt->lang("orders", "vieworder");
echo "</a></td>\n    <td class=\"fieldlabel\">";
echo $aInt->lang("domains", "regperiod");
echo "</td>\n    <td class=\"fieldarea\">\n        <div class=\"form-inline\">\n            <input type=\"hidden\" name=\"regperiod\" value=\"";
echo $registrationperiod;
echo "\">\n            ";
echo $registrationPeriodInput;
echo "        </div>\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
echo $aInt->lang("orders", "ordertype");
echo "</td>\n    <td class=\"fieldarea\">";
echo $ordertype . $premiumLabel;
echo "</td>\n    <td class=\"fieldlabel\">";
echo $aInt->lang("fields", "regdate");
echo "</td>\n    <td class=\"fieldarea\">\n        <div class=\"form-group date-picker-prepend-icon\">\n            <label for=\"inputRegDate\" class=\"field-icon\">\n                <i class=\"fal fa-calendar-alt\"></i>\n            </label>\n            <input id=\"inputRegDate\"\n                   type=\"text\"\n                   name=\"regdate\"\n                   value=\"";
echo $regdate;
echo "\"\n                   class=\"form-control date-picker-single\"\n            />\n        </div>\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
echo $aInt->lang("fields", "domain");
echo "</td>\n    <td class=\"fieldarea\">\n        <div class=\"input-group input-300\">\n            <input type=\"text\" name=\"domain\" class=\"form-control\" value=\"";
echo $domain;
echo "\">\n            <div class=\"input-group-btn\">\n                <button type=\"button\" class=\"btn btn-default dropdown-toggle\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\" style=\"margin-left:-3px;\">\n                    <span class=\"caret\"></span>\n                </button>\n                <ul class=\"dropdown-menu dropdown-menu-right\">\n                    <li><a href=\"http://www.";
echo $domain;
echo "\" target=\"_blank\">www</a>\n                    <li><a onclick=\"\$('#frmWhois').submit();return false\">";
echo $aInt->lang("domains", "whois");
echo "</a>\n                    <li><a href=\"http://www.intodns.com/";
echo $domain;
echo "\" target=\"_blank\">intoDNS</a></li>\n                </ul>\n            </div>\n        </div>\n    </td>\n    <td class=\"fieldlabel\">";
echo $aInt->lang("fields", "expirydate");
echo "</td>\n    <td class=\"fieldarea\">\n        <div class=\"form-group date-picker-prepend-icon\">\n            <label for=\"inputExpiryDate\" class=\"field-icon\">\n                <i class=\"fal fa-calendar-alt\"></i>\n            </label>\n            <input id=\"inputExpiryDate\"\n                   type=\"text\"\n                   name=\"expirydate\"\n                   value=\"";
echo $expirydate;
echo "\"\n                   class=\"form-control date-picker-single future\"\n            />\n        </div>\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
echo $aInt->lang("fields", "registrar");
echo "</td>\n    <td class=\"fieldarea\">";
echo getRegistrarsDropdownMenu($registrar);
echo "</td>\n    <td class=\"fieldlabel\">";
echo $aInt->lang("fields", "nextduedate");
echo "</td>\n    <td class=\"fieldarea\">\n        <input type=\"hidden\" name=\"oldnextduedate\" value=\"";
echo $nextduedate;
echo "\">\n        <div class=\"form-group date-picker-prepend-icon\">\n            <label for=\"inputNextDueDate\" class=\"field-icon\">\n                <i class=\"fal fa-calendar-alt\"></i>\n            </label>\n            <input id=\"inputNextDueDate\"\n                   type=\"text\"\n                   name=\"nextduedate\"\n                   value=\"";
echo $nextduedate;
echo "\"\n                   class=\"form-control date-picker-single future\"\n            />\n        </div>\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
echo $aInt->lang("fields", "firstpaymentamount");
echo "</td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"firstpaymentamount\" class=\"form-control input-100\" value=\"";
echo $firstpaymentamount;
echo "\">\n    </td>\n    <td class=\"fieldlabel\">";
echo $aInt->lang("fields", "paymentmethod");
echo "</td>\n    <td class=\"fieldarea\">";
echo paymentMethodsSelection();
echo " <a href=\"clientsinvoices.php?userid=";
echo $userid;
echo "&domainid=";
echo $id;
echo "\">";
echo $aInt->lang("invoices", "viewinvoices");
echo "</a>\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
echo $aInt->lang("fields", "recurringamount");
echo "</td>\n    <td class=\"fieldarea\">\n        <div class=\"form-inline\">\n            <input type=\"text\" name=\"recurringamount\" class=\"form-control input-100\" value=\"";
echo $recurringamount;
echo "\">\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"autorecalc\" ";
if ($autorecalcdefault) {
    echo " checked";
}
echo " /> ";
echo $aInt->lang("services", "autorecalc");
echo "            </label>\n        </div>\n        <div class=\"form-inline\">\n            ";
echo $renewalCostInfo;
echo "        </div>\n    </td>\n    <td class=\"fieldlabel\">";
echo $aInt->lang("fields", "status");
echo "</td>\n    <td class=\"fieldarea\">\n        <select name=\"status\" class=\"form-control select-inline\">\n            ";
echo (new WHMCS\Domain\Status())->translatedDropdownOptions(array($domainstatus));
echo "        </select>\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
echo $aInt->lang("fields", "promocode");
echo "</td>\n    <td class=\"fieldarea\"><select name=\"promoid\" class=\"form-control select-inline\"><option value=\"0\">";
echo $aInt->lang("global", "none");
echo "</option>";
$currency = getCurrency($userid);
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
    echo "<option value=\"" . $promo_id . "\"";
    if ($promo_id == $promoid) {
        echo " selected";
    }
    echo ">" . $promo_code . " - " . $promo_value . " " . $promo_recurring . "</option>";
}
echo "</select> (";
echo $aInt->lang("promotions", "noaffect");
echo ")</td>\n    <td class=\"fieldlabel\">";
echo $aInt->lang("fields", "subscriptionid");
echo "</td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" class=\"form-control input-200\" name=\"subscriptionid\" value=\"";
echo $subscriptionid;
echo "\">\n    </td>\n</tr>\n\n";
if ($domainregistraractions) {
    if ($domains->hasFunction("GetNameservers") || $domains->hasFunction("GetDomainInformation")) {
        echo "<tr>\n    <td class=\"fieldlabel\">";
        echo $aInt->lang("domains", "nameserver");
        echo " 1</td>\n    <td class=\"fieldarea\" colspan=\"3\">\n        <input type=\"text\" name=\"ns1\" value=\"";
        echo $nsvalues["ns1"];
        echo "\" class=\"form-control input-300\" />\n        <input type=\"hidden\" name=\"oldns1\" value=\"";
        echo $nsvalues["ns1"];
        echo "\" />\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
        echo $aInt->lang("domains", "nameserver");
        echo " 2</td>\n    <td class=\"fieldarea\" colspan=\"3\">\n        <input type=\"text\" name=\"ns2\" value=\"";
        echo $nsvalues["ns2"];
        echo "\" class=\"form-control input-300\" />\n        <input type=\"hidden\" name=\"oldns2\" value=\"";
        echo $nsvalues["ns2"];
        echo "\" />\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
        echo $aInt->lang("domains", "nameserver");
        echo " 3</td>\n    <td class=\"fieldarea\" colspan=\"3\">\n        <input type=\"text\" name=\"ns3\" value=\"";
        echo $nsvalues["ns3"];
        echo "\" class=\"form-control input-300\" />\n        <input type=\"hidden\" name=\"oldns3\" value=\"";
        echo $nsvalues["ns3"];
        echo "\" />\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
        echo $aInt->lang("domains", "nameserver");
        echo " 4</td>\n    <td class=\"fieldarea\" colspan=\"3\">\n        <input type=\"text\" name=\"ns4\" value=\"";
        echo $nsvalues["ns4"];
        echo "\" class=\"form-control input-300\" />\n        <input type=\"hidden\" name=\"oldns4\" value=\"";
        echo $nsvalues["ns4"];
        echo "\" />\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
        echo $aInt->lang("domains", "nameserver");
        echo " 5</td>\n    <td class=\"fieldarea\" colspan=\"3\">\n        <input type=\"text\" name=\"ns5\" value=\"";
        echo $nsvalues["ns5"];
        echo "\" class=\"form-control input-300\" />\n        <input type=\"hidden\" name=\"oldns5\" value=\"";
        echo $nsvalues["ns5"];
        echo "\" />\n    </td>\n</tr>\n    <tr>\n        <td class=\"fieldlabel\">&nbsp;</td>\n        <td class=\"fieldarea\" colspan=\"3\">\n            <label for=\"defaultns\">\n                <input type=\"checkbox\" name=\"defaultns\" id=\"defaultns\" />\n                ";
        echo $aInt->lang("domains", "resetdefaultns");
        echo "            </label>\n        </td>\n    </tr>\n";
    }
    if ($lockstatus) {
        echo "<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("domains", "reglock");
        echo "</td><td class=\"fieldarea\" colspan=\"3\"><input type=\"checkbox\" name=\"lockstatus\"";
        if ($lockstatus == "locked") {
            echo " checked";
        }
        echo "> ";
        echo $aInt->lang("global", "ticktoenable");
        echo " <input type=\"hidden\" name=\"oldlockstatus\" value=\"";
        echo $lockstatus;
        echo "\"></td></tr>\n";
    }
    echo "<tr>\n    <td class=\"fieldlabel\">";
    echo $aInt->lang("domains", "registrarcommands");
    echo "</td><td colspan=\"3\">\n";
    if ($domains->hasFunction("RegisterDomain")) {
        echo "<input type=\"button\" value=\"";
        echo $aInt->lang("domains", "actionreg");
        echo "\" class=\"button btn btn-default\" onClick=\"window.location='clientsdomainreg.php?domainid=";
        echo $id;
        echo "'\"> ";
    }
    if ($domains->hasFunction("TransferDomain")) {
        echo "<input type=\"button\" value=\"";
        echo $aInt->lang("domains", "transfer");
        echo "\" class=\"button btn btn-default\" onClick=\"window.location='clientsdomainreg.php?domainid=";
        echo $id;
        echo "&ac=transfer'\"> ";
    }
    if ($domains->hasFunction("RenewDomain")) {
        echo "<input type=\"button\" value=\"";
        echo $aInt->lang("domains", "renew");
        echo "\" class=\"button btn btn-default\" data-toggle=\"modal\" data-target=\"#modalRenew\"> ";
    }
    if ($domains->hasFunction("GetContactDetails")) {
        echo "<input type=\"button\" value=\"";
        echo $aInt->lang("domains", "modifydetails");
        echo "\" class=\"button btn btn-default\" onClick=\"window.location='clientsdomaincontacts.php?domainid=";
        echo $id;
        echo "'\"> ";
    }
    if ($domains->hasFunction("GetEPPCode")) {
        echo "<input type=\"button\" value=\"";
        echo $aInt->lang("domains", "getepp");
        echo "\" class=\"button btn btn-default\" data-toggle=\"modal\" data-target=\"#modalGetEPP\"> ";
    }
    if ($domains->hasFunction("RequestDelete")) {
        echo "<input type=\"button\" value=\"";
        echo $aInt->lang("domains", "requestdelete");
        echo "\" class=\"button btn btn-default\" data-toggle=\"modal\" data-target=\"#modalRequestDelete\"> ";
    }
    if ($domains->hasFunction("ReleaseDomain")) {
        echo "<input type=\"button\" value=\"";
        echo $aInt->lang("domains", "releasedomain");
        echo "\" class=\"button btn btn-default\" data-toggle=\"modal\" data-target=\"#modalReleaseDomain\"> ";
    }
    if ($domains->hasFunction("IDProtectToggle")) {
        $buttonValue = AdminLang::trans("domains.enableIdProtection");
        if ($idprotection) {
            $buttonValue = AdminLang::trans("domains.disableIdProtection");
        }
        echo "    <button type=\"button\" class=\"button btn btn-default\" data-toggle=\"modal\" data-target=\"#modalIdProtectToggle\">\n        ";
        echo $buttonValue;
        echo "    </button>\n";
    }
    if ($showResendIRTPVerificationEmail && $domains->hasFunction("ResendIRTPVerificationEmail")) {
        echo "    <input type=\"button\" value=\"";
        echo AdminLang::trans("domains.resendNotification");
        echo "\" class=\"button btn btn-default\" data-toggle=\"modal\" data-target=\"#modalResendIRTPVerificationEmail\">\n";
    }
    if ($domains->moduleCall("AdminCustomButtonArray")) {
        $adminbuttonarray = $domains->getModuleReturn();
        foreach ($adminbuttonarray as $key => $value) {
            echo " <input type=\"button\" value=\"";
            echo $key;
            echo "\" class=\"button btn btn-default\" onClick=\"window.location='";
            echo $whmcs->getPhpSelf();
            echo "?userid=";
            echo $userid;
            echo "&id=";
            echo $id;
            echo "&regaction=custom&ac=";
            echo $value . $token;
            echo "'\">";
        }
    }
    echo "    </td>\n</tr>\n";
}
echo "<tr>\n    <td class=\"fieldlabel\">";
echo $aInt->lang("domains", "managementtools");
echo "</td>\n    <td class=\"fieldarea\" colspan=\"3\">\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"dnsmanagement\"";
echo $dnsmanagement ? " checked=\"checked\"" : "";
echo ">\n            ";
echo AdminLang::trans("domains.dnsmanagement");
echo "        </label>\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"emailforwarding\"";
echo $emailforwarding ? " checked=\"checked\"" : "";
echo ">\n            ";
echo AdminLang::trans("domains.emailforwarding");
echo "        </label>\n        ";
$onclick = "";
if ($domains->hasFunction("IDProtectToggle")) {
    $onclick = " onclick=\"\$('#modalIdProtectToggle').modal('show');\"";
}
echo "        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"idprotection\"";
echo ($idprotection ? " checked=\"checked\"" : "") . $onclick;
echo ">\n            ";
echo AdminLang::trans("domains.idprotection");
echo "        </label>\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"donotrenew\"";
echo $donotrenew ? " checked=\"checked\"" : "";
echo ">\n            ";
echo AdminLang::trans("domains.donotrenew");
echo "        </label>\n    </td>\n</tr>\n";
if ($registrar) {
    $module = new WHMCS\Module\Registrar();
    $module->load($registrar);
    if (!$module->functionExists("IDProtectToggle")) {
        echo "<tr>\n    <td class=\"fieldlabel\">&nbsp</td>\n    <td class=\"fieldarea\" colspan=\"3\">";
        echo $aInt->lang("domains", "idprotectioncontrolna");
        echo "</td>\n</tr>\n";
    }
}
$reminderEmails = array("", "first", "second", "third", "fourth", "fifth");
$reminderEmailOutput = "<tr>\n    <td class=\"fieldlabel\">\n        " . $aInt->lang("domains", "domainReminders") . "\n    </td>\n    <td class=\"fieldarea\" colspan=\"3\">\n        <div id=\"domainReminders\" style=\"overflow-y:auto; max-height:100px; font-size: 0.9em;\">\n            <table class=\"datatable\" width=\"100%\" style=\"margin-bottom:0;\">\n                <tr>\n                    <th>" . $aInt->lang("fields", "date") . "</th>\n                    <th>" . $aInt->lang("domains", "reminder") . "</th>\n                    <th>" . $aInt->lang("emails", "to") . "</th>\n                    <th>" . $aInt->lang("domains", "sent") . "</th>\n                </tr>";
if ($domains->obtainEmailReminders()) {
    foreach ($domains->obtainEmailReminders() as $reminderMail) {
        $reminderType = AdminLang::trans("domains." . $reminderEmails[$reminderMail["type"]] . "Reminder");
        $reminderDate = fromMySQLDate($reminderMail["date"]);
        $recipients = $reminderMail["recipients"];
        $sent = sprintf(AdminLang::trans("domains.beforeExpiry"), $reminderMail["days_before_expiry"]);
        if ($reminderMail["days_before_expiry"] < 0) {
            $sent = sprintf(AdminLang::trans("domains.afterExpiry"), $reminderMail["days_before_expiry"] * -1);
        }
        $reminderEmailOutput .= "<tr align=\"center\">\n    <td>" . $reminderDate . "</td>\n    <td>" . $reminderType . "</td>\n    <td width=\"50%\">" . $recipients . "</td>\n    <td>" . $sent . "</td>\n</tr>";
    }
} else {
    $noRecords = AdminLang::trans("global.norecordsfound");
    $reminderEmailOutput .= "<tr align=\"center\">\n    <td colspan=\"4\">" . $noRecords . "</td>\n</tr>";
}
$reportLink = "";
if (checkPermission("View Reports", true) && $domains->obtainEmailReminders()) {
    $reportLink = sprintf("<input type=\"button\" onclick=\"%s\" value=\"%s\" class=\"btn btn-default top-margin-5\" />", "window.location='reports.php?report=domain_renewal_emails&client=" . $userid . "&domain=" . $domain . "'", AdminLang::trans("fields.export"));
}
$reminderEmailOutput .= "</table></div>" . $reportLink . "</td></tr>";
echo $reminderEmailOutput;
if (function_exists($registrar . "_AdminDomainsTabFields")) {
    $fieldsarray = call_user_func($registrar . "_AdminDomainsTabFields", $params);
    if (is_array($fieldsarray)) {
        foreach ($fieldsarray as $k => $v) {
            echo "<tr><td class=\"fieldlabel\">" . $k . "</td><td class=\"fieldarea\" colspan=\"3\">" . $v . "</td></tr>";
        }
    }
}
$hookret = run_hook("AdminClientDomainsTabFields", array("id" => $id));
foreach ($hookret as $hookdat) {
    foreach ($hookdat as $k => $v) {
        echo "<td class=\"fieldlabel\">" . $k . "</td><td class=\"fieldarea\" colspan=\"3\">" . $v . "</td></tr>";
    }
}
$additflds = new WHMCS\Domains\AdditionalFields();
$additflds->setDomain($domain)->setDomainType($ordertype)->getFieldValuesFromDatabase($id);
foreach ($additflds->getFieldsForOutput() as $fieldLabel => $inputHTML) {
    echo "<tr><td class=\"fieldlabel\">" . $fieldLabel . "</td><td class=\"fieldarea\" colspan=\"3\">" . $inputHTML . "</td></tr>";
}
echo "<tr>\n    <td class=\"fieldlabel\">";
echo $aInt->lang("fields", "adminnotes");
echo "</td>\n    <td class=\"fieldarea\" colspan=\"3\">\n        <textarea name=\"additionalnotes\" rows=4 class=\"form-control\">";
echo $additionalnotes;
echo "</textarea>\n    </td>\n</tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
echo $aInt->lang("global", "savechanges");
echo "\" class=\"btn btn-primary\" />\n    <input type=\"reset\" value=\"";
echo $aInt->lang("global", "cancelchanges");
echo "\" class=\"btn btn-default\" />\n    <br />\n    <a href=\"#\" data-toggle=\"modal\" data-target=\"#modalDelete\" style=\"color:#cc0000\"><strong>";
echo $aInt->lang("global", "delete");
echo "</strong></a>\n</div>\n\n</form>\n\n<form action=\"clientsemails.php?userid=";
echo $userid;
echo "\" method=\"post\">\n<input type=\"hidden\" name=\"action\" value=\"send\">\n<input type=\"hidden\" name=\"type\" value=\"domain\">\n<input type=\"hidden\" name=\"id\" value=\"";
echo $id;
echo "\">\n<div class=\"contentbox\">";
echo "<B>" . $aInt->lang("global", "sendmessage") . "</B> <select name=\"messageID\" class=\"form-control select-inline\"><option value=\"0\">" . $aInt->lang("emails", "newmessage") . "</option>";
$domainMailTemplates = WHMCS\Mail\Template::where("type", "=", "domain")->where("language", "=", "")->orderBy("name")->get();
foreach ($domainMailTemplates as $template) {
    echo "<option value=\"" . $template->id . "\"";
    if ($template->custom) {
        echo " style=\"background-color:#efefef\"";
    }
    echo ">" . $template->name . "</option>";
}
echo "</select> <input type=\"submit\" value=\"" . $aInt->lang("global", "sendmessage") . "\" class=\"btn btn-default btn-sm\" />";
echo "</div>\n</form>\n";
echo "\n<form method=\"post\" action=\"whois.php\" target=\"_blank\" id=\"frmWhois\">\n<input type=\"hidden\" name=\"domain\" value=\"" . $domain . "\" />\n</form>\n";
echo $modalHtml;
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();

?>