<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Create Upgrade/Downgrade Orders", false);
$aInt->title = $aInt->lang("services", "upgradedowngrade");
$aInt->requiredFiles(array("orderfunctions", "upgradefunctions", "invoicefunctions", "configoptionsfunctions"));
ob_start();
$result = select_query("tblhosting", "tblhosting.userid,tblhosting.domain,tblhosting.billingcycle,tblhosting.nextduedate,tblhosting.paymentmethod,tblproducts.id AS pid,tblproducts.name,tblproductgroups.name as groupname", array("tblhosting.id" => $id), "", "", "", "tblproducts ON tblproducts.id=tblhosting.packageid INNER JOIN tblproductgroups ON tblproductgroups.id=tblproducts.gid");
$data = mysql_fetch_array($result);
$userid = $data["userid"];
$service_groupname = $data["groupname"];
$service_pid = $data["pid"];
$service_prodname = $data["name"];
$service_domain = $data["domain"];
$service_billingcycle = $data["billingcycle"];
$service_nextduedate = $data["nextduedate"];
$service_paymentmethod = $data["paymentmethod"];
if (!$userid) {
    exit($aInt->lang("global", "erroroccurred"));
}
$service_nextduedate = str_replace("-", "", $service_nextduedate);
if ($service_billingcycle != "Free Account" && $service_billingcycle != "One Time" && $service_nextduedate < date("Ymd")) {
    infoBox($aInt->lang("services", "upgradeoverdue"), $aInt->lang("services", "upgradeoverdueinfo"), "error");
    echo $infobox;
    $content = ob_get_contents();
    ob_end_clean();
    $aInt->content = $content;
    $aInt->displayPopUp();
    exit;
}
if (upgradeAlreadyInProgress($id)) {
    $orders = get_query_val("tblupgrades", "orderid", array("status" => "Pending", "relid" => $id));
    infoBox($aInt->lang("services", "upgradealreadyinprogress"), $aInt->lang("services", "upgradealreadyinprogressinfo") . " <a href='#' id='viewOrder'>" . $aInt->lang("orders", "vieworder") . "</a>", "error");
    echo $infobox;
    echo "<script>\n            \$('a#viewOrder').click(function() {\n                window.opener.location.href = 'orders.php?action=view&id=" . $orders . "';\n                window.close();\n            });\n        </script>";
    $content = ob_get_contents();
    ob_end_clean();
    $aInt->content = $content;
    $aInt->displayPopUp();
    exit;
}
$currency = getCurrency($userid);
if ($action == "getcycles") {
    check_token("WHMCS.admin.default");
    ajax_getcycles($pid);
    exit;
}
if ($action == "calcsummary") {
    check_token("WHMCS.admin.default");
    try {
        $_SESSION["uid"] = $userid;
        if ($type == "product") {
            $upgrades = SumUpPackageUpgradeOrder($id, $newproductid, $billingcycle, $promocode, $service_paymentmethod, false);
            $upgrades = $upgrades[0];
            $subtotal = $GLOBALS["subtotal"];
            $qualifies = $GLOBALS["qualifies"];
            $discount = $GLOBALS["discount"];
            $total = formatCurrency($subtotal - $discount);
            echo $aInt->lang("services", "daysleft") . ": " . $upgrades["daysuntilrenewal"] . " / " . $upgrades["totaldays"] . "<br />";
            if (0 < $discount) {
                echo $aInt->lang("fields", "discount") . ": " . formatCurrency($GLOBALS["discount"]) . "<br />";
            }
            echo $aInt->lang("services", "upgradedue") . ": <span style=\"font-size:16px;\">" . $total . "</span>";
        } else {
            if ($type == "configoptions") {
                $upgrades = SumUpPackageUpgradeOrder($id, $service_pid, $service_billingcycle, $promocode, $service_paymentmethod, false);
                $upgrades = $upgrades[0];
                echo $aInt->lang("services", "daysleft") . ": " . $upgrades["daysuntilrenewal"] . " / " . $upgrades["totaldays"] . "<br />";
                $upgrades = SumUpConfigOptionsOrder($id, $configoption, $promocode, $service_paymentmethod, false);
                $subtotal = $GLOBALS["subtotal"];
                $qualifies = $GLOBALS["qualifies"];
                $discount = $GLOBALS["discount"];
                $total = formatCurrency($subtotal - $discount);
                foreach ($upgrades as $upgrade) {
                    echo $upgrade["configname"] . ": " . $upgrade["originalvalue"] . " => " . $upgrade["newvalue"] . " (" . $upgrade["price"] . ")<br />";
                }
                if (0 < $discount) {
                    echo $aInt->lang("fields", "discount") . ": " . formatCurrency($GLOBALS["discount"]) . "<br />";
                }
                echo $aInt->lang("services", "upgradedue") . ": <span style=\"font-size:16px;\">" . $total . "</span>";
            }
        }
    } catch (Exception $e) {
        echo Lang::trans("error") . ": " . $e->getMessage();
    }
    unset($_SESSION["uid"]);
    exit;
}
if ($action == "order") {
    check_token("WHMCS.admin.default");
    $_SESSION["uid"] = $userid;
    if ($type == "product") {
        $upgrades = SumUpPackageUpgradeOrder($id, $newproductid, $billingcycle, $promocode, $service_paymentmethod, true);
    } else {
        if ($type == "configoptions") {
            $upgrades = SumUpConfigOptionsOrder($id, $configoption, $promocode, $service_paymentmethod, true);
        }
    }
    $upgradedata = createUpgradeOrder($id, "", $promocode, $service_paymentmethod);
    $orderid = $upgradedata["orderid"];
    unset($_SESSION["uid"]);
    echo "<script language=\"javascript\">\nwindow.opener.location.href = \"orders.php?action=view&id=";
    echo $orderid;
    echo "\";\nwindow.close();\n</script>\n";
    exit;
}
if (!$action) {
    if (!$type) {
        $type = "product";
    }
    $configoptions = getCartConfigOptions($service_pid, "", $service_billingcycle, $id);
    echo "\n<p><strong>";
    echo $aInt->lang("services", "related");
    echo ":</strong> ";
    echo $service_groupname . " - " . $service_prodname;
    if ($service_domain) {
        echo " (" . $service_domain . ")";
    }
    echo "</p>\n\n<script language=\"javascript\">\n\$(document).ready(function(){\n\n    calctotals();\n\n    \$(\"#newpid\").change(function () {\n        var newpid = \$(\"#newpid option:selected\").val();\n        WHMCS.http.jqClient.post(\"clientsupgrade.php\", { action: \"getcycles\", id: ";
    echo $id;
    echo ", pid: newpid, token: \"";
    echo generate_token("plain");
    echo "\" },\n        function(data){\n            \$(\"#billingcyclehtml\").html(data);\n            calctotals();\n        });\n    });\n\n});\n\nfunction calctotals() {\n    WHMCS.http.jqClient.post(\"clientsupgrade.php\", \"action=calcsummary&\"+\$(\"#upgradefrm\").serialize(),\n    function(data){\n        if (data) \$(\"#upgradesummary\").html(data);\n        else \$(\"#upgradesummary\").html(\"";
    echo $aInt->lang("services", "nochanges");
    echo "\");\n    });\n}\n</script>\n\n<form method=\"post\" action=\"";
    echo $_SERVER["PHP_SELF"];
    echo "?action=order\" id=\"upgradefrm\">\n<input type=\"hidden\" name=\"id\" value=\"";
    echo $id;
    echo "\" />\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\" width=\"25%\">";
    echo $aInt->lang("services", "upgradetype");
    echo "</td><td class=\"fieldarea\"><input type=\"radio\" name=\"type\" value=\"product\" id=\"typeproduct\" onclick=\"window.location='";
    echo $_SERVER["PHP_SELF"] . "?id=" . $id;
    echo "'\"";
    if ($type == "product") {
        echo " checked";
    }
    echo " /> <label for=\"typeproduct\">";
    echo $aInt->lang("services", "productcycle");
    echo "</label>";
    if (count($configoptions)) {
        echo " <input type=\"radio\" name=\"type\" value=\"configoptions\" id=\"typeconfigoptions\" onclick=\"window.location='";
        echo $_SERVER["PHP_SELF"] . "?id=" . $id . "&type=configoptions";
        echo "'\"";
        if ($type == "configoptions") {
            echo " checked";
        }
        echo " /> <label for=\"typeconfigoptions\">";
        echo $aInt->lang("setup", "configoptions");
        echo "</label>";
    }
    echo "</td></tr>\n";
    if ($type == "product") {
        echo "<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("services", "newservice");
        echo "</td><td class=\"fieldarea\"><select name=\"newproductid\" id=\"newpid\">";
        echo $aInt->productDropDown($service_pid);
        echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "billingcycle");
        echo "</td><td class=\"fieldarea\" id=\"billingcyclehtml\">";
        ajax_getcycles($service_pid);
        echo "</td></tr>\n";
    } else {
        if ($type == "configoptions") {
            foreach ($configoptions as $configoption) {
                $optionid = $configoption["id"];
                $optionhidden = $configoption["hidden"];
                $optionname = $optionhidden ? $configoption["optionname"] . " <i>(" . $aInt->lang("fields", "hidden") . ")</i>" : $configoption["optionname"];
                $optiontype = $configoption["optiontype"];
                $selectedvalue = $configoption["selectedvalue"];
                $selectedqty = $configoption["selectedqty"];
                echo "<tr><td class=\"fieldlabel\">" . $optionname . "</td><td class=\"fieldarea\">";
                if ($optiontype == "1") {
                    echo "<select name=\"configoption[" . $optionid . "]\" onchange=\"calctotals()\">";
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
                            echo "<input type=\"radio\" name=\"configoption[" . $optionid . "]\" value=\"" . $option["id"] . "\" onclick=\"calctotals()\"";
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
                            echo "<input type=\"checkbox\" name=\"configoption[" . $optionid . "]\" value=\"1\" onclick=\"calctotals()\"";
                            if ($selectedqty) {
                                echo " checked";
                            }
                            echo "> " . $configoption["options"][0]["name"];
                        } else {
                            if ($optiontype == "4") {
                                echo "<input type=\"text\" name=\"configoption[" . $optionid . "]\" value=\"" . $selectedqty . "\" size=\"5\" onkeyup=\"calctotals()\"> x " . $configoption["options"][0]["name"];
                            }
                        }
                    }
                }
                echo "</td></tr>";
            }
        }
    }
    echo "<tr><td class=\"fieldlabel\">";
    echo $aInt->lang("fields", "promocode");
    echo "</td><td class=\"fieldarea\"><select name=\"promocode\" id=\"promocode\" onchange=\"calctotals()\"><option value=\"\">";
    echo $aInt->lang("global", "none");
    echo "</option>";
    $result = select_query("tblpromotions", "", array("upgrades" => "1"), "code", "ASC");
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
        echo "<option value=\"" . $promo_code . "\"";
        if ($promo_id == $promoid) {
            echo " selected";
        }
        echo ">" . $promo_code . " - " . $promo_value . " " . $promo_recurring . "</option>";
    }
    echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
    echo $aInt->lang("services", "upgradesummary");
    echo "</td><td class=\"fieldarea\" id=\"upgradesummary\">";
    echo $aInt->lang("services", "upgradesummaryinfo");
    echo "</td></tr>\n</table>\n\n<p align=\"center\"><input type=\"submit\" value=\"";
    echo $aInt->lang("orders", "createorder");
    echo "\" /></p>\n\n</form>\n\n<p align=\"center\"><input type=\"button\" value=\"";
    echo $aInt->lang("addons", "closewindow");
    echo "\" onClick=\"window.close()\" class=\"button btn btn-default\"></p>\n\n";
}
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->displayPopUp();
function ajax_getcycles($pid)
{
    global $aInt;
    global $service_billingcycle;
    $pricing = getPricingInfo($pid);
    if ($pricing["type"] == "recurring") {
        echo "<select name=\"billingcycle\" onchange=\"calctotals()\">";
        if ($pricing["monthly"]) {
            echo "<option value=\"monthly\"";
            if ($service_billingcycle == "Monthly") {
                echo " selected";
            }
            echo ">" . $pricing["monthly"] . "</option>";
        }
        if ($pricing["quarterly"]) {
            echo "<option value=\"quarterly\"";
        }
        if ($service_billingcycle == "Quarterly") {
            echo " selected";
        }
        echo ">" . $pricing["quarterly"] . "</option>";
        if ($pricing["semiannually"]) {
            echo "<option value=\"semiannually\"";
        }
        if ($service_billingcycle == "Semi-Annually") {
            echo " selected";
        }
        echo ">" . $pricing["semiannually"] . "</option>";
        if ($pricing["annually"]) {
            echo "<option value=\"annually\"";
        }
        if ($service_billingcycle == "Annually") {
            echo " selected";
        }
        echo ">" . $pricing["annually"] . "</option>";
        if ($pricing["biennially"]) {
            echo "<option value=\"biennially\"";
        }
        if ($service_billingcycle == "Biennially") {
            echo " selected";
        }
        echo ">" . $pricing["biennially"] . "</option>";
        if ($pricing["triennially"]) {
            echo "<option value=\"triennially\"";
        }
        if ($service_billingcycle == "Triennially") {
            echo " selected";
        }
        echo ">" . $pricing["triennially"] . "</option>";
        echo "</select>";
    } else {
        if ($pricing["type"] == "onetime") {
            echo "<input type=\"hidden\" name=\"billingcycle\" value=\"onetime\" /> " . $aInt->lang("billingcycles", "onetime");
        } else {
            echo "<input type=\"hidden\" name=\"billingcycle\" value=\"free\" /> " . $aInt->lang("billingcycles", "free");
        }
    }
}

?>