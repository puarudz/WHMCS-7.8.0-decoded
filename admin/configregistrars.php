<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Configure Domain Registrars");
$aInt->title = $aInt->lang("domainregistrars", "title");
$aInt->sidebar = "config";
$aInt->icon = "domains";
$aInt->helplink = "Domain Registrars";
$aInt->requiredFiles(array("registrarfunctions", "modulefunctions"));
$module = $whmcs->get_req_var("module");
$action = $whmcs->get_req_var("action");
if ($action == "save") {
    check_token("WHMCS.admin.default");
    unset($_POST["token"]);
    unset($_POST["save"]);
    if ($module) {
        $registrar = new WHMCS\Module\Registrar();
        if ($registrar->load($module)) {
            if ($registrar->isActivated()) {
                $registrar->saveSettings($_POST);
            } else {
                $registrar->activate()->saveSettings($_POST);
            }
        }
    }
    RebuildRegistrarModuleHookCache();
    redir("saved=true#" . $module);
}
if ($action == "activate") {
    check_token("WHMCS.admin.default");
    if ($module) {
        $registrar = new WHMCS\Module\Registrar();
        if ($registrar->load($module)) {
            $registrar->activate();
        }
    }
    RebuildRegistrarModuleHookCache();
    redir("activated=true#" . $module);
}
if ($action == "deactivate") {
    check_token("WHMCS.admin.default");
    if ($module) {
        $registrar = new WHMCS\Module\Registrar();
        if ($registrar->load($module)) {
            logAdminActivity("Registrar Deactivated: '" . $registrar->getDisplayName() . "'");
            $registrar->deactivate();
        }
    }
    RebuildRegistrarModuleHookCache();
    redir("deactivated=true");
}
$promoHelper = new WHMCS\View\Admin\Marketplace\PromotionHelper();
$promoHelper->hookIntoPage($aInt);
if ($promoHelper->isPromoFetchRequest()) {
    $response = $promoHelper->fetchPromoContent($whmcs->get_req_var("partner"), $whmcs->get_req_var("promodata"));
    $aInt->setBodyContent($response);
} else {
    ob_start();
    if ($saved) {
        infoBox($aInt->lang("domainregistrars", "changesuccess"), $aInt->lang("domainregistrars", "changesuccessinfo"), "success");
    }
    if ($activated) {
        infoBox($aInt->lang("domainregistrars", "moduleactivated"), $aInt->lang("domainregistrars", "moduleactivatedinfo"), "success");
    }
    if ($deactivated) {
        infoBox($aInt->lang("domainregistrars", "moduledeactivated"), $aInt->lang("domainregistrars", "moduledeactivatedinfo"), "success");
    }
    echo $infobox;
    $aInt->deleteJSConfirm("deactivateMod", "domainregistrars", "deactivatesure", $_SERVER["PHP_SELF"] . "?action=deactivate&module=");
    $jscode .= "function showConfig(module) {\n    \$(\"#\"+module+\"config\").fadeToggle();\n}";
    echo "\n<h2>Sponsored Domain Registrars</h2>\n\n<div class=\"row partner-registrars\">\n    <div class=\"col-md-6\">\n        <div class=\"partner-box\">\n            <div class=\"row bottom-margin-10\">\n                <div class=\"col-md-8\">\n                    <div class=\"partner-logo\" onclick=\"showPromo('enom')\">\n                        <img src=\"https://cdn.whmcs.com/assets/logos/enom.gif\">\n                    </div>\n                </div>\n                <div class=\"col-md-4 text-center partner-actions\">\n                    <button class=\"btn btn-default\" onclick=\"showPromo('enom')\">";
    echo AdminLang::trans("global.signupNow");
    echo "</button>\n                </div>\n            </div>\n            eNom, Inc. is a domain name registrar and web hosting services company. ICANN accredited & rated the #1 Reseller Registrar.\n        </div>\n    </div>\n    <div class=\"col-md-6\">\n        <div class=\"partner-box\">\n            <div class=\"row bottom-margin-10\">\n                <div class=\"col-md-8\">\n                    <div class=\"partner-logo\" onclick=\"showPromo('resellerclub')\">\n                        <img src=\"https://cdn.whmcs.com/assets/logos/resellerclub.png\">\n                    </div>\n                </div>\n                <div class=\"col-md-4 text-center partner-actions\">\n                    <button class=\"btn btn-default\" onclick=\"showPromo('resellerclub')\">";
    echo AdminLang::trans("global.signupNow");
    echo "</button>\n                </div>\n            </div>\n            ResellerClub, founded in 1998, is a major registrar services reseller and is one of the world’s largest ICANN accredited Registrars.\n        </div>\n    </div>\n</div>\n\n";
    echo "<div class=\"tablebg\">\n<table class=\"datatable\" width=\"100%\" border=\"0\" cellspacing=\"1\" cellpadding=\"3\">\n<tr><th width=\"140\"></th><th>" . $aInt->lang("addonmodules", "module") . "</th><th width=\"350\"></th></tr>";
    $registrar = new WHMCS\Module\Registrar();
    $modulesarray = $registrar->getList();
    $modulesConfigHtml = array();
    foreach ($modulesarray as $module) {
        if (!isValidforPath($module)) {
            exit("Invalid Registrar Module Name");
        }
        if (file_exists("../modules/registrars/" . $module . "/logo.gif")) {
            $registrarlogourl = "../modules/registrars/" . $module . "/logo.gif";
        } else {
            if (file_exists("../modules/registrars/" . $module . "/logo.jpg")) {
                $registrarlogourl = "../modules/registrars/" . $module . "/logo.jpg";
            } else {
                if (file_exists("../modules/registrars/" . $module . "/logo.png")) {
                    $registrarlogourl = "../modules/registrars/" . $module . "/logo.png";
                } else {
                    $registrarlogourl = "./images/spacer.gif";
                }
            }
        }
        $moduleactive = false;
        $registrar->load($module);
        $moduleconfigdata = $registrar->getSettings();
        if (is_array($moduleconfigdata) && !empty($moduleconfigdata)) {
            $moduleactive = true;
            $moduleaction = "<input type=\"button\" value=\"" . $aInt->lang("addonmodules", "activate") . "\" disabled=\"disabled\" class=\"btn btn-disabled\" /> <input type=\"button\" value=\"" . $aInt->lang("addonmodules", "deactivate") . "\" onclick=\"deactivateMod('" . $module . "');return false\" class=\"btn btn-danger\" />  <input type=\"button\" value=\"" . $aInt->lang("addonmodules", "config") . "\" class=\"btn btn-default\" onclick=\"showConfig('" . $module . "')\" />";
        } else {
            $moduleaction = "<input type=\"button\" value=\"" . $aInt->lang("addonmodules", "activate") . "\" onclick=\"window.location='" . $_SERVER["PHP_SELF"] . "?action=activate&module=" . $module . generate_token("link") . "'\" class=\"btn btn-success\" /> <input type=\"button\" value=\"" . $aInt->lang("addonmodules", "deactivate") . "\" disabled=\"disabled\" class=\"btn disabled\" /> <input type=\"button\" value=\"" . $aInt->lang("addonmodules", "config") . "\" disabled=\"disabled\" class=\"btn btn-disabled\" />";
        }
        $configarray = $registrar->call("getConfigArray");
        $displayName = $registrar->getDisplayName();
        ob_start();
        echo "    <tr id=\"formholder_";
        echo $module;
        echo "\" ";
        if ($moduleactive) {
            echo "class=\"active\" style=\"background-color:#EBFEE2;\"";
        }
        echo ">\n        <td align=\"center\" ";
        if ($moduleactive) {
            echo "style=\"background-color:#EBFEE2;\"";
        }
        echo "><a name=\"";
        echo $module;
        echo "\"></a><img src=\"";
        echo $registrarlogourl;
        echo "\" width=\"125\" height=\"40\" style=\"border:1px solid #ccc;\" /></td>\n        <td class=\"title\" ";
        if ($moduleactive) {
            echo "style=\"background-color:#EBFEE2;\"";
        }
        echo "><strong>&nbsp;&raquo; ";
        echo $displayName;
        echo "</strong>";
        if ($configarray["Description"]["Value"]) {
            echo "<br />" . $configarray["Description"]["Value"];
        }
        echo "</td>\n        <td width=\"200\" align=\"center\" ";
        if ($moduleactive) {
            echo "style=\"background-color:#EBFEE2;\"";
        }
        echo ">";
        echo $moduleaction;
        echo "</td>\n    </tr>\n    <tr><td id=\"";
        echo $module;
        echo "config\" class=\"config\" style=\"display:none;padding:15px;\" colspan=\"3\"><form method=\"post\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "?action=save&module=";
        echo $module . generate_token("link");
        echo "\">\n        <table class=\"form\" width=\"100%\">\n        ";
        foreach ($configarray as $key => $values) {
            if ($values["Type"] != "System") {
                if (!$values["FriendlyName"]) {
                    $values["FriendlyName"] = $key;
                }
                $values["Name"] = $key;
                $values["Value"] = $moduleconfigdata[$key];
                echo "<tr><td class=\"fieldlabel\">" . $values["FriendlyName"] . "</td><td class=\"fieldarea\">" . moduleConfigFieldOutput($values) . "</td></tr>";
            }
        }
        echo "        </table><br /><div align=\"center\"><input type=\"submit\" name=\"save\" value=\"";
        echo $aInt->lang("global", "savechanges");
        echo "\" class=\"btn primary\" /></form></div><br />\n    </td></tr>\n";
        $modulesConfigHtml[$displayName] = ob_get_clean();
    }
    uksort($modulesConfigHtml, "strnatcmp");
    echo implode("\n", $modulesConfigHtml);
    echo "</table>\n</div>\n\n<script language=\"javascript\">\n\$(document).ready(function(){\n    var modpass = window.location.hash;\n    if (modpass) \$(modpass+\"config\").show();\n});\n</script>\n\n";
    $content = ob_get_contents();
    ob_end_clean();
    $aInt->content = $content;
    $aInt->jquerycode = $jquerycode;
    $aInt->jscode = $jscode;
}
$aInt->display();

?>