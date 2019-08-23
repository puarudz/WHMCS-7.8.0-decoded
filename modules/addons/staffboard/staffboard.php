<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
function staffboard_config()
{
    $configarray = array("name" => "Staff Noticeboard", "version" => "1.1", "author" => "WHMCS", "description" => "Acts as a noticeboard within the WHMCS admin area providing a quick and easy way to communicate with all the staff via your WHMCS system");
    $fieldname = "Edit/Delete Permissions";
    $fielddesc = " (Select all you want to allow to edit and delete notes)";
    $result = select_query("tbladminroles", "", "", "id", "ASC");
    while ($data = mysql_fetch_array($result)) {
        $configarray["fields"]["masteradmin" . $data["id"]] = array("FriendlyName" => $fieldname, "Type" => "yesno", "Description" => $data["name"] . $fielddesc);
        $fieldname = $fielddesc = "";
    }
    return $configarray;
}
function staffboard_activate()
{
    $query = "CREATE TABLE `mod_staffboard` (\n        `id` INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,\n        `note` TEXT NOT NULL,\n        `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,\n        `color` VARCHAR(10) NOT NULL,\n        `adminid` INT(10) NOT NULL,\n        `x` INT(4) NOT NULL,\n        `y` INT(4) NOT NULL,\n        `z` INT(4) NOT NULL\n        ) ; ";
    $result = full_query($query);
}
function staffboard_deactivate()
{
    $query = "DROP TABLE `mod_staffboard`";
    $result = full_query($query);
}
function staffboard_menubar($vars)
{
    $modulelink = $vars["modulelink"];
    $links = array("" => "Notes", "refresh" => "Refresh");
    $tblinks = array("addoverlay" => "Add Note");
    echo "<style>\n.lic_linksbar {\n    padding:10px 25px 10px 25px;\n    background-color:#666;\n    font-weight:bold;\n    font-size: 14px;\n    color: #E3F0FD;\n    margin: 0 0 15px 0;\n    -moz-border-radius: 5px;\n    -webkit-border-radius: 5px;\n    -o-border-radius: 5px;\n    border-radius: 5px;\n}\n.lic_linksbar a {\n    color: #fff;\n    font-weight: normal;\n}\n.res_suboptions {\n    background-color: #efefef;\n    width: 250px;\n    padding: 5px 10px 5px 10px;\n    margin: 0 0 15px 15px;\n    -moz-border-radius: 5px;\n    -webkit-border-radius: 5px;\n    -o-border-radius: 5px;\n    border-radius: 5px;\n}\n</style>\n<div class=\"lic_linksbar\">";
    $first = true;
    foreach ($links as $k => $v) {
        if (!$first) {
            echo " | ";
        } else {
            $first = false;
        }
        if ($_REQUEST["action"] != $k) {
            echo "<a href=\"" . $modulelink . "&action=" . $k . "\">";
        }
        echo $v;
        if ($_REQUEST["action"] != $k) {
            echo "</a>";
        }
    }
    foreach ($tblinks as $k => $v) {
        if (!$first) {
            echo " | ";
        } else {
            $first = false;
        }
        if ($_REQUEST["action"] != $k) {
            echo "<a class=\"thickbox\" href=\"" . $modulelink . "&action=" . $k . "\">";
        }
        echo $v;
        if ($_REQUEST["action"] != $k) {
            echo "</a>";
        }
    }
    echo "</div>";
}
function staffboard_output($vars)
{
    $modulelink = $vars["modulelink"];
    $whmcs = DI::make("app");
    $action = $whmcs->get_req_var("action");
    if ($action == "editnote") {
        $noteId = (int) $whmcs->get_req_var("noteid");
        $notedata = get_query_vals("mod_staffboard", "", array("id" => $noteId));
        $yellowSelected = $notedata["color"] == "yellow" ? "selected " : "";
        $blueSelected = $notedata["color"] == "blue" ? "selected " : "";
        $greenSelected = $notedata["color"] == "green" ? "selected " : "";
        $whiteSelected = $notedata["color"] == "white" ? "selected " : "";
        $pinkSelected = $notedata["color"] == "pink" ? " selected " : "";
        $purpleSelected = $notedata["color"] == "purple" ? "selected " : "";
        $formToken = generate_token();
        $content = "<div style=\"padding:20px 5px;\">\n    <h3 class=\"Title\">Edit note</h3>\n    <div id=\"noteData\">\n        <form action=\"" . $modulelink . "&action=updatenote\" method=\"post\" class=\"note-form\">\n        " . $formToken . "\n        <input type=\"hidden\" name=\"noteid\" value=\"" . $noteId . "\" />\n        <label for=\"note\">Text of the note</label>\n        <textarea name=\"note\" id=\"note\" class=\"pr-body\" cols=\"150\" rows=\"50\">" . $notedata["note"] . "</textarea>\n        <label>Color</label>\n        <select name=\"color\">\n            <option " . $yellowSelected . "value=\"yellow\">Yellow</option>\n            <option " . $blueSelected . "value=\"blue\">Blue</option>\n            <option " . $greenSelected . "value=\"green\">Green</option>\n            <option " . $whiteSelected . "value=\"white\">White</option>\n            <option " . $pinkSelected . "value=\"pink\">Pink</option>\n            <option " . $purpleSelected . "value=\"purple\">Purple</option>\n        </select>\n        <input type=\"submit\" name=\"submit\" value=\"Save Note\" />\n        </form>\n    </div>\n</div>";
        echo $content;
        WHMCS\Terminus::getInstance()->doExit();
    }
    if ($action == "addoverlay") {
        $formToken = generate_token();
        echo "<div style=\"padding:20px 5px;\">\n    <h3 class=\"Title\">Add a new note</h3>\n    <div id=\"noteData\">\n        <form action=\"" . $modulelink . "&action=createnote\" method=\"post\" class=\"note-form\">\n        " . $formToken . "\n        <label for=\"note\">Text of the note</label>\n        <textarea name=\"note\" id=\"note\" class=\"pr-body\" cols=\"150\" rows=\"50\"></textarea>\n        <label>Color</label>\n        <select name=\"color\">\n            <option value=\"yellow\">Yellow</option>\n            <option value=\"blue\">Blue</option>\n            <option value=\"green\">Green</option>\n            <option value=\"white\">White</option>\n            <option value=\"pink\">Pink</option>\n            <option value=\"purple\">Purple</option>\n        </select>\n        <input type=\"submit\" name=\"submit\" value=\"Add Note\" />\n        </form>\n    </div>\n</div>";
        WHMCS\Terminus::getInstance()->doExit();
    }
    $adminroleid = get_query_val("tbladmins", "roleid", array("id" => $_SESSION["adminid"]));
    if ($action == "updatenote") {
        check_token("WHMCS.admin.default");
        $noteid = $_REQUEST["noteid"];
        if (get_query_val("mod_staffboard", "adminid", array("id" => $noteid)) || $vars["masteradmin" . $adminroleid]) {
            update_query("mod_staffboard", array("color" => $_REQUEST["color"], "note" => $_REQUEST["note"], "date" => "now()"), array("id" => $noteid));
            redir("module=staffboard");
        }
    } else {
        if ($action == "updatepos") {
            check_token("WHMCS.admin.default");
            update_query("mod_staffboard", array("x" => (int) $_REQUEST["x"], "y" => (int) $_REQUEST["y"], "z" => (int) $_REQUEST["z"]), array("id" => (int) $_REQUEST["id"]));
            exit;
        }
        if ($action == "createnote") {
            check_token("WHMCS.admin.default");
            if (!isset($_POST["note"]) || !in_array($_POST["color"], array("yellow", "green", "blue", "white", "pink", "purple"))) {
                exit("Please go back and try again.");
            }
            $result = select_query("mod_staffboard", "z", "", "z", "DESC");
            $row = mysql_fetch_assoc($result);
            $lastz = $row["z"];
            insert_query("mod_staffboard", array("note" => $_POST["note"], "date" => "now()", "color" => $_POST["color"], "x" => 0, "y" => 0, "z" => $lastz + 1, "adminid" => $_SESSION["adminid"]));
            redir("module=staffboard");
        } else {
            if ($action == "deletenote") {
                check_token("WHMCS.admin.default");
                $noteid = $_REQUEST["noteid"];
                if (get_query_val("mod_staffboard", "adminid", array("id" => $noteid)) || $vars["masteradmin" . $adminroleid]) {
                    delete_query("mod_staffboard", array("id" => $_REQUEST["noteid"]));
                }
                redir("module=staffboard");
            } else {
                if ($action == "refresh") {
                    redir("module=staffboard");
                }
            }
        }
    }
    echo "<link href=\"../modules/addons/staffboard/css/jquery.staffboard.css\" rel=\"stylesheet\" type=\"text/css\" />";
    echo "<script type=\"text/javascript\">var csrfToken = '" . generate_token("plain") . "';</script>";
    echo "<script type=\"text/javascript\" src=\"../modules/addons/staffboard/js/jquery.staffboard.js\"></script>";
    staffboard_menubar($vars);
    $notes = "";
    $notes_result = select_query("mod_staffboard", "", array());
    while ($row = mysql_fetch_assoc($notes_result)) {
        $result = select_query("tbladmins", "firstname,lastname", array("id" => $row["adminid"]));
        $data = mysql_fetch_assoc($result);
        $editlink = $row["adminid"] == $_SESSION["adminid"] || $vars["masteradmin" . $adminroleid] ? " <a class=\"thickbox\" href=\"" . $modulelink . "&action=editnote&noteid=" . $row["id"] . "\">Edit</a>" : "";
        $editlink .= $vars["masteradmin" . $adminroleid] ? " | <a onclick=\"return confirm('Are you sure you want to delete this note?');\" href=\"" . $modulelink . "&action=deletenote&noteid=" . $row["id"] . generate_token("link") . "\">Delete</a>" : "";
        if ($row["id"] < $row["z"]) {
            $zaxis = $row["z"];
        } else {
            $zaxis = $row["id"];
        }
        $notes .= "\n        <div id=\"note" . $row["id"] . "\" class=\"note " . $row["color"] . "\" style=\"left:" . $row["x"] . "px;top:" . $row["y"] . "px;z-index:" . $zaxis . "\"><div style=\"height:95%\">" . nl2br($row["note"]) . "</div>\n            <div class=\"author\">" . $data["firstname"] . " " . $data["lastname"] . " on " . fromMySQLDate($row["date"], 1) . "<br />" . $editlink . "</div>\n            <span class=\"data\">" . $row["id"] . "</span>\n        </div>";
    }
    echo "\n    <div id=\"main\">\n        " . $notes . "\n    </div>\n    ";
}

?>