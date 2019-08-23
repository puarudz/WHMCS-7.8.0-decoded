<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Configure General Settings", false);
$aInt->title = $aInt->lang("general", "title");
$aInt->sidebar = "config";
$aInt->icon = "config";
$aInt->helplink = "General Settings";
$aInt->requireAuthConfirmation();
$aInt->requiredFiles(array("clientfunctions"));
$errMgmt = new WHMCS\Utility\ErrorManagement();
$promoHelper = new WHMCS\View\Admin\Marketplace\PromotionHelper();
$promoHelper->hookIntoPage($aInt);
if ($promoHelper->isPromoFetchRequest()) {
    $response = $promoHelper->fetchPromoContent($whmcs->get_req_var("partner"), $whmcs->get_req_var("promodata"));
    $aInt->setBodyContent($response);
}
$whmcs = WHMCS\Application::getInstance();
$action = $whmcs->get_req_var("action");
if ($action == "addWhiteListIp") {
    check_token("WHMCS.admin.default");
    if (defined("DEMO_MODE")) {
        exit;
    }
    $whitelistedips = $whmcs->get_config("WhitelistedIPs");
    $whitelistedips = safe_unserialize($whitelistedips);
    $whitelistedips[] = array("ip" => $ipaddress, "note" => $notes);
    $whmcs->set_config("WhitelistedIPs", safe_serialize($whitelistedips));
    logAdminActivity("General Settings Changed. Whitelisted IP Added: '" . $ipaddress . "'");
    delete_query("tblbannedips", array("ip" => $ipaddress));
    exit;
}
if ($action == "deletewhitelistip") {
    check_token("WHMCS.admin.default");
    if (defined("DEMO_MODE")) {
        exit;
    }
    $removeip = explode(" - ", $removeip);
    $whitelistedips = $whmcs->get_config("WhitelistedIPs");
    $whitelistedips = safe_unserialize($whitelistedips);
    foreach ($whitelistedips as $k => $v) {
        if ($v["ip"] == $removeip[0]) {
            unset($whitelistedips[$k]);
        }
    }
    $whmcs->set_config("WhitelistedIPs", safe_serialize($whitelistedips));
    update_query("tblconfiguration", array("value" => safe_serialize($whitelistedips)), array("setting" => "WhitelistedIPs"));
    logAdminActivity("General Settings Changed. Whitelisted IP Removed: '" . $removeip[0] . "'");
    exit;
} else {
    if ($action == "addApiIp") {
        check_token("WHMCS.admin.default");
        if (defined("DEMO_MODE")) {
            exit;
        }
        $whitelistedips = $whmcs->get_config("APIAllowedIPs");
        $whitelistedips = safe_unserialize($whitelistedips);
        $whitelistedips[] = array("ip" => $ipaddress, "note" => $notes);
        $whmcs->set_config("APIAllowedIPs", safe_serialize($whitelistedips));
        logAdminActivity("General Settings Changed. API Allowed IP Added: '" . $ipaddress . "'");
        exit;
    }
    if ($action == "deleteapiip") {
        check_token("WHMCS.admin.default");
        if (defined("DEMO_MODE")) {
            exit;
        }
        $removeip = explode(" - ", $removeip);
        $whitelistedips = $whmcs->get_config("APIAllowedIPs");
        $whitelistedips = safe_unserialize($whitelistedips);
        foreach ($whitelistedips as $k => $v) {
            if ($v["ip"] == $removeip[0]) {
                unset($whitelistedips[$k]);
            }
        }
        $whmcs->set_config("APIAllowedIPs", safe_serialize($whitelistedips));
        logAdminActivity("General Settings Changed. API Allowed IP Removed: '" . $removeip[0] . "'");
        exit;
    } else {
        if ($action == "addTrustedProxyIp") {
            check_token("WHMCS.admin.default");
            $ipaddress = $whmcs->get_req_var("ipaddress");
            $notes = $whmcs->get_req_var("notes");
            if (strpos($ipaddress, "/") !== false) {
                list($ip, $netmask) = explode("/", $ipaddress, 2);
                $isUserInputAddressValid = WHMCS\Http\IpUtils::checkIp($ip, $ipaddress);
            } else {
                $isUserInputAddressValid = filter_var($ipaddress, FILTER_VALIDATE_IP);
            }
            if (!$isUserInputAddressValid) {
                echo "Failed to update trusted proxy IP list with invalid IP '" . WHMCS\Input\Sanitize::makeSafeForOutput($ipaddress) . "'";
                exit;
            }
            if (defined("DEMO_MODE")) {
                echo "This feature is unavailable in demo mode.";
                exit;
            }
            $whitelistedips = $whmcs->get_config("trustedProxyIps");
            $whitelistedips = json_decode($whitelistedips, true);
            $whitelistedips = is_array($whitelistedips) ? $whitelistedips : array();
            $whitelistedips[] = array("ip" => $ipaddress, "note" => $notes);
            if ($ipaddress == $whmcs->getRemoteIp()) {
                $whmcs->set_config("trustedProxyIps", json_encode($whitelistedips));
                WHMCS\Http\Request::defineProxyTrustFromApplication($whmcs);
                $whmcs->setRemoteIp(WHMCS\Utility\Environment\CurrentUser::getIP());
                $auth = new WHMCS\Auth();
                $auth->getInfobyID(WHMCS\Session::get("adminid"));
                $auth->setSessionVars($whmcs);
            } else {
                $whmcs->set_config("trustedProxyIps", json_encode($whitelistedips));
            }
            logAdminActivity("General Settings Changed. Trusted Proxy IP Added: '" . $ipaddress . "'");
            exit;
        }
        if ($action == "deletetrustedproxyip") {
            check_token("WHMCS.admin.default");
            if (defined("DEMO_MODE")) {
                exit;
            }
            $removeip = explode(" - ", $removeip);
            $whitelistedips = $whmcs->get_config("trustedProxyIps");
            $whitelistedips = json_decode($whitelistedips, true);
            $whitelistedips = is_array($whitelistedips) ? $whitelistedips : array();
            foreach ($whitelistedips as $k => $v) {
                if ($v["ip"] == $removeip[0]) {
                    unset($whitelistedips[$k]);
                }
            }
            $whmcs->set_config("trustedProxyIps", json_encode($whitelistedips));
            WHMCS\Http\Request::defineProxyTrustFromApplication($whmcs);
            $reevaluatedIp = WHMCS\Utility\Environment\CurrentUser::getIP();
            if ($removeip[0] == $reevaluatedIp) {
                $whmcs->setRemoteIp($reevaluatedIp);
                $auth = new WHMCS\Auth();
                $auth->getInfobyID(WHMCS\Session::get("adminid"));
                $auth->setSessionVars($whmcs);
            }
            logAdminActivity("General Settings Changed. Trusted Proxy IP Removed: '" . $removeip[0] . "'");
            exit;
        } else {
            $clientLanguages = WHMCS\Language\ClientLanguage::getLanguages();
            $clientTemplates = array();
            $orderFormTemplates = array();
            try {
                $clientTemplates = WHMCS\View\Template::all();
            } catch (Exception $e) {
                $aInt->gracefulExit("Templates directory is missing. Please reupload /templates/");
            }
            try {
                $orderFormTemplates = WHMCS\View\Template\OrderForm::all();
            } catch (Exception $e) {
                $aInt->gracefulExit("Order Form Templates directory is missing. Please reupload /templates/orderforms/");
            }
            $frm1 = new WHMCS\Form();
            if ($action == "save") {
                check_token("WHMCS.admin.default");
                if (defined("DEMO_MODE")) {
                    redir("demo=1");
                }
                $tab = $whmcs->get_req_var("tab");
                unset($_SESSION["Language"]);
                unset($_SESSION["Template"]);
                unset($_SESSION["OrderFormTemplate"]);
                WHMCS\Session::release();
                $existingConfig = WHMCS\Config\Setting::allAsArray();
                $ticketEmailLimit = intval($whmcs->get_req_var("ticketEmailLimit"));
                if (!$ticketEmailLimit) {
                    redir("tab=" . $tab . "&error=limitnotnumeric");
                }
                if (!WHMCS\Invoice::isValidCustomInvoiceNumberFormat(WHMCS\Input\Sanitize::decode($whmcs->get_req_var("sequentialinvoicenumberformat")))) {
                    redir("tab=" . $tab . "&error=invalidCustomInvoiceNumber");
                }
                $affiliatebonusdeposit = number_format($affiliatebonusdeposit, 2, ".", "");
                $affiliatepayout = number_format($affiliatepayout, 2, ".", "");
                if (!in_array($language, $clientLanguages)) {
                    if (in_array("english", $clientLanguages)) {
                        $language = "english";
                    } else {
                        $language = $clientLanguages[0];
                    }
                }
                if (!$clientTemplates->has($template)) {
                    $template = $clientTemplates->first()->getName();
                }
                if (!$orderFormTemplates->has($orderformtemplate)) {
                    $orderformtemplate = $orderFormTemplates->first()->getName();
                }
                $clientsprofoptional = $clientsprofoptional ? implode(",", $clientsprofoptional) : "";
                $clientsprofuneditable = $clientsprofuneditable ? implode(",", $clientsprofuneditable) : "";
                if ($tcpdffont == "custom" && $tcpdffontcustom) {
                    $tcpdffont = $tcpdffontcustom;
                }
                $addfundsminimum = format_as_currency($addfundsminimum);
                $addfundsmaximum = format_as_currency($addfundsmaximum);
                $addfundsmaximumbalance = format_as_currency($addfundsmaximumbalance);
                $latefeeminimum = format_as_currency($latefeeminimum);
                $domain = cleansystemurl($domain);
                $systemurl = cleansystemurl($systemurl);
                $domphone = App::formatPostedPhoneNumber("domphone");
                $captchaUtility = new WHMCS\Utility\Captcha();
                $captchaFormsSettings = $captchaUtility->getForms();
                $captchaFormsEnabled = $whmcs->get_req_var("captchaform");
                if (!is_array($captchaFormsEnabled)) {
                    $captchaFormsEnabled = array();
                }
                foreach ($captchaFormsSettings as $form => $previousValue) {
                    if (!array_key_exists($form, $captchaFormsEnabled)) {
                        $captchaFormsSettings[$form] = false;
                    } else {
                        $captchaFormsSettings[$form] = true;
                    }
                }
                $captchaFormsSettings = json_encode($captchaFormsSettings);
                $save_arr = array("CompanyName" => WHMCS\Input\Sanitize::decode($companyname), "Email" => $email, "Domain" => $domain, "LogoURL" => $logourl, "InvoicePayTo" => $whmcs->get_req_var("invoicepayto"), "SystemURL" => $systemurl, "Template" => $template, "ActivityLimit" => (int) $whmcs->get_req_var("activitylimit"), "NumRecordstoDisplay" => (int) $whmcs->get_req_var("numrecords"), "MaintenanceMode" => $whmcs->get_req_var("maintenancemode"), "MaintenanceModeMessage" => $whmcs->get_req_var("maintenancemodemessage"), "MaintenanceModeURL" => $maintenancemodeurl, "Charset" => $whmcs->get_req_var("charset"), "DateFormat" => $whmcs->get_req_var("dateformat"), "ClientDateFormat" => $clientdateformat, "DefaultCountry" => $whmcs->get_req_var("defaultcountry"), "Language" => $whmcs->get_req_var("language"), "AllowLanguageChange" => $whmcs->get_req_var("allowuserlanguage"), "EnableTranslations" => (int) $whmcs->get_req_var("enable_translations"), "CutUtf8Mb4" => $whmcs->get_req_var("cututf8mb4"), "PhoneNumberDropdown" => (int) App::getFromRequest("tel-cc-input"), "OrderDaysGrace" => (int) $whmcs->get_req_var("orderdaysgrace"), "OrderFormTemplate" => $orderformtemplate, "OrderFormSidebarToggle" => (int) $whmcs->get_req_var("orderfrmsidebartoggle"), "EnableTOSAccept" => $whmcs->get_req_var("enabletos"), "TermsOfService" => $whmcs->get_req_var("tos"), "AutoRedirectoInvoice" => $whmcs->get_req_var("autoredirecttoinvoice"), "ShowNotesFieldonCheckout" => $whmcs->get_req_var("shownotesfieldoncheckout"), "ProductMonthlyPricingBreakdown" => $whmcs->get_req_var("productmonthlypricingbreakdown"), "AllowDomainsTwice" => $whmcs->get_req_var("allowdomainstwice"), "NoInvoiceEmailOnOrder" => $whmcs->get_req_var("noinvoicemeailonorder"), "SkipFraudForExisting" => $whmcs->get_req_var("skipfraudforexisting"), "AutoProvisionExistingOnly" => $whmcs->get_req_var("autoprovisionexistingonly"), "GenerateRandomUsername" => $whmcs->get_req_var("generaterandomusername"), "ProrataClientsAnniversaryDate" => $whmcs->get_req_var("prorataclientsanniversarydate"), "AllowRegister" => $whmcs->get_req_var("allowregister"), "AllowTransfer" => $whmcs->get_req_var("allowtransfer"), "AllowOwnDomain" => $whmcs->get_req_var("allowowndomain"), "EnableDomainRenewalOrders" => $whmcs->get_req_var("enabledomainrenewalorders"), "AutoRenewDomainsonPayment" => $whmcs->get_req_var("autorenewdomainsonpayment"), "FreeDomainAutoRenewRequiresProduct" => $freedomainautorenewrequiresproduct, "DomainAutoRenewDefault" => $whmcs->get_req_var("domainautorenewdefault"), "DomainToDoListEntries" => $whmcs->get_req_var("domaintodolistentries"), "AllowIDNDomains" => $allowidndomains, "DisableDomainGraceAndRedemptionFees" => (int) App::getFromRequest("disabledomaingrace"), "DomainExpirationFeeHandling" => App::getFromRequest("domainExpiryFeeHandling"), "DefaultNameserver1" => $ns1, "DefaultNameserver2" => $ns2, "DefaultNameserver3" => $ns3, "DefaultNameserver4" => $ns4, "DefaultNameserver5" => $ns5, "RegistrarAdminUseClientDetails" => $domuseclientsdetails, "RegistrarAdminFirstName" => $domfirstname, "RegistrarAdminLastName" => $domlastname, "RegistrarAdminCompanyName" => $domcompanyname, "RegistrarAdminEmailAddress" => $domemail, "RegistrarAdminAddress1" => $domaddress1, "RegistrarAdminAddress2" => $domaddress2, "RegistrarAdminCity" => $domcity, "RegistrarAdminStateProvince" => $domstate, "RegistrarAdminPostalCode" => $dompostcode, "RegistrarAdminCountry" => $domcountry, "RegistrarAdminPhone" => $domphone, "MailType" => $whmcs->get_req_var("mailtype"), "MailEncoding" => $mailencoding, "SMTPPort" => $smtpport, "SMTPHost" => $smtphost, "SMTPUsername" => $smtpusername, "SMTPSSL" => $smtpssl, "EmailCSS" => $whmcs->get_req_var("emailcss"), "Signature" => $whmcs->get_req_var("signature"), "EmailGlobalHeader" => $emailglobalheader, "EmailGlobalFooter" => $emailglobalfooter, "SystemEmailsFromName" => $whmcs->get_req_var("systememailsfromname"), "SystemEmailsFromEmail" => $whmcs->get_req_var("systememailsfromemail"), "BCCMessages" => $bccmessages, "ContactFormDept" => $whmcs->get_req_var("contactformdept"), "ContactFormTo" => $contactformto, "SupportModule" => $whmcs->get_req_var("supportmodule"), "TicketMask" => $ticketmask, "SupportTicketOrder" => $whmcs->get_req_var("supportticketorder"), "TicketEmailLimit" => $ticketEmailLimit, "ShowClientOnlyDepts" => $showclientonlydepts, "RequireLoginforClientTickets" => $whmcs->get_req_var("requireloginforclienttickets"), "SupportTicketKBSuggestions" => $whmcs->get_req_var("supportticketkbsuggestions"), "AttachmentThumbnails" => $attachmentthumbnails, "TicketRatingEnabled" => $whmcs->get_req_var("ticketratingenabled"), "TicketAddCarbonCopyRecipients" => App::getFromRequest("ticket_add_cc"), "PreventEmailReopening" => (bool) $whmcs->get_req_var("preventEmailReopening") ? 1 : 0, "UpdateLastReplyTimestamp" => $lastreplyupdate, "DisableSupportTicketReplyEmailsLogging" => $whmcs->get_req_var("disablesupportticketreplyemailslogging"), "TicketAllowedFileTypes" => $whmcs->get_req_var("allowedfiletypes"), "NetworkIssuesRequireLogin" => $whmcs->get_req_var("networkissuesrequirelogin"), "DownloadsIncludeProductLinked" => $dlinclproductdl, "ContinuousInvoiceGeneration" => $whmcs->get_req_var("continuousinvoicegeneration"), "EnablePDFInvoices" => $whmcs->get_req_var("enablepdfinvoices"), "PDFPaperSize" => $pdfpapersize, "TCPDFFont" => $tcpdffont, "StoreClientDataSnapshotOnInvoiceCreation" => $invoiceclientdatasnapshot, "EnableMassPay" => $whmcs->get_req_var("enablemasspay"), "AllowCustomerChangeInvoiceGateway" => $whmcs->get_req_var("allowcustomerchangeinvoicegateway"), "GroupSimilarLineItems" => $whmcs->get_req_var("groupsimilarlineitems"), "CancelInvoiceOnCancellation" => $cancelinvoiceoncancel, "AutoCancelSubscriptions" => $autoCancelSubscriptions, "EnableProformaInvoicing" => $enableProformaInvoicing, "SequentialInvoiceNumbering" => $whmcs->get_req_var("sequentialinvoicenumbering"), "SequentialInvoiceNumberFormat" => $whmcs->get_req_var("sequentialinvoicenumberformat"), "LateFeeType" => $whmcs->get_req_var("latefeetype"), "InvoiceLateFeeAmount" => $whmcs->get_req_var("invoicelatefeeamount"), "LateFeeMinimum" => $whmcs->get_req_var("latefeeminimum"), "ShowCCIssueStart" => $whmcs->get_req_var("showccissuestart"), "InvoiceIncrement" => (int) $whmcs->get_req_var("invoiceincrement"), "AddFundsEnabled" => $addfundsenabled, "AddFundsMinimum" => $addfundsminimum, "AddFundsMaximum" => $addfundsmaximum, "AddFundsMaximumBalance" => $addfundsmaximumbalance, "AddFundsRequireOrder" => $whmcs->get_req_var("addfundsrequireorder"), "NoAutoApplyCredit" => App::getFromRequest("noautoapplycredit") ? "" : "on", "CreditOnDowngrade" => App::getFromRequest("creditondowngrade"), "AffiliateEnabled" => $affiliateenabled, "AffiliateEarningPercent" => $affiliateearningpercent, "AffiliateBonusDeposit" => $affiliatebonusdeposit, "AffiliatePayout" => $affiliatepayout, "AffiliatesDelayCommission" => $affiliatesdelaycommission, "AffiliateDepartment" => $affiliatedepartment, "AffiliateLinks" => $affiliatelinks, "CaptchaSetting" => $whmcs->get_req_var("captchasetting"), "CaptchaType" => $captchatype, "ReCAPTCHAPublicKey" => $recaptchapublickey, "ReCAPTCHAPrivateKey" => $recaptchaprivatekey, "CaptchaForms" => $captchaFormsSettings, "EnableEmailVerification" => (int) $whmcs->get_req_var("enable_email_verification"), "AutoGeneratedPasswordFormat" => $autogeneratedpwformat, "RequiredPWStrength" => (int) $whmcs->get_req_var("requiredpwstrength"), "InvalidLoginBanLength" => (int) $whmcs->get_req_var("invalidloginsbanlength"), "sendFailedLoginWhitelist" => $sendFailedLoginWhitelist != "" ? 1 : 0, "DisableAdminPWReset" => $disableadminpwreset, "CCAllowCustomerDelete" => $whmcs->get_req_var("ccallowcustomerdelete"), "DisableSessionIPCheck" => $whmcs->get_req_var("disablesessionipcheck"), "AllowSmartyPhpTags" => $allowsmartyphptags, "proxyHeader" => (string) $whmcs->get_req_var("proxyheader"), "LogAPIAuthentication" => (int) $logapiauthentication, "TwitterUsername" => $twitterusername, "AnnouncementsTweet" => $announcementstweet, "AnnouncementsFBRecommend" => $announcementsfbrecommend, "AnnouncementsFBComments" => $announcementsfbcomments, "AllowClientsEmailOptOut" => (int) $whmcs->get_req_var("allowclientsemailoptout"), "EmailMarketingRequireOptIn" => (int) $whmcs->get_req_var("marketingreqoptin"), "EmailMarketingOptInMessage" => $whmcs->get_req_var("marketingoptinmessage"), "ClientDisplayFormat" => $whmcs->get_req_var("clientdisplayformat"), "DefaultToClientArea" => $whmcs->get_req_var("defaulttoclientarea"), "AllowClientRegister" => $whmcs->get_req_var("allowclientregister"), "ClientsProfileOptionalFields" => $clientsprofoptional, "ClientsProfileUneditableFields" => $clientsprofuneditable, "SendEmailNotificationonUserDetailsChange" => $whmcs->get_req_var("sendemailnotificationonuserdetailschange"), "ShowCancellationButton" => $whmcs->get_req_var("showcancel"), "SendAffiliateReportMonthly" => $whmcs->get_req_var("affreport"), "BannedSubdomainPrefixes" => $bannedsubdomainprefixes, "EnableSafeInclude" => $whmcs->get_req_var("enablesafeinclude"), "DisplayErrors" => $whmcs->get_req_var("displayerrors"), "LogErrors" => $whmcs->get_req_var("logerrors"), "SQLErrorReporting" => $whmcs->get_req_var("sqlerrorreporting"), "HooksDebugMode" => $hooksdebugmode);
                if ($whmcs->get_req_var("sequentialinvoicenumbervalue") && is_numeric($whmcs->get_req_var("sequentialinvoicenumbervalue"))) {
                    $save_arr["SequentialInvoiceNumberValue"] = $whmcs->get_req_var("sequentialinvoicenumbervalue");
                }
                $booleanKeys = array("MaintenanceMode", "AllowLanguageChange", "CutUtf8Mb4", "EnableTOSAccept", "ShowNotesFieldonCheckout", "ProductMonthlyPricingBreakdown", "AllowDomainsTwice", "NoInvoiceEmailOnOrder", "SkipFraudForExisting", "AutoProvisionExistingOnly", "GenerateRandomUsername", "ProrataClientsAnniversaryDate", "EnableTranslations", "CutUtf8Mb4", "PhoneNumberDropdown", "AllowRegister", "AllowTransfer", "AllowOwnDomain", "EnableDomainRenewalOrders", "AutoRenewDomainsonPayment", "FreeDomainAutoRenewRequiresProduct", "DomainAutoRenewDefault", "DomainToDoListEntries", "AllowIDNDomains", "RegistrarAdminUseClientDetails", "ShowClientOnlyDepts", "RequireLoginforClientTickets", "SupportTicketKBSuggestions", "TicketRatingEnabled", "TicketAddCarbonCopyRecipients", "PreventEmailReopening", "DisableSupportTicketReplyEmailsLogging", "NetworkIssuesRequireLogin", "DownloadsIncludeProductLinked", "ContinuousInvoiceGeneration", "EnablePDFInvoices", "StoreClientDataSnapshotOnInvoiceCreation", "EnableMassPay", "AllowCustomerChangeInvoiceGateway", "GroupSimilarLineItems", "CancelInvoiceOnCancellation", "AutoCancelSubscriptions", "EnableProformaInvoicing", "SequentialInvoiceNumbering", "ShowCCIssueStart", "AddFundsEnabled", "AddFundsRequireOrder", "CreditOnDowngrade", "AffiliateEnabled", "EnableEmailVerification", "sendFailedLoginWhitelist", "DisableAdminPWReset", "CCAllowCustomerDelete", "DisableSessionIPCheck", "AllowSmartyPhpTags", "LogAPIAuthentication", "AnnouncementsTweet", "AnnouncementsFBRecommend", "AnnouncementsFBComments", "AllowClientsEmailOptOut", "EmailMarketingRequireOptIn", "DefaultToClientArea", "AllowClientRegister", "SendEmailNotificationonUserDetailsChange", "ShowCancellationButton", "SendAffiliateReportMonthly", "EnableSafeInclude", "DisplayErrors", "LogErrors", "SQLErrorReporting", "HooksDebugMode");
                $basicLoggingKeys = array("InvoicePayTo", "MaintenanceModeMessage", "EmailCSS", "Signature", "EmailGlobalHeader", "EmailGlobalFooter", "NoAutoApplyCredit", "AffiliateLinks", "ReCAPTCHAPublicKey", "ReCAPTCHAPrivateKey", "BannedSubdomainPrefixes");
                $secureKeys = array("SMTPPassword");
                $changes = array();
                $newPassword = trim($whmcs->get_req_var("smtppassword"));
                $originalPassword = decrypt($whmcs->get_config("SMTPPassword"));
                $valueToStore = interpretMaskedPasswordChangeForStorage($newPassword, $originalPassword);
                if ($valueToStore !== false) {
                    $save_arr["SMTPPassword"] = $valueToStore;
                    if ($newPassword != $originalPassword) {
                        $changes[] = "SMTP Password Changed";
                    }
                }
                foreach ($save_arr as $k => $v) {
                    WHMCS\Config\Setting::setValue($k, trim($v));
                    if ($existingConfig[$k] != trim($v) && !in_array($k, $secureKeys)) {
                        $regEx = "/(?<=[a-z])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])/x";
                        $friendlySettingParts = preg_split($regEx, $k);
                        $friendlySetting = implode(" ", $friendlySettingParts);
                        if (in_array($k, $booleanKeys)) {
                            if (!$v || $v === false || $v == "off") {
                                $changes[] = (string) $friendlySetting . " Disabled";
                            } else {
                                $changes[] = (string) $friendlySetting . " Enabled";
                                if ($k == "StoreClientDataSnapshotOnInvoiceCreation") {
                                    $snapShot = new WHMCS\Billing\Invoice\Snapshot();
                                    $snapShot->createTable();
                                }
                            }
                        } else {
                            if (in_array($k, $basicLoggingKeys)) {
                                $changes[] = (string) $friendlySetting . " Changed";
                            } else {
                                $changes[] = (string) $friendlySetting . " Changed from '" . $existingConfig[$k] . "' to '" . $v . "'";
                            }
                        }
                    }
                }
                if ($continuousinvoicegeneration == "on" && !$CONFIG["ContinuousInvoiceGeneration"]) {
                    full_query("UPDATE tblhosting SET nextinvoicedate = nextduedate");
                    full_query("UPDATE tbldomains SET nextinvoicedate = nextduedate");
                    full_query("UPDATE tblhostingaddons SET nextinvoicedate = nextduedate");
                }
                $token_manager =& getTokenManager();
                $token_manager->processAdminHTMLSave($whmcs);
                $tokenNamespaces = WHMCS\Config\Setting::getValue("token_namespaces");
                if ($existingConfig["token_namespaces"] != $tokenNamespaces) {
                    $changes[] = "CSRF Token Settings changed";
                }
                $invoicestartnumber = (int) $whmcs->get_req_var("invoicestartnumber");
                if (0 < $invoicestartnumber) {
                    $maxinvnum = get_query_val("tblinvoiceitems", "invoiceid", "", "invoiceid", "DESC", "0,1");
                    if ($invoicestartnumber < $maxinvnum) {
                        if ($changes) {
                            logAdminActivity("General Settings Modified. Changes made: " . implode(". ", $changes) . ".");
                        }
                        redir("tab=" . $tab . "&error=invnumtoosml");
                    }
                    full_query("ALTER TABLE tblinvoices AUTO_INCREMENT = " . (int) $invoicestartnumber);
                    $changes[] = "Invoice Starting Number Changed to " . $invoicestartnumber;
                }
                if ($changes) {
                    logAdminActivity("General Settings Modified. Changes made: " . implode(". ", $changes) . ".");
                }
                redir("tab=" . $tab . "&success=true");
            }
            WHMCS\Session::release();
            ob_start();
            $jquerycode .= "\n\$(\"#enableProformaInvoicing\").click(function() {\n    if (\$(\"#enableProformaInvoicing\").is(\":checked\")) {\n        \$(\"#sequentialpaidnumbering\").prop(\"checked\", true);\n        \$(\"#sequentialpaidnumbering\").prop(\"disabled\", true);\n    } else {\n        \$(\"#sequentialpaidnumbering\").prop(\"disabled\", false);\n    }\n});\n\$(\"#saveChanges\").click(function() {\n     \$(\"#sequentialpaidnumbering\").prop(\"disabled\", false);\n});\n\n\$(\"#removewhitelistedip\").click(function () {\n    var removeip = \$('#whitelistedips option:selected').text();\n    \$('#whitelistedips option:selected').remove();\n    WHMCS.http.jqClient.post(\"configgeneral.php\", { action: \"deletewhitelistip\", removeip: removeip, token: \"" . generate_token("plain") . "\" });\n    return false;\n});\nfunction checkToDisplayAccessDeniedMessage(\$box, responseText)\n{\n    var errorResponse;\n    var errorResponseHtml;\n\n    // Check if access was denied.  If so, load the error page.\n    if (responseText.toLowerCase().indexOf(\"error-page\") !== -1) {\n        // Create a jQuery object from the page's response,\n        // so it can be traversed.\n        errorResponse = jQuery(\"<div>\", { html: responseText });\n\n        // Remove the \"Access Denied\" <h1> tag.\n        errorResponse.find(\"h1\").remove();\n        // Remove the \"Go Back\" button.\n        errorResponse.find(\".error-footer\").remove();\n\n        // Find the markup for the error page.\n        errorResponseHtml = errorResponse.find(\"#contentarea\")\n            .html();\n\n        // Load the error page's markup.\n        \$box.html(errorResponseHtml);\n    }\n}\n\n\$(\"#removetrustedproxyip\").click(function () {\n    var removeip = \$('#trustedproxyips option:selected').text();\n    \$('#trustedproxyips option:selected').remove();\n    WHMCS.http.jqClient.post(\"configgeneral.php\", { action: \"deletetrustedproxyip\", removeip: removeip, token: \"" . generate_token("plain") . "\" });\n    return false;\n});\n\$(\"#removeapiip\").click(function () {\n    var removeip = \$('#apiallowedips option:selected').text();\n    \$('#apiallowedips option:selected').remove();\n    WHMCS.http.jqClient.post(\"configgeneral.php\", { action: \"deleteapiip\", removeip: removeip, token: \"" . generate_token("plain") . "\" });\n    return false;\n});\n\n\$(\"#btnDeleteLocalCards\").click(function () {\n    var self = this;\n    swal({\n            title: \"" . AdminLang::trans("global.areYouSure") . "\",\n            text: \"" . AdminLang::trans("general.deleteLocalCardsInfo") . "\",\n            type: \"warning\",\n            dangerMode: true,\n            showCancelButton: true,\n            confirmButtonColor: \"#DD6B55\",\n            confirmButtonText: \"" . AdminLang::trans("global.yes") . "\",\n            cancelButtonText: \"" . AdminLang::trans("global.no") . "\"\n        },\n        function(isConfirm){\n            if (isConfirm) {\n                WHMCS.http.jqClient.jsonPost({\n                    url: \"" . routePath("admin-setup-payments-deletelocalcards") . "\",\n                    data: {\n                        token: \"" . generate_token("plain") . "\"\n                    },\n                    success: function(data) {\n                        if (data.success) {\n                            // growl success\n                            jQuery.growl.notice(\n                                {\n                                    title: data.successMsgTitle,\n                                    message: data.successMsg\n                                }\n                            );\n                        }\n                    },\n                });\n            }\n        }\n    );\n});\n";
            echo $aInt->modal("AddTrustedProxyIp", $aInt->lang("general", "addtrustedproxy"), "<table id=\"add-trusted-proxy-ip-table\"><tr><td>" . $aInt->lang("fields", "ipaddressorrange") . ":</td><td><input type=\"text\" id=\"ipaddress3\" class=\"form-control\" /></td></tr>" . "<tr><td></td><td>" . $aInt->lang("fields", "ipaddressorrangeinfo") . " <a href=\"https://docs.whmcs.com/Security_Tab#Trusted_Proxies\" target=\"_blank\">" . $aInt->lang("help", "contextlink") . "?</a></td></tr><tr><td>" . $aInt->lang("fields", "adminnotes") . ":</td><td><input type=\"text\" id=\"notes3\" class=\"form-control\" /></td></tr></table>", array(array("title" => $aInt->lang("general", "addip"), "onclick" => "addTrustedProxyIp(jQuery(\"#ipaddress3\").val(),jQuery(\"#notes3\").val());"), array("title" => $aInt->lang("global", "cancel"))));
            echo $aInt->modal("AddWhiteListIp", $aInt->lang("general", "addwhitelistedip"), "<table id=\"add-white-listed-ip-table\"><tr><td>" . $aInt->lang("fields", "ipaddress") . ":</td><td><input type=\"text\" id=\"ipaddress\" class=\"form-control\" /></td></tr>" . "<tr><td>" . $aInt->lang("fields", "reason") . ":</td><td><input type=\"text\" id=\"notes\" class=\"form-control\" />" . "</td></tr></table>", array(array("title" => $aInt->lang("general", "addip"), "onclick" => "addWhiteListedIp(jQuery(\"#ipaddress\").val(), jQuery(\"#notes\").val());"), array("title" => $aInt->lang("global", "cancel"))), "small");
            echo $aInt->modal("AddApiIp", $aInt->lang("general", "addwhitelistedip"), "<table><tr><td>" . $aInt->lang("fields", "ipaddress") . ":</td><td><input type=\"text\" id=\"ipaddress2\" class=\"form-control\" /></td></tr>" . "<tr><td>" . $aInt->lang("fields", "notes") . ":</td><td><input type=\"text\" id=\"notes2\" class=\"form-control\" />" . "</td></tr></table>", array(array("title" => $aInt->lang("general", "addip"), "onclick" => "addApiIp(jQuery(\"#ipaddress2\").val(), jQuery(\"#notes2\").val());"), array("title" => $aInt->lang("global", "cancel"))), "small");
            $token = generate_token("plain");
            $jsCode = "function addTrustedProxyIp(ipaddress, note) {\n    WHMCS.http.jqClient.post(\n        \"configgeneral.php\",\n        {\n            action: \"addTrustedProxyIp\",\n            ipaddress: ipaddress,\n            notes: note,\n            token: \"" . $token . "\"\n        },\n        function (data) {\n            if (data) {\n                alert(data);\n            } else {\n                jQuery('#trustedproxyips').append('<option>' + ipaddress + ' - ' + note + '</option>');\n                jQuery('#modalAddTrustedProxyIp').modal('hide');\n            }\n        }\n    );\n    return false;\n}\n\nfunction addWhiteListedIp(ipaddress, note) {\n    jQuery('#whitelistedips').append('<option>' + ipaddress + ' - ' + note + '</option>');\n    WHMCS.http.jqClient.post(\n        \"configgeneral.php\",\n        {\n            action: \"addWhiteListIp\",\n            ipaddress: ipaddress,\n            notes: note,\n            token: \"" . $token . "\"\n        }\n    );\n    jQuery('#modalAddWhiteListIp').modal('hide');\n    return false;\n}\n\nfunction addApiIp(ipaddress, note) {\n    jQuery('#apiallowedips').append('<option>' + ipaddress + ' - ' + note + '</option>');\n    WHMCS.http.jqClient.post(\n        \"configgeneral.php\",\n        {\n            action: \"addApiIp\",\n            ipaddress: ipaddress,\n            notes: note,\n            token: \"" . $token . "\"\n        }\n    );\n    jQuery('#modalAddApiIp').modal('hide');\n    return false;\n}";
            $infobox = "";
            if (defined("DEMO_MODE")) {
                infoBox("Demo Mode", "Actions on this page are unavailable while in demo mode. Changes will not be saved.");
            }
            if (!empty($success)) {
                infoBox($aInt->lang("general", "changesuccess"), $aInt->lang("general", "changesuccessinfo"));
            }
            if (isset($error)) {
                if ($error == "invnumtoosml") {
                    infoBox($aInt->lang("global", "validationerror"), $aInt->lang("general", "errorinvnumtoosml"), "error");
                } else {
                    if ($error == "limitnotnumeric") {
                        infoBox($aInt->lang("global", "validationerror"), $aInt->lang("general", "limitNotNumeric"), "error");
                    } else {
                        if ($error == "invalidCustomInvoiceNumber") {
                            infoBox($aInt->lang("general", "sequentialpaidformat") . " " . $aInt->lang("global", "validationerror"), $aInt->lang("general", "sequentialPaidNumberValidationFail"), "error");
                        }
                    }
                }
            }
            echo $infobox;
            $result = select_query("tblconfiguration", "", "");
            while ($data = mysql_fetch_array($result)) {
                $setting = $data["setting"];
                $value = $data["value"];
                $CONFIG[(string) $setting] = (string) $value;
            }
            $hasMbstring = extension_loaded("mbstring");
            $validMailEncodings = WHMCS\Mail::getValidEncodings();
            $tcpdfDefaultFonts = array("courier", "freesans", "helvetica", "times", "dejavusans");
            $defaultFont = false;
            $activeFontName = $whmcs->get_config("TCPDFFont");
            echo "\n<form method=\"post\" action=\"";
            echo $whmcs->getPhpSelf();
            echo "?action=save\" name=\"configfrm\">\n\n";
            echo $aInt->beginAdminTabs(array($aInt->lang("general", "tabgeneral"), $aInt->lang("general", "tablocalisation"), $aInt->lang("general", "tabordering"), $aInt->lang("general", "tabdomains"), $aInt->lang("general", "tabmail"), $aInt->lang("general", "tabsupport"), $aInt->lang("general", "tabinvoices"), $aInt->lang("general", "tabcredit"), $aInt->lang("general", "tabaffiliates"), $aInt->lang("general", "tabsecurity"), $aInt->lang("general", "tabsocial"), $aInt->lang("general", "tabother")), true);
            echo "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\" style=\"min-width:200px;\">";
            echo $aInt->lang("fields", "companyname");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"companyname\" value=\"";
            echo WHMCS\Input\Sanitize::makeSafeForOutput($CONFIG["CompanyName"]);
            echo "\" class=\"form-control input-inline input-300\"> ";
            echo $aInt->lang("general", "companynameinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("fields", "email");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"email\" value=\"";
            echo $CONFIG["Email"];
            echo "\" class=\"form-control input-inline input-400\"> ";
            echo $aInt->lang("general", "emailaddressinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("fields", "domain");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"domain\" value=\"";
            echo $CONFIG["Domain"];
            echo "\" class=\"form-control input-inline input-400\"> ";
            echo $aInt->lang("general", "domaininfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "logourl");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"logourl\" value=\"";
            echo $CONFIG["LogoURL"];
            echo "\" class=\"form-control\">";
            echo $aInt->lang("general", "logourlinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "payto");
            echo "</td><td class=\"fieldarea\"><div class=\"row\"><div class=\"col-sm-8 col-md-6\"><textarea cols=\"50\" rows=\"5\" name=\"invoicepayto\" class=\"form-control bottom-margin-5\">";
            echo $CONFIG["InvoicePayTo"];
            echo "</textarea></div></div>";
            echo $aInt->lang("general", "paytoinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "systemurl");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"systemurl\" value=\"";
            echo $CONFIG["SystemURL"];
            echo "\" class=\"form-control input-inline input-400\"><br>";
            echo $aInt->lang("general", "systemurlinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "template");
            echo "</td><td class=\"fieldarea\"><select name=\"template\" class=\"form-control select-inline\">";
            $defaultTemplate = WHMCS\View\Template::getDefault();
            if (is_null($defaultTemplate)) {
                $defaultTemplate = WHMCS\View\Template::factory();
            }
            foreach ($clientTemplates as $template) {
                $selected = $template->getName() == $defaultTemplate->getName() ? " selected" : "";
                $friendlyName = ucfirst($template->getName());
                if ($template->getName() != "kayako") {
                    echo "<option value=\"" . $template->getName() . "\"" . $selected . ">" . $friendlyName . "</option>";
                }
            }
            echo " </select> ";
            echo $aInt->lang("general", "templateinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "limitactivitylog");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"activitylimit\" value=\"";
            echo $CONFIG["ActivityLimit"];
            echo "\" class=\"form-control input-inline input-100\"> ";
            echo $aInt->lang("general", "limitactivityloginfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "recstodisplay");
            echo "</td><td class=\"fieldarea\"><select name=\"numrecords\" class=\"form-control select-inline\">\n<option";
            if ($CONFIG["NumRecordstoDisplay"] == "25") {
                echo " selected";
            }
            echo ">25\n<option";
            if ($CONFIG["NumRecordstoDisplay"] == "50") {
                echo " selected";
            }
            echo ">50\n<option";
            if ($CONFIG["NumRecordstoDisplay"] == "100") {
                echo " selected";
            }
            echo ">100\n<option";
            if ($CONFIG["NumRecordstoDisplay"] == "200") {
                echo " selected";
            }
            echo ">200\n</select></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "maintmode");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"maintenancemode\"";
            if ($CONFIG["MaintenanceMode"]) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "maintmodeinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "maintmodemessage");
            echo "</td><td class=\"fieldarea\"><textarea rows=\"3\" name=\"maintenancemodemessage\" class=\"form-control\">";
            echo $CONFIG["MaintenanceModeMessage"];
            echo "</textarea></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "maintmodeurl");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"maintenancemodeurl\" value=\"";
            echo isset($CONFIG["MaintenanceModeURL"]) ? $CONFIG["MaintenanceModeURL"] : "";
            echo "\" class=\"form-control\">";
            echo $aInt->lang("general", "maintmodeurlinfo");
            echo "</td></tr>\n    <tr>\n        <td class=\"fieldlabel\">";
            echo AdminLang::trans("uriPathMgmt.labelFriendlyUrls");
            echo "</td>\n        <td class=\"fieldarea\">\n            ";
            echo (new WHMCS\Admin\Setup\General\UriManagement\View\Helper\SimpleSetting())->getSimpleSettingHtmlPartial();
            echo "        </td>\n    </tr>\n</table>\n\n";
            echo $aInt->nextAdminTab();
            echo "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\" style=\"min-width:200px;\">";
            echo $aInt->lang("general", "charset");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"charset\" value=\"";
            echo $CONFIG["Charset"];
            echo "\" class=\"form-control input-inline input-200\"> Default: utf-8</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "dateformat");
            echo "</td><td class=\"fieldarea\"><select name=\"dateformat\" class=\"form-control select-inline\"><option value=\"DD/MM/YYYY\"";
            if ($CONFIG["DateFormat"] == "DD/MM/YYYY") {
                echo " SELECTED";
            }
            echo ">DD/MM/YYYY<option value=\"DD.MM.YYYY\"";
            if ($CONFIG["DateFormat"] == "DD.MM.YYYY") {
                echo " SELECTED";
            }
            echo ">DD.MM.YYYY<option value=\"DD-MM-YYYY\"";
            if ($CONFIG["DateFormat"] == "DD-MM-YYYY") {
                echo " SELECTED";
            }
            echo ">DD-MM-YYYY<option value=\"MM/DD/YYYY\"";
            if ($CONFIG["DateFormat"] == "MM/DD/YYYY") {
                echo " SELECTED";
            }
            echo ">MM/DD/YYYY<option value=\"YYYY/MM/DD\"";
            if ($CONFIG["DateFormat"] == "YYYY/MM/DD") {
                echo " SELECTED";
            }
            echo ">YYYY/MM/DD<option value=\"YYYY-MM-DD\"";
            if ($CONFIG["DateFormat"] == "YYYY-MM-DD") {
                echo " SELECTED";
            }
            echo ">YYYY-MM-DD</select> ";
            echo $aInt->lang("general", "dateformatinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "clientdateformat");
            echo "</td><td class=\"fieldarea\"><select name=\"clientdateformat\" class=\"form-control select-inline\">\n";
            if (!isset($CONFIG["ClientDateFormat"])) {
                $CONFIG["ClientDateFormat"] = "";
            }
            echo "<option value=\"\"";
            if ($CONFIG["ClientDateFormat"] == "") {
                echo " selected";
            }
            echo ">";
            echo AdminLang::trans("general.useglobaldateformat");
            echo "</option>\n<option value=\"full\"";
            if ($CONFIG["ClientDateFormat"] == "full") {
                echo " selected";
            }
            echo ">1st January 2000</option>\n<option value=\"shortmonth\"";
            if ($CONFIG["ClientDateFormat"] == "shortmonth") {
                echo " selected";
            }
            echo ">1st Jan 2000</option>\n<option value=\"fullday\"";
            if ($CONFIG["ClientDateFormat"] == "fullday") {
                echo " selected";
            }
            echo ">Monday, January 1st, 2000</option>\n</select> ";
            echo $aInt->lang("general", "clientdateformatinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "defaultcountry");
            echo "</td><td class=\"fieldarea\">";
            echo getCountriesDropDown($CONFIG["DefaultCountry"], "defaultcountry");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "defaultlanguage");
            echo "</td><td class=\"fieldarea\"><select name=\"language\" class=\"form-control select-inline\">";
            $language = WHMCS\Language\ClientLanguage::getValidLanguageName($whmcs->get_config("Language"));
            foreach ($clientLanguages as $lang) {
                echo "<option value=\"" . $lang . "\"";
                if ($lang == $language) {
                    echo " selected=\"selected\"";
                }
                echo ">" . ucfirst($lang) . "</option>";
            }
            echo " </select></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "languagemenu");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"allowuserlanguage\"";
            if ($CONFIG["AllowLanguageChange"] == "on") {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "languagechange");
            echo "</label></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
            echo AdminLang::trans("general.enableTranslations");
            echo "    </td>\n    <td class=\"fieldarea\">\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"enable_translations\" value=\"1\"";
            if (WHMCS\Config\Setting::getValue("EnableTranslations")) {
                echo " checked";
            }
            echo ">\n            ";
            echo AdminLang::trans("general.enableTranslationsDescription");
            echo "        </label>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "utf8mb4cut");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"cututf8mb4\"";
            if ($CONFIG["CutUtf8Mb4"] == "on") {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "utf8mb4cuttext");
            echo "</label></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
            echo AdminLang::trans("general.phoneNumberDropdown");
            echo "    </td>\n    <td class=\"fieldarea\">\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"tel-cc-input\" value=\"1\"";
            if (WHMCS\Config\Setting::getValue("PhoneNumberDropdown")) {
                echo " checked=\"checked\"";
            }
            echo ">\n            ";
            echo AdminLang::trans("general.phoneNumberDropdownText");
            echo "        </label>\n    </td>\n</tr>\n</table>\n\n";
            echo $aInt->nextAdminTab();
            echo "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\" style=\"min-width:200px;\">";
            echo $aInt->lang("general", "ordergrace");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"orderdaysgrace\" value=\"";
            echo $CONFIG["OrderDaysGrace"];
            echo "\" class=\"form-control input-inline input-80\"> ";
            echo $aInt->lang("general", "ordergraceinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\" width=\"220\">";
            echo $aInt->lang("general", "defaultordertemplate");
            echo "</td><td class=\"fieldarea\">\n";
            foreach ($orderFormTemplates as $template) {
                $checked = $template->isDefault() ? " checked" : "";
                $friendlyName = $template->getDisplayName();
                echo "    <div style=\"float:left;padding:10px;text-align:center;\">\n        <label class=\"radio-inline\">\n            <img src=\"" . $template->getThumbnailWebPath() . "\" width=\"165\" height=\"90\" style=\"border:5px solid #fff;\" /><br />\n            <input id=\"orderformtemplate-" . $template->getName() . "\" type=\"radio\" name=\"orderformtemplate\" value=\"" . $template->getName() . "\"" . $checked . "> " . $friendlyName . "\n        </label>\n    </div>";
            }
            echo "</td></tr>\n<tr>\n    <td class=\"fieldlabel\">";
            echo $aInt->lang("general", "orderfrmsidebartoggle");
            echo "</td>\n    <td class=\"fieldarea\">\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"orderfrmsidebartoggle\" value=\"1\"";
            if (WHMCS\Config\Setting::getValue("OrderFormSidebarToggle")) {
                echo " checked";
            }
            echo " />\n            ";
            echo $aInt->lang("general", "orderfrmsidebartoggleinfo");
            echo "        </label>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "tos");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"enabletos\"";
            if ($CONFIG["EnableTOSAccept"] == "on") {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "tosinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "tosurl");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"tos\" value=\"";
            echo $CONFIG["TermsOfService"];
            echo "\" class=\"form-control\">";
            echo $aInt->lang("general", "tosurlinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "autoredirect");
            echo "</td><td class=\"fieldarea\"><label class=\"radio-inline\"><input type=\"radio\" name=\"autoredirecttoinvoice\" value=\"\"";
            if ($CONFIG["AutoRedirectoInvoice"] == "") {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "noredirect");
            echo "</label><br><label class=\"radio-inline\"><input type=\"radio\" name=\"autoredirecttoinvoice\" value=\"on\"";
            if ($CONFIG["AutoRedirectoInvoice"] == "on") {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "invoiceredirect");
            echo "</label><br><label class=\"radio-inline\"><input type=\"radio\" name=\"autoredirecttoinvoice\" value=\"gateway\"";
            if ($CONFIG["AutoRedirectoInvoice"] == "gateway") {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "gatewayredirect");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "checkoutnotes");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"shownotesfieldoncheckout\"";
            if ($CONFIG["ShowNotesFieldonCheckout"] == "on") {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "checkoutnotesinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "pricingbreakdown");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"productmonthlypricingbreakdown\"";
            if ($CONFIG["ProductMonthlyPricingBreakdown"] == "on") {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "pricingbreakdowninfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "blockdomains");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"allowdomainstwice\"";
            if ($CONFIG["AllowDomainsTwice"] == "on") {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "blockdomainsinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "noinvoiceemail");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"noinvoicemeailonorder\"";
            if ($CONFIG["NoInvoiceEmailOnOrder"]) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "noinvoiceemailinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "skipfraudexisting");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"skipfraudforexisting\"";
            if ($CONFIG["SkipFraudForExisting"]) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "skipfraudexistinginfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "autoexisting");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"autoprovisionexistingonly\"";
            if ($CONFIG["AutoProvisionExistingOnly"]) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "autoexistinginfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "randomuser");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"generaterandomusername\"";
            if ($CONFIG["GenerateRandomUsername"]) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "randomuserinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "prorataanniversary");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" id=\"prorataclientsanniversarydate\" name=\"prorataclientsanniversarydate\"";
            if ($CONFIG["ProrataClientsAnniversaryDate"]) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "prorataanniversaryinfo");
            echo "</label></td></tr>\n</table>\n\n";
            echo $aInt->nextAdminTab();
            echo "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\" style=\"min-width:200px;\">";
            echo $aInt->lang("general", "domainoptions");
            echo "</td><td class=\"fieldarea\">\n<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"allowregister\"";
            if ($CONFIG["AllowRegister"] == "on") {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "domainoptionsreg");
            echo "</label><br>\n<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"allowtransfer\"";
            if ($CONFIG["AllowTransfer"] == "on") {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "domainoptionstran");
            echo "</label><br>\n<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"allowowndomain\"";
            if ($CONFIG["AllowOwnDomain"] == "on") {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "domainoptionsown");
            echo "</label>\n</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "enablerenewal");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"enabledomainrenewalorders\"";
            if ($CONFIG["EnableDomainRenewalOrders"]) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "enablerenewalinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "autorenew");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"autorenewdomainsonpayment\"";
            if ($CONFIG["AutoRenewDomainsonPayment"] == "on") {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "autorenewinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "autorenewrequireproduct");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"freedomainautorenewrequiresproduct\"";
            if ($CONFIG["FreeDomainAutoRenewRequiresProduct"] == "on") {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "autorenewrequireproductinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "defaultrenew");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"domainautorenewdefault\"";
            if ($CONFIG["DomainAutoRenewDefault"] == "on") {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "defaultrenewinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "domaintodolistentries");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"domaintodolistentries\"";
            if ($CONFIG["DomainToDoListEntries"]) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "domaintodolistentriesinfo");
            echo "</label></td></tr>\n<tr>\n    <td class=\"fieldlabel\">";
            echo $aInt->lang("general", "allowidndomains");
            echo "</td>\n    <td class=\"fieldarea\">\n        <label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"allowidndomains\"";
            if (!empty($CONFIG["AllowIDNDomains"])) {
                echo " checked";
            }
            echo " ";
            echo $hasMbstring === false ? "disabled=\"disabled\"" : "";
            echo " /> ";
            echo $aInt->lang("general", "allowidndomainsinfo");
            echo "        </label>\n";
            if ($hasMbstring === false) {
                echo "        <div id=\"warnIDN\" style=\"background: #FCFCFC; border: 1px solid red; padding: 2px; max-width: 50em\">";
                echo $aInt->lang("general", "idnmbstringwarning");
                echo "</td></div>\n";
            }
            echo "    </td>\n</tr>\n\n<tr>\n    <td class=\"fieldlabel\">\n        ";
            echo AdminLang::trans("general.domainGraceAndRedemptionFees");
            echo "    </td>\n    <td class=\"fieldarea\">\n        <label class=\"radio-inline\">\n            <input type=\"radio\" name=\"disabledomaingrace\" value=\"0\"";
            echo !$CONFIG["DisableDomainGraceAndRedemptionFees"] ? " checked=\"checked\"" : "";
            echo ">\n            ";
            echo AdminLang::trans("global.enabled");
            echo "        </label>\n        <label class=\"radio-inline\">\n            <input type=\"radio\" name=\"disabledomaingrace\" value=\"1\"";
            echo $CONFIG["DisableDomainGraceAndRedemptionFees"] ? " checked=\"checked\"" : "";
            echo ">\n            ";
            echo AdminLang::trans("global.disabled");
            echo "        </label>\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
            echo AdminLang::trans("general.domainGraceBilling");
            echo "    </td>\n    <td class=\"fieldarea\">\n        <label class=\"radio-inline\">\n            <input type=\"radio\" name=\"domainExpiryFeeHandling\" value=\"existing\"";
            echo $CONFIG["DomainExpirationFeeHandling"] == "existing" ? " checked=\"checked\"" : "";
            echo ">\n            ";
            echo AdminLang::trans("general.domainGraceBillingAddToExistingInvoice");
            echo "        </label>\n        <br>\n        <label class=\"radio-inline\">\n            <input type=\"radio\" name=\"domainExpiryFeeHandling\" value=\"new\"";
            echo $CONFIG["DomainExpirationFeeHandling"] == "new" ? " checked=\"checked\"" : "";
            echo ">\n            ";
            echo AdminLang::trans("general.domainGraceBillingCreateNewInvoice");
            echo "        </label>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("domainregistrars", "defaultns1");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"ns1\" value=\"";
            echo $CONFIG["DefaultNameserver1"];
            echo "\" class=\"form-control input-inline input-300\"></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("domainregistrars", "defaultns2");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"ns2\" value=\"";
            echo $CONFIG["DefaultNameserver2"];
            echo "\" class=\"form-control input-inline input-300\"></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("domainregistrars", "defaultns3");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"ns3\" value=\"";
            echo $CONFIG["DefaultNameserver3"];
            echo "\" class=\"form-control input-inline input-300\"></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("domainregistrars", "defaultns4");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"ns4\" value=\"";
            echo $CONFIG["DefaultNameserver4"];
            echo "\" class=\"form-control input-inline input-300\"></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("domainregistrars", "defaultns5");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"ns5\" value=\"";
            echo isset($CONFIG["DefaultNameserver5"]) ? $CONFIG["DefaultNameserver5"] : "";
            echo "\" class=\"form-control input-inline input-300\"></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("domainregistrars", "useclientsdetails");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"domuseclientsdetails\"";
            if ($CONFIG["RegistrarAdminUseClientDetails"] == "on") {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("domainregistrars", "useclientsdetailsdesc");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("fields", "firstname");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"domfirstname\" value=\"";
            echo $CONFIG["RegistrarAdminFirstName"];
            echo "\" class=\"form-control input-inline input-300\"> ";
            echo $aInt->lang("domainregistrars", "defaultcontactdetails");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("fields", "lastname");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"domlastname\" value=\"";
            echo $CONFIG["RegistrarAdminLastName"];
            echo "\" class=\"form-control input-inline input-300\"></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("fields", "companyname");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"domcompanyname\" value=\"";
            echo $CONFIG["RegistrarAdminCompanyName"];
            echo "\" class=\"form-control input-inline input-300\"></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("fields", "email");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"domemail\" value=\"";
            echo $CONFIG["RegistrarAdminEmailAddress"];
            echo "\" class=\"form-control input-inline input-300\"></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("fields", "address1");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"domaddress1\" value=\"";
            echo $CONFIG["RegistrarAdminAddress1"];
            echo "\" class=\"form-control input-inline input-300\"></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("fields", "address2");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"domaddress2\" value=\"";
            echo $CONFIG["RegistrarAdminAddress2"];
            echo "\" class=\"form-control input-inline input-300\"></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("fields", "city");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"domcity\" value=\"";
            echo $CONFIG["RegistrarAdminCity"];
            echo "\" class=\"form-control input-inline input-300\"></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("fields", "state");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"domstate\" value=\"";
            echo $CONFIG["RegistrarAdminStateProvince"];
            echo "\" class=\"form-control input-inline input-300\"></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("fields", "postcode");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"dompostcode\" value=\"";
            echo $CONFIG["RegistrarAdminPostalCode"];
            echo "\" class=\"form-control input-inline input-300\"></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("fields", "country");
            echo "</td><td class=\"fieldarea\">";
            echo getCountriesDropDown($CONFIG["RegistrarAdminCountry"], "domcountry");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("fields", "phonenumber");
            echo "</td><td class=\"fieldarea\"><div style=\"max-width:300px;\"><input type=\"text\" name=\"domphone\" value=\"";
            echo $CONFIG["RegistrarAdminPhone"];
            echo "\"></div></td></tr>\n</table>\n\n";
            echo $aInt->nextAdminTab();
            echo "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\" style=\"min-width:200px;\">";
            echo $aInt->lang("general", "mailtype");
            echo "</td><td class=\"fieldarea\">\n    <select name=\"mailtype\" class=\"form-control select-inline\">\n        <option value=\"mail\"";
            if ($CONFIG["MailType"] == "mail") {
                echo " selected";
            }
            echo ">";
            echo $aInt->lang("general", "phpmail");
            echo "</option>\n        <option value=\"smtp\"";
            if ($CONFIG["MailType"] == "smtp") {
                echo " selected";
            }
            echo ">";
            echo $aInt->lang("general", "smtp");
            echo "</option>\n    </select></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "mailencoding");
            echo "</td><td class=\"fieldarea\">";
            echo $frm1->dropdown("mailencoding", $validMailEncodings, $whmcs->get_config("MailEncoding"));
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "smtpport");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"smtpport\" value=\"";
            echo $CONFIG["SMTPPort"];
            echo "\" class=\"form-control input-inline input-80\"> ";
            echo $aInt->lang("general", "smtpportinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "smtphost");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"smtphost\" value=\"";
            echo $CONFIG["SMTPHost"];
            echo "\" class=\"form-control input-inline input-400\"></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "smtpusername");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"smtpusername\" value=\"";
            echo $CONFIG["SMTPUsername"];
            echo "\" class=\"form-control input-inline input-400\"></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "smtppassword");
            echo "</td><td class=\"fieldarea\"><input type=\"password\" name=\"smtppassword\" value=\"";
            echo replacePasswordWithMasks(decrypt($CONFIG["SMTPPassword"]));
            echo "\" class=\"form-control input-inline input-300\"></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "smtpssltype");
            echo "</td><td class=\"fieldarea\">\n<label class=\"radio-inline\"><input type=\"radio\" name=\"smtpssl\" id=\"mail-smtp-nossl\" value=\"\" ";
            if ($CONFIG["SMTPSSL"] == "") {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("global", "none");
            echo "</label>\n<label class=\"radio-inline\"><input type=\"radio\" name=\"smtpssl\" id=\"mail-smtp-ssl\" value=\"ssl\" ";
            if ($CONFIG["SMTPSSL"] == "ssl") {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "smtpssl");
            echo "</label>\n<label class=\"radio-inline\"><input type=\"radio\" name=\"smtpssl\" id=\"mail-smtp-tls\" value=\"tls\" ";
            if ($CONFIG["SMTPSSL"] == "tls") {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "smtptls");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "mailsignature");
            echo "</td><td class=\"fieldarea\"><div class=\"row\"><div class=\"col-sm-8\"><textarea name=\"signature\" rows=\"4\" class=\"form-control\">";
            echo $CONFIG["Signature"];
            echo "</textarea></div></div></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "emailcsscode");
            echo "</td><td class=\"fieldarea\"><textarea name=\"emailcss\" rows=\"4\" class=\"form-control\">";
            echo $CONFIG["EmailCSS"];
            echo "</textarea></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
            echo $aInt->lang("general", "emailClientHeader");
            echo "    </td>\n    <td class=\"fieldarea\">\n        <textarea name=\"emailglobalheader\" rows=\"5\" class=\"form-control bottom-margin-5\">\n            ";
            echo WHMCS\Input\Sanitize::makeSafeForOutput($CONFIG["EmailGlobalHeader"]);
            echo "        </textarea>\n        ";
            echo $aInt->lang("general", "emailClientHeaderInfo");
            echo "    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
            echo $aInt->lang("general", "emailClientFooter");
            echo "    </td>\n    <td class=\"fieldarea\">\n        <textarea name=\"emailglobalfooter\" rows=\"5\" class=\"form-control bottom-margin-5\">\n            ";
            echo WHMCS\Input\Sanitize::makeSafeForOutput($CONFIG["EmailGlobalFooter"]);
            echo "        </textarea>\n        ";
            echo $aInt->lang("general", "emailClientFooterInfo");
            echo "    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "systemfromname");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"systememailsfromname\" value=\"";
            echo WHMCS\Input\Sanitize::makeSafeForOutput($CONFIG["SystemEmailsFromName"]);
            echo "\" class=\"form-control input-inline input-300\"></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "systemfromemail");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"systememailsfromemail\" value=\"";
            echo $CONFIG["SystemEmailsFromEmail"];
            echo "\" class=\"form-control input-inline input-400\"></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "bccmessages");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"bccmessages\" value=\"";
            echo $CONFIG["BCCMessages"];
            echo "\" class=\"form-control\">";
            echo $aInt->lang("general", "bccmessagesinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "presalesdest");
            echo "</td><td class=\"fieldarea\"><select name=\"contactformdept\" class=\"form-control select-inline\"><option value=\"\">";
            echo $aInt->lang("general", "presalesdept");
            echo "</option>";
            $dept_query = select_query("tblticketdepartments", "id, name", "");
            while ($dept_result = mysql_fetch_assoc($dept_query)) {
                $selected = "";
                if ($CONFIG["ContactFormDept"] == $dept_result["id"]) {
                    $selected = " selected";
                }
                echo "<option value=\"" . $dept_result["id"] . "\"" . $selected . ">" . $dept_result["name"] . "</option>";
            }
            echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "presalesemail");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"contactformto\" value=\"";
            echo $CONFIG["ContactFormTo"];
            echo "\" class=\"form-control input-inline input-400\"></td></tr>\n</table>\n\n";
            echo $aInt->nextAdminTab();
            echo "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\" style=\"min-width:200px;\">";
            echo $aInt->lang("general", "supportmodule");
            echo "</td><td class=\"fieldarea\"><select name=\"supportmodule\" class=\"form-control select-inline\"><option value=\"\">";
            echo $aInt->lang("general", "builtin");
            echo "</option>";
            $supportfolder = ROOTDIR . "/modules/support/";
            if (is_dir($supportfolder)) {
                $dh = opendir($supportfolder);
                while (false !== ($folder = readdir($dh))) {
                    if (is_dir($supportfolder . $folder) && $folder != "." && $folder != "..") {
                        echo "<option value=\"" . $folder . "\"";
                        if ($folder == $CONFIG["SupportModule"]) {
                            echo " selected";
                        }
                        echo ">" . ucfirst($folder) . "</option>";
                    }
                }
                closedir($dh);
                $ticketEmailLimit = (int) $whmcs->get_config("TicketEmailLimit");
                if (!$ticketEmailLimit) {
                    $ticketEmailLimit = 10;
                }
            }
            echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "ticketmask");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"ticketmask\" value=\"";
            echo $CONFIG["TicketMask"];
            echo "\" class=\"form-control input-inline input-300\"><br />";
            echo $aInt->lang("general", "ticketmaskinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "ticketreplyorder");
            echo "</td><td class=\"fieldarea\"><select name=\"supportticketorder\" class=\"form-control select-inline\"><option value=\"ASC\"";
            if ($CONFIG["SupportTicketOrder"] == "ASC") {
                echo " selected";
            }
            echo ">";
            echo $aInt->lang("general", "orderasc");
            echo "<option value=\"DESC\"";
            if ($CONFIG["SupportTicketOrder"] == "DESC") {
                echo " selected";
            }
            echo ">";
            echo $aInt->lang("general", "orderdesc");
            echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "ticketEmailLimit");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"ticketEmailLimit\" value=\"";
            echo $ticketEmailLimit;
            echo "\"  class=\"form-control input-inline input-80\"> ";
            echo $aInt->lang("general", "ticketEmailLimitInfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "showclientonlydepts");
            echo "</td><td class=\"fieldarea\"><input type=\"checkbox\" name=\"showclientonlydepts\"";
            if (!empty($CONFIG["ShowClientOnlyDepts"])) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "showclientonlydeptsinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "clientticketlogin");
            echo "</td><td class=\"fieldarea\"><input type=\"checkbox\" name=\"requireloginforclienttickets\"";
            if ($CONFIG["RequireLoginforClientTickets"] == "on") {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "clientticketlogininfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "kbsuggestions");
            echo "</td><td class=\"fieldarea\"><input type=\"checkbox\" name=\"supportticketkbsuggestions\"";
            if ($CONFIG["SupportTicketKBSuggestions"] == "on") {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "kbsuggestionsinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "attachmentthumbnails");
            echo "</td><td class=\"fieldarea\"><input type=\"checkbox\" name=\"attachmentthumbnails\"";
            if ($CONFIG["AttachmentThumbnails"]) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "attachmentthumbnailsinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "supportrating");
            echo "</td><td class=\"fieldarea\"><input type=\"checkbox\" name=\"ticketratingenabled\"";
            if ($CONFIG["TicketRatingEnabled"] == "on") {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "supportratinginfo");
            echo "</td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
            echo AdminLang::trans("general.addCarbonCopyRecipients");
            echo "    </td>\n    <td class=\"fieldarea\">\n        ";
            $allowCcRecipients = "";
            if ((bool) WHMCS\Config\Setting::getValue("TicketAddCarbonCopyRecipients")) {
                $allowCcRecipients = " checked=\"checked\"";
            }
            echo "        <label class=\"checkbox-inline\">\n            <input type=\"hidden\" name=\"ticket_add_cc\" value=\"0\">\n            <input type=\"checkbox\" name=\"ticket_add_cc\"";
            echo $allowCcRecipients;
            echo " value=\"1\"/>\n            ";
            echo AdminLang::trans("general.addCarbonCopyRecipientsDescription");
            echo "        </label>\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
            echo $aInt->lang("general", "preventEmailReopeningTicket");
            echo "    </td>\n    <td class=\"fieldarea\">\n        ";
            $preventEmailReopening = (bool) $whmcs->get_config("PreventEmailReopening") ? " checked" : "";
            echo "        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"preventEmailReopening\"";
            echo $preventEmailReopening;
            echo " />\n            ";
            echo $aInt->lang("general", "preventEmailReopeningTicketDescription");
            echo "        </label>\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
            echo $aInt->lang("general", "supportlastreplyupdate");
            echo "    </td>\n    <td class=\"fieldarea\">\n        <label class=\"radio-inline\">\n            <input type=\"radio\" name=\"lastreplyupdate\" value=\"always\"";
            if (!$whmcs->get_config("UpdateLastReplyTimestamp") || $whmcs->get_config("UpdateLastReplyTimestamp") == "always") {
                echo " checked";
            }
            echo " /> ";
            echo $aInt->lang("general", "supportlastreplyupdatealways");
            echo "        </label>\n        <label class=\"radio-inline\">\n            <input type=\"radio\" name=\"lastreplyupdate\" value=\"statusonly\"";
            if ($whmcs->get_config("UpdateLastReplyTimestamp") == "statusonly") {
                echo " checked";
            }
            echo " /> ";
            echo $aInt->lang("general", "supportlastreplyupdateonlystatuschange");
            echo "        </label>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "disablereplylogging");
            echo "</td><td class=\"fieldarea\"><input type=\"checkbox\" name=\"disablesupportticketreplyemailslogging\"";
            if ($CONFIG["DisableSupportTicketReplyEmailsLogging"]) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "disablereplylogginginfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "allowedattachments");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"allowedfiletypes\" value=\"";
            echo $CONFIG["TicketAllowedFileTypes"];
            echo "\" class=\"form-control input-inline input-400\"> ";
            echo $aInt->lang("general", "allowedattachmentsinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "networklogin");
            echo "</td><td class=\"fieldarea\"><input type=\"checkbox\" name=\"networkissuesrequirelogin\"";
            if ($CONFIG["NetworkIssuesRequireLogin"]) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "networklogininfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "incproductdls");
            echo "</td><td class=\"fieldarea\"><input type=\"checkbox\" name=\"dlinclproductdl\"";
            if (!empty($CONFIG["DownloadsIncludeProductLinked"])) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "incproductdlsinfo");
            echo "</td></tr>\n</table>\n\n";
            echo $aInt->nextAdminTab();
            echo "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\" style=\"min-width:200px;\">";
            echo $aInt->lang("general", "continvgeneration");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"continuousinvoicegeneration\"";
            if ($CONFIG["ContinuousInvoiceGeneration"] == "on") {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "continvgenerationinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "enablepdf");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"enablepdfinvoices\"";
            if ($CONFIG["EnablePDFInvoices"] == "on") {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "enablepdfinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "pdfpapersize");
            echo "</td><td class=\"fieldarea\"><select name=\"pdfpapersize\" class=\"form-control select-inline\">\n<option value=\"A4\"";
            if ($whmcs->get_config("PDFPaperSize") == "A4") {
                echo " selected";
            }
            echo ">A4</option>\n<option value=\"Letter\"";
            if ($whmcs->get_config("PDFPaperSize") == "Letter") {
                echo " selected";
            }
            echo ">Letter</option>\n</select> ";
            echo $aInt->lang("general", "pdfpapersizeinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "tcpdffont");
            echo "</td><td class=\"fieldarea\">\n";
            foreach ($tcpdfDefaultFonts as $font) {
                echo "<label class=\"radio-inline\"><input type=\"radio\" name=\"tcpdffont\" value=\"" . $font . "\"";
                if ($font == $activeFontName) {
                    echo " checked";
                    $defaultFont = true;
                    $activeFontName = "";
                }
                echo " /> " . ucfirst($font) . "</label> ";
            }
            echo "<label class=\"radio-inline\"><input type=\"radio\" name=\"tcpdffont\" value=\"custom\"";
            if (!$defaultFont) {
                echo " checked";
            }
            echo " /> Custom</label> <input type=\"text\" name=\"tcpdffontcustom\" value=\"" . $activeFontName . "\" class=\"form-control input-inline input-200\">";
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "storeClientDataSnapshot");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"invoiceclientdatasnapshot\"";
            if (!empty($CONFIG["StoreClientDataSnapshotOnInvoiceCreation"])) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "storeClientDataSnapshotInfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "enablemasspay");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"enablemasspay\"";
            if ($CONFIG["EnableMassPay"] == "on") {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "enablemasspayinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "clientsgwchoose");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"allowcustomerchangeinvoicegateway\"";
            if ($CONFIG["AllowCustomerChangeInvoiceGateway"] == "on") {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "clientsgwchooseinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "groupsimilarlineitems");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"groupsimilarlineitems\"";
            if ($CONFIG["GroupSimilarLineItems"]) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "groupsimilarlineitemsinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "cancelinvoiceoncancel");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"cancelinvoiceoncancel\"";
            if ($CONFIG["CancelInvoiceOnCancellation"]) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "cancelinvoiceoncancelinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "autoCancelSubscriptions");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"autoCancelSubscriptions\"";
            if (!empty($CONFIG["AutoCancelSubscriptions"])) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "autoCancelSubscriptionsInfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "enableProformaInvoicing");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" id=\"enableProformaInvoicing\" name=\"enableProformaInvoicing\"";
            if (WHMCS\Config\Setting::getValue("EnableProformaInvoicing")) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "enableProformaInvoicingInfo");
            echo "</label></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
            echo $aInt->lang("general", "sequentialpaidnumbering");
            echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"hidden\" name=\"sequentialinvoicenumbering\" value=\"0\" />\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" id=\"sequentialpaidnumbering\" name=\"sequentialinvoicenumbering\"\n                ";
            echo WHMCS\Config\Setting::getValue("SequentialInvoiceNumbering") ? " checked" : "";
            echo "                ";
            echo WHMCS\Config\Setting::getValue("EnableProformaInvoicing") ? " disabled" : "";
            echo "            value=\"1\" />\n            ";
            echo $aInt->lang("general", "sequentialpaidnumberinginfo");
            echo "        </label>\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
            echo $aInt->lang("general", "sequentialpaidformat");
            echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"sequentialinvoicenumberformat\" value=\"";
            echo $CONFIG["SequentialInvoiceNumberFormat"];
            echo "\" class=\"form-control input-inline input-200\">\n        ";
            echo $aInt->lang("general", "sequentialpaidformatinfo");
            echo " {YEAR} {MONTH} {DAY} {NUMBER}\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "nextpaidnumber");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"sequentialinvoicenumbervalue\" placeholder=\"";
            echo $CONFIG["SequentialInvoiceNumberValue"];
            echo "\" class=\"form-control input-inline input-100\"> ";
            echo $aInt->lang("general", "nextpaidnumberinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "latefeetype");
            echo "</td><td class=\"fieldarea\"><label class=\"radio-inline\"><input type=\"radio\" name=\"latefeetype\" value=\"Percentage\"";
            if ($CONFIG["LateFeeType"] == "Percentage") {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("affiliates", "percentage");
            echo "</label> <label class=\"radio-inline\"><input type=\"radio\" name=\"latefeetype\" value=\"Fixed Amount\"";
            if ($CONFIG["LateFeeType"] == "Fixed Amount") {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("affiliates", "fixedamount");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "latefeeamount");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"invoicelatefeeamount\" value=\"";
            echo $CONFIG["InvoiceLateFeeAmount"];
            echo "\" class=\"form-control input-inline input-100\"> ";
            echo $aInt->lang("general", "latefeeamountinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "latefeemin");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"latefeeminimum\" value=\"";
            echo $CONFIG["LateFeeMinimum"];
            echo "\" class=\"form-control input-inline input-100\"> ";
            echo $aInt->lang("general", "latefeemininfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "issuestart");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"showccissuestart\"";
            if ($CONFIG["ShowCCIssueStart"] == "on") {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "issuestartinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "invoiceinc");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"invoiceincrement\"";
            echo " value=\"" . $CONFIG["InvoiceIncrement"] . "\"";
            echo " class=\"form-control input-inline input-100\"> ";
            echo $aInt->lang("general", "invoiceincinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "invoicestartno");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"invoicestartnumber\" value=\"\" class=\"form-control input-inline input-100\"> ";
            echo $aInt->lang("general", "invoicestartnoinfo");
            $maxinvnum = get_query_val("tblinvoiceitems", "invoiceid", "", "invoiceid", "DESC", "0,1");
            echo $maxinvnum ? $maxinvnum : "0";
            echo " (" . $aInt->lang("general", "blanknochange") . ")";
            echo "</td></tr>\n</table>\n\n";
            echo $aInt->nextAdminTab();
            echo "    ";
            if (!isset($CONFIG["CurrencySymbol"])) {
                $CONFIG["CurrencySymbol"] = "";
            }
            echo "    <table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n        <tr>\n            <td class=\"fieldlabel\" style=\"min-width:200px;\">";
            echo $aInt->lang("general", "enabledisable");
            echo "</td>\n            <td class=\"fieldarea\">\n                <label class=\"checkbox-inline\">\n                    <input type=\"checkbox\" name=\"addfundsenabled\"";
            if ($CONFIG["AddFundsEnabled"]) {
                echo " CHECKED";
            }
            echo ">\n                    ";
            echo $aInt->lang("general", "enablecredit");
            echo "                </label>\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">";
            echo $aInt->lang("general", "mincreditdeposit");
            echo "</td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"addfundsminimum\" value=\"";
            echo $CONFIG["AddFundsMinimum"];
            echo "\" class=\"form-control input-inline input-100\">\n                ";
            echo $aInt->lang("general", "mincreditdepositinfo");
            echo "            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">";
            echo $aInt->lang("general", "maxcreditdeposit");
            echo "</td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"addfundsmaximum\" value=\"";
            echo $CONFIG["AddFundsMaximum"];
            echo "\" class=\"form-control input-inline input-100\">\n                ";
            echo $aInt->lang("general", "maxcreditdepositinfo");
            echo "            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">";
            echo $aInt->lang("general", "maxbalance");
            echo "</td>\n            <td class=\"fieldarea\">\n                ";
            echo $CONFIG["CurrencySymbol"];
            echo "                <input type=\"text\" name=\"addfundsmaximumbalance\" value=\"";
            echo $CONFIG["AddFundsMaximumBalance"];
            echo "\" class=\"form-control input-inline input-100\">\n                ";
            echo $aInt->lang("general", "maxbalanceinfo");
            echo "            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">";
            echo $aInt->lang("general", "addfundsrequireorder");
            echo "</td>\n            <td class=\"fieldarea\">\n                <label class=\"checkbox-inline\">\n                    <input type=\"checkbox\" name=\"addfundsrequireorder\"";
            if ($CONFIG["AddFundsRequireOrder"]) {
                echo " checked";
            }
            echo ">\n                    ";
            echo $aInt->lang("general", "addfundsrequireorderinfo");
            echo "                </label>\n            </td>\n        </tr>\n\n        <tr>\n            <td class=\"fieldlabel\">";
            echo AdminLang::trans("general.creditApply");
            echo "</td>\n            <td class=\"fieldarea\">\n                <label class=\"checkbox-inline\">\n                    <input type=\"checkbox\" name=\"noautoapplycredit\" value=\"on\"";
            if (!$CONFIG["NoAutoApplyCredit"]) {
                echo " checked";
            }
            echo ">\n                    ";
            echo $aInt->lang("general", "creditApplyAutomatic");
            echo "                </label>\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">";
            echo $aInt->lang("general", "creditdowngrade");
            echo "</td>\n            <td class=\"fieldarea\">\n                <label class=\"checkbox-inline\">\n                    <input type=\"checkbox\" name=\"creditondowngrade\"";
            if ($CONFIG["CreditOnDowngrade"] == "on") {
                echo " CHECKED";
            }
            echo ">\n                    ";
            echo $aInt->lang("general", "creditdowngradeinfo");
            echo "                </label>\n            </td>\n        </tr>\n    </table>\n\n";
            echo $aInt->nextAdminTab();
            echo "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\" style=\"min-width:200px;\">";
            echo $aInt->lang("general", "enabledisable");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"affiliateenabled\"";
            if ($CONFIG["AffiliateEnabled"] == "on") {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "enableaff");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "affpercentage");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"affiliateearningpercent\" value=\"";
            echo $CONFIG["AffiliateEarningPercent"];
            echo "\" class=\"form-control input-inline input-100\"> ";
            echo $aInt->lang("general", "affpercentageinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "affbonus");
            echo "</td><td class=\"fieldarea\">";
            echo $CONFIG["CurrencySymbol"];
            echo "<input type=\"text\" name=\"affiliatebonusdeposit\" value=\"";
            echo $CONFIG["AffiliateBonusDeposit"];
            echo "\" class=\"form-control input-inline input-100\"> ";
            echo $aInt->lang("general", "affbonusinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "affpayamount");
            echo "</td><td class=\"fieldarea\">";
            echo $CONFIG["CurrencySymbol"];
            echo "<input type=\"text\" name=\"affiliatepayout\" value=\"";
            echo $CONFIG["AffiliatePayout"];
            echo "\" class=\"form-control input-inline input-100\"> ";
            echo $aInt->lang("general", "affpayamountinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "affcommdelay");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"affiliatesdelaycommission\" value=\"";
            echo $CONFIG["AffiliatesDelayCommission"];
            echo "\" class=\"form-control input-inline input-100\"> ";
            echo $aInt->lang("general", "affcommdelayinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "affdepartment");
            echo "</td><td class=\"fieldarea\"><select name=\"affiliatedepartment\" class=\"form-control select-inline\">";
            $dept_query = select_query("tblticketdepartments", "id,name", "", "order", "ASC");
            while ($dept_result = mysql_fetch_assoc($dept_query)) {
                echo "<option value=\"" . $dept_result["id"] . "\"";
                if ($CONFIG["AffiliateDepartment"] == $dept_result["id"]) {
                    echo " selected";
                }
                echo ">" . $dept_result["name"] . "</option>";
            }
            echo "</select> ";
            echo $aInt->lang("general", "affdepartmentinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "afflinks");
            echo "</td><td class=\"fieldarea\"><textarea name=\"affiliatelinks\" rows=\"10\" class=\"form-control bottom-margin-5\">";
            echo $CONFIG["AffiliateLinks"];
            echo "</textarea>";
            echo $aInt->lang("general", "afflinksinfo");
            echo "<br />";
            echo $aInt->lang("general", "afflinksinfo2");
            echo "</td></tr>\n</table>\n\n";
            echo $aInt->nextAdminTab();
            echo "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr>\n    <td class=\"fieldlabel\" style=\"min-width:200px;\">\n        ";
            echo $aInt->lang("general", "emailVerification");
            echo "    </td>\n    <td class=\"fieldarea\">\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"enable_email_verification\" value=\"1\"";
            echo WHMCS\Config\Setting::getValue("EnableEmailVerification") ? " checked" : "";
            echo ">\n            ";
            echo AdminLang::trans("general.emailVerificationDescription");
            echo "        </label>\n    </td>\n</tr>\n    ";
            if (!isset($CONFIG["CaptchaType"])) {
                $CONFIG["CaptchaType"] = "";
            }
            if (!isset($CONFIG["ReCAPTCHAPublicKey"])) {
                $CONFIG["ReCAPTCHAPublicKey"] = "";
            }
            if (!isset($CONFIG["ReCAPTCHAPrivateKey"])) {
                $CONFIG["ReCAPTCHAPrivateKey"] = "";
            }
            $captchaUtility = new WHMCS\Utility\Captcha();
            $forms = $captchaUtility->getForms();
            $captchaFormInputs = array();
            foreach ($forms as $formName => $formValue) {
                $checked = "";
                if ($formValue) {
                    $checked = "checked=\"checked\"";
                }
                $formLabel = AdminLang::trans("general.recaptchaform-" . $formName);
                $captchaFormInputs[] = "<label class=\"checkbox-inline\">\n    <input type=\"checkbox\" name=\"captchaform[" . $formName . "]\" " . $checked . ">\n    " . $formLabel . "\n</label>";
            }
            switch ($CONFIG["CaptchaSetting"]) {
                case "on":
                    $onChecked = " checked=\"checked\"";
                    $offLoggedIn = $offChecked = "";
                    break;
                case "offloggedin":
                    $offLoggedIn = " checked=\"checked\"";
                    $onChecked = $offChecked = "";
                    break;
                default:
                    $offChecked = " checked=\"checked\"";
                    $offLoggedIn = $onChecked = "";
                    break;
            }
            $captchaType = $CONFIG["CaptchaType"];
            $captchaTypes = array("default" => array("value" => "", "checked" => $captchaType == "", "image" => "../includes/verifyimage.php"), "recaptcha" => array("value" => "recaptcha", "checked" => $captchaType == "recaptcha", "image" => "../assets/img/recaptcha.gif"), "invisible" => array("value" => "invisible", "checked" => $captchaType == "invisible", "image" => "../assets/img/recaptcha-invisible.png"));
            $showHideSettings = "";
            if (!$captchaType) {
                $showHideSettings = " style=\"display:none;\"";
            }
            echo "<tr>\n    <td class=\"fieldlabel\">";
            echo AdminLang::trans("general.captcha");
            echo "</td>\n    <td class=\"fieldarea\">\n        <label class=\"radio-inline\">\n            <input type=\"radio\" name=\"captchasetting\" value=\"on\"";
            echo $onChecked;
            echo ">\n            ";
            echo AdminLang::trans("general.captchaalwayson");
            echo "        </label><br />\n        <label class=\"radio-inline\">\n            <input type=\"radio\" name=\"captchasetting\" value=\"offloggedin\"";
            echo $offLoggedIn;
            echo ">\n            ";
            echo AdminLang::trans("general.captchaoffloggedin");
            echo "        </label><br />\n        <label class=\"radio-inline\">\n            <input type=\"radio\"\n                   name=\"captchasetting\"\n                   id=\"captcha-setting-alwaysoff\"\n                   value=\"\"";
            echo $offChecked;
            echo "            >\n            ";
            echo AdminLang::trans("general.captchaoff");
            echo "        </label>\n    </td>\n</tr>\n    <tr>\n        <td class=\"fieldlabel\">";
            echo AdminLang::trans("general.captchatype");
            echo "</td>\n        <td class=\"fieldarea\">\n            <div class=\"row\">\n                ";
            foreach ($captchaTypes as $type => $captchaData) {
                $label = AdminLang::trans("general.captcha" . $type);
                $checked = "";
                if ($captchaData["checked"]) {
                    $checked = " checked=\"checked\"";
                }
                echo "<div class=\"col-lg-4 col-md-12\">\n    <label class=\"radio-inline\">\n        <input type=\"radio\"\n               name=\"captchatype\"\n               class=\"captcha-type\"\n               value=\"" . $captchaData["value"] . "\"\n               " . $checked . "\n        />\n        " . $label . "<br />\n        <img src=\"" . $captchaData["image"] . "\" style=\"max-width: 260px;\">\n    </label>              \n</div>";
            }
            echo "            </div>\n        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">";
            echo AdminLang::trans("general.recaptchaforms");
            echo "</td>\n        <td class=\"fieldarea\">\n            ";
            echo implode("<br/>", $captchaFormInputs);
            echo "        </td>\n    </tr>\n    <tr class=\"recaptchasetts\"";
            echo $showHideSettings;
            echo ">\n        <td class=\"fieldlabel\">\n            ";
            echo AdminLang::trans("general.recaptchapublickey");
            echo "        </td>\n        <td class=\"fieldarea\">\n            <input type=\"text\"\n                   name=\"recaptchapublickey\"\n                   class=\"form-control input-inline input-400\"\n                   value=\"";
            echo $CONFIG["ReCAPTCHAPublicKey"];
            echo "\"\n            >\n            ";
            echo AdminLang::trans("general.recaptchakeyinfo");
            echo "        </td>\n    </tr>\n    <tr class=\"recaptchasetts\"";
            echo $showHideSettings;
            echo ">\n        <td class=\"fieldlabel\">\n            ";
            echo AdminLang::trans("general.recaptchaprivatekey");
            echo "        </td>\n        <td class=\"fieldarea\">\n            <input type=\"text\"\n                   name=\"recaptchaprivatekey\"\n                   class=\"form-control input-inline input-400\"\n                   value=\"";
            echo $CONFIG["ReCAPTCHAPrivateKey"];
            echo "\"\n            >\n        </td>\n    </tr>\n\n<tr>\n    <td class=\"fieldlabel\">";
            echo AdminLang::trans("general.autoGeneratedPasswordFormat");
            echo "</td>\n    <td class=\"fieldarea\">\n        <label class=\"radio-inline\">\n            <input type=\"radio\" name=\"autogeneratedpwformat\" value=\"\"";
            if (WHMCS\Config\Setting::getValue("AutoGeneratedPasswordFormat") != "legacy") {
                echo " checked";
            }
            echo ">\n            ";
            echo AdminLang::trans("general.autoGeneratedPasswordFormatAllChars");
            echo "        </label>\n        <br>\n        <label class=\"radio-inline\">\n            <input type=\"radio\" name=\"autogeneratedpwformat\" value=\"legacy\"";
            if (WHMCS\Config\Setting::getValue("AutoGeneratedPasswordFormat") == "legacy") {
                echo " checked";
            }
            echo ">\n            ";
            echo AdminLang::trans("general.autoGeneratedPasswordFormatLegacy");
            echo "        </label>\n    </td>\n</tr>\n\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "reqpassstrength");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"requiredpwstrength\" value=\"";
            echo $CONFIG["RequiredPWStrength"];
            echo "\" class=\"form-control input-inline input-80\"> ";
            echo $aInt->lang("general", "reqpassstrengthinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "failedbantime");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"invalidloginsbanlength\" value=\"";
            echo $CONFIG["InvalidLoginBanLength"];
            echo "\" class=\"form-control input-inline input-80\"> ";
            echo $aInt->lang("general", "banminutes");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "whitelistedips");
            echo "</td><td class=\"fieldarea\"><select name=\"whitelistedips[]\" id=\"whitelistedips\" size=\"3\" multiple class=\"form-control select-inline\">";
            $whitelistedips = isset($CONFIG["WhitelistedIPs"]) ? safe_unserialize($CONFIG["WhitelistedIPs"]) : array();
            $whitelistedips = is_array($whitelistedips) ? $whitelistedips : array();
            foreach ($whitelistedips as $whitelist) {
                echo "<option value=" . $whitelist["ip"] . ">" . $whitelist["ip"] . " - " . $whitelist["note"] . "</option>";
            }
            echo "</select> ";
            echo $aInt->lang("general", "whitelistedipsinfo");
            echo "<br /><a href=\"#\" data-toggle=\"modal\" data-target=\"#modalAddWhiteListIp\"><img src=\"images/icons/add.png\" align=\"absmiddle\" border=\"0\" /> ";
            echo $aInt->lang("general", "addip");
            echo "</a> <a href=\"#\" id=\"removewhitelistedip\"><img src=\"images/icons/delete.png\" align=\"absmiddle\" border=\"0\" /> ";
            echo $aInt->lang("general", "removeselected");
            echo "</a></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "sendFailedLoginWhitelist");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"sendFailedLoginWhitelist\"";
            if (!empty($CONFIG["sendFailedLoginWhitelist"])) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "sendFailedLoginWhitelistInfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "disableadminpwreset");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"disableadminpwreset\"";
            if ($CONFIG["DisableAdminPWReset"]) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "disableadminpwresetinfo");
            echo "</label></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
            echo AdminLang::trans("general.deleteLocalCards");
            echo "    </td>\n    <td class=\"fieldarea\">\n        <button id=\"btnDeleteLocalCards\" type=\"button\" class=\"btn btn-sm btn-danger\">\n            ";
            echo AdminLang::trans("global.delete");
            echo "        </button>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "allowccdelete");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"ccallowcustomerdelete\"";
            if ($CONFIG["CCAllowCustomerDelete"]) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "allowccdeleteinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "disablesessionip");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"disablesessionipcheck\"";
            if ($CONFIG["DisableSessionIPCheck"]) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "disablesessionipinfo");
            echo "</label></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
            echo $aInt->lang("general", "allowsmartyphptags");
            echo "    </td>\n    <td class=\"fieldarea\">\n        ";
            echo $aInt->lang("general", "allowsmartyphptagsinfo");
            echo "        <br />\n        <label class=\"radio-inline\">\n            <input type=\"radio\" name=\"allowsmartyphptags\" value=\"1\"";
            if (!empty($CONFIG["AllowSmartyPhpTags"])) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("global", "enabled");
            echo "        </label>\n        <br />\n        <label class=\"radio-inline\">\n            <input type=\"radio\" name=\"allowsmartyphptags\" value=\"0\"";
            if (empty($CONFIG["AllowSmartyPhpTags"])) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("global", "disabled");
            echo " (";
            echo $aInt->lang("global", "recommended");
            echo ")\n        </label>\n    </td>\n</tr>\n\n    <tr>\n        <td class=\"fieldlabel\">\n            ";
            echo $aInt->lang("general", "proxyheader");
            echo "        </td>\n        <td class=\"fieldarea\">\n            <input type=\"text\" name=\"proxyheader\" value=\"";
            $proxyHeader = (string) $whmcs->get_config("proxyHeader");
            echo $proxyHeader;
            echo "\" class=\"form-control input-inline input-200\">\n            &nbsp;";
            echo $aInt->lang("general", "proxyheaderinfo");
            echo "        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">";
            echo $aInt->lang("general", "trustedproxy");
            echo "</td>\n        <td class=\"fieldarea\">\n            <select name=\"trustedproxyips[]\" id=\"trustedproxyips\" size=\"3\" multiple class=\"form-control select-inline\">\n                ";
            $whitelistedips = json_decode($whmcs->get_config("trustedProxyIps"), true);
            if (!is_array($whitelistedips)) {
                $whitelistedips = array();
            }
            foreach ($whitelistedips as $whitelist) {
                echo sprintf("<option value=\"%s\">%s - %s</option>", $whitelist["ip"], $whitelist["ip"], $whitelist["note"]);
            }
            echo "            </select>&nbsp;";
            echo $aInt->lang("general", "trustedproxyinfo");
            echo "<br />\n            <a href=\"#\" data-toggle=\"modal\" data-target=\"#modalAddTrustedProxyIp\">\n                <img src=\"images/icons/add.png\" align=\"absmiddle\" border=\"0\" />\n                ";
            echo $aInt->lang("general", "addip");
            echo "            </a>\n            &nbsp;\n            <a href=\"#\" id=\"removetrustedproxyip\">\n                <img src=\"images/icons/delete.png\" align=\"absmiddle\" border=\"0\" />\n                ";
            echo $aInt->lang("general", "removeselected");
            echo "            </a>\n        </td>\n    </tr>\n\n    <tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "apirestriction");
            echo "</td><td class=\"fieldarea\"><select name=\"apiallowedips[]\" id=\"apiallowedips\" size=\"3\" multiple class=\"form-control select-inline\">";
            $whitelistedips = safe_unserialize($CONFIG["APIAllowedIPs"]);
            foreach ($whitelistedips as $whitelist) {
                echo "<option value=" . $whitelist["ip"] . ">" . $whitelist["ip"] . " - " . $whitelist["note"] . "</option>";
            }
            echo "</select> ";
            echo $aInt->lang("general", "apirestrictioninfo");
            echo "<br /><a href=\"#\" data-toggle=\"modal\" data-target=\"#modalAddApiIp\"><img src=\"images/icons/add.png\" align=\"absmiddle\" border=\"0\" /> ";
            echo $aInt->lang("general", "addip");
            echo "</a> <a href=\"#\" id=\"removeapiip\"><img src=\"images/icons/delete.png\" align=\"absmiddle\" border=\"0\" /> ";
            echo $aInt->lang("general", "removeselected");
            echo "</a></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "logapiauthentication");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"logapiauthentication\" value=\"1\"";
            if ($CONFIG["LogAPIAuthentication"]) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "logapiauthenticationinfo");
            echo "</label></td></tr>\n";
            $token_manager =& getTokenManager();
            echo $token_manager->generateAdminConfigurationHTMLRows($aInt);
            echo "</table>\n\n";
            echo $aInt->nextAdminTab();
            echo "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\" style=\"min-width:200px;\">";
            echo $aInt->lang("general", "twitterint");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"twitterusername\" value=\"";
            echo $CONFIG["TwitterUsername"];
            echo "\" class=\"form-control input-inline input-200\"> ";
            echo $aInt->lang("general", "twitterintinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "twitterannouncementstweet");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"announcementstweet\"";
            if ($CONFIG["AnnouncementsTweet"]) {
                echo " checked";
            }
            echo " /> ";
            echo $aInt->lang("general", "twitterannouncementstweetinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "facebookannouncementsrecommend");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"announcementsfbrecommend\"";
            if ($CONFIG["AnnouncementsFBRecommend"]) {
                echo " checked";
            }
            echo " /> ";
            echo $aInt->lang("general", "facebookannouncementsrecommendinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "facebookannouncementscomments");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"announcementsfbcomments\"";
            if ($CONFIG["AnnouncementsFBComments"]) {
                echo " checked";
            }
            echo " /> ";
            echo $aInt->lang("general", "facebookannouncementscommentsinfo");
            echo "</label></td></tr>\n</table>\n\n";
            echo $aInt->nextAdminTab();
            echo "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr>\n    <td class=\"fieldlabel\">\n        ";
            echo AdminLang::trans("general.marketingEmails");
            echo "    </td>\n    <td class=\"fieldarea\">\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"allowclientsemailoptout\" value=\"1\"";
            if (WHMCS\Config\Setting::getValue("AllowClientsEmailOptOut")) {
                echo " checked";
            }
            echo ">\n            ";
            echo AdminLang::trans("general.marketingEmailsDescription");
            echo "        </label>\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
            echo AdminLang::trans("general.marketingEmailsRequireOptIn");
            echo "</td>\n    <td class=\"fieldarea\">\n        <label class=\"radio-inline\">\n            <input type=\"radio\" name=\"marketingreqoptin\" value=\"1\"";
            if (WHMCS\Config\Setting::getValue("EmailMarketingRequireOptIn")) {
                echo " checked";
            }
            echo ">\n            ";
            echo AdminLang::trans("general.marketingEmailsRequireOptInEnabled");
            echo "        </label>\n        <br>\n        <label class=\"radio-inline\">\n            <input type=\"radio\" name=\"marketingreqoptin\" value=\"0\"";
            if (!WHMCS\Config\Setting::getValue("EmailMarketingRequireOptIn")) {
                echo " checked";
            }
            echo ">\n            ";
            echo AdminLang::trans("general.marketingEmailsRequireOptInDisabled");
            echo "        </label>\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
            echo AdminLang::trans("general.marketingEmailsOptInMessaging");
            echo "</td>\n    <td class=\"fieldarea\">\n        <textarea rows=\"2\" name=\"marketingoptinmessage\" class=\"form-control\">";
            echo WHMCS\Config\Setting::getValue("EmailMarketingOptInMessage");
            echo "</textarea>\n    </td>\n</tr>\n\n<tr><td class=\"fieldlabel\" style=\"min-width:200px;\">";
            echo $aInt->lang("general", "adminclientformat");
            echo "</td><td class=\"fieldarea\"><label class=\"radio-inline\"><input type=\"radio\" name=\"clientdisplayformat\" value=\"1\"";
            if ($CONFIG["ClientDisplayFormat"] == "1") {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "showfirstlast");
            echo "</label><br /><label class=\"radio-inline\"><input type=\"radio\" name=\"clientdisplayformat\" value=\"2\"";
            if ($CONFIG["ClientDisplayFormat"] == "2") {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "showcompanyfirstlast");
            echo "</label><br /><label class=\"radio-inline\"><input type=\"radio\" name=\"clientdisplayformat\" value=\"3\"";
            if ($CONFIG["ClientDisplayFormat"] == "3") {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "showfullcompany");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "defaulttoclientarea");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"defaulttoclientarea\"";
            if ($CONFIG["DefaultToClientArea"]) {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "defaulttoclientareainfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "allowclientreg");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"allowclientregister\"";
            if ($CONFIG["AllowClientRegister"] == "on") {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "allowclientreginfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "profileoptionalfields");
            echo "</td><td class=\"fieldarea\">";
            echo $aInt->lang("general", "profileoptionalfieldsinfo");
            echo ":<br />\n<table width=\"100%\"><tr>\n";
            $ClientsProfileOptionalFields = explode(",", $CONFIG["ClientsProfileOptionalFields"]);
            $updatefieldsarray = array("firstname" => $aInt->lang("fields", "firstname"), "lastname" => $aInt->lang("fields", "lastname"), "address1" => $aInt->lang("fields", "address1"), "city" => $aInt->lang("fields", "city"), "state" => $aInt->lang("fields", "state"), "postcode" => $aInt->lang("fields", "postcode"), "phonenumber" => $aInt->lang("fields", "phonenumber"));
            $fieldcount = 0;
            foreach ($updatefieldsarray as $field => $displayname) {
                echo "<td width=\"25%\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"clientsprofoptional[]\" value=\"" . $field . "\"";
                if (in_array($field, $ClientsProfileOptionalFields)) {
                    echo " checked";
                }
                echo " /> " . $displayname . "</label></td>";
                $fieldcount++;
                if ($fieldcount == 4) {
                    echo "</tr><tr>";
                    $fieldcount = 0;
                }
            }
            echo "</tr></table></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "lockedfields");
            echo "</td><td class=\"fieldarea\">";
            echo $aInt->lang("general", "lockedfieldsinfo");
            echo ":<br />\n<table width=\"100%\"><tr>\n";
            $ClientsProfileUneditableFields = explode(",", $CONFIG["ClientsProfileUneditableFields"]);
            $updatefieldsarray = array("firstname" => AdminLang::trans("fields.firstname"), "lastname" => AdminLang::trans("fields.lastname"), "companyname" => AdminLang::trans("fields.companyname"), "email" => AdminLang::trans("fields.email"), "address1" => AdminLang::trans("fields.address1"), "address2" => AdminLang::trans("fields.address2"), "city" => AdminLang::trans("fields.city"), "state" => AdminLang::trans("fields.state"), "postcode" => AdminLang::trans("fields.postcode"), "country" => AdminLang::trans("fields.country"), "phonenumber" => AdminLang::trans("fields.phonenumber"), "tax_id" => AdminLang::trans(WHMCS\Billing\Tax\Vat::getLabel("fields")));
            $fieldcount = 0;
            foreach ($updatefieldsarray as $field => $displayname) {
                echo "<td width=\"25%\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"clientsprofuneditable[]\" value=\"" . $field . "\"";
                if (in_array($field, $ClientsProfileUneditableFields)) {
                    echo " checked";
                }
                echo " /> " . $displayname . "</label></td>";
                $fieldcount++;
                if ($fieldcount == 4) {
                    echo "</tr><tr>";
                    $fieldcount = 0;
                }
            }
            echo "</tr></table></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "clientdetailsnotify");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"sendemailnotificationonuserdetailschange\"";
            if ($CONFIG["SendEmailNotificationonUserDetailsChange"] == "on") {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "clientdetailsnotifyinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "showcancellink");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"showcancel\"";
            if ($CONFIG["ShowCancellationButton"] == "on") {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "showcancellinkinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "monthlyaffreport");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"affreport\"";
            if ($CONFIG["SendAffiliateReportMonthly"] == "on") {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "monthlyaffreportinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "bannedsubdomainprefixes");
            echo "</td><td class=\"fieldarea\"><textarea name=\"bannedsubdomainprefixes\" cols=\"100\" rows=\"2\" class=\"form-control\">";
            echo $CONFIG["BannedSubdomainPrefixes"];
            echo "</textarea></td></tr>\n<tr>\n    <td class=\"fieldlabel\">";
            echo AdminLang::trans("general.enablesafeinclude");
            echo "</td>\n    <td class=\"fieldarea\">\n        <label class=\"radio-inline\">\n            <input type=\"radio\" name=\"enablesafeinclude\" value=\"1\"";
            if (WHMCS\Config\Setting::getValue("EnableSafeInclude")) {
                echo " checked";
            }
            echo ">\n            ";
            echo AdminLang::trans("general.enablesafeincludeyes");
            echo "        </label>\n        <br>\n        <label class=\"radio-inline\">\n            <input type=\"radio\" name=\"enablesafeinclude\" value=\"0\"";
            if (!WHMCS\Config\Setting::getValue("EnableSafeInclude")) {
                echo " checked";
            }
            echo ">\n            ";
            echo AdminLang::trans("general.enablesafeincludeno");
            echo "        </label>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "displayerrors");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"displayerrors\"";
            if ($CONFIG["DisplayErrors"]) {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "displayerrorsinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "logerrors");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"logerrors\"";
            if ($CONFIG["LogErrors"]) {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "logerrorsinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "sqldebugmode");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"sqlerrorreporting\"";
            if ($CONFIG["SQLErrorReporting"]) {
                echo " CHECKED";
            }
            echo "> ";
            echo $aInt->lang("general", "sqldebugmodeinfo");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("general", "hooksdebugmode");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"hooksdebugmode\"";
            if ($whmcs->get_config("HooksDebugMode")) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("general", "hooksdebugmodeinfo");
            echo "</label></td></tr>\n</table>\n\n";
            echo $aInt->endAdminTabs();
            echo "\n<div class=\"btn-container\">\n    <input id=\"saveChanges\" type=\"submit\" value=\"";
            echo $aInt->lang("global", "savechanges");
            echo "\" class=\"btn btn-primary\">\n    <input type=\"reset\" value=\"";
            echo $aInt->lang("global", "cancelchanges");
            echo "\" class=\"btn btn-default\" />\n</div>\n\n<input type=\"hidden\" name=\"tab\" id=\"tab\" value=\"";
            echo (int) $_REQUEST["tab"];
            echo "\" />\n\n</form>\n\n";
            $content = ob_get_contents();
            ob_end_clean();
            $aInt->content = $content;
            $aInt->jquerycode = $jquerycode;
            $aInt->jscode = $jsCode;
            $aInt->display();
        }
    }
}
function cleanSystemURL($url)
{
    $prefix = App::in_ssl() ? "https" : "http";
    if ($url == "" || !preg_match("/\\b(?:(?:https?|ftp):\\/\\/|www\\.)[-a-z0-9+&@#\\/%?=~_|!:,.;]*[-a-z0-9+&@#\\/%=~_|]/i", $url)) {
        $url = $prefix . "://" . $_SERVER["SERVER_NAME"] . preg_replace("#/[^/]*\\.php\$#simU", "/", $_SERVER["PHP_SELF"]);
    } else {
        $url = str_replace("\\", "", trim($url));
        if (!preg_match("~^(?:ht)tps?://~i", $url)) {
            $url = $prefix . "://" . $url;
        }
        $url = preg_replace("~^https?://[^/]+\$~", "\$0/", $url);
    }
    if (substr($url, -1) != "/") {
        $url .= "/";
    }
    return str_replace("/" . App::get_admin_folder_name() . "/", "/", $url);
}

?>