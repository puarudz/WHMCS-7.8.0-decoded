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
if (defined("LICENSINGADDONLICENSE")) {
    exit("License Hacking Attempt Detected");
}
global $whmcs;
global $licensing;
if ($whmcs->get_req_var("larefresh")) {
    $licensing->forceRemoteCheck();
}
define("LICENSINGADDONLICENSE", $licensing->isActiveAddon("Licensing Addon"));
function licensing_MetaData()
{
    return array("addonLicenseRequired" => true, "addonLicenseName" => "Licensing Addon");
}
function licensing_config()
{
    $configarray = array("name" => "Licensing Manager", "description" => "License, restrict & distribute your own coding and applications using the same system we use for WHMCS.<br />Find out more & purchase @ <a href=\"http://go.whmcs.com/94/licensing-addon\" target=\"_blank\">www.whmcs.com/addons/licensing-addon</a>", "premium" => true, "version" => "3.1", "author" => "WHMCS", "language" => "english", "fields" => array());
    if (!LICENSINGADDONLICENSE) {
        $configarray["fields"]["license"] = array("FriendlyName" => "License Check Failed", "Type" => "", "Description" => "You need to purchase the licensing addon from <a href=\"http://go.whmcs.com/94/licensing-addon\" target=\"_blank\">www.whmcs.com/addons/licensing-addon</a> before you can use this functionality. If you just purchased it recently, please <a href=\"configaddonmods.php?larefresh=1#licensing\">click here</a> to refresh this message");
    } else {
        $configarray["fields"] = array("clientverifytool" => array("FriendlyName" => "Public License Verification Tool", "Type" => "yesno", "Description" => "Tick this box to enable the Client Area License Verification Tool (accessed via /index.php?m=licensing)"), "maxreissues" => array("FriendlyName" => "Maximum Allowed Reissues", "Type" => "text", "Size" => "4", "Default" => "10", "Description" => "Enter the maximum number of reissues you want to allow (abuse protection)"), "logprune" => array("FriendlyName" => "Auto Logs Prune", "Type" => "text", "Size" => "4", "Default" => "90", "Description" => "Enter the number of days to keep license access log history for"));
    }
    return $configarray;
}
function licensing_activate()
{
    $query = "CREATE TABLE `mod_licensing` (`id` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,`serviceid` INT( 10 ) NOT NULL ,`addon_id` INT(10) NOT NULL DEFAULT  0,`licensekey` TEXT NOT NULL ,`validdomain` TEXT NOT NULL ,`validip` TEXT NOT NULL ,`validdirectory` TEXT NOT NULL ,`reissues` INT( 1 ) NOT NULL ,`status` ENUM( 'Active', 'Reissued', 'Suspended', 'Expired' ) NOT NULL ,`lastaccess` datetime NOT NULL default '0000-00-00 00:00:00')";
    full_query($query);
    $query = "CREATE TABLE `mod_licensinglog` (`id` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,`licenseid` INT( 10 ) NOT NULL ,`domain` TEXT NOT NULL ,`ip` TEXT NOT NULL ,`path` TEXT NOT NULL ,`message` TEXT NOT NULL ,`datetime` datetime NOT NULL default '0000-00-00 00:00:00')";
    full_query($query);
    $query = "CREATE TABLE `mod_licensingbans` (`id` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,`type` VARCHAR(1) NOT NULL ,`value` TEXT NOT NULL ,`notes` TEXT NOT NULL )";
    full_query($query);
}
function licensing_deactivate()
{
    $query = "DROP TABLE `mod_licensing`";
    full_query($query);
    $query = "DROP TABLE `mod_licensinglog`";
    full_query($query);
    $query = "DROP TABLE `mod_licensingbans`";
    full_query($query);
}
function licensing_addon_valid_input_clean($vals)
{
    $vals = explode(",", $vals);
    foreach ($vals as $k => $v) {
        $vals[$k] = trim($v, " \t\n\r");
    }
    return implode(",", $vals);
}
function licensing_output($vars)
{
    global $whmcs;
    global $licensing;
    global $aInt;
    global $numrows;
    global $tabledata;
    global $orderby;
    global $order;
    global $page;
    global $limit;
    global $jscode;
    if (!LICENSINGADDONLICENSE) {
        if ($whmcs->get_req_var("refresh")) {
            $licensing->forceRemoteCheck();
            redir("module=licensing");
        }
        echo "<div class=\"gracefulexit\">\nYour WHMCS license key is not enabled to use the Licensing Addon yet.<br /><br />\nYou can find out more about it and purchase @ <a href=\"http://go.whmcs.com/94/licensing-addon\" target=\"_blank\">www.whmcs.com/addons/licensing-addon</a><br /><br />\nIf you have only recently purchased the addon, please <a href=\"addonmodules.php?module=licensing&refresh=1\">click here</a> to perform a license refresh.\n</div>";
        return false;
    }
    $modulelink = $vars["modulelink"];
    $action = isset($_REQUEST["action"]) ? $_REQUEST["action"] : "";
    $id = (int) $_REQUEST["id"];
    echo "<style>\n.licensinglinksbar {\n    padding-bottom: 5px;\n    border-bottom: 2px solid #6CAD41;\n}\n\n.panel.panel-accent-gold {\n    border-top: 3px solid #f0ad4e;\n}\n.panel.panel-accent-green {\n    border-top: 3px solid #5cb85c;\n}\n.panel.panel-accent-red {\n    border-top: 3px solid #d9534f;\n}\n.panel.panel-accent-blue {\n    border-top: 3px solid #5bc0de;\n}\n.panel.panel-accent-asbestos {\n    border-top: 3px solid #7f8c8d;\n}\n\n.panel-body.license-count {\n    font-size: 3em;\n    font-weight: bold;\n    text-align: center;\n}\n</style>\n\n<div class=\"licensinglinksbar\">\n    <ul class=\"nav nav-pills\">\n      <li role=\"presentation\"" . (empty($action) ? " class=\"active\"" : "") . "><a href=\"" . $modulelink . "\">Home</a></li>\n      <li role=\"presentation\"" . ($action == "list" ? " class=\"active\"" : "") . "><a href=\"" . $modulelink . "&action=list\">Search/Browse Licenses</a></li>\n      <li role=\"presentation\"" . ($action == "bans" ? " class=\"active\"" : "") . "><a href=\"" . $modulelink . "&action=bans\">Ban Control</a></li>\n      <li role=\"presentation\"" . ($action == "log" ? " class=\"active\"" : "") . "><a href=\"" . $modulelink . "&action=log\">License Access Logs</a></li>\n      <li role=\"presentation\"><a href=\"https://docs.whmcs.com/Licensing_Addon\" target=\"_blank\">Help</a></li>\n      ";
    if ($action == "manage") {
        echo "<li role=\"presentation\" class=\"active\"><a href=\"#\">Manage This License</a></li>";
    }
    echo "\n    </ul>\n</div>\n";
    if (!$action) {
        echo "\n<div class=\"row\">\n    <div class=\"col-md-9 pull-md-left\">\n        <h2>Statistics</h2>\n        <div class=\"row\">\n            <div class=\"col-sm-4\">\n                <div class=\"panel panel-default panel-accent-green\">\n                    <div class=\"panel-heading\">\n                        <h3 class=\"panel-title\">Active Licenses</h3>\n                    </div>\n                    <div class=\"panel-body license-count\">\n                        ";
        echo number_format(get_query_val("mod_licensing", "COUNT(*)", "status='Reissued' OR status='Active'"), 0, ".", ",");
        echo "                    </div>\n                </div>\n            </div>\n            <div class=\"col-sm-4\">\n                <div class=\"panel panel-default panel-accent-red\">\n                    <div class=\"panel-heading\">\n                        <h3 class=\"panel-title\">Suspended Licenses</h3>\n                    </div>\n                    <div class=\"panel-body license-count\">\n                        ";
        echo number_format(get_query_val("mod_licensing", "COUNT(*)", "status='Suspended'"), 0, ".", ",");
        echo "                    </div>\n                </div>\n            </div>\n            <div class=\"col-sm-4\">\n                <div class=\"panel panel-default panel-accent-asbestos\">\n                    <div class=\"panel-heading\">\n                        <h3 class=\"panel-title\">Expired Licenses</h3>\n                    </div>\n                    <div class=\"panel-body license-count\">\n                        ";
        echo number_format(get_query_val("mod_licensing", "COUNT(*)", "status='Expired'"), 0, ".", ",");
        echo "                    </div>\n                </div>\n            </div>\n        </div>\n        <div class=\"row\">\n            <div class=\"col-sm-6\">\n                <div class=\"panel panel-default panel-accent-gold\">\n                    <div class=\"panel-heading\">\n                        <h3 class=\"panel-title\">Total Licenses in Database</h3>\n                    </div>\n                    <div class=\"panel-body license-count\">\n                        ";
        echo number_format(get_query_val("mod_licensing", "COUNT(*)", ""), 0, ".", ",");
        echo "                    </div>\n                </div>\n            </div>\n            <div class=\"col-sm-6\">\n                <div class=\"panel panel-default panel-accent-blue\">\n                    <div class=\"panel-heading\">\n                        <h3 class=\"panel-title\">Accessed within the Past 30 Days</h3>\n                    </div>\n                    <div class=\"panel-body license-count\">\n                        ";
        echo number_format(get_query_val("mod_licensing", "COUNT(*)", "lastaccess>='" . date("Y-m-d", mktime(0, 0, 0, date("m"), date("d") - 30, date("Y"))) . "'"), 0, ".", ",");
        echo "                    </div>\n                </div>\n            </div>\n        </div>\n    </div>\n\n    <div class=\"col-md-3 pull-md-right\">\n        <h2>Search</h2>\n        <form method=\"post\" action=\"";
        echo $modulelink;
        echo "&action=list\">\n            <div class=\"form-group\">\n                <label for=\"inputPid\" class=\"control-label\">Product/License</label>\n                <select name=\"search_pid\" id=\"inputPid\" class=\"form-control\">\n                    <option value=\"0\">- Any -</option>";
        $products = WHMCS\Product\Product::where("servertype", "=", "licensing")->get();
        foreach ($products as $product) {
            echo "<option value=\"" . $product->id . "\">" . $product->name . "</option>";
        }
        $addons = WHMCS\Product\Addon::where("module", "=", "licensing")->get();
        foreach ($addons as $addon) {
            echo "<option value=\"a" . $addon->id . "\">" . $addon->name . "</option>";
        }
        echo "</select>\n            </div>\n            <div class=\"form-group\">\n                <label for=\"inputLicensekey\" class=\"control-label\">License Key</label>\n                <input type=\"text\" name=\"search_licensekey\" id=\"inputLicensekey\" class=\"form-control\" value=\"";
        echo $search_licensekey;
        echo "\" />\n            </div>\n            <div class=\"form-group\">\n                <label for=\"inputDomain\" class=\"control-label\">Domain</label>\n                <input type=\"text\" name=\"search_domain\" id=\"inputDomain\" class=\"form-control\" value=\"";
        echo $search_domain;
        echo "\" />\n            </div>\n            <div class=\"form-group\">\n                <label for=\"inputIp\" class=\"control-label\">IP</label>\n                <input type=\"text\" name=\"search_ip\" id=\"inputIp\" class=\"form-control\" value=\"";
        echo $search_ip;
        echo "\" />\n            </div>\n            <div class=\"form-group\">\n                <label for=\"inputDir\" class=\"control-label\">Directory</label>\n                <input type=\"text\" name=\"search_dir\" id=\"inputDir\" class=\"form-control\" value=\"";
        echo $search_dir;
        echo "\" />\n            </div>\n            <div class=\"form-group\">\n                <label for=\"inputStatus\" class=\"control-label\">Status</label>\n                <select name=\"search_status\" id=\"inputStatus\" class=\"form-control\">\n                    <option value=\"\">- Any -</option>\n                    <option>Reissued</option>\n                    <option>Active</option>\n                    <option>Suspended</option>\n                    <option>Expired</option>\n                </select>\n            </div>\n            <input class=\"btn btn-primary btn-block\" type=\"submit\" value=\"Search\" /?>\n        </form>\n    </div>\n</div>\n\n\n";
    } else {
        if ($action == "list") {
            echo "    <h2>Search/Browse Licenses</h2>\n\n    <form method=\"post\" class=\"form-horizontal\" action=\"";
            echo $modulelink;
            echo "&action=list\">\n        <div class=\"form-group\">\n            <label for=\"inputPid\" class=\"col-sm-2 control-label\">Product/License</label>\n            <div class=\"col-sm-10\">\n                <select name=\"search_pid\" id=\"inputPid\" class=\"form-control\">\n                    <option value=\"0\">- Any -</option>";
            $result = select_query("tblproducts", "id,name", array("servertype" => "licensing"), "name", "ASC");
            while ($data = mysql_fetch_array($result)) {
                echo "<option value=\"" . $data["id"] . "\">" . $data["name"] . "</option>";
            }
            $products = WHMCS\Product\Product::where("servertype", "=", "licensing")->orderBy("name")->get();
            foreach ($products as $product) {
                echo "<option value=\"" . $product->id . "\">" . $product->name . "</option>";
            }
            $addons = WHMCS\Product\Addon::where("module", "=", "licensing")->orderBy("name")->get();
            foreach ($addons as $addon) {
                echo "<option value=\"a" . $addon->id . "\">" . $addon->name . "</option>";
            }
            echo "</select>\n            </div>\n        </div>\n        <div class=\"form-group\">\n            <label for=\"inputLicensekey\" class=\"col-sm-2 control-label\">License Key</label>\n            <div class=\"col-sm-10\">\n                <input type=\"text\" name=\"search_licensekey\" id=\"inputLicensekey\" class=\"form-control\" value=\"";
            echo $_REQUEST["search_licensekey"];
            echo "\" />\n            </div>\n        </div>\n        <div class=\"form-group\">\n            <label for=\"inputDomain\" class=\"col-sm-2 control-label\">Domain</label>\n            <div class=\"col-sm-10\">\n                <input type=\"text\" name=\"search_domain\" id=\"inputDomain\" class=\"form-control\" value=\"";
            echo $_REQUEST["search_domain"];
            echo "\" />\n            </div>\n        </div>\n        <div class=\"form-group\">\n            <label for=\"inputIp\" class=\"col-sm-2 control-label\">IP</label>\n            <div class=\"col-sm-10\">\n                <input type=\"text\" name=\"search_ip\" id=\"inputIp\" class=\"form-control\" value=\"";
            echo $_REQUEST["search_ip"];
            echo "\" />\n            </div>\n        </div>\n        <div class=\"form-group\">\n            <label for=\"inputDir\" class=\"col-sm-2 control-label\">Directory</label>\n            <div class=\"col-sm-10\">\n                <input type=\"text\" name=\"search_dir\" id=\"inputDir\" class=\"form-control\" value=\"";
            echo $_REQUEST["search_dir"];
            echo "\" />\n            </div>\n        </div>\n        <div class=\"form-group\">\n            <label for=\"inputStatus\" class=\"col-sm-2 control-label\">Status</label>\n            <div class=\"col-sm-10\">\n                <select name=\"search_status\" id=\"inputStatus\" class=\"form-control\">\n                    <option value=\"\">- Any -</option>\n                    <option";
            echo $_REQUEST["search_status"] == "Reissued" ? " selected" : "";
            echo ">Reissued</option>\n                    <option";
            echo $_REQUEST["search_status"] == "Active" ? " selected" : "";
            echo ">Active</option>\n                    <option";
            echo $_REQUEST["search_status"] == "Suspended" ? " selected" : "";
            echo ">Suspended</option>\n                    <option";
            echo $_REQUEST["search_status"] == "Expired" ? " selected" : "";
            echo ">Expired</option>\n                </select>\n            </div>\n        </div>\n        <div class=\"col-md-4 col-md-offset-4\">\n            <input class=\"btn btn-primary btn-block\" type=\"submit\" value=\"Search\" />\n        </div>\n        <div class=\"clearfix\"></div>\n    </form>\n    <div class=\"clearfix\"></div>\n\n    <h2>Result Set</h2>\n\n    ";
            $where = array();
            $addon = false;
            if ($_REQUEST["search_pid"] && substr($_REQUEST["search_pid"], 0, 1) == "a") {
                $addon = true;
                $where["packageid"] = $_REQUEST["search_pid"];
                $join = "tblhostingaddons ON tblhostingaddons.id=mod_licensing.addon_id";
            } else {
                if ($_REQUEST["search_pid"]) {
                    $where["packageid"] = $_REQUEST["search_pid"];
                    $join = "tblhosting ON tblhosting.id=mod_licensing.serviceid";
                }
            }
            if ($_REQUEST["search_licensekey"]) {
                $where["licensekey"] = array("sqltype" => "LIKE", "value" => trim($_REQUEST["search_licensekey"]));
            }
            if ($_REQUEST["search_domain"]) {
                $where["validdomain"] = array("sqltype" => "LIKE", "value" => trim($_REQUEST["search_domain"]));
            }
            if ($_REQUEST["search_ip"]) {
                $where["validip"] = array("sqltype" => "LIKE", "value" => trim($_REQUEST["search_ip"]));
            }
            if ($_REQUEST["search_dir"]) {
                $where["validdirectory"] = array("sqltype" => "LIKE", "value" => trim($_REQUEST["search_dir"]));
            }
            if ($_REQUEST["search_status"]) {
                $where["status"] = $_REQUEST["search_status"];
            }
            $aInt->sortableTableInit("id", "ASC");
            if (!in_array($orderby, array("id", "licensekey", "validdomain", "validip", "lastaccess", "status"))) {
                $orderby = "id";
            }
            $result = select_query("mod_licensing", "mod_licensing.*", $where, $orderby, $order, "", $join);
            $numrows = mysql_num_rows($result);
            if (count($where) && $numrows == 1) {
                $data = mysql_fetch_array($result);
                $id = $data["id"];
                redir("module=licensing&action=manage&id=" . $id);
            }
            $result = select_query("mod_licensing", "mod_licensing.*", $where, $orderby, $order, $page * $limit . "," . $limit, $join);
            while ($data = mysql_fetch_array($result)) {
                $id = $data["id"];
                $serviceid = $data["serviceid"];
                $addonId = $data["addon_id"];
                $licensekey = $data["licensekey"];
                $validdomain = $data["validdomain"];
                $validip = $data["validip"];
                $validdirectory = $data["validdirectory"];
                $status = $data["status"];
                $lastaccess = $data["lastaccess"];
                if ($lastaccess == "0000-00-00 00:00:00") {
                    $lastaccess = "Never";
                } else {
                    $lastaccess = fromMySQLDate($lastaccess, "time");
                }
                $validdomain = explode(",", $validdomain);
                $validip = explode(",", $validip);
                $validdirectory = explode(",", $validdirectory);
                $validdomain = $validdomain[0];
                $validip = $validip[0];
                $validdirectory = $validdirectory[0];
                if ($addonId) {
                    $userId = WHMCS\Service\Addon::find($addonId)->clientId;
                    $uri = "clientshosting.php?userid=" . $userId . "&id=" . $serviceid . "&aid=" . $addonId;
                } else {
                    $userId = WHMCS\Service\Service::find($serviceid)->clientId;
                    $uri = "clientshosting.php?userid=" . $userId . "&id=" . $serviceid;
                }
                $tabledata[] = array("<a href=\"" . $uri . "\" target=\"_blank\">" . $licensekey . "</a>", $validdomain, $validip, $lastaccess, $status, "<a href=\"" . $modulelink . "&action=manage&id=" . $id . "\"><img src=\"images/edit.gif\" border=\"0\"></a>");
            }
            echo $aInt->sortableTable(array(array("licensekey", "License Key"), array("validdomain", "Valid Domains"), array("validip", "Valid IPs"), array("lastaccess", "Last Access"), array("status", "Status"), ""), $tabledata);
        } else {
            if ($action == "manage") {
                if ($_REQUEST["save"]) {
                    update_query("mod_licensing", array("validdomain" => licensing_addon_valid_input_clean($_REQUEST["validdomain"]), "validip" => licensing_addon_valid_input_clean($_REQUEST["validip"]), "validdirectory" => licensing_addon_valid_input_clean($_REQUEST["validdirectory"]), "reissues" => $_REQUEST["reissues"], "status" => $_REQUEST["status"]), array("id" => $id));
                    redir("module=licensing&action=manage&id=" . $id);
                }
                $result = select_query("mod_licensing", "", array("id" => $id));
                $data = mysql_fetch_array($result);
                $id = $data["id"];
                if (!$id) {
                    echo infoBox("License Not Found", "License Not Found. Please go back and try again.", "error");
                    return false;
                }
                $serviceid = $data["serviceid"];
                $addonId = $data["addon_id"];
                $licensekey = $data["licensekey"];
                $validdomain = $data["validdomain"];
                $validip = $data["validip"];
                $validdirectory = $data["validdirectory"];
                $reissues = $data["reissues"];
                $status = $data["status"];
                $lastaccess = $data["lastaccess"];
                if ($addonId) {
                    $model = WHMCS\Service\Addon::with("productAddon")->find($addonId);
                    $productname = $model->productAddon->name;
                    $uri = "clientshosting.php?userid=" . $model->clientId . "&id=" . $model->serviceId . "&aid=" . $model->id;
                } else {
                    $model = WHMCS\Service\Service::with("product", "product.productGroup")->find($serviceid);
                    $productname = $model->product->productGroup->name . " - " . $model->product->name;
                    $uri = "clientshosting.php?userid=" . $model->clientId . "&id=" . $model->id;
                }
                $userId = $model->clientId;
                if ($lastaccess == "0000-00-00 00:00:00") {
                    $lastaccess = "Never";
                } else {
                    $lastaccess = fromMySQLDate($lastaccess, "time");
                }
                echo "\n<h2>Manage License Key: ";
                echo $licensekey;
                echo "</h2>\n\n<form method=\"post\" class=\"form-horizontal\" action=\"";
                echo $modulelink;
                echo "&action=manage&id=";
                echo $id;
                echo "\">\n    <input type=\"hidden\" name=\"save\" value=\"true\" />\n    <div class=\"form-group\">\n        <label for=\"product\" class=\"col-sm-3 control-label\">Product/Service</label>\n        <div class=\"col-sm-9\">\n            <div class=\"input-group\">\n                <input type=\"text\" name=\"product\" class=\"form-control\" value=\"";
                echo $productname;
                echo "\" disabled=\"disabled\"/>\n                <span class=\"input-group-btn\">\n                    <a href=\"";
                echo $uri;
                echo "\" class=\"btn btn-default\">Product Details &raquo;</a>\n                </span>\n            </div>\n        </div>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"inputDomain\" class=\"col-sm-3 control-label\">Valid Domains</label>\n        <div class=\"col-sm-9\">\n            <textarea name=\"validdomain\" id=\"inputDomain\" class=\"form-control\">";
                echo $validdomain;
                echo "</textarea>\n        </div>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"inputIp\" class=\"col-sm-3 control-label\">Valid IPs</label>\n        <div class=\"col-sm-9\">\n            <textarea name=\"validip\" id=\"inputIp\" class=\"form-control\">";
                echo $validip;
                echo "</textarea>\n        </div>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"inputDirectory\" class=\"col-sm-3 control-label\">Valid Directories</label>\n        <div class=\"col-sm-9\">\n            <textarea name=\"validdirectory\" id=\"inputDirectory\" class=\"form-control\">";
                echo $validdirectory;
                echo "</textarea>\n        </div>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"inputReissues\" class=\"col-sm-3 control-label\">Number of Reissues</label>\n        <div class=\"col-sm-9\">\n            <input type=\"text\" name=\"reissues\" id=\"inputReissues\" class=\"form-control\" value=\"";
                echo $reissues;
                echo "\" />\n        </div>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"inputStatus\" class=\"col-sm-3 control-label\">Status</label>\n        <div class=\"col-sm-9\">\n            <select name=\"status\" id=\"inputStatus\" class=\"form-control\">\n                <option";
                echo $status == "Reissued" ? " selected" : "";
                echo ">Reissued</option>\n                <option";
                echo $status == "Active" ? " selected" : "";
                echo ">Active</option>\n                <option";
                echo $status == "Suspended" ? " selected" : "";
                echo ">Suspended</option>\n                <option";
                echo $status == "Expired" ? " selected" : "";
                echo ">Expired</option>\n            </select>\n        </div>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"inputLastAccess\" class=\"col-sm-3 control-label\">Last Access</label>\n        <div class=\"col-sm-9\">\n            <input type=\"text\" name=\"lastaccess\" id=\"inputLastAccess\" class=\"form-control\" value=\"";
                echo $lastaccess;
                echo "\" disabled=\"disabled\"/>\n        </div>\n    </div>\n    <div class=\"col-md-4 col-md-offset-2\">\n        <button type=\"button\" class=\"btn btn-default btn-block\" onclick=\"history.go(-1)\">&laquo; Back to List</button>\n    </div>\n    <div class=\"col-md-4\">\n        <input class=\"btn btn-primary btn-block\" type=\"submit\" value=\"Save Changes\" /?>\n    </div>\n    <div class=\"clearfix\"></div>\n</form>\n\n<h2>Recent Access</h2>\n\n";
                $aInt->sortableTableInit("nopagination");
                $result = select_query("mod_licensinglog", "", array("licenseid" => $id), "id", "DESC", "0,10");
                while ($data = mysql_fetch_array($result)) {
                    $domain = $data["domain"];
                    $ip = $data["ip"];
                    $path = $data["path"];
                    $message = $data["message"];
                    $datetime = $data["datetime"];
                    $datetime = fromMySQLDate($datetime, true);
                    $tabledata[] = array($datetime, $domain, $ip, $path, $message);
                }
                echo $aInt->sortableTable(array("Date", "Domain", "IP", "Path", "Result"), $tabledata);
            } else {
                if ($action == "bans") {
                    if ($_REQUEST["save"]) {
                        check_token();
                        if (trim($_REQUEST["banvalue"])) {
                            insert_query("mod_licensingbans", array("value" => trim($_REQUEST["banvalue"]), "notes" => trim($_REQUEST["bannote"])));
                        }
                        redir("module=licensing&action=bans");
                    }
                    if ($_REQUEST["delete"]) {
                        check_token();
                        delete_query("mod_licensingbans", array("id" => $_REQUEST["delete"]));
                        redir("module=licensing&action=bans");
                    }
                    $jscode = "function doDelete(id) {\n    if (confirm(\"Are you sure you want to delete this ban entry?\")) {\n        window.location='" . $modulelink . "&action=bans&delete='+id+'" . generate_token("link") . "';\n    }\n}\n";
                    echo "\n<h2>Ban Control</h2>\n\n<form method=\"post\" class=\"form-horizontal\" action=\"";
                    echo $modulelink;
                    echo "&action=bans\">\n    <div class=\"form-group\">\n        <label for=\"inputBanValue\" class=\"col-sm-2 control-label\">Domain/IP</label>\n        <div class=\"col-sm-10\">\n            <input type=\"text\" name=\"banvalue\" id=\"inputBanValue\" class=\"form-control\" size=\"40\" />\n        </div>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"inputBanNote\" class=\"col-sm-2 control-label\">Reason/Notes</label>\n        <div class=\"col-sm-10\">\n            <input type=\"text\" name=\"bannote\" id=\"inputBanNote\" class=\"form-control\" size=\"80\" />\n        </div>\n    </div>\n    <input type=\"hidden\" name=\"save\" value=\"true\" />\n\n    <div class=\"col-md-4 col-md-offset-4\">\n        <input class=\"btn btn-primary btn-block\" type=\"submit\" value=\"Add Ban\" />\n    </div>\n    <div class=\"clearfix\"></div>\n</form>\n\n<h2>Current Bans</h2>\n";
                    $aInt->sortableTableInit("nopagination");
                    $result = select_query("mod_licensingbans", "", "", "value", "ASC");
                    while ($data = mysql_fetch_array($result)) {
                        $id = $data["id"];
                        $value = $data["value"];
                        $notes = $data["notes"];
                        $tabledata[] = array($value, $notes, "<a href=\"#\" onClick=\"doDelete('" . $id . "');return false\"><img src=\"images/delete.gif\" width=\"16\" height=\"16\" border=\"0\" alt=\"" . $aInt->lang("global", "delete") . "\"></a>");
                    }
                    echo $aInt->sortableTable(array("Domain/IP", "Ban Reason/Notes", ""), $tabledata);
                } else {
                    if ($action == "log") {
                        echo "\n<h2>License Access Logs</h2>\n\n<form method=\"post\" class=\"form-horizontal\" action=\"";
                        echo $modulelink;
                        echo "&action=log\">\n    <div class=\"form-group\">\n        <label for=\"inputDomainLog\" class=\"col-sm-2 control-label\">Domain</label>\n        <div class=\"col-sm-10\">\n            <input type=\"text\" name=\"search_domainlog\" id=\"inputDomainLog\" class=\"form-control\" value=\"";
                        echo $_REQUEST["search_domainlog"];
                        echo "\" />\n        </div>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"inputIpLog\" class=\"col-sm-2 control-label\">IP</label>\n        <div class=\"col-sm-10\">\n            <input type=\"text\" name=\"search_iplog\" id=\"inputIpLog\" class=\"form-control\" value=\"";
                        echo $_REQUEST["search_iplog"];
                        echo "\" />\n        </div>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"inputDirectoryLog\" class=\"col-sm-2 control-label\">Dir</label>\n        <div class=\"col-sm-10\">\n            <input type=\"text\" name=\"search_dirlog\" id=\"inputDirectoryLog\" class=\"form-control\" value=\"";
                        echo $_REQUEST["search_dirlog"];
                        echo "\" />\n        </div>\n    </div>\n    <div class=\"form-group\">\n        <label for=\"inputMessage\" class=\"col-sm-2 control-label\">Message</label>\n        <div class=\"col-sm-10\">\n            <input type=\"text\" name=\"search_message\" id=\"inputMessage\" class=\"form-control\" value=\"";
                        echo $_REQUEST["search_message"];
                        echo "\" />\n        </div>\n    </div>\n    <div class=\"col-md-4 col-md-offset-4\">\n        <input class=\"btn btn-primary btn-block\" type=\"submit\" value=\"Search\" />\n    </div>\n    <div class=\"clearfix\"></div>\n</form>\n\n";
                        $where = array();
                        if ($_REQUEST["search_domainlog"]) {
                            $where["domain"] = array("sqltype" => "LIKE", "value" => trim($_REQUEST["search_domainlog"]));
                        }
                        if ($_REQUEST["search_iplog"]) {
                            $where["ip"] = array("sqltype" => "LIKE", "value" => trim($_REQUEST["search_iplog"]));
                        }
                        if ($_REQUEST["search_dirlog"]) {
                            $where["path"] = array("sqltype" => "LIKE", "value" => trim($_REQUEST["search_dirlog"]));
                        }
                        if ($_REQUEST["search_message"]) {
                            $where["message"] = array("sqltype" => "LIKE", "value" => trim($_REQUEST["search_message"]));
                        }
                        $result = select_query("mod_licensinglog", "", $where, "id", "DESC");
                        $numrows = mysql_num_rows($result);
                        $aInt->sortableTableInit("datetime", "ASC");
                        $result = select_query("mod_licensinglog", "", $where, "id", "DESC", $page * $limit . "," . $limit);
                        while ($data = mysql_fetch_array($result)) {
                            $domain = $data["domain"];
                            $ip = $data["ip"];
                            $path = $data["path"];
                            $message = $data["message"];
                            $datetime = $data["datetime"];
                            $datetime = fromMySQLDate($datetime, true);
                            $tabledata2[] = array($datetime, $domain, $ip, $path, $message);
                        }
                        echo $aInt->sortableTable(array("Date", "Domain", "IP", "Path", "Status Message"), $tabledata2);
                    }
                }
            }
        }
    }
}
function licensing_clientarea($vars)
{
    if (!$vars["clientverifytool"]) {
        return false;
    }
    $domain = trim($_POST["domain"]);
    $check = false;
    $results = array();
    if ($domain) {
        $check = true;
        $result = select_query("mod_licensing", "*", "validdomain LIKE '%" . db_escape_string($domain) . "%' OR validip LIKE '%" . db_escape_string($domain) . "%'");
        while ($data = mysql_fetch_array($result)) {
            $licenseid = $data["id"];
            if ($data["addon_id"]) {
                $productname = WHMCS\Service\Addon::with("productAddon")->find($data["addon_id"])->name;
            } else {
                $productname = WHMCS\Service\Service::with("product")->find($data["serviceid"])->name;
            }
            $status = $data["status"];
            $validdomains = explode(",", $data["validdomain"]);
            $validips = explode(",", $data["validip"]);
            if (in_array($domain, $validdomains) || in_array($domain, $validips)) {
                $results[] = array("productname" => $productname, "domain" => $validdomains[0], "ip" => $validips[0], "status" => $status);
            }
        }
    }
    return array("pagetitle" => "License Verification Tool", "breadcrumb" => array("index.php?m=licensing" => "License Verification Tool"), "templatefile" => "licenseverify", "vars" => array("domain" => $domain, "check" => $check, "results" => $results, "ADDONLANG" => $vars["_lang"]));
}

?>