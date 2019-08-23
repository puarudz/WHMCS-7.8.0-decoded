<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\MarketConnect\Promotion\Service;

class SpamExperts extends AbstractService
{
    protected $name = "spamexperts";
    protected $friendlyName = "SpamExperts";
    protected $primaryIcon = "assets/img/marketconnect/spamexperts/logo.png";
    protected $primaryLandingPageRouteName = "store-emailservices-index";
    protected $productKeys = array("spamexperts_incoming", "spamexperts_outgoing", "spamexperts_incomingoutgoing", "spamexperts_incomingarchiving", "spamexperts_outgoingarchiving", "spamexperts_incomingoutgoingarchiving");
    protected $qualifyingProductTypes = NULL;
    protected $loginPanel = array("label" => "Manage Your Email", "icon" => "fas fa-envelope-open", "image" => "assets/img/marketconnect/spamexperts/logo.png", "color" => "teal");
    protected $upsells = array("spamexperts_incoming" => array("spamexperts_incomingoutgoing"), "spamexperts_outgoing" => array("spamexperts_incomingoutgoing"), "spamexperts_incomingoutgoing" => array("spamexperts_incomingoutgoingarchiving"), "spamexperts_incomingarchiving" => array("spamexperts_incomingoutgoingarchiving"), "spamexperts_outgoingarchiving" => array("spamexperts_incomingoutgoingarchiving"));
    protected $upsellPromoContent = array("spamexperts_incomingoutgoing" => array("imagePath" => "assets/img/marketconnect/spamexperts/logo.png", "headline" => "Add Outgoing Protection", "tagline" => "For complete peace of mind", "features" => array("Inbound and outbound protection", "Protect the reputation of your brand", "Increase outbound email continuity", "Improve email deliverability"), "learnMoreRoute" => "store-emailservices-index", "cta" => "Upgrade to"), "spamexperts_incomingoutgoingarchiving" => array("imagePath" => "assets/img/marketconnect/spamexperts/logo.png", "headline" => "Add Email Archiving", "tagline" => "Complete protection against loss", "features" => array("Full suite of email protection", "Inbound and outbound protection", "Secure backups of all email activity", "Improved email continuity and deliverability"), "learnMoreRoute" => "store-emailservices-index", "cta" => "Upgrade to"));
    protected $defaultPromotionalContent = array("imagePath" => "assets/img/marketconnect/icons/spamexperts.png", "headline" => "Say goodbye to Spam", "tagline" => "Full email security solution", "features" => array("Near 100% filtering accuracy", "Increased email continuity & redundancy", "Easy setup and configuration", "Supports up to 1000 email boxes"), "learnMoreRoute" => "store-emailservices-index", "cta" => "Add", "ctaRoute" => "store-emailservices-index");
    protected $planFeatures = array("spamexperts_incomingarchiving" => array("Easy setup &amp; configuration" => true, "Virus Protection" => true, "Malware Protection" => true, "Spam Protection" => true, "Scans incoming mail" => true, "Secure email archiving" => true), "spamexperts_outgoingarchiving" => array("Easy setup &amp; configuration" => true, "Virus Protection" => true, "Malware Protection" => true, "Spam Protection" => true, "Scans outgoing mail" => true, "Secure email archiving" => true), "spamexperts_incomingoutgoing" => array("Easy setup &amp; configuration" => true, "Virus, Malware and Spam Protection" => true, "Scans all incoming mail" => true, "Increased continuity & redundancy" => true, "Compatible with any mail server" => true, "Scans incoming mail" => true, "Scans outgoing mail" => true), "spamexperts_incomingoutgoingarchiving" => array("Easy setup &amp; configuration" => true, "Virus, Malware and Spam Protection" => true, "Scans all incoming mail" => true, "Increased continuity & redundancy" => true, "Compatible with any mail server" => true, "Scans incoming mail" => true, "Scans outgoing mail" => true, "Secure email archiving" => true));
    protected $recommendedUpgradePaths = array("spamexperts_incoming" => "spamexperts_incomingoutgoing", "spamexperts_outgoing" => "spamexperts_incomingoutgoing", "spamexperts_incomingoutgoing" => "spamexperts_incomingoutgoingarchiving", "spamexperts_incomingarchiving" => "spamexperts_incomingoutgoingarchiving", "spamexperts_outgoingarchiving" => "spamexperts_incomingoutgoingarchiving");
    public function getFeaturesForUpgrade($key)
    {
        if (in_array($key, array("spamexperts_incoming", "spamexperts_outgoing", "spamexperts_archiving"))) {
            return null;
        }
        return $this->planFeatures[$key];
    }
}

?>