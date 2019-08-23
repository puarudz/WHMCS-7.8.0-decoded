<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\ApplicationSupport\View\Traits;

abstract class NotificationTrait
{
    protected $topBarNotification = array();
    protected $clientLimitNotification = array();
    protected $globalWarningNotification = array();
    public abstract function getAdminUser();
    public function getTopBarNotification()
    {
        $notifications = $this->topBarNotification;
        $admin = $this->getAdminUser();
        if (\App::isUpdateAvailable() && $admin->hasPermission("Update WHMCS")) {
            $notifications[] = "<a href=\"update.php\" class=\"update-now\">" . \AdminLang::trans("update.updateNow") . "</a>";
        }
        return implode("\n", $notifications);
    }
    public function getClientLimitNotification()
    {
        $notifications = $this->clientLimitNotification;
        $admin = $this->getAdminUser();
        if (!$admin->exists || $admin->roleId != 1) {
            return array();
        }
        $clientLimitNotification = \DI::make("license")->getClientLimitNotificationAttributes();
        if ($this->isClientLimitNotificationDismissed($clientLimitNotification["title"])) {
            return array();
        }
        return $notifications;
    }
    public function getGlobalWarningNotification()
    {
        $notification = "";
        $admin = $this->getAdminUser();
        if ($admin && $admin->hasPermission("Configure General Settings")) {
            $globalWarningHelper = new \WHMCS\Admin\ApplicationSupport\View\Html\Helper\GlobalWarning();
            $notification = $globalWarningHelper->getNotifications();
        }
        return $notification;
    }
    public function addTopBarNotification($notification)
    {
        $this->topBarNotification[] = $notification;
        return $this;
    }
    public function addClientLimitNotification($clientLimitNotification)
    {
        $this->clientLimitNotification[] = $clientLimitNotification;
        return $this;
    }
    public function addGlobalWarningNotification($globalWarningNotification)
    {
        $this->globalWarningNotification[] = $globalWarningNotification;
        return $this;
    }
    protected function getClientLimitNotificationDismisses()
    {
        $dismisses = \WHMCS\Config\Setting::getValue("ClientLimitNotificationDismisses");
        return json_decode($dismisses, true);
    }
    protected function isClientLimitNotificationDismissed($title)
    {
        $titleParts = explode(" ", $title);
        if (\WHMCS\Session::get("ClientLimitNotificationDismissed" . implode($titleParts))) {
            return true;
        }
        $licensing = \DI::make("license");
        $dismisses = $this->getClientLimitNotificationDismisses();
        if (isset($dismisses[$title][$licensing->getClientLimit()]) && is_array($dismisses[$title][$licensing->getClientLimit()]) && in_array($this->getAdminUser()->id, $dismisses[$title][$licensing->getClientLimit()])) {
            return true;
        }
        return false;
    }
    public function getNotificationJavascript()
    {
        $clientLimitNotification = $this->getClientLimitNotification();
        $js = array();
        if (isset($clientLimitNotification["clientLimitNotification"]["attemptUpgrade"])) {
            $js[] = "function licenseUpgradeFailed() {\n                    \$(\".client-limit-notification-form\")\n                        .find(\".panel-title i\").removeClass(\"fa-spinner\").removeClass(\"fa-spin\").addClass(\"fa-times\").end()\n                        .find(\".panel-body p:first-child\").html(\"The automatic upgrade attempt has failed. Please click the Upgrade button below to complete your upgrade.\").end()\n                        .find(\".panel-body .btn\").addClass(\"btn-link\").removeClass(\"btn-warning\");\n                    \$(\"#btnClientLimitNotificationUpgrade\").addClass(\"btn-warning\").removeClass(\"btn-link\").removeClass(\"hidden\");\n                }";
        }
        return $js;
    }
    public function getNotificationJquery()
    {
        $jquery = array();
        $clientLimitNotification = $this->getClientLimitNotification();
        if (isset($clientLimitNotification["clientLimitNotification"]["attemptUpgrade"])) {
            $jquery[] = "WHMCS.http.jqClient.post(\"" . routePath("admin-help-license-upgrade-send") . "\", \$(\".client-limit-notification-form form\").serialize(),\n                function(data) {\n                    if (data.success) {\n                        \$(\".client-limit-notification-form\").addClass(\"panel-success\").removeClass(\"panel-warning\")\n                            .find(\".panel-title i\").removeClass(\"fa-spinner\").removeClass(\"fa-spin\").addClass(\"fa-check\").end()\n                            .find(\".panel-title small\").fadeOut(\"fast\").end()\n                            .find(\".panel-title span\").html(\"Client Limit Upgraded\").end()\n                            .find(\".panel-body p:first-child\").html(\"You have been automatically upgraded to the next license tier. The new price will take effect from your next renewal invoice.\").end()\n                            .find(\".panel-body .btn\").addClass(\"btn-success\").removeClass(\"btn-warning\");\n                    } else {\n                        licenseUpgradeFailed();\n                    }\n                }, \"json\")\n                .fail(function(data) {\n                    licenseUpgradeFailed();\n                });";
        }
        return $jquery;
    }
}

?>