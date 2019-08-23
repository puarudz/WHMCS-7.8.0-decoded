<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("CLIENTAREA", true);
require "init.php";
$userId = WHMCS\Session::get("uid");
$isAdmin = WHMCS\Session::get("adminid");
$contactID = WHMCS\Session::get("cid");
if (!$userId) {
    if ($isAdmin && $whmcs->get_req_var("returntoadmin")) {
        $whmcs->redirect(App::get_admin_folder_name(), array());
    }
    redir("", "index.php");
}
$hookParams = array("userid" => $userId);
$hookParams["contactid"] = $contactID ? $contactID : 0;
run_hook("ClientLogout", $hookParams);
WHMCS\Session::delete("uid");
WHMCS\Session::delete("cid");
WHMCS\Session::delete("upw");
WHMCS\Cookie::delete("User");
if ($isAdmin && $whmcs->get_req_var("returntoadmin")) {
    $whmcs->redirect(App::get_admin_folder_name() . "/clientssummary.php", array("userid" => $userId));
}
if (App::getFromRequest("redirect")) {
    header("Location: " . routePath(App::getFromRequest("redirect")));
    exit;
}
$pagetitle = $_LANG["logouttitle"];
$breadcrumbnav = "<a href=\"index.php\">" . $_LANG["globalsystemname"] . "</a> > <a href=\"clientarea.php\">" . $_LANG["clientareatitle"] . "</a> > <a href=\"logout.php\">" . $_LANG["logouttitle"] . "</a>";
$pageicon = "images/clientarea_big.gif";
$templatefile = "logout";
$displayTitle = Lang::trans("logouttitle");
$tagline = "";
initialiseClientArea($pagetitle, $displayTitle, $tagline, $pageicon, $breadcrumbnav);
$smartyvalues["showingLoginPage"] = true;
outputClientArea($templatefile, false, array("ClientAreaPageLogout"));

?>