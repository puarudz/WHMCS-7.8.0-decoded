<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function getOrderStatusColour($status)
{
    $statuscolors = array("Active" => "779500", "Pending" => "CC0000", "Fraud" => "000000", "Cancelled" => "888");
    return "<span style=\"color:#" . $statuscolors[$status] . "\">" . $status . "</span>";
}
function getProductInfo($pid)
{
    $result = select_query("tblproducts", "tblproducts.id,tblproducts.name,tblproducts.description,tblproducts.gid,tblproducts.type," . "tblproductgroups.id AS group_id,tblproductgroups.name as group_name, tblproducts.freedomain," . "tblproducts.freedomainpaymentterms,tblproducts.freedomaintlds,tblproducts.stockcontrol,tblproducts.qty", array("tblproducts.id" => $pid), "", "", "", "tblproductgroups ON tblproductgroups.id=tblproducts.gid");
    $data = mysql_fetch_array($result);
    $productinfo = array();
    $productinfo["pid"] = $data["id"];
    $productinfo["gid"] = $data["gid"];
    $productinfo["type"] = $data["type"];
    $productinfo["groupname"] = WHMCS\Product\Group::getGroupName($data["group_id"], $data["group_name"]);
    $productinfo["name"] = WHMCS\Product\Product::getProductName($data["id"], $data["name"]);
    $productinfo["description"] = nl2br(WHMCS\Product\Product::getProductDescription($data["id"]), $data["description"]);
    $productinfo["freedomain"] = $data["freedomain"];
    $productinfo["freedomainpaymentterms"] = explode(",", $data["freedomainpaymentterms"]);
    $productinfo["freedomaintlds"] = explode(",", $data["freedomaintlds"]);
    $productinfo["qty"] = $data["stockcontrol"] ? $data["qty"] : "";
    return $productinfo;
}
function getPricingInfo($pid, $inclconfigops = false, $upgrade = false)
{
    global $CONFIG;
    global $_LANG;
    global $currency;
    $result = select_query("tblproducts", "", array("id" => $pid));
    $data = mysql_fetch_array($result);
    $paytype = $data["paytype"];
    $freedomain = $data["freedomain"];
    $freedomainpaymentterms = $data["freedomainpaymentterms"];
    if (!isset($currency["id"])) {
        $currency = getCurrency();
    }
    $result = select_query("tblpricing", "", array("type" => "product", "currency" => $currency["id"], "relid" => $pid));
    $data = mysql_fetch_array($result);
    $msetupfee = $data["msetupfee"];
    $qsetupfee = $data["qsetupfee"];
    $ssetupfee = $data["ssetupfee"];
    $asetupfee = $data["asetupfee"];
    $bsetupfee = $data["bsetupfee"];
    $tsetupfee = $data["tsetupfee"];
    $monthly = $data["monthly"];
    $quarterly = $data["quarterly"];
    $semiannually = $data["semiannually"];
    $annually = $data["annually"];
    $biennially = $data["biennially"];
    $triennially = $data["triennially"];
    $configoptions = new WHMCS\Product\ConfigOptions();
    $freedomainpaymentterms = explode(",", $freedomainpaymentterms);
    $monthlypricingbreakdown = $CONFIG["ProductMonthlyPricingBreakdown"];
    $minprice = 0;
    $setupFee = 0;
    $mincycle = "";
    $hasconfigoptions = false;
    if ($paytype == "free") {
        $pricing["type"] = $mincycle = "free";
    } else {
        if ($paytype == "onetime") {
            if ($inclconfigops) {
                $msetupfee += $configoptions->getBasePrice($pid, "msetupfee");
                $monthly += $configoptions->getBasePrice($pid, "monthly");
            }
            $minprice = $monthly;
            $setupFee = $msetupfee;
            $pricing["type"] = $mincycle = "onetime";
            $pricing["onetime"] = new WHMCS\View\Formatter\Price($monthly, $currency);
            if ($msetupfee != "0.00") {
                $pricing["onetime"] .= " + " . new WHMCS\View\Formatter\Price($msetupfee, $currency) . " " . $_LANG["ordersetupfee"];
            }
            if (in_array("onetime", $freedomainpaymentterms) && $freedomain && !$upgrade) {
                $pricing["onetime"] .= " (" . $_LANG["orderfreedomainonly"] . ")";
            }
        } else {
            if ($paytype == "recurring") {
                $pricing["type"] = "recurring";
                if (0 <= $monthly) {
                    if ($inclconfigops) {
                        $msetupfee += $configoptions->getBasePrice($pid, "msetupfee");
                        $monthly += $configoptions->getBasePrice($pid, "monthly");
                    }
                    if (!$mincycle) {
                        $minprice = $monthly;
                        $setupFee = $msetupfee;
                        $mincycle = "monthly";
                        $minMonths = 1;
                    }
                    if ($monthlypricingbreakdown) {
                        $pricing["monthly"] = $_LANG["orderpaymentterm1month"] . " - " . new WHMCS\View\Formatter\Price($monthly, $currency);
                    } else {
                        $pricing["monthly"] = new WHMCS\View\Formatter\Price($monthly, $currency) . " " . $_LANG["orderpaymenttermmonthly"];
                    }
                    if ($msetupfee != "0.00") {
                        $pricing["monthly"] .= " + " . new WHMCS\View\Formatter\Price($msetupfee, $currency) . " " . $_LANG["ordersetupfee"];
                    }
                    if (in_array("monthly", $freedomainpaymentterms) && $freedomain && !$upgrade) {
                        $pricing["monthly"] .= " (" . $_LANG["orderfreedomainonly"] . ")";
                    }
                }
                if (0 <= $quarterly) {
                    if ($inclconfigops) {
                        $qsetupfee += $configoptions->getBasePrice($pid, "qsetupfee");
                        $quarterly += $configoptions->getBasePrice($pid, "quarterly");
                    }
                    if (!$mincycle) {
                        $minprice = $monthlypricingbreakdown ? $quarterly / 3 : $quarterly;
                        $setupFee = $qsetupfee;
                        $mincycle = "quarterly";
                        $minMonths = 3;
                    }
                    if ($monthlypricingbreakdown) {
                        $pricing["quarterly"] = $_LANG["orderpaymentterm3month"] . " - " . new WHMCS\View\Formatter\Price($quarterly / 3, $currency);
                    } else {
                        $pricing["quarterly"] = new WHMCS\View\Formatter\Price($quarterly, $currency) . " " . $_LANG["orderpaymenttermquarterly"];
                    }
                    if ($qsetupfee != "0.00") {
                        $pricing["quarterly"] .= " + " . new WHMCS\View\Formatter\Price($qsetupfee, $currency) . " " . $_LANG["ordersetupfee"];
                    }
                    if (in_array("quarterly", $freedomainpaymentterms) && $freedomain && !$upgrade) {
                        $pricing["quarterly"] .= " (" . $_LANG["orderfreedomainonly"] . ")";
                    }
                }
                if (0 <= $semiannually) {
                    if ($inclconfigops) {
                        $ssetupfee += $configoptions->getBasePrice($pid, "ssetupfee");
                        $semiannually += $configoptions->getBasePrice($pid, "semiannually");
                    }
                    if (!$mincycle) {
                        $minprice = $monthlypricingbreakdown ? $semiannually / 6 : $semiannually;
                        $setupFee = $ssetupfee;
                        $mincycle = "semiannually";
                        $minMonths = 6;
                    }
                    if ($monthlypricingbreakdown) {
                        $pricing["semiannually"] = $_LANG["orderpaymentterm6month"] . " - " . new WHMCS\View\Formatter\Price($semiannually / 6, $currency);
                    } else {
                        $pricing["semiannually"] = new WHMCS\View\Formatter\Price($semiannually, $currency) . " " . $_LANG["orderpaymenttermsemiannually"];
                    }
                    if ($ssetupfee != "0.00") {
                        $pricing["semiannually"] .= " + " . new WHMCS\View\Formatter\Price($ssetupfee, $currency) . " " . $_LANG["ordersetupfee"];
                    }
                    if (in_array("semiannually", $freedomainpaymentterms) && $freedomain && !$upgrade) {
                        $pricing["semiannually"] .= " (" . $_LANG["orderfreedomainonly"] . ")";
                    }
                }
                if (0 <= $annually) {
                    if ($inclconfigops) {
                        $asetupfee += $configoptions->getBasePrice($pid, "asetupfee");
                        $annually += $configoptions->getBasePrice($pid, "annually");
                    }
                    if (!$mincycle) {
                        $minprice = $monthlypricingbreakdown ? $annually / 12 : $annually;
                        $setupFee = $asetupfee;
                        $mincycle = "annually";
                        $minMonths = 12;
                    }
                    if ($monthlypricingbreakdown) {
                        $pricing["annually"] = $_LANG["orderpaymentterm12month"] . " - " . new WHMCS\View\Formatter\Price($annually / 12, $currency);
                    } else {
                        $pricing["annually"] = new WHMCS\View\Formatter\Price($annually, $currency) . " " . $_LANG["orderpaymenttermannually"];
                    }
                    if ($asetupfee != "0.00") {
                        $pricing["annually"] .= " + " . new WHMCS\View\Formatter\Price($asetupfee, $currency) . " " . $_LANG["ordersetupfee"];
                    }
                    if (in_array("annually", $freedomainpaymentterms) && $freedomain && !$upgrade) {
                        $pricing["annually"] .= " (" . $_LANG["orderfreedomainonly"] . ")";
                    }
                }
                if (0 <= $biennially) {
                    if ($inclconfigops) {
                        $bsetupfee += $configoptions->getBasePrice($pid, "bsetupfee");
                        $biennially += $configoptions->getBasePrice($pid, "biennially");
                    }
                    if (!$mincycle) {
                        $minprice = $monthlypricingbreakdown ? $biennially / 24 : $biennially;
                        $setupFee = $bsetupfee;
                        $mincycle = "biennially";
                        $minMonths = 24;
                    }
                    if ($monthlypricingbreakdown) {
                        $pricing["biennially"] = $_LANG["orderpaymentterm24month"] . " - " . new WHMCS\View\Formatter\Price($biennially / 24, $currency);
                    } else {
                        $pricing["biennially"] = new WHMCS\View\Formatter\Price($biennially, $currency) . " " . $_LANG["orderpaymenttermbiennially"];
                    }
                    if ($bsetupfee != "0.00") {
                        $pricing["biennially"] .= " + " . new WHMCS\View\Formatter\Price($bsetupfee, $currency) . " " . $_LANG["ordersetupfee"];
                    }
                    if (in_array("biennially", $freedomainpaymentterms) && $freedomain && !$upgrade) {
                        $pricing["biennially"] .= " (" . $_LANG["orderfreedomainonly"] . ")";
                    }
                }
                if (0 <= $triennially) {
                    if ($inclconfigops) {
                        $tsetupfee += $configoptions->getBasePrice($pid, "tsetupfee");
                        $triennially += $configoptions->getBasePrice($pid, "triennially");
                    }
                    if (!$mincycle) {
                        $minprice = $monthlypricingbreakdown ? $triennially / 36 : $triennially;
                        $setupFee = $tsetupfee;
                        $mincycle = "triennially";
                        $minMonths = 36;
                    }
                    if ($monthlypricingbreakdown) {
                        $pricing["triennially"] = $_LANG["orderpaymentterm36month"] . " - " . new WHMCS\View\Formatter\Price($triennially / 36, $currency);
                    } else {
                        $pricing["triennially"] = new WHMCS\View\Formatter\Price($triennially, $currency) . " " . $_LANG["orderpaymenttermtriennially"];
                    }
                    if ($tsetupfee != "0.00") {
                        $pricing["triennially"] .= " + " . new WHMCS\View\Formatter\Price($tsetupfee, $currency) . " " . $_LANG["ordersetupfee"];
                    }
                    if (in_array("triennially", $freedomainpaymentterms) && $freedomain && !$upgrade) {
                        $pricing["triennially"] .= " (" . $_LANG["orderfreedomainonly"] . ")";
                    }
                }
            }
        }
    }
    $pricing["hasconfigoptions"] = $configoptions->hasConfigOptions($pid);
    if (isset($pricing["onetime"])) {
        $pricing["cycles"]["onetime"] = $pricing["onetime"];
    }
    if (isset($pricing["monthly"])) {
        $pricing["cycles"]["monthly"] = $pricing["monthly"];
    }
    if (isset($pricing["quarterly"])) {
        $pricing["cycles"]["quarterly"] = $pricing["quarterly"];
    }
    if (isset($pricing["semiannually"])) {
        $pricing["cycles"]["semiannually"] = $pricing["semiannually"];
    }
    if (isset($pricing["annually"])) {
        $pricing["cycles"]["annually"] = $pricing["annually"];
    }
    if (isset($pricing["biennially"])) {
        $pricing["cycles"]["biennially"] = $pricing["biennially"];
    }
    if (isset($pricing["triennially"])) {
        $pricing["cycles"]["triennially"] = $pricing["triennially"];
    }
    $pricing["rawpricing"] = array("msetupfee" => format_as_currency($msetupfee), "qsetupfee" => format_as_currency($qsetupfee), "ssetupfee" => format_as_currency($ssetupfee), "asetupfee" => format_as_currency($asetupfee), "bsetupfee" => format_as_currency($bsetupfee), "tsetupfee" => format_as_currency($tsetupfee), "monthly" => format_as_currency($monthly), "quarterly" => format_as_currency($quarterly), "semiannually" => format_as_currency($semiannually), "annually" => format_as_currency($annually), "biennially" => format_as_currency($biennially), "triennially" => format_as_currency($triennially));
    $pricing["minprice"] = array("price" => new WHMCS\View\Formatter\Price($minprice, $currency), "setupFee" => 0 < $setupFee ? new WHMCS\View\Formatter\Price($setupFee, $currency) : 0, "cycle" => $monthlypricingbreakdown && $paytype == "recurring" ? "monthly" : $mincycle, "simple" => (new WHMCS\View\Formatter\Price($minprice, $currency))->toPrefixed());
    if (isset($minMonths)) {
        switch ($minMonths) {
            case 3:
                $langVar = "shoppingCartProductPerMonth";
                $count = "3 ";
                break;
            case 6:
                $langVar = "shoppingCartProductPerMonth";
                $count = "6 ";
                break;
            case 12:
                $langVar = $monthlypricingbreakdown ? "shoppingCartProductPerMonth" : "shoppingCartProductPerYear";
                $count = "";
                break;
            case 24:
                $langVar = $monthlypricingbreakdown ? "shoppingCartProductPerMonth" : "shoppingCartProductPerYear";
                $count = "2 ";
                break;
            case 36:
                $langVar = $monthlypricingbreakdown ? "shoppingCartProductPerMonth" : "shoppingCartProductPerYear";
                $count = "3 ";
                break;
            default:
                $langVar = "shoppingCartProductPerMonth";
                $count = "";
        }
        $pricing["minprice"]["cycleText"] = Lang::trans($langVar, array(":count" => $count, ":price" => $pricing["minprice"]["simple"]));
        $pricing["minprice"]["cycleTextWithCurrency"] = Lang::trans($langVar, array(":count" => $count, ":price" => $pricing["minprice"]["price"]));
    }
    return $pricing;
}
function calcCartTotals($checkout = false, $ignorenoconfig = false, array $currency = array())
{
    global $CONFIG;
    global $_LANG;
    global $remote_ip;
    global $promo_data;
    $whmcs = WHMCS\Application::getInstance();
    if (!function_exists("bundlesGetProductPriceOverride")) {
        require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "cartfunctions.php";
    }
    if (!function_exists("getClientsDetails")) {
        require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "clientfunctions.php";
    }
    if (!function_exists("getCartConfigOptions")) {
        require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "configoptionsfunctions.php";
    }
    if (!function_exists("getTLDPriceList")) {
        require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "domainfunctions.php";
    }
    if (!function_exists("getTaxRate")) {
        require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "invoicefunctions.php";
    }
    $isAdmin = false;
    if (defined("ADMINAREA") || defined("APICALL") || DI::make("runtimeStorage")->runningViaLocalApi === true) {
        $isAdmin = true;
    }
    if (!$currency) {
        $userId = WHMCS\Session::get("uid");
        $currencyId = WHMCS\Session::get("currency");
        $currency = getCurrency($userId, $currencyId);
    }
    $orderForm = new WHMCS\OrderForm();
    $cart_total = $cart_discount = 0;
    $cart_tax = array();
    $recurring_tax = array();
    run_hook("PreCalculateCartTotals", $orderForm->getCartData());
    if (!$ignorenoconfig) {
        if ($orderForm->getCartDataByKey("products")) {
            foreach ($orderForm->getCartDataByKey("products") as $key => $productdata) {
                if (isset($productdata["noconfig"]) && $productdata["noconfig"]) {
                    unset($_SESSION["cart"]["products"][$key]);
                }
            }
        }
        $bundlewarnings = bundlesValidateCheckout();
        if ($orderForm->getCartDataByKey("products")) {
            $_SESSION["cart"]["products"] = array_values($_SESSION["cart"]["products"]);
        }
    }
    if ($checkout) {
        if (!$_SESSION["cart"]) {
            return false;
        }
        run_hook("PreShoppingCartCheckout", $_SESSION["cart"]);
        $ordernumhooks = run_hook("OverrideOrderNumberGeneration", $_SESSION["cart"]);
        $order_number = "";
        if (count($ordernumhooks)) {
            foreach ($ordernumhooks as $ordernumhookval) {
                if (is_numeric($ordernumhookval)) {
                    $order_number = $ordernumhookval;
                }
            }
        }
        if (!$order_number) {
            $order_number = generateUniqueID();
        }
        $paymentmethod = $_SESSION["cart"]["paymentmethod"];
        if (isset($_SESSION["adminid"])) {
            $gateways = new WHMCS\Gateways();
            if (!$gateways->isActiveGateway($paymentmethod)) {
                $paymentmethod = $gateways->getFirstAvailableGateway();
            }
        } else {
            $availablegateways = getAvailableOrderPaymentGateways();
            if (!array_key_exists($paymentmethod, $availablegateways)) {
                foreach ($availablegateways as $k => $v) {
                    $paymentmethod = $k;
                    break;
                }
            }
        }
        $userid = $_SESSION["uid"];
        $ordernotes = "";
        if ($_SESSION["cart"]["notes"] && $_SESSION["cart"]["notes"] != $_LANG["ordernotesdescription"]) {
            $ordernotes = $_SESSION["cart"]["notes"];
        }
        if ($orderForm->getNumItemsInCart() <= 0) {
            return false;
        }
        $orderid = insert_query("tblorders", array("ordernum" => $order_number, "userid" => $userid, "contactid" => $_SESSION["cart"]["contact"], "date" => "now()", "status" => "Pending", "paymentmethod" => $paymentmethod, "ipaddress" => $remote_ip, "notes" => $ordernotes));
        logActivity("New Order Placed - Order ID: " . $orderid . " - User ID: " . $userid);
        $domaineppcodes = array();
    }
    $promotioncode = $orderForm->getCartDataByKey("promo");
    if ($promotioncode) {
        $result = select_query("tblpromotions", "", array("code" => $promotioncode));
        $promo_data = mysql_fetch_array($result);
    }
    if (!isset($_SESSION["uid"])) {
        if (!$_SESSION["cart"]["user"]["country"]) {
            $_SESSION["cart"]["user"]["country"] = $CONFIG["DefaultCountry"];
        }
        $state = $_SESSION["cart"]["user"]["state"];
        $country = $_SESSION["cart"]["user"]["country"];
    } else {
        $clientsdetails = getClientsDetails($_SESSION["uid"]);
        $state = $clientsdetails["state"];
        $country = $clientsdetails["country"];
    }
    $taxCalculator = new WHMCS\Billing\Tax();
    $taxCalculator->setIsInclusive($CONFIG["TaxType"] == "Inclusive")->setIsCompound($CONFIG["TaxL2Compound"]);
    if ($CONFIG["TaxEnabled"]) {
        $taxdata = getTaxRate(1, $state, $country);
        $taxname = $taxdata["name"];
        $taxrate = $taxdata["rate"];
        $rawtaxrate = $taxrate;
        $inctaxrate = $taxrate / 100 + 1;
        $taxrate /= 100;
        $taxCalculator->setLevel1Percentage($taxdata["rate"]);
        $taxdata = getTaxRate(2, $state, $country);
        $taxname2 = $taxdata["name"];
        $taxrate2 = $taxdata["rate"];
        $rawtaxrate2 = $taxrate2;
        $inctaxrate2 = $taxrate2 / 100 + 1;
        $taxrate2 /= 100;
        $taxCalculator->setLevel2Percentage($taxdata["rate"]);
    }
    if (WHMCS\Config\Setting::getValue("TaxEnabled") && WHMCS\Config\Setting::getValue("TaxInclusiveDeduct") && WHMCS\Config\Setting::getValue("TaxType") == "Inclusive" && (!$taxrate && !$taxrate2 || $clientsdetails["taxexempt"])) {
        $systemFirstTaxRate = WHMCS\Database\Capsule::table("tbltax")->value("taxrate");
        if ($systemFirstTaxRate) {
            $excltaxrate = 1 + $systemFirstTaxRate / 100;
        } else {
            $excltaxrate = 1;
        }
    } else {
        $excltaxrate = 1;
    }
    $cartdata = $productsarray = $tempdomains = $orderproductids = $orderdomainids = $orderaddonids = $orderrenewalids = $freedomains = array();
    $recurring_cycles_total = array("monthly" => 0, "quarterly" => 0, "semiannually" => 0, "annually" => 0, "biennially" => 0, "triennially" => 0);
    $cartProducts = $orderForm->getCartDataByKey("products");
    if (is_array($cartProducts)) {
        $productRemovedFromCart = false;
        $one_time_discount_applied = false;
        foreach ($cartProducts as $key => $productdata) {
            $data = get_query_vals("tblproducts", "tblproducts.*, tblproductgroups.name AS groupname", array("tblproducts.id" => $productdata["pid"]), "", "", "", "tblproductgroups ON tblproductgroups.id=tblproducts.gid");
            $pid = $data["id"];
            $gid = $data["gid"];
            $groupname = $isAdmin && !$checkout ? $data["groupname"] : WHMCS\Product\Group::getGroupName($gid, $data["groupname"]);
            $productname = $isAdmin && !$checkout ? $data["name"] : WHMCS\Product\Product::getProductName($pid, $data["name"]);
            $paytype = $data["paytype"];
            $allowqty = $data["allowqty"];
            $proratabilling = $data["proratabilling"];
            $proratadate = $data["proratadate"];
            $proratachargenextmonth = $data["proratachargenextmonth"];
            $tax = $data["tax"];
            $servertype = $data["servertype"];
            $servergroup = $data["servergroup"];
            $stockcontrol = $data["stockcontrol"];
            $qty = isset($productdata["qty"]) ? $productdata["qty"] : 1;
            if (!$allowqty || !$qty) {
                $qty = 1;
            }
            $productdata["allowqty"] = $allowqty;
            if ($stockcontrol) {
                $quantityAvailable = (int) $data["qty"];
                if (!defined("ADMINAREA")) {
                    if ($quantityAvailable <= 0) {
                        unset($_SESSION["cart"]["products"][$key]);
                        $productRemovedFromCart = true;
                        continue;
                    }
                    if ($quantityAvailable < $qty) {
                        $qty = $quantityAvailable;
                    }
                }
            }
            $productdata["qty"] = $qty;
            $freedomain = $data["freedomain"];
            if ($freedomain) {
                $freedomainpaymentterms = $data["freedomainpaymentterms"];
                $freedomaintlds = $data["freedomaintlds"];
                $freedomainpaymentterms = explode(",", $freedomainpaymentterms);
                $freedomaintlds = explode(",", $freedomaintlds);
            } else {
                $freedomainpaymentterms = $freedomaintlds = array();
            }
            $productinfo = getproductinfo($pid);
            if (array_key_exists("sslCompetitiveUpgrade", $productdata) && $productdata["sslCompetitiveUpgrade"]) {
                $productinfo["name"] .= "<br><small>" . Lang::trans("store.ssl.competitiveUpgradeQualified") . "</small>";
            }
            $productdata["productinfo"] = $productinfo;
            if (!function_exists("getCustomFields")) {
                require ROOTDIR . "/includes/customfieldfunctions.php";
            }
            $customfields = getCustomFields("product", $pid, "", $isAdmin, "", $productdata["customfields"]);
            $productdata["customfields"] = $customfields;
            $pricing = getpricinginfo($pid);
            if ($paytype != "free") {
                $prod = new WHMCS\Pricing();
                $prod->loadPricing("product", $pid);
                if (!$prod->hasBillingCyclesAvailable()) {
                    unset($_SESSION["cart"]["products"][$key]);
                    continue;
                }
            }
            if ($pricing["type"] == "recurring") {
                $billingcycle = strtolower($productdata["billingcycle"]);
                if (!in_array($billingcycle, array("monthly", "quarterly", "semiannually", "annually", "biennially", "triennially"))) {
                    $billingcycle = "";
                }
                if ($pricing["rawpricing"][$billingcycle] < 0) {
                    $billingcycle = "";
                }
                if (!$billingcycle) {
                    if (0 <= $pricing["rawpricing"]["monthly"]) {
                        $billingcycle = "monthly";
                    } else {
                        if (0 <= $pricing["rawpricing"]["quarterly"]) {
                            $billingcycle = "quarterly";
                        } else {
                            if (0 <= $pricing["rawpricing"]["semiannually"]) {
                                $billingcycle = "semiannually";
                            } else {
                                if (0 <= $pricing["rawpricing"]["annually"]) {
                                    $billingcycle = "annually";
                                } else {
                                    if (0 <= $pricing["rawpricing"]["biennially"]) {
                                        $billingcycle = "biennially";
                                    } else {
                                        if (0 <= $pricing["rawpricing"]["triennially"]) {
                                            $billingcycle = "triennially";
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                if ($pricing["type"] == "onetime") {
                    $billingcycle = "onetime";
                } else {
                    $billingcycle = "free";
                }
            }
            $productdata["billingcycle"] = $billingcycle;
            $productdata["billingcyclefriendly"] = Lang::trans("orderpaymentterm" . $billingcycle);
            if ($billingcycle == "free") {
                $product_setup = $product_onetime = $product_recurring = "0";
                $databasecycle = "Free Account";
            } else {
                if ($billingcycle == "onetime") {
                    $product_setup = $pricing["rawpricing"]["msetupfee"];
                    $product_onetime = $pricing["rawpricing"]["monthly"];
                    $product_recurring = 0;
                    $databasecycle = "One Time";
                } else {
                    $product_setup = $pricing["rawpricing"][substr($billingcycle, 0, 1) . "setupfee"];
                    $product_onetime = $product_recurring = $pricing["rawpricing"][$billingcycle];
                    $databasecycle = ucfirst($billingcycle);
                    if ($databasecycle == "Semiannually") {
                        $databasecycle = "Semi-Annually";
                    }
                }
            }
            if ($product_setup < 0) {
                $product_setup = 0;
            }
            $before_priceoverride_value = "";
            if ($bundleoverride = bundlesGetProductPriceOverride("product", $key)) {
                $before_priceoverride_value = $product_setup + $product_onetime;
                $product_setup = 0;
                $product_onetime = $product_recurring = $bundleoverride;
            }
            $hookret = run_hook("OrderProductPricingOverride", array("key" => $key, "pid" => $pid, "proddata" => $productdata));
            foreach ($hookret as $hookret2) {
                if (is_array($hookret2)) {
                    if ($hookret2["setup"]) {
                        $product_setup = $hookret2["setup"];
                    }
                    if ($hookret2["recurring"]) {
                        $product_onetime = $product_recurring = $hookret2["recurring"];
                    }
                }
            }
            $productdata["pricing"]["baseprice"] = new WHMCS\View\Formatter\Price($product_onetime, $currency);
            $configoptionsdb = array();
            $configurableoptions = getCartConfigOptions($pid, $productdata["configoptions"], $billingcycle, "", "", true);
            $configoptions = array();
            if ($configurableoptions) {
                foreach ($configurableoptions as $confkey => $value) {
                    if (!$value["hidden"] || defined("ADMINAREA") || defined("APICALL")) {
                        $configoptions[] = array("name" => $value["optionname"], "type" => $value["optiontype"], "option" => $value["selectedoption"], "optionname" => $value["selectedname"], "setup" => 0 < $value["selectedsetup"] ? new WHMCS\View\Formatter\Price($value["selectedsetup"], $currency) : "", "recurring" => new WHMCS\View\Formatter\Price($value["selectedrecurring"], $currency), "qty" => $value["selectedqty"]);
                        $product_setup += $value["selectedsetup"];
                        $product_onetime += $value["selectedrecurring"];
                        if (strlen($before_priceoverride_value)) {
                            $before_priceoverride_value += $value["selectedrecurring"];
                        }
                        if ($billingcycle != "onetime") {
                            $product_recurring += $value["selectedrecurring"];
                        }
                    }
                    $configoptionsdb[$value["id"]] = array("value" => $value["selectedvalue"], "qty" => $value["selectedqty"]);
                }
            }
            $productdata["configoptions"] = $configoptions;
            if (in_array($billingcycle, $freedomainpaymentterms)) {
                $domain = $productdata["domain"];
                $domainparts = explode(".", $domain, 2);
                $tld = "." . $domainparts[1];
                if (in_array($tld, $freedomaintlds)) {
                    $freedomains[$domain] = $freedomain;
                }
            }
            if ($proratabilling) {
                $proratavalues = getProrataValues($billingcycle, $product_onetime, $proratadate, $proratachargenextmonth, date("d"), date("m"), date("Y"), $_SESSION["uid"]);
                $product_onetime = $proratavalues["amount"];
                $productdata["proratadate"] = fromMySQLDate($proratavalues["date"]);
            }
            if (WHMCS\Config\Setting::getValue("TaxEnabled") && WHMCS\Config\Setting::getValue("TaxInclusiveDeduct")) {
                $product_setup = format_as_currency($product_setup / $excltaxrate);
                $product_onetime = format_as_currency($product_onetime / $excltaxrate);
                $product_recurring = format_as_currency($product_recurring / $excltaxrate);
            }
            $product_total_today_db = $product_setup + $product_onetime;
            $product_recurring_db = $product_recurring;
            $productdata["pricing"]["setup"] = $product_setup * $qty;
            $productdata["pricing"]["recurring"][$billingcycle] = $product_recurring * $qty;
            $productdata["pricing"]["totaltoday"] = $product_total_today_db * $qty;
            $productdata["pricing"]["productonlysetup"] = $productdata["pricing"]["setup"];
            $productdata["pricing"]["totaltodayexcltax"] = $productdata["pricing"]["totaltoday"];
            $productdata["pricing"]["totalTodayExcludingTaxSetup"] = $product_onetime * $qty;
            if ($product_onetime == 0 && $product_recurring == 0) {
                $pricing_text = $_LANG["orderfree"];
            } else {
                $pricing_text = "";
                if (strlen($before_priceoverride_value)) {
                    $pricing_text .= "<strike>" . new WHMCS\View\Formatter\Price($before_priceoverride_value, $currency) . "</strike> ";
                }
                $pricing_text .= new WHMCS\View\Formatter\Price($product_onetime, $currency);
                if (0 < $product_setup) {
                    $pricing_text .= " + " . new WHMCS\View\Formatter\Price($product_setup, $currency) . " " . $_LANG["ordersetupfee"];
                }
                if ($allowqty && 1 < $qty) {
                    $pricing_text .= $_LANG["invoiceqtyeach"] . "<br />" . $_LANG["invoicestotal"] . ": " . new WHMCS\View\Formatter\Price($productdata["pricing"]["totaltoday"], $currency);
                }
            }
            $productdata["pricingtext"] = $pricing_text;
            if (isset($productdata["priceoverride"])) {
                $product_total_today_db = $product_recurring_db = $product_onetime = $productdata["priceoverride"];
                $product_setup = 0;
            }
            $applyTaxToCart = $CONFIG["TaxEnabled"] && $tax && !$clientsdetails["taxexempt"];
            if ($applyTaxToCart) {
                $cart_tax = array_merge($cart_tax, array_fill(0, $qty, $product_total_today_db));
                if (!isset($recurring_tax[$billingcycle])) {
                    $recurring_tax[$billingcycle] = array();
                }
                $recurring_tax[$billingcycle] = array_merge($recurring_tax[$billingcycle], array_fill(0, $qty, $product_recurring_db));
            }
            $firstqtydiscountonly = false;
            if ($promotioncode) {
                $onetimediscount = $recurringdiscount = $promoid = $firstqtydiscountedamtonetime = $firstqtydiscountedamtrecurring = 0;
                if ($promocalc = CalcPromoDiscount($pid, $databasecycle, $product_total_today_db, $product_recurring_db, $product_setup)) {
                    $onetimediscount = $promocalc["onetimediscount"];
                    $recurringdiscount = $promocalc["recurringdiscount"];
                    $product_total_today_db -= $onetimediscount;
                    $product_recurring_db -= $recurringdiscount;
                    if (1 < $qty) {
                        $applyonce = $promocalc["applyonce"];
                        if ($applyonce) {
                            $cart_discount += $onetimediscount;
                            $firstqtydiscountonly = true;
                            $firstqtydiscountedamtonetime = $product_total_today_db;
                            $firstqtydiscountedamtrecurring = $product_recurring_db;
                            $product_total_today_db += $onetimediscount;
                            $product_recurring_db += $recurringdiscount;
                        } else {
                            $cart_discount += $onetimediscount * $qty;
                        }
                    } else {
                        $cart_discount += $onetimediscount;
                    }
                    if ($applyTaxToCart) {
                        $discount_quantity = $firstqtydiscountonly ? 1 : $qty;
                        if ($onetimediscount != 0) {
                            $cart_tax = array_merge($cart_tax, array_fill(0, $discount_quantity, 0 - $onetimediscount));
                        }
                        if ($recurringdiscount != 0) {
                            $recurring_tax[$billingcycle] = array_merge($recurring_tax[$billingcycle], array_fill(0, $discount_quantity, 0 - $recurringdiscount));
                        }
                    }
                    $promoid = $promo_data["id"];
                }
            }
            $cart_total += $product_total_today_db * $qty;
            $product_total_qty_recurring = $product_recurring_db * $qty;
            if ($firstqtydiscountonly) {
                $cart_total = $cart_total - $product_total_today_db + $firstqtydiscountedamtonetime;
                $product_total_qty_recurring = $product_total_qty_recurring - $product_recurring_db + $firstqtydiscountedamtrecurring;
            }
            if (!isset($recurring_cycles_total[$billingcycle])) {
                $recurring_cycles_total[$billingcycle] = 0;
            }
            $recurring_cycles_total[$billingcycle] += $product_total_qty_recurring;
            $domain = $productdata["domain"];
            $serverhostname = isset($productdata["server"]["hostname"]) ? $productdata["server"]["hostname"] : "";
            $serverns1prefix = isset($productdata["server"]["ns1prefix"]) ? $productdata["server"]["ns1prefix"] : "";
            $serverns2prefix = isset($productdata["server"]["ns2prefix"]) ? $productdata["server"]["ns2prefix"] : "";
            $serverrootpw = isset($productdata["server"]["rootpw"]) ? encrypt($productdata["server"]["rootpw"]) : "";
            if ($serverns1prefix && $domain) {
                $serverns1prefix = $serverns1prefix . "." . $domain;
            }
            if ($serverns2prefix && $domain) {
                $serverns2prefix = $serverns2prefix . "." . $domain;
            }
            if ($serverhostname) {
                $serverhostname = trim($serverhostname, " .");
                if (1 < substr_count($serverhostname, ".") || !$domain) {
                    $domain = $serverhostname;
                } else {
                    $domain = $serverhostname . "." . $domain;
                }
            }
            $productdata["domain"] = $domain;
            $userid = (int) WHMCS\Session::get("uid");
            if ($checkout) {
                $multiqtyids = array();
                for ($qtycount = 1; $qtycount <= $qty; $qtycount++) {
                    if ($firstqtydiscountonly) {
                        if ($one_time_discount_applied) {
                            $promoid = 0;
                        } else {
                            $one_time_discount_applied = true;
                        }
                    }
                    $serverid = $servertype ? getServerID($servertype, $servergroup) : "0";
                    $hostingquerydates = $databasecycle == "Free Account" ? "0000-00-00" : date("Y-m-d");
                    $firstpaymentamount = $firstqtydiscountonly && $qtycount == 1 ? $firstqtydiscountedamtonetime : $product_total_today_db;
                    $recurringamount = $firstqtydiscountonly && $qtycount == 1 ? $firstqtydiscountedamtrecurring : $product_recurring_db;
                    $serviceid = insert_query("tblhosting", array("userid" => $userid, "orderid" => $orderid, "packageid" => $pid, "server" => $serverid, "regdate" => "now()", "domain" => $domain, "paymentmethod" => $paymentmethod, "firstpaymentamount" => $firstpaymentamount, "amount" => $recurringamount, "billingcycle" => $databasecycle, "nextduedate" => $hostingquerydates, "nextinvoicedate" => $hostingquerydates, "domainstatus" => "Pending", "ns1" => $serverns1prefix, "ns2" => $serverns2prefix, "password" => $serverrootpw, "promoid" => $promoid));
                    $multiqtyids[$qtycount] = $serviceid;
                    $orderproductids[] = $serviceid;
                    if ($stockcontrol) {
                        full_query("UPDATE tblproducts SET qty=qty-1 WHERE id=" . (int) $pid);
                    }
                    if ($configoptionsdb) {
                        foreach ($configoptionsdb as $confOptionsKey => $value) {
                            insert_query("tblhostingconfigoptions", array("relid" => $serviceid, "configid" => $confOptionsKey, "optionid" => $value["value"], "qty" => $value["qty"]));
                        }
                    }
                    foreach ($productdata["customfields"] as $value) {
                        if (!function_exists("saveCustomFields")) {
                            require_once ROOTDIR . "/includes/customfieldfunctions.php";
                        }
                        saveCustomFields($serviceid, array($value["id"] => $value["rawvalue"]), "product", $isAdmin);
                    }
                    $productdetails = getInvoiceProductDetails($serviceid, $pid, date("Y-m-d"), $hostingquerydates, $databasecycle, $domain, $userid);
                    $invoice_description = $productdetails["description"];
                    if (array_key_exists("sslCompetitiveUpgrade", $productdata) && $productdata["sslCompetitiveUpgrade"]) {
                        $invoice_description .= "\n" . Lang::trans("store.ssl.competitiveUpgradeQualified");
                    }
                    $invoice_tax = $productdetails["tax"];
                    if (!$_SESSION["cart"]["geninvoicedisabled"]) {
                        $prodinvoicearray = array();
                        $prodinvoicearray["userid"] = $userid;
                        $prodinvoicearray["type"] = "Hosting";
                        $prodinvoicearray["relid"] = $serviceid;
                        $prodinvoicearray["taxed"] = $invoice_tax;
                        $prodinvoicearray["duedate"] = $hostingquerydates;
                        $prodinvoicearray["paymentmethod"] = $paymentmethod;
                        $promo_total_today = $product_total_today_db;
                        if ($firstqtydiscountonly && 1 < $qty) {
                            $promo_total_today -= $onetimediscount;
                        }
                        if (0 < $product_setup) {
                            $prodinvoicesetuparray = $prodinvoicearray;
                            $prodinvoicesetuparray["description"] = $productname . " " . $_LANG["ordersetupfee"];
                            $prodinvoicesetuparray["amount"] = $product_setup;
                            $prodinvoicesetuparray["type"] = "Setup";
                            insert_query("tblinvoiceitems", $prodinvoicesetuparray);
                        }
                        if ($billingcycle != "free" && 0 <= $product_onetime) {
                            $prodinvoicearray["description"] = $invoice_description;
                            $prodinvoicearray["amount"] = $product_onetime;
                            insert_query("tblinvoiceitems", $prodinvoicearray);
                        }
                        $promovals = getInvoiceProductPromo($promo_total_today, $promoid, $userid, $serviceid, $product_setup + $product_onetime);
                        if ($promovals["description"]) {
                            $prodinvoicepromoarray = $prodinvoicearray;
                            $prodinvoicepromoarray["type"] = "PromoHosting";
                            $prodinvoicepromoarray["description"] = $promovals["description"];
                            $prodinvoicepromoarray["amount"] = $promovals["amount"];
                            insert_query("tblinvoiceitems", $prodinvoicepromoarray);
                        }
                    }
                    $adminemailitems = $_LANG["orderproduct"] . ": " . $groupname . " - " . $productname . "<br>\n";
                    if ($domain) {
                        $adminemailitems .= $_LANG["orderdomain"] . ": " . $domain . "<br>\n";
                    }
                    foreach ($configurableoptions as $confkey => $value) {
                        if (!$value["hidden"]) {
                            $adminemailitems .= $value["optionname"] . ": " . $value["selectedname"] . "<br />\n";
                        }
                    }
                    foreach ($customfields as $customfield) {
                        if (!$customfield["adminonly"]) {
                            $adminemailitems .= (string) $customfield["name"] . ": " . $customfield["value"] . "<br />\n";
                        }
                    }
                    $adminemailitems .= $_LANG["firstpaymentamount"] . ": " . new WHMCS\View\Formatter\Price($product_total_today_db, $currency) . "<br>\n";
                    if ($product_recurring_db) {
                        $adminemailitems .= $_LANG["recurringamount"] . ": " . new WHMCS\View\Formatter\Price($product_recurring_db, $currency) . "<br>\n";
                    }
                    $adminemailitems .= $_LANG["orderbillingcycle"] . ": " . $_LANG["orderpaymentterm" . str_replace(array("-", " "), "", strtolower($databasecycle))] . "<br>\n";
                    if ($allowqty && 1 < $qty) {
                        $adminemailitems .= $_LANG["quantity"] . ": " . $qty . "<br>\n" . $_LANG["invoicestotal"] . ": " . $productdata["pricing"]["totaltoday"] . "<br>\n";
                    }
                    $adminemailitems .= "<br>\n";
                }
            }
            $addonsarray = array();
            $addons = $productdata["addons"];
            if ($addons) {
                foreach ($addons as $addonid) {
                    $result = select_query("tbladdons", "name,description,billingcycle,tax,module,server_group_id", array("id" => $addonid));
                    $data = mysql_fetch_array($result);
                    $addon_name = $data["name"];
                    $addon_description = $data["description"];
                    $addon_billingcycle = $data["billingcycle"];
                    $addon_tax = $data["tax"];
                    $serverType = $data["module"];
                    $serverGroupId = $data["server_group_id"];
                    if (!$CONFIG["TaxEnabled"]) {
                        $addon_tax = "";
                    }
                    switch ($addon_billingcycle) {
                        case "recurring":
                            $availableAddonCycles = array();
                            $data = WHMCS\Database\Capsule::table("tblpricing")->where("type", "=", "addon")->where("currency", "=", $currency["id"])->where("relid", "=", $addonid)->first();
                            $databaseCycles = array("monthly", "quarterly", "semiannually", "annually", "biennially", "triennially");
                            $databaseSetups = array("msetupfee", "qsetupfee", "ssetupfee", "asetupfee", "bsetupfee", "tsetupfee");
                            foreach ($databaseCycles as $dbCyclesKey => $value) {
                                if (0 <= $data->{$value}) {
                                    $objectKey = $databaseSetups[$dbCyclesKey];
                                    $availableAddonCycles[$value] = array("price" => $data->{$value}, "setup" => $data->{$objectKey});
                                }
                            }
                            $addon_setupfee = 0;
                            $addon_recurring = 0;
                            $addon_billingcycle = "Free Account";
                            if ($availableAddonCycles) {
                                if (array_key_exists($billingcycle, $availableAddonCycles)) {
                                    $addon_setupfee = $availableAddonCycles[$billingcycle]["setup"];
                                    $addon_recurring = $availableAddonCycles[$billingcycle]["price"];
                                    $addon_billingcycle = $billingcycle;
                                } else {
                                    foreach ($availableAddonCycles as $cycle => $data) {
                                        $addon_setupfee = $data["setup"];
                                        $addon_recurring = $data["price"];
                                        $addon_billingcycle = $cycle;
                                        break;
                                    }
                                }
                            }
                            break;
                        case "free":
                        case "Free":
                        case "Free Account":
                            $addon_setupfee = 0;
                            $addon_recurring = 0;
                            $addon_billingcycle = "Free";
                            break;
                        case "onetime":
                            $addon_billingcycle = "One Time";
                        case "One Time":
                        default:
                            $result = select_query("tblpricing", "msetupfee,monthly", array("type" => "addon", "currency" => $currency["id"], "relid" => $addonid));
                            $data = mysql_fetch_array($result);
                            $addon_setupfee = $data["msetupfee"];
                            $addon_recurring = $data["monthly"];
                            break;
                    }
                    $hookret = run_hook("OrderAddonPricingOverride", array("key" => $key, "pid" => $pid, "addonid" => $addonid, "proddata" => $productdata));
                    foreach ($hookret as $hookret2) {
                        if (is_array($hookret2)) {
                            if ($hookret2["setup"]) {
                                $addon_setupfee = $hookret2["setup"];
                            }
                            if ($hookret2["recurring"]) {
                                $addon_recurring = $hookret2["recurring"];
                            }
                        }
                    }
                    $addon_total_today_db = $addon_setupfee + $addon_recurring;
                    $addon_recurring_db = $addon_recurring;
                    $addon_total_today = $addon_total_today_db * $qty;
                    if (WHMCS\Config\Setting::getValue("TaxEnabled") && WHMCS\Config\Setting::getValue("TaxInclusiveDeduct")) {
                        $addon_total_today_db = round($addon_total_today_db / $excltaxrate, 2);
                        $addon_recurring_db = round($addon_recurring_db / $excltaxrate, 2);
                    }
                    if ($promotioncode) {
                        $onetimediscount = $recurringdiscount = $promoid = 0;
                        if ($promocalc = CalcPromoDiscount("A" . $addonid, $addon_billingcycle, $addon_total_today_db, $addon_recurring_db, $addon_setupfee)) {
                            $onetimediscount = $promocalc["onetimediscount"];
                            $recurringdiscount = $promocalc["recurringdiscount"];
                            $addon_total_today_db -= $onetimediscount;
                            $addon_recurring_db -= $recurringdiscount;
                            $cart_discount += $onetimediscount * $qty;
                        }
                    }
                    if ($checkout) {
                        if ($addon_billingcycle == "Free") {
                            $addon_billingcycle = "Free Account";
                        }
                        for ($qtycount = 1; $qtycount <= $qty; $qtycount++) {
                            $serviceid = $multiqtyids[$qtycount];
                            $addonsetupfee = $addon_total_today_db - $addon_recurring_db;
                            $serverId = $serverType ? getServerID($serverType, $serverGroupId) : "0";
                            $aid = insert_query("tblhostingaddons", array("hostingid" => $serviceid, "addonid" => $addonid, "userid" => $userid, "orderid" => $orderid, "server" => $serverId, "regdate" => "now()", "name" => "", "setupfee" => $addonsetupfee, "recurring" => $addon_recurring_db, "billingcycle" => $addon_billingcycle, "status" => "Pending", "nextduedate" => "now()", "nextinvoicedate" => "now()", "paymentmethod" => $paymentmethod, "tax" => $addon_tax));
                            $orderaddonids[] = $aid;
                            $adminemailitems .= $_LANG["clientareaaddon"] . ": " . $addon_name . "<br>\n" . $_LANG["ordersetupfee"] . ": " . new WHMCS\View\Formatter\Price($addonsetupfee, $currency) . "<br>\n";
                            if ($addon_recurring_db) {
                                $adminemailitems .= $_LANG["recurringamount"] . ": " . new WHMCS\View\Formatter\Price($addon_recurring_db, $currency) . "<br>\n";
                            }
                            $adminemailitems .= $_LANG["orderbillingcycle"] . ": " . $_LANG["orderpaymentterm" . str_replace(array("-", " "), "", strtolower($addon_billingcycle))] . "<br>\n<br>\n";
                        }
                    }
                    $addon_total_today_db *= $qty;
                    $cart_total += $addon_total_today_db;
                    $addon_recurring_db *= $qty;
                    $addon_billingcycle = str_replace(array("-", " "), "", strtolower($addon_billingcycle));
                    if ($addon_tax && !$clientsdetails["taxexempt"]) {
                        $cart_tax[] = $addon_total_today_db;
                        if (!isset($recurring_tax[$addon_billingcycle])) {
                            $recurring_tax[$addon_billingcycle] = array();
                        }
                        $recurring_tax[$addon_billingcycle][] = $addon_recurring_db;
                    }
                    $recurring_cycles_total[$addon_billingcycle] += $addon_recurring_db;
                    if ($addon_setupfee == "0" && $addon_recurring == "0") {
                        $pricing_text = $_LANG["orderfree"];
                    } else {
                        $pricing_text = new WHMCS\View\Formatter\Price($addon_recurring, $currency);
                        if ($addon_setupfee && $addon_setupfee != "0.00") {
                            $pricing_text .= " + " . new WHMCS\View\Formatter\Price($addon_setupfee, $currency) . " " . $_LANG["ordersetupfee"];
                        }
                        if ($allowqty && 1 < $qty) {
                            $pricing_text .= $_LANG["invoiceqtyeach"] . "<br />" . $_LANG["invoicestotal"] . ": " . new WHMCS\View\Formatter\Price($addon_total_today, $currency);
                        }
                    }
                    $addonsarray[] = array("name" => $addon_name, "pricingtext" => $pricing_text, "setup" => 0 < $addon_setupfee ? new WHMCS\View\Formatter\Price($addon_setupfee * $qty, $currency) : "", "recurring" => new WHMCS\View\Formatter\Price($addon_recurring, $currency), "billingcycle" => $addon_billingcycle, "billingcyclefriendly" => Lang::trans("orderpaymentterm" . $addon_billingcycle), "totaltoday" => new WHMCS\View\Formatter\Price($addon_total_today, $currency));
                    $productdata["pricing"]["setup"] += $addon_setupfee * $qty;
                    $productdata["pricing"]["addons"] += $addon_recurring * $qty;
                    $productdata["pricing"]["recurring"][$addon_billingcycle] += $addon_recurring * $qty;
                    $productdata["pricing"]["totaltoday"] += $addon_total_today;
                }
            }
            $productdata["addons"] = $addonsarray;
            if ($CONFIG["TaxEnabled"] && $tax && !$clientsdetails["taxexempt"]) {
                $taxCalculator->setTaxBase($productdata["pricing"]["totaltoday"]);
                $total_tax_1 = $taxCalculator->getLevel1TaxTotal();
                $total_tax_2 = $taxCalculator->getLevel2TaxTotal();
                $productdata["pricing"]["totaltoday"] = $taxCalculator->getTotalAfterTaxes();
                if (0 < $total_tax_1) {
                    $productdata["pricing"]["tax1"] = new WHMCS\View\Formatter\Price($total_tax_1, $currency);
                }
                if (0 < $total_tax_2) {
                    $productdata["pricing"]["tax2"] = new WHMCS\View\Formatter\Price($total_tax_2, $currency);
                }
            }
            $productdata["pricing"]["productonlysetup"] = 0 < $productdata["pricing"]["productonlysetup"] ? new WHMCS\View\Formatter\Price($productdata["pricing"]["productonlysetup"], $currency) : "";
            $productdata["pricing"]["setup"] = new WHMCS\View\Formatter\Price($productdata["pricing"]["setup"], $currency);
            foreach ($productdata["pricing"]["recurring"] as $cycle => $recurring) {
                unset($productdata["pricing"]["recurring"][$cycle]);
                if (0 < $recurring) {
                    $recurringwithtax = $recurring;
                    $recurringbeforetax = $recurringwithtax;
                    if ($CONFIG["TaxEnabled"] && $tax && !$clientsdetails["taxexempt"]) {
                        $taxCalculator->setTaxBase($recurring);
                        $recurringwithtax = $taxCalculator->getTotalAfterTaxes();
                        $recurringbeforetax = $taxCalculator->getTotalBeforeTaxes();
                    }
                    $productdata["pricing"]["recurring"][$_LANG["orderpaymentterm" . $cycle]] = new WHMCS\View\Formatter\Price($recurringwithtax, $currency);
                    $productdata["pricing"]["recurringexcltax"][$_LANG["orderpaymentterm" . $cycle]] = new WHMCS\View\Formatter\Price($recurringbeforetax, $currency);
                }
            }
            if (isset($productdata["pricing"]["addons"]) && 0 < $productdata["pricing"]["addons"]) {
                $productdata["pricing"]["addons"] = new WHMCS\View\Formatter\Price($productdata["pricing"]["addons"], $currency);
            }
            $productdata["pricing"]["totaltoday"] = new WHMCS\View\Formatter\Price($productdata["pricing"]["totaltoday"], $currency);
            $productdata["pricing"]["totaltodayexcltax"] = new WHMCS\View\Formatter\Price($productdata["pricing"]["totaltodayexcltax"], $currency);
            $productdata["pricing"]["totalTodayExcludingTaxSetup"] = new WHMCS\View\Formatter\Price($productdata["pricing"]["totalTodayExcludingTaxSetup"], $currency);
            $productsarray[$key] = $productdata;
        }
        if ($productRemovedFromCart) {
            $_SESSION["cart"]["products"] = array_values($_SESSION["cart"]["products"]);
            $cartdata["productRemovedFromCart"] = true;
        }
    }
    $cartdata["products"] = $productsarray;
    $addonsarray = array();
    $cartAddons = $orderForm->getCartDataByKey("addons");
    if (is_array($cartAddons)) {
        foreach ($cartAddons as $key => $addon) {
            $addonid = $addon["id"];
            $serviceid = $addon["productid"];
            $service = WHMCS\Service\Service::find($serviceid);
            if ($service->clientId != WHMCS\Session::get("uid")) {
                continue;
            }
            $requested_billingcycle = isset($addon["billingcycle"]) ? $addon["billingcycle"] : "";
            if (!$requested_billingcycle) {
                $requested_billingcycle = strtolower(str_replace("-", "", $service->billingCycle));
            }
            $result = select_query("tbladdons", "name,description,billingcycle,tax,module,server_group_id", array("id" => $addonid));
            $data = mysql_fetch_array($result);
            $addon_name = $data["name"];
            if (array_key_exists("sslCompetitiveUpgrade", $addon) && $addon["sslCompetitiveUpgrade"]) {
                $addon_name .= "<br><small>" . Lang::trans("store.ssl.competitiveUpgradeQualified") . "</small>";
            }
            $addon_description = $data["description"];
            $addon_billingcycle = $data["billingcycle"];
            $addon_tax = $data["tax"];
            $serverType = $data["module"];
            $serverGroupId = $data["server_group_id"];
            if (!$CONFIG["TaxEnabled"]) {
                $addon_tax = "";
            }
            switch ($addon_billingcycle) {
                case "recurring":
                    $availableAddonCycles = array();
                    $data = WHMCS\Database\Capsule::table("tblpricing")->where("type", "=", "addon")->where("currency", "=", $currency["id"])->where("relid", "=", $addonid)->first();
                    $databaseCycles = array("monthly", "quarterly", "semiannually", "annually", "biennially", "triennially");
                    $databaseSetups = array("msetupfee", "qsetupfee", "ssetupfee", "asetupfee", "bsetupfee", "tsetupfee");
                    foreach ($databaseCycles as $dbCyclesKey => $value) {
                        if (0 <= $data->{$value}) {
                            $objectKey = $databaseSetups[$dbCyclesKey];
                            $availableAddonCycles[$value] = array("price" => $data->{$value}, "setup" => $data->{$objectKey});
                        }
                    }
                    $addon_setupfee = 0;
                    $addon_recurring = 0;
                    $addon_billingcycle = "Free";
                    if ($availableAddonCycles) {
                        if (array_key_exists($requested_billingcycle, $availableAddonCycles)) {
                            $addon_setupfee = $availableAddonCycles[$requested_billingcycle]["setup"];
                            $addon_recurring = $availableAddonCycles[$requested_billingcycle]["price"];
                            $addon_billingcycle = $requested_billingcycle;
                        } else {
                            foreach ($availableAddonCycles as $cycle => $data) {
                                $addon_setupfee = $data["setup"];
                                $addon_recurring = $data["price"];
                                $addon_billingcycle = $cycle;
                                break;
                            }
                        }
                    }
                    break;
                case "free":
                case "Free":
                case "Free Account":
                    $addon_setupfee = 0;
                    $addon_recurring = 0;
                    $addon_billingcycle = "Free";
                    break;
                case "onetime":
                case "One Time":
                default:
                    $result = select_query("tblpricing", "msetupfee,monthly", array("type" => "addon", "currency" => $currency["id"], "relid" => $addonid));
                    $data = mysql_fetch_array($result);
                    $addon_setupfee = $data["msetupfee"];
                    $addon_recurring = $data["monthly"];
                    break;
            }
            $hookret = run_hook("OrderAddonPricingOverride", array("key" => $key, "addonid" => $addonid, "serviceid" => $serviceid));
            foreach ($hookret as $hookret2) {
                if (is_array($hookret2)) {
                    if ($hookret2["setup"]) {
                        $addon_setupfee = $hookret2["setup"];
                    }
                    if ($hookret2["recurring"]) {
                        $addon_recurring = $hookret2["recurring"];
                    }
                }
            }
            $addon_total_today_db = $addon_setupfee + $addon_recurring;
            $addon_recurring_db = $addon_recurring;
            if (WHMCS\Config\Setting::getValue("TaxEnabled") && WHMCS\Config\Setting::getValue("TaxInclusiveDeduct")) {
                $addon_total_today_db = round($addon_total_today_db / $excltaxrate, 2);
                $addon_recurring_db = round($addon_recurring_db / $excltaxrate, 2);
            }
            if ($promotioncode) {
                $onetimediscount = $recurringdiscount = $promoid = 0;
                if ($promocalc = CalcPromoDiscount("A" . $addonid, $addon_billingcycle, $addon_total_today_db, $addon_recurring_db, $addon_setupfee)) {
                    $onetimediscount = $promocalc["onetimediscount"];
                    $recurringdiscount = $promocalc["recurringdiscount"];
                    $addon_total_today_db -= $onetimediscount;
                    $addon_recurring_db -= $recurringdiscount;
                    $cart_discount += $onetimediscount;
                }
            }
            if ($checkout) {
                if ($addon_billingcycle == "Free") {
                    $addon_billingcycle = "Free Account";
                }
                $addonsetupfee = $addon_total_today_db - $addon_recurring_db;
                $serverId = $serverType ? getServerID($serverType, $serverGroupId) : "0";
                $aid = insert_query("tblhostingaddons", array("hostingid" => $serviceid, "addonid" => $addonid, "userid" => $userid, "orderid" => $orderid, "server" => $serverId, "regdate" => "now()", "name" => "", "setupfee" => $addonsetupfee, "recurring" => $addon_recurring_db, "billingcycle" => $addon_billingcycle, "status" => "Pending", "nextduedate" => "now()", "nextinvoicedate" => "now()", "paymentmethod" => $paymentmethod, "tax" => $addon_tax));
                if (array_key_exists("sslCompetitiveUpgrade", $addon) && $addon["sslCompetitiveUpgrade"]) {
                    $sslCompetitiveUpgradeAddons = WHMCS\Session::get("SslCompetitiveUpgradeAddons");
                    if (!is_array($sslCompetitiveUpgradeAddons)) {
                        $sslCompetitiveUpgradeAddons = array();
                    }
                    $sslCompetitiveUpgradeAddons[] = $aid;
                    WHMCS\Session::set("SslCompetitiveUpgradeAddons", $sslCompetitiveUpgradeAddons);
                }
                $orderaddonids[] = $aid;
                $adminemailitems .= $_LANG["clientareaaddon"] . ": " . $addon_name . "<br>\n" . $_LANG["ordersetupfee"] . ": " . new WHMCS\View\Formatter\Price($addonsetupfee, $currency) . "<br>\n";
                if ($addon_recurring_db) {
                    $adminemailitems .= $_LANG["recurringamount"] . ": " . new WHMCS\View\Formatter\Price($addon_recurring_db, $currency) . "<br>\n";
                }
                $adminemailitems .= $_LANG["orderbillingcycle"] . ": " . $_LANG["orderpaymentterm" . str_replace(array("-", " "), "", strtolower($addon_billingcycle))] . "<br>\n<br>\n";
            }
            $cart_total += $addon_total_today_db;
            $addon_billingcycle = str_replace(array("-", " "), "", strtolower($addon_billingcycle));
            if ($addon_tax && !$clientsdetails["taxexempt"]) {
                $cart_tax[] = $addon_total_today_db;
                if (!isset($recurring_tax[$addon_billingcycle])) {
                    $recurring_tax[$addon_billingcycle] = array();
                }
                $recurring_tax[$addon_billingcycle][] = $addon_recurring_db;
            }
            $recurring_cycles_total[$addon_billingcycle] += $addon_recurring_db;
            if ($addon_setupfee == "0" && $addon_recurring == "0") {
                $pricing_text = $_LANG["orderfree"];
            } else {
                $pricing_text = new WHMCS\View\Formatter\Price($addon_recurring, $currency);
                if ($addon_setupfee && $addon_setupfee != "0.00") {
                    $pricing_text .= " + " . new WHMCS\View\Formatter\Price($addon_setupfee, $currency) . " " . $_LANG["ordersetupfee"];
                }
            }
            $result = select_query("tblhosting", "tblproducts.name,tblhosting.packageid,tblhosting.domain", array("tblhosting.id" => $serviceid), "", "", "", "tblproducts ON tblproducts.id=tblhosting.packageid");
            $data = mysql_fetch_array($result);
            $productname = $isAdmin ? $data["name"] : WHMCS\Product\Product::getProductName($data["packageid"]);
            $domainname = $data["domain"];
            $addonsarray[] = array("addonid" => $addonid, "name" => $addon_name, "productname" => $productname, "domainname" => $domainname, "pricingtext" => $pricing_text, "setup" => 0 < $addon_setupfee ? new WHMCS\View\Formatter\Price($addon_setupfee, $currency) : "", "totaltoday" => new WHMCS\View\Formatter\Price($addon_setupfee + $addon_recurring, $currency), "billingcycle" => $addon_billingcycle, "billingcyclefriendly" => Lang::trans("orderpaymentterm" . $addon_billingcycle));
        }
    }
    $cartdata["addons"] = $addonsarray;
    $totaldomainprice = 0;
    $cartDomains = $orderForm->getCartDataByKey("domains");
    if (is_array($cartDomains)) {
        $result = select_query("tblpricing", "", array("type" => "domainaddons", "currency" => $currency["id"], "relid" => 0));
        $data = mysql_fetch_array($result);
        $domaindnsmanagementprice = $data["msetupfee"];
        $domainemailforwardingprice = $data["qsetupfee"];
        $domainidprotectionprice = $data["ssetupfee"];
        foreach ($cartDomains as $key => $domain) {
            $domaintype = $domain["type"];
            $domainname = $domain["domain"];
            $regperiod = $domain["regperiod"];
            $domainPriceOverride = array_key_exists("domainpriceoverride", $domain) ? $domain["domainpriceoverride"] : NULL;
            $domainRenewOverride = array_key_exists("domainrenewoverride", $domain) ? $domain["domainrenewoverride"] : NULL;
            $domainparts = explode(".", $domainname, 2);
            list($sld, $tld) = $domainparts;
            $temppricelist = getTLDPriceList("." . $tld);
            if (!isset($temppricelist[$regperiod][$domaintype])) {
                $tldyears = array_keys($temppricelist);
                $regperiod = $tldyears[0];
            }
            if (!isset($temppricelist[$regperiod][$domaintype])) {
                $errMsg = "Invalid TLD/Registration Period Supplied for Domain Registration";
                if ($whmcs->isApiRequest()) {
                    $apiresults = array("result" => "error", "message" => $errMsg);
                    return $apiresults;
                }
                throw new WHMCS\Exception\Fatal($errMsg);
            }
            if (array_key_exists($domainname, $freedomains)) {
                $tldyears = array_keys($temppricelist);
                $regperiod = $tldyears[0];
                $domainprice = "0.00";
                $renewprice = $freedomains[$domainname] == "once" ? $temppricelist[$regperiod]["renew"] : ($renewprice = "0.00");
            } else {
                $domainprice = $temppricelist[$regperiod][$domaintype];
                $renewprice = $temppricelist[$regperiod]["renew"];
            }
            $before_priceoverride_value = "";
            if ($bundleoverride = bundlesGetProductPriceOverride("domain", $key)) {
                $before_priceoverride_value = $domainprice;
                $domainprice = $renewprice = $bundleoverride;
            }
            if (!is_null($domainPriceOverride)) {
                $domainprice = $domainPriceOverride;
            }
            if (!is_null($domainRenewOverride)) {
                $renewprice = $domainRenewOverride;
            }
            $hookret = run_hook("OrderDomainPricingOverride", array("type" => $domaintype, "domain" => $domainname, "regperiod" => $regperiod, "dnsmanagement" => $domain["dnsmanagement"], "emailforwarding" => $domain["emailforwarding"], "idprotection" => $domain["idprotection"], "eppcode" => WHMCS\Input\Sanitize::decode($domain["eppcode"]), "premium" => $domain["isPremium"]));
            foreach ($hookret as $hookret2) {
                if (is_array($hookret2)) {
                    if (isset($hookret2["firstPaymentAmount"])) {
                        $before_priceoverride_value = $domainprice;
                        $domainprice = $hookret2["firstPaymentAmount"];
                    }
                    if (isset($hookret2["recurringAmount"])) {
                        $renewprice = $hookret2["recurringAmount"];
                    }
                } else {
                    if (strlen($hookret2)) {
                        $before_priceoverride_value = $domainprice;
                        $domainprice = $hookret2;
                    }
                }
            }
            if ($domain["dnsmanagement"]) {
                $dnsmanagement = true;
                $domainprice += $domaindnsmanagementprice * $regperiod;
                $renewprice += $domaindnsmanagementprice * $regperiod;
                if (strlen($before_priceoverride_value)) {
                    $before_priceoverride_value += $domaindnsmanagementprice * $regperiod;
                }
            } else {
                $dnsmanagement = false;
            }
            if ($domain["emailforwarding"]) {
                $emailforwarding = true;
                $domainprice += $domainemailforwardingprice * $regperiod;
                $renewprice += $domainemailforwardingprice * $regperiod;
                if (strlen($before_priceoverride_value)) {
                    $before_priceoverride_value += $domainemailforwardingprice * $regperiod;
                }
            } else {
                $emailforwarding = false;
            }
            if ($domain["idprotection"]) {
                $idprotection = true;
                $domainprice += $domainidprotectionprice * $regperiod;
                $renewprice += $domainidprotectionprice * $regperiod;
                if (strlen($before_priceoverride_value)) {
                    $before_priceoverride_value += $domainidprotectionprice * $regperiod;
                }
            } else {
                $idprotection = false;
            }
            if (WHMCS\Config\Setting::getValue("TaxEnabled") && WHMCS\Config\Setting::getValue("TaxInclusiveDeduct")) {
                $domainprice = round($domainprice / $excltaxrate, 2);
                $renewprice = round($renewprice / $excltaxrate, 2);
            }
            $domain_price_db = $domainprice;
            $domain_renew_price_db = $renewprice;
            if ($promotioncode) {
                $onetimediscount = $recurringdiscount = $promoid = 0;
                if ($promocalc = CalcPromoDiscount("D." . $tld, $regperiod . "Years", $domain_price_db, $domain_renew_price_db)) {
                    $onetimediscount = $promocalc["onetimediscount"];
                    $recurringdiscount = $promocalc["recurringdiscount"];
                    $domain_price_db -= $onetimediscount;
                    $domain_renew_price_db -= $recurringdiscount;
                    $cart_discount += $onetimediscount;
                    $promoid = $promo_data["id"];
                }
            }
            if ($regperiod == "1") {
                $domain_billing_cycle = "annually";
            } else {
                if ($regperiod == "2") {
                    $domain_billing_cycle = "biennially";
                } else {
                    if ($regperiod == "3") {
                        $domain_billing_cycle = "triennially";
                    }
                }
            }
            if (!is_null($domain_renew_price_db)) {
                if ($CONFIG["TaxEnabled"] && $CONFIG["TaxDomains"] && !$clientsdetails["taxexempt"]) {
                    if (!isset($recurring_tax[$domain_billing_cycle])) {
                        $recurring_tax[$domain_billing_cycle] = array();
                    }
                    $recurring_tax[$domain_billing_cycle][] = $domain_renew_price_db;
                }
                $recurring_cycles_total[$domain_billing_cycle] += $domain_renew_price_db;
            }
            if ($checkout) {
                $donotrenew = 1;
                if (App::get_config("DomainAutoRenewDefault")) {
                    $donotrenew = 0;
                }
                $domainid = insert_query("tbldomains", array("userid" => $userid, "orderid" => $orderid, "type" => $domaintype, "registrationdate" => "now()", "domain" => $domainname, "firstpaymentamount" => $domain_price_db, "recurringamount" => $domain_renew_price_db, "registrationperiod" => $regperiod, "status" => "Pending", "paymentmethod" => $paymentmethod, "expirydate" => "00000000", "nextduedate" => "now()", "nextinvoicedate" => "now()", "dnsmanagement" => (int) $dnsmanagement, "emailforwarding" => (int) $emailforwarding, "idprotection" => (int) $idprotection, "donotrenew" => (int) $donotrenew, "promoid" => $promoid, "is_premium" => (int) $domain["isPremium"]));
                if (array_key_exists("registrarCostPrice", $domain)) {
                    $extraDetails = WHMCS\Domain\Extra::firstOrNew(array("domain_id" => $domainid, "name" => "registrarCostPrice"));
                    $extraDetails->value = $domain["registrarCostPrice"];
                    $extraDetails->save();
                    $extraDetails = WHMCS\Domain\Extra::firstOrNew(array("domain_id" => $domainid, "name" => "registrarCurrency"));
                    $extraDetails->value = (int) $domain["registrarCurrency"];
                    $extraDetails->save();
                }
                if ($domain["isPremium"] && array_key_exists("registrarRenewalCostPrice", $domain)) {
                    $extraDetails = WHMCS\Domain\Extra::firstOrNew(array("domain_id" => $domainid, "name" => "registrarRenewalCostPrice"));
                    $extraDetails->value = $domain["registrarRenewalCostPrice"];
                    $extraDetails->save();
                    $extraDetails = WHMCS\Domain\Extra::firstOrNew(array("domain_id" => $domainid, "name" => "registrarCurrency"));
                    if ((int) $extraDetails->value != (int) $domain["registrarCurrency"]) {
                        $extraDetails->value = $domain["registrarCurrency"];
                        $extraDetails->save();
                    }
                }
                $orderdomainids[] = $domainid;
                $adminemailitems .= $_LANG["orderdomainregistration"] . ": " . ucfirst($domaintype) . "<br>\n" . $_LANG["orderdomain"] . ": " . $domainname . "<br>\n" . $_LANG["firstpaymentamount"] . ": " . new WHMCS\View\Formatter\Price($domain_price_db, $currency) . "<br>\n" . $_LANG["recurringamount"] . ": " . new WHMCS\View\Formatter\Price($domain_renew_price_db, $currency) . "<br>\n" . $_LANG["orderregperiod"] . ": " . $regperiod . " " . $_LANG["orderyears"] . "<br>\n";
                if ($dnsmanagement) {
                    $adminemailitems .= " + " . $_LANG["domaindnsmanagement"] . "<br>\n";
                }
                if ($emailforwarding) {
                    $adminemailitems .= " + " . $_LANG["domainemailforwarding"] . "<br>\n";
                }
                if ($idprotection) {
                    $adminemailitems .= " + " . $_LANG["domainidprotection"] . "<br>\n";
                }
                $adminemailitems .= "<br>\n";
                if (in_array($domaintype, array("register", "transfer"))) {
                    $additflds = new WHMCS\Domains\AdditionalFields();
                    $additflds->setTLD($tld)->setDomainType($domaintype)->setFieldValues($domain["fields"])->saveToDatabase($domainid);
                }
                if ($domaintype == "transfer" && $domain["eppcode"]) {
                    $domaineppcodes[$domainname] = $domain["eppcode"];
                }
            }
            $pricing_text = "";
            if (strlen($before_priceoverride_value)) {
                $pricing_text .= "<strike>" . new WHMCS\View\Formatter\Price($before_priceoverride_value, $currency) . "</strike> ";
            }
            $pricing_text .= new WHMCS\View\Formatter\Price($domainprice, $currency);
            $pricing = getTLDPriceList("." . $tld, true, $domaintype == "transfer" ? "transfer" : "");
            if (array_key_exists($domainname, $freedomains)) {
                $pricing = array(key($pricing) => current($pricing));
            }
            $tempdomains[$key] = array("type" => $domaintype, "domain" => $domainname, "regperiod" => $regperiod, "yearsLanguage" => $regperiod == 1 ? Lang::trans("orderForm.year") : Lang::trans("orderForm.years"), "shortYearsLanguage" => $regperiod == 1 ? Lang::trans("orderForm.shortPerYear", array(":years" => $regperiod)) : Lang::trans("orderForm.shortPerYears", array(":years" => $regperiod)), "price" => $pricing_text, "totaltoday" => new WHMCS\View\Formatter\Price($domainprice, $currency), "renewprice" => new WHMCS\View\Formatter\Price($renewprice, $currency), "dnsmanagement" => $dnsmanagement, "emailforwarding" => $emailforwarding, "idprotection" => $idprotection, "eppvalue" => $domain["eppcode"], "premium" => $domain["isPremium"], "pricing" => !is_null($domainPriceOverride) ? array(1 => $pricing_text) : $pricing);
            if (!$domain_renew_price_db) {
                unset($tempdomains[$key]["renewprice"]);
            }
            $totaldomainprice += $domain_price_db;
        }
    }
    $cartdata["domains"] = $tempdomains;
    $cart_total += $totaldomainprice;
    if ($CONFIG["TaxDomains"]) {
        $cart_tax[] = $totaldomainprice;
    }
    $orderUpgradeIds = array();
    $cartdata["upgrades"] = array();
    $cartUpgrades = $orderForm->getCartDataByKey("upgrades");
    if (is_array($cartUpgrades)) {
        foreach ($cartUpgrades as $cartUpgrade) {
            $entityType = $cartUpgrade["upgrade_entity_type"];
            $entityId = $cartUpgrade["upgrade_entity_id"];
            $targetEntityId = $cartUpgrade["target_entity_id"];
            $upgradeCycle = $cartUpgrade["billing_cycle"];
            try {
                if ($entityType == "service") {
                    $upgradeEntity = WHMCS\Service\Service::findOrFail($entityId);
                    $upgradeTarget = WHMCS\Product\Product::findOrFail($targetEntityId);
                } else {
                    if ($entityType == "addon") {
                        $upgradeEntity = WHMCS\Service\Addon::findOrFail($entityId);
                        $upgradeTarget = WHMCS\Product\Addon::findOrFail($targetEntityId);
                    } else {
                        continue;
                    }
                }
            } catch (Exception $e) {
                continue;
            }
            if ($upgradeEntity->clientId != WHMCS\Session::get("uid")) {
                continue;
            }
            $upgrade = (new WHMCS\Service\Upgrade\Calculator())->setUpgradeTargets($upgradeEntity, $upgradeTarget, $upgradeCycle)->calculate();
            $cartdata["upgrades"][] = $upgrade;
            $cart_total += $upgrade->upgradeAmount->toNumeric();
            if ($upgrade->applyTax) {
                $cart_tax[] = $upgrade->upgradeAmount->toNumeric();
            }
            if ($checkout) {
                $upgrade->userId = $userid;
                $upgrade->orderId = $orderid;
                $upgrade->upgradeAmount = $upgrade->upgradeAmount->toNumeric();
                $upgrade->creditAmount = $upgrade->creditAmount->toNumeric();
                $upgrade->newRecurringAmount = $upgrade->newRecurringAmount->toNumeric();
                $upgrade->save();
                $invoiceDescription = Lang::trans("upgrade") . ": ";
                if ($upgrade->type == "service") {
                    $invoiceDescription .= $upgrade->originalProduct->productGroup->name . " - " . $upgrade->originalProduct->name . " => " . $upgrade->newProduct->name;
                    if ($upgrade->service->domain) {
                        $invoiceDescription .= "\n" . $upgrade->service->domain;
                    }
                } else {
                    if ($upgrade->type == "addon") {
                        $invoiceDescription .= $upgrade->originalAddon->name . " => " . $upgrade->newAddon->name;
                    }
                }
                $invoiceDescription .= "\n" . "New Recurring Amount: " . formatCurrency($upgrade->newRecurringAmount);
                if (0 < $upgrade->totalDaysInCycle) {
                    $invoiceDescription .= "\n" . "Credit Amount: " . formatCurrency($upgrade->creditAmount) . "\n" . Lang::trans("upgradeCreditDescription", array(":daysRemaining" => $upgrade->daysRemaining, ":totalDays" => $upgrade->totalDaysInCycle));
                }
                insert_query("tblinvoiceitems", array("userid" => $userid, "type" => "Upgrade", "relid" => $upgrade->id, "description" => $invoiceDescription, "amount" => $upgrade->upgradeAmount, "taxed" => $upgrade->applyTax, "duedate" => "now()", "paymentmethod" => $paymentmethod));
                $orderUpgradeIds[] = $upgrade->id;
            }
        }
    }
    $orderrenewals = "";
    $cartdata["renewals"] = array();
    $cartRenewals = $orderForm->getCartDataByKey("renewals");
    if (is_array($cartRenewals)) {
        $result = select_query("tblpricing", "", array("type" => "domainaddons", "currency" => $currency["id"], "relid" => 0));
        $data = mysql_fetch_array($result);
        $domaindnsmanagementprice = $data["msetupfee"];
        $domainemailforwardingprice = $data["qsetupfee"];
        $domainidprotectionprice = $data["ssetupfee"];
        foreach ($cartRenewals as $domainid => $regperiod) {
            try {
                $domain = WHMCS\Domain\Domain::findOrFail($domainid);
            } catch (Exception $e) {
                continue;
            }
            $domainid = $domain->id;
            $userId = $domain->clientId;
            if ($userId != WHMCS\Session::get("uid")) {
                continue;
            }
            $clientCurrency = getCurrency($userId);
            $domainname = $domain->domain;
            $expirydate = $domain->expiryDate;
            if ($domain->getRawAttribute("expirydate") == "0000-00-00") {
                $expirydate = $domain->nextDueDate;
            }
            $dnsmanagement = $domain->hasDnsManagement;
            $emailforwarding = $domain->hasEmailForwarding;
            $idprotection = $domain->hasIdProtection;
            $tld = "." . $domain->tld;
            $isPremium = $domain->isPremium;
            $temppricelist = getTLDPriceList($tld, "", true);
            if (!isset($temppricelist[$regperiod]["renew"])) {
                $errMsg = "Invalid TLD/Registration Period Supplied for Domain Renewal";
                if ($whmcs->isApiRequest()) {
                    $apiresults = array("result" => "error", "message" => $errMsg);
                    return $apiresults;
                }
                throw new WHMCS\Exception\Fatal($errMsg);
            }
            $renewprice = $temppricelist[$regperiod]["renew"];
            if ($isPremium) {
                $extraDetails = WHMCS\Domain\Extra::whereDomainId($domainid)->whereName("registrarRenewalCostPrice")->first();
                if ($extraDetails) {
                    $regperiod = 1;
                    $markupRenewalPrice = $extraDetails->value;
                    $domainRecurringPrice = (double) format_as_currency($domain->recurringAmount);
                    $markupPercentage = WHMCS\Domains\Pricing\Premium::markupForCost($markupRenewalPrice);
                    $markupRenewalPrice = (double) format_as_currency($markupRenewalPrice * (1 + $markupPercentage / 100));
                    if ($domainRecurringPrice == $markupRenewalPrice) {
                        $renewprice = $domainRecurringPrice;
                    } else {
                        if ($markupRenewalPrice <= $domainRecurringPrice) {
                            $renewprice = $domainRecurringPrice;
                        } else {
                            if ($domainRecurringPrice <= $markupRenewalPrice) {
                                $renewprice = $markupRenewalPrice;
                            } else {
                                $renewprice = $markupRenewalPrice;
                            }
                        }
                    }
                }
            }
            $renewalGracePeriod = $domain->gracePeriod;
            $gracePeriodFee = $domain->gracePeriodFee;
            $redemptionGracePeriod = $domain->redemptionGracePeriod;
            $redemptionGracePeriodFee = $domain->redemptionGracePeriodFee;
            if (0 < $gracePeriodFee) {
                $gracePeriodFee = convertCurrency($gracePeriodFee, 1, $clientCurrency["id"]);
            }
            if (0 < $redemptionGracePeriodFee) {
                $redemptionGracePeriodFee = convertCurrency($redemptionGracePeriodFee, 1, $clientCurrency["id"]);
            }
            if (!$renewalGracePeriod || $renewalGracePeriod < 0 || $gracePeriodFee < 0) {
                $renewalGracePeriod = 0;
                $gracePeriodFee = 0;
            }
            if (!$redemptionGracePeriod || $redemptionGracePeriod < 0 || $redemptionGracePeriodFee < 0) {
                $redemptionGracePeriod = 0;
                $redemptionGracePeriodFee = 0;
            }
            $today = WHMCS\Carbon::today();
            $todayExpiryDifference = $today->diff($expirydate);
            $daysUntilExpiry = ($todayExpiryDifference->invert == 1 ? -1 : 1) * $todayExpiryDifference->days;
            $inGracePeriod = $inRedemptionGracePeriod = false;
            if ($daysUntilExpiry < 0) {
                if ($renewalGracePeriod && 0 - $renewalGracePeriod <= $daysUntilExpiry) {
                    $inGracePeriod = true;
                } else {
                    if ($redemptionGracePeriod && 0 - ($renewalGracePeriod + $redemptionGracePeriod) <= $daysUntilExpiry) {
                        $inRedemptionGracePeriod = true;
                    }
                }
                if (($inGracePeriod || $inRedemptionGracePeriod) && !$isPremium) {
                    $renewalOptions = reset($temppricelist);
                    $regperiod = reset(array_keys($temppricelist));
                    $renewprice = $renewalOptions["renew"];
                }
            }
            if ($dnsmanagement) {
                $renewprice += $domaindnsmanagementprice * $regperiod;
            }
            if ($emailforwarding) {
                $renewprice += $domainemailforwardingprice * $regperiod;
            }
            if ($idprotection) {
                $renewprice += $domainidprotectionprice * $regperiod;
            }
            if (WHMCS\Config\Setting::getValue("TaxEnabled") && WHMCS\Config\Setting::getValue("TaxInclusiveDeduct")) {
                $renewprice = round($renewprice / $excltaxrate, 2);
            }
            $domain_renew_price_db = $renewprice;
            if ($promotioncode) {
                $onetimediscount = $recurringdiscount = $promoid = 0;
                if ($promocalc = CalcPromoDiscount("D" . $tld, $regperiod . "Years", $domain_renew_price_db, $domain_renew_price_db)) {
                    $onetimediscount = $promocalc["onetimediscount"];
                    $domain_renew_price_db -= $onetimediscount;
                    $cart_discount += $onetimediscount;
                }
            }
            $cart_total += $domain_renew_price_db;
            if ($CONFIG["TaxDomains"]) {
                $cart_tax[] = $domain_renew_price_db;
            }
            if ($checkout) {
                $domain_renew_price_db = format_as_currency($domain_renew_price_db);
                $orderrenewalids[] = $domainid;
                $orderrenewals .= (string) $domainid . "=" . $regperiod . ",";
                $adminemailitems .= $_LANG["domainrenewal"] . ": " . $domainname . " - " . $regperiod . " " . $_LANG["orderyears"] . "<br>\n";
                $domaindesc = $_LANG["domainrenewal"] . " - " . $domainname . " - " . $regperiod . " " . $_LANG["orderyears"] . " (" . fromMySQLDate($expirydate) . " - " . fromMySQLDate(getInvoicePayUntilDate($expirydate, $regperiod)) . ")";
                if ($dnsmanagement) {
                    $adminemailitems .= " + " . $_LANG["domaindnsmanagement"] . "<br>\n";
                    $domaindesc .= "\n + " . $_LANG["domaindnsmanagement"];
                }
                if ($emailforwarding) {
                    $adminemailitems .= " + " . $_LANG["domainemailforwarding"] . "<br>\n";
                    $domaindesc .= "\n + " . $_LANG["domainemailforwarding"];
                }
                if ($idprotection) {
                    $adminemailitems .= " + " . $_LANG["domainidprotection"] . "<br>\n";
                    $domaindesc .= "\n + " . $_LANG["domainidprotection"];
                }
                $adminemailitems .= "<br>\n";
                $tax = WHMCS\Config\Setting::getValue("TaxEnabled") && WHMCS\Config\Setting::getValue("TaxDomains") ? "1" : "0";
                $domain->registrationPeriod = $regperiod;
                $domain->recurringAmount = $domain_renew_price_db;
                insert_query("tblinvoiceitems", array("userid" => $userid, "type" => "Domain", "relid" => $domainid, "description" => $domaindesc, "amount" => $domain_renew_price_db, "taxed" => $tax, "duedate" => "now()", "paymentmethod" => $paymentmethod));
                if ($inGracePeriod || $inRedemptionGracePeriod) {
                    if (0 < $gracePeriodFee) {
                        WHMCS\Database\Capsule::table("tblinvoiceitems")->insert(array("userid" => $userId, "type" => "DomainGraceFee", "relid" => $domainid, "description" => Lang::trans("domainGracePeriodFeeInvoiceItem", array(":domainName" => $domainname)), "amount" => $gracePeriodFee, "taxed" => $tax, "duedate" => $today->toDateString(), "paymentmethod" => $paymentmethod));
                    }
                    if ($domain->status == "Active") {
                        $domain->status = "Grace";
                    }
                }
                if ($inRedemptionGracePeriod) {
                    if (0 < $redemptionGracePeriodFee) {
                        WHMCS\Database\Capsule::table("tblinvoiceitems")->insert(array("userid" => $userId, "type" => "DomainRedemptionFee", "relid" => $domainid, "description" => Lang::trans("domainRedemptionPeriodFeeInvoiceItem", array(":domainName" => $domainname)), "amount" => $redemptionGracePeriodFee, "taxed" => $tax, "duedate" => $today->toDateString(), "paymentmethod" => $paymentmethod));
                    }
                    if (in_array($domain->status, array("Active", "Grace"))) {
                        $domain->status = "Redemption";
                    }
                }
                $domain->save();
                $result = select_query("tblinvoiceitems", "tblinvoiceitems.id,tblinvoiceitems.invoiceid", array("type" => "Domain", "relid" => $domainid, "status" => "Unpaid", "tblinvoices.userid" => $_SESSION["uid"]), "", "", "", "tblinvoices ON tblinvoices.id=tblinvoiceitems.invoiceid");
                while ($data = mysql_fetch_array($result)) {
                    $itemid = $data["id"];
                    $invoiceid = $data["invoiceid"];
                    $otherItems = WHMCS\Billing\Invoice\Item::where("invoiceid", $invoiceid)->where("id", "!=", $itemid);
                    $itemCount = $otherItems->count();
                    foreach ($otherItems->get() as $otherItem) {
                        switch ($otherItem->type) {
                            case "DomainGraceFee":
                            case "DomainRedemptionFee":
                            case "PromoDomain":
                                if ($otherItem->relatedEntityId == $domainid) {
                                    $itemCount--;
                                }
                                break;
                            case "GroupDiscount":
                            case "LateFee":
                                $itemCount--;
                                break;
                        }
                    }
                    if ($itemCount === 0) {
                        update_query("tblinvoices", array("status" => "Cancelled"), array("id" => $invoiceid));
                        logActivity("Cancelled Previous Domain Renewal Invoice - " . "Invoice ID: " . $invoiceid . " - Domain: " . $domainname, $userId);
                        run_hook("InvoiceCancelled", array("invoiceid" => $invoiceid));
                    } else {
                        WHMCS\Billing\Invoice\Item::where(function (Illuminate\Database\Eloquent\Builder $query) use($invoiceid, $domainid) {
                            $query->where("invoiceid", $invoiceid)->where("relid", $domainid)->whereIn("type", array("Domain", "DomainGraceFee", "DomainRedemptionFee", "PromoDomain"));
                        })->orWhere(function (Illuminate\Database\Eloquent\Builder $query) use($invoiceid) {
                            $query->where("invoiceid", $invoiceid)->whereIn("type", array("GroupDiscount", "LateFee"));
                        })->delete();
                        updateInvoiceTotal($invoiceid);
                        logActivity("Removed Previous Domain Renewal Line Item" . " - Invoice ID: " . $invoiceid . " - Domain: " . $domainname, $userId);
                    }
                }
            }
            $renewalPrice = $renewprice;
            $hasGracePeriodFee = $hasRedemptionGracePeriodFee = false;
            if (($inGracePeriod || $inRedemptionGracePeriod) && $gracePeriodFee != "0.00") {
                $cart_total += $gracePeriodFee;
                $renewalPrice += $gracePeriodFee;
                if ($CONFIG["TaxDomains"]) {
                    $cart_tax[] = $gracePeriodFee;
                }
                $hasGracePeriodFee = true;
            }
            if ($inRedemptionGracePeriod && $redemptionGracePeriodFee != "0.00") {
                $cart_total += $redemptionGracePeriodFee;
                $renewalPrice += $redemptionGracePeriodFee;
                if ($CONFIG["TaxDomains"]) {
                    $cart_tax[] = $redemptionGracePeriodFee;
                }
                $hasRedemptionGracePeriodFee = true;
            }
            $renewalTax = array();
            $renewalPriceBeforeTax = $renewalPrice;
            if (WHMCS\Config\Setting::getValue("TaxEnabled") && WHMCS\Config\Setting::getValue("TaxDomains") && !$clientsdetails["taxexempt"]) {
                $taxCalculator->setTaxBase($renewalPrice);
                $total_tax_1 = $taxCalculator->getLevel1TaxTotal();
                $total_tax_2 = $taxCalculator->getLevel2TaxTotal();
                if (0 < $total_tax_1) {
                    $renewalTax["tax1"] = new WHMCS\View\Formatter\Price($total_tax_1, $currency);
                }
                if (0 < $total_tax_2) {
                    $renewalTax["tax2"] = new WHMCS\View\Formatter\Price($total_tax_2, $currency);
                }
                if (WHMCS\Config\Setting::getValue("TaxType") == "Inclusive") {
                    $renewalPriceBeforeTax = $taxCalculator->getTotalBeforeTaxes();
                }
            }
            $cartdata["renewals"][$domainid] = array("domain" => $domainname, "regperiod" => $regperiod, "price" => new WHMCS\View\Formatter\Price($renewalPrice, $currency), "priceBeforeTax" => new WHMCS\View\Formatter\Price($renewalPriceBeforeTax, $currency), "taxes" => $renewalTax, "dnsmanagement" => $dnsmanagement, "emailforwarding" => $emailforwarding, "idprotection" => $idprotection, "hasGracePeriodFee" => $hasGracePeriodFee, "hasRedemptionGracePeriodFee" => $hasRedemptionGracePeriodFee);
        }
    }
    $cart_adjustments = 0;
    $adjustments = run_hook("CartTotalAdjustment", $_SESSION["cart"]);
    foreach ($adjustments as $k => $adjvals) {
        if ($checkout) {
            insert_query("tblinvoiceitems", array("userid" => $userid, "type" => "", "relid" => "", "description" => $adjvals["description"], "amount" => $adjvals["amount"], "taxed" => $adjvals["taxed"], "duedate" => "now()", "paymentmethod" => $paymentmethod));
        }
        $adjustments[$k]["amount"] = new WHMCS\View\Formatter\Price($adjvals["amount"], $currency);
        $cart_adjustments += $adjvals["amount"];
        if ($adjvals["taxed"]) {
            $cart_tax[] = $adjvals["amount"];
        }
    }
    $total_tax_1 = $total_tax_2 = 0;
    if ($CONFIG["TaxEnabled"] && !$clientsdetails["taxexempt"]) {
        if (WHMCS\Config\Setting::getValue("TaxPerLineItem")) {
            foreach ($cart_tax as $taxBase) {
                $taxCalculator->setTaxBase($taxBase);
                $total_tax_1 += $taxCalculator->getLevel1TaxTotal();
                $total_tax_2 += $taxCalculator->getLevel2TaxTotal();
            }
        } else {
            $taxCalculator->setTaxBase(array_sum($cart_tax));
            $total_tax_1 = $taxCalculator->getLevel1TaxTotal();
            $total_tax_2 = $taxCalculator->getLevel2TaxTotal();
        }
        if ($CONFIG["TaxType"] == "Inclusive") {
            $cart_total -= $total_tax_1 + $total_tax_2;
        } else {
            foreach ($recurring_tax as $cycle => $taxBases) {
                if (WHMCS\Config\Setting::getValue("TaxPerLineItem")) {
                    foreach ($taxBases as $taxBase) {
                        $taxCalculator->setTaxBase($taxBase);
                        $recurring_cycles_total[$cycle] += $taxCalculator->getLevel1TaxTotal() + $taxCalculator->getLevel2TaxTotal();
                    }
                } else {
                    $taxCalculator->setTaxBase(array_sum($taxBases));
                    $recurring_cycles_total[$cycle] += $taxCalculator->getLevel1TaxTotal() + $taxCalculator->getLevel2TaxTotal();
                }
            }
        }
    }
    $cart_subtotal = $cart_total + $cart_discount;
    $cart_total += $total_tax_1 + $total_tax_2 + $cart_adjustments;
    $cart_subtotal = format_as_currency($cart_subtotal);
    $cart_discount = format_as_currency($cart_discount);
    $cart_adjustments = format_as_currency($cart_adjustments);
    $total_tax_1 = format_as_currency($total_tax_1);
    $total_tax_2 = format_as_currency($total_tax_2);
    $cart_total = format_as_currency($cart_total);
    if ($checkout) {
        $adminemailitems .= $_LANG["ordertotalduetoday"] . ": " . new WHMCS\View\Formatter\Price($cart_total, $currency);
        if ($promotioncode && $promo_data["promoapplied"]) {
            update_query("tblpromotions", array("uses" => "+1"), array("code" => $promotioncode));
            $promo_recurring = $promo_data["recurring"] ? "Recurring" : "One Time";
            update_query("tblorders", array("promocode" => $promo_data["code"], "promotype" => $promo_recurring . " " . $promo_data["type"], "promovalue" => $promo_data["value"]), array("id" => $orderid));
        }
        if ($_SESSION["cart"]["ns1"] && $_SESSION["cart"]["ns1"]) {
            $ordernameservers = $_SESSION["cart"]["ns1"] . "," . $_SESSION["cart"]["ns2"];
            if ($_SESSION["cart"]["ns3"]) {
                $ordernameservers .= "," . $_SESSION["cart"]["ns3"];
            }
            if ($_SESSION["cart"]["ns4"]) {
                $ordernameservers .= "," . $_SESSION["cart"]["ns4"];
            }
            if ($_SESSION["cart"]["ns5"]) {
                $ordernameservers .= "," . $_SESSION["cart"]["ns5"];
            }
        }
        $domaineppcodes = count($domaineppcodes) ? safe_serialize($domaineppcodes) : "";
        $orderdata = array();
        if (is_array($_SESSION["cart"]["bundle"])) {
            foreach ($_SESSION["cart"]["bundle"] as $bvals) {
                $orderdata["bundleids"][] = $bvals["bid"];
            }
        }
        update_query("tblorders", array("amount" => $cart_total, "nameservers" => $ordernameservers, "transfersecret" => $domaineppcodes, "renewals" => substr($orderrenewals, 0, -1), "orderdata" => safe_serialize($orderdata)), array("id" => $orderid));
        $invoiceid = 0;
        if (!$_SESSION["cart"]["geninvoicedisabled"]) {
            if (!$userid) {
                $errMsg = "An error occurred";
                if ($whmcs->isApiRequest()) {
                    $apiresults = array("result" => "error", "message" => $errMsg);
                    return $apiresults;
                }
                throw new WHMCS\Exception\Fatal($errMsg);
            }
            $invoiceid = createInvoices($userid, true, "", array("products" => $orderproductids, "addons" => $orderaddonids, "domains" => $orderdomainids));
            if ($CONFIG["OrderDaysGrace"]) {
                $new_time = mktime(0, 0, 0, date("m"), date("d") + $CONFIG["OrderDaysGrace"], date("Y"));
                $duedate = date("Y-m-d", $new_time);
                update_query("tblinvoices", array("duedate" => $duedate), array("id" => $invoiceid));
            }
            if (!$CONFIG["NoInvoiceEmailOnOrder"]) {
                $invoiceArr = array("source" => "autogen", "user" => WHMCS\Session::get("adminid") ? WHMCS\Session::get("adminid") : "system", "invoiceid" => $invoiceid);
                run_hook("InvoiceCreationPreEmail", $invoiceArr);
                sendMessage("Invoice Created", $invoiceid);
            }
        }
        if ($invoiceid) {
            update_query("tblorders", array("invoiceid" => $invoiceid), array("id" => $orderid));
            $result = select_query("tblinvoices", "status", array("id" => $invoiceid));
            $data = mysql_fetch_array($result);
            $status = $data["status"];
            if ($status == "Paid") {
                if ($orderid) {
                    run_hook("OrderPaid", array("orderId" => $orderid, "userId" => $userid, "invoiceId" => $invoiceid));
                }
                $invoiceid = "";
            }
        }
        if (!$_SESSION["adminid"]) {
            if (isset($_COOKIE["WHMCSAffiliateID"])) {
                $result = select_query("tblaffiliates", "clientid", array("id" => (int) $_COOKIE["WHMCSAffiliateID"]));
                $data = mysql_fetch_array($result);
                $clientid = $data["clientid"];
                if ($clientid && $_SESSION["uid"] != $clientid) {
                    foreach ($orderproductids as $orderproductid) {
                        insert_query("tblaffiliatesaccounts", array("affiliateid" => (int) $_COOKIE["WHMCSAffiliateID"], "relid" => $orderproductid));
                    }
                }
            }
            if (isset($_COOKIE["WHMCSLinkID"])) {
                update_query("tbllinks", array("conversions" => "+1"), array("id" => $_COOKIE["WHMCSLinkID"]));
            }
        }
        $result = select_query("tblclients", "firstname, lastname, companyname, email, address1, address2, city, state, postcode, country, phonenumber, ip, host", array("id" => $userid));
        $data = mysql_fetch_array($result);
        list($firstname, $lastname, $companyname, $email, $address1, $address2, $city, $state, $postcode, $country, $phonenumber, $ip, $host) = $data;
        $customfields = getCustomFields("client", "", $userid, "", true);
        $clientcustomfields = "";
        foreach ($customfields as $customfield) {
            $clientcustomfields .= (string) $customfield["name"] . ": " . $customfield["value"] . "<br />\n";
        }
        $result = select_query("tblpaymentgateways", "value", array("gateway" => $paymentmethod, "setting" => "name"));
        $data = mysql_fetch_array($result);
        $nicegatewayname = $data["value"];
        sendAdminMessage("New Order Notification", array("order_id" => $orderid, "order_number" => $order_number, "order_date" => fromMySQLDate(date("Y-m-d H:i:s"), true), "invoice_id" => $invoiceid, "order_payment_method" => $nicegatewayname, "order_total" => new WHMCS\View\Formatter\Price($cart_total, $currency), "client_id" => $userid, "client_first_name" => $firstname, "client_last_name" => $lastname, "client_email" => $email, "client_company_name" => $companyname, "client_address1" => $address1, "client_address2" => $address2, "client_city" => $city, "client_state" => $state, "client_postcode" => $postcode, "client_country" => $country, "client_phonenumber" => $phonenumber, "client_customfields" => $clientcustomfields, "order_items" => $adminemailitems, "order_notes" => nl2br($ordernotes), "client_ip" => $ip, "client_hostname" => $host), "account");
        if (!$_SESSION["cart"]["orderconfdisabled"]) {
            sendMessage("Order Confirmation", $userid, array("order_id" => $orderid, "order_number" => $order_number, "order_details" => $adminemailitems));
        }
        $_SESSION["cart"] = array();
        $_SESSION["orderdetails"] = array("OrderID" => $orderid, "OrderNumber" => $order_number, "ServiceIDs" => $orderproductids, "DomainIDs" => $orderdomainids, "AddonIDs" => $orderaddonids, "UpgradeIDs" => $orderUpgradeIds, "RenewalIDs" => $orderrenewalids, "PaymentMethod" => $paymentmethod, "InvoiceID" => $invoiceid, "TotalDue" => $cart_total, "Products" => $orderproductids, "Domains" => $orderdomainids, "Addons" => $orderaddonids, "Renewals" => $orderrenewalids);
        run_hook("AfterShoppingCartCheckout", $_SESSION["orderdetails"]);
    }
    $total_recurringmonthly = $recurring_cycles_total["monthly"] <= 0 ? "" : new WHMCS\View\Formatter\Price($recurring_cycles_total["monthly"], $currency);
    $total_recurringquarterly = $recurring_cycles_total["quarterly"] <= 0 ? "" : new WHMCS\View\Formatter\Price($recurring_cycles_total["quarterly"], $currency);
    $total_recurringsemiannually = $recurring_cycles_total["semiannually"] <= 0 ? "" : new WHMCS\View\Formatter\Price($recurring_cycles_total["semiannually"], $currency);
    $total_recurringannually = $recurring_cycles_total["annually"] <= 0 ? "" : new WHMCS\View\Formatter\Price($recurring_cycles_total["annually"], $currency);
    $total_recurringbiennially = $recurring_cycles_total["biennially"] <= 0 ? "" : new WHMCS\View\Formatter\Price($recurring_cycles_total["biennially"], $currency);
    $total_recurringtriennially = $recurring_cycles_total["triennially"] <= 0 ? "" : new WHMCS\View\Formatter\Price($recurring_cycles_total["triennially"], $currency);
    $cartdata["bundlewarnings"] = $bundlewarnings;
    $cartdata["rawdiscount"] = $cart_discount;
    $cartdata["subtotal"] = new WHMCS\View\Formatter\Price($cart_subtotal, $currency);
    $cartdata["discount"] = new WHMCS\View\Formatter\Price($cart_discount, $currency);
    $cartdata["promotype"] = $promo_data["type"];
    $cartdata["promovalue"] = $promo_data["type"] == "Fixed Amount" || $promo_data["type"] == "Price Override" ? new WHMCS\View\Formatter\Price($promo_data["value"], $currency) : round($promo_data["value"], 2);
    $cartdata["promorecurring"] = $promo_data["recurring"] ? $_LANG["recurring"] : $_LANG["orderpaymenttermonetime"];
    $cartdata["taxrate"] = $rawtaxrate;
    $cartdata["taxrate2"] = $rawtaxrate2;
    $cartdata["taxname"] = $taxname;
    $cartdata["taxname2"] = $taxname2;
    $cartdata["taxtotal"] = new WHMCS\View\Formatter\Price($total_tax_1, $currency);
    $cartdata["taxtotal2"] = new WHMCS\View\Formatter\Price($total_tax_2, $currency);
    $cartdata["adjustments"] = $adjustments;
    $cartdata["adjustmentstotal"] = new WHMCS\View\Formatter\Price($cart_adjustments, $currency);
    $cartdata["rawtotal"] = $cart_total;
    $cartdata["total"] = new WHMCS\View\Formatter\Price($cart_total, $currency);
    $cartdata["totalrecurringmonthly"] = $total_recurringmonthly;
    $cartdata["totalrecurringquarterly"] = $total_recurringquarterly;
    $cartdata["totalrecurringsemiannually"] = $total_recurringsemiannually;
    $cartdata["totalrecurringannually"] = $total_recurringannually;
    $cartdata["totalrecurringbiennially"] = $total_recurringbiennially;
    $cartdata["totalrecurringtriennially"] = $total_recurringtriennially;
    run_hook("AfterCalculateCartTotals", $cartdata);
    return $cartdata;
}
function SetPromoCode($promotioncode)
{
    global $_LANG;
    $_SESSION["cart"]["promo"] = "";
    $result = select_query("tblpromotions", "", array("code" => $promotioncode));
    $data = mysql_fetch_array($result);
    $id = $data["id"];
    $maxuses = $data["maxuses"];
    $uses = $data["uses"];
    $startdate = $data["startdate"];
    $expiredate = $data["expirationdate"];
    $newsignups = $data["newsignups"];
    $existingclient = $data["existingclient"];
    $onceperclient = $data["onceperclient"];
    if (!$id) {
        $promoerrormessage = $_LANG["ordercodenotfound"];
        return $promoerrormessage;
    }
    if ($startdate != "0000-00-00") {
        $startdate = str_replace("-", "", $startdate);
        if (date("Ymd") < $startdate) {
            $promoerrormessage = $_LANG["orderpromoprestart"];
            return $promoerrormessage;
        }
    }
    if ($expiredate != "0000-00-00") {
        $expiredate = str_replace("-", "", $expiredate);
        if ($expiredate < date("Ymd")) {
            $promoerrormessage = $_LANG["orderpromoexpired"];
            return $promoerrormessage;
        }
    }
    if (0 < $maxuses && $maxuses <= $uses) {
        $promoerrormessage = $_LANG["orderpromomaxusesreached"];
        return $promoerrormessage;
    }
    if ($newsignups && $_SESSION["uid"]) {
        $result = select_query("tblorders", "COUNT(*)", array("userid" => $_SESSION["uid"]));
        $data = mysql_fetch_array($result);
        $previousorders = $data[0];
        if (0 < $previousorders) {
            $promoerrormessage = $_LANG["promonewsignupsonly"];
            return $promoerrormessage;
        }
    }
    if ($existingclient) {
        if ($_SESSION["uid"]) {
            $result = select_query("tblorders", "count(*)", array("status" => "Active", "userid" => $_SESSION["uid"]));
            $orderCount = mysql_fetch_array($result);
            if ($orderCount[0] == 0) {
                $promoerrormessage = $_LANG["promoexistingclient"];
                return $promoerrormessage;
            }
        } else {
            $promoerrormessage = $_LANG["promoexistingclient"];
            return $promoerrormessage;
        }
    }
    if ($onceperclient && $_SESSION["uid"]) {
        $result = select_query("tblorders", "count(*)", "promocode='" . db_escape_string($promotioncode) . "' AND userid=" . (int) $_SESSION["uid"] . " AND status IN ('Pending','Active')");
        $orderCount = mysql_fetch_array($result);
        if (0 < $orderCount[0]) {
            $promoerrormessage = $_LANG["promoonceperclient"];
            return $promoerrormessage;
        }
    }
    $_SESSION["cart"]["promo"] = $promotioncode;
}
function CalcPromoDiscount($pid, $cycle, $fpamount, $recamount, $setupfee = 0)
{
    global $promo_data;
    global $currency;
    $id = $promo_data["id"];
    $promotionCode = $promo_data["code"];
    if (!$id) {
        return false;
    }
    $anyPromotionPermission = false;
    if (WHMCS\Session::get("adminid") && !defined("CLIENTAREA")) {
        $anyPromotionPermission = checkPermission("Use Any Promotion Code on Order", true);
    }
    if (!$anyPromotionPermission) {
        $newSignups = $promo_data["newsignups"];
        if ($newSignups && WHMCS\Session::get("uid")) {
            $previousOrders = get_query_val("tblorders", "COUNT(*)", array("userid" => WHMCS\Session::get("uid")));
            if (2 <= $previousOrders) {
                return false;
            }
        }
        $existingClient = $promo_data["existingclient"];
        $oncePerClient = $promo_data["onceperclient"];
        if ($existingClient) {
            $orderCount = get_query_val("tblorders", "count(*)", array("status" => "Active", "userid" => WHMCS\Session::get("uid")));
            if ($orderCount < 1) {
                return false;
            }
        }
        if ($oncePerClient) {
            $orderCount = get_query_val("tblorders", "count(*)", array("promocode" => $promotionCode, "userid" => WHMCS\Session::get("uid"), "status" => array("sqltype" => "IN", "values" => array("Pending", "Active"))));
            if (0 < $orderCount) {
                return false;
            }
        }
        $applyOnce = $promo_data["applyonce"];
        $promoApplied = $promo_data["promoapplied"];
        if ($applyOnce && $promoApplied) {
            return false;
        }
        $appliesTo = explode(",", $promo_data["appliesto"]);
        if (!in_array($pid, $appliesTo)) {
            return false;
        }
        $expireDate = $promo_data["expirationdate"];
        if ($expireDate != "0000-00-00") {
            $year = substr($expireDate, 0, 4);
            $month = substr($expireDate, 5, 2);
            $day = substr($expireDate, 8, 2);
            $validUntil = $year . $month . $day;
            $dayOfMonth = date("d");
            $monthNum = date("m");
            $yearNum = date("Y");
            $todaysDate = $yearNum . $monthNum . $dayOfMonth;
            if ($validUntil < $todaysDate) {
                return false;
            }
        }
        $cycles = $promo_data["cycles"];
        if ($cycles) {
            $cycles = explode(",", $cycles);
            if (!in_array($cycle, $cycles)) {
                return false;
            }
        }
        $maxUses = $promo_data["maxuses"];
        if ($maxUses) {
            $uses = $promo_data["uses"];
            if ($maxUses <= $uses) {
                return false;
            }
        }
        $requires = $promo_data["requires"];
        $requiresExisting = $promo_data["requiresexisting"];
        if ($requires) {
            $requires = explode(",", $requires);
            $hasRequired = false;
            if (is_array($_SESSION["cart"]["products"])) {
                foreach ($_SESSION["cart"]["products"] as $values) {
                    if (in_array($values["pid"], $requires)) {
                        $hasRequired = true;
                    }
                    if (is_array($values["addons"])) {
                        foreach ($values["addons"] as $addonid) {
                            if (in_array("A" . $addonid, $requires)) {
                                $hasRequired = true;
                            }
                        }
                    }
                }
            }
            if (is_array($_SESSION["cart"]["addons"])) {
                foreach ($_SESSION["cart"]["addons"] as $values) {
                    if (in_array("A" . $values["id"], $requires)) {
                        $hasRequired = true;
                    }
                }
            }
            if (is_array($_SESSION["cart"]["domains"])) {
                foreach ($_SESSION["cart"]["domains"] as $values) {
                    $domainParts = explode(".", $values["domain"], 2);
                    $tld = $domainParts[1];
                    if (in_array("D." . $tld, $requires)) {
                        $hasRequired = true;
                    }
                }
            }
            if (!$hasRequired && $requiresExisting) {
                $requiredProducts = $requiredAddons = array();
                $requiredDomains = "";
                foreach ($requires as $v) {
                    if (substr($v, 0, 1) == "A") {
                        $requiredAddons[] = substr($v, 1);
                    } else {
                        if (substr($v, 0, 1) == "D") {
                            $requiredDomains .= "domain LIKE '%" . substr($v, 1) . "' OR ";
                        } else {
                            $requiredProducts[] = $v;
                        }
                    }
                }
                if (count($requiredProducts)) {
                    $data = get_query_val("tblhosting", "COUNT(*)", array("userid" => WHMCS\Session::get("uid"), "packageid" => array("sqltype" => "IN", "values" => $requiredProducts), "domainstatus" => "Active"));
                    if ($data) {
                        $hasRequired = true;
                    }
                }
                if (count($requiredAddons)) {
                    $data = get_query_val("tblhostingaddons", "COUNT(*)", array("tblhosting.userid" => WHMCS\Session::get("uid"), "addonid" => array("sqltype" => "IN", "values" => $requiredAddons), "status" => "Active"), "", "", "", "tblhosting ON tblhosting.id=tblhostingaddons.hostingid");
                    if ($data) {
                        $hasRequired = true;
                    }
                }
                if ($requiredDomains) {
                    $data = get_query_val("tbldomains", "COUNT(*)", "userid='" . WHMCS\Session::get("uid") . "' AND status='Active' AND (" . substr($requiredDomains, 0, -4) . ")");
                    if ($data) {
                        $hasRequired = true;
                    }
                }
            }
            if (!$hasRequired) {
                return false;
            }
        }
    }
    $type = $promo_data["type"];
    $value = $promo_data["value"];
    $onetimediscount = 0;
    if ($type == "Percentage") {
        $onetimediscount = $fpamount * $value / 100;
    } else {
        if ($type == "Fixed Amount") {
            if ($currency["id"] != 1) {
                $promo_data["value"] = $value = convertCurrency($value, 1, $currency["id"]);
            }
            if ($fpamount < $value) {
                $onetimediscount = $fpamount;
            } else {
                $onetimediscount = $value;
            }
        } else {
            if ($type == "Price Override") {
                if ($currency["id"] != 1) {
                    $promo_data["value"] = convertCurrency($promo_data["value"], 1, $currency["id"]);
                }
                if (!isset($promo_data["priceoverride"])) {
                    $promo_data["priceoverride"] = $promo_data["value"];
                }
                $onetimediscount = $fpamount - $promo_data["priceoverride"];
            } else {
                if ($type == "Free Setup") {
                    $onetimediscount = $setupfee;
                    $promo_data["value"] += $setupfee;
                }
            }
        }
    }
    $recurringdiscount = 0;
    $recurring = $promo_data["recurring"];
    if ($recurring) {
        if ($type == "Percentage") {
            $recurringdiscount = $recamount * $value / 100;
        } else {
            if ($type == "Fixed Amount") {
                if ($recamount < $value) {
                    $recurringdiscount = $recamount;
                } else {
                    $recurringdiscount = $value;
                }
            } else {
                if ($type == "Price Override") {
                    $recurringdiscount = $recamount - $promo_data["priceoverride"];
                }
            }
        }
    }
    $onetimediscount = round($onetimediscount, 2);
    $recurringdiscount = round($recurringdiscount, 2);
    $promo_data["promoapplied"] = true;
    return array("onetimediscount" => $onetimediscount, "recurringdiscount" => $recurringdiscount, "applyonce" => $applyOnce);
}
function acceptOrder($orderid, $vars = array())
{
    $whmcs = WHMCS\Application::getInstance();
    if (!$orderid) {
        return false;
    }
    if (!is_array($vars)) {
        $vars = array();
    }
    $errors = array();
    run_hook("AcceptOrder", array("orderid" => $orderid));
    $result = select_query("tblhosting", "", array("orderid" => $orderid, "domainstatus" => "Pending"));
    while ($data = mysql_fetch_array($result)) {
        $productid = $data["id"];
        $userId = $data["userid"];
        $updateqry = array();
        if ($vars["products"][$productid]["server"]) {
            $updateqry["server"] = $vars["products"][$productid]["server"];
        }
        if ($vars["products"][$productid]["username"]) {
            $updateqry["username"] = $vars["products"][$productid]["username"];
        }
        if ($vars["products"][$productid]["password"]) {
            $updateqry["password"] = encrypt($vars["products"][$productid]["password"]);
        }
        if ($vars["api"]["serverid"]) {
            $updateqry["server"] = $vars["api"]["serverid"];
        }
        if ($vars["api"]["username"]) {
            $updateqry["username"] = $vars["api"]["username"];
        }
        if ($vars["api"]["password"]) {
            $updateqry["password"] = encrypt($vars["api"]["password"]);
        }
        if (count($updateqry)) {
            update_query("tblhosting", $updateqry, array("id" => $productid));
        }
        $result2 = select_query("tblhosting", "tblproducts.servertype,tblproducts.autosetup", array("tblhosting.id" => $productid), "", "", "", "tblproducts ON tblproducts.id=tblhosting.packageid");
        $data = mysql_fetch_array($result2);
        $module = $data["servertype"];
        $autosetup = $data["autosetup"];
        $autosetup = $autosetup ? true : false;
        $sendwelcome = $autosetup ? true : false;
        if (count($vars)) {
            $autosetup = $vars["products"][$productid]["runcreate"];
            $sendwelcome = $vars["products"][$productid]["sendwelcome"];
            if (isset($vars["api"]["autosetup"])) {
                $autosetup = $vars["api"]["autosetup"];
            }
            if (isset($vars["api"]["sendemail"])) {
                $sendwelcome = $vars["api"]["sendemail"];
            }
        }
        if ($autosetup) {
            if ($module) {
                logActivity("Running Module Create on Accept Pending Order", $userId);
                if (!isValidforPath($module)) {
                    $errMsg = "Invalid Server Module Name";
                    if ($whmcs->isApiRequest()) {
                        $apiresults = array("result" => "error", "message" => $errMsg);
                        return $apiresults;
                    }
                    throw new WHMCS\Exception\Fatal($errMsg);
                }
                require_once ROOTDIR . "/modules/servers/" . $module . "/" . $module . ".php";
                $moduleresult = ServerCreateAccount($productid);
                if ($moduleresult == "success") {
                    if ($sendwelcome && $module != "marketconnect") {
                        sendMessage("defaultnewacc", $productid);
                    }
                } else {
                    $errors[] = $moduleresult;
                }
            }
        } else {
            update_query("tblhosting", array("domainstatus" => "Active"), array("id" => $productid));
            if ($sendwelcome) {
                sendMessage("defaultnewacc", $productid);
            }
        }
    }
    $addons = WHMCS\Service\Addon::with("productAddon")->where("orderid", "=", $orderid)->where("status", "=", "Pending")->get();
    foreach ($addons as $addon) {
        $addonUniqueId = $addon->id;
        $serviceId = $addon->serviceId;
        $addonId = $addon->addonId;
        $addonBillingCycle = $addon->billingCycle;
        $addonStatus = $addon->status;
        $addonNextDueDate = $addon->nextDueDate;
        $addonName = $addon->name ?: $addon->productAddon->name;
        $autoSetup = $addonId && $addon->productAddon->autoActivate;
        $sendWelcomeEmail = $autoSetup && $addon->productAddon->welcomeEmailTemplateId;
        if (count($vars)) {
            $autoSetup = $vars["addons"][$addonUniqueId]["runcreate"];
            $sendWelcomeEmail = $vars["addons"][$addonUniqueId]["sendwelcome"];
            if (isset($vars["api"]["autosetup"])) {
                $autoSetup = $vars["api"]["autosetup"];
            }
            if (isset($vars["api"]["sendemail"])) {
                $sendWelcomeEmail = $vars["api"]["sendemail"];
            }
        }
        if ($sendWelcomeEmail && !$addon->productAddon->welcomeEmailTemplateId) {
            $sendWelcomeEmail = false;
        }
        if ($autoSetup) {
            $automationResult = "";
            $noModule = true;
            if ($addon->productAddon->module) {
                $automation = WHMCS\Service\Automation\AddonAutomation::factory($addon);
                $automationResult = $automation->runAction("CreateAccount");
                $noModule = false;
                if ($addon->productAddon->module == "marketconnect") {
                    $sendWelcomeEmail = false;
                }
            }
            if ($noModule || $automationResult) {
                if ($sendWelcomeEmail) {
                    sendMessage($addon->productAddon->welcomeEmailTemplate, $serviceId, array("addon_order_id" => $orderid, "addon_id" => $addonUniqueId, "addon_service_id" => $serviceId, "addon_addonid" => $addonId, "addon_billing_cycle" => $addonBillingCycle, "addon_status" => $addonStatus, "addon_nextduedate" => $addonNextDueDate, "addon_name" => $addonName));
                }
                $addon->status = "Active";
                $addon->save();
                if ($noModule) {
                    run_hook("AddonActivation", array("id" => $addonUniqueId, "userid" => $addon->clientId, "serviceid" => $serviceId, "addonid" => $addonId));
                }
            }
        } else {
            if ($sendWelcomeEmail) {
                sendMessage($addon->productAddon->welcomeEmailTemplate, $serviceId, array("addon_order_id" => $orderid, "addon_id" => $addonUniqueId, "addon_service_id" => $serviceId, "addon_addonid" => $addonId, "addon_billing_cycle" => $addonBillingCycle, "addon_status" => $addonStatus, "addon_nextduedate" => $addonNextDueDate, "addon_name" => $addonName));
            }
            $addon->status = "Active";
            $addon->save();
            run_hook("AddonActivated", array("id" => $addonUniqueId, "userid" => $addon->clientId, "serviceid" => $serviceId, "addonid" => $addonId));
        }
    }
    $result = select_query("tbldomains", "", array("orderid" => $orderid, "status" => "Pending"));
    while ($data = mysql_fetch_array($result)) {
        $domainid = $data["id"];
        $regtype = $data["type"];
        $domain = $data["domain"];
        $registrar = $data["registrar"];
        $emailmessage = $regtype == "Transfer" ? "Domain Transfer Initiated" : "Domain Registration Confirmation";
        if ($vars["domains"][$domainid]["registrar"]) {
            $registrar = $vars["domains"][$domainid]["registrar"];
        }
        if ($vars["api"]["registrar"]) {
            $registrar = $vars["api"]["registrar"];
        }
        if ($registrar) {
            update_query("tbldomains", array("registrar" => $registrar), array("id" => $domainid));
        }
        if ($vars["domains"][$domainid]["sendregistrar"]) {
            $sendregistrar = "on";
        }
        if ($vars["domains"][$domainid]["sendemail"]) {
            $sendemail = "on";
        }
        if (isset($vars["api"]["sendregistrar"])) {
            $sendregistrar = $vars["api"]["sendregistrar"];
        }
        if (isset($vars["api"]["sendemail"])) {
            $sendemail = $vars["api"]["sendemail"];
        }
        if ($sendregistrar && $registrar) {
            $params = array();
            $params["domainid"] = $domainid;
            $moduleresult = $regtype == "Transfer" ? RegTransferDomain($params) : RegRegisterDomain($params);
            if (!$moduleresult["error"]) {
                if ($sendemail) {
                    sendMessage($emailmessage, $domainid);
                }
            } else {
                $errors[] = $moduleresult["error"];
            }
        } else {
            update_query("tbldomains", array("status" => "Active"), array("id" => $domainid, "status" => "Pending"));
            if ($sendemail) {
                sendMessage($emailmessage, $domainid);
            }
        }
    }
    if (is_array($vars["renewals"])) {
        foreach ($vars["renewals"] as $domainid => $options) {
            if ($vars["renewals"][$domainid]["sendregistrar"]) {
                $sendregistrar = "on";
            }
            if ($vars["renewals"][$domainid]["sendemail"]) {
                $sendemail = "on";
            }
            if ($sendregistrar) {
                $params = array();
                $params["domainid"] = $domainid;
                $moduleresult = RegRenewDomain($params);
                if ($moduleresult["error"]) {
                    $errors[] = $moduleresult["error"];
                } else {
                    if ($sendemail) {
                        sendMessage("Domain Renewal Confirmation", $domainid);
                    }
                }
            } else {
                if ($sendemail) {
                    sendMessage("Domain Renewal Confirmation", $domainid);
                }
            }
        }
    }
    $result = select_query("tblorders", "userid,promovalue", array("id" => $orderid));
    $data = mysql_fetch_array($result);
    $userid = $data["userid"];
    $promovalue = $data["promovalue"];
    if (substr($promovalue, 0, 2) == "DR") {
        if ($vars["domains"][$domainid]["sendregistrar"]) {
            $sendregistrar = "on";
        }
        if (isset($vars["api"]["autosetup"])) {
            $sendregistrar = $vars["api"]["autosetup"];
        }
        if ($sendregistrar) {
            $params = array();
            $params["domainid"] = $domainid;
            $moduleresult = RegRenewDomain($params);
            if ($moduleresult["error"]) {
                $errors[] = $moduleresult["error"];
            } else {
                if ($sendemail) {
                    sendMessage("Domain Renewal Confirmation", $domainid);
                }
            }
        } else {
            if ($sendemail) {
                sendMessage("Domain Renewal Confirmation", $domainid);
            }
        }
    }
    update_query("tblupgrades", array("status" => "Completed"), array("orderid" => $orderid));
    if (!count($errors)) {
        update_query("tblorders", array("status" => "Active"), array("id" => $orderid));
        logActivity("Order Accepted - Order ID: " . $orderid, $userid);
    }
    return $errors;
}
function changeOrderStatus($orderid, $status, $cancelSubscription = false)
{
    $whmcs = WHMCS\Application::getInstance();
    if (!$orderid) {
        return false;
    }
    $orderid = (int) $orderid;
    if ($status == "Cancelled") {
        run_hook("CancelOrder", array("orderid" => $orderid));
    } else {
        if ($status == "Refunded") {
            run_hook("CancelAndRefundOrder", array("orderid" => $orderid));
            $status = "Cancelled";
        } else {
            if ($status == "Fraud") {
                run_hook("FraudOrder", array("orderid" => $orderid));
            } else {
                if ($status == "Pending") {
                    run_hook("PendingOrder", array("orderid" => $orderid));
                }
            }
        }
    }
    $orderStatus = WHMCS\Database\Capsule::table("tblorders")->where("id", $orderid)->value("status");
    update_query("tblorders", array("status" => $status), array("id" => $orderid));
    if ($status == "Cancelled" || $status == "Fraud") {
        $result = select_query("tblhosting", "tblhosting.id,tblhosting.userid,tblhosting.domainstatus,tblproducts.servertype,tblhosting.packageid,tblhosting.paymentmethod,tblproducts.stockcontrol,tblproducts.qty", array("orderid" => $orderid), "", "", "", "tblproducts ON tblproducts.id=tblhosting.packageid");
        while ($data = mysql_fetch_array($result)) {
            $userId = $data["userid"];
            if ($cancelSubscription) {
                try {
                    cancelSubscriptionForService($data["id"], $userId);
                } catch (Exception $e) {
                    WHMCS\Database\Capsule::table("tblorders")->where("id", $orderid)->update(array("status" => $orderStatus));
                    $errMessage = "subcancelfailed";
                    return $errMessage;
                }
            }
            $productid = $data["id"];
            $addons = WHMCS\Database\Capsule::table("tblhostingaddons")->where("hostingid", "=", $productid)->where("status", "!=", $status)->leftJoin("tbladdons", "tbladdons.id", "=", "tblhostingaddons.addonid")->get(array(WHMCS\Database\Capsule::raw("tblhostingaddons.id"), "userid", "status", "module"));
            $cancelResult = processAddonsCancelOrFraud($addons, $status);
            if (App::isApiRequest() && is_array($cancelResult)) {
                return $cancelResult;
            }
            $prodstatus = $data["domainstatus"];
            $module = $data["servertype"];
            $packageid = $data["packageid"];
            $stockcontrol = $data["stockcontrol"];
            $qty = $data["qty"];
            if ($module && ($prodstatus == "Active" || $prodstatus == "Suspended")) {
                logActivity("Running Module Terminate on Order Cancel", $userId);
                if (!isValidforPath($module)) {
                    $errMsg = "Invalid Server Module Name";
                    if ($whmcs->isApiRequest()) {
                        $apiresults = array("result" => "error", "message" => $errMsg);
                        return $apiresults;
                    }
                    throw new WHMCS\Exception\Fatal($errMsg);
                }
                require_once ROOTDIR . "/modules/servers/" . $module . "/" . $module . ".php";
                $moduleresult = ServerTerminateAccount($productid);
                if ($moduleresult == "success") {
                    update_query("tblhosting", array("domainstatus" => $status), array("id" => $productid));
                    if ($stockcontrol) {
                        update_query("tblproducts", array("qty" => "+1"), array("id" => $packageid));
                    }
                }
            } else {
                update_query("tblhosting", array("domainstatus" => $status), array("id" => $productid));
                if ($stockcontrol) {
                    update_query("tblproducts", array("qty" => "+1"), array("id" => $packageid));
                }
            }
        }
        $addons = WHMCS\Database\Capsule::table("tblhostingaddons")->where("orderid", "=", $orderid)->where("status", "!=", $status)->leftJoin("tbladdons", "tbladdons.id", "=", "tblhostingaddons.addonid")->get(array(WHMCS\Database\Capsule::raw("tblhostingaddons.id"), "userid", "status", "module"));
        $cancelResult = processAddonsCancelOrFraud($addons, $status);
        if (App::isApiRequest() && is_array($cancelResult)) {
            return $cancelResult;
        }
    } else {
        update_query("tblhosting", array("domainstatus" => $status), array("orderid" => $orderid));
        update_query("tblhostingaddons", array("status" => $status), array("orderid" => $orderid));
    }
    if ($status == "Pending") {
        $result = select_query("tbldomains", "id,type", array("orderid" => $orderid));
        while ($data = mysql_fetch_assoc($result)) {
            if ($data["type"] == "Transfer") {
                $status = "Pending Transfer";
            } else {
                $status = "Pending";
            }
            update_query("tbldomains", array("status" => $status), array("id" => $data["id"]));
        }
    } else {
        update_query("tbldomains", array("status" => $status), array("orderid" => $orderid));
    }
    $result = select_query("tblorders", "userid,invoiceid", array("id" => $orderid));
    $data = mysql_fetch_array($result);
    $userid = $data["userid"];
    $invoiceid = $data["invoiceid"];
    if ($invoiceid) {
        if ($status == "Pending") {
            update_query("tblinvoices", array("status" => "Unpaid"), array("id" => $invoiceid, "status" => "Cancelled"));
        } else {
            WHMCS\Database\Capsule::table("tblinvoices")->where("status", "=", "Unpaid")->where("id", $invoiceid)->update(array("status" => "Cancelled"));
            run_hook("InvoiceCancelled", array("invoiceid" => $invoiceid));
            if (!function_exists("refundCreditOnStatusChange")) {
                require ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "invoicefunctions.php";
            }
            refundCreditOnStatusChange($invoiceid, $status);
        }
    }
    logActivity("Order Status set to " . $status . " - Order ID: " . $orderid, $userid);
}
function cancelRefundOrder($orderid)
{
    $orderid = (int) $orderid;
    $result = select_query("tblorders", "invoiceid", array("id" => $orderid));
    $data = mysql_fetch_array($result);
    $invoiceid = $data["invoiceid"];
    if ($invoiceid) {
        $result = select_query("tblinvoices", "status", array("id" => $invoiceid));
        $data = mysql_fetch_array($result);
        $invoicestatus = $data["status"];
        if ($invoicestatus == "Paid") {
            $result = select_query("tblaccounts", "id", array("invoiceid" => $invoiceid));
            $data = mysql_fetch_array($result);
            $transid = $data["id"];
            $gatewayresult = refundInvoicePayment($transid, "", true);
            if ($gatewayresult == "manual") {
                return "manual";
            }
            if ($gatewayresult != "success") {
                return "refundfailed";
            }
            changeorderstatus($orderid, "Refunded");
        } else {
            if ($invoicestatus == "Refunded") {
                return "alreadyrefunded";
            }
            return "notpaid";
        }
    } else {
        return "noinvoice";
    }
}
function deleteOrder($orderid)
{
    if (!$orderid) {
        return false;
    }
    $orderid = (int) $orderid;
    run_hook("DeleteOrder", array("orderid" => $orderid));
    $result = select_query("tblorders", "userid,invoiceid", array("id" => $orderid));
    $data = mysql_fetch_array($result);
    if (!canOrderBeDeleted($orderid)) {
        return false;
    }
    $userid = $data["userid"];
    $invoiceid = $data["invoiceid"];
    delete_query("tblhostingconfigoptions", "relid IN (SELECT id FROM tblhosting WHERE orderid=" . $orderid . ")");
    delete_query("tblaffiliatesaccounts", "relid IN (SELECT id FROM tblhosting WHERE orderid=" . $orderid . ")");
    $select = "tblhosting.id AS relid, tblcustomfields.id AS fieldid";
    $where = array("tblhosting.orderid" => $orderid, "tblcustomfields.type" => "product");
    $join = "tblcustomfields ON tblcustomfields.relid=tblhosting.packageid";
    $result = select_query("tblhosting", $select, $where, "", "", "", $join);
    while ($data = mysql_fetch_array($result)) {
        $hostingid = $data["relid"];
        $customfieldid = $data["fieldid"];
        $deleteWhere = array("relid" => $hostingid, "fieldid" => $customfieldid);
        delete_query("tblcustomfieldsvalues", $deleteWhere);
    }
    delete_query("tblhosting", array("orderid" => $orderid));
    foreach (WHMCS\Service\Addon::where("orderid", $orderid)->get() as $serviceAddon) {
        $serviceAddon->delete();
    }
    delete_query("tbldomains", array("orderid" => $orderid));
    delete_query("tblupgrades", array("orderid" => $orderid));
    delete_query("tblorders", array("id" => $orderid));
    delete_query("tblinvoices", array("id" => $invoiceid));
    delete_query("tblinvoiceitems", array("invoiceid" => $invoiceid));
    logActivity("Deleted Order - Order ID: " . $orderid, $userid);
}
function getAddons($pid, array $addons = array())
{
    global $currency;
    $addonsArray = array();
    $billingCycles = array("monthly" => Lang::trans("orderpaymenttermmonthly"), "quarterly" => Lang::trans("orderpaymenttermquarterly"), "semiannually" => Lang::trans("orderpaymenttermsemiannually"), "annually" => Lang::trans("orderpaymenttermannually"), "biennially" => Lang::trans("orderpaymenttermbiennially"), "triennially" => Lang::trans("orderpaymenttermtriennially"));
    $orderAddons = WHMCS\Product\Addon::availableOnOrderForm($addons)->get();
    foreach ($orderAddons as $addon) {
        if (!in_array($pid, $addon->packages)) {
            continue;
        }
        $pricing = WHMCS\Database\Capsule::table("tblpricing")->where("type", "=", "addon")->where("currency", "=", $currency["id"])->where("relid", "=", $addon->id)->first();
        if (!$pricing && $addon->billingCycle != "Free") {
            continue;
        }
        $addonPricingString = "";
        $addonBillingCycles = array();
        switch ($addon->billingCycle) {
            case "recurring":
                foreach ($billingCycles as $system => $translated) {
                    $setupFeeField = substr($system, 0, 1) . "setupfee";
                    if ($pricing->{$system} < 0) {
                        continue;
                    }
                    $addonPrice = new WHMCS\View\Formatter\Price($pricing->{$system}, $currency) . " " . $translated;
                    if (0 < $pricing->{$setupFeeField}) {
                        $addonPrice .= " + " . new WHMCS\View\Formatter\Price($pricing->{$setupFeeField}, $currency) . " " . Lang::trans("ordersetupfee");
                    }
                    if (empty($addonPricingString)) {
                        $addonPricingString = $addonPrice;
                    }
                    $addonBillingCycles[$system] = array("setup" => 0 < $pricing->{$setupFeeField} ? new WHMCS\View\Formatter\Price($pricing->{$setupFeeField}, $currency) : NULL, "price" => new WHMCS\View\Formatter\Price($pricing->{$system}, $currency));
                }
                break;
            case "free":
            case "Free":
            case "Free Account":
                $addonPricingString = Lang::trans("orderfree");
                $addonBillingCycles["free"] = array("setup" => NULL, "price" => NULL);
                break;
            case "onetime":
            case "One Time":
            default:
                $system = str_replace(array(" ", "-"), "", strtolower($addon->billingCycle));
                $translated = Lang::trans("orderpaymentterm" . $system);
                $addonPrice = new WHMCS\View\Formatter\Price($pricing->monthly, $currency) . " " . $translated;
                if (0 < $pricing->msetupfee) {
                    $addonPrice .= " + " . formatCurrency($pricing->msetupfee) . " " . Lang::trans("ordersetupfee");
                }
                if (empty($addonPricingString)) {
                    $addonPricingString = $addonPrice;
                }
                $addonBillingCycles[$system] = array("setup" => new WHMCS\View\Formatter\Price($pricing->msetupfee, $currency), "price" => new WHMCS\View\Formatter\Price($pricing->monthly, $currency));
                break;
        }
        $checkbox = "<input type=\"checkbox\" name=\"addons[" . $addon->id . "]\" id=\"a" . $addon->id . "\"";
        $status = false;
        if (in_array($addon->id, $addons)) {
            $checkbox .= " checked=\"checked\"";
            $status = true;
        }
        $checkbox .= " />";
        $minPrice = 0;
        $minCycle = "onetime";
        foreach ($addonBillingCycles as $cycle => $price) {
            $minPrice = $price;
            $minCycle = $cycle;
            break;
        }
        $addonsArray[] = array("id" => $addon->id, "checkbox" => $checkbox, "name" => $addon->name, "description" => $addon->description, "pricing" => $addonPricingString, "billingCycles" => $addonBillingCycles, "minPrice" => $minPrice, "minCycle" => $minCycle, "status" => $status);
    }
    return $addonsArray;
}
function getAvailableOrderPaymentGateways($forceAll = false)
{
    $whmcs = App::self();
    $disabledGateways = array();
    $cartSession = WHMCS\Session::get("cart");
    if (isset($cartSession["products"])) {
        foreach ($cartSession["products"] as $values) {
            $groupDisabled = WHMCS\Database\Capsule::table("tblproductgroups")->join("tblproducts", "tblproducts.gid", "=", "tblproductgroups.id")->where("tblproducts.id", "=", $values["pid"])->first(array("disabledgateways"));
            $disabledGateways = array_merge(explode(",", $groupDisabled->disabledgateways), $disabledGateways);
        }
    }
    if (!function_exists("showPaymentGatewaysList")) {
        require ROOTDIR . "/includes/gatewayfunctions.php";
    }
    $userId = isset($_SESSION["uid"]) ? $_SESSION["uid"] : NULL;
    $gatewaysList = showPaymentGatewaysList(array_unique($disabledGateways), $userId, $forceAll);
    foreach ($gatewaysList as $module => $values) {
        $gatewaysList[$module]["payment_type"] = "Invoices";
        if (($values["type"] == "CC" || $values["type"] == "OfflineCC") && !isValidforPath($module)) {
            $errorMessage = "Invalid Gateway Module Name";
            if ($whmcs->isApiRequest()) {
                $apiResults = array("result" => "error", "message" => $errorMessage);
                return $apiResults;
            }
            throw new WHMCS\Exception\Fatal($errorMessage);
        }
        $gatewaysList[$module]["payment_type"] = "CreditCard";
        $gatewayInterface = WHMCS\Module\Gateway::factory($module);
        $gatewaysList[$module]["payment_type"] = "Invoices";
        $gatewaysList[$module]["show_local_cards"] = true;
        switch ($gatewayInterface->getWorkflowType()) {
            case WHMCS\Module\Gateway::WORKFLOW_ASSISTED:
                $gatewaysList[$module]["payment_type"] = "RemoteCreditCard";
                $gatewaysList[$module]["show_local_cards"] = false;
                break;
            case WHMCS\Module\Gateway::WORKFLOW_REMOTE:
            case WHMCS\Module\Gateway::WORKFLOW_TOKEN:
                $gatewaysList[$module]["payment_type"] = "RemoteCreditCard";
                break;
            case WHMCS\Module\Gateway::WORKFLOW_MERCHANT:
                $gatewaysList[$module]["payment_type"] = "CreditCard";
                break;
            case WHMCS\Module\Gateway::WORKFLOW_NOLOCALCARDINPUT:
            case WHMCS\Module\Gateway::WORKFLOW_THIRDPARTY:
                $gatewaysList[$module]["payment_type"] = "Invoices";
                $gatewaysList[$module]["show_local_cards"] = false;
                $gatewaysList[$module]["type"] = "Invoices";
                break;
        }
    }
    return $gatewaysList;
}
function canOrderBeDeleted($orderID, $orderStatus = "")
{
    if (!$orderID) {
        return false;
    }
    static $cancelledStatuses = NULL;
    if (!is_array($cancelledStatuses)) {
        $cancelledStatuses = WHMCS\Database\Capsule::table("tblorderstatuses")->where("showcancelled", 1)->pluck("title");
    }
    $orderID = (int) $orderID;
    if (!$orderStatus) {
        try {
            $orderDetails = WHMCS\Database\Capsule::table("tblorders")->find($orderID, array("tblorders.status as orderStatus"));
            if (!$orderDetails) {
                throw new WHMCS\Exception\Api\InvalidAction("Order Not Found");
            }
            $orderStatus = $orderDetails->orderStatus;
        } catch (Exception $e) {
            return false;
        }
    }
    if (in_array($orderStatus, $cancelledStatuses) || $orderStatus == "Fraud") {
        return true;
    }
    return false;
}
function processAddonsCancelOrFraud($addonCollection, $status)
{
    foreach ($addonCollection as $addon) {
        $addonId = $addon->id;
        $module = $addon->module;
        $addonStatus = $addon->status;
        if ($module && in_array($addonStatus, array("Active", "Suspended"))) {
            logActivity("Running Module Terminate on Order Cancel - Addon ID: " . $addonId, $addon->userid);
            $server = new WHMCS\Module\Server();
            if (!$server->loadByAddonId($addonId)) {
                $errMsg = "Invalid Server Module Name";
                if (App::isApiRequest()) {
                    $apiresults = array("result" => "error", "message" => $errMsg);
                    return $apiresults;
                }
                throw new WHMCS\Exception\Fatal($errMsg);
            }
            $moduleResult = $server->call("Terminate");
            if ($moduleResult == "success") {
                WHMCS\Database\Capsule::table("tblhostingaddons")->where("id", "=", $addonId)->update(array("status" => $status));
            }
        } else {
            WHMCS\Database\Capsule::table("tblhostingaddons")->where("id", "=", $addonId)->update(array("status" => $status));
        }
    }
    return "";
}

?>