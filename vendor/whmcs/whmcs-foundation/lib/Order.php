<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS;

class Order
{
    private $orderId = 0;
    private $data = array();
    public function setID($orderId)
    {
        $this->orderId = (int) $orderId;
        $this->loadData();
        return $this;
    }
    protected function loadData()
    {
        try {
            $orderData = Order\Order::findOrFail($this->orderId);
            $this->data = $orderData->toArray();
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }
    public function getData($key)
    {
        if (!$this->data) {
            $this->loadData();
        }
        $keyParts = explode(".", $key);
        if (count($keyParts) == 1) {
            return isset($this->data[$key]) ? $this->data[$key] : "";
        }
        $value = $this->data;
        foreach ($keyParts as $key) {
            $value = isset($value[$key]) ? $value[$key] : "";
        }
        return $value;
    }
    public function getActiveFraudModule()
    {
        $fraudModule = Database\Capsule::table("tblfraud")->where("setting", "Enable")->where("value", "on")->first();
        $module = "";
        if ($fraudModule) {
            $module = $fraudModule->fraud;
        }
        return $module;
    }
    public function shouldFraudCheckBeSkipped()
    {
        $fraudModule = "";
        $userId = (int) $this->getData("userid");
        try {
            $this->skipFraudCheckBecausePaidByCredit();
            $this->skipFraudCheckBecauseOfExistingOrders();
            $this->shouldFraudCheckBeSkippedByHook();
        } catch (Exception\Order\SkipFraudCheck $e) {
            logActivity("Order ID " . $this->orderId . " Skipped Fraud Check due to Already Active Orders", $userId);
            $fraudModule = "SKIPPED";
        } catch (Exception\Order\HookSkipFraudCheck $e) {
            logActivity("Order ID " . $this->orderId . " Skipped Fraud Check due to Custom Hook", $userId);
            $fraudModule = "SKIPPED";
        } catch (Exception\Order\PaidByCredit $e) {
            $fraudModule = "CREDIT";
        }
        if ($fraudModule) {
            Database\Capsule::table("tblorders")->where("id", $this->orderId)->update(array("fraudmodule" => $fraudModule, "fraudoutput" => ""));
            return true;
        }
        return false;
    }
    protected function skipFraudCheckBecauseOfExistingOrders()
    {
        if (Config\Setting::getValue("SkipFraudForExisting")) {
            $userId = (int) $this->getData("userid");
            $existingOrderCount = Order\Order::where("status", "Active")->where("userid", $userId)->count();
            if ($existingOrderCount) {
                throw new Exception\Order\SkipFraudCheck("Existing Order Found");
            }
        }
    }
    protected function shouldFraudCheckBeSkippedByHook()
    {
        $userId = $this->getData("userid");
        $hookParams = array("orderid" => $this->orderId, "userid" => $userId);
        $hookResponses = run_hook("RunFraudCheck", $hookParams);
        foreach ($hookResponses as $hookResponse) {
            if ($hookResponse) {
                throw new Exception\Order\HookSkipFraudCheck("Skipped By Hook");
            }
        }
    }
    protected function skipFraudCheckBecausePaidByCredit()
    {
        $paidByCredit = Database\Capsule::table("tblinvoices")->join("tblorders", "tblorders.invoiceid", "=", "tblinvoices.id")->where("tblorders.id", $this->orderId)->where("tblinvoices.subtotal", "!=", "0")->where("tblinvoices.credit", ">", 0)->where("tblinvoices.total", "=", "0")->count();
        if ($paidByCredit) {
            throw new Exception\Order\PaidByCredit("Paid By Credit");
        }
    }
}

?>