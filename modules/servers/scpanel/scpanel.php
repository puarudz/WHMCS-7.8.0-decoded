<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function scpanel_MetaData()
{
    return array("DisplayName" => "SCPanel", "APIVersion" => "1.0", "DefaultNonSSLPort" => "2086", "DefaultSSLPort" => "2087");
}
function scpanel_ConfigOptions()
{
    $configarray = array("Super User" => array("Type" => "text", "Size" => "25"), "Super User Password" => array("Type" => "text", "Size" => "25"), "OS" => array("Type" => "dropdown", "Options" => "linux,freebsd4,freebsd5,solaris,macosx"), "Max Users" => array("Type" => "text", "Size" => "5"), "Next Port" => array("Type" => "text", "Size" => "5"), "Max Traffic" => array("Type" => "text", "Size" => "5", "Description" => "MB (enter unmetered for unlimited)"), "Traffic Abuse" => array("Type" => "yesno", "Description" => "If ticked, stream will auto-suspend on traffic abuse"), "Max Bit Rate" => array("Type" => "dropdown", "Options" => "8,16,20,24,32,48,56,64,80,96,112,128,160,192,224,256,320,384,512,768,1024"), "Bit Rate Abuse" => array("Type" => "yesno", "Description" => "If ticked, stream will auto-suspend on bitrate abuse"), "On Demand" => array("Type" => "yesno", "Description" => "If ticked, the customer will be able to upload and use on-demand content"), "Proxy" => array("Type" => "yesno", "Description" => "If ticked, the customer will be able to use SCPanel Proxy to stream through port 80"), "WHM Package Name" => array("Type" => "text", "Size" => "25"), "Intro Backup Max Size" => array("Type" => "text", "Size" => "5", "Description" => "MB (leave blank for default)"), "On Demand Max Size" => array("Type" => "text", "Size" => "5", "Description" => "MB (leave blank for default)"), "Use Port Prefix Domain" => array("Type" => "text", "Size" => "30", "Description" => "To use port.domain.com for SCPanel accounts, enter the .domain.com ending here"));
    return $configarray;
}
function scpanel_AdminLink($params)
{
    $form = sprintf("<form action=\"%s://%s:%s/login/\" method=\"post\" target=\"_blank\">" . "<input type=\"hidden\" name=\"user\" value=\"%s\" />" . "<input type=\"hidden\" name=\"pass\" value=\"%s\" />" . "<input type=\"submit\" value=\"%s\" />" . "</form>", $params["serverhttpprefix"], WHMCS\Input\Sanitize::encode($params["serverip"]), $params["serverport"], WHMCS\Input\Sanitize::encode($params["serverusername"]), WHMCS\Input\Sanitize::encode($params["serverpassword"]), "WHM");
    return $form;
}
function scpanel_CreateAccount($params)
{
    $sAuth = base64_encode($params["serverusername"] . ":" . $params["serverpassword"]);
    if ($params["configoption15"]) {
        $params["domain"] = $params["configoption5"] . $params["configoption15"];
        $params["username"] = "sc" . $params["domain"];
        $params["username"] = str_replace(".", "", $params["username"]);
        $params["username"] = str_replace("-", "", $params["username"]);
        $params["username"] = substr($params["username"], 0, 8);
    }
    $params["model"]->serviceProperties->save(array("domain" => $params["domain"], "username" => $params["username"]));
    $cpanelpassword = WHMCS\Module\Server::generateRandomPassword();
    $sHTTP = "GET /scripts/wwwacct?domain=" . urlencode($params["domain"]) . "&username=" . urlencode($params["username"]) . "&password=" . urlencode($cpanelpassword) . "&plan=" . urlencode($params["configoption12"]) . "&x=\r\n HTTP/1.0\r\nAuthorization: Basic " . $sAuth . "\r\n";
    $ch = curl_init($params["serverhttpprefix"] . "://" . $params["serverip"] . ":" . $params["serverport"] . "/xml-api/createacct?username=" . urlencode($params["username"]) . "&plan=" . urlencode($params["configoption12"]) . "&password=" . urlencode($cpanelpassword) . "&domain=" . urlencode($params["domain"]));
    $header = array("Authorization: Basic " . $sAuth);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    $output = curl_exec($ch);
    curl_close($ch);
    $output = simplexml_load_string($output);
    if ($output->result->status == 1) {
        $result = "success";
    } else {
        $result = $output->result->statusmsg;
    }
    if ($result == "success") {
        if ($params["configoption7"] == "on") {
            $params["configoption7"] = "yes";
        } else {
            $params["configoption7"] = "no";
        }
        if ($params["configoption9"] == "on") {
            $params["configoption9"] = "yes";
        } else {
            $params["configoption9"] = "no";
        }
        if ($params["configoption10"] == "on") {
            $params["configoption10"] = "yes";
        } else {
            $params["configoption10"] = "no";
        }
        if ($params["configoption11"] == "on") {
            $params["configoption11"] = "yes";
        } else {
            $params["configoption11"] = "no";
        }
        if ($params["configoption13"]) {
            $params["configoption13"] .= "M";
        }
        if ($params["configoption14"]) {
            $params["configoption14"] .= "M";
        }
        $access_url = $params["serverip"] . "/~" . $params["username"];
        $url = "http://" . $access_url . "/install.php";
        $data = file_get_contents($url);
        logModuleCall("scpanel", "", $url, $data);
        $url = "http://" . $access_url . "/scp/index.php";
        $data = file_get_contents($url);
        logModuleCall("scpanel", "", $url, $data);
        $url = "http://" . $access_url . "/scp/install/api/1.php?user=" . $params["configoption1"] . "&pass=" . $params["configoption2"] . "&os=" . $params["configoption3"];
        $data = file_get_contents($url);
        logModuleCall("scpanel", "", $url, $data);
        if ($data != "OK" && $result == "success") {
            $result = titleCase($data);
            return $result;
        }
        $url = "http://" . $access_url . "/scp/install/api/2.php?user=" . $params["configoption1"] . "&pass=" . $params["configoption2"] . "&os=" . $params["configoption3"] . "&maxuser=" . $params["configoption4"] . "&password=" . $params["password"] . "&portbase=" . $params["configoption5"] . "&destip=" . $params["serverip"];
        $data = file_get_contents($url);
        logModuleCall("scpanel", "", $url, $data);
        if ($data != "OK" && $result == "success") {
            $result = titleCase($data);
            return $result;
        }
        $url = "http://" . $access_url . "/scp/install/api/3.php?user=" . $params["configoption1"] . "&pass=" . $params["configoption2"] . "&server_host=" . $params["serverip"] . "&server_port=" . $params["configoption5"];
        $data = file_get_contents($url);
        logModuleCall("scpanel", "", $url, $data);
        if ($data != "OK" && $result == "success") {
            $result = titleCase($data);
            return $result;
        }
        $url = "http://" . $access_url . "/scp/install/api/4.php?user=" . $params["configoption1"] . "&pass=" . $params["configoption2"] . "&maxtraffic=" . $params["configoption6"] . "&trafficabuse=" . $params["configoption7"] . "&maxbitrate=" . $params["configoption8"] . "&bitrateabuse=" . $params["configoption9"] . "&intro_backup_max_size=" . $params["configoption13"] . "&ondemand_max_size=" . $params["configoption14"] . "&ondemand=" . $params["configoption10"] . "&proxy=" . $params["configoption11"];
        $data = file_get_contents($url);
        logModuleCall("scpanel", "", $url, $data);
        if ($data != "OK" && $result == "success") {
            $result = titleCase($data);
            return $result;
        }
        $url = "http://" . $access_url . "/scp/install/api/5.php?user=" . $params["configoption1"] . "&pass=" . $params["configoption2"] . "&username=" . $params["username"] . "&password=" . $params["password"] . "&name=" . urlencode($params["clientsdetails"]["firstname"] . " " . $params["clientsdetails"]["lastname"]) . "&user_mail=" . urlencode($params["clientsdetails"]["email"]) . "&allow_admin_mail=ON&allow_user_mail=OFF";
        $data = file_get_contents($url);
        logModuleCall("scpanel", "", $url, $data);
        if ($data != "OK" && $result == "success") {
            $result = titleCase($data);
            return $result;
        }
        $url = "http://" . $access_url . "/scp/install/api/6.php?user=" . $params["configoption1"] . "&pass=" . $params["configoption2"] . "&action=start";
        $data = file_get_contents($url);
        logModuleCall("scpanel", "", $url, $data);
        if ($data != "OK" && $result == "success") {
            $result = titleCase($data);
        }
        $url = "http://" . $access_url . "/scp/install/api/7.php?user=" . $params["configoption1"] . "&pass=" . $params["configoption2"] . "&action=cleanup";
        $data = file_get_contents($url);
        logModuleCall("scpanel", "", $url, $data);
        if ($data != "OK" && $result == "success") {
            $result = titleCase($data);
        }
        update_query("tblproducts", array("configoption5" => "+=2"), array("id" => $params["packageid"]));
    }
    return $result;
}
function scpanel_TerminateAccount($params)
{
    $sAuth = base64_encode($params["serverusername"] . ":" . $params["serverpassword"]);
    $ch = curl_init($params["serverhttpprefix"] . "://" . $params["serverip"] . ":" . $params["serverport"] . "/xml-api/removeacct?user=" . urlencode($params["username"]));
    $header = array("Authorization: Basic " . $sAuth);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    $output = curl_exec($ch);
    curl_close($ch);
    $output = simplexml_load_string($output);
    if ($output->result->status == 1) {
        $result = "success";
    } else {
        $result = $output->result->statusmsg;
    }
    return $result;
}
function scpanel_SuspendAccount($params)
{
    $url = "http://" . $params["domain"] . "/scp/admin/api/action.php?user=" . $params["configoption1"] . "&pass=" . $params["configoption2"] . "&action=suspend";
    $data = file_get_contents($url);
    logModuleCall("scpanel", "", $url, $data);
    $result = "success";
    return $result;
}
function scpanel_UnsuspendAccount($params)
{
    $url = "http://" . $params["domain"] . "/scp/admin/api/action.php?user=" . $params["configoption1"] . "&pass=" . $params["configoption2"] . "&action=unsuspend";
    $data = file_get_contents($url);
    logModuleCall("scpanel", "", $url, $data);
    $result = "success";
    return $result;
}
function scpanel_AdminCustomButtonArray()
{
    $buttonarray = array("Start" => "start", "Stop" => "stop");
    return $buttonarray;
}
function scpanel_start($params)
{
    $url = "http://" . $params["domain"] . "/scp/admin/api/action.php?user=" . $params["configoption1"] . "&pass=" . $params["configoption2"] . "&action=start";
    $data = file_get_contents($url);
    logModuleCall("scpanel", "", $url, $data);
    $result = "success";
    return $result;
}
function scpanel_stop($params)
{
    $url = "http://" . $params["domain"] . "/scp/admin/api/action.php?user=" . $params["configoption1"] . "&pass=" . $params["configoption2"] . "&action=stop";
    $data = file_get_contents($url);
    logModuleCall("scpanel", "", $url, $data);
    $result = "success";
    return $result;
}

?>