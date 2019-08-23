<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Perform Registrar Operations");
$aInt->title = $aInt->lang("domains", "regtransfer");
$aInt->sidebar = "clients";
$aInt->icon = "clientsprofile";
$aInt->requiredFiles(array("clientfunctions", "registrarfunctions"));
if ($action == "do") {
    check_token("WHMCS.admin.default");
}
ob_start();
$result = select_query("tbldomains", "", array("id" => $domainid));
$data = mysql_fetch_array($result);
$domainid = $data["id"];
if (!$domainid) {
    $aInt->gracefulExit("Domain ID Not Found");
}
$userid = $data["userid"];
$domain = $data["domain"];
$orderid = $data["orderid"];
$registrar = $data["registrar"];
$registrationperiod = $data["registrationperiod"];
$domainparts = explode(".", $domain, 2);
$params = array();
$params["domainid"] = $domainid;
list($params["sld"], $params["tld"]) = $domainparts;
$params["regperiod"] = $registrationperiod;
$params["registrar"] = $registrar;
$nsvals = array();
if (!$ns1 && !$ns2) {
    $hostingAccount = Illuminate\Database\Capsule\Manager::table("tblhosting")->where("domain", "=", $domain)->whereIn("domainstatus", array("Active", "Pending"))->first(array("server"));
    $server = (int) $hostingAccount->server;
    if ($server) {
        $result = select_query("tblservers", "", array("id" => $server));
        $data = mysql_fetch_array($result);
        for ($i = 1; $i <= 5; $i++) {
            $nsvals[$i] = $data["nameserver" . $i];
        }
        $autonsdesc = "(" . $aInt->lang("domains", "autonsdesc1") . ")";
    } else {
        for ($i = 1; $i <= 5; $i++) {
            $nsvals[$i] = $CONFIG["DefaultNameserver" . $i];
        }
        $autonsdesc = "(" . $aInt->lang("domains", "autonsdesc2") . ")";
    }
}
$result = select_query("tblorders", "", array("id" => $orderid));
$data = mysql_fetch_array($result);
$nameservers = $data["nameservers"];
if ($nameservers && $nameservers != "," && !$_POST) {
    $nameservers = explode(",", $nameservers);
    for ($i = 1; $i <= 5; $i++) {
        $nsvals[$i] = $nameservers[$i - 1];
    }
    $autonsdesc = "(" . $aInt->lang("domains", "autonsdesc3") . ")";
}
if (!$transfersecret) {
    $transfersecret = $data["transfersecret"];
    $transfersecret = $transfersecret ? safe_unserialize($transfersecret) : array();
    $transfersecret = $transfersecret[$domain];
}
if (is_array($_POST)) {
    for ($i = 1; $i <= 5; $i++) {
        if (isset($_POST["ns" . $i])) {
            $nsvals[$i] = $_POST["ns" . $i];
        }
    }
}
echo "\n<form method=\"post\" action=\"";
echo $whmcs->getPhpSelf();
echo "?domainid=";
echo $domainid;
echo "&action=do&ac=";
echo $ac;
echo "\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"20%\" class=\"fieldlabel\">";
echo $aInt->lang("fields", "registrar");
echo "</td><td class=\"fieldarea\">";
echo ucfirst($registrar);
echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("permissions", "action");
echo "</td><td class=\"fieldarea\">";
if ($ac == "") {
    echo $aInt->lang("domains", "actionreg");
} else {
    echo $aInt->lang("domains", "transfer");
}
echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("fields", "domain");
echo "</td><td class=\"fieldarea\">";
echo $domain;
echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("domains", "regperiod");
echo "</td><td class=\"fieldarea\">";
echo $registrationperiod;
echo " ";
echo $aInt->lang("domains", "years");
echo "</td></tr>\n";
for ($i = 1; $i <= 5; $i++) {
    echo "<tr><td class=\"fieldlabel\">";
    echo $aInt->lang("domains", "nameserver") . " " . $i;
    echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"ns";
    echo $i;
    echo "\" size=\"40\" value=\"";
    echo $nsvals[$i];
    echo "\" /> ";
    if ($i == 1) {
        echo $autonsdesc;
    }
    echo "</td></tr>";
}
if ($ac == "transfer") {
    echo "<tr><td class=\"fieldlabel\">";
    echo $aInt->lang("domains", "eppcode");
    echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"transfersecret\" size=\"20\" value=\"";
    echo WHMCS\Input\Sanitize::makeSafeForOutput($transfersecret);
    echo "\" /> (";
    echo $aInt->lang("domains", "ifreq");
    echo ")</td></tr>";
}
echo "<tr><td class=\"fieldlabel\">";
echo $aInt->lang("orders", "sendconfirmation");
echo "</td><td class=\"fieldarea\"><input type=\"checkbox\" name=\"sendregisterconfirm\" checked /> ";
echo $aInt->lang("domains", "sendregisterconfirm");
echo "</td></tr>\n</table>\n\n";
if ($action == "do") {
    define("NO_QUEUE", true);
    for ($i = 1; $i <= 5; $i++) {
        $params["ns" . $i] = $_POST["ns" . $i];
    }
    $params["transfersecret"] = $_POST["transfersecret"];
    if (!$ac) {
        $result = RegRegisterDomain($params);
    } else {
        $result = RegTransferDomain($params);
    }
    if ($result["error"]) {
        infoBox($aInt->lang("global", "erroroccurred"), $result["error"], "error");
        echo $infobox;
    } else {
        if ($result["pending"]) {
            infoBox($aInt->lang("status", "pending"), $result["pendingMessage"], "info");
        } else {
            if (!$ac) {
                infoBox($aInt->lang("global", "success"), $aInt->lang("domains", "regsuccess"), "success");
            } else {
                infoBox($aInt->lang("global", "success"), $aInt->lang("domains", "transuccess"), "success");
            }
        }
        echo "<br />" . $infobox;
        echo "\n<p align=\"center\"><input type=\"button\" value=\"";
        echo $aInt->lang("global", "continue");
        echo " &raquo;\" class=\"btn btn-default\" onClick=\"window.location='clientsdomains.php?userid=";
        echo $userid;
        echo "&domainid=";
        echo $domainid;
        echo "'\"></p>\n\n";
        if ($sendregisterconfirm == "on") {
            if ($ac == "") {
                sendMessage("Domain Registration Confirmation", $domainid);
            } else {
                sendMessage("Domain Transfer Initiated", $domainid);
            }
        }
        $complete = "true";
    }
}
if ($complete != "true") {
    $replace = $ac == "" ? $aInt->lang("domains", "actionreg") : $aInt->lang("domains", "transfer");
    $question = str_replace("%s", $replace, $aInt->lang("domains", "actionquestion"));
    echo "\n<p align=center>";
    echo $question;
    echo "</p>\n<p align=center><input type=\"submit\" value=\" ";
    echo $aInt->lang("global", "yes");
    echo " \" class=\"btn btn-success\"> <input type=\"button\" value=\" ";
    echo $aInt->lang("global", "no");
    echo " \" class=\"btn btn-default\" onClick=\"window.location='clientsdomains.php?userid=";
    echo $userid;
    echo "&domainid=";
    echo $domainid;
    echo "'\">\n\n";
}
echo "\n</form>\n\n";
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->display();

?>