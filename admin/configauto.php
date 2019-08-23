<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Configure Automation Settings");
$aInt->title = $aInt->lang("automation", "title");
$aInt->sidebar = "config";
$aInt->icon = "autosettings";
$aInt->helplink = "Automation Settings";
$aInt->requireAuthConfirmation();
$whmcs = App::self();
$sub = $whmcs->get_req_var("sub");
if ($sub == "save") {
    check_token("WHMCS.admin.default");
    $changes = array();
    $currentConfig = WHMCS\Config\Setting::allAsArray();
    $booleanKeys = array("DomainSyncEnabled", "DomainSyncNotifyOnly", "DRAutoDeleteInactiveClients");
    $friendlyNames = array("DRAutoDeleteInactiveClients" => "Data Retention Delete Inactive Clients", "DRAutoDeleteInactiveClientsMonths" => "Data Retention Delete Inactive Clients Months");
    $changeOfDailyCronHour = false;
    $cronStatus = new WHMCS\Cron\Status();
    $requestedDailyCronHour = (int) $whmcs->get_req_var("dailycronexecutionhour");
    $currentDailyCronHour = $cronStatus->getDailyCronExecutionHour();
    if ($requestedDailyCronHour !== (int) $currentDailyCronHour->format("H")) {
        $cronStatus->setDailyCronExecutionHour($requestedDailyCronHour);
        foreach (WHMCS\Scheduling\Task\AbstractTask::all() as $task) {
            $status = $task->getStatus();
            $currentNextDue = $status->getNextDue();
            $currentNextDue->hour($requestedDailyCronHour)->second("00");
            if ($currentNextDue->isPast()) {
                $newNextDue = $task->anticipatedNextRun($currentNextDue);
            } else {
                $newNextDue = $currentNextDue;
            }
            $status->setNextDue($newNextDue)->save();
        }
        $changeOfDailyCronHour = true;
    }
    $settingsToSave = array("DRAutoDeleteInactiveClients" => App::getFromRequest("autodeleteinactiveclients"), "DRAutoDeleteInactiveClientsMonths" => App::getFromRequest("autodeleteinactiveclientsmonths"), "DomainSyncEnabled" => App::getFromRequest("domainsyncenabled"), "DomainSyncNextDueDate" => App::getFromRequest("domainsyncnextduedate"), "DomainSyncNextDueDateDays" => (int) App::getFromRequest("domainsyncnextduedatedays"), "DomainSyncNotifyOnly" => App::getFromRequest("domainsyncnotifyonly"), "DomainStatusSyncFrequency" => (int) App::getFromRequest("domain_status_sync_frequency"), "DomainTransferStatusCheckFrequency" => (int) App::getFromRequest("domain_transfer_sync_frequency"));
    if ($settingsToSave["DomainStatusSyncFrequency"] < 0) {
        $settingsToSave["DomainStatusSyncFrequency"] = 0;
    }
    if ($settingsToSave["DomainTransferStatusCheckFrequency"] < 0) {
        $settingsToSave["DomainTransferStatusCheckFrequency"] = 0;
    }
    foreach ($settingsToSave as $key => $value) {
        if ($currentConfig[$key] != $value) {
            if (in_array($key, $friendlyNames)) {
                $friendlySetting = $friendlyNames[$key];
            } else {
                $regEx = "/(?<=[a-z])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])/x";
                $friendlySettingParts = preg_split($regEx, $key);
                $friendlySetting = implode(" ", $friendlySettingParts);
            }
            $currentValue = $currentConfig[$key];
            $newValue = $value;
            if (in_array($key, $booleanKeys)) {
                $currentValue = "off";
                $newValue = "on";
                if (!$value || $value === false || $value == "off") {
                    $currentValue = "on";
                    $newValue = "off";
                }
            }
            $changes[] = (string) $friendlySetting . " changed from '" . $currentValue . "' to '" . $newValue . "'";
        }
        WHMCS\Config\Setting::setValue($key, $value);
    }
    WHMCS\Config\Setting::setValue("AutoSuspension", $whmcs->get_req_var("autosuspend"));
    WHMCS\Config\Setting::setValue("AutoSuspensionDays", $whmcs->get_req_var("days"));
    WHMCS\Config\Setting::setValue("CreateInvoiceDaysBefore", $whmcs->get_req_var("createinvoicedays"));
    WHMCS\Config\Setting::setValue("CreateDomainInvoiceDaysBefore", $whmcs->get_req_var("createdomaininvoicedays"));
    WHMCS\Config\Setting::setValue("SendReminder", $whmcs->get_req_var("invoicesendreminder"));
    WHMCS\Config\Setting::setValue("SendInvoiceReminderDays", $whmcs->get_req_var("invoicesendreminderdays"));
    WHMCS\Config\Setting::setValue("UpdateStatsAuto", $whmcs->get_req_var("updatestatusauto"));
    WHMCS\Config\Setting::setValue("CloseInactiveTickets", $whmcs->get_req_var("closeinactivetickets"));
    WHMCS\Config\Setting::setValue("PruneTicketAttachmentsMonths", (int) App::getFromRequest("remove_inactive_attachments"));
    WHMCS\Config\Setting::setValue("AutoTermination", $whmcs->get_req_var("autotermination"));
    WHMCS\Config\Setting::setValue("AutoTerminationDays", $whmcs->get_req_var("autoterminationdays"));
    WHMCS\Config\Setting::setValue("AutoUnsuspend", $whmcs->get_req_var("autounsuspend"));
    WHMCS\Config\Setting::setValue("AddLateFeeDays", $whmcs->get_req_var("addlatefeedays"));
    WHMCS\Config\Setting::setValue("SendFirstOverdueInvoiceReminder", $whmcs->get_req_var("invoicefirstoverduereminder"));
    WHMCS\Config\Setting::setValue("SendSecondOverdueInvoiceReminder", $whmcs->get_req_var("invoicesecondoverduereminder"));
    WHMCS\Config\Setting::setValue("SendThirdOverdueInvoiceReminder", $whmcs->get_req_var("invoicethirdoverduereminder"));
    WHMCS\Config\Setting::setValue("AutoCancellationRequests", $whmcs->get_req_var("autocancellationrequests"));
    WHMCS\Config\Setting::setValue("CCProcessDaysBefore", $whmcs->get_req_var("ccprocessdaysbefore"));
    WHMCS\Config\Setting::setValue("CCAttemptOnlyOnce", $whmcs->get_req_var("ccattemptonlyonce"));
    WHMCS\Config\Setting::setValue("CCRetryEveryWeekFor", $whmcs->get_req_var("ccretryeveryweekfor"));
    WHMCS\Config\Setting::setValue("CCDaySendExpiryNotices", $whmcs->get_req_var("ccdaysendexpirynotices"));
    WHMCS\Config\Setting::setValue("CCDoNotRemoveOnExpiry", $whmcs->get_req_var("donotremovecconexpiry"));
    WHMCS\Config\Setting::setValue("CurrencyAutoUpdateExchangeRates", $whmcs->get_req_var("currencyautoupdateexchangerates"));
    WHMCS\Config\Setting::setValue("CurrencyAutoUpdateProductPrices", $whmcs->get_req_var("currencyautoupdateproductprices"));
    WHMCS\Config\Setting::setValue("OverageBillingMethod", $whmcs->get_req_var("overagebillingmethod"));
    WHMCS\Config\Setting::setValue("ReversalChangeInvoiceStatus", $whmcs->get_req_var("revchangeinvoicestatus"));
    WHMCS\Config\Setting::setValue("ReversalChangeDueDates", $whmcs->get_req_var("revchangeduedates"));
    WHMCS\Config\Setting::setValue("CreateInvoiceDaysBeforeMonthly", $whmcs->get_req_var("invoicegenmonthly"));
    WHMCS\Config\Setting::setValue("CreateInvoiceDaysBeforeQuarterly", $whmcs->get_req_var("invoicegenquarterly"));
    WHMCS\Config\Setting::setValue("CreateInvoiceDaysBeforeSemiAnnually", $whmcs->get_req_var("invoicegensemiannually"));
    WHMCS\Config\Setting::setValue("CreateInvoiceDaysBeforeAnnually", $whmcs->get_req_var("invoicegenannually"));
    WHMCS\Config\Setting::setValue("CreateInvoiceDaysBeforeBiennially", $whmcs->get_req_var("invoicegenbiennially"));
    WHMCS\Config\Setting::setValue("CreateInvoiceDaysBeforeTriennially", $whmcs->get_req_var("invoicegentriennially"));
    WHMCS\Config\Setting::setValue("AutoClientStatusChange", $whmcs->get_req_var("autoclientstatuschange"));
    foreach ($renewals as $count => $renewal) {
        if ($whmcs->get_req_var("renewalWhen", (int) $count) == "after" && 0 < $renewal) {
            $renewals[$count] *= -1;
        }
    }
    WHMCS\Config\Setting::setValue("DomainRenewalNotices", implode(",", $renewals));
    $savedConfig = WHMCS\Config\Setting::allAsArray();
    foreach ($currentConfig as $setting => $value) {
        if ($setting == "DomainRenewalNotices") {
            $options = array("First", "Second", "Third", "Fourth", "Fifth");
            $currentSetting = explode(",", $value);
            foreach ($currentSetting as $key => $renewal) {
                if ($renewals[$key] != $renewal) {
                    $currentBeforeAfter = $newBeforeAfter = "";
                    if (0 < $renewal) {
                        $currentBeforeAfter = " before ";
                    } else {
                        if ($renewal < 0) {
                            $renewal *= -1;
                            $currentBeforeAfter = " after ";
                        }
                    }
                    if (0 < $renewals[$key]) {
                        $newBeforeAfter = " before";
                    } else {
                        if ($renewals[$key] < 0) {
                            $renewals[$key] *= -1;
                            $newBeforeAfter = " after";
                        }
                    }
                    $changes[] = (string) $options[$key] . " Domain Renewal Notice changed from " . (string) $renewal . " day(s)" . $currentBeforeAfter . "to " . (string) $renewals[$key] . " day(s)" . $newBeforeAfter;
                }
            }
        } else {
            if (in_array($setting, $settingsToSave)) {
                continue;
            }
            if ($savedConfig[$setting] != $value) {
                if ($value == "on" && !$savedConfig[$setting]) {
                    $savedConfig[$setting] = "off";
                }
                if ($savedConfig[$setting] == "on" && !$value) {
                    $value = "off";
                }
                $regEx = "/(?<=[a-z])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])/x";
                $friendlySettingParts = preg_split($regEx, $setting);
                $friendlySetting = implode(" ", $friendlySettingParts);
                $changes[] = (string) $friendlySetting . " changed from '" . $value . "' to '" . $savedConfig[$setting] . "'";
            }
        }
    }
    $autoSuspendEmail = $whmcs->get_req_var("autoSuspendEmail");
    $disableSuspendEmail = $autoSuspendEmail ? "0" : "1";
    $autoUnsuspendEmail = $whmcs->get_req_var("autoUnsuspendEmail");
    $disableUnsuspendEmail = $autoUnsuspendEmail ? "0" : "1";
    $template = WHMCS\Mail\Template::where("type", "=", "product")->where("name", "=", "Service Suspension Notification")->get()->first();
    if (!is_null($template)) {
        if ($template->disabled != $disableSuspendEmail) {
            $changes[] = "Service Suspension Notification email template " . ($disableSuspendEmail == "0" ? "Enabled" : "Disabled");
        }
        $template->disabled = $disableSuspendEmail;
        $template->save();
    }
    $template = WHMCS\Mail\Template::where("type", "=", "product")->where("name", "=", "Service Unsuspension Notification")->get()->first();
    if (!is_null($template)) {
        if ($template->disabled != $disableUnsuspendEmail) {
            $changes[] = "Service Unsuspension Notification email template " . ($disableUnsuspendEmail == "0" ? "Enabled" : "Disabled");
        }
        $template->disabled = $disableUnsuspendEmail;
        $template->save();
    }
    if ($changes) {
        logAdminActivity("Automation Settings Changed. Changes made: " . implode(". ", $changes));
    }
    redir("success=1" . ($changeOfDailyCronHour ? "&cronhourchanged=1" : ""));
}
ob_start();
if (App::getFromRequest("success")) {
    infoBox($aInt->lang("automation", "changesuccess"), $aInt->lang("automation", "changesuccessinfo"));
    echo $infobox;
}
if (App::getFromRequest("cronhourchanged")) {
    echo WHMCS\View\Helper::alert(AdminLang::trans("automation.changeOfDailyCronHourHelpText") . " <a href=\"https://docs.whmcs.com/Crons#Change_of_Daily_Cron_Hour\" target=\"_blank\" class=\"alert-link\">" . AdminLang::trans("global.learnMore") . " &raquo;</a>", "info");
}
$cron = new WHMCS\Cron();
if ($lastInvocationTime = $cron->getLastCronInvocationTime()) {
    $lastInvocationTime = fromMySQLDate($lastInvocationTime->format("Y-m-d H:i:s"), true);
} else {
    $lastInvocationTime = "Never";
}
$result = select_query("tblconfiguration", "", "");
while ($data = mysql_fetch_array($result)) {
    $setting = $data["setting"];
    $value = $data["value"];
    $CONFIG[(string) $setting] = (string) $value;
}
$autoUnsuspendEmail = WHMCS\Mail\Template::where("type", "=", "product")->where("name", "=", "Service Unsuspension Notification")->get()->first();
$autoUnsuspendChecked = "";
if (!is_null($autoUnsuspendEmail) && !$autoUnsuspendEmail->disabled) {
    $autoUnsuspendChecked = " checked";
}
$autoSuspendEmail = WHMCS\Mail\Template::where("type", "=", "product")->where("name", "=", "Service Suspension Notification")->get()->first();
$autoSuspendChecked = "";
if (!is_null($autoSuspendEmail) && !$autoSuspendEmail->disabled) {
    $autoSuspendChecked = " checked";
}
$jscode = "function showadvinvoice() {\n    \$(\"#advinvoicesettings\").slideToggle();\n}";
echo "\n<div class=\"automation-cron-status\">\n    ";
if ($cron->hasCronBeenInvokedIn24Hours()) {
    echo "        <div class=\"alert-success \">\n            <i class=\"fas fa-check\"></i>\n            Cron Status Ok\n            <small>Last Run: ";
    echo $lastInvocationTime;
    echo "</small>\n        </div>\n    ";
} else {
    if ($cron->hasCronEverBeenInvoked()) {
        echo "        <div class=\"alert-danger\">\n            <i class=\"fas fa-times\"></i>\n            Cron Status Error\n            <small>Last Run: ";
        echo $lastInvocationTime;
        echo "</small>\n        </div>\n    ";
    } else {
        echo "        <div class=\"alert-warning\">\n            <i class=\"fas fa-exclamation-triangle\"></i>\n            No Cron Records\n            <small>\n                <a href=\"https://docs.whmcs.com/Crons\" target=\"_blank\" class=\"alert-link\">\n                    View setup documentation &raquo;\n                </a>\n            </small>\n        </div>\n    ";
    }
}
echo "</div>\n<div class=\"automation-cron-label\">\n    <span>The System Cron automates tasks within WHMCS. It should be configured to execute every 5 minutes, or as frequently as your web hosting provider allows.</span>\n</div>\n\n<div class=\"cron-command input-group\">\n    <span class=\"input-group-addon\" id=\"cronPhp\">Cron Command</span>\n    <input type=\"text\" id=\"cronPhp\" value=\"*/5 * * * * ";
echo WHMCS\Environment\Php::getPreferredCliBinary();
echo " -q ";
$cronFolder = $whmcs->getCronDirectory();
echo $cronFolder;
echo "/cron.php\" class=\"form-control\" onfocus=\"this.select()\" onmouseup=\"return false;\" />\n</div>\n\n<form method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
echo "?sub=save\">\n\n<h2>Scheduling</h2>\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\" width=\"25%\">Time of Day</td><td class=\"fieldarea\"><select name=\"dailycronexecutionhour\" class=\"form-control select-inline input-select-time\">";
$label = "am";
$dailyCronExecutionHour = WHMCS\Cron::getDailyCronExecutionHour()->format("H");
for ($hour = 0; $hour <= 23; $hour++) {
    $friendlyHour = $hour;
    if ($friendlyHour == 12) {
        $label = "pm";
    } else {
        if (12 < $friendlyHour) {
            $friendlyHour -= 12;
        }
    }
    echo "<option value=\"" . $hour . "\"" . ($dailyCronExecutionHour == $hour ? " selected" : "") . ">" . $friendlyHour . ":00" . $label . "</option>";
}
echo "</select> The hour of the day you wish for the daily automated actions to be executed &nbsp; <a href=\"#\" data-toggle=\"tooltip\" data-placement=\"right\" title=\"For this setting to take effect, your cron must be configured to run at least once every hour. We recommend setting it to run every 5 minutes to allow for other system processes to take place.\"><i class=\"fas fa-info-circle\"></i> Important Note</a></td></tr>\n</table>\n\n<h2>";
echo AdminLang::trans("automation.modulefunctions");
echo "</h2>\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\" width=\"25%\">";
echo $aInt->lang("automation", "autosuspend");
echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"autosuspend\"";
if ($CONFIG["AutoSuspension"] == "on") {
    echo " CHECKED";
}
echo "> ";
echo $aInt->lang("automation", "autosuspendinfo");
echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("automation", "suspenddays");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"days\" value=\"";
echo $CONFIG["AutoSuspensionDays"];
echo "\" class=\"form-control input-50 input-inline\"> ";
echo $aInt->lang("automation", "suspenddaysinfo");
echo "</td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
echo $aInt->lang("automation", "sendAutoSuspendEmail");
echo "    </td>\n    <td class=\"fieldarea\">\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"autoSuspendEmail\"";
echo $autoSuspendChecked;
echo ">\n            ";
echo $aInt->lang("automation", "sendAutoSuspendEmailInfo");
echo "        </label>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("automation", "autounsuspend");
echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"autounsuspend\"";
if ($CONFIG["AutoUnsuspend"] == "on") {
    echo " CHECKED";
}
echo "> ";
echo $aInt->lang("automation", "autounsuspendinfo");
echo "</label></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
echo $aInt->lang("automation", "sendAutoUnsuspendEmail");
echo "    </td>\n    <td class=\"fieldarea\">\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"autoUnsuspendEmail\"";
echo $autoUnsuspendChecked;
echo ">\n            ";
echo $aInt->lang("automation", "sendAutoUnsuspendEmailInfo");
echo "        </label>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("automation", "autoterminate");
echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"autotermination\"";
if ($CONFIG["AutoTermination"] == "on") {
    echo " CHECKED";
}
echo "> ";
echo $aInt->lang("automation", "autoterminateinfo");
echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("automation", "terminatedays");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"autoterminationdays\" value=\"";
echo $CONFIG["AutoTerminationDays"];
echo "\" class=\"form-control input-50 input-inline\"> ";
echo $aInt->lang("automation", "terminatedaysinfo");
echo "</td></tr>\n</table>\n\n<h2>";
echo $aInt->lang("automation", "billingsettings");
echo "</h2>\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("automation", "invoicegen");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"createinvoicedays\" value=\"";
echo $CONFIG["CreateInvoiceDaysBefore"];
echo "\" class=\"form-control input-50 input-inline\"> ";
echo $aInt->lang("automation", "invoicegeninfo");
echo " (<a href=\"#\" onclick=\"showadvinvoice();return false\">";
echo $aInt->lang("automation", "advsettings");
echo "</a>)\n<div id=\"advinvoicesettings\" align=\"center\" style=\"display:none;\">\n<br />\n<b>";
echo $aInt->lang("automation", "percycle");
echo "</b><br />\n";
echo $aInt->lang("automation", "percycleinfo");
echo ":<br />\n<table width=\"650\" cellspacing=\"1\" bgcolor=\"#cccccc\">\n<tr bgcolor=\"#efefef\" style=\"text-align:center;font-weight:bold\"><td>";
echo $aInt->lang("billingcycles", "monthly");
echo "</td><td>";
echo $aInt->lang("billingcycles", "quarterly");
echo "</td><td>";
echo $aInt->lang("billingcycles", "semiannually");
echo "</td><td>";
echo $aInt->lang("billingcycles", "annually");
echo "</td><td>";
echo $aInt->lang("billingcycles", "biennially");
echo "</td><td>";
echo $aInt->lang("billingcycles", "triennially");
echo "</td></tr>\n<tr bgcolor=\"#ffffff\"><td><input type=\"text\" name=\"invoicegenmonthly\" value=\"";
echo $CONFIG["CreateInvoiceDaysBeforeMonthly"];
echo "\" class=\"form-control input-100\" /></td><td><input type=\"text\" name=\"invoicegenquarterly\" value=\"";
echo $CONFIG["CreateInvoiceDaysBeforeQuarterly"];
echo "\" class=\"form-control input-100\" /></td><td><input type=\"text\" name=\"invoicegensemiannually\" value=\"";
echo $CONFIG["CreateInvoiceDaysBeforeSemiAnnually"];
echo "\" class=\"form-control input-100\" /></td><td><input type=\"text\" name=\"invoicegenannually\" value=\"";
echo $CONFIG["CreateInvoiceDaysBeforeAnnually"];
echo "\" class=\"form-control input-100\" /></td><td><input type=\"text\" name=\"invoicegenbiennially\" value=\"";
echo $CONFIG["CreateInvoiceDaysBeforeBiennially"];
echo "\" class=\"form-control input-100\" /></td><td><input type=\"text\" name=\"invoicegentriennially\" value=\"";
echo $CONFIG["CreateInvoiceDaysBeforeTriennially"];
echo "\" class=\"form-control input-100\" /></td></tr>\n</table>\n(";
echo $aInt->lang("automation", "blankcycledefault");
echo ")\n<br /><br />\n<b>";
echo $aInt->lang("automation", "domainsettings");
echo "</b><br />\n";
echo $aInt->lang("automation", "domainsettingsinfo");
echo ":<br />\n<input type=\"text\" name=\"createdomaininvoicedays\" value=\"";
echo $CONFIG["CreateDomainInvoiceDaysBefore"];
echo "\" class=\"form-control input-50 input-inline\"> (";
echo $aInt->lang("automation", "blankdefault");
echo ")<br /><br />\n</div>\n</td></tr>\n<tr><td class=\"fieldlabel\" width=\"25%\">";
echo $aInt->lang("automation", "reminderemails");
echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"invoicesendreminder\"";
if ($CONFIG["SendReminder"] == "on") {
    echo " CHECKED";
}
echo "> ";
echo $aInt->lang("automation", "reminderemailsinfo");
echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("automation", "unpaidreminder");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"invoicesendreminderdays\" value=\"";
echo $CONFIG["SendInvoiceReminderDays"];
echo "\" class=\"form-control input-50 input-inline\"> ";
echo $aInt->lang("automation", "unpaidreminderinfo");
echo " (";
echo $aInt->lang("automation", "todisable");
echo ")</td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("automation", "firstreminder");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"invoicefirstoverduereminder\" value=\"";
echo $CONFIG["SendFirstOverdueInvoiceReminder"];
echo "\" class=\"form-control input-50 input-inline\"> ";
echo $aInt->lang("automation", "firstreminderinfo");
echo " (";
echo $aInt->lang("automation", "todisable");
echo ")</td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("automation", "secondreminder");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"invoicesecondoverduereminder\" value=\"";
echo $CONFIG["SendSecondOverdueInvoiceReminder"];
echo "\" class=\"form-control input-50 input-inline\"> ";
echo $aInt->lang("automation", "secondreminderinfo");
echo " (";
echo $aInt->lang("automation", "todisable");
echo ")</td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("automation", "thirdreminder");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"invoicethirdoverduereminder\" value=\"";
echo $CONFIG["SendThirdOverdueInvoiceReminder"];
echo "\" class=\"form-control input-50 input-inline\"> ";
echo $aInt->lang("automation", "thirdreminderinfo");
echo " (";
echo $aInt->lang("automation", "todisable");
echo ")</td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("automation", "latefeedays");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"addlatefeedays\" value=\"";
echo $CONFIG["AddLateFeeDays"];
echo "\" class=\"form-control input-50 input-inline\"> ";
echo $aInt->lang("automation", "latefeedaysinfo");
echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("automation", "overages");
echo "</td><td class=\"fieldarea\"><label class=\"radio-inline\"><input type=\"radio\" name=\"overagebillingmethod\" value=\"1\"";
if ($CONFIG["OverageBillingMethod"] == "1") {
    echo " checked";
}
echo "> ";
echo $aInt->lang("automation", "overageslastday");
echo "</label><br /><label class=\"radio-inline\"><input type=\"radio\" name=\"overagebillingmethod\" value=\"2\"";
if ($CONFIG["OverageBillingMethod"] == "2") {
    echo " checked";
}
echo "> ";
echo $aInt->lang("automation", "overagesnextinvoice");
echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("automation", "revchangeinvoicestatus");
echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"revchangeinvoicestatus\" value=\"1\"";
if ($CONFIG["ReversalChangeInvoiceStatus"] == "1") {
    echo " CHECKED";
}
echo "> ";
echo $aInt->lang("automation", "revchangeinvoicestatusinfo");
echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("automation", "revchangeduedates");
echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"revchangeduedates\" value=\"1\"";
if ($CONFIG["ReversalChangeDueDates"] == "1") {
    echo " CHECKED";
}
echo "> ";
echo $aInt->lang("automation", "revchangeduedatesinfo");
echo "</label></td></tr>\n</table>\n\n<h2>";
echo $aInt->lang("automation", "ccsettings");
echo "</h2>\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\" width=\"25%\">";
echo $aInt->lang("automation", "ccdaysbeforedue");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"ccprocessdaysbefore\" value=\"";
echo $CONFIG["CCProcessDaysBefore"];
echo "\" class=\"form-control input-50 input-inline\"> ";
echo $aInt->lang("automation", "ccdaysbeforedueinfo");
echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("automation", "cconlyonce");
echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"ccattemptonlyonce\"";
if ($CONFIG["CCAttemptOnlyOnce"] == "on") {
    echo " CHECKED";
}
echo "> ";
echo $aInt->lang("automation", "cconlyonceinfo");
echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("automation", "cceveryweek");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"ccretryeveryweekfor\" value=\"";
echo $CONFIG["CCRetryEveryWeekFor"];
echo "\" class=\"form-control input-50 input-inline\"> ";
echo $aInt->lang("automation", "cceveryweekinfo");
echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("automation", "ccexpirynotices");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"ccdaysendexpirynotices\" value=\"";
echo $CONFIG["CCDaySendExpiryNotices"];
echo "\" class=\"form-control input-50 input-inline\"> ";
echo $aInt->lang("automation", "ccexpirynoticesinfo");
echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("automation", "ccnoremove");
echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"donotremovecconexpiry\"";
if ($CONFIG["CCDoNotRemoveOnExpiry"] == "on") {
    echo " CHECKED";
}
echo "> ";
echo $aInt->lang("automation", "ccnoremoveinfo");
echo "</label></td></tr>\n</table>\n\n<h2>";
echo $aInt->lang("automation", "currencysettings");
echo "</h2>\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\" width=\"25%\">";
echo $aInt->lang("automation", "exchangerates");
echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"currencyautoupdateexchangerates\"";
if ($CONFIG["CurrencyAutoUpdateExchangeRates"] == "on") {
    echo " CHECKED";
}
echo "> ";
echo $aInt->lang("automation", "exchangeratesinfo");
echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("automation", "productprices");
echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"currencyautoupdateproductprices\"";
if ($CONFIG["CurrencyAutoUpdateProductPrices"] == "on") {
    echo " CHECKED";
}
echo "> ";
echo $aInt->lang("automation", "productpricesinfo");
echo "</label></td></tr>\n</table>\n\n<h2>";
echo $aInt->lang("automation", "domainremindersettings");
echo "</h2>\n";
$renewals = explode(",", $CONFIG["DomainRenewalNotices"], 5);
for ($i = count($renewals); $i < 5; $i++) {
    $renewals[] = 0;
}
$languageStrings = array("firstrenewal", "secondrenewal", "thirdrenewal", "fourthrenewal", "fifthrenewal");
$renewalData = array();
foreach ($renewals as $count => $renewal) {
    $selectData = "<select name=\"renewalWhen[" . $count . "]\" class=\"form-control select-inline\">" . "<option value=\"before\"" . (0 <= $renewal ? " selected=\"selected\"" : "") . ">" . $aInt->lang("global", "before") . "</option>" . "<option value=\"after\"" . ($renewal < 0 ? " selected=\"selected\"" : "") . ">" . $aInt->lang("global", "after") . "</option>" . "</select>";
    $renewalData[] = array("name" => $languageStrings[$count], "fieldName" => "renewals[" . $count . "]", "value" => $renewal < 0 ? (int) ($renewal * -1) : (int) $renewal, "info" => sprintf($aInt->lang("automation", $languageStrings[$count] . "info"), $selectData));
}
echo "<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n";
foreach ($renewalData as $count => $renewal) {
    echo "    <tr>\n        <td class=\"fieldlabel\" width=\"25%\">\n            " . $aInt->lang("automation", $renewal["name"]) . "\n        </td>\n        <td class=\"fieldarea\">\n            <input type=\"text\" name=\"" . $renewal["fieldName"] . "\" value=\"" . $renewal["value"] . "\" class=\"form-control input-50 input-inline\" />\n             " . $renewal["info"] . " (" . $aInt->lang("automation", "todisable") . ")\n        </td>\n    </tr>";
}
echo "</table>\n    <h2>";
echo AdminLang::trans("automation.domainSync");
echo "</h2>\n    ";
$domainSyncEnabled = $domainSyncDate = $domainSyncNotify = "";
if (WHMCS\Config\Setting::getValue("DomainSyncEnabled")) {
    $domainSyncEnabled = " checked=\"checked\"";
}
if (WHMCS\Config\Setting::getValue("DomainSyncNextDueDate")) {
    $domainSyncDate = " checked=\"checked\"";
}
if (WHMCS\Config\Setting::getValue("DomainSyncNotifyOnly")) {
    $domainSyncNotify = " checked=\"checked\"";
}
echo "    <table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n        <tr>\n            <td class=\"fieldlabel\" width=\"25%\">";
echo AdminLang::trans("general.domainsyncenabled");
echo "</td>\n            <td class=\"fieldarea\">\n                <label class=\"checkbox-inline\">\n                    <input type=\"checkbox\" name=\"domainsyncenabled\"";
echo $domainSyncEnabled;
echo ">\n                    ";
echo AdminLang::trans("general.domainsyncenabledinfo");
echo "                </label>\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">";
echo AdminLang::trans("general.domainsyncnextduedate");
echo "</td>\n            <td class=\"fieldarea\">\n                <label class=\"checkbox-inline\">\n                    <input type=\"checkbox\" name=\"domainsyncnextduedate\"";
echo $domainSyncDate;
echo ">\n                    ";
echo AdminLang::trans("general.domainsyncnextduedateinfo");
echo "                </label>\n                <input type=\"text\" name=\"domainsyncnextduedatedays\"\n                       class=\"form-control input-50 input-inline\"\n                       value=\"";
echo (int) WHMCS\Config\Setting::getValue("DomainSyncNextDueDateDays");
echo "\"\n                />\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">";
echo AdminLang::trans("general.domainsyncnotifyonly");
echo "</td>\n            <td class=\"fieldarea\">\n                <label class=\"checkbox-inline\">\n                    <input type=\"checkbox\" name=\"domainsyncnotifyonly\"";
echo $domainSyncNotify;
echo ">\n                    ";
echo AdminLang::trans("general.domainsyncnotifyonlyinfo");
echo "                </label>\n            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">";
echo AdminLang::trans("automation.domainStatusSyncFrequency");
echo "</td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"domain_status_sync_frequency\"\n                       class=\"form-control input-50 input-inline\"\n                       value=\"";
echo (int) WHMCS\Config\Setting::getValue("DomainStatusSyncFrequency");
echo "\"\n                />\n                ";
echo AdminLang::trans("automation.domainStatusSyncFrequencyInfo");
echo "            </td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">";
echo AdminLang::trans("automation.domainTransferSyncFrequency");
echo "</td>\n            <td class=\"fieldarea\">\n                <input type=\"text\" name=\"domain_transfer_sync_frequency\"\n                       class=\"form-control input-50 input-inline\"\n                       value=\"";
echo (int) WHMCS\Config\Setting::getValue("DomainTransferStatusCheckFrequency");
echo "\"\n                />\n                ";
echo AdminLang::trans("automation.domainTransferSyncFrequencyInfo");
echo "            </td>\n        </tr>\n    </table>\n\n\n<h2>";
echo $aInt->lang("automation", "ticketsettings");
echo "</h2>\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n    <tr>\n        <td class=\"fieldlabel\" width=\"25%\">\n            ";
echo AdminLang::trans("automation.inactivetickets");
echo "        </td>\n        <td class=\"fieldarea\">\n            <input type=\"number\"\n                   name=\"closeinactivetickets\"\n                   value=\"";
echo WHMCS\Config\Setting::getValue("CloseInactiveTickets");
echo "\"\n                   class=\"form-control input-80 input-inline\"\n                   min=\"0\"\n            >\n            ";
echo AdminLang::trans("automation.inactiveticketsinfo");
echo "            (";
echo AdminLang::trans("automation.todisable");
echo ")\n        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\" width=\"25%\">\n            ";
echo AdminLang::trans("automation.pruneTicketAttachments");
echo "        </td>\n        <td class=\"fieldarea\">\n            <select name=\"remove_inactive_attachments\" class=\"form-control select-inline\">\n                ";
foreach (range(0, 24) as $monthValue) {
    $selectedValue = (int) WHMCS\Config\Setting::getValue("PruneTicketAttachmentsMonths");
    $replacements = array();
    $description = "global.disabled";
    $selected = "";
    if ($monthValue) {
        $description = "global.someMonths";
        if ($monthValue === 1) {
            $description = "global.aMonth";
        }
        $replacements = array(":months" => $monthValue);
    }
    if ($selectedValue === $monthValue) {
        $selected = " selected=\"selected\"";
    }
    $description = AdminLang::trans($description, $replacements);
    echo "<option value=\"" . $monthValue . "\"" . $selected . ">" . $description . "</option>";
}
echo "            </select>\n            ";
echo AdminLang::trans("automation.pruneTicketAttachmentsInfo");
echo "        </td>\n    </tr>\n</table>\n\n<h2>";
echo AdminLang::trans("automation.dataRetentionSettings");
echo "</h2>\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n    <tr>\n        <td class=\"fieldlabel\" width=\"25%\">";
echo AdminLang::trans("automation.autoDeleteInactiveClients");
echo "</td>\n        <td class=\"fieldarea\">\n            <label class=\"radio-inline\">\n                <input type=\"radio\" name=\"autodeleteinactiveclients\" value=\"0\"";
echo !WHMCS\Config\Setting::getValue("DRAutoDeleteInactiveClients") ? " checked" : "";
echo ">\n                ";
echo AdminLang::trans("automation.dataRetentionNever");
echo "            </label>\n            <br>\n            <label class=\"radio-inline\">\n                <input type=\"radio\" name=\"autodeleteinactiveclients\" value=\"1\"";
echo WHMCS\Config\Setting::getValue("DRAutoDeleteInactiveClients") ? " checked" : "";
echo ">\n                ";
echo AdminLang::trans("automation.autoDeleteInactiveClientsAfter");
echo ": <input type=\"text\" name=\"autodeleteinactiveclientsmonths\" value=\"";
echo (int) WHMCS\Config\Setting::getValue("DRAutoDeleteInactiveClientsMonths");
echo "\" class=\"form-control input-inline input-100\">\n            </label>\n            <div class=\"alert alert-info\" style=\"margin:0;\">\n                <i class=\"fas fa-exclamation-triangle fa-fw\"></i>\n                <strong>";
echo AdminLang::trans("automation.warning");
echo ":</strong>\n                <em>";
echo AdminLang::trans("automation.warningRemoveCustomerData");
echo "</em>\n                <br>\n                ";
echo AdminLang::trans("automation.inactiveClientExplanation");
echo "            </div>\n        </td>\n    </tr>\n</table>\n\n<h2>";
echo $aInt->lang("automation", "misc");
echo "</h2>\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\" width=\"25%\">";
echo $aInt->lang("automation", "cancellation");
echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"autocancellationrequests\"";
if ($CONFIG["AutoCancellationRequests"] == "on") {
    echo " CHECKED";
}
echo "> ";
echo $aInt->lang("automation", "cancellationinfo");
echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("automation", "usage");
echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"updatestatusauto\"";
if ($CONFIG["UpdateStatsAuto"] == "on") {
    echo " CHECKED";
}
echo "> ";
echo $aInt->lang("automation", "usageinfo");
echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("automation", "autostatuschange");
echo "</td><td class=\"fieldarea\"><label class=\"radio-inline\"><input type=\"radio\" name=\"autoclientstatuschange\" value=\"1\" ";
if ($CONFIG["AutoClientStatusChange"] == "1") {
    echo " CHECKED";
}
echo "> ";
echo $aInt->lang("automation", "disableautoinactiveinfo");
echo "</label> <br /><label class=\"radio-inline\"><input type=\"radio\" name=\"autoclientstatuschange\" value=\"2\" ";
if ($CONFIG["AutoClientStatusChange"] == "2") {
    echo " CHECKED";
}
echo "> ";
echo $aInt->lang("automation", "defaultstatusautochange");
echo "</label> <br /><label class=\"radio-inline\"><input type=\"radio\" name=\"autoclientstatuschange\" value=\"3\" ";
if ($CONFIG["AutoClientStatusChange"] == "3") {
    echo " CHECKED";
}
echo "> ";
echo $aInt->lang("automation", "setdaysforinactiveinfo");
echo "</label></td></tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
echo $aInt->lang("global", "savechanges");
echo "\" class=\"btn btn-primary\">\n    <input type=\"reset\" value=\"";
echo $aInt->lang("global", "cancelchanges");
echo "\" class=\"btn btn-default\" />\n</div>\n\n</form>\n\n";
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jscode = $jscode;
$aInt->display();

?>