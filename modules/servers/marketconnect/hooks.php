<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

add_hook("ClientAreaPrimarySidebar", -1, function (WHMCS\View\Menu\Item $sidebar) {
    if (!$sidebar->getChild("Service Details Actions")) {
        return NULL;
    }
    $service = Menu::context("service");
    if ($service instanceof WHMCS\Service\Service && $service->product->module != "marketconnect") {
        return NULL;
    }
    $serviceId = $service->id;
    $type = WHMCS\MarketConnect\Provision::factoryFromModel($service)->getServiceType();
    if (in_array($type, array("rapidssl", "geotrust", "symantec"))) {
        $sslOrder = WHMCS\Service\Ssl::where("serviceid", "=", $serviceId)->where("addon_id", "=", 0)->first();
        if ($sslOrder && $sslOrder->status == "Awaiting Configuration") {
            $sidebar->getChild("Service Details Actions")->addChild("Configure SSL", array("uri" => "configuressl.php?cert=" . md5($sslOrder->id), "label" => Lang::trans("sslconfigurenow"), "order" => 1));
        }
        $sidebarActions = array("client_change_approver_email" => "ssl.changeApproverEmail", "client_retrieve_certificate" => "ssl.retrieveCertificate", "client_reissue_certificate" => "ssl.reissueCertificate");
        $i = 1;
        foreach ($sidebarActions as $a => $languageString) {
            $text = Lang::trans($languageString);
            $postUri = WHMCS\Utility\Environment\WebHelper::getBaseUrl() . DIRECTORY_SEPARATOR . "clientarea.php";
            $bodyHtml = "<form method=\"post\" action=\"" . $postUri . "\">\n    <input type=\"hidden\" name=\"action\" value=\"productdetails\" />\n    <input type=\"hidden\" name=\"modop\" value=\"custom\" />\n    <input type=\"hidden\" name=\"a\" value=\"" . $a . "\" />\n    <input type=\"hidden\" name=\"id\" value=\"" . $serviceId . "\" />\n    <span class=\"btn-sidebar-form-submit\">\n        <span class=\"loading hidden\">\n            <i class=\"fas fa-spinner fa-spin\"></i>\n        </span>\n        <span class=\"text\">" . $text . "</span>\n    </span>\n    <span class=\"error-feedback\"></span>\n</form>";
            $sidebar->getChild("Service Details Actions")->addChild($a, array("uri" => "#", "label" => $bodyHtml, "order" => $i, "disabled" => !$sslOrder || $service->domainStatus != "Active", "attributes" => array("class" => "btn-sidebar-form-submit")));
            $i++;
        }
        return NULL;
    } else {
        $manageText = Lang::trans("manage");
        $bodyHtml = "<form>\n    <input type=\"hidden\" name=\"modop\" value=\"custom\" />\n    <input type=\"hidden\" name=\"a\" value=\"manage_order\" />\n    <input type=\"hidden\" name=\"id\" value=\"" . $serviceId . "\" />\n    <span class=\"btn-service-sso\">\n        <span class=\"loading hidden\">\n            <i class=\"fas fa-spinner fa-spin\"></i>\n        </span>\n        <span class=\"text\">" . $manageText . "</span>\n    </span>\n    <span class=\"login-feedback\"></span>\n</form>";
        $sidebar->getChild("Service Details Actions")->addChild("Manage", array("uri" => "#", "label" => $bodyHtml, "order" => 1, "attributes" => array("class" => "btn-service-sso"), "disabled" => $service->domainStatus != "Active"));
        if ($type == "weebly") {
            $disabled = false;
            $lastWeebly = WHMCS\Product\Product::weebly()->visible()->orderBy("order", "desc")->first();
            if ($service->domainStatus != "Active" || !$lastWeebly || $lastWeebly && $lastWeebly->moduleConfigOption1 == $service->product->moduleConfigOption1) {
                $disabled = true;
            }
            $uri = routePath("store-weebly-upgrade");
            $upgradeText = Lang::trans("upgrade");
            $formClass = $disabled ? " class=\"disabled\"" : "";
            $bodyHtml = "<form action=\"" . $uri . "\" method=\"post\"" . $formClass . ">\n    <input type=\"hidden\" name=\"serviceid\" value=\"" . $serviceId . "\" />\n    <input type=\"hidden\" name=\"addonId\" value=\"0\" />\n    <span class=\"btn-sidebar-form-submit\">\n        <span class=\"loading hidden\">\n            <i class=\"fas fa-spinner fa-spin\"></i>\n        </span>\n        <span class=\"text\">" . $upgradeText . "</span>\n    </span>\n</form>";
            $sidebar->getChild("Service Details Actions")->addChild("Upgrade Weebly", array("uri" => "#", "label" => $bodyHtml, "order" => 2, "disabled" => $disabled, "attributes" => array("class" => "btn-sidebar-form-submit")));
        }
    }
});

?>