<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Configure Domain Pricing");
$aInt->title = $aInt->lang("domains", "pricingtitle");
$aInt->sidebar = "config";
$aInt->icon = "domains";
$aInt->helplink = "Domain Pricing";
$aInt->requiredFiles(array("registrarfunctions"));
ob_start();
$whmcs = WHMCS\Application::getInstance();
$action = $whmcs->get_req_var("action");
$success = $whmcs->get_req_var("success");
$error = $whmcs->get_req_var("error");
$jqueryCode = "";
if ($action == "saveorder") {
    check_token("WHMCS.admin.default");
    $pricing = App::getFromRequest("pricing");
    foreach ($pricing as $order => $tldId) {
        WHMCS\Domains\Extension::where("id", $tldId)->update(array("order" => $order));
    }
    logAdminActivity("Domain Pricing TLD Order Updated");
    $aInt->jsonResponse(array("success" => true));
    WHMCS\Terminus::getInstance()->doExit();
}
if ($action == "showduplicatetld") {
    $tldsresult = select_query("tbldomainpricing", "extension", "");
    $tldoptions = "<option value=\"\">" . $aInt->lang("domains", "selecttldtoduplicate") . "</option>";
    while ($tldsdata = mysql_fetch_assoc($tldsresult)) {
        $tldoptions .= "<option value=\"" . $tldsdata["extension"] . "\">" . $tldsdata["extension"] . "</option>";
    }
    $body = "<form method=\"post\" id=\"duplicatetldform\" action=\"" . $_SERVER["PHP_SELF"] . "\" onsubmit=\"jQuery('#btnDuplicateTld').trigger('click'); return false;\">" . generate_token("form") . "<table width=\"80%\" align=\"center\">" . "<tr><td>Existing TLD:</td><td><input type=\"hidden\" name=\"action\" value=\"duplicatetld\" /><select name=\"tld\" class=\"form-control\">" . $tldoptions . "</select></td></tr>" . "<tr><td>New TLD:</td><td><input type=\"text\" name=\"newtld\" class=\"form-control input-100\" /></td></tr></table></form>";
    $response = new WHMCS\Http\JsonResponse(array("body" => $body));
    $response->send();
    exit;
}
if ($action == "toggle-premium") {
    check_token("WHMCS.admin.default");
    $enable = (int) $whmcs->getFromRequest("enable");
    WHMCS\Config\Setting::setValue("PremiumDomains", $enable);
    $aInt->jsonResponse(array("success" => true));
}
if ($action == "delete-premium") {
    check_token("WHMCS.admin.default");
    $id = (int) $whmcs->getFromRequest("id");
    try {
        WHMCS\Domains\Pricing\Premium::where("id", "=", $id)->delete();
    } catch (Exception $e) {
        $aInt->jsonResponse(array("errorMsg" => $e->getMessage(), "errorMsgTitle" => AdminLang::trans("global.error")));
    }
    $aInt->jsonResponse(array("successMsg" => AdminLang::trans("global.changesuccessdeleted"), "successMsgTitle" => AdminLang::trans("global.success")));
}
$jsToken = generate_token("plain");
if ($action == "premium-levels") {
    $token = generate_token();
    $saveOutput = array();
    if ($whmcs->isInRequest("save")) {
        check_token("WHMCS.admin.default");
        $ids = $whmcs->getFromRequest("ids");
        $tos = $whmcs->getFromRequest("to");
        $markups = $whmcs->getFromRequest("markup");
        try {
            $message = "";
            $saved = $new = $toSave = false;
            foreach ($ids as $id) {
                $level = WHMCS\Domains\Pricing\Premium::find($id);
                if ($level->toAmount != (double) $tos[$id]) {
                    $level->toAmount = (double) $tos[$id];
                    $toSave = true;
                }
                if ($level->markup != (double) $markups[$id]) {
                    $level->markup = (double) $markups[$id];
                    $toSave = true;
                }
                if ($toSave) {
                    $level->save();
                    $saved = true;
                }
            }
            if ($saved) {
                $message .= AdminLang::trans("global.changesuccessdesc");
            }
            foreach ($tos["new"] as $key => $to) {
                if (!$to) {
                    continue;
                }
                $level = new WHMCS\Domains\Pricing\Premium();
                $level->toAmount = (double) $to;
                $level->markup = (double) $markups["new"][$key];
                $level->save();
                $new = true;
            }
            if ($new) {
                $message .= "<br />" . AdminLang::trans("global.changesuccessadded");
            }
            $saveOutput["successMsg"] = $message;
            $saveOutput["successMsgTitle"] = AdminLang::trans("global.success");
        } catch (Exception $e) {
            $saveOutput["errorMsg"] = $e->getMessage();
            $saveOutput["errorMsgTitle"] = AdminLang::trans("global.error");
        }
    }
    $premiumBandsInformation = AdminLang::trans("domains.premiumBandsInformation");
    $output = "<div class=\"alert alert-warning text-center\">\n    " . $premiumBandsInformation . "\n</div>\n<form action=\"configdomains.php\">\n    " . $token . "\n    <input type=\"hidden\" name=\"action\" value=\"premium-levels\" />\n    <input type=\"hidden\" name=\"save\" value=\"true\" />\n    <div class=\"table-responsive\">\n        <table class=\"table\">\n            <tr>\n                <th>Price &lt;</th><th>Markup %</th><th></th>\n            </tr>";
    $maxLevel = NULL;
    $maxAmount = 0;
    $uniqueText = AdminLang::trans("domains.levelUnique");
    foreach (WHMCS\Domains\Pricing\Premium::all() as $premiumLevel) {
        if ($premiumLevel->toAmount == -1) {
            $maxLevel = $premiumLevel;
            continue;
        }
        if ($maxAmount < $premiumLevel->toAmount) {
            $maxAmount = $premiumLevel->toAmount;
        }
        $markup = floatval($premiumLevel->markup);
        $output .= "<tr>\n    <input type=\"hidden\" name=\"ids[]\" value=\"" . $premiumLevel->id . "\" />\n    <td>\n        <input type=\"text\" class=\"form-control to-amount\" name=\"to[" . $premiumLevel->id . "]\" value=\"" . $premiumLevel->toAmount . "\" data-toggle=\"tooltip\" data-placement=\"top\" data-trigger=\"manual\" title=\"" . $uniqueText . "\" />\n    </td>\n    <td>\n        <div class=\"input-group\">\n            <input type=\"text\" class=\"form-control\" name=\"markup[" . $premiumLevel->id . "]\" value=\"" . $markup . "\" placeholder=\"%\" />\n            <div class=\"input-group-addon\">%</div>\n        </div>\n    </td>\n    <td><a href=\"#\" onclick=\"return false;\" class=\"btn btn-sm premium-delete\" data-pricing-id=\"" . $premiumLevel->id . "\"><i class=\"fas fa-minus-circle text-danger\"></i></a></td>\n</tr>";
    }
    if ($maxLevel) {
        $markup = floatval($maxLevel->markup);
        $output .= "<tr>\n    <input type=\"hidden\" name=\"ids[]\" value=\"" . $maxLevel->id . "\" />\n    <td>\n        <input type=\"text\" class=\"form-control max-amount\" disabled=\"disabled\" value=\">= " . $maxAmount . "\" />\n        <input type=\"hidden\" name=\"to[" . $maxLevel->id . "]\" value=\"-1\" />\n    </td>\n    <td>\n        <div class=\"input-group\">\n            <input type=\"text\" class=\"form-control\" name=\"markup[" . $maxLevel->id . "]\" value=\"" . $markup . "\" placeholder=\"%\" />\n            <div class=\"input-group-addon\">%</div>\n        </div>\n    </td>\n    <td></td>\n</tr>";
    }
    $output .= "            <tr><td colspan=\"3\"></td></tr>\n            <tr class=\"new\">\n                <td>\n                    <input type=\"text\" class=\"form-control to-amount\" name=\"to[new][]\" value=\"\" placeholder=\"New Price <\" data-toggle=\"tooltip\" data-placement=\"top\" data-trigger=\"manual\" title=\"" . $uniqueText . "\" />\n                </td>\n                <td>\n                    <div class=\"input-group\">\n                        <input type=\"text\" class=\"form-control\" name=\"markup[new][]\" value=\"\" placeholder=\"New Markup %\" />\n                        <div class=\"input-group-addon\">%</div>\n                    </div>\n                </td>\n                <td class=\"remove-clone\">\n                    <a href=\"#\" onclick=\"return false;\" class=\"btn btn-sm add-more-new\">\n                        <i class=\"fas fa-plus-circle text-success\"></i>\n                    </a>\n                </td>\n            </tr>\n        </table>\n    </div>\n</form>\n<script type=\"text/javascript\">\n    jQuery(document).ready(function() {\n        jQuery(document).off('change blur keyup', '.to-amount');\n        jQuery(document).on('change blur keyup', '.to-amount', function() {\n            var amounts = [];\n            jQuery('.to-amount').not(jQuery(this)).each(function () {\n                amounts.push(parseFloat(jQuery(this).val()).toFixed(2));\n            });\n            if (jQuery.inArray(parseFloat(jQuery(this).val()).toFixed(2), amounts) >= 0) {\n                jQuery('#btnSavePremium').attr('disabled', 'disabled').addClass('disabled');\n                jQuery(this).focus();\n                jQuery(this).tooltip('show');\n            } else {\n                jQuery('#btnSavePremium').removeAttr('disabled').removeClass('disabled');\n                jQuery(this).tooltip('hide');\n            }\n        });\n\n        jQuery(document).off('click', '.premium-delete');\n        jQuery(document).on('click', '.premium-delete', function() {\n\n            var self = jQuery(this);\n            self.attr('disabled', 'disabled').addClass('disabled');\n            WHMCS.http.jqClient.post(\n                window.location.pathname,\n                {\n                    token: '" . $jsToken . "',\n                    id: parseInt(self.data('pricing-id')),\n                    action: 'delete-premium'\n                },\n                function (data) {\n                    if (data.successMsg) {\n                        jQuery.growl.notice({ title: data.successMsgTitle, message: data.successMsg });\n                        self.parents('tr').slideUp('fast').remove();\n                        var maxValue = 0.00;\n                        jQuery('.to-amount').each(function () {\n                            if (parseFloat(jQuery(this).val()) > maxValue) {\n                                maxValue = parseFloat(jQuery(this).val());\n                            }\n                        });\n                        jQuery('.max-amount').val('>= ' + maxValue.toFixed(2));\n                    } else {\n                        jQuery.growl.warning({ title: data.errorMsgTitle, message: data.errorMsg });\n                        self.removeAttr('disabled').removeClass('disabled');\n                    }\n                },\n                'json'\n            );\n        });\n        jQuery(document).off('click', '.add-more-new');\n        jQuery(document).on('click', '.add-more-new', function() {\n            jQuery('tr.new').clone().appendTo(jQuery(this).parents('table')).removeClass('new')\n                .find('.remove-clone').html('').end()\n                .find('input').val('').end();\n        });\n    });\n</script>";
    $aInt->jsonResponse(array_merge(array("body" => $output), $saveOutput));
}
if ($action == "lookup-provider") {
    $registrarProviders = WHMCS\Domains\DomainLookup\Provider::getAvailableRegistrarProviders();
    if (App::isInRequest("provider")) {
        check_token("WHMCS.admin.default");
        $premiumSupport = false;
        $imgPath = (new WHMCS\View\Asset(WHMCS\Utility\Environment\WebHelper::getBaseUrl(ROOTDIR, $_SERVER["SCRIPT_NAME"])))->getImgPath();
        if (array_key_exists(App::getFromRequest("provider"), $registrarProviders)) {
            $premiumSupport = true;
            WHMCS\Config\Setting::setValue("domainLookupProvider", "Registrar");
            WHMCS\Config\Setting::setValue("domainLookupRegistrar", App::getFromRequest("provider"));
            $thisProvider = $registrarProviders[App::getFromRequest("provider")];
            if ($thisProvider["logo"]) {
                $lookupRegistrar = "<img id=\"imgLookupRegistrar\" src=\"" . $thisProvider["logo"] . "\">";
            } else {
                $lookupRegistrar = $thisProvider["name"];
            }
            if (!$thisProvider["suggestionSettings"]) {
                $aInt->jsonResponse(array("successMsg" => AdminLang::trans("global.changesuccess"), "successMsgTitle" => AdminLang::trans("global.success"), "logo" => $lookupRegistrar, "dismiss" => true, "premiumSupport" => $premiumSupport));
            }
        } else {
            if (App::getFromRequest("provider") == "WhmcsDomains") {
                WHMCS\Config\Setting::setValue("domainLookupProvider", "WhmcsDomains");
                WHMCS\Config\Setting::setValue("domainLookupRegistrar", "");
                $lookupRegistrar = "<img src=\"" . $imgPath . "/lookup/whmcs-namespinning-large.png\" />";
            } else {
                WHMCS\Config\Setting::setValue("domainLookupProvider", "WhmcsWhois");
                WHMCS\Config\Setting::setValue("domainLookupRegistrar", "");
                $lookupRegistrar = "<img src=\"" . $imgPath . "/lookup/standard-whois.png\">";
            }
        }
        $aInt->jsonResponse(array("successMsg" => AdminLang::trans("global.changesuccess"), "successMsgTitle" => AdminLang::trans("global.success"), "logo" => $lookupRegistrar, "url" => "configdomainlookup.php?action=configure", "title" => "Configure Lookup Provider", "submitlabel" => AdminLang::trans("global.save"), "submitId" => "btnSaveLookupConfiguration", "premiumSupport" => $premiumSupport));
    }
    $primaryProviders = array("whmcsdomains" => array("name" => "WhmcsDomains", "logo" => "/lookup/whmcs-namespinning.png", "description" => "Fastest lookup times with high quality and relevant name suggestions accross multiple TLDs + multi-language support.", "recommended" => true), "whois" => array("name" => "WhmcsWhois", "displayName" => "Standard WHOIS", "description" => "Domain availability checks using the standard WHOIS protocol.<br><br>Provides results for the requested TLD along with other TLDs you select, but no automated SLD suggestions."), "registrar" => array("name" => "Registrar", "displayName" => "Domain Registrar", "description" => "Use a domain registrar of your choice to perform domain availability checks.<br><br>Features vary depending upon the registrar being used."));
    $output = array();
    foreach ($primaryProviders as $provider) {
        $isActive = WHMCS\Config\Setting::getValue("domainLookupProvider") == $provider["name"];
        $output[] = "<div class=\"col-sm-4\"><div class=\"lookup-provider bordered" . ($isActive ? " active" : "") . "\" data-provider=\"" . $provider["name"] . "\">" . "<div class=\"logo\">" . (isset($provider["logo"]) ? "<img src=\"" . (new WHMCS\View\Asset(WHMCS\Utility\Environment\WebHelper::getBaseUrl(ROOTDIR, $_SERVER["SCRIPT_NAME"])))->getImgPath() . $provider["logo"] . "\" class=\"img-responsive\">" : "<h2>" . $provider["displayName"] . "</h2>") . "</div>" . ($provider["recommended"] ? "<span class=\"label label-info\">Recommended</span>" : "") . "<p>" . $provider["description"] . "</p>" . "<button class=\"btn btn-default btn-sm\">Select</button>" . "</div></div>";
    }
    $registrarsOutput = array();
    foreach ($registrarProviders as $registrarName => $registrarProvider) {
        $registrarsOutput[] = "<li role=\"presentation\" class=\"" . ($registrarName == WHMCS\Config\Setting::getValue("domainLookupRegistrar") ? "active" : "") . "\"><a href=\"#\" data-provider=\"" . $registrarName . "\">" . $registrarProvider["name"] . "</a></li>";
    }
    $output = "<div class=\"row row-lookup-providers\">" . implode($output) . "</div>\n\n<div class=\"lookup-providers-registrars" . (WHMCS\Config\Setting::getValue("domainLookupProvider") == "Registrar" ? "" : " hidden") . "\">\n    <h3>Choose a registrar...</h3>\n    <ul class=\"nav nav-pills\">\n        " . implode($registrarsOutput) . "\n    </ul>\n</div>" . "<script>\n    jQuery(document).ready(function() {\n        jQuery(document).off('click', '.lookup-provider, .lookup-providers-registrars a');\n        jQuery(document).on('click', '.lookup-provider, .lookup-providers-registrars a', function() {\n\n            var self = jQuery(this);\n            var provider = self.data('provider');\n\n            jQuery('.lookup-provider').removeClass('active');\n            self.addClass('active');\n\n            if (provider == 'Registrar') {\n                if (jQuery('.lookup-providers-registrars').hasClass('hidden')) {\n                    jQuery('.lookup-providers-registrars').hide().removeClass('hidden');\n                }\n                jQuery('.lookup-providers-registrars').slideDown();\n                return;\n            }\n\n            WHMCS.http.jqClient.post(\n                window.location.pathname,\n                {\n                    token: '" . $jsToken . "',\n                    provider: provider,\n                    action: 'lookup-provider'\n                },\n                function (data) {\n                    if (data.successMsg) {\n                        jQuery('.selected-provider').html(data.logo);\n                        updateAjaxModal(data);\n                        var toggle = jQuery('.premium-toggle-switch');\n                        if (data.premiumSupport) {\n                            toggle.bootstrapSwitch('disabled', false);\n                        } else {\n                            toggle.bootstrapSwitch('state', false);\n                            toggle.bootstrapSwitch('disabled', true);\n                        }\n                    } else {\n                        jQuery.growl.warning({ title: data.errorMsgTitle, message: data.errorMsg });\n                    }\n                },\n                'json'\n            );\n        });\n    });\n</script>";
    $aInt->jsonResponse(array("body" => $output));
}
if ($action == "duplicatetld") {
    check_token("WHMCS.admin.default");
    $newtld = trim($newtld);
    if (!$tld || !$newtld) {
        redir("error=emptytld");
    }
    if (substr($newtld, 0, 1) != ".") {
        $newtld = "." . $newtld;
    }
    if (get_query_val("tbldomainpricing", "id", array("extension" => $newtld))) {
        redir("error=" . str_replace("%s", $newtld, $aInt->lang("domains", "extensionalreadyexist")));
    }
    $tlddata = get_query_vals("tbldomainpricing", "id,dnsmanagement, emailforwarding, idprotection, eppcode, autoreg", array("extension" => $tld));
    $relid = $tlddata["id"];
    $newtlddata = array();
    $newtlddata["extension"] = $newtld;
    $newtlddata["dnsmanagement"] = $tlddata["dnsmanagement"];
    $newtlddata["emailforwarding"] = $tlddata["emailforwarding"];
    $newtlddata["idprotection"] = $tlddata["idprotection"];
    $newtlddata["eppcode"] = $tlddata["eppcode"];
    $newtlddata["autoreg"] = $tlddata["autoreg"];
    $newtlddata["order"] = get_query_val("tbldomainpricing", "MAX(`order`)", "") + 1;
    $newrelid = insert_query("tbldomainpricing", $newtlddata);
    $regpricingresult = select_query("tblpricing", "*", array("relid" => $relid, "type" => "domainregister"));
    while ($regpricingdata = mysql_fetch_assoc($regpricingresult)) {
        unset($regpricingdata["id"]);
        $regpricingdata["relid"] = $newrelid;
        insert_query("tblpricing", $regpricingdata);
    }
    $transferpricingresult = select_query("tblpricing", "*", array("relid" => $relid, "type" => "domaintransfer"));
    while ($transferpricingdata = mysql_fetch_assoc($transferpricingresult)) {
        unset($transferpricingdata["id"]);
        $transferpricingdata["relid"] = $newrelid;
        insert_query("tblpricing", $transferpricingdata);
    }
    $renewpricingresult = select_query("tblpricing", "*", array("relid" => $relid, "type" => "domainrenew"));
    while ($renewpricingdata = mysql_fetch_assoc($renewpricingresult)) {
        unset($renewpricingdata["id"]);
        $renewpricingdata["relid"] = $newrelid;
        insert_query("tblpricing", $renewpricingdata);
    }
    logAdminActivity("Domain Pricing TLD Duplicated: " . $tld . " to " . $newtld);
    run_hook("TopLevelDomainAdd", array("tld" => $newtlddata["extension"], "supportsDnsManagement" => (bool) $newtlddata["dnsmanagement"], "supportsEmailForwarding" => (bool) $newtlddata["emailforwarding"], "supportsIdProtection" => (bool) $newtlddata["idprotection"], "requiresEppCode" => (bool) $newtlddata["eppcode"], "automaticRegistrar" => $newtlddata["autoreg"]));
    $response = new WHMCS\Http\JsonResponse(array("body" => "<script type=\"text/javascript\">window.location.replace(\"configdomains.php?success=true\");</script>", "dismiss" => true));
    $response->send();
    WHMCS\Terminus::getInstance()->doExit();
}
if ($action == "resetpricing") {
    check_token("WHMCS.admin.default");
    $id = $_GET["id"];
    $cugroupid = $_GET["cugroupid"];
    if (!$cugroupid) {
        redir("action=editpricing&id=" . $id);
    }
    $clientGroup = WHMCS\Database\Capsule::table("tblclientgroups")->find($cugroupid);
    $domainTld = WHMCS\Database\Capsule::table("tbldomainpricing")->find($id);
    $result0 = select_query("tblclientgroups", "id,groupname", "", "groupname", "ASC");
    $result = select_query("tblcurrencies", "", "", "code", "ASC");
    while ($data = mysql_fetch_assoc($result)) {
        $curr_id = $data["id"];
        $curr_code = $data["code"];
        $currenciesarray[$curr_id] = $curr_code;
    }
    foreach ($currenciesarray as $curr_id => $curr_code) {
        $regresult2_baseslab = select_query("tblpricing", "", array("type" => "domainregister", "tsetupfee" => "0", "currency" => $curr_id, "relid" => $id));
        $regvalues = mysql_fetch_assoc($regresult2_baseslab);
        update_query("tblpricing", array("msetupfee" => $regvalues["msetupfee"], "qsetupfee" => $regvalues["qsetupfee"], "ssetupfee" => $regvalues["ssetupfee"], "asetupfee" => $regvalues["asetupfee"], "bsetupfee" => $regvalues["bsetupfee"], "monthly" => $regvalues["monthly"], "quarterly" => $regvalues["quarterly"], "semiannually" => $regvalues["semiannually"], "annually" => $regvalues["annually"], "biennially" => $regvalues["biennially"]), array("type" => "domainregister", "tsetupfee" => $cugroupid, "currency" => $curr_id, "relid" => $id));
        $transresult2_baseslab = select_query("tblpricing", "", array("type" => "domaintransfer", "tsetupfee" => "0", "currency" => $curr_id, "relid" => $id));
        $transvalues = mysql_fetch_assoc($transresult2_baseslab);
        update_query("tblpricing", array("msetupfee" => $transvalues["msetupfee"], "qsetupfee" => $transvalues["qsetupfee"], "ssetupfee" => $transvalues["ssetupfee"], "asetupfee" => $transvalues["asetupfee"], "bsetupfee" => $transvalues["bsetupfee"], "monthly" => $transvalues["monthly"], "quarterly" => $transvalues["quarterly"], "semiannually" => $transvalues["semiannually"], "annually" => $transvalues["annually"], "biennially" => $transvalues["biennially"]), array("type" => "domaintransfer", "tsetupfee" => $cugroupid, "currency" => $curr_id, "relid" => $id));
        $renewresult2_baseslab = select_query("tblpricing", "", array("type" => "domainrenew", "tsetupfee" => "0", "currency" => $curr_id, "relid" => $id));
        $renewvalues = mysql_fetch_assoc($renewresult2_baseslab);
        update_query("tblpricing", array("msetupfee" => $renewvalues["msetupfee"], "qsetupfee" => $renewvalues["qsetupfee"], "ssetupfee" => $renewvalues["ssetupfee"], "asetupfee" => $renewvalues["asetupfee"], "bsetupfee" => $renewvalues["bsetupfee"], "monthly" => $renewvalues["monthly"], "quarterly" => $renewvalues["quarterly"], "semiannually" => $renewvalues["semiannually"], "annually" => $renewvalues["annually"], "biennially" => $renewvalues["biennially"]), array("type" => "domainrenew", "tsetupfee" => $cugroupid, "currency" => $curr_id, "relid" => $id));
    }
    logAdminActivity("Domain Pricing Slab Reset: '" . $domainTld->extension . "' - '" . $clientGroup->groupname . "'");
    redir("action=editpricing&id=" . $id . "&selectedcugroupid=" . $cugroupid . "&resetcomplete=true");
}
if ($action == "deactivateslab") {
    check_token("WHMCS.admin.default");
    $id = $_GET["id"];
    $cugroupid = $_GET["cugroupid"];
    $clientGroup = WHMCS\Database\Capsule::table("tblclientgroups")->find($cugroupid);
    $domainTld = WHMCS\Database\Capsule::table("tbldomainpricing")->find($id);
    delete_query("tblpricing", array("type" => "domainregister", "tsetupfee" => $cugroupid, "relid" => $id));
    delete_query("tblpricing", array("type" => "domaintransfer", "tsetupfee" => $cugroupid, "relid" => $id));
    delete_query("tblpricing", array("type" => "domainrenew", "tsetupfee" => $cugroupid, "relid" => $id));
    logAdminActivity("Domain Pricing Slab Removed: '" . $domainTld->extension . "' - '" . $clientGroup->groupname . "'");
    redir("action=editpricing&id=" . $id . "&selectedcugroupid=" . $cugroupid . "&deactivated=true");
}
if ($action == "activateslab") {
    check_token("WHMCS.admin.default");
    $id = $_GET["id"];
    $cugroupid = $_GET["cugroupid"];
    $clientGroup = WHMCS\Database\Capsule::table("tblclientgroups")->find($cugroupid);
    $domainTld = WHMCS\Database\Capsule::table("tbldomainpricing")->find($id);
    $result = select_query("tblcurrencies", "", "", "code", "ASC");
    while ($data = mysql_fetch_assoc($result)) {
        $curr_id = $data["id"];
        $curr_code = $data["code"];
        $currenciesarray[$curr_id] = $curr_code;
    }
    foreach ($currenciesarray as $curr_id => $curr_code) {
        $result2 = select_query("tblpricing", "", array("type" => "domainregister", "tsetupfee" => $cugroupid, "currency" => $curr_id, "relid" => $id));
        $data = mysql_fetch_array($result2);
        $pricing_id = $data["id"];
        if (!$pricing_id) {
            $result2 = select_query("tblpricing", "", array("type" => "domainregister", "tsetupfee" => "0", "currency" => $curr_id, "relid" => $id));
            $data = mysql_fetch_array($result2);
            $pricing_id = $data["id"];
            if (!$pricing_id) {
                insert_query("tblpricing", array("type" => "domainregister", "currency" => $curr_id, "relid" => $id, "msetupfee" => "-1", "qsetupfee" => "-1", "ssetupfee" => "-1", "asetupfee" => "-1", "bsetupfee" => "-1", "monthly" => "-1", "quarterly" => "-1", "semiannually" => "-1", "annually" => "-1", "biennially" => "-1"));
            } else {
                insert_query("tblpricing", array("type" => "domainregister", "currency" => $curr_id, "relid" => $id, "tsetupfee" => $cugroupid, "msetupfee" => $data["msetupfee"], "qsetupfee" => $data["qsetupfee"], "ssetupfee" => $data["ssetupfee"], "asetupfee" => $data["asetupfee"], "bsetupfee" => $data["bsetupfee"], "monthly" => $data["monthly"], "quarterly" => $data["quarterly"], "semiannually" => $data["semiannually"], "annually" => $data["annually"], "biennially" => $data["biennially"]));
            }
        }
        $result2 = select_query("tblpricing", "", array("type" => "domaintransfer", "tsetupfee" => $cugroupid, "currency" => $curr_id, "relid" => $id));
        $data = mysql_fetch_array($result2);
        $pricing_id = $data["id"];
        if (!$pricing_id) {
            $result2 = select_query("tblpricing", "", array("type" => "domaintransfer", "tsetupfee" => "0", "currency" => $curr_id, "relid" => $id));
            $data = mysql_fetch_array($result2);
            $pricing_id = $data["id"];
            if (!$pricing_id) {
                insert_query("tblpricing", array("type" => "domaintransfer", "currency" => $curr_id, "relid" => $id));
            } else {
                insert_query("tblpricing", array("type" => "domaintransfer", "currency" => $curr_id, "relid" => $id, "tsetupfee" => $cugroupid, "msetupfee" => $data["msetupfee"], "qsetupfee" => $data["qsetupfee"], "ssetupfee" => $data["ssetupfee"], "asetupfee" => $data["asetupfee"], "bsetupfee" => $data["bsetupfee"], "monthly" => $data["monthly"], "quarterly" => $data["quarterly"], "semiannually" => $data["semiannually"], "annually" => $data["annually"], "biennially" => $data["biennially"]));
            }
        }
        $result2 = select_query("tblpricing", "", array("type" => "domainrenew", "tsetupfee" => $cugroupid, "currency" => $curr_id, "relid" => $id));
        $data = mysql_fetch_array($result2);
        $pricing_id = $data["id"];
        if (!$pricing_id) {
            $result2 = select_query("tblpricing", "", array("type" => "domainrenew", "tsetupfee" => "0", "currency" => $curr_id, "relid" => $id));
            $data = mysql_fetch_array($result2);
            $pricing_id = $data["id"];
            if (!$pricing_id) {
                insert_query("tblpricing", array("type" => "domainrenew", "currency" => $curr_id, "relid" => $id));
                insert_query("tblpricing", array("type" => "domainrenew", "currency" => $curr_id, "relid" => $id, "tsetupfee" => $cugroupid, "msetupfee" => $data["msetupfee"], "qsetupfee" => $data["qsetupfee"], "ssetupfee" => $data["ssetupfee"], "asetupfee" => $data["asetupfee"], "bsetupfee" => $data["bsetupfee"], "monthly" => $data["monthly"], "quarterly" => $data["quarterly"], "semiannually" => $data["semiannually"], "annually" => $data["annually"], "biennially" => $data["biennially"]));
            } else {
                insert_query("tblpricing", array("type" => "domainrenew", "currency" => $curr_id, "relid" => $id, "tsetupfee" => $cugroupid, "msetupfee" => $data["msetupfee"], "qsetupfee" => $data["qsetupfee"], "ssetupfee" => $data["ssetupfee"], "asetupfee" => $data["asetupfee"], "bsetupfee" => $data["bsetupfee"], "monthly" => $data["monthly"], "quarterly" => $data["quarterly"], "semiannually" => $data["semiannually"], "annually" => $data["annually"], "biennially" => $data["biennially"]));
            }
        }
    }
    logAdminActivity("Domain Pricing Slab Activated: '" . $domainTld->extension . "' - '" . $clientGroup->groupname . "'");
    redir("action=editpricing&id=" . $id . "&selectedcugroupid=" . $cugroupid . "&activated=true");
}
if ($action == "editpricing") {
    $cugrouparray = array();
    $clientGroup = NULL;
    if (isset($_GET["selectedcugroupid"])) {
        $selectedcugroupid = $_GET["selectedcugroupid"];
        $clientGroup = WHMCS\Database\Capsule::table("tblclientgroups")->find($selectedcugroupid);
    } else {
        $selectedcugroupid = 0;
    }
    $id = $whmcs->get_req_var("id");
    $domainTld = WHMCS\Database\Capsule::table("tbldomainpricing")->find($id);
    if ($register) {
        check_token("WHMCS.admin.default");
        foreach ($register as $cugroupid => $register_values) {
            foreach ($register_values as $curr_id => $values) {
                update_query("tblpricing", array("msetupfee" => $values[1], "qsetupfee" => $values[2], "ssetupfee" => $values[3], "asetupfee" => $values[4], "bsetupfee" => $values[5], "monthly" => $values[6], "quarterly" => $values[7], "semiannually" => $values[8], "annually" => $values[9], "biennially" => $values[10]), array("type" => "domainregister", "tsetupfee" => $selectedcugroupid, "currency" => $curr_id, "relid" => $id));
            }
        }
        foreach ($transfer as $cugroupid => $transfer_values) {
            foreach ($transfer_values as $curr_id => $values) {
                update_query("tblpricing", array("msetupfee" => $values[1], "qsetupfee" => $values[2], "ssetupfee" => $values[3], "asetupfee" => $values[4], "bsetupfee" => $values[5], "monthly" => $values[6], "quarterly" => $values[7], "semiannually" => $values[8], "annually" => $values[9], "biennially" => $values[10]), array("type" => "domaintransfer", "tsetupfee" => $selectedcugroupid, "currency" => $curr_id, "relid" => $id));
            }
        }
        foreach ($renew as $cugroupid => $renew_values) {
            foreach ($renew_values as $curr_id => $values) {
                update_query("tblpricing", array("msetupfee" => $values[1], "qsetupfee" => $values[2], "ssetupfee" => $values[3], "asetupfee" => $values[4], "bsetupfee" => $values[5], "monthly" => $values[6], "quarterly" => $values[7], "semiannually" => $values[8], "annually" => $values[9], "biennially" => $values[10]), array("type" => "domainrenew", "tsetupfee" => $selectedcugroupid, "currency" => $curr_id, "relid" => $id));
            }
        }
        if ($clientGroup) {
            logAdminActivity("Domain Pricing Slab Modified: '" . $domainTld->extension . "' - '" . $clientGroup->groupname . "'");
        } else {
            logAdminActivity("Domain Pricing Modified: '" . $domainTld->extension . "'");
        }
        run_hook("TopLevelDomainPricingUpdate", array("tld" => $domainTld->extension));
        redir("action=editpricing&id=" . $id . "&updated=true" . ($cugroupid ? "&selectedcugroupid=" . $cugroupid : ""));
    }
    $result = select_query("tbldomainpricing", "", array("id" => $id));
    $data = mysql_fetch_array($result);
    $extension = $data["extension"];
    $aInt->title = $aInt->lang("domains", "pricetitle") . " " . $extension;
    ob_start();
    if (isset($_GET["activated"])) {
        infoBox($_ADMINLANG["domains"]["activatepricingslab"], $_ADMINLANG["global"]["changesuccessdesc"], "success");
    }
    if (isset($_GET["deactivated"])) {
        infoBox($_ADMINLANG["domains"]["deactivatepricingslab"], $_ADMINLANG["global"]["changesuccessdesc"], "success");
    }
    if (isset($_GET["resetcomplete"])) {
        infoBox($_ADMINLANG["domains"]["resetpricingslab"], $_ADMINLANG["global"]["changesuccessdesc"], "success");
    }
    if ($whmcs->get_req_var("updated")) {
        infoBox($_ADMINLANG["global"]["changesuccess"], $_ADMINLANG["global"]["changesuccessdesc"], "success");
    }
    echo $infobox;
    echo "\n<p>";
    echo $aInt->lang("domains", "checkBoxToEnable") . " " . $aInt->lang("domains", "leaveAtNegativeOne");
    echo "</p>\n\n";
    $result = select_query("tblclientgroups", "id,groupname", "", "groupname", "ASC");
    while ($data = mysql_fetch_assoc($result)) {
        $cugroupid = $data["id"];
        $cugroupname = $data["groupname"];
        $cugrouparray[$cugroupid] = $cugroupname;
    }
    $result = select_query("tblcurrencies", "", "", "code", "ASC");
    while ($data = mysql_fetch_assoc($result)) {
        $curr_id = $data["id"];
        $curr_code = $data["code"];
        $currenciesarray[$curr_id] = $curr_code;
    }
    foreach ($currenciesarray as $curr_id => $curr_code) {
        $result2 = select_query("tblpricing", "", array("type" => "domainregister", "tsetupfee" => $selectedcugroupid, "currency" => $curr_id, "relid" => $id));
        $data = mysql_fetch_array($result2);
        $pricing_id1a = $data["id"];
        if (!$pricing_id1a) {
            $result2 = select_query("tblpricing", "", array("type" => "domainregister", "tsetupfee" => "0", "currency" => $curr_id, "relid" => $id));
            $data = mysql_fetch_array($result2);
            $pricing_id1b = $data["id"];
            if (!$pricing_id1b) {
                $pricing_id1a = insert_query("tblpricing", array("type" => "domainregister", "currency" => $curr_id, "relid" => $id, "msetupfee" => "-1", "qsetupfee" => "-1", "ssetupfee" => "-1", "asetupfee" => "-1", "bsetupfee" => "-1", "monthly" => "-1", "quarterly" => "-1", "semiannually" => "-1", "annually" => "-1", "biennially" => "-1"));
            }
        }
        $result2 = select_query("tblpricing", "", array("type" => "domaintransfer", "tsetupfee" => $selectedcugroupid, "currency" => $curr_id, "relid" => $id));
        $data = mysql_fetch_array($result2);
        $pricing_id2a = $data["id"];
        if (!$pricing_id2a) {
            $result2 = select_query("tblpricing", "", array("type" => "domaintransfer", "tsetupfee" => "0", "currency" => $curr_id, "relid" => $id));
            $data = mysql_fetch_array($result2);
            $pricing_id2b = $data["id"];
            if (!$pricing_id2b) {
                $pricing_id2a = insert_query("tblpricing", array("type" => "domaintransfer", "currency" => $curr_id, "relid" => $id));
            }
        }
        $result2 = select_query("tblpricing", "", array("type" => "domainrenew", "tsetupfee" => $selectedcugroupid, "currency" => $curr_id, "relid" => $id));
        $data = mysql_fetch_array($result2);
        $pricing_id3a = $data["id"];
        if (!$pricing_id3a) {
            $result2 = select_query("tblpricing", "", array("type" => "domainrenew", "tsetupfee" => "0", "currency" => $curr_id, "relid" => $id));
            $data = mysql_fetch_array($result2);
            $pricing_id3b = $data["id"];
            if (!$pricing_id3b) {
                $pricing_id3a = insert_query("tblpricing", array("type" => "domainrenew", "currency" => $curr_id, "relid" => $id));
            }
        }
    }
    $jqueryCode .= "\$(\".pricingToggle\").click(function() {\n    var data = \$(this).attr(\"data\");\n\n    if (\$(this).is(\":checked\")) {\n        \$(\"input[name='register\" + data + \"']\").val(\"0.00\").show();\n        \$(\"input[name='transfer\" + data + \"']\").val(\"0.00\").show();\n        \$(\"input[name='renew\" + data + \"']\").val(\"0.00\").show();\n    } else {\n        \$(\"input[name='register\" + data + \"']\").val(\"-1.00\").hide();\n        \$(\"input[name='transfer\" + data + \"']\").val(\"-1.00\").hide();\n        \$(\"input[name='renew\" + data + \"']\").val(\"-1.00\").hide();\n    }\n});";
    echo "\n<form method=\"post\" action=\"";
    echo $_SERVER["PHP_SELF"];
    echo "?action=editpricing&id=";
    echo $id;
    echo "&selectedcugroupid=";
    echo $selectedcugroupid;
    echo "\">\n";
    $onChangeurl = $_SERVER["PHP_SELF"] . "?action=editpricing&id=" . $id . "&selectedcugroupid=";
    echo "<p align=\"center\">";
    echo $aInt->lang("domains", "pricingslabfor");
    echo " <select name=\"selectedcugroupid\" onchange=\"location.href='";
    echo $onChangeurl;
    echo "'+this.value;\" class=\"form-control select-inline\">\n<option value=\"0\">";
    echo $aInt->lang("domains", "defaultpricingslab");
    echo "</option>\n";
    if (is_array($cugrouparray)) {
        foreach ($cugrouparray as $cugrouparrayid => $cugrouparrayname) {
            echo "<option";
            if ($selectedcugroupid == $cugrouparrayid) {
                echo " selected=\"selected\"";
            }
            echo " value=\"" . $cugrouparrayid . "\">" . $cugrouparrayname . " " . $aInt->lang("fields", "clientgroup") . "</option>";
        }
    }
    echo "</select> <button type=\"button\" class=\"btn btn-info\" data-toggle=\"tooltip\" data-placement=\"bottom\" title=\"";
    echo $aInt->lang("domains", "slabsintro");
    echo "\"><i class=\"fas fa-question\"></i></button></p>\n\n";
    $noslabpricing = !$pricing_id1a || !$pricing_id2a || !$pricing_id3a ? true : false;
    if ($selectedcugroupid != 0) {
        echo "<p align=\"center\">";
        if ($noslabpricing) {
            echo "<a href=\"" . $_SERVER["PHP_SELF"] . "?action=activateslab&id=" . $id . "&cugroupid=" . $selectedcugroupid . generate_token("link") . "\" onclick=\"return confirm('" . $aInt->lang("domains", "activatepricingslabconfirm", 1) . "')\">";
        }
        echo $aInt->lang("domains", "activatepricingslab") . "</a> | ";
        if (!$noslabpricing) {
            echo "<a href=\"" . $_SERVER["PHP_SELF"] . "?action=deactivateslab&id=" . $id . "&cugroupid=" . $selectedcugroupid . generate_token("link") . "\" onclick=\"return confirm('" . $aInt->lang("domains", "deactivatepricingslabconfirm", 1) . "')\">";
        }
        echo $aInt->lang("domains", "deactivatepricingslab") . "</a> | ";
        if (!$noslabpricing) {
            echo "<a href=\"" . $_SERVER["PHP_SELF"] . "?action=resetpricing&id=" . $id . "&cugroupid=" . $selectedcugroupid . generate_token("link") . "\" onclick=\"return confirm('" . $aInt->lang("domains", "resetpricingslab", 1) . "')\">";
        }
        echo $aInt->lang("domains", "resetpricingslab") . "</a></p>";
    }
    $saveButton = "";
    if (!$noslabpricing) {
        $totalcurrencies = count($currenciesarray);
        echo "\n<table class=\"datatable\">\n<tr>\n    <th></th>\n    <th>";
        echo $aInt->lang("currencies", "currency");
        echo "</th>\n    <th>";
        echo $aInt->lang("global", "enable");
        echo "</th>\n    <th class=\"domain-pricing-head\">";
        echo $aInt->lang("domains", "actionreg");
        echo "</th>\n    <th class=\"domain-pricing-head\">";
        echo $aInt->lang("domains", "transfer");
        echo "</th>\n    <th class=\"domain-pricing-head\">";
        echo $aInt->lang("domains", "renewal");
        echo "</th>\n</tr>\n";
        $years = 1;
        while ($years <= 10) {
            echo "<tr class=\"domain-pricing-row\"><td rowspan=\"" . $totalcurrencies . "\" class=\"field-highlight text-nowrap text-center\"><b>" . $years . " " . $aInt->lang("domains", "years") . "</b></td>";
            $i = 0;
            foreach ($currenciesarray as $curr_id => $curr_code) {
                $result2_baseslab = select_query("tblpricing", "", array("type" => "domainregister", "tsetupfee" => $selectedcugroupid, "currency" => $curr_id, "relid" => $id));
                $regdata_baseslab = mysql_fetch_array($result2_baseslab);
                $register[$selectedcugroupid][$curr_id] = array(1 => $regdata_baseslab["msetupfee"], 2 => $regdata_baseslab["qsetupfee"], 3 => $regdata_baseslab["ssetupfee"], 4 => $regdata_baseslab["asetupfee"], 5 => $regdata_baseslab["bsetupfee"], 6 => $regdata_baseslab["monthly"], 7 => $regdata_baseslab["quarterly"], 8 => $regdata_baseslab["semiannually"], 9 => $regdata_baseslab["annually"], 10 => $regdata_baseslab["biennially"]);
                $transresult2_baseslab = select_query("tblpricing", "", array("type" => "domaintransfer", "tsetupfee" => $selectedcugroupid, "currency" => $curr_id, "relid" => $id));
                $transdata_baseslab = mysql_fetch_array($transresult2_baseslab);
                $transfer[$selectedcugroupid][$curr_id] = array(1 => $transdata_baseslab["msetupfee"], 2 => $transdata_baseslab["qsetupfee"], 3 => $transdata_baseslab["ssetupfee"], 4 => $transdata_baseslab["asetupfee"], 5 => $transdata_baseslab["bsetupfee"], 6 => $transdata_baseslab["monthly"], 7 => $transdata_baseslab["quarterly"], 8 => $transdata_baseslab["semiannually"], 9 => $transdata_baseslab["annually"], 10 => $transdata_baseslab["biennially"]);
                $result2_baseslab = select_query("tblpricing", "", array("type" => "domainrenew", "tsetupfee" => $selectedcugroupid, "currency" => $curr_id, "relid" => $id));
                $rendata_baseslab = mysql_fetch_array($result2_baseslab);
                $renew[$selectedcugroupid][$curr_id] = array(1 => $rendata_baseslab["msetupfee"], 2 => $rendata_baseslab["qsetupfee"], 3 => $rendata_baseslab["ssetupfee"], 4 => $rendata_baseslab["asetupfee"], 5 => $rendata_baseslab["bsetupfee"], 6 => $rendata_baseslab["monthly"], 7 => $rendata_baseslab["quarterly"], 8 => $rendata_baseslab["semiannually"], 9 => $rendata_baseslab["annually"], 10 => $rendata_baseslab["biennially"]);
                if (0 < $i) {
                    echo "</tr><tr class=\"domain-pricing-row\">";
                }
                $enableName = "enable[" . $selectedcugroupid . "][" . $curr_id . "][" . $years . "]";
                $registerName = "register[" . $selectedcugroupid . "][" . $curr_id . "][" . $years . "]";
                $registerValue = $register[$selectedcugroupid][$curr_id][$years];
                $transferName = "transfer[" . $selectedcugroupid . "][" . $curr_id . "][" . $years . "]";
                $transferValue = $transfer[$selectedcugroupid][$curr_id][$years];
                $renewName = "renew[" . $selectedcugroupid . "][" . $curr_id . "][" . $years . "]";
                $renewValue = $renew[$selectedcugroupid][$curr_id][$years];
                $toggleCheck = $register[$selectedcugroupid][$curr_id][$years] == "-1" ? "" : " checked=\"checked\"";
                $toggleData = "[" . $selectedcugroupid . "][" . $curr_id . "][" . $years . "]";
                $hideInput = $register[$selectedcugroupid][$curr_id][$years] == "-1" ? " style=\"display:none;\"" : "";
                $output = "<td class=\"text-center\">\n    " . $curr_code . "\n</td>\n<td class=\"text-center\">\n    <input type=\"checkbox\" name=\"" . $enableName . "\" class=\"pricingToggle\" data=\"" . $toggleData . "\"" . $toggleCheck . " class=\"form-control\" />\n</td>\n<td class=\"text-center\">\n    <input type=\"text\" name=\"" . $registerName . "\" value=\"" . $registerValue . "\" size=\"10\"" . $hideInput . " class=\"form-control\" />\n</td>\n<td class=\"text-center\">\n    <input type=\"text\" name=\"" . $transferName . "\" value=\"" . $transferValue . "\" size=\"10\"" . $hideInput . " class=\"form-control\" />\n</td>\n<td class=\"text-center\">\n    <input type=\"text\" name=\"" . $renewName . "\" value=\"" . $renewValue . "\" size=\"10\"" . $hideInput . " class=\"form-control\" />\n</td>";
                echo $output;
                $i++;
            }
            echo "</tr>";
            $years += 1;
        }
        echo "</table>\n\n";
        $saveButton = "<input type=\"submit\" value=\"" . $aInt->lang("global", "savechanges") . "\" class=\"btn btn-primary\" />";
    }
    echo "    <div class=\"btn-container\">\n        ";
    echo $saveButton;
    echo "        <input type=\"button\" value=\"";
    echo $aInt->lang("addons", "closewindow");
    echo "\" onclick=\"window.close();\" class=\"button btn btn-default\" />\n    </div>\n</form>\n\n<script>\n\$(function () {\n    \$('[data-toggle=\"tooltip\"]').tooltip();\n})\n</script>\n\n";
    $content = ob_get_contents();
    ob_end_clean();
    $aInt->content = $content;
    $aInt->jquerycode = $jqueryCode;
    $aInt->displayPopUp();
    exit;
} else {
    if ($action == "delete") {
        check_token("WHMCS.admin.default");
        $id = $whmcs->get_req_var("id");
        $domainTld = WHMCS\Database\Capsule::table("tbldomainpricing")->find($id);
        delete_query("tbldomainpricing", array("id" => $id));
        foreach (array("domainregister", "domaintransfer", "domainrenew") as $type) {
            delete_query("tblpricing", array("type" => $type, "relid" => $id));
        }
        logAdminActivity("Domain Pricing TLD Removed: '" . $domainTld->extension . "'");
        $spotlightTlds = WHMCS\Config\Setting::getValue("SpotlightTLDs");
        if ($spotlightTlds) {
            $spotlightTlds = explode(",", $spotlightTlds);
            if (in_array($domainTld->extension, $spotlightTlds)) {
                $spotlightTlds = array_flip($spotlightTlds);
                unset($spotlightTlds[$domainTld->extension]);
                $spotlightTlds = array_flip($spotlightTlds);
                WHMCS\Config\Setting::setValue("SpotlightTLDs", implode(",", $spotlightTlds));
            }
        }
        $whoisTlds = WHMCS\Domains\DomainLookup\Settings::whereRegistrar("WhmcsWhois")->whereSetting("suggestTlds")->first();
        if ($whoisTlds) {
            $tlds = explode(",", $whoisTlds->value);
            if (in_array($domainTld->extension, $tlds)) {
                $tlds = array_flip($tlds);
                unset($tlds[$domainTld->extension]);
                $tlds = array_flip($tlds);
                $whoisTlds->value = implode(",", $tlds);
                $whoisTlds->save();
            }
        }
        run_hook("TopLevelDomainDelete", array("tld" => $domainTld->extension));
        redir("deleted=true");
    }
    if ($action == "save") {
        check_token("WHMCS.admin.default");
        $tld = App::getFromRequest("tld");
        $dns = App::getFromRequest("dns");
        $email = App::getFromRequest("email");
        $idprot = App::getFromRequest("idprot");
        $eppcode = App::getFromRequest("eppcode");
        $autoreg = App::getFromRequest("autoreg");
        $tldGroup = App::getFromRequest("tldGroup");
        $customGracePeriod = App::getFromRequest("grace");
        $gracePeriodFee = App::getFromRequest("grace_fee");
        $customRedemptionGracePeriod = App::getFromRequest("redemption");
        $redemptionGracePeriodFee = App::getFromRequest("redemption_grace_fee");
        $modifiedTlds = array();
        foreach ($tld as $id => $extension) {
            $domainTld = WHMCS\Domains\Extension::find($id);
            $extension = trim(strtolower($extension));
            $gracePeriod = $customGracePeriod[$id];
            $tldGracePeriodFee = $gracePeriodFee[$id];
            $redemptionGracePeriod = $customRedemptionGracePeriod[$id];
            $tldRedemptionGracePeriodFee = $redemptionGracePeriodFee[$id];
            if (!$gracePeriod && $gracePeriod !== "0" || $gracePeriod < 0) {
                $gracePeriod = -1;
            }
            if ($tldGracePeriodFee === "" || $tldGracePeriodFee < -1) {
                $tldGracePeriodFee = -1;
            }
            if (!$redemptionGracePeriod && $redemptionGracePeriod !== "0" || $redemptionGracePeriod < 0) {
                $redemptionGracePeriod = -1;
            }
            if ($tldRedemptionGracePeriodFee === "" || $tldRedemptionGracePeriodFee < -1) {
                $tldRedemptionGracePeriodFee = -1;
            }
            $changed = false;
            if ($domainTld->extension != $extension) {
                $newExtension = WHMCS\Domains\Extension::where("extension", $extension)->first();
                if ($newExtension && $newExtension->id != $id) {
                    redir("error=" . str_replace("%s", $extension, AdminLang::trans("domains.extensionalreadyexist")));
                }
                logAdminActivity("Domain Pricing TLD Modified: '" . $domainTld->extension . "' to '" . $extension . "'");
                $domainTld->extension = $extension;
                if (!in_array($extension, $modifiedTlds)) {
                    $modifiedTlds[] = $extension;
                }
                $changed = true;
            }
            if ($domainTld->supportsDnsManagement != (bool) $dns[$id] || $domainTld->supportsEmailForwarding != (bool) $email[$id] || $domainTld->supportsIdProtection != (bool) $idprot[$id] || $domainTld->requiresEppCode != (bool) $eppcode[$id] || $domainTld->autoRegistrationRegistrar != $autoreg[$id] || $domainTld->group != $tldGroup[$id] || $domainTld->gracePeriod != $gracePeriod || $domainTld->gracePeriodFee != $tldGracePeriodFee || $domainTld->redemptionGracePeriod != $redemptionGracePeriod || $domainTld->redemptionGracePeriodFee != $tldRedemptionGracePeriodFee) {
                $domainTld->supportsDnsManagement = (bool) $dns[$id];
                $domainTld->supportsEmailForwarding = (bool) $email[$id];
                $domainTld->supportsIdProtection = (bool) $idprot[$id];
                $domainTld->requiresEppCode = (bool) $eppcode[$id];
                $domainTld->autoRegistrationRegistrar = $autoreg[$id];
                $domainTld->group = $tldGroup[$id];
                $domainTld->gracePeriod = $gracePeriod;
                $domainTld->gracePeriodFee = $tldGracePeriodFee;
                $domainTld->redemptionGracePeriod = $redemptionGracePeriod;
                $domainTld->redemptionGracePeriodFee = $tldRedemptionGracePeriodFee;
                logAdminActivity("Domain Pricing Options Modified: '" . $extension . "'");
                if (!in_array($extension, $modifiedTlds)) {
                    $modifiedTlds[] = $extension;
                }
                $changed = true;
            }
            if ($changed) {
                $domainTld->save();
            }
        }
        run_hook("TopLevelDomainUpdate", array("modifiedTlds" => $modifiedTlds));
        $newtld = trim(App::getFromRequest("newtld"));
        if ($newtld) {
            $newdns = (bool) App::getFromRequest("newdns");
            $newemail = (bool) App::getFromRequest("newemail");
            $newidprot = (bool) App::getFromRequest("newidprot");
            $neweppcode = (bool) App::getFromRequest("neweppcode");
            $newautoreg = App::getFromRequest("newautoreg");
            $tldGroup = $tldGroup["new"];
            try {
                $domainsSetup = new WHMCS\Admin\Setup\Domains();
                $domainsSetup->addTld($newtld, $newdns, $newemail, $newidprot, $neweppcode, $newautoreg, -1, $tldGroup);
                run_hook("TopLevelDomainAdd", array("tld" => $newtld, "supportsDnsManagement" => (bool) $newdns, "supportsEmailForwarding" => (bool) $newemail, "supportsIdProtection" => (bool) $newidprot, "requiresEppCode" => (bool) $neweppcode, "automaticRegistrar" => $newautoreg));
            } catch (WHMCS\Exception $e) {
                $error = str_replace("%s", $newtld, $aInt->lang("domains", "extensionalreadyexist"));
            }
        }
        if ($error) {
            redir("error=" . $error);
        }
        redir("success=true");
    }
    if ($action == "saveaddons") {
        check_token("WHMCS.admin.default");
        foreach ($_POST["currency"] as $currency_id => $pricing) {
            update_query("tblpricing", $pricing, array("type" => "domainaddons", "currency" => $currency_id, "relid" => 0));
        }
        logAdminActivity("Domain Pricing Addons Modified");
        redir("success=true");
    }
    if ($action == "sort-spotlight" || $action == "remove-spotlight") {
        check_token("WHMCS.admin.default");
        $spotlightTlds = App::getFromRequest("order");
        $removeTld = App::getFromRequest("tld");
        if (!$spotlightTlds) {
            $spotlightTlds = explode(",", WHMCS\Config\Setting::getValue("SpotlightTLDs"));
        }
        $items = array();
        foreach ($spotlightTlds as $tld) {
            if (!$tld || $tld == $removeTld) {
                continue;
            }
            $items[] = $tld;
        }
        $outputItems = array_pad($items, 8, "0");
        $items = implode(",", $items);
        WHMCS\Config\Setting::setValue("SpotlightTLDs", $items);
        $items = implode(",", $outputItems);
        $aInt->setBodyContent(array("items" => $items));
        $aInt->output();
        WHMCS\Terminus::getInstance()->doExit();
    }
    if ($action == "add-spotlight") {
        check_token("WHMCS.admin.default");
        $tld = App::getFromRequest("tld");
        $spotlightTlds = explode(",", WHMCS\Config\Setting::getValue("SpotlightTLDs"));
        if (!in_array($tld, $spotlightTlds)) {
            $spotlightTlds[] = $tld;
        }
        $items = array();
        foreach ($spotlightTlds as $savedTld) {
            if (!$savedTld) {
                continue;
            }
            $items[] = $savedTld;
        }
        $outputItems = array_pad($items, 8, "0");
        $items = implode(",", $items);
        WHMCS\Config\Setting::setValue("SpotlightTLDs", $items);
        $items = implode(",", $outputItems);
        $aInt->setBodyContent(array("items" => $items));
        $aInt->output();
        WHMCS\Terminus::getInstance()->doExit();
    }
    $aInt->deleteJSConfirm("doDelete", "domains", "delsureextension", "?action=delete&id=");
    echo WHMCS\View\Asset::jsInclude("Sortable.min.js");
    $growlNotificationAdd = WHMCS\View\Helper::jsGrowlNotification("success", "global.success", "global.changesuccessadded");
    $growlNotificationReorder = WHMCS\View\Helper::jsGrowlNotification("success", "global.success", "global.changesuccesssorting");
    $growlNotificationDelete = WHMCS\View\Helper::jsGrowlNotification("success", "global.success", "global.changesuccessdeleted");
    $jqueryCode .= "\n\$('#domainpricing').tableDnD({\n    onDrop: function(table, row) {\n        var thisRow = jQuery(\"#\" + row.id),\n            tldId = thisRow.data(\"tld-id\"),\n            tldIds = [];\n\n            jQuery(\".domain-pricing-row\").each(function (index) {\n                var thisId = jQuery(this).data(\"tld-id\");\n                if (typeof thisId !== \"undefined\") {\n                    tldIds.push(thisId);\n                    var currentRow = jQuery(\"#dp-\" + thisId),\n                        extraRow = jQuery(\"#dpe-\" + thisId),\n                        clonedRow = extraRow.clone();\n                        extraRow.remove();\n                        currentRow.after(clonedRow);\n                }\n            });\n        WHMCS.http.jqClient.post(\n            \"configdomains.php\",\n             { action: \"saveorder\", pricing: tldIds, token: \"" . generate_token("plain") . "\" },\n             function (data) {\n                " . $growlNotificationReorder . "\n             }\n        );\n    },\n    dragHandle: \"sortcol\"\n});\n\nvar spotlightTldSortable = Sortable.create(\n    spotlightTlds,\n    {\n        animation: 150,\n        ghostClass: 'ghost',\n        filter: '.remove-tld',\n        dataIdAttr: 'data-tld',\n        store: {\n            /**\n             * Get the order of elements. Called once during initialization.\n             * @param   {Sortable}  sortable\n             * @returns {Array}\n             */\n            get: function (sortable) {\n                //do nothing\n                spotlight = sortable.toArray();\n                return [];\n            },\n\n            /**\n             * Save the order of elements. Called onEnd (when the item is dropped).\n             * @param {Sortable}  sortable\n             */\n            set: function (sortable) {\n                var order = sortable.toArray();\n                if (order.toString() == spotlight.toString()) {\n                    return;\n                }\n                var post = WHMCS.http.jqClient.post(\n                    window.location.pathname,\n                    {\n                        action: \"sort-spotlight\",\n                        order: order,\n                        token: \"" . generate_token("plain") . "\"\n                    }\n                );\n                post.done(\n                    function(data) {\n                        " . $growlNotificationReorder . "\n                        spotlight = data.items.split(',');\n                        shouldAddSpotlightBeDisabled();\n                    }\n                );\n                spotlight = order;\n            }\n        },\n        onMove: function (evt) {\n            var item = evt.dragged,\n                destination = evt.related;\n\n            if (jQuery(item).text().trim() == '' || jQuery(destination).text().trim() == '') {\n                return false;\n            }\n        },\n        onFilter: function (evt) {\n            var item = evt.item;\n            var tld = jQuery(item).attr('data-tld');\n            var post = WHMCS.http.jqClient.post(\n                window.location.pathname,\n                {\n                    action: \"remove-spotlight\",\n                    tld: tld,\n                    token: \"" . generate_token("plain") . "\"\n                }\n            );\n            jQuery(item).attr('data-tld', '0');\n            post.done(\n                function(data) {\n                    " . $growlNotificationDelete . "\n                    spotlight = data.items.split(',');\n\n                    var spotlightEntries = jQuery('.spotlight-tld');\n\n                    for (var i = 0; i < spotlight.length; i++) {\n                        if (spotlight[i] == '0') {\n                            jQuery(spotlightEntries[i]).attr('data-tld', '0');\n                            jQuery(spotlightEntries[i]).find('span').html('<i class=\"fas fa-times remove-tld hidden pull-right\"> </i>');\n                        } else {\n                            jQuery(spotlightEntries[i]).attr('data-tld', spotlight[i]);\n                            jQuery(spotlightEntries[i]).find('span').html('<i class=\"fas fa-times remove-tld pull-right\"> </i>' + spotlight[i]);\n                        }\n                    }\n                    shouldAddSpotlightBeDisabled();\n                }\n            );\n            jQuery(item).find('span').html('<i class=\"fas fa-times remove-tld pull-right hidden\"> </i>');\n        }\n    }\n);\n\njQuery('.add-spotlight').click(function() {\n    jQuery(this).attr('disabled', 'disabled');\n    var tld = jQuery(this).closest('div.spotlight').find('input').val();\n\n    jQuery('.spotlight-tld').each(function (index) {\n        if (jQuery(this).text().trim() == '') {\n            jQuery(this).attr('data-tld', tld);\n            jQuery(this).find('span').html('<i class=\"fas fa-times remove-tld pull-right\"> </i>' + tld);\n\n            var post = WHMCS.http.jqClient.post(\n                window.location.pathname,\n                {\n                    action: \"add-spotlight\",\n                    tld: tld,\n                    token: \"" . generate_token("plain") . "\"\n                }\n            );\n            post.done(\n                function(data) {\n                    " . $growlNotificationAdd . "\n                    spotlight = data.items.split(',');\n                    shouldAddSpotlightBeDisabled();\n                }\n            );\n            return false;\n        }\n    });\n});\n\njQuery('.tld').on('focus', function() {\n    var id = jQuery(this).attr('name').substring(4).replace(']', '');\n    if (typeof tldValue[id] == 'undefined') {\n        tldValue[id] = jQuery(this).val();\n    }\n});\n\njQuery('.tld').on('keypress', function(e) {\n    var id = jQuery(this).attr('name').substring(4).replace(']', '');\n    if ((jQuery(this).val() + e.key) != tldValue[id]) {\n        jQuery(this).parent().find('button.add-spotlight').attr('disabled', 'disabled');\n    }\n});\n\njQuery('.tld').on('keyup', function(e) {\n    var id = jQuery(this).attr('name').substring(4).replace(']', '');\n    if ((jQuery(this).val()) == tldValue[id]) {\n        jQuery(this).parent().find('button.add-spotlight').removeAttr('disabled');\n    }\n    shouldAddSpotlightBeDisabled();\n});\n\nvar spotlight = spotlightTldSortable.toArray(),\n    tldValue = [];\nfunction shouldAddSpotlightBeDisabled()\n{\n    var count = 0,\n        current = null,\n        tldInputs = jQuery('input.tld'),\n        addButtons = jQuery('.add-spotlight');\n\n    addButtons.removeAttr('disabled');\n\n    for (var i = 0; i < spotlight.length; i++) {\n        current = spotlight[i].trim();\n\n        if (current != '0' && current != '') {\n            count++\n            tldInputs.each(function (index) {\n                if (jQuery(this).val() == current) {\n                    jQuery(this).parent().find('button.add-spotlight').attr('disabled', 'disabled');\n                }\n            });\n        }\n    }\n    if (count == 8) {\n        addButtons.attr('disabled', 'disabled');\n    }\n}\nshouldAddSpotlightBeDisabled();\n\njQuery('.tld-group li a').on('click', function(e) {\n    e.preventDefault();\n    var tldId = jQuery(this).parent().parent().data('tld-id'),\n        group = jQuery(this).find('span').attr('data-group'),\n        spanHtml = jQuery(this).html();\n    if (group != 'none') {\n        jQuery('#dp-' + tldId).first('td').find('div.selected-tld-group').html(spanHtml);\n    } else {\n        jQuery('#dp-' + tldId).first('td').find('div.selected-tld-group').html('');\n    }\n    jQuery('input[name=\"tldGroup[' + tldId + ']\"]').val(group);\n});\n\njQuery(\".tld-settings\").on(\"click\", function(e) {\n    var tldId = jQuery(this).data(\"tld-id\"),\n        tableRow = jQuery(\"#dpe-\" + tldId),\n        isHidden = tableRow.hasClass(\"hidden\");\n    if (isHidden) {\n        tableRow.hide().removeClass(\"hidden\").fadeIn(\"slow\");\n    } else {\n        tableRow.fadeOut(\"slow\").addClass(\"hidden\");\n    }\n});\n";
    $jsCode = "\nfunction openPricingPopup(id)\n{\n    var winLeft = (screen.width - 560) / 2;\n    var winTop = (screen.height - 600) / 2;\n    var winProperties = 'height=600,width=560,top=' + winTop + ',left=' + winLeft + ',scrollbars=yes';\n    win = window.open('configdomains.php?action=editpricing&id=' + id, 'domainpricing', winProperties);\n    if (parseInt(navigator.appVersion) >= 4) {\n        win.window.focus();\n    }\n}\n";
    $imgPath = (new WHMCS\View\Asset(WHMCS\Utility\Environment\WebHelper::getBaseUrl(ROOTDIR, $_SERVER["SCRIPT_NAME"])))->getImgPath();
    $spotlightTlds = WHMCS\Config\Setting::getValue("SpotlightTLDs");
    $spotlightTlds = $spotlightTlds ? explode(",", WHMCS\Config\Setting::getValue("SpotlightTLDs")) : array();
    $spotlightTlds = array_pad($spotlightTlds, 8, "0");
    $lookupProvider = WHMCS\Config\Setting::getValue("domainLookupProvider");
    $lookupRegistrar = WHMCS\Config\Setting::getValue("domainLookupRegistrar");
    $toggleDisabled = false;
    if ($lookupProvider == "WhmcsDomains") {
        $lookupRegistrar = "<img src=\"" . $imgPath . "/lookup/whmcs-namespinning-large.png\">";
    } else {
        if ($lookupProvider == "Registrar") {
            $registrar = new WHMCS\Module\Registrar();
            $registrar->load($lookupRegistrar);
            if ($lookupRegistrarLogo = $registrar->getLogoFilename()) {
                $lookupRegistrar = "<img id=\"imgLookupRegistrar\" src=\"" . $lookupRegistrarLogo . "\">";
            }
        } else {
            $toggleDisabled = true;
            $lookupRegistrar = "<img src=\"" . $imgPath . "/lookup/standard-whois.png\">";
        }
    }
    if ($success) {
        infoBox($aInt->lang("global", "changesuccess"), $aInt->lang("global", "changesuccessdesc"), "success");
    }
    if ($error) {
        if ($error == "emptytld") {
            $error = $aInt->lang("domains", "sourcenewtldempty");
        }
        infoBox($aInt->lang("global", "erroroccurred"), $error, "error");
    }
    echo $infobox;
    echo "<p>" . $aInt->lang("domains", "pricinginfo") . "</p>";
    echo "    <div class=\"spotlight-title\">\n        ";
    echo AdminLang::trans("domains.spotlightTLDs");
    echo " <i class=\"far fa-lightbulb\"></i>\n    </div>\n    <div class=\"spotlight-tlds\">\n        <div id=\"spotlightTlds\" class=\"spotlight-tld-container\">\n            ";
    foreach ($spotlightTlds as $tld) {
        $iClass = "";
        $tldText = $tld;
        if ($tld === "0") {
            $tldText = "";
            $iClass = " hidden";
        }
        echo "<div class=\"spotlight-tld\" data-tld=\"" . $tld . "\">\n    <span>\n        <i class=\"fas fa-times remove-tld pull-right" . $iclass . "\"> </i>\n        " . $tldText . "\n    </span>\n</div>";
    }
    echo "        </div>\n        <div class=\"clearfix\"></div>\n    </div>\n\n<div class=\"row\">\n    <div class=\"col-sm-12 col-lg-9\">\n\n<form method=\"post\" action=\"";
    echo $_SERVER["PHP_SELF"];
    echo "\">\n<input type=\"hidden\" name=\"action\" value=\"save\" />\n\n<div class=\"tablebg\">\n<table class=\"datatable\" width=\"100%\" border=\"0\" cellspacing=\"1\" cellpadding=\"3\" id=\"domainpricing\">\n    <tr class=\"nodrop\">\n        <th width=\"20\"><input type=\"checkbox\" id=\"checkAllTld\" /></th>\n        <th class=\"th-tld\">";
    echo $aInt->lang("fields", "tld");
    echo "</th>\n        <th>";
    echo $aInt->lang("global", "pricing");
    echo "</th>\n        <th>";
    echo $aInt->lang("domains", "dnsmanagement");
    echo "</th>\n        <th>";
    echo $aInt->lang("domains", "emailforwarding");
    echo "</th>\n        <th>";
    echo $aInt->lang("domains", "idprotection");
    echo "</th>\n        <th>";
    echo $aInt->lang("domains", "eppcode");
    echo "</th>\n        <th>";
    echo $aInt->lang("domains", "autoreg");
    echo "</th>\n        <th width=\"20\"></th>\n        <th width=\"20\"></th>\n        <th width=\"20\"></th>\n    </tr>\n    ";
    $defaultCurrency = WHMCS\Database\Capsule::table("tblcurrencies")->find(1);
    foreach (WHMCS\Domains\Extension::all() as $domainExtension) {
        $id = $domainExtension->id;
        $extension = $domainExtension->extension;
        $autoreg = $domainExtension->autoRegistrationRegistrar;
        $dnsmanagement = $domainExtension->supportsDnsManagement;
        $emailforwarding = $domainExtension->supportsEmailForwarding;
        $idprotection = $domainExtension->supportsIdProtection;
        $eppcode = $domainExtension->requiresEppCode;
        $order = $domainExtension->order;
        $group = $domainExtension->group;
        $customGracePeriod = $domainExtension->getRawAttribute("grace_period");
        $defaultGracePeriod = $domainExtension->defaultGracePeriod;
        $gracePeriodFee = 0 <= $domainExtension->getRawAttribute("grace_period_fee") ? format_as_currency($domainExtension->getRawAttribute("grace_period_fee")) : -1;
        $customRedemptionGracePeriod = $domainExtension->getRawAttribute("redemption_grace_period");
        $defaultRedemptionGracePeriod = $domainExtension->defaultRedemptionGracePeriod;
        $redemptionGracePeriodFee = 0 <= $domainExtension->getRawAttribute("redemption_grace_period_fee") ? format_as_currency($domainExtension->getRawAttribute("redemption_grace_period_fee")) : -1;
        $groupInfo = WHMCS\View\Helper::getDomainGroupLabel($group);
        echo "<tr id=\"dp-";
        echo $id;
        echo "\" data-tld-id=\"";
        echo $id;
        echo "\" class=\"domain-pricing-row\">\n<td><input type=\"checkbox\" name=\"tldId[]\" data-tld=\"";
        echo $extension;
        echo "\" value=\"";
        echo $id;
        echo "\" /></td>\n<td>\n    <div class=\"input-group spotlight\">\n        <div class=\"selected-tld-group-container\"><div class=\"selected-tld-group\">";
        echo $groupInfo;
        echo "</div></div>\n        <input type=\"text\" class=\"form-control tld\" name=\"tld[";
        echo $id;
        echo "]\" value=\"";
        echo $extension;
        echo "\">\n        <input type=\"hidden\" name=\"tldGroup[";
        echo $id;
        echo "]\" value=\"";
        echo $group;
        echo "\">\n        <div class=\"input-group-btn add-spotlight-btn-group\">\n            <button id=\"btnAddSpotlight";
        echo $id;
        echo "\" type=\"button\" class=\"btn btn-info add-spotlight\" value=\"";
        echo AdminLang::trans("domains.addSpotlight");
        echo "\">\n                <i class=\"far fa-lightbulb\"></i>\n            </button>\n            <button id=\"tldGroup";
        echo $id;
        echo "\" type=\"button\" class=\"btn btn-default dropdown-toggle\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">\n                <span class=\"caret\"></span>\n                <span class=\"sr-only\">Toggle Dropdown</span>\n            </button>\n            <ul id=\"tldGroupOptions";
        echo $id;
        echo "\" class=\"dropdown-menu tld-group\" data-tld-id=\"";
        echo $id;
        echo "\" role=\"menu\">\n                <li>\n                    <a href=\"#\">\n                        <span class=\"label label-default\" data-group=\"none\">";
        echo AdminLang::trans("domains.noGroup");
        echo "</span>\n                    </a>\n                </li>\n                <li>\n                    <a href=\"#\">\n                        <span class=\"label label-danger\" data-group=\"hot\">";
        echo AdminLang::trans("domains.hot");
        echo "</span>\n                    </a>\n                </li>\n                <li>\n                    <a href=\"#\">\n                        <span class=\"label label-success\" data-group=\"new\">";
        echo AdminLang::trans("domains.new");
        echo "</span>\n                    </a>\n                </li>\n                <li>\n                    <a href=\"#\">\n                        <span class=\"label label-warning\" data-group=\"sale\">";
        echo AdminLang::trans("domains.sale");
        echo "</span>\n                    </a>\n                </li>\n            </ul>\n        </div>\n    </div>\n</td>\n<td class=\"text-center\"><a href=\"#\" class=\"btn btn-default btn-sm\" onclick=\"openPricingPopup(";
        echo $id;
        echo ");return false\">";
        echo $aInt->lang("domains", "openpricing");
        echo "</a></td>\n<td class=\"text-center\"><input type=\"checkbox\" data-type=\"dns\" data-tld=\"";
        echo $extension;
        echo "\" name=\"dns[";
        echo $id;
        echo "]\"";
        if ($dnsmanagement) {
            echo " checked";
        }
        echo "></td>\n<td class=\"text-center\"><input type=\"checkbox\" data-type=\"email\" data-tld=\"";
        echo $extension;
        echo "\" name=\"email[";
        echo $id;
        echo "]\"";
        if ($emailforwarding) {
            echo " checked";
        }
        echo "></td>\n<td class=\"text-center\"><input type=\"checkbox\" data-type=\"idprot\" data-tld=\"";
        echo $extension;
        echo "\" name=\"idprot[";
        echo $id;
        echo "]\"";
        if ($idprotection) {
            echo " checked";
        }
        echo "></td>\n<td class=\"text-center\"><input type=\"checkbox\" data-type=\"eppcode\" data-tld=\"";
        echo $extension;
        echo "\" name=\"eppcode[";
        echo $id;
        echo "]\"";
        if ($eppcode) {
            echo " checked";
        }
        echo "></td>\n<td class=\"text-center\">";
        echo getRegistrarsDropdownMenu($autoreg, "autoreg[" . $id . "]");
        echo "</td>\n<td class=\"text-center\">\n    <a href=\"#\" class=\"tld-settings\" data-tld-id=\"";
        echo $id;
        echo "\" onclick=\"return false;\">\n        <i class=\"fas fa-cog\" aria-hidden=\"true\"></i>\n        <span class=\"sr-only\">";
        echo AdminLang::trans("global.settings");
        echo "</span>\n    </a>\n</td>\n<td class=\"sortcol\">&nbsp;</td>\n<td><a href=\"#\" onClick=\"doDelete('";
        echo $id;
        echo "');return false\"><img src=\"images/icons/delete.png\" width=\"16\" height=\"16\" border=\"0\" alt=\"";
        echo $aInt->lang("global", "delete");
        echo "\"></a></td>\n</tr>\n<tr id=\"dpe-";
        echo $id;
        echo "\" class=\"domain-extra hidden nodrop\">\n    <td colspan=\"11\" align=\"center\" valign=\"middle\">\n        <table class=\"datatable\">\n            <tr>\n                <td></td>\n                <th>";
        echo AdminLang::trans("domains.gracePeriod");
        echo "</th>\n                <th>";
        echo AdminLang::trans("domains.redemptionPeriod");
        echo "</th>\n            </tr>\n            <tr>\n                <td>";
        echo AdminLang::trans("domains.duration");
        echo "</td>\n                <td align=\"right\">\n                    <div class=\"input-group\">\n                        <input id=\"gracePeriod";
        echo $id;
        echo "\" name=\"grace[";
        echo $id;
        echo "]\" type=\"number\" class=\"form-control input-inline input-125\" placeholder=\"";
        echo $defaultGracePeriod;
        echo " (Default)\" min=\"0\" value=\"";
        echo 0 <= $customGracePeriod ? $customGracePeriod : "";
        echo "\">\n                        <span class=\"input-group-addon\">";
        echo AdminLang::trans("calendar.days");
        echo "</span>\n                    </div>\n                </td>\n                <td align=\"right\">\n                    <div class=\"input-group\">\n                        <input id=\"redemptionGracePeriod";
        echo $id;
        echo "\" name=\"redemption[";
        echo $id;
        echo "]\" type=\"number\" class=\"form-control input-inline input-125\" placeholder=\"";
        echo $defaultRedemptionGracePeriod;
        echo " (Default)\" min=\"0\" value=\"";
        echo 0 <= $customRedemptionGracePeriod ? $customRedemptionGracePeriod : "";
        echo "\">\n                        <span class=\"input-group-addon\">";
        echo AdminLang::trans("calendar.days");
        echo "</span>\n                    </div>\n                </td>\n            </tr>\n            <tr>\n                <td>\n                    ";
        echo AdminLang::trans("domains.fee");
        echo "                </td>\n                <td align=\"right\">\n                    <div class=\"input-group\">\n                        <span class=\"input-group-addon\">";
        echo $defaultCurrency->prefix;
        echo "</span>\n                        <input id=\"gracePeriodFee";
        echo $id;
        echo "\" name=\"grace_fee[";
        echo $id;
        echo "]\" type=\"number\" class=\"form-control input-inline input-125\" placeholder=\"0.00\" min=\"-1\" step=\"any\" value=\"";
        echo 0 <= $gracePeriodFee ? $gracePeriodFee : "";
        echo "\">\n                        <span class=\"input-group-addon\">";
        echo $defaultCurrency->suffix;
        echo "</span>\n                    </div>\n                </td>\n                <td align=\"right\">\n                    <div class=\"input-group\">\n                        <span class=\"input-group-addon\">";
        echo $defaultCurrency->prefix;
        echo "</span>\n                        <input id=\"redemptionGracePeriodFee";
        echo $id;
        echo "\" name=\"redemption_grace_fee[";
        echo $id;
        echo "]\" type=\"number\" class=\"form-control input-inline input-125\" placeholder=\"0.00\" min=\"-1\" step=\"any\" value=\"";
        echo 0 <= $redemptionGracePeriodFee ? $redemptionGracePeriodFee : "";
        echo "\">\n                        <span class=\"input-group-addon\">";
        echo $defaultCurrency->suffix;
        echo "</span>\n                    </div>\n                </td>\n            </tr>\n        </table>\n    </td>\n</tr>\n";
    }
    echo "<tr class=\"addtld nodrop\" id=\"dp-new\">\n<td></td>\n<td>\n    <div class=\"input-group spotlight\">\n        <div class=\"selected-tld-group-container\"><div class=\"selected-tld-group\"></div></div>\n        <input type=\"text\" name=\"newtld\" class=\"form-control tld\" placeholder=\"";
    echo $aInt->lang("domains", "addtld");
    echo "\" />\n        <input type=\"hidden\" name=\"tldGroup[new]\" value=\"\">\n        <div class=\"input-group-btn add-spotlight-btn-group\">\n            <button id=\"newTldGroup\" type=\"button\" class=\"btn btn-default dropdown-toggle\" data-toggle=\"dropdown\" aria-haspopup=\"true\" aria-expanded=\"false\">\n                <span class=\"caret\"></span>\n                <span class=\"sr-only\">Toggle Dropdown</span>\n            </button>\n            <ul id=\"newTldGroupOptions\" class=\"dropdown-menu tld-group\" data-tld-id=\"new\" role=\"menu\">\n                <li>\n                    <a href=\"#\">\n                        <span class=\"label label-default\" data-group=\"none\">";
    echo AdminLang::trans("domains.noGroup");
    echo "</span>\n                    </a>\n                </li>\n                <li>\n                    <a href=\"#\">\n                        <span class=\"label label-danger\" data-group=\"hot\">";
    echo AdminLang::trans("domains.hot");
    echo "</span>\n                    </a>\n                </li>\n                <li>\n                    <a href=\"#\">\n                        <span class=\"label label-success\" data-group=\"new\">";
    echo AdminLang::trans("domains.new");
    echo "</span>\n                    </a>\n                </li>\n                <li>\n                    <a href=\"#\">\n                        <span class=\"label label-warning\" data-group=\"sale\">";
    echo AdminLang::trans("domains.sale");
    echo "</span>\n                    </a>\n                </li>\n            </ul>\n        </div>\n    </div>\n</td>\n<td></td>\n<td class=\"text-center\"><input type=\"checkbox\" name=\"newdns\"></td>\n<td class=\"text-center\"><input type=\"checkbox\" name=\"newemail\"></td>\n<td class=\"text-center\"><input type=\"checkbox\" name=\"newidprot\"></td>\n<td class=\"text-center\"><input type=\"checkbox\" name=\"neweppcode\"></td>\n<td class=\"text-center\">";
    echo getRegistrarsDropdownMenu($autoreg, "newautoreg");
    echo "</td>\n<td colspan=\"3\"></td>\n</tr>\n</table>\n    <p align=\"center\">\n        <input id=\"btnSaveChanges\" type=\"submit\" value=\"";
    echo $aInt->lang("global", "savechanges");
    echo "\" class=\"btn btn-primary\" />\n        <a type=\"button\" class=\"btn btn-default open-modal\" href=\"configdomains.php?action=showduplicatetld\" data-btn-submit-label=\"";
    echo AdminLang::trans("global.submit");
    echo "\" data-modal-title=\"";
    echo AdminLang::trans("domains.duplicatetld");
    echo "\" data-btn-submit-id=\"btnDuplicateTld\">\n            ";
    echo AdminLang::trans("domains.duplicatetld");
    echo "        </a>\n    </p>\n\n</div>\n</form>\n</div>\n    <div class=\"col-xs-3\"></div>\n    <div class=\"col-lg-3 col-xs-6\">\n        ";
    $currencies = WHMCS\Database\Capsule::table("tblcurrencies")->pluck("code", "id");
    $domainAddons = array();
    foreach ($currencies as $currencyId => $currencyCode) {
        $domainAddonPricing = WHMCS\Database\Capsule::table("tblpricing")->where("type", "=", "domainaddons")->where("relid", "=", 0)->where("currency", "=", $currencyId)->first();
        if (!$domainAddonPricing) {
            WHMCS\Database\Capsule::table("tblpricing")->insert(array("type" => "domainaddons", "currency" => $currencyId, "relid" => 0));
            $domainAddonPricing = WHMCS\Database\Capsule::table("tblpricing")->where("type", "=", "domainaddons")->where("relid", "=", 0)->where("currency", "=", $currencyId)->first(array("msetupfee", "qsetupfee", "ssetupfee"));
        }
        $domainAddons["dnsManagement"][$currencyId] = array("field" => "msetupfee", "price" => $domainAddonPricing->msetupfee);
        $domainAddons["emailForwarding"][$currencyId] = array("field" => "qsetupfee", "price" => $domainAddonPricing->qsetupfee);
        $domainAddons["idProtection"][$currencyId] = array("field" => "ssetupfee", "price" => $domainAddonPricing->ssetupfee);
    }
    echo "\n        <br>\n\n        <div class=\"panel panel-default\">\n            <div class=\"panel-heading\">\n                <h3 class=\"panel-title\">";
    echo AdminLang::trans("domains.lookupProvider");
    echo "</h3>\n            </div>\n            <div class=\"panel-body\">\n\n                <div class=\"text-center selected-provider\">\n                    ";
    echo $lookupRegistrar;
    echo "                </div>\n\n                <div class=\"row\">\n                    <div class=\"col-md-6 text-center\">\n                        <a id=\"changeLookupProvider\" class=\"btn btn-sm btn-default btn-block open-modal\" href=\"configdomains.php?action=lookup-provider\" data-modal-title=\"Choose Lookup Provider\" onclick=\"return false;\" data-modal-size=\"modal-lg\">";
    echo AdminLang::trans("global.change");
    echo "</a>\n                    </div>\n                    <div class=\"col-md-6 text-center\">\n                        <a id=\"configureLookupProvider\" class=\"btn btn-sm btn-default btn-block open-modal\" href=\"configdomainlookup.php?action=configure\" data-modal-title=\"Configure Lookup Provider\" data-btn-submit-id=\"btnSaveLookupConfiguration\" data-btn-submit-label=\"Save\" onclick=\"return false;\" data-modal-size=\"modal-lg\">";
    echo AdminLang::trans("global.configure");
    echo "</a>\n                    </div>\n                </div>\n\n            </div>\n        </div>\n\n        <div class=\"panel panel-default\">\n            <div class=\"panel-heading\">\n                <h3 class=\"panel-title\">";
    echo AdminLang::trans("domains.premiumDomains");
    echo "</h3>\n            </div>\n            <div class=\"panel-body\">\n\n                <div class=\"row\">\n                    <div class=\"col-md-6 text-center\">\n                        <label class=\"checkbox-inline\">\n                            <input type=\"checkbox\" name=\"premiumDomains\" class=\"premium-toggle-switch\"";
    echo WHMCS\Config\Setting::getValue("PremiumDomains") ? " checked=\"checked\"" : "";
    echo " />\n                        </label>\n                    </div>\n                    <div class=\"col-md-6 text-center premium-domain-option\">\n                        <a id=\"linkConfigurePremiumMarkup\" href=\"configdomains.php?action=premium-levels\" class=\"btn btn-default btn-sm btn-block open-modal";
    echo WHMCS\Config\Setting::getValue("PremiumDomains") ? "" : " disabled";
    echo "\" data-modal-title=\"";
    echo AdminLang::trans("domains.premiumLevelsTitle");
    echo "\" data-btn-submit-id=\"btnSavePremium\" data-btn-submit-label=\"";
    echo AdminLang::trans("global.save");
    echo "\">";
    echo AdminLang::trans("global.configure");
    echo "</a>\n                    </div>\n                </div>\n\n            </div>\n        </div>\n\n        <div class=\"panel panel-default\">\n            <div class=\"panel-heading\">\n                <h3 class=\"panel-title\">";
    echo AdminLang::trans("domains.bulkManagement");
    echo "</h3>\n            </div>\n            <div class=\"panel-body\">\n                <div class=\"row\">\n                    <div class=\"col-md-12\">\n                        <div class=\"domain-addon-title text-center bottom-margin-5\">\n                            ";
    echo AdminLang::trans("global.pricing");
    echo "                        </div>\n                        <div class=\"row\">\n                            <div class=\"col-md-12 text-center bottom-margin-5\">1 ";
    echo AdminLang::Trans("domains.year");
    echo "</div>\n                            <div class=\"col-md-12 row bottom-margin-5\">\n                                <div class=\"col-md-4\">\n                                    ";
    echo AdminLang::trans("domains.register");
    echo "                                </div>\n                                <div class=\"col-md-8\">\n                                    <div class=\"input-group\">\n                                        <span class=\"input-group-addon\">";
    echo $defaultCurrency->prefix;
    echo "</span>\n                                        <input type=\"number\" step=\"any\" id=\"inputOneYearRegistrationBulk\" class=\"form-control input-inline\" placeholder=\"0.00\" min=\"-1\" />\n                                        <span class=\"input-group-addon\">";
    echo $defaultCurrency->suffix;
    echo "</span>\n                                    </div>\n                                </div>\n                            </div>\n                            <div class=\"col-md-12 row bottom-margin-5\">\n                                <div class=\"col-md-4\">\n                                    ";
    echo AdminLang::trans("domains.transfer");
    echo "                                </div>\n                                <div class=\"col-md-8\">\n                                    <div class=\"input-group\">\n                                        <span class=\"input-group-addon\">";
    echo $defaultCurrency->prefix;
    echo "</span>\n                                        <input type=\"number\" step=\"any\" id=\"inputOneYearTransferBulk\" class=\"form-control input-inline\" placeholder=\"0.00\" min=\"-1\" />\n                                        <span class=\"input-group-addon\">";
    echo $defaultCurrency->suffix;
    echo "</span>\n                                    </div>\n                                </div>\n                            </div>\n                            <div class=\"col-md-12 row bottom-margin-5\">\n                                <div class=\"col-md-4\">\n                                    ";
    echo AdminLang::trans("domains.renewal");
    echo "                                </div>\n                                <div class=\"col-md-8\">\n                                    <div class=\"input-group\">\n                                        <span class=\"input-group-addon\">";
    echo $defaultCurrency->prefix;
    echo "</span>\n                                        <input type=\"number\" step=\"any\" id=\"inputOneYearRenewBulk\" class=\"form-control input-inline\" placeholder=\"0.00\" min=\"-1\" />\n                                        <span class=\"input-group-addon\">";
    echo $defaultCurrency->suffix;
    echo "</span>\n                                    </div>\n                                </div>\n                            </div>\n                            <div class=\"col-md-12 bottom-margin-5\">\n                                <label class=\"checkbox-inline\">\n                                    <input type=\"checkbox\" id=\"inputCopyPricingBulk\" value=\"1\" />\n                                    ";
    echo AdminLang::trans("domains.bulkYearsDescription");
    echo "                                </label>\n                            </div>\n                        </div>\n                        <div class=\"domain-addon-title text-center bottom-margin-5\">\n                            ";
    echo AdminLang::trans("domains.gracePeriod");
    echo "                        </div>\n                        <div class=\"row\">\n                            <div class=\"col-md-12 row bottom-margin-5\">\n                                <div class=\"col-md-4\">\n                                    ";
    echo AdminLang::trans("domains.duration");
    echo "                                </div>\n                                <div class=\"col-md-8\">\n                                    <div class=\"input-group\">\n                                        <input type=\"number\" id=\"inputGraceDurationBulk\" class=\"form-control input-inline\" min=\"0\" />\n                                        <span class=\"input-group-addon\">";
    echo AdminLang::trans("calendar.days");
    echo "</span>\n                                    </div>\n                                </div>\n                            </div>\n                            <div class=\"col-md-12 row bottom-margin-5\">\n                                <div class=\"col-md-4\">\n                                    ";
    echo AdminLang::trans("domains.fee");
    echo "                                </div>\n                                <div class=\"col-md-8\">\n                                    <div class=\"input-group\">\n                                        <span class=\"input-group-addon\">";
    echo $defaultCurrency->prefix;
    echo "</span>\n                                        <input type=\"number\" step=\"any\" id=\"inputGraceFeeBulk\" class=\"form-control input-inline\" placeholder=\"0.00\" min=\"-1\" />\n                                        <span class=\"input-group-addon\">";
    echo $defaultCurrency->suffix;
    echo "</span>\n                                    </div>\n                                </div>\n                            </div>\n                        </div>\n                        <div class=\"domain-addon-title text-center bottom-margin-5\">\n                            ";
    echo AdminLang::trans("domains.redemptionPeriod");
    echo "                        </div>\n                        <div class=\"row\">\n                            <div class=\"col-md-12 row bottom-margin-5\">\n                                <div class=\"col-md-4\">\n                                    ";
    echo AdminLang::trans("domains.duration");
    echo "                                </div>\n                                <div class=\"col-md-8\">\n                                    <div class=\"input-group\">\n                                        <input type=\"number\" id=\"inputRedemptionDurationBulk\" class=\"form-control input-inline\" min=\"0\" />\n                                        <span class=\"input-group-addon\">";
    echo AdminLang::trans("calendar.days");
    echo "</span>\n                                    </div>\n                                </div>\n                            </div>\n                            <div class=\"col-md-12 row bottom-margin-5\">\n                                <div class=\"col-md-4\">\n                                    ";
    echo AdminLang::trans("domains.fee");
    echo "                                </div>\n                                <div class=\"col-md-8\">\n                                    <div class=\"input-group\">\n                                        <span class=\"input-group-addon\">";
    echo $defaultCurrency->prefix;
    echo "</span>\n                                        <input type=\"number\" step=\"any\" id=\"inputRedemptionFeeBulk\" class=\"form-control input-inline\" placeholder=\"0.00\" min=\"-1\" />\n                                        <span class=\"input-group-addon\">";
    echo $defaultCurrency->suffix;
    echo "</span>\n                                    </div>\n                                </div>\n                            </div>\n                        </div>\n                    </div>\n                </div>\n\n                <div class=\"text-center\">\n                    <button id=\"btnBulkManagementSave\" type=\"button\" class=\"btn btn-default\">\n                        ";
    echo AdminLang::trans("global.savechanges");
    echo "                    </button>\n                </div>\n            </div>\n        </div>\n\n        <div class=\"panel panel-default\">\n            <div class=\"panel-heading\">\n                <h3 class=\"panel-title\">";
    echo AdminLang::trans("domains.domainaddons");
    echo "</h3>\n            </div>\n            <div class=\"panel-body\">\n                <form method=\"post\" action=\"";
    echo $_SERVER["PHP_SELF"];
    echo "\">\n                    <input type=\"hidden\" name=\"action\" value=\"saveaddons\" />\n                    <div class=\"row\">\n                        <div class=\"col-md-12\">\n                            ";
    foreach ($domainAddons as $type => $domainAddonData) {
        echo "                                    <div class=\"domain-addon-title text-center bottom-margin-5\">\n                                        ";
        echo AdminLang::trans("domains." . strtolower($type));
        echo "                                    </div>\n                                    ";
        foreach ($domainAddonData as $currencyId => $priceInfo) {
            echo "<div class=\"row bottom-margin-5\">\n    <div class=\"col-md-6 text-center\">\n        <strong>" . $currencies[$currencyId] . "</strong>\n    </div>\n    <div class=\"col-md-6 text-center\">\n        <input type=\"text\" name=\"currency[" . $currencyId . "][" . $priceInfo["field"] . "]\" class=\"form-control input-100 text-center\" value=\"" . $priceInfo["price"] . "\" />\n    </div>\n</div>";
        }
    }
    echo "                        </div>\n                    </div>\n                    <div class=\"text-center\">\n                        <input type=\"submit\" value=\"";
    echo AdminLang::trans("global.savechanges");
    echo "\" class=\"btn btn-default\" />\n                    </div>\n                </form>\n            </div>\n        </div>\n    </div>\n</div>\n\n";
    echo WHMCS\View\Asset::jsInclude("jqueryro.js");
    echo "\n<style>\ntd.sortcol {\n    background-image: url(\"images/updown.gif\");\n    background-repeat: no-repeat;\n    background-position: center center;\n    cursor: move;\n}\ntable.datatable .tDnD_whileDrag td,table.datatable .addtld td {\n    background-color: #eeeeee;\n}\n</style>\n\n";
    echo $aInt->modal("DuplicateTld", $aInt->lang("domains", "duplicatetld"), $aInt->lang("global", "loading"), array(array("title" => AdminLang::trans("global.submit"), "onclick" => "\$(\"#duplicatetldform\").submit()", "class" => "btn-primary"), array("title" => AdminLang::trans("global.cancel"))));
    $token = generate_token("plain");
    $errorGrowl = WHMCS\View\Helper::jsGrowlNotification("error", AdminLang::trans("global.unexpectedError"), AdminLang::trans("domains.enablePremiumDomainFailure"));
    $toggleDisabled = (int) $toggleDisabled;
    $errorSwal = array("title" => AdminLang::trans("global.error"), "text" => AdminLang::trans("domains.massUpdateError"), "confirmButtonText" => AdminLang::trans("global.ok"));
    $massUpdateSwal = array("title" => AdminLang::trans("global.areYouSure"), "text" => AdminLang::trans("domains.massUpdateConfirm"), "confirmButtonText" => AdminLang::trans("global.yes"), "cancelButtonText" => AdminLang::trans("global.no"));
    $massUpdateUrl = routePath("admin-tld-mass-configuration");
    $massUpdateErrorGrowl = WHMCS\View\Helper::jsGrowlNotification("error", AdminLang::trans("global.error"), AdminLang::trans("global.unexpectedError"));
    $jqueryCode .= "\njQuery(\"#checkAllTld\").click(function (event) {\n    jQuery(event.target).parents(\".datatable\").find(\"input[name='tldId[]']\").prop(\"checked\", this.checked);\n});\n\njQuery(\".premium-toggle-switch\").bootstrapSwitch(\n    {\n        'size': 'small',\n        'disabled': " . $toggleDisabled . ",\n        'onColor': 'success',\n        'onSwitchChange': function(event, state)\n        {\n            var validResponse = false;\n            WHMCS.http.jqClient.post(\n                window.location.pathname,\n                {\n                    action: 'toggle-premium',\n                    token: '" . $token . "',\n                    enable: state == true ? 1 : 0\n                },\n                function(data) {\n                    if (typeof data.success != 'undefined') {\n                        validResponse = true;\n                        if (state) {\n                            //Show things\n                            jQuery('.premium-domain-option').find('a').removeClass('disabled');\n                        } else {\n                            //Hide things\n                            jQuery('.premium-domain-option').find('a').addClass('disabled');\n                        }\n                    }\n\n                },\n                'json'\n            ).always(function() {\n                if (!validResponse) {\n                    " . $errorGrowl . "\n                }\n            });\n        }\n    }\n);\n\njQuery(document).on('click', '#btnBulkManagementSave', function (event)\n    {\n        event.preventDefault();\n        var selectedItems = jQuery(\"input[name='tldId[]']\"),\n            self = jQuery(this),\n            oneYearRegistration = jQuery('#inputOneYearRegistrationBulk').val(),\n            oneYearRenew = jQuery('#inputOneYearRenewBulk').val(),\n            oneYearTransfer = jQuery('#inputOneYearTransferBulk').val(),\n            graceDuration = jQuery('#inputGraceDurationBulk').val(),\n            graceFee = jQuery('#inputGraceFeeBulk').val(),\n            redemptionDuration = jQuery('#inputRedemptionDurationBulk').val(),\n            redemptionFee = jQuery('#inputRedemptionFeeBulk').val();\n\n        if (selectedItems.filter(':checked').length === 0\n            || (\n                (!oneYearRegistration && oneYearRegistration !== 0)\n                && (!oneYearRenew && oneYearRenew !== 0)\n                && (!oneYearTransfer && oneYearTransfer !== 0)\n                && (!graceDuration && graceDuration !== 0)\n                && (!graceFee && graceFee !== 0)\n                && (!redemptionDuration && redemptionDuration !== 0)\n                && (!redemptionFee && redemptionFee !== 0)\n            )\n        ) {\n            swal({\n                title: '" . $errorSwal["title"] . "',\n                html: true,\n                text: '" . $errorSwal["text"] . "',\n                type: 'error',\n                confirmButtonText: '" . $errorSwal["confirmButtonText"] . "'\n            });\n        } else {\n            var selectedTlds = [],\n                validResponse = false;\n            jQuery(\"input[name='tldId[]']:checked\").each(function() {\n                selectedTlds.push(parseInt(jQuery(this).val()));\n            });\n            swal(\n                {\n                    title: '" . $massUpdateSwal["title"] . "',\n                    html: true,\n                    text: '" . $massUpdateSwal["text"] . "',\n                    type: 'warning',\n                    showCancelButton: true,\n                    confirmButtonText: '" . $massUpdateSwal["confirmButtonText"] . "',\n                    cancelButtonText: '" . $massUpdateSwal["cancelButtonText"] . "'\n                },\n                function() {\n                    self.prop('disabled', true).addClass('disabled');\n                    WHMCS.http.jqClient.post(\n                        '" . $massUpdateUrl . "',\n                        {\n                            token: csrfToken,\n                            tldIds: selectedTlds,\n                            pricing: {\n                                register: oneYearRegistration,\n                                renew: oneYearRenew,\n                                transfer: oneYearTransfer,\n                                copyToYears: jQuery('#inputCopyPricingBulk').prop('checked'),\n                                grace: {\n                                    duration: graceDuration,\n                                    fee: graceFee\n                                },\n                                redemption: {\n                                    duration: redemptionDuration,\n                                    fee: redemptionFee\n                                }\n                            }\n                        }\n                    ).done(function(data) {\n                        if (data.success === true) {\n                            validResponse = true;\n                            window.location.replace('configdomains.php?success=true');\n                        }\n                    }).always(function() {\n                        if (!validResponse) {\n                            self.prop('disabled', false).removeClass('disabled');\n                            " . $massUpdateErrorGrowl . "\n                        }\n                    });\n                }\n            );\n        }\n\n    });\n";
    $content = ob_get_contents();
    ob_end_clean();
    $aInt->content = $content;
    $aInt->jquerycode = $jqueryCode;
    $aInt->jscode = $jsCode;
    $aInt->display();
}

?>