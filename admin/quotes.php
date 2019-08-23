<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Manage Quotes");
$aInt->title = AdminLang::trans("quotes.title");
$aInt->sidebar = "billing";
$aInt->icon = "quotes";
$aInt->requiredFiles(array("clientfunctions", "customfieldfunctions", "invoicefunctions", "quotefunctions", "configoptionsfunctions", "orderfunctions"));
if ($action == "getdesc") {
    check_token("WHMCS.admin.default");
    $result = select_query("tblproducts", "", array("id" => $id));
    $data = mysql_fetch_array($result);
    $name = $data["name"];
    $description = $data["description"];
    echo $name . "\n" . $description;
    exit;
}
if ($action == "getprice") {
    check_token("WHMCS.admin.default");
    $result = select_query("tblpricing", "", array("type" => "product", "currency" => $currency, "relid" => $id));
    $data = mysql_fetch_array($result);
    if (0 < $data["monthly"]) {
        echo $data["monthly"];
    } else {
        if (0 < $data["quarterly"]) {
            echo $data["quarterly"];
        } else {
            if (0 < $data["semiannually"]) {
                echo $data["semiannually"];
            } else {
                if (0 < $data["annually"]) {
                    echo $data["annually"];
                } else {
                    if (0 < $data["biennially"]) {
                        echo $data["biennially"];
                    } else {
                        if (0 < $data["triennially"]) {
                            echo $data["triennially"];
                        } else {
                            echo "0.00";
                        }
                    }
                }
            }
        }
    }
    exit;
}
if ($action == "getproddetails") {
    check_token("WHMCS.admin.default");
    $currency = getCurrency("", $currency);
    $pricing = getPricingInfo($pid);
    if (!$billingcycle) {
        $billingcycle = $pricing["minprice"]["cycle"];
    }
    echo "<input type=\"hidden\" name=\"billingcycle\" value=\"" . $billingcycle . "\" />";
    if ($pricing["type"] == "recurring") {
    }
    $configoptions = getCartConfigOptions($pid, "", $billingcycle);
    if (count($configoptions)) {
        echo "<p><b>Configurable Options</b></p>\n<table>";
        foreach ($configoptions as $configoption) {
            $optionid = $configoption["id"];
            $optionhidden = $configoption["hidden"];
            $optionname = $optionhidden ? $configoption["optionname"] . " <i>(" . AdminLang::trans("global.hidden") . ")</i>" : $configoption["optionname"];
            $optiontype = $configoption["optiontype"];
            $selectedvalue = $configoption["selectedvalue"];
            $selectedqty = $configoption["selectedqty"];
            echo "<tr><td class=\"fieldlabel\">" . $optionname . "</td><td class=\"fieldarea\">";
            if ($optiontype == "1") {
                echo "<select name=\"configoption[" . $optionid . "]\">";
                foreach ($configoption["options"] as $option) {
                    echo "<option value=\"" . $option["id"] . "\"";
                    if ($option["hidden"]) {
                        echo " style='color:#ccc;'";
                    }
                    if ($selectedvalue == $option["id"]) {
                        echo " selected";
                    }
                    echo ">" . $option["name"] . "</option>";
                }
                echo "</select>";
            } else {
                if ($optiontype == "2") {
                    foreach ($configoption["options"] as $option) {
                        echo "<input type=\"radio\" name=\"configoption[" . $optionid . "]\" value=\"" . $option["id"] . "\"";
                        if ($selectedvalue == $option["id"]) {
                            echo " checked";
                        }
                        if ($option["hidden"]) {
                            echo "> <span style='color:#ccc;'>" . $option["name"] . "</span><br />";
                        } else {
                            echo "> " . $option["name"] . "<br />";
                        }
                    }
                } else {
                    if ($optiontype == "3") {
                        echo "<input type=\"checkbox\" name=\"configoption[" . $optionid . "]\" value=\"1\"";
                        if ($selectedqty) {
                            echo " checked";
                        }
                        echo "> " . $configoption["options"][0]["name"];
                    } else {
                        if ($optiontype == "4") {
                            echo "<input type=\"text\" name=\"configoption[" . $optionid . "]\" value=\"" . $selectedqty . "\" size=\"5\"> x " . $configoption["options"][0]["name"];
                        }
                    }
                }
            }
        }
        echo "</table>";
    }
    exit;
} else {
    if ($action == "loadprod") {
        $result = select_query("tblquotes", "userid,currency", array("id" => $id));
        $data = mysql_fetch_array($result);
        $userid = $data["userid"];
        $currencyid = $data["currency"];
        $currency = getCurrency($userid, $currencyid);
        $aInt->title = "Load Product";
        $aInt->content = "<script>\n\$(document).ready(function(){\n\$(\"#addproduct\").change(function () {\n    if (this.options[this.selectedIndex].value) {\n        \$(\"#add_desc\").val(this.options[this.selectedIndex].text);\n        WHMCS.http.jqClient.post(\"quotes.php\", { action: \"getproddetails\", currency: " . $currency["id"] . ", pid: this.options[this.selectedIndex].value, token: \"" . generate_token("plain") . "\" },\n        function(data){\n            \$(\"#configops\").html(data);\n        });\n    }\n});\n});\nfunction selectproduct() {\n    window.opener.location.href = \"quotes.php?action=addproduct&id=" . $id . "&\"+\$(\"#addfrm\").serialize();\n    window.close();\n}\n</script>\n<form id=\"addfrm\" onsubmit=\"selectproduct();return false\">\n" . generate_token("form") . "\n<p><b>Product/Service</b></p><p><select name=\"pid\" id=\"addproduct\" style=\"width:95%;\"><option>Choose a product...</option>";
        $products = new WHMCS\Product\Products();
        $productsList = $products->getProducts();
        foreach ($productsList as $data) {
            $productid = $data["id"];
            $groupname = $data["groupname"];
            $productname = $data["name"];
            $aInt->content .= "<option value=\"" . $productid . "\">" . $groupname . " - " . $productname . "</option>";
        }
        $aInt->content .= "</select></p>\n<div id=\"configops\"></div>\n<p align=\"center\"><input type=\"submit\" value=\"Select\" /></p>\n</form>";
        $aInt->displayPopUp();
        exit;
    } else {
        if ($action == "addproduct") {
            check_token("WHMCS.admin.default");
            $result = select_query("tblquotes", "userid,currency", array("id" => $id));
            $data = mysql_fetch_array($result);
            $userid = $data["userid"];
            $currencyid = $data["currency"];
            $currency = getCurrency($userid, $currencyid);
            $result = select_query("tblproducts", "tblproducts.tax,tblproductgroups.id AS group_id", array("tblproducts.id" => $pid), "", "", "", "tblproductgroups ON tblproductgroups.id=tblproducts.gid");
            $data = mysql_fetch_array($result);
            $groupId = $data["group_id"];
            $clientLanguage = NULL;
            if ($userid) {
                $clientLanguage = WHMCS\User\Client::find($userid, array("language"))->language ?: NULL;
            }
            $groupname = WHMCS\Product\Group::getGroupName($groupId, "", $clientLanguage);
            $prodname = WHMCS\Product\Product::getProductName($pid, "", $clientLanguage);
            $tax = $data["tax"];
            $desc = $groupname . " - " . $prodname;
            $pricing = getPricingInfo($pid);
            $billingcycle = $pricing["minprice"]["cycle"];
            if ($billingcycle == "onetime") {
                $billingcycle = "monthly";
            }
            $amount = $pricing["rawpricing"][$billingcycle];
            $configoptions = getCartConfigOptions($pid, $configoption, $billingcycle);
            foreach ($configoptions as $option) {
                $desc .= "\n" . $option["optionname"] . ": " . $option["selectedname"];
                $amount += $option["selectedsetup"] + $option["selectedrecurring"];
            }
            insert_query("tblquoteitems", array("quoteid" => $id, "description" => $desc, "quantity" => "1", "unitprice" => $amount, "discount" => "0", "taxable" => $tax));
            saveQuote($id, "", "", "", "", "", 0, "", "", "", "", "", "", "", "", "", "", "", "", array(), "", "", "", true);
            redir("action=manage&id=" . $id);
        }
        if ($action == "save") {
            check_token("WHMCS.admin.default");
            $lineitems = array();
            if ($desc) {
                foreach ($desc as $lid => $description) {
                    $lineitems[] = array("id" => $lid, "desc" => $description, "qty" => $qty[$lid], "up" => $up[$lid], "discount" => $discount[$lid], "taxable" => $taxable[$lid]);
                }
            }
            if ($add_desc) {
                $lineitems[] = array("desc" => $add_desc, "qty" => $add_qty, "up" => $add_up, "discount" => $add_discount, "taxable" => $add_taxable);
            }
            $phonenumber = App::formatPostedPhoneNumber();
            $id = saveQuote((int) App::getFromRequest("id"), App::getFromRequest("subject"), App::getFromRequest("stage"), App::getFromRequest("datecreated"), App::getFromRequest("validuntil"), App::getFromRequest("clienttype"), (int) App::getFromRequest("userid"), App::getFromRequest("firstname"), App::getFromRequest("lastname"), App::getFromRequest("companyname"), App::getFromRequest("email"), App::getFromRequest("address1"), App::getFromRequest("address2"), App::getFromRequest("city"), App::getFromRequest("state"), App::getFromRequest("postcode"), App::getFromRequest("country"), $phonenumber, (int) App::getFromRequest("currency"), $lineitems, App::getFromRequest("proposal"), App::getFromRequest("customernotes"), App::getFromRequest("adminnotes"), false, App::getFromRequest("tax_id"));
            logActivity("Modified Quote - Quote ID: " . $id, $userid);
            redir("action=manage&id=" . $id);
        }
        if ($action == "duplicate") {
            check_token("WHMCS.admin.default");
            $addstr = "";
            $result = select_query("tblquotes", "", array("id" => $id));
            $data = mysql_fetch_array($result);
            foreach ($data as $key => $value) {
                if (is_numeric($key)) {
                    if ($key == "0") {
                        $value = "";
                    }
                    if ($key == "2") {
                        $value = "Draft";
                    }
                    $addstr .= "'" . addslashes($value) . "',";
                }
            }
            $addstr = substr($addstr, 0, -1);
            $query = "INSERT INTO tblquotes VALUES (" . $addstr . ")";
            full_query($query);
            $newquoteid = mysql_insert_id();
            $result = select_query("tblquoteitems", "", array("quoteid" => $id), "id", "ASC");
            while ($data = mysql_fetch_array($result)) {
                $addstr = "";
                foreach ($data as $key => $value) {
                    if (is_numeric($key)) {
                        if ($key == "0") {
                            $value = "";
                        }
                        if ($key == "1") {
                            $value = $newquoteid;
                        }
                        $addstr .= "'" . addslashes($value) . "',";
                    }
                }
                $addstr = substr($addstr, 0, -1);
                $query = "INSERT INTO tblquoteitems VALUES (" . $addstr . ")";
                full_query($query);
            }
            redir("action=manage&id=" . $newquoteid . "&duplicated=true");
        }
        if ($action == "delete") {
            check_token("WHMCS.admin.default");
            delete_query("tblquotes", array("id" => $id));
            delete_query("tblquoteitems", array("quoteid" => $id));
            redir();
        }
        if ($action == "deleteline") {
            check_token("WHMCS.admin.default");
            delete_query("tblquoteitems", array("id" => $lid));
            if (!is_array($lineitems)) {
                $lineitems = array();
            }
            saveQuote($id, $subject, $stage, $datecreated, $validuntil, $clienttype, $userid, $firstname, $lastname, $companyname, $email, $address1, $address2, $city, $state, $postcode, $country, $phonenumber, $currency, $lineitems, $proposal, $customernotes, $adminnotes, true);
            redir("action=manage&id=" . $id);
        }
        if ($action == "dlpdf") {
            $pdfdata = genQuotePDF($id);
            global $_LANG;
            header("Pragma: public");
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header("Cache-Control: must-revalidate, post-check=0, pre-check=0, private");
            header("Cache-Control: private", false);
            header("Content-Type: application/octet-stream");
            header("Content-Disposition: attachment; filename=\"" . $_LANG["quotefilename"] . $id . ".pdf\"");
            header("Content-Transfer-Encoding: binary");
            echo $pdfdata;
            exit;
        }
        if ($action == "sendpdf") {
            check_token("WHMCS.admin.default");
            if (get_query_val("tblquotes", "datesent", array("id" => $id)) == "0000-00-00") {
                update_query("tblquotes", array("datesent" => "now()"), array("id" => $id));
            }
            $result = sendQuotePDF($id);
            if ($result === true) {
                redir("action=manage&id=" . $id . "&sent=true");
            } else {
                $action = "manage";
                infoBox(AdminLang::trans("system.errorSendingEmail"), $result, "error");
            }
        }
        if ($action == "convert") {
            check_token("WHMCS.admin.default");
            checkPermission("Create Invoice");
            $invoiceid = convertQuotetoInvoice($id, $invoicetype, $invoiceduedate, $depositpercent, $depositduedate, $finalduedate, $sendemail);
            redir("action=edit&id=" . $invoiceid, "invoices.php");
        }
        ob_start();
        $aInt->deleteJSConfirm("doDelete", "quotes", "deletesure", "?action=delete&id=");
        $aInt->deleteJSConfirm("doDeleteLine", "invoices", "deletelineitem", "?action=deleteline&id=" . $id . "&lid=");
        if (!$action) {
            echo $aInt->beginAdminTabs(array(AdminLang::trans("global.searchfilter")));
            echo "\n<form action=\"";
            echo $whmcs->getPhpSelf();
            echo "\" method=\"get\"><input type=\"hidden\" name=\"filter\" value=\"true\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr>\n    <td width=\"15%\" class=\"fieldlabel\">\n        ";
            echo AdminLang::trans("emails.subject");
            echo "    </td>\n    <td class=\"fieldarea\" width=\"50%\">\n        <input type=\"text\" name=\"subject\" value=\"";
            echo $subject;
            echo "\" class=\"form-control input-400\">\n    </td>\n    <td width=\"15%\" class=\"fieldlabel\">\n        ";
            echo AdminLang::trans("fields.client");
            echo "    </td>\n    <td class=\"fieldarea\">\n        ";
            echo $aInt->clientsDropDown($userid, false, "userid", true);
            echo "    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
            echo AdminLang::trans("quotes.stage");
            echo "</td><td class=\"fieldarea\"><select name=\"stage\" class=\"form-control select-inline\">\n<option value=\"\">";
            echo AdminLang::trans("global.any");
            echo "</option>\n<option";
            if ($stage == "Draft") {
                echo " selected";
            }
            echo ">";
            echo AdminLang::trans("quotes.stagedraft");
            echo "</option>\n<option";
            if ($stage == "Delivered") {
                echo " selected";
            }
            echo ">";
            echo AdminLang::trans("quotes.stagedelivered");
            echo "</option>\n<option";
            if ($stage == "On Hold") {
                echo " selected";
            }
            echo ">";
            echo AdminLang::trans("quotes.stageonhold");
            echo "</option>\n<option";
            if ($stage == "Accepted") {
                echo " selected";
            }
            echo ">";
            echo AdminLang::trans("quotes.stageaccepted");
            echo "</option>\n<option";
            if ($stage == "Lost") {
                echo " selected";
            }
            echo ">";
            echo AdminLang::trans("quotes.stagelost");
            echo "</option>\n<option";
            if ($stage == "Dead") {
                echo " selected";
            }
            echo ">";
            echo AdminLang::trans("quotes.stagedead");
            echo "</option>\n</select></td><td class=\"fieldlabel\">";
            echo AdminLang::trans("quotes.validityperiod");
            echo "</td><td class=\"fieldarea\"><select name=\"validity\" class=\"form-control select-inline\"><option value=\"\">";
            echo AdminLang::trans("global.any");
            echo "</option><option>";
            echo AdminLang::trans("status.valid");
            echo "</option><option>";
            echo AdminLang::trans("status.expired");
            echo "</option></select></td></tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"Filter\" class=\"btn btn-default\">\n</div>\n\n</form>\n\n";
            echo $aInt->endAdminTabs();
            echo "\n<br />\n\n";
            $aInt->sortableTableInit("lastmodified", "DESC");
            $where = array();
            if ($stage) {
                $where["stage"] = $stage;
            }
            if ($validity == "Valid") {
                $where["validuntil"] = array("sqltype" => ">", "value" => date("Ymd"));
            }
            if ($validity == "Expired") {
                $where["validuntil"] = array("sqltype" => "<=", "value" => date("Ymd"));
            }
            if ($userid) {
                $where["userid"] = $userid;
            }
            if ($subject) {
                $where["subject"] = array("sqltype" => "LIKE", "value" => $subject);
            }
            $numresults = select_query("tblquotes", "", $where);
            $numrows = mysql_num_rows($numresults);
            $result = select_query("tblquotes", "", $where, $orderby, $order, $page * $limit . "," . $limit);
            while ($data = mysql_fetch_array($result)) {
                $id = $data["id"];
                $subject = $data["subject"];
                $userid = $data["userid"];
                $firstname = $data["firstname"];
                $lastname = $data["lastname"];
                $companyname = $data["companyname"];
                $stage = $data["stage"];
                $total = $data["total"];
                $validuntil = $data["validuntil"];
                $lastmodified = $data["lastmodified"];
                $validuntil = fromMySQLDate($validuntil);
                $lastmodified = fromMySQLDate($lastmodified);
                if ($userid) {
                    $clientlink = $aInt->outputClientLink($userid);
                } else {
                    $clientlink = (string) $firstname . " " . $lastname;
                    if ($companyname) {
                        $clientlink .= " (" . $companyname . ")";
                    }
                }
                $tabledata[] = array("<a href=\"quotes.php?action=manage&id=" . $id . "\">" . $id . "</a>", "<a href=\"quotes.php?action=manage&id=" . $id . "\">" . $subject . "</a>", $clientlink, $stage, $total, $validuntil, $lastmodified, "<a href=\"quotes.php?action=manage&id=" . $id . "\"><img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"Edit\"></a>", "<a href=\"#\" onClick=\"doDelete('" . $id . "');return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"Delete\"></a>");
            }
            echo $aInt->sortableTable(array(array("id", "ID"), array("subject", "Subject"), "Client Name", array("stage", "Stage"), array("total", "Total"), array("validuntil", "Valid Until"), array("lastmodified", "Last Modified"), "", ""), $tabledata, $tableformurl, $tableformbuttons);
        } else {
            if ($action == "manage") {
                if ($id) {
                    $addons_html = run_hook("AdminAreaViewQuotePage", array("quoteid" => $id));
                }
                if (!$id) {
                    $datecreated = getTodaysDate();
                    $validuntil = fromMySQLDate(date("Y-m-d", mktime(0, 0, 0, date("m") + 1, date("d"), date("Y"))));
                    $clienttype = "existing";
                    $whmcs = App::self();
                    $userid = $whmcs->get_req_var("userid");
                    if (!WHMCS\User\Client::find($userid)->exists) {
                        $userid = 0;
                        $clienttype = "new";
                    }
                    $id = saveQuote("", "", "", $datecreated, $validuntil, $clienttype, $userid);
                }
                $result = select_query("tblquotes", "", array("id" => $id));
                $data = mysql_fetch_array($result);
                $subject = $data["subject"];
                $stage = $data["stage"];
                $datecreated = fromMySQLDate($data["datecreated"]);
                $datesent = $data["datesent"] != "0000-00-00" ? fromMySQLDate($data["datesent"]) : "";
                $dateaccepted = $data["dateaccepted"] != "0000-00-00" ? fromMySQLDate($data["dateaccepted"]) : "";
                $validuntil = fromMySQLDate($data["validuntil"]);
                $userid = $data["userid"];
                $proposal = $data["proposal"];
                $customernotes = $data["customernotes"];
                $adminnotes = $data["adminnotes"];
                $firstname = $data["firstname"];
                $lastname = $data["lastname"];
                $companyname = $data["companyname"];
                $email = $data["email"];
                $address1 = $data["address1"];
                $address2 = $data["address2"];
                $city = $data["city"];
                $state = $data["state"];
                $postcode = $data["postcode"];
                $country = $data["country"];
                $phonenumber = $data["phonenumber"];
                $currencyid = $data["currency"];
                $currency = getCurrency($userid, $currencyid);
                $subtotal = $data["subtotal"];
                $tax1 = $data["tax1"];
                $tax2 = $data["tax2"];
                $total = $data["total"];
                if (!$userid) {
                    $result = select_query("tblclients", "COUNT(*)", array("email" => $email));
                    $data = mysql_fetch_array($result);
                    $emailexists = $data[0];
                    if ($emailexists) {
                        infoBox(AdminLang::trans("quotes.emailexists"), AdminLang::trans("quotes.emailexistsmsg"));
                    }
                }
                if ($userid) {
                    $clienttype = "existing";
                    $clientsdetails = getClientsDetails($userid);
                    $fortax_state = $clientsdetails["state"];
                    $fortax_country = $clientsdetails["country"];
                } else {
                    $clienttype = "new";
                    $fortax_state = $state;
                    $fortax_country = $country;
                }
                $taxlevel1 = getTaxRate(1, $fortax_state, $fortax_country);
                $taxlevel2 = getTaxRate(2, $fortax_state, $fortax_country);
                if ($duplicated) {
                    infoBox(AdminLang::trans("quotes.quoteduplicated"), AdminLang::trans("quotes.quoteduplicatedmsg") . $id, "success");
                }
                if ($sent) {
                    infoBox(AdminLang::trans("quotes.quotedelivered"), AdminLang::trans("quotes.quotedeliveredmsg"), "success");
                }
                echo $infobox;
                if (!$currency["id"]) {
                    $currency["id"] = 1;
                }
                $jquerycode = "\$(\"#clienttypeexisting\").click(function () {\n    \$(\"#newclientform\").slideUp(\"slow\");\n});\n\$(\"#clienttypenew\").click(function () {\n    \$(\"#newclientform\").slideDown(\"slow\");\n});\n\$(\"#userdropdown\").change(function () {\n    \$(\"#clienttypeexisting\").click();\n});\n\$(\"#addproduct\").change(function () {\n    if (this.options[this.selectedIndex].value) {\n        WHMCS.http.jqClient.post(\"quotes.php\", { action: \"getdesc\", id: this.options[this.selectedIndex].value },\n        function(data){\n            \$(\"#add_desc\").val(data);\n        });\n        WHMCS.http.jqClient.post(\"quotes.php\", { action: \"getprice\", currency: " . $currency["id"] . ", id: this.options[this.selectedIndex].value },\n        function(data){\n            \$(\"#add_up\").val(data);\n        });\n    }\n});\n\$(\"textarea.expanding\").autogrow({\n    minHeight: 16,\n    lineHeight: 14\n});";
                $jscode .= "function selectSingle() {\n    \$(\"#singleoptions\").slideToggle();\n    \$(\"#depositoptions\").slideToggle();\n}\nfunction selectDeposit() {\n    \$(\"#singleoptions\").slideToggle();\n    \$(\"#depositoptions\").slideToggle();\n}";
                foreach ($addons_html as $addon_html) {
                    echo "<div style=\"margin-bottom:15px;\">" . $addon_html . "</div>";
                }
                echo "\n<form method=\"post\" action=\"";
                echo $_SERVER["PHP_SELF"];
                echo "\" id=\"clientinfo\">\n<input type=\"hidden\" name=\"action\" value=\"save\" />\n";
                if ($id) {
                    echo "<input type=\"hidden\" name=\"id\" value=\"";
                    echo $id;
                    echo "\" />";
                }
                echo "<h2>";
                echo AdminLang::trans("quotes.generalinfo");
                echo "</h2>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"15%\" class=\"fieldlabel\">";
                echo AdminLang::trans("quotes.subject");
                echo "</td><td class=\"fieldarea\"><input type=\"text\" id=\"inputQuoteSubject\" name=\"subject\" value=\"";
                echo $subject;
                echo "\" class=\"form-control\"></td><td width=\"15%\" class=\"fieldlabel\">";
                echo AdminLang::trans("quotes.stage");
                echo "</td><td class=\"fieldarea\"><select id=\"quoteStage\" name=\"stage\" class=\"form-control select-inline\">\n<option value=\"Draft\"";
                if ($stage == "Draft") {
                    echo " selected";
                }
                echo ">";
                echo AdminLang::trans("quotes.stagedraft");
                echo "</option>\n<option value=\"Delivered\"";
                if ($stage == "Delivered") {
                    echo " selected";
                }
                echo ">";
                echo AdminLang::trans("quotes.stagedelivered");
                echo "</option>\n<option value=\"On Hold\"";
                if ($stage == "On Hold") {
                    echo " selected";
                }
                echo ">";
                echo AdminLang::trans("quotes.stageonhold");
                echo "</option>\n<option value=\"Accepted\"";
                if ($stage == "Accepted") {
                    echo " selected";
                }
                echo ">";
                echo AdminLang::trans("quotes.stageaccepted");
                echo "</option>\n<option value=\"Lost\"";
                if ($stage == "Lost") {
                    echo " selected";
                }
                echo ">";
                echo AdminLang::trans("quotes.stagelost");
                echo "</option>\n<option value=\"Dead\"";
                if ($stage == "Dead") {
                    echo " selected";
                }
                echo ">";
                echo AdminLang::trans("quotes.stagedead");
                echo "</option>\n</select></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
                echo AdminLang::trans("quotes.datecreated");
                echo "    </td>\n    <td class=\"fieldarea\">\n        <div class=\"form-group date-picker-prepend-icon\">\n            <label for=\"inputDateCreated\" class=\"field-icon\">\n                <i class=\"fal fa-calendar-alt\"></i>\n            </label>\n            <input id=\"inputDateCreated\"\n                   type=\"text\"\n                   name=\"datecreated\"\n                   value=\"";
                echo $datecreated;
                echo "\"\n                   class=\"form-control date-picker-single future\"\n            />\n        </div>\n    </td>\n    <td class=\"fieldlabel\">\n        ";
                echo AdminLang::trans("quotes.validuntil");
                echo "    </td>\n    <td class=\"fieldarea\">\n        <div class=\"form-group date-picker-prepend-icon\">\n            <label for=\"inputValidUntil\" class=\"field-icon\">\n                <i class=\"fal fa-calendar-alt\"></i>\n            </label>\n            <input id=\"inputValidUntil\"\n                   type=\"text\"\n                   name=\"validuntil\"\n                   value=\"";
                echo $validuntil;
                echo "\"\n                   class=\"form-control date-picker-single future\"\n            />\n        </div>\n    </td>\n</tr>\n";
                if ($datesent || $dateaccepted) {
                    echo "<tr>";
                    if ($datesent) {
                        echo "<td class=\"fieldlabel\">";
                        echo AdminLang::trans("quotes.datesent");
                        echo "</td><td class=\"fieldarea\">";
                        echo $datesent;
                        echo "</td>";
                    }
                    if ($dateaccepted) {
                        echo "<td class=\"fieldlabel\">";
                        echo AdminLang::trans("quotes.dateaccepted");
                        echo "</td><td class=\"fieldarea\">";
                        echo $dateaccepted;
                        echo "</td>";
                    }
                    echo "</tr>\n";
                }
                echo "</table>\n\n<div class=\"btn-container\">\n    <input id=\"inputSaveChanges\" type=\"submit\" value=\"";
                echo AdminLang::trans("global.savechanges");
                echo "\" class=\"btn btn-primary\" />\n    <input type=\"button\" value=\"";
                echo AdminLang::trans("global.duplicate");
                echo "\" class=\"button btn btn-default\" onclick=\"window.location='quotes.php?action=duplicate&id=";
                echo $id . generate_token("link");
                echo "'\"";
                if (!$id) {
                    echo " disabled=\"true\"";
                }
                echo " />\n    <input type=\"button\" value=\"";
                echo AdminLang::trans("invoices.printableversion");
                echo "\" class=\"button btn btn-default\" onclick=\"window.open('../viewquote.php?id=";
                echo $id;
                echo "','windowfrm','menubar=yes,toolbar=yes,scrollbars=yes,resizable=yes,width=750,height=600')\" \"";
                if (!$id) {
                    echo " disabled=\"true\"";
                }
                echo " />\n    <input type=\"button\" value=\"";
                echo AdminLang::trans("quotes.viewPdf");
                echo "\" class=\"button btn btn-default\" onclick=\"window.open('../dl.php?type=q&id=";
                echo $id;
                echo "&viewpdf=1','pdfquote','')\" />\n    <input type=\"button\" value=\"";
                echo AdminLang::trans("quotes.downloadPdf");
                echo "\" class=\"button btn btn-default\" onclick=\"window.location='";
                echo $_SERVER["PHP_SELF"];
                echo "?action=dlpdf&id=";
                echo $id;
                echo "';\"";
                if (!$id) {
                    echo " disabled=\"true\"";
                }
                echo " />\n    <input type=\"button\" id=\"emailAsPdf\" value=\"";
                echo AdminLang::trans("quotes.emailAsPdf");
                echo "\" class=\"button btn btn-default\" onclick=\"window.location='quotes.php?action=sendpdf&id=";
                echo $id . generate_token("link");
                echo "';\"";
                if (!$id) {
                    echo " disabled=\"true\"";
                }
                echo " />\n    <input type=\"button\" id=\"convertToInvoice\" value=\"";
                echo AdminLang::trans("quotes.convertToPdf");
                echo "\" class=\"btn btn-default\" data-toggle=\"modal\" data-target=\"#modalQuoteConvert\"";
                if (!$id) {
                    echo " disabled=\"true\"";
                }
                echo " />\n    <input type=\"button\" value=\"";
                echo AdminLang::trans("global.delete");
                echo "\" class=\"btn btn-danger\" onclick=\"doDelete('";
                echo $id;
                echo "');\"";
                if (!$id) {
                    echo " disabled=\"true\"";
                }
                echo " />\n</div>\n\n<h2>";
                echo AdminLang::trans("quotes.clientinfo");
                echo "</h2>\n\n<p><input type=\"radio\" name=\"clienttype\" value=\"existing\" id=\"clienttypeexisting\"";
                if ($clienttype == "existing") {
                    echo " checked";
                }
                echo " /> <label for=\"clienttypeexisting\">";
                echo AdminLang::trans("quotes.quoteexistingclient");
                echo ":</label> ";
                echo str_replace("<select id=\"selectUserid\"", "<select id=\"userdropdown\"", $aInt->clientsDropDown($userid));
                echo " ";
                if ($clienttype == "existing") {
                    echo " <a href=\"clientssummary.php?userid=" . $userid . "\" target=\"_blank\">View Client Profile</a>";
                }
                echo "<br /><input type=\"radio\" name=\"clienttype\" value=\"new\" id=\"clienttypenew\"";
                if ($clienttype == "new") {
                    echo " checked";
                }
                echo " /> <label for=\"clienttypenew\">";
                echo AdminLang::trans("quotes.quotenewclient");
                echo "</label></p>\n\n<div id=\"newclientform\"";
                if ($clienttype == "existing") {
                    echo " style=\"display:none;\"";
                }
                echo ">\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"15%\" class=\"fieldlabel\">";
                echo AdminLang::trans("fields.firstname");
                echo "</td><td class=\"fieldarea\"><input type=\"text\" id=\"firstname\" name=\"firstname\" class=\"form-control input-250\" value=\"";
                echo $firstname;
                echo "\"></td><td width=\"15%\" class=\"fieldlabel\">";
                echo AdminLang::trans("fields.address1");
                echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"address1\" class=\"form-control input-250\" value=\"";
                echo $address1;
                echo "\"></td></tr>\n<tr><td class=\"fieldlabel\">";
                echo AdminLang::trans("fields.lastname");
                echo "</td><td class=\"fieldarea\"><input type=\"text\" id=\"lastname\" name=\"lastname\" class=\"form-control input-250\" value=\"";
                echo $lastname;
                echo "\"></td><td class=\"fieldlabel\">";
                echo AdminLang::trans("fields.address2");
                echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"address2\" class=\"form-control input-250\" value=\"";
                echo $address2;
                echo "\"></td></tr>\n<tr><td class=\"fieldlabel\">";
                echo AdminLang::trans("fields.companyname");
                echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"companyname\" class=\"form-control input-250\" value=\"";
                echo $companyname;
                echo "\"></td><td class=\"fieldlabel\">";
                echo AdminLang::trans("fields.city");
                echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"city\" class=\"form-control input-250\" value=\"";
                echo $city;
                echo "\"></td></tr>\n<tr><td class=\"fieldlabel\">";
                echo AdminLang::trans("fields.email");
                echo "</td><td class=\"fieldarea\"><input type=\"text\" id=\"inputNewClientEmail\" name=\"email\" class=\"form-control input-250\" value=\"";
                echo $email;
                echo "\"></td><td class=\"fieldlabel\">";
                echo AdminLang::trans("fields.state");
                echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"state\" data-selectinlinedropdown=\"1\" class=\"form-control input-250\" value=\"";
                echo $state;
                echo "\"></td></tr>\n<tr><td class=\"fieldlabel\">";
                echo AdminLang::trans("fields.phonenumber");
                echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"phonenumber\" class=\"form-control input-250\" value=\"";
                echo $phonenumber;
                echo "\"></td><td class=\"fieldlabel\">";
                echo AdminLang::trans("fields.postcode");
                echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"postcode\" class=\"form-control input-150\" value=\"";
                echo $postcode;
                echo "\"></td></tr>\n<tr><td class=\"fieldlabel\">";
                echo AdminLang::trans("currencies.currency");
                echo "</td><td class=\"fieldarea\"><select name=\"currency\" class=\"form-control select-inline\">";
                $result = select_query("tblcurrencies", "id,code,`default`", "", "code", "ASC");
                while ($data = mysql_fetch_array($result)) {
                    echo "<option value=\"" . $data["id"] . "\"";
                    if ($currencyid && $data["id"] == $currencyid || !$currencyid && $data["default"]) {
                        echo " selected";
                    }
                    echo ">" . $data["code"] . "</option>";
                }
                echo "</select></td><td class=\"fieldlabel\">";
                echo AdminLang::trans("fields.country");
                echo "</td><td class=\"fieldarea\">";
                echo getCountriesDropDown($country);
                echo "</td></tr>\n</table>\n</div>\n\n<h2>";
                echo AdminLang::trans("quotes.lineitems");
                echo "</h2>\n\n";
                echo WHMCS\View\Asset::jsInclude("jqueryag.js");
                echo "\n<div class=\"tablebg\">\n<table class=\"datatable\" width=\"100%\" border=\"0\" cellspacing=\"1\" cellpadding=\"3\">\n<tr bgcolor=\"#efefef\" style=\"text-align:center;font-weight:bold\">\n    <th width=\"60\">";
                echo AdminLang::trans("quotes.qty");
                echo "</th>\n    <th>";
                echo AdminLang::trans("quotes.description");
                echo "</th>\n    <th width=\"120\">";
                echo AdminLang::trans("quotes.unitprice");
                echo "</th>\n    <th width=\"120\">";
                echo AdminLang::trans("quotes.discount");
                echo "</th>\n    <th width=\"120\">";
                echo AdminLang::trans("quotes.total");
                echo "</th>\n    <th width=\"60\">";
                echo AdminLang::trans("quotes.taxed");
                echo "</th>\n    <th width=\"20\"></th>\n</tr>\n";
                if ($id) {
                    $result = select_query("tblquoteitems", "", array("quoteid" => $id), "id", "ASC");
                    for ($i = 0; $data = mysql_fetch_array($result); $i++) {
                        $line_id = $data["id"];
                        $line_desc = $data["description"];
                        $line_qty = $data["quantity"];
                        $line_unitprice = $data["unitprice"];
                        $line_discount = $data["discount"];
                        $line_taxable = $data["taxable"];
                        $line_total = formatCurrency($line_qty * $line_unitprice * (1 - $line_discount / 100));
                        echo "<tr bgcolor=#ffffff style=\"text-align:center;\"><td><input type=\"text\" name=\"qty[" . $line_id . "]\" value=\"" . $line_qty . "\" class=\"form-control\"></td><td><textarea name=\"desc[" . $line_id . "]\" class=\"expanding form-control\">" . $line_desc . "</textarea></td><td><input type=\"text\" name=\"up[" . $line_id . "]\" value=\"" . $line_unitprice . "\" class=\"form-control\"></td><td><input type=\"text\" name=\"discount[" . $line_id . "]\" value=\"" . $line_discount . "\" class=\"form-control\"></td><td>" . $line_total . "</td><td><input type=\"checkbox\" name=\"taxable[" . $line_id . "]\" value=\"1\"";
                        if ($line_taxable) {
                            echo " checked";
                        }
                        echo "></td><td width=20 align=center><a href=\"#\" onClick=\"doDeleteLine('" . $line_id . "');return false\"><img src=\"images/delete.gif\" border=\"0\"></tr>";
                    }
                }
                echo "<tr bgcolor=#ffffff style=\"text-align:center;\"><td><input type=\"text\" name=\"add_qty\" value=\"1\" class=\"form-control\"></td><td><textarea name=\"add_desc\" id=\"add_desc\" class=\"expanding form-control\"></textarea></td><td><input type=\"text\" name=\"add_up\" id=\"add_up\" value=\"0.00\" class=\"form-control\"></td><td><input type=\"text\" name=\"add_discount\" value=\"0.00\" class=\"form-control\"></td><td></td><td><input type=\"checkbox\" name=\"add_taxable\" value=\"1\" /></td><td></td></tr>\n<tr bgcolor=\"#efefef\" style=\"text-align:center;font-weight:bold\"><td colspan=\"4\"><table width=\"100%\" cellspacing=\"0\" cellpadding=\"0\"><tr><td style=\"text-align:left;font-weight:normal;\"><a href=\"#\" onclick=\"";
                $aInt->popupWindow($_SERVER["PHP_SELF"] . "?action=loadprod&id=" . $id, "clientinfo");
                echo "\"><img src=\"images/icons/add.png\" border=\"0\" align=\"absmiddle\" /> ";
                echo AdminLang::trans("quotes.addPredefinedProduct");
                echo "</a></td><td align=\"right\">";
                echo AdminLang::trans("quotes.subtotal");
                echo "&nbsp;</td></tr></table></td><td width=90>";
                echo formatCurrency($subtotal);
                echo "</td><td></td><td></td></tr>\n";
                if ($CONFIG["TaxEnabled"] == "on") {
                    if (0 < $taxlevel1["rate"]) {
                        echo "<tr bgcolor=\"#efefef\" style=\"text-align:center;font-weight:bold\"><td colspan=\"4\" align=\"right\">";
                        echo $taxlevel1["name"];
                        echo " @ ";
                        echo $taxlevel1["rate"];
                        echo "%:&nbsp;</td><td width=90>";
                        echo formatCurrency($tax1);
                        echo "</td><td></td><td></td></tr>";
                    }
                    if (0 < $taxlevel2["rate"]) {
                        echo "<tr bgcolor=\"#efefef\" style=\"text-align:center;font-weight:bold\"><td colspan=\"4\" align=\"right\">";
                        echo $taxlevel2["name"];
                        echo " @ ";
                        echo $taxlevel2["rate"];
                        echo "%:&nbsp;</td><td width=90>";
                        echo formatCurrency($tax2);
                        echo "</td><td></td><td></td></tr>";
                    }
                }
                echo "<tr bgcolor=\"#efefef\" style=\"text-align:center;font-weight:bold\"><td colspan=\"4\" align=\"right\">";
                echo AdminLang::trans("quotes.totaldue");
                echo "&nbsp;</td><td width=90>";
                echo formatCurrency($total);
                echo "</td><td></td><td></td></tr>\n</table>\n</div>\n\n<h2>";
                echo AdminLang::trans("quotes.notes");
                echo "</h2>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\">";
                echo AdminLang::trans("quotes.proposaltext");
                echo "<br />";
                echo AdminLang::trans("quotes.proposaltextmsg");
                echo "</td><td class=\"fieldarea\"><textarea name=\"proposal\" rows=\"5\" class=\"form-control\">";
                echo $proposal;
                echo "</textarea></td></tr>\n<tr><td width=\"15%\" class=\"fieldlabel\">";
                echo AdminLang::trans("quotes.customernotes");
                echo "<br />";
                echo AdminLang::trans("quotes.customernotesmsg");
                echo "</td><td class=\"fieldarea\"><textarea name=\"customernotes\" rows=\"5\" class=\"form-control\">";
                echo $customernotes;
                echo "</textarea></td></tr>\n<tr><td class=\"fieldlabel\">";
                echo AdminLang::trans("quotes.adminonlynotes");
                echo "<br />";
                echo AdminLang::trans("quotes.adminonlynotesmsg");
                echo "</td><td class=\"fieldarea\"><textarea name=\"adminnotes\" rows=\"5\" class=\"form-control\">";
                echo $adminnotes;
                echo "</textarea></td></tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
                echo AdminLang::trans("global.savechanges");
                echo "\" class=\"btn btn-primary\" />\n    <input type=\"button\" value=\"";
                echo AdminLang::trans("global.duplicate");
                echo "\" class=\"button btn btn-default\" onclick=\"window.location='quotes.php?action=duplicate&id=";
                echo $id . generate_token("link");
                echo "'\"";
                if (!$id) {
                    echo " disabled=\"true\"";
                }
                echo " />\n    <input type=\"button\" value=\"";
                echo AdminLang::trans("invoices.printableversion");
                echo "\" class=\"button btn btn-default\" onclick=\"window.open('../viewquote.php?id=";
                echo $id;
                echo "','windowfrm','menubar=yes,toolbar=yes,scrollbars=yes,resizable=yes,width=750,height=600')\" \"";
                if (!$id) {
                    echo " disabled=\"true\"";
                }
                echo " />\n    <input type=\"button\" value=\"";
                echo AdminLang::trans("quotes.viewPdf");
                echo "\" class=\"button btn btn-default\" onclick=\"window.open('../dl.php?type=q&id=";
                echo $id;
                echo "&viewpdf=1','pdfquote','')\" /> <input type=\"button\" value=\"Download PDF\" class=\"button btn btn-default\" onclick=\"window.location='";
                echo $_SERVER["PHP_SELF"];
                echo "?action=dlpdf&id=";
                echo $id;
                echo "';\"";
                if (!$id) {
                    echo " disabled=\"true\"";
                }
                echo " />\n    <input type=\"button\" value=\"";
                echo AdminLang::trans("quotes.emailAsPdf");
                echo "\" class=\"button btn btn-default\" onclick=\"window.location='quotes.php?action=sendpdf&id=";
                echo $id . generate_token("link");
                echo "';\"";
                if (!$id) {
                    echo " disabled=\"true\"";
                }
                echo " />\n    <input type=\"button\" value=\"";
                echo AdminLang::trans("quotes.convertToPdf");
                echo "\" class=\"btn btn-default\" data-toggle=\"modal\" data-target=\"#modalQuoteConvert\"";
                if (!$id) {
                    echo " disabled=\"true\"";
                }
                echo " />\n    <input type=\"button\" value=\"";
                echo AdminLang::trans("global.delete");
                echo "\" class=\"btn btn-danger\" onclick=\"doDelete('";
                echo $id;
                echo "');\"";
                if (!$id) {
                    echo " disabled=\"true\"";
                }
                echo " />\n</div>\n</form>\n\n";
                $content = "<form id=\"convertquotefrm\">\n" . generate_token("form") . "\n<label class=\"radio-inline\"><input type=\"radio\" name=\"invoicetype\" value=\"single\" onclick=\"selectSingle()\" checked /> Generate a single invoice for the entire amount</label><br />\n<div id=\"singleoptions\" align=\"center\">\n<br />\n<div class=\"form-group date-picker-prepend-icon\">\n    Due Date of Invoice: \n    <label for=\"inputInvoiceDueDate\" class=\"field-icon\">\n        <i class=\"fal fa-calendar-alt\"></i>\n    </label>\n    <input id=\"inputInvoiceDueDate\"\n           type=\"text\"\n           name=\"invoiceduedate\"\n           value=\"" . getTodaysDate() . "\"\n           class=\"form-control input-inline date-picker-single future\"\n    />\n</div>\n<br /><br />\n</div>\n<label class=\"radio-inline\"><input type=\"radio\" name=\"invoicetype\" value=\"deposit\" onclick=\"selectDeposit()\" /> Split into 2 invoices - a deposit and final payment</label><br />\n<div id=\"depositoptions\" align=\"center\" style=\"display:none;\">\n<br />\nEnter Deposit Percentage: <input type=\"text\" name=\"depositpercent\" value=\"50\" size=\"5\" />%<br />\n<div class=\"form-group date-picker-prepend-icon\">\n    Due Date of Deposit: \n    <label for=\"inputDepositDueDate\" class=\"field-icon\">\n        <i class=\"fal fa-calendar-alt\"></i>\n    </label>\n    <input id=\"inputDepositDueDate\"\n           type=\"text\"\n           name=\"depositduedate\"\n           value=\"" . getTodaysDate() . "\"\n           class=\"form-control input-inline date-picker-single future\"\n    />\n</div>\n<div class=\"form-group date-picker-prepend-icon\">\n    Due Date of Final Payment: \n    <label for=\"inputDepositDueDate\" class=\"field-icon\">\n        <i class=\"fal fa-calendar-alt\"></i>\n    </label>\n    <input id=\"inputDepositDueDate\"\n           type=\"text\"\n           name=\"depositduedate\"\n           value=\"" . WHMCS\Carbon::today()->addMonth(1)->toAdminDateFormat() . "\"\n           class=\"form-control input-inline date-picker-single future\"\n    />\n</div>\n</div>\n<br />\n<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"sendemail\" checked /> Send Invoice Notification Email</label>\n</form>";
                echo $aInt->modal("QuoteConvert", "Convert to Invoice", $content, array(array("title" => AdminLang::trans("global.submit"), "onclick" => "window.location=\"?action=convert&id=" . $id . "&\" + jQuery(\"#convertquotefrm\").serialize();", "class" => "btn-primary"), array("title" => AdminLang::trans("global.cancel"))));
            }
        }
        $content = ob_get_contents();
        ob_end_clean();
        $aInt->content = $content;
        $aInt->jquerycode = $jquerycode;
        $aInt->jscode = $jscode;
        $aInt->display();
    }
}

?>