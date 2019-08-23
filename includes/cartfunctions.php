<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function cartPreventDuplicateProduct($domain)
{
    if (!$domain) {
        return true;
    }
    $domains = array();
    foreach ($_SESSION["cart"]["products"] as $k => $values) {
        $domains[$k] = $values["domain"];
    }
    if (in_array($domain, $domains)) {
        $i = array_search($domain, $domains);
        unset($_SESSION["cart"]["products"][$i]);
        $_SESSION["cart"]["products"] = array_values($_SESSION["cart"]["products"]);
    }
}
function cartPreventDuplicateDomain($domain)
{
    $domains = array();
    if (!empty($_SESSION["cart"]["domains"]) && is_array($_SESSION["cart"]["domains"])) {
        foreach ($_SESSION["cart"]["domains"] as $k => $values) {
            $domains[$k] = $values["domain"];
        }
        if (in_array($domain, $domains)) {
            $i = array_search($domain, $domains);
            unset($_SESSION["cart"]["domains"][$i]);
            $_SESSION["cart"]["domains"] = array_values($_SESSION["cart"]["domains"]);
        }
    }
}
function bundlesConvertBillingCycle($cycle)
{
    return str_replace(array("-", " "), "", strtolower($cycle));
}
function bundlesStepCompleteRedirect($lastconfig)
{
    $i = $lastconfig["i"];
    if ($lastconfig["type"] == "product" && !isset($_SESSION["cart"]["products"][$i]["bnum"])) {
        return false;
    }
    if ($lastconfig["type"] == "domain" && !isset($_SESSION["cart"]["domains"][$i]["bnum"])) {
        return false;
    }
    if (is_array($_SESSION["cart"]["bundle"])) {
        $bnum = count($_SESSION["cart"]["bundle"]);
        $bnum--;
        $bundledata = $_SESSION["cart"]["bundle"][$bnum];
        $bid = $bundledata["bid"];
        $step = $bundledata["step"];
        $complete = $bundledata["complete"];
        if (!$complete) {
            $data = get_query_vals("tblbundles", "", array("id" => $bid));
            $bid = $data["id"];
            $itemdata = $data["itemdata"];
            $itemdata = safe_unserialize($itemdata);
            $_SESSION["cart"]["bundle"][$bnum]["step"] = $step + 1;
            $step = $_SESSION["cart"]["bundle"][$bnum]["step"];
            $vals = $itemdata[$step];
            if (is_array($vals)) {
                if ($vals["type"] == "product") {
                    $vals["bnum"] = $bnum;
                    $vals["bitem"] = $step;
                    $vals["billingcycle"] = bundlesconvertbillingcycle($vals["billingcycle"]);
                    $_SESSION["cart"]["passedvariables"] = $vals;
                    unset($_SESSION["cart"]["lastconfigured"]);
                    redir("a=add&pid=" . $vals["pid"]);
                } else {
                    if ($vals["type"] == "domain") {
                        $vals["bnum"] = $bnum;
                        $vals["bitem"] = $step;
                        $_SESSION["cart"]["passedvariables"] = $vals;
                        unset($_SESSION["cart"]["lastconfigured"]);
                        redir("a=add&domain=register");
                    }
                }
            } else {
                $_SESSION["cart"]["bundle"][$bnum]["complete"] = 1;
                $step = $_SESSION["cart"]["bundle"][$bnum]["complete"];
            }
        }
    }
}
function bundlesValidateProductConfig($key, $billingcycle, $configoptions, $addons)
{
    global $_LANG;
    $proddata = $_SESSION["cart"]["products"][$key];
    if (!isset($proddata["bnum"])) {
        return false;
    }
    $bid = $_SESSION["cart"]["bundle"][$proddata["bnum"]]["bid"];
    if (!$bid) {
        return false;
    }
    $data = get_query_vals("tblbundles", "", array("id" => $bid));
    $itemdata = $data["itemdata"];
    $itemdata = safe_unserialize($itemdata);
    $proditemdata = $itemdata[$proddata["bitem"]];
    $errors = "";
    $productname = WHMCS\Product\Product::getProductName($proddata["pid"]);
    if ($proditemdata["billingcycle"] && bundlesconvertbillingcycle($proditemdata["billingcycle"]) != $billingcycle) {
        $errors .= "<li>" . sprintf($_LANG["bundlewarningproductcycle"], $proditemdata["billingcycle"], $productname);
    }
    foreach ($proditemdata["configoption"] as $cid => $opid) {
        if ($opid != $configoptions[$cid]) {
            $data = get_query_vals("tblproductconfigoptions", "optionname,optiontype,(SELECT optionname FROM tblproductconfigoptionssub WHERE id='" . (int) $opid . "') AS subopname", array("id" => $cid));
            if ($data["optiontype"] == 1 || $data["optiontype"] == 2) {
                $errors .= "<li>" . sprintf($_LANG["bundlewarningproductconfopreq"], $data["subopname"], $data["optionname"]);
            } else {
                if ($data["optiontype"] == 3) {
                    if ($opid) {
                        $errors .= "<li>" . sprintf($_LANG["bundlewarningproductconfopyesnoenable"], $data["optionname"]);
                    } else {
                        $errors .= "<li>" . sprintf($_LANG["bundlewarningproductconfopyesnodisable"], $data["optionname"]);
                    }
                } else {
                    if ($data["optiontype"] == 4) {
                        $errors .= "<li>" . sprintf($_LANG["bundlewarningproductconfopqtyreq"], $opid, $data["optionname"]);
                    }
                }
            }
        }
    }
    if ($proditemdata["addons"]) {
        foreach ($proditemdata["addons"] as $addonid) {
            if (!in_array($addonid, $addons)) {
                $errors .= "<li>" . sprintf($_LANG["bundlewarningproductaddonreq"], get_query_val("tbladdons", "name", array("id" => $addonid)), $productname);
            }
        }
    }
    return $errors;
}
function bundlesValidateCheckout()
{
    global $_LANG;
    if (!isset($_SESSION["cart"]["bundle"])) {
        return "";
    }
    $bundlesess = $_SESSION["cart"]["bundle"];
    foreach ($bundlesess as $k => $v) {
        unset($bundlesess[$k]["warnings"]);
    }
    $bundledata = $warnings = array();
    foreach ($bundlesess as $bnum => $vals) {
        $bid = $vals["bid"];
        $data = get_query_vals("tblbundles", "", array("id" => $bid));
        $allowpromo = $data["allowpromo"];
        $itemdata = $data["itemdata"];
        $itemdata = safe_unserialize($itemdata);
        $bundledata[$bid] = $itemdata;
        if ($_SESSION["cart"]["promo"] && !$allowpromo) {
            $warnings[] = $_LANG["bundlewarningpromo"];
            $bundlesess[$bnum]["warnings"] = 1;
        }
    }
    $numitemsperbundle = $productbundleddomains = $domainsincart = array();
    foreach ($_SESSION["cart"]["domains"] as $k => $values) {
        $domainsincart[$values["domain"]] = $k;
    }
    foreach ($_SESSION["cart"]["products"] as $k => $v) {
        if (isset($v["bnum"])) {
            $bnum = $v["bnum"];
            $bitem = $v["bitem"];
            $pid = $v["pid"];
            $domain = $v["domain"];
            $billingcycle = $v["billingcycle"];
            $configoptions = $v["configoptions"];
            $addons = $v["addons"];
            $bid = $_SESSION["cart"]["bundle"][$bnum]["bid"];
            $itemdata = $bundledata[$bid][$bitem];
            if ($itemdata["type"] != "product" || $pid != $itemdata["pid"]) {
                unset($_SESSION["cart"]["products"][$k]["bnum"]);
                unset($_SESSION["cart"]["products"][$k]["bitem"]);
            } else {
                $numitemsperbundle[$bnum]++;
                $productname = WHMCS\Product\Product::getProductName($pid);
                if ($itemdata["billingcycle"] && bundlesconvertbillingcycle($itemdata["billingcycle"]) != $billingcycle) {
                    $warnings[] = sprintf($_LANG["bundlewarningproductcycle"], $itemdata["billingcycle"], $productname);
                    $bundlesess[$bnum]["warnings"] = 1;
                }
                foreach ($itemdata["configoption"] as $cid => $opid) {
                    if ($opid != $configoptions[$cid]) {
                        $data = get_query_vals("tblproductconfigoptions", "optionname,optiontype,(SELECT optionname FROM tblproductconfigoptionssub WHERE id='" . (int) $opid . "') AS subopname", array("id" => $cid));
                        if ($data["optiontype"] == 1 || $data["optiontype"] == 2) {
                            $warnings[] = sprintf($_LANG["bundlewarningproductconfopreq"], $data["subopname"], $data["optionname"]);
                            $bundlesess[$bnum]["warnings"] = 1;
                        } else {
                            if ($data["optiontype"] == 3) {
                                if ($opid) {
                                    $warnings[] = sprintf($_LANG["bundlewarningproductconfopyesnoenable"], $data["optionname"]);
                                } else {
                                    $warnings[] = sprintf($_LANG["bundlewarningproductconfopyesnodisable"], $data["optionname"]);
                                }
                                $bundlesess[$bnum]["warnings"] = 1;
                            } else {
                                if ($data["optiontype"] == 4) {
                                    $warnings[] = sprintf($_LANG["bundlewarningproductconfopqtyreq"], $opid, $data["optionname"]);
                                    $bundlesess[$bnum]["warnings"] = 1;
                                }
                            }
                        }
                    }
                }
                if ($itemdata["addons"]) {
                    foreach ($itemdata["addons"] as $addonid) {
                        if (!in_array($addonid, $addons)) {
                            $warnings[] = sprintf($_LANG["bundlewarningproductaddonreq"], get_query_val("tbladdons", "name", array("id" => $addonid)), $productname);
                            $bundlesess[$bnum]["warnings"] = 1;
                        }
                    }
                }
                if (array_key_exists($domain, $domainsincart)) {
                    $domid = $domainsincart[$domain];
                    $v = $_SESSION["cart"]["domains"][$domid];
                    $regperiod = $v["regperiod"];
                    if (is_array($itemdata["tlds"])) {
                        $domaintld = explode(".", $domain, 2);
                        $domaintld = "." . $domaintld[1];
                        if (!in_array($domaintld, $itemdata["tlds"])) {
                            $warnings[] = sprintf($_LANG["bundlewarningdomaintld"], implode(",", $itemdata["tlds"]), $domain);
                            $bundlesess[$bnum]["warnings"] = 1;
                        }
                    }
                    if ($itemdata["regperiod"] && $itemdata["regperiod"] != $regperiod) {
                        $warnings[] = sprintf($_LANG["bundlewarningdomainregperiod"], $itemdata["regperiod"], $domain);
                        $bundlesess[$bnum]["warnings"] = 1;
                    }
                    if (is_array($itemdata["domaddons"])) {
                        foreach ($itemdata["domaddons"] as $domaddon) {
                            if (!$v[$domaddon]) {
                                $warnings[] = sprintf($_LANG["bundlewarningdomainaddon"], $_LANG["domain" . $domaddon], $domain);
                                $bundlesess[$bnum]["warnings"] = 1;
                            }
                        }
                    }
                    $productbundleddomains[$domain] = array($bnum, $bitem);
                } else {
                    if (is_array($itemdata["tlds"]) || $itemdata["regperiod"] || is_array($itemdata["domaddons"])) {
                        $warnings[] = sprintf($_LANG["bundlewarningdomainreq"], $productname);
                        $bundlesess[$bnum]["warnings"] = 1;
                    }
                }
            }
        }
    }
    foreach ($_SESSION["cart"]["domains"] as $k => $v) {
        if (isset($v["bnum"])) {
            $bnum = $v["bnum"];
            $bitem = $v["bitem"];
            $domain = $v["domain"];
            $regperiod = $v["regperiod"];
            $bid = $_SESSION["cart"]["bundle"][$bnum]["bid"];
            $itemdata = $bundledata[$bid][$bitem];
            if ($itemdata["type"] != "domain") {
                unset($_SESSION["cart"]["domains"][$k]["bnum"]);
                unset($_SESSION["cart"]["domains"][$k]["bitem"]);
            } else {
                $numitemsperbundle[$bnum]++;
                if (is_array($itemdata["tlds"])) {
                    $domaintld = explode(".", $domain, 2);
                    $domaintld = "." . $domaintld[1];
                    if (!in_array($domaintld, $itemdata["tlds"])) {
                        $warnings[] = sprintf($_LANG["bundlewarningdomaintld"], implode(",", $itemdata["tlds"]), $domain);
                        $bundlesess[$bnum]["warnings"] = 1;
                    }
                }
                if ($itemdata["regperiod"] && $itemdata["regperiod"] != $regperiod) {
                    $warnings[] = sprintf($_LANG["bundlewarningdomainregperiod"], $itemdata["regperiod"], $domain);
                    $bundlesess[$bnum]["warnings"] = 1;
                }
                if (is_array($itemdata["addons"])) {
                    foreach ($itemdata["addons"] as $domaddon) {
                        if (!$v[$domaddon]) {
                            $warnings[] = sprintf($_LANG["bundlewarningdomainaddon"], $_LANG["domain" . $domaddon], $domain);
                            $bundlesess[$bnum]["warnings"] = 1;
                        }
                    }
                }
            }
        }
    }
    foreach ($bundlesess as $bnum => $vals) {
        $bid = $vals["bid"];
        $bundletotalitems = count($bundledata[$bid]);
        if ($bundletotalitems != $numitemsperbundle[$bnum]) {
            unset($bundlesess[$bnum]);
        }
    }
    $_SESSION["cart"]["bundle"] = $bundlesess;
    $_SESSION["cart"]["prodbundleddomains"] = $productbundleddomains;
    return $warnings;
}
function bundlesGetProductPriceOverride($type, $key)
{
    global $currency;
    $proddata = $_SESSION["cart"][$type . "s"][$key];
    $prodbundleddomain = false;
    if (!isset($proddata["bnum"]) && $type == "domain") {
        $domain = $proddata["domain"];
        if (is_array($_SESSION["cart"]["prodbundleddomains"][$domain])) {
            $proddata["bnum"] = $_SESSION["cart"]["prodbundleddomains"][$domain][0];
            $proddata["bitem"] = $_SESSION["cart"]["prodbundleddomains"][$domain][1];
        }
    }
    if (!isset($proddata["bnum"])) {
        return false;
    }
    $bid = $_SESSION["cart"]["bundle"][$proddata["bnum"]]["bid"];
    if (!$bid) {
        return false;
    }
    $bundlewarnings = $_SESSION["cart"]["bundle"][$proddata["bnum"]]["warnings"];
    if ($bundlewarnings) {
        return false;
    }
    $data = get_query_vals("tblbundles", "", array("id" => $bid));
    $itemdata = $data["itemdata"];
    $itemdata = safe_unserialize($itemdata);
    if ($type == "product" && $itemdata[$proddata["bitem"]]["priceoverride"]) {
        return convertCurrency($itemdata[$proddata["bitem"]]["price"], 1, $currency["id"]);
    }
    if ($type == "domain" && $itemdata[$proddata["bitem"]]["dompriceoverride"]) {
        return convertCurrency($itemdata[$proddata["bitem"]]["domprice"], 1, $currency["id"]);
    }
    return false;
}
function cartAvailabilityResultsBackwardsCompat(WHMCS\Domains\Domain $domainToLookup, WHMCS\Domains\DomainLookup\SearchResult $searchResult, $matchString)
{
    $availabilityResults = array(array("domain" => $searchResult->getDomain(), "status" => $searchResult->getStatus(), "regoptions" => $searchResult->getStatus() == $matchString ? $searchResult->pricing()->toArray() : array(), "suggestion" => false));
    $lookupProvider = WHMCS\Domains\DomainLookup\Provider::factory();
    foreach ($lookupProvider->getSuggestions($domainToLookup) as $suggestion) {
        $availabilityResults[] = array("domain" => $suggestion->getDomain(), "status" => $suggestion->getStatus(), "regoptions" => $suggestion->getStatus() == $matchString ? $suggestion->pricing()->toArray() : array(), "suggestion" => true);
    }
    return $availabilityResults;
}
function cartCheckIfDomainAlreadyOrdered(WHMCS\Domains\Domain $domainToCheck)
{
    $existingDomains = WHMCS\Database\Capsule::table("tbldomains")->where("domain", "=", $domainToCheck->getRawDomain())->whereIn("status", array("Active", "Pending", "Pending Registration", "Pending Transfer"))->get(array("domain"));
    foreach ($existingDomains as $domain) {
        if ($domain->domain == $domainToCheck->getRawDomain()) {
            return true;
        }
    }
    return false;
}

?>