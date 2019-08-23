<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("View Products/Services");
$aInt->title = $aInt->lang("products", "title");
$aInt->sidebar = "config";
$aInt->icon = "configproducts";
$aInt->helplink = "Configuring Products/Services";
$aInt->requiredFiles(array("modulefunctions", "gatewayfunctions"));
$whmcs = App::self();
$success = $whmcs->get_req_var("success");
$setupReset = $whmcs->get_req_var("setupReset");
$id = (int) $whmcs->get_req_var("id");
$ids = (int) $whmcs->get_req_var("ids");
$sub = $whmcs->get_req_var("sub");
if ($id && $sub != "deletegroup") {
    $product = WHMCS\Product\Product::find($id);
} else {
    if ($ids) {
        $productGroup = WHMCS\Product\Group::find($ids);
    }
}
$ajaxActions = array("module-settings" => "getModuleSettings");
if (array_key_exists($action, $ajaxActions)) {
    $productSetup = new WHMCS\Admin\Setup\ProductSetup();
    try {
        $actionToCall = $ajaxActions[$action];
        $response = $productSetup->{$actionToCall}($product->id);
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
if ($action == "getdownloads") {
    check_token("WHMCS.admin.default");
    if (!checkPermission("Edit Products/Services", true)) {
        exit("Access Denied");
    }
    $dir = $_POST["dir"];
    $dir = preg_replace("/[^0-9]/", "", $dir);
    echo "<ul class=\"jqueryFileTree\" style=\"display: none;\">";
    $result = select_query("tbldownloadcats", "", array("parentid" => $dir), "name", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $catid = $data["id"];
        $catname = $data["name"];
        echo "<li class=\"directory collapsed\"><a href=\"#\" rel=\"dir" . $catid . "/\">" . $catname . "</a></li>";
    }
    $result = select_query("tbldownloads", "", array("category" => $dir), "title", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $downid = $data["id"];
        $downtitle = $data["title"];
        $downfilename = $data["location"];
        $downfilenameSplit = explode(".", $downfilename);
        $ext = end($downfilenameSplit);
        echo "<li class=\"file ext_" . $ext . "\"><a href=\"#\" rel=\"" . $downid . "\">" . $downtitle . "</a></li>";
    }
    echo "</ul>";
    exit;
}
if ($action == "managedownloads") {
    check_token("WHMCS.admin.default");
    if (!checkPermission("Edit Products/Services", true)) {
        exit("Access Denied");
    }
    $adddl = (int) $whmcs->get_req_var("adddl");
    $remdl = (int) $whmcs->get_req_var("remdl");
    if ($adddl) {
        $download = WHMCS\Download\Download::find($adddl);
        $product->productDownloads()->attach($download);
        logAdminActivity("Product Modified - Download Attached: '" . $download->title . "' - Product ID: " . $product->id);
    }
    if ($remdl) {
        $download = WHMCS\Download\Download::find($remdl);
        $product->productDownloads()->detach($download);
        logAdminActivity("Product Modified - Download Detached: '" . $download->title . "' - Product ID: " . $product->id);
    }
    printproductdownloads($product->getDownloadIds());
    exit;
}
if ($action == "quickupload") {
    check_token("WHMCS.admin.default");
    if (!checkPermission("Edit Products/Services", true)) {
        exit("Access Denied");
    }
    $categorieslist = "";
    buildcategorieslist(0, 0);
    echo "<form method=\"post\" action=\"configproducts.php?action=uploadfile&id=" . $id . "\" id=\"quickuploadfrm\" enctype=\"multipart/form-data\">\n" . generate_token("form") . "\n<table width=\"100%\">\n<tr><td width=\"120\">Category:</td><td><select name=\"catId\" class=\"form-control\">" . $categorieslist . "</select></td></tr>\n<tr><td>Title:</td><td><input type=\"text\" name=\"title\" class=\"form-control\" /></td></tr>\n<tr><td>Description:</td><td><input type=\"text\" name=\"description\" class=\"form-control\" /></td></tr>\n<tr><td>Choose File:</td><td><input type=\"file\" name=\"uploadfile\" class=\"form-control\" /></td></tr>\n</table>\n</form>";
    exit;
}
if ($action == "uploadfile") {
    check_token("WHMCS.admin.default");
    if (!checkPermission("Edit Products/Services", true)) {
        exit("Access Denied");
    }
    try {
        foreach (WHMCS\File\Upload::getUploadedFiles("uploadfile") as $uploadedFile) {
            $filename = $uploadedFile->storeAsDownload();
            $catId = (int) $whmcs->get_req_var("catId");
            $title = $whmcs->get_req_var("title");
            $description = $whmcs->get_req_var("description");
            $download = new WHMCS\Download\Download();
            $download->downloadCategoryId = $catId;
            $download->type = "zip";
            $download->title = $title ? $title : $filename;
            $download->description = $description;
            $download->fileLocation = $filename;
            $download->clientDownloadOnly = true;
            $download->isProductDownload = true;
            $download->save();
            $product->productDownloads()->attach($download);
            logActivity("Added New Product Download - " . $title);
            logAdminActivity("Product Modified - Download Attached: '" . $download->title . "' - Product ID: " . $product->id);
        }
    } catch (Exception $e) {
        $aInt->gracefulExit("Could not save file: " . $e->getMessage());
    }
    redir("action=edit&id=" . $id . "&tab=8");
}
if ($action == "adddownloadcat") {
    check_token("WHMCS.admin.default");
    if (!checkPermission("Edit Products/Services", true)) {
        exit("Access Denied");
    }
    $categorieslist = "";
    buildcategorieslist(0, 0);
    echo "<form method=\"post\" action=\"configproducts.php?action=createdownloadcat&id=" . $id . "\" id=\"adddownloadcatfrm\" enctype=\"multipart/form-data\">\n" . generate_token("form") . "\n<table width=\"100%\">\n<tr><td width=\"80\">Category:</td><td><select name=\"catid\" class=\"form-control\">" . $categorieslist . "</select></td></tr>\n<tr><td>Name:</td><td><input type=\"text\" name=\"title\" class=\"form-control\" /></td></tr>\n<tr><td>Description:</td><td><input type=\"text\" name=\"description\" class=\"form-control\" /></td></tr>\n</table>\n</form>";
    exit;
}
if ($action == "createdownloadcat") {
    check_token("WHMCS.admin.default");
    checkPermission("Edit Products/Services");
    insert_query("tbldownloadcats", array("parentid" => $catid, "name" => $title, "description" => $description, "hidden" => "0"));
    logActivity("Added New Download Category - " . $title);
    redir("action=edit&id=" . $id . "&tab=8");
}
if ($action == "add") {
    check_token("WHMCS.admin.default");
    checkPermission("Create New Products/Services");
    $hostingProductTypes = array("hostingaccount", "reselleraccount", "server");
    $groupId = (int) $whmcs->get_req_var("gid");
    $productType = $whmcs->getFromRequest("type");
    if (!in_array($productType, $hostingProductTypes)) {
        $productType = "other";
    }
    $newProduct = new WHMCS\Product\Product();
    $newProduct->type = $productType;
    $newProduct->productGroupId = $groupId;
    $newProduct->name = $whmcs->getFromRequest("productname");
    $newProduct->paymentType = "free";
    $newProduct->showDomainOptions = in_array($whmcs->getFromRequest("type"), $hostingProductTypes);
    $newProduct->module = $whmcs->getFromRequest("module");
    $newProduct->isHidden = (bool) $whmcs->getFromRequest("createhidden");
    $displayOrder = WHMCS\Database\Capsule::table("tblproducts")->where("gid", "=", $groupId)->max("order");
    $newProduct->displayOrder = is_null($displayOrder) ? 1 : ++$displayOrder;
    $newProduct->save();
    $productId = $newProduct->id;
    logAdminActivity("Product Created - '" . $newProduct->name . "' - Product ID: " . $newProduct->id);
    redir("action=edit&id=" . $productId);
}
if ($action == "save") {
    check_token("WHMCS.admin.default");
    checkPermission("Edit Products/Services");
    $type = $whmcs->get_req_var("type");
    $gid = (int) $whmcs->get_req_var("gid");
    $name = $whmcs->get_req_var("name");
    $description = WHMCS\Input\Sanitize::decode($whmcs->get_req_var("description"));
    $hidden = (int) (bool) $whmcs->get_req_var("hidden");
    $showdomainops = (int) (bool) $whmcs->get_req_var("showdomainops");
    $welcomeemail = (int) $whmcs->get_req_var("welcomeemail");
    $stockcontrol = (int) (bool) $whmcs->get_req_var("stockcontrol");
    $qty = (int) $whmcs->get_req_var("qty");
    $proratabilling = (int) (bool) $whmcs->get_req_var("proratabilling");
    $proratadate = (int) $whmcs->get_req_var("proratadate");
    $proratachargenextmonth = (int) $whmcs->get_req_var("proratachargenextmonth");
    $paytype = $whmcs->get_req_var("paytype");
    $allowqty = (int) (bool) $whmcs->get_req_var("allowqty");
    $subdomain = $whmcs->get_req_var("subdomain");
    $autosetup = $whmcs->get_req_var("autosetup");
    $servertype = $whmcs->get_req_var("servertype");
    $servergroup = (int) $whmcs->get_req_var("servergroup");
    $freedomain = $whmcs->get_req_var("freedomain");
    $freedomainpaymentterms = $whmcs->get_req_var("freedomainpaymentterms");
    $freedomaintlds = $whmcs->get_req_var("freedomaintlds");
    $recurringcycles = $whmcs->get_req_var("recurringcycles");
    $autoterminatedays = (int) $whmcs->get_req_var("autoterminatedays");
    $autoterminateemail = (int) $whmcs->get_req_var("autoterminateemail");
    $configoptionsupgrade = (int) (bool) $whmcs->get_req_var("configoptionsupgrade");
    $billingcycleupgrade = $whmcs->get_req_var("billingcycleupgrade");
    $upgradeemail = (int) $whmcs->get_req_var("upgradeemail");
    $overagesenabled = (int) (bool) $whmcs->get_req_var("overagesenabled");
    $overageunitsdisk = $whmcs->get_req_var("overageunitsdisk");
    $overageunitsbw = $whmcs->get_req_var("overageunitsbw");
    $overagesdisklimit = (int) $whmcs->get_req_var("overagesdisklimit");
    $overagesbwlimit = (int) $whmcs->get_req_var("overagesbwlimit");
    $overagesdiskprice = (double) $whmcs->get_req_var("overagesdiskprice");
    $overagesbwprice = (double) $whmcs->get_req_var("overagesbwprice");
    $tax = (int) (bool) $whmcs->get_req_var("tax");
    $affiliatepaytype = $whmcs->get_req_var("affiliatepaytype");
    $affiliatepayamount = (double) $whmcs->get_req_var("affiliatepayamount");
    $affiliateonetime = (int) (bool) $whmcs->get_req_var("affiliateonetime");
    $retired = (int) (bool) $whmcs->get_req_var("retired");
    $isFeatured = (int) (bool) $whmcs->get_req_var("isFeatured");
    $savefreedomainpaymentterms = $freedomainpaymentterms ? implode(",", $freedomainpaymentterms) : "";
    $savefreedomaintlds = $freedomaintlds ? implode(",", $freedomaintlds) : "";
    $overagesenabled = $overagesenabled ? "1," . $overageunitsdisk . "," . $overageunitsbw : "";
    $table = "tblproducts";
    $changes = array();
    $array = array();
    if ($type != $product->type) {
        $changes[] = "Product Type Modified: '" . $product->type . "' to '" . $type . "'";
    }
    $array["type"] = $type;
    if ($gid != $product->productGroupId) {
        $newGroup = WHMCS\Product\Group::find($gid);
        $changes[] = "Product Group Modified: '" . $product->productGroup->name . "' to '" . $newGroup->name . "'";
    }
    $array["gid"] = $gid;
    if ($name != $product->name) {
        logAdminActivity("Product Modified - Name Modified: '" . $product->name . "' to '" . $name . "' - Product ID: " . $product->id);
        $product->name = $name;
    }
    $array["name"] = $name;
    if ($description != $product->description) {
        $changes[] = "Product Description Modified";
        $array["description"] = $description;
        $product->description = $description;
    }
    $array["description"] = $description;
    if ($welcomeemail != $product->welcomeEmailTemplateId) {
        $changes[] = "Welcome Email Modified";
    }
    $array["welcomeemail"] = $welcomeemail;
    if ($showdomainops != $product->showDomainOptions) {
        if ($showdomainops) {
            $changes[] = "Require Domain Enabled";
        } else {
            $changes[] = "Require Domain Disabled";
        }
    }
    $array["showdomainoptions"] = $showdomainops;
    if ($stockcontrol != $product->stockControlEnabled) {
        if ($stockcontrol) {
            $changes[] = "Stock Control Enabled";
        } else {
            $changes[] = "Stock Control Disabled";
        }
    }
    $array["stockcontrol"] = $stockcontrol;
    if ($qty != $product->quantityInStock) {
        $changes[] = "Quantity In Stock Modified: '" . $product->quantityInStock . "' to '" . $qty . "'";
    }
    $array["qty"] = $qty;
    if ($tax != $product->applyTax) {
        if ($tax) {
            $changes[] = "Apply Tax Enabled";
        } else {
            $changes[] = "Apply Tax Disabled";
        }
    }
    $array["tax"] = $tax;
    if ($isFeatured != $product->isFeatured) {
        if ($isFeatured) {
            $changes[] = "Featured Product Enabled";
        } else {
            $changes[] = "Featured Product Disabled";
        }
    }
    $array["is_featured"] = $isFeatured;
    if ($hidden != $product->isHidden) {
        if ($hidden) {
            $changes[] = "Product Hidden";
        } else {
            $changes[] = "Product Displayed";
        }
    }
    $array["hidden"] = $hidden;
    if ($retired != $product->isRetired) {
        if ($retired) {
            $changes[] = "Product Retired";
        } else {
            $changes[] = "Product Activated";
        }
    }
    $array["retired"] = $retired;
    if ($paytype != $product->paymentType) {
        $changes[] = "Payment Type Modified: '" . $product->paymentType . "' to '" . $paytype . "'";
    }
    $array["paytype"] = $paytype;
    if ($allowqty != $product->allowMultipleQuantities) {
        if ($allowqty) {
            $changes[] = "Allow Multiple Quantities Enabled";
        } else {
            $changes[] = "Allow Multiple Quantities Disabled";
        }
    }
    $array["allowqty"] = $allowqty;
    if ($recurringcycles != $product->recurringCycleLimit) {
        $changes[] = "Recurring Cycles Limit Modified: '" . $product->recurringCycleLimit . "' to '" . $recurringcycles . "'";
    }
    $array["recurringcycles"] = $recurringcycles;
    if ($autoterminatedays != $product->daysAfterSignUpUntilAutoTermination) {
        if (!$autoterminatedays) {
            $changes[] = "Auto Terminate/Fixed Term Disabled";
        } else {
            if (!$product->daysAfterSignUpUntilAutoTermination) {
                $changes[] = "Auto Terminate/Fixed Term Enabled and set to: '" . $autoterminatedays . "'";
            } else {
                $changes[] = "Auto Terminate/Fixed Term Modified: " . "'" . $product->daysAfterSignUpUntilAutoTermination . "' to '" . $autoterminatedays . "'";
            }
        }
    }
    $array["autoterminatedays"] = $autoterminatedays;
    if ($autoterminateemail != $product->autoTerminationEmailTemplateId) {
        $changes[] = "Automatic Termination Email Template Modified";
    }
    $array["autoterminateemail"] = $autoterminateemail;
    if ($proratabilling != $product->proRataBilling) {
        if ($proratabilling) {
            $changes[] = "Prorata Billing Enabled";
        } else {
            $changes[] = "Prorata Billing Disabled";
        }
    }
    $array["proratabilling"] = $proratabilling;
    if ($proratadate != $product->proRataChargeDayOfCurrentMonth) {
        $changes[] = "Prorata Date Modified: '" . $product->proRataChargeDayOfCurrentMonth . "' to '" . $proratadate . "'";
    }
    $array["proratadate"] = $proratadate;
    if ($proratachargenextmonth != $product->proRataChargeNextMonthAfterDay) {
        $changes[] = "Charge Next Month: '" . $product->proRataChargeNextMonthAfterDay . "' to '" . $proratachargenextmonth . "'";
    }
    $array["proratachargenextmonth"] = $proratachargenextmonth;
    $array["servertype"] = $servertype;
    if ($servergroup != $product->serverGroupId) {
        $changes[] = "Server Group Modified: '" . $product->serverGroupId . "' to '" . $servergroup . "'";
    }
    $array["servergroup"] = $servergroup;
    if (App::isInRequest("autosetup") && $autosetup != $product->autoSetup) {
        if (!$autosetup) {
            $changes[] = "Automatic Setup Disabled";
        } else {
            $changes[] = "Automatic Setup Modified: '" . ucfirst($product->autoSetup) . "' to '" . ucfirst($autosetup) . "'";
        }
        $array["autosetup"] = $autosetup;
    }
    if ($configoptionsupgrade != $product->allowConfigOptionUpgradeDowngrade) {
        if ($configoptionsupgrade) {
            $changes[] = "Configurable Options Upgrade/Downgrade Enabled";
        } else {
            $changes[] = "Configurable Options Upgrade/Downgrade Disabled";
        }
    }
    $array["configoptionsupgrade"] = $configoptionsupgrade;
    $array["billingcycleupgrade"] = $billingcycleupgrade;
    if ($upgradeemail != $product->upgradeEmailTemplateId) {
        $changes[] = "Upgrade Email Template Modified";
    }
    $array["upgradeemail"] = $upgradeemail;
    if ($freedomain != $product->freeDomain) {
        if (!$freedomain) {
            $changes[] = "Free Domain Disabled";
        } else {
            if ($freedomain == "on") {
                $changes[] = "Free Domain Renewal Modified: 'Free Renewal with Active Product'";
            } else {
                $changes[] = "Free Domain Renewal Modified: 'No Free Renewal'";
            }
        }
    }
    $array["freedomain"] = $freedomain;
    if ($savefreedomainpaymentterms != implode(",", $product->freeDomainPaymentTerms)) {
        $changes[] = "Free Domain Payment Terms Modified";
    }
    $array["freedomainpaymentterms"] = $savefreedomainpaymentterms;
    if ($savefreedomaintlds != implode(",", $product->freeDomainPaymentTerms)) {
        $changes[] = "Free Domain TLD's Modified";
    }
    $array["freedomaintlds"] = $savefreedomaintlds;
    if ($affiliatepaytype != $product->affiliatePaymentType) {
        if (!$affiliatepaytype) {
            $changes[] = "Custom Affiliate Payout Modified: Use Default";
        } else {
            switch ($affiliatepaytype) {
                case "percentage":
                    $changes[] = "Custom Affiliate Payout Modified: Percentage";
                    break;
                case "fixed":
                    $changes[] = "Custom Affiliate Payout Modified: Fixed Amount";
                    break;
                default:
                    $changes[] = "Custom Affiliate Payout Modified: No Commission";
            }
        }
    }
    $array["affiliatepaytype"] = $affiliatepaytype;
    if ($affiliatepayamount != $product->affiliatePaymentAmount) {
        $changes[] = "Affiliate Pay Amount Modified: '" . $product->affiliatePaymentAmount . "' to '" . $affiliatepayamount . "'";
    }
    $array["affiliatepayamount"] = $affiliatepayamount;
    if ($affiliateonetime != $product->affiliatePayoutOnceOnly) {
        if ($affiliateonetime) {
            $changes[] = "Affiliate One Time Payout Enabled";
        } else {
            $changes[] = "Affiliate Recurring Payout Enabled";
        }
    }
    $array["affiliateonetime"] = $affiliateonetime;
    $subdomain = WHMCS\Admin\Setup\ProductSetup::formatSubDomainValuesToEnsureLeadingDotAndUnique(explode(",", $subdomain));
    $subdomain = implode(",", $subdomain);
    if ($subdomain != implode(",", $product->freeSubDomains)) {
        $changes[] = "Subdomain Options Modified: '" . implode(",", $product->freeSubDomains) . "' to '" . $subdomain . "'";
    }
    $array["subdomain"] = $subdomain;
    if ($overagesenabled != implode(",", $product->enableOverageBillingAndUnits)) {
        if ($overagesenabled) {
            $changes[] = "Overages Billing Enabled";
        } else {
            $changes[] = "Overages Billing Disabled";
        }
    }
    $array["overagesenabled"] = $overagesenabled;
    if ($overagesdisklimit != $product->overageDiskLimit) {
        $currentDiskUnits = $product->enableOverageBillingAndUnits[1];
        $oldLimit = (string) $product->overageDiskLimit . " " . $currentDiskUnits;
        $newLimit = (string) $overagesdisklimit . " " . $overageunitsdisk;
        $changes[] = "Soft Limits Disk Usage Modified: '" . $oldLimit . "' to '" . $newLimit . "'";
    }
    $array["overagesdisklimit"] = $overagesdisklimit;
    if ($overagesbwlimit != $product->overageBandwidthLimit) {
        $currentBandwidthUnits = $product->enableOverageBillingAndUnits[2];
        $oldLimit = (string) $product->overageBandwidthLimit . " " . $currentBandwidthUnits;
        $newLimit = (string) $overagesbwlimit . " " . $overageunitsbw;
        $changes[] = "Soft Limits Bandwidth Modified: '" . $oldLimit . "' to '" . $newLimit . "'";
    }
    $array["overagesbwlimit"] = $overagesbwlimit;
    if ($overagesdiskprice != $product->overageDiskPrice) {
        $changes[] = "Disk Usage Overage Costs Modified: '" . $product->overageDiskPrice . "' to '" . $overagesdiskprice . "'";
    }
    $array["overagesdiskprice"] = $overagesdiskprice;
    if ($overagesbwprice != $product->overageBandwidthPrice) {
        $changes[] = "Bandwidth Overage Costs Modified: '" . $product->overageBandwidthPrice . "' to '" . $overagesbwprice . "'";
    }
    $array["overagesbwprice"] = $overagesbwprice;
    $hasServerTypeChanged = $servertype != $product->module;
    $server = new WHMCS\Module\Server();
    $newServer = $server->load($servertype);
    if ($hasServerTypeChanged) {
        $oldServer = new WHMCS\Module\Server();
        $oldName = $oldServer->load($product->module) ? $oldServer->getDisplayName() : "";
        $newName = $newServer ? $server->getDisplayName() : "";
        $changes[] = "Server Module Modified: '" . $oldName . "' to '" . $newName . "'";
    }
    $packageconfigoption = $whmcs->get_req_var("packageconfigoption") ?: array();
    if ($server->functionExists("ConfigOptions")) {
        $configArray = $server->call("ConfigOptions", array("producttype" => $product->type));
        $counter = 0;
        foreach ($configArray as $key => $values) {
            $counter++;
            $mco = "moduleConfigOption" . $counter;
            if (!$whmcs->isInRequest("packageconfigoption", $counter)) {
                $packageconfigoption[$counter] = $product->{$mco};
            }
            $saveValue = is_array($packageconfigoption[$counter]) ? $packageconfigoption[$counter] : trim($packageconfigoption[$counter]);
            if (!$hasServerTypeChanged) {
                if ($values["Type"] == "password") {
                    $field = "configoption" . $counter;
                    $existingValue = $product->{$field};
                    $updatedPassword = interpretMaskedPasswordChangeForStorage($saveValue, $existingValue);
                    if ($updatedPassword === false) {
                        continue;
                    }
                    if ($updatedPassword) {
                        $changes[] = (string) $key . " Value Modified";
                    }
                } else {
                    if (is_array($saveValue)) {
                        $saveValue = json_encode($saveValue);
                        if ($saveValue != $product->{$mco}) {
                            $changes[] = (string) $key . " Value Modified";
                        }
                    } else {
                        $saveValue = WHMCS\Input\Sanitize::decode($saveValue);
                        if ($saveValue != $product->{$mco}) {
                            $changes[] = (string) $key . " Value Modified: '" . $product->{$mco} . "' to '" . $saveValue . "'";
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
            $array["configoption" . $counter] = $saveValue;
        }
    }
    $where = array("id" => $id);
    update_query($table, $array, $where);
    $product->save($array);
    $product = WHMCS\Product\Product::find($id);
    $oldUpgradeProductIds = array();
    foreach ($product->upgradeProducts as $oldUpgradeProduct) {
        $oldUpgradeProductIds[] = $oldUpgradeProduct->id;
    }
    $upgradepackages = $whmcs->get_req_var("upgradepackages");
    $product->upgradeProducts()->detach();
    $upgradePackagesChanged = false;
    foreach ($upgradepackages as $upgradePackageId) {
        if (!in_array($upgradePackageId, $oldUpgradeProductIds) && $upgradePackagesChanged == false) {
            $upgradePackagesChanged = true;
            $changes[] = "Upgrade Packages Modified";
        }
        $product->upgradeProducts()->attach(WHMCS\Product\Product::find($upgradePackageId));
    }
    foreach ($oldUpgradeProductIds as $oldUpgradeProductId) {
        if (!in_array($oldUpgradeProductId, $upgradepackages) && $upgradePackagesChanged == false) {
            $upgradePackagesChanged = true;
            $changes[] = "Upgrade Packages Modified";
        }
    }
    $pricingChanged = $setupFeeReset = false;
    foreach ($_POST["currency"] as $currency_id => $pricing) {
        if ($pricingChanged === false) {
            $oldPricing = WHMCS\Database\Capsule::table("tblpricing")->where("type", "=", "product")->where("currency", "=", $currency_id)->where("relid", "=", $id)->first();
            foreach ($pricing as $variable => $price) {
                if ($oldPricing->{$variable} != $price) {
                    $pricingChanged = true;
                    $changes[] = "Pricing Modified";
                    break;
                }
            }
        }
        $setupFeeVars = array("msetupfee", "qsetupfee", "ssetupfee", "asetupfee", "bsetupfee", "tsetupfee");
        foreach ($setupFeeVars as $setupFeeVar) {
            if ($pricing[$setupFeeVar] && $pricing[$setupFeeVar] < 0) {
                $pricing[$setupFeeVar] = 0;
                $setupFeeReset = true;
            }
        }
        update_query("tblpricing", $pricing, array("type" => "product", "currency" => $currency_id, "relid" => $id));
    }
    $customfieldname = $whmcs->get_req_var("customfieldname");
    if ($customfieldname) {
        $customfieldtype = $whmcs->get_req_var("customfieldtype");
        $customfielddesc = $whmcs->get_req_var("customfielddesc");
        $customfieldoptions = $whmcs->get_req_var("customfieldoptions");
        $customfieldregexpr = $whmcs->get_req_var("customfieldregexpr");
        $customadminonly = $whmcs->get_req_var("customadminonly");
        $customrequired = $whmcs->get_req_var("customrequired");
        $customshoworder = $whmcs->get_req_var("customshoworder");
        $customshowinvoice = $whmcs->get_req_var("customshowinvoice");
        $customsortorder = $whmcs->get_req_var("customsortorder");
        foreach ($customfieldname as $fid => $value) {
            $thisCustomField = WHMCS\Database\Capsule::table("tblcustomfields")->find($fid);
            if ($value != $thisCustomField->fieldname) {
                $changes[] = "Custom Field Name Modified: '" . $thisCustomField->fieldname . "' to '" . $value . "'";
            }
            if ($customfieldtype[$fid] != $thisCustomField->fieldtype || $customfielddesc[$fid] != $thisCustomField->description || $customfieldoptions[$fid] != $thisCustomField->fieldoptions || $customfieldregexpr[$fid] != $thisCustomField->regexpr || $customadminonly[$fid] != $thisCustomField->adminonly || $customrequired[$fid] != $thisCustomField->required || $customshoworder[$fid] != $thisCustomField->showorder || $customshowinvoice[$fid] != $thisCustomField->showinvoice || $customsortorder[$fid] != $thisCustomField->sortorder) {
                $changes[] = "Custom Field Modified: '" . $value . "'";
            }
            update_query("tblcustomfields", array("fieldname" => $value, "fieldtype" => $customfieldtype[$fid], "description" => $customfielddesc[$fid], "fieldoptions" => $customfieldoptions[$fid], "regexpr" => WHMCS\Input\Sanitize::decode($customfieldregexpr[$fid]), "adminonly" => $customadminonly[$fid], "required" => $customrequired[$fid], "showorder" => $customshoworder[$fid], "showinvoice" => $customshowinvoice[$fid], "sortorder" => $customsortorder[$fid]), array("id" => $fid));
        }
    }
    $addfieldname = $whmcs->get_req_var("addfieldname");
    if ($addfieldname) {
        $addfieldtype = $whmcs->get_req_var("addfieldtype");
        $addcustomfielddesc = $whmcs->get_req_var("addcustomfielddesc");
        $addfieldoptions = $whmcs->get_req_var("addfieldoptions");
        $addregexpr = $whmcs->get_req_var("addregexpr");
        $addadminonly = $whmcs->get_req_var("addadminonly");
        $addrequired = $whmcs->get_req_var("addrequired");
        $addshoworder = $whmcs->get_req_var("addshoworder");
        $addshowinvoice = $whmcs->get_req_var("addshowinvoice");
        $addsortorder = $whmcs->get_req_var("addsortorder");
        $changes[] = "Custom Field Created: '" . $addfieldname . "'";
        $customFieldIDid = insert_query("tblcustomfields", array("type" => "product", "relid" => $id, "fieldname" => $addfieldname, "fieldtype" => $addfieldtype, "description" => $addcustomfielddesc, "fieldoptions" => $addfieldoptions, "regexpr" => WHMCS\Input\Sanitize::decode($addregexpr), "adminonly" => $addadminonly, "required" => $addrequired, "showorder" => $addshoworder, "showinvoice" => $addshowinvoice, "sortorder" => $addsortorder));
        if (WHMCS\Config\Setting::getValue("EnableTranslations")) {
            WHMCS\Language\DynamicTranslation::saveNewTranslations($customFieldIDid, array("custom_field.{id}.name", "custom_field.{id}.description"));
        }
    }
    $productConfigOptionsChanged = false;
    $productConfigLinks = WHMCS\Database\Capsule::table("tblproductconfiglinks")->where("pid", "=", $id)->get();
    $existingConfigLinks = array();
    foreach ($productConfigLinks as $productConfigLink) {
        if (!in_array($productConfigLink->gid, $configoptionlinks) && $productConfigOptionsChanged === false) {
            $productConfigOptionsChanged = true;
            $changes[] = "Assigned Configurable Option Groups Modified";
        }
        $existingConfigLinks[] = $productConfigLink->gid;
    }
    delete_query("tblproductconfiglinks", array("pid" => $id));
    if ($configoptionlinks) {
        foreach ($configoptionlinks as $gid) {
            if (!in_array($gid, $existingConfigLinks) && $productConfigOptionsChanged === false) {
                $productConfigOptionsChanged = true;
                $changes[] = "Assigned Configurable Option Groups Modified";
            }
            insert_query("tblproductconfiglinks", array("gid" => $gid, "pid" => $id));
        }
    }
    rebuildModuleHookCache();
    run_hook("ProductEdit", array_merge(array("pid" => $id), $array));
    run_hook("AdminProductConfigFieldsSave", array("pid" => $id));
    $redirectURL = "action=edit&id=" . $id . ($tab ? "&tab=" . $tab : "") . "&success=true";
    if ($setupFeeReset) {
        $redirectURL .= "&setupReset=true";
    }
    if ($changes) {
        logAdminActivity("Product Configuration Modified: " . implode(". ", $changes) . ". Product ID: " . $product->id);
    }
    redir($redirectURL);
}
if ($sub == "deletecustomfield") {
    check_token("WHMCS.admin.default");
    checkPermission("Edit Products/Services");
    $fid = (int) $whmcs->get_req_var("fid");
    $customField = WHMCS\CustomField::find($fid);
    logAdminActivity("Product Configuration Modified: Custom Field Deleted: '" . $customField->fieldName . "' - Product ID: " . $id);
    $customField->delete();
    redir("action=edit&id=" . $id . "&tab=" . $tab);
}
if ($action == "duplicatenow") {
    check_token("WHMCS.admin.default");
    checkPermission("Create New Products/Services");
    $existingproduct = (int) $whmcs->get_req_var("existingproduct");
    $newproductname = $whmcs->get_req_var("newproductname");
    try {
        $newProduct = WHMCS\Product\Product::findOrFail($existingproduct)->replicate();
        $existingProductName = $newProduct->name;
        $newProduct->name = $newproductname;
        $newProduct->displayOrder++;
        WHMCS\Product\Product::where("gid", $newProduct->productGroupId)->where("order", ">=", $newProduct->displayOrder)->increment("order");
        $newProduct->save();
        $newproductid = $newProduct->id;
        $result = select_query("tblpricing", "", array("type" => "product", "relid" => $existingproduct));
        while ($data = mysql_fetch_array($result)) {
            $addstr = "";
            foreach ($data as $key => $value) {
                if (is_numeric($key)) {
                    if ($key == "0") {
                        $value = "";
                    }
                    if ($key == "3") {
                        $value = $newproductid;
                    }
                    $addstr .= "'" . db_escape_string($value) . "',";
                }
            }
            $addstr = substr($addstr, 0, -1);
            full_query("INSERT INTO tblpricing VALUES (" . $addstr . ")");
        }
        $result2 = select_query("tblcustomfields", "", array("type" => "product", "relid" => $existingproduct), "id", "ASC");
        while ($data = mysql_fetch_array($result2)) {
            $addstr = "";
            foreach ($data as $key => $value) {
                if (is_numeric($key)) {
                    if ($key == "0") {
                        $value = "";
                    }
                    if ($key == "2") {
                        $value = $newproductid;
                    }
                    $addstr .= "'" . db_escape_string($value) . "',";
                }
            }
            $addstr = substr($addstr, 0, -1);
            full_query("INSERT INTO tblcustomfields VALUES (" . $addstr . ")");
        }
    } catch (Exception $e) {
        logAdminActivity("Failed to duplicate product ID " . $existingproduct . ": " . $e->getMessage());
        throw $e;
    }
    logAdminActivity("Product Duplicated: '" . $existingProductName . "' to '" . $newproductname . "' - Product ID: " . $newproductid);
    redir("action=edit&id=" . $newproductid);
}
if ($sub == "savegroup") {
    check_token("WHMCS.admin.default");
    checkPermission("Manage Product Groups");
    $ids = (int) $whmcs->get_req_var("ids");
    $name = $whmcs->get_req_var("name");
    $orderFormTemplate = $whmcs->get_req_var("orderfrmtplname");
    $hidden = (int) (bool) $whmcs->get_req_var("hidden");
    $headline = $whmcs->get_req_var("headline");
    $tagline = $whmcs->get_req_var("tagline");
    $systemOrderFormTemplate = WHMCS\Config\Setting::getValue("OrderFormTemplate");
    try {
        $orderFormTemplates = WHMCS\View\Template\OrderForm::all();
        if (!$ids || $whmcs->get_req_var("orderfrmtpl") == "custom") {
            if ($orderFormTemplate == $systemOrderFormTemplate || !$orderFormTemplates->has($orderFormTemplate)) {
                $orderFormTemplate = "";
            }
        } else {
            $orderFormTemplate = "";
        }
    } catch (Exception $e) {
        $aInt->gracefulExit("Order Form Templates directory is missing. Please reupload /templates/orderforms/");
    }
    $disabledGateways = array();
    $gateways2 = getGatewaysArray();
    foreach ($gateways2 as $gateway => $gatewayName) {
        if (!$gateways[$gateway]) {
            $disabledGateways[] = $gateway;
        }
    }
    $changes = array();
    if ($ids) {
        $group = WHMCS\Product\Group::find($ids);
        if ($name != $group->name) {
            $changes[] = "Name Modified: '" . $group->name . "' to '" . $name . "'";
        }
        if ($orderFormTemplate != $group->orderFormTemplate) {
            $changes[] = "Order Form Template Modified: '" . $group->orderFormTemplate . "' to '" . $orderFormTemplate . "'";
        }
        if ($disabledGateways != $group->disabledPaymentGateways) {
            $changes[] = "Disabled Payment Gateways Modified";
        }
        if ($hidden != $group->isHidden) {
            if ($hidden) {
                $changes[] = "Group Hidden";
            } else {
                $changes[] = "Group Displayed";
            }
        }
        if ($headline != $group->headline) {
            $changes[] = "Headline Modified: '" . $group->headline . "' to '" . $headline . "'";
        }
        if ($tagline != $group->tagline) {
            $changes[] = "Tagline Modified: '" . $group->tagline . "' to '" . $tagline . "'";
        }
    } else {
        $group = new WHMCS\Product\Group();
        $group->displayOrder = WHMCS\Database\Capsule::table("tblproductgroups")->max("order") + 1;
    }
    $group->name = $name;
    $group->orderFormTemplate = $orderFormTemplate;
    $group->disabledPaymentGateways = $disabledGateways;
    $group->isHidden = $hidden;
    $group->headline = $headline;
    $group->tagline = $tagline;
    $group->save();
    if ($ids) {
        if ($changes) {
            logAdminActivity("Product Group Modified: '" . $group->name . "' - Changes: " . implode(". ", $changes) . " - Product Group ID: " . $group->id);
        }
    } else {
        logAdminActivity("Product Group Created: '" . $group->name . "' - Product Group ID: " . $group->id);
    }
    redir();
}
if ($sub == "deletegroup") {
    check_token("WHMCS.admin.default");
    checkPermission("Manage Product Groups");
    $groupId = (int) $whmcs->get_req_var("id");
    $group = WHMCS\Product\Group::find($groupId);
    logAdminActivity("Product Group Deleted: '" . $group->name . "' - Product Group ID: " . $group->id);
    $group->delete();
    redir();
}
if ($sub == "delete") {
    check_token("WHMCS.admin.default");
    checkPermission("Delete Products/Services");
    run_hook("ProductDelete", array("pid" => $id));
    logAdminActivity("Product Deleted: '" . $product->name . "' - Product Group ID: " . $product->id);
    $product->delete();
    delete_query("tblproductconfiglinks", array("pid" => $id));
    WHMCS\CustomField::where("type", "=", "product")->where("relid", "=", $id)->delete();
    redir();
}
if ($action == "updatesort") {
    check_token("WHMCS.admin.default");
    $order = (array) $whmcs->get_req_var("order");
    foreach ($order as $sort => $item) {
        $properties = explode("|", $item);
        list($type, $groupId, $itemId) = $properties;
        if ($type == "group") {
            checkPermission("Manage Product Groups");
            $group = WHMCS\Product\Group::find($groupId);
            if ($group->displayOrder != $sort) {
                logAdminActivity("Group Modified: '" . $group->name . "'" . " - Display Order Modified: '" . $group->displayOrder . "' to '" . $sort . "' - Group ID: " . $group->id);
                $group->displayOrder = $sort;
                $group->save();
            }
        } else {
            if ($type == "bundle") {
                checkPermission("Edit Products/Services");
                $bundle = WHMCS\Database\Capsule::table("tblbundles")->find($itemId);
                if ($bundle->sortorder != $sort) {
                    logAdminActivity("Bundle Modified: '" . $bundle->name . "'" . " - Display Order Modified: '" . $bundle->displayOrder . "' to '" . $sort . "' - Bundle ID: " . $bundle->id);
                    WHMCS\Database\Capsule::table("tblbundles")->where("id", "=", $itemId)->update(array("sortorder" => $sort));
                }
            } else {
                checkPermission("Edit Products/Services");
                $product = WHMCS\Product\Product::find($itemId);
                if ($product->displayOrder != $sort) {
                    logAdminActivity("Product Modified: '" . $product->name . "'" . " - Display Order Modified: '" . $product->displayOrder . "' to '" . $sort . "' - Product ID: " . $product->id);
                    $product->displayOrder = $sort;
                    $product->save();
                }
            }
        }
    }
    $aInt->setBodyContent(array("success" => true));
    $aInt->output();
    WHMCS\Terminus::getInstance()->doExit();
}
if ($action == "add-feature") {
    check_token("WHMCS.admin.default");
    $groupId = (int) $whmcs->get_req_var("groupId");
    if (!$groupId) {
        WHMCS\Terminus::getInstance()->doExit();
    }
    $newFeature = $whmcs->get_req_var("feature");
    $feature = new WHMCS\Product\Group\Feature();
    $feature->productGroupId = $groupId;
    $feature->feature = $newFeature;
    $maxOrder = WHMCS\Product\Group\Feature::orderBy("order", "desc")->where("product_group_id", "=", $groupId)->first(array("order"));
    $feature->order = $maxOrder->order + 1;
    $feature->save();
    $output = array();
    $output["html"] = "<div class=\"list-group-item\" data-id=\"" . $feature->id . "\">\n    <span class=\"badge remove-feature\" data-id=\"" . $feature->id . "\">\n        <i class=\"glyphicon glyphicon-remove\"></i>\n    </span>\n    <span class=\"glyphicon glyphicon-move\" aria-hidden=\"true\"></span>\n    " . $feature->feature . "\n</div>";
    $output["message"] = AdminLang::trans("products.featureAddSuccess");
    $aInt->setBodyContent($output);
    $aInt->display();
    logAdminActivity("Product Group Modified: Feature Added: '" . $feature->feature . "' - Product Group ID: " . $groupId);
    WHMCS\Terminus::getInstance()->doExit();
}
if ($action == "remove-feature") {
    check_token("WHMCS.admin.default");
    $groupId = (int) $whmcs->get_req_var("groupId");
    $featureId = (int) $whmcs->get_req_var("feature");
    if (!$groupId || !$featureId) {
        WHMCS\Terminus::getInstance()->doExit();
    }
    $feature = WHMCS\Product\Group\Feature::find($featureId);
    logAdminActivity("Product Group Modified: Feature Removed: '" . $feature->feature . "' - Product Group ID: " . $groupId);
    $feature->delete();
    echo AdminLang::trans("products.featureDeleteSuccess");
    WHMCS\Terminus::getInstance()->doExit();
}
if ($action == "feature-sort") {
    check_token("WHMCS.admin.default");
    $order = (array) $whmcs->get_req_var("order");
    $features = WHMCS\Product\Group\Feature::whereIn("id", array_values($order))->get();
    $productGroupId = 0;
    foreach ($features as $feature) {
        $feature->order = array_search($feature->id, $order);
        $feature->save();
        $productGroupId = $feature->productGroupId;
    }
    if ($productGroupId) {
        logAdminActivity("Product Group Modified: Feature Sort Updated - Product Group ID: " . $productGroupId);
    }
    echo AdminLang::trans("products.featureSortSuccess");
    WHMCS\Terminus::getInstance()->doExit();
}
ob_start();
if ($action == "") {
    $result = select_query("tblproductgroups", "COUNT(*)", "");
    $data = mysql_fetch_array($result);
    $num_rows = $data[0];
    $result = select_query("tblproducts", "COUNT(*)", "");
    $data = mysql_fetch_array($result);
    $num_rows2 = $data[0];
    $aInt->deleteJSConfirm("doDelete", "products", "deleteproductconfirm", "?sub=delete&id=");
    $aInt->deleteJSConfirm("doGroupDelete", "products", "deletegroupconfirm", "?sub=deletegroup&id=");
    $aInt->deleteJSConfirm("doBundleDelete", "bundles", "deletebundleconfirm", "configbundles.php?action=delete&id=");
    $marketConnectInactiveServices = array();
    $showMarketConnectPromos = true;
    $dismissedProductPromotions = json_decode(WHMCS\Config\Setting::getValue("MarketConnectDismissedPromos"), true);
    if (!is_array($dismissedProductPromotions)) {
        $dismissedProductPromotions = array();
    }
    if (array_key_exists($aInt->getAdminID(), $dismissedProductPromotions)) {
        $version = App::getVersion()->getVersion();
        if (version_compare($dismissedProductPromotions[$aInt->getAdminID()], $version) != -1) {
            $showMarketConnectPromos = false;
        }
    }
    if ($showMarketConnectPromos) {
        $marketConnectInactiveServices = array_filter(WHMCS\MarketConnect\MarketConnect::getServices(), function ($var) {
            return !in_array($var, WHMCS\MarketConnect\MarketConnect::getActiveServices());
        });
    }
    $marketConnectPromos = array();
    $learnMore = AdminLang::trans("global.learnMore");
    foreach ($marketConnectInactiveServices as $mcService) {
        $promotionInfo = WHMCS\MarketConnect\Promotion::SERVICES[$mcService];
        if ($promotionInfo) {
            $logo = WHMCS\View\Asset::imgTag("marketconnect/" . $mcService . "/logo-sml.png", $promotionInfo["serviceTitle"]);
            $title = AdminLang::trans("marketConnect.add", array(":product" => $promotionInfo["vendorName"] . " " . $promotionInfo["serviceTitle"]));
            $href = "marketconnect.php?learnmore=" . $mcService;
            $marketConnectPromos[] = "<a href=\"" . $href . "\" target=\"_blank\" class=\"mc-promo bordered clearfix\">\n    <div class=\"logo\">" . $logo . "</div>\n    <div class=\"content\">\n        <h2 class=\"truncate\">" . $title . "</h2>\n        <p>" . $promotionInfo["description"] . "</p>\n    </div>\n</a>";
        }
    }
    $marketConnectPromosOutput = "";
    if (0 < count($marketConnectPromos)) {
        $marketConnectPromosOutput = "<div class=\"pull-right\">\n    <a href=\"#\" id=\"dismissPromos\"><i class=\"fal fa-times\"></i></a>\n</div>\n<div class=\"product-mc-promos\">\n    <div class=\"owl-carousel owl-theme\" id=\"mcConfigureProductPromos\">";
        foreach ($marketConnectPromos as $promo) {
            $marketConnectPromosOutput .= "<div class=\"item\">" . $promo . "</div>";
        }
        $marketConnectPromosOutput .= "</div>\n</div>";
    }
    echo "\n<p>";
    echo $aInt->lang("products", "description");
    echo "</p>\n\n<div class=\"btn-group\" role=\"group\">\n    <a id=\"Create-Group-link\" href=\"";
    echo $whmcs->getPhpSelf();
    echo "?action=creategroup\" class=\"btn btn-default\"><i class=\"fas fa-plus\"></i> ";
    echo $aInt->lang("products", "createnewgroup");
    echo "</a>\n    <a id=\"Create-Product-link\" href=\"";
    echo $whmcs->getPhpSelf();
    echo "?action=create\" class=\"btn btn-default";
    if ($num_rows == 0) {
        echo " btn-disabled\" disabled=\"disabled";
    }
    echo "\"><i class=\"fas fa-plus-circle\"></i> ";
    echo $aInt->lang("products", "createnewproduct");
    echo "</a>\n    <a id=\"Duplicate-Product-link\" href=\"";
    echo $whmcs->getPhpSelf();
    echo "?action=duplicate\" class=\"btn btn-default";
    if ($num_rows2 == 0) {
        echo " btn-disabled\" disabled=\"disabled";
    }
    echo "\"><i class=\"fas fa-plus-square\"></i> ";
    echo $aInt->lang("products", "duplicateproduct");
    echo "</a>\n</div>\n\n";
    echo $marketConnectPromosOutput;
    echo "\n<div id=\"tableBackground\" class=\"tablebg\">\n    <table class=\"datatable no-margin\" width=\"100%\" border=\"0\" cellspacing=\"1\" cellpadding=\"3\">\n        <tr>\n            <th style=\"width: 23%;\">";
    echo $aInt->lang("products", "productname");
    echo "</th>\n            <th style=\"width: 18%;\">";
    echo $aInt->lang("fields", "type");
    echo "</th>\n            <th style=\"width: 18%;\">";
    echo $aInt->lang("products", "paytype");
    echo "</th>\n            <th style=\"width: 17%;\">";
    echo $aInt->lang("products", "stock");
    echo "</th>\n            <th style=\"width: 18%;\">";
    echo $aInt->lang("products", "autosetup");
    echo "</th>\n            <th style=\"width: 2%;\"></th>\n            <th style=\"width: 2%;\"></th>\n            <th style=\"width: 2%;\"></th>\n        </tr>\n    </table>\n";
    $result = select_query("tblproductgroups", "", "", "order", "DESC");
    $data = mysql_fetch_array($result);
    $lastorder = $data["order"];
    $result2 = select_query("tblproductgroups", "", "", "order", "ASC");
    $k = 0;
    while ($data = mysql_fetch_array($result2)) {
        $k++;
        $groupid = $data["id"];
        update_query("tblproductgroups", array("order" => $k), array("id" => $groupid));
        $name = $data["name"];
        $hidden = $data["hidden"];
        $order = $data["order"];
        $result = select_query("tblproducts", "COUNT(*)", array("gid" => $groupid));
        $data = mysql_fetch_array($result);
        $num_rows = $data[0];
        if (0 < $num_rows) {
            $deletelink = "alert('" . $aInt->lang("products", "deletegrouperror", 1) . "')";
        } else {
            $deletelink = "doGroupDelete('" . $groupid . "')";
        }
        echo "\n    <table class=\"datatable sort-groups no-margin\" data-id=\"group|" . $groupid . "|0\" width=\"100%\" border=\"0\" cellspacing=\"1\" cellpadding=\"3\">\n        <tr>\n            <td colspan=\"6\" style=\"width: 96%; background-color:#f3f3f3;\">\n                <div class=\"prodGroup\" align=\"left\">\n                    &nbsp;\n                    <span class=\"glyphicon glyphicon-move\" aria-hidden=\"true\"></span>\n                    &nbsp;<strong>" . $aInt->lang("fields", "groupname") . ":</strong>\n                    " . $name . " ";
        if ($hidden) {
            echo "(Hidden) ";
        }
        echo "\n                </div>\n            </td>\n            <td style=\"width: 2%; background-color:#f3f3f3;\" align=center>\n                <a href=\"?action=editgroup&ids=" . $groupid . "\">\n                    <img src=\"images/edit.gif\" border=\"0\" alt=\"" . $aInt->lang("global", "edit") . "\">\n                </a>\n            </td>\n            <td style=\"width: 2%; background-color:#f3f3f3;\" align=center>\n                <a href=\"#\" onClick=\"" . $deletelink . ";return false\">\n                    <img src=\"images/delete.gif\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\">\n                </a>\n            </td>\n        </tr>\n";
        $basicProductExpression = WHMCS\Database\Capsule::connection()->raw("`id`,`type`,`name`,`paytype`,`autosetup`,`proratabilling`,`stockcontrol`,`qty`,`servertype`,`hidden`,`order`,\n(SELECT COUNT(*) FROM tblhosting WHERE tblhosting.packageid=tblproducts.id) AS usagecount");
        $basicProducts = WHMCS\Database\Capsule::table("tblproducts")->select($basicProductExpression)->where("gid", "=", $groupid)->orderBy("order")->orderBy("name")->get();
        $bundleProductExpression = WHMCS\Database\Capsule::connection()->raw("id, name, sortorder as `order`");
        $bundleProducts = WHMCS\Database\Capsule::table("tblbundles")->select($bundleProductExpression)->where("gid", "=", $groupid)->orderBy("order")->orderBy("name")->get();
        $fillColumns = array("type", "paytype", "autosetup", "proratabilling", "stockcontrol", "qty", "servertype", "hidden", "usagecount");
        foreach ($bundleProducts as $row => $bundle) {
            foreach ($fillColumns as $column) {
                if ($column == "paytype") {
                    $bundle->{$column} = "-";
                } else {
                    if ($column == "type") {
                        $bundle->{$column} = "bundle";
                    } else {
                        $bundle->{$column} = "";
                    }
                }
            }
            $bundleProducts[$row] = $bundle;
        }
        $outputs = array_merge($basicProducts, $bundleProducts);
        usort($outputs, function ($a, $b) {
            $ordering = strnatcmp($a->order, $b->order);
            if ($ordering) {
                return $ordering;
            }
            return strnatcmp($a->name, $b->name);
        });
        $i = 0;
        echo "<tbody id=\"tbodyGroupProduct" . $groupid . "\" class=\"list-group\">";
        foreach ($outputs as $output) {
            $id = $output->id;
            $type = $output->type;
            $name = $output->name;
            $paytype = $output->paytype;
            $autosetup = $output->autosetup;
            $proratabilling = $output->proratabilling;
            $stockcontrol = $output->stockcontrol;
            $qty = $output->qty;
            $hidden = $output->hidden;
            $sortorder = $output->order;
            $num_rows = $output->usagecount;
            $moduleName = $output->servertype;
            if ($moduleName) {
                $module = new WHMCS\Module\Server();
                $module->load($moduleName);
                $moduleDisplayName = $module->getDisplayName();
            } else {
                $moduleDisplayName = "";
            }
            if (0 < $num_rows) {
                $deletelink = "alert('" . $aInt->lang("products", "deleteproducterror", 1) . "')";
            } else {
                $deletelink = "doDelete('" . $id . "')";
            }
            if ($autosetup == "on") {
                $autosetup = $aInt->lang("products", "asetupafteracceptpendingorder");
            } else {
                if ($autosetup == "order") {
                    $autosetup = $aInt->lang("products", "asetupinstantlyafterorder");
                } else {
                    if ($autosetup == "payment") {
                        $autosetup = $aInt->lang("products", "asetupafterpay");
                    } else {
                        if ($autosetup == "") {
                            $autosetup = $aInt->lang("products", "off");
                        }
                    }
                }
            }
            if ($paytype == "free") {
                $paymenttype = AdminLang::trans("billingcycles.free");
            } else {
                if ($paytype == "onetime") {
                    $paymenttype = AdminLang::trans("billingcycles.onetime");
                } else {
                    if ($paytype == "-") {
                        $paymenttype = "-";
                    } else {
                        $paymenttype = AdminLang::trans("status.recurring");
                    }
                }
            }
            if ($proratabilling) {
                $paymenttype .= " (" . $aInt->lang("products", "proratabilling") . ")";
            }
            $editLink = "<a href=\"?action=edit&id=" . $id . "\">\n        <img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . AdminLang::trans("global.edit") . "\">\n    </a>";
            $deleteLink = "<a href=\"#\" onClick=\"" . $deletelink . ";return false\">\n        <img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . AdminLang::trans("global.delete") . "\">\n    </a>";
            $sortOrderName = "so[" . $id . "]";
            if ($type == "hostingaccount") {
                $producttype = AdminLang::trans("products.hostingaccount");
            } else {
                if ($type == "reselleraccount") {
                    $producttype = AdminLang::trans("products.reselleraccount");
                } else {
                    if ($type == "server") {
                        $producttype = AdminLang::trans("products.dedicatedvpsserver");
                    } else {
                        if ($type == "bundle") {
                            $producttype = AdminLang::trans("products.bundle");
                            $editLink = "<a href=\"configbundles.php?action=manage&id=" . $id . "\">\n        <img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . AdminLang::trans("global.edit") . "\">\n    </a>";
                            $deleteLink = "<a href=\"#\" onClick=\"doBundleDelete('" . $id . "');return false\">\n        <img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . AdminLang::trans("global.delete") . "\">\n    </a>";
                            $sortOrderName = "sob[" . $id . "]";
                        } else {
                            $producttype = AdminLang::trans("products.otherproductservice");
                        }
                    }
                }
            }
            if ($moduleDisplayName) {
                $producttype .= " (" . $moduleDisplayName . ")";
            }
            if ($stockcontrol) {
                $qtystock = $qty;
            } else {
                $qtystock = "-";
            }
            if ($hidden) {
                $name .= " (Hidden)";
                $hidden = " style=\"background-color:#efefef;\"";
            } else {
                $hidden = "";
            }
            echo "    <tr class=\"product text-center\" data-id=\"" . $type . "|" . $groupid . "|" . $id . "\">\n        <td style=\"width: 23%;\" class=\"text-left\"" . $hidden . ">" . $name . "</td>\n        <td style=\"width: 18%;\" " . $hidden . ">" . $producttype . "</td>\n        <td style=\"width: 18%;\" " . $hidden . ">" . $paymenttype . "</td>\n        <td style=\"width: 17%;\" " . $hidden . ">" . $qtystock . "</td>\n        <td style=\"width: 18%;\" " . $hidden . ">" . $autosetup . "</td>\n        <td style=\"width: 2%;\" " . $hidden . ">\n            <span class=\"glyphicon glyphicon-move\" aria-hidden=\"true\"></span>\n        </td>\n        <td style=\"width: 2%;\" " . $hidden . ">" . $editLink . "</td>\n        <td style=\"width: 2%;\" " . $hidden . ">" . $deleteLink . "</td>\n    </tr>";
            $i++;
        }
        echo "\n</tbody>\n";
        if ($i == "0") {
            echo "\n            <tr>\n                <td colspan=\"8\" align=center>" . $aInt->lang("products", "noproductsingroupsetup") . "\n                </td>\n            </tr>\n        ";
        }
        echo "</table>\n";
        $i = 0;
    }
    if ($k == "0") {
        echo "\n        <table class=\"datatable no-margin\" width=\"100%\" border=\"0\" cellspacing=\"1\" cellpadding=\"3\">\n            <tr>\n                <td colspan=10 align=center>" . $aInt->lang("products", "nogroupssetup") . "</td>\n            </tr>\n        </table>\n    ";
    }
    echo "</div>\n\n";
    echo WHMCS\View\Asset::jsInclude("Sortable.min.js");
    echo "<script>\nvar successMsgShowing = false;\nvar sortOptions = {\n    handle: '.glyphicon-move',\n    ghostClass: 'ghost',\n    animation: 150,\n    store: {\n        /**\n         * Get the order of elements. Called once during initialization.\n         * @param   {Sortable}  sortable\n         * @returns {Array}\n         */\n        get: function (sortable) {\n            // Do nothing upon initialization.\n            return [];\n        },\n\n        /**\n         * Save the order of elements. Called onEnd (when the item is dropped).\n         * @param {Sortable}  sortable\n         */\n        set: function (sortable) {\n            var order = sortable.toArray();\n            var post = WHMCS.http.jqClient.post(\n                \"configproducts.php\",\n                {\n                    action: \"updatesort\",\n                    order: order,\n                    token: \"";
    echo generate_token("plain");
    echo "\"\n                }\n            );\n\n            post.done(\n                function(data) {\n                    ";
    echo WHMCS\View\Helper::jsGrowlNotification("success", "global.success", "global.changesuccessdesc");
    echo "                }\n            );\n        }\n    }\n};\n\n// Handle product/bundle sorting.\njQuery('*[id^=\"tbodyGroupProduct\"]').each(function(index, group) {\n    Sortable.create(group, sortOptions);\n});\n\n// Handle Group sorting.\nsortOptions.draggable = \".sort-groups\";\nsortOptions.group = { name: 'groups', pull: true, put: true };\nSortable.create(tableBackground, sortOptions);\n\n\$(document).ready(function () {\n    \$('.product-promo-carousel').owlCarousel({\n        items: 1,\n        loop: true,\n        center: true,\n        mouseDrag: true,\n        touchDrag: true,\n        autoplay: true,\n        autoplayTimeout: 4000,\n        autoplayHoverPause: true\n    });\n});\n</script>\n\n";
} else {
    if ($action == "edit") {
        $product = WHMCS\Product\Product::find($id);
        $result = select_query("tblproducts", "", array("id" => $id));
        $data = mysql_fetch_array($result);
        $id = $data["id"];
        $type = $data["type"];
        $groupid = $gid = $data["gid"];
        $name = $data["name"];
        $description = $data["description"];
        $showdomainops = $data["showdomainoptions"];
        $hidden = $data["hidden"];
        $welcomeemail = $data["welcomeemail"];
        $paytype = $data["paytype"];
        $allowqty = $data["allowqty"];
        $subdomain = $data["subdomain"];
        $autosetup = $data["autosetup"];
        $servergroup = $data["servergroup"];
        $stockcontrol = $data["stockcontrol"];
        $qty = $data["qty"];
        $proratabilling = $data["proratabilling"];
        $proratadate = $data["proratadate"];
        $proratachargenextmonth = $data["proratachargenextmonth"];
        $servertype = $data["servertype"];
        $freedomain = $data["freedomain"];
        $counter = 1;
        while ($counter <= 24) {
            $packageconfigoption[$counter] = isset($data["configoption" . $counter]) ? $data["configoption" . $counter] : NULL;
            $counter += 1;
        }
        $freedomainpaymentterms = $data["freedomainpaymentterms"];
        $freedomaintlds = $data["freedomaintlds"];
        $recurringcycles = $data["recurringcycles"];
        $autoterminatedays = $data["autoterminatedays"];
        $autoterminateemail = $data["autoterminateemail"];
        $tax = $data["tax"];
        $configoptionsupgrade = $data["configoptionsupgrade"];
        $billingcycleupgrade = $data["billingcycleupgrade"];
        $upgradeemail = $data["upgradeemail"];
        $overagesenabled = $data["overagesenabled"];
        $overagesdisklimit = $data["overagesdisklimit"];
        $overagesbwlimit = $data["overagesbwlimit"];
        $overagesdiskprice = $data["overagesdiskprice"];
        $overagesbwprice = $data["overagesbwprice"];
        $affiliatepayamount = $data["affiliatepayamount"];
        $affiliatepaytype = $data["affiliatepaytype"];
        $affiliateonetime = $data["affiliateonetime"];
        $retired = $data["retired"];
        $isFeatured = (bool) $data["is_featured"];
        $freedomainpaymentterms = explode(",", $freedomainpaymentterms);
        $freedomaintlds = explode(",", $freedomaintlds);
        $overagesenabled = explode(",", $overagesenabled);
        $upgradepackages = $product->getUpgradeProductIds();
        $downloadIds = $product->getDownloadIds();
        $order = $data["order"];
        $server = new WHMCS\Module\Server();
        $serverModules = $server->getListWithDisplayNames();
        if ($servertype) {
            $server->load($servertype);
        }
        echo WHMCS\View\Asset::jsInclude("jquerylq.js") . WHMCS\View\Asset::jsInclude("jqueryFileTree.js") . WHMCS\View\Asset::cssInclude("jqueryFileTree.css");
        echo "<h2>Edit Product</h2>\n<form method=\"post\" action=\"" . $_SERVER["PHP_SELF"] . "?action=save&id=" . $id;
        echo "\" name=\"packagefrm\">";
        $jscode = "function deletecustomfield(id) {\nif (confirm(\"Are you sure you want to delete this field and ALL DATA associated with it?\")) {\nwindow.location='" . $_SERVER["PHP_SELF"] . "?action=edit&id=" . $id . "&tab=4&sub=deletecustomfield&fid='+id+'" . generate_token("link") . "';\n}}";
        $jquerycode = "\$('#productdownloadsbrowser').fileTree({ root: '0', script: 'configproducts.php?action=getdownloads" . generate_token("link") . "', folderEvent: 'click', expandSpeed: 1, collapseSpeed: 1 }, function(file) {\n    WHMCS.http.jqClient.post(\"configproducts.php?action=managedownloads&id=" . $id . generate_token("link") . "&adddl=\"+file, function(data) {\n        \$(\"#productdownloadslist\").html(data);\n    });\n});\n\$(\".removedownload\").livequery(\"click\", function(event) {\n    var dlid = \$(this).attr(\"rel\");\n    WHMCS.http.jqClient.post(\"configproducts.php?action=managedownloads&id=" . $id . generate_token("link") . "&remdl=\"+dlid, function(data) {\n        \$(\"#productdownloadslist\").html(data);\n    });\n});\n\$(\"#showquickupload\").click(\n    function() {\n        \$(\"#modalQuickUpload\").modal(\"show\");\n        \$(\"#modalQuickUploadBody\").load(\"configproducts.php?action=quickupload&id=" . $id . generate_token("link") . "\");\n        return false;\n    }\n);\n\$(\"#showadddownloadcat\").click(\n    function() {\n        \$(\"#modalAddDownloadCategory\").modal(\"show\");\n        \$(\"#modalAddDownloadCategoryBody\").load(\"configproducts.php?action=adddownloadcat&id=" . $id . generate_token("link") . "\");\n        return false;\n    }\n);\n";
        if ($success) {
            infoBox($aInt->lang("global", "changesuccess"), $aInt->lang("global", "changesuccessdesc") . " <div style=\"float:right;margin-top:-15px;\"><input type=\"button\" value=\"&laquo; " . $aInt->lang("products", "backtoproductlist") . "\" onClick=\"window.location='configproducts.php'\" class=\"btn btn-default btn-sm\"></div>");
        }
        echo $infobox;
        if ($setupReset == "true") {
            infoBox($aInt->lang("global", "information"), $aInt->lang("products", "setupreset"));
            echo $infobox;
        }
        echo $aInt->beginAdminTabs(array($aInt->lang("products", "tabsdetails"), $aInt->lang("global", "pricing"), $aInt->lang("products", "tabsmodulesettings"), $aInt->lang("setup", "customfields"), $aInt->lang("setup", "configoptions"), $aInt->lang("products", "tabsupgrades"), $aInt->lang("products", "tabsfreedomain"), $aInt->lang("setup", "other"), $aInt->lang("products", "tabslinks")), true);
        echo "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "producttype");
        echo "</td><td class=\"fieldarea\"><select name=\"type\" class=\"form-control select-inline\" onChange=\"doFieldUpdate()\"><option value=\"hostingaccount\"";
        if ($type == "hostingaccount") {
            echo " SELECTED";
        }
        echo ">";
        echo $aInt->lang("products", "hostingaccount");
        echo "<option value=\"reselleraccount\"";
        if ($type == "reselleraccount") {
            echo " SELECTED";
        }
        echo ">";
        echo $aInt->lang("products", "reselleraccount");
        echo "<option value=\"server\"";
        if ($type == "server") {
            echo " SELECTED";
        }
        echo ">";
        echo $aInt->lang("products", "dedicatedvpsserver");
        echo "<option value=\"other\"";
        if ($type == "other") {
            echo " SELECTED";
        }
        echo ">";
        echo $aInt->lang("setup", "other");
        echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("products", "productgroup");
        echo "</td><td class=\"fieldarea\"><select name=\"gid\" class=\"form-control select-inline\">";
        $result = select_query("tblproductgroups", "", "", "order", "ASC");
        while ($data = mysql_fetch_array($result)) {
            $select_gid = $data["id"];
            $select_name = $data["name"];
            echo "<option value=\"" . $select_gid . "\"";
            if ($select_gid == $groupid) {
                echo " selected";
            }
            echo ">" . $select_name . "</option>";
        }
        echo "</select></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
        echo $aInt->lang("products", "productname");
        echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" size=\"40\" name=\"name\" value=\"";
        echo $name;
        echo "\" class=\"form-control input-400 input-inline\">\n        ";
        echo $aInt->getTranslationLink("product.name", $id);
        echo "    </td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
        echo $aInt->lang("products", "productdesc");
        echo "    </td>\n    <td class=\"fieldarea\">\n        <div class=\"row\">\n            <div class=\"col-sm-7\">\n                <textarea name=\"description\" rows=\"5\" class=\"form-control\">";
        echo WHMCS\Input\Sanitize::encode($description);
        echo "</textarea>\n            </div>\n            <div class=\"col-sm-5\">\n                    ";
        echo $aInt->getTranslationLink("product.description", $id);
        echo "<br />\n                    ";
        echo $aInt->lang("products", "htmlallowed");
        echo "<br>\n                    &lt;br /&gt; ";
        echo $aInt->lang("products", "htmlnewline");
        echo "<br>\n                    &lt;strong&gt;";
        echo $aInt->lang("products", "htmlbold");
        echo "&lt;/strong&gt; <b>";
        echo $aInt->lang("products", "htmlbold");
        echo "</b><br>\n                    &lt;em&gt;";
        echo $aInt->lang("products", "htmlitalics");
        echo "&lt;/em&gt; <i>";
        echo $aInt->lang("products", "htmlitalics");
        echo "</i>\n            </div>\n        </div>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("products", "welcomeemail");
        echo "</td><td class=\"fieldarea\"><select name=\"welcomeemail\" class=\"form-control select-inline\"><option value=\"0\">";
        echo $aInt->lang("global", "none");
        echo "</option>";
        $emails = array("Hosting Account Welcome Email", "Reseller Account Welcome Email", "Dedicated/VPS Server Welcome Email", "SHOUTcast Welcome Email", "Other Product/Service Welcome Email");
        foreach ($emails as $email) {
            $mailTemplates = WHMCS\Mail\Template::where("type", "=", "product")->where("name", "=", $email)->where("language", "=", "")->get();
            foreach ($mailTemplates as $template) {
                echo "<option value=\"" . $template->id . "\"";
                if ($template->id == $welcomeemail) {
                    echo " selected";
                }
                echo ">" . $template->name . "</option>";
            }
        }
        $customProductMailTemplates = WHMCS\Mail\Template::where("type", "=", "product")->where("custom", "=", 1)->where("language", "=", "")->orderBy("name")->get();
        foreach ($customProductMailTemplates as $template) {
            echo "<option value=\"" . $template->id . "\"";
            if ($template->id == $welcomeemail) {
                echo " selected";
            }
            echo ">" . $template->name . "</option>";
        }
        echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("products", "requiredomain");
        echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"showdomainops\"";
        if ($showdomainops) {
            echo " checked";
        }
        echo "> ";
        echo $aInt->lang("products", "domainregoptionstick");
        echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("products", "stockcontrol");
        echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"stockcontrol\"";
        if ($stockcontrol) {
            echo " checked";
        }
        echo "> ";
        echo $aInt->lang("products", "stockcontroldesc");
        echo ":</label> <input type=\"text\" name=\"qty\" value=\"";
        echo $qty;
        echo "\" class=\"form-control input-80 input-inline text-center\"></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("products", "applytax");
        echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"tax\"";
        if ($tax == "1") {
            echo " checked";
        }
        echo "> ";
        echo $aInt->lang("products", "applytaxdesc");
        echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo AdminLang::trans("fields.featured");
        echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"isFeatured\"";
        if ($isFeatured) {
            echo " checked";
        }
        echo "> ";
        echo AdminLang::trans("products.featuredDescription");
        echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "hidden");
        echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"hidden\"";
        if ($hidden) {
            echo " checked";
        }
        echo "> ";
        echo $aInt->lang("products", "hiddendesc");
        echo "</label></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
        echo $aInt->lang("products", "retired");
        echo "    </td>\n    <td class=\"fieldarea\">\n        <label class=\"checkbox-inline\">\n            <input id=\"inputRequired\" type=\"checkbox\" name=\"retired\" value=\"1\"";
        if ($retired) {
            echo " checked";
        }
        echo ">\n            ";
        echo $aInt->lang("products", "retireddesc");
        echo "        </label>\n    </td>\n</tr>\n</table>\n\n";
        echo $aInt->nextAdminTab();
        echo "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n    ";
        if ($server->isMetaDataValueSet("NoEditPricing") && $server->getMetaDataValue("NoEditPricing")) {
            $configurationLink = $server->call("get_configuration_link", array("model" => $product));
            echo "<input type=\"hidden\" name=\"paytype\" value=\"" . $paytype . "\" />" . "<div class=\"marketconnect-product-redirect\" role=\"alert\">\n                " . AdminLang::trans("products.marketConnectManageRedirectMsg") . "<br>\n                <a href=\"" . $configurationLink . "\" class=\"btn btn-default btn-sm\">" . AdminLang::trans("products.marketConnectManageRedirectBtn") . "</a>\n            </div>";
        } else {
            echo "<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("products", "paymenttype");
            echo "</td><td class=\"fieldarea\"><label class=\"radio-inline\"><input type=\"radio\" name=\"paytype\" id=\"PayType-Free\" value=\"free\" onclick=\"hidePricingTable()\"";
            if ($paytype == "free") {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("billingcycles", "free");
            echo "</label> <label class=\"radio-inline\"><input type=\"radio\" name=\"paytype\" value=\"onetime\" id=\"PayType-OneTime\" onclick=\"showPricingTable(false)\"";
            if ($paytype == "onetime") {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("billingcycles", "onetime");
            echo "</label> <label class=\"radio-inline\"><input type=\"radio\" name=\"paytype\" value=\"recurring\" id=\"PayType-Recurring\" onclick=\"showPricingTable(true)\"";
            if ($paytype == "recurring") {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("global", "recurring");
            echo "</label></td></tr>\n<tr id=\"trPricing\"";
            if ($paytype == "free") {
                echo " style=\"display:none;\"";
            }
            echo "><td colspan=\"2\" align=\"center\"><br>\n    <div class=\"row\">\n        <div class=\"col-sm-10 col-sm-offset-1\">\n            <table id=\"pricingtbl\" class=\"table table-condensed\">\n                <tr bgcolor=\"#efefef\" style=\"text-align:center;font-weight:bold\">\n                    <td>";
            echo $aInt->lang("currencies", "currency");
            echo "</td>\n                    <td></td>\n                    <td>";
            echo $aInt->lang("billingcycles", "onetime");
            echo "/";
            echo $aInt->lang("billingcycles", "monthly");
            echo "</td>\n                    <td class=\"prod-pricing-recurring\">";
            echo $aInt->lang("billingcycles", "quarterly");
            echo "</td>\n                    <td class=\"prod-pricing-recurring\">";
            echo $aInt->lang("billingcycles", "semiannually");
            echo "</td>\n                    <td class=\"prod-pricing-recurring\">";
            echo $aInt->lang("billingcycles", "annually");
            echo "</td>\n                    <td class=\"prod-pricing-recurring\">";
            echo $aInt->lang("billingcycles", "biennially");
            echo "</td>\n                    <td class=\"prod-pricing-recurring\">";
            echo $aInt->lang("billingcycles", "triennially");
            echo "</td>\n                </tr>\n";
            $result = select_query("tblcurrencies", "id,code", "", "code", "ASC");
            while ($data = mysql_fetch_array($result)) {
                $currency_id = $data["id"];
                $currency_code = $data["code"];
                $result2 = select_query("tblpricing", "", array("type" => "product", "currency" => $currency_id, "relid" => $id));
                $data = mysql_fetch_array($result2);
                $pricing_id = $data["id"];
                $cycles = array("monthly", "quarterly", "semiannually", "annually", "biennially", "triennially");
                if (!$pricing_id) {
                    $insertarr = array("type" => "product", "currency" => $currency_id, "relid" => $id);
                    foreach ($cycles as $cycle) {
                        $insertarr[$cycle] = "-1";
                    }
                    insert_query("tblpricing", $insertarr);
                    $result2 = select_query("tblpricing", "", array("type" => "product", "currency" => $currency_id, "relid" => $id));
                    $data = mysql_fetch_array($result2);
                }
                $setupfields = $pricingfields = $disablefields = "";
                foreach ($cycles as $i => $cycle) {
                    $price = $data[$cycle];
                    $class = 1 <= $i ? " class=\"prod-pricing-recurring\"" : "";
                    $setupfields .= "<td" . $class . "><input type=\"text\" name=\"currency[" . $currency_id . "][" . substr($cycle, 0, 1) . "setupfee]\" id=\"setup_" . $currency_code . "_" . $cycle . "\" value=\"" . $data[substr($cycle, 0, 1) . "setupfee"] . "\"" . ($price == "-1" ? " style=\"display:none\"" : "") . " class=\"form-control input-inline input-100 text-center\" /></td>";
                    $pricingfields .= "<td" . $class . "><input type=\"text\" name=\"currency[" . $currency_id . "][" . $cycle . "]\" id=\"pricing_" . $currency_code . "_" . $cycle . "\" size=\"10\" value=\"" . $price . "\"" . ($price == "-1" ? " style=\"display:none;\"\"" : "") . " class=\"form-control input-inline input-100 text-center\" /></td>";
                    $disablefields .= "<td" . $class . "><input type=\"checkbox\" class=\"pricingtgl\" currency=\"" . $currency_code . "\" cycle=\"" . $cycle . "\"" . ($price == "-1" ? "" : " checked=\"checked\"") . " /></td>";
                }
                echo "<tr bgcolor=\"#ffffff\" style=\"text-align:center\">\n            <td rowspan=\"3\" bgcolor=\"#efefef\"><b>" . $currency_code . "</b></td>\n            <td>" . $aInt->lang("fields", "setupfee") . "</td>\n            " . $setupfields . "\n        </tr>\n        <tr bgcolor=\"#ffffff\" style=\"text-align:center\">\n            <td>" . $aInt->lang("fields", "price") . "</td>\n            " . $pricingfields . "\n        </tr>\n        <tr bgcolor=\"#ffffff\" style=\"text-align:center\">\n            <td>" . $aInt->lang("global", "enable") . "</td>\n            " . $disablefields . "\n        </tr>";
            }
            $jscode .= "\nfunction hidePricingTable() {\n    \$(\"#trPricing\").fadeOut();\n}\nfunction showPricingTable(recurring) {\n    if (\$(\"#trPricing\").is(\":visible\")) {\n        if (recurring) {\n            \$(\"#trPricing .table\").css(\"max-width\", \"\");\n            \$(\".prod-pricing-recurring\").fadeIn();\n        } else {\n            \$(\".prod-pricing-recurring\").fadeOut(\"fast\", function() {\n                \$(\"#trPricing .table\").css(\"max-width\", \"370px\");\n            });\n        }\n    } else {\n        \$(\"#trPricing\").fadeIn();\n        if (recurring) {\n            \$(\"#trPricing .table\").css(\"max-width\", \"\");\n            \$(\".prod-pricing-recurring\").show();\n        } else {\n            \$(\"#trPricing .table\").css(\"max-width\", \"370px\");\n            \$(\".prod-pricing-recurring\").hide();\n        }\n    }\n}\n";
            $jquerycode .= "\$(\".pricingtgl\").click(function() {\n    var cycle = \$(this).attr(\"cycle\");\n    var currency = \$(this).attr(\"currency\");\n\n    if (\$(this).is(\":checked\")) {\n\n        \$(\"#pricing_\" + currency + \"_\" + cycle).val(\"0.00\").show();\n        \$(\"#setup_\" + currency + \"_\" + cycle).show();\n    } else {\n        \$(\"#pricing_\" + currency + \"_\" + cycle).val(\"-1.00\").hide();\n        \$(\"#setup_\" + currency + \"_\" + cycle).hide();\n    }\n});";
            echo "            </table>\n        </div>\n    </div>\n</td></tr>\n    ";
        }
        echo "<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("products", "allowqty");
        echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"allowqty\" value=\"1\"";
        if ($allowqty) {
            echo " checked";
        }
        echo " /> ";
        echo $aInt->lang("products", "allowqtydesc");
        echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("products", "recurringcycleslimit");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"recurringcycles\" value=\"";
        echo $recurringcycles;
        echo "\" class=\"form-control input-80 input-inline text-center\" /> ";
        echo $aInt->lang("products", "recurringcycleslimitdesc");
        echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("products", "autoterminatefixedterm");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"autoterminatedays\" value=\"";
        echo $autoterminatedays;
        echo "\" class=\"form-control input-80 input-inline text-center\" /> ";
        echo $aInt->lang("products", "autoterminatefixedtermdesc");
        echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("products", "terminationemail");
        echo "</td><td class=\"fieldarea\"><select name=\"autoterminateemail\" class=\"form-control select-inline\"><option value=\"0\">";
        echo $aInt->lang("global", "none");
        echo "</option>";
        $productMailTemplates = WHMCS\Mail\Template::where("type", "=", "product")->where("custom", "=", 1)->where("language", "=", "")->orderBy("name")->get();
        foreach ($productMailTemplates as $template) {
            echo "<option value=\"" . $template->id . "\"";
            if ($template->id == $autoterminateemail) {
                echo " selected";
            }
            echo ">" . $template->name . "</option>";
        }
        echo "</select> ";
        echo $aInt->lang("products", "chooseemailtplfixedtermend");
        echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("products", "proratabilling");
        echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" id=\"prorataBilling\" name=\"proratabilling\"";
        if ($proratabilling) {
            echo " checked";
        }
        echo "> ";
        echo $aInt->lang("products", "tickboxtoenable");
        echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("products", "proratadate");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" id=\"prorataDate\" name=\"proratadate\" value=\"";
        echo $proratadate;
        echo "\"class=\"form-control input-80 input-inline text-center\"> ";
        echo $aInt->lang("products", "proratadatedesc");
        echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("products", "chargenextmonth");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" id=\"prorataChargeNextMonth\" name=\"proratachargenextmonth\" value=\"";
        echo $proratachargenextmonth;
        echo "\"class=\"form-control input-80 input-inline text-center\"> ";
        echo $aInt->lang("products", "chargenextmonthdesc");
        echo "</td></tr>\n</table>\n\n";
        echo $aInt->nextAdminTab();
        if ($server->isMetaDataValueSet("NoEditModuleSettings") && $server->getMetaDataValue("NoEditModuleSettings")) {
            $configurationLink = $server->call("get_configuration_link", array("model" => $product));
            echo "<input type=\"hidden\" name=\"servertype\" id=\"inputModule\" value=\"" . $servertype . "\" />" . "<div class=\"marketconnect-product-redirect\" role=\"alert\">\n                " . AdminLang::trans("products.marketConnectManageRedirectMsg") . "<br>\n                <a href=\"" . $configurationLink . "\" class=\"btn btn-default btn-sm\">" . AdminLang::trans("products.marketConnectManageRedirectBtn") . "</a>\n            </div>";
        } else {
            echo "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\" width=\"15%\">";
            echo $aInt->lang("products", "modulename");
            echo "</td><td class=\"fieldarea\" width=\"40%\"><select name=\"servertype\" id=\"inputModule\" class=\"form-control select-inline\" onchange=\"fetchModuleSettings('";
            echo $id;
            echo "', 'simple');\"><option value=\"\">";
            echo $aInt->lang("global", "none");
            foreach ($serverModules as $moduleName => $displayName) {
                echo "<option value=\"" . $moduleName . "\"" . ($moduleName == $servertype ? " selected" : "") . ">" . $displayName . "</option>";
            }
            echo "</select> <img src=\"images/loading.gif\" id=\"moduleSettingsLoader\"></td>\n<td class=\"fieldlabel\" width=\"15%\">";
            echo $aInt->lang("products", "servergroup");
            echo "</td><td class=\"fieldarea\"><select name=\"servergroup\" id=\"inputServerGroup\" class=\"form-control select-inline\" onchange=\"fetchModuleSettings('";
            echo $id;
            echo "', 'simple');\"><option value=\"0\" data-server-types=\"\">";
            echo $aInt->lang("global", "none");
            echo "</option>";
            $serverGroups = WHMCS\Database\Capsule::table("tblservergroups")->join("tblservergroupsrel", "tblservergroups.id", "=", "tblservergroupsrel.groupid")->join("tblservers", "tblservergroupsrel.serverid", "=", "tblservers.id")->groupBy("tblservergroups.id")->selectRaw("tblservergroups.id,tblservergroups.name,CONCAT(\",\", GROUP_CONCAT(DISTINCT tblservers.type SEPARATOR \",\"), \",\") as server_types")->get();
            foreach ($serverGroups as $group) {
                $option = "<option value=\"" . $group->id . "\"";
                $option .= " data-server-types=\"" . $group->server_types . "\"";
                if ($group->id == $servergroup) {
                    $option .= " selected";
                }
                $option .= ">" . $group->name . "</option>";
                echo $option;
            }
            echo "</select></td>\n</tr>\n</table>\n\n<div id=\"serverReturnedError\" class=\"alert alert-warning hidden\" style=\"margin:10px 0;\">\n    <i class=\"fas fa-exclamation-triangle\"></i>\n    <span id=\"serverReturnedErrorText\"></span>\n</div>\n\n<table class=\"form module-settings\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\" id=\"tblModuleSettings\">\n    <tr id=\"noModuleSelectedRow\">\n        <td>\n            <div class=\"no-module-selected\">\n                ";
            echo AdminLang::trans("products.moduleSettingsChooseAProduct");
            echo "            </div>\n        </td>\n    </tr>\n</table>\n<div class=\"module-settings-mode hidden\" id=\"mode-switch\" data-mode=\"simple\">\n    <a class=\"btn btn-sm btn-link\">\n        <span class=\"text-simple hidden\">";
            echo AdminLang::trans("products.switchSimple");
            echo "</span>\n        <span class=\"text-advanced hidden\">";
            echo AdminLang::trans("products.switchAdvanced");
            echo "</span>\n    </a>\n</div>\n";
        }
        echo "<table class=\"form module-settings-automation module-settings-loading\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\" id=\"tblModuleAutomationSettings\">\n    <tr>\n        <td width=\"20\">\n            <input type=\"radio\" name=\"autosetup\" value=\"order\" id=\"autosetup_order\" disabled";
        if ($autosetup == "order") {
            echo " checked";
        }
        echo ">\n        </td>\n        <td class=\"fieldarea\">\n            <label for=\"autosetup_order\" class=\"checkbox-inline\">";
        echo $aInt->lang("products", "asetupinstantlyafterorderdesc");
        echo "</label>\n        </td>\n    </tr>\n    <tr>\n        <td>\n            <input type=\"radio\" name=\"autosetup\" value=\"payment\" disabled id=\"autosetup_payment\"";
        if ($autosetup == "payment") {
            echo " checked";
        }
        echo ">\n        </td>\n        <td class=\"fieldarea\">\n            <label for=\"autosetup_payment\" class=\"checkbox-inline\">";
        echo $aInt->lang("products", "asetupafterpaydesc");
        echo "</label>\n        </td>\n    </tr>\n    <tr>\n        <td>\n            <input type=\"radio\" name=\"autosetup\" value=\"on\" disabled id=\"autosetup_on\"";
        if ($autosetup == "on") {
            echo " checked";
        }
        echo ">\n        </td>\n        <td class=\"fieldarea\">\n            <label for=\"autosetup_on\" class=\"checkbox-inline\">";
        echo $aInt->lang("products", "asetupmadesc");
        echo "</label>\n        </td>\n    </tr>\n    <tr>\n        <td>\n            <input type=\"radio\" name=\"autosetup\" value=\"\" disabled id=\"autosetup_no\"";
        if ($autosetup == "") {
            echo " checked";
        }
        echo ">\n        </td>\n        <td class=\"fieldarea\">\n            <label for=\"autosetup_no\" class=\"checkbox-inline\">";
        echo $aInt->lang("products", "noautosetupdesc");
        echo "</label>\n        </td>\n    </tr>\n</table>\n\n<script>\n\$(document).ready(function(){\n    var moduleSettingsFetched = false;\n    \$('a[data-toggle=\"tab\"]').on('shown.bs.tab', function (e) {\n        if (moduleSettingsFetched) {\n            return;\n        }\n        var href = \$(this).attr('href');\n        if (href == '#tab3') {\n            fetchModuleSettings('";
        echo $id;
        echo "');\n            moduleSettingsFetched = true;\n        }\n    });\n    if (\$('#inputModule').val() != '' && '";
        echo App::getFromRequest("tab") == 3;
        echo "') {\n        fetchModuleSettings('";
        echo $id;
        echo "');\n    }\n});\n</script>\n\n";
        echo $aInt->nextAdminTab();
        echo "\n";
        $result = select_query("tblcustomfields", "", array("type" => "product", "relid" => $id), "sortorder` ASC,`id", "ASC");
        while ($data = mysql_fetch_array($result)) {
            $fid = $data["id"];
            $fieldname = $data["fieldname"];
            $fieldtype = $data["fieldtype"];
            $description = $data["description"];
            $fieldoptions = $data["fieldoptions"];
            $regexpr = $data["regexpr"];
            $adminonly = $data["adminonly"];
            $required = $data["required"];
            $showorder = $data["showorder"];
            $showinvoice = $data["showinvoice"];
            $sortorder = $data["sortorder"];
            echo "<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr>\n    <td class=\"fieldlabel\">\n        ";
            echo $aInt->lang("customfields", "fieldname");
            echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"customfieldname[";
            echo $fid;
            echo "]\" value=\"";
            echo $fieldname;
            echo "\" class=\"form-control input-inline input-400\" />\n        ";
            echo $aInt->getTranslationLink("custom_field.name", $fid, "product");
            echo "        <div class=\"pull-right\">\n            ";
            echo $aInt->lang("customfields", "order");
            echo "            <input type=\"text\" name=\"customsortorder[";
            echo $fid;
            echo "]\" value=\"";
            echo $sortorder;
            echo "\" class=\"form-control input-inline input-100 text-center\">\n        </div>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("customfields", "fieldtype");
            echo "</td><td class=\"fieldarea\"><select name=\"customfieldtype[";
            echo $fid;
            echo "]\" class=\"form-control select-inline\">\n<option value=\"text\"";
            if ($fieldtype == "text") {
                echo " selected";
            }
            echo ">";
            echo $aInt->lang("customfields", "typetextbox");
            echo "</option>\n<option value=\"link\"";
            if ($fieldtype == "link") {
                echo " selected";
            }
            echo ">";
            echo $aInt->lang("customfields", "typelink");
            echo "</option>\n<option value=\"password\"";
            if ($fieldtype == "password") {
                echo " selected";
            }
            echo ">";
            echo $aInt->lang("customfields", "typepassword");
            echo "</option>\n<option value=\"dropdown\"";
            if ($fieldtype == "dropdown") {
                echo " selected";
            }
            echo ">";
            echo $aInt->lang("customfields", "typedropdown");
            echo "</option>\n<option value=\"tickbox\"";
            if ($fieldtype == "tickbox") {
                echo " selected";
            }
            echo ">";
            echo $aInt->lang("customfields", "typetickbox");
            echo "</option>\n<option value=\"textarea\"";
            if ($fieldtype == "textarea") {
                echo " selected";
            }
            echo ">";
            echo $aInt->lang("customfields", "typetextarea");
            echo "</option>\n</select></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
            echo $aInt->lang("fields", "description");
            echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"customfielddesc[";
            echo $fid;
            echo "]\" value=\"";
            echo $description;
            echo "\" class=\"form-control input-inline input-500\" />\n        ";
            echo $aInt->getTranslationLink("custom_field.description", $fid, "product");
            echo "        ";
            echo $aInt->lang("customfields", "descriptioninfo");
            echo "    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("customfields", "validation");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"customfieldregexpr[";
            echo $fid;
            echo "]\" value=\"";
            echo WHMCS\Input\Sanitize::encode($regexpr);
            echo "\" class=\"form-control input-inline input-500\"> ";
            echo $aInt->lang("customfields", "validationinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("customfields", "selectoptions");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"customfieldoptions[";
            echo $fid;
            echo "]\" value=\"";
            echo $fieldoptions;
            echo "\" class=\"form-control input-inline input-500\"> ";
            echo $aInt->lang("customfields", "selectoptionsinfo");
            echo "</td></tr>\n    <tr>\n        <td class=\"fieldlabel\"></td>\n        <td class=\"fieldarea\">\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"customadminonly[";
            echo $fid;
            echo "]\"";
            if ($adminonly == "on") {
                echo " checked";
            }
            echo ">\n                ";
            echo $aInt->lang("customfields", "adminonly");
            echo "            </label>\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"customrequired[";
            echo $fid;
            echo "]\"";
            if ($required == "on") {
                echo " checked";
            }
            echo ">\n                ";
            echo $aInt->lang("customfields", "requiredfield");
            echo "            </label>\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"customshoworder[";
            echo $fid;
            echo "]\"";
            if ($showorder == "on") {
                echo " checked";
            }
            echo ">\n                ";
            echo $aInt->lang("customfields", "orderform");
            echo "            </label>\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"customshowinvoice[";
            echo $fid;
            echo "]\"";
            if ($showinvoice) {
                echo " checked";
            }
            echo ">\n                ";
            echo $aInt->lang("customfields", "showinvoice");
            echo "            </label>\n            <div class=\"pull-right\">\n                <a href=\"#\" onclick=\"deletecustomfield('";
            echo $fid;
            echo "');return false\" class=\"btn btn-danger btn-xs\">";
            echo $aInt->lang("customfields", "deletefield");
            echo "</a>\n            </div>\n        </td>\n    </tr>\n</table><br>\n";
        }
        echo "<b>";
        echo $aInt->lang("customfields", "addfield");
        echo "</b><br><br>\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr>\n    <td class=\"fieldlabel\">\n        ";
        echo $aInt->lang("customfields", "fieldname");
        echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"addfieldname\" class=\"form-control input-inline input-400\" />\n        ";
        echo $aInt->getTranslationLink("custom_field.name", 0, "product");
        echo "        <div class=\"pull-right\">\n            ";
        echo $aInt->lang("customfields", "order");
        echo "            <input type=\"text\" name=\"addsortorder\" value=\"0\" class=\"form-control input-inline input-100 text-center\" />\n        </div>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("customfields", "fieldtype");
        echo "</td><td class=\"fieldarea\"><select name=\"addfieldtype\" class=\"form-control select-inline\">\n<option value=\"text\">";
        echo $aInt->lang("customfields", "typetextbox");
        echo "</option>\n<option value=\"link\">";
        echo $aInt->lang("customfields", "typelink");
        echo "</option>\n<option value=\"password\">";
        echo $aInt->lang("customfields", "typepassword");
        echo "</option>\n<option value=\"dropdown\">";
        echo $aInt->lang("customfields", "typedropdown");
        echo "</option>\n<option value=\"tickbox\">";
        echo $aInt->lang("customfields", "typetickbox");
        echo "</option>\n<option value=\"textarea\">";
        echo $aInt->lang("customfields", "typetextarea");
        echo "</option>\n</select></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
        echo $aInt->lang("fields", "description");
        echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"addcustomfielddesc\" class=\"form-control input-inline input-500\" />\n        ";
        echo $aInt->getTranslationLink("custom_field.description", 0, "product");
        echo "        ";
        echo $aInt->lang("customfields", "descriptioninfo");
        echo "    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("customfields", "validation");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"addregexpr\" class=\"form-control input-inline input-500\"> ";
        echo $aInt->lang("customfields", "validationinfo");
        echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("customfields", "selectoptions");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"addfieldoptions\" class=\"form-control input-inline input-500\"> ";
        echo $aInt->lang("customfields", "selectoptionsinfo");
        echo "</td></tr>\n    <tr>\n        <td class=\"fieldlabel\"></td>\n        <td class=\"fieldarea\">\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"addadminonly\">\n                ";
        echo $aInt->lang("customfields", "adminonly");
        echo "            </label>\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"addrequired\">\n                ";
        echo $aInt->lang("customfields", "requiredfield");
        echo "            </label>\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"addshoworder\">\n                ";
        echo $aInt->lang("customfields", "orderform");
        echo "            </label>\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"addshowinvoice\">\n                ";
        echo $aInt->lang("customfields", "showinvoice");
        echo "            </label>\n        </td>\n    </tr>\n</table>\n\n";
        echo $aInt->nextAdminTab();
        echo "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"150\" class=\"fieldlabel\">";
        echo $aInt->lang("products", "assignedoptiongroups");
        echo "</td><td class=\"fieldarea\"><select name=\"configoptionlinks[]\" size=\"8\" class=\"form-control select-inline\" style=\"width:90%\" multiple>\n";
        $configoptionlinks = array();
        $result = select_query("tblproductconfiglinks", "", array("pid" => $id));
        while ($data = mysql_fetch_array($result)) {
            $configoptionlinks[] = $data["gid"];
        }
        $result = select_query("tblproductconfiggroups", "", "", "name", "ASC");
        while ($data = mysql_fetch_array($result)) {
            $confgroupid = $data["id"];
            $name = $data["name"];
            $description = $data["description"];
            echo "<option value=\"" . $confgroupid . "\"";
            if (in_array($confgroupid, $configoptionlinks)) {
                echo " selected";
            }
            echo ">" . $name . " - " . $description . "</option>";
        }
        echo "</select></td></tr>\n</table>\n\n";
        echo $aInt->nextAdminTab();
        echo "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("products", "packagesupgrades");
        echo "</td><td class=\"fieldarea\"><select name=\"upgradepackages[]\" size=\"10\" class=\"form-control select-inline\" multiple>";
        $query = "SELECT tblproducts.id,tblproductgroups.name AS groupname,tblproducts.name AS productname FROM tblproducts INNER JOIN tblproductgroups ON tblproductgroups.id=tblproducts.gid ORDER BY tblproductgroups.`order`,tblproducts.`order`,tblproducts.name ASC";
        $result = full_query($query);
        while ($data = mysql_fetch_array($result)) {
            $productid = $data["id"];
            $groupname = $data["groupname"];
            $productname = $data["productname"];
            if ($id != $productid) {
                echo "<option value=\"" . $productid . "\"";
                if (@in_array($productid, $upgradepackages)) {
                    echo " selected";
                }
                echo ">" . $groupname . " - " . $productname . "</option>";
            }
        }
        echo "</select><br>";
        echo $aInt->lang("products", "usectrlclickpkgs");
        echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("setup", "configoptions");
        echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"configoptionsupgrade\"";
        if ($configoptionsupgrade) {
            echo " checked";
        }
        echo "> ";
        echo $aInt->lang("products", "tickboxallowconfigoptupdowngrades");
        echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("products", "upgradeemail");
        echo "</td><td class=\"fieldarea\"><select name=\"upgradeemail\" class=\"form-control select-inline\"><option value=\"0\">";
        echo $aInt->lang("global", "none");
        echo "</option>";
        $emails = array($aInt->lang("products", "emailshostingac"), $aInt->lang("products", "emailsresellerac"), $aInt->lang("products", "emailsvpsdediserver"), $aInt->lang("products", "emailsother"));
        foreach ($emails as $email) {
            $mailTemplates = WHMCS\Mail\Template::where("type", "=", "product")->where("name", "=", $email)->where("language", "=", "")->get();
            foreach ($mailTemplates as $template) {
                echo "<option value=\"" . $template->id . "\"";
                if ($template->id == $upgradeemail) {
                    echo " selected";
                }
                echo ">" . $template->name . "</option>";
            }
        }
        foreach ($customProductMailTemplates as $template) {
            echo "<option value=\"" . $template->id . "\"";
            if ($template->id == $upgradeemail) {
                echo " selected";
            }
            echo ">" . $template->name . "</option>";
        }
        echo "</select></td></tr>\n</table>\n\n";
        echo $aInt->nextAdminTab();
        echo "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n    <tr>\n        <td class=\"fieldlabel\">";
        echo $aInt->lang("products", "tabsfreedomain");
        echo "</td>\n        <td class=\"fieldarea\">\n            <label class=\"radio-inline\">\n                <input type=\"radio\" name=\"freedomain\" value=\"\"";
        if (!$freedomain) {
            echo " checked";
        }
        echo ">\n                ";
        echo $aInt->lang("global", "none");
        echo "            </label><br />\n            <label class=\"radio-inline\">\n                <input type=\"radio\" name=\"freedomain\" value=\"once\"";
        if ($freedomain == "once") {
            echo " checked";
        }
        echo ">\n                ";
        echo $aInt->lang("products", "freedomainrenewnormal");
        echo "            </label><br />\n            <label class=\"radio-inline\">\n                <input type=\"radio\" name=\"freedomain\" value=\"on\"";
        if ($freedomain == "on") {
            echo " checked";
        }
        echo ">\n                ";
        echo $aInt->lang("products", "freedomainfreerenew");
        echo "            </label>\n        </td>\n    </tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("products", "freedomainpayterms");
        echo "</td><td class=\"fieldarea\"><select name=\"freedomainpaymentterms[]\" size=\"6\" class=\"form-control select-inline\" multiple>\n<option value=\"onetime\"";
        if (in_array("onetime", $freedomainpaymentterms)) {
            echo " selected";
        }
        echo ">";
        echo $aInt->lang("billingcycles", "onetime");
        echo "</option>\n<option value=\"monthly\"";
        if (in_array("monthly", $freedomainpaymentterms)) {
            echo " selected";
        }
        echo ">";
        echo $aInt->lang("billingcycles", "monthly");
        echo "</option>\n<option value=\"quarterly\"";
        if (in_array("quarterly", $freedomainpaymentterms)) {
            echo " selected";
        }
        echo ">";
        echo $aInt->lang("billingcycles", "quarterly");
        echo "</option>\n<option value=\"semiannually\"";
        if (in_array("semiannually", $freedomainpaymentterms)) {
            echo " selected";
        }
        echo ">";
        echo $aInt->lang("billingcycles", "semiannually");
        echo "</option>\n<option value=\"annually\"";
        if (in_array("annually", $freedomainpaymentterms)) {
            echo " selected";
        }
        echo ">";
        echo $aInt->lang("billingcycles", "annually");
        echo "</option>\n<option value=\"biennially\"";
        if (in_array("biennially", $freedomainpaymentterms)) {
            echo " selected";
        }
        echo ">";
        echo $aInt->lang("billingcycles", "biennially");
        echo "</option>\n<option value=\"triennially\"";
        if (in_array("triennially", $freedomainpaymentterms)) {
            echo " selected";
        }
        echo ">";
        echo $aInt->lang("billingcycles", "triennially");
        echo "</option>\n</select><br>";
        echo $aInt->lang("products", "selectfreedomainpayterms");
        echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("products", "freedomaintlds");
        echo "</td>\n    <td class=\"fieldarea\"><select name=\"freedomaintlds[]\" size=\"5\" class=\"form-control select-inline\" multiple>";
        $query = "SELECT DISTINCT extension FROM tbldomainpricing ORDER BY `order` ASC";
        $result = full_query($query);
        while ($data = mysql_fetch_array($result)) {
            $extension = $data["extension"];
            echo "<option";
            if (in_array($extension, $freedomaintlds)) {
                echo " selected";
            }
            echo ">" . $extension;
        }
        echo "</select><br>";
        echo $aInt->lang("products", "usectrlclickpayterms");
        echo "</td></tr>\n</table>\n\n";
        echo $aInt->nextAdminTab();
        echo "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n";
        $producteditfieldsarray = run_hook("AdminProductConfigFields", array("pid" => $id));
        if (is_array($producteditfieldsarray)) {
            foreach ($producteditfieldsarray as $pv) {
                foreach ($pv as $k => $v) {
                    echo "<tr><td class=\"fieldlabel\">" . $k . "</td><td class=\"fieldarea\">" . $v . "</td></tr>";
                }
            }
        }
        echo "    <tr>\n        <td class=\"fieldlabel\">";
        echo $aInt->lang("products", "customaffiliatepayout");
        echo "</td>\n        <td class=\"fieldarea\">\n            <label class=\"radio-inline\">\n                <input type=\"radio\" name=\"affiliatepaytype\" value=\"\"";
        if ($affiliatepaytype == "") {
            echo " checked";
        }
        echo ">\n                ";
        echo $aInt->lang("affiliates", "usedefault");
        echo "            </label>\n            <label class=\"radio-inline\">\n                <input type=\"radio\" name=\"affiliatepaytype\" value=\"percentage\"";
        if ($affiliatepaytype == "percentage") {
            echo " checked";
        }
        echo ">\n                ";
        echo $aInt->lang("affiliates", "percentage");
        echo "            </label>\n            <label class=\"radio-inline\">\n                <input type=\"radio\" name=\"affiliatepaytype\" value=\"fixed\"";
        if ($affiliatepaytype == "fixed") {
            echo " checked";
        }
        echo ">\n                ";
        echo $aInt->lang("affiliates", "fixedamount");
        echo "            </label>\n            <label class=\"radio-inline\">\n                <input type=\"radio\" name=\"affiliatepaytype\" value=\"none\"";
        if ($affiliatepaytype == "none") {
            echo " checked";
        }
        echo ">\n                ";
        echo $aInt->lang("affiliates", "nocommission");
        echo "            </label>\n        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">";
        echo $aInt->lang("affiliates", "affiliatepayamount");
        echo "</td>\n        <td class=\"fieldarea\">\n            <input type=\"text\" name=\"affiliatepayamount\" value=\"";
        echo $affiliatepayamount;
        echo "\" class=\"form-control input-inline input-100 text-center\">\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"affiliateonetime\"";
        if ($affiliateonetime) {
            echo " checked";
        }
        echo ">\n                ";
        echo $aInt->lang("affiliates", "onetimepayout");
        echo "            </label>\n        </td>\n    </tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("products", "subdomainoptions");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"subdomain\" value=\"";
        echo $subdomain;
        echo "\" class=\"form-control input-inline input-300\"> ";
        echo $aInt->lang("products", "subdomainoptionsdesc");
        echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("products", "associateddownloads");
        echo "</td><td class=\"fieldarea\">";
        echo $aInt->lang("products", "associateddownloadsdesc");
        echo "<br />\n<table align=\"center\"><tr><td valign=\"top\">\n<div align=\"center\"><strong>";
        echo $aInt->lang("products", "availablefiles");
        echo "</strong></div>\n<div id=\"productdownloadsbrowser\" style=\"width: 250px;height: 200px;border-top: solid 1px #BBB;border-left: solid 1px #BBB;border-bottom: solid 1px #FFF;border-right: solid 1px #FFF;background: #FFF;overflow: scroll;padding: 5px;\"></div>\n</td><td><></td><td valign=\"top\">\n<div align=\"center\"><strong>";
        echo $aInt->lang("products", "selectedfiles");
        echo "</strong></div>\n<div id=\"productdownloadslist\" style=\"width: 250px;height: 200px;border-top: solid 1px #BBB;border-left: solid 1px #BBB;border-bottom: solid 1px #FFF;border-right: solid 1px #FFF;background: #FFF;overflow: scroll;padding: 5px;\">";
        printproductdownloads($downloadIds);
        echo "</div>\n</td></tr></table>\n<div align=\"center\"><input type=\"button\" value=\"";
        echo $aInt->lang("products", "addcategory");
        echo "\" class=\"button btn btn-default\" id=\"showadddownloadcat\" /> <input type=\"button\" value=\"";
        echo $aInt->lang("products", "quickupload");
        echo "\" class=\"button btn btn-default\" id=\"showquickupload\" /></div>\n</td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("products", "overagesbilling");
        echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"overagesenabled\" value=\"1\"";
        if ($overagesenabled[0]) {
            echo " checked";
        }
        echo "> ";
        echo $aInt->lang("global", "ticktoenable");
        echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("products", "overagesoftlimits");
        echo "</td><td class=\"fieldarea\">";
        echo $aInt->lang("products", "overagediskusage");
        echo " <input type=\"text\" name=\"overagesdisklimit\" value=\"";
        echo $overagesdisklimit;
        echo "\" class=\"form-control input-inline input-100 text-center\"> <select name=\"overageunitsdisk\" class=\"form-control select-inline\"><option>MB</option><option";
        if ($overagesenabled[1] == "GB") {
            echo " selected";
        }
        echo ">GB</option><option";
        if ($overagesenabled[1] == "TB") {
            echo " selected";
        }
        echo ">TB</option></select> ";
        echo $aInt->lang("products", "overagebandwidth");
        echo " <input type=\"text\" name=\"overagesbwlimit\" value=\"";
        echo $overagesbwlimit;
        echo "\" class=\"form-control input-inline input-100 text-center\"> <select name=\"overageunitsbw\" class=\"form-control select-inline\"><option>MB</option><option";
        if ($overagesenabled[2] == "GB") {
            echo " selected";
        }
        echo ">GB</option><option";
        if ($overagesenabled[2] == "TB") {
            echo " selected";
        }
        echo ">TB</option></select></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("products", "overagecosts");
        echo "</td><td class=\"fieldarea\">";
        echo $aInt->lang("products", "overagediskusage");
        echo " <input type=\"text\" name=\"overagesdiskprice\" value=\"";
        echo $overagesdiskprice;
        echo "\" class=\"form-control input-inline input-100 text-center\"> ";
        echo $aInt->lang("products", "overagebandwidth");
        echo " <input type=\"text\" name=\"overagesbwprice\" value=\"";
        echo $overagesbwprice;
        echo "\" class=\"form-control input-inline input-100 text-center\"> (";
        echo $aInt->lang("products", "priceperunit");
        echo ")</td></tr>\n</table>\n\n";
        echo $aInt->nextAdminTab();
        echo "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\" width=\"370\">";
        echo $aInt->lang("products", "directscartlink");
        echo "</td><td class=\"fieldarea\"><input id=\"Direct-Link\" type=\"text\" class=\"form-control\" value=\"";
        echo App::getSystemUrl();
        echo "cart.php?a=add&pid=";
        echo $id;
        echo "\" readonly></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
        echo $aInt->lang("products", "directscarttpllink");
        echo "    </td>\n    <td class=\"fieldarea\">\n        <input id=\"Direct-Link-With-Template\" type=\"text\" class=\"form-control\" value=\"";
        echo App::getSystemUrl();
        echo "cart.php?a=add&pid=";
        echo $id;
        echo "&carttpl=";
        echo WHMCS\View\Template\OrderForm::getDefault()->getName();
        echo "\" readonly>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("products", "directscartdomlink");
        echo "</td><td class=\"fieldarea\"><input id=\"Direct-Link-Including-Domain\" type=\"text\" class=\"form-control\" value=\"";
        echo App::getSystemUrl();
        echo "cart.php?a=add&pid=";
        echo $id;
        echo "&sld=whmcs&tld=.com\" readonly></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("products", "productgcartlink");
        echo "</td><td class=\"fieldarea\"><input id=\"Product-Group-Cart-Link\" type=\"text\" class=\"form-control\" value=\"";
        echo App::getSystemUrl();
        echo "cart.php?gid=";
        echo $gid;
        echo "\" readonly></td></tr>\n</table>\n\n";
        echo $aInt->endAdminTabs();
        echo "\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"Save Changes\" class=\"btn btn-primary\">\n    <input type=\"button\" value=\"";
        echo $aInt->lang("global", "cancelchanges");
        echo "\" onclick=\"window.location='configproducts.php'\" class=\"btn btn-default\">\n</div>\n\n<input type=\"hidden\" name=\"tab\" id=\"tab\" value=\"";
        echo (int) $_REQUEST["tab"];
        echo "\" />\n\n</form>\n\n";
        echo $aInt->modal("QuickUpload", "Quick File Upload", AdminLang::trans("global.loading"), array(array("title" => AdminLang::trans("global.save"), "onclick" => "jQuery(\"#quickuploadfrm\").submit();"), array("title" => AdminLang::trans("global.cancel"))));
        echo $aInt->modal("AddDownloadCategory", AdminLang::trans("support.addcategory"), AdminLang::trans("global.loading"), array(array("title" => AdminLang::trans("global.save"), "onclick" => "jQuery(\"#adddownloadcatfrm\").submit();"), array("title" => AdminLang::trans("global.cancel"))), "small");
    } else {
        if ($action == "create") {
            checkPermission("Create New Products/Services");
            $inputModule = App::getFromRequest("module");
            $productGroups = WHMCS\Product\Group::orderBy("order")->pluck("name", "id");
            if (count($productGroups) == 0) {
                App::redirect("configproducts.php", "action=creategroup&prodcreatenogroups=1");
            }
            $jquerycode = "\$('.product-creation-types .type').click(function(e) {\n    \$('.product-creation-types .type').removeClass('active');\n    \$(this).addClass('active');\n    \$('#inputProductType').val(\$(this).data('type'));\n});\n\$('.product-creation-modules .module').click(function(e) {\n    \$('.product-creation-modules .module').removeClass('active');\n    \$(this).addClass('active');\n    \$('#inputProductModule').val(\$(this).data('module'));\n});";
            echo "\n<div class=\"admin-tabs-v2 contrained-width\">\n    <form id=\"frmAddProduct\" method=\"post\" action=\"";
            echo $whmcs->getPhpSelf();
            echo "?action=add\" class=\"form-horizontal\">\n        <div class=\"col-lg-9 col-lg-offset-3 col-md-8 col-sm-offset-4\">\n            <h2>";
            echo $aInt->lang("products", "createnewproduct");
            echo "</h2>\n        </div>\n        <div class=\"form-group\">\n            <label for=\"inputGroup\" class=\"col-lg-3 col-sm-4 control-label\">\n                ";
            echo AdminLang::trans("fields.producttype");
            echo "<br>\n                <small>";
            echo AdminLang::trans("products.productTypeDescription");
            echo "</small>\n            </label>\n            <div class=\"col-lg-9 col-sm-8\">\n                <input type=\"hidden\" name=\"type\" value=\"hostingaccount\" id=\"inputProductType\">\n                <div class=\"multi-select-blocks product-creation-types clearfix\">\n                    <div class=\"block\">\n                        <div class=\"type active\" data-type=\"hostingaccount\" id=\"productTypeShared\">\n                            <i class=\"fa fa-server\"></i>\n                            <span>";
            echo AdminLang::trans("products.hostingaccount");
            echo "</span>\n                        </div>\n                    </div>\n                    <div class=\"block\">\n                        <div class=\"type\" data-type=\"reselleraccount\" id=\"productTypeReseller\">\n                            <i class=\"fa fa-cloud\"></i>\n                            <span>";
            echo AdminLang::trans("products.reselleraccount");
            echo "</span>\n                        </div>\n                    </div>\n                    <div class=\"block\">\n                        <div class=\"type\" data-type=\"server\" id=\"productTypeServer\">\n                            <i class=\"fa fa-hdd\"></i>\n                            <span>";
            echo AdminLang::trans("products.dedicatedvpsserver");
            echo "</span>\n                        </div>\n                    </div>\n                    <div class=\"block\">\n                        <div class=\"type\" data-type=\"other\" id=\"productTypeOther\">\n                            <i class=\"fa fa-cube\"></i>\n                            <span>";
            echo AdminLang::trans("products.otherproductservice");
            echo "</span>\n                        </div>\n                    </div>\n                </div>\n            </div>\n        </div>\n        <div class=\"form-group\">\n            <label for=\"inputGroup\" class=\"col-lg-3 col-sm-4 control-label\">\n                ";
            echo AdminLang::trans("products.productgroup");
            echo "<br>\n                <small><a href=\"configproducts.php?action=creategroup\">";
            echo AdminLang::trans("products.createNewProductGroup");
            echo "</a></small>\n            </label>\n            <div class=\"col-lg-4 col-sm-4\">\n                <select name=\"gid\" id=\"inputGroup\" class=\"form-control\">\n                    ";
            foreach ($productGroups as $groupId => $groupName) {
                echo "<option value=\"" . $groupId . "\">" . $groupName . "</option>";
            }
            echo "                </select>\n            </div>\n        </div>\n        <div class=\"form-group\">\n            <label for=\"inputProductName\" class=\"col-lg-3 col-sm-4 control-label\">\n                ";
            echo AdminLang::trans("products.productname");
            echo "<br>\n                <small>";
            echo AdminLang::trans("products.productnameDescription");
            echo "</small>\n            </label>\n            <div class=\"col-lg-5 col-sm-6\">\n                <input type=\"text\" class=\"form-control\" name=\"productname\" id=\"inputProductName\" required>\n            </div>\n        </div>\n        <div class=\"form-group\">\n            <label for=\"inputProductModule\" class=\"col-lg-3 col-sm-4 control-label\">\n                ";
            echo AdminLang::trans("fields.module");
            echo "<br>\n                <small>";
            echo AdminLang::trans("products.moduleDescription");
            echo "</small>\n            </label>\n            <div class=\"col-lg-3 col-sm-5\">\n                <select name=\"module\" class=\"form-control\" id=\"inputProductModule\">\n                    <option value=\"\">No Module</option>\n                    ";
            $moduleInterface = new WHMCS\Module\Server();
            $moduleList = collect($moduleInterface->getListWithDisplayNames());
            $promotedModules = collect(array("cpanel", "plesk", "directadmin", "licensing", "autorelease"));
            echo "<optgroup label=\"Popular Modules\">";
            foreach ($promotedModules as $module) {
                if ($moduleList->has($module)) {
                    echo "<option value=\"" . $module . "\"" . ($module == $inputModule ? " selected" : "") . ">" . $moduleList[$module] . "</option>";
                }
            }
            echo "</optgroup><optgroup label=\"All Other Modules\">";
            foreach ($moduleList as $module => $displayName) {
                if (!$promotedModules->contains($module)) {
                    echo "<option value=\"" . $module . "\"" . ($module == $inputModule ? " selected" : "") . ">" . $displayName . "</option>";
                }
            }
            echo "</optgroup>                </select>\n            </div>\n        </div>\n        <div class=\"form-group\">\n            <label for=\"inputHidden\" class=\"col-lg-3 col-sm-4 control-label\">\n                ";
            echo AdminLang::trans("products.createAsHidden");
            echo "<br>\n                <small>";
            echo AdminLang::trans("products.createAsHiddenDescription");
            echo "</small>\n            </label>\n            <div class=\"col-lg-5 col-sm-6\">\n                <input type=\"checkbox\" class=\"slide-toggle\" name=\"createhidden\" id=\"inputHidden\" checked>\n            </div>\n        </div>\n\n        <div class=\"btn-container\">\n            <input type=\"submit\" value=\"";
            echo $aInt->lang("global", "continue");
            echo " &raquo;\" class=\"btn btn-primary\" id=\"btnContinue\" />\n        </div>\n\n        <br>\n        <div class=\"alert alert-grey\">\n            <i class=\"fa fa-info-circle fa-fw\"></i>\n            Looking to add a MarketConnect product such as SSL, Website Builder or Backups? Visit the <a href=\"marketconnect.php\">MarketConnect Portal</a>\n        </div>\n    </form>\n</div>\n\n";
        } else {
            if ($action == "duplicate") {
                checkPermission("Create New Products/Services");
                echo "\n<h2>";
                echo $aInt->lang("products", "duplicateproduct");
                echo "</h2>\n\n<form method=\"post\" action=\"";
                echo $whmcs->getPhpSelf();
                echo "?action=duplicatenow\">\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=150 class=\"fieldlabel\">";
                echo $aInt->lang("products", "existingproduct");
                echo "</td><td class=\"fieldarea\"><select name=\"existingproduct\" class=\"form-control select-inline\">";
                $products = new WHMCS\Product\Products();
                $productsList = $products->getProducts();
                foreach ($productsList as $data) {
                    $pid = $data["id"];
                    $groupname = $data["groupname"];
                    $prodname = $data["name"];
                    echo "<option value=\"" . $pid . "\">" . $groupname . " - " . $prodname . "</option>";
                }
                echo "</select></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
                echo AdminLang::trans("products.newproductname");
                echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" class=\"form-control input-500\" name=\"newproductname\" />\n    </td>\n</tr>\n</table>\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
                echo $aInt->lang("global", "continue");
                echo " &raquo;\" class=\"btn btn-primary\">\n</div>\n</form>\n\n";
            } else {
                if ($action == "creategroup" || $action == "editgroup") {
                    checkPermission("Manage Product Groups");
                    $result = select_query("tblproductgroups", "", array("id" => $ids));
                    $data = mysql_fetch_array($result);
                    $ids = (int) $data["id"];
                    $name = $data["name"];
                    $headline = $data["headline"];
                    $tagline = $data["tagline"];
                    $orderfrmtpl = $data["orderfrmtpl"];
                    $disabledgateways = $data["disabledgateways"];
                    $hidden = $data["hidden"];
                    $systemOrderFormTemplate = WHMCS\Config\Setting::getValue("OrderFormTemplate");
                    $disabledgateways = explode(",", $disabledgateways);
                    if (!$ids && WHMCS\Config\Setting::getValue("EnableTranslations")) {
                        WHMCS\Language\DynamicTranslation::whereIn("related_type", array("product_group.{id}.headline", "product_group.{id}.name", "product_group.{id}.tagline"))->where("related_id", "=", 0)->delete();
                    }
                    echo "\n<h2>";
                    echo $aInt->lang("products", $action == "creategroup" ? "creategroup" : "editgroup");
                    echo "</h2>\n\n";
                    if (App::getFromRequest("prodcreatenogroups")) {
                        echo infoBox(AdminLang::trans("products.productGroupRequired"), AdminLang::trans("products.productGroupRequiredDescription"));
                    }
                    echo "\n<form id=\"frmAddProductGroup\" method=\"post\" action=\"";
                    echo $whmcs->getPhpSelf();
                    echo "?sub=savegroup&ids=";
                    echo $ids;
                    echo "\">\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr>\n    <td width=\"25%\" class=\"fieldlabel\">";
                    echo $aInt->lang("products", "productgroupname");
                    echo "</td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"name\" value=\"";
                    echo $name;
                    echo "\" class=\"form-control input-400 input-inline\" placeholder=\"eg. Shared Hosting\" />\n        ";
                    echo $aInt->getTranslationLink("product_group.name", $ids);
                    echo "    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
                    echo AdminLang::trans("products.groupHeadline");
                    echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" id=\"headline\" name=\"headline\" value=\"";
                    echo $headline;
                    echo "\" class=\"form-control input-700 input-inline\" placeholder=\"";
                    echo AdminLang::trans("products.groupHeadlinePlaceHolder");
                    echo "\" />\n        ";
                    echo $aInt->getTranslationLink("product_group.headline", $ids);
                    echo "    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
                    echo AdminLang::trans("products.groupTagline");
                    echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" id=\"tagline\" name=\"tagline\" value=\"";
                    echo $tagline;
                    echo "\" class=\"form-control input-700 input-inline\" placeholder=\"";
                    echo AdminLang::trans("products.groupTaglinePlaceHolder");
                    echo "\" />\n        ";
                    echo $aInt->getTranslationLink("product_group.tagline", $ids);
                    echo "    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
                    echo AdminLang::trans("products.groupFeatures");
                    echo "    </td>\n    <td class=\"fieldarea\">\n        ";
                    if ($action == "editgroup") {
                        $changesSavedSuccessfully = AdminLang::trans("global.changesuccess");
                        $description = AdminLang::trans("products.groupFeaturesDescription");
                        echo "<div class=\"feature-list-desc\">\n    " . $description . "\n</div>\n<div id=\"featureList\" class=\"clearfix list-group feature-list\">";
                        $featureList = WHMCS\Product\Group\Feature::orderBy("order")->where("product_group_id", "=", $ids)->get();
                        foreach ($featureList as $feature) {
                            echo "<div class=\"list-group-item\" data-id=\"" . $feature->id . "\">\n    <span class=\"badge remove-feature\" data-id=\"" . $feature->id . "\">\n        <i class=\"glyphicon glyphicon-remove\"></i>\n    </span>\n    <span class=\"glyphicon glyphicon-move\" aria-hidden=\"true\"></span>\n    <span class=\"product-group-feature\">" . $feature->feature . "</span>\n</div>";
                        }
                        $addNewFeature = AdminLang::trans("products.addNewFeature");
                        $addNew = AdminLang::trans("global.addnew");
                        echo "</div>\n<div id=\"new-features\" class=\"input-group\">\n    <input type=\"text\" name=\"new-feature\" id=\"new-feature\" placeholder=\"" . $addNewFeature . "\" class=\"form-control\" />\n    <span class=\"input-group-btn\">\n        <button type=\"button\" id=\"new-feature-add\" class=\"btn btn-warning width-120\">\n        <i class=\"fas fa-spinner fa-spin hidden\" id=\"new-feature-add-spinner\"></i>\n            " . $addNew . "\n        </button>\n    </span>\n</div>";
                    } else {
                        echo "<div style=\"padding:7px 10px;color:#888;font-style:italic;\">" . AdminLang::trans("products.groupSave") . "</div>";
                    }
                    echo "    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
                    echo $aInt->lang("products", "orderfrmtpl");
                    echo "</td>\n    <td class=\"fieldarea\">\n        ";
                    if ($action != "creategroup") {
                        echo "            <label class=\"radio-inline\">\n                <input type=\"radio\" name=\"orderfrmtpl\" value=\"default\"";
                        if (!$orderfrmtpl) {
                            echo " checked";
                        }
                        echo " />\n                ";
                        echo $aInt->lang("products", "groupTemplateUseSystemDefault");
                        echo " (";
                        echo WHMCS\View\Template\OrderForm::find($systemOrderFormTemplate)->getDisplayName();
                        echo ")\n            </label>\n            <label class=\"radio-inline\">\n                <input type=\"radio\" name=\"orderfrmtpl\" value=\"custom\"";
                        if ($orderfrmtpl) {
                            echo " checked";
                        }
                        echo " />\n                ";
                        echo $aInt->lang("products", "groupTemplateUseSpecificTemplate");
                        echo "            </label>\n        ";
                    }
                    echo "        <div id=\"orderFormTemplateOptions\" style=\"padding:15px;clear:both;\"";
                    echo $action == "editgroup" && !$orderfrmtpl ? " class=\"hidden\"" : "";
                    echo ">\n\n";
                    try {
                        $orderFormTemplates = WHMCS\View\Template\OrderForm::all();
                        $priorityOrderFormTemplates = array("standard_cart" => 100, "premium_comparison" => 101, "pure_comparison" => 99, "supreme_comparison" => 97, "universal_slider" => 96, "cloud_slider" => 95);
                        $count = 0;
                        $orderFormTemplates = $orderFormTemplates->sortBy(function (WHMCS\View\Template\OrderForm $template) use(&$count, $priorityOrderFormTemplates) {
                            $count--;
                            if (array_key_exists($template->getName(), $priorityOrderFormTemplates)) {
                                return $count - $priorityOrderFormTemplates[$template->getName()];
                            }
                            return 0;
                        });
                    } catch (Exception $e) {
                        $aInt->gracefulExit("Order Form Templates directory is missing. Please reupload /templates/orderforms/");
                    }
                    foreach ($orderFormTemplates as $template) {
                        $checked = $template->getName() == $orderfrmtpl ? " checked" : "";
                        $friendlyName = $template->getDisplayName();
                        if ($action == "creategroup" || !$orderfrmtpl) {
                            $checked = $template->getName() == $systemOrderFormTemplate ? " checked" : "";
                        }
                        if ($template->getName() == $systemOrderFormTemplate) {
                            $friendlyName .= " (<strong>" . AdminLang::trans("global.default") . "</strong>)";
                        }
                        echo "    <div style=\"float:left;padding:10px;text-align:center;\">\n        <label class=\"radio-inline\">\n            <img src=\"" . $template->getThumbnailWebPath() . "\" width=\"165\" height=\"90\" style=\"border:5px solid #fff;\" /><br />\n            <input id=\"orderformtemplate-" . $template->getName() . "\" type=\"radio\" name=\"orderfrmtplname\" value=\"" . $template->getName() . "\"" . $checked . "> " . $friendlyName . "\n        </label>\n    </div>";
                    }
                    echo "\n        </div>\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
                    echo $aInt->lang("products", "availablepgways");
                    echo "</td>\n    <td class=\"fieldarea\" style=\"padding:7px 10px;\">\n        ";
                    $gateways = getGatewaysArray();
                    foreach ($gateways as $gateway => $displayName) {
                        echo "<label class=\"checkbox-inline\">\n<input type=\"checkbox\" name=\"gateways[" . $gateway . "] pgateway_checkbox\"" . (!in_array($gateway, $disabledgateways) ? " checked" : "") . " />\n" . $displayName . "\n</label>";
                    }
                    echo "    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">";
                    echo $aInt->lang("fields", "hidden");
                    echo "</td>\n    <td class=\"fieldarea\">\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"hidden\"";
                    if ($hidden) {
                        echo " checked";
                    }
                    echo ">\n            ";
                    echo $aInt->lang("products", "hiddengroupdesc");
                    echo "        </label>\n    </td>\n</tr>\n";
                    if ($ids) {
                        echo "    <tr>\n        <td class=\"fieldlabel\">";
                        echo $aInt->lang("products", "directcartlink");
                        echo "</td>\n        <td class=\"fieldarea\"><input type=\"text\" value=\"";
                        echo $CONFIG["SystemURL"];
                        echo "/cart.php?gid=";
                        echo ltrim($ids, 0);
                        echo "\" class=\"form-control\" readonly></td>\n    </tr>\n";
                    }
                    echo "</table>\n\n    <div class=\"btn-container\">\n        <input type=\"submit\" value=\"";
                    echo $aInt->lang("global", "savechanges");
                    echo "\" class=\"btn btn-primary\" /> <input type=\"button\" value=\"";
                    echo $aInt->lang("global", "cancelchanges");
                    echo "\" onclick=\"window.location='configproducts.php'\" class=\"btn btn-default\" />\n    </div>\n</form>\n\n";
                    if ($action == "editgroup") {
                        echo WHMCS\View\Asset::jsInclude("Sortable.min.js");
                        $token = generate_token("plain");
                        $growlNotificationAdd = WHMCS\View\Helper::jsGrowlNotification("success", "global.success", "global.changesuccessadded");
                        $growlNotificationReorder = WHMCS\View\Helper::jsGrowlNotification("success", "global.success", "global.changesuccesssorting");
                        $growlNotificationDelete = WHMCS\View\Helper::jsGrowlNotification("success", "global.success", "global.changesuccessdeleted");
                        $jquerycode .= "var successMsgShowing = false;\nSortable.create(featureList, {\n    handle: '.glyphicon-move',\n    animation: 150,\n    ghostClass: 'ghost',\n    store: {\n        /**\n         * Get the order of elements. Called once during initialization.\n         * @param   {Sortable}  sortable\n         * @returns {Array}\n         */\n        get: function (sortable) {\n            //do nothing\n            return [];\n        },\n\n        /**\n         * Save the order of elements. Called onEnd (when the item is dropped).\n         * @param {Sortable}  sortable\n         */\n        set: function (sortable) {\n            var order = sortable.toArray();\n            var post = WHMCS.http.jqClient.post(\n                \"configproducts.php\",\n                {\n                    action: \"feature-sort\",\n                    order: order,\n                    token: \"" . $token . "\"\n                }\n            );\n            post.done(\n                function(data) {\n                    " . $growlNotificationReorder . "\n                }\n            );\n        }\n    },\n    filter: \".remove-feature\",\n    onFilter: function (evt) {\n        var item = evt.item;\n        var id = jQuery(item).attr('data-id');\n        var post = WHMCS.http.jqClient.post(\n            \"configproducts.php\",\n            {\n                action: \"remove-feature\",\n                groupId: \"" . $ids . "\",\n                feature: id,\n                token: \"" . $token . "\"\n            }\n        );\n        post.done(\n            function(data) {\n                " . $growlNotificationDelete . "\n            }\n        );\n        item.parentNode.removeChild(item);\n    }\n});\njQuery(\"#new-feature\").keypress(function (e) {\n    if (e.which == 13) {\n        e.preventDefault();\n        jQuery(\"#new-feature-add\").click();\n    }\n});\njQuery(\"#new-feature-add\").on('click', function () {\n    var feature = jQuery(\"#new-feature\").val();\n    if (feature != \"\") {\n        jQuery(\"#new-feature\").val('');\n        jQuery(\"#new-feature-add-spinner\").fadeOut(10).removeClass('hidden').fadeIn(200);\n        jQuery(\"#new-feature-add\").prop('disabled', true);\n        var post = WHMCS.http.jqClient.post(\n            \"configproducts.php\",\n            {\n                action: \"add-feature\",\n                groupId: \"" . $ids . "\",\n                feature: feature,\n                token: \"" . $token . "\"\n            }\n        );\n        post.done(\n            function(data) {\n                jQuery(\"#featureList\").append(data.html);\n                jQuery(\"#new-feature-add-spinner\").fadeOut(200).addClass('hidden');\n                jQuery(\"#new-feature-add\").prop('disabled', false);\n                " . $growlNotificationAdd . "\n            }\n        );\n    }\n});";
                    }
                    $jquerycode .= "\njQuery(\"input[name='orderfrmtpl']\").change(function() {\n    if (jQuery(this).val() == \"custom\") {\n        jQuery(\"#orderFormTemplateOptions\").hide().removeClass(\"hidden\").slideDown();\n    } else {\n        jQuery(\"#orderFormTemplateOptions\").slideUp();\n    }\n})\n";
                }
            }
        }
    }
}
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();
function printProductDownloads($downloads)
{
    if (!is_array($downloads)) {
        $downloads = array();
    }
    echo "<ul class=\"jqueryFileTree\">";
    foreach ($downloads as $downloadid) {
        $result = select_query("tbldownloads", "", array("id" => $downloadid));
        $data = mysql_fetch_array($result);
        $downid = $data["id"];
        $downtitle = $data["title"];
        $downfilename = $data["location"];
        $downfilenameSplit = explode(".", $downfilename);
        $ext = end($downfilenameSplit);
        echo "<li class=\"file ext_" . $ext . "\"><a href=\"#\" class=\"removedownload\" rel=\"" . $downid . "\">" . $downtitle . "</a></li>";
    }
    echo "</ul>";
}
function buildCategoriesList($level, $parentlevel)
{
    global $categorieslist;
    global $categories;
    $result = select_query("tbldownloadcats", "", array("parentid" => $level), "name", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $id = $data["id"];
        $parentid = $data["parentid"];
        $category = $data["name"];
        $categorieslist .= "<option value=\"" . $id . "\">";
        for ($i = 1; $i <= $parentlevel; $i++) {
            $categorieslist .= "- ";
        }
        $categorieslist .= (string) $category . "</option>";
        buildCategoriesList($id, $parentlevel + 1);
    }
}

?>