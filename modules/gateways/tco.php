<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!function_exists("tco_config")) {
    function tco_config()
    {
        $systemUrl = App::getSystemURL();
        $configarray = array("FriendlyName" => array("Type" => "System", "Value" => "2CheckOut"), "vendornumber" => array("FriendlyName" => "Vendor Account Number", "Type" => "text", "Size" => "10", "Description" => "Don't Yet Have an Account? <a href='http://go.whmcs.com/398/2checkout-signup' target='_blank'>Signup for a new account here</a>!"), "integrationMethod" => array("FriendlyName" => "Checkout Style", "Type" => "dropdown", "Value" => "inline", "Options" => array("inline" => "Inline Checkout", "standard" => "Standard Checkout"), "Description" => "Which checkout style would you like to use?"), "apiusername" => array("FriendlyName" => "API Username", "Type" => "text", "Size" => "20", "Description" => "Setup in Account > User Management section of 2CheckOut's Panel"), "apipassword" => array("FriendlyName" => "API Password", "Type" => "text", "Size" => "20", "Description" => ""), "secretword" => array("FriendlyName" => "Secret Word", "Type" => "text", "Size" => "15", "Description" => "Used to validate callbacks, found in Account > Site Management of 2CheckOut's Panel (must leave blank for demo mode testing). Required for 2CheckOut Inline"), "recurringBilling" => array("FriendlyName" => "Recurring Billing", "Type" => "dropdown", "Value" => "", "Options" => array("" => "Offer Recurring & One Time Payments", "forcerecur" => "Offer Recurring Only", "disablerecur" => "Offer One Time Only")), "skipfraudcheck" => array("FriendlyName" => "Skip 2CO Fraud Check", "Type" => "yesno", "Description" => "Tick this box to mark invoices paid as soon as payments are made and not wait for 2CheckOut's Fraud Review Pass"), "demomode" => array("FriendlyName" => "Demo Mode", "Type" => "yesno", "Description" => "Tick to perform demo transactions in Live Mode (not necessary if using Sandbox Mode)"), "sandbox" => array("FriendlyName" => "Use Sandbox", "Type" => "yesno", "Description" => "Tick to use Sandbox Mode (requires a sandbox account)."), "UsageNotes" => array("Type" => "System", "Value" => "You must enable INS Notifications inside your 2CheckOut account." . " To do this, login to 2CheckOut and navigate to" . " <em>Notifications > Global Settings > Global URL</em>, enable all notification options" . " and enter the following URL: '<strong>" . $systemUrl . "modules/gateways/callback/tco.php</strong>'"));
        return $configarray;
    }
    function tco_link(array $params = array())
    {
        $class = "\\WHMCS\\Module\\Gateway\\TCO\\Standard";
        if ($params["integrationMethod"] == "inline") {
            $class = "\\WHMCS\\Module\\Gateway\\TCO\\Inline";
        }
        $tco = new $class();
        return $tco->link($params);
    }
    function tco_refund(array $params = array())
    {
        $sale_id = $params["transid"];
        $post_variables = array("sale_id" => $sale_id, "amount" => $params["amount"], "currency" => "vendor", "category" => 5, "comment" => "Cancelled");
        if (strpos($sale_id, "-")) {
            $parts = explode("-", $sale_id, 2);
            list($post_variables["sale_id"], $post_variables["invoice_id"]) = $parts;
        }
        if ($params["sandbox"] == "on") {
            $url = "https://sandbox.2checkout.com/api/sales/refund_invoice";
        } else {
            $url = "https://www.2checkout.com/api/sales/refund_invoice";
        }
        $query_string = http_build_query($post_variables);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_USERPWD, $params["apiusername"] . ":" . $params["apipassword"]);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json"));
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $results = array();
        if (!($response = curl_exec($ch))) {
            $results["curl_error"] = curl_error($ch);
            curl_close($ch);
            return array("status" => "error", "rawdata" => $results);
        }
        curl_close($ch);
        if (!function_exists("json_decode")) {
            exit("JSON Module Required in PHP Build for 2CheckOut Gateway");
        }
        $response = json_decode($response, true);
        if (!count($response["errors"]) && $response["response_code"] == "OK") {
            $results["transid"] = $params["transid"];
            $results["message"] = $response["response_message"];
            $results["status"] = "success";
            return array("status" => "success", "transid" => $results["transid"], "rawdata" => $results);
        }
        $results["status"] = "error";
        $results["error_code"] = $response["errors"][0]["code"];
        $results["message"] = $response["errors"][0]["message"];
        return array("status" => "error", "rawdata" => $results);
    }
    function tco_reoccuring_request()
    {
        App::load_function("client");
        App::load_function("invoice");
        App::load_function("gateway");
        $GATEWAY = getGatewayVariables("tco");
        $invoiceid = $description = (int) $_POST["invoiceid"];
        $vendorid = $GATEWAY["vendornumber"];
        $apiusername = $GATEWAY["apiusername"];
        $apipassword = $GATEWAY["apipassword"];
        $demomode = $GATEWAY["demomode"];
        $recurrings = getRecurringBillingValues($invoiceid);
        if (!$recurrings) {
            $url = "../../viewinvoice.php?id=" . $invoiceid;
            header("Location:" . $url);
            exit;
        }
        $primaryserviceid = $recurrings["primaryserviceid"];
        $first_payment_amount = $recurrings["firstpaymentamount"] ? $recurrings["firstpaymentamount"] : $recurrings["recurringamount"];
        $recurring_amount = $recurrings["recurringamount"];
        $billing_cycle = $recurrings["recurringcycleperiod"] . " Month";
        if ($recurrings["recurringcycleunits"] == "Years") {
            $billing_cycle = $recurrings["recurringcycleperiod"] . " Year";
        }
        $billing_duration = "Forever";
        $startup_fee = $first_payment_amount - $recurring_amount;
        if ($GATEWAY["sandbox"] == "on") {
            $url = "https://sandbox.2checkout.com/api/products/create_product";
        } else {
            $url = "https://www.2checkout.com/api/products/create_product";
        }
        $name = "Recurring Subscription for Invoice #" . $invoiceid;
        if ($demomode == "on") {
            $query_string = "name=" . $name . "&price=" . $recurring_amount . "&startup_fee=" . $startup_fee . "&demo=Y&recurring=1&recurrence=" . $billing_cycle . "&duration=" . $billing_duration . "&description=" . $description;
        } else {
            $query_string = "name=" . $name . "&price=" . $recurring_amount . "&startup_fee=" . $startup_fee . "&recurring=1&recurrence=" . $billing_cycle . "&duration=" . $billing_duration . "&description=" . $description;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERPWD, $apiusername . ":" . $apipassword);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query_string);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Accept: application/json"));
        $response = curl_exec($ch);
        curl_close($ch);
        if (!function_exists("json_decode")) {
            exit("JSON Module Required in PHP Build for 2CheckOut Gateway");
        }
        $response = json_decode($response, true);
        if (!count($response["errors"]) && $response["response_code"] == "OK") {
            logTransaction($GATEWAY["paymentmethod"], print_r($response, true), "Ok");
            $product_id = $response["product_id"];
            $assigned_product_id = $response["assigned_product_id"];
            $result = select_query("tblinvoices", "userid", array("id" => $invoiceid));
            $data = mysql_fetch_array($result);
            $userid = $data[0];
            $clientsdetails = getClientsDetails($userid);
            $currency = getCurrency($userid);
            if ($GATEWAY["convertto"]) {
                $currency = getCurrency("", $GATEWAY["convertto"]);
            }
            $lang = $clientsdetails["language"];
            if (!$lang) {
                $lang = WHMCS\Config\Setting::getValue("Language");
            }
            $lang = WHMCS\Module\Gateway\TCO\Helper::language($lang);
            if ($lang) {
                $lang = "&lang=" . $lang;
            }
            if ($clientsdetails["country"] != "US" || $clientsdetails["country"] != "CA") {
                $clientsdetails["state"] = "XX";
            }
            if ($GATEWAY["sandbox"] == "on") {
                $domain = "https://sandbox.2checkout.com";
            } else {
                $domain = "https://www.2checkout.com";
            }
            $url = (string) $domain . "/checkout/purchase?sid=" . $vendorid . "&quantity=1&product_id=" . $assigned_product_id . "&currency_code=" . $currency["code"] . "&merchant_order_id=" . $primaryserviceid . "&card_holder_name=" . $clientsdetails["firstname"] . " " . $clientsdetails["lastname"] . "&street_address=" . $clientsdetails["address1"] . "&city=" . $clientsdetails["city"] . "&state=" . $clientsdetails["state"] . "&zip=" . $clientsdetails["postcode"] . "&country=" . $clientsdetails["country"] . "&email=" . $clientsdetails["email"] . "&phone=" . $clientsdetails["phonenumber"] . $lang;
            header("Location:" . $url);
            exit;
        }
        $apierror = "Errors => " . print_r($response, true);
        logTransaction($GATEWAY["paymentmethod"], $apierror, "Error");
        $url = "../../viewinvoice.php?id=" . $invoiceid . "&paymentfailed=true";
        header("Location:" . $url);
        exit;
    }
    function tco_cancelSubscription(array $params)
    {
        $url = "https://www.2checkout.com/api/sales/detail_sale";
        if ($params["sandbox"]) {
            $url = "https://sandbox.2checkout.com/api/sales/detail_sale";
        }
        $subscriptionId = explode("-", $params["subscriptionID"]);
        $invoiceId = 0;
        $saleId = $subscriptionId[0];
        if (1 < count($subscriptionId)) {
            $invoiceId = $subscriptionId[1];
        }
        $url .= "?sale_id=" . $saleId;
        if ($invoiceId) {
            $url .= "&invoice_id=" . $invoiceId;
        }
        try {
            $response = tco_curlCall($url, $params["apiusername"], $params["apipassword"]);
            if ($response["response_code"] == "OK") {
                $lineItems = array();
                foreach ($response["sale"]["invoices"][0]["lineitems"] as $lineItem) {
                    $lineItems[] = $lineItem["lineitem_id"];
                }
                if ($lineItems) {
                    $url = "https://www.2checkout.com/api/sales/stop_lineitem_recurring";
                    if ($params["sandbox"]) {
                        $url = "https://sandbox.2checkout.com/api/sales/stop_lineitem_recurring";
                    }
                    foreach ($lineItems as $lineItem) {
                        tco_curlCall($url, $params["apiusername"], $params["apipassword"], array("lineitem_id" => $lineItem));
                    }
                    return array("status" => "success", "rawdata" => "Cancelled LineItems => " . implode(", ", $lineItems));
                }
            } else {
                return array("status" => "error", "rawdata" => "Error cancelling recurring payment for subscription: " . $params["subscriptionID"] . "\nErrors => " . print_r($response, true));
            }
        } catch (Exception $e) {
            return array("status" => "error", "rawdata" => $e->getMessage());
        }
        return array("status" => "error", "rawdata" => "Error cancelling recurring payment for subscription: " . $params["subscriptionID"]);
    }
    function tco_curlCall($url, $apiUsername, $apiPassword, $request = "")
    {
        if (!function_exists("json_decode")) {
            throw new WHMCS\Exception\Module\InvalidConfiguration("JSON Module Required in PHP Build for 2CheckOut Gateway");
        }
        $response = curlCall($url, $request, array("CURLOPT_USERPWD" => $apiUsername . ":" . $apiPassword, "CURLOPT_HTTPAUTH" => CURLAUTH_BASIC, "CURLOPT_SSL_VERIFYHOST" => 2, "CURLOPT_RETURNTRANSFER" => 1, "CURLOPT_SSL_VERIFYPEER" => 0, "CURLOPT_FOLLOWLOCATION" => 1, "CURLOPT_HEADER" => 0, "CURLOPT_HTTPHEADER" => array("Accept: application/json")));
        $decodedResponse = json_decode($response, true);
        if (!$decodedResponse || json_last_error() !== JSON_ERROR_NONE) {
            throw new WHMCS\Exception\Module\NotServicable($response);
        }
        return $decodedResponse;
    }
}
if (!defined("WHMCS") && !defined("TCO_REOCCURRING_PROCESS")) {
    define("TCO_REOCCURRING_PROCESS", true);
    require_once "../../init.php";
    if (isset($_GET["recurring"]) && $_GET["recurring"] == "1") {
        tco_reoccuring_request();
    }
}

?>