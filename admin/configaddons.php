<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Configure Product Addons");
$aInt->title = $aInt->lang("addons", "productaddons");
$aInt->sidebar = "config";
$aInt->icon = "productaddons";
$aInt->helplink = "Product Addons";
$action = $whmcs->getFromRequest("action");
$sub = $whmcs->getFromRequest("sub");
$id = (int) $whmcs->getFromRequest("id");
$addon = $id ? WHMCS\Product\Addon::find($id) : NULL;
$server = new WHMCS\Module\Server();
if ($id && !$addon) {
    throw new WHMCS\Exception\ProgramExit("Invalid Addon Id Provided");
}
$ajaxActions = array("module-settings" => "getModuleSettings");
if (array_key_exists($action, $ajaxActions)) {
    $addonSetup = new WHMCS\Admin\Setup\AddonSetup();
    try {
        $actionToCall = $ajaxActions[$action];
        $response = $addonSetup->{$actionToCall}($addon->id);
        if (!is_array($response)) {
            $response = array("error" => "Invalid response");
        }
    } catch (Exception $e) {
        $response = array("error" => $e->getMessage());
    }
    $aInt->setBodyContent($response);
    $aInt->output();
    exit;
}
$saved = $deleted = false;
$jscode = $jQueryCode = "";
$pricingEditDisabled = false;
$moduleSettingsDisabled = false;
$addonModuleDisplayName = $configurationLink = "";
if ($addon->module && $server->load($addon->module)) {
    if ($server->isMetaDataValueSet("NoEditPricing") && $server->getMetaDataValue("NoEditPricing")) {
        $pricingEditDisabled = true;
    }
    if ($server->isMetaDataValueSet("NoEditModuleSettings") && $server->getMetaDataValue("NoEditModuleSettings")) {
        $moduleSettingsDisabled = true;
    }
    $addonModuleDisplayName = $server->getDisplayName();
    if ($pricingEditDisabled || $moduleSettingsDisabled) {
        $configurationLink = $server->call("get_configuration_link", array("model" => $addon));
    }
}
if ($action == "save") {
    check_token("WHMCS.admin.default");
    $createdNew = false;
    $name = $whmcs->getFromRequest("name");
    $description = $whmcs->getFromRequest("description");
    $billingCycle = $whmcs->getFromRequest("billingcycle");
    $packages = $whmcs->getFromRequest("packages") ?: array();
    $tax = (bool) (int) $whmcs->getFromRequest("tax");
    $showOrder = (bool) (int) $whmcs->getFromRequest("showorder");
    $hide = (bool) (int) $whmcs->getFromRequest("hidden");
    $retired = (bool) (int) $whmcs->getFromRequest("retired");
    $autoActivate = $whmcs->getFromRequest("autoactivate");
    $suspendProduct = (bool) (int) $whmcs->getFromRequest("suspendproduct");
    $downloads = $whmcs->getFromRequest("downloads") ?: array();
    $welcomeEmail = (int) $whmcs->getFromRequest("welcomeemail");
    $weight = (int) $whmcs->getFromRequest("weight");
    $module = $whmcs->getFromRequest("servertype");
    $serverGroup = (int) $whmcs->getFromRequest("servergroup");
    $type = $whmcs->getFromRequest("type");
    $changedRecurring = false;
    $hasServerTypeChanged = false;
    $oldServerModule = "";
    if ($id) {
        if ($addon->getRawAttribute("name") != $name) {
            logAdminActivity("Product Addon Modified: Name Changed: '" . $addon->name . "' to '" . $name . "' - Product Addon ID: " . $id);
            $addon->name = $name;
        }
        if ($addon->getRawAttribute("description") != $description || $addon->billingCycle != $billingCycle || $addon->packages != $packages || $addon->applyTax != $tax || $addon->showOnOrderForm != $showOrder || $addon->isHidden != $hide || $addon->retired != $retired || $addon->autoActivate != $autoActivate || $addon->suspendProduct != $suspendProduct || $addon->downloads != $downloads || $addon->welcomeEmailTemplateId != $welcomeEmail || $addon->weight != $weight || $addon->module != $module || $addon->type != $type || $addon->serverGroupId != $serverGroup) {
            logAdminActivity("Product Addon Modified: '" . $name . "' - Product Addon ID: " . $id);
            if ($billingCycle == "recurring" && $addon->billingCycle != $billingCycle || $addon->billingCycle == "recurring" && $billingCycle != $addon->billingCycle) {
                $changedRecurring = true;
            }
            $addon->description = WHMCS\Input\Sanitize::decode($description);
            $addon->billingCycle = $billingCycle;
            $addon->packages = $packages;
            $addon->applyTax = $tax;
            $addon->showOnOrderForm = $showOrder;
            $addon->isHidden = $hide;
            $addon->retired = $retired;
            $addon->autoActivate = $autoActivate;
            $addon->suspendProduct = $suspendProduct;
            $addon->downloads = $downloads;
            $addon->welcomeEmailTemplateId = $welcomeEmail;
            $addon->weight = $weight;
            $addon->type = $type;
            $addon->serverGroupId = $serverGroup;
            if ($addon->module != $module) {
                $oldServerModule = $addon->module;
                $hasServerTypeChanged = true;
                $addon->module = $module;
            }
        }
        $addon->save();
    } else {
        $addon = new WHMCS\Product\Addon();
        $addon->name = $name;
        $addon->description = WHMCS\Input\Sanitize::decode($description);
        $addon->billingCycle = $billingCycle;
        $addon->packages = $packages;
        $addon->applyTax = $tax;
        $addon->showOnOrderForm = $showOrder;
        $addon->isHidden = $hide;
        $addon->retired = $retired;
        $addon->autoActivate = $autoActivate;
        $addon->suspendProduct = $suspendProduct;
        $addon->downloads = $downloads;
        $addon->welcomeEmailTemplateId = $welcomeEmail;
        $addon->weight = $weight;
        $addon->module = $module;
        $addon->type = $type;
        $addon->serverGroupId = $serverGroup;
        $addon->save();
        $id = $addon->id;
        logAdminActivity("Product Addon Created: '" . $name . "' - Product Addon ID: " . $id);
        $createdNew = true;
    }
    $pricingUpdated = false;
    foreach ($_POST["currency"] as $currency_id => $pricing) {
        if ($createdNew) {
            WHMCS\Database\Capsule::table("tblpricing")->insert(array_merge($pricing, array("type" => "addon", "currency" => $currency_id, "relid" => $id)));
        } else {
            $addonPricing = WHMCS\Database\Capsule::table("tblpricing")->where("type", "=", "addon")->where("currency", "=", $currency_id)->where("relid", "=", $id)->first();
            foreach ($pricing as $keyName => $value) {
                if (($addonPricing->{$keyName} != $value || $changedRecurring) && !$pricingUpdated) {
                    logAdminActivity("Product Addon Modified: '" . $name . "' - Pricing Updated - Product Addon ID: " . $id);
                    $pricingUpdated = true;
                    break;
                }
            }
            if (!$pricingEditDisabled && $pricingUpdated) {
                if ($billingCycle != "recurring") {
                    $pricing = array_merge($pricing, array("qsetupfee" => 0, "quarterly" => -1, "ssetupfee" => 0, "semiannually" => -1, "asetupfee" => 0, "annually" => -1, "bsetupfee" => 0, "biennially" => -1, "tsetupfee" => 0, "triennially" => -1));
                } else {
                    $cycleCount = 0;
                    $activeCycle = NULL;
                    $activeCycleTitleCase = NULL;
                    $cycles = array("monthly" => "Monthly", "quarterly" => "Quarterly", "semiannually" => "Semi-Annually", "annually" => "Annually", "biennially" => "Biennially", "triennially" => "Triennially");
                    foreach ($cycles as $cycle => $cycleTitleCase) {
                        if (0 <= $pricing[$cycle]) {
                            $activeCycle = $cycle;
                            $activeCycleTitleCase = $cycleTitleCase;
                            $cycleCount++;
                        }
                    }
                    if ($cycleCount == 1) {
                        $setupfee = $pricing[substr($activeCycle, 0, 1) . "setupfee"];
                        $price = $pricing[$activeCycle];
                        foreach (array_keys($cycles) as $cycle) {
                            $pricing[substr($cycle, 0, 1) . "setupfee"] = 0;
                            $pricing[$cycle] = 0;
                        }
                        $pricing["msetupfee"] = $setupfee;
                        $pricing["monthly"] = $price;
                        $addon->billingCycle = $activeCycleTitleCase;
                        $addon->save();
                    }
                }
                WHMCS\Database\Capsule::table("tblpricing")->where("type", "=", "addon")->where("currency", "=", $currency_id)->where("relid", "=", $id)->update($pricing);
            }
        }
    }
    $fieldChanges = array();
    if ($whmcs->isInRequest("customFieldName")) {
        $customFieldNames = $whmcs->getFromRequest("customFieldName");
        foreach ($customFieldNames as $fieldId => $customFieldName) {
            $customFieldType = $whmcs->getFromRequest("customFieldType", $fieldId);
            $customFieldDescription = $whmcs->getFromRequest("customFieldDescription", $fieldId);
            $customFieldOptions = explode(",", $whmcs->getFromRequest("customFieldOptions", $fieldId));
            $customFieldExpression = WHMCS\Input\Sanitize::decode($whmcs->getFromRequest("customFieldExpression", $fieldId));
            $customFieldAdmin = $whmcs->getFromRequest("customFieldAdmin", $fieldId);
            $customFieldRequired = $whmcs->getFromRequest("customFieldRequired", $fieldId);
            $customFieldShowOrder = $whmcs->getFromRequest("customFieldShowOrder", $fieldId);
            $customFieldShowInvoice = $whmcs->getFromRequest("customFieldShowInvoice", $fieldId);
            $customFieldSortOrder = $whmcs->getFromRequest("customFieldSortOrder", $fieldId);
            $customField = WHMCS\CustomField::find($fieldId);
            if ($customFieldName != $customField->getRawAttribute("fieldname")) {
                $fieldChanges[] = "Custom Field Name Modified: '" . $customField->getRawAttribute("fieldname") . "' to '" . $customFieldName . "'";
                $customField->fieldName = $customFieldName;
            }
            if ($customFieldType != $customField->fieldType || $customFieldDescription != $customField->getRawAttribute("description") || $customFieldOptions != $customField->fieldOptions || $customFieldExpression != $customField->regularExpression || $customFieldAdmin != $customField->adminOnly || $customFieldRequired != $customField->required || $customFieldShowOrder != $customField->showOrder || $customFieldShowInvoice != $customField->showInvoice || $customFieldSortOrder != $customField->sortOrder) {
                $fieldChanges[] = "Custom Field Modified: '" . $customFieldName . "'";
                $customField->fieldType = $customFieldType;
                $customField->description = $customFieldDescription;
                $customField->fieldOptions = $customFieldOptions;
                $customField->regularExpression = $customFieldExpression;
                $customField->adminOnly = $customFieldAdmin;
                $customField->required = $customFieldRequired;
                $customField->showOnOrderForm = "";
                $customField->showOnInvoice = $customFieldShowInvoice;
                $customField->sortOrder = $customFieldSortOrder;
            }
            $customField->save();
        }
    }
    if ($whmcs->getFromRequest("addFieldName")) {
        $addFieldName = $whmcs->getFromRequest("addFieldName");
        $addFieldType = $whmcs->get_req_var("addFieldType");
        $addFieldDescription = $whmcs->get_req_var("addFieldDescription");
        $addFieldOptions = explode(",", $whmcs->get_req_var("addFieldOptions"));
        $addFieldExpression = WHMCS\Input\Sanitize::decode($whmcs->get_req_var("addFieldExpression"));
        $addFieldAdmin = $whmcs->get_req_var("addFieldExpression");
        $addFieldRequired = $whmcs->get_req_var("addFieldRequired");
        $addFieldShowOrder = $whmcs->get_req_var("addFieldShowOrder");
        $addFieldShowInvoice = $whmcs->get_req_var("addFieldShowInvoice");
        $addFieldSortOrder = $whmcs->get_req_var("addFieldSortOrder");
        $fieldChanges[] = "Custom Field Created: '" . $addFieldName . "'";
        $customField = new WHMCS\CustomField();
        $customField->type = "addon";
        $customField->relatedId = $id;
        $customField->fieldName = $addFieldName;
        $customField->fieldType = $addFieldType;
        $customField->description = $addFieldDescription;
        $customField->fieldOptions = $addFieldOptions;
        $customField->regularExpression = $addFieldExpression;
        $customField->adminOnly = $addFieldAdmin;
        $customField->required = $addFieldRequired;
        $customField->showOnOrderForm = "";
        $customField->showOnInvoice = $addFieldShowInvoice;
        $customField->sortOrder = $addFieldSortOrder;
        $customField->save();
    }
    $server = new WHMCS\Module\Server();
    $newServer = $server->load($module);
    if ($hasServerTypeChanged) {
        $oldServer = new WHMCS\Module\Server();
        $oldName = $oldServer->load($oldServerModule) ? $oldServer->getDisplayName() : "";
        $newName = $newServer ? $server->getDisplayName() : "";
        $fieldChanges[] = "Server Module Modified: '" . $oldName . "' to '" . $newName . "'";
        WHMCS\Config\Module\ModuleConfiguration::where("entity_type", "=", "addon")->where("entity_id", "=", $id)->delete();
    }
    $packageConfigOptions = $whmcs->get_req_var("packageconfigoption") ?: array();
    if ($server->functionExists("ConfigOptions")) {
        $configArray = $server->call("ConfigOptions", array("producttype" => $addon->type, "addon" => true));
        $counter = 0;
        foreach ($configArray as $key => $values) {
            $friendlyName = $key;
            if (array_key_exists("FriendlyName", $values)) {
                $friendlyName = $values["FriendlyName"];
            }
            $counter++;
            $field = "configoption" . $counter;
            if (!$whmcs->isInRequest("packageconfigoption", $counter)) {
                $moduleConfiguration = $addon->moduleConfiguration->where("setting_name", $field)->first();
                $packageConfigOptions[$counter] = $moduleConfiguration ? $moduleConfiguration->value : "";
                if ($hasServerTypeChanged) {
                    $packageConfigOptions[$counter] = "";
                }
            }
            $saveValue = is_array($packageConfigOptions[$counter]) ? $packageConfigOptions[$counter] : trim($packageConfigOptions[$counter]);
            if (!$hasServerTypeChanged) {
                $existingValue = $addon->moduleConfiguration->where("setting_name", $field)->first();
                if ($existingValue) {
                    $existingValue = $existingValue->value;
                }
                if ($values["Type"] == "password") {
                    $updatedPassword = interpretMaskedPasswordChangeForStorage($saveValue, $existingValue);
                    if ($updatedPassword === false) {
                        continue;
                    }
                    if ($updatedPassword) {
                        $fieldChanges[] = (string) $key . " Value Modified";
                    }
                } else {
                    if (is_array($saveValue)) {
                        $saveValue = json_encode($saveValue);
                        if ($saveValue != $existingValue) {
                            $fieldChanges[] = (string) $key . " Value Modified";
                        }
                    } else {
                        $saveValue = WHMCS\Input\Sanitize::decode($saveValue);
                        if ($saveValue != $existingValue) {
                            $fieldChanges[] = (string) $key . " Value Modified: '" . $existingValue . "' to '" . $saveValue . "'";
                        }
                    }
                }
            } else {
                if (is_array($saveValue)) {
                    $saveValue = json_encode($saveValue);
                } else {
                    $saveValue = WHMCS\Input\Sanitize::decode($saveValue);
                }
            }
            $moduleConfiguration = $addon->moduleConfiguration->where("setting_name", $field)->first();
            if (!$moduleConfiguration) {
                $moduleConfiguration = new WHMCS\Config\Module\ModuleConfiguration();
            }
            $moduleConfiguration->entityType = "addon";
            $moduleConfiguration->entityId = $id;
            $moduleConfiguration->friendlyName = $friendlyName;
            $moduleConfiguration->settingName = $field;
            $moduleConfiguration->value = $saveValue;
            $moduleConfiguration->save();
        }
    }
    if ($fieldChanges) {
        $logStart = "Product Addon Modified";
        if ($createdNew) {
            $logStart = "Product Addon Creatd";
        }
        logAdminActivity((string) $logStart . " '" . $name . "' - " . implode(". ", $fieldChanges) . " - Product Addon ID: " . $id);
    }
    run_hook("AddonConfigSave", array("id" => $id));
    $redirect = "action=manage&id=" . $id . "&saved=true";
    if ($createdNew) {
        $redirect = "action=manage&id=" . $id . "&created=true";
    }
    if ($tab) {
        $redirect .= "&tab=" . $tab;
    }
    redir($redirect);
}
if ($action == "delete") {
    check_token("WHMCS.admin.default");
    checkPermission("Delete Products/Services");
    if (!$addon) {
        redir();
    }
    run_hook("ProductAddonDelete", array("addonId" => $addon->id));
    if (0 < $addon->serviceAddons->count()) {
        redir("exists=true");
    }
    $addonName = $addon->getRawAttribute("name");
    $addon->customFields()->delete();
    $addon->moduleConfiguration()->delete();
    $addon->delete();
    delete_query("tblpricing", array("type" => "addon", "relid" => $id));
    logAdminActivity("Product Addon Deleted: '" . $addonName . "' - Product Addon ID: " . $id);
    redir("deleted=true");
}
if ($sub && $sub == "delete_custom_field") {
    check_token("WHMCS.admin.default");
    $fieldId = (int) $whmcs->get_req_var("fid");
    $customField = WHMCS\CustomField::find($fieldId);
    if (!$customField) {
        redir("action=manage&id=" . $id);
    }
    logAdminActivity("Product Addon Modified: Custom Field Deleted: '" . $customField->getRawAttribute("name") . "' - Addon ID: " . $id);
    $customField->delete();
    redir("action=manage&id=" . $id . "&saved=true");
}
ob_start();
if ($whmcs->getFromRequest("saved")) {
    echo WHMCS\View\Helper::alert(AdminLang::trans("addons.changesuccessinfo"), "success");
}
if ($whmcs->getFromRequest("created")) {
    echo WHMCS\View\Helper::alert(AdminLang::trans("addons.addonaddsuccessinfo"), "success");
}
if ($whmcs->getFromRequest("deleted")) {
    echo WHMCS\View\Helper::alert(AdminLang::trans("addons.addondelsuccessinfo"), "success");
}
if (App::getFromRequest("exists")) {
    echo WHMCS\View\Helper::alert(AdminLang::trans("addons.deleteAddonError"), "warning");
}
if (!$action) {
    echo "\n<p>";
    echo $aInt->lang("addons", "description");
    echo "</p>\n\n<p><a href=\"";
    echo $whmcs->getPhpSelf();
    echo "?action=manage\" class=\"btn btn-default\"><i class=\"fas fa-plus\"></i> ";
    echo $aInt->lang("addons", "addnew");
    echo "</a></p>\n\n";
    $aInt->sortableTableInit("nopagination");
    $addons = WHMCS\Product\Addon::all();
    $tableData = array();
    foreach ($addons as $addon) {
        $addonId = $addon->id;
        $packages = $addon->packages;
        $name = $addon->getRawAttribute("name");
        $description = $addon->getRawAttribute("description");
        $billingCycle = $addon->billingCycle;
        $showOnOrder = $addon->showOnOrderForm;
        $hidden = $addon->isHidden;
        $weight = $addon->weight;
        if (in_array($billingCycle, array("free", "Free Account"))) {
            $paymentType = AdminLang::trans("billingcycles.free");
        } else {
            if (in_array($billingCycle, array("onetime", "One Time"))) {
                $paymentType = AdminLang::trans("billingcycles.onetime");
            } else {
                $paymentType = AdminLang::trans("status.recurring");
            }
        }
        $yesImage = "<img src=\"images/icons/tick.png\" alt=\"" . AdminLang::trans("global.yes") . "\" border=\"0\" />";
        $showOnOrder = $showOnOrder ? $yesImage : "&nbsp;";
        $hidden = $hidden ? $yesImage : "&nbsp;";
        $deleteAction = "onClick=\"doDelete('" . $addonId . "');return false;\"";
        if (0 < $addon->serviceAddons()->count()) {
            $deleteAction = "data-toggle=\"modal\" data-target=\"#modalAddonsNoDelete\"";
        }
        $tableData[] = array($name, $description, $paymentType, $showOnOrder, $hidden, $weight, "<a href=\"?action=manage&id=" . $addonId . "\"><img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "edit") . "\"></a>", "<a href=\"#\" " . $deleteAction . "><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\"></a>");
    }
    echo $aInt->sortableTable(array(AdminLang::trans("addons.name"), AdminLang::trans("fields.description"), AdminLang::trans("products.paytype"), AdminLang::trans("addons.showonorder"), AdminLang::trans("global.hidden"), AdminLang::trans("addons.weighting"), "", ""), $tableData);
    echo $aInt->modal("AddonsNoDelete", AdminLang::trans("addons.noDelete"), AdminLang::trans("addons.deleteAddonError"), array(array("title" => AdminLang::trans("global.ok"), "class" => "btn-primary")));
    echo $aInt->modalWithConfirmation("doDelete", AdminLang::trans("addons.areYouSureDelete"), $whmcs->getPhpSelf() . "?action=delete&id=", "addonId");
} else {
    if ($action == "manage") {
        echo "<form method=\"post\" action=\"" . $whmcs->getPhpSelf() . "?action=save&id=" . $id . "\">";
        if ($id) {
            $manageTitle = $aInt->lang("addons", "editaddon");
            $packages = $addon->packages;
            $name = $addon->getRawAttribute("name");
            $description = $addon->getRawAttribute("description");
            $billingCycle = $addon->billingCycle;
            $tax = (bool) $addon->applyTax;
            $showOrder = (bool) $addon->showOnOrderForm;
            $hidden = (bool) $addon->isHidden;
            $retired = (bool) $addon->retired;
            $autoActivate = $addon->autoActivate;
            $suspendProduct = (bool) $addon->suspendProduct;
            $downloads = $addon->downloads;
            $welcomeEmail = $addon->welcomeEmailTemplateId;
            $weight = $addon->weight;
            $type = $addon->type;
        } else {
            $manageTitle = $aInt->lang("addons", "createnew");
            $packages = $downloads = array();
            $name = $description = $autoActivate = "";
            $billingCycle = "Free Account";
            $tax = $showOrder = $hidden = $retired = $suspendProduct = false;
            $welcomeEmail = $weight = 0;
            $type = "hostingaccount";
        }
        echo $aInt->beginAdminTabs(array(AdminLang::trans("products.tabsdetails"), AdminLang::trans("global.pricing"), AdminLang::trans("products.tabsmodulesettings"), AdminLang::trans("setup.customfields"), AdminLang::trans("addons.applicableproducts"), AdminLang::trans("products.associateddl")), true);
        $aInt->title .= " - " . $manageTitle;
        echo "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr>\n    <td class=\"fieldlabel\" width=\"20%\">";
        echo $aInt->lang("addons", "name");
        echo "</td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"name\" value=\"";
        echo $name;
        echo "\" class=\"form-control input-400 input-inline\">\n        <div class=\"pull-right\">";
        echo $aInt->getTranslationLink("product_addon.name", $id);
        echo "</div>\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "description");
        echo "</td>\n    <td class=\"fieldarea\" valign=\"top\">\n        <textarea name=\"description\" rows=\"3\" class=\"form-control input-500 input-inline\">";
        echo WHMCS\Input\Sanitize::encode($description);
        echo "</textarea>\n        <div class=\"pull-right\">";
        echo $aInt->getTranslationLink("product_addon.description", $id);
        echo "</div>\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
        echo $aInt->lang("addons", "taxaddon");
        echo "</td>\n    <td class=\"fieldarea\">\n        <input type=\"hidden\" name=\"tax\" value=\"0\" />\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"tax\"";
        echo $tax ? " checked=\"checked\"" : "";
        echo " value=\"1\" /> ";
        echo $aInt->lang("addons", "taxaddoninfo");
        echo "        </label>\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
        echo $aInt->lang("addons", "showonorder");
        echo "</td>\n    <td class=\"fieldarea\">\n        <input type=\"hidden\" name=\"showorder\" value=\"0\" />\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"showorder\"";
        echo $showOrder ? " checked=\"checked\"" : "";
        echo " value=\"1\" /> ";
        echo $aInt->lang("addons", "showonorderinfo");
        echo "        </label>\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
        echo $aInt->lang("addons", "suspendparentproduct");
        echo "</td>\n    <td class=\"fieldarea\">\n        <input type=\"hidden\" name=\"suspendproduct\" value=\"0\" />\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"suspendproduct\"";
        echo $suspendProduct ? " checked=\"checked\"" : "";
        echo " value=\"1\" /> ";
        echo $aInt->lang("addons", "suspendparentproductinfo");
        echo "        </label>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("products", "welcomeemail");
        echo "</td><td class=\"fieldarea\"><select name=\"welcomeemail\" class=\"form-control select-inline\"><option value=\"0\">";
        echo $aInt->lang("global", "none");
        echo "</option>\n";
        $productTemplates = WHMCS\Mail\Template::where("type", "=", "product")->where("language", "=", "")->orderBy("name")->get();
        foreach ($productTemplates as $template) {
            echo "<option value=\"" . $template->id . "\"";
            if ($template->id == $welcomeEmail) {
                echo " selected";
            }
            echo ">" . $template->name . "</option>";
        }
        echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("addons", "weighting");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"weight\" value=\"";
        echo $weight;
        echo "\" class=\"form-control input-100 input-inline\" /> ";
        echo $aInt->lang("addons", "weightinginfo");
        echo "</td></tr>\n";
        $hookret = run_hook("AddonConfig", array("id" => $id));
        foreach ($hookret as $hookdat) {
            foreach ($hookdat as $k => $v) {
                echo "<td class=\"fieldlabel\">" . $k . "</td><td class=\"fieldarea\">" . $v . "</td></tr>";
            }
        }
        echo "<tr>\n    <td class=\"fieldlabel\">";
        echo AdminLang::trans("global.hidden");
        echo "</td>\n    <td class=\"fieldarea\">\n        <input type=\"hidden\" name=\"hidden\" value=\"0\" />\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"hidden\"";
        echo $hidden ? " checked=\"checked\"" : "";
        echo " value=\"1\" />\n            ";
        echo AdminLang::trans("addons.hiddenDescription");
        echo "        </label>\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
        echo AdminLang::trans("addons.retired");
        echo "</td>\n    <td class=\"fieldarea\">\n        <input type=\"hidden\" name=\"retired\" value=\"0\" />\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"retired\"";
        echo $retired ? " checked=\"checked\"" : "";
        echo " value=\"1\" />\n            ";
        echo AdminLang::trans("addons.retiredDescription");
        echo "        </label>\n    </td>\n</tr>\n</table>\n";
        echo $aInt->nextAdminTab();
        echo "        ";
        if ($pricingEditDisabled) {
            echo "<input type=\"hidden\" name=\"billingcycle\" value=\"" . $billingCycle . "\">" . "<div class=\"marketconnect-product-redirect\" role=\"alert\">\n                    " . AdminLang::trans("products.marketConnectManageRedirectMsg") . "<br>\n                    <a href=\"" . $configurationLink . "\" class=\"btn btn-default btn-sm\">" . AdminLang::trans("products.marketConnectManageRedirectBtn") . "</a>\n                </div>";
        } else {
            echo "    <table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n        <tr>\n            <td class=\"fieldlabel\">";
            echo $aInt->lang("products", "paymenttype");
            echo "</td>\n            <td class=\"fieldarea\">\n                <label class=\"radio-inline\">\n                    <input type=\"radio\" name=\"billingcycle\" id=\"PayType-Free\" value=\"free\" onclick=\"hidePricingTable()\"";
            if (in_array($billingCycle, array("free", "Free Account"))) {
                echo " checked";
            }
            echo ">\n                    ";
            echo $aInt->lang("billingcycles", "free");
            echo "                </label>\n                <label class=\"radio-inline\">\n                    <input type=\"radio\" name=\"billingcycle\" value=\"onetime\" id=\"PayType-OneTime\" onclick=\"showPricingTable(false)\"";
            if (in_array($billingCycle, array("onetime", "One Time"))) {
                echo " checked";
            }
            echo ">\n                    ";
            echo $aInt->lang("billingcycles", "onetime");
            echo "                </label>\n                <label class=\"radio-inline\">\n                    <input type=\"radio\" name=\"billingcycle\" value=\"recurring\" id=\"PayType-Recurring\" onclick=\"showPricingTable(true)\"";
            if (!in_array($billingCycle, array("free", "onetime", "Free Account", "One Time"))) {
                echo " checked";
            }
            echo ">\n                    ";
            echo $aInt->lang("global", "recurring");
            echo "                </label>\n            </td>\n        </tr>\n        <tr id=\"trPricing\"";
            if ($paytype == "free") {
                echo " style=\"display:none;\"";
            }
            echo "><td colspan=\"2\" align=\"center\"><br>\n            <div class=\"row\">\n                <div class=\"col-sm-10 col-sm-offset-1\">\n                    <table id=\"pricingtbl\" class=\"table table-condensed\">\n                        <tr bgcolor=\"#efefef\" style=\"text-align:center;font-weight:bold\">\n                            <td>";
            echo $aInt->lang("currencies", "currency");
            echo "</td>\n                            <td></td>\n                            <td>";
            echo $aInt->lang("billingcycles", "onetime");
            echo "/";
            echo $aInt->lang("billingcycles", "monthly");
            echo "</td>\n                            <td class=\"prod-pricing-recurring\">";
            echo $aInt->lang("billingcycles", "quarterly");
            echo "</td>\n                            <td class=\"prod-pricing-recurring\">";
            echo $aInt->lang("billingcycles", "semiannually");
            echo "</td>\n                            <td class=\"prod-pricing-recurring\">";
            echo $aInt->lang("billingcycles", "annually");
            echo "</td>\n                            <td class=\"prod-pricing-recurring\">";
            echo $aInt->lang("billingcycles", "biennially");
            echo "</td>\n                            <td class=\"prod-pricing-recurring\">";
            echo $aInt->lang("billingcycles", "triennially");
            echo "</td>\n                        </tr>\n";
            $result = select_query("tblcurrencies", "id,code", "", "code", "ASC");
            while ($data = mysql_fetch_array($result)) {
                $currency_id = $data["id"];
                $currency_code = $data["code"];
                $cycles = array("monthly", "quarterly", "semiannually", "annually", "biennially", "triennially");
                $legacyCycles = array("One Time" => array("setup" => "msetupfee", "term" => "monthly"), "Monthly" => array("setup" => "msetupfee", "term" => "monthly"), "Quarterly" => array("setup" => "qsetupfee", "term" => "quarterly"), "Semi-Annually" => array("setup" => "ssetupfee", "term" => "semiannually"), "Annually" => array("setup" => "asetupfee", "term" => "annually"), "Biennially" => array("setup" => "bsetupfee", "term" => "biennially"), "Triennially" => array("setup" => "tsetupfee", "term" => "triennially"));
                $pricing = WHMCS\Database\Capsule::table("tblpricing")->where("type", "=", "addon")->where("currency", "=", $currency_id)->where("relid", "=", $id)->first();
                if (is_null($pricing)) {
                    $addonData = array("type" => "addon", "currency" => $currency_id, "relid" => $id);
                    foreach ($cycles as $cycle) {
                        $addonData[$cycle] = "-1";
                    }
                    $pricingId = WHMCS\Database\Capsule::table("tblpricing")->insertGetId($addonData);
                    $pricing = WHMCS\Database\Capsule::table("tblpricing")->find($pricingId);
                    if (!$id) {
                        WHMCS\Database\Capsule::table("tblpricing")->where("type", "=", "addon")->where("relid", "=", 0)->delete();
                    }
                }
                $legacyPricingStorage = false;
                if (array_key_exists($billingCycle, $legacyCycles)) {
                    $legacyPricingStorage = true;
                }
                $setupfields = $pricingfields = $disablefields = "";
                foreach ($cycles as $i => $cycle) {
                    if ($cycle == $legacyCycles[$billingCycle]["term"]) {
                        $price = $pricing->monthly;
                        $setupfeeName = "msetupfee";
                    } else {
                        $price = $legacyPricingStorage ? -1 : $pricing->{$cycle};
                        $setupfeeName = substr($cycle, 0, 1) . "setupfee";
                    }
                    $class = 1 <= $i ? " class=\"prod-pricing-recurring\"" : "";
                    $setupfields .= "<td" . $class . "><input type=\"text\" name=\"currency[" . $currency_id . "][" . substr($cycle, 0, 1) . "setupfee]\" id=\"setup_" . $currency_code . "_" . $cycle . "\" value=\"" . $pricing->{$setupfeeName} . "\"" . ($price == "-1" ? " style=\"display:none\"" : "") . " class=\"form-control input-inline input-100 text-center\" /></td>";
                    $pricingfields .= "<td" . $class . "><input type=\"text\" name=\"currency[" . $currency_id . "][" . $cycle . "]\" id=\"pricing_" . $currency_code . "_" . $cycle . "\" size=\"10\" value=\"" . $price . "\"" . ($price == "-1" ? " style=\"display:none;\"\"" : "") . " class=\"form-control input-inline input-100 text-center\" /></td>";
                    $disablefields .= "<td" . $class . "><input type=\"checkbox\" class=\"pricingtgl\" currency=\"" . $currency_code . "\" cycle=\"" . $cycle . "\"";
                    $disablefields .= $price == "-1" ? "" : " checked=\"checked\"";
                    $disablefields .= " /></td>";
                }
                echo "<tr bgcolor=\"#ffffff\" style=\"text-align:center\">\n            <td rowspan=\"3\" bgcolor=\"#efefef\"><b>" . $currency_code . "</b></td>\n            <td>" . $aInt->lang("fields", "setupfee") . "</td>\n            " . $setupfields . "\n        </tr>\n        <tr bgcolor=\"#ffffff\" style=\"text-align:center\">\n            <td>" . $aInt->lang("fields", "price") . "</td>\n            " . $pricingfields . "\n        </tr>\n        <tr bgcolor=\"#ffffff\" style=\"text-align:center\">\n            <td>" . $aInt->lang("global", "enable") . "</td>\n            " . $disablefields . "\n        </tr>";
            }
            $jscode .= "\nfunction hidePricingTable() {\n    \$(\"#trPricing\").fadeOut();\n}\nfunction showPricingTable(recurring) {\n    if (\$(\"#trPricing\").is(\":visible\")) {\n        if (recurring) {\n            \$(\"#trPricing .table\").css(\"max-width\", \"\");\n            \$(\".prod-pricing-recurring\").fadeIn();\n        } else {\n            \$(\".prod-pricing-recurring\").fadeOut(\"fast\", function() {\n                \$(\"#trPricing .table\").css(\"max-width\", \"370px\");\n            });\n        }\n    } else {\n        \$(\"#trPricing\").fadeIn();\n        if (recurring) {\n            \$(\"#trPricing .table\").css(\"max-width\", \"\");\n            \$(\".prod-pricing-recurring\").show();\n        } else {\n            \$(\"#trPricing .table\").css(\"max-width\", \"370px\");\n            \$(\".prod-pricing-recurring\").hide();\n        }\n    }\n}\n";
            $jQueryCode .= "\$(\".pricingtgl\").click(function() {\n    var cycle = \$(this).attr(\"cycle\");\n    var currency = \$(this).attr(\"currency\");\n\n    if (\$(this).is(\":checked\")) {\n        \$(\"#pricing_\" + currency + \"_\" + cycle).val(\"0.00\").show();\n        \$(\"#setup_\" + currency + \"_\" + cycle).show();\n    } else {\n        \$(\"#pricing_\" + currency + \"_\" + cycle).val(\"-1.00\").hide();\n        \$(\"#setup_\" + currency + \"_\" + cycle).hide();\n    }\n});";
            echo "            </table>\n        </div>\n    </div>\n</td></tr>\n</table>\n";
        }
        echo "\n";
        echo $aInt->nextAdminTab();
        $serverModules = $server->getListWithDisplayNames();
        echo "    <div id=\"addonModuleSettings\" class=\"table-responsive\">\n    ";
        if (!$moduleSettingsDisabled) {
            echo "        <table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n            <tr>\n                <td class=\"fieldlabel\" width=\"10%\">";
            echo AdminLang::trans("fields.producttype");
            echo "</td>\n                <td class=\"fieldarea\" width=\"25%\">\n                    <select name=\"type\" class=\"form-control select-inline\">\n                        <option value=\"hostingaccount\"";
            echo $type == "hostingaccount" ? " selected=\"selected\"" : "";
            echo ">\n                            ";
            echo AdminLang::trans("products.hostingaccount");
            echo "                        </option>\n                        <option value=\"reselleraccount\"";
            echo $type == "reselleraccount" ? " selected=\"selected\"" : "";
            echo ">\n                            ";
            echo AdminLang::trans("products.reselleraccount");
            echo "                        </option>\n                        <option value=\"server\"";
            echo $type == "server" ? " selected=\"selected\"" : "";
            echo ">\n                            ";
            echo AdminLang::trans("products.dedicatedvpsserver");
            echo "                        </option>\n                        <option value=\"other\"";
            echo $type == "other" ? " selected=\"selected\"" : "";
            echo ">\n                            ";
            echo AdminLang::trans("setup.other");
            echo "                        </option>\n                    </select>\n                </td>\n                <td class=\"fieldlabel\" width=\"10%\">";
            echo AdminLang::trans("products.modulename");
            echo "</td>\n                <td class=\"fieldarea\" width=\"25%\"><select name=\"servertype\" id=\"inputModule\" class=\"form-control select-inline\" onchange=\"fetchModuleSettings('";
            echo $id;
            echo "', 'simple');\"><option value=\"\">";
            echo AdminLang::trans("global.none");
            foreach ($serverModules as $moduleName => $displayName) {
                echo "<option value=\"" . $moduleName . "\"" . ($moduleName == $addon->module ? " selected" : "") . ">" . $displayName . "</option>";
            }
            echo "</select> <img src=\"images/loading.gif\" id=\"moduleSettingsLoader\" style=\"display: none;\"></td>\n                <td class=\"fieldlabel\" width=\"15%\">";
            echo AdminLang::trans("products.servergroup");
            echo "</td>\n                <td class=\"fieldarea\"><select name=\"servergroup\" id=\"inputServerGroup\" class=\"form-control select-inline\" onchange=\"fetchModuleSettings('";
            echo $id;
            echo "', 'simple');\"><option value=\"0\" data-server-types=\"\">";
            echo AdminLang::trans("global.none");
            echo "</option>";
            $serverGroups = WHMCS\Database\Capsule::table("tblservergroups")->join("tblservergroupsrel", "tblservergroups.id", "=", "tblservergroupsrel.groupid")->join("tblservers", "tblservergroupsrel.serverid", "=", "tblservers.id")->groupBy("tblservergroups.id")->selectRaw("tblservergroups.id,tblservergroups.name,CONCAT(\",\", GROUP_CONCAT(DISTINCT tblservers.type SEPARATOR \",\"), \",\") as server_types")->get();
            foreach ($serverGroups as $group) {
                $option = "<option value=\"" . $group->id . "\"";
                $option .= " data-server-types=\"" . $group->server_types . "\"";
                if ($group->id == $addon->serverGroupId) {
                    $option .= " selected";
                }
                $option .= ">" . $group->name . "</option>";
                echo $option;
            }
            echo "</select></td>\n            </tr>\n        </table>\n\n        <div id=\"serverReturnedError\" class=\"alert alert-warning hidden\" style=\"margin:10px 0;\">\n            <i class=\"fas fa-exclamation-triangle\"></i>\n            <span id=\"serverReturnedErrorText\"></span>\n        </div>\n\n        <table class=\"form module-settings\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\" id=\"tblModuleSettings\">\n            <tr id=\"noModuleSelectedRow\">\n                <td>\n                    <div class=\"no-module-selected\">\n                        ";
            echo AdminLang::trans("products.moduleSettingsChooseAProduct");
            echo "                    </div>\n                </td>\n            </tr>\n        </table>\n        <div class=\"module-settings-mode hidden\" id=\"mode-switch\" data-mode=\"simple\">\n            <a class=\"btn btn-sm btn-link\">\n                <span class=\"text-simple hidden\">";
            echo AdminLang::trans("products.switchSimple");
            echo "</span>\n                <span class=\"text-advanced hidden\">";
            echo AdminLang::trans("products.switchAdvanced");
            echo "</span>\n            </a>\n        </div>\n        ";
        } else {
            echo "<input type=\"hidden\" name=\"servertype\" id=\"inputModule\" value=\"" . $addon->module . "\" />" . "<div class=\"marketconnect-product-redirect\" role=\"alert\">\n                " . AdminLang::trans("products.marketConnectManageRedirectMsg") . "<br>\n                <a href=\"" . $configurationLink . "\" class=\"btn btn-default btn-sm\">" . AdminLang::trans("products.marketConnectManageRedirectBtn") . "</a>\n            </div>";
        }
        echo "\n        <table class=\"form module-settings-automation\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\" id=\"tblAddonAutomationSettings\">\n            <tr>\n                <td width=\"20\">\n                    <input type=\"radio\" name=\"autoactivate\" value=\"order\" id=\"autosetup_order\" ";
        if ($addon->autoActivate == "order") {
            echo " checked";
        }
        echo ">\n                </td>\n                <td class=\"fieldarea\">\n                    <label for=\"autosetup_order\" class=\"checkbox-inline\">";
        echo AdminLang::trans("addons.setupInstantlyAfterOrder");
        echo "</label>\n                </td>\n            </tr>\n            <tr>\n                <td>\n                    <input type=\"radio\" name=\"autoactivate\" value=\"payment\" id=\"autosetup_payment\"";
        if ($addon->autoActivate == "payment") {
            echo " checked";
        }
        echo ">\n                </td>\n                <td class=\"fieldarea\">\n                    <label for=\"autosetup_payment\" class=\"checkbox-inline\">";
        echo AdminLang::trans("addons.setupAfterPayment");
        echo "</label>\n                </td>\n            </tr>\n            <tr>\n                <td>\n                    <input type=\"radio\" name=\"autoactivate\" value=\"on\" id=\"autosetup_on\"";
        if ($addon->autoActivate == "on") {
            echo " checked";
        }
        echo ">\n                </td>\n                <td class=\"fieldarea\">\n                    <label for=\"autosetup_on\" class=\"checkbox-inline\">";
        echo AdminLang::trans("addons.setupAfterAcceptOrder");
        echo "</label>\n                </td>\n            </tr>\n            <tr>\n                <td>\n                    <input type=\"radio\" name=\"autoactivate\" value=\"\" id=\"autosetup_no\"";
        if ($addon->autoActivate == "") {
            echo " checked";
        }
        echo ">\n                </td>\n                <td class=\"fieldarea\">\n                    <label for=\"autosetup_no\" class=\"checkbox-inline\">";
        echo AdminLang::trans("addons.noAutomaticSetup");
        echo "</label>\n                </td>\n            </tr>\n        </table>\n    </div>\n";
        echo $aInt->nextAdminTab();
        $customFields = WHMCS\CustomField::addonFields($id)->get();
        $language = array("fieldName" => AdminLang::trans("customfields.fieldname"), "order" => AdminLang::trans("customfields.order"), "fieldType" => AdminLang::trans("customfields.fieldtype"), "typeTextBox" => AdminLang::trans("customfields.typetextbox"), "typeLink" => AdminLang::trans("customfields.typelink"), "typePassword" => AdminLang::trans("customfields.typepassword"), "typeDropdown" => AdminLang::trans("customfields.typedropdown"), "typeTickBox" => AdminLang::trans("customfields.typetickbox"), "typeTextArea" => AdminLang::trans("customfields.typetextarea"), "description" => AdminLang::trans("fields.description"), "descriptionInfo" => AdminLang::trans("customfields.descriptioninfo"), "validation" => AdminLang::trans("customfields.validation"), "validationInfo" => AdminLang::trans("customfields.validationinfo"), "selectOptions" => AdminLang::trans("customfields.selectoptions"), "selectOptionsInfo" => AdminLang::trans("customfields.selectoptionsinfo"), "adminOnly" => AdminLang::trans("customfields.adminonly"), "requiredField" => AdminLang::trans("customfields.requiredfield"), "orderForm" => AdminLang::trans("customfields.orderform"), "showInvoice" => AdminLang::trans("customfields.showinvoice"), "deleteField" => AdminLang::trans("customfields.deletefield"), "addField" => AdminLang::trans("customfields.addfield"));
        $customFieldOutput = "";
        foreach ($customFields as $customField) {
            $fieldId = $customField->id;
            $fieldType = $customField->fieldtype;
            $selectedType = array("typeTextBox" => $fieldType == "text" ? " selected=\"selected\"" : "", "typeLink" => $fieldType == "link" ? " selected=\"selected\"" : "", "typePassword" => $fieldType == "password" ? " selected=\"selected\"" : "", "typeDropdown" => $fieldType == "dropdown" ? " selected=\"selected\"" : "", "typeTickBox" => $fieldType == "tickbox" ? " selected=\"selected\"" : "", "typeTextArea" => $fieldType == "textarea" ? " selected=\"selected\"" : "");
            $nameTranslationLink = $aInt->getTranslationLink("custom_field.name", $fieldId, "addon");
            $regularExpression = WHMCS\Input\Sanitize::decode($customField->regularExpression);
            $adminOnly = $customField->adminOnly ? " checked=\"checked\"" : "";
            $required = $customField->required ? " checked=\"checked\"" : "";
            $showInvoice = $customField->showOnInvoice ? " checked=\"checked\"" : "";
            $customFieldOutput .= "<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n    <tr>\n        <td class=\"fieldlabel\">\n            " . $language["fieldName"] . "\n        </td>\n        <td class=\"fieldarea\">\n            <input type=\"text\" name=\"customFieldName[" . $fieldId . "]\" value=\"" . $customField->getRawAttribute("fieldname") . "\" class=\"form-control input-inline input-400\" />\n            " . $aInt->getTranslationLink("custom_field.name", $fieldId, "addon") . "\n            <div class=\"pull-right\">\n                " . $language["order"] . "\n                <input type=\"text\" name=\"customFieldSortOrder[" . $fieldId . "]\" value=\"" . $customField->sortOrder . "\" class=\"form-control input-inline input-100 text-center\">\n            </div>\n        </td>\n    </tr>\n    <tr><td class=\"fieldlabel\">" . $language["fieldType"] . "</td><td class=\"fieldarea\"><select name=\"customFieldType[" . $fieldId . "]\" class=\"form-control select-inline\">\n    <option value=\"text\" " . $selectedType["typeTextBox"] . ">" . $language["typeTextBox"] . "</option>\n    <option value=\"link\" " . $selectedType["typeLink"] . ">" . $language["typeLink"] . "</option>\n    <option value=\"password\" " . $selectedType["typePassword"] . ">" . $language["typePassword"] . "</option>\n    <option value=\"dropdown\" " . $selectedType["typeDropdown"] . ">" . $language["typeDropdown"] . "</option>\n    <option value=\"tickbox\" " . $selectedType["typeTickBox"] . ">" . $language["typeTickBox"] . "</option>\n    <option value=\"textarea\" " . $selectedType["typeTextArea"] . ">" . $language["typeTextArea"] . "</option>\n    </select></td></tr>\n    <tr>\n        <td class=\"fieldlabel\">\n            " . $language["description"] . "\n        </td>\n        <td class=\"fieldarea\">\n            <input type=\"text\" name=\"customFieldDescription[" . $fieldId . "]\" value=\"" . $customField->getRawAttribute("description") . "\" class=\"form-control input-inline input-500\" />\n            " . $aInt->getTranslationLink("custom_field.description", $fieldId, "addon") . "\n            " . $language["descriptionInfo"] . "\n        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">" . $language["validation"] . "</td>\n        <td class=\"fieldarea\">\n            <input type=\"text\" name=\"customFieldExpression[" . $fieldId . "]\" value=\"" . $regularExpression . "\" class=\"form-control input-inline input-500\"> " . $language["validationInfo"] . "\n        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">" . $language["selectOptions"] . "</td>\n        <td class=\"fieldarea\">\n            <input type=\"text\" name=\"customFieldOptions[" . $fieldId . "]\" value=\"" . $customField->getRawAttribute("fieldoptions") . "\" class=\"form-control input-inline input-500\"> " . $language["selectOptionsInfo"] . "\n        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\"></td>\n        <td class=\"fieldarea\">\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"customFieldAdmin[" . $fieldId . "]\" " . $adminOnly . " />\n                " . $language["adminOnly"] . "\n            </label>\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"customFieldRequired[" . $fieldId . "]\" " . $required . " />\n                " . $language["requiredField"] . "\n            </label>\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"customFieldShowInvoice[" . $fieldId . "]\" " . $showInvoice . " />\n                " . $language["showInvoice"] . "\n            </label>\n            <div class=\"pull-right\">\n                <a href=\"#\" onclick=\"deleteCustomField('" . $fieldId . "');return false\" class=\"btn btn-danger btn-xs\">" . $language["deleteField"] . "</a>\n            </div>\n        </td>\n    </tr>\n</table><br>";
        }
        $customFieldOutput .= "<b>" . $language["addField"] . "</b><br><br>\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr>\n    <td class=\"fieldlabel\">\n        " . $language["fieldName"] . "\n    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"addFieldName\" class=\"form-control input-inline input-400\" />\n        " . $aInt->getTranslationLink("custom_field.name", 0, "addon") . "\n        <div class=\"pull-right\">\n            " . $language["order"] . "\n            <input type=\"text\" name=\"addSortOrder\" value=\"0\" class=\"form-control input-inline input-100 text-center\" />\n        </div>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">" . $language["fieldType"] . "</td><td class=\"fieldarea\"><select name=\"addFieldType\" class=\"form-control select-inline\">\n<option value=\"text\">" . $language["typeTextBox"] . "</option>\n<option value=\"link\">" . $language["typeLink"] . "</option>\n<option value=\"password\">" . $language["typePassword"] . "</option>\n<option value=\"dropdown\">" . $language["typeDropdown"] . "</option>\n<option value=\"tickbox\">" . $language["typeTickBox"] . "</option>\n<option value=\"textarea\">" . $language["typeTextArea"] . "</option>\n</select></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        " . $language["description"] . "\n    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"addFieldDescription\" class=\"form-control input-inline input-500\" />\n        " . $aInt->getTranslationLink("custom_field.description", 0, "addon") . "\n        " . $language["descriptionInfo"] . "\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">" . $language["validation"] . "</td><td class=\"fieldarea\"><input type=\"text\" name=\"addFieldExpression\" class=\"form-control input-inline input-500\"> " . $language["validationInfo"] . "</td></tr>\n<tr><td class=\"fieldlabel\">" . $language["selectOptions"] . "</td><td class=\"fieldarea\"><input type=\"text\" name=\"addFieldOptions\" class=\"form-control input-inline input-500\"> " . $language["selectOptionsInfo"] . "</td></tr>\n    <tr>\n        <td class=\"fieldlabel\"></td>\n        <td class=\"fieldarea\">\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"addFieldAdmin\">\n                " . $language["adminOnly"] . "\n            </label>\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"addFieldRequired\">\n                " . $language["requiredField"] . "\n            </label>\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"addFieldShowInvoice\">\n                " . $language["showInvoice"] . "\n            </label>\n        </td>\n    </tr>\n</table>";
        echo "    <div id=\"customFields\" class=\"table-responsive\">\n        ";
        echo $customFieldOutput;
        echo "    </div>\n    ";
        echo $aInt->nextAdminTab();
        echo "        <div class=\"bordered\">\n            <div class=\"row\">\n                <div class=\"col-md-10 col-md-offset-1\">\n                    <select name=\"packages[]\" size=\"10\" multiple class=\"form-control select-inline-long\">\n                        ";
        $products = new WHMCS\Product\Products();
        $productsList = $products->getProducts();
        foreach ($productsList as $data) {
            $productId = $data["id"];
            $groupName = $data["groupname"];
            $name = $data["name"];
            $selected = in_array($productId, $packages) ? "selected=\"selected\"" : "";
            echo "<option value=\"" . $productId . "\" " . $selected . ">" . $groupName . " - " . $name . "</option>";
        }
        echo "                    </select>\n                </div>\n            </div>\n        </div>\n    ";
        echo $aInt->nextAdminTab();
        echo "        <div class=\"bordered\">\n            <div class=\"row\">\n                <div class=\"col-md-10 col-md-offset-1\">\n                    <select name=\"downloads[]\" size=\"10\" multiple class=\"form-control select-inline-long\">";
        $query = "SELECT tbldownloads.*,tbldownloadcats.name FROM tbldownloads INNER JOIN tbldownloadcats ON tbldownloads.category=tbldownloadcats.id WHERE tbldownloads.productdownload='1' ORDER BY tbldownloadcats.name ASC,tbldownloads.title ASC";
        $result = full_query($query);
        while ($data = mysql_fetch_array($result)) {
            $downloadId = $data["id"];
            $downloadCat = $data["name"];
            $downloadName = $data["title"];
            $selected = in_array($downloadId, $downloads) ? "selected=\"selected\"" : "";
            echo "<option value=\"" . $downloadId . "\" " . $selected . ">" . $downloadCat . " - " . $downloadName . "</option>";
        }
        echo "                    </select>\n                </div>\n            </div>\n        </div>\n    ";
        echo $aInt->endAdminTabs();
        echo "\n    <div class=\"btn-container\">\n        <input type=\"submit\" value=\"";
        echo $aInt->lang("global", "savechanges");
        echo "\" class=\"btn btn-primary\">\n        <input type=\"button\" value=\"";
        echo $aInt->lang("global", "cancelchanges");
        echo "\" class=\"btn btn-default\" onclick=\"window.location='configaddons.php'\" />\n    </div>\n\n<input type=\"hidden\" name=\"tab\" id=\"tab\" value=\"";
        echo $tab;
        echo "\" />\n\n</form>\n\n";
        $passedTab = (int) App::getFromRequest("tab");
        $languageStrings = array("loading" => AdminLang::trans("global.loading"), "availableProducts" => AdminLang::trans("addons.availableProducts"), "filterProducts" => AdminLang::trans("addons.filterProducts"), "selectedProducts" => AdminLang::trans("addons.selectedProducts"), "availableDownloads" => AdminLang::trans("addons.availableDownloads"), "filterDownloads" => AdminLang::trans("addons.filterDownloads"), "selectedDownloads" => AdminLang::trans("addons.selectedDownloads"));
        $jQueryCode .= "var moduleSettingsFetched = false;\njQuery('a[data-toggle=\"tab\"]').on('shown.bs.tab', function (e) {\n    if (moduleSettingsFetched) {\n        return;\n    }\n    var href = jQuery(this).attr('href');\n    if (href === '#tab3') {\n        fetchModuleSettings('" . $id . "');\n        moduleSettingsFetched = true;\n    }\n});\nif (jQuery('#inputModule').val() !== '' && " . $passedTab . " === 3) {\n    fetchModuleSettings('" . $id . "');\n}\njQuery('select[name=\"packages[]\"]').bootstrapDualListbox(\n    {\n        nonSelectedListLabel: '" . $languageStrings["availableProducts"] . "',\n        selectedListLabel: '" . $languageStrings["selectedProducts"] . "',\n        filterPlaceHolder: '" . $languageStrings["filterProducts"] . "',\n        selectorMinimalHeight: 200\n    }\n);\njQuery('select[name=\"downloads[]\"]').bootstrapDualListbox(\n    {\n        nonSelectedListLabel: '" . $languageStrings["availableDownloads"] . "',\n        selectedListLabel: '" . $languageStrings["selectedDownloads"] . "',\n        filterPlaceHolder: '" . $languageStrings["filterDownloads"] . "',\n        selectorMinimalHeight: 200\n    }\n);";
        $token = generate_token("link");
        $jscode .= "function deleteCustomField(id) {\n    if (confirm(\"Are you sure you want to delete this field and ALL DATA associated with it?\")) {\n        window.location = window.location.pathname + '?action=manage&id=" . $id . "&sub=delete_custom_field&fid=' + id + '" . $token . "';\n    }\n}";
    }
}
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jscode = $jscode;
$aInt->jquerycode = $jQueryCode;
$aInt->display();

?>