<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

$fromserverstatus = true;
if (!defined("CLIENTAREA")) {
    define("CLIENTAREA", true);
    require "init.php";
    $templatepath = ROOTDIR . "/templates/" . $CONFIG["Template"] . "/";
    if (!file_exists($templatepath . "networkissues.tpl") && file_exists($templatepath . "serverstatus.tpl")) {
        redir("", "serverstatus.php");
    }
    $pagetitle = $_LANG["networkissuestitle"];
    $pageicon = "images/clientarea_big.gif";
    $breadcrumbnav = "<a href=\"index.php\">" . $_LANG["globalsystemname"] . "</a> > <a href=\"networkissues.php\">" . $_LANG["networkissuestitle"] . "</a>";
    $fromserverstatus = false;
}
$view = $whmcs->get_req_var("view");
if ($view == "open") {
    $query_where = "status!='Resolved' AND status!='Scheduled'";
    $breadcrumbnav .= " > <a href=\"networkissues.php?view=open\">" . $_LANG["networkissuesstatusopen"] . "</a>";
} else {
    if ($view == "scheduled") {
        $query_where = "status='Scheduled'";
        $breadcrumbnav .= " > <a href=\"networkissues.php?view=scheduled\">" . $_LANG["networkissuesstatusscheduled"] . "</a>";
    } else {
        if ($view == "resolved") {
            $query_where = "status='Resolved'";
            $breadcrumbnav .= " > <a href=\"networkissues.php?view=resolved\">" . $_LANG["networkissuesstatusresolved"] . "</a>";
        } else {
            if (substr($view, 0, 3) == "nid") {
                $nid = str_replace("nid", "", $view);
                $query_where = "id=" . (int) $nid;
            } else {
                $query_where = "status!='Resolved'";
            }
        }
    }
}
if (!$fromserverstatus) {
    initialiseClientArea($pagetitle, $pageicon, $breadcrumbnav);
}
if ($CONFIG["NetworkIssuesRequireLogin"] && !$_SESSION["uid"]) {
    $goto = "networkissues";
    require "login.php";
}
$issueStatusCounts = array();
$result = select_query("tblnetworkissues", "COUNT(*)", "status!='Resolved' AND status!='Scheduled'");
$data = mysql_fetch_array($result);
$smartyvalues["opencount"] = $data[0];
$issueStatusCounts["open"] = $data[0];
$result = select_query("tblnetworkissues", "COUNT(*)", "status='Scheduled'");
$data = mysql_fetch_array($result);
$smartyvalues["scheduledcount"] = $data[0];
$issueStatusCounts["scheduled"] = $data[0];
$result = select_query("tblnetworkissues", "COUNT(*)", "status='Resolved'");
$data = mysql_fetch_array($result);
$smartyvalues["resolvedcount"] = $data[0];
$issueStatusCounts["resolved"] = $data[0];
$users_servers = array();
if (isset($_SESSION["uid"])) {
    $result = select_query("tblhosting", "DISTINCT server", array("userid" => $_SESSION["uid"]));
    while ($data = mysql_fetch_array($result)) {
        if ($data["server"]) {
            $users_servers[] = $data["server"];
        }
    }
}
$result = select_query("tblnetworkissues", "COUNT(*)", $query_where);
$data = mysql_fetch_array($result);
$numitems = $data[0];
list($orderby, $sort, $limit) = clientAreaTableInit("networkissues", "lastupdate", "DESC", $numitems);
$smartyvalues["orderby"] = $orderby;
$smartyvalues["sort"] = strtolower($sort);
$issues = array();
$result = select_query("tblnetworkissues", "", $query_where, $orderby, $sort, $limit);
while ($data = mysql_fetch_array($result)) {
    $startdate = fromMySQLDate($data["startdate"], true);
    $lastupdate = fromMySQLDate($data["lastupdate"], true);
    if (!is_null($data["enddate"])) {
        $enddate = fromMySQLDate($data["enddate"], true);
    } else {
        $enddate = "";
    }
    $priority = $_LANG["networkissuespriority" . strtolower($data["priority"])];
    $status = $_LANG["networkissuesstatus" . str_replace(" ", "", strtolower($data["status"]))];
    $type = $_LANG["networkissuestype" . strtolower($data["type"])];
    $affected = false;
    if ($data["server"]) {
        if (in_array($data["server"], $users_servers)) {
            $affected = true;
        }
        $result2 = select_query("tblservers", "name", array("id" => $data["server"]));
        $data2 = mysql_fetch_array($result2);
        $servername = $data2["name"];
    } else {
        $affected = false;
        $servername = "";
    }
    $issues[] = array("id" => $data["id"], "startdate" => $startdate, "enddate" => $enddate, "title" => $data["title"], "description" => $data["description"], "type" => $type, "affecting" => $data["affecting"], "server" => $servername, "priority" => $priority, "rawPriority" => $data["priority"], "status" => $status, "lastupdate" => $lastupdate, "clientaffected" => $affected);
}
$smartyvalues["issues"] = $issues;
$smartyvalues["view"] = $view;
$smartyvalues = array_merge($smartyvalues, clientAreaTablePageNav($numitems));
$smartyvalues["noissuesmsg"] = sprintf($_LANG["networkstatusnone"], isset($_LANG["networkissuesstatus" . $view]) ? $_LANG["networkissuesstatus" . $view] : "");
if (!$fromserverstatus) {
    $templatefile = "networkissues";
    outputClientArea($templatefile, false, array("ClientAreaPageNetworkIssues"));
}

?>