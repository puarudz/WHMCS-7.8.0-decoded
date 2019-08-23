<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 * @ Release on : 24.03.2018
 * @ Website    : http://EasyToYou.eu
 */

function skrill_config()
{
    return array("FriendlyName" => array("Type" => "System", "Value" => "Skrill 1-Tap"), "emailAddress" => array("FriendlyName" => "Email Address", "Type" => "text", "Size" => "30"), "apiMqiPassword" => array("FriendlyName" => "API/MQI Password", "Type" => "text", "Size" => "30", "Description" => "You will need to enable the MQI (merchant query interface) and API (automated payment interface)" . " and set up an MQI/API password to use 1-Tap. <a href=\"https://docs.whmcs.com/Skrill 1-Tap\" class=\"autoLinked\">Learn more</a>"), "secretWord" => array("FriendlyName" => "Secret Word", "Type" => "text", "Size" => "30", "Description" => "This can be configured within your <a href=\"https://account.skrill.com/\" class=\"autoLinked\">Skrill account</a>" . " by logging in and navigating to Settings > Developer Settings > Change secret word."), "maxRecurringAmount" => array("FriendlyName" => "Max Recurring Amount", "Type" => "text", "Size" => "10", "Description" => "<i>Optional</i> Defaults to order total." . " Define to override the maximum amount for future payments." . " Customer's will be asked to authorize this amount when performing an initial 1-Tap payment."), "noAccount" => array("Type" => "info", "Description" => "<div class=\"alert alert-info\" style=\"margin-bottom: 0;\">New to Skrill? <a href=\"https://go.whmcs.com/558/skrill-signup\" class=\"autoLinked alert-link\">Create an account</a></div>"));
}
function skrill_nolocalcc()
{
}
function skrill_no_cc()
{
}
function skrill_link(array $params)
{
    if (!App::getFromRequest("make_payment")) {
        return "<form method=\"POST\" name=\"paymentfrm\" action=\"" . $params["systemurl"] . "viewinvoice.php?id=" . $params["invoiceid"] . "\">\n    <input type=\"hidden\" name=\"make_payment\" value=\"true\">\n    <input type=\"image\" id=\"btnPayNow\" src=\"https://www.skrill.com/fileadmin/content/images/brand_centre/Skrill_Logos/Skrill_1_tap_189x50.png\" alt=\"" . $params["langpaynow"] . "\" title=\"" . $params["langpaynow"] . "\">\n</form>";
    }
    if ($params["clientdetails"]["gatewayid"]) {
        $postFields = array("email" => $params["emailAddress"], "password" => md5($params["apiMqiPassword"]), "action" => "prepare", "amount" => _skrill_amount($params["amount"]), "currency" => $params["currency"], "ondemand_note" => $params["description"], "frn_trn_id" => $params["invoiceid"] . "-" . time(), "rec_payment_id" => $params["clientdetails"]["gatewayid"], "merchant_fields" => "platform,invoice_id", "platform" => "96648253", "invoice_id" => $params["invoiceid"]);
        try {
            $transaction = _skrill_one_tap_request($postFields);
            if ($transaction["STATUS"] == "-2") {
                logTransaction("skrill", array_merge(array("response" => $transaction, "request" => $postFields), $params), "Declined", $params);
                redirSystemURL("id=" . $params["invoiceid"] . "&paymentfailed=true", "viewinvoice.php");
            }
            $clientCurrency = $params["clientdetails"]["currency"];
            $amount = $transaction["AMOUNT"];
            $paymentCurrency = $params["currencyId"];
            if ($paymentCurrency && $clientCurrency != $paymentCurrency) {
                $amount = convertCurrency($amount, $paymentCurrency, $clientCurrency);
            }
            addTransaction($params["userid"], 0, "Invoice Payment", $amount, 0, 0, "skrill", $transaction["ID"], $params["invoiceid"]);
            logTransaction("skrill", array_merge(array("response" => $transaction, "request" => $postFields), $params), "Success", $params);
            redirSystemURL("id=" . $params["invoiceid"] . "&paymentsuccess=true", "viewinvoice.php");
        } catch (WHMCS\Exception\Information $e) {
        } catch (WHMCS\Exception\Billing\BillingException $e) {
        } catch (Exception $e) {
            logTransaction("skrill", array_merge(array("response" => $e->getMessage(), "request" => $postFields), $params), "Error on Payment", $params);
            return "An Error Occurred - Please contact Support";
        }
    }
    $items = WHMCS\Billing\Invoice\Item::where("invoiceid", "=", $params["invoiceid"])->get();
    $maxAmount = 0;
    foreach ($items as $item) {
        switch ($item->type) {
            case "Hosting":
                $service = WHMCS\Service\Service::find($item->relatedEntityId);
                if ($service->orderId) {
                    $maxAmount = WHMCS\Database\Capsule::table("tblorders")->find($service->orderId);
                    if ($maxAmount) {
                        $maxAmount = $maxAmount->amount;
                    }
                }
                break;
            case "Addon":
                $addon = WHMCS\Service\Addon::find($item->relatedEntityId);
                if ($addon->orderId) {
                    $maxAmount = WHMCS\Database\Capsule::table("tblorders")->find($addon->orderId);
                    if ($maxAmount) {
                        $maxAmount = $maxAmount->amount;
                    }
                }
                break;
            case "DomainRegister":
            case "DomainRenew":
            case "DomainTransfer":
            case "DomainAddonDNS":
            case "DomainAddonEMF":
            case "DomainAddonIDP":
                $domain = WHMCS\Domain\Domain::find($item->relatedEntityId);
                if ($domain->orderId) {
                    $maxAmount = WHMCS\Database\Capsule::table("tblorders")->find($domain->orderId);
                    if ($maxAmount) {
                        $maxAmount = $maxAmount->amount;
                    }
                }
                break;
        }
        if ($maxAmount) {
            break;
        }
    }
    $maxAmount = $maxAmount ?: $params["amount"];
    if (!$maxAmount) {
        $maxAmount = $params["amount"];
    }
    if (array_key_exists("maxRecurringAmount", $params) && $params["maxRecurringAmount"]) {
        $clientCurrency = $params["clientdetails"]["currency"];
        $maxAmount = $params["maxRecurringAmount"];
        $paymentCurrency = $params["currencyId"];
        if ($paymentCurrency && $clientCurrency != $paymentCurrency) {
            $maxAmount = convertCurrency($maxAmount, $clientCurrency, $paymentCurrency);
        }
    }
    $postFields = array("pay_to_email" => $params["emailAddress"], "recipient_description" => WHMCS\Config\Setting::getValue("CompanyName"), "return_url" => $params["returnurl"], "return_url_target" => "1", "cancel_url" => $params["returnurl"] . "&paymentfailed=true", "cancel_url_target" => "1", "status_url" => $params["systemurl"] . "/modules/gateways/callback/skrill.php", "language" => _skrill_language($params["clientdetails"]["language"]), "prepare_only" => "1", "merchant_fields" => "platform,invoice_id", "platform" => "96648253", "invoice_id" => $params["invoiceid"], "pay_from_email" => $params["clientdetails"]["email"], "firstname" => $params["clientdetails"]["firstname"], "lastname" => $params["clientdetails"]["lastname"], "address" => $params["clientdetails"]["address1"], "phone_number" => preg_replace("/[^0-9]/", "", $params["clientdetails"]["phonenumber"]), "postal_code" => preg_replace("/[^a-zA-Z0-9]/", "", $params["clientdetails"]["postcode"]), "city" => $params["clientdetails"]["city"], "state" => $params["clientdetails"]["state"], "country" => _skrill_country_code($params["clientdetails"]["countrycode"]), "amount" => _skrill_amount($params["amount"]), "currency" => $params["currency"], "detail1_description" => $params["description"], "detail1_text" => $params["invoicenum"], "ondemand_max_amount" => number_format($maxAmount, 2), "ondemand_max_currency" => $params["currency"], "ondemand_note" => $params["description"], "ondemand_status_url" => $params["systemurl"] . "/modules/gateways/callback/skrill.php");
    $url = "https://pay.skrill.com";
    $rawResponse = curlCall($url, $postFields);
    if (substr($rawResponse, 0, 10) == "CURL Error") {
        logTransaction("skrill", array_merge(array("response" => $rawResponse, "request" => $postFields), $params), "Error on Payment Request", $params);
        return "An Error Occurred - Please contact Support";
    }
    $response = json_decode($rawResponse, true);
    if ($response || is_array($response)) {
        logTransaction("skrill", array_merge($response, $params), "Error on Payment Request", $params);
        return "An Error Occurred - Please contact Support";
    }
    $sessionId = $rawResponse;
    header("Location: " . $url . "?sid=" . $sessionId);
    WHMCS\Terminus::getInstance()->doExit();
}
function skrill_capture(array $params)
{
    try {
        if (!$params["gatewayid"]) {
            throw new WHMCS\Exception\Module\InvalidConfiguration("No 1-Tap Payment Information Stored");
        }
        $postFields = array("email" => $params["emailAddress"], "password" => md5($params["apiMqiPassword"]), "action" => "status_od", "amount" => _skrill_amount($params["amount"]), "trn_id" => $params["gatewayid"]);
        $url = "https://www.skrill.com/app/query.pl";
        $rawResponse = curlCall($url, $postFields);
        if (substr($rawResponse, 0, 10) == "CURL Error") {
            throw new WHMCS\Exception\Module\NotServicable($rawResponse);
        }
        $response = explode("\n", $rawResponse);
        $response = explode("  ", $response[1]);
        $response = explode(" ", $response[0]);
        if ($response[1] == "-1") {
            invoiceDeletePayMethod($params["invoiceid"]);
            throw new WHMCS\Exception\Module\NotServicable("1-Tap Payment Cancelled");
        }
        $postFields = array("email" => $params["emailAddress"], "password" => md5($params["apiMqiPassword"]), "action" => "prepare", "amount" => _skrill_amount($params["amount"]), "currency" => $params["currency"], "ondemand_note" => $params["description"], "frn_trn_id" => $params["invoiceid"] . "-" . time(), "rec_payment_id" => $params["gatewayid"], "merchant_fields" => "platform,invoice_id", "platform" => "96648253", "invoice_id" => $params["invoiceid"]);
        $transaction = _skrill_one_tap_request($postFields);
        if ($transaction["STATUS"] == "-2") {
            return array("status" => "error", "rawdata" => $transaction);
        }
        return array("status" => "success", "transid" => $transaction["ID"], "amount" => $transaction["AMOUNT"], "rawdata" => $transaction);
    } catch (WHMCS\Exception\Billing\BillingException $e) {
        return array("status" => "max_amount", "rawdata" => $e->getMessage());
    } catch (Exception $e) {
        return array("status" => "error", "rawdata" => $e->getMessage());
    }
}
function skrill_storeremote(array $params)
{
    if ($params["action"] == "delete") {
        try {
            $postFields = array("email" => $params["emailAddress"], "password" => md5($params["apiMqiPassword"]), "action" => "cancel_od", "amount" => 0, "trn_id" => $params["gatewayid"]);
            $url = "https://www.skrill.com/app/query.pl";
            $rawResponse = curlCall($url, $postFields);
            if (substr($rawResponse, 0, 10) == "CURL Error") {
                throw new WHMCS\Exception\Module\NotServicable($rawResponse);
            }
            $response = json_decode($rawResponse, true);
            if ($response || is_array($response)) {
                throw new WHMCS\Exception\Gateways\SubscriptionCancellationFailed(json_encode($response));
            }
            $response = explode("\t", $rawResponse);
            $response = $response[2];
            if ($response == "OK") {
                return array("status" => "success", "rawdata" => array("request" => $postFields, "response" => $rawResponse));
            }
            throw new WHMCS\Exception\Gateways\SubscriptionCancellationFailed($rawResponse);
        } catch (Exception $e) {
            return array("status" => "error", "rawdata" => $e->getMessage());
        }
    }
    return array("status" => "success", "rawdata" => array("info" => "No Action Performed"));
}
function skrill_refund(array $params)
{
    try {
        $postFields = array("email" => $params["emailAddress"], "password" => md5($params["apiMqiPassword"]), "action" => "prepare", "amount" => _skrill_amount($params["amount"]), "transaction_id" => $params["transid"], "merchant_fields" => "platform", "platform" => "96648253");
        $url = "https://www.skrill.com/app/refund.pl";
        $rawResponse = curlCall($url, $postFields);
        if (substr($rawResponse, 0, 10) == "CURL Error") {
            throw new WHMCS\Exception\Module\NotServicable($rawResponse);
        }
        $response = XMLtoARRAY($rawResponse);
        $response = $response["RESPONSE"];
        if (array_key_exists("ERROR", $response)) {
            throw new WHMCS\Exception\Module\NotServicable($response["ERROR"]["ERROR_MSG"]);
        }
        $sessionId = $response["SID"];
        $postFields = array("sid" => $sessionId, "action" => "refund");
        $rawResponse = curlCall($url, $postFields);
        if (substr($rawResponse, 0, 10) == "CURL Error") {
            throw new WHMCS\Exception\Module\NotServicable($rawResponse);
        }
        $response = XMLtoARRAY($rawResponse);
        $response = $response["RESPONSE"];
        if (array_key_exists("ERROR", $response)) {
            throw new WHMCS\Exception\Module\NotServicable($response["ERROR"]["ERROR_MSG"]);
        }
        if ($response["STATUS"] != 2) {
            if ($response["STATUS"] == -2) {
                throw new WHMCS\Exception\Module\NotServicable($response["ERROR"]);
            }
            throw new WHMCS\Exception\Module\NotServicable("Refund Pending - Manual Update Required");
        }
        return array("status" => "success", "rawdata" => $response, "transid" => $response["MB_TRANSACTION_ID"]);
    } catch (Exception $e) {
        return array("status" => "error", "rawdata" => $e->getMessage());
    }
}
function _skrill_amount($amount)
{
    return $amount + 0;
}
function _skrill_country_code($countryCode)
{
    $countries = array("AF" => "AFG", "AX" => "ALA", "AL" => "ALB", "DZ" => "DZA", "AS" => "ASM", "AD" => "AND", "AO" => "AGO", "AI" => "AIA", "AQ" => "ATA", "AG" => "ATG", "AR" => "ARG", "AM" => "ARM", "AW" => "ABW", "AU" => "AUS", "AT" => "AUT", "AZ" => "AZE", "BS" => "BHS", "BH" => "BHR", "BD" => "BGD", "BB" => "BRB", "BY" => "BLR", "BE" => "BEL", "BZ" => "BLZ", "BJ" => "BEN", "BM" => "BMU", "BT" => "BTN", "BO" => "BOL", "BQ" => "BES", "BA" => "BIH", "BW" => "BWA", "BV" => "BVT", "BR" => "BRA", "IO" => "IOT", "BN" => "BRN", "BG" => "BGR", "BF" => "BFA", "BI" => "BDI", "KH" => "KHM", "CM" => "CMR", "CA" => "CAN", "CV" => "CPV", "KY" => "CYM", "CF" => "CAF", "TD" => "TCD", "CL" => "CHL", "CN" => "CHN", "CX" => "CXR", "CC" => "CCK", "CO" => "COL", "KM" => "COM", "CG" => "COG", "CD" => "COD", "CK" => "COK", "CR" => "CRI", "CI" => "CIV", "HR" => "HRV", "CU" => "CUB", "CW" => "CUW", "CY" => "CYP", "CZ" => "CZE", "DK" => "DNK", "DJ" => "DJI", "DM" => "DMA", "DO" => "DOM", "EC" => "ECU", "EG" => "EGY", "SV" => "SLV", "GQ" => "GNQ", "ER" => "ERI", "EE" => "EST", "ET" => "ETH", "FK" => "FLK", "FO" => "FRO", "FJ" => "FIJ", "FI" => "FIN", "FR" => "FRA", "GF" => "GUF", "PF" => "PYF", "TF" => "ATF", "GA" => "GAB", "GM" => "GMB", "GE" => "GEO", "DE" => "DEU", "GH" => "GHA", "GI" => "GIB", "GR" => "GRC", "GL" => "GRL", "GD" => "GRD", "GP" => "GLP", "GU" => "GUM", "GT" => "GTM", "GG" => "GGY", "GN" => "GIN", "GW" => "GNB", "GY" => "GUY", "HT" => "HTI", "HM" => "HMD", "VA" => "VAT", "HN" => "HND", "HK" => "HKG", "HU" => "HUN", "IS" => "ISL", "IN" => "IND", "ID" => "IDN", "IR" => "IRN", "IQ" => "IRQ", "IE" => "IRL", "IM" => "IMN", "IL" => "ISR", "IT" => "ITA", "JM" => "JAM", "JP" => "JPN", "JE" => "JEY", "JO" => "JOR", "KZ" => "KAZ", "KE" => "KEN", "KI" => "KIR", "KP" => "PRK", "KR" => "KOR", "KW" => "KWT", "KG" => "KGZ", "LA" => "LAO", "LV" => "LVA", "LB" => "LBN", "LS" => "LSO", "LR" => "LBR", "LY" => "LBY", "LI" => "LIE", "LT" => "LTU", "LU" => "LUX", "MO" => "MAC", "MK" => "MKD", "MG" => "MDG", "MW" => "MWI", "MY" => "MYS", "MV" => "MDV", "ML" => "MLI", "MT" => "MLT", "MH" => "MHL", "MQ" => "MTQ", "MR" => "MRT", "MU" => "MUS", "YT" => "MYT", "MX" => "MEX", "FM" => "FSM", "MD" => "MDA", "MC" => "MCO", "MN" => "MNG", "ME" => "MNE", "MS" => "MSR", "MA" => "MAR", "MZ" => "MOZ", "MM" => "MMR", "NA" => "NAM", "NR" => "NRU", "NP" => "NPL", "NL" => "NLD", "AN" => "ANT", "NC" => "NCL", "NZ" => "NZL", "NI" => "NIC", "NE" => "NER", "NG" => "NGA", "NU" => "NIU", "NF" => "NFK", "MP" => "MNP", "NO" => "NOR", "OM" => "OMN", "PK" => "PAK", "PW" => "PLW", "PS" => "PSE", "PA" => "PAN", "PG" => "PNG", "PY" => "PRY", "PE" => "PER", "PH" => "PHL", "PN" => "PCN", "PL" => "POL", "PT" => "PRT", "PR" => "PRI", "QA" => "QAT", "RE" => "REU", "RO" => "ROU", "RU" => "RUS", "RW" => "RWA", "BL" => "BLM", "SH" => "SHN", "KN" => "KNA", "LC" => "LCA", "MF" => "MAF", "SX" => "SXM", "PM" => "SPM", "VC" => "VCT", "WS" => "WSM", "SM" => "SMR", "ST" => "STP", "SA" => "SAU", "SN" => "SEN", "RS" => "SRB", "SC" => "SYC", "SL" => "SLE", "SG" => "SGP", "SK" => "SVK", "SI" => "SVN", "SB" => "SLB", "SO" => "SOM", "ZA" => "ZAF", "GS" => "SGS", "SS" => "SSD", "ES" => "ESP", "LK" => "LKA", "SD" => "SDN", "SR" => "SUR", "SJ" => "SJM", "SZ" => "SWZ", "SE" => "SWE", "CH" => "CHE", "SY" => "SYR", "TW" => "TWN", "TJ" => "TJK", "TZ" => "TZA", "TH" => "THA", "TL" => "TLS", "TG" => "TGO", "TK" => "TKL", "TO" => "TON", "TT" => "TTO", "TN" => "TUN", "TR" => "TUR", "TM" => "TKM", "TC" => "TCA", "TV" => "TUV", "UG" => "UGA", "UA" => "UKR", "AE" => "ARE", "GB" => "GBR", "US" => "USA", "UM" => "UMI", "UY" => "URY", "UZ" => "UZB", "VU" => "VUT", "VE" => "VEN", "VN" => "VNM", "VG" => "VGB", "VI" => "VIR", "WF" => "WLF", "EH" => "ESH", "YE" => "YEM", "ZM" => "ZMB", "ZW" => "ZWE");
    return array_key_exists($countryCode, $countries) ? $countries[$countryCode] : $countryCode;
}
function _skrill_language($language)
{
    $language = strtolower($language);
    $languages = array("bulgarian" => "BG", "chinese" => "ZH", "czech" => "CS", "danish" => "DA", "dutch" => "NL", "english" => "EN", "finnish" => "FI", "french" => "FR", "german" => "DE", "greek" => "EL", "italian" => "IT", "japanese" => "JA", "polish" => "PL", "romanian" => "RO", "russian" => "RU", "spanish" => "ES", "swedish" => "SV", "turkish" => "TR");
    return array_key_exists($language, $languages) ? $languages[$language] : "EN";
}
function _skrill_one_tap_request(array $postFields)
{
    $url = "https://www.skrill.com/app/ondemand_request.pl";
    $rawResponse = curlCall($url, $postFields);
    if (substr($rawResponse, 0, 10) == "CURL Error") {
        throw new WHMCS\Exception\Module\NotServicable($rawResponse);
    }
    $response = XMLtoARRAY($rawResponse);
    $response = $response["RESPONSE"];
    if (array_key_exists("ERROR", $response)) {
        if (in_array($response["ERROR"]["ERROR_MSG"], array("MAX_AMOUNT_REACHED"))) {
            throw new WHMCS\Exception\Billing\BillingException($response["ERROR"]["ERROR_MSG"]);
        }
        if (in_array($response["ERROR"]["ERROR_MSG"], array("ONDEMAND_CANCELLED"))) {
            throw new WHMCS\Exception\Information($response["ERROR"]["ERROR_MSG"]);
        }
        throw new WHMCS\Exception\Module\NotServicable($response["ERROR"]["ERROR_MSG"]);
    }
    $sessionId = $response["SID"];
    $postFields = array("sid" => $sessionId, "action" => "request");
    $rawResponse = curlCall($url, $postFields);
    if (substr($rawResponse, 0, 10) == "CURL Error") {
        throw new WHMCS\Exception\Module\NotServicable($rawResponse);
    }
    $response = XMLtoARRAY($rawResponse);
    $response = $response["RESPONSE"];
    if (array_key_exists("ERROR", $response)) {
        throw new WHMCS\Exception\Module\NotServicable($response["ERROR"]["ERROR_MSG"]);
    }
    return $response["TRANSACTION"];
}

?>