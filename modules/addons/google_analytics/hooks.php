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
add_hook("ShoppingCartCheckoutCompletePage", 1, "google_analytics_hook_checkout_tracker");
add_hook("ClientAreaHeadOutput", 1, "google_analytics_hook_page_tracking");
function google_analytics_hook_checkout_tracker($vars)
{
    global $CONFIG;
    $modulevars = array();
    $result = select_query("tbladdonmodules", "", array("module" => "google_analytics"));
    while ($data = mysql_fetch_array($result)) {
        $value = $data["value"];
        $value = explode("|", $value);
        $value = trim($value[0]);
        $modulevars[$data["setting"]] = $value;
    }
    if (!$modulevars["code"]) {
        return false;
    }
    if ($modulevars["analytics_version"] == "Universal Analytics") {
        $universalAnalytics = true;
    } else {
        $universalAnalytics = false;
    }
    $orderid = $vars["orderid"];
    $ordernumber = $vars["ordernumber"];
    $invoiceid = $vars["invoiceid"];
    $ispaid = $vars["ispaid"];
    $amount = $subtotal = $vars["amount"];
    $paymentmethod = $vars["paymentmethod"];
    $clientdetails = $vars["clientdetails"];
    $result = select_query("tblorders", "renewals", array("id" => $orderid));
    $data = mysql_fetch_array($result);
    $renewals = $data["renewals"];
    if ($invoiceid) {
        $result = select_query("tblinvoices", "subtotal,tax,tax2,total", array("id" => $invoiceid));
        $data = mysql_fetch_array($result);
        $subtotal = $data["subtotal"];
        $tax = $data["tax"] + $data["tax2"];
        $total = $data["total"];
    }
    if (isset($_SESSION["gatracking"][$orderid])) {
        return false;
    }
    $_SESSION["gatracking"][$orderid] = 1;
    if ($universalAnalytics) {
        if (!empty($modulevars["domain"])) {
            $moduleDomain = "{ cookieDomain: '" . $modulevars["domain"] . "' }";
        } else {
            $moduleDomain = "'auto'";
        }
        $code = "\n<!-- Google Analytics -->\n<script>\n(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){\n(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),\n    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)\n    })(window,document,'script','//www.google-analytics.com/analytics.js','ga');\n\nga('create', '" . $modulevars["code"] . "', " . $moduleDomain . ");\nga('send', 'pageview');\n\n// ecommerce functions.\nga('require', 'ecommerce', 'ecommerce.js');\nga('ecommerce:addTransaction', {\n    id: '" . $orderid . "',\n    affiliation: 'WHMCS Cart',\n    revenue: '" . $subtotal . "',\n    tax: '" . $tax . "'\n});\n";
    } else {
        $code = "\n<script type=\"text/javascript\">\nvar _gaq = _gaq || [];\n_gaq.push(['_setAccount', '" . $modulevars["code"] . "']);";
        if ($modulevars["domain"]) {
            $code .= "\n_gaq.push(['_setDomainName', '" . $modulevars["domain"] . "']);";
        }
        $code .= "\n_gaq.push(['_trackPageview']);\n_gaq.push(['_addTrans',\n'" . $orderid . "',\n'WHMCS Cart',\n'" . $subtotal . "',\n'" . $tax . "',\n'0',\n'" . $clientdetails["city"] . "',\n'" . $clientdetails["state"] . "',\n'" . $clientdetails["country"] . "'\n]);\n";
    }
    $result = select_query("tblhosting", "tblhosting.id,tblproducts.id AS pid,tblproducts.name,tblproductgroups.name AS groupname,tblhosting.firstpaymentamount", array("orderid" => $orderid), "", "", "", "tblproducts ON tblproducts.id=tblhosting.packageid INNER JOIN tblproductgroups ON tblproductgroups.id=tblproducts.gid");
    while ($data = mysql_fetch_array($result)) {
        $serviceid = $data["id"];
        $itempid = $data["pid"];
        $name = $data["name"];
        $groupname = $data["groupname"];
        $itemamount = $data["firstpaymentamount"];
        if ($universalAnalytics) {
            $code .= "\nga('ecommerce:addItem', {\n    id: '" . $orderid . "',\n    sku: 'PID" . $itempid . "',\n    name: '" . $name . "',\n    category: '" . $groupname . "',\n    price: '" . $itemamount . "',\n    quantity: '1'\n});\n";
        } else {
            $code .= "\n_gaq.push(['_addItem',\n'" . $orderid . "',\n'PID" . $itempid . "',\n'" . $name . "',\n'" . $groupname . "',\n'" . $itemamount . "',\n'1'\n]);\n";
        }
    }
    $result = select_query("tblhostingaddons", "tblhostingaddons.id,tblhostingaddons.addonid,tbladdons.name,tblhostingaddons.setupfee,tblhostingaddons.recurring", array("orderid" => $orderid), "", "", "", "tbladdons ON tbladdons.id=tblhostingaddons.addonid");
    while ($data = mysql_fetch_array($result)) {
        $aid = $data["id"];
        $addonid = $data["addonid"];
        $name = $data["name"];
        $groupname = $data["groupname"];
        $itemamount = $data["setupfee"] + $data["recurring"];
        if ($universalAnalytics) {
            $code .= "\nga('ecommerce:addItem', {\n    id: '" . $orderid . "',\n    sku: 'AID" . $addonid . "',\n    name: '" . $name . "',\n    category: 'Addons',\n    price: '" . $itemamount . "',\n    quantity: '1'\n});\n";
        } else {
            $code .= "\n_gaq.push(['_addItem',\n'" . $orderid . "',\n'AID" . $addonid . "',\n'" . $name . "',\n'Addons',\n'" . $itemamount . "',\n'1'\n]);\n";
        }
    }
    $result = select_query("tbldomains", "tbldomains.id,tbldomains.type,tbldomains.domain,tbldomains.firstpaymentamount", array("orderid" => $orderid));
    while ($data = mysql_fetch_array($result)) {
        $did = $data["id"];
        $regtype = $data["type"];
        $domain = $data["domain"];
        $itemamount = $data["firstpaymentamount"];
        $domainparts = explode(".", $domain, 2);
        if ($universalAnalytics) {
            $code .= "\nga('ecommerce:addItem', {\n    id: '" . $orderid . "',\n    sku: 'TLD" . strtoupper($domainparts[1]) . "',\n    name: '" . $regtype . "',\n    category: 'Domain',\n    price: '" . $itemamount . "',\n    quantity: '1'\n});\n";
        } else {
            $code .= "\n_gaq.push(['_addItem',\n'" . $orderid . "',\n'TLD" . strtoupper($domainparts[1]) . "',\n'" . $regtype . "',\n'Domain',\n'" . $itemamount . "',\n'1'\n]);\n";
        }
    }
    if ($renewals) {
        $renewals = explode(",", $renewals);
        foreach ($renewals as $renewal) {
            $renewal = explode("=", $renewal);
            list($domainid, $registrationperiod) = $renewal;
            $result = select_query("tbldomains", "id,domain,recurringamount", array("id" => $domainid));
            $data = mysql_fetch_array($result);
            $did = $data["id"];
            $domain = $data["domain"];
            $itemamount = $data["recurringamount"];
            $domainparts = explode(".", $domain, 2);
            if ($universalAnalytics) {
                $code .= "\nga('ecommerce:addItem', {\n    id: '" . $orderid . "',\n    sku: 'TLD" . strtoupper($domainparts[1]) . "',\n    name: 'Renewal',\n    category: 'Domain',\n    price: '" . $itemamount . "',\n    quantity: '1'\n});\n";
            } else {
                $code .= "\n_gaq.push(['_addItem',\n'" . $orderid . "',\n'TLD" . strtoupper($domainparts[1]) . "',\n'Renewal',\n'Domain',\n'" . $itemamount . "',\n'1'\n]);\n";
            }
        }
    }
    if ($universalAnalytics) {
        $code .= "\nga('ecommerce:send');\n\n</script>\n<!-- End Google Analytics -->\n";
    } else {
        $code .= "\n_gaq.push(['_trackTrans']);\n\n(function() {\n    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;\n    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';\n    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);\n})();\n\n</script>";
    }
    return $code;
}
function google_analytics_hook_page_tracking($vars)
{
    global $smarty;
    $modulevars = array();
    $result = select_query("tbladdonmodules", "", array("module" => "google_analytics"));
    while ($data = mysql_fetch_array($result)) {
        $value = $data["value"];
        $value = explode("|", $value);
        $value = trim($value[0]);
        $modulevars[$data["setting"]] = $value;
    }
    if (!$modulevars["code"]) {
        return false;
    }
    if ($modulevars["analytics_version"] == "Universal Analytics") {
        if (!empty($modulevars["domain"])) {
            $domain = "{ cookieDomain: '" . $modulevars["domain"] . "' }";
        } else {
            $domain = "'auto'";
        }
        $jscode = "\n<!-- Google Analytics -->\n<script>\n(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){\n(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),\n    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)\n    })(window,document,'script','//www.google-analytics.com/analytics.js','ga');\n\nga('create', '" . $modulevars["code"] . "', " . $domain . ");\nga('send', 'pageview');\n\n</script>\n<!-- End Google Analytics -->\n";
    } else {
        $jscode = "<script type=\"text/javascript\">\n\nvar _gaq = _gaq || [];\n_gaq.push(['_setAccount', '" . $modulevars["code"] . "']);";
        if ($modulevars["domain"]) {
            $jscode .= "\n_gaq.push(['_setDomainName', '" . $modulevars["domain"] . "']);";
        }
        $jscode .= "\n_gaq.push(['_trackPageview']);\n\n(function() {\nvar ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;\nga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';\nvar s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);\n})();\n\n</script>\n";
    }
    return $jscode;
}

?>