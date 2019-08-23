<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function getTLDList($type = "register")
{
    global $currency;
    $currency_id = $currency["id"];
    $userId = (int) WHMCS\Session::get("uid");
    if (!$currency_id) {
        $currency_id = isset($_SESSION["currency"]) ? $_SESSION["currency"] : "";
        $currency = getCurrency($userId, $currency_id);
        $currency_id = $currency["id"];
    }
    $clientgroupid = isset($_SESSION["uid"]) ? get_query_val("tblclients", "groupid", array("id" => $_SESSION["uid"])) : "0";
    if (!$clientgroupid) {
        $clientgroupid = 0;
    }
    $isReg = strcasecmp($type, "register") == 0;
    $checkfields = array("msetupfee", "qsetupfee", "ssetupfee", "asetupfee", "bsetupfee", "monthly", "quarterly", "semiannually", "annually", "biennially");
    $query = "SELECT DISTINCT tbldomainpricing.extension";
    $query .= " FROM tbldomainpricing";
    $query .= " JOIN tblpricing ON tblpricing.relid=tbldomainpricing.id";
    if (!$isReg) {
        $query .= " JOIN tblpricing AS regcheck ON regcheck.relid=tbldomainpricing.id";
    }
    $query .= " WHERE";
    $query .= " tblpricing.type=?";
    $query .= " AND tblpricing.currency=?";
    $query .= " AND (tblpricing.tsetupfee=? OR tblpricing.tsetupfee=0)";
    if (!$isReg) {
        $query .= " AND regcheck.type=\"domainregister\"";
        $query .= " AND regcheck.currency=tblpricing.currency";
        $query .= " AND regcheck.tsetupfee=tblpricing.tsetupfee";
    }
    $extraConds = array();
    foreach ($checkfields as $field) {
        $cond = "(tblpricing." . $field . " >= 0 ";
        if (!$isReg) {
            $cond .= " AND regcheck." . $field . " >= 0";
        }
        $cond .= ")";
        $extraConds[] = $cond;
    }
    $query .= " AND (" . implode(" OR ", $extraConds) . ")";
    $query .= " ORDER BY tbldomainpricing.order ASC";
    $bindings = array("domain" . $type, $currency_id, $clientgroupid);
    $result = WHMCS\Database\Capsule::connection()->select($query, $bindings);
    $extensions = array_map(function ($item) {
        return $item->extension;
    }, $result);
    return $extensions;
}
function getTLDPriceList($tld, $display = false, $renewpricing = "", $userid = 0, $useCache = true)
{
    global $currency;
    if (!$currency || !is_array($currency)) {
        $currency = getCurrency(WHMCS\Session::get("uid"), WHMCS\Session::get("currency"));
    }
    if (!$userid && WHMCS\Session::get("uid")) {
        $userid = WHMCS\Session::get("uid");
    }
    if (ltrim($tld, ".") == $tld) {
        $tld = "." . $tld;
    }
    static $pricingCache = NULL;
    $cacheKey = NULL;
    if (!$pricingCache) {
        $pricingCache = array();
    } else {
        foreach ($pricingCache as $key => $pricing) {
            if ($pricing["tld"] == $tld && $pricing["display"] == $display && $pricing["renewpricing"] == $renewpricing && $pricing["userid"] == $userid) {
                if ($useCache) {
                    return $pricing["pricing"];
                }
                $cacheKey = $key;
                break;
            }
        }
    }
    if (is_null($cacheKey)) {
        $pricing = array("tld" => $tld, "display" => $display, "renewpricing" => $renewpricing, "userid" => $userid);
        $cacheKey = count($pricingCache);
        $pricingCache[$cacheKey] = $pricing;
    }
    if ($renewpricing == "renew") {
        $renewpricing = true;
    }
    $currency_id = $currency["id"];
    try {
        $extensionData = WHMCS\Domains\Extension::where("extension", $tld)->firstOrFail(array("id"));
        $id = $extensionData->id;
    } catch (Exception $e) {
        return array();
    }
    $clientgroupid = $userid ? get_query_val("tblclients", "groupid", array("id" => $userid)) : "0";
    $checkfields = array("msetupfee", "qsetupfee", "ssetupfee", "asetupfee", "bsetupfee", "monthly", "quarterly", "semiannually", "annually", "biennially");
    $pricingData = WHMCS\Database\Capsule::table("tblpricing")->whereIn("type", array("domainregister", "domaintransfer", "domainrenew"))->where("currency", "=", $currency_id)->where("relid", "=", $id)->orderBy("tsetupfee", "desc")->get();
    $sortedData = array("domainregister" => array(), "domaintransfer" => array(), "domainrenew" => array());
    foreach ($pricingData as $entry) {
        $entryPricingGroupId = (int) $entry->tsetupfee;
        if ($entryPricingGroupId == 0 || $entryPricingGroupId == $clientgroupid) {
            $type = $entry->type;
            if (empty($sortedData[$type])) {
                $sortedData[$type] = (array) $entry;
            }
        }
    }
    if (!$renewpricing || $renewpricing === "transfer") {
        $data = $sortedData["domainregister"];
        foreach ($checkfields as $k => $v) {
            $register[$k + 1] = $data[$v] ?: -1;
        }
        $data = $sortedData["domaintransfer"];
        foreach ($checkfields as $k => $v) {
            $transfer[$k + 1] = $data[$v] ?: -1;
        }
    }
    if (!$renewpricing || $renewpricing !== "transfer") {
        $data = $sortedData["domainrenew"];
        foreach ($checkfields as $k => $v) {
            $renew[$k + 1] = $data[$v] ?: -1;
        }
    }
    $tldpricing = array();
    $years = 1;
    while ($years <= 10) {
        if ($renewpricing === "transfer") {
            if (0 <= $register[$years] && 0 <= $transfer[$years]) {
                if ($display) {
                    $transfer[$years] = formatCurrency($transfer[$years]);
                }
                $tldpricing[$years]["transfer"] = $transfer[$years];
            }
        } else {
            if ($renewpricing) {
                if (0 < $renew[$years]) {
                    if ($display) {
                        $renew[$years] = formatCurrency($renew[$years]);
                    }
                    $tldpricing[$years]["renew"] = $renew[$years];
                }
            } else {
                if (0 <= $register[$years]) {
                    if ($display) {
                        $register[$years] = formatCurrency($register[$years]);
                    }
                    $tldpricing[$years]["register"] = $register[$years];
                    if (0 <= $transfer[$years]) {
                        if ($display) {
                            $transfer[$years] = formatCurrency($transfer[$years]);
                        }
                        $tldpricing[$years]["transfer"] = $transfer[$years];
                    }
                    if (0 < $renew[$years]) {
                        if ($display) {
                            $renew[$years] = formatCurrency($renew[$years]);
                        }
                        $tldpricing[$years]["renew"] = $renew[$years];
                    }
                }
            }
        }
        $years += 1;
    }
    $pricingCache[$cacheKey]["pricing"] = $tldpricing;
    return $tldpricing;
}
function cleanDomainInput($val)
{
    global $CONFIG;
    $val = trim($val);
    if (!$CONFIG["AllowIDNDomains"]) {
        $val = strtolower($val);
    }
    return $val;
}
function checkDomainisValid($sld, $tld)
{
    global $CONFIG;
    if ($sld[0] == "-" || $sld[strlen($sld) - 1] == "-") {
        return 0;
    }
    $isIdn = $isIdnTld = $skipAllowIDNDomains = false;
    if ($CONFIG["AllowIDNDomains"]) {
        $idnConvert = new WHMCS\Domains\Idna();
        $idnConvert->encode($sld);
        if ($idnConvert->get_last_error() && $idnConvert->get_last_error() != "The given string does not contain encodable chars") {
            return 0;
        }
        if ($idnConvert->get_last_error() && $idnConvert->get_last_error() == "The given string does not contain encodable chars") {
            $skipAllowIDNDomains = true;
        } else {
            $isIdn = true;
        }
    }
    if ($isIdn === false) {
        if (preg_replace("/[^.%\$^'#~@&*(),_£?!+=:{}[]()|\\/ \\\\ ]/", "", $sld)) {
            return 0;
        }
        if ((!$CONFIG["AllowIDNDomains"] || $skipAllowIDNDomains === true) && preg_replace("/[^a-z0-9-.]/i", "", $sld . $tld) != $sld . $tld) {
            return 0;
        }
        if (preg_replace("/[^a-z0-9-.]/", "", $tld) != $tld) {
            return 0;
        }
        $validMask = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-";
        if (strspn($sld, $validMask) != strlen($sld)) {
            return 0;
        }
    }
    run_hook("DomainValidation", array("sld" => $sld, "tld" => $tld));
    if ($sld === false && $sld !== 0 || !$tld) {
        return 0;
    }
    $coreTLDs = array(".com", ".net", ".org", ".info", "biz", ".mobi", ".name", ".asia", ".tel", ".in", ".mn", ".bz", ".cc", ".tv", ".us", ".me", ".co.uk", ".me.uk", ".org.uk", ".net.uk", ".ch", ".li", ".de", ".jp");
    $DomainMinLengthRestrictions = $DomainMaxLengthRestrictions = array();
    require ROOTDIR . "/configuration.php";
    foreach ($coreTLDs as $cTLD) {
        if (!array_key_exists($cTLD, $DomainMinLengthRestrictions)) {
            $DomainMinLengthRestrictions[$cTLD] = 3;
        }
        if (!array_key_exists($cTLD, $DomainMaxLengthRestrictions)) {
            $DomainMaxLengthRestrictions[$cTLD] = 63;
        }
    }
    if (array_key_exists($tld, $DomainMinLengthRestrictions) && strlen($sld) < $DomainMinLengthRestrictions[$tld]) {
        return 0;
    }
    if (array_key_exists($tld, $DomainMaxLengthRestrictions) && $DomainMaxLengthRestrictions[$tld] < strlen($sld)) {
        return 0;
    }
    return 1;
}
function disableAutoRenew($domainid)
{
    $data = get_query_vals("tbldomains", "id,domain,nextduedate,userid", array("id" => $domainid));
    $domainid = $data["id"];
    $domainname = $data["domain"];
    $nextduedate = $data["nextduedate"];
    $userId = $data["userid"];
    if (!$domainid) {
        return false;
    }
    update_query("tbldomains", array("nextinvoicedate" => $nextduedate, "donotrenew" => "1"), array("id" => $domainid));
    $who = "Client";
    if ($_SESSION["adminid"]) {
        $who = "Admin";
    }
    logActivity((string) $who . " Disabled Domain Auto Renew - Domain ID: " . $domainid . " - Domain: " . $domainname, $userId);
    $result = select_query("tblinvoiceitems", "tblinvoiceitems.id,tblinvoiceitems.invoiceid", array("type" => "Domain", "relid" => $domainid, "status" => "Unpaid", "tblinvoices.userid" => $_SESSION["uid"]), "", "", "", "tblinvoices ON tblinvoices.id=tblinvoiceitems.invoiceid");
    while ($data = mysql_fetch_array($result)) {
        $itemid = $data["id"];
        $invoiceid = $data["invoiceid"];
        $result2 = select_query("tblinvoiceitems", "COUNT(*)", array("invoiceid" => $invoiceid));
        $data = mysql_fetch_array($result2);
        $itemcount = $data[0];
        $otheritemcount = 0;
        if (1 < $itemcount) {
            $otheritemcount = get_query_val("tblinvoiceitems", "COUNT(*)", "invoiceid=" . (int) $invoiceid . " AND id!=" . (int) $itemid . " AND type NOT IN ('PromoHosting','PromoDomain','GroupDiscount')");
        }
        if ($itemcount == 1 || $otheritemcount == 0) {
            update_query("tblinvoiceitems", array("type" => "", "relid" => "0"), array("id" => $itemid));
            update_query("tblinvoices", array("status" => "Cancelled"), array("id" => $invoiceid));
            logActivity("Cancelled Previous Domain Renewal Invoice - Invoice ID: " . $invoiceid . " - Domain: " . $domainname, $userId);
            run_hook("InvoiceCancelled", array("invoiceid" => $invoiceid));
        } else {
            delete_query("tblinvoiceitems", array("id" => $itemid));
            updateInvoiceTotal($invoiceid);
            logActivity("Removed Previous Domain Renewal Line Item - Invoice ID: " . $invoiceid . " - Domain: " . $domainname, $userId);
        }
    }
}
function multipleTldPriceListings(array $tlds)
{
    $tldPriceListings = array();
    static $groups = NULL;
    if (is_null($groups)) {
        $groups = WHMCS\Database\Capsule::table("tbldomainpricing")->pluck("group", "extension");
    }
    foreach ($tlds as $tld) {
        $tldPricing = gettldpricelist($tld, true, "", (int) WHMCS\Session::get("uid"));
        $firstOption = current($tldPricing);
        $year = key($tldPricing);
        $saleGroup = isset($groups[$tld]) && strtolower($groups[$tld]) != "none" ? strtolower($groups[$tld]) : "";
        $tldPriceListings[] = array("tld" => $tld, "tldNoDots" => str_replace(".", "", $tld), "period" => $year, "register" => isset($firstOption["register"]) ? $firstOption["register"] : "", "transfer" => isset($firstOption["transfer"]) ? $firstOption["transfer"] : "", "renew" => isset($firstOption["renew"]) ? $firstOption["renew"] : "", "group" => $saleGroup, "groupDisplayName" => $saleGroup ? Lang::trans("domainCheckerSalesGroup." . $saleGroup) : "");
    }
    return $tldPriceListings;
}
function getSpotlightTlds()
{
    return array_filter(explode(",", WHMCS\Config\Setting::getValue("SpotlightTLDs")), function ($item) {
        return $item;
    });
}
function getSpotlightTldsWithPricing()
{
    return multipletldpricelistings(getspotlighttlds());
}

?>