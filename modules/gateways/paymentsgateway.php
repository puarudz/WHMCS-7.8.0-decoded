<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

if (!defined("WHMCS")) {
    exit("This file cannot be accessed directly");
}
$GATEWAYMODULE["paymentsgatewayname"] = "paymentsgateway";
$GATEWAYMODULE["paymentsgatewayvisiblename"] = "Forte Payment Systems";
$GATEWAYMODULE["paymentsgatewaytype"] = "CC";
function paymentsgateway_activate()
{
    defineGatewayField("paymentsgateway", "text", "merchantid", "", "Merchant ID", "15", "");
    defineGatewayField("paymentsgateway", "text", "password", "", "Password", "20", "");
    defineGatewayField("paymentsgateway", "yesno", "testmode", "", "Test Mode", "", "");
}
function tep_achd_card_type($card_type)
{
    switch ($card_type) {
        case "Visa":
            $card_type_val = "VISA";
            break;
        case "MasterCard":
            $card_type_val = "MAST";
            break;
        case "American Express":
            $card_type_val = "AMER";
            break;
        case "Diners Club":
            $card_type_val = "DINE";
            break;
        case "Discover":
            $card_type_val = "DISC";
            break;
        case "JCB":
            $card_type_val = "JCB";
            break;
    }
    return $card_type_val;
}
function paymentsgateway_capture($params)
{
    $output_transaction = "pg_merchant_id=" . $params["merchantid"] . "&pg_password=" . $params["password"] . "&pg_transaction_type=10&pg_total_amount=" . $params["amount"] . "&ecom_consumerorderid=" . $params["invoiceid"] . "&pg_billto_postal_name_company=" . $params["clientdetails"]["companyname"] . "&ecom_billto_postal_name_first=" . $params["clientdetails"]["firstname"] . "&ecom_billto_postal_name_last=" . $params["clientdetails"]["lastname"] . "&ecom_billto_postal_street_line1=" . $params["clientdetails"]["address1"] . "&ecom_billto_postal_city=" . $params["clientdetails"]["city"] . "&ecom_billto_postal_stateprov=" . $params["clientdetails"]["state"] . "&ecom_billto_postal_postalcode=" . $params["clientdetails"]["postcode"] . "&ecom_billto_postal_countrycode=" . $params["clientdetails"]["country"] . "&ecom_billto_telecom_phone_number=" . $params["clientdetails"]["phonenumber"] . "&ecom_billto_online_email=" . $params["clientdetails"]["email"] . "&ecom_payment_card_type=" . tep_achd_card_type($params["cardtype"]) . "&ecom_payment_card_name=" . $params["clientdetails"]["firstname"] . " " . $params["clientdetails"]["lastname"] . "&ecom_payment_card_number=" . $params["cardnum"] . "&ecom_payment_card_expdate_month=" . substr($params["cardexp"], 0, 2) . "&ecom_payment_card_expdate_year=" . substr($params["cardexp"], 2, 2) . "&endofdata&";
    if ($params["testmode"] == "on") {
        $output_url = "https://www.paymentsgateway.net/cgi-bin/posttest.pl";
    } else {
        $output_url = "https://www.paymentsgateway.net/cgi-bin/postauth.pl";
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $output_url);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $output_transaction);
    $response = curl_exec($ch);
    curl_close($ch);
    $response = explode("\n", $response);
    foreach ($response as $resp) {
        $resp2 = explode("=", $resp);
        $results[$resp2[0]] = $resp2[1];
    }
    if ($results["pg_response_type"] == "A") {
        return array("status" => "success", "transid" => $results["pg_trace_number"], "rawdata" => $results);
    }
    if ($results["pg_response_type"] == "D") {
        return array("status" => "declined", "rawdata" => $results);
    }
    return array("status" => "error", "rawdata" => $results);
}

?>