<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version750rc1 extends IncrementalVersion
{
    protected $updateActions = array("checkForSpacesInAdminUsername", "renameMergeFieldsInUpcomingDomainRenewalNoticeEmailTemplate");
    public function __construct(\WHMCS\Version\SemanticVersion $version)
    {
        parent::__construct($version);
        $this->filesToRemove[] = ROOTDIR . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . "orderforms" . DIRECTORY_SEPARATOR . "standard_cart" . DIRECTORY_SEPARATOR . "domainrenewals.tpl";
        $this->filesToRemove[] = ROOTDIR . DIRECTORY_SEPARATOR . "admin" . DIRECTORY_SEPARATOR . "lang" . DIRECTORY_SEPARATOR . "langupdate.php";
        $this->filesToRemove[] = ROOTDIR . DIRECTORY_SEPARATOR . "lang" . DIRECTORY_SEPARATOR . "langupdate.php";
    }
    protected function checkForSpacesInAdminUsername()
    {
        $hasSpaceCount = \WHMCS\User\Admin::where("username", "like", "% %")->count();
        \WHMCS\Config\Setting::setValue("AdminUserNamesWithSpaces", (int) (bool) $hasSpaceCount);
        return $this;
    }
    protected function renameMergeFieldsInUpcomingDomainRenewalNoticeEmailTemplate()
    {
        $template = \WHMCS\Mail\Template::master()->where("name", "Upcoming Domain Renewal Notice")->first();
        if ($template) {
            $message = $template->message;
            $message = str_replace(array("domain_renewal_link", "domains_manage_link"), array("domain_renewal_url", "domains_manage_url"), $message);
            $template->message = $message;
            $template->save();
        }
        return $this;
    }
    public function getFeatureHighlights()
    {
        return array(new \WHMCS\Notification\FeatureHighlight("Domain Redemption <span>Automation</span>", "Complete domain lifecycle management with grace and redemption fee support", null, "domain-redemption-automation.png", "Setup grace and redemption rules on a per extension basis plus a new and improved client area domain renewal experience.", "https://docs.whmcs.com/Domain_Grace_and_Redemption_Grace_Periods", "Learn More"), new \WHMCS\Notification\FeatureHighlight("PHP <strong>7.1 &amp; 7.2</strong> <span>Support</span>", "Now compatible with PHP 5.6, 7.0, 7.1 and 7.2", null, "php-71-and-72.png", "Run WHMCS on the latest versions of PHP plus check compatibility of modules and custom code ahead of upgrading with our new PHP compatibility checker.", "https://docs.whmcs.com/System_Environment_Guide#PHP", "Learn More"), new \WHMCS\Notification\FeatureHighlight("Sell SiteLock via", "<div class=\"text-center\"><a href=\"https://marketplace.whmcs.com/connect\" target=\"_blank\"><img src=\"images/whatsnew/marketconnect.png\" style=\"margin:0 auto;\"></a></div>", null, "marketconnect-sitelock.png", "Offer Leading Website Security & Malware protection to your customers with the new SiteLock integration.", "https://marketplace.whmcs.com/connect/sitelock", "Learn More"), new \WHMCS\Notification\FeatureHighlight("Staff <span>@mentions</span>", "Allowing you to tag and notify other administrative users", null, "staff-mentions.png", "Provides a quick and easy way to alert other members of your team with familiar @mention syntax in client and ticket notes.", "https://docs.whmcs.com/Staff_Mentions", "Learn More"), new \WHMCS\Notification\FeatureHighlight("Marketing Emails <span>Consent</span>", "More flexibility, control and tracking of marketing email consent", null, "marketing-consent.png", "A range of new features including a choice of opt-in or opt-out during registration or checkout, logging of consent history, new email merge fields and more.", "https://docs.whmcs.com/Marketing_Emails_Automation", "Learn More"));
    }
}

?>