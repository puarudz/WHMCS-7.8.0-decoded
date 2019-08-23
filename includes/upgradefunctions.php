<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function SumUpPackageUpgradeOrder($id, $newproductid, $newproductbillingcycle, $promocode, $paymentmethod = "", $checkout = "")
{
    global $CONFIG;
    global $_LANG;
    global $currency;
    global $upgradeslist;
    global $orderamount;
    global $orderdescription;
    global $applytax;
    $_SESSION["upgradeids"] = array();
    $whmcs = App::self();
    $configoptionsamount = 0;
    $amountToCredit = 0;
    $result = select_query("tblhosting", "tblproducts.id,tblproducts.name,tblhosting.nextduedate,tblhosting.billingcycle,tblhosting.amount," . "tblhosting.firstpaymentamount,tblhosting.domain", array("userid" => $_SESSION["uid"], "tblhosting.id" => $id), "", "", "", "tblproducts ON tblproducts.id=tblhosting.packageid");
    $data = mysql_fetch_array($result);
    $oldproductid = $data["id"];
    $oldproductname = WHMCS\Product\Product::getProductName($oldproductid, $data["name"]);
    $domain = $data["domain"];
    $nextduedate = $data["nextduedate"];
    $billingcycle = $data["billingcycle"];
    $oldamount = $data["amount"];
    if ($billingcycle == "One Time") {
        $oldamount = $data["firstpaymentamount"];
    }
    $cycle = new WHMCS\Billing\Cycles();
    if (!($cycle->isValidSystemBillingCycle($newproductbillingcycle) || $cycle->isValidPublicBillingCycle($newproductbillingcycle))) {
        exit("Invalid New Billing Cycle");
    }
    if (defined("CLIENTAREA")) {
        try {
            $currentProduct = WHMCS\Product\Product::findOrFail($oldproductid);
            $upgradeProductIds = $currentProduct->upgradeProducts()->pluck("upgrade_product_id");
        } catch (Exception $e) {
            throw new WHMCS\Exception\Fatal("Invalid Current Product ID");
        }
        if (!$upgradeProductIds->contains($newproductid)) {
            throw new WHMCS\Exception\Fatal("Invalid new product ID for upgrade");
        }
    }
    try {
        $product = WHMCS\Product\Product::findOrFail($newproductid);
        $newproductid = $product->id;
        $newproductname = $product->name;
        $applytax = $product->applyTax;
        $paytype = $product->paymentType;
        $stockControlEnabled = $product->stockControlEnabled;
        $quantityInStock = $product->quantityInStock;
    } catch (Exception $e) {
        throw new WHMCS\Exception\Fatal("Invalid New Product ID");
    }
    if ($stockControlEnabled && $quantityInStock <= 0 && $oldproductid != $newproductid) {
        throw new WHMCS\Exception\Fatal("Product Out of Stock");
    }
    $normalisedBillingCycle = $cycle->getNormalisedBillingCycle($newproductbillingcycle);
    if (!in_array($normalisedBillingCycle, $product->getAvailableBillingCycles())) {
        throw new WHMCS\Exception\Fatal("Invalid Billing Cycle Requested");
    }
    $newproductbillingcycleraw = $newproductbillingcycle;
    $newproductbillingcyclenice = ucfirst($newproductbillingcycle);
    if ($newproductbillingcyclenice == "Semiannually") {
        $newproductbillingcyclenice = "Semi-Annually";
    }
    $configoptionspricingarray = getCartConfigOptions($newproductid, "", $newproductbillingcyclenice, $id);
    if ($configoptionspricingarray) {
        foreach ($configoptionspricingarray as $configoptionkey => $configoptionvalues) {
            $configoptionsamount += $configoptionvalues["selectedrecurring"];
        }
    }
    $newproductbillingcycle = $normalisedBillingCycle;
    if ($newproductbillingcycle == "onetime") {
        $newproductbillingcycle = "monthly";
    }
    if ($newproductbillingcycle == "free") {
        $newamount = 0;
    } else {
        $result = select_query("tblpricing", $newproductbillingcycle, array("type" => "product", "currency" => $currency["id"], "relid" => $newproductid));
        $data = mysql_fetch_array($result);
        $newamount = $data[$newproductbillingcycle];
    }
    if (($paytype == "onetime" || $paytype == "recurring") && $newamount < 0) {
        exit("Invalid New Billing Cycle");
    }
    $newamount += $configoptionsamount;
    $year = substr($nextduedate, 0, 4);
    $month = substr($nextduedate, 5, 2);
    $day = substr($nextduedate, 8, 2);
    $oldCycleMonths = getBillingCycleMonths($billingcycle);
    $prevduedate = date("Y-m-d", mktime(0, 0, 0, $month - $oldCycleMonths, $day, $year));
    $totaldays = round((strtotime($nextduedate) - strtotime($prevduedate)) / 86400);
    $newCycleMonths = getBillingCycleMonths($newproductbillingcyclenice);
    $prevduedate = date("Y-m-d", mktime(0, 0, 0, $month - $newCycleMonths, $day, $year));
    $newtotaldays = round((strtotime($nextduedate) - strtotime($prevduedate)) / 86400);
    if ($newproductbillingcyclenice == "Onetime") {
        $newtotaldays = $totaldays;
    }
    if ($billingcycle == "Free Account" || $billingcycle == "One Time") {
        $days = $newtotaldays = $totaldays = getBillingCycleDays($newproductbillingcyclenice);
        $totalmonths = getBillingCycleMonths($newproductbillingcyclenice);
        $nextduedate = date("Y-m-d", mktime(0, 0, 0, date("m") + $totalmonths, date("d"), date("Y")));
        $amountdue = format_as_currency($newamount - $oldamount);
        $difference = $newamount;
    } else {
        $todaysdate = date("Ymd");
        $nextduedatetime = strtotime($nextduedate);
        $todaysdate = strtotime($todaysdate);
        $days = round(($nextduedatetime - $todaysdate) / 86400);
        $oldAmountPerMonth = round($oldamount / $oldCycleMonths, 2);
        $newAmountPerMonth = round($newamount / $newCycleMonths, 2);
        if ($oldAmountPerMonth == $newAmountPerMonth) {
            $newamount = $oldamount / $totaldays * $newtotaldays;
        }
        $daysnotused = $days / $totaldays;
        $refundamount = $oldamount * $daysnotused;
        $cyclemultiplier = $days / $newtotaldays;
        $amountdue = $newamount * $cyclemultiplier;
        $amountdue = $amountdue - $refundamount;
        if ($amountdue < 0 && !$CONFIG["CreditOnDowngrade"]) {
            $amountToCredit = $amountdue;
            $amountdue = 0;
        }
        $amountdue = format_as_currency($amountdue);
        $difference = $newamount - $oldamount;
    }
    $discount = 0;
    $promoqualifies = true;
    if ($promocode) {
        $promodata = validateUpgradePromo($promocode);
        if (is_array($promodata)) {
            $appliesto = $promodata["appliesto"];
            $requires = $promodata["requires"];
            $cycles = $promodata["cycles"];
            $value = $promodata["value"];
            $type = $promodata["discounttype"];
            $promodesc = $promodata["desc"];
            if ($newproductbillingcycle == "free") {
                $billingcycle = "Free Account";
            } else {
                if ($newproductbillingcycle == "onetime") {
                    $billingcycle = "One Time";
                } else {
                    if ($newproductbillingcycle == "semiannually") {
                        $billingcycle = "Semi-Annually";
                    } else {
                        $billingcycle = ucfirst($newproductbillingcycle);
                    }
                }
            }
            if (count($appliesto) && $appliesto[0] && !in_array($newproductid, $appliesto)) {
                $promoqualifies = false;
            }
            if (count($requires) && $requires[0] && !in_array($oldproductid, $requires)) {
                $promoqualifies = false;
            }
            if (count($cycles) && $cycles[0] && !in_array($billingcycle, $cycles)) {
                $promoqualifies = false;
            }
            if ($promoqualifies && 0 < $amountdue) {
                if ($type == "Percentage") {
                    $percent = $value / 100;
                    $discount = $amountdue * $percent;
                } else {
                    $discount = $value;
                    if ($amountdue < $discount) {
                        $discount = $amountdue;
                    }
                }
            }
        }
        if ($discount == 0) {
            $promodata = get_query_vals("tblpromotions", "type,value", array("lifetimepromo" => 1, "recurring" => 1, "code" => $promocode));
            if (is_array($promodata)) {
                if ($promodata["type"] == "Percentage") {
                    $percent = $promodata["value"] / 100;
                    $discount = $amountdue * $percent;
                } else {
                    $discount = $promodata["value"];
                    if ($amountdue < $discount) {
                        $discount = $amountdue;
                    }
                }
                $promoqualifies = true;
            }
        }
    }
    $upgradearray[] = array("oldproductid" => $oldproductid, "oldproductname" => $oldproductname, "newproductid" => $newproductid, "newproductname" => $newproductname, "daysuntilrenewal" => $days, "totaldays" => $totaldays, "newproductbillingcycle" => $newproductbillingcycleraw, "price" => $amountdue, "discount" => $discount, "promoqualifies" => $promoqualifies);
    $hookReturns = run_hook("OrderProductUpgradeOverride", $upgradearray[0]);
    foreach ($hookReturns as $hookReturn) {
        if (is_array($hookReturn)) {
            if (isset($hookReturn["price"])) {
                $upgradearray[0]["price"] = $hookReturn["price"];
                $amountdue = $upgradearray[0]["price"];
            }
            if (isset($hookReturn["discount"])) {
                $discount = $hookReturn["discount"];
            }
            if (isset($hookReturn["promoqualifies"])) {
                if (!is_bool($hookReturn["promoqualifies"])) {
                    throw new WHMCS\Exception\Fatal("Invalid promo qualification parameter returned by hook. " . "Must be boolean, returned " . gettype($hookReturn["promoqualifies"]));
                }
                $promoqualifies = $hookReturn["promoqualifies"];
            }
            if (isset($hookReturn["daysuntilrenewal"])) {
                $upgradearray[0]["daysuntilrenewal"] = $hookReturn["daysuntilrenewal"];
            }
            if (isset($hookReturn["totaldays"])) {
                $upgradearray[0]["totaldays"] = $hookReturn["totaldays"];
            }
            if (isset($hookReturn["newproductbillingcycle"])) {
                $upgradearray[0]["newproductbillingcycle"] = $hookReturn["newproductbillingcycle"];
            }
            try {
                if (isset($hookReturn["oldproductid"])) {
                    $product = WHMCS\Product\Product::findOrFail($oldproductid);
                    $upgradearray[0]["oldproductname"] = $product->name;
                }
                if (isset($hookReturn["newproductid"])) {
                    $product = WHMCS\Product\Product::findOrFail($newproductid);
                    $upgradearray[0]["newproductname"] = $product->name;
                }
            } catch (Exception $e) {
                throw new WHMCS\Exception\Fatal("Invalid Product ID returned by hook");
            }
        }
    }
    $upgradearray[0]["price"] = formatCurrency($upgradearray[0]["price"]);
    unset($upgradearray[0]["discount"]);
    unset($upgradearray[0]["promoqualifies"]);
    $GLOBALS["subtotal"] = $amountdue;
    $GLOBALS["qualifies"] = $promoqualifies;
    $GLOBALS["discount"] = $discount;
    $client = WHMCS\User\Client::find(WHMCS\Session::get("uid"));
    $totalDue = $amountdue;
    if ($whmcs->get_config("TaxEnabled") && $applytax && !$client->taxExempt) {
        $taxData = getTaxRate(1, $client->state, $client->country);
        $taxRate = $taxData["rate"] / 100;
        $taxData = getTaxRate(2, $client->state, $client->country);
        $taxRate2 = $taxData["rate"] / 100;
        if ($whmcs->get_config("TaxType") == "Exclusive") {
            if ($whmcs->get_config("TaxL2Compound")) {
                $totalDue += $totalDue * $taxRate;
                $totalDue += $totalDue * $taxRate2;
            } else {
                $totalDue += $totalDue * $taxRate + $totalDue * $taxRate2;
            }
        }
    }
    if ($checkout) {
        $orderdescription = $_LANG["upgradedowngradepackage"] . ": " . $oldproductname . " => " . $newproductname . "<br>\n" . $_LANG["orderbillingcycle"] . ": " . $_LANG["orderpaymentterm" . str_replace(array("-", " "), "", strtolower($newproductbillingcycle))] . "<br>\n" . $_LANG["ordertotalduetoday"] . ": " . formatCurrency($totalDue);
        $amountwithdiscount = $amountdue - $discount;
        $upgradeid = insert_query("tblupgrades", array("type" => "package", "date" => "now()", "relid" => $id, "originalvalue" => $oldproductid, "newvalue" => (string) $newproductid . "," . $newproductbillingcycleraw, "amount" => $amountwithdiscount, "recurringchange" => $difference));
        $upgradeslist .= $upgradeid . ",";
        $_SESSION["upgradeids"][] = $upgradeid;
        $hookReturns = run_hook("PreUpgradeCheckout", array("clientId" => (int) WHMCS\Session::get("uid"), "upgradeId" => $upgradeid, "serviceId" => $id, "amount" => $amountdue, "discount" => $discount));
        foreach ($hookReturns as $hookReturn) {
            if (is_array($hookReturn)) {
                if (array_key_exists("amount", $hookReturn) && is_numeric($hookReturn["amount"])) {
                    $amountdue = $hookReturn["amount"];
                }
                if (array_key_exists("discount", $hookReturn) && is_numeric($hookReturn["discount"])) {
                    $discount = $hookReturn["discount"];
                }
                $amountwithdiscount = $amountdue - $discount;
                WHMCS\Database\Capsule::table("tblupgrades")->where("id", $upgradeid)->update(array("amount" => $amountwithdiscount));
            }
        }
        if (0 < $amountdue) {
            if ($domain) {
                $domain = " - " . $domain;
            }
            insert_query("tblinvoiceitems", array("userid" => $_SESSION["uid"], "type" => "Upgrade", "relid" => $upgradeid, "description" => $_LANG["upgradedowngradepackage"] . ": " . $oldproductname . $domain . "\n" . $oldproductname . " => " . $newproductname . " " . "(" . getTodaysDate() . " - " . fromMySQLDate($nextduedate) . ")", "amount" => $amountdue, "taxed" => $applytax, "duedate" => "now()", "paymentmethod" => $paymentmethod));
            if (0 < $discount) {
                insert_query("tblinvoiceitems", array("userid" => $_SESSION["uid"], "description" => $_LANG["orderpromotioncode"] . ": " . $promocode . " - " . $promodesc, "amount" => $discount * -1, "taxed" => $applytax, "duedate" => "now()", "paymentmethod" => $paymentmethod));
            }
            $orderamount += $amountwithdiscount;
        } else {
            if ($CONFIG["CreditOnDowngrade"]) {
                $creditamount = $amountdue * -1;
                insert_query("tblcredit", array("clientid" => $_SESSION["uid"], "date" => "now()", "description" => "Upgrade/Downgrade Credit", "amount" => $creditamount));
                update_query("tblclients", array("credit" => "+=" . $creditamount), array("id" => (int) $_SESSION["uid"]));
            } else {
                if ($amountToCredit) {
                    WHMCS\Session::set("UpgradeCredit" . $upgradeid, $amountToCredit);
                }
            }
            update_query("tblupgrades", array("paid" => "Y"), array("id" => $upgradeid));
            doUpgrade($upgradeid);
        }
    }
    return $upgradearray;
}
function SumUpConfigOptionsOrder($id, $configoptions, $promocode, $paymentmethod = "", $checkout = "")
{
    global $CONFIG;
    global $_LANG;
    global $upgradeslist;
    global $orderamount;
    global $orderdescription;
    global $applytax;
    $amountToCredit = 0;
    $_SESSION["upgradeids"] = array();
    $whmcs = App::self();
    $result = select_query("tblhosting", "packageid,domain,nextduedate,billingcycle", array("userid" => $_SESSION["uid"], "id" => $id));
    $data = mysql_fetch_array($result);
    $packageid = $data["packageid"];
    $domain = $data["domain"];
    $nextduedate = $data["nextduedate"];
    $billingcycle = $data["billingcycle"];
    $productInfo = WHMCS\Database\Capsule::table("tblproducts")->find($packageid, array("tax", "name", "configoptionsupgrade"));
    $applytax = $productInfo->tax;
    $allowConfigOptionsUpgrade = $productInfo->configoptionsupgrade;
    if (defined("CLIENTAREA") && !$allowConfigOptionsUpgrade) {
        redir("type=configoptions&id=" . (int) $id, "upgrade.php");
    }
    $productname = WHMCS\Product\Product::getProductName($packageid, $productInfo->name);
    if ($domain) {
        $productname .= " - " . $domain;
    }
    $year = substr($nextduedate, 0, 4);
    $month = substr($nextduedate, 5, 2);
    $day = substr($nextduedate, 8, 2);
    $cyclemonths = getBillingCycleMonths($billingcycle);
    $prevduedate = date("Y-m-d", mktime(0, 0, 0, $month - $cyclemonths, $day, $year));
    $totaldays = round((strtotime($nextduedate) - strtotime($prevduedate)) / 86400);
    $todaysdate = date("Ymd");
    $todaysdate = strtotime($todaysdate);
    $nextduedatetime = strtotime($nextduedate);
    $days = round(($nextduedatetime - $todaysdate) / 86400);
    if ($days < 0) {
        $days = $totaldays;
    }
    $percentage = $days / $totaldays;
    $discount = 0;
    $promoqualifies = true;
    if ($promocode) {
        $promodata = validateUpgradePromo($promocode);
        if (is_array($promodata)) {
            $appliesto = $promodata["appliesto"];
            $cycles = $promodata["cycles"];
            $promotype = $promodata["type"];
            $promovalue = $promodata["value"];
            $discounttype = $promodata["discounttype"];
            $upgradeconfigoptions = $promodata["configoptions"];
            $promodesc = $promodata["desc"];
            if ($promotype != "configoptions") {
                $promoqualifies = false;
            }
            if (count($appliesto) && $appliesto[0] && !in_array($packageid, $appliesto)) {
                $promoqualifies = false;
            }
            if (count($cycles) && $cycles[0] && !in_array($billingcycle, $cycles)) {
                $promoqualifies = false;
            }
            if ($discounttype == "Percentage") {
                $promovalue = $promovalue / 100;
            }
        }
        if ($promovalue == 0) {
            $promodata = get_query_vals("tblpromotions", "upgrades, upgradeconfig, type,value", array("lifetimepromo" => 1, "recurring" => 1, "code" => $promocode));
            if (is_array($promodata)) {
                if ($promodata["upgrades"] == 1) {
                    $upgradeconfig = safe_unserialize($promodata["upgradeconfig"]);
                    if ($upgradeconfig["type"] != "configoptions") {
                        $promoqualifies = false;
                    }
                    $promovalue = $upgradeconfig["value"];
                    $discounttype = $upgradeconfig["discounttype"];
                    if ($discounttype == "Percentage") {
                        $promovalue = $promovalue / 100;
                    }
                    $promoqualifies = true;
                } else {
                    $promoqualifies = false;
                }
            }
        }
    }
    $configoptions = getCartConfigOptions($packageid, $configoptions, $billingcycle);
    $oldconfigoptions = getCartConfigOptions($packageid, "", $billingcycle, $id);
    $subtotal = 0;
    foreach ($configoptions as $key => $configoption) {
        $configid = $configoption["id"];
        $configname = $configoption["optionname"];
        $optiontype = $configoption["optiontype"];
        $new_selectedvalue = $configoption["selectedvalue"];
        $new_selectedqty = $configoption["selectedqty"];
        $new_selectedname = $configoption["selectedname"];
        $new_selectedsetup = $configoption["selectedsetup"];
        $new_selectedrecurring = $configoption["selectedrecurring"];
        $old_selectedvalue = $oldconfigoptions[$key]["selectedvalue"];
        $old_selectedqty = $oldconfigoptions[$key]["selectedqty"];
        $old_selectedname = $oldconfigoptions[$key]["selectedname"];
        $old_selectedsetup = $oldconfigoptions[$key]["selectedsetup"];
        $old_selectedrecurring = $oldconfigoptions[$key]["selectedrecurring"];
        if (($optiontype == 1 || $optiontype == 2) && $new_selectedvalue != $old_selectedvalue || ($optiontype == 3 || $optiontype == 4) && $new_selectedqty != $old_selectedqty) {
            $difference = $new_selectedrecurring - $old_selectedrecurring;
            $amountdue = $difference * $percentage;
            $amountdue = format_as_currency($amountdue);
            if (!$CONFIG["CreditOnDowngrade"] && $amountdue < 0) {
                $amountToCredit = $amountdue;
                $amountdue = format_as_currency(0);
            }
            if ($optiontype == 1 || $optiontype == 2) {
                $db_orig_value = $old_selectedvalue;
                $db_new_value = $new_selectedvalue;
                $originalvalue = $old_selectedname;
                $newvalue = $new_selectedname;
            } else {
                if ($optiontype == 3) {
                    $db_orig_value = $old_selectedqty;
                    $db_new_value = $new_selectedqty;
                    if ($old_selectedqty) {
                        $originalvalue = $_LANG["yes"];
                        $newvalue = $_LANG["no"];
                    } else {
                        $originalvalue = $_LANG["no"];
                        $newvalue = $_LANG["yes"];
                    }
                } else {
                    if ($optiontype == 4) {
                        $new_selectedqty = (int) $new_selectedqty;
                        if ($new_selectedqty < 0) {
                            $new_selectedqty = 0;
                        }
                        $db_orig_value = $old_selectedqty;
                        $db_new_value = $new_selectedqty;
                        $originalvalue = $old_selectedqty;
                        $newvalue = $new_selectedqty . " x " . $configoption["options"][0]["nameonly"];
                    }
                }
            }
            $subtotal += $amountdue;
            $itemdiscount = 0;
            if ($promoqualifies && 0 < $amountdue && (!count($upgradeconfigoptions) || in_array($configid, $upgradeconfigoptions))) {
                $itemdiscount = $discounttype == "Percentage" ? round($amountdue * $promovalue, 2) : ($amountdue < $promovalue ? $amountdue : $promovalue);
            }
            $discount += $itemdiscount;
            $upgradearray[] = array("configname" => $configname, "originalvalue" => $originalvalue, "newvalue" => $newvalue, "price" => formatCurrency($amountdue));
            $client = WHMCS\User\Client::find(WHMCS\Session::get("uid"));
            $totalDue = $amountdue;
            if ($whmcs->get_config("TaxEnabled") && $applytax && $client && !$client->taxExempt) {
                $taxData = getTaxRate(1, $client->state, $client->country);
                $taxRate = $taxData["rate"] / 100;
                $taxData = getTaxRate(2, $client->state, $client->country);
                $taxRate2 = $taxData["rate"] / 100;
                if ($whmcs->get_config("TaxType") == "Exclusive") {
                    if ($whmcs->get_config("TaxL2Compound")) {
                        $totalDue += $totalDue * $taxRate;
                        $totalDue += $totalDue * $taxRate2;
                    } else {
                        $totalDue += $totalDue * $taxRate + $totalDue * $taxRate2;
                    }
                }
            }
            if ($checkout) {
                if ($orderdescription) {
                    $orderdescription .= "<br>\n<br>\n";
                }
                $orderdescription .= $_LANG["upgradedowngradeconfigoptions"] . ": " . $configname . " - " . $originalvalue . " => " . $newvalue . "<br>\nAmount Due: " . formatCurrency($totalDue);
                $paid = "N";
                if ($amountdue <= 0) {
                    $paid = "Y";
                }
                $amountwithdiscount = $amountdue - $itemdiscount;
                $upgradeid = insert_query("tblupgrades", array("type" => "configoptions", "date" => "now()", "relid" => $id, "originalvalue" => (string) $configid . "=>" . $db_orig_value, "newvalue" => $db_new_value, "amount" => $amountwithdiscount, "recurringchange" => $difference, "status" => "Pending", "paid" => $paid));
                $_SESSION["upgradeids"][] = $upgradeid;
                $hookReturns = run_hook("PreUpgradeCheckout", array("clientId" => (int) WHMCS\Session::get("uid"), "upgradeId" => $upgradeid, "serviceId" => $id, "amount" => $amountdue, "discount" => $discount));
                foreach ($hookReturns as $hookReturn) {
                    if (is_array($hookReturn)) {
                        if (array_key_exists("amount", $hookReturn) && is_numeric($hookReturn["amount"])) {
                            $amountdue = $hookReturn["amount"];
                        }
                        if (array_key_exists("discount", $hookReturn) && is_numeric($hookReturn["discount"])) {
                            $discount = $hookReturn["discount"];
                        }
                        $amountwithdiscount = $amountdue - $discount;
                        WHMCS\Database\Capsule::table("tblupgrades")->where("id", $upgradeid)->update(array("amount" => $amountwithdiscount));
                    }
                }
                if (0 < $amountdue) {
                    insert_query("tblinvoiceitems", array("userid" => $_SESSION["uid"], "type" => "Upgrade", "relid" => $upgradeid, "description" => $_LANG["upgradedowngradeconfigoptions"] . ": " . $productname . "\n" . $configname . ": " . $originalvalue . " => " . $newvalue . " (" . getTodaysDate() . " - " . fromMySQLDate($nextduedate) . ")", "amount" => $amountdue, "taxed" => $applytax, "duedate" => "now()", "paymentmethod" => $paymentmethod));
                    if (0 < $itemdiscount) {
                        insert_query("tblinvoiceitems", array("userid" => $_SESSION["uid"], "description" => $_LANG["orderpromotioncode"] . ": " . $promocode . " - " . $promodesc, "amount" => $itemdiscount * -1, "taxed" => $applytax, "duedate" => "now()", "paymentmethod" => $paymentmethod));
                    }
                    $orderamount += $amountwithdiscount;
                } else {
                    if ($CONFIG["CreditOnDowngrade"]) {
                        $creditamount = $amountdue * -1;
                        insert_query("tblcredit", array("clientid" => $_SESSION["uid"], "date" => "now()", "description" => "Upgrade/Downgrade Credit", "amount" => $creditamount));
                        update_query("tblclients", array("credit" => "+=" . $creditamount), array("id" => (int) $_SESSION["uid"]));
                    } else {
                        if ($amountToCredit) {
                            WHMCS\Session::set("UpgradeCredit" . $upgradeid, $amountToCredit);
                        }
                    }
                    doUpgrade($upgradeid);
                }
            }
        }
    }
    if (!count($upgradearray)) {
        if (defined("CLIENTAREA")) {
            redir("type=configoptions&id=" . (int) $id, "upgrade.php");
        } else {
            return array();
        }
    }
    $GLOBALS["subtotal"] = $subtotal;
    $GLOBALS["qualifies"] = $promoqualifies;
    $GLOBALS["discount"] = $discount;
    return $upgradearray;
}
function createUpgradeOrder($serviceId, $ordernotes, $promocode, $paymentmethod)
{
    global $CONFIG;
    global $remote_ip;
    global $orderdescription;
    global $orderamount;
    $whmcs = App::self();
    if ($promocode && !$GLOBALS["qualifies"]) {
        $promocode = "";
    }
    if ($promocode) {
        $result = select_query("tblpromotions", "upgradeconfig", array("code" => $promocode));
        $data = mysql_fetch_array($result);
        $upgradeconfig = $data["upgradeconfig"];
        $upgradeconfig = safe_unserialize($upgradeconfig);
        $promo_type = $upgradeconfig["discounttype"];
        $promo_value = $upgradeconfig["value"];
        update_query("tblpromotions", array("uses" => "+1"), array("code" => $promocode));
    }
    $order_number = generateUniqueID();
    $orderid = insert_query("tblorders", array("ordernum" => $order_number, "userid" => $_SESSION["uid"], "date" => "now()", "status" => "Pending", "promocode" => $promocode, "promotype" => $promo_type, "promovalue" => $promo_value, "paymentmethod" => $paymentmethod, "ipaddress" => $remote_ip, "amount" => $orderamount, "notes" => $ordernotes));
    $additionalOrderNote = "";
    foreach ($_SESSION["upgradeids"] as $upgradeid) {
        update_query("tblupgrades", array("orderid" => $orderid), array("id" => $upgradeid));
        $upgradeCreditAmount = WHMCS\Session::getAndDelete("UpgradeCredit" . $upgradeid);
        if ($upgradeCreditAmount && is_numeric($upgradeCreditAmount)) {
            $additionalOrderNote .= "Upgrade Order Credit Amount Calculated as: " . format_as_currency($upgradeCreditAmount * -1) . "\r\n";
        }
    }
    if ($additionalOrderNote) {
        $ordernotes .= "\r\n==========\r\nCredit on Downgrade Disabled\r\n" . $additionalOrderNote;
        WHMCS\Database\Capsule::table("tblorders")->where("id", $orderid)->update(array("notes" => $ordernotes));
    }
    sendMessage("Order Confirmation", $_SESSION["uid"], array("order_id" => $orderid, "order_number" => $order_number, "order_details" => $orderdescription));
    logActivity("Upgrade Order Placed - Order ID: " . $orderid, $_SESSION["uid"]);
    if (!function_exists("createInvoices")) {
        include ROOTDIR . "/includes/processinvoices.php";
    }
    $invoiceid = 0;
    $invoiceid = createInvoices($_SESSION["uid"], true);
    if ($invoiceid) {
        $result = select_query("tblinvoiceitems", "invoiceid", "type='Upgrade' AND relid IN (" . db_build_in_array($_SESSION["upgradeids"]) . ")", "invoiceid", "DESC");
        $data = mysql_fetch_array($result);
        $invoiceid = $data["invoiceid"];
    }
    if ($CONFIG["OrderDaysGrace"]) {
        $new_time = mktime(0, 0, 0, date("m"), date("d") + $CONFIG["OrderDaysGrace"], date("Y"));
        $duedate = date("Y-m-d", $new_time);
        update_query("tblinvoices", array("duedate" => $duedate), array("id" => $invoiceid));
    }
    if (!$CONFIG["NoInvoiceEmailOnOrder"]) {
        if ($whmcs->isClientAreaRequest()) {
            $source = "clientarea";
        } else {
            if ($whmcs->isAdminAreaRequest()) {
                $source = "adminarea";
            } else {
                if ($whmcs->isApiRequest()) {
                    $source = "api";
                } else {
                    $source = "autogen";
                }
            }
        }
        $invoiceArr = array("source" => $source, "user" => WHMCS\Session::get("adminid") ? WHMCS\Session::get("adminid") : "system", "invoiceid" => $invoiceid);
        run_hook("InvoiceCreationPreEmail", $invoiceArr);
        sendMessage("Invoice Created", $invoiceid);
    }
    update_query("tblorders", array("invoiceid" => $invoiceid), array("id" => $orderid));
    $result = select_query("tblclients", "firstname, lastname, companyname, email, address1, address2, city, state, postcode, country, phonenumber, ip, host", array("id" => $_SESSION["uid"]));
    $data = mysql_fetch_array($result);
    list($firstname, $lastname, $companyname, $email, $address1, $address2, $city, $state, $postcode, $country, $phonenumber, $ip, $host) = $data;
    $nicegatewayname = get_query_val("tblpaymentgateways", "value", array("gateway" => $paymentmethod, "setting" => "Name"));
    $ordertotal = get_query_val("tblinvoices", "total", array("id" => $invoiceid));
    $adminemailitems = "";
    if ($invoiceid) {
        $result = select_query("tblinvoiceitems", "description", "type='Upgrade' AND relid IN (" . db_build_in_array($_SESSION["upgradeids"]) . ")", "invoiceid", "DESC");
        while ($invoicedata = mysql_fetch_assoc($result)) {
            $adminemailitems .= $invoicedata["description"] . "<br />";
        }
    } else {
        $adminemailitems .= $orderdescription;
    }
    if (!$adminemailitems) {
        $adminemailitems = "Upgrade/Downgrade";
    }
    sendAdminMessage("New Order Notification", array("order_id" => $orderid, "order_number" => $order_number, "order_date" => date("d/m/Y H:i:s"), "invoice_id" => $invoiceid, "order_payment_method" => $nicegatewayname, "order_total" => formatCurrency($ordertotal), "client_id" => $_SESSION["uid"], "client_first_name" => $firstname, "client_last_name" => $lastname, "client_email" => $email, "client_company_name" => $companyname, "client_address1" => $address1, "client_address2" => $address2, "client_city" => $city, "client_state" => $state, "client_postcode" => $postcode, "client_country" => $country, "client_phonenumber" => $phonenumber, "order_items" => $adminemailitems, "order_notes" => "", "client_ip" => $ip, "client_hostname" => $host), "account");
    if (WHMCS\Config\Setting::getValue("AutoCancelSubscriptions")) {
        if (!function_exists("cancelSubscriptionForService")) {
            require ROOTDIR . "/includes/gatewayfunctions.php";
        }
        try {
            cancelSubscriptionForService($serviceId, WHMCS\Session::get("uid"));
        } catch (Exception $e) {
        }
    }
    return array("id" => $serviceId, "orderid" => $orderid, "order_number" => $order_number, "invoiceid" => $invoiceid);
}
function processUpgradePayment($upgradeid, $paidamount, $fees, $invoice = "", $gateway = "", $transid = "")
{
    update_query("tblupgrades", array("paid" => "Y"), array("id" => $upgradeid));
    doUpgrade($upgradeid);
}
function doUpgrade($upgradeid)
{
    $newpackageid = $newbillingcycle = $billingcycle = $configid = $optiontype = "";
    $tempvalue = array();
    $upgrade = WHMCS\Service\Upgrade\Upgrade::find($upgradeid);
    $orderid = $upgrade->orderId;
    $type = $upgrade->type;
    $relid = $upgrade->relid;
    $originalvalue = $upgrade->originalValue;
    $newvalue = $upgrade->newValue;
    $upgradeamount = $upgrade->upgradeAmount;
    $recurringchange = $upgrade->recurringChange;
    $result = select_query("tblorders", "promocode", array("id" => $orderid));
    $data = mysql_fetch_array($result);
    $promocode = $data["promocode"];
    if ($type == "package") {
        $newvalue = explode(",", $newvalue);
        list($newpackageid, $newbillingcycle) = $newvalue;
        $changevalue = "amount";
        if ($newbillingcycle == "free") {
            $newbillingcycle = "Free Account";
        } else {
            if ($newbillingcycle == "onetime") {
                $newbillingcycle = "One Time";
                $changevalue = "firstpaymentamount";
                $recurringchange = $upgradeamount;
            } else {
                if ($newbillingcycle == "monthly") {
                    $newbillingcycle = "Monthly";
                } else {
                    if ($newbillingcycle == "quarterly") {
                        $newbillingcycle = "Quarterly";
                    } else {
                        if ($newbillingcycle == "semiannually") {
                            $newbillingcycle = "Semi-Annually";
                        } else {
                            if ($newbillingcycle == "annually") {
                                $newbillingcycle = "Annually";
                            } else {
                                if ($newbillingcycle == "biennially") {
                                    $newbillingcycle = "Biennially";
                                } else {
                                    if ($newbillingcycle == "triennially") {
                                        $newbillingcycle = "Triennially";
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $result = select_query("tblhosting", "billingcycle", array("id" => $relid));
        $data = mysql_fetch_array($result);
        $billingcycle = $data["billingcycle"];
        if ($billingcycle == "Free Account" || $billingcycle == "One Time") {
            $newnextdue = getInvoicePayUntilDate(date("Y-m-d"), $newbillingcycle, true);
            update_query("tblhosting", array("nextduedate" => $newnextdue, "nextinvoicedate" => $newnextdue), array("id" => $relid));
        }
        if (!function_exists("migrateCustomFieldsBetweenProducts")) {
            require ROOTDIR . "/includes/customfieldfunctions.php";
        }
        migrateCustomFieldsBetweenProducts($relid, $newpackageid);
        update_query("tblhosting", array("packageid" => $newpackageid, "billingcycle" => $newbillingcycle, (string) $changevalue => "+=" . $recurringchange), array("id" => $relid));
        cancelUnpaidInvoiceForPreviousPriceAndRegenerateNewInvoiceByServiceId($relid);
        if (!function_exists("getCartConfigOptions")) {
            require ROOTDIR . "/includes/configoptionsfunctions.php";
        }
        $configoptions = getCartConfigOptions($newpackageid, "", $newbillingcycle);
        foreach ($configoptions as $configoption) {
            $result = select_query("tblhostingconfigoptions", "COUNT(*)", array("relid" => $relid, "configid" => $configoption["id"]));
            $data = mysql_fetch_array($result);
            if (!$data[0]) {
                insert_query("tblhostingconfigoptions", array("relid" => $relid, "configid" => $configoption["id"], "optionid" => $configoption["selectedvalue"]));
            }
        }
        $newProduct = WHMCS\Product\Product::findOrFail($newpackageid);
        if ($newProduct->stockControlEnabled) {
            $newProduct->quantityInStock = $newProduct->quantityInStock - 1;
            $newProduct->save();
        }
        $oldProduct = WHMCS\Service\Service::findOrFail($relid)->product();
        if ($oldProduct->stockControlEnabled) {
            $oldProduct->quantityInStock = $oldProduct->quantityInStock + 1;
            $oldProduct->save();
        }
        run_hook("AfterProductUpgrade", array("upgradeid" => $upgradeid));
        run_hook("AfterServiceUpgrade", array("upgradeId" => $upgradeid, "clientId" => $upgrade->userId, "serviceId" => $upgrade->relid));
    } else {
        if ($type == "configoptions") {
            $tempvalue = explode("=>", $originalvalue);
            $configid = $tempvalue[0];
            $result = select_query("tblproductconfigoptions", "", array("id" => $configid));
            $data = mysql_fetch_array($result);
            $optiontype = $data["optiontype"];
            $result = select_query("tblhostingconfigoptions", "COUNT(*)", array("relid" => $relid, "configid" => $configid));
            $data = mysql_fetch_array($result);
            if (!$data[0]) {
                insert_query("tblhostingconfigoptions", array("relid" => $relid, "configid" => $configid));
            }
            if ($optiontype == 1 || $optiontype == 2) {
                update_query("tblhostingconfigoptions", array("optionid" => $newvalue), array("relid" => $relid, "configid" => $configid));
            } else {
                if ($optiontype == 3 || $optiontype == 4) {
                    update_query("tblhostingconfigoptions", array("qty" => $newvalue), array("relid" => $relid, "configid" => $configid));
                }
            }
            update_query("tblhosting", array("amount" => "+=" . $recurringchange), array("id" => $relid));
            run_hook("AfterConfigOptionsUpgrade", array("upgradeid" => $upgradeid));
        } else {
            $newNextDueDate = getInvoicePayUntilDate(date("Y-m-d"), $upgrade->newCycle, true);
            if (!function_exists("migrateCustomFieldsBetweenProducts")) {
                require ROOTDIR . "/includes/customfieldfunctions.php";
            }
            migrateCustomFieldsBetweenProductsOrAddons($upgrade->relid, $upgrade->newValue, $upgrade->originalvalue, false, $upgrade->type == "addon");
            if ($upgrade->type == "service") {
                $service = WHMCS\Service\Service::find($upgrade->relid);
                $service->nextDueDate = $newNextDueDate;
                $service->nextInvoiceDate = $newNextDueDate;
                $service->packageId = $upgrade->newValue;
                $service->billingCycle = $upgrade->newCycle;
                $service->recurringFee = $upgrade->newRecurringAmount;
                $service->save();
                if (!function_exists("getCartConfigOptions")) {
                    require ROOTDIR . "/includes/configoptionsfunctions.php";
                }
                $configoptions = getCartConfigOptions($upgrade->newValue, "", $upgrade->newCycle);
                foreach ($configoptions as $configoption) {
                    $result = select_query("tblhostingconfigoptions", "COUNT(*)", array("relid" => $relid, "configid" => $configoption["id"]));
                    $data = mysql_fetch_array($result);
                    if (!$data[0]) {
                        insert_query("tblhostingconfigoptions", array("relid" => $relid, "configid" => $configoption["id"], "optionid" => $configoption["selectedvalue"]));
                    }
                }
                $newProduct = $service->product();
                if ($newProduct->stockControlEnabled) {
                    $newProduct->quantityInStock = $newProduct->quantityInStock - 1;
                    $newProduct->save();
                }
                $oldProduct = WHMCS\Product\Product::findOrFail($upgrade->originalValue);
                if ($oldProduct->stockControlEnabled) {
                    $oldProduct->quantityInStock = $oldProduct->quantityInStock + 1;
                    $oldProduct->save();
                }
            } else {
                if ($upgrade->type == "addon") {
                    $addon = WHMCS\Service\Addon::find($upgrade->relid);
                    $addon->nextDueDate = $newNextDueDate;
                    $addon->nextInvoiceDate = $newNextDueDate;
                    $addon->addonId = $upgrade->newValue;
                    $addon->billingCycle = $upgrade->newCycle;
                    $addon->recurringFee = $upgrade->newRecurringAmount;
                    $addon->save();
                }
            }
            cancelUnpaidInvoiceForPreviousPriceAndRegenerateNewInvoiceByServiceId($relid);
            if ($upgrade->type == "service") {
                run_hook("AfterProductUpgrade", array("upgradeid" => $upgradeid));
                run_hook("AfterServiceUpgrade", array("upgradeId" => $upgradeid, "clientId" => $upgrade->userId, "serviceId" => $upgrade->relid));
            } else {
                if ($upgrade->type == "addon") {
                    run_hook("AfterAddonUpgrade", array("upgradeid" => $upgradeid));
                }
            }
        }
    }
    if ($promocode) {
        $result = select_query("tblpromotions", "id,type,recurring,value", array("code" => $promocode));
        $data = mysql_fetch_array($result);
        list($promoid, $promotype, $promorecurring, $promovalue) = $data;
        if ($promorecurring) {
            $recurringamount = recalcRecurringProductPrice($relid);
            if ($promotype == "Percentage") {
                $discount = $recurringamount * $promovalue / 100;
                $recurringamount = $recurringamount - $discount;
            } else {
                $recurringamount = $recurringamount < $promovalue ? "0" : $recurringamount - $promovalue;
            }
            update_query("tblhosting", array("amount" => $recurringamount, "promoid" => $promoid), array("id" => $relid));
        } else {
            update_query("tblhosting", array("promoid" => "0"), array("id" => $relid));
        }
    } else {
        update_query("tblhosting", array("promoid" => "0"), array("id" => $relid));
    }
    if (in_array($type, array(WHMCS\Service\Upgrade\Upgrade::TYPE_PACKAGE, WHMCS\Service\Upgrade\Upgrade::TYPE_CONFIGOPTIONS, WHMCS\Service\Upgrade\Upgrade::TYPE_SERVICE, WHMCS\Service\Upgrade\Upgrade::TYPE_ADDON))) {
        if ($type === WHMCS\Service\Upgrade\Upgrade::TYPE_ADDON) {
            $upgradedService = WHMCS\Service\Addon::findOrFail($relid);
            $serverPackageId = $upgradedService->service->id;
            $serverAddonId = $upgradedService->id;
            $serverType = $upgradedService->productAddon->module;
            $upgradeEmailTemplate = NULL;
            $upgradedServiceDescription = "Addon ID: " . $relid . " - Service ID: " . $serverPackageId;
        } else {
            $upgradedService = WHMCS\Service\Service::findOrFail($relid);
            $serverPackageId = $upgradedService->id;
            $serverAddonId = 0;
            $serverType = $upgradedService->product->module;
            $upgradeEmailTemplate = $upgradedService->product->upgradeEmailTemplate;
            $upgradedServiceDescription = "Service ID: " . $relid;
        }
        $userid = $upgradedService->clientId;
        $manualUpgradeRequired = false;
        if ($serverType) {
            if (!function_exists("getModuleType")) {
                require dirname(__FILE__) . "/modulefunctions.php";
            }
            $result = ServerChangePackage($serverPackageId, $serverAddonId);
            if ($result != "success") {
                if ($result == "Function Not Supported by Module") {
                    $manualUpgradeRequired = true;
                } else {
                    logActivity("Automatic Product/Service Upgrade Failed - " . $upgradedServiceDescription, $userid);
                }
            } else {
                logActivity("Automatic Product/Service Upgrade Successful - " . $upgradedServiceDescription, $userid);
                if ($upgradeEmailTemplate) {
                    sendMessage($upgradeEmailTemplate, $relid);
                }
            }
        } else {
            $manualUpgradeRequired = true;
        }
        if ($manualUpgradeRequired) {
            $emailVars = array("client_id" => $userid, "service_id" => $relid, "order_id" => $orderid, "upgrade_id" => $upgradeid, "upgrade_type" => $type, "upgrade_amount" => $upgradeamount, "increase_recurring_value" => $recurringchange, "promomotion" => $promocode, "package_id" => $serverPackageId, "server_type" => $serverType);
            if ($type == "package") {
                $emailVars["new_package_id"] = $newpackageid;
                $emailVars["new_billing_cycle"] = $newbillingcycle;
                $emailVars["billing_cycle"] = $billingcycle;
            }
            if ($type == "configoptions") {
                $emailVars["config_id"] = $configid;
                $emailVars["option_type"] = $optiontype;
                $emailVars["current_value"] = $tempvalue[1];
                $emailVars["new_value"] = $newvalue;
            }
            sendAdminMessage("Manual Upgrade Required", $emailVars, "account");
            logActivity("Automatic Product/Service Upgrade not possible - " . $upgradedServiceDescription, $userid);
            WHMCS\Database\Capsule::table("tbltodolist")->insert(array("date" => date("Y-m-d"), "title" => "Manual Upgrade Required", "description" => "Manual Upgrade Required for " . $upgradedServiceDescription, "admin" => "", "status" => "Pending", "duedate" => date("Y-m-d")));
        }
    }
    update_query("tblupgrades", array("status" => "Completed"), array("id" => $upgradeid));
}
function validateUpgradePromo($promocode)
{
    global $_LANG;
    $result = select_query("tblpromotions", "", array("code" => $promocode));
    $data = mysql_fetch_array($result);
    $id = $data["id"];
    $recurringtype = $data["type"];
    $recurringvalue = $data["value"];
    $recurring = $data["recurring"];
    $cycles = $data["cycles"];
    $appliesto = $data["appliesto"];
    $requires = $data["requires"];
    $maxuses = $data["maxuses"];
    $uses = $data["uses"];
    $startdate = $data["startdate"];
    $expiredate = $data["expirationdate"];
    $existingclient = $data["existingclient"];
    $onceperclient = $data["onceperclient"];
    $upgrades = $data["upgrades"];
    $upgradeconfig = $data["upgradeconfig"];
    $upgradeconfig = safe_unserialize($upgradeconfig);
    $type = $upgradeconfig["discounttype"];
    $value = $upgradeconfig["value"];
    $configoptions = $upgradeconfig["configoptions"];
    if (!$id) {
        return $_LANG["ordercodenotfound"];
    }
    if (!$upgrades) {
        return $_LANG["promoappliedbutnodiscount"];
    }
    if ($startdate != "0000-00-00") {
        $startdate = str_replace("-", "", $startdate);
        if (date("Ymd") < $startdate) {
            return $_LANG["orderpromoprestart"];
        }
    }
    if ($expiredate != "0000-00-00") {
        $expiredate = str_replace("-", "", $expiredate);
        if ($expiredate < date("Ymd")) {
            return $_LANG["orderpromoexpired"];
        }
    }
    if (0 < $maxuses && $maxuses <= $uses) {
        return $_LANG["orderpromomaxusesreached"];
    }
    if ($onceperclient) {
        $result = select_query("tblorders", "count(*)", array("status" => "Active", "userid" => $_SESSION["uid"], "promocode" => $promocode));
        $orderCount = mysql_fetch_array($result);
        if (0 < $orderCount[0]) {
            return $_LANG["promoonceperclient"];
        }
    }
    $promodesc = $type == "Percentage" ? $value . "%" : formatCurrency($value);
    $promodesc .= " " . $_LANG["orderdiscount"];
    if (!$recurring) {
        $recurringvalue = 0;
        $recurringtype = "";
    }
    $recurringpromodesc = $recurring && 0 < $recurringvalue ? $recurringpromodesc = $recurringtype == "Percentage" ? $recurringvalue . "%" : formatCurrency($recurringvalue) : "";
    $cycles = explode(",", $cycles);
    $appliesto = explode(",", $appliesto);
    $requires = explode(",", $requires);
    return array("id" => $id, "cycles" => $cycles, "appliesto" => $appliesto, "requires" => $requires, "type" => $upgradeconfig["type"], "value" => $upgradeconfig["value"], "discounttype" => $upgradeconfig["discounttype"], "configoptions" => $upgradeconfig["configoptions"], "desc" => $promodesc, "recurringvalue" => $recurringvalue, "recurringtype" => $recurringtype, "recurringdesc" => $recurringpromodesc);
}
function upgradeAlreadyInProgress($hostingId)
{
    $hostingId = (int) $hostingId;
    $hostingSQL = "SELECT tblinvoices.status\n                     FROM tblorders, tblupgrades, tblinvoices\n                    WHERE tblupgrades.relid = '%d'\n                      AND tblorders.id = tblupgrades.orderid\n                      AND tblorders.invoiceid = tblinvoices.id\n                      AND tblinvoices.status = 'Unpaid'";
    $result = full_query(sprintf($hostingSQL, $hostingId));
    $data = mysql_fetch_array($result);
    if ($data[0]) {
        return true;
    }
    return false;
}
function cancelUnpaidInvoiceForPreviousPriceAndRegenerateNewInvoiceByServiceId($serviceId)
{
    $invoiceItems = WHMCS\Database\Capsule::table("tblinvoiceitems")->join("tblinvoices", "tblinvoices.id", "=", "tblinvoiceitems.invoiceid")->where("type", "=", "Hosting")->where("relid", "=", $serviceId)->where(WHMCS\Database\Capsule::raw("tblinvoices.status"), "=", "Unpaid")->orderBy("invoiceid")->get(array("tblinvoiceitems.*"));
    foreach ($invoiceItems as $invoiceItem) {
        $invoiceId = $invoiceItem->invoiceid;
        $userId = $invoiceItem->userid;
        $dueDate = WHMCS\Carbon::createFromFormat("Y-m-d", $invoiceItem->duedate);
        $allInvoiceItems = WHMCS\Database\Capsule::table("tblinvoiceitems")->where("invoiceid", "=", $invoiceId)->whereNotIn("type", array("PromoHosting", "GroupDiscount", "LateFee"))->get();
        $services = $addons = $domains = $items = array();
        foreach ($allInvoiceItems as $singleInvoiceItem) {
            switch ($singleInvoiceItem->type) {
                case "Hosting":
                    $services[] = $singleInvoiceItem->relid;
                    break;
                case "Addon":
                    $addons[] = $singleInvoiceItem->relid;
                    break;
                case "Domain":
                    $domains[] = $singleInvoiceItem->relid;
                    break;
                case "Item":
                    $items[] = $singleInvoiceItem->relid;
                    break;
            }
        }
        WHMCS\Database\Capsule::table("tblinvoiceitems")->where("invoiceid", "=", $invoiceId)->update(array("duedate" => $dueDate->copy()->subDay()->format("Y-m-d")));
        WHMCS\Database\Capsule::table("tblinvoices")->where("id", "=", $invoiceId)->update(array("status" => "Cancelled"));
        logActivity("Cancelled Outstanding Product Renewal Invoice - Invoice ID: " . $invoiceId . " - Service ID: " . $serviceId, $userId);
        run_hook("InvoiceCancelled", array("invoiceid" => $invoiceId));
        if ($services) {
            WHMCS\Database\Capsule::table("tblhosting")->whereIn("id", $services)->update(array("nextinvoicedate" => $dueDate->format("Y-m-d")));
        }
        if ($addons) {
            WHMCS\Database\Capsule::table("tblhostingaddons")->whereIn("id", $addons)->update(array("nextinvoicedate" => $dueDate->format("Y-m-d")));
        }
        if ($domains) {
            WHMCS\Database\Capsule::table("tbldomains")->whereIn("id", $domains)->update(array("nextinvoicedate" => $dueDate->format("Y-m-d")));
        }
        if ($items) {
            WHMCS\Database\Capsule::table("tblbillableitems")->whereIn("id", $items)->decrement("invoicecount", 1, array("duedate" => $dueDate->format("Y-m-d")));
        }
        if (!function_exists("createInvoices")) {
            require_once ROOTDIR . "/includes/processinvoices.php";
        }
        createInvoices($userId);
    }
}

?>