<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Manage Announcements");
$aInt->title = $aInt->lang("support", "announcements");
$aInt->sidebar = "support";
$aInt->icon = "announcements";
if ($sub == "delete") {
    check_token("WHMCS.admin.default");
    delete_query("tblannouncements", array("id" => $id));
    delete_query("tblannouncements", array("parentid" => $id));
    logActivity("Deleted Announcement (ID: " . $id . ")");
    redir();
}
if ($sub == "save") {
    check_token("WHMCS.admin.default");
    $date = toMySQLDate($date);
    $published = $published ? "1" : "0";
    if ($id) {
        update_query("tblannouncements", array("date" => $date, "title" => WHMCS\Input\Sanitize::decode($title), "announcement" => WHMCS\Input\Sanitize::decode($announcement), "published" => $published), array("id" => $id));
        logActivity("Modified Announcement (ID: " . $id . ")");
        run_hook("AnnouncementEdit", array("announcementid" => $id, "date" => $date, "title" => $title, "announcement" => $announcement, "published" => $published));
    } else {
        $id = insert_query("tblannouncements", array("date" => $date, "title" => WHMCS\Input\Sanitize::decode($title), "announcement" => WHMCS\Input\Sanitize::decode($announcement), "published" => $published));
        logActivity("Added New Announcement (" . $title . ")");
        run_hook("AnnouncementAdd", array("announcementid" => $id, "date" => $date, "title" => $title, "announcement" => $announcement, "published" => $published));
    }
    foreach ($multilang_title as $language => $title) {
        delete_query("tblannouncements", array("parentid" => $id, "language" => $language));
        if ($title) {
            insert_query("tblannouncements", array("parentid" => $id, "title" => WHMCS\Input\Sanitize::decode($title), "announcement" => WHMCS\Input\Sanitize::decode($multilang_announcement[$language]), "language" => $language));
        }
    }
    if ($toggleeditor) {
        if ($editorstate) {
            redir("action=manage&id=" . $id);
        } else {
            redir("action=manage&id=" . $id . "&noeditor=1");
        }
    }
    redir("success=1");
}
ob_start();
if ($action == "") {
    $aInt->deleteJSConfirm("doDelete", "support", "announcesuredel", "?sub=delete&id=");
    if ($success) {
        infoBox(AdminLang::trans("global.success"), AdminLang::trans("global.changesuccess"), "success");
        echo $infobox;
    }
    echo "\n<form method=\"post\" action=\"";
    echo $whmcs->getPhpSelf();
    echo "?action=manage\">\n<p align=\"center\"><input type=\"submit\" id=\"add_announcement\" value=\"";
    echo $aInt->lang("support", "announceadd");
    echo "\" class=\"btn btn-primary\" /></p>\n</form>\n\n";
    $numrows = get_query_val("tblannouncements", "COUNT(id)", array("language" => ""));
    $aInt->sortableTableInit("date", "DESC");
    $result = select_query("tblannouncements", "", array("language" => ""), "date", "DESC", $page * $limit . "," . $limit);
    while ($data = mysql_fetch_array($result)) {
        $id = $data["id"];
        $date = $data["date"];
        $title = $data["title"];
        $published = $data["published"];
        $date = fromMySQLDate($date, true);
        $isPublished = $published ? "Yes" : "No";
        $tabledata[] = array($date, $title, $isPublished, "<a href=\"?action=manage&id=" . $id . "\">\n             <img src=\"images/edit.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "edit") . "\">\n         </a>", "<a href=\"#\" onClick=\"doDelete('" . $id . "');return false\">\n             <img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "edit") . "\">\n         </a>");
    }
    echo $aInt->sortableTable(array($aInt->lang("fields", "date"), $aInt->lang("fields", "title"), $aInt->lang("support", "announcepublished"), "", ""), $tabledata, $tableformurl, $tableformbuttons);
} else {
    if ($action == "manage") {
        $multilang_title = array();
        $multilang_announcement = array();
        if ($id) {
            $action = "Edit";
            $result = select_query("tblannouncements", "", array("id" => $id, "language" => ""));
            $data = mysql_fetch_array($result);
            $id = $data["id"];
            $date = $data["date"];
            $title = WHMCS\Input\Sanitize::encode($data["title"]);
            $announcement = WHMCS\Input\Sanitize::encode($data["announcement"]);
            $published = $data["published"];
            $date = fromMySQLDate($date, true);
            $result = select_query("tblannouncements", "", array("parentid" => $id));
            while ($data = mysql_fetch_array($result)) {
                $language = $data["language"];
                $multilang_title[$language] = WHMCS\Input\Sanitize::encode($data["title"]);
                $multilang_announcement[$language] = WHMCS\Input\Sanitize::encode($data["announcement"]);
            }
        } else {
            $action = "Add";
            $date = fromMySQLDate(date("Y-m-d H:i:s"), true);
        }
        $jscode = "function showtranslation(language) {\n    var translationElement = \$(\"#translation_\" + language),\n        copiedSettings = tinymceSettings,\n        selector = \"textarea#\" + language;\n    translationElement.slideToggle(400, function() {\n        if (translationElement.is(':visible')) {\n            //We are displaying this row\n            copiedSettings.selector = selector;\n            tinymce.init(copiedSettings);\n        } else {\n            tinymce.remove(selector);\n        }\n    });\n}";
        $checked = $published ? " checked=\"checked\"" : "";
        echo "\n<h2>";
        echo $action;
        echo " ";
        echo $aInt->lang("support", "announcement");
        echo "</h2>\n<form method=\"post\" id=\"manageAnnouncementForm\" action=\"";
        echo $whmcs->getPhpSelf();
        echo "?sub=save&id=";
        echo $id;
        echo "\">\n<input type=\"hidden\" name=\"editorstate\" value=\"";
        echo $noeditor;
        echo "\" />\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n    <tr>\n        <td width=\"15%\" class=\"fieldlabel\">\n            ";
        echo AdminLang::trans("fields.date");
        echo "        </td>\n        <td class=\"fieldarea\">\n            <div class=\"form-group date-picker-prepend-icon\">\n                <label for=\"inputDate\" class=\"field-icon\">\n                    <i class=\"fal fa-calendar-alt\"></i>\n                </label>\n                <input id=\"inputDate\"\n                       type=\"text\"\n                       name=\"date\"\n                       value=\"";
        echo $date;
        echo "\"\n                       class=\"form-control date-picker-single time future\"\n                />\n            </div>\n        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">";
        echo AdminLang::trans("fields.title");
        echo "</td>\n        <td class=\"fieldarea\">\n            <input type=\"text\" name=\"title\" class=\"form-control input-80percent\" value=\"";
        echo $title;
        echo "\">\n        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">";
        echo AdminLang::trans("support.announcement");
        echo "</td>\n        <td class=\"fieldarea\">\n            <textarea name=\"announcement\" class=\"tinymce\">";
        echo $announcement;
        echo "</textarea>\n        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">";
        echo AdminLang::trans("support.announcepublished");
        echo "?</td>\n        <td class=\"fieldarea\">\n            <input type=\"hidden\" name=\"published\" value=\"0\">\n            <input type=\"checkbox\" name=\"published\" value=\"1\"";
        echo $checked;
        echo ">\n        </td>\n    </tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" name=\"toggleeditor\" value=\"";
        echo $aInt->lang("emailtpls", "rteditor");
        echo "\" class=\"btn btn-default\" />\n    <input type=\"submit\" value=\"";
        echo $aInt->lang("global", "savechanges");
        echo "\" class=\"btn btn-primary\" >\n</div>\n\n<h2>";
        echo $aInt->lang("support", "announcemultiling");
        echo "</h2>\n\n";
        foreach (WHMCS\Language\ClientLanguage::getLanguages() as $language) {
            if ($language != $CONFIG["Language"]) {
                $langTitle = AdminLang::trans("fields.title");
                $langAnnouncement = AdminLang::trans("support.announcement");
                $upperLanguage = ucfirst($language);
                $tableId = "translation_" . $language;
                $style = "";
                $class = "tinymce";
                if (!$multilang_title[$language]) {
                    $style = " style=\"display: none;\"";
                    $class = "tinymce-additional";
                }
                $titleInputName = "multilang_title[" . $language . "]";
                $titleInputValue = $multilang_title[$language];
                $titleInputClass = "form-control input-400";
                $textAreaName = "multilang_announcement[" . $language . "]";
                $textAreaId = $language;
                $translation = $multilang_announcement[$language];
                $extra = " rows=\"20\" style=\"width:100%;\"";
                $output = "<p>\n    <b>\n        <a href=\"#\" onclick=\"showtranslation('" . $language . "'); return false;\">\n            " . $upperLanguage . "\n        </a>\n    </b>\n</p>\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\" id=\"" . $tableId . "\"" . $style . ">\n    <tr>\n        <td width=\"15%\" class=\"fieldlabel\">\n            " . $langTitle . "\n        </td>\n        <td class=\"fieldarea\">\n            <input type=\"text\" name=\"" . $titleInputName . "\" value=\"" . $titleInputValue . "\" class=\"" . $titleInputClass . "\">\n        </td>\n    </tr>\n    <tr>\n        <td class=\"fieldlabel\">\n            " . $langAnnouncement . "\n        </td>\n        <td class=\"fieldarea\">\n            <textarea id=\"" . $textAreaId . "\" name=\"" . $textAreaName . "\"" . $extra . " class=\"" . $class . "\">" . $translation . "</textarea>\n        </td>\n    </tr>\n</table> ";
                echo $output;
            }
        }
        echo "\n<div class=\"btn-container\">\n    <input type=\"submit\" name=\"toggleeditor\" value=\"";
        echo $aInt->lang("emailtpls", "rteditor");
        echo "\" class=\"btn btn-default\" />\n    <input type=\"submit\" value=\"";
        echo $aInt->lang("global", "savechanges");
        echo "\" class=\"btn btn-primary\" />\n</div>\n\n</form>\n\n";
        if (!$noeditor) {
            $aInt->richTextEditor();
        }
    }
}
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jscode = $jscode;
$aInt->jquerycode = $jquerycode;
$aInt->display();

?>