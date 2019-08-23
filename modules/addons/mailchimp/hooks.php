<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

add_hook("ClientAdd", 1, function ($vars) {
    WHMCS\Scheduling\Jobs\Queue::addOrUpdate("mailchimp.customer.update." . $vars["userid"], "WHMCS\\Module\\Addon\\Mailchimp\\Mailchimp", "updateCustomer", array($vars["userid"]));
});
add_hook("ClientEdit", 1, function ($vars) {
    WHMCS\Scheduling\Jobs\Queue::addOrUpdate("mailchimp.customer.update." . $vars["userid"], "WHMCS\\Module\\Addon\\Mailchimp\\Mailchimp", "updateCustomer", array($vars["userid"]));
});
add_hook("AfterCalculateCartTotals", 1, function ($vars) {
    $userId = (int) WHMCS\Session::get("uid");
    $cartData = WHMCS\Session::get("cart");
    $currencyId = WHMCS\Session::get("currency");
    $userEmail = isset($cartData["user"]["email"]) ? $cartData["user"]["email"] : NULL;
    if (!$userId && !$userEmail) {
        return false;
    }
    $orderTotal = isset($vars["total"]) ? $vars["total"] : 0;
    if (is_object($orderTotal)) {
        $orderTotal = $orderTotal->toNumeric();
    }
    $lineItems = array();
    foreach ($vars["products"] as $product) {
        $lineItems[] = array("type" => "product", "id" => $product["pid"], "cycle" => $product["billingcycle"], "price" => isset($product["pricing"]["totaltoday"]) && $product["pricing"]["totaltoday"] instanceof WHMCS\View\Formatter\Price ? $product["pricing"]["totaltoday"]->toNumeric() : "0.00");
    }
    foreach ($vars["addons"] as $addon) {
        $lineItems[] = array("type" => "addon", "id" => $addon["addonid"], "cycle" => $addon["billingcycle"], "price" => isset($addon["totaltoday"]) && $addon["totaltoday"] instanceof WHMCS\View\Formatter\Price ? $addon["totaltoday"]->toNumeric() : "0.00");
    }
    foreach ($vars["domains"] as $domain) {
        $domainParts = explode(".", $domain["domain"], 2);
        $lineItems[] = array("type" => "tld", "id" => $domainParts[1], "cycle" => $domain["regperiod"], "price" => isset($domain["totaltoday"]) && $domain["totaltoday"] instanceof WHMCS\View\Formatter\Price ? $domain["totaltoday"]->toNumeric() : "0.00");
    }
    if (count($lineItems) == 0) {
        return false;
    }
    if ($userId) {
        WHMCS\Scheduling\Jobs\Queue::addOrUpdate("mailchimp.cart.add.userid-" . $userId, "WHMCS\\Module\\Addon\\Mailchimp\\Mailchimp", "createCart", array($userId, $orderTotal, $lineItems), 15);
    } else {
        $firstName = isset($cartData["user"]["firstname"]) ? $cartData["user"]["firstname"] : "";
        $lastName = isset($cartData["user"]["lastname"]) ? $cartData["user"]["lastname"] : "";
        $companyName = isset($cartData["user"]["companyname"]) ? $cartData["user"]["companyname"] : "";
        $currency = getCurrency(0, $currencyId);
        $currencyCode = $currency["code"];
        WHMCS\Scheduling\Jobs\Queue::addOrUpdate("mailchimp.cart.add.email-" . substr(md5($userEmail), 0, 10), "WHMCS\\Module\\Addon\\Mailchimp\\Mailchimp", "createCart", array($userId, $orderTotal, $lineItems, $firstName, $lastName, $companyName, $userEmail, $currencyCode), 15);
    }
});
add_hook("AfterShoppingCartCheckout", 1, function ($vars) {
    $orderId = $vars["OrderID"];
    $invoiceId = $vars["InvoiceID"];
    $order = WHMCS\Order\Order::find($orderId);
    if (is_null($order)) {
        return false;
    }
    $userId = $order->userId;
    $isCompletedOrder = false;
    if (0 < $invoiceId) {
        if ($order->isPaid) {
            $isCompletedOrder = true;
        } else {
            WHMCS\Scheduling\Jobs\Queue::addOrUpdate("mailchimp.cart.add.userid-" . $userId, "WHMCS\\Module\\Addon\\Mailchimp\\Mailchimp", "createCart", array($userId, $order->amount, WHMCS\Module\Addon\Mailchimp\Mailchimp::getLineItemsFromOrder($order)), 60);
        }
    } else {
        $isCompletedOrder = true;
    }
    if ($isCompletedOrder) {
        WHMCS\Scheduling\Jobs\Queue::remove("mailchimp.cart.add.userid-" . $userId);
        WHMCS\Scheduling\Jobs\Queue::remove("mailchimp.cart.add.email-" . substr(md5($order->client->email), 0, 10));
        WHMCS\Scheduling\Jobs\Queue::addOrUpdate("mailchimp.cart.delete.byuserid." . $userId, "WHMCS\\Module\\Addon\\Mailchimp\\Mailchimp", "deleteCart", array($userId));
        WHMCS\Scheduling\Jobs\Queue::addOrUpdate("mailchimp.cart.delete.byemail." . $userId, "WHMCS\\Module\\Addon\\Mailchimp\\Mailchimp", "deleteCart", array(0, $order->client->email));
        WHMCS\Scheduling\Jobs\Queue::addOrUpdate("mailchimp.order." . $orderId, "WHMCS\\Module\\Addon\\Mailchimp\\Mailchimp", "createOrder", array($orderId));
    }
});
add_hook("InvoicePaidPreEmail", 1, function ($vars) {
    $invoiceId = $vars["invoiceid"];
    $order = WHMCS\Order\Order::where("invoiceid", $invoiceId)->first();
    if (is_null($order)) {
        return false;
    }
    $userId = $order->userid;
    WHMCS\Scheduling\Jobs\Queue::remove("mailchimp.cart.add.userid-" . $userId);
    WHMCS\Scheduling\Jobs\Queue::remove("mailchimp.cart.add.email-" . substr(md5($order->client->email), 0, 10));
    WHMCS\Scheduling\Jobs\Queue::addOrUpdate("mailchimp.cart.delete.byuserid." . $userId, "WHMCS\\Module\\Addon\\Mailchimp\\Mailchimp", "deleteCart", array($userId));
    WHMCS\Scheduling\Jobs\Queue::addOrUpdate("mailchimp.cart.delete.byemail." . $userId, "WHMCS\\Module\\Addon\\Mailchimp\\Mailchimp", "deleteCart", array(0, $order->client->email));
    $orderId = $order->id;
    WHMCS\Scheduling\Jobs\Queue::addOrUpdate("mailchimp.order." . $orderId, "WHMCS\\Module\\Addon\\Mailchimp\\Mailchimp", "createOrder", array($orderId));
});
add_hook("AcceptOrder", 1, function ($vars) {
    WHMCS\Scheduling\Jobs\Queue::add("mailchimp.order." . $vars["orderid"] . ".accepted", "WHMCS\\Module\\Addon\\Mailchimp\\Mailchimp", "updateOrder", array($vars["orderid"], false, true, false, false));
});
add_hook("CancelOrder", 1, function ($vars) {
    WHMCS\Scheduling\Jobs\Queue::add("mailchimp.order." . $vars["orderid"] . ".cancelled", "WHMCS\\Module\\Addon\\Mailchimp\\Mailchimp", "updateOrder", array($vars["orderid"], false, false, true, false));
});
add_hook("FraudOrder", 1, function ($vars) {
    WHMCS\Scheduling\Jobs\Queue::add("mailchimp.order." . $vars["orderid"] . ".fraud", "WHMCS\\Module\\Addon\\Mailchimp\\Mailchimp", "updateOrder", array($vars["orderid"], false, false, true, false));
});
add_hook("CancelAndRefundOrder", 1, function ($vars) {
    WHMCS\Scheduling\Jobs\Queue::add("mailchimp.order." . $vars["orderid"] . ".refunded", "WHMCS\\Module\\Addon\\Mailchimp\\Mailchimp", "updateOrder", array($vars["orderid"], false, false, false, true));
});
add_hook("DeleteOrder", 1, function ($vars) {
    WHMCS\Scheduling\Jobs\Queue::add("mailchimp.order." . $vars["orderid"] . ".deleted", "WHMCS\\Module\\Addon\\Mailchimp\\Mailchimp", "deleteOrder", array($vars["orderid"]));
});
add_hook("ProductEdit", 1, function ($vars) {
    WHMCS\Scheduling\Jobs\Queue::addOrUpdate("mailchimp.sync", "WHMCS\\Module\\Addon\\Mailchimp\\Mailchimp", "syncProducts", array());
});
add_hook("AddonConfigSave", 1, function ($vars) {
    WHMCS\Scheduling\Jobs\Queue::addOrUpdate("mailchimp.sync", "WHMCS\\Module\\Addon\\Mailchimp\\Mailchimp", "syncProducts", array());
});
add_hook("TopLevelDomainAdd", 1, function ($vars) {
    WHMCS\Scheduling\Jobs\Queue::addOrUpdate("mailchimp.sync", "WHMCS\\Module\\Addon\\Mailchimp\\Mailchimp", "syncProducts", array());
});
add_hook("TopLevelDomainUpdate", 1, function ($vars) {
    WHMCS\Scheduling\Jobs\Queue::addOrUpdate("mailchimp.sync", "WHMCS\\Module\\Addon\\Mailchimp\\Mailchimp", "syncProducts", array());
});

?>