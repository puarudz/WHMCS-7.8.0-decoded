<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Configure Payment Gateways", false);
$aInt->title = $aInt->lang("setup", "gateways");
$aInt->sidebar = "config";
$aInt->icon = "offlinecc";
$aInt->helplink = "Payment Gateways";
$aInt->requireAuthConfirmation();
$aInt->requiredFiles(array("gatewayfunctions", "modulefunctions"));
if (App::getFromRequest("manage") && App::getFromRequest("gateway")) {
    redir("manage=1#m_" . strip_tags(strtolower(App::getFromRequest("gateway"))));
}
$GatewayValues = $GatewayConfig = $ActiveGateways = array();
$DisabledGateways = $AllGateways = $noConversion = array();
$numgateways = 0;
$includedmodules = array();
$noConfigFound = array();
$gatewayInterface = new WHMCS\Module\Gateway();
$AllGateways = $gatewayInterface->getList();
$ActiveGateways = $gatewayInterface->getActiveGateways();
$DisabledGateways = array_filter($AllGateways, function ($gateway) use($ActiveGateways) {
    return !in_array($gateway, $ActiveGateways);
});
foreach ($AllGateways as $gatewayModuleName) {
    if (!in_array($gatewayModuleName, $includedmodules)) {
        $gatewayInterface->load($gatewayModuleName);
        $includedmodules[] = $gatewayModuleName;
        try {
            $GatewayConfig[$gatewayModuleName] = $gatewayInterface->getConfiguration();
        } catch (Exception $e) {
            $noConfigFound[] = $gatewayModuleName;
            continue;
        }
        if (in_array($gatewayModuleName, $ActiveGateways)) {
            $noConversion[$gatewayModuleName] = $gatewayInterface->getMetaDataValue("noCurrencyConversion");
            $GatewayValues[$gatewayModuleName] = $gatewayInterface->loadSettings();
            if ($gatewayInterface->functionExists("admin_area_actions")) {
                $additionalButtons = $gatewayInterface->call("admin_area_actions");
                $additionalConfig = array();
                $buttons = array();
                foreach ($additionalButtons as $data) {
                    if (!is_array($data)) {
                        throw new WHMCS\Exception\Module\NotServicable("Invalid Function Return");
                    }
                    $methodName = $data["actionName"];
                    $buttonName = $data["label"];
                    $classes = array("btn", "btn-default", "open-modal");
                    $disabled = "";
                    $modalSize = "";
                    if (!empty($data["modalSize"])) {
                        $modalSize = "data-modal-size=\"" . $data["modalSize"] . "\"";
                    }
                    if (!empty($data["disabled"])) {
                        $disabled = " disabled=\"disabled";
                        $classes[] = "disabled";
                    }
                    $classes = implode(" ", $classes);
                    $routePath = routePath("admin-setup-payments-gateways-action", $gatewayModuleName, $methodName);
                    $button = "<a href=\"" . $routePath . "\" class=\"" . $classes . "\" data-modal-title=\"" . $buttonName . "\"" . $modalSize . ">\n    " . $buttonName . "\n</a>";
                    $buttons[] = $button;
                }
                $additionalConfig["additional_available_actions"] = array("FriendlyName" => "Available Actions", "Type" => "html", "Description" => implode("", $buttons));
                $GatewayConfig[$gatewayModuleName] += $additionalConfig;
            }
        }
    }
}
$lastorder = count($ActiveGateways);
$action = $whmcs->get_req_var("action");
if ($action == "onboarding" && in_array($gateway, $includedmodules)) {
    $gatewayInterface->load($gateway);
    if ($gatewayInterface->getMetaDataValue("apiOnboarding")) {
        echo $gatewayInterface->getOnBoardingRedirectHtml();
        throw new WHMCS\Exception\ProgramExit();
    }
}
if ($action == "activate" && in_array($gateway, $includedmodules)) {
    check_token("WHMCS.admin.default");
    $gatewayInterface = new WHMCS\Module\Gateway();
    $gatewayInterface->load($gateway);
    if ($gatewayInterface->getMetaDataValue("apiOnboarding")) {
        echo $gatewayInterface->getOnBoardingRedirectHtml();
        throw new WHMCS\Exception\ProgramExit();
    }
    delete_query("tblpaymentgateways", array("gateway" => $gateway));
    $lastorder++;
    $gatewayInterface->activate();
    try {
        $gatewayInterface->loadSettings();
        $gatewayInterface->call("post_activation");
    } catch (Exception $e) {
    }
    WHMCS\Session::delete("calinkupdatecc");
    redir("activated=" . $gateway . "#m_" . $gateway);
}
$newgateway = App::getFromRequest("newgateway");
if ($action == "deactivate" && in_array($newgateway, $includedmodules)) {
    check_token("WHMCS.admin.default");
    $gatewayInterface = new WHMCS\Module\Gateway();
    $gatewayInterface->load($gateway);
    try {
        $gatewayInterface->deactivate(array("newGateway" => $newgateway, "newGatewayName" => $GatewayConfig[$newgateway]["FriendlyName"]["Value"]));
        WHMCS\Session::delete("calinkupdatecc");
        redir("deactivated=true");
    } catch (Exception $e) {
        redir();
    }
    exit;
}
if ($action == "save" && in_array($module, $includedmodules)) {
    check_token("WHMCS.admin.default");
    $field = App::getFromRequest("field");
    $GatewayConfig[$module]["visible"] = array("Type" => "yesno");
    $GatewayConfig[$module]["name"] = array("Type" => "text");
    $GatewayConfig[$module]["convertto"] = array("Type" => "text");
    $gateway = new WHMCS\Module\Gateway();
    $gateway->load($module);
    $params = array();
    try {
        foreach ($field as $name => $value) {
            $params[$name] = WHMCS\Input\Sanitize::decode(trim($value));
        }
        $gateway->call("config_validate", $params);
        $existingParams = $gatewayInterface->getParams();
        foreach ($GatewayConfig[$module] as $confname => $values) {
            if ($values["Type"] != "System") {
                $valueToSave = WHMCS\Input\Sanitize::decode(trim($field[$confname]));
                if ($values["Type"] == "password") {
                    $updatedPassword = interpretMaskedPasswordChangeForStorage($valueToSave, $GatewayValues[$module][$confname]);
                    if ($updatedPassword === false) {
                        $valueToSave = $GatewayValues[$module][$confname];
                    }
                }
                WHMCS\Database\Capsule::table("tblpaymentgateways")->updateOrInsert(array("gateway" => $module, "setting" => $confname), array("value" => $valueToSave));
            }
        }
        $gateway->loadSettings();
        $gateway->call("config_post_save", array("existing" => $existingParams));
        $gatewayName = $GatewayConfig[$module]["FriendlyName"]["Value"];
        logAdminActivity("Gateway Module Configuration Modified: '" . $gatewayName . "'");
        $redirect = "updated=" . $module . "#m_" . $module;
    } catch (Exception $e) {
        WHMCS\Session::setAndRelease("GatewayConfiguration", $params);
        $error = $e->getMessage();
        if (!$error) {
            $error = "An unknown error occurred with the configuration check.";
        }
        WHMCS\Session::setAndRelease("ConfigurationError", $error);
        $redirect = "error=" . $module . "#m_" . $module;
    }
    redir($redirect);
}
if ($action == "moveup") {
    check_token("WHMCS.admin.default");
    $result = select_query("tblpaymentgateways", "", array("`order`" => $order));
    $data = mysql_fetch_array($result);
    $gateway = $data["gateway"];
    $order1 = $order - 1;
    update_query("tblpaymentgateways", array("order" => $order), array("`order`" => $order1));
    update_query("tblpaymentgateways", array("order" => $order1), array("gateway" => $gateway));
    logAdminActivity("Gateway Module Sorting Changed: Moved Up - '" . $GatewayConfig[$gateway]["FriendlyName"]["Value"] . "'");
    redir("sortchange=1");
}
if ($action == "movedown") {
    check_token("WHMCS.admin.default");
    $result = select_query("tblpaymentgateways", "", array("`order`" => $order));
    $data = mysql_fetch_array($result);
    $gateway = $data["gateway"];
    $order1 = $order + 1;
    update_query("tblpaymentgateways", array("order" => $order), array("`order`" => $order1));
    update_query("tblpaymentgateways", array("order" => $order1), array("gateway" => $gateway));
    logAdminActivity("Gateway Module Sorting Changed: Moved Down - '" . $GatewayConfig[$gateway]["FriendlyName"]["Value"] . "'");
    redir("sortchange=1");
}
$result = select_query("tblcurrencies", "id,code", "", "code", "ASC");
$i = 0;
$currenciesarray[$i] = mysql_fetch_assoc($result);
if ($currenciesarray[$i]) {
    $i++;
} else {
    array_pop($currenciesarray);
    $promoHelper = new WHMCS\View\Admin\Marketplace\PromotionHelper();
    $promoHelper->hookIntoPage($aInt);
    if ($promoHelper->isPromoFetchRequest()) {
        $response = $promoHelper->fetchPromoContent($whmcs->get_req_var("partner"), $whmcs->get_req_var("promodata"));
        $aInt->setBodyContent($response);
    } else {
        ob_start();
        $showGatewayConfig = false;
        if (App::getFromRequest("activated") || App::getFromRequest("deactivated") || App::getFromRequest("error") || App::getFromRequest("sortchange") || App::getFromRequest("updated") || App::getFromRequest("manage")) {
            $showGatewayConfig = true;
        }
        if ($whmcs->get_req_var("deactivated")) {
            infoBox($aInt->lang("global", "success"), $aInt->lang("gateways", "deactivatesuccess"));
        }
        if ($whmcs->get_req_var("sortchange")) {
            infoBox($aInt->lang("global", "success"), $aInt->lang("gateways", "sortchangesuccess"));
        }
        if (App::getFromRequest("obfailed")) {
            infoBox("Gateway Activation Failed", "Failed to activate payment gateway successfully. Please try again or contact support.", "error");
            echo $infobox;
        }
        echo "    ";
        if (0 < count($noConfigFound)) {
            $noConfigMessage = AdminLang::trans("gateways.noConfigFound");
            echo "        <div class=\"alert alert-info text-center\">";
            echo $noConfigMessage;
            echo "            <ul style=\"display: inline-block; text-align: left;\">\n                ";
            foreach ($noConfigFound as $failedModule) {
                echo "            <li>";
                echo "modules" . DIRECTORY_SEPARATOR . "gateways" . DIRECTORY_SEPARATOR . $failedModule . ".php";
                echo "</li>\n                ";
            }
            echo "            </ul>\n        </div>\n    ";
        }
        echo "<div role=\"tabpanel\">\n    <ul class=\"nav nav-tabs\" role=\"tablist\">\n        <li role=\"presentation\"";
        if (!$showGatewayConfig) {
            echo " class=\"active\"";
        }
        echo ">\n            <a href=\"#featured\" id=\"btnFeaturedGateways\" aria-controls=\"home\" role=\"tab\" data-toggle=\"tab\">\n                <i class=\"fas fa-star\"></i> Featured Payment Gateways\n            </a>\n        </li>\n        <li role=\"presentation\">\n            <a href=\"#all\" id=\"btnViewAllGateways\" aria-controls=\"profile\" role=\"tab\" data-toggle=\"tab\">\n                <i class=\"fas fa-plus\"></i> All Payment Gateways\n            </a>\n        </li>\n        <li role=\"presentation\"";
        if ($showGatewayConfig) {
            echo " class=\"active\"";
        }
        echo ">\n            <a href=\"#manage\" id=\"btnManageGateways\" aria-controls=\"messages\" role=\"tab\" data-toggle=\"tab\">\n                <i class=\"fas fa-wrench\"></i> Manage Existing Gateways\n            </a>\n        </li>\n    </ul>\n    <br />\n    <div class=\"tab-content\">\n        <div role=\"tabpanel\" class=\"tab-pane fade in";
        if (!$showGatewayConfig) {
            echo " active";
        }
        echo "\" id=\"featured\">\n\n            <div class=\"partner-box\">\n                <div class=\"row\">\n                    <div class=\"col-md-3\">\n                        <div class=\"partner-logo\" onclick=\"showPromo('paypal')\">\n                            <img src=\"https://cdn.whmcs.com/assets/logos/paypal.png\">\n                        </div>\n                    </div>\n                    <div class=\"col-md-7 partner-features\">\n                        <div class=\"partner-headline\">\n                            PayPal is one of the simplest and quickest ways for your customers to pay.\n                        </div>\n                        <div class=\"row\">\n                            <div class=\"col-sm-11 col-sm-offset-1\">\n                                <div class=\"row\">\n                                    <div class=\"col-sm-6\">\n                                        <i class=\"fas fa-check\"></i> Get Paid On Time<br />\n                                        <i class=\"fas fa-check\"></i> Express Checkout Supported\n                                    </div>\n                                    <div class=\"col-sm-6\">\n                                        <i class=\"fas fa-check\"></i> Automatic Subscription Billing<br />\n                                        <i class=\"fas fa-check\"></i> One-Click Refunds\n                                    </div>\n                                </div>\n                            </div>\n                        </div>\n                    </div>\n                    <div class=\"col-md-2 text-center partner-actions\">\n                        <button class=\"btn btn-info\" onclick=\"showPromo('paypal')\">\n                            ";
        echo AdminLang::trans("global.learnMore");
        echo "                        </button>\n                    </div>\n                </div>\n            </div>\n\n            <div class=\"partner-box partner-box-blue\">\n                <div class=\"row\">\n                    <div class=\"col-md-3\">\n                        <div class=\"partner-logo\" onclick=\"showPromo('authorizenet')\">\n                            <img src=\"https://cdn.whmcs.com/assets/logos/authorizenet.png\">\n                        </div>\n                    </div>\n                    <div class=\"col-md-7 partner-features\">\n                        <div class=\"partner-headline\">\n                            Accept Credit Cards simply and securely with Authorize.net powered by EVO Payments.\n                        </div>\n                        <div class=\"row top-margin-5\">\n                            <div class=\"col-sm-11 col-sm-offset-1\">\n                                <div class=\"row\">\n                                    <div class=\"col-sm-6\">\n                                        <i class=\"fas fa-fw fa-check\"></i> Automated Recurring Billing<br />\n                                        <i class=\"fas fa-fw fa-check\"></i> Secure Tokenised Card Storage<br />\n                                        <i class=\"fas fa-fw fa-info\"></i> US Users Only\n                                    </div>\n                                    <div class=\"col-sm-6\">\n                                        <i class=\"fas fa-fw fa-check\"></i> Seamless Checkout Experience<br />\n                                        <i class=\"fas fa-fw fa-check\"></i> Best Rates Guaranteed\n                                    </div>\n                                </div>\n                            </div>\n                        </div>\n                    </div>\n                    <div class=\"col-md-2 text-center partner-actions\">\n                        <button class=\"btn btn-info\" onclick=\"showPromo('authorizenet')\">\n                            ";
        echo AdminLang::trans("global.learnMore");
        echo "                        </button>\n                    </div>\n                </div>\n            </div>\n\n            <div class=\"row\">\n                <div class=\"col-md-2\"></div>\n                <div class=\"col-md-4\">\n                    <div style=\"margin:0 0 10px 0;padding:10px 15px;background-color:#fff;border-radius:6px;\" class=\"text-center\">\n                        <div style=\"height:70px;line-height:60px;\">\n                            <img src=\"https://cdn.whmcs.com/assets/logos/2checkout.gif\">\n                        </div>\n                        2CheckOut provides a secure hosted checkout process so you can accept payments without any of the hassles of PCI Compliance.<br />\n                        <div class=\"top-margin-10\">\n                            <button class=\"btn btn-default\" onclick=\"showPromo('2checkout')\">\n                                ";
        echo AdminLang::trans("global.learnMore");
        echo "                            </button>\n                        </div>\n                    </div>\n                </div>\n                <div class=\"col-md-4\">\n                    <div style=\"margin:0 0 10px 0;padding:10px 15px;background-color:#fff;border-radius:6px;\" class=\"text-center\">\n                        <div style=\"height:70px;line-height:60px;\">\n                            <img src=\"https://cdn.whmcs.com/assets/logos/skrill.gif\">\n                        </div>\n                        Trusted by millions across the globe Skrill allows you to pay and get paid in nearly 200 countries and 40 currencies.<br />\n                        <div class=\"top-margin-10\">\n                            <button class=\"btn btn-default\" onclick=\"showPromo('skrill')\">\n                                ";
        echo AdminLang::trans("global.learnMore");
        echo "                            </button>\n                        </div>\n                    </div>\n                </div>\n                <div class=\"col-md-2\"></div>\n            </div>\n\n            <p class=\"text-center text-muted\" style=\"background-color:#efefef;padding:6px;\">Looking for a payment gateway not listed above? View the <a href=\"#\" onclick=\"\$('#btnViewAllGateways').click()\" class=\"btn btn-warning btn-xs\">full list of payment gateways</a> we integrate with.</p>\n\n            <p class=\"text-center text-muted\"><small>There are many more payment gateways that, although not included in WHMCS by default, have modules for WHMCS. Many of those can be found in our <a href=\"https://marketplace.whmcs.com/\" target=\"_blank\">Marketplace</a>.</small></p>\n\n        </div>\n        <div role=\"tabpanel\" class=\"tab-pane fade\" id=\"all\">\n\n            <p>Click on a payment gateway below to activate and begin using it. Already active payment gateways will appear in green.</p>\n\n            <div class=\"row\">\n                <div class=\"clearfix\" style=\"background-color:#f8f8f8;margin:0 0 20px 0;padding:20px 0;\">\n                    <div class=\"col-xs-10 col-xs-offset-1\">\n                        <div class=\"row\">\n\n";
        sort($AllGateways);
        $output = array();
        foreach ($AllGateways as $modulename) {
            $displayName = $GatewayConfig[$modulename]["FriendlyName"]["Value"];
            $isActive = in_array($modulename, $ActiveGateways);
            $btnDisabled = $isActive ? " disabled" : "";
            $output[strtolower($displayName)] = "<div class=\"col-md-3 col-sm-6 text-center\" style=\"margin-bottom:5px;\">\n            <form method=\"post\" action=\"configgateways.php\">\n                <input type=\"hidden\" name=\"action\" value=\"activate\" />\n                <input type=\"hidden\" name=\"gateway\" value=\"" . $modulename . "\" />\n                <button type=\"submit\" id=\"btnActivate-" . $modulename . "\" class=\"btn btn-" . ($isActive ? "success" : "default") . " btn-sm btn-block truncate\"" . $btnDisabled . ">\n                    " . $displayName . "\n                </button>\n            </form>\n        </div>" . PHP_EOL;
        }
        ksort($output);
        echo implode($output);
        echo "                        </div>\n                    </div>\n                </div>\n            </div>\n\n            <p class=\"text-center text-muted\">Can't find the payment gateway you're looking for? Take a look at our <a href=\"https://marketplace.whmcs.com/product/category/Payment+Gateways\" target=\"_blank\">Marketplace</a> for gateways with third party modules.</p>\n\n        </div>\n        <div role=\"tabpanel\" class=\"tab-pane fade";
        if ($showGatewayConfig) {
            echo " in active";
        }
        echo "\" id=\"manage\">\n\n";
        echo $infobox ? $infobox . "<br />" : "";
        $count = 1;
        $newgateways = "";
        $result3 = select_query("tblpaymentgateways", "", array("setting" => "name"), "order", "ASC");
        while ($data = mysql_fetch_array($result3)) {
            $module = $data["gateway"];
            $order = $data["order"];
            echo "\n<form method=\"post\" action=\"";
            echo $whmcs->getPhpSelf();
            echo "?action=save\">\n<input type=\"hidden\" name=\"module\" value=\"";
            echo $module;
            echo "\">\n\n";
            $isModuleDisabled = false;
            if (isset($GatewayConfig[$module])) {
                $modName = $GatewayConfig[$module]["FriendlyName"]["Value"];
            } else {
                $modName = $module;
                $isModuleDisabled = true;
            }
            echo "<a name=\"m_" . $module . "\"></a><h2>" . $count . ". " . $modName;
            if ($numgateways != "1") {
                echo " <a href=\"#\" onclick=\"deactivateGW('" . $module . "','" . $GatewayConfig[$module]["FriendlyName"]["Value"] . "');return false\" style=\"color:#cc0000\">(" . $aInt->lang("gateways", "deactivate") . ")</a> ";
            }
            if ($order != "1") {
                echo "<a href=\"?action=moveup&order=" . $order . generate_token("link") . "\"><img src=\"images/moveup.gif\" align=\"absmiddle\" width=\"16\" height=\"16\" border=\"0\" alt=\"\"></a> ";
            }
            if ($order != $lastorder) {
                echo "<a href=\"?action=movedown&order=" . $order . generate_token("link") . "\"><img src=\"images/movedown.gif\" align=\"absmiddle\" width=\"16\" height=\"16\" border=\"0\" alt=\"\"></a>";
            }
            echo "</h2>";
            $infobox = "";
            $passedParams = array();
            if ($whmcs->get_req_var("activated") == $module) {
                infoBox($aInt->lang("global", "success"), $aInt->lang("gateways", "activatesuccess"));
            } else {
                if (App::getFromRequest("error") == $module) {
                    $message = AdminLang::trans(WHMCS\Session::getAndDelete("ConfigurationError"));
                    $message .= "<br>" . AdminLang::trans("gateways.changesUnsaved");
                    infoBox(AdminLang::trans("global.erroroccurred"), $message, "error");
                    $passedParams[$module] = WHMCS\Session::getAndDelete("GatewayConfiguration");
                } else {
                    if ($whmcs->get_req_var("updated") == $module) {
                        infoBox($aInt->lang("global", "success"), $aInt->lang("gateways", "savesuccess"), "success");
                    }
                }
            }
            if ($infobox) {
                echo $infobox;
            }
            if ($isModuleDisabled === true) {
                echo "    <p style=\"border: 2px solid red; padding: 10px\"><strong>";
                echo $aInt->lang("gateways", "moduleunavailable");
                echo "</strong></p>\n";
            } else {
                echo "<table class=\"form\" id=\"Payment-Gateway-Config-";
                echo $module;
                echo "\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"300\" class=\"fieldlabel\">";
                echo $aInt->lang("gateways", "showonorderform");
                echo "</td><td class=\"fieldarea\"><input type=\"checkbox\" name=\"field[visible]\"";
                if ($GatewayValues[$module]["visible"]) {
                    echo " checked";
                }
                echo " /></td></tr>\n<tr><td class=\"fieldlabel\">";
                echo $aInt->lang("gateways", "displayname");
                echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"field[name]\" size=\"30\" class=\"form-control input-inline input-300\" value=\"";
                echo htmlspecialchars($GatewayValues[$module]["name"]);
                echo "\"></td></tr>\n";
                foreach ($GatewayConfig[$module] as $confname => $values) {
                    if ($values["Type"] != "System") {
                        $values["Name"] = "field[" . $confname . "]";
                        if (isset($GatewayValues[$module][$confname])) {
                            $values["Value"] = $GatewayValues[$module][$confname];
                        }
                        if (isset($passedParams[$module][$confname])) {
                            $values["Value"] = $passedParams[$module][$confname];
                        }
                        echo "<tr>\n                <td class=\"fieldlabel\">" . $values["FriendlyName"] . "</td>\n                <td class=\"fieldarea\">" . moduleConfigFieldOutput($values) . "</td>\n            </tr>";
                    }
                }
                if (1 < count($currenciesarray) && !$noConversion[$module]) {
                    echo "<tr><td class=\"fieldlabel\">" . $aInt->lang("gateways", "currencyconvert") . "</td><td class=\"fieldarea\"><select name=\"field[convertto]\" class=\"form-control select-inline\"><option value=\"\">" . $aInt->lang("global", "none") . "</option>";
                    foreach ($currenciesarray as $currencydata) {
                        echo "<option value=\"" . $currencydata["id"] . "\"";
                        if (isset($GatewayValues[$module]["convertto"]) && $currencydata["id"] == $GatewayValues[$module]["convertto"]) {
                            echo " selected";
                        }
                        echo ">" . $currencydata["code"] . "</option>";
                    }
                    echo "</select></td></tr>";
                }
                if (array_key_exists("UsageNotes", $GatewayConfig[$module]) && $GatewayConfig[$module]["UsageNotes"]["Value"]) {
                    echo "<tr>\n    <td class=\"fieldlabel\"></td>\n    <td>\n        <div class=\"alert alert-info clearfix\" role=\"alert\" style=\"margin:0;\">\n            <i class=\"fas fa-info-circle fa-3x pull-left fa-fw\"></i>\n            <div style=\"margin-left: 56px;\">" . $GatewayConfig[$module]["UsageNotes"]["Value"] . "</div>\n        </div>\n    </td>\n</tr>";
                }
                echo "    <tr>\n        <td class=\"fieldlabel\"></td>\n        <td class=\"fieldarea\">\n            <input type=\"submit\" value=\"";
                echo $aInt->lang("global", "savechanges");
                echo "\" class=\"btn btn-primary\">\n        </td>\n    </tr>\n</table>\n";
            }
            echo "<br />\n\n</form>\n\n";
            if ($count != $order) {
                update_query("tblpaymentgateways", array("order" => $count), array("setting" => "name", "gateway" => $module));
            }
            $count++;
            $newgateways .= "<option value=\"" . $module . "\">" . $GatewayConfig[$module]["FriendlyName"]["Value"] . "</option>";
        }
        if (count($ActiveGateways) < 1) {
            echo "<p class=\"alert alert-danger\"><strong>" . $aInt->lang("gateways", "noGatewaysActive") . "</strong> " . $aInt->lang("gateways", "activateGatewayFirst") . "</p>";
        }
        echo "\n        </div>\n    </div>\n</div>\n\n";
        $jscode .= "var gatewayOptions = \"" . addslashes($newgateways) . "\";\nfunction deactivateGW(module,friendlyname) {\n    \$(\"#inputDeactivateGatewayName\").val(module);\n    \$(\"#inputFriendlyGatewayName\").val(friendlyname);\n    \$(\"#inputNewGateway\").html(gatewayOptions);\n    \$(\"#inputNewGateway option[value='\"+module+\"']\").remove();\n    \$(\"#modalDeactivateGateway\").modal(\"show\");\n}";
        echo $aInt->modal("DeactivateGateway", $aInt->lang("gateways", "deactivatemodule"), "<p>" . $aInt->lang("gateways", "deactivatemoduleinfo") . "</p>\n<form method=\"post\" action=\"configgateways.php?action=deactivate\" id=\"frmDeactivateGateway\">\n    <input type=\"hidden\" name=\"gateway\" value=\"\" id=\"inputDeactivateGatewayName\">\n    <input type=\"hidden\" name=\"friendlygateway\" value=\"\" id=\"inputFriendlyGatewayName\">\n    <div class=\"text-center\">\n        <select id=\"inputNewGateway\" name=\"newgateway\" class=\"form-control select-inline\">\n            " . $newgateways . "\n        </select>\n    </div>\n</form>", array(array("title" => $aInt->lang("gateways", "deactivate"), "onclick" => "\$(\"#frmDeactivateGateway\").submit()", "class" => "btn-primary"), array("title" => $aInt->lang("supportreq", "cancel"))));
        $content = ob_get_contents();
        ob_end_clean();
        $aInt->content = $content;
        $aInt->jquerycode = $jquerycode;
        $aInt->jscode = $jscode;
    }
    $aInt->display();
}

?>