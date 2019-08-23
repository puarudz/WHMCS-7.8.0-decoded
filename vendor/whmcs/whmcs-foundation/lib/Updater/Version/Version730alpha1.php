<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version730alpha1 extends IncrementalVersion
{
    protected $updateActions = array("createAuthnTables", "addConfigureSignInIntegrationPermission", "rewordUnchangedClientSignUpEmail", "createPendingUpgradeOrderCancelledEmailTemplate", "convertOldBackupSettings", "registerJobsQueueTask", "rewordUnchangedClientSignUpEmail", "addMissingOauthScopes", "cloneOauthScopeShoppingCartAddonsPivots", "syncApplicationLinksOnUpgrade");
    protected function createAuthnTables()
    {
        (new \WHMCS\Authentication\Remote\ProviderSetting())->createTable();
        (new \WHMCS\Authentication\Remote\AccountLink())->createTable();
    }
    protected function addConfigureSignInIntegrationPermission()
    {
        \WHMCS\Database\Capsule::table("tbladminperms")->insert(array("roleid" => 1, "permid" => 143));
    }
    protected function rewordUnchangedClientSignUpEmail()
    {
        $md5Hashes = array("b3d53c45f3981f9c7d4d053455d46c58", "941c87834ee67e1b79160611538f9fc6", "2e2ee71771894abae85baed9c65286c7", "5f935b27378e06e052ce06405f99e806", "77a990c043a7f1dad14168bda8630373", "4f19b67d8c47f8bbc3462248b9fbcde5", "8de6eb28b01cd20869a6bbacad1c2e13", "03ac3993c49f759cfddcab7f14798ba7");
        $email = \WHMCS\Mail\Template::master()->whereName("Client Signup Email")->first();
        if ($email && in_array(md5($email->message), $md5Hashes)) {
            $email->message = "<p>Dear {\$client_first_name},</p>\n<p>Thank you for creating a {\$companyname} account. Please review this email in its entirety as it contains important information.</p>\n<p><strong>Logging In</strong></p>\n<p>You can access our client area at {\$whmcs_link}</p>\n<p>You will need your email address and the password you chose during signup to login.</p>\n<p>If you created an account as part of placing a new order with us, you will shortly receive an order confirmation email.</p>\n<p><strong>Getting Support</strong></p>\n<p>If you need any help or assistance, you can access our support resources below.</p>\n<ul>\n<li><a href=\"{\$whmcs_url}/knowledgebase.php\">Knowledgebase</a></li>\n<li><a href=\"{\$whmcs_url}/submitticket.php\">Submit a Ticket</a></li>\n</ul>\n<p>{\$signature}</p>\n<p><small>You are receiving this email because you recently created an account. If you did not do this, please contact us.</small></p>";
            $email->save();
        }
    }
    protected function convertOldBackupSettings()
    {
        $activeBackupSettings = array();
        $email = \WHMCS\Config\Setting::getValue("DailyEmailBackup");
        if ($email) {
            $activeBackupSettings[] = "email";
        }
        $ftpHostname = \WHMCS\Config\Setting::getValue("FTPBackupHostname");
        if ($ftpHostname) {
            $activeBackupSettings[] = "ftp";
        }
        \WHMCS\Config\Setting::setValue("ActiveBackupSystems", implode(",", $activeBackupSettings));
    }
    protected function createPendingUpgradeOrderCancelledEmailTemplate()
    {
        $existingTemplate = \WHMCS\Mail\Template::master()->where("name", "=", "Upgrade Order Cancelled")->first();
        if (!$existingTemplate) {
            $mailTemplate = new \WHMCS\Mail\Template();
            $mailTemplate->name = "Upgrade Order Cancelled";
            $mailTemplate->subject = "Pending Upgrade Order Cancelled";
            $mailTemplate->language = "";
            $mailTemplate->plaintext = false;
            $mailTemplate->custom = false;
            $mailTemplate->type = "product";
            $mailTemplate->message = "<p>Dear {\$client_name},</p>\n<p>Re: {\$service_product_name}{if \$service_domain} ({\$service_domain}){/if}</p>\n<p>\n    Recently you placed an upgrade order with us.<br>\n    Today your automated renewal invoice has been generated for the product/service listed above which has invalidated the upgrade quote and invoice.<br>\n    As a result, your upgrade order has now been cancelled.\n</p>\n<p>Should you wish to continue with the upgrade, we ask that you please first pay the renewal invoice, after which you will be able to order the upgrade again and simply pay the difference.</p>\n<p>We thank you for your business.</p>\n<p>{\$signature}</p>";
            $mailTemplate->save();
        }
        return $this;
    }
    protected function registerJobsQueueTask()
    {
        \WHMCS\Cron\Task\RunJobsQueue::register();
    }
    protected function syncApplicationLinksOnUpgrade()
    {
        $oldScopeName = "clientarea:shopping_cart_addons";
        $newScopeName = "clientarea:upgrade";
        $servers = \WHMCS\Database\Capsule::table("tblapplinks")->where("tblapplinks.is_enabled", "=", 1)->select("tblapplinks.module_name")->get();
        $queue = new \WHMCS\Scheduling\Jobs\Queue();
        foreach ($servers as $server) {
            $serverIDs = \WHMCS\Database\Capsule::table("tblservers")->where("name", "=", $server->module_name)->select("id")->get();
            foreach ($serverIDs as $serverID) {
                $queue->add($server->module_name . ".scope.link.clone", "WHMCS\\ApplicationLink\\Provision", "cloneScopeLink", array($serverID->id, $oldScopeName, $newScopeName), 0, false);
            }
        }
    }
    protected function addMissingOauthScopes()
    {
        $standardScopeDefinitions = (new \WHMCS\ApplicationLink\Scope())->getStandardScopes();
        $scopeArray = \WHMCS\ApplicationLink\Scope::pluck("scope")->toArray();
        foreach ($standardScopeDefinitions as $scope) {
            if (!in_array($scope["scope"], $scopeArray)) {
                $newScope = new \WHMCS\ApplicationLink\Scope();
                foreach ($scope as $attribute => $value) {
                    $newScope->{$attribute} = $value;
                }
                $newScope->save();
            }
        }
    }
    protected function cloneOauthScopeShoppingCartAddonsPivots()
    {
        $oldScope = \WHMCS\ApplicationLink\Scope::where("scope", "=", "clientarea:shopping_cart_addons")->value("id");
        $newScope = \WHMCS\ApplicationLink\Scope::where("scope", "=", "clientarea:upgrade")->value("id");
        if ($oldScope) {
            $data = array();
            $oldScopedClients = \WHMCS\Database\Capsule::table("tbloauthserver_client_scopes")->where("scope_id", $oldScope)->pluck("client_id");
            $newScopedClients = \WHMCS\Database\Capsule::table("tbloauthserver_client_scopes")->where("scope_id", $newScope)->pluck("client_id");
            $needScope = array_diff($oldScopedClients, $newScopedClients);
            foreach ($needScope as $clientId) {
                $data[] = array("client_id" => $clientId, "scope_id" => $newScope);
            }
            if ($data) {
                foreach (array_chunk($data, 16384) as $dataChunk) {
                    \WHMCS\Database\Capsule::table("tbloauthserver_client_scopes")->insert($dataChunk);
                }
            }
        }
    }
    public function getFeatureHighlights()
    {
        $highlights = array();
        $highlights[] = new \WHMCS\Notification\FeatureHighlight("Social <span>Integration</span>", "Allow customers to register and sign in using popular 3rd party services.", null, "signin-integration.png", "<img src=\"images/whatsnew/social-auth-logos.png\" style=\"margin:0 auto;\">", "https://docs.whmcs.com/Sign-In_Integrations", "Learn More");
        $highlights[] = new \WHMCS\Notification\FeatureHighlight("MailChimp <span>Integration</span>", "With e-commerce integration for powerful email automations", null, "mailchimp.png", "Take advantage of MailChimp's powerful email automations such as abandoned cart follow-ups and product on-boarding emails.", "https://docs.whmcs.com/Mailchimp", "Learn More");
        $highlights[] = new \WHMCS\Notification\FeatureHighlight("New <span>Backup Options</span>", "Introducing Secure FTP and cPanel Backups", null, "cpanel-backup.png", "Perform automated daily backups to a Secure FTP destination or take advantage of the new cPanel account level backups for automated file and database backups.", "https://docs.whmcs.com/Automatic_Backups", "Learn More");
        $highlights[] = new \WHMCS\Notification\FeatureHighlight("Improved SSL Automation", "Now with Plesk &amp; DirectAdmin Support", "marketconnect-splash.png", "market-connect.png", "MarketConnect services now support automatic provisioning with Plesk and DirectAdmin too.", "marketconnect.php?tour=1", "Take the Tour", "https://go.whmcs.com/1234/market-connect-learn-more", "Learn More");
        $highlights[] = new \WHMCS\Notification\FeatureHighlight("New <span>Payment Gateways</span>", "Two new modules for payment processing", null, "payment-gateways.png", "<img src=\"images/whatsnew/authnetskrill1tap.png\" style=\"margin:0 auto;\">", "https://docs.whmcs.com/New_Payment_Gateways_in_7.3", "Learn More");
        return $highlights;
    }
}

?>