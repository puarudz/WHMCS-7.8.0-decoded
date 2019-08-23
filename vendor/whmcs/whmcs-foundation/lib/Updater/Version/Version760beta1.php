<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Updater\Version;

class Version760Beta1 extends IncrementalVersion
{
    public function getFeatureHighlights()
    {
        return array(new \WHMCS\Notification\FeatureHighlight("Domain <span>Namespinning</span>", "More relevant and higher quality suggestions", null, "improved-domain-lookup.png", "Increase domain sales with more relevant &amp; higher quality results, faster lookup times and multi-language support.<br>Available <strong>free</strong> to all WHMCS users.", "http://go.whmcs.com/1369/domain-namespinning", "Learn More"), new \WHMCS\Notification\FeatureHighlight("2Checkout <span>Inline Checkout</span>", "Accept payments without leaving WHMCS", null, "2checkout-inline.png", "2Checkout's new Inline Checkout provides a more streamlined checkout experience for customers enabling payment by credit card without ever leaving your site.", "http://go.whmcs.com/1373/2checkout-inline-checkout", "Learn More"), new \WHMCS\Notification\FeatureHighlight("New &amp; Improved <span>UX</span>", "<div class=\"text-center\"><a href=\"https://marketplace.whmcs.com/connect\" target=\"_blank\"><img src=\"images/whatsnew/marketconnect.png\" style=\"margin:0 auto;\"></a></div>", null, "marketconnect-upsells.png", "A streamlined customer experience, new promotional content, improved visual display plus improved multi-language support.", "http://go.whmcs.com/1377/marketconnect-ux-improvements", "Learn More"), new \WHMCS\Notification\FeatureHighlight("Updated MaxMind <span>Integration</span>", "Now with Insights and Factors Support", null, "maxmind-updated.png", "Updated integration with MaxMind's latest API gives you access to more fraud analysis metrics, custom rules and a new and improved fraud risk overview.", "http://go.whmcs.com/1381/maxmind-updated-integration", "Learn More"), new \WHMCS\Notification\FeatureHighlight("New Weebly <span>Plans</span>", "Introducing Weebly <strong>Lite</strong> and <strong>Performance</strong>", null, "weebly-lite.png", "A new entry level Lite plan allows you to offer Weebly from just \$1.99/mo (RRP). Plus the Performance plan for Power Sellers.", "http://go.whmcs.com/1385/new-weebly-plans", "Learn More"));
    }
}

?>