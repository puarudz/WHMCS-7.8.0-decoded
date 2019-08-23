<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Cron\Task;

class LicenseNotice extends \WHMCS\Scheduling\Task\AbstractTask
{
    protected $accessLevel = \WHMCS\Scheduling\Task\TaskInterface::ACCESS_SYSTEM;
    protected $defaultPriority = 700;
    protected $defaultFrequency = 1440;
    protected $defaultDescription = "Upgrade license on request or send notification alerts";
    protected $defaultName = "License Usage & Notification";
    protected $systemName = "LicenseNotice";
    protected $outputs = array();
    public function __invoke()
    {
        $whmcs = \DI::make("app");
        $license = \DI::make("license");
        if ($license->isClientLimitsEnabled() && $license->isNearClientLimit()) {
            $clientLimit = $license->getClientLimit();
            $numClients = $license->getNumberOfActiveClients();
            $helpLink = $this->getAdminHelpLink();
            if ($numClients < $clientLimit) {
                $subject = "License Limit Near";
                $message = "<p>You are nearing the maximum number of clients" . " permitted by your current license. Upgrade now to avoid" . " any interruptions in service.</p><p>Current Client Limit: " . $license->getTextClientLimit() . "<br />Total Number of Active Clients: " . $license->getTextNumberOfActiveClients() . "</p><p>For more information, visit <a href=\"" . $helpLink . "\">" . $helpLink . "</a></p>";
            } else {
                if ($clientLimit == $license->getNumberOfActiveClients()) {
                    $subject = "License Limit Reached";
                    $message = "<p>You have reached the number of clients permitted" . " by your current license. Upgrade now to avoid any" . " interruptions in service.</p><p>Current Client Limit: " . $license->getTextClientLimit() . "<br />Total Number of Active Clients: " . $license->getTextNumberOfActiveClients() . "</p><p>For more information, visit <a href=\"" . $helpLink . "\">" . $helpLink . "</a></p>";
                } else {
                    $subject = "License Limit Exceeded - Urgent Action Required";
                    $message = "<p>You have exceeded the number of clients permitted" . " by your current license. Upgrade now to continue to have" . " full access to manage your clients.</p><p>Current Client Limit: " . $license->getTextClientLimit() . "<br />Total Number of Active Clients: " . $license->getTextNumberOfActiveClients() . "</p><p>For more information, visit <a href=\"" . $helpLink . "\">" . $helpLink . "</a></p>";
                }
            }
            sendAdminNotification("system", $subject, $message);
        }
        return $this;
    }
    public function getAdminHelpLink()
    {
        $fqdnPath = fqdnRoutePath("admin-help-license");
        return $fqdnPath;
    }
}

?>