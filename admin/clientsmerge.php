<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Edit Clients Details");
$aInt->requiredFiles(array("clientfunctions"));
$aInt->title = $aInt->lang("clients", "mergeclient");
ob_start();
if (!$newuserid) {
    echo "<script type=\"text/javascript\">\n\$(document).ready(function(){\n    \$(\"#clientsearchval\").keyup(function () {\n        var useridsearchlength = \$(\"#clientsearchval\").val().length;\n        if (useridsearchlength>2) {\n        WHMCS.http.jqClient.post(whmcsBaseUrl + adminBaseRoutePath + \"/search.php\", { clientsearch: 1, value: \$(\"#clientsearchval\").val(), token: \"" . generate_token("plain") . "\" },\n            function(data){\n                if (data) {\n                    \$(\"#clientsearchresults\").html(data);\n                    \$(\"#clientsearchresults\").slideDown(\"slow\");\n                }\n            });\n        }\n    });\n});\nfunction searchselectclient(userid,name,email) {\n    \$(\"#newuserid\").val(userid);\n    \$(\"#clientsearchresults\").slideUp(\"slow\");\n}\n\nvar whmcsBaseUrl = \"" . WHMCS\Utility\Environment\WebHelper::getBaseUrl() . "\";\nvar adminBaseRoutePath = \"" . WHMCS\Admin\AdminServiceProvider::getAdminRouteBase() . "\";\n</script>\n";
    if ($error) {
        echo "<div class=\"errorbox\">" . $aInt->lang("clients", "invalidid") . "</div><br />";
    }
    echo "\n<p>";
    echo $aInt->lang("clients", "mergeexplain");
    echo "</p>\n\n<form method=\"post\" action=\"";
    echo $whmcs->getPhpSelf();
    echo "?userid=";
    echo $userid;
    echo "\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td class=\"fieldlabel\">";
    echo $aInt->lang("clients", "firstclient");
    echo "</td><td class=\"fieldarea\">";
    $result = select_query("tblclients", "", array("id" => $userid));
    $data = mysql_fetch_array($result);
    $useridselect = $data["id"];
    $firstname = $data["firstname"];
    $lastname = $data["lastname"];
    echo (string) $firstname . " " . $lastname . " (" . $useridselect . ")";
    echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
    echo $aInt->lang("clients", "secondclient");
    echo "</td><td class=\"fieldarea\"><table cellspacing=\"0\" cellpadding=\"0\"><tr><td><input type=\"text\" name=\"newuserid\" id=\"newuserid\" size=\"10\" /></td><td>&nbsp;&nbsp; <input type=\"submit\" value=\"";
    echo $aInt->lang("invoices", "merge");
    echo "\" class=\"button btn btn-default\" /></td></tr></table></td></tr>\n<tr><td class=\"fieldarea\" colspan=\"2\"><div align=\"center\"><input type=\"radio\" name=\"mergemethod\" value=\"to1\" id=\"to1\" /> <label for=\"to1\">";
    echo $aInt->lang("clients", "tofirst");
    echo "</label> <input type=\"radio\" name=\"mergemethod\" value=\"to2\" id=\"to2\" checked /> <label for=\"to2\">";
    echo $aInt->lang("clients", "tosecond");
    echo "</label></div></td></tr>\n</table>\n\n<br />\n<div align=\"center\">";
    echo $aInt->lang("global", "clientsintellisearch");
    echo ": <input type=\"text\" id=\"clientsearchval\" size=\"25\" /></div>\n<br />\n<div id=\"clientsearchresults\">\n<div class=\"searchresultheader\">Search Results</div>\n<div class=\"searchresult\" align=\"center\">Matches will appear here as you type</div>\n</div>\n\n</form>\n\n";
} else {
    check_token("WHMCS.admin.default");
    $newuserid = trim($newuserid);
    $result = select_query("tblclients", "id", array("id" => $newuserid));
    $data = mysql_fetch_array($result);
    $newuserid = $data["id"];
    if (!$newuserid) {
        redir("userid=" . $userid . "&error=1");
    }
    if ($mergemethod == "to1") {
        $resultinguserid = trim($userid);
        $deleteuser = trim($newuserid);
    } else {
        $resultinguserid = trim($newuserid);
        $deleteuser = trim($userid);
    }
    $tables_array = array("tblaccounts", "tblcontacts", "tbldomains", "tblemails", "tblhosting", "tblhostingaddons", "tblinvoiceitems", "tblinvoices", "tblnotes", "tblorders", "tblquotes", "tblticketreplies", "tbltickets", "tblactivitylog", "tblsslorders", "tblclientsfiles", "tblbillableitems");
    foreach ($tables_array as $table) {
        update_query($table, array("userid" => $resultinguserid), array("userid" => $userid));
    }
    update_query("tblcredit", array("clientid" => $resultinguserid), array("clientid" => $userid));
    $userid = $newuserid;
    $result = select_query("tblclients", "credit", array("id" => $deleteuser));
    $data = mysql_fetch_array($result);
    $credit = $data[0];
    update_query("tblclients", array("credit" => "+=" . $credit), array("id" => (int) $resultinguserid));
    WHMCS\Payment\PayMethod\Model::where("userid", $deleteuser)->update(array("userid" => $resultinguserid));
    $result = select_query("tblaffiliates", "", array("clientid" => $deleteuser));
    $data = mysql_fetch_array($result);
    $affid = $data["id"];
    if ($affid) {
        $visitors = $data["visitors"];
        $balance = $data["balance"];
        $withdrawn = $data["withdrawn"];
        $result = select_query("tblaffiliates", "", array("clientid" => $resultinguserid));
        $data = mysql_fetch_array($result);
        $newaffid = $data["id"];
        if (!$newaffid) {
            $newaffid = insert_query("tblaffiliates", array("date" => "now()", "clientid" => $resultinguserid));
        }
        update_query("tblaffiliates", array("visitors" => "+=" . (int) $visitors, "balance" => "+=" . $balance, "withdrawn" => "+=" . $withdrawn), array("id" => (int) $newaffid));
        update_query("tblaffiliatesaccounts", array("affiliateid" => $newaffid), array("affiliateid" => $affid));
        update_query("tblaffiliateshistory", array("affiliateid" => $newaffid), array("affiliateid" => $affid));
        update_query("tblaffiliateswithdrawals", array("affiliateid" => $newaffid), array("affiliateid" => $affid));
        delete_query("tblaffiliates", array("clientid" => $deleteuser));
    }
    logActivity("Merged User ID: " . $deleteuser . " with User ID: " . $resultinguserid, $resultinguserid);
    run_hook("AfterClientMerge", array("toUserID" => $resultinguserid, "fromUserID" => $deleteuser));
    if ($resultinguserid != $deleteuser) {
        deleteClient($deleteuser);
    }
    echo "<script language=\"javascript\">\nwindow.opener.location.href = \"clientssummary.php?userid=";
    echo $resultinguserid;
    echo "\";\nwindow.close();\n</script>\n";
}
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->displayPopUp();

?>