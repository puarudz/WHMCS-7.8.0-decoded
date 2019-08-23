<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version720alpha1 extends IncrementalVersion
{
    protected $updateActions = array("convertAddonsBooleanColumns", "populateClientIdInHostingAddons", "relabelQuoteAcceptedNotificationEmailTemplate", "updateWhmcsUrlInClientSignUpEmailToBeALink", "updateWhmcsUrlInDomainRegistrationConfirmationEmailToBeALink", "updateWhmcsUrlInDedicatedVPSWelcomeEmailToBeALink", "updateWhmcsUrlInDomainTransferInitiatedEmailToBeALink", "updateWhmcsUrlInDomainRenewalConfirmationEmailToBeALink", "updateWhmcsUrlInCreditCardExpiringSoonEmailToBeALink", "addViewFeatureHighlightsPermission", "addManageMarketPlacePermission", "addConfigureTicketEscalationsPermissionsToConfigureSupportDepartments", "createDirectDebitPaymentFailedEmailTemplate", "createDirectDebitPaymentConfirmationEmailTemplate", "createDirectDebitPaymentPendingEmailTemplate", "createCreditCardPaymentPendingEmailTemplate", "createPaymentReversedNotificationEmailTemplate", "createSpamExpertsWelcomeEmailTemplate", "createWeeblyWelcomeEmailTemplate", "conditionallyCreateHtaccessFile", "detectAndSetUriPathMode", "createSslConfigurationRequiredEmailTemplate");
    public function __construct(\WHMCS\Version\SemanticVersion $version)
    {
        parent::__construct($version);
        $this->filesToRemove[] = implode(DIRECTORY_SEPARATOR, array(ROOTDIR, "vendor", "whmcs", "whmcs-foundation", "lib", "Environment", "MySql.php"));
    }
    protected function convertAddonsBooleanColumns()
    {
        $columns = array("tax", "showorder", "suspendproduct");
        foreach ($columns as $column) {
            \WHMCS\Product\Addon::convertBooleanColumn($column);
        }
        return $this;
    }
    protected function populateClientIdInHostingAddons()
    {
        $addons = \WHMCS\Database\Capsule::table("tblhostingaddons")->join("tblhosting", "tblhostingaddons.hostingid", "=", "tblhosting.id")->get(array("tblhostingaddons.id", "tblhosting.userid"));
        foreach ($addons as $addon) {
            \WHMCS\Database\Capsule::table("tblhostingaddons")->where("id", "=", $addon->id)->update(array("userid" => $addon->userid));
        }
        return $this;
    }
    protected function createSpamExpertsWelcomeEmailTemplate()
    {
        $mailTemplate = new \WHMCS\Mail\Template();
        $mailTemplate->name = "SpamExperts Welcome Email";
        $mailTemplate->subject = "Welcome to Spam Free Email";
        $mailTemplate->language = "";
        $mailTemplate->plaintext = false;
        $mailTemplate->custom = false;
        $mailTemplate->type = "product";
        $mailTemplate->message = "<p>Congratulations!</p>\n<p>Your service has been setup and is ready for you to begin using.</p>\n{if \$configuration_required}\n<p><strong>Required Action</strong></p>\n<p>To begin using SpamExperts mail services, you must modify the MX records for your domain to the following:</p>\n<p>\n{foreach \$required_mx_records as \$mx_host => \$mx_priority}\n    {\$mx_host} with a recommended priority of {\$mx_priority}<br />\n{/foreach}\n</p>\n<p>We have guides available for this at <a href=\"https://my.spamexperts.com/kb/109/Hosted-Cloud-MX-records.html\">https://my.spamexperts.com/kb/109/Hosted-Cloud-MX-records.html</a></p>\n{/if}\n<p><strong>Managing your Service</strong></p>\n<p>You can access and manage your email filtering at any time from our client area at <a href=\"{\$whmcs_url}\">{\$whmcs_url}</a></p>\n<p>Simply login and look for the SpamExperts Manage link on the homepage.</p>\n<p>If you need any further assistance, please contact our <a href=\"{\$whmcs_url}submitticket.php\">support team</a></p>\n<p>{\$signature}</p>";
        $mailTemplate->save();
        return $this;
    }
    protected function relabelQuoteAcceptedNotificationEmailTemplate()
    {
        \WHMCS\Mail\Template::where("name", "=", "Quote Accepted Notification")->update(array("type" => "admin"));
        return $this;
    }
    protected function updateWhmcsUrlInClientSignUpEmailToBeALink()
    {
        $emailMd5s = array("4f19b67d8c47f8bbc3462248b9fbcde5", "8de6eb28b01cd20869a6bbacad1c2e13", "03ac3993c49f759cfddcab7f14798ba7");
        $email = \WHMCS\Mail\Template::whereName("Client Signup Email")->first();
        if ($email && in_array(md5($email->message), $emailMd5s)) {
            $email->message = "<p>\nDear {\$client_name},\n</p>\n<p>\nThank you for signing up with us. Your new account has been setup and you can now login to our client area using the details below.\n</p>\n<p>\nEmail Address: {\$client_email}<br />\nPassword: {\$client_password}\n</p>\n<p>\nTo login, visit <a href=\"{\$whmcs_url}\">{\$whmcs_url}</a>\n</p>\n<p>\n{\$signature}\n</p>";
            $email->save();
        }
        return $this;
    }
    protected function updateWhmcsUrlInDomainRegistrationConfirmationEmailToBeALink()
    {
        $registrationConfirmationEmailMd5s = array("f162fe721a621aae30968efe36ac0897", "7961987629acf56d7d67f3b0e15bbe22", "cddfa2d4ad6e4e2b1b6f3b6039f13ecc");
        $email = \WHMCS\Mail\Template::whereName("Domain Registration Confirmation")->first();
        if ($email && in_array(md5($email->message), $registrationConfirmationEmailMd5s)) {
            $email->message = "<p>\nDear {\$client_name},\n</p>\n<p>\nThis message is to confirm that your domain purchase has been successful. The details of the domain purchase are below:\n</p>\n<p>\nRegistration Date: {\$domain_reg_date}<br />\nDomain: {\$domain_name}<br />\nRegistration Period: {\$domain_reg_period}<br />\nAmount: {\$domain_first_payment_amount}<br />\nNext Due Date: {\$domain_next_due_date}\n</p>\n<p>\nYou may login to your client area at <a href=\"{\$whmcs_url}\">{\$whmcs_url}</a> to manage your new domain.\n</p>\n<p>\n{\$signature}\n</p>";
            $email->save();
        }
        return $this;
    }
    protected function updateWhmcsUrlInDedicatedVPSWelcomeEmailToBeALink()
    {
        $emailMd5s = array("d7de59e957cca29439d59c1c2515106e", "47d3ede936cec4f45fdf71a4a278d8de", "255a059e873eb3f1945a78b6738cea16");
        $email = \WHMCS\Mail\Template::whereName("Dedicated/VPS Server Welcome Email")->first();
        if ($email && in_array(md5($email->message), $emailMd5s)) {
            $email->message = "<p>\nDear {\$client_name},<br />\n<br />\n<strong>PLEASE PRINT THIS MESSAGE FOR YOUR RECORDS - PLEASE READ THIS EMAIL IN FULL.</strong>\n</p>\n<p>\nWe are pleased to tell you that the server you ordered has now been set up and is operational.\n</p>\n<p>\n<strong>Server Details<br />\n</strong>=============================\n</p>\n<p>\n{\$service_product_name}\n</p>\n<p>\nMain IP: {\$service_dedicated_ip}<br />\nRoot pass: {\$service_password}\n</p>\n<p>\nIP address allocation: <br />\n{\$service_assigned_ips}\n</p>\n<p>\nServerName: {\$service_domain}\n</p>\n<p>\n<strong>WHM Access<br />\n</strong>=============================<br />\n<a href=\"http://xxxxx:2086/\">http://xxxxx:2086</a><br />\nUsername: root<br />\nPassword: {\$service_password}\n</p>\n<p>\n<strong>Custom DNS Server Addresses</strong><br />\n=============================<br />\nThe custom DNS addresses you should set for your domain to use are: <br />\nPrimary DNS: {\$service_ns1}<br />\nSecondary DNS: {\$service_ns2}\n</p>\n<p>\nYou will have to login to your registrar and find the area where you can specify both of your custom name server addresses.\n</p>\n<p>\nAfter adding these custom nameservers to your domain registrar control panel, it will take 24 to 48 hours for your domain to delegate authority to your DNS server. Once this has taken effect, your DNS server has control over the DNS records for the domains which use your custom name server addresses.\n</p>\n<p>\n<strong>SSH Access Information<br />\n</strong>=============================<br />\nMain IP Address: xxxxxxxx<br />\nServer Name: {\$service_domain}<br />\nRoot Password: xxxxxxxx\n</p>\n<p>\nYou can access your server using a free simple SSH client program called Putty located at:<br />\n<a href=\"http://www.securitytools.net/mirrors/putty/\">http://www.securitytools.net/mirrors/putty/</a>\n</p>\n<p>\n<strong>Support</strong><br />\n=============================<br />\nFor any support needs, please open a ticket at <a href=\"{\$whmcs_url}\">{\$whmcs_url}</a>\n</p>\n<p>\nPlease include any necessary information to provide you with faster service, such as root password, domain names, and a description of the problem / or assistance needed. This will speed up the support time by allowing our administrators to immediately begin diagnosing the problem.\n</p>\n<p>\nThe manual for cPanel can be found here: <a href=\"http://www.cpanel.net/docs/cp/\">http://www.cpanel.net/docs/cp/</a> <br />\nFor documentation on using WHM please see the following link: <a href=\"http://www.cpanel.net/docs/whm/index.html\">http://www.cpanel.net/docs/whm/index.html</a>\n</p>\n<p>\n=============================\n</p>\n<p>\nIf you need to move accounts to the server use: Transfers Copy an account from another server with account password\n</p>\n<p>\n<a href=\"http://xxxxxxx:2086/scripts2/norootcopy\">http://xxxxxxx:2086/scripts2/norootcopy</a>\n</p>\n<p>\nNote the other server must use cpanel to move it.\n</p>\n<p>\n=============================\n</p>\n<p>\n{\$signature}\n</p>";
            $email->save();
        }
        return $this;
    }
    protected function updateWhmcsUrlInDomainTransferInitiatedEmailToBeALink()
    {
        $emailMd5s = array("a01c30a2dac7095a026dda8b31e94084", "7be1174c0274d36efb3bfa0bcbfc7568");
        $email = \WHMCS\Mail\Template::whereName("Domain Transfer Initiated")->first();
        if ($email && in_array(md5($email->message), $emailMd5s)) {
            $email->message = "<p>Dear {\$client_name}, </p><p>Thank you for your domain transfer order. Your order has been received and we have now initiated the transfer process. The details of the domain purchase are below: </p><p>Domain: {\$domain_name}<br />Registration Length: {\$domain_reg_period}<br />Transfer Price: {\$domain_first_payment_amount}<br />Renewal Price: {\$domain_recurring_amount}<br />Next Due Date: {\$domain_next_due_date} </p><p>You may login to your client area at <a href=\"{\$whmcs_url}\">{\$whmcs_url}</a> to manage your domain. </p><p>{\$signature} </p>";
            $email->save();
        }
        return $this;
    }
    protected function updateWhmcsUrlInDomainRenewalConfirmationEmailToBeALink()
    {
        $emailMd5s = array("a3205ccf969438f00109492779f3c5b4", "3c5f61a81cef69bda0efe6b341991857");
        $email = \WHMCS\Mail\Template::whereName("Domain Renewal Confirmation")->first();
        if ($email && in_array(md5($email->message), $emailMd5s)) {
            $email->message = "<p>Dear {\$client_name}, </p><p>Thank you for your domain renewal order. Your domain renewal request for the domain listed below has now been completed.</p><p>Domain: {\$domain_name}<br />Renewal Length: {\$domain_reg_period}<br />Renewal Price: {\$domain_recurring_amount}<br />Next Due Date: {\$domain_next_due_date} </p><p>You may login to your client area at <a href=\"{\$whmcs_url}\">{\$whmcs_url}</a> to manage your domain. </p><p>{\$signature} </p>";
            $email->save();
        }
        return $this;
    }
    protected function updateWhmcsUrlInCreditCardExpiringSoonEmailToBeALink()
    {
        $emailMd5s = array("831a81a6329d183c1f5dd820c44ff359", "ee2dd5ee752bc7df0abc2bbfa69fd683");
        $email = \WHMCS\Mail\Template::whereName("Credit Card Expiring Soon")->first();
        if ($email && in_array(md5($email->message), $emailMd5s)) {
            $email->message = "<p>Dear {\$client_name}, </p><p>This is a notice to inform you that your {\$client_cc_type} credit card ending with {\$client_cc_number} will be expiring next month on {\$client_cc_expiry}. Please login to update your credit card information as soon as possible and prevent any interuptions in service at <a href=\"{\$whmcs_url}\">{\$whmcs_url}</a><br /><br />If you have any questions regarding your account, please open a support ticket from the client area.</p><p>{\$signature}</p>";
            $email->save();
        }
        return $this;
    }
    protected function addViewFeatureHighlightsPermission()
    {
        \WHMCS\Database\Capsule::table("tbladminperms")->insert(array("roleid" => 1, "permid" => 139));
    }
    protected function addManageMarketPlacePermission()
    {
        \WHMCS\Database\Capsule::table("tbladminperms")->insert(array("roleid" => 1, "permid" => 141));
    }
    public function getFeatureHighlights()
    {
        $highlights = array();
        $highlights[] = new \WHMCS\Notification\FeatureHighlight("Introducing", "The new, easier way to buy and sell services.", "marketconnect-splash.png", "marketconnect-icon.png", "Increase your product line-up and offer products such as SSL, Website Builder and Spam Protection", "marketconnect.php?tour=1", "Take the Tour", "https://go.whmcs.com/1234/market-connect-learn-more", "Learn More");
        $highlights[] = new \WHMCS\Notification\FeatureHighlight("Full SSL Automation", "Landing Pages, Promotional Material and Improved UX", null, "ssl-automation.png", "Browsers are changing and SSL is becoming essential for all websites. WHMCS 7.2 gives you the tools you need to meet those needs.", "https://go.whmcs.com/1238/ssl-automation-learn-more", "Learn More");
        $highlights[] = new \WHMCS\Notification\FeatureHighlight("Domain Name Pricing Matrix", "New and improved extension pricing in the client area", null, "domain-pricing.png", "Allows clients to view all the extensions you offer, pricing, and displays your featured spotlight extensions prominently prior to clients performing a search.", "https://go.whmcs.com/1242/domain-pricing-matrix", "Learn More", "../cart.php?a=add&domain=register", "Try it Now");
        $highlights[] = new \WHMCS\Notification\FeatureHighlight("Start accepting Direct Debits", "Introducing SlimPay with support for Direct Debit & SEPA Payments", null, "direct-debit.png", "Direct Debit is a pull-based bank-to-bank payment method that simplifies subscription billing. And best of all, it’s just 1.5% per transaction. Start today.", "https://go.whmcs.com/1246/direct-debit-payments", "Learn More");
        return $highlights;
    }
    protected function createWeeblyWelcomeEmailTemplate()
    {
        $mailTemplate = new \WHMCS\Mail\Template();
        $mailTemplate->name = "Weebly Welcome Email";
        $mailTemplate->subject = "Welcome to your Beautiful Website";
        $mailTemplate->language = "";
        $mailTemplate->plaintext = false;
        $mailTemplate->custom = false;
        $mailTemplate->type = "product";
        $mailTemplate->message = "<p>Dear {\$client_name},</p>\n<p>Congratulations!</p>\n<p>Your account has been setup and you are ready to begin building your site with Weebly.</p>\n{if \$configuration_required}\n<p>To allow automatic publishing of your site, Weebly require an FTP account to be created and provided to them in their settings.</p>\n{/if}\n<p>Guides for how to get started with Weebly can be found at <a href=\"https://hc.weebly.com/hc/en-us/categories/203453908-Getting-Started\">https://hc.weebly.com/hc/en-us/categories/203453908-Getting-Started</a></p>\n<p>To access the Weebly site builder and begin building your website, please <a href=\"{\$whmcs_url}clientarea.php?action=productdetails&id={\$service_id}\">click here</a></p>\n<p>If you need any further assistance, please contact our <a href=\"{\$whmcs_url}submitticket.php\">support team</a></p>\n<p>{\$signature}</p>";
        $mailTemplate->save();
        return $this;
    }
    protected function addConfigureTicketEscalationsPermissionsToConfigureSupportDepartments()
    {
        $roles = \WHMCS\Database\Capsule::table("tbladminperms")->where("permid", 75)->pluck("roleid");
        foreach ($roles as $roleid) {
            \WHMCS\Database\Capsule::table("tbladminperms")->insert(array("roleid" => $roleid, "permid" => 140));
        }
        return $this;
    }
    public function conditionallyCreateHtaccessFile()
    {
        $configuration = new \WHMCS\Admin\Setup\General\UriManagement\ConfigurationController();
        try {
            $rewriteFile = \WHMCS\Route\Rewrite\File::factory(\WHMCS\Route\Rewrite\File::FILE_DEFAULT);
            if ($rewriteFile->isExclusivelyWhmcs() || $rewriteFile->isEmpty()) {
                $rewriteFile->updateWhmcsRuleSet();
                $request = (new \WHMCS\Http\Message\ServerRequest())->withQueryParams(array("setting" => $configuration::SETTING_AUTO_MANAGE, "state" => true));
                $configuration->updateUriManagementSetting($request);
            } else {
                $request = (new \WHMCS\Http\Message\ServerRequest())->withQueryParams(array("setting" => $configuration::SETTING_AUTO_MANAGE, "state" => false));
                $configuration->updateUriManagementSetting($request);
            }
        } catch (\Exception $e) {
            logActivity("Unable to take ownership of rewrite rules during update: " . $e->getMessage());
            try {
                $request = (new \WHMCS\Http\Message\ServerRequest())->withQueryParams(array("setting" => $configuration::SETTING_AUTO_MANAGE, "state" => false));
                $configuration->updateUriManagementSetting($request);
            } catch (\Exception $e) {
            }
        }
    }
    public function detectAndSetUriPathMode()
    {
        if (!\WHMCS\Config\Setting::getValue("SystemURL")) {
            return $this;
        }
        $configuration = new \WHMCS\Admin\Setup\General\UriManagement\ConfigurationController();
        try {
            $response = $configuration->remoteDetectEnvironmentMode(new \WHMCS\Http\Message\ServerRequest());
            $body = $response->getRawData();
            if (!empty($body["data"]["mode"])) {
                $mode = $body["data"]["mode"];
                if ($mode == \WHMCS\Route\UriPath::MODE_UNKNOWN) {
                    $mode = \WHMCS\Route\UriPath::MODE_BASIC;
                }
                $request = (new \WHMCS\Http\Message\ServerRequest())->withQueryParams(array("mode" => $mode));
                $configuration->setEnvironmentMode($request);
            } else {
                $request = (new \WHMCS\Http\Message\ServerRequest())->withQueryParams(array("mode" => \WHMCS\Route\UriPath::MODE_BASIC));
                $configuration->setEnvironmentMode($request);
            }
        } catch (\Exception $e) {
            logActivity("Failed to detect URI Path Mode during update: " . $e->getMessage());
            try {
                $request = (new \WHMCS\Http\Message\ServerRequest())->withQueryParams(array("mode" => \WHMCS\Route\UriPath::MODE_BASIC));
                $configuration->setEnvironmentMode($request);
            } catch (\Exception $e) {
            }
        }
    }
    protected function createDirectDebitPaymentFailedEmailTemplate()
    {
        $mailTemplate = new \WHMCS\Mail\Template();
        $mailTemplate->name = "Direct Debit Payment Failed";
        $mailTemplate->subject = "Direct Debit Payment Failed";
        $mailTemplate->language = "";
        $mailTemplate->plaintext = false;
        $mailTemplate->custom = false;
        $mailTemplate->type = "invoice";
        $mailTemplate->message = "<p>Dear {\$client_name},</p>\n<p>This is a notice that a recent direct debit payment for you failed.</p>\n<p>Invoice Date: {\$invoice_date_created}<br />Invoice No: {\$invoice_num}<br />Amount: {\$invoice_total}<br />Status: {\$invoice_status}</p>\n<p>You now need to login to your client area to pay the invoice manually.<br />{\$invoice_link}</p>\n<p>{\$signature}</p>";
        $mailTemplate->save();
        return $this;
    }
    protected function createDirectDebitPaymentConfirmationEmailTemplate()
    {
        $mailTemplate = new \WHMCS\Mail\Template();
        $mailTemplate->name = "Direct Debit Payment Confirmation";
        $mailTemplate->subject = "Direct Debit Payment Confirmation";
        $mailTemplate->language = "";
        $mailTemplate->plaintext = false;
        $mailTemplate->custom = false;
        $mailTemplate->type = "invoice";
        $mailTemplate->message = "<p>Dear {\$client_name},</p>\n<p>This is a payment receipt for Invoice {\$invoice_num} generated on {\$invoice_date_created}. The payment has been taken automatically via direct debit.</p>\n<p>Amount: {\$invoice_total}<br />Status: {\$invoice_status}</p>\n<p>You may review your invoice history at any time by logging in to your client area.<br />{\$invoice_link}</p>\n<p>Note: This email will serve as an official receipt for this payment.</p>\n<p>{\$signature}</p>";
        $mailTemplate->save();
        return $this;
    }
    protected function createDirectDebitPaymentPendingEmailTemplate()
    {
        $mailTemplate = new \WHMCS\Mail\Template();
        $mailTemplate->name = "Direct Debit Payment Pending";
        $mailTemplate->subject = "Direct Debit Payment Pending";
        $mailTemplate->language = "";
        $mailTemplate->plaintext = false;
        $mailTemplate->custom = false;
        $mailTemplate->type = "invoice";
        $mailTemplate->message = "<p>Dear {\$client_name},</p>\n<p>This is a notification that a payment has been requested for Invoice {\$invoice_num}. The payment has been taken automatically via direct debit on or around {\$invoice_date_due}.</p>\n<p>Amount: {\$invoice_total}<br />Status: {\$invoice_status}</p>\n<p>You may review your invoice history at any time by logging in to your client area.<br />{\$invoice_link}</p>\n<p>Note: This email will serve as an official notification for this payment.</p>\n<p>{\$signature}</p>";
        $mailTemplate->save();
        return $this;
    }
    protected function createCreditCardPaymentPendingEmailTemplate()
    {
        $mailTemplate = new \WHMCS\Mail\Template();
        $mailTemplate->name = "Credit Card Payment Pending";
        $mailTemplate->subject = "Credit Card Payment Pending";
        $mailTemplate->language = "";
        $mailTemplate->plaintext = false;
        $mailTemplate->custom = false;
        $mailTemplate->type = "invoice";
        $mailTemplate->message = "<p>Dear {\$client_name},</p>\n<p>This is a payment pending notification for Invoice {\$invoice_num} generated on {\$invoice_date_created}. The payment will be taken from your card on record with us automatically.</p>\n<p>Amount: {\$invoice_total}<br />Status: {\$invoice_status}</p>\n<p>You may review your invoice history at any time by logging in to your client area.<br />{\$invoice_link}</p>\n<p>{\$signature}</p>";
        $mailTemplate->save();
        return $this;
    }
    protected function createPaymentReversedNotificationEmailTemplate()
    {
        $mailTemplate = new \WHMCS\Mail\Template();
        $mailTemplate->name = "Payment Reversed Notification";
        $mailTemplate->subject = "Payment Reversed Notification";
        $mailTemplate->language = "";
        $mailTemplate->plaintext = false;
        $mailTemplate->custom = false;
        $mailTemplate->type = "admin";
        $mailTemplate->message = "<p>This is a notification that a transaction has been reversed and follow up may be required.</p>\n<p>\n    Transaction ID: {\$transaction_id}<br />\n    Transaction Date: {\$transaction_date}<br />\n    Transaction Amount: {\$transaction_amount}<br />\n    Payment Method: {\$payment_method}<br />\n    Invoice ID: {\$invoice_id}<br />\n</p>\n<p>{\$whmcs_admin_link}</p>";
        $mailTemplate->save();
        return $this;
    }
    protected function createSslConfigurationRequiredEmailTemplate()
    {
        $existingMailTemplate = \WHMCS\Mail\Template::where("name", "=", "SSL Certificate Configuration Required")->count();
        if ($existingMailTemplate == 0) {
            $mailTemplate = new \WHMCS\Mail\Template();
            $mailTemplate->name = "SSL Certificate Configuration Required";
            $mailTemplate->subject = "SSL Certificate Configuration Required";
            $mailTemplate->language = "";
            $mailTemplate->plaintext = false;
            $mailTemplate->custom = false;
            $mailTemplate->type = "product";
            $mailTemplate->message = "<p>Dear {\$client_name},</p>\n<p>Thank you for your order for an SSL Certificate. Before you can use your certificate, it requires configuration which can be done at the URL below.</p>\n<p>{\$ssl_configuration_link}</p>\n<p>Instructions are provided throughout the process but if you experience any problems or have any questions, please open a ticket for assistance.</p>\n<p>{\$signature}</p>";
            $mailTemplate->save();
        }
        return $this;
    }
}

?>