<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Help;

class HelpController
{
    use \WHMCS\Application\Support\Controller\DelegationTrait;
    protected function getLicense()
    {
        return \DI::make("license");
    }
    public function forceLicenseCheck(\WHMCS\Http\Message\ServerRequest $request)
    {
        try {
            $licensing = $this->getLicense();
            $licensing->forceRemoteCheck();
            if ($licensing->getStatus() != "Active") {
                redir("status=" . $licensing->getStatus(), \WHMCS\Utility\Environment\WebHelper::getAdminBaseUrl() . "/licenseerror.php");
            }
        } catch (\WHMCS\Exception\Http\ConnectionError $e) {
            redir("status=noconnection", \WHMCS\Utility\Environment\WebHelper::getAdminBaseUrl() . "/licenseerror.php");
        } catch (\WHMCS\Exception $e) {
            \WHMCS\Session::setAndRelease("licenseCheckError", $e->getMessage());
            redir("", \WHMCS\Utility\Environment\WebHelper::getAdminBaseUrl() . "/licenseerror.php");
        }
        $request = $request->withAttribute("success", true)->withMethod("GET");
        return $this->delegateTo("admin-help-license", $request);
    }
    public function sendLicenseUpgradeRequest(\WHMCS\Http\Message\ServerRequest $request)
    {
        $licensing = $this->getLicense();
        return new \WHMCS\Http\Message\JsonResponse(array("success" => $licensing->makeUpgradeCall()));
    }
    public function fetchLicenseUpgradeData(\WHMCS\Http\Message\ServerRequest $request)
    {
        $licensing = $this->getLicense();
        return new \WHMCS\Http\Message\JsonResponse(array("license_key" => $licensing->getLicenseKey(), "member_data" => $licensing->getEncryptedMemberData()));
    }
    public function viewLicense(\WHMCS\Http\Message\ServerRequest $request)
    {
        ob_start();
        $licensing = $this->getLicense();
        $successHtml = "";
        if ($request->get("success")) {
            $successHtml = "<div class=\"alert alert-success text-center\" role=\"alert\">" . "<i class=\"fas fa-check-circle fa-fw\"></i> " . "<strong>" . \AdminLang::trans("global.success") . "</strong> " . \AdminLang::trans("license.forceLicenseUpdateSuccess") . "</div>";
        }
        $clientLimitNotificationHtml = "";
        $clientLimitNotification = $licensing->getClientLimitNotificationAttributes();
        if (!is_null($clientLimitNotification)) {
            $clientLimitNotificationHtml = "\n<div class=\"panel panel-" . $clientLimitNotification["class"] . " client-limit-notification-form\" style=\"margin:20px auto;max-width:80%;\">\n    <div class=\"panel-heading\">\n        <h3 class=\"panel-title\">\n            <i class=\"fas fa-fw " . $clientLimitNotification["icon"] . "\"></i>\n            &nbsp;\n            " . $clientLimitNotification["title"] . "\n        </h3>\n    </div>\n    <div class=\"panel-body\">\n        <p>" . $clientLimitNotification["body"] . "</p>\n        <form method=\"post\" action=\"" . $clientLimitNotification["upgradeUrl"] . "\" target=\"_blank\" data-fetch-url=\"" . routePath("admin-help-license-upgrade-data") . "\">\n            <input type=\"hidden\" name=\"getupgradedata\" value=\"1\">\n            <input type=\"hidden\" name=\"license_key\" value=\"\" class=\"input-license-key\">\n            <input type=\"hidden\" name=\"member_data\" value=\"\" class=\"input-member-data\">\n            <div class=\"links\">\n                " . (!$clientLimitNotification["autoUpgradeEnabled"] ? "<button type=\"submit\" class=\"btn btn-sm btn-" . $clientLimitNotification["class"] . "\" >Upgrade Now</button>" : "") . "\n                " . ($clientLimitNotification["learnMoreUrl"] ? "<a href=\"" . $clientLimitNotification["learnMoreUrl"] . "\" class=\"btn btn-sm " . ($clientLimitNotification["autoUpgradeEnabled"] ? "btn-" . $clientLimitNotification["class"] : "btn-link") . "\" target=\"_blank\">Learn more &raquo;</a>" : "") . "\n            </div>\n        </form>\n    </div>\n</div>\n";
        }
        echo $successHtml;
        echo "\n        <div class=\"well text-center\">\n            <p>";
        echo \AdminLang::trans("license.forceLicenseUpdateDescription");
        echo "</p>\n            <form method=\"post\" action=\"";
        echo routePath("admin-help-license-check");
        echo "\">\n                <button type=\"submit\" class=\"btn btn-danger\">\n                    ";
        echo \AdminLang::trans("license.forceLicenseUpdate");
        echo "                </button>\n            </form>\n        </div>\n\n        <div class=\"margin-top-bottom-20\">\n            <table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n                <tr><td width=\"20%\" class=\"fieldlabel\">";
        echo \AdminLang::trans("license.regto");
        echo "</td><td class=\"fieldarea\">";
        echo $licensing->getKeyData("registeredname");
        echo "</td></tr>\n                <tr><td class=\"fieldlabel\">";
        echo \AdminLang::trans("license.key");
        echo "</td><td class=\"fieldarea\">";
        echo \App::get_license_key();
        echo "</td></tr>\n                <tr><td class=\"fieldlabel\">";
        echo \AdminLang::trans("license.type");
        echo "</td><td class=\"fieldarea\">";
        echo $licensing->getKeyData("productname");
        if ($licensing->isClientLimitsEnabled()) {
            echo " (" . $licensing->getTextClientLimit() . ")";
        }
        echo "</td></tr>\n                ";
        if ($licensing->isClientLimitsEnabled()) {
            echo "                    <tr><td class=\"fieldlabel\">Active Client Count / Limit</td><td class=\"fieldarea\">";
            echo $licensing->getNumberOfActiveClients() . " / " . $licensing->getTextClientLimit();
            echo "</td></tr>\n                    ";
            if ($clientLimitNotificationHtml) {
                echo "<tr><td class=\"fieldlabel\">License Notice</td><td class=\"fieldarea\">";
                echo $clientLimitNotificationHtml;
                echo "</td></tr>";
            }
            echo "                ";
        }
        echo "                <tr><td class=\"fieldlabel\">";
        echo \AdminLang::trans("license.validdomain");
        echo "</td><td class=\"fieldarea\">";
        echo str_replace(",", ", ", $licensing->getKeyData("validdomains"));
        echo "</td></tr>\n                <tr><td class=\"fieldlabel\">";
        echo \AdminLang::trans("license.validip");
        echo "</td><td class=\"fieldarea\">";
        echo str_replace(",", ", ", $licensing->getKeyData("validips"));
        echo "</td></tr>\n                <tr><td class=\"fieldlabel\">";
        echo \AdminLang::trans("license.validdir");
        echo "</td><td class=\"fieldarea\">";
        echo str_replace(",", ", ", $licensing->getKeyData("validdirs"));
        echo "</td></tr>\n                <tr><td class=\"fieldlabel\">";
        echo \AdminLang::trans("license.brandingremoval");
        echo "</td><td class=\"fieldarea\">";
        echo $licensing->getBrandingRemoval() ? "Yes" : "No";
        echo "</td></tr>\n                <tr><td class=\"fieldlabel\">";
        echo \AdminLang::trans("license.addons");
        echo "</td><td class=\"fieldarea\">";
        echo count($licensing->getActiveAddons()) ? implode("<br />", $licensing->getActiveAddons()) : "None";
        echo "</td></tr>\n                <tr><td class=\"fieldlabel\">";
        echo \AdminLang::trans("license.created");
        echo "</td><td class=\"fieldarea\">";
        echo date("l, jS F Y", strtotime($licensing->getKeyData("regdate")));
        echo "</td></tr>\n                <tr><td class=\"fieldlabel\">";
        echo \AdminLang::trans("license.expires");
        echo "</td><td class=\"fieldarea\">";
        echo $licensing->getExpiryDate(true);
        echo "</td></tr>\n            </table>\n        </div>\n\n        <div class=\"alert alert-info\">\n            <i class=\"fas fa-info-circle\"></i>\n            &nbsp;";
        echo \AdminLang::trans("license.reissue1");
        echo "            &nbsp;<a href=\"https://docs.whmcs.com/Licensing\" target=\"_blank\" class=\"alert-link\">\n                https://docs.whmcs.com/Licensing\n            </a>\n            &nbsp;";
        echo \AdminLang::trans("license.reissue2");
        echo "        </div>\n\n        ";
        $body = ob_get_clean();
        return (new \WHMCS\Admin\ApplicationSupport\View\Html\Smarty\BodyContentWrapper())->setTitle(\AdminLang::trans("license.title"))->setSidebarName("help")->setFavicon("support")->setBodyContent($body);
    }
}

?>