<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class Service
{
    private $id = "";
    private $userid = "";
    private $data = array();
    private $moduleparams = array();
    private $moduleresults = array();
    private $addons_names = NULL;
    private $addons_to_pids = NULL;
    private $addons_downloads = array();
    private $associated_download_ids = array();
    public function __construct($serviceId = NULL, $userId = NULL)
    {
        if (!function_exists("checkContactPermission")) {
            require_once ROOTDIR . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR . "clientfunctions.php";
        }
        if ($serviceId) {
            $this->setServiceID($serviceId, $userId);
        }
    }
    public function setServiceID($serviceid, $userid = "")
    {
        $this->id = $serviceid;
        $this->userid = $userid;
        $this->data = array();
        $this->moduleparams = array();
        $this->moduleresults = array();
        return $this->getServicesData();
    }
    public function getServicesData()
    {
        $where = array("tblhosting.id" => $this->id);
        if ($this->userid) {
            $where["tblhosting.userid"] = $this->userid;
        }
        $result = select_query("tblhosting", "tblhosting.*,tblproductgroups.id AS group_id,tblproducts.id AS productid,tblproducts.name," . "tblproducts.type,tblproducts.tax,tblproducts.configoptionsupgrade,tblproducts.billingcycleupgrade," . "tblproducts.servertype,tblproductgroups.name as group_name", $where, "", "", "", "tblproducts ON tblproducts.id=tblhosting.packageid INNER JOIN tblproductgroups ON tblproductgroups.id=tblproducts.gid");
        $data = mysql_fetch_array($result);
        if ($data["id"]) {
            $data["pid"] = $data["packageid"];
            $data["status"] = $data["domainstatus"];
            $data["password"] = decrypt($data["password"]);
            $data["groupname"] = Product\Group::getGroupName($data["group_id"], $data["group_name"]);
            $data["productname"] = Product\Product::getProductName($data["packageid"], $data["name"]);
            $this->associated_download_ids = Product\Product::find($data["productid"])->getDownloadIds();
            $this->data = $data;
            $this->data["upgradepackages"] = Product\Product::find($data["productid"])->getUpgradeProductIds();
            return true;
        }
        return false;
    }
    public function isNotValid()
    {
        return !count($this->data) ? true : false;
    }
    public function getData($var)
    {
        return isset($this->data[$var]) ? $this->data[$var] : "";
    }
    public function getID()
    {
        return (int) $this->getData("id");
    }
    public function getServerInfo()
    {
        if (!$this->getData("server")) {
            return array();
        }
        $result = select_query("tblservers", "", array("id" => $this->getData("server")));
        $serverarray = mysql_fetch_assoc($result);
        return $serverarray;
    }
    public function getSuspensionReason()
    {
        global $whmcs;
        if ($this->getData("status") != "Suspended") {
            return "";
        }
        $suspendreason = $this->getData("suspendreason");
        if (!$suspendreason) {
            $suspendreason = $whmcs->get_lang("suspendreasonoverdue");
        }
        return $suspendreason;
    }
    public function getBillingCycleDisplay()
    {
        global $whmcs;
        $lang = strtolower($this->getData("billingcycle"));
        $lang = str_replace(" ", "", $lang);
        $lang = str_replace("-", "", $lang);
        return $whmcs->get_lang("orderpaymentterm" . $lang);
    }
    public function getStatusDisplay()
    {
        global $whmcs;
        $lang = strtolower($this->getData("status"));
        $lang = str_replace(" ", "", $lang);
        $lang = str_replace("-", "", $lang);
        return $whmcs->get_lang("clientarea" . $lang);
    }
    public function getPaymentMethod()
    {
        $paymentmethod = $this->getData("paymentmethod");
        $displayname = get_query_val("tblpaymentgateways", "value", array("gateway" => $paymentmethod, "setting" => "name"));
        return $displayname ? $displayname : $paymentmethod;
    }
    public function getAllowProductUpgrades()
    {
        if ($this->getData("status") == "Active" && $this->getData("upgradepackages")) {
            $upgradepackages = count($this->getData("upgradepackages"));
            return $upgradepackages ? true : false;
        }
        return false;
    }
    public function getAllowConfigOptionsUpgrade()
    {
        if ($this->getData("status") == "Active" && $this->getData("configoptionsupgrade")) {
            return true;
        }
        return false;
    }
    public function getAllowChangePassword()
    {
        if ($this->getData("status") == "Active" && checkContactPermission("manageproducts", true)) {
            return true;
        }
        return false;
    }
    public function getModule()
    {
        $whmcs = \App::self();
        return $whmcs->sanitize("0-9a-z_-", $this->getData("servertype"));
    }
    public function getPredefinedAddonsOnce()
    {
        if (is_array($this->addons_names)) {
            return $this->addons_names;
        }
        return $this->getPredefinedAddons();
    }
    public function getPredefinedAddons()
    {
        $this->addons_names = $this->addons_to_pids = array();
        $result = select_query("tbladdons", "", "");
        while ($data = mysql_fetch_array($result)) {
            $addon_id = $data["id"];
            $addon_packages = $data["packages"];
            $addon_packages = explode(",", $addon_packages);
            $this->addons_names[$addon_id] = $data["name"];
            $this->addons_to_pids[$addon_id] = $addon_packages;
            $this->addons_downloads[$addon_id] = explode(",", $data["downloads"]);
        }
        return $this->addons_names;
    }
    public function getPredefinedAddonName($addonid)
    {
        $addons_data = $this->getPredefinedAddonsOnce();
        return array_key_exists($addonid, $addons_data) ? $addons_data[$addonid] : "";
    }
    private function addAssociatedDownloadID($mixed)
    {
        if (is_array($mixed)) {
            foreach ($mixed as $id) {
                if (is_numeric($id)) {
                    $this->associated_download_ids[] = $id;
                }
            }
        } else {
            if (is_numeric($mixed)) {
                $this->associated_download_ids[] = $mixed;
            } else {
                return false;
            }
        }
        return true;
    }
    public function hasProductGotAddons()
    {
        if (is_null($this->addons_to_pids)) {
            $this->getPredefinedAddons();
        }
        $addons = array();
        foreach ($this->addons_to_pids as $addonid => $pids) {
            if (in_array($this->getData("pid"), $pids)) {
                $addons[] = $addonid;
            }
        }
        return $addons;
    }
    public function getAddons()
    {
        global $whmcs;
        $addonCollection = Service\Addon::with("productAddon")->where("hostingid", "=", $this->getID())->orderBy("id", "DESC")->get();
        $addons = array();
        foreach ($addonCollection as $addon) {
            $addonName = $addon->name;
            $addonPaymentMethod = $addon->paymentGateway;
            $rawStatus = strtolower($addon->status);
            $addonRegistrationDate = fromMySQLDate($addon->registrationDate, 0, 1);
            $addonNextDueDate = fromMySQLDate($addon->nextDueDate, 0, 1);
            $addonPricing = "";
            if (!$addonPaymentMethod) {
                $addonPaymentMethod = ensurePaymentMethodIsSet($addon->clientId, $addon->id, "tblhostingaddons");
            }
            if ($addon->id) {
                if (!$addonName) {
                    $addonName = $addon->productAddon->name;
                }
                if (0 < count($addon->productAddon->downloads)) {
                    $this->addAssociatedDownloadID($addon->productAddon->downloads);
                }
            }
            if (substr($addon->billingCycle, 0, 4) == "Free") {
                $addonPricing = \Lang::trans("orderfree");
                $addonNextDueDate = "-";
            } else {
                if ($addon->billingCycle == "One Time") {
                    $addonNextDueDate = "-";
                }
                if (0 < $addon->setupFee) {
                    $addonPricing .= formatCurrency($addon->setupFee) . \Lang::trans("ordersetupfee");
                }
                if (0 < $addon->recurringFee) {
                    $modifiedCycle = str_replace(array("-", " "), "", strtolower($addon->billingCycle));
                    $addonPricing .= formatCurrency($addon->recurringFee) . " " . \Lang::trans("orderpaymentterm" . $modifiedCycle);
                }
                if (!$addonPricing) {
                    $addonPricing = \Lang::trans("orderfree");
                }
            }
            $xColour = "clientareatable" . $rawStatus;
            $addonStatus = \Lang::trans("clientarea" . $rawStatus);
            if (!in_array($rawStatus, array("Active", "Suspended", "Pending"))) {
                $xColour = "clientareatableterminated";
            }
            $managementActions = "";
            if (defined("CLIENTAREA") && $addon->productAddon->module) {
                $server = new Module\Server();
                if ($server->loadByAddonId($addon->id) && $server->functionExists("ClientArea")) {
                    $managementActions = $server->call("ClientArea");
                    if (is_array($managementActions)) {
                        $managementActions = "";
                    }
                }
            }
            $addons[] = array("id" => $addon->id, "regdate" => $addonRegistrationDate, "name" => $addonName, "pricing" => $addonPricing, "paymentmethod" => $addonPaymentMethod, "nextduedate" => $addonNextDueDate, "status" => $addonStatus, "rawstatus" => $rawStatus, "class" => $xColour, "managementActions" => $managementActions);
        }
        return $addons;
    }
    public function getAssociatedDownloads()
    {
        $download_ids = db_build_in_array(db_escape_numarray($this->associated_download_ids));
        if (!$download_ids) {
            return array();
        }
        $downloadsarray = array();
        $result = select_query("tbldownloads", "", "id IN (" . $download_ids . ")", "id", "DESC");
        while ($data = mysql_fetch_array($result)) {
            $dlid = $data["id"];
            $category = $data["category"];
            $type = $data["type"];
            $title = $data["title"];
            $description = $data["description"];
            $downloads = $data["downloads"];
            $location = $data["location"];
            $fileext = explode(".", $location);
            $fileext = end($fileext);
            $type = "zip";
            if ($fileext == "doc") {
                $type = "doc";
            }
            if ($fileext == "gif" || $fileext == "jpg" || $fileext == "jpeg" || $fileext == "png") {
                $type = "picture";
            }
            if ($fileext == "txt") {
                $type = "txt";
            }
            $type = "<img src=\"images/" . $type . ".png\" align=\"absmiddle\" alt=\"\" />";
            $downloadsarray[] = array("id" => $dlid, "catid" => $category, "type" => $type, "title" => $title, "description" => $description, "downloads" => $downloads, "link" => "dl.php?type=d&id=" . $dlid . "&serviceid=" . $this->getID());
        }
        return $downloadsarray;
    }
    public function getCustomFields()
    {
        return getCustomFields("product", $this->getData("pid"), $this->getData("id"), "", "", "", true);
    }
    public function getConfigurableOptions()
    {
        return getCartConfigOptions($this->getData("pid"), "", $this->getData("billingcycle"), $this->getData("id"));
    }
    public function getAllowCancellation()
    {
        if (($this->getData("status") == "Active" || $this->getData("status") == "Suspended") && checkContactPermission("orders", true)) {
            $billingCycle = $this->getData("billingcycle");
            if (!in_array(strtolower($billingCycle), array("free", "free account", "one time", "onetime"))) {
                $whmcs = \App::self();
                return $whmcs->get_config("ShowCancellationButton") ? true : false;
            }
        }
        return false;
    }
    public function hasCancellationRequest()
    {
        if ($this->getData("status") != "Cancelled") {
            $cancellation = Database\Capsule::table("tblcancelrequests")->select("type")->where("relid", "=", $this->getData("id"))->count();
            return 0 < $cancellation;
        }
        return false;
    }
    public function getDiskUsageStats()
    {
        global $whmcs;
        $diskusage = $this->getData("diskusage");
        $disklimit = $this->getData("disklimit");
        $bwusage = $this->getData("bwusage");
        $bwlimit = $this->getData("bwlimit");
        $lastupdate = $this->getData("lastupdate");
        if ($disklimit == "0") {
            $disklimit = $whmcs->get_lang("clientareaunlimited");
            $diskpercent = "0%";
        } else {
            $diskpercent = round($diskusage / $disklimit * 100, 0) . "%";
        }
        if ($bwlimit == "0") {
            $bwlimit = $whmcs->get_lang("clientareaunlimited");
            $bwpercent = "0%";
        } else {
            $bwpercent = round($bwusage / $bwlimit * 100, 0) . "%";
        }
        $lastupdate = $lastupdate == "0000-00-00 00:00:00" ? "" : fromMySQLDate($lastupdate, 1, 1);
        return array("diskusage" => $diskusage, "disklimit" => $disklimit, "diskpercent" => $diskpercent, "bwusage" => $bwusage, "bwlimit" => $bwlimit, "bwpercent" => $bwpercent, "lastupdate" => $lastupdate);
    }
    public function hasFunction($function)
    {
        $moduleInterface = new Module\Server();
        $moduleName = $this->getModule();
        if (!$moduleName) {
            $this->moduleresults = array("error" => "Service not assigned to a module");
            return false;
        }
        $loaded = $moduleInterface->load($moduleName);
        if (!$loaded) {
            $this->moduleresults = array("error" => "Product module not found");
            return false;
        }
        return $moduleInterface->functionExists($function);
    }
    public function moduleCall($function, $vars = array())
    {
        $moduleInterface = new Module\Server();
        $moduleName = $this->getModule();
        if (!$moduleName) {
            $this->moduleresults = array("error" => "Service not assigned to a module");
            return false;
        }
        $loaded = $moduleInterface->load($moduleName);
        if (!$loaded) {
            $this->moduleresults = array("error" => "Product module not found");
            return false;
        }
        $moduleInterface->setServiceId($this->getID());
        $builtParams = array_merge($moduleInterface->getParams(), $vars);
        switch ($function) {
            case "CreateAccount":
                $hookFunction = "Create";
                break;
            case "SuspendAccount":
                $hookFunction = "Suspend";
                break;
            case "TerminateAccount":
                $hookFunction = "Terminate";
                break;
            case "UnsuspendAccount":
                $hookFunction = "Unsuspend";
                break;
            default:
                $hookFunction = $function;
        }
        $hookResults = run_hook("PreModule" . $hookFunction, array("params" => $builtParams));
        try {
            if (processHookResults($moduleName, $function, $hookResults)) {
                return true;
            }
        } catch (Exception $e) {
            $this->moduleresults = array("error" => $e->getMessage());
            return false;
        }
        $results = $moduleInterface->call($function, $builtParams);
        $hookVars = array("params" => $builtParams, "results" => $results, "functionExists" => $results !== Module\Server::FUNCTIONDOESNTEXIST, "functionSuccessful" => is_array($results) && empty($results["error"]) || is_object($results));
        $successOrFail = "";
        if (!$hookVars["functionSuccessful"] && $hookResults["functionExists"]) {
            $successOrFail = "Failed";
        }
        $hookResults = run_hook("AfterModule" . $hookFunction . $successOrFail, $hookVars);
        try {
            if (processHookResults($moduleName, $function, $hookResults)) {
                return true;
            }
        } catch (Exception $e) {
            return array("error" => $e->getMessage());
        }
        if (!$hookVars["functionExists"] || $results === false) {
            $this->moduleresults = array("error" => "Function not found");
            return false;
        }
        if (is_array($results)) {
            $results = array("data" => $results);
        } else {
            $results = $results == "success" || !$results ? array() : array("error" => $results, "data" => $results);
        }
        $this->moduleresults = $results;
        return isset($results["error"]) && $results["error"] ? false : true;
    }
    public function getModuleReturn($var = "")
    {
        if (!$var) {
            return $this->moduleresults;
        }
        return isset($this->moduleresults[$var]) ? $this->moduleresults[$var] : "";
    }
    public function getLastError()
    {
        return $this->getModuleReturn("error");
    }
}

?>