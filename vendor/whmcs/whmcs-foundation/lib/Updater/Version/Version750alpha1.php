<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version750alpha1 extends IncrementalVersion
{
    protected $updateActions = array("replaceTicketTidInEmailNotificationsSubject", "registerDataRetentionPruningCronTask", "addMentionNotificationAdminEmailTemplate", "convertDomainPricingBooleanColumns", "convertConfigurationFileDomainRenewalGracePeriodsToDatabase", "updateUpcomingDomainRenewalNotice", "createSiteLockWelcomeEmailTemplate", "removeThreeYearMarketConnectSslTerms", "removeUnusedLegacyModules");
    protected function replaceTicketTidInEmailNotificationsSubject()
    {
        $emailTemplates = \WHMCS\Mail\Template::where("name", "Escalation Rule Notification")->get();
        foreach ($emailTemplates as $emailTemplate) {
            if (stripos($emailTemplate->subject, "\$tickettid") !== false) {
                $emailTemplate->subject = str_replace(array("\$tickettid"), array("\$ticket_tid"), $emailTemplate->subject);
                $emailTemplate->save();
            }
        }
        return $this;
    }
    protected function registerDataRetentionPruningCronTask()
    {
        \WHMCS\Cron\Task\DataRetentionPruning::register();
        return $this;
    }
    protected function addMentionNotificationAdminEmailTemplate()
    {
        $mailTemplate = \WHMCS\Mail\Template::where("name", "Mention Notification")->first();
        if (!$mailTemplate) {
            $template = new \WHMCS\Mail\Template();
            $template->name = "Mention Notification";
            $template->subject = "{\$mention_admin_name} mentioned you in a {\$mention_entity}";
            $template->type = "admin";
            $template->message = "<p>{\$mention_admin_name} mentioned you in {\$mention_entity_description}:</p>\n<blockquote>\n{\$message}\n</blockquote>\n<p><a href=\"{\$mention_view_url}\" target=\"_blank\">{\$mention_entity_action}</a></p>";
            $template->save();
        }
        return $this;
    }
    private function getUnusedLegacyModules()
    {
        return array("addons" => array("fixed_invoice_data"), "admin" => array("ip_manager", "ip_monitor"), "fraud" => array("telesign", "varilogix_fraudcall"), "gateways" => array("alertpay", "bidpay", "egold", "eway", "ewayuk", "googlecheckout", "ideal", "internetsecure", "libertyreserve", "myideal", "openecho", "payoffline", "stormpay"), "registrars" => array("directi", "dottk", "netregistry", "planetdomains", "registerfly", "resellerclubbeta", "tppinternet", "ventraip"), "servers" => array("castcontrol", "dotnetpanel", "enkompass", "enomtruste", "ensimx", "fluidvm", "plesk10", "plesk8", "plesk9", "pleskreseller"));
    }
    protected function removeUnusedLegacyModules()
    {
        (new \WHMCS\Module\LegacyModuleCleanup())->removeModulesIfInstalledAndUnused($this->getUnusedLegacyModules());
        return $this;
    }
    protected function convertDomainPricingBooleanColumns()
    {
        $columns = array("dnsmanagement", "emailforwarding", "idprotection", "eppcode");
        foreach ($columns as $column) {
            \WHMCS\Domains\Extension::convertBooleanColumn($column);
        }
        return $this;
    }
    protected function convertConfigurationFileDomainRenewalGracePeriodsToDatabase()
    {
        $DomainRenewalGracePeriods = array();
        ob_start();
        $loaded = (include ROOTDIR . "configuration.php");
        ob_end_clean();
        $fullDomainRenewalGracePeriods = array_merge(array(".com" => "30", ".net" => "30", ".org" => "30", ".info" => "15", ".biz" => "30", ".mobi" => "30", ".name" => "30", ".asia" => "30", ".tel" => "30", ".in" => "15", ".mn" => "30", ".bz" => "30", ".cc" => "30", ".tv" => "30", ".eu" => "0", ".co.uk" => "97", ".org.uk" => "97", ".me.uk" => "97", ".us" => "30", ".ws" => "0", ".me" => "30", ".cn" => "30", ".nz" => "0", ".ca" => "30"), $DomainRenewalGracePeriods);
        if ($loaded && $fullDomainRenewalGracePeriods) {
            foreach ($fullDomainRenewalGracePeriods as $tld => $gracePeriod) {
                $domain = \WHMCS\Domains\Extension::where("extension", $tld)->first();
                if (0 <= $gracePeriod && $domain) {
                    $domain->gracePeriod = $gracePeriod;
                    $domain->gracePeriodFee = 0;
                    $domain->save();
                }
            }
        }
        return $this;
    }
    protected function updateUpcomingDomainRenewalNotice()
    {
        $md5Values = array("7556f88474b1aca229b73b6683735625", "c977a0f0f691a2f18f2a95fb16867bb8");
        $newMessage = "<p>Dear {\$client_name},</p>\n<p>This is a reminder that the domain listed below is scheduled to expire soon.</p>\n<p>Domain Name - Expiry Date - Description</p>\n<p>--------------------------------------------------------------</p>\n<p>{\$domain_name} - {\$domain_next_due_date} - Expires in {\$domain_days_until_nextdue} Days</p>\n<p>Please be aware that if your domain name expires, any web site or email services associated with it will stop working.</p>\n<p>Renew it now to avoid interruption in service.</p>\n<p>To renew your domain, <a href=\"{\$domain_renewal_url}\">click here</a>.</p>\n<p>To view and manage your domains, you can login to our client area here: <a href=\"{\$domains_manage_url}\">Client Area</a></p>\n<p>If you have any questions, please reply to this email. Thank you for using our domain name services.</p>\n<p>{\$signature}</p>";
        $template = \WHMCS\Mail\Template::master()->where("name", "Upcoming Domain Renewal Notice")->first();
        if ($template && in_array(md5($template->message), $md5Values)) {
            $template->message = $newMessage;
            $template->save();
        }
        if (!$template) {
            $template = new \WHMCS\Mail\Template();
            $template->name = "Upcoming Domain Renewal Notice";
            $template->subject = "Upcoming Domain Renewal Notice";
            $template->message = $newMessage;
            $template->custom = false;
            $template->attachments = array();
            $template->type = "domain";
            $template->plaintext = false;
            $template->fromEmail = "";
            $template->fromName = "";
            $template->language = "";
            $template->save();
        }
        return $this;
    }
    public function createSiteLockWelcomeEmailTemplate()
    {
        $templateTitle = "SiteLock Welcome Email";
        $existingTemplatesCount = \WHMCS\Mail\Template::where("name", "=", $templateTitle)->count();
        if (0 < $existingTemplatesCount) {
            return $this;
        }
        $message = "<p>Dear {\$client_name},</p>\n<p>Thank you for purchasing the SiteLock service. You are just a few steps away from securing your website.</p>\n<p>Below are the details you will need to administer and use the SiteLock service.</p>\n{if (\$sitelock_requires_ftp && !\$sitelock_ftp_auto_provisioned) || (\$sitelock_requires_dns && !\$sitelock_dns_auto_provisioned)}\n    <p><b>Additional Setup Required</b></p>\n{/if}\n{if \$sitelock_requires_ftp && !\$sitelock_ftp_auto_provisioned}\n    <p>To allow SiteLock to automatically fix issues that are discovered with your website, SiteLock requires FTP access to your website.</p>\n    <p>Unfortunately we were unable to provision these automatically and so you must supply FTP credentials to SiteLock via the SiteLock Control Panel.</p>\n    <p>To do this, please login to our <a href=\"{\$whmcs_url}\">client area</a> and click the Login button found under the SiteLock service. Then navigate to Sites > FTP Credentials within the SiteLock control panel.</p>\n{/if}\n{if \$sitelock_requires_dns && !\$sitelock_dns_auto_provisioned}\n    <p>To allow SiteLock to provide WAF and CDN services for your website, SiteLock requires some DNS changes.</p>\n    <p>Unfortunately we were unable to provision these automatically and so you must make these changes manually.</p>\n    <p>To do this, please modify your domain DNS host records as follows:</p>\n    <p>{\$sitelock_dns_host_record_info}</p>\n{/if}\n<p><b>Using SiteLock</b></p>\n<p>To use the SiteLock service, login to our <a href=\"{\$whmcs_url}\">client area</a> and click the Login button found under the SiteLock service.</p>\n<p>If you have any questions, please reply to this email. Thank you for choosing our services.</p>\n<p>{\$signature}</p>";
        $template = new \WHMCS\Mail\Template();
        $template->name = $templateTitle;
        $template->subject = "Getting Started with SiteLock";
        $template->message = $message;
        $template->custom = false;
        $template->attachments = array();
        $template->type = "product";
        $template->plaintext = false;
        $template->fromEmail = "";
        $template->fromName = "";
        $template->language = "";
        $template->save();
        return $this;
    }
    protected function removeThreeYearMarketConnectSslTerms()
    {
        $currency = getCurrency();
        foreach (\WHMCS\Product\Product::ssl()->get() as $product) {
            $triennial = $product->pricing()->triennial();
            if (!is_null($triennial)) {
                \WHMCS\Database\Capsule::table("tblpricing")->where("type", "product")->where("relid", $product->id)->update(array("tsetupfee" => "-1", "triennially" => "-1"));
            }
        }
        return $this;
    }
}

?>