<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Controller;

class HomepageController
{
    public static function assertCurl()
    {
        if (!function_exists("curl_init")) {
            echo "<div style=\"border: 1px dashed #cc0000;font-family:Tahoma,sans-serif;background-color:#FBEEEB;width:100%;padding:10px;color:#cc0000;\"><strong>Critical Error</strong><br>CURL is not installed or is disabled on your server and it is required for WHMCS to run</div>";
            exit;
        }
    }
    public function refreshWidget(\WHMCS\Http\Message\ServerRequest $request)
    {
        $aInt = new \WHMCS\Admin("Main Homepage");
        $aInt->title = \AdminLang::trans("global.hometitle");
        $aInt->sidebar = "home";
        $aInt->icon = "home";
        $aInt->requiredFiles(array("clientfunctions", "invoicefunctions", "gatewayfunctions", "ccfunctions", "processinvoices", "reportfunctions"));
        $aInt->template = "homepage";
        try {
            $widgetInterface = new \WHMCS\Module\Widget();
            $widget = $widgetInterface->getWidgetByName(\App::getFromRequest("widget"));
            $refresh = (bool) $request->get("refresh");
            $widgetOutput = $widget->render($refresh);
            $js = "";
            foreach ($aInt->getChartFunctions() as $func) {
                if (strpos($widgetOutput, $func) !== false) {
                    $js .= $func . "();";
                }
            }
            if (!empty($js)) {
                $js = "<script>" . $js . "</script>";
            }
            return new \WHMCS\Http\Message\JsonResponse(array("success" => true, "widgetOutput" => $widgetOutput . $js));
        } catch (\Exception $e) {
            new \WHMCS\Http\Message\JsonResponse(array("success" => false, "exceptionMsg" => $e->getMessage()));
        }
        return $aInt;
    }
    public function saveNotes(\WHMCS\Http\Message\ServerRequest $request)
    {
        $notes = $request->get("notes");
        update_query("tbladmins", array("notes" => $notes), array("id" => \WHMCS\Session::get("adminid")));
        return new \WHMCS\Http\Message\JsonResponse(array("status" => "success"));
    }
    public function toggleWidgetDisplay(\WHMCS\Http\Message\ServerRequest $request)
    {
        $widget = $request->get("widget");
        try {
            $session = new \WHMCS\Session();
            $session->create(\WHMCS\Config\Setting::getValue("InstanceID"));
            $adminUser = \WHMCS\User\Admin::find((int) \WHMCS\Session::get("adminid"));
            $currentWidgets = $adminUser->hiddenWidgets;
            if (!in_array($widget, $currentWidgets)) {
                $currentWidgets[] = $widget;
            } else {
                $currentWidgets = array_flip($currentWidgets);
                unset($currentWidgets[$widget]);
                $currentWidgets = array_flip($currentWidgets);
            }
            $adminUser->hiddenWidgets = $currentWidgets;
            $adminUser->save();
            return new \WHMCS\Http\Message\JsonResponse(array("success" => true));
        } catch (\Exception $e) {
            return new \WHMCS\Http\Message\JsonResponse(array("success" => false, "widget" => $widget));
        }
    }
    public function index(\WHMCS\Http\Message\ServerRequest $request)
    {
        self::assertCurl();
        $licensing = \DI::make("license");
        if (!$licensing->checkOwnedUpdates()) {
            redir("status=version", "licenseerror.php");
        }
        if (!checkPermission("Main Homepage", true)) {
            redir("", "supportcenter.php");
        }
        $aInt = new \WHMCS\Admin("Main Homepage");
        $aInt->setResponseType(\WHMCS\Admin::RESPONSE_HTML_MESSAGE);
        $session = new \WHMCS\Session();
        $session->create(\WHMCS\Config\Setting::getValue("InstanceID"));
        $aInt->title = \AdminLang::trans("global.hometitle");
        $aInt->sidebar = "home";
        $aInt->icon = "home";
        $aInt->requiredFiles(array("clientfunctions", "invoicefunctions", "gatewayfunctions", "ccfunctions", "processinvoices", "reportfunctions"));
        $aInt->template = "homepage";
        $whmcs = \App::self();
        if ($request->get("createinvoices") || $request->get("generateinvoices")) {
            check_token("WHMCS.admin.default");
            checkPermission("Generate Due Invoices");
            $noemails = $request->get("noemails");
            global $invoicecount;
            createInvoices("", $noemails);
            redir("generatedinvoices=1&count=" . $invoicecount);
        }
        if ($request->get("generatedinvoices")) {
            infoBox(\AdminLang::trans("invoices.gencomplete"), (int) $request->get("count") . " Invoices Created");
        }
        if ($request->get("attemptccpayments")) {
            check_token("WHMCS.admin.default");
            checkPermission("Attempts CC Captures");
            \WHMCS\Session::set("AdminHomeCCCaptureResultMsg", ccProcessing());
            redir("attemptedccpayments=1");
        }
        if ($request->get("attemptedccpayments") && \WHMCS\Session::get("AdminHomeCCCaptureResultMsg")) {
            infoBox(\AdminLang::trans("invoices.attemptcccapturessuccess"), \WHMCS\Session::get("AdminHomeCCCaptureResultMsg"));
            \WHMCS\Session::delete("AdminHomeCCCaptureResultMsg");
        }
        $updater = new \WHMCS\Installer\Update\Updater();
        $templatevars["licenseinfo"] = array("registeredname" => $licensing->getRegisteredName(), "productname" => $licensing->getProductName(), "expires" => $licensing->getExpiryDate(), "currentversion" => $whmcs->getVersion()->getCasual(), "latestversion" => $updater->getLatestVersion()->getCasual(), "updateavailable" => $updater->isUpdateAvailable() && $aInt->hasPermission("Update WHMCS"));
        if ($licensing->getKeyData("productname") == "15 Day Free Trial") {
            $templatevars["freetrial"] = true;
        }
        $templatevars["infobox"] = isset($infobox) ? $infobox : "";
        $query = "SELECT COUNT(*) FROM tblpaymentgateways WHERE setting='type' AND value='CC'";
        $result = full_query($query);
        $data = mysql_fetch_array($result);
        if ($data[0]) {
            $templatevars["showattemptccbutton"] = true;
        }
        if (\WHMCS\Config\Setting::getValue("MaintenanceMode")) {
            $templatevars["maintenancemode"] = true;
        }
        $allWidgets = (new \WHMCS\Module\Widget())->getAllWidgets();
        $templatevars["widgets"] = $allWidgets;
        $staticWidgets = array();
        foreach ($allWidgets as $key => $widget) {
            if (!$widget->isDraggable()) {
                $staticWidgets[] = $widget;
                unset($allWidgets[$key]);
            }
        }
        ksort($allWidgets);
        $templatevars["sortableWidgets"] = $allWidgets;
        $templatevars["staticWidgets"] = $staticWidgets;
        $adminUser = \WHMCS\User\Admin::find((int) \WHMCS\Session::get("adminid"));
        $templatevars["hiddenWidgets"] = $adminUser->hiddenWidgets;
        $templatevars["generateInvoices"] = $aInt->modal("GenerateInvoices", \AdminLang::trans("invoices.geninvoices"), \AdminLang::trans("invoices.geninvoicessendemails"), array(array("title" => \AdminLang::trans("global.yes"), "onclick" => "window.location=\"index.php?generateinvoices=true" . generate_token("link") . "\"", "class" => "btn-primary"), array("title" => \AdminLang::trans("global.no"), "onclick" => "window.location=\"index.php?generateinvoices=true&noemails=true" . generate_token("link") . "\"")));
        $templatevars["creditCardCapture"] = $aInt->modal("CreditCardCapture", \AdminLang::trans("invoices.attemptcccaptures"), \AdminLang::trans("invoices.attemptcccapturessure"), array(array("title" => \AdminLang::trans("global.yes"), "onclick" => "window.location=\"index.php?attemptccpayments=true" . generate_token("link") . "\"", "class" => "btn-primary"), array("title" => \AdminLang::trans("global.no"))));
        $addons_html = run_hook("AdminHomepage", array());
        $templatevars["addons_html"] = $addons_html;
        $roleId = get_query_val("tbladmins", "roleid", array("id" => (int) \WHMCS\Session::get("adminid")));
        if ($roleId == 1) {
            if (!\WHMCS\Config\Setting::getValue("DisableSetupWizard")) {
                $aInt->addHeadJqueryCode("openSetupWizard();");
            } else {
                if ($aInt->hasPermission("View What's New") && $aInt->shouldSeeFeatureHighlights()) {
                    $aInt->addHeadJqueryCode("openFeatureHighlights();");
                }
            }
        }
        $licensing = \DI::make("license");
        if ($licensing->isClientLimitsEnabled()) {
            $templatevars["licenseinfo"]["productname"] .= " (" . $licensing->getTextClientLimit() . ")";
        }
        if (isset($jscode)) {
            $aInt->jscode = $jscode;
        }
        if (isset($jquerycode)) {
            $aInt->jquerycode = $jquerycode;
        }
        $aInt->templatevars = $templatevars;
        return $aInt->display();
    }
    public function mentions(\WHMCS\Http\Message\ServerRequest $request)
    {
        $admins = array();
        $adminUsers = \WHMCS\User\Admin::active()->orderBy("username")->get();
        foreach ($adminUsers as $adminUser) {
            $gravatar = "<img src=\"https://www.gravatar.com/avatar/" . $adminUser->gravatarHash . "?s=24&d=mm\" width=\"24\" height=\"24\" />";
            $admins[] = array("id" => $adminUser->id, "email" => $adminUser->email, "name" => $adminUser->fullName, "username" => $adminUser->username, "gravatar" => $gravatar);
        }
        return new \WHMCS\Http\Message\JsonResponse($admins);
    }
    public function marketingConversion(\WHMCS\Http\Message\ServerRequest $request)
    {
        $marketingType = $request->get("conversion_type");
        if ($marketingType != "") {
            check_token("WHMCS.admin.default");
            if ($marketingType == "convert") {
                \WHMCS\User\Client::where("emailoptout", 0)->update(array("marketing_emails_opt_in" => 1));
            }
            \WHMCS\Config\Setting::setValue("MarketingEmailConvert", "on");
            return new \WHMCS\Http\Message\JsonResponse(array("body" => "<script type=\"text/javascript\">jQuery(\"#marketingConsentAlert\").slideUp(\"fast\");</script>", "successMsg" => \AdminLang::trans("marketingConsent.conversionComplete"), "successMsgTitle" => \AdminLang::trans("global.success"), "dismiss" => true));
        }
        $postUrl = routePath("admin-marketing-consent-convert");
        $token = generate_token();
        $emptyOption = \AdminLang::trans("marketingConsent.doNotAssume");
        $convertOption = \AdminLang::trans("marketingConsent.doAssume");
        $introduction1 = \AdminLang::trans("marketingConsent.introduction1");
        $introduction2 = \AdminLang::trans("marketingConsent.introduction2");
        $introduction3 = \AdminLang::trans("marketingConsent.introduction3");
        $introduction4 = \AdminLang::trans("marketingConsent.introduction4");
        $helpLink = \AdminLang::trans("help.support");
        return new \WHMCS\Http\Message\JsonResponse(array("body" => "<p>\n    " . $introduction1 . "<br>\n    " . $introduction2 . " " . $introduction3 . "<br>\n    <div class=\"pull-right\"><a href=\"https://docs.whmcs.com/Marketing_Emails_Automation_Opt-In_Conversion\" class=\"autoLinked\">" . $helpLink . "</a></div>\n    " . $introduction4 . "\n</p>\n<form method=\"post\" action=\"" . $postUrl . "\">\n    " . $token . "\n    <label class=\"radio-inline\">\n        <input type=\"radio\" name=\"conversion_type\" value=\"empty\" checked=\"checked\">\n        " . $emptyOption . "\n    </label>\n    <div class=\"clearfix\"></div>\n    <label class=\"radio-inline\">\n        <input type=\"radio\" name=\"conversion_type\" value=\"convert\">\n        " . $convertOption . "\n    </label>\n</form>", "submitlabel" => \AdminLang::trans("global.save"), "submitId" => "btnSaveMarketingConversion"));
    }
    public function dismissMarketConnectProductPromo(\WHMCS\Http\Message\ServerRequest $request)
    {
        $adminUserId = (int) \WHMCS\Session::get("adminid");
        $dismissedProductPromotions = \WHMCS\Config\Setting::getValue("MarketConnectDismissedPromos");
        $dismissedProductPromotions = json_decode($dismissedProductPromotions, true);
        if (!is_array($dismissedProductPromotions)) {
            $dismissedProductPromotions = array();
        }
        $dismissedProductPromotions[$adminUserId] = \App::getVersion()->getVersion();
        \WHMCS\Config\Setting::setValue("MarketConnectDismissedPromos", json_encode($dismissedProductPromotions));
        return new \WHMCS\Http\Message\JsonResponse(array("messageTitle" => \AdminLang::trans("products.promoDismissed"), "message" => \AdminLang::trans("products.promoDismissedMessage")));
    }
    public function orderWidgets(\WHMCS\Http\Message\ServerRequest $request)
    {
        $order = $request->get("order");
        $admin = \WHMCS\User\Admin::find((int) \WHMCS\Session::get("adminid"));
        if ($admin && $admin->widgetOrder != $order) {
            $admin->widgetOrder = $order;
            $admin->save();
        }
        return new \WHMCS\Http\Message\JsonResponse(array("success" => true));
    }
}

?>