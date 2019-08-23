<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("View Products/Services");
$aInt->title = "Configurable Option Groups";
$aInt->sidebar = "config";
$aInt->icon = "configoptions";
$aInt->helplink = "Configurable Options";
if ($manageoptions) {
    $result = select_query("tblcurrencies", "", "", "code", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $curr_id = $data["id"];
        $curr_code = $data["code"];
        $currenciesarray[$curr_id] = $curr_code;
    }
    $totalcurrencies = count($currenciesarray) * 2;
    if ($save) {
        check_token("WHMCS.admin.default");
        checkPermission("Edit Products/Services");
        $cid = (int) $whmcs->get_req_var("cid");
        $configoptionname = $whmcs->get_req_var("configoptionname");
        if (!$cid) {
            $gid = (int) $whmcs->get_req_var("gid");
            $cid = insert_query("tblproductconfigoptions", array("gid" => $gid, "optionname" => $configoptionname));
            logAdminActivity("New Configurable Option Created: '" . $configoptionname . "' - Group: " . $group->name . " - Option Group ID: " . $gid);
        }
        $configOption = Illuminate\Database\Capsule\Manager::table("tblproductconfigoptions")->find($cid);
        $group = Illuminate\Database\Capsule\Manager::table("tblproductconfiggroups")->find($configOption->gid);
        if ($optionname == "") {
            $optionname = array();
        }
        if ($addoptionname == "") {
            $addoptionname = array();
        }
        $configoptiontype = $whmcs->get_req_var("configoptiontype");
        if ($configoptionname != $configOption->optionname) {
            logAdminActivity("Configurable Option Modified: Name Changed: " . "'" . $configOption->optionname . "' to " . $configoptionname . "' - " . "Group: " . $group->name . " - Option Group ID: " . $group->id);
        }
        if ($configoptiontype != $configOption->optiontype) {
            logAdminActivity("Configurable Option Modified: Type Changed: " . "'" . $configOption->optiontype . "' to " . $configoptiontype . "' - " . "Group: " . $group->name . " - Option Group ID: " . $group->id);
        }
        $qtyminimum = (int) $whmcs->get_req_var("qtyminimum");
        $qtymaximum = (int) $whmcs->get_req_var("qtymaximum");
        if ($qtyminimum != $configOption->qtyminimum) {
            logAdminActivity("Configurable Option Modified: Qty Minimum Modified: " . "'" . $configOption->qtyminimum . "' to " . $qtyminimum . "' - " . "Group: " . $group->name . " - Option Group ID: " . $group->id);
        }
        if ($qtymaximum != $configOption->qtymaximum) {
            logAdminActivity("Configurable Option Modified: Qty Maximum Modified: " . "'" . $configOption->qtymaximum . "' to " . $qtymaximum . "' - " . "Group: " . $group->name . " - Option Group ID: " . $group->id);
        }
        update_query("tblproductconfigoptions", array("optionname" => $configoptionname, "optiontype" => $configoptiontype, "qtyminimum" => $qtyminimum, "qtymaximum" => $qtymaximum), array("id" => $cid));
        foreach ($optionname as $key => $value) {
            $subOption = Illuminate\Database\Capsule\Manager::table("tblproductconfigoptionssub")->find($key);
            update_query("tblproductconfigoptionssub", array("optionname" => $value, "sortorder" => $sortorder[$key], "hidden" => $hidden[$key]), array("id" => $key));
            if ($subOption->optionname != $value || $subOption->sortorder != $sortorder[$key] || $subOption->hidden != $hidden[$key]) {
                logAdminActivity("Configurable Option Modified - '" . $configoptionname . "' - Option Modified: '" . $value . "'" . " - Group: " . $group->name . " - Option Group ID: " . $group->id);
            }
        }
        if ($price) {
            $priceChanges = false;
            foreach ($price as $curr_id => $temp_values) {
                foreach ($temp_values as $optionid => $values) {
                    if ($priceChanges === false) {
                        $currentPricing = Illuminate\Database\Capsule\Manager::table("tblpricing")->where("type", "=", "configoptions")->where("currency", "=", $curr_id)->where("relid", "=", $optionid)->first();
                        if ($currentPricing->msetupfee != $values[1] || $currentPricing->qsetupfee != $values[2] || $currentPricing->ssetupfee != $values[3] || $currentPricing->asetupfee != $values[4] || $currentPricing->bsetupfee != $values[5] || $currentPricing->tsetupfee != $values[6] || $currentPricing->monthly != $values[7] || $currentPricing->quarterly != $values[8] || $currentPricing->semiannually != $values[9] || $currentPricing->annually != $values[10] || $currentPricing->biennially != $values[11] || $currentPricing->triennially != $values[12]) {
                            $priceChanges = true;
                        }
                    }
                    update_query("tblpricing", array("msetupfee" => $values[1], "qsetupfee" => $values[2], "ssetupfee" => $values[3], "asetupfee" => $values[4], "bsetupfee" => $values[5], "tsetupfee" => $values[11], "monthly" => $values[6], "quarterly" => $values[7], "semiannually" => $values[8], "annually" => $values[9], "biennially" => $values[10], "triennially" => $values[12]), array("type" => "configoptions", "currency" => $curr_id, "relid" => $optionid));
                }
            }
            if ($priceChanges) {
                logAdminActivity("Configurable Option Pricing Modified: '" . $configoptionname . "'" . " - Group: " . $group->name . " - Option Group ID: " . $group->id);
            }
        }
        if ($addoptionname) {
            insert_query("tblproductconfigoptionssub", array("configid" => $cid, "optionname" => $addoptionname, "sortorder" => $addsortorder, "hidden" => $addhidden));
            logAdminActivity("Configurable Option Modified - '" . $configoptionname . "' - Option Added: '" . $addoptionname . "'" . " - Group: " . $group->name . " - Option Group ID: " . $group->id);
        }
        redir("manageoptions=true&cid=" . $cid);
    }
    if ($deleteconfigoption) {
        check_token("WHMCS.admin.default");
        checkPermission("Delete Products/Services");
        $cid = (int) $whmcs->get_req_var("cid");
        $confid = (int) $whmcs->get_req_var("confid");
        $configOption = Illuminate\Database\Capsule\Manager::table("tblproductconfigoptions")->find($cid);
        $group = Illuminate\Database\Capsule\Manager::table("tblproductconfiggroups")->find($configOption->gid);
        $option = Illuminate\Database\Capsule\Manager::table("tblproductconfigoptionssub")->find($confid);
        delete_query("tblproductconfigoptionssub", array("id" => $confid));
        logAdminActivity("Configurable Option Modified - '" . $configOption->name . "' - Option Removed: '" . $option->optionname . "'" . " - Group: " . $group->name . " - Option Group ID: " . $gid);
        redir("manageoptions=true&cid=" . $cid);
    }
    $aInt->title = "Configurable Options";
    $result = select_query("tblproductconfigoptions", "", array("id" => $cid));
    $data = mysql_fetch_array($result);
    $cid = $data["id"];
    $optionname = $data["optionname"];
    $optiontype = $data["optiontype"];
    $qtyminimum = $data["qtyminimum"];
    $qtymaximum = $data["qtymaximum"];
    $order = $data["order"];
    ob_start();
    echo "\n<script langauge=\"JavaScript\">\nfunction deletegroupoption(id) {\n    if (confirm(\"Are you sure you want to delete this product configuration option?\")) {\n        window.location='";
    echo $whmcs->getPhpSelf();
    echo "?manageoptions=true&cid=";
    echo $cid;
    echo "&deleteconfigoption=true&confid='+id+'";
    echo generate_token("link");
    echo "';\n    }\n}\nfunction closewindow() {\n    window.opener.document.managefrm.submit();\n    window.close();\n}\n</script>\n\n<form method=\"post\" action=\"";
    echo $_SERVER["PHP_SELF"];
    echo "?manageoptions=true&cid=";
    echo $cid;
    if ($gid) {
        echo "&gid=" . $gid;
    }
    echo "&save=true\">\n\n<p>Option Name: <input type=\"text\" name=\"configoptionname\" size=\"50\" value=\"";
    echo $optionname;
    echo "\" class=\"form-control\" style=\"display:inline-block;width:50%;\" /> Option Type: <select name=\"configoptiontype\" class=\"form-control select-inline\"><option value=\"1\"";
    if ($optiontype == "1") {
        echo " selected";
    }
    echo ">Dropdown</option><option value=\"2\"";
    if ($optiontype == "2") {
        echo " selected";
    }
    echo ">Radio</option><option value=\"3\"";
    if ($optiontype == "3") {
        echo " selected";
    }
    echo ">Yes/No</option><option value=\"4\"";
    if ($optiontype == "4") {
        echo " selected";
    }
    echo ">Quantity</option></select>";
    if ($optiontype == "4") {
        echo "<br>Minimum Quantity Required: <input type=\"text\" name=\"qtyminimum\" size=\"6\" value=\"" . $qtyminimum . "\" /> Maximum Allowed: <input type=\"text\" name=\"qtymaximum\" size=\"6\" value=\"" . $qtymaximum . "\" /> (Set to 0 for Unlimited)";
    }
    echo "</p>\n\n<table class=\"table\">\n<tr bgcolor=\"#efefef\" style=\"text-align:center;font-weight:bold;\">\n    <td>Options</td>\n    <td width=\"70\">&nbsp;</td>\n    <td width=\"70\">&nbsp;</td>\n    <td width=\"70\">";
    echo $aInt->lang("billingcycles", "onetime");
    echo "/<br />";
    echo $aInt->lang("billingcycles", "monthly");
    echo "</td><td width=70>Quarterly</td><td width=70>Semi-Annual</td><td width=70>Annual</td>\n    <td width=\"70\">Biennial</td>\n    <td width=\"70\">Triennial</td>\n    <td width=\"80\">Order</td>\n    <td width=\"30\">Hide</td>\n</tr>\n";
    $x = 0;
    $query = "SELECT * FROM tblproductconfigoptionssub WHERE configid='" . (int) $cid . "' ORDER BY sortorder ASC,id ASC";
    $result = full_query($query);
    while ($data = mysql_fetch_array($result)) {
        $x++;
        $optionid = $data["id"];
        $optionname = $data["optionname"];
        $sortorder = $data["sortorder"];
        $hidden = $data["hidden"];
        echo "<tr bgcolor=\"#ffffff\" style=\"text-align:center;\"><td rowspan=\"" . $totalcurrencies . "\"><input type=\"text\" name=\"optionname[" . $optionid . "]\" value=\"" . $optionname . "\" class=\"form-control\" style=\"min-width:180px;\">";
        if (1 < $x) {
            echo "<br><a href=\"#\" onclick=\"deletegroupoption('" . $optionid . "');return false;\"><img src=\"images/icons/delete.png\" border=\"0\">";
        }
        echo "</td>";
        $firstcurrencydone = false;
        foreach ($currenciesarray as $curr_id => $curr_code) {
            $result2 = select_query("tblpricing", "", array("type" => "configoptions", "currency" => $curr_id, "relid" => $optionid));
            $data = mysql_fetch_array($result2);
            $pricing_id = $data["id"];
            if (!$pricing_id) {
                insert_query("tblpricing", array("type" => "configoptions", "currency" => $curr_id, "relid" => $optionid));
                $result2 = select_query("tblpricing", "", array("type" => "configoptions", "currency" => $curr_id, "relid" => $optionid));
                $data = mysql_fetch_array($result2);
            }
            $val[1] = $data["msetupfee"];
            $val[2] = $data["qsetupfee"];
            $val[3] = $data["ssetupfee"];
            $val[4] = $data["asetupfee"];
            $val[5] = $data["bsetupfee"];
            $val[11] = $data["tsetupfee"];
            $val[6] = $data["monthly"];
            $val[7] = $data["quarterly"];
            $val[8] = $data["semiannually"];
            $val[9] = $data["annually"];
            $val[10] = $data["biennially"];
            $val[12] = $data["triennially"];
            if ($firstcurrencydone) {
                echo "</tr><tr bgcolor=\"#ffffff\" style=\"text-align:center;\">";
            }
            echo "<td rowspan=\"2\" bgcolor=\"#efefef\"><b>" . $curr_code . "</b></td><td>Setup</td><td><input type=\"text\" name=\"price[" . $curr_id . "][" . $optionid . "][1]\" value=\"" . $val[1] . "\" class=\"form-control\" style=\"width:80px;\"></td><td><input type=\"text\" name=\"price[" . $curr_id . "][" . $optionid . "][2]\" value=\"" . $val[2] . "\" class=\"form-control\" style=\"width:80px;\"></td><td><input type=\"text\" name=\"price[" . $curr_id . "][" . $optionid . "][3]\" value=\"" . $val[3] . "\" class=\"form-control\" style=\"width:80px;\"></td><td><input type=\"text\" name=\"price[" . $curr_id . "][" . $optionid . "][4]\" value=\"" . $val[4] . "\" class=\"form-control\" style=\"width:80px;\"></td><td><input type=\"text\" name=\"price[" . $curr_id . "][" . $optionid . "][5]\" value=\"" . $val[5] . "\" class=\"form-control\" style=\"width:80px;\"></td><td><input type=\"text\" name=\"price[" . $curr_id . "][" . $optionid . "][11]\" value=\"" . $val[11] . "\" class=\"form-control\" style=\"width:80px;\"></td>";
            if (!$firstcurrencydone) {
                echo "<td rowspan=\"" . $totalcurrencies . "\"><input type=\"text\" name=\"sortorder[" . $optionid . "]\" value=\"" . $sortorder . "\" class=\"form-control\" style=\"width:60px;\"></td><td rowspan=\"" . $totalcurrencies . "\"><input type=\"checkbox\" name=\"hidden[" . $optionid . "]\" value=\"1\"";
                if ($hidden) {
                    echo " checked";
                }
                echo " /></td>";
            }
            echo "</tr><tr bgcolor=\"#ffffff\" style=\"text-align:center;\"><td>Pricing</td><td><input type=\"text\" name=\"price[" . $curr_id . "][" . $optionid . "][6]\" value=\"" . $val[6] . "\" class=\"form-control\" style=\"width:80px;\"></td><td><input type=\"text\" name=\"price[" . $curr_id . "][" . $optionid . "][7]\" value=\"" . $val[7] . "\" class=\"form-control\" style=\"width:80px;\"></td><td><input type=\"text\" name=\"price[" . $curr_id . "][" . $optionid . "][8]\" value=\"" . $val[8] . "\" class=\"form-control\" style=\"width:80px;\"></td><td><input type=\"text\" name=\"price[" . $curr_id . "][" . $optionid . "][9]\" value=\"" . $val[9] . "\" class=\"form-control\" style=\"width:80px;\"></td><td><input type=\"text\" name=\"price[" . $curr_id . "][" . $optionid . "][10]\" value=\"" . $val[10] . "\" class=\"form-control\" style=\"width:80px;\"></td><td><input type=\"text\" name=\"price[" . $curr_id . "][" . $optionid . "][12]\" value=\"" . $val[12] . "\" class=\"form-control\" style=\"width:80px;\"></td>";
            $firstcurrencydone = true;
        }
        echo "</tr>";
    }
    if ($optiontype == "1" || $optiontype == "2" || $x == "0") {
        echo "<tr bgcolor=\"#efefef\"><td colspan=\"9\"><B>Add Option:</B> <input type=\"text\" name=\"addoptionname\" class=\"form-control\" style=\"display:inline-block;width:60%;\"></td><td><input type=\"text\" name=\"addsortorder\" value=\"0\" class=\"form-control\" style=\"width:60px;\"></td><td><input type=\"checkbox\" name=\"addhidden\" value=\"1\" /></td></tr>\n";
    }
    echo "</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"Save Changes\" class=\"btn btn-primary\" />\n    <input type=\"button\" value=\"Close Window\" onclick=\"closewindow();\" class=\"btn btn-default\" />\n</div>\n\n</form>\n\n";
    $content = ob_get_contents();
    ob_end_clean();
    $aInt->content = $content;
    $aInt->displayPopUp();
    exit;
} else {
    if ($action == "savegroup") {
        check_token("WHMCS.admin.default");
        checkPermission("Edit Products/Services");
        if ($id) {
            update_query("tblproductconfiggroups", array("name" => $name, "description" => $description), array("id" => $id));
            $response = "saved";
            logAdminActivity("Configurable Option Group Modified: '" . $name . "' - Option Group ID: " . $id);
        } else {
            $id = insert_query("tblproductconfiggroups", array("name" => $name, "description" => $description));
            $response = "added";
            logAdminActivity("Configurable Option Group Created: '" . $name . "' - Option Group ID: " . $id);
        }
        delete_query("tblproductconfiglinks", array("gid" => $id));
        if ($productlinks) {
            foreach ($productlinks as $pid) {
                insert_query("tblproductconfiglinks", array("gid" => $id, "pid" => $pid));
            }
        }
        if ($order) {
            foreach ($order as $configid => $sortorder) {
                update_query("tblproductconfigoptions", array("order" => $sortorder, "hidden" => $hidden[$configid]), array("id" => $configid));
            }
        }
        redir("action=managegroup&id=" . $id);
    }
    if ($action == "duplicate") {
        check_token("WHMCS.admin.default");
        checkPermission("Create New Products/Services");
        $result = select_query("tblproductconfiggroups", "", array("id" => $existinggroupid));
        $data = mysql_fetch_array($result);
        $addstr = "";
        $oldGroupName = $data["name"];
        $newgroupname = $whmcs->get_req_var("newgroupname");
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                if ($key == "0") {
                    $value = "";
                }
                if ($key == "1") {
                    $value = $newgroupname;
                }
                $addstr .= "'" . db_escape_string($value) . "',";
            }
        }
        $addstr = substr($addstr, 0, -1);
        full_query("INSERT INTO tblproductconfiggroups VALUES (" . $addstr . ")");
        $newgroupid = mysql_insert_id();
        $result = select_query("tblproductconfigoptions", "", array("gid" => $existinggroupid));
        while ($data = mysql_fetch_array($result)) {
            $configid = $data["id"];
            $addstr = "";
            foreach ($data as $key => $value) {
                if (is_numeric($key)) {
                    if ($key == "0") {
                        $value = "";
                    }
                    if ($key == "1") {
                        $value = $newgroupid;
                    }
                    $addstr .= "'" . db_escape_string($value) . "',";
                }
            }
            $addstr = substr($addstr, 0, -1);
            full_query("INSERT INTO tblproductconfigoptions VALUES (" . $addstr . ")");
            $newconfigid = mysql_insert_id();
            $result2 = select_query("tblproductconfigoptionssub", "", array("configid" => $configid));
            while ($data = mysql_fetch_array($result2)) {
                $optionid = $data["id"];
                $addstr = "";
                foreach ($data as $key => $value) {
                    if (is_numeric($key)) {
                        if ($key == "0") {
                            $value = "";
                        }
                        if ($key == "1") {
                            $value = $newconfigid;
                        }
                        $addstr .= "'" . db_escape_string($value) . "',";
                    }
                }
                $addstr = substr($addstr, 0, -1);
                full_query("INSERT INTO tblproductconfigoptionssub VALUES (" . $addstr . ")");
                $newoptionid = mysql_insert_id();
                $result3 = select_query("tblpricing", "", array("type" => "configoptions", "relid" => $optionid));
                while ($data = mysql_fetch_array($result3)) {
                    $addstr = "";
                    foreach ($data as $key => $value) {
                        if (is_numeric($key)) {
                            if ($key == "0") {
                                $value = "";
                            }
                            if ($key == "3") {
                                $value = $newoptionid;
                            }
                            $addstr .= "'" . db_escape_string($value) . "',";
                        }
                    }
                    $addstr = substr($addstr, 0, -1);
                    full_query("INSERT INTO tblpricing VALUES (" . $addstr . ")");
                }
            }
        }
        logAdminActivity("Configurable Option Group Duplicated: '" . $oldGroupName . "' to '" . $newgroupname . "' - Option Group ID: " . $newgroupid);
        redir("duplicated=true");
    }
    if ($action == "deleteoption") {
        check_token("WHMCS.admin.default");
        checkPermission("Edit Products/Services");
        $id = (int) $whmcs->get_req_var("id");
        $group = Illuminate\Database\Capsule\Manager::table("tblproductconfiggroups")->find($id);
        $opid = (int) $whmcs->get_req_var("opid");
        $option = Illuminate\Database\Capsule\Manager::table("tblproductconfigoptions")->find($opid);
        delete_query("tblproductconfigoptions", array("id" => $opid));
        delete_query("tblproductconfigoptionssub", array("configid" => $opid));
        delete_query("tblhostingconfigoptions", array("configid" => $opid));
        logAdminActivity("Configurable Option Group Modified - '" . $group->name . "' - Option Removed: '" . $option->optionname . "'" . " - Option Group ID: " . $id);
        redir("action=managegroup&id=" . $id);
    }
    if ($action == "deletegroup") {
        check_token("WHMCS.admin.default");
        checkPermission("Delete Products/Services");
        $id = (int) $whmcs->get_req_var("id");
        $group = Illuminate\Database\Capsule\Manager::table("tblproductconfiggroups")->find($id);
        $result = select_query("tblproductconfigoptions", "", array("gid" => $id));
        while ($data = mysql_fetch_array($result)) {
            $opid = $data["id"];
            delete_query("tblproductconfigoptions", array("id" => $opid));
            delete_query("tblproductconfigoptionssub", array("configid" => $opid));
            delete_query("tblhostingconfigoptions", array("configid" => $opid));
        }
        delete_query("tblproductconfiggroups", array("id" => $id));
        delete_query("tblproductconfiglinks", array("gid" => $id));
        logAdminActivity("Configurable Option Group Deleted - '" . $group->name . "' - Option Group ID: " . $id);
        redir("deleted=true");
    }
    ob_start();
    $jscode = "function doDelete(id) {\nif (confirm(\"Are you sure you want to delete this configurable option group?\")) {\nwindow.location='" . $_SERVER["PHP_SELF"] . "?action=deletegroup&id='+id+'" . generate_token("link") . "';\n}}";
    if ($action == "") {
        if ($deleted) {
            infoBox("Success", "The option group has been deleted successfully!");
        }
        if ($duplicated) {
            infoBox("Success", "The option group has been duplicated successfully!");
        }
        echo $infobox;
        echo "\n<p>Configurable options allow you to offer addons and customisation options with your products. Options are assigned to groups and groups can then be applied to products.</p>\n\n<p>\n    <div class=\"btn-group\" role=\"group\">\n        <a href=\"";
        echo $_SERVER["PHP_SELF"];
        echo "?action=managegroup\" class=\"btn btn-default\"><i class=\"fas fa-plus\"></i> Create a New Group</a>\n        <a href=\"";
        echo $_SERVER["PHP_SELF"];
        echo "?action=duplicategroup\" class=\"btn btn-default\"><i class=\"fas fa-plus-square\"></i> Duplicate a Group</a>\n    </div>\n</p>\n\n";
        $aInt->sortableTableInit("nopagination");
        $result = select_query("tblproductconfiggroups", "", "", "name", "ASC");
        while ($data = mysql_fetch_array($result)) {
            $id = $data["id"];
            $name = $data["name"];
            $description = $data["description"];
            $tabledata[] = array($name, $description, "<a href=\"" . $_SERVER["PHP_SELF"] . "?action=managegroup&id=" . $id . "\"><img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"Edit\"></a>", "<a href=\"#\" onClick=\"doDelete('" . $id . "');return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"Delete\"></a>");
        }
        echo $aInt->sortableTable(array("Group Name", "Description", "", ""), $tabledata);
    } else {
        if ($action == "managegroup") {
            if ($id) {
                $steptitle = "Manage Group";
                $result = select_query("tblproductconfiggroups", "", array("id" => $id));
                $data = mysql_fetch_array($result);
                $id = $data["id"];
                $name = $data["name"];
                $description = $data["description"];
                $productlinks = array();
                $result = select_query("tblproductconfiglinks", "", array("gid" => $id));
                while ($data = mysql_fetch_array($result)) {
                    $productlinks[] = $data["pid"];
                }
            } else {
                checkPermission("Create New Products/Services");
                $steptitle = "Create a New Group";
                $id = "";
                $productlinks = array();
            }
            $jscode = "function manageconfigoptions(id) {\n    window.open('" . $_SERVER["PHP_SELF"] . "?manageoptions=true&cid='+id,'configoptions','width=900,height=500,scrollbars=yes');\n}\nfunction addconfigoption() {\n    window.open('" . $_SERVER["PHP_SELF"] . "?manageoptions=true&gid=" . $id . "','configoptions','width=800,height=500,scrollbars=yes');\n}\nfunction doDelete(id,opid) {\n    if (confirm(\"Are you sure you want to delete this configurable option?\")) {\n        window.location='" . $_SERVER["PHP_SELF"] . "?action=deleteoption&id='+id+'&opid='+opid+'" . generate_token("link") . "';\n    }\n}";
            echo "\n<form method=\"post\" action=\"";
            echo $_SERVER["PHP_SELF"];
            echo "?action=savegroup&id=";
            echo $id;
            echo "\" name=\"managefrm\">\n\n<h2>";
            echo $steptitle;
            echo "</h2>\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"15%\" class=\"fieldlabel\">Group Name</td><td class=\"fieldarea\"><input type=\"text\" name=\"name\" value=\"";
            echo $name;
            echo "\" class=\"form-control\"></td></tr>\n<tr><td class=\"fieldlabel\">Description</td><td class=\"fieldarea\"><input type=\"text\" name=\"description\" value=\"";
            echo $description;
            echo "\" class=\"form-control\"></td></tr>\n<tr><td class=\"fieldlabel\">Assigned Products</td><td class=\"fieldarea\"><select name=\"productlinks[]\" size=\"8\" class=\"form-control\" multiple>\n";
            $products = new WHMCS\Product\Products();
            $productsList = $products->getProducts();
            foreach ($productsList as $data) {
                $pid = $data["id"];
                $groupname = $data["groupname"];
                $name = $data["name"];
                echo "<option value=\"" . $pid . "\"";
                if (in_array($pid, $productlinks)) {
                    echo " selected";
                }
                echo ">" . $groupname . " - " . $name . "</option>";
            }
            echo "</select></td></tr>\n</table>\n\n";
            if ($id) {
                echo "\n<br>\n\n<h2>Configurable Options</h2>\n\n<p align=\"center\"><input type=\"button\" value=\"Add New Configurable Option\" class=\"button btn btn-default\" onclick=\"addconfigoption()\" /></p>\n\n";
                $aInt->sortableTableInit("nopagination");
                $result = select_query("tblproductconfigoptions", "", array("gid" => $id), "order` ASC,`id", "ASC");
                while ($data = mysql_fetch_array($result)) {
                    $configid = $data["id"];
                    $optionname = $data["optionname"];
                    $configorder = $data["order"];
                    $hidden = $data["hidden"];
                    if ($hidden) {
                        $hidden = " checked";
                    }
                    $tabledata[] = array($optionname, "<input type=\"text\" name=\"order[" . $configid . "]\" value=\"" . $configorder . "\" class=\"form-control input-inline input-100\">", "<input type=\"checkbox\" name=\"hidden[" . $configid . "]\" value=\"1\"" . $hidden . " />", "<a href=\"#\" onClick=\"manageconfigoptions('" . $configid . "');return false\"><img src=\"images/edit.gif\" border=\"0\"></a>", "<a href=\"#\" onClick=\"doDelete('" . $id . "','" . $configid . "');return false\"><img src=\"images/delete.gif\" border=\"0\"></a>");
                }
                echo $aInt->sortableTable(array("Option", "Sort Order", "Hidden", "", ""), $tabledata);
            }
            echo "\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"Save Changes\" class=\"btn btn-primary\" />\n    <input type=\"button\" value=\"Back to Groups List\" onClick=\"window.location='configproductoptions.php'\" class=\"btn btn-default\" />\n</div>\n\n</form>\n\n";
        } else {
            if ($action == "duplicategroup") {
                checkPermission("Create New Products/Services");
                echo "\n<h2>Duplicate Group</h2>\n\n<form method=\"post\" action=\"";
                echo $whmcs->getPhpSelf();
                echo "?action=duplicate\">\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=150 class=\"fieldlabel\">Existing Group</td><td class=\"fieldarea\"><select name=\"existinggroupid\" class=\"form-control select-inline\">";
                $result = select_query("tblproductconfiggroups", "", "", "name", "ASC");
                while ($data = mysql_fetch_array($result)) {
                    $id = $data["id"];
                    $name = $data["name"];
                    $description = $data["description"];
                    if (50 < strlen($description)) {
                        $description = substr($description, 0, 50) . "...";
                    }
                    echo "<option value=\"" . $id . "\">" . $name . " - " . $description . "</option>";
                }
                echo "</select></td></tr>\n<tr><td class=\"fieldlabel\">New Group Name</td><td class=\"fieldarea\"><input type=\"text\" name=\"newgroupname\" class=\"form-control input-500\"></td></tr>\n</table>\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"Continue &raquo;\" class=\"btn btn-primary\">\n</div>\n</form>\n\n";
            }
        }
    }
    $content = ob_get_contents();
    ob_end_clean();
    $aInt->content = $content;
    $aInt->jquerycode = $jquerycode;
    $aInt->jscode = $jscode;
    $aInt->display();
}

?>