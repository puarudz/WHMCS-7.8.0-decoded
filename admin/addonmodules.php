<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

define("ADMINAREA", true);
require "../init.php";
$aInt = new WHMCS\Admin("Addon Modules", false);
$aInt->title = $aInt->lang("utilities", "addonmodules");
$aInt->sidebar = "addonmodules";
$aInt->icon = "addonmodules";
global $jquerycode;
global $jscode;
$jquerycode = $jscode = "";
ob_start();
if (!($module = $whmcs->get_req_var("module"))) {
    header("Location: " . routePath("admin-apps-index"));
    exit;
}
$activeaddonmodules = $CONFIG["ActiveAddonModules"];
$activeaddonmodules = explode(",", $activeaddonmodules);
if (!in_array($module, $activeaddonmodules)) {
    $aInt->gracefulExit("Invalid Module Name. Please Try Again.");
}
$modulelink = "addonmodules.php?module=" . $module;
$result = select_query("tbladdonmodules", "value", array("module" => $module, "setting" => "access"));
$data = mysql_fetch_array($result);
$allowedroles = explode(",", $data[0]);
$result = select_query("tbladmins", "roleid", array("id" => $_SESSION["adminid"]));
$data = mysql_fetch_array($result);
$adminroleid = $data[0];
if (!isValidforPath($module)) {
    exit("Invalid Addon Module Name");
}
$modulepath = ROOTDIR . "/modules/addons/" . $module . "/" . $module . ".php";
if (file_exists($modulepath)) {
    require $modulepath;
    if (function_exists($module . "_config")) {
        $configarray = call_user_func($module . "_config");
        $aInt->title = $configarray["name"];
        if (in_array($adminroleid, $allowedroles)) {
            $modulevars = array("module" => $module, "modulelink" => $modulelink);
            $result = select_query("tbladdonmodules", "", array("module" => $module));
            while ($data = mysql_fetch_array($result)) {
                $modulevars[$data["setting"]] = $data["value"];
            }
            $_ADDONLANG = array();
            if (!isValidforPath($aInt->language)) {
                exit("Invalid Admin Language Name");
            }
            $addonlangfile = ROOTDIR . "/modules/addons/" . $module . "/lang/" . $aInt->language . ".php";
            if (file_exists($addonlangfile)) {
                require $addonlangfile;
            } else {
                if ($configarray["language"]) {
                    if (!isValidforPath($configarray["language"])) {
                        exit("Invalid Language Name from Addon Module Config");
                    }
                    $addonlangfile = ROOTDIR . "/modules/addons/" . $module . "/lang/" . $configarray["language"] . ".php";
                    if (file_exists($addonlangfile)) {
                        require $addonlangfile;
                    }
                }
            }
            if (count($_ADDONLANG)) {
                $modulevars["_lang"] = $_ADDONLANG;
            }
            if ($modulevars["version"] != $configarray["version"]) {
                if (function_exists($module . "_upgrade")) {
                    call_user_func($module . "_upgrade", $modulevars);
                }
                update_query("tbladdonmodules", array("value" => $configarray["version"]), array("module" => $module, "setting" => "version"));
            }
            $sidebar = "";
            if (function_exists($module . "_sidebar")) {
                $sidebar = call_user_func($module . "_sidebar", $modulevars);
            }
            $aInt->assign("addon_module_sidebar", $sidebar);
            if (function_exists($module . "_output")) {
                call_user_func($module . "_output", $modulevars);
            } else {
                echo "<p>" . $aInt->lang("addonmodules", "nooutput") . "</p>";
            }
        } else {
            echo "<br /><br />\n<p align=\"center\"><b>" . $aInt->lang("permissions", "accessdenied") . "</b></p>\n<p align=\"center\">" . $aInt->lang("addonmodules", "noaccess") . "</p>\n<p align=\"center\">" . $aInt->lang("addonmodules", "howtogrant") . "</p>";
        }
    } else {
        echo "<p>" . $aInt->lang("addonmodules", "error") . "</p>";
    }
} else {
    $pagetitle = str_replace("_", " ", $module);
    $pagetitle = titleCase($pagetitle);
    echo "<h2>" . $pagetitle . "</h2>";
    if (in_array($adminroleid, $allowedroles)) {
        if (!isValidforPath($module)) {
            exit("Invalid Addon Module Name");
        }
        $modulepath = ROOTDIR . "/modules/admin/" . $module . "/" . $module . ".php";
        if (file_exists($modulepath)) {
            require $modulepath;
        } else {
            echo "<p>" . $aInt->lang("addonmodules", "nooutput") . "</p>";
        }
    } else {
        echo "<br /><br />\n<p align=\"center\"><b>" . $aInt->lang("permissions", "accessdenied") . "</b></p>\n<p align=\"center\">" . $aInt->lang("addonmodules", "noaccess") . "</p>\n<p align=\"center\">" . $aInt->lang("addonmodules", "howtogrant") . "</p>";
    }
}
$content = ob_get_contents();
ob_end_clean();
$aInt->content = $content;
$aInt->jquerycode = $jquerycode;
$aInt->jscode = $jscode;
$aInt->display();

?>