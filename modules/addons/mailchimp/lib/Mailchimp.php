<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Addon\Mailchimp;

class Mailchimp implements \WHMCS\Scheduling\Contract\JobInterface
{
    use \WHMCS\Scheduling\Jobs\JobTrait;
    protected $api = NULL;
    public function __construct($testmode = false)
    {
        $this->api = new Api();
        if ($testmode) {
            $this->api->enableTestMode();
        } else {
            $apiKey = \WHMCS\Module\Addon\Setting::module("mailchimp")->where("setting", "apiKey")->pluck("value")->first();
            try {
                $this->api->setApiKey($apiKey);
            } catch (\Exception $e) {
            }
        }
    }
    public function syncProducts()
    {
        $products = $this->api->getProducts();
        $products = collect($products["products"]);
        $productIds = $products->pluck("id");
        $productGroups = \WHMCS\Product\Group::pluck("name", "id");
        foreach (\WHMCS\Product\Product::get(array("id", "gid", "name", "description", "stockcontrol", "qty", "paytype", "hidden")) as $product) {
            $pricing = array();
            $paymentType = strtolower($product->paytype);
            if (in_array($paymentType, array("free", "free account"))) {
                $pricing["free"] = "0.00";
            } else {
                if (in_array($paymentType, array("onetime", "one time"))) {
                    $pricing["onetime"] = "0.00";
                } else {
                    foreach ($product->pricing()->allAvailableCycles() as $price) {
                        $pricing[$price->cycle()] = $price->price()->toNumeric();
                    }
                }
            }
            if ($productIds->contains("product-" . $product->id)) {
                $response = $this->api->updateProduct("product", $product->id, $productGroups[$product->gid], $product->name, $product->description, "cart.php?a=add&pid=" . $product->id, $pricing, $product->stockControlEnabled ? $product->quantityInStock : ($product->isHidden ? 0 : 1));
            } else {
                $response = $this->api->createProduct("product", $product->id, $productGroups[$product->gid], $product->name, $product->description, "cart.php?a=add&pid=" . $product->id, $pricing, $product->stockControlEnabled ? $product->quantityInStock : ($product->isHidden ? 0 : 1));
            }
        }
        foreach (\WHMCS\Product\Addon::get(array("id", "name", "description", "billingcycle", "showorder")) as $addon) {
            $pricing = array();
            $paymentType = strtolower($addon->billingcycle);
            if (in_array($paymentType, array("free", "free account"))) {
                $pricing["free"] = "0.00";
            } else {
                if (in_array($paymentType, array("onetime", "one time"))) {
                    $pricing["onetime"] = "0.00";
                } else {
                    foreach ($addon->pricing()->allAvailableCycles() as $price) {
                        $pricing[$price->cycle()] = $price->price()->toNumeric();
                    }
                }
            }
            if ($productIds->contains("addon-" . $addon->id)) {
                $response = $this->api->updateProduct("addon", $addon->id, "Addon", $addon->name, $addon->description, "cart.php?gid=addons", $pricing, $addon->showOnOrderForm ? 1 : 0);
            } else {
                $response = $this->api->createProduct("addon", $addon->id, "Addon", $addon->name, $addon->description, "cart.php?gid=addons", $pricing, $addon->showOnOrderForm ? 1 : 0);
            }
        }
        $domainCycles = array("msetupfee", "qsetupfee", "ssetupfee", "asetupfee", "bsetupfee", "monthly", "quarterly", "semiannually", "annually", "biennially");
        foreach (\WHMCS\Database\Capsule::table("tbldomainpricing")->orderBy("order")->pluck("extension", "id") as $tldId => $tld) {
            $pricingData = \WHMCS\Database\Capsule::table("tblpricing")->where("type", "domainregister")->where("currency", 1)->where("relid", $tldId)->first();
            $pricing = array();
            foreach ($domainCycles as $i => $key) {
                $price = $pricingData->{$key};
                if (0 <= $price) {
                    $pricing[$i + 1 . "yr" . (0 < $i ? "s" : "")] = $price;
                }
            }
            if (count($pricing) == 0) {
                $pricing["1yr"] = 0;
            }
            $tldNoDot = ltrim($tld, ".");
            if ($productIds->contains("tld-" . $tldNoDot)) {
                $response = $this->api->updateProduct("tld", $tldNoDot, "Domain", $tld, "", "cart.php?a=add&domain=register", $pricing, 1);
            } else {
                $response = $this->api->createProduct("tld", $tldNoDot, "Domain", $tld, "", "cart.php?a=add&domain=register", $pricing, 1);
            }
        }
    }
    public function updateCustomer($userId)
    {
        $settings = \WHMCS\Module\Addon\Setting::module("mailchimp")->pluck("value", "setting");
        $client = \WHMCS\User\Client::find($userId);
        $orderCount = $client->orders()->count();
        $totalSpent = $client->transactions()->sum("amountin") - $client->transactions()->sum("amountout");
        $country = "";
        return $this->api->updateCustomer($userId, $client->email, $client->marketingEmailsOptIn, $client->companyName, $client->firstName, $client->lastName, $orderCount, $totalSpent, $client->address1, $client->address2, $client->city, $client->state, $client->postcode, $country, $client->country, $settings);
    }
    public function createCart($userId, $total, $lineItems, $firstName = "", $lastName = "", $companyName = "", $email = "", $currencyCode = "")
    {
        $cartId = static::generateCartId($userId, $email);
        if (0 < $userId) {
            $client = \WHMCS\User\Client::find($userId);
            $customer = $this->getCustomerDataFromClient($client);
            $currencyCode = $client->currencyrel->code;
        } else {
            $customer = $this->getGuestCustomer($cartId, $firstName, $lastName, $companyName, $email);
        }
        return $this->api->createCart($cartId, $customer, $currencyCode, $total, $lineItems);
    }
    public function deleteCart($userId = 0, $email = "")
    {
        $cartId = static::generateCartId($userId, $email);
        try {
            return $this->api->deleteCart($cartId);
        } catch (Exceptions\ApiException $e) {
            return array();
        }
    }
    public function createOrder($orderId)
    {
        $order = \WHMCS\Order\Order::find($orderId);
        $client = $order->client;
        $customer = $this->getCustomerDataFromClient($client);
        $currencyCode = $client->currencyrel->code;
        $discountTotal = (double) $order->promovalue;
        $taxTotal = (double) $order->invoice->tax1 + $order->invoice->tax2;
        $total = (double) $order->amount;
        $lineItems = static::getLineItemsFromOrder($order);
        return $this->api->createOrder($orderId, $customer, $currencyCode, $discountTotal, $taxTotal, $total, $lineItems);
    }
    public function updateOrder($orderId, $isPaid = false, $isShipped = false, $isCancelled = false, $isRefunded = false)
    {
        try {
            return $this->api->updateOrder($orderId, $isPaid, $isShipped, $isCancelled, $isRefunded);
        } catch (Exceptions\ApiException $e) {
            return array();
        }
    }
    public function deleteOrder($orderId)
    {
        return $this->api->deleteOrder($orderId);
    }
    public static function generateCartId($userId, $email = "")
    {
        if (0 < $userId) {
            return "userid-" . $userId;
        }
        return "email-" . substr(md5($email), 0, 10);
    }
    public static function getLineItemsFromOrder(\WHMCS\Order\Order $order)
    {
        $lineItems = array();
        $cycles = new \WHMCS\Billing\Cycles();
        foreach ($order->services()->get(array("packageid", "firstpaymentamount", "billingcycle")) as $service) {
            $lineItems[] = array("type" => "product", "id" => $service->packageid, "cycle" => $cycles->getNormalisedBillingCycle($service->billingcycle), "price" => $service->firstpaymentamount);
        }
        foreach ($order->addons()->get(array("addonid", "billingcycle", "setupfee", "recurring")) as $addon) {
            $lineItems[] = array("type" => "addon", "id" => $addon->addonid, "cycle" => $cycles->getNormalisedBillingCycle($addon->billingcycle), "price" => $addon->setupfee + $addon->recurring);
        }
        foreach ($order->domains()->get(array("domain", "registrationperiod", "firstpaymentamount")) as $domain) {
            $lineItems[] = array("type" => "tld", "id" => $domain->tld, "cycle" => $domain->registrationperiod, "price" => $domain->firstpaymentamount);
        }
        return $lineItems;
    }
    protected function getCustomerDataFromClient($client)
    {
        $orderCount = $client->orders()->count();
        $totalSpent = $client->transactions()->sum("amountin") - $client->transactions()->sum("amountout");
        $country = "";
        return array("id" => "cust-" . $client->id, "email_address" => $client->email, "opt_in_status" => (bool) $client->marketingEmailsOptIn, "company" => $client->companyName, "first_name" => $client->firstName, "last_name" => $client->lastName, "orders_count" => $orderCount, "total_spent" => $totalSpent, "address" => array("address1" => $client->address1, "address2" => $client->address2, "city" => $client->city, "province" => $client->state, "postal_code" => $client->postcode, "country" => $country, "country_code" => $client->country));
    }
    protected function getGuestCustomer($cartId, $firstName, $lastName, $companyName, $email)
    {
        return array("id" => $cartId, "email_address" => $email, "opt_in_status" => false, "company" => $companyName, "first_name" => $firstName, "last_name" => $lastName);
    }
}

?>