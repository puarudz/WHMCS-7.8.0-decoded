<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
if (!$action) {
    $reqperm = "View Billable Items";
} else {
    $reqperm = "Manage Billable Items";
}
$aInt = new WHMCS\Admin($reqperm);
$whmcs = WHMCS\Application::getInstance();
$aInt->title = $aInt->lang("billableitems", "title");
$aInt->sidebar = "billing";
$aInt->icon = "billableitems";
$aInt->requiredFiles(array("invoicefunctions", "gatewayfunctions"));
if ($action == "save") {
    check_token("WHMCS.admin.default");
    $description = trim($whmcs->get_req_var("description"));
    if (!$userid) {
        $aInt->gracefulExit($aInt->lang("billableitems", "noclientsmsg"));
    }
    $duedate = toMySQLDate($duedate);
    getUsersLang($userid);
    if ($id) {
        if ($hours != 0) {
            if (strpos($description, " " . $_LANG["billableitemshours"] . " @ ") !== false) {
                $description = substr($description, 0, strrpos($description, " - ")) . " - " . $hours . " " . $_LANG["billableitemshours"] . " @ " . $amount . "/" . $_LANG["billableitemshour"];
            }
            $amount = $amount * $hours;
        }
        update_query("tblbillableitems", array("userid" => $userid, "description" => $description, "hours" => $hours, "amount" => $amount, "recur" => $recur, "recurcycle" => $recurcycle, "recurfor" => $recurfor, "invoiceaction" => $invoiceaction, "duedate" => $duedate, "invoicecount" => $invoicecount), array("id" => $id));
    } else {
        if ($hours != 0) {
            $description .= " - " . $hours . " " . $_LANG["billableitemshours"] . " @ " . $amount . "/" . $_LANG["billableitemshour"];
            $amount = $amount * $hours;
        }
        $id = insert_query("tblbillableitems", array("userid" => $userid, "description" => $description, "hours" => $hours, "amount" => $amount, "recur" => $recur, "recurcycle" => $recurcycle, "recurfor" => $recurfor, "invoiceaction" => $invoiceaction, "duedate" => $duedate));
    }
    redir();
}
if ($action == "delete") {
    check_token("WHMCS.admin.default");
    delete_query("tblbillableitems", array("id" => $id));
    redir();
}
ob_start();
if (!$action) {
    if ($invoice && is_array($bitem)) {
        check_token("WHMCS.admin.default");
        checkPermission("Manage Billable Items");
        foreach ($bitem as $id => $v) {
            update_query("tblbillableitems", array("invoiceaction" => "1"), array("id" => $id));
        }
        infoBox($aInt->lang("billableitems", "invoiceitems"), $aInt->lang("billableitems", "itemswillinvoice"));
        echo $infobox;
    }
    if ($delete && is_array($bitem)) {
        check_token("WHMCS.admin.default");
        checkPermission("Manage Billable Items");
        foreach ($bitem as $id => $v) {
            delete_query("tblbillableitems", array("id" => $id));
        }
        infoBox($aInt->lang("billableitems", "itemsdeleted"), $aInt->lang("billableitems", "itemsdeleteddesc"));
        echo $infobox;
    }
    $aInt->deleteJSConfirm("doDelete", "billableitems", "itemsdeletequestion", "billableitems.php?userid=" . $userid . "&action=delete&id=");
    echo $aInt->beginAdminTabs(array($aInt->lang("global", "searchfilter")));
    echo "\n<form action=\"";
    echo $_SERVER["PHP_SELF"];
    echo "\" method=\"get\">\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"15%\" class=\"fieldlabel\">";
    echo $aInt->lang("fields", "client");
    echo "</td><td class=\"fieldarea\">";
    echo $aInt->clientsDropDown($userid, false, "userid", true);
    echo "</td><td width=\"15%\" class=\"fieldlabel\">";
    echo $aInt->lang("fields", "amount");
    echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"amount\" class=\"form-control input-100\" value=\"";
    echo $amount;
    echo "\"></td></tr>\n<tr><td class=\"fieldlabel\">";
    echo $aInt->lang("fields", "description");
    echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"description\" class=\"form-control input-300\" value=\"";
    echo $description;
    echo "\"></td><td class=\"fieldlabel\">";
    echo $aInt->lang("fields", "status");
    echo "</td><td class=\"fieldarea\"><select name=\"status\" class=\"form-control select-inline\">\n<option value=\"\">";
    echo $aInt->lang("global", "any");
    echo "</option>\n<option";
    if ($status == "Uninvoiced") {
        echo " selected";
    }
    echo ">";
    echo $aInt->lang("status", "uninvoiced");
    echo "</option>\n<option";
    if ($status == "Invoiced") {
        echo " selected";
    }
    echo ">";
    echo $aInt->lang("status", "invoiced");
    echo "</option>\n<option";
    if ($status == "Recurring") {
        echo " selected";
    }
    echo ">";
    echo $aInt->lang("status", "recurring");
    echo "</option>\n<option";
    if ($status == "Active Recurring") {
        echo " selected";
    }
    echo ">";
    echo $aInt->lang("status", "activerecurring");
    echo "</option>\n<option";
    if ($status == "Completed Recurring") {
        echo " selected";
    }
    echo ">";
    echo $aInt->lang("status", "completedrecurring");
    echo "</option>\n</select></td></tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
    echo $aInt->lang("global", "searchfilter");
    echo "\" class=\"btn btn-default\">\n</div>\n\n</form>\n\n";
    echo $aInt->endAdminTabs();
    echo "\n<br />\n\n";
    $aInt->sortableTableInit("id", "DESC");
    $query = WHMCS\Database\Capsule::table("tblbillableitems")->join("tblclients", "tblclients.id", "=", "tblbillableitems.userid");
    $where = array();
    if ($status == "Uninvoiced") {
        $query->where("tblbillableitems.invoicecount", "=", "0");
    } else {
        if ($status == "Invoiced") {
            $query->where("tblbillableitems.invoicecount", ">", "0");
        } else {
            if ($status == "Recurring") {
                $query->where("tblbillableitems.invoiceaction", "=", "4");
            } else {
                if ($status == "Active Recurring") {
                    $query->where("tblbillableitems.invoiceaction", "=", "4")->whereRaw("tblbillableitems.invoicecount < tblbillableitems.recurfor");
                } else {
                    if ($status == "Completed Recurring") {
                        $query->where("tblbillableitems.invoiceaction", "=", "4")->whereRaw("tblbillableitems.invoicecount >= tblbillableitems.recurfor");
                    }
                }
            }
        }
    }
    if ($description) {
        $query->where("tblbillableitems.description", "LIKE", "%" . $description . "%");
    }
    if ($amount) {
        $query->where("tblbillableitems.amount", "=", $amount);
    }
    if ($userid) {
        $query->where("tblbillableitems.userid", "=", $userid);
    }
    $numrows = $query->count("tblbillableitems.id");
    $query->orderBy($orderby, $order)->skip($page * $limit)->limit($limit);
    $rows = $query->get(array("tblbillableitems.*", "tblclients.firstname", "tblclients.lastname", "tblclients.companyname", "tblclients.groupid", "tblclients.currency"));
    foreach ($rows as $row) {
        $data = (array) $row;
        $id = $data["id"];
        $userid = $data["userid"];
        $firstname = $data["firstname"];
        $lastname = $data["lastname"];
        $companyname = $data["companyname"];
        $groupid = $data["groupid"];
        $currency = $data["currency"];
        $description = $data["description"];
        $hours = $data["hours"];
        $amount = $data["amount"];
        $invoiceaction = $data["invoiceaction"];
        $invoicecount = $data["invoicecount"];
        $currency = getCurrency("", $currency);
        $amount = formatCurrency($amount);
        if ($invoiceaction == "0") {
            $invoiceaction = $aInt->lang("billableitems", "dontinvoice");
        } else {
            if ($invoiceaction == "1") {
                $invoiceaction = $aInt->lang("billableitems", "nextcronrun");
            } else {
                if ($invoiceaction == "2") {
                    $invoiceaction = $aInt->lang("billableitems", "nextinvoice");
                } else {
                    if ($invoiceaction == "3") {
                        $invoiceaction = $aInt->lang("billableitems", "invoiceduedate");
                    } else {
                        if ($invoiceaction == "4") {
                            $invoiceaction = $aInt->lang("billableitems", "recurringcycle");
                        }
                    }
                }
            }
        }
        if ($invoicecount) {
            $invoiced = $aInt->lang("global", "yes");
        } else {
            $invoiced = $aInt->lang("global", "no");
        }
        $managelink = "<a href=\"billableitems.php?action=manage&id=" . $id . "\">";
        $tabledata[] = array("<input type=\"checkbox\" name=\"bitem[" . $id . "]\" class=\"checkall\" />", $managelink . $id . "</a>", $aInt->outputClientLink($userid, $firstname, $lastname, $companyname, $groupid), $managelink . $description . "</a>", $hours, $amount, $invoiceaction, $invoiced, $managelink . "<img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "edit") . "\"></a>", "<a href=\"#\" onClick=\"doDelete('" . $id . "');return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\"></a>");
    }
    $tableformurl = $_SERVER["PHP_SELF"] . "?status=" . $status;
    $tableformbuttons = "<input type=\"submit\" name=\"invoice\" value=\"" . $aInt->lang("billableitems", "invoicenextcronrun") . "\" class=\"btn btn-default\" onclick=\"return confirm('" . $aInt->lang("billableitems", "invoicenextcronrunconfirm", "1") . "')\" /> <input type=\"submit\" name=\"delete\" value=\"" . $aInt->lang("global", "delete") . "\" class=\"btn btn-danger\" onclick=\"return confirm('" . $aInt->lang("global", "deleteconfirm", "1") . "')\" />";
    echo $aInt->sortableTable(array("checkall", array("id", $aInt->lang("fields", "id")), $aInt->lang("fields", "clientname"), array("description", $aInt->lang("fields", "description")), array("hours", $aInt->lang("billableitems", "hours")), array("amount", $aInt->lang("fields", "amount")), array("invoiceaction", $aInt->lang("billableitems", "invoiceaction")), array("invoicecount", $aInt->lang("status", "invoiced")), "", ""), $tabledata, $tableformurl, $tableformbuttons);
} else {
    if ($action == "manage") {
        $jquery = "";
        if ($id) {
            $pagetitle = $aInt->lang("billableitems", "edititem");
            $result = select_query("tblbillableitems", "", array("id" => $id));
            $data = mysql_fetch_array($result);
            $id = $data["id"];
            $userid = $data["userid"];
            $description = $data["description"];
            $hours = $data["hours"];
            $amount = $data["amount"];
            if ($hours != 0) {
                $amount = format_as_currency($amount / $hours);
            }
            $recur = $data["recur"];
            $recurcycle = $data["recurcycle"];
            $recurfor = $data["recurfor"];
            $invoiceaction = $data["invoiceaction"];
            $invoicecount = $data["invoicecount"];
            $duedate = fromMySQLDate($data["duedate"]);
        } else {
            $pagetitle = $aInt->lang("billableitems", "additem");
            $clientcheck = get_query_val("tblclients", "id", "");
            if (!$clientcheck) {
                $aInt->gracefulExit($aInt->lang("billableitems", "noclientsmsg"));
            }
            $invoiceaction = 0;
            $recur = 0;
            $duedate = getTodaysDate();
            $hours = "0.0";
            $amount = "0.00";
            $invoicecount = 0;
            $options = "";
            $products = new WHMCS\Product\Products();
            $productsList = $products->getProducts();
            foreach ($productsList as $data) {
                $pid = $data["id"];
                $pname = $data["name"];
                $ptype = $data["groupname"];
                $options .= "<option value=\"" . $pid . "\"";
                if ($package == $pid) {
                    $options .= " selected";
                }
                $options .= ">" . $ptype . " - " . $pname . "</option>";
            }
        }
        echo "<h2>" . $pagetitle . "</h2>";
        $jquerycode = "\$(\".itemselect\").change(function () {\n    var itemid = \$(this).val();\n    var userId = \$(\"select[name='userid']\").val();\n    WHMCS.http.jqClient.post(\"clientsbillableitems.php\", {\n        action: \"getproddesc\",\n        id: itemid,\n        user: userId,\n        token: \"" . generate_token("plain") . "\"\n    },\n    function(data){\n        \$(\"#desc\").val(data);\n    });\n    WHMCS.http.jqClient.post(\"clientsbillableitems.php\", { action: \"getprodprice\", id: itemid, currency: \"" . (int) $currency["id"] . "\", token: \"" . generate_token("plain") . "\" },\n    function(data){\n        \$(\"#rate\").val(data);\n    });\n});";
        echo "\n<form method=\"post\" action=\"";
        echo $_SERVER["PHP_SELF"];
        echo "?action=save&id=";
        echo $id;
        echo "\">\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "client");
        echo "</td><td class=\"fieldarea\">";
        echo $aInt->clientsDropDown($userid);
        echo "</td></tr>\n";
        if (!$id) {
            echo "<tr><td width=\"20%\" class=\"fieldlabel\">";
            echo $aInt->lang("fields", "product");
            echo "</td><td class=\"fieldarea\"><select name=\"pid[]\" class=\"form-control select-inline itemselect\" id=\"i'.\$i.'\"><option value=\"\">";
            echo $aInt->lang("global", "none");
            echo "</option>";
            echo $options;
            echo "</select></td></tr>";
        }
        echo "<tr><td width=\"20%\" class=\"fieldlabel\">";
        echo $aInt->lang("fields", "description");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"description\" value=\"";
        echo $description;
        echo "\" class=\"form-control input-400\" id=\"desc\" /></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("billableitems", "hoursqty");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"hours\" value=\"";
        echo $hours;
        echo "\" class=\"form-control input-100 input-inline\" /> ";
        echo $aInt->lang("billableitems", "hours");
        echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("fields", "amount");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"amount\" value=\"";
        echo $amount;
        echo "\" class=\"form-control input-100\" id=\"rate\" /></td></tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("billableitems", "invoiceaction");
        echo "</td><td class=\"fieldarea\">\n<input type=\"radio\" name=\"invoiceaction\" value=\"0\" id=\"invac0\"";
        if ($invoiceaction == "0") {
            echo " checked";
        }
        echo " /> ";
        echo $aInt->lang("billableitems", "dontinvoicefornow");
        echo "<br />\n<input type=\"radio\" name=\"invoiceaction\" value=\"1\" id=\"invac1\"";
        if ($invoiceaction == "1") {
            echo " checked";
        }
        echo " /> ";
        echo $aInt->lang("billableitems", "invoicenextcronrun");
        echo "<br />\n<input type=\"radio\" name=\"invoiceaction\" value=\"2\" id=\"invac2\"";
        if ($invoiceaction == "2") {
            echo " checked";
        }
        echo " /> ";
        echo $aInt->lang("billableitems", "addnextinvoice");
        echo "<br />\n<input type=\"radio\" name=\"invoiceaction\" value=\"3\" id=\"invac3\"";
        if ($invoiceaction == "3") {
            echo " checked";
        }
        echo " /> ";
        echo $aInt->lang("billableitems", "invoicenormalduedate");
        echo "<br />\n<input type=\"radio\" name=\"invoiceaction\" value=\"4\" id=\"invac4\"";
        if ($invoiceaction == "4") {
            echo " checked";
        }
        echo " /> ";
        echo $aInt->lang("billableitems", "recurevery");
        echo " <input type=\"text\" name=\"recur\" value=\"";
        echo $recur;
        echo "\" class=\"form-control input-50 input-inline\"> <select name=\"recurcycle\" class=\"form-control select-inline\">\n<option value=\"\">";
        echo $aInt->lang("billableitems", "never");
        echo "</option>\n<option value=\"Days\"";
        if ($recurcycle == "Days") {
            echo " selected";
        }
        echo ">";
        echo $aInt->lang("billableitems", "days");
        echo "</option>\n<option value=\"Weeks\"";
        if ($recurcycle == "Weeks") {
            echo " selected";
        }
        echo ">";
        echo $aInt->lang("billableitems", "weeks");
        echo "</option>\n<option value=\"Months\"";
        if ($recurcycle == "Months") {
            echo " selected";
        }
        echo ">";
        echo $aInt->lang("billableitems", "months");
        echo "</option>\n<option value=\"Years\"";
        if ($recurcycle == "Years") {
            echo " selected";
        }
        echo ">";
        echo $aInt->lang("billableitems", "years");
        echo "</option>\n</select> ";
        echo $aInt->lang("global", "for");
        echo " <input type=\"text\" name=\"recurfor\" class=\"form-control input-50 input-inline\" value=\"";
        echo $recurfor;
        echo "\" /> ";
        echo $aInt->lang("billableitems", "times");
        echo "<br />\n</td></tr>\n<tr id=\"duedaterow\">\n    <td class=\"fieldlabel\">";
        echo $aInt->lang("billableitems", "nextduedate");
        echo "</td>\n    <td class=\"fieldarea\">\n        <div class=\"form-group date-picker-prepend-icon\">\n            <label for=\"inputDueDate\" class=\"field-icon\">\n                <i class=\"fal fa-calendar-alt\"></i>\n            </label>\n            <input id=\"inputDueDate\"\n                   type=\"text\"\n                   name=\"duedate\"\n                   value=\"";
        echo $duedate;
        echo "\"\n                   class=\"form-control date-picker-single future\"\n            />\n        </div>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
        echo $aInt->lang("billableitems", "invoicecount");
        echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"invoicecount\" value=\"";
        echo $invoicecount;
        echo "\" class=\"form-control input-100\" /></td></tr>\n</table>\n\n";
        if ($id) {
            $currency = getCurrency($userid);
            $gatewaysarray = getGatewaysArray();
            $aInt->sortableTableInit("nopagination");
            $result = select_query("tblinvoiceitems", "tblinvoices.*", array("type" => "Item", "relid" => $id), "invoiceid", "ASC", "", "tblinvoices ON tblinvoices.id=tblinvoiceitems.invoiceid");
            while ($data = mysql_fetch_array($result)) {
                $invoiceid = $data["id"];
                $date = $data["date"];
                $duedate = $data["duedate"];
                $total = $data["total"];
                $paymentmethod = $data["paymentmethod"];
                $status = $data["status"];
                $date = fromMySQLDate($date);
                $duedate = fromMySQLDate($duedate);
                $total = formatCurrency($total);
                $paymentmethod = $gatewaysarray[$paymentmethod];
                $status = getInvoiceStatusColour($status);
                $invoicelink = "<a href=\"invoices.php?action=edit&id=" . $invoiceid . "\">";
                $tabledata[] = array($invoicelink . $invoiceid . "</a>", $date, $duedate, $total, $paymentmethod, $status, $invoicelink . "<img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "edit") . "\"></a>");
            }
            echo "<h2>" . $aInt->lang("billableitems", "relatedinvoices") . "</h2>" . $aInt->sortableTable(array($aInt->lang("fields", "invoicenum"), $aInt->lang("fields", "invoicedate"), $aInt->lang("fields", "duedate"), $aInt->lang("fields", "total"), $aInt->lang("fields", "paymentmethod"), $aInt->lang("fields", "status"), ""), $tabledata);
        }
        echo "\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
        echo $aInt->lang("global", "savechanges");
        echo "\" class=\"btn btn-primary\" />\n</div>\n\n</form>\n\n";
    }
}
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();

?>