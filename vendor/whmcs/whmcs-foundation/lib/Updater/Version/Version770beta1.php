<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version770beta1 extends IncrementalVersion
{
    protected $updateActions = array("updateSpamExpertsWelcomeEmail", "createCodeGuardWelcomeEmail");
    protected function updateSpamExpertsWelcomeEmail()
    {
        $md5Values = array("140fa66b98e540f904e390cc31f46701", "a0546354f9bf9503c64ea689fafaa7e4", "8a98dc61af3ddfdd7f1c71e110370d11", "007e29c0a671ed16dba92967a63ff3d");
        $newMessage = "<p>Congratulations!</p>\n<p>Your service has been setup and is ready for you to begin using.</p>\n{if \$configuration_required}\n<p><strong>Required Action</strong></p>\n<p>To begin using SpamExperts mail services, you must modify the MX records for your domain to the following:</p>\n<p>\n{foreach from=\$required_mx_records key=mx_host item=mx_priority}\n    {\$mx_host} with a recommended priority of {\$mx_priority}<br />\n{/foreach}\n</p>\n<p>We have guides available for this at <a href=\"https://my.spamexperts.com/kb/109/Hosted-Cloud-MX-records.html\" target=\"_blank\">https://my.spamexperts.com/kb/109/Hosted-Cloud-MX-records.html</a></p>\n{/if}\n{if \$outgoing_service}\n<p><strong>Outgoing Email Filtering</strong></p>\n<p>Outgoing Email Filtering protects your reputation by preventing spam & viruses from leaving\nyour network and working to ensure your IPs are protected from being blacklisted.<br>\nTo begin using it, you will need to login to the SpamExperts Control Panel to create\nthe outgoing user accounts you will use to send email.<br>\nOnce the user accounts are created, you will need to update your email clients to use\nthe new hostname and credentials provided via the SpamExperts Control Panel.<br>\nMore information on how to do this can be found at <a href=\"https://kb.spamexperts.com/36678-getting-started/227750-getting-started-with-outbound\" target=\"_blank\">https://kb.spamexperts.com/36678-getting-started/227750-getting-started-with-outbound</a>\n</p>\n{/if}\n{if \$archiving_service}\n<p><strong>Email Archiving</strong></p>\n<p>Your purchase included Email Archiving which helps to ensure you will never lose an email again.<br>\nEmail archiving has been automatically enabled and you can review and access your email archives\nvia the SpamExperts Control Panel at any time.\n</p>\n{/if}\n<p><strong>Managing your Service</strong></p>\n<p>You can access and manage your email filtering at any time from our client area at {\$whmcs_link}</p>\n<p>Simply login and look for the SpamExperts Manage link on the homepage.</p>\n<p>If you need any further assistance, please contact our <a href=\"{\$whmcs_url}submitticket.php\">support team</a></p>\n<p>{\$signature}</p>";
        $template = \WHMCS\Mail\Template::master()->where("name", "SpamExperts Welcome Email")->first();
        if ($template && in_array(md5($template->message), $md5Values)) {
            $template->message = $newMessage;
            $template->save();
        }
        if (!$template) {
            $template = new \WHMCS\Mail\Template();
            $template->name = "SpamExperts Welcome Email";
            $template->subject = "Welcome to Spam Free Email";
            $template->message = $newMessage;
            $template->custom = false;
            $template->attachments = array();
            $template->type = "product";
            $template->plaintext = false;
            $template->fromEmail = "";
            $template->fromName = "";
            $template->language = "";
            $template->save();
        }
        return $this;
    }
    protected function createCodeGuardWelcomeEmail()
    {
        $mailTemplate = new \WHMCS\Mail\Template();
        $mailTemplate->name = "CodeGuard Welcome Email";
        $mailTemplate->subject = "Welcome to Website Protection";
        $mailTemplate->language = "";
        $mailTemplate->attachments = array();
        $mailTemplate->plaintext = false;
        $mailTemplate->custom = false;
        $mailTemplate->type = "product";
        $mailTemplate->message = "<p>Congratulations!</p>\n<p>Your CodeGuard website backup service has been provisioned successfully and is now ready for use!</p>\n{if \$configuration_required}\n<p>To begin using the service, you will need to login and provide the FTP or SFTP credentials required to access your website. Our guide that demonstrates how to do this can be found at <a href=\"https://codeguard.zendesk.com/hc/en-us/articles/115000610543-How-do-I-back-up-my-website\" target=\"_blank\">https://codeguard.zendesk.com/hc/en-us/articles/115000610543-How-do-I-back-up-my-website</a></p>\n{else}\n<p>We have successfully configured daily backups for your website files and the first backup will be performed shortly. Once that has been performed, backups will be performed daily. If at any time a backup encounters problems, we will notify you by email.</p>\n<p>If you have databases that need to be backed up, CodeGuard will attempt to add them automatically after you have configured your website backup. Alternatively, database backups can be added manually. Our guide for how to do this can be found at <a href=\"https://codeguard.zendesk.com/hc/en-us/articles/115000604663-CodeGuard-Database-Backup-Walkthrough\" target=\"_blank\">https://codeguard.zendesk.com/hc/en-us/articles/115000604663-CodeGuard-Database-Backup-Walkthrough</a></p>\n{/if}\n<p>You can access and manage your website backups at any time from our <a href=\"{\$whmcs_url}\">client area</a>. Simply login and look for the CodeGuard Manage link on the homepage.</p>\n<p>If you need any further assistance, you may contact our <a href=\"{\$whmcs_url}submitticket.php\">support team</a> at any time.</p>\n<p>{\$signature}</p>";
        $mailTemplate->save();
        return $this;
    }
    public function getFeatureHighlights()
    {
        return array(new \WHMCS\Notification\FeatureHighlight("<span>Apps & Integrations</span> Center", "The new way to find and discover modules", null, "apps-and-integrations.png", "Find, discover and start using modules and integrations that can help grow your business and improve your workflows with the new module experience in WHMCS 7.7.", routePath("admin-apps-index"), "Go there now"), new \WHMCS\Notification\FeatureHighlight("<span>Faster</span> Intelligent Search", "New look and added functionality", null, "intelligent-search.png", "Helping you find things faster with a fresh new look and new functionality that hides inactive customers by default and allows you to view additional search results.", "https://go.whmcs.com/1417/intelligent-search", "Learn More"), new \WHMCS\Notification\FeatureHighlight("<span>Drag & Drop</span> Admin Dashboard", "Organise <strong>your</strong> dashboard <strong>for you</strong>", null, "drag-and-drop-widgets.png", "Now you can fully customise your dashboard experience with draggable widgets and display preferences that are synced accross all your devices.", "https://go.whmcs.com/1421/drag-and-drop-dashboard", "Learn More"), new \WHMCS\Notification\FeatureHighlight("<span>CodeGuard</span> Website Backup", "Daily backups with <strong>one-click</strong> restores", null, "codeguard.png", "Offer your customers an independent website backup service with fully automated provisioning and delivery via MarketConnect. Also featuring <strong>WordPress</strong> automatic updates with automated backup and restore on failure.", "https://go.whmcs.com/1425/codeguard-website-backup", "Learn More"), new \WHMCS\Notification\FeatureHighlight("<span>Amazon S3</span> File Storage Support", "Store files using any S3 Compatible Provider", null, "amazon-s3.png", "Store file uploads and attachments in Amazon's industry-leading object storage service for improved reliability and scalability. With two-way migration support to make switching <strong>easy</strong>.", "https://go.whmcs.com/1429/amazon-s3-support", "Learn More"));
    }
}

?>