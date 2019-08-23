<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Notification\Events;

class Order
{
    const DISPLAY_NAME = "Order";
    public function getEvents()
    {
        return array("created" => array("label" => "Created", "hook" => "AfterShoppingCartCheckout"), "paid" => array("label" => "Paid", "hook" => "OrderPaid"), "accepted" => array("label" => "Accepted", "hook" => "AcceptOrder"), "cancelled" => array("label" => "Cancelled", "hook" => "CancelOrder"), "refunded" => array("label" => "Refunded", "hook" => "CancelAndRefundOrder"), "failed_fraud" => array("label" => "Failed Fraud Check", "hook" => "FraudCheckFailed"));
    }
    public function getConditions()
    {
        return array("product" => array("FriendlyName" => "Product/Service", "Type" => "dropdown", "Options" => function () {
            $options = array();
            foreach (\WHMCS\Product\Group::with("products")->sorted()->get() as $group) {
                foreach ($group->products()->sorted()->pluck("name", "id") as $pid => $name) {
                    $options[$pid] = $group->name . " - " . $name;
                }
            }
            return $options;
        }, "GetDisplayValue" => function ($value) {
            try {
                $product = \WHMCS\Product\Product::findOrFail($value);
                return $product->productGroup->name . " - " . $product->name;
            } catch (\Exception $e) {
                return "Product not found";
            }
        }), "addon" => array("FriendlyName" => "Addon", "Type" => "dropdown", "Options" => function () {
            $options = array();
            foreach (\WHMCS\Product\Addon::sorted()->pluck("name", "id") as $addonId => $name) {
                $options[$addonId] = "Addon - " . $name;
            }
            return $options;
        }, "GetDisplayValue" => function ($value) {
            try {
                return "Addon - " . \WHMCS\Product\Addon::findOrFail($value)->name;
            } catch (\Exception $e) {
                return "Addon not found";
            }
        }), "order_total" => array("FriendlyName" => "Order Total", "Type" => "range"), "client_group" => array("FriendlyName" => "Client Group", "Type" => "dropdown", "Options" => function () {
            return \WHMCS\Database\Capsule::table("tblclientgroups")->orderBy("groupname")->pluck("groupname", "id");
        }, "GetDisplayValue" => function ($value) {
            return \WHMCS\Database\Capsule::table("tblclientgroups")->where("id", $value)->first()->groupname;
        }));
    }
    public function evaluateConditions($event, $conditions, $hookParameters)
    {
        $orderId = isset($hookParameters["OrderID"]) ? $hookParameters["OrderID"] : "";
        if (!$orderId) {
            $orderId = isset($hookParameters["orderid"]) ? $hookParameters["orderid"] : "";
        }
        if (!$orderId) {
            $orderId = isset($hookParameters["orderId"]) ? $hookParameters["orderId"] : "";
        }
        $order = null;
        if ($conditions["product"]) {
            if (is_null($order)) {
                $order = \WHMCS\Order\Order::find($orderId);
            }
            $productIds = $order->services()->pluck("packageid");
            if (!$productIds->contains($conditions["product"])) {
                return false;
            }
        }
        if ($conditions["addon"]) {
            if (is_null($order)) {
                $order = \WHMCS\Order\Order::find($orderId);
            }
            $addonIds = $order->addons()->pluck("addonid");
            if (!$addonIds->contains($conditions["addon"])) {
                return false;
            }
        }
        if ($conditions["order_total_filter"] && $conditions["order_total"]) {
            if (is_null($order)) {
                $order = \WHMCS\Order\Order::find($orderId);
            }
            $orderTotal = $order->amount;
            if ($conditions["order_total_filter"] == "greater") {
                if ($orderTotal < $conditions["order_total"]) {
                    return false;
                }
            } else {
                if ($conditions["order_total"] < $orderTotal) {
                    return false;
                }
            }
        }
        if ($conditions["client_group"]) {
            if (is_null($order)) {
                $order = \WHMCS\Order\Order::find($orderId);
            }
            $clientGroup = $order->client->groupId;
            if ($conditions["client_group"] != $clientGroup) {
                return false;
            }
        }
        return true;
    }
    public function buildNotification($event, $hookParameters)
    {
        $orderId = isset($hookParameters["OrderID"]) ? $hookParameters["OrderID"] : "";
        if (!$orderId) {
            $orderId = isset($hookParameters["orderid"]) ? $hookParameters["orderid"] : "";
        }
        if (!$orderId) {
            $orderId = isset($hookParameters["orderId"]) ? $hookParameters["orderId"] : "";
        }
        $order = \WHMCS\Order\Order::find($orderId);
        $title = \AdminLang::trans("fields.ordernum") . $order->orderNumber;
        $amount = $order->amount;
        $invoiceId = $order->invoiceId;
        $paymentMethod = $order->paymentMethod;
        $status = $order->status;
        $firstName = $order->client->firstName;
        $lastName = $order->client->lastName;
        $clientUrl = \App::getSystemUrl() . \App::get_admin_folder_name() . "/clientssummary.php?userid=" . $order->clientId;
        $currency = getCurrency($order->userid);
        $amount = formatCurrency($amount);
        $url = \App::getSystemUrl() . \App::get_admin_folder_name() . "/orders.php?action=view&id=" . $orderId;
        $invoiceUrl = \App::getSystemUrl() . \App::get_admin_folder_name() . "/invoices.php?action=edit&id=" . $invoiceId;
        $message = \AdminLang::trans("notifications.order." . $event);
        switch ($event) {
            case "accepted":
                $status = "Active";
                break;
        }
        $statusStyle = "primary";
        if ($status == "Pending") {
            $statusStyle = "danger";
        } else {
            if ($status == "Active") {
                $statusStyle = "success";
            } else {
                if ($status == "Cancelled") {
                    $statusStyle = "info";
                } else {
                    if ($status == "Fraud") {
                        $statusStyle = "warning";
                    }
                }
            }
        }
        $notification = (new \WHMCS\Notification\Notification())->setTitle($title)->setMessage($message)->setUrl($url)->addAttribute((new \WHMCS\Notification\NotificationAttribute())->setLabel(\AdminLang::trans("fields.client"))->setValue($firstName . " " . $lastName)->setUrl($clientUrl))->addAttribute((new \WHMCS\Notification\NotificationAttribute())->setLabel(\AdminLang::trans("fields.amount"))->setValue($amount));
        if (0 < $invoiceId) {
            if (!function_exists("getGatewayName")) {
                \App::load_function("gateway");
            }
            $notification->addAttribute((new \WHMCS\Notification\NotificationAttribute())->setLabel(\AdminLang::trans("fields.invoicenum"))->setValue($invoiceId)->setUrl($invoiceUrl))->addAttribute((new \WHMCS\Notification\NotificationAttribute())->setLabel(\AdminLang::trans("fields.paymentmethod"))->setValue(getGatewayName($paymentMethod)));
        }
        return $notification->addAttribute((new \WHMCS\Notification\NotificationAttribute())->setLabel(\AdminLang::trans("fields.status"))->setValue($status)->setStyle($statusStyle));
    }
}

?>