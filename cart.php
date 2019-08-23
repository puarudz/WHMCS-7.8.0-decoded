<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("CLIENTAREA", true);
define("SHOPPING_CART", true);
require __DIR__ . "/init.php";
require ROOTDIR . "/includes/orderfunctions.php";
require ROOTDIR . "/includes/domainfunctions.php";
require ROOTDIR . "/includes/configoptionsfunctions.php";
require ROOTDIR . "/includes/customfieldfunctions.php";
require ROOTDIR . "/includes/clientfunctions.php";
require ROOTDIR . "/includes/invoicefunctions.php";
require ROOTDIR . "/includes/processinvoices.php";
require ROOTDIR . "/includes/gatewayfunctions.php";
require ROOTDIR . "/includes/modulefunctions.php";
require ROOTDIR . "/includes/ccfunctions.php";
require ROOTDIR . "/includes/cartfunctions.php";
$nameserverRegexPattern = "/^(?!\\-)(?:[a-zA-Z\\d\\-]{0,62}[a-zA-Z\\d]\\.){2,126}(?!\\d+)[a-zA-Z\\d]{1,63}\$/";
initialiseClientArea(Lang::trans("carttitle"), Lang::trans("carttitle"), "", "", "<a href=\"cart.php\">" . Lang::trans("carttitle") . "</a>");
checkContactPermission("orders");
$orderfrm = new WHMCS\OrderForm();
$orderFormTemplate = WHMCS\View\Template\OrderForm::factory();
$orderFormTemplateName = $orderFormTemplate->getName();
$whmcs = WHMCS\Application::getInstance();
$securityqans = $whmcs->get_req_var("securityqans");
$securityqid = $whmcs->get_req_var("securityqid");
$a = $whmcs->get_req_var("a");
$gid = $whmcs->get_req_var("gid");
$pid = $whmcs->get_req_var("pid");
if (substr($pid, 0, 1) == "b") {
    $bid = (int) substr($pid, 1);
    redir("a=add&bid=" . $bid);
} else {
    $pid = (int) $pid;
}
$aid = (int) $whmcs->get_req_var("aid");
$ajax = $whmcs->get_req_var("ajax");
$sld = $whmcs->get_req_var("sld");
$tld = $whmcs->get_req_var("tld");
$domains = $whmcs->get_req_var("domains");
$step = $whmcs->get_req_var("step");
$remote_ip = $whmcs->getRemoteIp();
$cartSession = $orderfrm->getCartData();
$productInfoKey = (int) $whmcs->get_req_var("i");
if ($productInfoKey < 0) {
    $productInfoKey = NULL;
}
$orderfrmtpl = $whmcs->get_config("OrderFormTemplate");
if (!isValidforPath($orderfrmtpl)) {
    exit("Invalid Order Form Template Name");
}
$orderconf = array();
$orderfrmconfig = ROOTDIR . "/templates/orderforms/" . $orderfrmtpl . "/config.php";
if (file_exists($orderfrmconfig)) {
    include $orderfrmconfig;
}
if (!$ajax && isset($orderconf["denynonajaxaccess"]) && is_array($orderconf["denynonajaxaccess"]) && in_array($a, $orderconf["denynonajaxaccess"])) {
    redir();
}
$orderform = true;
$nowrapper = false;
$errormessage = $allowcheckout = "";
$userid = isset($_SESSION["uid"]) ? $_SESSION["uid"] : "";
$currencyid = isset($_SESSION["currency"]) ? $_SESSION["currency"] : "";
$currency = getCurrency($userid, $currencyid);
$smartyvalues["currency"] = $currency;
$smartyvalues["ipaddress"] = $remote_ip;
$smartyvalues["ajax"] = $ajax ? true : false;
$smartyvalues["inShoppingCart"] = true;
$smartyvalues["action"] = $a;
$smartyvalues["numitemsincart"] = $orderfrm->getNumItemsInCart();
$smartyvalues["gid"] = "";
$smartyvalues["domain"] = "";
$captcha = new WHMCS\Utility\Captcha();
if (isset($_SESSION["cart"]["lastconfigured"])) {
    bundlesStepCompleteRedirect($_SESSION["cart"]["lastconfigured"]);
    unset($_SESSION["cart"]["lastconfigured"]);
}
if ($step == "fraudcheck") {
    $a = "fraudcheck";
}
if ($promocode = $whmcs->get_req_var("promocode")) {
    SetPromoCode($promocode);
}
if ($a == "empty") {
    unset($_SESSION["cart"]);
    redir("a=view");
}
if ($a == "startover") {
    unset($_SESSION["cart"]);
    redir();
}
if ($a == "remove" && !is_null($productInfoKey)) {
    if ($r == "p" && isset($_SESSION["cart"]["products"][$productInfoKey])) {
        unset($_SESSION["cart"]["products"][$productInfoKey]);
        $_SESSION["cart"]["products"] = array_values($_SESSION["cart"]["products"]);
    } else {
        if ($r == "a" && isset($_SESSION["cart"]["addons"][$productInfoKey])) {
            unset($_SESSION["cart"]["addons"][$productInfoKey]);
            $_SESSION["cart"]["addons"] = array_values($_SESSION["cart"]["addons"]);
        } else {
            if ($r == "d" && isset($_SESSION["cart"]["domains"][$productInfoKey])) {
                unset($_SESSION["cart"]["domains"][$productInfoKey]);
                $_SESSION["cart"]["domains"] = array_values($_SESSION["cart"]["domains"]);
            } else {
                if ($r == "r" && isset($_SESSION["cart"]["renewals"][$productInfoKey])) {
                    unset($_SESSION["cart"]["renewals"][$productInfoKey]);
                } else {
                    if ($r == "u" && isset($_SESSION["cart"]["upgrades"][$productInfoKey])) {
                        unset($_SESSION["cart"]["upgrades"][$productInfoKey]);
                        $_SESSION["cart"]["upgrades"] = array_values($_SESSION["cart"]["upgrades"]);
                    }
                }
            }
        }
    }
    if ($ajax) {
        $response = new WHMCS\Http\JsonResponse(array("success" => true, "r" => $r, "i" => $productInfoKey));
        $response->send();
        WHMCS\Terminus::getInstance()->doExit();
    }
    redir("a=view");
}
if ($a == "applypromo") {
    $promoerrormessage = SetPromoCode($promocode);
    echo $promoerrormessage;
    exit;
}
if ($a == "validateCaptcha") {
    check_token();
    $error = false;
    $alreadyComplete = WHMCS\Session::get("CaptchaComplete");
    if (!$alreadyComplete) {
        $validate = new WHMCS\Validate();
        $captcha = new WHMCS\Utility\Captcha();
        $captcha->validateAppropriateCaptcha(WHMCS\Utility\Captcha::FORM_DOMAIN_CHECKER, $validate);
        if ($validate->hasErrors()) {
            $error = Lang::trans(in_array($captcha, array("recaptcha", "invisible")) ? "googleRecaptchaIncorrect" : "captchaverifyincorrect");
            WHMCS\Session::set("CaptchaComplete", false);
        } else {
            WHMCS\Session::set("CaptchaComplete", true);
        }
    }
    $response = new WHMCS\Http\JsonResponse();
    $response->setData(array("error" => $error));
    $response->send();
    WHMCS\Terminus::getInstance()->doExit();
}
if ($a == "checkDomain") {
    (new WHMCS\Domain\Checker())->ajaxCheck();
    WHMCS\Terminus::getInstance()->doExit();
}
if ($a == "addToCart") {
    check_token();
    $domain = App::getFromRequest("domain");
    $domain = new WHMCS\Domains\Domain($domain);
    $whoisCheck = (bool) (int) App::getFromRequest("whois");
    $response = new WHMCS\Http\JsonResponse();
    if ($whoisCheck) {
        $check = new WHMCS\Domain\Checker();
        $check->cartDomainCheck($domain, array($domain->getDotTopLevel()));
        $searchResult = $check->getSearchResult()->offsetGet(0);
    }
    if (!$whoisCheck || isset($searchResult) && in_array($searchResult->getStatus(), array(WHMCS\Domains\DomainLookup\SearchResult::STATUS_NOT_REGISTERED, WHMCS\Domains\DomainLookup\SearchResult::STATUS_UNKNOWN))) {
        cartPreventDuplicateDomain($domain->getDomain(false));
        $tldPrice = getTLDPriceList($domain->getDotTopLevel());
        $domainArray = array("type" => "register", "domain" => $domain->getDomain(false), "regperiod" => key($tldPrice), "isPremium" => false);
        if (!App::getFromRequest("sideorder")) {
            $passedVariables = $_SESSION["cart"]["passedvariables"];
            unset($_SESSION["cart"]["passedvariables"]);
            if (isset($passedVariables["bitem"])) {
                $domainArray["bitem"] = $passedVariables["bitem"];
            }
            if (isset($passedVariables["bnum"])) {
                $domainArray["bnum"] = $passedVariables["bnum"];
            }
        }
        $premiumData = WHMCS\Session::get("PremiumDomains");
        if ((bool) (int) WHMCS\Config\Setting::getValue("PremiumDomains") && array_key_exists($domain->getDomain(), $premiumData)) {
            $premiumPrice = $premiumData[$domain->getDomain()];
            if (array_key_exists("register", $premiumPrice["cost"])) {
                $domainArray["isPremium"] = true;
                $domainArray["domainpriceoverride"] = $premiumPrice["markupPrice"][1]["register"];
                $domainArray["registrarCostPrice"] = $premiumPrice["cost"]["register"];
                $domainArray["registrarCurrency"] = $premiumPrice["markupPrice"][1]["currency"];
                $domainArray["domainpriceoverride"] = $domainArray["domainpriceoverride"]->toNumeric();
            }
            if (array_key_exists("renew", $premiumPrice["cost"])) {
                $domainArray["domainrenewoverride"] = $premiumPrice["markupPrice"][1]["renew"];
                $domainArray["registrarRenewalCostPrice"] = $premiumPrice["cost"]["renew"];
                $domainArray["registrarCurrency"] = $premiumPrice["markupPrice"][1]["currency"];
                $domainArray["domainrenewoverride"] = $domainArray["domainrenewoverride"]->toNumeric();
            } else {
                $domainArray["isPremium"] = false;
            }
        }
        $_SESSION["cart"]["domains"][] = $domainArray;
        if (isset($domainArray["bnum"])) {
            $_SESSION["cart"]["lastconfigured"] = array("type" => "domain", "i" => count($_SESSION["cart"]["domains"]) - 1);
        }
        $cart = new WHMCS\OrderForm();
        $response->setData(array("result" => "added", "period" => key($tldPrice), "cartCount" => $cart->getNumItemsInCart()));
    } else {
        $response->setData(array("result" => isset($searchResult) ? $searchResult->getStatus() : "unavailable"));
    }
    $response->send();
    WHMCS\Terminus::getInstance()->doExit();
}
if ($a == "addDomainTransfer") {
    check_token();
    $domain = App::getFromRequest("domain");
    $eppCode = App::getFromRequest("epp");
    $domain = new WHMCS\Domains\Domain($domain);
    $searchResult = array();
    try {
        if ($captcha->isEnabled() && $captcha->isEnabledForForm(WHMCS\Utility\Captcha::FORM_DOMAIN_CHECKER) && $captcha->getCaptchaType() !== "invisible" && WHMCS\Session::get("CaptchaComplete") !== true) {
            throw new Exception(Lang::trans("googleRecaptchaIncorrect"));
        }
        if ($captcha && $captcha->isEnabled() && $captcha->recaptcha->isInvisible()) {
            $validate = new WHMCS\Validate();
            $captcha->validateAppropriateCaptcha(WHMCS\Utility\Captcha::FORM_DOMAIN_CHECKER, $validate);
            if ($validate->hasErrors()) {
                throw new Exception($validate->getErrors()[0]);
            }
        }
        if ($CONFIG["AllowDomainsTwice"] && cartCheckIfDomainAlreadyOrdered($domain)) {
            throw new Exception(Lang::trans("ordererrordomainalreadyexists"));
        }
        if ($domain->getSecondLevel() && $domain->getTopLevel() && $domain->isValidDomainName($domain->getSecondLevel(), $domain->getDotTopLevel())) {
            $check = new WHMCS\Domain\Checker();
            $check->cartDomainCheck($domain, array($domain->getDotTopLevel()));
            $searchResult = $check->getSearchResult()->offsetGet(0);
            $searchResult = $searchResult instanceof WHMCS\Domains\DomainLookup\SearchResult ? $searchResult->toArray() : array();
            if ($searchResult["isRegistered"]) {
                $extensionConfig = WHMCS\Database\Capsule::table("tbldomainpricing")->where("extension", "=", $domain->getDotTopLevel())->first();
                if (is_null($extensionConfig)) {
                    throw new Exception(Lang::trans("orderForm.domainExtensionTransferNotSupported"));
                }
                $eppCodeRequired = $extensionConfig->eppcode;
                if ($eppCodeRequired && $eppCode || !$eppCodeRequired) {
                    $tldPrice = getTLDPriceList($domain->getDotTopLevel(), false, "transfer");
                    if (!$tldPrice) {
                        throw new Exception(Lang::trans("orderForm.domainExtensionTransferPricingNotConfigured"));
                    }
                    cartPreventDuplicateDomain($domain->getDomain(false));
                    $passedVariables = $_SESSION["cart"]["passedvariables"];
                    unset($_SESSION["cart"]["passedvariables"]);
                    $domainArray = array("type" => "transfer", "domain" => $domain->getDomain(false), "regperiod" => key($tldPrice), "eppcode" => $eppCode, "isPremium" => false);
                    if (isset($passedVariables["bitem"])) {
                        $domainArray["bitem"] = $passedVariables["bitem"];
                    }
                    if (isset($passedVariables["bnum"])) {
                        $domainArray["bnum"] = $passedVariables["bnum"];
                    }
                    $premiumData = WHMCS\Session::get("PremiumDomains");
                    if ((bool) (int) WHMCS\Config\Setting::getValue("PremiumDomains") && array_key_exists($domain->getDomain(), $premiumData)) {
                        $premiumPrice = $premiumData[$domain->getDomain()];
                        if (array_key_exists("transfer", $premiumPrice["cost"])) {
                            $domainArray["isPremium"] = true;
                            $domainArray["domainpriceoverride"] = $premiumPrice["markupPrice"][1]["transfer"];
                            $domainArray["registrarCostPrice"] = $premiumPrice["cost"]["transfer"];
                            $domainArray["registrarCurrency"] = $premiumPrice["markupPrice"][1]["currency"];
                            $domainArray["domainpriceoverride"] = $domainArray["domainpriceoverride"]->toNumeric();
                        }
                        if (array_key_exists("renew", $premiumPrice["cost"])) {
                            $domainArray["domainrenewoverride"] = $premiumPrice["markupPrice"][1]["renew"];
                            $domainArray["registrarRenewalCostPrice"] = $premiumPrice["cost"]["renew"];
                            $domainArray["registrarCurrency"] = $premiumPrice["markupPrice"][1]["currency"];
                            $domainArray["domainrenewoverride"] = $domainArray["domainrenewoverride"]->toNumeric();
                        } else {
                            $domainArray["isPremium"] = false;
                        }
                    }
                    $_SESSION["cart"]["domains"][] = $domainArray;
                    $searchResult = "added";
                } else {
                    $searchResult["epp"] = $eppCodeRequired ? true : false;
                }
            } else {
                $searchResult["unavailable"] = Lang::trans("ordererrordomainnotregistered");
            }
        } else {
            $searchResult["unavailable"] = Lang::trans("ordererrordomaininvalid");
        }
    } catch (Exception $e) {
        $searchResult = array("unavailable" => $e->getMessage());
    }
    $response = new WHMCS\Http\JsonResponse();
    $response->setData(array("result" => $searchResult));
    $response->send();
    WHMCS\Terminus::getInstance()->doExit();
}
if ($a == "updateDomainPeriod") {
    check_token();
    $domain = App::getFromRequest("domain");
    $period = App::getFromRequest("period");
    foreach ($_SESSION["cart"]["domains"] as $key => $domainItem) {
        if ($domainItem["domain"] == $domain) {
            $_SESSION["cart"]["domains"][$key]["regperiod"] = $period;
            break;
        }
    }
    $response = new WHMCS\Http\JsonResponse();
    $response->setData(calcCartTotals(false, false, $currency));
    $response->send();
    WHMCS\Terminus::getInstance()->doExit();
}
if ($a == "removepromo") {
    $_SESSION["cart"]["promo"] = "";
    if ($ajax) {
        exit;
    }
    redir("a=view");
}
if ($a == "setstateandcountry") {
    $_SESSION["cart"]["user"]["state"] = $state;
    $_SESSION["cart"]["user"]["country"] = $country;
    redir("a=view");
}
if ($a == "addUpSell") {
    check_token();
    $modalLoad = App::getFromRequest("select_modal");
    $productKey = App::getFromRequest("product_key");
    $returnData = array();
    $cartSession = WHMCS\Session::get("cart");
    static $addonMap = NULL;
    if (!is_array($addonMap)) {
        $addonMap = array();
    }
    if (!array_key_exists($productKey, $addonMap)) {
        $addonsCollection = WHMCS\Product\Addon::whereHas("moduleConfiguration", function ($query) use($productKey) {
            $query->where("setting_name", "=", "configoption1")->where("value", "=", $productKey);
        })->with("moduleConfiguration")->get();
        foreach ($addonsCollection as $addon) {
            if (!count($addon->moduleConfiguration)) {
                continue;
            }
            $addonMap[$productKey] = $addon->id;
            break;
        }
    }
    if ($modalLoad) {
        $productOptions = "";
        foreach ($cartSession["products"] as $infoKey => $product) {
            if (!in_array($addonMap[$productKey], $product["addons"])) {
                $productName = WHMCS\Product\Product::find($product["pid"])->name;
                $domain = empty($product["domain"]) ? Lang::trans("nodomain") : $product["domain"];
                $productOptions .= "<option value=\"" . $infoKey . "\">" . $productName . " - " . $domain . "</option>\r\n";
            }
        }
        $formToken = generate_token();
        $returnData["body"] = "<form action=\"cart.php\">\n    " . $formToken . "\n    <input type=\"hidden\" name=\"a\" value=\"addUpSell\" />\n    <input type=\"hidden\" name=\"product_key\" value=\"" . $productKey . "\" />\n    <p>Please select the product you would like to add this add-on to:</p>\n    <p><select name=\"item\" class=\"form-control\">" . $productOptions . "</select></p>\n</form>\n<script type=\"text/javascript\">\nvar modalAjax = jQuery('#modalAjax'),\n    needRefresh = false;\nmodalAjax.off('hidden.bs.modal');\nmodalAjax.on('hidden.bs.modal', function (e) {\n    jQuery('.promo-cart .btn-add').find('span.loading').hide().end()\n        .prop('disabled', false);\n    if (needRefresh) {\n        window.location.reload(true);\n    }\n})\n</script>";
    } else {
        $cartProducts = $cartSession["products"];
        foreach ($cartProducts as $key => $data) {
            if (in_array($addonMap[$productKey], $data["addons"])) {
                unset($cartProducts[$key]);
            }
        }
        if (1 < count($cartProducts) && !App::isInRequest("item")) {
            $returnData["modal"] = "cart.php?select_modal=true&a=addUpSell&product_key=" . $productKey . generate_token("link");
            $returnData["modalTitle"] = Lang::trans("cartproductselection");
            $returnData["modalSubmit"] = Lang::trans("orderForm.add");
            $returnData["modelSubmitId"] = "btnAddUpSell";
        } else {
            reset($cartProducts);
            $productItemKey = key($cartProducts);
            if (App::isInRequest("item")) {
                $productItemKey = App::getFromRequest("item");
                $returnData["dismiss"] = true;
            }
            $marketConnectCart = new WHMCS\MarketConnect\Promotion\Helper\Cart();
            if (isset($cartSession["products"][$productItemKey]) && !in_array($addonMap[$productKey], $cartSession["products"][$productItemKey]["addons"])) {
                $toAdd = true;
                foreach ($cartSession["products"][$productItemKey]["addons"] as &$addonId) {
                    if ($marketConnectCart->isUpSellForAddon($addonId, $addonMap[$productKey])) {
                        $addonId = $addonMap[$productKey];
                        $toAdd = false;
                    }
                }
                if ($toAdd) {
                    $cartSession["products"][$productItemKey]["addons"][] = $addonMap[$productKey];
                }
                WHMCS\Session::set("cart", $cartSession);
            }
            $returnData["done"] = true;
        }
    }
    $response = new WHMCS\Http\JsonResponse();
    $response->setData($returnData);
    $response->send();
    WHMCS\Terminus::getInstance()->doExit();
}
if ((!$a || $a == "add" && $pid) && ($sld && $tld && !is_array($sld) || is_array($domains))) {
    if (is_array($domains)) {
        $tempdomain = $domains[0];
        $tempdomain = explode(".", $tempdomain, 2);
        $sld = $tempdomain[0];
        $tld = "." . $tempdomain[1];
    }
    $_SESSION["cartdomain"]["sld"] = $sld;
    $_SESSION["cartdomain"]["tld"] = $tld;
}
$productgroups = $orderfrm->getProductGroups();
$smarty->assign("productgroups", $productgroups);
$smartyvalues["registerdomainenabled"] = (bool) WHMCS\Config\Setting::getValue("AllowRegister");
$smartyvalues["transferdomainenabled"] = (bool) WHMCS\Config\Setting::getValue("AllowTransfer");
$smartyvalues["renewalsenabled"] = (bool) WHMCS\Config\Setting::getValue("EnableDomainRenewalOrders");
if (!$a) {
    if ($gid == "domains") {
        redir("a=add&domain=register");
    } else {
        if ($gid == "registerdomain") {
            redir("a=add&domain=register");
        } else {
            if ($gid == "transferdomain") {
                redir("a=add&domain=transfer");
            } else {
                if ($gid == "viewcart") {
                    redir("a=view");
                } else {
                    if ($gid == "addons") {
                        if (!$_SESSION["uid"]) {
                            $orderform = false;
                            include "login.php";
                        }
                        $smartyvalues["gid"] = "addons";
                        $templatefile = "addons";
                        $where = array();
                        $where["userid"] = $_SESSION["uid"];
                        $where["domainstatus"] = "Active";
                        if ($pid) {
                            $where["tblhosting.id"] = $pid;
                        }
                        $productids = array();
                        $result = select_query("tblhosting", "tblhosting.id,billingcycle,domain,packageid,tblproducts.name as product_name", $where, "", "", "", "tblproducts ON tblproducts.id=tblhosting.packageid");
                        while ($data = mysql_fetch_array($result)) {
                            $productstoids[$data["packageid"]][] = array("id" => $data["id"], "product" => WHMCS\Product\Product::getProductName($data["packageid"], $data["product_name"]), "domain" => $data["domain"]);
                            if (!in_array($data["packageid"], $productids)) {
                                $productids[] = $data["packageid"];
                            }
                        }
                        $addonids = array();
                        $result = select_query("tbladdons", "id,hidden,packages", "");
                        while ($data = mysql_fetch_array($result)) {
                            if ($data["hidden"]) {
                                continue;
                            }
                            $id = $data["id"];
                            $packages = $data["packages"];
                            $packages = explode(",", $packages);
                            foreach ($productids as $productid) {
                                if (in_array($productid, $productids) && !in_array($id, $addonids)) {
                                    $addonids[] = $id;
                                }
                            }
                        }
                        $addons = array();
                        if (count($addonids)) {
                            $addonModels = WHMCS\Product\Addon::availableOnOrderForm($addonids)->orderBy("weight", "ASC")->orderBy("name", "ASC")->get();
                            foreach ($addonModels as $addonModel) {
                                $addonid = $addonModel->id;
                                $packages = $addonModel->packages;
                                $name = $addonModel->name;
                                $description = $addonModel->description;
                                $billingcycle = WHMCS\ClientArea::getRawStatus($addonModel->billingCycle);
                                $free = false;
                                $result2 = select_query("tblpricing", "", array("type" => "addon", "currency" => $currency["id"], "relid" => $addonid));
                                $data = mysql_fetch_array($result2);
                                switch ($billingcycle) {
                                    case "free":
                                    case "freeaccount":
                                        $free = true;
                                        break;
                                    case "onetime":
                                    case "monthly":
                                    case "quarterly":
                                    case "semiannually":
                                    case "annually":
                                    case "biennially":
                                    case "triennially":
                                        $setupfee = $data["msetupfee"];
                                        $recurring = $data["monthly"];
                                        break;
                                    case "recurring":
                                    default:
                                        if (0 <= $data["monthly"]) {
                                            $setupfee = $data["msetupfee"];
                                            $recurring = $data["monthly"];
                                            $billingcycle = "monthly";
                                        } else {
                                            if (0 <= $data["quarterly"]) {
                                                $setupfee = $data["qsetupfee"];
                                                $recurring = $data["quarterly"];
                                                $billingcycle = "quarterly";
                                            } else {
                                                if (0 <= $data["semiannually"]) {
                                                    $setupfee = $data["ssetupfee"];
                                                    $recurring = $data["semiannually"];
                                                    $billingcycle = "semiannually";
                                                } else {
                                                    if (0 <= $data["annually"]) {
                                                        $setupfee = $data["asetupfee"];
                                                        $recurring = $data["annually"];
                                                        $billingcycle = "annually";
                                                    } else {
                                                        if (0 <= $data["biennially"]) {
                                                            $setupfee = $data["bsetupfee"];
                                                            $recurring = $data["biennially"];
                                                            $billingcycle = "biennially";
                                                        } else {
                                                            if (0 <= $data["triennially"]) {
                                                                $setupfee = $data["tsetupfee"];
                                                                $recurring = $data["triennially"];
                                                                $billingcycle = "triennially";
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        break;
                                }
                                $setupfee = empty($setupfee) || $setupfee == "0.00" ? "" : new WHMCS\View\Formatter\Price($setupfee, $currency);
                                $billingcycle = $_LANG["orderpaymentterm" . $billingcycle];
                                $packageids = array();
                                foreach ($packages as $packageid) {
                                    $thisaddonspackages = "";
                                    $thisaddonspackages = $productstoids[$packageid];
                                    if ($thisaddonspackages) {
                                        $packageids = array_merge($packageids, $thisaddonspackages);
                                    }
                                }
                                if (count($packageids)) {
                                    $addons[] = array("id" => $addonid, "name" => $name, "description" => $description, "free" => $free, "setupfee" => $setupfee, "recurringamount" => new WHMCS\View\Formatter\Price($recurring, $currency), "billingcycle" => $billingcycle, "productids" => $packageids);
                                }
                            }
                        }
                        $smarty->assign("addons", $addons);
                        $smarty->assign("noaddons", count($addons) <= 0);
                    } else {
                        if ($gid == "renewals") {
                            if (!$CONFIG["EnableDomainRenewalOrders"]) {
                                redir("", "clientarea.php");
                            }
                            if (!$_SESSION["uid"]) {
                                $orderform = false;
                                include "login.php";
                            }
                            try {
                                WHMCS\View\Template\OrderForm::factory("domain-renewals.tpl", $orderFormTemplateName);
                                header("Location: " . routePath("cart-domain-renewals"));
                                WHMCS\Terminus::getInstance()->doExit();
                            } catch (WHMCS\Exception\View\TemplateNotFound $e) {
                            } catch (Exception $e) {
                                App::redirect("clientarea.php");
                            }
                            $smartyvalues["gid"] = "renewals";
                            $templatefile = "domainrenewals";
                            $smartyvalues["productgroups"] = $productgroups;
                            $renewals = WHMCS\Domains::getRenewableDomains(WHMCS\Session::get("uid"));
                            $smartyvalues["renewals"] = $renewals["renewals"];
                        } else {
                            $templatefile = "products";
                            $smartyvalues["showSidebarToggle"] = (bool) WHMCS\Config\Setting::getValue("OrderFormSidebarToggle");
                            $hookResponses = run_hook("ShoppingCartViewCategoryAboveProductsOutput", array("cart" => WHMCS\Session::get("cart")));
                            $smartyvalues["hookAboveProductsOutput"] = $hookResponses;
                            $hookResponses = run_hook("ShoppingCartViewCategoryBelowProductsOutput", array("cart" => WHMCS\Session::get("cart")));
                            $smartyvalues["hookBelowProductsOutput"] = $hookResponses;
                            if ($pid) {
                                $result = select_query("tblproducts", "id,gid", array("id" => $pid));
                                $data = mysql_fetch_array($result);
                                $pid = $data["id"];
                                $gid = $data["gid"];
                                $smartyvalues["pid"] = $pid;
                            } else {
                                if (!$gid) {
                                    $gid = $productgroups[0]["gid"];
                                }
                            }
                            $productGroup = WHMCS\Product\Group::find($gid);
                            $groupinfo = $orderfrm->getProductGroupInfo($gid);
                            if (count($productgroups) && !$groupinfo) {
                                redir();
                            }
                            $orderFormTemplateName = $groupinfo["orderfrmtpl"] == "" ? $orderFormTemplateName : $groupinfo["orderfrmtpl"];
                            $smartyvalues["gid"] = $groupinfo["id"];
                            $smartyvalues["productGroup"] = $productGroup;
                            $smartyvalues["groupname"] = WHMCS\Product\Group::getGroupName($groupinfo["id"], $productGroup->name);
                            $products = array();
                            try {
                                $products = $orderfrm->getProducts($productGroup, true, true);
                            } catch (Exception $e) {
                                $smartyvalues["errormessage"] = Lang::trans("orderForm.error" . $e->getMessage());
                            }
                            $regex = "/[0-9]*\\.?[0-9]+/";
                            $featureValues = array();
                            foreach ($products as $productKey => $product) {
                                foreach ($product["features"] as $featureKey => $feature) {
                                    $matches = array();
                                    if (preg_match($regex, $feature, $matches)) {
                                        $featureAmount = $matches[0];
                                    } else {
                                        $featureAmount = PHP_INT_MAX;
                                    }
                                    $featureValues[$featureKey][$productKey] = $featureAmount;
                                    asort($featureValues[$featureKey]);
                                }
                            }
                            foreach ($featureValues as $featureKey => $feature) {
                                if (!in_array(PHP_INT_MAX, $feature)) {
                                    continue;
                                }
                                $highestValue = 1;
                                foreach ($feature as $value) {
                                    if ($value != PHP_INT_MAX) {
                                        $highestValue = $value;
                                    } else {
                                        break;
                                    }
                                }
                                $featureValues[$featureKey] = str_replace(PHP_INT_MAX, $highestValue * 2, $feature);
                            }
                            foreach ($featureValues as $featureKey => $feature) {
                                list($highestValue) = array_slice($feature, -1);
                                foreach ($feature as $productKey => $value) {
                                    $featureValues[$featureKey][$productKey] = (int) ($value / $highestValue * 100);
                                }
                            }
                            $smartyvalues["featurePercentages"] = $featureValues;
                            $smartyvalues["products"] = $products;
                            $smartyvalues["productscount"] = count($products);
                        }
                    }
                }
            }
        }
    }
}
if ($a == "add") {
    if ($pid) {
        $templatefile = "configureproductdomain";
        $productinfo = $orderfrm->setPid($pid);
        if (!$productinfo) {
            redir();
        }
        $orderFormTemplateName = $productinfo["orderfrmtpl"] == "" ? $orderFormTemplateName : $productinfo["orderfrmtpl"];
        $_SESSION["cart"]["domainoptionspid"] = $productinfo["pid"];
        $smartyvalues["productinfo"] = $productinfo;
        $smartyvalues["pid"] = $productinfo["pid"];
        $pid = $smartyvalues["pid"];
        $type = $productinfo["type"];
        $subdomains = $productinfo["subdomain"];
        $freedomain = $productinfo["freedomain"];
        $freedomaintlds = $productinfo["freedomaintlds"];
        $showdomainoptions = $productinfo["showdomainoptions"];
        $stockcontrol = $productinfo["stockcontrol"];
        $qty = $productinfo["qty"];
        $module = $productinfo["module"];
        if ($stockcontrol && $qty <= 0) {
            $templatefile = "error";
            $smartyvalues["errortitle"] = $_LANG["outofstock"];
            $smartyvalues["errormsg"] = $_LANG["outofstockdescription"];
            outputClientArea($templatefile, $ajax);
            exit;
        }
        $passedvariables = $_SESSION["cart"]["passedvariables"];
        $bundle = false;
        if (is_array($passedvariables) && (isset($passedvariables["bnum"]) || isset($passedvariables["bitem"]))) {
            $bundle = true;
        }
        if ($module == "marketconnect" && !$bundle) {
            App::redirectToRoutePath("store-order", array(), array("pid" => $pid));
        }
        $passedvariables = array();
        $skipconfig = $whmcs->get_req_var("skipconfig");
        $billingcycle = $whmcs->get_req_var("billingcycle");
        $configoption = $whmcs->get_req_var("configoption");
        $customfield = $whmcs->get_req_var("customfield");
        $addons = $whmcs->get_req_var("addons");
        if ($skipconfig) {
            $passedvariables["skipconfig"] = $skipconfig;
        }
        if ($billingcycle) {
            $passedvariables["billingcycle"] = $billingcycle;
        }
        if ($configoption) {
            $passedvariables["configoption"] = $configoption;
        }
        if ($customfield) {
            $passedvariables["customfield"] = $customfield;
        }
        if ($addons) {
            if (!is_array($addons)) {
                $passedvariables["addons"] = explode(",", $addons);
            } else {
                foreach ($addons as $k => $v) {
                    $passedvariables["addons"][] = trim($k);
                }
            }
        }
        $customFields = getCustomFields("product", $productinfo["pid"], "", true);
        foreach ($customFields as $customField) {
            $cfValue = $whmcs->get_req_var("cf_" . $customField["textid"]);
            if ($cfValue) {
                $passedvariables["customfield"][$customField["id"]] = $cfValue;
            }
        }
        if (count($passedvariables)) {
            $_SESSION["cart"]["passedvariables"] = $passedvariables;
        }
        if (isset($orderconf["directpidstep1"]) && $orderconf["directpidstep1"] && !$ajax) {
            redir("pid=" . $pid);
        }
        $domainselect = $whmcs->get_req_var("domainselect");
        $domainoption = $whmcs->get_req_var("domainoption");
        if ($domainselect && !$domains && $ajax && $domainoption != "incart" && $domainoption != "owndomain" && $domainoption != "subdomain") {
            exit("nodomains");
        }
        $productconfig = false;
        if ($orderfrm->getProductInfo("showdomainoptions") && !$domains) {
            $cartproducts = $orderfrm->getCartDataByKey("products");
            $cartdomains = $orderfrm->getCartDataByKey("domains");
            $incartdomains = array();
            if ($cartdomains) {
                foreach ($cartdomains as $cartdomain) {
                    $domainname = $cartdomain["domain"];
                    if ($cartproducts) {
                        foreach ($cartproducts as $cartproduct) {
                            if ($cartproduct["domain"] == $domainname) {
                                $domainname = "";
                            }
                        }
                    }
                    if ($domainname) {
                        $incartdomains[] = $domainname;
                    }
                }
            }
            if (!in_array($domainoption, array("incart", "register", "transfer", "owndomain", "subdomain"))) {
                $domainoption = "";
            }
            if ($incartdomains && !$domainoption) {
                $domainoption = "incart";
            }
            if ($CONFIG["AllowRegister"] && !$domainoption) {
                $domainoption = "register";
            }
            if ($CONFIG["AllowTransfer"] && !$domainoption) {
                $domainoption = "transfer";
            }
            if ($CONFIG["AllowOwnDomain"] && !$domainoption) {
                $domainoption = "owndomain";
            }
            if (count($subdomains) && !$domainoption) {
                $domainoption = "subdomain";
            }
            $registerTlds = getTLDList();
            $transferTlds = getTLDList("transfer");
            $smartyvalues["listtld"] = $registerTlds;
            $smartyvalues["registertlds"] = $registerTlds;
            $smartyvalues["transfertlds"] = $transferTlds;
            $smartyvalues["showdomainoptions"] = true;
            $smartyvalues["domainoption"] = $domainoption;
            $smartyvalues["registerdomainenabled"] = $CONFIG["AllowRegister"];
            $smartyvalues["transferdomainenabled"] = $CONFIG["AllowTransfer"];
            $smartyvalues["owndomainenabled"] = $CONFIG["AllowOwnDomain"];
            $smartyvalues["subdomain"] = isset($subdomains[0]) ? $subdomains[0] : "";
            $smartyvalues["subdomains"] = $subdomains;
            $smartyvalues["incartdomains"] = $incartdomains;
            $smartyvalues["availabilityresults"] = array();
            $smartyvalues["freedomaintlds"] = $freedomain && !empty($freedomaintlds) ? implode(", ", $freedomaintlds) : "";
            $smarty->assign("spotlightTlds", getSpotlightTldsWithPricing());
            if (is_array($tld)) {
                if ($domainoption == "register") {
                    $tld = $tld[0];
                    $sld = $sld[0];
                } else {
                    if ($domainoption == "transfer") {
                        $tld = $tld[1];
                        $sld = $sld[1];
                    } else {
                        if ($domainoption == "owndomain") {
                            $tld = $tld[2];
                            $sld = $sld[2];
                        } else {
                            if ($domainoption == "subdomain") {
                                if (!$subdomains[$tld[3]]) {
                                    $tld[3] = 0;
                                }
                                $tld = $subdomains[$tld[3]];
                                $sld = $sld[3];
                            } else {
                                if ($domainoption == "incart") {
                                    $incartdomain = explode(".", $incartdomain, 2);
                                    list($sld, $tld) = $incartdomain;
                                }
                            }
                        }
                    }
                }
            }
            $nocontinue = false;
            if (!$sld && !$tld && isset($_SESSION["cartdomain"]["sld"]) && isset($_SESSION["cartdomain"]["tld"]) && in_array($_SESSION["cartdomain"]["tld"], $registerTlds)) {
                $sld = $_SESSION["cartdomain"]["sld"];
                $tld = $_SESSION["cartdomain"]["tld"];
                $nocontinue = true;
                unset($_SESSION["cartdomain"]);
            }
            $sld = cleanDomainInput($sld);
            $tld = cleanDomainInput($tld);
            if (substr($sld, -1) == ".") {
                $sld = substr($sld, 0, -1);
            }
            if ($sld && $tld && ($domainoption == "register" && !in_array($tld, $registerTlds) || $domainoption == "transfer" && !in_array($tld, $transferTlds))) {
                $sld = "";
                $tld = "";
            }
            if ($tld && substr($tld, 0, 1) != ".") {
                $tld = "." . $tld;
            }
            $smartyvalues["sld"] = $sld;
            $smartyvalues["tld"] = $tld;
            if (isset($_REQUEST["sld"]) || isset($_REQUEST["tld"]) || $sld) {
                $validate = new WHMCS\Validate();
                if ($domainoption == "subdomain") {
                    if (!is_array($BannedSubdomainPrefixes)) {
                        $BannedSubdomainPrefixes = array();
                    }
                    if ($whmcs->get_config("BannedSubdomainPrefixes")) {
                        $bannedprefixes = $whmcs->get_config("BannedSubdomainPrefixes");
                        $bannedprefixes = explode(",", $bannedprefixes);
                        $BannedSubdomainPrefixes = array_merge($BannedSubdomainPrefixes, $bannedprefixes);
                    }
                    if (!WHMCS\Domains\Domain::isValidDomainName($sld, ".com")) {
                        $errormessage .= "<li>" . $_LANG["ordererrordomaininvalid"];
                    } else {
                        if (in_array($sld, $BannedSubdomainPrefixes)) {
                            $errormessage .= "<li>" . $_LANG["ordererrorsbudomainbanned"];
                        } else {
                            $result = select_query("tblhosting", "COUNT(*)", "domain='" . db_escape_string($sld . $tld) . "' AND (domainstatus!='Terminated' AND domainstatus!='Cancelled' AND domainstatus!='Fraud')");
                            $data = mysql_fetch_array($result);
                            $subchecks = $data[0];
                            if ($subchecks) {
                                $errormessage = "<li>" . $_LANG["ordererrorsubdomaintaken"];
                            }
                        }
                    }
                    run_validate_hook($validate, "CartSubdomainValidation", array("subdomain" => $sld, "domain" => $tld));
                } else {
                    if (!WHMCS\Domains\Domain::isValidDomainName($sld, $tld) || $domainoption == "owndomain" && !WHMCS\Domains\Domain::isSupportedTld($tld)) {
                        $errormessage .= $_LANG["ordererrordomaininvalid"];
                    }
                    if (($domainoption == "register" || $domainoption == "transfer") && $CONFIG["AllowDomainsTwice"]) {
                        if (substr($tld, 0, 1) != ".") {
                            $tld = "." . $tld;
                        }
                        $domainObject = new WHMCS\Domains\Domain($sld . $tld);
                        if (cartCheckIfDomainAlreadyOrdered($domainObject)) {
                            $errormessage = "<li>" . $_LANG["ordererrordomainalreadyexists"];
                        }
                    } else {
                        if ($domainoption == "owndomain" && $CONFIG["AllowDomainsTwice"]) {
                            $result = select_query("tblhosting", "domain", "domain='" . db_escape_string($sld . $tld) . "' AND (domainstatus!='Terminated' AND domainstatus!='Cancelled' AND domainstatus!='Fraud')");
                            while ($data = mysql_fetch_array($result)) {
                                if ($data[0] == $sld . $tld) {
                                    $errormessage = "<li>" . $_LANG["ordererrordomainalreadyexists"];
                                    break;
                                }
                            }
                        }
                    }
                    run_validate_hook($validate, "ShoppingCartValidateDomain", array("domainoption" => $domainoption, "sld" => $sld, "tld" => $tld));
                }
                if ($validate->hasErrors()) {
                    $errormessage .= $validate->getHTMLErrorOutput();
                }
                $smartyvalues["errormessage"] = $errormessage;
            }
            if (!$errormessage && !$nocontinue) {
                if (in_array($domainoption, array("register", "transfer")) && $sld && $tld) {
                    $check = new WHMCS\Domain\Checker();
                    $check->cartDomainCheck(new WHMCS\Domains\Domain($sld), array($tld));
                    $check->populateCartWithDomainSmartyVariables($domainoption, $smartyvalues);
                    $smartyvalues["domains"] = $domains;
                }
                if (in_array($domainoption, array("owndomain", "subdomain", "incart")) && $sld && $tld) {
                    $smartyvalues["showdomainoptions"] = false;
                    $domains = array($sld . $tld);
                    $productconfig = true;
                }
            }
        } else {
            $productconfig = true;
        }
        if ($productconfig) {
            $passedvariables = $_SESSION["cart"]["passedvariables"];
            unset($_SESSION["cart"]["passedvariables"]);
            cartPreventDuplicateProduct($domains[0]);
            $prodarray = array("pid" => $pid, "domain" => $domains[0], "billingcycle" => $passedvariables["billingcycle"], "configoptions" => $passedvariables["configoption"], "customfields" => $passedvariables["customfield"], "addons" => $passedvariables["addons"], "server" => "", "noconfig" => true);
            if (isset($passedvariables["bnum"])) {
                $prodarray["bnum"] = $passedvariables["bnum"];
            }
            if (isset($passedvariables["bitem"])) {
                $prodarray["bitem"] = $passedvariables["bitem"];
            }
            $updatedexistingqty = false;
            if ($productinfo["allowqty"]) {
                foreach ($_SESSION["cart"]["products"] as &$cart_prod) {
                    if ($pid == $cart_prod["pid"]) {
                        if (empty($cart_prod["qty"])) {
                            $cart_prod["qty"] = 1;
                        }
                        if (empty($cart_prod["noconfig"])) {
                            $cart_prod["qty"]++;
                            if ($stockcontrol && $qty < $cart_prod["qty"]) {
                                $cart_prod["qty"] = $qty;
                            }
                            $updatedexistingqty = true;
                        }
                        break;
                    }
                }
            }
            if (!$updatedexistingqty) {
                $_SESSION["cart"]["products"][] = $prodarray;
            }
            $newprodnum = count($_SESSION["cart"]["products"]) - 1;
            if ($_SESSION["cart"]["products"][$newprodnum]["pid"] != $pid) {
                $newprodnum = 0;
                $index = count($_SESSION["cart"]["products"]);
                while (0 < $index) {
                    $product = $_SESSION["cart"]["products"][--$index];
                    if ($product["pid"] == $pid) {
                        $newprodnum = $index;
                        break;
                    }
                }
            }
            if ($domainoption == "register" || $domainoption == "transfer") {
                foreach ($domains as $domainname) {
                    cartPreventDuplicateDomain($domainname);
                    $regperiod = $domainsregperiod[$domainname];
                    $domainparts = explode(".", $domainname, 2);
                    $temppricelist = getTLDPriceList("." . $domainparts[1]);
                    if (!isset($temppricelist[$regperiod][$domainoption])) {
                        if (is_array($regperiods)) {
                            foreach ($regperiods as $period) {
                                if (substr($period, 0, strlen($domainname)) == $domainname) {
                                    $regperiod = substr($period, strlen($domainname));
                                }
                            }
                        }
                        if (!$regperiod) {
                            $tldyears = array_keys($temppricelist);
                            $regperiod = $tldyears[0];
                        }
                    }
                    $domainArray = array("type" => $domainoption, "domain" => $domainname, "regperiod" => $regperiod, "isPremium" => false);
                    if (isset($passedvariables["bnum"])) {
                        $domainArray["bnum"] = $passedvariables["bnum"];
                    }
                    if (isset($passedvariables["bitem"])) {
                        $domainArray["bitem"] = $passedvariables["bitem"];
                    }
                    $premiumData = WHMCS\Session::get("PremiumDomains");
                    if ((bool) (int) WHMCS\Config\Setting::getValue("PremiumDomains") && array_key_exists($domainname, $premiumData)) {
                        $premiumPrice = $premiumData[$domainname];
                        if (array_key_exists("register", $premiumPrice["cost"])) {
                            $domainArray["isPremium"] = true;
                            $domainArray["domainpriceoverride"] = $premiumPrice["markupPrice"][1]["register"];
                            $domainArray["registrarCostPrice"] = $premiumPrice["cost"]["register"];
                            $domainArray["registrarCurrency"] = $premiumPrice["markupPrice"][1]["currency"];
                            $domainArray["domainpriceoverride"] = $domainArray["domainpriceoverride"]->toNumeric();
                        }
                        if (array_key_exists("renew", $premiumPrice["cost"])) {
                            $domainArray["domainrenewoverride"] = $premiumPrice["markupPrice"][1]["renew"];
                            $domainArray["registrarRenewalCostPrice"] = $premiumPrice["cost"]["renew"];
                            $domainArray["registrarCurrency"] = $premiumPrice["markupPrice"][1]["currency"];
                            $domainArray["domainrenewoverride"] = $domainArray["domainrenewoverride"]->toNumeric();
                        } else {
                            $domainArray["isPremium"] = false;
                        }
                    }
                    $_SESSION["cart"]["domains"][] = $domainArray;
                }
            }
            $_SESSION["cart"]["newproduct"] = true;
            if ($ajax) {
                $ajax = "&ajax=1";
            } else {
                if ($passedvariables["skipconfig"]) {
                    unset($_SESSION["cart"]["products"][$newprodnum]["noconfig"]);
                    $_SESSION["cart"]["lastconfigured"] = array("type" => "product", "i" => $newprodnum);
                    redir("a=view");
                }
            }
            redir("a=confproduct&i=" . $newprodnum . $ajax);
        }
    } else {
        if ($aid) {
            $requestAddonID = (int) $whmcs->get_req_var("aid");
            $requestServiceID = (int) $whmcs->get_req_var("serviceid");
            $requestProductID = (int) $whmcs->get_req_var("productid");
            if (!$requestServiceID && $requestProductID) {
                $requestServiceID = $requestProductID;
            }
            if (!$requestAddonID || !$requestServiceID) {
                redir("gid=addons");
            }
            $data = get_query_vals("tblhosting", "id,packageid", array("id" => $requestServiceID, "userid" => WHMCS\Session::get("uid"), "domainstatus" => "Active"));
            $serviceid = $data["id"];
            $pid = $data["packageid"];
            if (!$serviceid) {
                redir("gid=addons");
            }
            $data = get_query_vals("tbladdons", "id,packages", array("id" => $requestAddonID));
            $aid = $data["id"];
            $packages = $data["packages"];
            if (!$aid) {
                redir("gid=addons");
            }
            $packages = explode(",", $packages);
            if (!in_array($pid, $packages)) {
                redir("gid=addons");
            }
            $_SESSION["cart"]["addons"][] = array("id" => $aid, "productid" => $serviceid);
            if ($ajax) {
                exit;
            }
            redir("a=view");
        } else {
            if ($domain = App::getFromRequest("domain")) {
                $allowRegistration = WHMCS\Config\Setting::getValue("AllowRegister");
                $allowTransfers = WHMCS\Config\Setting::getValue("AllowTransfer");
                $allowRenewalOrders = WHMCS\Config\Setting::getValue("EnableDomainRenewalOrders");
                $smartyvalues["domainRegistrationEnabled"] = (bool) $allowRegistration;
                $smartyvalues["registerdomainenabled"] = $smartyvalues["domainRegistrationEnabled"];
                $smartyvalues["domainTransferEnabled"] = (bool) $allowTransfers;
                $smartyvalues["transferdomainenabled"] = $smartyvalues["domainTransferEnabled"];
                $smartyvalues["renewalsenabled"] = (bool) $allowRenewalOrders;
                if (!in_array($domain, array("register", "transfer"))) {
                    $domain = "register";
                }
                if ($domain == "register" && !$allowRegistration) {
                    redir();
                }
                if ($domain == "transfer" && !$allowTransfers) {
                    redir();
                }
                $pricing = localAPI("GetTldPricing", array("clientid" => (int) WHMCS\Session::get("uid"), "currencyid" => $currency["id"]));
                $smartyvalues["pricing"] = $pricing;
                foreach ($smartyvalues["pricing"]["pricing"] as $tld => &$priceData) {
                    foreach (array("register", "transfer", "renew") as $action) {
                        foreach ($priceData[$action] as $term => &$price) {
                            $price = new WHMCS\View\Formatter\Price($price, (array) $smartyvalues["pricing"]["currency"]);
                        }
                    }
                }
                unset($price);
                unset($priceData);
                $extensions = array_keys($smartyvalues["pricing"]["pricing"]) ?: array();
                $featuredTlds = array();
                $spotlights = getSpotlightTldsWithPricing();
                foreach ($spotlights as $spotlight) {
                    if (file_exists(ROOTDIR . "/assets/img/tld_logos/" . $spotlight["tldNoDots"] . ".png")) {
                        $featuredTlds[] = $spotlight;
                    }
                }
                $smartyvalues["featuredTlds"] = $featuredTlds;
                try {
                    $tldCategories = WHMCS\Domain\TopLevel\Category::whereHas("topLevelDomains", function (Illuminate\Database\Eloquent\Builder $query) use($extensions) {
                        $query->whereIn("tld", $extensions);
                    })->with("topLevelDomains")->tldsIn($extensions)->orderBy("is_primary", "desc")->orderBy("display_order")->orderBy("category")->get();
                } catch (Exception $e) {
                    $tldCategories = array();
                }
                $categoryCounts = array();
                foreach ($pricing["pricing"] as $extension => $price) {
                    foreach ($price["categories"] as $category) {
                        $categoryCounts[$category]++;
                    }
                }
                $categoriesWithCounts = array();
                foreach ($tldCategories->pluck("category") as $category) {
                    $categoriesWithCounts[$category] = $categoryCounts[$category];
                }
                if (array_key_exists("Other", $categoryCounts)) {
                    $categoriesWithCounts["Other"] = $categoryCounts["Other"];
                }
                $smartyvalues["categoriesWithCounts"] = $categoriesWithCounts;
                $smartyvalues["availabilityresults"] = array();
                if ($domains) {
                    $passedvariables = $_SESSION["cart"]["passedvariables"];
                    unset($_SESSION["cart"]["passedvariables"]);
                    foreach ($domains as $domainname) {
                        cartPreventDuplicateDomain($domainname);
                        $regperiod = $domainsregperiod[$domainname];
                        $domainparts = explode(".", $domainname, 2);
                        $temppricelist = getTLDPriceList("." . $domainparts[1]);
                        if (!isset($temppricelist[$regperiod][$domain])) {
                            if (is_array($regperiods)) {
                                foreach ($regperiods as $period) {
                                    if (substr($period, 0, strlen($domainname)) == $domainname) {
                                        $regperiod = substr($period, strlen($domainname));
                                    }
                                }
                            }
                            if (!$regperiod) {
                                $tldyears = array_keys($temppricelist);
                                $regperiod = $tldyears[0];
                            }
                        }
                        $domainArray = array("type" => $domain, "domain" => $domainname, "regperiod" => $regperiod, "eppcode" => $eppcode, "isPremium" => false);
                        if (isset($passedvariables["addons"])) {
                            foreach ($passedvariables["addons"] as $domaddon) {
                                $domainArray[$domaddon] = true;
                            }
                        }
                        if (isset($passedvariables["bnum"])) {
                            $domainArray["bnum"] = $passedvariables["bnum"];
                        }
                        if (isset($passedvariables["bitem"])) {
                            $domainArray["bitem"] = $passedvariables["bitem"];
                        }
                        $premiumData = WHMCS\Session::get("PremiumDomains");
                        if ((bool) (int) WHMCS\Config\Setting::getValue("PremiumDomains") && array_key_exists($domainname, $premiumData)) {
                            $premiumPrice = $premiumData[$domainname];
                            if (array_key_exists("transfer", $premiumPrice["cost"])) {
                                $domainArray["isPremium"] = true;
                                $domainArray["domainpriceoverride"] = $premiumPrice["markupPrice"][1]["transfer"];
                                $domainArray["registrarCostPrice"] = $premiumPrice["cost"]["transfer"];
                                $domainArray["registrarCurrency"] = $premiumPrice["markupPrice"][1]["currency"];
                                $domainArray["domainpriceoverride"] = $domainArray["domainpriceoverride"]->toNumeric();
                            }
                            if (array_key_exists("renew", $premiumPrice["cost"])) {
                                $domainArray["domainrenewoverride"] = $premiumPrice["markupPrice"][1]["renew"];
                                $domainArray["registrarRenewalCostPrice"] = $premiumPrice["cost"]["renew"];
                                $domainArray["registrarCurrency"] = $premiumPrice["markupPrice"][1]["currency"];
                                $domainArray["domainrenewoverride"] = $domainArray["domainrenewoverride"]->toNumeric();
                            } else {
                                $domainArray["isPremium"] = false;
                            }
                        }
                        $_SESSION["cart"]["domains"][] = $domainArray;
                    }
                    if ($ajax) {
                        $ajax = "&ajax=1";
                    }
                    $newdomnum = count($_SESSION["cart"]["domains"]) - 1;
                    $_SESSION["cart"]["lastconfigured"] = array("type" => "domain", "i" => $newdomnum);
                    if (!$ajax && is_array($orderconf["denynonajaxaccess"]) && in_array("confdomains", $orderconf["denynonajaxaccess"])) {
                        $smartyvalues["selecteddomains"] = $_SESSION["cart"]["domains"];
                        $smartyvalues["skipselect"] = true;
                    } else {
                        redir("a=confdomains" . $ajax);
                    }
                }
                $check = new WHMCS\Domain\Checker();
                if ($domain == "transfer") {
                    if ($orderFormTemplate->hasTemplate("domaintransfer")) {
                        $smarty->assign("captcha", $captcha);
                        $smarty->assign("captchaForm", WHMCS\Utility\Captcha::FORM_DOMAIN_CHECKER);
                        $captchaData = WHMCS\Session::getAndDelete("captchaData");
                        if ($captchaData) {
                            if (!$captchaData["invalidCaptchaError"]) {
                                $captcha->setEnabled(false);
                                $smarty->assign("captcha", $captcha);
                            } else {
                                $smarty->assign("captchaError", $captchaData["invalidCaptchaError"]);
                            }
                        } else {
                            WHMCS\Session::set("CaptchaComplete", false);
                        }
                        $templatefile = "domaintransfer";
                    } else {
                        $templatefile = "adddomain";
                    }
                } else {
                    if ($orderFormTemplate->hasTemplate("domainregister")) {
                        $showSuggestions = true;
                        if ($check->getLookupProvider() instanceof WHMCS\Domains\DomainLookup\Provider\BasicWhois && !WHMCS\Config\Setting::getValue("BulkCheckTLDs") || $check->getLookupProvider() instanceof WHMCS\Domains\DomainLookup\Provider\WhmcsWhois && !WHMCS\Config\Setting::getValue("domainLookup_WhmcsWhois_suggestTlds")) {
                            $showSuggestions = false;
                        }
                        $smarty->assign("showSuggestionsContainer", $showSuggestions);
                        $smarty->assign("captcha", $captcha);
                        $smarty->assign("captchaForm", WHMCS\Utility\Captcha::FORM_DOMAIN_CHECKER);
                        $captchaData = WHMCS\Session::getAndDelete("captchaData");
                        if ($captchaData) {
                            if (!$captchaData["invalidCaptchaError"]) {
                                $captcha->setEnabled(false);
                                $smarty->assign("captcha", $captcha);
                            } else {
                                $smarty->assign("captchaError", $captchaData["invalidCaptchaError"]);
                            }
                        } else {
                            WHMCS\Session::set("CaptchaComplete", false);
                        }
                        $templatefile = "domainregister";
                    } else {
                        $templatefile = "adddomain";
                    }
                }
                $registerTlds = getTLDList();
                $transferTlds = getTLDList("transfer");
                $smarty->assign("registertlds", $registerTlds);
                $smarty->assign("transfertlds", $transferTlds);
                $tldslist = $domain == "register" ? $registerTlds : $transferTlds;
                $smarty->assign("tlds", $tldslist);
                $smarty->assign("spotlightTlds", getSpotlightTldsWithPricing());
                $smartyvalues["domain"] = $domain;
                $sld = App::getFromRequest("sld");
                $tld = App::getFromRequest("tld");
                if ($domain == "transfer" && App::getFromRequest("sld_transfer")) {
                    $sld = App::getFromRequest("sld_transfer");
                }
                if ($domain == "transfer" && App::getFromRequest("tld_transfer")) {
                    $tld = App::getFromRequest("tld_transfer");
                }
                $lookupTerm = App::getFromRequest("query");
                if (!$lookupTerm && $sld) {
                    if ($tld && ltrim($tld, ".") == $tld) {
                        $tld = "." . $tld;
                    }
                    $lookupTerm = $sld . $tld;
                }
                if ($lookupTerm) {
                    $passedDomain = new WHMCS\Domains\Domain($lookupTerm);
                    $sld = $passedDomain->getSecondLevel();
                    $tld = $passedDomain->getDotTopLevel();
                }
                $smartyvalues["lookupTerm"] = $lookupTerm;
                $smartyvalues["sld"] = $sld;
                $smartyvalues["tld"] = $tld;
                if ($sld && $tld && !$errormessage && $templatefile == "adddomain") {
                    $check->cartDomainCheck(new WHMCS\Domains\Domain($sld), array($tld));
                    $check->populateCartWithDomainSmartyVariables($domain, $smartyvalues);
                }
            } else {
                if ($renewals) {
                    if ($renewalid) {
                        $_SESSION["cart"]["renewals"][$renewalid] = $renewalperiod;
                    } else {
                        if (!count($renewalids)) {
                            redir("gid=renewals");
                        } else {
                            foreach ($renewalids as $domainid) {
                                $_SESSION["cart"]["renewals"][$domainid] = $renewalperiod[$domainid];
                            }
                        }
                    }
                    if ($ajax) {
                        exit;
                    }
                    redir("a=view");
                } else {
                    if ($bid) {
                        $data = get_query_vals("tblbundles", "", array("id" => $bid));
                        $bid = $data["id"];
                        $validfrom = $data["validfrom"];
                        $validuntil = $data["validuntil"];
                        $uses = $data["uses"];
                        $maxuses = $data["maxuses"];
                        $itemdata = $data["itemdata"];
                        $itemdata = safe_unserialize($itemdata);
                        $vals = $itemdata[0];
                        if ($validfrom != "0000-00-00" && date("Ymd") < str_replace("-", "", $validfrom) || $validuntil != "0000-00-00" && str_replace("-", "", $validuntil) < date("Ymd")) {
                            $templatefile = "error";
                            $smartyvalues["errortitle"] = $_LANG["bundlevaliddateserror"];
                            $smartyvalues["errormsg"] = $_LANG["bundlevaliddateserrordesc"];
                            outputClientArea($templatefile);
                            exit;
                        }
                        if ($maxuses && $maxuses <= $uses) {
                            $templatefile = "error";
                            $smartyvalues["errortitle"] = $_LANG["bundlemaxusesreached"];
                            $smartyvalues["errormsg"] = $_LANG["bundlemaxusesreacheddesc"];
                            outputClientArea($templatefile);
                            exit;
                        }
                        $_SESSION["cart"]["bundle"][] = array("bid" => $bid, "step" => "0", "complete" => "0");
                        $totalnum = count($_SESSION["cart"]["bundle"]);
                        $vals["bnum"] = $totalnum - 1;
                        $vals["bitem"] = "0";
                        $vals["billingcycle"] = str_replace(array("-", " "), "", strtolower($vals["billingcycle"]));
                        $_SESSION["cart"]["passedvariables"] = $vals;
                        switch ($vals["type"]) {
                            case "product":
                                $extraVars = "pid=" . $vals["pid"];
                                break;
                            case "domain":
                                $extraVars = "domain=register";
                                break;
                            default:
                                redir();
                        }
                        redir("a=add&" . $extraVars);
                    } else {
                        redir();
                    }
                }
            }
        }
    }
}
if ($a == "domainoptions") {
    $productinfo = $orderfrm->setPid($_SESSION["cart"]["domainoptionspid"]);
    $orderFormTemplateName = $productinfo["orderfrmtpl"] == "" ? $orderFormTemplateName : $productinfo["orderfrmtpl"];
    $checktype = App::getFromRequest("checktype");
    $domain = App::getFromRequest("domain");
    if ($checktype == "register" || $checktype == "transfer") {
        if ($domain) {
            $domainparts = explode(".", $domain, 2);
            list($sld, $tld) = $domainparts;
        }
        $sld = cleanDomainInput($sld);
        $tld = cleanDomainInput($tld);
        if ($tld && substr($tld, 0, 1) != ".") {
            $tld = "." . $tld;
        }
        $domainToLookup = new WHMCS\Domains\Domain($sld . $tld);
        if ($sld != "www" && $sld && $tld && WHMCS\Domains\Domain::isValidDomainName($sld, $tld)) {
            $domaincheck = false;
            $smartyvalues["alreadyindb"] = false;
            if ($CONFIG["AllowDomainsTwice"]) {
                $domainObject = new WHMCS\Domains\Domain($sld . $tld);
                $domaincheck = cartCheckIfDomainAlreadyOrdered($domainObject);
            }
            if ($domaincheck) {
                $smartyvalues["alreadyindb"] = true;
            } else {
                $regenabled = $CONFIG["AllowRegister"];
                $transferenabled = $CONFIG["AllowTransfer"];
                $owndomainenabled = $CONFIG["AllowOwnDomain"];
                $check = new WHMCS\Domain\Checker();
                $check->cartDomainCheck($domainToLookup, array($tld));
                $searchResult = $check->getSearchResult()->offsetGet(0);
                $smartyvalues["searchResults"] = $searchResult->toArray();
                $smartyvalues["status"] = $searchResult->getLegacyStatus();
                $pricing = $searchResult->pricing()->toArray();
                if ($regenabled) {
                    $smartyvalues["regoptionscount"] = count($pricing);
                    $smartyvalues["regoptions"] = $pricing;
                }
                if ($transferenabled) {
                    $smartyvalues["transferoptionscount"] = count($pricing);
                    $smartyvalues["transferoptions"] = $pricing;
                    $transferPrice = current($pricing);
                    $smartyvalues["transferterm"] = key($pricing);
                    $smartyvalues["transferprice"] = $transferPrice["transfer"];
                }
                if (!$checktype) {
                    if ($searchResult->getStatus() == WHMCS\Domains\DomainLookup\SearchResult::STATUS_REGISTERED) {
                        $checktype = "transfer";
                    } else {
                        $checktype = "register";
                    }
                }
                $smartyvalues["domain"] = $domainToLookup->getDomain();
                $smartyvalues["checktype"] = $checktype;
                $smartyvalues["regenabled"] = $regenabled;
                $smartyvalues["transferenabled"] = $transferenabled;
                $smartyvalues["owndomainenabled"] = $owndomainenabled;
                $smartyvalues["searchResults"]["suggestions"] = array();
                if ($checktype == "register" && $regenabled) {
                    $check->populateSuggestionsInSmartyValues($smartyvalues);
                }
            }
        } else {
            $smartyvalues["invalid"] = true;
        }
    } else {
        if ($checktype == "owndomain") {
            $tld = strtolower($tld);
            if ($sld && $tld && WHMCS\Domains\Domain::isValidDomainName($sld, $tld) && WHMCS\Domains\Domain::isSupportedTld($tld)) {
                if (substr($tld, 0, 1) != ".") {
                    $tld = "." . $tld;
                }
                if ($CONFIG["AllowDomainsTwice"]) {
                    $smartyvalues["alreadyindb"] = false;
                    $result = select_query("tblhosting", "domain", "domain='" . db_escape_string($sld . $tld) . "' AND (domainstatus!='Terminated' AND domainstatus!='Cancelled' AND domainstatus!='Fraud')");
                    while ($data = mysql_fetch_array($result)) {
                        if ($data[0] == $sld . $tld) {
                            $smartyvalues["alreadyindb"] = true;
                            break;
                        }
                    }
                }
                $smartyvalues["checktype"] = $checktype;
                $smartyvalues["sld"] = $sld;
                $smartyvalues["tld"] = $tld;
            } else {
                $smartyvalues["invalid"] = true;
            }
        } else {
            if ($checktype == "subdomain") {
                if (!is_array($BannedSubdomainPrefixes)) {
                    $BannedSubdomainPrefixes = array();
                }
                if ($whmcs->get_config("BannedSubdomainPrefixes")) {
                    $bannedprefixes = $whmcs->get_config("BannedSubdomainPrefixes");
                    $bannedprefixes = explode(",", $bannedprefixes);
                    $BannedSubdomainPrefixes = array_merge($BannedSubdomainPrefixes, $bannedprefixes);
                }
                if (!WHMCS\Domains\Domain::isValidDomainName($sld, ".com")) {
                    $smartyvalues["invalid"] = true;
                } else {
                    if (in_array($sld, $BannedSubdomainPrefixes)) {
                        $smartyvalues["invalid"] = true;
                        $smartyvalues["reason"] = $_LANG["ordererrorsbudomainbanned"];
                    } else {
                        $result = select_query("tblhosting", "COUNT(*)", "domain='" . db_escape_string($sld . $tld) . "' AND (domainstatus!='Terminated' AND domainstatus!='Cancelled' AND domainstatus!='Fraud')");
                        $data = mysql_fetch_array($result);
                        $subchecks = $data[0];
                        if ($subchecks) {
                            $smartyvalues["invalid"] = true;
                            $smartyvalues["reason"] = $_LANG["ordererrorsubdomaintaken"];
                        } else {
                            $smartyvalues["checktype"] = $checktype;
                            $smartyvalues["sld"] = $sld;
                            $smartyvalues["tld"] = $tld;
                        }
                    }
                }
            } else {
                if ($checktype == "incart") {
                    $smartyvalues["checktype"] = "owndomain";
                    $domainparts = explode(".", $sld, 2);
                    list($sld, $tld) = $domainparts;
                    $smartyvalues["sld"] = $sld;
                    $smartyvalues["tld"] = $tld;
                }
            }
        }
    }
    $validate = new WHMCS\Validate();
    if ($checktype == "subdomain") {
        run_validate_hook($validate, "CartSubdomainValidation", array("subdomain" => $sld, "domain" => $tld));
    } else {
        run_validate_hook($validate, "ShoppingCartValidateDomain", array("domainoption" => $checktype, "sld" => $sld, "tld" => $tld));
    }
    if ($validate->hasErrors()) {
        $domainError = $validate->getHTMLErrorOutput();
        $smartyvalues["invalid"] = true;
        $smartyvalues["reason"] = $domainError;
    }
    $templatefile = "domainoptions";
}
if ($a == "cyclechange") {
    if (!is_int($productInfoKey) || !$billingcycle) {
        if ($ajax) {
            throw new WHMCS\Exception\ProgramExit($_LANG["invoiceserror"]);
        }
        redir();
    }
    if ($orderfrm->validateBillingCycle($billingcycle)) {
        $_SESSION["cart"]["products"][$productInfoKey]["billingcycle"] = $billingcycle;
    }
    $a = "confproduct";
}
if ($a == "confproduct") {
    $templatefile = "configureproduct";
    if (is_null($productInfoKey) || !isset($_SESSION["cart"]["products"][$productInfoKey]) || !is_array($_SESSION["cart"]["products"][$productInfoKey])) {
        if ($ajax) {
            exit($_LANG["invoiceserror"]);
        }
        redir();
    }
    if (isset($_SESSION["cart"]["products"][$productInfoKey]["skipConfig"]) && $_SESSION["cart"]["products"][$productInfoKey]["skipConfig"]) {
        $_SESSION["cart"]["products"][$productInfoKey]["skipConfig"] = false;
        redir("a=view");
    }
    $newproduct = isset($_SESSION["cart"]["newproduct"]) ? $_SESSION["cart"]["newproduct"] : "";
    unset($_SESSION["cart"]["newproduct"]);
    $pid = $_SESSION["cart"]["products"][$productInfoKey]["pid"];
    $productinfo = $orderfrm->setPid($pid);
    if (!$productinfo) {
        redir();
    }
    $orderFormTemplateName = $productinfo["orderfrmtpl"] == "" ? $orderFormTemplateName : $productinfo["orderfrmtpl"];
    $_SESSION["cart"]["cartsummarypid"] = $productinfo["pid"];
    $pid = $productinfo["pid"];
    $configure = $whmcs->get_req_var("configure");
    $validate = new WHMCS\Validate();
    if ($configure) {
        global $errormessage;
        $errormessage = "";
        $serverarray = array();
        if ($productinfo["type"] == "server") {
            $hostname = App::getFromRequest("hostname");
            $ns1prefix = App::getFromRequest("ns1prefix");
            $ns2prefix = App::getFromRequest("ns2prefix");
            $rootpw = App::getFromRequest("rootpw");
            $validate->validate("required", "hostname", "ordererrorservernohostname");
            if ($validate->validated("hostname")) {
                $validate->validate("unique_service_domain", "hostname", "ordererrorserverhostnameinuse");
                if ($validate->validated("hostname")) {
                    $validate->validate("hostname", "hostname", "orderErrorServerHostnameInvalid");
                    if ($validate->validated("hostname")) {
                        $validate->reverseValidate("numeric", "hostname", "orderErrorServerHostnameInvalid");
                    }
                }
            }
            $validate->validate("required", "ns1prefix", "ordererrorservernonameservers");
            if ($validate->validated("ns1prefix")) {
                $validate->validate("alphanumeric", "ns1prefix", "orderErrorServerNameserversInvalid");
                if ($validate->validated("ns1prefix")) {
                    $validate->reverseValidate("numeric", "ns1prefix", "orderErrorServerNameserversInvalid");
                }
            }
            $validate->validate("required", "ns2prefix", "ordererrorservernonameservers");
            if ($validate->validated("ns2prefix")) {
                $validate->validate("alphanumeric", "ns2prefix", "orderErrorServerNameserversInvalid");
                if ($validate->validated("ns2prefix")) {
                    $validate->reverseValidate("numeric", "ns2prefix", "orderErrorServerNameserversInvalid");
                }
            }
            $validate->validate("required", "rootpw", "ordererrorservernorootpw");
            $serverarray = array("hostname" => $hostname, "ns1prefix" => $ns1prefix, "ns2prefix" => $ns2prefix, "rootpw" => $rootpw);
        }
        $configoptionsarray = array();
        $configoption = $whmcs->get_req_var("configoption");
        if ($configoption) {
            $configOpsReturn = validateAndSanitizeQuantityConfigOptions($configoption);
            $configoptionsarray = $configOpsReturn["validOptions"];
            $errormessage .= $configOpsReturn["errorMessage"];
        }
        $addons = $whmcs->get_req_var("addons");
        $addonsarray = is_array($addons) ? array_keys($addons) : array();
        foreach (App::getFromRequest("addons_radio") as $addonId) {
            if (is_numeric($addonId)) {
                $addonsarray[] = $addonId;
            }
        }
        $customFieldData = isset($_SESSION["cart"]["products"][$productInfoKey]["customfields"]) ? (array) $_SESSION["cart"]["products"][$productInfoKey]["customfields"] : array();
        $newCustomFieldData = (array) App::getFromRequest("customfield");
        foreach ($newCustomFieldData as $key => $value) {
            $customFieldData[$key] = $value;
        }
        $errormessage .= bundlesValidateProductConfig($productInfoKey, $billingcycle, $configoptionsarray, $addonsarray);
        $_SESSION["cart"]["products"][$productInfoKey]["billingcycle"] = $billingcycle;
        $_SESSION["cart"]["products"][$productInfoKey]["server"] = $serverarray;
        $_SESSION["cart"]["products"][$productInfoKey]["configoptions"] = $configoptionsarray;
        $_SESSION["cart"]["products"][$productInfoKey]["customfields"] = $customFieldData;
        $_SESSION["cart"]["products"][$productInfoKey]["addons"] = $addonsarray;
        if ($whmcs->get_req_var("calctotal")) {
            $productinfo = $orderfrm->setPid($_SESSION["cart"]["products"][$productInfoKey]["pid"]);
            $orderFormTemplateName = $productinfo["orderfrmtpl"] == "" ? $orderFormTemplateName : $productinfo["orderfrmtpl"];
            try {
                $orderSummaryTemplate = "/templates/orderforms/" . WHMCS\View\Template\OrderForm::factory("ordersummary.tpl", $orderFormTemplateName)->getName() . "/ordersummary.tpl";
                $cartTotals = calcCartTotals(false, true);
                $templateVariables = array("producttotals" => $cartTotals["products"][$productInfoKey], "carttotals" => $cartTotals);
                header("HTTP/1.1 200 OK");
                header("Status: 200 OK");
                echo processSingleTemplate($orderSummaryTemplate, $templateVariables);
            } catch (Exception $e) {
            }
            exit;
        }
        if (!$ajax && !$whmcs->get_req_var("nocyclerefresh") && $previousbillingcycle != $billingcycle) {
            redir("a=confproduct&i=" . $productInfoKey);
        }
        $validate->validateCustomFields("product", $pid, true);
        run_validate_hook($validate, "ShoppingCartValidateProductUpdate", $_REQUEST);
        if ($validate->hasErrors()) {
            $errormessage .= $validate->getHTMLErrorOutput();
        }
        if ($errormessage) {
            if ($ajax) {
                exit($errormessage);
            }
            $smartyvalues["errormessage"] = $errormessage;
        } else {
            unset($_SESSION["cart"]["products"][$productInfoKey]["noconfig"]);
            $_SESSION["cart"]["lastconfigured"] = array("type" => "product", "i" => $productInfoKey);
            if ($ajax) {
                header("HTTP/1.1 200 OK");
                header("Status: 200 OK");
                exit;
            }
            redir("a=confdomains");
        }
    }
    $billingcycle = $_SESSION["cart"]["products"][$productInfoKey]["billingcycle"];
    $server = $_SESSION["cart"]["products"][$productInfoKey]["server"];
    $customfields = $_SESSION["cart"]["products"][$productInfoKey]["customfields"];
    $configoptions = $_SESSION["cart"]["products"][$productInfoKey]["configoptions"];
    $addons = $_SESSION["cart"]["products"][$productInfoKey]["addons"];
    if (!$addons) {
        $addons = array();
    }
    $domain = $_SESSION["cart"]["products"][$productInfoKey]["domain"];
    $noconfig = $_SESSION["cart"]["products"][$productInfoKey]["noconfig"];
    $billingcycle = $orderfrm->validateBillingCycle($billingcycle);
    $pricing = getPricingInfo($pid);
    $configurableoptions = getCartConfigOptions($pid, $configoptions, $billingcycle, "", true);
    $customfields = getCustomFields("product", $pid, "", "", "on", $customfields);
    $addonsarray = getAddons($pid, $addons);
    $marketConnect = new WHMCS\MarketConnect\MarketConnect();
    $addonsPromoOutput = $marketConnect->getMarketplaceConfigureProductAddonPromoHtml($addonsarray, $billingcycle);
    $addonsarray = $marketConnect->removeMarketplaceAddons($addonsarray);
    $hookResponses = run_hook("ShoppingCartConfigureProductAddonsOutput", array("billingCycle" => $billingcycle, "selectedAddons" => $addonsarray));
    foreach ($hookResponses as $response) {
        if ($response) {
            $addonsPromoOutput[] = $response;
        }
    }
    $smartyvalues["addonsPromoOutput"] = $addonsPromoOutput;
    $recurringcycles = 0;
    if ($pricing["type"] == "recurring") {
        if (0 <= $pricing["rawpricing"]["monthly"]) {
            $recurringcycles++;
        }
        if (0 <= $pricing["rawpricing"]["quarterly"]) {
            $recurringcycles++;
        }
        if (0 <= $pricing["rawpricing"]["semiannually"]) {
            $recurringcycles++;
        }
        if (0 <= $pricing["rawpricing"]["annually"]) {
            $recurringcycles++;
        }
        if (0 <= $pricing["rawpricing"]["biennially"]) {
            $recurringcycles++;
        }
    }
    if ($newproduct && $productinfo["type"] != "server" && ($pricing["type"] != "recurring" || $recurringcycles <= 1) && !count($configurableoptions) && !count($customfields) && !count($addonsarray) && !$addonsPromoOutput) {
        unset($_SESSION["cart"]["products"][$productInfoKey]["noconfig"]);
        $_SESSION["cart"]["lastconfigured"] = array("type" => "product", "i" => $productInfoKey);
        if ($ajax) {
            exit;
        }
        redir("a=confdomains");
    }
    $serverarray = array("hostname" => isset($server["hostname"]) ? $server["hostname"] : "", "ns1prefix" => isset($server["ns1prefix"]) ? $server["ns1prefix"] : "", "ns2prefix" => isset($server["ns2prefix"]) ? $server["ns2prefix"] : "", "rootpw" => isset($server["rootpw"]) ? $server["rootpw"] : "");
    $smartyvalues["editconfig"] = true;
    $smartyvalues["firstconfig"] = $noconfig ? true : false;
    $smartyvalues["i"] = $productInfoKey;
    $smartyvalues["productinfo"] = $productinfo;
    $smartyvalues["pricing"] = $pricing;
    $smartyvalues["billingcycle"] = $billingcycle;
    $smartyvalues["server"] = $serverarray;
    $smartyvalues["configurableoptions"] = $configurableoptions;
    $smartyvalues["addons"] = $addonsarray;
    $smartyvalues["customfields"] = $customfields;
    $smartyvalues["domain"] = $domain;
}
if ($a == "confdomains") {
    $templatefile = "configuredomains";
    $skipstep = true;
    $_SESSION["cartdomain"] = "";
    $update = $whmcs->get_req_var("update");
    $validate = $whmcs->get_req_var("validate");
    if ($update || $validate) {
        $validateHookParams = $_REQUEST;
        $domains = $_SESSION["cart"]["domains"];
        foreach ($domains as $key => $domainname) {
            if ($validate) {
                $domainfield[$key] = $_SESSION["cart"]["domains"][$key]["fields"];
            } else {
                $_SESSION["cart"]["domains"][$key]["dnsmanagement"] = $_POST["dnsmanagement"][$key];
                $_SESSION["cart"]["domains"][$key]["emailforwarding"] = $_POST["emailforwarding"][$key];
                $_SESSION["cart"]["domains"][$key]["idprotection"] = $_POST["idprotection"][$key];
                $_SESSION["cart"]["domains"][$key]["eppcode"] = $_POST["epp"][$key];
            }
            $domainparts = explode(".", $domainname["domain"], 2);
            $additflds = new WHMCS\Domains\AdditionalFields();
            $additflds->setTLD($domainparts[1]);
            $additflds->setFieldValues($domainfield[$key]);
            $missingfields = $additflds->getMissingRequiredFields();
            foreach ($missingfields as $missingfield) {
                $errormessage .= "<li>" . $missingfield . " " . $_LANG["clientareaerrorisrequired"] . " (" . $domainname["domain"] . ")";
            }
            $_SESSION["cart"]["domains"][$key]["fields"] = $domainfield[$key];
            $validateHookParams["domainfield"][$key] = $additflds->getAsNameValueArray();
            if ($domainname["type"] !== "register") {
                $result = select_query("tbldomainpricing", "", array("extension" => "." . $domainparts[1]));
                $data = mysql_fetch_array($result);
                if ($data["eppcode"] && !$_POST["epp"][$key]) {
                    $errormessage .= "<li>" . $_LANG["domaineppcoderequired"] . " " . $domainname["domain"];
                }
            }
        }
        for ($i = 1; $i <= 5; $i++) {
            $ns = $whmcs->get_req_var("domainns" . $i);
            if (preg_match($nameserverRegexPattern, $ns)) {
                $_SESSION["cart"]["ns" . $i] = $ns;
            }
            if ($ns == "" && isset($_SESSION["cart"]["ns" . $i])) {
                unset($_SESSION["cart"]["ns" . $i]);
            }
        }
        $validate = new WHMCS\Validate();
        run_validate_hook($validate, "ShoppingCartValidateDomainsConfig", $validateHookParams);
        if ($validate->hasErrors()) {
            $errormessage .= $validate->getHTMLErrorOutput();
        }
        if ($ajax) {
            exit($errormessage);
        }
        if ($errormessage) {
            $smartyvalues["errormessage"] = $errormessage;
        } else {
            redir("a=view");
        }
    }
    $domains = $_SESSION["cart"]["domains"];
    if ($domains) {
        foreach ($domains as $key => $domainname) {
            $regperiod = $domainname["regperiod"];
            $domainparts = explode(".", $domainname["domain"], 2);
            list($sld, $tld) = $domainparts;
            $result = select_query("tbldomainpricing", "", array("extension" => "." . $tld));
            $data = mysql_fetch_array($result);
            $domainconfigsshowing = $eppenabled = false;
            if ($data["dnsmanagement"]) {
                $domainconfigsshowing = true;
            }
            if ($data["emailforwarding"]) {
                $domainconfigsshowing = true;
            }
            if ($data["idprotection"]) {
                $domainconfigsshowing = true;
            }
            $result = select_query("tblpricing", "", array("type" => "domainaddons", "currency" => $currency["id"], "relid" => 0));
            $data2 = mysql_fetch_array($result);
            $domaindnsmanagementprice = $data2["msetupfee"] * $regperiod;
            $domainemailforwardingprice = $data2["qsetupfee"] * $regperiod;
            $domainidprotectionprice = $data2["ssetupfee"] * $regperiod;
            $domaindnsmanagementprice = $domaindnsmanagementprice == "0.00" ? $_LANG["orderfree"] : new WHMCS\View\Formatter\Price($domaindnsmanagementprice, $currency);
            $domainemailforwardingprice = $domainemailforwardingprice == "0.00" ? $_LANG["orderfree"] : new WHMCS\View\Formatter\Price($domainemailforwardingprice, $currency);
            $domainidprotectionprice = $domainidprotectionprice == "0.00" ? $_LANG["orderfree"] : new WHMCS\View\Formatter\Price($domainidprotectionprice, $currency);
            if ($data["eppcode"] && $domainname["type"] == "transfer") {
                $eppenabled = true;
                $domainconfigsshowing = true;
            }
            $additflds = new WHMCS\Domains\AdditionalFields();
            $additflds->setTLD($tld);
            $fieldValues = isset($domainname["fields"]) ? $domainname["fields"] : array();
            $additflds->setFieldValues($fieldValues);
            $domainfields = $additflds->getFieldsForOutput($key);
            if (count($domainfields)) {
                $domainconfigsshowing = true;
            }
            $products = $_SESSION["cart"]["products"];
            $hashosting = false;
            if ($products) {
                foreach ($products as $product) {
                    if ($product["domain"] == $domainname["domain"]) {
                        $hashosting = true;
                    }
                }
            }
            if (!$hashosting) {
                $atleastonenohosting = true;
            }
            if ($atleastonenohosting) {
                $skipstep = false;
            }
            $domainAddonsCount = 0;
            if ($data["dnsmanagement"]) {
                $domainAddonsCount++;
            }
            if ($data["emailforwarding"]) {
                $domainAddonsCount++;
            }
            if ($data["idprotection"]) {
                $domainAddonsCount++;
            }
            $domainsarray[$key] = array("domain" => $domainname["domain"], "regperiod" => $domainname["regperiod"], "dnsmanagement" => $data["dnsmanagement"], "emailforwarding" => $data["emailforwarding"], "idprotection" => $data["idprotection"], "addonsCount" => $domainAddonsCount, "dnsmanagementprice" => $domaindnsmanagementprice, "emailforwardingprice" => $domainemailforwardingprice, "idprotectionprice" => $domainidprotectionprice, "dnsmanagementselected" => isset($domainname["dnsmanagement"]) ? $domainname["dnsmanagement"] : false, "emailforwardingselected" => isset($domainname["emailforwarding"]) ? $domainname["emailforwarding"] : false, "idprotectionselected" => isset($domainname["idprotection"]) ? $domainname["idprotection"] : false, "eppenabled" => $eppenabled, "eppvalue" => isset($domainname["eppcode"]) ? $domainname["eppcode"] : "", "fields" => $domainfields, "configtoshow" => $domainconfigsshowing, "hosting" => $hashosting);
            if ($domainconfigsshowing || $eppenabled || $domainfields || $data["dnsmanagement"] || $data["emailforwarding"] || $data["idprotection"]) {
                $skipstep = false;
            }
        }
    }
    $smartyvalues["domains"] = $domainsarray;
    $smartyvalues["atleastonenohosting"] = $atleastonenohosting;
    if (!$skipstep && !$_SESSION["cart"]["ns1"] && !$_SESSION["cart"]["ns2"]) {
        for ($i = 1; $i <= 5; $i++) {
            $_SESSION["cart"]["ns" . $i] = isset($CONFIG["DefaultNameserver" . $i]) ? $CONFIG["DefaultNameserver" . $i] : NULL;
        }
    }
    for ($i = 1; $i <= 5; $i++) {
        $ns = isset($_SESSION["cart"]["ns" . $i]) ? $_SESSION["cart"]["ns" . $i] : "";
        $smartyvalues["domainns" . $i] = $ns;
    }
    if ($skipstep) {
        if ($ajax) {
            exit;
        }
        redir("a=view");
    }
}
if ($a == "checkout") {
    $domainconfigerror = false;
    $domains = $orderfrm->getCartDataByKey("domains");
    if ($domains) {
        foreach ($domains as $key => $domaindata) {
            $domainparts = explode(".", $domaindata["domain"], 2);
            $additflds = new WHMCS\Domains\AdditionalFields();
            $additflds->setTLD($domainparts[1]);
            $additflds->setFieldValues($domaindata["fields"]);
            if ($additflds->isMissingRequiredFields()) {
                $domainconfigerror = true;
            }
            if ($domaindata["type"] !== "register") {
                $result = select_query("tbldomainpricing", "eppcode", array("extension" => "." . $domainparts[1]));
                $data = mysql_fetch_array($result);
                if ($data["eppcode"] && !$domaindata["eppcode"]) {
                    $domainconfigerror = true;
                }
            }
        }
    }
    if ($domainconfigerror) {
        if ($ajax) {
            $errormessage .= "<li>" . $_LANG["carterrordomainconfigskipped"];
        } else {
            redir("a=confdomains&validate=1");
        }
    }
    $credit_card_input = "";
    foreach (getAvailableOrderPaymentGateways(true) as $moduleName => $moduleConfiguration) {
        $gateway = new WHMCS\Module\Gateway();
        if ($gateway->load($moduleName) && $gateway->functionExists("credit_card_input")) {
            $credit_card_input .= $gateway->call("credit_card_input", calcCartTotals(false, false, $currency));
        }
    }
    $smartyvalues["credit_card_input"] = $credit_card_input;
    $remoteAuth = DI::make("remoteAuth");
    $remoteAuthData = $remoteAuth->getRegistrationFormData();
    $remoteAuthData = (new WHMCS\Authentication\Remote\Management\Client\ViewHelper())->getTemplateData(WHMCS\Authentication\Remote\Providers\AbstractRemoteAuthProvider::HTML_TARGET_CHECKOUT);
    foreach ($remoteAuthData as $key => $value) {
        $smartyvalues[$key] = $value;
    }
    if (!empty($remoteAuthData)) {
        $userData = $_SESSION["cart"]["user"];
        if (empty($userData["email"]) && isset($remoteAuthData["email"])) {
            $userData["email"] = $remoteAuthData["email"];
        }
        if (empty($userData["firstname"]) && isset($remoteAuthData["firstname"])) {
            $userData["firstname"] = $remoteAuthData["firstname"];
        }
        if (empty($userData["lastname"]) && isset($remoteAuthData["lastname"])) {
            $userData["lastname"] = $remoteAuthData["lastname"];
        }
        $_SESSION["cart"]["user"] = $userData;
    }
    $allowcheckout = true;
    $a = "view";
}
if ($a == "addcontact") {
    $allowcheckout = true;
    $addcontact = true;
    $a = "view";
}
if ($a == "view") {
    $templatefile = "viewcart";
    $errormessage = "";
    $gateways = new WHMCS\Gateways();
    $availablegateways = getAvailableOrderPaymentGateways(true);
    $securityquestions = getSecurityQuestions();
    $submit = $whmcs->get_req_var("submit");
    $checkout = $whmcs->get_req_var("checkout");
    $validatelogin = $whmcs->get_req_var("validatelogin");
    $validatepromo = $whmcs->get_req_var("validatepromo");
    $ccinfo = $whmcs->get_req_var("ccinfo");
    $cctype = $whmcs->get_req_var("cctype");
    $ccDescription = App::getFromRequest("ccdescription");
    $ccnumber = $whmcs->get_req_var("ccnumber");
    $ccexpirymonth = $whmcs->get_req_var("ccexpirymonth");
    $ccexpiryyear = $whmcs->get_req_var("ccexpiryyear");
    $ccstartmonth = $whmcs->get_req_var("ccstartmonth");
    $ccstartyear = $whmcs->get_req_var("ccstartyear");
    $ccissuenum = $whmcs->get_req_var("ccissuenum");
    $cccvvexisting = $cccvv = $whmcs->get_req_var("cccvv");
    $nostore = $whmcs->get_req_var("nostore");
    $password = $whmcs->get_req_var("password");
    $password2 = $whmcs->get_req_var("password2");
    $customfields = $whmcs->get_req_var("customfields");
    $notes = $whmcs->get_req_var("notes");
    $contact = $whmcs->get_req_var("contact");
    $addcontact = $whmcs->get_req_var("addcontact");
    $domaincontactfirstname = $whmcs->get_req_var("domaincontactfirstname");
    $domaincontactlastname = $whmcs->get_req_var("domaincontactlastname");
    $domaincontactcompanyname = $whmcs->get_req_var("domaincontactcompanyname");
    $domaincontactemail = $whmcs->get_req_var("domaincontactemail");
    $domaincontactaddress1 = $whmcs->get_req_var("domaincontactaddress1");
    $domaincontactaddress2 = $whmcs->get_req_var("domaincontactaddress2");
    $domaincontactcity = $whmcs->get_req_var("domaincontactcity");
    $domaincontactstate = $whmcs->get_req_var("domaincontactstate");
    $domaincontactpostcode = $whmcs->get_req_var("domaincontactpostcode");
    $domaincontactcountry = $whmcs->get_req_var("domaincontactcountry");
    $domaincontactphonenumber = $whmcs->get_req_var("domaincontactphonenumber");
    $domaincontactphonenumber = App::formatPostedPhoneNumber("domaincontactphonenumber");
    $domaincontactcountry = $whmcs->get_req_var("domaincontactcountry");
    $domainContactTaxId = App::getFromRequest("domaincontacttax_id");
    $loginfailed = $whmcs->get_req_var("loginfailed");
    $insufficientstock = $whmcs->get_req_var("insufficientstock");
    if ($insufficientstock) {
        $errormessage .= "<li>" . $_LANG["insufficientstockmessage"] . "</li>";
    }
    $ccExpiryDate = $whmcs->get_req_var("ccexpirydate");
    if ($ccExpiryDate) {
        $ccExpirySplit = explode("/", $ccExpiryDate);
        $ccexpirymonth = !empty($ccExpirySplit[0]) ? $ccExpirySplit[0] : "";
        $ccexpiryyear = !empty($ccExpirySplit[1]) ? $ccExpirySplit[1] : "";
    }
    $ccexpirymonth = trim($ccexpirymonth);
    $ccexpiryyear = trim($ccexpiryyear);
    if (2 < strlen($ccexpiryyear)) {
        $ccexpiryyear = substr($ccexpiryyear, -2);
    }
    $ccStartDate = $whmcs->get_req_var("ccstartdate");
    if ($ccStartDate) {
        $ccStartSplit = explode("/", $ccStartDate);
        $ccstartmonth = !empty($ccStartSplit[0]) ? $ccStartSplit[0] : "";
        $ccstartyear = !empty($ccStartSplit[1]) ? $ccStartSplit[1] : "";
    }
    $ccstartmonth = trim($ccstartmonth);
    $ccstartyear = trim($ccstartyear);
    if (2 < strlen($ccstartmonth)) {
        $ccstartmonth = substr($ccstartmonth, -2);
    }
    if (!$cccvv && $cccvvexisting) {
        $cccvv = $cccvvexisting;
    }
    $encryptedVarNames = array("cctype", "ccnumber", "ccexpirymonth", "ccexpiryyear", "ccstartmonth", "ccstartyear", "ccissuenum", "cccvv", "nostore");
    foreach ($encryptedVarNames as $varName) {
        if (32 < strlen(${$varName})) {
            ${$varName} = substr(${$varName}, 0, 32);
        }
    }
    $remoteAuth = DI::make("remoteAuth");
    if ($remoteAuth->isPrelinkPerformed()) {
        $password = $remoteAuth->generateRandomPassword();
    }
    if (($submit || $checkout || $validatelogin) && !$validatepromo) {
        if ($orderfrm->getNumItemsInCart() <= 0) {
            redir("a=view");
        }
        $_SESSION["cart"]["paymentmethod"] = $paymentmethod;
        $_SESSION["cart"]["notes"] = $notes;
        if (!$_SESSION["uid"]) {
            if ($custtype == "existing" || $validatelogin) {
                $loginemail = $whmcs->get_req_var("loginemail");
                $loginpw = WHMCS\Input\Sanitize::decode($whmcs->get_req_var("loginpw"));
                if (!$loginpw) {
                    $loginpw = WHMCS\Input\Sanitize::decode($whmcs->get_req_var("loginpassword"));
                }
                if (validateClientLogin($loginemail, $loginpw)) {
                    initialiseLoggedInClient();
                } else {
                    if ($validatelogin) {
                        redir("a=checkout&loginfailed=1");
                    }
                    $errormessage .= "<li>" . $_LANG["loginincorrect"];
                }
                if (isset($_SESSION["2faverifyc"])) {
                    $_SESSION["2fafromcart"] = true;
                    redir("", "clientarea.php");
                }
                if ($validatelogin) {
                    redir("a=checkout");
                }
            } else {
                $phonenumber = App::formatPostedPhoneNumber();
                $_SESSION["cart"]["user"] = array("firstname" => $firstname, "lastname" => $lastname, "companyname" => $companyname, "email" => $email, "address1" => $address1, "address2" => $address2, "city" => $city, "state" => $state, "postcode" => $postcode, "country" => $country, "phonenumber" => $phonenumber);
                $errormessage .= checkDetailsareValid("", true, true, false);
            }
        }
        if ($validatelogin) {
            redir("a=checkout");
        }
        if ($contact == "new") {
            redir("a=addcontact");
        }
        if ($contact == "addingnew") {
            $errormessage .= checkContactDetails("", false, "domaincontact");
        }
        if ($availablegateways[$paymentmethod]["type"] == "CC" && $ccinfo) {
            $gateway = new WHMCS\Module\Gateway();
            $gateway->load($paymentmethod);
            if ($gateway->functionExists("cc_validation")) {
                $params = array();
                $params["cardtype"] = $cctype;
                $params["cardnum"] = ccFormatNumbers($ccnumber);
                $params["cardexp"] = ccFormatDate(ccFormatNumbers($ccexpirymonth . $ccexpiryyear));
                $params["cardstart"] = ccFormatDate(ccFormatNumbers($ccstartmonth . $ccstartyear));
                $params["cardissuenum"] = ccFormatNumbers($ccissuenum);
                $errormessage .= $gateway->call("cc_validation", $params);
                $params = NULL;
            } else {
                if ($ccinfo == "new") {
                    $errormessage .= updateCCDetails("", $cctype, $ccnumber, $cccvv, $ccexpirymonth . $ccexpiryyear, $ccstartmonth . $ccstartyear, $ccissuenum);
                }
                if (!$cccvv) {
                    $errormessage .= "<li>" . $_LANG["creditcardccvinvalid"];
                }
            }
            $_SESSION["cartccdetail"] = encrypt(base64_encode(serialize(array($cctype, $ccnumber, $ccexpirymonth, $ccexpiryyear, $ccstartmonth, $ccstartyear, $ccissuenum, $cccvv, $nostore, $ccinfo))));
        }
        $validate = new WHMCS\Validate();
        $cartCaptcha = new WHMCS\Utility\Captcha();
        if ($cartCaptcha->isEnabled()) {
            $cartCaptcha->validateAppropriateCaptcha(WHMCS\Utility\Captcha::FORM_CHECKOUT_COMPLETION, $validate);
        }
        $cartCheckoutHookData = $_REQUEST;
        $cartCheckoutHookData["promocode"] = $orderfrm->getCartDataByKey("promo");
        $cartCheckoutHookData["userid"] = WHMCS\Session::get("uid");
        run_validate_hook($validate, "ShoppingCartValidateCheckout", $cartCheckoutHookData);
        if (isset($_SESSION["uid"]) && $whmcs->get_config("EnableTOSAccept")) {
            $validate->validate("required", "accepttos", "ordererroraccepttos");
        }
        if ($validate->hasErrors()) {
            $errormessage .= $validate->getHTMLErrorOutput();
        }
        $currency = getCurrency(WHMCS\Session::get("uid"), WHMCS\Session::get("currency"));
        if ($whmcs->get_req_var("updateonly")) {
            $errormessage = "";
        }
        if ($ajax && $errormessage) {
            exit($errormessage);
        }
        if (!$errormessage && !$_POST["updateonly"]) {
            if (!$_SESSION["uid"]) {
                $phonenumber = App::formatPostedPhoneNumber();
                $marketingoptin = empty($marketingoptin) ? 0 : 1;
                $userid = addClient($firstname, $lastname, $companyname, $email, $address1, $address2, $city, $state, $postcode, $country, $phonenumber, $password, $securityqid, $securityqans, true, array("tax_id" => App::getFromRequest("tax_id")), "", false, $marketingoptin);
            }
            if ($contact == "addingnew") {
                $contact = addContact($_SESSION["uid"], $domaincontactfirstname, $domaincontactlastname, $domaincontactcompanyname, $domaincontactemail, $domaincontactaddress1, $domaincontactaddress2, $domaincontactcity, $domaincontactstate, $domaincontactpostcode, $domaincontactcountry, $domaincontactphonenumber, "", array(), "", "", "", "", "", "", $domainContactTaxId);
            }
            $_SESSION["cart"]["contact"] = $contact;
            define("INORDERFORM", true);
            $carttotals = calcCartTotals(true, false, $currency);
            $_SESSION["orderdetails"]["ccinfo"] = $ccinfo;
            if ($ccinfo == "new" && !$nostore) {
                $newPayMethod = NULL;
                updateCCDetails($_SESSION["uid"], $cctype, $ccnumber, $cccvv, $ccexpirymonth . $ccexpiryyear, $ccstartmonth . $ccstartyear, $ccissuenum, "", "", $paymentmethod, $newPayMethod, $ccDescription);
                if ($newPayMethod) {
                    $invoiceModel = WHMCS\Billing\Invoice::find($_SESSION["orderdetails"]["InvoiceID"]);
                    if ($invoiceModel) {
                        $invoiceModel->payMethod()->associate($newPayMethod);
                        $invoiceModel->save();
                    }
                }
            }
            $orderid = $_SESSION["orderdetails"]["OrderID"];
            $order = new WHMCS\Order();
            $order->setID($orderid);
            $fraudModule = $order->getActiveFraudModule();
            if ($fraudModule && $order->shouldFraudCheckBeSkipped()) {
                $fraudModule = "";
            }
            if (!$fraudModule) {
                if ($ajax) {
                    WHMCS\Terminus::getInstance()->doExit();
                }
                redir("a=complete");
            }
            $fraud = new WHMCS\Module\Fraud();
            logActivity("Order ID " . $orderid . " Fraud Check Initiated");
            update_query("tblorders", array("status" => "Fraud"), array("id" => $orderid));
            if ($_SESSION["orderdetails"]["Products"]) {
                foreach ($_SESSION["orderdetails"]["Products"] as $productid) {
                    update_query("tblhosting", array("domainstatus" => "Fraud"), array("id" => $productid, "domainstatus" => "Pending"));
                }
            }
            if ($_SESSION["orderdetails"]["Addons"]) {
                foreach ($_SESSION["orderdetails"]["Addons"] as $addonid) {
                    update_query("tblhostingaddons", array("status" => "Fraud"), array("id" => $addonid, "status" => "Pending"));
                }
            }
            if ($_SESSION["orderdetails"]["Domains"]) {
                foreach ($_SESSION["orderdetails"]["Domains"] as $domainid) {
                    update_query("tbldomains", array("status" => "Fraud"), array("id" => $domainid, "status" => "Pending"));
                }
            }
            update_query("tblinvoices", array("status" => "Cancelled"), array("id" => $_SESSION["orderdetails"]["InvoiceID"], "status" => "Unpaid"));
            if ($fraud->load($fraudModule)) {
                $results = $fraud->doFraudCheck($orderid);
                $_SESSION["orderdetails"]["fraudcheckresults"] = $results;
            }
            if ($ajax) {
                exit;
            }
            redir("a=fraudcheck");
        }
        if (!$paymentmethod) {
            $errormessage .= "<li>No payment gateways available so order cannot proceed";
        }
    }
    $smartyvalues["errormessage"] = $errormessage;
    if ($allowcheckout) {
        $smartyvalues["captcha"] = new WHMCS\Utility\Captcha();
        $smartyvalues["captchaForm"] = WHMCS\Utility\Captcha::FORM_CHECKOUT_COMPLETION;
        $hookResponses = run_hook("ShoppingCartCheckoutOutput", array("cart" => WHMCS\Session::get("cart")));
        $smartyvalues["hookOutput"] = $hookResponses;
    } else {
        $hookResponses = run_hook("ShoppingCartViewCartOutput", array("cart" => WHMCS\Session::get("cart")));
        $smartyvalues["hookOutput"] = $hookResponses;
    }
    if (isset($_POST["qty"]) && is_array($_POST["qty"])) {
        check_token();
        $didQtyChangeRemoveProducts = false;
        $temporderfrm = new WHMCS\OrderForm();
        $insufficientstock = false;
        foreach ($_POST["qty"] as $i => $qty) {
            $i = (int) $i;
            $qty = (int) $qty;
            if (is_array($_SESSION["cart"]["products"][$i])) {
                if (0 < $qty) {
                    $productinfo = $temporderfrm->setPid($_SESSION["cart"]["products"][$i]["pid"]);
                    if (!empty($productinfo) && $productinfo["stockcontrol"]) {
                        if (!isset($productinfo["qty"])) {
                            $productinfo["qty"] = 0;
                        }
                        if ($productinfo["qty"] < $qty) {
                            $qty = $productinfo["qty"];
                            $insufficientstock = true;
                        }
                    }
                    $_SESSION["cart"]["products"][$i]["qty"] = $qty;
                } else {
                    if ($qty == 0) {
                        unset($_SESSION["cart"]["products"][$i]);
                        $didQtyChangeRemoveProducts = true;
                    }
                }
            }
        }
        if ($didQtyChangeRemoveProducts) {
            $_SESSION["cart"]["products"] = array_values($_SESSION["cart"]["products"]);
        }
        redir("a=view" . ($insufficientstock ? "&insufficientstock=1" : ""));
    }
    $smartyvalues["promoaddedsuccess"] = false;
    if ($promocode) {
        $promoerrormessage = SetPromoCode($promocode);
        if ($promoerrormessage) {
            $smartyvalues["promoerrormessage"] = $promoerrormessage;
            $smartyvalues["errormessage"] = "<li>" . $promoerrormessage;
        } else {
            $smartyvalues["promoaddedsuccess"] = true;
        }
        if ($paymentmethod) {
            $_SESSION["cart"]["paymentmethod"] = $paymentmethod;
        }
        if ($ccinfo) {
            $_SESSION["cart"]["ccinfo"] = $ccinfo;
        }
        if ($notes) {
            $_SESSION["cart"]["notes"] = $notes;
        }
        if ($firstname) {
            $phonenumber = App::formatPostedPhoneNumber();
            $_SESSION["cart"]["user"] = array("firstname" => $firstname, "lastname" => $lastname, "companyname" => $companyname, "email" => $email, "address1" => $address1, "address2" => $address2, "city" => $city, "state" => $state, "postcode" => $postcode, "country" => $country, "phonenumber" => $phonenumber);
        }
    }
    $smartyvalues["promotioncode"] = $orderfrm->getCartDataByKey("promo");
    $cartsummary = $whmcs->get_req_var("cartsummary");
    $ignorenoconfig = $cartsummary ? true : false;
    $carttotals = calcCartTotals(false, $ignorenoconfig, $currency);
    $promotype = $carttotals["promotype"];
    $promovalue = $carttotals["promovalue"];
    $promorecurring = $carttotals["promorecurring"];
    if (isset($carttotals["productRemovedFromCart"]) && $carttotals["productRemovedFromCart"]) {
        $smartyvalues["errormessage"] .= "<li>" . $whmcs->get_lang("outOfStockProductRemoved") . "</li>";
    }
    $promodescription = $promotype == "Percentage" ? $promovalue . "%" : $promovalue;
    if ($promotype == "Price Override") {
        $promodescription .= " " . $_LANG["orderpromopriceoverride"];
    } else {
        if ($promotype == "Free Setup") {
            $promodescription = $_LANG["orderpromofreesetup"];
        }
    }
    $promoCycles = WHMCS\Database\Capsule::table("tblpromotions")->where("code", $smartyvalues["promotioncode"])->pluck("recurfor");
    $message = "orderForm.promoCycles";
    $replace[":cycles"] = $promoCycles[0];
    $forCycles = Lang::trans($message, $replace);
    if ($promoCycles[0] == 0) {
        $promodescription .= " " . $promorecurring . " " . $_LANG["orderdiscount"];
    } else {
        $promodescription .= " " . $promorecurring . " " . $_LANG["orderdiscount"] . " <br /> " . $forCycles;
    }
    $smartyvalues["promotiondescription"] = $promodescription;
    $amountOfCredit = 0;
    $canUseCreditOnCheckout = false;
    if (WHMCS\Session::get("uid")) {
        $amountOfCredit = $clientsdetails["credit"];
        if (0 < $amountOfCredit) {
            $canUseCreditOnCheckout = true;
        }
    }
    $smartyvalues["canUseCreditOnCheckout"] = $canUseCreditOnCheckout;
    $smartyvalues["creditBalance"] = new WHMCS\View\Formatter\Price($amountOfCredit, $currency);
    $smartyvalues["applyCredit"] = App::isInRequest("applycredit") ? (bool) App::getFromRequest("applycredit") : true;
    $smartyvalues["client"] = WHMCS\User\Client::loggedIn()->first();
    foreach ($carttotals as $k => $v) {
        $smartyvalues[$k] = $v;
    }
    $hasProductQuantities = false;
    foreach ($carttotals["products"] as $product) {
        if ($product["allowqty"]) {
            $hasProductQuantities = true;
        }
    }
    $smartyvalues["showqtyoptions"] = $hasProductQuantities;
    $smartyvalues["taxenabled"] = $CONFIG["TaxEnabled"];
    $paymentmethod = $_SESSION["cart"]["paymentmethod"];
    if (!$paymentmethod) {
        foreach ($availablegateways as $k => $v) {
            $paymentmethod = $k;
            break;
        }
    }
    $smartyvalues["selectedgateway"] = $paymentmethod;
    $smartyvalues["selectedgatewaytype"] = $availablegateways[$paymentmethod]["type"];
    if (empty($_SESSION["paypalexpress"]["payerid"])) {
        $smartyvalues["gateways"] = array_filter($availablegateways, function ($item) {
            return $item["sysname"] != "paypalexpress";
        });
    } else {
        $smartyvalues["gateways"] = array_filter($availablegateways, function ($item) {
            return $item["sysname"] == "paypalexpress";
        });
        $smartyvalues["selectedgateway"] = "paypalexpress";
    }
    $smartyvalues["ccinfo"] = $ccinfo;
    $smartyvalues["cctype"] = $cctype;
    $smartyvalues["ccdescription"] = $ccDescription;
    $smartyvalues["ccnumber"] = $ccnumber;
    $smartyvalues["ccexpirymonth"] = $ccexpirymonth;
    $smartyvalues["ccexpiryyear"] = $ccexpiryyear;
    $smartyvalues["ccstartmonth"] = $ccstartmonth;
    $smartyvalues["ccstartyear"] = $ccstartyear;
    $smartyvalues["ccissuenum"] = $ccissuenum;
    $smartyvalues["cccvv"] = $cccvv;
    $smartyvalues["showccissuestart"] = $CONFIG["ShowCCIssueStart"];
    $smartyvalues["shownostore"] = $CONFIG["CCAllowCustomerDelete"];
    $smartyvalues["allowClientsToRemoveCards"] = $CONFIG["CCAllowCustomerDelete"];
    $smartyvalues["months"] = $gateways->getCCDateMonths();
    $smartyvalues["startyears"] = $gateways->getCCStartDateYears();
    $smartyvalues["years"] = $gateways->getCCExpiryDateYears();
    $smartyvalues["expiryyears"] = $smartyvalues["years"];
    $cartitems = $orderfrm->getNumItemsInCart();
    if (!$cartitems) {
        $allowcheckout = false;
    }
    $smartyvalues["cartitems"] = $cartitems;
    $smartyvalues["checkout"] = $allowcheckout;
    if ($_SESSION["uid"]) {
        $clientsdetails = getClientsDetails();
        $clientsdetails["country"] = $clientsdetails["countryname"];
        $custtype = "existing";
        $smartyvalues["loggedin"] = true;
    } else {
        $clientsdetails = $_SESSION["cart"]["user"];
        $customfields = getCustomFields("client", "", "", "", "on", $customfield);
        $_SESSION["loginurlredirect"] = "cart.php?a=login";
        if (!$custtype) {
            $custtype = "new";
        }
    }
    $smartyvalues["custtype"] = $custtype;
    $smartyvalues["clientsdetails"] = $clientsdetails;
    $smartyvalues["loginfailed"] = $loginfailed;
    $countries = new WHMCS\Utility\Country();
    $smartyvalues["countries"] = $countries->getCountryNameArray();
    $smartyvalues["defaultcountry"] = WHMCS\Config\Setting::getValue("DefaultCountry");
    if (!isset($country)) {
        $country = isset($clientsdetails["countrycode"]) ? $clientsdetails["countrycode"] : $clientsdetails["country"];
    }
    $smartyvalues["clientcountrydropdown"] = getCountriesDropDown($country);
    $smartyvalues["country"] = $country;
    $smartyvalues["password"] = $password;
    $smartyvalues["password2"] = $password2;
    $smartyvalues["securityqans"] = $securityqans;
    $smartyvalues["securityqid"] = $securityqid;
    $smartyvalues["customfields"] = $customfields;
    $smartyvalues["securityquestions"] = $securityquestions;
    $smartyvalues["shownotesfield"] = $CONFIG["ShowNotesFieldonCheckout"];
    $smartyvalues["orderNotes"] = $notes;
    $smartyvalues["notes"] = 0 < strlen($notes) ? $notes : $_LANG["ordernotesdescription"];
    $smartyvalues["showMarketingEmailOptIn"] = !WHMCS\Session::get("uid") && WHMCS\Config\Setting::getValue("AllowClientsEmailOptOut");
    $smartyvalues["marketingEmailOptInMessage"] = Lang::trans("emailMarketing.optInMessage") != "emailMarketing.optInMessage" ? Lang::trans("emailMarketing.optInMessage") : WHMCS\Config\Setting::getValue("EmailMarketingOptInMessage");
    $smartyvalues["marketingEmailOptIn"] = App::isInRequest("marketingoptin") ? (bool) App::getFromRequest("marketingoptin") : (bool) (!WHMCS\Config\Setting::getValue("EmailMarketingRequireOptIn"));
    $smartyvalues["accepttos"] = $CONFIG["EnableTOSAccept"];
    $smartyvalues["tosurl"] = $CONFIG["TermsOfService"];
    $smartyvalues["domainsinorder"] = 0 < count($orderfrm->getCartDataByKey("domains", array()));
    $domaincontacts = array();
    $result = select_query("tblcontacts", "", array("userid" => $_SESSION["uid"], "address1" => array("sqltype" => "NEQ", "value" => "")), "firstname` ASC,`lastname", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $domaincontacts[] = array("id" => $data["id"], "name" => $data["firstname"] . " " . $data["lastname"]);
    }
    $smartyvalues["domaincontacts"] = $domaincontacts;
    $smartyvalues["contact"] = $contact;
    if ($contact == "addingnew") {
        $addcontact = true;
    }
    $smartyvalues["addcontact"] = $addcontact;
    $smartyvalues["domaincontact"] = array("firstname" => $domaincontactfirstname, "lastname" => $domaincontactlastname, "companyname" => $domaincontactcompanyname, "email" => $domaincontactemail, "address1" => $domaincontactaddress1, "address2" => $domaincontactaddress2, "city" => $domaincontactcity, "state" => $domaincontactstate, "postcode" => $domaincontactpostcode, "country" => $domaincontactcountry, "phonenumber" => $domaincontactphonenumber);
    $smartyvalues["domaincontactcountrydropdown"] = getCountriesDropDown($domaincontactcountry, "domaincontactcountry");
    $gatewaysoutput = $checkoutOutput = array();
    foreach ($availablegateways as $module => $vals) {
        $gatewayModule = new WHMCS\Module\Gateway();
        $gatewayModule->load($module);
        $params = $gatewayModule->loadSettings();
        $params["amount"] = $carttotals["rawtotal"];
        $params["currency"] = $currency["code"];
        if (isset($params["convertto"]) && $params["convertto"]) {
            $currencyCode = WHMCS\Database\Capsule::table("tblcurrencies")->where("id", "=", (int) $params["convertto"])->value("code");
            $convertToAmount = convertCurrency($carttotals["rawtotal"], $currency["id"], $params["convertto"]);
            $params["amount"] = format_as_currency($convertToAmount);
            $params["currency"] = $currencyCode;
            $params["currencyId"] = (int) $params["convertto"];
            $params["basecurrencyamount"] = format_as_currency($carttotals["rawtotal"]);
            $params["basecurrency"] = $currency["code"];
            $params["baseCurrencyId"] = $currency["id"];
        }
        if (!isset($params["currency"]) || !$params["currency"]) {
            $params["amount"] = format_as_currency($carttotals["rawtotal"]);
            $params["currency"] = $currency["code"];
            $params["currencyId"] = $currency["id"];
        }
        if ($userid) {
            $payMethod = WHMCS\User\Client::find($userid)->payMethods()->where("gateway_name", $module)->first();
            $gatewayId = "";
            $payment = $payMethod->payment;
            if ($payment instanceof WHMCS\Payment\Contracts\RemoteTokenDetailsInterface) {
                $gatewayId = $payment->getRemoteToken();
            }
            $params["gatewayid"] = $gatewayId;
        }
        $params["isCheckout"] = (bool) $allowcheckout;
        if ($gatewayModule->functionExists("orderformoutput")) {
            $output = $gatewayModule->call("orderformoutput", $params);
            if ($output) {
                $gatewaysoutput[] = $output;
            }
        }
    }
    $smartyvalues["gatewaysoutput"] = $gatewaysoutput;
    $smartyvalues["checkoutOutput"] = $checkoutOutput;
    $smartyvalues["clientsProfileOptionalFields"] = explode(",", WHMCS\Config\Setting::getValue("ClientsProfileOptionalFields"));
    $smartyvalues["showTaxIdField"] = WHMCS\Billing\Tax\Vat::isUsingNativeField();
    if ($cartsummary) {
        $ajax = "1";
        $templatefile = "cartsummary";
        $productinfo = $orderfrm->setPid($_SESSION["cart"]["cartsummarypid"]);
        $orderFormTemplateName = $productinfo["orderfrmtpl"] == "" ? $orderFormTemplateName : $productinfo["orderfrmtpl"];
    }
}
if ($a == "login") {
    if ($_SESSION["uid"]) {
        redir("a=checkout");
    }
    $templatefile = "login";
    $smartyvalues["captcha"] = new WHMCS\Utility\Captcha();
    $smartyvalues["captchaForm"] = WHMCS\Utility\Captcha::FORM_LOGIN;
    $smartyvalues["invalid"] = WHMCS\Session::getAndDelete("CaptchaError");
    $_SESSION["loginurlredirect"] = "cart.php?a=login";
    if ($incorrect) {
        $smartyvalues["incorrect"] = true;
    }
}
if ($a == "fraudcheck") {
    $orderid = $_SESSION["orderdetails"]["OrderID"];
    $results = isset($_SESSION["orderdetails"]["fraudcheckresults"]) ? $_SESSION["orderdetails"]["fraudcheckresults"] : "";
    unset($_SESSION["orderdetails"]["fraudcheckresults"]);
    if (!$results) {
        $order = new WHMCS\Order();
        $order->setID($orderid);
        $fraudModule = $order->getActiveFraudModule();
        if ($fraudModule && $order->shouldFraudCheckBeSkipped()) {
            $fraudModule = "";
        }
        if (!$fraudModule) {
            redir("a=complete");
        }
        $fraud = new WHMCS\Module\Fraud();
        if ($fraud->load($fraudModule)) {
            $results = $fraud->doFraudCheck($orderid);
        }
    }
    $isFraud = false;
    $fraudError = array();
    if (array_key_exists("error", $results) && $results["error"]) {
        $isFraud = true;
        $fraudError = $results["error"];
    }
    $hookresults = array("orderid" => $orderid, "ordernumber" => $_SESSION["orderdetails"]["OrderNumber"], "fraudresults" => $_SESSION["orderdetails"]["fraudcheckresults"], "invoiceid" => $_SESSION["orderdetails"]["InvoiceID"], "amount" => $_SESSION["orderdetails"]["TotalDue"], "fraudresults" => $results, "isfraud" => $isFraud, "frauderror" => $fraudError, "clientdetails" => getClientsDetails($_SESSION["uid"]));
    run_hook("AfterFraudCheck", $hookresults);
    $error = $results["error"];
    if ($results["userinput"]) {
        logActivity("Order ID " . $orderid . " Fraud Check Awaiting User Input");
        run_hook("FraudCheckAwaitingUserInput", $hookresults);
        $templatefile = "fraudcheck";
        $smarty->assign("errortitle", $results["title"]);
        $smarty->assign("error", $results["description"]);
        outputClientArea($templatefile, false, array("ClientAreaPageCartFraudCheckAwaitingUserInput"));
        exit;
    }
    if ($error) {
        logActivity("Order ID " . $orderid . " Failed Fraud Check");
        refundCreditOnStatusChange($_SESSION["orderdetails"]["InvoiceID"]);
        run_hook("FraudCheckFailed", $hookresults);
        $templatefile = "fraudcheck";
        $smarty->assign("errortitle", $error["title"]);
        $smarty->assign("error", $error["description"]);
        $smartyvalues["carttpl"] = $orderFormTemplateName;
        outputClientArea($templatefile, false, array("ClientAreaPageCartFraudCheckFailed"));
        exit;
    }
    update_query("tblorders", array("status" => "Pending"), array("id" => $orderid));
    if ($_SESSION["orderdetails"]["Products"]) {
        foreach ($_SESSION["orderdetails"]["Products"] as $productid) {
            update_query("tblhosting", array("domainstatus" => "Pending"), array("id" => $productid, "domainstatus" => "Fraud"));
        }
    }
    if ($_SESSION["orderdetails"]["Addons"]) {
        foreach ($_SESSION["orderdetails"]["Addons"] as $addonid) {
            update_query("tblhostingaddons", array("status" => "Pending"), array("id" => $addonid, "status" => "Fraud"));
        }
    }
    if ($_SESSION["orderdetails"]["Domains"]) {
        foreach ($_SESSION["orderdetails"]["Domains"] as $domainid) {
            update_query("tbldomains", array("status" => "Pending"), array("id" => $domainid, "status" => "Fraud"));
        }
    }
    update_query("tblinvoices", array("status" => "Unpaid"), array("id" => $_SESSION["orderdetails"]["InvoiceID"], "status" => "Cancelled"));
    logActivity("Order ID " . $orderid . " Passed Fraud Check");
    run_hook("FraudCheckPassed", $hookresults);
    redir("a=complete");
}
if ($a == "complete") {
    $remoteAuth = DI::make("remoteAuth");
    $remoteAuth->linkRemoteAccounts();
    $remoteAuthData = (new WHMCS\Authentication\Remote\Management\Client\ViewHelper())->getTemplateData();
    foreach ($remoteAuthData as $key => $value) {
        $smartyvalues[$key] = $value;
    }
    if (!is_array($_SESSION["orderdetails"])) {
        redir();
    }
    $orderid = $_SESSION["orderdetails"]["OrderID"];
    $invoiceid = $_SESSION["orderdetails"]["InvoiceID"];
    $paymentmethod = $_SESSION["orderdetails"]["PaymentMethod"];
    if (WHMCS\Session::get("InOrderButNeedProcessPaidInvoiceAction") && 0 < (int) $invoiceid) {
        processPaidInvoice($invoiceid);
    }
    $total = 0;
    if ($invoiceid) {
        $result = select_query("tblinvoices", "id,total,paymentmethod,status", array("userid" => $_SESSION["uid"], "id" => $invoiceid));
        $data = mysql_fetch_array($result);
        $invoiceid = $data["id"];
        $total = $data["total"];
        $paymentmethod = $data["paymentmethod"];
        $status = $data["status"];
        if (!$invoiceid) {
            exit("Invalid Invoice ID");
        }
        $clientsdetails = getClientsDetails($_SESSION["uid"]);
    }
    $paymentmethod = WHMCS\Gateways::makeSafeName($paymentmethod);
    if (!$paymentmethod) {
        exit("Unexpected payment method value. Exiting.");
    }
    $result = select_query("tblhosting", "tblhosting.id,tblproducts.servertype", array("tblhosting.orderid" => $orderid, "tblhosting.domainstatus" => "Pending", "tblproducts.autosetup" => "order"), "", "", "", "tblproducts ON tblproducts.id=tblhosting.packageid");
    while ($data = mysql_fetch_array($result)) {
        $id = $data["id"];
        $servertype = $data["servertype"];
        if (getNewClientAutoProvisionStatus($_SESSION["uid"])) {
            logActivity("Running Module Create on Order");
            if (!isValidforPath($servertype)) {
                exit("Invalid Server Module Name");
            }
            include_once ROOTDIR . "/modules/servers/" . $servertype . "/" . $servertype . ".php";
            $moduleresult = ServerCreateAccount($id);
            if ($moduleresult == "success" && $servertype != "marketconnect") {
                sendMessage("defaultnewacc", $id);
            }
        } else {
            logActivity("Module Create on Order Suppressed for New Client");
        }
    }
    $addons = WHMCS\Service\Addon::whereHas("productAddon", function (Illuminate\Database\Eloquent\Builder $query) {
        $query->where("autoactivate", "order");
    })->with("productAddon.welcomeEmailTemplate", "productAddon")->where("orderid", "=", $orderid)->where("status", "=", "Pending")->where("addonid", ">", 0)->get();
    foreach ($addons as $addon) {
        if (!$addon->productAddon) {
            continue;
        }
        $noModule = true;
        $automationResult = false;
        if ($addon->productAddon->module) {
            $noModule = false;
            if (getNewClientAutoProvisionStatus($_SESSION["uid"])) {
                $automationResult = WHMCS\Service\Automation\AddonAutomation::factory($addon)->runAction("CreateAccount");
            } else {
                logActivity("Module Create on Order Suppressed for New Client");
            }
        }
        if ($noModule || $automationResult) {
            if ($addon->productAddon->welcomeEmailTemplateId) {
                sendMessage($addon->productAddon->welcomeEmailTemplate, $id);
            }
            if ($noModule) {
                $addon->status = "Active";
                $addon->save();
                $params = array("id" => $addon->id, "userid" => $_SESSION["uid"], "serviceid" => $id, "addonid" => $addon->addonId);
                run_hook("AddonActivation", $params);
            }
        }
    }
    $gateway = new WHMCS\Module\Gateway();
    $gateway->load($paymentmethod);
    if ($invoiceid && $status == "Unpaid" && $gateway->functionExists("orderformcheckout")) {
        $payMethodId = (int) $_SESSION["orderdetails"]["ccinfo"];
        $payMethod = NULL;
        if ($payMethodId && is_numeric($payMethodId)) {
            $payMethod = WHMCS\Payment\PayMethod\Model::findForClient($payMethodId, WHMCS\Session::get("uid"));
        }
        if ($payMethod) {
            WHMCS\Database\Capsule::table("tblinvoices")->where("id", $invoiceid)->update(array("paymethodid" => $payMethod->id));
        }
        $invoice = new WHMCS\Invoice($invoiceid);
        try {
            $params = $invoice->initialiseGatewayAndParams();
        } catch (Exception $e) {
            logActivity("Failed to initialise payment gateway module: " . $e->getMessage());
            throw new WHMCS\Exception\Fatal("Could not initialise payment gateway. Please contact support.");
        }
        $params = array_merge($params, $invoice->getGatewayInvoiceParams());
        $params["gatewayid"] = $params["clientdetails"]["gatewayid"];
        $captureresult = $gateway->call("orderformcheckout", $params);
        if (is_array($captureresult)) {
            logTransaction($paymentmethod, $captureresult["rawdata"], ucfirst($captureresult["status"]));
            if ($captureresult["status"] == "success") {
                if (!function_exists("saveNewRemoteCardDetails")) {
                    require_once ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "ccfunctions.php";
                }
                if (array_key_exists("newRemoteCreditCard", $captureresult) && $captureresult["newRemoteCreditCard"]) {
                    $newCardPayMethod = saveNewRemoteCardDetails($captureresult["newRemoteCreditCard"], $gateway, $params["clientdetails"]["userid"]);
                    $invoiceModel = WHMCS\Billing\Invoice::find($invoiceid);
                    if ($invoiceModel) {
                        $invoiceModel->payMethod()->associate($newCardPayMethod);
                        $invoiceModel->save();
                    }
                }
                addInvoicePayment($invoiceid, $captureresult["transid"], isset($captureresult["amount"]) ? $captureresult["amount"] : "", $captureresult["fee"], $paymentmethod);
                $_SESSION["orderdetails"]["paymentcomplete"] = true;
                $status = "Paid";
            }
        }
    }
    if ($invoiceid && $status == "Unpaid") {
        $gatewaytype = get_query_val("tblpaymentgateways", "value", array("gateway" => $paymentmethod, "setting" => "type"));
        if (!isValidforPath($paymentmethod)) {
            exit("Invalid Payment Gateway Name");
        }
        $gatewaypath = ROOTDIR . "/modules/gateways/" . $paymentmethod . ".php";
        if (file_exists($gatewaypath) && !function_exists($paymentmethod . "_config") && !function_exists($paymentmethod . "_link") && !function_exists($paymentmethod . "_capture")) {
            include_once $gatewaypath;
        }
        if (($gatewaytype == "CC" || $gatewaytype == "OfflineCC") && ($CONFIG["AutoRedirectoInvoice"] == "on" || $CONFIG["AutoRedirectoInvoice"] == "gateway") && !function_exists($paymentmethod . "_nolocalcc")) {
            redir("invoiceid=" . $invoiceid, "creditcard.php");
        }
        if ($CONFIG["AutoRedirectoInvoice"] == "on") {
            redir("id=" . $invoiceid, "viewinvoice.php");
        }
        if ($CONFIG["AutoRedirectoInvoice"] == "gateway") {
            if (in_array($paymentmethod, array("mailin", "banktransfer"))) {
                redir("id=" . $invoiceid, "viewinvoice.php");
            }
            $invoice = new WHMCS\Invoice($invoiceid);
            $paymentbutton = $invoice->getPaymentLink();
            unset($orderform);
            $templatefile = "forwardpage";
            $smarty->assign("message", $_LANG["forwardingtogateway"]);
            $smarty->assign("code", $paymentbutton);
            $smarty->assign("invoiceid", $invoiceid);
            outputClientArea($templatefile, false, array("ClientAreaForwardPage"));
            exit;
        }
    }
    $amount = get_query_val("tblorders", "amount", array("userid" => $_SESSION["uid"], "id" => $orderid));
    $ispaid = false;
    if ($invoiceid) {
        $invoiceStatus = get_query_val("tblinvoices", "status", array("id" => $invoiceid));
        $ispaid = $invoiceStatus == "Paid" ? true : false;
        if ($ispaid) {
            $_SESSION["orderdetails"]["paymentcomplete"] = true;
        }
    }
    $templatefile = "complete";
    $smartyvalues = array_merge($smartyvalues, array("orderid" => $orderid, "ordernumber" => $_SESSION["orderdetails"]["OrderNumber"], "invoiceid" => $invoiceid, "ispaid" => $ispaid, "amount" => $amount, "paymentmethod" => $paymentmethod, "clientdetails" => getClientsDetails($_SESSION["uid"])));
    $addons_html = run_hook("ShoppingCartCheckoutCompletePage", $smartyvalues);
    $smartyvalues["addons_html"] = $addons_html;
}
if (!$templatefile) {
    redir();
}
$nowrapper = isset($_REQUEST["ajax"]) ? true : false;
try {
    $smartyvalues["carttpl"] = WHMCS\View\Template\OrderForm::factory($templatefile . ".tpl", $orderFormTemplateName)->getName();
} catch (WHMCS\Exception\View\TemplateNotFound $e) {
    $smartyvalues["carttpl"] = $orderFormTemplateName;
}
$smartyvalues["phoneNumberInputStyle"] = (int) WHMCS\Config\Setting::getValue("PhoneNumberDropdown");
Menu::addContext("productGroups", $orderfrm->getProductGroups(true));
Menu::addContext("productGroupId", $smartyvalues["gid"]);
Menu::addContext("domainRegistrationEnabled", $smartyvalues["registerdomainenabled"]);
Menu::addContext("domainTransferEnabled", $smartyvalues["transferdomainenabled"]);
Menu::addContext("domainRenewalEnabled", $smartyvalues["renewalsenabled"]);
Menu::addContext("domain", $smartyvalues["domain"]);
Menu::addContext("currency", $smartyvalues["currency"]);
Menu::addContext("action", $a);
if ($whmcs->isInRequest("i")) {
    Menu::addContext("productInfoKey", $productInfoKey);
}
Menu::addContext("productId", $pid);
Menu::addContext("domainAction", $whmcs->get_req_var("domain"));
Menu::addContext("allowRemoteAuth", $allowcheckout);
Menu::primarySidebar("orderFormView");
Menu::secondarySidebar("orderFormView");
outputClientArea($templatefile, $nowrapper, array("ClientAreaPageCart"));

?>