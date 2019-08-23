<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("CLIENTAREA", true);
require "init.php";
$pagetitle = $_LANG["downloadstitle"];
$breadcrumbnav = "<a href=\"" . $CONFIG["SystemURL"] . "/index.php\">" . $_LANG["globalsystemname"] . "</a> > <a href=\"" . $CONFIG["SystemURL"] . "/downloads.php\">" . $_LANG["downloadstitle"] . "</a>";
$pageicon = "images/downloads_big.gif";
$displayTitle = Lang::trans("downloadstitle");
$tagline = Lang::trans("downdoadsdesc");
initialiseClientArea($pagetitle, $displayTitle, $tagline, $pageicon, $breadcrumbnav);
$dlcats = array();
$action = $whmcs->get_req_var("action");
$search = $whmcs->get_req_var("search");
if (isset($catid) && !is_numeric($catid)) {
    redir();
}
if (isset($id) && !is_numeric($id)) {
    redir();
}
$catid = (int) $whmcs->get_req_var("catid");
$proddlrestrict = !$CONFIG["DownloadsIncludeProductLinked"] ? " AND productdownload='0'" : "";
$smartyvalues["seofriendlyurls"] = $CONFIG["SEOFriendlyUrls"];
$usingsupportmodule = false;
if ($CONFIG["SupportModule"]) {
    if (!isValidforPath($CONFIG["SupportModule"])) {
        exit("Invalid Support Module");
    }
    $supportmodulepath = "modules/support/" . $CONFIG["SupportModule"] . "/downloads.php";
    if (file_exists($supportmodulepath)) {
        $usingsupportmodule = true;
        $templatefile = "";
        require $supportmodulepath;
        outputClientArea($templatefile);
        exit;
    }
}
if ($action == "displaycat" || $action == "displayarticle") {
    $result = select_query("tbldownloadcats", "", array("id" => $catid));
    $data = mysql_fetch_array($result);
    $catid = $data["id"];
    if (!$catid) {
        redirSystemURL("", "downloads.php");
    }
    $catparentid = $data["parentid"];
    $catname = $data["name"];
    if ($CONFIG["SEOFriendlyUrls"]) {
        $catbreadcrumbnav = " > <a href=\"downloads/" . $catid . "/" . getModRewriteFriendlyString($catname) . "\">" . $catname . "</a>";
    } else {
        $catbreadcrumbnav = " > <a href=\"downloads.php?action=displaycat&amp;catid=" . $catid . "\">" . $catname . "</a>";
    }
    while ($catparentid != "0") {
        $result = select_query("tbldownloadcats", "", array("id" => $catparentid));
        $data = mysql_fetch_array($result);
        $cattempid = $data["id"];
        $catparentid = $data["parentid"];
        $catname = $data["name"];
        if ($CONFIG["SEOFriendlyUrls"]) {
            $catbreadcrumbnav = " > <a href=\"downloads/" . $cattempid . "/" . getModRewriteFriendlyString($catname) . "\">" . $catname . "</a>" . $catbreadcrumbnav;
        } else {
            $catbreadcrumbnav = " > <a href=\"downloads.php?action=displaycat&amp;catid=" . $cattempid . "\">" . $catname . "</a>" . $catbreadcrumbnav;
        }
    }
    $breadcrumbnav .= $catbreadcrumbnav;
}
if ($action == "search") {
    $breadcrumbnav .= " > <a href=\"downloads.php?action=search&amp;search=" . $search . "\">Search</a>";
}
$smarty->assign("breadcrumbnav", $breadcrumbnav);
$smarty->assign("search", $search);
if ($action == "displaycat") {
    $templatefile = "downloadscat";
    $i = 1;
    for ($result = select_query("tbldownloadcats", "", array("parentid" => $catid, "hidden" => "0"), "name", "ASC"); $data = mysql_fetch_array($result); $i++) {
        $idkb = $data["id"];
        $dlcats[$i] = array("id" => $idkb, "name" => $data["name"], "urlfriendlyname" => getModRewriteFriendlyString($data["name"]), "description" => $data["description"]);
        $idnumbers = array();
        $idnumbers[] = $idkb;
        dlgetcatids($idkb);
        $queryreport = "";
        foreach ($idnumbers as $idnumber) {
            $queryreport .= " OR category='" . $idnumber . "'";
        }
        $queryreport = substr($queryreport, 4);
        $dlcats[$i]["numarticles"] = get_query_val("tbldownloads", "COUNT(*)", "(" . $queryreport . ") AND hidden='0'" . $proddlrestrict);
    }
    $smarty->assign("dlcats", $dlcats);
    $result = select_query("tbldownloads", "", "category=" . $catid . " AND hidden='0'" . $proddlrestrict, "title", "ASC");
    $downloads = createdownloadsarray($result);
    $smarty->assign("downloads", $downloads);
} else {
    if ($action == "search") {
        check_token();
        if (!trim($search)) {
            redir();
        }
        $templatefile = "downloadscat";
        $result = select_query("tbldownloads", "tbldownloads.*", "(title like '%" . db_escape_string($search) . "%' OR tbldownloads.description like '%" . db_escape_string($search) . "%') AND tbldownloads.hidden='0' AND tbldownloadcats.hidden='0'" . $proddlrestrict, "title", "ASC", "", "tbldownloadcats ON tbldownloadcats.id=tbldownloads.category");
        $downloads = createdownloadsarray($result);
        $smarty->assign("search", $search);
        $smarty->assign("downloads", $downloads);
    } else {
        $templatefile = "downloads";
        $i = 1;
        for ($result = select_query("tbldownloadcats", "", array("parentid" => "0", "hidden" => "0"), "name", "ASC"); $data = mysql_fetch_array($result); $i++) {
            $idkb = $data["id"];
            $dlcats[$i] = array("id" => $idkb, "name" => $data["name"], "urlfriendlyname" => getModRewriteFriendlyString($data["name"]), "description" => $data["description"]);
            $idnumbers = array();
            $idnumbers[] = $idkb;
            dlgetcatids($idkb);
            $queryreport = "";
            foreach ($idnumbers as $idnumber) {
                $queryreport .= " OR category='" . $idnumber . "'";
            }
            $queryreport = substr($queryreport, 4);
            $dlcats[$i]["numarticles"] = get_query_val("tbldownloads", "COUNT(*)", "(" . $queryreport . ") AND hidden='0'" . $proddlrestrict);
        }
        $smarty->assign("dlcats", $dlcats);
    }
}
$smarty->assign("breadcrumb", breakBreadcrumbHTMLIntoParts($breadcrumbnav));
$result = select_query("tbldownloads", "tbldownloads.*", "tbldownloadcats.hidden='0' AND tbldownloads.hidden='0'" . $proddlrestrict, "downloads", "DESC", "0,5", "tbldownloadcats ON tbldownloadcats.id=tbldownloads.category");
$downloads = createdownloadsarray($result);
$smarty->assign("mostdownloads", $downloads);
Menu::addContext("downloadCategory", WHMCS\Download\Category::find($catid));
Menu::addContext("topFiveDownloads", WHMCS\Download\Download::topDownloads()->get());
Menu::primarySidebar("downloadList");
Menu::secondarySidebar("downloadList");
outputClientArea($templatefile, false, array("ClientAreaPageDownloads"));
function dlGetCatIds($catid)
{
    global $idnumbers;
    $result = select_query("tbldownloadcats", "id", array("parentid" => $catid, "hidden" => "0"));
    while ($data = mysql_fetch_array($result)) {
        $cid = $data[0];
        $idnumbers[] = $cid;
        dlGetCatIds($cid);
    }
}
function formatFileSize($val, $digits = 3)
{
    $factor = 1024;
    $symbols = array("", "k", "M", "G", "T", "P", "E", "Z", "Y");
    for ($i = 0; $i < count($symbols) - 1 && $factor <= $val; $i++) {
        $val /= $factor;
    }
    $p = strpos($val, ".");
    if ($p !== false && $digits < $p) {
        $val = round($val);
    } else {
        if ($p !== false) {
            $val = round($val, $digits - $p);
        }
    }
    return round($val, $digits) . " " . $symbols[$i] . "B";
}
function createDownloadsArray($result)
{
    global $CONFIG;
    $downloads = array();
    $storage = Storage::downloads();
    while ($data = mysql_fetch_array($result)) {
        $id = $data["id"];
        $category = $data["category"];
        $type = $data["type"];
        $ttitle = $data["title"];
        $description = $data["description"];
        $filename = $data["location"];
        $numdownloads = $data["downloads"];
        $clientsonly = $data["clientsonly"];
        $filesize = $storage->getSize($filename);
        $filesize = formatfilesize($filesize);
        $filenameArr = explode(".", $filename);
        $fileext = end($filenameArr);
        if ($fileext == "doc") {
            $type = "doc";
        }
        if ($fileext == "gif" || $fileext == "jpg" || $fileext == "jpeg" || $fileext == "png") {
            $type = "picture";
        }
        if ($fileext == "txt") {
            $type = "txt";
        }
        if ($fileext == "zip") {
            $type = "zip";
        }
        $type = DI::make("asset")->imgTag($type . ".png", "File", array("align" => "absmiddle"));
        $downloads[] = array("type" => $type, "title" => $ttitle, "urlfriendlytitle" => getModRewriteFriendlyString($ttitle), "description" => $description, "downloads" => $numdownloads, "filesize" => $filesize, "clientsonly" => $clientsonly, "link" => $CONFIG["SystemURL"] . "/dl.php?type=d&amp;id=" . $id);
    }
    return $downloads;
}

?>