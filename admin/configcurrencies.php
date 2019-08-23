<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Configure Currencies", false);
$aInt->title = $aInt->lang("currencies", "title");
$aInt->sidebar = "config";
$aInt->icon = "income";
$aInt->helplink = "Currencies";
$aInt->requireAuthConfirmation();
$aInt->requiredFiles(array("currencyfunctions"));
$infobox = "";
if ($action == "add") {
    check_token("WHMCS.admin.default");
    $prefix = strip_tags(WHMCS\Input\Sanitize::decode($prefix));
    $suffix = strip_tags(WHMCS\Input\Sanitize::decode($suffix));
    $addInfo = array("code" => $code, "prefix" => $prefix, "suffix" => $suffix, "format" => $format, "rate" => $rate);
    if (!is_numeric($rate)) {
        $action = false;
        $infobox = infoBox(AdminLang::trans("currencies.addCurrencyFailed"), AdminLang::trans("currencies.currencyConversionNotNumeric"), "error");
    } else {
        if (floatval($rate) == 0) {
            $action = false;
            $infobox = infoBox(AdminLang::trans("currencies.addCurrencyFailed"), AdminLang::trans("currencies.currencyConversionZero"), "error");
        }
    }
    if ($action) {
        insert_query("tblcurrencies", array("code" => $code, "prefix" => $prefix, "suffix" => $suffix, "format" => $format, "rate" => $rate));
        $logMessage = "Currency Added: '" . $code . "'";
        logAdminActivity($logMessage);
        redir();
    }
}
if ($action == "save") {
    check_token("WHMCS.admin.default");
    if ($id == 1) {
        $rate = 1;
    }
    $prefix = strip_tags(WHMCS\Input\Sanitize::decode($prefix));
    $suffix = strip_tags(WHMCS\Input\Sanitize::decode($suffix));
    update_query("tblcurrencies", array("code" => $code, "prefix" => $prefix, "suffix" => $suffix, "format" => $format, "rate" => $rate), array("id" => $id));
    $logMessage = "Currency Modified: '" . $code . "'";
    logAdminActivity($logMessage);
    if ($updatepricing) {
        currencyUpdatePricing($id);
    }
    redir();
}
if ($action == "delete") {
    check_token("WHMCS.admin.default");
    $result = select_query("tblclients", "COUNT(*)", array("currency" => $id));
    $data = mysql_fetch_array($result);
    $inuse = $data[0];
    if (!$inuse) {
        $code = Illuminate\Database\Capsule\Manager::table("tblcurrencies")->where("id", $id)->value("code");
        delete_query("tblcurrencies", array("id" => $id));
        delete_query("tblpricing", array("currency" => $id));
        $logMessage = "Currency Deleted: '" . $code . "'";
        logAdminActivity($logMessage);
    }
    redir();
}
if ($action == "updaterates") {
    check_token("WHMCS.admin.default");
    $msg = currencyUpdateRates();
    $logMessage = "Manual Currency Exchange Rates Sync Initiated";
    logAdminActivity($logMessage);
    WHMCS\Session::set("CurrencyUpdateMsg", $msg);
    redir("updaterates=1");
}
if ($action == "updateprices") {
    check_token("WHMCS.admin.default");
    currencyUpdatePricing();
    $logMessage = "Manual Mass Product Pricing Update Initiated using Currency Exchange Rates";
    logAdminActivity($logMessage);
    redir("updateprices=1");
}
ob_start();
if (!$action) {
    $aInt->deleteJSConfirm("doDelete", "currencies", "delsure", "?action=delete&id=");
    if ($updaterates && WHMCS\Session::get("CurrencyUpdateMsg")) {
        infoBox($aInt->lang("currencies", "exchrateupdate"), WHMCS\Session::get("CurrencyUpdateMsg"));
        WHMCS\Session::delete("CurrencyUpdateMsg");
    }
    if ($updateprices) {
        infoBox($aInt->lang("currencies", "updatedpricing"), $aInt->lang("currencies", "updatepricinginfo"));
    }
    echo $infobox;
    echo "<p>" . $aInt->lang("currencies", "info") . "</p>";
    $aInt->sortableTableInit("nopagination");
    $totalcurrencies = 0;
    for ($result = select_query("tblcurrencies", "", "", "code", "ASC"); $data = mysql_fetch_array($result); $totalcurrencies++) {
        $id = $data["id"];
        $code = $data["code"];
        $prefix = $data["prefix"];
        $suffix = $data["suffix"];
        $format = $data["format"];
        $rate = $data["rate"];
        if ($format == 1) {
            $formatex = "1234.56";
        } else {
            if ($format == 2) {
                $formatex = "1,234.56";
            } else {
                if ($format == 3) {
                    $formatex = "1.234,56";
                } else {
                    if ($format == 4) {
                        $formatex = "1,234";
                    }
                }
            }
        }
        if ($id != 1) {
            $result2 = select_query("tblclients", "COUNT(*)", array("currency" => $id));
            $data = mysql_fetch_array($result2);
            $inuse = $data[0];
            $deletelink = "<a href=\"#\" onClick=\"";
            if ($inuse) {
                $deletelink .= "alert('" . addslashes($aInt->lang("currencies", "deleteinuse")) . "')";
            } else {
                $deletelink .= "doDelete('" . $id . "')";
            }
            $deletelink .= ";return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\"></a>";
        } else {
            $deletelink = "";
        }
        $tabledata[] = array($code, $prefix, $suffix, $formatex, $rate, "<a href=\"?action=edit&id=" . $id . "\"><img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "edit") . "\"></a>", $deletelink);
    }
    echo $aInt->sortableTable(array($aInt->lang("currencies", "code"), $aInt->lang("currencies", "prefix"), $aInt->lang("currencies", "suffix"), $aInt->lang("currencies", "format"), $aInt->lang("currencies", "baserate"), "", ""), $tabledata);
    echo "\n<div class=\"btn-container\">\n    <input type=\"button\" value=\"";
    echo $aInt->lang("currencies", "updateexch");
    echo "\" class=\"btn btn-default\" onclick=\"window.location='configcurrencies.php?action=updaterates";
    echo generate_token("link");
    echo "'\" />\n    <input type=\"button\" value=\"";
    echo $aInt->lang("currencies", "updateprod");
    echo "\" class=\"btn btn-default\" onclick=\"window.location='configcurrencies.php?action=updateprices";
    echo generate_token("link");
    echo "'\" />\n</div>\n\n<h2>";
    echo $aInt->lang("currencies", "addadditional");
    echo "</h2>\n\n<form method=\"post\" action=\"";
    echo $whmcs->getPhpSelf();
    echo "\">\n<input type=\"hidden\" name=\"action\" value=\"add\" />\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"15%\" class=\"fieldlabel\">";
    echo $aInt->lang("currencies", "code");
    echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"code\" class=\"form-control input-150\"";
    if (!empty($addInfo)) {
        echo " value=\"" . $addInfo["code"] . "\"";
    }
    echo "> ";
    echo $aInt->lang("currencies", "codeinfo");
    echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
    echo $aInt->lang("currencies", "prefix");
    echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"prefix\" class=\"form-control input-150\"";
    if (!empty($addInfo)) {
        echo " value=\"" . $addInfo["prefix"] . "\"";
    }
    echo "></td></tr>\n<tr><td class=\"fieldlabel\">";
    echo $aInt->lang("currencies", "suffix");
    echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"suffix\" class=\"form-control input-150\"";
    if (!empty($addInfo)) {
        echo " value=\"" . $addInfo["suffix"] . "\"";
    }
    echo "></td></tr>\n<tr><td class=\"fieldlabel\">";
    echo $aInt->lang("currencies", "format");
    echo "</td><td class=\"fieldarea\"><select name=\"format\" class=\"form-control select-inline\">\n<option value=\"1\"";
    if ($addInfo["format"] == 1) {
        echo " selected";
    }
    echo ">1234.56</option>\n<option value=\"2\"";
    if ($addInfo["format"] == 2) {
        echo " selected";
    }
    echo ">1,234.56</option>\n<option value=\"3\"";
    if ($addInfo["format"] == 3) {
        echo " selected";
    }
    echo ">1.234,56</option>\n<option value=\"4\"";
    if ($addInfo["format"] == 4) {
        echo " selected";
    }
    echo ">1,234</option>\n</select></td></tr>\n<tr><td class=\"fieldlabel\">";
    echo $aInt->lang("currencies", "baserate");
    echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"rate\" class=\"form-control input-150\" value=\"";
    if (!empty($addInfo)) {
        echo $addInfo["rate"];
    } else {
        echo "1.00";
    }
    echo "\"> ";
    echo $aInt->lang("currencies", "baserateinfo");
    echo "</td></tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
    echo $aInt->lang("currencies", "add");
    echo "\" class=\"btn btn-primary\" />\n</div>\n\n</form>\n\n";
} else {
    if ($action == "edit") {
        $result = select_query("tblcurrencies", "", array("id" => $id));
        $data = mysql_fetch_array($result);
        $code = $data["code"];
        $prefix = $data["prefix"];
        $suffix = $data["suffix"];
        $format = $data["format"];
        $rate = $data["rate"];
        echo "\n<form method=\"post\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "\">\n<input type=\"hidden\" name=\"action\" value=\"save\" />\n<input type=\"hidden\" name=\"id\" value=\"";
        echo $id;
        echo "\" />\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"15%\" class=\"fieldlabel\">";
        echo $aInt->lang("currencies", "code");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"code\" value=\"";
        echo $code;
        echo "\" class=\"form-control input-150\"> ";
        echo $aInt->lang("currencies", "codeinfo");
        echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("currencies", "prefix");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"prefix\" class=\"form-control input-150\" value=\"";
        echo WHMCS\Input\Sanitize::encode($prefix);
        echo "\"></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("currencies", "suffix");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"suffix\" class=\"form-control input-150\" value=\"";
        echo WHMCS\Input\Sanitize::encode($suffix);
        echo "\"></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("currencies", "format");
        echo "</td><td class=\"fieldarea\"><select name=\"format\" class=\"form-control select-inline\">\n<option value=\"1\"";
        if ($format == 1) {
            echo " selected";
        }
        echo ">1234.56</option>\n<option value=\"2\"";
        if ($format == 2) {
            echo " selected";
        }
        echo ">1,234.56</option>\n<option value=\"3\"";
        if ($format == 3) {
            echo " selected";
        }
        echo ">1.234,56</option>\n<option value=\"4\"";
        if ($format == 4) {
            echo " selected";
        }
        echo ">1,234</option>\n</select></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("currencies", "baserate");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"rate\" class=\"form-control input-150\" value=\"";
        echo $rate;
        echo "\"";
        if ($id == 1) {
            echo " readonly=true disabled";
        }
        echo "> ";
        echo $aInt->lang("currencies", "baserateinfo");
        echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("currencies", "updatepricing");
        echo "</td><td class=\"fieldarea\">\n    <label class=\"checkbox-inline\">\n        <input type=\"checkbox\" name=\"updatepricing\">\n        ";
        echo $aInt->lang("currencies", "recalcpricing");
        echo "    </label>\n</td></tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
        echo $aInt->lang("global", "savechanges");
        echo "\" class=\"btn btn-primary\">\n    <input type=\"button\" value=\"";
        echo $aInt->lang("global", "cancelchanges");
        echo "\" class=\"btn btn-default\" onclick=\"window.location='configcurrencies.php'\" />\n</div>\n\n</form>\n\n";
    }
}
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jscode = $jscode;
$aInt->display();

?>