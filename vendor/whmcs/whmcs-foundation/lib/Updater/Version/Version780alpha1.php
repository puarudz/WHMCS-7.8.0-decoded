<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version780alpha1 extends IncrementalVersion
{
    protected $updateActions = array("insertGatewayModuleHooks", "pruneOrphanedSslOrders", "registerRemoveTicketAttachmentsCronTask", "updateLinkInSpamExpertsWelcomeEmail", "removeGooglePlus1", "removeUnusedLegacyModules", "updateCreditCardInvoiceCreatedEmailTemplate");
    public function __construct(\WHMCS\Version\SemanticVersion $version)
    {
        parent::__construct($version);
        $config = \DI::make("config");
        $adminFolder = "admin";
        if (!empty($config["customadminpath"])) {
            $adminFolder = $config["customadminpath"];
        }
        $this->filesToRemove[] = ROOTDIR . DIRECTORY_SEPARATOR . $adminFolder . DIRECTORY_SEPARATOR . "clientsccdetails.php";
        $this->filesToRemove[] = ROOTDIR . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . "gateways" . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . "ewaytokens";
        $this->filesToRemove[] = ROOTDIR . DIRECTORY_SEPARATOR . "modules" . DIRECTORY_SEPARATOR . "gateways" . DIRECTORY_SEPARATOR . "callback" . DIRECTORY_SEPARATOR . "ewaytokens.php";
    }
    public function pruneOrphanedSslOrders()
    {
        $orphanedSslOrderIds = \WHMCS\Database\Capsule::table("tblsslorders")->leftJoin("tblhostingaddons", "tblsslorders.addon_id", "=", "tblhostingaddons.id")->whereNull("tblhostingaddons.id")->where("tblsslorders.addon_id", "!=", 0)->pluck("tblsslorders.id");
        \WHMCS\Database\Capsule::table("tblsslorders")->whereIn("id", $orphanedSslOrderIds)->delete();
        return $this;
    }
    public function registerRemoveTicketAttachmentsCronTask()
    {
        \WHMCS\Cron\Task\AutoPruneTicketAttachments::register();
        return $this;
    }
    public function updateLinkInSpamExpertsWelcomeEmail()
    {
        $emailTemplates = \WHMCS\Mail\Template::where("name", "SpamExperts Welcome Email")->get();
        foreach ($emailTemplates as $emailTemplate) {
            $emailTemplate->message = str_replace("https://my.spamexperts.com/kb/109/Hosted-Cloud-MX-records.html", "https://documentation.solarwindsmsp.com/" . "spamexperts/documentation/Content/B_Admin%20Level/" . "domains/mx-records.htm", $emailTemplate->message);
            $emailTemplate->save();
        }
        return $this;
    }
    public function removeGooglePlus1()
    {
        \WHMCS\Database\Capsule::table("tblconfiguration")->where("setting", "=", "GooglePlus1")->delete();
        return $this;
    }
    public function insertGatewayModuleHooks()
    {
        $stripeActive = \WHMCS\Database\Capsule::table("tblpaymentgateways")->where("gateway", "stripe")->where("setting", "name")->where("value", "!=", "")->count();
        $acceptJsActive = \WHMCS\Database\Capsule::table("tblpaymentgateways")->where("gateway", "acceptjs")->where("setting", "name")->where("value", "!=", "")->count();
        $value = array();
        if ($stripeActive) {
            $value[] = "stripe";
        }
        if ($acceptJsActive) {
            $value[] = "acceptjs";
        }
        $value = implode(",", $value);
        \WHMCS\Database\Capsule::table("tblconfiguration")->insert(array("setting" => "GatewayModuleHooks", "value" => $value, "created_at" => date("Y-m-d H:i:s"), "updated_at" => date("Y-m-d H:i:s")));
        return $this;
    }
    public function getUnusedLegacyModules()
    {
        return array("gateways" => array("camtech", "cyberbit", "imsp", "fasthosts", "ntpnow", "paymex", "payza", "ematters"));
    }
    public function removeUnusedLegacyModules()
    {
        (new \WHMCS\Module\LegacyModuleCleanup())->removeModulesIfInstalledAndUnused($this->getUnusedLegacyModules());
        return $this;
    }
    public function getFeatureHighlights()
    {
        return array(new \WHMCS\Notification\FeatureHighlight("<span>Multiple</span> Pay Methods per Client", "Store more than one credit card", null, "multiple-credit-cards.png", "Store multiple credit cards and bank accounts for a single client and" . " choose the desired payment method on checkout or payment capture.", "https://docs.whmcs.com/Pay_Methods?utm_source=in-product&utm_medium=whatsnew78", "Learn More"), new \WHMCS\Notification\FeatureHighlight("<span>New</span> Server Sync Tool", "Synchronise with cPanel, Plesk and more...", null, "server-sync-tool.png", "Compare and sync records with cPanel, Plesk and DirectAdmin servers to identify" . " and import missing domains, sync usernames &amp; package info and" . " terminate inactive domains.", "https://docs.whmcs.com/Server_Sync?utm_source=in-product&utm_medium=whatsnew78", "Learn More", "configservers.php", "Try it now"), new \WHMCS\Notification\FeatureHighlight("<span>Free</span> Two-Factor Authentication", "Time-Based Tokens now available to all", null, "time-based-tokens.png", "Allow customers and administrative users to secure their account with Time-Based" . " Tokens. No cost to use and now enabled by default for new installations.", "https://docs.whmcs.com/Two-Factor_Authentication?utm_source=in-product&utm_medium=whatsnew78", "Learn More", "configtwofa.php", "Try it now"), new \WHMCS\Notification\FeatureHighlight("<span>New &amp; Updated</span> Module!", "Now with Stripe Elements and 3D-Secure", "stripe-logo.png", "stripe-elements.png", "With Stripe Elements you qualify for the easiest form of PCI compliance saving" . " time and paperwork. Plus with 3D-Secure accept more cards than ever.", "https://docs.whmcs.com/Stripe?utm_source=in-product&utm_medium=whatsnew78", "Learn More"), new \WHMCS\Notification\FeatureHighlight("<span>Improved</span> Date Picker", "A new more powerful date picker experience", null, "date-picker.png", "Select date ranges with ease with a brand new range picker experience, now" . " available in more locations. Plus an improved experience with common presets for date/time fields.", "https://docs.whmcs.com/Date_Range_Picker?utm_source=in-product&utm_medium=whatsnew78", "Learn More"));
    }
    public function updateCreditCardInvoiceCreatedEmailTemplate()
    {
        $emailTemplates = \WHMCS\Mail\Template::where("name", "Credit Card Invoice Created")->get();
        $replacementText = "{if \$invoice_auto_capture_available}\n    Payment will be taken automatically from the {if \$invoice_pay_method_type == \"bankaccount\"}bank account{else}credit card{/if} {\$invoice_pay_method_display_name} on {\$invoice_next_payment_attempt_date}. To change or pay with a different payment method, please login at {\$invoice_link} and click Pay Now, then follow the instructions on screen.\n{else}\n    Payment will not be taken automatically. To pay your invoice, please login at {\$invoice_link} and click Pay Now, then follow the instructions on screen.\n{/if}";
        $searchText = "Payment will be taken automatically on {\$invoice_date_due} from your credit card on record with us. To update or change the credit card details we hold for your account please login at {\$invoice_link} and click Pay Now then following the instructions on screen.";
        foreach ($emailTemplates as $emailTemplate) {
            $message = $emailTemplate->message;
            if (stristr($message, $searchText)) {
                $message = str_replace($searchText, $replacementText, $message);
                $emailTemplate->message = $message;
                $emailTemplate->save();
            }
        }
        return $this;
    }
}

?>