<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Edit Clients Products/Services");
$aInt->title = $aInt->lang("clients", "transferownership");
ob_start();
if ($action == "") {
    echo "<script type=\"text/javascript\">\n\$(document).ready(function(){\n    \$(\"#clientsearchval\").keyup(function () {\n        var useridsearchlength = \$(\"#clientsearchval\").val().length;\n        if (useridsearchlength>2) {\n        WHMCS.http.jqClient.post(whmcsBaseUrl + adminBaseRoutePath + \"/search.php\", { clientsearch: 1, value: \$(\"#clientsearchval\").val(), token: \"" . generate_token("plain") . "\" },\n            function(data){\n                if (data) {\n                    \$(\"#clientsearchresults\").html(data);\n                    \$(\"#clientsearchresults\").slideDown(\"slow\");\n                }\n            });\n        }\n    });\n});\nfunction searchselectclient(userid,name,email) {\n    \$(\"#newuserid\").val(userid);\n    \$(\"#clientsearchresults\").slideUp();\n}\n\nvar whmcsBaseUrl = \"" . WHMCS\Utility\Environment\WebHelper::getBaseUrl() . "\";\nvar adminBaseRoutePath = \"" . WHMCS\Admin\AdminServiceProvider::getAdminRouteBase() . "\";\n</script>\n";
    if ($error) {
        echo "<div class=\"errorbox\">" . $aInt->lang("clients", "invalidowner") . "</div><br />";
    }
    echo "\n<form method=\"post\" action=\"";
    echo $whmcs->getPhpSelf();
    echo "?action=transfer&type=";
    echo $type;
    echo "&id=";
    echo $id;
    echo "\">\n";
    echo $aInt->lang("clients", "transferchoose");
    echo "<br /><br />\n<div align=\"center\">\n";
    echo $aInt->lang("fields", "clientid");
    echo ": <input type=\"text\" name=\"newuserid\" id=\"newuserid\" size=\"10\" /> <input type=\"submit\" value=\"";
    echo $aInt->lang("domains", "transfer");
    echo "\" class=\"button btn btn-default\" /><br /><br />\n";
    echo $aInt->lang("global", "clientsintellisearch");
    echo ": <input type=\"text\" id=\"clientsearchval\" size=\"25\" />\n</div>\n<br />\n<div id=\"clientsearchresults\">\n<div class=\"searchresultheader\">Search Results</div>\n<div class=\"searchresult\" align=\"center\">Matches will appear here as you type</div>\n</div>\n</form>\n\n";
} else {
    check_token("WHMCS.admin.default");
    $newuserid = trim($newuserid);
    $result = select_query("tblclients", "id", array("id" => $newuserid));
    $data = mysql_fetch_array($result);
    $newuserid = $data["id"];
    if (!$newuserid) {
        redir("type=" . $type . "&id=" . $id . "&error=1");
    }
    if ($type == "hosting") {
        $result = select_query("tblhosting", "userid", array("id" => $id));
        $data = mysql_fetch_array($result);
        $moduleInterface = "";
        $hasAppLinks = false;
        try {
            $moduleInterface = new WHMCS\Module\Server();
            if ($moduleInterface->loadByServiceID($id) && $moduleInterface->isApplicationLinkSupported() && $moduleInterface->isApplicationLinkingEnabled()) {
                $call = "Delete";
                $moduleInterface->doSingleApplicationLinkCall($call);
                $hasAppLinks = true;
            }
        } catch (Exception $e) {
        }
        $userid = $data["userid"];
        logActivity("Moved Service ID: " . $id . " from User ID: " . $userid . " to User ID: " . $newuserid, $newuserid);
        update_query("tblhosting", array("userid" => $newuserid), array("id" => $id));
        $addons = Illuminate\Database\Capsule\Manager::table("tblhostingaddons")->where("hostingid", $id)->get();
        $addonsWithAppLinks = array();
        $addonModuleInterface = "";
        $hasAddonAppLinks = false;
        foreach ($addons as $addon) {
            try {
                $addonModuleInterface = new WHMCS\Module\Server();
                if ($addonModuleInterface->loadByAddonId($addon->id) && $addonModuleInterface->isApplicationLinkSupported() && $addonModuleInterface->isApplicationLinkingEnabled()) {
                    $addonsWithAppLinks[] = $addon->id;
                    $call = "Delete";
                    $addonModuleInterface->doSingleApplicationLinkCall($call);
                    $hasAddonAppLinks = true;
                }
            } catch (Exception $e) {
            }
        }
        Illuminate\Database\Capsule\Manager::table("tblhostingaddons")->where("hostingid", $id)->update(array("userid" => $newuserid));
        Illuminate\Database\Capsule\Manager::table("tblsslorders")->where("serviceid", "=", $id)->update(array("userid" => $newuserid));
        if ($hasAppLinks == true) {
            try {
                $moduleInterface = new WHMCS\Module\Server();
                if ($moduleInterface->loadByServiceID($id) && $moduleInterface->isApplicationLinkSupported() && $moduleInterface->isApplicationLinkingEnabled()) {
                    $call = "Create";
                    $moduleInterface->doSingleApplicationLinkCall($call);
                }
            } catch (Exception $e) {
            }
        }
        if ($hasAddonAppLinks) {
            foreach ($addonsWithAppLinks as $addonId) {
                try {
                    $addonModuleInterface = new WHMCS\Module\Server();
                    if ($addonModuleInterface->loadByAddonId($addonId) && $addonModuleInterface->isApplicationLinkSupported() && $addonModuleInterface->isApplicationLinkingEnabled()) {
                        $call = "Create";
                        $addonModuleInterface->doSingleApplicationLinkCall($call);
                    }
                } catch (Exception $e) {
                }
            }
        }
        echo "<script language=\"javascript\">\nwindow.opener.location.href = \"clientshosting.php?userid=";
        echo $newuserid;
        echo "&id=";
        echo $id;
        echo "\";\nwindow.close();\n</script>\n";
    } else {
        if ($type == "domain") {
            $result = select_query("tbldomains", "userid", array("id" => $id));
            $data = mysql_fetch_array($result);
            $userid = $data["userid"];
            logActivity("Moved Domain ID: " . $id . " from User ID: " . $userid . " to User ID: " . $newuserid, $newuserid);
            update_query("tbldomains", array("userid" => $newuserid), array("id" => $id));
            echo "<script language=\"javascript\">\nwindow.opener.location.href = \"clientsdomains.php?userid=";
            echo $newuserid;
            echo "&id=";
            echo $id;
            echo "\";\nwindow.close();\n</script>\n";
        }
    }
}
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->displayPopUp();

?>