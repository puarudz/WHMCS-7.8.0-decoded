<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class Gateways
{
    private $modulename = "";
    private static $gateways = NULL;
    private $displaynames = array();
    const CC_EXPIRY_MAX_YEARS = 20;
    public function getDisplayNames()
    {
        $result = select_query("tblpaymentgateways", "gateway,value", array("setting" => "name"), "order", "ASC");
        while ($data = mysql_fetch_array($result)) {
            $this->displaynames[$data["gateway"]] = $data["value"];
        }
        return $this->displaynames;
    }
    public function getDisplayName($gateway)
    {
        if (empty($this->displaynames)) {
            $this->getDisplayNames();
        }
        return array_key_exists($gateway, $this->displaynames) ? $this->displaynames[$gateway] : $gateway;
    }
    public static function isNameValid($gateway)
    {
        if (!is_string($gateway) || empty($gateway)) {
            return false;
        }
        if (!ctype_alnum(str_replace(array("_", "-"), "", $gateway))) {
            return false;
        }
        return true;
    }
    public static function getActiveGateways()
    {
        if (is_array(self::$gateways)) {
            return self::$gateways;
        }
        self::$gateways = array();
        $result = select_query("tblpaymentgateways", "DISTINCT gateway", "");
        while ($data = mysql_fetch_array($result)) {
            $gateway = $data[0];
            if (Gateways::isNameValid($gateway)) {
                self::$gateways[] = $gateway;
            }
        }
        return self::$gateways;
    }
    public function getAvailableGatewayInstances($onlyStoreRemote = false)
    {
        $modules = array();
        $gatewaysAggregator = new static();
        foreach (array_keys($gatewaysAggregator->getAvailableGateways()) as $name) {
            $module = new Module\Gateway();
            if ($module->isActiveGateway($name) && $module->load($name)) {
                if ($onlyStoreRemote) {
                    if ($module->functionExists("storeremote")) {
                        $modules[$name] = $module;
                    }
                } else {
                    $modules[$name] = $module;
                }
            }
        }
        return $modules;
    }
    public function isActiveGateway($gateway)
    {
        $gateways = $this->getActiveGateways();
        return in_array($gateway, $gateways);
    }
    public static function makeSafeName($gateway)
    {
        $validgateways = Gateways::getActiveGateways();
        return in_array($gateway, $validgateways) ? $gateway : "";
    }
    public function getAvailableGateways($invoiceid = "")
    {
        $validgateways = array();
        $result = full_query("SELECT DISTINCT gateway, (SELECT value FROM tblpaymentgateways g2 WHERE g1.gateway=g2.gateway AND setting='name' LIMIT 1) AS `name`, (SELECT `order` FROM tblpaymentgateways g2 WHERE g1.gateway=g2.gateway AND setting='name' LIMIT 1) AS `order` FROM `tblpaymentgateways` g1 WHERE setting='visible' AND value='on' ORDER BY `order` ASC");
        while ($data = mysql_fetch_array($result)) {
            $validgateways[$data[0]] = $data[1];
        }
        if ($invoiceid) {
            $invoiceid = (int) $invoiceid;
            $invoicegateway = get_query_val("tblinvoices", "paymentmethod", array("id" => $invoiceid));
            $result = select_query("tblinvoiceitems", "", array("type" => "Hosting", "invoiceid" => $invoiceid));
            while ($data = mysql_fetch_assoc($result)) {
                $relid = $data["relid"];
                if ($relid) {
                    $result2 = full_query("SELECT pg.disabledgateways AS disabled FROM tblhosting h LEFT JOIN tblproducts p on h.packageid = p.id LEFT JOIN tblproductgroups pg on p.gid = pg.id where h.id = " . (int) $relid);
                    $data2 = mysql_fetch_assoc($result2);
                    $gateways = explode(",", $data2["disabled"]);
                    foreach ($gateways as $gateway) {
                        if (array_key_exists($gateway, $validgateways) && $gateway != $invoicegateway) {
                            unset($validgateways[$gateway]);
                        }
                    }
                }
            }
            if (array_key_exists($invoicegateway, $validgateways) === false) {
                $validgateways[$invoicegateway] = get_query_val("tblpaymentgateways", "value", array("setting" => "name", "gateway" => $invoicegateway));
            }
        }
        return $validgateways;
    }
    public function getFirstAvailableGateway()
    {
        $gateways = $this->getAvailableGateways();
        return key($gateways);
    }
    public function getCCDateMonths()
    {
        $months = array();
        for ($i = 1; $i <= 12; $i++) {
            $months[] = str_pad($i, 2, "0", STR_PAD_LEFT);
        }
        return $months;
    }
    public function getCCStartDateYears()
    {
        $startyears = array();
        for ($i = date("Y") - 12; $i <= date("Y"); $i++) {
            $startyears[] = $i;
        }
        return $startyears;
    }
    public function getCCExpiryDateYears()
    {
        $expiryyears = array();
        for ($i = date("Y"); $i <= date("Y") + static::CC_EXPIRY_MAX_YEARS; $i++) {
            $expiryyears[] = $i;
        }
        return $expiryyears;
    }
    public function getActiveMerchantGatewaysByType()
    {
        $groupedGateways = array("assisted" => array(), "merchant" => array(), "remote" => array(), "thirdparty" => array(), "token" => array());
        $query = Database\Capsule::table("tblpaymentgateways as gw1")->where("gw1.setting", "type")->where("gw1.value", "CC")->leftJoin("tblpaymentgateways as gw2", "gw1.gateway", "=", "gw2.gateway")->where("gw2.setting", "visible");
        $gateways = $query->get(array("gw1.gateway", "gw2.value as visible"));
        foreach ($gateways as $gatewayData) {
            $gateway = $gatewayData->gateway;
            $gatewayInterface = new Module\Gateway();
            $gatewayInterface->load($gateway);
            $groupedGateways[$gatewayInterface->getWorkflowType()][$gateway] = (bool) $gatewayData->visible;
        }
        return $groupedGateways;
    }
    public function isLocalCreditCardStorageEnabled($client = true)
    {
        $merchantGateways = $this->getActiveMerchantGatewaysByType()[Module\Gateway::WORKFLOW_MERCHANT];
        if ($client) {
            $merchantGateways = array_filter($merchantGateways);
        }
        return 0 < count($merchantGateways);
    }
    public function isIssueDateAndStartNumberEnabled()
    {
        return (bool) Config\Setting::getValue("ShowCCIssueStart");
    }
    public function isLocalBankAccountGatewayAvailable()
    {
        foreach ($this->getAvailableGatewayInstances() as $gatewayInstance) {
            if ($gatewayInstance->supportsLocalBankDetails()) {
                return true;
            }
        }
        return false;
    }
}

?>