<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$whmcs = WHMCS\Application::getInstance();
$server = (int) $whmcs->getFromRequest("server");
$aInt = new WHMCS\Admin("Domain Resolver Checker");
$aInt->title = AdminLang::trans("utilitiesresolvercheck.domainresolverchecktitle");
$aInt->sidebar = "utilities";
$aInt->icon = "domainresolver";
$aInt->helplink = "Domain Resolver Checker";
$aInt->requiredFiles(array("modulefunctions"));
ob_start();
echo "\n<p>";
echo AdminLang::trans("utilitiesresolvercheck.pagedesc");
echo "</p>\n\n";
if ($step == "") {
    echo "\n<p align=\"center\">\n<form method=\"post\" action=\"";
    echo $whmcs->getPhpSelf();
    echo "?step=2\">\n<select name=\"server\" onChange=\"submit()\" class=\"form-control select-inline\"><option value=\"\">";
    echo AdminLang::trans("global.checkall");
    $result = select_query("tblservers", "", array("disabled" => "0"), "name", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $serverid = $data["id"];
        $servername = $data["name"];
        $activeserver = $data["active"];
        $servermaxaccounts = $data["maxaccounts"];
        $query2 = "SELECT COUNT(id) FROM tblhosting WHERE server=" . (int) $serverid . " AND domainstatus!='Pending' AND domainstatus!='Terminated'";
        $result2 = full_query($query2);
        $data2 = mysql_fetch_array($result2);
        $servernumaccounts = $data2[0];
        echo "<option value=\"" . $serverid . "\"";
        if ($server == $serverid) {
            echo " selected";
        }
        echo ">" . $servername . " (" . $servernumaccounts . " Accounts)";
    }
    echo "</select>\n<input type=\"submit\" value=\"";
    echo AdminLang::trans("utilitiesresolvercheck.runcheck");
    echo " &raquo;\" class=\"btn btn-primary\" />\n</form>\n</p>\n\n";
} else {
    if ($step == "2") {
        check_token("WHMCS.admin.default");
        echo "\n<form method=\"post\" action=\"sendmessage.php?type=product&multiple=true\" id=\"resolverfrm\">\n\n<div class=\"tablebg\">\n<table class=\"datatable\" width=\"100%\" border=\"0\" cellspacing=\"1\" cellpadding=\"3\">\n<tr><th width=\"20\"></th><th>";
        echo AdminLang::trans("fields.domain");
        echo "</th><th>";
        echo AdminLang::trans("fields.ipaddress");
        echo "</th><th>";
        echo AdminLang::trans("utilitiesresolvercheck.package");
        echo "</th><th>";
        echo AdminLang::trans("fields.status");
        echo "</th><th>";
        echo AdminLang::trans("fields.client");
        echo "</th></tr>\n";
        $where = array();
        if ($server) {
            $where = array("id" => $server);
        }
        $result = select_query("tblservers", "", $where, "name", "ASC");
        while ($data = mysql_fetch_array($result)) {
            $serverid = $data["id"];
            $servername = $data["name"];
            $serverip = $data["ipaddress"];
            $serverassignedips = $data["assignedips"];
            $serverusername = $data["username"];
            $serverpassword = $data["password"];
            $serverassignedips = explode("\n", $serverassignedips);
            array_walk($serverassignedips, "assignedips_trim");
            $serverassignedips[] = $serverip;
            echo "<tr><td colspan=\"6\" style=\"background-color:#efefef;font-weight:bold;\">" . $servername . " - " . $serverip . "</td></tr>";
            $serviceid = "";
            $result2 = select_query("tblhosting", "tblhosting.id AS serviceid,tblhosting.domain,tblhosting.domainstatus,tblhosting.userid,tblproducts.name,tblclients.firstname,tblclients.lastname,tblclients.companyname", "server='" . $serverid . "' AND domain!='' AND (domainstatus='Active' OR domainstatus='Suspended')", "domain", "ASC", "", "tblproducts ON tblhosting.packageid=tblproducts.id INNER JOIN tblclients ON tblhosting.userid=tblclients.id");
            while ($data = mysql_fetch_array($result2)) {
                $serviceid = $data["serviceid"];
                $domain = $data["domain"];
                $package = $data["name"];
                $status = $data["domainstatus"];
                $userid = $data["userid"];
                $firstname = $data["firstname"];
                $lastname = $data["lastname"];
                $companyname = $data["companyname"];
                $client = $firstname . " " . $lastname;
                if ($companyname) {
                    $client .= " (" . $companyname . ")";
                }
                $ipaddress = gethostbyname($domain);
                $bgcolor = !in_array($ipaddress, $serverassignedips) ? " style=\"background-color:#ffebeb\"" : "";
                echo "<tr style=\"text-align:center;\"><td" . $bgcolor . "><input type=\"checkbox\" name=\"selectedclients[]\" value=\"" . $serviceid . "\"></td><td" . $bgcolor . "><a href=\"clientshosting.php?userid=" . $userid . "&id=" . $serviceid . "\">" . $domain . "</a></td><td" . $bgcolor . ">" . $ipaddress . "</td><td" . $bgcolor . ">" . $package . "</td><td" . $bgcolor . ">" . $status . "</td><td" . $bgcolor . "><a href=\"clientssummary.php?userid=" . $userid . "\">" . $client . "</a></td></tr>";
            }
            if (!$serviceid) {
                echo "<tr bgcolor=\"#ffffff\"><td colspan=\"6\" align=\"center\">" . AdminLang::trans("global.norecordsfound") . "</td></tr>";
            }
        }
        echo "</table>\n</div>\n\n<p>\n    ";
        echo AdminLang::trans("global.withselected");
        echo ":\n    <input type=\"submit\" value=\"";
        echo AdminLang::trans("global.sendmessage");
        echo "\" class=\"btn btn-default\" />\n    <input type=\"button\" value=\"";
        echo AdminLang::trans("utilitiesresolvercheck.terminateonserver");
        echo "\" data-toggle=\"modal\" data-target=\"#modalTerminateAccounts\" class=\"btn btn-danger\" />\n</p>\n\n</form>\n\n<p>";
        echo AdminLang::trans("utilitiesresolvercheck.dediipwarning");
        echo "</p>\n\n";
        echo $aInt->modal("TerminateAccounts", AdminLang::trans("utilitiesresolvercheck.terminateonserver"), AdminLang::trans("utilitiesresolvercheck.delsureterminateonserver"), array(array("title" => AdminLang::trans("global.yes"), "onclick" => "window.location=\"?step=terminate&\" + jQuery(\"#resolverfrm\").serialize();"), array("title" => AdminLang::trans("global.no"))));
    } else {
        if ($step == "terminate") {
            check_token("WHMCS.admin.default");
            echo "<h3>" . AdminLang::trans("utilitiesresolvercheck.terminatingaccts") . "</h3>\n<ul>";
            if (!is_array($selectedclients)) {
                $selectedclients = array();
            }
            foreach ($selectedclients as $serviceid) {
                $serviceid = (int) $serviceid;
                $result = select_query("tblhosting", "tblhosting.id AS serviceid,tblhosting.domain,tblhosting.domainstatus,tblhosting.userid,tblproducts.name,tblclients.firstname,tblclients.lastname,tblclients.companyname,tblproducts.servertype", array("tblhosting.id" => $serviceid), "", "", "", "tblproducts ON tblhosting.packageid=tblproducts.id INNER JOIN tblclients ON tblhosting.userid=tblclients.id");
                $data = mysql_fetch_array($result);
                $serviceid = $data["serviceid"];
                $domain = $data["domain"];
                $package = $data["name"];
                $status = $data["domainstatus"];
                $userid = $data["userid"];
                $firstname = $data["firstname"];
                $lastname = $data["lastname"];
                $companyname = $data["companyname"];
                $module = $data["servertype"];
                $client = $firstname . " " . $lastname;
                if ($companyname) {
                    $client .= " (" . $companyname . ")";
                }
                if ($module) {
                    if (!isValidforPath($module)) {
                        exit("Invalid Server Module Name");
                    }
                    $modulepath = ROOTDIR . "/modules/servers/" . $module . "/" . $module . ".php";
                    if (file_exists($modulepath)) {
                        require_once $modulepath;
                    }
                }
                $result = ServerTerminateAccount($serviceid);
                if ($result != "success") {
                    $result = "Failed: " . $result;
                } else {
                    $result = "Successful!";
                }
                echo "<li>" . $client . " - " . $package . " (" . $domain . ") - " . $result . "</li>";
            }
            echo "\n</ul>\n<p><b>" . AdminLang::trans("utilitiesresolvercheck.terminatingacctsdone") . "</b><br />" . AdminLang::trans("utilitiesresolvercheck.terminatingacctsdonedesc") . "</p>";
        }
    }
}
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();
function assignedips_trim(&$value)
{
    $value = trim($value);
}

?>