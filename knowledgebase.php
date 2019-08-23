<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("CLIENTAREA", true);
require "init.php";
$pagetitle = $_LANG["knowledgebasetitle"];
$breadcrumbnav = "<a href=\"" . $CONFIG["SystemURL"] . "/index.php\">" . $_LANG["globalsystemname"] . "</a> > <a href=\"" . $CONFIG["SystemURL"] . "/knowledgebase.php\">" . $_LANG["knowledgebasetitle"] . "</a>";
$pageicon = "images/knowledgebase_big.gif";
$displayTitle = Lang::trans("knowledgebasetitle");
$tagline = "";
initialiseClientArea($pagetitle, $displayTitle, $tagline, $pageicon, $breadcrumbnav);
$action = $whmcs->get_req_var("action");
$searchin = $whmcs->get_req_var("searchin");
$useful = $whmcs->get_req_var("useful");
$vote = $whmcs->get_req_var("vote") ?: (int) WHMCS\Session::getAndDelete("knowledgebaseArticleVoted");
$kbcats = $kbmostviews = $kbarticles = array();
if (isset($catid) && !is_numeric($catid)) {
    redirSystemURL("", "knowledgebase.php");
}
if (isset($id) && !is_numeric($id)) {
    redirSystemURL("", "knowledgebase.php");
}
$id = (int) $whmcs->get_req_var("id");
$catid = (int) $whmcs->get_req_var("catid");
$kbcid = (int) $whmcs->get_req_var("kbcid");
$usingsupportmodule = false;
if ($CONFIG["SupportModule"]) {
    if (!isValidforPath($CONFIG["SupportModule"])) {
        exit("Invalid Support Module");
    }
    $supportmodulepath = "modules/support/" . $CONFIG["SupportModule"] . "/knowledgebase.php";
    if (file_exists($supportmodulepath)) {
        $usingsupportmodule = true;
        $templatefile = "";
        require $supportmodulepath;
        outputClientArea($templatefile);
        exit;
    }
}
if ($action == "search" && $searchin == "Downloads") {
    redirSystemURL("action=search&search=" . $search, "downloads.php");
}
$smartyvalues["seofriendlyurls"] = $CONFIG["SEOFriendlyUrls"];
$i = 1;
$kbRootCategories = array();
for ($result = select_query("tblknowledgebasecats", "", array("parentid" => "0", "hidden" => "", "catid" => 0), "name", "ASC"); $data = mysql_fetch_array($result); $i++) {
    $root_id = $data["id"];
    $root_name = $data["name"];
    $root_desc = $data["description"];
    $result2 = select_query("tblknowledgebasecats", "", array("catid" => $root_id, "language" => WHMCS\Session::get("Language")));
    $data = mysql_fetch_array($result2);
    if ($data["name"]) {
        $root_name = $data["name"];
    }
    if ($data["description"]) {
        $root_desc = $data["description"];
    }
    $idnumbers = array($root_id);
    kbgetcatids($root_id);
    $where = array();
    foreach ($idnumbers as $idnumber) {
        $where[] = "categoryid=" . (int) $idnumber;
    }
    $result2 = select_query("tblknowledgebase", "COUNT(*)", implode(" OR ", $where), "", "", "", "tblknowledgebaselinks ON tblknowledgebase.id=tblknowledgebaselinks.articleid");
    $data2 = mysql_fetch_array($result2);
    $articlesCount = $data2[0];
    $kbRootCategories[$i] = array("id" => $root_id, "name" => $root_name, "urlfriendlyname" => getModRewriteFriendlyString($root_name), "description" => $root_desc, "numarticles" => $articlesCount);
}
Menu::addContext("kbRootCategories", $kbRootCategories);
$tagForLookup = WHMCS\Input\Sanitize::decode($whmcs->get_req_var("tag"));
$tagName = WHMCS\Input\Sanitize::makeSafeForOutput($tagForLookup);
if ($tagName) {
    $templatefile = "knowledgebasecat";
    $breadcrumbnav .= " > <a href=\"" . WHMCS\Utility\Environment\WebHelper::getBaseUrl() . "/knowledgebase.php?tag=" . $tagName . "\">" . Lang::trans("kbviewingarticlestagged") . " '" . $tagName . "'</a>";
    $smartyvalues["breadcrumbnav"] = $breadcrumbnav;
    $smartyvalues["tag"] = $tagName;
    $smartyvalues["kbcats"] = array();
    $result = select_query("tblknowledgebase", "tblknowledgebase.*", array("tag" => $tagForLookup), "order` ASC,`title", "ASC", "", "tblknowledgebasetags ON tblknowledgebase.id=tblknowledgebasetags.articleid");
    while ($data = mysql_fetch_assoc($result)) {
        $id = $data["id"];
        $category = $data["category"];
        $title = $data["title"];
        $article = $data["article"];
        $views = $data["views"];
        $result2 = select_query("tblknowledgebase", "", array("parentid" => $id, "language" => WHMCS\Session::get("Language")));
        $data = mysql_fetch_array($result2);
        if ($data["title"]) {
            $title = $data["title"];
        }
        if ($data["article"]) {
            $article = $data["article"];
        }
        $kbarticles[] = array("id" => $id, "category" => $category, "title" => $title, "urlfriendlytitle" => getModRewriteFriendlyString($title), "article" => strip_tags($article), "views" => $views);
    }
    $smartyvalues["kbarticles"] = $kbarticles;
} else {
    if ($action == "displaycat") {
        $templatefile = "knowledgebasecat";
        $result = select_query("tblknowledgebasecats", "", array("id" => $catid, "hidden" => "", "catid" => 0));
        $data = mysql_fetch_array($result);
        $catid = $data["id"];
        if (!$catid) {
            redirSystemURL("", "knowledgebase.php");
        }
        $smartyvalues["catid"] = $catid;
        Menu::addContext("kbCategoryId", $catid);
        $catparentid = $data["parentid"];
        $catname = $data["name"];
        $result2 = select_query("tblknowledgebasecats", "", array("catid" => $catid, "language" => WHMCS\Session::get("Language")));
        $data = mysql_fetch_array($result2);
        if ($data["name"]) {
            $catname = $data["name"];
        }
        $smartyvalues["catname"] = $catname;
        $smartyvalues["tag"] = "";
        $categoryUri = WHMCS\Utility\Environment\WebHelper::getBaseUrl();
        $categoryUri .= WHMCS\Config\Setting::getValue("SEOFriendlyUrls") ? "/knowledgebase/" . $catid . "/" . getModRewriteFriendlyString($catname) : "/knowledgebase.php?action=displaycat&amp;catid=" . $catid;
        $catbreadcrumbnav = " > <a href=\"" . $categoryUri . "\">" . $catname . "</a>";
        $i = 0;
        $cattempid = 0;
        while ($catparentid != "0") {
            $result = select_query("tblknowledgebasecats", "", array("id" => $catparentid));
            $data = mysql_fetch_array($result);
            $cattempid = $data["id"];
            $catparentid = $data["parentid"];
            $catname = $data["name"];
            $result2 = select_query("tblknowledgebasecats", "", array("catid" => $cattempid, "language" => WHMCS\Session::get("Language")));
            $data = mysql_fetch_array($result2);
            if ($data["name"]) {
                $catname = $data["name"];
            }
            $categoryUri = WHMCS\Utility\Environment\WebHelper::getBaseUrl();
            $categoryUri .= WHMCS\Config\Setting::getValue("SEOFriendlyUrls") ? "/knowledgebase/" . $cattempid . "/" . getModRewriteFriendlyString($catname) : "/knowledgebase.php?action=displaycat&amp;catid=" . $cattempid;
            $catbreadcrumbnav = " > <a href=\"" . $categoryUri . "\">" . $catname . "</a>" . $catbreadcrumbnav;
            $i++;
            if (100 < $i) {
                break;
            }
        }
        Menu::addContext("kbCategoryParentId", (int) $cattempid);
        $breadcrumbnav .= $catbreadcrumbnav;
        $smarty->assign("breadcrumbnav", $breadcrumbnav);
        $smartyvalues["searchterm"] = "";
        $i = 1;
        for ($result = select_query("tblknowledgebasecats", "", array("parentid" => $catid, "hidden" => "", "catid" => 0), "name", "ASC"); $data = mysql_fetch_array($result); $i++) {
            $idkb = $data["id"];
            $name = $data["name"];
            $description = $data["description"];
            $result2 = select_query("tblknowledgebasecats", "", array("catid" => $idkb, "language" => WHMCS\Session::get("Language")));
            $data = mysql_fetch_array($result2);
            if ($data["name"]) {
                $name = $data["name"];
            }
            if ($data["description"]) {
                $description = $data["description"];
            }
            $kbcats[$i] = array("id" => $idkb, "name" => $name, "urlfriendlyname" => getModRewriteFriendlyString($name), "description" => $description);
            $idnumbers = array();
            $idnumbers[] = $idkb;
            kbgetcatids($idkb);
            $queryreport = "";
            foreach ($idnumbers as $idnumber) {
                $queryreport .= " OR categoryid='" . $idnumber . "'";
            }
            $queryreport = substr($queryreport, 4);
            $result2 = select_query("tblknowledgebase", "COUNT(*)", "(" . $queryreport . ")", "", "", "", "tblknowledgebaselinks ON tblknowledgebase.id=tblknowledgebaselinks.articleid");
            $data2 = mysql_fetch_array($result2);
            $categorycount = $data2[0];
            $kbcats[$i]["numarticles"] = $categorycount;
        }
        Menu::addContext("kbCategories", $kbcats);
        $smarty->assign("kbcats", $kbcats);
        $query = "SELECT *\n        FROM `tblknowledgebase` AS `b`, `tblknowledgebaselinks` AS `l`, `tblknowledgebasecats` AS `c`\n        WHERE `l`.`categoryid` = '" . (int) $catid . "'\n        AND `l`.`articleid`  = `b`.`id`\n        AND `l`.`categoryid` = `c`.`id`\n        AND `b`.`id`\n        NOT IN (\n            SELECT `l`.`articleid`\n            FROM `tblknowledgebaselinks` AS `l`, `tblknowledgebasecats` AS `c`\n            WHERE `l`.`categoryid` = `c`.`id`\n            AND `c`.`hidden` = 'on'\n        )\n        ORDER BY `order` ASC, `title` ASC";
        $rows = Illuminate\Database\Capsule\Manager::select($query);
        foreach ($rows as $data) {
            $id = $data->id;
            $articleId = $data->articleid;
            $title = $data->title;
            $article = $data->article;
            $views = $data->views;
            $result2 = select_query("tblknowledgebase", "", array("parentid" => $articleId, "language" => WHMCS\Session::get("Language")));
            $translatedData = mysql_fetch_array($result2);
            if ($translatedData["title"]) {
                $title = $translatedData["title"];
            }
            if ($translatedData["article"]) {
                $article = $translatedData["article"];
            }
            $kbarticles[] = array("id" => $articleId, "title" => $title, "urlfriendlytitle" => getModRewriteFriendlyString($title), "article" => strip_tags($article), "views" => $views);
        }
        $smarty->assign("kbarticles", $kbarticles);
    } else {
        if ($action == "search") {
            if ($whmcs->get_req_var("redirect")) {
                $search = WHMCS\Session::getAndDelete("kbSearchTerm");
                if (!$search) {
                    redir();
                }
            } else {
                check_token();
            }
            $templatefile = "knowledgebasecat";
            $catid = (int) $catid;
            if ($kbcid) {
                $catid = $kbcid;
            } else {
                if (!$catid) {
                    $catid = 0;
                }
            }
            $idnumbers = array();
            $idnumbers[] = $catid;
            kbgetcatids($catid);
            if ($catid) {
                $smartyvalues["catid"] = $catid;
                $catparentid = $catid;
                $i = 0;
                while ($catparentid != "0") {
                    $result = select_query("tblknowledgebasecats", "", array("id" => $catparentid));
                    $data = mysql_fetch_array($result);
                    $cattempid = $data["id"];
                    $catparentid = $data["parentid"];
                    $catname = $data["name"];
                    $result2 = select_query("tblknowledgebasecats", "", array("catid" => $cattempid, "language" => WHMCS\Session::get("Language")));
                    $data = mysql_fetch_array($result2);
                    if ($data["name"]) {
                        $catname = $data["name"];
                    }
                    $categoryUri = WHMCS\Utility\Environment\WebHelper::getBaseUrl();
                    $categoryUri .= WHMCS\Config\Setting::getValue("SEOFriendlyUrls") ? "/knowledgebase/" . $cattempid . "/" . getModRewriteFriendlyString($catname) : "/knowledgebase.php?action=displaycat&amp;catid=" . $cattempid;
                    $catbreadcrumbnav = " > <a href=\"" . $categoryUri . "\">" . $catname . "</a>" . $catbreadcrumbnav;
                    $i++;
                    if (100 < $i) {
                        break;
                    }
                }
                $breadcrumbnav .= $catbreadcrumbnav;
            }
            $breadcrumbnav .= " > <a href=\"" . WHMCS\Utility\Environment\WebHelper::getBaseUrl() . "/knowledgebase.php?action=search&amp;search=" . $search . "\">Search</a>";
            $smarty->assign("breadcrumbnav", $breadcrumbnav);
            $kbarticles = array();
            $smartyvalues["searchterm"] = $search;
            $smartyvalues["tag"] = "";
            $searchterms = array();
            $searchparts = explode(" ", WHMCS\Input\Sanitize::decode($search));
            foreach ($searchparts as $searchpart) {
                if ($searchpart) {
                    $searchterms[] = "(title LIKE '%" . db_escape_string($searchpart) . "%' OR article LIKE '%" . db_escape_string($searchpart) . "%')";
                }
            }
            $searchqry = implode(" AND ", $searchterms);
            if (!$searchqry) {
                $searchqry = "id='x'";
            }
            $query = "SELECT DISTINCT id FROM tblknowledgebase WHERE " . $searchqry . " AND (SELECT categoryid FROM tblknowledgebaselinks WHERE ((articleid=tblknowledgebase.id) OR (articleid=tblknowledgebase.parentid)) LIMIT 1) IN (" . db_build_in_array($idnumbers) . ") ORDER BY `order` ASC,`title` ASC";
            $result = full_query($query);
            $articleids = array();
            while ($data = mysql_fetch_array($result)) {
                $id = $data["id"];
                $result2 = select_query("tblknowledgebase", "", array("id" => $id));
                $data = mysql_fetch_array($result2);
                $title = $data["title"];
                $article = $data["article"];
                $views = $data["views"];
                $parentid = $data["parentid"];
                if ($parentid) {
                    $result2 = select_query("tblknowledgebase", "", array("id" => $parentid));
                    $data = mysql_fetch_array($result2);
                    $id = $data["id"];
                    $title = $data["title"];
                    $article = $data["article"];
                    $views = $data["views"];
                }
                $result2 = select_query("tblknowledgebasecats", "tblknowledgebasecats.hidden", array("articleid" => $id, "hidden" => "on"), "", "", "", "tblknowledgebaselinks ON tblknowledgebaselinks.categoryid=tblknowledgebasecats.id");
                $data = mysql_fetch_array($result2);
                if (!$data["hidden"] && !in_array($id, $articleids)) {
                    $result2 = select_query("tblknowledgebase", "", array("parentid" => $id, "language" => WHMCS\Session::get("Language")));
                    $data = mysql_fetch_array($result2);
                    if ($data["title"]) {
                        $title = $data["title"];
                    }
                    if ($data["article"]) {
                        $article = $data["article"];
                    }
                    $kbarticles[] = array("id" => $id, "title" => $title, "urlfriendlytitle" => getModRewriteFriendlyString($title), "article" => strip_tags($article), "views" => $views);
                    $articleids[] = $id;
                }
            }
            $smarty->assign("kbcats", array());
            $smarty->assign("kbarticles", $kbarticles);
        } else {
            if ($action == "displayarticle") {
                $templatefile = "knowledgebasearticle";
                if ($useful == "vote") {
                    if ($vote == "yes") {
                        update_query("tblknowledgebase", array("useful" => "+1"), array("id" => $id));
                    }
                    update_query("tblknowledgebase", array("votes" => "+1"), array("id" => $id));
                    $articleTitle = Illuminate\Database\Capsule\Manager::table("tblknowledgebase")->find($id, array("title"));
                    WHMCS\Session::set("knowledgebaseArticleVoted", true);
                    WHMCS\Config\Setting::getValue("SEOFriendlyUrls");
                    WHMCS\Config\Setting::getValue("SEOFriendlyUrls") ? $whmcs->redirect(getModRewriteFriendlyString($articleTitle->title) . ".html") : $whmcs->redirect(NULL, "action=displayarticle&id=" . $id);
                }
                update_query("tblknowledgebase", array("views" => "+1"), array("id" => $id));
                $result = select_query("tblknowledgebase", "", array("id" => $id));
                $data = mysql_fetch_array($result);
                $id = $data["id"];
                $title = $data["title"];
                $article = $data["article"];
                $views = $data["views"];
                $useful = $data["useful"];
                $votes = $data["votes"];
                $private = $data["private"];
                if (!$id) {
                    redirSystemURL("", "knowledgebase.php");
                }
                $result = select_query("tblknowledgebasecats", "tblknowledgebasecats.id,tblknowledgebasecats.name,tblknowledgebasecats.parentid,tblknowledgebasecats.hidden", array("articleid" => $id), "", "", "", "tblknowledgebaselinks ON tblknowledgebasecats.id=tblknowledgebaselinks.categoryid");
                $data = mysql_fetch_array($result);
                $catid = $data["id"];
                Menu::addContext("kbCategoryId", $catid);
                $catname = $data["name"];
                $catparentid = $data["parentid"];
                $hidden = $data["hidden"];
                if ($hidden) {
                    redirSystemURL("", "knowledgebase.php");
                }
                $result2 = select_query("tblknowledgebasecats", "", array("catid" => $catid, "language" => WHMCS\Session::get("Language")));
                $data = mysql_fetch_array($result2);
                if ($data["name"]) {
                    $catname = $data["name"];
                }
                $categoryUri = WHMCS\Utility\Environment\WebHelper::getBaseUrl();
                $categoryUri .= WHMCS\Config\Setting::getValue("SEOFriendlyUrls") ? "/knowledgebase/" . $catid . "/" . getModRewriteFriendlyString($catname) : "/knowledgebase.php?action=displaycat&amp;catid=" . $catid;
                $catbreadcrumbnav = " > <a href=\"" . $categoryUri . "\">" . $catname . "</a>";
                $cattempid = 0;
                if ($catparentid) {
                    $i = 0;
                    while ($catparentid != "0") {
                        $result = select_query("tblknowledgebasecats", "", array("id" => $catparentid));
                        $data = mysql_fetch_array($result);
                        $cattempid = $data["id"];
                        $catparentid = $data["parentid"];
                        $catname = $data["name"];
                        $result2 = select_query("tblknowledgebasecats", "", array("catid" => $cattempid, "language" => WHMCS\Session::get("Language")));
                        $data = mysql_fetch_array($result2);
                        if ($data["name"]) {
                            $catname = $data["name"];
                        }
                        $categoryUri = WHMCS\Utility\Environment\WebHelper::getBaseUrl();
                        $categoryUri .= WHMCS\Config\Setting::getValue("SEOFriendlyUrls") ? "/knowledgebase/" . $cattempid . "/" . getModRewriteFriendlyString($catname) : "/knowledgebase.php?action=displaycat&amp;catid=" . $cattempid;
                        $catbreadcrumbnav = " > <a href=\"" . $categoryUri . "\">" . $catname . "</a>" . $catbreadcrumbnav;
                        $i++;
                        if (100 < $i) {
                            break;
                        }
                    }
                }
                Menu::addContext("kbCategoryParentId", (int) $cattempid);
                $result2 = select_query("tblknowledgebase", "", array("parentid" => $id, "language" => WHMCS\Session::get("Language")));
                $data = mysql_fetch_array($result2);
                if ($data["title"]) {
                    $title = $data["title"];
                }
                if ($data["article"]) {
                    $article = $data["article"];
                }
                $categoryUri = WHMCS\Utility\Environment\WebHelper::getBaseUrl();
                $categoryUri .= WHMCS\Config\Setting::getValue("SEOFriendlyUrls") ? "/knowledgebase/" . $id . "/" . getModRewriteFriendlyString($title) . ".html" : "/knowledgebase.php?action=displaycat&amp;catid=" . $id;
                $catbreadcrumbnav .= " > <a href=\"" . $categoryUri . "\">" . $title . "</a>";
                $breadcrumbnav .= $catbreadcrumbnav;
                $smarty->assign("breadcrumbnav", $breadcrumbnav);
                if (!WHMCS\Session::get("uid") && $private == "on") {
                    $goto = "knowledgebase";
                    include "login.php";
                }
                $smartyvalues["kbarticle"] = array("id" => $id, "categoryid" => $catid, "categoryname" => $catname, "title" => $title, "urlfriendlytitle" => getModRewriteFriendlyString($title), "text" => $article, "views" => $views, "useful" => $useful, "votes" => $votes, "voted" => $vote);
                $catlist = array();
                $result = select_query("tblknowledgebaselinks", "", array("articleid" => $id));
                while ($data = mysql_fetch_assoc($result)) {
                    $catlist[] = $data["categoryid"];
                }
                $result = select_query("tblknowledgebase", "tblknowledgebase.*", "categoryid IN (" . db_build_in_array($catlist) . ") AND tblknowledgebase.id != " . $id . " ORDER BY RAND()", "", "", "0,5", "tblknowledgebaselinks ON tblknowledgebase.id=tblknowledgebaselinks.articleid");
                while ($data = mysql_fetch_array($result)) {
                    $id = $data["id"];
                    $title = $data["title"];
                    $article = $data["article"];
                    $views = $data["views"];
                    $result2 = select_query("tblknowledgebase", "", array("parentid" => $id, "language" => WHMCS\Session::get("Language")));
                    $data = mysql_fetch_array($result2);
                    if ($data["title"]) {
                        $title = $data["title"];
                    }
                    if ($data["article"]) {
                        $article = $data["article"];
                    }
                    $kbarticles[] = array("id" => $id, "title" => $title, "urlfriendlytitle" => getModRewriteFriendlyString($title), "article" => strip_tags($article), "views" => $views);
                }
                $smarty->assign("kbarticles", $kbarticles);
            } else {
                $templatefile = "knowledgebase";
                $smarty->assign("kbcats", $kbRootCategories);
                $result = select_query("tblknowledgebase", "tblknowledgebase.*", "parentid=0", "views", "DESC", "0,5");
                while ($data = mysql_fetch_array($result)) {
                    $id = $data["id"];
                    $title = $data["title"];
                    $article = $data["article"];
                    $views = $data["views"];
                    $result2 = select_query("tblknowledgebasecats", "tblknowledgebasecats.hidden", array("articleid" => $id, "hidden" => "on"), "", "", "", "tblknowledgebaselinks ON tblknowledgebaselinks.categoryid=tblknowledgebasecats.id");
                    $data = mysql_fetch_array($result2);
                    if (!$data["hidden"]) {
                        $result2 = select_query("tblknowledgebase", "", array("parentid" => $id, "language" => WHMCS\Session::get("Language")));
                        $data = mysql_fetch_array($result2);
                        if ($data["title"]) {
                            $title = $data["title"];
                        }
                        if ($data["article"]) {
                            $article = $data["article"];
                        }
                        $kbmostviews[] = array("id" => $id, "title" => $title, "urlfriendlytitle" => getModRewriteFriendlyString($title), "article" => strip_tags($article), "views" => $views);
                    }
                }
                $smarty->assign("kbmostviews", $kbmostviews);
            }
        }
    }
}
$smarty->assign("breadcrumb", breakBreadcrumbHTMLIntoParts($breadcrumbnav));
$tags = array();
$result = select_query("tblknowledgebasetags", "tag, count(id) as count", "id!='' GROUP BY tag", "count", "DESC");
while ($data = mysql_fetch_array($result)) {
    $tags[$data["tag"]] = $data["count"];
}
Menu::addContext("knowledgeBaseTags", $tags);
Menu::primarySidebar("supportKnowledgeBase");
Menu::secondarySidebar("supportKnowledgeBase");
outputClientArea($templatefile, false, array("ClientAreaPageKnowledgebase"));
function kbGetCatIds($catid)
{
    global $idnumbers;
    $result = select_query("tblknowledgebasecats", "id", array("parentid" => $catid, "hidden" => ""));
    while ($data = mysql_fetch_array($result)) {
        $cid = $data[0];
        $idnumbers[] = $cid;
        kbGetCatIds($cid);
    }
}

?>