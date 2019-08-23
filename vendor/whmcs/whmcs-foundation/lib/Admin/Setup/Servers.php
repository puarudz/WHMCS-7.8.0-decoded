<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Admin\Setup;

class Servers
{
    public function getAutoPopulateServers()
    {
        $modulesWithAutoConfigRoutine = array();
        $moduleInterface = new \WHMCS\Module\Server();
        foreach ($moduleInterface->getList() as $module) {
            $moduleInterface->load($module);
            if ($moduleInterface->functionExists("AutoPopulateServerConfig")) {
                $modulesWithAutoConfigRoutine[] = $module;
            }
        }
        return $modulesWithAutoConfigRoutine;
    }
    public function add($name, $type, $ipaddress, $assignedips, $hostname, $monthlycost, $noc, $statusaddress, $nameserver1, $nameserver1ip, $nameserver2, $nameserver2ip, $nameserver3, $nameserver3ip, $nameserver4, $nameserver4ip, $nameserver5, $nameserver5ip, $maxaccounts, $username, $password, $accesshash, $secure, $port, $restrictssoroles, $disabled = 0)
    {
        if (!$name) {
            throw new \WHMCS\Exception("A server name is required");
        }
        $result = select_query("tblservers", "id", array("type" => $type, "active" => "1"));
        $data = mysql_fetch_array($result);
        $active = $data["id"] ? "" : "1";
        $newid = insert_query("tblservers", array("name" => $name, "type" => $type, "ipaddress" => trim($ipaddress), "assignedips" => trim($assignedips), "hostname" => trim($hostname), "monthlycost" => trim($monthlycost), "noc" => $noc, "statusaddress" => trim($statusaddress), "nameserver1" => trim($nameserver1), "nameserver1ip" => trim($nameserver1ip), "nameserver2" => trim($nameserver2), "nameserver2ip" => trim($nameserver2ip), "nameserver3" => trim($nameserver3), "nameserver3ip" => trim($nameserver3ip), "nameserver4" => trim($nameserver4), "nameserver4ip" => trim($nameserver4ip), "nameserver5" => trim($nameserver5), "nameserver5ip" => trim($nameserver5ip), "maxaccounts" => trim($maxaccounts), "username" => trim($username), "password" => encrypt(trim($password)), "accesshash" => trim($accesshash), "secure" => $secure, "port" => $port, "active" => $active, "disabled" => $disabled));
        if (0 < count($restrictssoroles)) {
            foreach ($restrictssoroles as $roleId) {
                \WHMCS\Database\Capsule::table("tblserversssoperms")->insert(array("server_id" => $newid, "role_id" => $roleId));
            }
        }
        if ($type) {
            $moduleInterface = new \WHMCS\Module\Server();
            $moduleInterface->load($type);
            if ($moduleInterface->isApplicationLinkSupported()) {
                $appLink = \WHMCS\ApplicationLink\ApplicationLink::firstOrCreate(array("module_type" => $moduleInterface->getType(), "module_name" => $moduleInterface->getLoadedModule()));
                if (!$appLink->isEnabled) {
                    $appLink->isEnabled = true;
                    $appLink->save();
                }
            }
        }
        logAdminActivity("Server Created: '" . $name . "' - Server ID: " . $newid);
        run_hook("ServerAdd", array("serverid" => $newid));
        return $newid;
    }
    public function fetchAutoPopulateServerConfig($module, $hostname, $ipaddress, $username, $password, $accesshash, $secure, $port)
    {
        $moduleInterface = new \WHMCS\Module\Server();
        if (!$moduleInterface->load($module)) {
            throw new \WHMCS\Exception\Fatal("Invalid Server Module Type");
        }
        if ($moduleInterface->functionExists("TestConnection")) {
            $serverModel = new \WHMCS\Product\Server();
            $serverModel->ipAddress = $ipaddress;
            $serverModel->hostname = $hostname;
            $serverModel->username = $username;
            $serverModel->password = encrypt($password);
            $serverModel->accessHash = $accesshash;
            $serverModel->secure = $secure;
            $serverModel->port = $port;
            $params = $moduleInterface->getServerParams($serverModel);
            $connectionTestResult = $moduleInterface->call("TestConnection", $params);
            $errorMsg = "";
            $ips = $hostname = "";
            $assignedips = array();
            $nameservers = array();
            if (array_key_exists("success", $connectionTestResult) && $connectionTestResult["success"] == true) {
                $response = $moduleInterface->call("AutoPopulateServerConfig", $params);
                $verifySuccess = true;
                $modalBtnLabel = "Finish";
                $subaction = "create";
                $serverName = $response["name"];
                $serverHostname = $response["hostname"];
                $primaryIp = $response["primaryIp"];
                $assignedIps = $response["assignedips"];
                $nameservers = $response["nameservers"];
                $assignedIps = implode("\n", $response["assignedIps"]);
                return array("serverName" => $serverName, "serverHostname" => $serverHostname, "primaryIp" => $primaryIp, "assignedIps" => $assignedIps, "nameservers" => $nameservers);
            }
            $verifyError = array_key_exists("error", $connectionTestResult) ? $connectionTestResult["error"] : "An unknown error occurred";
            throw new \WHMCS\Exception($verifyError);
        }
        throw new \WHMCS\Exception\Fatal(\AdminLang::trans("configservers.testconnectionnotsupported"));
    }
    public function createApiToken($module, $serverId)
    {
        $moduleInterface = new \WHMCS\Module\Server();
        if ($moduleInterface->load($module) && $moduleInterface->functionExists("create_api_token")) {
            $params = $moduleInterface->getServerParams($serverId);
            $apiTokenResult = $moduleInterface->call("create_api_token", $params);
            if (array_key_exists("success", $apiTokenResult) && $apiTokenResult["success"] == true) {
                \WHMCS\Database\Capsule::table("tblservers")->where("id", "=", $serverId)->update(array("password" => encrypt(""), "accesshash" => $apiTokenResult["api_token"]));
            }
        }
    }
}

?>