<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

$pluginVersion = "1.11";
define("field_name", "User Name");
define("field_players", "Players");
define("field_location", "Location");
define("field_website", "Website");
define("field_hostname", "Host Name");
define("field_motd", "MOTD");
define("field_rconpw", "RCON Password");
define("field_serverpw", "Server Password");
function gamecp_MetaData()
{
    return array("DisplayName" => "GameCP", "APIVersion" => "1.0");
}
function gamecp_ConfigOptions()
{
    $configarray = array("GameCPX URL" => array("Type" => "text", "Size" => "80", "Description" => "The url to your panel - must include http://, ie: http://myclan.gamecp.com/"), "Passphrase" => array("Type" => "password", "Size" => "32", "Description" => "This must match the setting in GameCP Settings >  Billing > Passphrase"), "Game ID" => array("Type" => "text", "Size" => "4", "Description" => "This must match the Export/GameID Number in Manage Games for this game."), "Private Server" => array("Type" => "yesno", "Description" => "Yes"), "Disable Shell" => array("Type" => "yesno", "Description" => "Yes"), "Allocate IP By" => array("Type" => "dropdown", "Options" => "1,2", "Description" => "1=Auto, 2=Location Addon (a custom field called 'Location')"), "Enable Debugging" => array("Type" => "yesno", "Description" => "Yes"), "E-mail Server Info" => array("Type" => "dropdown", "Options" => "yes,no", "Description" => ""), "Start Server" => array("Type" => "dropdown", "Options" => "yes,no", "Description" => ""), "Mark IP Used" => array("Type" => "dropdown", "Options" => "no,yes", "Description" => ""));
    return $configarray;
}
function gamecp_CreateAccount($params)
{
    $serviceid = $params["serviceid"];
    $pid = $params["pid"];
    $producttype = $params["producttype"];
    $domain = $params["domain"];
    $username = $params["username"];
    $password = $params["password"];
    $clientsdetails = $params["clientsdetails"];
    $customfields = $params["customfields"];
    $configoptions = $params["configoptions"];
    $configoption1 = $params["configoption1"];
    $configoption2 = $params["configoption2"];
    $configoption3 = $params["configoption3"];
    $configoption4 = $params["configoption4"];
    $server = $params["server"];
    $serverid = $params["serverid"];
    $serverip = $params["serverip"];
    $serverusername = $params["serverusername"];
    $serverpassword = $params["serverpassword"];
    $serveraccesshash = $params["serveraccesshash"];
    $serversecure = $params["serversecure"];
    if (!$username) {
        $username = strtolower($customfields[field_name]);
    }
    $username = preg_replace("/[^a-z0123456789]/", "", $username);
    $dataToUpdate = array("username" => $username);
    $args = array("plugin_gamecp_GameCP_URL" => $params["configoption1"], "plugin_gamecp_Connector_Passphrase" => $params["configoption2"], "debugging" => "off");
    $unfio = array("action" => "userinfo", "function" => "userinfo", "customerid" => $clientsdetails["userid"]);
    $userinfo = curl2gcp($args, $unfio);
    preg_match_all("/USER: (?P<name>\\w+) ::/", $userinfo, $matches);
    if ($matches["name"][0] && $matches["name"][0] != $username) {
        $dataToUpdate["username"] = $matches["name"][0];
        $customfields[field_name] = $matches["name"][0];
    }
    preg_match_all("/PASS: (?P<pass>\\w+) ::/", $userinfo, $pwmatch);
    if ($pwmatch["pass"][0]) {
        $dataToUpdate["password"] = $pwmatch["pass"][0];
        $params["password"] = $pwmatch["pass"][0];
    }
    $urlvars = array("action" => "create", "function" => "createacct", "username" => $customfields[field_name], "password" => $params["password"], "customerid" => $clientsdetails["userid"], "packageid" => $params["serviceid"], "email_server" => $params["configoption8"], "start_server" => $params["configoption9"], "mark_ip_used" => $params["configoption10"], "emailaddr" => $clientsdetails["email"], "firstname" => $clientsdetails["firstname"], "lastname" => $clientsdetails["lastname"], "address" => $clientsdetails["address1"], "city" => $clientsdetails["city"], "state" => $clientsdetails["state"], "country" => $clientsdetails["country"], "zipcode" => $clientsdetails["postcode"], "phonenum" => $clientsdetails["phonenumber"], "game_id" => $params["configoption3"], "max_players" => $configoptions[field_players], "pub_priv" => $params["configoption4"], "login_path" => $params["configoption5"], "sv_location" => $customfields[field_location], "website" => $customfields[field_website], "hostname" => $customfields[field_hostname], "motd" => $customfields[field_motd], "rcon_password" => $customfields[field_rconpw], "priv_password" => $customfields[field_serverpw], "addons" => safe_serialize($configoptions));
    $args = array("plugin_gamecp_GameCP_URL" => $params["configoption1"], "plugin_gamecp_Connector_Passphrase" => $params["configoption2"], "debugging" => $params["configoption7"]);
    $r_result = curl2gcp($args, $urlvars);
    preg_match_all("/USER: (?P<name>\\w+) ::/", $r_result, $matches);
    if ($matches["name"][0] && $matches["name"][0] != $username) {
        $dataToUpdate["username"] = $matches["name"][0];
        $customfields[field_name] = $matches["name"][0];
    }
    if ($dataToUpdate) {
        $params["model"]->serviceProperties->save($dataToUpdate);
    }
    $result = checkStatus($r_result);
    if ($result == "completed") {
        $result = "success";
    } else {
        logModuleCall("gamecp", "create", $_REQUEST, $result);
        $result = "There was an error, please check Utilities, Logs,  Module Debug Log for more details.";
    }
    return $result;
}
function gamecp_TerminateAccount($params)
{
    if ($params["configoption3"] == "1000" || $params["configoption3"] == "1001" || $params["configoption3"] == "1002") {
        $action = "deletevoice";
    } else {
        $action = "delete";
    }
    $urlvars = array("action" => $action, "customerid" => $params["clientsdetails"]["userid"], "packageid" => $params["serviceid"]);
    $args = array("plugin_gamecp_GameCP_URL" => $params["configoption1"], "plugin_gamecp_Connector_Passphrase" => $params["configoption2"], "debugging" => $params["configoption7"]);
    $r_result = curl2gcp($args, $urlvars);
    $result = checkStatus($r_result);
    if ($result == "completed") {
        $result = "success";
    } else {
        logModuleCall("gamecp", "terminate", $_REQUEST, $result);
        $result = "There was an error, please check Utilities, Logs,  Module Debug Log for more details.";
    }
    return $result;
}
function gamecp_SuspendAccount($params)
{
    if ($params["configoption3"] == "1000" || $params["configoption3"] == "1001" || $params["configoption3"] == "1002") {
        $action = "suspendvoice";
    } else {
        $action = "suspendgame";
    }
    $urlvars = array("action" => $action, "customerid" => $params["clientsdetails"]["userid"], "packageid" => $params["serviceid"]);
    $args = array("plugin_gamecp_GameCP_URL" => $params["configoption1"], "plugin_gamecp_Connector_Passphrase" => $params["configoption2"], "debugging" => $params["configoption7"]);
    $r_result = curl2gcp($args, $urlvars);
    $result = checkStatus($r_result);
    if ($result == "completed") {
        $result = "success";
    } else {
        logModuleCall("gamecp", "suspend", $_REQUEST, $result);
        $result = "There was an error, please check Utilities, Logs,  Module Debug Log for more details.";
    }
    return $result;
}
function gamecp_UnsuspendAccount($params)
{
    if ($params["configoption3"] == "1000" || $params["configoption3"] == "1001" || $params["configoption3"] == "1002") {
        $action = "unsuspendvoice";
    } else {
        $action = "unsuspendgame";
    }
    $urlvars = array("action" => $action, "customerid" => $params["clientsdetails"]["userid"], "packageid" => $params["serviceid"]);
    $args = array("plugin_gamecp_GameCP_URL" => $params["configoption1"], "plugin_gamecp_Connector_Passphrase" => $params["configoption2"], "debugging" => $params["configoption7"]);
    $r_result = curl2gcp($args, $urlvars);
    $result = checkStatus($r_result);
    if ($result == "completed") {
        $result = "success";
    } else {
        logModuleCall("gamecp", "unsuspend", $_REQUEST, $result);
        $result = "There was an error, please check Utilities, Logs,  Module Debug Log for more details.";
    }
    return $result;
}
function gamecp_ChangePackage($params)
{
    if ($params["configoption3"] == "1000" || $params["configoption3"] == "1001" || $params["configoption3"] == "1002") {
        return false;
    }
    $urlvars = array("action" => "changeplayers", "packageid" => $params["serviceid"], "max_players" => $params["configoptions"]["Players"], "addons" => safe_serialize($params["configoptions"]));
    $args = array("plugin_gamecp_GameCP_URL" => $params["configoption1"], "plugin_gamecp_Connector_Passphrase" => $params["configoption2"], "debugging" => $params["configoption7"]);
    $r_result = curl2gcp($args, $urlvars);
    $result = checkStatus($r_result);
    if ($result == "completed") {
        $result = "success";
    } else {
        logModuleCall("gamecp", "changepackage", $_REQUEST, $result);
        $result = "There was an error, please check Utilities, Logs,  Module Debug Log for more details.";
    }
    return $result;
}
function gamecp_ClientArea($params)
{
    global $_LANG;
    $form = sprintf("<form action=\"%s\" method=\"post\" target=\"_blank\">" . "<input type=\"hidden\" name=\"user\" value=\"%s\" />" . "<input type=\"hidden\" name=\"pass\" value=\"%s\" />" . "<input type=\"submit\" name=\"sublogin\" value=\"%s\" />" . "</form>", WHMCS\Input\Sanitize::encode($params["configoption1"]), WHMCS\Input\Sanitize::encode($params["username"]), WHMCS\Input\Sanitize::encode($params["password"]), $_LANG["gamecplogin"]);
    return $form;
}
function gamecp_AdminLink($params)
{
    $form = sprintf("<form action=\"%s\" method=\"post\" target=\"_blank\">" . "<input type=\"hidden\" name=\"user\" value=\"%s\" />" . "<input type=\"hidden\" name=\"pass\" value=\"%s\" />" . "<input type=\"submit\" name=\"sublogin\" value=\"%s\" />" . "</form>", WHMCS\Input\Sanitize::encode($params["configoption1"]), WHMCS\Input\Sanitize::encode($params["serverusername"]), WHMCS\Input\Sanitize::encode($params["serverpassword"]), "Login to GameCP");
    return $form;
}
function gamecp_LoginLink($params)
{
    $form = sprintf("<a href=\"%s?username=%s&password=%s\" target=\"_blank\" class=\"moduleloginlink\">%s</a>", WHMCS\Input\Sanitize::encode($params["configoption1"]), WHMCS\Input\Sanitize::encode($params["username"]), WHMCS\Input\Sanitize::encode($params["password"]), "login to control panel");
    return $form;
}
function gamecp_AdminCustomButtonArray()
{
    $buttonarray = array("Start" => "start", "Stop" => "stop");
    return $buttonarray;
}
function gamecp_start($params)
{
    if ($params["configoption3"] == "1000" || $params["configoption3"] == "1001" || $params["configoption3"] == "1002") {
        return false;
    }
    $urlvars = array("action" => "start", "customerid" => $params["clientsdetails"]["userid"], "packageid" => $params["serviceid"]);
    $args = array("plugin_gamecp_GameCP_URL" => $params["configoption1"], "plugin_gamecp_Connector_Passphrase" => $params["configoption2"], "debugging" => $params["configoption7"]);
    $r_result = curl2gcp($args, $urlvars);
    $result = checkStatus($r_result);
    if ($result == "completed") {
        $result = "success";
    } else {
        logModuleCall("gamecp", "start", $_REQUEST, $result);
        $result = "There was an error, please check Utilities, Logs,  Module Debug Log for more details.";
    }
    return $result;
}
function gamecp_stop($params)
{
    if ($params["configoption3"] == "1000" || $params["configoption3"] == "1001" || $params["configoption3"] == "1002") {
        return false;
    }
    $urlvars = array("action" => "stop", "customerid" => $params["clientsdetails"]["userid"], "packageid" => $params["serviceid"]);
    $args = array("plugin_gamecp_GameCP_URL" => $params["configoption1"], "plugin_gamecp_Connector_Passphrase" => $params["configoption2"], "debugging" => $params["configoption7"]);
    $r_result = curl2gcp($args, $urlvars);
    $result = checkStatus($r_result);
    if ($result == "completed") {
        $result = "success";
    } else {
        logModuleCall("gamecp", "stop", $_REQUEST, $result);
        $result = "There was an error, please check Utilities, Logs,  Module Debug Log for more details.";
    }
    return $result;
}
function curl2gcp($args, $values)
{
    if (!$args["plugin_gamecp_GameCP_URL"]) {
        return "No GameCP URL defined. Assign one in your product. Please contact GameCP for support.";
    }
    if (!$args["plugin_gamecp_Connector_Passphrase"]) {
        return "No GameCP passphrase defined. Assign one in your product. Please contact GameCP for support.";
    }
    if ($args["debugging"] == "on") {
        $post = "passphrase=" . urlencode($args["plugin_gamecp_Connector_Passphrase"]) . "&debugging=true&connector=ce";
    } else {
        $post = "passphrase=" . urlencode($args["plugin_gamecp_Connector_Passphrase"]) . "&connector=ce";
    }
    if (is_array($values)) {
        foreach ($values as $key => $value) {
            $post .= "&" . $key . "=" . urlencode(str_replace("'", "", $value));
        }
    }
    $url = rtrim($args["plugin_gamecp_GameCP_URL"], "/") . "/billing/mb/index.php";
    $result = send2curl($url, $post);
    if ($args["debugging"] == "on") {
        logModuleCall("gamecp", "debug", $url . "?" . $post, strip_tags($result));
    }
    return $result;
}
function checkStatus($r_result)
{
    if (!$r_result) {
        return "GameCP did not reply to your command.<br>Please check your package configuration.<br> Please contact GameCP for support.";
    }
    if (preg_match("/Command Execution Result: Ok/i", strip_tags($r_result))) {
        return "completed";
    }
    if (preg_match("/Unable to determin IP address/i", strip_tags($r_result))) {
        return "GameCP was unable to select an ip address for this server.<br>Please check your machines slot and user quota.<br> Please contact GameCP for support.";
    }
    if (preg_match("/Passed Security Checks: Failed/i", strip_tags($r_result))) {
        return "Your GameCP passphrase is incorrect.<br>Check your settings.<br> Please contact GameCP for support.";
    }
    return "There was an unknown problem with the API, check the debugging details below:\n" . strip_tags($r_result);
}
function send2curl($url, $data)
{
    if (!extension_loaded("curl")) {
        return "Curl for php is not installed. Review google for in formation on php-curl, or curl for php.<br>Contact GameCP for support";
    }
    $ch = curl_init($url . "?" . $data);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

?>