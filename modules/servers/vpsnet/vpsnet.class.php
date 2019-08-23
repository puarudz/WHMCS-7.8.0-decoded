<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

class VPSNET
{
    protected $_apiUrl = "https://api.vps.net";
    private $_apiVersion = "api10json";
    protected $_apiUserAgent = "VPSNET_API_10_JSON/PHP";
    private $_session_cookie = NULL;
    private $_auth_name = "";
    private $_auth_api_key = "";
    private $_proxy = "";
    private $ch = NULL;
    public $last_errors = NULL;
    private static $instance = NULL;
    private function __construct()
    {
    }
    public function __destruct()
    {
        if (!is_null($this->ch)) {
            curl_close($this->ch);
        }
    }
    public function isAuthenticationInfoSet()
    {
        return 0 < strlen($this->_auth_name) && 0 < strlen($this->_auth_api_key);
    }
    public static function getInstance($username = "", $_auth_api_key = "", $proxy = "")
    {
        if (!isset($instance)) {
            $c = "VPSNET";
            self::$instance = new $c();
            self::$instance->_auth_name = $username;
            self::$instance->_auth_api_key = $_auth_api_key;
            if (0 < strlen($proxy)) {
                self::$instance->_proxy = $proxy;
            }
            if (strlen($username) == 0 || strlen($_auth_api_key) == 0) {
                throw new Exception("A Username and/or API Key has not yet been setup in Setup > Servers.");
            }
            self::$instance->_initCurl();
        }
        return self::$instance;
    }
    public function __clone()
    {
        trigger_error("Clone is not permitted. This class is a singleton.", 256);
    }
    private function _initCurl()
    {
        $this->ch = curl_init();
        if (0 < strlen($this->_proxy)) {
            curl_setopt($this->ch, CURLOPT_PROXY, $this->_proxy);
        }
        curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Accept: application/json"));
        curl_setopt($this->ch, CURLOPT_USERAGENT, $this->_apiUserAgent);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_USERPWD, $this->_auth_name . ":" . $this->_auth_api_key);
        curl_setopt($this->ch, CURLOPT_COOKIEFILE, "/tmp/.vpsnet." . $this->_auth_name . ".cookie");
        curl_setopt($this->ch, CURLOPT_COOKIEJAR, "/tmp/.vpsnet." . $this->_auth_name . ".cookie");
    }
    public function setAPIResource($resource, $append_api_version = true, $queryString = "")
    {
        if ($append_api_version) {
            curl_setopt($this->ch, CURLOPT_URL, sprintf("%1\$s/%2\$s.%3\$s?%4\$s", $this->_apiUrl, $resource, $this->_apiVersion, $queryString));
        } else {
            curl_setopt($this->ch, CURLOPT_URL, sprintf("%1\$s/%2\$s?%3\$s", $this->_apiUrl, $resource, $queryString));
        }
    }
    public function sendGETRequest()
    {
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($this->ch, CURLOPT_HTTPGET, true);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array("Content-Length: 0", "Content-Type: application/json", "Accept: application/json"));
        $rtn = $this->sendRequest();
        logModuleCall("vpsnet", "get", $this, $rtn);
        return $rtn;
    }
    public function sendPOSTRequest($data = NULL, $encodeasjson = true)
    {
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Accept: application/json"));
        if (!is_null($data)) {
            if ($encodeasjson) {
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($data));
            } else {
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);
                curl_setopt($this->ch, CURLOPT_HTTPHEADER, array());
            }
        }
        $rtn = $this->sendRequest();
        logModuleCall("vpsnet", "post", $this, $rtn);
        return $rtn;
    }
    public function sendPUTRequest($data)
    {
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "PUT");
        $json_data = json_encode($data);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array("Content-Length: " . strlen($json_data), "Content-Type: application/json", "Accept: application/json"));
        $rtn = $this->sendRequest();
        logModuleCall("vpsnet", "put", $this, $rtn);
        return $rtn;
    }
    public function sendDELETERequest()
    {
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Accept: application/json"));
        $rtn = $this->sendRequest();
        logModuleCall("vpsnet", "delete", $this, $rtn);
        return $rtn;
    }
    protected function sendRequest($data = NULL)
    {
        $rtn = array();
        $rtn["response_body"] = curl_exec($this->ch);
        $rtn["info"] = curl_getinfo($this->ch);
        if ($rtn["info"]["content_type"] == "application/json; charset=utf-8") {
            if ($rtn["info"]["http_code"] == 200) {
                $rtn["response"] = json_decode($rtn["response_body"]);
                $this->last_errors = NULL;
            } else {
                if ($rtn["info"]["http_code"] == 422) {
                    $rtn["errors"] = json_decode($rtn["response_body"]);
                    $this->last_errors = $rtn["errors"];
                } else {
                    $rtn["errors"] = json_decode($rtn["response_body"]);
                    $this->last_errors = $rtn["errors"];
                }
            }
        }
        return $rtn;
    }
    public function getNodes($consumer_id = 0)
    {
        if (0 < $consumer_id) {
            $this->setAPIResource("nodes", true, "consumer_id=" . $consumer_id);
        } else {
            $this->setAPIResource("nodes");
        }
        $result = $this->sendGETRequest();
        $return = array();
        if ($result["info"]["http_code"] == 422) {
        } else {
            if ($result["response"]) {
                $response = $result["response"];
                for ($x = 0; $x < count($response); $x++) {
                    $return[$x] = $this->_castObjectToClass("Node", $response[$x]->slice);
                }
            }
        }
        return $return;
    }
    public function getIPAddresses($consumer_id = 0)
    {
        if (0 < $consumer_id) {
            $this->setAPIResource("ip_address_assignments", true, "consumer_id=" . $consumer_id);
        } else {
            $this->setAPIResource("ip_address_assignments");
        }
        $result = $this->sendGETRequest();
        $return = array();
        if ($result["info"]["http_code"] == 422) {
        } else {
            if ($result["response"]) {
                $response = $result["response"];
                for ($x = 0; $x < count($response); $x++) {
                    $return[$x] = $this->_castObjectToClass("IPAddress", $response[$x]->ip_address);
                }
            }
        }
        return $return;
    }
    public function getVirtualMachines($consumer_id = 0)
    {
        if (0 < $consumer_id) {
            $this->setAPIResource("virtual_machines", true, "consumer_id=" . $consumer_id);
        } else {
            $this->setAPIResource("virtual_machines");
        }
        $result = $this->sendGETRequest();
        $return = array();
        if ($result["info"]["http_code"] == 422) {
        } else {
            if ($result["response"]) {
                $response = $result["response"];
                for ($x = 0; $x < count($response); $x++) {
                    $return[$x] = $this->_castObjectToClass("VirtualMachine", $response[$x]->virtual_machine);
                }
            }
        }
        return $return;
    }
    public function getAvailableCloudsAndTemplates()
    {
        $this->setAPIResource("available_clouds");
        $result = $this->sendGETRequest();
        $return = NULL;
        if ($result["info"]["http_code"] == 422) {
        } else {
            if ($result["response"]) {
                $return = $result["response"];
            }
        }
        return $return;
    }
    public function addInternalIPAddresses($quantity, $consumer_id = 0)
    {
        if ($quantity < 1) {
            trigger_error("To call VPSNET::addInternalIPAddress() you must provide a quantity greater than 0", 256);
            return false;
        }
        $this->setAPIResource("ip_address_assignments");
        $json_request["ip_address_assignment"]->quantity = $quantity;
        $json_request["ip_address_assignment"]->type = "internal";
        if (0 < $consumer_id) {
            $json_request["ip_address_assignment"]->consumer_id = $consumer_id;
        }
        $result = $this->sendPOSTRequest($json_request);
        $return = NULL;
        if ($result["response"]) {
            $return = $result["response"];
        }
        return $return;
    }
    public function addExternalIPAddresses($quantity, $cloud_id, $consumer_id = 0)
    {
        if ($quantity < 1 || $cloud_id < 1) {
            trigger_error("To call VPSNET::addExternalIPAddresses() you must provide a quantity greater than 0 and a cluster_id", 256);
            return false;
        }
        $this->setAPIResource("ip_address_assignments");
        $json_request["ip_address_assignment"]->quantity = $quantity;
        $json_request["ip_address_assignment"]->cloud_id = $cloud_id;
        $json_request["ip_address_assignment"]->type = "external";
        if (0 < $consumer_id) {
            $json_request["ip_address_assignment"]->consumer_id = $consumer_id;
        }
        $result = $this->sendPOSTRequest($json_request);
        $return = NULL;
        if ($result["response"]) {
            $return = $result["response"];
        }
        return $return;
    }
    public function createVirtualMachine($virtualmachine)
    {
        $this->setAPIResource("virtual_machines");
        $requestdata["label"] = $virtualmachine->label;
        $requestdata["fqdn"] = $virtualmachine->hostname;
        $requestdata["slices_required"] = $virtualmachine->slices_required;
        $requestdata["backups_enabled"] = (int) $virtualmachine->backups_enabled;
        $requestdata["rsync_backups_enabled"] = (int) $virtualmachine->rsync_backups_enabled;
        $requestdata["r1_soft_backups_enabled"] = (int) $virtualmachine->r1_soft_backups_enabled;
        $requestdata["system_template_id"] = $virtualmachine->system_template_id;
        $requestdata["cloud_id"] = $virtualmachine->cloud_id;
        $requestdata["consumer_id"] = $virtualmachine->consumer_id;
        $json_request["virtual_machine"] = $requestdata;
        $result = $this->sendPOSTRequest($json_request);
        $return = NULL;
        if ($result["response"]) {
            $return = $this->_castObjectToClass("VirtualMachine", $result["response"]->virtual_machine);
        } else {
            $return = $result;
        }
        return $return;
    }
    public function addNodes($quantity, $consumer_id = 0)
    {
        $this->setAPIResource("nodes");
        $json_request["quantity"] = $quantity;
        if (0 < $consumer_id) {
            $json_request["consumer_id"] = $consumer_id;
        }
        $result = $this->sendPOSTRequest($json_request);
        return $result["info"]["http_code"] == 200;
    }
    public function _castObjectToClass($classname, $object)
    {
        return unserialize(preg_replace("/^O:\\d+:\"[^\"]++\"/", "O:" . strlen($classname) . ":\"" . $classname . "\"", serialize($object)));
    }
}
class Node
{
    public $virtual_machine_id = 0;
    public $id = 0;
    public $consumer_id = 0;
    public $deleted = 0;
    public function __construct($id = 0, $virtual_machine_id = 0)
    {
        $this->id = $id;
        $this->virtual_machine_id = $virtual_machine_id;
    }
    public function remove()
    {
        $api = VPSNET::getInstance();
        if ($this->id < 1) {
            trigger_error("To call Node::remove() you must set its id", 256);
            return false;
        }
        if (0 < $this->virtual_machine_id) {
            trigger_error("You cannot call Node::remove() with a node assigned to a virtual machine. Instead use VirtualMachine::update()", 256);
            return false;
        }
        $api->setAPIResource("nodes/" . $this->id);
        $result = $api->sendDELETERequest();
        $this->deleted = $result["info"]["http_code"] == 200;
        return $this->deleted;
    }
}
class IPAddress
{
    public $id = 0;
    public $netmask = "";
    public $network = "";
    public $cloud_id = 0;
    public $ip_address = "";
    public $consumer_id = 0;
    public $deleted = false;
    private function __construct($id)
    {
        $this->id = $id;
    }
    public function isInternal()
    {
        return $cloud_id == 0;
    }
    public function isExternal()
    {
        return 0 < $cloud_id;
    }
    public function remove()
    {
        $api = VPSNET::getInstance();
        if ($this->id < 1) {
            trigger_error("To call IPAddress::remove() you must set id", 256);
            return false;
        }
        $api->setAPIResource("ip_address_assignments/" . $this->id);
        $result = $api->sendDELETERequest();
        $this->deleted = $result["info"]["http_code"] == 200;
        return $this->deleted;
    }
}
class Backup
{
    public $virtual_machine_id = 0;
    public $id = 0;
    public $label = "";
    public $auto_backup_type = NULL;
    public $deleted = false;
    public function __construct($id = 0, $virtual_machine_id = 0)
    {
        $this->id = $id;
        $this->virtual_machine_id = $virtual_machine_id;
    }
    public function restore()
    {
        $api = VPSNET::getInstance();
        if ($this->id < 1 || $this->virtual_machine_id < 1) {
            trigger_error("To call Backup::restore() you must set id and virtual_machine_id", 256);
            return false;
        }
        $api->setAPIResource("virtual_machines/" . $this->virtual_machine_id . "/backups/" . $this->id . "/restore");
        $result = $api->sendPOSTRequest();
        return $result["info"]["http_code"] == 200;
    }
    public function remove()
    {
        $api = VPSNET::getInstance();
        if ($this->id < 1 || $this->virtual_machine_id < 1) {
            trigger_error("To call Backup::remove() you must set id and virtual_machine_id", 256);
            return false;
        }
        $api->setAPIResource("virtual_machines/" . $this->virtual_machine_id . "/backups/" . $this->id);
        $result = $api->sendDELETERequest();
        $this->deleted = $result["info"]["http_code"] == 200;
        return $this->deleted;
    }
}
class UpgradeSchedule
{
    public $id = 0;
    public $label = "";
    public $extra_slices = 0;
    public $temporary = false;
    public $run_at = NULL;
    public $days = NULL;
    public function __construct($label, $extra_slices, $run_at, $days = 0)
    {
        $this->temporary = 0 < $days;
        $this->label = $label;
        $this->extra_slices = $extra_slices;
        $this->run_at = date_format("c", $run_at);
        if (0 < $days) {
            $this->days = $days;
        }
    }
}
class VirtualMachine
{
    public $label = "";
    public $hostname = "";
    public $domain_name = "";
    public $slices_count = 0;
    public $slices_required = 0;
    public $backups_enabled = 0;
    public $rsync_backups_enabled = 0;
    public $r1_soft_backups_enabled = 0;
    public $system_template_id = 0;
    public $cloud_id = 0;
    public $id = NULL;
    public $consumer_id = 0;
    public $created_at = NULL;
    public $updated_at = NULL;
    public $password = "";
    public $backups = array();
    public $upgrade_schedules = array();
    public $deleted = false;
    public function __construct($label = "", $hostname = "", $slices_required = "", $backups_enabled = "", $cloud_id = "", $system_template_id = "", $consumer_id = 0)
    {
        $this->label = $label;
        $this->hostname = $hostname;
        $this->slices_required = $slices_required;
        $this->backups_enabled = $backups_enabled;
        $this->cloud_id = $cloud_id;
        $this->system_template_id = $system_template_id;
        $this->consumer_id = $consumer_id;
    }
    private function _doAction($action)
    {
        $api = VPSNET::getInstance();
        $api->setAPIResource("virtual_machines/" . $this->id . "/" . $action);
        $result = $api->sendPOSTRequest();
        if ($result["info"]["http_code"] == 422) {
        } else {
            if ($result["response"]) {
                foreach ($result["response"]->virtual_machine as $key => $value) {
                    $this->{$key} = $value;
                }
            }
        }
        $resultclone = array();
        foreach ($result as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $key1 => $value1) {
                    if (is_array($value1)) {
                        foreach ($value1 as $key2 => $value2) {
                            $resultclone[$key][$key1][$key2] = strip_tags($value2);
                        }
                    } else {
                        $resultclone[$key][$key1] = strip_tags($value1);
                    }
                }
            } else {
                $resultclone[$key] = strip_tags($value);
            }
        }
        $this->rawresponse = $resultclone;
        return $this;
    }
    public function powerOn()
    {
        return $this->_doAction("power_on");
    }
    public function powerOff()
    {
        return $this->_doAction("power_off");
    }
    public function shutdown()
    {
        return $this->_doAction("shutdown");
    }
    public function reboot()
    {
        return $this->_doAction("reboot");
    }
    public function createBackup($label)
    {
        if (!is_string($label) || strlen($label) < 0) {
            trigger_error("To call VirtualMachine::createBackup() you must specify a label", 256);
            return false;
        }
        $api = VPSNET::getInstance();
        $api->setAPIResource("virtual_machines/" . $this->id . "/backups");
        $json_request["backup"]->label = $label;
        $result = $api->sendPOSTRequest($json_request);
        $return = NULL;
        if ($result["info"]["http_code"] == 422) {
        } else {
            $this->backups[] = $api->_castObjectToClass("Backup", $result["response"]);
        }
        return $result["response"];
    }
    public function createTemporaryUpgradeSchedule($label, $extra_slices, $run_at, $days)
    {
        $bInputErrors = false;
        if (!is_string($label) || strlen($label) < 0) {
            trigger_error("To call VirtualMachine::createTemporaryUpgradeSchedule() you must specify a label", 256);
            $bInputErrors = true;
        }
        if (!is_int($extra_slices)) {
            trigger_error("To call VirtualMachine::createTemporaryUpgradeSchedule() you must specify extra_slices as a number", 256);
            $bInputErrors = true;
        }
        if (!is_int($days) || $days < 1) {
            trigger_error("To call VirtualMachine::createTemporaryUpgradeSchedule() you must specify days as a number greater than 0", 256);
            $bInputErrors = true;
        }
        if ($bInputErrors) {
            return false;
        }
        $api = VPSNET::getInstance();
        $api->setAPIResource("virtual_machines/" . $this->id . "/backups");
        $json_request["backup"]->label = $label;
        $result = $api->sendPOSTRequest($json_request);
        $return = NULL;
        if ($result["info"]["http_code"] == 422) {
        } else {
            $this->backups[] = $api->_castObjectToClass("Backup", $result["response"]);
        }
        return $result["response"];
    }
    public function showNetworkGraph($period)
    {
        if (!in_array($period, array("hourly", "daily", "weekly", "monthly"))) {
            trigger_error("To call VirtualMachine::getNetworkGraph() you must specify a period of hourly, daily, weekly or monthly", 256);
            return false;
        }
        return $this->showGraph($period, "network");
    }
    public function showCPUGraph($period)
    {
        if (!in_array($period, array("hourly", "daily", "weekly", "monthly"))) {
            trigger_error("To call VirtualMachine::getCPUGraph() you must specify a period of hourly, daily, weekly or monthly", 256);
            return false;
        }
        return $this->showGraph($period, "cpu");
    }
    protected function showGraph($period, $type)
    {
        $api = VPSNET::getInstance();
        $api->setAPIResource("virtual_machines/" . $this->id . "/" . $type . "_graph", false, "period=" . $period);
        $result = $api->sendGETRequest();
        $response_body = $result["response_body"];
        return $result;
    }
    public function showConsole()
    {
        $api = VPSNET::getInstance();
        $urlpath = substr($_SERVER["PATH_INFO"], 1);
        $api->setAPIResource("virtual_machines/" . $this->id . "/console_proxy/" . $urlpath, false);
        $response_body = $result["response_body"];
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $requestdata = "k=" . urlencode($_POST["k"]) . "&";
            $requestdata .= "w=" . urlencode($_POST["w"]) . "&";
            $requestdata .= "c=" . urlencode($_POST["c"]) . "&";
            $requestdata .= "h=" . urlencode($_POST["h"]) . "&";
            $requestdata .= "s=" . urlencode($_POST["s"]) . "&";
            $result = $api->sendPOSTRequest($requestdata, false);
            header("Content-type: " . $result["info"]["content_type"]);
            echo $result["response_body"];
        } else {
            $result = $api->sendGETRequest();
            if (strpos($urlpath, ".css")) {
                header("Content-type: text/css");
            } else {
                header("Content-type: " . $result["info"]["content_type"]);
            }
            echo $result["response_body"];
        }
        return $result;
    }
    public function loadBackups()
    {
        $api = VPSNET::getInstance();
        $api->setAPIResource("virtual_machines/" . $this->id . "/backups");
        $result = $api->sendGETRequest();
        if ($result["info"]["http_code"] == 422) {
        } else {
            $this->backups = array();
            $response = $result["response"];
            for ($x = 0; $x < count($response); $x++) {
                $this->backups[$x] = $api->_castObjectToClass("Backup", $response[$x]);
            }
        }
        return $this->backups;
    }
    public function loadFully()
    {
        $api = VPSNET::getInstance();
        $api->setAPIResource("virtual_machines/" . $this->id);
        $result = $api->sendGETRequest();
        if ($result["info"]["http_code"] == 422) {
        } else {
            foreach ($result["response"]->virtual_machine as $key => $value) {
                $this->{$key} = $value;
            }
        }
        return $this;
    }
    public function update()
    {
        $api = VPSNET::getInstance();
        if ($this->id < 1) {
            trigger_error("To call VirtualMachine::update() you must set id", 256);
            return false;
        }
        $api->setAPIResource("virtual_machines/" . $this->id);
        $_virtual_machine_keys = array("label" => "", "backups_enabled" => "", "slices_required" => "");
        $vm = $this;
        $requestdata["label"] = $this->label;
        $requestdata["hostname"] = $this->hostname;
        $requestdata["domain_name"] = $this->domain_name;
        $requestdata["slices_required"] = $this->slices_required ? $this->slices_required : $this->slices_count;
        $requestdata["backups_enabled"] = (int) $this->backups_enabled;
        $requestdata["rsync_backups_enabled"] = (int) $this->rsync_backups_enabled;
        $requestdata["r1_soft_backups_enabled"] = (int) $this->r1_soft_backups_enabled;
        $requestdata["system_template_id"] = $this->system_template_id;
        $requestdata["cloud_id"] = $this->cloud_id;
        $requestdata["consumer_id"] = $this->consumer_id;
        $json_request["virtual_machine"] = $requestdata;
        $result = $api->sendPUTRequest($json_request);
        return $result["info"]["http_code"] == 200;
    }
    public function remove()
    {
        $api = VPSNET::getInstance();
        if ($this->id < 1) {
            trigger_error("To call VirtualMachine::remove() you must set its id", 256);
            return false;
        }
        $api->setAPIResource("virtual_machines/" . $this->id);
        $result = $api->sendDELETERequest();
        $this->deleted = $result["info"]["http_code"] == 200;
        return $this->deleted;
    }
}

?>