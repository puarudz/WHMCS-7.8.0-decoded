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
if (!function_exists("addClient")) {
    require ROOTDIR . "/includes/clientfunctions.php";
}
if (!function_exists("getCartConfigOptions")) {
    require ROOTDIR . "/includes/configoptionsfunctions.php";
}
if (!function_exists("getTLDPriceList")) {
    require ROOTDIR . "/includes/domainfunctions.php";
}
if (!function_exists("updateInvoiceTotal")) {
    require ROOTDIR . "/includes/invoicefunctions.php";
}
if (!function_exists("createInvoices")) {
    require ROOTDIR . "/includes/processinvoices.php";
}
if (!function_exists("calcCartTotals")) {
    require ROOTDIR . "/includes/orderfunctions.php";
}
if (!function_exists("ModuleBuildParams")) {
    require ROOTDIR . "/includes/modulefunctions.php";
}
if (!function_exists("cartPreventDuplicateProduct")) {
    require ROOTDIR . "/includes/cartfunctions.php";
}
if ($promocode && !$promooverride) {
    define("CLIENTAREA", true);
}
$whmcs = WHMCS\Application::getInstance();
try {
    $client = WHMCS\User\Client::findOrFail($whmcs->get_req_var("clientid"));
} catch (Exception $e) {
    $apiresults = array("result" => "error", "message" => "Client ID Not Found");
    return NULL;
}
$userid = (int) $client->id;
$blockedStatus = array("Closed");
if (in_array($client->status, $blockedStatus)) {
    $apiresults = array("result" => "error", "message" => "Unable to add order when client status is " . $client->status);
} else {
    $gatewaysarray = array();
    $result = select_query("tblpaymentgateways", "gateway", array("setting" => "name"));
    while ($data = mysql_fetch_array($result)) {
        $gatewaysarray[] = $data["gateway"];
    }
    if (!in_array($paymentmethod, $gatewaysarray)) {
        $apiresults = array("result" => "error", "message" => "Invalid Payment Method. Valid options include " . implode(",", $gatewaysarray));
    } else {
        global $remote_ip;
        if ($clientip) {
            $remote_ip = $clientip;
        }
        $_SESSION["uid"] = $userid;
        global $currency;
        $currency = getCurrency($userid);
        $_SESSION["cart"] = array();
        if (is_array($pid)) {
            foreach ($pid as $i => $prodid) {
                if ($prodid) {
                    $proddomain = $domain[$i];
                    $prodbillingcycle = $billingcycle[$i];
                    $configoptionsarray = array();
                    $customfieldsarray = array();
                    $domainfieldsarray = array();
                    $addonsarray = array();
                    if ($addons[$i]) {
                        $addonsarray = explode(",", $addons[$i]);
                    }
                    if ($configoptions[$i]) {
                        $configoptionsarray = safe_unserialize(base64_decode($configoptions[$i]));
                    }
                    if ($customfields[$i]) {
                        $customfieldsarray = safe_unserialize(base64_decode($customfields[$i]));
                    }
                    $productarray = array("pid" => $prodid, "domain" => $proddomain, "billingcycle" => $prodbillingcycle, "server" => $hostname[$i] || $ns1prefix[$i] || $ns2prefix[$i] || $rootpw[$i] ? array("hostname" => $hostname[$i], "ns1prefix" => $ns1prefix[$i], "ns2prefix" => $ns2prefix[$i], "rootpw" => $rootpw[$i]) : "", "configoptions" => $configoptionsarray, "customfields" => $customfieldsarray, "addons" => $addonsarray);
                    if (strlen($priceoverride[$i])) {
                        $productarray["priceoverride"] = $priceoverride[$i];
                    }
                    $_SESSION["cart"]["products"][] = $productarray;
                }
            }
        } else {
            if ($pid) {
                $configoptionsarray = array();
                $customfieldsarray = array();
                $domainfieldsarray = array();
                $addonsarray = array();
                if ($addons) {
                    $addonsarray = explode(",", $addons);
                }
                if ($configoptions) {
                    $configoptions = base64_decode($configoptions);
                    $configoptionsarray = safe_unserialize($configoptions);
                }
                if ($customfields) {
                    $customfields = base64_decode($customfields);
                    $customfieldsarray = safe_unserialize($customfields);
                }
                $productarray = array("pid" => $pid, "domain" => $domain, "billingcycle" => $billingcycle, "server" => $hostname || $ns1prefix || $ns2prefix || $rootpw ? array("hostname" => $hostname, "ns1prefix" => $ns1prefix, "ns2prefix" => $ns2prefix, "rootpw" => $rootpw) : "", "configoptions" => $configoptionsarray, "customfields" => $customfieldsarray, "addons" => $addonsarray);
                if (strlen($priceoverride)) {
                    $productarray["priceoverride"] = $priceoverride;
                }
                $_SESSION["cart"]["products"][] = $productarray;
            }
        }
        if (is_array($domaintype)) {
            foreach ($domaintype as $i => $type) {
                if ($type) {
                    if ($domainfields[$i]) {
                        $domainfields[$i] = base64_decode($domainfields[$i]);
                        $domainfieldsarray[$i] = safe_unserialize($domainfields[$i]);
                    }
                    $domainArray = array("type" => $type, "domain" => $domain[$i], "regperiod" => $regperiod[$i], "dnsmanagement" => $dnsmanagement[$i], "emailforwarding" => $emailforwarding[$i], "idprotection" => $idprotection[$i], "eppcode" => $eppcode[$i], "fields" => $domainfieldsarray[$i]);
                    if (isset($domainpriceoverride[$i]) && 0 < strlen($domainpriceoverride[$i])) {
                        $domainArray["domainpriceoverride"] = $domainpriceoverride[$i];
                    }
                    if (isset($domainrenewoverride[$i]) && 0 < strlen($domainrenewoverride[$i])) {
                        $domainArray["domainrenewoverride"] = $domainrenewoverride[$i];
                    }
                    $_SESSION["cart"]["domains"][] = $domainArray;
                }
            }
        } else {
            if ($domaintype) {
                if ($domainfields) {
                    $domainfields = base64_decode($domainfields);
                    $domainfieldsarray = safe_unserialize($domainfields);
                }
                $domainArray = array("type" => $domaintype, "domain" => $domain, "regperiod" => $regperiod, "dnsmanagement" => $dnsmanagement, "emailforwarding" => $emailforwarding, "idprotection" => $idprotection, "eppcode" => $eppcode, "fields" => $domainfieldsarray);
                if (isset($domainpriceoverride) && 0 < strlen($domainpriceoverride)) {
                    $domainArray["domainpriceoverride"] = $domainpriceoverride;
                }
                if (isset($domainrenewoverride) && 0 < strlen($domainrenewoverride)) {
                    $domainArray["domainrenewoverride"] = $domainrenewoverride;
                }
                $_SESSION["cart"]["domains"][] = $domainArray;
            }
        }
        if ($addonid) {
            $addonid = get_query_val("tbladdons", "id", array("id" => $addonid));
            if (!$addonid) {
                $apiresults = array("result" => "error", "message" => "Addon ID invalid");
                return NULL;
            }
            $serviceid = get_query_val("tblhosting", "id", array("userid" => $userid, "id" => $serviceid));
            if (!$serviceid) {
                $apiresults = array("result" => "error", "message" => "Service ID not owned by Client ID provided");
                return NULL;
            }
            $_SESSION["cart"]["addons"][] = array("id" => $addonid, "productid" => $serviceid);
        }
        if ($addonids) {
            foreach ($addonids as $i => $addonid) {
                $addonid = get_query_val("tbladdons", "id", array("id" => $addonid));
                if (!$addonid) {
                    $apiresults = array("result" => "error", "message" => "Addon ID invalid");
                    return NULL;
                }
                $serviceid = get_query_val("tblhosting", "id", array("userid" => $userid, "id" => $serviceids[$i]));
                if (!$serviceid) {
                    $apiresults = array("result" => "error", "message" => sprintf("Service ID %s not owned by Client ID provided", (int) $serviceids[$i]));
                    return NULL;
                }
                $_SESSION["cart"]["addons"][] = array("id" => $addonid, "productid" => $serviceid);
            }
        }
        $domainrenewals = $whmcs->get_req_var("domainrenewals");
        if ($domainrenewals) {
            foreach ($domainrenewals as $domain => $regperiod) {
                $domain = mysql_real_escape_string($domain);
                $sql = "SELECT `id`\n                FROM `tbldomains`\n                WHERE userid=" . $userid . " AND domain='" . $domain . "' AND status IN ('Active', 'Expired', 'Grace', 'Redemption')";
                $domainResult = full_query($sql);
                $domainData = mysql_fetch_array($domainResult);
                if (isset($domainData["id"])) {
                    $domainid = $domainData["id"];
                }
                if (!$domainid) {
                    $sql = "SELECT `status`\n                    FROM `tbldomains`\n                    WHERE userid=" . $userid . " AND domain='" . $domain . "'";
                    $domainResult = full_query($sql);
                    $domainData = mysql_fetch_array($domainResult);
                    $apiresults = array("result" => "error", "message" => "");
                    if (isset($domainData["status"])) {
                        $apiresults["message"] = "Domain status is set to '" . $domainData["status"] . "' and cannot be renewed";
                    } else {
                        $apiresults["message"] = "Domain not owned by Client ID provided";
                    }
                    return NULL;
                }
                $_SESSION["cart"]["renewals"][$domainid] = $regperiod;
            }
        }
        $cartitems = count($_SESSION["cart"]["products"]) + count($_SESSION["cart"]["addons"]) + count($_SESSION["cart"]["domains"]) + count($_SESSION["cart"]["renewals"]);
        if (!$cartitems) {
            $apiresults = array("result" => "error", "message" => "No items added to cart so order cannot proceed");
            return NULL;
        }
        $_SESSION["cart"]["ns1"] = $nameserver1;
        $_SESSION["cart"]["ns2"] = $nameserver2;
        $_SESSION["cart"]["ns3"] = $nameserver3;
        $_SESSION["cart"]["ns4"] = $nameserver4;
        $_SESSION["cart"]["paymentmethod"] = $paymentmethod;
        $_SESSION["cart"]["promo"] = $promocode;
        $_SESSION["cart"]["notes"] = $notes;
        if ($contactid) {
            $_SESSION["cart"]["contact"] = $contactid;
        }
        if ($noinvoice) {
            $_SESSION["cart"]["geninvoicedisabled"] = true;
        }
        if ($noinvoiceemail) {
            $CONFIG["NoInvoiceEmailOnOrder"] = true;
        }
        if ($noemail) {
            $_SESSION["cart"]["orderconfdisabled"] = true;
        }
        $cartdata = calcCartTotals(true, false, $currency);
        if ($cartdata["result"] == "error") {
            $apiresults = $cartdata;
            return NULL;
        }
        if ($affid) {
            $verifyAffId = WHMCS\Database\Capsule::table("tblaffiliates")->where("id", $affid)->first();
        }
        if ($affid && is_array($_SESSION["orderdetails"]["Products"]) && !empty($verifyAffId) && $_SESSION["uid"] != $verifyAffId->clientid) {
            foreach ($_SESSION["orderdetails"]["Products"] as $productid) {
                insert_query("tblaffiliatesaccounts", array("affiliateid" => $affid, "relid" => $productid));
            }
        } else {
            unset($affid);
        }
        $productids = $addonids = $domainids = "";
        if (is_array($_SESSION["orderdetails"]["Products"])) {
            $productids = implode(",", $_SESSION["orderdetails"]["Products"]);
        }
        if (is_array($_SESSION["orderdetails"]["Addons"])) {
            $addonids = implode(",", $_SESSION["orderdetails"]["Addons"]);
        }
        if (is_array($_SESSION["orderdetails"]["Domains"])) {
            $domainids = implode(",", $_SESSION["orderdetails"]["Domains"]);
        }
        $apiresults = array("result" => "success", "orderid" => $_SESSION["orderdetails"]["OrderID"], "productids" => $productids, "serviceids" => $productids, "addonids" => $addonids, "domainids" => $domainids);
        if (!$noinvoice) {
            $apiresults["invoiceid"] = $_SESSION["orderdetails"]["InvoiceID"] ? $_SESSION["orderdetails"]["InvoiceID"] : get_query_val("tblorders", "invoiceid", array("id" => $_SESSION["orderdetails"]["OrderID"]));
        }
    }
}

?>