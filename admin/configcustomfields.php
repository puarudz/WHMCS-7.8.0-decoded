<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Configure Custom Client Fields");
$aInt->title = $aInt->lang("customfields", "clienttitle");
$aInt->sidebar = "config";
$aInt->icon = "customfields";
$aInt->helplink = "Custom Fields";
$action = $whmcs->get_req_var("action");
if ($action == "save") {
    check_token("WHMCS.admin.default");
    $addfieldname = $whmcs->get_req_var("addfieldname");
    $fieldname = $whmcs->get_req_var("fieldname");
    if ($fieldname) {
        $fieldtype = (array) $whmcs->get_req_var("fieldtype");
        $description = (array) $whmcs->get_req_var("description");
        $fieldoptions = (array) $whmcs->get_req_var("fieldoptions");
        $regexpr = (array) $whmcs->get_req_var("regexpr");
        $adminonly = (array) $whmcs->get_req_var("adminonly");
        $required = (array) $whmcs->get_req_var("required");
        $showorder = (array) $whmcs->get_req_var("showorder");
        $showinvoice = (array) $whmcs->get_req_var("showinvoice");
        $sortorder = (array) $whmcs->get_req_var("sortorder");
        foreach ($fieldname as $fid => $value) {
            update_query("tblcustomfields", array("fieldname" => $value, "fieldtype" => $fieldtype[$fid], "description" => $description[$fid], "fieldoptions" => $fieldoptions[$fid], "regexpr" => WHMCS\Input\Sanitize::decode($regexpr[$fid]), "adminonly" => $adminonly[$fid], "required" => $required[$fid], "showorder" => $showorder[$fid], "showinvoice" => $showinvoice[$fid], "sortorder" => $sortorder[$fid]), array("id" => $fid));
            logAdminActivity("Client Custom Field Updated: '" . $value . "' - Custom Field ID: " . $fid);
        }
    }
    if ($addfieldname) {
        $addfieldtype = $whmcs->get_req_var("addfieldtype");
        $adddescription = $whmcs->get_req_var("adddescription");
        $addfieldoptions = $whmcs->get_req_var("addfieldoptions");
        $addregexpr = $whmcs->get_req_var("addregexpr");
        $addadminonly = $whmcs->get_req_var("addadminonly");
        $addrequired = $whmcs->get_req_var("addrequired");
        $addshoworder = $whmcs->get_req_var("addshoworder");
        $addshowinvoice = $whmcs->get_req_var("addshowinvoice");
        $addsortorder = $whmcs->get_req_var("addsortorder");
        $id = insert_query("tblcustomfields", array("type" => "client", "fieldname" => $addfieldname, "fieldtype" => $addfieldtype, "description" => $adddescription, "fieldoptions" => $addfieldoptions, "regexpr" => WHMCS\Input\Sanitize::decode($addregexpr), "adminonly" => $addadminonly, "required" => $addrequired, "showorder" => $addshoworder, "showinvoice" => $addshowinvoice, "sortorder" => $addsortorder));
        if (WHMCS\Config\Setting::getValue("EnableTranslations")) {
            WHMCS\Language\DynamicTranslation::saveNewTranslations($id, array("custom_field.{id}.name", "custom_field.{id}.description"));
        }
        logAdminActivity("Client Custom Field Created: '" . $addfieldname . "' - Custom Field ID: " . $id);
    }
    redir("success=true");
} else {
    if ($action == "delete") {
        check_token("WHMCS.admin.default");
        $id = (int) $whmcs->get_req_var("id");
        $customField = WHMCS\CustomField::find($id)->delete();
        logAdminActivity("Client Custom Field Deleted: '" . $customField->fieldname . "' - Custom Field ID: " . $id);
        redir("deleted=true");
    }
}
if (WHMCS\Config\Setting::getValue("EnableTranslations")) {
    WHMCS\Language\DynamicTranslation::whereIn("related_type", array("custom_field.{id}.name", "custom_field.{id}.description"))->where("related_id", "=", 0)->delete();
}
$aInt->deleteJSConfirm("doDelete", "customfields", "delsure", $_SERVER["PHP_SELF"] . "?action=delete&id=");
ob_start();
if ($whmcs->get_req_var("success")) {
    infoBox($aInt->lang("global", "changesuccess"), $aInt->lang("global", "changesuccessdesc"));
}
echo $infobox;
echo "\n<p>";
echo $aInt->lang("customfields", "clientinfo");
echo "</p>\n<form method=\"post\" action=\"";
echo $_SERVER["PHP_SELF"];
echo "?action=save\">\n";
$result = select_query("tblcustomfields", "", array("type" => "client"), "sortorder` ASC,`id", "ASC");
while ($data = mysql_fetch_array($result)) {
    $fid = $data["id"];
    $fieldname = $data["fieldname"];
    $fieldtype = $data["fieldtype"];
    $description = $data["description"];
    $fieldoptions = $data["fieldoptions"];
    $regexpr = $data["regexpr"];
    $adminonly = $data["adminonly"];
    $required = $data["required"];
    $showorder = $data["showorder"];
    $showinvoice = $data["showinvoice"];
    $sortorder = $data["sortorder"];
    echo "<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr>\n    <td class=\"fieldlabel\">\n        ";
    echo $aInt->lang("customfields", "fieldname");
    echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"fieldname[";
    echo $fid;
    echo "]\" value=\"";
    echo $fieldname;
    echo "\" class=\"form-control input-inline input-400\" />\n        ";
    echo $aInt->getTranslationLink("custom_field.name", $fid, "client");
    echo "        <div class=\"pull-right\">\n            ";
    echo $aInt->lang("customfields", "order");
    echo "            <input type=\"text\" name=\"sortorder[";
    echo $fid;
    echo "]\" value=\"";
    echo $sortorder;
    echo "\" class=\"form-control input-inline input-100 text-center\">\n        </div>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
    echo $aInt->lang("customfields", "fieldtype");
    echo "</td><td class=\"fieldarea\"><select name=\"fieldtype[";
    echo $fid;
    echo "]\" class=\"form-control select-inline\">\n<option value=\"text\"";
    if ($fieldtype == "text") {
        echo " selected";
    }
    echo ">";
    echo $aInt->lang("customfields", "typetextbox");
    echo "</option>\n<option value=\"link\"";
    if ($fieldtype == "link") {
        echo " selected";
    }
    echo ">";
    echo $aInt->lang("customfields", "typelink");
    echo "</option>\n<option value=\"password\"";
    if ($fieldtype == "password") {
        echo " selected";
    }
    echo ">";
    echo $aInt->lang("customfields", "typepassword");
    echo "</option>\n<option value=\"dropdown\"";
    if ($fieldtype == "dropdown") {
        echo " selected";
    }
    echo ">";
    echo $aInt->lang("customfields", "typedropdown");
    echo "</option>\n<option value=\"tickbox\"";
    if ($fieldtype == "tickbox") {
        echo " selected";
    }
    echo ">";
    echo $aInt->lang("customfields", "typetickbox");
    echo "</option>\n<option value=\"textarea\"";
    if ($fieldtype == "textarea") {
        echo " selected";
    }
    echo ">";
    echo $aInt->lang("customfields", "typetextarea");
    echo "</option>\n</select></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
    echo $aInt->lang("fields", "description");
    echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"description[";
    echo $fid;
    echo "]\" value=\"";
    echo $description;
    echo "\" class=\"form-control input-inline input-500\" />\n        ";
    echo $aInt->getTranslationLink("custom_field.description", $fid, "client");
    echo "        ";
    echo $aInt->lang("customfields", "descriptioninfo");
    echo "    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
    echo $aInt->lang("customfields", "validation");
    echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"regexpr[";
    echo $fid;
    echo "]\" value=\"";
    echo WHMCS\Input\Sanitize::encode($regexpr);
    echo "\" class=\"form-control input-inline input-500\"> ";
    echo $aInt->lang("customfields", "validationinfo");
    echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
    echo $aInt->lang("customfields", "selectoptions");
    echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"fieldoptions[";
    echo $fid;
    echo "]\" value=\"";
    echo $fieldoptions;
    echo "\" class=\"form-control input-inline input-500\"> ";
    echo $aInt->lang("customfields", "selectoptionsinfo");
    echo "</td></tr>\n    <tr>\n        <td class=\"fieldlabel\"></td>\n        <td class=\"fieldarea\">\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"adminonly[";
    echo $fid;
    echo "]\"";
    if ($adminonly == "on") {
        echo " checked";
    }
    echo ">\n                ";
    echo $aInt->lang("customfields", "adminonly");
    echo "            </label>\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"required[";
    echo $fid;
    echo "]\"";
    if ($required == "on") {
        echo " checked";
    }
    echo ">\n                ";
    echo $aInt->lang("customfields", "requiredfield");
    echo "            </label>\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"showorder[";
    echo $fid;
    echo "]\"";
    if ($showorder == "on") {
        echo " checked";
    }
    echo ">\n                ";
    echo $aInt->lang("customfields", "orderform");
    echo "            </label>\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"showinvoice[";
    echo $fid;
    echo "]\"";
    if ($showinvoice) {
        echo " checked";
    }
    echo ">\n                ";
    echo $aInt->lang("customfields", "showinvoice");
    echo "            </label>\n            <div class=\"pull-right\">\n                <a href=\"#\" onclick=\"doDelete('";
    echo $fid;
    echo "');return false\" class=\"btn btn-danger btn-xs\">";
    echo $aInt->lang("customfields", "deletefield");
    echo "</a>\n            </div>\n        </td>\n    </tr>\n</table>\n<br>\n";
}
echo "<b>";
echo $aInt->lang("customfields", "addfield");
echo "</b><br><br>\n<table class=\"form\" width=\"100%\" border=\"0\" cellspacing=\"2\" cellpadding=\"3\">\n<tr>\n    <td width=100 class=\"fieldlabel\">\n        ";
echo $aInt->lang("customfields", "fieldname");
echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"addfieldname\" class=\"form-control input-inline input-400\" />\n        ";
echo $aInt->getTranslationLink("custom_field.name", 0, "client");
echo "        <div class=\"pull-right\">\n            ";
echo $aInt->lang("customfields", "order");
echo "            <input type=\"text\" name=\"addsortorder\" value=\"0\" class=\"form-control input-inline input-100 text-center\" />\n        </div>\n    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("customfields", "fieldtype");
echo "</td><td class=\"fieldarea\"><select name=\"addfieldtype\" class=\"form-control select-inline\">\n<option value=\"text\">";
echo $aInt->lang("customfields", "typetextbox");
echo "</option>\n<option value=\"link\">";
echo $aInt->lang("customfields", "typelink");
echo "</option>\n<option value=\"password\">";
echo $aInt->lang("customfields", "typepassword");
echo "</option>\n<option value=\"dropdown\">";
echo $aInt->lang("customfields", "typedropdown");
echo "</option>\n<option value=\"tickbox\">";
echo $aInt->lang("customfields", "typetickbox");
echo "</option>\n<option value=\"textarea\">";
echo $aInt->lang("customfields", "typetextarea");
echo "</option>\n</select></td></tr>\n<tr>\n    <td class=\"fieldlabel\">\n        ";
echo $aInt->lang("fields", "description");
echo "    </td>\n    <td class=\"fieldarea\">\n        <input type=\"text\" name=\"adddescription\" class=\"form-control input-inline input-500\" />\n        ";
echo $aInt->getTranslationLink("custom_field.description", 0, "client");
echo "        ";
echo $aInt->lang("customfields", "descriptioninfo");
echo "    </td>\n</tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("customfields", "validation");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"addregexpr\" class=\"form-control input-inline input-500\"> ";
echo $aInt->lang("customfields", "validationinfo");
echo "</td></tr>\n<tr><td class=\"fieldlabel\">";
echo $aInt->lang("customfields", "selectoptions");
echo "</td><td class=\"fieldarea\"><input type=\"text\" name=\"addfieldoptions\" class=\"form-control input-inline input-500\"> ";
echo $aInt->lang("customfields", "selectoptionsinfo");
echo "</td></tr>\n    <tr>\n        <td class=\"fieldlabel\"></td>\n        <td class=\"fieldarea\">\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"addadminonly\">\n                ";
echo $aInt->lang("customfields", "adminonly");
echo "            </label>\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"addrequired\">\n                ";
echo $aInt->lang("customfields", "requiredfield");
echo "            </label>\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"addshoworder\">\n                ";
echo $aInt->lang("customfields", "orderform");
echo "            </label>\n            <label class=\"checkbox-inline\">\n                <input type=\"checkbox\" name=\"addshowinvoice\">\n                ";
echo $aInt->lang("customfields", "showinvoice");
echo "            </label>\n        </td>\n    </tr>\n</table>\n\n<div class=\"btn-container\">\n    <input type=\"submit\" value=\"";
echo $aInt->lang("global", "savechanges");
echo "\" class=\"btn btn-primary\" />\n    <input type=\"reset\" value=\"";
echo $aInt->lang("global", "cancelchanges");
echo "\" class=\"btn btn-default\" />\n</div>\n</form>\n\n";
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->display();

?>