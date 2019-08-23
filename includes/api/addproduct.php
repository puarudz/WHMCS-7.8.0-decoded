<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
if (!$name) {
    $apiresults = array("result" => "error", "message" => "You must supply a name for the product");
    return false;
}
if (!$type) {
    $type = "other";
}
if ($stockcontrol || $qty) {
    $stockcontrol = "1";
} else {
    $stockcontrol = "0";
}
if (!$paytype) {
    $paytype = "free";
}
$hidden = (int) (bool) $hidden;
$showdomainoptions = (int) (bool) $showdomainoptions;
$tax = (int) (bool) $tax;
$isFeatured = (int) (bool) $isFeatured;
$proratabilling = (int) (bool) $proratabilling;
$product = new WHMCS\Product\Product();
$product->type = $type;
$product->productGroupId = $gid;
$product->name = $name;
$product->description = WHMCS\Input\Sanitize::decode($description);
$product->isHidden = $hidden;
$product->showDomainOptions = $showdomainoptions;
$product->welcomeEmailTemplateId = $welcomeemail;
$product->stockControlEnabled = $stockcontrol;
$product->quantityInStock = $qty;
$product->proRataBilling = $proratabilling;
$product->proRataChargeDayOfCurrentMonth = $proratadate;
$product->proRataChargeNextMonthAfterDay = $proratachargenextmonth;
$product->paymentType = $paytype;
$product->freeSubDomains = explode(",", $subdomain);
$product->autoSetup = $autosetup;
$product->module = $module;
$product->serverGroupId = $servergroupid;
$product->moduleConfigOption1 = $configoption1;
$product->moduleConfigOption2 = $configoption2;
$product->moduleConfigOption3 = $configoption3;
$product->moduleConfigOption4 = $configoption4;
$product->moduleConfigOption5 = $configoption5;
$product->moduleConfigOption6 = $configoption6;
$product->applyTax = $tax;
$product->displayOrder = $order;
$product->isFeatured = $isFeatured;
$product->save();
$pid = $product->id;
foreach ($pricing as $currency => $values) {
    insert_query("tblpricing", array("type" => "product", "currency" => $currency, "relid" => $pid, "msetupfee" => $values["msetupfee"], "qsetupfee" => $values["qsetupfee"], "ssetupfee" => $values["ssetupfee"], "asetupfee" => $values["asetupfee"], "bsetupfee" => $values["bsetupfee"], "tsetupfee" => $values["tsetupfee"], "monthly" => $values["monthly"], "quarterly" => $values["quarterly"], "semiannually" => $values["semiannually"], "annually" => $values["annually"], "biennially" => $values["biennially"], "triennially" => $values["triennially"]));
}
$apiresults = array("result" => "success", "pid" => $pid);

?>