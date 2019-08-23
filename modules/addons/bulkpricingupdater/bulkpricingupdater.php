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
function bulkpricingupdater_config()
{
    return array("name" => "Bulk Pricing Updater", "description" => "This addon utility allows you to perform system wide price changes to existing clients", "version" => "3.0", "author" => "WHMCS", "language" => "english", "fields" => array());
}
function bulkpricingupdater_output($vars)
{
    global $currency;
    $modulelink = $vars["modulelink"];
    $step = isset($_REQUEST["step"]) ? $_REQUEST["step"] : "";
    $validate = isset($_REQUEST["validate"]) ? $_REQUEST["validate"] : "";
    $doupdate = isset($_REQUEST["doupdate"]) ? $_REQUEST["doupdate"] : "";
    $type = isset($_REQUEST["type"]) ? $_REQUEST["type"] : "";
    if ($validate || $doupdate) {
        check_token();
        $errorResponse = "";
        $resultsResponse = "";
        $criteriaResponse = "<h2>Criteria</h2><p>";
        if ($type == "services") {
            $productids = isset($_REQUEST["productids"]) && is_array($_REQUEST["productids"]) ? db_escape_numarray($_REQUEST["productids"]) : array();
            $prodcycles = isset($_REQUEST["prodcycles"]) && is_array($_REQUEST["prodcycles"]) ? $_REQUEST["prodcycles"] : array();
            $prodstatus = isset($_REQUEST["prodstatus"]) && is_array($_REQUEST["prodstatus"]) ? $_REQUEST["prodstatus"] : array();
            $prodcurrentprice = isset($_REQUEST["prodcurrentprice"]) ? $_REQUEST["prodcurrentprice"] : "";
            $prodcurrid = isset($_REQUEST["prodcurrid"]) ? $_REQUEST["prodcurrid"] : "";
            if ($prodcurrid) {
                $currency = getCurrency("", $prodcurrid);
            }
            if (count($productids) == 0) {
                $errorResponse = "Please select at least 1 product.";
            } else {
                if (count($prodcycles) == 0) {
                    $errorResponse = "Please select at least 1 billing cycle.";
                } else {
                    if (count($prodstatus) == 0) {
                        $errorResponse = "Please select at least 1 status.";
                    } else {
                        $products = array();
                        $result = select_query("tblproducts", "tblproducts.name,tblproductgroups.name AS groupname", array("tblproducts.id" => array("sqltype" => "IN", "values" => $productids)), "tblproductgroups`.`order` ASC,`tblproducts`.`order` ASC,`name", "ASC", "", "tblproductgroups ON tblproducts.gid=tblproductgroups.id");
                        while ($data = mysql_fetch_array($result)) {
                            $pname = $data["name"];
                            $ptype = $data["groupname"];
                            $products[] = $ptype . " - " . $pname;
                        }
                        $criteriaResponse .= "Product Names<br /><strong>" . implode("<br />", $products) . "</strong><br />" . "Billing Cycles: <strong>" . implode(", ", $prodcycles) . "</strong><br />" . "Statuses: <strong>" . implode(", ", $prodstatus) . "</strong><br />";
                        if ($prodcurrentprice) {
                            $criteriaResponse .= "Current Price: <strong>" . formatCurrency($prodcurrentprice) . "</strong>";
                        }
                    }
                }
            }
        } else {
            if ($type == "addons") {
                $addonids = isset($_REQUEST["addonids"]) && is_array($_REQUEST["addonids"]) ? db_escape_numarray($_REQUEST["addonids"]) : array();
                $addoncycles = isset($_REQUEST["addoncycles"]) && is_array($_REQUEST["addoncycles"]) ? $_REQUEST["addoncycles"] : array();
                $addonstatus = isset($_REQUEST["addonstatus"]) && is_array($_REQUEST["addonstatus"]) ? $_REQUEST["addonstatus"] : array();
                $addoncurrentprice = isset($_REQUEST["addoncurrentprice"]) ? $_REQUEST["addoncurrentprice"] : "";
                $addoncurrid = isset($_REQUEST["addoncurrid"]) ? $_REQUEST["addoncurrid"] : "";
                if ($addoncurrid) {
                    $currency = getCurrency("", $addoncurrid);
                }
                if (count($addonids) == 0) {
                    $errorResponse = "Please select at least 1 addon product.";
                } else {
                    if (count($addoncycles) == 0) {
                        $errorResponse = "Please select at least 1 billing cycle.";
                    } else {
                        if (count($addonstatus) == 0) {
                            $errorResponse = "Please select at least 1 status.";
                        } else {
                            $addons = array();
                            $result = select_query("tbladdons", "name", array("id" => array("sqltype" => "IN", "values" => $addonids)), "name", "ASC");
                            while ($data = mysql_fetch_array($result)) {
                                $addons[] = $data["name"];
                            }
                            $criteriaResponse .= "Addon Names<br /><strong>" . implode("<br />", $addons) . "</strong><br />" . "Billing Cycles: <strong>" . implode(", ", $addoncycles) . "</strong><br />" . "Statuses: <strong>" . implode(", ", $addonstatus) . "</strong><br />";
                            if ($addoncurrentprice) {
                                $criteriaResponse .= "Current Price: <strong>" . formatCurrency($addoncurrentprice) . "</strong>";
                            }
                        }
                    }
                }
            } else {
                if ($type == "domains") {
                    $domaintlds = isset($_REQUEST["domaintlds"]) && is_array($_REQUEST["domaintlds"]) ? $_REQUEST["domaintlds"] : array();
                    $regperiod = isset($_REQUEST["regperiod"]) ? $_REQUEST["regperiod"] : "";
                    $domainstatus = isset($_REQUEST["domainstatus"]) && is_array($_REQUEST["domainstatus"]) ? $_REQUEST["domainstatus"] : array();
                    $domainaddons = isset($_REQUEST["domainaddons"]) && is_array($_REQUEST["domainaddons"]) ? $_REQUEST["domainaddons"] : array();
                    $domaincurrentprice = isset($_REQUEST["domaincurrentprice"]) ? $_REQUEST["domaincurrentprice"] : "";
                    $domaincurrid = isset($_REQUEST["domaincurrid"]) ? $_REQUEST["domaincurrid"] : "";
                    if ($domaincurrid) {
                        $currency = getCurrency("", $domaincurrid);
                    }
                    if (count($domaintlds) == 0) {
                        $errorResponse = "Please select at least 1 TLD.";
                    } else {
                        if (count($domainstatus) == 0) {
                            $errorResponse = "Please select at least 1 status.";
                        } else {
                            $addons = array();
                            if (in_array("dnsmanagement", $domainaddons)) {
                                $addons[] = "DNS Management";
                            }
                            if (in_array("emailforwarding", $domainaddons)) {
                                $addons[] = "Email Forwarding";
                            }
                            if (in_array("idprotection", $domainaddons)) {
                                $addons[] = "ID Protection";
                            }
                            $criteriaResponse = "Domain TLDs<br /><strong>" . implode(", ", $domaintlds) . "</strong><br />" . "Registration Period: <strong>" . $regperiod . "</strong><br />" . "Statuses: <strong>" . implode(", ", $domainstatus) . "</strong><br />" . "Addons: <strong>" . (count($addons) ? implode(", ", $addons) : "None") . "</strong>";
                            if ($domaincurrentprice) {
                                $criteriaResponse .= "Current Price: <strong>" . formatCurrency($domaincurrentprice) . "</strong>";
                            }
                        }
                    }
                } else {
                    $errorResponse = "Invalid Type.";
                }
            }
        }
        if (!$errorResponse) {
            $newprice = isset($_REQUEST["newprice"]) ? $_REQUEST["newprice"] : "";
            $increaseprice = isset($_REQUEST["increaseprice"]) ? $_REQUEST["increaseprice"] : "";
            if (!is_numeric($newprice) && !is_numeric($increaseprice)) {
                $errorResponse = "New price or increase amount must be a valid number.";
            } else {
                if ($newprice <= 0 && $increaseprice <= 0) {
                    $errorResponse = "The new price or increase amount must be greater than zero.";
                } else {
                    if (0 < $newprice && 0 < $increaseprice) {
                        $errorResponse = "You must only provide either a new price or an increase amount, not both.";
                    }
                }
            }
        }
        if ($doupdate) {
            if ($errorResponse) {
                $errorResponse = "Error!<br />" . $errorResponse;
            } else {
                $query = "";
                if ($type == "services") {
                    $query = "UPDATE tblhosting SET ";
                    if (0 < $newprice) {
                        $query .= "amount=" . round($newprice, 2);
                    } else {
                        $query .= "amount=amount+" . round($increaseprice, 2);
                    }
                    $query .= " WHERE " . "packageid IN (" . db_build_in_array($productids) . ") AND " . "billingcycle IN (" . db_build_in_array($prodcycles) . ") AND " . "domainstatus IN (" . db_build_in_array($prodstatus) . ") AND " . "userid IN (SELECT id FROM tblclients WHERE currency=" . (int) $prodcurrid . ")";
                    if ($prodcurrentprice) {
                        $query .= " AND amount='" . db_escape_string($prodcurrentprice) . "'";
                    }
                } else {
                    if ($type == "addons") {
                        $query = "UPDATE tblhostingaddons,tblhosting SET ";
                        if (0 < $newprice) {
                            $query .= "tblhostingaddons.recurring=" . round($newprice, 2);
                        } else {
                            $query .= "tblhostingaddons.recurring=tblhostingaddons.recurring+" . round($increaseprice, 2);
                        }
                        $query .= " WHERE " . "tblhostingaddons.addonid='" . db_build_in_array($addonids) . "' AND " . "tblhostingaddons.billingcycle IN (" . db_build_in_array($addoncycles) . ") AND " . "tblhostingaddons.status IN (" . db_build_in_array($addonstatus) . ") AND " . "tblhosting.id=tblhostingaddons.hostingid AND " . "tblhosting.userid IN (SELECT id FROM tblclients WHERE currency=" . (int) $addoncurrid . ")";
                        if ($addoncurrentprice) {
                            $query .= " AND tblhostingaddons.recurring='" . db_escape_string($addoncurrentprice) . "'";
                        }
                    } else {
                        if ($type == "domains") {
                            $tldFilter = array();
                            foreach ($domaintlds as $domaintld) {
                                $tldFilter[] = "domain LIKE '%" . db_escape_string($domaintld) . "'";
                            }
                            $query = "UPDATE tbldomains SET ";
                            if (0 < $newprice) {
                                $query .= "recurringamount=" . round($newprice, 2);
                            } else {
                                $query .= "recurringamount=recurringamount+" . round($increaseprice, 2);
                            }
                            $query .= " WHERE " . "(" . implode(" OR ", $tldFilter) . ") AND " . "registrationperiod='" . db_escape_string($regperiod) . "' AND " . "status IN (" . db_build_in_array($domainstatus) . ") AND " . "userid IN (SELECT id FROM tblclients WHERE currency=" . (int) $domaincurrid . ")";
                            if (in_array("dnsmanagement", $domainaddons)) {
                                $query .= " AND (dnsmanagement='1' OR (dnsmanagement='on' AND dnsmanagement != '0'))";
                            } else {
                                $query .= " AND (dnsmanagement='0' OR dnsmanagement='')";
                            }
                            if (in_array("emailforwarding", $domainaddons)) {
                                $query .= " AND (emailforwarding='1' OR (emailforwarding='on' AND emailforwarding != '0'))";
                            } else {
                                $query .= " AND (emailforwarding='0' OR emailforwarding='')";
                            }
                            if (in_array("idprotection", $domainaddons)) {
                                $query .= " AND (idprotection='1' OR (idprotection = 'on' AND idprotection != '0'))";
                            } else {
                                $query .= " AND (idprotection='0' OR idprotection='')";
                            }
                            if ($domaincurrentprice) {
                                $query .= " AND recurringamount='" . db_escape_string($domaincurrentprice) . "'";
                            }
                        }
                    }
                }
                $result = full_query($query);
                if ($result) {
                    $resultsResponse = "Success!<br />" . mysql_affected_rows() . " Database Record(s) Updated";
                } else {
                    $resultsResponse = "Failed!<br />0 Database Record(s) Updated<br /><br />" . "<small>SQL Query for Debugging Purposes<br />" . $query . "</small>";
                }
            }
        }
        if (0 < $newprice) {
            $criteriaResponse .= "</p><h2>New Price</h2><p><strong>" . formatCurrency($newprice) . "</strong></p>";
        } else {
            $criteriaResponse .= "</p><h2>Price Increase Amount</h2><p><strong>+ " . formatCurrency($increaseprice) . "</strong></p>";
        }
        $response = array("error" => $errorResponse, "review" => $criteriaResponse, "results" => $resultsResponse);
        header("Content-Type: application/json");
        echo json_encode($response);
        exit;
    }
    $jscode = "\nfunction loadStep(currentStep, nextStep, service) {\n    if (nextStep == 3) {\n        WHMCS.http.jqClient.post( \"" . $modulelink . "&validate=1\", \$(\"#frmUpdate\").serialize(),\n            function(data) {\n                if (data.error) {\n                    alert(data.error);\n                } else {\n                    \$(\"#bulkpricingupdater-review\").html(data.review);\n                    transitionStep(currentStep, nextStep);\n                }\n            }, \"json\");\n    } else {\n        transitionStep(currentStep, nextStep, service);\n    }\n}\n\nfunction transitionStep(currentStep, nextStep, service) {\n    \$(\".bulkpricingupdater-steps div\").removeClass(\"active-step\");\n    \$(\"#step\" + nextStep).addClass(\"active-step\");\n    if (service) {\n        \$(\"#criteria-services\").hide();\n        \$(\"#criteria-addons\").hide();\n        \$(\"#criteria-domains\").hide();\n        \$(\"#criteria-\" + service).show();\n        \$(\"#inputType\").val(service);\n    }\n    \$(\"#step-\" + currentStep).slideUp(\"slow\", function() {\n        \$(\"#bulkpricingupdater-results\").removeClass(\"bulkpricingupdater-results-error\");\n        \$(\"#bulkpricingupdater-results\").removeClass(\"bulkpricingupdater-results-success\");\n        \$(\"#bulkpricingupdater-results\").html(\"<img src=\\\"images/loader.gif\\\" /><br /><br />Processing... Please Wait...\");\n        \$(\"#step-\" + nextStep).slideDown(\"slow\", function() {\n            if (nextStep == 4) {\n                WHMCS.http.jqClient.post( \"" . $modulelink . "&doupdate=1\", \$(\"#frmUpdate\").serialize(),\n                    function(data) {\n                        if (data.results) {\n                            \$(\"#bulkpricingupdater-results\").html(data.results);\n                            \$(\"#bulkpricingupdater-results\").addClass(\"bulkpricingupdater-results-success\");\n                        } else {\n                            \$(\"#bulkpricingupdater-results\").html(data.error);\n                            \$(\"#bulkpricingupdater-results\").addClass(\"bulkpricingupdater-results-error\");\n                        }\n                    }, \"json\");\n            }\n        });\n    });\n}\n";
    global $aInt;
    $aInt->extrajscode[] = $jscode;
    echo "\n<link href=\"../modules/addons/bulkpricingupdater/style.css\" rel=\"stylesheet\" type=\"text/css\" />\n\n<p>This utility can be used to apply system wide price changes to existing clients' product, addon and domain services.</p>\n<p>This is necessary because by default, changing the pricing of products, addons and domain TLDs in WHMCS does not cascade to existing clients. That is, existing customers always remain at the prices they were shown and agreed to at the time of placing an order. The only exception to this is if you enable Product Prices Currency update setting under the Automation Settings.</p>\n\n<div class=\"bulkpricingupdater-steps\">\n    <div id=\"step1\" class=\"active-step\">Step 1<span>Choose Type</span></div>\n    <div id=\"step2\">Step 2<span>Set Criteria</span></div>\n    <div id=\"step3\">Step 3<span>Review</span></div>\n    <div id=\"step4\">Step 4<span>Peform Update</span></div>\n</div>\n\n<div style=\"clear:both;\"></div>\n\n<form method=\"post\" id=\"frmUpdate\">\n\n<div class=\"bulkpricingupdater-step-content\">\n\n    <div id=\"step-1\">\n\n        <h2>Choose Type</h2>\n\n        <p>Choose which type of product/service you would like to update pricing for.</p>\n\n        <p align=\"center\">\n            <input type=\"button\" value=\"Products/Services\" onclick=\"loadStep(1, '2', 'services')\" class=\"btn btn-default\" />\n            <input type=\"button\" value=\"Product Addons\" onclick=\"loadStep(1, '2', 'addons')\" class=\"btn btn-default\" />\n            <input type=\"button\" value=\"Domains\" onclick=\"loadStep(1, '2', 'domains')\" class=\"btn btn-default\" />\n        </p>\n\n    </div>\n    <div id=\"step-2\">\n\n        <input type=\"hidden\" name=\"type\" id=\"inputType\" value=\"\" />\n\n        <div id=\"criteria-services\">\n\n            <h2>Products/Services Criteria</h2>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"20%\" class=\"fieldlabel\">Product Name(s)</td><td class=\"fieldarea\"><select name=\"productids[]\" size=\"10\" multiple=\"true\" style=\"width:600px;\">";
    $result = select_query("tblproducts", "tblproducts.id,tblproducts.gid,tblproducts.name,tblproductgroups.name AS groupname", "", "tblproductgroups`.`order` ASC,`tblproducts`.`order` ASC,`name", "ASC", "", "tblproductgroups ON tblproducts.gid=tblproductgroups.id");
    while ($data = mysql_fetch_array($result)) {
        $pid = $data["id"];
        $pname = $data["name"];
        $ptype = $data["groupname"];
        echo "<option value=\"" . $pid . "\">" . $ptype . " - " . $pname . "</option>";
    }
    echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">Billing Cycle</td><td class=\"fieldarea\"><select name=\"prodcycles[]\" size=\"6\" multiple=\"true\">\n<option>Monthly</option>\n<option>Quarterly</option>\n<option>Semi-Annually</option>\n<option>Annually</option>\n<option>Biennially</option>\n<option>Triennially</option>\n</select></td></tr>\n<tr><td class=\"fieldlabel\">Status</td><td class=\"fieldarea\"><select name=\"prodstatus[]\" size=\"5\" multiple=\"true\">\n<option selected>Pending</option>\n<option selected>Active</option>\n<option selected>Completed</option>\n<option selected>Suspended</option>\n<option>Terminated</option>\n<option>Cancelled</option>\n<option>Fraud</option>\n</select></td></tr>\n<tr><td class=\"fieldlabel\">Current Price</td><td class=\"fieldarea\"><input type=\"text\" name=\"prodcurrentprice\" size=\"10\" value=\"\" /> (Optional)</td></tr>\n<tr><td class=\"fieldlabel\">Currency</td><td class=\"fieldarea\"><select name=\"prodcurrid\">";
    $result = select_query("tblcurrencies", "id,code", "", "code", "ASC");
    while ($data = mysql_fetch_array($result)) {
        echo "<option value=\"" . $data["id"] . "\"";
        if ($data["id"] == $currency) {
            echo " selected";
        }
        echo ">" . $data["code"] . "</option>";
    }
    echo "</select></td></tr>\n</table>\n\n        </div>\n        <div id=\"criteria-addons\">\n\n            <h2>Addons Criteria</h2>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"20%\" class=\"fieldlabel\">Addon Name(s)</td><td class=\"fieldarea\"><select name=\"addonids[]\" size=\"10\" multiple=\"true\" style=\"width:600px;\">";
    $result = select_query("tbladdons", "", "", "name", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $id = $data["id"];
        $name = $data["name"];
        $description = $data["description"];
        echo "<option value=\"" . $id . "\">" . $name . "</option>";
    }
    echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">Billing Cycle</td><td class=\"fieldarea\"><select name=\"addoncycles[]\" size=\"6\" multiple=\"true\">\n<option>Monthly</option>\n<option>Quarterly</option>\n<option>Semi-Annually</option>\n<option>Annually</option>\n<option>Biennially</option>\n<option>Triennially</option>\n</select></td></tr>\n<tr><td class=\"fieldlabel\">Status</td><td class=\"fieldarea\"><select name=\"addonstatus[]\" size=\"5\" multiple=\"true\">\n<option selected>Pending</option>\n<option selected>Active</option>\n<option selected>Completed</option>\n<option selected>Suspended</option>\n<option>Terminated</option>\n<option>Cancelled</option>\n<option>Fraud</option>\n</select></td></tr>\n<tr><td class=\"fieldlabel\">Current Price</td><td class=\"fieldarea\"><input type=\"text\" name=\"addoncurrentprice\" size=\"10\" value=\"\" /> (Optional)</td></tr>\n<tr><td class=\"fieldlabel\">Currency</td><td class=\"fieldarea\"><select name=\"addoncurrid\">";
    $result = select_query("tblcurrencies", "id,code", "", "code", "ASC");
    while ($data = mysql_fetch_array($result)) {
        echo "<option value=\"" . $data["id"] . "\"";
        if ($data["id"] == $currency) {
            echo " selected";
        }
        echo ">" . $data["code"] . "</option>";
    }
    echo "</select></td></tr>\n</table>\n\n        </div>\n        <div id=\"criteria-domains\">\n\n            <h2>Domains Criteria</h2>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n    <tr>\n        <td width=\"20%\" class=\"fieldlabel\">TLD(s)</td>\n        <td class=\"fieldarea\">";
    $result = select_query("tbldomainpricing", "DISTINCT extension", "", "order", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $tld = $data["extension"];
        echo "<label><input type=\"checkbox\" name=\"domaintlds[]\" value=\"" . $tld . "\"> " . $tld . "</label> ";
    }
    echo "</td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">Registration Period</td>\n        <td class=\"fieldarea\">\n            <select name=\"regperiod\">\n                ";
    for ($domainyears = 1; $domainyears <= 10; $domainyears++) {
        echo "<option value=\"" . $domainyears . "\">" . $domainyears . " Year" . (1 < $domainyears ? "s" : "") . "</option>";
    }
    echo "            </select>\n        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">Status</td>\n        <td class=\"fieldarea\">\n            <select name=\"domainstatus[]\" size=\"8\" multiple=\"multiple\">\n                ";
    echo (new WHMCS\Domain\Status())->translatedDropdownOptions(array("Pending", "Pending Registration", "Pending Transfer", "Active"));
    echo "            </select>\n        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">Domain Addons</td>\n        <td class=\"fieldarea\">\n            <label><input type=\"checkbox\" name=\"domainaddons[]\" value=\"dnsmanagement\"/> DNS Management</label>\n            <label><input type=\"checkbox\" name=\"domainaddons[]\" value=\"emailforwarding\"/> Email Forwarding</label>\n            <label><input type=\"checkbox\" name=\"domainaddons[]\" value=\"idprotection\"/> ID Protection</label>\n        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">Current Price</td>\n        <td class=\"fieldarea\"><input type=\"text\" name=\"domaincurrentprice\" size=\"10\" value=\"\"/> (Optional)</td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">Currency</td>\n        <td class=\"fieldarea\">\n            <select name=\"domaincurrid\">";
    $result = select_query("tblcurrencies", "id,code", "", "code", "ASC");
    while ($data = mysql_fetch_array($result)) {
        echo "<option value=\"" . $data["id"] . "\"";
        if ($data["id"] == $currency) {
            echo " selected";
        }
        echo ">" . $data["code"] . "</option>";
    }
    echo "            </select>\n        </td>\n    </tr>\n</table>\n\n        </div>\n\n        <p>Specify how the pricing is to be altered below.</p>\n\n        <table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n            <tr>\n                <td width=\"20%\" class=\"fieldlabel\">New Recurring Price</td>\n                <td class=\"fieldarea\"><input type=\"text\" name=\"newprice\" size=\"10\" value=\"0.00\" /> Set this to override current pricing with a new fixed value</td>\n            </tr>\n            <tr>\n                <td width=\"20%\" class=\"fieldlabel\">-OR- Increase Pricing By</td>\n                <td class=\"fieldarea\"><input type=\"text\" name=\"increaseprice\" size=\"10\" value=\"0.00\" /> Set this to increase the current pricing by a specified amount</td>\n            </tr>\n        </table>\n\n        <p align=\"center\"><input type=\"button\" value=\"&laquo; Choose a Different Type\" onclick=\"loadStep(2, 1)\" class=\"btn btn-default\" /> <input type=\"button\" value=\"Continue &raquo;\" class=\"btn btn-success\" onclick=\"loadStep(2, 3)\" /></p>\n\n    </div>\n    <div id=\"step-3\">\n\n        <h2>Review</h2>\n\n        <div id=\"bulkpricingupdater-review\"></div>\n\n        <p align=\"center\"><input type=\"button\" value=\"&laquo; Edit Criteria\" class=\"btn\" onclick=\"loadStep(3, 2)\" class=\"btn btn-default\" /> <input type=\"button\" value=\"Perform Update &raquo;\" class=\"btn btn-danger\" onclick=\"loadStep(3, 4)\" /><br />Warning: This action cannot be undone.</p>\n\n    </div>\n    <div id=\"step-4\">\n\n        <h2>Perform Update</h2>\n\n        <p>The results of the pricing update will be displayed below.</p>\n\n        <div id=\"bulkpricingupdater-results\">\n            <img src=\"images/loader.gif\" /><br /><br />Processing... Please Wait...\n        </div>\n\n        <p align=\"center\"><input type=\"button\" value=\"&laquo; Edit Criteria and Run Again\" onclick=\"loadStep(4, 2)\" class=\"btn btn-default\" /> <input type=\"button\" value=\"Start Over\" class=\"btn btn-success\" onclick=\"loadStep(4, 1)\" /></p>\n\n    </div>\n\n</div>\n\n</form>\n\n";
}

?>