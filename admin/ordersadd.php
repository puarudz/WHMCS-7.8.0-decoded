<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
define("SHOPPING_CART", true);
require "../init.php";
$aInt = new WHMCS\Admin("Add New Order", false);
$aInt->title = $aInt->lang("orders", "addnew");
$aInt->sidebar = "orders";
$aInt->icon = "orders";
$aInt->requiredFiles(array("orderfunctions", "domainfunctions", "configoptionsfunctions", "customfieldfunctions", "clientfunctions", "invoicefunctions", "processinvoices", "gatewayfunctions", "modulefunctions", "cartfunctions"));
$action = $whmcs->get_req_var("action");
$userid = $whmcs->get_req_var("userid");
$currency = getCurrency($userid);
if ($action == "getcontacts") {
    $contacts = array();
    $result = select_query("tblcontacts", "id,firstname,lastname,companyname,email", array("userid" => (int) $whmcs->get_req_var("userid")), "firstname", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $contacts[$data["id"]] = $data["firstname"] . " " . $data["lastname"];
    }
    $aInt->jsonResponse($contacts);
}
if ($action == "createpromo") {
    check_token("WHMCS.admin.default");
    if (!checkPermission("Create/Edit Promotions", true)) {
        throw new WHMCS\Exception\ProgramExit("You do not have permission to create promotional codes. If you feel this message to be an error, please contact the administrator.");
    }
    if (!$code) {
        throw new WHMCS\Exception\ProgramExit("Promotion Code is Required");
    }
    if ($pvalue <= 0) {
        throw new WHMCS\Exception\ProgramExit("Promotion Value must be greater than zero");
    }
    $result = select_query("tblpromotions", "COUNT(*)", array("code" => $code));
    $data = mysql_fetch_array($result);
    $duplicates = $data[0];
    if ($duplicates) {
        throw new WHMCS\Exception\ProgramExit("Promotion Code already exists. Please try another.");
    }
    $promoid = insert_query("tblpromotions", array("code" => $code, "type" => $type, "recurring" => $recurring, "value" => $pvalue, "maxuses" => "1", "recurfor" => $recurfor, "expirationdate" => "0000-00-00", "notes" => "Order Process One Off Custom Promo"));
    $promo_type = $type;
    $promo_value = $pvalue;
    $promo_recurring = $recurring;
    $promo_code = $code;
    if ($promo_type == "Percentage") {
        $promo_value .= "%";
    } else {
        $promo_value = formatCurrency($promo_value);
    }
    $promo_recurring = $promo_recurring ? "Recurring" : "One Time";
    echo "<option value=\"" . $promo_code . "\">" . $promo_code . " - " . $promo_value . " " . $promo_recurring . "</option>";
    throw new WHMCS\Exception\ProgramExit();
}
if ($action == "getconfigoptions") {
    check_token("WHMCS.admin.default");
    WHMCS\Session::release();
    if (!trim($pid)) {
        exit;
    }
    $options = "";
    $cycles = new WHMCS\Billing\Cycles();
    $cycle = App::getFromRequest("cycle");
    $cycle = $cycles->getNormalisedBillingCycle($cycle);
    $configoptions = getCartConfigOptions($pid, "", $cycle);
    if (count($configoptions)) {
        $options .= "<p><b>" . $aInt->lang("setup", "configoptions") . "</b></p>\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">";
        foreach ($configoptions as $configoption) {
            $options .= "<tr><td width=\"130\" class=\"fieldlabel\">" . $configoption["optionname"] . "</td><td class=\"fieldarea\">";
            if ($configoption["optiontype"] == "1") {
                $options .= "<select onchange=\"updatesummary()\" class=\"form-control select-inline\" name=\"configoption[" . $orderid . "][" . $configoption["id"] . "]\">";
                foreach ($configoption["options"] as $optiondata) {
                    $options .= "<option value=\"" . $optiondata["id"] . "\"";
                    if ($optiondata["id"] == $configoption["selectedvalue"]) {
                        $options .= " selected";
                    }
                    $options .= ">" . $optiondata["name"] . "</option>";
                }
                $options .= "</select>";
            } else {
                if ($configoption["optiontype"] == "2") {
                    foreach ($configoption["options"] as $optiondata) {
                        $options .= "<input type=\"radio\" onclick=\"updatesummary()\" name=\"configoption[" . $orderid . "][" . $configoption["id"] . "]\" value=\"" . $optiondata["id"] . "\"";
                        if ($optiondata["id"] == $configoption["selectedvalue"]) {
                            $options .= " checked=\"checked\"";
                        }
                        $options .= "> " . $optiondata["name"] . "<br />";
                    }
                } else {
                    if ($configoption["optiontype"] == "3") {
                        $options .= "<input type=\"checkbox\" onclick=\"updatesummary()\" name=\"configoption[" . $orderid . "][" . $configoption["id"] . "]\" value=\"1\"";
                        if ($configoption["selectedqty"]) {
                            $options .= " checked=\"checked\"";
                        }
                        $options .= "> " . $configoption["options"][0]["name"];
                    } else {
                        if ($configoption["optiontype"] == "4") {
                            $options .= "<input type=\"text\" onchange=\"updatesummary()\" name=\"configoption[" . $orderid . "][" . $configoption["id"] . "]\" value=\"" . $configoption["selectedqty"] . "\" size=\"5\"> x " . $configoption["options"][0]["name"];
                        }
                    }
                }
            }
            $options .= "</td></tr>";
        }
        $options .= "</table>";
    }
    $customfields = getCustomFields("product", $pid, "", true, "", $customfields);
    if (count($customfields)) {
        $options .= "<p><b>" . $aInt->lang("setup", "customfields") . "</b></p>\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">";
        foreach ($customfields as $customfield) {
            $inputfield = str_replace("name=\"customfield", "name=\"customfield[" . $orderid . "]", $customfield["input"]);
            $options .= "<tr><td width=\"130\" class=\"fieldlabel\">" . $customfield["name"] . "</td><td class=\"fieldarea\">" . $inputfield . "</td></tr>";
        }
        $options .= "</table>";
    }
    $addonshtml = "";
    $addonsarray = getAddons($pid);
    $orderItemId = App::getFromRequest("orderid");
    $marketConnect = new WHMCS\MarketConnect\MarketConnect();
    $addonsPromoOutput = $marketConnect->getAdminMarketplaceAddonPromo($addonsarray, $cycle, $orderItemId);
    $addonsarray = $marketConnect->removeMarketplaceAddons($addonsarray);
    if (count($addonsarray)) {
        foreach ($addonsarray as $addon) {
            $addonshtml .= "<label class=\"checkbox-inline\">" . str_replace("<input type=\"checkbox\" name=\"addons", "<input type=\"checkbox\" onclick=\"updatesummary()\" name=\"addons[" . $orderid . "]", $addon["checkbox"]) . " " . $addon["name"];
            if ($addon["description"]) {
                $addonshtml .= " - " . $addon["description"];
            }
            $addonshtml .= "</label><br />";
        }
    }
    if (count($addonsPromoOutput)) {
        foreach ($addonsPromoOutput as $addon) {
            if ($addon) {
                $addonshtml .= implode("<br>", $addon) . "<br>";
            }
        }
    }
    $aInt->jsonResponse(array("options" => $options, "addons" => $addonshtml));
}
if ($action == "getdomainaddlfields") {
    check_token("WHMCS.admin.default");
    $userInputDomain = trim($whmcs->get_req_var("domain"));
    $domainCounter = (int) $whmcs->get_req_var("domainnum");
    $domain = new WHMCS\Domain\Domain();
    $domain->domain = $userInputDomain;
    $additionalFieldsOutput = array();
    foreach ($domain->getAdditionalFields()->getFieldsForOutput($domainCounter) as $fieldLabel => $inputHTML) {
        $additionalFieldsOutput[] = "<tr class=\"domain-addt-fields\"><td width=\"130\" class=\"fieldlabel\">" . $fieldLabel . "</td><td class=\"fieldarea\">" . $inputHTML . "</td></tr>" . PHP_EOL;
    }
    $aInt->jsonResponse(array("invalidTld" => !$domain->isConfiguredTld(), "additionalFields" => implode($additionalFieldsOutput)));
}
$previousSessionUserId = NULL;
if ($whmcs->get_req_var("submitorder")) {
    check_token("WHMCS.admin.default");
    $userid = get_query_val("tblclients", "id", array("id" => $userid));
    $addons = App::getFromRequest("addons");
    $addons_radio = App::getFromRequest("addons_radio");
    if (!$userid && !$calconly) {
        infoBox("Invalid Client ID", "Please enter or select a valid client to add the order to");
    } else {
        if (WHMCS\Session::get("uid")) {
            $previousSessionUserId = WHMCS\Session::get("uid");
        }
        $_SESSION["uid"] = $userid;
        getUsersLang($userid);
        $_SESSION["cart"] = array();
        $_SESSION["cart"]["paymentmethod"] = $paymentmethod;
        foreach ($pid as $k => $prodid) {
            if ($prodid) {
                if ($addons) {
                    $addons[$k] = array_keys($addons[$k]);
                }
                if (empty($addons)) {
                    $addons = array();
                }
                if ($addons_radio) {
                    foreach ($addons_radio[$k] as $addon_value) {
                        if ($addon_value) {
                            if (empty($addons[$k])) {
                                $addons[$k] = array();
                            }
                            $addons[$k][] = $addon_value;
                        }
                    }
                }
                if (!$qty[$k]) {
                    $qty[$k] = 1;
                }
                $productarray = array("pid" => $prodid, "domain" => trim($domain[$k]), "billingcycle" => str_replace(array("-", " "), "", strtolower($billingcycle[$k])), "server" => "", "configoptions" => $configoption[$k], "customfields" => $customfield[$k], "addons" => $addons[$k]);
                if (strlen($_POST["priceoverride"][$k])) {
                    $productarray["priceoverride"] = $_POST["priceoverride"][$k];
                }
                for ($count = 1; $count <= $qty[$k]; $count++) {
                    $_SESSION["cart"]["products"][] = $productarray;
                }
            }
        }
        $validtlds = array();
        $result = select_query("tbldomainpricing", "extension", "");
        while ($data = mysql_fetch_array($result)) {
            $validtlds[] = $data[0];
        }
        $orderContainsInvalidTlds = false;
        $domains = new WHMCS\Domains();
        foreach ($regaction as $k => $regact) {
            if ($regact) {
                $domainparts = explode(".", $domains->clean($regdomain[$k]), 2);
                if (in_array("." . $domainparts[1], $validtlds)) {
                    $domainArray = array("type" => $regact, "domain" => trim($regdomain[$k]), "regperiod" => $regperiod[$k], "dnsmanagement" => $dnsmanagement[$k], "emailforwarding" => $emailforwarding[$k], "idprotection" => $idprotection[$k], "eppcode" => $eppcode[$k], "fields" => $domainfield[$k]);
                    if (strlen($_POST["domainpriceoverride"][$k])) {
                        $domainArray["domainpriceoverride"] = $_POST["domainpriceoverride"][$k];
                    }
                    if (strlen($_POST["domainrenewoverride"][$k])) {
                        $domainArray["domainrenewoverride"] = $_POST["domainrenewoverride"][$k];
                    }
                    $_SESSION["cart"]["domains"][] = $domainArray;
                } else {
                    if (!empty($regdomain[$k])) {
                        $orderContainsInvalidTlds = true;
                    }
                }
            }
        }
        if ($promocode) {
            $_SESSION["cart"]["promo"] = $promocode;
        }
        $_SESSION["cart"]["orderconfdisabled"] = $adminorderconf ? false : true;
        $_SESSION["cart"]["geninvoicedisabled"] = $admingenerateinvoice ? false : true;
        if (!$adminsendinvoice) {
            $CONFIG["NoInvoiceEmailOnOrder"] = true;
        }
        $contactid = $whmcs->get_req_var("contactid");
        if ($contactid) {
            $_SESSION["cart"]["contact"] = $contactid;
        }
        if ($calconly) {
            ob_start();
            $ordervals = calcCartTotals(false, false, $currency);
            echo "<div class=\"ordersummarytitle\">" . $aInt->lang("orders", "orderSummary") . "</div>";
            if ($orderContainsInvalidTlds) {
                echo "<div class=\"alert alert-info text-center\" style=\"margin:15px 0;\">" . AdminLang::trans("domains.orderContainsInvalidTlds") . "</div>";
            }
            echo "<div id=\"ordersummary\">\n<table>\n";
            if (is_array($ordervals["products"])) {
                foreach ($ordervals["products"] as $cartprod) {
                    echo "<tr class=\"item\"><td colspan=\"2\"><div class=\"itemtitle\">" . $cartprod["productinfo"]["groupname"] . " - " . $cartprod["productinfo"]["name"] . "</div>";
                    echo $aInt->lang("billingcycles", $cartprod["billingcycle"]);
                    if ($cartprod["domain"]) {
                        echo " - " . $cartprod["domain"];
                    }
                    echo "<div class=\"itempricing\">";
                    if ($cartprod["priceoverride"]) {
                        echo formatCurrency($cartprod["priceoverride"]) . "*";
                    } else {
                        echo $cartprod["pricingtext"];
                    }
                    echo "</div>";
                    if ($cartprod["configoptions"]) {
                        foreach ($cartprod["configoptions"] as $cartcoption) {
                            if (!empty($cartcoption["optionname"]) && empty($cartcoption["value"])) {
                                $cartcoption["value"] = $cartcoption["optionname"];
                            }
                            if ($cartcoption["type"] == "1" || $cartcoption["type"] == "2") {
                                echo "<br />&nbsp;&raquo;&nbsp;" . $cartcoption["name"] . ": " . $cartcoption["value"];
                            } else {
                                if ($cartcoption["type"] == "3") {
                                    echo "<br />&nbsp;&raquo;&nbsp;" . $cartcoption["name"] . ": ";
                                    if ($cartcoption["qty"]) {
                                        echo $aInt->lang("global", "yes");
                                    } else {
                                        echo $aInt->lang("global", "no");
                                    }
                                } else {
                                    if ($cartcoption["type"] == "4") {
                                        echo "<br />&nbsp;&raquo;&nbsp;" . $cartcoption["name"] . ": " . $cartcoption["qty"] . " x " . $cartcoption["option"];
                                    }
                                }
                            }
                        }
                    }
                    echo "</td></tr>";
                    if ($cartprod["addons"]) {
                        foreach ($cartprod["addons"] as $addondata) {
                            echo "<tr class=\"item\"><td colspan=\"2\"><div class=\"itemtitle\">" . $addondata["name"] . "</div><div class=\"itempricing\">" . $addondata["pricingtext"] . "</div></td></tr>";
                        }
                    }
                }
            }
            if (is_array($ordervals["domains"])) {
                foreach ($ordervals["domains"] as $cartdom) {
                    echo "<tr class=\"item\"><td colspan=\"2\"><div class=\"itemtitle\">" . $aInt->lang("fields", "domain") . " " . $aInt->lang("domains", $cartdom["type"]) . "</div>" . $cartdom["domain"] . " (" . $cartdom["regperiod"] . " " . $aInt->lang("domains", "years") . ")";
                    if ($cartdom["dnsmanagement"]) {
                        echo "<br />&nbsp;&raquo;&nbsp;" . $aInt->lang("domains", "dnsmanagement");
                    }
                    if ($cartdom["emailforwarding"]) {
                        echo "<br />&nbsp;&raquo;&nbsp;" . $aInt->lang("domains", "emailforwarding");
                    }
                    if ($cartdom["idprotection"]) {
                        echo "<br />&nbsp;&raquo;&nbsp;" . $aInt->lang("domains", "idprotection");
                    }
                    echo "<div class=\"itempricing\">";
                    if ($cartdom["priceoverride"]) {
                        echo formatCurrency($cartdom["priceoverride"]) . "*";
                    } else {
                        echo $cartdom["price"];
                    }
                    echo "</div>";
                }
            }
            $cartitems = 0;
            foreach (array("products", "addons", "domains", "renewals") as $k) {
                if (array_key_exists($k, $ordervals)) {
                    $cartitems += count($ordervals[$k]);
                }
            }
            if (!$cartitems) {
                echo "<tr class=\"item\"><td colspan=\"2\"><div class=\"itemtitle\" align=\"center\">" . $aInt->lang("orders", "noItemsSelected") . "</div></td></tr>";
            }
            echo "<tr class=\"subtotal\"><td>" . $aInt->lang("fields", "subtotal") . "</td><td class=\"alnright\">" . $ordervals["subtotal"] . "</td></tr>";
            if ($ordervals["promotype"]) {
                echo "<tr class=\"promo\"><td>" . $aInt->lang("orders", "promoDiscount") . "</td><td class=\"alnright\">" . $ordervals["discount"] . "</td></tr>";
            }
            if ($ordervals["taxrate"]) {
                echo "<tr class=\"tax\"><td>" . $ordervals["taxname"] . " @ " . $ordervals["taxrate"] . "%</td><td class=\"alnright\">" . $ordervals["taxtotal"] . "</td></tr>";
            }
            if ($ordervals["taxrate2"]) {
                echo "<tr class=\"tax\"><td>" . $ordervals["taxname2"] . " @ " . $ordervals["taxrate2"] . "%</td><td class=\"alnright\">" . $ordervals["taxtotal2"] . "</td></tr>";
            }
            echo "<tr class=\"total\"><td width=\"140\">" . $aInt->lang("fields", "total") . "</td><td class=\"alnright\">" . $ordervals["total"] . "</td></tr>";
            if ($ordervals["totalrecurringmonthly"] || $ordervals["totalrecurringquarterly"] || $ordervals["totalrecurringsemiannually"] || $ordervals["totalrecurringannually"] || $ordervals["totalrecurringbiennially"] || $ordervals["totalrecurringtriennially"]) {
                echo "<tr class=\"recurring\"><td>Recurring</td><td class=\"alnright\">";
                if ($ordervals["totalrecurringmonthly"]) {
                    echo "" . $ordervals["totalrecurringmonthly"] . " Monthly<br />";
                }
                if ($ordervals["totalrecurringquarterly"]) {
                    echo "" . $ordervals["totalrecurringquarterly"] . " Quarterly<br />";
                }
                if ($ordervals["totalrecurringsemiannually"]) {
                    echo "" . $ordervals["totalrecurringsemiannually"] . " Semi-Annually<br />";
                }
                if ($ordervals["totalrecurringannually"]) {
                    echo "" . $ordervals["totalrecurringannually"] . " Annually<br />";
                }
                if ($ordervals["totalrecurringbiennially"]) {
                    echo "" . $ordervals["totalrecurringbiennially"] . " Biennially<br />";
                }
                if ($ordervals["totalrecurringtriennially"]) {
                    echo "" . $ordervals["totalrecurringtriennially"] . " Triennially<br />";
                }
                echo "</td></tr>";
            }
            $client = WHMCS\User\Client::find($userid);
            $amountOfCredit = 0;
            $canUseCreditOnCheckout = false;
            $amountOfCredit = $client->credit;
            if (0 < $ordervals["total"]->toNumeric() && 0 < $amountOfCredit) {
                $creditBalance = new WHMCS\View\Formatter\Price($amountOfCredit, $currency);
                $checked = App::isInRequest("applycredit") ? (bool) App::getFromRequest("applycredit") : true;
                if ($ordervals["total"]->toNumeric() <= $creditBalance->toNumeric()) {
                    $applyCredit = AdminLang::trans("orders.applyCreditAmountNoFurtherPayment", array(":amount" => $ordervals["total"]));
                } else {
                    $applyCredit = AdminLang::trans("orders.applyCreditAmount", array(":amount" => $creditBalance));
                }
                echo "<tr class=\"apply-credit\"><td colspan=\"2\"><div class=\"apply-credit-container\">\n<p>" . AdminLang::trans("orders.availableCreditBalance", array(":amount" => $creditBalance)) . "</p>\n<label class=\"radio\">\n<input type=\"radio\" name=\"applycredit\" value=\"1\" " . ($checked ? "checked=\"checked\"" : "") . ">\n" . $applyCredit . "\n</label>\n<label class=\"radio\">\n<input id=\"skipCreditOnCheckout\" type=\"radio\" name=\"applycredit\" value=\"0\" " . (!$checked ? "checked=\"checked\"" : "") . ">\n" . AdminLang::trans("orders.applyCreditSkip", array(":amount" => $creditBalance)) . "\n</label>\n</div></td></tr>";
            }
            echo "</table>\n</div>";
            if ($previousSessionUserId) {
                WHMCS\Session::set("uid", $previousSessionUserId);
            }
            $content = ob_get_contents();
            ob_end_clean();
            $aInt->jsonResponse(array("body" => $content));
        }
        $cartitems = count($_SESSION["cart"]["products"]) + count($_SESSION["cart"]["addons"]) + count($_SESSION["cart"]["domains"]) + count($_SESSION["cart"]["renewals"]);
        if (!$cartitems) {
            redir("noselections=1");
        }
        calcCartTotals(true, false, $currency);
        unset($_SESSION["uid"]);
        if ($orderstatus == "Active") {
            update_query("tblorders", array("status" => "Active"), array("id" => $_SESSION["orderdetails"]["OrderID"]));
            if (is_array($_SESSION["orderdetails"]["Products"])) {
                foreach ($_SESSION["orderdetails"]["Products"] as $productid) {
                    update_query("tblhosting", array("domainstatus" => "Active"), array("id" => $productid));
                }
            }
            if (is_array($_SESSION["orderdetails"]["Domains"])) {
                foreach ($_SESSION["orderdetails"]["Domains"] as $domainid) {
                    update_query("tbldomains", array("status" => "Active"), array("id" => $domainid));
                }
            }
        }
        getUsersLang(0);
        if ($previousSessionUserId) {
            WHMCS\Session::set("uid", $previousSessionUserId);
        }
        redir("action=view&id=" . $_SESSION["orderdetails"]["OrderID"], "orders.php");
    }
}
WHMCS\Session::release();
$regperiods = $regperiodss = "";
for ($regperiod = 1; $regperiod <= 10; $regperiod++) {
    $regperiods .= "<option value=\"" . $regperiod . "\">" . $regperiod . " " . $aInt->lang("domains", "year" . $regperiodss) . "</option>";
    $regperiodss = "s";
}
$jquerycode = "\n\$(function(){\n    var prodtemplate = \$(\"#products .product:first\").clone();\n    var productsCount = 0;\n    window.addProduct = function(){\n        productsCount++;\n        var order = prodtemplate.clone().find(\"*\").each(function(){\n            var newId = this.id.substring(0, this.id.length-1) + productsCount;\n\n            \$(this).prev().attr(\"for\", newId); // update label for\n            this.id = newId; // update id\n\n        }).end()\n        .attr(\"id\", \"ord\" + productsCount)\n        .appendTo(\"#products\");\n        return false;\n    }\n    \$(\".addproduct\").click(addProduct);\n\n    \$(\".adddomain\").click(function() {\n        var domainConfigCount = \$(\".tbl-domain-config\").length;\n        \$(\"#domains .tbl-domain-config:first\")\n            .clone()\n            .attr(\"domain-counter\", domainConfigCount)\n            .find(\".domain-reg-action\")\n            .attr(\"name\", \"regaction[\" + domainConfigCount + \"]\")\n            .end()\n            .find(\".invalid-tld\")\n            .hide()\n            .end()\n            .find(\".domain-reg-dnsmanagement\")\n            .attr(\"name\", \"dnsmanagement[\" + domainConfigCount + \"]\")\n            .end()\n            .find(\".domain-reg-emailforwarding\")\n            .attr(\"name\", \"emailforwarding[\" + domainConfigCount + \"]\")\n            .end()\n            .find(\".domain-reg-idprotection\")\n            .attr(\"name\", \"idprotection[\" + domainConfigCount + \"]\")\n            .end()\n            .find(\".domain-reg-priceoverride\")\n            .attr(\"name\", \"domainpriceoverride[\" + domainConfigCount + \"]\")\n            .end()\n            .find(\".domain-reg-renewoverride\")\n            .attr(\"name\", \"domainrenewoverride[\" + domainConfigCount + \"]\")\n            .end()\n            .find(\".domain-addt-fields\")\n            .remove()\n            .end()\n            .find(\".input-reg-domain\")\n            .val(\"\")\n            .end()\n            .find(\"input:checkbox\").removeAttr(\"checked\").end()\n            .find(\"input:radio\").prop(\"checked\", false).end()\n            .find(\"input:radio:first\").click().end()\n            .appendTo(\"#domains\")\n            .find(\"*\")\n            .each(function() {\n                var id = this.id || \"\";\n                if (id) {\n                    this.id = id.substring(0, id.length - 1) + (domainConfigCount);\n                }\n            });\n        return false;\n    });\n\n    \$(\".input-domain\").keyup(function() {\n      \$(\".input-reg-domain:first\").val(\$(\".input-domain\").val());\n    });\n\n});\n\n\$(\"#selectUserid\").change(function() {\n    \$(\"#linkAddContact\").attr(\"href\", \"clientscontacts.php?userid=\" + \$(this).val() + \"&contactid=addnew\");\n    loadDomainContactOptions();\n});\n\n";
$jscode = "\n\nvar summaryUpdateTimeoutId = 0;\nvar domainUpdateTimeoutId = 0;\n\nfunction loadDomainContactOptions() {\n    var hasDomainReg = false;\n    \$(\".domain-reg-action\").filter(\":checked\").each(function() {\n        if (this.value == \"register\" || this.value == \"transfer\") {\n            hasDomainReg = true;\n        }\n    });\n    if (!hasDomainReg) {\n        \$(\"#domainContactContainer\").hide();\n        return false;\n    }\n    \$.getJSON(\"ordersadd.php\", \"action=getcontacts&userid=\" + \$(\"#selectUserid\").val(), function(data){\n        var numberOfElements = data.length;\n        if (numberOfElements === 0) {\n            \$(\"#domainContactContainer\").hide();\n        } else {\n            \$(\"#inputContactID\").empty();\n            \$(\"#inputContactID\").append(\"<option value=\\\"0\\\">" . $aInt->lang("domains", "domaincontactuseprimary", 1) . "</option>\");\n            \$.each(data, function(key, value) {\n               \$(\"#inputContactID\").append(\"<option value=\\\"\" + key + \"\\\">\" + value + \"</option>\");\n            });\n            \$(\"#domainContactContainer\").show();\n        }\n    });\n}\nfunction loadproductoptions(piddd) {\n    var ord = piddd.id.substring(3);\n    var pid = piddd.value;\n    var billingcycle = \$(\"#billingcycle\" + ord).val();\n    if (pid==0) {\n        \$(\"#productconfigoptions\"+ord).html(\"\");\n        \$(\"#addonsrow\"+ord).hide();\n        updatesummary();\n    } else {\n    \$(\"#productconfigoptions\"+ord).html(\"<p align=\\\"center\\\">" . $aInt->lang("global", "loading") . "<br>" . addslashes(trim(DI::make("asset")->imgTag("loading.gif"))) . "</p>\");\n    WHMCS.http.jqClient.post(\"ordersadd.php\", { action: \"getconfigoptions\", pid: pid, cycle: billingcycle, orderid: ord, token: \"" . generate_token("plain") . "\" },\n    function(data){\n        if (data.addons) {\n            \$(\"#addonsrow\"+ord).show();\n            \$(\"#addonscont\"+ord).html(data.addons);\n        } else {\n            \$(\"#addonsrow\"+ord).hide();\n        }\n        \$(\"#productconfigoptions\"+ord).html(data.options);\n        updatesummary();\n    },\"json\");\n    }\n}\nfunction loaddomainoptions(domainRef) {\n    var regtype = \$(domainRef).filter(\":checked\").val();\n    var tblContainer = \$(domainRef).closest(\".tbl-domain-config\");\n    var fillDomain = false;\n    var domainField = \$(tblContainer).find(\".input-reg-domain\");\n    if (regtype == \"register\") {\n        \$(\"tr\", tblContainer).not(\".domain-eppcode\").css(\"display\", \"\");\n        \$(\"tr.domain-eppcode\", tblContainer).css(\"display\", \"none\");\n        fillDomain = true;\n    } else if (regtype == \"transfer\") {\n        \$(\"tr\", tblContainer).css(\"display\", \"\");\n        fillDomain = true;\n    } else {\n        \$(\"tr\", tblContainer).not(\"tr:first\").css(\"display\", \"none\");\n    }\n\n    if (fillDomain) {\n        if (\$(domainField).val() == \"\") {\n            var productDomain = \$(\"[name=\\\"domain[]\\\"]\").val();\n\n            if (productDomain != \"\") {\n                var numExistingEntries = \$(\".input-reg-domain\")\n                    .filter(function() {\n                        return \$(this).val() == productDomain;\n                    }).length;\n\n                if (numExistingEntries == 0) {\n                    \$(domainField).val(productDomain);\n                }\n            }\n        }\n    }\n\n    loaddomfields(domainRef);\n    loadDomainContactOptions();\n}\nfunction updatesummary() {\n    if (summaryUpdateTimeoutId) {\n        clearTimeout(summaryUpdateTimeoutId);\n        summaryUpdateTimeoutId = 0;\n    }\n\n    summaryUpdateTimeoutId = setTimeout(function() {\n        var applyCredit = \$(\"input[name='applycredit']:checked\").val();\n        if (typeof applyCredit === \"undefined\") {\n            applyCredit = 1;\n        }\n        WHMCS.http.jqClient.post(\"ordersadd.php\", \"submitorder=1&calconly=1&applycredit=\"+applyCredit+\"&\"+\$(\"#orderfrm\").serialize(),\n        function(data){\n            \$(\"#ordersumm\").html(data.body);\n        });\n    }, 300);\n}\nfunction loaddomfields(domainRef) {\n    var tblContainer = \$(domainRef).closest(\".tbl-domain-config\");\n    var domainName = \$(\".input-reg-domain\", tblContainer).val();\n    var domainCounter = \$(tblContainer).attr(\"domain-counter\");\n    if (domainName.length >= 5 && domainContainsAPeriod(domainName)) {\n        WHMCS.http.jqClient.post(\"ordersadd.php\", { action: \"getdomainaddlfields\", domain: domainName, domainnum: domainCounter, token: \"" . generate_token("plain") . "\" },\n        function(data) {\n            \$(\".domain-addt-fields\", tblContainer).remove();\n            \$(tblContainer).append(data.additionalFields);\n            if (data.invalidTld) {\n                \$(\".invalid-tld\", tblContainer).hide().removeClass(\"hidden\").fadeIn();\n            } else {\n                \$(\".invalid-tld\", tblContainer).fadeOut();\n            }\n        }, \"json\");\n    }\n}\nfunction handleDomainRegInput(currentDomain) {\n    var inputDomain = \$(currentDomain).val();\n\n    if (domainUpdateTimeoutId) {\n        clearTimeout(domainUpdateTimeoutId);\n        domainUpdateTimeoutId = 0;\n    }\n\n    if (domainContainsAPeriod(inputDomain)) {\n        domainUpdateTimeoutId = setTimeout(function() {\n            loaddomfields(currentDomain);\n        }, 300);\n\n        updatesummary();\n    }\n\n}\nfunction handleProductDomainInput(currentDomain) {\n    var inputDomain = \$(currentDomain).val();\n\n    var domainEntries = \$(\".input-reg-domain:visible\");\n\n    if (\$(domainEntries).length == 1) {\n        if (!\$(domainEntries).prop(\"data-manual-input\") || (\$(domainEntries).val().trim() == \"\")) {\n            \$(domainEntries).val(inputDomain);\n        }\n\n        handleDomainRegInput(domainEntries);\n    }\n\n    if (domainContainsAPeriod(inputDomain)) {\n        updatesummary();\n    }\n}\nfunction domainContainsAPeriod(domain) {\n    if (domain.indexOf(\".\") > -1 ) {\n        return true;\n    } else {\n        return false;\n    }\n}\n";
ob_start();
if (!checkActiveGateway()) {
    $aInt->gracefulExit($aInt->lang("gateways", "nonesetup"));
}
if ($userid && !$paymentmethod) {
    $paymentmethod = getClientsPaymentMethod($userid);
}
if ($whmcs->get_req_var("noselections")) {
    infoBox($aInt->lang("global", "validationerror"), $aInt->lang("orders", "noselections"));
}
echo $infobox;
echo "\n<form method=\"post\" action=\"";
echo $_SERVER["PHP_SELF"];
echo "\" id=\"orderfrm\">\n<input type=\"hidden\" name=\"submitorder\" value=\"true\" />\n\n<div class=\"row\">\n    <div class=\"col-md-8\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"130\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "client");
echo "</td><td class=\"fieldarea\">";
echo $aInt->clientsDropDown($userid);
echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "paymentmethod");
echo "</td><td class=\"fieldarea\">";
echo paymentMethodsSelection();
echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "promocode");
echo "</td><td class=\"fieldarea\"><select name=\"promocode\" id=\"promodd\" class=\"form-control select-inline\" onchange=\"updatesummary()\"><option value=\"\">";
echo $aInt->lang("global", "none");
echo "</option><optgroup label=\"Active Promotions\">";
$result = select_query("tblpromotions", "", "(maxuses<=0 OR uses<maxuses) AND (expirationdate='0000-00-00' OR expirationdate>='" . date("Ymd") . "')", "code", "ASC");
while ($data = mysql_fetch_array($result)) {
    $promo_id = $data["id"];
    $promo_code = $data["code"];
    $promo_type = $data["type"];
    $promo_recurring = $data["recurring"];
    $promo_value = $data["value"];
    if ($promo_type == "Percentage") {
        $promo_value .= "%";
    } else {
        $promo_value = formatCurrency($promo_value);
    }
    if ($promo_type == "Free Setup") {
        $promo_value = $aInt->lang("promos", "freesetup");
    }
    $promo_recurring = $promo_recurring ? $aInt->lang("status", "recurring") : $aInt->lang("status", "onetime");
    if ($promo_type == "Price Override") {
        $promo_recurring = $aInt->lang("promos", "priceoverride");
    }
    if ($promo_type == "Free Setup") {
        $promo_recurring = "";
    }
    echo "<option value=\"" . $promo_code . "\">" . $promo_code . " - " . $promo_value . " " . $promo_recurring . "</option>";
}
echo "</optgroup><optgroup label=\"Expired Promotions\">";
$result = select_query("tblpromotions", "", "(maxuses>0 AND uses>=maxuses) OR (expirationdate!='0000-00-00' AND expirationdate<'" . date("Ymd") . "')", "code", "ASC");
while ($data = mysql_fetch_array($result)) {
    $promo_id = $data["id"];
    $promo_code = $data["code"];
    $promo_type = $data["type"];
    $promo_recurring = $data["recurring"];
    $promo_value = $data["value"];
    if ($promo_type == "Percentage") {
        $promo_value .= "%";
    } else {
        $promo_value = formatCurrency($promo_value);
    }
    if ($promo_type == "Free Setup") {
        $promo_value = $aInt->lang("promos", "freesetup");
    }
    $promo_recurring = $promo_recurring ? $aInt->lang("status", "recurring") : $aInt->lang("status", "onetime");
    if ($promo_type == "Price Override") {
        $promo_recurring = $aInt->lang("promos", "priceoverride");
    }
    if ($promo_type == "Free Setup") {
        $promo_recurring = "";
    }
    echo "<option value=\"" . $promo_code . "\">" . $promo_code . " - " . $promo_value . " " . $promo_recurring . "</option>";
}
echo "</optgroup></select>\n        ";
if (checkPermission("Use Any Promotion Code on Order", true) && checkPermission("Create/Edit Promotions", true)) {
    $disabled = "data-toggle='modal' data-target='#modalCreatePromo' class='btn btn-default btn-sm'";
} else {
    $disabled = "data-toggle='tooltip' data-placement='auto right' class='btn btn-default btn-sm disabled' title='" . $aInt->lang("orders", "createPromoNeedPerms") . "'";
}
echo "        ";
echo "<a href='#' type='button' id='createPromoCode' " . $disabled . "><i class='fas fa-plus fa-fw'></i> " . $aInt->lang("orders", "createpromo") . "</a>";
echo "    </td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("orders", "status");
echo "</td><td class=\"fieldarea\"><select name=\"orderstatus\" class=\"form-control select-inline\">\n<option value=\"Pending\">";
echo $aInt->lang("status", "pending");
echo "</option>\n<option value=\"Active\">";
echo $aInt->lang("status", "active");
echo "</option>\n</select></td></tr>\n<tr><td width=\"130\" class=\"fieldlabel\"></td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"adminorderconf\" checked /> ";
echo $aInt->lang("orders", "orderconfirmation");
echo "</label> <label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"admingenerateinvoice\" checked /> ";
echo $aInt->lang("orders", "geninvoice");
echo "</label> <label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"adminsendinvoice\" checked /> ";
echo $aInt->lang("global", "sendemail");
echo "</label></td></tr>\n</table>\n\n<div id=\"products\">\n<div id=\"ord0\" class=\"product\">\n\n<p><b>";
echo $aInt->lang("fields", "product");
echo "</b></p>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"130\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "product");
echo "</td><td class=\"fieldarea\"><select name=\"pid[]\" id=\"pid0\" class=\"form-control select-inline\" onchange=\"loadproductoptions(this)\">";
echo $aInt->productDropDown(0, true);
echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "domain");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"domain[]\" class=\"form-control input-300\" onkeyup=\"handleProductDomainInput(this)\" class=\"input-domain\" /> <span id=\"whoisresult0\"></span></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "billingcycle");
echo "</td><td class=\"fieldarea\">";
if (!$billingcycle) {
    $billingcycle = "Monthly";
}
echo $aInt->cyclesDropDown($billingcycle, "", "", "billingcycle[]", "updatesummary();loadproductoptions(jQuery('#pid' + this.id.substring(12))[0]);return false;", "billingcycle0");
echo "</td></tr>\n<tr id=\"addonsrow0\" style=\"display:none;\"><td class=\"fieldlabel\">";
echo $aInt->lang("addons", "title");
echo "</td><td class=\"fieldarea\" id=\"addonscont0\"></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "quantity");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"qty[]\" value=\"1\" class=\"form-control input-50\" onkeyup=\"updatesummary()\" /></td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "priceoverride");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"priceoverride[]\" class=\"form-control input-100 input-inline\" onkeyup=\"updatesummary()\" /> ";
echo $aInt->lang("orders", "priceoverridedesc");
echo "</td></tr>\n</table>\n\n<div id=\"productconfigoptions0\"></div>\n\n</div>\n</div>\n\n<p style=\"padding:10px 0 5px 20px;\"><a href=\"#\" class=\"btn btn-default btn-sm addproduct\"><img src=\"images/icons/add.png\" border=\"0\" align=\"absmiddle\" /> ";
echo $aInt->lang("orders", "anotherproduct");
echo "</a></p>\n\n<p><b>";
echo $aInt->lang("domains", "domainreg");
echo "</b></p>\n\n<div id=\"domains\">\n\n<table class=\"form tbl-domain-config\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n    <tr>\n        <td width=\"130\" class=\"fieldlabel\">";
echo $aInt->lang("domains", "regtype");
echo "</td>\n        <td class=\"fieldarea\">\n            <label class=\"radio-inline\"><input type=\"radio\" name=\"regaction[0]\" value=\"\" class=\"domain-reg-action\" id=\"inputDomainRegActionNone0\" onclick=\"loaddomainoptions(this);updatesummary()\" checked /> ";
echo $aInt->lang("global", "none");
echo "</label>\n            <label class=\"radio-inline\"><input type=\"radio\" name=\"regaction[0]\" value=\"register\" class=\"domain-reg-action\" id=\"inputDomainRegActionRegister0\" onclick=\"loaddomainoptions(this);updatesummary()\" /> ";
echo $aInt->lang("domains", "register");
echo "</label>\n            <label class=\"radio-inline\"><input type=\"radio\" name=\"regaction[0]\" value=\"transfer\" class=\"domain-reg-action\" id=\"inputDomainRegActionTransfer0\" onclick=\"loaddomainoptions(this);updatesummary()\" /> ";
echo $aInt->lang("domains", "transfer");
echo "</label>\n        </td>\n    </tr>\n    <tr style=\"display:none;\">\n        <td class=\"fieldlabel\">";
echo $aInt->lang("fields", "domain");
echo "</td>\n        <td class=\"fieldarea\"><input type=\"text\" name=\"regdomain[]\" id=\"inputDomainRegDomain0\" class=\"form-control input-300 input-reg-domain\" data-manual-input=\"0\" onkeyup=\"\$(this).prop('data-manual-input', 1); handleDomainRegInput(this);\" /> <span id=\"spanInvalidTld0\" class=\"invalid-tld text-danger hidden\">";
echo AdminLang::trans("domains.tldNotConfiguredForSale");
echo " ";
echo AdminLang::trans("global.pleaseCheckInput");
echo "</span></td>\n    </tr>\n    <tr style=\"display:none;\">\n        <td class=\"fieldlabel\">";
echo $aInt->lang("domains", "regperiod");
echo "</td>\n        <td class=\"fieldarea\">\n            <select name=\"regperiod[]\" id=\"inputDomainRegPeriod0\" class=\"form-control select-inline\" onchange=\"updatesummary()\">\n                ";
echo $regperiods;
echo "            </select>\n        </td>\n    </tr>\n    <tr class=\"domain-eppcode\" style=\"display:none;\">\n        <td class=\"fieldlabel\">";
echo $aInt->lang("domains", "eppcode");
echo "</td>\n        <td class=\"fieldarea\"><input type=\"text\" name=\"eppcode[]\" class=\"form-control input-150\" id=\"inputDomainRegEppCode0\" /></td>\n    </tr>\n    <tr style=\"display:none;\">\n        <td class=\"fieldlabel\">";
echo $aInt->lang("domains", "addons");
echo "</td>\n        <td class=\"fieldarea\">\n            <label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"dnsmanagement[0]\" class=\"domain-reg-dnsmanagement\" id=\"inputDomainRegDnsManagement0\" onclick=\"updatesummary()\" /> ";
echo $aInt->lang("domains", "dnsmanagement");
echo "</label>\n            <label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"emailforwarding[0]\" class=\"domain-reg-emailforwarding\" id=\"inputDomainRegEmailForwarding0\" onclick=\"updatesummary()\" /> ";
echo $aInt->lang("domains", "emailforwarding");
echo "</label>\n            <label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"idprotection[0]\" class=\"domain-reg-idprotection\" id=\"inputDomainRegIdProtection0\" onclick=\"updatesummary()\" /> ";
echo $aInt->lang("domains", "idprotection");
echo "</label>\n        </td>\n    </tr>\n    <tr style=\"display:none;\">\n        <td class=\"fieldlabel\">";
echo $aInt->lang("domains", "priceOverride");
echo "</td>\n        <td class=\"fieldarea\"><input type=\"text\" name=\"domainpriceoverride[0]\" id=\"inputDomainRegPriceOverride0\" class=\"form-control input-100 input-inline domain-reg-priceoverride\" data-manual-input=\"0\" oninput=\"updatesummary()\" /> ";
echo $aInt->lang("domains", "priceOverrideWarning");
echo "</td>\n    </tr>\n    <tr style=\"display:none;\">\n        <td class=\"fieldlabel\">";
echo $aInt->lang("domains", "renewOverride");
echo "</td>\n        <td class=\"fieldarea\"><input type=\"text\" name=\"domainrenewoverride[0]\" id=\"inputDomainRenewPriceOverride0\" class=\"form-control input-100 input-inline domain-reg-renewoverride\" data-manual-input=\"0\" oninput=\"updatesummary()\" /> ";
echo $aInt->lang("domains", "priceOverrideWarning");
echo "</td>\n    </tr>\n</table>\n\n</div>\n\n<p style=\"padding:10px 0 5px 20px;\"><a href=\"#\" class=\"btn btn-default btn-sm adddomain\"><img src=\"images/icons/add.png\" border=\"0\" align=\"absmiddle\" /> ";
echo $aInt->lang("orders", "anotherdomain");
echo "</a></p>\n\n<div id=\"domainContactContainer\" style=\"display:none;\">\n\n<p><b>";
echo $aInt->lang("domains", "domainregcontact");
echo "</b></p>\n\n<p>";
echo sprintf($aInt->lang("domains", "domainregcontactorderinfo"), "<a href=\"#\" id=\"linkAddContact\">", "</a>");
echo "</p>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"130\" class=\"fieldlabel\">";
echo $aInt->lang("domains", "domaincontactchoose");
echo "</td><td class=\"fieldarea\"><select name=\"contactid\" id=\"inputContactID\"></select></td></tr>\n</table>\n\n</div>\n\n</div>\n    <div class=\"col-md-4\">\n\n<div id=\"ordersumm\"></div>\n\n<div class=\"ordersummarytitle\">\n    <input type=\"submit\" value=\"";
echo $aInt->lang("orders", "submit");
echo " &raquo;\" id=\"btnSubmit\" class=\"btn btn-primary\" style=\"font-size:20px;padding:12px 30px;\" />\n</div>\n\n\n    </div>\n</div>\n</form>\n\n<script> updatesummary(); </script>\n\n";
echo $aInt->modal("CreatePromo", $aInt->lang("orders", "createpromo"), "<form id=\"createpromofrm\">\n" . generate_token("form") . "\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\" width=\"140\">" . $aInt->lang("fields", "promocode") . "</td><td class=\"fieldarea\"><input type=\"text\" name=\"code\" id=\"promocode\" class=\"form-control input-200\" /></td></tr>\n<tr><td class=\"fieldlabel\">" . $aInt->lang("fields", "type") . "</td><td class=\"fieldarea\"><select name=\"type\" class=\"form-control select-inline\">\n<option value=\"Percentage\">" . $aInt->lang("promos", "percentage") . "</option>\n<option value=\"Fixed Amount\">" . $aInt->lang("promos", "fixedamount") . "</option>\n<option value=\"Price Override\">" . $aInt->lang("promos", "priceoverride") . "</option>\n<option value=\"Free Setup\">" . $aInt->lang("promos", "freesetup") . "</option>\n</select></td></tr>\n<tr><td class=\"fieldlabel\">" . $aInt->lang("promos", "value") . "</td><td class=\"fieldarea\"><input type=\"text\" name=\"pvalue\"  class=\"form-control input-100\" /></td></tr>\n<tr><td class=\"fieldlabel\">" . $aInt->lang("promos", "recurring") . "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"recurring\" id=\"recurring\" value=\"1\" /> " . $aInt->lang("promos", "recurenable") . "</label> <input type=\"text\" name=\"recurfor\" value=\"0\" class=\"form-control input-50 input-inline\" /> " . $aInt->lang("promos", "recurenable2") . "</td></tr>\n</table>\n<p>* " . $aInt->lang("orders", "createpromoinfo") . "</p>\n</form>", array(array("title" => $aInt->lang("global", "cancel")), array("title" => $aInt->lang("global", "ok"), "onclick" => "savePromo()", "class" => "btn-primary")));
$jscode .= "function savePromo() {\n    WHMCS.http.jqClient.post(\"ordersadd.php\", \"action=createpromo&\"+jQuery(\"#createpromofrm\").serialize(),\n    function(data){\n        if (data.substr(0,1)==\"<\") {\n            \$(\"#promodd\").append(data);\n            \$(\"#promodd\").val(\$(\"#promocode\").val());\n            \$(\"#modalCreatePromo\").modal(\"hide\");\n        } else {\n            alert(data);\n        }\n    });\n}";
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();

?>