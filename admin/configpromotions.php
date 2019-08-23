<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("View Promotions");
$aInt->title = $aInt->lang("promos", "title");
$aInt->sidebar = "config";
$aInt->icon = "autosettings";
$aInt->helplink = "Promotions";
if ($action == "genpromo") {
    $numbers = "0123456789";
    $uppercase = "ABCDEFGHIJKLMNOPQRSTUVYWXYZ";
    $str = "";
    $seeds_count = strlen($numbers) - 1;
    for ($i = 0; $i < 4; $i++) {
        $str .= $numbers[rand(0, $seeds_count)];
    }
    $seeds_count = strlen($uppercase) - 1;
    for ($i = 0; $i < 8; $i++) {
        $str .= $uppercase[rand(0, $seeds_count)];
    }
    $password = "";
    for ($i = 0; $i < 10; $i++) {
        $randomnum = rand(0, strlen($str) - 1);
        $password .= $str[$randomnum];
        $str = substr($str, 0, $randomnum) . substr($str, $randomnum + 1);
    }
    echo $password;
    exit;
}
if ($action == "save") {
    check_token("WHMCS.admin.default");
    checkPermission("Create/Edit Promotions");
    $id = (int) $whmcs->get_req_var("id");
    $code = trim($whmcs->get_req_var("code"));
    $type = $whmcs->get_req_var("type");
    $recurring = (int) $whmcs->get_req_var("recurring");
    $pvalue = $whmcs->get_req_var("pvalue");
    $requiresexisting = (int) $whmcs->get_req_var("requiresexisting");
    $startdate = $whmcs->get_req_var("startdate");
    $expirationdate = $whmcs->get_req_var("expirationdate");
    $maxuses = (int) $whmcs->get_req_var("maxuses");
    $lifetimepromo = (int) $whmcs->get_req_var("lifetimepromo");
    $applyonce = (int) $whmcs->get_req_var("applyonce");
    $newsignups = (int) $whmcs->get_req_var("newsignups");
    $existingclient = (int) $whmcs->get_req_var("existingclient");
    $onceperclient = (int) $whmcs->get_req_var("onceperclient");
    $recurfor = (int) $whmcs->get_req_var("recurfor");
    $cycles = $whmcs->get_req_var("cycles");
    $appliesto = $whmcs->get_req_var("appliesto");
    $requires = $whmcs->get_req_var("requires");
    $upgrades = (int) $whmcs->get_req_var("upgrades");
    $upgradevalue = $whmcs->get_req_var("upgradevalue");
    $upgradetype = $whmcs->get_req_var("upgradetype");
    $upgradediscounttype = $whmcs->get_req_var("upgradediscounttype");
    $configoptionupgrades = $whmcs->get_req_var("configoptionupgrades");
    $notes = $whmcs->get_req_var("notes");
    $startdate = !$startdate ? "0000-00-00" : toMySQLDate($startdate);
    $expirationdate = !$expirationdate ? "0000-00-00" : toMySQLDate($expirationdate);
    $cycles = is_array($cycles) ? implode(",", $cycles) : "";
    $appliesto = is_array($appliesto) ? implode(",", $appliesto) : "";
    $requires = is_array($requires) ? implode(",", $requires) : "";
    $upgradeconfig = safe_serialize(array("value" => format_as_currency($upgradevalue), "type" => $upgradetype, "discounttype" => $upgradediscounttype, "configoptions" => $configoptionupgrades));
    if ($id) {
        $promotion = Illuminate\Database\Capsule\Manager::table("tblpromotions")->find($id);
        if ($code != $promotion->code) {
            logAdminActivity("Promotion Modified: Code Modified: '" . $promotion->code . "' to '" . $code . "' - Promotion ID: " . $newid);
        }
        $changes = array();
        if ($type != $promotion->type) {
            $changes[] = "Type Changed: '" . $promotion->type . "' to '" . $type . "'";
        }
        if ($recurring != $promotion->recurring) {
            if ($recurring) {
                $changes[] = "Recurring Enabled";
            } else {
                $changes[] = "Recurring Disabled";
            }
        }
        if ($recurfor != $promotion->recurfor) {
            $changes[] = "Recur For Modified: '" . $promotion->recurfor . "' to '" . $recurfor . "'";
        }
        if ($pvalue != $promotion->value) {
            $changes[] = "Value Modified: '" . $promotion->value . "' to '" . $pvalue . "'";
        }
        if ($appliesto != $promotion->appliesto) {
            $changes[] = "Applies To Modified";
        }
        if ($requires != $promotion->requires) {
            $changes[] = "Requires Modified";
        }
        if ($requiresexisting != $promotion->requiresexisting) {
            if ($requiresexisting) {
                $changes[] = "Requires Existing Product Allowed In Account Enabled";
            } else {
                $changes[] = "Requires Existing Product Allowed In Account Disabled";
            }
        }
        if ($cycles != $promotion->cycles) {
            $changes[] = "Cycles Modified";
        }
        if ($startdate != $promotion->startdate) {
            $changes[] = "Start Date Modified: '" . $promotion->startdate . "' to '" . $startdate . "'";
        }
        if ($expirationdate != $promotion->expirationdate) {
            $changes[] = "Expiry Date Modified: '" . $promotion->expirationdate . "' to '" . $expirationdate . "'";
        }
        if ($maxuses != $promotion->maxuses) {
            $changes[] = "Max Uses Modified: '" . $promotion->maxuses . "' to '" . $maxuses . "'";
        }
        if ($lifetimepromo != $promotion->lifetimepromo) {
            if ($lifetimepromo) {
                $changes[] = "Lifetime Promotion Enabled";
            } else {
                $changes[] = "Lifetime Promotion Disabled";
            }
        }
        if ($applyonce != $promotion->applyonce) {
            if ($applyonce) {
                $changes[] = "Apply Once Enabled";
            } else {
                $changes[] = "Apply Once Disabled";
            }
        }
        if ($newsignups != $promotion->newsignups) {
            if ($newsignups) {
                $changes[] = "New Signups Only Enabled";
            } else {
                $changes[] = "New Signups Only Disabled";
            }
        }
        if ($onceperclient != $promotion->onceperclient) {
            if ($onceperclient) {
                $changes[] = "Once Per Client Enabled";
            } else {
                $changes[] = "Once Per Client Disabled";
            }
        }
        if ($existingclient != $promotion->existingclient) {
            if ($existingclient) {
                $changes[] = "Existing Client Only Enabled";
            } else {
                $changes[] = "Existing Client Only Disabled";
            }
        }
        if ($upgrades != $promotion->upgrades) {
            if ($upgrades) {
                $changes[] = "Product Upgrade Promotion Enabled";
            } else {
                $changes[] = "Product Upgrade Promotion Disabled";
            }
        }
        if ($upgradeconfig != $promotion->upgradeconfig) {
            $changes[] = "Upgrade Promotion Configuration Modified";
        }
        if ($notes != $promotion->notes) {
            $changes[] = "Admin Notes Modified";
        }
        update_query("tblpromotions", array("code" => $code, "type" => $type, "recurring" => $recurring, "value" => $pvalue, "cycles" => $cycles, "appliesto" => $appliesto, "requires" => $requires, "requiresexisting" => $requiresexisting, "startdate" => $startdate, "expirationdate" => $expirationdate, "maxuses" => $maxuses, "lifetimepromo" => $lifetimepromo, "applyonce" => $applyonce, "newsignups" => $newsignups, "existingclient" => $existingclient, "onceperclient" => $onceperclient, "recurfor" => $recurfor, "upgrades" => $upgrades, "upgradeconfig" => $upgradeconfig, "notes" => $notes), array("id" => $id));
        if ($changes) {
            logAdminActivity("Promotion Modified: '" . $code . "' - Changes: " . implode(". ", $changes) . " - Promotion ID: " . $id);
        }
        redir("updated=true");
    } else {
        $result = select_query("tblpromotions", "COUNT(*)", array("code" => $code));
        $data = mysql_fetch_array($result);
        $duplicates = $data[0];
        $newid = insert_query("tblpromotions", array("code" => $code, "type" => $type, "recurring" => $recurring, "value" => $pvalue, "cycles" => $cycles, "appliesto" => $appliesto, "requires" => $requires, "requiresexisting" => $requiresexisting, "startdate" => $startdate, "expirationdate" => $expirationdate, "maxuses" => $maxuses, "lifetimepromo" => $lifetimepromo, "applyonce" => $applyonce, "newsignups" => $newsignups, "existingclient" => $existingclient, "onceperclient" => $onceperclient, "recurfor" => $recurfor, "upgrades" => $upgrades, "upgradeconfig" => $upgradeconfig, "notes" => $notes));
        logAdminActivity("Promotion Created: '" . $code . "' - Promotion ID: " . $newid);
        if ($duplicates) {
            redir("action=manage&id=" . $newid);
        } else {
            redir("created=true");
        }
    }
}
if ($action == "delete") {
    check_token("WHMCS.admin.default");
    checkPermission("Delete Promotions");
    $id = (int) $whmcs->get_req_var("id");
    $promotion = Illuminate\Database\Capsule\Manager::table("tblpromotions")->find($id);
    logAdminActivity("Promotion Deleted: '" . $promotion->code . "' - Promotion ID: " . $id);
    delete_query("tblpromotions", array("id" => $id));
    redir("deleted=true");
}
$expire = (int) $whmcs->get_req_var("expire");
if ($expire) {
    check_token("WHMCS.admin.default");
    checkPermission("Create/Edit Promotions");
    update_query("tblpromotions", array("expirationdate" => date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 1, date("Y")))), array("id" => $expire));
    $promotion = Illuminate\Database\Capsule\Manager::table("tblpromotions")->find($expire);
    logAdminActivity("Promotion Expired: '" . $promotion->code . "' - Promotion ID: " . $expire);
    redir("expired=true");
}
ob_start();
if (!$action) {
    $aInt->deleteJSConfirm("doDelete", "promos", "deletesure", "?action=delete&id=");
    if ($deleted) {
        infoBox($aInt->lang("global", "success"), $aInt->lang("promos", "deletesuccess"));
    }
    if ($updated) {
        infoBox($aInt->lang("global", "success"), $aInt->lang("global", "changesuccess"));
    }
    if ($created) {
        infoBox($aInt->lang("global", "success"), $aInt->lang("promos", "addsuccess"));
    }
    if ($expired) {
        infoBox($aInt->lang("global", "success"), $aInt->lang("promos", "expiresuccess"));
    }
    echo $infobox;
    echo "\n<p>\n    <div class=\"pull-right btn-group\" role=\"group\">\n        <a href=\"configpromotions.php\" class=\"btn btn-default";
    if ($view == "") {
        echo " active";
    }
    echo "\">";
    echo $aInt->lang("promos", "activepromos");
    echo "</a>\n        <a href=\"configpromotions.php?view=expired\" class=\"btn btn-default";
    if ($view == "expired") {
        echo " active";
    }
    echo "\">";
    echo $aInt->lang("promos", "expiredpromos");
    echo "</a>\n        <a href=\"configpromotions.php?view=all\" class=\"btn btn-default";
    if ($view == "all") {
        echo " active";
    }
    echo "\">";
    echo $aInt->lang("promos", "allpromos");
    echo "</a>\n    </div>\n    <a href=\"";
    echo $whmcs->getPhpSelf();
    echo "?action=manage\" class=\"btn btn-default\"><i class=\"fas fa-plus\"></i> ";
    echo $aInt->lang("promos", "createpromo");
    echo "</a>\n</p>\n\n";
    $aInt->sortableTableInit("code", "ASC");
    if ($view == "all") {
        $where = "";
    } else {
        if ($view == "expired") {
            $where = "(maxuses>0 AND uses>=maxuses) OR (expirationdate!='0000-00-00' AND expirationdate<'" . date("Ymd") . "')";
        } else {
            $where = "(maxuses<=0 OR uses<maxuses) AND (expirationdate='0000-00-00' OR expirationdate>='" . date("Ymd") . "')";
        }
    }
    $result = select_query("tblpromotions", "COUNT(*)", $where);
    $data = mysql_fetch_array($result);
    $numrows = $data[0];
    $result = select_query("tblpromotions", "", $where, "code", "ASC", $page * $limit . "," . $limit);
    while ($data = mysql_fetch_array($result)) {
        $pid = $data["id"];
        $code = $data["code"];
        $type = $data["type"];
        $recurring = $data["recurring"];
        $value = $data["value"];
        $uses = $data["uses"];
        $maxuses = $data["maxuses"];
        $startdate = $data["startdate"];
        $expirationdate = $data["expirationdate"];
        $notes = $data["notes"];
        if (0 < $maxuses && $maxuses <= $uses) {
            $uses = "<b>" . $uses;
        }
        if (0 < $maxuses) {
            $uses .= "/" . $maxuses;
        }
        $recurring = $recurring ? "<img src=\"images/icons/tick.png\" width=\"16\" height=\"16\" alt=\"Yes\" />" : "";
        $startdate = $startdate == "0000-00-00" ? "-" : fromMySQLDate($startdate);
        $expirationdate = $expirationdate == "0000-00-00" ? "-" : fromMySQLDate($expirationdate);
        if ($notes) {
            $code = "<a title=\"" . $aInt->lang("fields", "notes") . ": " . $notes . "\">" . $code . "</a>";
        }
        if ($type == "Percentage") {
            $type = $aInt->lang("promos", "percentage");
        } else {
            if ($type == "Fixed Amount") {
                $type = $aInt->lang("promos", "fixedamount");
            } else {
                if ($type == "Free Setup") {
                    $type = $aInt->lang("promos", "freesetup");
                }
            }
        }
        $tabledata[] = array($code, $type, $value, $recurring, $uses, $startdate, $expirationdate, "<a href=\"?action=manage&duplicate=" . $pid . "\"><img src=\"images/icons/add.png\" border=\"0\" align=\"absmiddle\" /> " . $aInt->lang("promos", "duplicatepromo") . "</a>", "<a href=\"?expire=" . $pid . generate_token("link") . "\"><img src=\"images/icons/expire.png\" border=\"0\" align=\"absmiddle\" /> " . $aInt->lang("promos", "expirenow") . "</a>", "<a href=\"?action=manage&id=" . $pid . "\"><img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "edit") . "\"></a>", "<a href=\"#\" onClick=\"doDelete('" . $pid . "');return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\"></a>");
    }
    echo $aInt->sortableTable(array($aInt->lang("fields", "promocode"), $aInt->lang("fields", "type"), $aInt->lang("promos", "value"), $aInt->lang("promos", "recurring"), $aInt->lang("promos", "uses"), $aInt->lang("fields", "startdate"), $aInt->lang("fields", "expirydate"), "&nbsp;", "&nbsp;", "", ""), $tabledata);
} else {
    if ($action == "duplicate") {
        checkPermission("Create/Edit Promotions");
        echo "\n<p><b>";
        echo $aInt->lang("promos", "duplicatepromo");
        echo "</b></p>\n\n<form method=\"get\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "\">\n<input type=\"hidden\" name=\"action\" value=\"manage\" />\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"15%\" class=\"fieldlabel\">";
        echo $aInt->lang("promos", "existingpromo");
        echo "</td><td class=\"fieldarea\"><select name=\"duplicate\">";
        $query = "SELECT * FROM tblpromotions ORDER BY code ASC";
        $result = full_query($query);
        while ($data = mysql_fetch_array($result)) {
            $promoid = $data["id"];
            $promoname = $data["code"];
            echo "<option value=\"" . $promoid . "\">" . $promoname;
        }
        echo "</select></td></tr>\n</table>\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
        echo $aInt->lang("global", "continue");
        echo " &raquo;\" class=\"btn btn-primary\">\n</div>\n</form>\n\n";
    } else {
        if ($action == "manage") {
            if ($id) {
                $result = select_query("tblpromotions", "", array("id" => $id));
                $data = mysql_fetch_array($result);
                $code = $data["code"];
                $type = $data["type"];
                $recurring = $data["recurring"];
                $value = $data["value"];
                $cycles = $data["cycles"];
                $appliesto = $data["appliesto"];
                $requires = $data["requires"];
                $requiresexisting = $data["requiresexisting"];
                $startdate = $data["startdate"];
                $expirationdate = $data["expirationdate"];
                $maxuses = $data["maxuses"];
                $uses = $data["uses"];
                $lifetimepromo = $data["lifetimepromo"];
                $applyonce = $data["applyonce"];
                $newsignups = $data["newsignups"];
                $existingclient = $data["existingclient"];
                $onceperclient = $data["onceperclient"];
                $recurfor = $data["recurfor"];
                $upgrades = $data["upgrades"];
                $upgradeconfig = $data["upgradeconfig"];
                $notes = $data["notes"];
                $startdate = $startdate == "0000-00-00" ? "" : fromMySQLDate($startdate);
                $expirationdate = $expirationdate == "0000-00-00" ? "" : fromMySQLDate($expirationdate);
                $cycles = explode(",", $cycles);
                $appliesto = explode(",", $appliesto);
                $requires = explode(",", $requires);
                $upgradeconfig = safe_unserialize($upgradeconfig);
                $managetitle = $aInt->lang("promos", "editpromo");
                $result = select_query("tblpromotions", "COUNT(*)", array("code" => $code));
                $data = mysql_fetch_array($result);
                $duplicates = $data[0];
            } else {
                if ($duplicate) {
                    checkPermission("Create/Edit Promotions");
                    $result = select_query("tblpromotions", "", array("id" => $duplicate));
                    $data = mysql_fetch_array($result);
                    $code = "";
                    $type = $data["type"];
                    $recurring = $data["recurring"];
                    $value = $data["value"];
                    $cycles = $data["cycles"];
                    $appliesto = $data["appliesto"];
                    $requires = $data["requires"];
                    $requiresexisting = $data["requiresexisting"];
                    $startdate = $data["startdate"];
                    $expirationdate = $data["expirationdate"];
                    $maxuses = $data["maxuses"];
                    $uses = 0;
                    $lifetimepromo = $data["lifetimepromo"];
                    $applyonce = $data["applyonce"];
                    $newsignups = $data["newsignups"];
                    $existingclient = $data["existingclient"];
                    $onceperclient = $data["onceperclient"];
                    $recurfor = $data["recurfor"];
                    $upgrades = $data["upgrades"];
                    $upgradeconfig = $data["upgradeconfig"];
                    $notes = $data["notes"];
                    $startdate = $startdate == "0000-00-00" ? "" : fromMySQLDate($startdate);
                    $expirationdate = $expirationdate == "0000-00-00" ? "" : fromMySQLDate($expirationdate);
                    $cycles = explode(",", $cycles);
                    $appliesto = explode(",", $appliesto);
                    $requires = explode(",", $requires);
                    $upgradeconfig = safe_unserialize($upgradeconfig);
                    $managetitle = $aInt->lang("promos", "duplicatepromo");
                } else {
                    checkPermission("Create/Edit Promotions");
                    $managetitle = $aInt->lang("promos", "createpromo");
                    $appliesto = array();
                    $requires = array();
                    $cycles = array();
                    $value = "";
                    $recurfor = "0";
                    $duplicates = 0;
                }
            }
            echo "<p><b>" . $managetitle . "</b></p>";
            if (1 < $duplicates) {
                infoBox($aInt->lang("promos", "duplicate"), $aInt->lang("promos", "duplicateinfo"));
                echo $infobox;
            }
            $jscode = "function autoGenPromo() {\n    WHMCS.http.jqClient.post(\"configpromotions.php\", \"action=genpromo\", function(data) {\n        \$(\"#promocode\").val(data);\n    });\n}";
            echo "\n<form method=\"post\" action=\"";
            echo $whmcs->getPhpSelf();
            echo "?action=save&id=";
            echo $id;
            echo "\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\" width=\"15%\">";
            echo $aInt->lang("fields", "promocode");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"code\" value=\"";
            echo $code;
            echo "\" id=\"promocode\" class=\"form-control input-250 input-inline\" /> <input type=\"button\" value=\"";
            echo $aInt->lang("promos", "autogencode");
            echo "\" class=\"btn btn-success btn-sm\" onclick=\"autoGenPromo()\" /></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("fields", "type");
            echo "</td><td class=\"fieldarea\"><select name=\"type\" class=\"form-control select-inline\">\n<option value=\"Percentage\"";
            if ($type == "Percentage") {
                echo " selected";
            }
            echo ">";
            echo $aInt->lang("promos", "percentage");
            echo "</option>\n<option value=\"Fixed Amount\"";
            if ($type == "Fixed Amount") {
                echo " selected";
            }
            echo ">";
            echo $aInt->lang("promos", "fixedamount");
            echo "</option>\n<option value=\"Price Override\"";
            if ($type == "Price Override") {
                echo " selected";
            }
            echo ">";
            echo $aInt->lang("promos", "priceoverride");
            echo "</option>\n<option value=\"Free Setup\"";
            if ($type == "Free Setup") {
                echo " selected";
            }
            echo ">";
            echo $aInt->lang("promos", "freesetup");
            echo "</option>\n</select></td></tr>\n<tr>\n    <td class=\"fieldlabel\">";
            echo $aInt->lang("promos", "recurring");
            echo "</td>\n    <td class=\"fieldarea\">\n        <label class=\"checkbox-inline\">\n            <input type=\"checkbox\" name=\"recurring\" value=\"1\"";
            if ($recurring) {
                echo " checked";
            }
            echo " onclick=\"\$('input#recurfor').prop('disabled', !\$('input#recurfor').prop('disabled'));\">\n            ";
            echo $aInt->lang("promos", "recurenable");
            echo "        </label>\n        <input id=\"recurfor\" type=\"text\" name=\"recurfor\" value=\"";
            echo $recurfor;
            echo "\" class=\"form-control input-50 input-inline\"";
            echo !$recurring ? " disabled=\"disabled\"" : "";
            echo " />\n        ";
            echo $aInt->lang("promos", "recurenable2");
            echo "    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("promos", "value");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"pvalue\" value=\"";
            echo $value;
            echo "\" placeholder=\"0.00\" class=\"form-control input-150 input-inline\"></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("promos", "appliesto");
            echo "</td><td class=\"fieldarea\"><select name=\"appliesto[]\" size=\"8\" class=\"form-control\" multiple>\n";
            $products = new WHMCS\Product\Products();
            $productsList = $products->getProducts();
            foreach ($productsList as $data) {
                $id = $data["id"];
                $groupname = $data["groupname"];
                $name = $data["name"];
                echo "<option value=\"" . $id . "\"";
                if (in_array($id, $appliesto)) {
                    echo " selected";
                }
                echo ">" . $groupname . " - " . $name . "</option>";
            }
            $result = select_query("tbladdons", "", "", "name", "ASC");
            while ($data = mysql_fetch_array($result)) {
                $id = $data["id"];
                $name = $data["name"];
                $description = $data["description"];
                echo "<option value=\"A" . $id . "\"";
                if (in_array("A" . $id, $appliesto)) {
                    echo " selected";
                }
                echo ">" . $aInt->lang("orders", "addon") . " - " . $name . "</option>";
            }
            $result = select_query("tbldomainpricing", "DISTINCT extension", "", "extension", "ASC");
            while ($data = mysql_fetch_array($result)) {
                $tld = $data["extension"];
                echo "<option value=\"D" . $tld . "\"";
                if (in_array("D" . $tld, $appliesto)) {
                    echo " selected";
                }
                echo ">" . $aInt->lang("fields", "domain") . " - " . $tld . "</option>";
            }
            echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("promos", "requires");
            echo "</td><td class=\"fieldarea\"><select name=\"requires[]\" size=\"8\" class=\"form-control\" multiple>\n";
            $productsList = $products->getProducts();
            foreach ($productsList as $data) {
                $id = $data["id"];
                $groupname = $data["groupname"];
                $name = $data["name"];
                echo "<option value=\"" . $id . "\"";
                if (in_array($id, $requires)) {
                    echo " selected";
                }
                echo ">" . $groupname . " - " . $name . "</option>";
            }
            $result = select_query("tbladdons", "", "", "name", "ASC");
            while ($data = mysql_fetch_array($result)) {
                $id = $data["id"];
                $name = $data["name"];
                $description = $data["description"];
                echo "<option value=\"A" . $id . "\"";
                if (in_array("A" . $id, $requires)) {
                    echo " selected";
                }
                echo ">" . $aInt->lang("orders", "addon") . " - " . $name . "</option>";
            }
            $result = select_query("tbldomainpricing", "DISTINCT extension", "", "extension", "ASC");
            while ($data = mysql_fetch_array($result)) {
                $tld = $data["extension"];
                echo "<option value=\"D" . $tld . "\"";
                if (in_array("D" . $tld, $requires)) {
                    echo " selected";
                }
                echo ">" . $aInt->lang("fields", "domain") . " - " . $tld . "</option>";
            }
            echo "</select><br /><input type=\"checkbox\" name=\"requiresexisting\" value=\"1\"";
            if ($requiresexisting) {
                echo " checked";
            }
            echo " /> ";
            echo $aInt->lang("promos", "requiresexisting");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("promos", "cycles");
            echo "</td><td class=\"fieldarea\">\n\n<b>";
            echo $aInt->lang("services", "title");
            echo "</b><br />\n<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"cycles[]\" value=\"One Time\"";
            if (in_array("One Time", $cycles)) {
                echo " checked";
            }
            echo " /> ";
            echo $aInt->lang("billingcycles", "onetime");
            echo "</label>\n<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"cycles[]\" value=\"Monthly\"";
            if (in_array("Monthly", $cycles)) {
                echo " checked";
            }
            echo " /> ";
            echo $aInt->lang("billingcycles", "monthly");
            echo "</label>\n<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"cycles[]\" value=\"Quarterly\"";
            if (in_array("Quarterly", $cycles)) {
                echo " checked";
            }
            echo " /> ";
            echo $aInt->lang("billingcycles", "quarterly");
            echo "</label>\n<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"cycles[]\" value=\"Semi-Annually\"";
            if (in_array("Semi-Annually", $cycles)) {
                echo " checked";
            }
            echo " /> ";
            echo $aInt->lang("billingcycles", "semiannually");
            echo "</label>\n<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"cycles[]\" value=\"Annually\"";
            if (in_array("Annually", $cycles)) {
                echo " checked";
            }
            echo " /> ";
            echo $aInt->lang("billingcycles", "annually");
            echo "</label>\n<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"cycles[]\" value=\"Biennially\"";
            if (in_array("Biennially", $cycles)) {
                echo " checked";
            }
            echo " /> ";
            echo $aInt->lang("billingcycles", "biennially");
            echo "</label>\n<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"cycles[]\" value=\"Triennially\"";
            if (in_array("Triennially", $cycles)) {
                echo " checked";
            }
            echo " /> ";
            echo $aInt->lang("billingcycles", "triennially");
            echo "</label>\n<br />\n<b>";
            echo $aInt->lang("domains", "title");
            echo "</b><br />\n";
            for ($domainyears = 1; $domainyears <= 10; $domainyears++) {
                echo "<label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"cycles[]\" value=\"" . $domainyears . "Years\"";
                if (in_array($domainyears . "Years", $cycles)) {
                    echo " checked";
                }
                echo " /> " . $domainyears . " " . (1 < $domainyears ? $aInt->lang("domains", "years") : $aInt->lang("domains", "year")) . "</label> ";
            }
            echo "\n</td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
            echo $aInt->lang("fields", "startdate");
            echo "    </td>\n    <td class=\"fieldarea\">\n        <div class=\"form-group date-picker-prepend-icon\">\n            <label for=\"inputStartDate\" class=\"field-icon\">\n                <i class=\"fal fa-calendar-alt\"></i>\n            </label>\n            <input id=\"inputStartDate\"\n                   type=\"text\"\n                   name=\"startdate\"\n                   value=\"";
            echo $startdate;
            echo "\"\n                   class=\"form-control date-picker-single future\"\n            />\n            (";
            echo AdminLang::trans("promos.leaveblank");
            echo ")\n        </div>\n    </td>\n</tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
            echo $aInt->lang("fields", "expirydate");
            echo "    </td>\n    <td class=\"fieldarea\">\n        <div class=\"form-group date-picker-prepend-icon\">\n            <label for=\"inputExpirationDate\" class=\"field-icon\">\n                <i class=\"fal fa-calendar-alt\"></i>\n            </label>\n            <input id=\"inputExpirationDate\"\n                   type=\"text\"\n                   name=\"expirationdate\"\n                   value=\"";
            echo $expirationdate;
            echo "\"\n                   class=\"form-control date-picker-single future\"\n            />\n            (";
            echo AdminLang::trans("promos.leaveblank");
            echo ")\n        </div>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("promos", "maxuses");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"maxuses\" value=\"";
            echo $maxuses;
            echo "\" class=\"form-control input-100 input-inline\"> (";
            echo $aInt->lang("promos", "unlimiteduses");
            echo ")</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("promos", "numuses");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" class=\"form-control input-100\" value=\"";
            echo (int) $uses;
            echo "\" disabled=\"disabled\"></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("promos", "lifetimepromo");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"lifetimepromo\" value=\"1\"";
            if ($lifetimepromo) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("promos", "lifetimepromodesc");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("promos", "applyonce");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"applyonce\" value=\"1\"";
            if ($applyonce) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("promos", "applyoncedesc");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("promos", "newsignups");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"newsignups\" value=\"1\"";
            if ($newsignups) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("promos", "newsignupsdesc");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("promos", "onceperclient");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"onceperclient\" value=\"1\"";
            if ($onceperclient) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("promos", "onceperclientdesc");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("promos", "existingclient");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"existingclient\" value=\"1\"";
            if ($existingclient) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("promos", "existingclientdesc");
            echo "</label></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("promos", "upgrades");
            echo "</td><td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"upgrades\" value=\"1\" onclick=\"\$('#upgradeoptions').slideToggle()\"";
            if ($upgrades) {
                echo " checked";
            }
            echo "> ";
            echo $aInt->lang("promos", "upgradesdesc");
            echo "</label>\n\n<div id=\"upgradeoptions\"";
            if (!$upgrades) {
                echo " style=\"display:none;\"";
            }
            echo ">\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td colspan=\"2\" class=\"fieldarea\"><b>";
            echo $aInt->lang("promos", "upgradesinstructions");
            echo "</b><br />";
            echo $aInt->lang("promos", "upgradesinstructionsinfo");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("promos", "upgradetype");
            echo "</td><td class=\"fieldarea\"><input type=\"radio\" name=\"upgradetype\" value=\"product\"";
            if ($upgradeconfig["type"] == "product") {
                echo " checked";
            }
            echo " /> ";
            echo $aInt->lang("services", "title");
            echo " <input type=\"radio\" name=\"upgradetype\" value=\"configoptions\"";
            if ($upgradeconfig["type"] == "configoptions") {
                echo " checked";
            }
            echo " /> ";
            echo $aInt->lang("setup", "configoptions");
            echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("promos", "upgradediscount");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"upgradevalue\" size=\"10\" value=\"";
            echo $upgradeconfig["value"];
            echo "\" /> <select name=\"upgradediscounttype\">\n<option value=\"Percentage\"";
            if ($upgradeconfig["discounttype"] == "Percentage") {
                echo " selected";
            }
            echo ">";
            echo $aInt->lang("promos", "percentage");
            echo "</option>\n<option value=\"Fixed Amount\"";
            if ($upgradeconfig["discounttype"] == "Fixed Amount") {
                echo " selected";
            }
            echo ">";
            echo $aInt->lang("promos", "fixedamount");
            echo "</option>\n</select></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("promos", "configoptionsupgrades");
            echo "</td><td class=\"fieldarea\">\n<select name=\"configoptionupgrades[]\" size=\"8\" style=\"width:90%\" multiple>\n";
            $result = select_query("tblproductconfigoptions", "tblproductconfigoptions.id,name,optionname", "", "optionname", "ASC", "", "tblproductconfiggroups ON tblproductconfiggroups.id=tblproductconfigoptions.gid");
            while ($data = mysql_fetch_array($result)) {
                $configid = $data["id"];
                $groupname = $data["name"];
                $optionname = $data["optionname"];
                echo "<option value=\"" . $configid . "\"";
                if (in_array($configid, $upgradeconfig["configoptions"])) {
                    echo " selected";
                }
                echo ">" . $groupname . " - " . $optionname . "</option>";
            }
            echo "</select><br />";
            echo $aInt->lang("promos", "configoptionsupgradesdesc");
            echo "</td></tr>\n</table>\n</div>\n\n</td></tr>\n<tr><td class=\"fieldlabel\">";
            echo $aInt->lang("fields", "adminnotes");
            echo "</td><td class=\"fieldarea\"><textarea name=\"notes\" rows=\"4\" class=\"form-control\">";
            echo $notes;
            echo "</textarea></td></tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
            echo $aInt->lang("global", "savechanges");
            echo "\" class=\"btn btn-primary\" />\n    <input type=\"button\" value=\"";
            echo $aInt->lang("global", "cancelchanges");
            echo "\" class=\"btn btn-default\" onclick=\"window.location='configpromotions.php'\" />\n</div>\n\n</form>\n\n";
        }
    }
}
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jscode = $jscode;
$aInt->display();

?>