<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Manage Knowledgebase");
$aInt->title = AdminLang::trans("support.knowledgebase");
$aInt->sidebar = "support";
$aInt->icon = "knowledgebase";
$catid = (int) $catid;
$tag = strip_tags(WHMCS\Input\Sanitize::decode($whmcs->get_req_var("tag")));
$action = App::getFromRequest("action");
$id = (int) App::getFromRequest("id");
$categorieslist = "";
if ($action == "gettags") {
    check_token("WHMCS.admin.default");
    $array = array();
    $q = App::getFromRequest("q");
    $result = WHMCS\Database\Capsule::table("tblknowledgebasetags")->where("articleid", "!=", $id)->where("tag", "like", (string) $q . "%")->distinct()->orderBy("tag")->get();
    foreach ($result as $tagData) {
        $array[] = array("text" => $tagData->tag);
    }
    $aInt->jsonResponse($array);
}
if ($action == "addTag") {
    check_token("WHMCS.admin.default");
    $newTag = strip_tags(WHMCS\Input\Sanitize::decode(App::getFromRequest("newTag")));
    if ($newTag) {
        WHMCS\Database\Capsule::table("tblknowledgebasetags")->insert(array("articleid" => $id, "tag" => $newTag));
    }
    WHMCS\Terminus::getInstance()->doExit();
}
if ($action == "removeTag") {
    check_token("WHMCS.admin.default");
    $removeTag = WHMCS\Input\Sanitize::decode(App::getFromRequest("removeTag"));
    if ($removeTag) {
        WHMCS\Database\Capsule::table("tblknowledgebasetags")->where("articleid", "=", $id)->where("tag", "=", $removeTag)->delete();
    }
    WHMCS\Terminus::getInstance()->doExit();
}
if ($addarticle) {
    check_token("WHMCS.admin.default");
    $newarticleid = insert_query("tblknowledgebase", array("title" => $articlename));
    insert_query("tblknowledgebaselinks", array("categoryid" => $catid, "articleid" => $newarticleid));
    logActivity("Added New Knowledgebase Article - " . $articlename);
    redir("action=edit&id=" . $newarticleid);
}
if ($addcategory) {
    check_token("WHMCS.admin.default");
    $newcatid = insert_query("tblknowledgebasecats", array("parentid" => $catid, "name" => $catname, "description" => $description, "hidden" => $hidden));
    logActivity("Added New Knowledgebase Category - " . $catname);
    redir("catid=" . $newcatid);
}
if ($action == "save") {
    check_token("WHMCS.admin.default");
    update_query("tblknowledgebase", array("title" => $title, "article" => WHMCS\Input\Sanitize::decode($article), "views" => $views, "useful" => $useful, "votes" => $votes, "private" => $private, "order" => $order), array("id" => $id));
    delete_query("tblknowledgebaselinks", array("articleid" => $id));
    foreach ($categories as $category) {
        insert_query("tblknowledgebaselinks", array("categoryid" => $category, "articleid" => $id));
    }
    foreach ($multilang_title as $language => $title) {
        delete_query("tblknowledgebase", array("parentid" => $id, "language" => $language));
        if ($title) {
            insert_query("tblknowledgebase", array("parentid" => $id, "title" => $title, "article" => WHMCS\Input\Sanitize::decode($multilang_article[$language]), "language" => $language, "order" => $order));
        }
    }
    if ($toggleeditor) {
        if ($editorstate) {
            redir("action=edit&id=" . $id);
        } else {
            redir("action=edit&id=" . $id . "&noeditor=1");
        }
    }
    logActivity("Modified Knowledgebase Article ID: " . $id);
    redir("catid=" . $categories[0]);
}
if ($action == "savecat") {
    check_token("WHMCS.admin.default");
    update_query("tblknowledgebasecats", array("name" => $name, "description" => $description, "hidden" => $hidden, "parentid" => $parentcategory), array("id" => $id));
    foreach ($multilang_name as $language => $name) {
        delete_query("tblknowledgebasecats", array("catid" => $id, "language" => $language));
        if ($name) {
            insert_query("tblknowledgebasecats", array("catid" => $id, "name" => $name, "description" => $multilang_desc[$language], "language" => $language));
        }
    }
    logActivity("Modified Knowledgebase Category (ID: " . $id . ")");
    redir("catid=" . $parentcategory);
}
if ($action == "delete") {
    check_token("WHMCS.admin.default");
    delete_query("tblknowledgebase", array("id" => $id));
    delete_query("tblknowledgebaselinks", array("articleid" => $id));
    delete_query("tblknowledgebasetags", array("articleid" => $id));
    logActivity("Deleted Knowledgebase Article (ID: " . $id . ")");
    if ($tag) {
        redir("tag=" . urlencode($tag));
    } else {
        redir("catid=" . $catid);
    }
}
if ($action == "deletecategory") {
    check_token("WHMCS.admin.default");
    delete_query("tblknowledgebaselinks", array("categoryid" => $id));
    delete_query("tblknowledgebasecats", array("id" => $id));
    delete_query("tblknowledgebasecats", array("parentid" => $id));
    full_query("DELETE FROM tblknowledgebase WHERE parentid=0 AND id NOT IN (SELECT articleid FROM tblknowledgebaselinks)");
    logActivity("Deleted Knowledgebase Category (ID: " . $id . ")");
    redir("catid=" . $catid);
}
ob_start();
if ($action == "") {
    $breadcrumbnav = "";
    if ($catid != "0") {
        $result = select_query("tblknowledgebasecats", "", array("id" => $catid));
        $data = mysql_fetch_array($result);
        $catid = $data["id"];
        if (!$catid) {
            $aInt->gracefulExit("Category ID Not Found");
        }
        $catparentid = $data["parentid"];
        $catname = $data["name"];
        $catbreadcrumbnav = " > <a href=\"supportkb.php?catid=" . $catid . "\">" . $catname . "</a>";
        while ($catparentid != "0") {
            $result = select_query("tblknowledgebasecats", "", array("id" => $catparentid));
            $data = mysql_fetch_array($result);
            $cattempid = $data["id"];
            $catparentid = $data["parentid"];
            $catname = $data["name"];
            $catbreadcrumbnav = " > <a href=\"supportkb.php?catid=" . $cattempid . "\">" . $catname . "</a>" . $catbreadcrumbnav;
        }
        $breadcrumbnav .= $catbreadcrumbnav;
    }
    $aInt->deleteJSConfirm("doDelete", "support", "kbdelsure", $_SERVER["PHP_SELF"] . "?catid=" . $catid . "&action=delete&id=");
    $aInt->deleteJSConfirm("doDeleteShowTag", "support", "kbdelsure", $_SERVER["PHP_SELF"] . "?tag=" . urlencode($tag) . "&action=delete&id=");
    $aInt->deleteJSConfirm("doDeleteCat", "support", "kbcatdelsure", $_SERVER["PHP_SELF"] . "?catid=" . $catid . "&action=deletecategory&id=");
    echo $aInt->beginAdminTabs(array(AdminLang::trans("support.addcategory"), AdminLang::trans("support.addarticle")));
    echo "\n<form method=\"post\" action=\"";
    echo $whmcs->getPhpSelf();
    echo "?catid=";
    echo $catid;
    echo "&addcategory=true\">\n    <table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n        <tr>\n            <td width=\"15%\" class=\"fieldlabel\">";
    echo AdminLang::trans("support.catname");
    echo "</td>\n            <td class=\"fieldarea\"><input type=\"text\" name=\"catname\" class=\"form-control input-inline input-400\"> <label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"hidden\"> ";
    echo AdminLang::trans("support.ticktohide");
    echo "</label></td>\n        </tr>\n        <tr>\n            <td class=\"fieldlabel\">";
    echo AdminLang::trans("fields.description");
    echo "</td>\n            <td class=\"fieldarea\"><input type=\"text\" name=\"description\" class=\"form-control\"></td>\n        </tr>\n    </table>\n    <div class=\"btn-container\">\n        <input type=\"submit\" value=\"";
    echo AdminLang::trans("support.addcategory");
    echo "\" class=\"btn btn-primary\" />\n    </div>\n</form>\n\n";
    echo $aInt->nextAdminTab();
    echo "\n";
    if ($catid != "") {
        echo "\n<form method=\"post\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "?catid=";
        echo $catid;
        echo "&addarticle=true\">\n    <table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n        <tr>\n            <td width=\"15%\" class=\"fieldlabel\">";
        echo AdminLang::trans("support.articlename");
        echo "</td>\n            <td class=\"fieldarea\"><input type=\"text\" name=\"articlename\" class=\"form-control\"></td>\n        </tr>\n    </table>\n    <div class=\"btn-container\">\n        <input type=\"submit\" value=\"";
        echo AdminLang::trans("support.addarticle");
        echo "\" class=\"btn btn-primary\" />\n    </div>\n</form>\n\n";
    } else {
        echo AdminLang::trans("support.kbnotoplevel");
    }
    echo "\n";
    echo $aInt->endAdminTabs();
    $editImage = "<img src='images/edit.gif' align='absmiddle' border='0' alt='" . AdminLang::trans("global.edit") . "' />";
    $deleteImage = "<img src='images/delete.gif' align='absmiddle' border='0' alt='" . AdminLang::trans("global.delete") . "' />";
    $folderImage = WHMCS\View\Asset::imgTag("folder.gif", AdminLang::trans("support.category"));
    $articleImage = WHMCS\View\Asset::imgTag("article.gif", AdminLang::trans("support.article"));
    echo "<br><p>" . AdminLang::trans("support.youarehere") . ": " . "<a href=\"" . $whmcs->getPhpSelf() . "\">" . AdminLang::trans("support.kbhome") . "</a> " . $breadcrumbnav . "</p>";
    if ($tag) {
        $data = get_query_vals("tblknowledgebasetags", "id, tag", array("tag" => $tag));
        $tagId = $data["id"];
        $tagName = $data["tag"];
        if (!$tagId) {
            redir();
        }
        echo "<div style=\"margin: 20px 0;padding:10px 20px;background-color:#efefef;font-size:18px;border-radius:5px;\">\n    " . AdminLang::trans("support.viewarticlesfortag") . " \"" . WHMCS\Input\Sanitize::makeSafeForOutput($tagName) . "\"\n</div>";
        $result = select_query("tblknowledgebase", "tblknowledgebase.*", array("tag" => $tagName), "order` ASC,`title", "ASC", "", "tblknowledgebasetags ON tblknowledgebase.id=tblknowledgebasetags.articleid");
        $numarticles = mysql_num_rows($result);
        if (0 < $numarticles) {
            echo "<table width=\"100%\"><tr>";
            while ($data = mysql_fetch_array($result)) {
                $id = $data["id"];
                $category = $data["category"];
                $title = $data["title"];
                $article = strip_tags($data["article"]);
                $views = $data["views"];
                $private = $data["private"];
                $article = substr($article, 0, 150) . "...";
                $privateText = $private ? "<span style=\"color:#cccccc\">(" . strtoupper(AdminLang::trans("support.clientsonly")) . ")</span>" : "";
                echo sprintf("<div>%s <a href=\"supportkb.php?action=edit&id=%d\"><b>%s</b></a> <a href=\"#\" onClick=\"doDeleteShowTag(%d); return false;\">%s</a> %s</div>", $articleImage, $id, $title, $id, $deleteImage, $privateText);
                echo $article;
                echo sprintf("<div style=\"color:#cccccc; margin-bottom: 18px\">" . AdminLang::trans("support.views") . ": %d</div>", $views);
            }
            echo "</tr></table>";
        } else {
            echo "<p align=\"center\"><b>" . AdminLang::trans("support.noarticlesfound") . "</b></p>";
        }
    } else {
        $result = select_query("tblknowledgebasecats", "", array("parentid" => $catid), "name", "ASC");
        $numcats = mysql_num_rows($result);
        if (0 < $numcats) {
            echo "\n<div style=\"margin: 20px 0;padding:10px 20px;background-color:#efefef;font-size:18px;border-radius:5px;\">\n    " . AdminLang::trans("support.browsebycategory") . "\n</div>\n\n<div class=\"row\">\n";
            if ($catid == "") {
                $catid = 0;
            }
            $result = select_query("tblknowledgebasecats", "", array("parentid" => $catid, "catid" => 0), "name", "ASC");
            while ($data = mysql_fetch_array($result)) {
                $id = $data["id"];
                $name = $data["name"];
                $description = $data["description"];
                $hidden = $data["hidden"];
                $idnumbers = array();
                $idnumbers[] = $id;
                kbgetcatids($id);
                $queryreport = "";
                foreach ($idnumbers as $idnumber) {
                    $queryreport .= " OR categoryid='" . $idnumber . "'";
                }
                $queryreport = substr($queryreport, 4);
                $result2 = select_query("tblknowledgebase", "COUNT(*)", "parentid=0 AND (" . $queryreport . ")", "", "", "", "tblknowledgebaselinks ON tblknowledgebase.id=tblknowledgebaselinks.articleid");
                $data2 = mysql_fetch_array($result2);
                $numarticles = $data2[0];
                $catOutput = "<div class=\"col-md-4 col-sm-6\">\n    " . $folderImage . " <a href=\"supportkb.php?catid=" . $id . "\"><strong>" . $name . "</strong></a> (" . $numarticles . ")\n    <a href=\"supportkb.php?action=editcat&id=" . $id . "\">" . $editImage . "</a> <a href=\"#\" onClick=\"doDeleteCat(" . $id . "); return false;\">" . $deleteImage . "</a>";
                echo $catOutput;
                if ($hidden) {
                    echo " <font color=\"#cccccc\">(" . strtoupper(AdminLang::trans("fields.hidden")) . ")</font>";
                }
                echo "<br>" . $description . "<br><br></div>";
            }
            echo "</div>\n\n";
        }
        $result = select_query("tblknowledgebase", "tblknowledgebase.*", array("categoryid" => $catid), "order` ASC,`title", "ASC", "", "tblknowledgebaselinks ON tblknowledgebase.id=tblknowledgebaselinks.articleid");
        $numarticles = mysql_num_rows($result);
        if ($numarticles != "0") {
            echo "\n<div style=\"margin: 20px 0;padding:10px 20px;background-color:#efefef;font-size:18px;border-radius:5px;\">\n    ";
            echo AdminLang::trans("support.articles");
            echo "</div>\n\n";
            while ($data = mysql_fetch_array($result)) {
                $id = $data["id"];
                $category = $data["category"];
                $title = $data["title"];
                $article = strip_tags($data["article"]);
                $views = $data["views"];
                $private = $data["private"];
                $article = substr($article, 0, 150) . "...";
                $articleOutput = "<p>" . $articleImage . " <a href=\"supportkb.php?action=edit&id=" . $id . "\"><strong>" . $title . "</strong></a>\n<a href=\"#\" onClick=\"doDelete(" . $id . "); return false;\">" . $deleteImage . "</a>";
                echo $articleOutput;
                if ($private) {
                    echo " <font color=\"#cccccc\">(" . strtoupper(AdminLang::trans("support.clientsonly")) . ")</font>";
                }
                echo "<br>" . $article . "<br><font color=#cccccc>" . AdminLang::trans("support.views") . ": " . $views . "</font></p>";
            }
            echo "\n";
        } else {
            if ($catid != 0) {
                echo "<p><strong>" . AdminLang::trans("support.noarticlesfound") . "</strong></p>";
            }
        }
        if ($catid == 0) {
            echo "<div style=\"margin: 20px 0;padding:10px 20px;background-color:#efefef;font-size:18px;border-radius:5px;\">\n    " . AdminLang::trans("support.browsebytag") . "\n</div>";
            $tags = array();
            $result = select_query("tblknowledgebasetags", "tag, count(id) as count", "id!='' GROUP BY tag", "count", "ASC");
            while ($data = mysql_fetch_array($result)) {
                $tags[] = $data;
            }
            $tagCount = count($tags);
            if ($tagCount) {
                $output = array();
                $fontSize = array(1 => "12");
                foreach ($tags as $tag) {
                    if (!isset($fontSize[$tag["count"]])) {
                        $fontSize[$tag["count"]] = last($fontSize) + 2;
                    }
                    $thisFontSize = $fontSize[$tag["count"]];
                    $tagContent = strip_tags($tag["tag"]);
                    $tagParam = urlencode($tagContent);
                    $tagContent = WHMCS\Input\Sanitize::makeSafeForOutput($tagContent);
                    $output[] = "<a href=\"supportkb.php?tag=" . $tagParam . "\" style=\"font-size:" . $thisFontSize . "px;\">" . $tagContent . " (" . $tag["count"] . ")</a>&nbsp;";
                }
            } else {
                $output[] = AdminLang::trans("support.noTags");
            }
            $output = array_reverse($output);
            $output = implode("", $output);
            echo $output;
        }
    }
} else {
    if ($action == "edit") {
        $result = select_query("tblknowledgebase", "", array("id" => $id));
        $data = mysql_fetch_array($result);
        $id = (int) $data["id"];
        $title = WHMCS\Input\Sanitize::makeSafeForOutput($data["title"]);
        $article = WHMCS\Input\Sanitize::encode($data["article"]);
        $views = (int) $data["views"];
        $useful = (int) $data["useful"];
        $votes = (int) $data["votes"];
        $private = $data["private"];
        $order = (int) $data["order"];
        $multilang_title = array();
        $multilang_article = array();
        $result = select_query("tblknowledgebase", "", array("parentid" => $id));
        while ($data = mysql_fetch_array($result)) {
            $language = $data["language"];
            $multilang_title[$language] = WHMCS\Input\Sanitize::makeSafeForOutput($data["title"]);
            $multilang_article[$language] = WHMCS\Input\Sanitize::encode($data["article"]);
        }
        $categories = array();
        $result = select_query("tblknowledgebaselinks", "", array("articleid" => $id));
        while ($data = mysql_fetch_array($result)) {
            $categories[] = $data["categoryid"];
        }
        $tags = array();
        $result = select_query("tblknowledgebasetags", "tag", array("articleid" => $id), "tag", "ASC");
        while ($data = mysql_fetch_array($result)) {
            $tags[] = WHMCS\Input\Sanitize::makeSafeForOutput($data["tag"]);
        }
        $tags = implode(",", $tags);
        $jscode = "function showtranslation(language) {\n    \$(\"#translation_\"+language).slideToggle();\n}";
        echo "\n<form method=\"post\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "?catid=";
        echo $category;
        echo "&action=save&id=";
        echo $id;
        echo "\">\n<input type=\"hidden\" name=\"editorstate\" value=\"";
        echo $noeditor;
        echo "\" />\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n    <tr>\n        <td width=\"15%\" class=\"fieldlabel\">";
        echo AdminLang::trans("fields.title");
        echo "</td>\n        <td class=\"fieldarea\"><input type=\"text\" name=\"title\" value=\"";
        echo $title;
        echo "\" class=\"form-control\"></td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">";
        echo AdminLang::trans("support.categories");
        echo "</td>\n        <td class=\"fieldarea\"><select name=\"categories[]\" size=\"8\" multiple class=\"form-control\">";
        buildcategorieslist(0, 0);
        echo $categorieslist;
        echo "</select></td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">";
        echo AdminLang::trans("support.views");
        echo "</td>\n        <td class=\"fieldarea\"><input type=\"text\" name=\"views\" value=\"";
        echo $views;
        echo "\" class=\"form-control input-inline input-100\"> ";
        echo AdminLang::trans("support.votes");
        echo " For <input type=\"text\" name=\"useful\" value=\"";
        echo $useful;
        echo "\" class=\"form-control input-inline input-100\"> Total <input type=\"text\" name=\"votes\" value=\"";
        echo $votes;
        echo "\" class=\"form-control input-inline input-100\"></td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">";
        echo AdminLang::trans("customfields.order");
        echo "</td>\n        <td class=\"fieldarea\"><input type=\"text\" name=\"order\" value=\"";
        echo $order;
        echo "\" class=\"form-control input-inline input-100\"></td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">";
        echo AdminLang::trans("support.private");
        echo "</td>\n        <td class=\"fieldarea\"><label class=\"checkbox-inline\"><input type=\"checkbox\" name=\"private\"";
        if ($private) {
            echo " checked";
        }
        echo "> ";
        echo AdminLang::trans("support.privateinfo");
        echo "</label></td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">Tags</td>\n        <td class=\"fieldarea\"><input type=\"text\" id=\"kbTags\" style=\"min-width:500px;\" class=\"selectize-tags\" value=\"";
        echo $tags;
        echo "\"  placeholder=\"";
        echo AdminLang::trans("support.addTag");
        echo "\" /></td>\n    </tr>\n</table>\n<br />\n\n<textarea name=\"article\" rows=\"20\" class=\"form-control tinymce\">";
        echo $article;
        echo "</textarea>\n\n<div class=\"text-right\">\n    <br>\n    <input type=\"submit\" name=\"toggleeditor\" value=\"";
        echo AdminLang::trans("emailtpls.rteditor");
        echo "\" class=\"btn\" />\n</div>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
        echo AdminLang::trans("global.savechanges");
        echo "\" class=\"btn btn-primary\" />\n    <a href=\"supportkb.php?catid=";
        echo $categories[0];
        echo "\" class=\"btn btn-default\">";
        echo AdminLang::trans("global.cancelchanges");
        echo "</a>\n</div>\n\n<h2>";
        echo AdminLang::trans("support.announcemultiling");
        echo "</h2>\n\n";
        foreach (WHMCS\Language\ClientLanguage::getLanguages() as $language) {
            if ($language != $CONFIG["Language"]) {
                echo "<p><b><a href=\"supportkb.php#\" onClick=\"showtranslation('" . $language . "');return false;\">" . ucfirst($language) . "</a></b></p>\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\" id=\"translation_" . $language . "\"";
                if (!$multilang_title[$language]) {
                    echo " style=\"display:none;\"";
                }
                echo ">\n<tr><td width=\"15%\" class=\"fieldlabel\">" . AdminLang::trans("fields.title") . "</td><td class=\"fieldarea\"><input type=\"text\" name=\"multilang_title[" . $language . "]\" value=\"" . $multilang_title[$language] . "\" class=\"form-control\"></td></tr>\n<tr><td class=\"fieldlabel\">" . AdminLang::trans("support.article") . "</td><td class=\"fieldarea\"><textarea name=\"multilang_article[" . $language . "]\" rows=\"20\" style=\"width:100%\" class=\"tinymce\">" . $multilang_article[$language] . "</textarea></td></tr>\n</table>";
            }
        }
        echo "\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
        echo AdminLang::trans("global.savechanges");
        echo "\" class=\"btn btn-primary\" />\n    <input type=\"button\" value=\"";
        echo AdminLang::trans("global.cancelchanges");
        echo "\" class=\"btn btn-default\" onclick=\"history.go(-1)\" />\n</div>\n\n</form>\n\n";
        if (!$noeditor) {
            $aInt->richTextEditor();
        }
    } else {
        if ($action == "editcat") {
            $result = select_query("tblknowledgebasecats", "", array("id" => $id));
            $data = mysql_fetch_array($result);
            $id = (int) $data["id"];
            $parentid = $data["parentid"];
            $name = WHMCS\Input\Sanitize::makeSafeForOutput($data["name"]);
            $description = WHMCS\Input\Sanitize::makeSafeForOutput($data["description"]);
            $hidden = $data["hidden"];
            $categories = array();
            $categories[] = $parentid;
            $multilang_name = array();
            $multilang_desc = array();
            $result = select_query("tblknowledgebasecats", "", array("catid" => $id));
            while ($data = mysql_fetch_array($result)) {
                $language = $data["language"];
                $multilang_name[$language] = WHMCS\Input\Sanitize::makeSafeForOutput($data["name"]);
                $multilang_desc[$language] = WHMCS\Input\Sanitize::makeSafeForOutput($data["description"]);
            }
            echo "\n<form method=\"post\" action=\"";
            echo $whmcs->getPhpSelf();
            echo "?action=savecat&id=";
            echo $id;
            echo "\">\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr><td width=\"15%\" class=\"fieldlabel\">";
            echo AdminLang::trans("support.parentcat");
            echo "</td><td class=\"fieldarea\"><select name=\"parentcategory\" class=\"form-control\">\n<option value=\"\">";
            echo AdminLang::trans("support.toplevel");
            echo "</option>\n";
            buildcategorieslist(0, 0, $id);
            echo $categorieslist;
            echo "?></select></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo AdminLang::trans("support.catname");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"name\" value=\"";
            echo $name;
            echo "\" class=\"form-control\"></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo AdminLang::trans("fields.description");
            echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"description\" value=\"";
            echo $description;
            echo "\" class=\"form-control\"></td></tr>\n<tr><td class=\"fieldlabel\">";
            echo AdminLang::trans("fields.hidden");
            echo "</td><td class=\"fieldarea\"><input type=\"checkbox\" name=\"hidden\"";
            if ($hidden == "on") {
                echo " checked";
            }
            echo "> ";
            echo AdminLang::trans("support.hiddeninfo");
            echo "</td></tr>\n</table>\n\n<h2>";
            echo AdminLang::trans("support.announcemultiling");
            echo "</h2>\n\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n";
            foreach (WHMCS\Language\ClientLanguage::getLanguages() as $language) {
                echo "<tr><td width=\"15%\" class=\"fieldlabel\">" . ucfirst($language) . "</td><td class=\"fieldarea\">" . AdminLang::trans("fields.name") . ": <input type=\"text\" name=\"multilang_name[" . $language . "]\" value=\"" . $multilang_name[$language] . "\" class=\"form-control input-inline input-300\"> " . AdminLang::trans("fields.description") . ": <input type=\"text\" name=\"multilang_desc[" . $language . "]\" value=\"" . $multilang_desc[$language] . "\" class=\"form-control input-inline input-400\"></td></tr>\n";
            }
            echo "</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
            echo AdminLang::trans("global.savechanges");
            echo "\" class=\"btn btn-primary\" />\n    <input type=\"button\" value=\"";
            echo AdminLang::trans("global.cancelchanges");
            echo "\" class=\"btn btn-default\" onclick=\"history.go(-1)\" />\n</div>\n\n</form>\n\n";
        }
    }
}
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();
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
function buildCategoriesList($level, $parentlevel, $exclude = "")
{
    global $categorieslist;
    global $categories;
    $result = select_query("tblknowledgebasecats", "", array("parentid" => $level, "catid" => 0), "name", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $id = $data["id"];
        $parentid = $data["parentid"];
        $category = $data["name"];
        if ($id != $exclude) {
            $categorieslist .= "<option value=\"" . $id . "\"";
            if (in_array($id, $categories)) {
                $categorieslist .= " selected";
            }
            $categorieslist .= ">";
            for ($i = 1; $i <= $parentlevel; $i++) {
                $categorieslist .= "- ";
            }
            $categorieslist .= (string) $category . "</option>";
        }
        buildCategoriesList($id, $parentlevel + 1, $exclude);
    }
}

?>