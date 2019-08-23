<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

namespace WHMCS\Module\Addon\Mailchimp;

class Api
{
    protected $apiKey = NULL;
    protected $dc = NULL;
    protected $testmode = false;
    public function setApiKey($apiKey)
    {
        $apiKey = trim($apiKey);
        $parts = explode("-", $apiKey);
        if (count($parts) != 2) {
            throw new Exceptions\InvalidApiKey("API Key appears to be malformed. Please double check entry and try again.");
        }
        $this->apiKey = $apiKey;
        $this->dc = $parts[1];
        return $this;
    }
    public function info()
    {
        return $this->get("");
    }
    public function getLists()
    {
        return $this->get("lists");
    }
    public function createList($name, $fromEmail, $fromName, $permissionReminder, $contactCompany, $contactAddr1, $contactCity, $contactState, $contactZip, $contactCountry)
    {
        return $this->post("lists", array("name" => $name, "campaign_defaults" => array("from_name" => $fromName, "from_email" => $fromEmail, "subject" => "", "language" => "en"), "permission_reminder" => $permissionReminder, "contact" => array("company" => $contactCompany, "address1" => $contactAddr1, "city" => $contactCity, "state" => $contactState, "zip" => $contactZip, "country" => $contactCountry), "email_type_option" => false));
    }
    public function getStores()
    {
        return $this->get("ecommerce/stores");
    }
    public function deleteStore()
    {
        return $this->delete("ecommerce/stores/whmcs");
    }
    public function getWhmcsStoreListId()
    {
        $stores = $this->getStores();
        if (isset($stores["stores"]) && is_array($stores["stores"]) && 0 < count($stores["stores"])) {
            foreach ($stores["stores"] as $store) {
                if ($store["id"] == "whmcs") {
                    $listId = trim($store["list_id"]);
                    return !empty($listId) ? $listId : true;
                }
            }
        }
        return false;
    }
    public function createStore($listId, $currencyCode, $currencyPrefix)
    {
        return $this->post("ecommerce/stores", array("id" => "whmcs", "list_id" => $listId, "name" => "WHMCS", "currency_code" => $currencyCode, "money_format" => $currencyPrefix));
    }
    public function getProducts()
    {
        return $this->get("ecommerce/stores/whmcs/products?fields=products.id&count=1000");
    }
    public function createProduct($type, $productId, $group, $title, $description, $url, $pricing, $qty)
    {
        $variants = array();
        foreach ($pricing as $cycle => $price) {
            $variants[] = array("id" => $type . "-" . $productId . "-" . $cycle, "title" => $title . " - " . $cycle, "inventory_quantity" => (int) $qty);
        }
        return $this->post("ecommerce/stores/whmcs/products", array("id" => $type . "-" . $productId, "type" => $group, "vendor" => $group, "title" => $group . " - " . $title, "description" => $description, "url" => \App::getSystemURL() . $url, "image_url" => "", "variants" => $variants));
    }
    public function updateProduct($type, $productId, $group, $title, $description, $url, $pricing, $qty)
    {
        $variants = array();
        foreach ($pricing as $cycle => $price) {
            $variants[] = array("id" => $type . "-" . $productId . "-" . $cycle, "title" => $title . " - " . $cycle, "inventory_quantity" => (int) $qty);
        }
        return $this->patch("ecommerce/stores/whmcs/products/" . $type . "-" . $productId, array("type" => $group, "vendor" => $group, "title" => $group . " - " . $title, "description" => $description, "url" => \App::getSystemURL() . $url, "image_url" => "", "variants" => $variants));
    }
    public function updateCustomer($id, $email, $optInStatus, $company, $firstName, $lastName, $orderCount, $totalSpent, $address1, $address2, $city, $state, $postcode, $country, $countryCode, \Illuminate\Support\Collection $settings)
    {
        try {
            $response = $this->get("ecommerce/stores/whmcs/customers/" . $this->getCustomerId($id));
            if (is_array($response)) {
                $existingEmailAddress = $response["email_address"];
                if ($existingEmailAddress != $email) {
                    $optInStatus = (bool) (int) $response["opt_in_status"];
                    $this->delete("lists/" . $settings["primaryListId"] . "/members/" . md5($existingEmailAddress));
                    $this->delete("ecommerce/stores/whmcs/customers/" . $this->getCustomerId($id));
                }
            }
        } catch (\Exception $e) {
            if ($e->getMessage() != "Resource Not Found - The requested resource could not be found.") {
                throw $e;
            }
        }
        return $this->put("ecommerce/stores/whmcs/customers/" . $this->getCustomerId($id), array("id" => $this->getCustomerId($id), "email_address" => $email, "opt_in_status" => (bool) $optInStatus, "company" => $company, "first_name" => $firstName, "last_name" => $lastName, "orders_count" => $orderCount, "total_spent" => $totalSpent, "address" => array("address1" => $address1, "address2" => $address2, "city" => $city, "province" => $state, "postal_code" => $postcode, "country" => $country, "country_code" => $countryCode)));
    }
    public function createOrder($orderId, $customer, $currencyCode, $discountTotal, $taxTotal, $total, $lineItems)
    {
        return $this->post("ecommerce/stores/whmcs/orders", array("id" => $this->getOrderId($orderId), "customer" => $customer, "currency_code" => $currencyCode, "discount_total" => $discountTotal, "tax_total" => $taxTotal, "shipping_total" => 0, "order_total" => $total, "processed_at_foreign" => \WHMCS\Carbon::now()->toDateTimeString(), "billing_address" => array("name" => "", "address1" => "", "address2" => "", "city" => "", "province" => "", "province_code" => "", "postal_code" => "", "country" => "", "country_code" => "", "phone" => "", "company" => ""), "financial_status" => "pending", "fulfillment_status" => "pending", "landing_site" => \App::getSystemUrl(), "order_url" => \App::getSystemUrl() . "clientarea.php", "lines" => $this->formatLineItems($lineItems, "order-item-" . $orderId)));
    }
    public function updateOrder($orderId, $isPaid = false, $isShipped = false, $isCancelled = false, $isRefunded = false)
    {
        $data = array();
        if ($isPaid) {
            $data["financial_status"] = "paid";
        }
        if ($isShipped) {
            $data["fulfillment_status"] = "shipped";
        }
        if ($isRefunded) {
            $data["financial_status"] = "refunded";
        }
        if ($isCancelled) {
            $data["financial_status"] = "cancelled";
            $data["cancelled_at_foreign"] = \WHMCS\Carbon::now()->toDateTimeString();
        }
        return $this->patch("ecommerce/stores/whmcs/orders/" . $this->getOrderId($orderId), $data);
    }
    public function deleteOrder($orderId)
    {
        return $this->delete("ecommerce/stores/whmcs/orders/" . $this->getOrderId($orderId));
    }
    public function createCart($cartId, $customer, $currencyCode, $total, $lineItems)
    {
        return $this->post("ecommerce/stores/whmcs/carts", array("id" => $cartId, "customer" => $customer, "checkout_url" => \App::getSystemUrl() . "cart.php?a=checkout", "currency_code" => $currencyCode, "order_total" => $total, "lines" => $this->formatLineItems($lineItems, "cart-item-" . $cartId)));
    }
    public function deleteCart($cartId)
    {
        return $this->delete("ecommerce/stores/whmcs/carts/" . $cartId);
    }
    protected function getCustomerId($userId)
    {
        return "cust-" . $userId;
    }
    protected function getOrderId($orderId)
    {
        return "order-" . $orderId;
    }
    protected function formatLineItems($lineItems, $prefix)
    {
        $lines = array();
        foreach ($lineItems as $i => $line) {
            $cycleSuffix = "";
            if ($line["type"] == "tld") {
                $cycleSuffix = "yr" . (1 < $line["cycle"] ? "s" : "");
            }
            $lines[] = array("id" => $prefix . "-" . $i, "product_id" => $line["type"] . "-" . $line["id"], "product_variant_id" => $line["type"] . "-" . $line["id"] . "-" . $line["cycle"] . $cycleSuffix, "quantity" => 1, "price" => $line["price"]);
        }
        return $lines;
    }
    protected function getApiUrl()
    {
        if (!$this->dc) {
            throw new Exceptions\InvalidApiKey("API Key is missing or invalid.");
        }
        return "https://" . $this->dc . ".api.mailchimp.com/3.0/";
    }
    protected function getApiKey()
    {
        return $this->apiKey;
    }
    protected function get($action)
    {
        return $this->call("GET", $action, "");
    }
    protected function post($action, $data)
    {
        return $this->call("POST", $action, $data);
    }
    protected function patch($action, $data)
    {
        return $this->call("PATCH", $action, $data);
    }
    protected function put($action, $data)
    {
        return $this->call("PUT", $action, $data);
    }
    protected function delete($action)
    {
        return $this->call("DELETE", $action, "");
    }
    protected function call($method, $action, $data)
    {
        if ($this->testmode) {
            return $data ? $data : $action;
        }
        $url = $this->getApiUrl() . $action;
        $postData = is_array($data) ? json_encode($data) : $data;
        $options = array("CURLOPT_HTTPHEADER" => array("Authorization: Basic " . base64_encode("user:" . $this->getApiKey())), "CURLOPT_TIMEOUT" => 300, "CURLOPT_RETURNTRANSFER" => 1);
        if (in_array($method, array("PATCH", "PUT", "DELETE"))) {
            $options["CURLOPT_CUSTOMREQUEST"] = $method;
        }
        $response = curlCall($url, $postData, $options, false, true);
        logModuleCall("mailchimp", $action, $data, $response, json_decode($response, true));
        return $this->processResponse($response);
    }
    protected function processResponse($response)
    {
        $response = json_decode($response, true);
        if (isset($response["status"]) && $response["status"] != 200) {
            $errorMsg = "";
            if (isset($response["errors"]) && is_array($response["errors"]) && 0 < count($response["errors"])) {
                foreach ($response["errors"] as $error) {
                    if (isset($error["field"]) && !empty($error["field"])) {
                        $errorMsg .= $error["field"] . " - ";
                    }
                    $errorMsg .= $error["message"] . " ";
                }
            } else {
                if (isset($response["title"])) {
                    $errorMsg = $response["title"];
                    if ($errorMsg == "API Key Invalid") {
                        throw new Exceptions\InvalidApiKey("Your API key is invalid. Please check it and try again.");
                    }
                    if (isset($response["detail"])) {
                        $errorMsg .= " - " . $response["detail"];
                    }
                }
            }
            if (empty($errorMsg)) {
                $errorMsg = "Malformed API Response received from the MailChimp API. Please check the module log.";
            }
            throw new Exceptions\ApiException($errorMsg);
        } else {
            unset($response["_links"]);
            return $response;
        }
    }
    public function enableTestMode()
    {
        $this->testmode = true;
    }
}

?>