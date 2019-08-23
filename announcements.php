<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("CLIENTAREA", true);
if (isset($_POST["usessl"])) {
    define("FORCESSL", true);
}
require "init.php";
require "includes/ticketfunctions.php";
require "modules/social/twitter/twitter.php";
$pagetitle = $_LANG["announcementstitle"];
$breadcrumbnav = "<a href=\"" . $whmcs->getSystemURL() . "index.php\">" . $_LANG["globalsystemname"] . "</a> > <a href=\"" . $whmcs->getSystemURL() . "announcements.php\">" . $_LANG["announcementstitle"] . "</a>";
$pageicon = "images/announcements_big.gif";
$displayTitle = Lang::trans("news");
$tagline = Lang::trans("allthelatest") . " " . WHMCS\Config\Setting::getValue("CompanyName");
$id = (int) $whmcs->get_req_var("id");
$action = $whmcs->get_req_var("action");
$page = (int) $whmcs->get_req_var("page");
if ($id) {
    $result = select_query("tblannouncements", "", array("published" => "1", "id" => $id));
    $announcementData = mysql_fetch_array($result);
    $announcementId = $announcementData["id"];
    if (!$announcementId) {
        redir();
    }
    $displayTitle = $announcementData["title"];
    $tagline = "";
}
initialiseClientArea($pagetitle, $displayTitle, $tagline, $pageicon, $breadcrumbnav);
$twitterusername = $CONFIG["TwitterUsername"];
$smartyvalues["twitterusername"] = $CONFIG["TwitterUsername"];
$smartyvalues["twittertweet"] = $CONFIG["AnnouncementsTweet"];
$smartyvalues["facebookrecommend"] = $CONFIG["AnnouncementsFBRecommend"];
$smartyvalues["facebookcomments"] = $CONFIG["AnnouncementsFBComments"];
if ($action == "twitterfeed") {
    $smartyvalues["tweets"] = twitter_getTwitterIntents($twitterusername, WHMCS\Application::getInstance()->getDBVersion());
    $numtweets = $_POST["numtweets"] ? $_POST["numtweets"] : "3";
    $smartyvalues["numtweets"] = $numtweets;
    $template = $whmcs->getClientAreaTemplate()->getName();
    echo processSingleSmartyTemplate($smarty, "/templates/" . $template . "/twitterfeed.tpl", $smartyvalues);
    exit;
}
$smartyvalues["seofriendlyurls"] = $CONFIG["SEOFriendlyUrls"];
$usingsupportmodule = false;
if ($CONFIG["SupportModule"]) {
    if (!isValidforPath($CONFIG["SupportModule"])) {
        exit("Invalid Support Module");
    }
    $supportmodulepath = "modules/support/" . $CONFIG["SupportModule"] . "/announcements.php";
    if (file_exists($supportmodulepath)) {
        $usingsupportmodule = true;
        $templatefile = "";
        require $supportmodulepath;
        outputClientArea($templatefile);
        exit;
    }
}
$activeLanguage = WHMCS\Session::get("Language");
if (!$id) {
    $pagelimit = 10;
    if (!$page) {
        $page = 1;
    }
    $templatefile = "announcements";
    if (!function_exists("ticketsummary")) {
        require ROOTDIR . "/includes/ticketfunctions.php";
    }
    $where = array("published" => "1");
    $userView = $whmcs->get_req_var("view");
    if ($userView) {
        $where["date"] = array("sqltype" => "LIKE", "value" => $userView);
        $smartyvalues["view"] = $userView;
    }
    $announcements = array();
    $result = select_query("tblannouncements", "", $where, "date", "DESC", (int) (($page - 1) * $pagelimit) . "," . (int) $pagelimit);
    while ($data = mysql_fetch_array($result)) {
        $id = $data["id"];
        $date = $data["date"];
        $title = $data["title"];
        $announcement = $data["announcement"];
        if ($activeLanguage) {
            $result2 = select_query("tblannouncements", "", array("parentid" => $id, "language" => $_SESSION["Language"]));
            $data = mysql_fetch_array($result2);
            if ($data["title"]) {
                $title = $data["title"];
            }
            if ($data["announcement"]) {
                $announcement = $data["announcement"];
            }
        }
        $timestamp = strtotime($date);
        $date = fromMySQLDate($date, true);
        $announcements[] = array("id" => $id, "date" => $date, "timestamp" => $timestamp, "title" => $title, "urlfriendlytitle" => getModRewriteFriendlyString($title), "summary" => ticketsummary(strip_tags($announcement), 350), "text" => $announcement);
    }
    $smarty->assign("announcements", $announcements);
    $result = select_query("tblannouncements", "COUNT(*)", array("published" => "1"));
    $data = mysql_fetch_array($result);
    $numannouncements = $data[0];
    $totalpages = ceil($numannouncements / $pagelimit);
    $prevpage = $nextpage = "";
    if ($page != 1) {
        $prevpage = $page - 1;
    }
    if ($page != $totalpages && $numannouncements) {
        $nextpage = $page + 1;
    }
    if (!$totalpages) {
        $totalpages = 1;
    }
    $smarty->assign("numannouncements", $numannouncements);
    $smarty->assign("pagenumber", $page);
    $smarty->assign("totalpages", $totalpages);
    $smarty->assign("prevpage", $prevpage);
    $smarty->assign("nextpage", $nextpage);
} else {
    $templatefile = "viewannouncement";
    $date = $announcementData["date"];
    $title = $announcementData["title"];
    $announcement = $announcementData["announcement"];
    $timestamp = strtotime($date);
    $date = fromMySQLDate($date, true);
    $result2 = select_query("tblannouncements", "", array("parentid" => $announcementId, "language" => WHMCS\Session::get("Language")));
    $data = mysql_fetch_array($result2);
    if ($data["title"]) {
        $title = $data["title"];
    }
    if ($data["announcement"]) {
        $announcement = $data["announcement"];
    }
    $breadcrumbnav = "<a href=\"" . $CONFIG["SystemURL"] . "/index.php\">" . $_LANG["globalsystemname"] . "</a> > <a href=\"" . $CONFIG["SystemURL"] . "/announcements.php\">" . $_LANG["announcementstitle"] . "</a> > <a href=\"" . $CONFIG["SystemURL"] . "/";
    $urlfriendlytitle = getModRewriteFriendlyString($title);
    if ($CONFIG["SEOFriendlyUrls"]) {
        $breadcrumbnav .= "announcements/" . $id . "/" . $urlfriendlytitle . ".html";
    } else {
        $breadcrumbnav .= "announcements.php?id=" . $id;
    }
    $breadcrumbnav .= "\">" . $title . "</a>";
    $smarty->assign("breadcrumbnav", $breadcrumbnav);
    $smarty->assign("id", $id);
    $smarty->assign("date", $date);
    $smarty->assign("timestamp", $timestamp);
    $smarty->assign("displayTitle", $title);
    $smarty->assign("title", $title);
    $smarty->assign("text", $announcement);
    $smarty->assign("urlfriendlytitle", $urlfriendlytitle);
}
Menu::addContext("monthsWithAnnouncements", WHMCS\Announcement\Announcement::getUniqueMonthsWithAnnouncements());
Menu::primarySidebar("announcementList");
Menu::secondarySidebar("announcementList");
outputClientArea($templatefile, false, array("ClientAreaPageAnnouncements"));

?>